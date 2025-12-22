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
      
      <!-- Added overflow-y-auto to allow scrolling if menu is too long -->
      <nav class="nav scrollable-nav">
        <router-link to="/manager/dashboard" class="nav-link" active-class="active">
          <span class="link-icon">🏠</span>
          <span class="link-text">Dashboard</span>
        </router-link>

        <router-link to="/manager/schedule" class="nav-link" active-class="active">
          <span class="link-icon">🗓️</span>
          <span class="link-text">Team Schedule</span>
        </router-link>

        <router-link to="/manager/employees" class="nav-link" active-class="active">
          <span class="link-icon">👥</span>
          <span class="link-text">Employees</span>
        </router-link>

        <router-link to="/manager/attendance" class="nav-link" active-class="active">
          <span class="link-icon">⏰</span>
          <span class="link-text">Attendance</span>
        </router-link>

        <router-link to="/manager/leave-approvals" class="nav-link" active-class="active">
          <span class="link-icon">✔️</span>
          <span class="link-text">Approvals</span>
        </router-link>

        <router-link to="/manager/reports" class="nav-link" active-class="active">
          <span class="link-icon">📈</span>
          <span class="link-text">Reports</span>
        </router-link>

        <router-link to="/manager/productivity" class="nav-link" active-class="active">
          <span class="link-icon">⚡</span>
          <span class="link-text">Productivity</span>
        </router-link>

        <router-link to="/manager/payslips" class="nav-link" active-class="active">
          <span class="link-icon">💵</span>
          <span class="link-text">Payslips</span>
        </router-link>

        <router-link to="/manager/tasks" class="nav-link" active-class="active">
          <span class="link-icon">📋</span>
          <span class="link-text">Assign Tasks</span>
        </router-link>
        
        <!-- ✅ NEW SHIFTS LINK -->
        <!-- Ensure "to" matches your router path exactly (case sensitive) -->
        <router-link to="/manager/Shifts" class="nav-link" active-class="active">
          <span class="link-icon">⏱️</span>
          <span class="link-text">Assign Shifts</span>
        </router-link>
      </nav>

      <div class="sidebar-footer">
        <!-- Optional Footer content -->
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
        this.$router.push('/auth/login')
      } catch (error) {
        console.error('Logout failed:', error)
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        this.$router.push('/auth/login')
      }
    },
    handleClickOutside(event) {
      const dropdown = this.$el.querySelector('.profile-dropdown');
      if (dropdown && !dropdown.contains(event.target)) {
        this.showProfileDropdown = false;
      }
    }
  },
  mounted() {
    document.addEventListener('click', this.handleClickOutside)
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleClickOutside)
  }
}
</script>

<style scoped>
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

.modern-manager-layout {
  display: flex;
  min-height: 100vh;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: var(--background-color);
  overflow: hidden; /* Prevent body scroll if sidebar is fixed */
}

.sidebar {
  width: 240px;
  background-color: var(--sidebar-dark-blue);
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
  padding: 1.5rem 1rem;
  display: flex;
  flex-direction: column;
  height: 100vh;
  position: sticky;
  top: 0;
  flex-shrink: 0; /* Prevent sidebar shrinking */
}

.logo-section {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 1rem;
  color: var(--primary-color);
  flex-shrink: 0;
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

.attendance-toggle-section {
  margin-bottom: 1.5rem;
  padding: 0 0.25rem;
  flex-shrink: 0;
}

/* ✅ FIXED: Added scrolling to nav */
.nav {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  flex-grow: 1;
  overflow-y: auto; /* Enables scrolling */
  padding-right: 5px; /* Prevent scrollbar overlapping text */
}

/* Custom Scrollbar for Nav */
.nav::-webkit-scrollbar {
  width: 4px;
}
.nav::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.05);
}
.nav::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 4px;
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
  flex-shrink: 0; /* Prevent links from squishing */
}

.nav-link:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.nav-link.active {
  background-color: var(--primary-color);
  color: rgb(68, 31, 142) !important;
  box-shadow: 0 4px 8px rgba(0, 123, 255, 0.2);
  font-weight: 600;
}

.nav-link.active .link-icon {
  color: white;
}

.link-icon {
  font-size: 1.1rem;
  color: var(--sidebar-text-color);
  min-width: 24px;
  text-align: center;
}

.sidebar-footer {
  padding-top: 1rem;
  flex-shrink: 0;
}

/* Top Bar & Content */
.content-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow: hidden;
}

.top-bar {
  background-color: white;
  padding: 1rem 3rem;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: flex-end;
  align-items: center;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
  flex-shrink: 0;
}

.main {
  flex: 1;
  padding: 2rem 3rem;
  overflow-y: auto; /* Main content scrolls independently */
}

/* Profile Dropdown */
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
  color: var(--text-color);
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
  width: 100%;
  text-align: left;
  cursor: pointer;
  background: none;
  border: none;
}

.dropdown-item:hover {
  background-color: rgba(0, 123, 255, 0.1);
}

.dropdown-divider {
  height: 1px;
  background-color: var(--border-color);
  margin: 0.25rem 0;
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
  font-weight: 600;
}

.dropdown-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 999;
  background: transparent;
}

/* Responsive */
@media (max-width: 768px) {
  .sidebar {
    width: 70px;
    padding: 1rem 0.5rem;
  }
  
  .title, .user-name, .link-text {
    display: none;
  }
  
  .logo-section {
    justify-content: center;
  }
  
  .nav-link {
    justify-content: center;
    padding: 0.75rem;
  }

  /* This ensures the text spans hide correctly on mobile */
  .nav-link .link-text {
    display: none;
  }
  
  .main {
    padding: 1.5rem;
  }
  
  .top-bar {
    padding: 1rem 1.5rem;
  }
}
</style>