import type { Ref } from 'vue'
import { defineStore, store } from 'pinia'
import { useApi } from './useApi'
import { useToast } from './useToast'

export interface User {
  id: string
  name: string
  email: string
  avatar_url?: string
  business_name?: string
  business_address?: string
  siret?: string
  ape_code?: string
  vat_number?: string
}

export interface LoginCredentials {
  email: string
  password: string
  two_factor_code?: string
}

export const useAuth = () => {
  const api = useApi()
  const toast = useToast()
  const router = useRouter()
  const config = useRuntimeConfig()

  // Initialize auth store
  const useAuthStore = defineStore('auth', () => ({
    user: null as User | null,
    isAuthenticated: false,
    isLoading: false,
  }))

  const authStore = useAuthStore()

  const login = async (credentials: LoginCredentials) => {
    authStore.isLoading = true

    try {
      const response = await api.fetch('/auth/login', {
        method: 'POST',
        body: credentials,
      })

      const result = response._data as {
        data: { type: string; id: string; attributes: User }
        meta: { token: { access_token: string; refresh_token: string; expires_in: number } }
      }

      // Store tokens
      api.setTokens(result.meta.token.access_token, result.meta.token.refresh_token)

      // Set user state
      authStore.user = result.data.attributes
      authStore.isAuthenticated = true

      toast.success('Welcome back!')
      router.push('/')
    } catch (error: any) {
      authStore.isLoading = false
      const errorData = error.response?._data as { error?: { message: string } }

      if (errorData?.error?.message) {
        toast.error(errorData.error.message)
      }
    } finally {
      authStore.isLoading = false
    }
  }

  const register = async (data: { name: string; email: string; password: string }) => {
    authStore.isLoading = true

    try {
      const response = await api.fetch('/auth/register', {
        method: 'POST',
        body: data,
      })

      const result = response._data as {
        data: { type: string; id: string; attributes: User }
        meta: { token: { access_token: string; refresh_token: string; expires_in: number } }
      }

      // Store tokens
      api.setTokens(result.meta.token.access_token, result.meta.token.refresh_token)

      // Set user state
      authStore.user = result.data.attributes
      authStore.isAuthenticated = true

      toast.success('Account created successfully!')
      router.push('/')
    } catch (error: any) {
      authStore.isLoading = false
      const errorData = error.response?._data as { error?: { message?: string; errors?: Record<string, string[]> } }

      if (errorData?.error?.errors) {
        const firstError = Object.values(errorData.error.errors)[0]
        toast.error(firstError)
      } else if (errorData?.error?.message) {
        toast.error(errorData.error.message)
      }
    } finally {
      authStore.isLoading = false
    }
  }

  const logout = async () => {
    try {
      await api.fetch('/auth/logout', {
        method: 'POST',
      })

      // Clear tokens
      api.clearTokens()

      // Reset auth state
      authStore.user = null
      authStore.isAuthenticated = false

      toast.success('Logged out successfully')
      router.push('/auth/login')
    } catch (error) {
      toast.error('Failed to logout')
    }
  }

  const refreshToken = async () => {
    try {
      const newToken = await api.refreshAccessToken()
      return newToken
    } catch (error) {
      toast.error('Session expired. Please login again.')
      router.push('/auth/login')
      throw error
    }
  }

  return {
    user: store.toRef(() => authStore.user),
    isAuthenticated: store.toRef(() => authStore.isAuthenticated),
    isLoading: store.toRef(() => authStore.isLoading),
    login,
    register,
    logout,
    refreshToken,
  }
}
