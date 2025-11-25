<template>
  <div class="reports-management">
    <div class="page-header">
      <div class="header-content">
        <h1>My Team Reports</h1>
        <div class="filter-section">
          <div class="filter-group">
            <label for="employee-filter">Select Employee (Optional):</label>
            <select id="employee-filter" v-model="selectedEmployeeId" @change="handleEmployeeFilter">
              <option value="">All Team Members</option>
              <option v-for="employee in managedEmployees" :key="employee.id" :value="employee.id">
                {{ employee.name }}
              </option>
            </select>
          </div>
          <div v-if="selectedEmployeeId" class="filter-info">
            <p>Viewing reports for: {{ getEmployeeName(selectedEmployeeId) }}</p>
          </div>
        </div>
      </div>
      <div class="header-actions">
        <button 
          @click="generateTeamReport" 
          class="btn-primary"
          :disabled="generatingReport"
        >
          <span>📊</span> {{ generatingReport ? 'Generating...' : 'Generate Team Report' }}
        </button>
        <button 
          @click="generateProductivityReport" 
          class="btn-secondary"
          :disabled="generatingReport"
        >
          <span>📈</span> {{ generatingReport ? 'Generating...' : 'Productivity Report' }}
        </button>
        <button 
          v-if="selectedEmployeeId"
          @click="generateEmployeeReport" 
          class="btn-success"
          :disabled="generatingReport"
        >
          <span>👤</span> {{ generatingReport ? 'Generating...' : 'Employee Specific Report' }}
        </button>
        <button 
          v-if="selectedEmployeeId"
          @click="editPerformanceMetrics(selectedEmployeeId)" 
          class="btn-info"
        >
          <span>✏️</span> Edit Performance
        </button>
      </div>
    </div>

    <!-- Authentication Check -->
    <div v-if="!authStore.isAuthenticated" class="error-message">
      Please log in to access team reports.
    </div>

    <!-- Permission Check -->
    <div v-else-if="!authStore.isManager" class="error-message">
      You don't have permission to access this page.
    </div>

    <!-- Loading State -->
    <div v-else-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Loading team reports...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-message">
      {{ error }}
      <button @click="retryFetch" class="btn-primary">Retry</button>
    </div>

    <!-- Reports Dashboard -->
    <div v-else class="reports-dashboard">
      <!-- Manager Info -->
      <div class="manager-info">
        <h2>👨‍💼 Managing Team</h2>
        <p class="manager-subtitle">Team overview and performance metrics for your direct reports{{ selectedEmployeeId ? ` - Focusing on ${getEmployeeName(selectedEmployeeId)}` : '' }}</p>
      </div>

      <!-- Quick Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">👥</div>
          <div class="stat-content">
            <h3>Team Size</h3>
            <p class="stat-value">{{ teamStats.team_size || 0 }}</p>
            <p class="stat-label">Direct Reports</p>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">✅</div>
          <div class="stat-content">
            <h3>Present Today</h3>
            <p class="stat-value">{{ teamStats.present_today || 0 }}</p>
            <p class="stat-label">Employees Present</p>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">📅</div>
          <div class="stat-content">
            <h3>Pending Leaves</h3>
            <p class="stat-value">{{ teamStats.pending_leaves || 0 }}</p>
            <p class="stat-label">Awaiting Approval</p>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">📊</div>
          <div class="stat-content">
            <h3>Avg. Productivity</h3>
            <p class="stat-value">{{ teamStats.avg_productivity || 0 }}%</p>
            <p class="stat-label">This Month</p>
          </div>
        </div>
      </div>

      <!-- Team Members Quick View -->
      <div class="team-members-section">
        <div class="section-header">
          <h2>Team Members ({{ managedEmployees.length }})</h2>
          <button @click="viewAllTeamMembers" class="btn-view">View All</button>
        </div>
        <div v-if="managedEmployees.length === 0" class="empty-state">
          <p>No team members assigned to you yet.</p>
        </div>
        <div v-else class="team-members-grid">
          <div v-for="employee in managedEmployees.slice(0, 6)" :key="employee.id" class="team-member-card">
            <div class="member-avatar">
              {{ getInitials(employee.name) }}
            </div>
            <div class="member-info">
              <h4>{{ employee.name }}</h4>
              <p class="member-position">{{ employee.position || employee.job_title || 'Employee' }}</p>
              <p class="member-department">{{ employee.department || 'General' }}</p>
            </div>
            <div class="member-status">
              <span :class="['status-dot', getEmployeeStatus(employee)]"></span>
              <span class="status-text">{{ getEmployeeStatusText(employee) }}</span>
            </div>
          </div>
          <div v-if="managedEmployees.length > 6" class="more-members">
            <p>+{{ managedEmployees.length - 6 }} more team members</p>
          </div>
        </div>
      </div>

      <!-- Report Sections -->
      <div class="report-sections">
        <!-- Team Report -->
        <div class="report-section">
          <div class="section-header">
            <h2>Team Performance Report</h2>
            <button @click="viewTeamReport" class="btn-view">View Full Report</button>
          </div>
          <div v-if="teamReport" class="report-preview">
            <div class="preview-content">
              <h4>Your Team Overview</h4>
              <p>Period: {{ formatDate(teamReport.period_start) }} - {{ formatDate(teamReport.period_end) }}</p>
              <div class="preview-stats">
                <span class="preview-stat">Team Members: {{ teamReport.total_employees }}</span>
                <span class="preview-stat">Active Today: {{ teamReport.active_employees }}</span>
                <span class="preview-stat">On Leave: {{ teamReport.on_leave }}</span>
                <span class="preview-stat">Attendance Rate: {{ teamReport.attendance_rate }}%</span>
              </div>
            </div>
          </div>
          <div v-else class="empty-preview">
            <p>No team report generated yet.</p>
            <button @click="generateTeamReport" class="btn-primary">Generate Report</button>
          </div>
        </div>

        <!-- Productivity Report -->
        <div class="report-section">
          <div class="section-header">
            <h2>Team Productivity</h2>
            <button @click="viewProductivityReport" class="btn-view">View Full Report</button>
          </div>
          <div v-if="productivityReport" class="report-preview">
            <div class="preview-content">
              <h4>Performance Metrics</h4>
              <p>Generated: {{ formatDate(productivityReport.generated_at) }}</p>
              <div class="preview-stats">
                <span class="preview-stat">Avg. Productivity: {{ productivityReport.avg_productivity }}%</span>
                <span class="preview-stat">Tasks Completed: {{ productivityReport.total_tasks_completed }}</span>
                <span class="preview-stat">Team Attendance: {{ productivityReport.avg_attendance_rate }}%</span>
              </div>
            </div>
          </div>
          <div v-else class="empty-preview">
            <p>No productivity report generated yet.</p>
            <button @click="generateProductivityReport" class="btn-primary">Generate Report</button>
          </div>
        </div>

        <!-- Employee Specific Report (shown when employee selected) -->
        <div v-if="selectedEmployeeId" class="report-section">
          <div class="section-header">
            <h2>Employee Specific Report</h2>
            <button @click="viewEmployeeReport" class="btn-view">View Full Report</button>
          </div>
          <div v-if="employeeReport" class="report-preview">
            <div class="preview-content">
              <h4>{{ getEmployeeName(selectedEmployeeId) }} Overview</h4>
              <p>Period: {{ formatDate(employeeReport.period_start) }} - {{ formatDate(employeeReport.period_end) }}</p>
              <div class="preview-stats">
                <span class="preview-stat">Tasks Completed: {{ employeeReport.tasks_completed }}</span>
                <span class="preview-stat">Total Hours Worked: {{ employeeReport.total_hours }}h</span>
                <span class="preview-stat">Attendance Rate: {{ employeeReport.attendance_rate }}%</span>
                <span class="preview-stat">Productivity Score: {{ employeeReport.productivity_score }}%</span>
              </div>
            </div>
          </div>
          <div v-else class="empty-preview">
            <p>No employee report generated yet.</p>
            <button @click="generateEmployeeReport" class="btn-primary">Generate Report</button>
          </div>
        </div>
      </div>

      <!-- Pending Actions -->
      <div class="pending-actions">
        <h2>Pending Actions</h2>
        <div class="actions-grid">
          <div class="action-card">
            <div class="action-icon">📋</div>
            <div class="action-content">
              <h3>Leave Requests</h3>
              <p class="action-count">{{ pendingLeavesCount }} pending</p>
              <button @click="viewPendingLeaves" class="btn-action">Review</button>
            </div>
          </div>
          <div class="action-card">
            <div class="action-icon">⏰</div>
            <div class="action-content">
              <h3>Attendance Issues</h3>
              <p class="action-count">{{ attendanceIssuesCount }} to review</p>
              <button @click="viewAttendanceIssues" class="btn-action">Check</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Performance Metrics Edit Modal -->
    <div v-if="showPerformanceModal" class="modal-overlay" @click.self="closePerformanceModal">
      <div class="modal-card">
        <div class="modal-header">
          <h2>Edit Performance Metrics for {{ getEmployeeName(selectedEmployeeId) }}</h2>
          <button @click="closePerformanceModal" class="close-btn">✕</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="savePerformanceMetrics">
            <div class="form-group">
              <label>Performance Rating (1-5):</label>
              <input type="number" v-model="performanceData.rating" min="1" max="5" required>
            </div>
            <div class="form-group">
              <label>Productivity Score (%):</label>
              <input type="number" v-model="performanceData.productivity" min="0" max="100" required>
            </div>
            <div class="form-group">
              <label>Total Tasks Completed:</label>
              <input type="number" v-model="performanceData.tasks_completed" min="0" required>
            </div>
            <div class="form-group">
              <label>Notes:</label>
              <textarea v-model="performanceData.notes" rows="4" placeholder="Performance notes..."></textarea>
            </div>
            <div class="modal-footer">
              <button type="submit" class="btn-primary">Save Metrics</button>
              <button type="button" @click="closePerformanceModal" class="btn-secondary">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

