<?php

namespace Fintech\Remit\Http\Controllers;

use Fintech\Remit\Vendors\IslamiBankApi;
use Fintech\Transaction\Services\OrderService;
use Illuminate\Routing\Controller;

class VendorTestController extends Controller
{
    public function islamiBankFetchBalance(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        dump($vendor->fetchBalance('BDT'));
    }

    public function islamiBankFetchAccountDetail(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $data['account_number'] = '11052';
        $data['account_type'] = '10';
        $data['branch_code'] = '213';
        dump($vendor->fetchAccountDetail($data));
    }

    public function islamiBankFetchRemittanceStatus(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $data['transaction_reference_number'] = 'GIT4296253';
        $data['secret_key'] = '';
        dump($vendor->fetchRemittanceStatus($data));
    }

    public function islamiBankValidateBeneficiaryWallet(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $order = $this->__dummyWalletTransferOrderData();
        $order_data = json_decode($order['order_data'], true);
        $order_data['beneficiary_data']['receiver_information']['beneficiary_data']['wallet_account_number'] = '016147470541';
        //$data['account_number'] = '016147470541';
        //$data['account_type'] = $order_data;
        dump($vendor->validateBeneficiaryWallet($order_data));
    }

    private function __dummyWalletTransferOrderData(): array
    {
        return [
            'id' => 17,
            'source_country_id' => 39,
            'destination_country_id' => 19,
            'parent_id' => null,
            'sender_receiver_id' => 10,
            'user_id' => 8,
            'service_id' => 19,
            'transaction_form_id' => 10,
            'ordered_at' => '2024-05-14 17:13:37',
            'amount' => '5617.700000',
            'currency' => 'BDT',
            'converted_amount' => '55.000000',
            'converted_currency' => 'CAD',
            'order_number' => 'CAN00000000000000017',
            'risk_profile' => 'green',
            'notes' => null,
            'is_refunded' => 0,
            'order_data' => '{"user_name": "Test User 3", "wallet_id": 25, "created_at": "2024-05-14T17:13:37.995533Z", "created_by": "Test User 3", "assign_order": "no", "request_from": "web", "service_name": "MCash", "service_slug": "mbs_m_cash", "beneficiary_id": 6, "current_amount": 50177.38415, "previous_amount": 50177.38415, "purchase_number": "CAN00000000000000017", "beneficiary_data": {"reference_no": "MCM00000000000000017", "sender_information": {"name": "Test User 3", "email": "testuser3@gmail.com", "mobile": "01600000009", "profile": {"id_doc": {"id_no": "12345678", "id_type": "passport", "id_issue_at": "2024-05-12T00:00:00.000000Z", "id_expired_at": "2029-05-12T00:00:00.000000Z", "id_no_duplicate": "no", "id_issue_country": "Bangladesh"}, "blacklisted": "no", "profile_data": {"note": "Testing", "gender": "male", "occupation": "service", "father_name": "Mustak Ahmed", "mother_name": "Hamida Begum", "nationality": "Bangladeshi", "marital_status": "unmarried", "source_of_income": "salary"}, "date_of_birth": "1999-05-12T00:00:00.000000Z", "present_address": {"address": "Mohammadpur", "city_id": 16152, "state_id": 866, "city_data": null, "city_name": "Ajax", "post_code": "1234", "country_id": 39, "state_data": [], "state_name": "Ontario", "country_data": {"is_serving": true, "language_enabled": true, "multi_currency_enabled": true}, "country_name": "Canada"}}, "currency": "CAD", "language": "en", "fcm_token": "SLFtv0PgwYEqYxO5Y6NIYOVA8eVVqDHy1H6IzCOj"}, "wallet_information": {"bank_data": {"swift_code": "BSONBDDH"}, "bank_name": "SONALI BANK LIMITED"}, "receiver_information": {"city_id": 8527, "state_id": 771, "city_data": null, "city_name": "Madaripur", "country_id": 19, "state_data": [], "state_name": "Dhaka District", "relation_id": 58, "country_data": {"is_serving": true, "language_enabled": true, "multi_currency_enabled": true}, "country_name": "Bangladesh", "relation_data": null, "relation_name": "Niece", "beneficiary_data": {"email": "bd.wallet.beneficiary.1@gmail.com", "bank_name": null, "cash_name": null, "wallet_id": 25, "wallet_name": "SONALI BANK LIMITED", "account_name": "Bkash", "bank_branch_name": null, "instant_bank_name": null, "wallet_account_number": "147852369852", "instant_bank_branch_name": null}, "beneficiary_name": "bd wallet beneficiary 1", "beneficiary_mobile": "+8801478523000", "beneficiary_address": null, "beneficiary_type_id": 5, "beneficiary_type_name": "Wallet Transfer"}}, "master_user_name": "Afghanistan Master User", "service_stat_data": {"charge": "6%", "discount": "6%", "commission": "4%", "charge_refund": "yes", "discount_refund": "yes", "service_stat_id": 8801, "commission_refund": "yes", "charge_break_down_id": null}, "beneficiary_type_id": 5, "currency_convert_rate": {"rate": 102.14, "input": "BDT", "amount": "5617.70", "output": "CAD", "converted": 55, "amount_formatted": "CA$5,617.70", "converted_formatted": "BDT 55.00"}, "wallet_account_number": "147852369852", "created_by_mobile_number": "01600000009", "wallet_account_actual_name": "Bkash", "system_notification_variable_failed": "wallet_transfer_failed", "system_notification_variable_success": "wallet_transfer_success"}',
            'status' => 'successful',
            'creator_id' => null,
            'editor_id' => null,
            'destroyer_id' => null,
            'restorer_id' => null,
            'created_at' => '2024-05-14 17:13:37',
            'updated_at' => '2024-05-14 17:13:38',
            'deleted_at' => null,
            'restored_at' => null,
        ];
    }

