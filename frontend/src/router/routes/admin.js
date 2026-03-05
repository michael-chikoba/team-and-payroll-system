// src/router/routes/admin.js
import { useAuthStore } from '@/stores/auth'
import { useAdminPermissions } from '@/composables/useAdminPermissions'

// ─────────────────────────────────────────────────────────────────────────────
// Re-usable beforeEnter guard factory
//
// permissionKey: the "CAN do" key returned by useAdminPermissions
//   e.g. 'canViewPayroll' → route is blocked when _flags.cannot_view_payroll = true
//
// How it works:
//   1. Grabs the authStore to read the current business ID
//   2. Calls useAdminPermissions() — this either returns cached flags instantly
//      or starts a fresh fetch (singleton, so never duplicates an in-flight call)
//   3. Awaits waitForFetch() so we never evaluate stale "false" defaults
//   4. If the flag says the user is restricted → redirect to dashboard
//   5. Otherwise → allow navigation
// ─────────────────────────────────────────────────────────────────────────────
function makePermissionGuard(permissionKey) {
  return async (_to, _from, next) => {
    const authStore = useAuthStore()

    // If no business ID yet, wait a bit or redirect
    if (!authStore.currentBusinessId) {
      // Wait for auth to load
      await new Promise(resolve => setTimeout(resolve, 100))
      if (!authStore.currentBusinessId) {
        return next({ path: '/admin/dashboard' })
      }
    }

    const permissions = useAdminPermissions(authStore.currentBusinessId)

    // Wait for the API response before reading any flag.
    // If already cached, this resolves in the same tick (no network call).
    await permissions.waitForFetch()

    // Suspended admins are blocked from everything except dashboard
    if (permissions.isSuspended.value) {
      return next({ path: '/admin/dashboard' })
    }

    const permMap = {
      canAddEmployee: permissions.canAddEmployee,
      canViewPayroll: permissions.canViewPayroll,
      canViewPayslip: permissions.canViewPayslip,
      canManageAdmins: permissions.canManageAdmins,
    }

    const allowed = permMap[permissionKey]

    if (!allowed.value) {
      // Permission denied — bounce back to dashboard with a hint
      return next({ path: '/admin/dashboard', query: { restricted: '1' } })
    }

    next()
  }
}

// Convenience guard that only checks suspension (no specific permission needed)
async function suspendedGuard(_to, _from, next) {
  const authStore = useAuthStore()
  
  if (!authStore.currentBusinessId) {
    await new Promise(resolve => setTimeout(resolve, 100))
    if (!authStore.currentBusinessId) {
      return next({ path: '/admin/dashboard' })
    }
  }
  
  const permissions = useAdminPermissions(authStore.currentBusinessId)
  await permissions.waitForFetch()
  
  if (permissions.isSuspended.value) {
    return next({ path: '/admin/dashboard' })
  }
  next()
}

