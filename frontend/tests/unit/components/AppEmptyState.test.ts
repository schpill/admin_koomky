import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import AppEmptyState from '~/components/AppEmptyState.vue'

describe('AppEmptyState', () => {
  it('renders title and description', () => {
    const wrapper = mount(AppEmptyState, {
      props: {
        title: 'Nothing here',
        description: 'Try again later',
      },
    })

    expect(wrapper.text()).toContain('Nothing here')
    expect(wrapper.text()).toContain('Try again later')
  })

  it('renders action slot', () => {
    const wrapper = mount(AppEmptyState, {
      slots: {
        action: '<button>Retry</button>',
      },
    })

    expect(wrapper.text()).toContain('Retry')
  })
})
