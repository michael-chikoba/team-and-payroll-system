<template>
  <div class="employee-management">

    <!-- ── Header Card ─────────────────────────────── -->
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
            <h1 class="greeting">Team Management</h1>
            <p class="subtitle">Manage your team members and their details</p>
            <div class="role-meta">
              <span class="role-badge">Manager View</span>
            </div>
          </div>
        </div>

        <div class="header-actions">
          <button @click="toggleView" class="btn-outline" :title="gridView ? 'Switch to Table View' : 'Switch to Grid View'">
            <svg v-if="gridView" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h18v18H3zM3 9h18M3 15h18M9 3v18"/></svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            {{ gridView ? 'Table' : 'Grid' }}
          </button>
          <button @click="exportData" class="btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Export
          </button>
        </div>
      </div>
    </div>

    <!-- ── Auth / Permission guards ────────────────── -->
    <div v-if="!authStore.isAuthenticated" class="error-message">
      <strong>Authentication Required</strong> — Please log in to access employee management.
    </div>
    <div v-else-if="!authStore.isManager" class="error-message">
      <strong>Access Denied</strong> — Manager privileges required to view this page.
    </div>

    <!-- ── Loading ──────────────────────────────────── -->
    <div v-else-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Loading team members...</p>
      <div class="progress-track"><div class="progress-bar" :style="{ width: loadingProgress + '%' }"></div></div>
    </div>

    <!-- ── Error ────────────────────────────────────── -->
    <div v-else-if="error" class="error-message">
      <strong>Error</strong> — {{ error }}
      <button @click="retryFetch" class="btn-retry">Retry</button>
    </div>

    <!-- ── Dashboard Content ───────────────────────── -->
    <div v-else class="dashboard-content">

      <!-- 1. Metrics -->
      <div class="metrics-section">
        <h2>Team Overview</h2>
        <div class="metrics-grid">
          <div class="metric-card" style="--accent:#6366f1;">
            <div class="metric-icon-wrap" style="background:rgba(99,102,241,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <div class="metric-value">{{ employees.length }}</div>
            <div class="metric-label">Total Staff</div>
          </div>
          <div class="metric-card" style="--accent:#10b981;">
            <div class="metric-icon-wrap" style="background:rgba(16,185,129,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </div>
            <div class="metric-value">{{ fullTimeCount }}</div>
            <div class="metric-label">Full Time</div>
          </div>
          <div class="metric-card" style="--accent:#f59e0b;">
            <div class="metric-icon-wrap" style="background:rgba(245,158,11,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </div>
            <div class="metric-value">{{ newHiresCount }}</div>
            <div class="metric-label">New Hires (30d)</div>
          </div>
        </div>
      </div>

      <!-- 2. Controls + Table in one card -->
      <div class="table-section">

        <!-- Controls Bar (inside card, above table) -->
        <div class="controls-bar">
          <div class="search-wrap">
            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input v-model="searchQuery" placeholder="Search by name, ID, or department..." class="search-input" />
          </div>
          <div class="filters-row">
            <div class="filter-group">
              <label>Department</label>
              <select v-model="selectedDepartment" class="filter-select">
                <option value="">All Departments</option>
                <option v-for="dept in departments" :key="dept" :value="dept">{{ dept }}</option>
              </select>
            </div>
            <div class="filter-group">
              <label>Type</label>
              <select v-model="selectedType" class="filter-select">
                <option value="">All Types</option>
                <option value="full_time">Full Time</option>
                <option value="part_time">Part Time</option>
                <option value="contract">Contract</option>
              </select>
            </div>
            <button v-if="searchQuery || selectedDepartment || selectedType" @click="clearFilters" class="btn-clear">Clear</button>
          </div>
          <span class="records-count">{{ filteredEmployees.length }} member{{ filteredEmployees.length !== 1 ? 's' : '' }}</span>
        </div>

        <!-- 3a. Grid View -->
        <div v-if="gridView" class="employee-grid">
          <div v-for="employee in paginatedEmployees" :key="employee.id" class="employee-card">
            <div class="card-top">
              <div class="card-avatar">{{ getInitials(employee.full_name || employee.user?.name) }}</div>
              <span class="badge" :class="getBadgeClass(employee.employment_type)">{{ formatEmploymentType(employee.employment_type) }}</span>
            </div>
            <div class="card-body">
              <h4 class="card-name">{{ employee.full_name || employee.user?.name }}</h4>
              <p class="card-position">{{ employee.position }}</p>
              <div class="card-meta">
                <span class="meta-item">
                  <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
                  {{ employee.department || 'N/A' }}
                </span>
                <span class="meta-item">
                  <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="10" r="3"/><path d="M12 2a8 8 0 0 0-8 8c0 5.4 7.05 11.5 7.35 11.76a1 1 0 0 0 1.3 0C13 21.5 20 15.4 20 10a8 8 0 0 0-8-8z"/></svg>
                  {{ employee.type}}
                </span>
              </div>
            </div>
            <div class="card-footer">
              <button @click="viewEmployeeDetails(employee)" class="btn-view-full">View Profile</button>
            </div>
          </div>
          <div v-if="filteredEmployees.length === 0" class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <p>No employees found matching your search.</p>
          </div>
        </div>

        <!-- 3b. Table View -->
        <div v-else>
          <div v-if="filteredEmployees.length === 0" class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <p>No employees found matching your search.</p>
            <button @click="clearFilters" class="btn-secondary">Clear Filters</button>
          </div>

          <div v-else class="table-container">
            <div class="list-header">
              <div class="col-name sortable" @click="sortBy('full_name')">
                Name <span v-if="sortField === 'full_name'" class="sort-icon">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
              </div>
              <div class="col-id">ID</div>
              <div class="col-role sortable" @click="sortBy('position')">
                Role <span v-if="sortField === 'position'" class="sort-icon">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
              </div>
              <div class="col-type">Type</div>
              <div class="col-date">Hired</div>
              <div class="col-actions text-right">Actions</div>
            </div>

            <div v-for="employee in paginatedEmployees" :key="employee.id" class="list-row" @click="viewEmployeeDetails(employee)">
              <div class="col-name">
                <div class="member-avatar">{{ getInitials(employee.full_name || employee.user?.name) }}</div>
                <div class="name-info">
                  <span class="emp-name">{{ employee.full_name || employee.user?.name }}</span>
                  <span class="emp-email">{{ employee.email || employee.user?.email }}</span>
                </div>
              </div>
              <div class="col-id"><span class="id-badge">{{ employee.employee_id }}</span></div>
              <div class="col-role">
                <span class="role-name">{{ employee.position }}</span>
                <span class="role-dept">{{ employee.department }}</span>
              </div>
              <div class="col-type">
                <span class="badge" :class="getBadgeClass(employee.employment_type)">{{ formatEmploymentType(employee.employment_type) }}</span>
              </div>
              <div class="col-date text-muted">{{ formatDateShort(employee.hire_date) }}</div>
              <div class="col-actions" @click.stop>
                <div class="action-group">
                  <button @click="viewEmployeeDetails(employee)" class="action-btn" title="View Details">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Pagination inside card -->
          <div v-if="filteredEmployees.length > itemsPerPage" class="pagination-bar">
            <span class="pagination-info">
              Showing <strong>{{ startIndex + 1 }}–{{ Math.min(endIndex, filteredEmployees.length) }}</strong> of <strong>{{ filteredEmployees.length }}</strong>
            </span>
            <div class="pagination-controls">
              <button @click="prevPage" :disabled="currentPage === 1" class="page-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                Prev
              </button>
              <button v-for="page in visiblePages" :key="page" @click="goToPage(page)" :class="['page-btn', { active: currentPage === page }]">{{ page }}</button>
              <button @click="nextPage" :disabled="currentPage === totalPages" class="page-btn">
                Next
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- ── Employee Details Modal ──────────────────── -->
    <div v-if="selectedEmployee" class="modal-overlay" @click.self="selectedEmployee = null">
      <div class="modal-container">
        <div class="modal-header">
          <div class="modal-title-wrap">
            <div class="modal-avatar">{{ getInitials(selectedEmployee.full_name || selectedEmployee.user?.name) }}</div>
            <div>
              <h3 class="modal-name">{{ selectedEmployee.full_name || selectedEmployee.user?.name }}</h3>
              <p class="modal-position">{{ selectedEmployee.position }}</p>
            </div>
          </div>
          <button @click="selectedEmployee = null" class="modal-close">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          </button>
        </div>
        <div class="modal-body">
          <div class="modal-stats">
            <div class="modal-stat"><small>Employee ID</small><span class="id-badge">{{ selectedEmployee.employee_id }}</span></div>
            <div class="modal-stat"><small>Department</small><strong>{{ selectedEmployee.department }}</strong></div>
            <div class="modal-stat"><small>Type</small><span class="badge" :class="getBadgeClass(selectedEmployee.employment_type)">{{ formatEmploymentType(selectedEmployee.employment_type) }}</span></div>
            <div class="modal-stat"><small>Joined</small><strong>{{ formatDateShort(selectedEmployee.hire_date) }}</strong></div>
          </div>
          <div class="modal-section">
            <h5 class="modal-section-title">Contact Information</h5>
            <div class="detail-grid">
              <div class="detail-item"><label>Email Address</label><span>{{ selectedEmployee.email || selectedEmployee.user?.email || 'N/A' }}</span></div>
              <div class="detail-item"><label>Phone Number</label><span>{{ selectedEmployee.phone || 'N/A' }}</span></div>
              <div class="detail-item full-width"><label>Address</label><span>{{ selectedEmployee.address || 'N/A' }}</span></div>
            </div>
          </div>
          <div class="modal-section">
            <h5 class="modal-section-title">Work Details</h5>
            <div class="detail-grid">
              <div class="detail-item"><label>Reports To</label><span>{{ selectedEmployee.reports_to || 'N/A' }}</span></div>
              <div class="detail-item"><label>Work Location</label><span>{{ selectedEmployee.location || 'N/A' }}</span></div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="selectedEmployee = null" class="btn-secondary">Close</button>
          <button class="btn-primary">Edit Details</button>
        </div>
      </div>
    </div>

  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

