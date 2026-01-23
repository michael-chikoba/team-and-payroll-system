<template>
  <div v-if="isOvertimeActive" class="activity-monitor">
    <div class="monitor-status" :class="statusClass">
      <div class="status-indicator">
        <div class="pulse-dot" :class="{ active: isActive }"></div>
        <span>{{ statusText }}</span>
      </div>
      
      <div class="activity-info">
        <p class="info-text">
          <strong>Overtime Session Active</strong>
        </p>
        <p class="info-text">
          Current Hours: <strong>{{ currentHours.toFixed(2) }}</strong>
        </p>
        <p class="info-text text-sm">
          Last Activity: {{ lastActivityText }}
        </p>
        <p v-if="minutesUntilIdle > 0" class="warning-text text-sm">
          Auto clock-out in {{ minutesUntilIdle }} minutes if idle
        </p>
      </div>

      <button 
        @click="manualRefresh" 
        class="refresh-btn"
        :disabled="isRefreshing"
      >
        {{ isRefreshing ? 'Refreshing...' : 'I\'m Still Here' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useAttendanceStore } from '@/stores/attendance';
import axios from 'axios';

const attendanceStore = useAttendanceStore();

// State
const isActive = ref(true);
const lastActivity = ref(new Date());
const currentHours = ref(0);
const idleThreshold = ref(15); // minutes
const heartbeatInterval = ref(null);
const statusCheckInterval = ref(null);
const activityListeners = ref([]);
const isRefreshing = ref(false);
const minutesSinceActivity = ref(0);

// Computed
const isOvertimeActive = computed(() => {
  return attendanceStore.todayStatus?.is_in_overtime_session === true;
});

const statusClass = computed(() => {
  if (minutesSinceActivity.value >= idleThreshold.value) {
    return 'status-idle';
  }
  return isActive.value ? 'status-active' : 'status-warning';
});

const statusText = computed(() => {
  if (minutesSinceActivity.value >= idleThreshold.value) {
    return 'IDLE - Will Auto Clock Out';
  }
  return isActive.value ? 'Active' : 'Inactive Warning';
});

const lastActivityText = computed(() => {
  const seconds = Math.floor((new Date() - lastActivity.value) / 1000);
  if (seconds < 60) return `${seconds}s ago`;
  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) return `${minutes}m ago`;
  const hours = Math.floor(minutes / 60);
  return `${hours}h ${minutes % 60}m ago`;
});

const minutesUntilIdle = computed(() => {
  return Math.max(0, idleThreshold.value - minutesSinceActivity.value);
});

// Methods
const sendHeartbeat = async () => {
  if (!isOvertimeActive.value) return;

  try {
    const response = await axios.post('/api/attendance/heartbeat', {
      screen_active: !document.hidden,
      page_visible: document.visibilityState === 'visible'
    });

    if (response.data.success) {
      lastActivity.value = new Date(response.data.last_activity);
      currentHours.value = response.data.current_hours || 0;
      idleThreshold.value = response.data.idle_threshold_minutes || 15;
      isActive.value = true;
      minutesSinceActivity.value = 0;
    }
  } catch (error) {
    console.error('Heartbeat failed:', error);
    if (error.response?.status === 404) {
      // Session ended
      stopMonitoring();
      await attendanceStore.fetchTodayStatus();
    }
  }
};

const checkActivityStatus = async () => {
  if (!isOvertimeActive.value) return;

  try {
    const response = await axios.get('/api/attendance/activity-status');
    
    if (response.data.success && response.data.data.has_active_session) {
      const data = response.data.data;
      minutesSinceActivity.value = data.minutes_since_activity || 0;
      currentHours.value = data.current_hours || 0;
      
      if (data.status === 'idle') {
        isActive.value = false;
        showIdleWarning();
      }
    } else {
      // No active session
      stopMonitoring();
      await attendanceStore.fetchTodayStatus();
    }
  } catch (error) {
    console.error('Status check failed:', error);
  }
};

