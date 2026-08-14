<?php

namespace Database\Seeders;

use App\Models\OptionList;
use App\Models\OptionValue;
use Illuminate\Database\Seeder;

class OptionListSeeder extends Seeder
{
    /**
     * Seeds every predefined dropdown list that used to live as hardcoded PHP constants
     * on Sample, Sampling, Experiment, and ExperimentRecord (plus Project's inline
     * status list) into the option_lists/option_values tables. Idempotent — safe to
     * re-run; existing rows are left alone by key.
     */
    public function run(): void
    {
        // Dropped from the manageable set — a Yes/No select doesn't need admin-editable
        // values, ExperimentRecord::YES_NO stays a hardcoded pair. Cleans up a stray row
        // from before this list was removed here, if this seeder ran against that state.
        OptionList::where('key', 'yes_no')->delete();

        $this->flat('sample_groups', 'Sample groups', [
            'food' => 'Food',
            'human_medical' => 'Human/Medical',
            'plant' => 'Plant',
            'animal' => 'Animal',
        ]);

        // Subgroups are scoped to whichever sample_group(s) they make sense under — a value
        // can apply to more than one group (e.g. "meat_products" fits both "food" and "animal"),
        // but not to every group ("swab" only makes sense under "human_medical").
        $this->nested('sample_subgroups', 'Sample subgroups', 'sample_groups', [
            'fruits' => ['label' => 'Fruits', 'groups' => ['food', 'plant']],
            'vegetables' => ['label' => 'Vegetables', 'groups' => ['food', 'plant']],
            'grains' => ['label' => 'Grains', 'groups' => ['food', 'plant']],
            'grain_products' => ['label' => 'Grain products', 'groups' => ['food', 'plant']],
            'milks' => ['label' => 'Milks', 'groups' => ['food', 'animal']],
            'dairy_products' => ['label' => 'Dairy products', 'groups' => ['food', 'animal']],
            'meats' => ['label' => 'Meats', 'groups' => ['food', 'animal']],
            'meat_products' => ['label' => 'Meat products', 'groups' => ['food', 'animal']],
            'fish_seafood' => ['label' => 'Fish and seafood', 'groups' => ['food', 'animal']],
            'fish_seafood_products' => ['label' => 'Fish and seafood products', 'groups' => ['food', 'animal']],
            'fats_oils' => ['label' => 'Fats and oils', 'groups' => ['food', 'plant', 'animal']],
            'sugars' => ['label' => 'Sugars', 'groups' => ['food', 'plant']],
            'beverages' => ['label' => 'Beverages', 'groups' => ['food', 'plant']],
            'herbs_spices' => ['label' => 'Herbs and spices', 'groups' => ['food', 'plant']],
            'mushrooms' => ['label' => 'Mushrooms', 'groups' => ['food', 'plant']],
            'blood' => ['label' => 'Blood', 'groups' => ['human_medical']],
            'urine' => ['label' => 'Urine', 'groups' => ['human_medical']],
            'saliva' => ['label' => 'Saliva', 'groups' => ['human_medical']],
            'tissue' => ['label' => 'Tissue', 'groups' => ['human_medical']],
            'cell' => ['label' => 'Cell', 'groups' => ['human_medical']],
            'swab' => ['label' => 'Swab', 'groups' => ['human_medical']],
            'stool' => ['label' => 'Stool', 'groups' => ['human_medical']],
            'hair_nail' => ['label' => 'Hair and nail', 'groups' => ['human_medical']],
            'breath' => ['label' => 'Breath', 'groups' => ['human_medical']],
        ]);

        $this->flat('storage_conditions', 'Storage conditions', [
            'dark_room_temp' => 'Dark, room temperature (18-25°C)',
            'refrigerated' => 'Refrigerated (4°C)',
            'frozen' => 'Frozen (-20°C)',
            'deep_frozen' => 'Deep frozen (-80°C)',
        ]);

        $this->flat('storage_condition_details', 'Storage condition details', [
            'vacuum_sealed' => 'Vacuum sealed',
            'inert_gas' => 'Inert gas atmosphere',
            'sterile' => 'Sterile',
            'controlled_humidity' => 'Controlled humidity',
            'dry' => 'Dry',
            'styrofoam_box_with_ice' => 'Styrofoam box with ice',
        ]);

        $this->flat('purposes_of_analysis', 'Purposes of analysis', [
            'identification' => 'Identification',
            'quantification' => 'Quantification',
            'characterisation' => 'Characterisation',
            'quality_control' => 'Quality control',
            'screening' => 'Screening',
            'monitoring' => 'Monitoring',
            'comparative_analysis' => 'Comparative analysis',
            'geographical_traceability' => 'Geographical traceability',
            'authentication' => 'Authentication',
            'metabolic_diagnostic_testing' => 'Metabolic and diagnostic testing',
            'biomarker_detection' => 'Biomarker detection',
            'pollution_assessment' => 'Pollution assessment',
            'dating' => 'Dating',
            'origin_of_materials' => 'Origin of materials',
            'vegetation_reconstruction' => 'Vegetation reconstruction',
            'food_or_substance_traces' => 'Food or substance traces',
        ]);

        $this->flat('planned_analyses', 'Planned analyses', [
            'd2h' => 'δ2H',
            'd18o' => 'δ18O',
            'd2h_ext_water' => 'δ2Hext. water',
            'd13c' => 'δ13C',
            'd15n' => 'δ15N',
            'd34s' => 'δ34S',
            'elemental_composition' => 'Elemental composition',
            'mk_gc_ms_fid' => 'MK GC-MS/FID',
            'mk_gc_irms' => 'MK GC-IRMS',
            'voc_gc_ms' => 'VOC GC-MS',
            'voc_gc_irms' => 'VOC GC-IRMS',
        ]);

        $this->flat('status_options', 'Status (plant/animal)', [
            'authentic_slo' => 'Authentic SLO',
            'test_slo' => 'Test SLO',
            'abroad' => 'Abroad',
        ]);

        $this->flat('production_types', 'Production types', [
            'organic' => 'Organic',
            'conventional' => 'Conventional',
        ]);

        $this->flat('source_of_water', 'Source of water', [
            'surface_water' => 'Surface water',
            'groundwater' => 'Groundwater',
            'rainwater' => 'Rainwater',
            'treated_wastewater' => 'Treated wastewater',
        ]);

        $this->flat('part_of_plant', 'Part of plant', [
            'roots' => 'Roots',
            'stems' => 'Stems',
            'leaves' => 'Leaves',
            'flowers' => 'Flowers',
            'fruits' => 'Fruits',
            'seeds' => 'Seeds',
            'buds' => 'Buds',
            'rhizomes_tubers' => 'Rhizomes and tubers',
            'wood' => 'Wood',
            'epidermal_tissues' => 'Epidermal tissues',
        ]);

        $this->flat('plant_producer', 'Plant producer', [
            'authentic' => 'Authentic',
            'market' => 'Market',
        ]);

        $this->flat('plant_processing_types', 'Plant processing types', [
            'raw' => 'Raw',
            'fresh' => 'Fresh',
            'frozen' => 'Frozen',
            'fermented' => 'Fermented',
            'canned_preserved' => 'Canned/preserved',
            'dried' => 'Dried',
            'freeze_dried' => 'Freeze-dried',
        ]);

        $this->flat('part_of_animal', 'Part of animal', [
            'muscle' => 'Muscle',
            'fat' => 'Fat',
            'bone' => 'Bone',
            'milk' => 'Milk',
            'eggs' => 'Eggs',
            'skin' => 'Skin',
            'liver' => 'Liver (organ)',
            'kidney' => 'Kidney (organ)',
            'heart' => 'Heart (organ)',
            'lungs' => 'Lungs (organ)',
            'spleen' => 'Spleen (organ)',
            'brain' => 'Brain (organ)',
        ]);

        $this->flat('animal_processing_types', 'Animal processing types', [
            'raw' => 'Raw',
            'fresh' => 'Fresh',
            'frozen' => 'Frozen',
            'fermented' => 'Fermented',
            'canned_preserved' => 'Canned/preserved',
            'dried' => 'Dried',
            'freeze_dried' => 'Freeze-dried',
            'minced' => 'Minced',
            'cured' => 'Cured',
        ]);

        $this->flat('animal_feed_types', 'Animal feed types', [
            'forage' => 'Forage',
            'silage' => 'Silage',
            'hay' => 'Hay',
            'concentrates' => 'Concentrates',
            'protein_feed_animal' => 'Protein feeds of animal source',
            'protein_feed_plant' => 'Protein feeds of plant source',
            'mineral_supplements' => 'Mineral supplements',
            'vitamin_supplements' => 'Vitamin supplements',
            'by_product_feeds' => 'By-product feeds',
            'complete_feeds' => 'Complete feeds',
            'medications_probiotics' => 'Medications or probiotics',
        ]);

        $this->flat('sampling_methods', 'Sampling methods', [
            'official_sampling' => 'Official sampling',
            'producer_sampling' => 'Producer sampling',
            'self_sampling' => 'Self sampling',
            'manual_picking' => 'Manual picking',
            'instrument_sampling' => 'Instrument sampling',
            'composite_sampling' => 'Composite sampling',
            'spot_sampling' => 'Spot sampling',
            'systematic_sampling' => 'Systematic sampling',
            'excavation' => 'Excavation',
            'swab' => 'Swab',
            'core_sampling' => 'Core sampling',
            'active_sampling_pump' => 'Active sampling (pump)',
            'passive_sampling' => 'Passive sampling',
            'continuous_sampling' => 'Continuous sampling',
        ]);

        $this->flat('packaging_options', 'Packaging options', [
            'plastic_bags' => 'Plastic bags',
            'glass_bottles' => 'Glass bottles',
            'metal_cans' => 'Metal cans',
            'cardboard_boxes' => 'Cardboard boxes',
            'vacuum_sealed_bags' => 'Vacuum-sealed bags',
            'refrigerated_boxes' => 'Refrigerated boxes',
            'sterile_containers' => 'Sterile containers',
            'amber_glass_bottles' => 'Amber glass bottles',
        ]);

        $this->flat('experiment_statuses', 'Experiment statuses', [
            'planned' => 'Planned',
            'in_progress' => 'In progress',
            'completed' => 'Completed',
        ]);

        $this->flat('project_statuses', 'Project statuses', [
            'active' => 'Active',
            'completed' => 'Completed',
            'archived' => 'Archived',
        ]);

        $this->flat('analytes', 'Analytes', [
            'd18o_water' => 'δ18Owater',
            'd18o' => 'δ18O',
            'd2h' => 'δ2H',
            'd13c' => 'δ13C',
            'd13c_fat' => 'δ13Cfat',
            'd13c_defatted' => 'δ13Cdefatted',
            'd13c_pulp' => 'δ13Cpulp',
            'd13c_casein' => 'δ13Ckazein',
            'd13c_protein' => 'δ13Cprotein',
            'd13c_sugar' => 'δ13Csugar',
            'd13c_ethanol' => 'δ13Cethanol',
            'd15n' => 'δ15N',
            'd34s' => 'δ34S',
        ]);

        $this->flat('phase_of_sample', 'Phase of sample', [
            'solid' => 'Solid',
            'liquid' => 'Liquid',
            'gas' => 'Gas',
        ]);

        $this->flat('drying_options', 'Drying options', [
            'no' => 'No',
            'freeze_drying' => 'Freeze drying',
            'air_drying' => 'Air drying',
            'oven_drying' => 'Oven drying',
        ]);

        $this->flat('homogenisation_options', 'Homogenisation options', [
            'no' => 'No',
            'mortar' => 'Mortar',
            'ball_mill' => 'Ball mill',
            'liquid_nitrogen' => 'Liquid nitrogen',
        ]);

        $this->flat('preparation_methods', 'Preparation methods', [
            'defatting' => 'Defatting',
            'casein_isolation' => 'Casein (kazein) isolation',
            'protein_extraction' => 'Protein extraction',
            'sugar_isolation' => 'Sugar isolation',
            'pulp_preparation' => 'Pulp preparation',
            'gelatine_extraction' => 'Gelatine extraction',
            'decarbonation' => 'Decarbonation',
            'solvent_extraction_specific_compounds' => 'Solvent extraction of specific compounds',
            'spe_cleanup' => 'SPE cleanup',
            'derivatization' => 'Derivatization',
            'ultrafiltration' => 'Ultrafiltration',
            'water_extraction' => 'Water extraction',
        ]);

        $this->flat('microwave_phase_of_sample', 'Microwave digestion — phase of sample', [
            'fresh' => 'Fresh',
            'freeze_dried' => 'Freeze dried',
            'defatted' => 'Defatted',
        ]);

        $this->flat('elements', 'Elements', [
            'C' => 'Carbon (C)',
            'H' => 'Hydrogen (H)',
            'N' => 'Nitrogen (N)',
            'O' => 'Oxygen (O)',
            'S' => 'Sulfur (S)',
        ]);

        $this->flat('mk_gc_ms_units', 'MK GC-MS units', [
            'percent' => '%',
            'mg_100g' => 'mg/100 g',
            'mg_g_fat' => 'mg/g fat',
        ]);
    }

