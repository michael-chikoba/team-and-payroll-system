// src/router/index.js
import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAdminPermissions } from '@/composables/useAdminPermissions'
import routes from './routes/index'

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

// ── Permission map ────────────────────────────────────────────────────────────
// Maps each restricted admin path prefix → the permission flag that ALLOWS it.
// If the flag is false (i.e. the restriction is active), the user is redirected.
const ADMIN_ROUTE_PERMISSIONS = {
  '/admin/employ':        'canAddEmployee',   // Add Employees (Super Admin)
  '/admin/payroll':       'canViewPayroll',
  '/admin/payslips':      'canViewPayslip',
  '/admin/admin-manager': 'canManageAdmins',
}

// ── Navigation guard ──────────────────────────────────────────────────────────
router.beforeEach(async (to, _from, next) => {
  const authStore = useAuthStore()

  // ── 1. Guest-only routes (login, register, etc.) ──────────────────────────
  if (to.meta.guestOnly) {
    return authStore.isAuthenticated ? next({ path: '/admin/dashboard' }) : next()
  }

  // ── 2. Public routes that need no auth check ──────────────────────────────
  if (!to.meta.requiresAuth) return next()

  // ── 3. Require authentication ─────────────────────────────────────────────
  if (!authStore.isAuthenticated) {
    return next({ name: 'Login', query: { redirect: to.fullPath } })
  }

  // ── 4. Admin permission checks ────────────────────────────────────────────
  // Only run for /admin/* routes — employee and manager routes need no checks.
  if (to.path.startsWith('/admin/')) {
    const {
      canAddEmployee,
      canViewPayroll,
      canViewPayslip,
      canManageAdmins,
      isSuspended,
      waitForFetch,
    } = useAdminPermissions(authStore.currentBusinessId)

    // Wait for the permissions API call to resolve before evaluating flags.
    // waitForFetch() resolves immediately if already cached, otherwise awaits
    // the in-flight request, so there is no redundant network call.
    await waitForFetch()

    // ── 4a. Suspended admins can only see the dashboard ───────────────────
    if (isSuspended.value && to.path !== '/admin/dashboard') {
      return next({ path: '/admin/dashboard' })
    }

    // ── 4b. Check route-specific restrictions ─────────────────────────────
    for (const [pathPrefix, permissionKey] of Object.entries(ADMIN_ROUTE_PERMISSIONS)) {
      if (to.path.startsWith(pathPrefix)) {
        const permMap = { canAddEmployee, canViewPayroll, canViewPayslip, canManageAdmins }
        const allowed = permMap[permissionKey]

        if (!allowed.value) {
          // Redirect to dashboard with a flash message hint in the query
          return next({
            path: '/admin/dashboard',
            query: { restricted: '1' },
          })
        }
        break // Only one prefix can match per route
      }
    }
  }

  // ── 5. All checks passed ──────────────────────────────────────────────────
  next()
})

export default router