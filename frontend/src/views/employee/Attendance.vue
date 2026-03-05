<template>
  <div class="attendance-view">

    <!-- Activity Monitor (shows when overtime is active) -->
    <ActivityMonitor v-if="isInOvertimeSession" />

    <!-- ── Header Card ─────────────────────────────── -->
    <div class="dashboard-header-card">
      <div class="header-card-accent"></div>
      <div class="user-greeting">
        <div class="avatar-section">
          <div class="avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
          </div>
          <div class="user-info">
            <h1 class="greeting">{{ pageName }}</h1>
            <p class="subtitle">Track your daily attendance and work hours</p>
            <div class="role-meta">
              <span class="role-badge">Employee View</span>
            </div>
          </div>
        </div>

        <div class="header-actions">
          <!-- Today's Status Badge -->
          <div class="today-status-badge" :class="getStatusBadgeClass(todayStatus)">
            <span class="status-dot-large"></span>
            <span class="status-text">{{ formatTodayStatus(todayStatus) }}</span>
            <span v-if="isInOvertimeSession" class="overtime-badge-header">⚡ OT</span>
          </div>
          
          <!-- Today's Shift Info -->
          <div v-if="currentShift" class="shift-info-header">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            <span>{{ currentShift.start_time }} - {{ currentShift.end_time }}</span>
            <span class="shift-type">{{ formatShiftType(currentShift.type) }}</span>
          </div>
          
          <!-- Overtime Session Info (if active) -->
          <div v-if="isInOvertimeSession && overtimeSession" class="overtime-info-header">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            <span>OT: {{ formatTimeDisplay(overtimeSession.clock_in) }}</span>
            <span class="overtime-duration-header">{{ formatDuration(overtimeSession.duration_minutes) }}</span>
          </div>

          <button @click="retryFetch" class="btn-outline" title="Refresh">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
          </button>
          <button
            v-if="showResetButton"
            @click="forceResetStatus"
            :disabled="resetting"
            class="btn-outline"
            style="border-color: #ef4444; color: #ef4444;"
          >
            <span v-if="resetting" class="spinner-small"></span>
            {{ resetting ? 'Resetting...' : 'Reset' }}
          </button>
        </div>
      </div>
      
      <!-- Quick Stats Bar in Header -->
      <div class="header-quick-stats">
        <div class="quick-stat-item">
          <span class="stat-icon">⏱️</span>
          <div class="stat-info">
            <span class="stat-value">{{ formatHours(stats.totalHours || 0) }}</span>
            <span class="stat-label">Total</span>
          </div>
        </div>
        <div class="quick-stat-divider"></div>
        <div class="quick-stat-item">
          <span class="stat-icon">✓</span>
          <div class="stat-info">
            <span class="stat-value">{{ formatHours(stats.regularHours || 0) }}</span>
            <span class="stat-label">Regular</span>
          </div>
        </div>
        <div class="quick-stat-divider"></div>
        <div class="quick-stat-item" @click="viewOvertimeSummary" style="cursor: pointer">
          <span class="stat-icon">⚡</span>
          <div class="stat-info">
            <span class="stat-value overtime-text">{{ formatHours(stats.overtimeHours || 0) }}</span>
            <span class="stat-label">Overtime</span>
          </div>
        </div>
        <div class="quick-stat-divider"></div>
        <div class="quick-stat-item">
          <span class="stat-icon">📊</span>
          <div class="stat-info">
            <span class="stat-value">{{ (stats.attendanceRate || 0).toFixed(1) }}%</span>
            <span class="stat-label">Rate</span>
          </div>
        </div>
      </div>
    </div>

    <div class="dashboard-content">

      <!-- ── Smart Clock Action Card ──────────────────── -->
      <div class="clock-action-card">

        <!-- Step indicator -->
        <div class="clock-steps">
          <div class="clock-step" :class="{ done: stepDone('clockin'), active: stepActive('clockin') }">
            <div class="step-circle">
              <svg v-if="stepDone('clockin')" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
              <span v-else>1</span>
            </div>
            <span class="step-label">Clock In</span>
          </div>
          <div class="step-line" :class="{ done: stepDone('clockin') }"></div>
          <div class="clock-step" :class="{ done: stepDone('clockout'), active: stepActive('clockout') }">
            <div class="step-circle">
              <svg v-if="stepDone('clockout')" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
              <span v-else>2</span>
            </div>
            <span class="step-label">Clock Out</span>
          </div>
          <div class="step-line" :class="{ done: stepDone('clockout') }"></div>
          <div class="clock-step" :class="{ done: stepDone('overtime'), active: stepActive('overtime') }">
            <div class="step-circle">
              <svg v-if="stepDone('overtime')" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
              <span v-else>3</span>
            </div>
            <span class="step-label">Overtime</span>
          </div>
          <div class="step-line" :class="{ done: stepDone('overtime') }"></div>
          <div class="clock-step" :class="{ active: stepActive('otclockout') }">
            <div class="step-circle"><span>4</span></div>
            <span class="step-label">OT Clock Out</span>
          </div>
        </div>

        <!-- Single dynamic action button -->
        <div class="clock-action-center">

          <!-- State: Not clocked in → show Clock In -->
          <button
            v-if="canClockIn && !canClockOut && !canStartOvertime && !isInOvertimeSession"
            @click="clockIn"
            :disabled="clockingIn"
            class="btn-clock-main btn-clock-in-main"
          >
            <div class="btn-clock-icon">
              <span v-if="clockingIn" class="spinner-medium"></span>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <div class="btn-clock-text">
              <span class="btn-clock-label">{{ clockingIn ? 'Clocking In…' : 'Clock In' }}</span>
              <span class="btn-clock-sub">Start your work session</span>
            </div>
          </button>

          <!-- State: Clocked in (regular) → show Clock Out -->
          <button
            v-else-if="canClockOut && !isInOvertimeSession"
            @click="clockOut"
            :disabled="clockingOut"
            class="btn-clock-main btn-clock-out-main"
          >
            <div class="btn-clock-icon">
              <span v-if="clockingOut" class="spinner-medium"></span>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div>
            <div class="btn-clock-text">
              <span class="btn-clock-label">{{ clockingOut ? 'Clocking Out…' : 'Clock Out' }}</span>
              <span class="btn-clock-sub">End your regular session</span>
            </div>
          </button>

          <!-- State: Regular shift done → Start Overtime -->
          <button
            v-else-if="canStartOvertime && !isInOvertimeSession"
            @click="clockInOvertime"
            :disabled="clockingInOvertime"
            class="btn-clock-main btn-clock-overtime-main pulse-effect"
          >
            <div class="btn-clock-icon">
              <span v-if="clockingInOvertime" class="spinner-medium"></span>
              <span v-else class="ot-icon">⚡</span>
            </div>
            <div class="btn-clock-text">
              <span class="btn-clock-label">
                {{ clockingInOvertime ? 'Starting…' : 'Start Overtime' }}
                <span v-if="!clockingInOvertime" class="badge-ready-inline">Ready</span>
              </span>
              <span class="btn-clock-sub">Begin overtime session</span>
            </div>
          </button>

          <!-- State: In overtime session → Clock Out of OT -->
          <button
            v-else-if="isInOvertimeSession && canClockOut"
            @click="clockOut"
            :disabled="clockingOut"
            class="btn-clock-main btn-clock-otout-main"
          >
            <div class="btn-clock-icon">
              <span v-if="clockingOut" class="spinner-medium"></span>
              <span v-else class="ot-icon">⚡</span>
            </div>
            <div class="btn-clock-text">
              <span class="btn-clock-label">
                {{ clockingOut ? 'Clocking Out…' : 'Clock Out (Overtime)' }}
                <span class="live-indicator-inline">LIVE</span>
              </span>
              <span class="btn-clock-sub">End overtime session</span>
            </div>
          </button>

          <!-- State: All done (no action available) -->
          <div v-else class="clock-done-state">
            <div class="clock-done-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <div class="clock-done-text">
              <span class="clock-done-label">All Done for Today</span>
              <span class="clock-done-sub">Your attendance has been recorded</span>
            </div>
          </div>

        </div>
      </div>
     
      <!-- Overtime Quick Summary Banner -->
      <div v-if="overtimeSummary && overtimeSummary.total_overtime_hours > 0" class="overtime-banner">
        <div class="overtime-banner-icon">⚡</div>
        <div class="overtime-banner-content">
          <span class="overtime-banner-title">This Month's Overtime:</span>
          <span class="overtime-banner-hours">{{ formatHours(overtimeSummary.total_overtime_hours) }}</span>
          <span class="overtime-banner-sessions">({{ overtimeSummary.overtime_sessions_count || 0 }} sessions)</span>
          <span class="overtime-banner-days">over {{ overtimeSummary.days_with_overtime || 0 }} days</span>
        </div>
        <button @click="viewOvertimeSummary" class="btn-outline-warning">View Details</button>
      </div>
     
      <!-- Filters Section -->
      <div class="controls-section">
        <div class="controls-bar">
          <div class="filters-row">
            <div class="filter-group">
              <label>From Date</label>
              <input
                v-model="dateFrom"
                type="date"
                @change="handleFilterChange"
                :max="today"
                class="filter-input"
              />
            </div>
            <div class="filter-group">
              <label>To Date</label>
              <input
                v-model="dateTo"
                type="date"
                @change="handleFilterChange"
                :max="today"
                class="filter-input"
              />
            </div>
            <div class="filter-group">
              <label>Status</label>
              <select v-model="statusFilter" @change="handleFilterChange" class="filter-select">
                <option value="">All Statuses</option>
                <option value="present">Present</option>
                <option value="absent">Absent</option>
                <option value="late">Late</option>
                <option value="completed">Completed</option>
              </select>
            </div>
            <div class="filter-group">
              <label>Session Type</label>
              <select v-model="sessionTypeFilter" @change="handleFilterChange" class="filter-select">
                <option value="">All Sessions</option>
                <option value="regular">Regular Only</option>
                <option value="overtime">Overtime Only</option>
              </select>
            </div>
          </div>
          <div class="filter-actions">
            <button @click="resetFilters" class="btn-secondary">Reset Filters</button>
            <button v-if="stats.overtimeHours > 0" @click="exportOvertimeReport" class="btn-outline-primary">
              📊 Export Overtime
            </button>
          </div>
        </div>
      </div>
     
      <!-- Attendance Table -->
      <div class="table-section">
        <div class="table-header">
          <h2>Attendance Records</h2>
          <div class="table-meta">
            <span class="records-count">{{ filteredAttendance.length }} records</span>
            <span class="overtime-badge" v-if="overtimeRecordsCount > 0">
              ⚡ {{ overtimeRecordsCount }} overtime
            </span>
          </div>
        </div>
       
        <div v-if="loading" class="empty-state">
          <div class="spinner"></div>
          <p>Loading attendance records...</p>
        </div>
       
        <div v-else-if="error" class="empty-state error-state">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          <p>{{ error }}</p>
          <button @click="retryFetch" class="btn-primary">Try Again</button>
        </div>
       
        <div v-else-if="filteredAttendance.length === 0" class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
          <p>No attendance records found</p>
          <p class="empty-subtitle">No records match your current filters.</p>
          <button @click="resetFilters" class="btn-primary">Reset Filters</button>
        </div>
       
        <div v-else class="table-container">
          <div class="attendance-table-header">
            <div>Date</div>
            <div>Type</div>
            <div>Status</div>
            <div>Check In</div>
            <div>Check Out</div>
            <div class="text-right">Regular</div>
            <div class="text-right">Overtime</div>
            <div class="text-right">Total</div>
            <div>Notes</div>
          </div>

          <div
            v-for="record in paginatedRecords"
            :key="record.id || Math.random()"
            class="attendance-table-row"
          >
            <div>
              <div class="date-cell">
                <span class="date-text">{{ formatDate(record.date) }}</span>
              </div>
            </div>
            <div>
              <span v-if="record.is_overtime_session" class="type-badge type-overtime">⚡ Overtime</span>
              <span v-else class="type-badge type-regular">Regular</span>
            </div>
            <div>
              <span :class="['status-badge', getStatusClass(record.status)]">
                <span class="dot"></span>{{ formatStatus(record.status) }}
              </span>
            </div>
            <div class="time-cell">{{ formatTimeDisplay(record.clock_in) }}</div>
            <div class="time-cell">{{ formatTimeDisplay(record.clock_out) }}</div>
            <div class="text-right">
              <span class="hours-badge">{{ formatHours(record.regular_hours) }}</span>
            </div>
            <div class="text-right">
              <span class="hours-badge overtime-hours">
                <strong>{{ formatHours(record.overtime_hours) }}</strong>
                <span v-if="record.overtime_hours > 0" class="overtime-star">⚡</span>
              </span>
            </div>
            <div class="text-right">
              <span class="hours-badge total-hours">{{ formatHours(record.total_hours) }}</span>
            </div>
            <div class="notes-cell">
              <span class="notes-text">{{ record.notes || '-' }}</span>
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
            <div class="pagination-numbers">
              <button
                v-for="page in visiblePages"
                :key="page"
                @click="goToPage(page)"
                :class="['page-number', { active: page === currentPage }]"
              >{{ page }}</button>
            </div>
            <button @click="nextPage" :disabled="currentPage === totalPages" class="page-btn">
              Next
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
          </div>
          <select v-model="itemsPerPage" @change="handlePerPageChange" class="per-page-select">
            <option :value="10">10 / page</option>
            <option :value="25">25 / page</option>
            <option :value="50">50 / page</option>
            <option :value="100">100 / page</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Overtime Summary Modal -->
    <transition name="modal-fade">
      <div v-if="showOvertimeModal" class="modal-overlay" @click.self="closeOvertimeModal">
        <div class="modal-container">
          <div class="modal-header">
            <div class="modal-title-wrap">
              <div class="modal-avatar">⚡</div>
              <div>
                <h3 class="modal-name">Overtime Summary</h3>
                <p class="modal-position">{{ new Date().toLocaleString('default', { month: 'long', year: 'numeric' }) }}</p>
              </div>
            </div>
            <button @click="closeOvertimeModal" class="modal-close">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
          </div>
          <div class="modal-body" v-if="overtimeSummary">
            <div class="modal-stats">
              <div class="modal-stat">
                <small>Total Overtime</small>
                <strong>{{ formatHours(overtimeSummary.total_overtime_hours || 0) }}</strong>
              </div>
              <div class="modal-stat">
                <small>Sessions</small>
                <strong>{{ overtimeSummary.overtime_sessions_count || 0 }}</strong>
              </div>
              <div class="modal-stat">
                <small>Days with OT</small>
                <strong>{{ overtimeSummary.days_with_overtime || 0 }}</strong>
              </div>
            </div>
            <div class="detail-column" style="margin-bottom: 0;">
              <h5 class="modal-section-title">
                <span class="col-dot orange"></span> Overtime Details
              </h5>
              <div class="line-items">
                <div class="line-item">
                  <span>Average per day</span>
                  <span>{{ formatHours((overtimeSummary.total_overtime_hours || 0) / (overtimeSummary.days_with_overtime || 1)) }}</span>
                </div>
                <div class="line-item">
                  <span>Total all hours</span>
                  <span>{{ formatHours(overtimeSummary.total_all_hours || 0) }}</span>
                </div>
                <div class="line-item">
                  <span>Month</span>
                  <span>{{ new Date().toLocaleString('default', { month: 'long' }) }}</span>
                </div>
                <div class="line-item">
                  <span>Year</span>
                  <span>{{ new Date().getFullYear() }}</span>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button @click="closeOvertimeModal" class="btn-secondary">Close</button>
            <button @click="exportOvertimeReport" class="btn-primary">📊 Export Report</button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Toast -->
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
import ActivityMonitor from '@/components/ActivityMonitor.vue';

