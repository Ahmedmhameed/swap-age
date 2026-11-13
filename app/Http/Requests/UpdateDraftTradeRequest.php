<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDraftTradeRequest extends FormRequest
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
            'items' => ['sometimes', 'array'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.direction' => ['required', 'string', 'in:owner_gives,owner_receives'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.condition' => ['required', 'string', 'max:50'],
            'items.*.description' => ['required', 'string'],
            'items.*.id' => ['sometimes', 'integer', 'exists:trade_items,id'],
        ];
    }
}
