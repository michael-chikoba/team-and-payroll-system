<template>
  <div class="payroll-page">

    <!-- ── Sticky Header ─────────────────────────────── -->
    <div class="fixed-header">
      <div class="dashboard-header-card">
        <div class="user-greeting">
          <div class="avatar-section">
            <div class="avatar">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
              </svg>
            </div>
            <div class="user-info">
              <h1 class="greeting">{{ pageName }}</h1>
              <p class="subtitle">Manage and process employee payroll for the selected period</p>
              <div class="role-meta">
                <span class="role-badge">Admin View</span>
              </div>
            </div>
          </div>

          <div class="header-actions">
            <button @click="refreshPayrollData" class="btn-outline" :disabled="loading">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M23 4v6h-6"></path>
                <path d="M1 20v-6h6"></path>
                <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
              </svg>
              Refresh
            </button>
            <button @click="showPreview" class="btn-outline" :disabled="processing || pendingCount === 0">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                <circle cx="12" cy="12" r="3"></circle>
              </svg>
              Preview & Adjust
            </button>
            <button @click="processPayroll" class="btn-primary" :disabled="processing || pendingCount === 0">
              <div v-if="processing" class="spinner-sm"></div>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
              </svg>
              {{ processing ? 'Processing...' : 'Process Payroll' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="dashboard-content">

      <!-- ── Notifications ────────────────────────── -->
      <transition-group name="toast-slide" tag="div" class="notifications-wrap">
        <div v-if="taxConfigWarning" key="warn" class="notification warning">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
          </svg>
          <span>{{ taxConfigWarning }}</span>
          <button @click="taxConfigWarning = null" class="notif-close">×</button>
        </div>
        <div v-if="error" key="err" class="notification error">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
          </svg>
          <span>{{ error }}</span>
          <button @click="dismissError" class="notif-close">×</button>
        </div>
        <div v-if="successMessage" key="success" class="notification success">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <span>{{ successMessage }}</span>
          <button @click="successMessage = null" class="notif-close">×</button>
        </div>
      </transition-group>

      <!-- ── Metrics ──────────────────────────────── -->
      <div class="metrics-section" v-if="employees.length > 0">
        <h2>Summary</h2>
        <div class="metrics-grid">
          <div class="metric-card" style="--accent:#6366f1;" @click="filterStatus = 'all'">
            <div class="metric-icon-wrap" style="background:rgba(99,102,241,0.1)">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
              </svg>
            </div>
            <div class="metric-value">{{ employees.length }}</div>
            <div class="metric-label">Total Employees</div>
          </div>
          <div class="metric-card" style="--accent:#f59e0b;" @click="filterStatus = 'pending'">
            <div class="metric-icon-wrap" style="background:rgba(245,158,11,0.1)">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
            </div>
            <div class="metric-value">{{ pendingCount }}</div>
            <div class="metric-label">Pending</div>
          </div>
          <div class="metric-card" style="--accent:#10b981;" @click="filterStatus = 'paid'">
            <div class="metric-icon-wrap" style="background:rgba(16,185,129,0.1)">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
                <polyline points="20 6 9 17 4 12"></polyline>
              </svg>
            </div>
            <div class="metric-value">{{ paidCount }}</div>
            <div class="metric-label">Paid</div>
          </div>
          <div class="metric-card" style="--accent:#3b82f6;">
            <div class="metric-icon-wrap" style="background:rgba(59,130,246,0.1)">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"></line>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
              </svg>
            </div>
            <div class="metric-value">{{ formatCurrencyShort(totalPayroll) }}</div>
            <div class="metric-label">Total Pending</div>
          </div>
        </div>
      </div>

      <!-- ── Controls + Table ─────────────────────── -->
      <div class="table-section">

        <!-- Controls Bar -->
        <div class="controls-bar">
          <div class="filters-row">
            <div class="filter-group">
              <label>Period</label>
              <input type="month" v-model="payrollPeriod" @change="updateDateRange" class="filter-select date-input"/>
            </div>
            <div class="filter-group">
              <label>From</label>
              <input type="date" v-model="startDate" class="filter-select date-input"/>
            </div>
            <div class="filter-group">
              <label>To</label>
              <input type="date" v-model="endDate" class="filter-select date-input"/>
            </div>
            <div class="filter-group" v-if="authStore.isAdmin">
              <label>Business</label>
              <select v-model="selectedBusinessId" @change="onBusinessFilterChange" class="filter-select">
                <option value="">All Businesses</option>
                <option v-for="b in businesses" :key="b.id" :value="b.id">{{ b.name }}</option>
              </select>
            </div>
            <div class="filter-group">
              <label>Search</label>
              <div class="search-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="search-icon">
                  <circle cx="11" cy="11" r="8"></circle>
                  <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input v-model="searchQuery" type="text" placeholder="Search employees..." class="filter-input"/>
                <button v-if="searchQuery" @click="clearSearch" class="search-clear">×</button>
              </div>
            </div>
            <div class="filter-group">
              <label>Status</label>
              <div class="filter-pills">
                <button @click="filterStatus = 'all'"     :class="['pill', { active: filterStatus === 'all' }]">All</button>
                <button @click="filterStatus = 'pending'" :class="['pill', { active: filterStatus === 'pending' }]">Pending</button>
                <button @click="filterStatus = 'paid'"    :class="['pill', { active: filterStatus === 'paid' }]">Paid</button>
              </div>
            </div>
          </div>
          <div class="controls-right">
            <span class="records-count" v-if="!loading">{{ filteredEmployees.length }} employee{{ filteredEmployees.length !== 1 ? 's' : '' }}</span>
          </div>
        </div>

        <!-- Bulk Actions Bar -->
        <div v-if="selectedCount > 0" class="bulk-bar">
          <span class="bulk-count">{{ selectedCount }} selected</span>
          <div class="bulk-actions">
            <button @click="showBulkAdjustments" class="btn-outline btn-sm">Bulk Adjustments</button>
            <button v-if="selectedAllPaid" @click="bulkRevertToPending" class="btn-warn btn-sm" :disabled="!!statusUpdating">
              <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="1 4 1 10 7 10"></polyline>
                <path d="M3.51 15a9 9 0 1 0 .49-3.54"></path>
              </svg>
              Revert to Pending
            </button>
            <button v-if="selectedAllPending" @click="bulkMarkPaid" class="btn-success btn-sm" :disabled="!!statusUpdating">Mark as Paid</button>
            <button @click="clearSelection" class="btn-clear">Clear</button>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="empty-state">
          <div class="spinner"></div>
          <p>Loading payroll data...</p>
        </div>

        <!-- Empty -->
        <div v-else-if="filteredEmployees.length === 0" class="empty-state">
          <div class="empty-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
            </svg>
          </div>
          <h3>No employees found</h3>
          <p>{{ searchQuery ? 'Try adjusting your search criteria.' : 'Add employees to get started.' }}</p>
          <div class="empty-actions">
            <button v-if="!searchQuery" @click="addEmployee" class="btn-primary">Add Employee</button>
            <button v-else @click="clearSearch" class="btn-secondary">Clear Search</button>
          </div>
        </div>

        <!-- Table -->
        <div v-else>
          <div class="table-top-bar">
            <span class="table-range">Showing {{ pagination.startIndex + 1 }}–{{ pagination.endIndex }} of {{ filteredEmployees.length }}</span>
            <div class="table-top-right">
              <div class="page-nav">
                <button @click="previousPage" :disabled="pagination.currentPage === 1" class="page-btn">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                  </svg>
                  Prev
                </button>
                <span class="page-info">{{ pagination.currentPage }} / {{ pagination.totalPages }}</span>
                <button @click="nextPage" :disabled="pagination.currentPage === pagination.totalPages" class="page-btn">
                  Next
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                  </svg>
                </button>
              </div>
              <select v-model="pagination.pageSize" @change="onPageSizeChange" class="page-size-select">
                <option value="10">10 / page</option>
                <option value="25">25 / page</option>
                <option value="50">50 / page</option>
                <option value="100">100 / page</option>
              </select>
            </div>
          </div>

          <div class="list-header">
            <div class="col-check"><input type="checkbox" @change="toggleSelectAll" :checked="allSelected" class="cb"/></div>
            <div class="col-emp">Employee</div>
            <div v-if="authStore.isAdmin" class="col-biz">Business</div>
            <div class="col-pos">Position</div>
            <div class="col-amt text-right">Base Salary</div>
            <div class="col-amt text-right">Gross</div>
            <div class="col-amt text-right">Net Pay</div>
            <div class="col-status">Status</div>
            <div class="col-actions text-right">Actions</div>
          </div>

          <div
            v-for="employee in paginatedEmployees"
            :key="employee.id"
            :class="['list-row', { selected: employee.selected, 'status-updating': statusUpdating === employee.id }]"
          >
            <div class="col-check">
              <input type="checkbox" v-model="employee.selected" class="cb"/>
            </div>
            <div class="col-emp">
              <div class="emp-avatar" :style="{ background: getAvatarColor(employee.name) }">{{ getInitials(employee.name) }}</div>
              <div class="name-stack">
                <span class="emp-name">{{ employee.name }}</span>
                <span class="emp-meta">ID: #{{ employee.id }}</span>
                <span class="emp-meta">{{ employee.email || '' }}</span>
              </div>
            </div>
            <div v-if="authStore.isAdmin" class="col-biz">
              <span v-if="employee.business_name && employee.business_name !== 'No Business'" class="biz-chip">{{ employee.business_name }}</span>
              <span v-else class="text-muted">—</span>
            </div>
            <div class="col-pos"><span class="pos-tag">{{ employee.position }}</span></div>
            <div class="col-amt text-right mono">{{ formatCurrency(employee.base_salary) }}</div>
            <div class="col-amt text-right mono text-info">{{ formatCurrency(employee.gross_salary) }}</div>
            <div class="col-amt text-right mono text-success fw-bold">{{ formatCurrency(employee.net_pay) }}</div>
            <div class="col-status">
              <button
                :class="['status-badge', employee.payroll_status, 'status-toggle']"
                :title="employee.payroll_status === 'paid' ? 'Click to revert to Pending' : 'Click to mark as Paid'"
                @click="togglePayrollStatus(employee)"
                :disabled="statusUpdating === employee.id"
              >
                <span v-if="statusUpdating === employee.id" class="spinner-xs"></span>
                <span v-else class="dot"></span>
                {{ formatStatus(employee.payroll_status) }}
                <svg class="toggle-icon" xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                  <polyline v-if="employee.payroll_status === 'paid'" points="1 4 1 10 7 10"></polyline>
                  <path v-if="employee.payroll_status === 'paid'" d="M3.51 15a9 9 0 1 0 .49-3.54"></path>
                  <polyline v-else points="20 6 9 17 4 12"></polyline>
                </svg>
              </button>
            </div>
            <div class="col-actions">
              <div class="action-group">
                <button @click="viewDetails(employee.id)" class="action-btn" title="View Details">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                  </svg>
                </button>
                <button @click="editAdjustments(employee)" class="action-btn adjust" title="Adjustments">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                  </svg>
                </button>
                <button v-if="employee.payroll_status === 'paid'" @click="revertToPending(employee)" class="action-btn revert" title="Revert to Pending" :disabled="statusUpdating === employee.id">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="1 4 1 10 7 10"></polyline>
                    <path d="M3.51 15a9 9 0 1 0 .49-3.54"></path>
                  </svg>
                </button>
                <button @click="deleteEmployee(employee.id)" class="action-btn danger" title="Delete">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"></polyline>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Revert Confirmation Modal ───────────────── -->
    <transition name="modal-fade">
      <div v-if="showRevertModal" class="modal-overlay" @click.self="closeRevertModal">
        <div class="modal-container" style="max-width:480px;">
          <div class="modal-header">
            <div class="modal-title-wrap">
              <div class="modal-avatar" style="background:rgba(251,191,36,0.2);">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="1 4 1 10 7 10"></polyline>
                  <path d="M3.51 15a9 9 0 1 0 .49-3.54"></path>
                </svg>
              </div>
              <div>
                <h3 class="modal-name">Revert to Pending</h3>
                <p class="modal-sub">{{ revertTarget.length === 1 ? revertTarget[0]?.name : `${revertTarget.length} employees` }}</p>
              </div>
            </div>
            <button @click="closeRevertModal" class="modal-close">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          <div class="modal-body">
            <div class="revert-warning">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#92400e" stroke-width="2" style="flex-shrink:0;">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
              </svg>
              <div>
                <p style="margin:0 0 0.4rem;">The payslip{{ revertTarget.length > 1 ? 's' : '' }} for <strong>{{ revertTarget.length === 1 ? revertTarget[0]?.name : `${revertTarget.length} employees` }}</strong> will be <strong>permanently deleted</strong> from the <strong>{{ payrollPeriod }}</strong> period.</p>
                <p style="margin:0; font-size:0.82rem; color:#92400e;">All salary, tax, and deduction values will be recalculated fresh the next time payroll is processed. This cannot be undone.</p>
              </div>
            </div>
            <div v-if="revertTarget.length > 0" class="revert-employee-list">
              <div v-for="emp in revertTarget.slice(0, 5)" :key="emp.id" class="revert-emp-row">
                <div class="emp-avatar-sm" :style="{ background: getAvatarColor(emp.name) }">{{ getInitials(emp.name) }}</div>
                <div>
                  <div class="emp-name">{{ emp.name }}</div>
                  <div class="emp-meta">Current Net Pay: {{ formatCurrency(emp.net_pay) }}</div>
                </div>
                <span class="status-badge paid" style="margin-left:auto;font-size:0.68rem;"><span class="dot"></span> Paid → Deleted</span>
              </div>
              <div v-if="revertTarget.length > 5" class="revert-more">+ {{ revertTarget.length - 5 }} more employees</div>
            </div>
            <div class="form-group" style="margin-top:1rem;">
              <label class="form-label">Reason (Optional)</label>
              <textarea v-model="revertReason" rows="3" placeholder="e.g. Incorrect deductions, salary adjustment needed, wrong overtime hours..." class="form-textarea"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button @click="closeRevertModal" class="btn-secondary">Cancel</button>
            <button @click="confirmRevert" class="btn-warn" :disabled="!!statusUpdating">
              <div v-if="statusUpdating === 'bulk'" class="spinner-sm"></div>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="1 4 1 10 7 10"></polyline>
                <path d="M3.51 15a9 9 0 1 0 .49-3.54"></path>
              </svg>
              Delete Payslip &amp; Revert
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- ── Preview & Adjustments Modal ─────────────── -->
    <transition name="modal-fade">
      <div v-if="showPreviewModal" class="modal-overlay" @click.self="closePreview">
        <div class="modal-container modal-lg">
          <div class="modal-header">
            <div class="modal-title-wrap">
              <div class="modal-avatar">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
              </div>
              <div>
                <h3 class="modal-name">Payroll Preview &amp; Adjustments</h3>
                <p class="modal-sub">Review and modify payroll data before final processing</p>
              </div>
            </div>
            <button @click="closePreview" class="modal-close">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
          <div class="modal-body">
            <div class="modal-stats">
              <div class="modal-stat"><small>Employees</small><strong>{{ previewEmployees.length }}</strong></div>
              <div class="modal-stat"><small>Total Gross</small><strong>{{ formatCurrency(previewTotalGross) }}</strong></div>
              <div class="modal-stat highlight"><small>Net Payable</small><strong>{{ formatCurrency(previewTotalNet) }}</strong></div>
              <div class="modal-stat"><small>Adjustments</small><strong :class="previewTotalAdjustments >= 0 ? 'text-success' : 'text-danger'">{{ formatCurrency(previewTotalAdjustments) }}</strong></div>
            </div>

            <div class="adj-list">
              <div v-for="emp in previewEmployees" :key="emp.id" :class="['adj-card', { modified: calculateNetChange(emp) !== 0 }]">
                <div class="adj-header">
                  <div class="adj-emp">
                    <div class="emp-avatar-sm">{{ getInitials(emp.name) }}</div>
                    <div>
                      <div class="emp-name">{{ emp.name }}</div>
                      <div class="adj-meta">
                        <span class="pos-tag">{{ emp.position }}</span>
                        <span class="text-muted">#{{ emp.id }}</span>
                      </div>
                    </div>
                  </div>
                  <div class="adj-salary">
                    <div class="salary-row"><span>Base:</span><span>{{ formatCurrency(emp.base_salary) }}</span></div>
                    <div class="salary-row net"><span>Net Pay:</span><strong>{{ formatCurrency(calculateAdjustedNet(emp)) }}</strong></div>
                  </div>
                </div>

                <!-- ── Overtime Section (inline in preview card) ── -->
                <div class="overtime-section">
                  <div class="overtime-toggle-row">
                    <div class="ot-label-wrap">
                      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                      </svg>
                      <span>Overtime Applicable?</span>
                    </div>
                    <div class="toggle-switch-wrap">
                      <label class="toggle-switch">
                        <input type="checkbox" v-model="emp.adjustments.overtime_applicable" @change="onOvertimeToggle(emp)"/>
                        <span class="toggle-track">
                          <span class="toggle-thumb"></span>
                        </span>
                      </label>
                      <span class="toggle-label">{{ emp.adjustments.overtime_applicable ? 'Yes' : 'No' }}</span>
                    </div>
                  </div>

                  <div v-if="emp.adjustments.overtime_applicable" class="overtime-detail">
                    <!-- Mode selector -->
                    <div class="ot-mode-tabs">
                      <button
                        :class="['ot-tab', { active: emp.adjustments.overtime_mode === 'automatic' }]"
                        @click="setOvertimeMode(emp, 'automatic')"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <circle cx="12" cy="12" r="3"></circle>
                          <path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"></path>
                        </svg>
                        Automatic (from attendance)
                      </button>
                      <button
                        :class="['ot-tab', { active: emp.adjustments.overtime_mode === 'manual' }]"
                        @click="setOvertimeMode(emp, 'manual')"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        Manual Entry
                      </button>
                    </div>

                    <!-- Automatic mode -->
                    <div v-if="emp.adjustments.overtime_mode === 'automatic'" class="ot-auto-panel">
                      <div v-if="emp.adjustments.overtime_loading" class="ot-loading">
                        <div class="spinner-sm" style="border-top-color:#6366f1; border-color:#e2e8f0;"></div>
                        <span>Fetching from attendance records...</span>
                      </div>
                      <div v-else-if="emp.adjustments.overtime_auto_data" class="ot-auto-result">
                        <div class="ot-auto-grid">
                          <div class="ot-auto-item weekday">
                            <div class="ot-auto-icon">
                              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                              </svg>
                            </div>
                            <div class="ot-auto-info">
                              <span class="ot-auto-type">Weekday OT</span>
                              <span class="ot-auto-rate">× 1.5 rate</span>
                            </div>
                            <div class="ot-auto-values">
                              <span class="ot-hours">{{ emp.adjustments.overtime_auto_data.weekday_hours }}h</span>
                              <span class="ot-pay">{{ formatCurrency(emp.adjustments.overtime_auto_data.weekday_pay) }}</span>
                            </div>
                          </div>
                          <div class="ot-auto-item weekend">
                            <div class="ot-auto-icon">
                              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="5"></circle>
                                <line x1="12" y1="1" x2="12" y2="3"></line>
                                <line x1="12" y1="21" x2="12" y2="23"></line>
                                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                                <line x1="1" y1="12" x2="3" y2="12"></line>
                                <line x1="21" y1="12" x2="23" y2="12"></line>
                                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                              </svg>
                            </div>
                            <div class="ot-auto-info">
                              <span class="ot-auto-type">Weekend / PH OT</span>
                              <span class="ot-auto-rate">× 2.5 rate</span>
                            </div>
                            <div class="ot-auto-values">
                              <span class="ot-hours">{{ emp.adjustments.overtime_auto_data.weekend_ph_hours }}h</span>
                              <span class="ot-pay">{{ formatCurrency(emp.adjustments.overtime_auto_data.weekend_ph_pay) }}</span>
                            </div>
                          </div>
                        </div>
                        <div class="ot-auto-total">
                          <span>Total Overtime Pay</span>
                          <strong>{{ formatCurrency(emp.adjustments.overtime_auto_data.total_pay) }}</strong>
                        </div>
                        <button class="ot-refresh-btn" @click="fetchAutoOvertime(emp)">
                          <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M23 4v6h-6"></path>
                            <path d="M1 20v-6h6"></path>
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                          </svg>
                          Re-fetch from attendance
                        </button>
                      </div>
                      <div v-else class="ot-auto-empty">
                        <button class="btn-outline btn-sm" @click="fetchAutoOvertime(emp)">
                          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M23 4v6h-6"></path>
                            <path d="M1 20v-6h6"></path>
                            <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                          </svg>
                          Fetch from Attendance Records
                        </button>
                      </div>
                    </div>

                    <!-- Manual mode -->
                    <div v-if="emp.adjustments.overtime_mode === 'manual'" class="ot-manual-panel">
                      <div class="ot-manual-grid">
                        <div class="ot-manual-block weekday-block">
                          <div class="ot-block-header">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                              <line x1="16" y1="2" x2="16" y2="6"></line>
                              <line x1="8" y1="2" x2="8" y2="6"></line>
                              <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            <span>Weekday Overtime</span>
                            <span class="ot-rate-badge weekday">×1.5</span>
                          </div>
                          <div class="ot-manual-field">
                            <label>Hours</label>
                            <input type="number" v-model="emp.adjustments.overtime_weekday_hours" @input="recalcManualOvertime(emp)" placeholder="0.00" min="0" step="0.5" class="adj-input ot-hours-input"/>
                          </div>
                          <div class="ot-calc-preview" v-if="emp.adjustments.overtime_weekday_hours > 0">
                            <span>= {{ formatCurrency(calcWeekdayPay(emp)) }}</span>
                          </div>
                        </div>
                        <div class="ot-manual-block weekend-block">
                          <div class="ot-block-header">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                              <circle cx="12" cy="12" r="5"></circle>
                              <line x1="12" y1="1" x2="12" y2="3"></line>
                              <line x1="12" y1="21" x2="12" y2="23"></line>
                              <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                              <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                              <line x1="1" y1="12" x2="3" y2="12"></line>
                              <line x1="21" y1="12" x2="23" y2="12"></line>
                            </svg>
                            <span>Weekend / Public Holiday OT</span>
                            <span class="ot-rate-badge weekend">×2.5</span>
                          </div>
                          <div class="ot-manual-field">
                            <label>Hours</label>
                            <input type="number" v-model="emp.adjustments.overtime_weekend_ph_hours" @input="recalcManualOvertime(emp)" placeholder="0.00" min="0" step="0.5" class="adj-input ot-hours-input"/>
                          </div>
                          <div class="ot-calc-preview" v-if="emp.adjustments.overtime_weekend_ph_hours > 0">
                            <span>= {{ formatCurrency(calcWeekendPhPay(emp)) }}</span>
                          </div>
                        </div>
                      </div>
                      <div class="ot-manual-summary" v-if="emp.adjustments.overtime_bonus > 0">
                        <div class="ot-summary-line">
                          <span>Weekday ({{ emp.adjustments.overtime_weekday_hours || 0 }}h × {{ formatCurrency(calcHourlyRate(emp)) }} × 1.5)</span>
                          <span>{{ formatCurrency(calcWeekdayPay(emp)) }}</span>
                        </div>
                        <div class="ot-summary-line">
                          <span>Weekend/PH ({{ emp.adjustments.overtime_weekend_ph_hours || 0 }}h × {{ formatCurrency(calcHourlyRate(emp)) }} × 2.5)</span>
                          <span>{{ formatCurrency(calcWeekendPhPay(emp)) }}</span>
                        </div>
                        <div class="ot-summary-total">
                          <span>Total Overtime Pay</span>
                          <strong>{{ formatCurrency(emp.adjustments.overtime_bonus) }}</strong>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- ── End Overtime Section ── -->

                <div class="adj-inputs">
                  <div class="adj-col">
                    <div class="adj-col-label green">
                      <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                      </svg>
                      Other Additions
                    </div>
                    <div class="adj-field">
                      <label>Bonus / Allowance</label>
                      <input type="number" v-model="emp.adjustments.other_bonuses" @input="updateAdjustments(emp)" placeholder="0.00" min="0" step="0.01" class="adj-input"/>
                    </div>
                  </div>
                  <div class="adj-col">
                    <div class="adj-col-label red">
                      <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                      </svg>
                      Deductions
                    </div>
                    <div class="adj-field">
                      <label>Loan Repayment</label>
                      <input type="number" v-model="emp.adjustments.loan_deductions" @input="updateAdjustments(emp)" placeholder="0.00" min="0" step="0.01" class="adj-input"/>
                    </div>
                    <div class="adj-field">
                      <label>Salary Advance</label>
                      <input type="number" v-model="emp.adjustments.advance_deductions" @input="updateAdjustments(emp)" placeholder="0.00" min="0" step="0.01" class="adj-input"/>
                    </div>
                  </div>
                </div>

                <div class="adj-total">
                  <span>Net Adjustment:</span>
                  <span :class="['adj-val', getNetChangeClass(emp)]">
                    {{ calculateNetChange(emp) > 0 ? '+' : '' }}{{ formatCurrency(calculateNetChange(emp)) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button @click="closePreview" class="btn-secondary">Cancel</button>
            <div class="modal-footer-right">
              <button @click="saveAdjustments" class="btn-outline-modal">Save Only</button>
              <button @click="processWithAdjustments" class="btn-primary" :disabled="processing">
                <div v-if="processing" class="spinner-sm"></div>
                Process Payroll
              </button>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <!-- ── Single Adjustment Modal ─────────────────── -->
    <transition name="modal-fade">
      <div v-if="showAdjustmentModal" class="modal-overlay" @click.self="closeAdjustmentModal">
        <div class="modal-container modal-adj">
          <div class="modal-header">
            <div class="modal-title-wrap">
              <div class="modal-avatar">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="3"></circle>
                  <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                </svg>
              </div>
              <div>
                <h3 class="modal-name">Adjustments</h3>
                <p class="modal-sub">{{ adjustedEmployee?.name }}</p>
              </div>
            </div>
            <button @click="closeAdjustmentModal" class="modal-close">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>

          <div class="modal-body">

            <!-- ── Overtime Toggle ── -->
            <div class="single-adj-section">
              <div class="section-heading">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="10"></circle>
                  <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
                Overtime
              </div>

              <div class="overtime-toggle-row">
                <div class="ot-label-wrap">
                  <span>Does overtime apply for this employee?</span>
                </div>
                <div class="toggle-switch-wrap">
                  <label class="toggle-switch">
                    <input type="checkbox" v-model="currentAdjustments.overtime_applicable" @change="onSingleOvertimeToggle"/>
                    <span class="toggle-track"><span class="toggle-thumb"></span></span>
                  </label>
                  <span class="toggle-label">{{ currentAdjustments.overtime_applicable ? 'Yes — configure below' : 'No overtime' }}</span>
                </div>
              </div>

              <div v-if="currentAdjustments.overtime_applicable" class="overtime-detail" style="margin-top:0.75rem;">
                <!-- Mode Tabs -->
                <div class="ot-mode-tabs">
                  <button :class="['ot-tab', { active: currentAdjustments.overtime_mode === 'automatic' }]" @click="setSingleOvertimeMode('automatic')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="3"></circle>
                      <path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"></path>
                    </svg>
                    Automatic
                  </button>
                  <button :class="['ot-tab', { active: currentAdjustments.overtime_mode === 'manual' }]" @click="setSingleOvertimeMode('manual')">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                      <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    Manual Entry
                  </button>
                </div>

                <!-- Automatic -->
                <div v-if="currentAdjustments.overtime_mode === 'automatic'" class="ot-auto-panel">
                  <div v-if="singleOvertimeLoading" class="ot-loading">
                    <div class="spinner-sm" style="border-top-color:#6366f1; border-color:#e2e8f0;"></div>
                    <span>Fetching from attendance records...</span>
                  </div>
                  <div v-else-if="currentAdjustments.overtime_auto_data" class="ot-auto-result">
                    <div class="ot-auto-grid">
                      <div class="ot-auto-item weekday">
                        <div class="ot-auto-icon">
                          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                          </svg>
                        </div>
                        <div class="ot-auto-info">
                          <span class="ot-auto-type">Weekday OT</span>
                          <span class="ot-auto-rate">× 1.5 rate</span>
                        </div>
                        <div class="ot-auto-values">
                          <span class="ot-hours">{{ currentAdjustments.overtime_auto_data.weekday_hours }}h</span>
                          <span class="ot-pay">{{ formatCurrency(currentAdjustments.overtime_auto_data.weekday_pay) }}</span>
                        </div>
                      </div>
                      <div class="ot-auto-item weekend">
                        <div class="ot-auto-icon">
                          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="5"></circle>
                            <line x1="12" y1="1" x2="12" y2="3"></line>
                            <line x1="12" y1="21" x2="12" y2="23"></line>
                          </svg>
                        </div>
                        <div class="ot-auto-info">
                          <span class="ot-auto-type">Weekend / PH OT</span>
                          <span class="ot-auto-rate">× 2.5 rate</span>
                        </div>
                        <div class="ot-auto-values">
                          <span class="ot-hours">{{ currentAdjustments.overtime_auto_data.weekend_ph_hours }}h</span>
                          <span class="ot-pay">{{ formatCurrency(currentAdjustments.overtime_auto_data.weekend_ph_pay) }}</span>
                        </div>
                      </div>
                    </div>
                    <div class="ot-auto-total">
                      <span>Total Overtime Pay</span>
                      <strong>{{ formatCurrency(currentAdjustments.overtime_auto_data.total_pay) }}</strong>
                    </div>
                    <button class="ot-refresh-btn" @click="fetchSingleAutoOvertime">
                      <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M23 4v6h-6"></path>
                        <path d="M1 20v-6h6"></path>
                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                      </svg>
                      Re-fetch from attendance
                    </button>
                  </div>
                  <div v-else class="ot-auto-empty">
                    <button class="btn-outline btn-sm" @click="fetchSingleAutoOvertime">
                      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M23 4v6h-6"></path>
                        <path d="M1 20v-6h6"></path>
                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                      </svg>
                      Fetch from Attendance Records
                    </button>
                  </div>
                </div>

                <!-- Manual -->
                <div v-if="currentAdjustments.overtime_mode === 'manual'" class="ot-manual-panel">
                  <div class="ot-manual-grid">
                    <div class="ot-manual-block weekday-block">
                      <div class="ot-block-header">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                          <line x1="16" y1="2" x2="16" y2="6"></line>
                          <line x1="8" y1="2" x2="8" y2="6"></line>
                          <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <span>Weekday Overtime</span>
                        <span class="ot-rate-badge weekday">×1.5</span>
                      </div>
                      <div class="ot-manual-field">
                        <label>Hours</label>
                        <input type="number" v-model="currentAdjustments.overtime_weekday_hours" @input="recalcSingleManualOvertime" placeholder="0.00" min="0" step="0.5" class="adj-input ot-hours-input"/>
                      </div>
                      <div class="ot-calc-preview" v-if="currentAdjustments.overtime_weekday_hours > 0">
                        <span>= {{ formatCurrency(calcSingleWeekdayPay()) }}</span>
                      </div>
                    </div>
                    <div class="ot-manual-block weekend-block">
                      <div class="ot-block-header">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <circle cx="12" cy="12" r="5"></circle>
                          <line x1="12" y1="1" x2="12" y2="3"></line>
                          <line x1="12" y1="21" x2="12" y2="23"></line>
                        </svg>
                        <span>Weekend / Public Holiday OT</span>
                        <span class="ot-rate-badge weekend">×2.5</span>
                      </div>
                      <div class="ot-manual-field">
                        <label>Hours</label>
                        <input type="number" v-model="currentAdjustments.overtime_weekend_ph_hours" @input="recalcSingleManualOvertime" placeholder="0.00" min="0" step="0.5" class="adj-input ot-hours-input"/>
                      </div>
                      <div class="ot-calc-preview" v-if="currentAdjustments.overtime_weekend_ph_hours > 0">
                        <span>= {{ formatCurrency(calcSingleWeekendPhPay()) }}</span>
                      </div>
                    </div>
                  </div>
                  <div class="ot-manual-summary" v-if="currentAdjustments.overtime_bonus > 0">
                    <div class="ot-summary-line">
                      <span>Weekday ({{ currentAdjustments.overtime_weekday_hours || 0 }}h × ×1.5)</span>
                      <span>{{ formatCurrency(calcSingleWeekdayPay()) }}</span>
                    </div>
                    <div class="ot-summary-line">
                      <span>Weekend/PH ({{ currentAdjustments.overtime_weekend_ph_hours || 0 }}h × ×2.5)</span>
                      <span>{{ formatCurrency(calcSingleWeekendPhPay()) }}</span>
                    </div>
                    <div class="ot-summary-total">
                      <span>Total Overtime Pay</span>
                      <strong>{{ formatCurrency(currentAdjustments.overtime_bonus) }}</strong>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- ── Other Adjustments ── -->
            <div class="single-adj-section">
              <div class="section-heading">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Other Additions
              </div>
              <div class="adj-form-grid">
                <div class="adj-field">
                  <label>Other Bonuses / Allowances</label>
                  <input type="number" v-model="currentAdjustments.other_bonuses" @input="updateSingleAdjustments" class="adj-input" placeholder="0.00"/>
                </div>
              </div>
            </div>

            <div class="single-adj-section">
              <div class="section-heading">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Deductions
              </div>
              <div class="adj-form-grid">
                <div class="adj-field">
                  <label>Loan Deductions</label>
                  <input type="number" v-model="currentAdjustments.loan_deductions" @input="updateSingleAdjustments" class="adj-input" placeholder="0.00"/>
                </div>
                <div class="adj-field">
                  <label>Advance Deductions</label>
                  <input type="number" v-model="currentAdjustments.advance_deductions" @input="updateSingleAdjustments" class="adj-input" placeholder="0.00"/>
                </div>
              </div>
            </div>

            <div class="adj-net-summary">
              <span>Total Adjustments:</span>
              <span :class="getCurrentNetChangeClass()">
                {{ formatCurrency(currentAdjustments.bonuses - currentAdjustments.deductions) }}
              </span>
            </div>
          </div>

          <div class="modal-footer">
            <button @click="closeAdjustmentModal" class="btn-secondary">Cancel</button>
            <button @click="saveEmployeeAdjustments" class="btn-primary">Save Changes</button>
          </div>
        </div>
      </div>
    </transition>

  </div>
</template>

<script>
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'

export default {
  name: 'PayrollProcessing',

  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },

  data() {
    return {
      pageName: 'Payroll Processing',
      employees: [],
      selectedBusinessId: '',
      businesses: [],
      processing: false,
      loading: true,
      error: null,
      successMessage: null,
      taxConfigWarning: null,
      searchQuery: '',
      filterStatus: 'all',
      payrollPeriod: '',
      startDate: '',
      endDate: '',
      statusUpdating: null,

      // Revert modal
      showRevertModal: false,
      revertTarget: [],
      revertReason: '',

      // Preview / Adjustments modals
      showPreviewModal: false,
      showAdjustmentModal: false,
      previewEmployees: [],
      adjustedEmployee: null,
      singleOvertimeLoading: false,

      currentAdjustments: {
        overtime_applicable:     false,
        overtime_mode:           'manual',   // 'manual' | 'automatic'
        overtime_weekday_hours:  0,
        overtime_weekend_ph_hours: 0,
        overtime_bonus:          0,
        overtime_auto_data:      null,
        other_bonuses:           0,
        loan_deductions:         0,
        advance_deductions:      0,
        bonuses:                 0,
        deductions:              0
      },

      pagination: {
        currentPage: 1, pageSize: 10,
        totalPages: 0, startIndex: 0, endIndex: 0
      }
    }
  },

  computed: {
    pendingCount() { return this.employees.filter(e => e.payroll_status === 'pending').length },
    paidCount()    { return this.employees.filter(e => e.payroll_status === 'paid').length },

    totalPayroll() {
      return this.employees
        .filter(e => e.payroll_status === 'pending')
        .reduce((s, e) => s + (e.net_pay || 0), 0)
    },

    filteredEmployees() {
      let f = this.employees
      if (this.filterStatus !== 'all') f = f.filter(e => e.payroll_status === this.filterStatus)
      if (this.searchQuery) {
        const q = this.searchQuery.toLowerCase()
        f = f.filter(e =>
          e.name.toLowerCase().includes(q) ||
          e.position.toLowerCase().includes(q) ||
          String(e.id).includes(q) ||
          (e.business_name && e.business_name.toLowerCase().includes(q))
        )
      }
      return f
    },

    paginatedEmployees() {
      this.updatePagination()
      return this.filteredEmployees.slice(this.pagination.startIndex, this.pagination.endIndex)
    },

    selectedEmployees() { return this.employees.filter(e => e.selected) },
    selectedCount()     { return this.selectedEmployees.length },
    allSelected()       { return this.filteredEmployees.length > 0 && this.filteredEmployees.every(e => e.selected) },

    selectedAllPaid()    { return this.selectedCount > 0 && this.selectedEmployees.every(e => e.payroll_status === 'paid') },
    selectedAllPending() { return this.selectedCount > 0 && this.selectedEmployees.every(e => e.payroll_status === 'pending') },

    previewTotalGross()       { return this.previewEmployees.reduce((s, e) => s + (e.gross_salary || 0), 0) },
    previewTotalNet()         { return this.previewEmployees.reduce((s, e) => s + this.calculateAdjustedNet(e), 0) },
    previewTotalAdjustments() {
      return this.previewEmployees.reduce((s, e) => {
        const a = e.adjustments || {}
        return s + (a.bonuses || 0) - (a.deductions || 0)
      }, 0)
    }
  },

  watch: {
    searchQuery()       { this.pagination.currentPage = 1 },
    filterStatus()      { this.pagination.currentPage = 1 },
    filteredEmployees() { this.updatePagination() }
  },

  async mounted() {
    if (this.authStore.isAdmin) await this.fetchBusinesses()
    await this.initializePayrollPeriod()
    await this.fetchEmployees()
  },

  methods: {

    // ── Data ──────────────────────────────────────────────────────────────────

    async fetchBusinesses() {
      try {
        const r = await axios.get('/api/admin/businesses')
        this.businesses = r.data.data || []
      } catch (e) { console.error('Failed to fetch businesses:', e) }
    },

    onBusinessFilterChange() { this.pagination.currentPage = 1; this.fetchEmployees() },

    async fetchEmployees() {
      this.loading = true; this.error = null
      try {
        const params = { payroll_period: this.payrollPeriod, start_date: this.startDate, end_date: this.endDate, per_page: 1000 }
        if (this.selectedBusinessId) params.business_id = this.selectedBusinessId
        const r = await axios.get('/api/admin/payroll/employees-summary', { params })
        this.employees = (r.data.data || []).map(emp => ({
          id: emp.id, name: emp.name, email: emp.email,
          position: emp.position || emp.department || 'Unassigned',
          business_id: emp.business_id, business_name: emp.business_name || 'No Business',
          base_salary: emp.base_salary || 0, gross_salary: emp.gross_salary || 0,
          net_pay: emp.net_pay || 0, payroll_status: emp.payroll_status || 'pending',
          selected: false,
          adjustments: emp.adjustments || this.defaultAdjustments()
        }))
        this.updatePagination()
      } catch (err) { this.handleError(err) }
      finally { this.loading = false }
    },

    defaultAdjustments() {
      return {
        overtime_applicable:      false,
        overtime_mode:            'manual',
        overtime_weekday_hours:   0,
        overtime_weekend_ph_hours: 0,
        overtime_bonus:           0,
        overtime_auto_data:       null,
        overtime_loading:         false,
        other_bonuses:            0,
        loan_deductions:          0,
        advance_deductions:       0,
        bonuses:                  0,
        deductions:               0
      }
    },

    async refreshPayrollData() { await this.fetchEmployees(); this.showSuccess('Data refreshed!') },

    // ── Status Update ─────────────────────────────────────────────────────────

    togglePayrollStatus(employee) {
      if (employee.payroll_status === 'paid') this.openRevertModal([employee])
      else this.markSinglePaid(employee)
    },

    revertToPending(employee) { this.openRevertModal([employee]) },

    async markSinglePaid(employee) {
      if (!confirm(`Mark ${employee.name} as paid for ${this.payrollPeriod}?`)) return
      this.statusUpdating = employee.id
      try {
        await axios.post('/api/admin/payroll/update-status', {
          employee_ids: [employee.id], status: 'paid',
          payroll_period: this.payrollPeriod, start_date: this.startDate, end_date: this.endDate
        })
        employee.payroll_status = 'paid'
        this.showSuccess(`${employee.name} marked as paid.`)
      } catch (err) { this.handleError(err) }
      finally { this.statusUpdating = null }
    },

    async bulkMarkPaid() {
      const sel = this.employees.filter(e => e.selected && e.payroll_status === 'pending')
      if (!sel.length) { this.showError('No pending employees selected.'); return }
      if (!confirm(`Mark ${sel.length} employee(s) as paid for ${this.payrollPeriod}?`)) return
      this.statusUpdating = 'bulk'
      try {
        await axios.post('/api/admin/payroll/update-status', {
          employee_ids: sel.map(e => e.id), status: 'paid',
          payroll_period: this.payrollPeriod, start_date: this.startDate, end_date: this.endDate
        })
        sel.forEach(e => { e.payroll_status = 'paid' })
        this.showSuccess(`${sel.length} employee(s) marked as paid.`)
        this.clearSelection()
      } catch (err) { this.handleError(err) }
      finally { this.statusUpdating = null }
    },

    openRevertModal(employees) { this.revertTarget = employees; this.revertReason = ''; this.showRevertModal = true },
    closeRevertModal()         { this.showRevertModal = false; this.revertTarget = []; this.revertReason = '' },
    bulkRevertToPending() {
      const sel = this.employees.filter(e => e.selected && e.payroll_status === 'paid')
      if (!sel.length) { this.showError('No paid employees selected.'); return }
      this.openRevertModal(sel)
    },

    async confirmRevert() {
      if (!this.revertTarget.length) return
      this.statusUpdating = this.revertTarget.length === 1 ? this.revertTarget[0].id : 'bulk'
      try {
        const response = await axios.post('/api/admin/payroll/update-status', {
          employee_ids: this.revertTarget.map(e => e.id), status: 'pending',
          payroll_period: this.payrollPeriod, start_date: this.startDate, end_date: this.endDate,
          reason: this.revertReason || null
        })
        const count = this.revertTarget.length
        const deletedCount = response.data.deleted_count ?? count
        this.showSuccess(
          count === 1
            ? `${this.revertTarget[0].name} reverted to pending. Payslip deleted.`
            : `${count} employees reverted. ${deletedCount} payslip(s) deleted.`
        )
        this.clearSelection(); this.closeRevertModal()
        await this.fetchEmployees()
      } catch (err) { this.handleError(err) }
      finally { this.statusUpdating = null }
    },

    // ── Overtime — shared helpers ─────────────────────────────────────────────

    calcHourlyRate(emp) {
      const salary = emp?.base_salary || emp?.adjustments?.base_salary || 0
      return salary > 0 ? salary / 173.33 : 0
    },

    calcWeekdayPay(emp) {
      const h = parseFloat(emp.adjustments.overtime_weekday_hours) || 0
      return h * this.calcHourlyRate(emp) * 1.5
    },

    calcWeekendPhPay(emp) {
      const h = parseFloat(emp.adjustments.overtime_weekend_ph_hours) || 0
      return h * this.calcHourlyRate(emp) * 2.5
    },

    recalcManualOvertime(emp) {
      const pay = this.calcWeekdayPay(emp) + this.calcWeekendPhPay(emp)
      emp.adjustments.overtime_bonus = Math.round(pay * 100) / 100
      this.updateAdjustments(emp)
    },

    /**
     * Fetch overtime breakdown from backend for a specific employee.
     * Expects GET /api/admin/payroll/overtime-breakdown?employee_id=&start_date=&end_date=
     * Returns: { weekday_hours, weekday_pay, weekend_ph_hours, weekend_ph_pay, total_pay }
     */
    async fetchAutoOvertime(emp) {
      emp.adjustments.overtime_loading = true
      try {
        const r = await axios.get('/api/admin/payroll/overtime-breakdown', {
          params: { employee_id: emp.id, start_date: this.startDate, end_date: this.endDate }
        })
        emp.adjustments.overtime_auto_data = r.data.data || r.data
        emp.adjustments.overtime_bonus = emp.adjustments.overtime_auto_data.total_pay || 0
        this.updateAdjustments(emp)
      } catch (err) {
        this.showError(`Failed to fetch overtime for ${emp.name}: ${err.response?.data?.message || err.message}`)
      } finally {
        emp.adjustments.overtime_loading = false
      }
    },

    onOvertimeToggle(emp) {
      if (!emp.adjustments.overtime_applicable) {
        // Reset overtime when disabled
        emp.adjustments.overtime_bonus            = 0
        emp.adjustments.overtime_weekday_hours    = 0
        emp.adjustments.overtime_weekend_ph_hours = 0
        emp.adjustments.overtime_auto_data        = null
        this.updateAdjustments(emp)
      }
    },

    setOvertimeMode(emp, mode) {
      emp.adjustments.overtime_mode = mode
      // Reset overtime pay when switching modes
      emp.adjustments.overtime_bonus            = 0
      emp.adjustments.overtime_weekday_hours    = 0
      emp.adjustments.overtime_weekend_ph_hours = 0
      emp.adjustments.overtime_auto_data        = null
      this.updateAdjustments(emp)
    },

    // ── Overtime — single adjustment modal ───────────────────────────────────

    calcSingleWeekdayPay() {
      const h      = parseFloat(this.currentAdjustments.overtime_weekday_hours) || 0
      const salary = this.adjustedEmployee?.base_salary || 0
      const rate   = salary > 0 ? salary / 173.33 : 0
      return h * rate * 1.5
    },

    calcSingleWeekendPhPay() {
      const h      = parseFloat(this.currentAdjustments.overtime_weekend_ph_hours) || 0
      const salary = this.adjustedEmployee?.base_salary || 0
      const rate   = salary > 0 ? salary / 173.33 : 0
      return h * rate * 2.5
    },

    recalcSingleManualOvertime() {
      const pay = this.calcSingleWeekdayPay() + this.calcSingleWeekendPhPay()
      this.currentAdjustments.overtime_bonus = Math.round(pay * 100) / 100
      this.updateSingleAdjustments()
    },

    onSingleOvertimeToggle() {
      if (!this.currentAdjustments.overtime_applicable) {
        this.currentAdjustments.overtime_bonus            = 0
        this.currentAdjustments.overtime_weekday_hours    = 0
        this.currentAdjustments.overtime_weekend_ph_hours = 0
        this.currentAdjustments.overtime_auto_data        = null
        this.updateSingleAdjustments()
      }
    },

    setSingleOvertimeMode(mode) {
      this.currentAdjustments.overtime_mode             = mode
      this.currentAdjustments.overtime_bonus            = 0
      this.currentAdjustments.overtime_weekday_hours    = 0
      this.currentAdjustments.overtime_weekend_ph_hours = 0
      this.currentAdjustments.overtime_auto_data        = null
      this.updateSingleAdjustments()
    },

    async fetchSingleAutoOvertime() {
      this.singleOvertimeLoading = true
      try {
        const r = await axios.get('/api/admin/payroll/overtime-breakdown', {
          params: { employee_id: this.adjustedEmployee.id, start_date: this.startDate, end_date: this.endDate }
        })
        this.currentAdjustments.overtime_auto_data = r.data.data || r.data
        this.currentAdjustments.overtime_bonus = this.currentAdjustments.overtime_auto_data.total_pay || 0
        this.updateSingleAdjustments()
      } catch (err) {
        this.showError(`Failed to fetch overtime: ${err.response?.data?.message || err.message}`)
      } finally {
        this.singleOvertimeLoading = false
      }
    },

    updateSingleAdjustments() {
      this.currentAdjustments.bonuses    = (parseFloat(this.currentAdjustments.overtime_bonus) || 0) + (parseFloat(this.currentAdjustments.other_bonuses) || 0)
      this.currentAdjustments.deductions = (parseFloat(this.currentAdjustments.loan_deductions) || 0) + (parseFloat(this.currentAdjustments.advance_deductions) || 0)
    },

    // ── Pagination ────────────────────────────────────────────────────────────

    updatePagination() {
      const total = this.filteredEmployees.length
      this.pagination.totalPages  = Math.ceil(total / this.pagination.pageSize) || 1
      if (this.pagination.currentPage > this.pagination.totalPages) this.pagination.currentPage = this.pagination.totalPages
      this.pagination.startIndex = (this.pagination.currentPage - 1) * this.pagination.pageSize
      this.pagination.endIndex   = Math.min(this.pagination.startIndex + this.pagination.pageSize, total)
    },

    nextPage()         { if (this.pagination.currentPage < this.pagination.totalPages) this.pagination.currentPage++ },
    previousPage()     { if (this.pagination.currentPage > 1) this.pagination.currentPage-- },
    onPageSizeChange() { this.pagination.currentPage = 1; this.updatePagination() },

    // ── Date Range ────────────────────────────────────────────────────────────

    updateDateRange() {
      if (this.payrollPeriod) {
        const [y, m] = this.payrollPeriod.split('-')
        this.startDate = `${y}-${m}-01`
        const last = new Date(y, parseInt(m), 0)
        this.endDate = `${y}-${m}-${last.getDate().toString().padStart(2, '0')}`
      }
    },

    initializePayrollPeriod() {
      const now = new Date()
      this.payrollPeriod = `${now.getFullYear()}-${(now.getMonth() + 1).toString().padStart(2, '0')}`
      this.updateDateRange()
    },

    // ── Preview / Process ─────────────────────────────────────────────────────

    showPreview() {
      const sel = this.employees.filter(e => e.payroll_status === 'pending' && e.selected)
      if (!sel.length) { this.showError('Please select pending employees to preview.'); return }
      this.previewEmployees = JSON.parse(JSON.stringify(sel))
      // Ensure each has full adjustment defaults
      this.previewEmployees.forEach(emp => {
        emp.adjustments = { ...this.defaultAdjustments(), ...(emp.adjustments || {}) }
      })
      this.showPreviewModal = true
    },

    closePreview() { this.showPreviewModal = false; this.previewEmployees = [] },

    async processPayroll() {
      const pending = this.employees.filter(e => e.payroll_status === 'pending')
      if (!pending.length) return this.showError('No pending payroll.')
      if (!confirm(`Process ${pending.length} pending employee(s)?`)) return
      this.processing = true
      try {
        await axios.post('/api/admin/payroll/process', {
          employee_ids: pending.map(e => e.id),
          adjustments:  pending.reduce((a, e) => { if (e.adjustments) a[e.id] = e.adjustments; return a }, {}),
          payroll_period: this.payrollPeriod, start_date: this.startDate, end_date: this.endDate
        })
        await this.fetchEmployees()
        this.showSuccess('Payroll processed successfully!')
      } catch (err) { this.handlePayrollError(err) }
      finally { this.processing = false }
    },

    async processWithAdjustments() {
      this.processing = true
      try {
        await axios.post('/api/admin/payroll/process', {
          employee_ids: this.previewEmployees.map(e => e.id),
          adjustments:  this.previewEmployees.reduce((a, e) => { a[e.id] = e.adjustments; return a }, {}),
          payroll_period: this.payrollPeriod, start_date: this.startDate, end_date: this.endDate
        })
        await this.fetchEmployees()
        this.showSuccess('Payroll processed successfully!')
        this.closePreview()
      } catch (err) { this.handlePayrollError(err) }
      finally { this.processing = false }
    },

    // ── Adjustments ───────────────────────────────────────────────────────────

    editAdjustments(employee) {
      this.adjustedEmployee = employee
      this.currentAdjustments = {
        overtime_applicable:      employee.adjustments?.overtime_applicable      ?? false,
        overtime_mode:            employee.adjustments?.overtime_mode            ?? 'manual',
        overtime_weekday_hours:   employee.adjustments?.overtime_weekday_hours   ?? 0,
        overtime_weekend_ph_hours: employee.adjustments?.overtime_weekend_ph_hours ?? 0,
        overtime_bonus:           employee.adjustments?.overtime_bonus           ?? 0,
        overtime_auto_data:       employee.adjustments?.overtime_auto_data       ?? null,
        other_bonuses:            employee.adjustments?.other_bonuses            ?? 0,
        loan_deductions:          employee.adjustments?.loan_deductions          ?? 0,
        advance_deductions:       employee.adjustments?.advance_deductions       ?? 0,
        bonuses:                  employee.adjustments?.bonuses                  ?? 0,
        deductions:               employee.adjustments?.deductions               ?? 0
      }
      this.singleOvertimeLoading = false
      this.showAdjustmentModal = true
    },

    closeAdjustmentModal() { this.showAdjustmentModal = false; this.adjustedEmployee = null },

    updateAdjustments(employee) {
      const a      = employee.adjustments || employee
      a.bonuses    = (parseFloat(a.overtime_bonus)    || 0) + (parseFloat(a.other_bonuses)      || 0)
      a.deductions = (parseFloat(a.loan_deductions)   || 0) + (parseFloat(a.advance_deductions) || 0)
    },

    calculateAdjustedNet(emp)  { const a = emp.adjustments || {}; return (emp.net_pay || 0) + (a.bonuses || 0) - (a.deductions || 0) },
    calculateNetChange(emp)    { const a = emp.adjustments || {}; return (a.bonuses || 0) - (a.deductions || 0) },
    getNetChangeClass(emp)     { const c = this.calculateNetChange(emp); return c > 0 ? 'text-success' : c < 0 ? 'text-danger' : '' },
    getCurrentNetChangeClass() {
      const c = this.currentAdjustments.bonuses - this.currentAdjustments.deductions
      return c > 0 ? 'text-success' : c < 0 ? 'text-danger' : ''
    },

    saveEmployeeAdjustments() {
      if (this.adjustedEmployee) {
        if (!this.adjustedEmployee.adjustments) this.adjustedEmployee.adjustments = {}
        Object.assign(this.adjustedEmployee.adjustments, this.currentAdjustments)
        this.updateAdjustments({ adjustments: this.adjustedEmployee.adjustments })
        this.showSuccess('Adjustments saved!')
        this.closeAdjustmentModal()
      }
    },

    saveAdjustments() {
      this.previewEmployees.forEach(pe => {
        const me = this.employees.find(e => e.id === pe.id)
        if (me) me.adjustments = { ...pe.adjustments }
      })
      this.showSuccess('Adjustments saved locally!')
      this.closePreview()
    },

    showBulkAdjustments() {
      const sel = this.employees.filter(e => e.selected)
      if (!sel.length) { this.showError('Select employees first.'); return }
      this.previewEmployees = JSON.parse(JSON.stringify(sel))
      this.previewEmployees.forEach(emp => {
        emp.adjustments = { ...this.defaultAdjustments(), ...(emp.adjustments || {}) }
      })
      this.showPreviewModal = true
    },

    // ── Selection ─────────────────────────────────────────────────────────────

    toggleSelectAll() { const ns = !this.allSelected; this.filteredEmployees.forEach(e => e.selected = ns) },
    clearSelection()  { this.employees.forEach(e => e.selected = false) },

    // ── Misc ──────────────────────────────────────────────────────────────────

    addEmployee()  { this.$router.push({ name: 'admin.employees.create' }) },
    clearSearch()  { this.searchQuery = '' },
    dismissError() { this.error = null },

    showSuccess(msg) { this.successMessage = msg; setTimeout(() => { this.successMessage = null }, 5000) },
    showError(msg)   { this.error = msg; setTimeout(() => { this.error = null }, 6000) },
    handlePayrollError(err) { this.showError(err.response?.data?.message || 'Payroll processing failed') },
    handleError(err) {
      const msg = err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join(' ')
        : err.response?.data?.message || err.message || 'An error occurred'
      this.showError(msg)
    },

    viewDetails(id) { alert(`View details for employee #${id}`) },

    async deleteEmployee(id) {
      if (!confirm('Delete this employee?')) return
      try { await axios.delete(`/api/admin/employees/${id}`); await this.fetchEmployees(); this.showSuccess('Employee deleted.') }
      catch (err) { this.handleError(err) }
    },

    // ── Formatters ────────────────────────────────────────────────────────────

    formatCurrency(amount) {
      if (amount === null || amount === undefined) return '—'
      return new Intl.NumberFormat('en-ZM', { style: 'currency', currency: 'ZMW', minimumFractionDigits: 2 }).format(amount)
    },

    formatCurrencyShort(amount) {
      const n = parseFloat(amount) || 0
      if (n >= 1_000_000) return `ZMW ${(n / 1_000_000).toFixed(1)}M`
      if (n >= 1_000)     return `ZMW ${(n / 1_000).toFixed(1)}K`
      return `ZMW ${n.toFixed(0)}`
    },

    getInitials(name) {
      if (!name) return '?'
      return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2)
    },

    getAvatarColor(name) {
      const colors = ['#6366f1','#8b5cf6','#3b82f6','#10b981','#f59e0b','#ef4444','#ec4899','#14b8a6']
      let h = 0
      for (let i = 0; i < name.length; i++) h = name.charCodeAt(i) + ((h << 5) - h)
      return colors[Math.abs(h) % colors.length]
    },

    formatStatus(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : 'Unknown' }
  }
}
</script>

<style scoped>
/* ── Base ──────────────────────────────────────────── */
.payroll-page {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
}

/* ── Sticky Header ───────────────────────────────── */
.fixed-header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(248, 250, 252, 0.9);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  padding: 1rem 2rem 0;
  box-sizing: border-box;
  box-shadow: 0 1px 0 rgba(0,0,0,0.04);
}

.dashboard-header-card {
  background: white;
  border-radius: 12px;
  padding: 1rem 1.5rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0;
  margin-bottom: 1rem;
  position: relative;
  overflow: hidden;
}

/* Neutral grey accent bar — no color gradient */
.header-card-accent {
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  background: #e2e8f0;
}
.user-greeting { display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; }
.avatar-section { display: flex; align-items: center; gap: 1rem; }
/* Avatar: neutral grey, no gradient */
.avatar {
  width: 44px; height: 44px;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  border: 1px solid #e2e8f0;
  border-radius: 10px; display: flex; align-items: center;
  justify-content: center; color: white;
  flex-shrink: 0;
}
.user-info { display: flex; flex-direction: column; gap: 0.2rem; }
.greeting  { margin: 0; font-size: 1.25rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
.subtitle  { margin: 0; color: #64748b; font-size: 0.8rem; }
.role-meta { margin-top: 0.125rem; }
/* Role badge: neutral, no blue */
.role-badge {
  background: #f1f5f9; border: 1px solid #e2e8f0;
  padding: 0.125rem 0.6rem; border-radius: 8px;
  font-size: 0.7rem; font-weight: 600; color: #475569;
  display: inline-block;
}
.header-actions { display: flex; gap: 0.5rem; flex-shrink: 0; flex-wrap: wrap; align-items: center; }

/* ── Dashboard Content ───────────────────────────── */
.dashboard-content {
  flex: 1;
  padding: 0 2rem 2rem;
  max-width: 1400px;
  width: 100%;
  margin: 0 auto;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* ── Buttons ─────────────────────────────────────── */
/* Primary button: dark slate, no purple gradient */
.btn-primary {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: #334155; color: white;
  border: none; padding: 0.5rem 1.1rem; border-radius: 8px;
  font-size: 0.82rem; font-weight: 600; cursor: pointer;
  transition: all 0.2s; font-family: inherit;
}
.btn-primary:hover:not(:disabled) { background: #1e293b; transform: translateY(-1px); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.btn-outline {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.45rem 0.9rem; background: white; border: 1px solid #e2e8f0;
  color: #475569; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-outline:hover:not(:disabled) { background: #f8fafc; border-color: #cbd5e1; color: #1e293b; }
.btn-outline:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-sm { padding: 0.3rem 0.7rem; font-size: 0.76rem; }
.btn-outline-modal {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.5rem 1.1rem; background: white; border: 1px solid #e2e8f0;
  color: #475569; border-radius: 8px; font-size: 0.875rem; font-weight: 600;
  cursor: pointer; font-family: inherit; transition: all 0.2s;
}
.btn-outline-modal:hover { background: #f8fafc; }
.btn-secondary {
  padding: 0.5rem 1.1rem; background: #f1f5f9; color: #475569;
  border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.875rem;
  font-weight: 600; cursor: pointer; font-family: inherit; transition: background 0.15s;
}
.btn-secondary:hover { background: #e2e8f0; }
.btn-success {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.3rem 0.7rem; background: #10b981; color: white;
  border: none; border-radius: 6px; font-size: 0.76rem; font-weight: 600;
  cursor: pointer; font-family: inherit; transition: background 0.15s;
}
.btn-success:hover:not(:disabled) { background: #059669; }
.btn-success:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-warn {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.3rem 0.7rem; background: #f59e0b; color: white;
  border: none; border-radius: 6px; font-size: 0.76rem; font-weight: 600;
  cursor: pointer; font-family: inherit; transition: background 0.15s;
}
.btn-warn.btn-sm { padding: 0.3rem 0.7rem; font-size: 0.76rem; }
.btn-warn:not(.btn-sm) { padding: 0.5rem 1.1rem; font-size: 0.875rem; border-radius: 8px; }
.btn-warn:hover:not(:disabled) { background: #d97706; }
.btn-warn:disabled { opacity: 0.6; cursor: not-allowed; }
.btn-clear {
  padding: 0.2rem 0.5rem; background: none; border: none;
  color: #475569; font-size: 0.76rem; font-weight: 600;
  cursor: pointer; font-family: inherit;
}
.btn-clear:hover { text-decoration: underline; }

/* ── Notifications ───────────────────────────────── */
.notifications-wrap { display: flex; flex-direction: column; gap: 0.5rem; }
.notification {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 0.875rem 1.25rem; border-radius: 10px;
  font-size: 0.875rem; font-weight: 500; border-left: 4px solid;
  background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
.notification span { flex: 1; }
.notification.warning { border-left-color: #f59e0b; color: #92400e; }
.notification.error   { border-left-color: #ef4444; color: #991b1b; }
.notification.success { border-left-color: #10b981; color: #065f46; }
.notif-close { background: none; border: none; color: inherit; font-size: 1.1rem; cursor: pointer; opacity: 0.6; line-height: 1; }
.notif-close:hover { opacity: 1; }

/* ── Section Cards ───────────────────────────────── */
.metrics-section, .table-section {
  background: white; border-radius: 16px;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05);
  border: 1px solid #e2e8f0; padding: 1.5rem;
}
h2 { font-size: 1.1rem; font-weight: 600; margin: 0 0 1.25rem; color: #334155; }

/* ── Metrics — no colored accents ───────────────── */
.metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; }
.metric-card {
  padding: 1.25rem; background: #f8fafc; border-radius: 12px;
  display: flex; flex-direction: column; align-items: center; text-align: center;
  border: 1px solid #e2e8f0; position: relative; overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s; cursor: pointer;
}
.metric-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px -4px rgba(0,0,0,0.08); border-color: #cbd5e1; }
/* Remove the colored top bar entirely */
.metric-card::before { display: none; }
/* Neutral icon wrap — overrides inline rgba colors */
.metric-icon-wrap {
  width: 40px; height: 40px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 0.75rem;
  background: #f1f5f9 !important;
}
/* Neutralise SVG stroke colors set inline on metric icons */
.metric-icon-wrap svg { stroke: #64748b !important; }
.metric-value { font-size: 1.8rem; font-weight: 800; color: #0f172a; line-height: 1.1; margin-bottom: 0.25rem; }
.metric-label { font-size: 0.78rem; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.04em; }

/* ── Controls ────────────────────────────────────── */
.controls-bar { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1rem; gap: 1rem; flex-wrap: wrap; }
.filters-row  { display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: flex-end; }
.filter-group { display: flex; flex-direction: column; gap: 0.3rem; }
.filter-group label { font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
.filter-select {
  padding: 0.45rem 2rem 0.45rem 0.75rem; border: 1px solid #e2e8f0;
  border-radius: 8px; background: #f8fafc; color: #334155;
  font-size: 0.82rem; font-weight: 500; cursor: pointer; appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 0.6rem center;
  transition: all 0.2s; font-family: inherit;
}
.filter-select:focus { outline: none; border-color: #94a3b8; box-shadow: 0 0 0 3px rgba(148,163,184,0.15); }
.date-input { background-image: none; padding-right: 0.75rem; }
.search-wrapper { position: relative; }
.search-icon { position: absolute; left: 0.6rem; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
.filter-input {
  padding: 0.45rem 1.75rem 0.45rem 1.9rem; border: 1px solid #e2e8f0;
  border-radius: 8px; background: #f8fafc; color: #334155;
  font-size: 0.82rem; width: 170px; font-family: inherit;
}
.filter-input:focus { outline: none; border-color: #94a3b8; box-shadow: 0 0 0 3px rgba(148,163,184,0.15); }
.filter-input::placeholder { color: #94a3b8; }
.search-clear { position: absolute; right: 0.5rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; font-size: 1rem; }
.filter-pills { display: flex; background: #f1f5f9; border-radius: 8px; padding: 2px; gap: 1px; }
.pill { padding: 0.3rem 0.7rem; border: none; background: transparent; border-radius: 6px; font-size: 0.75rem; font-weight: 600; color: #64748b; cursor: pointer; transition: all 0.15s; font-family: inherit; }
.pill.active { background: white; color: #334155; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.controls-right { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }
.records-count { font-size: 0.78rem; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 0.2rem 0.7rem; border-radius: 9999px; }

/* ── Bulk Bar ─────────────────────────────────────── */
.bulk-bar {
  display: flex; justify-content: space-between; align-items: center;
  padding: 0.75rem 1rem; background: #f8fafc; border: 1px solid #e2e8f0;
  border-radius: 10px; margin-bottom: 0.875rem; gap: 1rem;
}
.bulk-count   { font-size: 0.82rem; font-weight: 700; color: #334155; }
.bulk-actions { display: flex; gap: 0.5rem; align-items: center; }

/* ── States ──────────────────────────────────────── */
.spinner    { width: 40px; height: 40px; border: 3px solid #e2e8f0; border-top-color: #64748b; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto; }
.spinner-sm { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,0.3); border-top-color: white; border-radius: 50%; animation: spin 0.8s linear infinite; display: inline-block; }
.spinner-xs { width: 9px; height: 9px; border: 1.5px solid rgba(255,255,255,0.4); border-top-color: white; border-radius: 50%; animation: spin 0.7s linear infinite; display: inline-block; }
@keyframes spin { to { transform: rotate(360deg); } }
.empty-state { text-align: center; padding: 4rem 2rem; display: flex; flex-direction: column; align-items: center; gap: 0.875rem; }
.empty-icon-wrap { width: 64px; height: 64px; border-radius: 16px; background: #f1f5f9; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: #94a3b8; }
.empty-state h3 { margin: 0; font-size: 1.1rem; font-weight: 700; color: #1e293b; }
.empty-state p  { margin: 0; font-size: 0.875rem; color: #64748b; }
.empty-actions { display: flex; gap: 0.75rem; margin-top: 0.25rem; }

/* ── Table ───────────────────────────────────────── */
.table-top-bar { display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 0; margin-bottom: 0.5rem; border-bottom: 1px solid #f1f5f9; }
.table-range { font-size: 0.82rem; color: #64748b; }
.table-top-right { display: flex; align-items: center; gap: 0.75rem; }
.page-nav { display: flex; align-items: center; gap: 0.35rem; }
.page-btn {
  display: inline-flex; align-items: center; gap: 0.25rem;
  padding: 0.3rem 0.65rem; background: white; border: 1px solid #e2e8f0;
  border-radius: 6px; font-size: 0.75rem; font-weight: 600; color: #475569;
  cursor: pointer; font-family: inherit; transition: all 0.15s;
}
.page-btn:hover:not(:disabled) { border-color: #cbd5e1; color: #1e293b; background: #f8fafc; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-info { font-size: 0.78rem; color: #64748b; padding: 0 0.25rem; }
.page-size-select {
  padding: 0.3rem 1.5rem 0.3rem 0.5rem; border: 1px solid #e2e8f0;
  border-radius: 6px; font-size: 0.75rem; background: white; appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 0.4rem center; cursor: pointer;
}
.list-header, .list-row { display: grid; grid-template-columns: 40px 2.5fr 1.2fr 1fr 1fr 1fr 1fr 0.9fr 0.9fr; padding: 0.7rem 0.75rem; align-items: center; gap: 0.75rem; }
.list-header { background: #f8fafc; border-radius: 8px; margin-bottom: 0.25rem; font-size: 0.68rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
.list-row { border-bottom: 1px solid #f1f5f9; transition: background 0.15s; border-radius: 6px; }
.list-row:hover    { background: #f8fafc; }
.list-row.selected { background: #f8fafc; }
.list-row.status-updating { opacity: 0.7; pointer-events: none; }
.cb { width: 16px; height: 16px; cursor: pointer; accent-color: #475569; }

.col-emp { display: flex; align-items: center; gap: 0.75rem; }

/* ─────────────────────────────────────────────────
   Employee avatar — single consistent blue #3b82f6
   !important overrides the inline :style binding
   from getAvatarColor() in the template.
───────────────────────────────────────────────── */
.emp-avatar {
  width: 36px; height: 36px; border-radius: 8px;
  background: #3b82f6 !important;
  color: white; display: flex; align-items: center; justify-content: center;
  font-size: 0.75rem; font-weight: 700; flex-shrink: 0;
}

.name-stack { display: flex; flex-direction: column; min-width: 0; }
.emp-name   { font-weight: 600; color: #1e293b; font-size: 0.875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.emp-meta   { font-size: 0.68rem; color: #94a3b8; }

.biz-chip { font-size: 0.72rem; font-weight: 600; color: #475569; background: #f1f5f9; border: 1px solid #e2e8f0; padding: 0.15rem 0.5rem; border-radius: 4px; }
.pos-tag  { font-size: 0.72rem; font-weight: 600; color: #475569; background: #f1f5f9; border: 1px solid #e2e8f0; padding: 0.15rem 0.5rem; border-radius: 4px; white-space: nowrap; }
.text-right   { text-align: right; }
.text-muted   { color: #94a3b8; font-size: 0.82rem; }
.text-info    { color: #3b82f6; }
.text-success { color: #10b981; }
.text-danger  { color: #ef4444; }
.mono { font-family: 'SFMono-Regular', Consolas, monospace; font-size: 0.82rem; }
.fw-bold { font-weight: 700; }

/* ── Status Badge ── */
.status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 0.25rem 0.6rem; border-radius: 9999px; font-size: 0.7rem; font-weight: 700; white-space: nowrap; border: none; font-family: inherit; }
.dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; flex-shrink: 0; }
.status-badge.pending { background: #fef3c7; color: #92400e; }
.status-badge.paid    { background: #d1fae5; color: #065f46; }
.status-badge.unknown, .status-badge.undefined { background: #f1f5f9; color: #64748b; }
.status-toggle { cursor: pointer; transition: all 0.15s; padding-right: 0.45rem; }
.status-toggle:hover:not(:disabled) { filter: brightness(0.9); transform: scale(1.04); box-shadow: 0 2px 8px rgba(0,0,0,0.12); }
.status-toggle:disabled { cursor: not-allowed; opacity: 0.7; }
.toggle-icon { opacity: 0; transition: opacity 0.15s; margin-left: 2px; }
.status-toggle:hover .toggle-icon { opacity: 1; }

/* ── Actions ── */
.action-group { display: flex; justify-content: flex-end; gap: 0.35rem; }
.action-btn { width: 28px; height: 28px; border-radius: 6px; border: 1px solid #e2e8f0; background: white; color: #64748b; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s; }
.action-btn:hover        { background: #f1f5f9; color: #334155; border-color: #cbd5e1; }
.action-btn.adjust:hover { background: #f1f5f9; color: #334155; border-color: #cbd5e1; }
.action-btn.revert:hover { background: #fffbeb; color: #d97706; border-color: #fcd34d; }
.action-btn.danger:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }
.action-btn:disabled     { opacity: 0.4; cursor: not-allowed; pointer-events: none; }

/* ── Modal ───────────────────────────────────────── */
.modal-overlay { position: fixed; inset: 0; background: rgba(15,23,42,0.45); backdrop-filter: blur(4px); z-index: 100; display: flex; justify-content: center; align-items: center; padding: 1rem; }
.modal-container { background: white; width: 100%; max-width: 520px; max-height: 90vh; border-radius: 16px; box-shadow: 0 25px 60px rgba(0,0,0,0.15); display: flex; flex-direction: column; overflow: hidden; border: 1px solid #e2e8f0; animation: slideUp 0.25s ease-out; }
.modal-container.modal-lg  { max-width: 900px; }
.modal-container.modal-adj { max-width: 600px; }
@keyframes slideUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }

/* Modal header — neutral, no dark blue gradient */
.modal-header {
  padding: 1.5rem 1.75rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;
}
.modal-title-wrap { display: flex; align-items: center; gap: 1rem; }
.modal-avatar {
  width: 44px; height: 44px;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 10px; display: flex; align-items: center;
  justify-content: center; color: #475569; flex-shrink: 0;
}
.modal-name { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin: 0; }
.modal-sub  { font-size: 0.82rem; color: #64748b; margin: 0.15rem 0 0; }
.modal-close {
  width: 34px; height: 34px; border-radius: 50%;
  border: 1px solid #e2e8f0; background: white; color: #475569;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.2s; flex-shrink: 0;
}
.modal-close:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }

.modal-body   { padding: 1.5rem 1.75rem; overflow-y: auto; flex: 1; }
.modal-footer { padding: 1.125rem 1.75rem; border-top: 1px solid #f1f5f9; background: #f8fafc; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
.modal-footer-right { display: flex; gap: 0.5rem; }

/* Modal Stats */
.modal-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.875rem; margin-bottom: 1.5rem; }
.modal-stat {
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
  padding: 0.875rem; text-align: center; display: flex;
  flex-direction: column; gap: 0.3rem; align-items: center;
}
/* Highlight stat: neutral dark slate instead of blue gradient */
.modal-stat.highlight {
  background: #1e293b;
  border-color: transparent;
}
.modal-stat.highlight small  { color: rgba(255,255,255,0.7); }
.modal-stat.highlight strong { color: white; }
.modal-stat small  { font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.modal-stat strong { font-size: 1rem; font-weight: 700; color: #1e293b; }

/* ── Revert Modal ── */
.revert-warning { display: flex; gap: 0.75rem; align-items: flex-start; padding: 0.875rem 1rem; background: #fffbeb; border: 1px solid #fcd34d; border-radius: 10px; margin-bottom: 1.25rem; font-size: 0.875rem; color: #78350f; line-height: 1.5; }
.revert-employee-list { border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
.revert-emp-row { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-bottom: 1px solid #f1f5f9; }
.revert-emp-row:last-child { border-bottom: none; }
.revert-more { padding: 0.6rem 1rem; font-size: 0.78rem; color: #64748b; font-weight: 600; text-align: center; background: #f8fafc; }

/* Small avatars in revert list — same single blue */
.emp-avatar-sm {
  width: 34px; height: 34px; border-radius: 8px;
  background: #3b82f6 !important;
  color: white; display: flex; align-items: center;
  justify-content: center; font-weight: 700; font-size: 0.75rem; flex-shrink: 0;
}

.form-group  { display: flex; flex-direction: column; gap: 0.4rem; }
.form-label  { font-size: 0.78rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
.form-textarea { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; background: #f8fafc; resize: vertical; font-family: inherit; font-size: 0.875rem; color: #334155; box-sizing: border-box; transition: border-color 0.2s; }
.form-textarea:focus { outline: none; border-color: #94a3b8; box-shadow: 0 0 0 3px rgba(148,163,184,0.15); }

/* ── Adjustment Cards ── */
.adj-list { display: flex; flex-direction: column; gap: 1rem; }
.adj-card { border: 1px solid #e2e8f0; border-radius: 10px; padding: 1rem; background: #fdfdfe; transition: border-color 0.2s; }
.adj-card.modified { border-color: #94a3b8; background: #fafafa; }
.adj-header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 0.875rem; border-bottom: 1px solid #f1f5f9; margin-bottom: 0.875rem; gap: 1rem; }
.adj-emp { display: flex; align-items: center; gap: 0.75rem; }
.adj-meta { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.2rem; }
.adj-salary { text-align: right; font-size: 0.82rem; }
.salary-row { display: flex; justify-content: space-between; gap: 1rem; color: #64748b; margin-bottom: 0.2rem; }
.salary-row.net { color: #10b981; font-size: 0.875rem; }
.adj-inputs { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 0.875rem; }
.adj-col { display: flex; flex-direction: column; gap: 0.6rem; }
.adj-col-label { font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; display: flex; align-items: center; gap: 0.35rem; }
.adj-col-label.green { color: #10b981; }
.adj-col-label.red   { color: #ef4444; }
.adj-field { display: flex; flex-direction: column; gap: 0.2rem; }
.adj-field label { font-size: 0.72rem; color: #64748b; font-weight: 500; }
.adj-input { padding: 0.4rem 0.65rem; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.82rem; font-family: 'SFMono-Regular', Consolas, monospace; transition: border-color 0.2s; background: #f8fafc; }
.adj-input:focus { outline: none; border-color: #94a3b8; box-shadow: 0 0 0 3px rgba(148,163,184,0.15); background: white; }
.adj-total { display: flex; justify-content: flex-end; align-items: center; gap: 0.75rem; padding-top: 0.75rem; border-top: 1px dashed #e2e8f0; font-size: 0.82rem; color: #64748b; }
.adj-val { font-weight: 700; font-size: 0.9rem; }
.adj-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.875rem; margin-bottom: 1rem; }
.adj-net-summary { display: flex; justify-content: space-between; align-items: center; padding: 0.875rem 1rem; background: #f8fafc; border-radius: 8px; font-size: 0.875rem; font-weight: 500; color: #475569; border: 1px solid #e2e8f0; margin-top: 1rem; }

/* ── Single Adj Sections ── */
.single-adj-section { margin-bottom: 1.25rem; padding-bottom: 1.25rem; border-bottom: 1px solid #f1f5f9; }
.single-adj-section:last-of-type { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.section-heading { display: flex; align-items: center; gap: 0.4rem; font-size: 0.72rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; color: #475569; margin-bottom: 0.875rem; }

/* ── Overtime UI — no blue/amber tints ──────────── */
.overtime-section {
  margin: 0.875rem 0; padding: 0.875rem;
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
}
.overtime-toggle-row { display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
.ot-label-wrap { display: flex; align-items: center; gap: 0.4rem; font-size: 0.82rem; font-weight: 600; color: #374151; }

/* Toggle switch */
.toggle-switch-wrap { display: flex; align-items: center; gap: 0.6rem; }
.toggle-switch { position: relative; display: inline-block; width: 40px; height: 22px; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-track { position: absolute; cursor: pointer; inset: 0; background: #cbd5e1; border-radius: 22px; transition: background 0.2s; }
.toggle-track:before { display: none; }
.toggle-switch input:checked + .toggle-track { background: #475569; }
.toggle-thumb { position: absolute; height: 16px; width: 16px; left: 3px; bottom: 3px; background: white; border-radius: 50%; transition: transform 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.15); }
.toggle-switch input:checked ~ .toggle-track .toggle-thumb { transform: translateX(18px); }
.toggle-label { font-size: 0.78rem; font-weight: 600; color: #64748b; }

.overtime-detail { margin-top: 1rem; }

/* Mode tabs */
.ot-mode-tabs { display: flex; gap: 0.4rem; margin-bottom: 0.875rem; background: #f1f5f9; border-radius: 8px; padding: 3px; }
.ot-tab { flex: 1; display: flex; align-items: center; justify-content: center; gap: 0.4rem; padding: 0.4rem 0.6rem; font-size: 0.75rem; font-weight: 600; color: #64748b; background: transparent; border: none; border-radius: 6px; cursor: pointer; transition: all 0.15s; font-family: inherit; }
.ot-tab.active { background: white; color: #334155; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.ot-tab:hover:not(.active) { color: #334155; }

/* Auto panel */
.ot-auto-panel { padding: 0.75rem; background: white; border: 1px solid #e2e8f0; border-radius: 8px; }
.ot-loading { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; font-size: 0.82rem; color: #64748b; }
.ot-loading .spinner-sm { border-color: #e2e8f0 !important; border-top-color: #64748b !important; width: 16px; height: 16px; }

.ot-auto-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem; }

/* Neutral OT item backgrounds — no blue/yellow tints */
.ot-auto-item { display: flex; align-items: center; gap: 0.6rem; padding: 0.65rem 0.75rem; border-radius: 8px; }
.ot-auto-item.weekday { background: #f8fafc; border: 1px solid #e2e8f0; }
.ot-auto-item.weekend { background: #f8fafc; border: 1px solid #e2e8f0; }

.ot-auto-icon { width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.ot-auto-item.weekday .ot-auto-icon { background: #f1f5f9; color: #475569; }
.ot-auto-item.weekend .ot-auto-icon { background: #f1f5f9; color: #475569; }

.ot-auto-info { flex: 1; display: flex; flex-direction: column; gap: 0.1rem; }
.ot-auto-type { font-size: 0.72rem; font-weight: 700; color: #334155; }
.ot-auto-rate { font-size: 0.65rem; color: #64748b; }

.ot-auto-values { text-align: right; display: flex; flex-direction: column; gap: 0.1rem; }
.ot-hours { font-size: 0.72rem; font-weight: 700; color: #475569; font-family: 'SFMono-Regular', Consolas, monospace; }
.ot-pay   { font-size: 0.78rem; font-weight: 700; color: #1e293b; font-family: 'SFMono-Regular', Consolas, monospace; }

.ot-auto-total { display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 0.75rem; background: #f8fafc; border-radius: 6px; font-size: 0.82rem; color: #475569; margin-bottom: 0.6rem; }
.ot-auto-total strong { font-size: 0.9rem; color: #1e293b; }

.ot-auto-empty { text-align: center; padding: 1rem; }

.ot-refresh-btn { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.72rem; color: #475569; background: none; border: none; cursor: pointer; font-family: inherit; font-weight: 600; padding: 0; }
.ot-refresh-btn:hover { text-decoration: underline; }

/* Manual panel */
.ot-manual-panel { padding: 0.75rem; background: white; border: 1px solid #e2e8f0; border-radius: 8px; }
.ot-manual-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem; }

/* Neutral manual blocks — no blue/amber tints */
.ot-manual-block { padding: 0.75rem; border-radius: 8px; }
.weekday-block { background: #f8fafc; border: 1px solid #e2e8f0; }
.weekend-block { background: #f8fafc; border: 1px solid #e2e8f0; }

.ot-block-header { display: flex; align-items: center; gap: 0.4rem; margin-bottom: 0.65rem; font-size: 0.72rem; font-weight: 700; color: #334155; }
.ot-rate-badge { margin-left: auto; font-size: 0.65rem; font-weight: 800; padding: 0.1rem 0.35rem; border-radius: 4px; }
/* Rate badges: neutral dark */
.ot-rate-badge.weekday { background: #475569; color: white; }
.ot-rate-badge.weekend { background: #64748b; color: white; }

.ot-manual-field { display: flex; flex-direction: column; gap: 0.2rem; }
.ot-manual-field label { font-size: 0.68rem; color: #64748b; font-weight: 600; }
.ot-hours-input { width: 100%; box-sizing: border-box; }

.ot-calc-preview { font-size: 0.72rem; font-weight: 700; color: #059669; margin-top: 0.35rem; font-family: 'SFMono-Regular', Consolas, monospace; }

.ot-manual-summary { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 0.75rem; font-size: 0.78rem; }
.ot-summary-line  { display: flex; justify-content: space-between; color: #64748b; padding: 0.15rem 0; }
.ot-summary-total { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0; margin-top: 0.5rem; padding-top: 0.5rem; font-weight: 600; color: #1e293b; }
.ot-summary-total strong { font-size: 0.9rem; color: #059669; }

/* ── Transitions ── */
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.25s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.toast-slide-enter-active, .toast-slide-leave-active { transition: all 0.3s ease; }
.toast-slide-enter-from, .toast-slide-leave-to { opacity: 0; transform: translateY(-8px); }

/* ── Responsive ──────────────────────────────────── */
@media (max-width: 1100px) {
  .list-header, .list-row { grid-template-columns: 40px 2fr 1fr 1fr 1fr 0.9fr 0.8fr; }
  .col-biz { display: none; }
}
@media (max-width: 900px) {
  .list-header, .list-row { grid-template-columns: 40px 2fr 1fr 1fr 0.9fr 0.8fr; }
  .col-pos { display: none; }
}
@media (max-width: 768px) {
  .fixed-header { padding: 0.75rem 1rem 0; }
  .dashboard-content { padding: 0 1rem 2rem; }
  
  .user-greeting { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .header-actions { width: 100%; flex-wrap: wrap; }
  .metrics-grid   { grid-template-columns: repeat(2, 1fr); }
  .controls-bar   { flex-direction: column; align-items: flex-start; }
  .modal-stats    { grid-template-columns: repeat(2, 1fr); }
  .adj-inputs     { grid-template-columns: 1fr; gap: 1rem; }
  .adj-form-grid  { grid-template-columns: 1fr; }
  .ot-auto-grid   { grid-template-columns: 1fr; }
  .ot-manual-grid { grid-template-columns: 1fr; }
  .modal-footer   { flex-direction: column; gap: 0.75rem; align-items: stretch; }
  .modal-footer-right { width: 100%; }
  .revert-emp-row { flex-wrap: wrap; }
}
@media (max-width: 480px) {
  .metrics-grid { grid-template-columns: 1fr 1fr; }
  .list-header  { display: none; }
  .list-row     { grid-template-columns: 40px 1fr auto; }
}
</style>