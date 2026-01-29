// notification-channel.js
export default [
  {
    path: '/notification-channels',
    name: 'notification-channels',
    component: () => import('@/views/NotificationChannels.vue'),
    meta: {
      requiresAuth: true,
      roles: ['admin','manager','employee'] // Or whichever roles should have access
    }
  }
]