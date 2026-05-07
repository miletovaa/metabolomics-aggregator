<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Taxonomy extends Model
{
    use HasFactory;

    protected $fillable = [
        'compound_id',
        'kingdom',
        'superclass',
        'class',
        'subclass',
        'direct_parent',
        'alternative_parents',
        'raw_json',
    ];

    protected $casts = [
        'raw_json' => 'array',
    ];

    public function compound(): BelongsTo
    {
        return $this->belongsTo(Compound::class);
    }
}
