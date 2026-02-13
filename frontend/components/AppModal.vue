<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
      >
        <div class="flex min-h-screen items-center justify-center p-4">
          <!-- Overlay -->
          <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
          >
            <div
              v-if="isOpen"
              class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"
              @click="close"
            />
          </Transition>

          <!-- Modal panel -->
          <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <div
              v-if="isOpen"
              class="relative transform overflow-hidden rounded-lg bg-white dark:bg-gray-800 shadow-xl transition-all sm:my-8 sm:w-full"
              :class="[sizeClasses[size]]"
            >
              <!-- Header -->
              <div v-if="$slots.header || title" class="border-b border-gray-200 dark:border-gray-700 px-4 py-3 sm:px-6">
                <div class="flex items-center justify-between">
                  <h3 v-if="title" id="modal-title" class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ title }}
                  </h3>
                  <slot name="header" />
                  <button
                    v-if="!hideCloseButton"
                    type="button"
                    class="ml-4 text-gray-400 hover:text-gray-500 focus:outline-none"
                    @click="close"
                  >
                    <span class="sr-only">Close</span>
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Body -->
              <div class="px-4 py-4 sm:px-6">
                <slot />
              </div>

              <!-- Footer -->
              <div v-if="$slots.footer" class="border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <slot name="footer" />
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { watch } from 'vue'

type ModalSize = 'sm' | 'md' | 'lg' | 'xl' | 'full'

interface Props {
  isOpen: boolean
  title?: string
  size?: ModalSize
  hideCloseButton?: boolean
  closeOnEscape?: boolean
  closeOnOverlay?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  title: '',
  size: 'md',
  hideCloseButton: false,
  closeOnEscape: true,
  closeOnOverlay: true,
})

const emit = defineEmits<{
  'update:isOpen': [value: boolean]
  close: []
}>()

const sizeClasses: Record<ModalSize, string> = {
  sm: 'sm:max-w-sm',
  md: 'sm:max-w-md',
  lg: 'sm:max-w-lg',
  xl: 'sm:max-w-xl',
  full: 'sm:max-w-full sm:m-4',
}

const close = () => {
  emit('update:isOpen', false)
  emit('close')
}

// Handle escape key
watch(() => props.isOpen, (isOpen) => {
  if (isOpen && props.closeOnEscape) {
    const handleEscape = (e: KeyboardEvent) => {
      if (e.key === 'Escape') close()
    }
    document.addEventListener('keydown', handleEscape)
    return () => document.removeEventListener('keydown', handleEscape)
  }
})
</script>
