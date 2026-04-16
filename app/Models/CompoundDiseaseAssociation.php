<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompoundDiseaseAssociation extends Model
{
    use HasFactory;

    protected $fillable = [
        'compound_id',
        'disease_id',
        'reference',
        'source_id',
    ];

    public function compound(): BelongsTo
    {
        return $this->belongsTo(Compound::class);
    }

    public function disease(): BelongsTo
    {
        return $this->belongsTo(Disease::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
