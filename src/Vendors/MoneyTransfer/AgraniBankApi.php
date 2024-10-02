<?php

namespace Fintech\Remit\Vendors\MoneyTransfer;

use Carbon\Carbon;
use DOMDocument;
use DOMException;
use ErrorException;
use Exception;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Remit\Contracts\MoneyTransfer;
use Fintech\Remit\Vendors\CatalogListService;
use Fintech\Remit\Vendors\CountryService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use stdClass;

class AgraniBankApi implements MoneyTransfer
{
    const OCCUPATION = 'OCC';

    const SOURCE_OF_FUND = 'SOF';

    const RELATIONSHIP = 'REL';

    const PURPOSE_OF_REMITTANCE = 'POR';

    const CUSTOMER_DOCUMENT_ID_TYPE = 'DOC';

    /**
     * @var string|null
     */
    public $country = null;

    /**
     * @var string|null
     */
    public $currency = null;

    /**
     * @var DOMDocument
     */
    public $xmlBody;

    /**
     * @var array
     */
    public $transactionBody; //base64 encode of auth

    /**
     * EMQ API configuration.
     *
     * @var array
     */
    private $config;

    /**
     * @var mixed|string
     */
    private $apiUrl;

    /**
     * @var string
     */
    private $status = 'sandbox';

    /**
     * @var string|null
     */
    private $basicAuthHash = null;

    /**
     * @var CatalogListService
     */
    private $catalogListService;

    /**
     * @var CountryService
     */
    private $countryService;

    /**
     * EMQApiService constructor.
     *
     * @param  CatalogListService  $catalogListService
     * @param  CountryService  $countryService
     *
     * @throws DOMException
     */
    public function __construct()
    {
        $this->config = config('agrani');
        $this->status = ($this->config['mode'] === 'sandbox') ? 'sandbox' : 'live';
        $this->apiUrl = $this->config[$this->status]['endpoint'];
        $this->encodeCredential();

        $this->xmlBody = new DOMDocument('1.0', 'utf-8');
        $this->xmlBody->preserveWhiteSpace = false;
        $this->xmlBody->formatOutput = true;
        $this->xmlBody->xmlStandalone = true;

        $this->transactionBody = $this->xmlBody->createElement('Transaction');

    }

    /**
     * Encode Auth info to base64 and store on $basicAuthHash
     *
     * @return void
     */
    protected function encodeCredential()
    {
        $asciString = '{ "Username=7106UAT", "Expassword=7106@Pass" }';
        $this->basicAuthHash = $asciString;
    }

    /**
     * Agrani Transfer TopUp
     *
     * @return stdClass
     *
     * @throws Exception
     */
    public function topUp($data)
    {
        $returnData = new stdClass;

        $reference = $data->reference_no;

        $transactionCreateResponse = $this->postCreateTransaction($data);

        Log::info('Unconfirmed APi Request:', $transactionCreateResponse);

        $returnData->emq_create_response = json_encode($transactionCreateResponse);

        switch ($transactionCreateResponse['status']) {
            case 200:
            case 201:

                //send confirmation request
                $transConfirmResponse = null; // $this->postTransactionConfirm($reference);
                $returnData->emq_confirm_response = json_encode($transConfirmResponse);

                Log::info('Confirmed APi Request:', $transConfirmResponse);

                switch ($transConfirmResponse['status']) {
                    case 200:
                    case 201:

                        $this->renderApiResponse($transConfirmResponse['response'], $returnData);
                        break;

                    case 400:

                        $returnData->message = $this->errorHandler($transConfirmResponse['response']);
                        $returnData->status = 'failed';
                        break;

                    case 500:

                        $returnData->message = $transactionCreateResponse['response']['message'];
                        $returnData->status = 'failed';
                        break;

                    default:

                        $returnData->message = 'Something went wrong from vendor API: Status Code :'.$transactionCreateResponse['status'];
                        $returnData->status = 'failed';
                        break;

                }

                $returnData->status_code = 201;
                break;

            case 400:

                $returnData->message = $this->errorHandler($transactionCreateResponse['response']);
                $returnData->status = 'failed';
                $returnData->status_code = 201;
                break;

            case 500:

                $returnData->message = $transactionCreateResponse['response']['message'];
                $returnData->status = 'failed';
                $returnData->status_code = 201;
                break;

            default:

                $returnData->message = 'Something went wrong from vendor API: Status Code :'.$transactionCreateResponse['status'];
                $returnData->status = 'failed';
                $returnData->status_code = 201;
                break;

        }

        return $returnData;
    }

