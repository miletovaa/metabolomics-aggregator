<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectCompound extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'compound_id',
        'custom_name',
        'is_duplicate',
        'mz',
        'rt',
        'is_mapped',
        'notes',
    ];

    protected $casts = [
        'is_duplicate' => 'boolean',
        'is_mapped' => 'boolean',
        'mz' => 'decimal:10',
        'rt' => 'decimal:10',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function compound(): BelongsTo
    {
        return $this->belongsTo(Compound::class);
    }
}
