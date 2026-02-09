<template>
  <div class="attendance-view">
    <!-- Activity Monitor (shows when overtime is active) -->
    <ActivityMonitor v-if="isInOvertimeSession" />
   
    <header class="attendance-header">
      <div class="header-left">
        <h1 class="page-title">{{ pageName }}</h1>
        <p class="page-subtitle">Track your daily attendance and work hours</p>
      </div>
      <div class="header-right">
        <div class="clock-buttons">
          <button
            @click="clockIn"
            :disabled="!canClockIn || clockingIn"
            class="btn btn-success"
          >
            {{ clockingIn ? 'Clocking In...' : 'Clock In' }}
          </button>
         
          <button
            @click="clockOut"
            :disabled="!canClockOut || clockingOut"
            class="btn btn-danger"
          >
            {{ clockingOut ? 'Clocking Out...' : 'Clock Out' }}
          </button>
         
          <button
            v-if="canStartOvertime"
            @click="clockInOvertime"
            :disabled="clockingInOvertime"
            class="btn btn-warning"
            title="Clock in for overtime after shift end"
          >
            {{ clockingInOvertime ? 'Starting...' : '⚡ Overtime' }}
          </button>
         
          <button
            v-if="showResetButton"
            @click="forceResetStatus"
            :disabled="resetting"
            class="btn btn-outline-secondary"
            title="Reset stuck attendance status"
          >
            {{ resetting ? 'Resetting...' : 'Reset' }}
          </button>
        </div>
       
        <div class="status-display">
          <div class="status-badge" :class="getStatusBadgeClass(todayStatus)">
            <span class="status-dot"></span>
            {{ formatTodayStatus(todayStatus) }}
            <span v-if="isInOvertimeSession" class="overtime-indicator">⚡ OVERTIME</span>
          </div>
       
          <!-- Shift Info Display -->
          <div v-if="currentShift" class="shift-info-card">
            <span class="shift-label">Today's Shift:</span>
            <span class="shift-time">{{ currentShift.start_time }} - {{ currentShift.end_time }}</span>
            <span class="shift-type-badge">{{ formatShiftType(currentShift.type) }}</span>
          </div>
        </div>
      </div>
    </header>
   
    <div class="attendance-content">
      <!-- Summary Cards -->
      <div class="summary-cards" v-if="!loading">
        <div class="stat-card stat-card-primary">
          <div class="stat-icon">⏱️</div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.totalHours?.toFixed(1) || 0 }}h</div>
            <div class="stat-label">Total Hours</div>
            <div class="stat-sublabel">this month</div>
          </div>
        </div>
        
        <div class="stat-card stat-card-success">
          <div class="stat-icon">✓</div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.regularHours?.toFixed(1) || 0 }}h</div>
            <div class="stat-label">Regular Hours</div>
            <div class="stat-sublabel">standard time</div>
          </div>
        </div>
        
        <div class="stat-card stat-card-warning">
          <div class="stat-icon">⚡</div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.overtimeHours?.toFixed(1) || 0 }}h</div>
            <div class="stat-label">Overtime Hours</div>
            <div class="stat-sublabel">extra time</div>
          </div>
        </div>
        
        <div class="stat-card stat-card-info">
          <div class="stat-icon">📊</div>
          <div class="stat-content">
            <div class="stat-value">{{ stats.attendanceRate?.toFixed(1) || 0 }}%</div>
            <div class="stat-label">Attendance Rate</div>
            <div class="stat-sublabel">of workdays</div>
          </div>
        </div>
      </div>
     
      <!-- Filters Section -->
      <div class="filters-card">
        <div class="filters-grid">
          <div class="filter-group">
            <label class="filter-label">From Date</label>
            <input 
              v-model="dateFrom" 
              type="date" 
              @change="handleFilterChange" 
              :max="today" 
              class="filter-input" 
            />
          </div>
          
          <div class="filter-group">
            <label class="filter-label">To Date</label>
            <input 
              v-model="dateTo" 
              type="date" 
              @change="handleFilterChange" 
              :max="today" 
              class="filter-input" 
            />
          </div>
          
          <div class="filter-group">
            <label class="filter-label">Status</label>
            <select v-model="statusFilter" @change="handleFilterChange" class="filter-select">
              <option value="">All Statuses</option>
              <option value="present">Present</option>
              <option value="absent">Absent</option>
              <option value="late">Late</option>
              <option value="completed">Completed</option>
            </select>
          </div>
          
          <div class="filter-actions">
            <button @click="resetFilters" class="btn btn-secondary">
              Reset Filters
            </button>
            <button @click="viewOvertimeSummary" class="btn btn-warning">
              ⚡ Overtime Summary
            </button>
          </div>
        </div>
      </div>
     
      <!-- Attendance Table -->
      <div class="table-card">
        <div class="table-header">
          <h2 class="table-title">Attendance Records</h2>
          <div class="table-meta">
            <span class="record-count">{{ filteredAttendance.length }} records</span>
            <span class="page-indicator">Page {{ currentPage }} of {{ totalPages }}</span>
          </div>
        </div>
       
        <div v-if="filteredAttendance.length === 0 && !loading" class="empty-state">
          <div class="empty-icon">📅</div>
          <h3 class="empty-title">No Records Found</h3>
          <p class="empty-description">No attendance records match your current filters.</p>
          <button @click="resetFilters" class="btn btn-primary">Reset Filters</button>
        </div>
       
        <div v-else-if="!loading" class="table-container">
          <table class="attendance-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Status</th>
                <th>Check In</th>
                <th>Check Out</th>
                <th>Regular</th>
                <th>Overtime</th>
                <th>Total</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="record in paginatedRecords" :key="record.id">
                <td>
                  <div class="date-cell">
                    <span class="date-text">{{ formatDate(record.date) }}</span>
                  </div>
                </td>
                <td>
                  <span v-if="record.is_overtime_session" class="type-badge type-overtime">
                    ⚡ Overtime
                  </span>
                  <span v-else class="type-badge type-regular">
                    Regular
                  </span>
                </td>
                <td>
                  <span :class="['status-badge-table', getStatusClass(record.status)]">
                    {{ formatStatus(record.status) }}
                  </span>
                </td>
                <td>
                  <div class="time-cell">
                    {{ formatTimeDisplay(record.checkIn || record.clock_in) }}
                  </div>
                </td>
                <td>
                  <div class="time-cell">
                    {{ formatTimeDisplay(record.checkOut || record.clock_out) }}
                  </div>
                </td>
                <td>
                  <div class="hours-cell">
                    {{ formatHours(record.regular_hours) }}
                  </div>
                </td>
                <td>
                  <div class="hours-cell hours-overtime">
                    {{ formatHours(record.overtime_hours) }}
                  </div>
                </td>
                <td>
                  <div class="hours-cell hours-total">
                    {{ formatHours(record.hoursWorked || record.total_hours) }}
                  </div>
                </td>
                <td>
                  <span class="notes-text">{{ record.notes || '-' }}</span>
                </td>
              </tr>
            </tbody>
          </table>
         
          <!-- Pagination Controls -->
          <div class="pagination-controls">
            <div class="pagination-buttons">
              <button
                @click="goToPage(1)"
                :disabled="currentPage === 1"
                class="pagination-btn"
              >
                First
              </button>
              <button
                @click="goToPage(currentPage - 1)"
                :disabled="currentPage === 1"
                class="pagination-btn"
              >
                Previous
              </button>
             
              <div class="pagination-numbers">
                <button
                  v-for="page in visiblePages"
                  :key="page"
                  @click="goToPage(page)"
                  :class="['pagination-number', { active: page === currentPage }]"
                >
                  {{ page }}
                </button>
              </div>
             
              <button
                @click="goToPage(currentPage + 1)"
                :disabled="currentPage === totalPages"
                class="pagination-btn"
              >
                Next
              </button>
              <button
                @click="goToPage(totalPages)"
                :disabled="currentPage === totalPages"
                class="pagination-btn"
              >
                Last
              </button>
            </div>
            
            <select v-model="itemsPerPage" @change="handlePerPageChange" class="per-page-select">
              <option :value="10">10 per page</option>
              <option :value="25">25 per page</option>
              <option :value="50">50 per page</option>
              <option :value="100">100 per page</option>
            </select>
          </div>
        </div>
      </div>
     
      <!-- Loading State -->
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading attendance records...</p>
      </div>
     
      <!-- Error State -->
      <div v-if="error" class="error-alert">
        <div class="error-icon">⚠️</div>
        <div class="error-content">
          <p>{{ error }}</p>
          <button @click="retryFetch" class="btn btn-primary">Retry</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import ActivityMonitor from '@/components/ActivityMonitor.vue';