    public function islamiBankFetchRemittanceCard(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $data['account_number'] = '34';
        $data['account_type'] = '71';
        $data['branch_code'] = '123';
        dump($vendor->fetchAccountDetail($data));
    }

    public function islamiBankFetchMobileBankingMCash(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $data['account_number'] = '01670697200';
        $data['account_type'] = '';
        $data['branch_code'] = '358';
        dump($vendor->fetchAccountDetail($data));
    }

    public function islamiBankSpotCash(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        //$order = $repo->find(20);
        //dd($order);
        //$order_data = $order->order_data;
        //dd($order_data);
        $order = $this->__dummyCashPickUpOrderData();
        $order_data = json_decode($order['order_data'], true);
        $order_data['beneficiary_data']['reference_no'] = 'TEST'.time();
        $order_data['sending_amount'] = $order['amount'];
        $order_data['sending_currency'] = $order['currency'];
        //dd($order_data);
        dump($vendor->directCreditRemittance($order_data));
    }

    private function __dummyCashPickUpOrderData(): array
    {
        return [
            'id' => 20,
            'source_country_id' => 39,
            'destination_country_id' => 19,
            'parent_id' => null,
            'sender_receiver_id' => 10,
            'user_id' => 8,
            'service_id' => 14,
            'transaction_form_id' => 6,
            'ordered_at' => '2024-05-14 17:18:49',
            'amount' => '2814.130000',
            'currency' => 'BDT',
            'converted_amount' => '31.679950',
            'converted_currency' => 'CAD',
            'order_number' => 'CAN00000000000000020',
            'risk_profile' => 'green',
            'notes' => null,
            'is_refunded' => 0,
            'order_data' => '{"cash_id": 61, "user_name": "Test User 3", "created_at": "2024-05-14T17:18:49.861607Z", "created_by": "Test User 3", "fund_source": "Share Money With Relative", "assign_order": "no", "request_from": "web", "service_name": "Cash Pickup", "service_slug": "cash_pickup", "beneficiary_id": 4, "current_amount": 50030.828934, "previous_amount": 50030.828934, "purchase_number": "CAN00000000000000020", "beneficiary_data": {"reference_no": "MCM00000000000000020", "cash_information": {"bank_data": {"swift_code": "BBHOBDDH"}, "bank_name": "BANGLADESH BANK", "bank_slug": "bangladesh-bank", "vendor_code": {"remit": {"agrani": null, "emqapi": null, "valyou": null, "citybank": null, "transfast": "BA01", "islamibank": "10"}}}, "sender_information": {"name": "Test User 3", "email": "testuser3@gmail.com", "mobile": "01600000009", "profile": {"id_doc": {"id_no": "12345678", "id_type": "passport", "id_issue_at": "2024-05-12T00:00:00.000000Z", "id_expired_at": "2029-05-12T00:00:00.000000Z", "id_no_duplicate": "no", "id_issue_country": "Bangladesh"}, "blacklisted": "no", "profile_data": {"note": "Testing", "gender": "male", "occupation": "service", "father_name": "Mustak Ahmed", "mother_name": "Hamida Begum", "nationality": "Bangladeshi", "marital_status": "unmarried", "source_of_income": "salary"}, "date_of_birth": "1999-05-12T00:00:00.000000Z", "present_address": {"address": "Mohammadpur", "city_id": 16152, "state_id": 866, "city_data": null, "city_name": "Ajax", "post_code": "1234", "country_id": 39, "state_data": [], "state_name": "Ontario", "country_data": {"is_serving": true, "language_enabled": true, "multi_currency_enabled": true}, "country_name": "Canada"}}, "currency": "CAD", "language": "en", "fcm_token": "SLFtv0PgwYEqYxO5Y6NIYOVA8eVVqDHy1H6IzCOj"}, "receiver_information": {"city_id": 8547, "state_id": 771, "city_data": null, "city_name": "Narayanganj", "country_id": 19, "state_data": [], "state_name": "Dhaka District", "relation_id": 51, "country_data": {"is_serving": true, "language_enabled": true, "multi_currency_enabled": true}, "country_name": "Bangladesh", "relation_data": null, "relation_name": "Friend", "beneficiary_data": {"email": "bd.cash.recipient.1@gmail.com", "cash_id": 61, "bank_name": null, "cash_name": "BANGLADESH BANK", "wallet_name": null, "bank_branch_name": null, "instant_bank_name": null, "cash_account_number": "bd cash recipient 1 account", "instant_bank_branch_name": null}, "beneficiary_name": "bd cash recipient 1", "beneficiary_mobile": "+8801856660645", "beneficiary_address": null, "beneficiary_type_id": 3, "beneficiary_type_name": "Cash Pickup"}}, "master_user_name": "Afghanistan Master User", "service_stat_data": {"charge": "5%", "discount": "1%", "commission": "7%", "charge_refund": "yes", "discount_refund": "yes", "service_stat_id": 6641, "commission_refund": "yes", "charge_break_down_id": null}, "remittance_purpose": "Religious Festival", "beneficiary_type_id": 3, "cash_account_number": "bd cash recipient 1 account", "currency_convert_rate": {"rate": 88.83, "input": "BDT", "amount": "2814.13", "output": "CAD", "converted": 31.67995046718451, "amount_formatted": "CA$2,814.13", "converted_formatted": "BDT 31.68"}, "created_by_mobile_number": "01600000009", "system_notification_variable_failed": "cash_pickup_failed", "system_notification_variable_success": "cash_pickup_success"}',
            'status' => 'successful',
            'creator_id' => null,
            'editor_id' => null,
            'destroyer_id' => null,
            'restorer_id' => null,
            'created_at' => '2024-05-14 17:18:49',
            'updated_at' => '2024-05-14 17:18:49',
            'deleted_at' => null,
            'restored_at' => null,
        ];
    }

