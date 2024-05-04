<?php

namespace Fintech\Remit\Vendors;

use Exception;
use Fintech\Core\Supports\Utility;
use Fintech\Remit\Contracts\BankTransfer;
use Fintech\Remit\Contracts\OrderQuotation;
use Illuminate\Support\Facades\Http;
use SimpleXMLElement;

class IslamiBankApi implements BankTransfer, OrderQuotation
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
     * Fetch Exchange House NRT/NRD account balance (fetchBalance)
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
        if ($explodeValue[0] == 'FALSE') {
            $return['status'] = $explodeValue[0];
            $return['status_code'] = $explodeValue[$explodeValueCount];
            $return['message'] = $this->__responseCodeList($explodeValue[$explodeValueCount]);
        } else {
            $return = explode('|', $response['Envelope']['Body']);
        }

        return $return;
    }

    /**
     * Fetch Account Details (fetchAccountDetail)
     * Parameters: userID, password, account_number, account_type, branch_code
     *
     * @throws Exception
     */
    public function fetchAccountDetail(array $data): array
    {
        $xmlString = '
            <ser:userID>'.$this->config[$this->status]['username'].'</ser:userID>
            <ser:password>'.$this->config[$this->status]['password'].'</ser:password>
        ';
        $xmlString .= '<ser:accNo>'.($data['account_number'] ?? null).'</ser:accNo>';
        $xmlString .= '<ser:accType>'.($data['account_type'] ?? null).'</ser:accType>';
        $xmlString .= '<ser:branchCode>'.($data['branch_code'] ?? null).'</ser:branchCode>';
        $soapMethod = 'fetchAccountDetail';
        $response = $this->connectionCheck($xmlString, $soapMethod);

        $explodeValue = explode('|', $response['Envelope']['Body']);
        $explodeValueCount = count($explodeValue) - 1;
        if ($explodeValue[0] == 'FALSE') {
            $return['status'] = $explodeValue[0];
            $return['status_code'] = $explodeValue[$explodeValueCount];
            $return['message'] = $this->__responseCodeList($explodeValue[$explodeValueCount]);
        } else {
            $return = explode('|', $response['Envelope']['Body']);
        }

        return $return;
    }

    /**
     * Fetch Remittance Status (fetchWSMessageStatus)
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
        if ($explodeValue[0] == 'FALSE') {
            $return['status'] = $explodeValue[0];
            $return['status_code'] = $explodeValue[$explodeValueCount];
            $return['message'] = $this->__responseCodeList($explodeValue[$explodeValueCount]);
        } else {
            $return = explode('|', $response['Envelope']['Body']);
        }

        return $return;
    }

    /**
     * Direct Credit Remittance (directCreditWSMessage)
     * Parameters: userID, password, accNo, wsMessage
     *
     * @throws Exception
     */
    public function directCreditRemittance(array $data): array
    {
        $xmlString = '
            <ser:userID>'.$this->config[$this->status]['username'].'</ser:userID>
            <ser:password>'.$this->config[$this->status]['password'].'</ser:password>
        ';
        $xmlString .= '<ser:transRefNo>'.($data['transaction_reference_number'] ?? null).'</ser:transRefNo>';
        $xmlString .= '<ser:wsMessage>';
        $xmlString .= '<xsd:amount>'.($data['amount'] ?? null).'</xsd:amount>';
        $xmlString .= '<xsd:isoCode>'.($data['isoCode'] ?? null).'</xsd:isoCode>';

        $xmlString .= '<xsd:beneficiaryAccNo>'.($data['beneficiaryAccNo'] ?? null).'</xsd:beneficiaryAccNo>';
        $xmlString .= '<xsd:beneficiaryAccType>'.($data['beneficiaryAccType'] ?? null).'</xsd:beneficiaryAccType>';

        $xmlString .= '<xsd:beneficiaryAddress>'.($data['beneficiaryAddress'] ?? null).'</xsd:beneficiaryAddress>';
        $xmlString .= '<xsd:beneficiaryBankCode>'.($data['beneficiaryBankCode'] ?? null).'</xsd:beneficiaryBankCode>';
        $xmlString .= '<xsd:beneficiaryBankName>'.($data['beneficiaryBankName'] ?? null).'</xsd:beneficiaryBankName>';
        $xmlString .= '<xsd:beneficiaryBranchCode>'.($data['beneficiaryBranchCode'] ?? null).'</xsd:beneficiaryBranchCode>';
        $xmlString .= '<xsd:beneficiaryBranchName>'.($data['beneficiaryBranchName'] ?? null).'</xsd:beneficiaryBranchName>';
        $xmlString .= '<xsd:beneficiaryName>'.($data['beneficiaryName'] ?? null).'</xsd:beneficiaryName>';
        $xmlString .= '<xsd:beneficiaryPhoneNo>'.($data['beneficiaryPhoneNo'] ?? null).'</xsd:beneficiaryPhoneNo>';
        $xmlString .= '<xsd:issueDate>'.($data['issueDate'] ?? null).'</xsd:issueDate>';
        $xmlString .= '<xsd:note>'.($data['note'] ?? null).'</xsd:note>';
        $xmlString .= '<xsd:paymentType>'.($data['paymentType'] ?? null).'</xsd:paymentType>';
        $xmlString .= '<xsd:remitterAddress>'.($data['remitterAddress'] ?? null).'</xsd:remitterAddress>';
        $xmlString .= '<xsd:remitterIdentificationNo>'.($data['remitterIdentificationNo'] ?? null).'</xsd:remitterIdentificationNo>';
        $xmlString .= '<xsd:remitterName'.($data['remitterName'] ?? null).'</xsd:remitterName>';
        $xmlString .= '<xsd:remitterPhoneNo>'.($data['remitterPhoneNo'] ?? null).'</xsd:remitterPhoneNo>';
        $xmlString .= '<xsd:secretKey>'.($data['secretKey'] ?? null).'</xsd:secretKey>';
        $xmlString .= '<xsd:transReferenceNo>'.($data['transReferenceNo'] ?? null).'</xsd:transReferenceNo>';
        $xmlString .= '<xsd: remittancePurpose>'.($data['remittancePurpose'] ?? null).'</xsd: remittancePurpose >';
        $xmlString .= '</ser:wsMessage>';
        $soapMethod = 'directCreditWSMessage';
        $response = $this->connectionCheck($xmlString, $soapMethod);

        $explodeValue = explode('|', $response['Envelope']['Body']);
        $explodeValueCount = count($explodeValue) - 1;
        if ($explodeValue[0] == 'FALSE') {
            $return['status'] = $explodeValue[0];
            $return['status_code'] = $explodeValue[$explodeValueCount];
            $return['message'] = $this->__responseCodeList($explodeValue[$explodeValueCount]);
        } else {
            $return = explode('|', $response['Envelope']['Body']);
        }

        return $return;
    }

    /**
     * Import/push remittance (importWSMessage)
     * Parameters: userID, password, accNo, wsMessage
     *
     * @throws Exception
     */
    public function importOrPushRemittance(array $data): SimpleXMLElement
    {
        $xmlString = '
            <ser:userID>'.$this->config[$this->status]['username'].'</ser:userID>
            <ser:password>'.$this->config[$this->status]['password'].'</ser:password>
        ';
        $xmlString .= '<ser:transRefNo>'.($data['transaction_reference_number'] ?? null).'</ser:transRefNo>';
        $xmlString .= '<ser:wsMessage>';
        $xmlString .= '<xsd:amount>'.($data['amount'] ?? null).'</xsd:amount>';
        $xmlString .= '<xsd:isoCode>'.($data['isoCode'] ?? null).'</xsd:isoCode>';
        $xmlString .= '<xsd:beneficiaryAddress>'.($data['beneficiaryAddress'] ?? null).'</xsd:beneficiaryAddress>';
        $xmlString .= '<xsd:beneficiaryBankCode>'.($data['beneficiaryBankCode'] ?? null).'</xsd:beneficiaryBankCode>';
        $xmlString .= '<xsd:beneficiaryBankName>'.($data['beneficiaryBankName'] ?? null).'</xsd:beneficiaryBankName>';
        $xmlString .= '<xsd:beneficiaryBranchCode>'.($data['beneficiaryBranchCode'] ?? null).'</xsd:beneficiaryBranchCode>';
        $xmlString .= '<xsd:beneficiaryBranchName>'.($data['beneficiaryBranchName'] ?? null).'</xsd:beneficiaryBranchName>';
        $xmlString .= '<xsd:beneficiaryName>'.($data['beneficiaryName'] ?? null).'</xsd:beneficiaryName>';
        $xmlString .= '<xsd:beneficiaryPassportNo>'.($data['beneficiaryPassportNo'] ?? null).'</xsd:beneficiaryPassportNo>';
        $xmlString .= '<xsd:beneficiaryPhoneNo>'.($data['beneficiaryPhoneNo'] ?? null).'</xsd:beneficiaryPhoneNo>';
        $xmlString .= '<xsd:creatorID>'.($data['creatorID'] ?? null).'</xsd:creatorID>';
        $xmlString .= '<xsd:exchHouseSwiftCode>'.($data['exchHouseSwiftCode'] ?? null).'</xsd:exchHouseSwiftCode>';
        $xmlString .= '<xsd:identityDescription>'.($data['identityDescription'] ?? null).'</xsd:identityDescription>';
        $xmlString .= '<xsd:identityType>'.($data['identityType'] ?? null).'</xsd:identityType>';
        $xmlString .= '<xsd:issueDate>'.($data['issueDate'] ?? null).'</xsd:issueDate>';
        $xmlString .= '<xsd:note>'.($data['note'] ?? null).'</xsd:note>';
        $xmlString .= '<xsd:paymentType>'.($data['paymentType'] ?? null).'</xsd:paymentType>';
        $xmlString .= '<xsd:remitterAddress>'.($data['remitterAddress'] ?? null).'</xsd:remitterAddress>';
        $xmlString .= '<xsd:remitterIdentificationNo>'.($data['remitterIdentificationNo'] ?? null).'</xsd:remitterIdentificationNo>';
        $xmlString .= '<xsd:remitterName'.($data['remitterName'] ?? null).'</xsd:remitterName>';
        $xmlString .= '<xsd:remitterPhoneNo>'.($data['remitterPhoneNo'] ?? null).'</xsd:remitterPhoneNo>';
        $xmlString .= '<xsd:secretKey>'.($data['secretKey'] ?? null).'</xsd:secretKey>';
        $xmlString .= '<xsd:transReferenceNo>'.($data['transReferenceNo'] ?? null).'</xsd:transReferenceNo>';
        $xmlString .= '<xsd:transferDate>'.($data['transferDate'] ?? null).'</xsd:transferDate>';
        $xmlString .= '<xsd: remittancePurpose>'.($data['remittancePurpose'] ?? null).'</xsd: remittancePurpose >';
        $xmlString .= '</ser:wsMessage>';
        $soapMethod = 'directCreditWSMessage';
        $response = $this->connectionCheck($xmlString, $soapMethod);

        return $response->directCreditWSMessageResponse->Response;
    }

    /**
     * Verify remittance (importWSMessage)
     * Parameters: userID, password, accNo, wsMessage
     *
     * @throws Exception
     */
    public function verifyRemittance(array $data): SimpleXMLElement
    {
        $xmlString = '
            <ser:userID>'.$this->config[$this->status]['username'].'</ser:userID>
            <ser:password>'.$this->config[$this->status]['password'].'</ser:password>
        ';
        $xmlString .= '<ser:transRefNo>'.($data['transaction_reference_number'] ?? null).'</ser:transRefNo>';
        $xmlString .= '<ser:wsMessage>';
        $xmlString .= '<xsd:amount>'.($data['amount'] ?? null).'</xsd:amount>';
        $xmlString .= '<xsd:isoCode>'.($data['isoCode'] ?? null).'</xsd:isoCode>';
        $xmlString .= '<xsd:beneficiaryAddress>'.($data['beneficiaryAddress'] ?? null).'</xsd:beneficiaryAddress>';
        $xmlString .= '<xsd:beneficiaryBankCode>'.($data['beneficiaryBankCode'] ?? null).'</xsd:beneficiaryBankCode>';
        $xmlString .= '<xsd:beneficiaryBankName>'.($data['beneficiaryBankName'] ?? null).'</xsd:beneficiaryBankName>';
        $xmlString .= '<xsd:beneficiaryBranchCode>'.($data['beneficiaryBranchCode'] ?? null).'</xsd:beneficiaryBranchCode>';
        $xmlString .= '<xsd:beneficiaryBranchName>'.($data['beneficiaryBranchName'] ?? null).'</xsd:beneficiaryBranchName>';
        $xmlString .= '<xsd:beneficiaryName>'.($data['beneficiaryName'] ?? null).'</xsd:beneficiaryName>';
        $xmlString .= '<xsd:beneficiaryPassportNo>'.($data['beneficiaryPassportNo'] ?? null).'</xsd:beneficiaryPassportNo>';
        $xmlString .= '<xsd:beneficiaryPhoneNo>'.($data['beneficiaryPhoneNo'] ?? null).'</xsd:beneficiaryPhoneNo>';
        $xmlString .= '<xsd:creatorID>'.($data['creatorID'] ?? null).'</xsd:creatorID>';
        $xmlString .= '<xsd:exchHouseSwiftCode>'.($data['exchHouseSwiftCode'] ?? null).'</xsd:exchHouseSwiftCode>';
        $xmlString .= '<xsd:identityDescription>'.($data['identityDescription'] ?? null).'</xsd:identityDescription>';
        $xmlString .= '<xsd:identityType>'.($data['identityType'] ?? null).'</xsd:identityType>';
        $xmlString .= '<xsd:issueDate>'.($data['issueDate'] ?? null).'</xsd:issueDate>';
        $xmlString .= '<xsd:note>'.($data['note'] ?? null).'</xsd:note>';
        $xmlString .= '<xsd:paymentType>'.($data['paymentType'] ?? null).'</xsd:paymentType>';
        $xmlString .= '<xsd:remitterAddress>'.($data['remitterAddress'] ?? null).'</xsd:remitterAddress>';
        $xmlString .= '<xsd:remitterIdentificationNo>'.($data['remitterIdentificationNo'] ?? null).'</xsd:remitterIdentificationNo>';
        $xmlString .= '<xsd:remitterName'.($data['remitterName'] ?? null).'</xsd:remitterName>';
        $xmlString .= '<xsd:remitterPhoneNo>'.($data['remitterPhoneNo'] ?? null).'</xsd:remitterPhoneNo>';
        $xmlString .= '<xsd:secretKey>'.($data['secretKey'] ?? null).'</xsd:secretKey>';
        $xmlString .= '<xsd:transReferenceNo>'.($data['transReferenceNo'] ?? null).'</xsd:transReferenceNo>';
        $xmlString .= '<xsd:transferDate>'.($data['transferDate'] ?? null).'</xsd:transferDate>';
        $xmlString .= '<xsd: remittancePurpose>'.($data['remittancePurpose'] ?? null).'</xsd: remittancePurpose >';
        $xmlString .= '</ser:wsMessage>';
        $soapMethod = 'directCreditWSMessage';
        $response = $this->connectionCheck($xmlString, $soapMethod);

        return $response->directCreditWSMessageResponse->Response;
    }

    /**
     * ValidateBeneficiaryWallet (validateBeneficiaryWallet)
     * Parameters: userID, password, walletNo, paymentType
     *
     * @throws Exception
     */
    public function validateBeneficiaryWallet(array $data): array
    {
        $xmlString = '
            <ser:userID>'.$this->config[$this->status]['username'].'</ser:userID>
            <ser:password>'.$this->config[$this->status]['password'].'</ser:password>
        ';
        $xmlString .= '<ser:walletNo>'.($data['account_number'] ?? null).'</ser:walletNo>';
        $xmlString .= '<ser:paymentType>'.($data['account_type'] ?? null).'</ser:paymentType>';
        $soapMethod = 'validateBeneficiaryWallet';
        $response = $this->connectionCheck($xmlString, $soapMethod);

        $explodeValue = explode('|', $response['Envelope']['Body']);
        $explodeValueCount = count($explodeValue) - 1;
        if ($explodeValue[0] == 'FALSE') {
            $return['status'] = $explodeValue[0];
            $return['status_code'] = $explodeValue[$explodeValueCount];
            $return['message'] = $this->__responseCodeList($explodeValue[$explodeValueCount]);
        } else {
            $return = explode('|', $response['Envelope']['Body']);
        }

        return $return;
    }

    /**
     * @throws Exception
     */
    private function connectionCheck($xml_post_string, $method): array
    {
        $xml_string = $this->xmlGenerate($xml_post_string, $method);
        dump($method.'<br>'.$xml_string);
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
     *
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
     * @return string[]
     */
    private function __responseStatusCodeList(string $code): array
    {
        return [
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
    }

    /**
     * Instrument/Payment Type Code
     *
     * @return string[]
     */
    private function __instrumentOrPaymentTypeCode(int $code): array
    {
        return [
            1 => 'Instant Cash / Spot Cash/COC',
            2 => 'IBBL Account Payee',
            3 => 'Other Bank (BEFTN)',
            4 => 'Remittance Card5 Mobile Banking (mCash)',
            6 => 'New IBBL Account Open',
            7 => 'Mobile Banking(bKash)',
            8 => 'Mobile Banking (Nagad)',
        ];
    }

    /**
     * Beneficiary Identity Type Code
     *
     * @return string[]
     */
    private function __beneficiaryIdentityTypeCode(int $code): array
    {
        return [
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
    }

    /**
     * Account Type Code
     * Please send the following two-digit code against the different types of account.
     *
     * @return string[]
     */
    private function __accountTypeCode(string $code): array
    {
        return [
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
        ];
    }

    public function makeTransfer(array $orderInfo = []): array
    {
        return [];
    }

    /**
     * @throws Exception
     */
    public function transferStatus(array $orderInfo = []): array
    {
        return $this->fetchRemittanceStatus($orderInfo);
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
     * @throws Exception
     */
    public function vendorBalance(array $accountInfo = []): array
    {
        $currency = $accountInfo['currency'] ?? 'USD';

        return $this->fetchBalance($currency);
    }

    public function requestQuotation($order): array
    {
        return [];
    }
}
