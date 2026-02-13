<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[
      'inline-flex items-center justify-center px-4 py-2 border border rounded-lg font-medium shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed',
      variant === 'primary' && 'bg-blue-600 border-transparent text-white hover:bg-blue-700 focus:ring-blue-500',
      variant === 'secondary' && 'bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 focus:ring-slate-500',
      variant === 'danger' && 'bg-red-600 border-transparent text-white hover:bg-red-700 focus:ring-red-500',
      variant === 'ghost' && 'bg-transparent border-transparent text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700',
    ]"
    v-bind="$attrs"
  >
    <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24">
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
    </svg>

    <slot v-else />
  </button>
</template>

<script setup lang="ts">
interface Props {
  variant?: 'primary' | 'secondary' | 'danger' | 'ghost'
  type?: 'button' | 'submit' | 'reset'
  disabled?: boolean
  loading?: boolean
}

withDefaults(defineProps<Props>(), {
  variant: 'primary',
  type: 'button',
  disabled: false,
  loading: false,
})
</script>
