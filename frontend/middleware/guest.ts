// https://nuxt.com/docs/api/middleware/nuxt
import { useAuth } from '~/composables/useAuth'

export default defineNuxtRouteMiddleware((to) => {
  const { isAuthenticated } = useAuth()

  // Redirect authenticated users away from auth pages
  const authRoutes = ['/auth/login', '/auth/register', '/auth/forgot-password', '/auth/reset-password']

  if (isAuthenticated.value && authRoutes.includes(to.path)) {
    return navigateTo('/')
  }
})
