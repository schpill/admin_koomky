<template>
  <div class="relative">
    <label
      v-if="label"
      :for="id"
      class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
    >
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>

    <select
      :id="id"
      :value="modelValue"
      :required="required"
      :disabled="disabled"
      :class="[
        'block w-full rounded-lg border shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm',
        'bg-white dark:bg-gray-800 text-gray-900 dark:text-white',
        'border-gray-300 dark:border-gray-600',
        disabled ? 'opacity-50 cursor-not-allowed' : '',
        error ? 'border-red-300 dark:border-red-600 focus:border-red-500 focus:ring-red-500' : '',
        customClass
      ]"
      @change="handleChange"
    >
      <option v-if="placeholder" value="" disabled>{{ placeholder }}</option>
      <option
        v-for="option in options"
        :key="getOptionValue(option)"
        :value="getOptionValue(option)"
        :disabled="getOptionDisabled(option)"
      >
        {{ getOptionLabel(option) }}
      </option>
    </select>

    <p v-if="hint && !error" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
      {{ hint }}
    </p>
    <p v-if="error" class="mt-1 text-sm text-red-600 dark:text-red-400">
      {{ error }}
    </p>
  </div>
</template>

<script setup lang="ts">
interface Option {
  label: string
  value: string | number
  disabled?: boolean
}

interface Props {
  id?: string
  label?: string
  modelValue?: string | number | null
  options: (Option | string | number)[]
  placeholder?: string
  required?: boolean
  disabled?: boolean
  hint?: string
  error?: string
  customClass?: string
}

const _props = withDefaults(defineProps<Props>(), {
  id: '',
  label: '',
  modelValue: null,
  placeholder: 'Select an option',
  required: false,
  disabled: false,
  hint: '',
  error: '',
  customClass: '',
})

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
}>()

const handleChange = (event: Event) => {
  const target = event.target as HTMLSelectElement
  emit('update:modelValue', target.value)
}

const getOptionValue = (option: Option | string | number) => {
  if (typeof option === 'object') return option.value
  return option
}

const getOptionLabel = (option: Option | string | number) => {
  if (typeof option === 'object') return option.label
  return option
}

const getOptionDisabled = (option: Option | string | number) => {
  if (typeof option === 'object') return option.disabled || false
  return false
}
</script>
