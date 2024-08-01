<?php

namespace Fintech\Remit\Http\Controllers;

use Fintech\Remit\Vendors\IslamiBankApi;
use Fintech\Transaction\Services\OrderService;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;

class VendorTestController extends Controller
{
    public function islamiBankAccountTypeCode()
    {
        $vendor = app()->make(IslamiBankApi::class);

        return $vendor->accountTypeCode('');
    }

    public function islamiBankFetchBalance(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        dump($vendor->fetchBalance('BDT'));
    }

    public function islamiBankFetchAccountDetail(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        $order = $repo->list(['service_id' => 13, 'sort' => 'orders.id', 'dir' => 'desc'])->first();
        $order_data = $order->order_data;
        dump($vendor->fetchAccountDetail($order_data));
    }

    public function islamiBankFetchRemittanceStatus(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $data['transaction_reference_number'] = 'GIT4296253';
        $data['secret_key'] = '';
        dump($vendor->fetchRemittanceStatus($data));
    }

    public function islamiBankSpotCash(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        $order = $repo->list(['service_id' => 14, 'sort' => 'orders.id', 'dir' => 'desc'])->first();
        $order_data = $order->order_data;
        $order_data['beneficiary_data']['reference_no'] = 'TEST'.time();
        $order_data['sending_amount'] = $order['amount'];
        $order_data['sending_currency'] = $order['currency'];
        dump($vendor->directCreditRemittance($order_data));
    }

    public function islamiBankAccountPayee(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        $order = $repo->list(['service_id' => 13, 'sort' => 'orders.id', 'dir' => 'desc'])->first();
        $order_data = $order->order_data;
        $order_data['beneficiary_data']['reference_no'] = 'TEST'.time();
        $order_data['sending_amount'] = $order['amount'];
        $order_data['sending_currency'] = $order['currency'];
        dump($vendor->directCreditRemittance($order_data));
    }

    public function islamiBankThirdBank(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        $order = $repo->list(['service_id' => 13, 'sort' => 'orders.id', 'dir' => 'desc'])->first();
        $order_data = $order->order_data;
        $order_data['beneficiary_data']['reference_no'] = 'TEST'.time();
        $order_data['sending_amount'] = $order['amount'];
        $order_data['sending_currency'] = $order['currency'];
        dump($vendor->directCreditRemittance($order_data));
    }

    public function islamiBankFetchMobileBankingMCash(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        $order = $repo->list(['service_id' => 19, 'sort' => 'orders.id', 'dir' => 'desc'])->first();
        $order_data = $order->order_data;
        //$order_data['beneficiary_data']['receiver_information']['beneficiary_data']['wallet_account_number'] = '016147470541';
        dump($vendor->fetchAccountDetail($order_data));
    }

    public function islamiBankMobileBankingMCash(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        $order = $repo->list(['service_id' => 19, 'sort' => 'orders.id', 'dir' => 'desc'])->first();
        $order_data = $order->order_data;
        $order_data['beneficiary_data']['reference_no'] = 'TEST'.time();
        $order_data['sending_amount'] = $order['amount'];
        $order_data['sending_currency'] = $order['currency'];
        dump($vendor->directCreditRemittance($order_data));
    }

    public function islamiBankFetchRemittanceCard(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $data['account_number'] = '34';
        $data['account_type'] = '71';
        $data['branch_code'] = '123';
        dump($vendor->fetchAccountDetail($data));
    }

