<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    public static function log(
        string $eventType,
        ?string $details = null,
        ?Model  $subject = null,
        ?string $subjectName = null,
        array   $metadata = [],
        ?int    $userId = null,
    ): ActivityLog {
        return ActivityLog::create([
            'user_id'      => $userId ?? Auth::id(),
            'event_type'   => $eventType,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->getKey(),
            'subject_name' => $subjectName ?? ($subject ? (string) ($subject->name ?? $subject->canonical_name ?? $subject->getKey()) : null),
            'details'      => $details,
            'metadata'     => $metadata ?: null,
        ]);
    }

    // ── Convenience helpers ───────────────────────────────────────────────────

    public static function createProject(Model $project): ActivityLog
    {
        return static::log('create_project', "Created project \"{$project->name}\".", $project, $project->name);
    }

    public static function editProject(Model $project, array $changes = []): ActivityLog
    {
        $detail = "Edited project \"{$project->name}\".";
        if ($changes) {
            $parts = [];
            foreach ($changes as $field => $value) {
                $parts[] = "{$field} → {$value}";
            }
            $detail .= ' Changes: ' . implode('; ', $parts) . '.';
        }
        return static::log('edit_project', $detail, $project, $project->name, $changes);
    }

    public static function deleteProject(string $projectName, ?int $projectId = null): ActivityLog
    {
        return static::log(
            'delete_project',
            "Deleted project \"{$projectName}\".",
            null,
            $projectName,
            $projectId ? ['project_id' => $projectId] : [],
        );
    }

    public static function createCompound(Model $projectCompound, Model $project): ActivityLog
    {
        $name = $projectCompound->custom_name ?? $projectCompound->compound?->canonical_name ?? "#{$projectCompound->id}";
        return static::log(
            'create_compound',
            "Added compound \"{$name}\" to project \"{$project->name}\".",
            $projectCompound,
            $name,
            ['project_id' => $project->id, 'project_name' => $project->name],
        );
    }

    public static function editCompound(Model $projectCompound, Model $project, array $changes = []): ActivityLog
    {
        $name   = $projectCompound->custom_name ?? $projectCompound->compound?->canonical_name ?? "#{$projectCompound->id}";
        $detail = "Edited compound \"{$name}\" in project \"{$project->name}\".";
        if ($changes) {
            $parts = [];
            foreach ($changes as $field => $value) {
                $parts[] = "{$field} → {$value}";
            }
            $detail .= ' Changes: ' . implode('; ', $parts) . '.';
        }
        return static::log(
            'edit_compound',
            $detail,
            $projectCompound,
            $name,
            array_merge(['project_id' => $project->id, 'project_name' => $project->name], $changes),
        );
    }

    public static function deleteCompound(string $compoundName, Model $project): ActivityLog
    {
        return static::log(
            'delete_compound',
            "Deleted compound \"{$compoundName}\" from project \"{$project->name}\".",
            null,
            $compoundName,
            ['project_id' => $project->id, 'project_name' => $project->name],
        );
    }

    public static function dumpCompounds(Model $project, int $count): ActivityLog
    {
        return static::log(
            'dump_compounds',
            "Exported {$count} compound(s) from project \"{$project->name}\".",
            $project,
            $project->name,
            ['project_id' => $project->id, 'compound_count' => $count],
        );
    }

    private static function sampleName(Model $sample): string
    {
        return $sample->lab_sample_id ?: ($sample->external_id ?: "#{$sample->id}");
    }

    public static function createSample(Model $sample): ActivityLog
    {
        $name = static::sampleName($sample);
        return static::log('create_sample', "Logged sample \"{$name}\".", $sample, $name);
    }

    public static function editSample(Model $sample, array $changes = []): ActivityLog
    {
        $name   = static::sampleName($sample);
        $detail = "Edited sample \"{$name}\".";
        if ($changes) {
            $parts = [];
            foreach ($changes as $field => $value) {
                $parts[] = "{$field} → {$value}";
            }
            $detail .= ' Changes: ' . implode('; ', $parts) . '.';
        }
        return static::log('edit_sample', $detail, $sample, $name, $changes);
    }

    public static function deleteSample(string $sampleName, ?int $sampleId = null): ActivityLog
    {
        return static::log(
            'delete_sample',
            "Deleted sample \"{$sampleName}\".",
            null,
            $sampleName,
            $sampleId ? ['sample_id' => $sampleId] : [],
        );
    }
}