export default {
  name: 'EmployeeAttendance',
 
  components: {
    ActivityMonitor
  },
 
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
      currentShift: null,
      canClockIn: false,
      canClockOut: false,
      canStartOvertime: false,
      isInOvertimeSession: false,
      shiftHasEnded: false,
      stats: {
        totalHours: 0,
        regularHours: 0,
        overtimeHours: 0,
        attendanceRate: 0,
        lateDays: 0,
        workdays: 0
      },
      loading: false,
      clockingIn: false,
      clockingOut: false,
      clockingInOvertime: false,
      resetting: false,
      showResetButton: false,
      error: null,
      retryCount: 0,
      currentPage: 1,
      itemsPerPage: 25
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
      return filtered.sort((a, b) => {
        const dateCompare = new Date(b.date) - new Date(a.date)
        if (dateCompare !== 0) return dateCompare
        return (a.is_overtime_session ? 1 : 0) - (b.is_overtime_session ? 1 : 0)
      })
    },
   
    totalPages() {
      return Math.ceil(this.filteredAttendance.length / this.itemsPerPage)
    },
   
    paginatedRecords() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredAttendance.slice(start, end)
    },
   
    visiblePages() {
      const pages = []
      const maxVisible = 5
      let startPage = Math.max(1, this.currentPage - Math.floor(maxVisible / 2))
      let endPage = Math.min(this.totalPages, startPage + maxVisible - 1)
     
      if (endPage - startPage < maxVisible - 1) {
        startPage = Math.max(1, endPage - maxVisible + 1)
      }
     
      for (let i = startPage; i <= endPage; i++) {
        pages.push(i)
      }
      return pages
    }
  },
 
  mounted() {
    this.fetchAttendance()
    this.fetchTodayStatus()
  },
 
  methods: {
    goToPage(page) {
      if (page >= 1 && page <= this.totalPages) {
        this.currentPage = page
      }
    },
   
    handlePerPageChange() {
      this.currentPage = 1
    },
   
    handleFilterChange() {
      this.currentPage = 1
      this.fetchAttendance()
    },
   
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
       
        this.attendance = attendanceRes.data.data || attendanceRes.data || []
       
        const statsData = statsRes.data.stats || statsRes.data || {}
        this.stats = {
          totalHours: statsData.totalHours || statsData.total_hours || 0,
          regularHours: statsData.regularHours || statsData.regular_hours || 0,
          overtimeHours: statsData.overtimeHours || statsData.overtime_hours || 0,
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
        this.canClockIn = response.data.can_clock_in || false
        this.canClockOut = response.data.can_clock_out || false
        this.canStartOvertime = response.data.can_start_overtime || false
        this.isInOvertimeSession = response.data.is_in_overtime_session || false
        this.shiftHasEnded = response.data.shift_has_ended || false
        this.currentShift = response.data.shift || null
       
        if (this.todayStatus === 'present' && response.data.regular_attendance?.clock_out) {
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
       
        if (err.response?.status === 422) {
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
       
        const overtimeHours = response.data.attendance?.overtime_hours || 0
        let message = response.data.message || 'Clocked out successfully!'
       
        if (overtimeHours > 0) {
          message += ` You worked ${this.formatHours(overtimeHours)} of overtime.`
        }
       
        this.$notify({
          type: 'success',
          title: 'Success',
          text: message
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
   
    async clockInOvertime() {
      if (!confirm('Start overtime session? This will be tracked separately from your regular hours.')) {
        return
      }
     
      this.clockingInOvertime = true
      try {
        const response = await axios.post('/api/attendance/clock-in-overtime')
        this.isInOvertimeSession = true
        this.canStartOvertime = false
        this.canClockOut = true
       
        this.$notify({
          type: 'success',
          title: 'Overtime Started',
          text: response.data.message || 'Clocked in for overtime successfully!'
        })
       
        await this.fetchAttendance()
        await this.fetchTodayStatus()
      } catch (err) {
        console.error('Overtime clock-in error:', err)
        this.$notify({
          type: 'error',
          title: 'Overtime Clock-in Failed',
          text: err.response?.data?.message || 'Failed to start overtime. Please try again.'
        })
      } finally {
        this.clockingInOvertime = false
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
   
    async viewOvertimeSummary() {
      try {
        const date = new Date()
        const response = await axios.get('/api/attendance/overtime-summary', {
          params: {
            month: date.getMonth() + 1,
            year: date.getFullYear()
          }
        })
       
        const summary = response.data.summary
        const message = `
          Overtime Summary for ${response.data.period.month_name}:
         
          Total Overtime Hours: ${summary.total_overtime_hours}h
          Overtime Sessions: ${summary.overtime_sessions_count}
          Days with Overtime: ${summary.days_with_overtime}
          Total Hours (All): ${summary.total_all_hours}h
        `
       
        alert(message)
      } catch (err) {
        this.$notify({
          type: 'error',
          title: 'Error',
          text: 'Failed to load overtime summary'
        })
      }
    },
   
    resetFilters() {
      this.dateFrom = ''
      this.dateTo = ''
      this.statusFilter = ''
      this.currentPage = 1
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
      if (!status) return 'status-present'
      const statusMap = {
        present: 'status-present',
        completed: 'status-completed',
        absent: 'status-absent',
        late: 'status-late',
        early_leave: 'status-early'
      }
      return statusMap[status] || 'status-present'
    },
   
    getStatusBadgeClass(status) {
      if (!status) return 'status-absent'
      return `status-${status}`
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
        completed: 'Completed',
        early_leave: 'Early Leave'
      }
      return statuses[status] || status
    },
   
    formatShiftType(type) {
      const types = {
        morning: 'Morning',
        day: 'Day',
        evening: 'Evening',
        night: 'Night'
      }
      return types[type] || type
    },
   
    formatHours(hours) {
      if (!hours && hours !== 0) return '0h 0m'
      const h = Math.floor(hours)
      const m = Math.round((hours % 1) * 60)
      return `${h}h ${m}m`
    },
   
    formatTimeDisplay(time) {
      if (!time) return 'N/A'
     
      try {
        let timeStr = time
       
        if (timeStr.includes('T')) {
          const dateTime = new Date(timeStr)
          const hours = dateTime.getHours()
          const minutes = dateTime.getMinutes()
          const ampm = hours >= 12 ? 'PM' : 'AM'
          const displayHours = hours % 12 || 12
          return `${displayHours}:${minutes.toString().padStart(2, '0')} ${ampm}`
        }
       
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
  background: #ffffff;
  min-height: 100vh;
  padding-bottom: 2rem;
}

/* Header Styles - Scrolls with content */
.attendance-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem 2.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
  border-radius: 0;
}

.header-left {
  margin-bottom: 1.5rem;
}

.page-title {
  color: #ffffff;
  font-size: 2rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
  letter-spacing: -0.5px;
}

.page-subtitle {
  color: rgba(255, 255, 255, 0.9);
  font-size: 1rem;
  margin: 0;
}

.header-right {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.clock-buttons {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-success {
  background: #10b981;
  color: white;
}

.btn-success:hover:not(:disabled) {
  background: #059669;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-danger {
  background: #ef4444;
  color: white;
}

.btn-danger:hover:not(:disabled) {
  background: #dc2626;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.btn-warning {
  background: #f59e0b;
  color: white;
}

.btn-warning:hover:not(:disabled) {
  background: #d97706;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.btn-outline-secondary {
  background: transparent;
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.5);
}

.btn-outline-secondary:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.1);
  border-color: white;
}

.btn-secondary {
  background: #6b7280;
  color: white;
}

.btn-secondary:hover:not(:disabled) {
  background: #4b5563;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.status-display {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  border-radius: 50px;
  font-weight: 600;
  font-size: 0.95rem;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.status-badge.status-present {
  background: rgba(16, 185, 129, 0.3);
  border-color: rgba(16, 185, 129, 0.5);
}

.status-badge.status-completed {
  background: rgba(59, 130, 246, 0.3);
  border-color: rgba(59, 130, 246, 0.5);
}

.status-badge.status-absent {
  background: rgba(239, 68, 68, 0.3);
  border-color: rgba(239, 68, 68, 0.5);
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: currentColor;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.overtime-indicator {
  margin-left: 0.5rem;
  font-weight: 700;
  animation: blink 1.5s infinite;
}

@keyframes blink {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.6; }
}

.shift-info-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1.25rem;
  background: rgba(255, 255, 255, 0.15);
  border-radius: 8px;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.shift-label {
  color: rgba(255, 255, 255, 0.9);
  font-size: 0.9rem;
  font-weight: 500;
}

.shift-time {
  color: white;
  font-weight: 600;
  font-size: 0.95rem;
  padding: 0.25rem 0.75rem;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 4px;
}

.shift-type-badge {
  padding: 0.25rem 0.75rem;
  background: rgba(255, 255, 255, 0.3);
  border-radius: 4px;
  color: white;
  font-size: 0.85rem;
  font-weight: 600;
}

/* Main Content */
.attendance-content {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 2rem;
}

/* Summary Cards */
.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e7eb;
  display: flex;
  gap: 1rem;
  transition: all 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
}

.stat-icon {
  width: 56px;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  flex-shrink: 0;
}

.stat-card-primary .stat-icon {
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(102, 126, 234, 0.2));
  color: #667eea;
}

.stat-card-success .stat-icon {
  background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.2));
  color: #10b981;
}

.stat-card-warning .stat-icon {
  background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.2));
  color: #f59e0b;
}

.stat-card-info .stat-icon {
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(59, 130, 246, 0.2));
  color: #3b82f6;
}

