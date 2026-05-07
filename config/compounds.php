<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Local SQLite database paths
    |--------------------------------------------------------------------------
    |
    | Place your hmdb.sqlite and nist.sqlite in storage/app/databases/ (default)
    | or set the paths via environment variables before running the import
    | commands:
    |   php artisan import:hmdb
    |   php artisan import:nist
    |
    */

    'hmdb_sqlite_path' => env(
        'HMDB_SQLITE_PATH',
        storage_path('app/databases/hmdb.sqlite')
    ),

    'nist_sqlite_path' => env(
        'NIST_SQLITE_PATH',
        storage_path('app/databases/nist.sqlite')
    ),
];