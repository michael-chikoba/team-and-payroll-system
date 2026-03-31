<template>
  <div class="emp-mgmt-page">

    <!-- ── Header Card ─────────────────────────────── -->
    <div class="dashboard-header-card">
      <div class="header-card-accent"></div>
      <div class="user-greeting">
        <div class="avatar-section">
          <div class="avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
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
          <button v-if="authStore.isAdmin && activeTab === 'active'" @click="showAddModal = true" class="btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Add Employee
          </button>
        </div>
      </div>
    </div>

    <!-- ── Auth Guards ────────────────────────────── -->
    <div v-if="!authStore.isAuthenticated" class="alert-card error">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
      Please log in to access employee management.
    </div>
    <div v-else-if="!authStore.isAdmin && !authStore.isManager" class="alert-card error">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
      You don't have permission to access this page.
    </div>

    <!-- ── Loading ────────────────────────────────── -->
    <div v-else-if="loading" class="section-card">
      <div class="board-loading">
        <div class="spinner"></div>
        <p>Loading employees...</p>
      </div>
    </div>

    <!-- ── Error ──────────────────────────────────── -->
    <div v-else-if="error" class="alert-card error">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
      {{ error }}
      <button @click="retryFetch" class="btn-inline">Retry</button>
    </div>

    <!-- ── Main Content ───────────────────────────── -->
    <div v-else class="dashboard-content">

      <!-- Business Filter -->
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

      <!-- ── Tabs ───────────────────────────────── -->
      <div class="tabs-bar">
        <button
          class="tab-btn"
          :class="{ active: activeTab === 'active' }"
          @click="switchTab('active')"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
          Active Employees
          <span class="tab-count">{{ employees.length }}</span>
        </button>
        <button
          v-if="authStore.isAdmin"
          class="tab-btn"
          :class="{ active: activeTab === 'archived' }"
          @click="switchTab('archived')"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line></svg>
          Archived Employees
          <span class="tab-count archived">{{ archivedEmployees.length }}</span>
        </button>
      </div>

      <!-- ══════════ ACTIVE TAB ══════════ -->
      <div v-if="activeTab === 'active'" class="section-card">
        <!-- Controls -->
        <div class="controls-bar">
          <div class="search-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="search-icon"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input v-model="searchQuery" type="text" @input="onSearch" class="filter-input" placeholder="Search employees..." />
          </div>
          <div class="controls-right">
            <div class="filter-group">
              <label>Per page</label>
              <select v-model="itemsPerPage" @change="onItemsPerPageChange" class="filter-select compact">
                <option value="10">10</option><option value="25">25</option>
                <option value="50">50</option><option value="100">100</option>
              </select>
            </div>
            <span class="records-count">{{ showingStart }}–{{ showingEnd }} of {{ totalItems }} employees</span>
          </div>
        </div>

        <!-- Table -->
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Name</th><th>ID</th><th>Email</th><th>Position</th><th>Department</th>
                <th>Country</th>
                <th v-if="authStore.isAdmin && businesses.length > 1">Business</th>
                <th>Salary</th><th>Transport</th><th>Lunch</th><th>Hire Date</th>
                <th>Manager</th><th>Type</th><th>Role</th><th>Status</th>
                <th v-if="authStore.isAdmin">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="employee in paginatedEmployees" :key="employee.id"
                  :class="{ 'row-suspended': employee.status === 'suspended' }">
                <td>
                  <div class="emp-name-cell">
                    <div class="emp-avatar" :class="{ 'avatar-suspended': employee.status === 'suspended' }">
                      {{ getInitials(getEmployeeName(employee)) }}
                    </div>
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
                <td><span class="chip type">{{ formatEmploymentType(employee.employment_type) }}</span></td>
                <td><span class="chip" :class="'role-' + getEmployeeRole(employee)">{{ formatRole(getEmployeeRole(employee)) }}</span></td>
                <td>
                  <span class="chip" :class="'status-' + employee.status">{{ formatStatus(employee.status) }}</span>
                </td>
                <td v-if="authStore.isAdmin">
                  <div class="row-actions">
                    <button @click="editEmployee(employee)" class="action-btn" title="Edit">
                      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <!-- Suspend -->
                    <button
                      v-if="employee.status === 'active'"
                      @click="openSuspendModal(employee)"
                      class="action-btn warn"
                      title="Suspend"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="10" y1="15" x2="10" y2="9"></line><line x1="14" y1="15" x2="14" y2="9"></line></svg>
                    </button>
                    <!-- Reinstate from suspended -->
                    <button
                      v-if="employee.status === 'suspended'"
                      @click="openReinstateModal(employee)"
                      class="action-btn success"
                      title="Reinstate"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                    </button>
                    <!-- Archive -->
                    <button
                      @click="openArchiveModal(employee)"
                      class="action-btn danger"
                      title="Archive"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty -->
        <div v-if="filteredEmployees.length === 0 && !loading" class="empty-state">
          <div class="empty-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
          </div>
          <h3>No employees found</h3>
          <p v-if="searchQuery">No results matching "{{ searchQuery }}"</p>
          <p v-else>Get started by adding your first employee</p>
          <button v-if="authStore.isAdmin" @click="showAddModal = true" class="btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Add First Employee
          </button>
        </div>

        <!-- Pagination -->
        <div v-if="filteredEmployees.length > 0" class="pagination-bar">
          <span class="pagination-info">Showing <strong>{{ showingStart }}</strong>–<strong>{{ showingEnd }}</strong> of <strong>{{ totalItems }}</strong></span>
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

      <!-- ══════════ ARCHIVED TAB ══════════ -->
      <div v-if="activeTab === 'archived'" class="section-card">
        <div class="archived-header">
          <div class="archived-info-box">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <span>Archived employees cannot log in but all their records (payslips, leave, attendance) are preserved. You can reinstate them at any time.</span>
          </div>
        </div>

        <!-- Controls -->
        <div class="controls-bar">
          <div class="search-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="search-icon"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input v-model="archiveSearchQuery" type="text" class="filter-input" placeholder="Search archived employees..." />
          </div>
          <span class="records-count">{{ filteredArchivedEmployees.length }} archived</span>
        </div>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Name</th><th>ID</th><th>Email</th><th>Position</th><th>Department</th>
                <th>Archive Reason</th><th>Archived On</th><th>Archived By</th>
                <th>Suspension Reason</th><th>Notes</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="employee in filteredArchivedEmployees" :key="employee.id" class="row-archived">
                <td>
                  <div class="emp-name-cell">
                    <div class="emp-avatar avatar-archived">{{ getInitials(getEmployeeName(employee)) }}</div>
                    <span>{{ getEmployeeName(employee) }}</span>
                  </div>
                </td>
                <td class="mono">{{ employee.employee_id || employee.id }}</td>
                <td class="muted">{{ employee.email || employee.user?.email || '—' }}</td>
                <td>{{ employee.position || '—' }}</td>
                <td>{{ employee.department || '—' }}</td>
                <td>
                  <span class="reason-cell" :title="employee.archive_reason">{{ employee.archive_reason || '—' }}</span>
                </td>
                <td class="muted">{{ formatDate(employee.archived_at) }}</td>
                <td class="muted">{{ employee.archived_by_name || '—' }}</td>
                <td>
                  <span class="reason-cell muted" :title="employee.suspension_reason">{{ employee.suspension_reason || '—' }}</span>
                </td>
                <td>
                  <span class="reason-cell muted" :title="employee.status_notes">{{ employee.status_notes || '—' }}</span>
                </td>
                <td>
                  <div class="row-actions">
                    <button @click="openReinstateModal(employee)" class="action-btn success" title="Reinstate Employee">
                      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="filteredArchivedEmployees.length === 0" class="empty-state">
          <div class="empty-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line></svg>
          </div>
          <h3>No archived employees</h3>
          <p>Archived employees will appear here.</p>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════ -->
    <!-- ADD / EDIT MODAL                             -->
    <!-- ════════════════════════════════════════════ -->
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
          </div>
          <div v-else-if="authStore.isAdmin && businesses.length === 1" class="form-group full-width">
            <label>Business</label>
            <input :value="businesses[0].name" type="text" disabled class="form-control disabled-input" />
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
          <!-- Email -->
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
                  <div v-for="country in filteredCountries" :key="country.id" class="country-option"
                    :class="{ selected: form.country_id === country.id }"
                    @click="selectCountry(country)">
                    <div class="flag-wrap sm">
                      <img :src="country.flag" class="flag-img" @error="e => e.target.style.display='none'" />
                      <span class="flag-fallback">{{ country.code }}</span>
                    </div>
                    <span class="country-name-val">{{ country.name }}</span>
                    <svg v-if="form.country_id === country.id" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="3" class="check-icon"><polyline points="20 6 9 17 4 12"></polyline></svg>
                  </div>
                  <div v-if="filteredCountries.length === 0" class="country-empty">No countries found</div>
                </div>
              </div>
            </div>
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
              <select v-model="form.department" required class="form-control">
                <option value="">{{ loadingDepartments ? 'Loading...' : 'Select Department' }}</option>
                <option v-for="(dept, i) in availableDepartments" :key="i" :value="dept.name">{{ dept.name }}</option>
              </select>
            </div>
          </div>
          <!-- Salary -->
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
          <div class="form-row">
            <div class="form-group">
              <label>Hire Date <span class="req">*</span></label>
              <input v-model="form.hire_date" type="date" required class="form-control" />
            </div>
            <div class="form-group">
              <label>Manager</label>
              <select v-model="form.manager_id" :disabled="form.role !== 'employee'" class="form-control">
                <option value="">No Manager</option>
                <option v-for="mgr in managers" :key="mgr.id" :value="mgr.id">{{ mgr.first_name }} {{ mgr.last_name }} ({{ mgr.department }})</option>
              </select>
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

    <!-- ════════════════════════════════════════════ -->
    <!-- SUSPEND MODAL                                -->
    <!-- ════════════════════════════════════════════ -->
    <div v-if="showSuspendModal" class="modal-overlay" @click.self="closeSuspendModal">
      <div class="modal modal-sm">
        <div class="modal-header warn-header">
          <div class="modal-title-row">
            <div class="modal-avatar warn-avatar">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="10" y1="15" x2="10" y2="9"></line><line x1="14" y1="15" x2="14" y2="9"></line></svg>
            </div>
            <div>
              <h2>Suspend Employee</h2>
              <p class="modal-subtitle">{{ getEmployeeName(actionTarget) }}</p>
            </div>
          </div>
          <button @click="closeSuspendModal" class="close-btn close-btn-dark">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </div>
        <div class="modal-body">
          <div class="action-info-box warn">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
            <span>The employee will immediately lose access to the system. Their data is fully preserved and they can be reinstated at any time.</span>
          </div>
          <div class="form-group" style="margin-top:1rem">
            <label>Reason for Suspension <span class="req">*</span></label>
            <select v-model="actionForm.reason" class="form-control">
              <option value="">Select a reason...</option>
              <option value="Performance issues">Performance issues</option>
              <option value="Disciplinary action">Disciplinary action</option>
              <option value="Investigation pending">Investigation pending</option>
              <option value="Contract dispute">Contract dispute</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group" v-if="actionForm.reason === 'Other' || actionForm.customReason !== undefined">
            <label>Specify Reason <span class="req">*</span></label>
            <input v-model="actionForm.customReason" type="text" placeholder="Enter reason..." class="form-control" />
          </div>
          <div class="form-group">
            <label>Additional Notes</label>
            <textarea v-model="actionForm.notes" placeholder="Optional notes..." class="form-control" rows="3"></textarea>
          </div>
          <div v-if="actionError" class="form-error-bar">{{ actionError }}</div>
        </div>
        <div class="modal-footer">
          <button @click="closeSuspendModal" class="btn-secondary">Cancel</button>
          <button @click="confirmSuspend" class="btn-warn" :disabled="actionSubmitting">
            {{ actionSubmitting ? 'Suspending...' : 'Suspend Employee' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════ -->
    <!-- ARCHIVE MODAL                                -->
    <!-- ════════════════════════════════════════════ -->
    <div v-if="showArchiveModal" class="modal-overlay" @click.self="closeArchiveModal">
      <div class="modal modal-sm">
        <div class="modal-header danger-header">
          <div class="modal-title-row">
            <div class="modal-avatar danger-avatar">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"></polyline><rect x="1" y="3" width="22" height="5"></rect><line x1="10" y1="12" x2="14" y2="12"></line></svg>
            </div>
            <div>
              <h2>Archive Employee</h2>
              <p class="modal-subtitle">{{ getEmployeeName(actionTarget) }}</p>
            </div>
          </div>
          <button @click="closeArchiveModal" class="close-btn close-btn-dark">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </div>
        <div class="modal-body">
          <div class="action-info-box danger">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <span>Archiving will block all system access. All historical records (payslips, attendance, leave) are permanently preserved. The employee can still be reinstated from the Archived tab.</span>
          </div>
          <div class="form-group" style="margin-top:1rem">
            <label>Reason for Archiving <span class="req">*</span></label>
            <select v-model="actionForm.reason" class="form-control">
              <option value="">Select a reason...</option>
              <option value="Resignation">Resignation</option>
              <option value="Termination">Termination</option>
              <option value="Contract ended">Contract ended</option>
              <option value="Retirement">Retirement</option>
              <option value="Redundancy">Redundancy</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="form-group" v-if="actionForm.reason === 'Other'">
            <label>Specify Reason <span class="req">*</span></label>
            <input v-model="actionForm.customReason" type="text" placeholder="Enter reason..." class="form-control" />
          </div>
          <div class="form-group">
            <label>Additional Notes</label>
            <textarea v-model="actionForm.notes" placeholder="Optional notes..." class="form-control" rows="3"></textarea>
          </div>
          <div v-if="actionError" class="form-error-bar">{{ actionError }}</div>
        </div>
        <div class="modal-footer">
          <button @click="closeArchiveModal" class="btn-secondary">Cancel</button>
          <button @click="confirmArchive" class="btn-danger" :disabled="actionSubmitting">
            {{ actionSubmitting ? 'Archiving...' : 'Archive Employee' }}
          </button>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════ -->
    <!-- REINSTATE MODAL                              -->
    <!-- ════════════════════════════════════════════ -->
    <div v-if="showReinstateModal" class="modal-overlay" @click.self="closeReinstateModal">
      <div class="modal modal-sm">
        <div class="modal-header success-header">
          <div class="modal-title-row">
            <div class="modal-avatar success-avatar">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
            </div>
            <div>
              <h2>Reinstate Employee</h2>
              <p class="modal-subtitle">{{ getEmployeeName(actionTarget) }}</p>
            </div>
          </div>
          <button @click="closeReinstateModal" class="close-btn close-btn-dark">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </div>
        <div class="modal-body">
          <div class="action-info-box success">
            <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
            <span>
              <template v-if="actionTarget?.status === 'archived'">This employee was archived on {{ formatDate(actionTarget?.archived_at) }}. Reinstating will restore their full system access.</template>
              <template v-else>This employee was suspended on {{ formatDate(actionTarget?.suspended_at) }}. Reinstating will restore their full system access.</template>
            </span>
          </div>
          <div class="form-group" style="margin-top:1rem">
            <label>Reinstatement Notes</label>
            <textarea v-model="actionForm.notes" placeholder="Optional notes about reinstatement..." class="form-control" rows="3"></textarea>
          </div>
          <div v-if="actionError" class="form-error-bar">{{ actionError }}</div>
        </div>
        <div class="modal-footer">
          <button @click="closeReinstateModal" class="btn-secondary">Cancel</button>
          <button @click="confirmReinstate" class="btn-success" :disabled="actionSubmitting">
            {{ actionSubmitting ? 'Reinstating...' : 'Reinstate Employee' }}
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
      // Tab
      activeTab: 'active',

      // Data
      employees: [],
      archivedEmployees: [],
      managers: [],
      countries: [],
      businesses: [],
      availableDepartments: [],

      // UI state
      loading: false,
      loadingDepartments: false,
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
      archiveSearchQuery: '',
      retryCount: 0,

      // Add / edit form
      form: {
        first_name: '', last_name: '', email: '', business_id: '', country_id: '',
        role: 'employee', position: '', department: '', base_salary: '',
        transport_allowance: '', lunch_allowance: '', employment_type: '',
        hire_date: '', manager_id: '',
      },

      // Action modals (suspend / archive / reinstate)
      showSuspendModal: false,
      showArchiveModal: false,
      showReinstateModal: false,
      actionTarget: null,
      actionSubmitting: false,
      actionError: null,
      actionForm: { reason: '', customReason: '', notes: '' },
    }
  },
  computed: {
    // Countries
    countriesSorted() { return [...this.countries].sort((a, b) => a.name.localeCompare(b.name)) },
    filteredCountries() {
      if (!this.countrySearch.trim()) return this.countriesSorted
      const s = this.countrySearch.toLowerCase()
      return this.countriesSorted.filter(c => c.name.toLowerCase().includes(s) || c.code.toLowerCase().includes(s))
    },
    selectedCountry() {
      return this.form.country_id ? this.countries.find(c => c.id === this.form.country_id) : null
    },

    // Active tab filtering
    filteredEmployees() {
      if (!this.searchQuery.trim()) return this.employees
      const s = this.searchQuery.toLowerCase()
      return this.employees.filter(e =>
        this.getEmployeeName(e).toLowerCase().includes(s) ||
        (e.email || e.user?.email || '').toLowerCase().includes(s) ||
        (e.position || '').toLowerCase().includes(s) ||
        (e.department || '').toLowerCase().includes(s) ||
        (e.employee_id || e.id || '').toString().toLowerCase().includes(s)
      )
    },
    totalItems()    { return this.filteredEmployees.length },
    totalPages()    { return Math.ceil(this.totalItems / this.itemsPerPage) },
    showingStart()  { return this.totalItems === 0 ? 0 : (this.currentPage - 1) * this.itemsPerPage + 1 },
    showingEnd()    { return Math.min(this.currentPage * this.itemsPerPage, this.totalItems) },
    paginatedEmployees() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      return this.filteredEmployees.slice(start, start + this.itemsPerPage)
    },
    visiblePages() {
      const pages = []; const total = this.totalPages; const cur = this.currentPage
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
      pages.push(total); return pages
    },

    // Archived tab filtering
    filteredArchivedEmployees() {
      if (!this.archiveSearchQuery.trim()) return this.archivedEmployees
      const s = this.archiveSearchQuery.toLowerCase()
      return this.archivedEmployees.filter(e =>
        this.getEmployeeName(e).toLowerCase().includes(s) ||
        (e.email || e.user?.email || '').toLowerCase().includes(s) ||
        (e.position || '').toLowerCase().includes(s) ||
        (e.department || '').toLowerCase().includes(s)
      )
    },
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
    },
  },
  mounted() {
    this.initializeComponent()
    document.addEventListener('click', this.closeCountryDropdown)
  },
  beforeUnmount() {
    document.removeEventListener('click', this.closeCountryDropdown)
  },
  methods: {
    // ── Init ──────────────────────────────────────────────────────
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

    // ── Tab switching ─────────────────────────────────────────────
    async switchTab(tab) {
      this.activeTab = tab
      if (tab === 'archived' && this.archivedEmployees.length === 0) {
        await this.fetchArchivedEmployees()
      }
    },

    // ── Data fetching ─────────────────────────────────────────────
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
    async fetchArchivedEmployees() {
      try {
        const url = this.selectedBusinessId
          ? `/api/admin/employees-archived?business_id=${this.selectedBusinessId}`
          : '/api/admin/employees-archived'
        const r = await axios.get(url)
        this.archivedEmployees = r.data?.data || r.data || []
      } catch (err) { console.error('Failed to fetch archived employees:', err) }
    },
    async fetchManagers() {
      try {
        const url = this.selectedBusinessId
          ? `/api/admin/managers?business_id=${this.selectedBusinessId}`
          : '/api/admin/managers'
        const r = await axios.get(url)
        this.managers = Array.isArray(r.data) ? r.data : r.data?.data || []
      } catch (e) { this.managers = [] }
    },
    async fetchCountries() {
      try {
        const r = await axios.get('/api/admin/countries')
        this.countries = r.data?.data || r.data || []
      } catch (e) { this.countries = [] }
    },

    // ── Add / Edit form ───────────────────────────────────────────
    async submitForm() {
      this.submitting = true; this.formError = null
      if (this.authStore.isAdmin && !this.form.business_id) {
        this.formError = 'Please select a business.'; this.submitting = false; return
      }
      if (!this.form.country_id) {
        this.formError = 'Please select a country.'; this.submitting = false; return
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
        manager_id: this.form.role === 'employee' ? this.form.manager_id : null,
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
          text: this.showEditModal ? 'Employee updated!' : 'Employee created! Default password: Password123!',
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
        manager_id: role === 'employee' ? employee.manager_id : '',
      }
      this.fetchSettingsForBusiness(businessId)
      this.showEditModal = true
    },

    // ── Suspend / Archive / Reinstate modals ─────────────────────
    openSuspendModal(employee) {
      this.actionTarget = employee
      this.actionForm   = { reason: '', customReason: '', notes: '' }
      this.actionError  = null
      this.showSuspendModal = true
    },
    closeSuspendModal() {
      this.showSuspendModal = false; this.actionTarget = null; this.actionError = null
    },
    async confirmSuspend() {
      const reason = this.actionForm.reason === 'Other'
        ? this.actionForm.customReason?.trim()
        : this.actionForm.reason

      if (!reason) {
        this.actionError = 'Please provide a reason for suspension.'; return
      }
      this.actionSubmitting = true; this.actionError = null
      try {
        await axios.post(`/api/admin/employees/${this.actionTarget.id}/suspend`, {
          reason, notes: this.actionForm.notes || null,
        })
        this.closeSuspendModal()
        await this.fetchEmployees()
        this.$notify({ type: 'warning', title: 'Employee Suspended', text: `${this.getEmployeeName(this.actionTarget || {})} has been suspended.` })
      } catch (err) {
        this.actionError = err.response?.data?.message || 'Failed to suspend employee.'
      } finally { this.actionSubmitting = false }
    },

    openArchiveModal(employee) {
      this.actionTarget = employee
      this.actionForm   = { reason: '', customReason: '', notes: '' }
      this.actionError  = null
      this.showArchiveModal = true
    },
    closeArchiveModal() {
      this.showArchiveModal = false; this.actionTarget = null; this.actionError = null
    },
    async confirmArchive() {
      const reason = this.actionForm.reason === 'Other'
        ? this.actionForm.customReason?.trim()
        : this.actionForm.reason

      if (!reason) {
        this.actionError = 'Please provide a reason for archiving.'; return
      }
      this.actionSubmitting = true; this.actionError = null
      try {
        await axios.post(`/api/admin/employees/${this.actionTarget.id}/archive`, {
          reason, notes: this.actionForm.notes || null,
        })
        this.closeArchiveModal()
        await Promise.all([this.fetchEmployees(), this.fetchArchivedEmployees()])
        this.$notify({ type: 'info', title: 'Employee Archived', text: 'Employee has been archived successfully.' })
      } catch (err) {
        this.actionError = err.response?.data?.message || 'Failed to archive employee.'
      } finally { this.actionSubmitting = false }
    },

    openReinstateModal(employee) {
      this.actionTarget = employee
      this.actionForm   = { reason: '', customReason: '', notes: '' }
      this.actionError  = null
      this.showReinstateModal = true
    },
    closeReinstateModal() {
      this.showReinstateModal = false; this.actionTarget = null; this.actionError = null
    },
    async confirmReinstate() {
      this.actionSubmitting = true; this.actionError = null
      try {
        await axios.post(`/api/admin/employees/${this.actionTarget.id}/reinstate`, {
          notes: this.actionForm.notes || null,
        })
        const name = this.getEmployeeName(this.actionTarget)
        this.closeReinstateModal()
        await Promise.all([this.fetchEmployees(), this.fetchArchivedEmployees()])
        // Switch to active tab after reinstate
        this.activeTab = 'active'
        this.$notify({ type: 'success', title: 'Employee Reinstated', text: `${name} has been reinstated successfully.` })
      } catch (err) {
        this.actionError = err.response?.data?.message || 'Failed to reinstate employee.'
      } finally { this.actionSubmitting = false }
    },

    // ── Helpers ───────────────────────────────────────────────────
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
    prevPage()           { if (this.currentPage > 1) this.currentPage-- },
    nextPage()           { if (this.currentPage < this.totalPages) this.currentPage++ },
    goToPage(page)       { if (page !== '...' && page >= 1 && page <= this.totalPages) this.currentPage = page },
    onSearch()           { this.currentPage = 1 },
    onItemsPerPageChange(){ this.currentPage = 1 },
    onBusinessFilterChange() {
      this.currentPage = 1
      this.fetchEmployees(); this.fetchManagers()
      if (this.activeTab === 'archived') this.fetchArchivedEmployees()
    },
    getBusinessName(id)   { return this.businesses.find(b => b.id === id)?.name || 'Unknown' },
    getCountry(employee) {
      const id = employee.country_id || employee.country?.id
      return this.countries.find(c => c.id === id) || null
    },
    getEmployeeName(e) {
      if (!e) return ''
      if (e.first_name && e.last_name) return `${e.first_name} ${e.last_name}`.trim()
      if (e.user?.first_name) return `${e.user.first_name} ${e.user.last_name || ''}`.trim()
      return e.name || e.full_name || 'N/A'
    },
    getEmployeeRole(e)   { return e.role || e.user?.role || 'employee' },
    getManagerName(id)   {
      if (!id) return 'No Manager'
      const m = this.managers.find(m => m.id === id)
      return m ? `${m.first_name} ${m.last_name}` : 'Unknown'
    },
    formatRole(r)        { return { admin: 'Admin', manager: 'Manager', employee: 'Employee' }[r] || 'Employee' },
    formatStatus(s)      { return { active: 'Active', suspended: 'Suspended', archived: 'Archived' }[s] || s },
    onRoleChange()       { if (this.form.role !== 'employee') this.form.manager_id = '' },
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
    resetForm() {
      this.form = {
        first_name: '', last_name: '', email: '', business_id: '', country_id: '',
        role: 'employee', position: '', department: '', base_salary: '',
        transport_allowance: '', lunch_allowance: '', employment_type: '', hire_date: '', manager_id: '',
      }
      this.countrySearch = ''; this.showCountryDropdown = false; this.availableDepartments = []
    },
    closeModals() {
      this.showAddModal = false; this.showEditModal = false
      this.currentEmployee = null; this.formError = null; this.resetForm()
    },
  },
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

