<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'event_type',
        'subject_type',
        'subject_id',
        'subject_name',
        'details',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): ?\Illuminate\Database\Eloquent\Model
    {
        if ($this->subject_type && $this->subject_id) {
            return $this->subject_type::find($this->subject_id);
        }
        return null;
    }

    public function eventLabel(): string
    {
        return match ($this->event_type) {
            'create_project'   => 'Created project',
            'edit_project'     => 'Edited project',
            'delete_project'   => 'Deleted project',
            'create_compound'  => 'Added compound',
            'edit_compound'    => 'Edited compound',
            'delete_compound'  => 'Deleted compound',
            'dump_compounds'   => 'Exported compounds',
            default            => ucfirst(str_replace('_', ' ', $this->event_type)),
        };
    }

    public function eventColor(): string
    {
        return match ($this->event_type) {
            'create_project', 'create_compound' => 'green',
            'edit_project',   'edit_compound'   => 'blue',
            'delete_project', 'delete_compound' => 'red',
            'dump_compounds'                    => 'purple',
            default                             => 'gray',
        };
    }
}
