# Booking System Overbooking – Critical Section Report

## 1. Centrum Air Overbooking Case (Context)
Centrum Air sells seats on flights. Under high demand, multiple customers attempt to book the last seats simultaneously via web/mobile. If the backend doesn’t properly synchronize access to the shared resource (remaining seats), two or more requests can see availability at the same time and confirm, resulting in overbooking (selling more seats than exist). This causes denied boarding, compensation costs, and reputational damage.

## 2. Critical Section Concept
A critical section is a block of code that accesses a shared resource and must be executed atomically. Here:
- Shared resource: the `flights` row (`seat_count`, `booked_count`) and the `bookings` records.
- Critical section: the check ("is there a seat left?") and the update ("reserve it").
Without coordination, concurrent requests can interleave and break invariants.

## 3. Read/Write Conflict
Typical interleaving:
1. Request A reads `booked_count = 4`, `seat_count = 5` → looks available.
2. Request B reads the same values before A saves.
3. Both increment and save → overbooking may reach 6.
This is a Read-Then-Write conflict: decisions are made on stale reads and then conflicting writes are performed.

## 4. Producer–Consumer Analogy
- Producer: airline provides a fixed number of seats (capacity).
- Consumers: booking requests consume seats.
Without coordination, consumers can over-consume beyond production. Correct solutions ensure each unit (seat) is consumed at most once via locking or queuing.

## 5. Demonstration Code Summary
- `POST /api/unsafe-book`: no lock; check-then-increment can race.
- `POST /api/safe-book`: transaction + `lockForUpdate()`; PostgreSQL takes an exclusive row-level lock on the selected `flights` row (`SELECT ... FOR UPDATE`). Only one request can update at a time.
- `GET /api/status`: shows counts.
- `php artisan simulate:overbooking`: fires parallel requests to show the difference.

## 6. Why the Solution Works (PostgreSQL)
Using PostgreSQL transactions with row-level locks:
- `SELECT ... FOR UPDATE` (via Eloquent `lockForUpdate()`) locks the row until commit/rollback.
- Other transactions attempting the same lock will block, preventing concurrent modifications.
- The availability check and increment happen atomically, so overbooking is prevented under concurrency.

## 7. Alternatives
- Redis distributed lock: `Cache::lock()` for multi-instance coordination.
- Optimistic locking: version column + retry on conflict.
- Queue-based serialization: funnel bookings for a flight through a single worker.
- Higher isolation levels: e.g., Serializable; may still need explicit locks depending on pattern.

## 8. Environment Notes (PostgreSQL)
- Enable PHP `pdo_pgsql` extension.
- `.env` example:
```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=laravel_booking
DB_USERNAME=laravel
DB_PASSWORD=secret
```
- Run: `php artisan migrate:fresh --seed` then `php artisan serve`.

## 9. Reproduction Steps
1. Seed: `php artisan migrate:fresh --seed` (creates flight CA100 with 5 seats).
2. Start server: `php artisan serve`.
3. Run: `php artisan simulate:overbooking --endpoint=unsafe --attempts=20` → may exceed 5.
4. Run: `php artisan simulate:overbooking --endpoint=safe --attempts=20` → never exceeds 5.

## 10. Submission Notes
Submit the folder with `/backend/` (source), `/html/` (optional), `README.md`, and export this report as `report.pdf` or `report.docx`.
