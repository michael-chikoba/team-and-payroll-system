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
        
        <!-- Chat Navigation Link -->
        <button @click="openChartModal" class="nav-link nav-button" :class="{ 'active': showChartModal }">
          <span class="link-icon">💬</span>
          <span class="link-text">Chat</span>
          <span v-if="unreadCount > 0 && !showChartModal" class="nav-badge">{{ unreadCount }}</span>
        </button>
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
         
          <button 
            @click="openChartModal" 
            class="quick-action-button"
            :title="showChartModal ? 'Close Chat' : 'Open Chat'"
          >
            <span class="quick-action-icon">💬</span>
            <span class="quick-action-text">Chat</span>
            <span v-if="unreadCount > 0 && !showChartModal" class="notification-badge">{{ unreadCount }}</span>
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
              <div class="dropdown-header">
                <span class="dropdown-avatar">{{ getInitials() }}</span>
                <div class="dropdown-user-info">
                  <span class="dropdown-user-name">{{ authStore.user?.fullName || 'Employee' }}</span>
                  <span class="dropdown-user-email">{{ authStore.user?.email || '' }}</span>
                </div>
              </div>
              <div class="dropdown-divider"></div>
              <router-link to="/employee/profile" class="dropdown-item" @click="closeDropdown">
                <span class="dropdown-icon">👤</span> 
                <span class="dropdown-item-text">Profile</span>
              </router-link>
              <router-link :to="{ name: 'mytickets' }" class="dropdown-item" @click="closeDropdown">
                <span class="dropdown-icon">🎫</span> 
                <span class="dropdown-item-text">My Tickets</span>
                <span v-if="pendingTicketsCount > 0" class="dropdown-badge">{{ pendingTicketsCount }}</span>
              </router-link>
              <router-link to="/employee/charts" class="dropdown-item" @click="closeDropdown">
                <span class="dropdown-icon">📊</span> 
                <span class="dropdown-item-text">Reports & Charts</span>
              </router-link>
              <div class="dropdown-divider"></div>
              <button @click="handleLogout" class="dropdown-item logout-item">
                <span class="dropdown-icon">🚪</span> 
                <span class="dropdown-item-text">Logout</span>
              </button>
            </div>
          </transition>
        </div>
      </header>
      <main class="main">
        <router-view />
       
        <!-- Chat Modal -->
        <transition name="modal">
          <div v-if="showChartModal" class="modal-overlay" @click.self="closeChartModal">
            <div class="modal-container">
              <div class="modal-header">
                <h3>💬 Team Chat</h3>
                <button @click="closeChartModal" class="modal-close-btn" title="Close Chat">×</button>
              </div>
              <div class="modal-content">
                <!-- Use the Slack-style ChatInterface component -->
                <ChartComponent 
                  v-if="showChartModal" 
                  :key="chartKey" 
                  @unread-count="updateUnreadCount"
                />
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
    const unreadCount = ref(0)
    let ticketRefreshInterval = null
    
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
      unreadCount.value = 0
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
    
    const updateUnreadCount = (count) => {
      if (!showChartModal.value) {
        unreadCount.value = count
      }
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
    
    // Enhanced Logout Function - Simplified to use authStore
    const handleLogout = async () => {
      try {
        await authStore.logout()
        router.push('/auth/login')
      } catch (error) {
        console.error('Logout failed:', error)
        // Fallback: Clear local storage and redirect
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        router.push('/auth/login')
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
     
      // Set up interval to refresh ticket count every 5 minutes
      ticketRefreshInterval = setInterval(fetchPendingTicketsCount, 300000)
      
      // Listen for ticket updates
      window.addEventListener('ticket-created', fetchPendingTicketsCount)
      window.addEventListener('ticket-updated', fetchPendingTicketsCount)
     
      onUnmounted(() => {
        if (ticketRefreshInterval) {
          clearInterval(ticketRefreshInterval)
        }
        document.removeEventListener('click', handleClickOutside)
        document.removeEventListener('keydown', handleEscapeKey)
        document.body.style.overflow = 'auto'
        
        // Remove event listeners
        window.removeEventListener('ticket-created', fetchPendingTicketsCount)
        window.removeEventListener('ticket-updated', fetchPendingTicketsCount)
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
      unreadCount,
      toggleDropdown,
      closeDropdown,
      openChartModal,
      closeChartModal,
      openQuickCreateTicket,
      closeQuickCreateModal,
      handleTicketCreated,
      goToDetailedCharts,
      updateUnreadCount,
      getInitials,
      handleLogout
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
  --text-color: #111827; /* Darker black */
  --text-light: #4b5563;
  --border-color: #eaeaea;
  --ticket-color: #ff6b6b;
  --danger-color: #d9534f;
  --modal-overlay: rgba(0, 0, 0, 0.6);
  --logout-color: #dc3545;
  --chat-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
  --nav-hover-bg: #f5f8ff;
  --nav-active-bg: #edf4ff;
  --sidebar-bg: #ffffff;
  /* Updated nav colors for visibility */
  --nav-text: #111827; /* Deep Charcoal/Black */
  --nav-text-active: #1a56db;
  --nav-border: #f0f0f0;
  --dropdown-bg: #ffffff;
  --dropdown-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
}

/* Layout */
.employee-layout {
  display: flex;
  min-height: 100vh;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background-color: var(--background-color);
  overflow: hidden;
}

/* Sidebar Styling - Updated for clear white background */
.sidebar {
  width: 260px;
  background-color: var(--sidebar-bg);
  box-shadow: 2px 0 15px rgba(0, 0, 0, 0.08);
  padding: 1.5rem 1rem;
  display: flex;
  flex-direction: column;
  height: 100vh;
  position: sticky;
  top: 0;
  z-index: 100;
  border-right: 1px solid var(--nav-border);
}

.logo-section {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 2rem;
  padding: 0 0.5rem;
  flex-shrink: 0;
  border-bottom: 1px solid var(--nav-border);
  padding-bottom: 1rem;
}

.logo-icon {
  font-size: 2rem;
  background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.title {
  margin: 0;
  font-size: 1.6rem;
  font-weight: 800; /* Bolder */
  color: var(--text-color);
  letter-spacing: -0.5px;
}

.attendance-toggle-section {
  margin-bottom: 2rem;
  padding: 1rem;
  flex-shrink: 0;
  background-color: #f8fafc;
  border-radius: 12px;
  border: 1px solid var(--border-color);
}

/* Navigation Scroll Styling */
.nav {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  flex: 1;
  overflow-y: auto;
  padding-right: 8px;
  margin-right: -8px;
  scrollbar-width: thin;
  scrollbar-color: #c1c1c1 transparent;
}

.nav::-webkit-scrollbar {
  width: 6px;
}

.nav::-webkit-scrollbar-thumb {
  background-color: #c1c1c1;
  border-radius: 4px;
}

.nav::-webkit-scrollbar-thumb:hover {
  background-color: #a0a0a0;
}

/* UPDATED NAV LINK STYLING FOR VISIBILITY */
.nav-link,
.nav-button {
  display: flex;
  align-items: center;
  gap: 14px;
  color: var(--nav-text); /* Uses deep black now */
  text-decoration: none;
  padding: 0.9rem 1.25rem;
  border-radius: 10px;
  font-weight: 600; /* Increased from 500 to 600 (Semi-Bold) */
  transition: all 0.2s ease;
  font-size: 1rem; /* Slightly larger base size */
  position: relative;
  flex-shrink: 0;
  background: transparent;
  border: 1px solid transparent;
  width: 100%;
  text-align: left;
  cursor: pointer;
  font-family: inherit;
}

.nav-link:hover,
.nav-button:hover {
  background-color: var(--nav-hover-bg);
  color: #000000; /* Pure black on hover */
  transform: translateX(2px);
  border-color: var(--border-color);
  box-shadow: 0 2px 6px rgba(74, 144, 226, 0.1);
}

.nav-link.active,
.nav-button.active {
  background-color: var(--nav-active-bg);
  color: var(--nav-text-active);
  font-weight: 700; /* Bold when active */
  border-left: 4px solid var(--primary-color); /* Thicker indicator */
  box-shadow: 0 2px 8px rgba(74, 144, 226, 0.15);
}

.nav-link.active[href*="tickets"] {
  background-color: rgba(255, 107, 107, 0.1);
  color: var(--ticket-color);
  border-left: 4px solid var(--ticket-color);
}

.nav-button.active {
  background-color: var(--nav-active-bg);
  color: var(--nav-text-active);
  border-left: 4px solid var(--primary-color);
}

.link-icon {
  font-size: 1.4rem; /* Slightly larger icons */
  flex-shrink: 0;
  width: 24px;
  text-align: center;
  opacity: 1; /* Ensure full opacity */
}

.link-text {
  flex: 1;
  font-size: 1rem; /* Clearer text size */
  font-weight: 600; /* Bolder text */
  letter-spacing: 0.2px;
  color: inherit; /* Inherits the dark color from parent */
}

/* Badge Styling */
.notification-badge-sidebar {
  margin-left: auto;
  background-color: var(--ticket-color);
  color: white;
  border-radius: 12px;
  min-width: 22px;
  height: 22px;
  font-size: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 7px;
  font-weight: 700;
  animation: pulse 2s infinite;
  box-shadow: 0 2px 4px rgba(255, 107, 107, 0.3);
}

.nav-badge {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background-color: var(--danger-color);
  color: white;
  border-radius: 50%;
  width: 22px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  animation: pulse 1.5s infinite;
  box-shadow: 0 2px 4px rgba(217, 83, 79, 0.3);
}

@keyframes pulse {
  0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7); }
  70% { transform: scale(1.1); box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
  100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
}

@keyframes ticketPulse {
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
  background-color: var(--background-color);
}

.top-header {
  background-color: var(--surface-color);
  padding: 1.25rem 2rem;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-shrink: 0;
  gap: 1rem;
  flex-wrap: wrap;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.page-title {
  font-size: 1.6rem;
  font-weight: 700;
  color: var(--text-color);
  margin: 0;
  letter-spacing: -0.3px;
}

.quick-actions {
  display: flex;
  gap: 12px;
  align-items: center;
}

.quick-action-button {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--surface-color);
  border: 1px solid var(--border-color);
  border-radius: 10px;
  padding: 10px 16px;
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
  font-size: 0.9rem;
  color: var(--text-color);
  font-weight: 600;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.quick-action-button:hover {
  border-color: var(--primary-color);
  color: var(--primary-color);
  background-color: rgba(74, 144, 226, 0.05);
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.quick-action-icon {
  font-size: 1.2rem;
}

.notification-badge {
  position: absolute;
  top: -6px;
  right: -6px;
  background-color: var(--danger-color);
  color: white;
  border-radius: 50%;
  min-width: 20px;
  height: 20px;
  font-size: 0.7rem;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  border: 2px solid white;
  padding: 0 4px;
  animation: pulse 1.5s infinite;
}

/* Special styling for ticket notification badge */
.ticket-notification-badge {
  background-color: #fff5f5;
  border-color: var(--ticket-color);
  color: var(--ticket-color);
  animation: ticketPulse 2s infinite;
}

.ticket-notification-badge:hover {
  background-color: #ffe3e3;
  border-color: #ff5252;
  color: #ff5252;
}

/* Profile Dropdown - UPDATED FOR SOLID BACKGROUND */
.profile-dropdown-container {
  position: relative;
}

.profile-button {
  display: flex;
  align-items: center;
  gap: 12px;
  background: none;
  border: 1px solid var(--border-color);
  cursor: pointer;
  padding: 8px 16px;
  border-radius: 10px;
  transition: all 0.2s ease;
  background-color: white;
}

.profile-button:hover {
  background-color: #f0f4f8;
  border-color: var(--primary-color);
  box-shadow: 0 2px 6px rgba(74, 144, 226, 0.1);
}

.avatar-placeholder {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 1rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.user-name-header {
  font-weight: 600;
  color: var(--text-color);
  font-size: 0.95rem;
}

.dropdown-arrow {
  font-size: 0.7rem;
  transition: transform 0.2s;
  color: var(--text-light);
}

.dropdown-arrow.open {
  transform: rotate(180deg);
}

/* UPDATED DROPDOWN MENU - SOLID BACKGROUND */
.dropdown-menu {
  position: absolute;
  top: 120%;
  right: 0;
  background-color: var(--dropdown-bg);
  border-radius: 12px;
  box-shadow: var(--dropdown-shadow);
  min-width: 280px;
  padding: 0;
  z-index: 1000;
  border: 1px solid var(--border-color);
  overflow: hidden;
  backdrop-filter: none;
  background: white;
}

.dropdown-header {
  padding: 1.25rem 1.5rem;
  background: linear-gradient(135deg, #f8fafc, #f1f5f9);
  border-bottom: 1px solid var(--border-color);
  display: flex;
  align-items: center;
  gap: 12px;
}

.dropdown-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 1.1rem;
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
  flex-shrink: 0;
}

.dropdown-user-info {
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.dropdown-user-name {
  font-weight: 600;
  color: var(--text-color);
  font-size: 0.95rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.dropdown-user-email {
  font-size: 0.8rem;
  color: var(--text-light);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-top: 2px;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0.85rem 1.5rem;
  color: var(--text-color);
  text-decoration: none;
  width: 100%;
  text-align: left;
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 0.95rem;
  transition: all 0.15s ease;
  font-weight: 500;
  background-color: white;
}

.dropdown-item:hover {
  background-color: #f0f4f8;
  color: var(--primary-color);
  padding-left: 1.75rem;
}

/* Special styling for tickets dropdown item */
.dropdown-item[href*="tickets"]:hover {
  background-color: rgba(255, 107, 107, 0.1);
  color: var(--ticket-color);
}

.dropdown-icon {
  font-size: 1.2rem;
  width: 24px;
  text-align: center;
}

.dropdown-item-text {
  flex: 1;
}

.dropdown-badge {
  margin-left: auto;
  background-color: var(--ticket-color);
  color: white;
  border-radius: 12px;
  min-width: 22px;
  height: 22px;
  font-size: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 7px;
  font-weight: 600;
}

.dropdown-divider {
  border-bottom: 1px solid var(--border-color);
  margin: 0;
}

.logout-item {
  color: var(--logout-color);
  font-weight: 600;
}

.logout-item:hover {
  background-color: rgba(220, 53, 69, 0.1);
  color: var(--logout-color);
}

/* Main Content */
.main {
  flex: 1;
  padding: 2rem;
  overflow-y: auto;
  overflow-x: hidden;
  background-color: var(--background-color);
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
  box-shadow: var(--chat-shadow);
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
  background-color: var(--primary-color);
  color: white;
  border-radius: 16px 16px 0 0;
}

.modal-header h3 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 10px;
}

.modal-close-btn {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  transition: background 0.2s;
}

.modal-close-btn:hover {
  background: rgba(255, 255, 255, 0.3);
}

.modal-content {
  flex: 1;
  padding: 0;
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
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
  font-size: 0.95rem;
}

.btn-primary {
  background-color: var(--primary-color);
  color: rgb(7, 1, 1);
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
    background-color: var(--sidebar-bg);
  }
 
  .title,
  .link-text,
  .user-name-header,
  .quick-action-text {
    display: none;
  }
 
  .nav-link,
  .nav-button {
    justify-content: center;
    padding: 1rem 0.5rem;
    border-radius: 8px;
  }
 
  .logo-section {
    justify-content: center;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--nav-border);
  }
 
  .notification-badge-sidebar,
  .nav-badge {
    position: absolute;
    top: 5px;
    right: 5px;
    margin: 0;
    min-width: 16px;
    height: 16px;
    font-size: 0.6rem;
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
  
  .dropdown-menu {
    min-width: 250px;
    right: 0;
    position: fixed;
    top: 80px;
    width: calc(100% - 2rem);
    margin: 0 1rem;
  }
  
  .dropdown-header {
    padding: 1rem;
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
  
  .ticket-notification-badge .quick-action-text {
    display: none;
  }
  
  .ticket-notification-badge::after {
    content: '🎫';
    font-size: 1.2rem;
  }
  
  .dropdown-menu {
    min-width: 200px;
    width: calc(100% - 1rem);
    margin: 0 0.5rem;
  }
}
</style>