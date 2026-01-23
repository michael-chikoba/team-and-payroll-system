// src/config/departmentConfig.js

/**
 * Department-based page access configuration
 * ONLY applies to employee and manager roles
 * Admin role has unrestricted access to all pages
 */
export const DEPARTMENT_ACCESS = {
  'IT': {
    employee: ['dashboard', 'attendance', 'leaves', 'apply-leave', 'payslips', 'profile', 'tasks'],
    manager: ['dashboard', 'employees', 'attendance', 'leave-approvals', 'reports', 'productivity', 'profile', 'payslips', 'tasks']
  },
  'HR': {
    employee: ['dashboard', 'attendance', 'leaves', 'apply-leave', 'payslips', 'profile', 'tasks'],
    manager: ['dashboard', 'employees', 'attendance', 'leave-approvals', 'reports', 'productivity', 'profile', 'payslips', 'tasks']
  },
  'Accounts': {
    employee: ['dashboard', 'attendance', 'leaves', 'apply-leave', 'payslips', 'profile', 'tasks'],
    manager: ['dashboard', 'employees', 'attendance', 'leave-approvals', 'reports', 'profile', 'payslips', 'tasks']
  },
  'Operations': {
    employee: ['dashboard', 'attendance', 'leaves', 'apply-leave', 'payslips', 'profile', 'tasks'],
    manager: ['dashboard', 'employees', 'attendance', 'leave-approvals', 'reports', 'productivity', 'profile', 'payslips', 'tasks']
  },
  'Call Center': {
    employee: ['dashboard', 'attendance', 'leaves', 'apply-leave', 'payslips', 'profile', 'tasks'],
    manager: ['dashboard', 'employees', 'attendance', 'leave-approvals', 'reports', 'productivity', 'profile', 'payslips', 'tasks']
  },
  'Content Management': {
    employee: ['dashboard', 'attendance', 'leaves', 'apply-leave', 'payslips', 'profile', 'tasks'],
    manager: ['dashboard', 'employees', 'attendance', 'leave-approvals', 'reports', 'profile', 'payslips', 'tasks']
  },
  'Sales & Marketing': {
    employee: ['dashboard', 'attendance', 'leaves', 'apply-leave', 'payslips', 'profile', 'tasks'],
    manager: ['dashboard', 'employees', 'attendance', 'leave-approvals', 'reports', 'productivity', 'profile', 'payslips', 'tasks']
  },
  'Project Management': {
    employee: ['dashboard', 'attendance', 'leaves', 'apply-leave', 'payslips', 'profile', 'tasks'],
    manager: ['dashboard', 'employees', 'attendance', 'leave-approvals', 'reports', 'productivity', 'profile', 'payslips', 'tasks']
  }
};

/**
 * Check if a user has access to a specific route based on their department
 * IMPORTANT: This check is bypassed for admin users
 * 
 * @param {string} department - User's department
 * @param {string} role - User's role (employee/manager/admin)
 * @param {string} routePath - Route path to check
 * @returns {boolean}
 */
export function canAccessRoute(department, role, routePath) {
  // ADMIN BYPASS: Admins have unrestricted access
  if (role === 'admin') {
    console.log('[Department Access] Admin user - access granted to all routes');
    return true;
  }

  // Only apply department restrictions to employee and manager roles
  if (role !== 'employee' && role !== 'manager') {
    console.warn(`[Department Access] Unknown role: ${role}. Denying access by default.`);
    return false;
  }

  // Normalize the route path (remove leading/trailing slashes and prefixes)
  const normalizedPath = routePath
    .replace(/^\/?(employee|manager)\//, '')
    .replace(/^\/|\/$/g, '');

  // Get department access config
  const deptConfig = DEPARTMENT_ACCESS[department];
  
  if (!deptConfig) {
    console.warn(`[Department Access] No configuration found for department: ${department}`);
    return false;
  }

  // Check if the role has access to this route
  const allowedRoutes = deptConfig[role] || [];
  const hasAccess = allowedRoutes.includes(normalizedPath);

  console.log('[Department Access]', {
    department,
    role,
    path: normalizedPath,
    hasAccess,
    allowedRoutes: allowedRoutes.length
  });
  
  return hasAccess;
}

/**
 * Get all accessible routes for a user
 * @param {string} department - User's department
 * @param {string} role - User's role
 * @returns {Array<string>}
 */
export function getAccessibleRoutes(department, role) {
  // Admin has access to all routes
  if (role === 'admin') {
    return ['all']; // Special indicator for unrestricted access
  }

  // Only check department for employee and manager
  if (role !== 'employee' && role !== 'manager') {
    return [];
  }

  const deptConfig = DEPARTMENT_ACCESS[department];
  
  if (!deptConfig) {
    return [];
  }

  return deptConfig[role] || [];
}

/**
 * Filter navigation menu items based on department access
 * Admins see all menu items without filtering
 * 
 * @param {Array} menuItems - Array of menu items
 * @param {string} department - User's department
 * @param {string} role - User's role
 * @returns {Array} Filtered menu items
 */
export function filterMenuByDepartment(menuItems, department, role) {
  // Admin bypass - show all menu items
  if (role === 'admin') {
    console.log('[Menu Filter] Admin user - showing all menu items');
    return menuItems;
  }

  // Only filter for employee and manager roles
  if (role !== 'employee' && role !== 'manager') {
    console.warn(`[Menu Filter] Unknown role: ${role}. Showing no menu items.`);
    return [];
  }

  return menuItems.filter(item => {
    // Extract route path from item
    const routePath = item.to?.name || item.path || '';
    
    // Convert route name to path format (e.g., 'EmployeeDashboard' -> 'dashboard')
    const normalizedPath = routePath
      .replace(/^(Employee|Manager)/, '')
      .replace(/([A-Z])/g, '-$1')
      .toLowerCase()
      .replace(/^-/, '');

    return canAccessRoute(department, role, normalizedPath);
  });
}

/**
 * Check if department restrictions should be applied for a given role
 * @param {string} role - User's role
 * @returns {boolean} True if department restrictions apply
 */
export function shouldApplyDepartmentRestrictions(role) {
  return role === 'employee' || role === 'manager';
}