# Testing Strategy

## Current Test Style

The project uses Pest with feature tests under `tests/Feature` and unit tests
under `tests/Unit`.

## Unit Tests

Recommended areas:

- `HasPublicId`
- `HasActivityLogging`
- `AccessGate`
- Dashboard service calculations
- Menu hierarchy service
- Env file service

## Feature Tests

Existing feature areas include login, lookup routes, modules, schema audits,
logging audits, and standards audits.

Recommended CRUD coverage for each module:

- index requires auth
- store validates required fields
- update validates and persists changes
- destroy soft deletes and writes audit metadata
- DataTables AJAX response shape

## State Machine Tests

No work-order state machine exists. Add tests if implementing:

- allowed transitions
- forbidden transitions
- auto-derived statuses
- locked approved/verified state behavior

## Cron Test Plan

- Test command signatures.
- Test command happy path.
- Test command no-data path.
- Test command failure path.
- Test tables touched by command.
- Test email reminder dry-run behavior before sending real mail.

## Approval Flow Tests

Payment verification should have focused tests:

- pending -> verified
- verified -> pending if reversal is allowed
- verified payment edit blocked if business requires lock
- verified payment delete blocked if business requires lock
- override permission behavior

## Required Verification Commands

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
```
