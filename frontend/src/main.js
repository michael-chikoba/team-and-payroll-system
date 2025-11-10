// main.js
import Notifications from '@kyvg/vue3-notification'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import axios from 'axios'

import './assets/styles/main.css'

// Set base URL for API requests
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000'
axios.defaults.baseURL = API_URL

// 👇 CRITICAL: Enable sending cookies with requests
axios.defaults.withCredentials = true

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)
app.use(Notifications)

// 👇 CRITICAL: Fetch CSRF cookie BEFORE mounting the app
const initializeApp = async () => {
  try {
    // Get CSRF cookie from Sanctum
    await axios.get('/sanctum/csrf-cookie')
    console.log('CSRF cookie fetched successfully')
  } catch (error) {
    console.warn('Failed to fetch CSRF cookie:', error)
    // App can still load, but auth will fail
  }

  // Add axios response interceptor for handling auth errors
  axios.interceptors.response.use(
    (response) => response,
    (error) => {
      if (error.response?.status === 401) {
        const { useAuthStore } = require('./stores/auth') // 👈 Dynamic import to avoid hoisting issues
        const authStore = useAuthStore()
        authStore.clearAuth()
        
        // Only redirect if not already on login page
        if (!window.location.pathname.includes('/login')) {
          router.push({ name: 'login' })
        }
      }
      return Promise.reject(error)
    }
  )

  app.mount('#app')
}

initializeApp()