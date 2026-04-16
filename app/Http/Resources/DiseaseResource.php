<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiseaseResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'category' => $this->category,

            'pivot' => $this->whenPivotLoaded('compound_disease_associations', function () {
                return [
                    'id' => $this->pivot->id,
                    'reference' => $this->pivot->reference,
                    'source_id' => $this->pivot->source_id,
                    'created_at' => $this->pivot->created_at,
                    'updated_at' => $this->pivot->updated_at,
                ];
            }),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
