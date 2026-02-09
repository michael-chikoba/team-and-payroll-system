/* eslint-disable no-undef */
// Service Worker for Push Notifications
const CACHE_VERSION = 'v1.0.1'
const NOTIFICATION_CACHE = 'notification-cache-' + CACHE_VERSION

console.log('🔧 Service Worker script loaded')

// Install event
self.addEventListener('install', (event) => {
  console.log('📦 Service Worker installing...')
  // Skip waiting to activate immediately
  self.skipWaiting()
})

// Activate event
self.addEventListener('activate', (event) => {
  console.log('✅ Service Worker activating...')
  event.waitUntil(
    Promise.all([
      // Clean up old caches
      caches.keys().then((cacheNames) => {
        return Promise.all(
          cacheNames.map((cacheName) => {
            if (cacheName !== NOTIFICATION_CACHE) {
              console.log('🗑️ Deleting old cache:', cacheName)
              return caches.delete(cacheName)
            }
          })
        )
      }),
      // Take control of all clients immediately
      self.clients.claim()
    ])
  )
  console.log('✅ Service Worker activated and claimed clients')
})

// Push event - handle incoming push notifications
self.addEventListener('push', (event) => {
  console.log('📬 Push notification received:', event)

  let notificationData = {
    title: 'Notification',
    body: 'You have a new notification',
    icon: '/images/icons/default.png',
    badge: '/images/icons/badge.png',
    data: {}
  }

  // Parse the payload
  if (event.data) {
    try {
      notificationData = event.data.json()
      console.log('📨 Parsed notification data:', notificationData)
    } catch (e) {
      console.error('❌ Error parsing push data:', e)
      notificationData.body = event.data.text()
    }
  }

  const options = {
    body: notificationData.body || notificationData.message,
    icon: notificationData.icon || '/favicon.ico',
    badge: notificationData.badge || '/favicon.ico',
    vibrate: [200, 100, 200],
    tag: notificationData.data?.id || 'notification-' + Date.now(),
    data: {
      url: notificationData.action || notificationData.data?.url || '/notifications',
      id: notificationData.data?.id,
      ...notificationData.data
    },
    requireInteraction: notificationData.requireInteraction || false,
    actions: notificationData.actions || [],
    timestamp: Date.now()
  }

  event.waitUntil(
    self.registration.showNotification(notificationData.title, options)
      .then(() => {
        console.log('✅ Notification shown successfully')
        // Notify all clients that a push was received
        return self.clients.matchAll({ type: 'window' })
      })
      .then((clients) => {
        clients.forEach((client) => {
          client.postMessage({
            type: 'PUSH_RECEIVED',
            notification: notificationData
          })
        })
      })
      .catch((error) => {
        console.error('❌ Error showing notification:', error)
      })
  )
})

// Notification click event
self.addEventListener('notificationclick', (event) => {
  console.log('🖱️ Notification clicked:', event)
  
  event.notification.close()

  const urlToOpen = event.notification.data?.url || '/notifications'
  const notificationId = event.notification.data?.id
  const action = event.action

  console.log('📍 Opening URL:', urlToOpen)

  // Handle notification actions
  if (action) {
    console.log('🎯 Notification action clicked:', action)
    event.waitUntil(handleNotificationAction(event, action, notificationId))
    return
  }

  // Default click behavior - open the URL
  event.waitUntil(
    clients.matchAll({ 
      type: 'window', 
      includeUncontrolled: true 
    })
      .then((clientList) => {
        console.log('🔍 Found clients:', clientList.length)
        
        // Check if there's already a window open with this URL
        for (let i = 0; i < clientList.length; i++) {
          const client = clientList[i]
          const clientUrl = new URL(client.url)
          const targetUrl = new URL(urlToOpen, self.location.origin)
          
          // If URLs match, focus that window
          if (clientUrl.pathname === targetUrl.pathname && 'focus' in client) {
            console.log('✨ Focusing existing window')
            return client.focus().then(() => {
              // Post message to client to refresh notifications
              client.postMessage({
                type: 'NOTIFICATION_CLICKED',
                notificationId: notificationId
              })
              return client
            })
          }
        }
        
        // If no matching window, try to focus any client and navigate it
        if (clientList.length > 0 && 'navigate' in clientList[0]) {
          console.log('🔄 Navigating existing window')
          return clientList[0].focus().then((client) => {
            return client.navigate(urlToOpen)
          })
        }
        
        // If no window is open, open a new one
        if (clients.openWindow) {
          console.log('🆕 Opening new window')
          return clients.openWindow(urlToOpen)
        }
      })
      .then((client) => {
        // Mark notification as read
        if (notificationId && client) {
          markNotificationAsRead(notificationId)
        }
      })
      .catch((error) => {
        console.error('❌ Error handling notification click:', error)
      })
  )
})