    public function islamiBankAccountPayee(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        //$order = $repo->find(18);
        //dd($order);
        //$order_data = $order->order_data;
        //dd($order_data);
        $order = $this->__dummyBankTransferOwnBankOrderData();
        $order_data = json_decode($order['order_data'], true);
        //dd($order_data);
        $order_data['beneficiary_data']['reference_no'] = 'TEST'.time();
        $order_data['sending_amount'] = $order['amount'];
        $order_data['sending_currency'] = $order['currency'];
        //dd($order_data);
        dump($vendor->directCreditRemittance($order_data));
    }

    private function __dummyBankTransferOwnBankOrderData(): array
    {
        return [
            'id' => 18,
            'source_country_id' => 39,
            'destination_country_id' => 19,
            'parent_id' => null,
            'sender_receiver_id' => 10,
            'user_id' => 8,
            'service_id' => 13,
            'transaction_form_id' => 6,
            'ordered_at' => '2024-05-14 17:15:33',
            'amount' => '7377.600000',
            'currency' => 'BDT',
            'converted_amount' => '67.320011',
            'converted_currency' => 'CAD',
            'order_number' => 'CAN00000000000000018',
            'risk_profile' => 'green',
            'notes' => null,
            'is_refunded' => 0,
            'order_data' => '{"bank_id": 45, "user_name": "Test User 3", "created_at": "2024-05-14T17:15:34.066216Z", "created_by": "Test User 3", "fund_source": "Salary Saving  Loan From Employer", "assign_order": "no", "request_from": "web", "service_name": "Bank Transfer", "service_slug": "bank_transfer", "account_number": "123456789987", "bank_branch_id": 3758, "beneficiary_id": 2, "current_amount": 50122.38415, "previous_amount": 50122.38415, "purchase_number": "CAN00000000000000018", "beneficiary_data": {"reference_no": "MCM00000000000000018", "bank_information": {"bank_data": {"swift_code": "IBBLBDDH"}, "bank_name": "ISLAMI BANK BANGLADESH LIMITED", "bank_slug": "islami-bank-bangladesh-limited", "vendor_code": {"remit": {"agrani": null, "emqapi": null, "valyou": null, "citybank": null, "transfast": "BA26", "islamibank": "42"}}}, "branch_information": {"branch_data": {"location_no": "125030139"}, "branch_name": "BANDARBAN BRANCH", "vendor_code": {"remit": {"agrani": null, "emqapi": null, "valyou": null, "citybank": null, "transfast": null, "islamibank": null}}}, "sender_information": {"name": "Test User 3", "email": "testuser3@gmail.com", "mobile": "01600000009", "profile": {"id_doc": {"id_no": "12345678", "id_type": "passport", "id_issue_at": "2024-05-12T00:00:00.000000Z", "id_expired_at": "2029-05-12T00:00:00.000000Z", "id_no_duplicate": "no", "id_issue_country": "Bangladesh"}, "blacklisted": "no", "profile_data": {"note": "Testing", "gender": "male", "occupation": "service", "father_name": "Mustak Ahmed", "mother_name": "Hamida Begum", "nationality": "Bangladeshi", "marital_status": "unmarried", "source_of_income": "salary"}, "date_of_birth": "1999-05-12T00:00:00.000000Z", "present_address": {"address": "Mohammadpur", "city_id": 16152, "state_id": 866, "city_data": null, "city_name": "Ajax", "post_code": "1234", "country_id": 39, "state_data": [], "state_name": "Ontario", "country_data": {"is_serving": true, "language_enabled": true, "multi_currency_enabled": true}, "country_name": "Canada"}}, "currency": "CAD", "language": "en", "fcm_token": "SLFtv0PgwYEqYxO5Y6NIYOVA8eVVqDHy1H6IzCOj"}, "receiver_information": {"city_id": 8559, "state_id": 771, "city_data": null, "city_name": "Paltan", "country_id": 19, "state_data": [], "state_name": "Dhaka District", "relation_id": 59, "country_data": {"is_serving": true, "language_enabled": true, "multi_currency_enabled": true}, "country_name": "Bangladesh", "relation_data": null, "relation_name": "Non Related", "beneficiary_data": {"email": "bd.bank.recipient.1@gmail.com", "bank_id": 45, "bank_name": "ISLAMI BANK BANGLADESH LIMITED", "cash_name": null, "wallet_name": null, "account_name": "bd bank recipient 1 account", "bank_branch_id": 3758, "bank_branch_name": "BANDARBAN BRANCH", "instant_bank_name": null, "bank_account_number": "123456789987", "instant_bank_branch_name": null}, "beneficiary_name": "bd bank recipient 1", "beneficiary_mobile": "+8801234567899", "beneficiary_address": null, "beneficiary_type_id": 1, "beneficiary_type_name": "Bank Transfer"}}, "master_user_name": "Afghanistan Master User", "service_stat_data": {"charge": "4%", "discount": "6%", "commission": "3%", "charge_refund": "yes", "discount_refund": "yes", "service_stat_id": 6209, "commission_refund": "yes", "charge_break_down_id": null}, "remittance_purpose": "Repatriation Of Business Profits", "beneficiary_type_id": 1, "currency_convert_rate": {"rate": 109.59, "input": "BDT", "amount": "7377.60", "output": "CAD", "converted": 67.32001094990419, "amount_formatted": "CA$7,377.60", "converted_formatted": "BDT 67.32"}, "created_by_mobile_number": "01600000009", "system_notification_variable_failed": "bank_transfer_failed", "system_notification_variable_success": "bank_transfer_success"}',
            'status' => 'successful',
            'creator_id' => null,
            'editor_id' => null,
            'destroyer_id' => null,
            'restorer_id' => null,
            'created_at' => '2024-05-14 17:15:34',
            'updated_at' => '2024-05-14 17:15:34',
            'deleted_at' => null,
            'restored_at' => null,
        ];
    }

