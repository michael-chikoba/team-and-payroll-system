<!-- layouts/AdminLayout.vue -->
<template>
  <div class="admin-layout">
    <!-- Sidebar Overlay for Mobile -->
    <div
      v-if="isMobile && isSidebarOpen"
      class="sidebar-overlay active"
      @click="closeSidebar"
    ></div>
    <aside class="sidebar" :class="sidebarClasses">
      <div class="logo-section">
        <BriefcaseIcon class="logo-icon h-8 w-8" />
        <span class="title">Payroll System</span>
      </div>
     
      <!-- Attendance Toggle Button Section -->
      <div class="attendance-toggle-section">
        <ClockToggle />
      </div>
     
      <nav class="sidebar-nav">
        <router-link to="/admin/dashboard" class="nav-item" @click="handleNavClick">
          <HomeIcon class="nav-icon" />
          <span class="nav-text">Dashboard</span>
        </router-link>
       
        <router-link to="/admin/employees" class="nav-item" @click="handleNavClick">
          <UsersIcon class="nav-icon" />
          <span class="nav-text">Employees</span>
        </router-link>
       
        <!-- Add Employees - RESTRICTED -->
        <router-link
          v-if="canAccessRestrictedPages"
          to="/admin/employ"
          class="nav-item"
          @click="handleNavClick"
        >
          <UserPlusIcon class="nav-icon" />
          <span class="nav-text">Add Employees</span>
          <span class="super-admin-badge">Super Admin</span>
        </router-link>
       
        <router-link to="/admin/payroll" class="nav-item" @click="handleNavClick">
          <BanknotesIcon class="nav-icon" />
          <span class="nav-text">Payroll</span>
        </router-link>
       
        <router-link to="/admin/payslips" class="nav-item" @click="handleNavClick">
          <DocumentTextIcon class="nav-icon" />
          <span class="nav-text">Payslips</span>
        </router-link>
       
        <router-link to="/admin/attendance" class="nav-item" @click="handleNavClick">
          <ClockIcon class="nav-icon" />
          <span class="nav-text">Attendance</span>
        </router-link>
       
        <router-link to="/admin/leaves" class="nav-item" @click="handleNavClick">
          <CalendarDaysIcon class="nav-icon" />
          <span class="nav-text">Leave Management</span>
        </router-link>
       
        <!-- Ticket Management -->
        <router-link to="/admin/tickets" class="nav-item" @click="handleNavClick">
          <TicketIcon class="nav-icon" />
          <span class="nav-text">Ticket Management</span>
          <span v-if="pendingTicketsCount > 0" class="sidebar-badge">{{ pendingTicketsCount }}</span>
        </router-link>
       
        <!-- Tasks Management -->
        <router-link to="/admin/Tasks" class="nav-item" @click="handleNavClick">
          <ClipboardDocumentListIcon class="nav-icon" />
          <span class="nav-text">Tasks Management</span>
        </router-link>
       
        <!-- Productivity Monitor - RESTRICTED -->
        <router-link
          v-if="canAccessRestrictedPages"
          to="/admin/productivity"
          class="nav-item"
          @click="handleNavClick"
        >
          <ChartBarIcon class="nav-icon" />
          <span class="nav-text">Productivity Monitor</span>
          <span class="super-admin-badge">Super Admin</span>
        </router-link>
       
        <router-link to="/admin/tax" class="nav-item" @click="handleNavClick">
          <CalculatorIcon class="nav-icon" />
          <span class="nav-text">Tax Configuration</span>
        </router-link>
       
        <router-link to="/admin/reports" class="nav-item" @click="handleNavClick">
          <ChartBarIcon class="nav-icon" />
          <span class="nav-text">Reports</span>
        </router-link>
       
        <router-link to="/admin/audit-logs" class="nav-item" @click="handleNavClick">
          <ClipboardDocumentCheckIcon class="nav-icon" />
          <span class="nav-text">Audit Logs</span>
        </router-link>
       
        <!-- Country Management -->
        <router-link to="/admin/countries" class="nav-item" @click="handleNavClick">
          <GlobeAltIcon class="nav-icon" />
          <span class="nav-text">Countries</span>
        </router-link>
       
        <router-link to="/admin/settings" class="nav-item" @click="handleNavClick">
          <Cog6ToothIcon class="nav-icon" />
          <span class="nav-text">Settings</span>
        </router-link>
       
        <!-- Business Management -->
        <router-link to="/admin/businesses" class="nav-item" @click="handleNavClick">
          <BuildingOfficeIcon class="nav-icon" />
          <span class="nav-text">Business Management</span>
        </router-link>
       
        <!-- Business Groups Dropdown -->
        <div
          class="nav-group"
          :class="{ 'active': isBusinessGroupsActive }"
        >
          <button
            class="nav-item nav-group-button"
            @click="toggleBusinessGroups"
          >
            <BuildingLibraryIcon class="nav-icon" />
            <span class="nav-text">Business Groups</span>
            <ChevronDownIcon
              class="dropdown-icon ml-auto h-4 w-4 transition-transform duration-200"
              :class="{ 'rotate-180': showBusinessGroups }"
            />
          </button>
         
          <!-- Business Groups Dropdown Menu -->
          <transition name="dropdown">
            <div v-if="showBusinessGroups" class="nav-group-menu">
              <router-link
                to="/admin/business-groups"
                class="nav-group-item"
                @click="handleNavClick"
                :class="{ 'active': $route.name === 'BusinessGroupList' }"
              >
                <ListBulletIcon class="nav-group-icon" />
                <span class="nav-group-text">All Groups</span>
              </router-link>
             
              <div v-if="isBusinessGroupDetailsRoute" class="nav-group-item current-group-item">
                <BuildingLibraryIcon class="nav-group-icon" />
                <span class="nav-group-text">Current Group</span>
                <span class="current-badge">Active</span>
              </div>
            </div>
          </transition>
        </div>
       
        <!-- Demo Requests - RESTRICTED -->
        <router-link
          v-if="canAccessRestrictedPages"
          to="/admin/demo-requests"
          class="nav-item"
          @click="handleNavClick"
        >
          <PresentationChartLineIcon class="nav-icon" />
          <span class="nav-text">Demo Requests</span>
          <span class="super-admin-badge">Super Admin</span>
        </router-link>
       
        <!-- Contact Requests - RESTRICTED -->
        <router-link
          v-if="canAccessRestrictedPages"
          to="/admin/contact-requests"
          class="nav-item"
          @click="handleNavClick"
        >
          <ChatBubbleLeftRightIcon class="nav-icon" />
          <span class="nav-text">Contact Requests</span>
          <span class="super-admin-badge">Super Admin</span>
        </router-link>
       
        <!-- AI Chat Assistant -->
        <button @click="openChat" class="nav-item nav-button" :class="{ 'active': showChat }">
          <ChatBubbleLeftRightIcon class="nav-icon" />
          <span class="nav-text">Chat</span>
          <span v-if="unreadCount > 0 && !showChat" class="nav-badge">{{ unreadCount }}</span>
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
          <!-- Ticket Notification Badge -->
          <div v-if="pendingTicketsCount > 0" class="ticket-notification-badge" @click="goToTickets">
            <TicketIcon class="ticket-icon h-5 w-5" />
            <span class="ticket-count">{{ pendingTicketsCount }}</span>
            <span class="ticket-text">Pending Tickets</span>
          </div>
         
          <button
            @click="openChat"
            class="quick-action-button"
            :title="showChat ? 'Close Chat' : 'Open Chat'"
          >
            <span class="quick-action-icon">💬</span>
            <span class="quick-action-text">Chat</span>
            <span v-if="unreadCount > 0 && !showChat" class="notification-badge">{{ unreadCount }}</span>
          </button>
         
          <!-- Notification Bell -->
          <NotificationBell />
        </div>
       
        <!-- Profile Dropdown -->
        <div class="profile-dropdown-container" ref="profileDropdownRef">
          <button class="profile-button" @click="toggleProfileDropdown">
            <span class="avatar-placeholder">{{ getUserInitials }}</span>
            <span class="user-name-header">{{ user?.name || 'Admin User' }}</span>
            <ChevronDownIcon
              class="dropdown-arrow h-4 w-4"
              :class="{ 'rotated': showProfileDropdown }"
            />
          </button>
         
          <!-- Profile dropdown menu -->
          <transition name="dropdown">
            <div v-if="showProfileDropdown" class="dropdown-menu" @click.stop>
              <router-link
                to="/admin/profile"
                class="dropdown-item"
                @click="handleDropdownItemClick"
              >
                <UserCircleIcon class="dropdown-icon h-5 w-5" />
                <span class="dropdown-text">My Profile</span>
              </router-link>
             
              <router-link
                to="/admin/tickets"
                class="dropdown-item"
                @click="handleDropdownItemClick"
              >
                <TicketIcon class="dropdown-icon h-5 w-5" />
                <span class="dropdown-text">Ticket Management</span>
                <span v-if="pendingTicketsCount > 0" class="dropdown-badge">{{ pendingTicketsCount }}</span>
              </router-link>
              <!-- Business Groups in Profile Dropdown -->
              <div class="dropdown-section">
                <div class="dropdown-section-header">Business Groups</div>
                <router-link
                  to="/admin/business-groups"
                  class="dropdown-item"
                  @click="handleDropdownItemClick"
                >
                  <BuildingLibraryIcon class="dropdown-icon h-5 w-5" />
                  <span class="dropdown-text">All Groups</span>
                </router-link>
                <router-link
                  to="/admin/business-groups/create"
                  class="dropdown-item"
                  @click="handleDropdownItemClick"
                >
                  <PlusCircleIcon class="dropdown-icon h-5 w-5" />
                  <span class="dropdown-text">Create Group</span>
                </router-link>
              </div>
             
              <div class="dropdown-divider"></div>
             
              <button
                @click="logout"
                class="dropdown-item logout-dropdown-item"
              >
                <ArrowLeftOnRectangleIcon class="dropdown-icon h-5 w-5" />
                <span class="dropdown-text">Logout</span>
              </button>
            </div>
          </transition>
        </div>
      </header>
     
      <main class="main">
        <router-view />
      </main>
     
      <!-- Chat Popup Modal -->
      <transition name="chat-popup">
        <div v-if="showChat" class="chat-popup-overlay" @click.self="closeChat">
          <div class="chat-popup-container">
            <div class="chat-popup-header">
              <h3>
                <ChatBubbleLeftRightIcon class="inline-block w-6 h-6 mr-2" />
                Chat Assistant
              </h3>
              <button @click="closeChat" class="chat-close-btn">
                <XMarkIcon class="w-6 h-6" />
              </button>
            </div>
            <div class="chat-popup-content">
              <ChatInterface
                v-if="showChat"
                :key="chatKey"
                @close="closeChat"
                @unread-count="updateUnreadCount"
              />
            </div>
          </div>
        </div>
      </transition>
    </div>
   
    <!-- Overlay to close dropdown when clicking outside -->
    <div
      v-if="showProfileDropdown"
      class="dropdown-overlay"
      @click="closeProfileDropdown"
    ></div>
  </div>
