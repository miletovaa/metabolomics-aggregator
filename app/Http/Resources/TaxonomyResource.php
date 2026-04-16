<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxonomyResource extends JsonResource
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
            'kingdom' => $this->kingdom,
            'superclass' => $this->superclass,
            'class' => $this->class,
            'subclass' => $this->subclass,
            'direct_parent' => $this->direct_parent,
            'alternative_parents' => $this->alternative_parents,
            'raw_json' => $this->raw_json,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