export default {
  name: 'TeamReports',
  
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  
  data() {
    return {
      loading: false,
      generatingReport: false,
      error: null,
      selectedEmployeeId: null,
      managedEmployees: [],
      teamStats: {},
      teamReport: null,
      productivityReport: null,
      employeeReport: null,
      pendingLeavesCount: 0,
      attendanceIssuesCount: 0,
      retryCount: 0,
      showPerformanceModal: false,
      performanceData: {
        rating: 0,
        productivity: 0,
        tasks_completed: 0,
        notes: ''
      }
    }
  },
  
  mounted() {
    this.initializeComponent()
  },
  
  methods: {
    initializeComponent() {
      if (!this.authStore.isAuthenticated) {
        this.error = 'Please log in to access team reports.'
        return
      }
      
      if (!this.authStore.isManager) {
        this.error = 'You do not have permission to access this page.'
        return
      }
      
      this.fetchManagerData()
    },
    
    handleEmployeeFilter() {
      this.refreshReports()
    },
    
    getEmployeeName(employeeId) {
      const employee = this.managedEmployees.find(emp => emp.id === employeeId)
      return employee ? employee.name : ''
    },
    
    async fetchManagerData() {
      this.loading = true
      this.error = null
      
      try {
        console.log('Fetching manager team data...')
        
        const [employeesRes, leavesRes, attendanceRes] = await Promise.all([
          axios.get('/api/manager/employees'),
          axios.get('/api/manager/leaves/pending'),
          axios.get('/api/manager/attendance')
        ])
        
        console.log('Managed employees response:', employeesRes.data)
        console.log('Pending leaves response:', leavesRes.data)
        console.log('Attendance response:', attendanceRes.data)
        
        this.managedEmployees = this.processEmployeesData(employeesRes.data)
        this.pendingLeavesCount = this.processLeavesData(leavesRes.data)
        const attendanceData = this.processAttendanceData(attendanceRes.data)
        this.calculateTeamStats(attendanceData)
        await this.refreshReports()
        
      } catch (err) {
        console.error('Fetch error:', err)
        this.handleApiError(err)
      } finally {
        this.loading = false
      }
    },
    
    async refreshReports() {
      await Promise.all([
        this.generateTeamReport(),
        this.generateProductivityReport()
      ])
      
      if (this.selectedEmployeeId) {
        await this.generateEmployeeReport()
      }
    },
    
    async generateTeamReport() {
      this.generatingReport = true
      try {
        const params = { employee_id: this.selectedEmployeeId || null }
        const response = await axios.get('/api/manager/reports/team', { params })
        this.teamReport = this.processTeamReportData(response.data)
        
        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Team report generated successfully!'
        })
      } catch (error) {
        console.error('Error generating team report:', error)
        this.$notify({
          type: 'error',
          title: 'Error',
          text: 'Failed to generate team report.'
        })
      } finally {
        this.generatingReport = false
      }
    },
    
    async generateProductivityReport() {
      this.generatingReport = true
      try {
        const params = { 
          employee_id: this.selectedEmployeeId || null,
          start_date: new Date().toISOString().split('T')[0],
          end_date: new Date().toISOString().split('T')[0]
        }
        const response = await axios.get('/api/manager/reports/productivity', { params })
        this.productivityReport = this.processProductivityData(response.data)
        
        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Productivity report generated successfully!'
        })
      } catch (error) {
        console.error('Error generating productivity report:', error)
        this.$notify({
          type: 'error',
          title: 'Error',
          text: 'Failed to generate productivity report.'
        })
      } finally {
        this.generatingReport = false
      }
    },
    
    async generateEmployeeReport() {
      this.generatingReport = true
      try {
        const params = { 
          employee_id: this.selectedEmployeeId,
          start_date: new Date().toISOString().split('T')[0],
          end_date: new Date().toISOString().split('T')[0]
        }
        // Fetch comprehensive employee report combining attendance, tasks, leaves
        const [attendanceRes, tasksRes, leavesRes, productivityRes] = await Promise.all([
          axios.get('/api/reports/attendance', { params }),
          axios.get(`/api/manager/employees/${this.selectedEmployeeId}/tasks`, { params }),
          axios.get('/api/reports/leave', { params }),
          axios.get('/api/manager/reports/productivity', { params })
        ])
        
        this.employeeReport = {
          period_start: params.start_date,
          period_end: params.end_date,
          tasks_completed: tasksRes.data.data?.length || 0,
          total_hours: attendanceRes.data.summary?.total_hours || 0,
          attendance_rate: attendanceRes.data.summary?.attendance_rate || 0,
          productivity_score: productivityRes.data.summary?.average_productivity_score || 0,
          leave_days: leavesRes.data.summary?.total_days || 0
        }
        
        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Employee report generated successfully!'
        })
      } catch (error) {
        console.error('Error generating employee report:', error)
        this.$notify({
          type: 'error',
          title: 'Error',
          text: 'Failed to generate employee report.'
        })
      } finally {
        this.generatingReport = false
      }
    },
    
    editPerformanceMetrics(employeeId) {
      // Load current metrics for the employee
      this.selectedEmployeeId = employeeId
      this.loadPerformanceData(employeeId)
      this.showPerformanceModal = true
    },
    
    async loadPerformanceData(employeeId) {
      try {
        const response = await axios.get(`/api/manager/employees/${employeeId}/performance`)
        const data = response.data
        this.performanceData = {
          rating: data.rating || 0,
          productivity: data.productivity || 0,
          tasks_completed: data.tasks_completed || 0,
          notes: data.notes || ''
        }
      } catch (error) {
        console.error('Error loading performance data:', error)
        this.performanceData = { rating: 0, productivity: 0, tasks_completed: 0, notes: '' }
      }
    },
    
    async savePerformanceMetrics() {
      try {
        await axios.post(`/api/manager/employees/${this.selectedEmployeeId}/performance`, this.performanceData)
        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Performance metrics updated successfully!'
        })
        this.closePerformanceModal()
        // Refresh reports to include updated metrics
        await this.refreshReports()
      } catch (error) {
        console.error('Error saving performance metrics:', error)
        this.$notify({
          type: 'error',
          title: 'Error',
          text: 'Failed to update performance metrics.'
        })
      }
    },
    
    closePerformanceModal() {
      this.showPerformanceModal = false
      this.performanceData = { rating: 0, productivity: 0, tasks_completed: 0, notes: '' }
    },
    
    viewTeamReport() {
      if (this.teamReport) {
        this.$router.push({ 
          name: 'report-details', 
          query: { type: 'team', manager: true, employee_id: this.selectedEmployeeId } 
        })
      }
    },
    
    viewProductivityReport() {
      if (this.productivityReport) {
        this.$router.push({ 
          name: 'report-details', 
          query: { type: 'productivity', manager: true, employee_id: this.selectedEmployeeId } 
        })
      }
    },
    
    viewEmployeeReport() {
      if (this.employeeReport) {
        this.$router.push({ 
          name: 'report-details', 
          query: { type: 'employee', manager: true, employee_id: this.selectedEmployeeId } 
        })
      }
    },
    
    // ... (other methods remain the same: processEmployeesData, processLeavesData, etc.)
    processEmployeesData(data) {
      const employees = data.data || data || []
      return employees.map(emp => ({
        id: emp.id,
        name: `${emp.first_name || ''} ${emp.last_name || ''}`.trim() || 'Unknown Employee',
        position: emp.position || emp.job_title || 'Employee',
        department: emp.department || 'General',
        email: emp.email,
        status: 'present'
      }))
    },
    
    processLeavesData(data) {
      if (Array.isArray(data)) {
        return data.length
      } else if (data.data && Array.isArray(data.data)) {
        return data.data.length
      } else if (typeof data === 'object' && data.pending_leaves !== undefined) {
        return data.pending_leaves
      }
      return 0
    },
    
    processAttendanceData(data) {
      const today = new Date().toISOString().split('T')[0]
      
      let attendances = []
      if (Array.isArray(data)) {
        attendances = data
      } else if (data.data && Array.isArray(data.data)) {
        attendances = data.data
      } else if (data.attendances) {
        attendances = data.attendances
      }
      
      const todayAttendances = attendances.filter(att => {
        const attDate = new Date(att.date || att.created_at).toISOString().split('T')[0]
        return attDate === today
      })
      
      return {
        todayAttendances,
        totalAttendances: attendances,
        summary: data.summary || {}
      }
    },
    
    calculateTeamStats(attendanceData) {
      const { todayAttendances, summary } = attendanceData
      
      const presentEmployees = todayAttendances.filter(att => 
        att.status === 'present' || att.status === 'completed'
      ).length
      
      const lateEmployees = todayAttendances.filter(att => 
        att.status === 'late'
      ).length
      
      this.managedEmployees.forEach(employee => {
        const employeeAttendance = todayAttendances.find(att => 
          att.employee_id === employee.id || att.employee?.id === employee.id
        )
        employee.status = employeeAttendance ? employeeAttendance.status : 'absent'
      })
      
      this.attendanceIssuesCount = this.managedEmployees.filter(emp => 
        ['absent', 'late'].includes(emp.status)
      ).length
      
      this.teamStats = {
        team_size: this.managedEmployees.length,
        present_today: presentEmployees + lateEmployees,
        pending_leaves: this.pendingLeavesCount,
        avg_productivity: summary.avg_productivity || 
                         Math.round((presentEmployees / this.managedEmployees.length) * 100) || 0
      }
    },
    
    processProductivityData(data) {
      return {
        generated_at: data.generated_at || new Date().toISOString(),
        avg_productivity: data.avg_productivity || data.average_productivity || 0,
        total_tasks_completed: data.total_tasks_completed || data.completed_tasks || 0,
        avg_attendance_rate: data.avg_attendance_rate || data.average_attendance || 0
      }
    },
    
    processTeamReportData(data) {
      return {
        period_start: data.period_start || data.start_date,
        period_end: data.period_end || data.end_date,
        total_employees: data.total_employees || data.team_size,
        active_employees: data.active_employees || data.present_employees,
        on_leave: data.on_leave || data.leave_count,
        attendance_rate: data.attendance_rate || data.attendance_percentage
      }
    },
    
    getInitials(name) {
      if (!name) return '??'
      return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2)
    },
    
    getEmployeeStatus(employee) {
      return employee.status || 'absent'
    },
    
    getEmployeeStatusText(employee) {
      const status = this.getEmployeeStatus(employee)
      const statusMap = {
        present: 'Present',
        completed: 'Present',
        absent: 'Absent',
        late: 'Late',
        'on_leave': 'On Leave'
      }
      return statusMap[status] || 'Unknown'
    },
    
    viewAllTeamMembers() {
      this.$router.push({ name: 'manager-team' })
    },
    
    viewPendingLeaves() {
      this.$router.push({ name: 'manager-leaves' })
    },
    
    viewAttendanceIssues() {
      this.$router.push({ name: 'manager-attendance' })
    },
    
    retryFetch() {
      this.retryCount++
      if (this.retryCount <= 3) {
        this.fetchManagerData()
      } else {
        this.error = 'Max retries exceeded. Check your network or server.'
      }
    },
    
    handleApiError(err) {
      let errorMsg = 'An unexpected error occurred.'
      if (err.code === 'ERR_NETWORK' || err.message.includes('Network Error')) {
        errorMsg = 'Network error: Please check your connection and try again.'
      } else if (err.response?.status === 401) {
        errorMsg = 'Your session has expired. Please log in again.'
        this.authStore.clearAuth()
        this.$router.push({ name: 'login' })
      } else if (err.response?.status === 403) {
        errorMsg = 'You do not have permission to perform this action.'
      } else if (err.response?.status === 404) {
        errorMsg = 'Manager endpoints not found. Please check the API routes.'
      } else {
        errorMsg = err.response?.data?.message || errorMsg
      }
      this.error = errorMsg
    },
    
    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
  }
}
</script>

