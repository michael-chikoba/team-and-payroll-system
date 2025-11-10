<template>
  <div class="attendance-view">
    <header class="header">
      <div class="header-left">
        <h1 class="title">{{ pageName }}</h1>
        <p class="subtitle">Track your daily attendance and work hours</p>
      </div>
      <div class="header-right">
        <div class="clock-buttons">
          <button 
            @click="clockIn" 
            :disabled="todayStatus === 'present' || todayStatus === 'completed' || clockingIn" 
            class="clock-btn in"
          >
            {{ clockingIn ? 'Clocking In...' : 'Clock In' }}
          </button>
          
          <button 
            @click="clockOut" 
            :disabled="todayStatus !== 'present' || clockingOut" 
            class="clock-btn out"
          >
            {{ clockingOut ? 'Clocking Out...' : 'Clock Out' }}
          </button>
          
          <!-- Reset Button - Only shows when there's an issue -->
          <button 
            v-if="showResetButton"
            @click="forceResetStatus" 
            :disabled="resetting" 
            class="clock-btn reset"
            title="Reset stuck attendance status"
          >
            {{ resetting ? 'Resetting...' : '🔄 Reset Status' }}
          </button>
        </div>
        
        <span class="today-status" :class="todayStatus ? todayStatus.toLowerCase() : 'absent'">
          Today: {{ formatTodayStatus(todayStatus) }}
        </span>
      </div>
    </header>
    
    <div class="filters">
      <div class="filter-group">
        <label>Date Range:</label>
        <input v-model="dateFrom" type="date" @change="fetchAttendance" :max="today" />
        <input v-model="dateTo" type="date" @change="fetchAttendance" :max="today" />
      </div>
      <div class="filter-group">
        <label>Status:</label>
        <select v-model="statusFilter" @change="fetchAttendance">
          <option value="">All</option>
          <option value="present">Present</option>
          <option value="absent">Absent</option>
          <option value="late">Late</option>
          <option value="early_leave">Early Leave</option>
        </select>
      </div>
      <button @click="exportAttendance" class="export-btn">📥 Export CSV</button>
    </div>

    <div class="content">
      <!-- Summary Cards -->
      <div class="summary-cards" v-if="!loading">
        <div class="card">
          <div class="card-icon">🕐</div>
          <h3>Total Hours This Month</h3>
          <p class="value">{{ stats.totalHours?.toFixed(1) || 0 }} hrs</p>
        </div>
        <div class="card">
          <div class="card-icon">📊</div>
          <h3>Attendance Rate</h3>
          <p class="value">{{ stats.attendanceRate?.toFixed(1) || 0 }}%</p>
        </div>
        <div class="card">
          <div class="card-icon">⚠️</div>
          <h3>Late Days</h3>
          <p class="value">{{ stats.lateDays || 0 }}</p>
        </div>
        <div class="card">
          <div class="card-icon">📅</div>
          <h3>Workdays This Month</h3>
          <p class="value">{{ stats.workdays || 0 }}</p>
        </div>
      </div>

      <!-- Attendance Table -->
      <div v-if="filteredAttendance.length === 0 && !loading" class="empty-state">
        <p>No attendance records found for the selected date range.</p>
        <button @click="resetFilters" class="btn-primary">Reset Filters</button>
      </div>
      <table v-else-if="!loading" class="attendance-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Check In</th>
            <th>Check Out</th>
            <th>Hours Worked</th>
            <th>Status</th>
            <th>Notes</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="record in filteredAttendance" :key="record.id">
            <td>{{ formatDate(record.date) }}</td>
            <td>{{ formatTimeDisplay(record.checkIn || record.clock_in) }}</td>
            <td>{{ formatTimeDisplay(record.checkOut || record.clock_out) }}</td>
            <td>{{ formatHours(record.hoursWorked || record.total_hours) }}</td>
            <td>
              <span :class="['status', getStatusClass(record.status)]">
                {{ formatStatus(record.status) }}
              </span>
            </td>
            <td>{{ record.notes || '-' }}</td>
          </tr>
        </tbody>
      </table>

      <!-- Loading State -->
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Loading attendance records...</p>
      </div>

      <!-- Error State -->
      <div v-if="error" class="error-message">
        {{ error }}
        <button @click="retryFetch" class="btn-primary">Retry</button>
      </div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

