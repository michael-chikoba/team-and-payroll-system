<!-- layouts/AdminLayout.vue -->
<template>
  <div class="admin-layout">
    <!-- Header -->
    <header class="header">
      <div class="logo">
        <BriefcaseIcon class="logo-icon h-8 w-8" />
        <span class="logo-text">Payroll System</span>
      </div>
     
      <div class="header-right">
        <!-- Ticket Notification Badge -->
        <div v-if="pendingTicketsCount > 0" class="ticket-notification-badge" @click="goToTickets">
          <TicketIcon class="ticket-icon h-5 w-5" />
          <span class="ticket-count">{{ pendingTicketsCount }}</span>
          <span class="ticket-text">Pending Tickets</span>
        </div>
        
         <div class="user-profile">
          <!-- Profile dropdown trigger -->
          <div class="profile-dropdown">
            <button class="profile-trigger" @click="toggleProfileDropdown">
              <span class="user-avatar">{{ getUserInitials }}</span>
              <div class="user-info">
                <span class="user-name">{{ user?.name || 'Admin User' }}</span>
                <span class="user-email">{{ user?.email || 'admin@example.com' }}</span>
              </div>
              <ChevronDownIcon 
                class="dropdown-arrow h-4 w-4" 
                :class="{ 'rotated': showProfileDropdown }">
              </ChevronDownIcon>
            </button>
            
            <!-- Profile dropdown menu -->
            <transition name="dropdown">
              <div v-if="showProfileDropdown" class="profile-dropdown-menu">
                <router-link 
                  to="/admin/profile" 
                  class="dropdown-item"
                  @click="closeProfileDropdown"
                >
                  <UserCircleIcon class="dropdown-icon h-5 w-5" />
                  <span class="dropdown-text">My Profile</span>
                </router-link>
                
                <router-link 
                  to="/admin/tickets" 
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
    ></div>
    
    <div class="layout-body">
      <!-- Sidebar -->
      <aside class="sidebar">
        <!-- Attendance Toggle Button Section -->
        <div class="attendance-toggle-section">
          <ClockToggle />
        </div>
        <nav class="sidebar-nav">
          <!-- Swapped Squares2x2Icon for HomeIcon to fix build error -->
          <router-link to="/admin/dashboard" class="nav-item">
            <HomeIcon class="nav-icon" />
            <span class="nav-text">Dashboard</span>
          </router-link>
         
          <router-link to="/admin/employees" class="nav-item">
            <UsersIcon class="nav-icon" />
            <span class="nav-text">Employees</span>
          </router-link>
         
          <!-- Add Employees - STILL RESTRICTED -->
          <router-link 
            v-if="canAccessAddEmployees"
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

          <!-- Ticket Management -->
          <router-link to="/admin/tickets" class="nav-item">
            <TicketIcon class="nav-icon" />
            <span class="nav-text">Ticket Management</span>
            <span v-if="pendingTicketsCount > 0" class="sidebar-badge">{{ pendingTicketsCount }}</span>
          </router-link>
          
          <!-- Tasks Management -->
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
         
          <!-- Country Management -->
          <router-link to="/admin/countries" class="nav-item">
            <GlobeAltIcon class="nav-icon" />
            <span class="nav-text">Countries</span>
          </router-link>
         
          <router-link to="/admin/settings" class="nav-item">
            <Cog6ToothIcon class="nav-icon" />
            <span class="nav-text">Settings</span>
          </router-link>
         
          <!-- Business Management -->
          <router-link to="/admin/businesses" class="nav-item">
            <BuildingOfficeIcon class="nav-icon" />
            <span class="nav-text">Business Management</span>
          </router-link>

          <!-- AI Chat Assistant -->
          <button @click="toggleChat" class="nav-item nav-button" :class="{ 'active': showChat }">
            <ChatBubbleLeftRightIcon class="nav-icon" />
            <span class="nav-text">Chat</span>
          </button>
        </nav>
      </aside>
      
      <!-- Main Content -->
      <main class="main-content">
        <router-view />
      </main>

      <!-- Chat Popup Modal -->
      <transition name="chat-popup">
        <div v-if="showChat" class="chat-popup-overlay" @click.self="toggleChat">
          <div class="chat-popup-container">
            <div class="chat-popup-header">
              <h3>
                <ChatBubbleLeftRightIcon class="inline-block w-6 h-6 mr-2" />
               Chat Assistant
              </h3>
              <button @click="toggleChat" class="chat-close-btn">
                <XMarkIcon class="w-6 h-6" />
              </button>
            </div>
            <div class="chat-popup-content">
              <ChartComponent @close="toggleChat" />
            </div>
          </div>
        </div>
      </transition>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { ref, onMounted, onUnmounted } from 'vue'
