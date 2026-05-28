#!/usr/bin/env php
<?php
/**
 * Compound identification pipeline tester.
 *
 * Usage:  php identify_compounds.php <project_id> [output.log]
 *
 * For each compound name in the project, tries these services in order
 * and stops as soon as one succeeds:
 *   1. PubChem
 *   2. ChEBI
 *   3. NCI CADD Chemical Identifier Resolver  (CAS lookup)
 *   4. OPSIN  (IUPAC systematic-name parser)
 *   5. ClassyFire  (via NCI CADD InChIKey → ClassyFire entities)
 *   6. NIST  (local SQLite: nist_compounds.compound_name)
 *
 * Output format (one line per compound):
 *   N - <name> - PubChem: FOUND CID:702 - ChEBI: skipped - ...
 *   N - <name> - NOT_FOUND
 */

declare(strict_types=1);

// ── Argument parsing ──────────────────────────────────────────────────────────
// Usage:  php identify_compounds.php <project_id> [--id=<row_id>] [output.log]

$projectId = (int) ($argv[1] ?? 0);
if (!$projectId) {
    fwrite(STDERR, "Usage: php identify_compounds.php <project_id> [--id=<row_id>] [output.log]\n");
    exit(1);
}

$singleRowId = null;
$outFile     = null;
foreach (array_slice($argv, 2) as $arg) {
    if (preg_match('/^--id=(\d+)$/', $arg, $m)) {
        $singleRowId = (int) $m[1];
    } else {
        $outFile = $arg;
    }
}
$outFile ??= sprintf('identify_project_%d_%s.log', $projectId, date('Ymd_His'));

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// ── Fetch compounds ───────────────────────────────────────────────────────────

$query = DB::table('project_compounds')
    ->where('project_id', $projectId)
    ->orderBy('id');

if ($singleRowId !== null) {
    $query->where('id', $singleRowId);
}

$rows = $query->get(['id', 'input_name', 'custom_name']);

if ($rows->isEmpty()) {
    fwrite(STDERR, "No compounds found for project {$projectId}.\n");
    exit(1);
}

$total = $rows->count();
echo "Project {$projectId}: {$total} compounds\n";
echo "Output → {$outFile}\n\n";

$fh = fopen($outFile, 'w') or die("Cannot open {$outFile}\n");

// ── Main loop ─────────────────────────────────────────────────────────────────

$i = 0;
foreach ($rows as $row) {
    $i++;
    $name = trim($row->input_name ?? $row->custom_name ?? '');

    if ($name === '') {
        $line = "{$i} - (empty) - SKIP";
        echo $line . "\n";
        fwrite($fh, $line . "\n");
        continue;
    }

    printf("[%d/%d] %s ... ", $i, $total, $name);

    [$steps, $found] = run_pipeline($name);

    $stepStr = implode(' - ', array_map(
        fn($k, $v) => "{$k}: {$v}",
        array_keys($steps),
        array_values($steps),
    ));

    if ($found) {
        $line = "{$i} - {$name} - {$stepStr}";
        fwrite($fh, $line . "\n");
        $foundBy = array_key_first(array_filter($steps, fn($v) => str_starts_with($v, 'FOUND')));
        echo "FOUND ({$foundBy})\n";
    } else {
        $candidates    = name_candidates($name);
        $candidateStr  = implode(' | ', array_map(fn($c) => "\"{$c}\"", $candidates));
        $line          = "{$i} - {$name} - NOT_FOUND";
        $candidateLine = "  candidates tried: {$candidateStr}";
        fwrite($fh, $line . "\n" . $candidateLine . "\n");
        echo "not found\n  candidates: {$candidateStr}\n";
    }

    usleep(300_000); // 300 ms between compounds
}

fclose($fh);
echo "\nDone → {$outFile}\n";

// ── Pipeline ──────────────────────────────────────────────────────────────────

