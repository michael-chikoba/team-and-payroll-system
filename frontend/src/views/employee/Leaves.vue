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
            </svg>
          </div>
          <div class="user-info">
            <h1 class="greeting">{{ pageName }}</h1>
            <p class="subtitle">Employee Leave Management</p>
            <div class="role-meta">
              <span class="role-badge">Employee View</span>
            </div>
          </div>
        </div>

        <div class="header-actions">
          <button @click="fetchLeaves" class="btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
            Refresh
          </button>
          <button @click="openApplyLeaveModal" class="btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Apply for Leave
          </button>
        </div>
      </div>
    </div>

    <div class="dashboard-content">

      <!-- ── Metrics ──────────────────────────────── -->
      <div class="metrics-section" v-if="!loading && (pendingLeaves.length > 0 || leaveHistory.length > 0)">
        <h2>Summary</h2>
        <div class="metrics-grid">
          <div class="metric-card" style="--accent:#6366f1;">
            <div class="metric-icon-wrap" style="background:rgba(99,102,241,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <div class="metric-value">{{ pendingLeaves.length }}</div>
            <div class="metric-label">Pending</div>
          </div>

          <div class="metric-card" style="--accent:#10b981;">
            <div class="metric-icon-wrap" style="background:rgba(16,185,129,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <div class="metric-value">{{ approvedCount }}</div>
            <div class="metric-label">Approved</div>
          </div>

          <div class="metric-card" style="--accent:#f59e0b;">
            <div class="metric-icon-wrap" style="background:rgba(245,158,11,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </div>
            <div class="metric-value">{{ totalDaysPending }}</div>
            <div class="metric-label">Days Pending</div>
          </div>
        </div>
      </div>

      <!-- ── Filters + Table ──────────────────────── -->
      <div class="table-section">

        <!-- Controls Bar -->
        <div class="controls-bar">
          <div class="filters-row">
            <div class="filter-group">
              <label>View</label>
              <select v-model="activeTab" class="filter-select">
                <option value="pending">Pending Requests</option>
                <option value="history">Leave History</option>
              </select>
            </div>
            <div class="filter-group" v-if="activeTab === 'history'">
              <label>Status</label>
              <select v-model="filterStatus" class="filter-select">
                <option value="">All Statuses</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div class="filter-group" v-if="activeTab === 'history'">
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
            {{ activeTab === 'pending' ? pendingLeaves.length : filteredHistory.length }}
            record{{ (activeTab === 'pending' ? pendingLeaves.length : filteredHistory.length) !== 1 ? 's' : '' }}
          </span>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="empty-state">
          <div class="spinner"></div>
          <p>Loading leave records…</p>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          <p>{{ error }}</p>
          <button @click="retryFetch" class="btn-primary">Try Again</button>
        </div>

        <!-- ── Pending Tab ──────────────────────── -->
        <div v-else-if="activeTab === 'pending'">
          <div v-if="pendingLeaves.length === 0" class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <p>No pending leave requests.</p>
            <button @click="openApplyLeaveModal" class="btn-primary">Apply for Leave</button>
          </div>

          <div v-else class="table-container">
            <div class="list-header pending-grid">
              <div>Leave Type</div>
              <div>Date Range</div>
              <div class="text-right">Duration</div>
              <div>Reason</div>
              <div>Status</div>
              <div class="text-right">Actions</div>
            </div>

            <div
              v-for="leave in paginatedPending"
              :key="leave.id"
              class="list-row pending-grid"
              @click="viewLeaveDetail(leave)"
            >
              <div class="leave-type-cell">
                <span class="leave-dot" :style="{ background: getLeaveTypeColor(leave.leave_type) }"></span>
                <span class="leave-type-name">{{ formatLeaveType(leave.leave_type) }}</span>
              </div>
              <div>
                <div class="date-range-wrap">
                  <span class="date-val">{{ formatDate(leave.start_date) }}</span>
                  <span class="date-sep">→</span>
                  <span class="date-val">{{ formatDate(leave.end_date) }}</span>
                </div>
              </div>
              <div class="text-right">
                <span class="duration-badge">{{ calculateDuration(leave.start_date, leave.end_date) }}d</span>
              </div>
              <div class="text-muted reason-truncate">{{ truncateText(leave.reason) }}</div>
              <div>
                <span :class="['status-badge', getStatusClass(leave.status)]">
                  <span class="dot"></span>{{ formatStatus(leave.status) }}
                </span>
              </div>
              <div class="action-group" @click.stop>
                <button
                  v-if="leave.status === 'pending' || leave.status === 'under_review'"
                  @click="cancelLeave(leave.id)"
                  class="action-btn danger"
                  title="Cancel Leave"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
                <button @click="viewLeaveDetail(leave)" class="action-btn" title="View Details">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- ── History Tab ──────────────────────── -->
        <div v-else-if="activeTab === 'history'">
          <div v-if="filteredHistory.length === 0" class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            <p>No leave history found.</p>
            <button v-if="filterStatus || filterType" @click="clearFilters" class="btn-secondary">Clear Filters</button>
          </div>

          <div v-else class="table-container">
            <div class="list-header history-grid">
              <div>Leave Type</div>
              <div>Date Range</div>
              <div class="text-right">Duration</div>
              <div>Status</div>
              <div>Approved By</div>
              <div>Action Date</div>
              <div class="text-right">Actions</div>
            </div>

            <div
              v-for="leave in paginatedHistory"
              :key="leave.id"
              class="list-row history-grid"
              @click="viewLeaveDetail(leave)"
            >
              <div class="leave-type-cell">
                <span class="leave-dot" :style="{ background: getLeaveTypeColor(leave.leave_type) }"></span>
                <span class="leave-type-name">{{ formatLeaveType(leave.leave_type) }}</span>
              </div>
              <div>
                <div class="date-range-wrap">
                  <span class="date-val">{{ formatDate(leave.start_date) }}</span>
                  <span class="date-sep">→</span>
                  <span class="date-val">{{ formatDate(leave.end_date) }}</span>
                </div>
              </div>
              <div class="text-right">
                <span class="duration-badge">{{ calculateDuration(leave.start_date, leave.end_date) }}d</span>
              </div>
              <div>
                <span :class="['status-badge', getStatusClass(leave.status)]">
                  <span class="dot"></span>{{ formatStatus(leave.status) }}
                </span>
              </div>
              <div class="text-muted">{{ leave.approved_by || leave.approver?.name || '—' }}</div>
              <div class="text-muted">{{ formatDate(leave.updated_at || leave.action_date) }}</div>
              <div class="action-group" @click.stop>
                <button @click="viewLeaveDetail(leave)" class="action-btn" title="View Details">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="pagination-bar">
          <span class="pagination-info">Page <strong>{{ currentPage }}</strong> of <strong>{{ totalPages }}</strong></span>
          <div class="pagination-controls">
            <button @click="prevPage" :disabled="currentPage === 1" class="page-btn">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
              Prev
            </button>
            <button @click="nextPage" :disabled="currentPage === totalPages" class="page-btn">
              Next
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
          </div>
        </div>

      </div>
    </div>

    <!-- ── Leave Detail Modal ───────────────────────── -->
    <transition name="modal-fade">
      <div v-if="selectedLeave" class="modal-overlay" @click.self="closeModal">
        <div class="modal-container">

          <div class="modal-header">
            <div class="modal-title-wrap">
              <div class="modal-avatar">{{ getLeaveTypeInitial(selectedLeave.leave_type) }}</div>
              <div>
                <h3 class="modal-name">Leave Details</h3>
                <p class="modal-position">{{ formatLeaveType(selectedLeave.leave_type) }} · {{ formatDate(selectedLeave.start_date) }} → {{ formatDate(selectedLeave.end_date) }}</p>
              </div>
            </div>
            <button @click="closeModal" class="modal-close">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
          </div>

          <div class="modal-body">
            <div class="modal-stats">
              <div class="modal-stat">
                <small>Duration</small>
                <strong>{{ calculateDuration(selectedLeave.start_date, selectedLeave.end_date) }} days</strong>
              </div>
              <div class="modal-stat">
                <small>Status</small>
                <span :class="['status-badge', getStatusClass(selectedLeave.status)]">
                  <span class="dot"></span>{{ formatStatus(selectedLeave.status) }}
                </span>
              </div>
              <div class="modal-stat">
                <small>Leave ID</small>
                <strong class="mono">#{{ selectedLeave.id }}</strong>
              </div>
            </div>

            <div class="split-view">
              <div class="detail-column">
                <h5 class="modal-section-title">
                  <span class="col-dot green"></span> Leave Info
                </h5>
                <div class="line-items">
                  <div class="line-item"><span>Leave Type</span><span>{{ formatLeaveType(selectedLeave.leave_type) }}</span></div>
                  <div class="line-item"><span>Start Date</span><span>{{ formatDate(selectedLeave.start_date) }}</span></div>
                  <div class="line-item"><span>End Date</span><span>{{ formatDate(selectedLeave.end_date) }}</span></div>
                  <div class="line-item"><span>Total Days</span><span>{{ calculateDuration(selectedLeave.start_date, selectedLeave.end_date) }}</span></div>
                  <div class="line-item"><span>Applied On</span><span>{{ formatDate(selectedLeave.created_at) }}</span></div>
                </div>
                <div class="col-total">
                  <span>Status</span>
                  <span :class="getStatusClass(selectedLeave.status) === 'success' ? 'text-success' : getStatusClass(selectedLeave.status) === 'danger' ? 'text-danger' : ''">
                    {{ formatStatus(selectedLeave.status) }}
                  </span>
                </div>
              </div>

              <div class="detail-column">
                <h5 class="modal-section-title">
                  <span class="col-dot red"></span> Approval Info
                </h5>
                <div class="line-items">
                  <div class="line-item"><span>Reviewed By</span><span>{{ selectedLeave.approved_by || selectedLeave.approver?.name || '—' }}</span></div>
                  <div class="line-item"><span>Action Date</span><span>{{ formatDate(selectedLeave.updated_at || selectedLeave.action_date) }}</span></div>
                  <div class="line-item"><span>Leave ID</span><span class="mono">#{{ selectedLeave.id }}</span></div>
                </div>
                <div class="col-total">
                  <span>Total Duration</span>
                  <span class="text-success">{{ calculateDuration(selectedLeave.start_date, selectedLeave.end_date) }} days</span>
                </div>
              </div>
            </div>

            <div class="net-pay-card">
              <div>
                <div class="net-label">REASON FOR LEAVE</div>
                <div class="net-reason">{{ selectedLeave.reason || 'No reason provided.' }}</div>
              </div>
              <div class="net-bg">LV</div>
            </div>
          </div>

          <div class="modal-footer">
            <button @click="closeModal" class="btn-secondary">Close</button>
            <button
              v-if="selectedLeave.status === 'pending' || selectedLeave.status === 'under_review'"
              @click="cancelLeave(selectedLeave.id); closeModal()"
              class="btn-danger"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
              Cancel Leave
            </button>
          </div>

        </div>
      </div>
    </transition>

    <!-- ── Apply Leave Modal ───────────────────────── -->
    <transition name="modal-fade">
      <div v-if="showApplyModal" class="modal-overlay" @click.self="closeApplyModal">
        <div class="modal-container apply-leave-modal">

          <div class="modal-header">
            <div class="modal-title-wrap">
              <div class="modal-avatar">AL</div>
              <div>
                <h3 class="modal-name">Apply for Leave</h3>
                <p class="modal-position">Submit your leave request for approval</p>
              </div>
            </div>
            <button @click="closeApplyModal" class="modal-close">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
          </div>

          <div class="modal-body">
            <div v-if="applySuccessMessage" class="alert alert-success">
              {{ applySuccessMessage }}
            </div>
            <form v-else @submit.prevent="submitLeave" class="leave-form">
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Leave Type *</label>
                  <select v-model="applyForm.leaveType" class="form-select" required @change="updateLeaveType">
                    <option value="">Select Leave Type</option>
                    <option value="annual">Annual Leave</option>
                    <option value="sick">Sick Leave</option>
                    <option value="maternity">Maternity Leave</option>
                    <option value="paternity">Paternity Leave</option>
                    <option value="bereavement">Bereavement Leave</option>
                    <option value="casual">Casual Leave</option>
                    <option value="emergency">Emergency Leave</option>
                    <option value="unpaid">Unpaid Leave</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Duration (Days) *</label>
                  <input
                    v-model.number="applyForm.duration"
                    type="number"
                    min="1"
                    max="30"
                    required
                    placeholder="e.g., 5"
                    class="form-control"
                    readonly
                  />
                </div>
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Start Date *</label>
                  <input v-model="applyForm.startDate" type="date" required :min="today" class="form-control" @change="validateDates" />
                </div>
                <div class="form-group">
                  <label class="form-label">End Date *</label>
                  <input v-model="applyForm.endDate" type="date" required :min="applyForm.startDate || today" class="form-control" @change="validateDates" />
                </div>
              </div>
              <div class="form-group full-width">
                <label class="form-label">Reason/Description *</label>
                <textarea v-model="applyForm.reason" rows="4" required placeholder="Provide a detailed reason for your leave request..." class="form-textarea"></textarea>
              </div>
              <div class="form-group full-width">
                <label class="form-label">Attach Supporting Document (Optional)</label>
                <input ref="fileInput" type="file" accept=".pdf,.doc,.docx,.jpg,.png" @change="handleFileUpload" class="form-control" />
                <p v-if="applyForm.attachment" class="file-info">Selected: {{ applyForm.attachment.name }}</p>
              </div>
              <div v-if="applyFormError" class="alert alert-danger">{{ applyFormError }}</div>
            </form>
          </div>

          <div class="modal-footer">
            <button @click="closeApplyModal" class="btn-secondary" :disabled="submitting">Cancel</button>
            <button @click="submitLeave" class="btn-primary" :disabled="submitting || applySuccessMessage" v-if="!applySuccessMessage">
              <svg v-if="!submitting" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
              <span v-if="submitting" class="spinner-small"></span>
              {{ submitting ? 'Submitting...' : 'Submit Leave Request' }}
            </button>
            <button v-if="applySuccessMessage" @click="resetAndCloseApplyModal" class="btn-primary">Done</button>
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
  name: 'Leaves',
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  data() {
    return {
      pageName: 'My Leaves',
      activeTab: 'pending',
      pendingLeaves: [],
      leaveHistory: [],
      filterStatus: '',
      filterType: '',
      loading: false,
      error: null,
      retryCount: 0,
      selectedLeave: null,
      showApplyModal: false,
      toast: { show: false, message: '', type: 'success' },
      currentPage: 1,
      perPage: 10,
      applyForm: {
        leaveType: '',
        duration: 1,
        startDate: '',
        endDate: '',
        reason: '',
        attachment: null
      },
      today: new Date().toISOString().split('T')[0],
      submitting: false,
      applySuccessMessage: null,
      applyFormError: null
    }
  },
  computed: {
    approvedCount()    { return this.leaveHistory.filter(l => l.status === 'approved').length },
    totalDaysPending() { return this.pendingLeaves.reduce((s, l) => s + this.calculateDuration(l.start_date, l.end_date), 0) },
    filteredHistory() {
      let h = this.leaveHistory
      if (this.filterStatus) h = h.filter(l => l.status === this.filterStatus)
      if (this.filterType)   h = h.filter(l => l.leave_type === this.filterType)
      return h
    },
    paginatedPending() {
      const s = (this.currentPage - 1) * this.perPage
      return this.pendingLeaves.slice(s, s + this.perPage)
    },
    paginatedHistory() {
      const s = (this.currentPage - 1) * this.perPage
      return this.filteredHistory.slice(s, s + this.perPage)
    },
    totalPages() {
      const total = this.activeTab === 'pending' ? this.pendingLeaves.length : this.filteredHistory.length
      return Math.ceil(total / this.perPage)
    }
  },
  watch: {
    activeTab()     { this.currentPage = 1 },
    filterStatus()  { this.currentPage = 1 },
    filterType()    { this.currentPage = 1 },
    'applyForm.startDate'() {
      if (this.applyForm.startDate && this.applyForm.endDate && this.applyForm.endDate < this.applyForm.startDate) {
        this.applyForm.endDate = ''
      }
    },
    'applyForm.endDate'() {
      if (this.applyForm.startDate && this.applyForm.endDate) {
        this.calculateDurationFromDates()
      }
    }
  },
  mounted() { this.fetchLeaves() },
  methods: {
    async fetchLeaves() {
      this.loading = true; this.error = null
      try {
        const r = await axios.get('/api/employee/leaves')
        const data = r.data.data || r.data || []
        this.pendingLeaves = data.filter(l => l.status === 'pending' || l.status === 'under_review')
        this.leaveHistory  = data.filter(l => l.status !== 'pending' && l.status !== 'under_review')
        this.currentPage = 1
      } catch (err) { this.handleApiError(err) }
      finally { this.loading = false }
    },
    async cancelLeave(id) {
      if (!confirm('Cancel this leave request?')) return
      try {
        const r = await axios.post(`/api/employee/leaves/${id}/cancel`)
        await this.fetchLeaves()
        this.showToast(r.data.message || 'Leave cancelled successfully.', 'success')
        if (this.selectedLeave && this.selectedLeave.id === id) {
          this.selectedLeave = null
        }
      } catch (err) {
        this.showToast(err.response?.data?.message || 'Failed to cancel leave.', 'error')
      }
    },
    openApplyLeaveModal() { this.resetApplyForm(); this.showApplyModal = true },
    closeApplyModal()     { this.showApplyModal = false; this.resetApplyForm() },
    resetAndCloseApplyModal() { this.resetApplyForm(); this.showApplyModal = false; this.fetchLeaves() },
    resetApplyForm() {
      this.applyForm = { leaveType: '', duration: 1, startDate: '', endDate: '', reason: '', attachment: null }
      this.applyFormError = null
      this.applySuccessMessage = null
      this.submitting = false
      if (this.$refs.fileInput) this.$refs.fileInput.value = ''
    },
    updateLeaveType() { this.applyFormError = null },
    validateDates() {
      if (this.applyForm.startDate && this.applyForm.endDate) this.calculateDurationFromDates()
    },
    calculateDurationFromDates() {
      if (this.applyForm.startDate && this.applyForm.endDate) {
        const start = new Date(this.applyForm.startDate)
        const end   = new Date(this.applyForm.endDate)
        const diff  = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1
        this.applyForm.duration = diff > 0 ? diff : 1
      }
    },
    validateForm() {
      this.applyFormError = null
      const validTypes = ['annual', 'sick', 'maternity', 'paternity', 'bereavement', 'casual', 'emergency', 'unpaid']
      if (!this.applyForm.leaveType)                    { this.applyFormError = 'Please select a leave type.'; return false }
      if (!validTypes.includes(this.applyForm.leaveType)) { this.applyFormError = 'Invalid leave type selected.'; return false }
      if (!this.applyForm.startDate || !this.applyForm.endDate) { this.applyFormError = 'Please select both start and end dates.'; return false }
      if (new Date(this.applyForm.endDate) < new Date(this.applyForm.startDate)) { this.applyFormError = 'End date must be after start date.'; return false }
      if (!this.applyForm.reason.trim())                { this.applyFormError = 'Please provide a reason for your leave request.'; return false }
      return true
    },
    async submitLeave() {
      if (!this.validateForm()) return
      this.submitting = true; this.applyFormError = null
      try {
        const payload = new FormData()
        payload.append('type',       this.applyForm.leaveType)
        payload.append('duration',   this.applyForm.duration)
        payload.append('start_date', this.applyForm.startDate)
        payload.append('end_date',   this.applyForm.endDate)
        payload.append('reason',     this.applyForm.reason)
        if (this.applyForm.attachment) payload.append('attachment', this.applyForm.attachment)
        const response = await axios.post('/api/employee/leaves', payload, {
          headers: { 'Content-Type': 'multipart/form-data', 'Accept': 'application/json' },
          timeout: 10000
        })
        this.applySuccessMessage = 'Leave request submitted successfully! It will be reviewed by your manager.'
        this.showToast('Leave request submitted successfully!', 'success')
      } catch (err) {
        this.handleApplyApiError(err)
      } finally {
        this.submitting = false
      }
    },
    handleFileUpload(event) {
      const file = event.target.files[0]
      if (file) {
        if (file.size > 5 * 1024 * 1024) { this.applyFormError = 'File size must be less than 5MB.'; event.target.value = ''; return }
        const allowed = ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','image/jpeg','image/png']
        if (!allowed.includes(file.type)) { this.applyFormError = 'File type not allowed. Please upload PDF, DOC, DOCX, JPG, or PNG.'; event.target.value = ''; return }
      }
      this.applyForm.attachment = file
      this.applyFormError = null
    },
    handleApplyApiError(err) {
      let msg = 'An unexpected error occurred.'
      if (err.code === 'ERR_NETWORK')        { msg = 'Network error. Please check your connection.' }
      else if (err.response?.status === 401) { msg = 'Session expired. Redirecting to login...'; this.authStore.clearAuth(); setTimeout(() => this.$router.push('/auth/login'), 2000) }
      else if (err.response?.status === 422) {
        const d = err.response.data
        msg = d.errors ? Object.values(d.errors).flat().join('. ') : d.message || 'Please check the form for errors.'
      }
      else if (err.response?.status === 403) { msg = 'You do not have permission to apply for leave.' }
      else if (err.response?.status === 500) { msg = 'Server error. Please try again later.' }
      else { msg = err.response?.data?.message || msg }
      this.applyFormError = msg
    },
    viewLeaveDetail(leave) { this.selectedLeave = { ...leave } },
    closeModal()   { this.selectedLeave = null },
    clearFilters() { this.filterStatus = ''; this.filterType = '' },
    retryFetch() {
      this.retryCount++
      if (this.retryCount <= 3) this.fetchLeaves()
      else this.error = 'Max retries exceeded. Please refresh the page.'
    },
    prevPage() { if (this.currentPage > 1) this.currentPage-- },
    nextPage() { if (this.currentPage < this.totalPages) this.currentPage++ },
    handleApiError(err) {
      if (err.code === 'ERR_NETWORK')        { this.error = 'Network error. Check your connection.'; return }
      if (err.response?.status === 401)      { this.authStore.clearAuth(); this.$router.push({ name: 'login' }); return }
      if (err.response?.status === 403)      { this.error = 'Access denied.'; return }
      if (err.response?.status === 404)      { this.error = 'Leave data not found.'; return }
      this.error = err.response?.data?.message || 'An unexpected error occurred.'
    },
    showToast(message, type = 'success') {
      this.toast = { show: true, message, type }
      setTimeout(() => { this.toast.show = false }, 3000)
    },
    getLeaveTypeColor(type) {
      return { annual:'#3b82f6', sick:'#f59e0b', maternity:'#ec4899', paternity:'#8b5cf6', bereavement:'#6b7280', unpaid:'#94a3b8', casual:'#10b981', emergency:'#ef4444' }[type] || '#64748b'
    },
    getLeaveTypeInitial(type) {
      return { annual:'AL', sick:'SL', maternity:'ML', paternity:'PL', bereavement:'BL', unpaid:'UL', casual:'CL', emergency:'EL' }[type] || 'LV'
    },
    formatLeaveType(type) {
      return { annual:'Annual Leave', sick:'Sick Leave', maternity:'Maternity Leave', paternity:'Paternity Leave', bereavement:'Bereavement Leave', unpaid:'Unpaid Leave', casual:'Casual Leave', emergency:'Emergency Leave' }[type] || type || '—'
    },
    formatStatus(status) {
      return { pending:'Pending', approved:'Approved', rejected:'Rejected', cancelled:'Cancelled', under_review:'Under Review' }[status] || status || '—'
    },
    getStatusClass(status) {
      return { approved:'success', pending:'warning', under_review:'warning', rejected:'danger', cancelled:'neutral' }[status?.toLowerCase()] || 'neutral'
    },
    formatDate(date) {
      if (!date) return '—'
      try { return new Date(date).toLocaleDateString('en-ZM', { year:'numeric', month:'short', day:'numeric' }) }
      catch { return '—' }
    },
    calculateDuration(startDate, endDate) {
      if (!startDate || !endDate) return 0
      try { return Math.ceil(Math.abs(new Date(endDate) - new Date(startDate)) / (1000 * 60 * 60 * 24)) + 1 }
      catch { return 0 }
    },
    truncateText(text) {
      if (!text) return '—'
      return text.length > 50 ? text.substring(0, 50) + '…' : text
    }
  }
}
</script>

