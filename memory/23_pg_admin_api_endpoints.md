# PG Admin API Endpoints

This document defines the API blueprint for a PG admin application that mirrors
the existing `pg_management` web workflows. The app is admin-only and should use
the same business rules, validation rules, route-key behavior, and module
ownership as the web application.

## API Standards

Base URL:

```text
/api/v1
```

Authentication:

```text
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

Standard success response:

```json
{
  "success": true,
  "message": "Operation completed successfully.",
  "data": {},
  "meta": {}
}
```

Standard error response:

```json
{
  "success": false,
  "message": "Validation failed.",
  "errors": {
    "field": ["Error message"]
  }
}
```

Common status codes:

| Code | Meaning |
| --- | --- |
| 200 | Success |
| 201 | Created |
| 204 | Deleted/no content |
| 400 | Bad request |
| 401 | Unauthenticated |
| 403 | Forbidden |
| 404 | Not found |
| 422 | Validation failed |
| 500 | Server error |

Pagination query:

| Param | Type | Required | Default | Notes |
| --- | --- | --- | --- | --- |
| page | integer | No | 1 | Current page |
| per_page | integer | No | 15 | Max 100 |
| q | string | No | null | Search text |
| sort_by | string | No | created_at | Whitelisted per endpoint |
| sort_dir | string | No | desc | `asc` or `desc` |

Paginated response meta:

```json
{
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 120,
    "last_page": 8
  }
}
```

## App Flow

```text
Login
  -> Dashboard
  -> PG Management
      -> Room Categories
      -> Rooms
      -> Tenants
          -> Payments
          -> Complaints
          -> Maintenance
  -> Services
  -> Reports/Dashboard Data
  -> Settings/Profile