export default {
  name: 'Attendance',
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  data() {
    return {
      pageName: 'My Attendance',
      attendance: [],
      dateFrom: '',
      dateTo: '',
      statusFilter: '',
      today: new Date().toISOString().split('T')[0],
      todayStatus: 'absent',
      stats: {
        totalHours: 0,
        attendanceRate: 0,
        lateDays: 0,
        workdays: 0
      },
      loading: false,
      clockingIn: false,
      clockingOut: false,
      resetting: false,
      showResetButton: false,
      error: null,
      retryCount: 0
    }
  },
  computed: {
    filteredAttendance() {
      let filtered = [...this.attendance]
      if (this.dateFrom) {
        filtered = filtered.filter(record => record.date >= this.dateFrom)
      }
      if (this.dateTo) {
        filtered = filtered.filter(record => record.date <= this.dateTo)
      }
      if (this.statusFilter) {
        filtered = filtered.filter(record => record.status === this.statusFilter)
      }
      return filtered.sort((a, b) => new Date(b.date) - new Date(a.date))
    }
  },
  mounted() {
    this.fetchAttendance()
    this.fetchTodayStatus()
  },
  methods: {
    async fetchAttendance(retry = false) {
      this.loading = true
      this.error = null
      try {
        const params = {
          ...(this.dateFrom && { from: this.dateFrom }),
          ...(this.dateTo && { to: this.dateTo }),
          ...(this.statusFilter && { status: this.statusFilter })
        }
        const [attendanceRes, statsRes] = await Promise.all([
          axios.get('/api/employee/attendance', { params }),
          axios.get('/api/employee/attendance/stats')
        ])
        
        // Handle different response structures
        this.attendance = attendanceRes.data.data || attendanceRes.data || []
        
        // Handle stats response
        const statsData = statsRes.data.stats || statsRes.data || {}
        this.stats = {
          totalHours: statsData.totalHours || statsData.total_hours || 0,
          attendanceRate: statsData.attendanceRate || statsData.attendance_rate || 0,
          lateDays: statsData.lateDays || statsData.late_days || 0,
          workdays: statsData.workdays || statsData.working_days || 0
        }
      } catch (err) {
        this.handleApiError(err)
      } finally {
        this.loading = false
      }
    },
    
    async fetchTodayStatus() {
      try {
        const response = await axios.get('/api/employee/attendance/today-status')
        this.todayStatus = response.data.status || 'absent'
        
        // If status is present but attendance has clock_out, show reset button
        if (this.todayStatus === 'present' && response.data.attendance?.clockOut) {
          this.showResetButton = true
        }
      } catch (err) {
        console.error('Failed to fetch today status:', err)
        this.todayStatus = 'absent'
      }
    },
    
    async clockIn() {
      this.clockingIn = true
      try {
        const response = await axios.post('/api/employee/attendance/clock-in')
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
        
        // Show reset button if already clocked in error
        if (err.response?.status === 422 && err.response?.data?.message?.includes('Already clocked in')) {
          this.showResetButton = true
        }
        
        this.$notify({
          type: 'error',
          title: 'Clock-in Failed',
          text: err.response?.data?.message || 'Failed to clock in. Please try again.'
        })
      } finally {
        this.clockingIn = false
      }
    },

    async clockOut() {
      this.clockingOut = true
      try {
        const response = await axios.post('/api/employee/attendance/clock-out')
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
      } finally {
        this.clockingOut = false
      }
    },

    async forceResetStatus() {
      if (!confirm('This will auto-close any open attendance records. Continue?')) {
        return
      }

      this.resetting = true
      try {
        const response = await axios.post('/api/employee/attendance/force-reset')
        this.showResetButton = false
        this.$notify({
          type: 'success',
          title: 'Status Reset',
          text: response.data.message || 'Attendance status has been reset successfully.'
        })
        await this.fetchTodayStatus()
        await this.fetchAttendance()
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
    
    async exportAttendance() {
      try {
        const params = {
          ...(this.dateFrom && { from: this.dateFrom }),
          ...(this.dateTo && { to: this.dateTo }),
          ...(this.statusFilter && { status: this.statusFilter })
        }
        const response = await axios.get('/api/employee/attendance/export', { 
          params, 
          responseType: 'blob' 
        })
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `attendance-${this.dateFrom || ''}-${this.dateTo || this.today}.csv`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Attendance exported successfully!'
        })
      } catch (err) {
        this.handleApiError(err)
      }
    },
    
    resetFilters() {
      this.dateFrom = ''
      this.dateTo = ''
      this.statusFilter = ''
      this.fetchAttendance()
    },
    
    retryFetch() {
      this.retryCount++
      if (this.retryCount <= 3) {
        this.fetchAttendance(true)
      } else {
        this.error = 'Max retries exceeded. Please refresh the page.'
      }
    },
    
    handleApiError(err) {
      let errorMsg = 'An unexpected error occurred.'
      if (err.code === 'ERR_NETWORK' || err.message?.includes('Network Error')) {
        errorMsg = 'Network error. Check your connection.'
      } else if (err.response?.status === 401) {
        errorMsg = 'Session expired. Redirecting to login...'
        this.authStore.clearAuth()
        this.$router.push({ name: 'login' })
      } else if (err.response?.status === 403) {
        errorMsg = 'Access denied.'
      } else if (err.response?.status === 422) {
        errorMsg = err.response.data.message || 'Please check your input.'
      } else {
        errorMsg = err.response?.data?.message || errorMsg
      }
      this.error = errorMsg
    },
    
    getStatusClass(status) {
      if (!status) return 'present'
      return status.toLowerCase()
    },
    
    formatTodayStatus(status) {
      if (!status) return 'Absent'
      const statuses = {
        present: 'In Progress',
        completed: 'Completed',
        absent: 'Absent'
      }
      return statuses[status] || status
    },
    
    formatStatus(status) {
      if (!status) return 'Present'
      const statuses = {
        present: 'Present',
        absent: 'Absent',
        late: 'Late',
        early_leave: 'Early Leave'
      }
      return statuses[status] || status
    },
    
    formatHours(hours) {
      if (!hours && hours !== 0) return 'N/A'
      const h = Math.floor(hours)
      const m = Math.round((hours % 1) * 60)
      return `${h}h ${m}m`
    },
    
    formatTimeDisplay(time) {
      if (!time) return 'N/A'
      
      try {
        // Remove any timezone or date information
        let timeStr = time
        
        // Handle ISO datetime format (2025-10-17T08:47:44.000000Z)
        if (timeStr.includes('T')) {
          const dateTime = new Date(timeStr)
          const hours = dateTime.getHours()
          const minutes = dateTime.getMinutes()
          const ampm = hours >= 12 ? 'PM' : 'AM'
          const displayHours = hours % 12 || 12
          return `${displayHours}:${minutes.toString().padStart(2, '0')} ${ampm}`
        }
        
        // Handle time with seconds (HH:MM:SS)
        if (timeStr.includes(':')) {
          const parts = timeStr.split(':')
          const hours = parseInt(parts[0])
          const minutes = parseInt(parts[1])
          const ampm = hours >= 12 ? 'PM' : 'AM'
          const displayHours = hours % 12 || 12
          return `${displayHours}:${minutes.toString().padStart(2, '0')} ${ampm}`
        }
        
        return time
      } catch (e) {
        console.error('Error formatting time:', e, time)
        return time
      }
    },
    
    formatDate(date) {
      if (!date) return 'N/A'
      try {
        return new Date(date).toLocaleDateString('en-ZM', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          weekday: 'short'
        })
      } catch (e) {
        return date
      }
    }
  }
}
</script>

