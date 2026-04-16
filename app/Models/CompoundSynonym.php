<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompoundSynonym extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'compound_id',
        'source_id',
    ];

    public function compound(): BelongsTo
    {
        return $this->belongsTo(Compound::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }
}
