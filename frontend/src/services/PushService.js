// PushService.js - Frontend service for managing push notifications
import axios from 'axios'

class PushService {
  constructor() {
    this.registration = null
    this.subscription = null
    this.vapidPublicKey = null
  }

  /**
   * Initialize the push service
   */
  async init(vapidPublicKey) {
    this.vapidPublicKey = vapidPublicKey
    
    if (!this.isSupported()) {
      console.warn('Push notifications are not supported in this browser')
      return false
    }

    try {
      // Register service worker
      this.registration = await this.registerServiceWorker()
      console.log('✅ Service Worker registered successfully')
      
      return true
    } catch (error) {
      console.error('❌ Failed to initialize push service:', error)
      return false
    }
  }

  /**
   * Check if push notifications are supported
   */
  isSupported() {
    return (
      'serviceWorker' in navigator &&
      'PushManager' in window &&
      'Notification' in window
    )
  }

  /**
   * Register service worker
   */
  async registerServiceWorker() {
    try {
      const registration = await navigator.serviceWorker.register('/sw.js', {
        scope: '/'
      })

      console.log('Service Worker registered with scope:', registration.scope)

      // Wait for service worker to be ready
      await navigator.serviceWorker.ready

      return registration
    } catch (error) {
      console.error('Service Worker registration failed:', error)
      throw error
    }
  }

  /**
   * Get current notification permission
   */
  getPermission() {
    if (!('Notification' in window)) {
      return 'unsupported'
    }
    return Notification.permission
  }

  /**
   * Request notification permission
   */
  async requestPermission() {
    if (!('Notification' in window)) {
      throw new Error('Notifications are not supported in this browser')
    }

    const permission = await Notification.requestPermission()
    console.log('Notification permission:', permission)
    
    return permission
  }

  /**
   * Subscribe to push notifications
   */
  async subscribe() {
    try {
      // Check permission
      const permission = this.getPermission()
      
      if (permission === 'denied') {
        throw new Error('Notification permission denied')
      }

      if (permission !== 'granted') {
        const newPermission = await this.requestPermission()
        if (newPermission !== 'granted') {
          throw new Error('Notification permission not granted')
        }
      }

      // Ensure service worker is registered
      if (!this.registration) {
        this.registration = await navigator.serviceWorker.ready
      }

      // Check if already subscribed
      let subscription = await this.registration.pushManager.getSubscription()
      
      if (subscription) {
        console.log('Already subscribed to push notifications')
        this.subscription = subscription
      } else {
        // Create new subscription
        subscription = await this.registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: this.urlBase64ToUint8Array(this.vapidPublicKey)
        })
        
        console.log('✅ New push subscription created')
        this.subscription = subscription
      }

      // Send subscription to server
      await this.sendSubscriptionToServer(subscription)

      return subscription
    } catch (error) {
      console.error('❌ Failed to subscribe to push notifications:', error)
      throw error
    }
  }

  /**
   * Unsubscribe from push notifications
   */
  async unsubscribe() {
    try {
      if (!this.registration) {
        this.registration = await navigator.serviceWorker.ready
      }

      const subscription = await this.registration.pushManager.getSubscription()
      
      if (subscription) {
        // Unsubscribe from browser
        await subscription.unsubscribe()
        console.log('✅ Unsubscribed from push notifications')

        // Remove from server
        await this.removeSubscriptionFromServer(subscription)
        
        this.subscription = null
        return true
      }

      return false
    } catch (error) {
      console.error('❌ Failed to unsubscribe from push notifications:', error)
      throw error
    }
  }

  /**
   * Check if currently subscribed
   */
  async isSubscribed() {
    try {
      if (!this.registration) {
        this.registration = await navigator.serviceWorker.ready
      }

      const subscription = await this.registration.pushManager.getSubscription()
      return subscription !== null
    } catch (error) {
      console.error('Failed to check subscription status:', error)
      return false
    }
  }

  /**
   * Get current subscription
   */
  async getSubscription() {
    try {
      if (!this.registration) {
        this.registration = await navigator.serviceWorker.ready
      }

      return await this.registration.pushManager.getSubscription()
    } catch (error) {
      console.error('Failed to get subscription:', error)
      return null
    }
  }

  /**
   * Send subscription to server
   */
  async sendSubscriptionToServer(subscription) {
    try {
      const subscriptionJson = subscription.toJSON()
      
      const response = await axios.post('/api/push/subscribe', {
        endpoint: subscriptionJson.endpoint,
        keys: subscriptionJson.keys,
        device_type: this.getDeviceType(),
        browser: this.getBrowserInfo()
      })

      console.log('✅ Subscription sent to server:', response.data)
      return response.data
    } catch (error) {
      console.error('❌ Failed to send subscription to server:', error)
      throw error
    }
  }

  /**
   * Remove subscription from server
   */
  async removeSubscriptionFromServer(subscription) {
    try {
      const subscriptionJson = subscription.toJSON()
      
      const response = await axios.delete('/api/push/subscribe', {
        data: {
          endpoint: subscriptionJson.endpoint
        }
      })

      console.log('✅ Subscription removed from server:', response.data)
      return response.data
    } catch (error) {
      console.error('❌ Failed to remove subscription from server:', error)
      throw error
    }
  }

  /**
   * Send test notification
   */
  async sendTestNotification() {
    try {
      const response = await axios.post('/api/push/test')
      console.log('✅ Test notification sent:', response.data)
      return response.data
    } catch (error) {
      console.error('❌ Failed to send test notification:', error)
      throw error
    }
  }

  /**
   * Convert VAPID key from base64 to Uint8Array
   */
  urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4)
    const base64 = (base64String + padding)
      .replace(/-/g, '+')
      .replace(/_/g, '/')

    const rawData = window.atob(base64)
    const outputArray = new Uint8Array(rawData.length)

    for (let i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i)
    }

    return outputArray
  }

  /**
   * Get device type
   */
  getDeviceType() {
    const ua = navigator.userAgent
    if (/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i.test(ua)) {
      return 'tablet'
    }
    if (/Mobile|Android|iP(hone|od)|IEMobile|BlackBerry|Kindle|Silk-Accelerated|(hpw|web)OS|Opera M(obi|ini)/.test(ua)) {
      return 'mobile'
    }
    return 'web'
  }

  /**
   * Get browser info
   */
  getBrowserInfo() {
    const ua = navigator.userAgent
    let browserName = 'Unknown'

    if (ua.indexOf('Firefox') > -1) {
      browserName = 'Firefox'
    } else if (ua.indexOf('SamsungBrowser') > -1) {
      browserName = 'Samsung Internet'
    } else if (ua.indexOf('Opera') > -1 || ua.indexOf('OPR') > -1) {
      browserName = 'Opera'
    } else if (ua.indexOf('Trident') > -1) {
      browserName = 'Internet Explorer'
    } else if (ua.indexOf('Edge') > -1) {
      browserName = 'Edge'
    } else if (ua.indexOf('Chrome') > -1) {
      browserName = 'Chrome'
    } else if (ua.indexOf('Safari') > -1) {
      browserName = 'Safari'
    }

    return browserName
  }

  /**
   * Show local notification (for testing)
   */
  async showLocalNotification(title, options = {}) {
    if (!this.registration) {
      throw new Error('Service worker not registered')
    }

    await this.registration.showNotification(title, {
      body: options.body || 'This is a test notification',
      icon: options.icon || '/images/icons/default.png',
      badge: options.badge || '/images/icons/badge.png',
      vibrate: [200, 100, 200],
      data: options.data || {},
      ...options
    })
  }
}

// Export singleton instance
export default new PushService()