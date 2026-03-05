<template>
  <div class="reports-view">

    <!-- ── Auth / Error Gates ──────────────────────── -->
    <div v-if="!authStore.isAuthenticated || !authStore.isAdmin" class="empty-state">
      <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
      <p>{{ !authStore.isAuthenticated ? 'Please log in to access admin reports.' : 'You don\'t have permission to access this page.' }}</p>
    </div>

    <template v-else>

      <!-- ── Header Card ──────────────────────────── -->
      <div class="dashboard-header-card">
        <div class="header-card-accent"></div>
        <div class="user-greeting">
          <div class="avatar-section">
            <div class="avatar">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
            </div>
            <div class="user-info">
              <h1 class="greeting">Organization Reports</h1>
              <p class="subtitle">Company-wide performance metrics and report generation</p>
              <div class="role-meta">
                <span class="role-badge">Admin View</span>
                <span v-if="selectedBusinessId || selectedCountry" class="filter-active-badge">
                  Filters Active
                </span>
              </div>
            </div>
          </div>
          <div class="header-actions">
            <button @click="fetchAdminData" class="btn-outline" :disabled="loading">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"/><path d="M1 20v-6h6"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
              {{ loading ? 'Loading…' : 'Refresh' }}
            </button>
          </div>
        </div>
      </div>

      <div class="dashboard-content">

        <!-- ── Loading / Error ──────────────────────── -->
        <div v-if="loading" class="table-section">
          <div class="empty-state">
            <div class="spinner"></div>
            <p>Loading organization reports…</p>
          </div>
        </div>

        <div v-else-if="error" class="table-section">
          <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <p>{{ error }}</p>
            <button @click="retryFetch" class="btn-primary">Try Again</button>
          </div>
        </div>

        <template v-else>

          <!-- ── Metrics ──────────────────────────── -->
          <div class="metrics-section">
            <h2>Overview</h2>
            <div class="metrics-grid">
              <div class="metric-card" style="--accent:#6366f1;">
                <div class="metric-icon-wrap" style="background:rgba(99,102,241,0.1);">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div class="metric-value">{{ orgStats.total_employees || 0 }}</div>
                <div class="metric-label">Total Employees</div>
              </div>
              <div class="metric-card" style="--accent:#10b981;">
                <div class="metric-icon-wrap" style="background:rgba(16,185,129,0.1);">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <div class="metric-value">{{ orgStats.present_today || 0 }}</div>
                <div class="metric-label">Present Today</div>
              </div>
              <div class="metric-card" style="--accent:#f59e0b;">
                <div class="metric-icon-wrap" style="background:rgba(245,158,11,0.1);">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div class="metric-value">{{ orgStats.pending_leaves || 0 }}</div>
                <div class="metric-label">Pending Leaves</div>
              </div>
              <div class="metric-card" style="--accent:#3b82f6;">
                <div class="metric-icon-wrap" style="background:rgba(59,130,246,0.1);">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                </div>
                <div class="metric-value">{{ orgStats.avg_attendance || 0 }}%</div>
                <div class="metric-label">Avg. Attendance</div>
              </div>
            </div>
          </div>

          <!-- ── Filters + Report Generator ──────── -->
          <div class="table-section">

            <!-- Controls Bar -->
            <div class="controls-bar">
              <div class="filters-row">
                <!-- Business -->
                <div class="filter-group" v-if="authStore.isAdmin">
                  <label>Business</label>
                  <select v-model="selectedBusinessId" @change="onBusinessFilterChange" class="filter-select">
                    <option value="">All Businesses</option>
                    <option v-for="b in businesses" :key="b.id" :value="b.id">{{ b.name }}</option>
                  </select>
                </div>
                <!-- Country -->
                <div class="filter-group" v-if="authStore.isAdmin">
                  <label>Country</label>
                  <select v-model="selectedCountry" @change="onCountryFilterChange" class="filter-select">
                    <option value="">All Countries</option>
                    <option v-for="c in countries" :key="c.code" :value="c.code">{{ c.flag_emoji }} {{ c.name }}</option>
                  </select>
                </div>
              </div>
              <div class="controls-right">
                <button v-if="selectedBusinessId || selectedCountry" @click="clearBusinessFilters" class="btn-clear">
                  <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                  Clear Filters
                </button>
              </div>
            </div>

            <!-- Active Filter Tags -->
            <div class="active-tags" v-if="selectedBusinessId || selectedCountry">
              <span v-if="selectedBusinessId" class="filter-tag-pill">
                Business: {{ getBusinessName(selectedBusinessId) }}
                <button @click="removeBusinessFilter" class="tag-x">×</button>
              </span>
              <span v-if="selectedCountry" class="filter-tag-pill">
                Country: {{ getCountryName(selectedCountry) }}
                <button @click="removeCountryFilter" class="tag-x">×</button>
              </span>
            </div>

            <!-- ── Report Type Selector ────────────── -->
            <div class="report-type-wrap">
              <h3 class="sub-heading">Select Report Type</h3>
              <div class="report-type-grid">
                <button
                  v-for="rt in reportTypes"
                  :key="rt.value"
                  @click="selectReportType(rt.value)"
                  :class="['type-btn', { active: selectedReportType === rt.value }]"
                >
                  <span class="type-icon">{{ rt.icon }}</span>
                  <span class="type-label">{{ rt.name }}</span>
                </button>
              </div>
            </div>

            <!-- ── Dynamic Report Form ─────────────── -->
            <div v-if="selectedReportType" class="report-form-wrap">
              <div class="report-form-head">
                <div>
                  <h3>{{ getReportTypeName(selectedReportType) }}</h3>
                  <p class="form-desc">{{ getReportTypeDescription(selectedReportType) }}</p>
                </div>
              </div>

              <!-- Attendance -->
              <div v-if="selectedReportType === 'attendance'" class="report-form-grid">
                <div class="filter-group">
                  <label>Start Date</label>
                  <input type="date" v-model="attendanceReportParams.start_date" class="filter-select" />
                </div>
                <div class="filter-group">
                  <label>End Date</label>
                  <input type="date" v-model="attendanceReportParams.end_date" class="filter-select" />
                </div>
                <div class="filter-group">
                  <label>Department</label>
                  <select v-model="attendanceReportParams.department" class="filter-select">
                    <option value="">All Departments</option>
                    <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
                  </select>
                </div>
                <div class="filter-group">
                  <label>Report Type</label>
                  <select v-model="attendanceReportParams.report_type" class="filter-select">
                    <option value="summary">Summary</option>
                    <option value="detailed">Detailed</option>
                    <option value="daily">Daily</option>
                  </select>
                </div>
                <div class="form-generate-col">
                  <button @click="generateAttendanceReport" class="btn-primary btn-generate" :disabled="generatingReport">
                    <svg v-if="!generatingReport" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    {{ generatingReport ? 'Generating…' : 'Generate Report' }}
                  </button>
                </div>
              </div>

              <!-- Leave -->
              <div v-if="selectedReportType === 'leave'" class="report-form-grid">
                <div class="filter-group">
                  <label>Start Date</label>
                  <input type="date" v-model="leaveReportParams.start_date" class="filter-select" />
                </div>
                <div class="filter-group">
                  <label>End Date</label>
                  <input type="date" v-model="leaveReportParams.end_date" class="filter-select" />
                </div>
                <div class="filter-group">
                  <label>Leave Type</label>
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
                  <label>Status</label>
                  <select v-model="leaveReportParams.status" class="filter-select">
                    <option value="all">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                  </select>
                </div>
                <div class="form-generate-col">
                  <button @click="generateLeaveReport" class="btn-primary btn-generate" :disabled="generatingReport">
                    <svg v-if="!generatingReport" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    {{ generatingReport ? 'Generating…' : 'Generate Report' }}
                  </button>
                </div>
              </div>

              <!-- Payroll -->
              <div v-if="selectedReportType === 'payroll'" class="report-form-grid">
                <div class="filter-group">
                  <label>Start Date</label>
                  <input type="date" v-model="payrollReportParams.start_date" class="filter-select" />
                </div>
                <div class="filter-group">
                  <label>End Date</label>
                  <input type="date" v-model="payrollReportParams.end_date" class="filter-select" />
                </div>
                <div class="filter-group">
                  <label>Department</label>
                  <select v-model="payrollReportParams.department" class="filter-select">
                    <option value="">All Departments</option>
                    <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
                  </select>
                </div>
                <div class="filter-group">
                  <label>Status</label>
                  <select v-model="payrollReportParams.status" class="filter-select">
                    <option value="all">All Status</option>
                    <option value="paid">Paid</option>
                    <option value="pending">Pending</option>
                  </select>
                </div>
                <div class="form-generate-col">
                  <button @click="generatePayrollReport" class="btn-primary btn-generate" :disabled="generatingReport">
                    <svg v-if="!generatingReport" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    {{ generatingReport ? 'Generating…' : 'Generate Report' }}
                  </button>
                </div>
              </div>

              <!-- Earnings -->
              <div v-if="selectedReportType === 'earnings'" class="report-form-grid">
                <div class="filter-group">
                  <label>Start Date</label>
                  <input type="date" v-model="earningsReportParams.start_date" class="filter-select" />
                </div>
                <div class="filter-group">
                  <label>End Date</label>
                  <input type="date" v-model="earningsReportParams.end_date" class="filter-select" />
                </div>
                <div class="filter-group">
                  <label>Department</label>
                  <select v-model="earningsReportParams.department" class="filter-select">
                    <option value="">All Departments</option>
                    <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
                  </select>
                </div>
                <div class="form-generate-col">
                  <button @click="generateEarningsReport" class="btn-primary btn-generate" :disabled="generatingReport">
                    <svg v-if="!generatingReport" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    {{ generatingReport ? 'Generating…' : 'Generate Report' }}
                  </button>
                </div>
              </div>

              <!-- Deductions -->
              <div v-if="selectedReportType === 'deductions'" class="report-form-grid">
                <div class="filter-group">
                  <label>Start Date</label>
                  <input type="date" v-model="deductionsReportParams.start_date" class="filter-select" />
                </div>
                <div class="filter-group">
                  <label>End Date</label>
                  <input type="date" v-model="deductionsReportParams.end_date" class="filter-select" />
                </div>
                <div class="filter-group">
                  <label>Department</label>
                  <select v-model="deductionsReportParams.department" class="filter-select">
                    <option value="">All Departments</option>
                    <option v-for="d in departments" :key="d" :value="d">{{ d }}</option>
                  </select>
                </div>
                <div class="filter-group">
                  <label>Deduction Type</label>
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
                <div class="form-generate-col">
                  <button @click="generateDeductionsReport" class="btn-primary btn-generate" :disabled="generatingReport">
                    <svg v-if="!generatingReport" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    {{ generatingReport ? 'Generating…' : 'Generate Report' }}
                  </button>
                </div>
              </div>

              <!-- Applied Filters Notice -->
              <div v-if="selectedBusinessId || selectedCountry" class="applied-notice">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                Filters applied:
                <span v-if="selectedBusinessId" class="notice-tag">{{ getBusinessName(selectedBusinessId) }}</span>
                <span v-if="selectedCountry" class="notice-tag">{{ getCountryName(selectedCountry) }}</span>
              </div>
            </div>

          </div><!-- /table-section (generator) -->

          <!-- ── Generated Reports List ──────────── -->
          <div class="table-section" v-if="generatedReports.length > 0">
            <h2>Recently Generated Reports</h2>

            <div class="reports-list">
              <div v-for="report in paginatedReports" :key="report.id" class="report-row">
                <div class="report-row-icon">{{ getReportIcon(report.type) }}</div>
                <div class="report-row-info">
                  <div class="report-row-title">{{ report.title }}</div>
                  <div class="report-row-period">{{ report.period }}</div>
                  <div class="report-row-date">Generated {{ formatDate(report.generated_at) }}</div>
                  <div class="report-row-tags" v-if="report.filters">
                    <span v-if="report.filters.business"    class="mini-tag">{{ report.filters.business }}</span>
                    <span v-if="report.filters.country"     class="mini-tag">{{ report.filters.country }}</span>
                    <span v-if="report.filters.department"  class="mini-tag">{{ report.filters.department }}</span>
                    <span v-if="report.filters.leave_type"  class="mini-tag">{{ report.filters.leave_type }}</span>
                    <span v-if="report.filters.deduction_type" class="mini-tag">{{ report.filters.deduction_type }}</span>
                  </div>
                </div>
                <div class="report-row-actions">
                  <button @click="downloadReport(report)" class="action-btn" title="Download PDF">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    PDF
                  </button>
                  <button @click="exportToExcel(report)" class="action-btn export" title="Export CSV">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                    CSV
                  </button>
                  <button @click="viewReport(report)" class="action-btn view" title="View Report">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    View
                  </button>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <div v-if="totalPages > 1" class="pagination-bar">
              <span class="pagination-info">Page <strong>{{ currentPage }}</strong> of <strong>{{ totalPages }}</strong></span>
              <div class="pagination-controls">
                <button @click="prevPage" :disabled="currentPage === 1" class="page-btn">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                  Prev
                </button>
                <button @click="nextPage" :disabled="currentPage === totalPages" class="page-btn">
                  Next
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
              </div>
            </div>
          </div>

          <!-- ── Report Preview ────────────────────── -->
          <div class="table-section" v-if="currentReport && currentReport.data">

            <div class="preview-header-row">
              <div>
                <h2>Preview: {{ currentReport.title }}</h2>
                <div class="preview-tags">
                  <span v-if="currentReport.originalParams?.business_id" class="mini-tag">{{ getBusinessName(currentReport.originalParams.business_id) }}</span>
                  <span v-if="currentReport.originalParams?.country" class="mini-tag">{{ getCountryName(currentReport.originalParams.country) }}</span>
                  <span v-if="currentReport.data?.currency" class="mini-tag">{{ currentReport.data.currency }}</span>
                </div>
              </div>
              <div class="preview-actions">
                <button @click="downloadReport(currentReport)" class="btn-outline export">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                  Download PDF
                </button>
                <button @click="exportToExcel(currentReport)" class="btn-outline">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                  Export CSV
                </button>
                <button @click="currentReport = null" class="btn-outline danger">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                  Close
                </button>
              </div>
            </div>

            <!-- ── Summary Stats Strip ─────────────── -->
            <!-- Payroll -->
            <div v-if="currentReport.type === 'payroll'" class="preview-content">
              <div class="preview-stats">
                <div class="preview-stat"><small>Gross Salary</small><strong>{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(currentReport.data.total_gross_salary) }}</strong></div>
                <div class="preview-stat"><small>Net Salary</small><strong>{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(currentReport.data.total_net_salary) }}</strong></div>
                <div class="preview-stat"><small>Total Earnings</small><strong>{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(currentReport.data.total_earnings) }}</strong></div>
                <div class="preview-stat"><small>Total Deductions</small><strong>{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(currentReport.data.total_all_deductions) }}</strong></div>
                <div class="preview-stat"><small>PAYE Tax</small><strong>{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(currentReport.data.total_paye_tax) }}</strong></div>
                <div class="preview-stat"><small>Employees</small><strong>{{ currentReport.data.processed_employees || 0 }}</strong></div>
              </div>

              <!-- Earnings breakdown cards -->
              <div v-if="currentReport.data.earning_breakdown?.length" class="breakdown-section">
                <h4>Earnings Breakdown</h4>
                <div class="breakdown-grid">
                  <div v-for="e in currentReport.data.earning_breakdown" :key="e.name" class="breakdown-card green">
                    <div class="bc-head">
                      <span class="bc-name">{{ e.name }}</span>
                      <span :class="['bc-badge', `type-${e.type}`]">{{ e.type }}</span>
                    </div>
                    <div class="bc-amount">{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(e.total_amount) }}</div>
                    <div class="bc-sub">{{ e.employee_count }} employees</div>
                  </div>
                </div>
              </div>

              <!-- Deductions breakdown cards -->
              <div v-if="currentReport.data.deduction_breakdown?.length" class="breakdown-section">
                <h4>Deductions Breakdown</h4>
                <div class="breakdown-grid">
                  <div v-for="d in currentReport.data.deduction_breakdown" :key="d.name" class="breakdown-card red">
                    <div class="bc-head">
                      <span class="bc-name">{{ d.name }}</span>
                      <span :class="['bc-badge', `type-${d.type}`]">{{ d.type }}</span>
                    </div>
                    <div class="bc-amount">{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(d.total_amount) }}</div>
                    <div class="bc-sub">{{ d.employee_count }} employees</div>
                  </div>
                </div>
              </div>

              <!-- Payslip details table -->
              <h4>Employee Payslip Details</h4>
              <div class="table-container">
                <div class="preview-grid payroll-grid list-header">
                  <div>Employee</div><div>Business</div><div>Department</div>
                  <div class="text-right">Gross</div><div class="text-right">Earnings</div>
                  <div class="text-right">Deductions</div><div class="text-right">Net</div>
                </div>
                <div v-for="d in (currentReport.data.payslip_details || []).slice(0,10)" :key="d.employee_id" class="preview-grid payroll-grid list-row">
                  <div class="fw-600">{{ d.employee_name || 'N/A' }}</div>
                  <div class="text-muted">{{ d.business || '—' }}</div>
                  <div class="text-muted">{{ d.department || '—' }}</div>
                  <div class="text-right font-mono">{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(d.gross_salary) }}</div>
                  <div class="text-right font-mono text-success">{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(d.total_earnings || d.gross_salary) }}</div>
                  <div class="text-right font-mono text-danger">{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(d.total_deductions) }}</div>
                  <div class="text-right font-mono fw-700">{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(d.net_salary) }}</div>
                </div>
              </div>
              <div v-if="(currentReport.data.payslip_details||[]).length > 10" class="more-records">…and {{ (currentReport.data.payslip_details||[]).length - 10 }} more records</div>
            </div>

            <!-- Earnings -->
            <div v-if="currentReport.type === 'earnings'" class="preview-content">
              <div class="preview-stats">
                <div class="preview-stat"><small>Total Earnings</small><strong>{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(currentReport.data.total_earnings) }}</strong></div>
                <div class="preview-stat"><small>Gross Salary</small><strong>{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(currentReport.data.total_gross_salary) }}</strong></div>
                <div class="preview-stat"><small>Average Earnings</small><strong>{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(currentReport.data.average_earnings) }}</strong></div>
                <div class="preview-stat"><small>Employees</small><strong>{{ currentReport.data.processed_employees || 0 }}</strong></div>
              </div>
              <div v-if="currentReport.data.earning_breakdown?.length" class="breakdown-section">
                <h4>Earnings Breakdown</h4>
                <div class="breakdown-grid">
                  <div v-for="e in currentReport.data.earning_breakdown" :key="e.name" class="breakdown-card green">
                    <div class="bc-head"><span class="bc-name">{{ e.name }}</span><span :class="['bc-badge', `type-${e.type}`]">{{ e.type }}</span></div>
                    <div class="bc-amount">{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(e.total_amount) }}</div>
                    <div class="bc-sub">{{ e.employee_count }} employees</div>
                  </div>
                </div>
              </div>
              <h4>Employee Earnings Details</h4>
              <div class="table-container">
                <div class="list-header" style="display:grid;grid-template-columns:2fr 1fr 1fr repeat(3,1fr);padding:.75rem 1rem;gap:.75rem;font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">
                  <div>Employee</div><div>Business</div><div>Department</div>
                  <div v-for="h in (currentReport.data.earning_headers||[])" :key="h" class="text-right">{{ h }}</div>
                  <div class="text-right">Total</div><div class="text-right">Gross</div>
                </div>
                <div v-for="d in (currentReport.data.employee_earnings||[]).slice(0,10)" :key="d.employee_id"
                  style="display:grid;grid-template-columns:2fr 1fr 1fr repeat(3,1fr);padding:.75rem 1rem;gap:.75rem;border-bottom:1px solid #f1f5f9;background:white;font-size:.82rem;">
                  <div class="fw-600">{{ d.employee_name || 'N/A' }}</div>
                  <div class="text-muted">{{ d.business || '—' }}</div>
                  <div class="text-muted">{{ d.department || '—' }}</div>
                  <div v-for="h in (currentReport.data.earning_headers||[])" :key="h" class="text-right font-mono">{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(d.earnings_breakdown?.[h]||0) }}</div>
                  <div class="text-right font-mono text-success">{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(d.total_earnings) }}</div>
                  <div class="text-right font-mono">{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(d.gross_salary) }}</div>
                </div>
              </div>
              <div v-if="(currentReport.data.employee_earnings||[]).length > 10" class="more-records">…and {{ (currentReport.data.employee_earnings||[]).length - 10 }} more records</div>
            </div>

            <!-- Deductions -->
            <div v-if="currentReport.type === 'deductions'" class="preview-content">
              <div class="preview-stats">
                <div class="preview-stat"><small>Total Deductions</small><strong>{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(currentReport.data.total_deductions) }}</strong></div>
                <div class="preview-stat"><small>PAYE Tax</small><strong>{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(currentReport.data.total_paye_tax) }}</strong></div>
                <div class="preview-stat"><small>Avg Deductions</small><strong>{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(currentReport.data.average_deductions) }}</strong></div>
                <div class="preview-stat"><small>Employees</small><strong>{{ currentReport.data.processed_employees || 0 }}</strong></div>
              </div>
              <div v-if="currentReport.data.deduction_breakdown?.length" class="breakdown-section">
                <h4>Deductions Breakdown</h4>
                <div class="breakdown-grid">
                  <div v-for="d in currentReport.data.deduction_breakdown" :key="d.name" class="breakdown-card red">
                    <div class="bc-head"><span class="bc-name">{{ d.name }}</span><span :class="['bc-badge', `type-${d.type}`]">{{ d.type }}</span></div>
                    <div class="bc-amount">{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(d.total_amount) }}</div>
                    <div class="bc-sub">{{ d.employee_count }} employees</div>
                  </div>
                </div>
              </div>
              <h4>Employee Deductions Details</h4>
              <div class="table-container">
                <div class="list-header" style="display:grid;grid-template-columns:2fr 1fr 1fr repeat(3,1fr) 1fr 1fr;padding:.75rem 1rem;gap:.5rem;font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase;">
                  <div>Employee</div><div>Business</div><div>Department</div>
                  <div v-for="h in (currentReport.data.deduction_headers||[])" :key="h" class="text-right">{{ h }}</div>
                  <div class="text-right">Total</div><div class="text-right">PAYE</div><div class="text-right">Net</div>
                </div>
                <div v-for="d in (currentReport.data.employee_deductions||[]).slice(0,10)" :key="d.employee_id"
                  style="display:grid;grid-template-columns:2fr 1fr 1fr repeat(3,1fr) 1fr 1fr;padding:.75rem 1rem;gap:.5rem;border-bottom:1px solid #f1f5f9;background:white;font-size:.82rem;">
                  <div class="fw-600">{{ d.employee_name || 'N/A' }}</div>
                  <div class="text-muted">{{ d.business || '—' }}</div>
                  <div class="text-muted">{{ d.department || '—' }}</div>
                  <div v-for="h in (currentReport.data.deduction_headers||[])" :key="h" class="text-right font-mono">{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(d.deductions_breakdown?.[h]||0) }}</div>
                  <div class="text-right font-mono text-danger">{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(d.total_deductions) }}</div>
                  <div class="text-right font-mono">{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(d.paye_tax||0) }}</div>
                  <div class="text-right font-mono fw-700">{{ getCurrencySymbol(currentReport.data) }}{{ formatNumber(d.net_salary) }}</div>
                </div>
              </div>
              <div v-if="(currentReport.data.employee_deductions||[]).length > 10" class="more-records">…and {{ (currentReport.data.employee_deductions||[]).length - 10 }} more records</div>
            </div>

            <!-- Attendance -->
            <div v-if="currentReport.type === 'attendance'" class="preview-content">
              <div class="preview-stats">
                <div class="preview-stat"><small>Total Days</small><strong>{{ currentReport.data.total_days || 0 }}</strong></div>
                <div class="preview-stat"><small>Present</small><strong class="text-success">{{ currentReport.data.present_days || 0 }}</strong></div>
                <div class="preview-stat"><small>Absent</small><strong class="text-danger">{{ currentReport.data.absent_days || 0 }}</strong></div>
                <div class="preview-stat"><small>Late</small><strong class="text-amber">{{ currentReport.data.late_days || 0 }}</strong></div>
                <div class="preview-stat"><small>Total Hours</small><strong>{{ currentReport.data.total_hours || 0 }}</strong></div>
                <div class="preview-stat"><small>Attendance Rate</small><strong>{{ currentReport.data.attendance_rate || 0 }}%</strong></div>
              </div>
            </div>

            <!-- Leave -->
            <div v-if="currentReport.type === 'leave'" class="preview-content">
              <div class="preview-stats">
                <div class="preview-stat"><small>Total Leaves</small><strong>{{ currentReport.data.total_leaves || 0 }}</strong></div>
                <div class="preview-stat"><small>Approved</small><strong class="text-success">{{ currentReport.data.approved_leaves || 0 }}</strong></div>
                <div class="preview-stat"><small>Pending</small><strong class="text-amber">{{ currentReport.data.pending_leaves || 0 }}</strong></div>
                <div class="preview-stat"><small>Rejected</small><strong class="text-danger">{{ currentReport.data.rejected_leaves || 0 }}</strong></div>
                <div class="preview-stat"><small>Total Days</small><strong>{{ currentReport.data.total_days || 0 }}</strong></div>
                <div class="preview-stat"><small>Approval Rate</small><strong>{{ currentReport.data.approval_rate || 0 }}%</strong></div>
              </div>
            </div>

            <!-- Net Pay card — mirrors payslip design -->
            <div class="net-pay-card">
              <div>
                <div class="net-label">REPORT PERIOD</div>
                <div class="net-value">{{ currentReport.period }}</div>
              </div>
              <div class="net-bg">RP</div>
            </div>

          </div><!-- /preview -->

        </template>
      </div><!-- /dashboard-content -->
    </template>

    <!-- ── Toast ─────────────────────────────────── -->
    <transition name="toast-slide">
      <div v-if="toast.show" :class="['toast', toast.type]">
        <svg v-if="toast.type==='success'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span>{{ toast.message }}</span>
      </div>
    </transition>

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
      selectedBusinessId: '',
      selectedCountry: '',
      businesses: [],
      countries: [],
      dashboardStartDate: '',
      dashboardEndDate: '',
      toast: { show: false, message: '', type: 'success' },
      selectedReportType: '',
      reportTypes: [
        { value: 'attendance', name: 'Attendance',  icon: '📊', description: 'Comprehensive attendance report for selected period' },
        { value: 'leave',      name: 'Leave',       icon: '📋', description: 'Leave utilization and approval report' },
        { value: 'payroll',    name: 'Payroll',     icon: '💰', description: 'Full payroll summary with earnings and deductions' },
        { value: 'earnings',   name: 'Earnings',    icon: '📈', description: 'Earnings-only report with type breakdown' },
        { value: 'deductions', name: 'Deductions',  icon: '📉', description: 'Deductions-only report with detailed breakdown' }
      ],
      attendanceReportParams:  { start_date: new Date().toISOString().split('T')[0], end_date: new Date().toISOString().split('T')[0], department: '', report_type: 'summary' },
      leaveReportParams:       { start_date: new Date().toISOString().split('T')[0], end_date: new Date().toISOString().split('T')[0], leave_type: '', status: 'all' },
      payrollReportParams:     { start_date: new Date().toISOString().split('T')[0], end_date: new Date().toISOString().split('T')[0], department: '', status: 'all' },
      earningsReportParams:    { start_date: new Date().toISOString().split('T')[0], end_date: new Date().toISOString().split('T')[0], department: '' },
      deductionsReportParams:  { start_date: new Date().toISOString().split('T')[0], end_date: new Date().toISOString().split('T')[0], department: '', deduction_type: 'all' },
      retryCount: 0,
      currentPage: 1,
      reportsPerPage: 5
    }
  },
  computed: {
    paginatedReports() { const s = (this.currentPage-1)*this.reportsPerPage; return this.generatedReports.slice(s, s+this.reportsPerPage); },
    totalPages()        { return Math.ceil(this.generatedReports.length / this.reportsPerPage); }
  },
  async mounted() { await this.initializeComponent(); },
  methods: {
    async initializeComponent() {
      if (!this.authStore.isAuthenticated || !this.authStore.isAdmin) return;
      await Promise.all([this.fetchBusinesses(), this.fetchCountries()]);
      await this.fetchAdminData();
      this.loadGeneratedReports();
    },
    async fetchCountries() {
      try { const r = await axios.get('/api/admin/countries'); this.countries = Array.isArray(r.data) ? r.data : r.data.data || []; } catch { this.countries = []; }
    },
    async fetchBusinesses() {
      try { const r = await axios.get('/api/admin/businesses'); this.businesses = r.data.data || []; } catch {}
    },
    getBusinessName(id)  { return this.businesses.find(b => b.id === id)?.name || 'Unknown'; },
    getCountryName(code) { const c = this.countries.find(c => c.code === code); return c ? `${c.flag_emoji||''} ${c.name}`.trim() : code; },
    getCurrencySymbol(d) { return d?.currency_symbol || d?.currency || 'K'; },
    selectReportType(type) { this.selectedReportType = type; },
    getReportTypeName(type) { return this.reportTypes.find(r => r.value === type)?.name || 'Report'; },
    getReportTypeDescription(type) { return this.reportTypes.find(r => r.value === type)?.description || ''; },
    onBusinessFilterChange() { this.fetchAdminData(); },
    onCountryFilterChange()  { this.fetchAdminData(); },
    removeBusinessFilter()   { this.selectedBusinessId = ''; this.fetchAdminData(); },
    removeCountryFilter()    { this.selectedCountry = '';    this.fetchAdminData(); },
    clearBusinessFilters()   { this.selectedBusinessId = ''; this.selectedCountry = ''; this.fetchAdminData(); this.showToast('Filters cleared.', 'success'); },
    async fetchAdminData() {
      this.loading = true; this.error = null;
      try {
        const today = new Date();
        this.dashboardStartDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
        this.dashboardEndDate   = new Date(today.getFullYear(), today.getMonth()+1, 0).toISOString().split('T')[0];
        const params = { start_date: this.dashboardStartDate, end_date: this.dashboardEndDate };
        if (this.selectedBusinessId) params.business_id = this.selectedBusinessId;
        if (this.selectedCountry)    params.country = this.selectedCountry;
        const empR = await axios.get('/api/admin/employees', { params });
        this.allEmployees = empR.data.data || empR.data || [];
        this.departments  = [...new Set(this.allEmployees.map(e => e.department).filter(Boolean))].sort();
        const statR = await axios.get('/api/admin/reports/stats', { params });
        this.orgStats = statR.data || {};
      } catch (err) { this.handleApiError(err); }
      finally { this.loading = false; }
    },
    // ── Report Generators ──
    async generateAttendanceReport() {
      this.generatingReport = true;
      try {
        const params = { start_date: this.ensureDate(this.attendanceReportParams.start_date), end_date: this.ensureDate(this.attendanceReportParams.end_date), report_type: this.attendanceReportParams.report_type || 'summary' };
        if (this.attendanceReportParams.department) params.department = this.attendanceReportParams.department;
        if (this.selectedBusinessId) params.business_id = this.selectedBusinessId;
        if (this.selectedCountry)    params.country = this.selectedCountry;
        const r = await axios.post('/api/admin/reports/generate/attendance', params);
        if (!r.data.success) throw new Error(r.data.message);
        this.currentReport = { type: 'attendance', title: 'Attendance Report', data: r.data.data || {}, period: r.data.data?.period || `${params.start_date} to ${params.end_date}`, originalParams: params };
        this.addToGenerated('attendance', 'Attendance Report', this.currentReport.period, params, r.data.data);
        this.showToast('Attendance report generated!', 'success');
      } catch (err) { this.handleApiError(err); }
      finally { this.generatingReport = false; }
    },
    async generateLeaveReport() {
      this.generatingReport = true;
      try {
        const params = { start_date: this.ensureDate(this.leaveReportParams.start_date), end_date: this.ensureDate(this.leaveReportParams.end_date), leave_type: this.leaveReportParams.leave_type || '', status: this.leaveReportParams.status || 'all' };
        if (this.selectedBusinessId) params.business_id = this.selectedBusinessId;
        if (this.selectedCountry)    params.country = this.selectedCountry;
        const r = await axios.post('/api/admin/reports/generate/leave', params);
        if (!r.data.success) throw new Error(r.data.message);
        this.currentReport = { type: 'leave', title: 'Leave Report', data: r.data.data || {}, period: r.data.data?.period || `${params.start_date} to ${params.end_date}`, originalParams: params };
        this.addToGenerated('leave', 'Leave Report', this.currentReport.period, params, r.data.data);
        this.showToast('Leave report generated!', 'success');
      } catch (err) { this.handleApiError(err); }
      finally { this.generatingReport = false; }
    },
    async generatePayrollReport() {
      this.generatingReport = true;
      try {
        const params = { start_date: this.ensureDate(this.payrollReportParams.start_date), end_date: this.ensureDate(this.payrollReportParams.end_date), status: this.payrollReportParams.status || 'all' };
        if (this.payrollReportParams.department) params.department = this.payrollReportParams.department;
        if (this.selectedBusinessId) params.business_id = this.selectedBusinessId;
        if (this.selectedCountry)    params.country = this.selectedCountry;
        const r = await axios.post('/api/admin/reports/generate/payroll', params);
        if (!r.data.success) throw new Error(r.data.message);
        this.currentReport = { type: 'payroll', title: 'Payroll Report', data: r.data.data || {}, period: r.data.data?.period || `${params.start_date} to ${params.end_date}`, originalParams: params };
        this.addToGenerated('payroll', 'Payroll Report', this.currentReport.period, params, r.data.data);
        this.showToast('Payroll report generated!', 'success');
      } catch (err) { this.handleApiError(err); }
      finally { this.generatingReport = false; }
    },
    async generateEarningsReport() {
      this.generatingReport = true;
      try {
        const params = { start_date: this.ensureDate(this.earningsReportParams.start_date), end_date: this.ensureDate(this.earningsReportParams.end_date) };
        if (this.earningsReportParams.department) params.department = this.earningsReportParams.department;
        if (this.selectedBusinessId) params.business_id = this.selectedBusinessId;
        if (this.selectedCountry)    params.country = this.selectedCountry;
        const r = await axios.post('/api/admin/reports/generate/earnings', params);
        if (!r.data.success) throw new Error(r.data.message);
        this.currentReport = { type: 'earnings', title: 'Earnings Report', data: r.data.data || {}, period: r.data.data?.period || `${params.start_date} to ${params.end_date}`, originalParams: params };
        this.addToGenerated('earnings', 'Earnings Report', this.currentReport.period, params, r.data.data);
        this.showToast('Earnings report generated!', 'success');
      } catch (err) { this.handleApiError(err); }
      finally { this.generatingReport = false; }
    },
    async generateDeductionsReport() {
      this.generatingReport = true;
      try {
        const params = { start_date: this.ensureDate(this.deductionsReportParams.start_date), end_date: this.ensureDate(this.deductionsReportParams.end_date) };
        if (this.deductionsReportParams.department)    params.department = this.deductionsReportParams.department;
        if (this.deductionsReportParams.deduction_type !== 'all') params.deduction_type = this.deductionsReportParams.deduction_type;
        if (this.selectedBusinessId) params.business_id = this.selectedBusinessId;
        if (this.selectedCountry)    params.country = this.selectedCountry;
        const r = await axios.post('/api/admin/reports/generate/deductions', params);
        if (!r.data.success) throw new Error(r.data.message);
        this.currentReport = { type: 'deductions', title: 'Deductions Report', data: r.data.data || {}, period: r.data.data?.period || `${params.start_date} to ${params.end_date}`, originalParams: params };
        this.addToGenerated('deductions', 'Deductions Report', this.currentReport.period, params, r.data.data);
        this.showToast('Deductions report generated!', 'success');
      } catch (err) { this.handleApiError(err); }
      finally { this.generatingReport = false; }
    },
    addToGenerated(type, title, period, params, data) {
      const r = { id: Date.now(), type, title, period, filters: { business: params.business_id ? this.getBusinessName(params.business_id) : null, country: params.country ? this.getCountryName(params.country) : null, department: params.department || null, leave_type: params.leave_type || null, deduction_type: params.deduction_type || null }, generated_at: new Date().toISOString(), data, originalParams: { ...params } };
      this.generatedReports.unshift(r);
      if (this.generatedReports.length > 10) this.generatedReports = this.generatedReports.slice(0,10);
      this.currentPage = 1;
      try { localStorage.setItem('admin_generated_reports', JSON.stringify(this.generatedReports)); } catch {}
    },
    loadGeneratedReports() {
      try { const s = localStorage.getItem('admin_generated_reports'); if (s) this.generatedReports = JSON.parse(s); } catch {}
    },
    async downloadReport(report) {
      try {
        const [sd, ed] = report.period?.includes(' to ') ? report.period.split(' to ').map(this.ensureDate) : [new Date().toISOString().split('T')[0], new Date().toISOString().split('T')[0]];
        const r = await axios.post(`/api/admin/reports/download/${report.type}`, { format:'pdf', start_date:sd, end_date:ed, ...this.getDownloadParams(report) }, { responseType:'blob' });
        const link = document.createElement('a'); link.href = URL.createObjectURL(new Blob([r.data],{type:'application/pdf'}));
        link.download = `${report.type}_report_${Date.now()}.pdf`; link.click();
        this.showToast('Download started!', 'success');
      } catch (err) { this.handleApiError(err); }
    },
    async exportToExcel(report) {
      try {
        const [sd, ed] = report.period?.includes(' to ') ? report.period.split(' to ').map(this.ensureDate) : [new Date().toISOString().split('T')[0], new Date().toISOString().split('T')[0]];
        const r = await axios.post(`/api/admin/reports/download/${report.type}`, { format:'csv', start_date:sd, end_date:ed, ...this.getDownloadParams(report) }, { responseType:'blob' });
        const link = document.createElement('a'); link.href = URL.createObjectURL(new Blob([r.data]));
        link.download = `${report.type}_report_${Date.now()}.csv`; link.click();
        this.showToast('CSV exported!', 'success');
      } catch (err) { this.handleApiError(err); }
    },
    getDownloadParams(report) {
      const p = report.originalParams || {}; const out = {};
      if (p.department)     out.department = p.department;
      if (p.business_id)    out.business_id = p.business_id;
      if (p.country)        out.country = p.country;
      if (p.report_type)    out.report_type = p.report_type;
      if (p.leave_type)     out.leave_type = p.leave_type;
      if (p.status && p.status !== 'all') out.status = p.status;
      if (p.deduction_type && p.deduction_type !== 'all') out.deduction_type = p.deduction_type;
      return out;
    },
    viewReport(report) {
      this.currentReport = report;
      setTimeout(() => { document.querySelector('.report-preview-section')?.scrollIntoView({ behavior:'smooth' }); }, 100);
    },
    retryFetch() { this.retryCount++; if (this.retryCount <= 3) this.fetchAdminData(); else { this.error = 'Max retries exceeded.'; this.retryCount = 0; } },
    handleApiError(err) {
      const msg = err.response?.status === 401 ? 'Session expired.' : err.response?.status === 403 ? 'Access denied.' : err.response?.status === 422 ? 'Invalid parameters.' : err.response?.data?.message || 'An error occurred.';
      this.error = msg; this.showToast(msg, 'error');
      if (err.response?.status === 401) { this.authStore.clearAuth?.(); this.$router?.push?.({name:'login'}); }
    },
    showToast(message, type = 'success') { this.toast = { show: true, message, type }; setTimeout(() => { this.toast.show = false; }, 3500); },
    formatNumber(v) { const n = parseFloat(v); return isNaN(n) ? '0.00' : n.toLocaleString('en-US',{minimumFractionDigits:2,maximumFractionDigits:2}); },
    formatDate(d) { return d ? new Date(d).toLocaleDateString('en-US',{year:'numeric',month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'}) : 'N/A'; },
    getReportIcon(type) { return {attendance:'📊',leave:'📋',payroll:'💰',earnings:'📈',deductions:'📉'}[type] || '📄'; },
    ensureDate(s) { if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s; try { const d=new Date(s); return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`; } catch { return new Date().toISOString().split('T')[0]; } },
    prevPage() { if (this.currentPage > 1) this.currentPage--; },
    nextPage() { if (this.currentPage < this.totalPages) this.currentPage++; }
  }
}
</script>

<style scoped>
/* ── Base ──────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; }

.reports-view {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
  padding: 1.5rem 2rem 3rem;
  max-width: 1300px;
  margin: 0 auto;
}

/* ── Header Card ─────────────────────────────────── */
.dashboard-header-card {
  background: white; border-radius: 16px; padding: 1.5rem 1.75rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;
  margin-bottom: 1.25rem; position: relative; overflow: hidden;
}
.header-card-accent { position: absolute; top: 0; left: 0; right: 0; height: 3px; }
.user-greeting  { display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
.avatar-section { display: flex; align-items: center; gap: 1rem; }
.avatar { width: 52px; height: 52px; background: linear-gradient(135deg, #3b82f6, #6366f1); border-radius: 14px; display: flex; align-items: center; justify-content: center; color: white; box-shadow: 0 4px 12px rgba(59,130,246,0.25); flex-shrink: 0; }
.user-info { display: flex; flex-direction: column; gap: 0.2rem; }
.greeting  { margin: 0; font-size: 1.375rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
.subtitle  { margin: 0; color: #64748b; font-size: 0.875rem; }
.role-meta { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.125rem; }
.role-badge { background: #f0fdf4; border: 1px solid #bbf7d0; padding: 0.125rem 0.6rem; border-radius: 8px; font-size: 0.7rem; font-weight: 600; color: #166534; }
.filter-active-badge { background: #eff6ff; border: 1px solid #bfdbfe; padding: 0.125rem 0.6rem; border-radius: 8px; font-size: 0.7rem; font-weight: 600; color: #1e40af; }
.header-actions { display: flex; gap: 0.5rem; }

/* ── Buttons ─────────────────────────────────────── */
.btn-primary {
  display: flex; align-items: center; gap: 0.4rem;
  background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;
  border: none; padding: 0.5rem 1.25rem; border-radius: 8px;
  font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,0.35); }
.btn-primary:disabled { opacity: 0.55; cursor: not-allowed; transform: none; }

.btn-outline {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.45rem 0.9rem; background: white; border: 1px solid #e2e8f0;
  color: #475569; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-outline:hover:not(:disabled) { background: #f8fafc; border-color: #cbd5e1; }
.btn-outline:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-outline.export { color: #059669; border-color: #bbf7d0; background: #f0fdf4; }
.btn-outline.export:hover { background: #dcfce7; }
.btn-outline.danger { color: #ef4444; border-color: #fecaca; background: #fff5f5; }
.btn-outline.danger:hover { background: #fee2e2; }

.btn-generate { width: 100%; justify-content: center; padding: 0.6rem 1rem; margin-top: auto; }

.btn-clear {
  display: flex; align-items: center; gap: 0.35rem;
  padding: 0.35rem 0.875rem; background: #fff5f5; border: 1px solid #fecaca;
  color: #ef4444; border-radius: 8px; font-size: 0.78rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-clear:hover { background: #fee2e2; }

/* ── Dashboard Layout ────────────────────────────── */
.dashboard-content { display: flex; flex-direction: column; gap: 1.5rem; }

.metrics-section,
.table-section {
  background: white; border-radius: 16px; padding: 1.5rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;
}

h2 { font-size: 1.1rem; font-weight: 600; margin: 0 0 1.25rem 0; color: #334155; }

/* ── Metrics ─────────────────────────────────────── */
.metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; }
.metric-card { padding: 1.25rem; background: #f8fafc; border-radius: 12px; display: flex; flex-direction: column; align-items: center; text-align: center; border: 1px solid #e2e8f0; position: relative; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; }
.metric-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px -4px rgba(0,0,0,0.08); border-color: var(--accent); }
.metric-card::before { display: none; }
.metric-icon-wrap { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem; }
.metric-value { font-size: 1.8rem; font-weight: 800; color: #0f172a; line-height: 1.1; margin-bottom: 0.25rem; }
.metric-label { font-size: 0.78rem; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.04em; }

/* ── Controls Bar ────────────────────────────────── */
.controls-bar {
  display: flex; justify-content: space-between; align-items: flex-end;
  margin-bottom: 0.875rem; gap: 1rem; flex-wrap: wrap;
}
.filters-row { display: flex; gap: 0.875rem; flex-wrap: wrap; align-items: flex-end; }
.controls-right { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }

.filter-group { display: flex; flex-direction: column; gap: 0.3rem; }
.filter-group label { font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }

.filter-select {
  padding: 0.45rem 0.875rem; border: 1px solid #e2e8f0; border-radius: 8px;
  background: #f8fafc; color: #334155; font-size: 0.875rem; font-weight: 500;
  cursor: pointer; appearance: none; transition: all 0.2s; font-family: inherit; min-width: 160px;
}
input.filter-select { min-width: 145px; appearance: auto; }
.filter-select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

/* Active filter tags */
.active-tags { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 1.25rem; }
.filter-tag-pill {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.25rem 0.65rem; background: #eff6ff; border: 1px solid #bfdbfe;
  color: #1e40af; border-radius: 9999px; font-size: 0.72rem; font-weight: 600;
}
.tag-x { background: none; border: none; color: #6b7280; cursor: pointer; font-size: 1rem; line-height: 1; padding: 0; margin-left: 0.1rem; }
.tag-x:hover { color: #ef4444; }

/* ── Report Type Selector ────────────────────────── */
.report-type-wrap { margin-bottom: 1.5rem; }
.sub-heading { font-size: 0.85rem; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.04em; margin: 0 0 0.875rem 0; }
.report-type-grid { display: flex; gap: 0.625rem; flex-wrap: wrap; }
.type-btn {
  display: flex; flex-direction: column; align-items: center; gap: 0.35rem;
  padding: 0.875rem 1.25rem; border: 1.5px solid #e2e8f0;
  border-radius: 10px; background: #f8fafc; cursor: pointer; transition: all 0.18s;
  min-width: 110px; font-family: inherit;
}
.type-btn:hover { border-color: #a5b4fc; background: #eff6ff; }
.type-btn.active { border-color: #6366f1; background: linear-gradient(135deg, #eff6ff, #e0e7ff); box-shadow: 0 2px 8px rgba(99,102,241,0.2); }
.type-icon  { font-size: 1.5rem; }
.type-label { font-size: 0.75rem; font-weight: 700; color: #334155; text-align: center; }

/* ── Report Form ─────────────────────────────────── */
.report-form-wrap {
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem;
}
.report-form-head { margin-bottom: 1rem; padding-bottom: 0.875rem; border-bottom: 1px solid #e2e8f0; }
.report-form-head h3 { margin: 0 0 0.25rem 0; font-size: 1rem; font-weight: 700; color: #1e293b; }
.form-desc { margin: 0; font-size: 0.8rem; color: #64748b; }

.report-form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(155px, 1fr));
  gap: 0.875rem;
  align-items: end;
}
.form-generate-col { display: flex; flex-direction: column; justify-content: flex-end; }

.applied-notice {
  display: flex; align-items: center; gap: 0.5rem; margin-top: 0.875rem;
  padding: 0.6rem 0.875rem; background: #eff6ff; border: 1px solid #bfdbfe;
  border-radius: 8px; font-size: 0.78rem; color: #3730a3; font-weight: 500;
}
.notice-tag { background: white; border: 1px solid #bfdbfe; color: #3730a3; padding: 0.1rem 0.4rem; border-radius: 4px; font-weight: 700; font-size: 0.7rem; }

/* ── Reports List ────────────────────────────────── */
.reports-list { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.25rem; }

.report-row {
  display: flex; align-items: center; gap: 1rem;
  padding: 1rem 1.25rem; background: #f8fafc; border: 1px solid #e2e8f0;
  border-radius: 10px; transition: all 0.15s;
}
.report-row:hover { background: #f0f4ff; border-color: #a5b4fc; }

.report-row-icon { font-size: 1.75rem; flex-shrink: 0; }
.report-row-info { flex: 1; min-width: 0; }
.report-row-title  { font-size: 0.9rem; font-weight: 700; color: #1e293b; }
.report-row-period { font-size: 0.78rem; color: #6366f1; font-weight: 600; margin-top: 0.1rem; }
.report-row-date   { font-size: 0.72rem; color: #94a3b8; margin-top: 0.1rem; }
.report-row-tags   { display: flex; flex-wrap: wrap; gap: 0.3rem; margin-top: 0.35rem; }

.mini-tag {
  display: inline-block; padding: 0.1rem 0.45rem;
  background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;
  border-radius: 9999px; font-size: 0.65rem; font-weight: 600;
}

.report-row-actions { display: flex; gap: 0.4rem; flex-shrink: 0; }

.action-btn {
  display: inline-flex; align-items: center; gap: 0.3rem;
  padding: 0.35rem 0.7rem; border: 1px solid #e2e8f0; background: white;
  color: #64748b; border-radius: 6px; font-size: 0.72rem; font-weight: 600;
  cursor: pointer; transition: all 0.15s; white-space: nowrap; font-family: inherit;
}
.action-btn:hover  { background: #eff6ff; color: #4f46e5; border-color: #a5b4fc; }
.action-btn.export:hover { background: #f0fdf4; color: #059669; border-color: #bbf7d0; }
.action-btn.view:hover   { background: #fafafa; color: #1e293b; border-color: #94a3b8; }

/* ── Pagination ──────────────────────────────────── */
.pagination-bar { display: flex; justify-content: space-between; align-items: center; padding-top: 1rem; border-top: 1px solid #f1f5f9; }
.pagination-info { font-size: 0.82rem; color: #64748b; }
.pagination-info strong { color: #1e293b; font-weight: 700; }
.pagination-controls { display: flex; gap: 0.5rem; }
.page-btn { display: flex; align-items: center; gap: 0.3rem; padding: 0.35rem 0.875rem; background: white; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.78rem; font-weight: 600; color: #475569; cursor: pointer; transition: all 0.15s; font-family: inherit; }
.page-btn:hover:not(:disabled) { border-color: #a5b4fc; color: #4f46e5; background: #eff6ff; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }

/* ── Preview Section ─────────────────────────────── */
.preview-header-row { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e2e8f0; flex-wrap: wrap; }
.preview-header-row h2 { margin: 0 0 0.35rem; }
.preview-tags  { display: flex; gap: 0.35rem; flex-wrap: wrap; }
.preview-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; flex-shrink: 0; }

.preview-content { display: flex; flex-direction: column; gap: 1.5rem; }

/* Preview Stats Strip */
.preview-stats {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem;
}
.preview-stat {
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
  padding: 0.875rem 1rem; display: flex; flex-direction: column; gap: 0.3rem;
}
.preview-stat small  { font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; }
.preview-stat strong { font-size: 1rem; font-weight: 700; color: #1e293b; }

/* Breakdown Cards */
.breakdown-section h4 { font-size: 0.85rem; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.04em; margin: 0 0 0.75rem; }
.breakdown-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.875rem; }
.breakdown-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1rem; position: relative; overflow: hidden; }
.breakdown-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; }
.breakdown-card.green::before { background: #10b981; }
.breakdown-card.red::before   { background: #ef4444; }
.bc-head   { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem; gap: 0.5rem; }
.bc-name   { font-size: 0.82rem; font-weight: 700; color: #1e293b; }
.bc-badge  { font-size: 0.62rem; font-weight: 700; text-transform: uppercase; padding: 0.1rem 0.4rem; border-radius: 9999px; }
.bc-amount { font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-bottom: 0.2rem; }
.bc-sub    { font-size: 0.72rem; color: #94a3b8; }

/* Badge type colors */
.type-basic     { background: #dbeafe; color: #1e40af; }
.type-allowance { background: #d1fae5; color: #065f46; }
.type-bonus     { background: #fef3c7; color: #92400e; }
.type-overtime  { background: #e0e7ff; color: #4f46e5; }
.type-commission{ background: #fce7f3; color: #9f1239; }
.type-tax       { background: #fee2e2; color: #991b1b; }
.type-statutory { background: #fef3c7; color: #92400e; }
.type-pension   { background: #dbeafe; color: #1e40af; }
.type-health    { background: #d1fae5; color: #065f46; }
.type-voluntary { background: #fce7f3; color: #9f1239; }
.type-loan      { background: #e0e7ff; color: #4f46e5; }
.type-other     { background: #f1f5f9; color: #475569; }

/* Preview Table */
h4 { font-size: 0.85rem; font-weight: 700; color: #334155; text-transform: uppercase; letter-spacing: 0.04em; margin: 0 0 0.75rem; }

.table-container { border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0; }
.preview-grid { display: grid; padding: 0.75rem 1rem; align-items: center; gap: 0.75rem; font-size: 0.82rem; }
.payroll-grid { grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr 1fr; }
.list-header  { background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 0.68rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
.list-row     { background: white; border-bottom: 1px solid #f1f5f9; }
.list-row:last-child { border-bottom: none; }

.text-right   { text-align: right; }
.text-muted   { color: #94a3b8; }
.text-success { color: #10b981; }
.text-danger  { color: #ef4444; }
.text-amber   { color: #f59e0b; }
.fw-600 { font-weight: 600; }
.fw-700 { font-weight: 700; }
.font-mono { font-family: 'SFMono-Regular', Consolas, monospace; }

.more-records { text-align: center; padding: 0.875rem; color: #94a3b8; font-size: 0.78rem; background: #f8fafc; border-top: 1px solid #e2e8f0; }

/* Net Pay Style Card */
.net-pay-card {
  background: linear-gradient(135deg, #001f5b 0%, #0040c1 100%);
  color: white; padding: 1.5rem; border-radius: 12px;
  display: flex; justify-content: space-between; align-items: center;
  position: relative; overflow: hidden; margin-top: 0.5rem;
}
.net-label { font-size: 0.68rem; opacity: 0.8; letter-spacing: 0.12em; font-weight: 600; margin-bottom: 0.35rem; }
.net-value { font-size: 1rem; font-weight: 700; opacity: 0.95; }
.net-bg    { position: absolute; right: -10px; bottom: -20px; font-size: 4.5rem; font-weight: 900; opacity: 0.07; letter-spacing: -0.05em; }

/* ── Empty & Spinner ─────────────────────────────── */
.empty-state { text-align: center; padding: 4rem 2rem; display: flex; flex-direction: column; align-items: center; gap: 0.875rem; color: #94a3b8; }
.empty-state p { margin: 0; font-size: 0.875rem; color: #64748b; }
.spinner { width: 40px; height: 40px; border: 3px solid #e2e8f0; border-top-color: #6366f1; border-radius: 50%; animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Toast ───────────────────────────────────────── */
.toast {
  position: fixed; bottom: 2rem; right: 2rem;
  background: white; padding: 0.875rem 1.25rem; border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  display: flex; align-items: center; gap: 0.65rem;
  z-index: 200; font-size: 0.875rem; font-weight: 500;
  border-left: 4px solid #10b981;
}
.toast.error   { border-left-color: #ef4444; }
.toast.success svg { color: #10b981; }
.toast.error   svg { color: #ef4444; }

/* ── Transitions ─────────────────────────────────── */
.toast-slide-enter-active, .toast-slide-leave-active { transition: all 0.3s ease; }
.toast-slide-enter-from, .toast-slide-leave-to { opacity: 0; transform: translateY(12px); }

/* ── Responsive ──────────────────────────────────── */
@media (max-width: 768px) {
  .reports-view    { padding: 1rem 1rem 2rem; }
  .user-greeting   { flex-direction: column; align-items: flex-start; }
  .header-actions  { width: 100%; }
  .metrics-grid    { grid-template-columns: repeat(2,1fr); }
  .report-form-grid{ grid-template-columns: 1fr 1fr; }
  .preview-header-row { flex-direction: column; }
  .preview-actions { width: 100%; }
  .payroll-grid    { grid-template-columns: 2fr 1fr 1fr; }
  .payroll-grid > :nth-child(n+4) { display: none; }
}
@media (max-width: 480px) {
  .metrics-grid     { grid-template-columns: 1fr; }
  .report-form-grid { grid-template-columns: 1fr; }
  .breakdown-grid   { grid-template-columns: 1fr; }
  .report-type-grid { flex-direction: column; }
  .type-btn         { width: 100%; flex-direction: row; justify-content: flex-start; min-width: 0; }
}
</style>