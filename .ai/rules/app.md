---
paths:
  - 'app/**'
---

# App

## Find daily records with DailyRecord::forUserOnDate
`record_date` is cast to `date`, so it is stored as "Y-m-d 00:00:00". `DailyRecord::firstOrCreate(['record_date' => 'Y-m-d'])` can miss the existing row and fail on the (user_id, record_date) unique index. Use `DailyRecord::forUserOnDate($userId, $date)` instead.
