<!-- layouts/CentralLayout.vue -->
<template>
  <div class="central-layout">
    <!-- Header -->
    <header class="header">
      <div class="logo">
        <BriefcaseIcon class="logo-icon h-8 w-8" />
        <span class="logo-text">{{ getAppName }}</span>
      </div>
     
      <div class="header-right">
        <!-- Role Badge -->
        <div class="role-badge" :class="userRoleClass">
          {{ userRoleDisplay }}
        </div>
        
        <!-- Ticket Notification Badge (for Admin & Manager) -->
        <div 
          v-if="showTicketBadge && pendingTicketsCount > 0" 
          class="ticket-notification-badge" 
          @click="goToTickets"
        >
          <TicketIcon class="ticket-icon h-5 w-5" />
          <span class="ticket-count">{{ pendingTicketsCount }}</span>
          <span class="ticket-text">Pending Tickets</span>
        </div>
        
        <!-- User Profile -->
        <div class="user-profile">
          <div class="profile-dropdown">
            <button class="profile-trigger" @click="toggleProfileDropdown">
              <span class="user-avatar">{{ getUserInitials }}</span>
              <div class="user-info">
                <span class="user-name">{{ userName }}</span>
                <span class="user-email">{{ userEmail }}</span>
              </div>
              <ChevronDownIcon 
                class="dropdown-arrow h-4 w-4" 
                :class="{ 'rotated': showProfileDropdown }"
              />
            </button>
            
            <!-- Profile dropdown menu -->
            <transition name="dropdown">
              <div v-if="showProfileDropdown" class="profile-dropdown-menu">
                <router-link 
                  :to="profileRoute" 
                  class="dropdown-item"
                  @click="closeProfileDropdown"
                >
                  <UserCircleIcon class="dropdown-icon h-5 w-5" />
                  <span class="dropdown-text">My Profile</span>
                </router-link>
                
                <router-link 
                  v-if="showTicketsInDropdown"
                  :to="ticketsRoute" 
                  class="dropdown-item"
                  @click="closeProfileDropdown"
                >
                  <TicketIcon class="dropdown-icon h-5 w-5" />
                  <span class="dropdown-text">Ticket Management</span>
                  <span v-if="pendingTicketsCount > 0" class="dropdown-badge">{{ pendingTicketsCount }}</span>
                </router-link>
                
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
        </div>
      </div>
    </header>
    
    <!-- Overlay to close dropdown when clicking outside -->
    <div 
      v-if="showProfileDropdown" 
      class="dropdown-overlay" 
      @click="closeProfileDropdown"
    />
    
    <div class="layout-body">
      <!-- Sidebar -->
      <aside class="sidebar">
        <!-- Attendance Toggle Button Section (for Employees & Managers) -->
        <div v-if="showAttendanceToggle" class="attendance-toggle-section">
          <ClockToggle />
        </div>
        
        <!-- Navigation Menu -->
        <nav class="sidebar-nav">
          <!-- Dashboard -->
          <router-link :to="dashboardRoute" class="nav-item">
            <HomeIcon class="nav-icon" />
            <span class="nav-text">Dashboard</span>
          </router-link>
          
          <!-- Admin Only Routes -->
          <template v-if="userRole === 'admin'">
            <router-link to="/admin/employees" class="nav-item">
              <UsersIcon class="nav-icon" />
              <span class="nav-text">Employees</span>
            </router-link>
            
            <router-link 
              v-if="canAccessRestrictedPages"
              to="/admin/employ" 
              class="nav-item"
            >
              <UserPlusIcon class="nav-icon" />
              <span class="nav-text">Add Employees</span>
              <span class="super-admin-badge">Super Admin</span>
            </router-link>
            
            <router-link to="/admin/payroll" class="nav-item">
              <BanknotesIcon class="nav-icon" />
              <span class="nav-text">Payroll</span>
            </router-link>
            
            <router-link to="/admin/payslips" class="nav-item">
              <DocumentTextIcon class="nav-icon" />
              <span class="nav-text">Payslips</span>
            </router-link>
            
            <router-link to="/admin/attendance" class="nav-item">
              <ClockIcon class="nav-icon" />
              <span class="nav-text">Attendance</span>
            </router-link>
            
            <router-link to="/admin/leaves" class="nav-item">
              <CalendarDaysIcon class="nav-icon" />
              <span class="nav-text">Leave Management</span>
            </router-link>

            <router-link to="/admin/tickets" class="nav-item">
              <TicketIcon class="nav-icon" />
              <span class="nav-text">Ticket Management</span>
              <span v-if="pendingTicketsCount > 0" class="sidebar-badge">{{ pendingTicketsCount }}</span>
            </router-link>
            
            <router-link to="/admin/Tasks" class="nav-item">
              <ClipboardDocumentListIcon class="nav-icon" />
              <span class="nav-text">Tasks Management</span>
            </router-link>
            
            <router-link to="/admin/tax" class="nav-item">
              <CalculatorIcon class="nav-icon" />
              <span class="nav-text">Tax Configuration</span>
            </router-link>
            
            <router-link to="/admin/reports" class="nav-item">
              <ChartBarIcon class="nav-icon" />
              <span class="nav-text">Reports</span>
            </router-link>
            
            <router-link to="/admin/audit-logs" class="nav-item">
              <ClipboardDocumentCheckIcon class="nav-icon" />
              <span class="nav-text">Audit Logs</span>
            </router-link>
            
            <router-link to="/admin/countries" class="nav-item">
              <GlobeAltIcon class="nav-icon" />
              <span class="nav-text">Countries</span>
            </router-link>
            
            <router-link to="/admin/settings" class="nav-item">
              <Cog6ToothIcon class="nav-icon" />
              <span class="nav-text">Settings</span>
            </router-link>
            
            <router-link to="/admin/businesses" class="nav-item">
              <BuildingOfficeIcon class="nav-icon" />
              <span class="nav-text">Business Management</span>
            </router-link>

            <router-link 
              v-if="canAccessRestrictedPages"
              to="/admin/demo-requests" 
              class="nav-item"
            >
              <PresentationChartLineIcon class="nav-icon" />
              <span class="nav-text">Demo Requests</span>
              <span class="super-admin-badge">Super Admin</span>
            </router-link>

            <router-link 
              v-if="canAccessRestrictedPages"
              to="/admin/contact-requests" 
              class="nav-item"
            >
              <ChatBubbleLeftRightIcon class="nav-icon" />
              <span class="nav-text">Contact Requests</span>
              <span class="super-admin-badge">Super Admin</span>
            </router-link>
          </template>
          
          <!-- Manager Routes -->
          <template v-if="userRole === 'manager'">
            <router-link to="/manager/schedule" class="nav-item">
              <CalendarDaysIcon class="nav-icon" />
              <span class="nav-text">Team Schedule</span>
            </router-link>
            
            <router-link to="/manager/employees" class="nav-item">
              <UsersIcon class="nav-icon" />
              <span class="nav-text">Employees</span>
            </router-link>
            
            <router-link to="/manager/attendance" class="nav-item">
              <ClockIcon class="nav-icon" />
              <span class="nav-text">Attendance</span>
            </router-link>
            
            <router-link to="/manager/leave-approvals" class="nav-item">
              <CheckCircleIcon class="nav-icon" />
              <span class="nav-text">Approvals</span>
            </router-link>
            
            <router-link to="/manager/tickets" class="nav-item">
              <TicketIcon class="nav-icon" />
              <span class="nav-text">Tickets</span>
              <span v-if="pendingTicketsCount > 0" class="sidebar-badge">{{ pendingTicketsCount }}</span>
            </router-link>
            
            <router-link to="/manager/reports" class="nav-item">
              <ChartBarIcon class="nav-icon" />
              <span class="nav-text">Reports</span>
            </router-link>
            
            <router-link to="/manager/productivity" class="nav-item">
              <BoltIcon class="nav-icon" />
              <span class="nav-text">Productivity</span>
            </router-link>
            
            <router-link to="/manager/payslips" class="nav-item">
              <BanknotesIcon class="nav-icon" />
              <span class="nav-text">Payslips</span>
            </router-link>
            
            <router-link to="/manager/tasks" class="nav-item">
              <ClipboardDocumentListIcon class="nav-icon" />
              <span class="nav-text">Assign Tasks</span>
            </router-link>
            
            <router-link to="/manager/shifts" class="nav-item">
              <ClockIcon class="nav-icon" />
              <span class="nav-text">Assign Shifts</span>
            </router-link>
          </template>
          
          <!-- Employee Routes -->
          <template v-if="userRole === 'employee'">
            <router-link to="/employee/attendance" class="nav-item">
              <ClockIcon class="nav-icon" />
              <span class="nav-text">Attendance</span>
            </router-link>
            
            <router-link to="/employee/leaves" class="nav-item">
              <CalendarDaysIcon class="nav-icon" />
              <span class="nav-text">Leaves</span>
            </router-link>
            
            <router-link to="/employee/apply-leave" class="nav-item">
              <DocumentArrowUpIcon class="nav-icon" />
              <span class="nav-text">Apply Leave</span>
            </router-link>
            
            <router-link to="/employee/payslips" class="nav-item">
              <BanknotesIcon class="nav-icon" />
              <span class="nav-text">Payslips</span>
            </router-link>
            
            <router-link :to="{ name: 'TaskBoard' }" class="nav-item">
              <ClipboardDocumentListIcon class="nav-icon" />
              <span class="nav-text">Tasks</span>
            </router-link>
            
            <router-link :to="{ name: 'EmployeeSchedules' }" class="nav-item">
              <CalendarDaysIcon class="nav-icon" />
              <span class="nav-text">Schedules</span>
            </router-link>
            
            <router-link :to="{ name: 'myshifts' }" class="nav-item">
              <ClockIcon class="nav-icon" />
              <span class="nav-text">My Shifts</span>
            </router-link>
            
            <router-link :to="{ name: 'mytickets' }" class="nav-item">
              <TicketIcon class="nav-icon" />
              <span class="nav-text">Tickets</span>
              <span v-if="pendingTicketsCount > 0" class="sidebar-badge">{{ pendingTicketsCount }}</span>
            </router-link>
            
            <router-link :to="{ name: 'charts' }" class="nav-item">
              <ChartBarIcon class="nav-icon" />
              <span class="nav-text">Reports</span>
            </router-link>
          </template>
          
          <!-- Common Routes for All Roles -->
          <router-link :to="commonRoutes.profile" class="nav-item">
            <UserCircleIcon class="nav-icon" />
            <span class="nav-text">Profile</span>
          </router-link>
          
          <!-- Chat Button for All Roles -->
          <button @click="openChat" class="nav-item nav-button" :class="{ 'active': showChat }">
            <ChatBubbleLeftRightIcon class="nav-icon" />
            <span class="nav-text">Chat</span>
            <span v-if="unreadCount > 0 && !showChat" class="nav-badge">{{ unreadCount }}</span>
          </button>
        </nav>
      </aside>
      
      <!-- Main Content -->
      <main class="main-content">
        <router-view />
      </main>

      <!-- Chat Popup Modal (Common for All Roles) -->
      <transition name="chat-popup">
        <div v-if="showChat" class="chat-popup-overlay" @click.self="closeChat">
          <div class="chat-popup-container">
            <div class="chat-popup-header">
              <h3>
                <ChatBubbleLeftRightIcon class="inline-block w-6 h-6 mr-2" />
                Team Chat
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
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import { useRouter, useRoute } from 'vue-router'
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import ClockToggle from '@/components/common/Toggle.vue'
import ChatInterface from '@/components/ChatInterface.vue'
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
  ChatBubbleLeftRightIcon,
  XMarkIcon,
  PresentationChartLineIcon,
  CheckCircleIcon,
  BoltIcon,
  DocumentArrowUpIcon
} from '@heroicons/vue/24/outline'

