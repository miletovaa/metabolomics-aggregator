<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BiomarkerController;
use App\Http\Controllers\CompoundController;
use App\Http\Controllers\DiseaseController;
use App\Http\Controllers\ExperimentRecordController;
use App\Http\Controllers\OntologyController;
use App\Http\Controllers\ProjectCompoundController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RecordTypeSchemaController;
use App\Http\Controllers\SampleController;
use App\Http\Controllers\SourceController;
use Illuminate\Support\Facades\Route;


Route::name('api.')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');

        Route::get('compounds', [CompoundController::class, 'index'])->name('compounds.search');
        Route::post('compounds', [CompoundController::class, 'store'])->name('compounds.store');
        Route::get('compounds/{compound}', [CompoundController::class, 'show'])->name('compounds.show');
        Route::put('compounds/{compound}', [CompoundController::class, 'update'])->name('compounds.update');

        Route::get('projects', [ProjectController::class, 'index']);
        Route::get('projects/{project}/compounds', [ProjectCompoundController::class, 'index']);
        Route::post('projects/{project}/compounds', [ProjectCompoundController::class, 'store']);
        Route::get('project-compounds/{projectCompound}', [ProjectCompoundController::class, 'show']);
        Route::put('project-compounds/{projectCompound}', [ProjectCompoundController::class, 'update']);

        Route::get('projects/{project}/samples', [SampleController::class, 'index']);
        Route::post('projects/{project}/samples', [SampleController::class, 'store']);
        Route::get('samples/{sample}', [SampleController::class, 'show']);
        Route::put('samples/{sample}', [SampleController::class, 'update']);

        Route::get('experiments/{experiment}/records', [ExperimentRecordController::class, 'index']);
        Route::post('experiments/{experiment}/records', [ExperimentRecordController::class, 'store']);
        Route::get('experiment-records/{experimentRecord}', [ExperimentRecordController::class, 'show']);
        Route::put('experiment-records/{experimentRecord}', [ExperimentRecordController::class, 'update']);
        Route::get('projects/{project}/experiment-records', [ExperimentRecordController::class, 'indexForProject']);

        Route::get('record-types/{recordType}/schema', [RecordTypeSchemaController::class, 'show']);

        Route::get('sources', [SourceController::class, 'index']);
        Route::get('sources/{source}', [SourceController::class, 'show']);

        Route::get('diseases', [DiseaseController::class, 'index']);
        Route::get('diseases/{disease}', [DiseaseController::class, 'show']);

        Route::get('ontologies', [OntologyController::class, 'index']);
        Route::get('ontologies/{ontology}', [OntologyController::class, 'show']);

        Route::get('biomarkers', [BiomarkerController::class, 'index']);
        Route::get('biomarkers/{biomarker}', [BiomarkerController::class, 'show']);
    });
});
