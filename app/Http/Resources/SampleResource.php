<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SampleResource extends JsonResource
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
            'lab_sample_id' => $this->lab_sample_id,
            'external_id' => $this->external_id,
            'matrix_group' => $this->matrix_group,
            'sample_group' => $this->sample_group,
            'sample_subgroup' => $this->sample_subgroup,
            'date_received' => $this->date_received,
            'storage_condition' => $this->storage_condition,
            'storage_condition_details' => $this->storage_condition_details,
            'responsible_analyst_id' => $this->responsible_analyst_id,
            'purpose_of_analysis' => $this->purpose_of_analysis,
            'planned_analysis' => $this->planned_analysis,
            'sample_type' => $this->sample_type,
            'type_details' => $this->type_details,
            'group_label' => $this->groupLabel(),
            'subgroup_label' => $this->subgroupLabel(),
            'storage_condition_label' => $this->storageConditionLabel(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
