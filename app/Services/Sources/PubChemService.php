<?php

namespace App\Services\Sources;

class PubChemService
{
    protected $sourceName = 'PubChem';

    public function searchByName(string $name): array {}
    public function getByCid(string $inchiKey): array {}
}