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

      <!-- Navigation Menu -->
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
        <router-link :to="{ name: 'TaskBoard' }" class="nav-link" active-class="active">
          <span class="link-icon">🗂️</span>
          <span class="link-text">Tasks</span>
        </router-link>

        <!-- Schedules link -->
        <router-link :to="{ name: 'EmployeeSchedules' }" class="nav-link" active-class="active">
          <span class="link-icon">📅</span>
          <span class="link-text">Schedules</span>
        </router-link>

        <!-- My Shifts link -->
        <router-link :to="{ name: 'myshifts' }" class="nav-link" active-class="active">
          <span class="link-icon">⏱️</span>
          <span class="link-text">My Shifts</span>
        </router-link>

        <!-- ✅ TICKETS LINK - Positioned here for visibility -->
        <router-link :to="{ name: 'mytickets' }" class="nav-link" active-class="active">
          <span class="link-icon">🎫</span>
          <span class="link-text">Tickets</span>
          <!-- Badge only shows if count is greater than 0 -->
          <span v-if="pendingTicketsCount > 0" class="notification-badge-sidebar">
            {{ pendingTicketsCount }}
          </span>
        </router-link>

        <!-- Reports link -->
        <router-link :to="{ name: 'charts' }" class="nav-link" active-class="active">
          <span class="link-icon">📊</span>
          <span class="link-text">Reports</span>
        </router-link>
      </nav>
    </aside>

    <div class="content-area">
      <header class="top-header">
        <h2 class="page-title">{{ currentPageTitle }}</h2>
        
        <!-- Quick Action Buttons -->
        <div class="quick-actions">
          <!-- Create Ticket Button (Visible on Tickets page) -->
          <button 
            v-if="showQuickCreateTicket"
            @click="openQuickCreateTicket" 
            class="quick-action-button"
            title="Create New Ticket"
          >
            <span class="quick-action-icon">🎫</span>
            <span class="quick-action-text">New Ticket</span>
          </button>
          
          <button @click="openChartModal" class="quick-action-button" title="View Charts">
            <span class="quick-action-icon">📊</span>
            <span class="quick-action-text">Charts</span>
          </button>
          
          <button @click="openNotifications" class="quick-action-button" title="Notifications">
            <span class="quick-action-icon">🔔</span>
            <span class="quick-action-text">Notifications</span>
            <span v-if="unreadCount > 0" class="notification-badge">{{ unreadCount }}</span>
          </button>
        </div>
        
        <!-- Profile Dropdown -->
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
                <span class="dropdown-icon">👤</span> Profile
              </router-link>
              <router-link :to="{ name: 'mytickets' }" class="dropdown-item" @click="closeDropdown">
                <span class="dropdown-icon">🎫</span> My Tickets
                <span v-if="pendingTicketsCount > 0" class="dropdown-badge">{{ pendingTicketsCount }}</span>
              </router-link>
              <router-link to="/employee/charts" class="dropdown-item" @click="closeDropdown">
                <span class="dropdown-icon">📊</span> Reports & Charts
              </router-link>
              <div class="dropdown-divider"></div>
              <button @click="logout" class="dropdown-item logout-item">
                <span class="dropdown-icon">➡️</span> Logout
              </button>
            </div>
          </transition>
        </div>
      </header>

      <main class="main">
        <router-view />
        
        <!-- Chart Modal -->
        <transition name="modal">
          <div v-if="showChartModal" class="modal-overlay" @click.self="closeChartModal">
            <div class="modal-container">
              <div class="modal-header">
                <h3>📊 Analytics Dashboard</h3>
                <button @click="closeChartModal" class="modal-close-btn">×</button>
              </div>
              <div class="modal-content">
                <ChartComponent />
              </div>
              <div class="modal-footer">
                <button @click="goToDetailedCharts" class="btn-primary">View Detailed Reports</button>
                <button @click="closeChartModal" class="btn-secondary">Close</button>
              </div>
            </div>
          </div>
        </transition>

        <!-- Quick Create Ticket Modal -->
        <CreateTicketModal
          v-if="showQuickCreateModal"
          :show="showQuickCreateModal"
          @close="closeQuickCreateModal"
          @created="handleTicketCreated"
        />
      </main>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import { useRoute, useRouter } from 'vue-router'
