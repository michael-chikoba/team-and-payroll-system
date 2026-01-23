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
  const isAuthenticated = computed(() => !!token.value && !!user.value && !isTokenExpired())
  const userRole = computed(() => user.value?.role)
  const isAdmin = computed(() => userRole.value === 'admin')
  const isManager = computed(() => userRole.value === 'manager')
  const isEmployee = computed(() => userRole.value === 'employee')

  const TOKEN_LIFETIME = 24 * 60 * 60 * 1000
  const ACTIVITY_TIMEOUT = 2 * 60 * 60 * 1000
  
  let activityTimer = null
  let expiryCheckInterval = null

  function isTokenExpired() {
    if (!tokenExpiry.value) return false
    return Date.now() > parseInt(tokenExpiry.value)
  }

  function updateTokenExpiry() {
    const expiry = Date.now() + TOKEN_LIFETIME
    tokenExpiry.value = expiry.toString()
    localStorage.setItem('tokenExpiry', tokenExpiry.value)
  }

  function resetActivityTimer() {
    if (activityTimer) {
      clearTimeout(activityTimer)
    }

    if (isAuthenticated.value && !isLoggingOut.value) {
      activityTimer = setTimeout(() => {
        console.log('Auto-logout due to inactivity')
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

  function cleanupActivityListeners() {
    if (activityTimer) {
      clearTimeout(activityTimer)
      activityTimer = null
    }
  }

  function startExpiryCheck() {
    if (expiryCheckInterval) {
      clearInterval(expiryCheckInterval)
    }

    expiryCheckInterval = setInterval(() => {
      if (isTokenExpired() && !isLoggingOut.value) {
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

  function setAxiosAuthHeader(authToken) {
    console.log('🔧 Setting Axios auth header:', authToken ? 'Token present' : 'No token')
    if (authToken) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${authToken}`
      axios.defaults.headers.common['Accept'] = 'application/json'
      axios.defaults.headers.common['Content-Type'] = 'application/json'
     // console.log('✅ Axios headers set:', axios.defaults.headers.common)
    } else {
      delete axios.defaults.headers.common['Authorization']
      console.log('🗑️ Axios auth header removed')
    }
  }

  async function loadFromStorage() {
   // console.log('📂 Loading auth from storage...')
    const storedToken = localStorage.getItem('token')
    const storedUser = localStorage.getItem('user')
    const storedExpiry = localStorage.getItem('tokenExpiry')

 

    if (storedToken && storedUser && storedExpiry) {
      if (Date.now() > parseInt(storedExpiry)) {
       // console.log('⚠️ Stored token expired, clearing auth')
        clearAuth()
        return
      }

      try {
        token.value = storedToken
        tokenExpiry.value = storedExpiry
        user.value = JSON.parse(storedUser)
        setAxiosAuthHeader(token.value)

        setupActivityListeners()
        startExpiryCheck()

        await fetchUser()
        
      //  console.log('✅ Auth state restored successfully')
      } catch (error) {
        console.error('❌ Failed to restore auth state:', error)
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
    
    setupActivityListeners()
    startExpiryCheck()
    
  }

  function clearAuth() {
   // console.log('🗑️ Clearing auth state...')
    user.value = null
    token.value = null
    tokenExpiry.value = null
    
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    localStorage.removeItem('tokenExpiry')
    
    setAxiosAuthHeader(null)
    
    cleanupActivityListeners()
    stopExpiryCheck()
    
   // console.log('✅ Auth state cleared')
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
    //  console.log('📤 Calling authAPI.login()...')
      
      const response = await authAPI.login(credentials)
      
     
      
      // Check response structure
      if (!response.data) {
        console.error('❌ No data in response!')
        throw new Error('Invalid response: no data')
      }
      
      if (!response.data.user) {
        console.error('❌ No user in response data!')
        console.error('Response data keys:', Object.keys(response.data))
        throw new Error('Invalid response: no user data')
      }
      
      if (!response.data.token) {
        console.error('❌ No token in response data!')
        console.error('Response data keys:', Object.keys(response.data))
        throw new Error('Invalid response: no token')
      }
      
      
     
      setAuth(response.data.user, response.data.token)
    
     
      return response
    } catch (error) {
      console.log('=== LOGIN ERROR IN STORE ===')
      console.error('Error:', error)
      console.error('Error message:', error.message)
      console.error('Error response:', error.response)
      
      if (error.response) {
        console.error('Response status:', error.response.status)
        console.error('Response data:', error.response.data)
        console.error('Response headers:', error.response.headers)
      }
      
      clearAuth()
      throw error
    } finally {
      isLoading.value = false
      console.log('=== AUTH STORE LOGIN END ===\n')
    }
  }

  async function register(userData) {
   
    
    try {
      isLoading.value = true
     
      
      const response = await authAPI.register(userData)
      
      
     
      setAuth(response.data.user, response.data.token)
      
    
     
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
    // Prevent multiple logout calls
    if (isLoggingOut.value) {
    
      return
    }

    isLoggingOut.value = true
    

    try {
      // Step 1: Emit logout event BEFORE clearing anything
      window.dispatchEvent(new CustomEvent('user-logging-out'))
     
      // Step 2: Wait a moment for components to clean up
      await new Promise(resolve => setTimeout(resolve, 100))
      
      // Step 3: Call logout API (don't fail if this errors)
      try {
        await authAPI.logout()
        //console.log('✅ Logout API call successful')
      } catch (apiError) {
        console.warn('⚠️ Logout API failed (continuing anyway):', apiError.message)
      }
      
      // Step 4: Clear all auth state
      clearAuth()
     
      
      // Step 5: Navigate to login
      await router.push({ name: 'login' })
      //console.log('✅ Redirected to login')
      
    } catch (error) {
      console.error('❌ Logout error:', error)
      // Even if something fails, ensure we clear auth
      clearAuth()
      router.push({ name: 'login' })
    } finally {
      isLoggingOut.value = false
    //  console.log('🔓 Logout process complete\n')
    }
  }

  async function fetchUser() {
    if (!token.value) {
     // console.log('⚠️ No token, skipping fetchUser')
      return
    }
    
 
    
    try {
      const response = await authAPI.getUser()
     
      
      user.value = response.data.user
      localStorage.setItem('user', JSON.stringify(user.value))
      
      updateTokenExpiry()
    } catch (error) {
      console.error('❌ Failed to fetch user:', error)
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

  async function refreshSession() {
    if (!isAuthenticated.value) return
    
   
    
    try {
      await fetchUser()
      
    } catch (error) {
      console.error('❌ Failed to refresh session:', error)
    }
  }

  // Initialize axios header if token exists
  if (token.value) {
  //  console.log('🔧 Initializing axios with existing token')
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