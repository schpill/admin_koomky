import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import AppPagination from '~/components/AppPagination.vue'

describe('AppPagination', () => {
  it('shows the correct item range', () => {
    const wrapper = mount(AppPagination, {
      props: {
        currentPage: 2,
        totalPages: 5,
        totalItems: 35,
        perPage: 10,
      },
    })

    expect(wrapper.text()).toContain('Showing')
    expect(wrapper.text()).toContain('11')
    expect(wrapper.text()).toContain('20')
    expect(wrapper.text()).toContain('35')
  })

  it('renders ellipsis when many pages', () => {
    const wrapper = mount(AppPagination, {
      props: {
        currentPage: 5,
        totalPages: 10,
        totalItems: 100,
        perPage: 10,
      },
    })

    expect(wrapper.text()).toContain('...')
    expect(wrapper.text()).toContain('5')
    expect(wrapper.text()).toContain('10')
  })

  it('emits page-change on previous and next', async () => {
    const wrapper = mount(AppPagination, {
      props: {
        currentPage: 2,
        totalPages: 3,
        totalItems: 30,
        perPage: 10,
      },
    })

    const previous = wrapper.findAll('button').find(btn => btn.text() === 'Previous')
    const next = wrapper.findAll('button').find(btn => btn.text() === 'Next')

    await previous?.trigger('click')
    await next?.trigger('click')

    expect(wrapper.emitted('page-change')?.[0]).toEqual([1])
    expect(wrapper.emitted('page-change')?.[1]).toEqual([3])
  })

  it('emits page-change when clicking a page number', async () => {
    const wrapper = mount(AppPagination, {
      props: {
        currentPage: 1,
        totalPages: 4,
        totalItems: 40,
        perPage: 10,
      },
    })

    const pageThree = wrapper.findAll('button').find(btn => btn.text() === '3')
    await pageThree?.trigger('click')

    expect(wrapper.emitted('page-change')?.[0]).toEqual([3])
  })
})
