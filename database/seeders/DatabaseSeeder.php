<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OptionListSeeder::class,
            SourceSeeder::class,
            CompoundSeeder::class,
            ProjectSeeder::class,
            FattyAcidCompoundSeeder::class,
            SampleSeeder::class,
            ExperimentSeeder::class,
        ]);
    }
}