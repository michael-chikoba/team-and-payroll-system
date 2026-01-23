<template>
  <div class="manager-dashboard">
    <div class="page-header">
      <h1>Manager Dashboard</h1>
      <p class="subtitle">{{ formatDateLong(today) }}</p>
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
      <button @click="retryFetch" class="btn-primary">Retry</button>
    </div>
    
    <!-- Dashboard Content -->
    <div v-else class="dashboard-content">
      
      <!-- 1. Metrics Overview -->
      <div class="metrics-section">
        <h2>Team Metrics</h2>
        <div class="metrics-grid">
          <div class="metric-card">
            <div class="metric-icon">👥</div>
            <div class="metric-value">{{ overview.teamSize }}</div>
            <div class="metric-label">Total Staff</div>
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
              <span v-if="loadingAttendance" class="spinner-small"></span>
              {{ loadingAttendance ? 'Syncing...' : 'Refresh' }}
            </button>
          </div>
        </div>
        
        <!-- Summary Cards (Mini) -->
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
        
        <!-- Detailed List View -->
        <div class="attendance-details">
          <h3>Team Status</h3>
          
          <div v-if="attendanceData.length === 0" class="empty-list">
            No attendance records found for today.
          </div>

          <div v-else class="attendance-list-container">
            <!-- Header Row -->
            <div class="list-header">
              <div class="col-name">Employee</div>
              <div class="col-status">Status</div>
              <div class="col-time">Check In</div>
              <div class="col-time">Check Out</div>
              <div class="col-hrs">Hrs</div>
            </div>

            <!-- Rows -->
            <div v-for="emp in attendanceData" :key="emp.id" class="list-row">
              <div class="col-name">
                <div class="avatar-small">{{ getInitials(emp.full_name) }}</div>
                <div class="name-info">
                  <span class="emp-name">{{ emp.full_name }}</span>
                  <span class="emp-pos">{{ emp.position }}</span>
                </div>
              </div>
              
              <div class="col-status">
                <span class="status-badge" :class="getStatusClass(emp.status)">
                  {{ formatStatus(emp.status) }}
                </span>
                <span v-if="emp.is_late" class="late-indicator" title="Late Arrival">⚠️</span>
              </div>
              
              <div class="col-time">
                {{ formatTime(emp.clock_in) }}
              </div>
              
              <div class="col-time">
                {{ formatTime(emp.clock_out) }}
              </div>
              
              <div class="col-hrs">
                <strong>{{ formatHours(emp.total_hours) }}</strong>
              </div>
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
              <span class="leave-dates">
                {{ formatDate(leave.start_date) }} - {{ formatDate(leave.end_date) }}
              </span>
            </div>
            <div class="leave-details-text">
              <p class="leave-reason">"{{ leave.reason || 'No reason provided' }}"</p>
              <span class="leave-days">{{ leave.number_of_days }} days</span>
            </div>
            <div class="leave-actions">
              <button @click="approveLeave(leave.id)" class="btn-approve">Approve</button>
              <button @click="rejectLeave(leave.id)" class="btn-reject">Reject</button>
            </div>
          </div>
        </div>
        <div v-else class="empty-state">
          <p>✅ All caught up! No pending leaves.</p>
        </div>
      </div>
      
      <!-- 4. Quick Links -->
      <div class="quick-links-section">
        <h2>Quick Actions</h2>
        <div class="quick-links-grid">
          <router-link to="/manager/employees" class="quick-link-card">
            <span class="icon">👥</span>
            <span>View Team</span>
          </router-link>
          <router-link to="/manager/leave-approvals" class="quick-link-card">
            <span class="icon">📅</span>
            <span>Leave Calendar</span>
          </router-link>
          <router-link to="/manager/attendance" class="quick-link-card">
            <span class="icon">📈</span>
            <span>Attendance Reports</span>
          </router-link>
          <router-link to="/manager/reports" class="quick-link-card">
            <span class="icon">📋</span>
            <span>Export Data</span>
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
      // Data State
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
      allEmployees: [], // Store all employees to calculate team size
      
      // UI State
      loading: false,
      loadingAttendance: false,
      error: null,
      lastUpdated: null,
      today: new Date().toISOString().split('T')[0],
      pollInterval: null
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
        // Parallel fetch for Dashboard General Data and Specific Attendance Data
        await Promise.all([
          this.fetchGeneralStats(),
          this.fetchAttendanceData()
        ])
        
        // Start polling for attendance updates every 60s
        this.pollInterval = setInterval(() => {
          this.refreshAttendance()
        }, 60000)
        
      } catch (err) {
        this.handleError(err)
      } finally {
        this.loading = false
      }
    },

    // --- 1. General Dashboard Stats (Leaves, etc) ---
    async fetchGeneralStats() {
      try {
        const response = await axios.get('/api/dashboard')
        const data = response.data

        this.pendingLeaves = data.pending_leaves || []
        
        // Update pending leaves count
        this.overview.pendingLeaves = data.stats?.pending_leave_approvals || this.pendingLeaves.length
        
        // If we have team data, use it to get accurate team size
        if (data.team_members) {
          this.allEmployees = data.team_members
          this.overview.teamSize = this.allEmployees.length
        }
        
      } catch (err) {
        console.error('Error fetching general stats:', err)
        // Don't block the whole dashboard if this fails
      }
    },

    // --- 2. Robust Attendance Fetching (Fixed to match AttendanceMonitor logic) ---
    async fetchAttendanceData() {
      try {
        const url = `/api/manager/attendance/team-status?date=${this.today}`
        const response = await axios.get(url)
        const data = response.data

        // Handle success flag if present
        if ('success' in data && !data.success) {
          throw new Error(data.message || 'Failed to fetch attendance')
        }

        this.processAttendanceResponse(data)
        this.lastUpdated = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })

      } catch (err) {
        console.error('Attendance fetch error:', err)
        // Don't block the whole dashboard if just attendance fails
      }
    },

    async refreshAttendance() {
      this.loadingAttendance = true
      await this.fetchAttendanceData()
      this.loadingAttendance = false
    },

    processAttendanceResponse(data) {
      const attendances = data.attendances || []
      
      // FIXED: Group by employee to get unique employees for today
      // The API returns historical records, so we need to filter for today only
      // and group by employee
      const employeeMap = new Map()
      
      attendances.forEach((record, index) => {
        // Only process records for today
        if (record.date === this.today) {
          const employee = record.employee || {}
          
          // Extract employee details from nested structure
          const employeeId = employee.employee_id || employee.id
          
          // If we already have this employee, only replace if this record is newer
          if (!employeeMap.has(employeeId) || record.id > employeeMap.get(employeeId).id) {
            const fullName = employee.full_name || 
                            `${employee.user?.first_name || ''} ${employee.user?.last_name || ''}`.trim() || 
                            'Unknown Employee'
            
            // Convert checkIn/checkOut to match expected format
            const clockIn = record.checkIn || null
            const clockOut = record.checkOut || null
            const totalHours = record.hoursWorked || 0
            const status = record.status || 'absent'

            const processed = {
              id: record.id,
              employee_id: employeeId,
              full_name: fullName,
              employee_number: employee.employee_id || 'N/A',
              department: employee.department || 'Unassigned',
              position: employee.position || 'N/A',
              status: status,
              clock_in: clockIn,
              clock_out: clockOut,
              total_hours: totalHours,
              date: record.date,
              is_late: status === 'late',
              minutes_late: this.calculateMinutesLate(clockIn)
            }
            
            employeeMap.set(employeeId, processed)
          }
        }
      })

      // Convert Map to Array
      this.attendanceData = Array.from(employeeMap.values())
      
      // Calculate overview based on processed data
      this.calculateOverview(data.summary || {}, this.attendanceData)
    },

    calculateMinutesLate(clockIn) {
      if (!clockIn) return 0
      
      try {
        // Assuming work starts at 9:00 AM
        const clockInTime = new Date(`2000-01-01T${this.convertTo24Hour(clockIn)}`)
        const startTime = new Date('2000-01-01T09:00:00')
        
        const diffMs = clockInTime - startTime
        return diffMs > 0 ? Math.floor(diffMs / (1000 * 60)) : 0
      } catch (e) {
        return 0
      }
    },

    convertTo24Hour(timeStr) {
      if (!timeStr) return ''
      
      // If already in 24-hour format, return as is
      if (!timeStr.includes('AM') && !timeStr.includes('PM')) {
        return timeStr
      }
      
      // Convert 12-hour to 24-hour format
      const [time, modifier] = timeStr.split(' ')
      let [hours, minutes] = time.split(':')
      
      if (hours === '12') {
        hours = '00'
      }
      
      if (modifier === 'PM') {
        hours = parseInt(hours, 10) + 12
      }
      
      return `${hours.toString().padStart(2, '0')}:${minutes}`
    },

    calculateOverview(apiSummary, processedData) {
      // If we have a list of all employees from general stats, use that for team size
      // Otherwise use the processed data count (which only shows employees with records for today)
      const totalEmployees = this.allEmployees.length > 0 ? this.allEmployees.length : 
                           (apiSummary.total_employees || processedData.length)
      
      // Calculate counts from processed data
      const presentCount = processedData.filter(e => 
        ['present', 'completed'].includes(e.status)
      ).length
      
      const lateCount = processedData.filter(e => 
        e.status === 'late'
      ).length
      
      const onLeaveCount = processedData.filter(e => 
        ['on_leave', 'onleave'].includes(e.status)
      ).length
      
      // Calculate absent count: total employees minus those present/late/on leave
      const absentCount = Math.max(0, totalEmployees - (presentCount + lateCount + onLeaveCount))

      // Update overview
      this.overview.teamSize = totalEmployees
      this.overview.presentCount = presentCount
      this.overview.lateCount = lateCount
      this.overview.onLeaveCount = onLeaveCount
      this.overview.absentCount = absentCount

      // Calculate Attendance Rate: (Present + Late) / Total Employees
      const activeCount = presentCount + lateCount
      this.overview.attendanceRate = totalEmployees > 0 
        ? Math.round((activeCount / totalEmployees) * 100) 
        : 0
        
      console.log('Dashboard Overview:', {
        totalEmployees,
        presentCount,
        lateCount,
        onLeaveCount,
        absentCount,
        attendanceRate: this.overview.attendanceRate,
        allEmployeesCount: this.allEmployees.length,
        attendanceDataCount: processedData.length
      })
    },

    // --- 3. Leave Actions ---
    async approveLeave(leaveId) {
      if(!confirm('Approve this leave request?')) return
      try {
        await axios.post(`/api/manager/leaves/${leaveId}/approve`)
        this.fetchGeneralStats() // Refresh lists
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

    // --- 4. Helpers & Formatting ---
    retryFetch() {
      this.initializeDashboard()
    },

    handleError(err) {
      console.error(err)
      if (err.response?.status === 401) {
        this.authStore.clearAuth()
        this.$router.push('/login')
      } else {
        this.error = err.response?.data?.message || err.message || 'Failed to load dashboard.'
      }
    },

    getInitials(name) {
      return name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '??'
    },
    
    formatDateLong(d) {
      return new Date(d).toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
    },

    formatDate(d) {
      return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
    },

    formatTime(t) {
      if (!t) return '--:--'
      try {
        // Handle 12-hour format (e.g., "08:09 AM")
        if (t.includes('AM') || t.includes('PM')) {
          return t
        }
        
        // Handle 24-hour format
        const date = t.includes('T') ? new Date(t) : new Date(`2000-01-01T${t}`)
        return isNaN(date) ? t : date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
      } catch (e) { 
        return t 
      }
    },

    formatHours(h) {
      const num = parseFloat(h) || 0
      const hrs = Math.floor(num)
      const mins = Math.round((num - hrs) * 60)
      return `${hrs}h ${mins}m`
    },

    formatStatus(s) {
      const map = { 
        present: 'Present', 
        completed: 'Completed', 
        absent: 'Absent', 
        late: 'Late', 
        on_leave: 'On Leave', 
        onleave: 'On Leave' 
      }
      return map[s] || s
    },
    
    formatLeaveType(type) {
      return type ? type.replace(/_/g, ' ').toUpperCase() : 'LEAVE'
    },

    getStatusClass(s) {
      if (['present', 'completed'].includes(s)) return 'status-present'
      if (['absent'].includes(s)) return 'status-absent'
      if (['late'].includes(s)) return 'status-late'
      if (['on_leave', 'onleave'].includes(s)) return 'status-leave'
      return 'status-unknown'
    }
  }
}
</script>

