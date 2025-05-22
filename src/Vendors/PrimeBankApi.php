<?php

namespace Fintech\Remit\Vendors;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use ErrorException;
use Exception;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Facades\Core;
use Fintech\Core\Supports\AssignVendorVerdict;
use Fintech\Core\Supports\Utility;
use Fintech\Remit\Contracts\MoneyTransfer;
use Fintech\Remit\Support\AccountVerificationVerdict;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use JsonException;

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

    private string $cipher = 'AES-128-ECB';

    /**
     * PrimeBankApiApiService constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->config = config('fintech.remit.providers.primebank');
        $this->status = $this->config['mode'];
        $this->apiUrl = $this->config[$this->status]['endpoint'];
        $this->secretKey = substr($this->config[$this->status]['secret_key'] ?? '', 0, 16);

        $this->client = Http::withoutVerifying()
            ->withHeaders(['encKey' => $this->secretKey])
            ->baseUrl($this->apiUrl);

        $this->token = $this->config['token'] ?? null;

        $this->expiredAt = empty($this->config['expired_at']) ? null : CarbonImmutable::parse($this->config['expired_at']);

        $this->syncAuthToken();
    }

    /**
     * @throws ConnectionException
     * @throws Exception
     */
    private function post(string $url, array $params = []): ?array
    {
        $requestBody = $this->encryptedRequest($params);

        $responseBody = $this->client
            ->withBody($requestBody, 'text/plain')
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
                'UserId' => $this->config[$this->status]['username'],
                'CorporateId' => $this->config[$this->status]['corporate_id'],
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

    /**
     * @throws Exception
     */
    private function encryptedRequest(array $payload = []): string|false|null
    {
        $plainText = json_encode($payload);

        if (! Utility::isJson($plainText)) {
            throw new JsonException('Unable to encode the data');
        }

        $cipherText = openssl_encrypt($plainText, $this->cipher, $this->secretKey, OPENSSL_RAW_DATA);

        if (empty($cipherText)) {
            throw new DecryptException('Unable to encrypt the data');
        }

        return bin2hex($cipherText);
    }

    /**
     * @throws DecryptException
     * @throws JsonException
     */
    public function decryptedResponse(string $cipherText): ?array
    {
        $cipherText = hex2bin($cipherText);

        if ($cipherText === false) {
            throw new \InvalidArgumentException('Invalid Hex Data');
        }

        $plainText = openssl_decrypt($cipherText, $this->cipher, $this->secretKey, OPENSSL_RAW_DATA);

        if (empty($plainText)) {
            throw new DecryptException('Unable to decrypt the data');
        }

        if (! Utility::isJson($plainText)) {
            throw new JsonException('Unable to decode the data');
        }

        return json_decode($plainText, true);
    }

    /**
     * @param  Model|BaseModel  $order
     */
    public function requestQuote($order): AssignVendorVerdict
    {
        return $order->defaultRequestQuoteResponse(['vendor' => class_basename(__CLASS__)]);
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

        $order_data = $order->order_data ?? [];
        $sender_data = $order_data['beneficiary_data']['sender_information'] ?? [];
        $beneficiary_data = $order_data['beneficiary_data']['receiver_information'] ?? [];
        $bank_data = $order_data['beneficiary_data']['bank_information'] ?? [];
        $branch_data = $order_data['beneficiary_data']['branch_information'] ?? [];
        $ref_number = $order_data['beneficiary_data']['reference_no'] ?? $order_data['purchase_number'];

        $transactionDetail['BeneficiaryAccountNo'] = '1101115000412';
        $transactionDetail['MandateType'] = 'BEFTN';
        $transactionDetail['PurposeCode'] = '123';
        $transactionDetail['PurposeDescription'] = 'FAMILY MAINTENANCE';
        $transactionDetail['TransactionDate'] = '05/02/2020';
        $transactionDetail['TransactionReferenceNo'] = 'TT42741108580091';
        $transactionDetail['TransferAmount'] = '6666';
        $transactionDetail['Currency'] = 'BDT';
        $transactionDetail['BankName'] = 'PRIME BANK LTD.';
        $transactionDetail['BankBranch'] = 'BARISAL';
        $transactionDetail['BranchCode'] = '';
        $transactionDetail['RoutingNumber'] = '';
        $transactionDetail['BeneBankAddress'] = '';
        $transactionDetail['CashAgentName'] = '';
        $transactionDetail['CashAgentBranch'] = '';
        $transactionDetail['CashPayOutPin'] = '';
        $transactionDetail['WalletName'] = '';
        $transactionDetail['WalletNo'] = '';
        $transactionDetail['TwoPercentageConsent'] = '';

        $remitterDetail['RemitterName'] = $sender_data['name'] ?? '';
        $remitterDetail['RemitterIDType'] = ($sender_data['profile']['id_doc']['id_vendor']['remit']['primebank'] ?? '8');
        $remitterDetail['RemitterIDNo'] = $sender_data['profile']['id_doc']['id_no'] ?? '';
        if (isset($sender_data['profile']['id_doc']['id_doc_type_id']) && $sender_data['profile']['id_doc']['id_doc_type_id'] == 'passport') {
            $remitterDetail['RemitterPassportNumber'] = $sender_data['profile']['id_doc']['id_no'] ?? '';
        }
        $remitterDetail['PassportExpiryDate'] = $sender_data['profile']['id_doc']['id_expired_at'] ?? '';
        $remitterDetail['RemitterOtherID'] = $sender_data['profile']['id_doc']['id_no'] ?? '';
        $remitterDetail['RemitterOtherIdExpDate'] = $sender_data['profile']['id_doc']['id_expired_at'] ?? '';
        $remitterDetail['RemitterAddress'] = 'lodha amara';
        $remitterDetail['RemitterZipCode'] = '123456';
        $remitterDetail['RemitterEmailID'] = '';
        $remitterDetail['RemitterMobileNo'] = '65-12345678';
        $remitterDetail['RemitterCountry'] = 'SG';
        $remitterDetail['RemitterState'] = '';
        $remitterDetail['BeneficiaryRelationship'] = 'NEPHEW';
        $remitterDetail['RemitterDob'] = '07-05-1992';
        $remitterDetail['RemitterOccupation'] = 'BUSINESS_IN_TRADING';

        $beneficiaryDetail['BeneficiaryName'] = 'UAE EXCHANGE CENTRE L.L.C.';
        $beneficiaryDetail['BeneficiaryAddress'] = 'dhaka';
        $beneficiaryDetail['BeneficiaryCountry'] = 'BD';
        $beneficiaryDetail['BeneficiaryState'] = 'BANDARBAN';
        $beneficiaryDetail['BeneficiaryZipNo'] = '111111';
        $beneficiaryDetail['BeneficiaryEmailId'] = 'abc@test.com';
        $beneficiaryDetail['BeneficiaryMobileNo'] = '880-1234567890';
        $beneficiaryDetail['BeneficiaryIDType'] = 'NATIONAL ID';
        $beneficiaryDetail['BeneficiaryIDNo'] = '';
        $beneficiaryDetail['BeneficiaryDob'] = '';

        $payload['CorporateId'] = $this->config[$this->status]['corporate_id'];
        $payload['UserId'] = $this->config[$this->status]['username'];
        $payload['Transaction'] = [
            [
                'TransactionDetails' => $transactionDetail,
                'RemitterDetails' => $remitterDetail,
                'BeneficiaryDetails' => $beneficiaryDetail,
            ],
        ];

        $params['ORDER_NO'] = $ref_number;
        $params['TRANSACTION_PIN'] = $ref_number;

        $params['TRN_DATE'] = CarbonImmutable::parse($order->created_at)->format('Y-m-d');
        $params['AMOUNT'] = currency($order->converted_amount, $order->converted_currency)->float();
        // RECEIVER
        $params['RECEIVER_NAME'] = ($beneficiary_data['beneficiary_name'] ?? null);
        $params['RECEIVER_SUB_COUNTRY_LEVEL_2'] = ($beneficiary_data['city_name'] ?? null);
        $params['RECEIVER_ADDRESS'] = ($beneficiary_data['city_name'] ?? null).','.($beneficiary_data['country_name'] ?? null);
        $params['RECEIVER_AND_SENDER_RELATION'] = $beneficiary_data['relation_name'] ?? 'Relatives';
        $params['RECEIVER_CONTACT'] = str_replace('+88', '', ($beneficiary_data['beneficiary_mobile'] ?? null));
        $params['RECIEVER_BANK_BR_ROUTING_NUMBER'] = intval($branch_data['branch_location_no'] ?? '');
        $params['RECEIVER_BANK'] = ($bank_data['bank_name'] ?? null);
        $params['RECEIVER_BANK_BRANCH'] = ($branch_data['branch_name'] ?? null);
        $params['RECEIVER_ACCOUNT_NUMBER'] = ($beneficiary_data['beneficiary_data']['bank_account_number'] ?? null);
        // SENDER
        $params['SENDER_NAME'] = ($sender_data['name'] ?? null);
        $params['SENDER_PASSPORT_NO'] = ($sender_data['profile']['id_doc']['id_no'] ?? null);
        $params['SENDER_OTHER_ID_TYPE'] = ($sender_data['profile']['id_doc']['id_vendor']['remit']['meghnabank'] ?? '8');
        $params['SENDER_OTHER_ID_NO'] = ($sender_data['profile']['id_doc']['id_no'] ?? null);
        $params['SENDER_COUNTRY'] = ($sender_data['profile']['present_address']['country_name'] ?? null);
        $params['SENDER_SUB_COUNTRY_LEVEL_2'] = ($sender_data['profile']['present_address']['city_name'] ?? null);
        //        $params['SENDER_ADDRESS_LINE'] = ($data['beneficiary_data']['sender_information']['profile']['present_address']['country_name'] ?? null);
        $params['SENDER_CONTACT'] = ($sender_data['mobile'] ?? null);
        $params['PURPOSE'] = ($sender_data['profile']['remittance_purpose']['name'] ?? 'Compensation');

        $params['TRNTP'] = match ($order_data['service_slug']) {
            'cash_pickup' => 'C',
            'bank_transfer' => 'A',
            default => null
        };

        $response = $this->post('/remitAccCrTransfer', $params);

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
