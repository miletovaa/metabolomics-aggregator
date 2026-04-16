<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectCompoundResource extends JsonResource
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
            'project_id' => $this->project_id,
            'compound_id' => $this->compound_id,
            'custom_name' => $this->custom_name,
            'is_duplicate' => $this->is_duplicate,
            'mz' => $this->mz,
            'rt' => $this->rt,
            'is_mapped' => $this->is_mapped,
            'notes' => $this->notes,
            'compound' => new CompoundResource($this->whenLoaded('compound')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
