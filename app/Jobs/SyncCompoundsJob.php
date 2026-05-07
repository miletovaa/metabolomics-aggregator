<?php

namespace App\Jobs;

use App\Models\Compound;
use App\Models\Source;
use App\Services\CompoundMappingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncCompoundsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int  $timeout       = 7200;
    public int  $tries         = 1;
    public bool $failOnTimeout = true;

    private const BASE_URL      = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound';
    private const DELAY_US      = 250_000;
    private const SYNONYM_LIMIT = 30;
    private const CACHE_KEY     = 'sync_compounds_global';

    public static function cacheKey(): string
    {
        return self::CACHE_KEY;
    }

    public function handle(CompoundMappingService $svc): void
    {
        $pubchemSourceId = Source::firstOrCreate(['name' => 'PubChem'])->id;

        // ── Phase 1: PubChem CID lookup for compounds without one ─────────────
        $phase1 = Compound::whereNull('pubchem_cid')->with('synonyms')->get();
        // ── Phase 2: Re-fetch PubChem properties for all that have a CID ──────
        $phase2 = Compound::whereNotNull('pubchem_cid')->get();
        // ── Phase 3: ChEBI enrichment for all compounds ───────────────────────
        $phase3 = Compound::all();
        // ── Phase 4: HMDB enrichment for compounds without description ─────────
        $phase4 = Compound::whereNull('description')->get();
        // ── Phase 5: NIST RI for compounds with InChI but no polar RI ─────────
        $phase5 = Compound::whereNotNull('inchi')
            ->whereDoesntHave('retentionIndices', fn($q) => $q->where('is_polar', true))
            ->get();
        // ── Phase 6: ClassyFire taxonomy for compounds with InChIKey, no taxonomy
        $phase6 = Compound::whereNotNull('inchikey')
            ->whereDoesntHave('taxonomy')
            ->get();

        $total     = $phase1->count() + $phase2->count() + $phase3->count()
                   + $phase4->count() + $phase5->count() + $phase6->count();
        $processed = 0;
        $enriched  = 0;
        $failed    = 0;

        $this->writeProgress('running', 0, $total, 'Starting…', $enriched, $failed);

        // ── Phase 1 ───────────────────────────────────────────────────────────
        foreach ($phase1 as $compound) {
            $cid = $this->lookupCidWithFallbacks($compound->canonical_name);

            if (!$cid) {
                foreach ($compound->synonyms as $syn) {
                    $cid = $this->lookupCidWithFallbacks($syn->name);
                    if ($cid) break;
                }
            }

            if ($cid) {
                $conflict = Compound::where('pubchem_cid', $cid)
                    ->where('id', '!=', $compound->id)
                    ->exists();

                if (!$conflict) {
                    $props    = $this->fetchPropertyData($cid);
                    usleep(self::DELAY_US);
                    $synonyms = $this->fetchSynonymList($cid);
                    usleep(self::DELAY_US);

                    $update = array_filter([
                        'pubchem_cid'       => $cid,
                        'iupac_name'        => $props['IUPACName']        ?? null,
                        'molecular_formula' => $props['MolecularFormula'] ?? null,
                        'smiles'            => $props['IsomericSMILES']   ?? null,
                        'inchi'             => $props['InChI']            ?? null,
                        'inchikey'          => $props['InChIKey']         ?? null,
                        'cas'               => $this->extractCas($synonyms),
                        'chebi_id'          => $this->extractChebi($synonyms),
                    ], static fn($v) => $v !== null);

                    try {
                        $compound->update($update);
                        $this->storeSynonyms($compound, $synonyms, $pubchemSourceId);
                        $enriched++;
                    } catch (\Throwable $e) {
                        $failed++;
                        Log::error('SyncCompoundsJob phase1 failed', ['id' => $compound->id, 'error' => $e->getMessage()]);
                    }
                }
            } else {
                $failed++;
            }

            $processed++;
            $this->writeProgress('running', $processed, $total, "[PubChem CID] {$compound->canonical_name}", $enriched, $failed);
        }

        // ── Phase 2 ───────────────────────────────────────────────────────────
        foreach ($phase2 as $compound) {
            $cid      = $compound->pubchem_cid;
            $props    = $this->fetchPropertyData($cid);
            usleep(self::DELAY_US);
            $synonyms = $this->fetchSynonymList($cid);
            usleep(self::DELAY_US);

            $update = array_filter([
                'iupac_name'        => $props['IUPACName']        ?? null,
                'molecular_formula' => $props['MolecularFormula'] ?? null,
                'smiles'            => $props['IsomericSMILES']   ?? null,
                'inchi'             => $props['InChI']            ?? null,
                'inchikey'          => $props['InChIKey']         ?? null,
                'cas'               => $this->extractCas($synonyms),
                'chebi_id'          => $this->extractChebi($synonyms),
            ], static fn($v) => $v !== null);

            if ($update) {
                try {
                    $compound->update($update);
                    $this->storeSynonyms($compound, $synonyms, $pubchemSourceId);
                    $enriched++;
                } catch (\Throwable $e) {
                    $failed++;
                    Log::error('SyncCompoundsJob phase2 failed', ['id' => $compound->id, 'error' => $e->getMessage()]);
                }
            }

            $processed++;
            $this->writeProgress('running', $processed, $total, "[PubChem props] {$compound->canonical_name}", $enriched, $failed);
        }

        // ── Phase 3: ChEBI ────────────────────────────────────────────────────
        foreach ($phase3 as $compound) {
            try {
                if ($svc->enrichFromChebi($compound)) {
                    $enriched++;
                }
                usleep(self::DELAY_US);
            } catch (\Throwable $e) {
                $failed++;
                Log::error('SyncCompoundsJob phase3 (ChEBI) failed', ['id' => $compound->id, 'error' => $e->getMessage()]);
            }

            $processed++;
            $this->writeProgress('running', $processed, $total, "[ChEBI] {$compound->canonical_name}", $enriched, $failed);
        }

        // ── Phase 4: HMDB ─────────────────────────────────────────────────────
        foreach ($phase4 as $compound) {
            try {
                $compound->refresh();
                $hmdbData = $svc->lookupHmdb($compound);
                if ($hmdbData) {
                    $svc->enrichFromHmdbData($compound, $hmdbData);
                    $enriched++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::error('SyncCompoundsJob phase4 (HMDB) failed', ['id' => $compound->id, 'error' => $e->getMessage()]);
            }

            $processed++;
            $this->writeProgress('running', $processed, $total, "[HMDB] {$compound->canonical_name}", $enriched, $failed);
        }

        // ── Phase 5: NIST RI ──────────────────────────────────────────────────
        foreach ($phase5 as $compound) {
            try {
                $svc->enrichNistRi($compound, $compound->canonical_name ?? '');
                $enriched++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('SyncCompoundsJob phase5 (NIST) failed', ['id' => $compound->id, 'error' => $e->getMessage()]);
            }

            $processed++;
            $this->writeProgress('running', $processed, $total, "[NIST] {$compound->canonical_name}", $enriched, $failed);
        }

        // ── Phase 6: ClassyFire taxonomy ──────────────────────────────────────
        foreach ($phase6 as $compound) {
            try {
                $compound->refresh();
                if ($compound->inchikey) {
                    $taxData = $svc->lookupClassyFire($compound->inchikey);
                    if ($taxData) {
                        $svc->storeTaxonomy($compound, $taxData, $svc->sourceId('ClassyFire'));
                        $enriched++;
                    }
                    usleep(self::DELAY_US);
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::error('SyncCompoundsJob phase6 (ClassyFire) failed', ['id' => $compound->id, 'error' => $e->getMessage()]);
            }

            $processed++;
            $this->writeProgress('running', $processed, $total, "[ClassyFire] {$compound->canonical_name}", $enriched, $failed);
        }

        $this->writeProgress('done', $total, $total, '', $enriched, $failed);
    }

    public function failed(\Throwable $e): void
    {
        $this->writeProgress('error', 0, 0, $e->getMessage(), 0, 0);
        Log::error('SyncCompoundsJob crashed', ['error' => $e->getMessage()]);
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function writeProgress(string $status, int $processed, int $total, string $current, int $enriched, int $failed): void
    {
        Cache::put(
            self::CACHE_KEY,
            compact('status', 'processed', 'total', 'current', 'enriched', 'failed'),
            now()->addHours(3),
        );
    }

    private function lookupCidWithFallbacks(string $name): ?string
    {
        $cid = $this->lookupCidByName($name);
        usleep(self::DELAY_US);
        if ($cid) return $cid;

        foreach ($this->generateNameVariants($name) as $variant) {
            $cid = $this->lookupCidByName($variant);
            usleep(self::DELAY_US);
            if ($cid) return $cid;
        }

        return null;
    }

    private function lookupCidByName(string $name): ?string
    {
        try {
            $response = Http::timeout(15)
                ->get(self::BASE_URL . '/name/' . rawurlencode($name) . '/synonyms/JSON');
            if ($response->successful()) {
                $cid = $response->json('InformationList.Information.0.CID');
                return $cid !== null ? (string) $cid : null;
            }
        } catch (\Throwable $e) {
            Log::warning("PubChem CID lookup failed for '{$name}': {$e->getMessage()}");
        }
        return null;
    }

    private function fetchPropertyData(string $cid): array
    {
        try {
            $response = Http::timeout(15)
                ->get(self::BASE_URL . "/cid/{$cid}/property/IUPACName,MolecularFormula,IsomericSMILES,InChI,InChIKey/JSON");
            if ($response->successful()) {
                return $response->json('PropertyTable.Properties.0') ?? [];
            }
        } catch (\Throwable $e) {
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
        } catch (\Throwable $e) {
            Log::warning("PubChem synonyms fetch failed for CID {$cid}: {$e->getMessage()}");
        }
        return [];
    }

    private function storeSynonyms(Compound $compound, array $synonyms, int $sourceId): void
    {
        if (empty($synonyms)) return;
        $now  = now();
        $rows = [];
        foreach (array_slice($synonyms, 0, self::SYNONYM_LIMIT) as $synonym) {
            if (mb_strlen($synonym) > 255) continue;
            $rows[] = ['compound_id' => $compound->id, 'name' => $synonym, 'source_id' => $sourceId, 'created_at' => $now, 'updated_at' => $now];
        }
        if (!empty($rows)) {
            DB::table('compound_synonyms')->insertOrIgnore($rows);
        }
    }

    private function extractCas(array $synonyms): ?string
    {
        foreach ($synonyms as $synonym) {
            if (preg_match('/^\d{1,7}-\d{2}-\d$/', (string) $synonym)) return $synonym;
        }
        return null;
    }

    private function extractChebi(array $synonyms): ?string
    {
        foreach ($synonyms as $synonym) {
            if (preg_match('/^CHEBI:\d+$/i', (string) $synonym)) return strtoupper($synonym);
        }
        return null;
    }

    private function generateNameVariants(string $name): array
    {
        $variants     = [];
        $greekToAscii = $this->convertGreekToAscii($name);
        $asciiToGreek = $this->convertAsciiToGreek($name);
        if ($greekToAscii !== $name) $variants[] = $greekToAscii;
        if ($asciiToGreek !== $name) $variants[] = $asciiToGreek;
        foreach (array_unique([$name, $greekToAscii, $asciiToGreek]) as $base) {
            foreach ($this->uninvertCasName($base) as $uninverted) {
                $variants[] = $uninverted;
                $g = $this->convertGreekToAscii($uninverted);
                if ($g !== $uninverted) $variants[] = $g;
                $g = $this->convertAsciiToGreek($uninverted);
                if ($g !== $uninverted) $variants[] = $g;
            }
        }
        return array_values(array_unique(array_filter($variants, static fn($v) => $v !== $name && $v !== '')));
    }

    private function convertGreekToAscii(string $name): string
    {
        static $map = [
            'α' => 'alpha', 'β' => 'beta',  'γ' => 'gamma',   'δ' => 'delta',
            'ε' => 'epsilon','ζ' => 'zeta', 'η' => 'eta',     'θ' => 'theta',
            'ι' => 'iota',  'κ' => 'kappa', 'λ' => 'lambda',  'μ' => 'mu',
            'ν' => 'nu',    'ξ' => 'xi',    'ο' => 'omicron', 'π' => 'pi',
            'ρ' => 'rho',   'σ' => 'sigma', 'τ' => 'tau',     'υ' => 'upsilon',
            'φ' => 'phi',   'χ' => 'chi',   'ψ' => 'psi',     'ω' => 'omega',
        ];
        return strtr($name, $map);
    }

    private function convertAsciiToGreek(string $name): string
    {
        static $map = [
            'epsilon' => 'ε', 'upsilon' => 'υ', 'omicron' => 'ο', 'lambda' => 'λ',
            'alpha'   => 'α', 'gamma'   => 'γ', 'delta'   => 'δ', 'theta'  => 'θ',
            'sigma'   => 'σ', 'omega'   => 'ω', 'kappa'   => 'κ', 'iota'   => 'ι',
            'zeta'    => 'ζ', 'beta'    => 'β', 'eta'     => 'η', 'phi'    => 'φ',
            'psi'     => 'ψ', 'chi'     => 'χ', 'rho'     => 'ρ', 'tau'    => 'τ',
            'xi'      => 'ξ', 'nu'      => 'ν', 'mu'      => 'μ', 'pi'     => 'π',
        ];
        static $pattern = null;
        if ($pattern === null) {
            $terms = array_keys($map);
            usort($terms, static fn($a, $b) => strlen($b) - strlen($a));
            $pattern = '/(?<![a-zA-Z])(' . implode('|', $terms) . ')(?![a-zA-Z])/i';
        }
        return preg_replace_callback($pattern, static fn($m) => $map[strtolower($m[1])], $name);
    }

    private function uninvertCasName(string $name): array
    {
        $commaPos = strpos($name, ',');
        if ($commaPos === false) return [];
        $parent   = trim(substr($name, 0, $commaPos));
        $modifier = trim(substr($name, $commaPos + 1));
        if (strlen($parent) < 3 || $modifier === '') return [];
        $variants = [];
        if (str_ends_with($modifier, '-')) {
            $stem       = rtrim($modifier, '-');
            $variants[] = $modifier . $parent;
            $variants[] = $modifier . strtolower($parent);
            $variants[] = $stem . $parent;
            $variants[] = $stem . strtolower($parent);
            $variants[] = $stem . ' ' . $parent;
            $variants[] = $stem . ' ' . strtolower($parent);
        } else {
            $variants[] = $modifier . ' ' . $parent;
            $variants[] = $modifier . ' ' . strtolower($parent);
        }
        return array_values(array_unique(array_filter($variants, static fn($v) => $v !== $name && $v !== '')));
    }
}
