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
        
        <!-- Add Chat Navigation Link -->
        <a href="#" class="nav-link" @click.prevent="openChatModal" :class="{ 'active': showChatModal }">
          <span class="link-icon">💬</span>
          <span class="link-text">Chat</span>
          <span v-if="unreadCount > 0 && !showChatModal" class="nav-badge">{{ unreadCount }}</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <!-- Optional Footer content -->
      </div>
    </aside>

    <div class="content-area">
      <header class="top-bar">
        <h2 class="page-title">Manager Dashboard</h2>
        
        <!-- Quick Action Buttons including Chat Button -->
        <div class="quick-actions">
          <button 
            @click="openChatModal" 
            class="quick-action-button"
            :title="showChatModal ? 'Close Chat' : 'Open Chat'"
          >
            <span class="quick-action-icon">💬</span>
            <span class="quick-action-text">Chat</span>
            <span v-if="unreadCount > 0 && !showChatModal" class="notification-badge">{{ unreadCount }}</span>
          </button>
          
          <button 
            @click="openNotifications" 
            class="quick-action-button"
            title="Notifications"
          >
            <span class="quick-action-icon">🔔</span>
            <span class="quick-action-text">Notifications</span>
            <span class="notification-badge">5</span>
          </button>
        </div>
        
        <!-- Profile Dropdown -->
        <div class="profile-dropdown">
          <button class="profile-trigger" @click="toggleProfileDropdown">
            <span class="user-avatar">{{ getInitials() }}</span>
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
      </header>
      
      <main class="main">
        <router-view />
        
        <!-- Chat Modal -->
        <transition name="modal">
          <div v-if="showChatModal" class="modal-overlay" @click.self="closeChatModal">
            <div class="modal-container">
              <div class="modal-header">
                <h3>💬 Team Chat</h3>
                <button @click="closeChatModal" class="modal-close-btn" title="Close Chat">×</button>
              </div>
              <div class="modal-content">
                <ChatInterface 
                  ref="chatInterface"
                  @unread-count="updateUnreadCount"
                  :key="chatKey"
                />
              </div>
              <div class="modal-footer">
                <button @click="closeChatModal" class="btn-secondary">
                  Close Chat
                </button>
              </div>
            </div>
          </div>
        </transition>
      </main>
    </div>
  </div>
</template>

<script>
import { defineComponent, ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import AttendanceToggle from '../components/common/Toggle.vue'
import ChatInterface from '@/components/ChatInterface.vue'
import { useAuthStore } from '../stores/auth'

export default defineComponent({
  name: 'ManagerLayout',
  components: {
    AttendanceToggle,
    ChatInterface
  },
  setup() {
    const router = useRouter()
    const authStore = useAuthStore()
    
    const showProfileDropdown = ref(false)
    const showChatModal = ref(false)
    const unreadCount = ref(0)
    const chatInterface = ref(null)
    const chatKey = ref(0)
    
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
      // Reset unread count when opening chat
      unreadCount.value = 0
      // Force re-render chat interface to ensure fresh state
      chatKey.value++
    }
    
    const closeChatModal = () => {
      showChatModal.value = false
    }
    
    const updateUnreadCount = (count) => {
      if (!showChatModal.value) {
        unreadCount.value = count
      }
    }
    
    const openNotifications = () => {
      // Implement notification functionality
      alert('Notifications feature coming soon!')
    }
    
    const getInitials = () => {
      const name = authStore.user?.fullName || 'Manager'
      return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2)
    }
    
    const handleClickOutside = (event) => {
      const dropdown = document.querySelector('.profile-dropdown')
      if (dropdown && !dropdown.contains(event.target)) {
        showProfileDropdown.value = false
      }
    }
    
    const handleEscapeKey = (event) => {
      if (event.key === 'Escape') {
        if (showProfileDropdown.value) {
          showProfileDropdown.value = false
        }
        if (showChatModal.value) {
          showChatModal.value = false
        }
      }
    }
    
    onMounted(() => {
      document.addEventListener('click', handleClickOutside)
      document.addEventListener('keydown', handleEscapeKey)
    })
    
    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
      document.removeEventListener('keydown', handleEscapeKey)
    })
    
    return {
      showProfileDropdown,
      showChatModal,
      unreadCount,
      chatInterface,
      chatKey,
      toggleProfileDropdown,
      closeProfileDropdown,
      handleLogout,
      openChatModal,
      closeChatModal,
      updateUnreadCount,
      openNotifications,
      getInitials,
      authStore
    }
  }
})
</script>

