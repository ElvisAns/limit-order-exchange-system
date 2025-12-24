# Limit-Order Exchange – Mini Engine

A simplified **peer-to-peer limit-order exchange** built to demonstrate safe balance handling, atomic execution, and real-time synchronization.

The platform allows users to trade crypto assets (BTC, ETH) **with other users**, using limit orders.
The system never acts as a trading counterparty and does not provide liquidity.

## High-Level User & System Flow

```mermaid
sequenceDiagram
    participant U as User
    participant UI as Frontend (Vue.js)
    participant API as Laravel API
    participant DB as Database
    participant RT as Pusher

    U->>UI: Login
    UI->>API: Authenticate
    API->>DB: Fetch user balances & assets
    API-->>UI: Wallet overview

    U->>UI: Place Buy/Sell order
    UI->>API: POST /api/orders
    API->>DB: Validate & lock funds/assets
    API->>DB: Create order (OPEN)

    API->>API: Trigger matching engine
    API->>DB: Attempt match

    alt Match found
        API->>DB: Execute trade atomically
        API->>RT: Broadcast OrderMatched
        RT-->>UI: Real-time update
    else No match
        API-->>UI: Order remains OPEN
    end
```

## Buy Order Creation

```mermaid
sequenceDiagram
    participant U as User
    participant API as Laravel API
    participant DB as Database

    U->>API: POST /api/orders (BUY)
    API->>DB: BEGIN TRANSACTION
    API->>DB: SELECT user FOR UPDATE
    API->>DB: Check USD balance
    API->>DB: Deduct USD (lock funds)
    API->>DB: Insert BUY order (OPEN)
    API->>DB: COMMIT
```

## Sell Order Creation

```mermaid
sequenceDiagram
    participant U as User
    participant API as Laravel API
    participant DB as Database

    U->>API: POST /api/orders (SELL)
    API->>DB: BEGIN TRANSACTION
    API->>DB: SELECT asset FOR UPDATE
    API->>DB: Check asset availability
    API->>DB: Move amount → locked_amount
    API->>DB: Insert SELL order (OPEN)
    API->>DB: COMMIT
```

## Order Matching (Full Match Only)

```mermaid
sequenceDiagram
    participant Engine as Matching Engine
    participant DB as Database

    Engine->>DB: BEGIN TRANSACTION
    Engine->>DB: Lock BUY order
    Engine->>DB: Lock SELL order
    Engine->>DB: Validate orders are OPEN
    Engine->>DB: Transfer asset to buyer
    Engine->>DB: Transfer USD to seller
    Engine->>DB: Apply 1.5% commission
    Engine->>DB: Mark orders as FILLED
    Engine->>DB: Insert trade record
    Engine->>DB: COMMIT
```

## Real-Time Notification Flow

```mermaid
sequenceDiagram
    participant API as Laravel
    participant RT as Pusher
    participant B as Buyer UI
    participant S as Seller UI

    API->>RT: OrderMatched event
    RT-->>B: private-user.{buyer_id}
    RT-->>S: private-user.{seller_id}

    B->>B: Update wallet & orders
    S->>S: Update wallet & orders
```

## Trading Model Clarification

> Users trade directly with other users on the platform.
> The system acts only as a matching and settlement engine and never as a counterparty.

* Buy orders consume USD from buyers
* Sell orders consume assets from sellers
* Funds and assets are locked until execution or cancellation
