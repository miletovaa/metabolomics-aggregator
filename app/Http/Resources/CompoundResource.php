<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompoundResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'canonical_name' => $this->canonical_name,
            'iupac_name' => $this->iupac_name,
            'molecular_formula' => $this->molecular_formula,
            'smiles' => $this->smiles,
            'inchi' => $this->inchi,
            'inchikey' => $this->inchikey,
            'pubchem_cid' => $this->pubchem_cid,
            'cas' => $this->cas,
            'hmdb_id' => $this->hmdb_id,
            'chebi_id' => $this->chebi_id,

            'synonyms' => CompoundSynonymResource::collection($this->whenLoaded('synonyms')),
            'retention_indices' => RetentionIndexResource::collection($this->whenLoaded('retentionIndices')),
            'taxonomy' => new TaxonomyResource($this->whenLoaded('taxonomy')),

            'diseases' => DiseaseResource::collection($this->whenLoaded('diseases')),
            'ontologies' => OntologyResource::collection($this->whenLoaded('ontologies')),
            'biomarkers' => BiomarkerResource::collection($this->whenLoaded('biomarkers')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
