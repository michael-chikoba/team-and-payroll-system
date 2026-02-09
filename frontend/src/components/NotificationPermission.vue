<template>
  <div v-if="shouldShowPrompt" class="notification-permission-prompt">
    <div class="prompt-content">
      <div class="prompt-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
          <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
      </div>
      
      <div class="prompt-text">
        <h3>Stay Updated with Notifications</h3>
        <p>Get instant alerts for tasks, invitations, and important updates</p>
      </div>
      
      <div class="prompt-actions">
        <button @click="enableNotifications" class="btn-enable" :disabled="loading">
          <span v-if="!loading">Enable Notifications</span>
          <span v-else>Enabling...</span>
        </button>
        <button @click="dismiss" class="btn-dismiss">
          Maybe Later
        </button>
      </div>
      
      <button @click="close" class="btn-close">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { usePushNotifications } from '@/composables/usePushNotifications'
import { useAuthStore } from '@/stores/auth'

export default {
  name: 'NotificationPermission',
  setup() {
    const authStore = useAuthStore()
    const push = usePushNotifications()
    
    const isDismissed = ref(false)
    const loading = ref(false)
    
    // Check if user has dismissed the prompt before
    const dismissedKey = 'notification-prompt-dismissed'
    
    const shouldShowPrompt = computed(() => {
      return (
        authStore.isAuthenticated &&
        push.isSupported.value &&
        push.canRequestPermission.value &&
        !isDismissed.value &&
        !localStorage.getItem(dismissedKey)
      )
    })
    
    const enableNotifications = async () => {
      try {
        loading.value = true
        
        const granted = await push.requestPermission()
        
        if (granted) {
          await push.subscribe()
          close()
        }
      } catch (error) {
        console.error('Failed to enable notifications:', error)
      } finally {
        loading.value = false
      }
    }
    
    const dismiss = () => {
      localStorage.setItem(dismissedKey, Date.now().toString())
      isDismissed.value = true
    }
    
    const close = () => {
      isDismissed.value = true
    }
    
    // Auto-show after a delay
    onMounted(() => {
      setTimeout(() => {
        if (shouldShowPrompt.value) {
          console.log('Showing notification permission prompt')
        }
      }, 3000) // Show after 3 seconds
    })
    
    return {
      shouldShowPrompt,
      loading,
      enableNotifications,
      dismiss,
      close
    }
  }
}
</script>

<style scoped>
.notification-permission-prompt {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 999999;
  animation: slideInRight 0.4s ease-out;
}

@keyframes slideInRight {
  from {
    transform: translateX(400px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.prompt-content {
  background: white;
  border-radius: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 1px rgba(0, 0, 0, 0.2);
  padding: 1.5rem;
  max-width: 400px;
  position: relative;
}

.prompt-icon {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  margin-bottom: 1rem;
}

.prompt-text h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.125rem;
  font-weight: 700;
  color: #1f2937;
}

.prompt-text p {
  margin: 0 0 1.5rem 0;
  font-size: 0.875rem;
  color: #6b7280;
  line-height: 1.5;
}

.prompt-actions {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.btn-enable {
  width: 100%;
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.btn-enable:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
}

.btn-enable:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-dismiss {
  width: 100%;
  padding: 0.75rem 1.5rem;
  background: transparent;
  color: #6b7280;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-dismiss:hover {
  background: #f9fafb;
  border-color: #d1d5db;
  color: #4b5563;
}

.btn-close {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: transparent;
  border: none;
  padding: 0.5rem;
  cursor: pointer;
  color: #9ca3af;
  border-radius: 6px;
  transition: all 0.2s;
}

.btn-close:hover {
  background: #f3f4f6;
  color: #4b5563;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
  .notification-permission-prompt {
    bottom: 10px;
    right: 10px;
    left: 10px;
  }
  
  .prompt-content {
    max-width: 100%;
  }
}
</style>