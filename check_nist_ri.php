#!/usr/bin/env php
<?php
/**
 * NIST RI consistency checker.
 *
 * Reads the NIST SQLite database, detects compounds whose retention index
 * values are inconsistent across entries sharing the same column polarity,
 * and writes the results to a separate analysis SQLite + a text report.
 *
 * Usage:
 *   php check_nist_ri.php [nist.sqlite] [analysis.sqlite] [report.tsv]
 *
 * Defaults:
 *   nist.sqlite   → /Users/annamiletova/ijs/p01_pd_biomarkers/data/nist.sqlite
 *   analysis.sqlite → nist_ri_analysis.sqlite  (created in current dir)
 *   report.tsv      → nist_ri_duplicates.tsv
 *
 * Flags:
 *   --threshold=N   RI range that triggers a duplicate flag (default: 100)
 *   --min-count=N   minimum number of entries per group (default: 2)
 */

declare(strict_types=1);

// ── CLI arguments ─────────────────────────────────────────────────────────────

$srcDb     = null;
$outDb     = null;
$reportFile = null;
$threshold  = 100.0;
$minCount   = 2;

foreach (array_slice($argv, 1) as $arg) {
    if (preg_match('/^--threshold=(\d+(?:\.\d+)?)$/', $arg, $m)) { $threshold = (float) $m[1]; continue; }
    if (preg_match('/^--min-count=(\d+)$/', $arg, $m))           { $minCount  = (int)   $m[1]; continue; }
    if ($srcDb     === null) { $srcDb      = $arg; continue; }
    if ($outDb     === null) { $outDb      = $arg; continue; }
    if ($reportFile === null) { $reportFile = $arg; continue; }
}

$srcDb      ??= '/Users/annamiletova/ijs/p01_pd_biomarkers/data/nist.sqlite';
$outDb      ??= 'nist_ri_analysis.sqlite';
$reportFile ??= 'nist_ri_duplicates.tsv';

if (!file_exists($srcDb)) {
    fwrite(STDERR, "Source DB not found: {$srcDb}\n");
    fwrite(STDERR, "Usage: php check_nist_ri.php [nist.sqlite] [analysis.sqlite] [report.tsv] [--threshold=100] [--min-count=2]\n");
    exit(1);
}

echo "Source  : {$srcDb}\n";
echo "Analysis: {$outDb}\n";
echo "Report  : {$reportFile}\n";
echo "Threshold: RI range > {$threshold} units flagged as inconsistent\n\n";

// ── Open source DB ────────────────────────────────────────────────────────────

$src = new PDO("sqlite:{$srcDb}");
$src->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ── Create analysis DB ────────────────────────────────────────────────────────

if (file_exists($outDb)) {
    unlink($outDb);
    echo "Removed existing analysis DB.\n";
}

$out = new PDO("sqlite:{$outDb}");
$out->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$out->exec('PRAGMA journal_mode=WAL');
$out->exec('PRAGMA synchronous=NORMAL');

$out->exec(<<<SQL
    -- One row per (compound_name, column_polarity) group
    CREATE TABLE ri_summary (
        id               INTEGER PRIMARY KEY AUTOINCREMENT,
        compound_name    TEXT    NOT NULL,
        column_polarity  TEXT    NOT NULL,
        entry_count      INTEGER NOT NULL,
        ri_min           REAL,
        ri_max           REAL,
        ri_range         REAL,
        ri_mean          REAL,
        ri_values        TEXT,   -- pipe-separated list of all RI values
        active_phases    TEXT,   -- pipe-separated distinct phases
        is_inconsistent  INTEGER NOT NULL DEFAULT 0  -- 1 if range > threshold
    );

    -- One row per RI entry that deviates significantly from the group mean
    CREATE TABLE ri_outliers (
        id               INTEGER PRIMARY KEY AUTOINCREMENT,
        summary_id       INTEGER REFERENCES ri_summary(id),
        compound_name    TEXT    NOT NULL,
        column_polarity  TEXT    NOT NULL,
        ri               REAL    NOT NULL,
        active_phase     TEXT,
        deviation        REAL,   -- ri - group mean
        reference        TEXT,
        comment          TEXT
    );

    CREATE INDEX idx_summary_name    ON ri_summary (compound_name);
    CREATE INDEX idx_summary_incons  ON ri_summary (is_inconsistent);
    CREATE INDEX idx_outlier_summary ON ri_outliers (summary_id);
SQL);

// ── Aggregate from source ─────────────────────────────────────────────────────

echo "Aggregating RI data by (compound_name, column_polarity)...\n";

$insertSummary = $out->prepare(<<<SQL
    INSERT INTO ri_summary
        (compound_name, column_polarity, entry_count, ri_min, ri_max, ri_range, ri_mean, ri_values, active_phases, is_inconsistent)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
SQL);

$insertOutlier = $out->prepare(<<<SQL
    INSERT INTO ri_outliers
        (summary_id, compound_name, column_polarity, ri, active_phase, deviation, reference, comment)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
SQL);

