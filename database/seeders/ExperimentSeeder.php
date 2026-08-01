<?php

namespace Database\Seeders;

use App\Models\Compound;
use App\Models\Experiment;
use App\Models\ExperimentRecord;
use App\Models\Project;
use App\Models\Sample;
use App\Models\User;
use Illuminate\Database\Seeder;

class ExperimentSeeder extends Seeder
{
    public function run(): void
    {
        $analyst = User::first();

        $this->seedAppleExperiment($analyst);
        $this->seedMilkExperiment($analyst);
    }

    private function seedAppleExperiment(?User $analyst): void
    {
        $sample = Sample::where('lab_sample_id', 'LAB-0001')->first();
        $project = Project::where('name', 'Essential Oils')->first();

        if (! $sample) {
            return;
        }

        $experiment = Experiment::updateOrCreate(
            ['name' => 'Apple Origin Authentication 2026'],
            [
                'project_id' => $project?->id,
                'description' => 'Bulk stable isotope authentication of Slovenian apple pulp against declared origin.',
                'status' => 'in_progress',
                'started_at' => '2026-04-14',
                'created_by' => $analyst?->id,
            ],
        );

        if ($experiment->records()->exists()) {
            return;
        }

        $prep = ExperimentRecord::create([
            'experiment_id' => $experiment->id,
            'sample_id' => $sample->id,
            'record_type' => 'sample_prep',
            'performed_by' => $analyst?->id,
            'performed_at' => '2026-04-15',
            'details' => [
                'amount_of_sample_g' => '15.2',
                'phase_of_sample' => 'solid',
                'drying' => 'freeze_drying',
                'homogenisation' => 'mortar',
                'sieving' => 'no',
                'preparation_method' => ['water_extraction'],
                'protocol' => 'Freeze-dry pulp, homogenise with mortar and pestle, extract with ultrapure water.',
            ],
        ]);

        $standardsNormalisation = 'USGS53 (5.47 ± 0.03); seawater (0.36 ± 0.04); snow (-18.91 ± 0.03)';
        $standardsQc = 'Milli-Q water (-9.12 ± 0.04)';

        $analysisC = ExperimentRecord::create([
            'experiment_id' => $experiment->id,
            'sample_id' => $sample->id,
            'parent_record_id' => $prep->id,
            'record_type' => 'analysis_isotopes',
            'performed_by' => $analyst?->id,
            'performed_at' => '2026-04-18',
            'details' => [
                'analyte' => 'd13c',
                'instrument' => 'IRMS (Finnigan DELTA XP) coupled to EA (Finnigan DELTA TC/EA)',
                'instrument_settings' => 'Standard bulk EA-IRMS combustion settings.',
                'standards_normalisation' => $standardsNormalisation,
                'standards_qc' => $standardsQc,
                'normalisation_done_by' => $analyst?->id,
            ],
        ]);

        $analysisO = ExperimentRecord::create([
            'experiment_id' => $experiment->id,
            'sample_id' => $sample->id,
            'parent_record_id' => $prep->id,
            'record_type' => 'analysis_isotopes',
            'performed_by' => $analyst?->id,
            'performed_at' => '2026-04-18',
            'details' => [
                'analyte' => 'd18o',
                'instrument' => 'IRMS (Finnigan DELTA XP) coupled to TC/EA',
                'instrument_settings' => 'Pyrolysis TC/EA settings for oxygen isotopes.',
                'standards_normalisation' => $standardsNormalisation,
                'standards_qc' => $standardsQc,
                'normalisation_done_by' => $analyst?->id,
            ],
        ]);

        ExperimentRecord::create([
            'experiment_id' => $experiment->id,
            'sample_id' => $sample->id,
            'parent_record_id' => $analysisC->id,
            'record_type' => 'result_stable_isotopes',
            'performed_by' => $analyst?->id,
            'performed_at' => '2026-04-19',
            'details' => ['analyte' => 'd13c', 'value' => '-24.8', 'stdev' => '0.09', 'unit' => '‰'],
        ]);

        ExperimentRecord::create([
            'experiment_id' => $experiment->id,
            'sample_id' => $sample->id,
            'parent_record_id' => $analysisO->id,
            'record_type' => 'result_stable_isotopes',
            'performed_by' => $analyst?->id,
            'performed_at' => '2026-04-19',
            'details' => ['analyte' => 'd18o', 'value' => '26.1', 'stdev' => '0.15', 'unit' => '‰'],
        ]);
    }

