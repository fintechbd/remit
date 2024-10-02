<?php

namespace Fintech\Remit\Vendors;

use DOMDocument;
use DOMException;
use Fintech\Core\Supports\Utility;
use Illuminate\Support\Facades\Http;

trait IslamiBankAuthTrait
{
    public static $ERROR_MESSAGES = [
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

        if ($this->config['mode'] === 'sandbox') {
            $this->apiUrl = $this->config[$this->status]['endpoint'];
            $this->status = 'sandbox';

        } else {
            $this->apiUrl = $this->config[$this->status]['endpoint'];
            $this->status = 'live';
        }

        $this->xml = new DOMDocument('1.0', 'utf-8');
        $this->xml->preserveWhiteSpace = false;
        $this->xml->formatOutput = false;
    }

    /**
     * @throws DOMException
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

        return $response['Envelope']['Body'] ?? null;
    }

}
