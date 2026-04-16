<?php

use App\Http\Controllers\Api\BiomarkerController;
use App\Http\Controllers\Api\CompoundController;
use App\Http\Controllers\Api\DiseaseController;
use App\Http\Controllers\Api\OntologyController;
use App\Http\Controllers\Api\ProjectCompoundController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\SourceController;
use Illuminate\Support\Facades\Route;

Route::apiResource('compounds', CompoundController::class);
Route::apiResource('projects', ProjectController::class);
Route::apiResource('project-compounds', ProjectCompoundController::class)->except(['index', 'store']);

Route::get('projects/{project}/compounds', [ProjectCompoundController::class, 'index']);
Route::post('projects/{project}/compounds', [ProjectCompoundController::class, 'store']);

Route::get('sources', [SourceController::class, 'index']);
Route::get('sources/{source}', [SourceController::class, 'show']);

Route::get('diseases', [DiseaseController::class, 'index']);
Route::get('diseases/{disease}', [DiseaseController::class, 'show']);

Route::get('ontologies', [OntologyController::class, 'index']);
Route::get('ontologies/{ontology}', [OntologyController::class, 'show']);

Route::get('biomarkers', [BiomarkerController::class, 'index']);
Route::get('biomarkers/{biomarker}', [BiomarkerController::class, 'show']);