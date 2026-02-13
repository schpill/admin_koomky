<template>
  <div class="overflow-x-auto">
    <div class="inline-block min-w-full align-middle">
      <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
          <!-- Header -->
          <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
              <th
                v-for="column in columns"
                :key="column.key"
                :class="[
                  'px-3 py-3.5 text-left text-sm font-semibold text-gray-900 dark:text-white',
                  column.sortable ? 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700' : '',
                ]"
                @click="column.sortable ? handleSort(column.key) : null"
              >
                <div class="flex items-center gap-2">
                  {{ column.label }}
                  <span v-if="column.sortable" class="text-gray-400">
                    <svg
                      v-if="sortColumn === column.key && sortOrder === 'asc'"
                      class="h-4 w-4"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                    >
                      <path d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L11 7.414V15a1 1 0 11-2 0V7.414L6.707 9.707a1 1 0 01-1.414 0z" />
                    </svg>
                    <svg
                      v-else-if="sortColumn === column.key && sortOrder === 'desc'"
                      class="h-4 w-4"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                    >
                      <path d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L9 12.586V5a1 1 0 012 0v7.586l2.293-2.293a1 1 0 011.414 0z" />
                    </svg>
                    <svg v-else class="h-4 w-4 opacity-30" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                  </span>
                </div>
              </th>
              <th v-if="$slots.actions" class="relative px-3 py-3.5">
                <span class="sr-only">Actions</span>
              </th>
            </tr>
          </thead>

          <!-- Body -->
          <tbody class="divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-900">
            <tr
              v-for="(item, index) in data"
              :key="getItemKey(item, index)"
              class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
            >
              <td
                v-for="column in columns"
                :key="column.key"
                class="whitespace-nowrap px-3 py-4 text-sm text-gray-900 dark:text-gray-100"
              >
                <slot
                  :name="`column-${column.key}`"
                  :record="item"
                  :value="getItemValue(item, column.key)"
                >
                  {{ getItemValue(item, column.key) }}
                </slot>
              </td>
              <td v-if="$slots.actions" class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                <slot name="actions" :record="item" />
              </td>
            </tr>

            <!-- Empty state -->
            <tr v-if="data.length === 0">
              <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="px-3 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                <AppEmptyState
                  :title="emptyTitle"
                  :description="emptyDescription"
                  :icon="emptyIcon"
                >
                  <template v-if="$slots['empty-action']" #action>
                    <slot name="empty-action" />
                  </template>
                </AppEmptyState>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Loading overlay -->
        <div
          v-if="loading"
          class="absolute inset-0 bg-white/50 dark:bg-gray-900/50 flex items-center justify-center"
        >
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"/>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="showPagination && totalPages > 1" class="mt-4">
      <AppPagination
        :current-page="currentPage"
        :total-pages="totalPages"
        :total-items="totalItems"
        :per-page="perPage"
        @page-change="$emit('page-change', $event)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
interface Column {
  key: string
  label: string
  sortable?: boolean
}

interface Props {
  columns: Column[]
  data: Record<string, unknown>[]
  itemKey?: string
  loading?: boolean
  emptyTitle?: string
  emptyDescription?: string
  emptyIcon?: 'search' | 'users' | 'folder' | 'default'
  showPagination?: boolean
  currentPage?: number
  totalPages?: number
  totalItems?: number
  perPage?: number
}

const props = withDefaults(defineProps<Props>(), {
  itemKey: 'id',
  loading: false,
  emptyTitle: 'No data',
  emptyDescription: 'No items to display',
  emptyIcon: 'default',
  showPagination: false,
  currentPage: 1,
  totalPages: 1,
  totalItems: 0,
  perPage: 10,
})

const emit = defineEmits<{
  sort: [column: string, order: 'asc' | 'desc']
  'page-change': [page: number]
}>()

const sortColumn = defineModel<string>('sortColumn')
const sortOrder = defineModel<'asc' | 'desc'>('sortOrder', { default: 'asc' })

const getItemKey = (item: Record<string, unknown>, index: number) => {
  return item[props.itemKey] || index
}

const getItemValue = (item: Record<string, unknown>, key: string) => {
  return key.split('.').reduce((obj: unknown, k) => (obj as Record<string, unknown>)?.[k], item as unknown)
}

const handleSort = (column: string) => {
  if (props.sortColumn === column) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortColumn.value = column
    sortOrder.value = 'asc'
  }
  emit('sort', column, sortOrder.value)
}
</script>
