<template>
  <header class="top-header">
    <div class="header-left">
      <h2 class="page-title">{{ currentPageTitle }}</h2>
    </div>
     <div class="logo-section">
        <span class="logo-icon">🚀</span>
        <h1 class="title">Portal</h1>
        <button class="sidebar-close" @click="$emit('close-sidebar')">
          <span>×</span>
        </button>
      </div>
      
    <div class="header-right">
      <!-- Quick Action Buttons -->
      <div class="quick-actions">
        <!-- Create Ticket Button (Visible on Tickets page) -->
        <button 
          v-if="showQuickCreateTicket"
          @click="$emit('open-quick-create-ticket')" 
          class="quick-action-button"
          title="Create New Ticket"
        >
          <span class="quick-action-icon">🎫</span>
          <span class="quick-action-text">New Ticket</span>
        </button>
        
        <button @click="$emit('open-chart-modal')" class="quick-action-button" title="View Charts">
          <span class="quick-action-icon">📊</span>
          <span class="quick-action-text">Charts</span>
        </button>
        
        <!-- Notification Bell Button -->
        <NotificationBell />
      </div>
      
      <!-- Profile Dropdown -->
      <div class="profile-dropdown-container" ref="dropdownContainer">
        <button @click="handleDropdownClick" class="profile-button">
          <span class="avatar-placeholder">{{ getInitials() }}</span>
          <span class="user-name-header">{{ getUserName() }}</span>
          <span class="dropdown-arrow" :class="{ 'open': isDropdownOpen }">▼</span>
        </button>
        
        <!-- Dropdown Menu -->
        <transition name="dropdown">
          <div v-if="isDropdownOpen" class="dropdown-menu">
            <router-link to="/employee/profile" class="dropdown-item" @click="handleDropdownItemClick">
              <span class="dropdown-icon">👤</span> Profile
            </router-link>
            <router-link :to="{ name: 'mytickets' }" class="dropdown-item" @click="handleDropdownItemClick">
              <span class="dropdown-icon">🎫</span> My Tickets
              <span v-if="pendingTicketsCount > 0" class="dropdown-badge">
                {{ pendingTicketsCount > 9 ? '9+' : pendingTicketsCount }}
              </span>
            </router-link>
            <router-link to="/employee/charts" class="dropdown-item" @click="handleDropdownItemClick">
              <span class="dropdown-icon">📊</span> Reports & Charts
            </router-link>
            <div class="dropdown-divider"></div>
            <button @click="handleLogout" class="dropdown-item logout-item">
              <span class="dropdown-icon">➡️</span> Logout
            </button>
          </div>
        </transition>
      </div>
    </div>
  </header>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import { onMounted, onUnmounted, ref } from 'vue'
import NotificationBell from '@/components/NotificationBell.vue'

export default {
  name: 'EmployeeHeader',
  components: {
    NotificationBell
  },
  props: {
    currentPageTitle: {
      type: String,
      default: 'Welcome Back!'
    },
    showQuickCreateTicket: {
      type: Boolean,
      default: false
    },
    pendingTicketsCount: {
      type: Number,
      default: 0
    },
    isDropdownOpen: {
      type: Boolean,
      default: false
    }
  },
  emits: ['toggle-dropdown', 'close-dropdown', 'open-chart-modal', 'open-quick-create-ticket'],
  setup(props, { emit }) {
    const authStore = useAuthStore()
    const router = useRouter()
    const dropdownContainer = ref(null)
    
    const getInitials = () => {
      const name = authStore.user?.fullName || 'Employee'
      return name.split(' ').map(word => word[0]).join('').toUpperCase().slice(0, 2)
    }
    
    const getUserName = () => {
      return authStore.user?.fullName || 'Employee'
    }
    
    const handleDropdownClick = () => {
      emit('toggle-dropdown')
    }
    
    const handleDropdownItemClick = () => {
      emit('close-dropdown')
    }
    
    const handleLogout = () => {
      emit('close-dropdown')
      authStore.clearAuth()
      router.push({ name: 'login' })
    }
    
    const handleClickOutside = (event) => {
      if (dropdownContainer.value && !dropdownContainer.value.contains(event.target)) {
        emit('close-dropdown')
      }
    }
    
    onMounted(() => {
      document.addEventListener('click', handleClickOutside)
    })
    
    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
    })
    
    return {
      dropdownContainer,
      getInitials,
      getUserName,
      handleDropdownClick,
      handleDropdownItemClick,
      handleLogout
    }
  }
}
</script>

