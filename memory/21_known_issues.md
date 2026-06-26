# Known Issues

## Incomplete Features

- No dedicated mobile offline sync implementation found.
- No work-order state machine found.
- No standalone report module found.
- No universal approval/lock workflow found.
- Queue infrastructure exists, but explicit job classes were not found.

## Partial Implementations

- Payment verification exists, but edit/delete lock rules should be audited.
- Dashboard is implemented, but KPI/report SQL should be tested before business
  decisions rely on it.
- Env variable management is powerful and should be restricted carefully.
- Tenant creation writes user/profile/tenant records and must remain
  transactional.

## Technical Debt

- Some modules use generated CRUD patterns; validate each workflow before adding
  business rules.
- Status value casing is inconsistent across modules (`active` vs `Active`).
- API route auth expectations should be explicitly documented per module.
- Full column documentation should be regenerated from migrations after every
  schema change.

## Refactor Needed

- Centralize payment lock/verification policy.
- Add explicit occupancy/capacity enforcement if required by business.
- Add report services for collection, occupancy, complaint aging, and tenant
  ledger reports.
- Add clear API resources if external/mobile clients are planned.
