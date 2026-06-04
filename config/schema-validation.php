<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Schema Validation Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the schema:validation artisan command that generates
    | validation rules from database table schemas.
    |
    */

    // Columns to skip when generating validation rules (system/audit fields).
    // These are NEVER user-submittable — they're populated automatically by
    // model traits or framework events:
    //   - id / *_at / *_by  : Eloquent + defaultMigration() boilerplate
    //   - public_id         : HasPublicId trait (ULID auto-generated on create)
    'skip_columns' => [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'updated_by',
        'deleted_by',
        'public_id',
        // Workflow-managed columns — populated by controller workflow methods
        // (updateStatus, approval transaction, version snapshot service).
        // Never appear in Store/Update FormRequest rules; the state machine + service set them.
        'status',
        'revision_no',
        'current_version_id',
        'reviewed_by',
        'reviewed_at',
        'approved_by',
        'approved_at',
    ],

    // Database connection to use for schema introspection.
    //
    // Mirrors `config/database.php`'s `default` resolution: when the developer
    // sets USE_LOCAL_DB=true, schema introspection must hit the local DB
    // (mariadb_local) — same as runtime queries — rather than the remote
    // production DB pointed to by DB_CONNECTION/DB_HOST. Without this branch,
    // `schema:validation` silently reported "table not found" on freshly-
    // migrated local modules because it was querying the remote DB.
    'connection' => filter_var(env('USE_LOCAL_DB', false), FILTER_VALIDATE_BOOLEAN)
        ? 'mariadb_local'
        : env('DB_CONNECTION', 'mariadb'),

    // Modules to exclude from schema validation generation
    'excluded_modules' => [],

    // JSON output path relative to each module's Requests directory
    // Files are saved as: Modules/{Module}/app/Http/Requests/{module}-{action}.json
    'json_output' => true,
];