function run_pipeline(string $name): array
{
    // 1. PubChem
    $result = pubchem_lookup($name);
    if ($result) {
        return [
            ['PubChem' => "FOUND CID:{$result}", 'ChEBI' => 'skipped', 'NCI CADD' => 'skipped', 'OPSIN' => 'skipped', 'ClassyFire' => 'skipped'],
            true,
        ];
    }

    // 2. ChEBI
    $result = chebi_lookup($name);
    if ($result) {
        return [
            ['PubChem' => 'not found', 'ChEBI' => "FOUND {$result}", 'NCI CADD' => 'skipped', 'OPSIN' => 'skipped', 'ClassyFire' => 'skipped'],
            true,
        ];
    }

    // 3. NCI CADD  (CAS number)
    $result = nci_cas_lookup($name);
    if ($result) {
        return [
            ['PubChem' => 'not found', 'ChEBI' => 'not found', 'NCI CADD' => "FOUND CAS:{$result}", 'OPSIN' => 'skipped', 'ClassyFire' => 'skipped'],
            true,
        ];
    }

    // 4. OPSIN  (IUPAC systematic name → InChIKey)
    $result = opsin_lookup($name);
    if ($result) {
        return [
            ['PubChem' => 'not found', 'ChEBI' => 'not found', 'NCI CADD' => 'not found', 'OPSIN' => "FOUND {$result}", 'ClassyFire' => 'skipped'],
            true,
        ];
    }

    // 5. ClassyFire  (NCI CADD InChIKey → ClassyFire entities)
    $inchikey = nci_inchikey_lookup($name);
    if ($inchikey) {
        $cfClass = classyfire_lookup($inchikey);
        if ($cfClass) {
            return [
                ['PubChem' => 'not found', 'ChEBI' => 'not found', 'NCI CADD' => 'not found', 'OPSIN' => 'not found', 'ClassyFire' => "FOUND {$inchikey} ({$cfClass})", 'NIST' => 'skipped'],
                true,
            ];
        }
    }

    // 6. NIST  (local SQLite, compound_name search)
    $result = nist_lookup($name);
    if ($result) {
        return [
            ['PubChem' => 'not found', 'ChEBI' => 'not found', 'NCI CADD' => 'not found', 'OPSIN' => 'not found', 'ClassyFire' => 'not found', 'NIST' => "FOUND {$result}"],
            true,
        ];
    }

    return [
        ['PubChem' => 'not found', 'ChEBI' => 'not found', 'NCI CADD' => 'not found', 'OPSIN' => 'not found', 'ClassyFire' => 'not found', 'NIST' => 'not found'],
        false,
    ];
}

// ── Service lookups ───────────────────────────────────────────────────────────

function pubchem_lookup(string $name): ?string
{
    foreach (name_candidates($name) as $candidate) {
        $data = http_json(
            'https://pubchem.ncbi.nlm.nih.gov/rest/pug/compound/name/'
            . urlencode($candidate) . '/cids/JSON'
        );
        $cid = $data['IdentifierList']['CID'][0] ?? null;
        if ($cid) return (string) $cid;
        usleep(200_000);
    }
    return null;
}

function chebi_lookup(string $name): ?string
{
    foreach (name_candidates($name) as $candidate) {
        $data = http_json(
            'https://www.ebi.ac.uk/chebi/backend/api/public/es_search/?term='
            . urlencode($candidate) . '&page=1&size=5'
        );
        $id = $data['results'][0]['_source']['chebi_accession'] ?? null;
        if ($id) return $id;
        usleep(200_000);
    }
    return null;
}

// Step 3: resolve to CAS via NCI CADD
function nci_cas_lookup(string $name): ?string
{
    foreach (name_candidates($name) as $candidate) {
        $text = http_text(
            'https://cactus.nci.nih.gov/chemical/structure/'
            . urlencode($candidate) . '/cas'
        );
        if ($text !== null) {
            return trim(explode("\n", trim($text))[0]);
        }
        usleep(250_000);
    }
    return null;
}

// Step 5 helper: resolve to InChIKey via NCI CADD  (different result type from step 3)
function nci_inchikey_lookup(string $name): ?string
{
    foreach (name_candidates($name) as $candidate) {
        $text = http_text(
            'https://cactus.nci.nih.gov/chemical/structure/'
            . urlencode($candidate) . '/stdinchikey'
        );
        if ($text !== null) {
            return trim(explode("\n", trim($text))[0]);
        }
        usleep(250_000);
    }
    return null;
}

