# Cron Jobs And Workers

## Queue Worker

Development script starts:

```bash
php artisan queue:listen --tries=1
```

`QUEUE_CONNECTION` defaults to `database`.

## Commands

| Command Class | Signature Source | Logic Summary | Tables Touched | Escalation Logic |
| --- | --- | --- | --- | --- |
| `AppRefreshCommand` | class file | App refresh/cache/build helper | Config/cache/runtime | none documented |
| `BusinessFlagsCommand` | class file | Business flag inspection | Config/env | none documented |
| `BusinessProfileMaterializeCommand` | class file | Materialize business profile config | Config/env | none documented |
| `BusinessProfileValidateCommand` | class file | Validate business profile | Config/env | none documented |
| `BusinessStatusCommand` | class file | Business status output | Config/env | none documented |
| `EnvDoctorCommand` | class file | Env diagnostics | Env/config | none documented |
| `EnvReferenceCommand` | class file | Env reference output | Env manifest | none documented |
| `MakeLogCommand` | class file | Generate log artifacts | Code generation | none documented |
| `ModuleAuditCommand` | class file | Module audit | Module files | none documented |
| `ModuleWithModelCommand` | class file | Module/model generation | Code generation | none documented |
| `PlanSyncCommand` | class file | Plan sync helper | Project files | none documented |
| `ProjectKnowledgeCheckCommand` | class file | Project knowledge checks | Project files | none documented |
| `SchemaUniqueAuditCommand` | class file | Unique/index schema audit | DB schema | none documented |
| `SchemaValidationCommand` | class file | Schema validation | DB schema | none documented |
| `UiAuditCommand` | class file | UI standards audit | Blade/assets | none documented |
| `SendRentReminders` | `Modules/Email/app/Console/Commands/SendRentReminders.php` | Sends rent reminder emails | Tenant/payment/email-related models per command implementation | email failures |

## Schedule

No explicit `routes/console.php` schedule entries were verified. Add scheduled
commands there or in Laravel 13 scheduling configuration before relying on cron.
