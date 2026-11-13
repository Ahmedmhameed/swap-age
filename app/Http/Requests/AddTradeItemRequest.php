<?php

namespace App\Http\Requests;

use App\Enums\TradeStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddTradeItemRequest extends FormRequest
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
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'direction' => ['required', Rule::in(['owner_gives', 'owner_receives'])],
            'quantity' => ['required', 'integer', 'min:1'],
            'condition' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string'],
            'product_photo' => [
                Rule::requiredIf(fn (): bool => $this->route('trade')?->status === TradeStatus::Negotiating),
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:5120',
            ],
        ];
    }
}
