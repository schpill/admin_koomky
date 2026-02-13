<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition ease-out duration-300"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 overflow-y-auto"
        role="dialog"
        aria-modal="true"
      >
        <div class="flex min-h-screen items-start justify-center p-4 pt-[20vh]">
          <!-- Overlay -->
          <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
          >
            <div
              v-if="isOpen"
              class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm"
              @click="close"
            />
          </Transition>

          <!-- Modal panel -->
          <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-4 scale-95"
          >
            <div
              v-if="isOpen"
              class="relative mx-auto max-w-2xl transform overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-2xl transition-all"
            >
              <!-- Search input -->
              <div class="flex items-center border-b border-gray-200 dark:border-gray-700 px-4">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                  ref="searchInput"
                  v-model="searchQuery"
                  type="text"
                  class="flex-1 border-0 bg-transparent px-4 py-4 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-0"
                  placeholder="Type a command or search..."
                  @keydown.enter="selectFirstResult"
                  @keydown.arrow-down="focusNextItem"
                  @keydown.arrow-up="focusPreviousItem"
                  @keydown.escape="close"
                >
                <kbd class="hidden sm:inline-block px-2 py-1 text-xs font-semibold text-gray-400 bg-gray-100 dark:bg-gray-700 rounded">ESC</kbd>
              </div>

              <!-- Results -->
              <div class="max-h-[60vh] overflow-y-auto">
                <!-- Loading state -->
                <div v-if="loading" class="flex items-center justify-center py-12">
                  <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"/>
                </div>

                <!-- No results -->
                <div v-else-if="filteredResults.length === 0" class="py-12 text-center">
                  <p class="text-gray-500 dark:text-gray-400">No results found for "{{ searchQuery }}"</p>
                </div>

                <!-- Results list -->
                <div v-else class="py-2">
                  <!-- Grouped results -->
                  <template v-for="(group, groupIndex) in groupedResults" :key="group.label">
                    <div v-if="group.items.length > 0" class="px-4 py-2">
                      <h3 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ group.label }}
                      </h3>
                      <div class="mt-2 space-y-1">
                        <button
                          v-for="(item, itemIndex) in group.items"
                          :key="item.id"
                          :ref="el => setItemRef(el, groupIndex, itemIndex)"
                          :class="[
                            'w-full flex items-center px-3 py-2 text-left rounded-lg transition-colors',
                            focusedItem?.id === item.id
                              ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300'
                              : 'text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700'
                          ]"
                          @click="selectItem(item)"
                          @mouseenter="focusedItem = item"
                        >
                          <!-- Icon -->
                          <component
                            :is="getIcon(item.icon)"
                            class="h-5 w-5 flex-shrink-0"
                            :class="focusedItem?.id === item.id ? 'text-blue-500' : 'text-gray-400'"
                          />
                          <!-- Content -->
                          <div class="ml-3 flex-1 min-w-0">
                            <p class="text-sm font-medium truncate">{{ item.title }}</p>
                            <p v-if="item.description" class="text-xs text-gray-500 dark:text-gray-400 truncate">
                              {{ item.description }}
                            </p>
                          </div>
                          <!-- Shortcut -->
                          <kbd v-if="item.shortcut" class="ml-3 px-2 py-0.5 text-xs font-mono text-gray-400 bg-gray-100 dark:bg-gray-700 rounded">
                            {{ item.shortcut }}
                          </kbd>
                        </button>
                      </div>
                    </div>
                  </template>
                </div>
              </div>

              <!-- Footer -->
              <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-2 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <div class="flex items-center gap-4">
                  <span class="flex items-center gap-1">
                    <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">↑↓</kbd>
                    navigate
                  </span>
                  <span class="flex items-center gap-1">
                    <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">↵</kbd>
                    select
                  </span>
                  <span class="flex items-center gap-1">
                    <kbd class="px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">esc</kbd>
                    close
                  </span>
                </div>
              </div>
            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import type { Component, ComponentPublicInstance } from 'vue'
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'

interface CommandItem {
  id: string
  title: string
  description?: string
  icon?: string
  shortcut?: string
  group: string
  action: () => void
}

interface Props {
  isOpen: boolean
  commands?: CommandItem[]
}

const props = withDefaults(defineProps<Props>(), {
  commands: () => [],
})

const emit = defineEmits<{
  'update:isOpen': [value: boolean]
  search: [query: string]
}>()

const searchQuery = ref('')
const searchInput = ref<HTMLInputElement>()
const loading = ref(false)
const focusedItem = ref<CommandItem | null>(null)
const itemRefs = ref<Map<string, HTMLElement>>(new Map())

