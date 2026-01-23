export const authGuard = (to, from, next) => {
  const authStore = useAuthStore()
  
  if (!authStore.isAuthenticated) {
    next({ name: 'Login', query: { redirect: to.fullPath } })
  } else {
    next()
  }
}

export const roleGuard = (roles) => {
  return (to, from, next) => {
    const authStore = useAuthStore()
    
    if (!authStore.isAuthenticated) {
      next({ name: 'Login' })
    } else if (!roles.includes(authStore.user?.role)) {
      next({ name: 'Unauthorized' })
    } else {
      next()
    }
  }
}

export const guestGuard = (to, from, next) => {
  const authStore = useAuthStore()
  
  if (authStore.isAuthenticated) {
    // Redirect to appropriate dashboard
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
  } else {
    next()
  }
}