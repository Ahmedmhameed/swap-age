<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CancelTradeRequest extends FormRequest
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
            'reason' => [
                'required',
                Rule::in([
                    'changed_mind',
                    'found_better_option',
                    'terms_disagreement',
                    'items_changed',
                    'item_mismatch',
                    'other_party_unresponsive',
                    'other',
                ]),
            ],
            'note' => ['nullable', 'string'],
            'cancellation_proof' => [
                Rule::requiredIf($this->input('reason') === 'item_mismatch'),
                'nullable',
                'file',
                'mimes:jpeg,jpg,png,webp,pdf,mp3,wav,ogg',
                'max:10240',
            ],
        ];
    }
}
