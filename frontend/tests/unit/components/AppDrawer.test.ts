import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { nextTick } from 'vue'
import AppDrawer from '~/components/AppDrawer.vue'

describe('AppDrawer', () => {
  it('emits close events on overlay click', async () => {
    const wrapper = mount(AppDrawer, {
      props: {
        isOpen: true,
      },
      global: {
        stubs: { Teleport: true, Transition: false },
      },
    })

    const overlay = wrapper.find('div.bg-gray-900\\/50')
    await overlay.trigger('click')

    expect(wrapper.emitted('update:isOpen')?.[0]).toEqual([false])
    expect(wrapper.emitted('close')).toBeTruthy()
  })

  it('closes on Escape key when open', async () => {
    const wrapper = mount(AppDrawer, {
      props: {
        isOpen: false,
        closeOnEscape: true,
      },
      global: {
        stubs: { Teleport: true, Transition: false },
      },
    })

    await wrapper.setProps({ isOpen: true })
    await nextTick()

    document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }))
    await nextTick()

    expect(wrapper.emitted('update:isOpen')?.[0]).toEqual([false])
  })
})
