<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExperimentRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'experiment_id',
        'sample_id',
        'parent_record_id',
        'record_type',
        'performed_by',
        'performed_at',
        'instrument',
        'note',
        'details',
    ];

    protected $casts = [
        'performed_at' => 'date',
        'details' => 'array',
    ];

    // For result_* records, `instrument` should be copied from the paired
    // analysis_* record's details.instrument at write time — result rows
    // don't carry their own instrument field in fieldSchema(), and this
    // avoids a parent_record_id traversal on every read.

    public const RECORD_TYPES = [
        'sample_prep' => 'Sample preparation',
        'sample_prep_microwave_digestion' => 'Sample preparation — microwave digestion',
        'analysis_isotopes' => 'Analysis — isotopes',
        'analysis_elemental_composition' => 'Analysis — elemental composition',
        'analysis_mk_gc_ms' => 'Analysis — MK GC-MS',
        'analysis_voc_gc_ms' => 'Analysis — VOC GC-MS',
        'analysis_mk_gc_irms' => 'Analysis — MK GC-IRMS',
        'analysis_voc_gc_irms' => 'Analysis — VOC GC-IRMS',
        'result_stable_isotopes' => 'Result — stable isotopes',
        'result_elemental_composition' => 'Result — elemental composition',
        'result_mk_gc_ms' => 'Result — MK GC-MS',
        'result_mk_gc_irms' => 'Result — MK GC-IRMS',
        'result_voc_gc_ms' => 'Result — VOC GC-MS',
        'result_voc_gc_irms' => 'Result — VOC GC-IRMS',
    ];

    public const FAMILIES = [
        'preparation' => ['sample_prep', 'sample_prep_microwave_digestion'],
        'analysis' => [
            'analysis_isotopes', 'analysis_elemental_composition',
            'analysis_mk_gc_ms', 'analysis_voc_gc_ms',
            'analysis_mk_gc_irms', 'analysis_voc_gc_irms',
        ],
        'result' => [
            'result_stable_isotopes', 'result_elemental_composition',
            'result_mk_gc_ms', 'result_mk_gc_irms',
            'result_voc_gc_ms', 'result_voc_gc_irms',
        ],
    ];

    public const FAMILY_LABELS = [
        'preparation' => 'Sample preparation',
        'analysis' => 'Analysis',
        'result' => 'Results',
    ];

    /** Shared isotope/fraction vocabulary used by analysis_isotopes and result_stable_isotopes. */
    public const ANALYTES = [
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
    ];

    public const PHASE_OF_SAMPLE = [
        'solid' => 'Solid',
        'liquid' => 'Liquid',
        'gas' => 'Gas',
    ];

    public const DRYING_OPTIONS = [
        'no' => 'No',
        'freeze_drying' => 'Freeze drying',
        'air_drying' => 'Air drying',
        'oven_drying' => 'Oven drying',
    ];

    public const HOMOGENISATION_OPTIONS = [
        'no' => 'No',
        'mortar' => 'Mortar',
        'ball_mill' => 'Ball mill',
        'liquid_nitrogen' => 'Liquid nitrogen',
    ];

    public const YES_NO = [
        'no' => 'No',
        'yes' => 'Yes',
    ];

    public const PREPARATION_METHODS = [
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
    ];

    public const MICROWAVE_PHASE_OF_SAMPLE = [
        'fresh' => 'Fresh',
        'freeze_dried' => 'Freeze dried',
        'defatted' => 'Defatted',
    ];

    public const ELEMENTS = [
        'C' => 'Carbon (C)',
        'H' => 'Hydrogen (H)',
        'N' => 'Nitrogen (N)',
        'O' => 'Oxygen (O)',
        'S' => 'Sulfur (S)',
    ];

    public const MK_GC_MS_UNITS = [
        'percent' => '%',
        'mg_100g' => 'mg/100 g',
        'mg_g_fat' => 'mg/g fat',
    ];

    /**
     * Declarative field schema per record_type, used to render the details sub-form generically.
     * Field types: text | textarea | number | select | multiselect | user_select | fatty_acid_select | compound_combobox
     */
    public static function fieldSchema(string $recordType): array
    {
        $gcShared = [
            ['key' => 'instrument', 'label' => 'Instrument', 'type' => 'text'],
            ['key' => 'gc_column', 'label' => 'GC column', 'type' => 'text'],
            ['key' => 'flow', 'label' => 'Flow', 'type' => 'text'],
            ['key' => 'inlet_mode', 'label' => 'Inlet mode', 'type' => 'text'],
            ['key' => 'oven_program', 'label' => 'Oven program', 'type' => 'textarea'],
            ['key' => 'run_time', 'label' => 'Run time', 'type' => 'text'],
            ['key' => 'solvent_delay', 'label' => 'Solvent delay', 'type' => 'text'],
        ];

        $liquidInjection = [
            ['key' => 'mps_syringe', 'label' => 'MPS syringe', 'type' => 'text'],
            ['key' => 'analysis_method_type', 'label' => 'Analysis type', 'type' => 'text'],
            ['key' => 'inj_volume_ul', 'label' => 'Injection volume (µL)', 'type' => 'number'],
            ['key' => 'rinse_settings', 'label' => 'Rinse settings', 'type' => 'textarea'],
        ];

        $spme = [
            ['key' => 'mps_syringe', 'label' => 'MPS syringe', 'type' => 'text'],
            ['key' => 'analysis_method_type', 'label' => 'Analysis type', 'type' => 'text'],
            ['key' => 'type_of_fiber', 'label' => 'Type of fiber', 'type' => 'text'],
            ['key' => 'spme_parameters_min', 'label' => 'SPME parameters (min)', 'type' => 'text'],
            ['key' => 'fiber_bakeout_min', 'label' => 'Fiber bakeout (min)', 'type' => 'text'],
        ];

        $irmsStandards = [
            ['key' => 'standards_normalisation', 'label' => 'Standards used — normalisation', 'type' => 'textarea'],
            ['key' => 'standards_qc', 'label' => 'Standards used — QC', 'type' => 'textarea'],
            ['key' => 'normalisation_done_by', 'label' => 'Normalisation done by', 'type' => 'user_select'],
        ];

        return match ($recordType) {
            'sample_prep' => [
                ['key' => 'amount_of_sample_g', 'label' => 'Amount of sample (g)', 'type' => 'number'],
                ['key' => 'phase_of_sample', 'label' => 'Phase of sample', 'type' => 'select', 'options' => self::PHASE_OF_SAMPLE],
                ['key' => 'drying', 'label' => 'Drying', 'type' => 'select', 'options' => self::DRYING_OPTIONS],
                ['key' => 'homogenisation', 'label' => 'Homogenisation', 'type' => 'select', 'options' => self::HOMOGENISATION_OPTIONS],
                ['key' => 'sieving', 'label' => 'Sieving', 'type' => 'select', 'options' => self::YES_NO],
                ['key' => 'preparation_method', 'label' => 'Preparation method', 'type' => 'multiselect', 'options' => self::PREPARATION_METHODS],
                ['key' => 'protocol', 'label' => 'Protocol', 'type' => 'textarea'],
                ['key' => 'reference', 'label' => 'Reference', 'type' => 'text'],
            ],
            'sample_prep_microwave_digestion' => [
                ['key' => 'phase_of_sample', 'label' => 'Phase of sample', 'type' => 'select', 'options' => self::MICROWAVE_PHASE_OF_SAMPLE],
                ['key' => 'first_step_weighing', 'label' => 'First step — weighing', 'type' => 'text'],
                ['key' => 'microwave_program', 'label' => 'Microwave program', 'type' => 'textarea'],
                ['key' => 'second_step_dilution', 'label' => 'Second step — dilution', 'type' => 'text'],
            ],
            'analysis_isotopes' => [
                ['key' => 'analyte', 'label' => 'Analyte', 'type' => 'select', 'options' => self::ANALYTES],
                ['key' => 'instrument', 'label' => 'Instrument', 'type' => 'text'],
                ['key' => 'instrument_settings', 'label' => 'Instrument settings', 'type' => 'textarea'],
                ...$irmsStandards,
            ],
            'analysis_elemental_composition' => [
                ['key' => 'instrument', 'label' => 'Instrument', 'type' => 'text'],
                ['key' => 'standards_used', 'label' => 'Standards used', 'type' => 'textarea'],
                ['key' => 'results_reviewed_by', 'label' => 'Results reviewed by', 'type' => 'user_select'],
            ],
            'analysis_mk_gc_ms' => [
                ...$gcShared,
                ...$liquidInjection,
                ['key' => 'ms_mass', 'label' => 'MS mass', 'type' => 'text'],
                ['key' => 'standards_used', 'label' => 'Standards used', 'type' => 'textarea'],
                ['key' => 'compound_identification_done_by', 'label' => 'Compound identification done by', 'type' => 'user_select'],
            ],
            'analysis_voc_gc_ms' => [
                ...$gcShared,
                ...$spme,
                ['key' => 'ms_mass', 'label' => 'MS mass', 'type' => 'text'],
                ['key' => 'standards_used', 'label' => 'Standards used', 'type' => 'textarea'],
                ['key' => 'compound_identification_done_by', 'label' => 'Compound identification done by', 'type' => 'user_select'],
            ],
            'analysis_mk_gc_irms' => [
                ...$gcShared,
                ...$liquidInjection,
                ...$irmsStandards,
            ],
            'analysis_voc_gc_irms' => [
                ...$gcShared,
                ...$spme,
                ...$irmsStandards,
            ],
            'result_stable_isotopes' => [
                ['key' => 'analyte', 'label' => 'Analyte', 'type' => 'select', 'options' => self::ANALYTES],
                ['key' => 'value', 'label' => 'Value', 'type' => 'number'],
                ['key' => 'stdev', 'label' => 'Stdev', 'type' => 'number'],
                ['key' => 'unit', 'label' => 'Unit', 'type' => 'text', 'default' => '‰'],
            ],
            'result_elemental_composition' => [
                ['key' => 'element', 'label' => 'Element', 'type' => 'select', 'options' => self::ELEMENTS],
                ['key' => 'value', 'label' => 'Value', 'type' => 'number'],
                ['key' => 'stdev', 'label' => 'Stdev', 'type' => 'number'],
                ['key' => 'unit', 'label' => 'Unit', 'type' => 'text', 'default' => '%'],
            ],
            'result_mk_gc_ms' => [
                ['key' => 'compound_id', 'label' => 'Fatty acid', 'type' => 'fatty_acid_select'],
                ['key' => 'unit', 'label' => 'Unit', 'type' => 'select', 'options' => self::MK_GC_MS_UNITS],
                ['key' => 'value', 'label' => 'Value', 'type' => 'number'],
            ],
            'result_mk_gc_irms' => [
                ['key' => 'compound_id', 'label' => 'Fatty acid', 'type' => 'fatty_acid_select'],
                ['key' => 'value', 'label' => 'Value', 'type' => 'number'],
                ['key' => 'stdev', 'label' => 'Stdev', 'type' => 'number'],
                ['key' => 'unit', 'label' => 'Unit', 'type' => 'text', 'default' => '‰'],
            ],
            'result_voc_gc_ms' => [
                ['key' => 'compound_id', 'label' => 'Compound', 'type' => 'compound_combobox'],
                ['key' => 'value', 'label' => 'Value', 'type' => 'number'],
                ['key' => 'unit', 'label' => 'Unit', 'type' => 'text'],
            ],
            'result_voc_gc_irms' => [
                ['key' => 'compound_id', 'label' => 'Compound', 'type' => 'compound_combobox'],
                ['key' => 'value', 'label' => 'Value', 'type' => 'number'],
                ['key' => 'stdev', 'label' => 'Stdev', 'type' => 'number'],
                ['key' => 'unit', 'label' => 'Unit', 'type' => 'text', 'default' => '‰'],
            ],
            default => [],
        };
    }

    public static function familyOf(string $recordType): ?string
    {
        foreach (self::FAMILIES as $family => $types) {
            if (in_array($recordType, $types, true)) {
                return $family;
            }
        }
        return null;
    }

    public function recordTypeLabel(): string
    {
        return self::RECORD_TYPES[$this->record_type] ?? $this->record_type;
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function parentRecord(): BelongsTo
    {
        return $this->belongsTo(ExperimentRecord::class, 'parent_record_id');
    }

    public function childRecords(): HasMany
    {
        return $this->hasMany(ExperimentRecord::class, 'parent_record_id');
    }
}
