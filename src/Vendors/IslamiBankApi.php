<?php

namespace Fintech\Remit\Vendors;

use Exception;
use Fintech\Core\Supports\Utility;
use Fintech\Remit\Contracts\OrderInquiry;
use Fintech\Remit\Contracts\OrderQuotation;
use Fintech\Remit\Contracts\ProceedOrder;
use Illuminate\Support\Facades\Http;

class IslamiBankApi implements OrderInquiry, OrderQuotation, ProceedOrder
{
    /**
     * IslamiBank API configuration.
     *
     * @var array
     */
    private mixed $config;

    /**
     * IslamiBank API Url.
     *
     * @var string
     */
    private mixed $apiUrl;

    private string $status = 'sandbox';

    /**
     * IslamiBankApiService constructor.
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
     * Fetch Account Details (fetchAccountDetail)
     *
     * Fetching account details of beneficiary (receiver) by which you will get the
     * full digit (17 digit) account no and account title (Beneficiary Name) which
     * is required to send when you will execute directCreditWSMessage
     * operation.
     *
     * Parameters: userID, password, account_number, account_type, branch_code
     *
     * @throws Exception
     */
    public function fetchAccountDetail(array $data): array
    {
        $accountDetail = $this->__transferData($data);
        $xmlString = '
            <ser:userID>'.$this->config[$this->status]['username'].'</ser:userID>
            <ser:password>'.$this->config[$this->status]['password'].'</ser:password>
        ';
        $xmlString .= '<ser:accNo>'.($accountDetail['beneficiaryAccNo'] ?? null).'</ser:accNo>';
        $xmlString .= '<ser:accType>'.($accountDetail['beneficiaryAccType'] ?? null).'</ser:accType>';
        $xmlString .= '<ser:branchCode>'.($accountDetail['beneficiaryBankCode'] ?? null).'</ser:branchCode>';
        $soapMethod = 'fetchAccountDetail';
        $response = $this->connectionCheck($xmlString, $soapMethod);

        $explodeValue = explode('|', $response['Envelope']['Body']);
        $explodeValueCount = count($explodeValue) - 1;
        $return['origin_response'] = $response['Envelope']['Body'];
        if ($explodeValueCount > 0) {
            $return['status'] = $explodeValue[0];
            if ($explodeValue[0] == 'FALSE') {
                $return['status_code'] = $explodeValue[$explodeValueCount];
                $return['message'] = $this->__responseCodeList($explodeValue[$explodeValueCount]);
            } else {
                $return['account_number'] = $explodeValue[1];
                $return['account_title'] = $explodeValue[2];
                if ($data['branch_code'] != 358) {
                    $return['account_holder_father_name'] = $explodeValue[3];
                }
            }
        }

        return $return;
    }

    /**
     * @throws Exception
     */
    private function connectionCheck($xml_post_string, $method): array
    {
        $xml_string = $this->xmlGenerate($xml_post_string, $method);

        $response = Http::soap($this->apiUrl, $method, $xml_string);

        //        $headers = [
        //            'Host: '.parse_url($this->apiUrl, PHP_URL_HOST),
        //            'Content-type: text/xml;charset="utf-8"',
        //            'Content-length: '.strlen($xml_string),
        //            'SOAPAction: '.$method,
        //        ];
        //
        //        // PHP cURL  for connection
        //        $ch = curl_init();
        //        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        //        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        //        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        //        curl_setopt($ch, CURLOPT_TIMEOUT, 0);
        //        curl_setopt($ch, CURLOPT_POST, true);
        //        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_string); // the SOAP request
        //        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        //        // execution
        //        $response = curl_exec($ch);
        //        Log::error($method.' CURL reported error: ');
        //        if ($response === false) {
        //            throw new Exception(curl_error($ch), curl_errno($ch));
        //        }
        //        curl_close($ch);
        //        Log::info('Raw Response'.PHP_EOL.$response);
        //        //        $response1 = str_replace('<SOAP-ENV:Body>', '', $response);
        //        //        $response2 = str_replace('</SOAP-ENV:Body>', '', $response1);
        //        //        $response = str_replace('xmlns:ns="http://service.ws.mt.ibbl"', '', $response2);
        //        //        $response = str_replace('ns:', '', $response); //dd($response);
        //        //        Log::info($method . '<br>' . $response);
        //
        //        return simplexml_load_string($response);
        return Utility::parseXml($response->body());
    }

    public function xmlGenerate($string, $method): string
    {
        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
    <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://service.ws.mt.ibbl" xmlns:xsd="http://bean.ws.mt.ibbl/xsd">
        <soapenv:Header/>
        <soapenv:Body>
            <ser:{$method}>
                {$string}
            </ser:{$method}>
        </soapenv:Body>
    </soapenv:Envelope>
XML;
    }