<style scoped>
/* Core Layout */
.manager-dashboard {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
}

.page-header {
  margin-bottom: 2rem;
}

.page-header h1 {
  font-size: 1.8rem;
  font-weight: 700;
  margin: 0;
  color: #0f172a;
}

.subtitle {
  color: #64748b;
  margin: 0.25rem 0 0;
}

.dashboard-content {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* Sections Common */
.metrics-section,
.attendance-summary-section,
.activities-section,
.quick-links-section {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
  border: 1px solid #e2e8f0;
  padding: 1.5rem;
}

h2 {
  font-size: 1.1rem;
  font-weight: 600;
  margin: 0 0 1.25rem 0;
  color: #334155;
}

/* 1. Metrics Grid */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.metric-card {
  padding: 1.25rem;
  background: #f8fafc;
  border-radius: 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.metric-icon { font-size: 1.5rem; margin-bottom: 0.5rem; }
.metric-value { font-size: 1.8rem; font-weight: 800; color: #0f172a; line-height: 1.2; }
.metric-label { font-size: 0.85rem; color: #64748b; font-weight: 500; }

/* 2. Attendance Section */
.attendance-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.last-updated {
  font-size: 0.8rem;
  color: #94a3b8;
}

/* Summary Stats */
.attendance-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
  margin-bottom: 2rem;
}

.attendance-stat {
  padding: 1rem;
  border-radius: 8px;
  text-align: center;
  border: 1px solid transparent;
}

.attendance-stat .stat-value { display: block; font-size: 1.5rem; font-weight: 700; }
.attendance-stat .stat-label { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 0.25rem; }

.attendance-stat.present { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
.attendance-stat.late { background: #fef3c7; color: #92400e; border-color: #fde68a; }
.attendance-stat.absent { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
.attendance-stat.leave { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }

/* List View */
.attendance-details h3 {
  font-size: 0.95rem;
  text-transform: uppercase;
  color: #64748b;
  letter-spacing: 0.05em;
  margin-bottom: 1rem;
}

.attendance-list-container {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
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
  font-size: 0.75rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
}

.list-row {
  border-bottom: 1px solid #e2e8f0;
  font-size: 0.9rem;
  background: white;
}

.list-row:last-child { border-bottom: none; }

.col-name { display: flex; align-items: center; gap: 0.75rem; }
.avatar-small {
  width: 32px; height: 32px;
  background: #6366f1; color: white;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.75rem; font-weight: 600;
}
.name-info { display: flex; flex-direction: column; }
.emp-name { font-weight: 600; color: #1e293b; }
.emp-pos { font-size: 0.75rem; color: #64748b; }

.col-status { display: flex; align-items: center; gap: 0.5rem; }

.status-badge {
  padding: 0.25rem 0.6rem;
  border-radius: 99px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: capitalize;
}
.status-present { background: #d1fae5; color: #065f46; }
.status-late { background: #fef3c7; color: #92400e; }
.status-absent { background: #fee2e2; color: #991b1b; }
.status-leave { background: #eff6ff; color: #1e40af; }

.col-time { color: #475569; font-variant-numeric: tabular-nums; }
.col-hrs { text-align: right; color: #1e293b; }

.view-all-link {
  margin-top: 1rem;
  text-align: center;
}
.view-all-link a {
  color: #4f46e5;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 600;
}

/* 3. Pending Leaves */
.leave-item {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 0.75rem;
  background: #f8fafc;
}

.leave-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.employee-name { font-weight: 600; color: #1e293b; display: block; }
.leave-type { font-size: 0.75rem; color: #4f46e5; font-weight: 600; background: #e0e7ff; padding: 2px 6px; border-radius: 4px; }
.leave-dates { font-size: 0.85rem; color: #64748b; }

.leave-details-text {
  margin-bottom: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.leave-reason { font-style: italic; color: #475569; font-size: 0.9rem; margin: 0; }
.leave-days { font-weight: 700; font-size: 0.9rem; }

.leave-actions { display: flex; gap: 0.5rem; }
.btn-approve { background: #10b981; color: white; border: none; padding: 0.4rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; }
.btn-reject { background: #ef4444; color: white; border: none; padding: 0.4rem 1rem; border-radius: 6px; cursor: pointer; font-size: 0.85rem; }

/* 4. Quick Links */
.quick-links-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 1rem;
}

.quick-link-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  padding: 1.5rem;
  background: #f8fafc;
  border-radius: 8px;
  text-decoration: none;
  color: #334155;
  font-weight: 500;
  transition: all 0.2s;
}
.quick-link-card:hover {
  background: white;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  transform: translateY(-2px);
  color: #4f46e5;
}
.quick-link-card .icon { font-size: 1.5rem; }

/* Utilities */
.btn-refresh {
  background: white;
  border: 1px solid #e2e8f0;
  padding: 0.4rem 0.8rem;
  border-radius: 6px;
  font-size: 0.85rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #475569;
}
.btn-refresh:hover { background: #f1f5f9; color: #0f172a; }

.spinner, .spinner-small {
  border: 2px solid #e2e8f0;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}
.spinner { width: 40px; height: 40px; margin: 0 auto 1rem; }
.spinner-small { width: 14px; height: 14px; }

.loading, .empty-state, .empty-list {
  text-align: center;
  padding: 2rem;
  color: #64748b;
}

.error-message {
  background: #fee2e2;
  color: #991b1b;
  padding: 1.5rem;
  border-radius: 8px;
  text-align: center;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* Mobile */
@media (max-width: 768px) {
  .attendance-stats { grid-template-columns: repeat(2, 1fr); }
  
  .list-header { display: none; }
  .list-row {
    grid-template-columns: 1fr 1fr;
    grid-template-areas: "name status" "times hrs";
    gap: 0.5rem;
  }
  .col-name { grid-area: name; }
  .col-status { grid-area: status; justify-content: flex-end; }
  .col-time { display: none; } /* Hide times on very small screens or adapt */
  .col-hrs { grid-area: hrs; text-align: right; }
}
</style>