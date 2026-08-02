<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExperimentRecordRequest;
use App\Http\Requests\UpdateExperimentRecordRequest;
use App\Http\Resources\ExperimentRecordResource;
use App\Models\Experiment;
use App\Models\ExperimentRecord;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExperimentRecordController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Experiment $experiment)
    {
        $records = $experiment->records()
            ->with(['sample', 'experiment'])
            ->paginate(100);

        return ExperimentRecordResource::collection($records);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreExperimentRecordRequest $request, Experiment $experiment): ExperimentRecordResource
    {
        $record = $experiment->records()->create($request->validated());

        return new ExperimentRecordResource($record->load(['sample', 'experiment']));
    }

    /**
     * Display the specified resource.
     */
    public function show(ExperimentRecord $experimentRecord): ExperimentRecordResource
    {
        return new ExperimentRecordResource($experimentRecord->load(['sample', 'experiment']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExperimentRecordRequest $request, ExperimentRecord $experimentRecord): ExperimentRecordResource
    {
        $experimentRecord->update($request->validated());

        return new ExperimentRecordResource($experimentRecord->load(['sample', 'experiment']));
    }

    /**
     * All experiment_records for a project, filtered by record_type, with
     * sample fields already joined in — the single call every analysis
     * pipeline's extract() builds on.
     */
    public function indexForProject(Project $project, Request $request)
    {
        $request->validate([
            'record_type' => ['required', 'string', Rule::in(array_keys(ExperimentRecord::RECORD_TYPES))],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:500'],
        ]);

        // record_type must be AND'd against the whole project-membership
        // check, and project-membership is an OR across the two possible
        // attachment paths (sample.project_id / experiment.project_id are
        // both nullable, so either can independently place a record in this
        // project). Wrapping the two whereHas calls in their own closure
        // keeps that grouping — unwrapped, Eloquent would OR the entire
        // preceding predicate chain with the second whereHas instead.
        $records = ExperimentRecord::query()
            ->where('record_type', $request->string('record_type')->toString())
            ->where(function ($query) use ($project) {
                $query->whereHas('experiment', fn ($q) => $q->where('project_id', $project->id))
                    ->orWhereHas('sample', fn ($q) => $q->where('project_id', $project->id));
            })
            ->with(['sample', 'experiment'])
            ->orderBy('id')
            ->paginate($request->integer('per_page', 100));

        return ExperimentRecordResource::collection($records);
    }
}
