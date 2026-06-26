# API Contracts

## API Authentication

API routes are loaded under Laravel's `api` middleware group plus
`CheckAccessType:mobile`. Auth requirements depend on module route definitions
and should be verified per endpoint before mobile or external integrations.

## Resource Contract Pattern

Most API endpoints follow Laravel resource shape:

| Method | URL Pattern | Controller Method | Request Body | Response | Errors |
| --- | --- | --- | --- | --- | --- |
| GET | `/api/v1/{resource}` | `index` | query params/search | JSON/list or controller-specific response | 401/403/500 |
| POST | `/api/v1/{resource}` | `store` | FormRequest fields | JSON/create response | 422/401/403/500 |
| GET | `/api/v1/{resource}/{id}` | `show` | none | JSON/detail | 404/401/403 |
| PUT/PATCH | `/api/v1/{resource}/{id}` | `update` | FormRequest fields | JSON/update response | 422/404/401/403 |
| DELETE | `/api/v1/{resource}/{id}` | `destroy` | Delete request fields where required | JSON/delete response | 422/404/401/403 |

## API Resource Bases

Current resource bases include:

- `/api/v1/cities`
- `/api/v1/countries`
- `/api/v1/currencies`
- `/api/v1/dashbords`
- `/api/v1/env-variables`
- `/api/v1/logins`
- `/api/v1/menumasters`
- `/api/v1/payments`
- `/api/v1/pg-management`
- `/api/v1/roles`
- `/api/v1/settings`
- `/api/v1/states`
- `/api/v1/subscriptions`
- `/api/v1/tenant`
- `/api/v1/units`
- `/api/v1/users`
- `/api/v1/years`

## Validation Rules

Validation lives in module FormRequest classes. Examples:

- `StorePgManagementRequest`: PG name, owner, mobile, block/room counts,
  location, pincode, address, status.
- `StoreTenantRequest`: identity, unique email/mobile, PG, room, bed, dates,
  rent/deposit, proof, emergency contact, status.
- `StorePaymentRequest`: tenant, PG, room, payment date, amount, method,
  reference, remarks.
- `StoreComplaintRequest`: PG, room, service category, service, date, note.

## Lookup JSON Contract

Shared lookup endpoints return arrays shaped as:

```json
[
  {
    "value": 1,
    "label": "Display Name"
  }
]
```

Supported query parameters include `q` and `limit`; `limit` is capped at 50.
