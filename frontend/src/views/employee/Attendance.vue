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
          
          <button
            v-if="showResetButton"
            @click="forceResetStatus"
            :disabled="resetting"
            class="clock-btn reset"
            title="Reset stuck attendance status"
          >
            {{ resetting ? 'Resetting...' : 'Reset' }}
          </button>
        </div>
        
        <div class="status-badge" :class="todayStatus ? todayStatus.toLowerCase() : 'absent'">
          <span class="status-dot"></span>
          {{ formatTodayStatus(todayStatus) }}
        </div>
      </div>
    </header>
   
    <div class="filters-section">
      <div class="filters-container">
        <div class="filter-group">
          <label>From Date</label>
          <input v-model="dateFrom" type="date" @change="handleFilterChange" :max="today" />
        </div>
        <div class="filter-group">
          <label>To Date</label>
          <input v-model="dateTo" type="date" @change="handleFilterChange" :max="today" />
        </div>
        <div class="filter-group">
          <label>Status</label>
          <select v-model="statusFilter" @change="handleFilterChange">
            <option value="">All Statuses</option>
            <option value="present">Present</option>
            <option value="absent">Absent</option>
            <option value="late">Late</option>
            <option value="early_leave">Early Leave</option>
          </select>
        </div>
        <div class="filter-actions">
          <button @click="resetFilters" class="btn-secondary">
            Reset
          </button>
          <button @click="exportAttendance" class="btn-export">
            Export
          </button>
        </div>
      </div>
    </div>
    <div class="content">
      <!-- Summary Cards -->
      <div class="summary-cards" v-if="!loading">
        <div class="card">
          <div class="card-header">
            <div class="card-icon blue"></div>
            <span class="card-label">Total Hours</span>
          </div>
          <p class="card-value">{{ stats.totalHours?.toFixed(1) || 0 }}</p>
          <p class="card-unit">hours this month</p>
        </div>
        <div class="card">
          <div class="card-header">
            <div class="card-icon green"></div>
            <span class="card-label">Attendance Rate</span>
          </div>
          <p class="card-value">{{ stats.attendanceRate?.toFixed(1) || 0 }}%</p>
          <p class="card-unit">of workdays</p>
        </div>
        <div class="card">
          <div class="card-header">
            <div class="card-icon orange"></div>
            <span class="card-label">Late Days</span>
          </div>
          <p class="card-value">{{ stats.lateDays || 0 }}</p>
          <p class="card-unit">this month</p>
        </div>
        <div class="card">
          <div class="card-header">
            <div class="card-icon purple"></div>
            <span class="card-label">Workdays</span>
          </div>
          <p class="card-value">{{ stats.workdays || 0 }}</p>
          <p class="card-unit">this month</p>
        </div>
      </div>
      <!-- Attendance Table -->
      <div class="table-container">
        <div class="table-header">
          <h2>Attendance Records</h2>
          <div class="table-info">
            <span class="record-count">{{ filteredAttendance.length }} records</span>
            <span class="page-info">Page {{ currentPage }} of {{ totalPages }}</span>
          </div>
        </div>
        <div v-if="filteredAttendance.length === 0 && !loading" class="empty-state">
          <div class="empty-icon"></div>
          <h3>No Records Found</h3>
          <p>No attendance records match your current filters.</p>
          <button @click="resetFilters" class="btn-primary">Reset Filters</button>
        </div>
        <div v-else-if="!loading" class="table-wrapper">
          <table class="attendance-table">
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
              <tr v-for="record in paginatedRecords" :key="record.id" class="table-row">
                <td>
                  <div class="date-cell">
                    <span class="date-main">{{ formatDate(record.date) }}</span>
                  </div>
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
                    {{ formatHours(record.hoursWorked || record.total_hours) }}
                  </div>
                </td>
                <td>
                  <span :class="['status-badge-table', getStatusClass(record.status)]">
                    {{ formatStatus(record.status) }}
                  </span>
                </td>
                <td>
                  <span class="notes-cell">{{ record.notes || '-' }}</span>
                </td>
              </tr>
            </tbody>
          </table>
          <!-- Pagination Controls -->
          <div class="pagination">
            <button
              @click="goToPage(1)"
              :disabled="currentPage === 1"
              class="pagination-btn prev-first"
            >
              First
            </button>
            <button
              @click="goToPage(currentPage - 1)"
              :disabled="currentPage === 1"
              class="pagination-btn prev"
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
              class="pagination-btn next"
            >
              Next
            </button>
            <button
              @click="goToPage(totalPages)"
              :disabled="currentPage === totalPages"
              class="pagination-btn next-last"
            >
              Last
            </button>
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
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Loading attendance records...</p>
      </div>
      <!-- Error State -->
      <div v-if="error" class="error-message">
        <div class="error-icon"></div>
        <p>{{ error }}</p>
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
      return filtered.sort((a, b) => new Date(b.date) - new Date(a.date))
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
* {
  box-sizing: border-box;
}

