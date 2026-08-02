<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSampleRequest;
use App\Http\Requests\UpdateSampleRequest;
use App\Http\Resources\SampleResource;
use App\Models\Project;
use App\Models\Sample;

class SampleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Project $project)
    {
        $samples = $project->samples()
            ->with('responsibleAnalyst')
            ->paginate(50);

        return SampleResource::collection($samples);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSampleRequest $request, Project $project): SampleResource
    {
        $sample = $project->samples()->create($request->validated());

        return new SampleResource($sample->load('responsibleAnalyst'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Sample $sample): SampleResource
    {
        return new SampleResource($sample->load('responsibleAnalyst'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSampleRequest $request, Sample $sample): SampleResource
    {
        $sample->update($request->validated());

        return new SampleResource($sample->load('responsibleAnalyst'));
    }
}
