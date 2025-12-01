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

    <!-- Business Filter Section (Admin Only) -->
    <div v-if="authStore.isAdmin" class="business-filter-section">
      <div class="business-filter-header">
        <h2>Business Filter</h2>
        <p class="filter-subtitle">Filter reports by business and country</p>
      </div>
      
      <div class="business-filter-controls">
        <div class="filter-group">
          <label class="filter-label">Business:</label>
          <select
            v-model="selectedBusinessId"
            @change="onBusinessFilterChange"
            class="business-select"
          >
            <option value="">All Businesses</option>
            <option
              v-for="business in businesses"
              :key="business.id"
              :value="business.id"
            >
              {{ business.name }}
            </option>
          </select>
          <span v-if="selectedBusinessId" class="active-filter-badge">
            {{ getBusinessName(selectedBusinessId) }}
          </span>
        </div>
        
        <div class="filter-group">
          <label class="filter-label">Country:</label>
          <select
            v-model="selectedCountry"
            @change="onCountryFilterChange"
            class="country-select"
          >
            <option value="">All Countries</option>
            <option value="ZM">Zambia</option>
            <option value="KE">Kenya</option>
            <option value="TZ">Tanzania</option>
            <option value="UG">Uganda</option>
            <option value="RW">Rwanda</option>
            <!-- Add more countries as needed -->
          </select>
          <span v-if="selectedCountry" class="active-filter-badge">
            {{ getCountryName(selectedCountry) }}
          </span>
        </div>
        
        <button @click="clearBusinessFilters" class="btn-clear-filters">
          Clear Filters
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
        <div v-if="selectedBusinessId || selectedCountry" class="active-filters-info">
          <span v-if="selectedBusinessId" class="filter-tag">
            Business: {{ getBusinessName(selectedBusinessId) }}
          </span>
          <span v-if="selectedCountry" class="filter-tag">
            Country: {{ getCountryName(selectedCountry) }}
          </span>
        </div>
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
            <p class="stat-label">Selected Period</p>
          </div>
        </div>
      </div>

      <!-- Report Generation Section -->
      <div class="report-generation-section">
        <h2>Generate Reports</h2>
        <div class="generation-options">
          <!-- Attendance Report -->
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
            <div class="filter-group">
              <label>Department:</label>
              <select v-model="attendanceReportParams.department" class="filter-select">
                <option value="">All Departments</option>
                <option v-for="dept in departments" :key="dept" :value="dept">{{ dept }}</option>
              </select>
            </div>
            <div class="report-type">
              <label>Report Type:</label>
              <select v-model="attendanceReportParams.report_type" class="filter-select">
                <option value="summary">Summary</option>
                <option value="detailed">Detailed</option>
                <option value="daily">Daily</option>
              </select>
            </div>
            <button @click="generateAttendanceReport" class="btn-primary" :disabled="generatingReport">
              {{ generatingReport ? 'Generating...' : 'Generate Report' }}
            </button>
          </div>

          <!-- Leave Report -->
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
            <div class="filter-group">
              <label>Leave Type:</label>
              <select v-model="leaveReportParams.leave_type" class="filter-select">
                <option value="">All Types</option>
                <option value="vacation">Vacation</option>
                <option value="sick">Sick Leave</option>
                <option value="personal">Personal</option>
                <option value="maternity">Maternity</option>
                <option value="paternity">Paternity</option>
              </select>
            </div>
            <div class="filter-group">
              <label>Status:</label>
              <select v-model="leaveReportParams.status" class="filter-select">
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
              </select>
            </div>
            <button @click="generateLeaveReport" class="btn-secondary" :disabled="generatingReport">
              {{ generatingReport ? 'Generating...' : 'Generate Report' }}
            </button>
          </div>

          <!-- Payroll Report -->
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
            <div class="filter-group">
              <label>Department:</label>
              <select v-model="payrollReportParams.department" class="filter-select">
                <option value="">All Departments</option>
                <option v-for="dept in departments" :key="dept" :value="dept">{{ dept }}</option>
              </select>
            </div>
            <div class="filter-group">
              <label>Status:</label>
              <select v-model="payrollReportParams.status" class="filter-select">
                <option value="all">All Status</option>
                <option value="paid">Paid</option>
                <option value="pending">Pending</option>
              </select>
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
              <div v-if="report.filters" class="report-filters">
                <span v-if="report.filters.business" class="filter-badge">
                  Business: {{ report.filters.business }}
                </span>
                <span v-if="report.filters.country" class="filter-badge">
                  Country: {{ report.filters.country }}
                </span>
                <span v-if="report.filters.department" class="filter-badge">
                  Department: {{ report.filters.department }}
                </span>
              </div>
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
        <div class="preview-header">
          <h2>Report Preview: {{ currentReport.title }}</h2>
          <div v-if="currentReport.data.filters" class="preview-filters">
            <span v-if="currentReport.data.filters.business" class="filter-tag">
              Business: {{ currentReport.data.filters.business }}
            </span>
            <span v-if="currentReport.data.filters.country" class="filter-tag">
              Country: {{ currentReport.data.filters.country }}
            </span>
            <span v-if="currentReport.data.filters.department" class="filter-tag">
              Department: {{ currentReport.data.filters.department }}
            </span>
          </div>
        </div>
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
            <!-- Attendance Details Table -->
            <h4 style="margin-top: 2rem; color: var(--color-primary);">Attendance Details</h4>
            <div class="attendance-details-table" v-if="currentReport.data.attendance_details">
              <table>
                <thead>
                  <tr>
                    <th>Employee Name</th>
                    <th>Business</th>
                    <th>Country</th>
                    <th>Department</th>
                    <th>Date</th>
                    <th>Clock In</th>
                    <th>Clock Out</th>
                    <th>Total Hours</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="detail in currentReport.data.attendance_details.slice(0, 10)" :key="detail.id">
                    <td>{{ detail.employee_name }}</td>
                    <td>{{ detail.business || 'No Business' }}</td>
                    <td>{{ detail.country || 'N/A' }}</td>
                    <td>{{ detail.department || 'N/A' }}</td>
                    <td>{{ detail.date }}</td>
                    <td>{{ detail.clock_in || 'N/A' }}</td>
                    <td>{{ detail.clock_out || 'N/A' }}</td>
                    <td>{{ detail.total_hours || '0.00' }}</td>
                    <td>
                      <span :class="['status-badge', detail.status?.toLowerCase()]">
                        {{ detail.status || 'N/A' }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
              <div v-if="currentReport.data.attendance_details.length > 10" class="more-records">
                <p>... and {{ currentReport.data.attendance_details.length - 10 }} more records</p>
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
            <!-- Leave Details Table -->
            <h4 style="margin-top: 2rem; color: var(--color-primary);">Leave Details</h4>
            <div class="leave-details-table" v-if="currentReport.data.leave_details">
              <table>
                <thead>
                  <tr>
                    <th>Employee Name</th>
                    <th>Business</th>
                    <th>Country</th>
                    <th>Leave Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Total Days</th>
                    <th>Status</th>
                    <th>Reason</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="detail in currentReport.data.leave_details.slice(0, 10)" :key="detail.id">
                    <td>{{ detail.employee_name }}</td>
                    <td>{{ detail.business || 'No Business' }}</td>
                    <td>{{ detail.country || 'N/A' }}</td>
                    <td>{{ detail.leave_type || 'N/A' }}</td>
                    <td>{{ detail.start_date }}</td>
                    <td>{{ detail.end_date }}</td>
                    <td>{{ detail.total_days || '0' }}</td>
                    <td>
                      <span :class="['status-badge', detail.status?.toLowerCase()]">
                        {{ detail.status || 'N/A' }}
                      </span>
                    </td>
                    <td>{{ detail.reason || 'N/A' }}</td>
                  </tr>
                </tbody>
              </table>
              <div v-if="currentReport.data.leave_details.length > 10" class="more-records">
                <p>... and {{ currentReport.data.leave_details.length - 10 }} more records</p>
              </div>
            </div>
          </div>
         
          <div v-if="currentReport.type === 'payroll' && currentReport.data">
            <h3>Payroll Summary</h3>
            <div class="report-stats">
              <div class="report-stat">
                <span class="stat-label">Total Payroll:</span>
                <span class="stat-value">K{{ currentReport.data.total_net_salary || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Employees Processed:</span>
                <span class="stat-value">{{ currentReport.data.processed_employees || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Average Salary:</span>
                <span class="stat-value">K{{ currentReport.data.average_net_salary || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Total Tax:</span>
                <span class="stat-value">K{{ currentReport.data.total_tax_amount || 0 }}</span>
              </div>
            </div>
            <!-- Payroll Details Table -->
            <h4 style="margin-top: 2rem; color: var(--color-primary);">Payslip Details</h4>
            <div class="payroll-details-table">
              <table>
                <thead>
                  <tr>
                    <th>Employee Name</th>
                    <th>Business</th>
                    <th>Country</th>
                    <th>Department</th>
                    <th>Gross Salary</th>
                    <th>Deductions</th>
                    <th>Net Salary</th>
                    <th>Tax Amount</th>
                    <th>Pay Period</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="detail in currentReport.data.payslip_details.slice(0, 10)" :key="detail.employee_id">
                    <td>{{ detail.employee_name }}</td>
                    <td>{{ detail.business || 'No Business' }}</td>
                    <td>{{ detail.country || 'N/A' }}</td>
                    <td>{{ detail.department || 'N/A' }}</td>
                    <td>K{{ detail.gross_salary }}</td>
                    <td>K{{ detail.deductions }}</td>
                    <td>K{{ detail.net_salary }}</td>
                    <td>K{{ detail.tax_amount }}</td>
                    <td>{{ detail.pay_period }}</td>
                  </tr>
                </tbody>
              </table>
              <div v-if="currentReport.data.payslip_details.length > 10" class="more-records">
                <p>... and {{ currentReport.data.payslip_details.length - 10 }} more records</p>
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
    return {
      loading: false,
      generatingReport: false,
      error: null,
      allEmployees: [],
      orgStats: {},
      generatedReports: [],
      currentReport: null,
      departments: [],
      
      // Business filter data
      selectedBusinessId: '',
      selectedCountry: '',
      businesses: [],
     
      // Dashboard date filter
      dashboardStartDate: '',
      dashboardEndDate: '',
      currentPeriod: 'This Month',
     
      // Report parameters with default values
      attendanceReportParams: {
        start_date: new Date().toISOString().split('T')[0],
        end_date: new Date().toISOString().split('T')[0],
        department: '',
        business_id: '',
        country: '',
        report_type: 'summary'
      },
     
      leaveReportParams: {
        start_date: new Date().toISOString().split('T')[0],
        end_date: new Date().toISOString().split('T')[0],
        leave_type: '',
        status: 'all',
        business_id: '',
        country: ''
      },
     
      payrollReportParams: {
        start_date: new Date().toISOString().split('T')[0],
        end_date: new Date().toISOString().split('T')[0],
        department: '',
        status: 'all',
        business_id: '',
        country: ''
      },
     
      retryCount: 0
    }
  },
 
  async mounted() {
    await this.initializeComponent()
  },
 
  methods: {
    async initializeComponent() {
      if (!this.authStore.isAuthenticated) {
        this.error = 'Please log in to access admin reports.'
        return
      }
     
      if (!this.authStore.isAdmin) {
        this.error = 'You do not have permission to access this page.'
        return
      }
     
      // Fetch businesses for admin
      if (this.authStore.isAdmin) {
        await this.fetchBusinesses()
      }
      
      await this.fetchAdminData()
      this.loadGeneratedReports()
    },
    
    // Fetch businesses method
    async fetchBusinesses() {
      try {
        const response = await axios.get('/api/admin/businesses')
        this.businesses = response.data.data || []
        console.log('Businesses fetched:', this.businesses)
      } catch (error) {
        console.error('Failed to fetch businesses:', error)
        this.$notify({
          type: 'error',
          title: 'Error',
          text: 'Failed to load businesses'
        })
      }
    },
    
    getBusinessName(businessId) {
      const business = this.businesses.find(b => b.id === businessId)
      return business ? business.name : 'Unknown Business'
    },
    
    getCountryName(countryCode) {
      const countries = {
        'ZM': 'Zambia',
        'KE': 'Kenya',
        'TZ': 'Tanzania',
        'UG': 'Uganda',
        'RW': 'Rwanda'
      }
      return countries[countryCode] || countryCode
    },
    
    onBusinessFilterChange() {
      // Update all report parameters with selected business
      this.attendanceReportParams.business_id = this.selectedBusinessId
      this.leaveReportParams.business_id = this.selectedBusinessId
      this.payrollReportParams.business_id = this.selectedBusinessId
      
      // Refresh data with new filters
      this.fetchAdminData()
    },
    
    onCountryFilterChange() {
      // Update all report parameters with selected country
      this.attendanceReportParams.country = this.selectedCountry
      this.leaveReportParams.country = this.selectedCountry
      this.payrollReportParams.country = this.selectedCountry
      
      // Refresh data with new filters
      this.fetchAdminData()
    },
    
    clearBusinessFilters() {
      this.selectedBusinessId = ''
      this.selectedCountry = ''
      
      // Clear business and country filters from all report parameters
      this.attendanceReportParams.business_id = ''
      this.attendanceReportParams.country = ''
      
      this.leaveReportParams.business_id = ''
      this.leaveReportParams.country = ''
      
      this.payrollReportParams.business_id = ''
      this.payrollReportParams.country = ''
      
      // Refresh data without filters
      this.fetchAdminData()
    },
    
    async fetchAdminData() {
      this.loading = true
      this.error = null
     
      try {
        // Set default dashboard dates to current month
        const today = new Date()
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0]
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0]
       
        this.dashboardStartDate = firstDay
        this.dashboardEndDate = lastDay
        this.currentPeriod = 'This Month'

        // Fetch employees with business and country filters
        const params = {
          start_date: this.dashboardStartDate,
          end_date: this.dashboardEndDate
        }
        
        // Add business filter if selected
        if (this.selectedBusinessId) {
          params.business_id = this.selectedBusinessId
        }
        
        // Add country filter if selected
        if (this.selectedCountry) {
          params.country = this.selectedCountry
        }

        const employeesRes = await axios.get('/api/admin/employees', { params })
        this.allEmployees = employeesRes.data.data || employeesRes.data || []
        
        // Extract unique departments from filtered employees
        this.departments = [...new Set(this.allEmployees
          .map(emp => emp.department)
          .filter(dept => dept && dept.trim() !== '')
        )].sort()

        // Fetch filtered stats with business/country filters
        await this.fetchFilteredStats(params)
       
      } catch (err) {
        console.error('Fetch error:', err)
        this.handleApiError(err)
      } finally {
        this.loading = false
      }
    },
   
    async fetchFilteredStats(params = {}) {
      this.loading = true
      this.error = null
     
      try {
        // Add business and country filters to stats request
        const statsParams = {
          start_date: this.dashboardStartDate,
          end_date: this.dashboardEndDate,
          ...params
        }
       
        const statsRes = await axios.get('/api/admin/reports/stats', { params: statsParams })
        this.orgStats = statsRes.data || {}
       
        // Sync report parameters with dashboard dates and current filters
        this.attendanceReportParams.start_date = this.dashboardStartDate
        this.attendanceReportParams.end_date = this.dashboardEndDate
        this.attendanceReportParams.business_id = this.selectedBusinessId
        this.attendanceReportParams.country = this.selectedCountry
       
        this.leaveReportParams.start_date = this.dashboardStartDate
        this.leaveReportParams.end_date = this.dashboardEndDate
        this.leaveReportParams.business_id = this.selectedBusinessId
        this.leaveReportParams.country = this.selectedCountry
       
        this.payrollReportParams.start_date = this.dashboardStartDate
        this.payrollReportParams.end_date = this.dashboardEndDate
        this.payrollReportParams.business_id = this.selectedBusinessId
        this.payrollReportParams.country = this.selectedCountry
       
        // Update current period display
        this.updateCurrentPeriodDisplay()
       
      } catch (err) {
        console.error('Fetch error:', err)
        this.handleApiError(err)
      } finally {
        this.loading = false
      }
    },

    updateCurrentPeriodDisplay() {
      const start = new Date(this.dashboardStartDate)
      const end = new Date(this.dashboardEndDate)
      const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
      const startMonth = monthNames[start.getMonth()]
      const endMonth = monthNames[end.getMonth()]
      
      if (start.getFullYear() === end.getFullYear() && startMonth === endMonth) {
        this.currentPeriod = `${startMonth} ${start.getFullYear()}`
      } else {
        this.currentPeriod = `${startMonth} ${start.getDate()} - ${endMonth} ${end.getDate()}, ${end.getFullYear()}`
      }
    },
   
    onDateChange() {
      // Ensure end date is not before start date
      if (this.dashboardEndDate < this.dashboardStartDate) {
        this.dashboardEndDate = this.dashboardStartDate
      }
    },
   
    resetDateFilter() {
      const today = new Date()
      const firstDay = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0]
      const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0]
      this.dashboardStartDate = firstDay
      this.dashboardEndDate = lastDay
      this.fetchFilteredStats()
    },
   
    async generateAttendanceReport() {
      this.generatingReport = true
      try {
        
        // Create params object with business/country filters
        const params = {
          start_date: this.attendanceReportParams.start_date,
          end_date: this.attendanceReportParams.end_date,
          report_type: this.attendanceReportParams.report_type || 'summary'
        }
        
        // Add department filter if selected
        if (this.attendanceReportParams.department && this.attendanceReportParams.department.trim() !== '') {
          params.department = this.attendanceReportParams.department
        }
        
        // Add business filter if selected
        if (this.attendanceReportParams.business_id && this.attendanceReportParams.business_id.trim() !== '') {
          params.business_id = this.attendanceReportParams.business_id
        }
        
        // Add country filter if selected
        if (this.attendanceReportParams.country && this.attendanceReportParams.country.trim() !== '') {
          params.country = this.attendanceReportParams.country
        }

        // Ensure dates are in proper format for backend
        if (params.start_date) {
          params.start_date = this.ensureDateFormat(params.start_date)
        }
        if (params.end_date) {
          params.end_date = this.ensureDateFormat(params.end_date)
        }

        const response = await axios.post('/api/admin/reports/generate/attendance', params)
        
        if (response.data.success) {
          this.currentReport = {
            type: 'attendance',
            title: 'Attendance Report',
            data: response.data.data,
            period: response.data.data.period,
            // Store the original parameters for download
            originalParams: { ...params }
          }
          
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
       
        // Ensure parameters are properly set with business/country filters
        const params = {
          ...this.leaveReportParams,
          leave_type: this.leaveReportParams.leave_type || '',
          status: this.leaveReportParams.status || 'all'
        }
       
        // Ensure dates are in proper format for backend
        if (params.start_date) {
          params.start_date = this.ensureDateFormat(params.start_date)
        }
        if (params.end_date) {
          params.end_date = this.ensureDateFormat(params.end_date)
        }
       
        const response = await axios.post('/api/admin/reports/generate/leave', params)
        
       
        if (response.data.success) {
          this.currentReport = {
            type: 'leave',
            title: 'Leave Report',
            data: response.data.data,
            period: response.data.data.period,
            // Store the original parameters for download
            originalParams: { ...params }
          }
         
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
        
        // Create params object with business/country filters
        const params = {
          start_date: this.payrollReportParams.start_date,
          end_date: this.payrollReportParams.end_date,
          status: this.payrollReportParams.status || 'all'
        }
        
        // Add department filter if selected
        if (this.payrollReportParams.department && this.payrollReportParams.department.trim() !== '') {
          params.department = this.payrollReportParams.department
        }
        
        // Add business filter if selected
        if (this.payrollReportParams.business_id && this.payrollReportParams.business_id.trim() !== '') {
          params.business_id = this.payrollReportParams.business_id
        }
        
        // Add country filter if selected
        if (this.payrollReportParams.country && this.payrollReportParams.country.trim() !== '') {
          params.country = this.payrollReportParams.country
        }

        // Ensure dates are in proper format for backend
        if (params.start_date) {
          params.start_date = this.ensureDateFormat(params.start_date)
        }
        if (params.end_date) {
          params.end_date = this.ensureDateFormat(params.end_date)
        }

        const response = await axios.post('/api/admin/reports/generate/payroll', params)
        
        if (response.data.success) {
          this.currentReport = {
            type: 'payroll',
            title: 'Payroll Report',
            data: response.data.data,
            period: response.data.data.period,
            // Store the properly formatted parameters for download
            originalParams: { ...params }
          }
          
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
       
        
        // Convert dates to Y-m-d format for backend
        let startDate, endDate
        
        if (report.period && report.period.includes(' to ')) {
          const [startStr, endStr] = report.period.split(' to ')
          startDate = this.formatDateForBackend(startStr)
          endDate = this.formatDateForBackend(endStr)
          
        } else {
          // Fallback to current report parameters with proper formatting
          switch (report.type) {
            case 'attendance':
              startDate = this.attendanceReportParams.start_date
              endDate = this.attendanceReportParams.end_date
              break
            case 'leave':
              startDate = this.leaveReportParams.start_date
              endDate = this.leaveReportParams.end_date
              break
            case 'payroll':
              startDate = this.payrollReportParams.start_date
              endDate = this.payrollReportParams.end_date
              break
            default:
              const today = new Date().toISOString().split('T')[0]
              startDate = today
              endDate = today
          }
          console.log('Dates from current params:', { startDate, endDate })
        }

        const params = {
          format: 'pdf',
          start_date: startDate,
          end_date: endDate,
          ...this.getAdditionalDownloadParams(report)
        }

       

        const response = await axios.post(`/api/admin/reports/download/${report.type}`, params, {
          responseType: 'blob',
          headers: {
            'Content-Type': 'application/json',
          }
        })

       

        if (response.data.size === 0) {
          console.error('Downloaded file is empty!')
          throw new Error('Downloaded file is empty')
        }

        const blob = new Blob([response.data], { type: 'application/pdf' })
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `${report.type}_report_${new Date().getTime()}.pdf`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)

        
        
        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Report download started!'
        })

      } catch (err) {
        console.error('=== DOWNLOAD ERROR ===')
        console.error('Error details:', err)
        console.error('Error response:', err.response)
        if (err.response) {
          console.error('Error status:', err.response.status)
          console.error('Error data:', err.response.data)
        }
        console.error('=====================')
        
        this.handleApiError(err)
      }
    },
   
    async exportToExcel(report) {
      try {
        
        // Convert dates to Y-m-d format for backend
        let startDate, endDate
        
        if (report.period && report.period.includes(' to ')) {
          const [startStr, endStr] = report.period.split(' to ')
          startDate = this.formatDateForBackend(startStr)
          endDate = this.formatDateForBackend(endStr)
          console.log('Dates converted for backend:', { startDate, endDate })
        } else {
          // Fallback to current report parameters with proper formatting
          switch (report.type) {
            case 'attendance':
              startDate = this.attendanceReportParams.start_date
              endDate = this.attendanceReportParams.end_date
              break
            case 'leave':
              startDate = this.leaveReportParams.start_date
              endDate = this.leaveReportParams.end_date
              break
            case 'payroll':
              startDate = this.payrollReportParams.start_date
              endDate = this.payrollReportParams.end_date
              break
            default:
              const today = new Date().toISOString().split('T')[0]
              startDate = today
              endDate = today
          }
        }

        const params = {
          format: 'csv',
          start_date: startDate,
          end_date: endDate,
          ...this.getAdditionalDownloadParams(report)
        }

       

        const response = await axios.post(`/api/admin/reports/download/${report.type}`, params, {
          responseType: 'blob',
          headers: {
            'Content-Type': 'application/json',
          }
        })

       

        if (response.data.size === 0) {
          console.error('Exported file is empty!')
          throw new Error('Exported file is empty')
        }

        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `${report.type}_report_${new Date().getTime()}.csv`)
        document.body.appendChild(link)
        link.click()
        link.remove()

        console.log('Export completed successfully')
        
        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Report exported to CSV!'
        })

      } catch (err) {
        console.error('=== EXPORT ERROR ===')
        console.error('Error details:', err)
        console.error('Error response:', err.response)
        console.error('===================')
        
        this.handleApiError(err)
      }
    },

    // Add this helper method to convert date formats
    formatDateForBackend(dateString) {
      // Convert from "Oct 31, 2025" to "2025-10-31"
      const date = new Date(dateString)
      if (isNaN(date.getTime())) {
        console.warn('Invalid date string:', dateString)
        return new Date().toISOString().split('T')[0] // Fallback to today
      }
      
      const year = date.getFullYear()
      const month = String(date.getMonth() + 1).padStart(2, '0')
      const day = String(date.getDate()).padStart(2, '0')
      
      return `${year}-${month}-${day}`
    },

    // Add this helper method to ensure date format
    ensureDateFormat(dateString) {
      // If it's already in Y-m-d format, return as is
      if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
        return dateString
      }
      
      // Otherwise convert it
      return this.formatDateForBackend(dateString)
    },
   
    // Add this helper method to get additional parameters
    getAdditionalDownloadParams(report) {
      const additionalParams = {}
      
      switch (report.type) {
        case 'attendance':
          if (this.attendanceReportParams.department && this.attendanceReportParams.department.trim() !== '') {
            additionalParams.department = this.attendanceReportParams.department
          }
          if (this.attendanceReportParams.business_id && this.attendanceReportParams.business_id.trim() !== '') {
            additionalParams.business_id = this.attendanceReportParams.business_id
          }
          if (this.attendanceReportParams.country && this.attendanceReportParams.country.trim() !== '') {
            additionalParams.country = this.attendanceReportParams.country
          }
          additionalParams.report_type = this.attendanceReportParams.report_type || 'summary'
          break
          
        case 'leave':
          if (this.leaveReportParams.leave_type) {
            additionalParams.leave_type = this.leaveReportParams.leave_type
          }
          if (this.leaveReportParams.status && this.leaveReportParams.status !== 'all') {
            additionalParams.status = this.leaveReportParams.status
          }
          if (this.leaveReportParams.business_id && this.leaveReportParams.business_id.trim() !== '') {
            additionalParams.business_id = this.leaveReportParams.business_id
          }
          if (this.leaveReportParams.country && this.leaveReportParams.country.trim() !== '') {
            additionalParams.country = this.leaveReportParams.country
          }
          break
          
        case 'payroll':
          if (this.payrollReportParams.department && this.payrollReportParams.department.trim() !== '') {
            additionalParams.department = this.payrollReportParams.department
          }
          if (this.payrollReportParams.status && this.payrollReportParams.status !== 'all') {
            additionalParams.status = this.payrollReportParams.status
          }
          if (this.payrollReportParams.business_id && this.payrollReportParams.business_id.trim() !== '') {
            additionalParams.business_id = this.payrollReportParams.business_id
          }
          if (this.payrollReportParams.country && this.payrollReportParams.country.trim() !== '') {
            additionalParams.country = this.payrollReportParams.country
          }
          break
      }
      
      console.log('Additional parameters for download:', additionalParams)
      return additionalParams
    },
   
    viewReport(report) {
      this.currentReport = report
    },
   
    async loadGeneratedReports() {
      // Load generated reports from backend
      try {
        const response = await axios.get('/api/admin/reports/generated-reports')
        this.generatedReports = response.data || []
      } catch (err) {
        console.error('Error loading generated reports:', err)
        // Don't show error for this, it's not critical
      }
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
        if (err.response.data.errors) {
          const errors = Object.values(err.response.data.errors).flat()
          errorMsg += ' Details: ' + errors.join(', ')
        }
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
  }
}
</script>