import { computed, ref, onMounted, onUnmounted, watch } from 'vue'
import ClockToggle from '@/components/common/Toggle.vue'
import ChartComponent from '@/components/ChatInterface.vue'
import CreateTicketModal from '@/components/Tickets/CreateTicketModal.vue'
import axios from 'axios'

export default {
  name: 'ModernEmployeeLayout',
  components: {
    ClockToggle,
    ChartComponent,
    CreateTicketModal
  },
  setup() {
    const authStore = useAuthStore()
    const router = useRouter()
    const route = useRoute()
    
    // State
    const isDropdownOpen = ref(false)
    const showChartModal = ref(false)
    const showQuickCreateModal = ref(false)
    const unreadCount = ref(3)
    const pendingTicketsCount = ref(0)

    // Compute current page title
    const currentPageTitle = computed(() => {
      return route.meta?.title || 'Welcome Back!'
    })

    // Show quick create ticket button only on tickets page
    const showQuickCreateTicket = computed(() => {
      return route.name === 'mytickets'
    })

    // Dropdown Logic
    const toggleDropdown = () => isDropdownOpen.value = !isDropdownOpen.value
    const closeDropdown = () => isDropdownOpen.value = false

    // Modal Logic
    const openChartModal = () => {
      showChartModal.value = true
      closeDropdown()
    }
    const closeChartModal = () => showChartModal.value = false
    
    const openQuickCreateTicket = () => showQuickCreateModal.value = true
    const closeQuickCreateModal = () => showQuickCreateModal.value = false

    const handleTicketCreated = () => {
      closeQuickCreateModal()
      if (route.name === 'mytickets') {
        // Emit event to refresh tickets list if a global bus exists, or handle locally
        window.dispatchEvent(new CustomEvent('refresh-tickets'))
      }
      fetchPendingTicketsCount()
    }

    const goToDetailedCharts = () => {
      closeChartModal()
      router.push({ name: 'charts' })
    }

    const openNotifications = () => {
      alert('Notifications feature coming soon!')
    }

    const getInitials = () => {
      const name = authStore.user?.fullName || 'Employee'
      return name.split(' ').map(word => word[0]).join('').toUpperCase().slice(0, 2)
    }

    // API Calls
    const fetchPendingTicketsCount = async () => {
      try {
        // Ensure this endpoint exists in your backend
        const response = await axios.get('/api/tickets/count', { 
          params: { status: 'pending' } 
        }).catch(() => ({ data: { total: 0 } })) // Fallback if API fails
        
        pendingTicketsCount.value = response.data.total || 0
      } catch (error) {
        console.error('Failed to fetch ticket count')
      }
    }

    const handleClickOutside = (event) => {
      const dropdown = document.querySelector('.profile-dropdown-container')
      if (dropdown && !dropdown.contains(event.target)) {
        closeDropdown()
      }
    }

    onMounted(() => {
      document.addEventListener('click', handleClickOutside)
      fetchPendingTicketsCount()
      const intervalId = setInterval(fetchPendingTicketsCount, 300000) // 5 mins
      onUnmounted(() => clearInterval(intervalId))
    })

    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
    })

    watch(() => route.path, () => {
      if (route.name === 'mytickets') fetchPendingTicketsCount()
    })

    return { 
      authStore, isDropdownOpen, showChartModal, showQuickCreateModal,
      unreadCount, pendingTicketsCount, currentPageTitle, showQuickCreateTicket,
      toggleDropdown, closeDropdown, openChartModal, closeChartModal,
      openQuickCreateTicket, closeQuickCreateModal, handleTicketCreated,
      goToDetailedCharts, openNotifications, getInitials, logout: () => {
        authStore.clearAuth()
        router.push({ name: 'login' })
      }
    }
  }
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
  --ticket-color: #ff6b6b;
  --danger-color: #d9534f;
}

/* Layout */
.employee-layout {
  display: flex;
  min-height: 100vh;
  font-family: 'Inter', sans-serif;
  background-color: var(--background-color);
  overflow: hidden; /* Prevents double scrollbars */
}

/* Sidebar Styling - UPDATED FOR VISIBILITY */
.sidebar {
  width: 260px;
  background-color: var(--surface-color);
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
  padding: 1.5rem 1rem;
  display: flex;
  flex-direction: column;
  height: 100vh;
  position: sticky;
  top: 0;
  z-index: 100;
}

