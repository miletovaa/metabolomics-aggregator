<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetentionIndex extends Model
{
    use HasFactory;

    protected $fillable = [
        'compound_id',
        'value',
        'column_type',
        'is_polar',
        'reference',
        'source_id',
    ];

    protected $casts = [
        'value' => 'decimal:10',
        'is_polar' => 'boolean',
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
