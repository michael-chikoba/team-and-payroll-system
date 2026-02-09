<template>
  <div class="reports-management">
    <div class="page-header">
      <div class="header-content">
        <h1>📄 Schedule Reports</h1>
        <p class="subtitle">View and manage submitted schedule reports from your team</p>
        <div class="filter-section">
          <div class="filter-group">
            <label for="employee-filter">Filter by Employee:</label>
            <select id="employee-filter" v-model="selectedEmployeeId" @change="handleEmployeeFilter">
              <option value="">All Team Members</option>
              <option v-for="employee in managedEmployees" :key="employee.id" :value="employee.id">
                {{ employee.name }}
              </option>
            </select>
          </div>
          <div v-if="selectedEmployeeId" class="filter-info">
            <span class="filter-badge">
              Viewing: {{ getEmployeeName(selectedEmployeeId) }}
              <button @click="clearEmployeeFilter" class="clear-filter">✕</button>
            </span>
          </div>
        </div>
      </div>
      <div class="header-actions">
        <button 
          @click="viewScheduleReports" 
          class="btn-primary"
        >
          <span>📋</span> View All Reports
        </button>
      </div>
    </div>

    <!-- Authentication Check -->
    <div v-if="!authStore.isAuthenticated" class="error-message">
      <span class="error-icon">🔒</span>
      <div>
        <h3>Authentication Required</h3>
        <p>Please log in to access schedule reports.</p>
      </div>
    </div>

    <!-- Permission Check -->
    <div v-else-if="!authStore.isManager" class="error-message">
      <span class="error-icon">⛔</span>
      <div>
        <h3>Access Denied</h3>
        <p>You don't have permission to access this page.</p>
      </div>
    </div>

    <!-- Loading State -->
    <div v-else-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Loading schedule reports...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-message">
      <span class="error-icon">⚠️</span>
      <div>
        <h3>Error</h3>
        <p>{{ error }}</p>
        <button @click="retryFetch" class="btn-retry">Retry</button>
      </div>
    </div>

    <!-- Reports Dashboard -->
    <div v-else class="reports-dashboard">
      <!-- Manager Info -->
      <div class="manager-info">
        <div class="manager-info-content">
          <h2>👨‍💼 Team Overview</h2>
          <p class="manager-subtitle">
            Managing {{ managedEmployees.length }} team member{{ managedEmployees.length !== 1 ? 's' : '' }}
            {{ selectedEmployeeId ? ` - Focusing on ${getEmployeeName(selectedEmployeeId)}` : '' }}
          </p>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="stats-grid">
        <div class="stat-card total">
          <div class="stat-icon">📊</div>
          <div class="stat-content">
            <h3>Total Reports</h3>
            <p class="stat-value">{{ scheduleReportsStats.total || 0 }}</p>
            <p class="stat-label">All Time</p>
          </div>
        </div>
        
        <div class="stat-card pending">
          <div class="stat-icon">📝</div>
          <div class="stat-content">
            <h3>Pending Review</h3>
            <p class="stat-value">{{ scheduleReportsStats.submitted || 0 }}</p>
            <p class="stat-label">Awaiting Action</p>
          </div>
        </div>
        
        <div class="stat-card approved">
          <div class="stat-icon">✅</div>
          <div class="stat-content">
            <h3>Approved</h3>
            <p class="stat-value">{{ scheduleReportsStats.approved || 0 }}</p>
            <p class="stat-label">Completed</p>
          </div>
        </div>
        
        <div class="stat-card rejected">
          <div class="stat-icon">❌</div>
          <div class="stat-content">
            <h3>Rejected</h3>
            <p class="stat-value">{{ scheduleReportsStats.rejected || 0 }}</p>
            <p class="stat-label">Needs Revision</p>
          </div>
        </div>
      </div>

      <!-- Recent Schedule Reports -->
      <div class="schedule-reports-section">
        <div class="section-header">
          <div class="section-title">
            <h2>📄 Recent Submissions</h2>
            <span class="section-count">{{ recentScheduleReports.length }} report{{ recentScheduleReports.length !== 1 ? 's' : '' }}</span>
          </div>
          <button @click="viewScheduleReports" class="btn-view-all">
            View All Reports
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
          </button>
        </div>
        
        <!-- Status Filter Pills -->
        <div class="status-filters">
          <button 
            :class="['filter-pill', { active: statusFilter === '' }]"
            @click="statusFilter = ''; fetchScheduleReports()"
          >
            All
          </button>
          <button 
            :class="['filter-pill submitted', { active: statusFilter === 'submitted' }]"
            @click="statusFilter = 'submitted'; fetchScheduleReports()"
          >
            Submitted ({{ scheduleReportsStats.submitted }})
          </button>
          <button 
            :class="['filter-pill reviewed', { active: statusFilter === 'reviewed' }]"
            @click="statusFilter = 'reviewed'; fetchScheduleReports()"
          >
            Reviewed ({{ scheduleReportsStats.reviewed }})
          </button>
          <button 
            :class="['filter-pill approved', { active: statusFilter === 'approved' }]"
            @click="statusFilter = 'approved'; fetchScheduleReports()"
          >
            Approved ({{ scheduleReportsStats.approved }})
          </button>
          <button 
            :class="['filter-pill rejected', { active: statusFilter === 'rejected' }]"
            @click="statusFilter = 'rejected'; fetchScheduleReports()"
          >
            Rejected ({{ scheduleReportsStats.rejected }})
          </button>
        </div>

        <!-- Reports List -->
        <div v-if="recentScheduleReports.length > 0" class="recent-reports">
          <div class="reports-list">
            <div 
              v-for="report in recentScheduleReports" 
              :key="report.id"
              @click="viewReportDetails(report)"
              class="report-item"
            >
              <div class="report-item-header">
                <div class="report-item-title">
                  <h4>{{ getReportScheduleTitle(report) }}</h4>
                  <span class="report-employee">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    {{ getReportEmployeeName(report) }}
                  </span>
                </div>
                <span :class="['report-status-badge', getReportStatusClass(report.status)]">
                  {{ formatReportStatus(report.status) }}
                </span>
              </div>
              
              <div class="report-item-meta">
                <span class="report-meta-item">
                  <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                  </svg>
                  {{ formatDate(report.created_at) }}
                </span>
                
                <span class="report-meta-item report-type">
                  <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                  {{ formatReportType(report.report_type) }}
                </span>
                
                <span v-if="report.file_name" class="report-meta-item file">
                  <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                  </svg>
                  {{ report.file_name }}
                </span>
              </div>

              <!-- Metadata Preview -->
              <div v-if="hasReportMetadata(report)" class="report-metadata">
                <span v-if="report.metadata?.hours_worked" class="metadata-badge">
                  ⏱️ {{ report.metadata.hours_worked }}h worked
                </span>
                <span v-if="report.metadata?.tasks_completed" class="metadata-badge">
                  ✓ {{ report.metadata.tasks_completed }} tasks
                </span>
              </div>

              <!-- Quick Actions -->
              <div class="report-quick-actions">
                <button 
                  @click.stop="viewReportDetails(report)" 
                  class="quick-action-btn view"
                >
                  View Details
                </button>
                <button 
                  v-if="report.status === 'submitted'"
                  @click.stop="quickReview(report, 'approved')" 
                  class="quick-action-btn approve"
                >
                  ✓ Approve
                </button>
                <button 
                  v-if="report.status === 'submitted'"
                  @click.stop="quickReview(report, 'rejected')" 
                  class="quick-action-btn reject"
                >
                  ✕ Reject
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <div v-else class="empty-reports">
          <div class="empty-icon">📄</div>
          <h3>No Reports Found</h3>
          <p v-if="statusFilter">No {{ statusFilter }} reports found{{ selectedEmployeeId ? ' for this employee' : '' }}.</p>
          <p v-else>No schedule reports have been submitted yet{{ selectedEmployeeId ? ' by this employee' : '' }}.</p>
          <button v-if="statusFilter || selectedEmployeeId" @click="clearFilters" class="btn-secondary">
            Clear Filters
          </button>
        </div>
      </div>

      <!-- Team Members Quick View -->
      <div class="team-members-section">
        <div class="section-header">
          <div class="section-title">
            <h2>👥 Team Members</h2>
            <span class="section-count">{{ managedEmployees.length }}</span>
          </div>
        </div>
        
        <div v-if="managedEmployees.length === 0" class="empty-state">
          <p>No team members assigned to you yet.</p>
        </div>
        
        <div v-else class="team-members-grid">
          <div 
            v-for="employee in managedEmployees.slice(0, 8)" 
            :key="employee.id" 
            class="team-member-card"
            @click="filterByEmployee(employee.id)"
          >
            <div class="member-avatar">
              {{ getInitials(employee.name) }}
            </div>
            <div class="member-info">
              <h4>{{ employee.name }}</h4>
              <p class="member-position">{{ employee.position || 'Employee' }}</p>
              <p class="member-department">{{ employee.department || 'General' }}</p>
            </div>
          </div>
          
          <div v-if="managedEmployees.length > 8" class="more-members">
            <p>+{{ managedEmployees.length - 8 }} more</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Schedule Reports Modal -->
    <ScheduleReportsModal
      :show="showScheduleReportsModal"
      :employee-id="selectedEmployeeId"
      :managed-employees="managedEmployees"
      @close="showScheduleReportsModal = false"
      @report-reviewed="handleReportReviewed"
    />

    <!-- Report Details Modal -->
    <ReportDetailsModal
      :show="showReportDetailsModal"
      :report="selectedReport"
      @close="showReportDetailsModal = false"
      @review="handleReviewReport"
      @download="handleDownloadReport"
    />
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import ScheduleReportsModal from '@/components/reports/ScheduleReportsModal.vue'
import ReportDetailsModal from '@/components/reports/ReportDetailsModal.vue'