<style scoped>
:root {
  --primary-color: #007bff;
  --primary-dark: #0056b3;
  --background-color: #f4f7f9;
  --sidebar-dark-blue: #001f5b;
  --sidebar-text-color: #ffffff;
  --text-color: #343a40;
  --text-light: #6c757d;
  --border-color: #e9ecef;
  --logout-color: #dc3545;
  --dropdown-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  --chat-bg: #ffffff;
  --chat-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
  --success-color: #28a745;
  --danger-color: #dc3545;
  --warning-color: #ffc107;
}

/* LAYOUT */
.modern-manager-layout {
  display: flex;
  min-height: 100vh;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background-color: var(--background-color);
  position: relative;
  overflow-x: hidden;
}

/* SIDEBAR */
.sidebar {
  width: 240px;
  background-color: var(--sidebar-dark-blue);
  box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
  padding: 1.5rem 1rem;
  display: flex;
  flex-direction: column;
  height: 100vh;
  position: sticky;
  top: 0;
  flex-shrink: 0;
  z-index: 1000;
}

.logo-section {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 1rem;
  color: var(--primary-color);
  flex-shrink: 0;
}

.logo-icon {
  font-size: 1.8rem;
}

.title {
  margin: 0;
  font-size: 1.4rem;
  font-weight: 600;
  color: rgb(27, 97, 154) !important;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}

.attendance-toggle-section {
  margin-bottom: 1.5rem;
  padding: 0 0.25rem;
  flex-shrink: 0;
}

.nav {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  flex-grow: 1;
  overflow-y: auto;
  padding-right: 5px;
}

.nav::-webkit-scrollbar {
  width: 4px;
}

.nav::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.05);
}

.nav::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.2);
  border-radius: 4px;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  color: var(--sidebar-text-color) !important;
  text-decoration: none;
  padding: 1rem 1.25rem;
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.2s ease;
  font-size: 0.95rem;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
  flex-shrink: 0;
  position: relative;
}

.nav-link:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

.nav-link.active {
  background-color: var(--primary-color);
  color: rgb(68, 31, 142) !important;
  box-shadow: 0 4px 8px rgba(0, 123, 255, 0.2);
  font-weight: 600;
}

.nav-link.active .link-icon {
  color: white;
}

.link-icon {
  font-size: 1.1rem;
  color: var(--sidebar-text-color);
  min-width: 24px;
  text-align: center;
}

/* Navigation badge */
.nav-badge {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  background-color: var(--danger-color);
  color: white;
  border-radius: 50%;
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  font-weight: bold;
  animation: pulse 1.5s infinite;
}

.sidebar-footer {
  padding-top: 1rem;
  flex-shrink: 0;
}

/* CONTENT AREA */
.content-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  height: 100vh;
  overflow: hidden;
  position: relative;
}

.top-bar {
  background-color: white;
  padding: 1.5rem 3rem;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.03);
  flex-shrink: 0;
  z-index: 50;
  position: sticky;
  top: 0;
  gap: 20px;
}

.page-title {
  margin: 0;
  font-size: 1.8rem;
  font-weight: 300;
  color: var(--text-color);
  flex: 1;
}

.quick-actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.quick-action-button {
  display: flex;
  align-items: center;
  gap: 8px;
  background: white;
  border: 1px solid var(--border-color);
  border-radius: 8px;
  padding: 8px 16px;
  font-size: 0.9rem;
  font-weight: 500;
  color: var(--text-color);
  cursor: pointer;
  transition: all 0.2s ease;
  position: relative;
}

.quick-action-button:hover {
  background-color: #f8f9fa;
  border-color: var(--primary-color);
  color: var(--primary-color);
}

.quick-action-icon {
  font-size: 1rem;
}

.quick-action-text {
  white-space: nowrap;
}