// ─────────────────────────────────────────────────────────────────────────────
// Admin route definitions
// ─────────────────────────────────────────────────────────────────────────────
export default [
  {
    path: '/admin',
    component: () => import('@/layouts/AdminLayout.vue'),
    meta: { requiresAuth: true, roles: ['admin'] },
    children: [
      // 1. DEFAULT CHILD ROUTE
      {
        path: '',
        name: 'AdminRoot',
        redirect: { name: 'AdminDashboard' },
      },

      // 2. DASHBOARD — always accessible
      {
        path: 'dashboard',
        name: 'AdminDashboard',
        component: () => import('@/views/admin/Dashboard.vue'),
      },

      // 3. EMPLOYEE MANAGEMENT — always accessible
      {
        path: 'employees',
        name: 'EmployeeManagement',
        component: () => import('@/views/admin/EmployeeManagement.vue'),
        beforeEnter: suspendedGuard,
      },

      // 4. ADD EMPLOYEES — restricted by cannot_add_employee flag
      {
        path: 'employ',
        name: 'ADDEmployees',
        component: () => import('@/views/admin/AddEmployees.vue'),
        meta: {
          requiresAuth: true,
          roles: ['admin'],
          specificUser: 'michaelchikoba0@gmail.com',
        },
        beforeEnter: makePermissionGuard('canAddEmployee'),
      },

      // PRODUCTIVITY MONITOR
      {
        path: 'productivity',
        name: 'AdminProductivityMonitor',
        component: () => import('@/views/common/ProductivityMonitor.vue'),
        beforeEnter: suspendedGuard,
      },

      // 5. PAYROLL PROCESSING — restricted by cannot_view_payroll flag
      {
        path: 'payroll',
        name: 'PayrollProcessing',
        component: () => import('@/views/admin/PayrollProcessing.vue'),
        beforeEnter: makePermissionGuard('canViewPayroll'),
      },

      // 6. PAYSLIP GENERATION — restricted by cannot_view_payslip flag
      {
        path: 'payslips',
        name: 'PayslipGeneration',
        component: () => import('@/views/admin/PayslipGeneration.vue'),
        beforeEnter: makePermissionGuard('canViewPayslip'),
      },

      // 7. TAX CONFIGURATION — always accessible
      {
        path: 'tax',
        name: 'TaxConfiguration',
        component: () => import('@/views/admin/TaxConfiguration.vue'),
        beforeEnter: suspendedGuard,
      },

      // 8. ADMIN REPORTS — always accessible
      {
        path: 'reports',
        name: 'AdminReports',
        component: () => import('@/views/admin/Reports.vue'),
        beforeEnter: suspendedGuard,
      },

      // PROFILE — always accessible (even suspended admins should see their profile)
      {
        path: 'profile',
        name: 'AdminProfile',
        component: () => import('@/views/common/Profile.vue'),
      },

      // 9. AUDIT LOGS — always accessible
      {
        path: 'audit-logs',
        name: 'AuditLogs',
        component: () => import('@/views/admin/AuditLogs.vue'),
        beforeEnter: suspendedGuard,
      },

      // 10. LEAVE MANAGEMENT — always accessible
      {
        path: 'leaves',
        name: 'LeaveManagement',
        component: () => import('@/views/admin/LeaveManagement.vue'),
        beforeEnter: suspendedGuard,
      },

      // ATTENDANCE MONITOR — always accessible
      {
        path: 'attendance',
        name: 'Attendancemonitor',
        component: () => import('@/views/admin/AttendanceMonitor.vue'),
        beforeEnter: suspendedGuard,
      },

      // 11. COUNTRY MANAGEMENT — always accessible
      {
        path: 'countries',
        name: 'CountryManagement',
        component: () => import('@/views/admin/CountryManagement.vue'),
        meta: { requiresAuth: true, roles: ['admin'] },
        beforeEnter: suspendedGuard,
      },

      // 12. SETTINGS — always accessible
      {
        path: 'settings',
        name: 'AdminSettings',
        component: () => import('@/views/admin/Settings.vue'),
        beforeEnter: suspendedGuard,
      },

      // TICKETS — always accessible
      {
        path: 'tickets',
        name: 'AdminTickets',
        component: () => import('@/views/Tickets.vue'),
        meta: { title: 'Ticket Management' },
        beforeEnter: suspendedGuard,
      },

      // TASKS — always accessible
      {
        path: 'Tasks',
        name: 'AdminTasks',
        component: () => import('@/views/admin/AdminTasks.vue'),
        meta: { title: 'Tasks Management' },
        beforeEnter: suspendedGuard,
      },

      // DEMO REQUESTS
      {
        path: 'demo-requests',
        name: 'DemoRequests',
        component: () => import('@/views/admin/DemoRequests.vue'),
        meta: { requiresAuth: true, roles: ['admin'] },
        beforeEnter: suspendedGuard,
      },

      // CONTACT REQUESTS
      {
        path: 'contact-requests',
        name: 'ContactRequests',
        component: () => import('@/views/admin/ContactRequests.vue'),
        meta: { requiresAuth: true, roles: ['admin'] },
        beforeEnter: suspendedGuard,
      },

      // 13. BUSINESS MANAGEMENT — always accessible
      {
        path: 'businesses',
        name: 'Addbusiness',
        component: () => import('@/views/admin/BusinessManagement.vue'),
        meta: { requiresAuth: true, roles: ['admin'] },
        beforeEnter: suspendedGuard,
      },

      // 14. BUSINESS GROUP LIST — always accessible
      {
        path: 'business-groups',
        name: 'BusinessGroupList',
        component: () => import('@/components/BusinessGroups/BusinessGroupList.vue'),
        meta: { requiresAuth: true, roles: ['admin'], title: 'Business Groups' },
        beforeEnter: suspendedGuard,
      },

      // 15. BUSINESS GROUP DETAILS — always accessible
      {
        path: 'business-groups/:id',
        name: 'BusinessGroupDetails',
        component: () => import('@/components/BusinessGroups/BusinessGroupDetails.vue'),
        meta: { requiresAuth: true, roles: ['admin'], title: 'Business Group Details' },
        props: true,
        beforeEnter: suspendedGuard,
      },

      // 16. CREATE BUSINESS GROUP — always accessible
      {
        path: 'business-groups/create',
        name: 'CreateBusinessGroup',
        component: () => import('@/components/BusinessGroups/CreateBusinessGroupModal.vue'),
        meta: { requiresAuth: true, roles: ['admin'], title: 'Create Business Group' },
        beforeEnter: suspendedGuard,
      },

      // 17. ADMIN MANAGER — restricted by cannot_manage_admins flag
      {
        path: 'admin-manager',
        name: 'AdminManager',
        component: () => import('@/views/admin/AdminManager.vue'),
        meta: {
          requiresAuth: true,
          roles: ['admin'],
          title: 'Admin Manager',
        },
        beforeEnter: makePermissionGuard('canManageAdmins'),
      },
    ],
  },
]