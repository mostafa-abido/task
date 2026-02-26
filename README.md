# Invoice Management API

Backend for managing invoices on rental contracts. You can create invoices from a contract, apply taxes (VAT + municipal fee), and record payments. Built with Laravel 10, PHP 8.1.

## Setup

You need PHP 8.1+, Composer, and a database (MySQL or SQLite).

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Set your DB credentials in `.env`, then run migrations:

```bash
php artisan migrate
```

## API

Routes live under `/api` and use Sanctum. Auth user must have a `tenant_id`.

- `POST /api/contracts/{contract}/invoices` – create invoice (body: `due_date`)
- `GET /api/contracts/{contract}/invoices` – list invoices. Query params: `status`, `from_date`, `to_date`, `per_page`
- `GET /api/contracts/{contract}/summary` – totals and outstanding for that contract
- `GET /api/invoices/{invoice}` – one invoice with contract and payments
- `POST /api/invoices/{invoice}/payments` – add payment (body: `amount`, `payment_method`, `reference_number` optional)

## How it’s structured

Request hits a Form Request (validation), then the controller builds a DTO, runs the policy check, calls the service. Service does the logic and uses repositories for DB. Response is shaped with API Resources. Tax is done with a Strategy (interface + VAT and municipal fee implementations, wired in the provider).

## Running tests

```bash
php artisan test tests/Feature/InvoiceApiTest.php
```

MIT
