<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Experiment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'status',
        'started_at',
        'completed_at',
        'created_by',
    ];

    /** Everyone can always view/edit/delete experiments they created or that belong to a
     *  project they own; the `experiments.$action` permission extends that to every other experiment. */
    public function scopeVisibleTo(Builder $query, User $user, string $action = 'view'): Builder
    {
        if ($user->hasPermission('experiments', $action)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('created_by', $user->id)
                ->orWhereHas('project', fn (Builder $p) => $p->where('user_id', $user->id));
        });
    }

    protected $casts = [
        'started_at' => 'date',
        'completed_at' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function records(): HasMany
    {
        return $this->hasMany(ExperimentRecord::class);
    }

    public function samples()
    {
        return Sample::query()
            ->whereIn('id', $this->records()->select('sample_id'))
            ->get();
    }

    public function statusLabel(): string
    {
        return OptionList::optionsFor('experiment_statuses')[$this->status] ?? $this->status;
    }
}
