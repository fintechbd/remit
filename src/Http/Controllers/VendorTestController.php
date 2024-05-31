<?php

namespace Fintech\Remit\Http\Controllers;

use Fintech\Remit\Vendors\IslamiBankApi;
use Fintech\Transaction\Services\OrderService;
use Illuminate\Routing\Controller;

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

    private function __dummyCashPickUpOrderData(): array
    {
        return [
            'id' => 26,
            'source_country_id' => 39,
            'destination_country_id' => 19,
            'parent_id' => null,
            'sender_receiver_id' => 10,
            'user_id' => 6,
            'service_id' => 14,
            'transaction_form_id' => 6,
            'ordered_at' => '2024-05-17 21:07:43',
            'amount' => '852.770000',
            'currency' => 'BDT',
            'converted_amount' => '9.600023',
            'converted_currency' => 'CAD',
            'order_number' => 'CAN00000000000000026',
            'risk_profile' => 'green',
            'notes' => null,
            'is_refunded' => 0,
            'order_data' => '{"cash_id": 45, "user_name": "Test User 1", "created_at": "2024-05-17T21:07:44.141609Z", "created_by": "Test User 1", "fund_source": "Salary", "assign_order": "no", "request_from": "web", "service_name": "Cash Pickup", "service_slug": "cash_pickup", "beneficiary_id": 13, "current_amount": 20927.474876, "previous_amount": 20927.474876, "purchase_number": "CAN00000000000000026", "beneficiary_data": {"reference_no": "MCM00000000000000026", "cash_information": {"bank_data": {"swift_code": "IBBLBDDH"}, "bank_name": "ISLAMI BANK BANGLADESH LIMITED", "bank_slug": "islami-bank-bangladesh-limited", "vendor_code": {"remit": {"agrani": null, "emqapi": null, "valyou": null, "citybank": null, "transfast": "BA26", "islamibank": "42"}}}, "sender_information": {"name": "Test User 1", "email": "testuser1@gmail.com", "mobile": "01600000007", "profile": {"id_doc": {"id_no": "12345678", "id_type": "passport", "id_issue_at": "2024-05-12T00:00:00.000000Z", "id_expired_at": "2029-05-12T00:00:00.000000Z", "id_no_duplicate": "no", "id_issue_country": "Bangladesh"}, "blacklisted": "no", "profile_data": {"note": "Testing", "gender": "male", "occupation": "service", "father_name": "Mustak Ahmed", "mother_name": "Hamida Begum", "nationality": "Bangladeshi", "marital_status": "unmarried", "source_of_income": "salary"}, "date_of_birth": "1999-05-12T00:00:00.000000Z", "present_address": {"address": "Mohammadpur", "city_id": 16152, "state_id": 866, "city_data": null, "city_name": "Ajax", "post_code": "1234", "country_id": 39, "state_data": [], "state_name": "Ontario", "country_data": {"is_serving": true, "language_enabled": true, "multi_currency_enabled": true}, "country_name": "Canada"}}, "currency": "CAD", "language": "en", "fcm_token": "e1xcoyZ4AJ8jdkb37mG6RgFffqSTd6fRtHAYZlHI"}, "receiver_information": {"city_id": 8486, "state_id": 771, "city_data": null, "city_name": "Dhaka", "country_id": 19, "state_data": [], "state_name": "Dhaka District", "relation_id": 79, "country_data": {"is_serving": true, "language_enabled": true, "multi_currency_enabled": true}, "country_name": "Bangladesh", "relation_data": null, "relation_name": "Others", "beneficiary_data": {"email": "mah.shamim@gmail.com", "cash_id": 45, "bank_name": null, "cash_name": "ISLAMI BANK BANGLADESH LIMITED", "wallet_name": null, "bank_branch_name": null, "instant_bank_name": null, "cash_account_number": "1234567890", "instant_bank_branch_name": null}, "beneficiary_name": "MD ARIFUL HAQUE CASH PICKUP", "beneficiary_mobile": "+8801614747054", "beneficiary_address": null, "beneficiary_type_id": 3, "beneficiary_type_name": "Cash Pickup"}}, "master_user_name": "Afghanistan Master User", "service_stat_data": {"charge": "5%", "discount": "1%", "commission": "7%", "charge_refund": "yes", "discount_refund": "yes", "service_stat_id": 6641, "commission_refund": "yes", "charge_break_down_id": null}, "remittance_purpose": "Family Maintenance or Savings", "beneficiary_type_id": 3, "cash_account_number": "1234567890", "currency_convert_rate": {"rate": 88.83, "input": "BDT", "amount": "852.77", "output": "CAD", "converted": 9.600022514916132, "amount_formatted": "CA$852.77", "converted_formatted": "BDT 9.60"}, "created_by_mobile_number": "01600000007", "system_notification_variable_failed": "cash_pickup_failed", "system_notification_variable_success": "cash_pickup_success"}',
            'status' => 'successful',
            'creator_id' => null,
            'editor_id' => null,
            'destroyer_id' => null,
            'restorer_id' => null,
            'created_at' => '2024-05-17 21:07:44',
            'updated_at' => '2024-05-17 21:07:44',
            'deleted_at' => null,
            'restored_at' => null,
        ];
    }

    public function islamiBankSpotCash(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        $order = $repo->list(['service_id' => 14, 'sort' => 'orders.id', 'dir' => 'desc'])->first();
        //dd($order);
        $order_data = $order->order_data;
        //dd($order_data);
        //$order = $this->__dummyCashPickUpOrderData();
        //$order_data = json_decode($order['order_data'], true);
        $order_data['beneficiary_data']['reference_no'] = 'TEST'.time();
        $order_data['sending_amount'] = $order['amount'];
        $order_data['sending_currency'] = $order['currency'];
        //dd($order_data);
        dump($vendor->directCreditRemittance($order_data));
    }

    private function __dummyBankTransferOwnBankOrderData(): array
    {
        return [
            'id' => 27,
            'source_country_id' => 39,
            'destination_country_id' => 19,
            'parent_id' => null,
            'sender_receiver_id' => 10,
            'user_id' => 6,
            'service_id' => 13,
            'transaction_form_id' => 6,
            'ordered_at' => '2024-05-17 21:08:20',
            'amount' => '1117.820000',
            'currency' => 'BDT',
            'converted_amount' => '10.200018',
            'converted_currency' => 'CAD',
            'order_number' => 'CAN00000000000000027',
            'risk_profile' => 'green',
            'notes' => null,
            'is_refunded' => 0,
            'order_data' => '{"bank_id": 45, "user_name": "Test User 1", "created_at": "2024-05-17T21:08:20.678561Z", "created_by": "Test User 1", "fund_source": "Salary", "assign_order": "no", "request_from": "web", "service_name": "Bank Transfer", "service_slug": "bank_transfer", "account_number": "1234567890", "bank_branch_id": 3879, "beneficiary_id": 14, "current_amount": 20918.258854, "previous_amount": 20918.258854, "purchase_number": "CAN00000000000000027", "beneficiary_data": {"reference_no": "MCM00000000000000027", "bank_information": {"bank_data": {"swift_code": "IBBLBDDH"}, "bank_name": "ISLAMI BANK BANGLADESH LIMITED", "bank_slug": "islami-bank-bangladesh-limited", "vendor_code": {"remit": {"agrani": null, "emqapi": null, "valyou": null, "citybank": null, "transfast": "BA26", "islamibank": "42"}}}, "branch_information": {"branch_data": {"location_no": "125271848"}, "branch_name": "DHOLAIKHAL BRANCH", "vendor_code": {"remit": {"agrani": null, "emqapi": null, "valyou": null, "citybank": null, "transfast": null, "islamibank": null}}}, "sender_information": {"name": "Test User 1", "email": "testuser1@gmail.com", "mobile": "01600000007", "profile": {"id_doc": {"id_no": "12345678", "id_type": "passport", "id_issue_at": "2024-05-12T00:00:00.000000Z", "id_expired_at": "2029-05-12T00:00:00.000000Z", "id_no_duplicate": "no", "id_issue_country": "Bangladesh"}, "blacklisted": "no", "profile_data": {"note": "Testing", "gender": "male", "occupation": "service", "father_name": "Mustak Ahmed", "mother_name": "Hamida Begum", "nationality": "Bangladeshi", "marital_status": "unmarried", "source_of_income": "salary"}, "date_of_birth": "1999-05-12T00:00:00.000000Z", "present_address": {"address": "Mohammadpur", "city_id": 16152, "state_id": 866, "city_data": null, "city_name": "Ajax", "post_code": "1234", "country_id": 39, "state_data": [], "state_name": "Ontario", "country_data": {"is_serving": true, "language_enabled": true, "multi_currency_enabled": true}, "country_name": "Canada"}}, "currency": "CAD", "language": "en", "fcm_token": "e1xcoyZ4AJ8jdkb37mG6RgFffqSTd6fRtHAYZlHI"}, "receiver_information": {"city_id": 8486, "state_id": 771, "city_data": null, "city_name": "Dhaka", "country_id": 19, "state_data": [], "state_name": "Dhaka District", "relation_id": 79, "country_data": {"is_serving": true, "language_enabled": true, "multi_currency_enabled": true}, "country_name": "Bangladesh", "relation_data": null, "relation_name": "Others", "beneficiary_data": {"email": "mah.shamim@gmail.com", "bank_id": 45, "bank_name": "ISLAMI BANK BANGLADESH LIMITED", "cash_name": null, "wallet_name": null, "account_name": "MD ARIFUL HAQUE", "bank_branch_id": 3879, "bank_branch_name": "DHOLAIKHAL BRANCH", "instant_bank_name": null, "bank_account_number": "1234567890", "instant_bank_branch_name": null}, "beneficiary_name": "MD ARIFUL HAQUE ISLAMI BANK", "beneficiary_mobile": "+8801760233030", "beneficiary_address": null, "beneficiary_type_id": 1, "beneficiary_type_name": "Bank Transfer"}}, "master_user_name": "Afghanistan Master User", "service_stat_data": {"charge": "4%", "discount": "6%", "commission": "3%", "charge_refund": "yes", "discount_refund": "yes", "service_stat_id": 6209, "commission_refund": "yes", "charge_break_down_id": null}, "remittance_purpose": "Family Maintenance or Savings", "beneficiary_type_id": 1, "currency_convert_rate": {"rate": 109.59, "input": "BDT", "amount": "1117.82", "output": "CAD", "converted": 10.200018249840314, "amount_formatted": "CA$1,117.82", "converted_formatted": "BDT 10.20"}, "created_by_mobile_number": "01600000007", "system_notification_variable_failed": "bank_transfer_failed", "system_notification_variable_success": "bank_transfer_success"}',
            'status' => 'successful',
            'creator_id' => null,
            'editor_id' => null,
            'destroyer_id' => null,
            'restorer_id' => null,
            'created_at' => '2024-05-17 21:08:20',
            'updated_at' => '2024-05-17 21:08:20',
            'deleted_at' => null,
            'restored_at' => null,
        ];
    }

    public function islamiBankAccountPayee(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        $order = $repo->list(['service_id' => 13, 'sort' => 'orders.id', 'dir' => 'desc'])->first();
        //dd($order);
        $order_data = $order->order_data;
        //dd($order_data);
        //$order = $this->__dummyBankTransferOwnBankOrderData();
        //$order_data = json_decode($order['order_data'], true);
        //dd($order_data);
        $order_data['beneficiary_data']['reference_no'] = 'TEST'.time();
        $order_data['sending_amount'] = $order['amount'];
        $order_data['sending_currency'] = $order['currency'];
        //dd($order_data);
        dump($vendor->directCreditRemittance($order_data));
    }

    private function __dummyBankTransferThirdBankOrderData(): array
    {
        return [
            'id' => 28,
            'source_country_id' => 39,
            'destination_country_id' => 19,
            'parent_id' => null,
            'sender_receiver_id' => 10,
            'user_id' => 6,
            'service_id' => 13,
            'transaction_form_id' => 6,
            'ordered_at' => '2024-05-17 21:26:35',
            'amount' => '1117.820000',
            'currency' => 'BDT',
            'converted_amount' => '10.200018',
            'converted_currency' => 'CAD',
            'order_number' => 'CAN00000000000000028',
            'risk_profile' => 'green',
            'notes' => null,
            'is_refunded' => 0,
            'order_data' => '{"bank_id": 14, "user_name": "Test User 1", "created_at": "2024-05-17T21:26:36.227973Z", "created_by": "Test User 1", "fund_source": "Salary", "assign_order": "no", "request_from": "web", "service_name": "Bank Transfer", "service_slug": "bank_transfer", "account_number": "1234567890", "bank_branch_id": 3221, "beneficiary_id": 12, "current_amount": 20907.854836, "previous_amount": 20907.854836, "purchase_number": "CAN00000000000000028", "beneficiary_data": {"reference_no": "MCM00000000000000028", "bank_information": {"bank_data": {"swift_code": "EBLDBDDH"}, "bank_name": "EASTERN BANK LIMITED", "bank_slug": "eastern-bank-limited", "vendor_code": {"remit": {"agrani": null, "emqapi": null, "valyou": null, "citybank": null, "transfast": "BA22", "islamibank": "52"}}}, "branch_information": {"branch_data": {"location_no": "95262987"}, "branch_name": "MIRPUR BRANCH", "vendor_code": {"remit": {"agrani": null, "emqapi": null, "valyou": null, "citybank": null, "transfast": null, "islamibank": null}}}, "sender_information": {"name": "Test User 1", "email": "testuser1@gmail.com", "mobile": "01600000007", "profile": {"id_doc": {"id_no": "12345678", "id_type": "passport", "id_issue_at": "2024-05-12T00:00:00.000000Z", "id_expired_at": "2029-05-12T00:00:00.000000Z", "id_no_duplicate": "no", "id_issue_country": "Bangladesh"}, "blacklisted": "no", "profile_data": {"note": "Testing", "gender": "male", "occupation": "service", "father_name": "Mustak Ahmed", "mother_name": "Hamida Begum", "nationality": "Bangladeshi", "marital_status": "unmarried", "source_of_income": "salary"}, "date_of_birth": "1999-05-12T00:00:00.000000Z", "present_address": {"address": "Mohammadpur", "city_id": 16152, "state_id": 866, "city_data": null, "city_name": "Ajax", "post_code": "1234", "country_id": 39, "state_data": [], "state_name": "Ontario", "country_data": {"is_serving": true, "language_enabled": true, "multi_currency_enabled": true}, "country_name": "Canada"}}, "currency": "CAD", "language": "en", "fcm_token": "e1xcoyZ4AJ8jdkb37mG6RgFffqSTd6fRtHAYZlHI"}, "receiver_information": {"city_id": 8486, "state_id": 771, "city_data": null, "city_name": "Dhaka", "country_id": 19, "state_data": [], "state_name": "Dhaka District", "relation_id": 79, "country_data": {"is_serving": true, "language_enabled": true, "multi_currency_enabled": true}, "country_name": "Bangladesh", "relation_data": null, "relation_name": "Others", "beneficiary_data": {"email": "mah.shamim@gmail.com", "bank_id": 14, "bank_name": "EASTERN BANK LIMITED", "cash_name": null, "wallet_name": null, "account_name": "MD ARIFUL HAQUE", "bank_branch_id": 3221, "bank_branch_name": "MIRPUR BRANCH", "instant_bank_name": null, "bank_account_number": "1234567890", "instant_bank_branch_name": null}, "beneficiary_name": "MD ARIFUL HAQUE THIRD BANK", "beneficiary_mobile": "+8801614747054", "beneficiary_address": null, "beneficiary_type_id": 1, "beneficiary_type_name": "Bank Transfer"}}, "master_user_name": "Afghanistan Master User", "service_stat_data": {"charge": "4%", "discount": "6%", "commission": "3%", "charge_refund": "yes", "discount_refund": "yes", "service_stat_id": 6209, "commission_refund": "yes", "charge_break_down_id": null}, "remittance_purpose": "Family Maintenance or Savings", "beneficiary_type_id": 1, "currency_convert_rate": {"rate": 109.59, "input": "BDT", "amount": "1117.82", "output": "CAD", "converted": 10.200018249840314, "amount_formatted": "CA$1,117.82", "converted_formatted": "BDT 10.20"}, "created_by_mobile_number": "01600000007", "system_notification_variable_failed": "bank_transfer_failed", "system_notification_variable_success": "bank_transfer_success"}',
            'status' => 'successful',
            'creator_id' => null,
            'editor_id' => null,
            'destroyer_id' => null,
            'restorer_id' => null,
            'created_at' => '2024-05-17 21:26:36',
            'updated_at' => '2024-05-17 21:26:36',
            'deleted_at' => null,
            'restored_at' => null,
        ];
    }

    public function islamiBankThirdBank(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        $order = $repo->list(['service_id' => 13, 'sort' => 'orders.id', 'dir' => 'desc'])->first();
        //dd($order);
        $order_data = $order->order_data;
        //dd($order);
        //$order = $this->__dummyBankTransferThirdBankOrderData();
        //$order_data = json_decode($order['order_data'], true);
        $order_data['beneficiary_data']['reference_no'] = 'TEST'.time();
        $order_data['sending_amount'] = $order['amount'];
        $order_data['sending_currency'] = $order['currency'];
        //dd($order_data);
        dump($vendor->directCreditRemittance($order_data));
    }

    private function __dummyWalletTransferOrderDataMCash(): array
    {
        return [
            'id' => 25,
            'source_country_id' => 39,
            'destination_country_id' => 19,
            'parent_id' => null,
            'sender_receiver_id' => 10,
            'user_id' => 6,
            'service_id' => 19,
            'transaction_form_id' => 10,
            'ordered_at' => '2024-05-17 21:07:02',
            'amount' => '1021.400000',
            'currency' => 'BDT',
            'converted_amount' => '10.000000',
            'converted_currency' => 'CAD',
            'order_number' => 'CAN00000000000000025',
            'risk_profile' => 'green',
            'notes' => null,
            'is_refunded' => 0,
            'order_data' => '{"user_name": "Test User 1", "wallet_id": 636, "created_at": "2024-05-17T21:07:02.576093Z", "created_by": "Test User 1", "assign_order": "no", "request_from": "web", "service_name": "MCash", "service_slug": "mbs_m_cash", "beneficiary_id": 17, "current_amount": 20937.474876, "previous_amount": 20937.474876, "purchase_number": "CAN00000000000000025", "beneficiary_data": {"reference_no": "MCM00000000000000025", "sender_information": {"name": "Test User 1", "email": "testuser1@gmail.com", "mobile": "01600000007", "profile": {"id_doc": {"id_no": "12345678", "id_type": "passport", "id_issue_at": "2024-05-12T00:00:00.000000Z", "id_expired_at": "2029-05-12T00:00:00.000000Z", "id_no_duplicate": "no", "id_issue_country": "Bangladesh"}, "blacklisted": "no", "profile_data": {"note": "Testing", "gender": "male", "occupation": "service", "father_name": "Mustak Ahmed", "mother_name": "Hamida Begum", "nationality": "Bangladeshi", "marital_status": "unmarried", "source_of_income": "salary"}, "date_of_birth": "1999-05-12T00:00:00.000000Z", "present_address": {"address": "Mohammadpur", "city_id": 16152, "state_id": 866, "city_data": null, "city_name": "Ajax", "post_code": "1234", "country_id": 39, "state_data": [], "state_name": "Ontario", "country_data": {"is_serving": true, "language_enabled": true, "multi_currency_enabled": true}, "country_name": "Canada"}}, "currency": "CAD", "language": "en", "fcm_token": "e1xcoyZ4AJ8jdkb37mG6RgFffqSTd6fRtHAYZlHI"}, "wallet_information": {"bank_data": {"swift_code": null}, "bank_name": "mCash", "bank_slug": "m-cash", "vendor_code": {"remit": {"agrani": null, "emqapi": null, "valyou": null, "citybank": null, "transfast": null, "islamibank": "42"}}}, "receiver_information": {"city_id": 8486, "state_id": 771, "city_data": null, "city_name": "Dhaka", "country_id": 19, "state_data": [], "state_name": "Dhaka District", "relation_id": 79, "country_data": {"is_serving": true, "language_enabled": true, "multi_currency_enabled": true}, "country_name": "Bangladesh", "relation_data": null, "relation_name": "Others", "beneficiary_data": {"email": "mah.shamim@gmail.com", "bank_name": null, "cash_name": null, "wallet_id": 636, "wallet_name": "mCash", "account_name": "Bkash", "bank_branch_name": null, "instant_bank_name": null, "wallet_account_number": "01614747054", "instant_bank_branch_name": null}, "beneficiary_name": "MD ARIFUL HAQUE MCASH", "beneficiary_mobile": "+8801819432359", "beneficiary_address": null, "beneficiary_type_id": 5, "beneficiary_type_name": "Wallet Transfer"}}, "master_user_name": "Afghanistan Master User", "service_stat_data": {"charge": "6%", "discount": "6%", "commission": "4%", "charge_refund": "yes", "discount_refund": "yes", "service_stat_id": 8801, "commission_refund": "yes", "charge_break_down_id": null}, "beneficiary_type_id": 5, "currency_convert_rate": {"rate": 102.14, "input": "BDT", "amount": "1021.40", "output": "CAD", "converted": 10, "amount_formatted": "CA$1,021.40", "converted_formatted": "BDT 10.00"}, "wallet_account_number": "01614747054", "created_by_mobile_number": "01600000007", "wallet_account_actual_name": "Bkash", "system_notification_variable_failed": "wallet_transfer_failed", "system_notification_variable_success": "wallet_transfer_success"}',
            'status' => 'successful',
            'creator_id' => null,
            'editor_id' => null,
            'destroyer_id' => null,
            'restorer_id' => null,
            'created_at' => '2024-05-17 21:07:02',
            'updated_at' => '2024-05-17 21:07:02',
            'deleted_at' => null,
            'restored_at' => null,
        ];
    }

    public function islamiBankFetchMobileBankingMCash(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        $order = $repo->list(['service_id' => 19, 'sort' => 'orders.id', 'dir' => 'desc'])->first();
        //dd($order);
        $order_data = $order->order_data;
        //$order = $this->__dummyWalletTransferOrderDataMCash();
        //$order_data = json_decode($order['order_data'], true);
        //dd($order_data);
        $order_data['beneficiary_data']['receiver_information']['beneficiary_data']['wallet_account_number'] = '016147470541';
        //$data['account_number'] = '016147470541';
        //$data['account_type'] = $order_data;
        dump($vendor->validateBeneficiaryWallet($order_data));
    }

    public function islamiBankMobileBankingMCash(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        $order = $repo->list(['service_id' => 19, 'sort' => 'orders.id', 'dir' => 'desc'])->first();
        //dd($order);
        $order_data = $order->order_data;
        //$order = $this->__dummyWalletTransferOrderDataMCash();
        //$order_data = json_decode($order['order_data'], true);
        $order_data['beneficiary_data']['reference_no'] = 'TEST'.time();
        $order_data['sending_amount'] = $order['amount'];
        $order_data['sending_currency'] = $order['currency'];
        //dd($order_data);
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

    private function __dummyWalletBkashTransferOrderData(): array
    {
        return [
            'id' => 23,
            'source_country_id' => 39,
            'destination_country_id' => 19,
            'parent_id' => null,
            'sender_receiver_id' => 10,
            'user_id' => 6,
            'service_id' => 16,
            'transaction_form_id' => 10,
            'ordered_at' => '2024-05-17 21:05:14',
            'amount' => '1132.920000',
            'currency' => 'BDT',
            'converted_amount' => '10.100027',
            'converted_currency' => 'CAD',
            'order_number' => 'CAN00000000000000023',
            'risk_profile' => 'green',
            'notes' => null,
            'is_refunded' => 0,
            'order_data' => '{"user_name": "Test User 1", "wallet_id": 634, "created_at": "2024-05-17T21:05:14.609017Z", "created_by": "Test User 1", "assign_order": "no", "request_from": "web", "service_name": "bKash", "service_slug": "mfs_bkash", "beneficiary_id": 15, "current_amount": 20958.491844, "previous_amount": 20958.491844, "purchase_number": "CAN00000000000000023", "beneficiary_data": {"reference_no": "MCM00000000000000023", "sender_information": {"name": "Test User 1", "email": "testuser1@gmail.com", "mobile": "01600000007", "profile": {"id_doc": {"id_no": "12345678", "id_type": "passport", "id_issue_at": "2024-05-12T00:00:00.000000Z", "id_expired_at": "2029-05-12T00:00:00.000000Z", "id_no_duplicate": "no", "id_issue_country": "Bangladesh"}, "blacklisted": "no", "profile_data": {"note": "Testing", "gender": "male", "occupation": "service", "father_name": "Mustak Ahmed", "mother_name": "Hamida Begum", "nationality": "Bangladeshi", "marital_status": "unmarried", "source_of_income": "salary"}, "date_of_birth": "1999-05-12T00:00:00.000000Z", "present_address": {"address": "Mohammadpur", "city_id": 16152, "state_id": 866, "city_data": null, "city_name": "Ajax", "post_code": "1234", "country_id": 39, "state_data": [], "state_name": "Ontario", "country_data": {"is_serving": true, "language_enabled": true, "multi_currency_enabled": true}, "country_name": "Canada"}}, "currency": "CAD", "language": "en", "fcm_token": "e1xcoyZ4AJ8jdkb37mG6RgFffqSTd6fRtHAYZlHI"}, "wallet_information": {"bank_data": {"swift_code": null}, "bank_name": "BKASH", "bank_slug": "bkash", "vendor_code": {"remit": {"agrani": null, "emqapi": null, "valyou": null, "citybank": null, "transfast": null, "islamibank": "42"}}}, "receiver_information": {"city_id": 8486, "state_id": 771, "city_data": null, "city_name": "Dhaka", "country_id": 19, "state_data": [], "state_name": "Dhaka District", "relation_id": 79, "country_data": {"is_serving": true, "language_enabled": true, "multi_currency_enabled": true}, "country_name": "Bangladesh", "relation_data": null, "relation_name": "Others", "beneficiary_data": {"email": "mah.shamim@gmail.com", "bank_name": null, "cash_name": null, "wallet_id": 634, "wallet_name": "BKASH", "account_name": "Bkash", "bank_branch_name": null, "instant_bank_name": null, "wallet_account_number": "01614747054", "instant_bank_branch_name": null}, "beneficiary_name": "MD ARIFUL HAQUE", "beneficiary_mobile": "+8801614747054", "beneficiary_address": null, "beneficiary_type_id": 5, "beneficiary_type_name": "Wallet Transfer"}}, "master_user_name": "Afghanistan Master User", "service_stat_data": {"charge": "5%", "discount": "6%", "commission": "3%", "charge_refund": "yes", "discount_refund": "yes", "service_stat_id": 7505, "commission_refund": "yes", "charge_break_down_id": null}, "beneficiary_type_id": 5, "currency_convert_rate": {"rate": 112.17, "input": "BDT", "amount": "1132.92", "output": "CAD", "converted": 10.100026745119017, "amount_formatted": "CA$1,132.92", "converted_formatted": "BDT 10.10"}, "wallet_account_number": "01614747054", "created_by_mobile_number": "01600000007", "wallet_account_actual_name": "Bkash", "system_notification_variable_failed": "wallet_transfer_failed", "system_notification_variable_success": "wallet_transfer_success"}',
            'status' => 'successful',
            'creator_id' => null,
            'editor_id' => null,
            'destroyer_id' => null,
            'restorer_id' => null,
            'created_at' => '2024-05-17 21:05:14',
            'updated_at' => '2024-05-17 21:05:14',
            'deleted_at' => null,
            'restored_at' => null,
        ];
    }

    public function islamiBankValidateBeneficiaryWalletBkash(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        $order = $repo->list(['service_id' => 16, 'sort' => 'orders.id', 'dir' => 'desc'])->first();
        //dd($order);
        $order_data = $order->order_data;
        //$order = $this->__dummyWalletBkashTransferOrderData();
        //$order_data = json_decode($order['order_data'], true);
        $order_data['beneficiary_data']['receiver_information']['beneficiary_data']['wallet_account_number'] = '016147470541';
        //$data['account_number'] = '016147470541';
        //$data['account_type'] = $order_data;
        dump($vendor->fetchAccountDetail($order_data));
    }

    public function islamiBankWalletTransferBkash(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        $order = $repo->list(['service_id' => 16, 'sort' => 'orders.id', 'dir' => 'desc'])->first();
        //dd($order);
        $order_data = $order->order_data;
        //dd($order_data);
        //$order = $this->__dummyWalletBkashTransferOrderData();
        //$order_data = json_decode($order['order_data'], true);
        $order_data['beneficiary_data']['reference_no'] = 'TEST'.time();
        $order_data['sending_amount'] = $order['amount'];
        $order_data['sending_currency'] = $order['currency'];
        //dd($order_data);
        dump($vendor->directCreditRemittance($order_data));
    }

    private function __dummyWalletNagadTransferOrderData(): array
    {
        return [
            'id' => 24,
            'source_country_id' => 39,
            'destination_country_id' => 19,
            'parent_id' => null,
            'sender_receiver_id' => 10,
            'user_id' => 6,
            'service_id' => 17,
            'transaction_form_id' => 10,
            'ordered_at' => '2024-05-17 21:06:09',
            'amount' => '720.300000',
            'currency' => 'BDT',
            'converted_amount' => '10.399942',
            'converted_currency' => 'CAD',
            'order_number' => 'CAN00000000000000024',
            'risk_profile' => 'green',
            'notes' => null,
            'is_refunded' => 0,
            'order_data' => '{"user_name": "Test User 1", "wallet_id": 635, "created_at": "2024-05-17T21:06:09.962824Z", "created_by": "Test User 1", "assign_order": "no", "request_from": "web", "service_name": "Nagad", "service_slug": "mfs_nagad", "beneficiary_id": 16, "current_amount": 20948.290816, "previous_amount": 20948.290816, "purchase_number": "CAN00000000000000024", "beneficiary_data": {"reference_no": "MCM00000000000000024", "sender_information": {"name": "Test User 1", "email": "testuser1@gmail.com", "mobile": "01600000007", "profile": {"id_doc": {"id_no": "12345678", "id_type": "passport", "id_issue_at": "2024-05-12T00:00:00.000000Z", "id_expired_at": "2029-05-12T00:00:00.000000Z", "id_no_duplicate": "no", "id_issue_country": "Bangladesh"}, "blacklisted": "no", "profile_data": {"note": "Testing", "gender": "male", "occupation": "service", "father_name": "Mustak Ahmed", "mother_name": "Hamida Begum", "nationality": "Bangladeshi", "marital_status": "unmarried", "source_of_income": "salary"}, "date_of_birth": "1999-05-12T00:00:00.000000Z", "present_address": {"address": "Mohammadpur", "city_id": 16152, "state_id": 866, "city_data": null, "city_name": "Ajax", "post_code": "1234", "country_id": 39, "state_data": [], "state_name": "Ontario", "country_data": {"is_serving": true, "language_enabled": true, "multi_currency_enabled": true}, "country_name": "Canada"}}, "currency": "CAD", "language": "en", "fcm_token": "e1xcoyZ4AJ8jdkb37mG6RgFffqSTd6fRtHAYZlHI"}, "wallet_information": {"bank_data": {"swift_code": null}, "bank_name": "NAGAD", "bank_slug": "nagad", "vendor_code": {"remit": {"agrani": null, "emqapi": null, "valyou": null, "citybank": null, "transfast": null, "islamibank": "42"}}}, "receiver_information": {"city_id": 8486, "state_id": 771, "city_data": null, "city_name": "Dhaka", "country_id": 19, "state_data": [], "state_name": "Dhaka District", "relation_id": 79, "country_data": {"is_serving": true, "language_enabled": true, "multi_currency_enabled": true}, "country_name": "Bangladesh", "relation_data": null, "relation_name": "Others", "beneficiary_data": {"email": "mah.shamim@gmail.com", "bank_name": null, "cash_name": null, "wallet_id": 635, "wallet_name": "NAGAD", "account_name": "Bkash", "bank_branch_name": null, "instant_bank_name": null, "wallet_account_number": "01614747054", "instant_bank_branch_name": null}, "beneficiary_name": "MD ARIFUL HAQUE NAGAD", "beneficiary_mobile": "+8801760233030", "beneficiary_address": null, "beneficiary_type_id": 5, "beneficiary_type_name": "Wallet Transfer"}}, "master_user_name": "Afghanistan Master User", "service_stat_data": {"charge": "3%", "discount": "7%", "commission": "6%", "charge_refund": "yes", "discount_refund": "yes", "service_stat_id": 7937, "commission_refund": "yes", "charge_break_down_id": null}, "beneficiary_type_id": 5, "currency_convert_rate": {"rate": 69.26, "input": "BDT", "amount": "720.30", "output": "CAD", "converted": 10.399942246606988, "amount_formatted": "CA$720.30", "converted_formatted": "BDT 10.40"}, "wallet_account_number": "01614747054", "created_by_mobile_number": "01600000007", "wallet_account_actual_name": "Bkash", "system_notification_variable_failed": "wallet_transfer_failed", "system_notification_variable_success": "wallet_transfer_success"}',
            'status' => 'successful',
            'creator_id' => null,
            'editor_id' => null,
            'destroyer_id' => null,
            'restorer_id' => null,
            'created_at' => '2024-05-17 21:06:09',
            'updated_at' => '2024-05-17 21:06:10',
            'deleted_at' => null,
            'restored_at' => null,
        ];
    }

    public function islamiBankValidateBeneficiaryWalletNagad(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        $order = $repo->list(['service_id' => 17, 'sort' => 'orders.id', 'dir' => 'desc'])->first();
        //dd($order);
        $order_data = $order->order_data;
        //$order = $this->__dummyWalletNagadTransferOrderData();
        //$order_data = json_decode($order['order_data'], true);
        $order_data['beneficiary_data']['receiver_information']['beneficiary_data']['wallet_account_number'] = '016147470541';
        //$data['account_number'] = '016147470541';
        //$data['account_type'] = $order_data;
        dump($vendor->validateBeneficiaryWallet($order_data));
    }

    public function islamiBankWalletTransferNagad(): void
    {
        $vendor = app()->make(IslamiBankApi::class);
        $repo = app()->make(OrderService::class);
        $order = $repo->list(['service_id' => 17, 'sort' => 'orders.id', 'dir' => 'desc'])->first();
        //dd($order);
        $order_data = $order->order_data;
        //dd($order_data);
        //$order = $this->__dummyWalletNagadTransferOrderData();
        //$order_data = json_decode($order['order_data'], true);
        $order_data['beneficiary_data']['reference_no'] = 'TEST'.time();
        $order_data['sending_amount'] = $order['amount'];
        $order_data['sending_currency'] = $order['currency'];
        //dd($order_data);
        dump($vendor->directCreditRemittance($order_data));
    }
}
