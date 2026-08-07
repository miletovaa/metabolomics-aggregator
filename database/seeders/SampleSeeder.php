<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Sample;
use App\Models\Sampling;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleSeeder extends Seeder
{
    public function run(): void
    {
        $analyst = User::first();
        $essentialOils = Project::where('name', 'Essential Oils')->first();
        $vocProject = Project::where('name', 'PD Volatile Compounds GC-MS')->first();
        $milkProject = Project::firstOrCreate(
            ['name' => 'Milk Traceability Study'],
            ['status' => 'active', 'user_id' => $analyst?->id],
        );

        $samples = [
            [
                'lab_sample_id' => 'LAB-0001',
                'external_id' => 'EXT-APL-01',
                'matrix_group' => 'Fruit pulp',
                'sample_group' => 'plant',
                'sample_subgroup' => null,
                'date_received' => '2026-04-02',
                'storage_condition' => 'refrigerated',
                'storage_condition_details' => ['dry'],
                'project_id' => $essentialOils?->id,
                'purpose_of_analysis' => ['authentication', 'geographical_traceability'],
                'planned_analysis' => ['d13c', 'd18o'],
                'sample_type' => 'plant',
                'type_details' => [
                    'latin_name' => 'Malus domestica',
                    'part_of_plant' => 'fruits',
                    'harvest_year' => '2025',
                    'status' => 'authentic_slo',
                    'producer' => 'authentic',
                    'production_type' => 'organic',
                    'declared_country_of_origin' => 'Slovenia',
                    'region_of_origin' => 'Štajerska',
                    'irrigation' => 'no',
                    'processing_type' => 'fresh',
                ],
                'sampling' => [
                    'date_of_sampling' => '2026-04-01',
                    'country_of_sampling' => 'Slovenia',
                    'place_of_sampling' => 'Maribor orchard',
                    'gerk' => 'GERK-4471023',
                    'gps_lat' => 46.5547,
                    'gps_lon' => 15.6459,
                    'altitude' => 274.0,
                    'sampling_method' => 'manual_picking',
                    'packaging' => 'plastic_bags',
                    'collector' => 'Janez Novak',
                ],
            ],
            [
                'lab_sample_id' => 'LAB-0002',
                'external_id' => 'EXT-BEEF-01',
                'matrix_group' => 'Muscle tissue',
                'sample_group' => 'animal',
                'sample_subgroup' => null,
                'date_received' => '2026-04-05',
                'storage_condition' => 'frozen',
                'storage_condition_details' => ['vacuum_sealed'],
                'project_id' => $milkProject->id,
                'purpose_of_analysis' => ['authentication', 'origin_of_materials'],
                'planned_analysis' => ['d13c', 'd15n'],
                'sample_type' => 'animal',
                'type_details' => [
                    'common_name' => 'Cattle',
                    'latin_name' => 'Bos taurus',
                    'part_of_animal' => 'muscle',
                    'status' => 'authentic_slo',
                    'producer' => 'authentic_slo',
                    'production_type' => 'conventional',
                    'country_of_origin' => 'Slovenia',
                    'region_of_origin' => 'Pomurje',
                    'feed' => ['forage', 'concentrates'],
                    'source_of_drinking_water' => 'groundwater',
                    'processing_type' => 'fresh',
                ],
                'sampling' => [
                    'date_of_sampling' => '2026-04-04',
                    'country_of_sampling' => 'Slovenia',
                    'place_of_sampling' => 'Murska Sobota abattoir',
                    'gps_lat' => 46.6653,
                    'gps_lon' => 16.1666,
                    'altitude' => 188.0,
                    'sampling_method' => 'official_sampling',
                    'packaging' => 'vacuum_sealed_bags',
                    'collector' => 'Maja Kovač',
                ],
            ],
            [
                'lab_sample_id' => 'LAB-0003',
                'external_id' => 'EXT-MILK-01',
                'matrix_group' => 'Raw milk',
                'sample_group' => 'animal',
                'sample_subgroup' => null,
                'date_received' => '2026-04-06',
                'storage_condition' => 'refrigerated',
                'storage_condition_details' => ['sterile'],
                'project_id' => $milkProject->id,
                'purpose_of_analysis' => ['quality_control', 'geographical_traceability'],
                'planned_analysis' => ['d13c', 'd18o', 'elemental_composition'],
                'sample_type' => 'animal',
                'type_details' => [
                    'common_name' => 'Dairy cow',
                    'latin_name' => 'Bos taurus',
                    'part_of_animal' => 'milk',
                    'status' => 'authentic_slo',
                    'producer' => 'authentic_slo',
                    'production_type' => 'organic',
                    'country_of_origin' => 'Slovenia',
                    'region_of_origin' => 'Gorenjska',
                    'feed' => ['forage', 'hay', 'mineral_supplements'],
                    'source_of_drinking_water' => 'surface_water',
                    'processing_type' => 'raw',
                ],
                'sampling' => [
                    'date_of_sampling' => '2026-04-06',
                    'country_of_sampling' => 'Slovenia',
                    'place_of_sampling' => 'Kranj dairy farm',
                    'gerk' => 'GERK-1182204',
                    'gps_lat' => 46.2437,
                    'gps_lon' => 14.3557,
                    'altitude' => 386.0,
                    'sampling_method' => 'producer_sampling',
                    'packaging' => 'sterile_containers',
                    'collector' => 'Peter Zorko',
                ],
            ],
            [
                'lab_sample_id' => 'LAB-0006',
                'external_id' => 'EXT-BLD-01',
                'matrix_group' => 'Whole blood',
                'sample_group' => 'human_medical',
                'sample_subgroup' => 'blood',
                'date_received' => '2026-04-10',
                'storage_condition' => 'frozen',
                'storage_condition_details' => ['sterile'],
                'project_id' => null,
                'purpose_of_analysis' => ['metabolic_diagnostic_testing', 'biomarker_detection'],
                'planned_analysis' => ['mk_gc_ms_fid'],
                'sample_type' => null,
                'type_details' => null,
                'sampling' => [
                    'date_of_sampling' => '2026-04-10',
                    'country_of_sampling' => 'Slovenia',
                    'place_of_sampling' => 'UKC Ljubljana',
                    'sampling_method' => 'official_sampling',
                    'packaging' => 'sterile_containers',
                    'collector' => 'Dr. Nina Hočevar',
                ],
            ],
            [
                'lab_sample_id' => 'LAB-0007',
                'external_id' => 'EXT-HERB-01',
                'matrix_group' => 'Dried leaves',
                'sample_group' => 'plant',
                'sample_subgroup' => null,
                'date_received' => '2026-04-11',
                'storage_condition' => 'dark_room_temp',
                'storage_condition_details' => ['dry', 'controlled_humidity'],
                'project_id' => $essentialOils?->id,
                'purpose_of_analysis' => ['characterisation', 'screening'],
                'planned_analysis' => ['voc_gc_ms', 'voc_gc_irms'],
                'sample_type' => 'plant',
                'type_details' => [
                    'latin_name' => 'Salvia officinalis',
                    'part_of_plant' => 'leaves',
                    'harvest_year' => '2025',
                    'status' => 'authentic_slo',
                    'producer' => 'market',
                    'production_type' => 'conventional',
                    'declared_country_of_origin' => 'Slovenia',
                    'processing_type' => 'dried',
                ],
                'sampling' => [
                    'date_of_sampling' => '2026-04-10',
                    'country_of_sampling' => 'Slovenia',
                    'place_of_sampling' => 'Vipava valley herb farm',
                    'sampling_method' => 'composite_sampling',
                    'packaging' => 'cardboard_boxes',
                    'collector' => 'Janez Novak',
                ],
            ],
            [
                'lab_sample_id' => 'LAB-0008',
                'external_id' => 'EXT-FISH-01',
                'matrix_group' => 'Fillet',
                'sample_group' => 'animal',
                'sample_subgroup' => null,
                'date_received' => '2026-04-12',
                'storage_condition' => 'deep_frozen',
                'storage_condition_details' => ['vacuum_sealed'],
                'project_id' => $vocProject?->id,
                'purpose_of_analysis' => ['authentication'],
                'planned_analysis' => ['d13c', 'd15n'],
                'sample_type' => 'animal',
                'type_details' => [
                    'common_name' => 'Rainbow trout',
                    'latin_name' => 'Oncorhynchus mykiss',
                    'part_of_animal' => 'muscle',
                    'status' => 'test_slo',
                    'producer' => 'authentic_slo',
                    'production_type' => 'conventional',
                    'country_of_origin' => 'Slovenia',
                    'feed' => ['complete_feeds'],
                    'source_of_drinking_water' => 'surface_water',
                    'processing_type' => 'frozen',
                ],
                // Intentionally left without a sampling record to show the "not yet logged" state.
            ],
            [
                'lab_sample_id' => 'LAB-0009',
                'external_id' => 'EXT-OIL-01',
                'matrix_group' => 'Cold-pressed oil',
                'sample_group' => 'food',
                'sample_subgroup' => 'fats_oils',
                'date_received' => '2026-04-13',
                'storage_condition' => 'dark_room_temp',
                'storage_condition_details' => ['dry', 'inert_gas'],
                'project_id' => $essentialOils?->id,
                'purpose_of_analysis' => ['authentication', 'geographical_traceability'],
                'planned_analysis' => ['d13c', 'd2h'],
                'sample_type' => null,
                'type_details' => null,
                'note' => 'Commercial multi-origin blend; single botanical source not declared, so no plant/animal type details apply.',
                'sampling' => [
                    'date_of_sampling' => '2026-04-12',
                    'country_of_sampling' => 'Slovenia',
                    'place_of_sampling' => 'Retail purchase, Ljubljana',
                    'sampling_method' => 'composite_sampling',
                    'packaging' => 'amber_glass_bottles',
                    'collector' => 'Janez Novak',
                ],
            ],
        ];

        foreach ($samples as $data) {
            $samplingData = $data['sampling'] ?? null;
            unset($data['sampling']);

            $sample = Sample::updateOrCreate(
                ['lab_sample_id' => $data['lab_sample_id']],
                array_merge($data, ['responsible_analyst_id' => $analyst?->id]),
            );

            if ($samplingData) {
                Sampling::updateOrCreate(
                    ['sample_id' => $sample->id],
                    $samplingData,
                );
            }
        }
    }
}
