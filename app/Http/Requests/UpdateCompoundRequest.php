<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompoundRequest extends FormRequest
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
        $compoundId = $this->route('compound')->id;

        return [
            'canonical_name' => ['sometimes', 'string', 'max:255'],
            'iupac_name' => ['nullable', 'string', 'max:255'],
            'molecular_formula' => ['nullable', 'string', 'max:255'],
            'smiles' => ['nullable', 'string'],
            'inchi' => ['nullable', 'string', Rule::unique('compounds', 'inchi')->ignore($compoundId)],
            'inchikey' => ['nullable', 'string', 'max:30', Rule::unique('compounds', 'inchikey')->ignore($compoundId)],
            'pubchem_cid' => ['nullable', 'string', 'max:255', Rule::unique('compounds', 'pubchem_cid')->ignore($compoundId)],
            'cas' => ['nullable', 'string', 'max:255'],
            'hmdb_id' => ['nullable', 'string', 'max:255', Rule::unique('compounds', 'hmdb_id')->ignore($compoundId)],
            'chebi_id' => ['nullable', 'string', 'max:255', Rule::unique('compounds', 'chebi_id')->ignore($compoundId)],
        ];
    }
}
