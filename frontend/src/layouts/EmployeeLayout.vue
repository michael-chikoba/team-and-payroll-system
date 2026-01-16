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

        <!-- Tickets Link -->
        <router-link :to="{ name: 'mytickets' }" class="nav-link" active-class="active">
          <span class="link-icon">🎫</span>
          <span class="link-text">Tickets</span>
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
          
          <!-- Notification Bell Button -->
          <NotificationBell />
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
                <h3>Chat</h3>
                <button @click="closeChartModal" class="modal-close-btn">×</button>
              </div>
              <div class="modal-content">
                <ChartComponent v-if="showChartModal" :key="chartKey" />
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
import NotificationBell from '@/components/NotificationBell.vue'
import axios from 'axios'

export default {
  name: 'ModernEmployeeLayout',
  components: {
    ClockToggle,
    ChartComponent,
    CreateTicketModal,
    NotificationBell
  },
  setup() {
    const authStore = useAuthStore()
    const router = useRouter()
    const route = useRoute()
    
    // State
    const isDropdownOpen = ref(false)
    const showChartModal = ref(false)
    const showQuickCreateModal = ref(false)
    const pendingTicketsCount = ref(0)
    const chartKey = ref(0)

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
      chartKey.value++
      showChartModal.value = true
      closeDropdown()
      document.body.style.overflow = 'hidden'
    }
    
    const closeChartModal = () => {
      showChartModal.value = false
      document.body.style.overflow = 'auto'
    }
    
    const openQuickCreateTicket = () => showQuickCreateModal.value = true
    const closeQuickCreateModal = () => showQuickCreateModal.value = false

    const handleTicketCreated = () => {
      closeQuickCreateModal()
      if (route.name === 'mytickets') {
        window.dispatchEvent(new CustomEvent('refresh-tickets'))
      }
      fetchPendingTicketsCount()
    }

    const goToDetailedCharts = () => {
      closeChartModal()
      router.push({ name: 'charts' })
    }

    const getInitials = () => {
      const name = authStore.user?.fullName || 'Employee'
      return name.split(' ').map(word => word[0]).join('').toUpperCase().slice(0, 2)
    }

    // API Calls
    const fetchPendingTicketsCount = async () => {
      try {
        const response = await axios.get('/api/tickets/count', { 
          params: { status: 'pending' } 
        }).catch(() => ({ data: { total: 0 } }))
        
        pendingTicketsCount.value = response.data.total || 0
      } catch (error) {
        console.error('Failed to fetch ticket count:', error)
      }
    }

    const handleClickOutside = (event) => {
      const dropdown = document.querySelector('.profile-dropdown-container')
      if (dropdown && !dropdown.contains(event.target)) {
        closeDropdown()
      }
    }

    const handleEscapeKey = (event) => {
      if (event.key === 'Escape') {
        if (showChartModal.value) closeChartModal()
        if (showQuickCreateModal.value) closeQuickCreateModal()
        if (isDropdownOpen.value) closeDropdown()
      }
    }

    // Lifecycle hooks
    onMounted(() => {
      document.addEventListener('click', handleClickOutside)
      document.addEventListener('keydown', handleEscapeKey)
      fetchPendingTicketsCount()
      
      const intervalId = setInterval(fetchPendingTicketsCount, 300000)
      
      onUnmounted(() => {
        clearInterval(intervalId)
        document.removeEventListener('click', handleClickOutside)
        document.removeEventListener('keydown', handleEscapeKey)
        document.body.style.overflow = 'auto'
      })
    })

    // Watch route changes to refresh ticket count
    watch(() => route.path, () => {
      if (route.name === 'mytickets') fetchPendingTicketsCount()
    })

    return { 
      authStore, 
      isDropdownOpen, 
      showChartModal, 
      showQuickCreateModal,
      pendingTicketsCount, 
      currentPageTitle, 
      showQuickCreateTicket,
      chartKey,
      toggleDropdown, 
      closeDropdown, 
      openChartModal, 
      closeChartModal,
      openQuickCreateTicket, 
      closeQuickCreateModal, 
      handleTicketCreated,
      goToDetailedCharts, 
      getInitials, 
      logout: () => {
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
  --modal-overlay: rgba(0, 0, 0, 0.6);
}

/* Layout */
.employee-layout {
  display: flex;
  min-height: 100vh;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background-color: var(--background-color);
  overflow: hidden;
}

/* Sidebar Styling */
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
  flex-shrink: 0;
}

.logo-icon {
  font-size: 1.8rem;
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

/* Navigation Scroll Styling */
.nav {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
  overflow-y: auto;
  padding-right: 5px;
  margin-right: -5px;
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
  padding: 0.85rem 1.25rem;
  border-radius: 12px;
  font-weight: 500;
  transition: all 0.2s ease;
  font-size: 0.95rem;
  position: relative;
  flex-shrink: 0;
}

.nav-link:hover {
  background-color: #f0f4f8;
  color: var(--primary-color);
  transform: translateX(2px);
}

.nav-link.active {
  background-color: rgba(74, 144, 226, 0.1);
  color: var(--primary-color);
  font-weight: 600;
}

.nav-link.active[href*="tickets"] {
  background-color: rgba(255, 107, 107, 0.1);
  color: var(--ticket-color);
}

.link-icon { 
  font-size: 1.2rem; 
  flex-shrink: 0;
}

.link-text {
  flex: 1;
}

/* Badge Styling */
.notification-badge-sidebar {
  margin-left: auto;
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
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
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
  gap: 1rem;
  flex-wrap: wrap;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-color);
  margin: 0;
}

.quick-actions { 
  display: flex; 
  gap: 10px; 
  align-items: center; 
}

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
  font-size: 0.9rem;
  color: var(--text-color);
}

