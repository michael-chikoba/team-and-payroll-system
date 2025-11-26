<template>
  <div class="modern-manager-layout">
    <aside class="sidebar">
      <div class="logo-section">
        <span class="logo-icon">📊</span>
        <h1 class="title">Manager Hub</h1>
      </div>
      <div class="attendance-toggle-section">
        <AttendanceToggle />
      </div>
      <nav class="nav">
        <router-link to="/manager/dashboard" class="nav-link" active-class="active">
          <span class="link-icon">🏠</span>
          Dashboard
        </router-link>
        <router-link to="/manager/employees" class="nav-link" active-class="active">
          <span class="link-icon">👥</span>
          Employees
        </router-link>
        <router-link to="/manager/attendance" class="nav-link" active-class="active">
          <span class="link-icon">⏰</span>
          Attendance
        </router-link>
        <router-link to="/manager/leave-approvals" class="nav-link" active-class="active">
          <span class="link-icon">✔️</span>
          Approvals
        </router-link>
        <router-link to="/manager/reports" class="nav-link" active-class="active">
          <span class="link-icon">📈</span>
          Reports
        </router-link>
        <router-link to="/manager/productivity" class="nav-link" active-class="active">
          <span class="link-icon">⚡</span>
          Productivity
        </router-link>
        <router-link to="/manager/payslips" class="nav-link" active-class="active">
          <span class="link-icon">💵</span>
          Payslips
        </router-link>
        <router-link to="/manager/tasks" class="nav-link" active-class="active">
          <span class="link-icon">📋</span>
          Assign Tasks
        </router-link>
      </nav>
      <div class="sidebar-footer">
        <button @click="handleLogout" class="logout-link">
          <span class="link-icon">🚪</span>
          Logout
        </button>
      </div>
    </aside>
    <div class="content-area">
      <header class="top-bar">
        <div class="user-profile">
          <div class="profile-dropdown">
            <button class="profile-trigger" @click="toggleProfileDropdown">
              <span class="user-avatar">M</span>
              <span class="user-name">Hello, Manager!</span>
              <span class="dropdown-arrow">▼</span>
            </button>
            <div v-if="showProfileDropdown" class="profile-dropdown-menu">
              <router-link 
                to="/manager/profile" 
                class="dropdown-item"
                @click="closeProfileDropdown"
              >
                <span class="dropdown-icon">👤</span>
                My Profile
              </router-link>
              <div class="dropdown-divider"></div>
              <button 
                @click="handleLogout"
                class="dropdown-item logout-dropdown-item"
              >
                <span class="dropdown-icon">🚪</span>
                Logout
              </button>
            </div>
          </div>
        </div>
      </header>
      <div 
        v-if="showProfileDropdown" 
        class="dropdown-overlay" 
        @click="closeProfileDropdown"
      ></div>
      <main class="main">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script>
import AttendanceToggle from '../components/common/Toggle.vue'
import { useAuthStore } from '../stores/auth'

export default {
  name: 'ManagerLayout',
  components: {
    AttendanceToggle
  },
  data() {
    return {
      showProfileDropdown: false
    }
  },
  methods: {
    toggleProfileDropdown() {
      this.showProfileDropdown = !this.showProfileDropdown
    },
    closeProfileDropdown() {
      this.showProfileDropdown = false
    },
    async handleLogout() {
      try {
        const authStore = useAuthStore()
        await authStore.logout()
        
        // Redirect to login page
        this.$router.push('/auth/login')
      } catch (error) {
        console.error('Logout failed:', error)
        // Even if API call fails, clear local storage and redirect
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        this.$router.push('/auth/login')
      }
    }
  },
  mounted() {
    // Close dropdown when clicking outside
    document.addEventListener('click', this.handleClickOutside)
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleClickOutside)
  }
}
</script>

