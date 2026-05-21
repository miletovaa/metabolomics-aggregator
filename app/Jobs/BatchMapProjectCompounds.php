<?php

namespace App\Jobs;

use App\Models\Compound;
use App\Models\ProjectMappingJob;
use App\Services\CompoundMappingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BatchMapProjectCompounds implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int  $timeout       = 7200;
    public int  $tries         = 1;
    public bool $failOnTimeout = true;

    public function __construct(public readonly int $mappingJobId) {}

    public function handle(CompoundMappingService $service): void
    {
        $mappingJob = ProjectMappingJob::findOrFail($this->mappingJobId);
        $mappingJob->update(['status' => 'running', 'started_at' => now()]);

        $project = $mappingJob->project;
        $rows    = $project->projectCompounds()->whereNull('compound_id')->get();
        $total   = $rows->count();

        if ($total === 0) {
            $mappingJob->update([
                'status'       => 'done',
                'completed_at' => now(),
                'log'          => ['ran_at' => now()->format('Y-m-d H:i:s'), 'total' => 0, 'local_found' => 0, 'pubchem_new' => 0, 'not_found' => 0],
            ]);
            return;
        }

        $watermark  = (int) (Compound::max('id') ?? 0);
        $localFound = 0;
        $pubchemNew = 0;
        $notFound   = 0;

        foreach ($rows as $row) {
            if (!trim($row->custom_name ?? '')) {
                $notFound++;
                continue;
            }

            try {
                $mapped = $service->mapProjectCompound($row);

                if ($mapped) {
                    $row->refresh();
                    if ($row->compound_id) {
                        $compound  = $row->compound()->with('taxonomy')->first();
                        $isTerpene = $this->computeIsTerpene($compound);
                        $row->update([
                            'is_terpene'   => $isTerpene,
                            'terpene_type' => $isTerpene ? $this->computeTerpeneType($compound) : null,
                        ]);

                        if ($row->compound_id > $watermark) {
                            $pubchemNew++;
                        } else {
                            $localFound++;
                        }
                    } else {
                        $notFound++;
                    }
                } else {
                    $notFound++;
                }
            } catch (\Throwable $e) {
                Log::error('BatchMapProjectCompounds: row failed', [
                    'project_compound_id' => $row->id,
                    'error'               => $e->getMessage(),
                ]);
                $notFound++;
            }
        }

        $this->detectDuplicates($project);

        $mappingJob->update([
            'status'       => 'done',
            'completed_at' => now(),
            'log'          => [
                'ran_at'      => now()->format('Y-m-d H:i:s'),
                'total'       => $total,
                'local_found' => $localFound,
                'pubchem_new' => $pubchemNew,
                'not_found'   => $notFound,
            ],
        ]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('BatchMapProjectCompounds: job failed', [
            'mapping_job_id' => $this->mappingJobId,
            'error'          => $e->getMessage(),
        ]);

        ProjectMappingJob::where('id', $this->mappingJobId)
            ->update(['status' => 'failed', 'completed_at' => now()]);
    }

    private function computeIsTerpene(\App\Models\Compound $compound): bool
    {
        $raw = $compound->taxonomy?->raw_json ?? '';
        return stripos((string) $raw, 'terpen') !== false;
    }

    private function computeTerpeneType(\App\Models\Compound $compound): ?string
    {
        $tax = $compound->taxonomy;

        $fields = implode(' ', array_filter([
            $tax?->superclass,
            $tax?->{'class'},
            $tax?->subclass,
            $tax?->direct_parent,
            $tax?->alternative_parents,
        ]));

        if (preg_match('/\bMonoterpene\b/i', $fields)) return 'Monoterpene';
        if (preg_match('/\bSesquiterpene\b/i', $fields)) return 'Sesquiterpene';
        if (preg_match('/\bDiterpene\b/i', $fields)) return 'Diterpene';

        return null;
    }

    private function detectDuplicates(\App\Models\Project $project): void
    {
        $duplicateCids = $project->projectCompounds()
            ->whereNotNull('compound_id')
            ->select('compound_id')
            ->groupBy('compound_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('compound_id');

        if ($duplicateCids->isEmpty()) {
            $project->projectCompounds()->where('is_duplicate', true)->update(['is_duplicate' => false]);
            return;
        }

        $project->projectCompounds()->whereIn('compound_id', $duplicateCids)->update(['is_duplicate' => true]);
        $project->projectCompounds()
            ->where(fn($q) => $q->whereNull('compound_id')->orWhereNotIn('compound_id', $duplicateCids))
            ->update(['is_duplicate' => false]);
    }
}
