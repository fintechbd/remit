<?php

namespace Fintech\Remit\Vendors;

use DOMDocument;
use DOMException;
use ErrorException;
use Exception;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Enums\Transaction\OrderStatus;
use Fintech\Core\Supports\AssignVendorVerdict;
use Fintech\Core\Supports\Utility;
use Fintech\Remit\Contracts\MoneyTransfer;
use Fintech\Remit\Contracts\WalletTransfer;
use Fintech\Remit\Contracts\WalletVerification;
use Fintech\Remit\Support\WalletVerificationVerdict;
use Fintech\Transaction\Facades\Transaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class IslamiBankApi implements MoneyTransfer, WalletTransfer, WalletVerification
{
    public const ERROR_MESSAGES = [
        1000 => '1000 - OTHER ERROR',
        1001 => '1001 - TRANSACTION REF INVALID',
        1002 => '1002 - AMOUNT INVALID',
        1003 => '1003 - ISO CODE INVALID',
        1004 => '1004 - SWIFT CODE INVALID',
        1005 => '1005 - NOTE INVALID',
        1006 => '1006 - SECRET KEY INVALID',
        1007 => '1007 - PAYMENT TYPE INVALID',
        1008 => '1008 - IDENTITY TYPE INVALID',
        1009 => '1009 - IDENTITY DESCRIPTION INVALID',
        1010 => '1010 - EXCHANGE BR CODE INVALID',
        1011 => '1011 - ISSUE DATE INVALID',
        1101 => '1101 - TRANSACTION REF MISSING',
        1102 => '1102 - AMOUNT MISSING',
        1103 => '1103 - CURRENCY MISSING',
        1104 => '1104 - SWIFT CODE MISSING',
        1105 => '1105 - NOTE MISSING',
        1106 => '1106 - SECRET KEY MISSING',
        1107 => '1107 - PAYMENT TYPE MISSING',
        1108 => '1108 - IDENTITY TYPE MISSING',
        1109 => '1109 - IDENTITY DESCRIPTION MISSING',
        1110 => '1110 - EXCHANGE BR CODE MISSING',
        1111 => '1111 - ISSUE DATE MISSING',
        1201 => '1201 - BENEFICIARY ACC NO NOT APPLICABLE',
        1202 => '1202 - BENEFICIARY ROUTING NO NOT APPLICABLE',
        1301 => '1301 - BENEFICIARY ACC NO NOT FOUND',
        2001 => '2001 - REMITTER NAME INVALID',
        2002 => '2002 - REMITTER IDENTIFICATION NO INVALID',
        2003 => '2003 - REMITTER PHONE NO INVALID',
        2004 => '2004 - REMITTER ADDRESS INVALID',
        2101 => '2101 - REMITTER NAME MISSING',
        2102 => '2102 - REMITTER IDENTIFICATION NO MISSING',
        2103 => '2103 - REMITTER PHONE NO MISSING',
        2104 => '2104 - REMITTER ADDRESS MISSING',
        3001 => '3001 - BENEFICIARY NAME INVALID',
        3002 => '3002 - BENEFICIARY PASSPORT INVALID',
        3003 => '3003 - BENEFICIARY PHONE INVALID',
        3004 => '3004 - BENEFICIARY ADDRESS INVALID',
        3005 => '3005 - BENEFICIARY ACC NO INVALID',
        3006 => '3006 - BENEFICIARY ACC TYPE INVALID',
        3007 => '3007 - BENEFICIARY BANK CODE INVALID',
        3008 => '3008 - BENEFICIARY BANK NAME INVALID',
        3009 => '3009 - BENEFICIARY BRANCH CODE INVALID',
        3010 => '3010 - BENEFICIARY BRANCH NAME INVALID',
        3011 => '3011 - BENEFICIARY ROUTING NO INVALID',
        3101 => '3101 - BENEFICIARY NAME MISSING',
        3102 => '3102 - BENEFICIARY PASSPORT MISSING',
        3103 => '3103 - BENEFICIARY PHONE MISSING',
        3104 => '3104 - BENEFICIARY ADDRESS MISSING',
        3105 => '3105 - BENEFICIARY ACC NO MISSING',
        3106 => '3106 - BENEFICIARY ACC TYPE MISSING',
        3107 => '3107 - BENEFICIARY BANK CODE MISSING',
        3108 => '3108 - BENEFICIARY BANK NAME MISSING',
        3109 => '3109 - BENEFICIARY BRANCH CODE MISSING',
        3110 => '3110 - BENEFICIARY BRANCH NAME MISSING',
        3111 => '3111 - BENEFICIARY ROUTING NO MISSING',
        3112 => '3112 - BENEFICIARY CARD NO MISSING',
        3113 => '3113 - BENEFICIARY WALLET ACC NO MISSING',
        3114 => '3114 - BENEFICIARY ACC NO LENGTH INVALID',
        5001 => '5001 - REMITTANCE ALREADY IMPORTED',
        5002 => '5002 - REMITTANCE VERIFIED SUCCESSFULLY',
        5003 => '5003 - REMITTANCE SUCCESS',
        5004 => '5004 - REMITTANCE FAILED',
        5005 => '5005 - REMITTANCE SKIPPED',
        5006 => '5006 - REMITTANCE NOT_FOUND',
        5007 => '5007 - REMITTANCE IS ENQUEUED',
        6001 => '6001 - INSUFFICIENT BALANCE',
        6002 => '6002 - ACCOUNT NAME AND ACCOUNT NO. DIFFER',
        6003 => '6003 - FIELD LENGTH INVALID',
        6004 => '6004 - ACCOUNT NO. NOT FOUND',
        7001 => '7001 - USER NAME OR PASSWORD IS MISSING',
        7002 => '7002 - USER NAME OR PASSWORD IS INVALID',
        7003 => '7003 - USER IS BLOCKED',
        7004 => '7004 - USER IS INACTIVE',
        7005 => '7005 - USER IS DEAD (PERMANENTLY BLOCKED)',
    ];

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

    private DOMDocument $xml;

    /**
     * IslamiBank API constructor.
     */
    public function __construct()
    {
        $this->config = config('fintech.remit.providers.islamibank');
        $this->status = config('fintech.remit.providers.islamibank.mode');
        $this->apiUrl = $this->config[$this->status]['endpoint'];

        $this->xml = new DOMDocument('1.0', 'utf-8');
        $this->xml->preserveWhiteSpace = false;
        $this->xml->formatOutput = false;
    }

    /**
     * @throws DOMException
     * @throws Exception
     */
    private function callApi($method, $payload)
    {
        $envelope = $this->xml->createElement('soapenv:Envelope');
        $envelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
        $envelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:ser', 'http://service.ws.mt.ibbl');
        $envelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsd', 'http://bean.ws.mt.ibbl/xsd');
        $envelope->appendChild($this->xml->createElement('soapenv:Header'));

        $envelopeBody = $this->xml->createElement('soapenv:Body');

        $envelopeBody->appendChild($payload);

        $envelope->appendChild($envelopeBody);

        $this->xml->appendChild($envelope);

        $xmlResponse = Http::soap($this->apiUrl, $method, $this->xml->saveXML())->body();

        $response = Utility::parseXml($xmlResponse);

        return $response['Envelope']['Body'] ?? [];

    }

    private function connectionErrorResponse(array $response): AssignVendorVerdict
    {
        $verdict = AssignVendorVerdict::make([
            'status' => 'false',
            'original' => $response,
            'amount' => '0',
        ]);

        if (isset($response['Fault'])) {
            $verdict->message($response['Fault']['faultstring'])
                ->orderTimeline('(Islami Bank) reported error: ' . strtolower($response['Fault']['faultstring']), 'error');
        }

        return $verdict;
    }

    private function apiErrorResponse(mixed $response, string $error): AssignVendorVerdict
    {

        $errorResponse = json_decode(
            preg_replace(
                '/(.+)\|([0-9]{4})/',
                '{"status": "$1", "amount": "0", "code": $2}',
                $error),
            true);

        if ($errorResponse['code']) {
            $errorResponse['message'] = ucwords(strtolower(self::ERROR_MESSAGES[$errorResponse['code']] ?? $error));
            unset($errorResponse['code']);
        }

        return AssignVendorVerdict::make([
            ...$errorResponse,
            'original' => $response,
        ]);
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
            <ser:userID>' . $this->config[$this->status]['username'] . '</ser:userID>
            <ser:password>' . $this->config[$this->status]['password'] . '</ser:password>
        ';
        //$xmlString .= '<ser:transRefNo>'.($data['transaction_reference_number'] ?? null).'</ser:transRefNo>';
        $xmlString .= '<ser:wsMessage>';
        $xmlString .= '<xsd:amount>' . ($importOrPushRemittance['amount'] ?? null) . '</xsd:amount>';
        $xmlString .= '<xsd:isoCode>' . ($importOrPushRemittance['isoCode'] ?? null) . '</xsd:isoCode>';
        $xmlString .= '<xsd:beneficiaryAddress>' . ($importOrPushRemittance['beneficiaryAddress'] ?? null) . '</xsd:beneficiaryAddress>';
        $xmlString .= '<xsd:beneficiaryBankCode>' . ($importOrPushRemittance['beneficiaryBankCode'] ?? null) . '</xsd:beneficiaryBankCode>';
        $xmlString .= '<xsd:beneficiaryBankName>' . ($importOrPushRemittance['beneficiaryBankName'] ?? null) . '</xsd:beneficiaryBankName>';
        $xmlString .= '<xsd:beneficiaryBranchCode>' . ($importOrPushRemittance['beneficiaryBranchCode'] ?? null) . '</xsd:beneficiaryBranchCode>';
        $xmlString .= '<xsd:beneficiaryBranchName>' . ($importOrPushRemittance['beneficiaryBranchName'] ?? null) . '</xsd:beneficiaryBranchName>';
        $xmlString .= '<xsd:beneficiaryName>' . ($importOrPushRemittance['beneficiaryName'] ?? null) . '</xsd:beneficiaryName>';
        $xmlString .= '<xsd:beneficiaryPassportNo>' . ($importOrPushRemittance['beneficiaryPassportNo'] ?? null) . '</xsd:beneficiaryPassportNo>';
        $xmlString .= '<xsd:beneficiaryPhoneNo>' . ($importOrPushRemittance['beneficiaryPhoneNo'] ?? null) . '</xsd:beneficiaryPhoneNo>';
        $xmlString .= '<xsd:creatorID>' . ($importOrPushRemittance['creatorID'] ?? null) . '</xsd:creatorID>';
        $xmlString .= '<xsd:exchHouseSwiftCode>' . ($importOrPushRemittance['exchHouseSwiftCode'] ?? null) . '</xsd:exchHouseSwiftCode>';
        $xmlString .= '<xsd:identityDescription>' . ($importOrPushRemittance['identityDescription'] ?? null) . '</xsd:identityDescription>';
        $xmlString .= '<xsd:identityType>' . ($importOrPushRemittance['identityType'] ?? null) . '</xsd:identityType>';
        $xmlString .= '<xsd:issueDate>' . ($importOrPushRemittance['issueDate'] ?? null) . '</xsd:issueDate>';
        $xmlString .= '<xsd:note>' . ($importOrPushRemittance['note'] ?? null) . '</xsd:note>';
        $xmlString .= '<xsd:paymentType>' . ($importOrPushRemittance['paymentType'] ?? null) . '</xsd:paymentType>';
        $xmlString .= '<xsd:remitterAddress>' . ($importOrPushRemittance['remitterAddress'] ?? null) . '</xsd:remitterAddress>';
        $xmlString .= '<xsd:remitterIdentificationNo>' . ($importOrPushRemittance['remitterIdentificationNo'] ?? null) . '</xsd:remitterIdentificationNo>';
        $xmlString .= '<xsd:remitterName' . ($importOrPushRemittance['remitterName'] ?? null) . '</xsd:remitterName>';
        $xmlString .= '<xsd:remitterPhoneNo>' . ($importOrPushRemittance['remitterPhoneNo'] ?? null) . '</xsd:remitterPhoneNo>';
        $xmlString .= '<xsd:secretKey>' . ($importOrPushRemittance['secretKey'] ?? null) . '</xsd:secretKey>';
        $xmlString .= '<xsd:transReferenceNo>' . ($importOrPushRemittance['transReferenceNo'] ?? null) . '</xsd:transReferenceNo>';
        $xmlString .= '<xsd:transferDate>' . ($importOrPushRemittance['transferDate'] ?? null) . '</xsd:transferDate>';
        $xmlString .= '<xsd: remittancePurpose>' . ($importOrPushRemittance['remittancePurpose'] ?? null) . '</xsd: remittancePurpose >';
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

    private function __transferData(BaseModel $order): array
    {
        $data = $order->order_data;

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
        $transferData['beneficiaryAddress'] = ($data['beneficiary_data']['receiver_information']['city_name'] ?? null) . ',' . ($data['beneficiary_data']['receiver_information']['country_name'] ?? null);
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
        $transferData['isoCode'] = $order->currency;
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
//        $transferData['transReferenceNo'] = ($data['beneficiary_data']['reference_no'] ?? null);
        $transferData['transReferenceNo'] = mt_rand(1000000, 999999999);

        switch ($data['service_slug']) {
            case 'mbs_m_cash':
                $transferData['paymentType'] = 5;
                //                $transferData['beneficiaryRoutingNo'] = '?';
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
                //                $transferData['beneficiaryRoutingNo'] = '?';
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
                //                $transferData['beneficiaryRoutingNo'] = '?';
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
                //                $transferData['beneficiaryRoutingNo'] = '?';
                $transferData['beneficiaryAccType'] = ($data['beneficiary_data']['beneficiary_acc_type'] ?? 71);
                break;
            case 'cash_pickup':
                $transferData['beneficiaryAccNo'] = '';
                $transferData['paymentType'] = 1;
                //                $transferData['beneficiaryRoutingNo'] = '?';
                $transferData['beneficiaryBankCode'] = ($data['beneficiary_data']['cash_information']['vendor_code']['remit']['islamibank'] ?? null);
                $transferData['beneficiaryBankName'] = ($data['beneficiary_data']['cash_information']['bank_name'] ?? null);
                $transferData['beneficiaryBranchCode'] = ($data['beneficiary_data']['branch_information']['vendor_code']['remit']['islamibank'] ?? 123);
                $transferData['beneficiaryBranchName'] = ($data['beneficiary_data']['branch_information']['branch_name'] ?? 'Head Office Complex');
                break;
            case 'bank_transfer':
                if ($data['beneficiary_data']['bank_information']['bank_slug'] == 'islami-bank-bangladesh-limited') {
                    $transferData['beneficiaryAccType'] = ($data['beneficiary_data']['beneficiary_acc_type'] ?? 10);
                    $transferData['beneficiaryBranchCode'] = ($data['beneficiary_data']['branch_information']['vendor_code']['remit']['islamibank'] ?? null);
                    //                    $transferData['beneficiaryRoutingNo'] = '?';
                    $transferData['paymentType'] = 2;
                }
                break;
            case 'instant_bank_transfer':
                $transferData['beneficiaryAccType'] = ($data['beneficiary_data']['beneficiary_acc_type'] ?? 10);
                $transferData['beneficiaryBranchCode'] = ($data['beneficiary_data']['branch_information']['vendor_code']['remit']['islamibank'] ?? null);
                //                $transferData['beneficiaryRoutingNo'] = '?';
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
     * Verify remittance (importWSMessage)
     * Parameters: userID, password, accNo, wsMessage
     *
     * @throws Exception
     */
    public function verifyRemittance(array $data): array
    {
        $verifyRemittance = $this->__transferData($data);
        $xmlString = '
            <ser:userID>' . $this->config[$this->status]['username'] . '</ser:userID>
            <ser:password>' . $this->config[$this->status]['password'] . '</ser:password>
        ';
        //$xmlString .= '<ser:transRefNo>'.($data['transaction_reference_number'] ?? null).'</ser:transRefNo>';
        $xmlString .= '<ser:wsMessage>';
        $xmlString .= '<xsd:amount>' . ($verifyRemittance['amount'] ?? null) . '</xsd:amount>';
        $xmlString .= '<xsd:isoCode>' . ($verifyRemittance['isoCode'] ?? null) . '</xsd:isoCode>';
        $xmlString .= '<xsd:beneficiaryAddress>' . ($verifyRemittance['beneficiaryAddress'] ?? null) . '</xsd:beneficiaryAddress>';
        $xmlString .= '<xsd:beneficiaryBankCode>' . ($verifyRemittance['beneficiaryBankCode'] ?? null) . '</xsd:beneficiaryBankCode>';
        $xmlString .= '<xsd:beneficiaryBankName>' . ($verifyRemittance['beneficiaryBankName'] ?? null) . '</xsd:beneficiaryBankName>';
        $xmlString .= '<xsd:beneficiaryBranchCode>' . ($verifyRemittance['beneficiaryBranchCode'] ?? null) . '</xsd:beneficiaryBranchCode>';
        $xmlString .= '<xsd:beneficiaryBranchName>' . ($verifyRemittance['beneficiaryBranchName'] ?? null) . '</xsd:beneficiaryBranchName>';
        $xmlString .= '<xsd:beneficiaryName>' . ($verifyRemittance['beneficiaryName'] ?? null) . '</xsd:beneficiaryName>';
        $xmlString .= '<xsd:beneficiaryPassportNo>' . ($verifyRemittance['beneficiaryPassportNo'] ?? null) . '</xsd:beneficiaryPassportNo>';
        $xmlString .= '<xsd:beneficiaryPhoneNo>' . ($verifyRemittance['beneficiaryPhoneNo'] ?? null) . '</xsd:beneficiaryPhoneNo>';
        $xmlString .= '<xsd:creatorID>' . ($verifyRemittance['creatorID'] ?? null) . '</xsd:creatorID>';
        $xmlString .= '<xsd:exchHouseSwiftCode>' . ($verifyRemittance['exchHouseSwiftCode'] ?? null) . '</xsd:exchHouseSwiftCode>';
        $xmlString .= '<xsd:identityDescription>' . ($verifyRemittance['identityDescription'] ?? null) . '</xsd:identityDescription>';
        $xmlString .= '<xsd:identityType>' . ($verifyRemittance['identityType'] ?? null) . '</xsd:identityType>';
        $xmlString .= '<xsd:issueDate>' . ($verifyRemittance['issueDate'] ?? null) . '</xsd:issueDate>';
        $xmlString .= '<xsd:note>' . ($verifyRemittance['note'] ?? null) . '</xsd:note>';
        $xmlString .= '<xsd:paymentType>' . ($verifyRemittance['paymentType'] ?? null) . '</xsd:paymentType>';
        $xmlString .= '<xsd:remitterAddress>' . ($verifyRemittance['remitterAddress'] ?? null) . '</xsd:remitterAddress>';
        $xmlString .= '<xsd:remitterIdentificationNo>' . ($verifyRemittance['remitterIdentificationNo'] ?? null) . '</xsd:remitterIdentificationNo>';
        $xmlString .= '<xsd:remitterName' . ($verifyRemittance['remitterName'] ?? null) . '</xsd:remitterName>';
        $xmlString .= '<xsd:remitterPhoneNo>' . ($verifyRemittance['remitterPhoneNo'] ?? null) . '</xsd:remitterPhoneNo>';
        $xmlString .= '<xsd:secretKey>' . ($verifyRemittance['secretKey'] ?? null) . '</xsd:secretKey>';
        $xmlString .= '<xsd:transReferenceNo>' . ($verifyRemittance['transReferenceNo'] ?? null) . '</xsd:transReferenceNo>';
        $xmlString .= '<xsd:transferDate>' . ($verifyRemittance['transferDate'] ?? null) . '</xsd:transferDate>';
        $xmlString .= '<xsd: remittancePurpose>' . ($verifyRemittance['remittancePurpose'] ?? null) . '</xsd: remittancePurpose >';
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
     * Fetch Remittance Status (fetchWSMessageStatus)
     *
     * Fetch Remittance Status. You can also check the current status of your
     * remittance whether your remittance has been paid or not.
     *
     * Parameters: userID, password, transaction_reference_number, secret_key
     *
     * @throws Exception
     */
    public function orderStatus(BaseModel $order): mixed
    {
        $xmlString = '
            <ser:userID>' . $this->config[$this->status]['username'] . '</ser:userID>
            <ser:password>' . $this->config[$this->status]['password'] . '</ser:password>
        ';
        $xmlString .= '<ser:transRefNo>' . ($data['transaction_reference_number'] ?? null) . '</ser:transRefNo>';
        $xmlString .= '<ser:secretKey>' . ($data['secret_key'] ?? null) . '</ser:secretKey>';
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

    /**
     * @param Model|BaseModel $order
     *
     * @throws Exception
     */
    public function requestQuote($order): AssignVendorVerdict
    {
        return $this->vendorBalance('BDT');
    }

    /**
     * Fetch Exchange House NRT/NRD account balance (fetchBalance).
     * We are maintaining two types of account of exchange houses.
     *
     * Parameters: userID, password, currency
     *
     * @throws Exception
     */
    private function vendorBalance(string $currency): AssignVendorVerdict
    {
        $currency = trim($currency);
        $method = 'fetchBalance';
        $service = $this->xml->createElement("ser:{$method}");
        $service->appendChild($this->xml->createElement('ser:userID', $this->config[$this->status]['username']));
        $service->appendChild($this->xml->createElement('ser:password', $this->config[$this->status]['password']));
        $service->appendChild($this->xml->createElement('ser:currency', $currency));

        $response = $this->callApi($method, $service);

        if (isset($response['Fault'])) {
            return $this->connectionErrorResponse($response);
        }

        $balance = $response['fetchBalanceResponse']['return'] ?? '';

        if (str_contains($balance, 'FALSE')) {
            return $this->apiErrorResponse($response, $balance);
        }

        $balance = str_replace(',', '', $balance);

        $successResponse = json_decode(
            preg_replace(
                "/(.+)\|([0-9+.?0-9*]+)\s({$currency})/",
                '{"status": "$1", "amount": "$2", "message": "The request was successful"}',
                $balance),
            true);

        return AssignVendorVerdict::make([
            ...$successResponse,
            'original' => $response,
        ]);

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
    private function fetchAccountDetail(BaseModel $order): mixed
    {
        $accountDetail = $this->__transferData($order);

        $method = 'fetchAccountDetail';

        $service = $this->xml->createElement("ser:{$method}");
        $service->appendChild($this->xml->createElement('ser:userID', $this->config[$this->status]['username']));
        $service->appendChild($this->xml->createElement('ser:password', $this->config[$this->status]['password']));
        $service->appendChild($this->xml->createElement('ser:accNo', $accountDetail['beneficiaryAccNo'] ?? '?'));
        $service->appendChild($this->xml->createElement('ser:accType', $accountDetail['beneficiaryAccType'] ?? '?'));
        $service->appendChild($this->xml->createElement('ser:branchCode', $accountDetail['beneficiaryBankCode'] ?? '?'));

        return $this->callApi($method, $service);
    }

    /**
     * Method to make a request to the remittance service provider
     * for an execution of the order.
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
     * @throws DOMException
     */
    public function executeOrder(BaseModel $order): AssignVendorVerdict
    {
        $order_data = $order->order_data;

        $data = $this->__transferData($order);

        $method = 'directCreditWSMessage';
        $service = $this->xml->createElement("ser:{$method}");
        $service->appendChild($this->xml->createElement('ser:userID', $this->config[$this->status]['username']));
        $service->appendChild($this->xml->createElement('ser:password', $this->config[$this->status]['password']));
        $wsMessage = $this->xml->createElement('ser:wsMessage');
        //        $wsMessage->appendChild($this->xml->createElement('xsd:additionalField1', $data['additionalField1'] ?? '?'));
        //        $wsMessage->appendChild($this->xml->createElement('xsd:additionalField2', $data['additionalField2'] ?? '?'));
        //        $wsMessage->appendChild($this->xml->createElement('xsd:additionalField3', $data['additionalField3'] ?? '?'));
        //        $wsMessage->appendChild($this->xml->createElement('xsd:additionalField4', $data['additionalField4'] ?? '?'));
        //        $wsMessage->appendChild($this->xml->createElement('xsd:additionalField5', $data['additionalField5'] ?? '?'));
        //        $wsMessage->appendChild($this->xml->createElement('xsd:additionalField6', $data['additionalField6'] ?? '?'));
        //        $wsMessage->appendChild($this->xml->createElement('xsd:additionalField7', $data['additionalField7'] ?? '?'));
        //        $wsMessage->appendChild($this->xml->createElement('xsd:additionalField8', $data['additionalField8'] ?? '?'));
        //        $wsMessage->appendChild($this->xml->createElement('xsd:additionalField9', $data['additionalField9'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:amount', $data['amount'] ?? '?'));
        //        $wsMessage->appendChild($this->xml->createElement('xsd:batchID', $data['batchID'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:beneficiaryAccNo', $data['beneficiaryAccNo'] ?? '?'));
        //        $wsMessage->appendChild($this->xml->createElement('xsd:beneficiaryAccType', $data['beneficiaryAccType'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:beneficiaryAddress', $data['beneficiaryAddress'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:beneficiaryBankCode', $data['beneficiaryBankCode'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:beneficiaryBankName', $data['beneficiaryBankName'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:beneficiaryBranchCode', $data['beneficiaryBranchCode'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:beneficiaryBranchName', $data['beneficiaryBranchName'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:beneficiaryName', $data['beneficiaryName'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:beneficiaryPassportNo', $data['beneficiaryPassportNo'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:beneficiaryPhoneNo', $data['beneficiaryPhoneNo'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:beneficiaryRoutingNo', $data['beneficiaryRoutingNo'] ?? '?'));
        //        $wsMessage->appendChild($this->xml->createElement('xsd:exHouseTxID', $data['exHouseTxID'] ?? '?'));
        //        $wsMessage->appendChild($this->xml->createElement('xsd:exchHouseBranchCode', $data['exHouseBranchCode'] ?? '?'));
        //        $wsMessage->appendChild($this->xml->createElement('xsd:exchHouseSwiftCode', $data['exHouseSwiftCode'] ?? '?'));
        //        $wsMessage->appendChild($this->xml->createElement('xsd:identityDescription', $data['identityDescription'] ?? '?'));
        //        $wsMessage->appendChild($this->xml->createElement('xsd:identityType', $data['identityType'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:isoCode', $data['isoCode'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:issueDate', $data['issueDate'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:note', $data['note'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:orderNo', $data['orderNo'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:paymentType', $data['paymentType'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:remittancePurpose', $data['remittancePurpose'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:remitterAddress', $data['remitterAddress'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:remitterCountry', $data['remitterCountry'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:remitterIdentificationNo', $data['remitterIdentificationNo'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:remitterName', $data['remitterName'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:remitterPassportNo', $data['remitterPassportNo'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:remitterPhoneNo', $data['remitterPhoneNo'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:secretKey', $data['secretKey'] ?? '?'));
        $wsMessage->appendChild($this->xml->createElement('xsd:transReferenceNo', $data['transReferenceNo'] ?? '?'));
        $service->appendChild($wsMessage);

        $response = $this->callApi($method, $service);

        if (isset($response['Fault'])) {
            return $this->connectionErrorResponse($response);
        }

        $balance = $response['fetchBalanceResponse']['return'] ?? '';

        if (str_contains($balance, 'FALSE')) {
            return $this->apiErrorResponse($response, $balance);
        }

        $orderInfo = json_decode(
            preg_replace(
                '/(.+)\|([0-9]{4})/',
                '{"status":"$1", "code":$2, "origin_message":"$0"}',
                $response),
            true);

        $orderInfo['message'] = isset($orderInfo['code']) ? self::ERROR_MESSAGES[$orderInfo['code']] : $response;

        $status = (in_array($orderInfo['code'], ['5003']))
            ? OrderStatus::Accepted->value
            : OrderStatus::AdminVerification->value;

        $order_data['vendor_data'] = $orderInfo;

        if (Transaction::order()->update($order->getKey(), ['status' => $status, 'order_data' => $order_data])) {
            $order->fresh();

            return $order;
        }

        return false;
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
     * Method to make a request to the remittance service provider
     * for a quotation of the order. that include charge, fee,
     * commission and other information related to order.
     *
     * Validate information of beneficiary wallet no. (validateBeneficiaryWallet)
     * Parameters: userID, password, walletNo, paymentType
     *
     * @throws Exception
     * @throws DOMException
     */
    public function validateWallet(array $inputs = []): WalletVerificationVerdict
    {
        $wallet = $inputs['wallet'] ?? null;

        $walletNo = Str::substr($inputs['wallet_no'], ($wallet->vendor_code['remit']['islamibank'] == '5') ? -12 : -11);

        $method = 'validateBeneficiaryWallet';
        $service = $this->xml->createElement("ser:{$method}");
        $service->appendChild($this->xml->createElement('ser:userID', $this->config[$this->status]['username']));
        $service->appendChild($this->xml->createElement('ser:password', $this->config[$this->status]['password']));
        $service->appendChild($this->xml->createElement('ser:walletNo', $walletNo));
        $service->appendChild($this->xml->createElement('ser:paymentType', $wallet->vendor_code['remit']['islamibank'] ?? ''));

        $response = $this->callApi($method, $service);

        $response = "TRUE|{$walletNo}|ABDULLAH AL MASUD|MD MOSHARRAF HOSSAIN";

        //        $response = "FALSE|3005";

        if (Str::startsWith($response, 'TRUE|')) {

            $json = json_decode(
                preg_replace(
                    '/(TRUE|FALSE)\|(\d+)\|(.+)/iu',
                    '{"status":"$1", "account_no":"$2", "account_title":"$3", "original":"$0"}',
                    $response),
                true);

            return WalletVerificationVerdict::make($json)
                ->status($json['status'] === 'TRUE')
                ->message('Wallet verification successful.')
                ->wallet($wallet);
        }

        $json = json_decode(
            preg_replace(
                '/(TRUE|FALSE)\|(\d{4})/iu',
                '{"status":"$1", "code":$2, "original":"$0"}',
                $response),
            true);

        return WalletVerificationVerdict::make()
            ->status(false)
            ->message('Wallet verification failed.')
            ->original([$json, 'message' => self::ERROR_MESSAGES[$json['code']] ?? ''])
            ->wallet($wallet);
    }
}
