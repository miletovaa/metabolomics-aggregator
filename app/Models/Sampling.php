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

    public const SAMPLING_METHODS = [
        'official_sampling' => 'Official sampling',
        'producer_sampling' => 'Producer sampling',
        'self_sampling' => 'Self sampling',
        'manual_picking' => 'Manual picking',
        'instrument_sampling' => 'Instrument sampling',
        'composite_sampling' => 'Composite sampling',
        'spot_sampling' => 'Spot sampling',
        'systematic_sampling' => 'Systematic sampling',
        'excavation' => 'Excavation',
        'swab' => 'Swab',
        'core_sampling' => 'Core sampling',
        'active_sampling_pump' => 'Active sampling (pump)',
        'passive_sampling' => 'Passive sampling',
        'continuous_sampling' => 'Continuous sampling',
    ];

    public const PACKAGING_OPTIONS = [
        'plastic_bags' => 'Plastic bags',
        'glass_bottles' => 'Glass bottles',
        'metal_cans' => 'Metal cans',
        'cardboard_boxes' => 'Cardboard boxes',
        'vacuum_sealed_bags' => 'Vacuum-sealed bags',
        'refrigerated_boxes' => 'Refrigerated boxes',
        'sterile_containers' => 'Sterile containers',
        'amber_glass_bottles' => 'Amber glass bottles',
    ];

    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class);
    }

    public function samplingMethodLabel(): ?string
    {
        return self::SAMPLING_METHODS[$this->sampling_method] ?? $this->sampling_method;
    }

    public function packagingLabel(): ?string
    {
        return self::PACKAGING_OPTIONS[$this->packaging] ?? $this->packaging;
    }
}
