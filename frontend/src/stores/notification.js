import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useNotificationStore = defineStore('notification', () => {
  const notifications = ref([])
  const unreadCount = ref(0)

  function addNotification(notification) {
    const id = Date.now() + Math.random()
    const newNotification = {
      id,
      type: notification.type || 'info',
      title: notification.title,
      message: notification.message,
      timestamp: new Date(),
      read: false,
      action: notification.action,
      duration: notification.duration || 5000
    }

    notifications.value.unshift(newNotification)
    unreadCount.value++

    // Auto remove after duration
    if (newNotification.duration > 0) {
      setTimeout(() => {
        removeNotification(id)
      }, newNotification.duration)
    }

    return id
  }

  function removeNotification(id) {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index !== -1) {
      if (!notifications.value[index].read) {
        unreadCount.value--
      }
      notifications.value.splice(index, 1)
    }
  }

  function markAsRead(id) {
    const notification = notifications.value.find(n => n.id === id)
    if (notification && !notification.read) {
      notification.read = true
      unreadCount.value--
    }
  }

  function markAllAsRead() {
    notifications.value.forEach(notification => {
      if (!notification.read) {
        notification.read = true
      }
    })
    unreadCount.value = 0
  }

  function clearAll() {
    notifications.value = []
    unreadCount.value = 0
  }

  function getUnreadNotifications() {
    return notifications.value.filter(n => !n.read)
  }

  return {
    notifications,
    unreadCount,
    addNotification,
    removeNotification,
    markAsRead,
    markAllAsRead,
    clearAll,
    getUnreadNotifications
  }
})