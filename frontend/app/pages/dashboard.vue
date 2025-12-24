<template>
  <div class="min-h-screen bg-white">
    <!-- Loading Skeleton -->
    <div v-if="loading" class="min-h-screen flex items-center justify-center">
      <div class="text-center">
        <USkeleton class="h-8 w-48 mx-auto mb-4" />
        <USkeleton class="h-4 w-32 mx-auto" />
      </div>
    </div>

    <!-- Main Content -->
    <div v-else>
      <!-- Header -->
      <div class="border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
          <div class="flex justify-between items-center">
            <div class="flex items-center gap-3">
              <h1 class="text-2xl font-bold text-black">Dashboard</h1>
              <!-- Connection Status Indicator -->
              <UBadge 
                :color="connectionStatus === 'connected' ? 'green' : connectionStatus === 'connecting' ? 'yellow' : 'red'" 
                variant="soft"
                size="xs"
              >
                <template v-if="connectionStatus === 'connected'">
                  🟢 Live
                </template>
                <template v-else-if="connectionStatus === 'connecting'">
                  🟡 Connecting...
                </template>
                <template v-else>
                  🔴 Disconnected
                </template>
              </UBadge>
            </div>
            <div class="flex items-center gap-2">
              <span class="text-sm text-gray-600">{{ profile?.name }}</span  >
              <UButton color="neutral" @click="handleLogout">
                Logout
                <Icon name="i-heroicons-arrow-right-on-rectangle" />
              </UButton>
            </div>
          </div>
        </div>
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Wallet Overview -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <UCard>
          <div class="text-sm text-gray-600">USD Balance</div>
          <div class="text-2xl font-bold text-black">${{ profile?.balance || '0.00' }}</div>
        </UCard>

        <UCard v-for="asset in assets" :key="asset.symbol">
          <div class="text-sm text-gray-600">{{ asset.symbol }}</div>
          <div class="text-2xl font-bold text-black">{{ asset.amount }}</div>
          <div v-if="parseFloat(asset.locked_amount) > 0" class="text-xs text-gray-500">
            Locked: {{ asset.locked_amount }}
          </div>
        </UCard>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Order Form -->
        <div class="lg:col-span-1">
          <UCard>
            <template #header>
              <h2 class="text-xl font-bold text-black">Place Order</h2>
            </template>

            <UForm @submit.prevent="handlePlaceOrder" class="space-y-4">
              <UFormField label="Symbol" required>
                <USelectMenu
                  v-model="orderForm.symbol"
                  :items="symbols"
                  size="xl"
                  class="w-full"
                />
              </UFormField>

              <UFormField label="Side" required>
                <USelectMenu
                  v-model="orderForm.side"
                  :items="sides"
                  placeholder="Select side"
                  size="xl"
                  class="w-full"
                />
              </UFormField>

              <UFormField label="Price (USD)" required>
                <UInput
                  v-model="orderForm.price"
                  type="number"
                  step="0.01"
                  placeholder="10000.00"
                  required
                  size="xl"
                  class="w-full"
                />
              </UFormField>

              <UFormField label="Amount" required>
                <UInput
                  v-model="orderForm.amount"
                  type="number"
                  step="0.00000001"
                  placeholder="0.1"
                  required
                  size="xl"
                  class="w-full"
                />
              </UFormField>

              <UAlert
                v-if="orderError"
                color="neutral"
                variant="soft"
                :title="orderError"
                icon="i-heroicons-x-mark-20-solid"
              />

              <UAlert
                v-if="orderSuccess"
                color="neutral"
                variant="soft"
                title="Order placed successfully!"
                icon="i-heroicons-check-circle-20-solid"
              />

              <UButton
                type="submit"
                block
                :loading="placingOrder"
                color="primary"
              >
                Place Order
              </UButton>
            </UForm>
          </UCard>
        </div>

        <!-- Orderbook & Orders -->
        <div class="lg:col-span-2 space-y-8">
          <!-- Orderbook -->
          <UCard>
            <template #header>
              <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold text-black">Orderbook</h2>
                <USelectMenu
                  v-model="selectedSymbol"
                  :items="symbols"
                  size="xl"
                  class="w-48"
                  @update:model-value="fetchOrderbook"
                />
              </div>
            </template>

            <div class="grid grid-cols-2 gap-6">
              <!-- Buy Orders -->
              <div>
                <div class="flex items-center justify-start gap-2 mb-3">
                  <h3 class="text-sm font-semibold text-green-600">Buy Orders</h3>
                  <UBadge color="green" variant="soft" size="xs">
                    {{ orderbook.buy_orders?.length || 0 }}
                  </UBadge>
                </div>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                  <UCard
                    v-for="order in orderbook.buy_orders"
                    :key="order.id"
                    class="hover:bg-gray-100 dark:hover:bg-gray-800 transition-all cursor-pointer"
                    @click="handleQuickSell(order)"
                  >
                    <div class="flex items-center justify-between">
                      <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                          <UIcon name="i-heroicons-arrow-trending-up" class="text-green-600" />
                          <span class="font-mono text-sm font-bold text-green-700">
                            ${{ order.price }}
                          </span>
                        </div>
                        <div class="text-xs text-gray-600">
                          Amount: <span class="font-semibold">{{ order.amount }}</span>
                          <br />
                          Date: <span class="font-semibold">{{ new Date(order.created_at).toLocaleString() }}</span>
                        </div>
                      </div>
                      <UButton
                        color="green"
                        variant="soft"
                        size="xs"
                        icon="i-heroicons-arrow-down-tray"
                      >
                        Sell
                      </UButton>
                    </div>
                  </UCard>
                  <div v-if="orderbook.buy_orders?.length === 0" class="text-center py-8">
                    <UIcon name="i-heroicons-inbox" class="text-gray-300 text-3xl mb-2" />
                    <p class="text-sm text-gray-400">No buy orders</p>
                  </div>
                </div>
              </div>

              <!-- Sell Orders -->
              <div>
                <div class="flex items-center justify-start gap-2 mb-3">
                  <h3 class="text-sm font-semibold text-red-600">Sell Orders</h3>
                  <UBadge color="red" variant="soft" size="xs">
                    {{ orderbook.sell_orders?.length || 0 }}
                  </UBadge>
                </div>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                  <UCard
                    v-for="order in orderbook.sell_orders"
                    :key="order.id"
                    class="hover:bg-gray-100 dark:hover:bg-gray-800 transition-all cursor-pointer"
                    @click="handleQuickBuy(order)"
                  >
                    <div class="flex items-center justify-between">
                      <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                          <UIcon name="i-heroicons-arrow-trending-down" class="text-red-600" />
                          <span class="font-mono text-sm font-bold text-red-700">
                            ${{ order.price }}
                          </span>
                        </div>
                        <div class="text-xs text-gray-600">
                          Amount: <span class="font-semibold">{{ order.amount }}</span>
                          <br />
                          Date: <span class="font-semibold">{{ new Date(order.created_at).toLocaleString() }}</span>
                        </div>
                      </div>
                      <UButton
                        color="red"
                        variant="soft"
                        size="xs"
                        icon="i-heroicons-arrow-up-tray"
                      >
                        Buy
                      </UButton>
                    </div>
                  </UCard>
                  <div v-if="orderbook.sell_orders?.length === 0" class="text-center py-8">
                    <UIcon name="i-heroicons-inbox" class="text-gray-300 text-3xl mb-2" />
                    <p class="text-sm text-gray-400">No sell orders</p>
                  </div>
                </div>
              </div>
            </div>
          </UCard>

          <!-- My Orders -->
          <UCard>
            <template #header>
              <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold text-black">My Orders</h2>
                <USelectMenu
                  v-model="selectedSymbol"
                  :items="symbols"
                  size="xl"
                  class="w-48"
                  @update:model-value="fetchOrderbook"
                />
              </div>
            </template>

            <UTable
              :data="myOrders"
              :columns="orderColumns"
              class="flex-1 max-h-[312px]" 
            >
            <template #action-cell="{ row }">
              <UButton
                v-if="row.original.status === 'open'"
                size="xs"
                variant="soft"
                icon="i-heroicons-x-circle"
                :loading="cancellingOrderId == row.original.id"
                @click="handleCancelOrder(row.original.id)">
                Cancel
              </UButton>
              <span v-else class="text-xs text-gray-400 dark:text-gray-600">—</span>
              </template>
            </UTable>
          </UCard>

          <!-- My Trades -->
          <UCard>
            <template #header>
              <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-black">My Trades</h2>
                <UBadge color="primary" variant="soft">
                  {{ myTrades.length }} completed
                </UBadge>
              </div>
            </template>

            <UTable
              :data="myTrades"
              :columns="tradeColumns"
              class="flex-1 max-h-[312px]" 
            >
              <template #side-data="{ row }">
                <UBadge
                  :color="row.side === 'buy' ? 'green' : 'red'"
                  variant="soft"
                >
                  {{ row.side.toUpperCase() }}
                </UBadge>
              </template>

              <template #created_at-data="{ row }">
                <span class="text-sm text-gray-600">
                  {{ new Date(row.created_at).toLocaleString() }}
                </span>
              </template>
            </UTable>

            <div v-if="myTrades.length === 0" class="text-center py-8">
              <UIcon name="i-heroicons-chart-bar" class="text-gray-300 text-4xl mb-2" />
              <p class="text-sm text-gray-400">No completed trades yet</p>
            </div>
          </UCard>
        </div>
      </div>
    </div>

      <!-- Notifications -->
      <!--<UNotifications />-->
    </div>
  </div>
