import Notifications from '@kyvg/vue3-notification'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import axios from 'axios'
import ChatInterface from './components/ChatInterface.vue'
import IntegrationsModal from './components/IntegrationsModal.vue'
import './assets/styles/main.css'
import '@/assets/css/landing.css'
import './assets/base.css'
import '@/assets/css/shared-layout-styles.css'

// Set base URL for API requests
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000'
axios.defaults.baseURL = API_URL
axios.defaults.withCredentials = true
axios.defaults.headers.common['Accept'] = 'application/json'
axios.defaults.headers.common['Content-Type'] = 'application/json'

// Create app and pinia FIRST
const app = createApp(App)
const pinia = createPinia()

// Install pinia BEFORE doing anything else
app.use(pinia)

// Register global components
app.component('ChatInterface', ChatInterface)
app.component('IntegrationsModal', IntegrationsModal)

// Install notifications plugin
app.use(Notifications)

// Make notifications available globally
app.config.globalProperties.$notify = app.config.globalProperties.$notify || function() {}

// ============================================
// SERVICE WORKER REGISTRATION
// ============================================
async function registerServiceWorker() {
  if (!('serviceWorker' in navigator)) {
    console.warn('⚠️ Service Workers not supported in this browser')
    return null
  }

  const isDevelopment = import.meta.env.MODE === 'development'
  const forceRegister = import.meta.env.VITE_ENABLE_SW === 'true'
  
  if (isDevelopment && !forceRegister) {
    console.log('🔧 Service Worker registration skipped in development mode')
    console.log('   Set VITE_ENABLE_SW=true in .env to enable')
    return null
  }

  try {
    console.log('📝 Registering service worker...')
    
    const registration = await navigator.serviceWorker.register('/sw.js', {
      scope: '/',
      updateViaCache: 'none'
    })

    console.log('✅ Service Worker registered successfully')
    console.log('   📍 Scope:', registration.scope)
    console.log('   ⚙️ Active:', !!registration.active)

    // Handle updates
    registration.addEventListener('updatefound', () => {
      const newWorker = registration.installing
      console.log('🆕 New service worker found, installing...')

      newWorker.addEventListener('statechange', () => {
        console.log('🔄 Service worker state:', newWorker.state)
        
        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
          console.log('⚡ New service worker ready')
          
          // Optional: Auto-update
          if (confirm('New version available! Reload to update?')) {
            newWorker.postMessage({ type: 'SKIP_WAITING' })
            window.location.reload()
          }
        }
      })
    })

    // Listen for messages
    navigator.serviceWorker.addEventListener('message', (event) => {
      console.log('💬 Message from SW:', event.data)
      
      switch (event.data?.type) {
        case 'PUSH_RECEIVED':
          window.dispatchEvent(new CustomEvent('push-notification-received', {
            detail: event.data.notification
          }))
          break
        case 'NOTIFICATION_CLICKED':
          window.dispatchEvent(new CustomEvent('notification-clicked', {
            detail: { notificationId: event.data.notificationId }
          }))
          break
      }
    })

    await navigator.serviceWorker.ready
    console.log('✅ Service Worker ready')

    window.swRegistration = registration
    return registration
  } catch (error) {
    console.error('❌ Service Worker registration failed:', error)
    return null
  }
}

