export const useAuth = () => {
  const { api, token } = useApi()
  const user = useState<any>('user', () => null)
  const router = useRouter()

  const login = async (email: string, password: string) => {
    try {
      const response: any = await api('/login', {
        method: 'POST',
        body: { email, password },
      })
      
      token.value = response.token
      user.value = response.user
      
      return { success: true, token: response.token }
    } catch (error: any) {
      return { 
        success: false, 
        error: error.data?.message || 'Login failed' 
      }
    }
  }

  const register = async (name: string, email: string, password: string, password_confirmation: string) => {
    try {
      const response: any = await api('/register', {
        method: 'POST',
        body: { name, email, password, password_confirmation },
      })
      
      token.value = response.token
      user.value = response.user
      
      return { success: true }
    } catch (error: any) {
      return { 
        success: false, 
        error: error.data?.message || 'Registration failed' 
      }
    }
  }

  const logout = async () => {
    try {
      await api('/logout', { method: 'POST' })
    } catch (error) {
      // Ignore errors on logout
    }
    
    token.value = null
    user.value = null
    await navigateTo('/login')
  }

  const fetchProfile = async () => {
    try {
      const response: any = await api('/profile')
      user.value = response.user
      return { success: true, user: response.user }
    } catch (error: any) {
      // Clear invalid token
      token.value = null
      user.value = null
      return { success: false, error: error.data?.message || 'Authentication failed' }
    }
  }

  return {
    user,
    login,
    register,
    logout,
    fetchProfile,
    isAuthenticated: computed(() => !!token.value),
  }
}

