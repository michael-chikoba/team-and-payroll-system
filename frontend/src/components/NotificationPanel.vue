<template>
  <!-- Backdrop overlay - teleported to body -->
  <div class="notification-panel-backdrop" @click="$emit('close')"></div>
  
  <!-- Panel container - teleported to body with dynamic positioning -->
  <div 
    class="notification-panel-wrapper" 
    :style="panelStyles"
    @click.stop
  >
    <div class="notification-panel">
      <div class="panel-header">
        <h3>Notifications</h3>
        <div class="header-actions">
          <button @click="markAllAsRead" class="mark-all-btn" v-if="hasUnread">
            Mark all as read
          </button>
          <button @click="$emit('close')" class="close-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"/>
              <line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
          </button>
        </div>
      </div>
      
      <div class="notifications-list" v-if="!loading">
        <div
          v-for="notification in notifications"
          :key="notification.id"
          :class="['notification-item', { unread: !notification.is_read }]"
          @click="handleNotificationClick(notification)">
          <div :class="['notification-icon', getIconClass(notification.type)]">
            <!-- Business Group Invitation Icon -->
            <svg v-if="notification.type === 'business_group_invitation'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            
            <!-- Task Assigned Icon -->
            <svg v-else-if="notification.type === 'task_assigned'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <polyline points="16 11 18 13 22 9"></polyline>
            </svg>
            
            <!-- Schedule Icons -->
            <svg v-else-if="notification.type.includes('schedule')" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
              <line x1="16" y1="2" x2="16" y2="6"/>
              <line x1="8" y1="2" x2="8" y2="6"/>
              <line x1="3" y1="10" x2="21" y2="10"/>
              <circle cx="12" cy="15" r="2"/>
            </svg>
            
            <!-- Leave Icons -->
            <svg v-else-if="notification.type.includes('leave')" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
              <line x1="16" y1="2" x2="16" y2="6"/>
              <line x1="8" y1="2" x2="8" y2="6"/>
              <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            
            <!-- Ticket Icons -->
            <svg v-else-if="notification.type.includes('ticket')" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2z"/>
              <path d="M3 7l9 6 9-6"/>
            </svg>
            
            <!-- Updated/Reminder Icon -->
            <svg v-else-if="notification.type.includes('updated') || notification.type.includes('reminder')" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <polyline points="12 6 12 12 16 14"/>
            </svg>
            
            <!-- Overdue Icon -->
            <svg v-else-if="notification.type.includes('overdue')" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            
            <!-- Default/Completed Icon -->
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </div>
          
          <div class="notification-content">
            <p class="notification-title" v-if="notification.title">{{ notification.title }}</p>
            <p class="notification-message">{{ formatNotificationMessage(notification) }}</p>
            <span class="notification-time">{{ formatTime(notification.created_at) }}</span>
          </div>
          
          <div v-if="!notification.is_read" class="unread-indicator"></div>
        </div>
        
        <div v-if="notifications.length === 0" class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
          </svg>
          <p>No notifications</p>
        </div>
      </div>
      
      <div v-else class="loading-state">
        <div class="spinner"></div>
        <p>Loading notifications...</p>
      </div>
    </div>
  </div>
</template>

<script>
import { useNotificationStore } from '@/stores/notification'
import { useRouter } from 'vue-router'
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'

export default {
  name: 'NotificationPanel',
  props: {
    notifications: {
      type: Array,
      required: true
    },
    buttonPosition: {
      type: Object,
      required: true
    }
  },
  emits: ['close', 'mark-all-read'],
  setup(props, { emit }) {
    const store = useNotificationStore()
    const router = useRouter()
    const authStore = useAuthStore()

    const loading = computed(() => store.loading)
    const hasUnread = computed(() => props.notifications.some(n => !n.is_read))
    
    const panelStyles = computed(() => {
      return {
        position: 'fixed',
        top: `${props.buttonPosition.top}px`,
        right: `${props.buttonPosition.right}px`,
      }
    })

    const formatNotificationMessage = (notification) => {
      // For business group invitations, format the message nicely
      if (notification.type === 'business_group_invitation') {
        const data = notification.data || {}
        return `${data.invited_by || 'A business'} invited you to join "${data.group_name || 'a business group'}" as ${data.proposed_role || 'member'}`
      }
      
      // For other notifications, return the existing message
      return notification.message
    }

  
const handleNotificationClick = async (notification) => {
  try {
    // Mark as read first
    if (!notification.is_read) {
      await store.markAsRead(notification.id)
    }
    
    // Close the panel
    emit('close')
    
    // Handle business group invitation
    if (notification.type === 'business_group_invitation') {
      await router.push({ name: 'Invitations' })
      console.log('✅ Navigated to Invitations from notification')
      return
    }
    
    // ... rest of your code
  } catch (error) {
    console.error('❌ Error handling notification click:', error)
  }
}
    const formatTime = (timestamp) => {
      const date = new Date(timestamp)
      const now = new Date()
      const diffMs = now - date
      const diffMins = Math.floor(diffMs / 60000)
      const diffHours = Math.floor(diffMs / 3600000)
      const diffDays = Math.floor(diffMs / 86400000)
      
      if (diffMins < 1) return 'Just now'
      if (diffMins < 60) return `${diffMins}m ago`
      if (diffHours < 24) return `${diffHours}h ago`
      if (diffDays < 7) return `${diffDays}d ago`
      
      return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric'
      })
    }

    const markAllAsRead = async () => {
      try {
        await store.markAllAsRead()
        emit('mark-all-read')
      } catch (error) {
        console.error('Error marking all as read:', error)
      }
    }

    const getIconClass = (type) => {
      if (type === 'business_group_invitation') return 'invitation'
      if (type === 'task_assigned') return 'assignment'
      if (type.includes('schedule')) return 'schedule'
      if (type.includes('updated')) return 'reminder'
      if (type.includes('overdue')) return 'overdue'
      if (type.includes('leave')) return 'leave'
      if (type.includes('ticket')) return 'ticket'
      if (type.includes('shift')) return 'shift'
      if (type.includes('payslip')) return 'payslip'
      return 'completed'
    }

    return {
      loading,
      hasUnread,
      panelStyles,
      formatNotificationMessage,
      handleNotificationClick,
      formatTime,
      markAllAsRead,
      getIconClass
    }
  }
}
</script>

