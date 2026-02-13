// https://nuxt.com/docs/api/composables/use-fetch
import type { FetchOptions, ofetch } from 'ofetch'
import { useToast } from './useToast'

export interface ApiError {
  message: string
  status?: number
  errors?: Record<string, string[]>
}

let isRefreshing = false
let refreshSubscribers: Array<(token: string) => void> = []

export function useApi() {
  const config = useRuntimeConfig()
  const toast = useToast()
  
  const isClient = import.meta.client || typeof localStorage !== 'undefined'

  // Get tokens from localStorage
  const getAccessToken = () => {
    if (isClient) return localStorage.getItem('access_token')
    return null
  }

  const getRefreshToken = () => {
    if (isClient) return localStorage.getItem('refresh_token')
    return null
  }

  const setTokens = (accessToken: string, refreshToken: string) => {
    if (isClient) {
      localStorage.setItem('access_token', accessToken)
      localStorage.setItem('refresh_token', refreshToken)
    }
  }

  const clearTokens = () => {
    if (isClient) {
      localStorage.removeItem('access_token')
      localStorage.removeItem('refresh_token')
    }
  }

  // Subscribe to token refresh events
  const subscribeToRefresh = (callback: (token: string) => void) => {
    refreshSubscribers.push(callback)
    return () => {
      refreshSubscribers = refreshSubscribers.filter(sub => sub !== callback)
    }
  }

  // Notify all subscribers of new token
  const notifyRefresh = (token: string) => {
    refreshSubscribers.forEach(sub => sub(token))
  }

  // Refresh access token
  const refreshAccessToken = async (): Promise<string> => {
    if (isRefreshing) return Promise.resolve(getAccessToken() || '')

    isRefreshing = true

    try {
      const refreshToken = getRefreshToken()

      const response = await $fetch<string>(`${config.public.apiBase}/auth/refresh`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${refreshToken}`,
          'Accept': 'application/json',
        },
      })

      const result = response as unknown as { meta?: { token?: string } }

      if (result.meta?.token) {
        const newToken = result.meta.token
        setTokens(newToken, newToken) // Use same token for both
        notifyRefresh(newToken)
        return newToken
      }

      throw new Error('No token returned')
    } catch (error) {
      clearTokens()
      window.location.href = '/auth/login'
      throw error
    } finally {
      isRefreshing = false
    }
  }

  // Setup request interceptor
  const apiFetch = $fetch.create({
    defaults: {
      baseURL: config.public.apiBase,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
      },
    },

    async onRequest({ options }: { options: FetchOptions }) {
      const accessToken = getAccessToken()

      if (accessToken) {
        options.headers = {
          ...options.headers,
          Authorization: `Bearer ${accessToken}`,
        }
      }
    },

    async onResponse({ request, response, options }) {
      // Handle 401 Unauthorized - try to refresh token
      if (response.status === 401) {
        try {
          const newToken = await refreshAccessToken()

          options.headers = {
            ...options.headers || {},
            Authorization: `Bearer ${newToken}`,
          }

          const retryRequest = request || response.url || (response as any).request?.url

          if (!retryRequest) {
            throw new Error('Cannot retry request: URL not found')
          }

          // Retry the original request with new token
          return $fetch(retryRequest, {
            ...options,
            headers: options.headers,
          })
        } catch (e) {
          clearTokens()
          window.location.href = '/auth/login'
        }
      }
    },

    async onResponseError({ response }) {
      const error = response._data as ApiError

      if (error.status && error.status >= 500) {
        toast.error('Server error. Please try again later.')
      }

      // Handle validation errors
      if (error.errors) {
        const firstError = Object.values(error.errors)[0]
        toast.error(firstError)
      }
    },
  })

  return {
    fetch: apiFetch,
    refreshAccessToken,
    getAccessToken,
    getRefreshToken,
    setTokens,
    clearTokens,
    subscribeToRefresh,
  }
}
