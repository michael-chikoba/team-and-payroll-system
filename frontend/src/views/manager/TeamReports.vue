<template>
  <div class="reports-management">

    <!-- ── Header Card (mirrors dashboard style) ─────── -->
    <div class="dashboard-header-card">
      <div class="header-card-accent"></div>
      <div class="user-greeting">
        <div class="avatar-section">
          <div class="avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
            </svg>
          </div>
          <div class="user-info">
            <h1 class="greeting">Schedule Reports</h1>
            <p class="subtitle">View and manage submitted schedule reports from your team</p>
            <div class="role-meta">
              <span class="role-badge">Manager View</span>
            </div>
          </div>
        </div>

        <div class="header-right">
          <div class="filter-group">
            <label for="employee-filter">Filter by Employee</label>
            <div class="select-wrap">
              <select id="employee-filter" v-model="selectedEmployeeId" @change="handleEmployeeFilter">
                <option value="">All Team Members</option>
                <option v-for="employee in managedEmployees" :key="employee.id" :value="employee.id">
                  {{ employee.name }}
                </option>
              </select>
            </div>
          </div>
          <button @click="viewScheduleReports" class="btn-primary">
            View All Reports
          </button>
        </div>
      </div>
    </div>

    <!-- ── Auth / Permission / Loading / Error guards ── -->
    <div v-if="!authStore.isAuthenticated" class="error-message">
      <strong>Authentication Required</strong> — Please log in to access schedule reports.
    </div>

    <div v-else-if="!authStore.isManager" class="error-message">
      <strong>Access Denied</strong> — You don't have permission to access this page.
    </div>

    <div v-else-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Loading schedule reports...</p>
    </div>

    <div v-else-if="error" class="error-message">
      <strong>Error</strong> — {{ error }}
      <button @click="retryFetch" class="btn-retry">Retry</button>
    </div>

    <!-- ── Dashboard Content ───────────────────────── -->
    <div v-else class="dashboard-content">

      <!-- 1. Stats Grid (compact, matches dashboard metric cards) -->
      <div class="stats-section">
        <h2>Team Overview <span class="subtitle-inline">Managing {{ managedEmployees.length }} team member{{ managedEmployees.length !== 1 ? 's' : '' }}{{ selectedEmployeeId ? ` · ${getEmployeeName(selectedEmployeeId)}` : '' }}</span></h2>
        <div class="metrics-grid">
          <div class="metric-card" style="--accent:#6366f1;">
            <div class="metric-icon-wrap" style="background:rgba(99,102,241,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div class="metric-value">{{ scheduleReportsStats.total || 0 }}</div>
            <div class="metric-label">Total Reports</div>
          </div>

          <div class="metric-card" style="--accent:#f59e0b;">
            <div class="metric-icon-wrap" style="background:rgba(245,158,11,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div class="metric-value">{{ scheduleReportsStats.submitted || 0 }}</div>
            <div class="metric-label">Pending Review</div>
          </div>

          <div class="metric-card" style="--accent:#10b981;">
            <div class="metric-icon-wrap" style="background:rgba(16,185,129,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div class="metric-value">{{ scheduleReportsStats.approved || 0 }}</div>
            <div class="metric-label">Approved</div>
          </div>

          <div class="metric-card" style="--accent:#ef4444;">
            <div class="metric-icon-wrap" style="background:rgba(239,68,68,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </div>
            <div class="metric-value">{{ scheduleReportsStats.rejected || 0 }}</div>
            <div class="metric-label">Rejected</div>
          </div>
        </div>
      </div>

      <!-- 2. Recent Submissions -->
      <div class="reports-section">
        <div class="section-header">
          <div>
            <h2>Recent Submissions <span class="section-count">{{ recentScheduleReports.length }}</span></h2>
          </div>
          <button @click="viewScheduleReports" class="btn-view-all">
            View All
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </button>
        </div>

        <!-- Status Filter Pills -->
        <div class="status-filters">
          <button :class="['filter-pill', { active: statusFilter === '' }]" @click="statusFilter = ''; fetchScheduleReports()">All</button>
          <button :class="['filter-pill submitted', { active: statusFilter === 'submitted' }]" @click="statusFilter = 'submitted'; fetchScheduleReports()">Submitted ({{ scheduleReportsStats.submitted }})</button>
          <button :class="['filter-pill reviewed', { active: statusFilter === 'reviewed' }]" @click="statusFilter = 'reviewed'; fetchScheduleReports()">Reviewed ({{ scheduleReportsStats.reviewed }})</button>
          <button :class="['filter-pill approved', { active: statusFilter === 'approved' }]" @click="statusFilter = 'approved'; fetchScheduleReports()">Approved ({{ scheduleReportsStats.approved }})</button>
          <button :class="['filter-pill rejected', { active: statusFilter === 'rejected' }]" @click="statusFilter = 'rejected'; fetchScheduleReports()">Rejected ({{ scheduleReportsStats.rejected }})</button>
        </div>

        <!-- Reports Table-style List -->
        <div v-if="recentScheduleReports.length > 0" class="reports-list-container">
          <div class="list-header">
            <div class="col-title">Schedule / Employee</div>
            <div class="col-type">Type</div>
            <div class="col-date">Submitted</div>
            <div class="col-status">Status</div>
            <div class="col-actions">Actions</div>
          </div>

          <div
            v-for="report in recentScheduleReports"
            :key="report.id"
            class="list-row"
            @click="viewReportDetails(report)"
          >
            <div class="col-title">
              <div class="report-name-wrap">
                <span class="report-title">{{ getReportScheduleTitle(report) }}</span>
                <span class="report-employee">{{ getReportEmployeeName(report) }}</span>
              </div>
            </div>
            <div class="col-type">
              <span class="type-badge">{{ formatReportType(report.report_type) }}</span>
            </div>
            <div class="col-date">{{ formatDate(report.created_at) }}</div>
            <div class="col-status">
              <span :class="['status-badge', getReportStatusClass(report.status)]">{{ formatReportStatus(report.status) }}</span>
            </div>
            <div class="col-actions" @click.stop>
              <button @click="viewReportDetails(report)" class="action-btn view">Details</button>
              <button v-if="report.status === 'submitted'" @click="quickReview(report, 'approved')" class="action-btn approve">✓</button>
              <button v-if="report.status === 'submitted'" @click="quickReview(report, 'rejected')" class="action-btn reject">✕</button>
            </div>
          </div>
        </div>

        <div v-else class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
          <p v-if="statusFilter">No {{ statusFilter }} reports found{{ selectedEmployeeId ? ' for this employee' : '' }}.</p>
          <p v-else>No schedule reports have been submitted yet{{ selectedEmployeeId ? ' by this employee' : '' }}.</p>
          <button v-if="statusFilter || selectedEmployeeId" @click="clearFilters" class="btn-secondary">Clear Filters</button>
        </div>
      </div>

      <!-- 3. Team Members -->
      <div class="team-section">
        <h2>Team Members <span class="section-count">{{ managedEmployees.length }}</span></h2>

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
            <div class="member-avatar">{{ getInitials(employee.name) }}</div>
            <div class="member-info">
              <span class="member-name">{{ employee.name }}</span>
              <span class="member-position">{{ employee.position || 'Employee' }}</span>
            </div>
          </div>

          <div v-if="managedEmployees.length > 8" class="more-members">
            +{{ managedEmployees.length - 8 }} more
          </div>
        </div>
      </div>

    </div>

    <!-- Modals (unchanged) -->
    <ScheduleReportsModal
      :show="showScheduleReportsModal"
      :employee-id="selectedEmployeeId"
      :managed-employees="managedEmployees"
      @close="showScheduleReportsModal = false"
      @report-reviewed="handleReportReviewed"
    />
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
  components: { ScheduleReportsModal, ReportDetailsModal },
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
      showScheduleReportsModal: false,
      showReportDetailsModal: false,
      selectedReport: null,
      recentScheduleReports: [],
      scheduleReportsStats: { total: 0, submitted: 0, reviewed: 0, approved: 0, rejected: 0 }
    }
  },
  mounted() { this.initializeComponent() },
  methods: {
    initializeComponent() {
      if (!this.authStore.isAuthenticated) { this.error = 'Please log in to access schedule reports.'; return }
      if (!this.authStore.isManager) { this.error = 'You do not have permission to access this page.'; return }
      this.fetchManagerData()
    },
    handleEmployeeFilter() { this.fetchScheduleReports() },
    filterByEmployee(employeeId) { this.selectedEmployeeId = employeeId; this.fetchScheduleReports() },
    clearEmployeeFilter() { this.selectedEmployeeId = null; this.fetchScheduleReports() },
    clearFilters() { this.selectedEmployeeId = null; this.statusFilter = ''; this.fetchScheduleReports() },
    getEmployeeName(employeeId) {
      const employee = this.managedEmployees.find(emp => emp.id === employeeId)
      return employee ? employee.name : 'Unknown'
    },
    getReportEmployeeName(report) {
      if (report.employee?.name) return report.employee.name
      if (report.employee?.first_name || report.employee?.last_name) return `${report.employee.first_name || ''} ${report.employee.last_name || ''}`.trim()
      if (report.employee_id) { const emp = this.managedEmployees.find(e => e.id === report.employee_id); if (emp) return emp.name }
      return 'Unknown Employee'
    },
    getReportScheduleTitle(report) { return report.schedule?.title || 'Untitled Schedule' },
    hasReportMetadata(report) { return report.metadata && (report.metadata.hours_worked || report.metadata.tasks_completed) },
    async fetchManagerData() {
      this.loading = true; this.error = null
      try {
        const employeesRes = await axios.get('/api/manager/employees')
        this.managedEmployees = this.processEmployeesData(employeesRes.data)
        await this.fetchScheduleReports()
      } catch (err) { this.handleApiError(err) } finally { this.loading = false }
    },
    async fetchScheduleReports() {
      try {
        const params = { employee_id: this.selectedEmployeeId || null, status: this.statusFilter || null, per_page: 10, sort_by: 'created_at', sort_order: 'desc' }
        const response = await axios.get('/api/schedule-reports', { params })
        const reports = response.data.reports?.data || []
        this.recentScheduleReports = reports.map(report => {
          if (report.employee && !report.employee.name && (report.employee.first_name || report.employee.last_name)) {
            report.employee.name = `${report.employee.first_name || ''} ${report.employee.last_name || ''}`.trim()
          }
          return report
        })
        this.scheduleReportsStats = response.data.stats || { total: 0, submitted: 0, reviewed: 0, approved: 0, rejected: 0 }
      } catch (error) {
        this.$notify({ type: 'error', title: 'Error', text: 'Failed to load schedule reports.' })
      }
    },
    viewScheduleReports() { this.showScheduleReportsModal = true },
    viewReportDetails(report) { this.selectedReport = report; this.showReportDetailsModal = true },
    async quickReview(report, status) {
      try {
        await axios.put(`/api/schedule-reports/${report.id}/review`, { status, review_comments: status === 'approved' ? 'Quick approved' : 'Requires revision' })
        this.$notify({ type: 'success', title: 'Success', text: `Report ${status} successfully!` })
        await this.fetchScheduleReports()
      } catch (error) { this.$notify({ type: 'error', title: 'Error', text: 'Failed to review report.' }) }
    },
    async handleReviewReport(reportId, status, comments) {
      try {
        await axios.put(`/api/schedule-reports/${reportId}/review`, { status, review_comments: comments })
        this.$notify({ type: 'success', title: 'Success', text: 'Report reviewed successfully!' })
        await this.fetchScheduleReports()
        this.showReportDetailsModal = false
      } catch (error) { this.$notify({ type: 'error', title: 'Error', text: 'Failed to review report.' }) }
    },
    async handleDownloadReport(reportId) {
      try {
        const response = await axios.get(`/api/schedule-reports/${reportId}/download`, { responseType: 'blob' })
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        const cd = response.headers['content-disposition']
        let filename = 'report.pdf'
        if (cd) { const m = cd.match(/filename="?(.+)"?/); if (m) filename = m[1] }
        link.setAttribute('download', filename)
        document.body.appendChild(link); link.click(); link.remove()
        window.URL.revokeObjectURL(url)
      } catch (error) { this.$notify({ type: 'error', title: 'Error', text: 'Failed to download report file.' }) }
    },
    handleReportReviewed() { this.fetchScheduleReports() },
    getReportStatusClass(status) {
      return { submitted: 'status-submitted', reviewed: 'status-reviewed', approved: 'status-approved', rejected: 'status-rejected' }[status] || 'status-submitted'
    },
    formatReportStatus(status) { return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown' },
    formatReportType(type) { return { text: 'Text', file: 'File', both: 'Text & File' }[type] || type },
    processEmployeesData(data) {
      let employees = Array.isArray(data) ? data : (data.data || data.employees || [])
      return employees.map(emp => {
        let fullName = emp.name || (emp.first_name || emp.last_name ? `${emp.first_name || ''} ${emp.last_name || ''}`.trim() : '') || (emp.user ? `${emp.user.first_name || ''} ${emp.user.last_name || ''}`.trim() : '') || emp.email || `Employee #${emp.id}`
        return { id: emp.id, name: fullName, position: emp.position || emp.job_title || 'Employee', department: emp.department || 'General', email: emp.email || '' }
      })
    },
    getInitials(name) { return name ? name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) : '??' },
    retryFetch() { this.retryCount++; if (this.retryCount <= 3) this.fetchManagerData(); else this.error = 'Max retries exceeded. Please check your network or server.' },
    handleApiError(err) {
      if (err.code === 'ERR_NETWORK' || err.message.includes('Network Error')) this.error = 'Network error: Please check your connection and try again.'
      else if (err.response?.status === 401) { this.error = 'Your session has expired. Please log in again.'; this.authStore.clearAuth(); this.$router.push({ name: 'login' }) }
      else if (err.response?.status === 403) this.error = 'You do not have permission to perform this action.'
      else if (err.response?.status === 404) this.error = 'Manager endpoints not found. Please check the API routes.'
      else this.error = err.response?.data?.message || 'An unexpected error occurred.'
    },
    formatDate(date) {
      if (!date) return 'N/A'
      try { return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) } catch { return 'Invalid Date' }
    }
  }
}
</script>

