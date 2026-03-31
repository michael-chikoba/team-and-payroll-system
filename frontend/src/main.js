/**
 * src/main.js
 *
 * FIXES APPLIED:
 * 1. loadFromStorage() is now called BEFORE router.isReady() and app.mount()
 *    so auth state is restored before any navigation decision is made.
 *
 * 2. The 401 axios interceptor no longer fires during the initial load window
 *    (authStore.authLoaded === false) — prevents a race condition where the
 *    background verification 401 clears auth that was just restored.
 *
 * 3. setupSessionRefresh now calls authStore.refreshSession() which exists
 *    (was calling authStore.refreshSession() on a function that didn't exist,
 *    throwing every 30 minutes).
 *
 * 4. setupRouterGuards() is called before router.isReady() so the global
 *    async guard is in place for the very first navigation.
 */

import Notifications    from '@kyvg/vue3-notification'
import { createApp }    from 'vue'
import { createPinia }  from 'pinia'
import App              from './App.vue'
import router           from './router'
import axios            from 'axios'
import { useAuthStore } from '@/stores/auth'
import { setupRouterGuards } from '@/router/guards'
import ChatInterface    from './components/ChatInterface.vue'
import IntegrationsModal from './components/IntegrationsModal.vue'

import './assets/css/responsive.css'
import '@/assets/css/shared-layout-styles.css'
import '@/assets/css/global-attendance.css'
import './assets/styles/main.css'
import '@/assets/css/landing.css'
import './assets/base.css'

// ─── Axios base config ────────────────────────────────────────────────────────
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000'
axios.defaults.baseURL        = API_URL
axios.defaults.withCredentials = true
axios.defaults.headers.common['Accept']       = 'application/json'
axios.defaults.headers.common['Content-Type'] = 'application/json'

// ─── App + Pinia (must be created before any store is used) ──────────────────
const app   = createApp(App)
const pinia = createPinia()

app.use(pinia)

// Global components
app.component('ChatInterface',   ChatInterface)
app.component('IntegrationsModal', IntegrationsModal)
app.use(Notifications)
app.config.globalProperties.$notify = app.config.globalProperties.$notify || function() {}

// ─── Error handlers ───────────────────────────────────────────────────────────
app.config.errorHandler = (err, instance, info) => {
  console.error('❌ Global error:', err)
  console.error('   Component:', instance?.$options?.name ?? 'Unknown')
  console.error('   Info:', info)
}
app.config.warnHandler = (msg) => {
  if (import.meta.env.MODE === 'development') console.warn('⚠️ Vue:', msg)
}

// ─── Axios interceptors ───────────────────────────────────────────────────────
/**
 * FIX: The 401 interceptor previously fired unconditionally, which created a
 * race condition on hard refresh:
 *   loadFromStorage() restores token → fires _verifyTokenInBackground() →
 *   if server returns 401 (token revoked), BOTH the interceptor AND
 *   _verifyTokenInBackground would try to clearAuth() + redirect, sometimes
 *   in the wrong order.
 *
 * New behaviour:
 *   • During initial load (authLoaded === false): 401s are passed through
 *     silently — _verifyTokenInBackground() handles them correctly.
 *   • After load: 401s on non-auth endpoints clear the session and redirect.
 */
