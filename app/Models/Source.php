<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function compoundSynonyms(): HasMany
    {
        return $this->hasMany(CompoundSynonym::class);
    }

    public function retentionIndices(): HasMany
    {
        return $this->hasMany(RetentionIndex::class);
    }

    public function compoundDiseaseAssociations(): HasMany
    {
        return $this->hasMany(CompoundDiseaseAssociation::class);
    }

    public function compoundOntologies(): HasMany
    {
        return $this->hasMany(CompoundOntology::class);
    }

    public function compoundBiomarkers(): HasMany
    {
        return $this->hasMany(CompoundBiomarker::class);
    }

}