<style scoped>
/* Backdrop - covers entire screen with MAXIMUM z-index */
.notification-panel-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.3);
  z-index: 999998; /* Maximum z-index for backdrop */
  animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

/* Panel wrapper - positioned dynamically with MAXIMUM z-index */
.notification-panel-wrapper {
  z-index: 999999; /* Maximum z-index - highest in entire app */
  animation: slideIn 0.3s ease-out;
  pointer-events: auto;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.notification-panel {
  width: 420px;
  max-width: calc(100vw - 40px);
  max-height: calc(100vh - 100px);
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 1px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.panel-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-shrink: 0;
  background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
}

.panel-header h3 {
  margin: 0;
  font-size: 1.25rem;
  color: #1f2937;
  font-weight: 700;
}

.header-actions {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.mark-all-btn {
  padding: 0.5rem 0.75rem;
  border: 1px solid #e5e7eb;
  background: white;
  border-radius: 6px;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
  color: #4b5563;
  font-weight: 500;
}

.mark-all-btn:hover {
  background: #f9fafb;
  border-color: #d1d5db;
  color: #1f2937;
}

.close-btn {
  padding: 0.5rem;
  border: none;
  background: transparent;
  cursor: pointer;
  border-radius: 6px;
  transition: background-color 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
}

.close-btn:hover {
  background: #f3f4f6;
  color: #1f2937;
}

.notifications-list {
  flex: 1;
  overflow-y: auto;
  min-height: 200px;
  max-height: calc(100vh - 200px);
}

/* Custom scrollbar */
.notifications-list::-webkit-scrollbar {
  width: 6px;
}

.notifications-list::-webkit-scrollbar-track {
  background: #f3f4f6;
}

.notifications-list::-webkit-scrollbar-thumb {
  background: #d1d5db;
  border-radius: 3px;
}

.notifications-list::-webkit-scrollbar-thumb:hover {
  background: #9ca3af;
}

.notification-item {
  display: flex;
  gap: 1rem;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #f3f4f6;
  cursor: pointer;
  transition: background-color 0.2s;
  position: relative;
}

.notification-item:hover {
  background: #f9fafb;
}

.notification-item.unread {
  background: #eff6ff;
}

.notification-item.unread:hover {
  background: #dbeafe;
}

.notification-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.notification-icon.invitation {
  background: #f3e8ff;
  color: #9333ea;
}

.notification-icon.assignment {
  background: #dbeafe;
  color: #3b82f6;
}

.notification-icon.schedule {
  background: #fce7f3;
  color: #ec4899;
}

.notification-icon.reminder {
  background: #fef3c7;
  color: #f59e0b;
}

.notification-icon.overdue {
  background: #fee2e2;
  color: #ef4444;
}

.notification-icon.leave {
  background: #e0e7ff;
  color: #6366f1;
}

.notification-icon.ticket {
  background: #fef9c3;
  color: #ca8a04;
}

.notification-icon.shift {
  background: #dbeafe;
  color: #0284c7;
}

.notification-icon.payslip {
  background: #d1fae5;
  color: #059669;
}

.notification-icon.completed {
  background: #d1fae5;
  color: #10b981;
}

.notification-content {
  flex: 1;
  min-width: 0;
}

.notification-title {
  margin: 0 0 0.25rem 0;
  font-size: 0.875rem;
  font-weight: 600;
  color: #1f2937;
  line-height: 1.4;
}

.notification-message {
  margin: 0 0 0.25rem 0;
  font-size: 0.875rem;
  color: #4b5563;
  line-height: 1.5;
  word-wrap: break-word;
}

.notification-time {
  font-size: 0.75rem;
  color: #9ca3af;
}

.unread-indicator {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #3b82f6;
  flex-shrink: 0;
  align-self: center;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  color: #9ca3af;
}

.empty-state svg {
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-state p {
  margin: 0;
  font-size: 0.875rem;
  color: #6b7280;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  color: #9ca3af;
  min-height: 300px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #f3f4f6;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loading-state p {
  margin: 0;
  font-size: 0.875rem;
  color: #6b7280;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .notification-panel-wrapper {
    top: 60px !important;
    right: 10px !important;
    left: 10px !important;
    width: auto !important;
  }
  
  .notification-panel {
    width: 100%;
    max-width: 100%;
    max-height: calc(100vh - 80px);
  }
  
  .panel-header {
    padding: 1rem;
  }
  
  .panel-header h3 {
    font-size: 1.1rem;
  }
  
  .notification-item {
    padding: 0.875rem 1rem;
  }
  
  .notifications-list {
    max-height: calc(100vh - 150px);
  }
}

@media (max-width: 480px) {
  .notification-panel-wrapper {
    top: 50px !important;
    right: 5px !important;
    left: 5px !important;
  }
  
  .mark-all-btn {
    font-size: 0.75rem;
    padding: 0.4rem 0.6rem;
  }
  
  .notification-icon {
    width: 28px;
    height: 28px;
  }
  
  .notification-icon svg {
    width: 14px;
    height: 14px;
  }
}
</style>