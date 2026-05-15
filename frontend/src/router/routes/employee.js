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
        path: 'tasks',
        name: 'TaskBoard',
        component: () => import('../../views/employee/TaskAssigned.vue'),
        meta: { 
          requiresAuth: true,
          roles: ['employee', 'manager', 'admin'],
          title: 'My Tasks'
        }
      },
      {
        path: 'schedules',
        name: 'EmployeeSchedules',
        component: () => import('../../views/employee/MySchedules.vue'),
        meta: { 
          requiresAuth: true,
          roles: ['employee', 'manager', 'admin'],
          title: 'My Schedules'
        }
      },
      {
        path: 'shifts',
        name: 'myshifts',
        component: () => import('../../views/employee/MyShift.vue'),
        meta: { 
          requiresAuth: true,
          roles: ['employee', 'manager', 'admin'],
          title: 'My Shift'
        }
      },
      {
        path: 'Tickets',
        name: 'mytickets',
        component: () => import('../../views/Tickets.vue'),
        meta: { 
          requiresAuth: true,
          roles: ['employee', 'manager', 'admin'],
          title: 'My Tickets'
        }
      },
      {
        path: 'learning',
        name: 'LearningCatalog',
        component: () => import('../../views/common/LearningCatalog.vue'),
        meta: { 
          requiresAuth: true,
          roles: ['employee', 'manager', 'admin'],
          title: 'Learning Catalog'
        }
      }
    ]
  }
]