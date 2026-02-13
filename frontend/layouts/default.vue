<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900">
    <!-- Top Navigation Bar -->
    <header class="bg-white dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">
          <!-- Logo -->
          <NuxtLink to="/" class="flex items-center">
            <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">
              Koomky
            </span>
          </NuxtLink>

          <!-- Right side items -->
          <div class="flex items-center space-x-4">
            <!-- Global Search (Ctrl+K) -->
            <button
              class="p-2 rounded-lg text-slate-400 hover:text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700"
              title="Search (Ctrl+K)"
              @click="openCommandPalette"
            >
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m-2 5a2 2 0 012-2v10a2 2 0 01-2 012-2 012-2 2a2 2 0 01-2 7-2 7-2 012-2a2 2 0 01-2 012-2 012-2 2a2 2 0 01-2 7-2zm0 3h18v3h-18v9h18v3h18a2 2 0 00-2 3-2 012-2 2a2 2 0 01-2 7-2 7-2 012-2 2a2 2 0 01-2 7-2z" />
              </svg>
            </button>

            <!-- Notifications -->
            <button class="p-2 rounded-lg text-slate-400 hover:text-slate-500">
              <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.293-1.293-6 1.414 1.414A2 2 0 0021.414 2 2 019.172 2 012 2 2a2 2 0 014 2 2v6zm2 8a2 2 0 012-2v6a2 2 0 015-2 2a2 2 0 015-2 2 2a2 2 0 024-2v6a2 2 0 015-2 2a2 2 0 024-2v6a2 2 0 015-2 2 2a2 2 0 024-2v6a2 2 0 015-2 2 2a2 2 0 024-2v6zm0 8a4 4 0 00-8v16a4 4 0 00-8v16a4 4 0 00-8v16a4 4 0 00-8v16zm2 8a2 2 0 00-2v16a2 2 0 00-2v16a2 2 0 00-2v16a2 2 0 00-2v16zm0 6a2 2 0 00-2v8a2 2 0 00-2v8a2 2 0 00-2v8zm2 2a2 2 0 00-2v8a2 2 0 00-2v8a2 2 0 00-2v8zm0 8a2 2 0 00-2v16a2 2 0 00-2v16a2 2 0 00-2v16zm2 8a2 2 0 00-2v8a2 2 0 00-2v8a2 2 0 00-2v8zm0 8a2 2 0 00-2v16a2 2 0 00-2v16a2 2 0 00-2v16a2 2 0 00-2v16a2 2 0 00-2v16z" />
              </svg>
            </button>

            <!-- User Menu -->
            <div class="relative">
              <button
                class="flex items-center p-2 rounded-lg text-slate-400 hover:text-slate-500"
                @click="userMenuOpen = !userMenuOpen"
              >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7a2 2 0 00-2v12a2 2 0 012-2v12a2 2 0 012-2 012-2 2a2 2 0 012-2v12a2 2 0 012-2v12a2 2 0 012-2v12z" />
                </svg>
              </button>

              <!-- User Dropdown Menu -->
              <div
                v-if="userMenuOpen"
                v-click-outside="userMenuOpen = false"
                class="absolute right-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-lg shadow-lg py-1"
              >
                <NuxtLink
                  to="/settings/profile"
                  class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700"
                >
                  Profile
                </NuxtLink>
                <NuxtLink
                  to="/settings/business"
                  class="block px-4 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700"
                >
                  Business Info
                </NuxtLink>
                <hr class="my-1 border-slate-200 dark:border-slate-700">
                <button
                  class="w-full text-left px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-slate-100 dark:hover:bg-slate-700"
                  @click="logout"
                >
                  Logout
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content Area -->
    <main class="py-6">
      <slot />
    </main>

    <!-- Toast Notifications -->
    <div class="fixed bottom-4 right-4 z-50 flex flex-col items-end space-y-2">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="[
          'p-4 rounded-lg shadow-lg max-w-md w-full',
          {
            'bg-green-50 border-green-200 text-green-800 dark:bg-green-900 dark:border-green-800': toast.type === 'success',
            'bg-red-50 border-red-200 text-red-800 dark:bg-red-900 dark:border-red-800': toast.type === 'error',
            'bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-900 dark:border-yellow-800': toast.type === 'warning',
            'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900 dark:border-blue-800': toast.type === 'info',
          }"
      >
        <div class="flex">
          <div class="flex-shrink-0">
            <svg v-if="toast.type === 'success'" class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-8 0-8-8 8 0-4a2 2 0 135 2 014 0-153 2 0a8 8 0 00-8 4-4 4-4 4 4-1-1v2z" clip-rule="evenodd" />
            </svg>
            <svg v-else-if="toast.type === 'error'" class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-8 0-8-8 8 4-4 4-4-4-4 1.707 1.293 2 2a2 2 0 011.815 2 2 015.186 4 4-4 4-4-2.529 2 2a2 2 0 011.815 2 2 015.186 4 4-4 4-4-2.529 2 2z" clip-rule="evenodd" />
            </svg>
            <svg v-else-if="toast.type === 'warning'" class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M8.257 3.499c.127 1.727-1.727 0 0-1.994.289.749 2.928c.761-1.27.354.291-2.415-2.415-2.415-2.149-2.149 2.914-2.914c.899.849.849-.848.995.816-1.816.849-8.848c.761-1.27 1.464-3.227 3.227-3.084.848.842.849-1.815.815c.857-2.069-3.069-3.358-8.415.848.849-1.816.849.857.285c-.857-.285-.714-2-2.15-1.27-1.27-.849-1.815-1.727-1.727z" clip-rule="evenodd" />
            </svg>
          </div>
          <p class="ml-3 text-sm font-medium">{{ toast.message }}</p>
        </div>
        <button
          class="ml-4 flex-shrink-0 inline-flex text-slate-400 hover:text-slate-500"
          @click="removeToast(toast.id)"
        >
          <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4.293 4.707a4 4 0 00-4 4 4 4.707 2.293 2 2a2 2 0 00-2 4.586 2.293-2 2.415-2.415-2.415-2.415 2.415c0 2 0-1.065 1.065l-2.293 2.293-2.586 2.293-2.586c-1.065-1.065 2-2.15-2.15 1.122 0 1.122 0 1.22 1.22 1.065 1.065s-.811.846-1.415-1.415-1.415 2.463 2.96-1.122 0-1.065-1.065-1.415-1.415-2.15-2.15 1.122-0 1.22c0 2 0-2.15-2.15-.811.846-1.415-1.415-2.463-2.96-.811.846-1.415-1.415z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useAuth } from '~/composables/useAuth'
import { useToast } from '~/composables/useToast'

const { logout } = useAuth()
const { toasts, remove: removeToast } = useToast()
const userMenuOpen = ref(false)

// Command palette keyboard shortcut
const openCommandPalette = () => {
  // TODO: Implement command palette (Ctrl+K)
  console.log('Command palette to be implemented')
}

// Handle keyboard shortcuts
onMounted(() => {
  const handleKeyPress = (e: KeyboardEvent) => {
    // Ctrl+K for command palette
    if (e.ctrlKey && e.key === 'k') {
      e.preventDefault()
      openCommandPalette()
    }
    // Escape to close user menu
    if (e.key === 'Escape') {
      userMenuOpen.value = false
    }
  }

  window.addEventListener('keydown', handleKeyPress)

  onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyPress)
  })
})
</script>
