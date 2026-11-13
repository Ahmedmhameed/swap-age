<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadEvidenceRequest extends FormRequest
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
            'kind' => ['required', Rule::in(['product_photo', 'document', 'recording'])],
            'trade_item_id' => ['nullable', 'integer', 'exists:trade_items,id'],
            'file' => [
                'required',
                'file',
                Rule::when($this->input('kind') === 'product_photo', ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120']),
                Rule::when($this->input('kind') === 'document', ['mimes:pdf', 'max:10240']),
                Rule::when($this->input('kind') === 'recording', ['mimes:mp3,wav,ogg', 'max:10240']),
            ],
        ];
    }
}
