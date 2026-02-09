// usePushNotifications.js - Vue composable for push notifications
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import PushService from '@/services/PushService'
import { useAuthStore } from '@/stores/auth'

export function usePushNotifications() {
  const authStore = useAuthStore()
  
  const isSupported = ref(false)
  const permission = ref('default')
  const isSubscribed = ref(false)
  const isLoading = ref(false)
  const error = ref(null)

  // VAPID public key - get this from your backend
  const VAPID_PUBLIC_KEY = import.meta.env.VITE_VAPID_PUBLIC_KEY || ''

  /**
   * Initialize push notifications
   */
  const init = async () => {
    if (!authStore.isAuthenticated) {
      console.log('User not authenticated, skipping push init')
      return
    }

    isSupported.value = PushService.isSupported()
    
    if (!isSupported.value) {
      console.warn('Push notifications not supported')
      return
    }

    try {
      await PushService.init(VAPID_PUBLIC_KEY)
      permission.value = PushService.getPermission()
      isSubscribed.value = await PushService.isSubscribed()
      
      console.log('✅ Push notifications initialized', {
        supported: isSupported.value,
        permission: permission.value,
        subscribed: isSubscribed.value
      })
    } catch (err) {
      console.error('Failed to initialize push notifications:', err)
      error.value = err.message
    }
  }

  /**
   * Request notification permission
   */
  const requestPermission = async () => {
    try {
      isLoading.value = true
      error.value = null
      
      const newPermission = await PushService.requestPermission()
      permission.value = newPermission
      
      if (newPermission === 'granted') {
        console.log('✅ Notification permission granted')
        return true
      } else {
        console.log('❌ Notification permission denied')
        return false
      }
    } catch (err) {
      console.error('Failed to request permission:', err)
      error.value = err.message
      return false
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Subscribe to push notifications
   */
  const subscribe = async () => {
    try {
      isLoading.value = true
      error.value = null
      
      const subscription = await PushService.subscribe()
      isSubscribed.value = true
      permission.value = PushService.getPermission()
      
      console.log('✅ Successfully subscribed to push notifications')
      return subscription
    } catch (err) {
      console.error('Failed to subscribe:', err)
      error.value = err.message
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Unsubscribe from push notifications
   */
  const unsubscribe = async () => {
    try {
      isLoading.value = true
      error.value = null
      
      await PushService.unsubscribe()
      isSubscribed.value = false
      
      console.log('✅ Successfully unsubscribed from push notifications')
      return true
    } catch (err) {
      console.error('Failed to unsubscribe:', err)
      error.value = err.message
      throw err
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Toggle push notification subscription
   */
  const toggleSubscription = async () => {
    if (isSubscribed.value) {
      return await unsubscribe()
    } else {
      return await subscribe()
    }
  }

  /**
   * Send test notification
   */
  const sendTestNotification = async () => {
    try {
      isLoading.value = true
      error.value = null
      
      await PushService.sendTestNotification()
      console.log('✅ Test notification sent')
      return true
    } catch (err) {
      console.error('Failed to send test notification:', err)
      error.value = err.message
      return false
    } finally {
      isLoading.value = false
    }
  }

  /**
   * Check subscription status
   */
  const checkSubscription = async () => {
    try {
      isSubscribed.value = await PushService.isSubscribed()
      return isSubscribed.value
    } catch (err) {
      console.error('Failed to check subscription:', err)
      return false
    }
  }

  // Computed properties
  const canSubscribe = computed(() => {
    return isSupported.value && permission.value === 'granted' && !isSubscribed.value
  })

  const canRequestPermission = computed(() => {
    return isSupported.value && permission.value === 'default'
  })

  const permissionDenied = computed(() => {
    return permission.value === 'denied'
  })

  // Auto-initialize on mount if user is authenticated
  onMounted(() => {
    if (authStore.isAuthenticated) {
      init()
    }
  })

  // Cleanup on unmount
  onBeforeUnmount(() => {
    // Any cleanup needed
  })

  return {
    // State
    isSupported,
    permission,
    isSubscribed,
    isLoading,
    error,
    
    // Computed
    canSubscribe,
    canRequestPermission,
    permissionDenied,
    
    // Methods
    init,
    requestPermission,
    subscribe,
    unsubscribe,
    toggleSubscription,
    sendTestNotification,
    checkSubscription
  }
}