// ============================================
// AXIOS INTERCEPTORS
// ============================================
function setupAxiosInterceptors(authStore) {
  // Request interceptor
  axios.interceptors.request.use(
    (config) => {
      const token = authStore.token
      if (token) {
        config.headers.Authorization = `Bearer ${token}`
      }
      return config
    },
    (error) => {
      console.error('❌ Request error:', error)
      return Promise.reject(error)
    }
  )

  // Response interceptor
  axios.interceptors.response.use(
    (response) => {
      if (authStore.isAuthenticated) {
        authStore.resetActivityTimer()
      }
      return response
    },
    (error) => {
      const status = error.response?.status
      const url = error.config?.url

      if (status === 401) {
        console.log('🚫 401 Unauthorized - clearing auth')
        
        if (!url?.includes('/login') && !url?.includes('/register')) {
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
        console.log('🔄 419 CSRF Token Mismatch - refreshing')
        return axios.get('/sanctum/csrf-cookie')
          .then(() => axios.request(error.config))
      }

      return Promise.reject(error)
    }
  )

  console.log('✅ Axios interceptors configured')
}

// ============================================
// SESSION MANAGEMENT
// ============================================
function setupSessionRefresh(authStore) {
  const REFRESH_INTERVAL = 30 * 60 * 1000 // 30 minutes

  setInterval(() => {
    if (authStore.isAuthenticated && !authStore.isTokenExpired()) {
      console.log('🔄 Periodic session refresh')
      authStore.refreshSession().catch((error) => {
        console.error('❌ Session refresh failed:', error)
      })
    }
  }, REFRESH_INTERVAL)

  console.log('✅ Session refresh configured')
}

// ============================================
// ERROR HANDLERS
// ============================================
app.config.errorHandler = (err, instance, info) => {
  console.error('❌ Global error:', err)
  console.error('   Component:', instance?.$options?.name || 'Unknown')
  console.error('   Info:', info)
  console.error('   Stack:', err.stack)
}

app.config.warnHandler = (msg, instance, trace) => {
  if (import.meta.env.MODE === 'development') {
    console.warn('⚠️ Vue warning:', msg)
  }
}

// ============================================
// ROUTER ERROR HANDLING
// ============================================
function setupRouterErrorHandling() {
  router.onError((error) => {
    console.error('❌ Router error:', error)
    console.error('   Error details:', {
      message: error.message,
      stack: error.stack
    })
  })

  // Navigation guards with error handling
  router.beforeEach((to, from, next) => {
    console.log('🧭 Navigating to:', to.path)
    next()
  })

  router.afterEach((to, from, failure) => {
    if (failure) {
      console.error('❌ Navigation failed:', failure)
    }
  })

  console.log('✅ Router error handling configured')
}

// ============================================
// DEVELOPMENT HELPERS
// ============================================
if (import.meta.env.MODE === 'development') {
  window.debugApp = {
    checkSW: async () => {
      if (!('serviceWorker' in navigator)) {
        return { supported: false }
      }
      const reg = await navigator.serviceWorker.getRegistration('/')
      return {
        supported: true,
        registered: !!reg,
        active: !!reg?.active,
        scope: reg?.scope,
        controller: !!navigator.serviceWorker.controller
      }
    },
    
    checkNotifications: () => ({
      supported: 'Notification' in window,
      permission: Notification?.permission
    }),
    
    getSubscription: async () => {
      const reg = await navigator.serviceWorker.getRegistration('/')
      if (!reg) return null
      return await reg.pushManager.getSubscription()
    },
    
    unregisterSW: async () => {
      const regs = await navigator.serviceWorker.getRegistrations()
      for (const reg of regs) {
        await reg.unregister()
      }
      console.log('✅ All service workers unregistered')
      location.reload()
    },

    router: router,
    checkRoutes: () => {
      console.log('📋 All routes:', router.getRoutes())
      return router.getRoutes()
    }
  }

  console.log('🔧 Debug helpers available: window.debugApp')
}

// ============================================
// APP INITIALIZATION
// ============================================
const initializeApp = async () => {
  try {
    console.log('🚀 Initializing app...')
    console.log('   Environment:', import.meta.env.MODE)
    console.log('   API URL:', API_URL)
    
    // 1. Get CSRF cookie
    try {
      console.log('🔐 Fetching CSRF cookie...')
      await axios.get('/sanctum/csrf-cookie')
      console.log('✅ CSRF cookie fetched')
    } catch (error) {
      console.warn('⚠️ Failed to fetch CSRF cookie:', error.message)
      // Don't fail the app if CSRF cookie fetch fails
    }

    // 2. Import auth store (after pinia is installed)
    console.log('📦 Loading auth store...')
    const { useAuthStore } = await import('./stores/auth')
    const authStore = useAuthStore()
    console.log('✅ Auth store loaded')

    // 3. Setup axios interceptors
    setupAxiosInterceptors(authStore)

    // 4. Install router
    console.log('🔌 Installing router...')
    app.use(router)
    console.log('✅ Router installed')

    // 5. Setup router error handling
    setupRouterErrorHandling()
    
    // 6. Wait for router to be ready before mounting
    console.log('⏳ Waiting for router to be ready...')
    await router.isReady()
    console.log('✅ Router ready')

    // 7. Mount the app
    console.log('🎨 Mounting app to DOM...')
    app.mount('#app')
    console.log('✅ App mounted')

    // 8. Wait a bit before loading heavy features
    await new Promise(resolve => setTimeout(resolve, 500))

    // 9. Register service worker after mount (in background)
    if (typeof window !== 'undefined') {
      window.addEventListener('load', async () => {
        try {
          const registration = await registerServiceWorker()
          if (registration) {
            console.log('✅ Service Worker setup complete')
          }
        } catch (error) {
          console.error('❌ Service Worker setup failed:', error)
          // Don't fail the app if SW registration fails
        }
      })
    }

    // 10. Setup session refresh
    setupSessionRefresh(authStore)

    // 11. Log success
    console.log('🎉 App initialization complete')
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━')
    
  } catch (error) {
    console.error('❌ Failed to initialize app:', error)
    console.error('   Error details:', {
      message: error.message,
      stack: error.stack
    })
    
    // Show error page
    document.body.innerHTML = `
      <div style="
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100vh;
        font-family: system-ui, -apple-system, sans-serif;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        text-align: center;
        padding: 20px;
      ">
        <div>
          <h1 style="font-size: 2rem; margin-bottom: 1rem;">
            ⚠️ Failed to start application
          </h1>
          <p style="font-size: 1.2rem; margin-bottom: 2rem;">
            ${error.message || 'An unexpected error occurred'}
          </p>
          <details style="
            margin-bottom: 2rem;
            text-align: left;
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            border-radius: 8px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
          ">
            <summary style="cursor: pointer; font-weight: 600; margin-bottom: 0.5rem;">
              Technical Details
            </summary>
            <pre style="
              font-size: 0.875rem;
              overflow-x: auto;
              white-space: pre-wrap;
              word-wrap: break-word;
            ">${error.stack || 'No stack trace available'}</pre>
          </details>
          <button 
            onclick="location.reload()" 
            style="
              background: white;
              color: #667eea;
              border: none;
              padding: 12px 24px;
              font-size: 1rem;
              border-radius: 6px;
              cursor: pointer;
              font-weight: 600;
              box-shadow: 0 4px 6px rgba(0,0,0,0.1);
              transition: transform 0.2s;
            "
            onmouseover="this.style.transform='translateY(-2px)'"
            onmouseout="this.style.transform='translateY(0)'"
          >
            Reload Page
          </button>
        </div>
      </div>
    `
  }
}

// Start the app
console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━')
console.log('🚀 Starting Application...')
console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━')
initializeApp()