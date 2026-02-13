import { ref, readonly } from 'vue'

export type ToastType = 'success' | 'error' | 'warning' | 'info'

interface Toast {
  id: string
  type: ToastType
  message: string
  timeout?: number
}

const toasts = ref<Toast[]>([])
let id = 0

export function useToast() {
  const add = (type: ToastType, message: string, timeout = 5000) => {
    const toast: Toast = {
      id: `toast-${id++}`,
      type,
      message,
      timeout,
    }

    toasts.value.push(toast)

    if (timeout) {
      setTimeout(() => {
        remove(toast.id)
      }, timeout)
    }
  }

  const remove = (id: string) => {
    const index = toasts.value.findIndex(t => t.id === id)
    if (index !== -1) {
      toasts.value.splice(index, 1)
    }
  }

  const success = (message: string, timeout?: number) => {
    add('success', message, timeout)
  }

  const error = (message: string, timeout?: number) => {
    add('error', message, timeout)
  }

  const warning = (message: string, timeout?: number) => {
    add('warning', message, timeout)
  }

  const info = (message: string, timeout?: number) => {
    add('info', message, timeout)
  }

  const clear = () => {
    toasts.value = []
  }

  return {
    toasts: readonly(toasts),
    add,
    remove,
    success,
    error,
    warning,
    info,
    clear,
  }
}