```

Admin app must not create tenant-facing behavior unless explicitly separated
from admin APIs.

## Authentication

### Login

```http
POST /api/v1/auth/login
```

Request:

| Param | Type | Required | Notes |
| --- | --- | --- | --- |
| email | string | Yes | Admin/user email |
| password | string | Yes | Password |
| device_name | string | Yes | Mobile/web client name |

Response:

```json
{
  "success": true,
  "message": "Login successful.",
  "data": {
    "token": "plain-text-token",
    "token_type": "Bearer",
    "user": {
      "id": 1,
      "public_id": "01J...",
      "name": "Admin User",
      "email": "admin@example.com",
      "roles": ["Super_Admin"],
      "permissions": ["pg.view", "tenant.create"]
    }
  }
}
```

### Logout

```http
POST /api/v1/auth/logout
```

Response:

```json
{
  "success": true,
  "message": "Logged out successfully.",
  "data": null
}
```

### Me

```http
GET /api/v1/auth/me
```

Response includes current user, role list, permission list, profile, active year,
and theme/layout settings.

## Dashboard

### Dashboard Summary

```http
GET /api/v1/dashboard/summary
```

Query params:

| Param | Type | Required | Notes |
| --- | --- | --- | --- |
| year_id | integer | No | Filter by selected year |
| from_date | date | No | `YYYY-MM-DD` |
| to_date | date | No | `YYYY-MM-DD` |

Response:

```json
{
  "success": true,
  "data": {
    "total_pgs": 8,
    "total_rooms": 120,
    "occupied_beds": 95,
    "available_beds": 25,
    "active_tenants": 95,
    "pending_payments": 12,
    "verified_payments": 80,
    "open_complaints": 5
  }
}
```

### Dashboard Charts

```http
GET /api/v1/dashboard/charts
```

Query params:

| Param | Type | Required | Notes |
| --- | --- | --- | --- |
| type | string | Yes | `payments`, `occupancy`, `complaints` |
| from_date | date | No | Start date |
| to_date | date | No | End date |
| pg_id | integer | No | PG filter |

Response:

```json
{
  "success": true,
  "data": {
    "labels": ["Jan", "Feb", "Mar"],
    "datasets": [
      {
        "label": "Payments",
        "data": [25000, 31000, 28000]
      }
    ]
  }
}
```

## Lookups

Lookup endpoints return compact lists for dropdowns and cascades.

Standard response:

```json
{
  "success": true,
  "data": [
    {
      "value": 1,
      "label": "Display Name"
    }
  ]
}
```

| Method | Endpoint | Params | Purpose |
| --- | --- | --- | --- |
| GET | `/api/v1/lookups/countries` | `q`, `limit` | Country dropdown |
| GET | `/api/v1/lookups/states` | `country_id`, `q`, `limit` | State dropdown |
| GET | `/api/v1/lookups/cities` | `state_id`, `country_id`, `q`, `limit` | City dropdown |
| GET | `/api/v1/lookups/pgs` | `q`, `status`, `limit` | PG dropdown |
| GET | `/api/v1/lookups/room-categories` | `pg_id`, `q`, `limit` | Room category dropdown |
| GET | `/api/v1/lookups/rooms` | `pg_id`, `category_id`, `status`, `q`, `limit` | Room dropdown |
| GET | `/api/v1/lookups/tenants` | `pg_id`, `room_id`, `status`, `q`, `limit` | Tenant dropdown |
| GET | `/api/v1/lookups/service-categories` | `q`, `status`, `limit` | Service category dropdown |
| GET | `/api/v1/lookups/services` | `service_category_id`, `q`, `status`, `limit` | Service dropdown |
| GET | `/api/v1/lookups/users` | `q`, `role`, `status`, `limit` | Active user dropdown |
| GET | `/api/v1/lookups/years` | `q`, `limit` | Year dropdown |

## PG Management

### List PGs

```http
GET /api/v1/pgs
```

Query params:

| Param | Type | Required | Notes |
| --- | --- | --- | --- |
| q | string | No | Search PG name/mobile/address |
| status | string | No | `active`, `inactive` |
| owner_id | integer | No | Owner filter |
| country_id | integer | No | Country filter |
| state_id | integer | No | State filter |
| city_id | integer | No | City filter |
| page | integer | No | Pagination |
| per_page | integer | No | Pagination |

Response item:

```json
{
  "id": 1,
  "public_id": "01J...",
  "pg_name": "Sunrise PG",
  "owner": {
    "id": 4,
    "name": "Owner Name"
  },
  "mobile_no": "9876543210",
  "total_block": 2,
  "total_room": 30,
  "location": {
    "country_id": 1,
    "state_id": 10,
    "city_id": 100,
    "pincode": "380001",
    "address": "Address text"
  },
  "status": "active"
}
```

### Create PG

```http
POST /api/v1/pgs
```

Request:

| Param | Type | Required | Notes |
| --- | --- | --- | --- |
| pg_name | string | Yes | Unique among non-deleted PGs |
| owner_id | integer | Yes | Existing user id |
| mobile_no | string | Yes | Max 20 |
| total_block | integer | Yes | Min 0 |
| total_room | integer | Yes | Min 0 |
| country_id | integer | Yes | Existing country |
| state_id | integer | Yes | Existing state |
| city_id | integer | Yes | Existing city |
| pincode | string | Yes | Max 10 |
| address | string | Yes | Full address |
| status | string | Yes | `active`, `inactive` |

Response: `201`, created PG object.

### PG Detail

```http
GET /api/v1/pgs/{public_id}
```

Response includes PG object, room counts, tenant counts, pending payment count,
and open complaint count.

### Update PG

```http
PUT /api/v1/pgs/{public_id}
```

Request: same fields as create. Response: updated PG object.

### Delete PG

```http
DELETE /api/v1/pgs/{public_id}
```

Response: success message. Delete should be soft delete.

## Room Categories

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/api/v1/room-categories` | List room categories |
| POST | `/api/v1/room-categories` | Create room category |
| GET | `/api/v1/room-categories/{public_id}` | Detail |
| PUT | `/api/v1/room-categories/{public_id}` | Update |
| DELETE | `/api/v1/room-categories/{public_id}` | Soft delete |

Create/update request:

| Param | Type | Required | Notes |
| --- | --- | --- | --- |
| pg_id | integer | Yes | Existing PG |
| category_name | string | Yes | Room category name |
| status | string | Yes | `active`, `inactive` |

Response item:

```json
{
  "id": 1,
  "public_id": "01J...",
  "pg_id": 1,
  "pg_name": "Sunrise PG",
  "category_name": "Deluxe",
  "status": "active"
}
```

