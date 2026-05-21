<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMappingJob extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'status',
        'log',
        'started_at',
        'completed_at',
        'read_at',
    ];

    protected $casts = [
        'log'          => 'array',
        'started_at'   => 'datetime',
        'completed_at' => 'datetime',
        'read_at'      => 'datetime',
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
}
