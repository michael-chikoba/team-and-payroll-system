<template>
  <div class="manager-dashboard">

    <!-- ── Main (header card lives inside here now) ───── -->
    <div class="dashboard-main">

      <!-- ── Header Card ─────────────────────────────── -->
      <div class="dashboard-header-card">
        <div class="header-card-accent"></div>
        <div class="user-greeting">
          <div class="avatar-section">
            <div class="avatar">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
              </svg>
            </div>
            <div class="user-info">
              <h1 class="greeting">Welcome back, {{ getManagerName() }}!</h1>
              <p class="subtitle">Manager Dashboard &bull; Oversee your team in real-time</p>
              <div class="role-meta">
                <span class="role-badge">Manager View</span>
              </div>
            </div>
          </div>
          <div class="date-badge">
            <div class="date-content">
              <span class="day">{{ currentDay }}</span>
              <div class="date-details">
                <span class="date-num">{{ currentDateNum }}</span>
                <span class="month-year">{{ currentMonthYear }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Auth / Permission / Loading / Error guards ── -->
      <div v-if="!authStore.isAuthenticated" class="error-message">
        Please log in to access the dashboard.
      </div>

      <div v-else-if="!authStore.isManager" class="error-message">
        You don't have permission to access this page.
      </div>

      <div v-else-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Loading dashboard...</p>
      </div>

      <div v-else-if="error" class="error-message">
        <h3>Error Loading Dashboard</h3>
        <p>{{ error }}</p>
        <button @click="retryFetch" class="btn-primary">Retry</button>
      </div>

      <!-- ── Dashboard Content ───────────────────────── -->
      <div v-else class="dashboard-content">

        <!-- 1. Metrics Overview -->
        <div class="metrics-section">
          <h2>Team Metrics</h2>
          <div class="metrics-grid">
            <div class="metric-card" style="--accent:#6366f1;">
              <div class="metric-icon-wrap" style="background:rgba(99,102,241,0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                  <circle cx="9" cy="7" r="4"></circle>
                  <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                  <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
              </div>
              <div class="metric-value">{{ overview.teamSize }}</div>
              <div class="metric-label">Total Staff</div>
            </div>

            <div class="metric-card" style="--accent:#f59e0b;">
              <div class="metric-icon-wrap" style="background:rgba(245,158,11,0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                  <line x1="16" y1="2" x2="16" y2="6"></line>
                  <line x1="8" y1="2" x2="8" y2="6"></line>
                  <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
              </div>
              <div class="metric-value">{{ overview.pendingLeaves }}</div>
              <div class="metric-label">Pending Leaves</div>
            </div>

            <div class="metric-card" style="--accent:#10b981;">
              <div class="metric-icon-wrap" style="background:rgba(16,185,129,0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
              </div>
              <div class="metric-value">{{ overview.attendanceRate }}%</div>
              <div class="metric-label">Attendance Rate</div>
            </div>

            <div class="metric-card" style="--accent:#3b82f6;">
              <div class="metric-icon-wrap" style="background:rgba(59,130,246,0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <circle cx="12" cy="12" r="10"></circle>
                  <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
              </div>
              <div class="metric-value">{{ overview.presentCount }}</div>
              <div class="metric-label">Present Today</div>
            </div>
          </div>
        </div>

        <!-- 2. Attendance Summary & List -->
        <div class="attendance-summary-section">
          <div class="attendance-header">
            <h2>Today's Attendance</h2>
            <div class="header-actions">
              <span class="last-updated" v-if="lastUpdated">Updated: {{ lastUpdated }}</span>
              <button @click="refreshAttendance" class="btn-refresh" :disabled="loadingAttendance">
                <svg v-if="!loadingAttendance" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path>
                  <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                </svg>
                <span v-if="loadingAttendance" class="spinner-small"></span>
                {{ loadingAttendance ? 'Syncing...' : 'Refresh' }}
              </button>
            </div>
          </div>

          <!-- Mini Stats -->
          <div class="attendance-stats">
            <div class="attendance-stat present">
              <span class="stat-value">{{ overview.presentCount }}</span>
              <span class="stat-label">Present</span>
            </div>
            <div class="attendance-stat late">
              <span class="stat-value">{{ overview.lateCount }}</span>
              <span class="stat-label">Late</span>
            </div>
            <div class="attendance-stat absent">
              <span class="stat-value">{{ overview.absentCount }}</span>
              <span class="stat-label">Absent</span>
            </div>
            <div class="attendance-stat leave">
              <span class="stat-value">{{ overview.onLeaveCount }}</span>
              <span class="stat-label">On Leave</span>
            </div>
          </div>

          <!-- Detailed List -->
          <div class="attendance-details">
            <h3>Team Status</h3>

            <div v-if="attendanceData.length === 0" class="empty-list">
              No attendance records found for today.
            </div>

            <div v-else class="attendance-list-container">
              <div class="list-header">
                <div class="col-name">Employee</div>
                <div class="col-status">Status</div>
                <div class="col-time">Check In</div>
                <div class="col-time">Check Out</div>
                <div class="col-hrs">Hrs</div>
              </div>

              <div v-for="emp in attendanceData" :key="emp.id" class="list-row">
                <div class="col-name">
                  <div class="avatar-small">{{ getInitials(emp.full_name) }}</div>
                  <div class="name-info">
                    <span class="emp-name">{{ emp.full_name }}</span>
                    <span class="emp-pos">{{ emp.position }}</span>
                  </div>
                </div>
                <div class="col-status">
                  <span class="status-badge" :class="getStatusClass(emp.status)">{{ formatStatus(emp.status) }}</span>
                  <span v-if="emp.is_late" class="late-indicator" title="Late Arrival">⚠️</span>
                </div>
                <div class="col-time">{{ formatTime(emp.clock_in) }}</div>
                <div class="col-time">{{ formatTime(emp.clock_out) }}</div>
                <div class="col-hrs"><strong>{{ formatHours(emp.total_hours) }}</strong></div>
              </div>
            </div>

            <div class="view-all-link">
              <router-link to="/manager/attendance">View Full Report →</router-link>
            </div>
          </div>
        </div>

        <!-- 3. Pending Leave Approvals -->
        <div class="activities-section">
          <h2>Pending Leave Approvals</h2>
          <div class="pending-leaves-list" v-if="pendingLeaves.length > 0">
            <div v-for="leave in pendingLeaves" :key="leave.id" class="leave-item">
              <div class="leave-header">
                <div class="employee-info">
                  <span class="employee-name">{{ leave.employee?.user?.name || 'Unknown' }}</span>
                  <span class="leave-type">{{ formatLeaveType(leave.type) }}</span>
                </div>
                <span class="leave-dates">{{ formatDate(leave.start_date) }} – {{ formatDate(leave.end_date) }}</span>
              </div>
              <div class="leave-details-text">
                <p class="leave-reason">"{{ leave.reason || 'No reason provided' }}"</p>
                <span class="leave-days">{{ leave.number_of_days }} days</span>
              </div>
              <div class="leave-actions">
                <button @click="approveLeave(leave.id)" class="btn-approve">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                  Approve
                </button>
                <button @click="rejectLeave(leave.id)" class="btn-reject">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                  Reject
                </button>
              </div>
            </div>
          </div>
          <div v-else class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            <p>All caught up! No pending leaves.</p>
          </div>
        </div>

        <!-- 4. Quick Links -->
        <div class="quick-links-section">
          <h2>Quick Actions</h2>
          <div class="quick-links-grid">
            <router-link to="/manager/employees" class="quick-link-card">
              <div class="ql-icon" style="background:#eff6ff;color:#3b82f6;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
              </div>
              <span>View Team</span>
            </router-link>
            <router-link to="/manager/leave-approvals" class="quick-link-card">
              <div class="ql-icon" style="background:#fffbeb;color:#f59e0b;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
              </div>
              <span>Leave Calendar</span>
            </router-link>
            <router-link to="/manager/attendance" class="quick-link-card">
              <div class="ql-icon" style="background:#ecfdf5;color:#10b981;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
              </div>
              <span>Attendance Reports</span>
            </router-link>
            <router-link to="/manager/reports" class="quick-link-card">
              <div class="ql-icon" style="background:#f5f3ff;color:#8b5cf6;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
              </div>
              <span>Export Data</span>
            </router-link>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

export default {
  name: 'ManagerDashboard',
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  data() {
    const now = new Date()
    return {
      overview: {
        teamSize: 0,
        pendingLeaves: 0,
        attendanceRate: 0,
        presentCount: 0,
        absentCount: 0,
        lateCount: 0,
        onLeaveCount: 0
      },
      attendanceData: [],
      pendingLeaves: [],
      allEmployees: [],
      loading: false,
      loadingAttendance: false,
      error: null,
      lastUpdated: null,
      today: new Date().toISOString().split('T')[0],
      pollInterval: null,
      currentDay: now.toLocaleDateString('en-US', { weekday: 'long' }),
      currentDateNum: now.getDate(),
      currentMonthYear: now.toLocaleDateString('en-US', { month: 'short', year: 'numeric' })
    }
  },
  mounted() {
    if (this.authStore.isAuthenticated && this.authStore.isManager) {
      this.initializeDashboard()
    }
  },
  beforeUnmount() {
    if (this.pollInterval) clearInterval(this.pollInterval)
  },
  methods: {
    async initializeDashboard() {
      this.loading = true
      this.error = null
      try {
        await Promise.all([this.fetchGeneralStats(), this.fetchAttendanceData()])
        this.pollInterval = setInterval(() => { this.refreshAttendance() }, 60000)
      } catch (err) {
        this.handleError(err)
      } finally {
        this.loading = false
      }
    },

    async fetchGeneralStats() {
      try {
        const response = await axios.get('/api/dashboard')
        const data = response.data
        this.pendingLeaves = data.pending_leaves || []
        this.overview.pendingLeaves = data.stats?.pending_leave_approvals || this.pendingLeaves.length
        if (data.team_members) {
          this.allEmployees = data.team_members
          this.overview.teamSize = this.allEmployees.length
        }
      } catch (err) {
        console.error('Error fetching general stats:', err)
      }
    },

    async fetchAttendanceData() {
      try {
        const response = await axios.get(`/api/manager/attendance/team-status?date=${this.today}`)
        const data = response.data
        if ('success' in data && !data.success) throw new Error(data.message || 'Failed to fetch attendance')
        this.processAttendanceResponse(data)
        this.lastUpdated = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
      } catch (err) {
        console.error('Attendance fetch error:', err)
      }
    },

    async refreshAttendance() {
      this.loadingAttendance = true
      await this.fetchAttendanceData()
      this.loadingAttendance = false
    },

    processAttendanceResponse(data) {
      const attendances = data.attendances || []
      const employeeMap = new Map()

      attendances.forEach((record) => {
        if (record.date === this.today) {
          const employeeId = record.employee_id
          if (!employeeId) return
          if (!employeeMap.has(employeeId) || (record.id && record.id > employeeMap.get(employeeId).id)) {
            employeeMap.set(employeeId, {
              id: record.id,
              employee_id: employeeId,
              full_name: record.full_name || 'Unknown Employee',
              employee_number: record.employee_number || 'N/A',
              department: record.department || 'Unassigned',
              position: record.position || 'N/A',
              status: record.status || 'absent',
              clock_in: record.clock_in || null,
              clock_out: record.clock_out || null,
              total_hours: record.total_hours || 0,
              date: record.date,
              is_late: record.is_late || record.status === 'late',
              minutes_late: record.minutes_late || 0
            })
          }
        }
      })

      this.attendanceData = Array.from(employeeMap.values())
      this.calculateOverview(data.summary || {}, this.attendanceData)
    },

    calculateOverview(apiSummary, processedData) {
      const totalEmployees = this.allEmployees.length > 0
        ? this.allEmployees.length
        : (apiSummary.total_employees || processedData.length)

      const presentCount = processedData.filter(e => ['present', 'completed'].includes(e.status)).length
      const lateCount = processedData.filter(e => e.status === 'late').length
      const onLeaveCount = processedData.filter(e => ['on_leave', 'onleave'].includes(e.status)).length
      const absentCount = Math.max(0, totalEmployees - (presentCount + lateCount + onLeaveCount))

      this.overview.teamSize = totalEmployees
      this.overview.presentCount = presentCount
      this.overview.lateCount = lateCount
      this.overview.onLeaveCount = onLeaveCount
      this.overview.absentCount = absentCount
      this.overview.attendanceRate = totalEmployees > 0
        ? Math.round(((presentCount + lateCount) / totalEmployees) * 100) : 0
    },

    async approveLeave(leaveId) {
      if (!confirm('Approve this leave request?')) return
      try {
        await axios.post(`/api/manager/leaves/${leaveId}/approve`)
        this.fetchGeneralStats()
      } catch (err) {
        alert('Failed to approve: ' + (err.response?.data?.message || err.message))
      }
    },

    async rejectLeave(leaveId) {
      const reason = prompt('Reason for rejection:')
      if (!reason) return
      try {
        await axios.post(`/api/manager/leaves/${leaveId}/reject`, { reason })
        this.fetchGeneralStats()
      } catch (err) {
        alert('Failed to reject: ' + (err.response?.data?.message || err.message))
      }
    },

    retryFetch() { this.initializeDashboard() },

    handleError(err) {
      if (err.response?.status === 401) {
        this.authStore.clearAuth()
        this.$router.push('/login')
      } else {
        this.error = err.response?.data?.message || err.message || 'Failed to load dashboard.'
      }
    },

    getManagerName() {
      return this.authStore.user?.name?.split(' ')[0] || 'Manager'
    },
    getInitials(name) {
      return name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '??'
    },
    formatDate(d) {
      return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
    },
    formatTime(t) {
      if (!t) return '--:--'
      try {
        if (t.includes('AM') || t.includes('PM')) return t
        const date = t.includes('T') ? new Date(t) : new Date(`2000-01-01T${t}`)
        return isNaN(date) ? t : date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
      } catch { return t }
    },
    formatHours(h) {
      const num = parseFloat(h) || 0
      const hrs = Math.floor(num)
      const mins = Math.round((num - hrs) * 60)
      return `${hrs}h ${mins}m`
    },
    formatStatus(s) {
      const map = {
        present: 'Present', completed: 'Completed',
        absent: 'Absent', late: 'Late',
        on_leave: 'On Leave', onleave: 'On Leave'
      }
      return map[s] || s
    },
    formatLeaveType(type) {
      return type ? type.replace(/_/g, ' ').toUpperCase() : 'LEAVE'
    },
    getStatusClass(s) {
      if (['present', 'completed'].includes(s)) return 'status-present'
      if (s === 'absent') return 'status-absent'
      if (s === 'late') return 'status-late'
      if (['on_leave', 'onleave'].includes(s)) return 'status-leave'
      return 'status-unknown'
    }
  }
}
</script>

<style scoped>
/* ── Base ─────────────────────────────────────────────── */
.manager-dashboard {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
}

/* ── Main wrapper ─────────────────────────────────────── */
.dashboard-main {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1.5rem 2rem 3rem;
}

/* ── Header Card ──────────────────────────────────────── */
.dashboard-header-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem 1.75rem;
  box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.03);
  border: 1px solid #e2e8f0;
  margin-bottom: 1.25rem;
  position: relative;
  overflow: hidden;
}