export default {
  name: 'TeamReports',
  
  components: {
    ScheduleReportsModal,
    ReportDetailsModal
  },
  
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  
  data() {
    return {
      loading: false,
      error: null,
      selectedEmployeeId: null,
      statusFilter: '',
      managedEmployees: [],
      retryCount: 0,
      
      // Schedule Reports Data
      showScheduleReportsModal: false,
      showReportDetailsModal: false,
      selectedReport: null,
      recentScheduleReports: [],
      scheduleReportsStats: {
        total: 0,
        submitted: 0,
        reviewed: 0,
        approved: 0,
        rejected: 0
      }
    }
  },
  
  mounted() {
    this.initializeComponent()
  },
  
  methods: {
    initializeComponent() {
      if (!this.authStore.isAuthenticated) {
        this.error = 'Please log in to access schedule reports.'
        return
      }
      
      if (!this.authStore.isManager) {
        this.error = 'You do not have permission to access this page.'
        return
      }
      
      this.fetchManagerData()
    },
    
    handleEmployeeFilter() {
      this.fetchScheduleReports()
    },
    
    filterByEmployee(employeeId) {
      this.selectedEmployeeId = employeeId
      this.fetchScheduleReports()
    },
    
    clearEmployeeFilter() {
      this.selectedEmployeeId = null
      this.fetchScheduleReports()
    },
    
    clearFilters() {
      this.selectedEmployeeId = null
      this.statusFilter = ''
      this.fetchScheduleReports()
    },
    
    getEmployeeName(employeeId) {
      const employee = this.managedEmployees.find(emp => emp.id === employeeId)
      return employee ? employee.name : 'Unknown Employee'
    },
    
    getReportEmployeeName(report) {
      // Try multiple ways to get employee name
      if (report.employee) {
        // If employee object exists with name
        if (report.employee.name) {
          return report.employee.name
        }
        // Try to construct from first_name and last_name
        if (report.employee.first_name || report.employee.last_name) {
          return `${report.employee.first_name || ''} ${report.employee.last_name || ''}`.trim()
        }
      }
      
      // Fallback to looking up in managedEmployees
      if (report.employee_id) {
        const employee = this.managedEmployees.find(emp => emp.id === report.employee_id)
        if (employee) {
          return employee.name
        }
      }
      
      return 'Unknown Employee'
    },
    
    getReportScheduleTitle(report) {
      if (report.schedule && report.schedule.title) {
        return report.schedule.title
      }
      return 'Untitled Schedule'
    },
    
    hasReportMetadata(report) {
      return report.metadata && 
             (report.metadata.hours_worked || report.metadata.tasks_completed)
    },
    
    async fetchManagerData() {
      this.loading = true
      this.error = null
      
      try {
        console.log('Fetching manager team data...')
        
        const employeesRes = await axios.get('/api/manager/employees')
        
        console.log('Managed employees response:', employeesRes.data)
        
        this.managedEmployees = this.processEmployeesData(employeesRes.data)
        
        console.log('Processed employees:', this.managedEmployees)
        
        await this.fetchScheduleReports()
        
      } catch (err) {
        console.error('Fetch error:', err)
        this.handleApiError(err)
      } finally {
        this.loading = false
      }
    },
    
    async fetchScheduleReports() {
      try {
        const params = {
          employee_id: this.selectedEmployeeId || null,
          status: this.statusFilter || null,
          per_page: 10,
          sort_by: 'created_at',
          sort_order: 'desc'
        }
        
        console.log('Fetching schedule reports with params:', params)
        
        const response = await axios.get('/api/schedule-reports', { params })
        
        console.log('Schedule reports response:', response.data)
        
        // Process reports to ensure employee data is available
        const reports = response.data.reports?.data || []
        this.recentScheduleReports = reports.map(report => {
          // Ensure employee name is set properly
          if (report.employee) {
            if (!report.employee.name && (report.employee.first_name || report.employee.last_name)) {
              report.employee.name = `${report.employee.first_name || ''} ${report.employee.last_name || ''}`.trim()
            }
          }
          return report
        })
        
        console.log('Processed reports:', this.recentScheduleReports)
        
        this.scheduleReportsStats = response.data.stats || {
          total: 0,
          submitted: 0,
          reviewed: 0,
          approved: 0,
          rejected: 0
        }
      } catch (error) {
        console.error('Error fetching schedule reports:', error)
        this.$notify({
          type: 'error',
          title: 'Error',
          text: 'Failed to load schedule reports.'
        })
      }
    },
    
    viewScheduleReports() {
      this.showScheduleReportsModal = true
    },
    
    viewReportDetails(report) {
      this.selectedReport = report
      this.showReportDetailsModal = true
    },
    
    async quickReview(report, status) {
      try {
        await axios.put(`/api/schedule-reports/${report.id}/review`, {
          status,
          review_comments: status === 'approved' ? 'Quick approved' : 'Requires revision'
        })
        
        this.$notify({
          type: 'success',
          title: 'Success',
          text: `Report ${status} successfully!`
        })
        
        await this.fetchScheduleReports()
      } catch (error) {
        console.error('Error reviewing report:', error)
        this.$notify({
          type: 'error',
          title: 'Error',
          text: 'Failed to review report.'
        })
      }
    },
    
    async handleReviewReport(reportId, status, comments) {
      try {
        await axios.put(`/api/schedule-reports/${reportId}/review`, {
          status,
          review_comments: comments
        })
        
        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Report reviewed successfully!'
        })
        
        await this.fetchScheduleReports()
        this.showReportDetailsModal = false
      } catch (error) {
        console.error('Error reviewing report:', error)
        this.$notify({
          type: 'error',
          title: 'Error',
          text: 'Failed to review report.'
        })
      }
    },
    
    async handleDownloadReport(reportId) {
      try {
        const response = await axios.get(`/api/schedule-reports/${reportId}/download`, {
          responseType: 'blob'
        })
        
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        
        const contentDisposition = response.headers['content-disposition']
        let filename = 'report.pdf'
        if (contentDisposition) {
          const filenameMatch = contentDisposition.match(/filename="?(.+)"?/)
          if (filenameMatch) {
            filename = filenameMatch[1]
          }
        }
        
        link.setAttribute('download', filename)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
      } catch (error) {
        console.error('Error downloading report:', error)
        this.$notify({
          type: 'error',
          title: 'Error',
          text: 'Failed to download report file.'
        })
      }
    },
    
    handleReportReviewed() {
      this.fetchScheduleReports()
    },
    
    getReportStatusClass(status) {
      const classes = {
        submitted: 'status-submitted',
        reviewed: 'status-reviewed',
        approved: 'status-approved',
        rejected: 'status-rejected'
      }
      return classes[status] || 'status-submitted'
    },
    
    formatReportStatus(status) {
      return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown'
    },
    
    formatReportType(type) {
      const types = {
        text: 'Text',
        file: 'File',
        both: 'Text & File'
      }
      return types[type] || type
    },
    
    processEmployeesData(data) {
      console.log('Processing employees data:', data)
      
      // Handle different response formats
      let employees = []
      
      if (Array.isArray(data)) {
        employees = data
      } else if (data.data && Array.isArray(data.data)) {
        employees = data.data
      } else if (data.employees && Array.isArray(data.employees)) {
        employees = data.employees
      }
      
      console.log('Extracted employees array:', employees)
      
      // Process each employee
      const processed = employees.map(emp => {
        // Build full name
        let fullName = ''
        
        if (emp.name) {
          fullName = emp.name
        } else if (emp.first_name || emp.last_name) {
          fullName = `${emp.first_name || ''} ${emp.last_name || ''}`.trim()
        } else if (emp.user && (emp.user.first_name || emp.user.last_name)) {
          fullName = `${emp.user.first_name || ''} ${emp.user.last_name || ''}`.trim()
        }
        
        // If still no name, use email or ID
        if (!fullName) {
          fullName = emp.email || `Employee #${emp.id}`
        }
        
        return {
          id: emp.id,
          name: fullName,
          position: emp.position || emp.job_title || 'Employee',
          department: emp.department || 'General',
          email: emp.email || '',
          first_name: emp.first_name || '',
          last_name: emp.last_name || ''
        }
      })
      
      console.log('Processed employees:', processed)
      
      return processed
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
    
    retryFetch() {
      this.retryCount++
      if (this.retryCount <= 3) {
        this.fetchManagerData()
      } else {
        this.error = 'Max retries exceeded. Please check your network or server.'
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
      try {
        return new Date(date).toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit'
        })
      } catch (e) {
        console.error('Date formatting error:', e)
        return 'Invalid Date'
      }
    }
  }
}
</script>

