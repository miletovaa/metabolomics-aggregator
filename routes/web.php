<?php

use App\Livewire\ActivityLog\Index as ActivityLogIndex;
use App\Livewire\Compounds\Index as CompoundsIndex;
use App\Livewire\Dashboard\Index as DashboardIndex;
use App\Livewire\Experiments\Index as ExperimentsIndex;
use App\Livewire\Projects\Index as ProjectsIndex;
use App\Livewire\Projects\Create as ProjectsCreate;
use App\Livewire\Projects\Show as ProjectsShow;
use App\Livewire\Samples\Create as SamplesCreate;
use App\Livewire\Samples\Edit as SamplesEdit;
use App\Livewire\Samples\Index as SamplesIndex;
use App\Livewire\Samplings\Index as SamplingsIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardIndex::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth'])->group(function () {
    Route::get('/compounds', CompoundsIndex::class)->name('compounds.index');
    Route::get('/samplings', SamplingsIndex::class)->name('samplings.index');

    Route::get('/projects', ProjectsIndex::class)->name('projects.index');
    Route::get('/projects/create', ProjectsCreate::class)->name('projects.create');
    Route::get('/projects/{project}', ProjectsShow::class)->name('projects.show');

    Route::get('/experiments', ExperimentsIndex::class)->name('experiments.index');

    Route::get('/samples', SamplesIndex::class)->name('samples.index');
    Route::get('/samples/create', SamplesCreate::class)->name('samples.create');
    Route::get('/samples/{sample}/edit', SamplesEdit::class)->name('samples.edit');

    Route::get('/activity-log', ActivityLogIndex::class)->name('activity-log.index');
});

require __DIR__.'/auth.php';