/* Gradient accent bar — blue-to-indigo for manager context */
.header-card-accent {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
 
}

/* Subtle decorative glow */
.dashboard-header-card::after {
  content: '';
  position: absolute;
  top: -20px;
  right: -20px;
  width: 160px;
  height: 160px;
  background: radial-gradient(circle, rgba(59, 130, 246, 0.05) 0%, transparent 70%);
  pointer-events: none;
}

.user-greeting {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1.5rem;
}

.avatar-section {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.avatar {
  width: 52px;
  height: 52px;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
  flex-shrink: 0;
}

.user-info {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.greeting {
  margin: 0;
  font-size: 1.375rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1.2;
}

.subtitle {
  margin: 0;
  color: #64748b;
  font-size: 0.875rem;
}

.role-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.125rem;
}

.role-badge {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  padding: 0.125rem 0.6rem;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 600;
  color: #1d4ed8;
  display: inline-flex;
  align-items: center;
}

/* ── Date badge (alternative: all on one line) ───────── */
.date-badge {
  background: #f8fafc; 
  border: 1px solid #e2e8f0;
  border-radius: 12px; 
  padding: 0.75rem 1.125rem;
  min-width: 130px; 
  flex-shrink: 0;
}

.date-content { 
  display: flex; 
  align-items: center; 
  gap: 0.5rem; 
  white-space: nowrap; /* Prevent wrapping */
}

.day { 
  font-size: 1rem; 
  font-weight: 700; 
  color: #8b5cf6; 
  padding-right: 0.25rem;
}

.date-details { 
  display: flex; 
  align-items: baseline; /* Align text baselines */
  gap: 0.25rem;
}

.date-num { 
  font-size: 1rem; 
  font-weight: 700; 
  color: #1e293b; 
}

.month-year { 
  font-size: 0.8rem; 
  color: #94a3b8; 
  font-weight: 500; 
}

/* Add a separator if desired */
.date-num::after {
  content: '';
  display: inline-block;
  width: 1px;
  height: 12px;
  background: #e2e8f0;
  margin: 0 0.5rem;
  vertical-align: middle;
}
/* ── Dashboard Content ────────────────────────────────── */
.dashboard-content {
  display: flex;
  flex-direction: column;
  gap: 1.75rem;
}

/* ── Section card base ────────────────────────────────── */
.metrics-section,
.attendance-summary-section,
.activities-section,
.quick-links-section {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0;
  padding: 1.5rem;
}

h2 {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0 0 1.25rem 0;
  color: #334155;
}

/* ── 1. Metrics ───────────────────────────────────────── */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.25rem;
}

