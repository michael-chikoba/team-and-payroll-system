<template>
  <div class="leaves-view">
    <!-- ── Header Card ─────────────────────────────── -->
    <div class="dashboard-header-card">
      <div class="header-card-accent"></div>
      <div class="user-greeting">
        <div class="avatar-section">
          <div class="avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
              <circle cx="12" cy="12" r="3"></circle>
            </svg>
          </div>
          <div class="user-info">
            <h1 class="greeting">Leave Approvals</h1>
            <p class="subtitle">Manager Approval Dashboard</p>
            <div class="role-meta">
              <span class="role-badge">Manager View</span>
            </div>
          </div>
        </div>
        <div class="header-actions">
          <button @click="fetchLeaveRequests" class="btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
            Refresh
          </button>
        </div>
      </div>
    </div>

    <div class="dashboard-content">
      <!-- Permission / Auth Check -->
      <div v-if="!authStore.isAuthenticated || !authStore.isManager" class="metrics-section">
        <div class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          <p style="font-size:1.1rem;color:#1e293b;">
            {{ authStore.isAuthenticated ? "You don't have permission to access this page." : "Please log in to access leave approvals." }}
          </p>
          <button v-if="!authStore.isAuthenticated" @click="$router.push('/login')" class="btn-primary">Go to Login</button>
        </div>
      </div>

      <!-- Main Manager Content -->
      <div v-else>
        <!-- ── Metrics ──────────────────────────────── -->
        <div class="metrics-section" v-if="!loading && leaveRequests.length > 0">
          <h2>Summary</h2>
          <div class="metrics-grid">
            <div class="metric-card" style="--accent:#ef4444;">
              <div class="metric-icon-wrap" style="background:rgba(239,68,68,0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
              </div>
              <div class="metric-value">{{ filteredRequests.length }}</div>
              <div class="metric-label">Pending</div>
            </div>
            <div class="metric-card" style="--accent:#f59e0b;">
              <div class="metric-icon-wrap" style="background:rgba(245,158,11,0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
              </div>
              <div class="metric-value">{{ totalDays }}</div>
              <div class="metric-label">Total Days</div>
            </div>
            <div class="metric-card" style="--accent:#10b981;">
              <div class="metric-icon-wrap" style="background:rgba(16,185,129,0.1);">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
              </div>
              <div class="metric-value">{{ avgDuration }}</div>
              <div class="metric-label">Avg Days</div>
            </div>
          </div>
        </div>

        <!-- ── Table Section ──────────────────────── -->
        <div class="table-section">
          <div class="controls-bar">
            <div class="filters-row">
              <div class="filter-group">
                <label>Leave Type</label>
                <select v-model="filterType" class="filter-select">
                  <option value="">All Types</option>
                  <option value="annual">Annual</option>
                  <option value="sick">Sick</option>
                  <option value="maternity">Maternity</option>
                  <option value="paternity">Paternity</option>
                  <option value="casual">Casual</option>
                  <option value="emergency">Emergency</option>
                  <option value="bereavement">Bereavement</option>
                  <option value="unpaid">Unpaid</option>
                </select>
              </div>
            </div>
            <span class="records-count" v-if="!loading">
              {{ filteredRequests.length }} pending request{{ filteredRequests.length !== 1 ? 's' : '' }}
            </span>
          </div>

          <!-- Loading -->
          <div v-if="loading" class="empty-state">
            <div class="spinner"></div>
            <p>Loading leave requests…</p>
          </div>

          <!-- Error -->
          <div v-else-if="error" class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <p>{{ error }}</p>
            <button @click="retryFetch" class="btn-primary">Try Again</button>
          </div>

          <!-- Table -->
          <div v-else>
            <div v-if="filteredRequests.length === 0" class="empty-state">
              <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.25"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
              <p>No pending leave requests</p>
              <p>Your team has no requests to review right now.</p>
            </div>

            <div v-else class="table-container">
              <div class="list-header approvals-grid">
                <div>Employee</div>
                <div>Leave Type</div>
                <div>Date Range</div>
                <div class="text-right">Duration</div>
                <div>Reason</div>
                <div class="text-right">Actions</div>
              </div>

              <div
                v-for="request in filteredRequests"
                :key="request.id"
                class="list-row approvals-grid"
              >
                <!-- Employee -->
                <div class="employee-cell">
                  <div class="employee-avatar">
                    {{ getInitials(getEmployeeName(request)) }}
                  </div>
                  <div>
                    <div class="employee-name">{{ getEmployeeName(request) }}</div>
                    <div class="employee-id">{{ request.employee?.employee_id || request.employee?.id ? 'EMP-' + request.employee?.id : '—' }}</div>
                  </div>
                </div>

                <!-- Leave Type -->
                <div class="leave-type-cell">
                  <span class="leave-dot" :style="{ background: getLeaveTypeColor(request.type || request.leave_type) }"></span>
                  <span class="leave-type-name">{{ formatLeaveType(request.type || request.leave_type) }}</span>
                </div>

                <!-- Date Range -->
                <div>
                  <div class="date-range-wrap">
                    <span class="date-val">{{ formatDate(request.start_date) }}</span>
                    <span class="date-sep">→</span>
                    <span class="date-val">{{ formatDate(request.end_date) }}</span>
                  </div>
                </div>

                <!-- Duration -->
                <div class="text-right">
                  <span class="duration-badge">{{ request.total_days || request.days || 0 }}d</span>
                </div>

                <!-- Reason -->
                <div class="text-muted reason-truncate">{{ truncateText(request.reason) }}</div>

                <!-- Actions -->
                <div class="action-group" @click.stop>
                  <button
                    @click="approveRequest(request)"
                    class="btn-success"
                    :disabled="processing === request.id"
                  >
                    <span v-if="processing === request.id && processingAction === 'approve'">
                      <span class="btn-spinner"></span>
                    </span>
                    <span v-else>Approve</span>
                  </button>
                  <button
                    @click="showRejectModal(request)"
                    class="btn-danger"
                    :disabled="processing === request.id"
                  >
                    Reject
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Reject Modal ───────────────────────── -->
    <transition name="modal-fade">
      <div v-if="showRejectDialog" class="modal-overlay" @click.self="closeRejectModal">
        <div class="modal-container" style="max-width: 520px;">
          <div class="modal-header">
            <div class="modal-title-wrap">
              <div class="modal-avatar" style="background:#ef4444;">✕</div>
              <div>
                <h3 class="modal-name">Reject Leave Request</h3>
                <p class="modal-position">{{ getEmployeeName(selectedRequest) }}</p>
              </div>
            </div>
            <button @click="closeRejectModal" class="modal-close">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
          </div>

          <div class="modal-body">
            <div class="modal-stats">
              <div class="modal-stat">
                <small>Leave Type</small>
                <strong>{{ formatLeaveType(selectedRequest?.type || selectedRequest?.leave_type) }}</strong>
              </div>
              <div class="modal-stat">
                <small>Duration</small>
                <strong>{{ selectedRequest?.total_days || selectedRequest?.days || 0 }} days</strong>
              </div>
              <div class="modal-stat">
                <small>From</small>
                <strong>{{ formatDate(selectedRequest?.start_date) }}</strong>
              </div>
              <div class="modal-stat">
                <small>To</small>
                <strong>{{ formatDate(selectedRequest?.end_date) }}</strong>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Manager Notes (Optional)</label>
              <textarea
                v-model="rejectNotes"
                rows="5"
                placeholder="Provide a reason for rejection..."
                class="form-textarea"
              ></textarea>
            </div>
          </div>

          <div class="modal-footer">
            <button @click="closeRejectModal" class="btn-secondary">Cancel</button>
            <button @click="confirmReject" class="btn-danger" :disabled="!!processing">
              {{ processing ? 'Rejecting...' : 'Confirm Reject' }}
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- ── Toast ─────────────────────────────────────── -->
    <transition name="toast-slide">
      <div v-if="toast.show" :class="['toast', toast.type]">
        <svg v-if="toast.type === 'success'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        <span>{{ toast.message }}</span>
      </div>
    </transition>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

