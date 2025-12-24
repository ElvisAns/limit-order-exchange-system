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
            <h1 class="text-2xl font-bold text-black">Dashboard</h1>
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
              <template #side-data="{ row }">
                <UBadge
                  :color="row.side === 'buy' ? 'green' : 'red'"
                  variant="soft"
                >
                  {{ row.side.toUpperCase() }}
                </UBadge>
              </template>

              <template #status-data="{ row }">
                <UBadge
                  :color="getStatusColor(row.status)"
                  variant="soft"
                >
                  {{ getStatusText(row.status) }}
                </UBadge>
              </template>

              <template #actions-data="{ row }">
                <UButton
                  v-if="row.status === 1"
                  size="xs"
                  color="red"
                  variant="ghost"
                  @click="handleCancelOrder(row.id)"
                >
                  Cancel
                </UButton>
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

const orderColumns = [
  { 
    accessorKey: 'symbol', 
    header: 'Symbol',
    enableSorting: true 
  },
  { 
    accessorKey: 'side', 
    header: 'Side',
    enableSorting: true 
  },
  { 
    accessorKey: 'price', 
    header: 'Price',
    enableSorting: true 
  },
  { 
    accessorKey: 'amount', 
    header: 'Amount',
    enableSorting: true 
  },
  { 
    accessorKey: 'status', 
    header: 'Status',
    enableSorting: true 
  },
  { 
    accessorKey: 'actions', 
    header: '',
    enableSorting: false 
  },
]

const tradeColumns = [
  { 
    accessorKey: 'symbol', 
    header: 'Symbol',
    enableSorting: true 
  },
  { 
    accessorKey: 'side', 
    header: 'Side',
    enableSorting: true 
  },
  { 
    accessorKey: 'price', 
    header: 'Price (USD)',
    enableSorting: true 
  },
  { 
    accessorKey: 'amount', 
    header: 'Amount',
    enableSorting: true 
  },
  { 
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
  const result = await cancelOrder(orderId)
  
  if (result.success) {
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
      description: result.error,
      color: 'red'
    })
  }
}

const handleLogout = async () => {
  await logout()
}

const getStatusColor = (status: number) => {
  switch (status) {
    case 1: return 'blue'
    case 2: return 'green'
    case 3: return 'gray'
    default: return 'gray'
  }
}

const getStatusText = (status: number) => {
  switch (status) {
    case 1: return 'OPEN'
    case 2: return 'FILLED'
    case 3: return 'CANCELLED'
    default: return 'UNKNOWN'
  }
}

// Pusher setup
let pusher: Pusher | null = null
let userChannel: any = null
let orderbookChannel: any = null

const setupPusher = () => {
  if (!token.value || !user.value) return

  pusher = new Pusher(config.public.pusherKey, {
    cluster: config.public.pusherCluster,
    authEndpoint: `${config.public.backendUrl}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${token.value}`,
      },
    },
  })

  // Subscribe to user's private channel for order matched events
  userChannel = pusher.subscribe(`private-App.Models.User.${user.value.id}`)

  userChannel.bind('order.matched', (data: any) => {
    console.log('Order matched:', data)

    // Update balance
    if (profile.value) {
      profile.value.balance = data.new_balance
    }

    // Update assets
    assets.value = data.assets

    // Update order status in list
    const orderIndex = myOrders.value.findIndex(o => o.id === data.order_id)
    if (orderIndex !== -1) {
      myOrders.value[orderIndex].status = 2 // FILLED
    }

    // Refresh orderbook and trades
    fetchOrderbook()
    fetchTrades()

    // Show notification
    toast.add({
      title: 'Order Matched! 🎉',
      description: `Your ${data.side} order for ${data.amount} ${data.symbol} was matched at $${data.matched_price}`,
      color: 'green',
      timeout: 10000,
    })

    if (data.refund_amount) {
      toast.add({
        title: 'Refund Received 💰',
        description: `You received a refund of $${data.refund_amount}`,
        color: 'blue',
        timeout: 10000,
      })
    }
  })

  // Subscribe to orderbook channel
  subscribeToOrderbook(selectedSymbol.value)
}

const subscribeToOrderbook = (symbol: string) => {
  if (!pusher) return

  // Unsubscribe from previous orderbook channel if exists
  if (orderbookChannel) {
    orderbookChannel.unbind_all()
    orderbookChannel.unsubscribe()
  }

  // Subscribe to new symbol's orderbook channel
  orderbookChannel = pusher.subscribe(`orderbook.${symbol}`)

  orderbookChannel.bind('orderbook.updated', (data: any) => {
    console.log('Orderbook updated:', data)

    // Refresh orderbook to get latest data
    fetchOrderbook()
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

