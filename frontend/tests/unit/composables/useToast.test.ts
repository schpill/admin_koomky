import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { nextTick } from 'vue'

const loadUseToast = async () => {
  const mod = await import('~/composables/useToast')
  return mod.useToast
}

describe('useToast', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    vi.resetModules()
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('adds and removes toasts', async () => {
    const useToast = await loadUseToast()
    const { toasts, add, remove, clear } = useToast()

    clear()
    add('success', 'Saved', 0)

    expect(toasts.value).toHaveLength(1)
    const id = toasts.value[0].id

    remove(id)
    expect(toasts.value).toHaveLength(0)
  })

  it('auto-removes toasts after timeout', async () => {
    const useToast = await loadUseToast()
    const { toasts, add, clear } = useToast()

    clear()
    add('info', 'Hello', 10)
    expect(toasts.value).toHaveLength(1)

    vi.advanceTimersByTime(10)
    await nextTick()

    expect(toasts.value).toHaveLength(0)
  })

  it('adds toast types via helpers', async () => {
    const useToast = await loadUseToast()
    const { toasts, success, error, warning, info, clear } = useToast()

    clear()
    success('Ok', 0)
    error('Fail', 0)
    warning('Warn', 0)
    info('Info', 0)

    expect(toasts.value.map(t => t.type)).toEqual(['success', 'error', 'warning', 'info'])
  })

  it('clears all toasts', async () => {
    const useToast = await loadUseToast()
    const { toasts, add, clear } = useToast()

    add('success', 'One', 0)
    add('error', 'Two', 0)
    expect(toasts.value).toHaveLength(2)

    clear()
    expect(toasts.value).toHaveLength(0)
  })
})
