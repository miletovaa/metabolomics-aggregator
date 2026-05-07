<?php

namespace App\Console\Commands;

use App\Models\Compound;
use App\Models\Source;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchPubChemData extends Command
{
    protected $signature = 'compounds:fetch-pubchem
                            {--skip-cid        : Skip phase 1 (CID lookup)}
                            {--skip-properties : Skip phase 2 (property fetch)}
                            {--deduplicate     : Remove duplicate compounds sharing the same pubchem_cid}
                            {--chunk=100       : Number of compounds per DB chunk}
                            {--limit=          : Stop after processing this many compounds (per phase)}';

    protected $description = 'Fetch PubChem CIDs and properties (IUPAC name, formula, CAS, ChEBI) for all compounds';

    private const BASE_URL = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound';

    // PubChem rate limit: 5 req/s. We stay at ~4 req/s.
    private const DELAY_US = 250_000;

    private const SYNONYM_LIMIT = 30;

    private ?int $pubchemSourceId = null;

    public function handle(): int
    {
        if ($this->option('deduplicate')) {
            $this->deduplicateCompounds();
        }

        if (! $this->option('skip-cid')) {
            $this->fetchCids();
        }

        if (! $this->option('skip-properties')) {
            $this->fetchProperties();
        }

        $this->info('Done.');

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Deduplication
    // -------------------------------------------------------------------------

    private function deduplicateCompounds(): void
    {
        $chunkSize = (int) $this->option('chunk');
        $total     = Compound::whereNull('pubchem_cid')->count();

        $this->info("Deduplication: checking {$total} compound(s) without pubchem_cid against PubChem");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $deleted = 0;
        $updated = 0;

        Compound::whereNull('pubchem_cid')
            ->with('synonyms')
            ->chunkById($chunkSize, function ($compounds) use ($bar, &$deleted, &$updated) {
                foreach ($compounds as $compound) {
                    $cid = $this->lookupCidWithFallbacks($compound->canonical_name);

                    if (! $cid) {
                        foreach ($compound->synonyms as $synonym) {
                            $cid = $this->lookupCidWithFallbacks($synonym->name);
                            if ($cid) {
                                break;
                            }
                        }
                    }

                    if (! $cid) {
                        $bar->advance();
                        continue;
                    }

                    $existing = Compound::where('pubchem_cid', $cid)
                        ->where('id', '!=', $compound->id)
                        ->value('id');

                    if ($existing) {
                        try {
                            $compound->delete();
                            $deleted++;
                            $bar->clear();
                            $this->warn("Deleted id={$compound->id} ({$compound->canonical_name}) — CID {$cid} already owned by id={$existing}");
                            $bar->display();
                        } catch (\Exception $e) {
                            $bar->clear();
                            $this->warn("Could not delete id={$compound->id}: {$e->getMessage()}");
                            $bar->display();
                        }
                    } else {
                        $properties = $this->fetchPropertyData($cid);
                        usleep(self::DELAY_US);
                        $synonyms = $this->fetchSynonymList($cid);
                        usleep(self::DELAY_US);

                        $update = array_filter([
                            'pubchem_cid'       => $cid,
                            'iupac_name'        => $properties['IUPACName'] ?? null,
                            'molecular_formula' => $properties['MolecularFormula'] ?? null,
                            'smiles'            => $properties['IsomericSMILES'] ?? null,
                            'inchi'             => $properties['InChI'] ?? null,
                            'inchikey'          => $properties['InChIKey'] ?? null,
                            'cas'               => $this->extractCas($synonyms),
                            'chebi_id'          => $this->extractChebi($synonyms),
                        ], static fn($v) => $v !== null);

                        try {
                            $compound->update($update);
                            $updated++;
                        } catch (\Exception $e) {
                            $bar->clear();
                            $this->warn("Could not update id={$compound->id} (cid={$cid}): {$e->getMessage()}");
                            $bar->display();
                        }

                        $this->storeSynonyms($compound, $synonyms);
                    }

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info("Deduplication: removed {$deleted}, enriched {$updated} compound(s).");
    }

    // -------------------------------------------------------------------------
    // Phase 1 — CID lookup
    // -------------------------------------------------------------------------

    private function fetchCids(): void
    {
        $limit     = $this->option('limit') ? (int) $this->option('limit') : null;
        $chunkSize = (int) $this->option('chunk');
        $total     = Compound::whereNull('pubchem_cid')->count();
        $capped    = $limit !== null ? min($limit, $total) : $total;

        $this->info("Phase 1: resolving CIDs for {$capped} compound(s) (chunk={$chunkSize})");
        $bar = $this->output->createProgressBar($capped);
        $bar->start();

        $processed = 0;

        Compound::whereNull('pubchem_cid')
            ->with('synonyms')
            ->chunkById($chunkSize, function ($compounds) use ($bar, $limit, &$processed) {
                foreach ($compounds as $compound) {
                    if ($limit !== null && $processed >= $limit) {
                        return false; // stop chunking
                    }

                    $cid = $this->lookupCidWithFallbacks($compound->canonical_name);

                    if (! $cid) {
                        foreach ($compound->synonyms as $synonym) {
                            $cid = $this->lookupCidWithFallbacks($synonym->name);
                            if ($cid) {
                                break;
                            }
                        }
                    }

                    if ($cid) {
                        $conflict = Compound::where('pubchem_cid', $cid)
                            ->where('id', '!=', $compound->id)
                            ->value('id');

                        if ($conflict) {
                            $bar->clear();
                            $this->warn("Duplicate CID {$cid}: id={$compound->id} ({$compound->canonical_name}) conflicts with id={$conflict} — skipping");
                            $bar->display();
                        } else {
                            $compound->update(['pubchem_cid' => $cid]);
                        }
                    } else {
                        $bar->clear();
                        $this->warn("Not found: {$compound->canonical_name} (id={$compound->id})");
                        $bar->display();
                    }

                    $bar->advance();
                    $processed++;
                }
            });

        $bar->finish();
        $this->newLine();
    }

    private function lookupCidByName(string $name): ?string
    {
        $encoded = rawurlencode($name);

        try {
            $response = Http::timeout(15)
                ->get(self::BASE_URL . "/name/{$encoded}/synonyms/JSON");

            if ($response->successful()) {
                $cid = $response->json('InformationList.Information.0.CID');

                return $cid !== null ? (string) $cid : null;
            }
        } catch (\Exception $e) {
            Log::warning("PubChem CID lookup failed for '{$name}': {$e->getMessage()}");
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Phase 2 — property fetch
    // -------------------------------------------------------------------------

    private function fetchProperties(): void
    {
        $limit     = $this->option('limit') ? (int) $this->option('limit') : null;
        $chunkSize = (int) $this->option('chunk');
        $total     = Compound::whereNotNull('pubchem_cid')->count();
        $capped    = $limit !== null ? min($limit, $total) : $total;

        $this->info("Phase 2: fetching properties for {$capped} compound(s) (chunk={$chunkSize})");
        $bar = $this->output->createProgressBar($capped);
        $bar->start();

        $processed = 0;

        Compound::whereNotNull('pubchem_cid')
            ->chunkById($chunkSize, function ($compounds) use ($bar, $limit, &$processed) {
                foreach ($compounds as $compound) {
                    if ($limit !== null && $processed >= $limit) {
                        return false;
                    }

                    $cid        = $compound->pubchem_cid;
                    $properties = $this->fetchPropertyData($cid);
                    usleep(self::DELAY_US);
                    $synonyms = $this->fetchSynonymList($cid);
                    usleep(self::DELAY_US);

                    $update = array_filter([
                        'iupac_name'        => $properties['IUPACName'] ?? null,
                        'molecular_formula' => $properties['MolecularFormula'] ?? null,
                        'smiles'            => $properties['IsomericSMILES'] ?? null,
                        'inchi'             => $properties['InChI'] ?? null,
                        'inchikey'          => $properties['InChIKey'] ?? null,
                        'cas'               => $this->extractCas($synonyms),
                        'chebi_id'          => $this->extractChebi($synonyms),
                    ], static fn($v) => $v !== null);

                    if ($update) {
                        try {
                            $compound->update($update);
                        } catch (\Exception $e) {
                            $bar->clear();
                            $this->warn("Could not update id={$compound->id} (cid={$cid}): {$e->getMessage()}");
                            $bar->display();
                        }
                    }

                    $this->storeSynonyms($compound, $synonyms);

                    $bar->advance();
                    $processed++;
                }
            });

        $bar->finish();
        $this->newLine();
    }

    private function fetchPropertyData(string $cid): array
    {
        try {
            $response = Http::timeout(15)
                ->get(self::BASE_URL . "/cid/{$cid}/property/IUPACName,MolecularFormula,IsomericSMILES,InChI,InChIKey/JSON");

            if ($response->successful()) {
                return $response->json('PropertyTable.Properties.0') ?? [];
            }
        } catch (\Exception $e) {
            Log::warning("PubChem properties fetch failed for CID {$cid}: {$e->getMessage()}");
        }

        return [];
    }

    private function fetchSynonymList(string $cid): array
    {
        try {
            $response = Http::timeout(15)
                ->get(self::BASE_URL . "/cid/{$cid}/synonyms/JSON");

            if ($response->successful()) {
                return $response->json('InformationList.Information.0.Synonym') ?? [];
            }
        } catch (\Exception $e) {
            Log::warning("PubChem synonyms fetch failed for CID {$cid}: {$e->getMessage()}");
        }

        return [];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function extractCas(array $synonyms): ?string
    {
        foreach ($synonyms as $synonym) {
            if (preg_match('/^\d{1,7}-\d{2}-\d$/', (string) $synonym)) {
                return $synonym;
            }
        }

        return null;
    }

    private function extractChebi(array $synonyms): ?string
    {
        foreach ($synonyms as $synonym) {
            if (preg_match('/^CHEBI:\d+$/i', (string) $synonym)) {
                return strtoupper($synonym);
            }
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Synonym storage
    // -------------------------------------------------------------------------

    private function getPubchemSourceId(): int
    {
        if ($this->pubchemSourceId === null) {
            $this->pubchemSourceId = Source::firstOrCreate(['name' => 'PubChem'])->id;
        }

        return $this->pubchemSourceId;
    }

    private function storeSynonyms(Compound $compound, array $synonyms): void
    {
        if (empty($synonyms)) {
            return;
        }

        $sourceId = $this->getPubchemSourceId();
        $now      = now();
        $rows     = [];

        foreach (array_slice($synonyms, 0, self::SYNONYM_LIMIT) as $synonym) {
            // Skip names that exceed the column length constraint.
            if (mb_strlen($synonym) > 255) {
                continue;
            }

            $rows[] = [
                'compound_id' => $compound->id,
                'name'        => $synonym,
                'source_id'   => $sourceId,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }

        if (!empty($rows)) {
            DB::table('compound_synonyms')->insertOrIgnore($rows);
        }
    }

    // -------------------------------------------------------------------------
    // Name normalisation
    // -------------------------------------------------------------------------

    private function lookupCidWithFallbacks(string $name): ?string
    {
        $cid = $this->lookupCidByName($name);
        usleep(self::DELAY_US);
        if ($cid) {
            return $cid;
        }

        foreach ($this->generateNameVariants($name) as $variant) {
            $cid = $this->lookupCidByName($variant);
            usleep(self::DELAY_US);
            if ($cid) {
                return $cid;
            }
        }

        return null;
    }

    private function generateNameVariants(string $name): array
    {
        $variants = [];

        $greekToAscii = $this->convertGreekToAscii($name);
        $asciiToGreek = $this->convertAsciiToGreek($name);

        if ($greekToAscii !== $name) {
            $variants[] = $greekToAscii;
        }
        if ($asciiToGreek !== $name) {
            $variants[] = $asciiToGreek;
        }

        // Try CAS-style uninversion for the original name and both greek conversions.
        foreach (array_unique([$name, $greekToAscii, $asciiToGreek]) as $base) {
            foreach ($this->uninvertCasName($base) as $uninverted) {
                $variants[] = $uninverted;

                $g = $this->convertGreekToAscii($uninverted);
                if ($g !== $uninverted) {
                    $variants[] = $g;
                }
                $g = $this->convertAsciiToGreek($uninverted);
                if ($g !== $uninverted) {
                    $variants[] = $g;
                }
            }
        }

        return array_values(array_unique(array_filter(
            $variants,
            static fn($v) => $v !== $name && $v !== '',
        )));
    }

    private function convertGreekToAscii(string $name): string
    {
        static $map = [
            'α' => 'alpha',   'β' => 'beta',    'γ' => 'gamma',   'δ' => 'delta',
            'ε' => 'epsilon', 'ζ' => 'zeta',    'η' => 'eta',     'θ' => 'theta',
            'ι' => 'iota',    'κ' => 'kappa',   'λ' => 'lambda',  'μ' => 'mu',
            'ν' => 'nu',      'ξ' => 'xi',      'ο' => 'omicron', 'π' => 'pi',
            'ρ' => 'rho',     'σ' => 'sigma',   'τ' => 'tau',     'υ' => 'upsilon',
            'φ' => 'phi',     'χ' => 'chi',     'ψ' => 'psi',     'ω' => 'omega',
            'Α' => 'Alpha',   'Β' => 'Beta',    'Γ' => 'Gamma',   'Δ' => 'Delta',
            'Ε' => 'Epsilon', 'Ζ' => 'Zeta',    'Η' => 'Eta',     'Θ' => 'Theta',
            'Ι' => 'Iota',    'Κ' => 'Kappa',   'Λ' => 'Lambda',  'Μ' => 'Mu',
            'Ν' => 'Nu',      'Ξ' => 'Xi',      'Ο' => 'Omicron', 'Π' => 'Pi',
            'Ρ' => 'Rho',     'Σ' => 'Sigma',   'Τ' => 'Tau',     'Υ' => 'Upsilon',
            'Φ' => 'Phi',     'Χ' => 'Chi',     'Ψ' => 'Psi',     'Ω' => 'Omega',
        ];

        return strtr($name, $map);
    }

    private function convertAsciiToGreek(string $name): string
    {
        static $map = [
            'epsilon' => 'ε', 'upsilon' => 'υ', 'omicron' => 'ο',
            'lambda'  => 'λ', 'alpha'   => 'α', 'gamma'   => 'γ',
            'delta'   => 'δ', 'theta'   => 'θ', 'sigma'   => 'σ',
            'omega'   => 'ω', 'kappa'   => 'κ', 'iota'    => 'ι',
            'zeta'    => 'ζ', 'beta'    => 'β', 'eta'     => 'η',
            'phi'     => 'φ', 'psi'     => 'ψ', 'chi'     => 'χ',
            'rho'     => 'ρ', 'tau'     => 'τ', 'xi'      => 'ξ',
            'nu'      => 'ν', 'mu'      => 'μ', 'pi'      => 'π',
        ];

        // Built once per process; sorted longest-first to prevent partial matches
        // (e.g. "eta" must not match inside "zeta", "theta", etc.).
        static $pattern = null;
        if ($pattern === null) {
            $terms = array_keys($map);
            usort($terms, static fn($a, $b) => strlen($b) - strlen($a));
            $pattern = '/(?<![a-zA-Z])(' . implode('|', $terms) . ')(?![a-zA-Z])/i';
        }

        return preg_replace_callback(
            $pattern,
            static fn($m) => $map[strtolower($m[1])],
            $name,
        );
    }

    /**
     * Expand CAS-style inverted names ("Parent, modifier-") into forward forms.
     *
     * "Aldehyde, alpha-methylene-" → ["alpha-methylene-Aldehyde",
     *                                  "alpha-methylene-aldehyde",
     *                                  "alpha-methylenealdehyde", ...]
     */
    private function uninvertCasName(string $name): array
    {
        $commaPos = strpos($name, ',');
        if ($commaPos === false) {
            return [];
        }

        $parent   = trim(substr($name, 0, $commaPos));
        $modifier = trim(substr($name, $commaPos + 1));

        // Require a multi-character parent (guards against "1,2-…" locant commas).
        if (strlen($parent) < 3 || $modifier === '') {
            return [];
        }

        $variants = [];

        if (str_ends_with($modifier, '-')) {
            // Substituent prefix — join directly with parent name.
            $stem       = rtrim($modifier, '-');
            $variants[] = $modifier . $parent;
            $variants[] = $modifier . strtolower($parent);
            $variants[] = $stem . $parent;
            $variants[] = $stem . strtolower($parent);
            $variants[] = $stem . ' ' . $parent;
            $variants[] = $stem . ' ' . strtolower($parent);
        } else {
            // Descriptor / locant — insert a space.
            $variants[] = $modifier . ' ' . $parent;
            $variants[] = $modifier . ' ' . strtolower($parent);
        }

        return array_values(array_unique(array_filter(
            $variants,
            static fn($v) => $v !== $name && $v !== '',
        )));
    }
}
