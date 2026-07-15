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
            'create_sample'    => 'Logged sample',
            'edit_sample'      => 'Edited sample',
            'delete_sample'    => 'Deleted sample',
            'create_sampling'  => 'Logged sampling',
            'edit_sampling'    => 'Edited sampling',
            'delete_sampling'  => 'Deleted sampling',
            'create_experiment' => 'Created experiment',
            'edit_experiment'   => 'Edited experiment',
            'delete_experiment' => 'Deleted experiment',
            'create_experiment_record' => 'Added experiment record',
            'delete_experiment_record' => 'Deleted experiment record',
            default            => ucfirst(str_replace('_', ' ', $this->event_type)),
        };
    }

    public function eventColor(): string
    {
        return match ($this->event_type) {
            'create_project', 'create_compound', 'create_sample', 'create_sampling', 'create_experiment', 'create_experiment_record' => 'green',
            'edit_project',   'edit_compound',   'edit_sample',   'edit_sampling',   'edit_experiment'                               => 'blue',
            'delete_project', 'delete_compound', 'delete_sample', 'delete_sampling', 'delete_experiment', 'delete_experiment_record' => 'red',
            'dump_compounds'                                                                                                          => 'purple',
            default                                                                                                                    => 'gray',
        };
    }
}
