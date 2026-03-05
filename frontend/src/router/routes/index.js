// src/router/routes/index.js
import authRoutes from './auth'
import employeeRoutes from './employee'
import managerRoutes from './manager'
import adminRoutes from './admin'
import notificationChannelRoutes from './notification-channel'
import landingRoutes from './landing'

export default [
  // Landing pages (public, no auth required)
  ...landingRoutes,

  // Business Group Routes
  {
    path: '/business-groups',
    name: 'BusinessGroups',
    component: () => import('@/components/BusinessGroups/BusinessGroupList.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/invitations',
    name: 'Invitations',
    component: () => import('@/components/BusinessGroups/InvitationsDashboard.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/group-tickets',
    name: 'GroupTickets',
    component: () => import('@/components/BusinessGroups/GroupTickets.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/group-tasks',
    name: 'GroupTasks',
    component: () => import('@/components/BusinessGroups/GroupTasks.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/settings/notifications',
    name: 'NotificationPreferences',
    component: () => import('@/components/NotificationPreferences.vue'),
    meta: { requiresAuth: true }
  },

  // Auth routes (guest only)
  ...authRoutes,

  // Protected routes (require auth)
  ...employeeRoutes,
  ...managerRoutes,
  ...adminRoutes,
  ...notificationChannelRoutes,

  // Error pages
  {
    path: '/unauthorized',
    name: 'Unauthorized',
    component: () => import('@/views/Unauthorized.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/NotFound.vue')
  }
]