## Rooms

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/api/v1/rooms` | List rooms |
| POST | `/api/v1/rooms` | Create room |
| GET | `/api/v1/rooms/{public_id}` | Detail |
| PUT | `/api/v1/rooms/{public_id}` | Update |
| DELETE | `/api/v1/rooms/{public_id}` | Soft delete |

List filters:

| Param | Type | Required | Notes |
| --- | --- | --- | --- |
| pg_id | integer | No | PG filter |
| category_id | integer | No | Room category filter |
| status | string | No | Status filter |
| q | string | No | Room number search |

Create/update request:

| Param | Type | Required | Notes |
| --- | --- | --- | --- |
| pg_id | integer | Yes | Existing PG |
| category_id | integer | Yes | Existing room category |
| room_no | string | Yes | Room number/name |
| bed_capacity | integer | No | Min 0 |
| rent_amount | decimal | No | Min 0 |
| status | string | Yes | `active`, `inactive` |

Response item:

```json
{
  "id": 1,
  "public_id": "01J...",
  "pg": {
    "id": 1,
    "name": "Sunrise PG"
  },
  "category": {
    "id": 2,
    "name": "Deluxe"
  },
  "room_no": "A-101",
  "bed_capacity": 4,
  "occupied_beds": 2,
  "available_beds": 2,
  "rent_amount": "6500.00",
  "status": "active"
}
```

## Tenants

### List Tenants

```http
GET /api/v1/tenants
```

Filters:

| Param | Type | Required | Notes |
| --- | --- | --- | --- |
| pg_id | integer | No | PG filter |
| room_id | integer | No | Room filter |
| status | string | No | `Active`, `Inactive` |
| q | string | No | Name, email, mobile search |
| checkin_from | date | No | `YYYY-MM-DD` |
| checkin_to | date | No | `YYYY-MM-DD` |

Response item:

```json
{
  "id": 1,
  "public_id": "01J...",
  "name": "Tenant Name",
  "email": "tenant@example.com",
  "phone": "9876543210",
  "pg": {
    "id": 1,
    "name": "Sunrise PG"
  },
  "room": {
    "id": 5,
    "room_no": "A-101"
  },
  "bed_no": "B1",
  "monthly_rent": "6500.00",
  "security_deposit": "10000.00",
  "checkin_date": "2026-06-01",
  "status": "Active"
}
```

### Create Tenant

```http
POST /api/v1/tenants
```

Request:

| Param | Type | Required | Notes |
| --- | --- | --- | --- |
| name_prefix | string | No | Max 10 |
| firstname | string | Yes | Tenant first name |
| lastname | string | No | Tenant last name |
| email | string | Yes | Unique in users |
| mobile | string | Yes | Unique in users |
| date_of_birth | date | No | `YYYY-MM-DD` for API |
| gender | string | No | `Male`, `Female`, `Other` |
| occupation | string | No | Max 100 |
| pg_id | integer | Yes | Existing PG |
| room_id | integer | Yes | Existing room |
| bed_no | string | Yes | Max 20 |
| checkin_date | date | No | `YYYY-MM-DD` |
| expected_checkout_date | date | No | Must be after/equal checkin |
| monthly_rent | decimal | No | Min 0 |
| security_deposit | decimal | Yes | Min 0 |
| payment_method | string | No | Payment method |
| id_proof_type | string | Yes | ID proof type |
| id_proof_number | string | Yes | ID proof number |
| id_proof_file | file/base64 | Yes | JPG, PNG, PDF, max 2 MB |
| emergency_contact_name | string | Yes | Emergency contact |
| emergency_relation | string | Yes | Relation |
| emergency_contact_number | string | Yes | Phone |
| permanent_state_id | integer | No | Existing state |
| permanent_city_id | integer | No | Existing city |
| permanent_address | string | No | Address |
| additional_notes | string | No | Notes |
| status | string | Yes | `Active`, `Inactive` |

Response: `201`, tenant object with linked user id.

### Tenant Detail

```http
GET /api/v1/tenants/{public_id}
```

Response includes tenant, linked user, PG, room, payments summary, recent
payments, complaints summary, and uploaded proof URL.

### Update Tenant

```http
PUT /api/v1/tenants/{public_id}
```

Request: same as create, except proof file can be optional when existing file is
kept.

### Delete Tenant

```http
DELETE /api/v1/tenants/{public_id}
```

## Payments

### List Payments

```http
GET /api/v1/payments
```

Filters:

| Param | Type | Required | Notes |
| --- | --- | --- | --- |
| tenant_id | integer | No | Tenant filter |
| pg_id | integer | No | PG filter |
| room_id | integer | No | Room filter |
| verified | string | No | `pending`, `verified` |
| payment_method | string | No | Cash/bank/etc |
| from_date | date | No | Payment date start |
| to_date | date | No | Payment date end |

Response item:

```json
{
  "id": 1,
  "public_id": "01J...",
  "tenant": {
    "id": 1,
    "name": "Tenant Name"
  },
  "pg": {
    "id": 1,
    "name": "Sunrise PG"
  },
  "room": {
    "id": 5,
    "room_no": "A-101"
  },
  "payment_date": "2026-06-10",
  "amount": "6500.00",
  "payment_method": "UPI",
  "reference_no": "UPI123",
  "verified": "pending",
  "remarks": "June rent"
}
```

### Create Payment

```http
POST /api/v1/payments
```

Request:

| Param | Type | Required | Notes |
| --- | --- | --- | --- |
| tenant_id | integer | Yes | Existing tenant |
| pg_id | integer | Yes | Existing PG |
| room_id | integer | Yes | Existing room |
| payment_date | date | Yes | `YYYY-MM-DD` |
| amount | decimal | Yes | Min 0 |
| payment_method | string | Yes | `Cash`, `Bank Transfer`, `Cheque`, `UPI`, `Other` |
| reference_no | string | No | Max 100 |
| remarks | string | No | Notes |

Response: `201`, payment object.

### Verify Or Unverify Payment

```http
POST /api/v1/payments/{public_id}/verification
```

Request:

| Param | Type | Required | Notes |
| --- | --- | --- | --- |
| verified | string | Yes | `pending` or `verified` |

Response:

```json
{
  "success": true,
  "message": "Payment marked as verified.",
  "data": {
    "public_id": "01J...",
    "verified": "verified"
  }
}
```

Business rule:

- Current web behavior allows toggling both ways.
- If admin app requires approval lock, block updates/deletes when
  `verified = verified` unless user has override permission.

## Complaints

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/api/v1/complaints` | List complaints |
| POST | `/api/v1/complaints` | Create complaint |
| GET | `/api/v1/complaints/{public_id}` | Detail |
| PUT | `/api/v1/complaints/{public_id}` | Update |
| DELETE | `/api/v1/complaints/{public_id}` | Soft delete |
| GET | `/api/v1/complaints/next-number` | Get next complaint number |

