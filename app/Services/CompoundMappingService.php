<?php

namespace App\Services;

use App\Models\Biomarker;
use App\Models\Compound;
use App\Models\CompoundBiomarker;
use App\Models\CompoundDiseaseAssociation;
use App\Models\CompoundOntology;
use App\Models\Disease;
use App\Models\Ontology;
use App\Models\ProjectCompound;
use App\Models\RetentionIndex;
use App\Models\Source;
use App\Models\Taxonomy;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PDO;

class CompoundMappingService
{
    private const PUBCHEM_BASE    = 'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound';
    private const CHEBI_BASE      = 'https://www.ebi.ac.uk/chebi/backend/api/public';
    private const CLASSYFIRE_BASE = 'http://classyfire.wishartlab.com/entities';

    /**
     * Greek-letter → ASCII transliteration (mirrors the Python workflow).
     */
    private const GREEK_MAP = [
        'α' => 'alpha',   'β' => 'beta',    'γ' => 'gamma',   'δ' => 'delta',
        'ε' => 'epsilon', 'ζ' => 'zeta',    'η' => 'eta',     'θ' => 'theta',
        'ι' => 'iota',    'κ' => 'kappa',   'λ' => 'lambda',  'μ' => 'mu',
        'ν' => 'nu',      'ξ' => 'xi',      'ο' => 'omicron', 'π' => 'pi',
        'ρ' => 'rho',     'σ' => 'sigma',   'τ' => 'tau',     'υ' => 'upsilon',
        'φ' => 'phi',     'χ' => 'chi',     'ψ' => 'psi',     'ω' => 'omega',
    ];

    private const PREFIX_MODIFIERS = [
        'alpha', 'beta', 'gamma', 'delta',
        'ortho', 'meta', 'para',
        'o', 'm', 'p',
    ];

    // Lazy-loaded SQLite connections
    private ?PDO $hmdbPdo = null;
    private ?PDO $nistPdo = null;

    // Source ID cache to avoid repeated DB hits
    private array $sourceIds = [];