    /**
     * Create bank transfers to for All
     *
     * @return array
     *
     * @throws Exception
     */
    public function postCreateTransaction($data)
    {
        $transactionTypes = ['Bank' => '15', 'Cash Pickup' => '05', 'CBS' => '16', 'Bkash' => '17'];

        $sender_last_name = isset($data->sender_last_name) ? $data->sender_last_name : '';

        $sender_first_name = isset($data->sender_first_name) ? $data->sender_first_name : '';

        $full_name = $sender_first_name;
        if (strlen($sender_last_name) > 0) {
            $full_name .= (' '.$sender_last_name);
        }

        $nameArray = preg_split("/\s+(?=\S*+$)/", $full_name);

        if (count($nameArray) > 1) {
            $sender_first_name = $nameArray[0];
            $sender_last_name = $nameArray[1];
        } else {
            $sender_last_name = $sender_first_name;
        }

        $transferInfo['tranno'] = $data->reference_no;
        $transferInfo['traninfosl'] = $data->reference_no;
        $transferInfo['trmode'] = isset($data->recipient_type_name)
            ? ($transactionTypes[$data->recipient_type_name] ?? '15')
            : '15';

        $transferInfo['purpose'] = $data->emq_purpose_of_remittance ?? null; //TODO agrani code needed
        $transferInfo['remamountdest'] = isset($data->transfer_amount) ? round($data->transfer_amount, 2) : '0.00';
        $transferInfo['remfname'] = $sender_first_name;
        $transferInfo['remlname'] = $sender_last_name;
        $transferInfo['remit_tel'] = isset($data->sender_mobile) ? substr($data->sender_mobile, -11) : null;
        $transferInfo['remaddress1'] = trim(($data->sender_address ?? null).' '.($data->sender_city ?? null));
        $transferInfo['remcountry'] = $data->trans_fast_sender_country_iso_code ?? null; //TODO agrani country code needed
        $transferInfo['benename'] = $data->receiver_first_name ?? null;
        $transferInfo['benemname'] = $data->receiver_middle_name ?? ' ';
        $transferInfo['benelname'] = $data->receiver_last_name ?? null;
        $transferInfo['beneaccountno'] = $data->bank_account_number ?? null;
        $transferInfo['benetel'] = $data->receiver_contact_number ?? null;
        $transferInfo['branchcode'] = substr(($data->location_routing_id[1]->bank_branch_location_field_value ?? null), -6);
        $transferInfo['beneaddress'] = $data->receiver_address ?? null;
        $transferInfo['benecountry'] = $data->trans_fast_receiver_country_iso_code ?? null;
        $transferInfo['entereddatetime'] = Carbon::now(config('app.timezone'))->format('Y-m-d\TH:i:s.u');
        $transferInfo['ratevalue'] = 0;
        $transferInfo['counttr'] = 0;
        $transferInfo['excode'] = $this->getExcode();
        $transferInfo['signaturevalue'] = $this->getTransactionSignature($transferInfo);

        array_walk($transferInfo, function (&$value, $key) {
            $this->transactionBody->appendChild($this->xmlBody->createElement($key, $value));
        });

        $this->xmlBody->appendChild($this->transactionBody);

        Log::info($this->xmlBody->saveXML());

        //die();
        return $this->putPostData('/MyCash', $transferInfo, 'POST');
    }

