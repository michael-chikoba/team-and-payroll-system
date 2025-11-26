<template>
  <div class="admin-layout">
    <!-- Header -->
    <header class="header">
      <div class="logo">
        <span class="logo-icon">💼</span>
        <span class="logo-text">Payroll System</span>
      </div>
     
      <div class="header-right">
        <div class="user-profile">
          <!-- Profile dropdown trigger -->
          <div class="profile-dropdown">
            <button class="profile-trigger" @click="toggleProfileDropdown">
              <span class="user-avatar">{{ getUserInitials }}</span>
              <span class="user-name">{{ user?.name || 'Admin User' }}</span>
              <span class="dropdown-arrow">▼</span>
            </button>
            <!-- Profile dropdown menu -->
            <div v-if="showProfileDropdown" class="profile-dropdown-menu">
              <router-link 
                to="/admin/profile" 
                class="dropdown-item"
                @click="closeProfileDropdown"
              >
                <span class="dropdown-icon">👤</span>
                My Profile
              </router-link>
              <div class="dropdown-divider"></div>
              <button 
                @click="logout"
                class="dropdown-item logout-dropdown-item"
              >
                <span class="dropdown-icon">🚪</span>
                Logout
              </button>
            </div>
          </div>
        </div>
      </div>
    </header>
    
    <!-- Overlay to close dropdown when clicking outside -->
    <div 
      v-if="showProfileDropdown" 
      class="dropdown-overlay" 
      @click="closeProfileDropdown"
    ></div>
    
    <div class="layout-body">
      <!-- Sidebar -->
      <aside class="sidebar">
        <!-- Attendance Toggle Button Section -->
        <div class="attendance-toggle-section">
          <ClockToggle />
        </div>
        <nav class="sidebar-nav">
          <router-link to="/admin/dashboard" class="nav-item">
            <span class="nav-icon">📊</span>
            <span class="nav-text">Dashboard</span>
          </router-link>
         
          <router-link to="/admin/employees" class="nav-item">
            <span class="nav-icon">👥</span>
            <span class="nav-text">Employees</span>
          </router-link>
         
          <router-link to="/admin/payroll" class="nav-item">
            <span class="nav-icon">💰</span>
            <span class="nav-text">Payroll</span>
          </router-link>
         
          <router-link to="/admin/payslips" class="nav-item">
            <span class="nav-icon">📄</span>
            <span class="nav-text">Payslips</span>
          </router-link>
         
          <router-link to="/admin/attendance" class="nav-item">
            <span class="nav-icon">⏰</span>
            <span class="nav-text">Attendance</span>
          </router-link>
         
          <router-link to="/admin/leaves" class="nav-item">
            <span class="nav-icon">🏖️</span>
            <span class="nav-text">Leave Management</span>
          </router-link>
         
          <router-link to="/admin/tax" class="nav-item">
            <span class="nav-icon">📋</span>
            <span class="nav-text">Tax Configuration</span>
          </router-link>
         
          <router-link to="/admin/reports" class="nav-item">
            <span class="nav-icon">📈</span>
            <span class="nav-text">Reports</span>
          </router-link>
         
          <router-link to="/admin/audit-logs" class="nav-item">
            <span class="nav-icon">🔍</span>
            <span class="nav-text">Audit Logs</span>
          </router-link>
         
          <router-link to="/admin/settings" class="nav-item">
            <span class="nav-icon">⚙️</span>
            <span class="nav-text">Settings</span>
          </router-link>
        </nav>
      </aside>
      <!-- Main Content -->
      <main class="main-content">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import ClockToggle from '@/components/common/Toggle.vue'

export default {
  name: 'AdminLayout',
  components: {
    ClockToggle
  },
  data() {
    return {
      showProfileDropdown: false
    }
  },
  setup() {
    const authStore = useAuthStore()
   
    const logout = async () => {
      try {
        await authStore.logout()
        // Redirect to login page after logout
        window.location.href = '/auth/login'
      } catch (error) {
        console.error('Logout failed:', error)
        // Fallback: clear local storage and redirect
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        window.location.href = '/auth/login'
      }
    }
   
    return {
      logout,
      user: authStore.user
    }
  },
  computed: {
    getUserInitials() {
      if (!this.user?.name) return 'A'
      return this.user.name
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .toUpperCase()
        .substring(0, 2)
    }
  },
  methods: {
    toggleProfileDropdown() {
      this.showProfileDropdown = !this.showProfileDropdown
    },
    closeProfileDropdown() {
      this.showProfileDropdown = false
    },
    handleClickOutside(event) {
      const profileDropdown = this.$el.querySelector('.profile-dropdown')
      if (profileDropdown && !profileDropdown.contains(event.target)) {
        this.closeProfileDropdown()
      }
    }
  },
  mounted() {
    console.log('AdminLayout mounted - current route:', this.$route.path)
    console.log('Current user:', this.user)
    // Add click outside listener
    document.addEventListener('click', this.handleClickOutside)
  },
  beforeUnmount() {
    // Remove click outside listener
    document.removeEventListener('click', this.handleClickOutside)
  }
}
</script>

