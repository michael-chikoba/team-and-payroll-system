<template>
  <aside class="sidebar" :class="{ 'mobile-open': isSidebarOpen }">
    <div 
      class="sidebar-overlay" 
      v-if="isSidebarOpen && isMobile" 
      @click="$emit('close-sidebar')"
    ></div>
    
    <div class="sidebar-content">
      <!-- Logo/Header Section -->
      <div class="header-section">
        <div class="logo-area">
          <div class="logo-icon">⚡</div>
          <h2 class="app-title">Employee Portal</h2>
        </div>
        <button 
          v-if="isMobile" 
          class="sidebar-close"
          @click="$emit('close-sidebar')"
          aria-label="Close sidebar"
        >
          &times;
        </button>
      </div>

      <!-- Attendance Toggle Button Section -->
      <div class="attendance-toggle-section">
        <ClockToggle />
      </div>

      <!-- Navigation Menu -->
      <nav class="nav" aria-label="Main navigation">
        <div class="nav-section">
          <h3 class="nav-section-title">Main</h3>
          <router-link 
            to="/employee/dashboard" 
            class="nav-link" 
            active-class="active"
            @click="handleMobileClose"
          >
            <span class="link-icon">🏠</span>
            <span class="link-text">Dashboard</span>
            <span class="link-arrow">›</span>
          </router-link>
          
          <router-link 
            to="/employee/attendance" 
            class="nav-link" 
            active-class="active"
            @click="handleMobileClose"
          >
            <span class="link-icon">⏰</span>
            <span class="link-text">Attendance</span>
            <span class="link-arrow">›</span>
          </router-link>
          
          <router-link 
            to="/employee/leaves" 
            class="nav-link" 
            active-class="active"
            @click="handleMobileClose"
          >
            <span class="link-icon">🏖️</span>
            <span class="link-text">Leaves</span>
            <span class="link-arrow">›</span>
          </router-link>
        </div>

        <div class="nav-section">
          <h3 class="nav-section-title">Work</h3>
          <router-link 
            to="/employee/apply-leave" 
            class="nav-link" 
            active-class="active"
            @click="handleMobileClose"
          >
            <span class="link-icon">📝</span>
            <span class="link-text">Apply Leave</span>
            <span class="link-arrow">›</span>
          </router-link>
          
          <router-link 
            to="/employee/payslips" 
            class="nav-link" 
            active-class="active"
            @click="handleMobileClose"
          >
            <span class="link-icon">💵</span>
            <span class="link-text">Payslips</span>
            <span class="link-arrow">›</span>
          </router-link>
          
          <router-link 
            :to="{ name: 'TaskBoard' }" 
            class="nav-link" 
            active-class="active"
            @click="handleMobileClose"
          >
            <span class="link-icon">🗂️</span>
            <span class="link-text">Tasks</span>
            <span v-if="pendingTasksCount > 0" class="nav-badge">
              {{ pendingTasksCount > 9 ? '9+' : pendingTasksCount }}
            </span>
            <span class="link-arrow">›</span>
          </router-link>

          <router-link 
            :to="{ name: 'EmployeeSchedules' }" 
            class="nav-link" 
            active-class="active"
            @click="handleMobileClose"
          >
            <span class="link-icon">📅</span>
            <span class="link-text">Schedules</span>
            <span class="link-arrow">›</span>
          </router-link>

          <router-link 
            :to="{ name: 'myshifts' }" 
            class="nav-link" 
            active-class="active"
            @click="handleMobileClose"
          >
            <span class="link-icon">⏱️</span>
            <span class="link-text">My Shifts</span>
            <span class="link-arrow">›</span>
          </router-link>
        </div>

        <div class="nav-section">
          <h3 class="nav-section-title">Support</h3>
          <router-link 
            :to="{ name: 'mytickets' }" 
            class="nav-link" 
            active-class="active"
            @click="handleMobileClose"
          >
            <span class="link-icon">🎫</span>
            <span class="link-text">Tickets</span>
            <span v-if="pendingTicketsCount > 0" class="nav-badge ticket-badge">
              {{ pendingTicketsCount > 9 ? '9+' : pendingTicketsCount }}
            </span>
            <span class="link-arrow">›</span>
          </router-link>

          <router-link 
            :to="{ name: 'charts' }" 
            class="nav-link" 
            active-class="active"
            @click="handleMobileClose"
          >
            <span class="link-icon">📊</span>
            <span class="link-text">Reports</span>
            <span class="link-arrow">›</span>
          </router-link>
        </div>
      </nav>
      
      <!-- Mobile User Info -->
      <div class="user-info-section" v-if="isMobile">
        <div class="user-avatar">{{ getInitials() }}</div>
        <div class="user-details">
          <span class="user-name">{{ getUserName() }}</span>
          <span class="user-email">{{ getUserEmail() }}</span>
        </div>
        <button 
          class="logout-btn"
          @click="handleLogout"
          aria-label="Logout"
        >
          <span class="logout-icon">↪️</span>
        </button>
      </div>
    </div>
  </aside>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import ClockToggle from '@/components/common/Toggle.vue'

