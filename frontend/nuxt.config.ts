// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: true },
  modules: ['@nuxt/ui'],
  css: ['~/assets/css/main.css'],
  
  runtimeConfig: {
    public: {
      backendUrl: process.env.NUXT_BACKEND_URL || 'http://localhost:8000',
      pusherKey: process.env.NUXT_PUSHER_KEY || '',
      pusherCluster: process.env.NUXT_PUSHER_CLUSTER || 'ap2',
    }
  },

  colorMode: {
    preference: 'light',
    fallback: 'light',
  },

  ui: {
    theme: {
      colors: ['black', 'white', 'gray', 'red', 'green', 'blue']
    }
  },
})
