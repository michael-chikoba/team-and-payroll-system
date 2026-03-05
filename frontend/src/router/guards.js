/**
 * src/router/guards.js
 *
 * WHY THE OLD GUARDS CAUSED HARD-REFRESH LOGOUTS:
 * authGuard / roleGuard / guestGuard were synchronous. On a hard refresh
 * Pinia is empty (user = null, authLoaded = false) when the first navigation
 * fires. The sync check !authStore.isAuthenticated was always true → redirect
 * to /login, even for a perfectly valid session.
 *
 * FIX: Every guard awaits waitForAuth() which blocks until loadFromStorage()
 * has finished phase 1 (localStorage restore). That takes < 1 ms normally.
 * The router only makes its decision once it knows the real auth state.
 *
 * FIX 2: requiresAuth default changed from (meta?.requiresAuth !== false) to
 * (meta?.requiresAuth === true). The old default treated EVERY route without
 * explicit meta as protected. Unauthenticated users were redirected to Login,
 * but if Login itself had no meta, it was also "protected" → infinite redirect
 * loop → Vue Router silently killed the navigation → white screen.
 */

import { watch } from 'vue'
import { useAuthStore } from '@/stores/auth'

// ─── Helper ───────────────────────────────────────────────────────────────────

/**
 * Returns a promise that resolves as soon as authStore.authLoaded === true.
 * If already true, resolves on the next microtask (effectively immediate).
 */
function waitForAuth(authStore) {
  if (authStore.authLoaded) return Promise.resolve()
  return new Promise(resolve => {
    const stop = watch(
      () => authStore.authLoaded,
      loaded => { if (loaded) { stop(); resolve() } }
    )
  })
}

// ─── Role → dashboard map (single source of truth) ───────────────────────────

const DASHBOARD_MAP = {
  admin:    { name: 'AdminDashboard' },
  manager:  { name: 'ManagerDashboard' },
  employee: { name: 'EmployeeDashboard' },
}

function dashboardFor(role) {
  return DASHBOARD_MAP[role] ?? { name: 'EmployeeDashboard' }
}

// ─── Guards ───────────────────────────────────────────────────────────────────

/**
 * authGuard — protects routes that require authentication.
 */
export const authGuard = async (to, from, next) => {
  const authStore = useAuthStore()
  await waitForAuth(authStore)

  if (!authStore.isAuthenticated) {
    return next({ name: 'Login', query: { redirect: to.fullPath } })
  }
  next()
}

/**
 * roleGuard — protects routes that require a specific role.
 * Usage in routes: beforeEnter: roleGuard(['admin', 'manager'])
 */
export const roleGuard = (roles) => async (to, from, next) => {
  const authStore = useAuthStore()
  await waitForAuth(authStore)

  if (!authStore.isAuthenticated) {
    return next({ name: 'Login', query: { redirect: to.fullPath } })
  }

  if (!roles.includes(authStore.user?.role)) {
    console.warn(`🚫 Role "${authStore.user?.role}" cannot access ${to.path}`)
    return next({ name: 'Unauthorized' })
  }

  next()
}

/**
 * guestGuard — prevents logged-in users from reaching /login, /register, etc.
 */
export const guestGuard = async (to, from, next) => {
  const authStore = useAuthStore()
  await waitForAuth(authStore)

  if (authStore.isAuthenticated) {
    return next(dashboardFor(authStore.user?.role))
  }
  next()
}

// ─── Global navigation guard ──────────────────────────────────────────────────
/**
 * Call setupRouterGuards(router) once in main.js BEFORE router.isReady().
 *
 * Route meta conventions:
 *   meta: { requiresAuth: true }   → protected route (must be EXPLICIT)
 *   meta: { guestOnly: true }      → /login, /register, landing pages
 *   meta: { roles: ['admin'] }     → role-restricted routes
 *
 * Routes with NO meta are public by default (landing, 404, etc.)
 */
export function setupRouterGuards(router) {
  router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore()

    // Wait for localStorage restore before making any auth decision
    await waitForAuth(authStore)

    const isAuthenticated = authStore.isAuthenticated
    const isGuestOnly     = to.meta?.guestOnly === true

    // FIX: Opt-in protection only. Routes must explicitly set requiresAuth: true.
    // The previous default (requiresAuth !== false) caused an infinite redirect:
    //   unauthenticated user → hits any route → guard redirects to Login →
    //   Login has no meta → treated as protected → redirects to Login → loop →
    //   Vue Router kills navigation silently → white screen.
    const requiresAuth = to.meta?.requiresAuth === true

    // ── Guest-only routes (/login, /register, landing, etc.) ─────────────────
    if (isGuestOnly && isAuthenticated) {
      return next(dashboardFor(authStore.userRole))
    }

    // ── Protected routes ──────────────────────────────────────────────────────
    if (requiresAuth && !isAuthenticated) {
      // Guard against redirect loops: if we're already on an auth path, just proceed
      if (to.path.startsWith('/auth/') || to.name === 'Login') {
        return next()
      }

      // Prefer named route; fall back to path if 'Login' doesn't exist
      const loginRoute = router.hasRoute('Login')
        ? { name: 'Login', query: { redirect: to.fullPath } }
        : { path: '/auth/login', query: { redirect: to.fullPath } }

      return next(loginRoute)
    }

    // ── Role-restricted routes ────────────────────────────────────────────────
    if (to.meta?.roles && !to.meta.roles.includes(authStore.userRole)) {
      console.warn(`🚫 Role "${authStore.userRole}" cannot access ${to.path}`)
      return next({ name: 'Unauthorized' })
    }

    next()
  })

  router.onError(error => {
    console.error('❌ Router error:', error)
  })

  router.afterEach((to, from, failure) => {
    if (failure) console.error('❌ Navigation failed:', failure)
  })

  console.log('✅ Router guards configured')
}