    /** @param array<string, string> $options */
    private function flat(string $key, string $name, array $options): void
    {
        $list = OptionList::updateOrCreate(['key' => $key], ['name' => $name, 'is_nested' => false]);

        $order = 0;
        foreach ($options as $optionKey => $label) {
            OptionValue::updateOrCreate(
                ['option_list_id' => $list->id, 'key' => $optionKey],
                ['label' => $label, 'sort_order' => $order++],
            );
        }
    }

    /** @param array<string, array{label: string, groups: string[]}> $options keyed by option key, each scoped to one or more parent-list group keys */
    private function nested(string $key, string $name, string $parentListKey, array $options): void
    {
        $parentList = OptionList::where('key', $parentListKey)->firstOrFail();
        $list = OptionList::updateOrCreate(['key' => $key], ['name' => $name, 'is_nested' => true, 'parent_list_id' => $parentList->id]);

        $order = 0;
        foreach ($options as $optionKey => $spec) {
            $value = OptionValue::updateOrCreate(
                ['option_list_id' => $list->id, 'key' => $optionKey],
                ['label' => $spec['label'], 'sort_order' => $order++],
            );

            $groupValueIds = OptionValue::where('option_list_id', $parentList->id)
                ->whereIn('key', $spec['groups'])
                ->pluck('id');

            $value->scopedTo()->sync($groupValueIds);
        }
    }
}
