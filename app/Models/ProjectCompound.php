<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectCompound extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'compound_id',
        'input_name',
        'custom_name',
        'is_duplicate',
        'is_terpene',
        'terpene_type',
        'mz',
        'rt',
        'is_mapped',
        'notes',
        'custom_taxonomy',
    ];

    protected $casts = [
        'is_duplicate' => 'boolean',
        'is_terpene'   => 'boolean',
        'is_mapped'    => 'boolean',
        'mz'           => 'decimal:10',
        'rt'           => 'decimal:10',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function projectCompounds(): BelongsTo
    {
        return $this->belongsTo(ProjectCompound::class);
    }

    public function compound(): BelongsTo
    {
        return $this->belongsTo(Compound::class);
    }
}