<style scoped>
/* Existing styles remain the same, add new ones for modal and filters */

.filter-section {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-group label {
  font-weight: 600;
  color: var(--text-secondary);
  font-size: 0.9rem;
}

.filter-group select {
  padding: 0.5rem;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: white;
}

.filter-info {
  color: var(--text-secondary);
  font-size: 0.9rem;
  margin: 0;
}

.btn-success {
  background: var(--success-gradient);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-success:hover {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
}

.btn-info {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-info:hover {
  background: #2563eb;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-card {
  background: white;
  border-radius: 12px;
  max-width: 500px;
  width: 90%;
  max-height: 80vh;
  overflow-y: auto;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h2 {
  margin: 0;
  color: #2d3748;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #718096;
  padding: 0.5rem;
}

.modal-body {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #2d3748;
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 1rem;
}

.form-group textarea {
  resize: vertical;
  min-height: 100px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid #e2e8f0;
}

/* Rest of existing styles... */
.reports-management {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  padding: 2rem;
  border-radius: 20px;
  box-shadow: var(--shadow-lg);
}

.header-content {
  flex: 1;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

.btn-primary, .btn-secondary {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #5a6fd8;
}

.btn-secondary {
  background: #e2e8f0;
  color: #4a5568;
}

.btn-secondary:hover:not(:disabled) {
  background: #cbd5e0;
}

.btn-primary:disabled, .btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.error-message {
  background: #fed7d7;
  color: #c53030;
  padding: 1rem;
  border-radius: 8px;
  text-align: center;
  margin: 2rem 0;
}

.loading {
  text-align: center;
  padding: 3rem;
  color: #718096;
}

.spinner {
  border: 3px solid #f3f3f3;
  border-top: 3px solid #667eea;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  font-size: 2rem;
}

.stat-content h3 {
  margin: 0 0 0.5rem 0;
  color: #718096;
  font-size: 0.875rem;
  font-weight: 600;
}

.stat-value {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
  color: #2d3748;
}

.stat-label {
  margin: 0;
  color: #718096;
  font-size: 0.875rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.section-header h2 {
  margin: 0;
  color: #2d3748;
}

.btn-view {
  background: none;
  border: 1px solid #667eea;
  color: #667eea;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-view:hover {
  background: #667eea;
  color: white;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #718096;
  background: #f7fafc;
  border-radius: 8px;
}

.report-sections {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 2rem;
  margin-bottom: 2rem;
}

.report-section {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  padding: 1.5rem;
}

.report-preview {
  background: #f7fafc;
  border-radius: 8px;
  padding: 1.5rem;
}

.preview-content h4 {
  margin: 0 0 1rem 0;
  color: #2d3748;
}

.preview-stats {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  margin-top: 1rem;
}

.preview-stat {
  color: #4a5568;
  font-size: 0.875rem;
}

.empty-preview {
  text-align: center;
  padding: 2rem;
  color: #718096;
}

.manager-info {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1.5rem;
  border-radius: 12px;
  margin-bottom: 2rem;
}

.manager-info h2 {
  margin: 0 0 0.5rem 0;
  font-size: 1.5rem;
}

.manager-subtitle {
  margin: 0;
  opacity: 0.9;
  font-size: 1rem;
}

.team-members-section {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.team-members-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
  margin-top: 1rem;
}

.team-member-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  transition: all 0.2s;
}

.team-member-card:hover {
  border-color: #667eea;
  transform: translateY(-2px);
}

.member-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.875rem;
  flex-shrink: 0;
}

.member-info h4 {
  margin: 0 0 0.25rem 0;
  color: #2d3748;
}

.member-position {
  margin: 0 0 0.25rem 0;
  color: #667eea;
  font-weight: 600;
  font-size: 0.875rem;
}

.member-department {
  margin: 0;
  color: #718096;
  font-size: 0.875rem;
}

.member-status {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-left: auto;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.status-dot.present, .status-dot.completed {
  background: #28a745;
}

.status-dot.absent {
  background: #dc3545;
}

.status-dot.late {
  background: #ffc107;
}

.status-dot.on_leave {
  background: #17a2b8;
}

.status-text {
  font-size: 0.875rem;
  color: #718096;
}

.more-members {
  grid-column: 1 / -1;
  text-align: center;
  padding: 2rem;
  color: #718096;
  border: 2px dashed #e2e8f0;
  border-radius: 8px;
}

.pending-actions {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-top: 1rem;
}

.action-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  transition: all 0.2s;
}

.action-card:hover {
  border-color: #667eea;
  transform: translateY(-2px);
}

.action-icon {
  font-size: 2rem;
}

.action-content h3 {
  margin: 0 0 0.5rem 0;
  color: #2d3748;
}

.action-count {
  margin: 0 0 1rem 0;
  color: #667eea;
  font-weight: 600;
  font-size: 1.25rem;
}

.btn-action {
  background: #667eea;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-action:hover {
  background: #5a6fd8;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
  }
  
  .header-actions {
    width: 100%;
    justify-content: flex-start;
    flex-wrap: wrap;
  }
  
  .team-members-grid {
    grid-template-columns: 1fr;
  }
  
  .team-member-card {
    flex-direction: column;
    text-align: center;
  }
  
  .member-status {
    margin-left: 0;
    margin-top: 0.5rem;
  }
  
  .action-card {
    flex-direction: column;
    text-align: center;
  }
  
  .report-sections {
    grid-template-columns: 1fr;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .filter-section {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>