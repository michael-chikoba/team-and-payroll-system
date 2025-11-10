export default [
  {
    path: '/manager',
    component: () => import('../../layouts/ManagerLayout.vue'),
    meta: { requiresAuth: true, roles: ['manager', 'admin'] },
    children: [
      {
        path: 'dashboard',
        name: 'ManagerDashboard',
        component: () => import('../../views/manager/Dashboard.vue')
      },
      {
        path: 'employees',
        name: 'EmployeeList',
        component: () => import('../../views/manager/EmployeeList.vue')
      },
      {
        path: 'attendance',
        name: 'AttendanceMonitor',
        component: () => import('../../views/manager/AttendanceMonitor.vue')
      },
      {
        path: 'leave-approvals',
        name: 'LeaveApprovals',
        component: () => import('../../views/manager/LeaveApprovals.vue')
      },
      {
        path: 'reports',
        name: 'TeamReports',
        component: () => import('../../views/manager/TeamReports.vue')
      },
      {
        path: 'productivity',
        name: 'ProductivityMonitor',
        component: () => import('../../views/manager/ProductivityMonitor.vue')
      },
      {
        path: 'Task',
        name: 'AssignTask',
        component: () => import('../../views/manager/TaskForm.vue')
      }
    ]
  }
]