<template>
  <div class="notification-preferences">
    <div class="preferences-header">
      <h2>Notification Preferences</h2>
      <p>Choose how and when you want to receive notifications</p>
    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading preferences...</p>
    </div>

    <div v-else class="preferences-content">
      <!-- Push Notification Status -->
      <div class="preference-section">
        <div class="section-header">
          <h3>Push Notifications</h3>
          <div class="status-badge" :class="pushStatusClass">
            {{ pushStatusText }}
          </div>
        </div>

        <div v-if="!push.isSupported.value" class="alert alert-warning">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="8" x2="12" y2="12"/>
            <line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <span>Push notifications are not supported in your browser</span>
        </div>

        <div v-else-if="push.permissionDenied.value" class="alert alert-error">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="15" y1="9" x2="9" y2="15"/>
            <line x1="9" y1="9" x2="15" y2="15"/>
          </svg>
          <span>Notification permission denied. Please enable notifications in your browser settings.</span>
        </div>

        <div v-else class="push-controls">
          <div v-if="!push.isSubscribed.value" class="push-subscribe">
            <button @click="subscribeToPush" class="btn-primary" :disabled="push.isLoading.value">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
              </svg>
              <span v-if="!push.isLoading.value">Enable Push Notifications</span>
              <span v-else>Enabling...</span>
            </button>
            <p class="helper-text">Get instant alerts even when you're not on the site</p>
          </div>

          <div v-else class="push-enabled">
            <div class="enabled-info">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                <polyline points="22 4 12 14.01 9 11.01"/>
              </svg>
              <span>Push notifications are enabled</span>
            </div>
            <div class="enabled-actions">
              <button @click="testPushNotification" class="btn-secondary" :disabled="push.isLoading.value">
                Test Notification
              </button>
              <button @click="unsubscribeFromPush" class="btn-danger" :disabled="push.isLoading.value">
                Disable
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Global Settings -->
      <div class="preference-section" v-if="preferences">
        <div class="section-header">
          <h3>Global Settings</h3>
        </div>

        <div class="preference-item">
          <div class="item-info">
            <label>Push Notifications</label>
            <p>Receive push notifications on your device</p>
          </div>
          <div class="item-control">
            <label class="switch">
              <input type="checkbox" v-model="preferences.push_enabled" @change="savePreferences">
              <span class="slider"></span>
            </label>
          </div>
        </div>

        <div class="preference-item">
          <div class="item-info">
            <label>Email Notifications</label>
            <p>Receive notifications via email</p>
          </div>
          <div class="item-control">
            <label class="switch">
              <input type="checkbox" v-model="preferences.email_enabled" @change="savePreferences">
              <span class="slider"></span>
            </label>
          </div>
        </div>
      </div>

      <!-- Notification Types -->
      <div class="preference-section" v-if="preferences">
        <div class="section-header">
          <h3>Notification Types</h3>
          <p class="section-subtitle">Choose which notifications you want to receive</p>
        </div>

        <div class="notification-types">
          <div class="type-item" v-for="type in notificationTypes" :key="type.key">
            <div class="type-header">
              <div class="type-icon" :class="type.class">
                <component :is="type.icon" />
              </div>
              <div class="type-info">
                <h4>{{ type.label }}</h4>
                <p>{{ type.description }}</p>
              </div>
            </div>
            <div class="type-controls">
              <div class="control-item">
                <span class="control-label">Push</span>
                <label class="switch small">
                  <input 
                    type="checkbox" 
                    v-model="preferences[`push_${type.key}`]" 
                    @change="savePreferences"
                    :disabled="!preferences.push_enabled"
                  >
                  <span class="slider"></span>
                </label>
              </div>
              <div class="control-item">
                <span class="control-label">Email</span>
                <label class="switch small">
                  <input 
                    type="checkbox" 
                    v-model="preferences[`email_${type.key}`]" 
                    @change="savePreferences"
                    :disabled="!preferences.email_enabled"
                  >
                  <span class="slider"></span>
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quiet Hours -->
      <div class="preference-section" v-if="preferences">
        <div class="section-header">
          <h3>Quiet Hours</h3>
          <p class="section-subtitle">Set times when you don't want to receive notifications</p>
        </div>

        <div class="preference-item">
          <div class="item-info">
            <label>Enable Quiet Hours</label>
            <p>Pause notifications during specific times</p>
          </div>
          <div class="item-control">
            <label class="switch">
              <input type="checkbox" v-model="preferences.quiet_hours_enabled" @change="savePreferences">
              <span class="slider"></span>
            </label>
          </div>
        </div>

        <div v-if="preferences.quiet_hours_enabled" class="quiet-hours-times">
          <div class="time-input">
            <label>Start Time</label>
            <input 
              type="time" 
              v-model="preferences.quiet_hours_start" 
              @change="savePreferences"
              class="time-field"
            >
          </div>
          <div class="time-separator">to</div>
          <div class="time-input">
            <label>End Time</label>
            <input 
              type="time" 
              v-model="preferences.quiet_hours_end" 
              @change="savePreferences"
              class="time-field"
            >
          </div>
        </div>
      </div>

      <!-- Success Message -->
      <div v-if="saveSuccess" class="alert alert-success">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
        <span>Preferences saved successfully</span>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, h } from 'vue'
