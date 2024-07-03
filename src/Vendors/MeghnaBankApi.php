<?php

namespace Fintech\Remit\Vendors;

use Exception;
use Fintech\Remit\Contracts\BankTransfer;
use Fintech\Remit\Contracts\OrderQuotation;
use Illuminate\Support\Facades\Log;

class MeghnaBankApi implements BankTransfer, OrderQuotation
{
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
    }

    /**
     * Base function that is responsible for interacting directly with the Remit Webservice api to obtain data
     *
     * @param  array  $params
     * @return array
     *
     * @throws Exception
     */
    public function getData($url, $params = [])
    {
        $apiUrl = $this->apiUrl.$url;
        $apiUrl .= http_build_query($params);
        Log::info($apiUrl);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $apiUrl);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_ENCODING, '');
        curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
        curl_setopt($curl, CURLOPT_TIMEOUT, 0);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, "'".$this->config[$this->status]['user'].":".$this->config[$this->status]['password']."'");
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
                "'bankid: ".$this->config[$this->status]['user']."'",
                "'agent: ".$this->config[$this->status]['agent']."'"
            ]
        );

        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        $error = curl_error($curl);

        if ($response === false) {
            Log::info($info);
            Log::info($error);
            throw new Exception(curl_error($curl), curl_errno($curl));
        }

        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        Log::info(json_decode($response, true));

        return [
            'status' => $status,
            'response' => json_decode($response, true),
        ];

    }

    /**
     * @param $order_number
     * @return array
     * @throws Exception
     */
    public function amendment($order_number): array
    {
        $url = 'transactionTracker?';
        $params['orderNo'] = $order_number;
        $params['queryCode'] = 1;
        $params['info'] = 'AMENDMENT INFO';
        $response = $this->getData($url, $params);

        return $response;
    }

    /**
     * @param $order_number
     * @return array
     * @throws Exception
     */
    public function cancellation($order_number): array
    {
        $url = 'transactionTracker?';
        $params['orderNo'] = $order_number;
        $params['queryCode'] = 2;
        $params['info'] = 'CANCELLATION PURPOSE';
        $response = $this->getData($url, $params);

        return $response;
    }

    /**
     * Transaction report
     * Order NO / Pin No wise query
     *
     * @param $order_number
     * @return array
     * @throws Exception
     */
    public function orderNumberWiseTransactionReport($order_number): array
    {
        $url = 'remitReport?';
        $params['ordpinNo'] = $order_number;
        $response = $this->getData($url, $params);

        return $response;
    }

    /**
     * Transaction report
     * Date wise query
     *
     * @param $fromDate
     * @param $toDate
     * @return array
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
     * @param int $queryType
     * @param string $confRate
     * @return array
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
     *
     * @param int|null $code
     * @return string
     */
    private function __enquiryCode(int $code = null): string
    {
        $return = [
            1 => 'Current rate',
            2 => 'Balance enquiry',
            3 => 'Amendment enquiry',
            4 => 'Cancellation enquiry',
        ];

        if(is_null($code) || $code <= 0){
            $returnEnquiryCode = $return;
        }else{
            $returnEnquiryCode = $return[$code];
        }
        return $returnEnquiryCode;
    }

    /**
     * Bank-wise Branch Routing Number List Find
     *
     * @param int $bankCode
     * @return array
     * @throws Exception
     */
    public function bankWiseBranchRoutingNumberListFind(int $bankCode): array
    {
        $url = 'routings?';
        $params['bankCode'] = $bankCode;
        $response = $this->getData($url, $params);

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
     *
     * @param string $code
     * @return string
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

    public function makeTransfer(array $orderInfo = []): mixed
    {
        return [

        ];
    }

    public function transferStatus(array $orderInfo = []): mixed
    {
        return [

        ];
    }

    public function cancelTransfer(array $orderInfo = []): mixed
    {
        return [

        ];
    }

    public function verifyAccount(array $accountInfo = []): mixed
    {
        return [

        ];
    }

    public function vendorBalance(array $accountInfo = []): mixed
    {
        return [

        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model|\Fintech\Core\Abstracts\BaseModel  $order
     */
    public function requestQuote($order): mixed
    {
        return [

        ];
    }
}
