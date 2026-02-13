<template>
  <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <!-- Logo -->
      <div class="text-center">
        <NuxtLink to="/" class="text-2xl font-bold text-blue-600 dark:text-blue-400">
          Koomky
        </NuxtLink>
        <h2 class="mt-6 text-3xl font-bold text-slate-900 dark:text-slate-100">
          Create your account
        </h2>
        <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">
          Already have an account?
          <NuxtLink
            to="/auth/login"
            class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500"
          >
            Sign in
          </NuxtLink>
        </p>
      </div>

      <!-- Registration Form -->
      <form class="mt-8 space-y-6" @submit.prevent="handleRegister">
        <div class="space-y-4">
          <!-- Name -->
          <div>
            <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
              Full name
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              required
              class="mt-1 block w-full rounded-lg border-slate-300 bg-slate-50 px-3 py-2 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-100 shadow-sm focus:ring-blue-500 focus:border-blue-500"
              placeholder="John Doe"
            >
            <p v-if="errors.name" class="mt-1 text-sm text-red-600">
              {{ errors.name }}
            </p>
          </div>

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
            >
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
              autocomplete="new-password"
              required
              class="mt-1 block w-full rounded-lg border-slate-300 bg-slate-50 px-3 py-2 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-100 shadow-sm focus:ring-blue-500 focus:border-blue-500"
              placeholder="••••••••"
            >
            <p v-if="errors.password" class="mt-1 text-sm text-red-600">
              {{ errors.password }}
            </p>
          </div>

          <!-- Confirm Password -->
          <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
              Confirm password
            </label>
            <input
              id="password_confirmation"
              v-model="form.password_confirmation"
              type="password"
              autocomplete="new-password"
              required
              class="mt-1 block w-full rounded-lg border-slate-300 bg-slate-50 px-3 py-2 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-100 shadow-sm focus:ring-blue-500 focus:border-blue-500"
              placeholder="••••••••"
            >
            <p v-if="errors.password_confirmation" class="mt-1 text-sm text-red-600">
              {{ errors.password_confirmation }}
            </p>
          </div>
        </div>

        <!-- Password Requirements -->
        <div class="mt-4 p-4 bg-blue-50 dark:bg-slate-800 rounded-lg">
          <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
            Password requirements:
          </h3>
          <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-1">
            <li class="flex items-start">
              <svg class="h-4 w-4 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-8 0-8-8 8 4-4 4-4 4-1.707 1.293 2 2a2 2 0 00-8 8v8a2 2 0 0014.4 8 8 004 4 4 4 4 1.707 1.293 2 2a2 2 0 008-2 8v8a2 2 0 012-2v12a2 2 0 00-2 2 2a2 2 0 014-2v12a2 2 0 024-2v6z" />
              </svg>
              Minimum 12 characters
            </li>
            <li class="flex items-start">
              <svg class="h-4 w-4 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-8 0-8-8 8 4-4 4-4 4-1.707 1.293 2 2a2 2 0 00-8 8v8a2 2 0 0014.4 8 8 004 4 4 4 1.707 1.293 2 2a2 2 0 008-2 8v8a2 2 0 012-2v12a2 2 0 00-2 2 2a2 2 0 014-2v12a2 2 0 024-2v6z" />
              </svg>
              At least one uppercase letter
            </li>
            <li class="flex items-start">
              <svg class="h-4 w-4 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-8 0-8-8 8 4-4 4-4 4-1.707 1.293 2 2a2 2 0 00-8 8v8a2 2 0 0014.4 8 8 004 4 4 4 1.707 1.293 2 2a2 2 0 008-2 8v8a2 2 0 012-2v12a2 2 0 00-2 2 2a2 2 0 014-2v12a2 2 0 024-2v6z" />
              </svg>
              At least one lowercase letter
            </li>
            <li class="flex items-start">
              <svg class="h-4 w-4 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-8 0-8-8 8 4-4 4-4 4-1.707 1.293 2 2a2 2 0 00-8 8v8a2 2 0 0014.4 8 8 004 4 4 4 1.707 1.293 2 2a2 2 0 008-2 8v8a2 2 0 012-2v12a2 2 0 00-2 2 2a2 2 0 014-2v12a2 2 0 024-2v6z" />
              </svg>
              At least one number
            </li>
            <li class="flex items-start">
              <svg class="h-4 w-4 text-green-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-8 0-8-8 8 4-4 4-4 4-1.707 1.293 2 2a2 2 0 00-8 8v8a2 2 0 0014.4 8 8 004 4 4 4 1.707 1.293 2 2a2 2 0 008-2 8v8a2 2 0 012-2v12a2 2 0 00-2 2 2a2 2 0 014-2v12a2 2 0 024-2v6z" />
              </svg>
              At least one special character
            </li>
          </ul>
        </div>

        <!-- Submit Button -->
        <div>
          <button
            type="submit"
            :disabled="isLoading"
            class="flex w-full justify-center rounded-lg bg-blue-600 px-3 py-3 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="isLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 00-8 8v8a8 8 0 0014.4 8 8 004 4 4 4 1.707 1.293 2 2a2 2 0 008-2 8v8a2 2 0 012-2v12a2 2 0 00-2 2 2a2 2 0 014-2v12a2 2 0 024-2v6z"/>
            </svg>
            <span v-else>Create account</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useAuth } from '~/composables/useAuth'

definePageMeta({
  layout: 'auth',
})

const { register, isLoading } = useAuth()

const form = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const errors = ref<Record<string, string>>({})

const handleRegister = async () => {
  errors.value = {}

  try {
    await register({
      name: form.name,
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation,
    })
  } catch (err) {
    const error = err as { response?: { _data?: { error?: { message?: string; errors?: Record<string, string[]> } } } }
    const errorData = error.response?._data as { error?: { message?: string; errors?: Record<string, string[]> } }

    if (errorData?.error?.errors) {
      errors.value = errorData.error.errors
    } else if (errorData?.error?.message) {
      errors.value = { _form: errorData.error.message }
    }
  }
}
</script>
