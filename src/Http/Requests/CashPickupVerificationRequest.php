<?php

namespace Fintech\Remit\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CashPickupVerificationRequest extends FormRequest
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
            'user_id' => 'required|integer|exists:users,id',
            'account_no' => ['required', 'string', 'min:5', 'max:255'],
            'slug' => ['required', 'string', 'min:3', 'exists:banks,slug'],
        ];
    }

    protected function prepareForValidation()
    {
        $this->mergeIfMissing([
            'user_id' => auth()->id()
        ]);
    }
}
