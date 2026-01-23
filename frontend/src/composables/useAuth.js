import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

export function useAuth() {
  const authStore = useAuthStore()

  const user = computed(() => authStore.user)
  const isAuthenticated = computed(() => authStore.isAuthenticated)
  const isAdmin = computed(() => authStore.isAdmin)
  const isManager = computed(() => authStore.isManager)
  const isEmployee = computed(() => authStore.isEmployee)
  const userRole = computed(() => authStore.userRole)

  function hasRole(roles) {
    return authStore.hasRole(roles)
  }

  function hasPermission(permission) {
    // This would check against user's permissions
    // For now, we'll base it on role
    const rolePermissions = {
      admin: ['*'],
      manager: ['view_employees', 'approve_leaves', 'view_reports'],
      employee: ['view_attendance', 'apply_leaves', 'view_payslips']
    }

    const userPermissions = rolePermissions[userRole.value] || []
    return userPermissions.includes('*') || userPermissions.includes(permission)
  }

  return {
    user,
    isAuthenticated,
    isAdmin,
    isManager,
    isEmployee,
    userRole,
    hasRole,
    hasPermission
  }
}