// Default commands
const defaultCommands: CommandItem[] = [
  {
    id: 'dashboard',
    title: 'Go to Dashboard',
    description: 'View your dashboard and stats',
    icon: 'home',
    group: 'Navigation',
    action: () => navigateTo('/'),
  },
  {
    id: 'clients',
    title: 'View Clients',
    description: 'Browse and manage your clients',
    icon: 'users',
    group: 'Navigation',
    action: () => navigateTo('/clients'),
  },
  {
    id: 'new-client',
    title: 'Add New Client',
    description: 'Create a new client record',
    icon: 'plus',
    group: 'Actions',
    action: () => navigateTo('/clients/new'),
  },
  {
    id: 'settings',
    title: 'Settings',
    description: 'Manage your account settings',
    icon: 'cog',
    group: 'Navigation',
    action: () => navigateTo('/settings'),
  },
  {
    id: 'dark-mode',
    title: 'Toggle Dark Mode',
    description: 'Switch between light and dark themes',
    icon: 'moon',
    group: 'Preferences',
    action: () => toggleDarkMode(),
  },
  {
    id: 'logout',
    title: 'Logout',
    description: 'Sign out of your account',
    icon: 'logout',
    group: 'Account',
    action: () => logout(),
  },
]

const allCommands = computed(() => [...defaultCommands, ...props.commands])

const filteredResults = computed(() => {
  if (!searchQuery.value.trim()) {
    return allCommands.value
  }

  const query = searchQuery.value.toLowerCase()
  return allCommands.value.filter(item =>
    item.title.toLowerCase().includes(query) ||
    item.description?.toLowerCase().includes(query) ||
    item.group.toLowerCase().includes(query)
  )
})

const groupedResults = computed(() => {
  const groups = filteredResults.value.reduce((acc, item) => {
    if (!acc[item.group]) {
      acc[item.group] = []
    }
    acc[item.group].push(item)
    return acc
  }, {} as Record<string, CommandItem[]>)

  return Object.entries(groups)
    .map(([label, items]) => ({ label, items }))
    .sort((a, b) => {
      const order = ['Navigation', 'Actions', 'Preferences', 'Account']
      return order.indexOf(a.label) - order.indexOf(b.label)
    })
})

const close = () => {
  emit('update:isOpen', false)
  searchQuery.value = ''
}

const selectItem = (item: CommandItem) => {
  item.action()
  close()
}

const selectFirstResult = () => {
  if (filteredResults.value.length > 0) {
    selectItem(filteredResults.value[0])
  }
}

const focusNextItem = () => {
  const items = filteredResults.value
  const currentIndex = items.findIndex(i => i.id === focusedItem.value?.id)
  const nextIndex = currentIndex < items.length - 1 ? currentIndex + 1 : 0
  focusedItem.value = items[nextIndex]
  scrollToItem(items[nextIndex])
}

const focusPreviousItem = () => {
  const items = filteredResults.value
  const currentIndex = items.findIndex(i => i.id === focusedItem.value?.id)
  const prevIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1
  focusedItem.value = items[prevIndex]
  scrollToItem(items[prevIndex])
}

const scrollToItem = (item: CommandItem) => {
  const element = itemRefs.value.get(item.id)
  element?.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
}

const setItemRef = (el: Element | ComponentPublicInstance | null, groupIndex: number, itemIndex: number) => {
  if (el) {
    const group = groupedResults.value[groupIndex]
    if (group && group.items[itemIndex]) {
      itemRefs.value.set(group.items[itemIndex].id, el as HTMLElement)
    }
  }
}

const getIcon = (_name?: string): Component => {
  // Return icon components - simplified for now
  return 'div' as unknown as Component
}

const toggleDarkMode = () => {
  // Toggle dark mode logic
  const isDark = document.documentElement.classList.contains('dark')
  if (isDark) {
    document.documentElement.classList.remove('dark')
  } else {
    document.documentElement.classList.add('dark')
  }
}

const logout = () => {
  const { logout } = useAuth()
  logout()
}

// Watch for open state to focus input
watch(() => props.isOpen, async (isOpen) => {
  if (isOpen) {
    await nextTick()
    searchInput.value?.focus()
    focusedItem.value = filteredResults.value[0] || null
  }
})

// Handle keyboard shortcut
const handleKeydown = (e: KeyboardEvent) => {
  if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
    e.preventDefault()
    emit('update:isOpen', !props.isOpen)
  }
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown)
})
</script>
