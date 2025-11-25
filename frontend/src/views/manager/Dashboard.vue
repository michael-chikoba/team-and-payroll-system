<template>
  <div class="manager-dashboard">
    <div class="page-header">
      <h1>Manager Dashboard</h1>
    </div>
    <!-- Authentication Check -->
    <div v-if="!authStore.isAuthenticated" class="error-message">
      Please log in to access the dashboard.
    </div>
    <!-- Permission Check -->
    <div v-else-if="!authStore.isManager" class="error-message">
      You don't have permission to access this page.
    </div>
    <!-- Loading State -->
    <div v-else-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Loading dashboard...</p>
    </div>
    <!-- Error State -->
    <div v-else-if="error" class="error-message">
      <h3>Error Loading Dashboard</h3>
      <p>{{ error }}</p>
      <div class="error-details" v-if="debugInfo">
        <details>
          <summary>Debug Information (click to expand)</summary>
          <pre>{{ debugInfo }}</pre>
        </details>
      </div>
      <button @click="retryFetch" class="btn-primary">Retry</button>
    </div>
    <!-- Dashboard Content -->
    <div v-else class="dashboard-content">
      <!-- Metrics Overview -->
      <div class="metrics-section">
        <h2>Team Metrics</h2>
        <div class="metrics-grid">
          <div class="metric-card">
            <div class="metric-icon">👥</div>
            <div class="metric-value">{{ overview.teamSize }}</div>
            <div class="metric-label">Team Members</div>
          </div>
          <div class="metric-card">
            <div class="metric-icon">⏰</div>
            <div class="metric-value">{{ overview.pendingLeaves }}</div>
            <div class="metric-label">Pending Leaves</div>
          </div>
          <div class="metric-card">
            <div class="metric-icon">📊</div>
            <div class="metric-value">{{ overview.attendanceRate }}%</div>
            <div class="metric-label">Attendance Rate</div>
          </div>
          <div class="metric-card">
            <div class="metric-icon">⏱️</div>
            <div class="metric-value">{{ overview.totalHours }}</div>
            <div class="metric-label">Total Team Hours</div>
          </div>
        </div>
      </div>
      <!-- Team Attendance Summary -->
      <div class="attendance-summary-section" v-if="attendanceSummary">
        <div class="attendance-header">
          <h2>Today's Attendance</h2>
          <span class="attendance-date">{{ formatDate(today) }}</span>
          <button @click="refreshAttendance" class="btn-refresh" :disabled="loadingAttendance">
            <span v-if="loadingAttendance">🔄</span>
            <span v-else>🔄 Refresh</span>
          </button>
        </div>
        <div class="attendance-stats">
          <div class="attendance-stat present">
            <span class="stat-value">{{ attendanceSummary.present || 0 }}</span>
            <span class="stat-label">Present</span>
          </div>
          <div class="attendance-stat absent">
            <span class="stat-value">{{ attendanceSummary.absent || 0 }}</span>
            <span class="stat-label">Absent</span>
          </div>
          <div class="attendance-stat late">
            <span class="stat-value">{{ attendanceSummary.late || 0 }}</span>
            <span class="stat-label">Late</span>
          </div>
          <div class="attendance-stat leave">
            <span class="stat-value">{{ attendanceSummary.onLeave || 0 }}</span>
            <span class="stat-label">On Leave</span>
          </div>
        </div>
      </div>
      <!-- Pending Leave Approvals -->
      <div class="activities-section">
        <h2>Pending Leave Approvals</h2>
        <div class="pending-leaves-list" v-if="pendingLeaves.length > 0">
          <div v-for="leave in pendingLeaves" :key="leave.id" class="leave-item">
            <div class="leave-header">
              <div class="employee-info">
                <span class="employee-name">{{ leave.employee?.user?.name || 'Unknown Employee' }}</span>
                <span class="leave-type">{{ formatLeaveType(leave.type) }}</span>
              </div>
              <span class="leave-dates">
                {{ formatDate(leave.start_date) }} - {{ formatDate(leave.end_date) }}
              </span>
            </div>
            <div class="leave-details">
              <p class="leave-reason">{{ leave.reason || 'No reason provided' }}</p>
              <span class="leave-days">{{ leave.number_of_days || 0 }} days</span>
            </div>
            <div class="leave-actions">
              <button @click="approveLeave(leave.id)" class="btn-approve">✓ Approve</button>
              <button @click="rejectLeave(leave.id)" class="btn-reject">✗ Reject</button>
            </div>
          </div>
        </div>
        <div v-else class="empty-state">
          <p>✅ No pending leave approvals</p>
        </div>
      </div>
      <!-- Quick Links -->
      <div class="quick-links-section">
        <h2>Quick Actions</h2>
        <div class="quick-links-grid">
          <router-link to="/manager/employees" class="quick-link-card">
            <div class="quick-link-icon">👨‍💼</div>
            <span>View Team</span>
          </router-link>
          <router-link to="/manager/leave-approvals" class="quick-link-card">
            <div class="quick-link-icon">📅</div>
            <span>Leave Approvals</span>
          </router-link>
          <router-link to="/manager/attendance" class="quick-link-card">
            <div class="quick-link-icon">📈</div>
            <span>Attendance Report</span>
          </router-link>
          <router-link to="/manager/reports/team" class="quick-link-card">
            <div class="quick-link-icon">📋</div>
            <span>Team Reports</span>
          </router-link>
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
    return {
      overview: {
        teamSize: 0,
        pendingLeaves: 0,
        attendanceRate: 0,
        totalHours: 0
      },
      attendanceSummary: null,
      pendingLeaves: [],
      loading: false,
      loadingAttendance: false,
      error: null,
      debugInfo: null,
      retryCount: 0,
      today: new Date().toISOString().split('T')[0], // Today's date in YYYY-MM-DD format
      pollInterval: null,
      pollIntervalMs: 30000 // Poll every 30 seconds
    }
  },
  
  mounted() {
    this.initializeComponent()
  },

  beforeUnmount() {
    this.stopPolling()
  },
  
  methods: {
    initializeComponent() {
      console.log('=== Manager Dashboard Initialization ===')
      console.log('Authenticated:', this.authStore.isAuthenticated)
      console.log('Is Manager:', this.authStore.isManager)
      console.log('User:', this.authStore.user)
      console.log('Token exists:', !!this.authStore.token)
      
      // Check authentication and permissions
      if (!this.authStore.isAuthenticated) {
        this.error = 'Please log in to access the dashboard.'
        return
      }
      
      if (!this.authStore.isManager) {
        this.error = 'You do not have permission to access this page.'
        return
      }
      
      this.fetchDashboardData()
      this.startPolling()
    },

    startPolling() {
      if (this.pollInterval) return // Already polling
      this.pollInterval = setInterval(() => {
        this.refreshAttendance()
      }, this.pollIntervalMs)
      console.log(`Started polling attendance every ${this.pollIntervalMs / 1000} seconds`)
    },

    stopPolling() {
      if (this.pollInterval) {
        clearInterval(this.pollInterval)
        this.pollInterval = null
        console.log('Stopped polling attendance')
      }
    },
   
    async fetchDashboardData(retry = false) {
      this.loading = true
      this.error = null
      this.debugInfo = null
      
      try {
        console.log('=== Fetching Manager Dashboard Data ===')
        console.log('Retry attempt:', retry ? this.retryCount : 'Initial')
        console.log('Base URL:', axios.defaults.baseURL)
        console.log('Auth token present:', !!this.authStore.token)
        console.log('User ID:', this.authStore.user?.id)
        console.log('User role:', this.authStore.user?.role)
      
        // Fetch dashboard data and attendance data in parallel
        const [dashboardResponse, attendanceResponse] = await Promise.all([
          axios.get('/api/dashboard'),
          axios.get('/api/manager/attendance', {
            params: { date: this.today }
          })
        ])
      
        console.log('=== Dashboard Response ===')
        console.log('Status:', dashboardResponse.status)
        console.log('Role:', dashboardResponse.data.role)
      
        console.log('=== Attendance Response ===')
        console.log('Status:', attendanceResponse.status)
        console.log('Attendance data:', attendanceResponse.data)
      
        // Verify this is a manager response
        if (dashboardResponse.data.role !== 'manager') {
          throw new Error(`Expected manager role but got: ${dashboardResponse.data.role}`)
        }
      
        // Map the backend response to component data
        const stats = dashboardResponse.data.stats || {}
        const productivity = stats.team_productivity || {}
      
        // Process attendance data from the dedicated endpoint
        let attendanceData = attendanceResponse.data.attendances || []
        // Filter to only today's date to avoid historical data and duplicates
        attendanceData = attendanceData.filter(a => a.date === this.today)
        const attSummary = attendanceResponse.data.summary || {}
        const teamSize = attSummary.total_employees || 0
      
        console.log('📊 Processing attendance data:', {
          teamSize,
          attendanceDataCount: attendanceData.length,
          rawAttendanceData: attendanceData
        })
      
        // Calculate actual attendance metrics from attendance endpoint
        const attendanceMetrics = this.calculateAttendanceMetrics(attendanceData, teamSize)
      
        this.overview = {
          teamSize: teamSize,
          pendingLeaves: stats.pending_leave_approvals || 0,
          attendanceRate: attendanceMetrics.attendanceRate,
          totalHours: Math.round(productivity.total_hours || 0)
        }
      
        // Set attendance summary
        this.attendanceSummary = attendanceMetrics.summary
      
        // Set pending leaves
        this.pendingLeaves = dashboardResponse.data.pending_leaves || []
      
        console.log('=== Data Mapped Successfully ===')
        console.log('Overview:', this.overview)
        console.log('Attendance Summary:', this.attendanceSummary)
        console.log('Pending leaves count:', this.pendingLeaves.length)
      
        // Reset retry count on success
        this.retryCount = 0
      
      } catch (err) {
        console.error('=== Dashboard Fetch Error ===')
        console.error('Error type:', err.name)
        console.error('Error message:', err.message)
        console.error('Error code:', err.code)
        console.error('Response status:', err.response?.status)
        console.error('Response data:', err.response?.data)
        console.error('Full error:', err)
      
        // Store debug info
        this.debugInfo = {
          errorType: err.name,
          errorMessage: err.message,
          errorCode: err.code,
          responseStatus: err.response?.status,
          responseData: err.response?.data,
          requestUrl: err.config?.url,
          requestMethod: err.config?.method,
          hasToken: !!this.authStore.token,
          userRole: this.authStore.user?.role,
          baseURL: axios.defaults.baseURL
        }
      
        this.handleApiError(err)
      } finally {
        this.loading = false
      }
    },

    async refreshAttendance() {
      if (this.loadingAttendance) return
      this.loadingAttendance = true
      try {
        console.log('=== Refreshing Attendance Data ===')
        const attendanceResponse = await axios.get('/api/manager/attendance', {
          params: { date: this.today }
        })
      
        let attendanceData = attendanceResponse.data.attendances || []
        // Filter to only today's date to avoid historical data and duplicates
        attendanceData = attendanceData.filter(a => a.date === this.today)
        const attSummary = attendanceResponse.data.summary || {}
        const teamSize = attSummary.total_employees || 0
      
        const attendanceMetrics = this.calculateAttendanceMetrics(attendanceData, teamSize)
      
        this.overview = {
          ...this.overview,
          teamSize: teamSize,
          attendanceRate: attendanceMetrics.attendanceRate
        }
      
        this.attendanceSummary = attendanceMetrics.summary
      
        console.log('=== Attendance Refreshed ===')
        console.log('Updated Attendance Summary:', this.attendanceSummary)
      } catch (err) {
        console.error('Attendance refresh error:', err)
        // Don't set global error for polling, just log
      } finally {
        this.loadingAttendance = false
      }
    },
   
    calculateAttendanceMetrics(attendanceData, teamSize) {
      const summary = {
        present: 0,
        absent: 0,
        late: 0,
        onLeave: 0
      }
     
      if (!attendanceData || !Array.isArray(attendanceData)) {
        if (teamSize > 0) {
          summary.absent = teamSize;
        }
        return {
          summary,
          attendanceRate: 0
        }
      }
     
      let counted = 0;
      // Process each attendance record
      attendanceData.forEach(record => {
        const status = record.status?.toLowerCase() || 'absent'
     
        switch (status) {
          case 'present':
          case 'completed':
            summary.present++
            break
          case 'late':
            summary.late++
            break
          case 'on_leave':
            summary.onLeave++
            break
          case 'absent':
            summary.absent++
            break
          default:
            // Count unknown statuses as absent
            summary.absent++
        }
        counted++;
      })
     
      // Add unaccounted employees (no records) as absent
      if (counted < teamSize) {
        summary.absent += (teamSize - counted);
      }
     
      // Calculate attendance rate: (present + late) / teamSize
      const presentAndLateCount = summary.present + summary.late
      const attendanceRate = teamSize > 0
        ? Math.round((presentAndLateCount / teamSize) * 100)
        : 0
     
      console.log('📊 Calculated Attendance Metrics:', {
        summary,
        presentAndLateCount,
        teamSize,
        attendanceRate
      })
     
      return {
        summary,
        attendanceRate
      }
    },
   
    retryFetch() {
      this.retryCount++
      console.log('Retry attempt:', this.retryCount)
     
      if (this.retryCount <= 3) {
        this.fetchDashboardData(true)
      } else {
        this.error = 'Max retries exceeded. Please check your connection and try again later.'
      }
    },
   
    handleApiError(err) {
      let errorMsg = 'An unexpected error occurred.'
     
      if (err.code === 'ERR_NETWORK' || err.message.includes('Network Error')) {
        errorMsg = 'Network error: Cannot connect to the server. Please check if the backend is running.'
      } else if (err.response?.status === 401) {
        errorMsg = 'Authentication failed. Your session may have expired. Please log in again.'
        setTimeout(() => {
          this.authStore.clearAuth()
          this.$router.push({ name: 'login' })
        }, 2000)
      } else if (err.response?.status === 403) {
        errorMsg = 'Access denied. You do not have permission to view this dashboard.'
      } else if (err.response?.status === 404) {
        // Check which endpoint failed
        const url = err.config?.url
        if (url?.includes('/api/manager/attendance')) {
          errorMsg = 'Attendance endpoint not found. Please verify the API route exists: /api/manager/attendance'
        } else {
          errorMsg = 'Dashboard endpoint not found. Please verify the API route exists: /api/dashboard'
        }
      } else if (err.response?.status === 500) {
        errorMsg = 'Server error. Please contact support if this persists.'
      } else {
        errorMsg = err.response?.data?.message || err.message || errorMsg
      }
     
      this.error = errorMsg
    },
   
    async approveLeave(leaveId) {
      try {
        await axios.post(`/api/manager/leaves/${leaveId}/approve`)
        alert('Leave approved successfully!')
        this.fetchDashboardData()
      } catch (err) {
        console.error('Approve leave error:', err)
        alert('Failed to approve leave: ' + (err.response?.data?.message || err.message))
      }
    },
   
    async rejectLeave(leaveId) {
      const reason = prompt('Please provide a reason for rejection:')
      if (!reason) return
     
      try {
        await axios.post(`/api/manager/leaves/${leaveId}/reject`, { reason })
        alert('Leave rejected.')
        this.fetchDashboardData()
      } catch (err) {
        console.error('Reject leave error:', err)
        alert('Failed to reject leave: ' + (err.response?.data?.message || err.message))
      }
    },
   
    formatLeaveType(type) {
      if (!type) return 'N/A'
      return type.split('_').map(word =>
        word.charAt(0).toUpperCase() + word.slice(1)
      ).join(' ')
    },
   
    formatDate(date) {
      if (!date) return 'N/A'
      try {
        const d = new Date(date.includes('T') ? date : date + 'T00:00:00')
        return d.toLocaleDateString('en-US', {
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
.manager-dashboard {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}
.page-header {
  margin-bottom: 2rem;
}
.page-header h1 {
  color: #2d3748;
  font-size: 2rem;
  margin: 0;
}
.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s;
}
.btn-primary:hover {
  transform: translateY(-2px);
}
.btn-refresh {
  background: #4a90e2;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.9rem;
}
.btn-refresh:hover:not(:disabled) {
  background: #357abd;
}
.btn-refresh:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.loading {
  text-align: center;
  padding: 4rem;
}
.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
.error-message {
  background: #fee;
  color: #c33;
  padding: 1.5rem;
  border-radius: 8px;
  text-align: center;
}
.error-message h3 {
  margin: 0 0 1rem 0;
}
.error-details {
  margin-top: 1rem;
  text-align: left;
}
.error-details summary {
  cursor: pointer;
  padding: 0.5rem;
  background: #fff;
  border-radius: 4px;
  color: #666;
}
.error-details pre {
  background: #fff;
  padding: 1rem;
  border-radius: 4px;
  overflow-x: auto;
  font-size: 0.85rem;
  color: #333;
  margin-top: 0.5rem;
}
.dashboard-content {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}
.metrics-section,
.attendance-summary-section,
.activities-section,
.quick-links-section {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  padding: 1.5rem;
}
.metrics-section h2,
.attendance-summary-section h2,
.activities-section h2,
.quick-links-section h2 {
  margin: 0 0 1rem 0;
  color: #2d3748;
  font-size: 1.25rem;
}
.attendance-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  flex-wrap: wrap;
  gap: 1rem;
}
.attendance-date {
  color: #718096;
  font-size: 0.9rem;
  font-weight: 500;
}
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}
.metric-card {
  text-align: center;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
  transition: transform 0.2s;
}
.metric-card:hover {
  transform: translateY(-2px);
}
.metric-icon {
  font-size: 2rem;
  margin-bottom: 0.5rem;
  display: block;
}
.metric-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: #2d3748;
  display: block;
}
.metric-label {
  font-size: 0.875rem;
  color: #718096;
}
.attendance-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
}
.attendance-stat {
  text-align: center;
  padding: 1rem;
  border-radius: 8px;
  border: 2px solid #e2e8f0;
  transition: transform 0.2s;
}
.attendance-stat:hover {
  transform: translateY(-2px);
}
.attendance-stat.present {
  border-color: #48bb78;
  background: #f0fff4;
}
.attendance-stat.absent {
  border-color: #f56565;
  background: #fff5f5;
}
.attendance-stat.late {
  border-color: #ed8936;
  background: #fffaf0;
}
.attendance-stat.leave {
  border-color: #4299e1;
  background: #ebf8ff;
}
.stat-value {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
}
.stat-label {
  display: block;
  font-size: 0.875rem;
  color: #718096;
  margin-top: 0.25rem;
}
.pending-leaves-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.leave-item {
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
  border-left: 4px solid #667eea;
  transition: transform 0.2s;
}
.leave-item:hover {
  transform: translateY(-2px);
}
.leave-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}
.employee-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.employee-name {
  font-weight: 600;
  color: #2d3748;
}
.leave-type {
  font-size: 0.85rem;
  color: #667eea;
}
.leave-dates {
  font-size: 0.9rem;
  color: #718096;
}
.leave-details {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}
.leave-reason {
  margin: 0;
  color: #4a5568;
  font-size: 0.95rem;
}
.leave-days {
  font-weight: 600;
  color: #2d3748;
}
.leave-actions {
  display: flex;
  gap: 0.5rem;
}
.btn-approve,
.btn-reject {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-approve {
  background: #48bb78;
  color: white;
}
.btn-approve:hover {
  background: #38a169;
}
.btn-reject {
  background: #f56565;
  color: white;
}
.btn-reject:hover {
  background: #e53e3e;
}
.empty-state {
  text-align: center;
  padding: 2rem;
  color: #718096;
}
.quick-links-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
}
.quick-link-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1.5rem;
  background: #f8f9fa;
  border-radius: 8px;
  text-decoration: none;
  color: #2d3748;
  transition: all 0.2s;
  text-align: center;
}
.quick-link-card:hover {
  background: #e6f7ff;
  color: #1890ff;
  transform: translateY(-2px);
}
.quick-link-icon {
  font-size: 2rem;
  margin-bottom: 0.5rem;
}
@media (max-width: 768px) {
  .manager-dashboard {
    padding: 1rem;
  }
 
  .metrics-grid {
    grid-template-columns: 1fr;
  }
 
  .attendance-stats {
    grid-template-columns: repeat(2, 1fr);
  }
 
  .attendance-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
 
  .quick-links-grid {
    grid-template-columns: repeat(2, 1fr);
  }
 
  .leave-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
 
  .leave-details {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
}
</style>