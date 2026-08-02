<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExperimentRecordResource extends JsonResource
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
            'experiment_id' => $this->experiment_id,
            'sample_id' => $this->sample_id,
            'parent_record_id' => $this->parent_record_id,
            'record_type' => $this->record_type,
            'performed_by' => $this->performed_by,
            'performed_at' => $this->performed_at,
            'instrument' => $this->instrument,
            'note' => $this->note,
            'details' => $this->details,
            // Flattened from the sample join, mirroring
            // `SELECT er.*, s.sample_group, s.sample_subgroup, s.matrix_group, s.storage_condition`
            // so the client never needs a second merge step. Both `sample` and
            // `experiment` are always eager-loaded by the controllers that
            // return this resource, so plain access here never lazy-loads.
            'sample_group' => $this->sample?->sample_group,
            'sample_subgroup' => $this->sample?->sample_subgroup,
            'matrix_group' => $this->sample?->matrix_group,
            'storage_condition' => $this->sample?->storage_condition,
            'project_id' => $this->sample?->project_id ?? $this->experiment?->project_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
