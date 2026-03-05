// src/main.js  (only the changed sections shown — merge with your existing file)

import Notifications      from '@kyvg/vue3-notification'
import { createApp }      from 'vue'
import { createPinia }    from 'pinia'
import App                from './App.vue'
import router             from './router'
import axios              from 'axios'
import { useAuthStore }   from '@/stores/auth'
import { useLoadingStore } from '@/stores/loading'          // ← ADD
import { setupRouterGuards } from '@/router/guards'
import ChatInterface      from './components/ChatInterface.vue'
import IntegrationsModal  from './components/IntegrationsModal.vue'
import GlobalLoading      from './components/GlobalLoading.vue'  // ← ADD

import './assets/css/responsive.css'
import '@/assets/css/shared-layout-styles.css'
import '@/assets/css/global-attendance.css'
import './assets/styles/main.css'
import '@/assets/css/landing.css'
import './assets/base.css'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000'
axios.defaults.baseURL         = API_URL
axios.defaults.withCredentials = true
axios.defaults.headers.common['Accept']       = 'application/json'
axios.defaults.headers.common['Content-Type'] = 'application/json'

const app   = createApp(App)
const pinia = createPinia()

app.use(pinia)

app.component('ChatInterface',    ChatInterface)
app.component('IntegrationsModal', IntegrationsModal)
app.component('GlobalLoading',    GlobalLoading)   // ← ADD global registration
app.use(Notifications)
app.config.globalProperties.$notify = app.config.globalProperties.$notify || function() {}

app.config.errorHandler = (err, instance, info) => {
  console.error('❌ Global error:', err)
  console.error('   Component:', instance?.$options?.name ?? 'Unknown')
  console.error('   Info:', info)
}
app.config.warnHandler = (msg) => {
  if (import.meta.env.MODE === 'development') console.warn('⚠️ Vue:', msg)
}

// ─── Axios interceptors ───────────────────────────────────────────────────────
// Now also drives the loading store on every request/response.
function setupAxiosInterceptors(authStore, loadingStore) {   // ← ADD loadingStore param
  axios.interceptors.request.use(
    config => {
      const t = authStore.token
      if (t) config.headers.Authorization = `Bearer ${t}`

      // Show global spinner for every outgoing request
      // (skip background verification requests if you prefer quiet boot)
      if (!config._silent) loadingStore.show()               // ← ADD

      return config
    },
    error => {
      loadingStore.hide()                                     // ← ADD
      console.error('❌ Request error:', error)
      return Promise.reject(error)
    }
  )

  axios.interceptors.response.use(
    response => {
      loadingStore.hide()                                     // ← ADD
      if (authStore.isAuthenticated) authStore.resetActivityTimer()
      return response
    },
    error => {
      loadingStore.hide()                                     // ← ADD
      const status = error.response?.status
      const url    = error.config?.url ?? ''

      if (status === 401) {
        const isAuthEndpoint = url.includes('/login') || url.includes('/register')
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

      if (status === 419) {
        console.log('🔄 419 CSRF mismatch — refreshing cookie')
        return axios.get('/sanctum/csrf-cookie').then(() => axios.request(error.config))
      }

      return Promise.reject(error)
    }
  )

  console.log('✅ Axios interceptors configured')
}

function setupSessionRefresh(authStore) {
  const REFRESH_INTERVAL = 30 * 60 * 1000
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

async function registerServiceWorker() {
  if (!('serviceWorker' in navigator)) return null
  const isDev         = import.meta.env.MODE === 'development'
  const forceRegister = import.meta.env.VITE_ENABLE_SW === 'true'
  if (isDev && !forceRegister) {
    console.log('🔧 SW skipped in dev')
    return null
  }
  try {
    const registration = await navigator.serviceWorker.register('/sw.js', {
      scope: '/',
      updateViaCache: 'none',
    })
    console.log('✅ SW registered, scope:', registration.scope)
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

// ─── Bootstrap ────────────────────────────────────────────────────────────────
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

    // 2. Stores
    const authStore    = useAuthStore()
    const loadingStore = useLoadingStore()                    // ← ADD

    // Show spinner immediately during boot
    loadingStore.show()                                       // ← ADD

    // 3. Axios interceptors
    setupAxiosInterceptors(authStore, loadingStore)           // ← pass loadingStore

    // 4. Router + guards
    app.use(router)
    setupRouterGuards(router)

    // 5. Load auth from storage
    await authStore.loadFromStorage()
    console.log('✅ Auth loaded — isAuthenticated:', authStore.isAuthenticated)

    // 6. Wait for router
    await router.isReady()
    console.log('✅ Router ready')

    // 7. Mount app
    app.mount('#app')
    console.log('✅ App mounted')

    // 8. Hide the boot spinner now that the correct page is rendered
    loadingStore.hide()                                       // ← ADD

    // 9. Background tasks
    window.addEventListener('load', async () => {
      try { await registerServiceWorker() } catch { /* non-critical */ }
    })
    setupSessionRefresh(authStore)

    console.log('🎉 App initialization complete')
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━')

  } catch (error) {
    console.error('❌ Failed to initialize app:', error)
    document.body.innerHTML = `
      <div style="display:flex;align-items:center;justify-content:center;
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