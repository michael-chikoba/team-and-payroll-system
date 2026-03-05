<template>
  <div class="clock-toggle-container">
    <!-- Main toggle button -->
    <button
      class="clock-toggle-btn"
      :class="{ 
        'is-clocked-in': isClockedIn, 
        'is-completed': todayStatus === 'completed',
        'loading': clockingIn || clockingOut 
      }"
      :disabled="clockingIn || clockingOut"
      @click="toggleClock"
    >
      <span v-if="clockingIn || clockingOut" class="loading-spinner">⏳</span>
      <span v-else>{{ buttonText }}</span>
    </button>
    
    <!-- Optional reset button (shown if there's an issue with status) -->
    <button
      v-if="showResetButton"
      class="reset-btn"
      :disabled="resetting"
      @click="forceResetStatus"
      title="Reset stuck attendance status"
    >
      {{ resetting ? 'Resetting...' : 'Reset' }}
    </button>
  </div>
</template>

<script>
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

export default {
  name: 'ClockToggle',
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  data() {
    return {
      todayStatus: 'absent', // 'absent', 'present', 'completed', 'late', 'on_leave'
      regularAttendance: null,
      overtimeAttendance: null,
      clockingIn: false,
      clockingOut: false,
      resetting: false,
      showResetButton: false,
      statusInterval: null,
    }
  },
  computed: {
    attendancePrefix() {
      const role = this.authStore.user?.role || 'employee'
      return `/api/${role}/attendance`
    },
    buttonText() {
      if (this.todayStatus === 'completed') {
        return 'Clocked Out'
      } else if (this.isClockedIn) {
        return 'Clock Out'
      } else {
        return 'Clock In'
      }
    },
    isClockedIn() {
      return this.todayStatus === 'present' || this.todayStatus === 'late'
    }
  },
  methods: {
    async toggleClock() {
      if (this.isClockedIn) {
        await this.clockOut()
      } else {
        await this.clockIn()
      }
    },
    
    async clockIn() {
      this.clockingIn = true
      try {
        const response = await axios.post(`${this.attendancePrefix}/clock-in`)
        
        this.todayStatus = 'present'
        this.showResetButton = false
        
        this.$notify({
          type: 'success',
          title: 'Clocked In',
          text: response.data.message || 'Clocked in successfully!'
        })
        
        // Refresh status immediately after clock-in
        await this.fetchTodayStatus()
        
        // Emit event for parent components (like attendance page)
        window.dispatchEvent(new CustomEvent('attendance-updated'))
        
      } catch (err) {
        console.error('Clock-in error:', err)
       
        // If already clocked in, show reset button
        if (err.response?.status === 422 && err.response?.data?.message?.includes('Already clocked in')) {
          this.showResetButton = true
        }
       
        this.$notify({
          type: 'error',
          title: 'Clock-in Failed',
          text: err.response?.data?.message || 'Failed to clock in. Please try again.'
        })
        
        // Refetch to ensure status is up-to-date even on error
        await this.fetchTodayStatus()
      } finally {
        this.clockingIn = false
      }
    },
    
    async clockOut() {
      this.clockingOut = true
      try {
        const response = await axios.post(`${this.attendancePrefix}/clock-out`)
        
        this.todayStatus = 'completed'
        this.showResetButton = false
        
        this.$notify({
          type: 'success',
          title: 'Clocked Out',
          text: response.data.message || 'Clocked out successfully!'
        })
        
        // Refresh status immediately after clock-out
        await this.fetchTodayStatus()
        
        // Emit event for parent components
        window.dispatchEvent(new CustomEvent('attendance-updated'))
        
      } catch (err) {
        console.error('Clock-out error:', err)
        
        this.$notify({
          type: 'error',
          title: 'Clock-out Failed',
          text: err.response?.data?.message || 'Failed to clock out. Please try again.'
        })
        
        // Refetch to ensure status is up-to-date even on error
        await this.fetchTodayStatus()
      } finally {
        this.clockingOut = false
      }
    },
    
    async fetchTodayStatus() {
      try {
        const response = await axios.get(`${this.attendancePrefix}/today-status`)
        
        console.log('Today status response:', response.data)
        
        // Extract status from response
        this.todayStatus = response.data.status || 'absent'
        this.regularAttendance = response.data.regular_attendance || null
        this.overtimeAttendance = response.data.overtime_attendance || null
        
        // Show reset button if there's an open attendance without clock-out
        if (this.regularAttendance && this.regularAttendance.clock_in && !this.regularAttendance.clock_out) {
          // Only show reset if status seems stuck
          if (this.todayStatus !== 'present' && this.todayStatus !== 'late') {
            this.showResetButton = true
          }
        } else {
          this.showResetButton = false
        }
        
      } catch (err) {
        console.error('Fetch today status error:', err)
        this.todayStatus = 'absent'
        this.showResetButton = false
        this.regularAttendance = null
        this.overtimeAttendance = null
      }
    },
    
    async forceResetStatus() {
      if (!confirm('This will auto-close any open attendance records. Continue?')) {
        return
      }
      
      this.resetting = true
      try {
        const response = await axios.post(`${this.attendancePrefix}/force-reset`)
        
        this.showResetButton = false
        this.todayStatus = 'absent'
        
        this.$notify({
          type: 'success',
          title: 'Status Reset',
          text: response.data.message || 'Attendance status has been reset successfully.'
        })
        
        // Refresh status
        await this.fetchTodayStatus()
        
        // Emit event for parent components
        window.dispatchEvent(new CustomEvent('attendance-updated'))
        
      } catch (err) {
        console.error('Reset error:', err)
        
        this.$notify({
          type: 'error',
          title: 'Reset Failed',
          text: err.response?.data?.message || 'Failed to reset status. Please contact support.'
        })
      } finally {
        this.resetting = false
      }
    },
    
    startStatusPolling() {
      // Poll every 30 seconds to keep status updated
      this.statusInterval = setInterval(() => {
        this.fetchTodayStatus()
      }, 30000)
    },
    
    stopStatusPolling() {
      if (this.statusInterval) {
        clearInterval(this.statusInterval)
        this.statusInterval = null
      }
    }
  },
  
  mounted() {
    // Initialize status on mount
    this.fetchTodayStatus()
    
    // Start polling for status updates
    this.startStatusPolling()
  },
  
  beforeUnmount() {
    // Clean up interval when component is destroyed
    this.stopStatusPolling()
  }
}
</script>

<style scoped>
.clock-toggle-container {
  display: flex;
  flex-direction: column;
  gap: 10px;
  align-items: center;
}

.clock-toggle-btn {
  padding: 12px 24px;
  font-size: 16px;
  font-weight: bold;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  background-color: #4CAF50;
  color: white;
  min-width: 140px;
}

.clock-toggle-btn:hover:not(:disabled) {
  background-color: #45a049;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
}

.clock-toggle-btn.is-clocked-in {
  background-color: #f44336;
}

.clock-toggle-btn.is-clocked-in:hover:not(:disabled) {
  background-color: #da190b;
}

.clock-toggle-btn.is-completed {
  background-color: #9e9e9e;
  cursor: not-allowed;
}

.clock-toggle-btn.is-completed:hover {
  background-color: #9e9e9e;
  transform: none;
  box-shadow: none;
}

.clock-toggle-btn.loading {
  opacity: 0.7;
  cursor: not-allowed;
}

.clock-toggle-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.loading-spinner {
  display: inline-block;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.reset-btn {
  padding: 8px 16px;
  font-size: 14px;
  background-color: #ff9800;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.reset-btn:hover:not(:disabled) {
  background-color: #e68900;
  transform: translateY(-1px);
}

.reset-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>