    /**
     * Response Code List
     * These codes will return in all operations.
     */
    private function __responseCodeList(int|string $code): string
    {
        $return = [
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

        return $return[$code];
    }

    /**
     * Direct Credit Remittance (directCreditWSMessage)
     *
     * Direct Credit Remittance : In case of Account payee, you can instantly credit to beneficiary account
     * then transaction will be :
     * Debit: Exchange House Account
     * Credit: Beneficiary (receiver) account
     * In case of instant cash, you can also directly debit your account and will be available for any branch payment:
     * Debit: Exchange House Account
     * Credit: Available for any branch payment.
     *
     * Parameters: userID, password, accNo, wsMessage
     *
     * @reference directCreditRemittance
     *
     * @throws Exception
     */
    public function directCreditRemittance(array $data): array
    {
        $directCreditRemittance = $this->__transferData($data);
        $xmlString = '
            <ser:userID>'.$this->config[$this->status]['username'].'</ser:userID>
            <ser:password>'.$this->config[$this->status]['password'].'</ser:password>
        ';
        $xmlString .= '<ser:wsMessage>';
        $xmlString .= '
            <xsd:additionalField1>'.$directCreditRemittance['additionalField1'].'</xsd:additionalField1>
            <xsd:additionalField2>'.$directCreditRemittance['additionalField2'].'</xsd:additionalField2>
            <xsd:additionalField3>'.$directCreditRemittance['additionalField3'].'</xsd:additionalField3>
            <xsd:additionalField4>'.$directCreditRemittance['additionalField4'].'</xsd:additionalField4>
            <xsd:additionalField5>'.$directCreditRemittance['additionalField5'].'</xsd:additionalField5>
            <xsd:additionalField6>'.$directCreditRemittance['additionalField6'].'</xsd:additionalField6>
            <xsd:additionalField7>'.$directCreditRemittance['additionalField7'].'</xsd:additionalField7>
            <xsd:additionalField8>'.$directCreditRemittance['additionalField8'].'</xsd:additionalField8>
            <xsd:additionalField9>'.$directCreditRemittance['additionalField9'].'</xsd:additionalField9>
        ';
        $xmlString .= '
            <xsd:amount>'.$directCreditRemittance['amount'].'</xsd:amount>
        ';
        $xmlString .= '
            <xsd:batchID>'.$directCreditRemittance['batchID'].'</xsd:batchID>
        ';
        $xmlString .= '
            <xsd:beneficiaryAccNo>'.$directCreditRemittance['beneficiaryAccNo'].'</xsd:beneficiaryAccNo>
        ';
        $xmlString .= '
            <xsd:beneficiaryAccType>'.$directCreditRemittance['beneficiaryAccType'].'</xsd:beneficiaryAccType>
        ';
        $xmlString .= '
            <xsd:beneficiaryAddress>'.$directCreditRemittance['beneficiaryAddress'].'</xsd:beneficiaryAddress>
        ';
        $xmlString .= '
            <xsd:beneficiaryBankCode>'.$directCreditRemittance['beneficiaryBankCode'].'</xsd:beneficiaryBankCode>
        ';
        $xmlString .= '
            <xsd:beneficiaryBankName>'.$directCreditRemittance['beneficiaryBankName'].'</xsd:beneficiaryBankName>
        ';
        $xmlString .= '
            <xsd:beneficiaryBranchCode>'.$directCreditRemittance['beneficiaryBranchCode'].'</xsd:beneficiaryBranchCode>
        ';
        $xmlString .= '
            <xsd:beneficiaryBranchName>'.$directCreditRemittance['beneficiaryBranchName'].'</xsd:beneficiaryBranchName>
        ';
        $xmlString .= '
            <xsd:beneficiaryName>'.$directCreditRemittance['beneficiaryName'].'</xsd:beneficiaryName>
        ';
        $xmlString .= '
            <xsd:beneficiaryPassportNo>'.$directCreditRemittance['beneficiaryPassportNo'].'</xsd:beneficiaryPassportNo>
        ';
        $xmlString .= '
            <xsd:beneficiaryPhoneNo>'.$directCreditRemittance['beneficiaryPhoneNo'].'</xsd:beneficiaryPhoneNo>
        ';
        $xmlString .= '
            <xsd:beneficiaryRoutingNo>'.$directCreditRemittance['beneficiaryRoutingNo'].'</xsd:beneficiaryRoutingNo>
        ';
        $xmlString .= '
            <xsd:exHouseTxID>'.$directCreditRemittance['exHouseTxID'].'</xsd:exHouseTxID>
        ';
        $xmlString .= '
            <xsd:exchHouseBranchCode>'.$directCreditRemittance['exchHouseBranchCode'].'</xsd:exchHouseBranchCode>
        ';
        $xmlString .= '
            <xsd:exchHouseSwiftCode>'.$directCreditRemittance['exchHouseSwiftCode'].'</xsd:exchHouseSwiftCode>
        ';
        $xmlString .= '
            <xsd:identityDescription>'.$directCreditRemittance['identityDescription'].'</xsd:identityDescription>
        ';
        $xmlString .= '
            <xsd:identityType>'.$directCreditRemittance['identityType'].'</xsd:identityType>
        ';
        $xmlString .= '
            <xsd:isoCode>'.($directCreditRemittance['isoCode'] ?? null).'</xsd:isoCode>
        ';
        $xmlString .= '
            <xsd:issueDate>'.$directCreditRemittance['issueDate'].'</xsd:issueDate>
        ';
        $xmlString .= '
            <xsd:note>'.$directCreditRemittance['note'].'</xsd:note>
        ';
        $xmlString .= '
            <xsd:orderNo>'.$directCreditRemittance['orderNo'].'</xsd:orderNo>
        ';
        $xmlString .= '
            <xsd:paymentType>'.$directCreditRemittance['paymentType'].'</xsd:paymentType>
        ';
        $xmlString .= '
            <xsd:remittancePurpose>'.$directCreditRemittance['remittancePurpose'].'</xsd:remittancePurpose>
        ';
        $xmlString .= '
            <xsd:remitterAddress>'.$directCreditRemittance['remitterAddress'].'</xsd:remitterAddress>
        ';
        $xmlString .= '
            <xsd:remitterCountry>'.$directCreditRemittance['remitterCountry'].'</xsd:remitterCountry>
        ';
        $xmlString .= '
            <xsd:remitterIdentificationNo>'.$directCreditRemittance['remitterIdentificationNo'].'</xsd:remitterIdentificationNo>
        ';
        $xmlString .= '
            <xsd:remitterName>'.$directCreditRemittance['remitterName'].'</xsd:remitterName>
        ';
        $xmlString .= '
            <xsd:remitterPassportNo>'.$directCreditRemittance['remitterPassportNo'].'</xsd:remitterPassportNo>
        ';
        $xmlString .= '
            <xsd:remitterPhoneNo>'.$directCreditRemittance['remitterPhoneNo'].'</xsd:remitterPhoneNo>
        ';
        $xmlString .= '
            <xsd:secretKey>'.($directCreditRemittance['secretKey'] ?? null).'</xsd:secretKey>
        ';
        $xmlString .= '
            <xsd:transReferenceNo>'.($directCreditRemittance['transReferenceNo'] ?? null).'</xsd:transReferenceNo>
        ';
        $xmlString .= '</ser:wsMessage>';
        $soapMethod = 'directCreditWSMessage';
        $response = $this->connectionCheck($xmlString, $soapMethod);

        $explodeValue = explode('|', $response['Envelope']['Body']);
        $explodeValueCount = count($explodeValue) - 1;
        $return['origin_response'] = $response['Envelope']['Body'];
        if ($explodeValueCount > 0) {
            $return['status'] = $explodeValue[0];
            $return['status_code'] = $explodeValue[$explodeValueCount];
            $return['message'] = $this->__responseCodeList($explodeValue[$explodeValueCount]);
        }

        return $return;
    }

    private function __transferData(array $data): array
    {
        $transferData['additionalField1'] = '?';
        $transferData['additionalField2'] = '?';
        $transferData['additionalField3'] = '?';
        $transferData['additionalField4'] = '?';
        $transferData['additionalField5'] = '?';
        $transferData['additionalField6'] = '?';
        $transferData['additionalField7'] = '?';
        $transferData['additionalField8'] = '?';
        $transferData['additionalField9'] = '?';
        $transferData['amount'] = ($data['sending_amount'] ?? null);
        $transferData['batchID'] = '?';
        $transferData['beneficiaryAccNo'] = ($data['beneficiary_data']['receiver_information']['beneficiary_data']['bank_account_number'] ?? $data['beneficiary_data']['receiver_information']['beneficiary_data']['wallet_account_number'] ?? null);
        $transferData['beneficiaryAccType'] = '';
        $transferData['beneficiaryAddress'] = ($data['beneficiary_data']['receiver_information']['city_name'] ?? null).','.($data['beneficiary_data']['receiver_information']['country_name'] ?? null);
        $transferData['beneficiaryBankCode'] = ($data['beneficiary_data']['bank_information']['vendor_code']['remit']['islamibank'] ?? null);
        $transferData['beneficiaryBankName'] = ($data['beneficiary_data']['bank_information']['bank_name'] ?? null);
        $transferData['beneficiaryBranchCode'] = '';
        $transferData['beneficiaryBranchName'] = ($data['beneficiary_data']['branch_information']['branch_name'] ?? null);
        $transferData['beneficiaryName'] = ($data['beneficiary_data']['receiver_information']['beneficiary_name'] ?? null);
        $transferData['beneficiaryPassportNo'] = '?';
        $transferData['beneficiaryPhoneNo'] = ($data['beneficiary_data']['receiver_information']['beneficiary_mobile'] ?? null);
        $transferData['beneficiaryRoutingNo'] = ($data['beneficiary_data']['branch_information']['branch_data']['location_no'] ?? '?');
        $transferData['exHouseTxID'] = '?';
        $transferData['exchHouseBranchCode'] = '?';
        $transferData['exchHouseSwiftCode'] = '?';
        $transferData['identityDescription'] = '?';
        $transferData['identityType'] = ($data['beneficiary_data']['sender_information']['profile']['id_doc']['id_vendor']['remit']['islami_bank'] ?? null);
        $transferData['isoCode'] = ($data['sending_currency'] ?? null);
        $transferData['issueDate'] = (date('Y-m-d', strtotime($data['created_at'])) ?? null);
        $transferData['note'] = '?';
        $transferData['orderNo'] = '?';
        $transferData['paymentType'] = 3;
        $transferData['remittancePurpose'] = ($data['beneficiary_data']['sender_information']['profile']['remittance_purpose']['name'] ?? '?');
        $transferData['remitterAddress'] = ($data['beneficiary_data']['sender_information']['profile']['present_address']['city_name'] ?? null);
        $transferData['remitterCountry'] = ($data['beneficiary_data']['sender_information']['profile']['present_address']['country_name'] ?? null);
        $transferData['remitterIdentificationNo'] = '?';
        $transferData['remitterName'] = ($data['beneficiary_data']['sender_information']['name'] ?? null);
        $transferData['remitterPassportNo'] = '?';
        $transferData['remitterPhoneNo'] = ($data['beneficiary_data']['sender_information']['mobile'] ?? null);
        $transferData['secretKey'] = ($data['beneficiary_data']['reference_no'] ?? null);
        $transferData['transReferenceNo'] = ($data['beneficiary_data']['reference_no'] ?? null);

        switch ($data['service_slug']) {
            case 'mbs_m_cash':
                $transferData['paymentType'] = 5;
                $transferData['beneficiaryRoutingNo'] = '?';
                $transferData['beneficiaryAccNo'] = ($data['beneficiary_data']['receiver_information']['beneficiary_data']['wallet_account_number'] ?? null);
                //$transferData['beneficiaryBankCode'] = ($data['beneficiary_data']['wallet_information']['vendor_code']['remit']['islamibank'] ?? '42');
                $transferData['beneficiaryBankCode'] = '42';
                //$transferData['beneficiaryBankName'] = ($data['beneficiary_data']['wallet_information']['bank_name'] ?? 'ISLAMI BANK BANGLADESH LIMITED');
                $transferData['beneficiaryBankName'] = 'ISLAMI BANK BANGLADESH LIMITED';
                $transferData['beneficiaryBranchCode'] = '358';
                $transferData['beneficiaryBranchName'] = ($data['beneficiary_data']['branch_information']['branch_name'] ?? 'Head Office Complex');
                break;
            case 'mfs_bkash':
                $transferData['paymentType'] = 7;
                $transferData['beneficiaryRoutingNo'] = '?';
                $transferData['beneficiaryAccNo'] = ($data['beneficiary_data']['receiver_information']['beneficiary_data']['wallet_account_number'] ?? null);
                //$transferData['beneficiaryBankCode'] = ($data['beneficiary_data']['wallet_information']['vendor_code']['remit']['islamibank'] ?? '42');
                $transferData['beneficiaryBankCode'] = '42';
                //$transferData['beneficiaryBankName'] = ($data['beneficiary_data']['wallet_information']['bank_name'] ?? 'ISLAMI BANK BANGLADESH LIMITED');
                $transferData['beneficiaryBankName'] = 'ISLAMI BANK BANGLADESH LIMITED';
                $transferData['beneficiaryBranchCode'] = '358';
                $transferData['beneficiaryBranchName'] = ($data['beneficiary_data']['branch_information']['branch_name'] ?? 'Head Office Complex');
                break;
            case 'mfs_nagad':
                $transferData['paymentType'] = 8;
                $transferData['beneficiaryRoutingNo'] = '?';
                $transferData['beneficiaryAccNo'] = ($data['beneficiary_data']['receiver_information']['beneficiary_data']['wallet_account_number'] ?? null);
                //$transferData['beneficiaryBankCode'] = ($data['beneficiary_data']['wallet_information']['vendor_code']['remit']['islamibank'] ?? '42');
                $transferData['beneficiaryBankCode'] = '42';
                //$transferData['beneficiaryBankName'] = ($data['beneficiary_data']['wallet_information']['bank_name'] ?? 'ISLAMI BANK BANGLADESH LIMITED');
                $transferData['beneficiaryBankName'] = 'ISLAMI BANK BANGLADESH LIMITED';
                $transferData['beneficiaryBranchCode'] = '358';
                $transferData['beneficiaryBranchName'] = ($data['beneficiary_data']['branch_information']['branch_name'] ?? 'Head Office Complex');
                break;
            case 'remittance_card':
                $transferData['paymentType'] = 4;
                $transferData['beneficiaryRoutingNo'] = '?';
                $transferData['beneficiaryAccType'] = ($data['beneficiary_data']['beneficiary_acc_type'] ?? 71);
                break;
            case 'cash_pickup':
                $transferData['beneficiaryAccNo'] = '';
                $transferData['paymentType'] = 1;
                $transferData['beneficiaryRoutingNo'] = '?';
                $transferData['beneficiaryBankCode'] = ($data['beneficiary_data']['cash_information']['vendor_code']['remit']['islamibank'] ?? null);
                $transferData['beneficiaryBankName'] = ($data['beneficiary_data']['cash_information']['bank_name'] ?? null);
                $transferData['beneficiaryBranchCode'] = ($data['beneficiary_data']['branch_information']['vendor_code']['remit']['islamibank'] ?? 123);
                $transferData['beneficiaryBranchName'] = ($data['beneficiary_data']['branch_information']['branch_name'] ?? 'Head Office Complex');
                break;
            case 'bank_transfer':
                if ($data['beneficiary_data']['bank_information']['bank_slug'] == 'islami-bank-bangladesh-limited') {
                    $transferData['beneficiaryAccType'] = ($data['beneficiary_data']['beneficiary_acc_type'] ?? 10);
                    $transferData['beneficiaryBranchCode'] = ($data['beneficiary_data']['branch_information']['vendor_code']['remit']['islamibank'] ?? null);
                    $transferData['beneficiaryRoutingNo'] = '?';
                    $transferData['paymentType'] = 2;
                }
                break;
            case 'instant_bank_transfer':
                $transferData['beneficiaryAccType'] = ($data['beneficiary_data']['beneficiary_acc_type'] ?? 10);
                $transferData['beneficiaryBranchCode'] = ($data['beneficiary_data']['branch_information']['vendor_code']['remit']['islamibank'] ?? null);
                $transferData['beneficiaryRoutingNo'] = '?';
                $transferData['paymentType'] = 1;
                break;
            default:
                //code block
        }
        /*if ($data['service_slug'] == 'mbs_m_cash') {
            $transferData['paymentType'] = 5;
            $transferData['beneficiaryRoutingNo'] = '?';
        } elseif ($data['service_slug'] == 'mfs_bkash') {
            $transferData['paymentType'] = 7;
            $transferData['beneficiaryRoutingNo'] = '?';
        } elseif ($data['service_slug'] == 'mfs_nagad') {
            $transferData['paymentType'] = 8;
            $transferData['beneficiaryRoutingNo'] = '?';
        } elseif ($data['service_slug'] == 'remittance_card') {
            $transferData['paymentType'] = 4;
            $transferData['beneficiaryRoutingNo'] = '?';
            $transferData['beneficiaryAccType'] = ($data['beneficiary_data']['beneficiary_acc_type'] ?? 71);
        } elseif ($data['service_slug'] == 'cash_pickup') {
            $transferData['beneficiaryAccNo'] = '';
            $transferData['paymentType'] = 1;
            $transferData['beneficiaryRoutingNo'] = '?';
        } elseif ($data['beneficiary_data']['bank_information']['bank_slug'] == 'islami-bank-bangladesh-limited') {
            $transferData['beneficiaryAccType'] = ($data['beneficiary_data']['beneficiary_acc_type'] ?? null);
            $transferData['beneficiaryBranchCode'] = ($data['beneficiary_data']['branch_information']['vendor_code']['remit'] ['islamibank'] ?? null);
            $transferData['beneficiaryRoutingNo'] = '?';
            $transferData['paymentType'] = 2;
        }*/

        if ($data['beneficiary_data']['sender_information']['profile']['id_doc']['id_type'] == 'passport') {
            $transferData['remitterPassportNo'] = ($data['beneficiary_data']['sender_information']['profile']['id_doc']['id_no'] ?? null);
        } else {
            $transferData['remitterIdentificationNo'] = ($data['beneficiary_data']['sender_information']['profile']['id_doc']['id_no'] ?? null);
        }

        //dd($transferData);
        return $transferData;
    }

    /**
     * Import/push remittance (importWSMessage)
     *
     * Import/Push Remittance. If exchange house account has no available balance then you can use this operation.
     * After certain time, we will pull the message and will be available for transaction.
     *
     * Parameters: userID, password, accNo, wsMessage
     *
     * @throws Exception
     */
    public function importOrPushRemittance(array $data): array
    {
        $importOrPushRemittance = $this->__transferData($data);
        $xmlString = '
            <ser:userID>'.$this->config[$this->status]['username'].'</ser:userID>
            <ser:password>'.$this->config[$this->status]['password'].'</ser:password>
        ';
        //$xmlString .= '<ser:transRefNo>'.($data['transaction_reference_number'] ?? null).'</ser:transRefNo>';
        $xmlString .= '<ser:wsMessage>';
        $xmlString .= '<xsd:amount>'.($importOrPushRemittance['amount'] ?? null).'</xsd:amount>';
        $xmlString .= '<xsd:isoCode>'.($importOrPushRemittance['isoCode'] ?? null).'</xsd:isoCode>';
        $xmlString .= '<xsd:beneficiaryAddress>'.($importOrPushRemittance['beneficiaryAddress'] ?? null).'</xsd:beneficiaryAddress>';
        $xmlString .= '<xsd:beneficiaryBankCode>'.($importOrPushRemittance['beneficiaryBankCode'] ?? null).'</xsd:beneficiaryBankCode>';
        $xmlString .= '<xsd:beneficiaryBankName>'.($importOrPushRemittance['beneficiaryBankName'] ?? null).'</xsd:beneficiaryBankName>';
        $xmlString .= '<xsd:beneficiaryBranchCode>'.($importOrPushRemittance['beneficiaryBranchCode'] ?? null).'</xsd:beneficiaryBranchCode>';
        $xmlString .= '<xsd:beneficiaryBranchName>'.($importOrPushRemittance['beneficiaryBranchName'] ?? null).'</xsd:beneficiaryBranchName>';
        $xmlString .= '<xsd:beneficiaryName>'.($importOrPushRemittance['beneficiaryName'] ?? null).'</xsd:beneficiaryName>';
        $xmlString .= '<xsd:beneficiaryPassportNo>'.($importOrPushRemittance['beneficiaryPassportNo'] ?? null).'</xsd:beneficiaryPassportNo>';
        $xmlString .= '<xsd:beneficiaryPhoneNo>'.($importOrPushRemittance['beneficiaryPhoneNo'] ?? null).'</xsd:beneficiaryPhoneNo>';
        $xmlString .= '<xsd:creatorID>'.($importOrPushRemittance['creatorID'] ?? null).'</xsd:creatorID>';
        $xmlString .= '<xsd:exchHouseSwiftCode>'.($importOrPushRemittance['exchHouseSwiftCode'] ?? null).'</xsd:exchHouseSwiftCode>';
        $xmlString .= '<xsd:identityDescription>'.($importOrPushRemittance['identityDescription'] ?? null).'</xsd:identityDescription>';
        $xmlString .= '<xsd:identityType>'.($importOrPushRemittance['identityType'] ?? null).'</xsd:identityType>';
        $xmlString .= '<xsd:issueDate>'.($importOrPushRemittance['issueDate'] ?? null).'</xsd:issueDate>';
        $xmlString .= '<xsd:note>'.($importOrPushRemittance['note'] ?? null).'</xsd:note>';
        $xmlString .= '<xsd:paymentType>'.($importOrPushRemittance['paymentType'] ?? null).'</xsd:paymentType>';
        $xmlString .= '<xsd:remitterAddress>'.($importOrPushRemittance['remitterAddress'] ?? null).'</xsd:remitterAddress>';
        $xmlString .= '<xsd:remitterIdentificationNo>'.($importOrPushRemittance['remitterIdentificationNo'] ?? null).'</xsd:remitterIdentificationNo>';
        $xmlString .= '<xsd:remitterName'.($importOrPushRemittance['remitterName'] ?? null).'</xsd:remitterName>';
        $xmlString .= '<xsd:remitterPhoneNo>'.($importOrPushRemittance['remitterPhoneNo'] ?? null).'</xsd:remitterPhoneNo>';
        $xmlString .= '<xsd:secretKey>'.($importOrPushRemittance['secretKey'] ?? null).'</xsd:secretKey>';
        $xmlString .= '<xsd:transReferenceNo>'.($importOrPushRemittance['transReferenceNo'] ?? null).'</xsd:transReferenceNo>';
        $xmlString .= '<xsd:transferDate>'.($importOrPushRemittance['transferDate'] ?? null).'</xsd:transferDate>';
        $xmlString .= '<xsd: remittancePurpose>'.($importOrPushRemittance['remittancePurpose'] ?? null).'</xsd: remittancePurpose >';
        $xmlString .= '</ser:wsMessage>';
        $soapMethod = 'directCreditWSMessage';
        $response = $this->connectionCheck($xmlString, $soapMethod);

        $explodeValue = explode('|', $response['Envelope']['Body']);
        $explodeValueCount = count($explodeValue) - 1;
        $return['origin_response'] = $response['Envelope']['Body'];
        if ($explodeValueCount > 0) {
            $return['status'] = $explodeValue[0];
            $return['status_code'] = $explodeValue[$explodeValueCount];
            $return['message'] = $this->__responseCodeList($explodeValue[$explodeValueCount]);
        }

        return $return;
    }

    /**
     * Verify remittance (importWSMessage)
     * Parameters: userID, password, accNo, wsMessage
     *
     * @throws Exception
     */
    public function verifyRemittance(array $data): array
    {
        $verifyRemittance = $this->__transferData($data);
        $xmlString = '
            <ser:userID>'.$this->config[$this->status]['username'].'</ser:userID>
            <ser:password>'.$this->config[$this->status]['password'].'</ser:password>
        ';
        //$xmlString .= '<ser:transRefNo>'.($data['transaction_reference_number'] ?? null).'</ser:transRefNo>';
        $xmlString .= '<ser:wsMessage>';
        $xmlString .= '<xsd:amount>'.($verifyRemittance['amount'] ?? null).'</xsd:amount>';
        $xmlString .= '<xsd:isoCode>'.($verifyRemittance['isoCode'] ?? null).'</xsd:isoCode>';
        $xmlString .= '<xsd:beneficiaryAddress>'.($verifyRemittance['beneficiaryAddress'] ?? null).'</xsd:beneficiaryAddress>';
        $xmlString .= '<xsd:beneficiaryBankCode>'.($verifyRemittance['beneficiaryBankCode'] ?? null).'</xsd:beneficiaryBankCode>';
        $xmlString .= '<xsd:beneficiaryBankName>'.($verifyRemittance['beneficiaryBankName'] ?? null).'</xsd:beneficiaryBankName>';
        $xmlString .= '<xsd:beneficiaryBranchCode>'.($verifyRemittance['beneficiaryBranchCode'] ?? null).'</xsd:beneficiaryBranchCode>';
        $xmlString .= '<xsd:beneficiaryBranchName>'.($verifyRemittance['beneficiaryBranchName'] ?? null).'</xsd:beneficiaryBranchName>';
        $xmlString .= '<xsd:beneficiaryName>'.($verifyRemittance['beneficiaryName'] ?? null).'</xsd:beneficiaryName>';
        $xmlString .= '<xsd:beneficiaryPassportNo>'.($verifyRemittance['beneficiaryPassportNo'] ?? null).'</xsd:beneficiaryPassportNo>';
        $xmlString .= '<xsd:beneficiaryPhoneNo>'.($verifyRemittance['beneficiaryPhoneNo'] ?? null).'</xsd:beneficiaryPhoneNo>';
        $xmlString .= '<xsd:creatorID>'.($verifyRemittance['creatorID'] ?? null).'</xsd:creatorID>';
        $xmlString .= '<xsd:exchHouseSwiftCode>'.($verifyRemittance['exchHouseSwiftCode'] ?? null).'</xsd:exchHouseSwiftCode>';
        $xmlString .= '<xsd:identityDescription>'.($verifyRemittance['identityDescription'] ?? null).'</xsd:identityDescription>';
        $xmlString .= '<xsd:identityType>'.($verifyRemittance['identityType'] ?? null).'</xsd:identityType>';
        $xmlString .= '<xsd:issueDate>'.($verifyRemittance['issueDate'] ?? null).'</xsd:issueDate>';
        $xmlString .= '<xsd:note>'.($verifyRemittance['note'] ?? null).'</xsd:note>';
        $xmlString .= '<xsd:paymentType>'.($verifyRemittance['paymentType'] ?? null).'</xsd:paymentType>';
        $xmlString .= '<xsd:remitterAddress>'.($verifyRemittance['remitterAddress'] ?? null).'</xsd:remitterAddress>';
        $xmlString .= '<xsd:remitterIdentificationNo>'.($verifyRemittance['remitterIdentificationNo'] ?? null).'</xsd:remitterIdentificationNo>';
        $xmlString .= '<xsd:remitterName'.($verifyRemittance['remitterName'] ?? null).'</xsd:remitterName>';
        $xmlString .= '<xsd:remitterPhoneNo>'.($verifyRemittance['remitterPhoneNo'] ?? null).'</xsd:remitterPhoneNo>';
        $xmlString .= '<xsd:secretKey>'.($verifyRemittance['secretKey'] ?? null).'</xsd:secretKey>';
        $xmlString .= '<xsd:transReferenceNo>'.($verifyRemittance['transReferenceNo'] ?? null).'</xsd:transReferenceNo>';
        $xmlString .= '<xsd:transferDate>'.($verifyRemittance['transferDate'] ?? null).'</xsd:transferDate>';
        $xmlString .= '<xsd: remittancePurpose>'.($verifyRemittance['remittancePurpose'] ?? null).'</xsd: remittancePurpose >';
        $xmlString .= '</ser:wsMessage>';
        $soapMethod = 'directCreditWSMessage';
        $response = $this->connectionCheck($xmlString, $soapMethod);

        $explodeValue = explode('|', $response['Envelope']['Body']);
        $explodeValueCount = count($explodeValue) - 1;
        $return['origin_response'] = $response['Envelope']['Body'];
        if ($explodeValueCount > 0) {
            $return['status'] = $explodeValue[0];
            $return['status_code'] = $explodeValue[$explodeValueCount];
            $return['message'] = $this->__responseCodeList($explodeValue[$explodeValueCount]);
        }

        return $return;
    }

    /**
     * @throws Exception
     */
    public function transferStatus(array $orderInfo = []): array
    {
        return $this->fetchRemittanceStatus($orderInfo);
    }

    /**
     * Fetch Remittance Status (fetchWSMessageStatus)
     *
     * Fetch Remittance Status. You can also check the current status of your
     * remittance whether your remittance has been paid or not.
     *
     * Parameters: userID, password, transaction_reference_number, secret_key
     *
     * @throws Exception
     */
    public function fetchRemittanceStatus(array $data): array
    {
        $xmlString = '
            <ser:userID>'.$this->config[$this->status]['username'].'</ser:userID>
            <ser:password>'.$this->config[$this->status]['password'].'</ser:password>
        ';
        $xmlString .= '<ser:transRefNo>'.($data['transaction_reference_number'] ?? null).'</ser:transRefNo>';
        $xmlString .= '<ser:secretKey>'.($data['secret_key'] ?? null).'</ser:secretKey>';
        $soapMethod = 'fetchWSMessageStatusResponse';
        $response = $this->connectionCheck($xmlString, $soapMethod);

        $explodeValue = explode('|', $response['Envelope']['Body']);
        $explodeValueCount = count($explodeValue) - 1;
        $return['origin_response'] = $response['Envelope']['Body'];
        if ($explodeValueCount > 0) {
            if ($explodeValue[0] == 'FALSE') {
                $return['status'] = $explodeValue[0];
                $return['status_code'] = $explodeValue[$explodeValueCount];
                $return['message'] = $this->__responseCodeList($explodeValue[$explodeValueCount]);
            } else {
                $return['status_code'] = $explodeValue[$explodeValueCount];
                $return['message'] = $this->__responseStatusCodeList($explodeValue[$explodeValueCount]);
            }
        }

        return $return;
    }

    /**
     * Response Status Code List
     * These codes will return in only Fetch Remittance Status (fetchWSMessageStatus) operation.
     */
    private function __responseStatusCodeList(string $code): string
    {
        $return = [
            '01' => 'REMITTANCE ISSUED',
            '02' => 'REMITTANCE TRANSFERRED/AUTHORIZED BY EXCHANGE HOUSE',
            '03' => 'REMITTANCE READY FOR PAYMENT',
            '04' => 'REMITTANCE UNDER PROCESS',
            '05' => 'REMITTANCE STOPPED',
            '06' => 'REMITTANCE STOPPED BY EXCHANGE HOUSE',
            '07' => 'REMITTANCE PAID',
            '08' => 'REMITTANCE AMENDED',
            '11' => 'REMITTANCE CANCELLED',
            '17' => 'REMITTANCE REVERSED',
            '20' => 'REMITTANCE CANCEL REQUEST',
            '30' => 'REMITTANCE AMENDMENT REQUEST',
            '70' => 'REMITTANCE CBS UNDER PROCESS',
            '73' => 'REMITTANCE CBS AUTHORIZED',
            '74' => 'REMITTANCE CBS PENDING',
            '77' => 'REMITTANCE CBS NRT ACCOUNT DEBITED',
            '78' => 'REMITTANCE CBS READY FOR PAYMENT',
            '79' => 'REMITTANCE CBS CREDITED TO ACCOUNT',
            '80' => 'REMITTANCE CBS UNKNOWN STATE',
            '82' => 'CBS ACC PAYEE TITLE AND ACCOUNT NO DIFFER',
            '83' => 'CBS EFT INVALID ACCOUNT',
            '84' => 'CBS EFT SENT TO THIRD BANK',
            '99' => 'REMITTANCE INVALID STATUS',
        ];

        return $return[$code];
    }

    public function cancelTransfer(array $orderInfo = []): array
    {
        return [];
    }

    /**
     * @throws Exception
     */
    public function verifyAccount(array $accountInfo = []): array
    {
        $this->validateBeneficiaryWallet($accountInfo);

        return [];
    }

    /**
     * Validate information of beneficiary wallet no. (validateBeneficiaryWallet)
     * Parameters: userID, password, walletNo, paymentType
     *
     * @throws Exception
     */
    public function validateBeneficiaryWallet(array $data): array
    {
        $validateBeneficiaryWallet = $this->__transferData($data);
        $xmlString = '
            <ser:userID>'.$this->config[$this->status]['username'].'</ser:userID>
            <ser:password>'.$this->config[$this->status]['password'].'</ser:password>
        ';
        $xmlString .= '<ser:walletNo>'.($validateBeneficiaryWallet['beneficiaryAccNo']).'</ser:walletNo>';
        $xmlString .= '<ser:paymentType>'.($validateBeneficiaryWallet['paymentType']).'</ser:paymentType>';
        $soapMethod = 'validateBeneficiaryWallet';
        $response = $this->connectionCheck($xmlString, $soapMethod);

        $explodeValue = explode('|', $response['Envelope']['Body']);
        $explodeValueCount = count($explodeValue) - 1;
        $return['origin_response'] = $response['Envelope']['Body'];
        if ($explodeValueCount > 0) {
            $return['status'] = $explodeValue[0];
            $return['status_code'] = $explodeValue[$explodeValueCount];
            $return['message'] = $this->__responseCodeList($explodeValue[$explodeValueCount]);
        }

        return $return;
    }

    /**
     * @throws Exception
     */
    public function vendorBalance(array $accountInfo = []): array
    {
        $currency = $accountInfo['currency'] ?? 'USD';

        return $this->fetchBalance($currency);
    }

    /**
     * Fetch Exchange House NRT/NRD account balance (fetchBalance).
     * We are maintaining two types of account of exchange houses.
     *
     * Parameters: userID, password, currency
     *
     * @throws Exception
     */
    public function fetchBalance(string $currency): array
    {
        $xmlString = "
            <ser:userID>{$this->config[$this->status]['username']}</ser:userID>
            <ser:password>{$this->config[$this->status]['password']}</ser:password>
            <ser:currency>{$currency}</ser:currency>";
        $soapMethod = 'fetchBalance';
        $response = $this->connectionCheck($xmlString, $soapMethod);

        $explodeValue = explode('|', $response['Envelope']['Body']);
        $explodeValueCount = count($explodeValue) - 1;
        $return['origin_response'] = $response['Envelope']['Body'];
        if ($explodeValueCount > 0) {
            $return['status'] = $explodeValue[0];
            if ($explodeValue[0] == 'FALSE') {
                $return['status_code'] = $explodeValue[$explodeValueCount];
                $return['message'] = $this->__responseCodeList($explodeValue[$explodeValueCount]);
            } else {
                $return['message'] = $explodeValue[$explodeValueCount];
            }
        }

        return $return;
    }

    /**
     * Instrument/Payment Type Code
     */
    private function __instrumentOrPaymentTypeCode(int $code): string
    {
        $return = [
            1 => 'Instant Cash / Spot Cash/COC',
            2 => 'IBBL Account Payee',
            3 => 'Other Bank (BEFTN)',
            4 => 'Remittance Card',
            5 => 'Mobile Banking (mCash)',
            6 => 'New IBBL Account Open',
            7 => 'Mobile Banking(bKash)',
            8 => 'Mobile Banking (Nagad)',
        ];

        return $return[$code];
    }

    /**
     * Beneficiary Identity Type Code
     */
    private function __beneficiaryIdentityTypeCode(int|string $code, string $flag = 'key'): string
    {
        $return = [
            1 => 'Passport',
            2 => 'Cheque',
            3 => 'Photo',
            4 => 'Finger Print',
            5 => 'Introducer',
            6 => 'Driving License',
            7 => 'Other',
            8 => 'Remittance Card',
            9 => 'National ID Card',
            10 => 'Birth Certificate',
            11 => 'Student ID Card',
        ];

        if ($flag == 'value') {
            $return = [
                1 => 'Passport',
                2 => 'Cheque',
                3 => 'Photo',
                4 => 'Finger Print',
                5 => 'Introducer',
                6 => 'Driving License',
                7 => 'Other',
                8 => 'Remittance Card',
                9 => 'National ID',
                9 => 'National ID Card',
                9 => 'National Identity Card',
                9 => 'Voter ID',
                9 => 'Voter ID Card',
                9 => 'Voter Identity Card',
                10 => 'Birth Certificate',
                11 => 'Student ID Card',
                11 => 'Student Identity Card',
            ];
            $return = array_flip($return);
        }

        if (is_string($code)) {
            $code = ucwords(str_replace('-', ' ', $code));
        }

        return $return[$code];
    }

    /**
     * Account Type Code
     * Please send the following two-digit code against the different types of account.
     *
     * @return array|string|string[]
     */
    public function accountTypeCode(string $code): array|string
    {
        $return = [
            '01' => 'AWCA (Current)',
            '02' => 'MSA (Savings)',
            '03' => 'MSSA (Scheme)',
            '05' => 'MTDRA(Term Deposit)',
            '06' => 'MMSA (Mohr)',
            '07' => 'MHSA (Hajj)',
            '09' => 'SND(Short Notice Deposit)',
            '10' => 'MSA-STAFF',
            '11' => 'FCA (FC Current)',
            '12' => 'MFCA (FC Savings)',
            '67' => 'SMSA(Student Savings)',
            '68' => 'MNSBA(NRB Savings Bond)',
            '71' => 'Remittance card',
        ];

        if ($code == '') {
            $returnValue = $return;
        } else {
            $returnValue = $return[$code];
        }

        return $returnValue;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model|\Fintech\Core\Abstracts\BaseModel  $order
     *
     * @throws Exception
     */
    public function requestQuote($order): mixed
    {
        return $this->fetchBalance('BDT');
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model|\Fintech\Core\Abstracts\BaseModel  $order
     *
     * @throws Exception
     */
    public function processOrder($order): mixed
    {
        $order_data = $order->order_data ?? [];

        $order_data['beneficiary_data']['reference_no'] = $order->order_number;
        $order_data['sending_amount'] = $order['amount'];
        $order_data['sending_currency'] = $order['currency'];

        return $this->directCreditRemittance($order_data);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model|\Fintech\Core\Abstracts\BaseModel  $order
     *
     * @throws Exception
     */
    public function orderStatus($order): mixed
    {
        //        $data['transaction_reference_number'] = 'GIT4296253';
        $data['transaction_reference_number'] = $order->order_number;
        $data['secret_key'] = '';

        return $this->fetchRemittanceStatus($data);
    }
}