export default {
  name: 'EmployeeSidebar',
  components: {
    ClockToggle
  },
  props: {
    isSidebarOpen: {
      type: Boolean,
      default: false
    },
    pendingTicketsCount: {
      type: Number,
      default: 0
    },
    pendingTasksCount: {
      type: Number,
      default: 0
    },
    isMobile: {
      type: Boolean,
      default: false
    }
  },
  emits: ['close-sidebar', 'close-sidebar-mobile'],
  setup(props, { emit }) {
    const authStore = useAuthStore()
    const router = useRouter()
    
    const getInitials = () => {
      const name = authStore.user?.fullName || 'Employee'
      return name.split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2)
    }
    
    const getUserName = () => {
      return authStore.user?.fullName || 'Employee'
    }
    
    const getUserEmail = () => {
      return authStore.user?.email || ''
    }
    
    const handleMobileClose = () => {
      if (props.isMobile) {
        emit('close-sidebar')
      }
      emit('close-sidebar-mobile')
    }
    
    const handleLogout = async () => {
      try {
        await authStore.logout()
        router.push('/login')
        emit('close-sidebar')
      } catch (error) {
        console.error('Logout failed:', error)
      }
    }
    
    return {
      getInitials,
      getUserName,
      getUserEmail,
      handleMobileClose,
      handleLogout
    }
  }
}
</script>

<style scoped>
/* CSS Variables for consistent theming - Enhanced for better visibility */
:root {
  --sidebar-width: 280px;
  --sidebar-bg: #ffffff;
  --sidebar-border: #e2e8f0;
  --sidebar-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  --primary-color: #2563eb;
  --primary-light: #3b82f6;
  --primary-hover: #1d4ed8;
  --danger-color: #dc2626;
  --warning-color: #d97706;
  --success-color: #059669;
  --text-primary: #1e293b;
  --text-secondary: #475569;
  --text-light: #64748b;
  --hover-bg: #f8fafc;
  --active-bg: #eff6ff;
  --border-radius: 10px;
  --transition-speed: 0.2s;
  --nav-gap: 6px;
}

/* Sidebar Container */
.sidebar {
  width: var(--sidebar-width);
  background-color: var(--sidebar-bg);
  border-right: 1px solid var(--sidebar-border);
  box-shadow: var(--sidebar-shadow);
  display: flex;
  flex-direction: column;
  height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
  z-index: 1000;
  transition: transform var(--transition-speed) cubic-bezier(0.4, 0, 0.2, 1);
  overflow-y: auto;
  overflow-x: hidden;
}

.sidebar-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  z-index: 999;
  backdrop-filter: blur(4px);
  animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.sidebar-content {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 1.5rem 1rem;
  gap: 1.5rem;
}

/* Header Section */
.header-section {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem;
  flex-shrink: 0;
  border-bottom: 1px solid var(--sidebar-border);
  padding-bottom: 1rem;
  margin-bottom: 0.5rem;
}

