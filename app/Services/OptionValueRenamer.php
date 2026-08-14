<?php

namespace App\Services;

use App\Models\Experiment;
use App\Models\ExperimentRecord;
use App\Models\Project;
use App\Models\Sample;
use App\Models\Sampling;
use Illuminate\Database\Eloquent\Model;

/**
 * When an option value's key is renamed (its label changed, which regenerates the key),
 * every record that already stored the old key needs to be updated to the new one —
 * otherwise existing data silently falls out of sync with the option list and shows up
 * as an unrecognized raw key instead of its label.
 *
 * The same list can be stored in different shapes depending on where it's used: a plain
 * column, a JSON array column, a single key inside a JSON object column, or an array
 * nested inside that JSON object. A few JSON keys are also shared by two *different*
 * lists depending on another column on the same row (e.g. type_details.producer holds
 * plant_producer values when sample_group=plant, but status_options values when
 * sample_group=animal) — the optional `where` scopes the locator to just those rows.
 */
class OptionValueRenamer
{
    private const LOCATORS = [
        'sample_groups' => [
            ['model' => Sample::class, 'column' => 'sample_group', 'shape' => 'scalar'],
        ],
        'sample_subgroups' => [
            ['model' => Sample::class, 'column' => 'sample_subgroup', 'shape' => 'scalar'],
        ],
        'storage_conditions' => [
            ['model' => Sample::class, 'column' => 'storage_condition', 'shape' => 'scalar'],
        ],
        'storage_condition_details' => [
            ['model' => Sample::class, 'column' => 'storage_condition_details', 'shape' => 'json_array'],
        ],
        'purposes_of_analysis' => [
            ['model' => Sample::class, 'column' => 'purpose_of_analysis', 'shape' => 'json_array'],
        ],
        'planned_analyses' => [
            ['model' => Sample::class, 'column' => 'planned_analysis', 'shape' => 'json_array'],
        ],
        'status_options' => [
            ['model' => Sample::class, 'column' => 'type_details', 'shape' => 'json_object_key', 'json_key' => 'status'],
            ['model' => Sample::class, 'column' => 'type_details', 'shape' => 'json_object_key', 'json_key' => 'producer', 'where' => ['sample_group' => 'animal']],
        ],
        'production_types' => [
            ['model' => Sample::class, 'column' => 'type_details', 'shape' => 'json_object_key', 'json_key' => 'production_type'],
        ],
        'source_of_water' => [
            ['model' => Sample::class, 'column' => 'type_details', 'shape' => 'json_object_key', 'json_key' => 'source_of_water'],
            ['model' => Sample::class, 'column' => 'type_details', 'shape' => 'json_object_key', 'json_key' => 'source_of_drinking_water'],
        ],
        'part_of_plant' => [
            ['model' => Sample::class, 'column' => 'type_details', 'shape' => 'json_object_key', 'json_key' => 'part_of_plant'],
        ],
        'plant_producer' => [
            ['model' => Sample::class, 'column' => 'type_details', 'shape' => 'json_object_key', 'json_key' => 'producer', 'where' => ['sample_group' => 'plant']],
        ],
        'plant_processing_types' => [
            ['model' => Sample::class, 'column' => 'type_details', 'shape' => 'json_object_key', 'json_key' => 'processing_type', 'where' => ['sample_group' => 'plant']],
        ],
        'part_of_animal' => [
            ['model' => Sample::class, 'column' => 'type_details', 'shape' => 'json_object_key', 'json_key' => 'part_of_animal'],
        ],
        'animal_processing_types' => [
            ['model' => Sample::class, 'column' => 'type_details', 'shape' => 'json_object_key', 'json_key' => 'processing_type', 'where' => ['sample_group' => 'animal']],
        ],
        'animal_feed_types' => [
            ['model' => Sample::class, 'column' => 'type_details', 'shape' => 'json_object_array_key', 'json_key' => 'feed'],
        ],
        'sampling_methods' => [
            ['model' => Sampling::class, 'column' => 'sampling_method', 'shape' => 'scalar'],
        ],
        'packaging_options' => [
            ['model' => Sampling::class, 'column' => 'packaging', 'shape' => 'scalar'],
        ],
        'experiment_statuses' => [
            ['model' => Experiment::class, 'column' => 'status', 'shape' => 'scalar'],
        ],
        'project_statuses' => [
            ['model' => Project::class, 'column' => 'status', 'shape' => 'scalar'],
        ],
        'analytes' => [
            ['model' => ExperimentRecord::class, 'column' => 'details', 'shape' => 'json_object_key', 'json_key' => 'analyte'],
        ],
        'phase_of_sample' => [
            ['model' => ExperimentRecord::class, 'column' => 'details', 'shape' => 'json_object_key', 'json_key' => 'phase_of_sample', 'where' => ['record_type' => 'sample_prep']],
        ],
        'drying_options' => [
            ['model' => ExperimentRecord::class, 'column' => 'details', 'shape' => 'json_object_key', 'json_key' => 'drying'],
        ],
        'homogenisation_options' => [
            ['model' => ExperimentRecord::class, 'column' => 'details', 'shape' => 'json_object_key', 'json_key' => 'homogenisation'],
        ],
        'preparation_methods' => [
            ['model' => ExperimentRecord::class, 'column' => 'details', 'shape' => 'json_object_array_key', 'json_key' => 'preparation_method'],
        ],
        'microwave_phase_of_sample' => [
            ['model' => ExperimentRecord::class, 'column' => 'details', 'shape' => 'json_object_key', 'json_key' => 'phase_of_sample', 'where' => ['record_type' => 'sample_prep_microwave_digestion']],
        ],
        'elements' => [
            ['model' => ExperimentRecord::class, 'column' => 'details', 'shape' => 'json_object_key', 'json_key' => 'element'],
        ],
        'mk_gc_ms_units' => [
            ['model' => ExperimentRecord::class, 'column' => 'details', 'shape' => 'json_object_key', 'json_key' => 'unit'],
        ],
    ];