.metric-card {
  padding: 1.25rem;
  background: #f8fafc;
  border-radius: 12px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  border: 1px solid #e2e8f0;
  position: relative;
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}

.metric-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px -4px rgba(0,0,0,0.08);
  border-color: var(--accent);
}

.metric-card::before { display: none; }

.metric-icon-wrap {
  width: 44px; height: 44px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 0.75rem;
}

.metric-value {
  font-size: 1.8rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.1;
  margin-bottom: 0.25rem;
}

.metric-label {
  font-size: 0.8rem;
  color: #64748b;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

/* ── 2. Attendance ────────────────────────────────────── */
.attendance-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
}

.header-actions { display: flex; align-items: center; gap: 1rem; }
.last-updated   { font-size: 0.78rem; color: #94a3b8; }

.attendance-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-bottom: 1.75rem;
}

.attendance-stat {
  padding: 0.875rem 1rem;
  border-radius: 10px;
  text-align: center;
  border: 1px solid transparent;
}

.attendance-stat .stat-value { display: block; font-size: 1.5rem; font-weight: 700; }
.attendance-stat .stat-label {
  font-size: 0.75rem; text-transform: uppercase;
  letter-spacing: 0.04em; margin-top: 0.2rem; font-weight: 600;
}

/* .attendance-stat.present { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
.attendance-stat.late    { background: #fef3c7; color: #92400e; border-color: #fde68a; }
.attendance-stat.absent  { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
.attendance-stat.leave   { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; } */

