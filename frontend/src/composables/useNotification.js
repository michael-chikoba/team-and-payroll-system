import { ref } from 'vue'

// Named export
export const useNotification = () => {
  const notifications = ref([])
  
  const addNotification = (message, type = 'info') => {
    const id = Date.now()
    notifications.value.push({
      id,
      message,
      type,
      timestamp: new Date()
    })
    
    // Auto remove after 5 seconds
    setTimeout(() => {
      const index = notifications.value.findIndex(n => n.id === id)
      if (index > -1) {
        notifications.value.splice(index, 1)
      }
    }, 5000)
  }
  
  const removeNotification = (id) => {
    const index = notifications.value.findIndex(n => n.id === id)
    if (index > -1) {
      notifications.value.splice(index, 1)
    }
  }
  
  const clearAll = () => {
    notifications.value = []
  }
  
  return {
    notifications,
    addNotification,
    removeNotification,
    clearAll
  }
}

// Default export for compatibility
export default useNotification
