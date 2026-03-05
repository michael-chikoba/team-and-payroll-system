<template>
  <div class="attendance-view">

    <!-- ── Confirm / Alert Modal ──────────────────── -->
    <transition name="modal-fade">
      <div v-if="modalVisible" class="modal-overlay" @click.self="cancelAction">
        <div class="modal-container modal-sm">
          <div class="modal-header" :class="modalIsSuccess ? 'header-success' : modalIsError ? 'header-error' : 'header-confirm'">
            <div class="modal-title-wrap">
              <div class="modal-avatar">
                <span v-if="modalIsSuccess">✓</span>
                <span v-else-if="modalIsError">!</span>
                <span v-else>?</span>
              </div>
              <div>
                <h3 class="modal-name">{{ modalTitle }}</h3>
              </div>
            </div>
            <button @click="cancelAction" class="modal-close">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
          </div>
          <div class="modal-body">
            <p class="modal-msg">{{ modalMessage }}</p>
          </div>
          <div class="modal-footer">
            <button v-if="modalIsConfirm" @click="cancelAction" class="btn-secondary">Cancel</button>
            <button @click="confirmAction" class="btn-primary" :class="{ 'btn-success-solid': modalIsSuccess, 'btn-danger-solid': modalIsError }">
              {{ modalButtonText }}
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- ── Employee History Modal ──────────────────── -->
    <transition name="modal-fade">
      <div v-if="historyModal.visible" class="modal-overlay" @click.self="closeHistoryModal">
        <div class="modal-container modal-lg">
          <div class="modal-header header-confirm">
            <div class="modal-title-wrap">
              <div class="modal-avatar" style="background: #3b82f6;">
                {{ getInitials(historyModal.employee?.full_name || '') }}
              </div>
              <div>
                <h3 class="modal-name">{{ historyModal.employee?.full_name }}</h3>
                <p class="modal-sub">{{ historyModal.employee?.employee_id }} · {{ historyModal.employee?.department }}</p>
              </div>
            </div>
            <button @click="closeHistoryModal" class="modal-close">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
          </div>

          <!-- History Filters -->
          <div class="history-filters">
            <div class="filter-group">
              <label>From</label>
              <input type="date" v-model="historyModal.dateFrom" @change="fetchEmployeeHistory()" class="filter-select" :max="historyModal.dateTo || today" />
            </div>
            <div class="filter-group">
              <label>To</label>
              <input type="date" v-model="historyModal.dateTo" @change="fetchEmployeeHistory()" class="filter-select" :min="historyModal.dateFrom" :max="today" />
            </div>
            <div class="history-summary-chips" v-if="historyModal.summary">
              <span class="chip green">✓ {{ historyModal.summary.present_days }} Present</span>
              <span class="chip red">✗ {{ historyModal.summary.absent_days }} Absent</span>
              <span class="chip amber">⏱ {{ historyModal.summary.late_days }} Late</span>
              <span class="chip blue">{{ historyModal.summary.total_hours }}h Total</span>
            </div>
          </div>

          <div class="modal-body history-body">
            <!-- Loading -->
            <div v-if="historyModal.loading" class="empty-state">
              <div class="spinner"></div>
              <p>Loading attendance history…</p>
            </div>

            <!-- Error -->
            <div v-else-if="historyModal.error" class="empty-state">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
              <p>{{ historyModal.error }}</p>
              <button @click="fetchEmployeeHistory()" class="btn-primary">Retry</button>
            </div>

            <!-- Empty -->
            <div v-else-if="historyModal.records.length === 0 && !historyModal.loading" class="empty-state">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
              <p>No attendance records found for this period.</p>
            </div>

            <!-- History Table -->
            <div v-else class="history-table-wrap">
              <div class="history-table-header history-grid">
                <div>Date</div>
                <div>Status</div>
                <div class="text-right">Clock In</div>
                <div class="text-right">Clock Out</div>
                <div class="text-right">Hours</div>
                <div>Notes</div>
              </div>
              <div
                v-for="record in historyModal.records"
                :key="record.id || record.date"
                class="history-row history-grid"
              >
                <div class="history-date">
                  <span class="date-main">{{ formatHistoryDate(record.date) }}</span>
                  <span class="date-day">{{ getDayName(record.date) }}</span>
                </div>
                <div>
                  <span :class="['status-badge', getStatusClass(record.status)]">
                    <span class="dot"></span>{{ formatStatus(record.status) }}
                  </span>
                </div>
                <div class="text-right font-mono">{{ safeFormatTime(record.clock_in || record.clock_in_time) }}</div>
                <div class="text-right font-mono">{{ safeFormatTime(record.clock_out || record.clock_out_time) }}</div>
                <div class="text-right font-mono text-success">{{ formatHours(record.hours_worked || record.total_hours) }}</div>
                <div class="text-muted text-sm">{{ record.notes || record.remarks || '—' }}</div>
              </div>
            </div>

            <!-- Pagination -->
            <div v-if="historyModal.pagination && historyModal.pagination.last_page > 1" class="history-pagination">
              <button
                @click="fetchEmployeeHistory(historyModal.pagination.current_page - 1)"
                :disabled="historyModal.pagination.current_page <= 1"
                class="page-btn"
              >← Prev</button>
              <span class="page-info">
                Page {{ historyModal.pagination.current_page }} of {{ historyModal.pagination.last_page }}
                ({{ historyModal.pagination.total }} records)
              </span>
              <button
                @click="fetchEmployeeHistory(historyModal.pagination.current_page + 1)"
                :disabled="historyModal.pagination.current_page >= historyModal.pagination.last_page"
                class="page-btn"
              >Next →</button>
            </div>
          </div>

          <div class="modal-footer">
            <button @click="exportHistoryCSV" class="btn-outline export" v-if="historyModal.records.length > 0">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
              Export CSV
            </button>
            <button @click="closeHistoryModal" class="btn-secondary">Close</button>
          </div>
        </div>
      </div>
    </transition>

    <!-- ── Page Header Card ───────────────────────── -->
    <div class="dashboard-header-card">
      <div class="header-card-accent"></div>
      <div class="user-greeting">
        <div class="avatar-section">
          <div class="avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
          </div>
          <div class="user-info">
            <h1 class="greeting">Team Attendance Monitor</h1>
            <p class="subtitle">
              Monitoring attendance for {{ safeFormatDate(selectedDate) }}
              <span v-if="selectedCountry !== 'all' || selectedBusiness !== 'all'">
                ·
                <span v-if="selectedCountry !== 'all'">{{ getCountryName(selectedCountry) }}</span>
                <span v-if="selectedBusiness !== 'all'"> · {{ getBusinessName(selectedBusiness) }}</span>
              </span>
            </p>
            <div class="role-meta">
              <span class="role-badge">Admin View</span>
            </div>
          </div>
        </div>

        <div class="header-actions">
          <button @click="fetchAllData" class="btn-outline" :disabled="loading">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
            {{ loading ? 'Loading…' : 'Refresh' }}
          </button>
          <button @click="exportToCSV" class="btn-outline export" v-if="filteredData.length > 0">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Export CSV
          </button>
        </div>
      </div>
    </div>

    <div class="dashboard-content">

      <!-- ── Metrics Cards ─────────────────────────── -->
      <div class="section-container" v-if="summaryData && !loading">
        <h2 class="section-title">Overview</h2>
        <div class="stats-grid">
          <div class="stat-card" style="--accent:#3b82f6;">
            <div class="stat-icon-wrap" style="background:rgba(59,130,246,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
            </div>
            <div class="stat-info">
              <span class="stat-label">Total Employees</span>
              <div class="stat-number">{{ summaryData.total_employees }}</div>
            </div>
          </div>
          <div class="stat-card" style="--accent:#10b981;">
            <div class="stat-icon-wrap" style="background:rgba(16,185,129,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <div class="stat-info">
              <span class="stat-label">Present</span>
              <div class="stat-number">{{ summaryData.present_count }}</div>
            </div>
          </div>
          <div class="stat-card" style="--accent:#ef4444;">
            <div class="stat-icon-wrap" style="background:rgba(239,68,68,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
            </div>
            <div class="stat-info">
              <span class="stat-label">Absent</span>
              <div class="stat-number">{{ summaryData.absent_count }}</div>
            </div>
          </div>
          <div class="stat-card" style="--accent:#f59e0b;">
            <div class="stat-icon-wrap" style="background:rgba(245,158,11,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <div class="stat-info">
              <span class="stat-label">Late</span>
              <div class="stat-number">{{ summaryData.late_count }}</div>
            </div>
          </div>
          <div class="stat-card" style="--accent:#8b5cf6;">
            <div class="stat-icon-wrap" style="background:rgba(139,92,246,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
            </div>
            <div class="stat-info">
              <span class="stat-label">Attendance Rate</span>
              <div class="stat-number">{{ summaryData.attendance_rate }}%</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Department Cards ───────────────────────── -->
      <div class="section-container" v-if="departmentStats.length > 0 && !loading">
        <h2 class="section-title">Department Breakdown</h2>
        <div class="quick-actions-grid">
          <div v-for="dept in departmentStats" :key="dept.name" class="dept-card">
            <div class="dept-card-head">
              <span class="dept-name">{{ dept.name }}</span>
              <span class="count-badge">{{ dept.total }}</span>
            </div>
            <div class="dept-metrics">
              <div class="dept-metric"><span class="dept-val green">{{ dept.present }}</span><span class="dept-lbl">Present</span></div>
              <div class="dept-metric"><span class="dept-val red">{{ dept.absent }}</span><span class="dept-lbl">Absent</span></div>
              <div class="dept-metric"><span class="dept-val amber">{{ dept.late }}</span><span class="dept-lbl">Late</span></div>
              <div class="dept-metric"><span class="dept-val">{{ dept.rate }}%</span><span class="dept-lbl">Rate</span></div>
            </div>
            <div class="dept-progress-bar">
              <div class="dept-progress-fill" :style="{ width: dept.rate + '%' }"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Employees Table Section ────────────────── -->
      <div class="section-container">
        <!-- Controls Bar -->
        <div class="controls-bar">
          <div class="filters-row">
            <div class="filter-group">
              <label>Date</label>
              <input type="date" v-model="selectedDate" @change="fetchAllData" :max="today" class="filter-select" :disabled="loading" />
            </div>
            <div class="filter-group" v-if="availableCountries.length > 0">
              <label>Country</label>
              <div class="select-wrap">
                <select v-model="selectedCountry" @change="onCountryChange" class="filter-select" :disabled="loadingFilters">
                  <option value="all">All Countries</option>
                  <option v-for="c in availableCountries" :key="c.id" :value="c.id">{{ c.name }} ({{ c.employee_count || 0 }})</option>
                </select>
                <span class="select-caret">▾</span>
              </div>
            </div>
            <div class="filter-group">
              <label>Business</label>
              <div class="select-wrap">
                <select v-model="selectedBusiness" @change="onBusinessChange" class="filter-select" :disabled="loadingFilters">
                  <option value="all">All Businesses</option>
                  <option v-for="b in availableBusinesses" :key="b.id" :value="b.id">{{ b.name }} ({{ b.employee_count || 0 }})</option>
                </select>
                <span class="select-caret">▾</span>
              </div>
            </div>
            <div class="filter-group">
              <label>Department</label>
              <div class="select-wrap">
                <select v-model="selectedDepartment" @change="onDepartmentChange" class="filter-select" :disabled="loading">
                  <option value="all">All Departments</option>
                  <option v-for="dept in departments" :key="dept" :value="dept">{{ dept }}</option>
                </select>
                <span class="select-caret">▾</span>
              </div>
            </div>
          </div>
          <div class="controls-right">
            <span class="records-count" v-if="!loading">{{ filteredData.length }} record{{ filteredData.length !== 1 ? 's' : '' }}</span>
            <div class="layout-toggle">
              <button @click="setLayout('grid')" :class="['layout-btn', { active: layout === 'grid' }]" title="Grid View">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
              </button>
              <button @click="setLayout('table')" :class="['layout-btn', { active: layout === 'table' }]" title="Table View">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Status Filter Tabs -->
        <div class="status-tabs" v-if="!loading && attendanceData.length > 0">
          <button @click="filterStatus = 'all'" :class="['tab-pill', { active: filterStatus === 'all' }]">All <span class="pill-count">{{ allCount }}</span></button>
          <button @click="filterStatus = 'present'" :class="['tab-pill', { active: filterStatus === 'present' }]">Present <span class="pill-count">{{ presentCount }}</span></button>
          <button @click="filterStatus = 'absent'" :class="['tab-pill', { active: filterStatus === 'absent' }]">Absent <span class="pill-count">{{ absentCount }}</span></button>
          <button @click="filterStatus = 'late'" :class="['tab-pill', { active: filterStatus === 'late' }]">Late <span class="pill-count">{{ lateCount }}</span></button>
        </div>

        <!-- Loading -->
        <div v-if="loading || loadingFilters" class="empty-state">
          <div class="spinner"></div>
          <p>{{ loadingFilters ? 'Loading filter options…' : 'Loading attendance data…' }}</p>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          <p>{{ error }}</p>
          <button @click="retryFetch" class="btn-primary">Try Again</button>
        </div>

        <!-- Grid Layout -->
        <div v-else-if="layout === 'grid' && filteredData.length > 0" class="employees-grid">
          <div v-for="employee in filteredData" :key="employee.id" class="employee-card">
            <div class="emp-card-head">
              <div class="emp-avatar" style="background: #3b82f6;">{{ getInitials(employee.full_name) }}</div>
              <div class="emp-card-info">
                <div class="emp-card-name">{{ employee.full_name }}</div>
                <div class="emp-card-meta">
                  <span class="emp-card-id">{{ employee.employee_id }}</span>
                  <span class="emp-card-dept">{{ employee.department }}</span>
                </div>
                <div class="emp-card-pos">{{ employee.position }}</div>
                <div class="emp-card-loc" v-if="employee.country_name || employee.business_name">
                  <span v-if="employee.country_name" class="loc-tag">🌍 {{ employee.country_name }}</span>
                  <span v-if="employee.business_name" class="loc-tag">🏢 {{ employee.business_name }}</span>
                </div>
              </div>
              <span :class="['status-badge', getStatusClass(employee.status)]"><span class="dot"></span>{{ formatStatus(employee.status) }}</span>
            </div>
            <div class="emp-card-times">
              <div class="time-item"><span class="time-lbl">Clock In</span><span class="time-val">{{ safeFormatTime(employee.clock_in_time) }}</span></div>
              <div class="time-divider"></div>
              <div class="time-item"><span class="time-lbl">Clock Out</span><span class="time-val">{{ safeFormatTime(employee.clock_out_time) }}</span></div>
              <div class="time-divider"></div>
              <div class="time-item"><span class="time-lbl">Hours</span><span class="time-val hours">{{ formatHours(employee.hours_worked) }}</span></div>
            </div>
            <div class="emp-card-actions">
              <button @click="viewHistory(employee)" class="action-btn" title="View History">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                View History
              </button>
              <button v-if="employee.status === 'absent' && isToday" @click="attemptMarkPresent(employee)" class="action-btn mark-btn" :disabled="markingPresentId === employee.id" title="Mark Present">
                <span v-if="markingPresentId === employee.id">Marking…</span>
                <span v-else><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg> Mark Present</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Table Layout -->
        <div v-else-if="layout === 'table' && filteredData.length > 0" class="table-container">
          <div class="list-header attendance-grid">
            <div>Employee</div><div>Department</div><div>Location</div><div>Status</div>
            <div class="text-right">Clock In</div><div class="text-right">Clock Out</div>
            <div class="text-right">Hours</div><div class="text-right">Actions</div>
          </div>
          <div v-for="employee in filteredData" :key="employee.id" class="list-row attendance-grid">
            <div class="emp-cell">
              <div class="emp-avatar-sm" style="background: #3b82f6;">{{ getInitials(employee.full_name) }}</div>
              <div><div class="emp-name">{{ employee.full_name }}</div><div class="emp-sub">{{ employee.employee_id }} · {{ employee.position }}</div></div>
            </div>
            <div><span class="dept-tag">{{ employee.department }}</span></div>
            <div class="loc-cell">
              <span v-if="employee.country_name" class="loc-tag-sm">🌍 {{ employee.country_name }}</span>
              <span v-if="employee.business_name" class="loc-tag-sm">🏢 {{ employee.business_name }}</span>
              <span v-if="!employee.country_name && !employee.business_name" class="text-muted">—</span>
            </div>
            <div><span :class="['status-badge', getStatusClass(employee.status)]"><span class="dot"></span>{{ formatStatus(employee.status) }}</span></div>
            <div class="text-right font-mono">{{ safeFormatTime(employee.clock_in_time) }}</div>
            <div class="text-right font-mono">{{ safeFormatTime(employee.clock_out_time) }}</div>
            <div class="text-right font-mono text-success">{{ formatHours(employee.hours_worked) }}</div>
            <div class="action-group">
              <button @click="viewHistory(employee)" class="action-btn-sm" title="View History">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
              </button>
              <button v-if="employee.status === 'absent' && isToday" @click="attemptMarkPresent(employee)" class="action-btn-sm mark" :disabled="markingPresentId === employee.id" title="Mark Present">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
              </button>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="!loading && filteredData.length === 0" class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
          <p>No {{ filterStatus === 'all' ? '' : formatStatus(filterStatus).toLowerCase() }} employees found.</p>
          <button v-if="filterStatus !== 'all'" @click="filterStatus = 'all'" class="btn-primary">Show All</button>
        </div>
      </div>
    </div>

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
import axios from 'axios';