<style scoped>
.attendance-view {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  min-height: 100vh;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: white;
  padding: 1.5rem 2rem;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  margin-bottom: 2rem;
}

.header-left {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.title {
  margin: 0;
  font-size: 2rem;
  font-weight: 300;
  color: #2c3e50;
}

.subtitle {
  margin: 0;
  color: #7f8c8d;
  font-size: 1rem;
}

.header-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.5rem;
}

.clock-buttons {
  display: flex;
  gap: 0.5rem;
}

.clock-btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: transform 0.2s ease;
}

.clock-btn.in {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  color: white;
}

.clock-btn.out {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
  color: white;
}

.clock-btn.reset {
  background: linear-gradient(135deg, #ffa500 0%, #ff8c00 100%);
  color: white;
  font-size: 0.9rem;
}

.clock-btn:hover:not(:disabled) {
  transform: translateY(-2px);
}

.clock-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.today-status {
  font-weight: 500;
  font-size: 0.95rem;
}

.today-status.present { color: #28a745; }
.today-status.completed { color: #007bff; }
.today-status.absent { color: #dc3545; }

.filters {
  display: flex;
  gap: 1rem;
  background: white;
  padding: 1rem;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  margin-bottom: 2rem;
  flex-wrap: wrap;
  align-items: end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  min-width: 150px;
}

.filter-group label {
  font-weight: 600;
  color: #2c3e50;
  font-size: 0.9rem;
}

.filter-group input,
.filter-group select {
  padding: 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.95rem;
}

.export-btn {
  background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: transform 0.2s ease;
}

.export-btn:hover {
  transform: translateY(-2px);
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  text-align: center;
}

.card-icon {
  font-size: 2.5rem;
  margin-bottom: 1rem;
}

.card h3 {
  margin: 0 0 0.5rem;
  font-size: 1rem;
  color: #7f8c8d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.card .value {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
  color: #2c3e50;
}

.content {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #7f8c8d;
}

.attendance-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.95rem;
}

.attendance-table th,
.attendance-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #ecf0f1;
}

.attendance-table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #2c3e50;
  text-transform: uppercase;
  font-size: 0.85rem;
}

.attendance-table tr:hover {
  background: #f8f9fa;
}

.status {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
  text-transform: uppercase;
}

.status.present {
  background: #d4edda;
  color: #155724;
}

.status.absent {
  background: #f8d7da;
  color: #721c24;
}

.status.late {
  background: #fff3cd;
  color: #856404;
}

.status.early_leave {
  background: #d1ecf1;
  color: #0c5460;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-message {
  background: #fee;
  color: #c33;
  padding: 1rem;
  border-radius: 8px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
}

@media (max-width: 768px) {
  .header {
    flex-direction: column;
    gap: 1rem;
    padding: 1rem;
  }

  .clock-buttons {
    flex-direction: column;
    width: 100%;
  }

  .clock-btn {
    width: 100%;
  }

  .filters {
    flex-direction: column;
    padding: 1rem;
  }

  .summary-cards {
    grid-template-columns: 1fr;
  }

  .attendance-table {
    font-size: 0.85rem;
  }

  .attendance-table th,
  .attendance-table td {
    padding: 0.5rem;
  }
}
</style>