export default {
  name: 'EmployeeList',
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  data() {
    return {
      employees: [], loading: false, loadingProgress: 0, error: null, retryCount: 0,
      currentPage: 1, itemsPerPage: 10, selectedEmployee: null,
      searchQuery: '', selectedDepartment: '', selectedType: '',
      sortField: 'full_name', sortDirection: 'asc', gridView: false
    }
  },
  computed: {
    filteredEmployees() {
      let filtered = this.employees
      if (this.searchQuery) {
        const q = this.searchQuery.toLowerCase()
        filtered = filtered.filter(emp => {
          const name = (emp.full_name || emp.user?.name || '').toLowerCase()
          const id = (emp.employee_id || '').toLowerCase()
          const dept = (emp.department || '').toLowerCase()
          const email = (emp.email || emp.user?.email || '').toLowerCase()
          return name.includes(q) || id.includes(q) || dept.includes(q) || email.includes(q)
        })
      }
      if (this.selectedDepartment) filtered = filtered.filter(emp => emp.department === this.selectedDepartment)
      if (this.selectedType) filtered = filtered.filter(emp => emp.employment_type === this.selectedType)
      filtered.sort((a, b) => {
        let aVal = this.sortField === 'full_name' ? (a.full_name || a.user?.name || '') : (a[this.sortField] || '')
        let bVal = this.sortField === 'full_name' ? (b.full_name || b.user?.name || '') : (b[this.sortField] || '')
        if (typeof aVal === 'string') aVal = aVal.toLowerCase()
        if (typeof bVal === 'string') bVal = bVal.toLowerCase()
        if (aVal < bVal) return this.sortDirection === 'asc' ? -1 : 1
        if (aVal > bVal) return this.sortDirection === 'asc' ? 1 : -1
        return 0
      })
      return filtered
    },
    paginatedEmployees() { const s = (this.currentPage - 1) * this.itemsPerPage; return this.filteredEmployees.slice(s, s + this.itemsPerPage) },
    totalPages() { return Math.ceil(this.filteredEmployees.length / this.itemsPerPage) || 1 },
    startIndex() { return (this.currentPage - 1) * this.itemsPerPage },
    endIndex() { return Math.min(this.startIndex + this.itemsPerPage, this.filteredEmployees.length) },
    visiblePages() {
      const pages = [], maxVisible = 5
      let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2))
      let end = Math.min(this.totalPages, start + maxVisible - 1)
      if (end - start + 1 < maxVisible) start = Math.max(1, end - maxVisible + 1)
      for (let i = start; i <= end; i++) pages.push(i)
      return pages
    },
    departments() { return Array.from(new Set(this.employees.map(e => e.department).filter(Boolean))).sort() },
    fullTimeCount() { return this.employees.filter(e => e.employment_type === 'full_time').length },
    newHiresCount() { const cutoff = new Date(); cutoff.setDate(cutoff.getDate() - 30); return this.employees.filter(e => e.hire_date && new Date(e.hire_date) >= cutoff).length }
  },
  watch: {
    searchQuery() { this.currentPage = 1 },
    selectedDepartment() { this.currentPage = 1 },
    selectedType() { this.currentPage = 1 }
  },
  mounted() { this.initializeComponent() },
  methods: {
    initializeComponent() {
      if (!this.authStore.isAuthenticated) { this.error = 'Please log in.'; return }
      if (!this.authStore.isManager) { this.error = 'Manager privileges required.'; return }
      this.fetchEmployees()
    },
    async fetchEmployees() {
      this.loading = true; this.loadingProgress = 0; this.error = null
      const interval = setInterval(() => { if (this.loadingProgress < 90) this.loadingProgress += 10 }, 100)
      try {
        const response = await axios.get('/api/manager/employees', { params: { manager_id: this.authStore.user?.id } })
        this.employees = response.data.data || response.data || []
        this.loadingProgress = 100
      } catch (err) { this.handleApiError(err) } finally { clearInterval(interval); setTimeout(() => this.loading = false, 300) }
    },
    retryFetch() { this.retryCount++; if (this.retryCount <= 3) this.fetchEmployees(); else this.error = 'Max retries exceeded.' },
    handleApiError(err) {
      if (err.code === 'ERR_NETWORK') this.error = 'Network error.'
      else if (err.response?.status === 401) { this.authStore.clearAuth(); this.$router.push({ name: 'login' }) }
      else this.error = err.response?.data?.message || 'An unexpected error occurred.'
    },
    viewEmployeeDetails(employee) { this.selectedEmployee = employee },
    nextPage() { if (this.currentPage < this.totalPages) this.currentPage++ },
    prevPage() { if (this.currentPage > 1) this.currentPage-- },
    goToPage(page) { this.currentPage = page },
    sortBy(field) {
      if (this.sortField === field) this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc'
      else { this.sortField = field; this.sortDirection = 'asc' }
    },
    toggleView() { this.gridView = !this.gridView },
    clearFilters() { this.searchQuery = ''; this.selectedDepartment = ''; this.selectedType = '' },
    exportData() { alert('Export feature coming soon!') },
    getInitials(name) { if (!name) return '??'; return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) },
    getBadgeClass(type) { return { full_time: 'badge-success', part_time: 'badge-info', contract: 'badge-warning', intern: 'badge-secondary' }[type] || 'badge-default' },
    formatDateShort(date) { if (!date) return '—'; return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) },
    formatEmploymentType(type) { return { full_time: 'Full Time', part_time: 'Part Time', contract: 'Contract', intern: 'Intern' }[type] || type || 'Unknown' }
  }
}
</script>

