<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
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
            'status' => $this->status,
            'user_id' => $this->user_id,

            'project_compounds' => ProjectCompoundResource::collection($this->whenLoaded('projectCompounds')),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
