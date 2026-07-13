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