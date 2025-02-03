<?php

namespace Fintech\Remit\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBankTransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'allow_insufficient_balance' => ['nullable', 'boolean'],
            'user_id' => ['nullable', 'integer', 'min:1'],
            'source_country_id' => ['required', 'integer', 'min:1', 'master_currency'],
            'destination_country_id' => ['required', 'integer', 'min:1', 'master_currency'],
            'service_id' => ['required', 'integer', 'min:1'],
            'ordered_at' => ['required', 'date', 'date_format:Y-m-d H:i:s', 'before_or_equal:'.date('Y-m-d H:i:s', strtotime('+3 seconds'))],
            'amount' => ['required', 'numeric'],
            'currency' => ['required', 'string', 'size:3'],
            'converted_currency' => ['required', 'string', 'size:3'],
            'order_data' => ['nullable', 'array'],
            'reverse' => ['nullable', 'boolean'],
            'order_data.request_from' => ['string', 'required'],
            'order_data.business_type' => ['string', 'nullable', 'in:personal,corporate'],
            'order_data.transaction_type' => ['string', 'nullable', 'in:fast,low'],
            'order_data.beneficiary_type_id' => ['integer', 'nullable'],
            'order_data.beneficiary_id' => ['integer', 'nullable'],
            'order_data.bank_id' => ['integer', 'nullable'],
            'order_data.bank_branch_id' => ['integer', 'nullable'],
            'order_data.bank_account_number' => ['string', 'nullable'],
            'order_data.fund_source' => ['string', 'nullable'],
            'order_data.remittance_purpose' => ['string', 'nullable'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->mergeIfMissing(['allow_insufficient_balance' => false]);

        $order_data = $this->input('order_data');

        $order_data['request_from'] = request()->platform()->value;

        if (empty($order_data['business_type'])) {
            $order_data['business_type'] = 'personal';
        }

        if (empty($order_data['transaction_type'])) {
            $order_data['transaction_type'] = 'low';
        }

        $this->merge(['order_data' => $order_data]);
    }

    /**
     * Get the validation attributes that apply to the request.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            //
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            //
        ];
    }
}
