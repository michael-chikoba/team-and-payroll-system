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
       
        <!-- Productivity Monitor -->
        <router-link to="/admin/productivity" class="nav-item" @click="handleNavClick">
          <ChartBarIcon class="nav-icon" />
          <span class="nav-text">Productivity Monitor</span>
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
          <button class="profile-button" @click.stop="toggleProfileDropdown">
            <span class="avatar-placeholder">{{ getUserInitials }}</span>
            <span class="user-name-header">{{ user?.name || 'Admin User' }}</span>
            <ChevronDownIcon
              class="dropdown-arrow h-4 w-4"
              :class="{ 'rotated': showProfileDropdown }"
            />
          </button>
         
          <!-- Profile dropdown menu -->
          <transition name="dropdown">
            <div v-if="showProfileDropdown" class="dropdown-menu">
              <router-link
                to="/admin/profile"
                class="dropdown-item"
                @click="handleDropdownItemClick"
              >
                <UserCircleIcon class="dropdown-icon h-5 w-5" />
                <span class="dropdown-text">My Profile</span>
              </router-link>
             
              <div class="dropdown-divider"></div>
             
              <button
                type="button"
                @click="handleLogout"
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
    <!-- REMOVED: The problematic overlay div that was blocking clicks -->
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
  ListBulletIcon
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
    ListBulletIcon
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
   
    // Enhanced Logout Function
    const handleLogout = async () => {
      try {
        console.log("Logout triggered"); // Debug log
        closeProfileDropdown()
        
        await authStore.logout()
        
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        localStorage.removeItem('selectedBusiness')
        
        router.push('/auth/login')
      } catch (error) {
        console.error('Logout failed:', error)
        // Fallback
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        localStorage.removeItem('selectedBusiness')
        router.push('/auth/login')
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
      closeProfileDropdown()
    }
   
    const handleClickOutside = (event) => {
      // Logic for Profile Dropdown
      if (profileDropdownRef.value && !profileDropdownRef.value.contains(event.target)) {
        if (showProfileDropdown.value) {
           closeProfileDropdown()
        }
      }
     
      // Logic for Business Groups Dropdown
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
      if (isMobile.value) {
        showBusinessGroups.value = false
      }
    }
   
    watch(() => route.path, () => {
      showBusinessGroups.value = false
      if (isMobile.value) {
        closeSidebar()
      }
    })
   
    onMounted(() => {
      checkIfMobile()
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
      handleLogout,
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
@import '@/assets/css/shared-layout-styles.css';

/* 
   Ensure the profile dropdown container is relative 
   so the absolute menu positions correctly against it 
*/
.profile-dropdown-container {
  position: relative;
  z-index: 50; /* Ensure container is above general content */
}

/* 
   Specific Dropdown Styles to guarantee clickability 
   and correct stacking context 
*/
.dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  margin-top: 0.5rem;
  width: 12rem; /* w-48 */
  background-color: white;
  border-radius: 0.375rem; /* rounded-md */
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  border: 1px solid #e5e7eb;
  z-index: 9999; /* Very high Z-index to float above everything else */
  overflow: hidden;
}

.dropdown-item {
  display: flex;
  align-items: center;
  width: 100%;
  padding: 0.5rem 1rem;
  font-size: 0.875rem; /* text-sm */
  color: #374151; /* text-gray-700 */
  text-decoration: none;
  transition: background-color 0.2s;
  cursor: pointer;
  background: transparent;
  border: none;
  text-align: left;
}

.dropdown-item:hover {
  background-color: #f3f4f6; /* hover:bg-gray-100 */
  color: #111827; /* hover:text-gray-900 */
}

.logout-dropdown-item {
  color: #ef4444; /* text-red-500 */
}

.logout-dropdown-item:hover {
  background-color: #fef2f2; /* hover:bg-red-50 */
  color: #dc2626; /* hover:text-red-600 */
}

.dropdown-divider {
  height: 1px;
  background-color: #e5e7eb; /* border-gray-200 */
  margin: 0.25rem 0;
}
</style>