.logo-area {
  display: flex;
  align-items: center;
  gap: 12px;
}

.logo-icon {
  font-size: 2rem;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
  background-clip: text;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.app-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
  letter-spacing: -0.5px;
}

.sidebar-close {
  display: none;
  background: var(--hover-bg);
  border: none;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.2s ease;
}

.sidebar-close:hover {
  background: var(--danger-color);
  color: white;
  transform: rotate(90deg);
}

/* Attendance Toggle Section */
.attendance-toggle-section {
  background: #f0f9ff;
  border-radius: var(--border-radius);
  padding: 1rem;
  border: 2px solid #dbeafe;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
}

/* Navigation Menu - Enhanced for visibility */
.nav {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  flex: 1;
  overflow-y: auto;
  overflow-x: hidden;
  padding: 0.5rem 0;
}

.nav::-webkit-scrollbar {
  width: 4px;
}

.nav::-webkit-scrollbar-track {
  background: transparent;
}

.nav::-webkit-scrollbar-thumb {
  background-color: #cbd5e1;
  border-radius: 2px;
}

.nav-section {
  display: flex;
  flex-direction: column;
  gap: var(--nav-gap);
}

.nav-section-title {
  font-size: 0.8125rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-secondary);
  margin: 0 0 0.75rem 0.75rem;
  padding-bottom: 0.25rem;
  border-bottom: 1px solid #f1f5f9;
}

/* Navigation Links - Enhanced visibility */
.nav-link {
  display: flex;
  align-items: center;
  gap: 14px;
  color: var(--text-secondary);
  text-decoration: none;
  padding: 1rem 1.25rem;
  border-radius: var(--border-radius);
  font-weight: 500;
  transition: all var(--transition-speed) ease;
  position: relative;
  border: 1px solid transparent;
  min-height: 52px;
  background: white;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.nav-link:hover {
  background: var(--hover-bg);
  color: var(--primary-hover);
  transform: translateX(4px);
  border-color: #e2e8f0;
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.08);
}

.nav-link:hover .link-icon {
  transform: scale(1.1);
  filter: brightness(1.2);
}

.nav-link:hover .link-arrow {
  opacity: 1;
  transform: translateX(0);
}

.nav-link.active {
  background: var(--active-bg);
  color: var(--primary-color);
  font-weight: 600;
  border-left: 4px solid var(--primary-color);
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.15);
}

.nav-link.active .link-icon {
  color: var(--primary-color);
  filter: none;
}

.nav-link.active .link-arrow {
  opacity: 1;
  color: var(--primary-color);
  transform: translateX(0);
}

.link-icon {
  font-size: 1.35rem;
  width: 24px;
  text-align: center;
  transition: all var(--transition-speed) ease;
  flex-shrink: 0;
  filter: brightness(0.8);
}

.link-text {
  flex: 1;
  font-size: 0.95rem;
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  letter-spacing: -0.3px;
}

.link-arrow {
  color: var(--text-light);
  font-size: 1.2rem;
  font-weight: bold;
  opacity: 0.5;
  transform: translateX(-4px);
  transition: all var(--transition-speed) ease;
  flex-shrink: 0;
}

/* Badge Styling */
.nav-badge {
  margin-left: auto;
  margin-right: 8px;
  background-color: var(--warning-color);
  color: white;
  border-radius: 20px;
  min-width: 24px;
  height: 24px;
  font-size: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 8px;
  font-weight: 700;
  flex-shrink: 0;
  animation: subtlePulse 2s infinite;
  box-shadow: 0 2px 4px rgba(217, 119, 6, 0.3);
}

.ticket-badge {
  background-color: var(--danger-color);
  box-shadow: 0 2px 4px rgba(220, 38, 38, 0.3);
}

@keyframes subtlePulse {
  0%, 100% { 
    transform: scale(1); 
  }
  50% { 
    transform: scale(1.05); 
  }
}

