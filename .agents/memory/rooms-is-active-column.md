---
name: Rooms table is_active column
description: The rooms table has both a status field ('inactive' value) AND an is_active boolean column. Both must be filtered in booking dropdowns.
---

## Rule
The `rooms` table has two overlapping concepts for "inactive" rooms:
- `status` field with value `'inactive'` — set via the rooms edit UI (Status dropdown)
- `is_active` boolean column — added via migration `2026_06_08_115745_add_is_active_to_rooms_table.php`

**Why this matters:** BookingController must filter BOTH to keep inactive rooms out of booking dropdowns:
```php
Room::where('status', '!=', 'maintenance')
    ->where('status', '!=', 'inactive')
    ->where('is_active', true)
```

**How to apply:** Any time a Room query is used for a user-facing dropdown or selection list, apply all three filters. The `is_active` column defaults to `true` for all rows created before the migration — meaning `status = 'inactive'` rooms won't be excluded by `is_active` alone without the explicit status filter.
