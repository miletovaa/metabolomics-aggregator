<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Disease extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'category',
    ];

    public function compoundAssociations(): HasMany
    {
        return $this->hasMany(CompoundDiseaseAssociation::class);
    }
    
    public function compounds(): BelongsToMany
    {
        return $this->belongsToMany(Compound::class, 'compound_disease_associations')
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
