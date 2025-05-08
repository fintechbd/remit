<?php

namespace Fintech\Remit\Vendors;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use ErrorException;
use Exception;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Facades\Core;
use Fintech\Core\Supports\AssignVendorVerdict;
use Fintech\Remit\Contracts\MoneyTransfer;
use Fintech\Remit\Support\AccountVerificationVerdict;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Encryption\Encrypter;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PrimeBankApi implements MoneyTransfer
{
    /**
     * PrimeBankApi API configuration.
     *
     * @var array
     */
    private mixed $config;

    /**
     * PrimeBankApi API Url.
     *
     * @var string
     */
    private mixed $apiUrl;

    private ?string $token;

    private ?Carbon $expiredAt;

    private string $status;

    private ?string $secretKey;

    private PendingRequest $client;

    private Encrypter $crypto;

    /**
     * PrimeBankApiApiService constructor.
     *
     * @throws ConnectionException
     */
    public function __construct()
    {
        $this->config = config('fintech.remit.providers.primebank');
        $this->status = $this->config['mode'];
        $this->apiUrl = $this->config[$this->status]['endpoint'];
        $this->secretKey = $this->config[$this->status]['secret_key'];

        $this->client = Http::withoutVerifying()
            ->baseUrl($this->apiUrl);

        $this->token = $this->config['token'] ?? null;

        $this->expiredAt = empty($this->config['expired_at']) ? null : CarbonImmutable::parse($this->config['expired_at']);

        dd($this->secretKey);

        $this->crypto = new Encrypter($this->secretKey, 'AES-128-CBC');

        $this->syncAuthToken();
    }

    /**
     * @throws ConnectionException
     * @throws ErrorException
     */
    private function post(string $url, array $params = []): array
    {
        $requestBody = $this->encryptedRequest($params);

        $responseBody = $this->client
            ->withBody($requestBody)
            ->contentType('text/plain')
            ->post($url)
            ->body();

        return $this->decryptedResponse($responseBody);
    }

    /**
     * @throws ConnectionException
     * @throws Exception
     */
    private function syncAuthToken(): void
    {
        if (! $this->token || (! $this->expiredAt || $this->expiredAt->isPast())) {

            $response = $this->post('/getToken', [
                'CorporateId' => $this->config[$this->status]['corporate_id'],
                'UserId' => $this->config[$this->status]['username'],
                'Password' => $this->config[$this->status]['password'],
            ]);

            dd($response);

            if (! empty($response['Error'])) {
                throw new \InvalidArgumentException($response['Error']);
            }

            if (! empty($response['Token'])) {
                Core::setting()->setValue('remit', 'providers.primebank.token', $response['Token'], 'string');
                Core::setting()->setValue('remit', 'providers.primebank.expired_at', \now()->format('Y-m-d H:i:s'), 'string');
            }
        }
    }

    private function encryptedRequest(array $payload = []): string
    {
        $plainText = json_encode($payload);

        return Str::upper($this->crypto->encryptString($plainText));
    }

    /**
     * @throws ErrorException
     */
    private function decryptedResponse(string $cipherText): array
    {
        try {

            return json_decode($this->crypto->decryptString($cipherText), true);

        } catch (DecryptException $e) {
            throw new ErrorException($e->getMessage());
        }
    }

    /**
     * @param  Model|BaseModel  $order
     */
    public function requestQuote($order): AssignVendorVerdict
    {
        //        $response = $this->get('/remitEnquiry', [
        //            'queryType' => 1,
        //            'confRate' => 'y',
        //        ]);

        return AssignVendorVerdict::make([
            'status' => 'true',
            'amount' => '0',
            'message' => 'The request was successful',
            'original' => $response ?? [],
            'ref_number' => $order->order_number,
        ]);
    }

    /**
     * Method to make a request to the remittance service provider
     * for an execution of the order.
     *
     *
     * @throws ErrorException
     */
    public function executeOrder(BaseModel $order): AssignVendorVerdict
    {

        /*
{
    "CorporateId": "",
    "UserId": "",
    "Transaction": [
        {
            "TransactionDetails": {
                "BeneficiaryAccountNo": "1101115000412",
                "MandateType": "BEFTN",
                "PurposeCode": "123",
                "PurposeDescription": "FAMILY MAINTENANCE",
                "TransactionDate": "05/02/2020",
                "TransactionReferenceNo": "TT42741108580091",
                "TransferAmount": "6666",
                "Currency": "BDT",
                "BankName": "PRIME BANK LTD.",
                "BankBranch": "BARISAL",
                "BranchCode": "",
                "RoutingNumber": "",
                "BeneBankAddress": "",
                "CashAgentName": "",
                "CashAgentBranch": "",
                "CashPayOutPin": "",
                "WalletName": "",
                "WalletNo": "",
                "TwoPercentageConsent": ""
            },
            "RemitterDetails": {
                "RemitterName": "GEETA SHUKLA",
                "RemitterIDType": "LONG TERM PASS",
                "RemitterIDNo": "435435",
                "RemitterPassportNumber": "",
                "PassportExpiryDate": "",
                "RemitterOtherID": "",
                "RemitterOtherIdExpDate": "",
                "RemitterAddress": "lodha amara",
                "RemitterZipCode": "123456",
                "RemitterEmailID": "",
                "RemitterMobileNo": "65-12345678",
                "RemitterCountry": "SG",
                "RemitterState": "",
                "BeneficiaryRelationship": "NEPHEW",
                "RemitterDob": "07-05-1992",
                "RemitterOccupation": "BUSINESS_IN_TRADING"
            },
            "BeneficiaryDetails": {
                "BeneficiaryName": "UAE EXCHANGE CENTRE L.L.C.",
                "BeneficiaryAddress": "dhaka",
                "BeneficiaryCountry": "BD",
                "BeneficiaryState": "BANDARBAN",
                "BeneficiaryZipNo": "111111",
                "BeneficiaryEmailId": "abc@test.com",
                "BeneficiaryMobileNo": "880-1234567890",
                "BeneficiaryIDType": "NATIONAL ID",
                "BeneficiaryIDNo": "",
                "BeneficiaryDob": ""
            }
        }
    ]
}
         */

        $order_data = $order->order_data ?? [];

        $ref_number = $order_data['beneficiary_data']['reference_no'] ?? $order_data['purchase_number'];
        $params['ORDER_NO'] = $ref_number;
        $params['TRANSACTION_PIN'] = $ref_number;
        $params['TRN_DATE'] = (date('Y-m-d', strtotime($order_data['created_at'])) ?? null);
        $params['AMOUNT'] = round(floatval($order_data['sending_amount'] ?? $order->converted_amount), 2);
        // RECEIVER
        $params['RECEIVER_NAME'] = ($order_data['beneficiary_data']['receiver_information']['beneficiary_name'] ?? null);
        $params['RECEIVER_SUB_COUNTRY_LEVEL_2'] = ($order_data['beneficiary_data']['receiver_information']['city_name'] ?? null);
        $params['RECEIVER_ADDRESS'] = ($order_data['beneficiary_data']['receiver_information']['city_name'] ?? null).','.($order_data['beneficiary_data']['receiver_information']['country_name'] ?? null);
        $params['RECEIVER_AND_SENDER_RELATION'] = $order_data['beneficiary_data']['receiver_information']['relation_name'] ?? 'Relatives';
        $params['RECEIVER_CONTACT'] = str_replace('+88', '', ($order_data['beneficiary_data']['receiver_information']['beneficiary_mobile'] ?? null));
        $params['RECIEVER_BANK_BR_ROUTING_NUMBER'] = intval($order_data['beneficiary_data']['branch_information']['branch_location_no'] ?? '');
        $params['RECEIVER_BANK'] = ($order_data['beneficiary_data']['bank_information']['bank_name'] ?? null);
        $params['RECEIVER_BANK_BRANCH'] = ($order_data['beneficiary_data']['branch_information']['branch_name'] ?? null);
        $params['RECEIVER_ACCOUNT_NUMBER'] = ($order_data['beneficiary_data']['receiver_information']['beneficiary_data']['bank_account_number']);
        // SENDER
        $params['SENDER_NAME'] = ($order_data['beneficiary_data']['sender_information']['name'] ?? null);
        $params['SENDER_PASSPORT_NO'] = ($order_data['beneficiary_data']['sender_information']['profile']['id_doc']['id_no'] ?? null);
        $params['SENDER_OTHER_ID_TYPE'] = ($order_data['beneficiary_data']['sender_information']['profile']['id_doc']['id_vendor']['remit']['meghnabank'] ?? '8');
        $params['SENDER_OTHER_ID_NO'] = ($order_data['beneficiary_data']['sender_information']['profile']['id_doc']['id_no'] ?? null);
        $params['SENDER_COUNTRY'] = ($order_data['beneficiary_data']['sender_information']['profile']['present_address']['country_name'] ?? null);
        $params['SENDER_SUB_COUNTRY_LEVEL_2'] = ($order_data['beneficiary_data']['sender_information']['profile']['present_address']['city_name'] ?? null);
        //        $params['SENDER_ADDRESS_LINE'] = ($data['beneficiary_data']['sender_information']['profile']['present_address']['country_name'] ?? null);
        $params['SENDER_CONTACT'] = ($order_data['beneficiary_data']['sender_information']['mobile'] ?? null);
        $params['PURPOSE'] = ($order_data['beneficiary_data']['sender_information']['profile']['remittance_purpose']['name'] ?? 'Compensation');

        $params['TRNTP'] = match ($order_data['service_slug']) {
            'cash_pickup' => 'C',
            'bank_transfer' => 'A',
            default => null
        };

        $response = $this->post('/sendTransaction', $params);

        $response = array_shift($response);

        if (empty($response['Code']) && isset($response['code'])) {
            $response['Code'] = $response['code'];
            unset($response['code']);
        }

        if (empty($response['Message']) && isset($response['message'])) {
            $response['Message'] = $response['message'];
            unset($response['message']);
        }

        if (! empty($response['missing_field'])) {
            $response['Message'] = ' ['.implode(',', $response['missing_field']).']';
        }

        $verdict = AssignVendorVerdict::make([
            'original' => $response,
            'ref_number' => $ref_number,
            'message' => $response['Message'] ?? null,
            'amount' => $params['AMOUNT'],
        ]);

        if (in_array($response['Code'], ['0001', '0002'])) {
            $verdict->status('true')
                ->orderTimeline("(Meghna Bank) responded code: {$response['Code']}, message: ".strtolower($response['Message']).'.');
        } else {
            $verdict->status('false')
                ->orderTimeline('(Meghna Bank) reported error: '.strtolower($response['Message']).'.', 'warn');
        }

        return $verdict;
    }

    /**
     * Method to make a request to the remittance service provider
     * for the progress status of the order.
     *
     * @throws ErrorException
     */
    public function orderStatus(BaseModel $order): mixed
    {
        $order_data = $order->order_data ?? [];

        $ref_number = $order_data['beneficiary_data']['reference_no'] ?? $order_data['purchase_number'];

        $response = $this->get('/remitReport', [
            'ordpinNo' => $ref_number,
        ]);

        return array_shift($response);
    }

    /**
     * Method to make a request to the remittance service provider
     * for the progress status of the order.
     *
     * @throws ErrorException
     */
    public function trackOrder(BaseModel $order): AssignVendorVerdict
    {
        $ref_number = $order->order_data['reference_no'] ?? $order->order_data['purchase_number'];
        $response = $this->get('/remitReport', [
            'ordpinNo' => $ref_number,
        ]);

        $response = array_shift($response);

        $verdict = AssignVendorVerdict::make([
            'original' => $response,
            'ref_number' => $ref_number,
            'amount' => $order->amount,
            'charge' => $order->charge_amount,
            'discount' => $order->discount_amount,
            'commission' => $order->commission_amount,
            'status' => 'false',
        ]);

        if (isset($response['Code'])) {
            $verdict->message($response['Message'] ?? null)
                ->orderTimeline('(Meghna Bank) reported error: '.strtolower($response['Message'] ?? '').'.');

            return $verdict;
        }

        $verdict->status('true')
            ->orderTimeline('(Meghna Bank) responded with  the request was successful.');

        return $verdict;

    }

    /**
     * Method to make a request to the remittance service provider
     * for the cancellation of the order.
     *
     * @throws ErrorException
     */
    public function cancelOrder(BaseModel $order): mixed
    {
        return $this->get('/transactionTracker', [
            'orderNo' => $order->order_data['beneficiary_data']['reference_no'] ?? null,
            'queryCode' => 2,
            'info' => 'Cancelled By User',
        ]);
    }

    /**
     * Method to make a request to the remittance service provider
     * for the amendment of the order.
     *
     * @throws ErrorException
     */
    public function amendmentOrder(BaseModel $order): mixed
    {
        return $this->get('/transactionTracker', [
            'orderNo' => $order->order_data['beneficiary_data']['reference_no'] ?? null,
            'queryCode' => 1,
            'info' => 'Cancelled By User',
        ]);
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

        return AccountVerificationVerdict::make()
            ->status('TRUE')
            ->account_no($inputs['account_no'] ?? '?')
            ->account_title('')
            ->message(__('remit::messages.wallet_verification.success'))
            ->original([])
            ->wallet($bank);
    }
}
