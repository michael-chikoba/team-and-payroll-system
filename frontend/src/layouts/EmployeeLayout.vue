<template>
  <div class="employee-layout">
    <aside class="sidebar">
      <div class="logo-section">
        <span class="logo-icon">🚀</span>
        <h1 class="title">Portal</h1>
      </div>
      <!-- Attendance Toggle Button Section -->
      <div class="attendance-toggle-section">
        <ClockToggle />
      </div>
      <nav class="nav">
        <router-link to="/employee/dashboard" class="nav-link" active-class="active">
          <span class="link-icon">🏠</span>
          <span class="link-text">Dashboard</span>
        </router-link>
        <router-link to="/employee/attendance" class="nav-link" active-class="active">
          <span class="link-icon">⏰</span>
          <span class="link-text">Attendance</span>
        </router-link>
        <router-link to="/employee/leaves" class="nav-link" active-class="active">
          <span class="link-icon">🏖️</span>
          <span class="link-text">Leaves</span>
        </router-link>
        <router-link to="/employee/apply-leave" class="nav-link" active-class="active">
          <span class="link-icon">📝</span>
          <span class="link-text">Apply Leave</span>
        </router-link>
        <router-link to="/employee/payslips" class="nav-link" active-class="active">
          <span class="link-icon">💵</span>
          <span class="link-text">Payslips</span>
        </router-link>
        
        <!-- Tasks link -->
        <router-link
          :to="{ name: 'TaskBoard' }"
          class="nav-link"
          active-class="active"
        >
          <span class="link-icon">🗂️</span>
          <span class="link-text">Tasks</span>
        </router-link>

        <!-- Schedules link -->
        <router-link
          :to="{ name: 'EmployeeSchedules' }"
          class="nav-link"
          active-class="active"
        >
          <span class="link-icon">📅</span>
          <span class="link-text">Schedules</span>
        </router-link>

        <!-- ✅ Added My Shifts link -->
        <router-link
          :to="{ name: 'myshifts' }"
          class="nav-link"
          active-class="active"
        >
          <span class="link-icon">⏱️</span>
          <span class="link-text">My Shifts</span>
        </router-link>
      </nav>
    </aside>
    <div class="content-area">
      <header class="top-header">
        <h2 class="page-title">Welcome Back!</h2>
        
        <!-- Profile Dropdown in Top Right -->
        <div class="profile-dropdown-container">
          <button @click="toggleDropdown" class="profile-button">
            <span class="avatar-placeholder">{{ getInitials() }}</span>
            <span class="user-name-header">{{ authStore.user?.fullName || 'Employee' }}</span>
            <span class="dropdown-arrow" :class="{ 'open': isDropdownOpen }">▼</span>
          </button>
          
          <!-- Dropdown Menu -->
          <transition name="dropdown">
            <div v-if="isDropdownOpen" class="dropdown-menu">
              <router-link to="/employee/profile" class="dropdown-item" @click="closeDropdown">
                <span class="dropdown-icon">👤</span>
                Profile
              </router-link>
              <div class="dropdown-divider"></div>
              <button @click="logout" class="dropdown-item logout-item">
                <span class="dropdown-icon">➡️</span>
                Logout
              </button>
            </div>
          </transition>
        </div>
      </header>
      <main class="main">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import ClockToggle from '@/components/common/Toggle.vue'
import { ref, onMounted, onUnmounted } from 'vue'

export default {
  name: 'ModernEmployeeLayout',
  components: {
    ClockToggle
  },
  setup() {
    const authStore = useAuthStore()
    const isDropdownOpen = ref(false)

    const toggleDropdown = () => {
      isDropdownOpen.value = !isDropdownOpen.value
    }

    const closeDropdown = () => {
      isDropdownOpen.value = false
    }

    const getInitials = () => {
      const name = authStore.user?.fullName || 'Employee'
      return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2)
    }

    const handleClickOutside = (event) => {
      const dropdown = document.querySelector('.profile-dropdown-container')
      if (dropdown && !dropdown.contains(event.target)) {
        closeDropdown()
      }
    }

    onMounted(() => {
      document.addEventListener('click', handleClickOutside)
    })

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
    })

    return { 
      authStore, 
      isDropdownOpen, 
      toggleDropdown, 
      closeDropdown,
      getInitials
    }
  },
  methods: {
    logout() {
      this.authStore.clearAuth()
      this.$router.push({ name: 'login' })
    },
  },
}
</script>

