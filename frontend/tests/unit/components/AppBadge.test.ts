import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import AppBadge from '~/components/AppBadge.vue'

describe('AppBadge', () => {
  it('renders text prop', () => {
    const wrapper = mount(AppBadge, {
      props: { text: 'New' },
    })

    expect(wrapper.text()).toContain('New')
  })

  it('applies variant and size classes', () => {
    const wrapper = mount(AppBadge, {
      props: { variant: 'success', size: 'lg' },
      slots: { default: 'Success' },
    })

    const classes = wrapper.classes().join(' ')
    expect(classes).toContain('bg-green-100')
    expect(classes).toContain('text-sm')
  })
})