.attendance-view {
  padding: 2rem;
  max-width: 1600px;
  margin: 0 auto;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  background: #f8f9fc;
  min-height: 100vh;
}

/* Header Styles */
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem;
  border-radius: 20px;
  box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
  margin-bottom: 2rem;
  color: white;
  position: relative;
  overflow: hidden;
}

.header::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
  animation: pulse 15s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

.header-left {
  z-index: 1;
}

.title {
  margin: 0 0 0.5rem 0;
  font-size: 2.5rem;
  font-weight: 700;
  letter-spacing: -0.5px;
}

.subtitle {
  margin: 0;
  font-size: 1.1rem;
  opacity: 0.9;
  font-weight: 300;
}

.header-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 1rem;
  z-index: 1;
}

.clock-buttons {
  display: flex;
  gap: 0.75rem;
}

.clock-btn {
  padding: 0.875rem 1.75rem;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.clock-btn.in {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.clock-btn.out {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.clock-btn.reset {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
}

.clock-btn:hover:not(:disabled) {
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
}

.clock-btn:active:not(:disabled) {
  transform: translateY(-1px);
}

.clock-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.status-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  border-radius: 50px;
  font-weight: 600;
  font-size: 0.95rem;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.status-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  animation: pulse-dot 2s ease-in-out infinite;
}

@keyframes pulse-dot {
  0%, 100% { transform: scale(1); opacity: 1; }
  50% { transform: scale(1.2); opacity: 0.8; }
}

.status-badge.present .status-dot {
  background: #10b981;
  box-shadow: 0 0 10px #10b981;
}

.status-badge.completed .status-dot {
  background: #3b82f6;
  box-shadow: 0 0 10px #3b82f6;
}

.status-badge.absent .status-dot {
  background: #ef4444;
  box-shadow: 0 0 10px #ef4444;
}

/* Filters Section */
.filters-section {
  margin-bottom: 2rem;
}

.filters-container {
  display: flex;
  gap: 1rem;
  background: white;
  padding: 1.5rem;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  flex-wrap: wrap;
  align-items: flex-end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  min-width: 180px;
  flex: 1;
}

.filter-group label {
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
}

.filter-group input,
.filter-group select {
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-size: 0.95rem;
  transition: all 0.2s;
  background: #f9fafb;
}

.filter-group input:focus,
.filter-group select:focus {
  outline: none;
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-actions {
  display: flex;
  gap: 0.75rem;
}

.btn-secondary,
.btn-export {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  font-size: 0.95rem;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
}

.btn-secondary:hover {
  background: #e5e7eb;
  transform: translateY(-2px);
}

.btn-export {
  background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
}

.btn-export:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(6, 182, 212, 0.4);
}

/* Summary Cards */
.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.card {
  background: white;
  padding: 1.75rem;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  border: 1px solid #f0f0f0;
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.card-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.card-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

.card-icon.blue {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: white;
}

.card-icon.green {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
}

.card-icon.orange {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
}

.card-icon.purple {
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
  color: white;
}

.card-label {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.card-value {
  margin: 0 0 0.25rem 0;
  font-size: 2.5rem;
  font-weight: 700;
  color: #1f2937;
  line-height: 1;
}

.card-unit {
  margin: 0;
  font-size: 0.875rem;
  color: #9ca3af;
  font-weight: 400;
}

/* Table Container */
.table-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #f0f0f0;
  background: #fafbfc;
}

.table-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: #1f2937;
}

.table-info {
  display: flex;
  gap: 1rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #6b7280;
}

.empty-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  background: #f3f4f6;
  margin: 0 auto 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.empty-state h3 {
  margin: 0 0 0.5rem 0;
  font-size: 1.25rem;
  color: #1f2937;
}

.empty-state p {
  margin: 0 0 2rem 0;
  font-size: 1rem;
}

.table-wrapper {
  position: relative;
}

.attendance-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.95rem;
}

.attendance-table th,
.attendance-table td {
  padding: 1.25rem 1rem;
  text-align: left;
  border-bottom: 1px solid #f0f0f0;
}

.attendance-table th {
  background: #fafbfc;
  font-weight: 600;
  color: #374151;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.05em;
}

.attendance-table tr:hover {
  background: #f9fafb;
}

.date-cell {
  font-weight: 500;
  color: #1f2937;
}

.time-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #4b5563;
}

