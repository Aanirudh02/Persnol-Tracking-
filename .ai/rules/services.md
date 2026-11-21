---
paths:
  - app/Services/DailyRegisterService.php
---

# Services

## Cash register: unlogged expenses and separate income
Expenses with `is_unlogged` (forgotten long ago) never enter the Daily Cash Register, so past balances stay as they are. They still count in expense totals and have their own Unlogged tab. Income with `keep_separate` is included in its own day's closing but is subtracted from `carry_forward`. The next day's opening uses `carry_forward`, not `closing`.
