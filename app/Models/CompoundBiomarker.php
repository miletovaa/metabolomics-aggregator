<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompoundBiomarker extends Model
{
    use HasFactory;

    protected $fillable = [
        'compound_id',
        'biomarker_id',
        'reference',
        'source_id',
    ];

    public function compound(): BelongsTo
    {
        return $this->belongsTo(Compound::class);
    }

    public function biomarker(): BelongsTo
    {
        return $this->belongsTo(Biomarker::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