<style scoped>
/* ── Base ──────────────────────────────────────────── */
.leaves-view {
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
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
}

.user-greeting { display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; }
.avatar-section { display: flex; align-items: center; gap: 1rem; }

.avatar {
  width: 52px; height: 52px;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  border-radius: 14px; display: flex; align-items: center; justify-content: center;
  color: white; box-shadow: 0 4px 12px rgba(59,130,246,0.25); flex-shrink: 0;
}

.user-info { display: flex; flex-direction: column; gap: 0.2rem; }
.greeting  { margin: 0; font-size: 1.375rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
.subtitle  { margin: 0; color: #64748b; font-size: 0.875rem; }
.role-meta { margin-top: 0.125rem; }

.role-badge {
  background: #f0fdf4; border: 1px solid #bbf7d0;
  padding: 0.125rem 0.6rem; border-radius: 8px;
  font-size: 0.7rem; font-weight: 600; color: #166534;
}

.header-actions { display: flex; gap: 0.5rem; flex-shrink: 0; }

/* ── Buttons ─────────────────────────────────────── */
.btn-primary {
  display: flex; align-items: center; gap: 0.4rem;
  background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;
  border: none; padding: 0.5rem 1.25rem; border-radius: 8px;
  font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
  font-family: inherit;
}
.btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,0.35); }
.btn-primary:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

