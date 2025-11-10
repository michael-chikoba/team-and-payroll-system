<template>
  <div class="admin-dashboard">
    <div class="page-header">
      <div>
        <h1 class="page-title">Payroll Admin Dashboard 🚀</h1>
        <p class="page-subtitle">Welcome back! Here's a snapshot of today's HR & Payroll data.</p>
      </div>
      <div class="header-actions">
        <button @click="refreshData" class="btn-secondary">
          <span class="action-icon-small">🔄</span> Refresh Data
        </button>
        <button @click="processPayroll" class="btn-primary">
          <span class="action-icon-small">💰</span> Process Payroll
        </button>
      </div>
    </div>

    <!-- Authentication Check -->
    <div v-if="!authStore.isAuthenticated" class="error-message">
      Please log in to access the dashboard.
    </div>

    <!-- Permission Check -->
    <div v-else-if="!authStore.isAdmin" class="error-message">
      You don't have permission to access this page.
    </div>

    <!-- Loading State -->
    <div v-else-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Loading dashboard...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-message">
      {{ error }}
      <button @click="retryFetch" class="btn-primary" style="margin-top: 1rem;">Retry</button>
    </div>

    <!-- Dashboard Content -->
    <div v-else>
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon" style="background: #667eea; color: white;">👥</div>
          <div class="stat-content">
            <p class="stat-label">Total Employees</p>
            <h3 class="stat-value">{{ stats.totalEmployees }}</h3>
            <p class="stat-change positive">📈 +{{ stats.newEmployees }} this month</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background: #48bb78; color: white;">✅</div>
          <div class="stat-content">
            <p class="stat-label">Active Today</p>
            <h3 class="stat-value">{{ stats.activeToday }}</h3>
            <p class="stat-change">{{ stats.attendanceRate }}% attendance rate</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon" style="background: #f6ad55; color: white;">🏖️</div>
          <div class="stat-content">
            <p class="stat-label">On Leave</p>
            <h3 class="stat-value">{{ stats.onLeave }}</h3>
            <p class="stat-change negative">{{ stats.pendingLeaves }} pending approvals</p>
          </div>
        </div>

        <div class="stat-card payroll-highlight">
          <div class="stat-icon" style="background: #ed64a6; color: white;">💳</div>
          <div class="stat-content">
            <p class="stat-label">Monthly Payroll</p>
            <h3 class="stat-value">K{{ formatNumber(stats.monthlyPayroll) }}</h3>
            <p class="stat-change" :class="{'positive': stats.payrollChange >= 0, 'negative': stats.payrollChange < 0}">
              {{ stats.payrollChange > 0 ? '▲' : '▼' }} {{ Math.abs(stats.payrollChange) }}% vs last month
            </p>
          </div>
        </div>
      </div>

      <div class="quick-actions">
        <h2 class="section-title">Quick Actions</h2>
        <div class="actions-grid">
          <button @click="navigateToName('EmployeeManagement')" class="action-card">
            <span class="action-icon">➕</span>
            <span class="action-label">Add Employee</span>
          </button>
          <button @click="navigateToName('PayslipGeneration')" class="action-card">
            <span class="action-icon">📄</span>
            <span class="action-label">Generate Payslips</span>
          </button>
          <button @click="navigateToName('AdminReports')" class="action-card">
            <span class="action-icon">✍️</span>
            <span class="action-label">Manage Leaves</span>
          </button>
          <button @click="navigateToName('AdminReports')" class="action-card">
            <span class="action-icon">📊</span>
            <span class="action-label">View Reports</span>
          </button>
          <button @click="navigateToName('TaxConfiguration')" class="action-card">
            <span class="action-icon">📝</span>
            <span class="action-label">Tax Config</span>
          </button>
        </div>
      </div>

      <div class="content-grid">
        <div class="card">
          <div class="card-header">
            <h2 class="card-title">Recent Activity</h2>
            <button @click="navigateToName('AuditLogs')" class="btn-text">View All</button>
          </div>
          <div class="activity-list">
            <div v-for="activity in recentActivity" :key="activity.id" class="activity-item">
              <div class="activity-icon" :style="{background: activity.color, color: 'white'}">
                {{ activity.icon }}
              </div>
              <div class="activity-content">
                <p class="activity-text">{{ activity.text }}</p>
                <p class="activity-time">{{ activity.time }}</p>
              </div>
            </div>
            <div v-if="recentActivity.length === 0" class="empty-state">
              <p>No recent activity</p>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header">
            <h2 class="card-title">Pending Approvals</h2>
            <span class="badge">{{ pendingApprovals.length }} Pending</span>
          </div>
          <div class="approvals-list">
            <div v-for="approval in pendingApprovals" :key="approval.id" class="approval-item">
              <div class="approval-info">
                <p class="approval-name">{{ approval.name }}</p>
                <p class="approval-type">{{ approval.type }}</p>
                <p class="approval-date">{{ approval.date }}</p>
              </div>
              <div class="approval-actions">
                <button @click="approve(approval.id)" class="btn-approve" aria-label="Approve">✓</button>
                <button @click="reject(approval.id)" class="btn-reject" aria-label="Reject">✗</button>
              </div>
            </div>
            <div v-if="pendingApprovals.length === 0" class="empty-state">
              <p>No pending approvals</p>
            </div>
          </div>
        </div>
      </div>

      <div class="card full-width">
        <div class="card-header">
          <h2 class="card-title">Department Overview</h2>
        </div>
        <div class="departments-grid">
          <div v-for="dept in departments" :key="dept.id" class="dept-card">
            <h3 class="dept-name">{{ dept.name }}</h3>
            <div class="dept-stats">
              <div class="dept-stat">
                <span class="dept-stat-value">{{ dept.employees }}</span>
                <span class="dept-stat-label">Employees</span>
              </div>
              <div class="dept-stat">
                <span class="dept-stat-value">{{ dept.present }}</span>
                <span class="dept-stat-label">Present</span>
              </div>
              <div class="dept-stat">
                <span class="dept-stat-value">K{{ formatNumber(dept.payroll) }}</span>
                <span class="dept-stat-label">Total Payroll</span>
              </div>
            </div>
          </div>
          <div v-if="departments.length === 0" class="empty-state">
            <p>No departments found</p>
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
  name: 'AdminDashboard',
  
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  
  data() {
    return {
      stats: {
        totalEmployees: 0,
        newEmployees: 0,
        activeToday: 0,
        attendanceRate: 0,
        onLeave: 0,
        pendingLeaves: 0,
        monthlyPayroll: 0,
        payrollChange: 0
      },
      recentActivity: [],
      pendingApprovals: [],
      departments: [],
      loading: false,
      error: null,
      retryCount: 0
    }
  },
  
  mounted() {
    this.initializeComponent()
  },
  
  methods: {
    initializeComponent() {
      if (!this.authStore.isAuthenticated) {
        this.error = 'Please log in to access the dashboard.'
        return
      }
      
      if (!this.authStore.isAdmin) {
        this.error = 'You do not have permission to access this page.'
        return
      }
      
      this.fetchDashboardData()
    },
    
    async fetchDashboardData(retry = false) {
      this.loading = true
      this.error = null
      
      try {
        console.log('Fetching dashboard data... (retry:', retry, ')')
        
        // Fetch all data using correct API endpoints
        const [employeesRes, attendanceRes, leaveRes, payrollRes, auditRes, attendanceStatusRes] = await Promise.all([
          axios.get('/api/admin/employees'),
          axios.get('/api/admin/reports/attendance'),
          axios.get('/api/admin/reports/leave'),
          axios.get('/api/admin/reports/payroll'),
          axios.get('/api/admin/audit-logs'),
          axios.get('/api/admin/attendance/current-statuses')
        ])
        
        // Process employees data
        const employees = Array.isArray(employeesRes.data) ? employeesRes.data : (employeesRes.data.data || [])
        this.stats.totalEmployees = employees.length
        
        // Calculate new employees this month
        const currentMonth = new Date().getMonth()
        const currentYear = new Date().getFullYear()
        this.stats.newEmployees = employees.filter(emp => {
          if (!emp.hire_date) return false
          const hireDate = new Date(emp.hire_date)
          return hireDate.getMonth() === currentMonth && hireDate.getFullYear() === currentYear
        }).length
        
        // Process attendance report data
        const attendanceData = attendanceRes.data.data || attendanceRes.data || {}
        const attendanceStatus = attendanceStatusRes.data.data || attendanceStatusRes.data || {}
        
        // Calculate active today from attendance status
        if (Array.isArray(attendanceStatus)) {
          this.stats.activeToday = attendanceStatus.filter(status => status.is_present).length
        } else if (attendanceData.present_today !== undefined) {
          this.stats.activeToday = attendanceData.present_today
        } else {
          this.stats.activeToday = 0
        }
        
        // Calculate attendance rate
        if (this.stats.totalEmployees > 0) {
          this.stats.attendanceRate = Math.round((this.stats.activeToday / this.stats.totalEmployees) * 100)
        } else {
          this.stats.attendanceRate = 0
        }
        
        // Process leave data
        const leaveData = leaveRes.data.data || leaveRes.data || {}
        this.stats.onLeave = leaveData.on_leave || leaveData.onLeave || 0
        this.stats.pendingLeaves = leaveData.pending || leaveData.pending_count || 0
        
        // Get pending leave approvals
        if (Array.isArray(leaveData.pending_leaves)) {
          this.pendingApprovals = leaveData.pending_leaves.slice(0, 5).map(leave => ({
            id: leave.id,
            name: leave.employee?.name || `Employee #${leave.employee_id}`,
            type: this.formatLeaveType(leave.leave_type),
            date: this.formatDateRange(leave.start_date, leave.end_date)
          }))
        } else {
          this.pendingApprovals = []
        }
        
        // Process payroll data
        const payrollData = payrollRes.data.data || payrollRes.data || {}
        this.stats.monthlyPayroll = payrollData.total_payroll || payrollData.total || 0
        this.stats.payrollChange = payrollData.change_percentage || payrollData.change || 0
        
        // Process audit logs for recent activity
        const auditData = Array.isArray(auditRes.data) ? auditRes.data : (auditRes.data.data || [])
        this.recentActivity = auditData.slice(0, 5).map(log => ({
          id: log.id,
          icon: this.getActivityIcon(log.action || log.type),
          text: log.description || log.message || `${log.action} by ${log.user?.name || 'System'}`,
          time: this.formatRelativeTime(log.created_at || log.timestamp),
          color: this.getActivityColor(log.action || log.type)
        }))
        
        // Process departments from employees and attendance
        const deptMap = {}
        employees.forEach(emp => {
          const dept = emp.department || 'Unassigned'
          if (!deptMap[dept]) {
            deptMap[dept] = { 
              id: dept, 
              name: dept, 
              employees: 0, 
              present: 0, 
              payroll: 0 
            }
          }
          deptMap[dept].employees++
          deptMap[dept].payroll += parseFloat(emp.base_salary || 0)
        })
        
        // Add present count from attendance status
        if (Array.isArray(attendanceStatus)) {
          attendanceStatus.forEach(status => {
            const emp = employees.find(e => e.id === status.employee_id)
            if (emp && status.is_present) {
              const dept = emp.department || 'Unassigned'
              if (deptMap[dept]) {
                deptMap[dept].present++
              }
            }
          })
        }
        
        this.departments = Object.values(deptMap)
        
      } catch (err) {
        console.error('Dashboard fetch error:', err)
        this.handleApiError(err)
      } finally {
        this.loading = false
      }
    },
    
    formatLeaveType(type) {
      const types = {
        annual: 'Annual Leave',
        sick: 'Sick Leave',
        maternity: 'Maternity Leave',
        paternity: 'Paternity Leave',
        unpaid: 'Unpaid Leave',
        compassionate: 'Compassionate Leave'
      }
      return types[type] || type
    },
    
    formatDateRange(startDate, endDate) {
      const start = new Date(startDate)
      const end = new Date(endDate)
      const options = { month: 'short', day: 'numeric' }
      
      if (start.toDateString() === end.toDateString()) {
        return start.toLocaleDateString('en-US', options)
      }
      return `${start.toLocaleDateString('en-US', options)} - ${end.toLocaleDateString('en-US', options)}`
    },
    
    retryFetch() {
      this.retryCount++
      if (this.retryCount <= 3) {
        this.fetchDashboardData(true)
      } else {
        this.error = 'Max retries exceeded. Check your network or server.'
      }
    },
    
    refreshData() {
      this.retryCount = 0
      this.fetchDashboardData()
    },
    
    processPayroll() {
      this.$router.push({ name: 'PayrollProcessing' })
    },
    
    navigateToName(name) {
      this.$router.push({ name: name })
    },
    
    approve(id) {
      console.log('Approving leave:', id)
      axios.post(`/api/manager/leaves/${id}/approve`)
        .then(response => {
          console.log('Leave approved successfully')
          this.pendingApprovals = this.pendingApprovals.filter(a => a.id !== id)
          // Refresh stats
          this.stats.pendingLeaves = Math.max(0, this.stats.pendingLeaves - 1)
        })
        .catch(err => {
          console.error('Error approving leave:', err)
          alert('Failed to approve leave: ' + (err.response?.data?.message || 'Unknown error'))
        })
    },
    
    reject(id) {
      console.log('Rejecting leave:', id)
      axios.post(`/api/manager/leaves/${id}/reject`)
        .then(response => {
          console.log('Leave rejected successfully')
          this.pendingApprovals = this.pendingApprovals.filter(a => a.id !== id)
          // Refresh stats
          this.stats.pendingLeaves = Math.max(0, this.stats.pendingLeaves - 1)
        })
        .catch(err => {
          console.error('Error rejecting leave:', err)
          alert('Failed to reject leave: ' + (err.response?.data?.message || 'Unknown error'))
        })
    },
    
    getActivityIcon(type) {
      const icons = {
        employee_added: '👤',
        employee_created: '👤',
        employee_updated: '✏️',
        employee_deleted: '🗑️',
        payroll_processed: '💰',
        payroll_generated: '💰',
        leave_applied: '🏖️',
        leave_approved: '✅',
        leave_rejected: '❌',
        payslip_generated: '📄',
        payslip_created: '📄',
        tax_updated: '⚙️',
        settings_updated: '⚙️',
        attendance_marked: '✓',
        login: '🔐',
        logout: '🚪'
      }
      return icons[type] || 'ℹ️'
    },
    
    getActivityColor(type) {
      const colors = {
        employee_added: '#667eea',
        employee_created: '#667eea',
        employee_updated: '#4299e1',
        employee_deleted: '#e53e3e',
        payroll_processed: '#48bb78',
        payroll_generated: '#48bb78',
        leave_applied: '#f6ad55',
        leave_approved: '#48bb78',
        leave_rejected: '#e53e3e',
        payslip_generated: '#ed64a6',
        payslip_created: '#ed64a6',
        tax_updated: '#667eea',
        settings_updated: '#667eea',
        attendance_marked: '#48bb78',
        login: '#4299e1',
        logout: '#718096'
      }
      return colors[type] || '#718096'
    },
    
    formatRelativeTime(timestamp) {
      const now = new Date()
      const then = new Date(timestamp)
      const diff = now - then
      const hours = Math.floor(diff / (1000 * 60 * 60))
      if (hours < 1) return 'Just now'
      if (hours < 24) return `${hours}h ago`
      const days = Math.floor(hours / 24)
      return `${days}d ago`
    },
    
    formatNumber(num) {
      return new Intl.NumberFormat('en-US').format(num || 0)
    },
    
    handleApiError(err) {
      let errorMsg = 'An unexpected error occurred.'
      if (err.response?.status === 401) {
        errorMsg = 'Session expired. Please log in again.'
        this.authStore.clearAuth()
        this.$router.push({ name: 'login' })
      } else if (err.response?.status === 403) {
        errorMsg = 'Access denied.'
      } else {
        errorMsg = err.response?.data?.message || errorMsg
      }
      this.error = errorMsg
    }
  }
}
</script>

