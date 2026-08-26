<?php

namespace App\Services;

use App\Models\Experiment;
use App\Models\ExperimentRecord;
use App\Models\Sample;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Parses the ICP-MS "multi-element analysis" report format (one sheet holding, top to bottom:
 * a per-element QC header, a variable number of reference-standard blocks, then a per-sample
 * results table) and stores it as ExperimentRecord rows:
 *
 * - One `analysis_elemental_composition` record per sample, carrying the run's QC/standards
 *   data (identical across all samples in the run — it describes the run, not the sample) in
 *   details.elemental_qc.
 * - One `result_elemental_composition` record per sample per element, linked to that sample's
 *   analysis record via parent_record_id.
 *
 * Rows are matched to elements positionally (column order), not by re-reading the header on
 * every row — the header/QC block and each standard block are found by their column B label,
 * not by fixed row numbers, since the number of standard blocks varies between reports.
 */
class ElementalCompositionImporter
{
    private const HEADER_FIELD_LABELS = [
        'odločitev' => 'decision',
        'mode' => 'mode',
        'rečitev' => 'dilution_factor',
        'lod vz (ng/g)' => 'lod_ng_g',
        '%n<lod vz' => 'pct_below_lod',
    ];

    private const STANDARD_FIELD_LABELS = [
        'avg (ng/g)' => 'avg',
        'rsd (%)' => 'rsd_pct',
        'referenčna vrednost (ng/g)' => 'reference_value',
        'negotovost rm (ng/g)' => 'uncertainty',
    ];

    /**
     * @return array{
     *     total: int,
     *     importedSamples: int,
     *     importedRecords: int,
     *     errors: array<int, array{row: int, messages: string[]}>,
     * }
     */
    public function import(Collection $rows, Experiment $experiment, ?int $performedBy): array
    {
        $rows = $rows->values();

        [$elements, $headerPerElement, $standards, $noteLines, $resultsStartIndex] = $this->parseHeaderAndStandards($rows);

        $note = trim(implode("\n", array_filter($noteLines, fn ($l) => trim((string) $l) !== '')));

        $sampleRows = $this->parseSampleRows($rows, $resultsStartIndex, $elements);

        $total = count($sampleRows);
        $errors = [];
        $importedSamples = 0;
        $importedRecords = 0;

        DB::transaction(function () use ($sampleRows, $experiment, $performedBy, $headerPerElement, $standards, $elements, $note, &$errors, &$importedSamples, &$importedRecords) {
            foreach ($sampleRows as $sampleRow) {
                $sample = Sample::where('lab_sample_id', $sampleRow['sample_identifier'])
                    ->orWhere('external_id', $sampleRow['sample_identifier'])
                    ->first();

                if (! $sample) {
                    $errors[] = [
                        'row' => $sampleRow['row'],
                        'messages' => ["No sample found matching id \"{$sampleRow['sample_identifier']}\"."],
                    ];

                    continue;
                }

                if ($sampleRow['values'] === []) {
                    $errors[] = ['row' => $sampleRow['row'], 'messages' => ['No element values on this row.']];

                    continue;
                }

                $analysisRecord = ExperimentRecord::create([
                    'experiment_id' => $experiment->id,
                    'sample_id' => $sample->id,
                    'record_type' => 'analysis_elemental_composition',
                    'performed_by' => $performedBy,
                    'performed_at' => $sampleRow['performed_at'],
                    'note' => $note ?: null,
                    'details' => [
                        'elemental_qc' => [
                            'elements' => $elements,
                            'per_element' => $headerPerElement,
                            'standards' => $standards,
                        ],
                    ],
                ]);
                $importedRecords++;

                foreach ($sampleRow['values'] as $symbol => $value) {
                    ExperimentRecord::create([
                        'experiment_id' => $experiment->id,
                        'sample_id' => $sample->id,
                        'parent_record_id' => $analysisRecord->id,
                        'record_type' => 'result_elemental_composition',
                        'performed_by' => $performedBy,
                        'performed_at' => $sampleRow['performed_at'],
                        'details' => [
                            'element' => $symbol,
                            'value' => $value,
                            'unit' => 'ng/g',
                        ],
                    ]);
                    $importedRecords++;
                }

                $importedSamples++;
            }
        });

        return [
            'total' => $total,
            'importedSamples' => $importedSamples,
            'importedRecords' => $importedRecords,
            'errors' => $errors,
        ];
    }

