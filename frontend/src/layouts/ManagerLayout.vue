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
        <router-link to="/manager/tickets" class="nav-link" active-class="active">
          <span class="link-icon">🎫</span>
          <span class="link-text">Tickets</span>
          <span v-if="pendingTicketsCount > 0" class="nav-badge">{{ pendingTicketsCount }}</span>
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
       
        <router-link to="/manager/shifts" class="nav-link" active-class="active">
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
        <h2 class="page-title">{{ currentPageTitle }}</h2>
        <div class="right-actions">
          <!-- Ticket Notification Badge -->
          <button
            v-if="pendingTicketsCount > 0"
            @click="goToTickets"
            class="ticket-notification-badge quick-action-button"
          >
            <span class="quick-action-icon">🎫</span>
            <span class="quick-action-text">Tickets ({{ pendingTicketsCount }})</span>
          </button>
         
          <button
            @click="openChatModal"
            class="quick-action-button"
            :title="showChatModal ? 'Close Chat' : 'Open Chat'"
          >
            <span class="quick-action-icon">💬</span>
            <span class="quick-action-text">Chat</span>
            <span v-if="unreadCount > 0 && !showChatModal" class="notification-badge">{{ unreadCount }}</span>
          </button>
         
          <!-- Notification Bell Button -->
          <NotificationBell />
       
          <!-- Profile Dropdown -->
          <div class="profile-dropdown">
            <button class="profile-trigger" @click="toggleProfileDropdown">
              <span class="user-avatar">{{ getInitials }}</span>
              <span class="user-name">Hello, Manager!</span>
              <span class="dropdown-arrow">▼</span>
            </button>
           
            <!-- Dropdown Menu -->
            <transition name="dropdown">
              <div v-if="showProfileDropdown" class="profile-dropdown-menu">
                <router-link
                  to="/manager/profile"
                  class="dropdown-item"
                  @click="closeProfileDropdown"
                >
                  <span class="dropdown-icon">👤</span>
                  My Profile
                </router-link>
               
                <router-link
                  to="/manager/tickets"
                  class="dropdown-item"
                  @click="closeProfileDropdown"
                >
                  <span class="dropdown-icon">🎫</span>
                  Ticket Management
                  <span v-if="pendingTicketsCount > 0" class="dropdown-badge">{{ pendingTicketsCount }}</span>
                </router-link>
               
                <router-link
                  to="/manager/settings"
                  class="dropdown-item"
                  @click="closeProfileDropdown"
                >
                  <span class="dropdown-icon">⚙️</span>
                  Settings
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
            </transition>
          </div>
        </div>
      </header>
     
      <main class="main">
        <router-view />
       
        <!-- Chat Modal - FIXED VERSION -->
        <teleport to="body">
          <transition name="modal">
            <div v-if="showChatModal" class="modal-overlay" @click.self="closeChatModal">
              <div class="modal-container chat-modal-container">
                <div class="modal-header">
                  <h3>💬 Team Chat</h3>
                  <button @click="closeChatModal" class="modal-close-btn" title="Close Chat">×</button>
                </div>
                <div class="modal-content">
                  <!-- Use the Slack-style ChatInterface component -->
                  <SlackChat
                    ref="chatInterface"
                    @unread-count="updateUnreadCount"
                  />
                </div>
              </div>
            </div>
          </transition>
        </teleport>
      </main>
    </div>
  </div>
</template>
<script>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import AttendanceToggle from '../components/common/Toggle.vue'
import { useSidebar } from '@/composables/useSidebar'
import SlackChat from '@/components/ChatInterface.vue'
import NotificationBell from '@/components/NotificationBell.vue'
import { useAuthStore } from '../stores/auth'
export default {
  name: 'ManagerLayout',
  components: {
    AttendanceToggle,
    SlackChat,
    NotificationBell
  },
  setup() {
    const router = useRouter()
    const route = useRoute()
    const authStore = useAuthStore()
   
    const showProfileDropdown = ref(false)
    const showChatModal = ref(false)
    const unreadCount = ref(0)
    const pendingTicketsCount = ref(0)
    const chatInterface = ref(null)
    const { 
  isSidebarOpen, 
  isMobile, 
  toggleSidebar, 
  closeSidebar,
  checkIfMobile,
  sidebarClasses 
} = useSidebar()

const handleNavClick = () => {
  if (isMobile.value) {
    closeSidebar()
  }
}

const handleResize = () => {
  checkIfMobile()
}
   
    const currentPageTitle = computed(() => {
      return route.meta?.title || 'Manager Dashboard'
    })
   
    const toggleProfileDropdown = () => {
      showProfileDropdown.value = !showProfileDropdown.value
    }
   
    const closeProfileDropdown = () => {
      showProfileDropdown.value = false
    }
   
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
   
    const openChatModal = () => {
      showChatModal.value = true
      closeProfileDropdown()
      unreadCount.value = 0
    }
   
    const closeChatModal = () => {
      showChatModal.value = false
    }
   
    const updateUnreadCount = (count) => {
      if (!showChatModal.value) {
        unreadCount.value = count
      }
    }
   
    const getInitials = computed(() => {
      const name = authStore.user?.fullName || 'Manager'
      return name.split(' ').map(word => word[0]).join('').toUpperCase().slice(0, 2)
    })
   
    // Fetch pending tickets count for manager
    const fetchPendingTicketsCount = async () => {
      try {
        // This endpoint should return tickets assigned to the manager or their team
        const response = await fetch('/api/tickets/count?status=pending&manager_id=' + authStore.user?.id, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        }).catch(() => ({ ok: false }))
       
        if (response.ok) {
          const data = await response.json()
          pendingTicketsCount.value = data.count || 0
        }
      } catch (error) {
        console.error('Failed to fetch pending tickets count:', error)
      }
    }
   
    const goToTickets = () => {
      router.push('/manager/tickets')
      closeProfileDropdown()
    }
   
    const handleClickOutside = (event) => {
      const dropdown = document.querySelector('.profile-dropdown')
      if (dropdown && !dropdown.contains(event.target)) {
        showProfileDropdown.value = false
      }
    }
   
    const handleEscapeKey = (event) => {
      if (event.key === 'Escape') {
        if (showProfileDropdown.value) showProfileDropdown.value = false
        if (showChatModal.value) showChatModal.value = false
      }
    }
   
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
        clearInterval(ticketRefreshInterval)
        window.removeEventListener('ticket-created', fetchPendingTicketsCount)
        window.removeEventListener('ticket-updated', fetchPendingTicketsCount)
      })
    })
   
    return {
      showProfileDropdown,
      showChatModal,
      unreadCount,
      pendingTicketsCount,
      chatInterface,
      toggleProfileDropdown,
      closeProfileDropdown,
      handleLogout,
      openChatModal,
      closeChatModal,
      updateUnreadCount,
      getInitials,
      fetchPendingTicketsCount,
      goToTickets,
      currentPageTitle,
      authStore,
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
/* Manager-specific overrides (if any) */
.sidebar-footer {
  padding-top: 1rem;
  flex-shrink: 0;
}
.right-actions {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}
</style>