export default {
  name: 'LeaveApprovals',

  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },

  data() {
    return {
      leaveRequests: [],
      filterType: '',
      loading: false,
      error: null,
      retryCount: 0,
      // processing holds the leave ID being actioned (null = idle)
      processing: null,
      processingAction: null,
      showRejectDialog: false,
      selectedRequest: null,
      rejectNotes: '',
      toast: { show: false, message: '', type: 'success' }
    }
  },

  computed: {
    filteredRequests() {
      if (!this.filterType) return this.leaveRequests
      return this.leaveRequests.filter(r => (r.type || r.leave_type) === this.filterType)
    },
    totalDays() {
      return this.leaveRequests.reduce((sum, r) => sum + (r.total_days || r.days || 0), 0)
    },
    avgDuration() {
      if (!this.leaveRequests.length) return '0'
      return (this.totalDays / this.leaveRequests.length).toFixed(1)
    }
  },

  mounted() {
    if (!this.authStore.isAuthenticated || !this.authStore.isManager) return
    this.fetchLeaveRequests()
  },

  methods: {
    // ── Data Fetching ───────────────────────────────────────────────────────

    /**
     * GET /api/manager/leaves/pending
     * Response shape: LeaveResource collection
     *   Each item has: id, type, start_date, end_date, total_days, reason,
     *                  status, manager_notes, employee { id, employee_id,
     *                  full_name, user { name } }
     */
    async fetchLeaveRequests() {
      this.loading = true
      this.error = null
      try {
        const response = await axios.get('/api/manager/leaves/pending')
        // pendingLeaves() returns a ResourceCollection → data array
        this.leaveRequests = Array.isArray(response.data)
          ? response.data
          : (response.data.data || [])
      } catch (err) {
        this.handleApiError(err, true)
      } finally {
        this.loading = false
      }
    },

    retryFetch() {
      this.retryCount++
      if (this.retryCount <= 3) {
        this.fetchLeaveRequests()
      } else {
        this.error = 'Max retries exceeded. Please refresh the page.'
      }
    },

    // ── Approve ─────────────────────────────────────────────────────────────

    /**
     * POST /api/manager/leaves/{leave}/approve
     * Handled by LeaveController@approve via ApproveLeaveRequest.
     * ApproveLeaveRequest validates: status (required), manager_notes (nullable).
     */
    async approveRequest(request) {
      if (!confirm(`Approve leave for ${this.getEmployeeName(request)}?`)) return

      this.processing = request.id
      this.processingAction = 'approve'
      try {
        await axios.post(`/api/manager/leaves/${request.id}/approve`, {
          status: 'approved',
          manager_notes: null
        })
        this.showToast('Leave request approved successfully!', 'success')
        await this.fetchLeaveRequests()
      } catch (err) {
        this.handleApiError(err)
      } finally {
        this.processing = null
        this.processingAction = null
      }
    },

    // ── Reject ──────────────────────────────────────────────────────────────

    showRejectModal(request) {
      this.selectedRequest = request
      this.rejectNotes = ''
      this.showRejectDialog = true
    },

    closeRejectModal() {
      this.showRejectDialog = false
      this.selectedRequest = null
      this.rejectNotes = ''
    },

    /**
     * POST /api/manager/leaves/{leave}/reject
     * Handled by LeaveController@reject.
     * Validates: manager_notes (nullable), reason (nullable).
     * Sets status = 'rejected' internally — no status field needed from frontend.
     */
    async confirmReject() {
      if (!this.selectedRequest) return

      this.processing = this.selectedRequest.id
      this.processingAction = 'reject'
      try {
        await axios.post(`/api/manager/leaves/${this.selectedRequest.id}/reject`, {
          manager_notes: this.rejectNotes || null
        })
        this.showToast('Leave request rejected.', 'success')
        this.closeRejectModal()
        await this.fetchLeaveRequests()
      } catch (err) {
        this.handleApiError(err)
      } finally {
        this.processing = null
        this.processingAction = null
      }
    },

    // ── Error Handling ───────────────────────────────────────────────────────

    handleApiError(err, setOnComponent = false) {
      let message = 'An unexpected error occurred.'

      if (err.code === 'ERR_NETWORK') {
        message = 'Network error. Check your connection.'
      } else if (err.response?.status === 401) {
        message = 'Session expired. Redirecting…'
        this.authStore.clearAuth()
        setTimeout(() => this.$router.push('/login'), 1500)
      } else if (err.response?.status === 403) {
        message = 'You do not have permission to perform this action.'
      } else if (err.response?.status === 422) {
        // Validation error — collect all field messages
        const errors = err.response.data?.errors
        if (errors) {
          message = Object.values(errors).flat().join(' ')
        } else {
          message = err.response.data?.message || message
        }
      } else {
        message = err.response?.data?.message || message
      }

      if (setOnComponent) {
        this.error = message
      } else {
        this.showToast(message, 'error')
      }
    },

    // ── Toast ────────────────────────────────────────────────────────────────

    showToast(message, type = 'success') {
      this.toast = { show: true, message, type }
      setTimeout(() => { this.toast.show = false }, 3500)
    },

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Resolve employee display name from various response shapes.
     * LeaveResource may return employee.full_name, employee.user.name,
     * or employee.user.first_name + last_name.
     */
    getEmployeeName(request) {
      if (!request?.employee) return '—'
      const emp = request.employee
      if (emp.full_name) return emp.full_name
      if (emp.name) return emp.name
      if (emp.user) {
        if (emp.user.name) return emp.user.name
        const first = emp.user.first_name || ''
        const last  = emp.user.last_name  || ''
        const full  = `${first} ${last}`.trim()
        if (full) return full
      }
      return `Employee #${emp.id}`
    },

    getInitials(name) {
      if (!name || name === '—') return '??'
      return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2)
    },

    formatDate(date) {
      if (!date) return '—'
      return new Date(date).toLocaleDateString('en-ZM', {
        year: 'numeric', month: 'short', day: 'numeric'
      })
    },

    formatLeaveType(type) {
      const map = {
        annual:      'Annual Leave',
        sick:        'Sick Leave',
        maternity:   'Maternity Leave',
        paternity:   'Paternity Leave',
        casual:      'Casual Leave',
        emergency:   'Emergency Leave',
        bereavement: 'Bereavement Leave',
        unpaid:      'Unpaid Leave'
      }
      return map[type] || type || '—'
    },

    getLeaveTypeColor(type) {
      const map = {
        annual:      '#3b82f6',
        sick:        '#f59e0b',
        maternity:   '#ec4899',
        paternity:   '#8b5cf6',
        casual:      '#10b981',
        emergency:   '#ef4444',
        bereavement: '#6b7280',
        unpaid:      '#94a3b8'
      }
      return map[type] || '#64748b'
    },

    truncateText(text) {
      if (!text) return '—'
      return text.length > 45 ? text.substring(0, 45) + '…' : text
    }
  }
}
</script>