.notification-badge {
  position: absolute;
  top: -5px;
  right: -5px;
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

.main {
  flex: 1;
  padding: 2rem 3rem;
  overflow-y: auto;
}

/* PROFILE DROPDOWN */
.profile-dropdown {
  position: relative;
  display: inline-block;
}

.profile-trigger {
  display: flex;
  align-items: center;
  gap: 10px;
  background: none;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  cursor: pointer;
  color: var(--text-color);
  transition: background-color 0.2s;
}

.profile-trigger:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

.profile-dropdown-menu {
  position: absolute;
  top: 100%;
  right: 0;
  background: white;
  border-radius: 8px;
  box-shadow: var(--dropdown-shadow);
  padding: 0.5rem 0;
  min-width: 200px;
  z-index: 1000;
  margin-top: 0.5rem;
  border: 1px solid var(--border-color);
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 0.75rem 1rem;
  text-decoration: none;
  color: var(--text-color);
  width: 100%;
  text-align: left;
  cursor: pointer;
  background: none;
  border: none;
  transition: background-color 0.2s;
  font-size: 0.95rem;
}

.dropdown-item:hover {
  background-color: rgba(0, 123, 255, 0.1);
}

.dropdown-divider {
  height: 1px;
  background-color: var(--border-color);
  margin: 0.25rem 0;
}

.logout-dropdown-item:hover {
  background-color: rgba(220, 53, 69, 0.1);
  color: var(--danger-color);
}

.user-avatar {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  background: linear-gradient(135deg, #50e3c2, #4299e1);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* MODAL STYLES */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  backdrop-filter: blur(3px);
}

.modal-container {
  background-color: white;
  border-radius: 16px;
  width: 90%;
  max-width: 900px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: var(--chat-shadow);
}

.modal-header {
  padding: 1.5rem 2rem;
  background: var(--primary-color);
  color: white;
  display: flex;
  justify-content: space-between;
  align-items: center;
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
  padding: 0;
  overflow: hidden;
  flex: 1;
  min-height: 500px;
}

.modal-footer {
  padding: 1.5rem 2rem;
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}

.btn-secondary {
  background-color: #f8f9fa;
  color: var(--text-color);
  border: 1px solid var(--border-color);
  border-radius: 8px;
  padding: 10px 20px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-secondary:hover {
  background-color: #e9ecef;
}

/* Style the ChatInterface component */
.modal-content :deep(.chat-interface) {
  height: 100%;
  width: 100%;
}

.modal-content :deep(.chat-container) {
  height: 100%;
  width: 100%;
  border-radius: 0;
  border: none;
}

/* Modal Transitions */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .modal-container,
.modal-leave-active .modal-container {
  transition: transform 0.3s ease;
}

.modal-enter-from .modal-container,
.modal-leave-to .modal-container {
  transform: scale(0.95);
}

@keyframes pulse {
  0% {
    transform: scale(1);
    box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
  }
  70% {
    transform: scale(1.1);
    box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
  }
  100% {
    transform: scale(1);
    box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
  }
}

/* RESPONSIVE DESIGN */
@media (max-width: 1024px) {
  .modal-container {
    width: 95%;
    max-width: 95%;
  }
}

@media (max-width: 768px) {
  .sidebar {
    width: 70px;
    padding: 1rem 0.5rem;
  }
  
  .title, .user-name, .link-text, .quick-action-text {
    display: none;
  }
  
  .logo-section {
    justify-content: center;
  }
  
  .nav-link {
    justify-content: center;
    padding: 0.75rem;
  }

  .nav-link .link-text {
    display: none;
  }
  
  .nav-badge {
    position: absolute;
    top: 5px;
    right: 5px;
    transform: none;
  }
  
  .main {
    padding: 1.5rem;
  }
  
  .top-bar {
    padding: 1rem 1.5rem;
    flex-wrap: wrap;
    gap: 10px;
  }
  
  .page-title {
    font-size: 1.5rem;
    order: 1;
    flex: 100%;
    margin-bottom: 10px;
  }
  
  .quick-actions {
    order: 2;
  }
  
  .profile-dropdown {
    order: 3;
  }
  
  .modal-container {
    width: 100%;
    height: 100%;
    max-height: 100%;
    border-radius: 0;
    margin: 0;
  }
  
  .modal-header {
    border-radius: 0;
  }
  
  .notification-badge {
    min-width: 18px;
    height: 18px;
    font-size: 0.65rem;
  }
}

@media (max-width: 480px) {
  .top-bar {
    padding: 1rem;
  }
  
  .main {
    padding: 1rem;
  }
  
  .quick-action-button {
    padding: 6px 12px;
  }
  
  .modal-container {
    width: 100%;
    height: 100%;
  }
}
</style>