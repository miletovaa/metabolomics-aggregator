<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sample extends Model
{
    use HasFactory;

    protected $fillable = [
        'lab_sample_id',
        'external_id',
        'matrix_group',
        'sample_group',
        'sample_subgroup',
        'date_received',
        'storage_condition',
        'storage_condition_details',
        'responsible_analyst_id',
        'project_id',
        'purpose_of_analysis',
        'planned_analysis',
        'type_details',
        'note',
    ];

    protected $casts = [
        'date_received' => 'date',
        'storage_condition_details' => 'array',
        'purpose_of_analysis' => 'array',
        'planned_analysis' => 'array',
        'type_details' => 'array',
    ];

    public const GROUPS = [
        'food' => 'Food',
        'human_medical' => 'Human/Medical',
        'plant' => 'Plant',
        'animal' => 'Animal',
    ];

    // Groups that carry a dedicated sample type details block (identification → type details → storage).
    public const TYPE_DETAIL_GROUPS = ['plant', 'animal'];

    public const SUBGROUPS = [
        'food' => [
            'fruits' => 'Fruits',
            'vegetables' => 'Vegetables',
            'grains' => 'Grains',
            'grain_products' => 'Grain products',
            'milks' => 'Milks',
            'dairy_products' => 'Dairy products',
            'meats' => 'Meats',
            'meat_products' => 'Meat products',
            'fish_seafood' => 'Fish and seafood',
            'fish_seafood_products' => 'Fish and seafood products',
            'fats_oils' => 'Fats and oils',
            'sugars' => 'Sugars',
            'beverages' => 'Beverages',
            'herbs_spices' => 'Herbs and spices',
            'mushrooms' => 'Mushrooms',
        ],
        'human_medical' => [
            'blood' => 'Blood',
            'urine' => 'Urine',
            'saliva' => 'Saliva',
            'tissue' => 'Tissue',
            'cell' => 'Cell',
            'swab' => 'Swab',
            'stool' => 'Stool',
            'hair_nail' => 'Hair and nail',
            'breath' => 'Breath',
        ],
    ];

    public const STORAGE_CONDITIONS = [
        'dark_room_temp' => 'Dark, room temperature (18-25°C)',
        'refrigerated' => 'Refrigerated (4°C)',
        'frozen' => 'Frozen (-20°C)',
        'deep_frozen' => 'Deep frozen (-80°C)',
    ];

    public const STORAGE_CONDITION_DETAILS = [
        'vacuum_sealed' => 'Vacuum sealed',
        'inert_gas' => 'Inert gas atmosphere',
        'sterile' => 'Sterile',
        'controlled_humidity' => 'Controlled humidity',
        'dry' => 'Dry',
        'styrofoam_box_with_ice' => 'Styrofoam box with ice',
    ];

    public const PURPOSES_OF_ANALYSIS = [
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
    ];

    public const PLANNED_ANALYSES = [
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
    ];

    public const STATUS_OPTIONS = [
        'authentic_slo' => 'Authentic SLO',
        'test_slo' => 'Test SLO',
        'abroad' => 'Abroad',
    ];

    public const PRODUCTION_TYPES = [
        'organic' => 'Organic',
        'conventional' => 'Conventional',
    ];

    public const SOURCE_OF_WATER = [
        'surface_water' => 'Surface water',
        'groundwater' => 'Groundwater',
        'rainwater' => 'Rainwater',
        'treated_wastewater' => 'Treated wastewater',
    ];

    // ── Plant-specific field options ────────────────────────────────────────

    public const PART_OF_PLANT = [
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
    ];

    public const PLANT_PRODUCER = [
        'authentic' => 'Authentic',
        'market' => 'Market',
    ];

    public const PLANT_PROCESSING_TYPES = [
        'raw' => 'Raw',
        'fresh' => 'Fresh',
        'frozen' => 'Frozen',
        'fermented' => 'Fermented',
        'canned_preserved' => 'Canned/preserved',
        'dried' => 'Dried',
        'freeze_dried' => 'Freeze-dried',
    ];

    // ── Animal-specific field options ───────────────────────────────────────

    public const PART_OF_ANIMAL = [
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
    ];

    public const ANIMAL_PROCESSING_TYPES = [
        'raw' => 'Raw',
        'fresh' => 'Fresh',
        'frozen' => 'Frozen',
        'fermented' => 'Fermented',
        'canned_preserved' => 'Canned/preserved',
        'dried' => 'Dried',
        'freeze_dried' => 'Freeze-dried',
        'minced' => 'Minced',
        'cured' => 'Cured',
    ];

    public const ANIMAL_FEED_TYPES = [
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
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function responsibleAnalyst(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_analyst_id');
    }

    public function sampling(): HasOne
    {
        return $this->hasOne(Sampling::class);
    }

    public function subgroupOptions(): array
    {
        return self::SUBGROUPS[$this->sample_group] ?? [];
    }

    public function groupLabel(): ?string
    {
        return self::GROUPS[$this->sample_group] ?? $this->sample_group;
    }

    public function subgroupLabel(): ?string
    {
        return $this->subgroupOptions()[$this->sample_subgroup] ?? $this->sample_subgroup;
    }

    public function storageConditionLabel(): ?string
    {
        return self::STORAGE_CONDITIONS[$this->storage_condition] ?? $this->storage_condition;
    }
}
