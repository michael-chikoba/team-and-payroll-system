<template>
  <div class="reports-management">
    <div class="page-header">
      <h1>Organization Reports</h1>
      <div class="header-actions">
        <button 
          @click="generateAttendanceReport" 
          class="btn-primary"
          :disabled="generatingReport"
        >
          <span>📊</span> {{ generatingReport ? 'Generating...' : 'Generate Attendance Report' }}
        </button>
        <button 
          @click="generateLeaveReport" 
          class="btn-secondary"
          :disabled="generatingReport"
        >
          <span>📋</span> {{ generatingReport ? 'Generating...' : 'Generate Leave Report' }}
        </button>
        <button 
          @click="generatePayrollReport" 
          class="btn-tertiary"
          :disabled="generatingReport"
        >
          <span>💰</span> {{ generatingReport ? 'Generating...' : 'Generate Payroll Report' }}
        </button>
      </div>
    </div>

    <!-- Authentication Check -->
    <div v-if="!authStore.isAuthenticated" class="error-message">
      Please log in to access admin reports.
    </div>

    <!-- Permission Check -->
    <div v-else-if="!authStore.isAdmin" class="error-message">
      You don't have permission to access this page.
    </div>

    <!-- Loading State -->
    <div v-else-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Loading organization reports...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-message">
      {{ error }}
      <button @click="retryFetch" class="btn-primary" style="margin-top: 1rem;">Retry</button>
    </div>

    <!-- Reports Dashboard -->
    <div v-else class="reports-dashboard">
      <!-- Admin Overview -->
      <div class="admin-info">
        <h2>🏢 Organization Overview</h2>
        <p class="admin-subtitle">Company-wide performance metrics and reports</p>
      </div>

      <!-- Quick Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">👥</div>
          <div class="stat-content">
            <h3>Total Employees</h3>
            <p class="stat-value">{{ orgStats.total_employees || 0 }}</p>
            <p class="stat-label">All Staff</p>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">✅</div>
          <div class="stat-content">
            <h3>Present Today</h3>
            <p class="stat-value">{{ orgStats.present_today || 0 }}</p>
            <p class="stat-label">Active Employees</p>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">📅</div>
          <div class="stat-content">
            <h3>Pending Leaves</h3>
            <p class="stat-value">{{ orgStats.pending_leaves || 0 }}</p>
            <p class="stat-label">Company-Wide</p>
          </div>
        </div>
        
        <div class="stat-card">
          <div class="stat-icon">📊</div>
          <div class="stat-content">
            <h3>Avg. Attendance</h3>
            <p class="stat-value">{{ orgStats.avg_attendance || 0 }}%</p>
            <p class="stat-label">This Month</p>
          </div>
        </div>
      </div>

      <!-- Report Generation Section -->
      <div class="report-generation-section">
        <h2>Generate Reports</h2>
        <div class="generation-options">
          <div class="generation-option">
            <h3>Attendance Report</h3>
            <p>Generate comprehensive attendance report for selected period</p>
            <div class="date-inputs">
              <div class="input-group">
                <label>Start Date:</label>
                <input type="date" v-model="attendanceReportParams.start_date" class="date-input">
              </div>
              <div class="input-group">
                <label>End Date:</label>
                <input type="date" v-model="attendanceReportParams.end_date" class="date-input">
              </div>
            </div>
            <button @click="generateAttendanceReport" class="btn-primary" :disabled="generatingReport">
              {{ generatingReport ? 'Generating...' : 'Generate Report' }}
            </button>
          </div>

          <div class="generation-option">
            <h3>Leave Report</h3>
            <p>Generate leave utilization and approval reports</p>
            <div class="date-inputs">
              <div class="input-group">
                <label>Start Date:</label>
                <input type="date" v-model="leaveReportParams.start_date" class="date-input">
              </div>
              <div class="input-group">
                <label>End Date:</label>
                <input type="date" v-model="leaveReportParams.end_date" class="date-input">
              </div>
            </div>
            <button @click="generateLeaveReport" class="btn-secondary" :disabled="generatingReport">
              {{ generatingReport ? 'Generating...' : 'Generate Report' }}
            </button>
          </div>

          <div class="generation-option">
            <h3>Payroll Report</h3>
            <p>Generate payroll summary and tax reports</p>
            <div class="date-inputs">
              <div class="input-group">
                <label>Start Date:</label>
                <input type="date" v-model="payrollReportParams.start_date" class="date-input">
              </div>
              <div class="input-group">
                <label>End Date:</label>
                <input type="date" v-model="payrollReportParams.end_date" class="date-input">
              </div>
            </div>
            <button @click="generatePayrollReport" class="btn-tertiary" :disabled="generatingReport">
              {{ generatingReport ? 'Generating...' : 'Generate Report' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Generated Reports Section -->
      <div class="generated-reports" v-if="generatedReports.length > 0">
        <h2>Recently Generated Reports</h2>
        <div class="reports-list">
          <div v-for="report in generatedReports" :key="report.id" class="report-item">
            <div class="report-icon">
              {{ getReportIcon(report.type) }}
            </div>
            <div class="report-info">
              <h4>{{ report.title }}</h4>
              <p class="report-period">{{ report.period }}</p>
              <p class="report-date">Generated: {{ formatDate(report.generated_at) }}</p>
            </div>
            <div class="report-actions">
              <button @click="downloadReport(report)" class="btn-download">📥 Download</button>
              <button @click="viewReport(report)" class="btn-view">👁️ View</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Report Preview Section -->
      <div class="report-preview-section" v-if="currentReport">
        <h2>Report Preview: {{ currentReport.title }}</h2>
        <div class="preview-actions">
          <button @click="downloadReport(currentReport)" class="btn-primary">Download PDF</button>
          <button @click="exportToExcel(currentReport)" class="btn-secondary">Export to Excel</button>
          <button @click="currentReport = null" class="btn-tertiary">Close Preview</button>
        </div>
        <div class="preview-content">
          <!-- Dynamic report content based on type -->
          <div v-if="currentReport.type === 'attendance' && currentReport.data">
            <h3>Attendance Summary</h3>
            <div class="report-stats">
              <div class="report-stat">
                <span class="stat-label">Total Employees:</span>
                <span class="stat-value">{{ currentReport.data.total_employees || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Average Attendance:</span>
                <span class="stat-value">{{ currentReport.data.attendance_rate || 0 }}%</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Total Present Days:</span>
                <span class="stat-value">{{ currentReport.data.attendance_summary?.present || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Working Days:</span>
                <span class="stat-value">{{ currentReport.data.working_days || 0 }}</span>
              </div>
            </div>
          </div>
          
          <div v-if="currentReport.type === 'leave' && currentReport.data">
            <h3>Leave Summary</h3>
            <div class="report-stats">
              <div class="report-stat">
                <span class="stat-label">Total Leave Requests:</span>
                <span class="stat-value">{{ currentReport.data.total_leave_requests || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Approved Leaves:</span>
                <span class="stat-value">{{ currentReport.data.status_breakdown?.approved || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Pending Leaves:</span>
                <span class="stat-value">{{ currentReport.data.status_breakdown?.pending || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Approval Rate:</span>
                <span class="stat-value">{{ currentReport.data.approval_rate || 0 }}%</span>
              </div>
            </div>
          </div>
          
          <div v-if="currentReport.type === 'payroll' && currentReport.data">
            <h3>Payroll Summary</h3>
            <div class="report-stats">
              <div class="report-stat">
                <span class="stat-label">Total Payroll:</span>
                <span class="stat-value">${{ currentReport.data.total_net_salary || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Employees Processed:</span>
                <span class="stat-value">{{ currentReport.data.processed_employees || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Average Salary:</span>
                <span class="stat-value">${{ currentReport.data.average_net_salary || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Total Tax:</span>
                <span class="stat-value">${{ currentReport.data.total_tax_amount || 0 }}</span>
              </div>
            </div>
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
  name: 'AdminReports',
  
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  
  data() {
    const today = new Date()
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1)
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0)
    
    return {
      loading: false,
      generatingReport: false,
      error: null,
      allEmployees: [],
      orgStats: {},
      generatedReports: [],
      currentReport: null,
      
      // Report parameters
      attendanceReportParams: {
        start_date: firstDay.toISOString().split('T')[0],
        end_date: lastDay.toISOString().split('T')[0],
        department: 'all',
        report_type: 'summary'
      },
      
      leaveReportParams: {
        start_date: firstDay.toISOString().split('T')[0],
        end_date: lastDay.toISOString().split('T')[0],
        leave_type: 'all',
        status: 'all'
      },
      
      payrollReportParams: {
        start_date: firstDay.toISOString().split('T')[0],
        end_date: lastDay.toISOString().split('T')[0],
        department: 'all',
        status: 'all'
      },
      
      retryCount: 0
    }
  },
  
  mounted() {
    this.initializeComponent()
  },
  
  methods: {
    initializeComponent() {
      if (!this.authStore.isAuthenticated) {
        this.error = 'Please log in to access admin reports.'
        return
      }
      
      if (!this.authStore.isAdmin) {
        this.error = 'You do not have permission to access this page.'
        return
      }
      
      this.fetchAdminData()
      this.loadGeneratedReports()
    },
    
    async fetchAdminData() {
      this.loading = true
      this.error = null
      
      try {
        // Get employees and basic stats
        const employeesRes = await axios.get('/api/admin/employees')
        this.allEmployees = employeesRes.data.data || employeesRes.data || []
        
        // Calculate basic organization stats
        this.calculateOrgStats()
        
      } catch (err) {
        console.error('Fetch error:', err)
        this.handleApiError(err)
      } finally {
        this.loading = false
      }
    },
    
    calculateOrgStats() {
      // Calculate basic organization statistics
      const totalEmployees = this.allEmployees.length
      const today = new Date().toISOString().split('T')[0]
      
      // These would ideally come from API endpoints, but we'll calculate basic ones
      this.orgStats = {
        total_employees: totalEmployees,
        present_today: Math.floor(totalEmployees * 0.85), // Placeholder - should come from API
        pending_leaves: Math.floor(totalEmployees * 0.1), // Placeholder
        avg_attendance: 85 // Placeholder
      }
    },
    
    async generateAttendanceReport() {
      this.generatingReport = true
      try {
        console.log('Generating attendance report with params:', this.attendanceReportParams)
        
        const response = await axios.post('/api/admin/generate/attendance', this.attendanceReportParams)
        console.log('Attendance report response:', response.data)
        
        if (response.data.success) {
          this.generatedReports.unshift({
            id: Date.now(),
            type: 'attendance',
            title: 'Attendance Report',
            period: `${this.attendanceReportParams.start_date} to ${this.attendanceReportParams.end_date}`,
            generated_at: new Date().toISOString(),
            data: response.data.report
          })
          
          this.$notify({
            type: 'success',
            title: 'Success',
            text: 'Attendance report generated successfully!'
          })
        } else {
          throw new Error(response.data.message || 'Failed to generate report')
        }
        
      } catch (err) {
        console.error('Error generating attendance report:', err)
        this.handleApiError(err)
      } finally {
        this.generatingReport = false
      }
    },
    
    async generateLeaveReport() {
      this.generatingReport = true
      try {
        console.log('Generating leave report with params:', this.leaveReportParams)
        
        const response = await axios.post('/api/admin/generate/leave', this.leaveReportParams)
        console.log('Leave report response:', response.data)
        
        if (response.data.success) {
          this.generatedReports.unshift({
            id: Date.now(),
            type: 'leave',
            title: 'Leave Report',
            period: `${this.leaveReportParams.start_date} to ${this.leaveReportParams.end_date}`,
            generated_at: new Date().toISOString(),
            data: response.data.report
          })
          
          this.$notify({
            type: 'success',
            title: 'Success',
            text: 'Leave report generated successfully!'
          })
        } else {
          throw new Error(response.data.message || 'Failed to generate report')
        }
        
      } catch (err) {
        console.error('Error generating leave report:', err)
        this.handleApiError(err)
      } finally {
        this.generatingReport = false
      }
    },
    
    async generatePayrollReport() {
      this.generatingReport = true
      try {
        console.log('Generating payroll report with params:', this.payrollReportParams)
        
        const response = await axios.post('/api/admin/generate/payroll', this.payrollReportParams)
        console.log('Payroll report response:', response.data)
        
        if (response.data.success) {
          this.generatedReports.unshift({
            id: Date.now(),
            type: 'payroll',
            title: 'Payroll Report',
            period: `${this.payrollReportParams.start_date} to ${this.payrollReportParams.end_date}`,
            generated_at: new Date().toISOString(),
            data: response.data.report
          })
          
          this.$notify({
            type: 'success',
            title: 'Success',
            text: 'Payroll report generated successfully!'
          })
        } else {
          throw new Error(response.data.message || 'Failed to generate report')
        }
        
      } catch (err) {
        console.error('Error generating payroll report:', err)
        this.handleApiError(err)
      } finally {
        this.generatingReport = false
      }
    },
    
    async downloadReport(report) {
      try {
        console.log('Downloading report:', report.type)
        
        const params = {
          format: 'pdf',
          start_date: report.data.period_start,
          end_date: report.data.period_end
        }
        
        const response = await axios.post(`/api/admin/download/${report.type}`, params, {
          responseType: 'blob'
        })
        
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `${report.type}_report_${new Date().getTime()}.pdf`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        
        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Report download started!'
        })
        
      } catch (err) {
        console.error('Error downloading report:', err)
        this.handleApiError(err)
      }
    },
    
    async exportToExcel(report) {
      try {
        console.log('Exporting report to Excel:', report.type)
        
        const params = {
          format: 'csv', // Using CSV for Excel compatibility
          start_date: report.data.period_start,
          end_date: report.data.period_end
        }
        
        const response = await axios.post(`/api/admin/download/${report.type}`, params, {
          responseType: 'blob'
        })
        
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `${report.type}_report_${new Date().getTime()}.csv`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        
        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Report exported to Excel!'
        })
        
      } catch (err) {
        console.error('Error exporting to Excel:', err)
        this.handleApiError(err)
      }
    },
    
    viewReport(report) {
      this.currentReport = report
    },
    
    loadGeneratedReports() {
      // Load previously generated reports from localStorage
      const savedReports = localStorage.getItem('generatedReports')
      if (savedReports) {
        this.generatedReports = JSON.parse(savedReports)
      }
    },
    
    saveGeneratedReports() {
      localStorage.setItem('generatedReports', JSON.stringify(this.generatedReports))
    },
    
    getReportIcon(type) {
      const icons = {
        attendance: '📊',
        leave: '📋',
        payroll: '💰'
      }
      return icons[type] || '📄'
    },
    
    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    },
    
    retryFetch() {
      this.retryCount++
      if (this.retryCount <= 3) {
        this.fetchAdminData()
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
        errorMsg = 'Report generation endpoint not found. Please check the API routes.'
      } else if (err.response?.status === 422) {
        errorMsg = 'Invalid report parameters. Please check your inputs.'
      } else if (err.response?.status === 500) {
        errorMsg = 'Server error: Please try again later.'
      } else {
        errorMsg = err.response?.data?.message || err.message || errorMsg
      }
      
      this.error = errorMsg
      
      this.$notify({
        type: 'error',
        title: 'Error',
        text: errorMsg
      })
    }
  },
  
  watch: {
    generatedReports: {
      handler() {
        this.saveGeneratedReports()
      },
      deep: true
    }
  }
}
</script>
<style>
/* --- Global Styles for Modern Look --- */
:root {
  /* Primary Brand Color - A calm, professional blue */
  --color-primary: #3b82f6; /* Tailwind blue-500 */
  /* Secondary Brand Color - A vibrant action color */
  --color-secondary: #10b981; /* Tailwind emerald-500 */
  /* Tertiary Color - A neutral for less critical actions */
  --color-tertiary: #a855f7; /* Tailwind purple-500 */
  /* Backgrounds */
  --color-bg: #f3f4f6; /* Light gray background for the page */
  --color-card-bg: #ffffff;
  /* Text */
  --color-heading: #1f2937; /* Darker text for titles */
  --color-text: #4b5563; /* Standard text */
  /* Borders & Shadows */
  --color-border: #e5e7eb;
  --shadow-card: 0 4px 12px rgba(0, 0, 0, 0.06);
  --shadow-hover: 0 6px 18px rgba(0, 0, 0, 0.1);
}

/* Recommended Body Style (Place this in your global CSS or main layout) */
/* body {
  background-color: var(--color-bg);
  font-family: 'Inter', sans-serif; 
  margin: 0;
  padding: 0;
} */

/* --- Page Layout --- */
.reports-management {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem;
  background-color: var(--color-bg);
  min-height: 100vh;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid var(--color-border);
}

.page-header h1 {
  color: var(--color-heading);
  font-size: 2.25rem;
  font-weight: 700;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

/* --- Buttons --- */
.btn-primary, .btn-secondary, .btn-tertiary, .btn-download, .btn-view {
  padding: 0.75rem 1.25rem;
  border: none;
  border-radius: 8px; /* Slightly larger radius */
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1rem;
}

.btn-primary {
  background: var(--color-primary);
  color: var(--color-card-bg);
}

.btn-secondary {
  background: var(--color-secondary);
  color: var(--color-card-bg);
}

.btn-tertiary {
  background: var(--color-tertiary);
  color: var(--color-card-bg);
}

.btn-primary:hover, .btn-secondary:hover, .btn-tertiary:hover {
  opacity: 0.9;
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.btn-primary:disabled, .btn-secondary:disabled, .btn-tertiary:disabled {
  background-color: var(--color-border);
  color: var(--color-text);
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

/* --- Status/Error Messages --- */
.error-message, .loading {
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  border-radius: 12px;
  text-align: center;
  font-weight: 600;
  font-size: 1.1rem;
}

.error-message {
  background-color: #fef2f2; /* Light red */
  color: #ef4444; /* Red text */
  border: 1px solid #fca5a5;
}

.loading {
  background-color: #eff6ff; /* Light blue */
  color: var(--color-primary);
  border: 1px solid #93c5fd;
}

/* --- Main Dashboard Sections --- */
.reports-dashboard > div:not(.admin-info) {
  /* Apply the primary card style to all main sections */
  background: var(--color-card-bg);
  border-radius: 16px; /* Modern, large radius */
  box-shadow: var(--shadow-card);
  padding: 2rem;
  margin-bottom: 2.5rem;
}

.admin-info h2 {
  color: var(--color-heading);
  font-size: 1.75rem;
  margin-bottom: 0.25rem;
}

.admin-subtitle {
  color: var(--color-text);
  font-size: 1rem;
  margin-bottom: 1.5rem;
}

/* --- Quick Stats Grid --- */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  /* This is a nested card, keep it simpler */
  background: var(--color-card-bg);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  padding: 1.5rem;
  display: flex;
  align-items: center;
  transition: transform 0.2s;
  border-left: 5px solid var(--color-primary); /* Feature line */
}

.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-hover);
}

.stat-icon {
  font-size: 2.5rem;
  margin-right: 1rem;
  /* Use a subtle background for the icon area */
  background-color: #eff6ff;
  padding: 0.5rem;
  border-radius: 8px;
}

.stat-content h3 {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--color-text);
  margin: 0;
  text-transform: uppercase;
}

.stat-value {
  font-size: 1.8rem;
  font-weight: 800;
  color: var(--color-primary);
  margin: 0.25rem 0;
  line-height: 1;
}

.stat-label {
  font-size: 0.8rem;
  color: #9ca3af;
  margin: 0;
}

/* --- Report Generation Section --- */
.report-generation-section h2 {
  color: var(--color-heading);
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
}

.generation-options {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
}

.generation-option {
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 1.5rem;
  text-align: left;
  background-color: #f9fafb; /* Slightly different background */
}

.generation-option h3 {
  margin: 0 0 0.25rem 0;
  color: var(--color-primary);
  font-size: 1.25rem;
}

.generation-option p {
  margin: 0 0 1.5rem 0;
  color: var(--color-text);
  font-size: 0.9rem;
}

.date-inputs {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.input-group {
  flex: 1;
}

.input-group label {
  color: var(--color-heading);
  font-size: 0.9rem;
}

.date-input {
  padding: 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  font-size: 1rem;
  width: 100%;
  box-sizing: border-box;
}

/* --- Generated Reports Section --- */
.generated-reports h2 {
  color: var(--color-heading);
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
}

.reports-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.report-item {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1rem 1.5rem;
  border: 1px solid var(--color-border);
  border-radius: 10px;
  transition: all 0.2s;
  background-color: var(--color-card-bg);
}

.report-item:hover {
  border-color: var(--color-primary);
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
}

.report-icon {
  font-size: 2.5rem;
  line-height: 1;
}

.report-info {
  flex: 1;
}

.report-info h4 {
  margin: 0 0 0.25rem 0;
  color: var(--color-heading);
  font-size: 1.1rem;
  font-weight: 600;
}

.report-period {
  margin: 0 0 0.25rem 0;
  color: var(--color-primary);
  font-weight: 700;
  font-size: 0.9rem;
}

.report-date {
  margin: 0;
  color: var(--color-text);
  font-size: 0.85rem;
}

.report-actions {
  display: flex;
  gap: 0.75rem;
}

/* Specific button styles for reports list */
.btn-download {
  background: var(--color-secondary); /* Green/Teal */
  color: white;
  padding: 0.6rem 1rem;
  font-size: 0.9rem;
}

.btn-view {
  background: var(--color-primary); /* Blue */
  color: white;
  padding: 0.6rem 1rem;
  font-size: 0.9rem;
}

/* --- Report Preview Section --- */
.report-preview-section h2 {
  color: var(--color-heading);
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
}

.preview-actions {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.preview-content h3 {
  color: var(--color-primary);
  font-size: 1.2rem;
  margin-bottom: 1rem;
  border-bottom: 2px solid var(--color-border);
  padding-bottom: 0.5rem;
}

.report-stats {
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
}

.report-stat {
  padding: 1.25rem;
  background: #f0f4ff; /* Lighter blue/gray for stats */
  border-radius: 10px;
  border-left: 4px solid var(--color-primary);
}

.stat-label {
  color: var(--color-text);
}

.stat-value {
  color: var(--color-heading);
  font-size: 1.2rem;
}

/* Responsive adjustments */
@media (max-width: 1024px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .header-actions {
    margin-top: 1rem;
    flex-wrap: wrap;
  }
}

@media (max-width: 768px) {
  .reports-management {
    padding: 1rem;
  }

  .generation-options {
    grid-template-columns: 1fr;
  }
  
  .report-item {
    flex-direction: column;
    align-items: flex-start;
    text-align: left;
    padding: 1.5rem;
  }
  
  .report-info {
    width: 100%;
    margin-bottom: 0.5rem;
  }
  
  .report-actions {
    justify-content: flex-start;
    width: 100%;
  }
  
  .preview-actions {
    flex-direction: column;
  }

  .report-stats {
    grid-template-columns: 1fr;
  }

  .date-inputs {
    flex-direction: column;
  }
}
</style>