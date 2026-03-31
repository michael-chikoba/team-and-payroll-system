/**
 * src/router/guards.js
 */
import { watch } from 'vue'
import { useAuthStore } from '@/stores/auth'

// ─── Helper ───────────────────────────────────────────────────────────────────

/**
 * Returns a promise that resolves as soon as authStore.authLoaded === true.
 */
function waitForAuth(authStore) {
  if (authStore.authLoaded) return Promise.resolve()
  return new Promise(resolve => {
    const stop = watch(
      () => authStore.authLoaded,
      loaded => { if (loaded) { stop(); resolve() } },
      { immediate: true }
    )
  })
}

// ─── Role → dashboard map ─────────────────────────────────────────────────────

const DASHBOARD_MAP = {
  admin:    { name: 'AdminDashboard' },
  manager:  { name: 'ManagerDashboard' },
  employee: { name: 'EmployeeDashboard' },
}

function dashboardFor(role) {
  return DASHBOARD_MAP[role] || { name: 'EmployeeDashboard' }
}

// ─── Global navigation guard ──────────────────────────────────────────────────

export function setupRouterGuards(router) {
  // Track current navigation to detect loops
  let lastNavigation = null
  let loopCount = 0

  router.beforeEach(async (to, from, next) => {
    // Detect navigation loops
    const navKey = `${from.fullPath}->${to.fullPath}`
    if (lastNavigation === navKey) {
      loopCount++
      if (loopCount > 3) {
        console.error('🚫 Navigation loop detected, redirecting to login')
        loopCount = 0
        lastNavigation = null
        return next({ name: 'Login' })
      }
    } else {
      loopCount = 0
      lastNavigation = navKey
    }

    // Debug logging (remove in production)
    if (process.env.NODE_ENV !== 'production') {
      console.log('🔄 Navigation:', { 
        from: from.fullPath, 
        to: to.fullPath,
        meta: to.meta,
        name: to.name
      })
    }

    const authStore = useAuthStore()

    // Wait for localStorage restore before making any auth decision
    await waitForAuth(authStore)

    const isAuthenticated = authStore.isAuthenticated
    const isGuestOnly = to.meta?.guestOnly === true
    const requiresAuth = to.meta?.requiresAuth === true

    // ── 1. Handle guest-only routes (login, register, etc.) ─────────────────
    if (isGuestOnly) {
      if (isAuthenticated) {
        // Authenticated users go to their dashboard
        const dashboard = dashboardFor(authStore.userRole)
        console.log('Authenticated user on guest route → redirecting to dashboard')
        return next(dashboard)
      }
      // Unauthenticated users can access guest routes
      console.log('Guest route allowed for unauthenticated user')
      return next()
    }

    // ── 2. Handle routes that require authentication ────────────────────────
    if (requiresAuth && !isAuthenticated) {
      console.log('Protected route → redirecting to login')
      
      // Store the original destination to redirect back after login
      return next({ 
        name: 'Login', 
        query: { redirect: to.fullPath } 
      })
    }

    // ── 3. Handle role-restricted routes ────────────────────────────────────
    if (to.meta?.roles && Array.isArray(to.meta.roles) && to.meta.roles.length > 0) {
      // Double-check authentication
      if (!isAuthenticated) {
        return next({ name: 'Login', query: { redirect: to.fullPath } })
      }
      
      // Check if user's role is allowed
      if (!to.meta.roles.includes(authStore.userRole)) {
        console.warn(`🚫 Role "${authStore.userRole}" cannot access ${to.path}`)
        return next({ name: 'Unauthorized' })
      }
    }

    // ── 4. All checks passed ────────────────────────────────────────────────
    if (process.env.NODE_ENV !== 'production') {
      console.log('✅ Navigation allowed:', to.path)
    }
    next()
  })

  router.onError(error => {
    console.error('❌ Router error:', error)
    // Prevent infinite error loops
    if (error.message?.includes('Maximum call stack')) {
      window.location.href = '/auth/login'
    }
  })

  router.afterEach((to, from) => {
    if (process.env.NODE_ENV !== 'production') {
      console.log('✅ Navigation completed:', to.path)
    }
  })

  console.log('✅ Router guards configured')
}