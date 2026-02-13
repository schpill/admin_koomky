import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import CommandPalette from '~/components/CommandPalette.vue'

vi.mock('~/composables/useAuth', () => ({
  useAuth: () => ({ logout: vi.fn() }),
}))

describe('CommandPalette', () => {
  beforeEach(() => {
    vi.stubGlobal('navigateTo', vi.fn())
  })

  it('renders default commands when open', () => {
    const wrapper = mount(CommandPalette, {
      props: { isOpen: true },
      global: {
        stubs: { Teleport: true, Transition: false },
      },
    })

    expect(wrapper.text()).toContain('View Clients')
    expect(wrapper.text()).toContain('Go to Dashboard')
  })

  it('filters commands by search query', async () => {
    const wrapper = mount(CommandPalette, {
      props: { isOpen: true },
      global: {
        stubs: { Teleport: true, Transition: false },
      },
    })

    const input = wrapper.find('input')
    await input.setValue('settings')

    expect(wrapper.text()).toContain('Settings')
    expect(wrapper.text()).not.toContain('View Clients')
  })

  it('selects a command and closes', async () => {
    const wrapper = mount(CommandPalette, {
      props: { isOpen: true },
      global: {
        stubs: { Teleport: true, Transition: false },
      },
    })

    const clientsButton = wrapper.findAll('button').find(btn => btn.text().includes('View Clients'))
    await clientsButton?.trigger('click')

    expect((globalThis as unknown as Record<string, unknown>).navigateTo).toHaveBeenCalledWith('/clients')
    expect(wrapper.emitted('update:isOpen')?.[0]).toEqual([false])
  })

  it('toggles open state with Ctrl+K', () => {
    const wrapper = mount(CommandPalette, {
      props: { isOpen: false },
      global: {
        stubs: { Teleport: true, Transition: false },
      },
    })

    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'k', ctrlKey: true }))
    expect(wrapper.emitted('update:isOpen')?.[0]).toEqual([true])
  })
})