// Handle notification actions (like Accept/Decline for invitations)
async function handleNotificationAction(event, action, notificationId) {
  console.log('🎯 Handling action:', action, 'for notification:', notificationId)
  
  const actionUrls = {
    'accept': '/api/invitations/accept',
    'decline': '/api/invitations/decline',
    'approve': '/api/leave/approve',
    'reject': '/api/leave/reject',
    'view': '/notifications'
  }

  const actionUrl = actionUrls[action]
  
  if (!actionUrl) {
    console.warn('⚠️ Unknown action:', action)
    return
  }

  if (actionUrl.startsWith('/api/')) {
    // Handle API actions
    try {
      const response = await fetch(actionUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ 
          notification_id: notificationId 
        }),
        credentials: 'include'
      })

      const data = await response.json()
      console.log('✅ Action completed:', data)

      // Show a new notification with the result
      await self.registration.showNotification('Action Completed', {
        body: data.message || 'Your action was successful',
        icon: '/favicon.ico',
        tag: 'action-result-' + Date.now(),
        requireInteraction: false
      })

      // Notify all clients to refresh
      const clients = await self.clients.matchAll({ type: 'window' })
      clients.forEach((client) => {
        client.postMessage({
          type: 'ACTION_COMPLETED',
          action: action,
          result: data
        })
      })
    } catch (error) {
      console.error('❌ Action failed:', error)
      await self.registration.showNotification('Action Failed', {
        body: 'Sorry, something went wrong. Please try again.',
        icon: '/favicon.ico',
        tag: 'action-error-' + Date.now()
      })
    }
  } else {
    // Navigate to page
    await clients.openWindow(actionUrl)
  }
}

// Mark notification as read
function markNotificationAsRead(notificationId) {
  if (!notificationId) return

  console.log('📖 Marking notification as read:', notificationId)
  
  fetch(`/api/notifications/${notificationId}/read`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json'
    },
    credentials: 'include'
  })
    .then((response) => {
      if (!response.ok) throw new Error('Failed to mark as read')
      return response.json()
    })
    .then((data) => {
      console.log('✅ Notification marked as read:', data)
    })
    .catch((error) => {
      console.error('❌ Failed to mark notification as read:', error)
    })
}

// Background sync event (for offline actions)
self.addEventListener('sync', (event) => {
  console.log('🔄 Background sync triggered:', event.tag)
  
  if (event.tag === 'sync-notifications') {
    event.waitUntil(syncNotifications())
  }
})

// Sync notifications with server
async function syncNotifications() {
  try {
    const response = await fetch('/api/notifications', {
      credentials: 'include'
    })
    const data = await response.json()
    console.log('✅ Notifications synced:', data)
    return data
  } catch (error) {
    console.error('❌ Failed to sync notifications:', error)
    throw error
  }
}

// Message event - handle messages from the main app
self.addEventListener('message', (event) => {
  console.log('💬 Message received in service worker:', event.data)
  
  if (event.data && event.data.type === 'SKIP_WAITING') {
    console.log('⏭️ Skipping waiting')
    self.skipWaiting()
  }
  
  if (event.data && event.data.type === 'CLIENTS_CLAIM') {
    console.log('👑 Claiming clients')
    self.clients.claim()
  }

  if (event.data && event.data.type === 'CHECK_SUBSCRIPTION') {
    console.log('🔍 Checking push subscription')
    self.registration.pushManager.getSubscription()
      .then((subscription) => {
        event.ports[0].postMessage({
          type: 'SUBSCRIPTION_STATUS',
          subscribed: !!subscription,
          subscription: subscription
        })
      })
  }
})

// Fetch event - optional, for caching strategies
self.addEventListener('fetch', (event) => {
  // Only handle same-origin requests
  if (!event.request.url.startsWith(self.location.origin)) {
    return
  }

  // Let the browser handle navigation requests normally
  if (event.request.mode === 'navigate') {
    return
  }

  // For API requests, use network-first strategy
  if (event.request.url.includes('/api/')) {
    event.respondWith(
      fetch(event.request)
        .catch(() => {
          // Return cached response if network fails
          return caches.match(event.request)
        })
    )
  }
})

console.log('✅ Service Worker script fully loaded and ready')