<style scoped>
.top-header {
  background-color: var(--surface-color);
  padding: 1rem 2rem;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-shrink: 0;
  gap: 1rem;
  position: sticky;
  top: 0;
  z-index: 100;
  backdrop-filter: blur(10px);
  background: rgba(255, 255, 255, 0.9);
}

.header-left {
  flex: 1;
  min-width: 0;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--text-color);
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.quick-actions { 
  display: flex; 
  gap: 8px; 
  align-items: center;
  flex-wrap: wrap;
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
  white-space: nowrap;
}

.quick-action-button:hover {
  border-color: var(--primary-color);
  color: var(--primary-color);
  background-color: rgba(74, 144, 226, 0.05);
  transform: translateY(-1px);
  box-shadow: var(--shadow-light);
}

.quick-action-icon {
  font-size: 1.1rem;
  flex-shrink: 0;
}

.quick-action-text {
  flex-shrink: 0;
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
  white-space: nowrap;
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
  flex-shrink: 0;
}

.user-name-header {
  font-weight: 500;
  color: var(--text-color);
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 150px;
}

.dropdown-arrow {
  font-size: 0.7rem;
  transition: transform 0.2s;
  color: var(--text-light);
  flex-shrink: 0;
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
  box-shadow: var(--shadow-heavy);
  min-width: 220px;
  max-width: 300px;
  padding: 0.5rem 0;
  z-index: 1000;
  overflow: hidden;
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
  white-space: nowrap;
}

.dropdown-item:hover { 
  background-color: #f0f4f8; 
}

.dropdown-icon {
  font-size: 1.1rem;
  flex-shrink: 0;
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
  flex-shrink: 0;
}

.dropdown-divider { 
  border-bottom: 1px solid var(--border-color); 
  margin: 0.5rem 0; 
}

.logout-item { 
  color: var(--danger-color);
  font-weight: 500;
}

/* Transitions */
.dropdown-enter-active, .dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from, .dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Responsive Design */
@media (max-width: 992px) {
  .top-header {
    padding: 1rem;
    flex-wrap: wrap;
    gap: 1rem;
  }
  
  .header-left {
    order: 1;
    width: 100%;
    margin-bottom: 0.5rem;
  }
  
  .header-right {
    order: 2;
    width: 100%;
    justify-content: space-between;
  }
}

@media (max-width: 768px) {
  .top-header {
    padding: 0.75rem;
    gap: 0.75rem;
  }
  
  .page-title {
    font-size: 1.3rem;
  }
  
  .quick-actions {
    gap: 6px;
  }
  
  .quick-action-button {
    padding: 6px 10px;
    font-size: 0.85rem;
  }
  
  .user-name-header {
    max-width: 100px;
  }
  
  .profile-button .user-name-header {
    display: none;
  }
}

@media (max-width: 576px) {
  .top-header {
    padding: 0.75rem;
    flex-direction: column;
    align-items: stretch;
    gap: 0.75rem;
  }
  
  .header-left,
  .header-right {
    width: 100%;
  }
  
  .quick-actions {
    justify-content: flex-start;
    order: 1;
  }
  
  .profile-dropdown-container {
    order: 2;
  }
  
  .quick-action-text {
    display: none;
  }
  
  .quick-action-button {
    padding: 8px;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    justify-content: center;
  }
  
  .dropdown-menu {
    min-width: 200px;
    right: -50%;
    transform: translateX(50%);
  }
  
  .dropdown-badge {
    min-width: 18px;
    height: 18px;
    font-size: 0.65rem;
    padding: 0 4px;
  }
}

@media (max-width: 375px) {
  .avatar-placeholder {
    width: 32px;
    height: 32px;
    font-size: 0.8rem;
  }
  
  .quick-action-button {
    width: 36px;
    height: 36px;
  }
}

/* Print styles */
@media print {
  .top-header {
    display: none !important;
  }
}
</style>