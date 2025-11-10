import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

// Use environment variable, fallback to current origin (for production)
const baseURL = import.meta.env.VITE_API_BASE_URL || window.location.origin

const api = axios.create({
  baseURL: `${baseURL}/api`,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest'
  }
})

// Request interceptor
api.interceptors.request.use(
  async (config) => {
    const authStore = useAuthStore()
    
    // Add bearer token if available
    if (authStore.token) {
      config.headers.Authorization = `Bearer ${authStore.token}`
    }
    
    // Get CSRF token for state-changing requests
    if (['post', 'put', 'patch', 'delete'].includes(config.method?.toLowerCase())) {
      // Get CSRF cookie if not already set
      if (!document.cookie.includes('XSRF-TOKEN')) {
        await axios.get(`${baseURL}/sanctum/csrf-cookie`, {
          withCredentials: true
        })
      }
      
      // Extract CSRF token from cookie
      const token = document.cookie
        .split('; ')
        .find(row => row.startsWith('XSRF-TOKEN='))
        ?.split('=')[1]
      
      if (token) {
        config.headers['X-XSRF-TOKEN'] = decodeURIComponent(token)
      }
    }
    
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor (keep your existing one)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    // Handle 401 Unauthorized
    if (error.response?.status === 401) {
      const authStore = useAuthStore()
      authStore.clearAuth()
      window.location.href = '/login'
    }
    
    // Handle 419 CSRF token mismatch
    if (error.response?.status === 419) {
      document.cookie.split(";").forEach((c) => {
        document.cookie = c
          .replace(/^ +/, "")
          .replace(/=.*/, "=;expires=" + new Date().toUTCString() + ";path=/")
      })
      
      console.error('CSRF token mismatch. Please refresh and try again.')
    }
    
    // Handle 403 Forbidden
    if (error.response?.status === 403) {
      console.error('Access forbidden:', error.response.data.message)
    }
    
    // Handle 500 Server Error
    if (error.response?.status === 500) {
      console.error('Server error. Please try again later.')
    }
    
    return Promise.reject(error)
  }
)

export default api