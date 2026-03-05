<template>
  <div class="attendance-monitor-view">

    <!-- ── Header Card ─────────────────────────────── -->
    <div class="dashboard-header-card">
      <div class="header-card-accent"></div>
      <div class="user-greeting">
        <div class="avatar-section">
          <div class="avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M22 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
          </div>
          <div class="user-info">
            <h1 class="greeting">Team Monitor</h1>
            <p class="subtitle">Attendance for {{ formatDate(selectedDate) }}</p>
            <div class="role-meta">
              <span class="role-badge manager">Manager View</span>
            </div>
          </div>
        </div>

        <div class="header-actions">
          <div class="date-picker-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <input
              type="date"
              v-model="selectedDate"
              :max="today"
              @change="fetchData"
              class="date-input"
            />
          </div>
          <button @click="fetchData" class="btn-outline" :disabled="loading">
            <svg v-if="!loading" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
            <span v-else class="spinner-small"></span>
            {{ loading ? 'Loading...' : 'Refresh' }}
          </button>
        </div>
      </div>
    </div>

    <div class="dashboard-content">

      <!-- Error State -->
      <div v-if="error" class="empty-state error-state">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <p>{{ error }}</p>
        <button @click="retryFetch" class="btn-primary">Try Again</button>
      </div>

      <!-- Loading State -->
      <div v-else-if="loading && !attendanceData.length" class="empty-state">
        <div class="spinner"></div>
        <p>Syncing attendance records...</p>
      </div>

      <!-- Data View -->
      <div v-else>
        <!-- Overview Cards -->
        <div v-if="teamOverview" class="metrics-section">
          <h2>Team Overview</h2>
          <div class="metrics-grid">
            <div class="metric-card" style="--accent:#6366f1;">
              <div class="metric-icon-wrap" style="background:rgba(99,102,241,0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
              </div>
              <div class="metric-value">{{ teamOverview.totalEmployees }}</div>
              <div class="metric-label">Total Staff</div>
            </div>

            <div class="metric-card" style="--accent:#10b981;">
              <div class="metric-icon-wrap" style="background:rgba(16,185,129,0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
              </div>
              <div class="metric-value">{{ teamOverview.presentCount }}</div>
              <div class="metric-label">Present</div>
            </div>

            <div class="metric-card" style="--accent:#ef4444;">
              <div class="metric-icon-wrap" style="background:rgba(239,68,68,0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
              </div>
              <div class="metric-value">{{ teamOverview.absentCount }}</div>
              <div class="metric-label">Absent</div>
            </div>

            <div class="metric-card" style="--accent:#f59e0b;">
              <div class="metric-icon-wrap" style="background:rgba(245,158,11,0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
              </div>
              <div class="metric-value">{{ teamOverview.lateCount }}</div>
              <div class="metric-label">Late</div>
            </div>

            <div class="metric-card" style="--accent:#06b6d4;">
              <div class="metric-icon-wrap" style="background:rgba(6,182,212,0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#06b6d4" stroke-width="2"><path d="M12 1v22M17 5H9.5M17 12h-5M17 19h-5"></path><path d="M5 21h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2z"></path></svg>
              </div>
              <div class="metric-value">{{ teamOverview.totalOvertimeHours }}</div>
              <div class="metric-label">Overtime Hrs</div>
            </div>
          </div>
        </div>

        <!-- Controls Bar -->
        <div class="controls-section">
          <div class="controls-bar">
            <div class="filters-row">
              <div class="filter-group">
                <label>Filter by Status</label>
                <select v-model="activeFilter" class="filter-select">
                  <option value="all">All</option>
                  <option value="present">Present</option>
                  <option value="absent">Absent</option>
                  <option value="late">Late</option>
                </select>
              </div>
            </div>
            <span class="records-count">
              {{ filteredData.length }} team member{{ filteredData.length !== 1 ? 's' : '' }}
            </span>
          </div>
        </div>

        <!-- Employee Grid -->
        <div class="employee-grid">
          <div v-for="emp in filteredData" :key="emp.id" class="employee-card">
            <div class="employee-card-header">
              <div class="employee-info">
                <div class="employee-avatar">
                  {{ getInitials(emp.full_name) }}
                </div>
                <div>
                  <h3 class="employee-name">{{ emp.full_name }}</h3>
                  <p class="employee-position">{{ emp.position || 'Staff' }}</p>
                </div>
              </div>
              <span :class="['status-badge', getStatusClass(emp.status)]">
                <span class="dot"></span>{{ formatStatus(emp.status) }}
              </span>
            </div>

            <div v-if="emp.has_shift" class="shift-badge">
              <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
              Shift: {{ emp.shift?.start_time || 'N/A' }} - {{ emp.shift?.end_time || 'N/A' }}
            </div>

            <div class="attendance-details">
              <div class="detail-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                <span>In: <strong>{{ formatTime(emp.clock_in_time) }}</strong></span>
              </div>
              <div class="detail-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 8 10"></polyline></svg>
                <span>Out: <strong>{{ formatTime(emp.clock_out_time) }}</strong></span>
              </div>
              <div class="detail-item">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <span>Total: <strong>{{ formatHours(emp.hours_worked) }}</strong></span>
              </div>
            </div>

            <!-- Overtime Section -->
            <div v-if="emp.overtime_hours > 0" class="overtime-section">
              <div class="overtime-header">
                <span class="overtime-badge">⚡ Overtime</span>
              </div>
              <div class="overtime-details">
                <div class="overtime-item">
                  <span>Regular</span>
                  <strong>{{ formatHours(emp.regular_hours) }}</strong>
                </div>
                <div class="overtime-item">
                  <span>Overtime</span>
                  <strong class="overtime-value">{{ formatHours(emp.overtime_hours) }}</strong>
                </div>
              </div>
            </div>

            <!-- Late Warning -->
            <div v-if="emp.is_late && emp.minutes_late > 0" class="late-warning">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
              Late by {{ emp.minutes_late }} minutes
            </div>

            <div class="card-footer">
              <button @click="viewHistory(emp)" class="btn-history">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                View History
              </button>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="filteredData.length === 0" class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <p>No employees found for this filter.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- ── History Modal ─────────────────────────────── -->
    <transition name="modal-fade">
      <div v-if="history.visible" class="modal-overlay" @click.self="closeHistory">
        <div class="modal-container wide">

          <div class="modal-header">
            <div class="modal-title-wrap">
              <div class="modal-avatar">{{ getInitials(history.employeeName) }}</div>
              <div>
                <h3 class="modal-name">Attendance History</h3>
                <p class="modal-position">{{ history.employeeName }}</p>
              </div>
            </div>
            <button @click="closeHistory" class="modal-close">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
          </div>

          <div class="modal-body">
            <div class="stats-grid">
              <div class="stat-card">
                <span class="stat-label">Total Days</span>
                <span class="stat-value">{{ history.summary.total_records || 0 }}</span>
              </div>
              <div class="stat-card success">
                <span class="stat-label">Present</span>
                <span class="stat-value">{{ history.summary.present_days || 0 }}</span>
              </div>
              <div class="stat-card danger">
                <span class="stat-label">Absent</span>
                <span class="stat-value">{{ history.summary.absent_days || 0 }}</span>
              </div>
              <div class="stat-card warning">
                <span class="stat-label">Late</span>
                <span class="stat-value">{{ history.summary.late_days || 0 }}</span>
              </div>
              <div class="stat-card info">
                <span class="stat-label">Overtime Hrs</span>
                <span class="stat-value">{{ (history.summary.total_overtime_hours || 0).toFixed(1) }}</span>
              </div>
            </div>

            <div class="table-container">
              <table v-if="history.records.length > 0" class="data-table">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Clock In</th>
                    <th>Clock Out</th>
                    <th>Regular</th>
                    <th>Overtime</th>
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(record, index) in history.records" :key="index">
                    <td>{{ formatDate(record.date) }}</td>
                    <td>
                      <span class="status-badge small" :class="getStatusClass(record.status)">
                        <span class="dot"></span>{{ formatStatus(record.status) }}
                      </span>
                    </td>
                    <td>{{ formatTime(record.clock_in) }}</td>
                    <td>{{ formatTime(record.clock_out) }}</td>
                    <td>{{ formatHours(record.regular_hours || 0) }}</td>
                    <td class="overtime-cell">{{ formatHours(record.overtime_hours || 0) }}</td>
                    <td><strong>{{ formatHours(record.total_hours) }}</strong></td>
                  </tr>
                </tbody>
              </table>
              <div v-else class="empty-state small">
                No history records found.
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button @click="closeHistory" class="btn-primary">Close</button>
          </div>

        </div>
      </div>
    </transition>

    <!-- ── Toast ─────────────────────────────────────── -->
    <transition name="toast-slide">
      <div v-if="toast.show" :class="['toast', toast.type]">
        <svg v-if="toast.type === 'success'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <span>{{ toast.message }}</span>
      </div>
    </transition>

  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