// Pull each group from source
$groups = $src->query(<<<SQL
    SELECT
        compound_name,
        column_polarity,
        COUNT(*)                                   AS entry_count,
        MIN(ri)                                    AS ri_min,
        MAX(ri)                                    AS ri_max,
        MAX(ri) - MIN(ri)                          AS ri_range,
        AVG(ri)                                    AS ri_mean,
        GROUP_CONCAT(CAST(ri AS TEXT), '|')        AS ri_values,
        GROUP_CONCAT(active_phase, '|')            AS active_phases
    FROM nist_compounds
    WHERE ri IS NOT NULL AND ri > 0
      AND compound_name IS NOT NULL AND compound_name != ''
    GROUP BY compound_name, column_polarity
    HAVING COUNT(*) >= {$minCount}
    ORDER BY compound_name, column_polarity
SQL);

$totalGroups    = 0;
$inconsistent   = 0;
$totalOutliers  = 0;

$out->beginTransaction();

foreach ($groups as $g) {
    $totalGroups++;
    $isIncons = ($g['ri_range'] > $threshold) ? 1 : 0;
    if ($isIncons) $inconsistent++;

    $insertSummary->execute([
        $g['compound_name'],
        $g['column_polarity'],
        $g['entry_count'],
        $g['ri_min'],
        $g['ri_max'],
        $g['ri_range'],
        $g['ri_mean'],
        $g['ri_values'],
        $g['active_phases'],
        $isIncons,
    ]);
    $summaryId = (int) $out->lastInsertId();

    // If inconsistent, fetch individual rows to identify outliers
    if ($isIncons) {
        $mean = (float) $g['ri_mean'];
        $rows = $src->prepare(<<<SQL
            SELECT ri, active_phase, reference, comment
            FROM nist_compounds
            WHERE compound_name = ? AND column_polarity = ? AND ri > 0
        SQL);
        $rows->execute([$g['compound_name'], $g['column_polarity']]);

        foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $dev = (float) $r['ri'] - $mean;
            // Flag as outlier if deviation > threshold/2 from the mean
            if (abs($dev) > $threshold / 2) {
                $insertOutlier->execute([
                    $summaryId,
                    $g['compound_name'],
                    $g['column_polarity'],
                    $r['ri'],
                    $r['active_phase'],
                    round($dev, 2),
                    $r['reference'],
                    $r['comment'],
                ]);
                $totalOutliers++;
            }
        }
    }

    if ($totalGroups % 5000 === 0) {
        $out->commit();
        $out->beginTransaction();
        printf("  processed %d groups (%d inconsistent)...\n", $totalGroups, $inconsistent);
    }
}

$out->commit();

echo "\nDone aggregating.\n";
printf("  Total groups      : %d\n",  $totalGroups);
printf("  Inconsistent       : %d  (range > %.0f RI units)\n", $inconsistent, $threshold);
printf("  Outlier entries    : %d\n", $totalOutliers);

// ── Write TSV report ──────────────────────────────────────────────────────────

echo "\nWriting report → {$reportFile}\n";

$report = $out->query(<<<SQL
    SELECT
        compound_name,
        column_polarity,
        entry_count,
        ROUND(ri_min,  1) AS ri_min,
        ROUND(ri_max,  1) AS ri_max,
        ROUND(ri_range, 1) AS ri_range,
        ROUND(ri_mean, 1) AS ri_mean,
        ri_values,
        active_phases
    FROM ri_summary
    WHERE is_inconsistent = 1
    ORDER BY ri_range DESC, compound_name
SQL);

$fh = fopen($reportFile, 'w') or die("Cannot open {$reportFile}\n");
fwrite($fh, implode("\t", [
    'compound_name', 'column_polarity', 'entry_count',
    'ri_min', 'ri_max', 'ri_range', 'ri_mean',
    'ri_values', 'active_phases',
]) . "\n");

$printed = 0;
foreach ($report->fetchAll(PDO::FETCH_ASSOC) as $row) {
    fwrite($fh, implode("\t", array_values($row)) . "\n");

    if ($printed < 20) {
        printf(
            "  %-50s | %-12s | n=%-3d | RI %.0f–%.0f (range=%.0f)\n",
            substr($row['compound_name'], 0, 50),
            $row['column_polarity'],
            $row['entry_count'],
            $row['ri_min'],
            $row['ri_max'],
            $row['ri_range'],
        );
        $printed++;
    }
}
if ($inconsistent > 20) {
    echo "  ... and " . ($inconsistent - 20) . " more — see {$reportFile}\n";
}
fclose($fh);

// ── Summary stats by polarity ─────────────────────────────────────────────────

echo "\nInconsistent compounds by column polarity:\n";
$stats = $out->query(<<<SQL
    SELECT column_polarity, COUNT(*) AS cnt
    FROM ri_summary
    WHERE is_inconsistent = 1
    GROUP BY column_polarity
    ORDER BY cnt DESC
SQL);
foreach ($stats->fetchAll(PDO::FETCH_ASSOC) as $s) {
    printf("  %-20s: %d\n", $s['column_polarity'], $s['cnt']);
}

echo "\nAnalysis DB tables:\n";
echo "  ri_summary  — one row per (compound, polarity) group\n";
echo "  ri_outliers — individual RI entries that deviate from group mean\n";
echo "\nDone.\n";
