<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CompoundController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Compound::query()->with([
            'synonyms.source',
            'retentionIndices.source',
            'taxonomy',
            'diseases',
            'ontologies',
            'biomarkers',
        ]);

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());

            $query->where(function ($q) use ($search) {
                $q->where('canonical_name', 'like', "%{$search}%")
                    ->orWhere('iupac_name', 'like', "%{$search}%")
                    ->orWhere('inchikey', 'like', "%{$search}%")
                    ->orWhere('hmdb_id', 'like', "%{$search}%")
                    ->orWhere('chebi_id', 'like', "%{$search}%")
                    ->orWhereHas('synonyms', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $compounds = $query->paginate(20);

        return CompoundResource::collection($compounds);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompoundRequest $request): CompoundResource
    {
        $compound = Compound::create($request->validated());

        return new CompoundResource($compound->load([
            'synonyms.source',
            'retentionIndices.source',
            'taxonomy',
            'diseases',
            'ontologies',
            'biomarkers',
        ]));
    }

    /**
     * Display the specified resource.
     */
    public function show(Compound $compound): CompoundResource
    {
        $compound->load([
            'synonyms.source',
            'retentionIndices.source',
            'taxonomy',
            'diseases',
            'ontologies',
            'biomarkers',
        ]);

        return new CompoundResource($compound);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompoundRequest $request, Compound $compound): CompoundResource
    {
        $compound->update($request->validated());

        return new CompoundResource($compound->load([
            'synonyms.source',
            'retentionIndices.source',
            'taxonomy',
            'diseases',
            'ontologies',
            'biomarkers',
        ]));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Compound $compound): JsonResponse
    {
        $compound->delete();

        return response()->json([
            'message' => 'Compound deleted successfully.',
        ]);
    }
}
