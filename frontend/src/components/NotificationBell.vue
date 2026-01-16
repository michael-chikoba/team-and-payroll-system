<template>
  <div class="notification-bell">
    <button 
      @click="togglePanel" 
      class="bell-button"
      :class="{ 'has-unread': unreadCount > 0 }"
    >
      <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
      </svg>
      <span v-if="unreadCount > 0" class="badge">
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>
    
    <NotificationPanel 
      v-if="showPanel"
      :notifications="notifications"
      @close="showPanel = false"
      @mark-all-read="handleMarkAllRead"
    />
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useNotificationStore } from '@/stores/notification'
import NotificationPanel from './NotificationPanel.vue'

export default {
  name: 'NotificationBell',
  components: {
    NotificationPanel
  },
  setup() {
    const store = useNotificationStore()
    const showPanel = ref(false)
    let pollInterval = null
    
    const notifications = computed(() => store.notifications)
    const unreadCount = computed(() => store.unreadCount)
    
    const togglePanel = () => {
      showPanel.value = !showPanel.value
      if (showPanel.value) {
        store.fetchNotifications()
      }
    }
    
    const handleMarkAllRead = () => {
      store.markAllAsRead()
    }
    
    // Poll for new notifications every 30 seconds
    const startPolling = () => {
      store.fetchUnreadCount() // Initial fetch
      pollInterval = setInterval(() => {
        store.fetchUnreadCount()
      }, 30000) // 30 seconds
    }
    
    const stopPolling = () => {
      if (pollInterval) {
        clearInterval(pollInterval)
        pollInterval = null
      }
    }
    
    onMounted(() => {
      startPolling()
    })
    
    onUnmounted(() => {
      stopPolling()
    })
    
    return {
      showPanel,
      notifications,
      unreadCount,
      togglePanel,
      handleMarkAllRead
    }
  }
}
</script>

<style scoped>
.notification-bell {
  position: relative;
}

.bell-button {
  position: relative;
  padding: 0.5rem;
  border: none;
  background: transparent;
  cursor: pointer;
  border-radius: 8px;
  transition: background-color 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
}

.bell-button:hover {
  background: #f3f4f6;
}

.bell-button.has-unread {
  color: #3b82f6;
}

.badge {
  position: absolute;
  top: 4px;
  right: 4px;
  background: #ef4444;
  color: white;
  font-size: 0.625rem;
  font-weight: 600;
  padding: 0.125rem 0.375rem;
  border-radius: 10px;
  min-width: 18px;
  text-align: center;
  line-height: 1.2;
}
</style>