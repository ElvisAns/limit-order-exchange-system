export const useApi = () => {
  const config = useRuntimeConfig()
  const token = useCookie('auth_token')

  const api = $fetch.create({
    baseURL: `${config.public.backendUrl}/api`,
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    },
    onRequest({ options }) {
      if (token.value) {
        options.headers = {
          ...options.headers,
          Authorization: `Bearer ${token.value}`,
        }
      }
    },
  })

  return {
    api,
    token,
  }
}

