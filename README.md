# Ticket Reservation System

A Laravel-based API for event ticket reservations with seat selection, temporary holds, and third-party payment integration.

## Features

- **User Authentication** — Token-based auth via Laravel Sanctum
- **Event & Seat Browsing** — List events, view seats with availability status
- **Seat Reservations** — Hold seats for 30 minutes with concurrency protection
- **Payment Integration** — Abstract gateway interface supporting multiple providers
- **Automatic Expiry** — Stale reservations released via scheduled command
- **Webhook Handling** — Secure payment confirmation via provider webhooks

## Requirements

- PHP 8.2+
- Composer
- SQLite (default) or MySQL/PostgreSQL
- Node.js & NPM (for frontend assets, optional)

## Installation

```bash
# Clone and enter project
cd ticket-api

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Run migrations and seed database
php artisan migrate --seed

# Start development server
php artisan serve
```

The API will be available at `http://localhost:8000/api`.

## Configuration

### Environment Variables

```env
# Database (SQLite by default)
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Payment Gateway
PAYMENT_DRIVER=mock  # or 'stripe'
STRIPE_KEY=your_stripe_key
STRIPE_SECRET=your_stripe_secret
STRIPE_WEBHOOK_SECRET=your_webhook_secret
```

### Payment Drivers

| Driver | Description |
|--------|-------------|
| `mock` | In-memory simulation for testing (default) |
| `stripe` | Stripe Checkout integration (stubbed) |

## API Endpoints

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/register` | Register new user |
| POST | `/api/login` | Login and get token |
| POST | `/api/logout` | Revoke current token |
| GET | `/api/user` | Get authenticated user |

**Headers:** `Authorization: Bearer {token}` for protected routes

### Events

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/events` | List all events |
| GET | `/api/events/{id}` | Get event with seats |
| GET | `/api/events/{id}/seats` | List seats (filter by `?status=available`) |

### Reservations

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/reservations` | Create reservation (requires auth) |
| GET | `/api/reservations/{id}` | View reservation (owner only) |
| DELETE | `/api/reservations/{id}` | Cancel reservation (owner only) |
| POST | `/api/reservations/{id}/checkout` | Initiate payment (owner only) |

**Create Reservation Body:**
```json
{
  "event_id": 1,
  "seat_ids": [1, 2, 3]
}
```

**Response:**
```json
{
  "reservation": { ... },
  "payment_url": "https://..."
}
```

### Payments

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/payments/success?reservation={id}` | Payment success callback |
| GET | `/api/payments/cancel?reservation={id}` | Payment cancel callback |
| POST | `/api/webhooks/payments` | Provider webhook (generic) |

### Scheduled Commands

```bash
# Manually expire stale reservations
php artisan app:expire-reservations
```

## Design Decisions & Assumptions

### Concurrency Control
- Uses `SELECT ... FOR UPDATE` (pessimistic locking) when checking seat availability
- Unique constraint on `reservation_seats.seat_id` prevents double-booking at DB level
- Status enum (`available`, `held`, `booked`) tracks seat lifecycle

### Reservation Expiry
- 30-minute TTL set at reservation creation (`expires_at`)
- `expireStale()` command finds `pending` reservations where `expires_at < now()`
- Seats released atomically with reservation status update

### Payment Abstraction
- `PaymentGatewayInterface` defines `createCheckoutSession()` and `verifyWebhook()`
- Gateway resolved via `PaymentServiceProvider` based on `config('services.payment.driver')`
- DTOs (`CheckoutSession`, `PaymentWebhookEvent`) decouple controllers from provider specifics
- New providers only need to implement the interface and register in the service provider

### Webhook Security
- Each gateway implements `verifyWebhook()` to validate signatures
- Mock gateway accepts test payloads; Stripe gateway would verify `stripe-signature` header
- Webhook endpoint is public (no auth) but validates provider signature

### Database Schema
- `events` — name, venue, starts_at
- `seats` — event_id, section, row, number, price_cents, status (unique per event+section+row+number)
- `reservations` — user_id, event_id, status, expires_at
- `reservation_seats` — pivot with unique seat_id (one reservation per seat)
- `payments` — reservation_id, provider, provider_reference, status, amount_cents

### Seeding
- 3 sample events with 3 sections each (A/B/C), rows A-D, seats 1-12
- Prices: Section A (45.00), B (35.00), C (25.00) in cents
- One test user: `test@example.com` / password

## Testing

```bash
# Run all tests
php artisan test

# Run specific test class
php artisan test --filter=ReservationFlowTest
php artisan test --filter=ReservationServiceTest
```

### Test Coverage
- User registration & login
- Event & seat listing
- Seat reservation concurrency (two users, same seat)
- Reservation expiry & seat release
- Unit tests for `ReservationService` logic

## Project Structure

```
app/
├── Console/Commands/ExpireReservations.php    # Scheduled expiry command
├── Domain/Reservations/
│   ├── ReservationService.php                 # Core reservation logic
│   └── Exceptions/SeatUnavailableException.php
├── Http/Controllers/
│   ├── AuthController.php
│   ├── EventController.php
│   ├── ReservationController.php
│   └── PaymentController.php
├── Models/                                    # Eloquent models
├── Payments/
│   ├── PaymentGatewayInterface.php            # Gateway contract
│   ├── MockGateway.php                        # Test implementation
│   ├── StripeGateway.php                      # Stripe stub
│   └── DTO/                                   # Data transfer objects
└── Providers/PaymentServiceProvider.php       # Gateway binding

database/
├── migrations/                                # Schema definitions
└── seeders/DatabaseSeeder.php                 # Sample data

tests/
├── Feature/ReservationFlowTest.php            # API integration tests
└── Unit/ReservationServiceTest.php            # Service unit tests
```

## Security Considerations

- Passwords hashed via Laravel's built-in `Hashed` cast
- Sanctum tokens for stateless API auth
- Rate limiting on login (`throttle:10,1`)
- Mass assignment protection via `$fillable`
- SQL injection prevented by Eloquent/Query Builder
- Webhook signature verification (implement per provider)

## Extending Payment Providers

1. Create `app/Payments/NewProviderGateway.php` implementing `PaymentGatewayInterface`
2. Add credentials to `config/services.php`
3. Update `PaymentServiceProvider` to resolve new driver
4. Implement webhook signature verification in `verifyWebhook()`

## License

MIT