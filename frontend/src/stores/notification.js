import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export const useNotificationStore = defineStore('notification', () => {
  const notifications = ref([])
  const unreadCount = ref(0)
  const loading = ref(false)
  
  // Configure axios base URL if needed
  const api = axios.create({
    baseURL: '/api', // Adjust based on your setup
    withCredentials: true, // Important for Sanctum
  })
  
  // Fetch notifications from API
  async function fetchNotifications() {
    loading.value = true
    try {
      const response = await api.get('/notifications')
      notifications.value = response.data.notifications || []
      updateUnreadCount()
      
      console.log('✅ Notifications fetched:', notifications.value.length)
      return notifications.value
    } catch (error) {
      console.error('❌ Failed to fetch notifications:', error.response?.data || error.message)
      notifications.value = []
      throw error
    } finally {
      loading.value = false
    }
  }
  
  // Fetch unread count only
  async function fetchUnreadCount() {
    try {
      const response = await api.get('/notifications/unread-count')
      unreadCount.value = response.data.count || 0
      console.log('✅ Unread count:', unreadCount.value)
      return unreadCount.value
    } catch (error) {
      console.error('❌ Failed to fetch unread count:', error.response?.data || error.message)
      unreadCount.value = 0
    }
  }
  
  // Update local unread count
  function updateUnreadCount() {
    unreadCount.value = notifications.value.filter(n => !n.is_read).length
  }
  
  // Add notification (for real-time updates via WebSocket/Pusher)
  function addNotification(notification) {
    notifications.value.unshift({
      ...notification,
      is_read: false
    })
    unreadCount.value++
    console.log('✅ Notification added:', notification)
  }
  
  // Mark single notification as read
  async function markAsRead(id) {
    const notification = notifications.value.find(n => n.id === id)
    if (notification && !notification.is_read) {
      try {
        await api.post(`/notifications/${id}/read`)
        notification.is_read = true
        notification.read_at = new Date().toISOString()
        unreadCount.value = Math.max(0, unreadCount.value - 1)
        
        console.log('✅ Notification marked as read:', id)
      } catch (error) {
        console.error('❌ Failed to mark as read:', error.response?.data || error.message)
        throw error
      }
    }
  }
  
  // Mark all notifications as read
  async function markAllAsRead() {
    try {
      const response = await api.post('/notifications/read-all')
      notifications.value.forEach(n => { 
        n.is_read = true 
        n.read_at = new Date().toISOString()
      })
      unreadCount.value = 0
      
      console.log('✅ All notifications marked as read. Count:', response.data.count)
    } catch (error) {
      console.error('❌ Failed to mark all as read:', error.response?.data || error.message)
      throw error
    }
  }
  
  // Remove notification from local state
  function removeNotification(id) {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index !== -1) {
      if (!notifications.value[index].is_read) {
        unreadCount.value = Math.max(0, unreadCount.value - 1)
      }
      notifications.value.splice(index, 1)
      console.log('✅ Notification removed:', id)
    }
  }
  
  // Clear all notifications from local state
  function clearAll() {
    notifications.value = []
    unreadCount.value = 0
    console.log('✅ All notifications cleared')
  }
  
  return {
    notifications,
    unreadCount,
    loading,
    fetchNotifications,
    fetchUnreadCount,
    addNotification,
    removeNotification,
    markAsRead,
    markAllAsRead,
    clearAll,
    updateUnreadCount
  }
})