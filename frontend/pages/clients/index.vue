<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Clients</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Manage your client portfolio
        </p>
      </div>
      <NuxtLink
        to="/clients/new"
        class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
      >
        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        Add Client
      </NuxtLink>
    </div>

    <!-- Filters -->
    <AppCard class="p-4">
      <div class="flex flex-col sm:flex-row gap-4">
        <!-- Search -->
        <div class="flex-1">
          <AppInput
            v-model="searchQuery"
            placeholder="Search clients..."
            custom-class="pl-10"
          >
            <template #prefix>
              <svg class="h-5 w-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </template>
          </AppInput>
        </div>

        <!-- Status Filter -->
        <div class="sm:w-48">
          <AppSelect v-model="statusFilter" :options="statusOptions" />
        </div>

        <!-- Tag Filter -->
        <div class="sm:w-48">
          <AppSelect v-model="tagFilter" :options="tagOptions" placeholder="All tags" />
        </div>
      </div>
    </AppCard>

    <!-- Data Table -->
    <AppCard :padding="false">
      <AppDataTable
        :columns="tableColumns"
        :data="clients"
        :loading="isLoading"
        :item-key="'id'"
        :show-pagination="true"
        :current-page="currentPage"
        :total-pages="totalPages"
        :total-items="totalItems"
        :per-page="perPage"
        @page-change="handlePageChange"
        @sort="handleSort"
      >
        <template #column-name="{ record }">
          <div class="flex items-center">
            <div class="h-10 w-10 flex-shrink-0 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
              <span class="text-sm font-medium text-blue-600 dark:text-blue-300">
                {{ record.name.charAt(0).toUpperCase() }}
              </span>
            </div>
            <div class="ml-4">
              <div class="text-sm font-medium text-gray-900 dark:text-white">
                {{ record.name }}
              </div>
              <div v-if="record.company" class="text-sm text-gray-500 dark:text-gray-400">
                {{ record.company }}
              </div>
            </div>
          </div>
        </template>

        <template #column-email="{ value }">
          <a v-if="value" :href="`mailto:${value}`" class="text-blue-600 hover:text-blue-700">
            {{ value }}
          </a>
          <span v-else class="text-gray-400">—</span>
        </template>

        <template #column-tags="{ record }">
          <div class="flex flex-wrap gap-1">
            <AppBadge
              v-for="tag in record.relationships?.tags?.data || []"
              :key="tag.id"
              :text="tag.attributes.name"
              size="sm"
            />
          </div>
        </template>

        <template #column-status="{ value }">
          <AppBadge
            :text="value"
            :variant="value === 'active' ? 'success' : 'default'"
            size="sm"
          />
        </template>

        <template #actions="{ record }">
          <NuxtLink
            :to="`/clients/${record.id}`"
            class="text-blue-600 hover:text-blue-700 mr-3"
          >
            View
          </NuxtLink>
          <button
            @click="confirmDelete(record)"
            class="text-red-600 hover:text-red-700"
          >
            Delete
          </button>
        </template>
      </AppDataTable>
    </AppCard>

    <!-- Delete Confirmation Modal -->
    <AppModal
      v-model:is-open="showDeleteModal"
      title="Delete Client"
      size="sm"
    >
      <p class="text-sm text-gray-500 dark:text-gray-400">
        Are you sure you want to delete <strong>{{ clientToDelete?.name }}</strong>? This action cannot be undone.
      </p>
      <template #footer>
        <button
          type="button"
          class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
          @click="showDeleteModal = false"
        >
          Cancel
        </button>
        <AppButton
          variant="danger"
          @click="deleteClient"
        >
          Delete
        </AppButton>
      </template>
    </AppModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'

definePageMeta({
  middleware: ['auth'],
})

const { $fetch } = useApi()

// State
const clients = ref<any[]>([])
const isLoading = ref(false)
const searchQuery = ref('')
const statusFilter = ref('all')
const tagFilter = ref('')
const currentPage = ref(1)
const perPage = ref(15)
const totalPages = ref(1)
const totalItems = ref(0)
const showDeleteModal = ref(false)
const clientToDelete = ref<any>(null)

// Table configuration
const tableColumns = [
  { key: 'name', label: 'Client', sortable: true },
  { key: 'email', label: 'Email', sortable: true },
  { key: 'phone', label: 'Phone', sortable: true },
  { key: 'tags', label: 'Tags', sortable: false },
  { key: 'status', label: 'Status', sortable: true },
]

// Filter options
const statusOptions = [
  { label: 'All Status', value: 'all' },
  { label: 'Active', value: 'active' },
  { label: 'Archived', value: 'archived' },
]

const tagOptions = computed(() => {
  // This will be populated from API
  return []
})

// Fetch clients
const fetchClients = async () => {
  isLoading.value = true

  try {
    const params: Record<string, any> = {
      page: currentPage.value,
      per_page: perPage.value,
    }

    if (searchQuery.value) {
      params.search = searchQuery.value
    }

    if (statusFilter.value !== 'all') {
      params.status = statusFilter.value
    }

    if (tagFilter.value) {
      params.tag = tagFilter.value
    }

    const response = await $fetch('/v1/clients', { params })
    clients.value = response.data.data
    totalPages.value = response.data.meta.last_page
    totalItems.value = response.data.meta.total
  } catch (error) {
    console.error('Failed to fetch clients:', error)
  } finally {
    isLoading.value = false
  }
}

// Handlers
const handlePageChange = (page: number) => {
  currentPage.value = page
  fetchClients()
}

const handleSort = (column: string, order: 'asc' | 'desc') => {
  // Implement sorting
}

const confirmDelete = (client: any) => {
  clientToDelete.value = client
  showDeleteModal.value = true
}

const deleteClient = async () => {
  if (!clientToDelete.value) return

  try {
    await $fetch(`/v1/clients/${clientToDelete.value.id}`, { method: 'DELETE' })
    showDeleteModal.value = false
    await fetchClients()
  } catch (error) {
    console.error('Failed to delete client:', error)
  }
}

// Watch for filter changes
watch([searchQuery, statusFilter, tagFilter], () => {
  currentPage.value = 1
  fetchClients()
}, { debounce: 300 })

onMounted(() => {
  fetchClients()
})
</script>
