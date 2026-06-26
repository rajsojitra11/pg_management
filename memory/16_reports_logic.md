# Reports Logic

## Current Report Surfaces

No standalone report module was found. Reporting-style logic currently lives in
the dashboard and export-capable pages.

| Report/Surface | SQL Logic | Filters | Aggregation | Export Type | Performance |
| --- | --- | --- | --- | --- | --- |
| Dashboard KPI | `DashboardService` queries | user/year/widget/date | counts/sums | JSON | use indexes and date filters |
| Dashboard charts | `DashboardService` queries | date range/widget | grouped series | JSON | avoid unbounded ranges |
| Dashboard tables | `DashbordController@getTableData` | type/year/date | recent records | JSON | eager load relations |
| Menu export | `MenuMasterController@export` | menu tree/search | hierarchy output | controller-defined | tree recursion risk |
| DataTables indexes | controller query builders | search/status/module filters | paginated rows | JSON | ensure server-side pagination |

## Missing Reports

- Tenant ledger report
- Payment collection report
- Occupancy report
- Complaint aging report
- Maintenance cost report
- Subscription revenue report

Add these as explicit report services instead of embedding SQL in Blade.
