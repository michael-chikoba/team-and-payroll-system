import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authAPI } from '@/api/auth'
import { useRouter } from 'vue-router'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {
  const router = useRouter()

  const user = ref(null)
  const token = ref(localStorage.getItem('token'))
  const tokenExpiry = ref(localStorage.getItem('tokenExpiry'))
  const isLoading = ref(false)
  const isAuthenticated = computed(() => !!token.value && !!user.value && !isTokenExpired())
  const userRole = computed(() => user.value?.role)
  const isAdmin = computed(() => userRole.value === 'admin')
  const isManager = computed(() => userRole.value === 'manager')
  const isEmployee = computed(() => userRole.value === 'employee')

  // Configurable token lifetime (in milliseconds)
  // Default: 24 hours
  const TOKEN_LIFETIME = 24 * 60 * 60 * 1000
  
  // Activity timeout (auto-logout after inactivity)
  // Default: 2 hours
  const ACTIVITY_TIMEOUT = 2 * 60 * 60 * 1000
  
  let activityTimer = null
  let expiryCheckInterval = null

  // Check if token has expired
  function isTokenExpired() {
    if (!tokenExpiry.value) return false
    return Date.now() > parseInt(tokenExpiry.value)
  }

  // Update token expiry time
  function updateTokenExpiry() {
    const expiry = Date.now() + TOKEN_LIFETIME
    tokenExpiry.value = expiry.toString()
    localStorage.setItem('tokenExpiry', tokenExpiry.value)
  }

  // Reset activity timer
  function resetActivityTimer() {
    if (activityTimer) {
      clearTimeout(activityTimer)
    }

    if (isAuthenticated.value) {
      activityTimer = setTimeout(() => {
        console.log('Auto-logout due to inactivity')
        logout()
      }, ACTIVITY_TIMEOUT)
    }
  }

  // Set up activity listeners
  function setupActivityListeners() {
    const events = ['mousedown', 'keydown', 'scroll', 'touchstart', 'click']
    
    const handleActivity = () => {
      if (isAuthenticated.value) {
        resetActivityTimer()
      }
    }

    events.forEach(event => {
      window.addEventListener(event, handleActivity, { passive: true })
    })

    // Start the timer
    resetActivityTimer()
  }

  // Clean up activity listeners
  function cleanupActivityListeners() {
    if (activityTimer) {
      clearTimeout(activityTimer)
      activityTimer = null
    }
  }

  // Periodically check token expiry
  function startExpiryCheck() {
    if (expiryCheckInterval) {
      clearInterval(expiryCheckInterval)
    }

    // Check every minute
    expiryCheckInterval = setInterval(() => {
      if (isTokenExpired()) {
        console.log('Token expired, logging out')
        logout()
      }
    }, 60 * 1000)
  }

  function stopExpiryCheck() {
    if (expiryCheckInterval) {
      clearInterval(expiryCheckInterval)
      expiryCheckInterval = null
    }
  }

  // Function to set axios authorization header
  function setAxiosAuthHeader(authToken) {
    if (authToken) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${authToken}`
      axios.defaults.headers.common['Accept'] = 'application/json'
      axios.defaults.headers.common['Content-Type'] = 'application/json'
    } else {
      delete axios.defaults.headers.common['Authorization']
    }
  }

  // Load auth state from localStorage
  async function loadFromStorage() {
    const storedToken = localStorage.getItem('token')
    const storedUser = localStorage.getItem('user')
    const storedExpiry = localStorage.getItem('tokenExpiry')

    if (storedToken && storedUser && storedExpiry) {
      // Check if token is expired
      if (Date.now() > parseInt(storedExpiry)) {
        console.log('Stored token expired, clearing auth')
        clearAuth()
        return
      }

      try {
        // Set values first
        token.value = storedToken
        tokenExpiry.value = storedExpiry
        user.value = JSON.parse(storedUser)
        setAxiosAuthHeader(token.value)

        // Start activity monitoring and expiry checks BEFORE validation
        // This prevents logout during the fetchUser call
        setupActivityListeners()
        startExpiryCheck()

        // Validate by fetching fresh user data
        // If this fails, catch block will clean everything up
        await fetchUser()
        
        console.log('Auth state restored successfully')
      } catch (error) {
        console.error('Failed to restore auth state:', error)
        clearAuth()
      }
    }
  }

  function setAuth(userData, authToken) {
    user.value = userData
    token.value = authToken
    
    localStorage.setItem('token', authToken)
    localStorage.setItem('user', JSON.stringify(userData))
    
    updateTokenExpiry()
    setAxiosAuthHeader(authToken)
    
    // Start monitoring
    setupActivityListeners()
    startExpiryCheck()
  }

  function clearAuth() {
    user.value = null
    token.value = null
    tokenExpiry.value = null
    
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    localStorage.removeItem('tokenExpiry')
    
    setAxiosAuthHeader(null)
    
    // Clean up monitoring
    cleanupActivityListeners()
    stopExpiryCheck()
  }

  function hasRole(roles) {
    if (!user.value) return false
    if (Array.isArray(roles)) {
      return roles.includes(user.value.role)
    }
    return user.value.role === roles
  }

  async function login(credentials) {
    try {
      isLoading.value = true
      const response = await authAPI.login(credentials)
     
      setAuth(response.data.user, response.data.token)
     
      return response
    } catch (error) {
      clearAuth()
      throw error
    } finally {
      isLoading.value = false
    }
  }

  async function register(userData) {
    try {
      isLoading.value = true
      const response = await authAPI.register(userData)
     
      setAuth(response.data.user, response.data.token)
     
      return response
    } catch (error) {
      clearAuth()
      throw error
    } finally {
      isLoading.value = false
    }
  }

  async function logout() {
    try {
      await authAPI.logout()
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      clearAuth()
      router.push({ name: 'login' })
    }
  }

  async function fetchUser() {
    if (!token.value) return
    
    try {
      const response = await authAPI.getUser()
      user.value = response.data.user
      localStorage.setItem('user', JSON.stringify(user.value))
      
      // Refresh token expiry on successful API call
      updateTokenExpiry()
    } catch (error) {
      clearAuth()
      throw error
    }
  }

  async function forgotPassword(email) {
    return await authAPI.forgotPassword({ email })
  }

  async function resetPassword(data) {
    return await authAPI.resetPassword(data)
  }

  // Refresh token (extend session)
  async function refreshSession() {
    if (!isAuthenticated.value) return
    
    try {
      await fetchUser()
      console.log('Session refreshed')
    } catch (error) {
      console.error('Failed to refresh session:', error)
    }
  }

  // Initialize axios headers when store is created
  if (token.value) {
    setAxiosAuthHeader(token.value)
  }

  return {
    user,
    token,
    tokenExpiry,
    isLoading,
    isAuthenticated,
    userRole,
    isAdmin,
    isManager,
    isEmployee,
    setAuth,
    clearAuth,
    hasRole,
    login,
    register,
    logout,
    fetchUser,
    forgotPassword,
    resetPassword,
    loadFromStorage,
    refreshSession,
    resetActivityTimer,
    isTokenExpired
  }
})