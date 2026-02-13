<template>
  <span
    :class="[
      'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
      variantClasses[variant],
      sizeClasses[size]
    ]"
  >
    <slot>{{ text }}</slot>
  </span>
</template>

<script setup lang="ts">
interface Props {
  text?: string
  variant?: 'success' | 'warning' | 'error' | 'info' | 'default'
  size?: 'sm' | 'md' | 'lg'
}

const _props = withDefaults(defineProps<Props>(), {
  text: '',
  variant: 'default',
  size: 'md',
})

const variantClasses: Record<string, string> = {
  success: 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-200',
  warning: 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-200',
  error: 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-200',
  info: 'bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-200',
  default: 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200',
}

const sizeClasses: Record<string, string> = {
  sm: 'px-2 py-0.5 text-xs',
  md: 'px-2.5 py-0.5 text-xs',
  lg: 'px-3 py-1 text-sm',
}
</script>
