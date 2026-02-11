// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2024-04-03',
  future: {
    compatibilityVersion: 4
  },
  devtools: { enabled: true },
  modules: [],
  css: ['~/assets/css/main.css'],
  app: {
    head: {
      title: 'Koomky',
      meta: [
        { charset: 'utf-8' },
        { name: 'viewport', content: 'width=device-width, initial-scale=1' }
      ]
    },
    pageTransition: { name: 'page', mode: 'out-in' }
  },
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE_URL || 'http://localhost/api/v1'
    }
  },
  typescript: {
    strict: true,
    typeCheck: true
  },
  vite: {
    build: {
      maxWarnings: 0
    }
  }
})
