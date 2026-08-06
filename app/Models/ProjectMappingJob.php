<?php

namespace App\Models;

use App\Models\Concerns\ScopedToRun;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMappingJob extends Model
{
    use ScopedToRun;

    protected $fillable = [
        'project_id',
        'user_id',
        'status',
        'log',
        'started_at',
        'completed_at',
        'read_at',
        'experiment_id',
        'sample_id',
        'record_type',
        'performed_by',
        'performed_at',
    ];

    protected $casts = [
        'log'          => 'array',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'read_at'      => 'datetime',
        'performed_at' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /** Null when this job is an ordinary project-wide mapping run (not tied to one analysis run). */
    public function runScopeTags(): ?array
    {
        if ($this->experiment_id === null) {
            return null;
        }

        return [
            'experiment_id' => $this->experiment_id,
            'sample_id'     => $this->sample_id,
            'record_type'   => $this->record_type,
            'performed_by'  => $this->performed_by,
            'performed_at'  => $this->performed_at?->toDateString(),
        ];
    }
}
