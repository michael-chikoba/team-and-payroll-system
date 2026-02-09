<template>
  <div class="employee-layout">
    <!-- Sidebar Overlay for Mobile -->
<div 
  v-if="isMobile && isSidebarOpen" 
  class="sidebar-overlay active"
  @click="closeSidebar"
></div>
    <aside class="sidebar" :class="sidebarClasses">
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
        <router-link to="/employee/dashboard" class="nav-link" active-class="active" @click="handleNavClick">
          <span class="link-icon">🏠</span>
          <span class="link-text">Dashboard</span>
        </router-link>
        
        <router-link to="/employee/attendance" class="nav-link" active-class="active" @click="handleNavClick">
          <span class="link-icon">⏰</span>
          <span class="link-text">Attendance</span>
        </router-link>
        
        <router-link to="/employee/leaves" class="nav-link" active-class="active" @click="handleNavClick">
          <span class="link-icon">🏖️</span>
          <span class="link-text">Leaves</span>
        </router-link>
        
        <router-link to="/employee/apply-leave" class="nav-link" active-class="active" @click="handleNavClick">
          <span class="link-icon">📝</span>
          <span class="link-text">Apply Leave</span>
        </router-link>
        
        <router-link to="/employee/payslips" class="nav-link" active-class="active" @click="handleNavClick">
          <span class="link-icon">💵</span>
          <span class="link-text">Payslips</span>
        </router-link>
        
        <!-- Tasks link -->
        <router-link :to="{ name: 'TaskBoard' }" class="nav-link" active-class="active" @click="handleNavClick">
          <span class="link-icon">🗂️</span>
          <span class="link-text">Tasks</span>
        </router-link>
        
        <!-- Schedules link -->
        <router-link :to="{ name: 'EmployeeSchedules' }" class="nav-link" active-class="active" @click="handleNavClick">
          <span class="link-icon">📅</span>
          <span class="link-text">Schedules</span>
        </router-link>
        
        <!-- My Shifts link -->
        <router-link :to="{ name: 'myshifts' }" class="nav-link" active-class="active" @click="handleNavClick">
          <span class="link-icon">⏱️</span>
          <span class="link-text">My Shifts</span>
        </router-link>
        
        <!-- Tickets Link -->
        <router-link :to="{ name: 'mytickets' }" class="nav-link" active-class="active" @click="handleNavClick">
          <span class="link-icon">🎫</span>
          <span class="link-text">Tickets</span>
          <span v-if="pendingTicketsCount > 0" class="notification-badge-sidebar">
            {{ pendingTicketsCount }}
          </span>
        </router-link>
        
        <!-- Reports link -->
        <router-link :to="{ name: 'charts' }" class="nav-link" active-class="active" @click="handleNavClick">
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
         <!-- Hamburger Menu for Mobile -->
  <button 
    class="hamburger-menu"
    :class="{ 'active': isSidebarOpen }"
    @click="toggleSidebar"
    aria-label="Toggle Menu"
  >
    <div class="hamburger-icon">
      <span></span>
      <span></span>
      <span></span>
    </div>
  </button>
        <h2 class="page-title">{{ currentPageTitle }}</h2>
        
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
import { useSidebar } from '@/composables/useSidebar'
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
    // Add sidebar composable
  const { 
    isSidebarOpen, 
    isMobile, 
    toggleSidebar, 
    closeSidebar,
    checkIfMobile,
    sidebarClasses 
  } = useSidebar()
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
      // Add navigation click handler
  const handleNavClick = () => {
    if (isMobile.value) {
      closeSidebar()
    }
  }
  
  // Add resize handler
  const handleResize = () => {
    checkIfMobile()
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
            if (isMobile.value && isSidebarOpen.value) closeSidebar() // ADD THIS LINE
      }
    }
    
    // Lifecycle hooks
    onMounted(() => {
       checkIfMobile()
    window.addEventListener('resize', handleResize)
      document.addEventListener('click', handleClickOutside)
      document.addEventListener('keydown', handleEscapeKey)
      fetchPendingTicketsCount()
      
      const ticketRefreshInterval = setInterval(fetchPendingTicketsCount, 300000)
      
      window.addEventListener('ticket-created', fetchPendingTicketsCount)
      window.addEventListener('ticket-updated', fetchPendingTicketsCount)
      
      onUnmounted(() => {
              window.removeEventListener('resize', handleResize)
        document.removeEventListener('click', handleClickOutside)
        document.removeEventListener('keydown', handleEscapeKey)
        document.body.style.overflow = 'auto'
        clearInterval(ticketRefreshInterval)
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
      handleLogout,
      isSidebarOpen,
    isMobile,
    toggleSidebar,
    closeSidebar,
    sidebarClasses,
    handleNavClick
    }
  }
}
</script>

<style scoped>
/* Import centralized layout styles */
@import '@/assets/css/shared-layout-styles.css';
/* Employee-specific overrides (if any) */
.logout-item {
  color: var(--logout-color);
}
.logout-item:hover {
  background-color: rgba(220, 53, 69, 0.1);
  color: var(--logout-color);
}
</style>