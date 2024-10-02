<?php

namespace Fintech\Remit\Vendors\MoneyTransfer;

use ErrorException;
use Exception;
use Fintech\Core\Abstracts\BaseModel;
use Fintech\Remit\Contracts\MoneyTransfer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class TransFastApi implements MoneyTransfer
{
    protected $payment_mode;

    protected $account_type;

    /**
     * TransFast API configuration.
     *
     * @var array
     */
    private $config;

    /**
     * TransFast API Url.
     *
     * @var string
     */
    private $apiUrl;

    /**
     * @var string
     */
    private $status = 'sandbox';

    /**
     * TransFastApiService constructor.
     */
    public function __construct()
    {
        $this->config = config('fintech.remit.providers.transfast');

        if ($this->config['mode'] === 'sandbox') {
            $this->apiUrl = $this->config[$this->status]['endpoint'];
            $this->status = 'sandbox';

        } else {
            $this->apiUrl = $this->config[$this->status]['endpoint'];
            $this->status = 'live';
        }

        $this->payment_mode = 'C'; //C = Bank Deposit, 2 = Cash Pick Up, G = Mobile Cash, U = Cash Card
        $this->account_type = 'P'; //P = SAVINGS, C = CHECKING
    }

    /**
     * Get the available countries
     *
     * @return array
     *
     * @throws Exception
     */
    public function getCountries()
    {
        $url = 'catalogs/countries';
        $response = $this->getData($url);

        return $response;
    }

    /**
     * Base function that is responsible for interacting directly with the transpay api to obtain data
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

    /**
     * Get the states for a specific country
     *
     * @return array
     *
     * @throws Exception
     */
    public function getStates($country)
    {
        $url = 'catalogs/states?';
        $params = ['CountryIsoCode' => $country];
        $response = $this->getData($url, $params);

        return $response;
    }

    /**
     * Get the cities for a given country and state
     *
     * @return array
     *
     * @throws Exception
     */
    public function getCities($country, $state)
    {
        $url = 'catalogs/cities?';
        $params = ['CountryIsoCode' => $country, 'StateId' => $state];
        $response = $this->getData($url, $params);

        return $response;
    }

    /**
     * Get the towns for a given country, state and city
     *
     * @return array
     *
     * @throws Exception
     */
    public function getTowns($country, $state, $city)
    {
        $url = 'catalogs/towns?';
        $params = ['CountryIsoCode' => $country, 'StateId' => $state, 'CityId' => $city];
        $response = $this->getData($url, $params);

        return $response;
    }

    /**
     * Get the available banks for a country
     *
     * @return array
     *
     * @throws Exception
     */
    public function getBanks($country)
    {
        $url = 'catalogs/banks?';
        $params = ['CountryIsoCode' => $country];
        $response = $this->getData($url, $params);

        return $response;
    }

    /**
     * Get the branches of a bank using the code of the bank and the city
     *
     * @return array
     *
     * @throws Exception
     */
    public function getBanksBranch($bank, $state, $city)
    {
        $url = 'catalogs/BankBranch?';
        $params = ['BankId' => $bank, 'CityID' => $city, 'StateId' => $state];
        $response = $this->getData($url, $params);

        return $response;
    }

    /**
     * Gets available branches from payers
     *
     * @return array
     *
     * @throws Exception
     */
    public function getBranchPayers($country, $state, $city, $receiveCurrencyIsoCode, $bank, $paymentMode, $sourceCurrencyIsoCode)
    {
        $url = 'catalogs/payers?';
        $params = [
            'CountryIsoCode' => $country, 'StateId' => $state, 'CityId' => $city, 'PaymentModeId' => $paymentMode,
            'SourceCurrencyIsoCode' => $sourceCurrencyIsoCode, 'ReceiveCurrencyIsoCode' => $receiveCurrencyIsoCode,
            'BankId' => $bank,
        ];

        return $this->getData($url, $params);
    }

    /**
     * Get the occupations available to select from the sender of the payment
     *
     * @return array
     *
     * @throws Exception
     */
    public function getSenderOccupation()
    {
        $url = 'catalogs/SenderOccupation';
        $response = $this->getData($url);

        return $response;
    }

    /**
     * Gets the fields required to send a payment
     *
     * @return array
     *
     * @throws Exception
     */
    public function getRequiredFields()
    {
        $url = 'requiredfields/postinvoice';
        $response = $this->getData($url);
        if ($response['status'] == '200') {
            return $response;
        }

        return $response;
    }

    /**
     * Get the different currencies available in the country, state, city and mode of payment
     *
     * @return array
     *
     * @throws Exception
     */
    public function getCurrencies($country, $state, $city, $paymentMode)
    {
        $url = 'transaction/receivercurrencies?';
        $params = [
            'CountryIsoCode' => $country,
            'StateId' => $state,
            'CityId' => $city,
            'PaymentModeId' => $paymentMode,
        ];

        return $this->getData($url, $params);
    }

    /**
     * Get the payment modes for a given country, state and city
     *
     * @return array
     *
     * @throws Exception
     */
    public function getPaymentModes($country, $state, $city)
    {
        $url = 'catalogs/paymentmodes?';
        $params = ['CountryIsoCode' => $country, 'StateId' => $state, 'CityId' => $city];
        $response = $this->getData($url, $params);

        return $response;
    }

    /**
     * Get the payout limits for a given country, city, payment mode, receiver currency iso code and source currency iso code
     *
     * @return array
     *
     * @throws Exception
     */
    public function getPayoutLimits($country, $city, $paymentMode, $receiveCurrencyIsoCode, $sourceCurrencyIsoCode)
    {
        $url = 'catalogs/paymentmodes?';
        $params = ['ReceiverCountryIsoCode' => $country, 'ReceiverCityId' => $city, 'PaymentModeId' => $paymentMode,
            'ReceiveCurrencyIsoCode' => $receiveCurrencyIsoCode, 'SourceCurrencyIsoCode' => $sourceCurrencyIsoCode];
        $response = $this->getData($url, $params);

        return $response;
    }

    /**
     * Get available Nationality
     *
     * @return array
     *
     * @throws Exception
     */
    public function getNationality($country)
    {
        $url = 'catalogs/Nationality?';
        $params = ['CountryIsoCode' => $country];
        $response = $this->getData($url, $params);

        return $response;
    }

    /**
     * Returns the list of source of funds applicable to the branch
     *
     * @return array
     *
     * @throws Exception
     */
    public function getSourceOfFunds()
    {
        $url = 'catalogs/sourceoffunds';
        $response = $this->getData($url);

        return $response;
    }

    /**
     * Get the receivers type of id for a given country
     *
     * @return array
     *
     * @throws Exception
     */
    public function getReceiversTypeOfId($country)
    {
        $url = 'catalogs/receiverstypeofid?';
        $params = ['CountryIsoCode' => $country];
        $response = $this->getData($url, $params);

        return $response;
    }

    /**
     * Get the senders type of id for a given country
     *
     * @return array
     *
     * @throws Exception
     */
    public function getSendersTypeOfId($country)
    {
        $url = 'catalogs/senderstypeofid?';
        $params = ['CountryIsoCode' => $country];
        $response = $this->getData($url, $params);

        return $response;
    }

    /**
     * Returns the list of countries supporing cash pickup anywhere.
     * Note, a Payer ID is not required for these countries when creating a transaction
     * Get available cash pickup country
     *
     * @param  $country
     * @return array
     *
     * @throws Exception
     */
    public function getCashPickupCountry()
    {
        $url = 'transaction/cashpickupcountry';
        $response = $this->getData($url);

        return $response;
    }

    /**
     * Returns the list of countries that support at least one type of payment mode
     * (e.g, Bank Deposit, MPESA, or Cash PickUp, or MPESA).
     *
     * @return array
     *
     * @throws Exception
     */
    public function getReceiveCountries()
    {
        $url = 'transaction/receivecountries';

        return $this->getData($url);
    }

    /**
     * Get the transaction info (calculator) for a given country, payment mode id, receiver currency iso code,
     * source currency iso code and sent amount
     * mandatory field are ReceiverCountryIsoCode, PaymentModeId, ReceiveCurrencyIsoCode, SourceCurrencyIsoCode,
     * optional field are ReceiverCityId, ReceiverTownId, SenderId, SenderLoyaltyCardNumber, PayingAgentId, Rate, FeeProduct
     * if required use bank transfer
     * if use any SentAmount/ReceiveAmount
     * Optional for Cash pick-up anywhere and Bank Deposit PayerId
     *
     * @return array
     *
     * @throws Exception
     */
    public function getTransactionInfo($inputData)
    {
        $url = 'transaction/transactioninfo?';
        $params['ReceiverCountryIsoCode'] = isset($inputData->trans_fast_receiver_country_iso_code) ? $inputData->trans_fast_receiver_country_iso_code : 'BD';
        $params['ReceiverCityId'] = isset($inputData->trans_fast_receiver_city_id) ? $inputData->trans_fast_receiver_city_id : null;
        $params['ReceiverTownId'] = isset($inputData->trans_fast_receiver_town_id) ? $inputData->trans_fast_receiver_town_id : null;
        if (isset($inputData->trans_fast_payer_id) && $inputData->trans_fast_payer_id != null) {
            $params['PayerId'] = isset($inputData->trans_fast_payer_id) ? $inputData->trans_fast_payer_id : null;
        }
        if (isset($inputData->trans_fast_paying_branch_id) && $inputData->trans_fast_paying_branch_id != null) {
            $params['PayingAgentId'] = ((isset($inputData->trans_fast_paying_branch_id) ? $inputData->trans_fast_paying_branch_id : null));
        }
        if ($inputData->recipient_type_name == 'Cash' || $inputData->recipient_type_name == 'Cash Pickup') {
            $params['PaymentModeId'] = 2;
        } elseif ($inputData->recipient_type_name == 'Wallet') {
            $params['PaymentModeId'] = 'G';
        } else {
            $params['PaymentModeId'] = 'C';
            $params['BankId'] = isset($inputData->trans_fast_bank_id) ? $inputData->trans_fast_bank_id : null;
        }
        $params['ReceiveCurrencyIsoCode'] = isset($inputData->transfer_currency) ? $inputData->transfer_currency : 'BDT';
        //$params['SentAmount'] = isset($inputData->sender_amount)?$inputData->sender_amount:0;
        $params['SourceCurrencyIsoCode'] = isset($inputData->sender_currency) ? $inputData->sender_currency : 'SGD';
        $params['SenderLoyaltyCardNumber'] = isset($inputData->sender_loyaty_card_number) ? $inputData->sender_loyaty_card_number : null;
        $params['ReceiveAmount'] = isset($inputData->transfer_amount) ? $inputData->transfer_amount : 0;
        $params['Rate'] = isset($inputData->trans_fast_rate) ? $inputData->trans_fast_rate : null;
        $params['FeeProduct'] = isset($inputData->trans_fast_product_fee) ? $inputData->trans_fast_product_fee : null;
        //dd($params);
        $response = $this->getData($url, $params);

        return $response;
    }

    /**
     * Get the BankDetailByRoutingNo for a given routing number and country
     *
     * @return array
     *
     * @throws Exception
     */
    public function getBankDetailByRoutingNo($country, $routingNumber)
    {
        $url = 'transaction/BankDetailByRoutingNo?';
        $params = ['CountryIsoCode' => $country, 'RoutingNumber' => $routingNumber];
        $response = $this->getData($url, $params);

        return $response;
    }

    /**
     * Get the getByReferenceNumber for a given reference number
     *
     * @return array
     *
     * @throws Exception
     */
    public function getByReferenceNumber($referenceNumber)
    {
        $url = 'transaction/ByReferenceNumber?';
        //$url = 'transaction/ByReferenceNo?';
        $params = ['ReferenceNumber' => $referenceNumber];
        $response = $this->getData($url, $params);

        return $response;
    }

    /**
     * Get the status of a transaction through its tfpin
     *
     * @return array
     *
     * @throws Exception
     */
    public function getTransactionStatus($tfpin)
    {
        $url = 'transaction/bytfpin?';
        $params = ['TfPin' => $tfpin];

        return $this->getData($url, $params);
    }

    /**
     * Get the getCountryRates by source currency iso code and receive country iso code
     *
     * @param  string  $feeProduct
     * @return array
     *
     * @throws Exception
     */
    public function getCountryRates($sourceCurrencyIsoCode, $receiveCountryIsoCode, $feeProduct = '')
    {
        $url = 'rates/countryrates?';
        $params = ['SourceCurrencyIsoCode' => $sourceCurrencyIsoCode, 'ReceiveCountryIsoCode' => $receiveCountryIsoCode,
            'FeeProduct' => $feeProduct];

        return $this->getData($url, $params);
    }

    /**
     * Get the getTransactionList by start date and end date
     *
     * @return array
     *
     * @throws Exception
     */
    public function getTransactionList($startDate, $endDate)
    {
        $url = 'transaction/bytimeinterval?';
        $params['StartDate'] = $startDate;
        $params['EndDate'] = $endDate;

        //$params['InvoiceStatusId'] = $invoiceStatusId;
        //$params['StartIndex'] = $startIndex;
        //$params['PageSize'] = $pageSize;
        return $this->getData($url, $params);
    }

    /**
     * Display the Sender details by providing a Sender ID.
     *
     * @return array
     *
     * @throws Exception
     */
    public function getSenderBySenderID($senderId)
    {
        $url = 'transaction/senderbyid?';
        $params = ['SenderId' => $senderId];

        return $this->getData($url, $params);
    }

    /**
     * Returns a collection of commissions by country based on filter context and pagination parameters.
     *
     * @param  string  $feeProduct
     * @return array
     *
     * @throws Exception
     */
    public function getCommissionByCountry($sourceCurrencyIsoCode, $receiveCountryIsoCode, $feeProduct = '')
    {
        $url = 'commissions/bycountry?';
        $params = ['SourceCurrencyIsoCode' => $sourceCurrencyIsoCode, 'ReceiveCountryIsoCode' => $receiveCountryIsoCode,
            'FeeProduct' => $feeProduct];

        return $this->getData($url, $params);
    }

    /**
     * Retrieve a catalog of complaint types that should be used when creating a complaint
     *
     * @param  $onlyForCustomerCare  -- i.e. true or false
     * @return array
     *
     * @throws Exception
     */
    public function getComplaintType($onlyForCustomerCare)
    {
        $url = 'catalogs/complainttype?';
        $params = ['OnlyForCustomerCare' => $onlyForCustomerCare];

        return $this->getData($url, $params);
    }

    /**
     * Return a collection of cancel reasons available
     *
     * @return array
     *
     * @throws Exception
     */
    public function getCancelReasons()
    {
        $url = 'catalogs/cancelreasons';

        return $this->getData($url);
    }

    /**
     * Return a collection of form of payments
     *
     * @return array
     *
     * @throws Exception
     */
    public function getFormOfPayments()
    {
        $url = 'catalogs/formofpayments';

        return $this->getData($url);
    }

    /**
     * Return a collection of form of remittance purposes
     *
     * @return array
     *
     * @throws Exception
     */
    public function getRemittancePurposes($country)
    {
        $url = 'catalogs/remittancepurposes?';
        $params = ['CountryIsoCode' => $country];

        return $this->getData($url, $params);
    }

    /**
     * Return a list of generated TfPins
     *
     * @param  string  $generatedUnused
     * @return array
     *
     * @throws Exception
     */
    public function getGeneratedTransFastPins($country, $payerId, $numberOfPins, $generatedUnused = '')
    {
        $url = 'transaction/tfpins?';
        $params = ['CountryIsoCode' => $country, 'PayerId' => $payerId, 'NumberOfPins' => $numberOfPins, 'GeneratedUnused' => $generatedUnused];

        return $this->getData($url, $params);
    }

    /**
     * Return the balance and credit information for current branch and the specified currency
     *
     * @return array
     *
     * @throws Exception
     */
    public function getAccountingBalance($currencyIsoCode)
    {
        $url = 'accounting/balanceandcredit?';
        $params = ['CurrencyIsoCode' => $currencyIsoCode];

        return $this->getData($url, $params);
    }

    /**
     * Return the valid banking account types for bank deposits
     *
     * @return array
     *
     * @throws Exception
     */
    public function getAccountType($countryIsoCode)
    {
        $url = 'catalogs/accounttypes?';
        $params = ['CountryIsoCode' => $countryIsoCode];

        return $this->getData($url, $params);
    }

    /**
     * Approve transaction given a TfPin (transaction number). Only approve transaction that is pending for approval.
     * Approval right to the user is required
     *
     * @return array
     *
     * @throws Exception
     */
    public function putReleaseTransaction($transFastPin)
    {
        $url = 'transaction/release';
        $params['TfPin'] = $transFastPin;

        return $this->putPostData($url, $params, 'PUT');
    }

    /**
     * Base function that is responsible for interacting directly with the trans fast api to send data
     *
     * @param  string  $method
     * @return array
     *
     * @throws Exception
     */
    public function putPostData($url, $dataArray, $method = 'POST')
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
            'Content-Type: application/json',
            'Authorization: Credentials '.$this->config[$this->status]['token']]
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
     * Cancel transaction given a TfPin (transaction number) and reason code (reason for cancellation).
     *
     * @return array
     *
     * @throws Exception
     */
    public function putCancelTransaction($transFastPin, $reasonId)
    {
        $url = 'transaction/cancel';
        $params['TfPin'] = $transFastPin;
        $params['ReasonId'] = $reasonId;

        return $this->putPostData($url, $params, 'PUT');
    }

    /**
     * Modify information for an existing Sender ID
     *
     * @return array
     *
     * @throws Exception
     */
    public function putModifySenderID(
        $senderId, $name, $address, $phoneHome, $phoneWork, $isIndividual, $countryISO, $phoneMobile, $email, $stateId,
        $cityId, $typeOfId, $idNumber, $idExpiryDate, $dateOfBirth, $nationalityIsoCode, $senderOccupation
    ) {
        $url = 'transaction/senderinfo';
        $params['SenderId'] = $senderId;
        $params['Name'] = $name;
        $params['Address'] = $address;
        $params['PhoneMobile'] = $phoneMobile;
        $params['PhoneHome'] = $phoneHome;
        $params['PhoneWork'] = $phoneWork;
        $params['IsIndividual'] = $isIndividual;
        $params['CountryIsoCode'] = $countryISO;
        $params['DateOfBirth'] = $dateOfBirth;
        $params['CityId'] = $cityId;
        $params['StateId'] = $stateId;
        $params['TypeOfId'] = $typeOfId;
        $params['IdNumber'] = $idNumber;
        $params['IdExpiryDate'] = $idExpiryDate;
        $params['NationalityIsoCode'] = $nationalityIsoCode;
        $params['SenderOccupation'] = $senderOccupation;
        $params['Email'] = $email;

        return $this->putPostData($url, $params, 'PUT');
    }

    /**
     * Read complaint/petition from Trans-Fast.
     * Once read, the message won’t be retrievable again.
     * Allows a single TFPIN to be retrieved or all petitions within a date range
     *
     * @return array
     *
     * @throws Exception
     */
    public function putReadCustomerCareComplaintOrPetition($transFastPin, $startDate, $endDate, $isReadAll = 'true')
    {
        $url = 'customercare/complaints';
        $params['TfPin'] = $transFastPin;
        $params['StartDate'] = $startDate;
        $params['EndDate'] = $endDate;
        $params['IsReadAll'] = $isReadAll;

        return $this->putPostData($url, $params, 'PUT');
    }

    /**
     * Send a complaint/petition to Trans-Fast.
     * The petition type identifies to which department the message is routed to
     *
     * @return array
     *
     * @throws Exception
     */
    public function postCustomerCareComplaintOrPetition($transFastPin, $petitionType, $message)
    {
        $url = 'customercare/complaints';
        $params['TfPin'] = $transFastPin;
        $params['PetitionType'] = $petitionType;
        $params['Description'] = $message;

        return $this->putPostData($url, $params);
    }

    /**
     * Create a new sender ID or modify an existing one based on sender information provided.
     * The Sender ID can then be used instead of the sender information when creating an invoice
     *
     * @return array
     *
     * @throws Exception
     */
    public function postCreateSenderID(
        $name, $address, $phoneHome, $phoneWork, $isIndividual, $countryISO, $phoneMobile, $email, $stateId,
        $cityId, $typeOfId, $idNumber, $idExpiryDate, $dateOfBirth, $nationalityIsoCode, $senderOccupation
    ) {
        $url = 'transaction/sender';
        $params['Name'] = $name;
        //$params['NameOtherLanguage'] = $nameOtherLanguage;
        $params['Address'] = $address;
        //$params['AddressOtherLanguage'] = $addressOtherLanguage;
        $params['PhoneMobile'] = $phoneMobile;
        $params['PhoneHome'] = $phoneHome;
        $params['PhoneWork'] = $phoneWork;
        //$params['ZipCode'] = $zipCode;
        $params['CityId'] = $cityId;
        $params['StateId'] = $stateId;
        $params['CountryIsoCode'] = $countryISO;
        $params['TypeOfId'] = $typeOfId;
        $params['IdNumber'] = $idNumber;
        $params['IdExpiryDate'] = $idExpiryDate;
        $params['NationalityIsoCode'] = $nationalityIsoCode;
        $params['DateOfBirth'] = $dateOfBirth;
        $params['Email'] = $email;
        $params['IsIndividual'] = $isIndividual;
        $params['SenderOccupation'] = $senderOccupation;

        return $this->putPostData($url, $params);
    }

    /**
     * Send a valid transaction to Transfast.
     * The input will be validated and the service will return corresponding response (reject or not).
     *
     * @return array
     *
     * @throws Exception
     */
    public function postCreateTransaction($data)
    {
        if ($data->recipient_type_name == 'Cash' || $data->recipient_type_name == 'Cash Pickup') {
            $this->payment_mode = 2;
        } elseif ($data->recipient_type_name == 'Wallet') {
            $this->payment_mode = 'G';
        }
        $url = 'transaction/invoice';

        //Sender Information
        $params['Sender']['LoyaltyCardNumber'] = ((isset($data->sender_loyalty_card_number) ? $data->sender_loyalty_card_number : null));
        $params['Sender']['Name'] = ((isset($data->sender_first_name) ? $data->sender_first_name : null));
        //$params['Sender']['NameOtherLanguage'] = ((isset($data->sender_name_other_language)?$data->sender_name_other_language:null));
        $params['Sender']['Address'] = ((isset($data->sender_address) ? $data->sender_address : null));
        //$params['Sender']['AddressOtherLanguage'] = ((isset($data->sender_address_other_language)?$data->sender_address_other_language:null));
        $params['Sender']['PhoneMobile'] = ((isset($data->sender_mobile) ? $data->sender_mobile : null));
        //$params['Sender']['PhoneHome'] = ((isset($data->sender_mobile_home)?$data->sender_mobile_home:null));
        //$params['Sender']['PhoneWork'] = ((isset($data->sender_mobile_work)?$data->sender_mobile_work:null));
        $params['Sender']['ZipCode'] = ((isset($data->sender_zipcode) ? $data->sender_zipcode : null));
        $params['Sender']['CityId'] = ((isset($data->trans_fast_sender_city_id) ? $data->trans_fast_sender_city_id : '94702'));
        if (isset($data->trans_fast_sender_state_id) && $data->trans_fast_sender_state_id != 'NA') {
            $params['Sender']['StateId'] = ((isset($data->trans_fast_sender_state_id) ? $data->trans_fast_sender_state_id : 'SGP01'));
        } else {
            $params['Sender']['StateId'] = 'SGP01';
        }
        $params['Sender']['CountryIsoCode'] = ((isset($data->trans_fast_sender_country_iso_code) ? $data->trans_fast_sender_country_iso_code : null));
        $params['Sender']['TypeOfId'] = ((isset($data->trans_fast_sender_id_type_id) ? $data->trans_fast_sender_id_type_id : null));
        $params['Sender']['IdNumber'] = ((isset($data->sender_id_number) ? $data->sender_id_number : null));
        $params['Sender']['IdExpiryDate'] = ((isset($data->sender_expire_date) ? date('Y-m-d', strtotime($data->sender_expire_date)) : null));
        $params['Sender']['NationalityIsoCode'] = ((isset($data->trans_fast_sender_nationality) ? $data->trans_fast_sender_nationality : null));
        $params['Sender']['DateOfBirth'] = ((isset($data->sender_date_of_birth) ? date('Y-m-d', strtotime($data->sender_date_of_birth)) : null));
        //$params['Sender']['Email'] = ((isset($data->sender_email)?$data->sender_email:null));
        $params['Sender']['IsIndividual'] = 'true';
        $params['Sender']['SenderOccupaton'] = ((isset($data->sender_occupation) ? $data->sender_occupation : null));

        //Receiver Information
        $params['Receiver']['FirstName'] = ((isset($data->receiver_first_name) ? $data->receiver_first_name : null));
        //$params['Receiver']['FirstNameOtherLanguage'] = ((isset($data->receiver_first_name_other_language)?$data->receiver_first_name_other_language:null));
        $params['Receiver']['SecondName'] = ((isset($data->receiver_middle_name) ? $data->receiver_middle_name : null));
        //$params['Receiver']['SecondNameOtherLanguage'] = ((isset($data->receiver_middle_name_other_language)?$data->receiver_middle_name_other_language:null));
        $params['Receiver']['LastName'] = ((isset($data->receiver_last_name) ? $data->receiver_last_name : null));
        //$params['Receiver']['LastNameOtherLanguage'] = ((isset($data->receiver_last_name_other_language)?$data->receiver_last_name_other_language:null));
        //$params['Receiver']['SecondLastName'] = ((isset($data->receiver_second_last_name)?$data->receiver_second_last_name:null));
        //$params['Receiver']['SecondLastNameOtherLanguage'] = ((isset($data->receiver_second_last_name_other_language)?$data->receiver_second_last_name_other_language:null));
        //$params['Receiver']['FullNameOtherLanguage'] = ((isset($data->receiver_full_name_other_language)?$data->receiver_full_name_other_language:null));
        $params['Receiver']['CompleteAddress'] = ((isset($data->receiver_address) ? $data->receiver_address : null));
        //$params['Receiver']['CompleteAddressOtherLanguage'] = ((isset($data->receiver_address_other_language)?$data->receiver_address_other_language:null));
        //$params['Receiver']['StateId'] = ((isset($data->trans_fast_receiver_state_id)?$data->trans_fast_receiver_state_id:null));
        //$params['Receiver']['CityId'] = ((isset($data->trans_fast_receiver_city_id)?$data->trans_fast_receiver_city_id:null));
        //$params['Receiver']['TownId'] = ((isset($data->trans_fast_receiver_town_id)?$data->trans_fast_receiver_town_id:null));
        $params['Receiver']['CountryIsoCode'] = ((isset($data->trans_fast_receiver_country_iso_code) ? $data->trans_fast_receiver_country_iso_code : null));
        $params['Receiver']['MobilePhone'] = ((isset($data->receiver_contact_number) ? $data->receiver_contact_number : null));
        //$params['Receiver']['HomePhone'] = ((isset($data->receiver_home_contact_number)?$data->receiver_home_contact_number:null));
        //$params['Receiver']['WorkPhone'] = ((isset($data->receiver_work_contact_number)?$data->receiver_work_contact_number:null));
        $params['Receiver']['ReceiverCityId'] = ((isset($data->trans_fast_receiver_city_id) ? $data->trans_fast_receiver_city_id : null));
        //$params['Receiver']['ZipCode'] = ((isset($data->receiver_zip_code)?$data->receiver_zip_code:null));
        //$params['Receiver']['NationalityIsoCode'] = ((isset($data->trans_fast_receiver_nationality_iso_code)?$data->trans_fast_receiver_nationality_iso_code:null));
        //$params['Receiver']['IsIndividual'] = true;
        //$params['Receiver']['Email'] = ((isset($data->receiver_email)?$data->receiver_email:null));
        //$params['Receiver']['Cpf'] = ((isset($data->receiver_cpf_id)?$data->receiver_cpf_id:null));
        //$params['Receiver']['ReceiverTypeOfId'] = ((isset($data->trans_fast_receiver_type_of_id)?$data->trans_fast_receiver_type_of_id:null));
        //$params['Receiver']['ReceiverIdNumber'] = ((isset($data->trans_fast_receiver_id_number)?$data->trans_fast_receiver_id_number:null));
        //$params['Receiver']['Notes'] = ((isset($data->receiver_notes)?$data->receiver_notes:null));
        //$params['Receiver']['NotesOtherLanguage'] = ((isset($data->receiver_notes_other_language)?$data->receiver_notes_other_language:null));

        //Transaction Information
        $params['TransactionInfo']['PaymentModeId'] = $this->payment_mode;
        $params['TransactionInfo']['ReceiveCurrencyIsoCode'] = ((isset($data->transfer_currency) ? $data->transfer_currency : 'BDT'));
        if (isset($data->trans_fast_payer_id) && $data->trans_fast_payer_id != null) {
            $params['TransactionInfo']['PayerId'] = ((isset($data->trans_fast_payer_id) ? $data->trans_fast_payer_id : null));
        }
        if (isset($data->trans_fast_paying_branch_id) && $data->trans_fast_paying_branch_id != null) {
            $params['TransactionInfo']['PayingBranchId'] = ((isset($data->trans_fast_paying_branch_id) ? $data->trans_fast_paying_branch_id : null));
        }
        $params['TransactionInfo']['PurposeOfRemittanceId'] = ((isset($data->trans_fast_purpose_of_remittance) ? $data->trans_fast_purpose_of_remittance : 1));
        $params['TransactionInfo']['SourceCurrencyIsoCode'] = ((isset($data->sender_currency) ? $data->sender_currency : null));
        //$params['TransactionInfo']['Rate'] = ((isset($data->transaction_exchange_rate)?$data->transaction_exchange_rate:null));
        //$params['TransactionInfo']['Rate'] = ((isset($data->transaction_total_sent_amount)?$data->transaction_total_sent_amount:null));
        //$params['TransactionInfo']['SentAmount'] = ((isset($data->sender_amount)?$data->sender_amount:null));
        //$params['TransactionInfo']['ServiceFee'] = ((isset($data->transaction_service_fee)?$data->transaction_service_fee:null));
        //$params['TransactionInfo']['USDServiceFee'] = ((isset($data->transaction_usd_service_fee)?$data->transaction_usd_service_fee:null));
        $params['TransactionInfo']['ReceiveAmount'] = ((isset($data->transfer_amount) ? $data->transfer_amount : null));
        //$params['TransactionInfo']['CashAmount'] = ((isset($data->transfer_amount)?$data->transfer_amount:null));
        //$params['TransactionInfo']['Payout'] = ((isset($data->transfer_amount)?$data->transfer_amount:null));
        $params['TransactionInfo']['FormOfPaymentId'] = 'ACH';
        $params['TransactionInfo']['ReferenceNumber'] = ((isset($data->purchase_number) ? $data->purchase_number : null));
        //$params['TransactionInfo']['ReferenceNumber'] = mt_rand(1000000, 9999999);
        $params['TransactionInfo']['SourceOfFundsID'] = ((isset($data->trans_fast_sender_source_of_fund_id) ? $data->trans_fast_sender_source_of_fund_id : null));
        //$params['TransactionInfo']['FeeProduct'] = ((isset($data->trans_fast_front_end_or_back_end)?$data->trans_fast_front_end_or_back_end:null));
        if ($this->payment_mode == 'C') {
            $params['TransactionInfo']['BankId'] = ((isset($data->trans_fast_bank_id) ? $data->trans_fast_bank_id : null));
            $params['TransactionInfo']['BankBranchId'] = (isset($data->location_routing_id[1]->bank_branch_location_field_value) ? $data->location_routing_id[1]->bank_branch_location_field_value : null);
            $params['TransactionInfo']['Account'] = ((isset($data->bank_account_number) ? $data->bank_account_number : null));
            $params['TransactionInfo']['AccountTypeId'] = $this->account_type;
        } elseif ($this->payment_mode == 'G') {
            $params['TransactionInfo']['Account'] = ((isset($data->bank_account_number) ? $data->bank_account_number : null));
        }

        //Compliance Information
        $params['Compliance']['CountryIssueIsoCode'] = ((isset($data->trans_fast_sender_country_iso_code) ? $data->trans_fast_sender_country_iso_code : null));
        if (isset($data->trans_fast_sender_state_id) && $data->trans_fast_sender_state_id != 'NA') {
            $params['Compliance']['StateIssueId'] = ((isset($data->trans_fast_sender_state_id) ? $data->trans_fast_sender_state_id : 'SGP01'));
        } else {
            $params['Compliance']['StateIssueId'] = 'SGP01';
        }
        //$params['Compliance']['StateIssueId'] = ((isset($data->trans_fast_sender_state_id)?$data->trans_fast_sender_state_id:null));
        $params['Compliance']['ReceiverRelationship'] = ((isset($data->sender_beneficiary_relationship) ? $data->sender_beneficiary_relationship : null));
        $params['Compliance']['SourceOfFundsID'] = ((isset($data->trans_fast_sender_source_of_fund_id) ? $data->trans_fast_sender_source_of_fund_id : null));
        $params['Compliance']['Ssn'] = ((isset($data->sender_ssn_number) ? $data->sender_ssn_number : null));
        $params['Compliance']['SenderOccupation'] = ((isset($data->sender_occupation) ? $data->sender_occupation : null));
        //$params['Compliance']['SenderEmployerName'] = ((isset($data->sender_employer_name)?$data->sender_employer_name:null));
        //$params['Compliance']['SenderEmployerAddress'] = ((isset($data->sender_employer_address)?$data->sender_employer_address:null));
        //$params['Compliance']['SenderEmployerPhone'] = ((isset($data->sender_employer_phone)?$data->sender_employer_phone:null));
        //$params['Compliance']['ReceiverDateOfBirth'] = ((isset($data->receiver_date_of_birth)?date('Y-m-d', strtotime($data->receiver_date_of_birth)):null));
        $params['Compliance']['SenderDateOfBirth'] = ((isset($data->sender_date_of_birth) ? date('Y-m-d', strtotime($data->sender_date_of_birth)) : null));
        $params['Compliance']['TypeOfId'] = ((isset($data->trans_fast_sender_id_type_id) ? $data->trans_fast_sender_id_type_id : null));
        $params['Compliance']['IdNumber'] = ((isset($data->sender_id_number) ? $data->sender_id_number : null));
        $params['Compliance']['RemittanceReasonId'] = 'A';
        $params['Compliance']['ReceiverFullName'] = ((isset($data->receiver_first_name) ? $data->receiver_first_name : null).(isset($data->receiver_middle_name) ? ' '.$data->receiver_middle_name : null).(isset($data->receiver_last_name) ? ' '.$data->receiver_last_name : null));
        $params['Compliance']['SenderFullName'] = ((isset($data->sender_first_name) ? $data->sender_first_name : null));

        //dd($params);
        return $this->putPostData($url, $params);
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