export default {
  name: 'EmployeeAttendance',
  components: { ActivityMonitor },
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  data() {
    return {
      pageName: 'My Attendance',
      attendance: [],
      dateFrom: '',
      dateTo: '',
      statusFilter: '',
      sessionTypeFilter: '',
      today: new Date().toISOString().split('T')[0],
      todayStatus: 'absent',
      currentShift: null,
      canClockIn: false,
      canClockOut: false,
      canStartOvertime: false,
      isInOvertimeSession: false,
      shiftHasEnded: false,
      overtimeSession: null,
      overtimeSummary: null,
      stats: {
        totalHours: 0,
        regularHours: 0,
        overtimeHours: 0,
        attendanceRate: 0,
        lateDays: 0,
        workdays: 0
      },
      loading: false,
      clockingIn: false,
      clockingOut: false,
      clockingInOvertime: false,
      extendingOvertime: false,
      resetting: false,
      showResetButton: false,
      error: null,
      retryCount: 0,
      currentPage: 1,
      itemsPerPage: 25,
      statsLoaded: false,
      autoRefreshInterval: null,
      overtimeRefreshInterval: null,
      showOvertimeModal: false,
      toast: { show: false, message: '', type: 'success' }
    }
  },
  computed: {
    filteredAttendance() {
      if (!Array.isArray(this.attendance)) return []
      let filtered = [...this.attendance]
      if (this.dateFrom) filtered = filtered.filter(r => r?.date >= this.dateFrom)
      if (this.dateTo)   filtered = filtered.filter(r => r?.date <= this.dateTo)
      if (this.statusFilter) filtered = filtered.filter(r => r?.status === this.statusFilter)
      if (this.sessionTypeFilter) {
        if (this.sessionTypeFilter === 'overtime') filtered = filtered.filter(r => r?.is_overtime_session === true)
        else if (this.sessionTypeFilter === 'regular') filtered = filtered.filter(r => r?.is_overtime_session === false)
      }
      return filtered.sort((a, b) => {
        if (!a?.date || !b?.date) return 0
        const dc = new Date(b.date) - new Date(a.date)
        if (dc !== 0) return dc
        return (a.is_overtime_session ? 1 : 0) - (b.is_overtime_session ? 1 : 0)
      })
    },
    overtimeRecordsCount() { return this.filteredAttendance.filter(r => r.is_overtime_session).length },
    totalPages() { return Math.max(1, Math.ceil((this.filteredAttendance?.length || 0) / (this.itemsPerPage || 25))) },
    paginatedRecords() {
      const s = (this.currentPage - 1) * this.itemsPerPage
      return this.filteredAttendance.slice(s, s + this.itemsPerPage)
    },
    visiblePages() {
      const pages = [], max = 5
      let start = Math.max(1, this.currentPage - Math.floor(max / 2))
      let end   = Math.min(this.totalPages, start + max - 1)
      if (end - start < max - 1) start = Math.max(1, end - max + 1)
      for (let i = start; i <= end; i++) pages.push(i)
      return pages
    }
  },
  mounted() {
    this.initializeComponent()
    this.startAutoRefresh()
  },
  beforeDestroy() { this.stopAutoRefresh() },
  methods: {
    /* ── Step indicator helpers ── */
    stepDone(step) {
      switch (step) {
        case 'clockin':   return !this.canClockIn && (this.canClockOut || this.canStartOvertime || this.isInOvertimeSession || this.todayStatus === 'completed')
        case 'clockout':  return (this.canStartOvertime || this.isInOvertimeSession || this.todayStatus === 'completed') && !this.canClockOut
        case 'overtime':  return this.todayStatus === 'completed' && !this.isInOvertimeSession && !this.canStartOvertime
        default: return false
      }
    },
    stepActive(step) {
      switch (step) {
        case 'clockin':    return this.canClockIn && !this.canClockOut
        case 'clockout':   return this.canClockOut && !this.isInOvertimeSession
        case 'overtime':   return this.canStartOvertime && !this.isInOvertimeSession
        case 'otclockout': return this.isInOvertimeSession && this.canClockOut
        default: return false
      }
    },

    goToPage(page) { if (page >= 1 && page <= this.totalPages) this.currentPage = page },
    prevPage() { if (this.currentPage > 1) this.currentPage-- },
    nextPage() { if (this.currentPage < this.totalPages) this.currentPage++ },

    async initializeComponent() {
      try {
        await Promise.all([
          this.fetchAttendance(),
          this.fetchTodayStatus(),
          this.fetchOvertimeSummary()
        ])
      } catch (err) {
        this.error = 'Failed to load attendance data. Please try again.'
      }
    },
    startAutoRefresh() {
      this.autoRefreshInterval = setInterval(() => {
        if (this.isInOvertimeSession) { this.fetchTodayStatus(); this.fetchOvertimeSummary() }
      }, 30000)
      this.overtimeRefreshInterval = setInterval(() => {
        if (this.isInOvertimeSession || this.stats.overtimeHours > 0) this.fetchOvertimeSummary()
      }, 60000)
    },
    stopAutoRefresh() {
      if (this.autoRefreshInterval)    clearInterval(this.autoRefreshInterval)
      if (this.overtimeRefreshInterval) clearInterval(this.overtimeRefreshInterval)
    },
    handlePerPageChange() { this.currentPage = 1 },
    handleFilterChange()  { this.currentPage = 1; this.fetchAttendance() },

    async fetchAttendance(retry = false) {
      this.loading = true; this.error = null
      try {
        const params = {
          ...(this.dateFrom && { from: this.dateFrom }),
          ...(this.dateTo   && { to:   this.dateTo }),
          ...(this.statusFilter && { status: this.statusFilter })
        }
        const [attendanceRes, statsRes] = await Promise.all([
          axios.get('/api/employee/attendance', { params }),
          axios.get('/api/employee/attendance/stats')
        ])
        const rawData = Array.isArray(attendanceRes.data)
          ? attendanceRes.data
          : (attendanceRes.data.data || attendanceRes.data.attendance || [])

        this.attendance = rawData.map(r => ({
          id: r.id, employee_id: r.employee_id, date: r.date,
          clock_in:  r.clock_in  || r.checkIn  || r.check_in,
          clock_out: r.clock_out || r.checkOut || r.check_out,
          regular_hours:  parseFloat(r.regular_hours  || r.regularHours  || 0),
          overtime_hours: parseFloat(r.overtime_hours || r.overtimeHours || 0),
          total_hours:    parseFloat(r.total_hours    || r.totalHours    || r.hoursWorked || 0),
          break_minutes:  parseInt(r.break_minutes || r.breakMinutes || 0),
          status: r.status || 'present',
          notes: r.notes || null,
          is_overtime_session: Boolean(r.is_overtime_session || r.isOvertimeSession),
          shift_assignment_id: r.shift_assignment_id || r.shiftAssignmentId,
          overtime_session_id: r.overtime_session_id || r.overtimeSessionId
        }))

        const sd = statsRes.data.stats || statsRes.data
        this.stats = {
          totalHours:     parseFloat(sd.totalHours     || sd.total_hours     || 0),
          regularHours:   parseFloat(sd.regularHours   || sd.regular_hours   || 0),
          overtimeHours:  parseFloat(sd.overtimeHours  || sd.overtime_hours  || 0),
          attendanceRate: parseFloat(sd.attendanceRate || sd.attendance_rate || 0),
          lateDays:       parseInt(sd.lateDays  || sd.late_days   || 0),
          workdays:       parseInt(sd.workdays  || sd.working_days|| 0)
        }
        this.statsLoaded = true
      } catch (err) { this.handleApiError(err) }
      finally { this.loading = false }
    },

    async fetchTodayStatus() {
      try {
        const r = await axios.get('/api/employee/attendance/today-status')
        this.todayStatus       = r.data.status || 'absent'
        this.canClockIn        = r.data.can_clock_in || false
        this.canClockOut       = r.data.can_clock_out || false
        this.canStartOvertime  = r.data.can_start_overtime || false
        this.isInOvertimeSession = r.data.is_in_overtime_session || false
        this.shiftHasEnded     = r.data.shift_has_ended || false
        this.currentShift      = r.data.shift || null
        this.overtimeSession   = r.data.overtime_session || null
        if (this.todayStatus === 'present' && r.data.regular_attendance?.clock_out) this.showResetButton = true
      } catch (err) {
        this.todayStatus = 'absent'
      }
    },

    async fetchOvertimeSummary() {
      try {
        const d = new Date()
        const r = await axios.get('/api/employee/attendance/overtime-summary', {
          params: { month: d.getMonth() + 1, year: d.getFullYear() }
        })
        this.overtimeSummary = r.data.summary || r.data
      } catch (err) { /* silent */ }
    },

    async clockIn() {
      this.clockingIn = true
      try {
        const r = await axios.post('/api/employee/attendance/clock-in')
        this.todayStatus = 'present'; this.showResetButton = false
        this.showToast(r.data.message || 'Clocked in successfully!', 'success')
        await this.fetchAttendance(); await this.fetchTodayStatus()
      } catch (err) {
        if (err.response?.status === 422) this.showResetButton = true
        this.showToast(err.response?.data?.message || 'Failed to clock in. Please try again.', 'error')
      } finally { this.clockingIn = false }
    },

    async clockOut() {
      this.clockingOut = true
      try {
        const r = await axios.post('/api/employee/attendance/clock-out')
        this.todayStatus = 'completed'; this.showResetButton = false
        const ot = r.data.attendance?.overtime_hours || 0
        let msg = r.data.message || 'Clocked out successfully!'
        if (ot > 0) msg += ` You worked ${this.formatHours(ot)} of overtime.`
        this.showToast(msg, 'success')
        await this.fetchAttendance(); await this.fetchTodayStatus(); await this.fetchOvertimeSummary()
      } catch (err) {
        this.showToast(err.response?.data?.message || 'Failed to clock out. Please try again.', 'error')
      } finally { this.clockingOut = false }
    },

    async clockInOvertime() {
      if (!confirm('Start overtime session? This will be tracked separately from your regular hours.')) return
      this.clockingInOvertime = true
      try {
        const r = await axios.post('/api/employee/attendance/clock-in-overtime')
        this.isInOvertimeSession = true; this.canStartOvertime = false
        this.canClockOut = true; this.todayStatus = 'present'
        this.overtimeSession = r.data.overtime_session || r.data.attendance || null
        this.showToast(r.data.message || 'Overtime session started!', 'success')
        await Promise.all([this.fetchAttendance(), this.fetchTodayStatus(), this.fetchOvertimeSummary()])
      } catch (err) {
        let msg = 'Failed to start overtime. '
        if (err.response?.status === 422 || err.response?.status === 400) {
          msg = err.response.data.message || 'Cannot start overtime at this time.'
        } else { msg += err.response?.data?.message || 'Please try again.' }
        this.showToast(msg, 'error')
      } finally { this.clockingInOvertime = false }
    },

    async forceResetStatus() {
      if (!confirm('This will auto-close any open attendance records. Continue?')) return
      this.resetting = true
      try {
        const r = await axios.post('/api/employee/attendance/force-reset')
        this.showResetButton = false; this.isInOvertimeSession = false; this.canStartOvertime = false
        this.showToast(r.data.message || 'Attendance status has been reset.', 'success')
        await this.fetchTodayStatus(); await this.fetchAttendance()
      } catch (err) {
        this.showToast(err.response?.data?.message || 'Failed to reset status.', 'error')
      } finally { this.resetting = false }
    },

    async viewOvertimeSummary() { await this.fetchOvertimeSummary(); this.showOvertimeModal = true },
    closeOvertimeModal() { this.showOvertimeModal = false },
    async exportOvertimeReport() { this.showToast('Overtime report is being generated.', 'success') },

    resetFilters() {
      this.dateFrom = ''; this.dateTo = ''; this.statusFilter = ''; this.sessionTypeFilter = ''
      this.currentPage = 1; this.fetchAttendance()
    },
    retryFetch() {
      this.retryCount++
      if (this.retryCount <= 3) this.fetchAttendance(true)
      else this.error = 'Max retries exceeded. Please refresh the page.'
    },
    handleApiError(err) {
      if (err.code === 'ERR_NETWORK' || err.message?.includes('Network Error')) { this.error = 'Network error. Check your connection.'; return }
      if (err.response?.status === 401) { this.authStore.clearAuth(); this.$router.push({ name: 'login' }); return }
      if (err.response?.status === 403) { this.error = 'Access denied.'; return }
      this.error = err.response?.data?.message || 'An unexpected error occurred.'
    },
    showToast(message, type = 'success') {
      this.toast = { show: true, message, type }
      setTimeout(() => { this.toast.show = false }, 3000)
    },
    getStatusClass(status) {
      return { present: 'success', completed: 'info', absent: 'danger', late: 'warning', early_leave: 'warning' }[status] || 'neutral'
    },
    getStatusBadgeClass(status) {
      return { present: 'status-present', completed: 'status-completed', absent: 'status-absent', late: 'status-late' }[status] || 'status-absent'
    },
    formatTodayStatus(status) {
      if (this.isInOvertimeSession) return 'Overtime In Progress'
      return { present: 'In Progress', completed: 'Completed', absent: 'Absent' }[status] || status
    },
    formatStatus(status) {
      return { present: 'Present', absent: 'Absent', late: 'Late', completed: 'Completed', early_leave: 'Early Leave' }[status] || status
    },
    formatShiftType(type) { return { morning: 'Morning', day: 'Day', evening: 'Evening', night: 'Night' }[type] || type },
    formatHours(hours) {
      if (!hours && hours !== 0) return '0h 0m'
      const h = Math.floor(hours), m = Math.round((hours % 1) * 60)
      return `${h}h ${m}m`
    },
    formatDuration(minutes) {
      if (!minutes) return ''
      return `(${Math.floor(minutes / 60)}h ${minutes % 60}m)`
    },
    formatTimeDisplay(time) {
      if (!time) return '—'
      try {
        let ts = time
        if (ts.includes('T')) {
          const d = new Date(ts)
          const h = d.getHours(), m = d.getMinutes()
          const ap = h >= 12 ? 'PM' : 'AM'
          return `${h % 12 || 12}:${m.toString().padStart(2,'0')} ${ap}`
        }
        if (ts.includes(':')) {
          const [hh, mm] = ts.split(':').map(Number)
          return `${hh % 12 || 12}:${mm.toString().padStart(2,'0')} ${hh >= 12 ? 'PM' : 'AM'}`
        }
        return time
      } catch { return time }
    },
    formatDate(date) {
      if (!date) return 'N/A'
      try { return new Date(date).toLocaleDateString('en-ZM', { year: 'numeric', month: 'short', day: 'numeric', weekday: 'short' }) }
      catch { return date }
    }
  }
}
</script>

