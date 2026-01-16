import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios' // Assume axios for API calls

export const useNotificationStore = defineStore('notification', () => {
  const notifications = ref([])
  const unreadCount = ref(0)

  async function fetchNotifications() {
    try {
      const response = await axios.get('/api/notifications')
      notifications.value = response.data.map(n => ({
        id: n.id,
        type: n.data.type,
        message: n.data.message,
        created_at: n.created_at,
        is_read: !!n.read_at,
        action: n.data.link // For click handling
      }))
      unreadCount.value = notifications.value.filter(n => !n.is_read).length
    } catch (error) {
      console.error('Failed to fetch notifications', error)
    }
  }

  function addNotification(notification) {
    // For realtime (e.g., via Pusher), but for now, manual add
    notifications.value.unshift({
      ...notification,
      id: Date.now() + Math.random(),
      is_read: false
    })
    unreadCount.value++
    // Refresh from API periodically or use websockets
  }

  function removeNotification(id) {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index !== -1) {
      if (!notifications.value[index].is_read) {
        unreadCount.value--
      }
      notifications.value.splice(index, 1)
    }
  }

  async function markAsRead(id) {
    const notification = notifications.value.find(n => n.id === id)
    if (notification && !notification.is_read) {
      try {
        await axios.post(`/api/notifications/${id}/read`)
        notification.is_read = true
        unreadCount.value--
      } catch (error) {
        console.error('Failed to mark as read', error)
      }
    }
  }

  async function markAllAsRead() {
    try {
      await axios.post('/api/notifications/read-all')
      notifications.value.forEach(n => { n.is_read = true })
      unreadCount.value = 0
    } catch (error) {
      console.error('Failed to mark all as read', error)
    }
  }

  function clearAll() {
    notifications.value = []
    unreadCount.value = 0
  }

  return {
    notifications,
    unreadCount,
    fetchNotifications,
    addNotification,
    removeNotification,
    markAsRead,
    markAllAsRead,
    clearAll
  }
})