Filters:

| Param | Type | Required | Notes |
| --- | --- | --- | --- |
| pg_id | integer | No | PG filter |
| room_id | integer | No | Room filter |
| service_category_id | integer | No | Category filter |
| service_id | integer | No | Service filter |
| status | string | No | Complaint status |
| from_date | date | No | Complaint date start |
| to_date | date | No | Complaint date end |

Create/update request:

| Param | Type | Required | Notes |
| --- | --- | --- | --- |
| pg_id | integer | Yes | Existing PG |
| room_id | integer | Yes | Existing room |
| service_category_id | integer | Yes | Existing category |
| service_id | integer | Yes | Existing service |
| complaint_date | date | Yes | `YYYY-MM-DD` |
| note | string | Yes | Complaint detail |
| status | string | No | Default `pending` |

Response item:

```json
{
  "id": 1,
  "public_id": "01J...",
  "complaint_no": "CMP-0001",
  "pg_name": "Sunrise PG",
  "room_no": "A-101",
  "service_category": "Electrical",
  "service": "Fan Repair",
  "complaint_date": "2026-06-15",
  "note": "Fan not working",
  "status": "pending"
}
```

## Maintenance

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/api/v1/maintenance` | List maintenance records |
| POST | `/api/v1/maintenance` | Create maintenance |
| GET | `/api/v1/maintenance/{public_id}` | Detail |
| PUT | `/api/v1/maintenance/{public_id}` | Update |
| DELETE | `/api/v1/maintenance/{public_id}` | Soft delete |

Recommended request fields:

| Param | Type | Required | Notes |
| --- | --- | --- | --- |
| pg_id | integer | Yes | Existing PG |
| room_id | integer | No | Existing room if room-specific |
| title | string | Yes | Maintenance title |
| description | string | No | Detail |
| maintenance_date | date | Yes | `YYYY-MM-DD` |
| amount | decimal | No | Cost |
| status | string | Yes | `pending`, `in_progress`, `completed`, `cancelled` |

Note: exact field names must match the Maintenance module migration/request
before implementation.

## Services

### Service Categories

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/api/v1/service-categories` | List categories |
| POST | `/api/v1/service-categories` | Create category |
| GET | `/api/v1/service-categories/{public_id}` | Detail |
| PUT | `/api/v1/service-categories/{public_id}` | Update |
| DELETE | `/api/v1/service-categories/{public_id}` | Soft delete |

Request:

| Param | Type | Required | Notes |
| --- | --- | --- | --- |
| name | string | Yes | Category name |
| status | string | Yes | `active`, `inactive` |

