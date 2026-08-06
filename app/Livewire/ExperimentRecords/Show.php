<?php

namespace App\Livewire\ExperimentRecords;

use App\Models\Experiment;
use App\Models\ExperimentRecord;
use App\Models\User;
use Livewire\Component;

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

    public function render()
    {
        $fields = collect(ExperimentRecord::fieldSchema($this->record->record_type))
            ->map(fn (array $field) => [
                'label' => $field['label'],
                'type'  => $field['type'],
                'value' => $this->formatFieldValue($field, $this->record->details[$field['key']] ?? null),
            ])
            ->filter(fn (array $f) => $f['value'] !== null)
            ->values();

        return view('livewire.experiment-records.show', [
            'fields' => $fields,
        ])->layout('layouts.app');
    }
}