/* User Info Section */
.user-info-section {
  display: none;
  align-items: center;
  gap: 14px;
  padding: 1.25rem;
  background: linear-gradient(135deg, #f8fafc, #f1f5f9);
  border-radius: var(--border-radius);
  border: 1px solid var(--sidebar-border);
  margin-top: auto;
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.user-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1rem;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(37, 99, 235, 0.3);
}

.user-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 4px;
  overflow: hidden;
  min-width: 0;
}

.user-name {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.95rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.user-email {
  font-size: 0.8125rem;
  color: var(--text-light);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.logout-btn {
  background: transparent;
  border: 1px solid #e2e8f0;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  color: var(--text-secondary);
  flex-shrink: 0;
}

.logout-btn:hover {
  background: var(--danger-color);
  border-color: var(--danger-color);
  color: white;
  transform: translateX(2px);
  box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
}

.logout-icon {
  font-size: 1.1rem;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .sidebar {
    width: 260px;
  }
}

@media (max-width: 992px) {
  .sidebar {
    transform: translateX(-100%);
    width: 300px;
  }
  
  .sidebar.mobile-open {
    transform: translateX(0);
  }
  
  .sidebar-close {
    display: flex;
  }
  
  .user-info-section {
    display: flex;
  }
  
  .nav-link {
    padding: 1rem 1rem;
    min-height: 48px;
  }
}

@media (max-width: 768px) {
  .sidebar {
    width: 280px;
  }
  
  .sidebar-content {
    padding: 1.25rem 0.875rem;
  }
  
  .nav-section-title {
    font-size: 0.75rem;
  }
  
  .nav-link {
    padding: 0.875rem 1rem;
  }
}

@media (max-width: 576px) {
  .sidebar {
    width: 100%;
    max-width: 320px;
  }
  
  .nav-link {
    padding: 0.875rem 1rem;
    min-height: 46px;
  }
  
  .link-text {
    font-size: 0.9rem;
  }
  
  .link-icon {
    font-size: 1.25rem;
  }
}

@media (max-width: 375px) {
  .sidebar-content {
    padding: 1rem 0.75rem;
    gap: 1.25rem;
  }
  
  .nav-link {
    padding: 0.75rem 0.875rem;
    font-size: 0.875rem;
    min-height: 44px;
  }
  
  .link-icon {
    font-size: 1.15rem;
    width: 22px;
  }
  
  .user-avatar {
    width: 42px;
    height: 42px;
    font-size: 0.9rem;
  }
}

/* High Contrast Mode Support */
@media (prefers-contrast: high) {
  .sidebar {
    border-right: 2px solid var(--text-primary);
  }
  
  .nav-link {
    border: 2px solid transparent;
  }
  
  .nav-link.active {
    border-left: 4px solid var(--text-primary);
    border: 2px solid var(--text-primary);
  }
  
  .nav-badge {
    border: 1px solid white;
  }
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
  .sidebar,
  .nav-link,
  .sidebar-close,
  .logout-btn,
  .link-icon,
  .link-arrow {
    transition: none;
  }
  
  .nav-link:hover {
    transform: none;
  }
  
  .nav-badge {
    animation: none;
  }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
  .sidebar {
    --sidebar-bg: #1e293b;
    --sidebar-border: #334155;
    --text-primary: #f1f5f9;
    --text-secondary: #cbd5e1;
    --text-light: #94a3b8;
    --hover-bg: #2d3748;
    --active-bg: rgba(59, 130, 246, 0.2);
  }
  
  .nav-link {
    background: #1e293b;
  }
  
  .attendance-toggle-section {
    background: #0f172a;
    border-color: #1e40af;
  }
  
  .user-info-section {
    background: linear-gradient(135deg, #0f172a, #1e293b);
    border-color: #334155;
  }
  
  .nav-section-title {
    border-bottom-color: #334155;
  }
  
  .header-section {
    border-bottom-color: #334155;
  }
}

/* Print Styles */
@media print {
  .sidebar {
    display: none !important;
  }
}
</style>