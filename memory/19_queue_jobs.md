# Queue Jobs

## Job Classes

No explicit job classes were found under `app/Jobs` or module job directories.

## Queue Tables

The project includes database queue support:

- `jobs`
- `job_batches`
- `failed_jobs`

## Dispatch Origin

No explicit `dispatch()` origin map was verified. Email reminders may be command
driven through `Modules/Email/app/Console/Commands/SendRentReminders.php`.

## Retry Policy

Development worker command uses:

```bash
php artisan queue:listen --tries=1
```

Production retry policy should be configured explicitly before queue-backed
features are added.

## Failure Handling

Use Laravel failed jobs table and alerting. No custom failed-job handler was
found.
