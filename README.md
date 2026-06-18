# Payment System REST API

Laravel REST API for importing customer payments, tracking payment status,
sending payment reminders, and producing communication reports.

## Requirements

- PHP 8.4.1 or newer for the currently locked dependencies
- Composer
- MySQL

> The current `composer.lock` contains Symfony packages requiring PHP 8.4.1.
> Upgrade the XAMPP PHP executable or run Artisan with another PHP 8.4.1+
> installation.

## Setup

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Configure the MySQL values in `.env`, then run:

```bash
php artisan migrate --seed
php artisan serve
```

Seeded accounts:

| Role | Email | Password |
|---|---|---|
| Admin | `admin@example.com` | `password` |
| User | `user@example.com` | `password` |

Use the token returned by `POST /api/login` as:

```text
Authorization: Bearer YOUR_TOKEN
Accept: application/json
```

## Endpoints

| Method | Endpoint | Access |
|---|---|---|
| POST | `/api/login` | Public |
| POST | `/api/logout` | Authenticated |
| POST | `/api/admin/upload-csv` | Admin |
| GET | `/api/customers?search=&per_page=` | Admin/User |
| PUT | `/api/customer/{id}/payment-status` | Admin/User |
| POST | `/api/customer/{id}/send-notification` | Admin/User |
| GET | `/api/reports/summary` | Admin/User |

The CSV must contain `Name`, `Phone Number`, `Email`, and `Payment Amount`.
Header matching is case-insensitive and accepts spaces, underscores, or hyphens.
Duplicate emails already in the database or repeated in the same file are
counted and skipped. Invalid rows are returned with line-level errors.

Email uses Laravel's configured mail driver. WhatsApp sends a JSON payload to
`WHATSAPP_WEBHOOK_URL` when configured. Without a webhook it uses the Laravel
log driver, allowing local assessment without third-party credentials.

## Testing

```bash
php artisan test
```

Import `postman/Payment System API.postman_collection.json` into Postman.
Run **Admin Login** or **User Login** first; the collection automatically saves
the returned bearer token.
