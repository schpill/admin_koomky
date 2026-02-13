import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import AppCard from '~/components/AppCard.vue'

describe('AppCard', () => {
  it('renders title, subtitle, and slots', () => {
    const wrapper = mount(AppCard, {
      props: {
        title: 'Card Title',
        subtitle: 'Card Subtitle',
      },
      slots: {
        default: '<div>Body</div>',
        footer: '<div>Footer</div>',
      },
    })

    expect(wrapper.text()).toContain('Card Title')
    expect(wrapper.text()).toContain('Card Subtitle')
    expect(wrapper.text()).toContain('Body')
    expect(wrapper.text()).toContain('Footer')
  })

  it('applies hover and padding classes', () => {
    const wrapper = mount(AppCard, {
      props: {
        hoverable: true,
        padding: true,
        customClass: 'custom-card',
      },
    })

    const classes = wrapper.classes().join(' ')
    expect(classes).toContain('hover:shadow-md')
    expect(classes).toContain('p-6')
    expect(classes).toContain('custom-card')
  })
})