.quick-action-button:hover {
  border-color: var(--primary-color);
  color: var(--primary-color);
  background-color: rgba(74, 144, 226, 0.05);
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.quick-action-icon {
  font-size: 1.1rem;
}

/* Profile Dropdown */
.profile-dropdown-container { 
  position: relative; 
}

.profile-button {
  display: flex;
  align-items: center;
  gap: 10px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 8px;
  transition: background-color 0.2s;
}

.profile-button:hover {
  background-color: #f0f4f8;
}

.avatar-placeholder {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.9rem;
}

.user-name-header {
  font-weight: 500;
  color: var(--text-color);
}

.dropdown-arrow {
  font-size: 0.7rem;
  transition: transform 0.2s;
  color: var(--text-light);
}

.dropdown-arrow.open {
  transform: rotate(180deg);
}

.dropdown-menu {
  position: absolute;
  top: 120%;
  right: 0;
  background-color: var(--surface-color);
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  min-width: 220px;
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
  font-size: 0.9rem;
  transition: background-color 0.15s;
}

.dropdown-item:hover { 
  background-color: #f0f4f8; 
}

.dropdown-icon {
  font-size: 1.1rem;
}

.dropdown-badge {
  margin-left: auto;
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

.dropdown-divider { 
  border-bottom: 1px solid var(--border-color); 
  margin: 0.5rem 0; 
}

.logout-item { 
  color: var(--danger-color);
  font-weight: 500;
}

/* Main Content */
.main {
  flex: 1;
  padding: 2rem;
  overflow-y: auto;
  overflow-x: hidden;
}

/* Modal Styling */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: var(--modal-overlay);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 1rem;
  backdrop-filter: blur(4px);
}

.modal-container {
  background-color: var(--surface-color);
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  max-width: 1000px;
  width: 100%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
  from {
    opacity: 0;
    transform: translateY(-20px) scale(0.95);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid var(--border-color);
}

.modal-header h3 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-color);
}

.modal-close-btn {
  background: none;
  border: none;
  font-size: 2rem;
  color: var(--text-light);
  cursor: pointer;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: all 0.2s;
}

.modal-close-btn:hover {
  background-color: #f0f4f8;
  color: var(--danger-color);
}

.modal-content {
  flex: 1;
  padding: 2rem;
  overflow-y: auto;
  overflow-x: hidden;
  min-height: 400px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem 2rem;
  border-top: 1px solid var(--border-color);
}

.btn-primary, .btn-secondary {
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  font-size: 0.95rem;
}

.btn-primary {
  background-color: var(--primary-color);
  color: white;
}

.btn-primary:hover {
  background-color: #3a7bc8;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(74, 144, 226, 0.3);
}

.btn-secondary {
  background-color: transparent;
  color: var(--text-color);
  border: 1px solid var(--border-color);
}

.btn-secondary:hover {
  background-color: #f0f4f8;
}

/* Transitions */
.dropdown-enter-active, .dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from, .dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

.modal-enter-active, .modal-leave-active {
  transition: all 0.3s ease;
}

.modal-enter-from, .modal-leave-to {
  opacity: 0;
}

.modal-enter-from .modal-container,
.modal-leave-to .modal-container {
  transform: translateY(-20px) scale(0.95);
}

/* Mobile Responsiveness */
@media (max-width: 992px) {
  .sidebar { 
    width: 70px; 
    padding: 1rem 0.5rem; 
  }
  
  .title, 
  .link-text, 
  .user-name-header, 
  .quick-action-text { 
    display: none; 
  }
  
  .nav-link { 
    justify-content: center; 
    padding: 1rem 0.5rem; 
  }
  
  .logo-section { 
    justify-content: center; 
  }
  
  .notification-badge-sidebar {
    position: absolute;
    top: 5px;
    right: 5px;
    margin: 0;
    min-width: 16px;
    height: 16px;
  }
  
  .top-header {
    padding: 1rem;
  }
  
  .page-title {
    font-size: 1.2rem;
  }
  
  .main {
    padding: 1rem;
  }
  
  .modal-container {
    max-width: 95%;
    max-height: 95vh;
  }
  
  .modal-header,
  .modal-footer {
    padding: 1rem;
  }
  
  .modal-content {
    padding: 1rem;
    min-height: 300px;
  }
}

@media (max-width: 576px) {
  .quick-actions {
    order: 3;
    width: 100%;
    justify-content: flex-start;
  }
  
  .modal-footer {
    flex-direction: column;
  }
  
  .btn-primary,
  .btn-secondary {
    width: 100%;
  }
}
</style>