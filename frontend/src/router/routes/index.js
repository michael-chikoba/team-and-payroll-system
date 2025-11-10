import authRoutes from './auth'
import employeeRoutes from './employee'
import managerRoutes from './manager'
import adminRoutes from './admin'

export default [
  ...authRoutes,
  ...employeeRoutes,
  ...managerRoutes,
  ...adminRoutes,
  {
    path: '/unauthorized',
    name: 'unauthorized',
    component: () => import('@/views/Unauthorized.vue')
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFound.vue')
  }
]