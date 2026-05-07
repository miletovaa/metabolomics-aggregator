<?php

namespace Database\Seeders;

use App\Models\Compound;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            $user = User::factory()->create([
                'name' => 'Anna',
                'email' => 'anna@example.com',
            ]);
        }

        $project = Project::updateOrCreate(
            [
                'name' => 'Test GC-MS Metabolomics Project',
                'user_id' => $user->id,
            ],
            [
                'description' => 'Development project for testing compound mapping, retention indices, taxonomy, and project compounds.',
                'status' => 'active',
            ]
        );

        $examples = [
            [
                'compound' => 'Limonene',
                'custom_name' => 'Limonene peak',
                'mz' => 136.1252000000,
                'rt' => 8.4300000000,
                'is_mapped' => true,
            ],
            [
                'compound' => 'Alpha-Pinene',
                'custom_name' => 'Possible alpha-pinene',
                'mz' => 136.1252000000,
                'rt' => 6.2100000000,
                'is_mapped' => true,
            ],
            [
                'compound' => 'Beta-Caryophyllene',
                'custom_name' => 'Sesquiterpene candidate',
                'mz' => 204.1878000000,
                'rt' => 14.7800000000,
                'is_mapped' => true,
            ],
        ];

        foreach ($examples as $example) {
            $compound = Compound::where('canonical_name', $example['compound'])->first();

            $project->projectCompounds()->updateOrCreate(
                [
                    'compound_id' => $compound?->id,
                    'custom_name' => $example['custom_name'],
                ],
                [
                    'is_duplicate' => false,
                    'mz' => $example['mz'],
                    'rt' => $example['rt'],
                    'is_mapped' => $example['is_mapped'],
                    'notes' => 'Testing record created by ProjectSeeder.',
                ]
            );
        }

        $project->projectCompounds()->updateOrCreate(
            [
                'compound_id' => null,
                'custom_name' => 'Unknown feature 1',
            ],
            [
                'is_duplicate' => false,
                'mz' => 152.0837000000,
                'rt' => 10.2200000000,
                'is_mapped' => false,
                'notes' => 'Unmapped test feature for import/matching workflow.',
            ]
        );
    }
}