</template>

<script setup lang="ts">
import Pusher from 'pusher-js'
import type { Ref } from 'vue'

const { user, logout, fetchProfile } = useAuth()
const { placeOrder, getOrderbook, cancelOrder, getTrades } = useOrders()
const { api, token } = useApi()
const config = useRuntimeConfig()
const toast = useToast()
const router = useRouter()

// Loading state
const loading = ref(true)

// Data
const profile: Ref<any> = ref(null)
const assets: Ref<any[]> = ref([])
const myOrders: Ref<any[]> = ref([])
const myTrades: Ref<any[]> = ref([])
const orderbook: Ref<any> = ref({ buy_orders: [], sell_orders: [] })
const connectionStatus = ref<'disconnected' | 'connecting' | 'connected'>('disconnected')

const symbols = ['BTC', 'ETH', 'USDT', 'BNB', 'SOL', 'ADA', 'DOT', 'MATIC']
const sides = ['buy', 'sell']
const selectedSymbol = ref('BTC')

const orderForm = reactive({
  symbol: 'BTC',
  side: 'buy',
  price: '',
  amount: '',
})

const placingOrder = ref(false)
const orderError = ref('')
const orderSuccess = ref(false)
const cancellingOrderId = ref<number | null>(null)

const orderColumns = [
  { 
    id: 'symbol',
    accessorKey: 'symbol', 
    header: 'Symbol',
    enableSorting: true 
  },
  { 
    id: 'side',
    accessorKey: 'side', 
    header: 'Side',
    enableSorting: true 
  },
  { 
    id: 'price',
    accessorKey: 'price', 
    header: 'Price',
    enableSorting: true 
  },
  { 
    id: 'amount',
    accessorKey: 'amount', 
    header: 'Amount',
    enableSorting: true 
  },
  { 
    id: 'status',
    accessorKey: 'status', 
    header: 'Status',
    enableSorting: true 
  },
  { 
    id: 'action',
    accessorKey: 'actions', 
    header: 'Actions',
    enableSorting: false 
  },
]

