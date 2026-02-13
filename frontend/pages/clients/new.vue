<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">New Client</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
          Add a new client to your portfolio
        </p>
      </div>
    </div>

    <!-- Form -->
    <AppCard>
      <form class="space-y-6" @submit.prevent="submitForm">
        <!-- Client Information -->
        <div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            Client Information
          </h3>
          <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
            <AppInput
              v-model="form.name"
              label="Client Name"
              placeholder="Enter client name"
              required
              :error="errors.name"
            />

            <AppInput
              v-model="form.company"
              label="Company"
              placeholder="Enter company name"
              :error="errors.company"
            />

            <AppInput
              v-model="form.email"
              label="Email"
              type="email"
              placeholder="client@example.com"
              :error="errors.email"
            />

            <AppInput
              v-model="form.phone"
              label="Phone"
              placeholder="+33 1 23 45 67 89"
              :error="errors.phone"
            />
          </div>
        </div>

        <!-- Billing Information -->
        <div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            Billing Information
          </h3>
          <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
            <AppInput
              v-model="form.vat_number"
              label="VAT Number"
              placeholder="FR12345678901"
              :error="errors.vat_number"
            />

            <AppInput
              v-model="form.website"
              label="Website"
              type="url"
              placeholder="https://example.com"
              :error="errors.website"
            />
          </div>

          <div class="mt-4">
            <AppTextarea
              v-model="form.billing_address"
              label="Billing Address"
              placeholder="Enter full billing address"
              :rows="3"
              :error="errors.billing_address"
            />
          </div>
        </div>

        <!-- Tags -->
        <div>
          <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
            Tags
          </h3>
          <div class="flex flex-wrap gap-2 mb-2">
            <AppBadge
              v-for="tag in form.tags"
              :key="tag"
              :text="tag"
              variant="info"
              removable
              @remove="removeTag(tag)"
            />
          </div>
          <div class="flex gap-2">
            <AppInput
              v-model="newTag"
              placeholder="Add a tag..."
              custom-class="flex-1"
              @keyup.enter="addTag"
            />
            <AppButton type="button" @click="addTag">Add</AppButton>
          </div>
        </div>

        <!-- Notes -->
        <div>
          <AppTextarea
            v-model="form.notes"
            label="Notes"
            placeholder="Additional notes about this client..."
            :rows="4"
            :error="errors.notes"
          />
        </div>

        <!-- Contacts -->
        <div>
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
              Contacts
            </h3>
            <AppButton type="button" variant="secondary" size="sm" @click="addContact">
              + Add Contact
            </AppButton>
          </div>

          <div v-if="form.contacts.length > 0" class="space-y-4">
            <div
              v-for="(contact, index) in form.contacts"
              :key="index"
              class="p-4 border border-gray-200 dark:border-gray-700 rounded-lg"
            >
              <div class="flex justify-between items-start mb-3">
                <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                  Contact {{ index + 1 }}
                </h4>
                <button
                  v-if="form.contacts.length > 1"
                  type="button"
                  class="text-red-600 hover:text-red-700 text-sm"
                  @click="removeContact(index)"
                >
                  Remove
                </button>
              </div>
              <div class="grid grid-cols-1 gap-y-4 gap-x-4 sm:grid-cols-2">
                <AppInput
                  v-model="contact.name"
                  label="Name"
                  placeholder="Contact name"
                  :required="index === 0"
                />

                <AppInput
                  v-model="contact.email"
                  label="Email"
                  type="email"
                  placeholder="contact@example.com"
                />

                <AppInput
                  v-model="contact.phone"
                  label="Phone"
                  placeholder="+33 1 23 45 67 89"
                />

                <AppInput
                  v-model="contact.position"
                  label="Position"
                  placeholder="Job title"
                />
              </div>
              <div class="mt-3">
                <label class="flex items-center">
                  <input
                    v-model="contact.is_primary"
                    type="checkbox"
                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                  >
                  <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                    Primary contact
                  </span>
                </label>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
            No contacts added. Click "Add Contact" to add one.
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
          <AppButton
            type="button"
            variant="secondary"
            @click="goBack"
          >
            Cancel
          </AppButton>
          <AppButton
            type="submit"
            :loading="isSubmitting"
          >
            Create Client
          </AppButton>
        </div>
      </form>
    </AppCard>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive } from 'vue'

definePageMeta({
  middleware: ['auth'],
})

const { $fetch } = useApi()
const router = useRouter()

const isSubmitting = ref(false)
const newTag = ref('')

const form = reactive({
  name: '',
  company: '',
  email: '',
  phone: '',
  vat_number: '',
  website: '',
  billing_address: '',
  notes: '',
  tags: [] as string[],
  contacts: [
    {
      name: '',
      email: '',
      phone: '',
      position: '',
      is_primary: true,
    },
  ] as Array<{ name: string; email: string; phone: string; position: string; is_primary: boolean }>,
})

const errors = reactive<Record<string, string>>({})

// Methods
const addTag = () => {
  if (newTag.value.trim() && !form.tags.includes(newTag.value.trim())) {
    form.tags.push(newTag.value.trim())
    newTag.value = ''
  }
}

const removeTag = (tag: string) => {
  const index = form.tags.indexOf(tag)
  if (index > -1) {
    form.tags.splice(index, 1)
  }
}

const addContact = () => {
  form.contacts.push({
    name: '',
    email: '',
    phone: '',
    position: '',
    is_primary: false,
  })
}

const removeContact = (index: number) => {
  form.contacts.splice(index, 1)
}

const validateForm = (): boolean => {
  // eslint-disable-next-line @typescript-eslint/no-dynamic-delete
  Object.keys(errors).forEach(key => delete errors[key])

  let isValid = true

  if (!form.name) {
    errors.name = 'Client name is required'
    isValid = false
  }

  if (!form.contacts[0]?.name) {
    errors['contacts.0.name'] = 'Primary contact name is required'
    isValid = false
  }

  return isValid
}

const submitForm = async () => {
  if (!validateForm()) return

  isSubmitting.value = true

  try {
    await $fetch('/v1/clients', {
      method: 'POST',
      body: form,
    })

    const toast = useToast()
    toast.success('Client created successfully!')

    router.push('/clients')
  } catch (err: unknown) {
    const errorData = err as { response?: { _data?: { errors?: Record<string, string> } } }
    if (errorData.response?._data?.errors) {
      Object.assign(errors, errorData.response._data.errors)
    }
  } finally {
    isSubmitting.value = false
  }
}

const goBack = () => {
  router.push('/clients')
}
</script>
