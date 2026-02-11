<template>
  <div
    :class="[
      'bg-white dark:bg-gray-800 rounded-lg shadow-sm',
      hoverable ? 'hover:shadow-md transition-shadow duration-200' : '',
      padding ? 'p-6' : '',
      customClass
    ]"
  >
    <!-- Header -->
    <div v-if="$slots.header || title" class="mb-4">
      <div class="flex items-center justify-between">
        <div>
          <h3 v-if="title" class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ title }}
          </h3>
          <p v-if="subtitle" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ subtitle }}
          </p>
        </div>
        <slot name="header" />
      </div>
    </div>

    <!-- Body -->
    <slot />

    <!-- Footer -->
    <div v-if="$slots.footer" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup lang="ts">
interface Props {
  title?: string
  subtitle?: string
  hoverable?: boolean
  padding?: boolean
  customClass?: string
}

withDefaults(defineProps<Props>(), {
  hoverable: false,
  padding: true,
  customClass: '',
})
</script>
