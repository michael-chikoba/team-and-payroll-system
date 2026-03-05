<template>
  <div class="emp-mgmt-page">

    <!-- ── Header Card ─────────────────────────────── -->
    <div class="dashboard-header-card">
      <div class="header-card-accent"></div>
      <div class="user-greeting">
        <div class="avatar-section">
          <div class="avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
          </div>
          <div class="user-info">
            <h1 class="greeting">Employee Management</h1>
            <p class="subtitle">Manage employees, roles, and organisational structure</p>
            <div class="role-meta">
              <span class="role-badge">{{ authStore.isAdmin ? 'Admin View' : 'Manager View' }}</span>
            </div>
          </div>
        </div>
        <div class="header-actions">
          <button v-if="authStore.isAdmin" @click="showAddModal = true" class="btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Add Employee
          </button>
        </div>
      </div>
    </div>

    <!-- ── Auth / Permission Guards ──────────────── -->
    <div v-if="!authStore.isAuthenticated" class="alert-card error">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
      Please log in to access employee management.
    </div>
    <div v-else-if="!authStore.isAdmin && !authStore.isManager" class="alert-card error">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
      You don't have permission to access this page.
    </div>

    <!-- ── Loading State ──────────────────────────── -->
    <div v-else-if="loading" class="section-card">
      <div class="board-loading">
        <div class="spinner"></div>
        <p>Loading employees...</p>
      </div>
    </div>

    <!-- ── Error State ────────────────────────────── -->
    <div v-else-if="error" class="alert-card error">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
      {{ error }}
      <button @click="retryFetch" class="btn-inline">Retry</button>
    </div>

    <!-- ── Main Content ───────────────────────────── -->
    <div v-else class="dashboard-content">

      <!-- Business Filter (admin multi-business) -->
      <div class="section-card filter-bar" v-if="authStore.isAdmin && businesses.length > 1">
        <div class="filter-group">
          <label>Business</label>
          <select v-model="selectedBusinessId" @change="onBusinessFilterChange" class="filter-select">
            <option value="">All Businesses</option>
            <option v-for="b in businesses" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
        </div>
        <span class="business-active-badge" v-if="selectedBusinessId">
          {{ getBusinessName(selectedBusinessId) }}
        </span>
      </div>

      <!-- Controls Bar -->
      <div class="section-card">
        <div class="controls-bar">
          <div class="search-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="search-icon"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input v-model="searchQuery" type="text" @input="onSearch" class="filter-input" placeholder="Search employees..." />
          </div>
          <div class="controls-right">
            <div class="filter-group">
              <label>Per page</label>
              <select v-model="itemsPerPage" @change="onItemsPerPageChange" class="filter-select compact">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
            </div>
            <span class="records-count">
              {{ showingStart }}–{{ showingEnd }} of {{ totalItems }} employees
            </span>
          </div>
        </div>

        <!-- Table -->
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>ID</th>
                <th>Email</th>
                <th>Position</th>
                <th>Department</th>
                <th>Country</th>
                <th v-if="authStore.isAdmin && businesses.length > 1">Business</th>
                <th>Salary</th>
                <th>Transport</th>
                <th>Lunch</th>
                <th>Hire Date</th>
                <th>Manager</th>
                <th>Type</th>
                <th>Role</th>
                <th v-if="authStore.isAdmin">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="employee in paginatedEmployees" :key="employee.id">
                <td>
                  <div class="emp-name-cell">
                    <div class="emp-avatar">{{ getInitials(getEmployeeName(employee)) }}</div>
                    <span>{{ getEmployeeName(employee) }}</span>
                  </div>
                </td>
                <td class="mono">{{ employee.employee_id || employee.id }}</td>
                <td class="muted">{{ employee.email || employee.user?.email || '—' }}</td>
                <td>{{ employee.position || '—' }}</td>
                <td>{{ employee.department || '—' }}</td>
                <td>
                  <div class="country-cell" v-if="getCountry(employee)">
                    <div class="flag-wrap">
                      <img :src="getCountry(employee).flag" class="flag-img" @error="e => e.target.style.display='none'" />
                      <span class="flag-fallback">{{ getCountry(employee).code }}</span>
                    </div>
                    <span>{{ getCountry(employee).name }}</span>
                  </div>
                  <span v-else class="muted">—</span>
                </td>
                <td v-if="authStore.isAdmin && businesses.length > 1">
                  <span class="chip business" v-if="employee.business_id">{{ getBusinessName(employee.business_id) }}</span>
                  <span class="muted" v-else>—</span>
                </td>
                <td class="mono">K{{ formatNumber(employee.base_salary) }}</td>
                <td class="mono">K{{ formatNumber(employee.transport_allowance || 0) }}</td>
                <td class="mono">K{{ formatNumber(employee.lunch_allowance || 0) }}</td>
                <td class="muted">{{ formatDate(employee.hire_date || employee.created_at) }}</td>
                <td class="muted">{{ getManagerName(employee.manager_id) }}</td>
                <td>
                  <span class="chip type">{{ formatEmploymentType(employee.employment_type) }}</span>
                </td>
                <td>
                  <span class="chip" :class="'role-' + getEmployeeRole(employee)">
                    {{ formatRole(getEmployeeRole(employee)) }}
                  </span>
                </td>
                <td v-if="authStore.isAdmin">
                  <div class="row-actions">
                    <button @click="editEmployee(employee)" class="action-btn" title="Edit">
                      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button @click="deleteEmployee(employee)" class="action-btn danger" title="Delete">
                      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-if="filteredEmployees.length === 0 && !loading" class="empty-state">
          <div class="empty-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
          </div>
          <h3>No employees found</h3>
          <p v-if="searchQuery">No results matching "{{ searchQuery }}"</p>
          <p v-else-if="selectedBusinessId">No employees for {{ getBusinessName(selectedBusinessId) }}</p>
          <p v-else>Get started by adding your first employee</p>
          <button v-if="authStore.isAdmin" @click="showAddModal = true" class="btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Add First Employee
          </button>
        </div>

        <!-- Pagination -->
        <div v-if="filteredEmployees.length > 0" class="pagination-bar">
          <span class="pagination-info">
            Showing <strong>{{ showingStart }}</strong>–<strong>{{ showingEnd }}</strong> of <strong>{{ totalItems }}</strong>
          </span>
          <div class="pagination-controls">
            <button @click="prevPage" :disabled="currentPage === 1" class="page-btn">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
              Prev
            </button>
            <template v-for="page in visiblePages" :key="page">
              <span v-if="page === '...'" class="page-ellipsis">…</span>
              <button v-else @click="goToPage(page)" :class="['page-btn', { active: page === currentPage }]">{{ page }}</button>
            </template>
            <button @click="nextPage" :disabled="currentPage === totalPages" class="page-btn">
              Next
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Add / Edit Modal ───────────────────────── -->
    <div v-if="(showAddModal || showEditModal) && authStore.isAdmin" class="modal-overlay" @click.self="closeModals">
      <div class="modal">
        <div class="modal-header">
          <div class="modal-title-row">
            <div class="modal-avatar">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
            </div>
            <div>
              <h2>{{ showEditModal ? 'Edit Employee' : 'Add New Employee' }}</h2>
              <p class="modal-subtitle">{{ showEditModal ? 'Update employee details below' : 'Fill in the details to create a new employee' }}</p>
            </div>
          </div>
          <button @click="closeModals" class="close-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </div>

        <form @submit.prevent="submitForm" class="modal-body">
          <!-- Business -->
          <div class="form-group full-width" v-if="authStore.isAdmin && businesses.length > 1">
            <label>Business <span class="req">*</span></label>
            <select v-model="form.business_id" required class="form-control">
              <option value="">Select Business</option>
              <option v-for="b in businesses" :key="b.id" :value="b.id">{{ b.name }} ({{ b.industry || 'N/A' }})</option>
            </select>
            <small v-if="businesses.length === 0" class="hint warn">No businesses available. Please create a business first.</small>
          </div>
          <div v-else-if="authStore.isAdmin && businesses.length === 1" class="form-group full-width">
            <label>Business</label>
            <input :value="businesses[0].name" type="text" disabled class="form-control disabled-input" />
            <input type="hidden" v-model="form.business_id" :value="businesses[0].id" />
          </div>

          <!-- Name -->
          <div class="form-row">
            <div class="form-group">
              <label>First Name <span class="req">*</span></label>
              <input v-model="form.first_name" type="text" required placeholder="First name" class="form-control" />
            </div>
            <div class="form-group">
              <label>Last Name <span class="req">*</span></label>
              <input v-model="form.last_name" type="text" required placeholder="Last name" class="form-control" />
            </div>
          </div>

          <!-- Email (add only) -->
          <div class="form-group full-width" v-if="!showEditModal">
            <label>Email <span class="req">*</span></label>
            <input v-model="form.email" type="email" required placeholder="employee@example.com" class="form-control" />
          </div>

          <!-- Country -->
          <div class="form-group full-width">
            <label>Country <span class="req">*</span></label>
            <div class="country-select-wrapper">
              <div class="country-select-header" @click="toggleCountryDropdown">
                <div class="selected-country" v-if="selectedCountry">
                  <div class="flag-wrap sm">
                    <img :src="selectedCountry.flag" class="flag-img" @error="e => e.target.style.display='none'" />
                    <span class="flag-fallback">{{ selectedCountry.code }}</span>
                  </div>
                  <span class="country-name-val">{{ selectedCountry.name }} <span class="country-code-val">({{ selectedCountry.code }})</span></span>
                </div>
                <span class="placeholder-text" v-else>Select a country...</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :class="['chevron', { open: showCountryDropdown }]"><polyline points="6 9 12 15 18 9"></polyline></svg>
              </div>
              <div v-if="showCountryDropdown" class="country-dropdown">
                <div class="country-search-wrap">
                  <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="search-icon"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                  <input type="text" v-model="countrySearch" placeholder="Search countries..." @click.stop ref="countrySearchInput" class="country-search-input" />
                </div>
                <div class="country-list">
                  <div
                    v-for="country in filteredCountries" :key="country.id"
                    class="country-option"
                    :class="{ selected: form.country_id === country.id, inactive: !country.is_active && form.country_id !== country.id }"
                    @click="selectCountry(country)"
                  >
                    <div class="flag-wrap sm">
                      <img :src="country.flag" class="flag-img" @error="e => e.target.style.display='none'" />
                      <span class="flag-fallback">{{ country.code }}</span>
                    </div>
                    <span class="country-name-val">{{ country.name }} <span class="country-code-val">({{ country.code }})</span></span>
                    <span v-if="!country.is_active" class="chip warn-chip">Inactive</span>
                    <svg v-if="form.country_id === country.id" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="3" class="check-icon"><polyline points="20 6 9 17 4 12"></polyline></svg>
                  </div>
                  <div v-if="filteredCountries.length === 0" class="country-empty">No countries found for "{{ countrySearch }}"</div>
                </div>
              </div>
            </div>
            <input type="hidden" v-model="form.country_id" required />
            <small v-if="selectedCountry && !selectedCountry.is_active" class="hint warn">This country is currently inactive.</small>
          </div>

          <!-- Role -->
          <div class="form-group full-width">
            <label>Role <span class="req">*</span></label>
            <select v-model="form.role" required @change="onRoleChange" class="form-control">
              <option value="employee">Employee</option>
              <option value="manager">Manager</option>
              <option value="admin">Admin</option>
            </select>
          </div>

          <!-- Position + Department -->
          <div class="form-row">
            <div class="form-group">
              <label>Position <span class="req">*</span></label>
              <input v-model="form.position" type="text" required placeholder="e.g., Software Developer" class="form-control" />
            </div>
            <div class="form-group">
              <label>Department <span class="req">*</span></label>
              <select v-model="form.department" required :disabled="loadingDepartments || !form.business_id" class="form-control">
                <option value="">{{ loadingDepartments ? 'Loading...' : 'Select Department' }}</option>
                <option v-for="(dept, i) in availableDepartments" :key="i" :value="dept.name">{{ dept.name }}</option>
              </select>
              <small v-if="!form.business_id" class="hint">Select a business first.</small>
              <small v-else-if="!loadingDepartments && availableDepartments.length === 0" class="hint warn">No departments configured for this business.</small>
            </div>
          </div>

          <!-- Salary + Type -->
          <div class="form-row">
            <div class="form-group">
              <label>Base Salary (K) <span class="req">*</span></label>
              <input v-model.number="form.base_salary" type="number" step="0.01" required placeholder="0.00" class="form-control" />
            </div>
            <div class="form-group">
              <label>Employment Type <span class="req">*</span></label>
              <select v-model="form.employment_type" required class="form-control">
                <option value="">Select Type</option>
                <option value="full_time">Full Time</option>
                <option value="part_time">Part Time</option>
                <option value="contract">Contract</option>
              </select>
            </div>
          </div>

          <!-- Allowances -->
          <div class="form-row">
            <div class="form-group">
              <label>Transport Allowance (K)</label>
              <input v-model.number="form.transport_allowance" type="number" step="0.01" placeholder="0.00" class="form-control" />
            </div>
            <div class="form-group">
              <label>Lunch Allowance (K)</label>
              <input v-model.number="form.lunch_allowance" type="number" step="0.01" placeholder="0.00" class="form-control" />
            </div>
          </div>

          <!-- Hire Date + Manager -->
          <div class="form-row">
            <div class="form-group">
              <label>Hire Date <span class="req">*</span></label>
              <input v-model="form.hire_date" type="date" required class="form-control" />
            </div>
            <div class="form-group">
              <label>Manager <span v-if="form.role === 'employee'" class="req">*</span></label>
              <select v-model="form.manager_id" :required="form.role === 'employee'" :disabled="form.role !== 'employee'" class="form-control">
                <option value="">No Manager</option>
                <option v-for="mgr in managers" :key="mgr.id" :value="mgr.id">{{ mgr.first_name }} {{ mgr.last_name }} ({{ mgr.department }})</option>
              </select>
              <small v-if="form.role === 'employee' && managers.length === 0" class="hint warn">No managers available. Create a manager first.</small>
              <small v-if="form.role !== 'employee'" class="hint">{{ form.role === 'admin' ? 'Admins' : 'Managers' }} don't have managers.</small>
            </div>
          </div>

          <div v-if="formError" class="form-error-bar">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            {{ formError }}
          </div>
        </form>

        <div class="modal-footer">
          <button type="button" @click="closeModals" class="btn-secondary">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="submitting" @click="submitForm">
            {{ submitting ? 'Saving...' : (showEditModal ? 'Update Employee' : 'Add Employee') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

export default {
  name: 'EmployeeManagement',
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  data() {
    return {
      employees: [],
      managers: [],
      countries: [],
      businesses: [],
      availableDepartments: [],
      loadingDepartments: false,
      loading: false,
      error: null,
      showAddModal: false,
      showEditModal: false,
      submitting: false,
      formError: null,
      currentEmployee: null,
      selectedBusinessId: '',
      showCountryDropdown: false,
      countrySearch: '',
      currentPage: 1,
      itemsPerPage: 10,
      searchQuery: '',
      form: {
        first_name: '', last_name: '', email: '', business_id: '', country_id: '',
        role: 'employee', position: '', department: '', base_salary: '',
        transport_allowance: '', lunch_allowance: '', employment_type: '',
        hire_date: '', manager_id: ''
      },
      retryCount: 0
    }
  },
  computed: {
    activeCountries() { return this.countries.filter(c => c.is_active) },
    countriesSorted() { return [...this.countries].sort((a, b) => a.name.localeCompare(b.name)) },
    filteredCountries() {
      if (!this.countrySearch.trim()) return this.countriesSorted
      const s = this.countrySearch.toLowerCase()
      return this.countriesSorted.filter(c => c.name.toLowerCase().includes(s) || c.code.toLowerCase().includes(s))
    },
    selectedCountry() {
      if (!this.form.country_id) return null
      return this.countries.find(c => c.id === this.form.country_id)
    },
    filteredEmployees() {
      if (!this.searchQuery.trim()) return this.employees
      const s = this.searchQuery.toLowerCase()
      return this.employees.filter(e => {
        return this.getEmployeeName(e).toLowerCase().includes(s) ||
          (e.email || e.user?.email || '').toLowerCase().includes(s) ||
          (e.position || '').toLowerCase().includes(s) ||
          (e.department || '').toLowerCase().includes(s) ||
          (e.employee_id || e.id || '').toString().toLowerCase().includes(s)
      })
    },
    totalItems() { return this.filteredEmployees.length },
    totalPages() { return Math.ceil(this.totalItems / this.itemsPerPage) },
    showingStart() { return this.totalItems === 0 ? 0 : (this.currentPage - 1) * this.itemsPerPage + 1 },
    showingEnd() { return Math.min(this.currentPage * this.itemsPerPage, this.totalItems) },
    paginatedEmployees() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      return this.filteredEmployees.slice(start, start + this.itemsPerPage)
    },
    visiblePages() {
      const pages = []
      const total = this.totalPages
      const cur = this.currentPage
      if (total <= 5) { for (let i = 1; i <= total; i++) pages.push(i); return pages }
      pages.push(1)
      if (cur <= 3) {
        for (let i = 2; i <= Math.min(4, total - 1); i++) pages.push(i)
        if (total > 4) pages.push('...')
      } else if (cur >= total - 2) {
        pages.push('...')
        for (let i = Math.max(2, total - 3); i <= total - 1; i++) pages.push(i)
      } else {
        pages.push('...'); pages.push(cur - 1); pages.push(cur); pages.push(cur + 1); pages.push('...')
      }
      pages.push(total)
      return pages
    }
  },
  watch: {
    'form.business_id'(newVal) {
      if (newVal) this.fetchSettingsForBusiness(newVal)
      else this.availableDepartments = []
    },
    showAddModal(newVal) {
      if (newVal && this.authStore.isAdmin) {
        this.$nextTick(() => this.initializeFormForAdd())
      }
    }
  },
  mounted() {
    this.initializeComponent()
    document.addEventListener('click', this.closeCountryDropdown)
  },
  beforeUnmount() {
    document.removeEventListener('click', this.closeCountryDropdown)
  },
  methods: {
    async initializeComponent() {
      if (!this.authStore.isAuthenticated || (!this.authStore.isAdmin && !this.authStore.isManager)) return
      try {
        if (this.authStore.isAdmin) await this.fetchBusinesses()
        if (this.authStore.isAdmin && this.businesses.length === 1) {
          this.selectedBusinessId = this.businesses[0].id
        } else if (this.authStore.user?.current_business_id) {
          this.selectedBusinessId = this.authStore.user.current_business_id
        }
        await Promise.all([this.fetchEmployees(), this.fetchManagers(), this.fetchCountries()])
      } catch (err) {
        console.error('Initialization error:', err)
        this.error = 'Failed to initialize. Please try again.'
      }
    },
    initializeFormForAdd() {
      if (this.authStore.isAdmin && this.businesses.length === 1) {
        this.form.business_id = this.businesses[0].id
        this.fetchSettingsForBusiness(this.businesses[0].id)
      } else if (this.authStore.user?.current_business_id) {
        this.form.business_id = this.authStore.user.current_business_id
        this.fetchSettingsForBusiness(this.authStore.user.current_business_id)
      }
    },
    async fetchBusinesses() {
      try {
        const r = await axios.get('/api/admin/businesses')
        this.businesses = r.data.data || []
      } catch (e) { console.error('Failed to fetch businesses:', e) }
    },
    async fetchSettingsForBusiness(businessId) {
      this.loadingDepartments = true
      try {
        const r = await axios.get('/api/admin/settings', { params: { business_id: businessId } })
        this.availableDepartments = r.data?.settings?.departments || []
      } catch (e) { this.availableDepartments = [] }
      finally { this.loadingDepartments = false }
    },
    async fetchEmployees() {
      this.loading = true; this.error = null
      try {
        const url = this.selectedBusinessId
          ? `/api/admin/employees?business_id=${this.selectedBusinessId}`
          : '/api/admin/employees'
        const r = await axios.get(url)
        this.employees = r.data?.employees || r.data?.data || r.data || []
        this.currentPage = 1
      } catch (err) { this.handleApiError(err) }
      finally { this.loading = false }
    },
    async fetchManagers() {
      try {
        const url = this.selectedBusinessId
          ? `/api/admin/managers?business_id=${this.selectedBusinessId}`
          : '/api/admin/managers'
        const r = await axios.get(url)
        this.managers = Array.isArray(r.data) ? r.data : r.data?.data || r.data || []
      } catch (e) { this.managers = [] }
    },
    async fetchCountries() {
      try {
        const r = await axios.get('/api/admin/countries')
        this.countries = r.data?.data || r.data || []
      } catch (e) { this.countries = [] }
    },
    toggleCountryDropdown() {
      this.showCountryDropdown = !this.showCountryDropdown
      if (this.showCountryDropdown) {
        this.$nextTick(() => this.$refs.countrySearchInput?.focus())
      }
    },
    closeCountryDropdown(event) {
      if (!document.querySelector('.country-select-wrapper')?.contains(event.target)) {
        this.showCountryDropdown = false; this.countrySearch = ''
      }
    },
    selectCountry(country) {
      this.form.country_id = country.id; this.showCountryDropdown = false; this.countrySearch = ''
    },
    prevPage() { if (this.currentPage > 1) this.currentPage-- },
    nextPage() { if (this.currentPage < this.totalPages) this.currentPage++ },
    goToPage(page) { if (page !== '...' && page >= 1 && page <= this.totalPages) this.currentPage = page },
    onSearch() { this.currentPage = 1 },
    onItemsPerPageChange() { this.currentPage = 1 },
    onBusinessFilterChange() {
      this.currentPage = 1
      this.fetchEmployees(); this.fetchManagers()
      if (this.showAddModal && this.selectedBusinessId) {
        this.form.business_id = this.selectedBusinessId
        this.fetchSettingsForBusiness(this.selectedBusinessId)
      }
    },
    getBusinessName(id) { return this.businesses.find(b => b.id === id)?.name || 'Unknown' },
    getCountry(employee) {
      const id = employee.country_id || employee.country?.id
      return this.countries.find(c => c.id === id) || null
    },
    getEmployeeName(e) {
      if (e.first_name && e.last_name) return `${e.first_name} ${e.last_name}`.trim()
      if (e.user?.first_name) return `${e.user.first_name} ${e.user.last_name || ''}`.trim()
      return e.name || e.full_name || 'N/A'
    },
    getEmployeeRole(e) { return e.role || e.user?.role || 'employee' },
    getManagerName(id) {
      if (!id) return 'No Manager'
      const m = this.managers.find(m => m.id === id)
      return m ? `${m.first_name} ${m.last_name}` : 'Unknown'
    },
    formatRole(r) { return { admin: 'Admin', manager: 'Manager', employee: 'Employee' }[r] || 'Employee' },
    onRoleChange() { if (this.form.role !== 'employee') this.form.manager_id = '' },
    async submitForm() {
      this.submitting = true; this.formError = null
      if (this.authStore.isAdmin && !this.form.business_id) {
        this.formError = 'Please select a business.'; this.submitting = false; return
      }
      if (!this.form.country_id) {
        this.formError = 'Please select a country.'; this.submitting = false; return
      }
      if (this.form.role === 'employee' && !this.form.manager_id) {
        this.formError = 'Please select a manager for this employee.'; this.submitting = false; return
      }
      const payload = {
        first_name: this.form.first_name, last_name: this.form.last_name,
        email: this.form.email, business_id: parseInt(this.form.business_id),
        country_id: parseInt(this.form.country_id), role: this.form.role,
        position: this.form.position, department: this.form.department,
        base_salary: parseFloat(this.form.base_salary),
        transport_allowance: parseFloat(this.form.transport_allowance || 0),
        lunch_allowance: parseFloat(this.form.lunch_allowance || 0),
        employment_type: this.form.employment_type, hire_date: this.form.hire_date,
        manager_id: this.form.role === 'employee' ? this.form.manager_id : null
      }
      try {
        if (this.showEditModal) {
          await axios.put(`/api/admin/employees/${this.currentEmployee.id}`, payload)
        } else {
          await axios.post('/api/admin/employees', payload)
        }
        await Promise.all([this.fetchEmployees(), this.fetchManagers()])
        this.closeModals()
        this.$notify({ type: 'success', title: 'Success',
          text: this.showEditModal ? 'Employee updated successfully!' : 'Employee created! Default password: Password123!'
        })
      } catch (err) { this.handleApiError(err) }
      finally { this.submitting = false }
    },
    editEmployee(employee) {
      this.currentEmployee = employee
      const role = this.getEmployeeRole(employee)
      const businessId = employee.business_id || this.authStore.user?.current_business_id || ''
      this.form = {
        first_name: employee.first_name || employee.user?.first_name || '',
        last_name: employee.last_name || employee.user?.last_name || '',
        email: employee.email || employee.user?.email || '',
        business_id: businessId,
        country_id: employee.country_id || employee.country?.id || '',
        role, position: employee.position || '', department: employee.department || '',
        base_salary: employee.base_salary || '', transport_allowance: employee.transport_allowance || '',
        lunch_allowance: employee.lunch_allowance || '', employment_type: employee.employment_type || '',
        hire_date: employee.hire_date?.split('T')[0] || employee.created_at?.split('T')[0] || '',
        manager_id: role === 'employee' ? employee.manager_id : ''
      }
      this.fetchSettingsForBusiness(businessId)
      this.showEditModal = true
    },
    resetForm() {
      this.form = {
        first_name: '', last_name: '', email: '', business_id: '', country_id: '',
        role: 'employee', position: '', department: '', base_salary: '',
        transport_allowance: '', lunch_allowance: '', employment_type: '', hire_date: '', manager_id: ''
      }
      this.countrySearch = ''; this.showCountryDropdown = false; this.availableDepartments = []
    },
    getInitials(name) {
      if (!name || name === 'N/A') return '??'
      return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2)
    },
    formatNumber(num) {
      return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0)
    },
    formatDate(date) {
      if (!date) return '—'
      return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
    },
    formatEmploymentType(type) {
      return { full_time: 'Full Time', part_time: 'Part Time', contract: 'Contract' }[type] || type || '—'
    },
    retryFetch() {
      this.retryCount++
      if (this.retryCount <= 3) this.fetchEmployees()
      else this.error = 'Max retries exceeded. Check your connection.'
    },
    handleApiError(err) {
      if (err.response?.status === 401) {
        this.authStore.clearAuth(); this.$router.push({ name: 'login' }); return
      }
      if (err.response?.status === 422) {
        this.formError = err.response.data.message || 'Please check the form for errors.'; return
      }
      this.error = err.response?.data?.message || 'An unexpected error occurred.'
    },
    closeModals() {
      this.showAddModal = false; this.showEditModal = false
      this.currentEmployee = null; this.formError = null; this.resetForm()
    },
    async deleteEmployee(employee) {
      if (!confirm(`Delete ${this.getEmployeeName(employee)}? This cannot be undone.`)) return
      try {
        await axios.delete(`/api/admin/employees/${employee.id}`)
        await this.fetchEmployees()
        this.$notify({ type: 'success', title: 'Success', text: 'Employee deleted.' })
      } catch (err) { this.handleApiError(err) }
    }
  }
}
</script>

