<?php

namespace Fintech\Remit\Vendors;

use App\Models\Backend\Setting\CatalogList;
use App\Models\Backend\Setting\Country;
use App\Services\Backend\Setting\CatalogListService;
use App\Services\Backend\Setting\CountryService;
use Carbon\Carbon;
use Exception;
use Fintech\Remit\Contracts\BankTransfer;
use Fintech\Remit\Contracts\OrderQuotation;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use MongoDB\Laravel\Eloquent\Model;
use stdClass;

class EmqApi implements BankTransfer, OrderQuotation
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
     * EMQ API configuration.
     *
     * @var array
     */
    private $config;

    /**
     * @var mixed|string
     */
    private $apiUrl; //base64 encode of auth

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
     */
    public function __construct(CatalogListService $catalogListService,
        CountryService $countryService)
    {
        $this->config = config('emq');
        $this->status = ($this->config['mode'] === 'sandbox') ? 'sandbox' : 'live';
        $this->apiUrl = $this->config[$this->status]['endpoint'];
        $this->encodeCredential();
        $this->catalogListService = $catalogListService;
        $this->countryService = $countryService;
    }

    /**
     * Encode Auth info to base64 and store on $basicAuthHash
     *
     * @return void
     */
    protected function encodeCredential()
    {
        $asciString = mb_convert_encoding($this->config[$this->status]['username'].':'.$this->config[$this->status]['password'], 'ASCII');
        $this->basicAuthHash = base64_encode($asciString);
    }

    /**
     * EMQ Transfer TopUp
     *
     * @return stdClass
     *
     * @throws Exception
     */
    public function topUp($data)
    {
        $returnData = new stdClass();

        $reference = $data->reference_no;

        $transactionCreateResponse = $this->postCreateTransaction($data);

        Log::info('Unconfirmed APi Request:', [$transactionCreateResponse]);

        $returnData->emq_create_response = json_encode($transactionCreateResponse);

        switch ($transactionCreateResponse['status']) {
            case 200:
            case 201:

                //send confirmation request
                $transConfirmResponse = $this->postTransactionConfirm($reference);
                $returnData->emq_confirm_response = json_encode($transConfirmResponse);

                Log::info('Confirmed APi Request:', [$transConfirmResponse]);

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
        $reference = $data->reference_no;

        $transactionTypes = ['Bank' => 'bank_account', 'Cash Pickup' => 'cash_pickup', 'Cash' => 'cash_pickup', 'Wallet' => 'ewallet'];

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

        $transferInfo['destination_amount']['currency'] = isset($data->transfer_currency) ? $data->transfer_currency : null;
        $transferInfo['destination_amount']['units'] = isset($data->transfer_amount) ? (string) round($data->transfer_amount, 2) : null; //TODO with charge or without charge
        //TODO FOR PHL
        $transferInfo['destination']['country'] = isset($data->emq_receiver_country_iso_code) ? $data->emq_receiver_country_iso_code : null;
        if (in_array($transferInfo['destination']['country'], ['PHL', 'IDN'])) {
            $transferInfo['destination_amount']['units'] = isset($data->transfer_amount) ? (string) floor($data->transfer_amount) : null; //TODO with charge or without charge
        }

        //$transferInfo["source_amount"]["currency"] = isset($data->sender_currency) ? $data->sender_currency : null;
        //$transferInfo["source_amount"]["units"] = isset($data->sender_amount) ? (string)round($data->sender_amount) : null; //TODO with charge or without charge

        $transferInfo['compliance']['source_of_funds'] = isset($data->emq_sender_source_of_fund_id) ? (string) $data->emq_sender_source_of_fund_id : null;
        $transferInfo['compliance']['remittance_purpose'] = isset($data->emq_purpose_of_remittance) ? $data->emq_purpose_of_remittance : null;

        $transferInfo['compliance']['relationship']['code'] = isset($data->emq_sender_beneficiary_relationship_code) ? $data->emq_sender_beneficiary_relationship_code : null;
        if ($transferInfo['compliance']['relationship']['code'] == '99' || $transferInfo['compliance']['relationship']['code'] == null) {
            $transferInfo['compliance']['relationship']['code'] = '99';
            $transferInfo['compliance']['relationship']['relation'] = isset($data->sender_beneficiary_relationship) ? ucwords(strtolower($data->sender_beneficiary_relationship)) : 'Others';
        }

        //Source
        $transferInfo['source']['type'] = 'partner';
        $transferInfo['source']['gender'] = isset($data->sender_gender) ? strtoupper(substr($data->sender_gender, 0, 1)) : 'M';
        $transferInfo['source']['country'] = isset($data->emq_sender_country_iso3_code) ? $data->emq_sender_country_iso3_code : null;

        $transferInfo['source']['segment'] = isset($data->emq_sender_segment) ? $data->emq_sender_segment : 'individual';
        $transferInfo['source']['legal_name_first'] = $sender_first_name;
        $transferInfo['source']['legal_name_last'] = $sender_last_name;

        $transferInfo['source']['mobile_number'] = isset($data->sender_mobile) ? ('+'.$data->sender_mobile) : null;

        $transferInfo['source']['date_of_birth'] = isset($data->sender_date_of_birth) ? Carbon::parse($data->sender_date_of_birth)->format('Y-m-d') : null;

        $transferInfo['source']['nationality'] = isset($data->emq_sender_nationality) ? $data->emq_sender_nationality : null;

        //$transferInfo['source']["id_type"] = isset($data->emq_sender_id_type) ? strtolower($data->emq_sender_id_type) : null;
        $transferInfo['source']['id_type'] = 'passport'; //national
        $transferInfo['source']['id_country'] = isset($data->emq_sender_id_issue_country) ? $data->emq_sender_id_issue_country : null;
        $transferInfo['source']['id_number'] = isset($data->sender_id_number) ? $data->sender_id_number : null;
        $transferInfo['source']['id_expiration'] = isset($data->sender_expire_date) ? Carbon::parse($data->sender_expire_date)->format('Y-m-d') : null;

        $transferInfo['source']['address_city'] = isset($data->sender_city) ? $data->sender_city : null;
        $transferInfo['source']['address_line'] = isset($data->sender_address) ? $data->sender_address : null;
        $transferInfo['source']['address_zip'] = isset($data->sender_zipcode) ? $data->sender_zipcode : null;
        $transferInfo['source']['address_country'] = isset($data->emq_sender_country_iso3_code) ? $data->emq_sender_country_iso3_code : null;

        //Destination
        $transferInfo['destination']['type'] = isset($data->recipient_type_name)
            ? (isset($transactionTypes[$data->recipient_type_name])
                ? $transactionTypes[$data->recipient_type_name] : 'bank_account')
            : null;
        $transferInfo['destination']['country'] = isset($data->emq_receiver_country_iso_code) ? $data->emq_receiver_country_iso_code : null;
        $transferInfo['destination']['legal_name_first'] = isset($data->receiver_first_name) ? $data->receiver_first_name : null;
        $transferInfo['destination']['legal_name_last'] = isset($data->receiver_last_name) ? $data->receiver_last_name : null;
        $transferInfo['destination']['mobile_number'] = isset($data->receiver_contact_number) ? ('+'.$data->receiver_contact_number) : null;

        $transferInfo['destination']['address_line'] = isset($data->receiver_address) ? $data->receiver_address : null;
        $transferInfo['destination']['address_city'] = isset($data->receiver_city) ? $data->receiver_city : null;

        //type bank account
        if ($transferInfo['destination']['type'] === 'bank_account') {

            if ($transferInfo['destination']['country'] !== 'CHN') {
                $transferInfo['destination']['bank'] = isset($data->emq_bank_id) ? $data->emq_bank_id : null;
            }
            //remove china bank address info
            if ($transferInfo['destination']['country'] == 'CHN') {
                unset($transferInfo['destination']['address_line']);
                unset($transferInfo['destination']['address_city']);
            }

            //remove malaysia bank address info
            if ($transferInfo['destination']['country'] == 'MYS') {
                unset($transferInfo['destination']['mobile_number']);
                unset($transferInfo['destination']['address_city']);
            }

            //indonesia
            if ($transferInfo['destination']['country'] == 'IDN') {
                $transferInfo['destination']['address_state'] = isset($data->receiver_province) ? $data->receiver_province : 'Jawa Barat';
                $transferInfo['destination']['address_state_code'] = isset($data->emq_receiver_province_code) ? $data->emq_receiver_province_code : '01';
                $transferInfo['destination']['address_city'] = isset($data->emq_receiver_city) ? $data->emq_receiver_city : 'BANDUNG';
                $transferInfo['destination']['address_city_code'] = isset($data->emq_receiver_city_code) ? $data->emq_receiver_city_code : '0191';
                $transferInfo['destination']['id_number'] = isset($data->receiver_id_number) ? $data->receiver_id_number : null;
            }

            $transferInfo['destination']['account_number'] = isset($data->bank_account_number) ? $data->bank_account_number : null;
            //branch
            if ($transferInfo['destination']['country'] === 'IND') { //India //99
                //$transferInfo["destination"]["branch"] = isset($data->emq_bank_branch_id) ? $data->emq_bank_branch_id : null;
                //$transferInfo["destination"]["branch"] = str_replace((isset($data->emq_bank_id) ? $data->emq_bank_id : null), '', (isset($data->location_routing_id[1]->bank_branch_location_field_value) ? $data->location_routing_id[1]->bank_branch_location_field_value : null));
                $transferInfo['destination']['branch'] = substr((isset($data->location_routing_id[1]->bank_branch_location_field_value) ? $data->location_routing_id[1]->bank_branch_location_field_value : null), -6);

            } elseif ($transferInfo['destination']['country'] === 'JPN') {
                $transferInfo['destination']['branch'] = isset($data->emq_bank_branch_id) ? $data->emq_bank_branch_id : null;
            }

            //swift //SPEA Country TODO Feature Work
            /*if ($transferInfo["destination"]["country"] === '') :
                $transferInfo["destination"]["swift_code"] = isset($data->emq_bank_swift_code) ? $data->emq_bank_swift_code : null;
                $transferInfo["destination"]["iban"] = isset($data->emq_bank_iban_code) ? $data->emq_bank_iban_code : null;
            endif;*/
        }

        //type ewallet
        if ($transferInfo['destination']['type'] === 'ewallet') {
            if (in_array($transferInfo['destination']['country'], $this->config['ewallet_allow_country'])) {
                $transferInfo['destination']['segment'] = isset($data->emq_sender_segment) ? $data->emq_sender_segment : 'individual';

                if ($transferInfo['destination']['country'] === 'PHL') {
                    $transferInfo['destination']['ewallet_id'] = isset($data->bank_account_number)
                        ? preg_replace('/^(63)(.+)/u', '$2', $data->bank_account_number, 1)
                        : null;
                } else {
                    $transferInfo['destination']['ewallet_id'] = isset($data->bank_account_number) ? $data->bank_account_number : null;
                }

                if (count($this->config['partners'][$transferInfo['destination']['country']]['ewallet']) > 0) {
                    $transferInfo['destination']['partner'] = isset($data->emq_ewallet_partner)
                        ? $data->emq_ewallet_partner
                        : $this->config['partners'][$transferInfo['destination']['country']]['ewallet'][0];
                }
            }
        }

        //type cash_pickup
        if ($transferInfo['destination']['type'] === 'cash_pickup') {
            $transferInfo['destination']['partner'] = isset($data->emq_cash_pickup_partner) ? $data->emq_cash_pickup_partner : null;
        }

        /*        //verify recipient
                $recipientConfirm = $this->verifyRecipient($transferInfo["destination"]);

                if ($recipientConfirm['status'] === 200 && $recipientConfirm['response']['result'] === 'ok') {*/

        $returnData = $this->putPostData("/transfers/{$reference}", $transferInfo, 'PUT');

        /*        } else {
        $returnData = $recipientConfirm;
    }*/

        return $returnData;
    }

    /**
     * Base function that is responsible for interacting directly with the nium api to send data
     *
     * @return array
     *
     * @throws Exception
     */
    public function putPostData(string $url, array $dataArray, string $method = 'POST')
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
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'cache-control: no-cache',
            'Content-Type: application/json',
            'Accepts: application/json',
            'Authorization: Basic '.$this->getBasicAuthHash(),
        ]
        );

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

        Log::info(json_decode($response, true));

        return [
            'status' => $status,
            'response' => json_decode($response, true),
        ];
    }

    /**
     * Return encode auth header value
     *
     * @return string|null
     */
    public function getBasicAuthHash()
    {
        return $this->basicAuthHash;
    }

    /**
     * @return array
     *
     * @throws Exception
     */
    public function postTransactionConfirm(string $reference)
    {
        return $this->putPostData("/transfers/{$reference}/confirm", [], 'POST');
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
        $returnData->init_time = isset($response['created']) ? $response['created'] : date('Y-m-d H:i:s P');
        $returnData->recharge_time = isset($response['created']) ? $response['created'] : date('Y-m-d H:i:s P');
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
        $returnData->status = $this->stateHandler($response['state']);
    }

    /**
     * @return string
     */
    public function stateHandler(string $state)
    {
        $response = null;

        switch ($state) {
            case 'new':
            case 'held':
            case 'review':
            case 'payout':
            case 'successful':
                $response = 'successful';
                break;

            case 'cancelled':
                //$response = 'failed_and_refund';
                $response = 'admin_to_verify';
                break;

            case 'sent':
            case 'finished':
            case 'success':
                $response = 'success';
                break;

            default:
                $response = 'admin_to_verify';
                break;
        }

        return $response;
    }

    /**
     * @return string
     */
    public function errorHandler($response)
    {

        $message = '';
        if (isset($response['reason'])) {
            switch ($response['reason']) {

                case 'not_found':
                case 'transfer_already_confirmed':
                case 'kyc_check_exchange_not_supported':
                case 'cash_payin_exchange_not_supported':
                case 'review_exchange_not_supported':
                case 'fees_exchange_not_supported':
                case 'permission_denied':
                case 'invalid_corridor':
                case 'transfer_expired':
                case 'transfer_not_found':
                case 'duplicate_reference':
                case 'amount_less_than_fees':
                case 'over_limit':
                case 'under_limit':
                case 'account_not_registered':
                case 'invalid_account_number':
                case 'invalid_account_name':
                case 'unable_to_verify':
                    $message = (is_array($response['message']))
                        ? (implode("\n", $response['message']).PHP_EOL)
                        : ucwords($response['message']).PHP_EOL;
                    break;

                case 'invalid_country':
                    $message = 'URL country segment is invalid'.PHP_EOL;
                    break;

                case 'no_rate':
                    $message = $response['message'].' between '.(implode('and', $response['detail'])).PHP_EOL;
                    break;

                case 'validation_failed':

                    $message = ucwords($response['message']).PHP_EOL;

                    $errorArray = Arr::dot($response['detail']);

                    foreach ($errorArray as $field => $error) {
                        $message .= $this->customErrorMessage(['field' => $field], $error).PHP_EOL;
                    }

                    break;

                case 'validation_not_rounded':

                    $message = ucwords($response['message']).PHP_EOL;

                    $errorArray = Arr::dot($response['detail']);

                    foreach ($errorArray as $field => $error) {
                        $message .= "{$field} {$error}".PHP_EOL;
                    }

                    break;

                case 'required_not_provided':

                    $message = (isset($response['detail']['fields']))
                        ? ucwords($response['message']).'Fields ( '.implode(', ', $response['detail']['fields']).')'.PHP_EOL
                        : ucwords($response['message']).PHP_EOL;

                    break;

                default:
                    $message = 'Unknown Error from Vendor. Reason: '.$response['reason'].PHP_EOL;
                    break;
            }
        }

        return $message;

    }

    public function customErrorMessage(array $fields, string $message)
    {
        $customMessages = [
            'validation_required' => ':field is required',
        ];

        if (isset($customMessages[$message])) {
            $valueFields = array_values($fields);
            $mapFields = array_map(function ($item) {
                return ':'.$item;
            }, array_keys($fields));

            return str_replace($mapFields, $valueFields, $customMessages[$message]);
        } else {
            return $fields['field'].' '.$message;
        }
    }

    /**
     * Convert Country full into ISO 3 value
     *
     * @param  string|null  $country  country full name
     * @return string|null country iso3 country code
     */
    public function getCountryISO3FromName(?string $country = null)
    {
        $countryModel = $this->countryService->ShowAllCountry(['country_name' => $country])->get()->first();

        return ($countryModel instanceof Country) ? $countryModel->country_iso3 : null;
    }

    /**
     * Get Catalog Emq Code of catalog
     *
     * @param  string|null  $catalog  country full name
     * @return string|null country iso3 country code
     */
    public function getCatalogeEmqCodeFromName(string $catalog, string $type)
    {
        $catalogModel = $this->catalogListService->ShowAllCatalogList([
            'catalog_data_wild_card' => $catalog,
            'catalog_type' => $type,
            'emq_code_not_null' => true])->get()->first();

        return ($catalogModel instanceof CatalogList) ? $catalogModel->emq_code : null;
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

    /******************************************* Support *******************************************/

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
     * Retrieve a quote of current rate.
     *
     * @parem source = "emq_partner_generic:api:HKG:USD"
     * @parem destination = "emq_partner_cebuana:cash_pickup:PHL:PHP"
     *
     * @return array
     *
     * @throws Exception
     */
    public function getQuotes(array $data)
    {
        $source = ($data['source_partner'].':');
        $source .= ($data['source_method'].':');
        $source .= ($data['source_country'].':');
        $source .= ($data['source_currency']);

        $destination = ($data['destination_partner'].':');
        $destination .= ($data['destination_method'].':');
        $destination .= ($data['destination_country'].':');
        $destination .= ($data['destination_currency']);

        return $this->getData("/quotes/{$source}/{$destination}");

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
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'cache-control: no-cache',
            'Content-Type: application/json',
            'Accepts: application/json',
            'Authorization: Basic '.$this->getBasicAuthHash(),
        ]
        );

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

        return [
            'status' => $status,
            'response' => json_decode($response, true),
        ];

    }
    /******************************************* Auth *******************************************/

    /**
     * Retrieve current balances
     *
     * @return array|string
     *
     * @throws Exception
     */
    public function getAllBalance(?string $currency = null)
    {
        $response = $this->getData('/balances');

        if ($response['status'] == 200) {
            switch ($currency) {
                case 'SGD' :
                    return $response['response']['balances']['Funding SGD (primary)'];

                case 'USD' :
                    return $response['response']['balances']['Fees USD'];

                default:
                    return $response['response']['balances'];
            }
        } else {
            return [];
        }
    }

    /******************************************* Quota *******************************************/

    /**
     * Retrieve a daily statement for a given day.
     *
     * @param  string  $date  'YYYY-MM-DD' format date string
     * @return array
     *
     * @throws Exception
     */
    public function getDailyStatement(string $date)
    {
        return $this->getData("/statements/daily/{$date}");

    }

    /******************************************* Balance *******************************************/

    /**
     * Retrieve a monthly statement up to a given day.
     *  Example 2021-04-01
     *
     * @param  string  $date  'YYYY-MM-DD' format date string
     *
     * @throws Exception
     */
    public function getMonthlyStatement(string $date)
    {
        return $this->getData("/statements/monthly/{$date}");

    }

    /******************************************* Report *******************************************/

    /**
     * Retrieve the settlement report for a given date and a given destination country
     *
     * @param  string  $param  ['source_country'] source country ISO3
     * @param  string  $param  ['dest_country'] source country ISO3
     * @param  string  $param  ['date'] 'YYYY-MM-DD' format date string
     *
     * @throws Exception
     */
    public function getDailySettlement(array $param)
    {
        return $this->getData("/settlements/{$param['source_country']}/{$param['dest_country']}/{$param['date']}");

    }

    /**
     * List or search senders
     *
     * @param  array  $params  [sender_id, name, mobile_number, id_number, page=1, page_size = 20]
     * @return array
     *
     * @throws Exception
     */
    public function getAllSenders(array $params = [])
    {
        return $this->getData('/senders', $params);

    }

    /**
     * @param
     * "sender_id": "1234567890",
     * "segment": "individual",
     * "legal_name_first": "John",
     * "legal_name_last": "Smith",
     * "mobile_number": "+886965716066",
     * "nationality": "TWN",
     * "date_of_birth": "1970-01-01",
     * "gender": "M",
     * "id_number": "A199267867",
     * "id_type": "national",
     * "id_country": "TWN"
     *
     * @throws Exception
     */
    public function createSender(array $data)
    {
        return $this->putPostData('/senders', $data, 'POST');

    }

    /******************************************* Sender *******************************************/

    /**
     * Retrieve a sender
     *
     * @param  string  $senderId  string
     *
     * @throws Exception
     */
    public function getSenderDetails(string $senderId)
    {
        return $this->getData("/senders/{$senderId}");

    }

    /**
     * Update a sender
     *
     * "segment": "individual",
     * "legal_name_first": "John",
     * "legal_name_last": "Smith",
     * "mobile_number": "+886965716066",
     * "nationality": "TWN",
     * "date_of_birth": "1970-01-01",
     * "gender": "M",
     * "id_number": "A199267867",
     * "id_type": "national",
     * "id_country": "TWN"
     *
     * @return array
     *
     * @throws Exception
     */
    public function updateSenderDetail(array $data, string $senderId)
    {
        return $this->putPostData("/senders/{$senderId}", $data, 'PUT');

    }

    /**
     * Retrieve list of recipients for a given sender.
     *
     * @return array
     *
     * @throws Exception
     */
    public function getAllRecipients(string $senderId)
    {
        return $this->getData("/senders/{$senderId}/recipients");

    }

    /**
     * @param  array  $data
     *                       ["country": "PHL",
     *                       "segment": "individual",
     *                       "legal_name_first": "Joe P.",
     *                       "legal_name_last": "Smith",
     *                       "mobile_number": "+85212345678"]
     *
     * @throws Exception
     */
    public function createRecipient(string $senderId, array $data)
    {
        return $this->putPostData("/senders/{$senderId}/recipients", $data, 'POST');

    }

    /******************************************* Recipient *******************************************/

    /**
     * Retrieve a recipient.
     *
     * @return array|mixed
     *
     * @throws Exception
     */
    public function getRecipientDetails(string $senderId, string $recipientId)
    {
        return $this->getData("/senders/{$senderId}/recipients/{$recipientId}");

    }

    /**
     * Update a recipient.
     *
     * @param  array  $data
     *                       ["country": "PHL",
     *                       "segment": "individual",
     *                       "legal_name_first": "Joe P.",
     *                       "legal_name_last": "Smith",
     *                       "mobile_number": "+85212345678"]
     * @return array|mixed
     *
     * @throws Exception
     */
    public function updateRecipientDetail(string $senderId, string $recipientId, array $data)
    {
        return $this->putPostData("/senders/{$senderId}", $data, 'PUT');

    }

    /**
     * Verify a recipient.
     *
     * @param  array  $data
     *                       {
     *                       "type": "bank_account",
     *                       "country": "CHN",
     *                       "legal_name_first": "hui",
     *                       "legal_name_last": "lu",
     *                       "mobile_number": "+8613800001111",
     *                       "account_number": "6217900100010200001"
     *                       }
     * @return array
     *
     * @throws Exception
     */
    public function verifyRecipient(array $data)
    {
        \Log::info('Verification APi Request:');
        \Log::info($data);

        return $this->putPostData('/recipients/verify', $data, 'POST');
    }

    /**
     * List active corridors
     * Retrieve list of active corridors.
     *
     * @throws Exception
     */
    public function getActiveCorridors()
    {
        return $this->getData('/data/countries');

    }

    /**
     * List all countries
     * Get all the country information, including country name,
     * ISO country code (both alpha-2 and alpha-3) ,
     * and International Direct Dialing.
     *
     * @throws Exception
     */
    public function getAllCountries()
    {
        $response = $this->getData('/data/countries/all');

        if ($response['status'] == 200) {
            return $response['response'];
        } else {
            return [];
        }

    }

    /******************************************* Static Data *******************************************/

    /**
     * List currencies
     * Retrieve list of currencies to be used in creating transfer
     *
     * @throws Exception
     */
    public function getAllCurrencies()
    {
        return $this->getData('/data/currencies');

    }

    /**
     * List occupations
     * Retrieve list of occupations
     *
     * @throws Exception
     */
    public function getAllOccupations()
    {

        return $this->getData('/data/occupations');

    }

    /**
     * List relationships
     * Retrieve list of beneficiary relationships.
     *
     * @throws Exception
     */
    public function getAllRelationships()
    {
        return $this->getData('/data/relationships');

    }

    /**
     * Retrieve bank list for a given country.
     *
     * @return array|mixed
     *
     * @throws Exception
     */
    public function getAllBankByCountry(?string $country = null)
    {
        if ($country != null) {
            $this->setCountry($country);
        }

        $this->verifyCountryParam();

        return $this->getData("/data/countries/{$this->country}/banks");

    }

    /**
     * Set Current Instance Country
     */
    public function setCountry(string $country)
    {
        $this->country = $country;
    }

    /**
     * Verify if Country value is set
     *
     * @throws Exception
     */
    protected function verifyCountryParam()
    {
        if ($this->country == null) {
            throw new Exception('country missing');
        }
    }

    /**
     * Retrieve bank list for a given currency of a country.
     *
     * @return array|mixed
     *
     * @throws Exception
     */
    public function getAllBankByCurrency(string $currency)
    {
        $this->verifyCountryParam();

        return $this->getData("/data/countries/{$this->country}/currencies/{$currency}/banks");
    }

    /**
     * Retrieve branch list for a given bank code.
     *
     * @return array|mixed
     *
     * @throws Exception
     */
    public function getAllBranchByBank(string $bank)
    {
        $this->verifyCountryParam();

        return $this->getData("/data/countries/{$this->country}/banks/{$bank}/branches");

    }

    /**
     * Retrieve address state list for a given country.
     *
     * @return array|mixed
     *
     * @throws Exception
     */
    public function getAllStateByCountry(?string $country = null)
    {
        if ($country != null) {
            $this->setCountry($country);
        }

        $this->verifyCountryParam();

        $response = $this->getData("/data/countries/{$this->country}/states");

        return ($response['status'] == 200) ? $response['response'] : [];

    }

    /**
     * Retrieve city list for given country, state.
     *
     * @return array|mixed
     *
     * @throws Exception
     */
    public function getAllCityByState(string $country, string $state)
    {
        // $this->verifyCountryParam();

        $response = $this->getData("/data/countries/{$country}/states/{$state}/cities");

        return ($response['status'] == 200) ? $response['response'] : [];

    }

    /**
     * Retrieve list of remittance purpose for a given country.
     *
     * @return array|mixed
     *
     * @throws Exception
     */
    public function getAllRemittancePurposesByCountry(?string $country = null)
    {
        if ($country != null) {
            $this->setCountry($country);
        }

        $this->verifyCountryParam();

        return $this->getData("/data/countries/{$this->country}/remittance_purposes");

    }

    /**
     * Retrieve list of remittance purpose for given country and segment.
     *
     * @return array|mixed
     *
     * @throws Exception
     */
    public function getAllRemittancePurposesBySegment(string $segment)
    {
        $this->verifyCountryParam();

        return $this->getData("/data/countries/{$this->country}/remittance_purposes/{$segment}");

    }

    /**
     * Retrieve list of source of funds for a given country.
     *
     * @return array|mixed
     *
     * @throws Exception
     */
    public function getAllSourceFundByCountry(?string $country = null)
    {
        if ($country != null) {
            $this->setCountry($country);
        }

        $this->verifyCountryParam();

        return $this->getData("/data/countries/{$this->country}/sources_of_funds");
    }

    /**
     * Retrieve list of source of funds for given country and segment
     *
     * @return array|mixed
     *
     * @throws Exception
     */
    public function getAllSourceFundBySegment(string $segment)
    {
        $this->verifyCountryParam();

        return $this->getData("/data/countries/{$this->country}/sources_of_funds/{$segment}");
    }

    /**
     * Retrieve list of sets and types in uploading identity documents.
     *
     * @return array|mixed
     *
     * @throws Exception
     */
    public function getAllDocumentTypesByCountry(?string $country = null)
    {
        if ($country != null) {
            $this->setCountry($country);
        }
        $this->verifyCountryParam();

        return $this->getData("/data/countries/{$this->country}/document_types");

    }

    /**
     * Retrieve list of sources to be used in quote.
     *
     * @return array|mixed
     *
     * @throws Exception
     */
    public function getAllSourceByCountry(?string $country = null)
    {
        if ($country != null) {
            $this->setCountry($country);
        }

        $this->verifyCountryParam();

        return $this->getData("/data/countries/{$this->country}/sources");

    }

    /**
     * Retrieve list of destinations to be used in quote.
     *
     * @return array|mixed
     *
     * @throws Exception
     */
    public function getAllDestinationByCountry(?string $country = null)
    {
        if ($country != null) {
            $this->setCountry($country);
        }
        $this->verifyCountryParam();

        return $this->getData("/data/countries/{$this->country}/destinations");

    }

    /*********************************** Transaction ***************************************/

    /**
     * List transfers, oldest first.
     *
     * @param  array  $data  [page, page_size, start_datetime, end_datetime]
     * @return array
     *
     * @throws Exception
     */
    public function getAllTransactions(array $data = [])
    {
        return $this->getData('/transfers', $data);

    }

    /**
     * @param  array  $reference  MCM6196575170666
     * @return array|mixed
     *
     * @throws Exception
     */
    public function searchTransactions(array $reference)
    {
        return $this->getData('/transfers/search', $reference);

    }

    /**
     * @throws Exception
     */
    public function getTransactionDetails(string $reference)
    {
        return $this->getData("/transfers/{$reference}");

    }

    /**
     * @return array
     *
     * @throws Exception
     */
    public function postTransactionCancel(string $reference)
    {
        return $this->putPostData("/transfers/{$reference}/cancel", [], 'POST');
    }

    /**
     * Execute the transfer operation
     */
    public function makeTransfer(array $orderInfo = []): mixed
    {
        // TODO: Implement makeTransfer() method.
    }

    public function transferStatus(array $orderInfo = []): mixed
    {
        // TODO: Implement transferStatus() method.
    }

    public function cancelTransfer(array $orderInfo = []): mixed
    {
        // TODO: Implement cancelTransfer() method.
    }

    public function verifyAccount(array $accountInfo = []): mixed
    {
        // TODO: Implement verifyAccount() method.
    }

    public function vendorBalance(array $accountInfo = []): mixed
    {
        // TODO: Implement vendorBalance() method.
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Model|Model  $order
     */
    public function requestQuotation($order): mixed
    {
        // TODO: Implement requestQuotation() method.
    }

    protected function getBalanceFromCurrency(string $currency, $response)
    {

    }
}
