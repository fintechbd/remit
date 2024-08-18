<?php

namespace Fintech\Remit\Vendors;

use Exception;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Remit\Contracts\MoneyTransfer;
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
            ->contentType('application/json')
            ->withBasicAuth($this->config[$this->status]['user'], $this->config[$this->status]['password'])
            ->withHeaders([
                'bankid' => $this->config[$this->status]['bankid'],
                'agent' => $this->config[$this->status]['agent'],
            ]);
    }

    private function get(string $url, array $params = []): array
    {
        $response = $this->client->get($url, $params)->json();

        return [
            'status' => $response['status'] ?? true,
            'message' => $response,
        ];
    }

    private function post(string $url, array $params = []): array
    {
        $response = $this->client->withBody(base64_encode(json_encode($params)))
            ->post($url)->json();

        return [
            'status' => $response['status'] ?? true,
            'message' => $response,
        ];
    }

    /**
     * @throws Exception
     */
    public function amendment(array $data): array
    {
        $url = 'transactionTracker?';
        $params['orderNo'] = ($data['beneficiary_data']['reference_no'] ?? null);
        $params['queryCode'] = 1;
        $params['info'] = 'AMENDMENT INFO';
        $response = $this->getData($url, $params);

        return $response;
    }

    /**
     * @throws Exception
     */
    public function cancellation(array $data): array
    {
        $url = 'transactionTracker?';
        $params['orderNo'] = ($data['beneficiary_data']['reference_no'] ?? null);
        $params['queryCode'] = 2;
        $params['info'] = 'CANCELLATION PURPOSE';
        $response = $this->getData($url, $params);

        return $response;
    }

    /**
     * Transaction report
     * Order NO / Pin No wise query
     *
     * @throws Exception
     */
    public function orderNumberWiseTransactionReport(array $data): array
    {
        $url = 'remitReport?';
        $params['ordpinNo'] = ($data['beneficiary_data']['reference_no'] ?? null);
        $response = $this->getData($url, $params);

        return $response;
    }

    /**
     * Transaction report
     * Date wise query
     *
     * @throws Exception
     */
    public function dateWiseTransactionReport($fromDate, $toDate): array
    {
        $url = 'remitDwise?';
        $params['fromDate'] = $fromDate;
        $params['toDate'] = $toDate;
        $response = $this->getData($url, $params);

        return $response;
    }

    /**
     * Balance, Treasury Deal& Amendment Enquiry
     * Treasury Deal confirmation (Exchange House)
     * Current rate=1, Balance enquiry=2, Amendment enquiry=3 & Cancellation enquiry=4
     * Value will be y/n it’s means y=yes,n=no
     *
     * @throws Exception
     */
    public function enquiry(int $queryType = 1, string $confRate = 'y'): array
    {
        $url = 'remitEnquiry?';
        $params['queryType'] = $queryType;
        $params['confRate'] = $confRate;
        $response = $this->getData($url, $params);

        return $response;
    }

    /**
     * Balance, Treasury Deal& Amendment Enquiry Code List
     */
    private function __enquiryCode(?int $code = null): string
    {
        $return = [
            1 => 'Current rate',
            2 => 'Balance enquiry',
            3 => 'Amendment enquiry',
            4 => 'Cancellation enquiry',
        ];

        if (is_null($code) || $code <= 0) {
            $returnEnquiryCode = $return;
        } else {
            $returnEnquiryCode = $return[$code];
        }

        return $returnEnquiryCode;
    }

    /**
     * Bank-wise Branch Routing Number List Find
     *
     * @throws Exception
     */
    public function bankWiseBranchRoutingNumberListFind(int $bankCode): array
    {
        $url = 'routings?';
        $params['bankCode'] = $bankCode;
        $response = $this->get($url, $params);

        return $response;
    }

    /**
     * Beneficiary Identity Type Code
     */
    private function __beneficiaryIdentityTypeCode(int $code): string
    {
        $return = [
            1 => 'National ID',
            2 => 'Passport',
            3 => 'Driving License',
            4 => 'Government Official ID',
            5 => 'Birth Certificate & Other ID',
            6 => 'Arm force ID',
            7 => 'Work Permit',
            8 => 'Other ID',
        ];

        return $return[$code];
    }

    /**
     * Status Code List
     */
    private function __statusCodeList(string $code): string
    {
        $return = [
            0 => 'Unpaid',
            1 => 'Paid',
            2 => 'Unprocessed',
            3 => 'Return',
            4 => 'Amendment',
            5 => 'Cancelled',
            6 => 'Pending Cancellation',
            null => 'Error',
        ];

        return $return[$code];
    }

    /**
     * Response Status Code List
     */
    private function __responseCodeList(string $code): string
    {
        $return = [
            '0000' => 'Mandatory field(s) missing',
            '0001' => 'Successfully insert cash',
            '0002' => 'Transaction Paid Successfully',
            '0003' => 'Transaction Failed',
            '0004' => 'Duplicate order number found',
            '0091' => 'The routing number must be integer value!',
            '0092' => 'Wrong routing number format',
            '0070' => 'Data not found \n No found against the provided value',
            '0030' => 'Length violation \n Order number length not longer than 25 characters',
            '0051' => 'Inquiry value does not match',
            '0052' => 'Order Number or Pin number does not match',
            '0050' => 'Missing data type or field empty',
            '404' => 'Object not found',
            '0044' => 'Amount field value must be numeric type',
            '0045' => 'Transaction type field value must be A (Bank Deposit) or C(Cash)',
            '0046' => 'Transaction date field value must be Y-m-d format',
            '0047' => 'Mobile number must be integer & within 11 digit',
            '0011' => 'The request resource does not support',
            '0068' => 'Bank code must be an integer',
            '0012' => 'The request resource does not support HTTP method',
            '0013' => 'Please provide bank code \n Invalid Bank Code/Empty bank code',
            '0022' => 'Unauthorized Access \n Invalid User Credential (IP, username, password, etc.)',
            '0010' => 'Fund Limit crossed \n Not Enough Fund to Process This Transaction',
            'CBS-0001' => 'CBS Transaction Already Made \n Already paid through CBS',
            'CBS-0002' => 'Failed to Process Transaction for BEFTN Register',
            'CBS-0003' => 'Failed to Transfer Amount from NRTA to BEFTN GL',
            'CBS-0004' => 'Failed to Transfer Amount From BEFTN GL to BB GL',
            'CBS-0005' => 'Failed to Transfer Amount From ONLINE GL to CUSTOMER AC',
            'CBS-0006' => 'Failed to Transfer Amount from NRTA to ONLINE GL',
            'CBS-0007' => 'Invalid Account No Found in CBS',
        ];

        return $return[$code];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model|\Fintech\Core\Abstracts\BaseModel  $order
     */
    public function requestQuote($order): mixed
    {
        return $this->get('/remitEnquiry', [
            'queryType' => 2,
        ]);
    }

    /**
     * Method to make a request to the remittance service provider
     * for an execution of the order.
     *
     *
     * @throws \ErrorException
     */
    public function executeOrder(BaseModel $order): mixed
    {
        $data = $order->order_data ?? [];

        $params['ORDER_NO'] = $data['beneficiary_data']['reference_no'] ?? null;
        $params['TRANSACTION_PIN'] = $data['beneficiary_data']['reference_no'] ?? null;
        $params['TRN_DATE'] = (date('Y-m-d', strtotime($data['created_at'])) ?? null);
        $params['AMOUNT'] = floatval($order->converted_amount ?? ($data['sending_amount'] ?? null));
        //RECEIVER
        $params['RECEIVER_NAME'] = ($data['beneficiary_data']['receiver_information']['beneficiary_name'] ?? null);
        $params['RECEIVER_SUB_COUNTRY_LEVEL_2'] = ($data['beneficiary_data']['receiver_information']['city_name'] ?? null);
        $params['RECEIVER_ADDRESS'] = ($data['beneficiary_data']['receiver_information']['city_name'] ?? null).','.($data['beneficiary_data']['receiver_information']['country_name'] ?? null);
        $params['RECEIVER_AND_SENDER_RELATION'] = $data['trest'] ?? null;
        $params['RECEIVER_CONTACT'] = str_replace('+880', '', ($data['beneficiary_data']['receiver_information']['beneficiary_mobile'] ?? null));
        $params['RECIEVER_BANK_BR_ROUTING_NUMBER'] = ($data['beneficiary_data']['branch_information']['branch_data']['location_no'] ?? '');
        $params['RECEIVER_BANK'] = ($data['beneficiary_data']['bank_information']['bank_name'] ?? null);
        $params['RECEIVER_BANK_BRANCH'] = ($data['beneficiary_data']['branch_information']['branch_name'] ?? null);
        $params['RECEIVER_ACCOUNT_NUMBER'] = ($data['beneficiary_data']['receiver_information']['beneficiary_data']['bank_account_number']);

        //SENDER
        $params['SENDER_NAME'] = ($data['beneficiary_data']['sender_information']['name'] ?? null);
        $params['SENDER_PASSPORT_NO'] = ($data['beneficiary_data']['sender_information']['profile']['id_doc']['id_no'] ?? null);
        $params['SENDER_OTHER_ID_TYPE'] = ($data['beneficiary_data']['sender_information']['profile']['id_doc']['id_vendor']['remit']['meghna_bank'] ?? null);
        $params['SENDER_COUNTRY'] = ($data['beneficiary_data']['sender_information']['profile']['present_address']['country_name'] ?? null);
        $params['SENDER_SUB_COUNTRY_LEVEL_2'] = ($data['beneficiary_data']['sender_information']['profile']['present_address']['city_name'] ?? null);
        $params['SENDER_ADDRESS_LINE'] = ($data['beneficiary_data']['sender_information']['profile']['present_address']['country_name'] ?? null);
        $params['SENDER_CONTACT'] = ($data['beneficiary_data']['sender_information']['mobile'] ?? null);
        $params['PURPOSE'] = ($data['beneficiary_data']['sender_information']['profile']['remittance_purpose']['name'] ?? 'Testing');

        //Transaction Type(A=Account,C=Cash)
        switch ($data['service_slug']) {
            case 'cash_pickup':
                $params['TRNTP'] = 'C';
                break;
            case 'bank_transfer':
                $params['TRNTP'] = 'A';
            default:
                //code block
        }

        return $this->post('/remitAccCrTransfer', $params);

    }

    /**
     * Method to make a request to the remittance service provider
     * for the progress status of the order.
     *
     * @throws \ErrorException
     */
    public function orderStatus(BaseModel $order): mixed
    {
        return $this->get('/remitReport', [
            'ordpinNo' => $order->order_data['beneficiary_data']['reference_no'] ?? null,
        ]);
    }

    /**
     * Method to make a request to the remittance service provider
     * for the cancellation of the order.
     *
     * @throws \ErrorException
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
     * @throws \ErrorException
     */
    public function amendmentOrder(BaseModel $order): mixed
    {
        return $this->get('/transactionTracker', [
            'orderNo' => $order->order_data['beneficiary_data']['reference_no'] ?? null,
            'queryCode' => 1,
            'info' => 'Cancelled By User',
        ]);
    }
}