    /** How many existing records currently store $key for $listKey — used to warn before delete. */
    public function usageCount(string $listKey, string $key): int
    {
        $count = 0;

        foreach (self::LOCATORS[$listKey] ?? [] as $locator) {
            $count += match ($locator['shape']) {
                'scalar' => $this->baseQuery($locator)->where($locator['column'], $key)->count(),
                'json_array' => $this->baseQuery($locator)->whereJsonContains($locator['column'], $key)->count(),
                'json_object_key' => $this->baseQuery($locator)->where("{$locator['column']}->{$locator['json_key']}", $key)->count(),
                'json_object_array_key' => $this->baseQuery($locator)->whereJsonContains("{$locator['column']}->{$locator['json_key']}", $key)->count(),
            };
        }

        return $count;
    }

    /** Update every record that stores $oldKey for $listKey to store $newKey instead. */
    public function cascade(string $listKey, string $oldKey, string $newKey): int
    {
        if ($oldKey === $newKey) {
            return 0;
        }

        $updated = 0;

        foreach (self::LOCATORS[$listKey] ?? [] as $locator) {
            $updated += match ($locator['shape']) {
                'scalar' => $this->cascadeScalar($locator, $oldKey, $newKey),
                'json_array' => $this->cascadeJsonArray($locator, $oldKey, $newKey),
                'json_object_key' => $this->cascadeJsonObjectKey($locator, $oldKey, $newKey),
                'json_object_array_key' => $this->cascadeJsonObjectArrayKey($locator, $oldKey, $newKey),
            };
        }

        return $updated;
    }

    private function cascadeScalar(array $locator, string $oldKey, string $newKey): int
    {
        return $this->baseQuery($locator)
            ->where($locator['column'], $oldKey)
            ->update([$locator['column'] => $newKey]);
    }

    private function cascadeJsonArray(array $locator, string $oldKey, string $newKey): int
    {
        $column = $locator['column'];
        $count = 0;

        $this->baseQuery($locator)
            ->whereJsonContains($column, $oldKey)
            ->each(function (Model $row) use ($column, $oldKey, $newKey, &$count) {
                $values = $row->{$column} ?? [];
                $row->{$column} = array_map(fn ($v) => $v === $oldKey ? $newKey : $v, $values);
                $row->save();
                $count++;
            });

        return $count;
    }

    private function cascadeJsonObjectKey(array $locator, string $oldKey, string $newKey): int
    {
        $column = $locator['column'];
        $jsonKey = $locator['json_key'];
        $count = 0;

        $this->baseQuery($locator)
            ->where("{$column}->{$jsonKey}", $oldKey)
            ->each(function (Model $row) use ($column, $jsonKey, $newKey, &$count) {
                $data = $row->{$column} ?? [];
                $data[$jsonKey] = $newKey;
                $row->{$column} = $data;
                $row->save();
                $count++;
            });

        return $count;
    }

    private function cascadeJsonObjectArrayKey(array $locator, string $oldKey, string $newKey): int
    {
        $column = $locator['column'];
        $jsonKey = $locator['json_key'];
        $count = 0;

        $this->baseQuery($locator)
            ->whereJsonContains("{$column}->{$jsonKey}", $oldKey)
            ->each(function (Model $row) use ($column, $jsonKey, $oldKey, $newKey, &$count) {
                $data = $row->{$column} ?? [];
                $values = $data[$jsonKey] ?? [];
                $data[$jsonKey] = array_map(fn ($v) => $v === $oldKey ? $newKey : $v, $values);
                $row->{$column} = $data;
                $row->save();
                $count++;
            });

        return $count;
    }

    private function baseQuery(array $locator)
    {
        /** @var Model $modelClass */
        $modelClass = $locator['model'];
        $query = $modelClass::query();

        foreach ($locator['where'] ?? [] as $field => $value) {
            $query->where($field, $value);
        }

        return $query;
    }
}
