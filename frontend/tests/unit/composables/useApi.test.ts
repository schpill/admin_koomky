import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useApi } from '~/composables/useApi'

// Mock fetch globally
const globalFetch = vi.fn()
global.fetch = globalFetch

describe('useApi', () => {
  beforeEach(() => {
    const pinia = createPinia()
    setActivePinia(pinia)
    vi.clearAllMocks()
    localStorage.clear()
  })

  it('provides fetch function', () => {
    const { fetch } = useApi()
    expect(typeof fetch).toBe('function')
  })

  it('includes authorization header when token exists', async () => {
    globalFetch.mockResolvedValue(new Response(JSON.stringify({ data: [] }), {
      status: 200,
      headers: { 'Content-Type': 'application/json' }
    }))

    // Set token in localStorage
    localStorage.setItem('access_token', 'test-token')

    const { fetch } = useApi()
    await fetch('/test')

    // Verify headers manually
    const callArgs = globalFetch.mock.calls[0]
    const options = callArgs[1]
    const authHeader = options.headers instanceof Headers 
      ? options.headers.get('Authorization')
      : options.headers['Authorization']

    expect(authHeader).toBe('Bearer test-token')
  })

  it('handles 401 errors and refreshes token', async () => {
    // First call fails with 401
    const errorResponse = new Response(JSON.stringify({ error: { message: 'Unauthenticated' } }), {
      status: 401,
      headers: { 'Content-Type': 'application/json' }
    })
    Object.defineProperty(errorResponse, 'url', { value: '/test' })

    globalFetch.mockResolvedValueOnce(errorResponse)
    
    // Refresh token call
    globalFetch.mockResolvedValueOnce(new Response(JSON.stringify({
      meta: { token: 'new-token' }
    }), {
      status: 200,
      headers: { 'Content-Type': 'application/json' }
    }))

    // Retry call
    globalFetch.mockResolvedValueOnce(new Response(JSON.stringify({ data: [] }), {
      status: 200,
      headers: { 'Content-Type': 'application/json' }
    }))

    localStorage.setItem('access_token', 'old-token')
    localStorage.setItem('refresh_token', 'refresh-token')

    const { fetch } = useApi()
    
    try {
      await fetch('/test')
    } catch (e) {
      // Expected to throw due to 401 propagation
    }

    // Should have tried to refresh token
    // 1. Initial request (401)
    // 2. Refresh request (/auth/refresh)
    // 3. Retry request (200)
    expect(globalFetch).toHaveBeenCalledTimes(3)
  })
})
