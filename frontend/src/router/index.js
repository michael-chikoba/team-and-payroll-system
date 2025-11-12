import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'


// Route imports
import authRoutes from './routes/auth'
import employeeRoutes from './routes/employee'
import managerRoutes from './routes/manager'
import adminRoutes from './routes/admin'
import publicRoutes from './routes/public'

const routes = [
  // PUBLIC ROUTES FIRST (includes root redirect)
  ...publicRoutes,
  
  // AUTH ROUTES SECOND
  ...authRoutes,
  
  // PROTECTED ROUTES
  ...employeeRoutes,
  ...managerRoutes,
  ...adminRoutes,
 
  
  // Catch all route - 404 (MUST BE LAST)
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('../views/NotFound.vue')
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

// Navigation guards
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore()
  
  // Check if route requires authentication
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'Login' })
    return
  }

  // Check if route requires specific role
  if (to.meta.roles && !to.meta.roles.includes(authStore.user?.role)) {
    next({ name: 'Unauthorized' })
    return
  }

  // Redirect authenticated users away from auth pages
  if (to.meta.guestOnly && authStore.isAuthenticated) {
    // Redirect to appropriate dashboard based on role
    const role = authStore.user?.role
    switch (role) {
      case 'admin':
        next({ name: 'AdminDashboard' })
        break
      case 'manager':
        next({ name: 'ManagerDashboard' })
        break
      default:
        next({ name: 'EmployeeDashboard' })
    }
    return
  }

  next()
})

export default router