export default {
  name: 'AttendanceMonitor',
  data() {
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 29);

    return {
      attendanceData: [],
      departmentStats: [],
      departments: [],
      selectedDepartment: 'all',
      loading: false,
      loadingFilters: false,
      error: null,
      retryCount: 0,
      selectedDate: new Date().toISOString().split('T')[0],
      today: new Date().toISOString().split('T')[0],
      filterStatus: 'all',
      markingPresentId: null,
      layout: 'table',
      selectedCountry: 'all',
      selectedBusiness: 'all',
      availableCountries: [],
      availableBusinesses: [],
      allBusinesses: [],
      summaryData: null,
      toast: { show: false, message: '', type: 'success' },

      // Confirm / Alert Modal
      modalVisible: false,
      modalTitle: '',
      modalMessage: '',
      modalIsConfirm: false,
      modalIsSuccess: false,
      modalIsError: false,
      modalButtonText: 'Close',
      modalResolve: null,
      modalReject: null,
      currentEmployeeToMark: null,

      // ── Employee History Modal ──────────────────────
      historyModal: {
        visible:    false,
        loading:    false,
        error:      null,
        employee:   null,
        records:    [],
        summary:    null,
        pagination: null,
        dateFrom:   thirtyDaysAgo.toISOString().split('T')[0],
        dateTo:     new Date().toISOString().split('T')[0],
      }
    };
  },

  computed: {
    isToday() { return this.selectedDate === this.today; },
    departmentFilteredData() {
      if (this.selectedDepartment === 'all') return this.attendanceData;
      return this.attendanceData.filter(e => e.department === this.selectedDepartment);
    },
    allCount()     { return this.departmentFilteredData.length; },
    presentCount() { return this.departmentFilteredData.filter(e => ['present','completed'].includes(e.status)).length; },
    absentCount()  { return this.departmentFilteredData.filter(e => ['absent','on_leave'].includes(e.status)).length; },
    lateCount()    { return this.departmentFilteredData.filter(e => e.status === 'late').length; },
    filteredData() {
      const f = this.departmentFilteredData;
      if (this.filterStatus === 'all') return f;
      return f.filter(e => {
        if (this.filterStatus === 'present') return ['present','completed'].includes(e.status);
        if (this.filterStatus === 'absent')  return ['absent','on_leave'].includes(e.status);
        if (this.filterStatus === 'late')    return e.status === 'late';
        return e.status === this.filterStatus;
      });
    }
  },

  mounted() {
    const saved = localStorage.getItem('attendance-layout');
    if (saved && ['grid','table'].includes(saved)) this.layout = saved;
    this.fetchFilterOptions();
    this.fetchAllData();
  },

  methods: {
    setLayout(type) { this.layout = type; localStorage.setItem('attendance-layout', type); },

    // ── Filter API ──────────────────────────────────
    async fetchFilterOptions() {
      this.loadingFilters = true;
      try {
        const [cr, br] = await Promise.all([
          axios.get('/api/admin/attendance/countries'),
          axios.get('/api/admin/attendance/businesses')
        ]);
        this.availableCountries   = cr.data.data || [];
        this.allBusinesses        = br.data.data || [];
        this.availableBusinesses  = [...this.allBusinesses];
      } catch {
        this.showError('Failed to load filter options.', 'Filter Error');
      } finally {
        this.loadingFilters = false;
      }
    },

    async onCountryChange() {
      this.selectedBusiness = 'all'; this.selectedDepartment = 'all';
      if (this.selectedCountry === 'all') {
        this.availableBusinesses = [...this.allBusinesses];
      } else {
        try {
          const r = await axios.get('/api/admin/attendance/businesses', { params: { country_id: this.selectedCountry } });
          this.availableBusinesses = r.data.data || [];
        } catch { /* silently fall back */ }
      }
      this.fetchAllData();
    },
    onBusinessChange()   { this.selectedDepartment = 'all'; this.fetchAllData(); },
    onDepartmentChange() { this.filterStatus = 'all'; this.fetchAllData(); },

    // ── Main Data API ───────────────────────────────
    async fetchAllData() {
      this.loading = true; this.error = null;
      try {
        const params = {
          date:        this.selectedDate,
          country_id:  this.selectedCountry  === 'all' ? null : this.selectedCountry,
          business_id: this.selectedBusiness === 'all' ? null : this.selectedBusiness,
          department:  this.selectedDepartment === 'all' ? null : this.selectedDepartment
        };
        const r = await axios.get('/api/admin/attendance/status', { params });
        if (r.data.success) {
          this.processAttendanceData(r.data.data, r.data.summary);
        } else throw new Error(r.data.message || 'Failed to fetch data');
      } catch (err) {
        this.handleApiError(err);
      } finally {
        this.loading = false;
      }
    },

    processAttendanceData(data, summary) {
      this.summaryData = summary;
      this.attendanceData = data.map(e => {
        const country  = this.availableCountries.find(c => c.id === e.country_id);
        const business = this.availableBusinesses.find(b => b.id === e.business_id)
                      || this.allBusinesses.find(b => b.id === e.business_id);
        return {
          id:             e.employee_id,        // raw DB id — used in API calls
          full_name:      `${e.first_name} ${e.last_name}`,
          employee_id:    e.employee_id_number || `EMP${String(e.employee_id).padStart(4,'0')}`,
          department:     e.department || 'Unassigned',
          position:       e.position   || 'N/A',
          status:         e.status,
          clock_in_time:  e.clock_in,
          clock_out_time: e.clock_out,
          hours_worked:   e.total_hours,
          date:           e.date,
          country_name:   country  ? country.name  : null,
          business_name:  business ? business.name : null
        };
      });
      this.calculateDepartmentStats();
      this.departments = [...new Set(this.attendanceData.map(e => e.department))].sort();
    },

    calculateDepartmentStats() {
      const map = new Map();
      this.attendanceData.forEach(e => {
        const d = e.department || 'Unassigned';
        if (!map.has(d)) map.set(d, { name: d, total: 0, present: 0, absent: 0, late: 0, rate: 0 });
        const s = map.get(d); s.total++;
        if (['present','completed'].includes(e.status)) s.present++;
        else if (e.status === 'late')                   s.late++;
        else if (['absent','on_leave'].includes(e.status)) s.absent++;
      });
      map.forEach(s => { if (s.total > 0) s.rate = Math.round(((s.present + s.late) / s.total) * 100); });
      this.departmentStats = Array.from(map.values());
    },

    retryFetch() {
      this.retryCount++;
      if (this.retryCount <= 3) this.fetchAllData();
      else { this.error = 'Max retries exceeded. Please refresh the page.'; this.retryCount = 0; }
    },

    handleApiError(err) {
      if (err.response?.status === 401) { this.error = 'Session expired. Please log in again.'; return; }
      if (err.response?.status === 403) { this.error = 'Access denied.'; return; }
      if (err.response?.status === 404) { this.resetData(); return; }
      this.error = err.response?.data?.message
        || (err.request ? 'Network error. Check your connection.' : 'Failed to load attendance data.');
    },

    resetData() { this.attendanceData = []; this.departmentStats = []; this.summaryData = null; },

    // ── Employee History Modal ──────────────────────
    viewHistory(employee) {
      const thirtyDaysAgo = new Date();
      thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 29);

      // Reset then open
      Object.assign(this.historyModal, {
        visible:    true,
        loading:    false,
        error:      null,
        employee,
        records:    [],
        summary:    null,
        pagination: null,
        dateFrom:   thirtyDaysAgo.toISOString().split('T')[0],
        dateTo:     this.today,
      });

      this.fetchEmployeeHistory();
    },

    closeHistoryModal() {
      this.historyModal.visible = false;
    },

    async fetchEmployeeHistory(page = 1) {
      if (!this.historyModal.employee) return;

      const employeeId = this.historyModal.employee.id;

      this.historyModal.loading = true;
      this.historyModal.error   = null;

      try {
        const { data: res } = await axios.get(
          `/api/admin/attendance/${employeeId}/history`,
          {
            params: {
              date_from: this.historyModal.dateFrom,
              date_to:   this.historyModal.dateTo,
              page,
              per_page:  20,
            }
          }
        );

        if (res.success === false) {
          throw new Error(res.message || 'Server returned an error');
        }

        if (Array.isArray(res.data)) {
          this.historyModal.records    = res.data;
          this.historyModal.summary    = res.summary || null;
          this.historyModal.pagination = res.meta   || null;
        } else if (res.data && Array.isArray(res.data.data)) {
          this.historyModal.records    = res.data.data;
          this.historyModal.summary    = res.summary || res.data.summary || null;
          this.historyModal.pagination = {
            current_page: res.data.current_page,
            last_page:    res.data.last_page,
            total:        res.data.total,
            per_page:     res.data.per_page,
          };
        } else if (Array.isArray(res)) {
          this.historyModal.records    = res;
          this.historyModal.summary    = null;
          this.historyModal.pagination = null;
        } else {
          this.historyModal.records    = [];
          this.historyModal.summary    = null;
          this.historyModal.pagination = null;
        }

      } catch (err) {
        if (err.response?.status === 404) {
          this.historyModal.records    = [];
          this.historyModal.summary    = null;
          this.historyModal.pagination = null;
        } else if (err.response?.status === 403) {
          this.historyModal.error = 'You do not have permission to view this employee\'s history.';
        } else {
          this.historyModal.error =
            err.response?.data?.message ||
            (err.request ? 'Network error. Please check your connection.' : 'Failed to load attendance history.');
        }
      } finally {
        this.historyModal.loading = false;
      }
    },

    exportHistoryCSV() {
      const emp = this.historyModal.employee;
      if (!emp || !this.historyModal.records.length) return;

      const headers = ['Date','Day','Status','Clock In','Clock Out','Hours','Notes'];
      const rows = this.historyModal.records.map(r => [
        r.date,
        this.getDayName(r.date),
        this.formatStatus(r.status),
        this.safeFormatTime(r.clock_in  || r.clock_in_time),
        this.safeFormatTime(r.clock_out || r.clock_out_time),
        this.formatHours(r.hours_worked || r.total_hours),
        r.notes || r.remarks || ''
      ]);
      const csv = [headers, ...rows].map(row => row.map(c => `"${c}"`).join(',')).join('\n');
      const link = document.createElement('a');
      link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
      link.download = `history_${emp.employee_id}_${this.historyModal.dateFrom}_to_${this.historyModal.dateTo}.csv`;
      link.click();
      this.showToast(`Exported ${this.historyModal.records.length} records.`, 'success');
    },

    // ── Mark Present ────────────────────────────────
    async attemptMarkPresent(employee) {
      this.currentEmployeeToMark = employee;
      const confirmed = await this.showConfirm(
        `Mark ${employee.full_name} as present for ${this.safeFormatDate(this.selectedDate)}? This will register a manual clock-in.`,
        'Mark Employee Present'
      ).catch(() => false);
      if (confirmed) await this.markPresent(employee);
    },

    async markPresent(employee) {
      this.markingPresentId = employee.id;
      try {
        await axios.post(`/api/admin/attendance/${employee.id}/mark-present`, {
          date:  this.selectedDate,
          force: true
        });
        this.showToast(`${employee.full_name} marked as present.`, 'success');
        await this.fetchAllData();
      } catch (err) {
        this.showToast(err.response?.data?.message || 'Failed to mark present.', 'error');
      } finally {
        this.markingPresentId = null;
      }
    },

    // ── CSV Export (main table) ──────────────────────
    exportToCSV() {
      if (!this.filteredData.length) return;
      const headers = ['Name','ID','Department','Position','Country','Business','Status','Clock In','Clock Out','Hours','Date'];
      const rows = this.filteredData.map(e => [
        e.full_name, e.employee_id, e.department, e.position,
        e.country_name || '', e.business_name || '',
        this.formatStatus(e.status),
        this.safeFormatTime(e.clock_in_time), this.safeFormatTime(e.clock_out_time),
        this.formatHours(e.hours_worked), this.safeFormatDate(e.date)
      ]);
      const csv = [headers, ...rows].map(r => r.map(c => `"${c}"`).join(',')).join('\n');
      const link = document.createElement('a');
      link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
      link.download = `attendance_${this.selectedDate}.csv`;
      link.click();
      this.showToast(`Exported ${this.filteredData.length} records.`, 'success');
    },

    // ── Modal Methods ────────────────────────────────
    showAlert(message, title = 'Notification', type = 'info') {
      Object.assign(this, { modalTitle: title, modalMessage: message, modalIsConfirm: false, modalIsSuccess: type === 'success', modalIsError: type === 'error', modalButtonText: 'Close', modalVisible: true });
      return new Promise(resolve => { this.modalResolve = resolve; });
    },
    showConfirm(message, title = 'Confirmation') {
      Object.assign(this, { modalTitle: title, modalMessage: message, modalIsConfirm: true, modalIsSuccess: false, modalIsError: false, modalButtonText: 'Confirm', modalVisible: true });
      return new Promise((resolve, reject) => { this.modalResolve = resolve; this.modalReject = reject; });
    },
    showSuccess(message, title = 'Success') { return this.showAlert(message, title, 'success'); },
    showError(message, title = 'Error')     { return this.showAlert(message, title, 'error'); },
    confirmAction() { this.modalVisible = false; if (this.modalResolve) this.modalResolve(true); this.resetModal(); },
    cancelAction()  { this.modalVisible = false; if (this.modalReject)  this.modalReject(false); this.resetModal(); },
    resetModal() {
      this.modalResolve = this.modalReject = this.currentEmployeeToMark = null;
      this.modalTitle = this.modalMessage = '';
      this.modalIsConfirm = this.modalIsSuccess = this.modalIsError = false;
      this.modalButtonText = 'Close';
    },

    // ── Toast ────────────────────────────────────────
    showToast(message, type = 'success') {
      this.toast = { show: true, message, type };
      setTimeout(() => { this.toast.show = false; }, 3500);
    },

    // ── Helpers ──────────────────────────────────────
    getCountryName(id)  { return this.availableCountries.find(c => c.id == id)?.name || 'Unknown'; },
    getBusinessName(id) { return (this.availableBusinesses.find(b => b.id == id) || this.allBusinesses.find(b => b.id == id))?.name || 'Unknown'; },
    getInitials(name) {
      if (!name) return '??';
      const p = name.split(' ').filter(Boolean);
      return ((p[0]?.[0] || '') + (p.length > 1 ? p[p.length-1]?.[0] || '' : '')).toUpperCase();
    },
    getAvatarColor(name) { return '#3b82f6'; }, // Always return blue
    // Strips any time/timezone component from a date string so that
    // "2026-02-26T00:00:00.000000Z", "2026-02-26T00:00:00", and "2026-02-26"
    // are all parsed as the same local date without timezone shifting.
    parseDateOnly(raw) {
      if (!raw) return null;
      // Extract just the YYYY-MM-DD portion regardless of what follows
      const match = String(raw).match(/^(\d{4}-\d{2}-\d{2})/);
      if (!match) return null;
      const [year, month, day] = match[1].split('-').map(Number);
      // Construct in local time to avoid UTC-offset issues
      const d = new Date(year, month - 1, day);
      return isNaN(d.getTime()) ? null : d;
    },
    safeFormatDate(date) {
      if (!date) return 'N/A';
      const d = this.parseDateOnly(date);
      return d ? d.toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric', weekday:'short' }) : 'Invalid';
    },
    formatHistoryDate(date) {
      if (!date) return '—';
      const d = this.parseDateOnly(date);
      return d ? d.toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' }) : String(date);
    },
    getDayName(date) {
      if (!date) return '';
      const d = this.parseDateOnly(date);
      return d ? d.toLocaleDateString('en-US', { weekday: 'short' }) : '';
    },
    safeFormatTime(time) {
      if (!time) return '—';
      if (/AM|PM/i.test(time)) return time;
      try {
        const d = time.includes('T') || time.includes('Z') ? new Date(time) : new Date(`2000-01-01T${time}`);
        return isNaN(d) ? time : d.toLocaleTimeString('en-US', { hour:'numeric', minute:'2-digit', hour12:true });
      } catch { return time; }
    },
    formatHours(h) {
      if (h == null || isNaN(h)) return '—';
      const hrs = Math.floor(h), mins = Math.round((h - hrs) * 60);
      return `${hrs}h ${mins}m`;
    },
    formatStatus(s) {
      return { present:'Present', completed:'Completed', absent:'Absent', late:'Late', on_leave:'On Leave' }[s] || s || 'Unknown';
    },
    getStatusClass(s) {
      return { present:'success', completed:'success', absent:'danger', late:'warning', on_leave:'info' }[s] || 'neutral';
    }
  }
};
</script>

