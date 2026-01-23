<template>
  <aside class="sidebar" :class="{ 'mobile-open': isSidebarOpen }">
    <div class="sidebar-overlay" v-if="isSidebarOpen" @click="$emit('close-sidebar')"></div>
    <div class="sidebar-content">
      <div class="logo-section">
        <span class="logo-icon"></span>
        <h1 class="title">Portal</h1>
        <button class="sidebar-close" @click="$emit('close-sidebar')">
          <span>×</span>
        </button>
      </div>
      
      <!-- Attendance Toggle Button Section -->
      <div class="attendance-toggle-section">
        <ClockToggle />
      </div>

      <!-- Navigation Menu -->
      <nav class="nav">
        <router-link to="/employee/dashboard" class="nav-link" active-class="active" @click="$emit('close-sidebar-mobile')">
          <span class="link-icon">🏠</span>
          <span class="link-text">Dashboard</span>
        </router-link>
        
        <router-link to="/employee/attendance" class="nav-link" active-class="active" @click="$emit('close-sidebar-mobile')">
          <span class="link-icon">⏰</span>
          <span class="link-text">Attendance</span>
        </router-link>
        
        <router-link to="/employee/leaves" class="nav-link" active-class="active" @click="$emit('close-sidebar-mobile')">
          <span class="link-icon">🏖️</span>
          <span class="link-text">Leaves</span>
        </router-link>
        
        <router-link to="/employee/apply-leave" class="nav-link" active-class="active" @click="$emit('close-sidebar-mobile')">
          <span class="link-icon">📝</span>
          <span class="link-text">Apply Leave</span>
        </router-link>
        
        <router-link to="/employee/payslips" class="nav-link" active-class="active" @click="$emit('close-sidebar-mobile')">
          <span class="link-icon">💵</span>
          <span class="link-text">Payslips</span>
        </router-link>
        
        <!-- Tasks link -->
        <router-link :to="{ name: 'TaskBoard' }" class="nav-link" active-class="active" @click="$emit('close-sidebar-mobile')">
          <span class="link-icon">🗂️</span>
          <span class="link-text">Tasks</span>
        </router-link>

        <!-- Schedules link -->
        <router-link :to="{ name: 'EmployeeSchedules' }" class="nav-link" active-class="active" @click="$emit('close-sidebar-mobile')">
          <span class="link-icon">📅</span>
          <span class="link-text">Schedules</span>
        </router-link>

        <!-- My Shifts link -->
        <router-link :to="{ name: 'myshifts' }" class="nav-link" active-class="active" @click="$emit('close-sidebar-mobile')">
          <span class="link-icon">⏱️</span>
          <span class="link-text">My Shifts</span>
        </router-link>

        <!-- Tickets Link -->
        <router-link :to="{ name: 'mytickets' }" class="nav-link" active-class="active" @click="$emit('close-sidebar-mobile')">
          <span class="link-icon">🎫</span>
          <span class="link-text">Tickets</span>
          <span v-if="pendingTicketsCount > 0" class="notification-badge-sidebar">
            {{ pendingTicketsCount > 9 ? '9+' : pendingTicketsCount }}
          </span>
        </router-link>

        <!-- Reports link -->
        <router-link :to="{ name: 'charts' }" class="nav-link" active-class="active" @click="$emit('close-sidebar-mobile')">
          <span class="link-icon">📊</span>
          <span class="link-text">Reports</span>
        </router-link>
      </nav>
      
      <!-- Mobile User Info -->
      <div class="mobile-user-info" v-if="isMobile">
        <div class="mobile-avatar">{{ getInitials() }}</div>
        <div class="mobile-user-details">
          <span class="mobile-user-name">{{ getUserName() }}</span>
          <span class="mobile-user-email">{{ getUserEmail() }}</span>
        </div>
      </div>
    </div>
  </aside>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
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
    isMobile: {
      type: Boolean,
      default: false
    }
  },
  emits: ['close-sidebar', 'close-sidebar-mobile'],
  setup() {
    const authStore = useAuthStore()
    
    const getInitials = () => {
      const name = authStore.user?.fullName || 'Employee'
      return name.split(' ').map(word => word[0]).join('').toUpperCase().slice(0, 2)
    }
    
    const getUserName = () => {
      return authStore.user?.fullName || 'Employee'
    }
    
    const getUserEmail = () => {
      return authStore.user?.email || ''
    }
    
    return {
      getInitials,
      getUserName,
      getUserEmail
    }
  }
}
</script>

<style scoped>
/* Sidebar Styling */
.sidebar {
  width: 260px;
  background-color: var(--surface-color);
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
  padding: 1.5rem 1rem;
  display: flex;
  flex-direction: column;
  height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
  z-index: 1000;
  transition: transform 0.3s ease;
  overflow-y: auto;
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
}

.sidebar-content {
  display: flex;
  flex-direction: column;
  height: 100%;
  position: relative;
  z-index: 1001;
  background: var(--surface-color);
}

.logo-section {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 1rem;
  padding: 0 0.5rem;
  flex-shrink: 0;
  position: relative;
}

.logo-icon {
  font-size: 1.8rem;
}

.title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-color);
  flex: 1;
}

.sidebar-close {
  display: none;
  background: none;
  border: none;
  font-size: 2rem;
  color: var(--text-light);
  cursor: pointer;
  width: 32px;
  height: 32px;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  transition: all 0.2s;
}

.sidebar-close:hover {
  background-color: #f0f4f8;
  color: var(--danger-color);
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
  width: 24px;
  text-align: center;
}

.link-text {
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Mobile User Info */
.mobile-user-info {
  display: none;
  padding: 1rem;
  border-top: 1px solid var(--border-color);
  margin-top: auto;
  align-items: center;
  gap: 12px;
  background: #f8fafc;
  border-radius: 12px;
  margin: 1rem 0.25rem 0;
}

.mobile-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.9rem;
  flex-shrink: 0;
}

.mobile-user-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2px;
  overflow: hidden;
}

.mobile-user-name {
  font-weight: 600;
  color: var(--text-color);
  font-size: 0.9rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.mobile-user-email {
  font-size: 0.75rem;
  color: var(--text-light);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
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
  flex-shrink: 0;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

/* Responsive Design */
@media (max-width: 1200px) {
  .sidebar {
    width: 220px;
  }
}

@media (max-width: 992px) {
  .sidebar {
    width: 220px;
  }
  
  .sidebar {
    transform: translateX(-100%);
  }
  
  .sidebar.mobile-open {
    transform: translateX(0);
  }
  
  .sidebar-close {
    display: flex;
  }
}

@media (max-width: 768px) {
  .sidebar {
    width: 280px;
  }
  
  .mobile-user-info {
    display: flex;
  }
}

@media (max-width: 576px) {
  .sidebar {
    width: 100%;
    max-width: 320px;
  }
}

@media (max-width: 375px) {
  .sidebar {
    padding: 1rem 0.5rem;
  }
  
  .nav-link {
    padding: 0.75rem 1rem;
    font-size: 0.9rem;
  }
  
  .link-icon {
    font-size: 1.1rem;
    width: 20px;
  }
  
  .mobile-avatar {
    width: 36px;
    height: 36px;
    font-size: 0.8rem;
  }
}

/* Small height devices */
@media (max-height: 600px) {
  .sidebar {
    padding: 1rem 0.5rem;
  }
  
  .nav-link {
    padding: 0.6rem 1rem;
  }
}

/* Print styles */
@media print {
  .sidebar {
    display: none !important;
  }
}
</style>