const manualRefresh = async () => {
  isRefreshing.value = true;
  try {
    await axios.post('/api/attendance/refresh-activity');
    lastActivity.value = new Date();
    minutesSinceActivity.value = 0;
    isActive.value = true;
    await sendHeartbeat();
  } catch (error) {
    console.error('Manual refresh failed:', error);
  } finally {
    isRefreshing.value = false;
  }
};

const showIdleWarning = () => {
  // You can integrate with your notification system here
  console.warn('User is idle - will auto clock out soon');
};

const onUserActivity = () => {
  // Throttle to prevent too many calls
  if (!isActive.value) {
    isActive.value = true;
    sendHeartbeat();
  }
};

const setupActivityListeners = () => {
  const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
  
  // Throttle activity detection
  let throttleTimer = null;
  const throttledActivity = () => {
    if (!throttleTimer) {
      throttleTimer = setTimeout(() => {
        onUserActivity();
        throttleTimer = null;
      }, 5000); // Throttle to once per 5 seconds
    }
  };
  
  events.forEach(event => {
    window.addEventListener(event, throttledActivity);
    activityListeners.value.push({ event, handler: throttledActivity });
  });

  // Page visibility
  const visibilityHandler = () => {
    if (!document.hidden) {
      onUserActivity();
    }
  };
  document.addEventListener('visibilitychange', visibilityHandler);
  activityListeners.value.push({ event: 'visibilitychange', handler: visibilityHandler });
};

const removeActivityListeners = () => {
  activityListeners.value.forEach(({ event, handler }) => {
    if (event === 'visibilitychange') {
      document.removeEventListener(event, handler);
    } else {
      window.removeEventListener(event, handler);
    }
  });
  activityListeners.value = [];
};

const startMonitoring = () => {
  // Initial heartbeat
  sendHeartbeat();

  // Setup heartbeat interval (every 60 seconds)
  heartbeatInterval.value = setInterval(() => {
    sendHeartbeat();
  }, 60000);

  // Setup status check interval (every 30 seconds)
  statusCheckInterval.value = setInterval(() => {
    checkActivityStatus();
  }, 30000);

  // Setup activity listeners
  setupActivityListeners();
};

const stopMonitoring = () => {
  if (heartbeatInterval.value) {
    clearInterval(heartbeatInterval.value);
    heartbeatInterval.value = null;
  }
  if (statusCheckInterval.value) {
    clearInterval(statusCheckInterval.value);
    statusCheckInterval.value = null;
  }
  removeActivityListeners();
};

// Lifecycle
onMounted(() => {
  if (isOvertimeActive.value) {
    startMonitoring();
  }
});

onUnmounted(() => {
  stopMonitoring();
});

// Watch for overtime session changes
watch(() => isOvertimeActive.value, (newVal) => {
  if (newVal) {
    startMonitoring();
  } else {
    stopMonitoring();
  }
});
</script>

<style scoped>
.activity-monitor {
  position: fixed;
  bottom: 20px;
  right: 20px;
  z-index: 1000;
}

.monitor-status {
  background: white;
  border-radius: 12px;
  padding: 16px 20px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  min-width: 280px;
  border-left: 4px solid #10b981;
  transition: all 0.3s ease;
}

.status-active {
  border-left-color: #10b981;
}

.status-warning {
  border-left-color: #f59e0b;
  animation: pulse 2s infinite;
}

.status-idle {
  border-left-color: #ef4444;
  animation: pulse 1s infinite;
}

.status-indicator {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 12px;
}

.pulse-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #ef4444;
}

.pulse-dot.active {
  background: #10b981;
  animation: pulse-dot 2s infinite;
}

.activity-info {
  margin: 12px 0;
}

.info-text {
  margin: 4px 0;
  font-size: 14px;
  color: #374151;
}

.warning-text {
  color: #dc2626;
  font-weight: 500;
  margin-top: 8px;
}

.text-sm {
  font-size: 12px;
}

.refresh-btn {
  width: 100%;
  padding: 8px 16px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: background 0.2s;
}

.refresh-btn:hover:not(:disabled) {
  background: #2563eb;
}

.refresh-btn:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}

@keyframes pulse-dot {
  0%, 100% {
    transform: scale(1);
    opacity: 1;
  }
  50% {
    transform: scale(1.2);
    opacity: 0.8;
  }
}
</style>