<style scoped>
/* ── Base ──────────────────────────────────────────── */
.reports-management {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
  padding: 1.5rem 2rem 3rem;
  max-width: 1200px;
  margin: 0 auto;
}

/* ── Header Card ─────────────────────────────────── */
.dashboard-header-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem 1.75rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0;
  margin-bottom: 1.25rem;
  position: relative;
  overflow: hidden;
}

.header-card-accent {
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  
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
  box-shadow: 0 4px 12px rgba(59,130,246,0.25);
  flex-shrink: 0;
}

.user-info { display: flex; flex-direction: column; gap: 0.2rem; }

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

.role-meta { margin-top: 0.125rem; }

.role-badge {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  padding: 0.125rem 0.6rem;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 600;
  color: #1d4ed8;
}

/* Header right: filter + button */
.header-right {
  display: flex;
  align-items: flex-end;
  gap: 0.75rem;
  flex-shrink: 0;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.filter-group label {
  font-size: 0.7rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.select-wrap select {
  padding: 0.45rem 0.875rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
  color: #334155;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  min-width: 200px;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.75rem center;
  padding-right: 2rem;
}

.select-wrap select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

/* ── Buttons ─────────────────────────────────────── */
.btn-primary {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
  border: none;
  padding: 0.5rem 1.25rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  white-space: nowrap;
}
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,0.35); }

