<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSampleRequest extends FormRequest
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
            'lab_sample_id' => ['nullable', 'string', 'max:255'],
            'external_id' => ['nullable', 'string', 'max:255'],
            'matrix_group' => ['nullable', 'string', 'max:255'],
            'sample_group' => ['required', 'string', 'max:50'],
            'sample_subgroup' => ['nullable', 'string', 'max:50'],
            'date_received' => ['nullable', 'date'],
            'storage_condition' => ['nullable', 'string', 'max:50'],
            'storage_condition_details' => ['nullable', 'array'],
            'responsible_analyst_id' => ['nullable', 'exists:users,id'],
            'purpose_of_analysis' => ['nullable', 'array'],
            'planned_analysis' => ['nullable', 'array'],
            'sample_type' => ['nullable', 'string', 'max:50'],
            'type_details' => ['nullable', 'array'],
        ];
    }
}
