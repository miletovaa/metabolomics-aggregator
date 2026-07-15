<?php

namespace App\Livewire\Samplings;

use App\Models\Sample;
use App\Models\Sampling;
use App\Services\ActivityLogger;
use Livewire\Component;

class Create extends Component
{
    public ?int $sampleId = null;
    public string $dateOfSampling = '';
    public string $countryOfSampling = '';
    public string $placeOfSampling = '';
    public string $gerk = '';
    public string $gpsLat = '';
    public string $gpsLon = '';
    public string $altitude = '';
    public string $samplingMethod = '';
    public string $packaging = '';
    public string $collector = '';

    public function save(): void
    {
        $this->validate([
            'sampleId' => ['required', 'exists:samples,id', 'unique:samplings,sample_id'],
            'dateOfSampling' => ['nullable', 'date'],
            'gpsLat' => ['nullable', 'numeric', 'between:-90,90'],
            'gpsLon' => ['nullable', 'numeric', 'between:-180,180'],
            'altitude' => ['nullable', 'numeric'],
            'samplingMethod' => ['nullable', 'in:' . implode(',', array_keys(Sampling::SAMPLING_METHODS))],
            'packaging' => ['nullable', 'in:' . implode(',', array_keys(Sampling::PACKAGING_OPTIONS))],
        ]);

        $sampling = Sampling::create([
            'sample_id' => $this->sampleId,
            'date_of_sampling' => $this->dateOfSampling ?: null,
            'country_of_sampling' => $this->countryOfSampling ?: null,
            'place_of_sampling' => $this->placeOfSampling ?: null,
            'gerk' => $this->gerk ?: null,
            'gps_lat' => $this->gpsLat !== '' ? $this->gpsLat : null,
            'gps_lon' => $this->gpsLon !== '' ? $this->gpsLon : null,
            'altitude' => $this->altitude !== '' ? $this->altitude : null,
            'sampling_method' => $this->samplingMethod ?: null,
            'packaging' => $this->packaging ?: null,
            'collector' => $this->collector ?: null,
        ]);

        ActivityLogger::createSampling($sampling);

        session()->flash('success', 'Sampling logged.');

        $this->redirect(route('samplings.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.samplings.create', [
            'availableSamples' => Sample::query()
                ->whereDoesntHave('sampling')
                ->orderByDesc('id')
                ->get(['id', 'lab_sample_id', 'external_id']),
        ])->layout('layouts.app');
    }
}
