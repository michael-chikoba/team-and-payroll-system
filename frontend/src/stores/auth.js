import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authAPI } from '@/api/auth'
import { useRouter } from 'vue-router'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {
  const router = useRouter()
  
  const user = ref(null)
  const token = ref(localStorage.getItem('token'))
  const isLoading = ref(false)

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const userRole = computed(() => user.value?.role)
  const isAdmin = computed(() => userRole.value === 'admin')
  const isManager = computed(() => userRole.value === 'manager')
  const isEmployee = computed(() => userRole.value === 'employee')

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

  function setAuth(userData, authToken) {
    user.value = userData
    token.value = authToken
    localStorage.setItem('token', authToken)
    setAxiosAuthHeader(authToken)
  }

  function clearAuth() {
    user.value = null
    token.value = null
    localStorage.removeItem('token')
    setAxiosAuthHeader(null)
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

  // Initialize axios headers when store is created
  if (token.value) {
    setAxiosAuthHeader(token.value)
  }

  return {
    user,
    token,
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
    resetPassword
  }
})