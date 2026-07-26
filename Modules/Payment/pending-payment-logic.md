# Pending Payment Logic

## Goal

Show tenants in the **Pending Payment** tab when their monthly rent is due but unpaid.

## Core Rule

A tenant appears in Pending Payment if they have **no payment record in the current billing cycle**.

The billing cycle for each tenant is based on their **check-in day of the month**.

## Formula

```sql
billing_cycle_start = DATE_ADD(checkin_date, INTERVAL TIMESTAMPDIFF(MONTH, checkin_date, CURDATE()) MONTH)
```

This calculates the most recent occurrence of the check-in day. For example, if check-in was 15 April, `billing_cycle_start` returns the 15th of the current (or previous) month.

The pending check:

```sql
checkin_date <= CURDATE() - INTERVAL 1 MONTH
AND NOT EXISTS (
    SELECT 1
    FROM payments
    WHERE payments.tenant_id = tenants.id
    AND payments.payment_date >= DATE_ADD(tenants.checkin_date, INTERVAL TIMESTAMPDIFF(MONTH, tenants.checkin_date, CURDATE()) MONTH)
)
```

Two conditions must be met:

1. At least **1 full month** has passed since check-in (first cycle has started)
2. No payment exists with `payment_date >= billing_cycle_start`

## Implementation

Located in `PaymentController::pendingPaymentsData()` (`app/Http/Controllers/PaymentController.php`).

```php
$billingRaw = 'DATE_ADD(tenants.checkin_date, INTERVAL TIMESTAMPDIFF(MONTH, tenants.checkin_date, CURDATE()) MONTH)';

$query = Tenant::with('pg:id,public_id,pg_name', 'room:id,public_id,room_no')
    ->select('id', 'public_id', 'name', 'pg_id', 'room_id', 'checkin_date', 'monthly_rent')
    ->whereNotNull('checkin_date')
    ->where('checkin_date', '<=', now()->subMonth())
    ->whereRaw("NOT EXISTS (
        SELECT 1 FROM payments
        WHERE payments.tenant_id = tenants.id
        AND payments.payment_date >= {$billingRaw}
    )");
```

Pg_Admin users are scoped to their own PG properties via `whereHas('pg', fn ($q) => $q->where('owner_id', $user->id))`.

## Examples

### Check-in on 15th

**Tenant:** Raj — **Check-in:** 15 Apr 2026 — **Rent:** ₹5,000

| Date | Event | Billing Start | Payment in Cycle? | In Pending Tab? |
|---|---|---|---|---|
| 15 Apr | Check-in | — | — | No |
| 15 May | Cycle 1 due | 15 May | None | **Yes** |
| 16 May | Paid ₹5,000 | 15 May | 16 May ≥ 15 May | No |
| 15 Jun | Cycle 2 due | 15 Jun | Last paid 16 May < 15 Jun | **Yes** |
| 15 Jul | Cycle 3 due | 15 Jul | Still unpaid | **Yes** |
| 17 Jul | Paid ₹5,000 | 15 Jul | 17 Jul ≥ 15 Jul | No |
| 15 Aug | Cycle 4 due | 15 Aug | Last paid 17 Jul < 15 Aug | **Yes** |

### Check-in on 25th

**Tenant:** Priya — **Check-in:** 25 Mar 2026 — **Rent:** ₹7,000

| Date | Event | Billing Start | Payment in Cycle? | In Pending Tab? |
|---|---|---|---|---|
| 25 Mar | Check-in | — | — | No |
| 25 Apr | Cycle 1 due | 25 Apr | None | **Yes** |
| 28 Apr | Paid ₹7,000 | 25 Apr | 28 Apr ≥ 25 Apr | No |
| 25 May | Cycle 2 due | 25 May | Last paid 28 Apr < 25 May | **Yes** |
| 30 May | Paid ₹7,000 | 25 May | 30 May ≥ 25 May | No |
| 25 Jun | Cycle 3 due | 25 Jun | None | **Yes** |

### Check-in on 1st

**Tenant:** Amit — **Check-in:** 1 Jan 2026 — **Rent:** ₹6,000

| Date | Event | Billing Start | Payment in Cycle? | In Pending Tab? |
|---|---|---|---|---|
| 1 Jan | Check-in | — | — | No |
| 1 Feb | Cycle 1 due | 1 Feb | None | **Yes** |
| 5 Feb | Paid ₹6,000 | 1 Feb | 5 Feb ≥ 1 Feb | No |
| 1 Mar | Cycle 2 due | 1 Mar | Last paid 5 Feb < 1 Mar | **Yes** |
| 1 Apr | Cycle 3 due | 1 Apr | Still unpaid | **Yes** |
| 3 Apr | Paid ₹6,000 | 1 Apr | 3 Apr ≥ 1 Apr | No |

## Key Points

- **Billing cycle** starts on the **check-in day** of each month (15th, 25th, 1st, etc.)
- Tenant **enters** Pending Payment on the billing cycle start day if no payment exists in that cycle
- Tenant **leaves** Pending Payment immediately when a payment is recorded with `payment_date >= billing_cycle_start`
- Tenant **re-enters** on the next billing cycle start day if no new payment exists
- Payments are not tagged to specific months — the system infers coverage from `payment_date` relative to `billing_cycle_start`
- The `where('checkin_date', '<=', now()->subMonth())` ensures tenants are excluded until their first full month completes

## Days Elapsed Calculation

**Never paid:** Days since the **current billing cycle start**:
```
$checkin->addMonths($checkin->diffInMonths(now()))->diffInDays(now())
```

**Has paid before:** Days since the **billing cycle start after the last payment** (i.e., when the covered cycle ended):
```
$checkin->addMonths($checkin->diffInMonths($lastPayment) + 1)->diffInDays(now())
```

This ensures overdue starts counting **after the last payment's covered cycle completes**, not from the payment date itself.

### Example

Check-in **15 Apr**, last payment **16 May** (covered the May 15 cycle → ended 14 Jun):
- Cycle after last payment = 15 Apr + (0 + 1) months → **15 Jun** ← overdue starts here
- If today is **26 Jul**: overdue = 26 Jul − 15 Jun = **41 days**
