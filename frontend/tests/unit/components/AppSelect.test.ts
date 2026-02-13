import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import AppSelect from '~/components/AppSelect.vue'

describe('AppSelect', () => {
  it('renders label and required indicator', () => {
    const wrapper = mount(AppSelect, {
      props: {
        label: 'Status',
        required: true,
        options: ['Open', 'Closed'],
        modelValue: null,
      },
    })

    expect(wrapper.text()).toContain('Status')
    expect(wrapper.text()).toContain('*')
  })

  it('renders placeholder option', () => {
    const wrapper = mount(AppSelect, {
      props: {
        options: ['A', 'B'],
        modelValue: null,
        placeholder: 'Pick one',
      },
    })

    const options = wrapper.findAll('option')
    expect(options[0].text()).toBe('Pick one')
    expect(options[0].attributes('disabled')).toBeDefined()
  })

  it('renders option labels and disabled state', () => {
    const wrapper = mount(AppSelect, {
      props: {
        options: [
          { label: 'One', value: '1' },
          { label: 'Two', value: '2', disabled: true },
          'Three',
        ],
        modelValue: null,
      },
    })

    const options = wrapper.findAll('option')
    expect(options.some(o => o.text() === 'One')).toBe(true)
    expect(options.some(o => o.text() === 'Two')).toBe(true)
    expect(options.some(o => o.text() === 'Three')).toBe(true)

    const disabledOption = options.find(o => o.text() === 'Two')
    expect(disabledOption?.attributes('disabled')).toBeDefined()
  })

  it('emits update:modelValue on change', async () => {
    const wrapper = mount(AppSelect, {
      props: {
        options: ['One', 'Two'],
        modelValue: null,
      },
    })

    const select = wrapper.find('select')
    await select.setValue('Two')

    expect(wrapper.emitted('update:modelValue')?.[0]).toEqual(['Two'])
  })
})
