<?php

namespace Fintech\Remit\Vendors;

use DOMDocument;
use DOMException;
use ErrorException;
use Exception;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Supports\AssignVendorVerdict;
use Fintech\Core\Supports\Utility;
use Fintech\Remit\Contracts\MoneyTransfer;
use Fintech\Remit\Contracts\WalletTransfer;
use Fintech\Remit\Support\AccountVerificationVerdict;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AgraniBankApi implements MoneyTransfer, WalletTransfer
{
    public const ERROR_MESSAGES = [
        201 => 'DUPLICATE TRANSACTION NUMBER',
        202 => 'SIGN DIFFER',
        203 => 'UNHANDLED EXCEPTION',
        401 => 'INVALID EXCODE',
        402 => 'REMITTER FIRST NAME MORE THAN 96 CHARS',
        403 => 'REMITTER LAST NAME MORE THAN 26 CHARS',
        404 => 'REMITTER ADDRESS MORE THAN 256 CHARS',
        405 => 'REMITTER COUNTRY MUST 2 CHARS',
        406 => 'BENEFICIARY NAME MORE THAN 96 CHARS',
        407 => 'BENEFICIARY MIDDLE NAME MORE THAN 26 CHARS',
        408 => 'BENEFICIARY LAST NAME MORE THAN 26 CHARS',
        409 => 'BENEFICIARY ADDRESS MORE THAN 128 CHAR',
        410 => 'BENEFICIARY COUNTRY MUST 2 CHAR',
        411 => 'BRANCH CODE MUST BE 9 DIGIT',
        412 => 'BRANCH CODE IS WRONG',
        413 => 'INSUFFICIENT BALANCE',
        414 => 'BENEFICIARY A/C 64 CHARS',
        417 => 'BENEFICIARY TEL MUST 11 CHAR',
        418 => 'BENEFICIARY AC MORE THAN 5 CHAR FOR OTHER BANK ACCOUNT PAYEE(15)',
        419 => 'BENEFICIARY AC MUST 13 CHAR FOR CREDIT TO CBS(16)',
        420 => 'BENEFICIARY TEL FORMAT WRONG',
        421 => 'BENEFICIARY TEL CONTAIN CHARS',
        422 => 'ACCOUNT NO IS WRONG',
        425 => 'REM TEL CONTAIN CHARS',
        423 => 'RATE VALUE 0',
        424 => 'REMIT AMOUNT DEST VALUE 0',
        //		425 => 'ACCOUNT NO IS WRONG',
        430 => 'TRANSACTION NUMBER IS MORE THAN 20 CHARS',
        502 => 'REMITTER FIRST NAME IS NULL',
        503 => 'REMITTER LAST NAME IS NULL',
        504 => 'REMITTER ADDRESS IS NULL',
        506 => 'BENEFICIARY NAME IS NULL',
        508 => 'BENEFICIARY LAST NAME IS NULL',
        530 => 'TRANSACTION NUMBER IS NULL',
    ];

    public const STATUS_MESSAGES = [
        01 => 'PENDING',
        02 => 'PAID',
        03 => 'CANCEL',
    ];

    //    const OCCUPATION = 'OCC';
    //
    //    const SOURCE_OF_FUND = 'SOF';
    //
    //    const RELATIONSHIP = 'REL';
    //
    //    const PURPOSE_OF_REMITTANCE = 'POR';
    //
    //    const CUSTOMER_DOCUMENT_ID_TYPE = 'DOC';
    //
    //    /**
    //     * @var string|null
    //     */
    //    public $country = null;
    //
    //    /**
    //     * @var string|null
    //     */
    //    public $currency = null;
    //
    public DOMDocument $xml;

    //
    //    /**
    //     * @var array
    //     */
    //    public $transactionBody; // base64 encode of auth
    //
    /**
     * Agrani Bank configuration.
     */
    private array $config;

    private string $apiUrl;

    private string $status = 'sandbox';
    //
    //    /**
    //     * @var string|null
    //     */
    //    private $basicAuthHash = null;
    //
    //    /**
    //     * @var CatalogListService
    //     */
    //    private $catalogListService;
    //
    //    /**
    //     * @var CountryService
    //     */
    //    private $countryService;

    /**
     * Agrani Bank Constructor
     *
     * @throws DOMException
     */
    public function __construct()
    {
        $this->config = config('fintech.remit.providers.agranibank');
        $this->status = config('fintech.remit.providers.agranibank.mode');
        $this->apiUrl = $this->config[$this->status]['endpoint'];

        $this->xml = new DOMDocument('1.0', 'utf-8');
        $this->xml->preserveWhiteSpace = false;
        $this->xml->formatOutput = false;
        $this->xml->xmlStandalone = true;
    }

    /**
     * Return Excode from config
     */
    private function excode(): ?string
    {
        return $this->config[$this->status]['excode'] ?? '7106';
    }

    /**
     * Return Username from config
     */
    private function username(): ?string
    {
        return $this->config[$this->status]['username'];
    }

    /**
     * Return Password from config
     */
    private function password(): ?string
    {
        return $this->config[$this->status]['password'];
    }

    /**
     * @throws ConnectionException
     * @throws DOMException
     * @throws Exception
     */
    private function get($url, $payload)
    {
        $requestBody = $this->preparePayload($payload);

        $xmlResponse = Http::baseUrl($this->apiUrl)
            ->contentType('text/xml; charset=utf-8')
            ->accept('application/xml')
            ->withHeaders([
                'Host' => parse_url($this->apiUrl, PHP_URL_HOST),
                'Username' => $this->username(),
                'Expassword' => $this->password(),
                'Content-Length' => strlen($requestBody),
            ])
            ->withBody($requestBody, 'text/xml;charset=utf-8')
            ->get($url)
            ->body();

        $response = Utility::parseXml($xmlResponse);

        dd($response);

    }

    /**
     * @throws ConnectionException
     * @throws DOMException
     * @throws Exception
     */
    private function post($url, $payload)
    {
        $requestBody = $this->preparePayload($payload);

        $xmlResponse = Http::baseUrl($this->apiUrl)
            ->contentType('text/xml; charset=utf-8')
            ->accept('application/xml')
            ->withHeaders([
                'Host' => parse_url($this->apiUrl, PHP_URL_HOST),
                'Username' => $this->username(),
                'Expassword' => $this->password(),
                'Content-Length' => strlen($requestBody),
            ])
            ->withBody($requestBody, 'text/xml;charset=utf-8')
            ->post($url)
            ->body();

        echo $xmlResponse;
        exit();

        $response = Utility::parseXml($xmlResponse);

        dd($response);
    }

    /**
     * @throws DOMException
     */
    private function preparePayload($payload): string
    {
        $order = $this->xml->createElement('TrnOrder');

        $header = $this->xml->createElement('Header');
        $header->appendChild($this->xml->createElement('excode', $this->excode()));
        $header->appendChild($this->xml->createElement('Username', $this->username()));
        $header->appendChild($this->xml->createElement('Expassword', $this->password()));
        $header->appendChild($this->xml->createElement('entereddatetime', now()->format('Y-m-d\TH:i:s\.v')));

        $order->appendChild($header);

        $order->appendChild($payload);

        $this->xml->appendChild($order);

        return $this->xml->saveXML();
    }

    /**
     * @return string|null
     */
    public function getTransactionSignature(array $transferInfo = [])
    {
        $signature = '';
        $signature .= $transferInfo['tranno'];
        $signature .= $transferInfo['trmode'];
        $signature .= $transferInfo['benename'];
        $signature .= $transferInfo['remfname'];
        $signature .= $transferInfo['beneaccountno'];
        $signature .= $transferInfo['branchcode'];
        $signature .= $transferInfo['benetel'];
        $signature .= $transferInfo['remamountdest'];
        $signature .= $transferInfo['entereddatetime'];

        return $this->encryptSignature($signature, '');

    }

    public function encryptSignature(string $plainText, $privateKey)
    {
        /*$signature = '';

        openssl_sign($plainText,$signature, $privateKey);*/
        $key = '-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEA71FijlOQ4FfKc0VKtjc7Mv6aZmSR9oLjq3uDw/88avOSrIpw
qaTCrwtk2zsFMl1YA3hByhF+TP95E0dDwGWXk1JkLWfZ/sjep+694PtS1oiyvOB8
hTPYk6U42leHIZDldKk1eu7n0wUph5/5GfahXrHY+8qr38s/QVvG7ohh/dVs3rvs
VJ+QBZz5X9WNYcWIWsEySkyAmtppNVUuf6pwtAkCO4565cNoTk+5zCXpRxsxkgkv
HyTB+Nk4QuXibdC3slscZs75G9tc0TO7sC6lcnZFtTd4z5qk6f6LeI2/asviehc7
awOl8nm9C3VKjZlgOYIejHhx0A5+lkzFdrk+JwIDAQABAoIBAQDNaZGqkFe9+Byx
LDyggm+xqY9la9VNPbOlMPM8fAuj0UWIC5wAQIdKMAF1mwcu36f38nSluLYr6OxH
e9fPgGPF8+ZAgu8+HbPfeLBKN+42bkbcj+LRglrW/+34m0BFs1T/+W0KA53AJqIq
40iw3FxOJ2ETXjaAdLfqpZfujeluMOW+Rjhe2VpILpWurgX07kWphP09TqK3yuja
At4lBKYFQth7a773uzSy8cy0Ohl+lvRHa/oVS0SXsiLo1iA1NYpXzKi0ww3+sSnw
kfoacFImVuqm2rxikTgSu9GUkC1REoseT5FRj8uHzyBm/wXAM1lniUGr6Moh9Yl2
a9KNbj8hAoGBAPpHuaWIboumderokBXOwjWcW0EBDx1+SiR3kFHoYvpck9wAe2BM
vdva8IbFomCllq8UUGaW6vXEqVEUITwgGjZBQWeOgSWjo4p0b7o63ftZrsI+/Bl7
1KJSSR/O45DTXrfD/jxMgTbnYqxbyBCQz54Om9aq338XICsFJkPsU2GzAoGBAPTJ
hkvxxkPOEHmuNjWbxJluFtWfw1Sg5dLMrd6csPB/pczW/TZJwKCJslHH1/SA5PVm
nroSkYgJDMas5caZogvuIO5YIdVIM0SVU0Hl1wYkK4nz2Zpt1YH7peQrjx9iuiGy
UTYpjdxb4nkz6WL8Zp0AIG+5Wxm9IzYKZmpFPO+9AoGACI/fl/wc3AYrzod6NmTG
XBMnRAgHPlkNrEWy2Dp8+Femb0ZM8jRt4lGRHOsx7OB9USv+vCO5kgLSUAXCRU5L
10NQO3yyilkYxSnKkLJm2axtwBNriGumEI+EFOR9AH1apiq8Tc/IM9qik4boRzjN
AXk6d5OM5coivZYFgxlYmOUCgYEA07hqO82GWqckgNo5cOylgr9BaMuiOtRfc5As
4lpMf/coBJ/+qrHntfLjFPDwzD2fytFTgEUHMs4BCuYIZ1oCWqdAPGZl/P9RuIQf
WuPcsycdsVgEYhmVjbOGrG8wf0j5DKQaseoHFQ00OPi5aDA+4JR3eaqsLPr2NYuR
QWFZb1ECgYBYagQ16rdG/aBU0sOd745EskY9F6Z+vpQ7U1J2gA6hdqT3Pl8epSOY
k6aHAHli0D9xC3UQzJSYVIGx6PDR8q5TgADSLyPDwejCArLUpchYrFz3R1FWRs/W
egQQX++y13mrQFJVKA7RCQPWEynD29lwP2oizhGIfEiqGfJZd3pTXQ==
-----END RSA PRIVATE KEY-----';
        $certs = [];
        openssl_pkcs12_read(file_get_contents(public_path('Certificate.crt')), $certs, '1'); // where 1106@123 is your certificate password
        // dd($certs);

        // if (! $certs ) return ;
        $signature = '';
        openssl_sign($plainText, $signature, $key);

        return base64_encode($signature);
    }

    /*********************************** Transaction ***************************************/

    /******************************************* Auth *******************************************/

    public function createPfxFile()
    {
        $certificate = file_get_contents($this->config['signature']['certificate']);
        $output_filepath = $this->config['signature']['target_pfx'];
        $private_key = file_get_contents($this->config['signature']['private_key']);
        $password = $this->config['signature']['passphase'];

        openssl_pkcs12_export_to_file($certificate, $output_filepath, $private_key, $password);

    }

    /**
     * @param  Model|BaseModel  $order
     *
     * @throws DOMException
     */
    public function requestQuote($order): AssignVendorVerdict
    {
        $transaction = $this->xml->createElement('Transaction');
        $transaction->appendChild($this->xml->createElement('beneaccountno', '0200014001577'));

        $response = $this->get('/t24validation', $transaction);
        dd($response);

        return AssignVendorVerdict::make();
    }

    /**
     * Method to make a request to the remittance service provider
     * for an execution of the order.
     *
     * @throws ErrorException
     */
    public function executeOrder(BaseModel $order): AssignVendorVerdict
    {
        return AssignVendorVerdict::make();
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
    public function cancelOrder(BaseModel $order): AssignVendorVerdict
    {
        return AssignVendorVerdict::make();
    }

    /**
     * Method to make a request to the remittance service provider
     * for the amendment of the order.
     *
     * @throws ErrorException
     */
    public function amendmentOrder(BaseModel $order): AssignVendorVerdict
    {
        return AssignVendorVerdict::make();
    }

    /**
     * Method to make a request to the remittance service provider
     * for the track real-time progress of the order.
     */
    public function trackOrder(BaseModel $order): AssignVendorVerdict
    {
        return AssignVendorVerdict::make();
    }

    /**
     * Method to make a request to the remittance service provider
     * for a quotation of the order. that include charge, fee,
     * commission and other information related to order.
     *
     * @throws \ErrorException
     */
    public function validateBankAccount(array $inputs = []): AccountVerificationVerdict
    {
        $bank = $inputs['bank'] ?? [];
        $bankBranch = $inputs['bank_branch'] ?? [];
        $beneficiaryAccountType = $inputs['beneficiary_account_type'] ?? [];

        $transaction = $this->xml->createElement('Transaction');
        $transaction->appendChild($this->xml->createElement('beneaccountno', $inputs['account_no'] ?? '?'));

        $response = $this->post('/t24validation', $transaction);

        dd($response);

        return AccountVerificationVerdict::make(['original' => $response]);

        if (isset($response['Fault'])) {
            return AccountVerificationVerdict::make([
                'status' => 'false',
                'message' => $response['Fault']['faultstring'] ?? __('remit::messages.wallet_verification.failure'),
                'original' => $response,
                'account_title' => 'N/A',
                'account_no' => 'N/A',
                'wallet' => $bank,
            ]);
        }

        $response = $response["{$method}Response"]['return'] ?: '';

        if (Str::startsWith($response, 'TRUE|')) {

            $arr = explode('|', $response);
            $json['status'] = 'TRUE';
            $json['account_no'] = $arr[1] ?? null;
            $json['account_title'] = $arr[2] ?? null;
            $json['original'] = $response;

            return AccountVerificationVerdict::make($json)
                ->status($json['status'] === 'TRUE')
                ->message(__('remit::messages.wallet_verification.success'))
                ->wallet($bank);
        }

        $json = json_decode(
            preg_replace(
                '/(TRUE|FALSE)\|(\d{4})/iu',
                '{"status":"$1", "code":$2, "original":"$0"}',
                $response),
            true);

        return AccountVerificationVerdict::make()
            ->status('false')
            ->message(__('remit::messages.wallet_verification.failure'))
            ->original([$json, 'message' => self::ERROR_MESSAGES[$json['code']] ?? ''])
            ->wallet($bank);
    }

    /**
     * Method to make a request to the remittance service provider
     * for a quotation of the order. that include charge, fee,
     * commission and other information related to order.
     *
     * @throws \ErrorException
     */
    public function validateWallet(array $inputs = []): AccountVerificationVerdict
    {
        $wallet = $inputs['bank'] ?? null;

        $walletNo = Str::substr($inputs['account_no'], ($wallet['vendor_code']['remit']['islamibank'] == '5') ? -12 : -11);

        $method = 'validateBeneficiaryWallet';
        $transaction = $this->xml->createElement('Transaction');
        $transaction->appendChild($this->xml->createElement('benename', ''));
        $transaction->appendChild($this->xml->createElement('benemname', ''));
        $transaction->appendChild($this->xml->createElement('benlename', ''));
        $transaction->appendChild($this->xml->createElement('benetel', $walletNo));
        $transaction->appendChild($this->xml->createElement('rem_tel', $walletNo));

        $response = $this->post('/bkashvalidation', $transaction);
        dd($response);

        logger()->debug('Response:', [$response]);

        if (isset($response['Fault'])) {
            return AccountVerificationVerdict::make([
                'status' => 'false',
                'message' => $response['Fault']['faultstring'] ?? __('remit::messages.wallet_verification.failure'),
                'original' => $response,
                'account_title' => 'N/A',
                'account_no' => 'N/A',
                'wallet' => $wallet,
            ]);
        }

        logger()->debug('Response:', [$response]);

        $response = $response["{$method}Response"]['return'] ?: '';

        if (Str::startsWith($response, 'TRUE|')) {

            $json = json_decode(
                preg_replace(
                    '/(TRUE|FALSE)\|(\d+)\|(.+)/iu',
                    '{"status":"$1", "account_no":"$2", "account_title":"$3", "original":"$0"}',
                    $response),
                true);

            return AccountVerificationVerdict::make($json)
                ->status($json['status'] === 'TRUE')
                ->message(__('remit::messages.wallet_verification.success'))
                ->wallet($wallet);
        }

        $json = json_decode(
            preg_replace(
                '/(TRUE|FALSE)\|(\d{4})/iu',
                '{"status":"$1", "code":$2, "original":"$0"}',
                $response),
            true);

        return AccountVerificationVerdict::make()
            ->status(false)
            ->message(__('remit::messages.wallet_verification.failure'))
            ->original([$json, 'message' => self::ERROR_MESSAGES[$json['code']] ?? ''])
            ->wallet($wallet);
    }
}
