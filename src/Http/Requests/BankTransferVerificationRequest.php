<?php

namespace Fintech\Remit\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BankTransferVerificationRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'account_no' => ['required', 'string', 'min:5', 'max:255'],
            'slug' => ['required', 'string', 'min:3'],
            'branch_id' => ['required', 'integer', 'min:1'],
            'account_type_id' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