export default {
  name: 'ManagerAttendanceMonitor',
  
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  
  data() {
    return {
      attendanceData: [],
      teamOverview: null,
      loading: false,
      error: null,
      retryCount: 0,
      selectedDate: new Date().toISOString().split('T')[0],
      today: new Date().toISOString().split('T')[0],
      activeFilter: 'all',
      toast: { show: false, message: '', type: 'success' },
      history: {
        visible: false,
        loading: false,
        employeeName: '',
        records: [],
        summary: {}
      }
    }
  },
  
  computed: {
    filteredData() {
      if (this.activeFilter === 'all') return this.attendanceData
      return this.attendanceData.filter(emp => {
        const status = emp.status?.toLowerCase()
        if (this.activeFilter === 'present') return ['present', 'completed'].includes(status)
        if (this.activeFilter === 'absent') return ['absent', 'on_leave'].includes(status)
        return status === this.activeFilter
      })
    }
  },
  
  mounted() {
    this.fetchData()
  },
  
  methods: {
    async fetchData() {
      this.loading = true
      this.error = null
      try {
        const response = await axios.get('/api/manager/attendance/team-status', {
          params: { date: this.selectedDate }
        })
        if (response.data.success) {
          this.processData(response.data)
        } else {
          throw new Error(response.data.message || 'Failed to fetch data')
        }
        this.retryCount = 0
      } catch (err) {
        this.handleError(err)
      } finally {
        this.loading = false
      }
    },
    
    processData(data) {
      const attendances = data.attendances || []
      const summary = data.summary || {}
      this.attendanceData = attendances.map(att => ({
        id: att.id || att.employee_id,
        employee_id: att.employee_id,
        full_name: att.full_name || att.employee_name || 'Unknown',
        position: att.position || 'Staff',
        department: att.department || 'General',
        status: att.status,
        clock_in_time: att.clock_in,
        clock_out_time: att.clock_out,
        hours_worked: att.total_hours || 0,
        regular_hours: att.regular_hours || 0,
        overtime_hours: att.overtime_hours || 0,
        has_shift: att.has_shift || false,
        shift: att.shift || null,
        is_late: att.is_late || false,
        minutes_late: att.minutes_late || 0,
        date: att.date
      }))
      const totalOvertime = this.attendanceData.reduce((sum, emp) => sum + (emp.overtime_hours || 0), 0)
      this.teamOverview = {
        totalEmployees: summary.total_employees || attendances.length,
        presentCount: summary.present || 0,
        absentCount: summary.absent || 0,
        lateCount: summary.late || 0,
        totalOvertimeHours: totalOvertime.toFixed(1)
      }
    },
    
    async viewHistory(employee) {
      this.history.visible = true
      this.history.loading = true
      this.history.employeeName = employee.full_name
      try {
        const date = new Date(this.selectedDate)
        const res = await axios.get(`/api/manager/attendance/${employee.employee_id}/history`, {
          params: { month: date.getMonth() + 1, year: date.getFullYear() }
        })
        this.history.records = res.data.data || []
        const records = this.history.records
        const totalOvertimeHours = records.reduce((sum, r) => sum + (r.overtime_hours || 0), 0)
        this.history.summary = { ...res.data.summary, total_overtime_hours: totalOvertimeHours }
      } catch (e) {
        this.showToast('Could not load history.', 'error')
        this.closeHistory()
      } finally {
        this.history.loading = false
      }
    },
    
    closeHistory() {
      this.history.visible = false
      this.history.records = []
      this.history.summary = {}
    },
    
    retryFetch() {
      if (this.retryCount < 3) { this.retryCount++; this.fetchData() }
      else { this.error = 'Unable to connect to server. Please refresh the page.' }
    },
    
    handleError(err) {
      console.error(err)
      this.error = err.response?.data?.message || 'Failed to load team attendance.'
      this.showToast(this.error, 'error')
    },
    
    showToast(message, type = 'success') {
      this.toast = { show: true, message, type }
      setTimeout(() => { this.toast.show = false }, 3000)
    },
    
    getInitials(name) {
      if (!name || name === 'Unknown') return '??'
      return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
    },
    
    formatDate(d) {
      if (!d) return '-'
      try {
        return new Date(d).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' })
      } catch { return '-' }
    },
    
    formatTime(t) {
      if (!t) return '--:--'
      if (t.includes('M')) return t
      try {
        const date = t.includes('T') ? new Date(t) : new Date(`2000-01-01T${t}`)
        return isNaN(date) ? t : date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true })
      } catch { return t }
    },
    
    formatHours(h) {
      const num = parseFloat(h) || 0
      const hrs = Math.floor(num)
      const mins = Math.round((num - hrs) * 60)
      return `${hrs}h ${mins.toString().padStart(2, '0')}m`
    },
    
    formatStatus(s) {
      const map = { present: 'Present', completed: 'Completed', absent: 'Absent', late: 'Late', on_leave: 'On Leave', pending: 'Pending', approved: 'Approved', rejected: 'Rejected' }
      return map[s?.toLowerCase()] || s || 'Unknown'
    },
    
    getStatusClass(s) {
      const status = s?.toLowerCase()
      const classMap = { present: 'success', completed: 'success', absent: 'danger', on_leave: 'danger', late: 'warning', pending: 'warning', approved: 'success', rejected: 'danger' }
      return classMap[status] || 'neutral'
    }
  }
}
</script>

