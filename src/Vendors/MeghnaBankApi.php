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
