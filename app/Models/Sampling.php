<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sampling extends Model
{
    use HasFactory;

    protected $fillable = [
        'sample_id',
        'date_of_sampling',
        'country_of_sampling',
        'place_of_sampling',
        'gerk',
        'gps_lat',
        'gps_lon',
        'altitude',
        'sampling_method',
        'packaging',
        'collector',
    ];

    /** A sampling has no owner of its own — visibility is inherited from its sample. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('sample', fn (Builder $q) => $q->visibleTo($user));
    }

    protected $casts = [
        'date_of_sampling' => 'date',
        'gps_lat' => 'decimal:6',
        'gps_lon' => 'decimal:6',
        'altitude' => 'decimal:2',
    ];

    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class);
    }

    public function samplingMethodLabel(): ?string
    {
        return OptionList::optionsFor('sampling_methods')[$this->sampling_method] ?? $this->sampling_method;
    }

    public function packagingLabel(): ?string
    {
        return OptionList::optionsFor('packaging_options')[$this->packaging] ?? $this->packaging;
    }
}
