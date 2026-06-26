# Services And State Machine

## Services

| Service | Purpose |
| --- | --- |
| `App\Services\SchemaValidationService` | Schema validation/audit support |
| `App\Services\ModuleComplianceScanner` | Module compliance scanning |
| `App\Services\AccessGate` | Central access checks |
| `App\Services\EnvManifest\EnvManifest` | Environment manifest handling |
| `Modules\EnvVariable\app\Services\EnvFileService` | `.env` file read/write/sync logic |
| `Modules\MenuMaster\app\Services\MenuMasterService` | Menu hierarchy operations |
| `Modules\Dashbord\app\Services\DashboardService` | Dashboard widgets/data |
| `Modules\Dashbord\app\Services\PrintDashboardService` | Printable/dashboard data service |

## Work Order Derived Status Rules

No work-order module or work-order state machine exists in this codebase.

## Transition Conditions

Implemented status fields are local to modules:

- Payment: `verified` transitions between `pending` and `verified` through
  `PaymentController@toggleVerified`.
- Complaint: `status` defaults to `pending`.
- PG: `status` is `active` or `inactive`.
- Tenant: `status` is `Active` or `Inactive`.
- Subscription: `status` defaults to `active`; `payment_status` defaults to
  `pending`.

## Auto Calculation Logic

- Dashboard services calculate KPI/chart/table data.
- No universal accounting ledger, room occupancy recalculation service, or
  work-order auto status service was found.

## Forbidden Manual Overrides

- Do not manually override `public_id`.
- Do not manually write log rows except through established logging helpers.
- Verified payments should be treated as locked unless controller/request logic
  explicitly allows a change.

## Edge Cases

- Tenant creation writes multiple tables; partial failure must stay wrapped in a
  transaction.
- Payment PG/room should match tenant PG/room where business rules require it.
- Complaint service should belong to selected service category.
- Room capacity is not globally enforced unless implemented in controller logic.
