<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTradeCashRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cash_amount_owner' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:10000'],
            'cash_amount_counterparty' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:10000'],
            'cash_currency' => ['sometimes', 'nullable', 'string', 'size:3'],
        ];
    }
}