.btn-outline {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.45rem 0.9rem; background: white; border: 1px solid #e2e8f0;
  color: #475569; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-outline:hover { background: #f8fafc; border-color: #cbd5e1; color: #1e293b; }

.btn-secondary {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.5rem 1.25rem; background: #f1f5f9; color: #475569;
  border: 1px solid #e2e8f0; border-radius: 8px;
  font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
  font-family: inherit;
}
.btn-secondary:hover:not(:disabled) { background: #e2e8f0; }
.btn-secondary:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-danger {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.5rem 1.25rem; background: #ef4444; color: white;
  border: none; border-radius: 8px; font-size: 0.875rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-danger:hover:not(:disabled) { background: #dc2626; box-shadow: 0 4px 12px rgba(239,68,68,0.3); }
.btn-danger:disabled { opacity: 0.5; cursor: not-allowed; }

/* ── Dashboard Content ───────────────────────────── */
.dashboard-content { display: flex; flex-direction: column; gap: 1.5rem; }

/* ── Section Cards ───────────────────────────────── */
.metrics-section,
.table-section {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0;
  padding: 1.5rem;
}

h2 { font-size: 1.1rem; font-weight: 600; margin: 0 0 1.25rem 0; color: #334155; }

/* ── Metrics ─────────────────────────────────────── */
/* CHANGED: minmax 200px → 160px, gap 1.25rem → 0.75rem */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 0.75rem;
}

/* CHANGED: padding 1.25rem → 0.75rem 1rem, border-radius 12px → 10px, added gap: 0.3rem */
.metric-card {
  padding: 0.75rem 1rem;
  background: #f8fafc;
  border-radius: 10px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  border: 1px solid #e2e8f0;
  position: relative;
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
  gap: 0.3rem;
}
.metric-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px -4px rgba(0,0,0,0.08); border-color: var(--accent); }
.metric-card::before { display: none; }

/* CHANGED: width/height 40px → 32px, border-radius 10px → 8px, margin-bottom 0.75rem → 0 (gap handles spacing) */
.metric-icon-wrap {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* CHANGED: font-size 1.8rem → 1.35rem, line-height 1.1 → 1, removed margin-bottom */
.metric-value {
  font-size: 1.35rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1;
}

/* CHANGED: font-size 0.78rem → 0.72rem */
.metric-label {
  font-size: 0.72rem;
  color: #64748b;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

/* ── Controls Bar ────────────────────────────────── */
.controls-bar {
  display: flex; justify-content: space-between; align-items: flex-end;
  margin-bottom: 1.25rem; gap: 1rem; flex-wrap: wrap;
}
.filters-row { display: flex; gap: 0.875rem; flex-wrap: wrap; }
.filter-group { display: flex; flex-direction: column; gap: 0.3rem; }
.filter-group label { font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }

.filter-select {
  padding: 0.45rem 2rem 0.45rem 0.875rem; border: 1px solid #e2e8f0;
  border-radius: 8px; background: #f8fafc; color: #334155;
  font-size: 0.875rem; font-weight: 500; cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 0.75rem center;
  transition: all 0.2s; font-family: inherit;
}
.filter-select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

.records-count {
  font-size: 0.78rem; font-weight: 700; color: #64748b;
  background: #f1f5f9; padding: 0.2rem 0.7rem; border-radius: 9999px; white-space: nowrap;
}

/* ── Table ───────────────────────────────────────── */
.table-container { border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0; }

.pending-grid {
  display: grid;
  grid-template-columns: 1.4fr 1.6fr 0.6fr 1.4fr 0.9fr 0.7fr;
  padding: 0.75rem 1rem; align-items: center; gap: 0.75rem;
}

.history-grid {
  display: grid;
  grid-template-columns: 1.4fr 1.6fr 0.6fr 0.9fr 1fr 1fr 0.5fr;
  padding: 0.75rem 1rem; align-items: center; gap: 0.75rem;
}

.list-header {
  background: #f8fafc; border-bottom: 1px solid #e2e8f0;
  font-size: 0.7rem; font-weight: 700; color: #64748b;
  text-transform: uppercase; letter-spacing: 0.05em;
}

.list-row {
  border-bottom: 1px solid #f1f5f9; background: white;
  cursor: pointer; transition: background 0.15s;
}
.list-row:last-child { border-bottom: none; }
.list-row:hover { background: #f8fafc; }

/* ── Cell types ──────────────────────────────────── */
.leave-type-cell { display: flex; align-items: center; gap: 0.5rem; }
.leave-dot  { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.leave-type-name { font-weight: 600; color: #334155; font-size: 0.875rem; }

.date-range-wrap { display: flex; align-items: center; gap: 0.35rem; flex-wrap: wrap; }
.date-val { font-size: 0.82rem; color: #475569; font-weight: 500; white-space: nowrap; }
.date-sep { color: #94a3b8; font-size: 0.75rem; }

.duration-badge {
  display: inline-block; padding: 0.2rem 0.55rem;
  background: #f1f5f9; color: #475569; border-radius: 6px;
  font-size: 0.75rem; font-weight: 700; border: 1px solid #e2e8f0;
}

.reason-truncate { color: #64748b; font-size: 0.82rem; }

.text-right  { text-align: right; }
.text-muted  { color: #64748b; font-size: 0.82rem; }
.text-success{ color: #10b981; }
.text-danger { color: #ef4444; }

/* ── Status Badges ───────────────────────────────── */
.status-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 0.25rem 0.65rem; border-radius: 9999px;
  font-size: 0.7rem; font-weight: 700; white-space: nowrap;
}
.dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
.status-badge.success { background: #d1fae5; color: #065f46; }
.status-badge.warning { background: #fef3c7; color: #92400e; }
.status-badge.danger  { background: #fee2e2; color: #991b1b; }
.status-badge.neutral { background: #f1f5f9; color: #64748b; }

/* ── Action Buttons ──────────────────────────────── */
.action-group { display: flex; justify-content: flex-end; gap: 0.35rem; }

.action-btn {
  width: 30px; height: 30px; border-radius: 6px;
  border: 1px solid #e2e8f0; background: white; color: #64748b;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.15s;
}
.action-btn:hover       { background: #eff6ff; color: #4f46e5; border-color: #a5b4fc; }
.action-btn.danger:hover{ background: #fee2e2; color: #ef4444; border-color: #fecaca; }

/* ── Spinner ─────────────────────────────────────── */
.spinner {
  width: 40px; height: 40px; border: 3px solid #e2e8f0; border-top-color: #6366f1;
  border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;
}
@keyframes spin { to { transform: rotate(360deg); } }

.spinner-small {
  display: inline-block;
  width: 14px; height: 14px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

.empty-state {
  text-align: center; padding: 4rem 2rem;
  display: flex; flex-direction: column; align-items: center; gap: 0.875rem; color: #94a3b8;
}
.empty-state p { margin: 0; font-size: 0.875rem; color: #64748b; }

/* ── Pagination ──────────────────────────────────── */
.pagination-bar {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1rem 0 0; border-top: 1px solid #f1f5f9; margin-top: 0.5rem;
}
.pagination-info { font-size: 0.82rem; color: #64748b; }
.pagination-info strong { color: #1e293b; font-weight: 700; }
.pagination-controls { display: flex; gap: 0.5rem; }

.page-btn {
  display: flex; align-items: center; gap: 0.3rem;
  padding: 0.35rem 0.875rem; background: white; border: 1px solid #e2e8f0;
  border-radius: 6px; font-size: 0.78rem; font-weight: 600; color: #475569;
  cursor: pointer; transition: all 0.15s; font-family: inherit;
}
.page-btn:hover:not(:disabled) { border-color: #a5b4fc; color: #4f46e5; background: #eff6ff; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }

/* ── Modal ───────────────────────────────────────── */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,31,91,0.5);
  backdrop-filter: blur(4px); z-index: 100;
  display: flex; justify-content: center; align-items: center; padding: 1rem;
}

.modal-container {
  background: white; width: 100%; max-width: 720px; max-height: 90vh;
  border-radius: 16px; box-shadow: 0 25px 60px rgba(0,31,91,0.2);
  display: flex; flex-direction: column; overflow: hidden;
  border: 1px solid #e2e8f0; animation: slideUp 0.25s ease-out;
}
.apply-leave-modal { max-width: 650px; }

@keyframes slideUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }

.modal-header {
  padding: 1.5rem 1.75rem;
  background: linear-gradient(135deg, #001f5b 0%, #002a7a 100%);
  display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;
}
.modal-title-wrap { display: flex; align-items: center; gap: 1rem; }

.modal-avatar {
  width: 48px; height: 48px; background: rgba(255,255,255,0.15); border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-weight: 900; font-size: 0.75rem; color: white; letter-spacing: 0.05em;
  border: 1.5px solid rgba(255,255,255,0.25);
}

.modal-name     { font-size: 1.2rem; font-weight: 700; color: white; margin: 0; }
.modal-position { font-size: 0.82rem; color: rgba(255,255,255,0.7); margin: 0.2rem 0 0; }

.modal-close {
  width: 36px; height: 36px; border-radius: 50%;
  border: 1.5px solid rgba(255,255,255,0.25); background: rgba(255,255,255,0.1);
  color: white; display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.2s;
}
.modal-close:hover { background: rgba(239,68,68,0.6); border-color: transparent; }

.modal-body { padding: 1.5rem 1.75rem; overflow-y: auto; flex: 1; }

.modal-stats {
  display: grid; grid-template-columns: repeat(3, 1fr);
  gap: 0.875rem; margin-bottom: 1.5rem;
}
.modal-stat {
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
  padding: 0.875rem; text-align: center;
  display: flex; flex-direction: column; gap: 0.3rem; align-items: center;
}
.modal-stat small  { font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.modal-stat strong { font-size: 0.9rem; font-weight: 700; color: #1e293b; }
.modal-stat .mono  { font-family: 'SFMono-Regular', Consolas, monospace; font-size: 0.82rem; }

.split-view { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem; }

.detail-column {
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1.125rem;
}

.modal-section-title {
  display: flex; align-items: center; gap: 0.5rem;
  font-size: 0.82rem; font-weight: 700; color: #334155;
  margin: 0 0 0.875rem; padding-bottom: 0.6rem; border-bottom: 1px solid #e2e8f0;
}

.col-dot       { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.col-dot.green { background: #10b981; }
.col-dot.red   { background: #ef4444; }

.line-items { display: flex; flex-direction: column; }
.line-item {
  display: flex; justify-content: space-between; align-items: center;
  padding: 0.45rem 0.5rem; border-radius: 6px; font-size: 0.82rem;
}
.line-item:hover { background: white; }
.line-item span:first-child { color: #64748b; }
.line-item span:last-child  { font-family: 'SFMono-Regular', Consolas, monospace; font-size: 0.78rem; font-weight: 600; color: #1e293b; }
.line-item .mono { font-family: 'SFMono-Regular', Consolas, monospace; }

.col-total {
  display: flex; justify-content: space-between; font-weight: 700;
  border-top: 1px solid #e2e8f0; padding-top: 0.75rem; margin-top: 0.5rem; font-size: 0.875rem;
}

.net-pay-card {
  background: linear-gradient(135deg, #001f5b 0%, #0040c1 100%);
  color: white; padding: 1.5rem; border-radius: 12px;
  display: flex; justify-content: space-between; align-items: flex-start;
  position: relative; overflow: hidden;
}
.net-label  { font-size: 0.68rem; opacity: 0.8; letter-spacing: 0.12em; font-weight: 600; margin-bottom: 0.5rem; }
.net-reason { font-size: 0.95rem; line-height: 1.6; opacity: 0.95; max-width: 90%; }
.net-bg     { position: absolute; right: -10px; bottom: -22px; font-size: 4.5rem; font-weight: 900; opacity: 0.07; letter-spacing: -0.05em; }

.modal-footer {
  padding: 1.125rem 1.75rem; border-top: 1px solid #f1f5f9;
  background: #f8fafc; display: flex; justify-content: flex-end; gap: 0.75rem; flex-shrink: 0;
}

/* ── Apply Leave Form ────────────────────────────── */
.leave-form { display: flex; flex-direction: column; gap: 1.25rem; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-group { display: flex; flex-direction: column; gap: 0.4rem; }
.form-group.full-width { grid-column: 1 / -1; }
.form-label { font-size: 0.8rem; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.04em; }

.form-control, .form-select, .form-textarea {
  padding: 0.6rem 0.875rem; border: 1px solid #e2e8f0;
  border-radius: 8px; background: #f8fafc; color: #1e293b;
  font-size: 0.9rem; font-family: inherit; transition: all 0.2s;
}
.form-control:focus, .form-select:focus, .form-textarea:focus {
  outline: none; border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99,102,241,0.1); background: white;
}
.form-textarea { resize: vertical; min-height: 100px; }
.form-control[readonly] { background: #f1f5f9; cursor: not-allowed; opacity: 0.8; }

.file-info {
  margin-top: 0.25rem; font-size: 0.8rem; color: #64748b;
  padding: 0.4rem 0.75rem; background: #f1f5f9;
  border-radius: 6px; border-left: 3px solid #6366f1;
}

/* ── Alerts ──────────────────────────────────────── */
.alert {
  padding: 1rem 1.25rem; border-radius: 10px; font-size: 0.9rem;
  display: flex; align-items: center; flex-wrap: wrap; gap: 0.75rem;
}
.alert-success { background: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; }
.alert-danger  { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }

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
.toast.warning { border-left-color: #f59e0b; }
.toast.success svg { color: #10b981; }
.toast.error   svg { color: #ef4444; }

/* ── Transitions ─────────────────────────────────── */
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.25s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.toast-slide-enter-active, .toast-slide-leave-active { transition: all 0.3s ease; }
.toast-slide-enter-from, .toast-slide-leave-to { opacity: 0; transform: translateY(12px); }

/* ── Responsive ──────────────────────────────────── */
@media (max-width: 768px) {
  .leaves-view    { padding: 1rem; }
  .user-greeting  { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .header-actions { width: 100%; }
  .metrics-grid   { grid-template-columns: 1fr 1fr; }
  .list-header    { display: none; }
  .pending-grid,
  .history-grid   {
    grid-template-columns: 1fr auto;
    grid-template-areas: "type status" "dates actions";
    padding: 0.875rem 1rem; gap: 0.5rem;
  }
  .split-view      { grid-template-columns: 1fr; gap: 1rem; }
  .modal-stats     { grid-template-columns: 1fr 1fr; }
  .modal-container { max-height: 100vh; border-radius: 16px 16px 0 0; position: fixed; bottom: 0; }
  .form-row        { grid-template-columns: 1fr; }
}

@media (max-width: 480px) {
  .metrics-grid { grid-template-columns: 1fr; }
  .modal-footer { flex-direction: column-reverse; }
  .modal-footer button { width: 100%; justify-content: center; }
}
</style>