<style scoped>
/* ── Base ─────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; }

.attendance-view {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
  max-width: 1400px;
  margin: 0 auto;
  padding: 1.5rem 2rem 3rem;
}

/* ── Header Card (matching dashboard) ─────────────────── */
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
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
}

.user-greeting {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
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

.user-info {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

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

.role-meta {
  margin-top: 0.125rem;
}

.role-badge {
  background: #f5f3ff;
  border: 1px solid #ddd6fe;
  padding: 0.125rem 0.6rem;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 600;
  color: #6d28d9;
}

.header-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

/* ── Section Containers (matching dashboard) ──────────── */
.section-container {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05);
  border: 1px solid #e2e8f0;
  margin-bottom: 1.5rem;
}

.section-title {
  font-size: 1.05rem;
  font-weight: 700;
  color: #334155;
  margin: 0 0 1rem;
}

/* ── Stats Grid (matching dashboard) ──────────────────── */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.25rem;
}

.stat-card {
  background: #f8fafc;
  border-radius: 12px;
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  border: 1px solid #e2e8f0;
  position: relative;
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px -4px rgba(0,0,0,0.08);
  border-color: #e2e8f0;;
}

.stat-card::before { display: none; }

.stat-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.stat-label {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.stat-number {
  font-size: 1.8rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.1;
}

/* ── Department Cards (matching dashboard quick actions) */
.quick-actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1rem;
}

