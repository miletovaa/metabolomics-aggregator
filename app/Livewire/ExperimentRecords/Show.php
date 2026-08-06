<?php

namespace App\Livewire\ExperimentRecords;

use App\Models\Experiment;
use App\Models\ExperimentRecord;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Shows a whole "batch" together: every ExperimentRecord sharing the same sample, record type,
 * analyst, and date as the one linked to (ExperimentRecord::groupKey()) — e.g. the δ13C and δ18O
 * analysis_isotopes records prepared in the same run — rather than just the single record that
 * was clicked, since they represent one logical experimental step differing only by subject.
 */
class Show extends Component
{
    public Experiment $experiment;
    public ExperimentRecord $record;

    public function mount(Experiment $experiment, ExperimentRecord $record): void
    {
        abort_unless($record->experiment_id === $experiment->id, 404);

        $this->experiment = $experiment;
        $this->record = $record;
    }

    private function formatFieldValue(array $field, mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        return match ($field['type']) {
            'select' => $field['options'][$value] ?? (string) $value,
            'multiselect' => collect((array) $value)
                ->map(fn ($v) => $field['options'][$v] ?? $v)
                ->join(', '),
            'user_select' => User::find($value)?->name ?? "#{$value}",
            default => (string) $value,
        };
    }

    private function fieldsFor(ExperimentRecord $record): Collection
    {
        return collect(ExperimentRecord::fieldSchema($record->record_type))
            ->map(fn (array $field) => [
                'label' => $field['label'],
                'type'  => $field['type'],
                'value' => $this->formatFieldValue($field, $record->details[$field['key']] ?? null),
            ])
            ->filter(fn (array $f) => $f['value'] !== null)
            ->values();
    }

    public function render()
    {
        $siblings = $this->experiment->records()
            ->where('record_type', $this->record->record_type)
            ->where('sample_id', $this->record->sample_id)
            ->when(
                $this->record->performed_by === null,
                fn ($q) => $q->whereNull('performed_by'),
                fn ($q) => $q->where('performed_by', $this->record->performed_by),
            )
            ->when(
                $this->record->performed_at === null,
                fn ($q) => $q->whereNull('performed_at'),
                fn ($q) => $q->whereDate('performed_at', $this->record->performed_at),
            )
            ->with(['sample', 'performedBy', 'parentRecord', 'childRecords'])
            ->orderBy('id')
            ->get();

        $batch = $siblings->map(fn (ExperimentRecord $r) => [
            'record'   => $r,
            'subject'  => $r->subjectLabel(),
            'fields'   => $this->fieldsFor($r),
            'children' => $r->childRecords,
        ]);

        return view('livewire.experiment-records.show', [
            'batch' => $batch,
        ])->layout('layouts.app');
    }
}
