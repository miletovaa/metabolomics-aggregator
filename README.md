# Metabolomics Data Management and Aggregation


## Local Development

```bash
php artisan serve --host=localhost --port=8000

npm run dev
```

## Development plan

### 1. Initial project configuration

Core Laravel configuration.
Installation of frontend dependencies.
Installation of Livewire.
Installation of authentication.

### 2. Environment configuration

Configuration of PostgreSQL in .env:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Configuration of Redis for cache, sessions, and queues:

```
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

Installation of Redis client.

```composer require predis/predis```

Creation of the database.


### 3. Database schema implementation

Implementation of the schema according to the current structure:

* compounds
* sources  
* projects
* project_compounds
* compound_synonyms
* retention_indices
* diseases
* compound_disease_associations
* ontologies
* compound_ontologies
* biomarkers
* compound_biomarkers
* taxonomies

Migration execution:

php artisan migrate

### 4. Model layer creation

Configuration of Eloquent models with:

* $fillable
* $casts
* belongsTo
* hasMany
* hasOne
* belongsToMany

### 5. Test data and seeders

Creation of testing data for development.

Records:

* Limonene
* Alpha-Pinene
* Beta-Caryophyllene
* D-Glucose
* Fructose
* Dopamine
* L-Alanine
* L-Glutamic acid
* Palmitic acid
* Oleic acid

Creation of seeders:

php artisan make:seeder SourceSeeder
php artisan make:seeder CompoundSeeder
php artisan make:seeder ProjectSeeder

Initial sources:

* PubChem
* HMDB
* ChEBI
* NIST

Seeder execution:

php artisan db:seed


### 6. API authentication configuration

Installation and configuration of Sanctum:

php artisan install:api
php artisan migrate

Addition of HasApiTokens to User.

Creation of authentication endpoints:

* POST /api/login
* POST /api/logout
* GET /api/me

Protected API route group:

```php
Route::name('api.')
    ->middleware('auth:sanctum')
    ->group(function () {
        // API resources here
    });
```

### 7. API resources

Creation of JSON resources.


### 8. API controllers and endpoints

Creation of controllers and endpoints.

### 9. Validation layer

Creation of request classes.

Validation responsibilities:

* required compound names
* unique external identifiers
* valid nullable compound mappings
* numeric mz and rt
* valid project ownership
* valid source references


### 10. External database integration layer

Creation of source service classes:

app/Services/Sources/PubChemService.php
app/Services/Sources/HmdbService.php
app/Services/Sources/ChebiService.php

Each source service should support:

* search by compound name
* fetch by external identifier
* response normalization
* error handling
* retry behavior
* rate-limit awareness

Suggested service methods:

searchByName(string $name): array
fetchById(string $id): array
normalize(array $payload): array


### 11. Normalization and upsert layer

Creation of normalization services:

app/Services/Normalization/CompoundNormalizer.php
app/Services/Compounds/CompoundUpsertService.php

Responsibilities:

* conversion of source payloads into internal field names
* canonical compound creation or update
* synonym creation
* taxonomy update
* retention index insertion
* disease / ontology / biomarker association handling
* source provenance attachment


### 12. Matching layer for imported compounds

Creation of a matching service:

app/Services/Matching/CompoundMatcher.php

Matching order:

1. exact inchikey
2. exact external identifier
3. exact canonical_name
4. exact synonym
5. normalized synonym
6. external API fallback
7. manual review state

Recommended future addition:

normalized_name

in compound_synonyms for better import matching.


### 13. Excel import and export configuration

Installation of Laravel Excel:

composer require maatwebsite/excel

Creation of import/export classes:

php artisan make:import ProjectCompoundsImport
php artisan make:export CompoundsExport
php artisan make:export ProjectCompoundsExport

Import workflow:

* file upload
* row parsing
* compound name extraction
* matching through CompoundMatcher
* creation of project_compounds
* marking rows as mapped, unmapped, or duplicate

Export workflow:

* flat compound table export
* project-specific compound export
* source comparison export later


### 14. Queue and background jobs

Creation of jobs:

php artisan make:job FetchCompoundFromSourceJob
php artisan make:job ProcessImportFileJob
php artisan make:job MatchProjectCompoundJob
php artisan make:job RefreshCompoundDataJob

Queue usage areas:

* external API calls
* batch imports
* compound matching
* source refresh
* export generation

Local worker:

php artisan queue:work


### 15. Livewire frontend foundation

Creation of basic pages:

php artisan make:livewire Compounds/Index
php artisan make:livewire Compounds/Show
php artisan make:livewire Projects/Index
php artisan make:livewire Projects/Show
php artisan make:livewire Imports/Upload

Initial frontend routes:

Route::middleware(['auth'])->group(function () {
    Route::get('/compounds', \App\Livewire\Compounds\Index::class)->name('compounds.index');
    Route::get('/compounds/{compound}', \App\Livewire\Compounds\Show::class)->name('compounds.show');
    Route::get('/projects', \App\Livewire\Projects\Index::class)->name('projects.index');
    Route::get('/projects/{project}', \App\Livewire\Projects\Show::class)->name('projects.show');
});

Frontend pages:

* compounds listing
* compound detail page
* project listing
* project detail page
* project compound table
* Excel upload page
* mapping review page


### 16. Compounds listing page

Features:

* paginated compounds table
* search by:
    * canonical name
    * IUPAC name
    * InChIKey
    * HMDB ID
    * ChEBI ID
    * PubChem CID
    * synonym
* sorting by ID, name, formula
* compact taxonomy display
* link to compound detail page

Recommended query relations:

```php
Compound::query()
    ->with(['taxonomy', 'synonyms.source'])