import ClockToggle from '@/components/common/Toggle.vue'
import ChartComponent from '@/components/ChatInterface.vue'
import { 
  BriefcaseIcon, 
  TicketIcon, 
  ChevronDownIcon,
  UserCircleIcon,
  ArrowLeftOnRectangleIcon,
  HomeIcon, // Replaced Squares2x2Icon
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
  XMarkIcon
} from '@heroicons/vue/24/outline'

export default {
  name: 'AdminLayout',
  components: {
    ClockToggle,
    ChartComponent,
    // Register Icons
    BriefcaseIcon,
    TicketIcon,
    ChevronDownIcon,
    UserCircleIcon,
    ArrowLeftOnRectangleIcon,
    HomeIcon, // Replaced Squares2x2Icon
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
    XMarkIcon
  },
  setup() {
    const authStore = useAuthStore()
    const router = useRouter()
    
    const showProfileDropdown = ref(false)
    const showChat = ref(false)
    const pendingTicketsCount = ref(0)
   
    const logout = async () => {
      try {
        await authStore.logout()
        // Redirect to login page after logout
        window.location.href = '/auth/login'
      } catch (error) {
        console.error('Logout failed:', error)
        // Fallback: clear local storage and redirect
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        window.location.href = '/auth/login'
      }
    }

    // Fetch pending tickets count for admin
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
   
    return {
      logout,
      user: authStore.user,
      showProfileDropdown,
      showChat,
      pendingTicketsCount,
      goToTickets,
      fetchPendingTicketsCount
    }
  },
  computed: {
    getUserInitials() {
      if (!this.user?.name) return 'A'
      return this.user.name
        .split(' ')
        .map(word => word.charAt(0))
        .join('')
        .toUpperCase()
        .substring(0, 2)
    },
    // Check if current user can access add employees (Still Restricted)
    canAccessAddEmployees() {
      return this.user?.email === 'michaelchikoba0@gmail.com'
    }
  },
  methods: {
    toggleProfileDropdown() {
      this.showProfileDropdown = !this.showProfileDropdown
    },
    closeProfileDropdown() {
      this.showProfileDropdown = false
    },
    toggleChat() {
      this.showChat = !this.showChat
    },
    handleClickOutside(event) {
      const profileDropdown = this.$el.querySelector('.profile-dropdown')
      if (profileDropdown && !profileDropdown.contains(event.target)) {
        this.closeProfileDropdown()
      }
    }
  },
  mounted() {
    // Add click outside listener
    document.addEventListener('click', this.handleClickOutside)
    
    // Fetch initial pending tickets count
    this.fetchPendingTicketsCount()
    
    // Set up interval to refresh ticket count every 5 minutes
    this.ticketRefreshInterval = setInterval(this.fetchPendingTicketsCount, 300000)
    
    // Listen for ticket updates
    window.addEventListener('ticket-created', this.fetchPendingTicketsCount)
    window.addEventListener('ticket-updated', this.fetchPendingTicketsCount)
  },
  beforeUnmount() {
    // Remove click outside listener
    document.removeEventListener('click', this.handleClickOutside)
    
    // Clear interval
    if (this.ticketRefreshInterval) {
      clearInterval(this.ticketRefreshInterval)
    }
    
    // Remove event listeners
    window.removeEventListener('ticket-created', this.fetchPendingTicketsCount)
    window.removeEventListener('ticket-updated', this.fetchPendingTicketsCount)
  }
}
</script>

<style scoped>
/* Add these styles to your existing AdminLayout styles */

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

/* Sidebar badge */
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

/* Dropdown badge */
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

.user-email {
  font-size: 0.8rem;
  opacity: 0.8;
  margin-left: 0.5rem;
}

.business-management-item,
.nav-item:has(.super-admin-badge) {
  position: relative;
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

/* Enhanced navigation item styles for better visual hierarchy */
.nav-item {
  position: relative;
}

.nav-item.router-link-active .super-admin-badge {
  background: linear-gradient(135deg, #7c3aed, #4f46e5);
}

.nav-item.router-link-active[href*="tickets"] {
  background: rgba(255, 107, 107, 0.1);
  color: #ff6b6b;
  border-left-color: #ff6b6b;
}

.nav-item.router-link-active[href*="tickets"] .nav-icon {
  color: #ff6b6b;
}

.nav-item[href*="tickets"]:hover {
  background: rgba(255, 107, 107, 0.08);
  color: #ff6b6b;
}

.admin-layout {
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
  overflow: hidden;
  display: flex;
  flex-direction: column;
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
  
  .sidebar-badge {
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
  
  .user-name {
    display: none;
  }
  
  .ticket-notification-badge .ticket-text {
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
}
</style>