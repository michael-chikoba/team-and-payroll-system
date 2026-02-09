<template>
  <div v-if="show" class="modal-overlay" @click.self="closeModal">
    <div class="modal-container">
      <div class="modal-header">
        <h2>📄 Schedule Reports</h2>
        <button @click="closeModal" class="close-btn">✕</button>
      </div>

      <!-- Filters -->
      <div class="modal-filters">
        <div class="filter-row">
          <div class="filter-group">
            <label>Employee:</label>
            <select v-model="filters.employeeId" @change="applyFilters">
              <option value="">All Employees</option>
              <option v-for="emp in managedEmployees" :key="emp.id" :value="emp.id">
                {{ emp.name }}
              </option>
            </select>
          </div>

          <div class="filter-group">
            <label>Status:</label>
            <select v-model="filters.status" @change="applyFilters">
              <option value="">All Statuses</option>
              <option value="submitted">Submitted</option>
              <option value="reviewed">Reviewed</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>

          <div class="filter-group">
            <label>Date Range:</label>
            <div class="date-range">
              <input 
                type="date" 
                v-model="filters.startDate" 
                @change="applyFilters"
                placeholder="Start Date"
              >
              <span>to</span>
              <input 
                type="date" 
                v-model="filters.endDate" 
                @change="applyFilters"
                placeholder="End Date"
              >
            </div>
          </div>

          <button @click="clearFilters" class="btn-clear">Clear Filters</button>
        </div>
      </div>

      <!-- Statistics Summary -->
      <div class="stats-summary">
        <div class="stat-item">
          <span class="stat-label">Total</span>
          <span class="stat-value">{{ stats.total_reports || 0 }}</span>
        </div>
        <div class="stat-item">
          <span class="stat-label">Submitted</span>
          <span class="stat-value text-blue">{{ stats.submitted || 0 }}</span>
        </div>
        <div class="stat-item">
          <span class="stat-label">Reviewed</span>
          <span class="stat-value text-yellow">{{ stats.reviewed || 0 }}</span>
        </div>
        <div class="stat-item">
          <span class="stat-label">Approved</span>
          <span class="stat-value text-green">{{ stats.approved || 0 }}</span>
        </div>
        <div class="stat-item">
          <span class="stat-label">Rejected</span>
          <span class="stat-value text-red">{{ stats.rejected || 0 }}</span>
        </div>
      </div>

      <!-- Reports List -->
      <div class="modal-body">
        <div v-if="loading" class="loading-state">
          <div class="spinner"></div>
          <p>Loading reports...</p>
        </div>

        <div v-else-if="error" class="error-state">
          <p>{{ error }}</p>
          <button @click="fetchReports" class="btn-retry">Retry</button>
        </div>

        <div v-else-if="reports.length === 0" class="empty-state">
          <div class="empty-icon">📄</div>
          <p>No schedule reports found</p>
          <small>Reports will appear here once submitted by employees</small>
        </div>

        <div v-else class="reports-list">
          <div 
            v-for="report in reports" 
            :key="report.id" 
            class="report-card"
            @click="viewReportDetails(report)"
          >
            <div class="report-header">
              <div class="report-title-section">
                <h4>{{ report.schedule?.title || 'Untitled Schedule' }}</h4>
                <p class="report-employee">
                  <span class="icon">👤</span>
                  {{ report.employee?.name || 'Unknown Employee' }}
                </p>
              </div>
              <span :class="['status-badge', getStatusClass(report.status)]">
                {{ formatStatus(report.status) }}
              </span>
            </div>

            <div class="report-meta">
              <div class="meta-item">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>{{ formatDate(report.created_at) }}</span>
              </div>

              <div v-if="report.report_type" class="meta-item">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>{{ formatReportType(report.report_type) }}</span>
              </div>

              <div v-if="report.file_name" class="meta-item">
                <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                </svg>
                <span>{{ report.file_name }}</span>
              </div>
            </div>

            <div v-if="report.metadata" class="report-metadata">
              <div v-if="report.metadata.hours_worked" class="metadata-item">
                <span class="metadata-label">Hours:</span>
                <span class="metadata-value">{{ report.metadata.hours_worked }}h</span>
              </div>
              <div v-if="report.metadata.tasks_completed" class="metadata-item">
                <span class="metadata-label">Tasks:</span>
                <span class="metadata-value">{{ report.metadata.tasks_completed }}</span>
              </div>
            </div>

            <div v-if="report.review_comments" class="review-comments">
              <strong>Review Comments:</strong>
              <p>{{ report.review_comments }}</p>
            </div>

            <div class="report-actions">
              <button 
                @click.stop="viewReportDetails(report)" 
                class="btn-view-detail"
              >
                View Details
              </button>
              <button 
                v-if="report.status === 'submitted'"
                @click.stop="quickReview(report, 'approved')" 
                class="btn-approve"
              >
                ✓ Approve
              </button>
              <button 
                v-if="report.status === 'submitted'"
                @click.stop="quickReview(report, 'rejected')" 
                class="btn-reject"
              >
                ✕ Reject
              </button>
              <button 
                v-if="report.file_path"
                @click.stop="downloadFile(report)" 
                class="btn-download"
              >
                ⬇ Download
              </button>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.total > pagination.perPage" class="pagination">
          <button 
            @click="changePage(pagination.currentPage - 1)"
            :disabled="pagination.currentPage === 1"
            class="btn-page"
          >
            Previous
          </button>
          
          <span class="page-info">
            Page {{ pagination.currentPage }} of {{ pagination.lastPage }}
          </span>
          
          <button 
            @click="changePage(pagination.currentPage + 1)"
            :disabled="pagination.currentPage === pagination.lastPage"
            class="btn-page"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'ScheduleReportsModal',

  props: {
    show: {
      type: Boolean,
      required: true
    },
    employeeId: {
      type: [Number, String],
      default: null
    },
    managedEmployees: {
      type: Array,
      default: () => []
    }
  },

  emits: ['close', 'report-reviewed'],

  data() {
    return {
      loading: false,
      error: null,
      reports: [],
      stats: {},
      filters: {
        employeeId: this.employeeId || '',
        status: '',
        startDate: '',
        endDate: ''
      },
      pagination: {
        currentPage: 1,
        lastPage: 1,
        perPage: 10,
        total: 0
      }
    }
  },

  watch: {
    show(newVal) {
      if (newVal) {
        this.filters.employeeId = this.employeeId || ''
        this.fetchReports()
      }
    },
    employeeId(newVal) {
      this.filters.employeeId = newVal || ''
    }
  },

  methods: {
    async fetchReports() {
      this.loading = true
      this.error = null

      try {
        const params = {
          employee_id: this.filters.employeeId || null,
          status: this.filters.status || null,
          start_date: this.filters.startDate || null,
          end_date: this.filters.endDate || null,
          page: this.pagination.currentPage,
          per_page: this.pagination.perPage,
          sort_by: 'created_at',
          sort_order: 'desc'
        }

        const response = await axios.get('/api/schedule-reports', { params })

        console.log('Schedule reports response:', response.data)

        this.reports = response.data.reports?.data || []
        this.stats = response.data.stats || {}

        // Update pagination
        if (response.data.reports) {
          this.pagination = {
            currentPage: response.data.reports.current_page || 1,
            lastPage: response.data.reports.last_page || 1,
            perPage: response.data.reports.per_page || 10,
            total: response.data.reports.total || 0
          }
        }
      } catch (err) {
        console.error('Error fetching reports:', err)
        this.error = 'Failed to load reports. Please try again.'
      } finally {
        this.loading = false
      }
    },

    applyFilters() {
      this.pagination.currentPage = 1
      this.fetchReports()
    },

    clearFilters() {
      this.filters = {
        employeeId: '',
        status: '',
        startDate: '',
        endDate: ''
      }
      this.applyFilters()
    },

    changePage(page) {
      if (page >= 1 && page <= this.pagination.lastPage) {
        this.pagination.currentPage = page
        this.fetchReports()
      }
    },

    viewReportDetails(report) {
      this.$emit('report-reviewed', report)
      // Could also open a detailed view modal
    },

    async quickReview(report, status) {
      try {
        await axios.put(`/api/schedule-reports/${report.id}/review`, {
          status,
          review_comments: status === 'approved' ? 'Quick approved' : 'Quick rejected'
        })

        this.$notify({
          type: 'success',
          title: 'Success',
          text: `Report ${status} successfully!`
        })

        this.fetchReports()
        this.$emit('report-reviewed')
      } catch (error) {
        console.error('Error reviewing report:', error)
        this.$notify({
          type: 'error',
          title: 'Error',
          text: 'Failed to review report.'
        })
      }
    },

    async downloadFile(report) {
      try {
        const response = await axios.get(`/api/schedule-reports/${report.id}/download`, {
          responseType: 'blob'
        })

        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', report.file_name || 'report.pdf')
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
      } catch (error) {
        console.error('Error downloading file:', error)
        this.$notify({
          type: 'error',
          title: 'Error',
          text: 'Failed to download file.'
        })
      }
    },

    closeModal() {
      this.$emit('close')
    },

    getStatusClass(status) {
      const classes = {
        submitted: 'status-submitted',
        reviewed: 'status-reviewed',
        approved: 'status-approved',
        rejected: 'status-rejected'
      }
      return classes[status] || 'status-submitted'
    },

    formatStatus(status) {
      return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown'
    },

    formatReportType(type) {
      const types = {
        text: 'Text Report',
        file: 'File Upload',
        both: 'Text & File'
      }
      return types[type] || type
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
    }
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-container {
  background: white;
  border-radius: 16px;
  max-width: 1200px;
  width: 100%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h2 {
  margin: 0;
  color: #2d3748;
  font-size: 1.5rem;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #718096;
  padding: 0.5rem;
  border-radius: 50%;
  transition: all 0.2s;
}

.close-btn:hover {
  background: #f7fafc;
  color: #2d3748;
}

.modal-filters {
  padding: 1.5rem 2rem;
  background: #f7fafc;
  border-bottom: 1px solid #e2e8f0;
}

.filter-row {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  align-items: flex-end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  flex: 1;
  min-width: 150px;
}

.filter-group label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #4a5568;
}

.filter-group select,
.filter-group input {
  padding: 0.5rem;
  border: 1px solid #cbd5e0;
  border-radius: 6px;
  font-size: 0.875rem;
  background: white;
}

.date-range {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.date-range input {
  flex: 1;
}

.date-range span {
  color: #718096;
  font-size: 0.875rem;
}

.btn-clear {
  padding: 0.5rem 1rem;
  background: white;
  border: 1px solid #cbd5e0;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  height: fit-content;
}

.btn-clear:hover {
  background: #f7fafc;
  border-color: #a0aec0;
}

.stats-summary {
  display: flex;
  gap: 2rem;
  padding: 1.5rem 2rem;
  background: white;
  border-bottom: 1px solid #e2e8f0;
  overflow-x: auto;
}

.stat-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.stat-label {
  font-size: 0.75rem;
  color: #718096;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
}

.text-blue { color: #3b82f6; }
.text-yellow { color: #f59e0b; }
.text-green { color: #10b981; }
.text-red { color: #ef4444; }

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem 2rem;
}

.loading-state,
.error-state,
.empty-state {
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

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.btn-retry {
  margin-top: 1rem;
  padding: 0.5rem 1rem;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.reports-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.report-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.5rem;
  cursor: pointer;
  transition: all 0.2s;
}

.report-card:hover {
  border-color: #667eea;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1);
  transform: translateY(-2px);
}

.report-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 1rem;
}

.report-title-section h4 {
  margin: 0 0 0.5rem 0;
  color: #2d3748;
  font-size: 1.125rem;
}

.report-employee {
  margin: 0;
  color: #718096;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.status-badge {
  padding: 0.375rem 0.875rem;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 700;
  white-space: nowrap;
}

.status-submitted {
  background: #dbeafe;
  color: #1e40af;
}

.status-reviewed {
  background: #fef3c7;
  color: #92400e;
}

.status-approved {
  background: #d1fae5;
  color: #065f46;
}

.status-rejected {
  background: #fee2e2;
  color: #991b1b;
}

.report-meta {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #f7fafc;
}

.meta-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #718096;
}

.meta-item .icon {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

.report-metadata {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
  padding: 0.75rem;
  background: #f7fafc;
  border-radius: 6px;
}

.metadata-item {
  display: flex;
  gap: 0.5rem;
  font-size: 0.875rem;
}

.metadata-label {
  color: #718096;
  font-weight: 600;
}

.metadata-value {
  color: #2d3748;
}

.review-comments {
  margin-bottom: 1rem;
  padding: 0.75rem;
  background: #fffbeb;
  border-left: 3px solid #f59e0b;
  border-radius: 4px;
  font-size: 0.875rem;
}

.review-comments strong {
  color: #92400e;
  display: block;
  margin-bottom: 0.25rem;
}

.review-comments p {
  margin: 0;
  color: #78350f;
}

.report-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.report-actions button {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-view-detail {
  background: #f7fafc;
  color: #4a5568;
}

.btn-view-detail:hover {
  background: #e2e8f0;
}

.btn-approve {
  background: #d1fae5;
  color: #065f46;
}

.btn-approve:hover {
  background: #a7f3d0;
}

.btn-reject {
  background: #fee2e2;
  color: #991b1b;
}

.btn-reject:hover {
  background: #fecaca;
}

.btn-download {
  background: #dbeafe;
  color: #1e40af;
}

.btn-download:hover {
  background: #bfdbfe;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 2rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

.btn-page {
  padding: 0.5rem 1rem;
  background: white;
  border: 1px solid #cbd5e0;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-page:hover:not(:disabled) {
  background: #f7fafc;
  border-color: #667eea;
  color: #667eea;
}

.btn-page:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-info {
  color: #718096;
  font-size: 0.875rem;
}

@media (max-width: 768px) {
  .modal-container {
    max-height: 95vh;
  }

  .filter-row {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-group {
    min-width: 100%;
  }

  .stats-summary {
    flex-wrap: wrap;
    gap: 1rem;
  }

  .report-header {
    flex-direction: column;
    gap: 1rem;
  }

  .report-meta {
    flex-direction: column;
    gap: 0.75rem;
  }
}
</style>