<?php

namespace Fintech\Remit\Vendors;

use Exception;
use Fintech\Remit\Contracts\BankTransfer;
use Fintech\Remit\Contracts\OrderQuotation;
use Illuminate\Support\Facades\Log;
use MongoDB\Laravel\Eloquent\Model;
use SimpleXMLElement;

class IslamiBankApi implements BankTransfer, OrderQuotation
{
    /**
     * CityBank API configuration.
     *
     * @var array
     */
    private $config;

    /**
     * CityBank API Url.
     *
     * @var string
     */
    private $apiUrl;

    private $status = 'sandbox';

    /**
     * CityBankApiService constructor.
     */
    public function __construct()
    {
        $this->config = config('fintech.remit.providers.islamibank');

        if ($this->config['mode'] === 'sandbox') {
            $this->apiUrl = $this->config[$this->status]['endpoint'];
            $this->status = 'sandbox';

        } else {
            $this->apiUrl = $this->config[$this->status]['endpoint'];
            $this->status = 'live';
        }
    }

    /**
     * Get transaction status service will help you to get the transaction status
     *
     * @param  $inputs_data
     *                      reference_no like system transaction number
     * @return mixed
     *
     * @throws Exception
     */
    public function getTnxStatus($inputs_data)
    {

    }

    /**
     * bKash customer validation service will help you to validate the beneficiary bkash number before send the transaction
     *
     * @param  $inputData
     *                    receiver_first_name like receiver name
     *                    bank_account_number like receiver bkash number or wallet number
     * @return mixed
     *
     * @throws Exception
     */
    public function bkashCustomerValidation($inputData)
    {
        $doAuthenticate = $this->doAuthenticate();
        if ($doAuthenticate != 'AUTH_FAILED' || $doAuthenticate != null) {
            $xml_string = '
                <bkash_customer_validation xsi:type="urn:bkash_customer_validation">
                    <!--You may enter the following 3 items in any order-->
                    <token xsi:type="xsd:string">'.$doAuthenticate.'</token>
                    <fullName xsi:type="xsd:string">'.$inputData['receiver_first_name'].'</fullName>
                    <mobileNumber xsi:type="xsd:string">'.$inputData['bank_account_number'].'</mobileNumber>
                </bkash_customer_validation>
            ';
            $soapMethod = 'bkashCustomerValidation';
            $response = $this->connectionCheck($xml_string, $soapMethod);
            if (isset($response) && $response != false && $response != null) {
                $returnValue = json_decode($response->bkashCustomerValidationResponse->Response, true);
            } else {
                $returnValue = ['message' => 'Transaction response Found', 'status' => 5000];
            }
        } else {
            $returnValue = ['message' => 'AUTH_FAILED INVALID USER INFORMATION', 'status' => 103];
        }

        return $returnValue;
    }

    /**
     * Do authenticate service will provide you the access token by providing following parameter value
     *
     * @return mixed
     *
     * @throws Exception
     */
    private function doAuthenticate()
    {
        $return = 'AUTH_FAILED';
        $xml_string = '
            <auth_info xsi:type="urn:auth_info">
                <username xsi:type="xsd:string">'.$this->config[$this->status]['username'].'</username>
                <password xsi:type="xsd:string">'.$this->config[$this->status]['password'].'</password>
                <exchange_company xsi:type="xsd:string">'.$this->config[$this->status]['exchange_company'].'</exchange_company>
            </auth_info>
        ';
        $soapMethod = 'doAuthenticate';
        $response = $this->connectionCheck($xml_string, $soapMethod);
        $returnValue = json_decode($response->doAuthenticateResponse->Response, true);
        if ($returnValue['message'] == 'Successful') {
            $return = $returnValue['token'];
        }

        return $return;
    }

    /**
     * @return SimpleXMLElement
     *
     * @throws Exception
     */
    private function connectionCheck($xml_post_string, $method)
    {
        $xml_string = $this->xmlGenerate($xml_post_string, $method);
        Log::info($method.'<br>'.$xml_string);
        $headers = [
            'Host: '.$this->config[$this->status]['app_host'],
            'Content-type: text/xml;charset="utf-8"',
            'Content-length: '.strlen($xml_string),
            'SOAPAction: '.$method,
        ];

        // PHP cURL  for connection
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_string); // the SOAP request
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // execution
        $response = curl_exec($ch);
        Log::error($method.' CURL reported error: ');
        if ($response === false) {
            throw new Exception(curl_error($ch), curl_errno($ch));
        }
        curl_close($ch);
        $response1 = str_replace('<SOAP-ENV:Body>', '', $response);
        $response2 = str_replace('</SOAP-ENV:Body>', '', $response1);
        $response = str_replace('xmlns:ns1="urn:dynamicapi"', '', $response2);
        $response = str_replace('ns1:', '', $response); //dd($response);
        Log::info($method.'<br>'.$response);

