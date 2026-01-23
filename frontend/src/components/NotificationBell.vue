<template>
  <div class="notification-bell-container">
    <button 
      @click="togglePanel" 
      class="notification-button"
      :class="{ 'has-unread': unreadCount > 0 }"
      title="Notifications"
    >
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
      </svg>
      <span v-if="unreadCount > 0" class="notification-count">
        {{ displayCount }}
      </span>
    </button>
    
    <NotificationPanel 
      v-if="showPanel"
      :notifications="notifications"
      :loading="loading"
      @close="showPanel = false"
      @mark-all-read="handleMarkAllRead"
      @mark-as-read="handleMarkAsRead"
      @delete-notification="handleDeleteNotification"
    />
  </div>
</template>

<script>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useNotificationStore } from '@/stores/notification'
import { useAuthStore } from '@/stores/auth'
import NotificationPanel from './NotificationPanel.vue'

export default {
  name: 'NotificationBell',
  components: {
    NotificationPanel
  },
  setup() {
    const store = useNotificationStore()
    const authStore = useAuthStore()
    
    const showPanel = ref(false)
    const loading = ref(false)
    let pollInterval = null
    let isComponentActive = ref(true)
    
    const notifications = computed(() => store.notifications)
    const unreadCount = computed(() => store.unreadCount)
    
    const displayCount = computed(() => {
      return unreadCount.value > 99 ? '99+' : unreadCount.value
    })
    
    const togglePanel = async () => {
      if (!isComponentActive.value || authStore.isLoggingOut || !authStore.isAuthenticated) {
        return
      }
      
      showPanel.value = !showPanel.value
      if (showPanel.value) {
        await fetchNotifications()
      }
    }
    
    const fetchUnreadCount = async () => {
      if (!isComponentActive.value || authStore.isLoggingOut || !authStore.isAuthenticated) {
        return
      }
      
      try {
        await store.fetchUnreadCount()
      } catch (error) {
        // Only log error if not a 401 (unauthenticated) and not logging out
        if (error.response?.status !== 401 && !authStore.isLoggingOut) {
          console.error('❌ Failed to fetch unread count:', error.response?.data)
        }
      }
    }
    
    const fetchNotifications = async () => {
      if (!isComponentActive.value || authStore.isLoggingOut || !authStore.isAuthenticated) {
        return
      }
      
      try {
        loading.value = true
        await store.fetchNotifications()
      } catch (error) {
        if (error.response?.status !== 401 && !authStore.isLoggingOut) {
          console.error('Failed to fetch notifications:', error)
        }
      } finally {
        loading.value = false
      }
    }
    
    const handleMarkAllRead = async () => {
      if (!isComponentActive.value || authStore.isLoggingOut) return
      
      try {
        await store.markAllAsRead()
      } catch (error) {
        if (!authStore.isLoggingOut) {
          console.error('Failed to mark all as read:', error)
        }
      }
    }
    
    const handleMarkAsRead = async (id) => {
      if (!isComponentActive.value || authStore.isLoggingOut) return
      
      try {
        await store.markAsRead(id)
      } catch (error) {
        if (!authStore.isLoggingOut) {
          console.error('Failed to mark notification as read:', error)
        }
      }
    }
    
    const handleDeleteNotification = async (id) => {
      if (!isComponentActive.value || authStore.isLoggingOut) return
      
      try {
        await store.deleteNotification(id)
      } catch (error) {
        if (!authStore.isLoggingOut) {
          console.error('Failed to delete notification:', error)
        }
      }
    }
    
    const cleanup = () => {
      console.log('🧹 Cleaning up NotificationBell component')
      isComponentActive.value = false
      
      if (pollInterval) {
        clearInterval(pollInterval)
        pollInterval = null
      }
      
      showPanel.value = false
    }
    
    const handleLogout = () => {
      cleanup()
    }
    
    const startPolling = () => {
      if (!authStore.isAuthenticated) return
      
      // Initial fetch
      fetchUnreadCount()
      
      // Poll every 30 seconds
      pollInterval = setInterval(() => {
        if (isComponentActive.value && authStore.isAuthenticated && !authStore.isLoggingOut) {
          fetchUnreadCount()
        }
      }, 30000)
    }
    
    onMounted(() => {
      console.log('📢 NotificationBell mounted')
      isComponentActive.value = true
      
      // Listen for logout event
      window.addEventListener('user-logging-out', handleLogout)
      
      startPolling()
    })
    
    onBeforeUnmount(() => {
      console.log('📢 NotificationBell unmounting')
      cleanup()
      window.removeEventListener('user-logging-out', handleLogout)
    })
    
    return {
      showPanel,
      notifications,
      unreadCount,
      displayCount,
      loading,
      togglePanel,
      handleMarkAllRead,
      handleMarkAsRead,
      handleDeleteNotification
    }
  }
}
</script>

<style scoped>
.notification-bell-container {
  position: relative;
}

.notification-button {
  position: relative;
  background: transparent;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.5rem;
  border-radius: 8px;
  color: #6b7280;
}

.notification-button:hover {
  background: #f3f4f6;
}

.notification-button.has-unread {
  color: #3b82f6;
}

.notification-count {
  position: absolute;
  top: -5px;
  right: -5px;
  background-color: #ef4444;
  color: white;
  border-radius: 10px;
  min-width: 20px;
  height: 20px;
  font-size: 0.625rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px solid white;
  padding: 0 4px;
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

/* Dropdown animation for panel */
.dropdown-enter-active,
.dropdown-leave-active {
  transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

@media (max-width: 576px) {
  .notification-button {
    padding: 0.375rem;
  }
}
</style>