<style scoped>
.leaves-view {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #f4f6f9;
  padding: 1.5rem 2rem 3rem;
  max-width: 1200px;
  margin: 0 auto;
}

/* ── Header ── */
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
.avatar-section { display: flex; align-items: center; gap: 1rem; }
.avatar {
  width: 52px; height: 52px;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  color: white;
  box-shadow: 0 4px 12px rgba(59,130,246,0.25);
  flex-shrink: 0;
}
.greeting { margin: 0; font-size: 1.375rem; font-weight: 700; color: #1e293b; }
.subtitle { margin: 0.15rem 0 0; font-size: 0.875rem; color: #64748b; }
.role-meta { margin-top: 0.4rem; }
.role-badge {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  padding: 0.125rem 0.6rem;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 600;
  color: #166534;
}

/* ── Buttons ── */
.btn-primary, .btn-outline, .btn-secondary, .btn-danger, .btn-success {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 1.25rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}
.btn-primary   { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; }
.btn-outline   { background: white; border: 1px solid #e2e8f0; color: #475569; }
.btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
.btn-danger    { background: #ef4444; color: white; }
.btn-success   { background: linear-gradient(135deg, #10b981, #34d399); color: white; }
.btn-primary:hover  { opacity: 0.9; }
.btn-danger:hover   { background: #dc2626; }
.btn-success:hover  { opacity: 0.9; }
.btn-outline:hover  { background: #f8fafc; }
button:disabled     { opacity: 0.55; cursor: not-allowed; }

/* ── Sections ── */
.metrics-section, .table-section {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0;
  padding: 1.5rem;
  margin-bottom: 1.25rem;
}
.metrics-section h2 { margin: 0 0 1rem; font-size: 1rem; font-weight: 700; color: #0f172a; }

/* ── Metrics Grid ── */
.metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1.25rem; }
.metric-card {
  padding: 1.25rem;
  background: #f8fafc;
  border-radius: 12px;
  display: flex; flex-direction: column; align-items: center; text-align: center;
  border: 1px solid #e2e8f0;
}
.metric-icon-wrap { padding: 0.6rem; border-radius: 10px; margin-bottom: 0.75rem; }
.metric-value { font-size: 1.8rem; font-weight: 800; color: #0f172a; }
.metric-label { font-size: 0.8rem; color: #64748b; font-weight: 600; margin-top: 0.2rem; }

/* ── Controls Bar ── */
.controls-bar { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.75rem; }
.filters-row { display: flex; gap: 0.875rem; flex-wrap: wrap; }
.filter-group { display: flex; flex-direction: column; gap: 0.3rem; }
.filter-group label { font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
.filter-select {
  padding: 0.45rem 2rem 0.45rem 0.875rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
  font-size: 0.875rem;
  color: #334155;
  cursor: pointer;
}
.records-count { font-size: 0.82rem; color: #64748b; font-weight: 600; }

/* ── Table ── */
.table-container { border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0; }
.approvals-grid {
  display: grid;
  grid-template-columns: 1.8fr 1.2fr 1.4fr 0.6fr 1.8fr 1.2fr;
  padding: 0.75rem 1rem;
  align-items: center;
  gap: 0.75rem;
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
  background: white;
  transition: background 0.15s;
}
.list-row:last-child { border-bottom: none; }
.list-row:hover { background: #f8fafc; }

/* ── Employee Cell ── */
.employee-cell { display: flex; align-items: center; gap: 0.75rem; }
.employee-avatar {
  width: 42px; height: 42px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 0.95rem;
  flex-shrink: 0;
}
.employee-name { font-weight: 600; color: #334155; font-size: 0.875rem; }
.employee-id { font-size: 0.78rem; color: #94a3b8; margin-top: 0.1rem; }

/* ── Leave Type ── */
.leave-type-cell { display: flex; align-items: center; gap: 0.5rem; }
.leave-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.leave-type-name { font-weight: 600; color: #334155; font-size: 0.875rem; }

/* ── Dates ── */
.date-range-wrap { display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap; }
.date-val { font-size: 0.82rem; color: #475569; }
.date-sep { font-size: 0.75rem; color: #94a3b8; }

/* ── Duration ── */
.text-right { text-align: right; }
.duration-badge {
  padding: 0.2rem 0.55rem;
  background: #f1f5f9;
  color: #475569;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 700;
}

/* ── Reason ── */
.reason-truncate { color: #64748b; font-size: 0.82rem; line-height: 1.4; }
.text-muted { color: #94a3b8; }

/* ── Actions ── */
.action-group { display: flex; gap: 0.5rem; justify-content: flex-end; flex-wrap: wrap; }

/* ── Empty / Loading / Error ── */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  display: flex; flex-direction: column; align-items: center;
  gap: 0.875rem;
  color: #94a3b8;
}
.empty-state p { margin: 0; }
.spinner {
  width: 40px; height: 40px;
  border: 3px solid #e2e8f0;
  border-top-color: #6366f1;
  border-radius: 50%;
  animation: spin 0.9s linear infinite;
}
.btn-spinner {
  display: inline-block;
  width: 14px; height: 14px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Modal ── */
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(0,31,91,0.5);
  backdrop-filter: blur(4px);
  z-index: 100;
  display: flex; justify-content: center; align-items: center;
  padding: 1rem;
}
.modal-container {
  background: white;
  width: 100%;
  border-radius: 16px;
  box-shadow: 0 25px 60px rgba(0,31,91,0.2);
  overflow: hidden;
  border: 1px solid #e2e8f0;
}
.modal-header {
  padding: 1.5rem 1.75rem;
  background: linear-gradient(135deg, #001f5b 0%, #002a7a 100%);
  display: flex; justify-content: space-between; align-items: center;
  color: white;
}
.modal-title-wrap { display: flex; align-items: center; gap: 0.875rem; }
.modal-avatar {
  width: 48px; height: 48px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.4rem;
  color: white;
}
.modal-name { font-size: 1.1rem; font-weight: 700; margin: 0; }
.modal-position { font-size: 0.82rem; opacity: 0.75; margin: 0.2rem 0 0; }
.modal-close {
  width: 36px; height: 36px;
  border-radius: 50%;
  background: rgba(255,255,255,0.1);
  border: 1.5px solid rgba(255,255,255,0.25);
  color: white;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  transition: background 0.2s;
}
.modal-close:hover { background: rgba(255,255,255,0.2); }
.modal-body { padding: 1.5rem 1.75rem; }
.modal-stats {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.875rem;
  margin-bottom: 1.25rem;
}
.modal-stat {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 0.875rem;
  text-align: center;
}
.modal-stat small { display: block; font-size: 0.72rem; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.3rem; }
.modal-stat strong { font-size: 0.9rem; color: #1e293b; }
.form-group { display: flex; flex-direction: column; gap: 0.4rem; }
.form-label { font-size: 0.78rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
.form-textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
  resize: vertical;
  font-family: inherit;
  font-size: 0.875rem;
  color: #334155;
  box-sizing: border-box;
  transition: border-color 0.2s;
}
.form-textarea:focus { outline: none; border-color: #6366f1; }
.modal-footer {
  padding: 1.125rem 1.75rem;
  border-top: 1px solid #f1f5f9;
  background: #f8fafc;
  display: flex; justify-content: flex-end;
  gap: 0.75rem;
}

/* ── Toast ── */
.toast {
  position: fixed;
  bottom: 2rem; right: 2rem;
  background: white;
  padding: 0.875rem 1.25rem;
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  display: flex; align-items: center;
  gap: 0.65rem;
  z-index: 200;
  font-size: 0.875rem;
  font-weight: 500;
  border-left: 4px solid #10b981;
  max-width: 360px;
}
.toast.error { border-left-color: #ef4444; }

/* ── Transitions ── */
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.25s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.toast-slide-enter-active, .toast-slide-leave-active { transition: all 0.3s ease; }
.toast-slide-enter-from, .toast-slide-leave-to { opacity: 0; transform: translateY(12px); }

/* ── Responsive ── */
@media (max-width: 900px) {
  .approvals-grid {
    grid-template-columns: 1fr 1fr;
    grid-template-areas:
      "employee actions"
      "type duration"
      "dates reason";
  }
  .approvals-grid > *:nth-child(1) { grid-area: employee; }
  .approvals-grid > *:nth-child(2) { grid-area: type; }
  .approvals-grid > *:nth-child(3) { grid-area: dates; }
  .approvals-grid > *:nth-child(4) { grid-area: duration; }
  .approvals-grid > *:nth-child(5) { grid-area: reason; }
  .approvals-grid > *:nth-child(6) { grid-area: actions; }
  .list-header { display: none; }
}
@media (max-width: 600px) {
  .leaves-view { padding: 1rem; }
  .approvals-grid { grid-template-columns: 1fr; grid-template-areas: "employee" "type" "dates" "reason" "duration" "actions"; }
  .modal-stats { grid-template-columns: 1fr 1fr; }
}
</style>