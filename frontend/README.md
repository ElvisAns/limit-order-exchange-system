# Frontend - Nuxt 3 SPA

## Installation

```bash
npm install
```

## Configuration

Create `.env`:

```env
NUXT_BACKEND_URL=http://localhost:8000
NUXT_PUSHER_KEY=your_pusher_key
NUXT_PUSHER_CLUSTER=ap2
```

**Important**: `NUXT_PUSHER_KEY` must match backend's `PUSHER_APP_KEY`

## Run

```bash
npm run dev
```

Open: http://localhost:3000

## Project Structure

```
app/
├── composables/
│   ├── useApi.ts      # HTTP client
│   ├── useAuth.ts     # Authentication
│   └── useOrders.ts   # Order management
└── pages/
    ├── index.vue      # Landing
    ├── login.vue      # Login
    ├── register.vue   # Register
    └── dashboard.vue  # Trading dashboard
```

## Features

- **Real-time orderbook** with Pusher WebSockets
- **Connection status** indicator (🟢 Live / 🟡 Connecting / 🔴 Disconnected)
- **Quick trade**: Click orderbook entries to pre-fill form
- **Live notifications**: Toast alerts for all actions
- **Responsive design**: Works on all devices

## Build

```bash
npm run build      # Production build
npm run preview    # Preview production
```

## Troubleshooting

**Styles not loading?**
```bash
rm -rf .nuxt node_modules/.cache
npm install
npm run dev
```

**Pusher not connecting?**
- Check browser console (F12) for errors
- Verify `NUXT_PUSHER_KEY` matches backend
- Check connection status badge

**API failing?**
- Ensure backend is running on port 8000
- Check `NUXT_BACKEND_URL` is correct
- Verify token in cookies (F12 → Application → Cookies)