    /**
     * Walks the header/QC block and every reference-standard block, dispatching each row by
     * its column-B label rather than a fixed row number. Stops at the "id vzorca" row, which
     * marks the start of the per-sample results table.
     *
     * @return array{0: string[], 1: array<string, array<string, mixed>>, 2: array<int, array{name: string, per_element: array<string, array<string, mixed>>}>, 3: string[], 4: int}
     */
    private function parseHeaderAndStandards(Collection $rows): array
    {
        $elements = [];
        $headerPerElement = [];
        $standardsByName = [];
        $standardOrder = [];
        $noteLines = [];
        $currentStandardName = null;
        $resultsStartIndex = $rows->count();

        foreach ($rows as $index => $row) {
            $row = $row instanceof Collection ? $row->all() : (array) $row;
            $colA = trim((string) ($row[0] ?? ''));
            $colB = trim((string) ($row[1] ?? ''));
            $label = mb_strtolower($colB);

            if ($label === 'id vzorca') {
                $resultsStartIndex = $index + 1;
                break;
            }

            if ($label === 'element') {
                $elements = $this->extractRowValues($row, count($row))
                    ->map(fn ($v) => trim((string) $v))
                    ->filter(fn ($v) => $v !== '')
                    ->values()
                    ->all();
                if ($colA !== '') {
                    $noteLines[] = $colA;
                }

                continue;
            }

            if (isset(self::HEADER_FIELD_LABELS[$label])) {
                $field = self::HEADER_FIELD_LABELS[$label];
                foreach ($elements as $i => $symbol) {
                    $value = $row[2 + $i] ?? null;
                    if ($value !== null && $value !== '') {
                        $headerPerElement[$symbol][$field] = $value;
                    }
                }
                if ($colA !== '') {
                    $noteLines[] = $colA;
                }

                continue;
            }

            if ($label === 'comment') {
                if ($colA !== '') {
                    $noteLines[] = $colA;
                }

                continue;
            }

            if (isset(self::STANDARD_FIELD_LABELS[$label])) {
                if ($colA !== '') {
                    $currentStandardName = $colA;
                }
                if ($currentStandardName === null) {
                    continue;
                }
                if (! isset($standardsByName[$currentStandardName])) {
                    $standardsByName[$currentStandardName] = [];
                    $standardOrder[] = $currentStandardName;
                }

                $field = self::STANDARD_FIELD_LABELS[$label];
                foreach ($elements as $i => $symbol) {
                    $value = $row[2 + $i] ?? null;
                    if ($value === null || $value === '' || $value === '---') {
                        continue;
                    }
                    $standardsByName[$currentStandardName][$symbol][$field] = $value;
                }

                continue;
            }
        }

        $standards = array_map(
            fn ($name) => ['name' => $name, 'per_element' => $standardsByName[$name]],
            $standardOrder,
        );

        return [$elements, $headerPerElement, $standards, $noteLines, $resultsStartIndex];
    }

    /**
     * @return array<int, array{row: int, performed_at: ?string, sample_identifier: string, values: array<string, mixed>}>
     */
    private function parseSampleRows(Collection $rows, int $startIndex, array $elements): array
    {
        $sampleRows = [];

        foreach ($rows->slice($startIndex) as $index => $row) {
            $row = $row instanceof Collection ? $row->all() : (array) $row;
            $rowNumber = $index + $startIndex + 1; // 1-indexed spreadsheet row

            $performedAtRaw = $row[0] ?? null;
            $sampleIdentifier = trim((string) ($row[1] ?? ''));

            if ($sampleIdentifier === '' && ($performedAtRaw === null || $performedAtRaw === '')) {
                continue;
            }

            if ($sampleIdentifier === '') {
                continue;
            }

            $values = [];
            foreach ($elements as $i => $symbol) {
                $value = $row[2 + $i] ?? null;
                if ($value !== null && $value !== '') {
                    $values[$symbol] = $value;
                }
            }

            $sampleRows[] = [
                'row' => $rowNumber,
                'performed_at' => $this->parseDate($performedAtRaw),
                'sample_identifier' => $sampleIdentifier,
                'values' => $values,
            ];
        }

        return $sampleRows;
    }

    private function parseDate(mixed $raw): ?string
    {
        if ($raw instanceof \DateTimeInterface) {
            return $raw->format('Y-m-d');
        }

        if (is_numeric($raw)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $raw)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        if (is_string($raw) && trim($raw) !== '') {
            try {
                return Carbon::parse($raw)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function extractRowValues(array $row, int $length): Collection
    {
        $values = [];
        for ($i = 2; $i < $length; $i++) {
            $values[] = $row[$i] ?? null;
        }

        return collect($values)->filter(fn ($v) => $v !== null);
    }
}
