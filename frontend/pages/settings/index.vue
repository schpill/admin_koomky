<template>
  <div class="space-y-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Settings</h1>
      <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Manage your account settings and preferences
      </p>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 dark:border-gray-700">
      <nav class="-mb-px flex space-x-8">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          :class="[
            activeTab === tab.id
              ? 'border-blue-500 text-blue-600 dark:text-blue-400'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300',
            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
          ]"
          @click="activeTab = tab.id"
        >
          {{ tab.label }}
        </button>
      </nav>
    </div>

    <!-- Tab Content -->
    <div class="mt-6">
      <!-- Profile Tab -->
      <div v-if="activeTab === 'profile'">
        <AppCard title="Profile Information">
          <form class="space-y-6" @submit.prevent="updateProfile">
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
              <AppInput
                v-model="profile.name"
                label="Full Name"
                :error="errors.name"
              />

              <AppInput
                v-model="profile.email"
                label="Email"
                type="email"
                :disabled="true"
                hint="Contact support to change your email"
              />
            </div>

            <AppTextarea
              v-model="profile.bio"
              label="Bio"
              placeholder="Tell us about yourself..."
              :rows="3"
            />

            <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
              <AppButton type="submit" :loading="isSaving">
                Save Changes
              </AppButton>
            </div>
          </form>
        </AppCard>
      </div>

      <!-- Business Tab -->
      <div v-if="activeTab === 'business'">
        <AppCard title="Business Information">
          <form class="space-y-6" @submit.prevent="updateBusiness">
            <div class="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-2">
              <AppInput
                v-model="business.company_name"
                label="Company Name"
                :error="errors.company_name"
              />

              <AppInput
                v-model="business.siret"
                label="SIRET"
                :error="errors.siret"
                hint="French business identification number"
              />
            </div>

            <AppInput
              v-model="business.vat_number"
              label="VAT Number"
              :error="errors.vat_number"
            />

            <AppTextarea
              v-model="business.address"
              label="Business Address"
              :rows="3"
            />

            <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
              <AppButton type="submit" :loading="isSaving">
                Save Changes
              </AppButton>
            </div>
          </form>
        </AppCard>
      </div>

      <!-- Security Tab -->
      <div v-if="activeTab === 'security'">
        <AppCard title="Change Password">
          <form class="space-y-6" @submit.prevent="changePassword">
            <AppInput
              v-model="password.current_password"
              label="Current Password"
              type="password"
              :error="errors.current_password"
            />

            <AppInput
              v-model="password.password"
              label="New Password"
              type="password"
              :error="errors.password"
            />

            <AppInput
              v-model="password.password_confirmation"
              label="Confirm New Password"
              type="password"
            />

            <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
              <AppButton type="submit" :loading="isSaving">
                Change Password
              </AppButton>
            </div>
          </form>
        </AppCard>

        <!-- 2FA Section -->
        <AppCard title="Two-Factor Authentication" class="mt-6">
          <div v-if="!user?.two_factor_enabled" class="text-center py-6">
            <svg class="h-12 w-12 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-white">
              Two-factor authentication is not enabled
            </h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Add an extra layer of security to your account
            </p>
            <AppButton class="mt-4" @click="enable2FA">
              Enable Two-Factor Authentication
            </AppButton>
          </div>

          <div v-else class="space-y-4">
            <div class="flex items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
              <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
              </svg>
              <div class="ml-3">
                <p class="text-sm font-medium text-green-800 dark:text-green-200">
                  Two-factor authentication is enabled
                </p>
                <p class="text-sm text-green-700 dark:text-green-300">
                  Your account is protected with 2FA
                </p>
              </div>
            </div>

            <div>
              <h4 class="text-sm font-medium text-gray-900 dark:text-white">Recovery Codes</h4>
              <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Save these codes in a safe place. They can be used to access your account if you lose your authenticator device.
              </p>
              <div class="mt-3 grid grid-cols-2 gap-2">
                <div
                  v-for="code in recoveryCodes"
                  :key="code"
                  class="p-2 bg-gray-100 dark:bg-gray-800 rounded font-mono text-sm text-gray-900 dark:text-white"
                >
                  {{ code }}
                </div>
              </div>
              <AppButton class="mt-4" variant="secondary" size="sm" @click="generateRecoveryCodes">
                Generate New Codes
              </AppButton>
            </div>

            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
              <AppButton variant="danger" size="sm" @click="disable2FA">
                Disable Two-Factor Authentication
              </AppButton>
            </div>
          </div>
        </AppCard>
      </div>

      <!-- Preferences Tab -->
      <div v-if="activeTab === 'preferences'">
        <AppCard title="Appearance">
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white">Dark Mode</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Toggle dark mode for the application
                </p>
              </div>
              <button
                :class="[
                  'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none',
                  isDarkMode
                    ? 'bg-blue-600'
                    : 'bg-gray-200 dark:bg-gray-700'
                ]"
                @click="toggleDarkMode"
              >
                <span
                  :class="[
                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                    isDarkMode ? 'translate-x-5' : 'translate-x-0'
                  ]"
                />
              </button>
            </div>

            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-sm font-medium text-gray-900 dark:text-white">Language</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  Select your preferred language
                </p>
              </div>
              <AppSelect v-model="preferences.language" :options="languageOptions" style="width: 200px;" />
            </div>
          </div>
        </AppCard>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'