.dept-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.125rem;
  transition: transform 0.18s, box-shadow 0.18s;
}

.dept-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px -4px rgba(0,0,0,0.08);
}

.dept-card-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.875rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #e2e8f0;
}

.dept-name {
  font-size: 0.9rem;
  font-weight: 700;
  color: #1e293b;
}

.count-badge {
  background: #fef3c7;
  color: #92400e;
  padding: 0.2rem 0.65rem;
  border-radius: 20px;
  font-size: 0.72rem;
  font-weight: 700;
}

.dept-metrics {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.5rem;
  margin-bottom: 0.875rem;
}

.dept-metric {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.dept-val {
  font-size: 1.2rem;
  font-weight: 800;
  line-height: 1.1;
}

.dept-lbl {
  font-size: 0.65rem;
  color: #94a3b8;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-top: 0.1rem;
}

.dept-val.green { color: #10b981; }
.dept-val.red   { color: #ef4444; }
.dept-val.amber { color: #f59e0b; }

.dept-progress-bar {
  height: 4px;
  background: #e2e8f0;
  border-radius: 9999px;
  overflow: hidden;
}

.dept-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #6366f1, #8b5cf6);
  border-radius: 9999px;
  transition: width 0.4s ease;
}

/* ── Controls Bar (matching dashboard) ───────────────── */
.controls-bar {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  margin-bottom: 1rem;
  gap: 1rem;
  flex-wrap: wrap;
}

.filters-row {
  display: flex;
  gap: 0.875rem;
  flex-wrap: wrap;
  align-items: flex-end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.filter-group label {
  font-size: 0.7rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.select-wrap {
  position: relative;
}

.filter-select {
  padding: 0.45rem 2rem 0.45rem 0.875rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #f8fafc;
  color: #334155;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  appearance: none;
  transition: all 0.2s;
  font-family: inherit;
  min-width: 160px;
}

.filter-select:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

.filter-select:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

input.filter-select {
  min-width: 145px;
  appearance: auto;
}

.select-caret {
  position: absolute;
  right: 0.6rem;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  color: #94a3b8;
  font-size: 0.7rem;
}

.controls-right {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-shrink: 0;
}

.records-count {
  font-size: 0.78rem;
  font-weight: 700;
  color: #64748b;
  background: #f1f5f9;
  padding: 0.2rem 0.7rem;
  border-radius: 9999px;
  white-space: nowrap;
}

.layout-toggle {
  display: flex;
  background: #f1f5f9;
  border-radius: 8px;
  padding: 2px;
  gap: 2px;
}

.layout-btn {
  width: 32px;
  height: 32px;
  border: none;
  background: transparent;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #94a3b8;
  cursor: pointer;
  transition: all 0.15s;
}

.layout-btn:hover {
  background: #e2e8f0;
  color: #475569;
}

.layout-btn.active {
  background: white;
  color: #4f46e5;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* ── Buttons (matching dashboard) ─────────────────────── */
.btn-primary {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  border: none;
  padding: 0.5rem 1.25rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(99,102,241,0.35);
}

.btn-success-solid {
  background: linear-gradient(135deg, #10b981, #059669) !important;
}

.btn-danger-solid {
  background: linear-gradient(135deg, #ef4444, #dc2626) !important;
}

.btn-outline {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.45rem 0.9rem;
  background: white;
  border: 1px solid #e2e8f0;
  color: #475569;
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}

.btn-outline:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #cbd5e1;
  color: #1e293b;
}

.btn-outline:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-outline.export {
  color: #059669;
  border-color: #bbf7d0;
  background: #f0fdf4;
}

.btn-outline.export:hover {
  background: #dcfce7;
}

.btn-secondary {
  padding: 0.5rem 1.25rem;
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  font-family: inherit;
}

.btn-secondary:hover {
  background: #e2e8f0;
}

/* ── Status tabs ──────────────────────────────────────── */
.status-tabs {
  display: flex;
  gap: 0.4rem;
  margin-bottom: 1.25rem;
  flex-wrap: wrap;
}

.tab-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.35rem 0.875rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  font-size: 0.78rem;
  font-weight: 600;
  color: #64748b;
  cursor: pointer;
  transition: all 0.15s;
  font-family: inherit;
}

.tab-pill:hover {
  background: #f1f5f9;
}

.tab-pill.active {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  border-color: transparent;
  box-shadow: 0 2px 8px rgba(99,102,241,0.25);
}

.tab-pill.active .pill-count {
  background: rgba(255,255,255,0.25);
}

.pill-count {
  background: #e2e8f0;
  color: #64748b;
  font-size: 0.68rem;
  font-weight: 700;
  padding: 0.05rem 0.4rem;
  border-radius: 9999px;
  min-width: 18px;
  text-align: center;
}

/* ── Grid cards ───────────────────────────────────────── */
.employees-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
  gap: 1.25rem;
}

.employee-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.125rem;
  transition: transform 0.18s, box-shadow 0.18s;
}

.employee-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px -4px rgba(0,0,0,0.1);
}

.emp-card-head {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding-bottom: 0.875rem;
  margin-bottom: 0.875rem;
  border-bottom: 1px solid #e2e8f0;
}

.emp-avatar {
  width: 42px;
  height: 42px;
  border-radius: 10px;
  background: #3b82f6 !important;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 0.85rem;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(0,0,0,0.12);
}

.emp-card-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.emp-card-name {
  font-size: 0.9rem;
  font-weight: 700;
  color: #1e293b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.emp-card-meta {
  display: flex;
  gap: 0.5rem;
}

.emp-card-id {
  font-size: 0.7rem;
  color: #94a3b8;
  font-family: monospace;
}

.emp-card-dept {
  font-size: 0.7rem;
  color: #64748b;
  background: #f1f5f9;
  padding: 0.05rem 0.4rem;
  border-radius: 4px;
}

.emp-card-pos {
  font-size: 0.75rem;
  color: #64748b;
}

.emp-card-loc {
  display: flex;
  flex-wrap: wrap;
  gap: 0.3rem;
  margin-top: 0.2rem;
}

.loc-tag {
  font-size: 0.68rem;
  padding: 0.1rem 0.4rem;
  background: #eff6ff;
  color: #1e40af;
  border: 1px solid #bfdbfe;
  border-radius: 4px;
}

.emp-card-times {
  display: flex;
  align-items: center;
  margin-bottom: 0.875rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  overflow: hidden;
}

.time-item {
  flex: 1;
  padding: 0.6rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.time-divider {
  width: 1px;
  background: #e2e8f0;
  align-self: stretch;
  flex-shrink: 0;
}

.time-lbl {
  font-size: 0.62rem;
  color: #94a3b8;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.time-val {
  font-size: 0.82rem;
  font-weight: 700;
  color: #334155;
  font-family: 'SFMono-Regular', Consolas, monospace;
}

.time-val.hours {
  color: #10b981;
}

.emp-card-actions {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  padding: 0.45rem 0.75rem;
  border: 1px solid #e2e8f0;
  background: white;
  color: #64748b;
  border-radius: 7px;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
  font-family: inherit;
}

.action-btn:hover {
  background: #eff6ff;
  color: #4f46e5;
  border-color: #a5b4fc;
}

.action-btn.mark-btn {
  background: #f0fdf4;
  color: #059669;
  border-color: #bbf7d0;
}

.action-btn.mark-btn:hover:not(:disabled) {
  background: #dcfce7;
}

.action-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ── Table layout ─────────────────────────────────────── */
.table-container {
  border-radius: 10px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
}

.attendance-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1.2fr 0.9fr 0.9fr 0.9fr 0.7fr 0.6fr;
  padding: 0.75rem 1rem;
  align-items: center;
  gap: 0.75rem;
}

.list-header {
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  font-size: 0.68rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.list-row {
  border-bottom: 1px solid #f1f5f9;
  background: white;
  transition: background 0.12s;
}

.list-row:last-child {
  border-bottom: none;
}

.list-row:hover {
  background: #fafbff;
}

.emp-cell {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.emp-avatar-sm {
  width: 36px;
  height: 36px;
  border-radius: 9px;
  background: #3b82f6 !important;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 0.78rem;
  flex-shrink: 0;
  box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.emp-name {
  font-size: 0.83rem;
  font-weight: 700;
  color: #1e293b;
}

.emp-sub {
  font-size: 0.7rem;
  color: #94a3b8;
  margin-top: 0.05rem;
}

.dept-tag {
  display: inline-block;
  padding: 0.2rem 0.55rem;
  background: #f1f5f9;
  color: #475569;
  border-radius: 5px;
  font-size: 0.72rem;
  font-weight: 600;
  border: 1px solid #e2e8f0;
}

.loc-cell {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.loc-tag-sm {
  font-size: 0.7rem;
  padding: 0.1rem 0.4rem;
  background: #eff6ff;
  color: #1e40af;
  border: 1px solid #bfdbfe;
  border-radius: 4px;
  white-space: nowrap;
  display: inline-block;
}

.text-right {
  text-align: right;
}

.text-muted {
  color: #94a3b8;
}

.text-sm {
  font-size: 0.78rem;
}

.text-success {
  color: #10b981;
  font-weight: 700;
}

.font-mono {
  font-family: 'SFMono-Regular', Consolas, monospace;
  font-size: 0.8rem;
  color: #475569;
}

.action-group {
  display: flex;
  justify-content: flex-end;
  gap: 0.3rem;
}

.action-btn-sm {
  width: 30px;
  height: 30px;
  border-radius: 6px;
  border: 1px solid #e2e8f0;
  background: white;
  color: #64748b;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.15s;
}

.action-btn-sm:hover {
  background: #eff6ff;
  color: #4f46e5;
  border-color: #a5b4fc;
}

.action-btn-sm.mark:hover:not(:disabled) {
  background: #f0fdf4;
  color: #059669;
  border-color: #bbf7d0;
}

.action-btn-sm:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* ── Status badges ────────────────────────────────────── */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 0.25rem 0.65rem;
  border-radius: 9999px;
  font-size: 0.7rem;
  font-weight: 700;
  white-space: nowrap;
}

.dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: currentColor;
}

.status-badge.success {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.warning {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.danger {
  background: #fee2e2;
  color: #991b1b;
}

.status-badge.info {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.neutral {
  background: #f1f5f9;
  color: #64748b;
}

/* ── Empty States ─────────────────────────────────────── */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.875rem;
  color: #94a3b8;
}

.empty-state p {
  margin: 0;
  font-size: 0.875rem;
  color: #64748b;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e2e8f0;
  border-top-color: #6366f1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ── Modals (matching dashboard) ──────────────────────── */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,31,91,0.5);
  backdrop-filter: blur(4px);
  z-index: 100;
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 1rem;
}

.modal-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 25px 60px rgba(0,31,91,0.2);
  display: flex;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  animation: slideUp 0.25s ease-out;
  width: 100%;
}

.modal-sm {
  max-width: 440px;
}

.modal-lg {
  max-width: 860px;
  max-height: 90vh;
}

@keyframes slideUp {
  from { opacity: 0; transform: translateY(16px); }
  to { opacity: 1; transform: none; }
}

.modal-header {
  padding: 1.25rem 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-shrink: 0;
}

.header-confirm {
  background: linear-gradient(135deg, #001f5b 0%, #002a7a 100%);
}

.header-success {
  background: linear-gradient(135deg, #065f46 0%, #059669 100%);
}

.header-error {
  background: linear-gradient(135deg, #991b1b 0%, #dc2626 100%);
}

.modal-title-wrap {
  display: flex;
  align-items: center;
  gap: 0.875rem;
}

.modal-avatar {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
  font-size: 0.9rem;
  color: white;
  border: 1.5px solid rgba(255,255,255,0.25);
  background: rgba(255,255,255,0.18);
}

.modal-name {
  font-size: 1rem;
  font-weight: 700;
  color: white;
  margin: 0;
}

.modal-sub {
  font-size: 0.75rem;
  color: rgba(255,255,255,0.7);
  margin: 0;
}

.modal-close {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: 1.5px solid rgba(255,255,255,0.25);
  background: rgba(255,255,255,0.1);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
}

.modal-close:hover {
  background: rgba(239,68,68,0.6);
  border-color: transparent;
}

.modal-body {
  padding: 1.25rem 1.5rem;
  overflow-y: auto;
}

.modal-msg {
  margin: 0;
  color: #475569;
  font-size: 0.9rem;
  line-height: 1.55;
}

.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #f1f5f9;
  background: #fafafa;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
  flex-shrink: 0;
}

/* ── History modal ────────────────────────────────────── */
.history-filters {
  display: flex;
  align-items: flex-end;
  gap: 0.875rem;
  flex-wrap: wrap;
  padding: 0.875rem 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  background: #fafafa;
  flex-shrink: 0;
}

.history-summary-chips {
  display: flex;
  gap: 0.4rem;
  flex-wrap: wrap;
  align-items: center;
  margin-left: 0.5rem;
}

.chip {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.2rem 0.6rem;
  border-radius: 8px;
  font-size: 0.72rem;
  font-weight: 700;
  border: 1px solid transparent;
}

.chip.green {
  background: #d1fae5;
  color: #065f46;
  border-color: #a7f3d0;
}

.chip.red {
  background: #fee2e2;
  color: #991b1b;
  border-color: #fca5a5;
}

.chip.amber {
  background: #fef3c7;
  color: #92400e;
  border-color: #fde68a;
}

.chip.blue {
  background: #dbeafe;
  color: #1e40af;
  border-color: #bfdbfe;
}

.history-body {
  padding: 0;
  overflow-y: auto;
  max-height: calc(90vh - 230px);
}

.history-table-wrap {
  border-top: 1px solid #f1f5f9;
}

.history-grid {
  display: grid;
  grid-template-columns: 1.6fr 1.1fr 1.1fr 1.1fr 0.9fr 1.4fr;
  padding: 0.7rem 1.25rem;
  gap: 0.75rem;
  align-items: center;
}

.history-table-header {
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  font-size: 0.68rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  position: sticky;
  top: 0;
  z-index: 1;
}

.history-row {
  border-bottom: 1px solid #f8fafc;
  transition: background 0.1s;
}

.history-row:last-child {
  border-bottom: none;
}

.history-row:hover {
  background: #fafbff;
}

.history-date {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.date-main {
  font-size: 0.82rem;
  font-weight: 600;
  color: #1e293b;
}

.date-day {
  font-size: 0.68rem;
  color: #94a3b8;
  font-weight: 500;
}

.history-pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  padding: 1rem 1.25rem;
  border-top: 1px solid #f1f5f9;
}

.page-btn {
  padding: 0.35rem 0.875rem;
  border: 1px solid #e2e8f0;
  background: white;
  border-radius: 7px;
  font-size: 0.8rem;
  font-weight: 600;
  color: #475569;
  cursor: pointer;
  transition: all 0.15s;
  font-family: inherit;
}

.page-btn:hover:not(:disabled) {
  background: #eff6ff;
  color: #4f46e5;
  border-color: #a5b4fc;
}

.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.page-info {
  font-size: 0.78rem;
  color: #64748b;
  font-weight: 500;
}

/* ── Toast ────────────────────────────────────────────── */
.toast {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  background: white;
  padding: 0.875rem 1.25rem;
  border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  display: flex;
  align-items: center;
  gap: 0.65rem;
  z-index: 200;
  font-size: 0.875rem;
  font-weight: 500;
  border-left: 4px solid #10b981;
}

.toast.error {
  border-left-color: #ef4444;
}

.toast.success svg {
  color: #10b981;
}

.toast.error svg {
  color: #ef4444;
}

/* ── Transitions ──────────────────────────────────────── */
.modal-fade-enter-active,
.modal-fade-leave-active {
  transition: opacity 0.25s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
}

.toast-slide-enter-active,
.toast-slide-leave-active {
  transition: all 0.3s ease;
}

.toast-slide-enter-from,
.toast-slide-leave-to {
  opacity: 0;
  transform: translateY(12px);
}

/* ── Responsive ───────────────────────────────────────── */
@media (max-width: 1024px) {
  .attendance-view {
    padding: 1rem 1rem 2rem;
  }
  
  .stats-grid {
    grid-template-columns: repeat(3, 1fr);
  }
  
  .employees-grid {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  }
  
  .attendance-grid {
    grid-template-columns: 2fr 1fr 1fr 1fr 0.8fr;
  }
  
  .attendance-grid > :nth-child(n+6) {
    display: none;
  }
  
  .history-grid {
    grid-template-columns: 1.4fr 1fr 1fr 1fr 0.8fr;
  }
  
  .history-grid > :nth-child(6) {
    display: none;
  }
}

@media (max-width: 768px) {
  .user-greeting {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .header-actions {
    width: 100%;
  }
  
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .quick-actions-grid {
    grid-template-columns: 1fr;
  }
  
  .employees-grid {
    grid-template-columns: 1fr;
  }
  
  .list-header {
    display: none;
  }
  
  .attendance-grid {
    grid-template-columns: 1fr auto;
    gap: 0.5rem;
    padding: 0.875rem 1rem;
  }
  
  .attendance-grid > :nth-child(n+3) {
    display: none;
  }
  
  .history-grid {
    grid-template-columns: 1.2fr 1fr 1fr 1fr;
    padding: 0.6rem 1rem;
  }
  
  .history-grid > :nth-child(n+5) {
    display: none;
  }
  
  .history-filters {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .modal-lg {
    max-height: 95vh;
  }
}

@media (max-width: 480px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .quick-actions-grid {
    grid-template-columns: 1fr;
  }
  
  .history-grid {
    grid-template-columns: 1.2fr 1fr 1fr;
  }
  
  .history-grid > :nth-child(n+4) {
    display: none;
  }
}
</style>