# Store Inventory Management API

A RESTful API built with **Laravel 10** for managing products, customers, inventory, and customer orders.

The application supports product stock tracking, order creation with tax calculations, customer order history, low-stock reporting, and a queued order-confirmation simulation.

## Features

- Product and customer data managed through Laravel models and migrations.
- Factories and seeders for sample products, customers, and orders.
- Order creation with one or more product items.
- Server-side calculation of subtotals, tax amounts, and grand totals.
- Stock validation and deduction when an order is placed.
- Database transaction and product row locking during order creation.
- Customer order history lookup by email.
- Low-stock product listing with a configurable threshold.
- Queued order-confirmation job simulated through application logging.
- Automated API tests for order creation and insufficient stock.

## Tech stack

- PHP
- Laravel 10
- Composer
- SQLite for local development (or MySQL)
- Laravel database queue

## Requirements

- PHP and the extensions required by your Laravel dependencies
- Composer
- SQLite PHP extension when using SQLite, or the relevant MySQL PHP extension when using MySQL
- A database
- Git (optional)

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/YOUR_USERNAME/store-inventory-management.git
cd store-inventory-management
```

Replace `YOUR_USERNAME` with your GitHub username.

### 2. Install dependencies

```bash
composer install
```

### 3. Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configure the database

**SQLite**

Create the database file:

```bash
touch database/database.sqlite
```

Configure `.env`:

```dotenv
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/store-inventory-management/database/database.sqlite
```

Replace the example path with the actual absolute path on your machine.

**MySQL**

Alternatively, create a MySQL database and update the `DB_*` values in `.env` with your database name, username, password, host, and port.

### 5. Run migrations and seeders

```bash
php artisan migrate --seed
```

The seeders populate sample products, customers, and orders. Check the seeder classes for the exact sample record counts.

### 6. Configure the queue

Set this in `.env`:

```dotenv
QUEUE_CONNECTION=database
```

If a jobs-table migration does not already exist, create it:

```bash
php artisan queue:table
php artisan migrate
```

Do not generate a duplicate jobs-table migration if one is already present.

### 7. Start the application

In one terminal:

```bash
php artisan serve
```

The application is usually available at `http://127.0.0.1:8000`.

In a second terminal, run the queue worker:

```bash
php artisan queue:work
```

The confirmation job writes a log entry. It does not send a real email unless a mail integration is added.

## API endpoints

All endpoints use Laravel's `/api` prefix.

| Method | Endpoint | Purpose |
|---|---|---|
| `POST` | `/api/orders` | Create an order |
| `GET` | `/api/customers/orders?email=...` | Retrieve a customer's order history |
| `GET` | `/api/products/low-stock` | List products below the stock threshold |

### 1. Create an order

**Request**

```http
POST /api/orders
Accept: application/json
Content-Type: application/json
```

Example JSON body:

```json
{
  "customer": {
    "name": "John Doe",
    "email": "john@example.com"
  },
  "items": [
    {
      "product_id": 1,
      "quantity": 2
    },
    {
      "product_id": 3,
      "quantity": 1
    }
  ]
}
```

The API validates the customer details, item list, product IDs, quantities, and available stock. Product prices and tax percentages are read from the database, not trusted from the request.

On success, the API creates the order and its order items, calculates the totals, deducts stock, and dispatches the confirmation job after the order transaction completes.

Typical success status: `201 Created`.

### 2. Get customer order history

```http
GET /api/customers/orders?email=john@example.com
Accept: application/json
```

Returns the customer and their orders, including order items and related products. If no customer exists for the supplied email, the endpoint returns `404 Not Found`.

**Security note:** Email-only lookup is suitable for an assignment/demo, not for protecting real customer data. A production API should use authentication and authorization.

### 3. Get low-stock products

```http
GET /api/products/low-stock
Accept: application/json
```

An optional threshold can be supplied:

```http
GET /api/products/low-stock?threshold=10
```

If omitted, the endpoint uses `LOW_STOCK_THRESHOLD` from `.env` (default configured in the application).

```dotenv
LOW_STOCK_THRESHOLD=5
```

The endpoint returns products whose stock is **less than** the threshold.

## Running automated tests

Run all tests:

```bash
php artisan test
```

Run the order API tests only:

```bash
php artisan test --filter=OrderApiTest
```

Review the test files for the exact scenarios and assertions implemented in this repository.

### Concurrency note

The order service uses a database transaction and `lockForUpdate()` when retrieving products. This is intended to protect stock updates from concurrent requests on databases that support row-level locking, such as MySQL with InnoDB.

SQLite does not provide equivalent row-level `SELECT ... FOR UPDATE` behavior. A passing SQLite test run is therefore **not proof** that concurrent requests for the last unit are safe. Validate that scenario using MySQL/InnoDB with genuinely concurrent requests or processes.

## Configuration

| Setting | Purpose |
|---|---|
| `DB_CONNECTION` | Database driver |
| `DB_DATABASE` | Database name or SQLite database path |
| `QUEUE_CONNECTION` | Queue driver; use `database` for the included job workflow |
| `LOW_STOCK_THRESHOLD` | Default low-stock threshold |

After changing configuration, clear cached settings if necessary:

```bash
php artisan optimize:clear
```

## Relevant project structure

```text
app/
  Http/
    Controllers/Api/     # API controllers
    Requests/            # Request validation
  Jobs/                  # Queued jobs
  Models/                # Eloquent models
  Services/              # Business logic (if present)
config/                  # Application configuration
database/
  factories/             # Model factories
  migrations/            # Database schema
  seeders/               # Sample data
routes/
  api.php                # API routes
tests/
  Feature/               # Feature/API tests
```

The exact structure may vary slightly depending on the final implementation.

## Assumptions and design notes

- The client submits product IDs and quantities. The server calculates monetary values using product data stored in the database.
- Order items retain the applied unit price and tax percentage so historical order values remain available if product prices change later.
- A customer's email is used to find or create the customer during order creation.
- Stock is deducted only when the order is successfully created.
- The confirmation job simulates email delivery by logging order details; no real email is sent by default.
- This is an assignment/demo API. A production system would normally add authentication, authorization, pagination, rate limiting, customer identity verification, and operational monitoring.

## Queue verification

After placing an order while the queue worker is running, check:

```text
storage/logs/laravel.log
```

Look for a log entry similar to:

```text
Order confirmation email simulated.
```

## Author

**Riya Pradeep**

