// https://nuxt.com/docs/api/middleware/nuxt
import { useAuth } from '~/composables/useAuth'
import { useToast } from '~/composables/useToast'

export default defineNuxtRouteMiddleware((to) => {
  const { isAuthenticated } = useAuth()
  const toast = useToast()

  // Public routes that don't require authentication
  const publicRoutes = ['/auth/login', '/auth/register', '/auth/forgot-password', '/auth/reset-password']

  if (publicRoutes.includes(to.path)) {
    // Allow access to public routes
    if (isAuthenticated.value && to.path === '/auth/login') {
      return navigateTo('/')
    }
    return
  }

  // Protected routes
  if (!isAuthenticated.value) {
    toast.error('Please login to access this page')
    return navigateTo('/auth/login')
  }
})
