<?php

namespace Fintech\Remit\Vendors;

use ErrorException;
use Exception;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Enums\Transaction\OrderType;
use Fintech\Core\Supports\AssignVendorVerdict;
use Fintech\Core\Supports\Utility;
use Fintech\Remit\Contracts\MoneyTransfer;
use Fintech\Remit\Contracts\WalletTransfer;
use Fintech\Remit\Support\AccountVerificationVerdict;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AgraniBankApi implements MoneyTransfer, WalletTransfer
{
    public const ERROR_MESSAGES = [
        200 => 'TRANSACTION SUCCESSFUL',
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
        423 => 'RATE VALUE 0',
        424 => 'REMIT AMOUNT DEST VALUE 0',
        425 => 'REM TEL CONTAIN CHARS',
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

    public \DOMDocument $xml;

    /**
     * Agrani Bank configuration.
     */
    private array $config;

    private string $apiUrl;

    private string $status = 'sandbox';

    /**
     * Agrani Bank Constructor
     *
     * @throws \DOMException
     * @throws Exception
     */
    public function __construct()
    {
        $this->config = config('fintech.remit.providers.agranibank');
        $this->status = config('fintech.remit.providers.agranibank.mode');
        $this->apiUrl = $this->config[$this->status]['endpoint'];

        if (! extension_loaded('dom')) {
            throw new Exception('PHP DOM extension not installed.');
        }

        if (! extension_loaded('openssl')) {
            throw new Exception('PHP OpenSSL extension not installed.');
        }

        $this->xml = new \DOMDocument('1.0', 'utf-8');
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
     * Return Password from config
     *
     * @throws FileNotFoundException
     */
    private function sslPrivateKeyContent(): ?string
    {
        $filepath = $this->config[$this->status]['private_key'];

        if (! is_file($filepath)) {
            throw new FileNotFoundException("SSL Private key File does not exists in [$filepath].");
        }

        return file_get_contents($filepath);
    }

    /**
     * @throws ConnectionException
     * @throws \DOMException
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
     * @throws \DOMException
     * @throws Exception
     */
    private function post($url, $payload): ?array
    {
        try {
            $request = $this->preparePayload($payload);

            $response = Http::soap($this->apiUrl.$url, '', $request, [
                'Username' => $this->username(),
                'Expassword' => $this->password(),
            ])->body();

            if (Utility::isJson($response)) {
                $response = json_decode($response, true);
                if (isset($response['errorCode'])) {
                    $response['status'] = 'false';
                    $response['errorMessage'] = self::ERROR_MESSAGES[$response['errorCode']] ?? 'Unknown error';
                }

                return $response;
            }

            return Str::contains($response, '<!doctype html>', true)
                ? $this->parseHtml($response)
                : Utility::parseXml($response);
        } catch (\Exception $e) {
            return ['status' => 'FALSE', 'message' => $e->getMessage()];
        }
    }

    /**
     * @throws \DOMException
     */
    private function parseHtml(string $response): array
    {
        $html = new \DOMDocument;

        $html->loadHTML($response);

        $xpath = new \DOMXPath($html);

        $message = trim(strip_tags($xpath->query('/html/body/h1[1]')->item(0)?->textContent ?? 'Internal Server Error'));

        $description = str_replace('Description ', '', trim(strip_tags($xpath->query('/html/body/p[2]')->item(0)?->textContent ?? 'Something went wrong')));

        return ['status' => 'FALSE', 'message' => "$message ($description)"];
    }

    /**
     * @throws \DOMException
     */
    private function preparePayload($payload): string
    {
        $this->xml->appendChild($payload);

        return $this->xml->saveXML();
    }

    /**
     * @throws Exception
     */
    private function encryptSignature(array $transferData = []): string
    {
        $plainText = $transferData['tranno'];
        $plainText .= $transferData['trmode'];
        $plainText .= $transferData['benename'];
        $plainText .= $transferData['remfname'];
        $plainText .= $transferData['beneaccountno'];
        $plainText .= $transferData['branchcode'];
        $plainText .= $transferData['benetel'];
        $plainText .= $transferData['entereddatetime'];

        $signature = '';

        if (! openssl_sign($plainText, $signature, $this->sslPrivateKeyContent(), OPENSSL_ALGO_SHA256)) {
            throw new Exception('Unable to sign message');
        }

        return base64_encode($signature);
    }

    private function connectionException(array $response): AssignVendorVerdict
    {
        $verdict = AssignVendorVerdict::make([
            'status' => 'false',
            'original' => $response,
            'amount' => '0',
        ]);

        $verdict->message($response['message'])
            ->orderTimeline('(Agrani Bank) reported error: '.strtolower($response['message']), 'warn');

        return $verdict;
    }

    /**
     * @param  Model|BaseModel  $order
     *
     * @throws \DOMException
     */
    public function requestQuote($order): AssignVendorVerdict
    {
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
        $transferData = $this->__transferData($order);

        $envelope = $this->xml->createElement('TrnOrder');

        $header = $this->xml->createElement('Header');
        $header->appendChild($this->xml->createElement('excode', $this->excode()));
        $header->appendChild($this->xml->createElement('entereddatetime', $transferData['entereddatetime']));
        $header->appendChild($this->xml->createElement('Username', $this->username()));
        $header->appendChild($this->xml->createElement('Expassword', $this->password()));

        $transaction = $this->xml->createElement('Transaction');
        $transaction->appendChild($this->xml->createElement('tranno', $transferData['tranno'] ?? null));
        $transaction->appendChild($this->xml->createElement('traninfosl', $transferData['traninfosl'] ?? null));
        $transaction->appendChild($this->xml->createElement('trmode', $transferData['trmode'] ?? null));
        $transaction->appendChild($this->xml->createElement('purpose', $transferData['purpose'] ?? null));
        $transaction->appendChild($this->xml->createElement('remamountsource', $transferData['remamountsource'] ?? null));
        $transaction->appendChild($this->xml->createElement('remamountdest', $transferData['remamountdest'] ?? null));
        $transaction->appendChild($this->xml->createElement('incentiveamount', $transferData['incentiveamount'] ?? null));

        $transaction->appendChild($this->xml->createElement('incentiveamountagr', $transferData['incentiveamountagr'] ?? null));
        $transaction->appendChild($this->xml->createElement('ratevalue', $transferData['ratevalue'] ?? null));
        $transaction->appendChild($this->xml->createElement('remid', $transferData['remid'] ?? null));
        $transaction->appendChild($this->xml->createElement('remfname', $transferData['remfname'] ?? null));
        $transaction->appendChild($this->xml->createElement('remlname', $transferData['remlname'] ?? '.'));
        $transaction->appendChild($this->xml->createElement('rem_tel', $transferData['rem_tel'] ?? null));
        $transaction->appendChild($this->xml->createElement('remaddress1', $transferData['remaddress1'] ?? null));
        $transaction->appendChild($this->xml->createElement('remcountry', $transferData['remcountry'] ?? null));
        $transaction->appendChild($this->xml->createElement('benename', $transferData['benename'] ?? null));
        $transaction->appendChild($this->xml->createElement('benemname', $transferData['benemname'] ?? null));
        $transaction->appendChild($this->xml->createElement('benlename', $transferData['benlename'] ?? null));
        $transaction->appendChild($this->xml->createElement('beneaccountno', $transferData['beneaccountno'] ?? null));
        $transaction->appendChild($this->xml->createElement('benetel', $transferData['benetel'] ?? null));
        $transaction->appendChild($this->xml->createElement('branchcode', $transferData['branchcode'] ?? null));
        $transaction->appendChild($this->xml->createElement('benebeftncode', $transferData['benebeftncode'] ?? null));
        $transaction->appendChild($this->xml->createElement('beneaddress', $transferData['beneaddress'] ?? null));
        $transaction->appendChild($this->xml->createElement('benecountry', $transferData['benecountry'] ?? null));
        $transaction->appendChild($this->xml->createElement('excode', $this->excode()));
        $transaction->appendChild($this->xml->createElement('entereddatetime', $transferData['entereddatetime'] ?? null));
        $transaction->appendChild($this->xml->createElement('signaturevalue', $transferData['signaturevalue'] ?? null));

        $signature = $this->xml->createElement('Signature');
        $signature->appendChild($this->xml->createElement('SignatureValue', $transferData['signaturevalue'] ?? null));

        $envelope->appendChild($header);

        $envelope->appendChild($transaction);

        $envelope->appendChild($signature);

        $response = $this->post('/clavis', $envelope);

        dd($response);

        if (isset($response['status'])) {
            return $this->connectionException($response);
        }

        $verdict = new AssignVendorVerdict([
            'original' => $response,
            'ref_number' => $transferData['tranno'],
            'amount' => $transferData['remamountdest'],
            'charge' => $order->charge_amount,
            'discount' => $order->discount_amount,
            'commission' => $order->commission_amount,
        ]);

        //        $response = $response['Response'];

        dd($response);

        //        if ($response['ResponseCode'] == 200) {
        //
        //        }

    }

    /**
     * @throws Exception
     */
    private function __transferData(BaseModel $order): array
    {
        $data = $order->order_data;

        $sender_data = $data['beneficiary_data']['sender_information'] ?? [];
        $beneficiary_data = $data['beneficiary_data']['receiver_information'] ?? [];
        $bank_data = $data['beneficiary_data']['bank_information'] ?? [];
        $branch_data = $data['beneficiary_data']['branch_information'] ?? [];

        $transferData['tranno'] = ($data['beneficiary_data']['reference_no'] ?? null);
        $transferData['traninfosl'] = ($data['purchase_number'] ?? null);
        $transferData['purpose'] = $sender_data['profile']['remittance_purpose']['vendor_code']['remit']['agranibank'] ?? '04';
        $transferData['remamountsource'] = floatval($order->amount);
        $transferData['remamountdest'] = round($data['sending_amount'] ?? '0');
        $transferData['incentiveamount'] = '0.0';
        $transferData['incentiveamountagr'] = '0.0';
        $transferData['ratevalue'] = $data['currency_convert_rate']['rate'] ?? '0';
        $transferData['remid'] = $sender_data['profile']['id_doc']['id_no'] ?? '';
        $transferData['remfname'] = $sender_data['name'] ?? null;
        $transferData['remlname'] = '';
        $transferData['rem_tel'] = '';
        $transferData['remaddress1'] = $sender_data['profile']['present_address']['city_name'] ?? null;
        $transferData['remcountry'] = $sender_data['profile']['present_address']['country_name'] ?? null;
        $transferData['beneid'] = '0';
        $transferData['benename'] = ($beneficiary_data['beneficiary_name'] ?? null);
        $transferData['benemname'] = ' ';
        $transferData['benlename'] = ' ';
        $transferData['beneaccountno'] = ($beneficiary_data['beneficiary_data']['bank_account_number'] ?? $beneficiary_data['beneficiary_data']['wallet_account_number'] ?? null);
        $transferData['benetel'] = Str::substr(($beneficiary_data['beneficiary_mobile'] ?? ''), -11);
        $transferData['branchcode'] = ($branch_data['branch_data']['location_no'] ?? '?');
        $transferData['benebeftncode'] = '';
        $transferData['beneaddress'] = implode(', ', [$beneficiary_data['city_name'] ?? '', $beneficiary_data['state_name'] ?? '']);
        $transferData['benecountry'] = '';
        $transferData['entereddatetime'] = now('Asia/Dhaka')->format('Y-m-d\TH:i:s\.v');
        $transferData['counttr'] = '0';
        $transferData['transtatus'] = '2';

        switch ($order->order_type->value) {
            case OrderType::BankTransfer->value:
                $transferData['trmode'] = ($bank_data['bank_slug'] == 'agrani-bank-ltd')
                    ? '16'
                    : '15';
                break;

            case OrderType::CashPickup->value:
                $transferData['trmode'] = '05';
                break;

            case OrderType::WalletTransfer->value:
                $transferData['trmode'] = ($bank_data['bank_slug'] == 'mfs-bkash')
                    ? '17'
                    : '5';
                break;
        }

        $transferData['signaturevalue'] = $this->encryptSignature($transferData);

        return $transferData;

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
     * @throws \DOMException
     */
    public function validateBankAccount(array $inputs = []): AccountVerificationVerdict
    {
        $bank = $inputs['bank'] ?? [];
        $bankBranch = $inputs['bank_branch'] ?? [];
        $beneficiaryAccountType = $inputs['beneficiary_account_type'] ?? [];

        $transaction = $this->xml->createElement('Transaction');
        $transaction->appendChild($this->xml->createElement('beneaccountno', $inputs['account_no'] ?? '?'));

        $response = $this->post('/t24validation', $transaction);

        if (isset($response['status'])) {
            return AccountVerificationVerdict::make([
                'status' => 'false',
                'message' => $response['message'] ?? __('remit::messages.wallet_verification.failure'),
                'original' => $response,
                'account_title' => 'N/A',
                'account_no' => 'N/A',
                'wallet' => $bank,
            ]);
        }

        $accountTitle = $response['Response']['fullname'] ?: null;

        $json['status'] = 'TRUE';
        $json['account_no'] = $inputs['account_no'] ?? null;
        $json['account_title'] = $accountTitle ?? null;
        $json['original'] = $response;

        return AccountVerificationVerdict::make($json)
            ->status('TRUE')
            ->message(__('remit::messages.wallet_verification.success'))
            ->wallet($bank);
    }

    /**
     * Method to make a request to the remittance service provider
     * for a quotation of the order. that include charge, fee,
     * commission and other information related to order.
     *
     * @throws \ErrorException
     * @throws \DOMException
     */
    public function validateWallet(array $inputs = []): AccountVerificationVerdict
    {
        $wallet = $inputs['bank'] ?? [];
        $remitter = $inputs['user'] ?? [];

        $walletNo = Str::substr($inputs['account_no'], ($wallet['vendor_code']['remit']['islamibank'] == '5') ? -12 : -11);

        $transaction = $this->xml->createElement('Transaction');
        $transaction->appendChild($this->xml->createElement('benename', ''));
        $transaction->appendChild($this->xml->createElement('benemname', ''));
        $transaction->appendChild($this->xml->createElement('benlename', ''));
        $transaction->appendChild($this->xml->createElement('benetel', $walletNo));
        $transaction->appendChild($this->xml->createElement('rem_tel', $remitter['mobile'] ?? '?'));

        $response = $this->post('/bkashvalidation', $transaction);

        if (isset($response['status'])) {
            return AccountVerificationVerdict::make([
                'status' => 'false',
                'message' => $response['message'] ?? __('remit::messages.wallet_verification.failure'),
                'original' => $response,
                'account_title' => 'N/A',
                'account_no' => 'N/A',
                'wallet' => $wallet,
            ]);
        }

        $accountTitle = $response['Response']['fullname'] ?: null;

        $json['status'] = 'TRUE';
        $json['account_no'] = $walletNo ?? null;
        $json['account_title'] = $accountTitle ?? null;
        $json['original'] = $response;

        return AccountVerificationVerdict::make($json)
            ->status('TRUE')
            ->message(__('remit::messages.wallet_verification.success'))
            ->wallet($wallet);
    }
}