function setupAxiosInterceptors(authStore) {
  // Request: attach current token
  axios.interceptors.request.use(
    config => {
      const t = authStore.token
      if (t) config.headers.Authorization = `Bearer ${t}`
      return config
    },
    error => {
      console.error('❌ Request error:', error)
      return Promise.reject(error)
    }
  )

  // Response: handle 401 / 419 / activity reset
  axios.interceptors.response.use(
    response => {
      // Reset idle timer on every successful response
      if (authStore.isAuthenticated) authStore.resetActivityTimer()
      return response
    },
    error => {
      const status = error.response?.status
      const url    = error.config?.url ?? ''

      // ── 401 handling ─────────────────────────────────────────────────────
      if (status === 401) {
        const isAuthEndpoint = url.includes('/login') || url.includes('/register')

        // FIX: Do NOT react to 401s while auth is still loading.
        // _verifyTokenInBackground() will handle any 401 from the initial
        // /api/user call correctly without double-clearing.
        if (!authStore.authLoaded) {
          console.warn('⚠️ 401 during initial auth load — deferring to background verifier')
          return Promise.reject(error)
        }

        if (!isAuthEndpoint && authStore.isAuthenticated) {
          console.warn('🚫 401 on authenticated request — clearing session')
          authStore.clearAuth()
          if (!window.location.pathname.includes('/auth/')) {
            router.push({
              path: '/auth/login',
              query: { redirect: window.location.pathname }
            })
          }
        }
      }

      // ── 419 CSRF mismatch ─────────────────────────────────────────────────
      if (status === 419) {
        console.log('🔄 419 CSRF mismatch — refreshing cookie')
        return axios.get('/sanctum/csrf-cookie').then(() => axios.request(error.config))
      }

      return Promise.reject(error)
    }
  )

  console.log('✅ Axios interceptors configured')
}

// ─── Session refresh ──────────────────────────────────────────────────────────
// FIX: was calling authStore.refreshSession() which didn't exist in the old
// store. The new auth.js exports refreshSession() → calls refreshToken().
function setupSessionRefresh(authStore) {
  const REFRESH_INTERVAL = 30 * 60 * 1000 // 30 minutes
  setInterval(() => {
    if (authStore.isAuthenticated && !authStore.isTokenExpired()) {
      console.log('🔄 Periodic session refresh')
      authStore.refreshSession().catch(err => {
        console.error('❌ Session refresh failed:', err)
      })
    }
  }, REFRESH_INTERVAL)
  console.log('✅ Session refresh configured')
}

// ─── Service worker ───────────────────────────────────────────────────────────
async function registerServiceWorker() {
  if (!('serviceWorker' in navigator)) return null

  const isDev         = import.meta.env.MODE === 'development'
  const forceRegister = import.meta.env.VITE_ENABLE_SW === 'true'
  if (isDev && !forceRegister) {
    console.log('🔧 SW skipped in dev (set VITE_ENABLE_SW=true to enable)')
    return null
  }

  try {
    const registration = await navigator.serviceWorker.register('/sw.js', {
      scope: '/',
      updateViaCache: 'none',
    })
    console.log('✅ Service Worker registered, scope:', registration.scope)

    registration.addEventListener('updatefound', () => {
      const newWorker = registration.installing
      newWorker.addEventListener('statechange', () => {
        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
          if (confirm('New version available! Reload to update?')) {
            newWorker.postMessage({ type: 'SKIP_WAITING' })
            window.location.reload()
          }
        }
      })
    })

    navigator.serviceWorker.addEventListener('message', event => {
      switch (event.data?.type) {
        case 'PUSH_RECEIVED':
          window.dispatchEvent(new CustomEvent('push-notification-received', { detail: event.data.notification }))
          break
        case 'NOTIFICATION_CLICKED':
          window.dispatchEvent(new CustomEvent('notification-clicked', { detail: { notificationId: event.data.notificationId } }))
          break
      }
    })

    await navigator.serviceWorker.ready
    window.swRegistration = registration
    return registration
  } catch (error) {
    console.error('❌ SW registration failed:', error)
    return null
  }
}

// ─── Dev helpers ──────────────────────────────────────────────────────────────
if (import.meta.env.MODE === 'development') {
  window.debugApp = {
    checkSW: async () => {
      const reg = await navigator.serviceWorker?.getRegistration('/')
      return { supported: 'serviceWorker' in navigator, registered: !!reg, active: !!reg?.active }
    },
    checkNotifications: () => ({ supported: 'Notification' in window, permission: Notification?.permission }),
    unregisterSW: async () => {
      const regs = await navigator.serviceWorker?.getRegistrations() ?? []
      for (const r of regs) await r.unregister()
      console.log('✅ All SWs unregistered'); location.reload()
    },
    router,
    checkRoutes: () => router.getRoutes(),
  }
  console.log('🔧 Debug helpers: window.debugApp')
}

