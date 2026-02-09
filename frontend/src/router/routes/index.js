import authRoutes from './auth'
import employeeRoutes from './employee'
import managerRoutes from './manager'
import adminRoutes from './admin'
import notificationChannelRoutes from './notification-channel'

export default [
  // Landing page (public, no auth required)
  {
    path: '/',
    name: 'Landing',
    component: () => import('@/views/Landing.vue'),
    meta: { 
      public: true // Mark as public route
    }
  },
  // In router/index.js
{
  path: '/invitations',
  name: 'Invitations',
  component: () => import('@/components/BusinessGroups/InvitationsDashboard.vue'),
  meta: {
    requiresAuth: true
  }
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