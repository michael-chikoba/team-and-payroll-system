import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import routes from './routes'
const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})
let authLoadPromise = null
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  console.log('🛣️ Router navigation:', {
    from: from.path,
    to: to.path,
    toName: to.name
  })
  // Allow public routes without auth check
  if (to.meta.public) {
    console.log('✅ Public route, allowing access')
    next()
    return
  }
  // Wait for auth to be loaded from storage if not already loaded
  if (!authStore.authLoaded && localStorage.getItem('token')) {
    if (!authLoadPromise) {
      console.log('⏳ Loading auth from storage...')
      authLoadPromise = authStore.loadFromStorage()
        .then(() => {
          console.log('✅ Auth loaded successfully')
          authLoadPromise = null
        })
        .catch((error) => {
          console.error('❌ Auth load failed:', error)
          authLoadPromise = null
        })
    }
    try {
      await authLoadPromise
    } catch (error) {
      console.error('❌ Error waiting for auth load:', error)
    }
  }
  const isAuthenticated = authStore.isAuthenticated
  const guestOnly = to.meta.guestOnly
  const requiresAuth = to.meta.requiresAuth
  const requiredRoles = to.meta.roles
  console.log('🔐 Auth status:', {
    isAuthenticated,
    userEmail: authStore.user?.email,
    userRole: authStore.userRole,
    authLoaded: authStore.authLoaded,
    hasToken: !!authStore.token,
    hasUser: !!authStore.user
  })
  console.log('🔍 Route requirements:', {
    guestOnly,
    requiresAuth,
    requiredRoles
  })
  // Handle guest-only routes (login, register, etc.)
  if (guestOnly && isAuthenticated) {
    console.log('✅ Already authenticated, redirecting to dashboard')
    const dashboardRoute = getDashboardRoute(authStore.userRole)
    next({ name: dashboardRoute })
    return
  }
  // Handle protected routes
  if (requiresAuth && !isAuthenticated) {
    console.log('🚫 Not authenticated, redirecting to login')
    next({
      name: 'Login',
      query: { redirect: to.fullPath }
    })
    return
  }
  // Check role-based access
  if (requiresAuth && requiredRoles && requiredRoles.length > 0) {
    const hasRole = authStore.hasRole(requiredRoles)
    console.log('🔑 Role check:', {
      userRole: authStore.userRole,
      requiredRoles,
      hasRole
    })
    if (!hasRole) {
      console.log('🚫 Insufficient permissions')
      next({ name: 'Unauthorized' })
      return
    }
  }
  // Check specific user restrictions
  if (to.meta.specificUser && authStore.user?.email !== to.meta.specificUser) {
    console.log('🚫 Route restricted to specific user:', to.meta.specificUser)
    next({ name: 'Unauthorized' })
    return
  }
  console.log('✅ Navigation allowed\n')
  next()
})
function getDashboardRoute(role) {
  const dashboards = {
    'admin': 'AdminDashboard',
    'manager': 'ManagerDashboard',
    'employee': 'EmployeeDashboard'
  }
  return dashboards[role] || 'EmployeeDashboard'
}
export default router