.stat-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1a1a1a;
  line-height: 1;
  margin-bottom: 0.25rem;
}

.stat-label {
  font-size: 0.95rem;
  font-weight: 600;
  color: #4b5563;
  margin-bottom: 0.25rem;
}

.stat-sublabel {
  font-size: 0.8rem;
  color: #9ca3af;
}

/* Filters Card */
.filters-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e7eb;
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  align-items: end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-label {
  font-weight: 600;
  color: #374151;
  font-size: 0.9rem;
}

.filter-input,
.filter-select {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.95rem;
  color: #1a1a1a;
  background: white;
  transition: all 0.2s ease;
}

.filter-input:focus,
.filter-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

/* Table Card */
.table-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  border: 1px solid #e5e7eb;
  overflow: hidden;
}

.table-header {
  padding: 1.5rem;
  border-bottom: 2px solid #f3f4f6;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.table-title {
  font-size: 1.35rem;
  font-weight: 700;
  color: #1a1a1a;
  margin: 0;
}

.table-meta {
  display: flex;
  gap: 1rem;
  font-size: 0.9rem;
  color: #6b7280;
}

.record-count {
  font-weight: 600;
  color: #374151;
}

.page-indicator {
  color: #9ca3af;
}

/* Table Container */
.table-container {
  overflow-x: auto;
}

