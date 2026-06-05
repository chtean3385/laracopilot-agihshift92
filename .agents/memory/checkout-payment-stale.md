---
name: Checkout payment stale eager-loaded collection
description: When you create a Payment record mid-request, the already-loaded $booking->payments collection doesn't include it; track the amount separately.
---

## Rule
In `CheckOutController::process()`, the booking is loaded with `with(['payments', ...])` at the top. A new final payment is created shortly after. Later, `$totalPaid = $booking->payments->where('status','completed')->sum('amount')` uses the **stale eager-loaded collection** — the newly created payment is not there, so `$isPaid` is always false and the invoice is generated with the full amount as outstanding.

## Fix
Track the created payment amount in a variable and add it to the sum:

```php
$finalPaymentAmount = 0;
if ($request->final_payment > 0) {
    Payment::create([...]);
    $finalPaymentAmount = (float) $request->final_payment;
}
// later:
$totalPaid = $booking->payments->where('status', 'completed')->sum('amount') + $finalPaymentAmount;
```

No extra DB query needed.

**Why:** Booking with cash payment at checkout always showed full outstanding on invoice.

**How to apply:** Whenever you create a related record inside a request where the parent is already eager-loaded, either reload the relation or track the new value separately.
