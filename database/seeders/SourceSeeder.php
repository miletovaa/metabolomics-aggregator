<?php

namespace Database\Seeders;

use App\Models\Source;
use Illuminate\Database\Seeder;

class SourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = [
            'PubChem',
            'HMDB',
            'ChEBI',
            'NIST',
        ];

        foreach ($sources as $source) {
            Source::firstOrCreate([
                'name' => $source,
            ]);
        }
    }
}