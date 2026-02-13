import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import AppTextarea from '~/components/AppTextarea.vue'

describe('AppTextarea', () => {
  it('renders label and required indicator', () => {
    const wrapper = mount(AppTextarea, {
      props: {
        label: 'Notes',
        required: true,
        modelValue: '',
      },
    })

    expect(wrapper.text()).toContain('Notes')
    expect(wrapper.text()).toContain('*')
  })

  it('emits update, blur, and focus events', async () => {
    const wrapper = mount(AppTextarea, {
      props: {
        modelValue: '',
      },
    })

    const textarea = wrapper.find('textarea')
    await textarea.setValue('Hello')
    await textarea.trigger('focus')
    await textarea.trigger('blur')

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['Hello'])
    expect(wrapper.emitted('focus')).toBeTruthy()
    expect(wrapper.emitted('blur')).toBeTruthy()
  })

  it('shows character counter when enabled', () => {
    const wrapper = mount(AppTextarea, {
      props: {
        modelValue: '1234',
        maxlength: 10,
        showCounter: true,
      },
    })

    expect(wrapper.text()).toContain('4/10')
  })
})