.attendance-table {
  width: 100%;
  border-collapse: collapse;
}

.attendance-table thead {
  background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
}

.attendance-table th {
  padding: 1rem;
  text-align: left;
  font-weight: 700;
  color: #374151;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 2px solid #e5e7eb;
}

.attendance-table td {
  padding: 1rem;
  border-bottom: 1px solid #f3f4f6;
  color: #1a1a1a;
  font-size: 0.95rem;
}

.attendance-table tbody tr {
  transition: background-color 0.15s ease;
}

.attendance-table tbody tr:hover {
  background-color: #f9fafb;
}

.date-cell {
  font-weight: 600;
  color: #374151;
}

.type-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.375rem 0.75rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.type-overtime {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.type-regular {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
  border: 1px solid rgba(59, 130, 246, 0.3);
}

.status-badge-table {
  display: inline-flex;
  align-items: center;
  padding: 0.375rem 0.875rem;
  border-radius: 50px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: capitalize;
}

.status-present {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.status-completed {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
  border: 1px solid rgba(59, 130, 246, 0.3);
}

.status-absent {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.status-late {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.status-early {
  background: rgba(168, 85, 247, 0.1);
  color: #a855f7;
  border: 1px solid rgba(168, 85, 247, 0.3);
}

.time-cell {
  font-weight: 500;
  color: #4b5563;
  font-family: 'Monaco', 'Courier New', monospace;
}

.hours-cell {
  font-weight: 600;
  color: #374151;
}

.hours-overtime {
  color: #f59e0b;
}

.hours-total {
  color: #667eea;
  font-size: 1.05rem;
}

.notes-text {
  color: #6b7280;
  font-size: 0.9rem;
}

/* Pagination */
.pagination-controls {
  padding: 1.5rem;
  border-top: 1px solid #f3f4f6;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
  background: #fafbfc;
}

.pagination-buttons {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  flex-wrap: wrap;
}

.pagination-btn {
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  background: white;
  color: #374151;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.9rem;
}

.pagination-btn:hover:not(:disabled) {
  background: #f3f4f6;
  border-color: #667eea;
  color: #667eea;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-numbers {
  display: flex;
  gap: 0.25rem;
}

.pagination-number {
  min-width: 2.5rem;
  height: 2.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #d1d5db;
  background: white;
  color: #374151;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.9rem;
}

.pagination-number:hover {
  background: #f3f4f6;
  border-color: #667eea;
  color: #667eea;
}

.pagination-number.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-color: #667eea;
}

.per-page-select {
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background: white;
  color: #374151;
  font-size: 0.9rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.per-page-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #374151;
  margin-bottom: 0.5rem;
}

.empty-description {
  color: #6b7280;
  margin-bottom: 1.5rem;
  font-size: 1rem;
}

/* Loading State */
.loading-state {
  text-align: center;
  padding: 4rem 2rem;
}

.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #f3f4f6;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Error State */
.error-alert {
  background: rgba(239, 68, 68, 0.1);
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 12px;
  padding: 1.5rem;
  margin: 2rem;
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.error-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.error-content {
  flex: 1;
}

.error-content p {
  color: #dc2626;
  font-weight: 600;
  margin-bottom: 1rem;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .summary-cards {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .attendance-header {
    padding: 1.5rem;
  }
  
  .page-title {
    font-size: 1.5rem;
  }
  
  .attendance-content {
    padding: 0 1rem;
  }
  
  .summary-cards {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .filters-grid {
    grid-template-columns: 1fr;
  }
  
  .clock-buttons {
    flex-direction: column;
  }
  
  .btn {
    width: 100%;
    justify-content: center;
  }
  
  .status-display {
    flex-direction: column;
    align-items: stretch;
  }
  
  .shift-info-card {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .table-container {
    overflow-x: scroll;
  }
  
  .attendance-table {
    min-width: 900px;
  }
  
  .pagination-controls {
    flex-direction: column;
  }
  
  .pagination-buttons {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .page-title {
    font-size: 1.25rem;
  }
  
  .stat-card {
    padding: 1rem;
  }
  
  .stat-icon {
    width: 48px;
    height: 48px;
    font-size: 1.5rem;
  }
  
  .stat-value {
    font-size: 1.5rem;
  }
  
  .table-header {
    padding: 1rem;
  }
  
  .table-title {
    font-size: 1.1rem;
  }
  
  .attendance-table th,
  .attendance-table td {
    padding: 0.75rem 0.5rem;
    font-size: 0.85rem;
  }
}
</style>