<style>
/* CSS Variables */
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

/* Business Filter Section Styles */
.business-filter-section {
  background: white;
  padding: 1.5rem;
  border-radius: 16px;
  margin-bottom: 1.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.business-filter-header h2 {
  color: var(--color-heading);
  font-size: 1.5rem;
  margin-bottom: 0.25rem;
}

.filter-subtitle {
  color: var(--color-text);
  font-size: 0.9rem;
  margin-bottom: 1.5rem;
}

.business-filter-controls {
  display: flex;
  gap: 1.5rem;
  align-items: flex-end;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  min-width: 200px;
}

.filter-label {
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
  font-size: 0.9rem;
}

.business-select, .country-select {
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: border-color 0.3s;
  background: white;
}

.business-select:focus, .country-select:focus {
  outline: none;
  border-color: #667eea;
}

.active-filter-badge {
  margin-left: 0.5rem;
  padding: 0.25rem 0.75rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
  display: inline-block;
  margin-top: 0.5rem;
}

.btn-clear-filters {
  padding: 0.75rem 1.5rem;
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
  height: fit-content;
}

.btn-clear-filters:hover {
  background: #e5e7eb;
}

.active-filters-info {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.5rem;
  flex-wrap: wrap;
}

.filter-tag {
  padding: 0.25rem 0.75rem;
  background: #eef2ff;
  color: #667eea;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 500;
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
  margin-bottom: 1rem;
}

.input-group {
  flex: 1;
}

.input-group label {
  color: var(--color-heading);
  font-size: 0.9rem;
  display: block;
  margin-bottom: 0.25rem;
}

.date-input {
  padding: 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  font-size: 1rem;
  width: 100%;
  box-sizing: border-box;
}

.date-input option {
  padding: 0.5rem;
}

.filter-group, .report-type {
  margin-bottom: 1rem;
}

.filter-group label, .report-type label {
  display: block;
  color: var(--color-heading);
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.filter-select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  font-size: 1rem;
  background-color: white;
  box-sizing: border-box;
}

.filter-select:focus {
  outline: none;
  border-color: var(--color-primary);
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

.report-filters {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.5rem;
  flex-wrap: wrap;
}

.filter-badge {
  padding: 0.125rem 0.5rem;
  background: #f5f3ff;
  color: #8b5cf6;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.report-actions {
  display: flex;
  gap: 0.75rem;
}

/* --- Report Preview Section --- */
.report-preview-section h2 {
  color: var(--color-heading);
  font-size: 1.5rem;
  margin-bottom: 1.5rem;
}

.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.preview-filters {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
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

.preview-content h4 {
  color: var(--color-heading);
  font-size: 1.1rem;
  margin-bottom: 1rem;
}

.report-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.report-stat {
  padding: 1.25rem;
  background: #f0f4ff; /* Lighter blue/gray for stats */
  border-radius: 10px;
  border-left: 4px solid var(--color-primary);
  display: flex;
  justify-content: space-between;
}

.report-stat .stat-label {
  color: var(--color-text);
  font-weight: 500;
}

.report-stat .stat-value {
  color: var(--color-heading);
  font-size: 1.2rem;
  font-weight: 600;
}

/* Table Styles */
.attendance-details-table,
.leave-details-table,
.payroll-details-table {
  overflow-x: auto;
  margin-top: 1rem;
  margin-bottom: 2rem;
}

.attendance-details-table table,
.leave-details-table table,
.payroll-details-table table {
  width: 100%;
  border-collapse: collapse;
  background: var(--color-card-bg);
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.attendance-details-table th,
.attendance-details-table td,
.leave-details-table th,
.leave-details-table td,
.payroll-details-table th,
.payroll-details-table td {
  padding: 0.75rem 1rem;
  text-align: left;
  border-bottom: 1px solid var(--color-border);
}

.attendance-details-table th,
.leave-details-table th,
.payroll-details-table th {
  background: #f9fafb;
  font-weight: 600;
  color: var(--color-heading);
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.attendance-details-table tr:hover,
.leave-details-table tr:hover,
.payroll-details-table tr:hover {
  background: #f0f4ff;
}

.attendance-details-table tr:last-child td,
.leave-details-table tr:last-child td,
.payroll-details-table tr:last-child td {
  border-bottom: none;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
}

.status-badge.present {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.absent {
  background: #fee2e2;
  color: #991b1b;
}

.status-badge.late {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.approved {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.pending {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.rejected {
  background: #fee2e2;
  color: #991b1b;
}

.more-records {
  text-align: center;
  padding: 1rem;
  color: var(--color-text);
  font-style: italic;
  font-size: 0.9rem;
}

.spinner {
  border: 4px solid #f3f3f3;
  border-top: 4px solid var(--color-primary);
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
  
  .business-filter-controls {
    flex-direction: column;
    align-items: stretch;
    gap: 1rem;
  }
  
  .filter-group {
    width: 100%;
  }
  
  .business-select, .country-select {
    width: 100%;
  }
  
  .btn-clear-filters {
    width: 100%;
  }
  
  .active-filter-badge {
    margin-left: 0;
    margin-top: 0.5rem;
    display: inline-block;
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
  
  .preview-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .report-stats {
    grid-template-columns: 1fr;
  }
  
  .date-inputs {
    flex-direction: column;
  }
  
  .attendance-details-table,
  .leave-details-table,
  .payroll-details-table {
    font-size: 0.9rem;
  }
  
  .attendance-details-table th,
  .attendance-details-table td,
  .leave-details-table th,
  .leave-details-table td,
  .payroll-details-table th,
  .payroll-details-table td {
    padding: 0.5rem;
  }
  
  .filter-group, .report-type {
    margin-bottom: 1rem;
  }
  
  .date-inputs {
    flex-direction: column;
    gap: 0.5rem;
  }
  
  .date-inputs .input-group {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .page-header h1 {
    font-size: 1.75rem;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .generation-option {
    padding: 1rem;
  }
  
  .header-actions {
    flex-direction: column;
  }
  
  .header-actions button {
    width: 100%;
    justify-content: center;
  }
}
</style>