<style scoped>
.admin-dashboard {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
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
  padding: 1rem;
  border-radius: 8px;
  text-align: center;
}

.empty-state {
  text-align: center;
  padding: 2rem;
  color: #718096;
}

.empty-state p {
  margin: 0;
}

/* Page Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 0.5rem 0;
}

.page-subtitle {
  color: #718096;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

.action-icon-small {
  font-size: 1.1em;
}

.btn-primary, .btn-secondary {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
  color: white;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 123, 255, 0.4);
}

.btn-secondary {
  background: white;
  color: #4a5568;
  border: 1px solid #e2e8f0;
}

.btn-secondary:hover {
  background: #f7fafc;
}

/* Stats Grid */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  gap: 1rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: transform 0.2s;
  border: 1px solid transparent;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.stat-card.payroll-highlight {
  border-image: linear-gradient(45deg, #ed64a6, #ffc107) 1;
  border-width: 2px;
  border-style: solid;
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  flex-shrink: 0;
  color: white;
}

.stat-content {
  flex: 1;
}

.stat-label {
  color: #718096;
  font-size: 0.875rem;
  margin: 0 0 0.5rem 0;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 0.5rem 0;
}

.stat-change {
  font-size: 0.875rem;
  color: #718096;
  margin: 0;
}

.stat-change.positive {
  color: #48bb78;
  font-weight: 600;
}

.stat-change.negative {
  color: #e53e3e;
  font-weight: 600;
}

/* Quick Actions */
.quick-actions {
  margin-bottom: 2rem;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1a202c;
  margin: 0 0 1rem 0;
}

.actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
}

