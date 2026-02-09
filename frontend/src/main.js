import Notifications from '@kyvg/vue3-notification'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import axios from 'axios'
import ChatInterface from './components/ChatInterface.vue'
import IntegrationsModal from './components/IntegrationsModal.vue'
import './assets/styles/main.css'
import '@/assets/css/landing.css'
// main.js
import './assets/base.css'
import '@/assets/css/shared-layout-styles.css'

// Set base URL for API requests
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000'
axios.defaults.baseURL = API_URL
axios.defaults.withCredentials = true

const app = createApp(App)
const pinia = createPinia()

// Register global components
app.component('ChatInterface', ChatInterface)
app.component('IntegrationsModal', IntegrationsModal)

// Install plugins
app.use(pinia)
app.use(Notifications)

// Initialize app
const initializeApp = async () => {
  try {
    // Get CSRF cookie from Sanctum
    await axios.get('/sanctum/csrf-cookie')
    console.log('✅ CSRF cookie fetched successfully')
  } catch (error) {
    console.warn('⚠️ Failed to fetch CSRF cookie:', error)
  }

  // Import auth store AFTER pinia is installed
  const { useAuthStore } = await import('./stores/auth')
  const authStore = useAuthStore()

  // Add axios response interceptor for handling auth errors
  axios.interceptors.response.use(
    (response) => {
      // On successful API calls, reset activity timer if user is authenticated
      if (authStore.isAuthenticated) {
        authStore.resetActivityTimer()
      }
      return response
    },
    (error) => {
      if (error.response?.status === 401) {
        console.log('🚫 401 error received, clearing auth')
        authStore.clearAuth()
        
        // Only redirect if not already on login page
        if (!window.location.pathname.includes('/auth/login')) {
          router.push({ path: '/auth/login' })
        }
      }
      return Promise.reject(error)
    }
  )

  // DON'T load auth here - let the router guard handle it
  // This prevents double-loading race conditions
  console.log('✅ App initialized')

  // Optional: Refresh session periodically (every 30 minutes)
  setInterval(() => {
    if (authStore.isAuthenticated && !authStore.isTokenExpired()) {
      console.log('🔄 Periodic session refresh check')
      authStore.refreshSession()
    }
  }, 30 * 60 * 1000)

  // Mount app and router
  app.use(router)
  app.mount('#app')
}

initializeApp()