import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

export function usePermissions() {
  const authStore = useAuthStore()

  const permissions = computed(() => {
    const basePermissions = [
      'view_dashboard',
      'view_profile',
      'update_profile'
    ]

    if (authStore.isEmployee) {
      return [
        ...basePermissions,
        'view_attendance',
        'clock_in',
        'clock_out',
        'apply_leave',
        'view_leaves',
        'view_payslips',
        'download_payslips'
      ]
    }

    if (authStore.isManager) {
      return [
        ...basePermissions,
        'view_team_attendance',
        'view_team_leaves',
        'approve_leaves',
        'view_team_reports',
        'manage_team'
      ]
    }

    if (authStore.isAdmin) {
      return [
        ...basePermissions,
        'manage_employees',
        'manage_payroll',
        'process_payroll',
        'generate_reports',
        'manage_system',
        'view_audit_logs',
        'configure_tax',
        'view_all_data'
      ]
    }

    return basePermissions
  })

  function hasPermission(permission) {
    return permissions.value.includes(permission)
  }

  function hasAnyPermission(permissionArray) {
    return permissionArray.some(permission => hasPermission(permission))
  }

  function hasAllPermissions(permissionArray) {
    return permissionArray.every(permission => hasPermission(permission))
  }

  return {
    permissions,
    hasPermission,
    hasAnyPermission,
    hasAllPermissions
  }
}