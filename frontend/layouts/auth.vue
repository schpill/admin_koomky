<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-900 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">
      <slot />
    </div>

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
          }
        ]"
      >
        <div class="flex">
          <div class="flex-shrink-0">
            <svg v-if="toast.type === 'success'" class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-8 0-8-8 8 4-4 4-4 4-1.707 1.293 2 2a2 2 0 011.815 2 2 015.186 4 4-4 4-4-1v2z" clip-rule="evenodd" />
            </svg>
            <svg v-else-if="toast.type === 'error'" class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-8 0-8-8 8 4-4 4-4 4-1.707 1.293 2 2a2 2 0 011.815 2 2 015.186 4 4-4 2.529 2 2a2 2 0 011.815 2 2 015.186 4 4-4 4-4-2.529 2 2z" clip-rule="evenodd" />
            </svg>
          </div>
          <p class="ml-3 text-sm font-medium">{{ toast.message }}</p>
        </div>
        <button
          class="ml-4 flex-shrink-0 inline-flex text-slate-400 hover:text-slate-500"
          @click="removeToast(toast.id)"
        >
          <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4.293 4.707a4 4 0 00-4 4 4.707 2.293 2 2a2 2 0 00-2 4.586 2.293-2 2.415-2.415-2.415 2.415c0 2 0-1.065 1.065l-2.293 2.293-2.586 2.293-2.586c-1.065-1.065 2-2.15-2.15 1.122 0 1.122 0 1.22 1.22 1.065 1.065s-.811.846-1.415-1.415-2.463 2.96-1.122 0-1.065-1.065-1.415-1.415-2.15-2.15 1.122-0 1.22c0 2 0-2.15-2.15-.811.846-1.415-1.415-2.463-2.96-.811.846-1.415-1.415z" clip-rule="evenodd" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useToast } from '~/composables/useToast'

const { toasts, remove: removeToast } = useToast()
</script>
