<template>
  <div class="min-h-screen flex items-center justify-center bg-white">
    <UCard class="w-full max-w-md text-center">
      <template #header>
        <h1 class="text-3xl font-bold text-black">Limit Order Exchange</h1>
      </template>

      <p class="text-gray-600 mb-6">
        Trade cryptocurrencies with limit orders
      </p>

      <div class="space-y-3">
        <!-- Show Dashboard button if authenticated -->
        <template v-if="isAuthenticated">
          <UButton
            to="/dashboard"
            block
            color="primary"
          >
            Go to Dashboard
          </UButton>

          <UButton
            @click="handleLogout"
            block
            variant="outline"
            color="primary"
          >
            Logout
          </UButton>
        </template>

        <!-- Show Login/Register if not authenticated -->
        <template v-else>
          <UButton
            to="/login"
            block
            color="primary"
          >
            Login
          </UButton>

          <UButton
            to="/register"
            block
            variant="outline"
            color="primary"
          >
            Register
          </UButton>
        </template>
      </div>
    </UCard>
  </div>
</template>

<script setup lang="ts">
const { logout } = useAuth()
const token = useCookie('auth_token')
const router = useRouter()

const isAuthenticated = computed(() => !!token.value)

const handleLogout = async () => {
  await logout()
  await navigateTo('/')
}
</script>