const tradeColumns = [
  { 
    id: 'symbol',
    accessorKey: 'symbol', 
    header: 'Symbol',
    enableSorting: true 
  },
  { 
    id: 'side',
    accessorKey: 'side', 
    header: 'Side',
    enableSorting: true 
  },
  { 
    id: 'price',
    accessorKey: 'price', 
    header: 'Price (USD)',
    enableSorting: true 
  },
  { 
    id: 'amount',
    accessorKey: 'amount', 
    header: 'Amount',
    enableSorting: true 
  },
  { 
    id: 'created_at',
    accessorKey: 'created_at', 
    header: 'Date',
    enableSorting: true 
  },
]

// Methods
const loadProfile = async () => {
  const result = await fetchProfile()
  
  if (result.success && result.user) {
    profile.value = result.user
    assets.value = result.user.assets || []
    return true
  } else {
    // Auth failed - redirect to login
    toast.add({
      title: 'Session Expired',
      description: 'Please login again',
      color: 'red'
    })
    await navigateTo('/login')
    return false
  }
}

const fetchOrderbook = async () => {
  const result = await getOrderbook(selectedSymbol.value)
  
  if (result.success) {
    orderbook.value = result.data
    myOrders.value = result.data.my_orders || []
  }
}

// Watch for symbol changes to resubscribe to orderbook
watch(selectedSymbol, (newSymbol) => {
  if (pusher) {
    subscribeToOrderbook(newSymbol)
  }
})

const fetchTrades = async () => {
  const result = await getTrades()
  if (result.success) {
    myTrades.value = result.data.trades || []
  }
}

