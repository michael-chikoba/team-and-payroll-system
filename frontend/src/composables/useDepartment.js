// src/composables/useDepartment.js
import { computed } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { 
  canAccessRoute, 
  getAccessibleRoutes, 
  filterMenuByDepartment,
  shouldApplyDepartmentRestrictions 
} from '@/config/departmentConfig';

export function useDepartment() {
  const authStore = useAuthStore();

  // Defensive coding: Ensure fallbacks if store data is null
  const department = computed(() => authStore.userDepartment || null);
  const role = computed(() => authStore.userRole || 'guest');
  const isAdmin = computed(() => authStore.isAdmin || false);
  const isManager = computed(() => authStore.isManager || false);
  const isEmployee = computed(() => authStore.isEmployee || false);
  
  const hasDepartmentRestrictions = computed(() => 
    shouldApplyDepartmentRestrictions(role.value)
  );

  const canAccess = (routePath) => {
    if (isAdmin.value) return true;
    if (!hasDepartmentRestrictions.value) return true;
    
    if (!department.value) {
      const normalizedPath = routePath
        .replace(/^\/?(employee|manager)\//, '')
        .replace(/^\/|\/$/g, '');
      return ['dashboard', 'profile'].includes(normalizedPath);
    }
    return canAccessRoute(department.value, role.value, routePath);
  };

  const filterMenu = (menuItems) => {
    if (!Array.isArray(menuItems)) return [];
    if (isAdmin.value) return menuItems;
    if (!hasDepartmentRestrictions.value) return menuItems;
    
    // Safety check for filterMenuByDepartment import
    if (typeof filterMenuByDepartment !== 'function') return menuItems;
    
    return filterMenuByDepartment(menuItems, department.value, role.value);
  };

  const departmentName = computed(() => department.value || 'No Department');

  const hasDepartment = computed(() => {
    if (isAdmin.value) return true;
    if (!hasDepartmentRestrictions.value) return true;
    return !!department.value;
  });

  return {
    department,
    role,
    isAdmin,
    isManager,
    isEmployee,
    canAccess,
    filterMenu,
    departmentName,
    hasDepartment
  };
}