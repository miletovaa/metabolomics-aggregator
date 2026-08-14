<?php

namespace App\Livewire\Samplings;

use App\Models\OptionList;
use App\Models\Sampling;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Edit extends Component
{
    public Sampling $sampling;

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

    public function mount(Sampling $sampling): void
    {
        abort_unless(Sampling::visibleTo(Auth::user(), 'edit')->whereKey($sampling->id)->exists(), 404);

        $this->sampling = $sampling;

        $this->dateOfSampling = $sampling->date_of_sampling?->format('Y-m-d') ?? '';
        $this->countryOfSampling = (string) $sampling->country_of_sampling;
        $this->placeOfSampling = (string) $sampling->place_of_sampling;
        $this->gerk = (string) $sampling->gerk;
        $this->gpsLat = (string) $sampling->gps_lat;
        $this->gpsLon = (string) $sampling->gps_lon;
        $this->altitude = (string) $sampling->altitude;
        $this->samplingMethod = (string) $sampling->sampling_method;
        $this->packaging = (string) $sampling->packaging;
        $this->collector = (string) $sampling->collector;
    }

    public function save(): void
    {
        $this->validate([
            'dateOfSampling' => ['nullable', 'date'],
            'gpsLat' => ['nullable', 'numeric', 'between:-90,90'],
            'gpsLon' => ['nullable', 'numeric', 'between:-180,180'],
            'altitude' => ['nullable', 'numeric'],
            'samplingMethod' => ['nullable', 'in:' . implode(',', array_keys(OptionList::optionsFor('sampling_methods')))],
            'packaging' => ['nullable', 'in:' . implode(',', array_keys(OptionList::optionsFor('packaging_options')))],
        ]);

        $this->sampling->fill([
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

        $changes = ActivityLogger::diff($this->sampling);
        $this->sampling->save();

        ActivityLogger::editSampling($this->sampling, $changes);

        session()->flash('success', 'Sampling updated.');

        $this->redirect(route('samplings.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.samplings.edit', [
            'samplingMethods' => OptionList::optionsFor('sampling_methods'),
            'packagingOptions' => OptionList::optionsFor('packaging_options'),
        ])->layout('layouts.app');
    }
}
