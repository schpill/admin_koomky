<template>
  <div class="relative">
    <label v-if="label" :for="id" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
      {{ label }}
      <span v-if="required" class="text-red-500">*</span>
    </label>
    <input
      :id="id"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :required="required"
      @input="onInput"
      @change="onChange"
      :class="[
        'block w-full rounded-lg border-slate-300 bg-slate-50 px-3 py-2 shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm',
        error && 'border-red-300 focus:border-red-500 focus:ring-red-500',
        customClass
      ]"
    />
    <p v-if="error" class="mt-1 text-sm text-red-600">
      {{ error }}
    </p>
  </div>
</template>

<script setup lang="ts">
interface Props {
  id?: string
  type?: 'text' | 'email' | 'password' | 'number'
  label?: string
  placeholder?: string
  modelValue: string | number
  error?: string
  disabled?: boolean
  required?: boolean
  customClass?: string
}

defineProps<Props>()

const emit = defineEmits<{
  'update:modelValue': [value: string | number]
}>()

const onInput = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value)
}

const onChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  emit('update:modelValue', target.value)
}
</script>
