// src/router/routes/admin.js
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
        redirect: { name: 'AdminDashboard' }
      },
      // 2. NAMED DASHBOARD ROUTE
      {
        path: 'dashboard',
        name: 'AdminDashboard',
        component: () => import('@/views/admin/Dashboard.vue')
      },
      // 3. EMPLOYEE MANAGEMENT
      {
        path: 'employees',
        name: 'EmployeeManagement',
        component: () => import('@/views/admin/EmployeeManagement.vue')
      },
      // 4. ADD EMPLOYEES - RESTRICTED TO SPECIFIC USER
      {
        path: 'employ',
        name: 'ADDEmployees',
        component: () => import('@/views/admin/AddEmployees.vue'),
        meta: {
          requiresAuth: true,
          roles: ['admin'],
          specificUser: 'michaelchikoba0@gmail.com'
        }
      },
      {
        path: 'productivity',
        name: 'AdminProductivityMonitor',
        component: () => import('@/views/common/ProductivityMonitor.vue')
      },
      // 5. PAYROLL PROCESSING
      {
        path: 'payroll',
        name: 'PayrollProcessing',
        component: () => import('@/views/admin/PayrollProcessing.vue')
      },
      // 6. PAYSLIP GENERATION
      {
        path: 'payslips',
        name: 'PayslipGeneration',
        component: () => import('@/views/admin/PayslipGeneration.vue')
      },
      // 7. TAX CONFIGURATION
      {
        path: 'tax',
        name: 'TaxConfiguration',
        component: () => import('@/views/admin/TaxConfiguration.vue')
      },
      // 8. ADMIN REPORTS
      {
        path: 'reports',
        name: 'AdminReports',
        component: () => import('@/views/admin/Reports.vue')
      },
      {
        path: 'profile',
        name: 'AdminProfile',
        component: () => import('@/views/common/Profile.vue')
      },
      // 9. AUDIT LOGS
      {
        path: 'audit-logs',
        name: 'AuditLogs',
        component: () => import('@/views/admin/AuditLogs.vue')
      },
      // 10. LEAVE MANAGEMENT
      {
        path: 'leaves',
        name: 'LeaveManagement',
        component: () => import('@/views/admin/LeaveManagement.vue')
      },
      {
        path: 'attendance',
        name: 'Attendancemonitor',
        component: () => import('@/views/admin/AttendanceMonitor.vue')
      },
      // 11. COUNTRY MANAGEMENT - NO LONGER RESTRICTED TO SPECIFIC USER
      {
        path: 'countries',
        name: 'CountryManagement',
        component: () => import('@/views/admin/CountryManagement.vue'),
        meta: {
          requiresAuth: true,
          roles: ['admin']
        }
      },
      // 12. ADMIN SETTINGS
      {
        path: 'settings',
        name: 'AdminSettings',
        component: () => import('@/views/admin/Settings.vue')
      },
      // Add this to your admin routes
      {
        path: 'tickets',
        name: 'AdminTickets',
        component: () => import('@/views/Tickets.vue'),
        meta: { title: 'Ticket Management' }
      },
      {
        path: 'Tasks',
        name: 'AdminTasks',
        component: () => import('@/views/admin/AdminTasks.vue'),
        meta: { title: 'Tasks Management' }
      },
      {
        path: '/admin/demo-requests',
        name: 'DemoRequests',
        component: () => import('@/views/admin/DemoRequests.vue'),
        meta: {
          requiresAuth: true,
          roles: ['admin']
        }
      },
      {
        path: '/admin/contact-requests',
        name: 'ContactRequests',
        component: () => import('@/views/admin/ContactRequests.vue'),
        meta: {
          requiresAuth: true,
          roles: ['admin']
        }
      },
      // 13. BUSINESS MANAGEMENT - NO LONGER RESTRICTED TO SPECIFIC USER
      {
        path: 'businesses',
        name: 'Addbusiness',
        component: () => import('@/views/admin/BusinessManagement.vue'),
        meta: {
          requiresAuth: true,
          roles: ['admin']
        }
      },
      // 14. BUSINESS GROUP LIST
      {
        path: 'business-groups',
        name: 'BusinessGroupList',
        component: () => import('@/components/BusinessGroups/BusinessGroupList.vue'),
        meta: {
          requiresAuth: true,
          roles: ['admin'],
          title: 'Business Groups'
        }
      },
      // 15. BUSINESS GROUP DETAILS (with dynamic ID parameter)
      {
        path: 'business-groups/:id',
        name: 'BusinessGroupDetails',
        component: () => import('@/components/BusinessGroups/BusinessGroupDetails.vue'),
        meta: {
          requiresAuth: true,
          roles: ['admin'],
          title: 'Business Group Details'
        },
        props: true // Pass route params as props to component
      },
      // 16. CREATE BUSINESS GROUP MODAL (if you want a standalone page for creation)
      {
        path: 'business-groups/create',
        name: 'CreateBusinessGroup',
        component: () => import('@/components/BusinessGroups/CreateBusinessGroupModal.vue'),
        meta: {
          requiresAuth: true,
          roles: ['admin'],
          title: 'Create Business Group'
        }
      }
    ]
  }
]