import { usePushNotifications } from '@/composables/usePushNotifications'
import axios from 'axios'

// Icon components (inline SVG)
const BellIcon = () => h('svg', { xmlns: 'http://www.w3.org/2000/svg', width: '16', height: '16', viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2' }, [
  h('path', { d: 'M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9' }),
  h('path', { d: 'M13.73 21a2 2 0 0 1-3.46 0' })
])

export default {
  name: 'NotificationPreferences',
  setup() {
    const push = usePushNotifications()
    
    const loading = ref(false)
    const saveSuccess = ref(false)
    const preferences = ref(null)
    
    const notificationTypes = [
      {
        key: 'business_group_invitation',
        label: 'Business Group Invitations',
        description: 'When someone invites you to join a business group',
        icon: BellIcon,
        class: 'invitation'
      },
      {
        key: 'task_assigned',
        label: 'Task Assignments',
        description: 'When a new task is assigned to you',
        icon: BellIcon,
        class: 'task'
      },
      {
        key: 'schedule_updated',
        label: 'Schedule Updates',
        description: 'When your work schedule is updated',
        icon: BellIcon,
        class: 'schedule'
      },
      {
        key: 'leave_request',
        label: 'Leave Requests',
        description: 'Updates on your leave requests',
        icon: BellIcon,
        class: 'leave'
      },
      {
        key: 'ticket_created',
        label: 'Support Tickets',
        description: 'When a new ticket is created or updated',
        icon: BellIcon,
        class: 'ticket'
      },
      {
        key: 'reminder',
        label: 'Reminders',
        description: 'Important reminders and deadlines',
        icon: BellIcon,
        class: 'reminder'
      },
      {
        key: 'system_announcement',
        label: 'System Announcements',
        description: 'Important updates about the system',
        icon: BellIcon,
        class: 'announcement'
      }
    ]
    
    const pushStatusClass = computed(() => {
      if (!push.isSupported.value) return 'unsupported'
      if (push.permissionDenied.value) return 'denied'
      if (push.isSubscribed.value) return 'enabled'
      return 'disabled'
    })
    
    const pushStatusText = computed(() => {
      if (!push.isSupported.value) return 'Unsupported'
      if (push.permissionDenied.value) return 'Denied'
      if (push.isSubscribed.value) return 'Enabled'
      return 'Disabled'
    })
    
    const loadPreferences = async () => {
      try {
        loading.value = true
        const response = await axios.get('/api/push/preferences')
        preferences.value = response.data.preferences
      } catch (error) {
        console.error('Failed to load preferences:', error)
      } finally {
        loading.value = false
      }
    }
    
    const savePreferences = async () => {
      try {
        await axios.put('/api/push/preferences', preferences.value)
        
        // Show success message
        saveSuccess.value = true
        setTimeout(() => {
          saveSuccess.value = false
        }, 3000)
      } catch (error) {
        console.error('Failed to save preferences:', error)
      }
    }
    
    const subscribeToPush = async () => {
      try {
        await push.subscribe()
        // Reload preferences after subscription
        await loadPreferences()
      } catch (error) {
        console.error('Failed to subscribe:', error)
      }
    }
    
    const unsubscribeFromPush = async () => {
      if (confirm('Are you sure you want to disable push notifications?')) {
        try {
          await push.unsubscribe()
        } catch (error) {
          console.error('Failed to unsubscribe:', error)
        }
      }
    }
    
    const testPushNotification = async () => {
      try {
        await push.sendTestNotification()
      } catch (error) {
        console.error('Failed to send test notification:', error)
      }
    }
    
    onMounted(() => {
      push.init()
      loadPreferences()
    })
    
    return {
      push,
      loading,
      saveSuccess,
      preferences,
      notificationTypes,
      pushStatusClass,
      pushStatusText,
      savePreferences,
      subscribeToPush,
      unsubscribeFromPush,
      testPushNotification
    }
  }
}
</script>

