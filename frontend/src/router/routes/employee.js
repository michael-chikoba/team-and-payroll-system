export default [
  {
    path: '/employee',
    
    component: () => import('../../layouts/EmployeeLayout.vue'),
    meta: { requiresAuth: true, roles: ['employee', 'manager', 'admin'] },
    children: [
      {
        path: 'dashboard',
        name: 'EmployeeDashboard',
        component: () => import('../../views/employee/Dashboard.vue')
      },
      {
        path: 'attendance',
        name: 'EmployeeAttendance',
        component: () => import('../../views/employee/Attendance.vue')
      },
      {
        path: 'leaves',
        name: 'EmployeeLeaves',
        component: () => import('../../views/employee/Leaves.vue')
      },
      {
        path: 'apply-leave',
        name: 'ApplyLeave',
        component: () => import('../../views/employee/ApplyLeave.vue')
      },
      {
        path: 'payslips',
        name: 'EmployeePayslips',
        component: () => import('../../views/employee/Payslips.vue')
      },
      {
        path: 'profile',
        name: 'EmployeeProfile',
        component: () => import('../../views/employee/Profile.vue')
      },
      {
     path: '/employee/tasks',
    name: 'EmployeeTasks',
    component: () => import('../../components/common/TaskBoard.vue'),
    meta: { 
      requiresAuth: true,
      roles: ['employee'],
      title: 'My Tasks'

      }
    }
    ]
  }
]