    /**
     * Return Excode from config
     *
     * @return string
     */
    public function getExcode()
    {
        return $this->config[$this->status]['excode'] ?? 7106;
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
        openssl_pkcs12_read(file_get_contents(public_path('Certificate.crt')), $certs, '1'); //where 1106@123 is your certificate password
        //dd($certs);

        //if (! $certs ) return ;
        $signature = '';
        openssl_sign($plainText, $signature, $key);

        return base64_encode($signature);
    }

    /**
     * Base function that is responsible for interacting directly with the nium api to send data
     *
     * @return array
     *
     * @throws Exception
     */
    public function putPostData(string $url, array $dataArray = [], string $method = 'POST')
    {
        $apiUrl = $this->apiUrl.$url;
        Log::info($apiUrl);
        $jsonArray = json_encode($dataArray);
        Log::info(json_decode($jsonArray, true));

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $apiUrl);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_POST, count($dataArray));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonArray);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_VERBOSE, true);

        $headers = [
            'cache-control: no-cache',
            'Content-Type: application/xml',
            'Accepts: application/xml',
            'Username: '.$this->getUsername(),
            'Expassword: '.$this->getPassword(),
        ];

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        $error = curl_error($curl);

        //dd([$error, $info, $response]);
        if ($response == false) {
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
     * Return Username from config
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->config[$this->status]['username'];
    }

    /**
     * Return Password from config
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->config[$this->status]['password'];
    }

    /**
     * Render Emq Response to pointed StdClass
     *
     * @param  array  $response  emq response
     * @param  stdClass  $returnData  class that will get rendered response
     * @return void
     */
    public function renderApiResponse(array $response, stdClass &$returnData)
    {
        $returnData->init_time = $response['created'] ?? date('Y-m-d H:i:s P');
        $returnData->recharge_time = $response['created'] ?? date('Y-m-d H:i:s P');
        $returnData->recharge_status = isset($response['info']['state']) ? $response['info']['state'] : null;

        $returnData->reference_no = isset($response['reference']) ? $response['reference'] : null;

        if (isset($response['destination']['type']) && $response['destination']['type'] == 'back_account') {
            $returnData->operator_name = isset($response['destination']['bank']) ? $response['destination']['bank'] : null;
            $returnData->operator_id = isset($response['destination']['branch']) ? $response['destination']['branch'] : null;
        } else {
            $returnData->operator_name = isset($response['destination']['partner']) ? $response['destination']['partner'] : null;
            $returnData->operator_id = isset($response['destination']['segment']) ? $response['destination']['segment'] : null;
        }

        $returnData->connection_type = isset($response['destination']['type']) ? $response['destination']['type'] : null;
        $returnData->recipient_msisdn = isset($response['destination']['account_number']) ? $response['destination']['account_number'] : null;

        $returnData->amount = isset($response['destination_amount']['units']) ? $response['destination_amount']['units'] : null;
        $returnData->order_total = isset($response['source_amount']['units']) ? $response['source_amount']['units'] : null;

        $returnData->available_credit = isset($response['info']['state']) ? $response['info']['state'] : null;
        $returnData->message = json_encode($response['info'], JSON_PRETTY_PRINT);

        //$returnData->vr_guid = $response['info']['code'];
        $returnData->vr_guid = isset($response['reference']) ? $response['reference'] : null;
        $returnData->telco_transaction_id = isset($response['info']['code']) ? $response['info']['code'] : null;

    }

    /**
     * Agrani Transfer TopUp
     *
     * @return stdClass
     *
     * @throws Exception
     */
    public function oldTopUp($data)
    {
        $returnData = new stdClass;

        $reference = $data->reference_no;

        $transactionCreateResponse = $this->postCreateTransaction($data);

        Log::info('Unconfirmed APi Request:', $transactionCreateResponse);

        $returnData->emq_create_response = json_encode($transactionCreateResponse);

        switch ($transactionCreateResponse['status']) {
            case 200:
            case 201:

                //send confirmation request
                $transConfirmResponse = $this->postTransactionConfirm($reference);
                $returnData->emq_confirm_response = json_encode($transConfirmResponse);

                Log::info('Confirmed APi Request:', $transConfirmResponse);

                switch ($transConfirmResponse['status']) {
                    case 200:
                    case 201:

                        $this->renderApiResponse($transConfirmResponse['response'], $returnData);
                        break;

                    case 400:

                        $returnData->message = $this->errorHandler($transConfirmResponse['response']);
                        $returnData->status = 'failed';
                        break;

                    case 500:

                        $returnData->message = $transactionCreateResponse['response']['message'];
                        $returnData->status = 'failed';
                        break;

                    default:

                        $returnData->message = 'Something went wrong from vendor API: Status Code :'.$transactionCreateResponse['status'];
                        $returnData->status = 'failed';
                        break;

                }

                $returnData->status_code = 201;
                break;

            case 400:

                $returnData->message = $this->errorHandler($transactionCreateResponse['response']);
                $returnData->status = 'failed';
                $returnData->status_code = 201;
                break;

            case 500:

                $returnData->message = $transactionCreateResponse['response']['message'];
                $returnData->status = 'failed';
                $returnData->status_code = 201;
                break;

            default:

                $returnData->message = 'Something went wrong from vendor API: Status Code :'.$transactionCreateResponse['status'];
                $returnData->status = 'failed';
                $returnData->status_code = 201;
                break;

        }

        return $returnData;
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
     * Login and obtain session token.
     *
     * @param string username
     * @param string password
     *
     * @throws Exception
     */
    public function postLogin()
    {
        $payLoad = ['username' => $this->getUsername(), 'password' => $this->getPassword()];
        $this->putPostData('/auth/login', $payLoad, 'POST');

    }

    /**
     * @throws Exception
     */
    public function getTransactionDetails(string $reference)
    {
        return $this->getData("/getxmltraninfobyiduat/{$reference}");

    }

    /**
     * Base function that is responsible for interacting directly with the nium api to obtain data
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
        $headers = [
            'cache-control: no-cache',
            'Content-Type: application/json',
            'Accepts: application/json',
            'Username: '.$this->getUsername(),
            'Expassword: '.$this->getPassword(),
        ];
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($curl);
        $info = curl_getinfo($curl);
        $error = curl_error($curl);

        if ($response == false) {
            Log::info($info);
            Log::info($error);
            throw new Exception(curl_error($curl), curl_errno($curl));
        }

        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        Log::info('API Response : '.$response);

        echo $response;

        return [
            'status' => $status,
            'response' => json_decode($response, true),
        ];

    }

    /**
     * @param  Model|BaseModel  $order
     */
    public function requestQuote($order): mixed
    {
        return [

        ];
    }

    /**
     * Method to make a request to the remittance service provider
     * for an execution of the order.
     *
     * @throws ErrorException
     */
    public function executeOrder(BaseModel $order): mixed
    {
        // TODO: Implement executeOrder() method.
    }

    /**
     * Method to make a request to the remittance service provider
     * for the progress status of the order.
     *
     * @throws ErrorException
     */
    public function orderStatus(BaseModel $order): mixed
    {
        // TODO: Implement orderStatus() method.
    }

    /**
     * Method to make a request to the remittance service provider
     * for the cancellation of the order.
     *
     * @throws ErrorException
     */
    public function cancelOrder(BaseModel $order): mixed
    {
        // TODO: Implement cancelOrder() method.
    }

    /**
     * Method to make a request to the remittance service provider
     * for the amendment of the order.
     *
     * @throws ErrorException
     */
    public function amendmentOrder(BaseModel $order): mixed
    {
        // TODO: Implement amendmentOrder() method.
    }

    /**
     * Method to make a request to the remittance service provider
     * for the track real-time progress of the order.
     *
     * @throws ErrorException
     */
    public function trackOrder(BaseModel $order): mixed
    {
        // TODO: Implement trackOrder() method.
    }
}
