<template>
  <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <!-- Logo -->
      <div class="text-center">
        <NuxtLink to="/" class="text-2xl font-bold text-blue-600 dark:text-blue-400">
          Koomky
        </NuxtLink>
        <h2 class="mt-6 text-3xl font-bold text-slate-900 dark:text-slate-100">
          Sign in to your account
        </h2>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
          Or
          <NuxtLink
            to="/auth/register"
            class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500"
          >
            create a new account
          </NuxtLink>
        </p>
      </div>

      <!-- Login Form -->
      <form class="mt-8 space-y-6" @submit.prevent="handleLogin">
        <div class="space-y-4">
          <!-- Email -->
          <div>
            <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
              Email address
            </label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              autocomplete="email"
              required
              class="mt-1 block w-full rounded-lg border-slate-300 bg-slate-50 px-3 py-2 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-100 shadow-sm focus:ring-blue-500 focus:border-blue-500"
              placeholder="you@example.com"
            />
            <p v-if="errors.email" class="mt-1 text-sm text-red-600">
              {{ errors.email }}
            </p>
          </div>

          <!-- Password -->
          <div>
            <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
              Password
            </label>
            <input
              id="password"
              v-model="form.password"
              type="password"
              autocomplete="current-password"
              required
              class="mt-1 block w-full rounded-lg border-slate-300 bg-slate-50 px-3 py-2 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-100 shadow-sm focus:ring-blue-500 focus:border-blue-500"
              placeholder="••••••••"
            />
            <p v-if="errors.password" class="mt-1 text-sm text-red-600">
              {{ errors.password }}
            </p>
          </div>
        </div>

        <!-- Submit Button -->
        <div>
          <button
            type="submit"
            :disabled="isLoading"
            class="flex w-full justify-center rounded-lg bg-blue-600 px-3 py-3 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="isLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 00-8 8v8a8 8 0 0014.4 8 8 004 4 4 4 4 1.707 1.293 2 2a2 2 0 008-2 8v8a2 2 0 012-2v12a2 2 0 00-2 2 2a2 2 0 014-2 2a2 2 0 00-2 2 2a2 2 0 014-2v12a2 2 0 012-2v12a2 2 0 014-2v12a2 2 0 024-2v6z"></path>
            </svg>
            <span v-else>Sign in</span>
          </button>
        </div>

        <!-- Forgot Password -->
        <div class="mt-4 text-center">
          <NuxtLink
            to="/auth/forgot-password"
            class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-500"
          >
            Forgot your password?
          </NuxtLink>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'
import { useAuth } from '~/composables/useAuth'

definePageMeta({
  layout: 'auth',
})

const { login, isLoading } = useAuth()

const form = reactive({
  email: '',
  password: '',
})

const errors = ref<Record<string, string>>({})

const handleLogin = async () => {
  errors.value = {}

  try {
    await login({
      email: form.email,
      password: form.password,
    })
  } catch (error: any) {
    const errorData = error.response?._data as { error?: { message?: string; errors?: Record<string, string[]> } }

    if (errorData?.error?.errors) {
      errors.value = errorData.error.errors
    } else if (errorData?.error?.message) {
      errors.value = { _form: errorData.error.message }
    }
  }
}
</script>