<style scoped>
/* ── Base ──────────────────────────────────────────── */
.attendance-view {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
  padding: 1.5rem 2rem 3rem;
  max-width: 1400px;
  margin: 0 auto;
}

/* ── Header Card ─────────────────────────────────── */
.dashboard-header-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem 1.75rem 1rem 1.75rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0;
  margin-bottom: 1.25rem;
  position: relative;
  overflow: hidden;
}

.header-card-accent {
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
}

.user-greeting {
  display: flex; justify-content: space-between; align-items: center;
  gap: 1.5rem; margin-bottom: 1rem;
}
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
.header-actions {
  display: flex; align-items: center; gap: 0.75rem;
  flex-shrink: 0; flex-wrap: wrap;
}

/* Status badge */
.today-status-badge {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.4rem 1rem; border-radius: 50px;
  font-size: 0.85rem; font-weight: 600;
  background: #f8fafc; border: 1px solid #e2e8f0;
}
.today-status-badge.status-present  { background: #d1fae5; border-color: #a7f3d0; color: #065f46; }
.today-status-badge.status-completed{ background: #dbeafe; border-color: #bfdbfe; color: #1e40af; }
.today-status-badge.status-absent   { background: #fee2e2; border-color: #fecaca; color: #991b1b; }
.today-status-badge.status-late     { background: #fef3c7; border-color: #fde68a; color: #92400e; }
.status-dot-large { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }
.overtime-badge-header {
  background: #f59e0b; color: white;
  padding: 0.2rem 0.5rem; border-radius: 20px;
  font-size: 0.7rem; font-weight: 700;
}
.shift-info-header {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.4rem 1rem; background: #f1f5f9;
  border-radius: 50px; border: 1px solid #e2e8f0;
  font-size: 0.85rem; color: #334155;
}
.shift-type {
  background: #e2e8f0; padding: 0.2rem 0.5rem;
  border-radius: 4px; font-size: 0.7rem; font-weight: 600; color: #475569;
}
.overtime-info-header {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.4rem 1rem;
  background: rgba(245,158,11,0.1); border-radius: 50px;
  border: 1px solid rgba(245,158,11,0.3);
  font-size: 0.85rem; color: #f59e0b;
}
.overtime-duration-header {
  background: rgba(0,0,0,0.05); padding: 0.2rem 0.5rem;
  border-radius: 4px; font-size: 0.7rem; color: #92400e;
}

/* Quick stats bar */
.header-quick-stats {
  display: flex; align-items: center; justify-content: space-around;
  padding: 1rem 0 0.5rem; border-top: 1px solid #e2e8f0; margin-top: 0.5rem;
}
.quick-stat-item {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.5rem 1rem; border-radius: 10px; transition: background 0.2s;
}
.quick-stat-item:hover { background: #f8fafc; }
.stat-icon { font-size: 1.25rem; }
.stat-info { display: flex; flex-direction: column; }
.stat-value { font-size: 1rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
.stat-value.overtime-text { color: #f59e0b; }
.stat-label { font-size: 0.7rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
.quick-stat-divider { width: 1px; height: 30px; background: #e2e8f0; }

/* ── Buttons ─────────────────────────────────────── */
.btn-primary {
  display: flex; align-items: center; gap: 0.4rem;
  background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;
  border: none; padding: 0.5rem 1.25rem; border-radius: 8px;
  font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: inherit;
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

.btn-outline-warning {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.35rem 0.9rem; background: white; border: 1px solid #f59e0b;
  color: #f59e0b; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-outline-warning:hover { background: #f59e0b; color: white; }

.btn-outline-primary {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.4rem 1rem; background: white; border: 1px solid #6366f1;
  color: #6366f1; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-outline-primary:hover { background: #6366f1; color: white; }

.btn-secondary {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.45rem 1rem; background: #f1f5f9; border: 1px solid #e2e8f0;
  color: #334155; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-secondary:hover { background: #e2e8f0; }

/* ── Smart Clock Action Card ─────────────────────── */
.clock-action-card {
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05);
  padding: 1.5rem 2rem;
  display: flex;
  align-items: center;
  gap: 2rem;
}

/* Step Indicator */
.clock-steps {
  display: flex;
  align-items: center;
  gap: 0;
  flex-shrink: 0;
}

.clock-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.4rem;
}

.step-circle {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #f1f5f9;
  border: 2px solid #e2e8f0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.72rem;
  font-weight: 700;
  color: #94a3b8;
  transition: all 0.3s ease;
}

.clock-step.active .step-circle {
  background: white;
  border-color: #6366f1;
  color: #6366f1;
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
}

.clock-step.done .step-circle {
  background: #10b981;
  border-color: #10b981;
  color: white;
}

.step-label {
  font-size: 0.65rem;
  font-weight: 600;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  white-space: nowrap;
  text-align: center;
}

.clock-step.active .step-label { color: #6366f1; }
.clock-step.done .step-label   { color: #10b981; }

.step-line {
  width: 40px;
  height: 2px;
  background: #e2e8f0;
  margin: 0 0.25rem;
  margin-bottom: 1rem; /* push down to align with circles */
  border-radius: 1px;
  transition: background 0.3s ease;
  flex-shrink: 0;
}
.step-line.done { background: #10b981; }

/* Main Action Button */
.clock-action-center {
  flex: 1;
  display: flex;
  justify-content: flex-end;
}

/* Shared big button base */
.btn-clock-main {
  display: inline-flex;
  align-items: center;
  gap: 1rem;
  padding: 0.875rem 1.75rem;
  border-radius: 12px;
  border: none;
  cursor: pointer;
  transition: all 0.25s ease;
  font-family: inherit;
  min-width: 240px;
}
.btn-clock-main:disabled {
  opacity: 0.65;
  cursor: not-allowed;
  transform: none !important;
  box-shadow: none !important;
}

.btn-clock-icon {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  background: rgba(255,255,255,0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  font-size: 1.25rem;
}

.ot-icon { font-size: 1.25rem; line-height: 1; }

.btn-clock-text {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.2rem;
}

.btn-clock-label {
  font-size: 0.95rem;
  font-weight: 700;
  color: white;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.btn-clock-sub {
  font-size: 0.72rem;
  color: rgba(255,255,255,0.75);
  font-weight: 500;
}

/* Clock In — green */
.btn-clock-in-main {
  background: linear-gradient(135deg, #22c55e, #16a34a);
  box-shadow: 0 4px 14px rgba(34,197,94,0.3);
}
.btn-clock-in-main:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(34,197,94,0.4);
}

/* Clock Out — red */
.btn-clock-out-main {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  box-shadow: 0 4px 14px rgba(239,68,68,0.3);
}
.btn-clock-out-main:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(239,68,68,0.4);
}

/* Start Overtime — amber */
.btn-clock-overtime-main {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  box-shadow: 0 4px 14px rgba(245,158,11,0.3);
}
.btn-clock-overtime-main:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(245,158,11,0.4);
}

/* OT Clock Out — orange */
.btn-clock-otout-main {
  background: linear-gradient(135deg, #f97316, #ea580c);
  box-shadow: 0 4px 14px rgba(249,115,22,0.3);
}
.btn-clock-otout-main:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(249,115,22,0.4);
}

/* Inline badges inside button label */
.badge-ready-inline {
  background: rgba(255,255,255,0.3);
  padding: 0.1rem 0.4rem;
  border-radius: 6px;
  font-size: 0.65rem;
  font-weight: 700;
}

.live-indicator-inline {
  background: rgba(255,255,255,0.25);
  padding: 0.1rem 0.4rem;
  border-radius: 6px;
  font-size: 0.65rem;
  font-weight: 700;
  animation: blink 1s infinite;
}

/* All-done state */
.clock-done-state {
  display: inline-flex;
  align-items: center;
  gap: 1rem;
  padding: 0.875rem 1.75rem;
  border-radius: 12px;
  background: #f0fdf4;
  border: 1.5px solid #bbf7d0;
  min-width: 240px;
}

.clock-done-icon {
  width: 42px; height: 42px; border-radius: 10px;
  background: #10b981; display: flex; align-items: center; justify-content: center;
  color: white; flex-shrink: 0;
}

.clock-done-text { display: flex; flex-direction: column; gap: 0.2rem; }
.clock-done-label { font-size: 0.95rem; font-weight: 700; color: #065f46; }
.clock-done-sub   { font-size: 0.72rem; color: #16a34a; }

/* Spinners */
.spinner-medium {
  display: inline-block;
  width: 18px; height: 18px;
  border: 2.5px solid rgba(255,255,255,0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

.spinner-small {
  display: inline-block;
  width: 12px; height: 12px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

/* Animations */
@keyframes spin  { to { transform: rotate(360deg); } }
@keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
@keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.03); } 100% { transform: scale(1); } }

.pulse-effect { animation: pulse 2s ease-in-out infinite; }

/* ── Dashboard Content ───────────────────────────── */
.dashboard-content { display: flex; flex-direction: column; gap: 1.5rem; }

/* ── Overtime Banner ─────────────────────────────── */
.overtime-banner {
  background: linear-gradient(135deg, rgba(245,158,11,0.1), rgba(245,158,11,0.2));
  border: 1px solid rgba(245,158,11,0.3);
  border-radius: 12px; padding: 1rem 1.5rem;
  display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;
}
.overtime-banner-icon { font-size: 1.75rem; }
.overtime-banner-content {
  flex: 1; display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;
}
.overtime-banner-title   { font-weight: 600; color: #92400e; font-size: 0.9rem; }
.overtime-banner-hours   { font-size: 1.25rem; font-weight: 700; color: #f59e0b; }
.overtime-banner-sessions,
.overtime-banner-days    { color: #6b7280; font-size: 0.875rem; }

/* ── Controls Section ────────────────────────────── */
.controls-section {
  background: white; border-radius: 16px;
  border: 1px solid #e2e8f0; padding: 1.25rem 1.5rem;
}
.controls-bar {
  display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; flex-wrap: wrap;
}
.filters-row { display: flex; gap: 0.875rem; flex-wrap: wrap; }
.filter-group { display: flex; flex-direction: column; gap: 0.3rem; }
.filter-group label { font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
.filter-input, .filter-select {
  padding: 0.45rem 0.875rem; border: 1px solid #e2e8f0;
  border-radius: 8px; background: #f8fafc; color: #334155;
  font-size: 0.875rem; font-weight: 500; transition: all 0.2s; font-family: inherit;
}
.filter-input:focus, .filter-select:focus {
  outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}
.filter-select {
  padding-right: 2rem; appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 0.75rem center;
}
.filter-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; }

/* ── Table Section ───────────────────────────────── */
.table-section {
  background: white; border-radius: 16px;
  border: 1px solid #e2e8f0; padding: 1.5rem;
}
.table-header {
  display: flex; justify-content: space-between; align-items: center;
  margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem;
}
.table-header h2 { font-size: 1.1rem; font-weight: 600; margin: 0; color: #334155; }
.table-meta { display: flex; gap: 0.75rem; align-items: center; }
.records-count {
  font-size: 0.78rem; font-weight: 700; color: #64748b;
  background: #f1f5f9; padding: 0.2rem 0.7rem; border-radius: 9999px;
}
.overtime-badge {
  background: rgba(245,158,11,0.1); color: #f59e0b;
  padding: 0.2rem 0.7rem; border-radius: 9999px; font-size: 0.78rem; font-weight: 700;
}
.table-container { border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0; }
.attendance-table-header {
  display: grid;
  grid-template-columns: 1fr 0.8fr 0.8fr 0.8fr 0.8fr 0.6fr 0.6fr 0.6fr 1.2fr;
  padding: 0.75rem 1rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0;
  font-size: 0.7rem; font-weight: 700; color: #64748b;
  text-transform: uppercase; letter-spacing: 0.05em; gap: 0.75rem;
}
.attendance-table-row {
  display: grid;
  grid-template-columns: 1fr 0.8fr 0.8fr 0.8fr 0.8fr 0.6fr 0.6fr 0.6fr 1.2fr;
  padding: 0.75rem 1rem; align-items: center; gap: 0.75rem;
  border-bottom: 1px solid #f1f5f9; background: white; transition: background 0.15s;
}
.attendance-table-row:hover { background: #f8fafc; }
.attendance-table-row:last-child { border-bottom: none; }
.date-cell .date-text { font-weight: 600; color: #334155; font-size: 0.82rem; }
.type-badge {
  display: inline-flex; align-items: center;
  padding: 0.25rem 0.75rem; border-radius: 6px;
  font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px;
}
.type-overtime { background: rgba(245,158,11,0.1); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3); }
.type-regular  { background: rgba(59,130,246,0.1);  color: #3b82f6; border: 1px solid rgba(59,130,246,0.3); }
.status-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 0.25rem 0.65rem; border-radius: 9999px;
  font-size: 0.7rem; font-weight: 700; white-space: nowrap;
}
.dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
.status-badge.success { background: #d1fae5; color: #065f46; }
.status-badge.warning { background: #fef3c7; color: #92400e; }
.status-badge.danger  { background: #fee2e2; color: #991b1b; }
.status-badge.info    { background: #dbeafe; color: #1e40af; }
.status-badge.neutral { background: #f1f5f9; color: #64748b; }
.time-cell {
  font-size: 0.82rem; color: #475569; font-weight: 500;
  font-family: 'Monaco', 'Courier New', monospace;
}
.hours-badge {
  display: inline-block; padding: 0.2rem 0.55rem;
  background: #f1f5f9; color: #475569; border-radius: 6px;
  font-size: 0.75rem; font-weight: 700; border: 1px solid #e2e8f0;
}
.overtime-hours { background: rgba(245,158,11,0.1); color: #f59e0b; border-color: rgba(245,158,11,0.3); }
.total-hours    { background: rgba(99,102,241,0.1);  color: #4f46e5; border-color: rgba(99,102,241,0.3); }
.overtime-star  { margin-left: 0.25rem; animation: blink 1.5s infinite; }
.notes-cell .notes-text {
  color: #64748b; font-size: 0.8rem;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px;
}
.text-right { text-align: right; }

/* ── Pagination ──────────────────────────────────── */
.pagination-bar {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1rem 0 0; border-top: 1px solid #f1f5f9; margin-top: 1.25rem;
  flex-wrap: wrap; gap: 1rem;
}
.pagination-info { font-size: 0.82rem; color: #64748b; }
.pagination-info strong { color: #1e293b; font-weight: 700; }
.pagination-controls { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.page-btn {
  display: flex; align-items: center; gap: 0.3rem;
  padding: 0.35rem 0.875rem; background: white; border: 1px solid #e2e8f0;
  border-radius: 6px; font-size: 0.78rem; font-weight: 600; color: #475569;
  cursor: pointer; transition: all 0.15s; font-family: inherit;
}
.page-btn:hover:not(:disabled) { border-color: #a5b4fc; color: #4f46e5; background: #eff6ff; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.pagination-numbers { display: flex; gap: 0.25rem; }
.page-number {
  min-width: 2rem; height: 2rem; display: flex; align-items: center; justify-content: center;
  border: 1px solid #e2e8f0; background: white; color: #475569;
  border-radius: 6px; font-size: 0.78rem; font-weight: 600; cursor: pointer; transition: all 0.15s;
}
.page-number:hover { border-color: #a5b4fc; color: #4f46e5; background: #eff6ff; }
.page-number.active { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border-color: transparent; }
.per-page-select {
  padding: 0.35rem 1rem; border: 1px solid #e2e8f0; border-radius: 6px;
  background: white; color: #475569; font-size: 0.78rem; font-weight: 600;
  cursor: pointer; font-family: inherit;
}
.per-page-select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

/* ── States ──────────────────────────────────────── */
.spinner {
  width: 40px; height: 40px; border: 3px solid #e2e8f0; border-top-color: #6366f1;
  border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;
}
.empty-state {
  text-align: center; padding: 3rem 2rem;
  display: flex; flex-direction: column; align-items: center; gap: 0.875rem; color: #94a3b8;
}
.empty-state.error-state { background: #fef2f2; border-radius: 12px; border: 1px solid #fecaca; }
.empty-state.error-state p { color: #991b1b; }
.empty-state p { margin: 0; font-size: 0.875rem; color: #64748b; }
.empty-subtitle { color: #94a3b8; font-size: 0.8rem; }

/* ── Modal ───────────────────────────────────────── */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,31,91,0.5);
  backdrop-filter: blur(4px); z-index: 100;
  display: flex; justify-content: center; align-items: center; padding: 1rem;
}
.modal-container {
  background: white; width: 100%; max-width: 500px; max-height: 90vh;
  border-radius: 16px; box-shadow: 0 25px 60px rgba(0,31,91,0.2);
  display: flex; flex-direction: column; overflow: hidden;
  border: 1px solid #e2e8f0; animation: slideUp 0.25s ease-out;
}
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
  font-size: 1.5rem; color: white; border: 1.5px solid rgba(255,255,255,0.25);
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
.detail-column { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1.125rem; }
.modal-section-title {
  display: flex; align-items: center; gap: 0.5rem;
  font-size: 0.82rem; font-weight: 700; color: #334155;
  margin: 0 0 0.875rem; padding-bottom: 0.6rem; border-bottom: 1px solid #e2e8f0;
}
.col-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.col-dot.orange { background: #f59e0b; }
.line-items { display: flex; flex-direction: column; }
.line-item {
  display: flex; justify-content: space-between; align-items: center;
  padding: 0.45rem 0.5rem; border-radius: 6px; font-size: 0.82rem;
}
.line-item:hover { background: white; }
.line-item span:first-child { color: #64748b; }
.line-item span:last-child  { font-weight: 600; color: #1e293b; }
.modal-footer {
  padding: 1.125rem 1.75rem; border-top: 1px solid #f1f5f9;
  background: #f8fafc; display: flex; justify-content: flex-end; gap: 0.75rem; flex-shrink: 0;
}

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
@media (max-width: 1024px) {
  .clock-action-card { flex-direction: column; align-items: stretch; gap: 1.5rem; }
  .clock-action-center { justify-content: center; }
  .user-greeting { flex-direction: column; align-items: flex-start; }
  .header-quick-stats { flex-wrap: wrap; gap: 0.5rem; }
}

@media (max-width: 768px) {
  .attendance-view { padding: 1rem; }
  .header-actions  { width: 100%; }
  .filters-row, .filter-group, .filter-input, .filter-select { width: 100%; flex-direction: column; }
  .filter-actions  { width: 100%; }
  .filter-actions button { flex: 1; }
  .attendance-table-header { display: none; }
  .attendance-table-row { grid-template-columns: 1fr; gap: 0.5rem; padding: 1rem; border-bottom: 2px solid #f1f5f9; }
  .modal-footer { flex-direction: column-reverse; }
  .modal-footer button { width: 100%; justify-content: center; }
  .quick-stat-divider { display: none; }
  .header-quick-stats { justify-content: space-between; }
  .clock-steps { display: none; } /* hide on small screens */
  .btn-clock-main { min-width: unset; width: 100%; }
  .clock-done-state { width: 100%; }
}

@media (max-width: 480px) {
  .pagination-bar { flex-direction: column; align-items: stretch; }
  .pagination-controls { justify-content: center; }
  .per-page-select { width: 100%; }
  .header-quick-stats { flex-direction: column; align-items: flex-start; }
  .quick-stat-item { width: 100%; padding: 0.5rem 0; }
}
</style>