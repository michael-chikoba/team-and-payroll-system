// router/routes/tasks.js
export default [
  {
    path: '/tasks',
    name: 'TaskBoard',
    component: () => import('../../views/employee/TaskAssigned.vue'),
    meta: { 
      requiresAuth: true,
      roles: ['employee'], 
      title: 'Task Board'
    }
  },
  // Optional: Separate routes for different views if needed
  {
    path: '/tasks/my-tasks',
    name: 'MyTasks',
    component: () => import('../../views/employee/TaskAssigned.vue'),
    meta: { 
      requiresAuth: true,
      roles: ['employee'],
      title: 'My Tasks'
    }
  
  }
]