.logo-section {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 1rem;
  padding: 0 0.5rem;
  flex-shrink: 0; /* Prevents logo from shrinking */
}

.title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-color);
}

.attendance-toggle-section {
  margin-bottom: 1rem;
  padding: 0 0.25rem;
  flex-shrink: 0;
}

/* IMPORTANT: Navigation Scroll Styling */
.nav {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
  overflow-y: auto; /* ENABLE SCROLLING FOR SIDEBAR */
  padding-right: 5px; /* Space for scrollbar */
  margin-right: -5px;
  
  /* Modern Scrollbar Styling */
  scrollbar-width: thin;
  scrollbar-color: #ddd transparent;
}

.nav::-webkit-scrollbar {
  width: 4px;
}

.nav::-webkit-scrollbar-thumb {
  background-color: #ddd;
  border-radius: 4px;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 15px;
  color: var(--text-light);
  text-decoration: none;
  padding: 0.85rem 1.25rem; /* Slightly reduced padding to fit more items */
  border-radius: 12px;
  font-weight: 500;
  transition: all 0.2s ease;
  font-size: 0.95rem;
  position: relative;
  flex-shrink: 0; /* Prevents links from squishing */
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

/* Specific styling for Tickets active state */
.nav-link.active[href*="tickets"] {
  background-color: rgba(255, 107, 107, 0.1);
  color: var(--ticket-color);
}

.link-icon { font-size: 1.2rem; }

/* Badge Styling */
.notification-badge-sidebar {
  margin-left: auto; /* Pushes badge to the far right */
  background-color: var(--ticket-color);
  color: white;
  border-radius: 10px;
  min-width: 20px;
  height: 20px;
  font-size: 0.7rem;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 6px;
  font-weight: 600;
}

/* Content Area */
.content-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow: hidden;
}

.top-header {
  background-color: var(--surface-color);
  padding: 1rem 2rem;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-shrink: 0;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 400;
  color: var(--text-color);
}

.quick-actions { display: flex; gap: 10px; align-items: center; }

.quick-action-button {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--surface-color);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  padding: 8px 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
}

.quick-action-button:hover {
  border-color: var(--primary-color);
  color: var(--primary-color);
}

.notification-badge {
  position: absolute;
  top: -5px;
  right: -5px;
  background-color: var(--danger-color);
  color: white;
  border-radius: 50%;
  width: 16px;
  height: 16px;
  font-size: 0.65rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Dropdown */
.profile-dropdown-container { position: relative; }

.profile-button {
  display: flex;
  align-items: center;
  gap: 10px;
  background: none;
  border: none;
  cursor: pointer;
}

.avatar-placeholder {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background-color: var(--primary-color);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
}

.dropdown-menu {
  position: absolute;
  top: 120%;
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
  gap: 10px;
  padding: 0.75rem 1rem;
  color: var(--text-color);
  text-decoration: none;
  width: 100%;
  text-align: left;
  border: none;
  background: none;
  cursor: pointer;
}

.dropdown-item:hover { background-color: #f0f4f8; }
.dropdown-divider { border-bottom: 1px solid var(--border-color); margin: 4px 0; }
.logout-item { color: var(--danger-color); }

/* Main Content */
.main {
  flex: 1;
  padding: 2rem;
  overflow-y: auto; /* Content scrolls independently */
}

/* Transitions */
.dropdown-enter-active, .dropdown-leave-active, 
.modal-enter-active, .modal-leave-active {
  transition: all 0.2s ease;
}
.dropdown-enter-from, .dropdown-leave-to, 
.modal-enter-from, .modal-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Mobile Responsiveness */
@media (max-width: 992px) {
  .sidebar { width: 70px; padding: 1rem 0.5rem; }
  .title, .link-text, .user-name-header, .quick-action-text { display: none; }
  .nav-link { justify-content: center; padding: 1rem 0.5rem; }
  .logo-section { justify-content: center; }
  .notification-badge-sidebar {
    position: absolute;
    top: 5px;
    right: 5px;
    margin: 0;
    min-width: 16px;
    height: 16px;
  }
}
</style>