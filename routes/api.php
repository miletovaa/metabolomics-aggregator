<?php

use App\Http\Controllers\BiomarkerController;
use App\Http\Controllers\CompoundController;
use App\Http\Controllers\DiseaseController;
use App\Http\Controllers\OntologyController;
use App\Http\Controllers\ProjectCompoundController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SourceController;
use Illuminate\Support\Facades\Route;


Route::name('api.')->group(function () {
    Route::get('compounds', [CompoundController::class, 'index'])->name('compounds.search');
    Route::get('compounds/{compound}', [CompoundController::class, 'show'])->name('compounds.show');

    Route::get('projects', [ProjectController::class, 'index']);
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
});
