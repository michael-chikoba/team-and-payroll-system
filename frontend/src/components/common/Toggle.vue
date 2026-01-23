<template>
  <div class="clock-toggle-container">
    <!-- Main toggle button -->
    <button
      class="clock-toggle-btn"
      :class="{ 'is-clocked-in': todayStatus === 'present', loading: clockingIn || clockingOut }"
      :disabled="clockingIn || clockingOut"
      @click="toggleClock"
    >
      <span v-if="clockingIn || clockingOut" class="loading-spinner">⏳</span>
      <span v-else>{{ buttonText }}</span>
    </button>
    <!-- Optional reset button (shown if already clocked in today) -->
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
      todayStatus: 'absent', // e.g., 'absent', 'present', 'completed'
      clockingIn: false,
      clockingOut: false,
      resetting: false,
      showResetButton: false,
    }
  },
  computed: {
    attendancePrefix() {
      const role = this.authStore.user?.role || 'employee'
      return `/api/${role}/attendance`
    },
    buttonText() {
      switch (this.todayStatus) {
        case 'present':
          return 'Clock Out'
        case 'completed':
          return 'Clock In (Today Completed)'
        default:
          return 'Clock In'
      }
    },
    isClockedIn() {
      return this.todayStatus === 'present'
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
          title: 'Success',
          text: response.data.message || 'Clocked in successfully!'
        })
        await this.fetchAttendance()
        await this.fetchTodayStatus()
      } catch (err) {
        console.error('Clock-in error:', err)
       
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
          title: 'Success',
          text: response.data.message || 'Clocked out successfully!'
        })
        await this.fetchAttendance()
        await this.fetchTodayStatus()
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
    // Placeholder methods - implement these based on your API to refresh data
    // Ensure fetchTodayStatus actually updates this.todayStatus from the API response
    async fetchAttendance() {
      // e.g., await axios.get('/api/employee/attendance').then(res => { /* update data */ })
      console.log('Fetching attendance...')
    },
    async fetchTodayStatus() {
      try {
        // Example implementation: Fetch and update status from API
        // Replace with your actual API endpoint
        const response = await axios.get(`${this.attendancePrefix}/today-status`)
        this.todayStatus = response.data.status || 'absent'
        this.showResetButton = (this.todayStatus === 'present' && response.data.attendance?.clockOut)
      } catch (err) {
        console.error('Fetch today status error:', err)
        this.todayStatus = 'absent'
        this.showResetButton = false
      }
    },
    // Reset button handler (matching forceResetStatus from Attendance)
    async forceResetStatus() {
      if (!confirm('This will auto-close any open attendance records. Continue?')) {
        return
      }
      this.resetting = true
      try {
        const response = await axios.post(`${this.attendancePrefix}/force-reset`)
        this.showResetButton = false
        this.$notify({
          type: 'success',
          title: 'Status Reset',
          text: response.data.message || 'Attendance status has been reset successfully.'
        })
        await this.fetchAttendance()
        await this.fetchTodayStatus()
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
    }
  },
  mounted() {
    // Initialize status on mount
    this.fetchTodayStatus()
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
}
.clock-toggle-btn:hover:not(:disabled) {
  background-color: #45a049;
}
.clock-toggle-btn.is-clocked-in {
  background-color: #f44336;
}
.clock-toggle-btn.is-clocked-in:hover:not(:disabled) {
  background-color: #da190b;
}
.clock-toggle-btn.loading {
  opacity: 0.7;
  cursor: not-allowed;
}
.loading-spinner {
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
}
.reset-btn:hover:not(:disabled) {
  background-color: #e68900;
}
.reset-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>