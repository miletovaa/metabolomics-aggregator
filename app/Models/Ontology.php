<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ontology extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
    ];

    public function compoundLinks(): HasMany
    {
        return $this->hasMany(CompoundOntology::class);
    }

    public function compounds(): BelongsToMany
    {
        return $this->belongsToMany(Compound::class, 'compound_ontologies')
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
