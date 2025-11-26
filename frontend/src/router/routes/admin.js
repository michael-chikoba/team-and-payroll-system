export default [
  {
    path: '/admin',
    // The AdminLayout component must contain a <router-view> tag
    // for the child components (like Dashboard) to be rendered.
    component: () => import('../../layouts/AdminLayout.vue'),
    meta: { requiresAuth: true, roles: ['admin'] },
    children: [
      // 1. DEFAULT CHILD ROUTE: This route handles the path '/admin' itself.
      // It ensures the Admin Dashboard is shown when the user navigates to the base admin URL.
      {
        path: '',
        name: 'AdminRoot',
        redirect: { name: 'AdminDashboard' }
      },
      // 2. NAMED DASHBOARD ROUTE: The primary dashboard view.
      {
        path: 'dashboard', // Resolves to /admin/dashboard
        name: 'AdminDashboard',
        component: () => import('../../views/admin/Dashboard.vue')
      },
      // 3. EMPLOYEE MANAGEMENT
      {
        path: 'employees', // Resolves to /admin/employees
        name: 'EmployeeManagement',
        component: () => import('../../views/admin/EmployeeManagement.vue')
      },
      // 4. PAYROLL PROCESSING
      {
        path: 'payroll', // Resolves to /admin/payroll
        name: 'PayrollProcessing',
        component: () => import('../../views/admin/PayrollProcessing.vue')
      },
      // 5. PAYSLIP GENERATION
      {
        path: 'payslips', // Resolves to /admin/payslips
        name: 'PayslipGeneration',
        component: () => import('../../views/admin/PayslipGeneration.vue')
      },
      // 6. TAX CONFIGURATION
      {
        path: 'tax', // Resolves to /admin/tax
        name: 'TaxConfiguration',
        component: () => import('../../views/admin/TaxConfiguration.vue')
      },
      // 7. ADMIN REPORTS
      {
        path: 'reports', // Resolves to /admin/reports
        name: 'AdminReports',
        component: () => import('../../views/admin/Reports.vue')
      },
      {
              path: 'profile',
        name: 'AdminProfile',
        component: () => import('../../views/common/Profile.vue')
      },
      // 8. AUDIT LOGS
      {
        path: 'audit-logs', // Resolves to /admin/audit-logs
        name: 'AuditLogs',
        component: () => import('../../views/admin/AuditLogs.vue')
      },
      // 9. LEAVE MANAGEMENT (Added this back, assuming you need it for the Approve Leaves button)
      {
        path: 'leaves', // Resolves to /admin/leaves
        name: 'LeaveManagement',
        // Assuming this component is for approving/managing leaves
        component: () => import('../../views/admin/LeaveManagement.vue') 
      },
      {
        path: 'attendance',
        name: 'Attendancemonitor',

        component: () => import('../../views/admin/AttendanceMonitor.vue')

      },
      // 10. ADMIN SETTINGS
      {
        path: 'settings', // Resolves to /admin/settings
        name: 'AdminSettings',
        component: () => import('../../views/admin/Settings.vue')
      }
    ]
  }
]
