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
        dump($vendor->fetchAccountDetail($order_data));
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
        dump($vendor->fetchAccountDetail($order_data));
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