### Services

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/api/v1/services` | List services |
| POST | `/api/v1/services` | Create service |
| GET | `/api/v1/services/{public_id}` | Detail |
| PUT | `/api/v1/services/{public_id}` | Update |
| DELETE | `/api/v1/services/{public_id}` | Soft delete |

Request:

| Param | Type | Required | Notes |
| --- | --- | --- | --- |
| service_category_id | integer | Yes | Existing service category |
| name | string | Yes | Service name |
| status | string | Yes | `active`, `inactive` |

## Master Data

Use these endpoints for admin configuration screens.

| Resource | List | Create | Detail | Update | Delete |
| --- | --- | --- | --- | --- | --- |
| Countries | `GET /countries` | `POST /countries` | `GET /countries/{public_id}` | `PUT /countries/{public_id}` | `DELETE /countries/{public_id}` |
| States | `GET /states` | `POST /states` | `GET /states/{public_id}` | `PUT /states/{public_id}` | `DELETE /states/{public_id}` |
| Cities | `GET /cities` | `POST /cities` | `GET /cities/{public_id}` | `PUT /cities/{public_id}` | `DELETE /cities/{public_id}` |
| Units | `GET /units` | `POST /units` | `GET /units/{public_id}` | `PUT /units/{public_id}` | `DELETE /units/{public_id}` |
| Currencies | `GET /currencies` | `POST /currencies` | `GET /currencies/{public_id}` | `PUT /currencies/{public_id}` | `DELETE /currencies/{public_id}` |
| Years | `GET /years` | `POST /years` | `GET /years/{public_id}` | `PUT /years/{public_id}` | `DELETE /years/{public_id}` |

All paths above are under:

```text
/api/v1
```

## Users And Roles

### Users

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/api/v1/users` | List users |
| POST | `/api/v1/users` | Create user |
| GET | `/api/v1/users/{public_id}` | User detail |
| PUT | `/api/v1/users/{public_id}` | Update user |
| DELETE | `/api/v1/users/{public_id}` | Delete user |
| POST | `/api/v1/users/{public_id}/status` | Activate/deactivate |
| POST | `/api/v1/users/{public_id}/login-status` | Block/unblock login |

### Roles

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/api/v1/roles` | List roles |
| POST | `/api/v1/roles` | Create role |
| GET | `/api/v1/roles/{public_id}` | Role detail |
| PUT | `/api/v1/roles/{public_id}` | Update role |
| DELETE | `/api/v1/roles/{public_id}` | Delete role |
| GET | `/api/v1/permissions` | List permissions |
| POST | `/api/v1/roles/{public_id}/permissions` | Sync role permissions |

## Settings And Profile

| Method | Endpoint | Purpose |
| --- | --- | --- |
| GET | `/api/v1/settings` | Get app/company settings |
| PUT | `/api/v1/settings/{public_id}` | Update settings |
| POST | `/api/v1/settings/logo` | Upload logo |
| GET | `/api/v1/profile` | Current admin profile |
| PUT | `/api/v1/profile` | Update profile |
| POST | `/api/v1/profile/avatar` | Upload avatar |
| POST | `/api/v1/profile/change-password` | Change password |
| POST | `/api/v1/profile/theme` | Change theme/layout |
| POST | `/api/v1/profile/logout-everywhere` | Revoke other sessions |

## Reports

These are recommended admin app report APIs, derived from dashboard and module
data.

| Method | Endpoint | Params | Response |
| --- | --- | --- | --- |
| GET | `/api/v1/reports/occupancy` | `pg_id`, `from_date`, `to_date` | PG/room occupancy totals |
| GET | `/api/v1/reports/payments` | `pg_id`, `tenant_id`, `verified`, `from_date`, `to_date` | Payment collection rows/totals |
| GET | `/api/v1/reports/tenant-ledger/{tenant_public_id}` | `from_date`, `to_date` | Tenant payment ledger |
| GET | `/api/v1/reports/complaints` | `pg_id`, `status`, `from_date`, `to_date` | Complaint summary |
| GET | `/api/v1/reports/maintenance` | `pg_id`, `status`, `from_date`, `to_date` | Maintenance summary |

Report response example:

```json
{
  "success": true,
  "data": {
    "totals": {
      "amount": "125000.00",
      "count": 42
    },
    "rows": []
  }
}
```

## Audit Fields

Every create/update/delete should preserve:

- `created_by`
- `updated_by`
- `deleted_by`
- activity log row
- IP address
- user agent
- device/client name

## Implementation Order

1. Auth and current user.
2. Lookups.
3. Dashboard summary.
4. PG Management.
5. Room categories and rooms.
6. Tenants.
7. Payments and verification.
8. Complaints.
9. Maintenance.
10. Services and master data.
11. Users, roles, permissions.
12. Settings/profile.
13. Reports.

## Compatibility Notes

- Use `public_id` in URLs for models that use `HasPublicId`.
- Keep internal numeric ids in request bodies for selected relationships unless
  the API contract is changed to use public ids for all foreign keys.
- Match existing FormRequest validation rules before implementation.
- Keep status values consistent with current modules unless a migration normalizes
  them.
- Do not expose tenant-only behavior in this admin API namespace.
