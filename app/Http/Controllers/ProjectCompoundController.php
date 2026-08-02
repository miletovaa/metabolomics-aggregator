<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectCompoundRequest;
use App\Http\Requests\UpdateProjectCompoundRequest;
use App\Http\Resources\ProjectCompoundResource;
use App\Models\Project;
use App\Models\ProjectCompound;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectCompoundController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Project $project)
    {
        $projectCompounds = $project->projectCompounds()
            ->with([
                'compound.taxonomy',
                'compound.synonyms.source',
            ])
            ->paginate(20);

        return ProjectCompoundResource::collection($projectCompounds);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectCompoundRequest $request, Project $project): ProjectCompoundResource
    {
        $projectCompound = $project->projectCompounds()->create($request->validated());

        return new ProjectCompoundResource(
            $projectCompound->load([
                'compound.taxonomy',
                'compound.synonyms.source',
            ])
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(ProjectCompound $projectCompound): ProjectCompoundResource
    {
        return new ProjectCompoundResource(
            $projectCompound->load([
                'compound.taxonomy',
                'compound.synonyms.source',
            ])
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectCompoundRequest $request, ProjectCompound $projectCompound): ProjectCompoundResource
    {
        $projectCompound->update($request->validated());

        return new ProjectCompoundResource(
            $projectCompound->load([
                'compound.taxonomy',
                'compound.synonyms.source',
            ])
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectCompound $projectCompound): JsonResponse
    {
        $projectCompound->delete();

        return response()->json([
            'message' => 'Project compound deleted successfully.',
        ]);
    }
}