<style scoped>
/* ── Base ──────────────────────────────────────────── */
.emp-mgmt-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
  padding: 1.5rem 2rem 3rem;
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* ── Header Card ─────────────────────────────────── */
.dashboard-header-card {
  background: white; border-radius: 16px; padding: 1.5rem 1.75rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0; position: relative; overflow: hidden; flex-shrink: 0;
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
  background: #eff6ff; border: 1px solid #bfdbfe; padding: 0.125rem 0.6rem;
  border-radius: 8px; font-size: 0.7rem; font-weight: 600; color: #1d4ed8; display: inline-block;
}
.header-actions { display: flex; gap: 0.5rem; flex-shrink: 0; }

/* ── Buttons ─────────────────────────────────────── */
.btn-primary {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;
  border: none; padding: 0.5rem 1.1rem; border-radius: 8px;
  font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-primary:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,0.35); }
.btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-secondary {
  padding: 0.5rem 1.1rem; background: #f1f5f9; color: #475569;
  border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.875rem;
  font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-secondary:hover { background: #e2e8f0; }

.btn-inline {
  background: none; border: none; color: #6366f1; font-weight: 600;
  cursor: pointer; text-decoration: underline; font-size: 0.875rem; font-family: inherit; margin-left: 0.5rem;
}

/* ── Alert Cards ─────────────────────────────────── */
.alert-card {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 1rem 1.25rem; border-radius: 12px; font-size: 0.875rem; font-weight: 500;
}
.alert-card.error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

/* ── Section Card ────────────────────────────────── */
.section-card {
  background: white; border-radius: 16px; padding: 1.5rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0;
}
.filter-bar { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; padding: 1rem 1.5rem; }

/* ── Controls ────────────────────────────────────── */
.controls-bar {
  display: flex; justify-content: space-between; align-items: center;
  gap: 1rem; flex-wrap: wrap; margin-bottom: 1.25rem;
}
.controls-right { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }

.filter-group { display: flex; flex-direction: column; gap: 0.3rem; }
.filter-group label { font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }

.filter-select {
  padding: 0.45rem 2rem 0.45rem 0.75rem; border: 1px solid #e2e8f0;
  border-radius: 8px; background: #f8fafc; color: #334155;
  font-size: 0.82rem; font-weight: 500; appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 0.6rem center;
  transition: all 0.2s; font-family: inherit; cursor: pointer;
}
.filter-select.compact { padding-top: 0.35rem; padding-bottom: 0.35rem; }
.filter-select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

.search-wrapper { position: relative; }
.search-icon { position: absolute; left: 0.65rem; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
.filter-input {
  padding: 0.45rem 0.75rem 0.45rem 2rem; border: 1px solid #e2e8f0;
  border-radius: 8px; background: #f8fafc; color: #334155;
  font-size: 0.82rem; font-weight: 500; width: 240px; font-family: inherit;
  transition: all 0.2s;
}
.filter-input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.filter-input::placeholder { color: #94a3b8; }

.records-count {
  font-size: 0.78rem; font-weight: 700; color: #64748b;
  background: #f1f5f9; padding: 0.2rem 0.7rem; border-radius: 9999px; white-space: nowrap;
}
.business-active-badge {
  background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;
  padding: 0.2rem 0.75rem; border-radius: 9999px; font-size: 0.78rem; font-weight: 700;
}

/* ── Dashboard Content ───────────────────────────── */
.dashboard-content { display: flex; flex-direction: column; gap: 1.25rem; }

/* ── Table ───────────────────────────────────────── */
.table-wrap {
  overflow-x: auto;
  border-radius: 10px;
  border: 1px solid #e2e8f0;
  margin-bottom: 0.5rem;
}

.data-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }

/* Header: no bottom border — use background + shadow for separation */
.data-table th {
  background: #f1f5f9;
  font-size: 0.68rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 0.75rem 1rem;
  white-space: nowrap;
  position: sticky;
  top: 0;
  z-index: 10;
  text-align: left;
  /* Blend the bottom edge into the header bg so no line appears */
  border-bottom: 2px solid #f1f5f9;
}

/* First / last header cell rounding */
.data-table thead tr th:first-child { border-radius: 9px 0 0 0; }
.data-table thead tr th:last-child  { border-radius: 0 9px 0 0; }

.data-table td {
  padding: 0.8rem 1rem; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle;
}
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: #f8fafc; }

.emp-name-cell { display: flex; align-items: center; gap: 0.625rem; font-weight: 600; color: #1e293b; }
.emp-avatar {
  width: 32px; height: 32px; border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #6366f1); color: white;
  display: flex; align-items: center; justify-content: center; font-size: 0.72rem; font-weight: 700; flex-shrink: 0;
}

.mono { font-variant-numeric: tabular-nums; font-family: 'SFMono-Regular', 'Consolas', monospace; font-size: 0.8rem; }
.muted { color: #64748b; }

/* Country cell */
.country-cell { display: flex; align-items: center; gap: 0.5rem; white-space: nowrap; }
.flag-wrap {
  position: relative; width: 28px; height: 20px; border-radius: 3px;
  overflow: hidden; background: #f0f0f0; flex-shrink: 0;
}
.flag-wrap.sm { width: 28px; height: 20px; }
.flag-img { width: 100%; height: 100%; object-fit: cover; display: block; }
.flag-fallback {
  position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
  font-weight: 700; font-size: 0.6rem; color: #555; background: rgba(255,255,255,0.85);
}

/* Chips */
.chip {
  display: inline-block; padding: 0.15rem 0.65rem; border-radius: 9999px;
  font-size: 0.7rem; font-weight: 700; white-space: nowrap;
  background: #e0e7ff; color: #4338ca;
}
.chip.type { background: #f1f5f9; color: #475569; }
.chip.business { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.chip.role-employee { background: #d1fae5; color: #065f46; }
.chip.role-manager { background: #fef3c7; color: #92400e; }
.chip.role-admin { background: #fee2e2; color: #991b1b; }
.chip.warn-chip { background: #fef3c7; color: #92400e; font-size: 0.65rem; }

/* Row Actions */
.row-actions { display: flex; gap: 0.3rem; }
.action-btn {
  width: 28px; height: 28px; border-radius: 6px; border: 1px solid #e2e8f0;
  background: white; color: #64748b; display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.15s;
}
.action-btn:hover { background: #eff6ff; color: #4f46e5; border-color: #a5b4fc; }
.action-btn.danger:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }

/* ── Loading / Empty ─────────────────────────────── */
.board-loading {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; gap: 0.875rem; padding: 4rem 2rem;
  color: #64748b; font-size: 0.875rem;
}
.spinner {
  width: 40px; height: 40px; border: 3px solid #e2e8f0; border-top-color: #6366f1;
  border-radius: 50%; animation: spin 1s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.empty-state {
  text-align: center; padding: 4rem 2rem;
  display: flex; flex-direction: column; align-items: center; gap: 0.875rem;
}
.empty-icon-wrap {
  width: 64px; height: 64px; border-radius: 16px; background: #f1f5f9;
  border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: #94a3b8;
}
.empty-state h3 { margin: 0; font-size: 1.1rem; font-weight: 700; color: #1e293b; }
.empty-state p { margin: 0; font-size: 0.875rem; color: #64748b; max-width: 320px; }

/* ── Pagination ──────────────────────────────────── */
.pagination-bar {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1rem 0 0; border-top: 1px solid #f1f5f9; margin-top: 0.5rem;
}
.pagination-info { font-size: 0.82rem; color: #64748b; }
.pagination-info strong { color: #1e293b; font-weight: 700; }
.pagination-controls { display: flex; gap: 0.3rem; align-items: center; }
.page-btn {
  display: inline-flex; align-items: center; gap: 0.3rem;
  padding: 0.3rem 0.65rem; background: white; border: 1px solid #e2e8f0;
  border-radius: 6px; font-size: 0.78rem; font-weight: 600; color: #475569;
  cursor: pointer; transition: all 0.15s; font-family: inherit; min-width: 2rem; justify-content: center;
}
.page-btn:hover:not(:disabled) { border-color: #a5b4fc; color: #4f46e5; background: #eff6ff; }
.page-btn.active { background: #6366f1; color: white; border-color: #6366f1; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-ellipsis { padding: 0 0.25rem; color: #94a3b8; font-size: 0.82rem; }

/* ── Modal ───────────────────────────────────────── */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,0.45);
  display: flex; align-items: center; justify-content: center; z-index: 1000;
  backdrop-filter: blur(3px);
}
.modal {
  background: white; border-radius: 16px; width: 90%; max-width: 640px;
  max-height: 90vh; display: flex; flex-direction: column;
  box-shadow: 0 24px 48px -12px rgba(0,0,0,0.18); overflow: hidden;
}
.modal-header {
  display: flex; justify-content: space-between; align-items: flex-start;
  padding: 1.5rem; border-bottom: 1px solid #e2e8f0; flex-shrink: 0;
  background: linear-gradient(90deg, #001f5b 0%, #0040c1 100%);
}
.modal-title-row { display: flex; align-items: center; gap: 0.875rem; }
.modal-avatar {
  width: 42px; height: 42px; background: rgba(255,255,255,0.15);
  border: 1px solid rgba(255,255,255,0.25);
  border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;
}
.modal-header h2 { margin: 0; font-size: 1.1rem; font-weight: 700; color: white; }
.modal-subtitle { font-size: 0.78rem; color: rgba(255,255,255,0.65); margin: 0.2rem 0 0; }
.close-btn {
  background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
  border-radius: 8px; width: 32px; height: 32px; display: flex; align-items: center;
  justify-content: center; cursor: pointer; color: white; transition: background 0.15s; flex-shrink: 0;
}
.close-btn:hover { background: rgba(255,255,255,0.2); }

.modal-body { padding: 1.5rem; overflow-y: auto; flex-grow: 1; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-group { margin-bottom: 1rem; }
.form-group.full-width { grid-column: 1 / -1; }
.form-group label {
  display: block; margin-bottom: 0.4rem; font-size: 0.78rem; font-weight: 700;
  color: #374151; text-transform: uppercase; letter-spacing: 0.04em;
}
.req { color: #ef4444; }
.form-control {
  width: 100%; padding: 0.55rem 0.85rem; border: 1px solid #e2e8f0;
  border-radius: 8px; font-size: 0.875rem; color: #1e293b; background: #f8fafc;
  font-family: inherit; transition: all 0.15s; box-sizing: border-box;
}
.form-control:focus { outline: none; border-color: #6366f1; background: white; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.form-control:disabled { background: #f1f5f9; color: #94a3b8; cursor: not-allowed; }
.disabled-input { background: #f1f5f9 !important; color: #94a3b8 !important; cursor: not-allowed; }

select.form-control {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 0.75rem center; padding-right: 2.25rem;
}

.hint { font-size: 0.75rem; color: #94a3b8; margin-top: 0.3rem; display: block; }
.hint.warn { color: #f59e0b; }

.form-error-bar {
  display: flex; align-items: center; gap: 0.5rem;
  background: #fee2e2; color: #991b1b; padding: 0.75rem 1rem;
  border-radius: 8px; font-size: 0.82rem; margin-top: 0.5rem;
}

.modal-footer {
  display: flex; justify-content: flex-end; gap: 0.75rem;
  padding: 1.25rem 1.5rem; border-top: 1px solid #e2e8f0; background: #f8fafc; flex-shrink: 0;
}

/* Country selector in modal */
.country-select-wrapper { position: relative; }
.country-select-header {
  display: flex; align-items: center; justify-content: space-between; gap: 0.75rem;
  padding: 0.55rem 0.85rem; border: 1px solid #e2e8f0; border-radius: 8px;
  background: #f8fafc; cursor: pointer; transition: all 0.15s;
}
.country-select-header:hover { border-color: #6366f1; }
.selected-country { display: flex; align-items: center; gap: 0.5rem; flex: 1; }
.placeholder-text { color: #94a3b8; font-size: 0.875rem; flex: 1; }
.country-name-val { font-size: 0.875rem; font-weight: 500; color: #1e293b; }
.country-code-val { font-size: 0.75rem; color: #64748b; }
.chevron { color: #64748b; transition: transform 0.2s; flex-shrink: 0; }
.chevron.open { transform: rotate(180deg); }

.country-dropdown {
  position: absolute; top: calc(100% + 4px); left: 0; right: 0;
  background: white; border: 1px solid #e2e8f0; border-radius: 10px;
  box-shadow: 0 8px 24px -4px rgba(0,0,0,0.12); z-index: 200; overflow: hidden;
}
.country-search-wrap {
  position: relative; padding: 0.75rem;
  border-bottom: 1px solid #f1f5f9;
}
.country-search-input {
  width: 100%; padding: 0.45rem 0.75rem 0.45rem 2rem; border: 1px solid #e2e8f0;
  border-radius: 7px; font-size: 0.82rem; background: #f8fafc; color: #1e293b;
  font-family: inherit; box-sizing: border-box;
}
.country-search-input:focus { outline: none; border-color: #6366f1; }
.country-search-wrap .search-icon { position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: #94a3b8; }
.country-list { max-height: 220px; overflow-y: auto; }
.country-option {
  display: flex; align-items: center; gap: 0.625rem;
  padding: 0.6rem 0.875rem; cursor: pointer; border-bottom: 1px solid #f8fafc;
  transition: background 0.12s; color: #334155;
}
.country-option:last-child { border-bottom: none; }
.country-option:hover { background: #f8fafc; }
.country-option.selected { background: #eff6ff; }
.country-option.inactive { opacity: 0.55; }
.check-icon { margin-left: auto; flex-shrink: 0; }
.country-empty { padding: 1rem; text-align: center; font-size: 0.82rem; color: #94a3b8; }

/* ── Responsive ──────────────────────────────────── */
@media (max-width: 900px) {
  .emp-mgmt-page { padding: 1rem 1rem 2rem; }
  .user-greeting { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .header-actions { width: 100%; }
  .btn-primary { width: 100%; justify-content: center; }
  .controls-bar { flex-direction: column; align-items: flex-start; }
  .filter-input { width: 100%; }
  .pagination-bar { flex-direction: column; gap: 0.75rem; align-items: flex-start; }
  .form-row { grid-template-columns: 1fr; }
  .modal { max-height: 95vh; }
}
</style>