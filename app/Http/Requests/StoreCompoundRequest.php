<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompoundRequest extends FormRequest
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
            'canonical_name' => ['required', 'string', 'max:255'],
            'iupac_name' => ['nullable', 'string', 'max:255'],
            'molecular_formula' => ['nullable', 'string', 'max:255'],
            'smiles' => ['nullable', 'string'],
            'inchi' => ['nullable', 'string', 'unique:compounds,inchi'],
            'inchikey' => ['nullable', 'string', 'max:30', 'unique:compounds,inchikey'],
            'pubchem_cid' => ['nullable', 'string', 'max:255', 'unique:compounds,pubchem_cid'],
            'cas' => ['nullable', 'string', 'max:255'],
            'hmdb_id' => ['nullable', 'string', 'max:255', 'unique:compounds,hmdb_id'],
            'chebi_id' => ['nullable', 'string', 'max:255', 'unique:compounds,chebi_id'],
        ];
    }
}
