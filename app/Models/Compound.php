<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Compound extends Model
{
    use HasFactory;

    protected $fillable = [
        'canonical_name',
        'iupac_name',
        'molecular_formula',
        'smiles',
        'inchi',
        'inchikey',
        'pubchem_cid',
        'cas',
        'hmdb_id',
        'chebi_id',
    ];

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_compounds')
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

    public function synonyms(): HasMany
    {
        return $this->hasMany(CompoundSynonym::class);
    }

    public function retentionIndices(): HasMany
    {
        return $this->hasMany(RetentionIndex::class);
    }

    public function diseases(): BelongsToMany
    {
        return $this->belongsToMany(Disease::class, 'compound_disease_associations')
            ->withPivot(['reference', 'source_id'])
            ->withTimestamps();
    }

    public function ontologies(): BelongsToMany
    {
        return $this->belongsToMany(Ontology::class, 'compound_ontologies')
            ->withPivot([
                'id',
                'reference',
                'source_id',
                'created_at',
                'updated_at',
            ])
            ->withTimestamps();
    }

    public function biomarkers(): BelongsToMany
    {
        return $this->belongsToMany(Biomarker::class, 'compound_biomarkers')
            ->withPivot([
                'id',
                'reference',
                'source_id',
                'created_at',
                'updated_at',
            ])
            ->withTimestamps();
    }

    public function taxonomy(): HasOne
    {
        return $this->hasOne(Taxonomy::class);
    }
}