<style scoped>
.notification-preferences {
  max-width: 900px;
  margin: 0 auto;
  padding: 2rem;
}

.preferences-header {
  margin-bottom: 2rem;
}

.preferences-header h2 {
  margin: 0 0 0.5rem 0;
  font-size: 1.875rem;
  font-weight: 700;
  color: #1f2937;
}

.preferences-header p {
  margin: 0;
  font-size: 1rem;
  color: #6b7280;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  color: #9ca3af;
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

.preference-section {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1f2937;
}

.section-subtitle {
  margin: 0.5rem 0 0 0;
  font-size: 0.875rem;
  color: #6b7280;
}

.status-badge {
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-badge.enabled {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.disabled {
  background: #fee2e2;
  color: #991b1b;
}

.status-badge.denied {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.unsupported {
  background: #f3f4f6;
  color: #6b7280;
}

.alert {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.alert-warning {
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #fde68a;
}

.alert-error {
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.alert-success {
  background: #d1fae5;
  color: #065f46;
  border: 1px solid #a7f3d0;
}

.push-subscribe, .push-enabled {
  padding: 1rem 0;
}

.btn-primary, .btn-secondary, .btn-danger {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
  background: #f3f4f6;
  color: #4b5563;
}

.btn-secondary:hover:not(:disabled) {
  background: #e5e7eb;
}

.btn-danger {
  background: #fee2e2;
  color: #991b1b;
}

.btn-danger:hover:not(:disabled) {
  background: #fecaca;
}

.btn-primary:disabled, .btn-secondary:disabled, .btn-danger:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.helper-text {
  margin: 0.5rem 0 0 0;
  font-size: 0.875rem;
  color: #6b7280;
}

.enabled-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  background: #f0fdf4;
  border-radius: 8px;
  margin-bottom: 1rem;
  color: #065f46;
  font-weight: 500;
}

.enabled-actions {
  display: flex;
  gap: 0.75rem;
}

.preference-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 0;
  border-bottom: 1px solid #f3f4f6;
}

.preference-item:last-child {
  border-bottom: none;
}

.item-info label {
  display: block;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.25rem;
}

.item-info p {
  margin: 0;
  font-size: 0.875rem;
  color: #6b7280;
}

/* Toggle Switch */
.switch {
  position: relative;
  display: inline-block;
  width: 48px;
  height: 24px;
}

.switch.small {
  width: 40px;
  height: 20px;
}

.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #cbd5e1;
  transition: 0.3s;
  border-radius: 24px;
}

.slider:before {
  position: absolute;
  content: "";
  height: 18px;
  width: 18px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: 0.3s;
  border-radius: 50%;
}

.switch.small .slider:before {
  height: 14px;
  width: 14px;
}

input:checked + .slider {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

input:checked + .slider:before {
  transform: translateX(24px);
}

.switch.small input:checked + .slider:before {
  transform: translateX(20px);
}

input:disabled + .slider {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Notification Types */
.notification-types {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.type-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
}

.type-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex: 1;
}

.type-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.type-icon.invitation { background: #f3e8ff; color: #9333ea; }
.type-icon.task { background: #dbeafe; color: #3b82f6; }
.type-icon.schedule { background: #fce7f3; color: #ec4899; }
.type-icon.leave { background: #e0e7ff; color: #6366f1; }
.type-icon.ticket { background: #fef9c3; color: #ca8a04; }
.type-icon.reminder { background: #fef3c7; color: #f59e0b; }
.type-icon.announcement { background: #d1fae5; color: #10b981; }

.type-info h4 {
  margin: 0 0 0.25rem 0;
  font-size: 0.875rem;
  font-weight: 600;
  color: #1f2937;
}

.type-info p {
  margin: 0;
  font-size: 0.75rem;
  color: #6b7280;
}

.type-controls {
  display: flex;
  gap: 1.5rem;
  align-items: center;
}

.control-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.control-label {
  font-size: 0.75rem;
  color: #6b7280;
  font-weight: 500;
}

/* Quiet Hours */
.quiet-hours-times {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  margin-top: 1rem;
}

.time-input {
  flex: 1;
}

.time-input label {
  display: block;
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  margin-bottom: 0.5rem;
}

.time-field {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 0.875rem;
}

.time-separator {
  color: #9ca3af;
  font-weight: 500;
  padding-top: 1.5rem;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .notification-preferences {
    padding: 1rem;
  }
  
  .type-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .type-controls {
    width: 100%;
    justify-content: space-between;
  }
  
  .enabled-actions {
    flex-direction: column;
  }
  
  .enabled-actions button {
    width: 100%;
  }
}
</style>