.action-card {
  background: white;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
  text-align: center;
}

.action-card:hover {
  border-color: #007bff;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 123, 255, 0.2);
}

.action-icon {
  font-size: 2rem;
}

.action-label {
  font-weight: 600;
  color: #4a5568;
  font-size: 0.95rem;
}

/* Content Grid */
.content-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); 
  gap: 1.5rem;
  margin-bottom: 2rem;
}

/* Card */
.card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card.full-width {
  grid-column: 1 / -1;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.card-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1a202c;
  margin: 0;
}

.btn-text {
  background: none;
  border: none;
  color: #007bff;
  font-weight: 600;
  cursor: pointer;
  font-size: 0.875rem;
}

.btn-text:hover {
  text-decoration: underline;
}

.badge {
  background: #fff5e7;
  color: #f6ad55;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 600;
}

/* Activity List */
.activity-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.activity-item {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.activity-icon {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.activity-content {
  flex: 1;
}

.activity-text {
  color: #1a202c;
  margin: 0 0 0.25rem 0;
  font-size: 0.9rem;
}

.activity-time {
  color: #718096;
  font-size: 0.8rem;
  margin: 0;
}

/* Approvals List */
.approvals-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.approval-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: #f7fafc;
  border-radius: 8px;
}

.approval-info {
  flex-grow: 1;
}

.approval-name {
  font-weight: 600;
  color: #1a202c;
  margin: 0 0 0.25rem 0;
}

.approval-type {
  color: #4a5568;
  font-size: 0.875rem;
  margin: 0 0 0.25rem 0;
}

.approval-date {
  color: #718096;
  font-size: 0.8rem;
  margin: 0;
}

.approval-actions {
  display: flex;
  gap: 0.5rem;
  flex-shrink: 0;
}

.btn-approve, .btn-reject {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  font-size: 1.125rem;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-approve {
  background: #9ae6b4;
  color: #2f855a;
}

.btn-approve:hover {
  background: #48bb78;
  color: white;
}

.btn-reject {
  background: #feb2b2;
  color: #c53030;
}

.btn-reject:hover {
  background: #e53e3e;
  color: white;
}

/* Departments */
.departments-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.dept-card {
  padding: 1.5rem;
  background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.dept-name {
  font-size: 1rem;
  font-weight: 600;
  color: #1a202c;
  margin: 0 0 1rem 0;
}

.dept-stats {
  display: flex;
  justify-content: space-between;
  gap: 0.5rem;
}

.dept-stat {
  text-align: center;
}

.dept-stat-value {
  display: block;
  font-size: 1.25rem;
  font-weight: 700;
  color: #007bff;
  margin-bottom: 0.25rem;
}

.dept-stat-label {
  display: block;
  font-size: 0.75rem;
  color: #718096;
}

/* Responsive */
@media (max-width: 768px) {
  .admin-dashboard {
    padding: 1rem;
  }

  .page-header {
    flex-direction: column;
    align-items: stretch;
  }

  .header-actions {
    margin-top: 1rem;
    justify-content: space-between;
  }

  .btn-primary, .btn-secondary {
    flex: 1;
    justify-content: center;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .content-grid {
    grid-template-columns: 1fr;
  }

  .actions-grid {
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  }

  .departments-grid {
    grid-template-columns: 1fr;
  }

  .dept-stats {
    flex-direction: column;
    gap: 0.75rem;
  }

  .dept-stat {
    text-align: left;
  }
}

@media (max-width: 480px) {
  .page-title {
    font-size: 1.5rem;
  }

  .stat-value {
    font-size: 1.5rem;
  }

  .stat-icon {
    width: 50px;
    height: 50px;
    font-size: 1.5rem;
  }
}
</style>