<style scoped>
/* ── Base ──────────────────────────────────────────── */
.attendance-monitor-view {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
  padding: 1.5rem 2rem 3rem;
  max-width: 1400px;
  margin: 0 auto;
}

/* ── Header Card ─────────────────────────────────── */
.dashboard-header-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem 1.75rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
  border: 1px solid #e2e8f0;
  margin-bottom: 1.25rem;
  position: relative;
  overflow: hidden;
}

.header-card-accent {
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: linear-gradient(90deg, #6366f1, #8b5cf6, #06b6d4);
}

.user-greeting { display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; }
.avatar-section { display: flex; align-items: center; gap: 1rem; }

.avatar {
  width: 52px; height: 52px;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  border-radius: 14px; display: flex; align-items: center; justify-content: center;
  color: white; box-shadow: 0 4px 12px rgba(99,102,241,0.3); flex-shrink: 0;
}

.user-info { display: flex; flex-direction: column; gap: 0.2rem; }
.greeting  { margin: 0; font-size: 1.375rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
.subtitle  { margin: 0; color: #64748b; font-size: 0.875rem; }
.role-meta { margin-top: 0.125rem; }

.role-badge.manager {
  background: #eff6ff; border: 1px solid #bfdbfe;
  padding: 0.125rem 0.6rem; border-radius: 8px;
  font-size: 0.7rem; font-weight: 600; color: #1e40af;
}

.header-actions {
  display: flex; gap: 0.5rem; flex-shrink: 0; align-items: center;
}

.date-picker-wrapper {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.4rem 0.875rem;
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;
  transition: border-color 0.2s;
}
.date-picker-wrapper:focus-within {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}
.date-picker-wrapper svg { color: #64748b; }

.date-input {
  padding: 0; border: none; background: transparent;
  color: #1e293b; font-size: 0.875rem; font-family: inherit;
}
.date-input:focus { outline: none; }

/* ── Buttons ─────────────────────────────────────── */
.btn-primary {
  display: flex; align-items: center; gap: 0.4rem;
  background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;
  border: none; padding: 0.5rem 1.25rem; border-radius: 8px;
  font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
  font-family: inherit;
}
.btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(99,102,241,0.4); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

.btn-outline {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.45rem 1rem; background: white; border: 1px solid #e2e8f0;
  color: #475569; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-outline:hover:not(:disabled) { background: #f8fafc; border-color: #6366f1; color: #6366f1; }
.btn-outline:disabled { opacity: 0.5; cursor: not-allowed; }

/* ── Dashboard Content ───────────────────────────── */
.dashboard-content { display: flex; flex-direction: column; gap: 1.25rem; }

/* ── Metrics Section ─────────────────────────────── */
.metrics-section {
  background: white; border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  border: 1px solid #e2e8f0; padding: 1.5rem;
}

h2 { font-size: 1rem; font-weight: 700; margin: 0 0 1.25rem 0; color: #334155; letter-spacing: -0.01em; }

.metrics-grid {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem;
}

.metric-card {
  padding: 1.25rem; background: #f8fafc; border-radius: 12px;
  display: flex; flex-direction: column; align-items: center; text-align: center;
  border: 1px solid #e2e8f0;
  transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
}
.metric-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px -4px rgba(0,0,0,0.1);
  border-color: var(--accent);
}

.metric-icon-wrap {
  width: 40px; height: 40px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;
}
.metric-value { font-size: 2rem; font-weight: 800; color: #0f172a; line-height: 1; margin-bottom: 0.3rem; }
.metric-label { font-size: 0.72rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; }

/* ── Controls Section ────────────────────────────── */
.controls-section {
  background: white; border-radius: 16px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  border: 1px solid #e2e8f0; padding: 1rem 1.5rem;
}

.controls-bar { display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap; }
.filters-row { display: flex; gap: 0.875rem; flex-wrap: wrap; }
.filter-group { display: flex; flex-direction: column; gap: 0.3rem; }
.filter-group label { font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.06em; }

.filter-select {
  padding: 0.45rem 2rem 0.45rem 0.875rem; border: 1px solid #e2e8f0;
  border-radius: 8px; background: #f8fafc; color: #334155;
  font-size: 0.875rem; font-weight: 500; cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 0.75rem center;
  transition: all 0.2s; font-family: inherit;
}
.filter-select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

.records-count {
  font-size: 0.75rem; font-weight: 700; color: #6366f1;
  background: #eff0fe; padding: 0.25rem 0.8rem; border-radius: 9999px;
  border: 1px solid #c7d2fe;
}

/* ── Employee Grid ───────────────────────────────── */
.employee-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 1.25rem;
}

/* ── Employee Card ───────────────────────────────── */
.employee-card {
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  display: flex;
  flex-direction: column;
}

.employee-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 16px 32px -8px rgba(99,102,241,0.15);
  border-color: #c7d2fe;
}

.employee-card-header {
  padding: 1.25rem;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  background: linear-gradient(135deg, #f8faff 0%, #f1f5f9 100%);
  border-bottom: 1px solid #f1f5f9;
}

.employee-info { display: flex; gap: 0.875rem; align-items: center; }

.employee-avatar {
  width: 46px; height: 46px;
  border-radius: 12px;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: white;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.9rem; font-weight: 700; flex-shrink: 0;
  box-shadow: 0 4px 10px rgba(99,102,241,0.25);
  letter-spacing: 0.03em;
}

.employee-name { margin: 0 0 0.2rem 0; font-size: 0.95rem; font-weight: 700; color: #1e293b; }
.employee-position { margin: 0; font-size: 0.72rem; color: #94a3b8; font-weight: 500; }

.shift-badge {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.45rem 1.25rem;
  background: #f0f9ff; color: #0369a1;
  font-size: 0.75rem; font-weight: 600;
  border-bottom: 1px solid #e0f2fe;
}
.shift-badge svg { color: #0284c7; }

.attendance-details {
  padding: 1rem 1.25rem;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  border-bottom: none;  /* ← add this */
}

.detail-item {
  display: flex; flex-direction: column; gap: 0.25rem;
  padding: 0.6rem 0.75rem;
  background: #f8fafc; border-radius: 8px;
  border: 1px solid #f1f5f9;
  font-size: 0.72rem; color: #64748b;
}

.detail-item svg { color: #94a3b8; margin-bottom: 0.15rem; }
.detail-item strong { color: #1e293b; font-weight: 700; font-size: 0.82rem; }

/* Overtime Section */
.overtime-section {
  padding: 0.875rem 1.25rem;
  background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
  border-top: 1px solid #fde68a;
  border-bottom: 1px solid #fde68a;
}

.overtime-header { margin-bottom: 0.5rem; }
.overtime-badge { font-weight: 700; color: #92400e; font-size: 0.78rem; }

.overtime-details { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; }

.overtime-item {
  display: flex; flex-direction: column; align-items: center;
  padding: 0.5rem; background: rgba(255,255,255,0.6); border-radius: 8px;
  border: 1px solid #fde68a;
}
.overtime-item span { font-size: 0.65rem; color: #92400e; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.2rem; }
.overtime-item strong { font-size: 0.88rem; color: #92400e; }
.overtime-value { color: #b45309 !important; }

/* Late Warning */
.late-warning {
  padding: 0.65rem 1.25rem;
  background: #fff7ed; color: #c2410c;
  font-size: 0.78rem; font-weight: 600;
  display: flex; align-items: center; gap: 0.5rem;
  border-top: 1px solid #fed7aa;
  border-bottom: 1px solid #fed7aa;
}
.late-warning svg { color: #c2410c; flex-shrink: 0; }

/* ── Card Footer — no border, clean ─────────────── */
.card-footer {
  padding: 0.875rem 1.25rem;
  display: flex;
  justify-content: flex-end;
  margin-top: auto;
  border-top: none;  /* ← add this explicitly */
  background: transparent;
}

.btn-history {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.4rem 0.875rem;
  background: transparent;
  border: 1.5px solid #e2e8f0;
  color: #6366f1;
  border-radius: 8px;
  font-size: 0.75rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s;
  font-family: inherit;
}
.btn-history:hover {
  background: #eff0fe;
  border-color: #6366f1;
  transform: translateY(-1px);
  box-shadow: 0 3px 10px rgba(99,102,241,0.15);
}

/* ── Status Badges ───────────────────────────────── */
.status-badge {
  display: inline-flex; align-items: center; gap: 4px;
  padding: 0.25rem 0.7rem; border-radius: 9999px;
  font-size: 0.7rem; font-weight: 700; white-space: nowrap;
}
.status-badge.small { padding: 0.15rem 0.5rem; font-size: 0.65rem; }
.dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
.status-badge.success { background: #dcfce7; color: #166534; }
.status-badge.warning { background: #fef9c3; color: #854d0e; }
.status-badge.danger  { background: #fee2e2; color: #991b1b; }
.status-badge.neutral { background: #f1f5f9; color: #64748b; }

/* ── States ──────────────────────────────────────── */
.spinner {
  width: 40px; height: 40px; border: 3px solid #e2e8f0; border-top-color: #6366f1;
  border-radius: 50%; animation: spin 0.9s linear infinite; margin: 0 auto;
}
.spinner-small {
  display: inline-block; width: 14px; height: 14px;
  border: 2px solid rgba(99,102,241,0.3); border-top-color: #6366f1;
  border-radius: 50%; animation: spin 0.6s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.empty-state {
  text-align: center; padding: 4rem 2rem;
  background: white; border-radius: 16px; border: 1px solid #e2e8f0;
  display: flex; flex-direction: column; align-items: center; gap: 0.875rem; color: #cbd5e1;
}
.empty-state.error-state { background: #fef2f2; border-color: #fecaca; }
.empty-state p { margin: 0; font-size: 0.875rem; color: #94a3b8; }
.empty-state.error-state p { color: #991b1b; }
.empty-state.small { padding: 2rem; }

/* ── Modal ───────────────────────────────────────── */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(15,23,42,0.6);
  backdrop-filter: blur(6px); z-index: 100;
  display: flex; justify-content: center; align-items: center; padding: 1rem;
}

.modal-container {
  background: white; width: 100%; max-width: 720px; max-height: 90vh;
  border-radius: 20px; box-shadow: 0 32px 64px rgba(15,23,42,0.25);
  display: flex; flex-direction: column; overflow: hidden;
  border: 1px solid #e2e8f0; animation: slideUp 0.25s ease-out;
}
.modal-container.wide { max-width: 900px; }

@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

.modal-header {
  padding: 1.5rem 1.75rem;
  background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
  display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;
}

.modal-title-wrap { display: flex; align-items: center; gap: 1rem; }

.modal-avatar {
  width: 48px; height: 48px; background: linear-gradient(135deg, #3b82f6, #6366f1);
  border-radius: 12px; display: flex; align-items: center; justify-content: center;
  font-weight: 900; font-size: 0.75rem; color: white;
  border: 1.5px solid rgba(255,255,255,0.2);
  box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.modal-name     { font-size: 1.15rem; font-weight: 700; color: white; margin: 0; }
.modal-position { font-size: 0.82rem; color: rgba(255,255,255,0.6); margin: 0.2rem 0 0; }

.modal-close {
  width: 36px; height: 36px; border-radius: 50%;
  border: 1.5px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.08);
  color: white; display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.2s;
}
.modal-close:hover { background: rgba(239,68,68,0.5); border-color: transparent; }

.modal-body { padding: 1.5rem 1.75rem; overflow-y: auto; flex: 1; }

.modal-footer {
  padding: 1rem 1.75rem; border-top: 1px solid #f1f5f9;
  background: #f8fafc; display: flex; justify-content: flex-end; gap: 0.75rem; flex-shrink: 0;
}

/* Stats Grid */
.stats-grid {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 0.875rem; margin-bottom: 1.5rem;
}

.stat-card {
  padding: 1rem; border-radius: 10px; text-align: center;
  border: 1px solid #e2e8f0; background: #f8fafc;
}
.stat-card .stat-label { display: block; font-size: 0.68rem; color: #94a3b8; margin-bottom: 0.3rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }
.stat-card .stat-value { display: block; font-size: 1.6rem; font-weight: 800; color: #1e293b; }
.stat-card.success { background: #f0fdf4; border-color: #bbf7d0; }
.stat-card.success .stat-value { color: #166534; }
.stat-card.danger  { background: #fef2f2; border-color: #fecaca; }
.stat-card.danger .stat-value  { color: #991b1b; }
.stat-card.warning { background: #fefce8; border-color: #fef08a; }
.stat-card.warning .stat-value { color: #854d0e; }
.stat-card.info    { background: #f0f9ff; border-color: #bae6fd; }
.stat-card.info .stat-value    { color: #0369a1; }

/* Table */
.table-container { border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }

.data-table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.data-table th {
  text-align: left; padding: 0.75rem 1rem;
  background: #f8fafc; color: #64748b; font-weight: 700;
  font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.05em;
  border-bottom: 1px solid #e2e8f0;
}
.data-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #f1f5f9; color: #334155; }
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: #f8fafc; }
.overtime-cell { color: #b45309; font-weight: 600; }

/* ── Toast ───────────────────────────────────────── */
.toast {
  position: fixed; bottom: 2rem; right: 2rem;
  background: white; padding: 0.875rem 1.25rem; border-radius: 12px;
  box-shadow: 0 12px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.06);
  display: flex; align-items: center; gap: 0.65rem;
  z-index: 200; font-size: 0.875rem; font-weight: 500;
  border-left: 4px solid #10b981;
}
.toast.error   { border-left-color: #ef4444; }
.toast.warning { border-left-color: #f59e0b; }
.toast.success svg { color: #10b981; }
.toast.error   svg { color: #ef4444; }

/* ── Transitions ─────────────────────────────────── */
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.25s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.toast-slide-enter-active, .toast-slide-leave-active { transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1); }
.toast-slide-enter-from, .toast-slide-leave-to { opacity: 0; transform: translateY(16px) scale(0.95); }

/* ── Responsive ──────────────────────────────────── */
@media (max-width: 1024px) {
  .employee-grid { grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); }
}

@media (max-width: 768px) {
  .attendance-monitor-view { padding: 1rem; }
  .user-greeting { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .header-actions { width: 100%; flex-direction: column; align-items: stretch; }
  .date-picker-wrapper, .date-input, .btn-outline { width: 100%; }
  .btn-outline { justify-content: center; }
  .metrics-grid { grid-template-columns: 1fr 1fr; }
  .attendance-details { grid-template-columns: 1fr; gap: 0.5rem; }
  .modal-container { max-height: 100vh; border-radius: 20px 20px 0 0; position: fixed; bottom: 0; }
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .modal-footer { flex-direction: column-reverse; }
  .modal-footer button { width: 100%; justify-content: center; }
  .data-table { font-size: 0.75rem; }
  .data-table th, .data-table td { padding: 0.5rem; }
}

@media (max-width: 480px) {
  .metrics-grid { grid-template-columns: 1fr; }
  .employee-grid { grid-template-columns: 1fr; }
  .filters-row, .filter-group, .filter-select { flex-direction: column; width: 100%; }
  .stats-grid { grid-template-columns: 1fr; }
}
</style>