<style scoped>
/* Same styles as before - keeping them identical */
.reports-management {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem;
  border-radius: 16px;
  color: white;
}

.header-content {
  flex: 1;
}

.header-content h1 {
  margin: 0 0 0.5rem 0;
  font-size: 2rem;
  font-weight: 700;
}

.subtitle {
  margin: 0 0 1.5rem 0;
  opacity: 0.9;
  font-size: 1rem;
}

.filter-section {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.filter-group label {
  font-weight: 600;
  font-size: 0.875rem;
  white-space: nowrap;
}

.filter-group select {
  padding: 0.5rem 1rem;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  min-width: 200px;
}

.filter-group select option {
  color: #2d3748;
}

.filter-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 600;
}

.clear-filter {
  background: rgba(255, 255, 255, 0.3);
  border: none;
  color: white;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  transition: all 0.2s;
}

.clear-filter:hover {
  background: rgba(255, 255, 255, 0.5);
}

.header-actions {
  display: flex;
  gap: 1rem;
}

.btn-primary {
  padding: 0.75rem 1.5rem;
  background: white;
  color: #667eea;
  border: none;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
}

.error-message {
  background: white;
  border-left: 4px solid #ef4444;
  padding: 2rem;
  border-radius: 12px;
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin: 2rem 0;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.error-icon {
  font-size: 2rem;
}

.error-message h3 {
  margin: 0 0 0.5rem 0;
  color: #c53030;
}

.error-message p {
  margin: 0 0 1rem 0;
  color: #718096;
}

.btn-retry {
  padding: 0.5rem 1rem;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}

.loading {
  text-align: center;
  padding: 4rem;
  color: #718096;
}

.spinner {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.manager-info {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.manager-info-content h2 {
  margin: 0 0 0.5rem 0;
  color: #2d3748;
  font-size: 1.5rem;
}

.manager-subtitle {
  margin: 0;
  color: #718096;
  font-size: 1rem;
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
  border-left: 4px solid #cbd5e0;
  transition: all 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 16px rgba(0,0,0,0.12);
}

.stat-card.total {
  border-left-color: #667eea;
}

.stat-card.pending {
  border-left-color: #f59e0b;
}

.stat-card.approved {
  border-left-color: #10b981;
}

.stat-card.rejected {
  border-left-color: #ef4444;
}

.stat-icon {
  font-size: 2.5rem;
}

.stat-content h3 {
  margin: 0 0 0.5rem 0;
  color: #718096;
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-value {
  margin: 0 0 0.25rem 0;
  font-size: 2.5rem;
  font-weight: 700;
  color: #2d3748;
}

.stat-label {
  margin: 0;
  color: #a0aec0;
  font-size: 0.875rem;
}

.schedule-reports-section {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  padding: 2rem;
  margin-bottom: 2rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.section-title h2 {
  margin: 0;
  color: #2d3748;
  font-size: 1.25rem;
}

.section-count {
  padding: 0.25rem 0.75rem;
  background: #f7fafc;
  border-radius: 9999px;
  font-size: 0.875rem;
  color: #718096;
  font-weight: 600;
}

.btn-view-all {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: none;
  border: 2px solid #667eea;
  color: #667eea;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-view-all:hover {
  background: #667eea;
  color: white;
}

.status-filters {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.filter-pill {
  padding: 0.5rem 1rem;
  border: 2px solid #e2e8f0;
  background: white;
  color: #718096;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.filter-pill:hover {
  border-color: #cbd5e0;
  background: #f7fafc;
}

.filter-pill.active {
  border-color: #667eea;
  background: #667eea;
  color: white;
}

.filter-pill.submitted.active {
  border-color: #f59e0b;
  background: #f59e0b;
}

.filter-pill.reviewed.active {
  border-color: #8b5cf6;
  background: #8b5cf6;
}

.filter-pill.approved.active {
  border-color: #10b981;
  background: #10b981;
}

.filter-pill.rejected.active {
  border-color: #ef4444;
  background: #ef4444;
}

.reports-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.report-item {
  padding: 1.5rem;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
}

.report-item:hover {
  border-color: #667eea;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
}

.report-item-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 1rem;
}

.report-item-title h4 {
  margin: 0 0 0.5rem 0;
  font-size: 1.125rem;
  font-weight: 700;
  color: #2d3748;
}

.report-employee {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.875rem;
  color: #718096;
}

.report-status-badge {
  padding: 0.375rem 1rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 700;
  white-space: nowrap;
}

.status-submitted {
  background: #fef3c7;
  color: #92400e;
}

.status-reviewed {
  background: #e0e7ff;
  color: #3730a3;
}

.status-approved {
  background: #d1fae5;
  color: #065f46;
}

.status-rejected {
  background: #fee2e2;
  color: #991b1b;
}

.report-item-meta {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #f7fafc;
}

.report-meta-item {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.875rem;
  color: #718096;
}

.report-metadata {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
}

.metadata-badge {
  padding: 0.375rem 0.75rem;
  background: #f7fafc;
  border-radius: 6px;
  font-size: 0.875rem;
  color: #4a5568;
  font-weight: 600;
}

.report-quick-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #f7fafc;
}

.quick-action-btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.quick-action-btn.view {
  background: #f7fafc;
  color: #4a5568;
}

.quick-action-btn.view:hover {
  background: #e2e8f0;
}

.quick-action-btn.approve {
  background: #d1fae5;
  color: #065f46;
}

.quick-action-btn.approve:hover {
  background: #a7f3d0;
}

.quick-action-btn.reject {
  background: #fee2e2;
  color: #991b1b;
}

.quick-action-btn.reject:hover {
  background: #fecaca;
}

.empty-reports {
  text-align: center;
  padding: 4rem 2rem;
  color: #718096;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
  opacity: 0.3;
}

.empty-reports h3 {
  margin: 0 0 0.5rem 0;
  color: #2d3748;
}

.empty-reports p {
  margin: 0 0 1.5rem 0;
}

.btn-secondary {
  padding: 0.75rem 1.5rem;
  background: #e2e8f0;
  color: #4a5568;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #cbd5e0;
}

.team-members-section {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  padding: 2rem;
}

.team-members-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1rem;
  margin-top: 1.5rem;
}

.team-member-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.2s;
}

.team-member-card:hover {
  border-color: #667eea;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
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

.member-info {
  flex: 1;
}

.member-info h4 {
  margin: 0 0 0.25rem 0;
  color: #2d3748;
  font-size: 1rem;
}

.member-position {
  margin: 0 0 0.125rem 0;
  color: #667eea;
  font-weight: 600;
  font-size: 0.875rem;
}

.member-department {
  margin: 0;
  color: #a0aec0;
  font-size: 0.875rem;
}

.more-members {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  border: 2px dashed #e2e8f0;
  border-radius: 12px;
  color: #718096;
  font-weight: 600;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #718096;
  background: #f7fafc;
  border-radius: 8px;
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    gap: 1.5rem;
  }
  
  .header-actions {
    width: 100%;
  }
  
  .btn-primary {
    width: 100%;
    justify-content: center;
  }
  
  .filter-section {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filter-group {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filter-group select {
    width: 100%;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .status-filters {
    flex-wrap: nowrap;
    overflow-x: auto;
    padding-bottom: 0.5rem;
  }
  
  .team-members-grid {
    grid-template-columns: 1fr;
  }
}
</style>