        return simplexml_load_string($response);
    }

    /**
     * @return string
     */
    public function xmlGenerate($string, $method)
    {
        $xml_string = '<?xml version="1.0" encoding="utf-8"?>
            <soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:urn="urn:dynamicapi">
                <soapenv:Header/>
                <soapenv:Body>
                    <urn:'.$method.' soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                        '.$string.'
                    </urn:'.$method.'>
                </soapenv:Body>
            </soapenv:Envelope>
        ';

        return $xml_string;
    }

    /**
     * bKash customer validation service will help you to validate the beneficiary bkash number before send the transaction
     *
     * @param  $inputData
     *                    bank_account_number like receiver bkash number or wallet number
     * @return mixed
     *
     * @throws Exception
     */
    public function bkashValidation($inputData)
    {
        $doAuthenticate = $this->doAuthenticate();
        if ($doAuthenticate != 'AUTH_FAILED' || $doAuthenticate != null) {
            $xml_string = '
                <bkash_customer_details xsi:type="urn:bkash_customer_validation">
                    <!--You may enter the following 3 items in any order-->
                    <token xsi:type="xsd:string">'.$doAuthenticate.'</token>
                    <mobileNumber xsi:type="xsd:string">'.$inputData['bank_account_number'].'</mobileNumber>
                </bkash_customer_details>
            ';
            $soapMethod = 'getBkashCustomerDetails';
            $response = $this->connectionCheck($xml_string, $soapMethod);
            if (isset($response) && $response != false && $response != null) {
                $returnValue = json_decode($response->getBkashCustomerDetailsResponse->Response, true);
            } else {
                $returnValue = ['message' => 'Transaction response Found', 'status' => 5000];
            }
        } else {
            $returnValue = ['message' => 'AUTH_FAILED INVALID USER INFORMATION', 'status' => 103];
        }

        return $returnValue;
    }

    /**
     * This service call will provide you the bkash transaction status.
     *
     * @param  $inputData
     *                    reference_no like system transaction number
     * @return mixed
     *
     * @throws Exception
     */
    public function getBkashTnxStatus($inputData)
    {
        $doAuthenticate = $this->doAuthenticate();
        if ($doAuthenticate != 'AUTH_FAILED' || $doAuthenticate != null) {
            $xml_string = '
                <bkash_transfer_status xsi:type="urn:bkash_transfer_status">
                    <!--You may enter the following 2 items in any order-->
                    <token xsi:type="xsd:string">'.$doAuthenticate.'</token>
                    <reference_no xsi:type="xsd:string">'.$inputData['reference_no'].'</reference_no>
                </bkash_transfer_status>
            ';
            $soapMethod = 'getBkashTransferStatus';
            $response = $this->connectionCheck($xml_string, $soapMethod);
            if (isset($response) && $response != false && $response != null) {
                $returnValue = json_decode($response->getBkashTransferStatusResponse->Response, true);
            } else {
                $returnValue = ['message' => 'Transaction response Found', 'status' => 5000];
            }
        } else {
            $returnValue = ['message' => 'AUTH_FAILED INVALID USER INFORMATION', 'status' => 103];
        }

        return $returnValue;
    }

    /**
     * @return object
     *
     * @throws Exception
     */
    public function topUp($input)
    {
        if ($input->service_id == 15) {
            $returnValue = $this->doBkashTransfer($input->transaction_json_data);
        } elseif ($input->service_id == 36) {
            $returnValue = $this->doNagadTransfer($input->transaction_json_data);
        } else {
            $returnValue = $this->doTransfer($input->transaction_json_data);
        }

        return (object) $returnValue;
    }

    /**
     * Do bKash transfer service will help you to send a bkash transaction
     *
     * @param  $input_data
     * @return mixed
     *
     * @throws Exception
     */
    public function doBkashTransfer($inputData)
    {
        $doAuthenticate = $this->doAuthenticate();
        if ($doAuthenticate != 'AUTH_FAILED' || $doAuthenticate != null) {
            $xml_string = '
                <do_bkash_transfer xsi:type="urn:do_bkash_transfer">
                    <!--You may enter the following 18 items in any order-->
                    <token xsi:type="xsd:string">'.$doAuthenticate.'</token>
                    <amount_in_bdt xsi:type="xsd:string">'.$inputData->transfer_amount.'</amount_in_bdt>
                    <reference_no xsi:type="xsd:string">'.$inputData->reference_no.'</reference_no>
                    <remitter_name xsi:type="xsd:string">'.$inputData->sender_first_name.'</remitter_name>
                    <remitter_dob xsi:type="xsd:string">'.$inputData->sender_date_of_birth.'</remitter_dob>
                    <!--Optional:-->
                    <remitter_iqama_no xsi:type="xsd:string"/>
                    <remitter_id_passport_no xsi:type="xsd:string">'.$inputData->sender_id_number.'</remitter_id_passport_no>
                    <!--Optional:-->
                    <remitter_address xsi:type="xsd:string">'.$inputData->sender_address.'</remitter_address>
                    <remitter_mobile_no xsi:type="xsd:string">'.$inputData->sender_mobile.'</remitter_mobile_no>
                    <issuing_country xsi:type="xsd:string">'.$inputData->sender_id_issue_country.'</issuing_country>
            ';
            if (isset($inputData->wallet_account_actual_name) && $inputData->wallet_account_actual_name != '') {
                $xml_string .= '
                    <beneficiary_name xsi:type="xsd:string">'.(isset($inputData->wallet_account_actual_name) ? $inputData->wallet_account_actual_name : null).'</beneficiary_name>
            ';
            } else {
                $xml_string .= '
                    <beneficiary_name xsi:type="xsd:string">'.((isset($inputData->receiver_first_name) ? $inputData->receiver_first_name : null).(isset($inputData->receiver_middle_name) ? ' '.$inputData->receiver_middle_name : null).(isset($inputData->receiver_last_name) ? ' '.$inputData->receiver_last_name : null)).'</beneficiary_name>
            ';
            }
            $xml_string .= '
                    <beneficiary_city xsi:type="xsd:string">'.(isset($inputData->receiver_city) ? $inputData->receiver_city : 'Dhaka').'</beneficiary_city>
                    <!--Optional:-->
                    <beneficiary_id_no xsi:type="xsd:string"></beneficiary_id_no>
                    <!--Optional:-->
                    <beneficiary_id_type xsi:type="xsd:string"></beneficiary_id_type>
                    <purpose_of_payment xsi:type="xsd:string">'.$inputData->purpose_of_remittance.'</purpose_of_payment>
                    <beneficiary_mobile_phone_no xsi:type="xsd:string">'.$inputData->bank_account_number.'</beneficiary_mobile_phone_no>
                    <!--Optional:-->
                    <beneficiary_address xsi:type="xsd:string">'.$inputData->receiver_address.'</beneficiary_address>
                    <issue_date xsi:type="xsd:string">'.date('Y-m-d', strtotime($inputData->created_date)).'</issue_date>
                </do_bkash_transfer>
            ';
            $soapMethod = 'doBkashTransfer';
            $response = $this->connectionCheck($xml_string, $soapMethod);
            if (isset($response) && $response != false && $response != null) {
                $returnValue = json_decode($response->doBkashTransferResponse->Response, true);
            } else {
                $returnValue = ['message' => 'Transaction response Found', 'status' => 5000];
            }
        } else {
            $returnValue = ['message' => 'AUTH_FAILED INVALID USER INFORMATION', 'status' => 103];
        }

        return $returnValue;
    }

    /**
     * Do nagad transfer service will help you to send a nagad transaction
     *
     * @param  $input_data
     * @return mixed
     *
     * @throws Exception
     */
    public function doNagadTransfer($inputData)
    {
        $doAuthenticate = $this->doAuthenticate();
        if ($doAuthenticate != 'AUTH_FAILED' || $doAuthenticate != null) {
            $xml_string = '
                <remitter_iqama_no xsi:type="urn:nagad_remit_transfer">
                    <!--You may enter the following 18 items in any order-->
                    <token xsi:type="xsd:string">'.$doAuthenticate.'</token>
                    <amount_in_bdt xsi:type="xsd:string">'.$inputData->transfer_amount.'</amount_in_bdt>
                    <reference_no xsi:type="xsd:string">'.$inputData->reference_no.'</reference_no>
                    <remitter_name xsi:type="xsd:string">'.$inputData->sender_first_name.'</remitter_name>
                    <remitter_dob xsi:type="xsd:string">'.$inputData->sender_date_of_birth.'</remitter_dob>
                    <!--Optional:-->
                    <remitter_iqama_no xsi:type="xsd:string"/></remitter_iqama_no>
                    <remitter_id_passport_no xsi:type="xsd:string">'.$inputData->sender_id_number.'</remitter_id_passport_no>
                    <!--Optional:-->
                    <remitter_address xsi:type="xsd:string">'.$inputData->sender_address.'</remitter_address>
                    <remitter_mobile_no xsi:type="xsd:string">'.$inputData->sender_mobile.'</remitter_mobile_no>
                    <issuing_country xsi:type="xsd:string">'.$inputData->sender_id_issue_country.'</issuing_country>
            ';
            if (isset($inputData->wallet_account_actual_name) && $inputData->wallet_account_actual_name != '') {
                $xml_string .= '
                    <beneficiary_name xsi:type="xsd:string">'.(isset($inputData->wallet_account_actual_name) ? $inputData->wallet_account_actual_name : null).'</beneficiary_name>
            ';
            } else {
                $xml_string .= '
                    <beneficiary_name xsi:type="xsd:string">'.((isset($inputData->receiver_first_name) ? $inputData->receiver_first_name : null).(isset($inputData->receiver_middle_name) ? ' '.$inputData->receiver_middle_name : null).(isset($inputData->receiver_last_name) ? ' '.$inputData->receiver_last_name : null)).'</beneficiary_name>
            ';
            }
            $xml_string .= '
                    <beneficiary_city xsi:type="xsd:string">'.(isset($inputData->receiver_city) ? $inputData->receiver_city : 'Dhaka').'</beneficiary_city>
                    <!--Optional:-->
                    <beneficiary_id_no xsi:type="xsd:string"></beneficiary_id_no>
                    <!--Optional:-->
                    <beneficiary_id_type xsi:type="xsd:string"></beneficiary_id_type>
                    <purpose_of_payment xsi:type="xsd:string">'.$inputData->purpose_of_remittance.'</purpose_of_payment>
                    <beneficiary_mobile_phone_no xsi:type="xsd:string">'.$inputData->bank_account_number.'</beneficiary_mobile_phone_no>
                    <!--Optional:-->
                    <beneficiary_address xsi:type="xsd:string">'.$inputData->receiver_address.'</beneficiary_address>
                    <issue_date xsi:type="xsd:string">'.date('Y-m-d', strtotime($inputData->created_date)).'</issue_date>
                    <transaction_type xsi:type="xsd:string"></transaction_type>
                </do_bkash_transfer>
            ';
            $soapMethod = 'doNagadTransfer';
            $response = $this->connectionCheck($xml_string, $soapMethod);
            if (isset($response) && $response != false && $response != null) {
                $returnValue = json_decode($response->doNagadTransferResponse->Response, true);
            } else {
                $returnValue = ['message' => 'Transaction response Found', 'status' => 5000];
            }
        } else {
            $returnValue = ['message' => 'AUTH_FAILED INVALID USER INFORMATION', 'status' => 103];
        }

        return $returnValue;
    }

    /**
     * nagad customer validation service will help you to validate the beneficiary nagad number before send the transaction
     *
     * @param  $inputData
     *                    receiver_first_name like receiver name
     *                    bank_account_number like receiver nagad number or wallet number
     * @return mixed
     *
     * @throws Exception
     */
    public function nagadCustomerValidation($inputData)
    {
        $doAuthenticate = $this->doAuthenticate();
        if ($doAuthenticate != 'AUTH_FAILED' || $doAuthenticate != null) {
            $xml_string = '
                <nagad_remitter_validation xsi:type="urn:nagad_remitter_validation">
                    <!--You may enter the following 4 items in any order-->
                    <token xsi:type="xsd:string">'.$doAuthenticate.'</token>
                    <amount xsi:type="xsd:string">50</amount>
                    <beneficiaryMobileNumber xsi:type="xsd:string">'.$inputData['bank_account_number'].'</beneficiaryMobileNumber>
                    <payMode xsi:type="xsd:string">N</payMode>
                </nagad_remitter_validation>
            ';
            $soapMethod = 'nagadCustomerValidation';
            $response = $this->connectionCheck($xml_string, $soapMethod);
            if (isset($response) && $response != false && $response != null) {
                $returnValue = json_decode($response->nagadCustomerValidationResponse->Response, true);
            } else {
                $returnValue = ['message' => 'Transaction response Found', 'status' => 5000];
            }
        } else {
            $returnValue = ['message' => 'AUTH_FAILED INVALID USER INFORMATION', 'status' => 103];
        }

        return $returnValue;
    }

    /**
     * This service call will provide you the nagad transaction status.
     *
     * @param  $inputData
     *                    txnNo like system transaction number
     * @return mixed
     *
     * @throws Exception
     */
    public function getNagadTnxStatus($inputData)
    {
        $doAuthenticate = $this->doAuthenticate();
        if ($doAuthenticate != 'AUTH_FAILED' || $doAuthenticate != null) {
            $xml_string = '
                <nagad_remit_transfer_status xsi:type="urn:nagad_remit_transfer_status">
                    <!--You may enter the following 2 items in any order-->
                    <token xsi:type="xsd:string">'.$doAuthenticate.'</token>
                    <txnNo xsi:type="xsd:string">'.$inputData['reference_no'].'</txnNo>
                </nagad_remit_transfer_status>
            ';
            $soapMethod = 'getNagadTransferStatus';
            $response = $this->connectionCheck($xml_string, $soapMethod);
            if (isset($response) && $response != false && $response != null) {
                $returnValue = json_decode($response->getNagadTransferStatusRespons->Response, true);
            } else {
                $returnValue = ['message' => 'Transaction response Found', 'status' => 5000];
            }
        } else {
            $returnValue = ['message' => 'AUTH_FAILED INVALID USER INFORMATION', 'status' => 103];
        }

        return $returnValue;
    }

    /**
     * Execute the transfer operation
     */
    public function makeTransfer(array $orderInfo = []): mixed
    {
        $doAuthenticate = $this->doAuthenticate();
        if ($doAuthenticate != 'AUTH_FAILED' || $doAuthenticate != null) {
            if ($inputData->bank_id == 17) {
                $mode_of_payment = 'CBL Account';
            } else {
                $mode_of_payment = 'Other Bank';
            }
            if ($inputData->recipient_type_name == 'Cash') {
                $mode_of_payment = 'Cash';
            }
            if ($inputData->recipient_type_name == 'Cash Pickup') {
                $mode_of_payment = 'Cash';
            }
            $xml_string = '
                <Transaction xsi:type="urn:Transaction">
                    <token xsi:type="xsd:string">'.$doAuthenticate.'</token>
                    <reference_no xsi:type="xsd:string">'.$inputData->reference_no.'</reference_no>
                    <remitter_name xsi:type="xsd:string">'.$inputData->sender_first_name.'</remitter_name>
                    <remitter_code xsi:type="xsd:string">'.$inputData->sender_mobile.'</remitter_code>
                    <remitter_iqama_no xsi:type="xsd:string"></remitter_iqama_no>
                    <remitter_id_passport_no xsi:type="xsd:string">'.$inputData->sender_id_number.'</remitter_id_passport_no>
                    <issuing_country xsi:type="xsd:string">'.$inputData->sender_id_issue_country.'</issuing_country>
                    <beneficiary_name xsi:type="xsd:string">'.((isset($inputData->receiver_first_name) ? $inputData->receiver_first_name : null).(isset($inputData->receiver_middle_name) ? ' '.$inputData->receiver_middle_name : null).(isset($inputData->receiver_last_name) ? ' '.$inputData->receiver_last_name : null)).'</beneficiary_name>
            ';
            if ($mode_of_payment != 'Cash') {
                $xml_string .= '
                        <beneficiary_account_no xsi:type="xsd:string">'.$inputData->bank_account_number.'</beneficiary_account_no>
                        <beneficiary_bank_account_type xsi:type="xsd:string">Savings</beneficiary_bank_account_type>
                        <beneficiary_bank_name xsi:type="xsd:string">'.$inputData->bank_name.'</beneficiary_bank_name>
                        <beneficiary_bank_branch_name xsi:type="xsd:string">'.$inputData->bank_branch_name.'</beneficiary_bank_branch_name>
                        <branch_routing_number xsi:type="xsd:string">'.(isset($inputData->location_routing_id[1]->bank_branch_location_field_value) ? $inputData->location_routing_id[1]->bank_branch_location_field_value : null).'</branch_routing_number>
                ';
            }
            $xml_string .= '
                    <amount_in_taka xsi:type="xsd:string">'.$inputData->transfer_amount.'</amount_in_taka>
                    <purpose_of_payment xsi:type="xsd:string">'.$inputData->purpose_of_remittance.'</purpose_of_payment>
                    <beneficiary_mobile_phone_no xsi:type="xsd:string">'.$inputData->receiver_contact_number.'</beneficiary_mobile_phone_no>
                    <beneficiary_id_type xsi:type="xsd:string"></beneficiary_id_type>
                    <pin_no xsi:type="xsd:string"></pin_no>
                    <remitter_address xsi:type="xsd:string">'.$inputData->sender_address.'</remitter_address>
                    <remitter_mobile_no xsi:type="xsd:string">'.$inputData->sender_mobile.'</remitter_mobile_no>
                    <beneficiary_address xsi:type="xsd:string">'.$inputData->receiver_address.'</beneficiary_address>
                    <beneficiary_id_no xsi:type="xsd:string"></beneficiary_id_no>
                    <special_instruction xsi:type="xsd:string">NA</special_instruction>
                    <mode_of_payment xsi:type="xsd:string">'.$mode_of_payment.'</mode_of_payment>
                    <issue_date xsi:type="xsd:string">'.date('Y-m-d', strtotime($inputData->created_date)).'</issue_date>
                    <!--Optional:-->
                    <custom_field_name_1 xsi:type="xsd:string">?</custom_field_name_1>
                    <custom_field_value_1 xsi:type="xsd:string">?</custom_field_value_1>
                    <custom_field_name_2 xsi:type="xsd:string">?</custom_field_name_2>
                    <custom_field_value_2 xsi:type="xsd:string">?</custom_field_value_2>
                    <custom_field_name_3 xsi:type="xsd:string">?</custom_field_name_3>
                    <custom_field_value_3 xsi:type="xsd:string">?</custom_field_value_3>
                    <custom_field_name_4 xsi:type="xsd:string">?</custom_field_name_4>
                    <custom_field_value_4 xsi:type="xsd:string">?</custom_field_value_4>
                    <custom_field_name_5 xsi:type="xsd:string">?</custom_field_name_5>
                    <custom_field_value_5 xsi:type="xsd:string">?</custom_field_value_5>
                    <custom_field_name_6 xsi:type="xsd:string">?</custom_field_name_6>
                    <custom_field_value_6 xsi:type="xsd:string">?</custom_field_value_6>
                    <custom_field_name_7 xsi:type="xsd:string">?</custom_field_name_7>
                    <custom_field_value_7 xsi:type="xsd:string">?</custom_field_value_7>
                    <custom_field_name_8 xsi:type="xsd:string">?</custom_field_name_8>
                    <custom_field_value_8 xsi:type="xsd:string">?</custom_field_value_8>
                    <custom_field_name_9 xsi:type="xsd:string">?</custom_field_name_9>
                    <custom_field_value_9 xsi:type="xsd:string">?</custom_field_value_9>
                    <custom_field_name_10 xsi:type="xsd:string">?</custom_field_name_10>
                    <custom_field_value_10 xsi:type="xsd:string">?</custom_field_value_10>
                </Transaction>
            ';
            $soapMethod = 'doTransfer';
            $response = $this->connectionCheck($xml_string, $soapMethod);
            if (isset($response) && $response != false && $response != null) {
                $returnValue = json_decode($response->doTransferResponse->Response, true);
            } else {
                $returnValue = ['message' => 'Transaction response Found', 'status' => 5000];
            }
        } else {
            $returnValue = ['message' => 'AUTH_FAILED INVALID USER INFORMATION', 'status' => 103];
        }

        return $returnValue;
    }

    public function transferStatus(array $orderInfo = []): mixed
    {
        $doAuthenticate = $this->doAuthenticate();
        if ($doAuthenticate != 'AUTH_FAILED' || $doAuthenticate != null) {
            $xml_string = '
                <transaction_status xsi:type="urn:transaction_status">
                    <!--You may enter the following 2 items in any order-->
                    <token xsi:type="xsd:string">'.$doAuthenticate.'</token>
                    <reference_no xsi:type="xsd:string">'.$inputs_data['reference_no'].'</reference_no>
                </transaction_status>
            ';
            $soapMethod = 'getTnxStatus';
            $response = $this->connectionCheck($xml_string, $soapMethod);
            if (isset($response) && $response != false && $response != null) {
                $returnValue = json_decode($response->getTnxStatusResponse->Response, true);
            } else {
                $returnValue = ['message' => 'Transaction response Found', 'status' => 5000];
            }
        } else {
            $returnValue = ['message' => 'AUTH_FAILED INVALID USER INFORMATION', 'status' => 103];
        }

        return $returnValue;
    }

    /**
     * Do amendment or cancel service will help you to send the transaction cancel/amendment request
     * reference_no like system transaction number, amend_query like cancel/amendment
     *
     * @throws Exception
     */
    public function cancelTransfer(array $orderInfo = []): mixed
    {
        $doAuthenticate = $this->doAuthenticate();
        if ($doAuthenticate != 'AUTH_FAILED' || $doAuthenticate != null) {
            $xml_string = '
                <txn_amend_cancel xsi:type="urn:txn_amend_cancel">
                    <!--You may enter the following 3 items in any order-->
                    <token xsi:type="xsd:string">'.$doAuthenticate.'</token>
                    <reference_no xsi:type="xsd:string">'.$inputData['reference_no'].'</reference_no>
                    <amend_query xsi:type="xsd:string">'.$inputData['amend_query'].'</amend_query>
                </txn_amend_cancel>
            ';
            $soapMethod = 'doAmendmentOrCancel';
            $response = $this->connectionCheck($xml_string, $soapMethod);
            if (isset($response) && $response != false && $response != null) {
                $returnValue = json_decode($response->doAmendmentOrCancelResponse->Response, true);
            } else {
                $returnValue = ['message' => 'Transaction response Found', 'status' => 5000];
            }
        } else {
            $returnValue = ['message' => 'AUTH_FAILED INVALID USER INFORMATION', 'status' => 103];
        }

        return $returnValue;
    }

    public function verifyAccount(array $accountInfo = []): mixed
    {
        // TODO: Implement verifyAccount() method.
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model|Model  $order
     *
     * @throws Exception
     */
    public function requestQuotation($order): mixed
    {
        return $this->vendorBalance();
    }

    public function vendorBalance(array $accountInfo = []): mixed
    {
        $doAuthenticate = $this->doAuthenticate();
        if ($doAuthenticate != 'AUTH_FAILED' || $doAuthenticate != null) {
            $xml_string = '
                <get_balance xsi:type="urn:get_balance">
                    <token xsi:type="xsd:string">'.$doAuthenticate.'</token>
                </get_balance>
            ';
            $soapMethod = 'getBalance';
            $response = $this->connectionCheck($xml_string, $soapMethod);
            if (isset($response) && $response != false && $response != null) {
                $returnValue = json_decode($response->getBalanceResponse->Response, true);
            } else {
                $returnValue = ['message' => 'Transaction response Found', 'status' => 5000];
            }
        } else {
            $returnValue = ['message' => 'AUTH_FAILED INVALID USER INFORMATION', 'status' => 103];
        }

        return $returnValue;
    }

    /**
     * Response Code List
     * These codes will return in all operations.
     *
     * @param int $code
     * @return string[]
     */
    private function __responseCodeList(int $code): array
    {
        return [
            1000 => 'ERROR OTHERS',
            1001 => 'TRANSACTION REF INVALID',
            1002 => 'AMOUNT INVALID',
            1003 => 'ISO CODE INVALID',
            1004 => 'SWIFT CODE INVALID',
            1005 => 'NOTE INVALID',
            1006 => 'SECRET KEY INVALID',
            1007 => 'PAYMENT TYPE INVALID',
            1008 => 'IDENTITY TYPE INVALID',
            1009 => 'IDENTITY DESCRIPTION INVALID',
            1010 => 'EXCHANGE BR CODE INVALID',
            1011 => 'ISSUE DATE INVALID',
            1101 => 'TRANSACTION REF MISSING',
            1102 => 'AMOUNT MISSING',
            1103 => 'CURRENCY MISSING',
            1104 => 'SWIFT CODE MISSING',
            1105 => 'NOTE MISSING',
            1106 => 'SECRET KEY MISSING',
            1107 => 'PAYMENT TYPE MISSING',
            1108 => 'IDENTITY TYPE MISSING',
            1109 => 'IDENTITY DESCRIPTION MISSING',
            1110 => 'EXCHANGE BR CODE MISSING',
            1111 => 'ISSUE DATE MISSING',
            1201 => 'BENEFICIARY ACC NO NOT APPLICABLE',
            1202 => 'BENEFICIARY ROUTING NO NOT APPLICABLE',
            1301 => 'BENEFICIARY ACC NO NOT FOUND',
            2001 => 'REMITTER NAME INVALID',
            2002 => 'REMITTER IDENTIFICATION NO INVALID',
            2003 => 'REMITTER PHONE NO INVALID',
            2004 => 'REMITTER ADDRESS INVALID',
            2101 => 'REMITTER NAME MISSING',
            2102 => 'REMITTER IDENTIFICATION NO MISSING',
            2103 => 'REMITTER PHONE NO MISSING',
            2104 => 'REMITTER ADDRESS MISSING',
            3001 => 'BENEFICIARY NAME INVALID',
            3002 => 'BENEFICIARY PASSPORT INVALID',
            3003 => 'BENEFICIARY PHONE INVALID',
            3004 => 'BENEFICIARY ADDRESS INVALID',
            3005 => 'BENEFICIARY ACC NO INVALID',
            3006 => 'BENEFICIARY ACC TYPE INVALID',
            3007 => 'BENEFICIARY BANK CODE INVALID',
            3008 => 'BENEFICIARY BANK NAME INVALID',
            3009 => 'BENEFICIARY BRANCH CODE INVALID',
            3010 => 'BENEFICIARY BRANCH NAME INVALID',
            3011 => 'BENEFICIARY ROUTING NO INVALID',
            3101 => 'BENEFICIARY NAME MISSING',
            3102 => 'BENEFICIARY PASSPORT MISSING',
            3103 => 'BENEFICIARY PHONE MISSING',
            3104 => 'BENEFICIARY ADDRESS MISSING',
            3105 => 'BENEFICIARY ACC NO MISSING',
            3106 => 'BENEFICIARY ACC TYPE MISSING',
            3107 => 'BENEFICIARY BANK CODE MISSING',
            3108 => 'BENEFICIARY BANK NAME MISSING',
            3109 => 'BENEFICIARY BRANCH CODE MISSING',
            3110 => 'BENEFICIARY BRANCH NAME MISSING',
            3111 => 'BENEFICIARY ROUTING NO MISSING',
            3112 => 'BENEFICIARY CARD NO MISSING',
            3113 => 'BENEFICIARY WALLET ACC NO MISSING',
            3114 => 'BENEFICIARY ACC NO LENGTH INVALID',
            5001 => 'REMITTANCE ALREADY IMPORTED',
            5002 => 'REMITTANCE VERIFIED SUCCESSFULLY',
            5003 => 'REMITTANCE SUCCESS',
            5004 => 'REMITTANCE FAILED',
            5005 => 'REMITTANCE SKIPPED',
            5006 => 'REMITTANCE NOT_FOUND',
            5007 => 'REMITTANCE IS ENQUEUED',
            6001 => 'INSUFFICIENT BALANCE',
            6002 => 'ACCOUNT NAME AND ACCOUNT NO. DIFFER',
            6003 => 'FIELD LENGTH INVALID',
            6004 => 'ACCOUNT NO. NOT FOUND',
            7001 => 'USER NAME OR PASSWORD IS MISSING',
            7002 => 'USER NAME OR PASSWORD IS INVALID',
            7003 => 'USER IS BLOCKED',
            7004 => 'USER IS INACTIVE',
            7005 => 'USER IS DEAD (PERMANENTLY BLOCKED)',
        ];
    }

    /**
     * Response Status Code List
     * These codes will return in only Fetch Remittance Status (fetchWSMessageStatus) operation.
     *
     * @param int $code
     * @return string[]
     */
    private function __responseStatusCodeList(int $code): array
    {
        return [
            01 => 'REMITTANCE ISSUED',
            02 => 'REMITTANCE TRANSFERRED/AUTHORIZED BY EXCHANGE HOUSE',
            03 => 'REMITTANCE READY FOR PAYMENT',
            04 => 'REMITTANCE UNDER PROCESS',
            05 => 'REMITTANCE STOPPED',
            06 => 'REMITTANCE STOPPED BY EXCHANGE HOUSE',
            07 => 'REMITTANCE PAID08 REMITTANCE AMENDED',
            11 => 'REMITTANCE CANCELLED',
            17 => 'REMITTANCE REVERSED',
            20 => 'REMITTANCE CANCEL REQUEST',
            30 => 'REMITTANCE AMENDMENT REQUEST',
            70 => 'REMITTANCE CBS UNDER PROCESS',
            73 => 'REMITTANCE CBS AUTHORIZED',
            74 => 'REMITTANCE CBS PENDING',
            77 => 'REMITTANCE CBS NRT ACCOUNT DEBITED',
            78 => 'REMITTANCE CBS READY FOR PAYMENT',
            79 => 'REMITTANCE CBS CREDITED TO ACCOUNT',
            80 => 'REMITTANCE CBS UNKNOWN STATE',
            82 => 'CBS ACC PAYEE TITLE AND ACCOUNT NO DIFFER',
            83 => 'CBS EFT INVALID ACCOUNT',
            84 => 'CBS EFT SENT TO THIRD BANK',
            99 => 'REMITTANCE INVALID STATUS',
        ];
    }
}
