<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RequestProductRequest extends FormRequest
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
            'brand' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:150'],
            'model' => ['nullable', 'string', 'max:150'],
            'version' => ['nullable', 'string', 'max:100'],
            'specs' => ['nullable', 'array'],
        ];
    }
}
