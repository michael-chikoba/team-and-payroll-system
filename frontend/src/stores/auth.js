import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authAPI } from '@/api/auth'
import { useRouter } from 'vue-router'
import axios from 'axios'

export const useAuthStore = defineStore('auth', () => {
  const router = useRouter()

  // ─── State ───────────────────────────────────────────────────────────────────
  const user         = ref(null)
  const token        = ref(localStorage.getItem('token'))
  const tokenExpiry  = ref(localStorage.getItem('tokenExpiry'))
  const isLoading    = ref(false)
  const isLoggingOut = ref(false)
  const authLoaded   = ref(false)

  // ─── Computed ─────────────────────────────────────────────────────────────────
  const isAuthenticated   = computed(() => !!token.value && !!user.value && !isTokenExpired())
  const userRole          = computed(() => user.value?.role)
  const isAdmin           = computed(() => userRole.value === 'admin')
  const isManager         = computed(() => userRole.value === 'manager')
  const isEmployee        = computed(() => userRole.value === 'employee')
  const currentBusinessId = computed(() => user.value?.current_business_id || null)
  const userId            = computed(() => user.value?.id || null)

  // ─── Constants ────────────────────────────────────────────────────────────────
  const REFRESH_THRESHOLD = 2 * 60 * 60 * 1000  // refresh 2 h before expiry
  const ACTIVITY_TIMEOUT  = 2 * 60 * 60 * 1000  // idle logout after 2 h

  // ─── Internal timer / listener state ─────────────────────────────────────────
  let activityTimer             = null
  let refreshTimer              = null
  let expiryCheckInterval       = null
  let activityListenersAttached = false  // FIX: prevents duplicate window listeners

  // ─── Token helpers ────────────────────────────────────────────────────────────

  function isTokenExpired() {
    if (!tokenExpiry.value) return false
    return Date.now() > parseInt(tokenExpiry.value)
  }

  function updateTokenExpiry(expiresAt) {
    tokenExpiry.value = expiresAt
      ? new Date(expiresAt).getTime().toString()
      : (Date.now() + 24 * 60 * 60 * 1000).toString()
    localStorage.setItem('tokenExpiry', tokenExpiry.value)
  }

  /**
   * FIX: original code computed a negative refreshTime when the token was
   * already within the 2-hour threshold, making setTimeout fire instantly in
   * a tight loop. Now falls back to a 5-second delay instead.
   */
  function scheduleTokenRefresh() {
    if (refreshTimer) clearTimeout(refreshTimer)
    if (!tokenExpiry.value) return

    const timeUntilExpiry = parseInt(tokenExpiry.value) - Date.now()
    if (timeUntilExpiry <= 0) return  // already expired, the minute-check handles it

    const refreshIn = timeUntilExpiry > REFRESH_THRESHOLD
      ? timeUntilExpiry - REFRESH_THRESHOLD  // normal: refresh 2 h before expiry
      : 5_000                                // already close to expiry → refresh in 5 s

    refreshTimer = setTimeout(async () => {
      console.log('🔄 Auto-refreshing token...')
      try { await refreshToken() } catch { /* refreshToken handles logout on 401 */ }
    }, refreshIn)

    console.log(`⏰ Token refresh in ${Math.round(refreshIn / 60_000)} min`)
  }

  // ─── Activity / idle timer ────────────────────────────────────────────────────

  function resetActivityTimer() {
    if (activityTimer) clearTimeout(activityTimer)
    if (isAuthenticated.value && !isLoggingOut.value) {
      activityTimer = setTimeout(() => {
        console.log('🕒 Auto-logout: inactivity')
        logout()
      }, ACTIVITY_TIMEOUT)
    }
  }

  /** FIX: guard prevents stacking duplicate event listeners on window */
  function setupActivityListeners() {
    if (activityListenersAttached) return
    const events = ['mousedown', 'keydown', 'scroll', 'touchstart', 'click']
    const handle = () => {
      if (isAuthenticated.value && !isLoggingOut.value) resetActivityTimer()
    }
    events.forEach(e => window.addEventListener(e, handle, { passive: true }))
    activityListenersAttached = true
    resetActivityTimer()
  }

  // ─── Cleanup ──────────────────────────────────────────────────────────────────

  function cleanupTimers() {
    if (activityTimer)       { clearTimeout(activityTimer);        activityTimer       = null }
    if (refreshTimer)        { clearTimeout(refreshTimer);         refreshTimer        = null }
    if (expiryCheckInterval) { clearInterval(expiryCheckInterval); expiryCheckInterval = null }
    activityListenersAttached = false  // allow re-registration on next login
  }

  function startExpiryCheck() {
    if (expiryCheckInterval) clearInterval(expiryCheckInterval)
    expiryCheckInterval = setInterval(() => {
      if (isTokenExpired() && !isLoggingOut.value) {
        console.log('❌ Token expired — logging out')
        logout()
      }
    }, 60_000)
  }

  // ─── Axios header ─────────────────────────────────────────────────────────────

  function setAxiosAuthHeader(authToken) {
    if (authToken) {
      axios.defaults.headers.common['Authorization'] = `Bearer ${authToken}`
      axios.defaults.headers.common['Accept']        = 'application/json'
      axios.defaults.headers.common['Content-Type']  = 'application/json'
    } else {
      delete axios.defaults.headers.common['Authorization']
    }
  }

  // ─── loadFromStorage — TWO-PHASE ──────────────────────────────────────────────
  /**
   * PHASE 1 (immediate): read localStorage → restore reactive state →
   *   set authLoaded = true so the router guard unblocks and the correct
   *   page renders with zero login flicker.
   *
   * PHASE 2 (background, after UI is up): call the server to verify.
   *   • 401  → token definitively invalid/revoked → clearAuth + redirect
   *   • anything else (500, timeout, network blip) → keep session alive
   *
   * Result: a hard refresh NEVER logs the user out due to a slow or
   * temporarily unavailable server.
   */
  async function loadFromStorage() {
    if (authLoaded.value) return  // idempotent guard

    const storedToken  = localStorage.getItem('token')
    const storedUser   = localStorage.getItem('user')
    const storedExpiry = localStorage.getItem('tokenExpiry')

    // Nothing stored
    if (!storedToken || !storedUser || !storedExpiry) {
      console.log('ℹ️ No stored auth data — unauthenticated')
      authLoaded.value = true
      return
    }

    // Token definitively expired (client-side check)
    if (Date.now() > parseInt(storedExpiry)) {
      console.log('⚠️ Stored token expired — clearing auth')
      clearAuth()
      authLoaded.value = true
      return
    }

    // ── PHASE 1: Restore state immediately ────────────────────────────────────
    try {
      token.value       = storedToken
      tokenExpiry.value = storedExpiry
      user.value        = JSON.parse(storedUser)
    } catch {
      console.error('❌ Corrupt localStorage user data — clearing auth')
      clearAuth()
      authLoaded.value = true
      return
    }

    setAxiosAuthHeader(token.value)
    setupActivityListeners()
    startExpiryCheck()
    scheduleTokenRefresh()

    // CRITICAL: set authLoaded BEFORE the async server call so the router
    // guard unblocks immediately and the page renders without bouncing to /login
    authLoaded.value = true
    console.log('✅ Auth restored from localStorage (phase 1)')

    // ── PHASE 2: Background server verification (non-blocking) ───────────────
    _verifyTokenInBackground()
  }

  /**
   * Silently check the token with the server after the UI is already up.
   * Only a hard 401 ends the session — everything else is treated as transient.
   */
  async function _verifyTokenInBackground() {
    try {
      console.log('🌐 Background token verification...')
      const response = await authAPI.getUser()

      // Refresh user data with latest from server
      user.value = response.data.user || response.data
      localStorage.setItem('user', JSON.stringify(user.value))

      if (response.data.token_expires_at) {
        updateTokenExpiry(response.data.token_expires_at)
        scheduleTokenRefresh()
      }

      // Initialize business context
      if (user.value?.current_business_id) {
        try {
          const { useBusinessStore } = await import('./business')
          await useBusinessStore().fetchBusinesses()
          console.log('✅ Business context initialized')
        } catch (e) {
          console.warn('⚠️ Business context init failed (non-critical):', e)
        }
      }

      console.log('✅ Token verified with server')
    } catch (error) {
      const status = error.response?.status

      if (status === 401) {
        // Definitively invalid — server says so
        console.warn('🔒 Background verify: 401 — clearing session')
        clearAuth()
        const route = router.currentRoute?.value
        if (route?.meta?.requiresAuth !== false) {
          router.push({ name: 'Login' })
        }
      } else {
        // Network error, 5xx, timeout, CORS, etc. — preserve session
        console.warn(
          `⚠️ Background verify failed (status: ${status ?? 'network error'}) — session preserved`
        )
      }
    }
  }

  // ─── setAuth / clearAuth ──────────────────────────────────────────────────────

  function setAuth(userData, authToken, expiresAt) {
    user.value  = userData
    token.value = authToken
    localStorage.setItem('token', authToken)
    localStorage.setItem('user', JSON.stringify(userData))
    updateTokenExpiry(expiresAt)
    setAxiosAuthHeader(authToken)
    setupActivityListeners()
    startExpiryCheck()
    scheduleTokenRefresh()
    console.log('✅ Auth state set')
  }

  function clearAuth() {
    user.value        = null
    token.value       = null
    tokenExpiry.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('user')
    localStorage.removeItem('tokenExpiry')
    setAxiosAuthHeader(null)
    cleanupTimers()
    console.log('🗑️ Auth cleared')
  }

  // ─── Auth actions ─────────────────────────────────────────────────────────────

  function hasRole(roles) {
    if (!user.value) return false
    return Array.isArray(roles) ? roles.includes(user.value.role) : user.value.role === roles
  }

  async function login(credentials) {
    try {
      isLoading.value = true
      const response  = await authAPI.login(credentials)
      setAuth(response.data.user, response.data.token, response.data.expires_at)

      if (response.data.user?.current_business_id) {
        try {
          const { useBusinessStore } = await import('./business')
          await useBusinessStore().fetchBusinesses()
          console.log('✅ Business context initialized after login')
        } catch (e) { console.warn('⚠️ Business init failed:', e) }
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
      const response  = await authAPI.register(userData)
      setAuth(response.data.user, response.data.token, response.data.expires_at)
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
      await new Promise(r => setTimeout(r, 100))
      try { await authAPI.logout() }
      catch (e) { console.warn('⚠️ Logout API failed:', e.message) }
      try {
        const { useBusinessStore } = await import('./business')
        useBusinessStore().clearBusinessData()
      } catch { /* non-critical */ }
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

  /**
   * fetchUser: for explicit refreshes (e.g. after token renewal).
   * FIX: non-401 errors no longer wipe the session.
   */
  async function fetchUser() {
    if (!token.value) return
    try {
      const response = await authAPI.getUser()
      user.value = response.data.user || response.data
      localStorage.setItem('user', JSON.stringify(user.value))
      if (response.data.token_expires_at) {
        updateTokenExpiry(response.data.token_expires_at)
        scheduleTokenRefresh()
      }
    } catch (error) {
      if (error.response?.status === 401) {
        console.warn('🔒 fetchUser 401 — clearing auth')
        clearAuth()
        throw error
      }
      // non-401: preserve session
      console.warn('⚠️ fetchUser failed (non-401), session preserved:', error.message)
    }
  }

  async function refreshToken() {
    if (!token.value || isLoggingOut.value) return
    try {
      console.log('🔄 Refreshing token...')
      const response = await authAPI.refreshToken()
      token.value = response.data.token
      user.value  = response.data.user
      localStorage.setItem('token', token.value)
      localStorage.setItem('user', JSON.stringify(user.value))
      updateTokenExpiry(response.data.expires_at)
      setAxiosAuthHeader(token.value)
      scheduleTokenRefresh()
      console.log('✅ Token refreshed')
      return response.data
    } catch (error) {
      console.error('❌ Token refresh failed:', error)
      if (error.response?.status === 401) await logout()
      throw error
    }
  }

  // FIX: main.js called authStore.refreshSession() which did not exist
  async function refreshSession() {
    return await refreshToken()
  }

  async function forgotPassword(email) {
    return await authAPI.forgotPassword({ email })
  }

  async function resetPassword(data) {
    return await authAPI.resetPassword(data)
  }

  function updateCurrentBusiness(businessId) {
    if (user.value) {
      user.value.current_business_id = businessId
      localStorage.setItem('user', JSON.stringify(user.value))
      console.log('✅ current_business_id updated:', businessId)
    }
  }

  async function refreshUser() {
    try { await fetchUser() }
    catch (error) { console.error('refreshUser failed:', error); throw error }
  }

  // Bootstrap: restore axios header synchronously on module load
  if (token.value) setAxiosAuthHeader(token.value)

  // ─── Public API ───────────────────────────────────────────────────────────────
  return {
    user, token, tokenExpiry, isLoading, isLoggingOut, authLoaded,
    isAuthenticated, userRole, isAdmin, isManager, isEmployee,
    currentBusinessId, userId,
    setAuth, clearAuth, hasRole,
    login, register, logout,
    fetchUser, refreshToken, refreshSession,
    forgotPassword, resetPassword,
    loadFromStorage, resetActivityTimer, isTokenExpired,
    updateCurrentBusiness, refreshUser,
  }
})