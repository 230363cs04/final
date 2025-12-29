# Booking System Overbooking – Critical Section (Laravel)

## Folder Structure
- `/backend/` – Laravel backend demonstrating race condition vs synchronization
- `/html/` – optional simple UI
- `README.md` – this file
- `report.md` – explanation (export to PDF or Word as `report.pdf` or `report.docx`)

## Requirements
- PHP 8.2+, Composer
- PostgreSQL 13+ (row-level locking)
- Laravel 10/11
- PHP extension: `pdo_pgsql` enabled

## PostgreSQL setup (example)
- Create a database and user, e.g. using psql:
```sql
CREATE DATABASE postgres;
CREATE USER postgres WITH PASSWORD 'postgres';
GRANT ALL PRIVILEGES ON DATABASE postgres TO laravel;
```

## Setup
```bash
cd backend
copy .env.example .env   # Windows
php artisan key:generate
```

Edit `.env` and set PostgreSQL config:
```
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=postgres
```

Then run migrations and seeders:
```bash
php artisan migrate:fresh --seed
php artisan serve
```

Make sure the PHP `pdo_pgsql` extension is enabled (on Windows, enable it in `php.ini`).

## API Endpoints
- `POST /api/unsafe-book` body: `{ "flight": "CA100", "name": "Alice" }`
- `POST /api/safe-book` body: `{ "flight": "CA100", "name": "Alice" }`
- `GET /api/status?flight=CA100`

## Simulation
Open a second terminal while the server is running:
```bash
php artisan simulate:overbooking --endpoint=unsafe --attempts=20
php artisan simulate:overbooking --endpoint=safe --attempts=20
```

Expected:
- Unsafe: overbooking may occur (`booked_count > seat_count`)
- Safe: no overbooking (never exceeds `seat_count`)

## How it works
- Unsafe path reads `booked_count`, checks capacity, then increments without locking—two requests can pass the check concurrently.
- Safe path uses a DB transaction and `lockForUpdate()` to take a row-level lock on the `flights` row (supported by PostgreSQL), making the check+increment atomic.
"# final" 
