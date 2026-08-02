<?php

namespace App\Http\Requests;

use App\Models\ExperimentRecord;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExperimentRecordRequest extends FormRequest
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
            'sample_id' => ['required', 'exists:samples,id'],
            'parent_record_id' => ['nullable', 'exists:experiment_records,id'],
            'record_type' => ['required', 'string', Rule::in(array_keys(ExperimentRecord::RECORD_TYPES))],
            'performed_by' => ['nullable', 'exists:users,id'],
            'performed_at' => ['nullable', 'date'],
            'instrument' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'details' => ['nullable', 'array'],
        ];
    }
}
