import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import AppDataTable from '~/components/AppDataTable.vue'
import AppEmptyState from '~/components/AppEmptyState.vue'

describe('AppDataTable', () => {
  it('renders column headers and data values', () => {
    const wrapper = mount(AppDataTable, {
      props: {
        columns: [
          { key: 'name', label: 'Name' },
          { key: 'meta.count', label: 'Count' },
        ],
        data: [
          { id: 1, name: 'Acme', meta: { count: 2 } },
        ],
      },
      global: {
        stubs: { AppPagination: true },
      },
    })

    expect(wrapper.text()).toContain('Name')
    expect(wrapper.text()).toContain('Count')
    expect(wrapper.text()).toContain('Acme')
    expect(wrapper.text()).toContain('2')
  })

  it('emits sort when clicking sortable header', async () => {
    const wrapper = mount(AppDataTable, {
      props: {
        columns: [
          { key: 'name', label: 'Name', sortable: true },
        ],
        data: [
          { id: 1, name: 'Acme' },
        ],
        sortColumn: 'name',
        sortOrder: 'asc',
      },
      global: {
        stubs: { AppPagination: true, AppEmptyState: true },
      },
    })

    const header = wrapper.findAll('th').find(th => th.text().includes('Name'))
    await header?.trigger('click')

    expect(wrapper.emitted('sort')?.[0]).toEqual(['name', 'desc'])
  })

  it('shows empty state when data is empty', () => {
    const wrapper = mount(AppDataTable, {
      props: {
        columns: [{ key: 'name', label: 'Name' }],
        data: [],
        emptyTitle: 'No data',
      },
      global: {
        components: { AppEmptyState },
        stubs: { AppPagination: true },
      },
    })

    expect(wrapper.text()).toContain('No data')
  })

  it('renders pagination when enabled and multiple pages', () => {
    const wrapper = mount(AppDataTable, {
      props: {
        columns: [{ key: 'name', label: 'Name' }],
        data: [{ id: 1, name: 'Acme' }],
        showPagination: true,
        totalPages: 2,
        totalItems: 2,
        perPage: 1,
        currentPage: 1,
      },
      global: {
        stubs: { AppPagination: true, AppEmptyState: true },
      },
    })

    const pagination = wrapper.findComponent({ name: 'AppPagination' })
    expect(pagination.exists()).toBe(true)
  })
})
