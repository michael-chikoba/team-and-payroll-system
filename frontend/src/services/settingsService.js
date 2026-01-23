// src/services/settingsService.js
import axios from 'axios';

/**
 * Service for fetching system settings and departments
 * ONLY applies to employee and manager roles
 * Admin users don't need department validation
 */
export const settingsService = {
  /**
   * Get valid departments for the current user's business/country
   * Returns empty array for admins (they don't need departments)
   */
  async getValidDepartments() {
    try {
      const response = await axios.get('/api/settings/departments');
      return response.data;
    } catch (error) {
      console.error('[Settings Service] Failed to fetch departments:', error);
      throw error;
    }
  },

  /**
   * Get all system settings for the current user's context
   */
  async getSystemSettings() {
    try {
      const response = await axios.get('/api/settings/system');
      return response.data;
    } catch (error) {
      console.error('[Settings Service] Failed to fetch system settings:', error);
      throw error;
    }
  },

  /**
   * Validate if a department is valid for the current user's business/country
   */
  async validateDepartment(department) {
    try {
      const response = await axios.post('/api/settings/validate-department', {
        department
      });
      return response.data;
    } catch (error) {
      console.error('[Settings Service] Failed to validate department:', error);
      throw error;
    }
  }
};

/**
 * Sync department configuration from backend
 * Updates the frontend department config with backend data
 * 
 * IMPORTANT: This only runs for employee and manager roles
 * Admins are automatically bypassed
 */
export async function syncDepartmentConfig() {
  try {
    console.log('[Department Sync] Starting sync...');
    
    const data = await settingsService.getValidDepartments();
    
    // Check if user is admin (backend will return appropriate response)
    if (data.is_admin) {
      console.log('[Department Sync] User is admin - skipping department sync');
      localStorage.removeItem('valid_departments');
      localStorage.removeItem('current_department');
      return {
        is_admin: true,
        message: 'Admin users do not require department configuration'
      };
    }
    
    console.log('[Department Sync] Configuration received:', {
      departments: data.departments?.length || 0,
      current: data.current_department,
      business: data.business_id,
      country: data.country_code
    });

    // Store in localStorage for offline reference (only for employee/manager)
    if (data.departments && Array.isArray(data.departments)) {
      localStorage.setItem('valid_departments', JSON.stringify(data.departments));
      localStorage.setItem('current_department', data.current_department || '');
      
      console.log('[Department Sync] ✅ Configuration synced successfully');
    } else {
      console.warn('[Department Sync] No valid departments received');
    }
    
    return data;
  } catch (error) {
    console.error('[Department Sync] ❌ Failed to sync department config:', error);
    
    // Fallback to cached data if available
    const cached = localStorage.getItem('valid_departments');
    if (cached) {
      console.log('[Department Sync] Using cached departments as fallback');
      return {
        departments: JSON.parse(cached),
        current_department: localStorage.getItem('current_department') || null,
        cached: true
      };
    }
    
    throw error;
  }
}

/**
 * Clear department cache
 * Useful when logging out or switching users
 */
export function clearDepartmentCache() {
  console.log('[Department Sync] Clearing department cache');
  localStorage.removeItem('valid_departments');
  localStorage.removeItem('current_department');
}