```

### 17. Compound detail page

Displayed sections:

* core identifiers
* names and synonyms
* taxonomy
* retention indices
* diseases
* ontologies
* biomarkers
* linked projects
* source provenance later

This page should avoid excessive editing logic at first. Initial goal: reliable display.


### 18. Project workflow

Project pages should support:

* project creation
* project metadata editing
* project compound listing
* manual compound attachment
* unmapped compound rows
* duplicate marking
* notes
* mz and rt display
* export of project compounds

Important distinction:

* compounds = canonical database records
* project_compounds = project-specific experimental observations


### 19. Source refresh workflow

Creation of source refresh endpoints and jobs:

* refresh one compound from PubChem
* refresh one compound from HMDB
* refresh one compound from ChEBI
* batch refresh selected compounds
* scheduled refresh for active project compounds

Recommended future table:

source_records

Fields:

* id
* compound_id nullable
* source_id
* external_id
* raw_payload json
* fetched_at
* payload_hash
* status
* timestamps

This would make source comparison and re-parsing much easier.


### 20. Import review workflow

Import workflow pages:

* upload Excel
* preview parsed rows
* automatic matching results
* ambiguous result review
* unmatched compound handling
* confirmation
* creation of project_compounds

Recommended future tables:

import_batches
import_rows

These would preserve uploaded file history and matching status.


### 21. Testing

Test categories:

* model relationship tests
* API endpoint tests
* validation tests
* import parsing tests
* matching tests
* source integration tests
* Livewire component tests

Useful commands:

php artisan test
php artisan route:list
php artisan route:list --path=api


### 22. Local development workflow

Regular local startup:

php artisan serve --host=localhost --port=8000
npm run dev
php artisan queue:work

Browser URL:

http://localhost:8000

Vite URL should not be opened directly:

http://localhost:5173


### 23. Deployment preparation

Deployment checklist:

* production .env
* PostgreSQL database
* Redis server
* queue worker via Supervisor
* scheduler via cron
* storage link
* database backups
* API token security
* rate limiting
* logging for failed external fetches

Production commands:

composer install --no-dev --optimize-autoloader
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache


### Development order summary

- [x] 1. Project configuration
- [x] 2. Database schema creation
- [x] 3. Model relationship setup
- [x] 4. Seed data creation
- [ ] 5. API resources and controllers
- [ ] 6. Sanctum authentication
- [ ] 7. API validation
- [ ] 8. External source services
- [ ] 9. Normalization and matching services
- [ ] 10. Manual GUI settings for index and show pages and import/export
- [ ] 11. Make tables editable + make custom name for ProjectCompounds
- [ ] 12. Import/export pipeline
- [ ] 13. Queue jobs
- [ ] 14. Livewire compounds page
- [ ] 15. Compound detail page
- [ ] 16. Project pages
- [ ] 17. Import review UI
- [ ] 18. Source refresh workflow
- [ ] 19. Testing and deployment preparation


## Database schema

### Compounds:

    id
    canonical_name string(255)
    iupac_name string(255) nullable
    molecular_formula string(255) nullable
    smiles text nullable
    inchi text unique nullable
    inchikey string(30) unique nullable
    pubchem_cid string(255) unique nullable
    cas string(255) nullable
    hmdb_id string(255) unique nullable
    chebi_id string(255) unique nullable
    timestamps

### Sources:

    id
    name string(255)
    timestamps

### Projects:

    id
    name string(255) unique
    description text nullable
    status string(50) default 'active'
    user_id foreign_id
    timestamps

### Project_compounds:

    id
    project_id foreign_id
    compound_id foreign_id nullable
    custom_name string(255) nullable
    is_duplicate boolean default false
    mz decimal(20, 10) nullable
    rt decimal(20, 10) nullable
    is_mapped boolean default false
    notes text nullable
    timestamps
    
### Compound_synonyms:

    id
    name string(255)
    compound_id foreign_id
    source_id foreign_id
    timestamps

### Retention_indices:

    id
    compound_id foreign_id
    value decimal(20, 10)
    column_type string(50)
    is_polar boolean default false
    reference text nullable
    source_id foreign_id nullable
    timestamps
    
### Diseases:

    id
    name
    description text nullable
    category string(255) nullable
    timestamps
    
### Compound_disease_associations:

    id
    compound_id foreign_id
    disease_id foreign_id
    reference text nullable
    source_id foreign_id nullable
    timestamps
    
### Ontologies:

    id
    name string(255) unique
    type string(255) nullable
    description string(1000) nullable
    timestamps

### Compound_ontologies:

    id
    compound_id foreign_id
    ontology_id foreign_id
    reference text nullable
    source_id foreign_id nullable
    timestamps

### Biomarkers:

    id
    name string(255)
    description text nullable
    timestamps

### Compound_biomarkers:

    id
    compound_id foreign_id
    biomarker_id foreign_id
    reference text nullable
    source_id foreign_id nullable
    timestamps

### Taxonomies:

    id
    compound_id foreign_id
    kingdom string(255) nullable
    superclass string(255) nullable
    class string(255) nullable
    subclass string(255) nullable
    direct_parent string(255) nullable
    alternative_parents string(255) nullable
    raw_json json nullable
    timestamps

### Samples:

    id
    lab_sample_id string nullable
    external_id string nullable
    matrix_group string nullable
    sample_group string(50) — food | environment | human_medical | animal
    sample_subgroup string(50) nullable — depends on sample_group, see Sample::SUBGROUPS
    date_received date nullable
    storage_condition string(50) nullable — dark_room_temp | refrigerated | frozen | deep_frozen
    storage_condition_details json nullable — multi-select: vacuum_sealed, inert_gas, sterile, controlled_humidity, dry, styrofoam_box_with_ice
    responsible_analyst_id foreign_id nullable (users)
    project_id foreign_id nullable (projects)
    purpose_of_analysis json nullable — multi-select, see Sample::PURPOSES_OF_ANALYSIS
    planned_analysis json nullable — multi-select, see Sample::PLANNED_ANALYSES
    type_details json nullable — free-form, shape depends on sample_group (see below)
    timestamps

`type_details` holds the fields that only apply to one `sample_group` (via `Sample::TYPE_DETAIL_GROUPS`), instead of a wide mostly-null table (same pattern as `Taxonomies.raw_json`):

* **plant**: latin_name, part_of_plant (roots, stems, leaves, flowers, fruits, seeds, buds, rhizomes_tubers, wood, epidermal_tissues), harvest_year, status (authentic_slo, test_slo, abroad), producer (authentic, market), production_type (organic, conventional), declared_country_of_origin, country_of_origin_of_raw_material, region_of_origin, irrigation (yes/no), source_of_water (surface_water, groundwater, rainwater, treated_wastewater), processing_type (raw, fresh, frozen, fermented, canned_preserved, dried, freeze_dried), note
* **animal**: common_name, latin_name, part_of_animal (muscle, fat, bone, milk, eggs, skin, liver, kidney, heart, lungs, spleen, brain), status (authentic_slo, test_slo, abroad), producer (authentic_slo, test_slo, abroad), production_type (organic, conventional), country_of_origin, region_of_origin, feed (multi-select: forage, silage, hay, concentrates, protein_feed_animal, protein_feed_plant, mineral_supplements, vitamin_supplements, by_product_feeds, complete_feeds, medications_probiotics), source_of_drinking_water (surface_water, groundwater, rainwater, treated_wastewater), processing_type (raw, fresh, frozen, fermented, canned_preserved, dried, freeze_dried, minced, cured), note
* **environmental**: depth, temperature_at_sampling, ph, conductivity, note

All option lists live as constants on `App\Models\Sample` (`GROUPS`, `SUBGROUPS`, `STORAGE_CONDITIONS`, `STORAGE_CONDITION_DETAILS`, `PURPOSES_OF_ANALYSIS`, `PLANNED_ANALYSES`, `TYPE_DETAIL_GROUPS`, `STATUS_OPTIONS`, `PRODUCTION_TYPES`, `SOURCE_OF_WATER`, `PART_OF_PLANT`, `PLANT_PRODUCER`, `PLANT_PROCESSING_TYPES`, `PART_OF_ANIMAL`, `ANIMAL_PROCESSING_TYPES`, `ANIMAL_FEED_TYPES`) rather than lookup tables, matching the existing `Project::STATUSES` convention.

Pages: `/samples` (list, search, delete), `/samples/create`, `/samples/{sample}/edit` — the project field is a searchable combobox that lets you pick an existing project or create one on the fly by name (`Project::firstOrCreate`).

### Samplings:

Collection-event details for a sample: when, where, how, and by whom it was physically collected. One-to-one with `Samples` (`sample_id` is unique) — this is *not* a shared reference/technique catalog, it's per-event workspace data, so it lives under the Workspace nav group alongside Projects/Experiments/Samples, not under Catalog.

    id
    sample_id foreign_id unique (samples)
    date_of_sampling date nullable
    country_of_sampling string nullable
    place_of_sampling string nullable
    gerk string nullable — Slovenian agricultural land-parcel code, where applicable
    gps_lat decimal(10,6) nullable
    gps_lon decimal(10,6) nullable
    altitude decimal(8,2) nullable
    sampling_method string(50) nullable — see Sampling::SAMPLING_METHODS
    packaging string(50) nullable — see Sampling::PACKAGING_OPTIONS
    collector string nullable — free text; not necessarily a system user
    timestamps

Pages: `/samplings`, `/samplings/create` (pick any sample that doesn't already have a sampling record), `/samplings/{sampling}/edit`.

### Compounds (extended):

    lipid_class string(50) nullable — SFA | MUFA | MUFA-trans | PUFA | PUFA-trans | PUFA (ω-3) | PUFA (ω-6), fatty acids only

The 38-fatty-acid panel used by the MK GC-MS / MK GC-IRMS result tables is seeded into the existing `compounds` catalog (matched/deduplicated by CAS number) rather than a parallel lookup table — `database/seeders/FattyAcidCompoundSeeder.php`. Each fatty acid's lab code (e.g. `C18:1 cis-9`) and common name (e.g. `Oleic acid`) are added as `compound_synonyms` alongside whatever canonical/IUPAC name the compound already had from PubChem/HMDB sync.

### Experiments:

The umbrella entity for a conducted experiment; the actual prep/analysis/result data lives in `experiment_records`, not on this table.

    id
    project_id foreign_id nullable (projects)
    name string
    description text nullable
    status string(50) default 'planned' — planned | in_progress | completed
    started_at date nullable
    completed_at date nullable
    created_by foreign_id nullable (users)
    timestamps

### Experiment_records:

A single table holding every sample-preparation step, analysis run, and result row for an experiment, discriminated by `record_type`. This was a deliberate simplification over an earlier draft that split these into separate `SamplePreparation`/`AnalysisRun`/`Result` tables — one table with a type column and a self-referencing `parent_record_id` keeps the lineage (prep → analysis → result) as a simple self-join instead of a three-way join, and each repeating group (e.g. the isotope panel: δ18O, δ2H, δ13C, δ15N, δ34S, plus δ13C's per-fraction variants) becomes multiple rows of the same `record_type` instead of a wide, mostly-null table.

    id
    experiment_id foreign_id (experiments)
    sample_id foreign_id (samples)
    parent_record_id foreign_id nullable (experiment_records) — links a result to the analysis run that produced it, or an analysis to the prep batch it consumed
    record_type string(50) — see below
    performed_by foreign_id nullable (users) — generic "done by / analysed by"
    performed_at date nullable — generic "date of X"
    note text nullable
    details json nullable — shape depends on record_type, see ExperimentRecord::fieldSchema()
    timestamps

`record_type` values, grouped into three families (`ExperimentRecord::FAMILIES`):

* **Preparation**: `sample_prep`, `sample_prep_microwave_digestion`
* **Analysis**: `analysis_isotopes`, `analysis_elemental_composition`, `analysis_mk_gc_ms`, `analysis_voc_gc_ms`, `analysis_mk_gc_irms`, `analysis_voc_gc_irms`
* **Result**: `result_stable_isotopes`, `result_elemental_composition`, `result_mk_gc_ms`, `result_mk_gc_irms`, `result_voc_gc_ms`, `result_voc_gc_irms`

Notes on the less obvious fields:

* `analysis_isotopes` / `result_stable_isotopes` both key off the same `analyte` vocabulary (`ExperimentRecord::ANALYTES`: δ18Owater, δ18O, δ2H, δ13C, δ13Cfat, δ13Cdefatted, δ13Cpulp, δ13Ckazein, δ13Cprotein, δ13Csugar, δ13Cethanol, δ15N, δ34S) — one `experiment_records` row per analyte, not one wide row with 13 value/stdev column pairs.
* `analysis_mk_gc_ms`/`analysis_mk_gc_irms` use liquid-injection fields (`mps_syringe`, `inj_volume_ul`, `rinse_settings`); `analysis_voc_gc_ms`/`analysis_voc_gc_irms` use SPME/headspace fields (`type_of_fiber`, `spme_parameters_min`, `fiber_bakeout_min`) — MK vs. VOC is the injection-method split, not a separate dimension.
* `result_mk_gc_ms`/`result_mk_gc_irms` reference `compound_id` via a fixed dropdown of the seeded fatty-acid `Compound` rows (`ExperimentRecord::fieldSchema()` type `fatty_acid_select`). `result_voc_gc_ms`/`result_voc_gc_irms` reference `compound_id` via a live-search combobox over the *entire* compound catalog, with the ability to create a new `Compound` on the fly (type `compound_combobox`) — VOC identification is open-ended, unlike the fixed MK fatty-acid panel.
* `result_elemental_composition`'s shape (`element`, `value`, `stdev`, `unit`) is provisional — no source header was given for this one, unlike the other five result types, so it's a best-guess placeholder (C/H/N/O/S) pending confirmation.

Pages: `/experiments` (list, delete), `/experiments/create`, `/experiments/{experiment}` (show — records grouped by family, with an "Add Record" action), `/experiments/{experiment}/records/create`, `/experiments/{experiment}/records/{record}/edit`. The add/edit record form is driven entirely by `ExperimentRecord::fieldSchema($recordType)` — a declarative field list (key, label, input type, options) — so the 12+ record types share one generic renderer instead of one hand-written form per type.

## Entity architecture: Workspace vs. Catalog

Entities split into two kinds, reflected in navigation and the dashboard:

* **Catalog** (shared reference data, not owned by a project): Compounds.
* **Workspace** (owned/scoped, day-to-day lab work): Projects, Experiments, Samples, Samplings.

Samplings was originally planned as a Catalog entity (a shared "sampling technique" reference), but once its actual fields turned out to be collection-event-specific (GPS, date, collector), it was moved to Workspace and modeled as `Sampling belongsTo Sample` (1:1) rather than the other way around. With Samplings gone, Catalog is down to a single entity, so the header nav's "Catalog" dropdown was flattened into a plain top-level "Compounds" link.