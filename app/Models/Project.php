<?php

namespace App\Models;

use App\Models\Compound;
use App\Models\ProjectCompound;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'user_id',
    ];

    /** Everyone can always view/edit/delete their own projects; the `projects.$action`
     *  permission extends that to every other user's projects too. */
    public function scopeVisibleTo(Builder $query, User $user, string $action = 'view'): Builder
    {
        if ($user->hasPermission('projects', $action)) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function projectCompounds(): HasMany
    {
        return $this->hasMany(ProjectCompound::class);
    }

    public function samples(): HasMany
    {
        return $this->hasMany(Sample::class);
    }

    public function experiments(): HasMany
    {
        return $this->hasMany(Experiment::class);
    }

    public function samplings(): HasManyThrough
    {
        return $this->hasManyThrough(Sampling::class, Sample::class);
    }

    public function compounds(): BelongsToMany
    {
        return $this->belongsToMany(Compound::class, 'project_compounds')
            ->withPivot([
                'id',
                'custom_name',
                'is_duplicate',
                'mz',
                'rt',
                'is_mapped',
                'notes',
                'created_at',
                'updated_at',
            ])
            ->withTimestamps();
    }
}