    private function __dummyBankTransferThirdBankOrderData(): array
    {
        return [
            'id' => 19,
            'source_country_id' => 39,
            'destination_country_id' => 19,
            'parent_id' => null,
            'sender_receiver_id' => 10,
            'user_id' => 8,
            'service_id' => 13,
            'transaction_form_id' => 6,
            'ordered_at' => '2024-05-14 17:17:29',
            'amount' => '2459.200000',
            'currency' => 'BDT',
            'converted_amount' => '22.440004',
            'converted_currency' => 'CAD',
            'order_number' => 'CAN00000000000000019',
            'risk_profile' => 'green',
            'notes' => null,
            'is_refunded' => 0,
            'order_data' => '{"bank_id": 9, "user_name": "Test User 3", "created_at": "2024-05-14T17:17:30.365099Z", "created_by": "Test User 3", "fund_source": "Loan From Employer", "assign_order": "no", "request_from": "web", "service_name": "Bank Transfer", "service_slug": "bank_transfer", "account_number": "147852369996", "bank_branch_id": 2811, "beneficiary_id": 3, "current_amount": 50053.717738, "previous_amount": 50053.717738, "purchase_number": "CAN00000000000000019", "beneficiary_data": {"reference_no": "MCM00000000000000019", "bank_information": {"bank_data": {"swift_code": "CIBLBDDH"}, "bank_name": "CITY BANK LIMITED", "bank_slug": "city-bank-limited", "vendor_code": {"remit": {"agrani": null, "emqapi": null, "valyou": null, "citybank": null, "transfast": "BA44", "islamibank": "44"}}}, "branch_information": {"branch_data": {"location_no": "225261187"}, "branch_name": "DHANMONDI BRANCH", "vendor_code": {"remit": {"agrani": null, "emqapi": null, "valyou": null, "citybank": null, "transfast": null, "islamibank": null}}}, "sender_information": {"name": "Test User 3", "email": "testuser3@gmail.com", "mobile": "01600000009", "profile": {"id_doc": {"id_no": "12345678", "id_type": "passport", "id_issue_at": "2024-05-12T00:00:00.000000Z", "id_expired_at": "2029-05-12T00:00:00.000000Z", "id_no_duplicate": "no", "id_issue_country": "Bangladesh"}, "blacklisted": "no", "profile_data": {"note": "Testing", "gender": "male", "occupation": "service", "father_name": "Mustak Ahmed", "mother_name": "Hamida Begum", "nationality": "Bangladeshi", "marital_status": "unmarried", "source_of_income": "salary"}, "date_of_birth": "1999-05-12T00:00:00.000000Z", "present_address": {"address": "Mohammadpur", "city_id": 16152, "state_id": 866, "city_data": null, "city_name": "Ajax", "post_code": "1234", "country_id": 39, "state_data": [], "state_name": "Ontario", "country_data": {"is_serving": true, "language_enabled": true, "multi_currency_enabled": true}, "country_name": "Canada"}}, "currency": "CAD", "language": "en", "fcm_token": "SLFtv0PgwYEqYxO5Y6NIYOVA8eVVqDHy1H6IzCOj"}, "receiver_information": {"city_id": 8595, "state_id": 767, "city_data": null, "city_name": "Sunamganj", "country_id": 19, "state_data": [], "state_name": "Sylhet District", "relation_id": 60, "country_data": {"is_serving": true, "language_enabled": true, "multi_currency_enabled": true}, "country_name": "Bangladesh", "relation_data": null, "relation_name": "Relative", "beneficiary_data": {"email": "bd.bank.recipient.2@gmail.com", "bank_id": 9, "bank_name": "CITY BANK LIMITED", "cash_name": null, "wallet_name": null, "account_name": "bd bank recipient 2 account", "bank_branch_id": 2811, "bank_branch_name": "DHANMONDI BRANCH", "instant_bank_name": null, "bank_account_number": "147852369996", "instant_bank_branch_name": null}, "beneficiary_name": "bd bank recipient 2", "beneficiary_mobile": "+8801869662323", "beneficiary_address": null, "beneficiary_type_id": 1, "beneficiary_type_name": "Bank Transfer"}}, "master_user_name": "Afghanistan Master User", "service_stat_data": {"charge": "4%", "discount": "6%", "commission": "3%", "charge_refund": "yes", "discount_refund": "yes", "service_stat_id": 6209, "commission_refund": "yes", "charge_break_down_id": null}, "remittance_purpose": "Services Trade", "beneficiary_type_id": 1, "currency_convert_rate": {"rate": 109.59, "input": "BDT", "amount": "2459.20", "output": "CAD", "converted": 22.44000364996806, "amount_formatted": "CA$2,459.20", "converted_formatted": "BDT 22.44"}, "created_by_mobile_number": "01600000009", "system_notification_variable_failed": "bank_transfer_failed", "system_notification_variable_success": "bank_transfer_success"}',
            'status' => 'successful',
            'creator_id' => null,
            'editor_id' => null,
            'destroyer_id' => null,
            'restorer_id' => null,
            'created_at' => '2024-05-14 17:17:30',
            'updated_at' => '2024-05-14 17:17:30',
            'deleted_at' => null,
            'restored_at' => null,
        ];
    }

    public function islamiBankThirdBank(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        //$order = $repo->find(19);
        //$order_data = $order->order_data;
        //dd($order);
        $order = $this->__dummyBankTransferThirdBankOrderData();
        $order_data = json_decode($order['order_data'], true);
        $order_data['beneficiary_data']['reference_no'] = 'TEST'.time();
        $order_data['sending_amount'] = $order['amount'];
        $order_data['sending_currency'] = $order['currency'];
        //dd($order_data);
        dump($vendor->directCreditRemittance($order_data));
    }

    public function islamiBankMobileBankingMCash(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        $order = $repo->find(17);
        //dd($order);
        //$order_data = $order->order_data;
        $order = $this->__dummyWalletTransferOrderData();
        $order_data = json_decode($order['order_data'], true);
        $order_data['beneficiary_data']['reference_no'] = 'TEST'.time();
        $order_data['sending_amount'] = $order['amount'];
        $order_data['sending_currency'] = $order['currency'];
        //dd($order_data);
        dump($vendor->directCreditRemittance($order_data));
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
}
