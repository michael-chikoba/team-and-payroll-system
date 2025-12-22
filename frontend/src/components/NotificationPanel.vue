<template>
  <div class="notification-panel-overlay" @click="$emit('close')">
    <div class="notification-panel" @click.stop>
      <div class="panel-header">
        <h3>Notifications</h3>
        <div class="header-actions">
          <button @click="$emit('mark-all-read')" class="mark-all-btn">
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

      <div class="notifications-list">
        <div
          v-for="notification in notifications"
          :key="notification.id"
          :class="['notification-item', { unread: !notification.is_read }]"
          @click="handleNotificationClick(notification)">
          
          <div :class="['notification-icon', notification.type]">
            <svg v-if="notification.type === 'assignment'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
            <svg v-else-if="notification.type === 'reminder'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <polyline points="12 6 12 12 16 14"/>
            </svg>
            <svg v-else-if="notification.type === 'overdue'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </div>

          <div class="notification-content">
            <p class="notification-message">{{ notification.message }}</p>
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
    </div>
  </div>
</template>

<script>
export default {
  name: 'NotificationPanel',
  props: {
    notifications: {
      type: Array,
      required: true
    }
  },
  emits: ['close', 'mark-read', 'mark-all-read'],
  setup(props, { emit }) {
    const handleNotificationClick = (notification) => {
      if (!notification.is_read) {
        emit('mark-read', notification);
      }
    };

    const formatTime = (timestamp) => {
      const date = new Date(timestamp);
      const now = new Date();
      const diffMs = now - date;
      const diffMins = Math.floor(diffMs / 60000);
      const diffHours = Math.floor(diffMs / 3600000);
      const diffDays = Math.floor(diffMs / 86400000);

      if (diffMins < 1) return 'Just now';
      if (diffMins < 60) return `${diffMins}m ago`;
      if (diffHours < 24) return `${diffHours}h ago`;
      if (diffDays < 7) return `${diffDays}d ago`;
      
      return date.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric' 
      });
    };

    return {
      handleNotificationClick,
      formatTime
    };
  }
};
</script>

<style scoped>
.notification-panel-overlay {
  position: fixed;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  background: rgba(0, 0, 0, 0.3);
  z-index: 1000;
  display: flex;
  justify-content: flex-end;
  animation: fadeIn 0.2s;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.notification-panel {
  width: 400px;
  max-width: 100%;
  background: white;
  box-shadow: -4px 0 24px rgba(0,0,0,0.2);
  display: flex;
  flex-direction: column;
  animation: slideIn 0.3s;
}

@keyframes slideIn {
  from { transform: translateX(100%); }
  to { transform: translateX(0); }
}

.panel-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.panel-header h3 {
  margin: 0;
  font-size: 1.25rem;
  color: #1f2937;
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
}

.mark-all-btn:hover {
  background: #f9fafb;
  border-color: #d1d5db;
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
}

.close-btn:hover {
  background: #f3f4f6;
}

.notifications-list {
  flex: 1;
  overflow-y: auto;
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

.notification-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.notification-icon.assignment {
  background: #dbeafe;
  color: #3b82f6;
}

.notification-icon.reminder {
  background: #fef3c7;
  color: #f59e0b;
}

.notification-icon.overdue {
  background: #fee2e2;
  color: #ef4444;
}

.notification-icon.completed {
  background: #d1fae5;
  color: #10b981;
}

.notification-content {
  flex: 1;
}

.notification-message {
  margin: 0 0 0.25rem 0;
  font-size: 0.875rem;
  color: #1f2937;
  line-height: 1.5;
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
}

.empty-state p {
  margin: 0;
  font-size: 0.875rem;
}
</style>