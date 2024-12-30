<?php

namespace Fintech\Remit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoneyTransferPaymentRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'interac_email' => ['required', 'string', 'min:5', 'max:255', 'email:rfc,dns'],
            'vendor' => ['required', 'string'],
        ];
    }
}