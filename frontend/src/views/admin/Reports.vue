<template>
  <div class="reports-management">
  
    <!-- Business Filter Section (Admin Only) -->
    <div v-if="authStore.isAdmin" class="business-filter-section">
      <div class="business-filter-header">
        <h2>Business Filter</h2>
        <p class="filter-subtitle">Filter reports by business and country</p>
      </div>
     
      <div class="business-filter-controls">
        <div class="filter-group">
          <label class="filter-label">Business:</label>
          <select v-model="selectedBusinessId" @change="onBusinessFilterChange" class="business-select">
            <option value="">All Businesses</option>
            <option v-for="business in businesses" :key="business.id" :value="business.id">
              {{ business.name }}
            </option>
          </select>
          <span v-if="selectedBusinessId" class="active-filter-badge">
            {{ getBusinessName(selectedBusinessId) }}
          </span>
        </div>
       
        <div class="filter-group">
          <label class="filter-label">Country:</label>
          <select v-model="selectedCountry" @change="onCountryFilterChange" class="country-select">
            <option value="">All Countries</option>
            <option v-for="country in countries" :key="country.code" :value="country.code">
              {{ country.flag_emoji }} {{ country.name }}
            </option>
          </select>
          <span v-if="selectedCountry" class="active-filter-badge">
            {{ getCountryName(selectedCountry) }}
          </span>
        </div>
       
        <button @click="clearBusinessFilters" class="btn-clear-filters">Clear Filters</button>
      </div>
     
      <div v-if="selectedBusinessId || selectedCountry" class="applied-filters-info">
        <p><strong>Filters Applied:</strong></p>
        <div class="applied-filters-tags">
          <span v-if="selectedBusinessId" class="filter-tag">
            Business: {{ getBusinessName(selectedBusinessId) }}
            <span @click="removeBusinessFilter" class="tag-remove">×</span>
          </span>
          <span v-if="selectedCountry" class="filter-tag">
            Country: {{ getCountryName(selectedCountry) }}
            <span @click="removeCountryFilter" class="tag-remove">×</span>
          </span>
        </div>
      </div>
    </div>
    <!-- Loading/Error States -->
    <div v-if="!authStore.isAuthenticated" class="error-message">
      Please log in to access admin reports.
    </div>
    <div v-else-if="!authStore.isAdmin" class="error-message">
      You don't have permission to access this page.
    </div>
    <div v-else-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Loading organization reports...</p>
    </div>
    <div v-else-if="error" class="error-message">
      {{ error }}
      <button @click="retryFetch" class="btn-primary" style="margin-top: 1rem;">Retry</button>
    </div>
    <!-- Reports Dashboard -->
    <div v-else class="reports-dashboard">
      <!-- Admin Overview -->
      <div class="admin-info">
        <h2>🏢 Organization Overview</h2>
        <div class="admin-subtitle-row">
          <p class="admin-subtitle">Company-wide performance metrics and reports</p>
          <div v-if="selectedBusinessId || selectedCountry" class="active-filters-display">
            <span v-if="selectedBusinessId" class="filter-display-item">
              <span class="filter-label">Business:</span>
              <span class="filter-value">{{ getBusinessName(selectedBusinessId) }}</span>
            </span>
            <span v-if="selectedCountry" class="filter-display-item">
              <span class="filter-label">Country:</span>
              <span class="filter-value">{{ getCountryName(selectedCountry) }}</span>
            </span>
          </div>
        </div>
      </div>
     
      <!-- Quick Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon">👥</div>
          <div class="stat-content">
            <h3>Total Employees</h3>
            <p class="stat-value">{{ orgStats.total_employees || 0 }}</p>
            <p class="stat-label">
              <span v-if="selectedBusinessId || selectedCountry">Filtered Staff</span>
              <span v-else>All Staff</span>
            </p>
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
            <p class="stat-label">
              <span v-if="selectedBusinessId || selectedCountry">Filtered</span>
              <span v-else>Company-Wide</span>
            </p>
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
        <div class="generation-header">
          <h2>Generate Reports</h2>
          <p class="section-description">
            Generate detailed reports with business and country filters applied.
            <span v-if="selectedBusinessId || selectedCountry" class="filters-applied-note">
              (Business and country filters will be applied to all generated reports)
            </span>
          </p>
        </div>
       
        <!-- Report Type Selection -->
        <div class="report-type-selector">
          <div class="type-selector-header">
            <h3>Select Report Type</h3>
          </div>
          <div class="type-selector-grid">
            <button
              v-for="reportType in reportTypes"
              :key="reportType.value"
              @click="selectReportType(reportType.value)"
              :class="['type-selector-btn', { 'active': selectedReportType === reportType.value }]"
            >
              <span class="type-icon">{{ reportType.icon }}</span>
              <span class="type-name">{{ reportType.name }}</span>
            </button>
          </div>
        </div>
        <!-- Dynamic Report Form -->
        <div v-if="selectedReportType" class="dynamic-report-form">
          <div class="report-form-header">
            <h3>{{ getReportTypeName(selectedReportType) }}</h3>
            <p class="form-description">{{ getReportTypeDescription(selectedReportType) }}</p>
          </div>
         
          <div class="report-form-content">
            <!-- Attendance Report Form -->
            <div v-if="selectedReportType === 'attendance'" class="report-form">
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
             
              <div class="filter-group">
                <label>Report Type:</label>
                <select v-model="attendanceReportParams.report_type" class="filter-select">
                  <option value="summary">Summary</option>
                  <option value="detailed">Detailed</option>
                  <option value="daily">Daily</option>
                </select>
              </div>
             
              <div class="business-country-info" v-if="selectedBusinessId || selectedCountry">
                <div class="info-label">Filters will be applied:</div>
                <div class="info-tags">
                  <span v-if="selectedBusinessId" class="info-tag business">
                    Business: {{ getBusinessName(selectedBusinessId) }}
                  </span>
                  <span v-if="selectedCountry" class="info-tag country">
                    Country: {{ getCountryName(selectedCountry) }}
                  </span>
                </div>
              </div>
             
              <button @click="generateAttendanceReport" class="btn-primary generate-btn" :disabled="generatingReport">
                {{ generatingReport ? 'Generating...' : 'Generate Attendance Report' }}
              </button>
            </div>
            <!-- Leave Report Form -->
            <div v-if="selectedReportType === 'leave'" class="report-form">
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
             
              <div class="business-country-info" v-if="selectedBusinessId || selectedCountry">
                <div class="info-label">Filters will be applied:</div>
                <div class="info-tags">
                  <span v-if="selectedBusinessId" class="info-tag business">
                    Business: {{ getBusinessName(selectedBusinessId) }}
                  </span>
                  <span v-if="selectedCountry" class="info-tag country">
                    Country: {{ getCountryName(selectedCountry) }}
                  </span>
                </div>
              </div>
             
              <button @click="generateLeaveReport" class="btn-secondary generate-btn" :disabled="generatingReport">
                {{ generatingReport ? 'Generating...' : 'Generate Leave Report' }}
              </button>
            </div>
            <!-- Payroll Report Form -->
            <div v-if="selectedReportType === 'payroll'" class="report-form">
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
             
              <div class="business-country-info" v-if="selectedBusinessId || selectedCountry">
                <div class="info-label">Filters will be applied:</div>
                <div class="info-tags">
                  <span v-if="selectedBusinessId" class="info-tag business">
                    Business: {{ getBusinessName(selectedBusinessId) }}
                  </span>
                  <span v-if="selectedCountry" class="info-tag country">
                    Country: {{ getCountryName(selectedCountry) }}
                  </span>
                </div>
              </div>
             
              <button @click="generatePayrollReport" class="btn-tertiary generate-btn" :disabled="generatingReport">
                {{ generatingReport ? 'Generating...' : 'Generate Payroll Report' }}
              </button>
            </div>
            <!-- Earnings Report Form -->
            <div v-if="selectedReportType === 'earnings'" class="report-form">
              <div class="date-inputs">
                <div class="input-group">
                  <label>Start Date:</label>
                  <input type="date" v-model="earningsReportParams.start_date" class="date-input">
                </div>
                <div class="input-group">
                  <label>End Date:</label>
                  <input type="date" v-model="earningsReportParams.end_date" class="date-input">
                </div>
              </div>
             
              <div class="filter-group">
                <label>Department:</label>
                <select v-model="earningsReportParams.department" class="filter-select">
                  <option value="">All Departments</option>
                  <option v-for="dept in departments" :key="dept" :value="dept">{{ dept }}</option>
                </select>
              </div>
             
              <div class="business-country-info" v-if="selectedBusinessId || selectedCountry">
                <div class="info-label">Filters will be applied:</div>
                <div class="info-tags">
                  <span v-if="selectedBusinessId" class="info-tag business">
                    Business: {{ getBusinessName(selectedBusinessId) }}
                  </span>
                  <span v-if="selectedCountry" class="info-tag country">
                    Country: {{ getCountryName(selectedCountry) }}
                  </span>
                </div>
              </div>
             
              <button @click="generateEarningsReport" class="btn-earnings generate-btn" :disabled="generatingReport">
                {{ generatingReport ? 'Generating...' : 'Generate Earnings Report' }}
              </button>
            </div>
            <!-- Deductions Report Form -->
            <div v-if="selectedReportType === 'deductions'" class="report-form">
              <div class="date-inputs">
                <div class="input-group">
                  <label>Start Date:</label>
                  <input type="date" v-model="deductionsReportParams.start_date" class="date-input">
                </div>
                <div class="input-group">
                  <label>End Date:</label>
                  <input type="date" v-model="deductionsReportParams.end_date" class="date-input">
                </div>
              </div>
             
              <div class="filter-group">
                <label>Department:</label>
                <select v-model="deductionsReportParams.department" class="filter-select">
                  <option value="">All Departments</option>
                  <option v-for="dept in departments" :key="dept" :value="dept">{{ dept }}</option>
                </select>
              </div>
             
              <div class="filter-group">
                <label>Deduction Type:</label>
                <select v-model="deductionsReportParams.deduction_type" class="filter-select">
                  <option value="all">All Deductions</option>
                  <option value="tax">Tax Only</option>
                  <option value="statutory">Statutory Only</option>
                  <option value="pension">Pension Only</option>
                  <option value="health">Health Only</option>
                  <option value="voluntary">Voluntary Only</option>
                  <option value="other">Other Only</option>
                </select>
              </div>
             
              <div class="business-country-info" v-if="selectedBusinessId || selectedCountry">
                <div class="info-label">Filters will be applied:</div>
                <div class="info-tags">
                  <span v-if="selectedBusinessId" class="info-tag business">
                    Business: {{ getBusinessName(selectedBusinessId) }}
                  </span>
                  <span v-if="selectedCountry" class="info-tag country">
                    Country: {{ getCountryName(selectedCountry) }}
                  </span>
                </div>
              </div>
             
              <button @click="generateDeductionsReport" class="btn-deductions generate-btn" :disabled="generatingReport">
                {{ generatingReport ? 'Generating...' : 'Generate Deductions Report' }}
              </button>
            </div>
          </div>
        </div>
      </div>
      <!-- Generated Reports Section -->
      <div class="generated-reports" v-if="generatedReports.length > 0">
        <h2>Recently Generated Reports</h2>
        <div class="reports-list">
          <div v-for="report in paginatedReports" :key="report.id" class="report-item">
            <div class="report-icon">{{ getReportIcon(report.type) }}</div>
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
                <span v-if="report.filters.leave_type" class="filter-badge">
                  Type: {{ report.filters.leave_type }}
                </span>
                <span v-if="report.filters.deduction_type" class="filter-badge">
                  Deduction: {{ report.filters.deduction_type }}
                </span>
              </div>
            </div>
            <div class="report-actions">
              <button @click="downloadReport(report)" class="btn-download">📥 Download</button>
              <button @click="viewReport(report)" class="btn-view">👁️ View</button>
            </div>
          </div>
        </div>
        <div class="pagination" v-if="totalPages > 1">
          <button @click="prevPage" :disabled="currentPage === 1">Previous</button>
          <span>Page {{ currentPage }} of {{ totalPages }}</span>
          <button @click="nextPage" :disabled="currentPage === totalPages">Next</button>
        </div>
      </div>
      <!-- Report Preview Section -->
      <div class="report-preview-section" v-if="currentReport && currentReport.data">
        <div class="preview-header">
          <div>
            <h2>Report Preview: {{ currentReport.title }}</h2>
            <div class="preview-filters">
              <span v-if="currentReport.originalParams?.business_id" class="filter-tag">
                Business: {{ getBusinessName(currentReport.originalParams.business_id) }}
              </span>
              <span v-if="currentReport.originalParams?.country" class="filter-tag">
                Country: {{ getCountryName(currentReport.originalParams.country) }}
              </span>
              <span v-if="currentReport.data.currency" class="filter-tag">
                Currency: {{ currentReport.data.currency }}
              </span>
              <span v-if="currentReport.originalParams?.department" class="filter-tag">
                Department: {{ currentReport.originalParams.department }}
              </span>
            </div>
          </div>
          <button @click="currentReport = null" class="btn-close-preview">Close</button>
        </div>
       
        <div class="preview-actions">
          <button @click="downloadReport(currentReport)" class="btn-primary">
            <span>📥</span> Download PDF
          </button>
          <button @click="exportToExcel(currentReport)" class="btn-secondary">
            <span>📊</span> Export to Excel
          </button>
          <button @click="currentReport = null" class="btn-tertiary">
            <span>✕</span> Close Preview
          </button>
        </div>
       
        <div class="preview-content">
          <!-- Payroll Report Preview -->
          <div v-if="currentReport.type === 'payroll' && currentReport.data">
            <h3>Payroll Summary</h3>
            <div class="report-stats">
              <div class="report-stat">
                <span class="stat-label">Total Gross Salary:</span>
                <span class="stat-value">
                  {{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(currentReport.data.total_gross_salary || '0.00') }}
                </span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Total Net Salary:</span>
                <span class="stat-value">
                  {{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(currentReport.data.total_net_salary || '0.00') }}
                </span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Total Earnings:</span>
                <span class="stat-value">
                  {{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(currentReport.data.total_earnings || '0.00') }}
                </span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Total Deductions:</span>
                <span class="stat-value">
                  {{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(currentReport.data.total_all_deductions || '0.00') }}
                </span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Total PAYE Tax:</span>
                <span class="stat-value">
                  {{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(currentReport.data.total_paye_tax || '0.00') }}
                </span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Employees Processed:</span>
                <span class="stat-value">{{ currentReport.data.processed_employees || 0 }}</span>
              </div>
            </div>
           
            <!-- Earnings Breakdown -->
            <div v-if="currentReport.data.earning_breakdown && currentReport.data.earning_breakdown.length > 0" class="earnings-breakdown-section">
              <h4>Earnings Breakdown</h4>
              <div class="earning-cards">
                <div v-for="earning in currentReport.data.earning_breakdown" :key="earning.name" class="earning-card">
                  <div class="earning-card-header">
                    <span class="earning-name">{{ earning.name }}</span>
                    <span :class="['earning-type-badge', `type-${earning.type}`]">{{ earning.type }}</span>
                  </div>
                  <div class="earning-card-body">
                    <div class="earning-amount">{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(earning.total_amount) }}</div>
                    <div class="earning-employees">{{ earning.employee_count }} employees</div>
                  </div>
                </div>
              </div>
            </div>
           
            <!-- Deductions Breakdown -->
            <div v-if="currentReport.data.deduction_breakdown && currentReport.data.deduction_breakdown.length > 0" class="deduction-breakdown-section">
              <h4>Deductions Breakdown</h4>
              <div class="deduction-cards">
                <div v-for="deduction in currentReport.data.deduction_breakdown" :key="deduction.name" class="deduction-card">
                  <div class="deduction-card-header">
                    <span class="deduction-name">{{ deduction.name }}</span>
                    <span :class="['deduction-type-badge', `type-${deduction.type}`]">{{ deduction.type }}</span>
                  </div>
                  <div class="deduction-card-body">
                    <div class="deduction-amount">{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(deduction.total_amount) }}</div>
                    <div class="deduction-employees">{{ deduction.employee_count }} employees</div>
                  </div>
                </div>
              </div>
            </div>
           
            <!-- Payroll Details Table -->
            <h4>Employee Payslip Details</h4>
            <div class="payroll-details-table">
              <table>
                <thead>
                  <tr>
                    <th>Employee</th>
                    <th>Business</th>
                    <th>Country</th>
                    <th>Department</th>
                    <th>Gross Salary</th>
                    <th>Total Earnings</th>
                    <th>Total Deductions</th>
                    <th>Net Salary</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="detail in (currentReport.data.payslip_details || []).slice(0, 10)" :key="detail.employee_id">
                    <td>{{ detail.employee_name || 'N/A' }}</td>
                    <td>{{ detail.business || 'No Business' }}</td>
                    <td>{{ detail.country || 'N/A' }}</td>
                    <td>{{ detail.department || 'N/A' }}</td>
                    <td>{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(detail.gross_salary) }}</td>
                    <td>{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(detail.total_earnings || detail.gross_salary) }}</td>
                    <td>{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(detail.total_deductions) }}</td>
                    <td>{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(detail.net_salary) }}</td>
                  </tr>
                </tbody>
              </table>
              <div v-if="(currentReport.data.payslip_details || []).length > 10" class="more-records">
                <p>... and {{ (currentReport.data.payslip_details || []).length - 10 }} more records</p>
              </div>
            </div>
          </div>
         
          <!-- Earnings Report Preview -->
          <div v-if="currentReport.type === 'earnings' && currentReport.data">
            <h3>Earnings Summary</h3>
            <div class="report-stats">
              <div class="report-stat">
                <span class="stat-label">Total Earnings:</span>
                <span class="stat-value">
                  {{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(currentReport.data.total_earnings || '0.00') }}
                </span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Total Gross Salary:</span>
                <span class="stat-value">
                  {{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(currentReport.data.total_gross_salary || '0.00') }}
                </span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Employees Processed:</span>
                <span class="stat-value">{{ currentReport.data.processed_employees || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Average Earnings:</span>
                <span class="stat-value">
                  {{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(currentReport.data.average_earnings || '0.00') }}
                </span>
              </div>
            </div>
           
            <!-- Earnings Breakdown -->
            <div v-if="currentReport.data.earning_breakdown && currentReport.data.earning_breakdown.length > 0" class="earnings-breakdown-section">
              <h4>Earnings Breakdown</h4>
              <div class="earning-cards">
                <div v-for="earning in currentReport.data.earning_breakdown" :key="earning.name" class="earning-card">
                  <div class="earning-card-header">
                    <span class="earning-name">{{ earning.name }}</span>
                    <span :class="['earning-type-badge', `type-${earning.type}`]">{{ earning.type }}</span>
                  </div>
                  <div class="earning-card-body">
                    <div class="earning-amount">{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(earning.total_amount) }}</div>
                    <div class="earning-employees">{{ earning.employee_count }} employees</div>
                  </div>
                </div>
              </div>
            </div>
           
            <!-- Employee Earnings Table -->
            <h4>Employee Earnings Details</h4>
            <div class="payroll-details-table">
              <table>
                <thead>
                  <tr>
                    <th>Employee</th>
                    <th>Business</th>
                    <th>Country</th>
                    <th>Department</th>
                    <th v-for="header in (currentReport.data.earning_headers || [])" :key="header">{{ header }}</th>
                    <th>Total Earnings</th>
                    <th>Gross Salary</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="detail in (currentReport.data.employee_earnings || []).slice(0, 10)" :key="detail.employee_id">
                    <td>{{ detail.employee_name || 'N/A' }}</td>
                    <td>{{ detail.business || 'No Business' }}</td>
                    <td>{{ detail.country || 'N/A' }}</td>
                    <td>{{ detail.department || 'N/A' }}</td>
                    <td v-for="header in (currentReport.data.earning_headers || [])" :key="header">
                      {{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(detail.earnings_breakdown?.[header] || 0) }}
                    </td>
                    <td>{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(detail.total_earnings) }}</td>
                    <td>{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(detail.gross_salary) }}</td>
                  </tr>
                </tbody>
              </table>
              <div v-if="(currentReport.data.employee_earnings || []).length > 10" class="more-records">
                <p>... and {{ (currentReport.data.employee_earnings || []).length - 10 }} more records</p>
              </div>
            </div>
          </div>
         
          <!-- Deductions Report Preview -->
          <div v-if="currentReport.type === 'deductions' && currentReport.data">
            <h3>Deductions Summary</h3>
            <div class="report-stats">
              <div class="report-stat">
                <span class="stat-label">Total Deductions:</span>
                <span class="stat-value">
                  {{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(currentReport.data.total_deductions || '0.00') }}
                </span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Total PAYE Tax:</span>
                <span class="stat-value">
                  {{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(currentReport.data.total_paye_tax || '0.00') }}
                </span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Employees Processed:</span>
                <span class="stat-value">{{ currentReport.data.processed_employees || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Average Deductions:</span>
                <span class="stat-value">
                  {{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(currentReport.data.average_deductions || '0.00') }}
                </span>
              </div>
            </div>
           
            <!-- Deductions Breakdown -->
            <div v-if="currentReport.data.deduction_breakdown && currentReport.data.deduction_breakdown.length > 0" class="deduction-breakdown-section">
              <h4>Deductions Breakdown</h4>
              <div class="deduction-cards">
                <div v-for="deduction in currentReport.data.deduction_breakdown" :key="deduction.name" class="deduction-card">
                  <div class="deduction-card-header">
                    <span class="deduction-name">{{ deduction.name }}</span>
                    <span :class="['deduction-type-badge', `type-${deduction.type}`]">{{ deduction.type }}</span>
                  </div>
                  <div class="deduction-card-body">
                    <div class="deduction-amount">{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(deduction.total_amount) }}</div>
                    <div class="deduction-employees">{{ deduction.employee_count }} employees</div>
                  </div>
                </div>
              </div>
            </div>
           
            <!-- Employee Deductions Table -->
            <h4>Employee Deductions Details</h4>
            <div class="payroll-details-table">
              <table>
                <thead>
                  <tr>
                    <th>Employee</th>
                    <th>Business</th>
                    <th>Country</th>
                    <th>Department</th>
                    <th v-for="header in (currentReport.data.deduction_headers || [])" :key="header">{{ header }}</th>
                    <th>Total Deductions</th>
                    <th>PAYE Tax</th>
                    <th>Net Salary</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="detail in (currentReport.data.employee_deductions || []).slice(0, 10)" :key="detail.employee_id">
                    <td>{{ detail.employee_name || 'N/A' }}</td>
                    <td>{{ detail.business || 'No Business' }}</td>
                    <td>{{ detail.country || 'N/A' }}</td>
                    <td>{{ detail.department || 'N/A' }}</td>
                    <td v-for="header in (currentReport.data.deduction_headers || [])" :key="header">
                      {{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(detail.deductions_breakdown?.[header] || 0) }}
                    </td>
                    <td>{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(detail.total_deductions) }}</td>
                    <td>{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(detail.paye_tax || 0) }}</td>
                    <td>{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(detail.net_salary) }}</td>
                  </tr>
                </tbody>
              </table>
              <div v-if="(currentReport.data.employee_deductions || []).length > 10" class="more-records">
                <p>... and {{ (currentReport.data.employee_deductions || []).length - 10 }} more records</p>
              </div>
            </div>
          </div>
         
          <!-- Attendance Report Preview -->
          <div v-if="currentReport.type === 'attendance' && currentReport.data">
            <h3>Attendance Summary</h3>
            <div class="report-stats">
              <div class="report-stat">
                <span class="stat-label">Total Days:</span>
                <span class="stat-value">{{ currentReport.data.total_days || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Present Days:</span>
                <span class="stat-value">{{ currentReport.data.present_days || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Absent Days:</span>
                <span class="stat-value">{{ currentReport.data.absent_days || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Late Days:</span>
                <span class="stat-value">{{ currentReport.data.late_days || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Total Hours:</span>
                <span class="stat-value">{{ currentReport.data.total_hours || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Attendance Rate:</span>
                <span class="stat-value">{{ currentReport.data.attendance_rate || 0 }}%</span>
              </div>
            </div>
          </div>
         
          <!-- Leave Report Preview -->
          <div v-if="currentReport.type === 'leave' && currentReport.data">
            <h3>Leave Summary</h3>
            <div class="report-stats">
              <div class="report-stat">
                <span class="stat-label">Total Leaves:</span>
                <span class="stat-value">{{ currentReport.data.total_leaves || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Approved:</span>
                <span class="stat-value">{{ currentReport.data.approved_leaves || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Pending:</span>
                <span class="stat-value">{{ currentReport.data.pending_leaves || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Rejected:</span>
                <span class="stat-value">{{ currentReport.data.rejected_leaves || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Total Days:</span>
                <span class="stat-value">{{ currentReport.data.total_days || 0 }}</span>
              </div>
              <div class="report-stat">
                <span class="stat-label">Approval Rate:</span>
                <span class="stat-value">{{ currentReport.data.approval_rate || 0 }}%</span>
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
      successMessage: null,
      allEmployees: [],
      orgStats: {},
      generatedReports: [],
      currentReport: null,
      departments: [],
     
      // Business filter data
      selectedBusinessId: '',
      selectedCountry: '',
      businesses: [],
      countries: [],
     
      // Dashboard date filter
      dashboardStartDate: '',
      dashboardEndDate: '',
      currentPeriod: 'This Month',
     
      // Report type selection
      selectedReportType: '',
      reportTypes: [
        { value: 'attendance', name: 'Attendance Report', icon: '📊', description: 'Generate comprehensive attendance report for selected period' },
        { value: 'leave', name: 'Leave Report', icon: '📋', description: 'Generate leave utilization and approval reports' },
        { value: 'payroll', name: 'Payroll Report', icon: '💰', description: 'Complete payroll summary with earnings and deductions breakdown' },
        { value: 'earnings', name: 'Earnings Report', icon: '📈', description: 'Earnings-only report with breakdown by type' },
        { value: 'deductions', name: 'Deductions Report', icon: '📉', description: 'Deductions-only report with detailed breakdown' }
      ],
     
      // Report parameters
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
     
      earningsReportParams: {
        start_date: new Date().toISOString().split('T')[0],
        end_date: new Date().toISOString().split('T')[0],
        department: '',
        business_id: '',
        country: ''
      },
     
      deductionsReportParams: {
        start_date: new Date().toISOString().split('T')[0],
        end_date: new Date().toISOString().split('T')[0],
        department: '',
        business_id: '',
        country: '',
        deduction_type: 'all'
      },
     
      retryCount: 0,
      currentPage: 1,
      reportsPerPage: 5
    }
  },
 
  async mounted() {
    await this.initializeComponent()
  },
 
  computed: {
    hasActiveFilters() {
      return this.selectedBusinessId || this.selectedCountry
    },
   
    activeFilters() {
      const filters = {}
      if (this.selectedBusinessId) {
        filters.business_id = this.selectedBusinessId
        filters.business_name = this.getBusinessName(this.selectedBusinessId)
      }
      if (this.selectedCountry) {
        filters.country = this.selectedCountry
        filters.country_name = this.getCountryName(this.selectedCountry)
      }
      return filters
    },
    paginatedReports() {
      const start = (this.currentPage - 1) * this.reportsPerPage
      const end = start + this.reportsPerPage
      return this.generatedReports.slice(start, end)
    },
    totalPages() {
      return Math.ceil(this.generatedReports.length / this.reportsPerPage)
    }
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
     
      if (this.authStore.isAdmin) {
        await this.fetchBusinesses()
      }
     
      await this.fetchCountries()
      await this.fetchAdminData()
      this.loadGeneratedReports()
    },
   
    async fetchCountries() {
      try {
        const response = await axios.get('/api/admin/countries')
       
        if (Array.isArray(response.data)) {
          this.countries = response.data
        } else if (response.data.data && Array.isArray(response.data.data)) {
          this.countries = response.data.data
        } else {
          this.countries = []
        }
      } catch (error) {
        console.error('Failed to fetch countries:', error)
        this.countries = []
      }
    },
   
    async fetchBusinesses() {
      try {
        const response = await axios.get('/api/admin/businesses')
        this.businesses = response.data.data || []
      } catch (error) {
        console.error('Failed to fetch businesses:', error)
      }
    },
   
    getBusinessName(businessId) {
      const business = this.businesses.find(b => b.id === businessId)
      return business ? business.name : 'Unknown Business'
    },
   
    getCountryName(countryCode) {
      const country = this.countries.find(c => c.code === countryCode)
      return country ? `${country.flag_emoji || ''} ${country.name}`.trim() : countryCode
    },
   
    getCurrencySymbol(reportData) {
      if (reportData?.currency_symbol) {
        return reportData.currency_symbol
      }
      return reportData?.currency || 'KES'
    },
   
    // Report type methods
    selectReportType(type) {
      this.selectedReportType = type
    },
   
    getReportTypeName(type) {
      const reportType = this.reportTypes.find(rt => rt.value === type)
      return reportType ? reportType.name : 'Report'
    },
   
    getReportTypeDescription(type) {
      const reportType = this.reportTypes.find(rt => rt.value === type)
      return reportType ? reportType.description : 'Generate report'
    },
   
    onBusinessFilterChange() {
      this.attendanceReportParams.business_id = this.selectedBusinessId
      this.leaveReportParams.business_id = this.selectedBusinessId
      this.payrollReportParams.business_id = this.selectedBusinessId
      this.earningsReportParams.business_id = this.selectedBusinessId
      this.deductionsReportParams.business_id = this.selectedBusinessId
      this.fetchAdminData()
    },
   
    onCountryFilterChange() {
      this.attendanceReportParams.country = this.selectedCountry
      this.leaveReportParams.country = this.selectedCountry
      this.payrollReportParams.country = this.selectedCountry
      this.earningsReportParams.country = this.selectedCountry
      this.deductionsReportParams.country = this.selectedCountry
     
      this.fetchAdminData()
    },
   
    removeBusinessFilter() {
      this.selectedBusinessId = ''
      this.onBusinessFilterChange()
    },
   
    removeCountryFilter() {
      this.selectedCountry = ''
      this.onCountryFilterChange()
    },
   
    clearBusinessFilters() {
      this.selectedBusinessId = ''
      this.selectedCountry = ''
     
      this.attendanceReportParams.business_id = ''
      this.attendanceReportParams.country = ''
      this.leaveReportParams.business_id = ''
      this.leaveReportParams.country = ''
      this.payrollReportParams.business_id = ''
      this.payrollReportParams.country = ''
      this.earningsReportParams.business_id = ''
      this.earningsReportParams.country = ''
      this.deductionsReportParams.business_id = ''
      this.deductionsReportParams.country = ''
     
      this.fetchAdminData()
      this.showSuccess('All filters cleared!')
    },
   
    async fetchAdminData() {
      this.loading = true
      this.error = null
     
      try {
        const today = new Date()
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0]
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0).toISOString().split('T')[0]
       
        this.dashboardStartDate = firstDay
        this.dashboardEndDate = lastDay
        this.currentPeriod = 'This Month'
        const params = {
          start_date: this.dashboardStartDate,
          end_date: this.dashboardEndDate
        }
       
        if (this.selectedBusinessId) {
          params.business_id = this.selectedBusinessId
        }
       
        if (this.selectedCountry) {
          params.country = this.selectedCountry
        }
        const employeesRes = await axios.get('/api/admin/employees', { params })
        this.allEmployees = employeesRes.data.data || employeesRes.data || []
       
        this.departments = [...new Set(this.allEmployees
          .map(emp => emp.department)
          .filter(dept => dept && dept.trim() !== '')
        )].sort()
        await this.fetchFilteredStats(params)
       
      } catch (err) {
        console.error('Fetch error:', err)
        this.handleApiError(err)
      } finally {
        this.loading = false
      }
    },
   
    async fetchFilteredStats(params = {}) {
      try {
        const statsParams = {
          start_date: this.dashboardStartDate,
          end_date: this.dashboardEndDate,
          ...params
        }
       
        const statsRes = await axios.get('/api/admin/reports/stats', { params: statsParams })
        this.orgStats = statsRes.data || {}
       
        // Set default dates for all reports
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
       
        this.earningsReportParams.start_date = this.dashboardStartDate
        this.earningsReportParams.end_date = this.dashboardEndDate
        this.earningsReportParams.business_id = this.selectedBusinessId
        this.earningsReportParams.country = this.selectedCountry
       
        this.deductionsReportParams.start_date = this.dashboardStartDate
        this.deductionsReportParams.end_date = this.dashboardEndDate
        this.deductionsReportParams.business_id = this.selectedBusinessId
        this.deductionsReportParams.country = this.selectedCountry
       
      } catch (err) {
        console.error('Fetch error:', err)
        this.handleApiError(err)
      }
    },
   
    async generatePayrollReport() {
      this.generatingReport = true
      try {
        const params = {
          start_date: this.ensureDateFormat(this.payrollReportParams.start_date),
          end_date: this.ensureDateFormat(this.payrollReportParams.end_date),
          status: this.payrollReportParams.status || 'all'
        }
       
        if (this.payrollReportParams.department && String(this.payrollReportParams.department).trim() !== '') {
          params.department = this.payrollReportParams.department
        }
       
        if (this.selectedBusinessId && String(this.selectedBusinessId).trim() !== '') {
          params.business_id = this.selectedBusinessId
        }
       
        if (this.selectedCountry && String(this.selectedCountry).trim() !== '') {
          params.country = this.selectedCountry
        }
        const response = await axios.post('/api/admin/reports/generate/payroll', params)
       
        if (response.data.success) {
          const reportData = response.data.data || {}
         
          this.currentReport = {
            type: 'payroll',
            title: 'Payroll Report',
            data: reportData,
            period: reportData.period || `${params.start_date} to ${params.end_date}`,
            originalParams: { ...params }
          }
         
          this.addToGeneratedReports('payroll', 'Payroll Report', this.currentReport.period, params, reportData)
          this.showSuccess('Payroll report generated successfully!')
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
   
    async generateEarningsReport() {
      this.generatingReport = true
      try {
        const params = {
          start_date: this.ensureDateFormat(this.earningsReportParams.start_date),
          end_date: this.ensureDateFormat(this.earningsReportParams.end_date),
          status: this.payrollReportParams.status || 'all'
        }
       
        if (this.earningsReportParams.department && String(this.earningsReportParams.department).trim() !== '') {
          params.department = this.earningsReportParams.department
        }
       
        if (this.selectedBusinessId && String(this.selectedBusinessId).trim() !== '') {
          params.business_id = this.selectedBusinessId
        }
       
        if (this.selectedCountry && String(this.selectedCountry).trim() !== '') {
          params.country = this.selectedCountry
        }
        const response = await axios.post('/api/admin/reports/generate/earnings', params)
       
        if (response.data.success) {
          const reportData = response.data.data || {}
         
          this.currentReport = {
            type: 'earnings',
            title: 'Earnings Report',
            data: reportData,
            period: reportData.period || `${params.start_date} to ${params.end_date}`,
            originalParams: { ...params }
          }
         
          this.addToGeneratedReports('earnings', 'Earnings Report', this.currentReport.period, params, reportData)
          this.showSuccess('Earnings report generated successfully!')
        } else {
          throw new Error(response.data.message || 'Failed to generate report')
        }
       
      } catch (err) {
        console.error('Error generating earnings report:', err)
        this.handleApiError(err)
      } finally {
        this.generatingReport = false
      }
    },
   
    async generateDeductionsReport() {
      this.generatingReport = true
      try {
        const params = {
          start_date: this.ensureDateFormat(this.deductionsReportParams.start_date),
          end_date: this.ensureDateFormat(this.deductionsReportParams.end_date),
          status: this.payrollReportParams.status || 'all'
        }
       
        if (this.deductionsReportParams.department && String(this.deductionsReportParams.department).trim() !== '') {
          params.department = this.deductionsReportParams.department
        }
       
        if (this.selectedBusinessId && String(this.selectedBusinessId).trim() !== '') {
          params.business_id = this.selectedBusinessId
        }
       
        if (this.selectedCountry && String(this.selectedCountry).trim() !== '') {
          params.country = this.selectedCountry
        }
       
        if (this.deductionsReportParams.deduction_type && this.deductionsReportParams.deduction_type !== 'all') {
          params.deduction_type = this.deductionsReportParams.deduction_type
        }
        const response = await axios.post('/api/admin/reports/generate/deductions', params)
       
        if (response.data.success) {
          const reportData = response.data.data || {}
         
          this.currentReport = {
            type: 'deductions',
            title: 'Deductions Report',
            data: reportData,
            period: reportData.period || `${params.start_date} to ${params.end_date}`,
            originalParams: { ...params }
          }
         
          this.addToGeneratedReports('deductions', 'Deductions Report', this.currentReport.period, params, reportData)
          this.showSuccess('Deductions report generated successfully!')
        } else {
          throw new Error(response.data.message || 'Failed to generate report')
        }
       
      } catch (err) {
        console.error('Error generating deductions report:', err)
        this.handleApiError(err)
      } finally {
        this.generatingReport = false
      }
    },
   
    async generateAttendanceReport() {
      this.generatingReport = true
      try {
        const params = {
          start_date: this.ensureDateFormat(this.attendanceReportParams.start_date),
          end_date: this.ensureDateFormat(this.attendanceReportParams.end_date),
          report_type: this.attendanceReportParams.report_type || 'summary'
        }
       
        if (this.attendanceReportParams.department && String(this.attendanceReportParams.department).trim() !== '') {
          params.department = this.attendanceReportParams.department
        }
       
        if (this.selectedBusinessId && String(this.selectedBusinessId).trim() !== '') {
          params.business_id = this.selectedBusinessId
        }
       
        if (this.selectedCountry && String(this.selectedCountry).trim() !== '') {
          params.country = this.selectedCountry
        }
        const response = await axios.post('/api/admin/reports/generate/attendance', params)
       
        if (response.data.success) {
          const reportData = response.data.data || {}
         
          this.currentReport = {
            type: 'attendance',
            title: 'Attendance Report',
            data: reportData,
            period: reportData.period || `${params.start_date} to ${params.end_date}`,
            originalParams: { ...params }
          }
         
          this.addToGeneratedReports('attendance', 'Attendance Report', this.currentReport.period, params, reportData)
          this.showSuccess('Attendance report generated successfully!')
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
        const params = {
          start_date: this.ensureDateFormat(this.leaveReportParams.start_date),
          end_date: this.ensureDateFormat(this.leaveReportParams.end_date),
          leave_type: this.leaveReportParams.leave_type || '',
          status: this.leaveReportParams.status || 'all'
        }
       
        if (this.selectedBusinessId && String(this.selectedBusinessId).trim() !== '') {
          params.business_id = this.selectedBusinessId
        }
       
        if (this.selectedCountry && String(this.selectedCountry).trim() !== '') {
          params.country = this.selectedCountry
        }
       
        const response = await axios.post('/api/admin/reports/generate/leave', params)
       
        if (response.data.success) {
          const reportData = response.data.data || {}
         
          this.currentReport = {
            type: 'leave',
            title: 'Leave Report',
            data: reportData,
            period: reportData.period || `${params.start_date} to ${params.end_date}`,
            originalParams: { ...params }
          }
         
          this.addToGeneratedReports('leave', 'Leave Report', this.currentReport.period, params, reportData)
          this.showSuccess('Leave report generated successfully!')
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
   
    formatNumber(value) {
      const num = parseFloat(value)
      if (isNaN(num)) return '0.00'
      return num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      })
    },
   
    addToGeneratedReports(type, title, period, params, data) {
      const report = {
        id: Date.now(),
        type,
        title,
        period,
        filters: {
          business: params.business_id ? this.getBusinessName(params.business_id) : null,
          country: params.country ? this.getCountryName(params.country) : null,
          department: params.department || null,
          leave_type: params.leave_type || null,
          status: params.status || null,
          deduction_type: params.deduction_type || null
        },
        generated_at: new Date().toISOString(),
        data,
        originalParams: { ...params }
      }
     
      this.generatedReports.unshift(report)
     
      if (this.generatedReports.length > 10) {
        this.generatedReports = this.generatedReports.slice(0, 10)
      }
     
      this.currentPage = 1
      this.saveGeneratedReports()
    },
   
    saveGeneratedReports() {
      try {
        localStorage.setItem('admin_generated_reports', JSON.stringify(this.generatedReports))
      } catch (error) {
        console.error('Error saving generated reports:', error)
      }
    },
   
    async downloadReport(report) {
      try {
        let startDate, endDate
       
        if (report.period && report.period.includes(' to ')) {
          const [startStr, endStr] = report.period.split(' to ')
          startDate = this.formatDateForBackend(startStr)
          endDate = this.formatDateForBackend(endStr)
        } else {
          const today = new Date().toISOString().split('T')[0]
          startDate = today
          endDate = today
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
        console.error('Download error:', err)
        this.handleApiError(err)
      }
    },
   
    async exportToExcel(report) {
      try {
        let startDate, endDate
       
        if (report.period && report.period.includes(' to ')) {
          const [startStr, endStr] = report.period.split(' to ')
          startDate = this.formatDateForBackend(startStr)
          endDate = this.formatDateForBackend(endStr)
        } else {
          const today = new Date().toISOString().split('T')[0]
          startDate = today
          endDate = today
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
          throw new Error('Exported file is empty')
        }
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
          text: 'Report exported to CSV!'
        })
      } catch (err) {
        console.error('Export error:', err)
        this.handleApiError(err)
      }
    },
    formatDateForBackend(dateString) {
      const date = new Date(dateString)
      if (isNaN(date.getTime())) {
        return new Date().toISOString().split('T')[0]
      }
     
      const year = date.getFullYear()
      const month = String(date.getMonth() + 1).padStart(2, '0')
      const day = String(date.getDate()).padStart(2, '0')
     
      return `${year}-${month}-${day}`
    },
    ensureDateFormat(dateString) {
      if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) {
        return dateString
      }
      return this.formatDateForBackend(dateString)
    },
   
    getAdditionalDownloadParams(report) {
      const additionalParams = {}
      const params = report.originalParams || {}
     
      switch (report.type) {
        case 'attendance':
          if (params.department && String(params.department).trim() !== '') {
            additionalParams.department = params.department
          }
          if (params.business_id && String(params.business_id).trim() !== '') {
            additionalParams.business_id = params.business_id
          }
          if (params.country && String(params.country).trim() !== '') {
            additionalParams.country = params.country
          }
          additionalParams.report_type = params.report_type || 'summary'
          break
         
        case 'leave':
          if (params.leave_type && String(params.leave_type).trim() !== '') {
            additionalParams.leave_type = params.leave_type
          }
          if (params.status && params.status !== 'all') {
            additionalParams.status = params.status
          }
          if (params.business_id && String(params.business_id).trim() !== '') {
            additionalParams.business_id = params.business_id
          }
          if (params.country && String(params.country).trim() !== '') {
            additionalParams.country = params.country
          }
          break
         
        case 'payroll':
          if (params.department && String(params.department).trim() !== '') {
            additionalParams.department = params.department
          }
          if (params.status && params.status !== 'all') {
            additionalParams.status = params.status
          }
          if (params.business_id && String(params.business_id).trim() !== '') {
            additionalParams.business_id = params.business_id
          }
          if (params.country && String(params.country).trim() !== '') {
            additionalParams.country = params.country
          }
          break
         
        case 'earnings':
          if (params.department && String(params.department).trim() !== '') {
            additionalParams.department = params.department
          }
          if (params.business_id && String(params.business_id).trim() !== '') {
            additionalParams.business_id = params.business_id
          }
          if (params.country && String(params.country).trim() !== '') {
            additionalParams.country = params.country
          }
          break
         
        case 'deductions':
          if (params.department && String(params.department).trim() !== '') {
            additionalParams.department = params.department
          }
          if (params.business_id && String(params.business_id).trim() !== '') {
            additionalParams.business_id = params.business_id
          }
          if (params.country && String(params.country).trim() !== '') {
            additionalParams.country = params.country
          }
          if (params.deduction_type && params.deduction_type !== 'all') {
            additionalParams.deduction_type = params.deduction_type
          }
          break
      }
     
      return additionalParams
    },
   
    viewReport(report) {
      if (report.data) {
        this.currentReport = report
      } else {
        this.fetchReportData(report)
      }
      setTimeout(() => {
        const previewSection = document.querySelector('.report-preview-section')
        if (previewSection) {
          previewSection.scrollIntoView({ behavior: 'smooth', block: 'start' })
        }
      }, 100)
    },
    async fetchReportData(report) {
      this.generatingReport = true
      try {
        // Fallback: Ensure dates are set if missing in originalParams
        const params = { ...report.originalParams }
        if (!params.start_date) {
          params.start_date = this.dashboardStartDate || new Date().toISOString().split('T')[0]
        }
        if (!params.end_date) {
          params.end_date = this.dashboardEndDate || new Date().toISOString().split('T')[0]
        }
        const response = await axios.post(`/api/admin/reports/generate/${report.type}`, params)
        if (response.data.success) {
          const reportData = response.data.data || {}
          this.currentReport = {
            ...report,
            data: reportData,
            period: reportData.period || report.period
          }
        } else {
          throw new Error(response.data.message || 'Failed to fetch report data')
        }
      } catch (err) {
        this.handleApiError(err)
      } finally {
        this.generatingReport = false
      }
    },
   
    async loadGeneratedReports() {
      try {
        const savedReports = localStorage.getItem('admin_generated_reports')
        if (savedReports) {
          this.generatedReports = JSON.parse(savedReports)
        }
      } catch (err) {
        console.error('Error loading generated reports:', err)
      }
    },
   
    getReportIcon(type) {
      const icons = {
        attendance: '📊',
        leave: '📋',
        payroll: '💰',
        earnings: '📈',
        deductions: '📉'
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
   
    showSuccess(message) {
      this.successMessage = message
      this.$notify({
        type: 'success',
        title: 'Success',
        text: message,
        duration: 3000
      })
      setTimeout(() => {
        this.successMessage = null
      }, 3000)
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
    },
    prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--
      }
    },
    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++
      }
    }
  }
}
</script>

<style>
/* Add new styles for report type selector */
.report-type-selector {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  border: 1px solid var(--color-border);
}
.type-selector-header {
  margin-bottom: 1.5rem;
}
.type-selector-header h3 {
  color: var(--color-heading);
  font-size: 1.2rem;
  margin: 0;
}
.type-selector-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 1rem;
}
.type-selector-btn {
  padding: 1.25rem;
  border: 2px solid var(--color-border);
  border-radius: 10px;
  background: white;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
}
.type-selector-btn:hover {
  transform: translateY(-3px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  border-color: var(--color-primary);
}
.type-selector-btn.active {
  border-color: var(--color-primary);
  background: linear-gradient(135deg, #667eea0d 0%, #764ba20d 100%);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}
.type-icon {
  font-size: 2rem;
}
.type-name {
  font-weight: 600;
  color: var(--color-heading);
  text-align: center;
  font-size: 0.95rem;
}
.dynamic-report-form {
  background: white;
  border-radius: 12px;
  padding: 2rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  border: 1px solid var(--color-border);
}
.report-form-header {
  margin-bottom: 2rem;
}
.report-form-header h3 {
  color: var(--color-heading);
  font-size: 1.5rem;
  margin: 0 0 0.5rem 0;
}
.form-description {
  color: var(--color-text);
  margin: 0;
  font-size: 0.95rem;
}
.report-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}
.report-form .generate-btn {
  margin-top: 1rem;
}
.generation-header {
  margin-bottom: 2rem;
}
.generation-header h2 {
  color: var(--color-heading);
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
}
.section-description {
  color: var(--color-text);
  font-size: 0.95rem;
  margin-bottom: 0;
}
.filters-applied-note {
  color: var(--color-primary);
  font-weight: 500;
}
/* Earnings-specific styles */
.btn-earnings {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}
.btn-earnings:hover {
  background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
}
/* Deductions-specific styles */
.btn-deductions {
  background: linear-gradient(135deg, #f56565 0%, #ed64a6 100%);
  color: white;
}
.btn-deductions:hover {
  background: linear-gradient(135deg, #e53e3e 0%, #d53f8c 100%);
}
/* Earnings Breakdown Section */
.earnings-breakdown-section {
  margin-top: 2rem;
  margin-bottom: 2rem;
}
.earning-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1rem;
  margin-top: 1rem;
}
.earning-card {
  background: white;
  border: 1px solid #dbeafe;
  border-radius: 12px;
  padding: 1.25rem;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}
.earning-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
.earning-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}
.earning-name {
  font-weight: 600;
  color: var(--color-heading);
  font-size: 0.95rem;
}
.earning-type-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
}
.earning-type-badge.type-basic {
  background: #dbeafe;
  color: #1e40af;
}
.earning-type-badge.type-allowance {
  background: #d1fae5;
  color: #065f46;
}
.earning-type-badge.type-bonus {
  background: #fef3c7;
  color: #92400e;
}
.earning-type-badge.type-overtime {
  background: #e0e7ff;
  color: #4f46e5;
}
.earning-type-badge.type-commission {
  background: #fce7f3;
  color: #9f1239;
}
.earning-type-badge.type-other {
  background: #f3f4f6;
  color: #4b5563;
}
.earning-card-body {
  border-top: 1px solid #dbeafe;
  padding-top: 0.75rem;
}
.earning-amount {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-primary);
  margin-bottom: 0.25rem;
}
.earning-employees {
  font-size: 0.85rem;
  color: var(--color-text);
}
/* Deduction Breakdown Section */
.deduction-breakdown-section {
  margin-top: 2rem;
  margin-bottom: 2rem;
}
.deduction-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1rem;
  margin-top: 1rem;
}
.deduction-card {
  background: white;
  border: 1px solid var(--color-border);
  border-radius: 12px;
  padding: 1.25rem;
  transition: all 0.3s ease;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}
.deduction-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
.deduction-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}
.deduction-name {
  font-weight: 600;
  color: var(--color-heading);
  font-size: 0.95rem;
}
.deduction-type-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
}
.deduction-type-badge.type-tax {
  background: #fee2e2;
  color: #991b1b;
}
.deduction-type-badge.type-statutory {
  background: #fef3c7;
  color: #92400e;
}
.deduction-type-badge.type-pension {
  background: #dbeafe;
  color: #1e40af;
}
.deduction-type-badge.type-health {
  background: #d1fae5;
  color: #065f46;
}
.deduction-type-badge.type-voluntary {
  background: #fce7f3;
  color: #9f1239;
}
.deduction-type-badge.type-loan {
  background: #e0e7ff;
  color: #4f46e5;
}
.deduction-type-badge.type-other {
  background: #f3f4f6;
  color: #4b5563;
}
.deduction-card-body {
  border-top: 1px solid var(--color-border);
  padding-top: 0.75rem;
}
.deduction-amount {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--color-primary);
  margin-bottom: 0.25rem;
}
.deduction-employees {
  font-size: 0.85rem;
  color: var(--color-text);
}
/* Responsive adjustments */
@media (max-width: 1400px) {
  .earning-cards,
  .deduction-cards {
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  }
 
  .type-selector-grid {
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  }
}
@media (max-width: 768px) {
  .earning-cards,
  .deduction-cards {
    grid-template-columns: 1fr;
  }
 
  .type-selector-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
@media (max-width: 480px) {
  .type-selector-grid {
    grid-template-columns: 1fr;
  }
}
/* Pagination styles */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 1rem;
}
.pagination button {
  padding: 0.5rem 1rem;
  background: var(--color-primary);
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  transition: background 0.3s;
}
.pagination button:hover {
  background: #2563eb;
}
.pagination button:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}
.pagination span {
  font-weight: bold;
  color: var(--color-heading);
}
/* Keep all existing CSS styles from your original code */
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
  margin-bottom: 1rem;
}
.filter-group {
  display: flex;
  flex-direction: column;
  min-width: 200px;
  flex: 1;
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
  width: 100%;
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
  white-space: nowrap;
}
.btn-clear-filters:hover {
  background: #e5e7eb;
}
.applied-filters-info {
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
  border: 1px solid #e5e7eb;
  margin-top: 1rem;
}
.applied-filters-info p {
  margin: 0 0 0.5rem 0;
  font-size: 0.9rem;
  color: #374151;
}
.applied-filters-tags {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}
.filter-tag {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0.875rem;
  background: #eef2ff;
  color: #667eea;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 500;
}
.tag-remove {
  cursor: pointer;
  font-size: 1.2rem;
  line-height: 1;
  color: #667eea;
  opacity: 0.7;
  transition: opacity 0.2s;
  margin-left: 0.25rem;
}
.tag-remove:hover {
  opacity: 1;
}
/* Admin Info Section */
.admin-info h2 {
  color: var(--color-heading);
  font-size: 1.75rem;
  margin-bottom: 0.25rem;
}
.admin-subtitle-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.5rem;
}
.admin-subtitle {
  color: var(--color-text);
  font-size: 1rem;
}
.active-filters-display {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}
.filter-display-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: #f0f4ff;
  border-radius: 8px;
  font-size: 0.9rem;
}
.filter-display-item .filter-label {
  font-weight: 600;
  color: #4b5563;
  margin: 0;
}
.filter-display-item .filter-value {
  font-weight: 500;
  color: #3b82f6;
}
/* --- Buttons --- */
.btn-primary, .btn-secondary, .btn-tertiary, .btn-download, .btn-view, .btn-close-preview {
  padding: 0.75rem 1.25rem;
  border: none;
  border-radius: 8px;
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
.btn-primary:hover, .btn-secondary:hover, .btn-tertiary:hover, .btn-download:hover, .btn-view:hover {
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
  background: var(--color-secondary);
  color: white;
  padding: 0.6rem 1rem;
  font-size: 0.9rem;
}
.btn-view {
  background: var(--color-primary);
  color: white;
  padding: 0.6rem 1rem;
  font-size: 0.9rem;
}
.btn-close-preview {
  background: #6b7280;
  color: white;
}
.btn-close-preview:hover {
  background: #4b5563;
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
  background-color: #fef2f2;
  color: #ef4444;
  border: 1px solid #fca5a5;
}
.loading {
  background-color: #eff6ff;
  color: var(--color-primary);
  border: 1px solid #93c5fd;
}
/* --- Main Dashboard Sections --- */
.reports-dashboard > div:not(.admin-info) {
  background: var(--color-card-bg);
  border-radius: 16px;
  box-shadow: var(--shadow-card);
  padding: 2rem;
  margin-bottom: 2.5rem;
}
/* Report Generation Section */
.report-generation-section h2 {
  color: var(--color-heading);
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
}
.section-description {
  color: var(--color-text);
  font-size: 0.95rem;
  margin-bottom: 1.5rem;
}
.filters-applied-note {
  color: var(--color-primary);
  font-weight: 500;
}
.option-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.date-inputs {
  display: flex;
  gap: 1rem;
}
.input-group {
  flex: 1;
}
.input-group label {
  color: var(--color-heading);
  font-size: 0.9rem;
  display: block;
  margin-bottom: 0.25rem;
  font-weight: 500;
}
.date-input {
  padding: 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  font-size: 1rem;
  width: 100%;
  box-sizing: border-box;
}
.filter-group {
  margin-bottom: 0;
}
.filter-group label {
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
.business-country-info {
  padding: 0.75rem;
  background: #f0f4ff;
  border-radius: 8px;
  border: 1px solid #dbeafe;
  margin-top: 0.5rem;
}
.info-label {
  font-size: 0.85rem;
  color: #3b82f6;
  font-weight: 600;
  margin-bottom: 0.5rem;
}
.info-tags {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.info-tag {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 500;
}
.info-tag.business {
  background: #e0e7ff;
  color: #4f46e5;
}
.info-tag.country {
  background: #d1fae5;
  color: #059669;
}
.generate-btn {
  margin-top: auto;
  width: 100%;
  justify-content: center;
}
/* --- Quick Stats Grid --- */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}
.stat-card {
  background: var(--color-card-bg);
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  padding: 1.5rem;
  display: flex;
  align-items: center;
  transition: transform 0.2s;
  border-left: 5px solid var(--color-primary);
}
.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-hover);
}
.stat-icon {
  font-size: 2.5rem;
  margin-right: 1rem;
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
  margin: 0 0 0.5rem 0;
  color: var(--color-text);
  font-size: 0.85rem;
}
.report-filters {
  display: flex;
  gap: 0.5rem;
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
.report-preview-section {
  background: var(--color-card-bg);
  border-radius: 16px;
  box-shadow: var(--shadow-card);
  padding: 2rem;
  margin-bottom: 2.5rem;
}
.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid var(--color-border);
}
.preview-header h2 {
  color: var(--color-heading);
  font-size: 1.5rem;
  margin: 0 0 0.5rem 0;
}
.preview-filters {
  display: flex;
  gap: 0.75rem;
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
.report-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}
.report-stat {
  padding: 1.25rem;
  background: #f0f4ff;
  border-radius: 10px;
  border-left: 4px solid var(--color-primary);
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.report-stat .stat-label {
  color: var(--color-text);
  font-weight: 500;
  font-size: 0.9rem;
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
  border-radius: 8px;
  border: 1px solid var(--color-border);
}
.attendance-details-table table,
.leave-details-table table,
.payroll-details-table table {
  width: 100%;
  border-collapse: collapse;
  background: var(--color-card-bg);
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
  white-space: nowrap;
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
  white-space: nowrap;
}
.status-badge.present, .status-badge.approved {
  background: #d1fae5;
  color: #065f46;
}
.status-badge.absent, .status-badge.rejected {
  background: #fee2e2;
  color: #991b1b;
}
.status-badge.late, .status-badge.pending {
  background: #fef3c7;
  color: #92400e;
}
.more-records {
  text-align: center;
  padding: 1rem;
  color: var(--color-text);
  font-style: italic;
  font-size: 0.9rem;
  background: #f9fafb;
  border-top: 1px solid var(--color-border);
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
    gap: 1rem;
  }
  .header-actions {
    width: 100%;
    justify-content: flex-start;
    flex-wrap: wrap;
  }
 
  .generation-options {
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
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
  
  .admin-subtitle-row {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .active-filters-display {
    width: 100%;
  }
  
  .generation-options {
    grid-template-columns: 1fr;
  }
  
  .date-inputs {
    flex-direction: column;
  }
 
  .report-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.5rem;
  }
 
  .report-info {
    width: 100%;
  }
 
  .report-actions {
    width: 100%;
    justify-content: flex-start;
  }
 
  .preview-header {
    flex-direction: column;
    gap: 1rem;
  }
  
  .preview-actions {
    flex-direction: column;
  }
  
  .report-stats {
    grid-template-columns: 1fr;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .header-actions {
    flex-direction: column;
  }
  
  .header-actions button {
    width: 100%;
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .page-header h1 {
    font-size: 1.75rem;
  }
  
  .business-filter-header h2 {
    font-size: 1.25rem;
  }
  
  .admin-info h2 {
    font-size: 1.5rem;
  }
  
  .preview-header h2 {
    font-size: 1.25rem;
  }
  
  .attendance-details-table,
  .leave-details-table,
  .payroll-details-table {
    font-size: 0.8rem;
  }
  
  .attendance-details-table th,
  .attendance-details-table td,
  .leave-details-table th,
  .leave-details-table td,
  .payroll-details-table th,
  .payroll-details-table td {
    padding: 0.5rem;
  }
}
</style>