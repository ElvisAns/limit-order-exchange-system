<template>
  <div class="min-h-screen flex items-center justify-center bg-white">
    <UCard class="w-full max-w-md">
      <template #header>
        <h2 class="text-2xl font-bold text-center text-black">Login</h2>
      </template>

      <UForm @submit.prevent="handleLogin" class="space-y-4">
        <UFormField label="Email" required>
          <UInput
            v-model="form.email"
            type="email"
            placeholder="your@email.com"
            required
            size="xl"
            class="w-full"
          />
        </UFormField>

        <UFormField label="Password" required>
          <UInput
            v-model="form.password"
            type="password"
            placeholder="••••••••"
            required
            size="xl"
            class="w-full"
          />
        </UFormField>

        <UAlert
            color="neutral"
            title="Error"
            :description="error"
            icon="i-heroicons-x-mark-20-solid"
            v-if="error"
        />

        <UButton
          type="submit"
          block
          :loading="loading"
          color="primary"
        >
          Login
        </UButton>
      </UForm>

      <template #footer>
        <div class="text-center text-sm text-gray-600">
          Don't have an account?
          <NuxtLink to="/register" class="text-black font-semibold hover:underline">
            Register
          </NuxtLink>
        </div>
      </template>
    </UCard>
  </div>
</template>

<script setup lang="ts">
const { login } = useAuth()
const router = useRouter()
const token = useCookie('auth_token')

const form = reactive({
  email: '',
  password: '',
})

const loading = ref(false)
const error = ref('')

// Check if already authenticated
onMounted(async () => {
  if (token.value) {
    await navigateTo('/dashboard')
  }
})

const handleLogin = async () => {
  loading.value = true
  error.value = ''

  const result = await login(form.email, form.password)

  loading.value = false

  if (result.success) {
    await navigateTo('/dashboard')
  } else {
    error.value = result.error
  }
}
</script>

