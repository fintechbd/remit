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
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Credentials '.$this->config[$this->status]['token']]);

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