<style scoped>
/* ── Base ──────────────────────────────────────────── */
.employee-management {
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
  background: white; border-radius: 16px; padding: 1.5rem 1.75rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0; margin-bottom: 1.25rem; position: relative; overflow: hidden;
}

.header-card-accent {
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  
}

.user-greeting { display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; }
.avatar-section { display: flex; align-items: center; gap: 1rem; }

.avatar {
  width: 52px; height: 52px; background: linear-gradient(135deg, #3b82f6, #6366f1);
  border-radius: 14px; display: flex; align-items: center; justify-content: center;
  color: white; box-shadow: 0 4px 12px rgba(59,130,246,0.25); flex-shrink: 0;
}

.user-info { display: flex; flex-direction: column; gap: 0.2rem; }
.greeting { margin: 0; font-size: 1.375rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
.subtitle { margin: 0; color: #64748b; font-size: 0.875rem; }
.role-meta { margin-top: 0.125rem; }

.role-badge {
  background: #eff6ff; border: 1px solid #bfdbfe;
  padding: 0.125rem 0.6rem; border-radius: 8px; font-size: 0.7rem; font-weight: 600; color: #1d4ed8;
}

.header-actions { display: flex; gap: 0.5rem; flex-shrink: 0; }

/* ── Buttons ─────────────────────────────────────── */
.btn-primary {
  background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border: none;
  padding: 0.5rem 1.25rem; border-radius: 8px; font-size: 0.875rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s;
}
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,0.35); }

.btn-outline {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.45rem 0.9rem; background: white; border: 1px solid #e2e8f0;
  color: #475569; border-radius: 8px; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.btn-outline:hover { background: #f8fafc; border-color: #6366f1; color: #6366f1; }

.btn-secondary {
  padding: 0.5rem 1rem; background: #f1f5f9; color: #475569;
  border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.btn-secondary:hover { background: #e2e8f0; }

.btn-retry {
  display: inline-block; margin-left: 0.75rem; padding: 0.35rem 0.875rem;
  background: #991b1b; color: white; border: none; border-radius: 8px; font-size: 0.82rem; font-weight: 600; cursor: pointer;
}

.btn-clear {
  padding: 0.45rem 0.875rem; background: #fef3c7; color: #92400e;
  border: 1px solid #fde68a; border-radius: 8px; font-size: 0.78rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.btn-clear:hover { background: #fde68a; }

.btn-view-full {
  width: 100%; padding: 0.5rem; background: transparent; color: #6366f1;
  border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.btn-view-full:hover { background: #eff6ff; border-color: #6366f1; }

/* ── States ──────────────────────────────────────── */
.loading { text-align: center; padding: 4rem 1rem; color: #64748b; }
.spinner {
  width: 40px; height: 40px; border: 3px solid #e2e8f0; border-top-color: #6366f1;
  border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem;
}
@keyframes spin { to { transform: rotate(360deg); } }

.progress-track { width: 200px; height: 4px; background: #e2e8f0; border-radius: 9999px; overflow: hidden; margin: 1rem auto 0; }
.progress-bar { height: 100%; background: linear-gradient(90deg, #6366f1, #8b5cf6); border-radius: 9999px; transition: width 0.3s; }

.error-message { background: #fee2e2; color: #991b1b; padding: 1.25rem 1.5rem; border-radius: 10px; margin: 1rem 0; font-size: 0.9rem; }

.empty-state { text-align: center; padding: 3rem 2rem; color: #94a3b8; display: flex; flex-direction: column; align-items: center; gap: 0.75rem; }
.empty-state p { margin: 0; font-size: 0.875rem; color: #64748b; }

/* ── Dashboard Content ───────────────────────────── */
.dashboard-content { display: flex; flex-direction: column; gap: 1.5rem; }

/* ── Metrics Section ─────────────────────────────── */
.metrics-section {
  background: white; border-radius: 16px;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0; padding: 1.5rem;
}

h2 { font-size: 1.1rem; font-weight: 600; margin: 0 0 1.25rem 0; color: #334155; }

.metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; }

.metric-card {
  padding: 1.25rem; background: #f8fafc; border-radius: 12px;
  display: flex; flex-direction: column; align-items: center; text-align: center;
  border: 1px solid #e2e8f0; transition: transform 0.2s, box-shadow 0.2s;
}
.metric-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px -4px rgba(0,0,0,0.08); border-color: var(--accent); }
.metric-card::before { display: none; }

.metric-icon-wrap { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem; }
.metric-value { font-size: 1.8rem; font-weight: 800; color: #0f172a; line-height: 1.1; margin-bottom: 0.25rem; }
.metric-label { font-size: 0.78rem; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.04em; }

/* ── Table Section (unified card) ───────────────── */
.table-section {
  background: white; border-radius: 16px;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0; padding: 1.5rem;
}

/* Controls Bar */
.controls-bar {
  display: flex; align-items: center; gap: 0.875rem;
  margin-bottom: 1.25rem; flex-wrap: wrap;
}

.search-wrap {
  display: flex; align-items: center; gap: 0.75rem; flex: 1; min-width: 220px;
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 0.55rem 1rem; transition: all 0.2s;
}
.search-wrap:focus-within { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); background: white; }
.search-icon { color: #94a3b8; flex-shrink: 0; }
.search-input { flex: 1; border: none; background: transparent; outline: none; font-size: 0.875rem; color: #1e293b; font-family: inherit; }
.search-input::placeholder { color: #94a3b8; }

.filters-row { display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap; }
.filter-group { display: flex; flex-direction: column; gap: 0.3rem; }
.filter-group label { font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }

.filter-select {
  padding: 0.45rem 2rem 0.45rem 0.875rem; border: 1px solid #e2e8f0; border-radius: 8px;
  background: #f8fafc; color: #334155; font-size: 0.875rem; font-weight: 500; cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 0.75rem center; transition: all 0.2s;
}
.filter-select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

.records-count {
  font-size: 0.75rem; font-weight: 700; color: #6366f1;
  background: #eff0fe; padding: 0.2rem 0.7rem; border-radius: 9999px;
  border: 1px solid #c7d2fe; white-space: nowrap; margin-left: auto;
}

/* ── Badges ──────────────────────────────────────── */
.badge { padding: 0.25rem 0.65rem; border-radius: 9999px; font-size: 0.72rem; font-weight: 700; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-info    { background: #dbeafe; color: #1e40af; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-secondary { background: #f1f5f9; color: #475569; }
.badge-default { background: #f1f5f9; color: #64748b; }

/* ── Table ───────────────────────────────────────── */
.table-container { border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0; }

.list-header,
.list-row {
  display: grid;
  grid-template-columns: 2.5fr 0.8fr 1.5fr 1fr 1fr 0.5fr;
  padding: 0.75rem 1rem; align-items: center; gap: 1rem;
}

.list-header {
  background: #f8fafc; border-bottom: 1px solid #e2e8f0;
  font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;
}

.list-header .sortable { cursor: pointer; user-select: none; transition: color 0.15s; }
.list-header .sortable:hover { color: #334155; }
.sort-icon { margin-left: 0.25rem; color: #6366f1; }

.list-row {
  border-bottom: 1px solid #f1f5f9;
  font-size: 0.875rem; background: white; cursor: pointer; transition: background 0.15s;
}
.list-row:last-child { border-bottom: none; }
.list-row:hover { background: #f8fafc; }

.col-name { display: flex; align-items: center; gap: 0.75rem; }

.member-avatar {
  width: 34px; height: 34px; background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;
  font-size: 0.72rem; font-weight: 700; flex-shrink: 0;
}

.name-info { display: flex; flex-direction: column; min-width: 0; }
.emp-name { font-weight: 600; color: #1e293b; font-size: 0.875rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.emp-email { font-size: 0.72rem; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.id-badge {
  font-family: 'SFMono-Regular', Consolas, monospace; font-size: 0.72rem; font-weight: 700;
  background: #f1f5f9; color: #475569; padding: 0.2rem 0.5rem; border-radius: 6px; border: 1px solid #e2e8f0;
}

.role-name { font-weight: 600; color: #1e293b; font-size: 0.875rem; display: block; }
.role-dept { font-size: 0.72rem; color: #64748b; }

.text-right { text-align: right; }
.text-muted { color: #64748b; font-size: 0.82rem; }

.action-group { display: flex; justify-content: flex-end; }

.action-btn {
  width: 30px; height: 30px; border-radius: 6px;
  border: 1px solid #e2e8f0; background: white; color: #64748b;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.15s;
}
.action-btn:hover { background: #eff6ff; color: #4f46e5; border-color: #a5b4fc; }

/* ── Grid View ───────────────────────────────────── */
.employee-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem;
}

.employee-card {
  border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: white;
  transition: all 0.2s; display: flex; flex-direction: column;
}
.employee-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(99,102,241,0.1); border-color: #a5b4fc; }

.card-top {
  
  padding: 1.25rem; display: flex; justify-content: space-between; align-items: flex-start;
}

.card-avatar {
  width: 48px; height: 48px; background: rgba(255,255,255,0.15); border-radius: 12px; color: white;
  display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: 1rem; border: 1.5px solid rgba(255,255,255,0.25);
}

.card-body { padding: 1.125rem; flex: 1; }
.card-name { font-weight: 700; color: #1e293b; font-size: 0.95rem; margin: 0 0 0.2rem; }
.card-position { font-size: 0.78rem; color: #64748b; margin: 0 0 0.875rem; }
.card-meta { display: flex; flex-direction: column; gap: 0.4rem; }
.meta-item { display: flex; align-items: center; gap: 0.4rem; font-size: 0.78rem; color: #64748b; font-weight: 500; }

/* Card footer — no border */
.card-footer { padding: 0.875rem 1.125rem; border-top: none; background: transparent; }

/* ── Pagination ──────────────────────────────────── */
.pagination-bar {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1rem 0 0; border-top: 1px solid #f1f5f9; margin-top: 0.5rem;
}

.pagination-info { font-size: 0.82rem; color: #64748b; }
.pagination-info strong { color: #1e293b; font-weight: 700; }
.pagination-controls { display: flex; gap: 0.35rem; }

.page-btn {
  display: flex; align-items: center; gap: 0.3rem;
  padding: 0.35rem 0.75rem; background: white; border: 1px solid #e2e8f0;
  border-radius: 6px; font-size: 0.78rem; font-weight: 600; color: #475569; cursor: pointer; transition: all 0.15s;
}
.page-btn:hover:not(:disabled) { border-color: #a5b4fc; color: #4f46e5; background: #eff6ff; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-btn.active { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border-color: transparent; box-shadow: 0 2px 8px rgba(99,102,241,0.3); }

/* ── Modal ───────────────────────────────────────── */
.modal-overlay {
  position: fixed; top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(15,23,42,0.55); backdrop-filter: blur(4px);
  display: flex; align-items: center; justify-content: center;
  z-index: 2050; animation: fadeIn 0.2s ease-out;
}
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

.modal-container {
  background: white; width: 680px; max-width: 95%; border-radius: 20px;
  box-shadow: 0 32px 64px rgba(15,23,42,0.25);
  display: flex; flex-direction: column; max-height: 88vh; overflow: hidden;
  border: 1px solid #e2e8f0; animation: slideUp 0.25s ease-out;
}
@keyframes slideUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }

.modal-header {
  padding: 1.5rem 1.75rem;
  background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
  display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;
}

.modal-title-wrap { display: flex; align-items: center; gap: 1rem; }

.modal-avatar {
  width: 52px; height: 52px; background: rgba(255,255,255,0.15); border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  font-weight: 800; font-size: 1.1rem; color: white; border: 1.5px solid rgba(255,255,255,0.25);
}

.modal-name { font-size: 1.2rem; font-weight: 700; color: white; margin: 0; }
.modal-position { font-size: 0.82rem; color: rgba(255,255,255,0.65); margin: 0.2rem 0 0; }

.modal-close {
  width: 36px; height: 36px; border-radius: 50%;
  border: 1.5px solid rgba(255,255,255,0.25); background: rgba(255,255,255,0.1);
  color: white; display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.2s;
}
.modal-close:hover { background: rgba(239,68,68,0.6); border-color: transparent; }

.modal-body { padding: 1.5rem 1.75rem; overflow-y: auto; flex: 1; }

.modal-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.875rem; margin-bottom: 1.5rem; }

.modal-stat {
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 0.875rem;
  text-align: center; display: flex; flex-direction: column; gap: 0.35rem; align-items: center;
}
.modal-stat small { font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.modal-stat strong { font-size: 0.9rem; font-weight: 700; color: #1e293b; }

.modal-section { margin-bottom: 1.25rem; }
.modal-section:last-child { margin-bottom: 0; }
.modal-section-title {
  font-size: 0.7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.07em;
  margin: 0 0 0.875rem; padding-bottom: 0.5rem; border-bottom: 1px solid #f1f5f9;
}

.detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.875rem; }
.detail-item.full-width { grid-column: 1 / -1; }
.detail-item { display: flex; flex-direction: column; gap: 0.25rem; }
.detail-item label { font-size: 0.7rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.detail-item span { font-size: 0.875rem; color: #334155; font-weight: 500; }

.modal-footer {
  padding: 1.125rem 1.75rem; border-top: 1px solid #f1f5f9;
  background: #f8fafc; display: flex; justify-content: flex-end; gap: 0.75rem; flex-shrink: 0;
}

/* ── Responsive ──────────────────────────────────── */
@media (max-width: 900px) {
  .list-header { display: none; }
  .list-row { grid-template-columns: 1fr auto; grid-template-areas: "name badge" "role action"; gap: 0.5rem; padding: 0.875rem 1rem; }
  .col-name { grid-area: name; }
  .col-type { grid-area: badge; justify-self: end; }
  .col-role { grid-area: role; }
  .col-actions { grid-area: action; justify-self: end; }
  .col-id, .col-date { display: none; }
  .modal-stats { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
  .employee-management { padding: 1rem; }
  .user-greeting { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .header-actions { width: 100%; }
  .metrics-grid { grid-template-columns: repeat(3, 1fr); }
  .controls-bar { flex-direction: column; align-items: stretch; }
  .search-wrap { min-width: unset; }
  .records-count { margin-left: 0; }
  .filters-row { flex-wrap: wrap; }
  .filter-select { width: 100%; }
  .employee-grid { grid-template-columns: 1fr; }
  .modal-container { width: 100%; max-width: 100%; border-radius: 20px 20px 0 0; max-height: 92vh; position: fixed; bottom: 0; }
  .detail-grid { grid-template-columns: 1fr; }
}

@media (max-width: 480px) {
  .metrics-grid { grid-template-columns: 1fr; }
  .modal-stats { grid-template-columns: repeat(2, 1fr); }
}
</style>