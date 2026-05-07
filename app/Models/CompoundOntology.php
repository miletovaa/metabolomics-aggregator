<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompoundOntology extends Model
{
    use HasFactory;

    protected $fillable = [
        'compound_id',
        'ontology_id',
        'reference',
        'source_id',
    ];

    public function compound(): BelongsTo
    {
        return $this->belongsTo(Compound::class);
    }

    public function ontology(): BelongsTo
    {
        return $this->belongsTo(Ontology::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
