<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Sample;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SampleImporter
{
    public const MULTI_VALUE_DELIMITER = ';';

    /**
     * Type-details fields per sample_group, mirroring the fields on the sample
     * create/edit form (resources/views/livewire/samples/_form.blade.php).
     */
    private const TYPE_DETAIL_FIELDS = [
        'plant' => [
            'latin_name' => null,
            'part_of_plant' => Sample::PART_OF_PLANT,
            'harvest_year' => null,
            'status' => Sample::STATUS_OPTIONS,
            'producer' => Sample::PLANT_PRODUCER,
            'production_type' => Sample::PRODUCTION_TYPES,
            'declared_country_of_origin' => null,
            'country_of_origin_of_raw_material' => null,
            'region_of_origin' => null,
            'irrigation' => ['yes' => 'Yes', 'no' => 'No'],
            'source_of_water' => Sample::SOURCE_OF_WATER,
            'processing_type' => Sample::PLANT_PROCESSING_TYPES,
            'note' => null,
        ],
        'animal' => [
            'common_name' => null,
            'latin_name' => null,
            'part_of_animal' => Sample::PART_OF_ANIMAL,
            'status' => Sample::STATUS_OPTIONS,
            'producer' => Sample::STATUS_OPTIONS,
            'production_type' => Sample::PRODUCTION_TYPES,
            'country_of_origin' => null,
            'region_of_origin' => null,
            'source_of_drinking_water' => Sample::SOURCE_OF_WATER,
            'processing_type' => Sample::ANIMAL_PROCESSING_TYPES,
            'feed' => Sample::ANIMAL_FEED_TYPES,
            'note' => null,
        ],
    ];

    private const MULTI_VALUE_FIELDS = ['feed'];

    /**
     * Import rows produced by SamplesImport (one assoc array per spreadsheet row,
     * keyed by the slugged header). Rows that are entirely blank are skipped and
     * not counted. Every other row is validated independently — a bad row is
     * reported and skipped, it never blocks the rows around it.
     *
     * @return array{total: int, imported: int, samples: Collection<int, Sample>, errors: array<int, array{row: int, messages: string[]}>}
     */
    public function import(Collection $rows): array
    {
        $imported = collect();
        $errors = [];
        $total = 0;

        foreach ($rows as $index => $row) {
            // Heading row is spreadsheet row 1, so the first data row is row 2.
            $rowNumber = $index + 2;

            $data = $this->normalizeRow($row instanceof Collection ? $row->toArray() : (array) $row);

            if ($this->isBlank($data)) {
                continue;
            }

            $total++;

            [$attributes, $messages] = $this->validateRow($data);

            if ($messages) {
                $errors[] = ['row' => $rowNumber, 'messages' => $messages];

                continue;
            }

            $imported->push(Sample::create($attributes));
        }

        return [
            'total' => $total,
            'imported' => $imported->count(),
            'samples' => $imported,
            'errors' => $errors,
        ];
    }

    private function normalizeRow(array $row): array
    {
        return collect($row)
            ->mapWithKeys(fn ($value, $key) => [$key => $this->normalizeValue($value)])
            ->all();
    }

    private function normalizeValue(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function isBlank(array $data): bool
    {
        return collect($data)->every(fn ($value) => $value === null);
    }

    /** Accepts "3" or Excel-numeric-cell "3.0"/"3.00"; rejects anything with a non-zero fraction. */
    private function parseId(string $raw): ?int
    {
        if (! is_numeric($raw)) {
            return null;
        }

        $float = (float) $raw;
        if ($float != (int) $float) {
            return null;
        }

        return (int) $float;
    }

    /**
     * @return array{0: array<string, mixed>, 1: string[]}
     */
    private function validateRow(array $data): array
    {
        $errors = [];
        $attributes = [];

        $attributes['lab_sample_id'] = $data['lab_sample_id'] ?? null;
        $attributes['external_id'] = $data['external_id'] ?? null;
        $attributes['matrix_group'] = $data['matrix_group'] ?? null;
        $attributes['note'] = $data['note'] ?? null;

        $group = $data['sample_group'] ?? null;
        if ($group === null) {
            $errors[] = 'sample_group is required.';
        } elseif (! isset(Sample::GROUPS[$group])) {
            $errors[] = "sample_group '{$group}' is not valid. Valid options: " . implode(', ', array_keys(Sample::GROUPS)) . '.';
        } else {
            $attributes['sample_group'] = $group;
        }

        $subgroup = $data['sample_subgroup'] ?? null;
        if ($subgroup !== null) {
            $subgroupOptions = isset($attributes['sample_group']) ? (Sample::SUBGROUPS[$attributes['sample_group']] ?? []) : [];
            if (! isset($subgroupOptions[$subgroup])) {
                $errors[] = $subgroupOptions === []
                    ? "sample_subgroup '{$subgroup}' is not valid: sample_group '" . ($attributes['sample_group'] ?? $group) . "' has no subgroups."
                    : "sample_subgroup '{$subgroup}' is not valid for sample_group '{$attributes['sample_group']}'. Valid options: " . implode(', ', array_keys($subgroupOptions)) . '.';
            } else {
                $attributes['sample_subgroup'] = $subgroup;
            }
        }

        $dateReceived = $data['date_received'] ?? null;
        if ($dateReceived !== null) {
            try {
                $attributes['date_received'] = Carbon::parse($dateReceived)->format('Y-m-d');
            } catch (\Throwable) {
                $errors[] = "date_received '{$dateReceived}' is not a valid date.";
            }
        }

        $storageCondition = $data['storage_condition'] ?? null;
        if ($storageCondition !== null) {
            if (! isset(Sample::STORAGE_CONDITIONS[$storageCondition])) {
                $errors[] = "storage_condition '{$storageCondition}' is not valid. Valid options: " . implode(', ', array_keys(Sample::STORAGE_CONDITIONS)) . '.';
            } else {
                $attributes['storage_condition'] = $storageCondition;
            }
        }

        [$storageConditionDetails, $storageConditionDetailsErrors] = $this->parseMultiEnum(
            $data['storage_condition_details'] ?? null,
            Sample::STORAGE_CONDITION_DETAILS,
            'storage_condition_details',
        );
        $errors = array_merge($errors, $storageConditionDetailsErrors);
        if ($storageConditionDetails !== null) {
            $attributes['storage_condition_details'] = $storageConditionDetails;
        }

        [$purposeOfAnalysis, $purposeErrors] = $this->parseMultiEnum(
            $data['purpose_of_analysis'] ?? null,
            Sample::PURPOSES_OF_ANALYSIS,
            'purpose_of_analysis',
        );
        $errors = array_merge($errors, $purposeErrors);
        if ($purposeOfAnalysis !== null) {
            $attributes['purpose_of_analysis'] = $purposeOfAnalysis;
        }

        [$plannedAnalysis, $plannedErrors] = $this->parseMultiEnum(
            $data['planned_analysis'] ?? null,
            Sample::PLANNED_ANALYSES,
            'planned_analysis',
        );
        $errors = array_merge($errors, $plannedErrors);
        if ($plannedAnalysis !== null) {
            $attributes['planned_analysis'] = $plannedAnalysis;
        }

        $analystId = $data['responsible_analyst_id'] ?? null;
        if ($analystId !== null) {
            $id = $this->parseId($analystId);
            if ($id === null || ! User::whereKey($id)->exists()) {
                $errors[] = "responsible_analyst_id '{$analystId}' does not match an existing user.";
            } else {
                $attributes['responsible_analyst_id'] = $id;
            }
        }

        $projectId = $data['project_id'] ?? null;
        if ($projectId !== null) {
            $id = $this->parseId($projectId);
            if ($id === null || ! Project::whereKey($id)->exists()) {
                $errors[] = "project_id '{$projectId}' does not match an existing project.";
            } else {
                $attributes['project_id'] = $id;
            }
        }

        if (isset($attributes['sample_group']) && in_array($attributes['sample_group'], Sample::TYPE_DETAIL_GROUPS, true)) {
            [$typeDetails, $typeDetailErrors] = $this->parseTypeDetails($attributes['sample_group'], $data);
            $errors = array_merge($errors, $typeDetailErrors);
            $attributes['type_details'] = $typeDetails ?: null;
        }

        return [$attributes, $errors];
    }

    /**
     * @param array<string, string>|null $options
     * @return array{0: string[]|null, 1: string[]}
     */
    private function parseMultiEnum(?string $raw, array $options, string $field): array
    {
        if ($raw === null) {
            return [null, []];
        }

        $values = array_values(array_filter(array_map('trim', explode(self::MULTI_VALUE_DELIMITER, $raw)), fn ($v) => $v !== ''));

        $invalid = array_diff($values, array_keys($options));
        if ($invalid !== []) {
            return [null, ["{$field} has invalid value(s): " . implode(', ', $invalid) . '. Valid options: ' . implode(', ', array_keys($options)) . '.']];
        }

        return [$values ?: null, []];
    }

    /**
     * @return array{0: array<string, mixed>, 1: string[]}
     */
    private function parseTypeDetails(string $group, array $data): array
    {
        $errors = [];
        $details = [];

        foreach (self::TYPE_DETAIL_FIELDS[$group] as $field => $options) {
            $raw = $data[$field] ?? null;
            if ($raw === null) {
                continue;
            }

            if (in_array($field, self::MULTI_VALUE_FIELDS, true)) {
                [$values, $fieldErrors] = $this->parseMultiEnum($raw, $options, $field);
                $errors = array_merge($errors, $fieldErrors);
                if ($values !== null) {
                    $details[$field] = $values;
                }

                continue;
            }

            if ($options !== null && ! isset($options[$raw])) {
                $errors[] = "{$field} '{$raw}' is not valid. Valid options: " . implode(', ', array_keys($options)) . '.';

                continue;
            }

            $details[$field] = $raw;
        }

        return [$details, $errors];
    }
}