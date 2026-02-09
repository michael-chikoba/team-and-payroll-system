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
  const isLoggingOut = ref(false)
  const authLoaded = ref(false)
 
  const isAuthenticated = computed(() => !!token.value && !!user.value && !isTokenExpired())
  const userRole = computed(() => user.value?.role)
  const isAdmin = computed(() => userRole.value === 'admin')
  const isManager = computed(() => userRole.value === 'manager')
  const isEmployee = computed(() => userRole.value === 'employee')
 
  // NEW: Business context getters
  const currentBusinessId = computed(() => user.value?.current_business_id || null)
  const userId = computed(() => user.value?.id || null)
  const REFRESH_THRESHOLD = 2 * 60 * 60 * 1000 // 2 hours
  const ACTIVITY_TIMEOUT = 2 * 60 * 60 * 1000 // 2 hours
 
  let activityTimer = null
  let refreshTimer = null
  let expiryCheckInterval = null
  function isTokenExpired() {
    if (!tokenExpiry.value) return false
    return Date.now() > parseInt(tokenExpiry.value)
  }
  function updateTokenExpiry(expiresAt) {
    if (expiresAt) {
      tokenExpiry.value = new Date(expiresAt).getTime().toString()
    } else {
      tokenExpiry.value = (Date.now() + (24 * 60 * 60 * 1000)).toString()
    }
    localStorage.setItem('tokenExpiry', tokenExpiry.value)
  }
  function scheduleTokenRefresh() {
    if (refreshTimer) {
      clearTimeout(refreshTimer)
    }
    if (!tokenExpiry.value) return
    const expiry = parseInt(tokenExpiry.value)
    const now = Date.now()
    const timeUntilExpiry = expiry - now
    const refreshTime = timeUntilExpiry - REFRESH_THRESHOLD
    if (refreshTime > 0) {
      refreshTimer = setTimeout(async () => {
        console.log('🔄 Auto-refreshing token...')
        await refreshToken()
      }, refreshTime)
     
      console.log(`⏰ Token refresh scheduled in ${Math.round(refreshTime / 60000)} minutes`)
    }
  }
  function resetActivityTimer() {
    if (activityTimer) {
      clearTimeout(activityTimer)
    }
    if (isAuthenticated.value && !isLoggingOut.value) {
      activityTimer = setTimeout(() => {
        console.log('🕒 Auto-logout due to inactivity')
        logout()
      }, ACTIVITY_TIMEOUT)
    }
  }
  function setupActivityListeners() {
    const events = ['mousedown', 'keydown', 'scroll', 'touchstart', 'click']
   
    const handleActivity = () => {
      if (isAuthenticated.value && !isLoggingOut.value) {
        resetActivityTimer()
      }
    }
    events.forEach(event => {
      window.addEventListener(event, handleActivity, { passive: true })
    })
    resetActivityTimer()
  }
  function cleanupTimers() {
    if (activityTimer) {
      clearTimeout(activityTimer)
      activityTimer = null
    }
    if (refreshTimer) {
      clearTimeout(refreshTimer)
      refreshTimer = null
    }
    if (expiryCheckInterval) {
      clearInterval(expiryCheckInterval)
      expiryCheckInterval = null
    }
  }
  function startExpiryCheck() {
    if (expiryCheckInterval) {
      clearInterval(expiryCheckInterval)
    }
    expiryCheckInterval = setInterval(() => {
      if (isTokenExpired() && !isLoggingOut.value) {
        console.log('❌ Token expired, logging out')
        logout()
      }
    }, 60 * 1000) // Check every minute
  }
  function setAxiosAuthHeader(authToken) {
    if (authToken) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${authToken}`
      axios.defaults.headers.common['Accept'] = 'application/json'
      axios.defaults.headers.common['Content-Type'] = 'application/json'
    } else {
      delete axios.defaults.headers.common['Authorization']
    }
  }
  async function loadFromStorage() {
    // Prevent loading multiple times
    if (authLoaded.value) {
      console.log('⏭️ Auth already loaded, skipping...')
      return
    }
    console.log('🔍 === LOAD FROM STORAGE DEBUG ===')
    const storedToken = localStorage.getItem('token')
    const storedUser = localStorage.getItem('user')
    const storedExpiry = localStorage.getItem('tokenExpiry')
    console.log('📦 LocalStorage contents:', {
      hasToken: !!storedToken,
      tokenPreview: storedToken ? storedToken.substring(0, 20) + '...' : null,
      hasUser: !!storedUser,
      userEmail: storedUser ? JSON.parse(storedUser).email : null,
      hasExpiry: !!storedExpiry,
      expiryDate: storedExpiry ? new Date(parseInt(storedExpiry)).toISOString() : null,
      currentTime: new Date().toISOString(),
      isExpired: storedExpiry ? Date.now() > parseInt(storedExpiry) : null
    })
    if (!storedToken || !storedUser || !storedExpiry) {
      console.log('⚠️ Missing auth data in localStorage')
      authLoaded.value = true
      return
    }
    if (Date.now() > parseInt(storedExpiry)) {
      console.log('⚠️ Stored token expired, clearing auth')
      clearAuth()
      authLoaded.value = true
      return
    }
    try {
      console.log('🔧 Restoring auth state...')
      token.value = storedToken
      tokenExpiry.value = storedExpiry
      user.value = JSON.parse(storedUser)
     
      console.log('🔑 Setting axios header...')
      setAxiosAuthHeader(token.value)
      console.log('👂 Setting up activity listeners...')
      setupActivityListeners()
      startExpiryCheck()
      scheduleTokenRefresh()
      console.log('🌐 Verifying token with server...')
      await fetchUser()
     
      // NEW: Initialize business store if user has businesses
      if (user.value?.current_business_id) {
        try {
          const { useBusinessStore } = await import('./business')
          const businessStore = useBusinessStore()
          await businessStore.fetchBusinesses()
          console.log('✅ Business context initialized')
        } catch (error) {
          console.warn('⚠️ Failed to initialize business context:', error)
        }
      }
     
      authLoaded.value = true
      console.log('✅ Auth state restored from storage successfully')
    } catch (error) {
      console.error('❌ Failed to restore auth state:', error)
      console.error('Error details:', {
        name: error.name,
        message: error.message,
        stack: error.stack,
        response: error.response?.data
      })
      clearAuth()
      authLoaded.value = true
    }
    console.log('🔍 === LOAD FROM STORAGE DEBUG END ===\n')
  }
  function setAuth(userData, authToken, expiresAt) {
    user.value = userData
    token.value = authToken
   
    localStorage.setItem('token', authToken)
    localStorage.setItem('user', JSON.stringify(userData))
   
    updateTokenExpiry(expiresAt)
    setAxiosAuthHeader(authToken)
   
    setupActivityListeners()
    startExpiryCheck()
    scheduleTokenRefresh()
   
    console.log('✅ Auth state set successfully')
  }
  function clearAuth() {
    user.value = null
    token.value = null
    tokenExpiry.value = null
   
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    localStorage.removeItem('tokenExpiry')
   
    setAxiosAuthHeader(null)
    cleanupTimers()
   
    console.log('🗑️ Auth state cleared')
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
     
      setAuth(
        response.data.user,
        response.data.token,
        response.data.expires_at
      )
     
      // NEW: Initialize business store after login
      if (response.data.user?.current_business_id) {
        try {
          const { useBusinessStore } = await import('./business')
          const businessStore = useBusinessStore()
          await businessStore.fetchBusinesses()
          console.log('✅ Business context initialized after login')
        } catch (error) {
          console.warn('⚠️ Failed to initialize business context:', error)
        }
      }
     
      console.log('✅ Login successful')
      return response
    } catch (error) {
      console.error('❌ Login error:', error)
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
     
      setAuth(
        response.data.user,
        response.data.token,
        response.data.expires_at
      )
     
      console.log('✅ Registration successful')
      return response
    } catch (error) {
      console.error('❌ Registration error:', error)
      clearAuth()
      throw error
    } finally {
      isLoading.value = false
    }
  }
  async function logout() {
    if (isLoggingOut.value) return
    isLoggingOut.value = true
    console.log('🚪 Logging out...')
    try {
      window.dispatchEvent(new CustomEvent('user-logging-out'))
      await new Promise(resolve => setTimeout(resolve, 100))
     
      try {
        await authAPI.logout()
      } catch (apiError) {
        console.warn('⚠️ Logout API failed:', apiError.message)
      }
     
      // NEW: Clear business store
      try {
        const { useBusinessStore } = await import('./business')
        const businessStore = useBusinessStore()
        businessStore.clearBusinessData()
        console.log('✅ Business context cleared')
      } catch (error) {
        console.warn('⚠️ Failed to clear business context:', error)
      }
     
      clearAuth()
      await router.push({ name: 'Login' })
     
      console.log('✅ Logout complete')
    } catch (error) {
      console.error('❌ Logout error:', error)
      clearAuth()
      router.push({ name: 'Login' })
    } finally {
      isLoggingOut.value = false
    }
  }
  async function fetchUser() {
    if (!token.value) {
      console.log('⚠️ fetchUser: No token available')
      return
    }
   
    console.log('🌐 Fetching user from API...')
    console.log(' - Token:', token.value.substring(0, 20) + '...')
    console.log(' - Authorization header:', axios.defaults.headers.common['Authorization'])
   
    try {
      const response = await authAPI.getUser()
      console.log('✅ User fetched successfully:', response.data.user?.email || response.data.email)
     
      // Handle both response formats
      user.value = response.data.user || response.data
      localStorage.setItem('user', JSON.stringify(user.value))
     
      if (response.data.token_expires_at) {
        console.log('🔄 Updating token expiry:', response.data.token_expires_at)
        updateTokenExpiry(response.data.token_expires_at)
        scheduleTokenRefresh()
      }
    } catch (error) {
      console.error('❌ Failed to fetch user:', {
        status: error.response?.status,
        statusText: error.response?.statusText,
        data: error.response?.data,
        message: error.message
      })
     
      if (error.response?.status === 401) {
        console.log('🚪 401 Unauthorized - clearing auth and redirecting')
        clearAuth()
        throw error
      }
    }
  }
  async function refreshToken() {
    if (!token.value || isLoggingOut.value) return
    try {
      console.log('🔄 Refreshing token...')
     
      const response = await authAPI.refreshToken()
     
      token.value = response.data.token
      user.value = response.data.user
     
      localStorage.setItem('token', token.value)
      localStorage.setItem('user', JSON.stringify(user.value))
     
      updateTokenExpiry(response.data.expires_at)
      setAxiosAuthHeader(token.value)
      scheduleTokenRefresh()
     
      console.log('✅ Token refreshed successfully')
      return response.data
    } catch (error) {
      console.error('❌ Token refresh failed:', error)
      if (error.response?.status === 401) {
        await logout()
      }
      throw error
    }
  }
  async function forgotPassword(email) {
    return await authAPI.forgotPassword({ email })
  }
  async function resetPassword(data) {
    return await authAPI.resetPassword(data)
  }
  // NEW: Update user's current business (called from business store)
  function updateCurrentBusiness(businessId) {
    if (user.value) {
      user.value.current_business_id = businessId
      localStorage.setItem('user', JSON.stringify(user.value))
      console.log('✅ User current_business_id updated:', businessId)
    }
  }
  // NEW: Refresh user data from server
  async function refreshUser() {
    try {
      await fetchUser()
    } catch (error) {
      console.error('Failed to refresh user:', error)
      throw error
    }
  }
  // Initialize axios header if token exists
  if (token.value) {
    setAxiosAuthHeader(token.value)
  }
  return {
    user,
    token,
    tokenExpiry,
    isLoading,
    isLoggingOut,
    isAuthenticated,
    userRole,
    isAdmin,
    isManager,
    isEmployee,
    authLoaded,
    // NEW: Business context
    currentBusinessId,
    userId,
    updateCurrentBusiness,
    refreshUser,
    // Existing methods
    setAuth,
    clearAuth,
    hasRole,
    login,
    register,
    logout,
    fetchUser,
    refreshToken,
    forgotPassword,
    resetPassword,
    loadFromStorage,
    resetActivityTimer,
    isTokenExpired
  }
})