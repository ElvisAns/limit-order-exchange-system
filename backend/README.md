# Backend - Laravel API

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

## Configuration

Edit `.env`:

```env
BROADCAST_CONNECTION=pusher
QUEUE_CONNECTION=database

PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_key
PUSHER_APP_SECRET=your_secret
PUSHER_APP_CLUSTER=ap2

SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:8000
```

## Database

```bash
php artisan migrate
php artisan db:seed  # Optional: creates test users & orders
```

## Run

```bash
# Terminal 1: API server
php artisan serve

# Terminal 2: Queue worker (required!)
php artisan queue:work
```

## API Routes

```
POST /api/register - Register user
POST /api/login - Login
POST /api/logout - Logout
GET  /api/profile - Get user with assets
GET  /api/orders?symbol=BTC - Get orderbook
POST /api/orders - Create order
POST /api/orders/{id}/cancel - Cancel order
GET  /api/trades - Get user trades
```

## Database Schema

**users**: `id`, `name`, `email`, `password`, `balance` (USD)  
**assets**: `user_id`, `symbol`, `amount`, `locked_amount`  
**orders**: `user_id`, `symbol`, `side`, `price`, `amount`, `status`  
**trades**: `buy_order_id`, `sell_order_id`, `buyer_id`, `seller_id`, `symbol`, `price`, `amount`

## Broadcasting Events

**Private Channel**: `private-App.Models.User.{id}`  
- `order.matched` - When user's order executes

**Public Channel**: `orderbook.{symbol}`  
- `orderbook.updated` - When orderbook changes

## Debugging

```bash
# View logs
tail -f storage/logs/laravel.log

# Check queue
php artisan queue:failed
php artisan queue:restart

# Code formatting
vendor/bin/pint
```
