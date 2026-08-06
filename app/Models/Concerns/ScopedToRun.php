<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Shared by ProjectCompound and ProjectMappingJob: both can optionally be tagged
 * to one specific analysis run (sample + record type + performed by + performed at,
 * mirroring ExperimentRecord::groupKey()). Plain `where` breaks once a tag is null
 * (performed_by/performed_at are legitimately nullable), hence the null-safe branches.
 */
trait ScopedToRun
{
    public function scopeForRun(Builder $query, array $tags): Builder
    {
        return $query
            ->where('experiment_id', $tags['experiment_id'])
            ->where('sample_id', $tags['sample_id'])
            ->where('record_type', $tags['record_type'])
            ->when(
                $tags['performed_by'] === null,
                fn (Builder $q) => $q->whereNull('performed_by'),
                fn (Builder $q) => $q->where('performed_by', $tags['performed_by']),
            )
            ->when(
                $tags['performed_at'] === null,
                fn (Builder $q) => $q->whereNull('performed_at'),
                fn (Builder $q) => $q->whereDate('performed_at', $tags['performed_at']),
            );
    }
}
