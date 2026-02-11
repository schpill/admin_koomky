import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import AppButton from '~/components/AppButton.vue'

describe('AppButton', () => {
  it('renders button text', () => {
    const wrapper = mount(AppButton, {
      slots: { default: 'Click me' }
    })
    expect(wrapper.text()).toContain('Click me')
  })

  it('applies primary variant classes', () => {
    const wrapper = mount(AppButton, {
      props: { variant: 'primary' }
    })
    expect(wrapper.classes()).toContain('bg-blue-600')
  })

  it('applies danger variant classes', () => {
    const wrapper = mount(AppButton, {
      props: { variant: 'danger' }
    })
    expect(wrapper.classes()).toContain('bg-red-600')
  })

  it('shows loading spinner when loading', async () => {
    const wrapper = mount(AppButton, {
      props: { loading: false }
    })

    await wrapper.setProps({ loading: true })
    expect(wrapper.find('.animate-spin').exists()).toBe(true)
  })

  it('is disabled when loading', () => {
    const wrapper = mount(AppButton, {
      props: { loading: true }
    })
    expect(wrapper.find('button').attributes('disabled')).toBeDefined()
  })

  it('emits click event', async () => {
    const wrapper = mount(AppButton)
    await wrapper.find('button').trigger('click')
    expect(wrapper.emitted('click')).toBeTruthy()
  })
})