.hours-cell {
  font-weight: 600;
  color: #1f2937;
}

.status-badge-table {
  padding: 0.375rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}

.status-badge-table.present {
  background: #d1fae5;
  color: #065f46;
}

.status-badge-table.absent {
  background: #fee2e2;
  color: #991b1b;
}

.status-badge-table.late {
  background: #fef3c7;
  color: #92400e;
}

.status-badge-table.early_leave {
  background: #cffafe;
  color: #0e7490;
}

.notes-cell {
  color: #6b7280;
  font-style: italic;
}

/* Pagination */
.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.5rem 2rem;
  background: #fafbfc;
  border-top: 1px solid #f0f0f0;
}

.pagination-btn {
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.875rem;
  color: #374151;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.pagination-btn:hover:not(:disabled) {
  background: #f3f4f6;
  border-color: #9ca3af;
  transform: translateY(-1px);
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
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  background: white;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.875rem;
  color: #374151;
  min-width: 40px;
  text-align: center;
}

.pagination-number:hover:not(.active) {
  background: #f3f4f6;
  border-color: #9ca3af;
}

.pagination-number.active {
  background: #667eea;
  color: white;
  border-color: #667eea;
}

.per-page-select {
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.875rem;
  background: white;
}

/* Loading and Error States */
.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
  color: #6b7280;
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
  background: #fee2e2;
  color: #991b1b;
  padding: 2rem;
  border-radius: 12px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  margin: 2rem;
}

.error-icon {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: #fecaca;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

/* Responsive Design */
@media (max-width: 768px) {
  .attendance-view {
    padding: 1rem;
  }

  .header {
    flex-direction: column;
    gap: 1.5rem;
    text-align: center;
  }

  .clock-buttons {
    flex-direction: column;
    width: 100%;
  }

  .clock-btn {
    width: 100%;
    justify-content: center;
  }

  .filters-container {
    flex-direction: column;
    padding: 1rem;
  }

  .filter-group {
    min-width: auto;
  }

  .summary-cards {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
  }

  .table-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }

  .attendance-table {
    font-size: 0.85rem;
  }

  .attendance-table th,
  .attendance-table td {
    padding: 0.75rem 0.5rem;
  }

  .pagination {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }

  .pagination-numbers {
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .summary-cards {
    grid-template-columns: 1fr;
  }

  .table-info {
    flex-direction: column;
    gap: 0.5rem;
    align-items: center;
  }
}
</style>