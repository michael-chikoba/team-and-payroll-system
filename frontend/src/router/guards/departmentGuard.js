// src/router/guards/departmentGuard.js

import { useAuthStore } from '@/stores/auth'
import { canAccessRoute } from '@/config/departmentConfig'

export async function departmentGuard(to, from, next) {
  const authStore = useAuthStore()
  
  console.log('[Department Guard] Checking access:', {
    path: to.path,
    name: to.name,
    role: authStore.userRole,
    department: authStore.userDepartment,
    isAdmin: authStore.isAdmin
  })

  // CRITICAL: Admin bypass
  if (authStore.isAdmin) {
    console.log('[Department Guard] ✅ Admin user - access granted')
    next()
    return
  }

  // Only apply to employee and manager roles
  if (authStore.userRole !== 'employee' && authStore.userRole !== 'manager') {
    console.log('[Department Guard] ✅ Non-restricted role - access granted')
    next()
    return
  }

  // Check if user has a department assigned
  if (!authStore.userDepartment) {
    console.warn('[Department Guard] ❌ No department assigned')
    next({
      name: authStore.userRole === 'manager' ? 'ManagerDashboard' : 'EmployeeDashboard',
      query: {
        error: 'no_department',
        message: 'You do not have a department assigned. Please contact HR.'
      }
    })
    return
  }

  // Extract the route path
  const routePath = to.path
    .replace(/^\/?(employee|manager)\//, '')
    .replace(/^\/|\/$/g, '')

  // Always allow dashboard and profile
  if (routePath === 'dashboard' || to.name === 'EmployeeDashboard' || to.name === 'ManagerDashboard') {
    console.log('[Department Guard] ✅ Dashboard access - always allowed')
    next()
    return
  }

  if (routePath === 'profile' || to.name === 'EmployeeProfile' || to.name === 'ManagerProfile') {
    console.log('[Department Guard] ✅ Profile access - always allowed')
    next()
    return
  }

  // Check department-based access
  const hasAccess = canAccessRoute(
    authStore.userDepartment,
    authStore.userRole,
    routePath
  )

  if (!hasAccess) {
    console.warn('[Department Guard] ❌ Access denied:', {
      department: authStore.userDepartment,
      role: authStore.userRole,
      route: routePath
    })
    
    next({
      name: authStore.userRole === 'manager' ? 'ManagerDashboard' : 'EmployeeDashboard',
      query: {
        error: 'access_denied',
        department: authStore.userDepartment,
        message: `Your department (${authStore.userDepartment}) does not have access to this page.`
      }
    })
    return
  }

  console.log('[Department Guard] ✅ Access granted')
  next()
}