    public function islamiBankRemittanceCard(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        //$order = $repo->find(265);
        //$order_data = $order->order_data;
        $order = [
            'id' => 265,
            'source_country_id' => 231,
            'destination_country_id' => 19,
            'parent_id' => null,
            'sender_receiver_id' => 10,
            'user_id' => 6,
            'service_id' => 13,
            'transaction_form_id' => 6,
            'ordered_at' => '2024-04-22 09:49:47',
            'amount' => '10000.000000',
            'currency' => 'BDT',
            'converted_amount' => '131.995776',
            'converted_currency' => 'AED',
            'order_number' => 'ARE00000000000000265',
            'risk_profile' => 'green',
            'notes' => null,
            'is_refunded' => 0,
            'order_data' => '{"bank_id": 18, "user_name": "Test User 1", "created_at": "2024-04-22T09:49:48.694109Z", "created_by": "Test User 1", "fund_source": "Bank Deposit", "assign_order": "no", "request_from": "web", "service_name": "Bank Transfer", "service_slug": "bank_transfer", "account_number": "88023456789", "bank_branch_id": 3142, "beneficiary_id": 35, "current_amount": 585.997246, "previous_amount": 585.997246, "purchase_number": "ARE00000000000000265", "beneficiary_data": {"reference_no": "MCM00000000000000265", "bank_information": {"bank_data": {"swift_code": "IBBLBDDH", "islami_bank_code": 42}, "bank_name": "Islami Bank Bangladesh Limited", "bank_slug": "islami_bank_bangladesh_limited"}, "branch_information": {"branch_data": {"routing_no": "100154364", "islami_bank_branch_code": 213}, "branch_name": "Head Office Complex"}, "sender_information": {"name": "Test User 1", "email": "testuser1@gmail.com", "mobile": "01600000007", "profile": {"id_doc": {"id_no": "12345678", "id_type": "passport", "id_issue_at": "2024-02-04T00:00:00.000000Z", "id_expired_at": "2029-02-04T00:00:00.000000Z", "id_no_duplicate": "no", "id_issue_country": "Bangladesh"}, "blacklisted": "no", "profile_data": {"note": "Testing", "gender": "male", "occupation": "service", "father_name": "Mustak Ahmed", "mother_name": "Hamida Begum", "nationality": "Bangladeshi", "marital_status": "unmarried", "source_of_income": "salary"}, "date_of_birth": "1999-02-04T00:00:00.000000Z", "present_address": {"address": "Mohammadpur", "city_id": 16152, "state_id": 866, "city_data": null, "city_name": "Ajax", "post_code": "1234", "country_id": 39, "state_data": [], "state_name": "Ontario", "country_data": {"is_serving": true, "language_enabled": true, "multi_currency_enabled": true}, "country_name": "Canada"}}, "currency": "CAD", "language": "en", "fcm_token": "8uqMfhCq6ieLiTm2EKZHDs6eDf76Gv6iFvF8oAiD"}, "receiver_information": {"city_id": 8486, "state_id": 771, "city_data": null, "city_name": "Dhaka", "country_id": 19, "state_data": [], "state_name": "Dhaka District", "relation_id": 3, "country_data": {"is_serving": true, "language_enabled": true, "multi_currency_enabled": true}, "country_name": "Bangladesh", "relation_data": null, "relation_name": "Brother In Law", "beneficiary_data": {"email": "testuser1@gmail.com", "bank_id": 18, "cash_id": 18, "bank_name": "Islami Bank Bangladesh Limited", "cash_name": "Islami Bank Bangladesh Limited", "last_name": null, "wallet_id": null, "first_name": null, "wallet_name": null, "account_name": "recip1 bank", "bank_branch_id": 3142, "instant_bank_id": null, "bank_branch_name": "Head Office Complex", "account_type_code": "02", "instant_bank_name": null, "bank_account_number": "88023456789", "cash_account_number": null, "wallet_account_number": null, "instant_bank_branch_id": null, "instant_bank_branch_name": null, "instant_bank_account_number": null}, "beneficiary_name": "test.instant test.instant", "beneficiary_mobile": "01600000007", "beneficiary_address": null, "beneficiary_type_id": 1, "beneficiary_type_name": "Bank Transfer"}}, "master_user_name": "Afghanistan Master User", "service_stat_data": {"charge": "5%", "discount": "5%", "commission": "3%", "charge_refund": "yes", "discount_refund": "yes", "service_stat_id": 6257, "commission_refund": "yes", "charge_break_down_id": null}, "remittance_purpose": "Family Maintenance or Savings", "beneficiary_type_id": 1, "currency_convert_rate": {"rate": 75.76, "input": "BDT", "amount": "10000", "output": "AED", "converted": 131.99577613516365, "amount_formatted": "AED 10,000.00", "converted_formatted": "BDT 132.00"}, "created_by_mobile_number": "01600000007", "system_notification_variable_failed": "bank_transfer_failed", "system_notification_variable_success": "bank_transfer_success"}',
            'status' => 'successful',
            'creator_id' => null,
            'editor_id' => null,
            'destroyer_id' => null,
            'restorer_id' => null,
            'created_at' => '2024-04-22 09:49:48',
            'updated_at' => '2024-04-22 09:49:48',
            'deleted_at' => null,
            'restored_at' => null,
        ];
        $order_data = json_decode($order['order_data'], true);
        $order_data['beneficiary_data']['reference_no'] = 'TEST'.time();
        $order_data['sending_amount'] = $order['amount'];
        $order_data['sending_currency'] = $order['currency'];
        //dd($order_data);
        dump($vendor->directCreditRemittance($order_data));
    }

