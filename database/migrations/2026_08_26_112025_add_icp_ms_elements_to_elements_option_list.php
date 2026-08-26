<?php

use App\Models\OptionList;
use App\Models\OptionValue;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    // Symbol => full name, in the order they appear on the ICP-MS multi-element panel
    // report this list was added to support (rezultati-multielementa-analiza-*.xlsx).
    // 'S' (Sulfur) already exists from the original CHNOS list, so it's skipped here.
    private const ICP_MS_ELEMENTS = [
        'B' => 'Boron',
        'Na' => 'Sodium',
        'Mg' => 'Magnesium',
        'P' => 'Phosphorus',
        'K' => 'Potassium',
        'Ca' => 'Calcium',
        'Mn' => 'Manganese',
        'Fe' => 'Iron',
        'Co' => 'Cobalt',
        'Cu' => 'Copper',
        'Zn' => 'Zinc',
        'As' => 'Arsenic',
        'Se' => 'Selenium',
        'Rb' => 'Rubidium',
        'Sr' => 'Strontium',
        'Mo' => 'Molybdenum',
        'Cd' => 'Cadmium',
        'Sb' => 'Antimony',
        'Cs' => 'Cesium',
        'Ba' => 'Barium',
        'Hg' => 'Mercury',
        'Pb' => 'Lead',
    ];

    public function up(): void
    {
        $list = OptionList::where('key', 'elements')->first();
        if (! $list) {
            return;
        }

        $nextOrder = (int) $list->values()->max('sort_order') + 1;

        foreach (self::ICP_MS_ELEMENTS as $symbol => $name) {
            $exists = $list->values()->where('key', $symbol)->exists();
            if ($exists) {
                continue;
            }

            OptionValue::create([
                'option_list_id' => $list->id,
                'key' => $symbol,
                'label' => "{$name} ({$symbol})",
                'sort_order' => $nextOrder++,
            ]);
        }

        OptionList::forgetCache('elements');
    }

    public function down(): void
    {
        $list = OptionList::where('key', 'elements')->first();
        if (! $list) {
            return;
        }

        $list->values()->whereIn('key', array_keys(self::ICP_MS_ELEMENTS))->delete();

        OptionList::forgetCache('elements');
    }
};