</template>
<script>
import { useAuthStore } from '@/stores/auth'
import { useRouter, useRoute } from 'vue-router'
import { ref, onMounted, onUnmounted, computed, watch } from 'vue'
import { useSidebar } from '@/composables/useSidebar'
import ClockToggle from '@/components/common/Toggle.vue'
import ChatInterface from '@/components/ChatInterface.vue'
import NotificationBell from '@/components/NotificationBell.vue'
import {
  BriefcaseIcon,
  TicketIcon,
  ChevronDownIcon,
  UserCircleIcon,
  ArrowLeftOnRectangleIcon,
  HomeIcon,
  UsersIcon,
  UserPlusIcon,
  BanknotesIcon,
  DocumentTextIcon,
  ClockIcon,
  CalendarDaysIcon,
  ClipboardDocumentListIcon,
  CalculatorIcon,
  ChartBarIcon,
  ClipboardDocumentCheckIcon,
  GlobeAltIcon,
  Cog6ToothIcon,
  BuildingOfficeIcon,
  BuildingLibraryIcon,
  ChatBubbleLeftRightIcon,
  XMarkIcon,
  PresentationChartLineIcon,
  ListBulletIcon,
  PlusCircleIcon
} from '@heroicons/vue/24/outline'
export default {
  name: 'AdminLayout',
  components: {
    ClockToggle,
    ChatInterface,
    NotificationBell,
    // Icons
    BriefcaseIcon,
    TicketIcon,
    ChevronDownIcon,
    UserCircleIcon,
    ArrowLeftOnRectangleIcon,
    HomeIcon,
    UsersIcon,
    UserPlusIcon,
    BanknotesIcon,
    DocumentTextIcon,
    ClockIcon,
    CalendarDaysIcon,
    ClipboardDocumentListIcon,
    CalculatorIcon,
    ChartBarIcon,
    ClipboardDocumentCheckIcon,
    GlobeAltIcon,
    Cog6ToothIcon,
    BuildingOfficeIcon,
    BuildingLibraryIcon,
    ChatBubbleLeftRightIcon,
    XMarkIcon,
    PresentationChartLineIcon,
    ListBulletIcon,
    PlusCircleIcon
  },
  setup() {
    const authStore = useAuthStore()
    const router = useRouter()
    const route = useRoute()
   
    // Sidebar composable
    const {
      isSidebarOpen,
      isMobile,
      toggleSidebar,
      closeSidebar,
      checkIfMobile,
      sidebarClasses
    } = useSidebar()
   
    const showProfileDropdown = ref(false)
    const showChat = ref(false)
    const chatKey = ref(0)
    const pendingTicketsCount = ref(0)
    const unreadCount = ref(0)
    const profileDropdownRef = ref(null)
    const showBusinessGroups = ref(false)
   
    const currentPageTitle = computed(() => {
      return route.meta?.title || 'Admin Dashboard'
    })
   
    const getUserInitials = computed(() => {
      if (!authStore.user?.name) return 'AU'
      return authStore.user.name
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .toUpperCase()
        .substring(0, 2)
    })
   
    const canAccessRestrictedPages = computed(() => {
      const allowedEmails = ['michaelchikoba0@gmail.com', 'chikobamichael97@gmail.com'];
      return allowedEmails.includes(authStore.user?.email);
    })
   
    const isBusinessGroupsActive = computed(() => {
      return route.path.startsWith('/admin/business-groups');
    })
   
    const isBusinessGroupDetailsRoute = computed(() => {
      return route.name === 'BusinessGroupDetails';
    })
   
    const handleNavClick = () => {
      // Close sidebar on mobile when navigating
      if (isMobile.value) {
        closeSidebar()
      }
    }
   
    const toggleBusinessGroups = () => {
      showBusinessGroups.value = !showBusinessGroups.value
    }
   
    const logout = async () => {
      try {
        await authStore.logout()
        window.location.href = '/auth/login'
      } catch (error) {
        console.error('Logout failed:', error)
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        window.location.href = '/auth/login'
      }
    }
   
    const fetchPendingTicketsCount = async () => {
      try {
        const response = await fetch('/api/tickets/count?status=pending', {
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
      router.push('/admin/tickets')
    }
   
    const openChat = () => {
      chatKey.value++
      showChat.value = true
      unreadCount.value = 0
      document.body.style.overflow = 'hidden'
    }
   
    const closeChat = () => {
      showChat.value = false
      document.body.style.overflow = 'auto'
    }
   
    const updateUnreadCount = (count) => {
      if (!showChat.value) {
        unreadCount.value = count
      }
    }
   
    const toggleProfileDropdown = () => {
      showProfileDropdown.value = !showProfileDropdown.value
    }
   
    const closeProfileDropdown = () => {
      showProfileDropdown.value = false
    }
   
    const handleDropdownItemClick = () => {
      // Close dropdown when a link is clicked
      closeProfileDropdown()
    }
   
    const handleClickOutside = (event) => {
      if (profileDropdownRef.value && !profileDropdownRef.value.contains(event.target)) {
        closeProfileDropdown()
      }
     
      // Close business groups dropdown if clicking outside
      const navGroup = document.querySelector('.nav-group')
      if (navGroup && !navGroup.contains(event.target) && showBusinessGroups.value) {
        showBusinessGroups.value = false
      }
    }
   
    const handleEscapeKey = (event) => {
      if (event.key === 'Escape') {
        if (showProfileDropdown.value) closeProfileDropdown()
        if (showBusinessGroups.value) showBusinessGroups.value = false
        if (showChat.value) closeChat()
        if (isMobile.value && isSidebarOpen.value) closeSidebar()
      }
    }
   
    const handleResize = () => {
      checkIfMobile()
      // Close dropdowns on mobile
      if (isMobile.value) {
        showBusinessGroups.value = false
      }
    }
   
    // Watch route changes to close dropdowns
    watch(() => route.path, () => {
      showBusinessGroups.value = false
      if (isMobile.value) {
        closeSidebar()
      }
    })
   
    onMounted(() => {
      // Check if mobile on mount
      checkIfMobile()
     
      // Add event listeners
      document.addEventListener('click', handleClickOutside)
      document.addEventListener('keydown', handleEscapeKey)
      window.addEventListener('resize', handleResize)
     
      fetchPendingTicketsCount()
     
      const ticketRefreshInterval = setInterval(fetchPendingTicketsCount, 300000)
     
      window.addEventListener('ticket-created', fetchPendingTicketsCount)
      window.addEventListener('ticket-updated', fetchPendingTicketsCount)
     
      onUnmounted(() => {
        document.removeEventListener('click', handleClickOutside)
        document.removeEventListener('keydown', handleEscapeKey)
        window.removeEventListener('resize', handleResize)
        document.body.style.overflow = 'auto'
        clearInterval(ticketRefreshInterval)
        window.removeEventListener('ticket-created', fetchPendingTicketsCount)
        window.removeEventListener('ticket-updated', fetchPendingTicketsCount)
      })
    })
   
    return {
      logout,
      user: authStore.user,
      showProfileDropdown,
      showChat,
      pendingTicketsCount,
      unreadCount,
      chatKey,
      profileDropdownRef,
      showBusinessGroups,
      goToTickets,
      openChat,
      closeChat,
      updateUnreadCount,
      toggleProfileDropdown,
      closeProfileDropdown,
      handleDropdownItemClick,
      toggleBusinessGroups,
      getUserInitials,
      canAccessRestrictedPages,
      isBusinessGroupsActive,
      isBusinessGroupDetailsRoute,
      currentPageTitle,
      handleNavClick,
      // Sidebar
      isSidebarOpen,
      isMobile,
      toggleSidebar,
      closeSidebar,
      sidebarClasses
    }
  }
}
</script>
<style scoped>
/* Import centralized responsive layout styles */
@import '@/assets/css/shared-layout-styles.css';
/* Additional styles to ensure dropdown is clickable */
.dropdown-menu {
  position: relative;
  z-index: 1001;
  pointer-events: all;
}
.dropdown-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 1000;
  background: transparent;
}
.profile-dropdown-container {
  position: relative;
  z-index: 1002;
}
.dropdown-item {
  cursor: pointer;
  pointer-events: all;
  user-select: none;
}
.dropdown-item:hover {
  background-color: rgba(0, 0, 0, 0.05);
}
/* Business Groups Dropdown Styles */
.nav-group {
  position: relative;
}
.nav-group-button {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  cursor: pointer;
  border: none;
  background: transparent;
  padding: 12px 16px;
  color: #4b5563;
  transition: background-color 0.2s;
}
.nav-group-button:hover {
  background-color: rgba(59, 130, 246, 0.1);
  color: #1f2937;
}
.nav-group.active .nav-group-button {
  background-color: #3b82f6;
  color: white;
}
.nav-group-menu {
  background-color: #f9fafb;
  border-left: 3px solid #3b82f6;
  margin-left: 16px;
  overflow: hidden;
  border-radius: 0 4px 4px 0;
}
.nav-group-item {
  display: flex;
  align-items: center;
  padding: 10px 16px;
  color: #4b5563;
  text-decoration: none;
  transition: background-color 0.2s;
  border-left: 3px solid transparent;
}
.nav-group-item:hover {
  background-color: #e5e7eb;
  color: #1f2937;
}
.nav-group-item.active {
  background-color: #dbeafe;
  color: #1e40af;
  border-left-color: #3b82f6;
}
.nav-group-icon {
  width: 18px;
  height: 18px;
  margin-right: 12px;
  flex-shrink: 0;
}
.nav-group-text {
  font-size: 14px;
  flex-grow: 1;
}
.current-group-item {
  background-color: #f0f9ff;
  border-left-color: #0ea5e9;
}
.current-badge {
  background-color: #0ea5e9;
  color: white;
  font-size: 12px;
  padding: 2px 8px;
  border-radius: 12px;
  margin-left: 8px;
}
/* Dropdown transition */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.3s ease;
  max-height: 200px;
  opacity: 1;
}
.dropdown-enter-from,
.dropdown-leave-to {
  max-height: 0;
  opacity: 0;
  margin-top: 0;
  margin-bottom: 0;
  padding-top: 0;
  padding-bottom: 0;
}
/* Profile dropdown section */
.dropdown-section {
  padding: 8px 0;
  border-top: 1px solid #e5e7eb;
  border-bottom: 1px solid #e5e7eb;
  margin: 8px 0;
}
.dropdown-section-header {
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  color: #6b7280;
  padding: 8px 16px;
  letter-spacing: 0.05em;
}
/* Super Admin Badge */
.super-admin-badge {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  font-size: 10px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 12px;
  margin-left: 8px;
  white-space: nowrap;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
/* Rotate transition for dropdown icon */
.rotate-180 {
  transform: rotate(180deg);
}
/* Mobile responsive adjustments */
@media (max-width: 768px) {
  .nav-group-menu {
    margin-left: 8px;
  }
 
  .nav-group-item {
    padding: 8px 12px;
  }
 
  .super-admin-badge {
    font-size: 9px;
    padding: 1px 6px;
  }
}
</style>