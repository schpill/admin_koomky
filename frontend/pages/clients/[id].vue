<template>
  <div class="space-y-6">
    <!-- Loading State -->
    <div v-if="isLoading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"/>
    </div>

    <!-- Client Details -->
    <div v-else-if="client">
      <!-- Header -->
      <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="h-16 w-16 flex-shrink-0 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
              <span class="text-2xl font-bold text-blue-600 dark:text-blue-300">
                {{ client.attributes.name.charAt(0).toUpperCase() }}
              </span>
            </div>
            <div>
              <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ client.attributes.name }}
              </h1>
              <p v-if="client.attributes.company" class="text-gray-600 dark:text-gray-400">
                {{ client.attributes.company }}
              </p>
            </div>
          </div>
          <div class="flex gap-2">
            <NuxtLink
              :to="`/clients/${client.id}/edit`"
              class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
            >
              Edit
            </NuxtLink>
            <button
              class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-yellow-600 hover:bg-yellow-700"
              @click="confirmArchive"
            >
              {{ client.attributes.status === 'active' ? 'Archive' : 'Restore' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left Column: Contact Info -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Contact Information -->
        <AppCard title="Contact Information">
          <dl class="grid grid-cols-1 gap-y-4 sm:grid-cols-2">
            <div v-if="client.attributes.email">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                <a :href="`mailto:${client.attributes.email}`" class="text-blue-600 hover:text-blue-700">
                  {{ client.attributes.email }}
                </a>
              </dd>
            </div>
            <div v-if="client.attributes.phone">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Phone</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                <a :href="`tel:${client.attributes.phone}`" class="text-blue-600 hover:text-blue-700">
                  {{ client.attributes.phone }}
                </a>
              </dd>
            </div>
            <div v-if="client.attributes.website" class="sm:col-span-2">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Website</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                <a :href="client.attributes.website" target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-700">
                  {{ client.attributes.website }}
                </a>
              </dd>
            </div>
            <div v-if="client.attributes.vat_number">
              <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">VAT Number</dt>
              <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                {{ client.attributes.vat_number }}
              </dd>
            </div>
          </dl>
        </AppCard>

        <!-- Billing Address -->
        <AppCard title="Billing Address">
          <address class="not-italic text-sm text-gray-900 dark:text-white whitespace-pre-line">
            {{ client.attributes.billing_address || 'No billing address provided' }}
          </address>
        </AppCard>

        <!-- Notes -->
        <AppCard v-if="client.attributes.notes" title="Notes">
          <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">
            {{ client.attributes.notes }}
          </p>
        </AppCard>

        <!-- Contacts -->
        <AppCard title="Contacts">
          <div v-if="contacts.length > 0" class="space-y-4">
            <div
              v-for="contact in contacts"
              :key="contact.id"
              class="flex items-start p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
            >
              <div class="h-10 w-10 flex-shrink-0 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                  {{ contact.attributes.name.charAt(0).toUpperCase() }}
                </span>
              </div>
              <div class="ml-4 flex-1">
                <div class="flex items-center gap-2">
                  <p class="text-sm font-medium text-gray-900 dark:text-white">
                    {{ contact.attributes.name }}
                  </p>
                  <AppBadge
                    v-if="contact.attributes.is_primary"
                    text="Primary"
                    size="sm"
                    variant="success"
                  />
                </div>
                <p v-if="contact.attributes.position" class="text-sm text-gray-500 dark:text-gray-400">
                  {{ contact.attributes.position }}
                </p>
                <div class="mt-2 flex gap-4 text-sm">
                  <a v-if="contact.attributes.email" :href="`mailto:${contact.attributes.email}`" class="text-blue-600 hover:text-blue-700">
                    {{ contact.attributes.email }}
                  </a>
                  <a v-if="contact.attributes.phone" :href="`tel:${contact.attributes.phone}`" class="text-blue-600 hover:text-blue-700">
                    {{ contact.attributes.phone }}
                  </a>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
            No contacts added yet.
          </div>
        </AppCard>

        <!-- Activity Timeline -->
        <AppCard title="Activity">
          <div v-if="activities.length > 0" class="space-y-4">
            <div
              v-for="activity in activities"
              :key="activity.id"
              class="flex items-start"
            >
              <div class="flex-shrink-0">
                <div class="h-8 w-8 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                  <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 102 0V6z" clip-rule="evenodd" />
                  </svg>
                </div>
              </div>
              <div class="ml-4">
                <p class="text-sm text-gray-900 dark:text-white">
                  {{ activity.attributes.description }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                  {{ formatDate(activity.attributes.created_at) }}
                </p>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
            No activity recorded yet.
          </div>
        </AppCard>
      </div>

      <!-- Right Column: Tags & Actions -->
      <div class="space-y-6">
        <!-- Tags -->
        <AppCard title="Tags">
          <div class="flex flex-wrap gap-2">
            <AppBadge
              v-for="tag in client.relationships?.tags?.data || []"
              :key="tag.id"
              :text="tag.attributes.name"
            />
            <span v-if="!client.relationships?.tags?.data?.length" class="text-sm text-gray-500 dark:text-gray-400">
              No tags
            </span>
          </div>
        </AppCard>

        <!-- Meta Information -->
        <AppCard title="Information">
          <dl class="space-y-3">
            <div>
              <dt class="text-sm text-gray-500 dark:text-gray-400">Reference</dt>
              <dd class="text-sm font-mono text-gray-900 dark:text-white">
                {{ client.attributes.reference }}
              </dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500 dark:text-gray-400">Status</dt>
              <dd>
                <AppBadge
                  :text="client.attributes.status"
                  :variant="client.attributes.status === 'active' ? 'success' : 'default'"
                  size="sm"
                />
              </dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500 dark:text-gray-400">Created</dt>
              <dd class="text-sm text-gray-900 dark:text-white">
                {{ formatDate(client.attributes.created_at) }}
              </dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500 dark:text-gray-400">Last Updated</dt>
              <dd class="text-sm text-gray-900 dark:text-white">
                {{ formatDate(client.attributes.updated_at) }}
              </dd>
            </div>
          </dl>
        </AppCard>
      </div>
    </div>

    <!-- Archive Confirmation Modal -->
    <AppModal
      v-model:is-open="showArchiveModal"
      :title="client?.attributes.status === 'active' ? 'Archive Client' : 'Restore Client'"
      size="sm"
    >
      <p class="text-sm text-gray-500 dark:text-gray-400">
        Are you sure you want to {{ client?.attributes.status === 'active' ? 'archive' : 'restore' }}
        <strong>{{ client?.attributes.name }}</strong>?
      </p>
      <template #footer>
        <button
          type="button"
          class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
          @click="showArchiveModal = false"
        >
          Cancel
        </button>
        <AppButton
          :variant="client?.attributes.status === 'active' ? 'warning' : 'success'"
          @click="handleArchive"
        >
          {{ client?.attributes.status === 'active' ? 'Archive' : 'Restore' }}
        </AppButton>
      </template>
    </AppModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

definePageMeta({
  middleware: ['auth'],
})

const route = useRoute()
const { $fetch } = useApi()

interface Client {
  id: string
  attributes: {
    name: string
    status: string
    created_at: string
    updated_at: string
  }
  relationships?: {
    contacts?: { data: any[] }
    activities?: { data: any[] }
  }
}

const isLoading = ref(true)
const client = ref<Client | null>(null)
const showArchiveModal = ref(false)

const contacts = computed(() => client.value?.relationships?.contacts?.data || [])
const activities = computed(() => client.value?.relationships?.activities?.data || [])

const fetchClient = async () => {
  isLoading.value = true

  try {
    const response = await $fetch<{ data: Client }>(`/v1/clients/${route.params.id}`)
    client.value = response.data
  } catch (error) {
    console.error('Failed to fetch client:', error)
  } finally {
    isLoading.value = false
  }
}

const confirmArchive = () => {
  showArchiveModal.value = true
}

const handleArchive = async () => {
  if (!client.value) return

  const action = client.value.attributes.status === 'active' ? 'archive' : 'restore'
  try {
    await $fetch(`/v1/clients/${route.params.id}/${action}`, { method: 'POST' })

    const toast = useToast()
    toast.success(`Client ${action}d successfully!`)

    showArchiveModal.value = false
    await fetchClient()
  } catch (error) {
    console.error(`Failed to ${action} client:`, error)
  }
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

onMounted(() => {
  fetchClient()
})

// Watch route param changes
watch(() => route.params.id, () => {
  fetchClient()
})
</script>
