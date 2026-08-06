<?php

namespace App\Models;

use App\Models\Concerns\ScopedToRun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectCompound extends Model
{
    use HasFactory;
    use ScopedToRun;

    protected $fillable = [
        'project_id',
        'compound_id',
        'input_name',
        'custom_name',
        'is_duplicate',
        'is_terpene',
        'terpene_type',
        'mz',
        'rt',
        'is_mapped',
        'notes',
        'custom_taxonomy',
        'experiment_id',
        'sample_id',
        'record_type',
        'performed_by',
        'performed_at',
        'parent_record_id',
        'value',
        'unit',
        'stdev',
    ];

    protected $casts = [
        'is_duplicate' => 'boolean',
        'is_terpene'   => 'boolean',
        'is_mapped'    => 'boolean',
        'mz'           => 'decimal:10',
        'rt'           => 'decimal:10',
        'performed_at' => 'date',
        'value'        => 'decimal:10',
        'stdev'        => 'decimal:10',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function projectCompounds(): BelongsTo
    {
        return $this->belongsTo(ProjectCompound::class);
    }

    public function compound(): BelongsTo
    {
        return $this->belongsTo(Compound::class);
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function parentRecord(): BelongsTo
    {
        return $this->belongsTo(ExperimentRecord::class, 'parent_record_id');
    }

    /** Composite key identifying "the same analysis run" — mirrors ExperimentRecord::groupKey(). */
    public function runGroupKey(): string
    {
        return implode('|', [
            $this->sample_id,
            $this->record_type,
            $this->performed_at?->toDateString(),
            $this->performed_by,
        ]);
    }

    public function recordTypeLabel(): ?string
    {
        return $this->record_type ? (ExperimentRecord::RECORD_TYPES[$this->record_type] ?? $this->record_type) : null;
    }
}