/* ── Header ──────────────────────────────────────── */
.dashboard-header-card {
  background: white; border-radius: 16px; padding: 1.5rem 1.75rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;
  position: relative; overflow: hidden;
}
.header-card-accent { position: absolute; top: 0; left: 0; right: 0; height: 3px;  }
.user-greeting { display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; }
.avatar-section { display: flex; align-items: center; gap: 1rem; }
.avatar {
  width: 52px; height: 52px;
  
  border-radius: 14px; display: flex; align-items: center; justify-content: center;
  color: white; box-shadow: 0 4px 12px rgba(59,130,246,0.25); flex-shrink: 0;
}
.user-info { display: flex; flex-direction: column; gap: 0.2rem; }
.greeting { margin: 0; font-size: 1.375rem; font-weight: 700; color: #1e293b; }
.subtitle { margin: 0; color: #64748b; font-size: 0.875rem; }
.role-badge {
  background: #eff6ff; border: 1px solid #bfdbfe; padding: 0.125rem 0.6rem;
  border-radius: 8px; font-size: 0.7rem; font-weight: 600; color: #1d4ed8;
}
.header-actions { display: flex; gap: 0.5rem; }

/* ── Tabs ────────────────────────────────────────── */
.tabs-bar {
  display: flex; gap: 0.5rem;
  background: white; border-radius: 12px; padding: 0.4rem;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0,0,0,0.04);
  width: fit-content;
}
.tab-btn {
  display: inline-flex; align-items: center; gap: 0.5rem;
  padding: 0.5rem 1.1rem; border-radius: 8px; border: none;
  background: transparent; color: #64748b; font-size: 0.85rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.tab-btn:hover { background: #f1f5f9; color: #1e293b; }
.tab-btn.active { background: #6366f1; color: white; box-shadow: 0 2px 8px rgba(99,102,241,0.3); }
.tab-count {
  background: rgba(255,255,255,0.2); color: inherit;
  border-radius: 9999px; padding: 0.05rem 0.5rem;
  font-size: 0.72rem; font-weight: 700;
}
.tab-btn:not(.active) .tab-count { background: #e2e8f0; color: #475569; }
.tab-count.archived { background: #fee2e2; color: #dc2626; }
.tab-btn.active .tab-count.archived { background: rgba(255,255,255,0.25); color: white; }

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

.btn-warn {
  padding: 0.5rem 1.1rem; background: #f59e0b; color: white;
  border: none; border-radius: 8px; font-size: 0.875rem;
  font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-warn:hover:not(:disabled) { background: #d97706; }
.btn-warn:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-danger {
  padding: 0.5rem 1.1rem; background: #ef4444; color: white;
  border: none; border-radius: 8px; font-size: 0.875rem;
  font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-danger:hover:not(:disabled) { background: #dc2626; }
.btn-danger:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-success {
  padding: 0.5rem 1.1rem; background: #10b981; color: white;
  border: none; border-radius: 8px; font-size: 0.875rem;
  font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-success:hover:not(:disabled) { background: #059669; }
.btn-success:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-inline {
  background: none; border: none; color: #6366f1; font-weight: 600;
  cursor: pointer; text-decoration: underline; font-size: 0.875rem; font-family: inherit; margin-left: 0.5rem;
}

/* ── Alert / Info boxes ──────────────────────────── */
.alert-card {
  display: flex; align-items: center; gap: 0.75rem;
  padding: 1rem 1.25rem; border-radius: 12px; font-size: 0.875rem; font-weight: 500;
}
.alert-card.error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }

.archived-header { margin-bottom: 1rem; }
.archived-info-box {
  display: flex; align-items: flex-start; gap: 0.625rem;
  background: #eff6ff; border: 1px solid #bfdbfe;
  color: #1d4ed8; padding: 0.875rem 1rem; border-radius: 10px; font-size: 0.82rem;
}

.action-info-box {
  display: flex; align-items: flex-start; gap: 0.625rem;
  padding: 0.875rem 1rem; border-radius: 10px; font-size: 0.82rem; line-height: 1.5;
}
.action-info-box.warn    { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; }
.action-info-box.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
.action-info-box.success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }

/* ── Section / Filters ───────────────────────────── */
.section-card {
  background: white; border-radius: 16px; padding: 1.5rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;
}
.filter-bar { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; padding: 1rem 1.5rem; }
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
  font-family: inherit; cursor: pointer;
}
.filter-select.compact { padding-top: 0.35rem; padding-bottom: 0.35rem; }
.filter-select:focus { outline: none; border-color: #6366f1; }
.search-wrapper { position: relative; }
.search-icon { position: absolute; left: 0.65rem; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
.filter-input {
  padding: 0.45rem 0.75rem 0.45rem 2rem; border: 1px solid #e2e8f0;
  border-radius: 8px; background: #f8fafc; color: #334155;
  font-size: 0.82rem; font-weight: 500; width: 240px; font-family: inherit;
}
.filter-input:focus { outline: none; border-color: #6366f1; }
.filter-input::placeholder { color: #94a3b8; }
.records-count {
  font-size: 0.78rem; font-weight: 700; color: #64748b;
  background: #f1f5f9; padding: 0.2rem 0.7rem; border-radius: 9999px;
}
.business-active-badge {
  background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;
  padding: 0.2rem 0.75rem; border-radius: 9999px; font-size: 0.78rem; font-weight: 700;
}

/* ── Dashboard content ───────────────────────────── */
.dashboard-content { display: flex; flex-direction: column; gap: 1.25rem; }

/* ── Table ───────────────────────────────────────── */
.table-wrap { overflow-x: auto; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 0.5rem; }
.data-table { width: 100%; border-collapse: collapse; font-size: 0.82rem; }
.data-table th {
  background: #f1f5f9; font-size: 0.68rem; font-weight: 700; color: #64748b;
  text-transform: uppercase; letter-spacing: 0.05em; padding: 0.75rem 1rem;
  white-space: nowrap; position: sticky; top: 0; z-index: 10; text-align: left;
  border-bottom: 2px solid #f1f5f9;
}
.data-table td { padding: 0.8rem 1rem; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: middle; }
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: #f8fafc; }
.row-suspended td { opacity: 0.75; }
.row-archived td  { opacity: 0.65; background: #fafafa; }

.emp-name-cell { display: flex; align-items: center; gap: 0.625rem; font-weight: 600; color: #1e293b; }
.emp-avatar {
  width: 32px; height: 32px; border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #6366f1); color: white;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.72rem; font-weight: 700; flex-shrink: 0;
}
.emp-avatar.avatar-suspended { background: linear-gradient(135deg, #f59e0b, #d97706); }
.emp-avatar.avatar-archived  { background: linear-gradient(135deg, #94a3b8, #64748b); }

.mono  { font-variant-numeric: tabular-nums; font-family: 'SFMono-Regular','Consolas',monospace; font-size: 0.8rem; }
.muted { color: #64748b; }

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
.chip.type     { background: #f1f5f9; color: #475569; }
.chip.business { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.chip.role-employee { background: #d1fae5; color: #065f46; }
.chip.role-manager  { background: #fef3c7; color: #92400e; }
.chip.role-admin    { background: #fee2e2; color: #991b1b; }
.chip.status-active    { background: #d1fae5; color: #065f46; }
.chip.status-suspended { background: #fef3c7; color: #92400e; }
.chip.status-archived  { background: #f1f5f9; color: #475569; }

/* Reason cells */
.reason-cell {
  display: inline-block; max-width: 180px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  font-size: 0.8rem;
}

/* Row actions */
.row-actions { display: flex; gap: 0.3rem; }
.action-btn {
  width: 28px; height: 28px; border-radius: 6px; border: 1px solid #e2e8f0;
  background: white; color: #64748b; display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.15s;
}
.action-btn:hover          { background: #eff6ff; color: #4f46e5; border-color: #a5b4fc; }
.action-btn.warn:hover     { background: #fffbeb; color: #d97706; border-color: #fde68a; }
.action-btn.danger:hover   { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }
.action-btn.success:hover  { background: #f0fdf4; color: #059669; border-color: #6ee7b7; }

/* ── Loading / Empty ─────────────────────────────── */
.board-loading {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; gap: 0.875rem; padding: 4rem 2rem; color: #64748b;
}
.spinner {
  width: 40px; height: 40px; border: 3px solid #e2e8f0; border-top-color: #6366f1;
  border-radius: 50%; animation: spin 1s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.empty-state {
  text-align: center; padding: 4rem 2rem; display: flex;
  flex-direction: column; align-items: center; gap: 0.875rem;
}
.empty-icon-wrap {
  width: 64px; height: 64px; border-radius: 16px; background: #f1f5f9;
  border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: #94a3b8;
}
.empty-state h3 { margin: 0; font-size: 1.1rem; font-weight: 700; color: #1e293b; }
.empty-state p  { margin: 0; font-size: 0.875rem; color: #64748b; max-width: 320px; }

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
.modal.modal-sm { max-width: 480px; }

.modal-header {
  display: flex; justify-content: space-between; align-items: flex-start;
  padding: 1.5rem; border-bottom: 1px solid #e2e8f0; flex-shrink: 0;
  
}
.modal-header.warn-header    { background: linear-gradient(90deg, #92400e, #d97706); }
.modal-header.danger-header  { background: linear-gradient(90deg, #991b1b, #dc2626); }
.modal-header.success-header { background: linear-gradient(90deg, #065f46, #059669); }

.modal-title-row { display: flex; align-items: center; gap: 0.875rem; }
.modal-avatar {
  width: 42px; height: 42px; background: rgba(255,255,255,0.15);
  border: 1px solid rgba(255,255,255,0.25); border-radius: 12px;
  display: flex; align-items: center; justify-content: center; color: white; flex-shrink: 0;
}
.modal-header h2 { margin: 0; font-size: 1.1rem; font-weight: 700; color: white; }
.modal-subtitle  { font-size: 0.78rem; color: rgba(255,255,255,0.65); margin: 0.2rem 0 0; }
.close-btn {
  background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2);
  border-radius: 8px; width: 32px; height: 32px; display: flex; align-items: center;
  justify-content: center; cursor: pointer; color: white; transition: background 0.15s; flex-shrink: 0;
}
.close-btn:hover { background: rgba(255,255,255,0.2); }
.close-btn-dark { background: rgba(0,0,0,0.1); border-color: rgba(0,0,0,0.15); }

.modal-body { padding: 1.5rem; overflow-y: auto; flex-grow: 1; }
.modal-footer {
  display: flex; justify-content: flex-end; gap: 0.75rem;
  padding: 1.25rem 1.5rem; border-top: 1px solid #e2e8f0; background: #f8fafc; flex-shrink: 0;
}

/* Form elements */
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
textarea.form-control { resize: vertical; min-height: 80px; }
select.form-control {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 0.75rem center; padding-right: 2.25rem;
}
.disabled-input { background: #f1f5f9 !important; color: #94a3b8 !important; cursor: not-allowed; }
.form-error-bar {
  display: flex; align-items: center; gap: 0.5rem;
  background: #fee2e2; color: #991b1b; padding: 0.75rem 1rem;
  border-radius: 8px; font-size: 0.82rem; margin-top: 0.5rem;
}

/* Country selector */
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
  position: relative; padding: 0.75rem; border-bottom: 1px solid #f1f5f9;
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
  padding: 0.6rem 0.875rem; cursor: pointer; border-bottom: 1px solid #f8fafc; transition: background 0.12s;
}
.country-option:hover    { background: #f8fafc; }
.country-option.selected { background: #eff6ff; }
.check-icon { margin-left: auto; flex-shrink: 0; }
.country-empty { padding: 1rem; text-align: center; font-size: 0.82rem; color: #94a3b8; }

/* ── Responsive ──────────────────────────────────── */
@media (max-width: 900px) {
  .emp-mgmt-page { padding: 1rem 1rem 2rem; }
  .user-greeting  { flex-direction: column; align-items: flex-start; }
  .header-actions { width: 100%; }
  .btn-primary    { width: 100%; justify-content: center; }
  .controls-bar   { flex-direction: column; align-items: flex-start; }
  .filter-input   { width: 100%; }
  .pagination-bar { flex-direction: column; gap: 0.75rem; }
  .form-row       { grid-template-columns: 1fr; }
  .modal          { max-height: 95vh; }
  .tabs-bar       { width: 100%; }
  .tab-btn        { flex: 1; justify-content: center; }
}
</style>