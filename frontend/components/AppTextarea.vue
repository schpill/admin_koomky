<template>
  <div>
    <label
      v-if="label"
      :for="id"
      class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
    >
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <textarea
      :id="id"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :readonly="readonly"
      :rows="rows"
      :maxlength="maxlength"
      :class="[
        'block w-full rounded-lg border shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
        'bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500',
        'border-gray-300 dark:border-gray-600',
        disabled ? 'opacity-50 cursor-not-allowed' : '',
        readonly ? 'bg-gray-50 dark:bg-gray-900' : '',
        error ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500' : '',
        resize !== 'none' ? resize : '',
        customClass
      ]"
      @input="handleInput"
      @blur="handleBlur"
      @focus="handleFocus"
    />

    <div class="mt-1 flex items-center justify-between">
      <p v-if="hint && !error" class="text-sm text-gray-500 dark:text-gray-400">
        {{ hint }}
      </p>
      <p v-if="error" class="text-sm text-red-600 dark:text-red-400">
        {{ error }}
      </p>
      <p v-if="maxlength && showCounter" class="text-sm text-gray-400 dark:text-gray-500 ml-auto">
        {{ characterCount }}/{{ maxlength }}
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  id?: string
  label?: string
  modelValue?: string
  placeholder?: string
  required?: boolean
  disabled?: boolean
  readonly?: boolean
  rows?: number
  maxlength?: number
  showCounter?: boolean
  hint?: string
  error?: string
  resize?: 'none' | 'both' | 'horizontal' | 'vertical'
  customClass?: string
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: '',
  required: false,
  disabled: false,
  readonly: false,
  rows: 3,
  showCounter: false,
  resize: 'vertical',
  customClass: '',
})

const emit = defineEmits<{
  'update:modelValue': [value: string]
  blur: [event: FocusEvent]
  focus: [event: FocusEvent]
}>()

const handleInput = (event: Event) => {
  const target = event.target as HTMLTextAreaElement
  emit('update:modelValue', target.value)
}

const handleBlur = (event: FocusEvent) => {
  emit('blur', event)
}

const handleFocus = (event: FocusEvent) => {
  emit('focus', event)
}

const characterCount = computed(() => {
  return props.modelValue?.length || 0
})
</script>
