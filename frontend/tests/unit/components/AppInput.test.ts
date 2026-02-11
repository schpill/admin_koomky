import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import AppInput from '~/components/AppInput.vue'

describe('AppInput', () => {
  it('renders input with label', () => {
    const wrapper = mount(AppInput, {
      props: {
        label: 'Email',
        modelValue: ''
      }
    })
    expect(wrapper.text()).toContain('Email')
  })

  it('shows required asterisk when required', () => {
    const wrapper = mount(AppInput, {
      props: {
        label: 'Email',
        modelValue: '',
        required: true
      }
    })
    expect(wrapper.text()).toContain('*')
  })

  it('displays error message', () => {
    const wrapper = mount(AppInput, {
      props: {
        modelValue: '',
        error: 'This field is required'
      }
    })
    expect(wrapper.text()).toContain('This field is required')
  })

  it('emits update:modelValue on input', async () => {
    const wrapper = mount(AppInput, {
      props: {
        modelValue: ''
      }
    })
    const input = wrapper.find('input')
    await input.setValue('test@example.com')
    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['test@example.com'])
  })

  it('applies custom class', () => {
    const wrapper = mount(AppInput, {
      props: {
        modelValue: '',
        customClass: 'custom-input-class'
      }
    })
    expect(wrapper.find('input').classes()).toContain('custom-input-class')
  })
})