const handlePlaceOrder = async () => {
  placingOrder.value = true
  orderError.value = ''
  orderSuccess.value = false

  const result = await placeOrder(
    orderForm.symbol,
    orderForm.side,
    orderForm.price,
    orderForm.amount
  )

  placingOrder.value = false

  if (result.success) {
    orderSuccess.value = true
    orderForm.price = ''
    orderForm.amount = ''
    
    // Refresh data
    await loadProfile()
    await fetchOrderbook()
    
    toast.add({
      title: 'Order Placed',
      description: `${orderForm.side.toUpperCase()} order for ${orderForm.amount} ${orderForm.symbol} placed successfully`,
      color: 'green'
    })
  } else {
    orderError.value = result.error
  }
}

const handleQuickBuy = (sellOrder: any) => {
  // Pre-fill form to buy this sell order
  orderForm.symbol = selectedSymbol.value
  orderForm.side = 'buy'
  orderForm.price = sellOrder.price
  orderForm.amount = sellOrder.amount
  
  // Scroll to form
  window.scrollTo({ top: 0, behavior: 'smooth' })
  
  toast.add({
    title: 'Order Pre-filled',
    description: `Ready to buy ${sellOrder.amount} ${selectedSymbol.value} at $${sellOrder.price}`,
    color: 'blue',
    timeout: 3000
  })
}

const handleQuickSell = (buyOrder: any) => {
  // Pre-fill form to sell to this buy order
  orderForm.symbol = selectedSymbol.value
  orderForm.side = 'sell'
  orderForm.price = buyOrder.price
  orderForm.amount = buyOrder.amount
  
  // Scroll to form
  window.scrollTo({ top: 0, behavior: 'smooth' })
  
  toast.add({
    title: 'Order Pre-filled',
    description: `Ready to sell ${buyOrder.amount} ${selectedSymbol.value} at $${buyOrder.price}`,
    color: 'blue',
    timeout: 3000
  })
}

const handleCancelOrder = async (orderId: number) => {
  cancellingOrderId.value = orderId
  
  try {
    const result = await cancelOrder(orderId)
    
    cancellingOrderId.value = null
    
    if (result.success) {
      // Update local state immediately
      const orderIndex = myOrders.value.findIndex(o => o.id === orderId)
      if (orderIndex !== -1) {
        myOrders.value[orderIndex].status = 'cancelled'
      }
      
      // Refresh data
      await loadProfile()
      await fetchOrderbook()
      
      toast.add({
        title: 'Order Cancelled',
        description: 'Order cancelled successfully',
        color: 'green'
      })
    } else {
      toast.add({
        title: 'Error',
        description: result.error || 'Failed to cancel order',
        color: 'red'
      })
    }
  } catch (error) {
    cancellingOrderId.value = null
    toast.add({
      title: 'Error',
      description: 'An unexpected error occurred',
      color: 'red'
    })
  }
}

const handleLogout = async () => {
  await logout()
}


// Pusher setup
let pusher: Pusher | null = null
let userChannel: any = null
let orderbookChannel: any = null