    private function seedMilkExperiment(?User $analyst): void
    {
        $sample = Sample::where('lab_sample_id', 'LAB-0003')->first();
        $project = Project::where('name', 'Milk Traceability Study')->first();

        if (! $sample) {
            return;
        }

        $experiment = Experiment::updateOrCreate(
            ['name' => 'Milk Elemental & Fatty Acid Profile Study'],
            [
                'project_id' => $project?->id,
                'description' => 'Bulk elemental composition and FAME fingerprint of raw milk for traceability.',
                'status' => 'completed',
                'started_at' => '2026-04-20',
                'completed_at' => '2026-04-28',
                'created_by' => $analyst?->id,
            ],
        );

        if ($experiment->records()->exists()) {
            return;
        }

        $digestion = ExperimentRecord::create([
            'experiment_id' => $experiment->id,
            'sample_id' => $sample->id,
            'record_type' => 'sample_prep_microwave_digestion',
            'performed_by' => $analyst?->id,
            'performed_at' => '2026-04-20',
            'details' => [
                'phase_of_sample' => 'fresh',
                'first_step_weighing' => '0.5 g into PTFE vessel',
                'microwave_program' => 'Ramp 10 min to 180°C, hold 15 min',
                'second_step_dilution' => 'Diluted to 50 mL with ultrapure water',
            ],
        ]);

        $elementalAnalysis = ExperimentRecord::create([
            'experiment_id' => $experiment->id,
            'sample_id' => $sample->id,
            'parent_record_id' => $digestion->id,
            'record_type' => 'analysis_elemental_composition',
            'performed_by' => $analyst?->id,
            'performed_at' => '2026-04-22',
            'details' => [
                'instrument' => 'Elemental Analyzer (Vario EL cube)',
                'standards_used' => 'Sulfanilamide certified reference standard',
                'results_reviewed_by' => $analyst?->id,
            ],
        ]);

        foreach ([['C', '4.80', '%'], ['N', '0.55', '%']] as [$element, $value, $unit]) {
            ExperimentRecord::create([
                'experiment_id' => $experiment->id,
                'sample_id' => $sample->id,
                'parent_record_id' => $elementalAnalysis->id,
                'record_type' => 'result_elemental_composition',
                'performed_by' => $analyst?->id,
                'performed_at' => '2026-04-22',
                'details' => ['element' => $element, 'value' => $value, 'unit' => $unit],
            ]);
        }

        $famePrep = ExperimentRecord::create([
            'experiment_id' => $experiment->id,
            'sample_id' => $sample->id,
            'record_type' => 'sample_prep',
            'performed_by' => $analyst?->id,
            'performed_at' => '2026-04-23',
            'details' => [
                'phase_of_sample' => 'liquid',
                'drying' => 'no',
                'homogenisation' => 'no',
                'sieving' => 'no',
                'preparation_method' => ['defatting', 'derivatization'],
                'protocol' => 'Lipid extraction followed by FAME derivatization for GC-MS.',
            ],
        ]);

        $gcMsAnalysis = ExperimentRecord::create([
            'experiment_id' => $experiment->id,
            'sample_id' => $sample->id,
            'parent_record_id' => $famePrep->id,
            'record_type' => 'analysis_mk_gc_ms',
            'performed_by' => $analyst?->id,
            'performed_at' => '2026-04-25',
            'details' => [
                'instrument' => 'Agilent 7890B GC / 5977B MSD',
                'mps_syringe' => '10 µL',
                'analysis_method_type' => 'Targeted FAME',
                'inj_volume_ul' => '1',
                'rinse_settings' => 'Wash 1: pre 5, post 5; Wash 2: pre 0, post 0',
                'gc_column' => 'DB-23, 60 m',
                'flow' => '1.0 mL/min He',
                'inlet_mode' => 'Split 1:20',
                'oven_program' => '55(2); 10°C/min 200(0); 3°C/min 235(0); 8°C/min 380(8)',
                'run_time' => '45 min',
                'ms_mass' => '50-550 amu',
                'solvent_delay' => '4 min',
                'standards_used' => '37-component FAME mix; internal standard: d-toluene',
                'compound_identification_done_by' => $analyst?->id,
            ],
        ]);

        $fattyAcidResults = [
            ['112-80-1', '28.4'], // Oleic acid
            ['57-10-3', '31.2'],  // Palmitic acid
            ['57-11-4', '11.6'],  // Stearic acid
        ];

        foreach ($fattyAcidResults as [$cas, $value]) {
            $compound = Compound::where('cas', $cas)->first();
            if (! $compound) {
                continue;
            }

            ExperimentRecord::create([
                'experiment_id' => $experiment->id,
                'sample_id' => $sample->id,
                'parent_record_id' => $gcMsAnalysis->id,
                'record_type' => 'result_mk_gc_ms',
                'performed_by' => $analyst?->id,
                'performed_at' => '2026-04-26',
                'details' => ['compound_id' => $compound->id, 'unit' => 'percent', 'value' => $value],
            ]);
        }
    }
}