.btn-view-all {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.4rem 0.9rem;
  background: white;
  border: 1px solid #e2e8f0;
  color: #6366f1;
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-view-all:hover { background: #f5f3ff; border-color: #c4b5fd; }

.btn-secondary {
  padding: 0.5rem 1rem;
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  margin-top: 0.5rem;
}
.btn-secondary:hover { background: #e2e8f0; }

.btn-retry {
  display: inline-block;
  margin-top: 0.75rem;
  padding: 0.4rem 1rem;
  background: #991b1b;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
}

/* ── States ──────────────────────────────────────── */
.loading {
  text-align: center;
  padding: 4rem 1rem;
  color: #64748b;
}

.spinner {
  width: 40px; height: 40px;
  border: 3px solid #e2e8f0;
  border-top-color: #6366f1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin { to { transform: rotate(360deg); } }

.error-message {
  background: #fee2e2;
  color: #991b1b;
  padding: 1.25rem 1.5rem;
  border-radius: 10px;
  margin: 1rem 0;
  font-size: 0.9rem;
}

.empty-state {
  text-align: center;
  padding: 3rem 2rem;
  color: #94a3b8;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}
.empty-state p { margin: 0; font-size: 0.875rem; color: #64748b; }

/* ── Dashboard Content ───────────────────────────── */
.dashboard-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* ── Section card base ───────────────────────────── */
.stats-section,
.reports-section,
.team-section {
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
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.subtitle-inline {
  font-size: 0.8rem;
  font-weight: 400;
  color: #64748b;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
  border: none;
  padding-bottom: 0;
}

.section-header h2 { margin-bottom: 0; }

.section-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: #f1f5f9;
  border-radius: 20px;
  padding: 0.1rem 0.6rem;
  font-size: 0.75rem;
  font-weight: 700;
  color: #64748b;
}

/* ── 1. Stats / Metrics ──────────────────────────── */
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
  width: 40px; height: 40px;
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
  font-size: 0.78rem;
  color: #64748b;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

/* ── 2. Reports List ─────────────────────────────── */
.status-filters {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.25rem;
  flex-wrap: wrap;
}

.filter-pill {
  padding: 0.35rem 0.875rem;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #64748b;
  border-radius: 20px;
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.filter-pill:hover { border-color: #cbd5e1; background: #f1f5f9; }
.filter-pill.active { border-color: #6366f1; background: #6366f1; color: white; }
.filter-pill.submitted.active { border-color: #f59e0b; background: #f59e0b; }
.filter-pill.reviewed.active  { border-color: #8b5cf6; background: #8b5cf6; }
.filter-pill.approved.active  { border-color: #10b981; background: #10b981; }
.filter-pill.rejected.active  { border-color: #ef4444; background: #ef4444; }

.reports-list-container {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
}

.list-header,
.list-row {
  display: grid;
  grid-template-columns: 2.5fr 1fr 1.2fr 1fr 1.2fr;
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
  cursor: pointer;
  transition: background 0.15s;
}
.list-row:last-child { border-bottom: none; }
.list-row:hover { background: #f8fafc; }

.report-name-wrap { display: flex; flex-direction: column; gap: 0.2rem; }
.report-title { font-weight: 600; color: #1e293b; font-size: 0.875rem; }
.report-employee { font-size: 0.72rem; color: #64748b; }

.type-badge {
  padding: 0.2rem 0.6rem;
  background: #f1f5f9;
  color: #475569;
  border-radius: 6px;
  font-size: 0.72rem;
  font-weight: 600;
}

.col-date { color: #475569; font-size: 0.82rem; }

.status-badge {
  padding: 0.25rem 0.65rem;
  border-radius: 20px;
  font-size: 0.72rem;
  font-weight: 700;
}
.status-submitted { background: #fef3c7; color: #92400e; }
.status-reviewed  { background: #e0e7ff; color: #3730a3; }
.status-approved  { background: #d1fae5; color: #065f46; }
.status-rejected  { background: #fee2e2; color: #991b1b; }

.col-actions { display: flex; gap: 0.4rem; align-items: center; }

.action-btn {
  padding: 0.3rem 0.65rem;
  border: none;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
}
.action-btn.view    { background: #f1f5f9; color: #475569; }
.action-btn.view:hover { background: #e2e8f0; }
.action-btn.approve { background: #d1fae5; color: #065f46; }
.action-btn.approve:hover { background: #a7f3d0; }
.action-btn.reject  { background: #fee2e2; color: #991b1b; }
.action-btn.reject:hover { background: #fecaca; }

/* ── 3. Team Members ─────────────────────────────── */
.team-members-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 0.875rem;
}

.team-member-card {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  padding: 0.875rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
  background: #f8fafc;
}
.team-member-card:hover {
  border-color: #a5b4fc;
  background: white;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(99,102,241,0.08);
}

.member-avatar {
  width: 38px; height: 38px;
  border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: white;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700;
  font-size: 0.75rem;
  flex-shrink: 0;
}

.member-info { display: flex; flex-direction: column; gap: 0.15rem; min-width: 0; }
.member-name { font-weight: 600; color: #1e293b; font-size: 0.875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.member-position { font-size: 0.72rem; color: #64748b; }

.more-members {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  border: 1px dashed #e2e8f0;
  border-radius: 10px;
  color: #94a3b8;
  font-size: 0.82rem;
  font-weight: 600;
  background: #f8fafc;
}

/* ── Responsive ──────────────────────────────────── */
@media (max-width: 900px) {
  .list-header { display: none; }
  .list-row {
    grid-template-columns: 1fr 1fr;
    grid-template-rows: auto auto;
    grid-template-areas: "title status" "meta actions";
    gap: 0.5rem;
    padding: 1rem;
  }
  .col-title  { grid-area: title; }
  .col-status { grid-area: status; justify-self: end; }
  .col-type, .col-date { display: none; }
  .col-actions { grid-area: actions; justify-self: end; }
}

@media (max-width: 768px) {
  .reports-management { padding: 1rem; }
  .user-greeting { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .header-right { flex-direction: column; align-items: stretch; width: 100%; }
  .select-wrap select { width: 100%; }
  .btn-primary { width: 100%; justify-content: center; }
  .metrics-grid { grid-template-columns: repeat(2, 1fr); }
  .team-members-grid { grid-template-columns: 1fr; }
}

@media (max-width: 480px) {
  .metrics-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>