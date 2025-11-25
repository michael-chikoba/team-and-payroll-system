import Notifications from '@kyvg/vue3-notification'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import axios from 'axios'
import { useAuthStore } from './stores/auth'

import './assets/styles/main.css'

// Set base URL for API requests
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000'
axios.defaults.baseURL = API_URL

// Enable sending cookies with requests
axios.defaults.withCredentials = true

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(Notifications)

const initializeApp = async () => {
  try {
    // Get CSRF cookie from Sanctum
    await axios.get('/sanctum/csrf-cookie')
    console.log('CSRF cookie fetched successfully')
  } catch (error) {
    console.warn('Failed to fetch CSRF cookie:', error)
  }

  // Initialize auth store
  const authStore = useAuthStore()

  // Add axios response interceptor for handling auth errors
  axios.interceptors.response.use(
    (response) => {
      // On successful API calls, reset activity timer if user is authenticated
      const authStore = useAuthStore()
      if (authStore.token && authStore.user) {
        authStore.resetActivityTimer()
      }
      return response
    },
    (error) => {
      const authStore = useAuthStore()
      if (error.response?.status === 401) {
        console.log('401 error received, clearing auth')
        authStore.clearAuth()
        
        // Only redirect if not already on login page
        if (!window.location.pathname.includes('/login')) {
          router.push({ name: 'login' })
        }
      }
      return Promise.reject(error)
    }
  )

  // Restore auth state on app init
  try {
    await authStore.loadFromStorage()
    console.log('App initialized, auth status:', authStore.isAuthenticated)
  } catch (error) {
    console.error('Failed to load auth from storage:', error)
  }

  // Optional: Refresh session periodically (every 30 minutes)
  setInterval(() => {
    if (authStore.token && authStore.user && !authStore.isTokenExpired()) {
      authStore.refreshSession()
    }
  }, 30 * 60 * 1000)

  app.mount('#app')
}

initializeApp()