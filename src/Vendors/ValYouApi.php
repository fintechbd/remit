<?php

namespace Fintech\Remit\Vendors;

use ErrorException;
use Exception;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Remit\Contracts\MoneyTransfer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class ValYouApi implements MoneyTransfer
{
    /**
     * @var string
     */
    protected $agent_code;

    /**
     * @var string
     */
    protected $ClientId;

    /**
     * @var string
     */
    protected $agent_session_id;

    /**
     * @var string
     */
    protected $ClientPass;

    /**
     * @var string
     */
    protected $payment_mode;

    /**
     * @var string
     */
    protected $calculated_by_sending_payout_currency;

    /**
     * ValYou API configuration.
     *
     * @var array
     */
    private $config;

    /**
     * ValYou API Url.
     *
     * @var string
     */
    private $apiUrl;

    private $status = 'sandbox';

    /**
     * ValYouApiService constructor.
     */
    public function __construct()
    {
        $this->config = config('valyou');
        if ($this->config['mode'] === 'sandbox') {
            $this->apiUrl = 'https://test.valyouremit.com/SendAPI/webService.asmx';
            $this->status = 'sandbox';

        } else {
            $this->apiUrl = 'https://'.$this->config[$this->status]['app_host'].'/SendAPI/webService.asmx';
            $this->status = 'live';
        }
        $this->ClientId = $this->config[$this->status]['username'];
        $this->ClientPass = $this->config[$this->status]['password'];
        $this->agent_code = $this->config[$this->status]['agent_code'];
        $this->agent_session_id = $this->config[$this->status]['agent_session_id'];
        $this->payment_mode = 'B'; // C- Cash Pickup by ID, B- Account Deposit, H- Home Delivery
        $this->calculated_by_sending_payout_currency = 'P'; // P – Calculated by Payout Currency or C – Calculated by Sending Currency
    }

    /**
     * To ensure optimal response time from the Web Service SOAP, Partner can invoke GetEcho method
     * from time to time for the purpose of server ‘warm-up’ or keep-alive purpose
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function getEcho()
    {
        $signature = $this->agent_code.$this->ClientId.$this->ClientPass;
        $hash_signature = hash('sha256', $signature);
        $xml_string = '
            <AGENT_CODE>'.$this->agent_code.'</AGENT_CODE>
            <USER_ID>'.$this->ClientId.'</USER_ID>
            <SIGNATURE>'.$hash_signature.'</SIGNATURE>
        ';
        $soapMethod = 'GetEcho';
        $xml_post_string = $this->xmlGenerate($xml_string, $soapMethod);
        $response = $this->connectionCheck($xml_post_string, $soapMethod);
        Log::info(json_decode(json_encode($response->GetEchoResponse), true));

        return json_decode(json_encode($response->GetEchoResponse), true);
    }

    /**
     * @return string
     */
    public function xmlGenerate($string, $method)
    {
        $xml_string = '<?xml version="1.0" encoding="utf-8"?>
            <soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
                <soap:Body>
                    <'.$method.' xmlns="WebServices">
                        '.$string.'
                    </'.$method.'>
                </soap:Body>
            </soap:Envelope>
        ';

        return $xml_string;
    }

    /**
     * @return SimpleXMLElement
     *
     * @throws Exception
     */
    public function connectionCheck($xml_post_string, $method)
    {
        $headers = [
            'Host: '.$this->config[$this->status]['app_host'],
            'Content-type: text/xml;charset="utf-8"',
            'Content-length: '.strlen($xml_post_string),
            'SOAPAction: WebServices/'.$method,
        ];

        // PHP cURL  for connection
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string); // the SOAP request
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // execution
        $response = curl_exec($ch);
        if ($response === false) {
            throw new Exception(curl_error($ch), curl_errno($ch));
            $response .= "\nError occoured when connecting to the SMS SOAP Server!";
            $response .= "\nSoap Exception: ".$exception;
            $response .= "\nSOAP Fault: (faultcode: {curl_errno($ch)}, faultstring: {curl_error($ch)})";
            Log::error('CURL reported error: ', $response);
        }
        curl_close($ch);
        $response1 = str_replace('<soap:Body>', '', $response);
        $response2 = str_replace('</soap:Body>', '', $response1);
        $response = str_replace('xmlns="WebServices"', '', $response2);

        return simplexml_load_string($response);
    }

    /**
     * Call this method to Get the Static Data like Countries, list Payment Mode, Get Payout Agent List
     *
     * @param  $input_data
     *                     CTY (Country), OCC (Occupation), SOF (Source of Fund), REL (Relationship),
     *                     PRO (Purpose of Remittance), DOC (Customer Document ID Type)
     *                     PTY (Payment method), AOD (Bank Name (Account Deposit)) are use ADDITIONAL_FIELD1: Destination country Name
     *                     AGT (Cash Pickup Payout Payee) are use ADDITIONAL_FIELD1: Destination country Name and
     *                     ADDITIONAL_FIELD2: Payment Mode (C: cash pickup | B: Account Deposit| H: Home Delivery)
     * @return mixed
     *
     * @throws Exception
     */
    public function getCatalogue($input_data)
    {
        $signature = $this->agent_code.$this->ClientId.$this->agent_session_id.$input_data['catalogue_type'].
            (isset($input_data['additional_field_1']) ? $input_data['additional_field_1'] : '').
            (isset($input_data['additional_field_2']) ? $input_data['additional_field_2'] : '').
            (isset($input_data['additional_field_3']) ? $input_data['additional_field_3'] : '').
            $this->ClientPass;
        $hash_signature = hash('sha256', $signature);
        $xml_string = '
            <AGENT_CODE>'.$this->agent_code.'</AGENT_CODE>
            <USER_ID>'.$this->ClientId.'</USER_ID>
            <AGENT_SESSION_ID>'.$this->agent_session_id.'</AGENT_SESSION_ID>
            <CATALOGUE_TYPE>'.$input_data['catalogue_type'].'</CATALOGUE_TYPE>
            <ADDITIONAL_FIELD1>'.(isset($input_data['additional_field_1']) ? $input_data['additional_field_1'] : '').'</ADDITIONAL_FIELD1>
            <ADDITIONAL_FIELD1>'.(isset($input_data['additional_field_2']) ? $input_data['additional_field_2'] : '').'</ADDITIONAL_FIELD1>
            <ADDITIONAL_FIELD1>'.(isset($input_data['additional_field_3']) ? $input_data['additional_field_3'] : '').'</ADDITIONAL_FIELD1>
            <SIGNATURE>'.$hash_signature.'</SIGNATURE>
        ';
        $soapMethod = 'GetCatalogue';
        $xml_post_string = $this->xmlGenerate($xml_string, $soapMethod);
        $response = $this->connectionCheck($xml_post_string, $soapMethod);
        Log::info(json_decode(json_encode($response->GetCatalogueResponse->GetCatalogueResult), true));

        return json_decode(json_encode($response->GetCatalogueResponse->GetCatalogueResult), true);
    }

    /**
     * Call GetAgentList to get the detailed location information of Payout Partners from System.
     * Location ID should pass to calculate GetExRate and to call SendTransaction method.
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function getAgentList($input_data)
    {
        $pay_out_country = $input_data['pay_out_country'];
        $bank_name = isset($input_data['valyou_bank_name']) ? $input_data['valyou_bank_name'] : '';
        $bank_branch_state = isset($input_data['valyou_bank_branch_state']) ? $input_data['valyou_bank_branch_state'] : '';
        $pay_out_agent_code = isset($input_data['pay_out_agent_code']) ? $input_data['pay_out_agent_code'] : '';
        if ($input_data['recipient_type_name'] == 'Cash') {
            $this->payment_mode = 'C';
            // $this->calculated_by_sending_payout_currency = 'C';
        }
        $signature = $this->agent_code.$this->ClientId.$this->agent_session_id.
            $this->payment_mode.$pay_out_country.$bank_name.$bank_branch_state.$pay_out_agent_code.
            $this->ClientPass;
        $hash_signature = hash('sha256', $signature);
        $xml_string = '
            <AGENT_CODE>'.$this->agent_code.'</AGENT_CODE>
            <USER_ID>'.$this->ClientId.'</USER_ID>
            <AGENT_SESSION_ID>'.$this->agent_session_id.'</AGENT_SESSION_ID>
            <PAYMENTMODE>'.$this->payment_mode.'</PAYMENTMODE>
            <PAYOUT_COUNTRY>'.$pay_out_country.'</PAYOUT_COUNTRY>
            <BANK_NAME>'.$bank_name.'</BANK_NAME>
            <BANK_BRANCHSTATE>'.$bank_branch_state.'</BANK_BRANCHSTATE>
            <PAYOUT_AGENT_CODE>'.$pay_out_agent_code.'</PAYOUT_AGENT_CODE>
            <SIGNATURE>'.$hash_signature.'</SIGNATURE>
        ';
        $soapMethod = 'GetAgentList';
        $xml_post_string = $this->xmlGenerate($xml_string, $soapMethod);
        $response = $this->connectionCheck($xml_post_string, $soapMethod);
        Log::info(json_decode(json_encode($response->GetAgentListResponse->GetAgentListResult), true));

        return json_decode(json_encode($response->GetAgentListResponse->GetAgentListResult), true);
    }

    /**
     * Partner call GetEXRateCommand get the latest Exchange Rate and Service Charges.
     *
     * @param  $input_data
     *                     LOCATION_ID (location_id), TRANSFERAMOUNT (transfer_amount), PAYOUT_COUNTRY (payout_country)
     * @return mixed
     *
     * @throws Exception
     */
    public function exRate($input_data)
    {
        // dd($input_data);
        $location_id = $input_data['location_id'];
        $transfer_amount = $input_data['transfer_amount'];
        $payout_country = $input_data['payout_country'];
        if ($input_data['recipient_type_name'] == 'Cash') {
            $location_id = '96700015P39319013P743723';
            $this->payment_mode = 'C';
            // $this->calculated_by_sending_payout_currency = 'C';
        } elseif ($input_data['recipient_type_name'] == 'Wallet') {
            $location_id = '96700241P96836978';
        }
        $signature = $this->agent_code.$this->ClientId.$this->agent_session_id.
            $transfer_amount.$this->payment_mode.$this->calculated_by_sending_payout_currency.
            $location_id.$payout_country.$this->ClientPass;
        $hash_signature = hash('sha256', $signature);
        $xml_string = '
            <AGENT_CODE>'.$this->agent_code.'</AGENT_CODE>
            <USER_ID>'.$this->ClientId.'</USER_ID>
            <AGENT_SESSION_ID>'.$this->agent_session_id.'</AGENT_SESSION_ID>
            <TRANSFERAMOUNT>'.$transfer_amount.'</TRANSFERAMOUNT>
            <PAYMENTMODE>'.$this->payment_mode.'</PAYMENTMODE>
            <CALC_BY>'.$this->calculated_by_sending_payout_currency.'</CALC_BY>
            <LOCATION_ID>'.$location_id.'</LOCATION_ID>
            <PAYOUT_COUNTRY>'.$payout_country.'</PAYOUT_COUNTRY>
            <PROMOTION_CODE></PROMOTION_CODE>
            <SIGNATURE>'.$hash_signature.'</SIGNATURE>
        ';
        Log::info('exrate xml: '.$xml_string);
        $soapMethod = 'GetEXRate';
        $xml_post_string = $this->xmlGenerate($xml_string, $soapMethod);
        $response = $this->connectionCheck($xml_post_string, $soapMethod);
        Log::info(json_decode(json_encode($response->GetEXRateResponse->GetEXRateResult), true));

        return json_decode(json_encode($response->GetEXRateResponse->GetEXRateResult), true);
    }

    /**
     * This method is used the check current status of transaction by PINNO or by Agent TXN ID.
     *
     * @param  $input_data
     *                     PINNO (PINNO), AGENT_TXNID (AGENT_TXNID)
     * @return mixed
     *
     * @throws Exception
     */
    public function queryTxnStatus($input_data)
    {
        $PINNO = $input_data['PINNO'];
        $AGENT_TXNID = $input_data['AGENT_TXNID'];
        $signature = $this->agent_code.$this->ClientId.$PINNO.$this->agent_session_id.$AGENT_TXNID.$this->ClientPass;
        $hash_signature = hash('sha256', $signature);
        $xml_string = '
            <AGENT_CODE>'.$this->agent_code.'</AGENT_CODE>
            <USER_ID>'.$this->ClientId.'</USER_ID>
            <PINNO>'.$PINNO.'</PINNO>
            <AGENT_SESSION_ID>'.$this->agent_session_id.'</AGENT_SESSION_ID>
            <AGENT_TXNID>'.$AGENT_TXNID.'</AGENT_TXNID>
            <SIGNATURE>'.$hash_signature.'</SIGNATURE>
        ';
        $soapMethod = 'QueryTXNStatus';
        $xml_post_string = $this->xmlGenerate($xml_string, $soapMethod);
        $response = $this->connectionCheck($xml_post_string, $soapMethod);
        Log::info(json_decode(json_encode($response->QueryTXNStatusResponse->QueryTXNStatusResult), true));

        return json_decode(json_encode($response->QueryTXNStatusResponse->QueryTXNStatusResult), true);
    }

    /**
     * Call this method to view send/paid and the status of Transaction report.
     *
     * @param  $input_data
     *                     REPORT_TYPE ($input_data['report_type']) data are
     *                     A: List ALL TXN by SENT Date wise (including Cancel, PAID, UN-Paid TXN)
     *                     S: List ALL TXN by SENT Date wise exclude CANCEL TXN and included PAID, UN-PAID
     *                     P: List all Paid TXN by PAID Date wise
     *                     C: List all Cancelled TXN by Cancel Date wise
     *                     U: List all the TXN Sent Date wise with UN-PAID TXN only
     *                     FROM_DATE ($input_data['from_date']), TO_DATE ($input_data['to_date'])
     * @return mixed
     *
     * @throws Exception
     */
    public function reconcileReport($input_data)
    {
        $signature = $this->agent_code.$this->ClientId.$this->agent_session_id.
            $input_data['report_type'].$input_data['from_date'].'00:00:00'.$input_data['to_date'].'23:59:59'.
            $this->ClientPass;
        $hash_signature = hash('sha256', $signature);
        $xml_string = '
            <AGENT_CODE>'.$this->agent_code.'</AGENT_CODE>
            <USER_ID>'.$this->ClientId.'</USER_ID>
            <AGENT_SESSION_ID>'.$this->agent_session_id.'</AGENT_SESSION_ID>
            <REPORT_TYPE>'.$input_data['report_type'].'</REPORT_TYPE>
            <FROM_DATE>'.$input_data['from_date'].'</FROM_DATE>
            <FROM_DATE_TIME>00:00:00</FROM_DATE_TIME>
            <TO_DATE>'.$input_data['to_date'].'</TO_DATE>
            <TO_DATE_TIME>23:59:59</TO_DATE_TIME>
            <SIGNATURE>'.$hash_signature.'</SIGNATURE>
        ';
        $soapMethod = 'ReconcileReport';
        $xml_post_string = $this->xmlGenerate($xml_string, $soapMethod);
        $response = $this->connectionCheck($xml_post_string, $soapMethod);
        Log::info(json_decode(json_encode($response->ReconcileReportResponse->ReconcileReportResult), true));

        return json_decode(json_encode($response->ReconcileReportResponse->ReconcileReportResult), true);
    }

    /**
     * Call this method to view send/paid and the status of Transaction report.
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function reconcileReportV2($input_data)
    {
        $signature = $this->agent_code.$this->ClientId.$this->agent_session_id.
            $input_data['report_type'].$input_data['from_date'].'00:00:00'.$input_data['to_date'].'23:59:59'.
            $this->ClientPass;
        $hash_signature = hash('sha256', $signature);
        $xml_string = '
            <AGENT_CODE>'.$this->agent_code.'</AGENT_CODE>
            <USER_ID>'.$this->ClientId.'</USER_ID>
            <AGENT_SESSION_ID>'.$this->agent_session_id.'</AGENT_SESSION_ID>
            <REPORT_TYPE>'.$input_data['report_type'].'</REPORT_TYPE>
            <FROM_DATE>'.$input_data['from_date'].'</FROM_DATE>
            <FROM_DATE_TIME>00:00:00</FROM_DATE_TIME>
            <TO_DATE>'.$input_data['to_date'].'</TO_DATE>
            <TO_DATE_TIME>23:59:59</TO_DATE_TIME>
            <SIGNATURE>'.$hash_signature.'</SIGNATURE>
        ';
        $soapMethod = 'ReconcileReportV2';
        $xml_post_string = $this->xmlGenerate($xml_string, $soapMethod);
        $response = $this->connectionCheck($xml_post_string, $soapMethod);
        Log::info(json_decode(json_encode($response->ReconcileReportV2Response->ReconcileReportV2Result), true));

        return json_decode(json_encode($response->ReconcileReportV2Response->ReconcileReportV2Result), true);
    }

    /**
     * @param  $input_data
     *                     PAYOUT_COUNTRY ($input_data['country_name'])
     * @return mixed
     *
     * @throws Exception
     */
    public function getCountryWiseRate($input_data)
    {
        $signature = $this->agent_code.$this->ClientId.$this->agent_session_id.$input_data['country_name'].$this->ClientPass;
        $hash_signature = hash('sha256', $signature);
        $xml_string = '
            <AGENT_CODE>'.$this->agent_code.'</AGENT_CODE>
            <USER_ID>'.$this->ClientId.'</USER_ID>
            <AGENT_SESSION_ID>'.$this->agent_session_id.'</AGENT_SESSION_ID>
            <PAYOUT_COUNTRY>'.$input_data['country_name'].'</PAYOUT_COUNTRY>
            <SIGNATURE>'.$hash_signature.'</SIGNATURE>
        ';
        $soapMethod = 'GetCountryWiseRate';
        $xml_post_string = $this->xmlGenerate($xml_string, $soapMethod);
        $response = $this->connectionCheck($xml_post_string, $soapMethod);
        Log::info(json_decode(json_encode($response->GetCountryWiseRateResponse->GetCountryWiseRateResult), true));

        return json_decode(json_encode($response->GetCountryWiseRateResponse->GetCountryWiseRateResult), true);
    }

    /**
     * Call this method to check the validation of the account.
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function accountValidate($input_data)
    {
        $location_id = $input_data['location_id'];
        $account = $input_data['account'];
        $signature = $this->agent_code.$this->ClientId.$this->agent_session_id.$location_id.$account.$this->ClientPass;
        $hash_signature = hash('sha256', $signature);
        $xml_string = '
            <AGENT_CODE>'.$this->agent_code.'</AGENT_CODE>
            <USER_ID>'.$this->ClientId.'</USER_ID>
            <AGENT_SESSION_ID>'.$this->agent_session_id.'</AGENT_SESSION_ID>
            <LOCATION_ID>'.$location_id.'</LOCATION_ID>
            <ACCOUNT_NO>'.$account.'</ACCOUNT_NO>
            <SIGNATURE>'.$hash_signature.'</SIGNATURE>
        ';
        $soapMethod = 'AccountValidate';
        $xml_post_string = $this->xmlGenerate($xml_string, $soapMethod);
        $response = $this->connectionCheck($xml_post_string, $soapMethod);
        Log::info(json_decode(json_encode($response->AccountValidateResponse->AccountValidateResult), true));

        return json_decode(json_encode($response->AccountValidateResponse->AccountValidateResult), true);
    }

    /**
     * Call this method to get the notification of the amended transaction.
     *
     * @param  $input_data
     *                     SHOW_INCREMENTAL ($input_data['show_incremental']) data are
     *                     "Y" = List report incremental basis
     *                     "N" = List report within date range
     *                     If "y" then FROM_DATE and TO_DATE not required
     * @return mixed
     *
     * @throws Exception
     */
    public function notificationStatus($input_data)
    {
        $signature = $this->agent_code.$this->ClientId.$this->agent_session_id.
            $input_data['from_date'].$input_data['to_date'].$input_data['show_incremental'].
            $this->ClientPass;
        $hash_signature = hash('sha256', $signature);
        $xml_string = '
            <AGENT_CODE>'.$this->agent_code.'</AGENT_CODE>
            <USER_ID>'.$this->ClientId.'</USER_ID>
            <AGENT_SESSION_ID>'.$this->agent_session_id.'</AGENT_SESSION_ID>
            <FROM_DATE>'.$input_data['from_date'].'</FROM_DATE>
            <TO_DATE>'.$input_data['to_date'].'</TO_DATE>
            <SHOW_INCREMENTAL>'.$input_data['show_incremental'].'</SHOW_INCREMENTAL>
            <SIGNATURE>'.$hash_signature.'</SIGNATURE>
        ';
        $soapMethod = 'NotificationStatus';
        $xml_post_string = $this->xmlGenerate($xml_string, $soapMethod);
        $response = $this->connectionCheck($xml_post_string, $soapMethod);
        Log::info(json_decode(json_encode($response->NotificationStatusResponse->NotificationStatusResult), true));

        return json_decode(json_encode($response->NotificationStatusResponse->NotificationStatusResult), true);
    }

    /**
     * @return object
     *
     * @throws Exception
     */
    public function topUp($input)
    {
        $sendTransfer = $this->sendTransaction($input);
        if ($sendTransfer['CODE'] == 0) {
            $sendTransfer['status_code'] = 200;
            $authorizedConfirmed = $this->authorizedConfirmed($sendTransfer);
        } else {
            $authorizedConfirmed = [
                'MESSAGE' => 'Transaction Error',
                'CODE' => '3007',
            ];
        }

        return (object) array_merge($sendTransfer, $authorizedConfirmed);
    }

    /**
     * After Successful calling GetExRate method, call this function to send transaction.
     * While sending Transaction if Customer already exists, it will update the existing Customer.
     * If theCustomer is new, the SendTransaction method Auto-creates new Customer based on Sender_ID_Type and
     * Sender_ID_Number(these information will be unique to identify Customer)
     *
     * The Authorized_Confirmed method has to be called within 30 minutes of SendTransaction
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function sendTransaction($input_data)
    {
        // dd($input_data);
        $location_id = $input_data->valyou_location_routing_id[0]->bank_branch_location_field_value;
        $AGENT_TXNID = $input_data->purchase_number;
        $SENDER_FIRST_NAME = $input_data->sender_first_name;
        $SENDER_MIDDLE_NAME = '';
        $SENDER_LAST_NAME = '';
        $SENDER_GENDER = $input_data->sender_gender;
        $SENDER_ADDRESS = $input_data->sender_address;
        $SENDER_CITY = $input_data->sender_city;
        $SENDER_STATES = $input_data->sender_states;
        $SENDER_ZIPCODE = $input_data->sender_zipcode;
        $SENDER_COUNTRY = $input_data->sender_country;
        $SENDER_MOBILE = $input_data->sender_mobile;
        $SENDER_NATIONALITY = $input_data->sender_nationality;
        $SENDER_ID_TYPE = $input_data->sender_id_type;
        $SENDER_ID_NUMBER = $input_data->sender_id_number;
        $SENDER_ID_ISSUE_COUNTRY = $input_data->sender_id_issue_country;
        $SENDER_ID_ISSUE_DATE = date('Y-m-d', strtotime($input_data->sender_id_issue_date));
        $SENDER_ID_EXPIRE_DATE = date('Y-m-d', strtotime($input_data->sender_expire_date));
        $SENDER_DATE_OF_BIRTH = date('Y-m-d', strtotime($input_data->sender_date_of_birth));
        $SENDER_OCCUPATION = $input_data->sender_occupation;
        $SENDER_SOURCE_OF_FUND = $input_data->sender_source_of_fund;
        $SENDER_COUNTRY_OF_BIRTH = $input_data->sender_country_of_birth;
        $SENDER_BENEFICIARY_RELATIONSHIP = $input_data->sender_beneficiary_relationship;
        // $PURPOSE_OF_REMITTANCE = $input_data->purpose_of_remittance;
        $PURPOSE_OF_REMITTANCE = 'FAMILY MAINTENANCE/SAVINGS';
        $RECEIVER_FIRST_NAME = $input_data->receiver_first_name;
        $RECEIVER_MIDDLE_NAME = ''; // $input_data->receiver_middle_name;
        $RECEIVER_LAST_NAME = $input_data->receiver_last_name;
        $RECEIVER_ADDRESS = $input_data->receiver_address;
        $RECEIVER_CONTACT_NUMBER = ''; // $input_data->receiver_contact_number;
        $RECEIVER_COUNTRY = $input_data->receiver_country;
        $RECEIVER_CITY = $input_data->receiver_country;
        $TRANSFER_AMOUNT = $input_data->transfer_amount;
        $REMIT_CURRENCY = ''; // $input_data->sender_currency;
        $TRANSFER_CURRENCY = $input_data->transfer_currency;
        $BANK_NAME = $input_data->valyou_bank_name;
        $BANK_BRANCH_NAME = $input_data->valyou_bank_branch_name;
        if ($TRANSFER_CURRENCY == 'INR') {
            $BANK_BRANCH_NAME = $input_data->valyou_location_routing_id[1]->bank_branch_location_field_value;
        }
        $BANK_ACCOUNT_NUMBER = $input_data->bank_account_number;
        if ($input_data['recipient_type_name'] == 'Cash') {
            $location_id = is_null($input_data['location_id']) ? $input_data['location_id'] : '96700015P39319013P743723';
            $this->payment_mode = 'C';
            // $this->calculated_by_sending_payout_currency = 'C';
        } elseif ($input_data['recipient_type_name'] == 'Wallet') {
            $location_id = is_null($input_data['location_id']) ? $input_data['location_id'] : '96700241P96836978';
        }
        $signature = $this->agent_code.$this->ClientId.$this->agent_session_id.$AGENT_TXNID.$location_id.
            $SENDER_FIRST_NAME.$SENDER_MIDDLE_NAME.$SENDER_LAST_NAME.$SENDER_GENDER.
            $SENDER_ADDRESS.$SENDER_CITY.$SENDER_STATES.$SENDER_ZIPCODE.$SENDER_COUNTRY.$SENDER_MOBILE.
            $SENDER_NATIONALITY.$SENDER_ID_TYPE.$SENDER_ID_NUMBER.$SENDER_ID_ISSUE_COUNTRY.$SENDER_ID_ISSUE_DATE.
            $SENDER_ID_EXPIRE_DATE.$SENDER_DATE_OF_BIRTH.$SENDER_OCCUPATION.$SENDER_SOURCE_OF_FUND.
            $SENDER_COUNTRY_OF_BIRTH.$SENDER_BENEFICIARY_RELATIONSHIP.$PURPOSE_OF_REMITTANCE.
            $RECEIVER_FIRST_NAME.$RECEIVER_MIDDLE_NAME.$RECEIVER_LAST_NAME.$RECEIVER_ADDRESS.
            $RECEIVER_CONTACT_NUMBER.$RECEIVER_CITY.$RECEIVER_COUNTRY.
            $this->calculated_by_sending_payout_currency.$TRANSFER_AMOUNT.$REMIT_CURRENCY.
            $TRANSFER_CURRENCY.$this->payment_mode.$BANK_NAME.$BANK_BRANCH_NAME.$BANK_ACCOUNT_NUMBER.$this->ClientPass;
        $hash_signature = hash('sha256', $signature);
        $xml_string = '
            <AGENT_CODE>'.$this->agent_code.'</AGENT_CODE>
            <USER_ID>'.$this->ClientId.'</USER_ID>
            <AGENT_SESSION_ID>'.$this->agent_session_id.'</AGENT_SESSION_ID>
            <AGENT_TXNID>'.$AGENT_TXNID.'</AGENT_TXNID>
            <LOCATION_ID>'.$location_id.'</LOCATION_ID>
            <SENDER_FIRST_NAME>'.$SENDER_FIRST_NAME.'</SENDER_FIRST_NAME>
            <SENDER_MIDDLE_NAME></SENDER_MIDDLE_NAME>
            <SENDER_LAST_NAME></SENDER_LAST_NAME>
            <SENDER_GENDER>'.$SENDER_GENDER.'</SENDER_GENDER>
            <SENDER_ADDRESS>'.$SENDER_ADDRESS.'</SENDER_ADDRESS>
            <SENDER_CITY>'.$SENDER_CITY.'</SENDER_CITY>
            <SENDER_STATES>'.$SENDER_STATES.'</SENDER_STATES>
            <SENDER_ZIPCODE>'.$SENDER_ZIPCODE.'</SENDER_ZIPCODE>
            <SENDER_COUNTRY>'.$SENDER_COUNTRY.'</SENDER_COUNTRY>
            <SENDER_MOBILE>'.$SENDER_MOBILE.'</SENDER_MOBILE>
            <SENDER_NATIONALITY>'.$SENDER_NATIONALITY.'</SENDER_NATIONALITY>
            <SENDER_ID_TYPE>'.$SENDER_ID_TYPE.'</SENDER_ID_TYPE>
            <SENDER_ID_NUMBER>'.$SENDER_ID_NUMBER.'</SENDER_ID_NUMBER>
            <SENDER_ID_ISSUE_COUNTRY>'.$SENDER_ID_ISSUE_COUNTRY.'</SENDER_ID_ISSUE_COUNTRY>
            <SENDER_ID_ISSUE_DATE>'.$SENDER_ID_ISSUE_DATE.'</SENDER_ID_ISSUE_DATE>
            <SENDER_ID_EXPIRE_DATE>'.$SENDER_ID_EXPIRE_DATE.'</SENDER_ID_EXPIRE_DATE>
            <SENDER_DATE_OF_BIRTH>'.$SENDER_DATE_OF_BIRTH.'</SENDER_DATE_OF_BIRTH>
            <SENDER_OCCUPATION>'.$SENDER_OCCUPATION.'</SENDER_OCCUPATION>
            <SENDER_SOURCE_OF_FUND>'.$SENDER_SOURCE_OF_FUND.'</SENDER_SOURCE_OF_FUND>
            <SENDER_SECONDARY_ID_TYPE></SENDER_SECONDARY_ID_TYPE>
            <SENDER_SECONDARY_ID_NUMBER></SENDER_SECONDARY_ID_NUMBER>
            <SENDER_COUNTRY_OF_BIRTH>'.$SENDER_COUNTRY_OF_BIRTH.'</SENDER_COUNTRY_OF_BIRTH>
            <SENDER_BENEFICIARY_RELATIONSHIP>'.$SENDER_BENEFICIARY_RELATIONSHIP.'</SENDER_BENEFICIARY_RELATIONSHIP>
            <PURPOSE_OF_REMITTANCE>'.$PURPOSE_OF_REMITTANCE.'</PURPOSE_OF_REMITTANCE>
            <RECEIVER_FIRST_NAME>'.$RECEIVER_FIRST_NAME.'</RECEIVER_FIRST_NAME>
            <RECEIVER_MIDDLE_NAME>'.$RECEIVER_MIDDLE_NAME.'</RECEIVER_MIDDLE_NAME>
            <RECEIVER_LAST_NAME>'.$RECEIVER_LAST_NAME.'</RECEIVER_LAST_NAME>
            <RECEIVER_ADDRESS>'.$RECEIVER_ADDRESS.'</RECEIVER_ADDRESS>
            <RECEIVER_CONTACT_NUMBER>'.$RECEIVER_CONTACT_NUMBER.'</RECEIVER_CONTACT_NUMBER>
            <RECEIVER_CITY>'.$RECEIVER_CITY.'</RECEIVER_CITY>
            <RECEIVER_COUNTRY>'.$RECEIVER_COUNTRY.'</RECEIVER_COUNTRY>
            <RECEIVER_ID_TYPE></RECEIVER_ID_TYPE>
            <RECEIVER_ID_NUMBER></RECEIVER_ID_NUMBER>
            <CALC_BY>'.$this->calculated_by_sending_payout_currency.'</CALC_BY>
            <TRANSFER_AMOUNT>'.$TRANSFER_AMOUNT.'</TRANSFER_AMOUNT>
            <REMIT_CURRENCY>'.$REMIT_CURRENCY.'</REMIT_CURRENCY>
            <TRANSFER_CURRENCY>'.$TRANSFER_CURRENCY.'</TRANSFER_CURRENCY>
            <PAYMENTMODE>'.$this->payment_mode.'</PAYMENTMODE>
            <BANK_NAME>'.$BANK_NAME.'</BANK_NAME>
            <BANK_BRANCH_NAME>'.$BANK_BRANCH_NAME.'</BANK_BRANCH_NAME>
            <BANK_ACCOUNT_NUMBER>'.$BANK_ACCOUNT_NUMBER.'</BANK_ACCOUNT_NUMBER>
            <PROMOTION_CODE></PROMOTION_CODE>
            <SIGNATURE>'.$hash_signature.'</SIGNATURE>
            <EXRATE_SESSION_ID></EXRATE_SESSION_ID>
        ';
        $soapMethod = 'SendTransaction';
        $xml_post_string = $this->xmlGenerate($xml_string, $soapMethod);
        $response = $this->connectionCheck($xml_post_string, $soapMethod);
        Log::info(json_decode(json_encode($response->SendTransactionResponse->SendTransactionResult), true));

        return json_decode(json_encode($response->SendTransactionResponse->SendTransactionResult), true);
    }

    /**
     * Call this method to Commit Transaction.
     * After SendTransaction, you must call Authorized_Confirmed to make transaction available.
     * Session will be expired in 30 mins.
     * All those transactions which are not Authorized, will be truncated from system at End of Day.
     *
     * @return mixed
     *
     * @throws Exception
     */
    public function authorizedConfirmed($input_data)
    {
        $PINNO = $input_data['PINNO'];
        $signature = $this->agent_code.$this->ClientId.$PINNO.$this->agent_session_id.$this->ClientPass;
        $hash_signature = hash('sha256', $signature);
        $xml_string = '
            <AGENT_CODE>'.$this->agent_code.'</AGENT_CODE>
            <USER_ID>'.$this->ClientId.'</USER_ID>
            <PINNO>'.$PINNO.'</PINNO>
            <AGENT_SESSION_ID>'.$this->agent_session_id.'</AGENT_SESSION_ID>
            <SIGNATURE>'.$hash_signature.'</SIGNATURE>
        ';
        $soapMethod = 'Authorized_Confirmed';
        $xml_post_string = $this->xmlGenerate($xml_string, $soapMethod);
        $response = $this->connectionCheck($xml_post_string, $soapMethod);
        Log::info(json_decode(json_encode($response->Authorized_ConfirmedResponse->Authorized_ConfirmedResult), true));

        return json_decode(json_encode($response->Authorized_ConfirmedResponse->Authorized_ConfirmedResult), true);
    }

    /**
     * @param  Model|BaseModel  $order
     */
    public function requestQuote($order): mixed
    {
        return [

        ];
    }

    /**
     * Method to make a request to the remittance service provider
     * for an execution of the order.
     *
     * @throws ErrorException
     */
    public function executeOrder(BaseModel $order): mixed
    {
        return [];
    }

    /**
     * Method to make a request to the remittance service provider
     * for the progress status of the order.
     *
     * @throws ErrorException
     */
    public function orderStatus(BaseModel $order): mixed
    {
        return [];
    }

    /**
     * Method to make a request to the remittance service provider
     * for the cancellation of the order.
     *
     * @throws ErrorException
     */
    public function cancelOrder(BaseModel $order): mixed
    {
        return [];
    }

    /**
     * Method to make a request to the remittance service provider
     * for the amendment of the order.
     *
     * @throws ErrorException
     */
    public function amendmentOrder(BaseModel $order): mixed
    {
        return [];
    }

    /**
     * Method to make a request to the remittance service provider
     * for the track real-time progress of the order.
     *
     * @throws ErrorException
     */
    public function trackOrder(BaseModel $order): mixed
    {
        return [];
    }
}