<style scoped>
.admin-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f5f7fa;
}

/* Header */
.header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 4rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  z-index: 100;
}

.logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.5rem;
  font-weight: 700;
}

.logo-icon {
  font-size: 2rem;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

/* User Profile Dropdown Styles */
.user-profile {
  position: relative;
}

.profile-dropdown {
  position: relative;
  display: inline-block;
}

.profile-trigger {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: rgba(255, 255, 255, 0.15);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.2);
  padding: 0.5rem 1rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s ease;
  font-family: inherit;
}

.profile-trigger:hover {
  background: rgba(255, 255, 255, 0.25);
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.9);
  color: #667eea;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 600;
}

.user-name {
  font-weight: 500;
}

.dropdown-arrow {
  font-size: 0.7rem;
  transition: transform 0.2s ease;
}

.profile-dropdown:hover .dropdown-arrow {
  transform: rotate(180deg);
}

.profile-dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  padding: 0.5rem 0;
  min-width: 180px;
  z-index: 1000;
  margin-top: 0.5rem;
  border: 1px solid #e2e8f0;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0.75rem 1rem;
  text-decoration: none;
  color: #4a5568;
  transition: background-color 0.2s ease;
  font-size: 0.9rem;
  background: none;
  border: none;
  width: 100%;
  text-align: left;
  cursor: pointer;
  font-family: inherit;
}

.dropdown-item:hover {
  background-color: #f7fafc;
  color: #667eea;
}

.logout-dropdown-item {
  color: #e53e3e;
}

.logout-dropdown-item:hover {
  background-color: #fed7d7;
  color: #e53e3e;
}

.dropdown-icon {
  font-size: 1rem;
  width: 20px;
  text-align: center;
}

.dropdown-divider {
  height: 1px;
  background-color: #e2e8f0;
  margin: 0.25rem 0;
}

/* Dropdown overlay for closing when clicking outside */
.dropdown-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 99;
  background: transparent;
}

/* Layout Body */
.layout-body {
  margin-top: 4rem;
  display: flex;
  flex: 1;
}

/* Sidebar */
.sidebar {
  position: fixed;
  top: 4rem;
  left: 0;
  width: 260px;
  height: calc(100vh - 4rem);
  background: white;
  box-shadow: 2px 0 8px rgba(0, 0, 0, 0.05);
  overflow-y: auto;
  z-index: 90;
  display: flex;
  flex-direction: column;
}

.attendance-toggle-section {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.sidebar-nav {
  padding: 1.5rem 0;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.875rem 1.5rem;
  color: #4a5568;
  text-decoration: none;
  transition: all 0.2s ease;
  border-left: 3px solid transparent;
}

.nav-item:hover {
  background: #f7fafc;
  color: #667eea;
}

.nav-item.router-link-active {
  background: #eef2ff;
  color: #667eea;
  border-left-color: #667eea;
  font-weight: 600;
}

.nav-icon {
  font-size: 1.25rem;
  width: 24px;
  text-align: center;
}

.nav-text {
  font-size: 0.95rem;
}

/* Main Content */
.main-content {
  margin-left: 260px;
  margin-top: 0;
  flex: 1;
  padding: 2rem;
  overflow-y: auto;
  background: #f5f7fa;
  min-height: calc(100vh - 4rem);
}

/* Responsive */
@media (max-width: 768px) {
  .sidebar {
    width: 80px;
    left: 0;
  }
  
  .attendance-toggle-section {
    padding: 1rem;
    display: flex;
    justify-content: center;
  }
  
  .nav-text {
    display: none;
  }
  
  .nav-item {
    justify-content: center;
    padding: 1rem;
  }
  
  .main-content {
    margin-left: 80px;
    padding: 1rem;
  }
  
  .logo-text {
    display: none;
  }
  
  .user-name {
    display: none;
  }
  
  .profile-trigger {
    padding: 0.5rem;
  }
  
  .profile-dropdown-menu {
    right: -10px;
    min-width: 160px;
  }
  
  .header {
    padding: 1rem;
  }
}
</style>