    // ─────────────────────────────────────────────────────────────────────────
    // Public entry point
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Map a single ProjectCompound row to a Compound record.
     * Creates / enriches the Compound as needed.
     *
     * Returns true if the row was successfully mapped.
     */
    public function mapProjectCompound(ProjectCompound $row): bool
    {
        $name = trim($row->input_name ?? $row->custom_name ?? '');
        if ($name === '') {
            return false;
        }

        // ── Step 0: local DB lookup ──────────────────────────────────────────
        $compound = $this->findLocalByName($name);
        if ($compound) {
            $row->update(['compound_id' => $compound->id, 'is_mapped' => true]);
            return true;
        }

        // ── Step 1: PubChem (by name, normalized name, reordered normalized name)
        $pubchemData = $this->lookupPubChem($name);

        if ($pubchemData) {
            $compound = $this->upsertCompoundFromPubChem($pubchemData);
            $this->storeSynonyms($compound, $pubchemData['synonyms'], $this->sourceId('PubChem'));

            // ── HMDB (by InChI, InChIKey, SMILES, name, IUPAC, synonyms) ────
            $hmdb = $this->lookupHmdb($compound, $pubchemData['synonyms'] ?? []);

            if ($hmdb) {
                // ── HMDB by HMDB ID → full enrichment ───────────────────────
                $this->enrichFromHmdbData($compound, $hmdb);
            } else {
                // ── KEGG by CID ─────────────────────────────────────────────
                $this->lookupKeggByCid($pubchemData['cid']);

                // ── ClassyFire by InChIKey (regardless of KEGG result) ──────
                if ($compound->inchikey && !$compound->taxonomy()->exists()) {
                    $cfData = $this->lookupClassyFire($compound->inchikey);
                    if ($cfData) {
                        $this->storeTaxonomy($compound, $cfData, $this->sourceId('ClassyFire'));
                    }
                }
            }

            // ── NIST by InChI ────────────────────────────────────────────────
            $this->enrichNistRi($compound, $name);

            $row->update(['compound_id' => $compound->id, 'is_mapped' => true]);
            return true;
        }

        // ── Step 2: ChEBI (by name, normalized name, reordered normalized name)
        $chebiData = $this->lookupChEBI($name);

        if ($chebiData) {
            $compound = $this->upsertCompoundFromChEBI($chebiData);
            $this->storeSynonyms($compound, $chebiData['synonyms'], $this->sourceId('ChEBI'));

            if (!empty($chebiData['ontology_roles'])) {
                $this->storeOntologies(
                    $compound,
                    array_map(fn($r) => ['name' => $r, 'process_type' => 'ChEBI role'], $chebiData['ontology_roles']),
                    $this->sourceId('ChEBI')
                );
            }

            // ── HMDB by HMDB ID ──────────────────────────────────────────────
            if (!empty($chebiData['hmdb_id'])) {
                $hmdb = $this->lookupHmdbById($chebiData['hmdb_id']);
                if ($hmdb) {
                    $this->enrichFromHmdbData($compound, $hmdb);
                }
            }

            // ── NIST by InChI ────────────────────────────────────────────────
            $this->enrichNistRi($compound, $name);

            $row->update(['compound_id' => $compound->id, 'is_mapped' => true]);
            return true;
        }

        // ── Step 3: ChEBI not found → NIST by name ──────────────────────────
        // NIST only provides RI values; without a structural identifier we
        // cannot create a compound record, so the row remains unmapped.
        return false;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Name normalisation (mirrors Python cells 5–6)
    // ─────────────────────────────────────────────────────────────────────────

    private function normalizeGreek(string $name): string
    {
        return strtr($name, self::GREEK_MAP);
    }

    public function normalizeName(string $name): string
    {
        $name = $this->normalizeGreek($name);
        // ".alpha." → "alpha"  (existing)
        $name = (string) preg_replace(
            '/\.(alpha|beta|gamma|delta|epsilon|theta)\./i',
            '$1',
            $name
        );
        // "alpha Isomethyl ionone" → "alpha-Isomethyl ionone"  (leading Greek word + space → hyphen)
        $name = (string) preg_replace(
            '/^(alpha|beta|gamma|delta|epsilon|theta)\s+/i',
            '$1-',
            $name
        );
        return str_replace(['±', '- '], ['+-', '-'], trim($name));
    }

    public function commaPrefixReorder(string $name): string
    {
        if (!preg_match('/^(.+?),\s*([A-Za-z]+)-\s*$/', $name, $m)) {
            return $name;
        }
        if (!in_array(strtolower($m[2]), self::PREFIX_MODIFIERS, true)) {
            return $name;
        }
        return strtolower($m[2]) . '-' . trim($m[1]);
    }

    /**
     * Returns name candidates: original → normalised → reordered → joined-suffix → inverted.
     */
    private function nameCandidates(string $name): array
    {
        $candidates = [$name];

        $normalised = $this->normalizeName($name);
        if ($normalised !== $name) {
            $candidates[] = $normalised;
        }

        $reordered = $this->commaPrefixReorder($normalised);
        if ($reordered !== $normalised) {
            $candidates[] = $reordered;
        }

        // Greek-prefix suffix collapsing:
        //   "alpha-Isomethyl ionone" → "alpha-Isomethylionone"   (remove spaces)
        //   "beta-iso-Methyl ionone" → "beta-isomethylionone"    (remove spaces + hyphens, lowercase)
        $base = $reordered ?: $normalised;
        if (preg_match('/^(alpha|beta|gamma|delta|epsilon|theta)-(.+)$/i', $base, $m)) {
            $suffix = $m[2];
            if (str_contains($suffix, ' ')) {
                $candidates[] = $m[1] . '-' . str_replace(' ', '', $suffix);
            }
            $flat = strtolower(str_replace([' ', '-'], '', $suffix));
            $candidates[] = $m[1] . '-' . $flat;
        }

        // "Base Name, 1,2,3-descriptor-" → "1,2,3-descriptor-Base Name"
        $inverted = $this->generalCommaReorder($normalised);
        if ($inverted !== $normalised) {
            $candidates[] = $inverted;
            // Move inline stereodescriptor to front if present in the inverted form
            $stereoFront = $this->stereoToFront($inverted);
            if ($stereoFront !== null) {
                $candidates[] = $stereoFront;
            }
        }

        // Non-hyphenated CAS inversion: "Peroxide, dimethyl" → "dimethyl Peroxide"
        $plain = $this->plainCommaReorder($normalised);
        if ($plain !== null) {
            $candidates[] = $plain;
            $candidates[] = strtolower($plain);
        }

        // CAS ester/acetate reorder:
        //   "2-Propenoic acid, 3-(4-methoxyphenyl)-, 2-ethylhexyl ester"
        //     → "2-ethylhexyl 3-(4-methoxyphenyl)-2-propenoate"
        foreach ($this->casEsterReorder($normalised) as $c) {
            $candidates[] = $c;
        }

        // Derivative comma removal: "Butyraldehyde, semicarbazone" → "Butyraldehyde semicarbazone"
        $commaRemoved = (string) preg_replace('/,\s+/', ' ', $normalised, 1);
        if ($commaRemoved !== $normalised) {
            $candidates[] = $commaRemoved;
            $candidates[] = strtolower($commaRemoved);
        }

        // Typo corrections: re-run candidates on the corrected form
        $corrected = $this->applyTypoCorrections($normalised);
        if ($corrected !== null) {
            $candidates[] = $corrected;
            $correctedInverted = $this->generalCommaReorder($corrected);
            if ($correctedInverted !== $corrected) {
                $candidates[] = $correctedInverted;
            }
            $correctedPlain = $this->plainCommaReorder($corrected);
            if ($correctedPlain !== null) {
                $candidates[] = $correctedPlain;
            }
        }

        // Greek Unicode ↔ dotted ASCII form used in many chemical databases:
        //   "1α" → "1.alpha."  |  "1.alpha." → "1α"
        $dotted = $this->greekToDotted($name);
        if ($dotted !== null) {
            $candidates[] = $dotted;
            $dottedInverted = $this->generalCommaReorder($dotted);
            if ($dottedInverted !== $dotted) {
                $candidates[] = $dottedInverted;
            }
        }
        $unicode = $this->dottedToGreek($name);
        if ($unicode !== null) {
            $candidates[] = $unicode;
            $unicodeInverted = $this->generalCommaReorder($unicode);
            if ($unicodeInverted !== $unicode) {
                $candidates[] = $unicodeInverted;
            }
        }

        return array_unique($candidates);
    }

    private function generalCommaReorder(string $name): string
    {
        // Matches: "<base>, <descriptor>-"  where ", " (with space) is the separator.
        // Locant commas like "1,4-" never have a space after them, so \s+ safely picks
        // the right comma even when the base name or descriptor contains locant commas.
        if (!preg_match('/^(.+?),\s+(.+)-\s*$/', $name, $m)) {
            return $name;
        }
        return $m[2] . '-' . trim($m[1]);
    }

    private function plainCommaReorder(string $name): ?string
    {
        // Handles non-hyphenated CAS inversions: "Peroxide, dimethyl" → "dimethyl Peroxide"
        if (!preg_match('/^(.+?),\s+(.+)$/', $name, $m)) {
            return null;
        }
        $base   = trim($m[1]);
        $suffix = trim($m[2]);
        // Skip if suffix ends with '-' (generalCommaReorder covers that)
        // or contains ', ' (complex multi-part CAS name)
        if (str_ends_with($suffix, '-') || str_contains($suffix, ', ')) {
            return null;
        }
        return $suffix . ' ' . $base;
    }

    private function stereoToFront(string $name): ?string
    {
        // Moves inline stereodescriptors to the front:
        //   "1,2,3-trimethyl-4-propenyl-, (E)-naphthalene" → "(E)-1,2,3-trimethyl-4-propenyl-naphthalene"
        //   "8-(N-pyrrolidinyl)-, cis-Bicyclo[...]"        → "cis-8-(N-pyrrolidinyl)-Bicyclo[...]"
        if (!preg_match('/^(.+),\s*((?:cis|trans|endo|exo|\([EZRSezrs+\-]\)))-(.+)$/i', $name, $m)) {
            return null;
        }
        $prefix = strtolower(trim($m[2]));
        $body   = rtrim(trim($m[1]), '- ');
        $tail   = trim($m[3]);
        return $prefix . '-' . $body . '-' . $tail;
    }

    private function casEsterReorder(string $name): array
    {
        $candidates = [];

        // "Acid_name[, DESCRIPTOR-], ester_prefix ester" → "ester_prefix [DESCRIPTOR-]acid-ate"
        if (preg_match('/^(.+?),\s+(.+?)\s+ester\s*$/i', $name, $m)) {
            $base = trim($m[1]);
            $rest = trim($m[2]);

            if (preg_match('/^(.+),\s+([^,]+)$/', $rest, $m2)) {
                $descriptor  = trim($m2[1]);
                $esterPrefix = trim($m2[2]);
            } else {
                $descriptor  = '';
                $esterPrefix = $rest;
            }

            // Strip " acid" and optional inline stereodescriptor from base
            $baseEster = (string) preg_replace('/\s+acid(?:\s*\([^)]+\))?\s*-?\s*$/i', '', $base);
            $baseEster = (string) preg_replace('/oic$/i', 'oate', $baseEster);
            $baseEster = (string) preg_replace('/ic$/i',  'ate',  $baseEster);

            $body = $descriptor !== '' ? $descriptor . $baseEster : $baseEster;
            $candidates[] = strtolower("{$esterPrefix} {$body}");
        }

        // "Base, DESCRIPTOR-, acetate/formate/benzoate/..." → "DESCRIPTOR-base acetate"
        if (preg_match('/^(.+?),\s+(.+?),\s+(acetate|formate|benzoate|propanoate|butyrate)\s*$/i', $name, $m)) {
            $base       = trim($m[1]);
            $descriptor = trim($m[2]);
            $esterName  = strtolower(trim($m[3]));
            $candidates[] = strtolower("{$descriptor}{$base} {$esterName}");
        }

        return array_filter(array_unique($candidates));
    }

    private function applyTypoCorrections(string $name): ?string
    {
        static $patterns = [
            '/\bunae/i'           => 'unde',       // Unaecanal → Undecanal, Unaecane → Undecane
            '/\bunaecyl\b/i'      => 'undecyl',    // Unaecyl → Undecyl
            '/\binaene\b/i'       => 'indene',     // inaene → indene
            '/\binaen\b/i'        => 'inden',      // inaen → inden
            '/\btriptofane?\b/i'  => 'tryptophan', // triptofane/triptofan → tryptophan
        ];

        $corrected = $name;
        foreach ($patterns as $pattern => $replacement) {
            $corrected = (string) preg_replace($pattern, $replacement, $corrected);
        }

        return $corrected !== $name ? $corrected : null;
    }

    private function greekToDotted(string $name): ?string
    {
        static $map = [
            'α' => '.alpha.', 'β' => '.beta.',   'γ' => '.gamma.',   'δ' => '.delta.',
            'ε' => '.epsilon.','ζ' => '.zeta.',  'η' => '.eta.',     'θ' => '.theta.',
            'ι' => '.iota.',  'κ' => '.kappa.',  'λ' => '.lambda.',  'μ' => '.mu.',
            'ν' => '.nu.',    'ξ' => '.xi.',     'ο' => '.omicron.', 'π' => '.pi.',
            'ρ' => '.rho.',   'σ' => '.sigma.',  'τ' => '.tau.',     'υ' => '.upsilon.',
            'φ' => '.phi.',   'χ' => '.chi.',    'ψ' => '.psi.',     'ω' => '.omega.',
        ];
        $converted = strtr($name, $map);
        return $converted !== $name ? $converted : null;
    }

    private function dottedToGreek(string $name): ?string
    {
        static $map = [
            '.alpha.'   => 'α', '.beta.'    => 'β', '.gamma.'   => 'γ', '.delta.'   => 'δ',
            '.epsilon.' => 'ε', '.zeta.'    => 'ζ', '.eta.'     => 'η', '.theta.'   => 'θ',
            '.iota.'    => 'ι', '.kappa.'   => 'κ', '.lambda.'  => 'λ', '.mu.'      => 'μ',
            '.nu.'      => 'ν', '.xi.'      => 'ξ', '.omicron.' => 'ο', '.pi.'      => 'π',
            '.rho.'     => 'ρ', '.sigma.'   => 'σ', '.tau.'     => 'τ', '.upsilon.' => 'υ',
            '.phi.'     => 'φ', '.chi.'     => 'χ', '.psi.'     => 'ψ', '.omega.'   => 'ω',
        ];
        $converted = strtr($name, $map);
        return $converted !== $name ? $converted : null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Local DB lookup
    // ─────────────────────────────────────────────────────────────────────────

    private function findLocalByName(string $name): ?Compound
    {
        $lower = Str::lower($name);

        return Compound::whereRaw('LOWER(canonical_name) = ?', [$lower])->first()
            ?? Compound::whereRaw('LOWER(iupac_name) = ?', [$lower])->first()
            ?? Compound::whereHas('synonyms', fn($q) =>
                $q->whereRaw('LOWER(name) = ?', [$lower])
            )->first();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PubChem
    // ─────────────────────────────────────────────────────────────────────────

    private function lookupPubChem(string $name): ?array
    {
        foreach ($this->nameCandidates($name) as $candidate) {
            $data = $this->pubchemFetchByName($candidate);
            if ($data) {
                return $data;
            }
            usleep(200_000); // respect PubChem rate limit
        }
        return null;
    }

    private function pubchemFetchByName(string $name): ?array
    {
        $encoded = urlencode($name);

        // 1. Get CID + synonyms
        $synResp = $this->httpGet(self::PUBCHEM_BASE . "/name/{$encoded}/synonyms/JSON");

        if (!isset($synResp['InformationList']['Information'][0])) {
            return null;
        }

        $info     = $synResp['InformationList']['Information'][0];
        $cid      = $info['CID'] ?? null;
        $synonyms = array_slice($info['Synonym'] ?? [], 0, 30);

        if (!$cid) {
            return null;
        }

        usleep(200_000);

        // 2. Get structure properties
        $propResp = $this->httpGet(
            self::PUBCHEM_BASE . "/cid/{$cid}/property/Title,IUPACName,MolecularFormula,IsomericSMILES,InChI,InChIKey/JSON"
        );
        $props = $propResp['PropertyTable']['Properties'][0] ?? [];

        return [
            'cid'               => (string) $cid,
            'iupac_name'        => $props['IUPACName']        ?? $props['Title'] ?? null,
            'molecular_formula' => $props['MolecularFormula'] ?? null,
            'inchi'             => $props['InChI']            ?? null,
            'inchikey'          => $props['InChIKey']         ?? null,
            'smiles'            => $props['IsomericSMILES']   ?? $props['SMILES'] ?? null,
            'synonyms'          => $synonyms,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ChEBI
    // ─────────────────────────────────────────────────────────────────────────

    private function lookupChEBI(string $name): ?array
    {
        foreach ($this->nameCandidates($name) as $candidate) {
            $data = $this->chebiFetchByName($candidate);
            if ($data) {
                return $data;
            }
            usleep(200_000);
        }
        return null;
    }

    private function chebiFetchByName(string $name): ?array
    {
        $searchResp = $this->httpGet(
            self::CHEBI_BASE . '/es_search/?term=' . urlencode($name) . '&page=1&size=15'
        );

        if (empty($searchResp['results'])) {
            return null;
        }

        $chebiId = $searchResp['results'][0]['_source']['chebi_accession'] ?? null;
        if (!$chebiId) {
            return null;
        }

        usleep(200_000);

        $compound = $this->httpGet(
            self::CHEBI_BASE . "/compound/{$chebiId}?only_ontology_parents=false&only_ontology_children=false"
        );

        if (!$compound) {
            return null;
        }

        // HMDB cross-reference
        $hmdbId = null;
        foreach ($compound['database_accessions']['MANUAL_X_REF'] ?? [] as $xref) {
            if (($xref['prefix'] ?? '') === 'hmdb') {
                $hmdbId = $xref['accession_number'] ?? null;
                break;
            }
        }

        // IUPAC name
        $iupacName = $compound['names']['IUPAC NAME'][0]['name'] ?? null;

        // Synonyms
        $synonyms = array_filter(array_map(
            fn($s) => is_array($s) ? ($s['name'] ?? '') : (string) $s,
            $compound['names']['SYNONYM'] ?? []
        ));

        // Ontology roles (e.g. "metabolite", "drug", "cofactor", etc.)
        $ontologyRoles = [];
        foreach ($compound['compound_has_role'] ?? [] as $role) {
            $roleName = is_array($role) ? ($role['role_name'] ?? ($role['name'] ?? null)) : null;
            if ($roleName) {
                $ontologyRoles[] = $roleName;
            }
        }
        // Also try the flat roles array some ChEBI versions return
        foreach ($compound['roles'] ?? [] as $role) {
            $roleName = is_array($role) ? ($role['name'] ?? null) : (string) $role;
            if ($roleName) {
                $ontologyRoles[] = $roleName;
            }
        }
        $ontologyRoles = array_values(array_unique(array_filter($ontologyRoles)));

        $struct = $compound['default_structure'] ?? [];

        return [
            'chebi_id'       => $chebiId,
            'hmdb_id'        => $hmdbId,
            'iupac_name'     => $iupacName,
            'inchi'          => $struct['standard_inchi']     ?? null,
            'inchikey'       => $struct['standard_inchi_key'] ?? null,
            'smiles'         => $struct['smiles']             ?? null,
            'synonyms'       => array_values($synonyms),
            'ontology_roles' => $ontologyRoles,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HMDB (local SQLite)
    // ─────────────────────────────────────────────────────────────────────────

    private function hmdbPdo(): ?PDO
    {
        if ($this->hmdbPdo) {
            return $this->hmdbPdo;
        }
        $path = config('compounds.hmdb_sqlite_path');
        if (!$path || !file_exists($path)) {
            return null;
        }
        $this->hmdbPdo = new PDO("sqlite:{$path}");
        $this->hmdbPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $this->hmdbPdo;
    }

    public function lookupHmdb(Compound $compound, array $synonyms = []): ?array
    {
        $pdo = $this->hmdbPdo();
        if (!$pdo) {
            return null;
        }

        try {
            // Primary: match by InChIKey or InChI
            $stmt = $pdo->prepare(<<<SQL
                SELECT m.hmdb_id, m.name, m.inchi, m.inchi_key, m.smiles,
                       m.synonyms, m.chemical_taxonomy_json,
                       m.retention_indices_json, m.description,
                       o.metabolic_processes_json, o.diseases_json, o.biomarker_for_json
                FROM metabolites m
                LEFT JOIN ontology o ON m.hmdb_id = o.hmdb_id
                WHERE m.inchi_key = ?
                   OR m.inchi     = ?
                LIMIT 1
            SQL);
            $stmt->execute([$compound->inchikey, $compound->inchi]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            // Fallback: match by SMILES
            if (!$row && $compound->smiles) {
                $stmt = $pdo->prepare(<<<SQL
                    SELECT m.hmdb_id, m.name, m.inchi, m.inchi_key, m.smiles,
                           m.synonyms, m.chemical_taxonomy_json,
                           m.retention_indices_json, m.description,
                           o.metabolic_processes_json, o.diseases_json, o.biomarker_for_json
                    FROM metabolites m
                    LEFT JOIN ontology o ON m.hmdb_id = o.hmdb_id
                    WHERE m.smiles = ?
                    LIMIT 1
                SQL);
                $stmt->execute([$compound->smiles]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            // Fallback: match by IUPAC name or canonical name
            if (!$row && ($compound->iupac_name || $compound->canonical_name)) {
                $stmt = $pdo->prepare(<<<SQL
                    SELECT m.hmdb_id, m.name, m.inchi, m.inchi_key, m.smiles,
                           m.synonyms, m.chemical_taxonomy_json,
                           m.retention_indices_json, m.description,
                           o.metabolic_processes_json, o.diseases_json, o.biomarker_for_json
                    FROM metabolites m
                    LEFT JOIN ontology o ON m.hmdb_id = o.hmdb_id
                    WHERE LOWER(m.name) = LOWER(?)
                       OR LOWER(m.name) = LOWER(?)
                       OR LOWER(m.synonyms) LIKE LOWER(?)
                    LIMIT 1
                SQL);
                $stmt->execute([
                    $compound->iupac_name    ?? '',
                    $compound->canonical_name ?? '',
                    '%' . ($compound->canonical_name ?? $compound->iupac_name) . '%',
                ]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            // Fallback: match by PubChem synonyms
            if (!$row && !empty($synonyms)) {
                foreach (array_slice($synonyms, 0, 10) as $syn) {
                    $syn = trim((string) $syn);
                    if ($syn === '') continue;

                    $stmt = $pdo->prepare(<<<SQL
                        SELECT m.hmdb_id, m.name, m.inchi, m.inchi_key, m.smiles,
                               m.synonyms, m.chemical_taxonomy_json,
                               m.retention_indices_json, m.description,
                               o.metabolic_processes_json, o.diseases_json, o.biomarker_for_json
                        FROM metabolites m
                        LEFT JOIN ontology o ON m.hmdb_id = o.hmdb_id
                        WHERE LOWER(m.name) = LOWER(?)
                           OR LOWER(m.synonyms) LIKE LOWER(?)
                        LIMIT 1
                    SQL);
                    $stmt->execute([$syn, '%' . $syn . '%']);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($row) {
                        break;
                    }
                }
            }

            return $row ?: null;
        } catch (\Throwable $e) {
            Log::warning('HMDB SQLite lookup failed: ' . $e->getMessage());
            return null;
        }
    }

    private function lookupHmdbById(string $hmdbId): ?array
    {
        $pdo = $this->hmdbPdo();
        if (!$pdo) {
            return null;
        }

        try {
            $stmt = $pdo->prepare(<<<SQL
                SELECT m.hmdb_id, m.name, m.inchi, m.inchi_key, m.smiles,
                       m.synonyms, m.chemical_taxonomy_json,
                       m.retention_indices_json, m.description,
                       o.metabolic_processes_json, o.diseases_json, o.biomarker_for_json
                FROM metabolites m
                LEFT JOIN ontology o ON m.hmdb_id = o.hmdb_id
                WHERE m.hmdb_id = ?
                LIMIT 1
            SQL);
            $stmt->execute([$hmdbId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\Throwable $e) {
            Log::warning('HMDB SQLite lookup by ID failed: ' . $e->getMessage());
            return null;
        }
    }

    public function enrichFromHmdbData(Compound $compound, array $hmdb): void
    {
        // Update core identifiers and description
        $updates = array_filter([
            'hmdb_id'     => (!$compound->hmdb_id     && !empty($hmdb['hmdb_id']))     ? $hmdb['hmdb_id']     : null,
            'inchi'       => (!$compound->inchi       && !empty($hmdb['inchi']))        ? $hmdb['inchi']       : null,
            'inchikey'    => (!$compound->inchikey    && !empty($hmdb['inchi_key']))    ? $hmdb['inchi_key']   : null,
            'smiles'      => (!$compound->smiles      && !empty($hmdb['smiles']))       ? $hmdb['smiles']      : null,
            'description' => (!$compound->description && !empty($hmdb['description'])) ? $hmdb['description'] : null,
        ]);

        if ($updates) {
            $compound->update($updates);
        }

        $sourceId = $this->sourceId('HMDB');

        // Synonyms (semicolon-separated in HMDB SQLite)
        if (!empty($hmdb['synonyms'])) {
            $syns = array_filter(array_map('trim', explode(';', $hmdb['synonyms'])));
            $this->storeSynonyms($compound, $syns, $sourceId);
        }

        // Taxonomy
        if (!$compound->taxonomy()->exists() && !empty($hmdb['chemical_taxonomy_json'])) {
            $taxData = json_decode($hmdb['chemical_taxonomy_json'], true);
            if (is_array($taxData)) {
                $this->storeTaxonomy($compound, $taxData, $sourceId);
            }
        }

        // Retention indices
        if (!empty($hmdb['retention_indices_json'])) {
            $riList = json_decode($hmdb['retention_indices_json'], true);
            if (is_array($riList)) {
                foreach ($riList as $ri) {
                    $riValue = $ri['retention_index'] ?? null;
                    $phase   = $ri['stationary_phase'] ?? null;
                    if ($riValue === null) continue;

                    $isPolar = in_array($phase, ['Standard polar', 'Semi standard polar'], true);
                    $this->storeRetentionIndex($compound, (float) $riValue, $phase ?? 'unknown', $isPolar, $sourceId);
                }
            }
        }

        // Diseases
        if (!empty($hmdb['diseases_json'])) {
            $diseases = json_decode($hmdb['diseases_json'], true);
            if (is_array($diseases)) {
                $this->storeDiseases($compound, $diseases, $sourceId);
            }
        }

        // Ontologies / metabolic processes
        if (!empty($hmdb['metabolic_processes_json'])) {
            $procs = json_decode($hmdb['metabolic_processes_json'], true);
            if (is_array($procs)) {
                $this->storeOntologies($compound, $procs, $sourceId);
            }
        }

        // Biomarkers
        if (!empty($hmdb['biomarker_for_json'])) {
            $biomarkers = json_decode($hmdb['biomarker_for_json'], true);
            if (is_array($biomarkers)) {
                $this->storeBiomarkers($compound, $biomarkers, $sourceId);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // NIST RI (local SQLite)
    // ─────────────────────────────────────────────────────────────────────────

    private function nistPdo(): ?PDO
    {
        if ($this->nistPdo) {
            return $this->nistPdo;
        }
        $path = config('compounds.nist_sqlite_path');
        if (!$path || !file_exists($path)) {
            return null;
        }
        $this->nistPdo = new PDO("sqlite:{$path}");
        $this->nistPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $this->nistPdo;
    }

    public function enrichNistRi(Compound $compound, string $originalName = ''): void
    {
        $pdo = $this->nistPdo();
        if (!$pdo) {
            return;
        }

        try {
            // Primary: by InChI
            $stmt = $pdo->prepare(<<<SQL
                SELECT AVG(ri) AS avg_ri, COUNT(*) AS n
                FROM nist_compounds
                WHERE column_polarity = 'polar column'
                  AND ri_type        = 'Normal alkane RI'
                  AND inchi          = ?
                  AND ri IS NOT NULL
            SQL);
            $stmt->execute([$compound->inchi]);
            $row   = $stmt->fetch(PDO::FETCH_ASSOC);
            $avgRi = (!empty($row['n'])) ? $row['avg_ri'] : null;

            // Fallback: by name
            if ($avgRi === null) {
                $stmt = $pdo->prepare(<<<SQL
                    SELECT AVG(ri) AS avg_ri, COUNT(*) AS n
                    FROM nist_compounds
                    WHERE column_polarity = 'polar column'
                      AND ri_type        = 'Normal alkane RI'
                      AND compound_name LIKE ?
                      AND ri IS NOT NULL
                SQL);
                $stmt->execute(["%{$originalName}%"]);
                $row   = $stmt->fetch(PDO::FETCH_ASSOC);
                $avgRi = (!empty($row['n'])) ? $row['avg_ri'] : null;
            }

            if ($avgRi !== null) {
                $n = (int) ($row['n'] ?? 1);
                RetentionIndex::firstOrCreate(
                    [
                        'compound_id' => $compound->id,
                        'column_type' => 'Normal alkane RI',
                        'is_polar'    => true,
                        'source_id'   => $this->sourceId('NIST'),
                    ],
                    [
                        'value'     => round((float) $avgRi, 4),
                        'reference' => "NIST Chemistry WebBook – avg of {$n} polar entries",
                    ]
                );
            }
        } catch (\Throwable $e) {
            Log::warning('NIST SQLite RI lookup failed: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ClassyFire taxonomy
    // ─────────────────────────────────────────────────────────────────────────

    public function lookupClassyFire(string $inchikey): ?array
    {
        $resp = $this->httpGet(self::CLASSYFIRE_BASE . "/{$inchikey}.json");
        return is_array($resp) ? $resp : null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ChEBI enrichment (for use by SyncCompoundsJob)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Given a compound that already exists in our DB, try to enrich it from
     * ChEBI: look up by chebi_id (if set) or by InChIKey, then store
     * ontology roles, synonyms, and HMDB cross-reference.
     */
    public function enrichFromChebi(Compound $compound): bool
    {
        $chebiId = $compound->chebi_id;

        if (!$chebiId && $compound->inchikey) {
            $resp = $this->httpGet(
                self::CHEBI_BASE . '/es_search/?term=' . urlencode($compound->inchikey) . '&page=1&size=5'
            );
            $chebiId = $resp['results'][0]['_source']['chebi_accession'] ?? null;
            usleep(200_000);
            if ($chebiId) {
                $compound->update(['chebi_id' => $chebiId]);
            }
        }

        if (!$chebiId) {
            return false;
        }

        $data = $this->httpGet(
            self::CHEBI_BASE . "/compound/{$chebiId}?only_ontology_parents=false&only_ontology_children=false"
        );

        if (!$data) {
            return false;
        }

        // HMDB cross-reference
        $hmdbId = null;
        foreach ($data['database_accessions']['MANUAL_X_REF'] ?? [] as $xref) {
            if (($xref['prefix'] ?? '') === 'hmdb') {
                $hmdbId = $xref['accession_number'] ?? null;
                break;
            }
        }
        if ($hmdbId && !$compound->hmdb_id) {
            $compound->update(['hmdb_id' => $hmdbId]);
        }

        // Synonyms
        $synonyms = array_filter(array_map(
            fn($s) => is_array($s) ? ($s['name'] ?? '') : (string) $s,
            $data['names']['SYNONYM'] ?? []
        ));
        if ($synonyms) {
            $this->storeSynonyms($compound, array_values($synonyms), $this->sourceId('ChEBI'));
        }

        // Ontology roles
        $roles = [];
        foreach ($data['compound_has_role'] ?? [] as $role) {
            $n = is_array($role) ? ($role['role_name'] ?? ($role['name'] ?? null)) : null;
            if ($n) $roles[] = $n;
        }
        foreach ($data['roles'] ?? [] as $role) {
            $n = is_array($role) ? ($role['name'] ?? null) : (string) $role;
            if ($n) $roles[] = $n;
        }
        if ($roles) {
            $this->storeOntologies(
                $compound,
                array_map(fn($r) => ['name' => $r, 'process_type' => 'ChEBI role'], array_values(array_unique(array_filter($roles)))),
                $this->sourceId('ChEBI')
            );
        }

        return true;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Upsert helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function upsertCompoundFromPubChem(array $data): Compound
    {
        // Search by each unique key in priority order so we never hit a constraint violation.
        $compound = null;
        if (!empty($data['inchikey'])) {
            $compound = Compound::where('inchikey', $data['inchikey'])->first();
        }
        if (!$compound && !empty($data['inchi'])) {
            $compound = Compound::where('inchi', $data['inchi'])->first();
        }
        if (!$compound && !empty($data['cid'])) {
            $compound = Compound::where('pubchem_cid', $data['cid'])->first();
        }

        $attributes = array_filter([
            'canonical_name'    => $data['iupac_name']        ?? null,
            'iupac_name'        => $data['iupac_name']        ?? null,
            'molecular_formula' => $data['molecular_formula'] ?? null,
            'inchi'             => $data['inchi']             ?? null,
            'inchikey'          => $data['inchikey']          ?? null,
            'smiles'            => $data['smiles']            ?? null,
            'pubchem_cid'       => $data['cid']               ?? null,
        ], fn($v) => $v !== null);

        if ($compound) {
            $compound->update($attributes);
            return $compound->fresh();
        }

        try {
            return Compound::create($attributes);
        } catch (\Illuminate\Database\QueryException $e) {
            // Race condition: another request inserted the same compound concurrently.
            if ($e->getCode() === '23505') {
                if (!empty($data['inchikey']) && $found = Compound::where('inchikey', $data['inchikey'])->first()) {
                    return $found;
                }
                if (!empty($data['inchi']) && $found = Compound::where('inchi', $data['inchi'])->first()) {
                    return $found;
                }
            }
            throw $e;
        }
    }

    private function upsertCompoundFromChEBI(array $data): Compound
    {
        $compound = null;
        if (!empty($data['inchikey'])) {
            $compound = Compound::where('inchikey', $data['inchikey'])->first();
        }
        if (!$compound && !empty($data['inchi'])) {
            $compound = Compound::where('inchi', $data['inchi'])->first();
        }
        if (!$compound && !empty($data['chebi_id'])) {
            $compound = Compound::where('chebi_id', $data['chebi_id'])->first();
        }

        $attributes = array_filter([
            'canonical_name' => $data['iupac_name'] ?? null,
            'iupac_name'     => $data['iupac_name'] ?? null,
            'inchi'          => $data['inchi']      ?? null,
            'inchikey'       => $data['inchikey']   ?? null,
            'smiles'         => $data['smiles']     ?? null,
            'chebi_id'       => $data['chebi_id']   ?? null,
            'hmdb_id'        => $data['hmdb_id']    ?? null,
        ], fn($v) => $v !== null);

        if ($compound) {
            $compound->update($attributes);
            return $compound->fresh();
        }

        try {
            return Compound::create($attributes);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23505') {
                if (!empty($data['inchikey']) && $found = Compound::where('inchikey', $data['inchikey'])->first()) {
                    return $found;
                }
                if (!empty($data['inchi']) && $found = Compound::where('inchi', $data['inchi'])->first()) {
                    return $found;
                }
            }
            throw $e;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Relationship storage helpers (public so import commands can reuse them)
    // ─────────────────────────────────────────────────────────────────────────

    public function storeSynonyms(Compound $compound, array $synonyms, int $sourceId): void
    {
        foreach (array_unique(array_filter(array_map('trim', $synonyms))) as $syn) {
            $compound->synonyms()->firstOrCreate(
                ['name' => $syn],
                ['source_id' => $sourceId]
            );
        }
    }

    public function storeTaxonomy(Compound $compound, array $data, int $sourceId): void
    {
        // Handles both ClassyFire format ({kingdom: {name:…}}) and HMDB flat format
        $getStr = function (string ...$keys) use ($data): ?string {
            foreach ($keys as $k) {
                $val = $data[$k] ?? null;
                if (is_array($val)) {
                    $val = $val['name'] ?? null;
                }
                if (is_string($val) && $val !== '') {
                    return $val;
                }
            }
            return null;
        };

        $altParents = array_filter(array_map(
            fn($p) => is_array($p) ? ($p['name'] ?? '') : (string) $p,
            (array) ($data['alternative_parents'] ?? [])
        ));

        Taxonomy::updateOrCreate(
            ['compound_id' => $compound->id],
            [
                'kingdom'             => $getStr('kingdom'),
                'superclass'          => $getStr('superclass', 'super_class', 'superklass'),
                'class'               => $getStr('class', 'klass'),
                'subclass'            => $getStr('subclass', 'sub_class', 'subklass'),
                'direct_parent'       => $getStr('direct_parent'),
                'alternative_parents' => implode('; ', $altParents) ?: null,
                'raw_json'            => json_encode($data),
            ]
        );
    }

    public function storeRetentionIndex(
        Compound $compound,
        float    $value,
        string   $columnType,
        bool     $isPolar,
        ?int     $sourceId = null
    ): void {
        RetentionIndex::firstOrCreate(
            [
                'compound_id' => $compound->id,
                'column_type' => $columnType,
                'is_polar'    => $isPolar,
                'source_id'   => $sourceId,
            ],
            ['value' => $value]
        );
    }

    public function storeDiseases(Compound $compound, array $diseases, int $sourceId): void
    {
        foreach ($diseases as $d) {
            $name = is_array($d) ? ($d['name'] ?? null) : (string) $d;
            if (!$name) continue;

            $disease = Disease::firstOrCreate(
                ['name' => $name],
                [
                    'description' => is_array($d) ? ($d['description'] ?? null) : null,
                    'category'    => is_array($d) ? ($d['category']    ?? null) : null,
                ]
            );

            CompoundDiseaseAssociation::firstOrCreate([
                'compound_id' => $compound->id,
                'disease_id'  => $disease->id,
                'source_id'   => $sourceId,
            ]);
        }
    }

    public function storeOntologies(Compound $compound, array $processes, int $sourceId): void
    {
        foreach ($processes as $p) {
            $name = is_array($p) ? ($p['name'] ?? null) : (string) $p;
            if (!$name) continue;

            $ontology = Ontology::firstOrCreate(
                ['name' => $name],
                [
                    'type'        => is_array($p) ? ($p['process_type'] ?? null) : null,
                    'description' => is_array($p) ? ($p['description']  ?? null) : null,
                ]
            );

            CompoundOntology::firstOrCreate([
                'compound_id' => $compound->id,
                'ontology_id' => $ontology->id,
                'source_id'   => $sourceId,
            ]);
        }
    }

    public function storeBiomarkers(Compound $compound, array $biomarkers, int $sourceId): void
    {
        foreach ($biomarkers as $b) {
            $name = is_array($b) ? ($b['name'] ?? null) : (string) $b;
            if (!$name) continue;

            $biomarker = Biomarker::firstOrCreate(
                ['name' => $name],
                ['description' => is_array($b) ? ($b['description'] ?? null) : null]
            );

            CompoundBiomarker::firstOrCreate([
                'compound_id'  => $compound->id,
                'biomarker_id' => $biomarker->id,
                'source_id'    => $sourceId,
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Source ID cache
    // ─────────────────────────────────────────────────────────────────────────

    public function sourceId(string $name): int
    {
        if (!isset($this->sourceIds[$name])) {
            $this->sourceIds[$name] = Source::firstOrCreate(['name' => $name])->id;
        }
        return $this->sourceIds[$name];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // KEGG
    // ─────────────────────────────────────────────────────────────────────────

    private function lookupKeggByCid(string $cid): bool
    {
        $text = $this->httpGetText("https://rest.kegg.jp/conv/compound/pubchem:{$cid}");
        if ($text === null || trim($text) === '') {
            return false;
        }
        // Response format: "pubchem:702\tcpd:C00079\n"
        $parts = explode("\t", trim($text));
        return count($parts) >= 2 && trim($parts[1]) !== '';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HTTP helpers (with retry + rate-limit back-off)
    // ─────────────────────────────────────────────────────────────────────────

    private function httpGet(string $url, int $maxTries = 3): ?array
    {
        for ($attempt = 0; $attempt < $maxTries; $attempt++) {
            try {
                $response = Http::timeout(30)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->get($url);

                if ($response->successful()) {
                    return $response->json();
                }

                // Not found – no point retrying
                if ($response->status() === 404) {
                    return null;
                }

                // Rate limited – exponential back-off
                if ($response->status() === 429) {
                    sleep(2 ** $attempt);
                    continue;
                }

                Log::debug("HTTP {$response->status()} for {$url}");
            } catch (\Throwable $e) {
                Log::debug("HTTP attempt {$attempt} failed for {$url}: {$e->getMessage()}");
                if ($attempt < $maxTries - 1) {
                    sleep($attempt + 1);
                }
            }
        }
        return null;
    }

    private function httpGetText(string $url, int $maxTries = 3): ?string
    {
        for ($attempt = 0; $attempt < $maxTries; $attempt++) {
            try {
                $response = Http::timeout(30)->get($url);

                if ($response->successful()) {
                    return $response->body();
                }
                if ($response->status() === 404) {
                    return null;
                }
                if ($response->status() === 429) {
                    sleep(2 ** $attempt);
                    continue;
                }

                Log::debug("HTTP {$response->status()} for {$url}");
            } catch (\Throwable $e) {
                Log::debug("HTTP attempt {$attempt} failed for {$url}: {$e->getMessage()}");
                if ($attempt < $maxTries - 1) {
                    sleep($attempt + 1);
                }
            }
        }
        return null;
    }
}