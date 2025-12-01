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
      // 11. COUNTRY MANAGEMENT - RESTRICTED TO SPECIFIC USER
      {
        path: 'countries',
        name: 'CountryManagement',
        component: () => import('@/views/admin/CountryManagement.vue'),
        meta: { 
          requiresAuth: true, 
          roles: ['admin'],
          specificUser: 'michaelchikoba0@gmail.com'
        }
      },
      // 12. ADMIN SETTINGS
      {
        path: 'settings',
        name: 'AdminSettings',
        component: () => import('@/views/admin/Settings.vue')
      },
      // 13. BUSINESS MANAGEMENT - RESTRICTED TO SPECIFIC USER
      {
        path: 'businesses',
        name: 'Addbusiness',
        component: () => import('@/views/admin/BusinessManagement.vue'),
        meta: { 
          requiresAuth: true, 
          roles: ['admin'],
          specificUser: 'michaelchikoba0@gmail.com'
        }
      }
    ]
  }
]