<style scoped>
/* Color Palette */
:root {
  --primary-color: #4a90e2;
  --secondary-color: #50e3c2;
  --background-color: #f8f9fb;
  --surface-color: #ffffff;
  --text-color: #333333;
  --text-light: #7a7a7a;
  --border-color: #eaeaea;
}

/* Base Layout */
.employee-layout {
  display: flex;
  min-height: 100vh;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background-color: var(--background-color);
}

/* Sidebar Styling */
.sidebar {
  width: 260px;
  background-color: var(--surface-color);
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
  padding: 2rem 1rem;
  display: flex;
  flex-direction: column;
  position: sticky;
  top: 0;
  height: 100vh;
}

.logo-section {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 1rem;
  padding: 0 0.5rem;
  color: var(--primary-color);
}

.title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-color);
}

.attendance-toggle-section {
  margin-bottom: 1.5rem;
  padding: 0 0.25rem;
}

.nav {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  flex: 1;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 15px;
  color: var(--text-light);
  text-decoration: none;
  padding: 1rem 1.25rem;
  border-radius: 12px;
  font-weight: 500;
  transition: all 0.2s ease;
  font-size: 1rem;
}

.nav-link:hover {
  background-color: #f0f4f8;
  color: var(--primary-color);
}

.nav-link.active {
  background-color: rgba(74, 144, 226, 0.1);
  color: var(--primary-color);
  font-weight: 600;
}

.link-icon {
  font-size: 1.2rem;
}

.content-area {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.top-header {
  background-color: var(--surface-color);
  padding: 1.5rem 3rem;
  border-bottom: 1px solid var(--border-color);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.page-title {
  margin: 0;
  font-size: 1.8rem;
  font-weight: 300;
  color: var(--text-color);
}

.profile-dropdown-container {
  position: relative;
}

.profile-button {
  display: flex;
  align-items: center;
  gap: 12px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem 1rem;
  border-radius: 12px;
  transition: all 0.2s ease;
}

.profile-button:hover {
  background-color: #f0f4f8;
}

.avatar-placeholder {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background-color: var(--primary-color);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  font-weight: 600;
}

.user-name-header {
  font-weight: 600;
  color: var(--text-color);
  font-size: 0.95rem;
}

.dropdown-arrow {
  font-size: 0.7rem;
  color: var(--text-light);
  transition: transform 0.2s ease;
}

.dropdown-arrow.open {
  transform: rotate(180deg);
}

.dropdown-menu {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  background-color: var(--surface-color);
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  min-width: 200px;
  padding: 0.5rem 0;
  z-index: 1000;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0.75rem 1.25rem;
  color: var(--text-color);
  text-decoration: none;
  border: none;
  background: none;
  width: 100%;
  text-align: left;
  cursor: pointer;
  font-size: 0.95rem;
  font-weight: 500;
  transition: all 0.2s ease;
}

.dropdown-item:hover {
  background-color: #f0f4f8;
}

.dropdown-icon {
  font-size: 1.1rem;
}

.dropdown-divider {
  height: 1px;
  background-color: var(--border-color);
  margin: 0.5rem 0;
}

.logout-item {
  color: #e24a4a;
}

.logout-item:hover {
  background-color: rgba(226, 74, 74, 0.1);
}

.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.main {
  flex: 1;
  padding: 2rem 3rem;
  overflow-y: auto;
}

@media (max-width: 992px) {
  .sidebar { width: 80px; padding: 1rem 0.5rem; }
  .title, .link-text { display: none; }
  .logo-section { justify-content: center; }
  .nav-link { justify-content: center; }
  .user-name-header { display: none; }
}
</style>