function opsin_lookup(string $name): ?string
{
    foreach (name_candidates($name) as $candidate) {
        $data = http_json('https://opsin.ch.cam.ac.uk/opsin/' . urlencode($candidate));
        if (($data['status'] ?? '') === 'SUCCESS' && !empty($data['inchikey'])) {
            return $data['inchikey'];
        }
        usleep(200_000);
    }
    return null;
}

function classyfire_lookup(string $inchikey): ?string
{
    $data = http_json('http://classyfire.wishartlab.com/entities/' . urlencode($inchikey) . '.json');
    return $data['direct_parent']['name'] ?? ($data['class']['name'] ?? null);
}

function nist_lookup(string $name): ?string
{
    static $pdo = null;

    if ($pdo === null) {
        $path = config('compounds.nist_sqlite_path',
            '/Users/annamiletova/ijs/p01_pd_biomarkers/data/nist.sqlite');
        if (!$path || !file_exists($path)) {
            return null;
        }
        $pdo = new PDO("sqlite:{$path}");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    foreach (name_candidates($name) as $candidate) {
        $stmt = $pdo->prepare(
            'SELECT compound_id, compound_name FROM nist_compounds WHERE LOWER(compound_name) LIKE LOWER(?) LIMIT 1'
        );
        $stmt->execute(['%' . trim($candidate) . '%']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $id = $row['compound_id'] ?? $row['compound_name'];
            return (string) $id;
        }
    }
    return null;
}

// ── Name normalisation (mirrors CompoundMappingService) ───────────────────────

function name_candidates(string $name): array
{
    $norm      = normalize_name($name);
    $reordered = comma_prefix_reorder($norm);

    $candidates = array_unique(array_filter([
        $name,
        $norm      !== $name ? $norm      : null,
        $reordered !== $norm ? $reordered : null,
    ]));

    // Greek Unicode ↔ dotted ASCII form used in many chemical databases:
    //   "1α" → "1.alpha."  |  "1.alpha." → "1α"
    $dotted = greek_to_dotted($name);
    if ($dotted !== null) {
        $candidates[] = $dotted;
        $dottedInverted = general_comma_reorder($dotted);
        if ($dottedInverted !== $dotted) $candidates[] = $dottedInverted;
    }
    $unicode = dotted_to_greek($name);
    if ($unicode !== null) {
        $candidates[] = $unicode;
        $unicodeInverted = general_comma_reorder($unicode);
        if ($unicodeInverted !== $unicode) $candidates[] = $unicodeInverted;
    }

    // Greek-prefix suffix collapsing:
    //   "alpha-Isomethyl ionone"   → "alpha-Isomethylionone"   (remove spaces)
    //   "beta-iso-Methyl ionone"   → "beta-isomethylionone"    (remove spaces AND hyphens, lowercase)
    $base = $reordered ?: $norm;
    if (preg_match('/^(alpha|beta|gamma|delta|epsilon|theta)-(.+)$/i', $base, $m)) {
        $suffix = $m[2];
        // spaces only (preserves inner hyphens like "iso-Methyl ionone" → "iso-Methylionone")
        if (str_contains($suffix, ' ')) {
            $candidates[] = $m[1] . '-' . str_replace(' ', '', $suffix);
        }
        // spaces + hyphens + lowercase ("iso-Methyl ionone" → "isomethylionone")
        $flat = strtolower(str_replace([' ', '-'], '', $suffix));
        $candidates[] = $m[1] . '-' . $flat;
    }

    // "Base Name, 1,2,3-descriptor-" → "1,2,3-descriptor-Base Name"
    // Uses \s+ after separator comma to distinguish it from locant commas (which have no space).
    $inverted = general_comma_reorder($norm);
    if ($inverted !== $norm) {
        $candidates[] = $inverted;
        // If inverted name contains an inline stereodescriptor "..., (E)-base" → "(E)-...-base"
        $stereoFront = stereo_to_front($inverted);
        if ($stereoFront !== null) $candidates[] = $stereoFront;
    }

    // Non-hyphenated CAS inversion: "Peroxide, dimethyl" → "dimethyl Peroxide"
    // (general_comma_reorder requires trailing '-'; this handles the suffix-without-hyphen case)
    $plain = plain_comma_reorder($norm);
    if ($plain !== null) {
        $candidates[] = $plain;
        $candidates[] = strtolower($plain);
    }

    // CAS ester/acetate reorder:
    //   "2-Propenoic acid, 3-(4-methoxyphenyl)-, 2-ethylhexyl ester"
    //     → "2-ethylhexyl 3-(4-methoxyphenyl)-2-propenoate"
    //   "4,7-methano-1H-inden-5-ol, 3a,...-, acetate"
    //     → "3a,...-4,7-methano-1h-inden-5-ol acetate"
    foreach (cas_ester_reorder($norm) as $c) {
        $candidates[] = $c;
    }

    // Derivative comma removal: "Butyraldehyde, semicarbazone" → "Butyraldehyde semicarbazone"
    // (plain_comma_reorder inverts order; this keeps the original order, just drops the comma)
    $commaRemoved = (string) preg_replace('/,\s+/', ' ', $norm, 1);
    if ($commaRemoved !== $norm) {
        $candidates[] = $commaRemoved;
        $candidates[] = strtolower($commaRemoved);
    }

    // Typo corrections: try the corrected name through the whole pipeline again
    $corrected = apply_typo_corrections($norm);
    if ($corrected !== null) {
        $candidates[] = $corrected;
        // Also invert the corrected name if it follows CAS format
        $correctedInverted = general_comma_reorder($corrected);
        if ($correctedInverted !== $corrected) $candidates[] = $correctedInverted;
        $correctedPlain = plain_comma_reorder($corrected);
        if ($correctedPlain !== null) $candidates[] = $correctedPlain;
    }

    return array_unique($candidates);
}

function general_comma_reorder(string $name): string
{
    // Matches: "<base>, <descriptor>-"  where ", " (with space) is the separator.
    // Locant commas like "1,4-" never have a space after them, so \s+ safely picks
    // the right comma even when the base name or descriptor contains locant commas.
    if (!preg_match('/^(.+?),\s+(.+)-\s*$/', $name, $m)) {
        return $name;
    }
    return $m[2] . '-' . trim($m[1]);
}

function plain_comma_reorder(string $name): ?string
{
    // Handles non-hyphenated CAS inversions: "Peroxide, dimethyl" → "dimethyl Peroxide"
    // Only triggers when separator ", " exists, suffix has no trailing '-' (handled by
    // general_comma_reorder), and the suffix is simple enough to safely invert.
    if (!preg_match('/^(.+?),\s+(.+)$/', $name, $m)) return null;
    $base   = trim($m[1]);
    $suffix = trim($m[2]);
    // Skip if suffix ends with '-' (general_comma_reorder covers that case)
    // or contains ', ' (complex multi-part CAS name we can't safely invert)
    if (str_ends_with($suffix, '-') || str_contains($suffix, ', ')) return null;
    return $suffix . ' ' . $base;
}

function stereo_to_front(string $name): ?string
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

function cas_ester_reorder(string $name): array
{
    $candidates = [];

    // Pattern 1: "Acid_name[, DESCRIPTOR-], ester_prefix ester"
    //   → "ester_prefix [DESCRIPTOR-]acid-ate"
    if (preg_match('/^(.+?),\s+(.+?)\s+ester\s*$/i', $name, $m)) {
        $base = trim($m[1]);
        $rest = trim($m[2]);

        // Split rest by last ", " → descriptor + ester_prefix
        if (preg_match('/^(.+),\s+([^,]+)$/', $rest, $m2)) {
            $descriptor  = trim($m2[1]);
            $esterPrefix = trim($m2[2]);
        } else {
            $descriptor  = '';
            $esterPrefix = $rest;
        }

        // Strip " acid" and optional inline stereodescriptor: "Propenoic acid (Z)-" → "Propenoic"
        $baseEster = (string) preg_replace('/\s+acid(?:\s*\([^)]+\))?\s*-?\s*$/i', '', $base);
        // oic → oate, ic → ate
        $baseEster = (string) preg_replace('/oic$/i', 'oate', $baseEster);
        $baseEster = (string) preg_replace('/ic$/i',  'ate',  $baseEster);

        $body = $descriptor !== '' ? $descriptor . $baseEster : $baseEster;
        $candidates[] = strtolower("{$esterPrefix} {$body}");
    }

    // Pattern 2: "Base, DESCRIPTOR-, acetate/formate/benzoate/..."
    //   → "DESCRIPTOR-base acetate"
    if (preg_match('/^(.+?),\s+(.+?),\s+(acetate|formate|benzoate|propanoate|butyrate)\s*$/i', $name, $m)) {
        $base       = trim($m[1]);
        $descriptor = trim($m[2]);
        $esterName  = strtolower(trim($m[3]));
        $candidates[] = strtolower("{$descriptor}{$base} {$esterName}");
    }

    return array_filter(array_unique($candidates));
}

function apply_typo_corrections(string $name): ?string
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

function greek_to_dotted(string $name): ?string
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

function dotted_to_greek(string $name): ?string
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

function normalize_name(string $name): string
{
    static $greek = [
        'α' => 'alpha',   'β' => 'beta',    'γ' => 'gamma',   'δ' => 'delta',
        'ε' => 'epsilon', 'ζ' => 'zeta',    'η' => 'eta',     'θ' => 'theta',
        'ι' => 'iota',    'κ' => 'kappa',   'λ' => 'lambda',  'μ' => 'mu',
        'ν' => 'nu',      'ξ' => 'xi',      'ο' => 'omicron', 'π' => 'pi',
        'ρ' => 'rho',     'σ' => 'sigma',   'τ' => 'tau',     'υ' => 'upsilon',
        'φ' => 'phi',     'χ' => 'chi',     'ψ' => 'psi',     'ω' => 'omega',
    ];
    $name = strtr($name, $greek);
    // ".alpha." → "alpha" (existing)
    $name = (string) preg_replace('/\.(alpha|beta|gamma|delta|epsilon|theta)\./i', '$1', $name);
    // "alpha Isomethyl ionone" → "alpha-Isomethyl ionone" (leading Greek word + space → hyphen)
    $name = (string) preg_replace('/^(alpha|beta|gamma|delta|epsilon|theta)\s+/i', '$1-', $name);
    return str_replace(['±', '- '], ['+-', '-'], trim($name));
}

function comma_prefix_reorder(string $name): string
{
    static $mods = ['alpha', 'beta', 'gamma', 'delta', 'ortho', 'meta', 'para', 'o', 'm', 'p'];
    if (!preg_match('/^(.+?),\s*([A-Za-z]+)-\s*$/', $name, $m)) return $name;
    if (!in_array(strtolower($m[2]), $mods, true)) return $name;
    return strtolower($m[2]) . '-' . trim($m[1]);
}

// ── HTTP ──────────────────────────────────────────────────────────────────────

function http_json(string $url): ?array
{
    $body = http_raw($url, 'application/json');
    if ($body === null) return null;
    $data = json_decode($body, true);
    return is_array($data) ? $data : null;
}

function http_text(string $url): ?string
{
    return http_raw($url, 'text/plain, */*');
}

function http_raw(string $url, string $accept, int $maxTries = 3): ?string
{
    for ($try = 0; $try < $maxTries; $try++) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => [
                "Accept: {$accept}",
                'User-Agent: CompoundIdentifier/1.0',
            ],
        ]);
        $body   = (string) curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err    = curl_error($ch);
        curl_close($ch);

        if ($err) {
            if ($try < $maxTries - 1) sleep($try + 1);
            continue;
        }
        if ($status === 200 && $body !== '') return $body;
        if ($status === 404 || $status === 400) return null;
        if ($status === 429) { sleep(2 ** $try); continue; }
        if ($try < $maxTries - 1) sleep($try + 1);
    }
    return null;
}
