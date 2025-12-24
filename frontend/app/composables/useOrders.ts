export const useOrders = () => {
  const { api } = useApi()

  const placeOrder = async (symbol: string, side: string, price: string, amount: string) => {
    try {
      const response: any = await api('/orders', {
        method: 'POST',
        body: { symbol, side, price, amount },
      })
      
      return { success: true, data: response }
    } catch (error: any) {
      return { 
        success: false, 
        error: error.data?.message || 'Failed to place order',
        errors: error.data?.errors || {}
      }
    }
  }

  const getOrderbook = async (symbol: string) => {
    try {
      const response: any = await api(`/orders?symbol=${symbol}`)
      return { success: true, data: response }
    } catch (error: any) {
      return { 
        success: false, 
        error: error.data?.message || 'Failed to fetch orderbook'
      }
    }
  }

  const cancelOrder = async (orderId: number) => {
    try {
      const response: any = await api(`/orders/${orderId}/cancel`, {
        method: 'POST',
      })
      
      return { success: true, data: response }
    } catch (error: any) {
      return { 
        success: false, 
        error: error.data?.message || 'Failed to cancel order'
      }
    }
  }

  const getTrades = async () => {
    try {
      const response: any = await api('/trades')
      return { success: true, data: response }
    } catch (error: any) {
      return { 
        success: false, 
        error: error.data?.message || 'Failed to fetch trades'
      }
    }
  }

  return {
    placeOrder,
    getOrderbook,
    cancelOrder,
    getTrades,
  }
}

