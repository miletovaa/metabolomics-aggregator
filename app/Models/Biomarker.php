<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Biomarker extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function compoundLinks(): HasMany
    {
        return $this->hasMany(CompoundBiomarker::class);
    }
       
    public function compounds(): BelongsToMany
    {
        return $this->belongsToMany(Compound::class, 'compound_biomarkers')
            ->withPivot([
                'id',
                'reference',
                'source_id',
                'created_at',
                'updated_at',
            ])
            ->withTimestamps();
    }
}