// ─── Bootstrap ────────────────────────────────────────────────────────────────
/**
 * CORRECT ORDER — every step depends on the one before it:
 *
 *  1. Pinia installed          (done above, before initializeApp)
 *  2. CSRF cookie              (some backends require this before any API call)
 *  3. authStore created        (safe because Pinia is ready)
 *  4. Axios interceptors       (need authStore ref)
 *  5. setupRouterGuards        (must be before router.isReady())
 *  6. loadFromStorage()        ← THE KEY FIX
 *     Restores user/token from localStorage, sets authLoaded = true.
 *     Router guard awaits authLoaded before deciding protected vs public.
 *  7. app.use(router)          (install router)
 *  8. router.isReady()         (initial navigation runs with correct auth state)
 *  9. app.mount()              (app renders the RIGHT page, no login bounce)
 * 10. Background: SW + session refresh
 */
async function initializeApp() {
  try {
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━')
    console.log('🚀 Starting Application...')
    console.log(`   Env: ${import.meta.env.MODE} | API: ${API_URL}`)
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━')

    // 1. CSRF cookie
    try {
      await axios.get('/sanctum/csrf-cookie')
      console.log('✅ CSRF cookie fetched')
    } catch (e) {
      console.warn('⚠️ CSRF cookie fetch failed (continuing):', e.message)
    }

    // 2. Auth store (Pinia already installed above)
    const authStore = useAuthStore()

    // 3. Axios interceptors
    setupAxiosInterceptors(authStore)

    // 4. Router guards (must be registered before first navigation)
    app.use(router)
    setupRouterGuards(router)

    // 5. ── LOAD FROM STORAGE (THE KEY FIX) ──────────────────────────────────
    //    Restores token + user from localStorage synchronously, then fires
    //    background server verification. authLoaded is set to true at the end
    //    of phase 1 (before any network call), so the router guard below
    //    gets the correct auth state immediately.
    await authStore.loadFromStorage()
    console.log('✅ Auth loaded — isAuthenticated:', authStore.isAuthenticated)

    // 6. Wait for router initial navigation (guard uses authLoaded which is now true)
    await router.isReady()
    console.log('✅ Router ready')

    // 7. Mount app — renders the correct page (dashboard or login) with no flicker
    app.mount('#app')
    console.log('✅ App mounted')

    // 8. Background tasks (non-blocking)
    window.addEventListener('load', async () => {
      try { await registerServiceWorker() } catch { /* non-critical */ }
    })
    setupSessionRefresh(authStore)

    console.log('🎉 App initialization complete')
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━')

  } catch (error) {
    console.error('❌ Failed to initialize app:', error)

    document.body.innerHTML = `
      <div style="
        display:flex;align-items:center;justify-content:center;
        height:100vh;font-family:system-ui,sans-serif;
        background:linear-gradient(135deg,#667eea,#764ba2);
        color:white;text-align:center;padding:20px;">
        <div>
          <h1 style="font-size:2rem;margin-bottom:1rem;">⚠️ Failed to start application</h1>
          <p style="font-size:1.1rem;margin-bottom:2rem;">${error.message || 'An unexpected error occurred'}</p>
          <details style="margin-bottom:2rem;text-align:left;background:rgba(255,255,255,0.1);
            padding:1rem;border-radius:8px;max-width:600px;margin:0 auto 2rem;">
            <summary style="cursor:pointer;font-weight:600;margin-bottom:.5rem;">Technical Details</summary>
            <pre style="font-size:.8rem;overflow-x:auto;white-space:pre-wrap;">${error.stack ?? 'No stack trace'}</pre>
          </details>
          <button onclick="location.reload()"
            style="background:white;color:#667eea;border:none;padding:12px 24px;
              font-size:1rem;border-radius:6px;cursor:pointer;font-weight:600;">
            Reload Page
          </button>
        </div>
      </div>`
  }
}

initializeApp()