const setupPusher = () => {
  if (!token.value || !user.value) {
    return
  }

  connectionStatus.value = 'connecting'

  try {
    pusher = new Pusher(config.public.pusherKey, {
      cluster: config.public.pusherCluster,
      authEndpoint: `${config.public.backendUrl}/api/broadcasting/auth`,
      auth: {
        headers: {
          Authorization: `Bearer ${token.value}`,
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      },
    })

    // Connection state change handler
    pusher.connection.bind('state_change', (states: any) => {
      if (states.current === 'connected') {
        connectionStatus.value = 'connected'
      } else if (states.current === 'connecting' || states.current === 'unavailable') {
        connectionStatus.value = 'connecting'
      } else {
        connectionStatus.value = 'disconnected'
      }
    })

    // Connection error handler
    pusher.connection.bind('error', (err: any) => {
      toast.add({
        title: 'Connection Error',
        description: 'Failed to connect to real-time updates',
        color: 'red',
        timeout: 5000,
      })
    })

    // Subscribe to user's private channel for order matched events
    userChannel = pusher.subscribe(`private-App.Models.User.${user.value.id}`)

    userChannel.bind('order.matched', (data: any) => {
      // Update balance (event sends the user's updated balance)
      if (profile.value && data.new_balance) {
        profile.value.balance = data.new_balance
      }

      // Update assets (event sends the user's updated assets)
      if (data.assets) {
        assets.value = data.assets
      }

      // Update order status in list
      const orderIndex = myOrders.value.findIndex(o => o.id === data.order_id)
      if (orderIndex !== -1) {
        myOrders.value[orderIndex].status = 'filled'
      }

      // Refresh orderbook and trades
      fetchOrderbook()
      fetchTrades()

      // Show personalized notification based on side
      if (data.side === 'buy') {
        // User bought crypto
        toast.add({
          title: 'Purchase Complete!',
          description: `Successfully bought ${data.amount} ${data.symbol} at $${data.matched_price}`,
          color: 'green',
          timeout: 10000,
          icon: 'i-heroicons-shopping-cart'
        })
        
        // Play success sound (optional)
        if (typeof window !== 'undefined') {
          const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBjtm0PLMdzAFH3Hn7OGVRwoSZr3t56ZSEglLoeTvvW0hBzxr0PLMdjAFHnHm7eKWRgsRZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHnHm7eKWRwoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRgoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRgoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRgoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRgoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRgoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRgoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRgoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRwoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRwoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRwoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRwoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRwoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRwoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRwoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRwoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRwoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRwoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRwoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRwoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRwoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRwoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRwoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRwoSZr3t56ZSEglKouTvvG0hBzxr0PLMdjAFHXHm7eKWRwoSZr3t56ZSEglKouTvvG0h')
          audio.volume = 0.3
          audio.play().catch(() => {})
        }
      } else {
        // User sold crypto
        toast.add({
          title: 'Sale Complete!',
          description: `Successfully sold ${data.amount} ${data.symbol} at $${data.matched_price}`,
          color: 'green',
          timeout: 10000,
          icon: 'i-heroicons-banknotes'
        })
      }

      // Show refund notification if applicable
      if (data.refund_amount && parseFloat(data.refund_amount) > 0) {
        toast.add({
          title: 'Bonus Refund!',
          description: `Price difference refund: $${data.refund_amount}`,
          color: 'blue',
          timeout: 10000,
          icon: 'i-heroicons-currency-dollar'
        })
      }
    })

    // Subscribe to orderbook channel
    subscribeToOrderbook(selectedSymbol.value)
  } catch (error) {
    connectionStatus.value = 'disconnected'
  }
}

const subscribeToOrderbook = (symbol: string) => {
  if (!pusher) {
    return
  }

  // Unsubscribe from previous orderbook channel if exists
  if (orderbookChannel) {
    orderbookChannel.unbind_all()
    orderbookChannel.unsubscribe()
  }

  // Subscribe to new symbol's orderbook channel
  orderbookChannel = pusher.subscribe(`orderbook.${symbol}`)

  orderbookChannel.bind('orderbook.updated', (data: any) => {
    // Only refresh if it's for the current symbol
    if (data.symbol === selectedSymbol.value) {
      fetchOrderbook()
      
      // Show notification based on action type
      if (data.action === 'created') {
        toast.add({
          title: 'New Order Available',
          description: `A new ${data.order?.side || ''} order appeared in ${data.symbol} orderbook`,
          color: 'blue',
          timeout: 3000,
          icon: 'i-heroicons-plus-circle'
        })
      } else if (data.action === 'cancelled') {
        toast.add({
          title: 'Order Cancelled',
          description: `An order was removed from ${data.symbol} orderbook`,
          color: 'gray',
          timeout: 2000,
          icon: 'i-heroicons-x-circle'
        })
      } else if (data.action === 'matched') {
        toast.add({
          title: 'Orders Matched',
          description: `Orders were matched in ${data.symbol} orderbook`,
          color: 'green',
          timeout: 3000,
          icon: 'i-heroicons-check-circle'
        })
      }
    }
  })
}

const cleanupPusher = () => {
  if (userChannel) {
    userChannel.unbind_all()
    userChannel.unsubscribe()
  }
  if (orderbookChannel) {
    orderbookChannel.unbind_all()
    orderbookChannel.unsubscribe()
  }
  if (pusher) {
    pusher.disconnect()
  }
  
  connectionStatus.value = 'disconnected'
}

// Lifecycle
onMounted(async () => {
  // Check if token exists
  if (!token.value) {
    loading.value = false
    await navigateTo('/login')
    return
  }

  // Load profile with loading state
  loading.value = true
  const success = await loadProfile()
  
  if (success) {
    await fetchOrderbook()
    await fetchTrades()
    setupPusher()
  }
  
  loading.value = false
})

onUnmounted(() => {
  cleanupPusher()
})
</script>