<style scoped>
/* Modern Color Palette & Typography */
:root {
  --primary-color: #007bff;
  --background-color: #f4f7f9;
  --sidebar-dark-blue: #001f5b;
  --sidebar-text-color: #ffffff;
  --text-color: #343a40;
  --text-light: #6c757d;
  --border-color: #e9ecef;
  --logout-color: #dc3545;
  --dropdown-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

/* Base Layout */
.modern-manager-layout {
  display: flex;
  min-height: 100vh;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: var(--background-color);
}

/* Sidebar Styling */
.sidebar {
  width: 240px;
  background-color: var(--sidebar-dark-blue);
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
  padding: 1.5rem 1rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  position: sticky;
  top: 0;
  height: 100vh;
}

/* Logo Section */
.logo-section {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 1rem;
  color: var(--primary-color);
}

.logo-icon {
  font-size: 1.8rem;
}

.title {
  margin: 0;
  font-size: 1.4rem;
  font-weight: 600;
  color: rgb(27, 97, 154) !important;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}

/* Attendance Toggle Section */
.attendance-toggle-section {
  margin-bottom: 1.5rem;
  padding: 0 0.25rem;
}

/* Navigation Links */
.nav {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  flex-grow: 1;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  color: var(--sidebar-text-color) !important;
  text-decoration: none;
  padding: 1rem 1.25rem;
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.2s ease;
  font-size: 0.95rem;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.nav-link:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.nav-link.active {
  background-color: var(--primary-color);
  color: rgb(68, 31, 142) !important;
  box-shadow: 0 4px 8px rgba(0, 123, 255, 0.2);
  font-weight: 600;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
}

.nav-link.active .link-icon {
  color: white;
}

.link-icon {
  font-size: 1.1rem;
  color: var(--sidebar-text-color);
  transition: color 0.2s;
}

/* Sidebar Footer (Logout) */
.sidebar-footer {
  padding-top: 1.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.logout-link {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;
  color: var(--logout-color);
  text-decoration: none;
  padding: 1rem 1.25rem;
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.2s ease;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
  background: none;
  border: none;
  cursor: pointer;
  font-family: inherit;
  font-size: inherit;
}

.logout-link:hover {
  background-color: rgba(220, 53, 69, 0.2);
}

/* Profile Dropdown Styles */
.profile-dropdown {
  position: relative;
  display: inline-block;
}

.profile-trigger {
  display: flex;
  align-items: center;
  gap: 10px;
  background: none;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.2s ease;
  color: var(--text-color);
}

.profile-trigger:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

.dropdown-arrow {
  font-size: 0.7rem;
  transition: transform 0.2s ease;
  color: var(--text-light);
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
  box-shadow: var(--dropdown-shadow);
  padding: 0.5rem 0;
  min-width: 180px;
  z-index: 1000;
  margin-top: 0.5rem;
  border: 1px solid var(--border-color);
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0.75rem 1rem;
  text-decoration: none;
  color: var(--text-color);
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
  background-color: rgba(0, 123, 255, 0.1);
  color: var(--primary-color);
}

.logout-dropdown-item {
  color: var(--logout-color);
}

.logout-dropdown-item:hover {
  background-color: rgba(220, 53, 69, 0.1);
  color: var(--logout-color);
}

.dropdown-icon {
  font-size: 1rem;
  width: 20px;
  text-align: center;
}

.dropdown-divider {
  height: 1px;
  background-color: var(--border-color);
  margin: 0.25rem 0;
}

/* Dropdown overlay for closing when clicking outside */
.dropdown-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 999;
  background: transparent;
}

/* User Profile in Top Bar */
.user-profile {
  display: flex;
  align-items: center;
}

.user-avatar {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  background-color: #50e3c2;
  color: var(--text-color);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  font-weight: 600;
}

.user-name {
  font-weight: 500;
  color: var(--text-color);
}

/* Content Area */
.content-area {
  flex: 1;
  display: flex;
  flex-direction: column;
}

/* Top Bar/Header */
.top-bar {
  background-color: white;
  padding: 1rem 3rem;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: flex-end;
  align-items: center;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
}

/* Main Content Wrapper */
.main {
  flex: 1;
  padding: 2rem 3rem;
  overflow-y: auto;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
  .sidebar {
    width: 70px;
    padding: 1rem 0.5rem;
  }
  
  .title, .user-name {
    display: none;
  }
  
  .logo-section {
    justify-content: center;
    margin-bottom: 0.5rem;
  }
  
  .attendance-toggle-section {
    margin-bottom: 1rem;
    display: flex;
    justify-content: center;
  }

  .nav-link {
    justify-content: center;
    padding: 0.75rem;
  }
  
  .nav-link span:not(.link-icon), .logout-link span:not(.link-icon) {
    display: none;
  }
  
  .logout-link {
    justify-content: center;
    padding: 0.75rem;
  }
  
  .main {
    padding: 1.5rem;
  }
  
  .top-bar {
    padding: 1rem 1.5rem;
  }
  
  .profile-trigger .user-name {
    display: none;
  }
  
  .profile-dropdown-menu {
    right: -10px;
    min-width: 160px;
  }
}
</style>