export default {
  name: 'CentralLayout',
  components: {
    ClockToggle,
    ChatInterface,
    // Register Icons
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
    ChatBubbleLeftRightIcon,
    XMarkIcon,
    PresentationChartLineIcon,
    CheckCircleIcon,
    BoltIcon,
    DocumentArrowUpIcon
  },
  setup() {
    const authStore = useAuthStore()
    const router = useRouter()
    const route = useRoute()
    
    // Reactive state
    const showProfileDropdown = ref(false)
    const showChat = ref(false)
    const chatKey = ref(0)
    const pendingTicketsCount = ref(0)
    const unreadCount = ref(0)
    
    // Computed properties
    const userRole = computed(() => {
      return authStore.user?.role || 'employee'
    })
    
    const userName = computed(() => {
      return authStore.user?.name || authStore.user?.fullName || 'User'
    })
    
    const userEmail = computed(() => {
      return authStore.user?.email || ''
    })
    
    const getUserInitials = computed(() => {
      if (!userName.value) return 'U'
      return userName.value
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .toUpperCase()
        .substring(0, 2)
    })
    
    const userRoleDisplay = computed(() => {
      const roles = {
        'admin': 'Administrator',
        'manager': 'Manager',
        'employee': 'Employee',
        'super_admin': 'Super Admin'
      }
      return roles[userRole.value] || 'User'
    })
    
    const userRoleClass = computed(() => {
      const classes = {
        'admin': 'role-badge-admin',
        'manager': 'role-badge-manager',
        'employee': 'role-badge-employee',
        'super_admin': 'role-badge-super-admin'
      }
      return classes[userRole.value] || 'role-badge-default'
    })
    
    const getAppName = computed(() => {
      const names = {
        'admin': 'Payroll System',
        'manager': 'Manager Hub',
        'employee': 'Employee Portal'
      }
      return names[userRole.value] || 'Dashboard'
    })
    
    const dashboardRoute = computed(() => {
      return `/${userRole.value}/dashboard`
    })
    
    const profileRoute = computed(() => {
      return `/${userRole.value}/profile`
    })
    
    const ticketsRoute = computed(() => {
      return `/${userRole.value}/tickets`
    })
    
    const canAccessRestrictedPages = computed(() => {
      return authStore.user?.email === 'michaelchikoba0@gmail.com' || userRole.value === 'super_admin'
    })
    
    const showTicketBadge = computed(() => {
      return ['admin', 'manager'].includes(userRole.value)
    })
    
    const showTicketsInDropdown = computed(() => {
      return ['admin', 'manager', 'employee'].includes(userRole.value)
    })
    
    const showAttendanceToggle = computed(() => {
      return ['employee', 'manager'].includes(userRole.value)
    })
    
    const commonRoutes = computed(() => ({
      profile: `/${userRole.value}/profile`,
      tickets: `/${userRole.value}/tickets`,
      chat: `/${userRole.value}/chat`
    }))
    
    // Methods
    const logout = async () => {
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
    
    const fetchPendingTicketsCount = async () => {
      if (!showTicketBadge.value) return
      
      try {
        const response = await fetch(`/api/tickets/count?status=pending&role=${userRole.value}`, {
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
      router.push(ticketsRoute.value)
    }
    
    const openChat = () => {
      chatKey.value++
      showChat.value = true
      closeProfileDropdown()
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
    
    const handleClickOutside = (event) => {
      const profileDropdown = document.querySelector('.profile-dropdown')
      if (profileDropdown && !profileDropdown.contains(event.target)) {
        closeProfileDropdown()
      }
    }
    
    const handleEscapeKey = (event) => {
      if (event.key === 'Escape') {
        if (showProfileDropdown.value) closeProfileDropdown()
        if (showChat.value) closeChat()
      }
    }
    
    // Lifecycle
    onMounted(() => {
      document.addEventListener('click', handleClickOutside)
      document.addEventListener('keydown', handleEscapeKey)
      
      // Fetch initial pending tickets count
      fetchPendingTicketsCount()
      
      // Set up interval to refresh ticket count every 5 minutes
      const ticketRefreshInterval = setInterval(fetchPendingTicketsCount, 300000)
      
      // Listen for ticket updates
      window.addEventListener('ticket-created', fetchPendingTicketsCount)
      window.addEventListener('ticket-updated', fetchPendingTicketsCount)
      
      // Cleanup function
      onUnmounted(() => {
        document.removeEventListener('click', handleClickOutside)
        document.removeEventListener('keydown', handleEscapeKey)
        document.body.style.overflow = 'auto'
        
        // Clear interval
        if (ticketRefreshInterval) {
          clearInterval(ticketRefreshInterval)
        }
        
        // Remove event listeners
        window.removeEventListener('ticket-created', fetchPendingTicketsCount)
        window.removeEventListener('ticket-updated', fetchPendingTicketsCount)
      })
    })
    
    // Watch for route changes
    watch(() => route.path, () => {
      closeProfileDropdown()
      if (route.name?.includes('tickets')) {
        fetchPendingTicketsCount()
      }
    })
    
    return {
      // State
      showProfileDropdown,
      showChat,
      pendingTicketsCount,
      unreadCount,
      chatKey,
      
      // Computed
      userRole,
      userName,
      userEmail,
      getUserInitials,
      userRoleDisplay,
      userRoleClass,
      getAppName,
      dashboardRoute,
      profileRoute,
      ticketsRoute,
      canAccessRestrictedPages,
      showTicketBadge,
      showTicketsInDropdown,
      showAttendanceToggle,
      commonRoutes,
      
      // Methods
      logout,
      goToTickets,
      fetchPendingTicketsCount,
      openChat,
      closeChat,
      updateUnreadCount,
      toggleProfileDropdown,
      closeProfileDropdown
    }
  }
}
</script>

<style scoped>
/* Base Layout */
.central-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: #f5f7fa;
}

/* Header */
.header {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 4rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1rem 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  z-index: 100;
}

.logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1.5rem;
  font-weight: 700;
}

.logo-icon {
  width: 2rem;
  height: 2rem;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

/* Role Badge */
.role-badge {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.role-badge-admin {
  background: rgba(139, 92, 246, 0.9);
  color: white;
}

.role-badge-manager {
  background: rgba(59, 130, 246, 0.9);
  color: white;
}

.role-badge-employee {
  background: rgba(34, 197, 94, 0.9);
  color: white;
}

.role-badge-super-admin {
  background: linear-gradient(135deg, #ff6b6b, #ff8e53);
  color: white;
}

.role-badge-default {
  background: rgba(107, 114, 128, 0.9);
  color: white;
}

/* Ticket notification badge in header */
.ticket-notification-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(255, 255, 255, 0.15);
  color: white;
  padding: 6px 12px;
  border-radius: 20px;
  cursor: pointer;
  transition: all 0.3s ease;
  border: 1px solid rgba(255, 255, 255, 0.2);
  margin-right: 15px;
  font-size: 0.9rem;
  font-weight: 500;
}

.ticket-notification-badge:hover {
  background: rgba(255, 255, 255, 0.25);
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.ticket-icon {
  width: 1.25rem;
  height: 1.25rem;
}

.ticket-count {
  background: #ff6b6b;
  color: white;
  border-radius: 50%;
  width: 22px;
  height: 22px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 600;
  animation: pulse 2s infinite;
}

.ticket-text {
  font-weight: 500;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

/* User Profile Dropdown Styles */
.user-profile {
  position: relative;
}

.profile-dropdown {
  position: relative;
  display: inline-block;
}

.profile-trigger {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: rgba(255, 255, 255, 0.15);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.2);
  padding: 0.5rem 1rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s ease;
  font-family: inherit;
}

.profile-trigger:hover {
  background: rgba(255, 255, 255, 0.25);
}

.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.9);
  color: #667eea;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 600;
}

.user-name {
  font-weight: 500;
}

.user-email {
  font-size: 0.8rem;
  opacity: 0.8;
  margin-left: 0.5rem;
}

.dropdown-arrow {
  width: 0.75rem;
  height: 0.75rem;
  transition: transform 0.2s ease;
}

.rotated {
  transform: rotate(180deg);
}

.profile-dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  padding: 0.5rem 0;
  min-width: 220px;
  z-index: 1000;
  margin-top: 0.5rem;
  border: 1px solid #e2e8f0;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0.75rem 1rem;
  text-decoration: none;
  color: #4a5568;
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
  background-color: #f7fafc;
  color: #667eea;
}

.logout-dropdown-item {
  color: #e53e3e;
}

.logout-dropdown-item:hover {
  background-color: #fed7d7;
  color: #e53e3e;
}

.dropdown-icon {
  width: 1.25rem;
  height: 1.25rem;
}

.dropdown-divider {
  height: 1px;
  background-color: #e2e8f0;
  margin: 0.25rem 0;
}

.dropdown-badge {
  margin-left: auto;
  background: #ff6b6b;
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

/* Dropdown overlay for closing when clicking outside */
.dropdown-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 99;
  background: transparent;
}

/* Layout Body */
.layout-body {
  margin-top: 4rem;
  display: flex;
  flex: 1;
}

/* Sidebar */
.sidebar {
  position: fixed;
  top: 4rem;
  left: 0;
  width: 260px;
  height: calc(100vh - 4rem);
  background: white;
  box-shadow: 2px 0 8px rgba(0, 0, 0, 0.05);
  overflow-y: auto;
  z-index: 90;
  display: flex;
  flex-direction: column;
}

.attendance-toggle-section {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.sidebar-nav {
  padding: 1.5rem 0;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
}

.nav-item,
.nav-button {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.875rem 1.5rem;
  color: #4a5568;
  text-decoration: none;
  transition: all 0.2s ease;
  border-left: 3px solid transparent;
}

.nav-button {
  background: none;
  border: none;
  border-left: 3px solid transparent;
  font-family: inherit;
  font-size: inherit;
  cursor: pointer;
  width: 100%;
  text-align: left;
}

.nav-item:hover,
.nav-button:hover {
  background: #f7fafc;
  color: #667eea;
}

.nav-item.router-link-active,
.nav-button.active {
  background: #eef2ff;
  color: #667eea;
  border-left-color: #667eea;
  font-weight: 600;
}

.nav-icon {
  width: 24px;
  height: 24px;
  flex-shrink: 0;
}

.nav-text {
  font-size: 0.95rem;
}

/* Sidebar badges */
.sidebar-badge {
  margin-left: auto;
  background: #ff6b6b;
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

.nav-badge {
  margin-left: auto;
  background: #dc3545;
  color: white;
  border-radius: 50%;
  min-width: 20px;
  height: 20px;
  font-size: 0.7rem;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 2px;
  font-weight: 600;
  animation: badgePulse 1.5s infinite;
}

@keyframes badgePulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.1); }
  100% { transform: scale(1); }
}

.super-admin-badge {
  margin-left: auto;
  background: linear-gradient(135deg, #8b5cf6, #6366f1);
  color: white;
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Main Content */
.main-content {
  margin-left: 260px;
  margin-top: 0;
  flex: 1;
  padding: 2rem;
  overflow-y: auto;
  background: #f5f7fa;
  min-height: calc(100vh - 4rem);
}

/* Chat Popup Styles */
.chat-popup-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
  backdrop-filter: blur(3px);
}

.chat-popup-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
  width: 100%;
  max-width: 900px;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.chat-popup-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.chat-popup-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  display: flex;
  align-items: center;
}

.chat-close-btn {
  background: rgba(255, 255, 255, 0.2);
  border: none;
  color: white;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
}

.chat-close-btn:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: rotate(90deg);
}

.chat-popup-content {
  flex: 1;
  overflow: auto;
  display: flex;
  flex-direction: column;
  min-height: 400px;
}

/* Chat Popup Transition */
.chat-popup-enter-active,
.chat-popup-leave-active {
  transition: opacity 0.3s ease;
}

.chat-popup-enter-active .chat-popup-container,
.chat-popup-leave-active .chat-popup-container {
  transition: transform 0.3s ease;
}

.chat-popup-enter-from,
.chat-popup-leave-to {
  opacity: 0;
}

.chat-popup-enter-from .chat-popup-container,
.chat-popup-leave-to .chat-popup-container {
  transform: scale(0.9) translateY(20px);
}

/* Responsive */
@media (max-width: 768px) {
  .sidebar {
    width: 80px;
    left: 0;
  }
  
  .attendance-toggle-section {
    padding: 1rem;
    display: flex;
    justify-content: center;
  }
  
  .nav-text {
    display: none;
  }
  
  .nav-item,
  .nav-button {
    justify-content: center;
    padding: 1rem;
  }
  
  .sidebar-badge,
  .nav-badge,
  .super-admin-badge {
    position: absolute;
    top: 5px;
    right: 5px;
    margin: 0;
    min-width: 16px;
    height: 16px;
    font-size: 0.6rem;
  }
  
  .main-content {
    margin-left: 80px;
    padding: 1rem;
  }
  
  .logo-text {
    display: none;
  }
  
  .user-name,
  .user-email,
  .role-badge,
  .ticket-text {
    display: none;
  }
  
  .profile-trigger {
    padding: 0.5rem;
  }
  
  .profile-dropdown-menu {
    right: -10px;
    min-width: 160px;
  }
  
  .header {
    padding: 1rem;
  }
  
  .chat-popup-container {
    max-width: 100%;
    max-height: 90vh;
    margin: 0;
    border-radius: 12px;
  }
  
  .chat-popup-overlay {
    padding: 0.5rem;
  }
}

/* Small screens */
@media (max-width: 480px) {
  .ticket-notification-badge {
    padding: 4px 8px;
    font-size: 0.8rem;
  }
  
  .ticket-count {
    width: 18px;
    height: 18px;
    font-size: 0.7rem;
  }
  
  .ticket-notification-badge .ticket-text {
    display: none;
  }
}
</style>