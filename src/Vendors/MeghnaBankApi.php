<?php

namespace Fintech\Remit\Vendors;

use Carbon\CarbonImmutable;
use ErrorException;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Core\Supports\AssignVendorVerdict;
use Fintech\Remit\Contracts\MoneyTransfer;
use Fintech\Remit\Support\AccountVerificationVerdict;
use Fintech\Remit\Vendors\Enums\MeghnaBank\QueryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class MeghnaBankApi implements MoneyTransfer
{
    /**
     * MeghnaBank API configuration.
     *
     * @var array
     */
    private mixed $config;

    /**
     * MeghnaBank API Url.
     *
     * @var string
     */
    private mixed $apiUrl;

    private string $status = 'sandbox';

    private PendingRequest $client;

    /**
     * MeghnaBankApiService constructor.
     */
    public function __construct()
    {
        $this->config = config('fintech.remit.providers.meghnabank');

        if ($this->config['mode'] === 'sandbox') {
            $this->apiUrl = $this->config[$this->status]['endpoint'];
            $this->status = 'sandbox';

        } else {
            $this->apiUrl = $this->config[$this->status]['endpoint'];
            $this->status = 'live';
        }

        $this->client = Http::withoutVerifying()
            ->baseUrl($this->apiUrl)
            ->acceptJson()
            ->withBasicAuth($this->config[$this->status]['username'], $this->config[$this->status]['password'])
            ->withHeaders([
                'bankid' => $this->config[$this->status]['bankid'],
                'agent' => $this->config[$this->status]['agent'],
            ]);
    }

    private function get(string $url, array $params = []): array
    {
        try {

            return $this->client
                ->contentType('application/json')
                ->get($url, $params)
                ->json();

        } catch (\Throwable $throwable) {

            logger()->error($throwable);

            return [];
        }
    }

    private function post(string $url, array $params = []): array
    {
        try {

            return $this->client
                ->withBody(base64_encode(json_encode($params)))
                ->contentType('text/plain')
                ->post($url)
                ->json();

        } catch (\Throwable $throwable) {

            logger()->error($throwable);

            return [];
        }
    }

    /**
     * @param  Model|BaseModel  $order
     */
    public function requestQuote($order): AssignVendorVerdict
    {
        $response = $this->get('/remitEnquiry', [
            'queryType' => QueryType::CurrentRate->value,
            'confRate' => 'y',
        ]);

        return AssignVendorVerdict::make([
            'status' => 'TRUE',
            'message' => 'The request was successful',
            'amount' => $order->amount,
            'original' => $response,
            'ref_number' => $order->order_number,
            'charge' => $order->charge_amount,
            'discount' => $order->discount_amount,
            'commission' => $order->commission_amount,
        ])
            ->orderTimeline('The requestQuote method was made internal successful', 'success');
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