.attendance-details h3 {
  font-size: 0.8rem;
  text-transform: uppercase;
  color: #64748b;
  letter-spacing: 0.06em;
  font-weight: 700;
  margin-bottom: 0.875rem;
}

.attendance-list-container {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
}

.list-header, .list-row {
  display: grid;
  grid-template-columns: 2fr 1.2fr 1fr 1fr 0.8fr;
  padding: 0.75rem 1rem;
  align-items: center;
  gap: 1rem;
}

.list-header {
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  font-size: 0.7rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.list-row {
  border-bottom: 1px solid #f1f5f9;
  font-size: 0.875rem;
  background: white;
  transition: background 0.15s;
}
.list-row:last-child { border-bottom: none; }
.list-row:hover { background: #f8fafc; }

.col-name { display: flex; align-items: center; gap: 0.75rem; }

.avatar-small {
  width: 34px; height: 34px;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: white; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.72rem; font-weight: 700; flex-shrink: 0;
}

.name-info { display: flex; flex-direction: column; }
.emp-name { font-weight: 600; color: #1e293b; font-size: 0.875rem; }
.emp-pos  { font-size: 0.72rem; color: #64748b; }

.col-status { display: flex; align-items: center; gap: 0.4rem; }

.status-badge {
  padding: 0.25rem 0.65rem;
  border-radius: 20px;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: capitalize;
}
.status-present { background: #d1fae5; color: #065f46; }
.status-late    { background: #fef3c7; color: #92400e; }
.status-absent  { background: #fee2e2; color: #991b1b; }
.status-leave   { background: #eff6ff; color: #1e40af; }

.col-time { color: #475569; font-variant-numeric: tabular-nums; font-size: 0.875rem; }
.col-hrs  { text-align: right; color: #1e293b; font-size: 0.875rem; }

.view-all-link { margin-top: 1rem; text-align: center; }
.view-all-link a {
  color: #6366f1; text-decoration: none;
  font-size: 0.875rem; font-weight: 600; transition: color 0.2s;
}
.view-all-link a:hover { color: #4f46e5; }

/* ── 3. Pending Leaves ────────────────────────────────── */
.leave-item {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 1rem 1.25rem;
  margin-bottom: 0.75rem;
  background: #f8fafc;
  transition: border-color 0.2s;
}
.leave-item:last-child { margin-bottom: 0; }
.leave-item:hover { border-color: #cbd5e1; }

.leave-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 0.5rem;
}

.employee-name { font-weight: 700; color: #1e293b; display: block; font-size: 0.95rem; }
.leave-type {
  font-size: 0.7rem; color: #4f46e5; font-weight: 700;
  background: #e0e7ff; padding: 2px 7px; border-radius: 5px;
  margin-top: 0.25rem; display: inline-block;
}
.leave-dates { font-size: 0.82rem; color: #64748b; white-space: nowrap; }

.leave-details-text {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.875rem;
}
.leave-reason { font-style: italic; color: #475569; font-size: 0.875rem; margin: 0; }
.leave-days   { font-weight: 700; font-size: 0.875rem; color: #1e293b; white-space: nowrap; }

.leave-actions { display: flex; gap: 0.5rem; }

.btn-approve, .btn-reject {
  display: flex; align-items: center; gap: 0.35rem;
  padding: 0.4rem 1rem; border-radius: 8px;
  cursor: pointer; font-size: 0.82rem; font-weight: 600;
  border: none; transition: all 0.2s;
}
.btn-approve { background: #10b981; color: white; }
.btn-approve:hover { background: #059669; }
.btn-reject  { background: #ef4444; color: white; }
.btn-reject:hover { background: #dc2626; }

/* ── 4. Quick Links ───────────────────────────────────── */
.quick-links-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 1rem;
}

.quick-link-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 1.5rem 1rem;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  text-decoration: none;
  color: #334155;
  font-weight: 600;
  font-size: 0.875rem;
  text-align: center;
  transition: all 0.2s;
}
.quick-link-card:hover {
  background: white;
  box-shadow: 0 6px 16px rgba(0,0,0,0.07);
  transform: translateY(-2px);
  border-color: #cbd5e1;
  color: #1e293b;
}

.ql-icon {
  width: 44px; height: 44px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  transition: transform 0.2s;
}
.quick-link-card:hover .ql-icon { transform: scale(1.08); }

/* ── Utilities ────────────────────────────────────────── */
.btn-primary {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white; border: none; padding: 0.5rem 1.25rem;
  border-radius: 8px; font-size: 0.875rem; font-weight: 600;
  cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem;
  transition: all 0.2s;
}
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,0.35); }

.btn-refresh {
  background: white; border: 1px solid #e2e8f0;
  padding: 0.4rem 0.9rem; border-radius: 8px;
  font-size: 0.82rem; cursor: pointer;
  display: flex; align-items: center; gap: 0.4rem;
  color: #475569; transition: all 0.2s; font-weight: 500;
}
.btn-refresh:hover:not(:disabled) { background: #f1f5f9; color: #0f172a; border-color: #cbd5e1; }
.btn-refresh:disabled { opacity: 0.6; cursor: not-allowed; }

.spinner {
  width: 40px; height: 40px;
  border: 3px solid #e2e8f0;
  border-top-color: #6366f1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}
.spinner-small {
  width: 14px; height: 14px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #6366f1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  display: inline-block;
}

@keyframes spin { to { transform: rotate(360deg); } }

.loading {
  text-align: center;
  padding: 4rem 1rem;
  color: #64748b;
}

.empty-state {
  text-align: center;
  padding: 2rem 1.5rem;
  color: #94a3b8;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}
.empty-state p { margin: 0; font-size: 0.9rem; }

.empty-list {
  text-align: center;
  padding: 2rem;
  color: #64748b;
  font-size: 0.875rem;
}

.error-message {
  background: #fee2e2; color: #991b1b;
  padding: 1.5rem; border-radius: 10px; text-align: center;
  margin: 1.5rem 0;
}
.error-message h3 { margin: 0 0 0.5rem; }
.error-message p  { margin: 0 0 1rem; }

/* ── Responsive ───────────────────────────────────────── */
@media (max-width: 768px) {
  .dashboard-main { padding: 1rem; }
  .user-greeting  { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .greeting       { font-size: 1.25rem; }
  .attendance-stats { grid-template-columns: repeat(2, 1fr); }

  .list-header { display: none; }
  .list-row {
    grid-template-columns: 1fr 1fr;
    grid-template-areas: "name status" "time hrs";
    gap: 0.5rem;
  }
  .col-name   { grid-area: name; }
  .col-status { grid-area: status; justify-content: flex-end; }
  .col-time:first-of-type { grid-area: time; }
  .col-time:last-of-type  { display: none; }
  .col-hrs    { grid-area: hrs; text-align: right; }
}

@media (max-width: 480px) {
  .metrics-grid     { grid-template-columns: repeat(2, 1fr); }
  .quick-links-grid { grid-template-columns: repeat(2, 1fr); }
  .leave-header     { flex-direction: column; gap: 0.5rem; }
}
</style>