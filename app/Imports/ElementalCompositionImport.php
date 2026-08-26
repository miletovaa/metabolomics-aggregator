<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Raw-cell import — this report's layout (header/QC block, a variable number of reference
 * standard blocks, then a per-sample results table, all in one sheet) doesn't fit a plain
 * heading-row table, so rows are handed to ElementalCompositionImporter as-is for structural
 * parsing rather than mapped by column heading.
 */
class ElementalCompositionImport implements ToCollection
{
    public ?Collection $rows = null;

    public function collection(Collection $rows): void
    {
        $this->rows = $rows;
    }
}
