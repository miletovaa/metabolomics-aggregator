<?php

namespace Database\Seeders;

use App\Models\Compound;
use App\Models\CompoundSynonym;
use Illuminate\Database\Seeder;

class FattyAcidCompoundSeeder extends Seeder
{
    /**
     * Fatty acid panel used by the MK GC-MS / MK GC-IRMS result tables.
     * [oznaka (lab code), common name, CAS, lipid class]
     */
    private const FATTY_ACIDS = [
        ['C4:0', 'Butyric acid', '107-92-6', 'SFA'],
        ['C6:0', 'Caproic acid', '142-62-1', 'SFA'],
        ['C8:0', 'Caprylic acid', '124-07-2', 'SFA'],
        ['C10:0', 'Capric acid', '334-48-5', 'SFA'],
        ['C11:0', 'Undecanoic acid', '112-37-8', 'SFA'],
        ['C12:0', 'Lauric acid', '143-07-7', 'SFA'],
        ['C13:0', 'Tridecanoic acid', '638-53-9', 'SFA'],
        ['C14:0', 'Myristic acid', '544-63-8', 'SFA'],
        ['C14:1 cis-9', 'Myristoleic acid', '544-64-9', 'MUFA'],
        ['C15:0', 'Pentadecanoic acid', '1002-84-2', 'SFA'],
        ['C15:1 cis-10', 'Pentadecenoic acid', '2396-78-3', 'MUFA'],
        ['C16:0', 'Palmitic acid', '57-10-3', 'SFA'],
        ['C16:1 cis-9', 'Palmitoleic acid', '373-49-9', 'MUFA'],
        ['C17:0', 'Heptadecanoic acid', '506-12-7', 'SFA'],
        ['C17:1 cis-10', 'Heptadecenoic acid', '7316-37-0', 'MUFA'],
        ['C18:0', 'Stearic acid', '57-11-4', 'SFA'],
        ['C18:1 trans-9', 'Elaidic acid', '112-79-8', 'MUFA-trans'],
        ['C18:1 cis-9', 'Oleic acid', '112-80-1', 'MUFA'],
        ['C18:1 n-7 (cis-11)', 'Vaccenic acid', '506-17-2', 'MUFA'],
        ['C18:2 trans-9,12', 'trans-Linoleic acid isomers', '2420-56-6', 'PUFA-trans'],
        ['C18:2 cis-9,12', 'Linoleic acid', '60-33-3', 'PUFA (ω-6)'],
        ['C18:3 cis-6,9,12', 'γ-Linolenic acid', '506-26-3', 'PUFA (ω-6)'],
        ['C18:3 cis-9,12,15', 'α-Linolenic acid', '463-40-1', 'PUFA (ω-3)'],
        ['C20:0', 'Arachidic acid', '506-30-9', 'SFA'],
        ['C20:1 cis-11', 'Gadoleic acid', '29204-02-2', 'MUFA'],
        ['C20:2 cis-11,14', 'Eicosadienoic acid', '28845-84-5', 'PUFA'],
        ['C20:3 cis-8,11,14', 'Dihomo-γ-linolenic acid', '30564-75-1', 'PUFA (ω-6)'],
        ['C20:3 cis-11,14,17', 'Eicosatrienoic acid', '10417-93-3', 'PUFA (ω-3)'],
        ['C20:4 cis-5,8,11,14', 'Arachidonic acid', '506-32-1', 'PUFA (ω-6)'],
        ['C20:5 cis-5,8,11,14,17', 'Eicosapentaenoic acid (EPA)', '10417-94-4', 'PUFA (ω-3)'],
        ['C21:0', 'Heneicosanoic acid', '506-34-3', 'SFA'],
        ['C22:0', 'Behenic acid', '112-85-6', 'SFA'],
        ['C22:1 cis-13', 'Erucic acid', '112-86-7', 'MUFA'],
        ['C22:2 cis-13,16', 'Docosadienoic acid', '2465-32-9', 'PUFA'],
        ['C22:6 cis-4,7,10,13,16,19', 'Docosahexaenoic acid (DHA)', '6217-54-5', 'PUFA (ω-3)'],
        ['C23:0', 'Tricosanoic acid', '2433-96-7', 'SFA'],
        ['C24:0', 'Lignoceric acid', '638-90-0', 'SFA'],
        ['C24:1 cis-15', 'Nervonic acid', '506-37-6', 'MUFA'],
    ];

    public function run(): void
    {
        foreach (self::FATTY_ACIDS as [$code, $commonName, $cas, $lipidClass]) {
            $compound = Compound::firstOrCreate(
                ['cas' => $cas],
                ['canonical_name' => $commonName],
            );

            if (! $compound->lipid_class) {
                $compound->update(['lipid_class' => $lipidClass]);
            }

            CompoundSynonym::firstOrCreate([
                'compound_id' => $compound->id,
                'name' => $code,
            ]);

            if (strcasecmp($compound->canonical_name, $commonName) !== 0) {
                CompoundSynonym::firstOrCreate([
                    'compound_id' => $compound->id,
                    'name' => $commonName,
                ]);
            }
        }
    }
}
