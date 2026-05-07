<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\CompoundMappingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncProjectCompoundsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Allow up to 2 hours – thousands of compounds each need HTTP calls.
     */
    public int  $timeout         = 7200;
    public int  $tries           = 1;
    public bool $failOnTimeout   = true;

    public function __construct(public readonly Project $project) {}

    // ─────────────────────────────────────────────────────────────────────────

    public function handle(CompoundMappingService $service): void
    {
        $rows = $this->project
            ->projectCompounds()
            ->whereNull('compound_id')
            ->orderBy('id')
            ->get();

        $total  = $rows->count();
        $mapped = 0;
        $failed = 0;

        $this->writeProgress('running', 0, $total, 'Starting…', $mapped, $failed);

        foreach ($rows as $index => $row) {
            try {
                if ($service->mapProjectCompound($row)) {
                    $mapped++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::error('CompoundSync failed', [
                    'project_id'  => $this->project->id,
                    'row_id'      => $row->id,
                    'custom_name' => $row->custom_name,
                    'error'       => $e->getMessage(),
                ]);
            }

            $this->writeProgress(
                'running',
                $index + 1,
                $total,
                $row->custom_name ?? '',
                $mapped,
                $failed,
            );
        }

        $this->writeProgress('done', $total, $total, '', $mapped, $failed);
    }

    // ─────────────────────────────────────────────────────────────────────────

    public function failed(\Throwable $e): void
    {
        $this->writeProgress('error', 0, 0, $e->getMessage(), 0, 0);
        Log::error('SyncProjectCompoundsJob crashed', [
            'project_id' => $this->project->id,
            'error'      => $e->getMessage(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────

    public static function cacheKey(int|Project $project): string
    {
        $id = $project instanceof Project ? $project->id : $project;
        return "sync_compounds_{$id}";
    }

    private function writeProgress(
        string $status,
        int    $processed,
        int    $total,
        string $current,
        int    $mapped,
        int    $failed,
    ): void {
        Cache::put(
            self::cacheKey($this->project),
            compact('status', 'processed', 'total', 'current', 'mapped', 'failed'),
            now()->addHours(3)
        );
    }
}