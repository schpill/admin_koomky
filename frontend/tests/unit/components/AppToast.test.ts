import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import AppToast from '~/components/AppToast.vue'

describe('AppToast', () => {
  it('renders toasts and emits remove on close', async () => {
    const wrapper = mount(AppToast, {
      props: {
        toasts: [
          { id: 't1', type: 'success', message: 'Saved' },
        ],
      },
      global: {
        stubs: { Teleport: true, TransitionGroup: false },
      },
    })

    expect(wrapper.text()).toContain('Saved')
    expect(wrapper.find('[role="alert"]').classes()).toContain('bg-green-100')

    const closeButton = wrapper.find('button')
    await closeButton.trigger('click')

    expect(wrapper.emitted('remove')?.[0]).toEqual(['t1'])
  })
})
