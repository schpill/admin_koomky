import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useApi } from '~/composables/useApi'

// Mock fetch globally
global.fetch = vi.fn()

describe('useApi', () => {
  beforeEach(() => {
    const pinia = createPinia()
    setActivePinia(pinia)
    vi.clearAllMocks()
  })

  it('provides fetch function', () => {
    const { $fetch } = useApi()
    expect(typeof $fetch).toBe('function')
  })

  it('includes authorization header when token exists', async () => {
    const mockFetch = global.fetch as any
    mockFetch.mockResolvedValue({
      ok: true,
      json: async () => ({ data: [] })
    })

    // Set token in localStorage
    localStorage.setItem('auth_token', 'test-token')

    const { $fetch } = useApi()
    await $fetch('/test')

    expect(mockFetch).toHaveBeenCalledWith(
      expect.any(String),
      expect.objectContaining({
        headers: expect.objectContaining({
          Authorization: 'Bearer test-token'
        })
      })
    )
  })

  it('handles 401 errors and refreshes token', async () => {
    const mockFetch = global.fetch as any

    // First call fails with 401, second succeeds after refresh
    mockFetch
      .mockResolvedValueOnce({
        ok: false,
        status: 401,
        json: async () => ({ error: { message: 'Unauthenticated' } })
      })
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({
          data: {
            data: { type: 'user', id: '123' },
            meta: { token: { access_token: 'new-token' } }
          }
        })
      })
      .mockResolvedValueOnce({
        ok: true,
        json: async () => ({ data: [] })
      })

    localStorage.setItem('auth_token', 'old-token')
    localStorage.setItem('refresh_token', 'refresh-token')

    const { $fetch } = useApi()
    await $fetch('/test')

    // Should have tried to refresh token
    expect(mockFetch).toHaveBeenCalledTimes(3)
  })
})