definePageMeta({
  middleware: ['auth'],
})

const { $fetch } = useApi()
const { user } = useAuth()
const toast = useToast()

const activeTab = ref('profile')
const isSaving = ref(false)
const isDarkMode = ref(document.documentElement.classList.contains('dark'))

const tabs = [
  { id: 'profile', label: 'Profile' },
  { id: 'business', label: 'Business' },
  { id: 'security', label: 'Security' },
  { id: 'preferences', label: 'Preferences' },
]

const profile = reactive({
  name: '',
  email: '',
  bio: '',
})

const business = reactive({
  company_name: '',
  siret: '',
  vat_number: '',
  address: '',
})

const password = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const preferences = reactive({
  language: 'en',
})

const errors = reactive<Record<string, string>>({})
const recoveryCodes = ref<string[]>([])

const languageOptions = [
  { label: 'English', value: 'en' },
  { label: 'Français', value: 'fr' },
]

const updateProfile = async () => {
  isSaving.value = true

  try {
    await $fetch('/v1/settings', {
      method: 'PUT',
      body: {
        name: profile.name,
        bio: profile.bio,
      },
    })

    toast.success('Profile updated successfully!')
    // eslint-disable-next-line @typescript-eslint/no-dynamic-delete
    Object.keys(errors).forEach(key => delete errors[key])
  } catch (err: unknown) {
    const errorData = err as { response?: { _data?: { errors?: Record<string, string> } } }
    if (errorData.response?._data?.errors) {
      Object.assign(errors, errorData.response._data.errors)
    }
  } finally {
    isSaving.value = false
  }
}

const updateBusiness = async () => {
  isSaving.value = true

  try {
    await $fetch('/v1/settings', {
      method: 'PUT',
      body: business,
    })

    toast.success('Business information updated!')
    // eslint-disable-next-line @typescript-eslint/no-dynamic-delete
    Object.keys(errors).forEach(key => delete errors[key])
  } catch (err: unknown) {
    const errorData = err as { response?: { _data?: { errors?: Record<string, string> } } }
    if (errorData.response?._data?.errors) {
      Object.assign(errors, errorData.response._data.errors)
    }
  } finally {
    isSaving.value = false
  }
}

const changePassword = async () => {
  isSaving.value = true

  try {
    await $fetch('/v1/settings/password', {
      method: 'PUT',
      body: password,
    })

    toast.success('Password changed successfully!')
    password.current_password = ''
    password.password = ''
    password.password_confirmation = ''
    // eslint-disable-next-line @typescript-eslint/no-dynamic-delete
    Object.keys(errors).forEach(key => delete errors[key])
  } catch (err: unknown) {
    const errorData = err as { response?: { _data?: { errors?: Record<string, string> } } }
    if (errorData.response?._data?.errors) {
      Object.assign(errors, errorData.response._data.errors)
    }
  } finally {
    isSaving.value = false
  }
}

const enable2FA = () => {
  // Navigate to 2FA setup
  navigateTo('/settings/2fa/setup')
}

const disable2FA = async () => {
  // Confirm and disable 2FA
  try {
    await $fetch('/v1/auth/2fa', { method: 'DELETE' })
    toast.success('Two-factor authentication disabled')
    user.value.two_factor_enabled = false
  } catch {
    toast.error('Failed to disable two-factor authentication')
  }
}

const generateRecoveryCodes = async () => {
  try {
    const response = await $fetch('/v1/auth/2fa/recovery-codes', { method: 'POST' })
    recoveryCodes.value = response.data.attributes.codes
    toast.success('New recovery codes generated')
  } catch {
    toast.error('Failed to generate recovery codes')
  }
}

const toggleDarkMode = () => {
  isDarkMode.value = !isDarkMode.value
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark')
  } else {
    document.documentElement.classList.remove('dark')
  }
  localStorage.setItem('darkMode', String(isDarkMode.value))
}

onMounted(() => {
  if (user.value) {
    profile.name = user.value.name
    profile.email = user.value.email
    profile.bio = user.value.bio || ''
  }

  // Check dark mode preference
  const savedDarkMode = localStorage.getItem('darkMode')
  isDarkMode.value = savedDarkMode === 'true' || (!savedDarkMode && window.matchMedia('(prefers-color-scheme: dark)').matches)
  if (isDarkMode.value) {
    document.documentElement.classList.add('dark')
  }
})
</script>
