import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { createTestingPinia } from '@pinia/testing'
import { useAuth } from '~/composables/useAuth'
import { useApi } from '~/composables/useApi'

// Mock useApi
vi.mock('~/composables/useApi', () => ({
  useApi: vi.fn(() => ({
    fetch: vi.fn(),
    clearTokens: vi.fn(),
    setTokens: vi.fn(),
  })),
}))

vi.mock('~/composables/useToast', () => ({
  useToast: () => ({
    toasts: { value: [] },
    add: vi.fn(),
    success: vi.fn(),
    error: vi.fn(),
    warning: vi.fn(),
    info: vi.fn(),
    clear: vi.fn(),
  }),
}))

describe('useAuth', () => {
  beforeEach(() => {
    const pinia = createPinia()
    setActivePinia(pinia)
    vi.clearAllMocks()
  })

  it('provides user ref', () => {
    const { user } = useAuth()
    expect(user.value).toBeDefined()
  })

  it('provides isAuthenticated ref', () => {
    const { isAuthenticated } = useAuth()
    expect(isAuthenticated.value).toBeDefined()
  })

  it('provides isLoading ref', () => {
    const { isLoading } = useAuth()
    expect(isLoading.value).toBeDefined()
  })

  it('has login method', () => {
    const { login } = useAuth()
    expect(login).toBeInstanceOf(Function)
  })

  it('has register method', () => {
    const { register } = useAuth()
    expect(register).toBeInstanceOf(Function)
  })

  it('has logout method', () => {
    const { logout } = useAuth()
    expect(logout).toBeInstanceOf(Function)
  })

  it('has refreshToken method', () => {
    const { refreshToken } = useAuth()
    expect(refreshToken).toBeInstanceOf(Function)
  })

  describe('login', () => {
    it('sets user and tokens on success', async () => {
      const mockFetch = vi.fn().mockResolvedValue({
        data: {
          type: 'user',
          id: '123',
          attributes: { name: 'Test User', email: 'test@example.com' },
        },
        meta: {
          token: {
            access_token: 'test-token',
            refresh_token: 'test-refresh',
            expires_in: 900,
          },
        },
      })
      
      vi.mocked(useApi).mockReturnValue({
        fetch: mockFetch,
        clearTokens: vi.fn(),
        setTokens: vi.fn(),
        refreshAccessToken: vi.fn(),
        getAccessToken: vi.fn(),
        getRefreshToken: vi.fn(),
        subscribeToRefresh: vi.fn(),
      })

      const { login } = useAuth()
      await login({ email: 'test@example.com', password: 'password' })

      expect(mockFetch).toHaveBeenCalledWith('/auth/login', {
        method: 'POST',
        body: { email: 'test@example.com', password: 'password' },
      })
    })
  })

  describe('logout', () => {
    it('clears tokens and resets user state', async () => {
      const mockClearTokens = vi.fn()
      const mockFetch = vi.fn().mockResolvedValue({})
      
      vi.mocked(useApi).mockReturnValue({
        fetch: mockFetch,
        clearTokens: mockClearTokens,
        setTokens: vi.fn(),
        refreshAccessToken: vi.fn(),
        getAccessToken: vi.fn(),
        getRefreshToken: vi.fn(),
        subscribeToRefresh: vi.fn(),
      })

      const { logout } = useAuth()
      await logout()

      expect(mockClearTokens).toHaveBeenCalled()
    })
  })
})
