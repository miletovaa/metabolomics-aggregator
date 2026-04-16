<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectCompoundRequest extends FormRequest
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
            'compound_id' => ['nullable', 'exists:compounds,id'],
            'custom_name' => ['nullable', 'string', 'max:255'],
            'is_duplicate' => ['nullable', 'boolean'],
            'mz' => ['nullable', 'numeric'],
            'rt' => ['nullable', 'numeric'],
            'is_mapped' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