    public function islamiBankValidateBeneficiaryWalletBkash(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        $order = $repo->list(['service_id' => 16, 'sort' => 'orders.id', 'dir' => 'desc'])->first();
        $order_data = $order->order_data;
        //$order_data['beneficiary_data']['receiver_information']['beneficiary_data']['wallet_account_number'] = '016147470541';
        dump($vendor->validateBeneficiaryWallet($order_data));
    }

    public function islamiBankWalletTransferBkash(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        $order = $repo->list(['service_id' => 16, 'sort' => 'orders.id', 'dir' => 'desc'])->first();
        $order_data = $order->order_data;
        $order_data['beneficiary_data']['reference_no'] = 'TEST'.time();
        $order_data['sending_amount'] = $order['amount'];
        $order_data['sending_currency'] = $order['currency'];
        dump($vendor->directCreditRemittance($order_data));
    }

    public function islamiBankValidateBeneficiaryWalletNagad(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        $order = $repo->list(['service_id' => 17, 'sort' => 'orders.id', 'dir' => 'desc'])->first();
        $order_data = $order->order_data;
        //$order_data['beneficiary_data']['receiver_information']['beneficiary_data']['wallet_account_number'] = '016147470541';
        dump($vendor->validateBeneficiaryWallet($order_data));
    }

    public function islamiBankWalletTransferNagad(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        $order = $repo->list(['service_id' => 17, 'sort' => 'orders.id', 'dir' => 'desc'])->first();
        $order_data = $order->order_data;
        $order_data['beneficiary_data']['reference_no'] = 'TEST'.time();
        $order_data['sending_amount'] = $order['amount'];
        $order_data['sending_currency'] = $order['currency'];
        dump($vendor->directCreditRemittance($order_data));
    }

    public function meghnaBankConnectionCheck(): void
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://uatrmsapi.meghnabank.com.bd/VSLExchangeAPI/Controller/remitEnquiry?queryType=1&confRate=y',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'bankid: MGBL',
                'agent: 14',
            ],
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            //CURLOPT_USERPWD => 'MGBL@clavisExchange:clavis@6230',
            CURLOPT_USERPWD => sha1('MGBL@clavisExchange').':'.sha1('clavis@6230'),
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
        dump($response);

        $response = Http::withoutVerifying()
            ->acceptJson()
            ->contentType('application/json')
            ->withBasicAuth(sha1('MGBL@clavisExchange'), sha1('clavis@6230'))
            ->withHeaders([
                'bankid' => 'MGBL',
                'agent' => '14',
            ])
            ->get('https://uatrmsapi.meghnabank.com.bd/VSLExchangeAPI/Controller/remitEnquiry',[
                'queryType' => 1,
                'confRate'=>'y'
                ]);

        dd($response->json());
    }

    /*public function meghnaBankConnectionCheck(): void
    {
        // Example usage
        $host = 'uatrmsapi.meghnabank.com.bd';
        $port = 23;
        $path = '/VSLExchangeAPI/Controller/remitEnquiry?queryType=1&confRate=y';
        $user = sha1('MGBL@clavisExchange');
        $password = sha1('clavis@6230');
        $headers = [
            'bankid' => 'MGBL',
            'agent' => '14',
            //'token' => 'yourToken'
        ];

        $response = $this->sendTelnetRequest($host, $port, $path, $user, $password, $headers);
        echo nl2br(htmlspecialchars($response));
    }*/

    public function sslVRConnectionCheck(): void
    {
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://common-api-demo.sslwireless.com/api/service-list',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => [
                'AUTH-KEY: BD6pFSIfSOLEIgKyru67MeBhICkRiFla',
                'STK-CODE: DEMO',
            ],
        ]);

        $response = curl_exec($curl);

        curl_close($curl);
        dump($response);
    }

    public function sendTelnetRequest($host, $port, $path, $user, $password, $headers)
    {
        $fp = fsockopen($host, $port, $errno, $errstr, 30);
        if (! $fp) {
            echo "Error: $errno - $errstr<br />\n";

            return;
        }

        // Create the Basic Auth header
        $auth = base64_encode("$user:$password");
        $headerString = "GET $path HTTP/1.1\r\n";
        $headerString .= "Host: $host\r\n";
        $headerString .= "Authorization: Basic $auth\r\n";

        // Add custom headers
        foreach ($headers as $key => $value) {
            $headerString .= "$key: $value\r\n";
        }

        $headerString .= "Connection: Close\r\n\r\n";

        // Send the request
        fwrite($fp, $headerString);

        // Get the response
        $response = '';
        while (! feof($fp)) {
            $response .= fgets($fp, 128);
        }

        // Close the connection
        fclose($fp);

        return $response;
    }
}
