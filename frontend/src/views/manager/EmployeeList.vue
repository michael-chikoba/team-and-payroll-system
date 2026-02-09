<template>
  <div class="employee-management">
    <div class="section-header text-center">
      <h1 class="section-title">👥 Team Management</h1>
      <p class="section-subtitle">Manage your team members and their details</p>
    </div>

    <!-- Stats Row -->
    <div class="row stats-row">
      <div class="col-4">
        <div class="stats-card primary">
          <div class="stats-card-icon primary">👥</div>
          <div class="stats-card-info">
            <div class="stats-card-value">{{ employees.length }}</div>
            <div class="stats-card-label">Total Staff</div>
          </div>
        </div>
      </div>
      <div class="col-4">
        <div class="stats-card success">
          <div class="stats-card-icon success">👨‍💼</div>
          <div class="stats-card-info">
            <div class="stats-card-value">{{ fullTimeCount }}</div>
            <div class="stats-card-label">Full Time</div>
          </div>
        </div>
      </div>
      <div class="col-4">
        <div class="stats-card warning">
          <div class="stats-card-icon warning">📅</div>
          <div class="stats-card-info">
            <div class="stats-card-value">{{ newHiresCount }}</div>
            <div class="stats-card-label">New Hires (30 days)</div>
          </div>
        </div>
      </div>
    </div>
   
    <!-- Authentication Check -->
    <div v-if="!authStore.isAuthenticated" class="alert alert-danger">
      <div class="alert-icon">🔒</div>
      <div class="alert-content">
        <h3>Authentication Required</h3>
        <p>Please log in to access employee management.</p>
      </div>
    </div>
   
    <!-- Permission Check -->
    <div v-else-if="!authStore.isManager" class="alert alert-danger">
      <div class="alert-icon">🚫</div>
      <div class="alert-content">
        <h3>Access Denied</h3>
        <p>Manager privileges required to view this page.</p>
      </div>
    </div>
   
    <!-- Loading State -->
    <div v-else-if="loading" class="loading text-center">
      <div class="loader">
        <div class="spinner"></div>
        <div class="loader-text">Loading team members...</div>
        <div class="loader-progress">
          <div class="progress-bar" :style="{ width: loadingProgress + '%' }"></div>
        </div>
      </div>
    </div>
   
    <!-- Error State -->
    <div v-else-if="error" class="alert alert-danger">
      <div class="alert-icon">⚠️</div>
      <div class="alert-content">
        <h3>Unable to Load Data</h3>
        <p>{{ error }}</p>
        <button @click="retryFetch" class="btn btn-primary">
          <span class="btn-icon">🔄</span>
          Try Again
        </button>
      </div>
    </div>
   
    <!-- Main Content -->
    <div v-else class="content-section">
      <!-- Search and Filters -->
      <div class="controls-section">
        <div class="search-bar d-flex align-items-center">
          <span class="search-icon">🔍</span>
          <input
            v-model="searchQuery"
            placeholder="Search employees by name, ID, or department..."
            class="form-control flex-1"
          />
          <span class="search-count text-muted">{{ filteredEmployees.length }} results</span>
        </div>
       
        <div class="filter-actions d-flex justify-content-between align-items-end gap-3">
          <div class="filters d-flex gap-3">
            <div class="form-group">
              <label class="filter-label">Department</label>
              <select v-model="selectedDepartment" class="form-select">
                <option value="">All Departments</option>
                <option v-for="dept in departments" :key="dept" :value="dept">{{ dept }}</option>
              </select>
            </div>
           
            <div class="form-group">
              <label class="filter-label">Employment Type</label>
              <select v-model="selectedType" class="form-select">
                <option value="">All Types</option>
                <option value="full_time">Full Time</option>
                <option value="part_time">Part Time</option>
                <option value="contract">Contract</option>
              </select>
            </div>
          </div>
         
          <div class="view-options d-flex gap-2">
            <button
              @click="toggleView"
              class="btn btn-outline"
              :title="gridView ? 'Switch to Table View' : 'Switch to Grid View'"
            >
              {{ gridView ? '📊 Table' : '🟦 Grid' }}
            </button>
            <button @click="exportData" class="btn btn-outline">
              <span class="btn-icon">📥</span>
              Export
            </button>
          </div>
        </div>
      </div>
     
      <!-- Grid View -->
      <div v-if="gridView" class="employee-grid row gap-3">
        <div v-for="employee in paginatedEmployees" :key="employee.id" class="col-4">
          <div class="card employee-card">
            <div class="card-header d-flex justify-content-between align-items-start">
              <div class="employee-avatar-large">
                {{ getInitials(employee.full_name || employee.user?.name) }}
              </div>
              <span class="badge" :class="getBadgeClass(employee.employment_type)">
                {{ formatEmploymentType(employee.employment_type) }}
              </span>
            </div>
           
            <div class="card-body">
              <h3 class="card-title">{{ employee.full_name || employee.user?.name }}</h3>
              <p class="card-subtitle">{{ employee.position }}</p>
              
              <div class="info-list">
                <div class="info-item">
                  <span class="icon">🏢</span>
                  <span>{{ employee.department }}</span>
                </div>
                <div class="info-item">
                  <span class="icon">📍</span>
                  <span>{{ employee.location || 'Remote' }}</span>
                </div>
                <div class="info-item">
                  <span class="icon">🆔</span>
                  <span class="text-mono">{{ employee.employee_id }}</span>
                </div>
              </div>
            </div>
           
            <div class="card-footer">
              <button @click="viewEmployeeDetails(employee)" class="btn btn-primary btn-block">
                View Profile
              </button>
            </div>
          </div>
        </div>
      </div>
     
      <!-- Table View (Cleaned Up) -->
      <div v-else class="table-responsive shadow-sm rounded">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th class="sortable ps-4" @click="sortBy('full_name')" style="width: 25%;">
                Name
                <span class="sort-icon" v-if="sortField === 'full_name'">
                  {{ sortDirection === 'asc' ? '↑' : '↓' }}
                </span>
              </th>
              <th style="width: 10%;">ID</th>
              <th class="sortable" @click="sortBy('position')" style="width: 20%;">
                Role
                <span class="sort-icon" v-if="sortField === 'position'">
                  {{ sortDirection === 'asc' ? '↑' : '↓' }}
                </span>
              </th>
              <th style="width: 15%;">Location</th>
              <th style="width: 10%;">Type</th>
              <th style="width: 10%;">Hired</th>
              <th style="width: 10%;" class="text-end pe-4">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="employee in paginatedEmployees" :key="employee.id">
              <td class="ps-4">
                <div class="d-flex align-items-center gap-3">
                  <div class="avatar-small">
                    {{ getInitials(employee.full_name || employee.user?.name) }}
                  </div>
                  <div class="d-flex flex-column">
                    <span class="fw-bold text-dark">
                      {{ employee.full_name || employee.user?.name }}
                    </span>
                    <span class="text-muted small">
                      {{ employee.email || employee.user?.email }}
                    </span>
                  </div>
                </div>
              </td>
              <!-- ID: Monospace font, no badge -->
              <td>
                <span class="text-mono text-secondary">{{ employee.employee_id }}</span>
              </td>
              <!-- Role: Position bold, Department smaller below -->
              <td>
                <div class="d-flex flex-column">
                  <span class="fw-medium">{{ employee.position }}</span>
                  <span class="text-muted small">{{ employee.department }}</span>
                </div>
              </td>
              <!-- Location: Icon + Text, no warning badge -->
              <td>
                <div class="d-flex align-items-center gap-1 text-secondary">
                  <span>📍</span>
                  <span>{{ employee.location || 'N/A' }}</span>
                </div>
              </td>
              <!-- Type: Status Badge (Semantic Colors) -->
              <td>
                <span class="badge rounded-pill" :class="getBadgeClass(employee.employment_type)">
                  {{ formatEmploymentType(employee.employment_type) }}
                </span>
              </td>
              <!-- Date: Standard Format -->
              <td class="text-nowrap text-secondary">
                {{ formatDateShort(employee.hire_date) }}
              </td>
              <!-- Actions -->
              <td class="text-end pe-4">
                <button 
                  @click="viewEmployeeDetails(employee)" 
                  class="btn btn-sm btn-light border" 
                  title="View Details"
                >
                  View
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
     
      <!-- Empty State -->
      <div v-if="filteredEmployees.length === 0" class="empty-state text-center py-5">
        <div class="empty-state-icon mb-3" style="font-size: 3rem; opacity: 0.5;">🔍</div>
        <h3 class="h5">No Employees Found</h3>
        <p class="text-muted">We couldn't find any team members matching your search.</p>
        <button @click="clearFilters" class="btn btn-outline-primary mt-2">
          Clear Filters
        </button>
      </div>
     
      <!-- Pagination -->
      <div v-if="filteredEmployees.length > itemsPerPage" class="pagination-container d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
        <div class="text-muted small">
          Showing <strong>{{ startIndex + 1 }}-{{ Math.min(endIndex, filteredEmployees.length) }}</strong> of <strong>{{ filteredEmployees.length }}</strong>
        </div>
        <div class="pagination-controls d-flex gap-1">
          <button
            @click="prevPage"
            :disabled="currentPage === 1"
            class="btn btn-sm btn-outline-secondary"
          >
            Previous
          </button>
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="goToPage(page)"
            class="btn btn-sm"
            :class="currentPage === page ? 'btn-primary' : 'btn-outline-secondary'"
          >
            {{ page }}
          </button>
          <button
            @click="nextPage"
            :disabled="currentPage === totalPages"
            class="btn btn-sm btn-outline-secondary"
          >
            Next
          </button>
        </div>
      </div>
    </div>
   
    <!-- Employee Details Modal -->
    <div v-if="selectedEmployee" class="modal-overlay" @click.self="selectedEmployee = null">
      <div class="modal-container">
        <div class="modal-header">
          <div class="d-flex align-items-center gap-3">
            <div class="employee-avatar-large">
              {{ getInitials(selectedEmployee.full_name || selectedEmployee.user?.name) }}
            </div>
            <div>
              <h3 class="m-0">{{ selectedEmployee.full_name || selectedEmployee.user?.name }}</h3>
              <p class="text-muted m-0">{{ selectedEmployee.position }}</p>
            </div>
          </div>
          <button @click="selectedEmployee = null" class="modal-close-btn">×</button>
        </div>
       
        <div class="modal-content-scroll">
          <div class="row g-3 mb-4">
            <div class="col-3">
              <div class="detail-box">
                <small class="text-muted">Employee ID</small>
                <div class="fw-bold text-mono">{{ selectedEmployee.employee_id }}</div>
              </div>
            </div>
            <div class="col-3">
              <div class="detail-box">
                <small class="text-muted">Department</small>
                <div class="fw-bold">{{ selectedEmployee.department }}</div>
              </div>
            </div>
            <div class="col-3">
              <div class="detail-box">
                <small class="text-muted">Type</small>
                <div>
                  <span class="badge" :class="getBadgeClass(selectedEmployee.employment_type)">
                    {{ formatEmploymentType(selectedEmployee.employment_type) }}
                  </span>
                </div>
              </div>
            </div>
            <div class="col-3">
              <div class="detail-box">
                <small class="text-muted">Joined</small>
                <div class="fw-bold">{{ formatDateShort(selectedEmployee.hire_date) }}</div>
              </div>
            </div>
          </div>

          <div class="info-section mb-4">
            <h5 class="border-bottom pb-2 mb-3">Contact Information</h5>
            <div class="row g-3">
              <div class="col-6">
                <label class="text-muted small">Email Address</label>
                <div>{{ selectedEmployee.email || selectedEmployee.user?.email || 'N/A' }}</div>
              </div>
              <div class="col-6">
                <label class="text-muted small">Phone Number</label>
                <div>{{ selectedEmployee.phone || 'N/A' }}</div>
              </div>
              <div class="col-12">
                <label class="text-muted small">Address</label>
                <div>{{ selectedEmployee.address || 'N/A' }}</div>
              </div>
            </div>
          </div>

          <div class="info-section">
            <h5 class="border-bottom pb-2 mb-3">Work Details</h5>
            <div class="row g-3">
              <div class="col-6">
                <label class="text-muted small">Reports To</label>
                <div>{{ selectedEmployee.reports_to || 'N/A' }}</div>
              </div>
              <div class="col-6">
                <label class="text-muted small">Work Location</label>
                <div>{{ selectedEmployee.location || 'N/A' }}</div>
              </div>
            </div>
          </div>
        </div>
       
        <div class="modal-footer">
          <button @click="selectedEmployee = null" class="btn btn-secondary me-2">Close</button>
          <button class="btn btn-primary">Edit Details</button>
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
      employees: [],
      loading: false,
      loadingProgress: 0,
      error: null,
      retryCount: 0,
      currentPage: 1,
      itemsPerPage: 10,
      selectedEmployee: null,
      searchQuery: '',
      selectedDepartment: '',
      selectedType: '',
      sortField: 'full_name',
      sortDirection: 'asc',
      gridView: false
    }
  },
  computed: {
    filteredEmployees() {
      let filtered = this.employees
     
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(emp => {
          const name = (emp.full_name || emp.user?.name || '').toLowerCase()
          const id = (emp.employee_id || '').toLowerCase()
          const dept = (emp.department || '').toLowerCase()
          const email = (emp.email || emp.user?.email || '').toLowerCase()
          
          return name.includes(query) ||
                 id.includes(query) ||
                 dept.includes(query) ||
                 email.includes(query)
        })
      }
     
      if (this.selectedDepartment) {
        filtered = filtered.filter(emp => emp.department === this.selectedDepartment)
      }
     
      if (this.selectedType) {
        filtered = filtered.filter(emp => emp.employment_type === this.selectedType)
      }
     
      filtered.sort((a, b) => {
        let aVal = a[this.sortField] || ''
        let bVal = b[this.sortField] || ''
       
        if (this.sortField === 'full_name') {
           aVal = a.full_name || a.user?.name || ''
           bVal = b.full_name || b.user?.name || ''
        }

        if (typeof aVal === 'string') aVal = aVal.toLowerCase()
        if (typeof bVal === 'string') bVal = bVal.toLowerCase()
       
        if (aVal < bVal) return this.sortDirection === 'asc' ? -1 : 1
        if (aVal > bVal) return this.sortDirection === 'asc' ? 1 : -1
        return 0
      })
     
      return filtered
    },
   
    paginatedEmployees() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredEmployees.slice(start, end)
    },
   
    totalPages() {
      return Math.ceil(this.filteredEmployees.length / this.itemsPerPage) || 1
    },
   
    startIndex() {
      return (this.currentPage - 1) * this.itemsPerPage
    },
   
    endIndex() {
      return Math.min(this.startIndex + this.itemsPerPage, this.filteredEmployees.length)
    },
   
    visiblePages() {
      const pages = []
      const maxVisible = 5
      let start = Math.max(1, this.currentPage - Math.floor(maxVisible / 2))
      let end = Math.min(this.totalPages, start + maxVisible - 1)
     
      if (end - start + 1 < maxVisible) {
        start = Math.max(1, end - maxVisible + 1)
      }
     
      for (let i = start; i <= end; i++) {
        pages.push(i)
      }
     
      return pages
    },
   
    departments() {
      const depts = new Set(this.employees.map(emp => emp.department).filter(Boolean))
      return Array.from(depts).sort()
    },
   
    fullTimeCount() {
      return this.employees.filter(emp => emp.employment_type === 'full_time').length
    },
   
    newHiresCount() {
      const thirtyDaysAgo = new Date()
      thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30)
     
      return this.employees.filter(emp => {
        if (!emp.hire_date) return false
        const hireDate = new Date(emp.hire_date)
        return hireDate >= thirtyDaysAgo
      }).length
    }
  },
  watch: {
    searchQuery() { this.currentPage = 1 },
    selectedDepartment() { this.currentPage = 1 },
    selectedType() { this.currentPage = 1 }
  },
  mounted() {
    this.initializeComponent()
  },
  methods: {
    initializeComponent() {
      if (!this.authStore.isAuthenticated) {
        this.error = 'Please log in to access employee management.'
        return
      }
      if (!this.authStore.isManager) {
        this.error = 'You do not have permission to access this page.'
        return
      }
      this.fetchEmployees()
    },
   
    async fetchEmployees(retry = false) {
      this.loading = true
      this.loadingProgress = 0
      this.error = null
      
      const progressInterval = setInterval(() => {
        if (this.loadingProgress < 90) this.loadingProgress += 10
      }, 100)
     
      try {
        const response = await axios.get('/api/manager/employees', {
          params: { manager_id: this.authStore.user?.id }
        })
        this.employees = response.data.data || response.data || []
        this.loadingProgress = 100
      } catch (err) {
        this.handleApiError(err)
      } finally {
        clearInterval(progressInterval)
        setTimeout(() => this.loading = false, 300)
      }
    },
   
    retryFetch() {
      this.retryCount++
      if (this.retryCount <= 3) {
        this.fetchEmployees(true)
      } else {
        this.error = 'Max retries exceeded. Please check your connection.'
      }
    },
   
    handleApiError(err) {
      let errorMsg = 'An unexpected error occurred.'
      if (err.code === 'ERR_NETWORK') {
        errorMsg = 'Network error. Please check your connection.'
      } else if (err.response?.status === 401) {
        errorMsg = 'Session expired.'
        this.authStore.clearAuth()
        this.$router.push({ name: 'login' })
      } else {
        errorMsg = err.response?.data?.message || errorMsg
      }
      this.error = errorMsg
    },
   
    viewEmployeeDetails(employee) {
      this.selectedEmployee = employee
    },
   
    nextPage() {
      if (this.currentPage < this.totalPages) this.currentPage++
    },
   
    prevPage() {
      if (this.currentPage > 1) this.currentPage--
    },
   
    goToPage(page) {
      this.currentPage = page
    },
   
    sortBy(field) {
      if (this.sortField === field) {
        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc'
      } else {
        this.sortField = field
        this.sortDirection = 'asc'
      }
    },
   
    toggleView() {
      this.gridView = !this.gridView
    },
   
    clearFilters() {
      this.searchQuery = ''
      this.selectedDepartment = ''
      this.selectedType = ''
    },
   
    exportData() {
      alert('Export feature coming soon!')
    },
   
    getInitials(name) {
      if (!name) return '??'
      return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2)
    },
   
    getBadgeClass(type) {
      const map = {
        full_time: 'bg-success text-white',
        part_time: 'bg-info text-dark',
        contract: 'bg-warning text-dark',
        intern: 'bg-secondary text-white'
      }
      return map[type] || 'bg-light text-dark'
    },
   
    formatDateShort(date) {
      if (!date) return '-'
      return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
      })
    },
   
    formatEmploymentType(type) {
      const types = {
        full_time: 'Full Time',
        part_time: 'Part Time',
        contract: 'Contract',
        intern: 'Intern'
      }
      return types[type] || type || 'Unknown'
    }
  }
}
</script>

<style scoped>
/* ============================================
   ENHANCED VISIBILITY STYLES - WHITE BACKGROUND
   ============================================ */

/* General Layout - WHITE BACKGROUND */
.employee-management {
  padding: 2rem;
  background-color: #ffffff;
  min-height: 100vh;
}

/* Section Header */
.section-header {
  margin-bottom: 2rem;
}

.section-title {
  font-size: 2.25rem;
  font-weight: 800;
  color: #1a202c;
  margin-bottom: 0.5rem;
  letter-spacing: -0.025em;
}

.section-subtitle {
  font-size: 1.1rem;
  color: #4b5563;
  font-weight: 500;
}

/* Stats Cards - Enhanced Visibility */
.stats-row {
  margin-bottom: 2.5rem;
}

.stats-card {
  background: white;
  border-radius: 16px;
  padding: 1.75rem;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  box-shadow: 0 4px 16px rgba(0,0,0,0.08);
  transition: all 0.3s;
  border: 2px solid #e5e7eb;
}

.stats-card:hover { 
  transform: translateY(-4px); 
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}

.stats-card-icon {
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 14px;
  font-size: 1.75rem;
  flex-shrink: 0;
}

.stats-card-icon.primary { 
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
  color: #1e40af;
  border: 2px solid #93c5fd;
}

.stats-card-icon.success { 
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
  color: #065f46;
  border: 2px solid #6ee7b7;
}

.stats-card-icon.warning { 
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  color: #92400e;
  border: 2px solid #fcd34d;
}

.stats-card-value { 
  font-size: 2.25rem;
  font-weight: 900;
  line-height: 1;
  color: #1a202c;
}

.stats-card-label { 
  color: #6b7280;
  font-size: 0.95rem;
  margin-top: 6px;
  font-weight: 600;
}

/* ============================================
   FILTERS & SEARCH - ENHANCED VISIBILITY
   ============================================ */
.controls-section {
  background: white;
  padding: 1.75rem;
  border-radius: 16px;
  margin-bottom: 2rem;
  box-shadow: 0 4px 16px rgba(0,0,0,0.08);
  border: 2px solid #e5e7eb;
}

.search-bar {
  background: #f9fafb;
  border: 2px solid #d1d5db;
  border-radius: 12px;
  padding: 0.75rem 1.25rem;
  margin-bottom: 1.25rem;
  transition: all 0.3s;
}

.search-bar:focus-within {
  border-color: #3b82f6;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.search-icon {
  font-size: 1.25rem;
  margin-right: 0.75rem;
}

.search-bar input {
  border: none;
  background: transparent;
  outline: none;
  font-size: 1rem;
  color: #1a202c;
  font-weight: 500;
  flex: 1;
}

.search-bar input::placeholder {
  color: #9ca3af;
  font-weight: 400;
}

.search-count {
  font-size: 0.9rem;
  color: #6b7280;
  font-weight: 600;
  margin-left: 1rem;
  white-space: nowrap;
}

/* CRITICAL: Enhanced filter labels */
.filter-label {
  font-size: 0.85rem;
  text-transform: uppercase;
  color: #1a202c;
  font-weight: 800;
  margin-bottom: 0.5rem;
  display: block;
  letter-spacing: 0.05em;
}

/* CRITICAL: Enhanced form select dropdowns */
.form-select {
  padding: 0.75rem 1rem;
  border: 2px solid #d1d5db;
  border-radius: 10px;
  font-size: 1rem;
  background: white;
  color: #1a202c;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.form-select:hover {
  border-color: #3b82f6;
}

.form-select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.form-select option {
  background: white;
  color: #1a202c;
  font-weight: 600;
  padding: 0.75rem;
}

/* ============================================
   TABLE - MAXIMUM VISIBILITY WITH WHITE BACKGROUND
   ============================================ */
.table-responsive {
  background: white;
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 4px 16px rgba(0,0,0,0.08);
  border: 2px solid #e5e7eb;
}

.table {
  margin-bottom: 0;
  width: 100%;
}

/* CRITICAL: Enhanced table header */
.table thead th {
  background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
  border-bottom: 3px solid #d1d5db;
  color: #1a202c;
  font-weight: 800;
  text-transform: uppercase;
  font-size: 0.85rem;
  padding: 1.25rem 1rem;
  letter-spacing: 0.05em;
  white-space: nowrap;
}

/* CRITICAL: Enhanced table body */
.table tbody td {
  padding: 1.25rem 1rem;
  border-bottom: 1px solid #e5e7eb;
  background: white;
  color: #1a202c;
  font-size: 0.95rem;
  font-weight: 500;
  vertical-align: middle;
}

.table tbody tr:last-child td { 
  border-bottom: none;
}

.table tbody tr {
  transition: all 0.2s;
}

.table tbody tr:hover {
  background-color: #f9fafb;
  box-shadow: inset 0 0 0 2px #e5e7eb;
}

/* CRITICAL: Enhanced text visibility in table */
.fw-bold {
  font-weight: 800 !important;
  color: #1a202c !important;
}

.fw-medium {
  font-weight: 700 !important;
  color: #374151 !important;
}

.text-dark {
  color: #1a202c !important;
  font-weight: 700 !important;
}

.text-secondary {
  color: #4b5563 !important;
  font-weight: 600 !important;
}

.text-muted {
  color: #6b7280 !important;
  font-weight: 500 !important;
}

.small {
  font-size: 0.9rem !important;
}

/* CRITICAL: Enhanced avatar */
.avatar-small {
  width: 44px;
  height: 44px;
  background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
  color: #1a202c;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
  font-size: 1rem;
  flex-shrink: 0;
  border: 2px solid white;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* CRITICAL: Enhanced monospace text */
.text-mono {
  font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
  font-weight: 700 !important;
  color: #4b5563 !important;
  background: #f3f4f6;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  font-size: 0.9rem;
}

/* CRITICAL: Sortable columns */
.sortable {
  cursor: pointer;
  user-select: none;
  transition: all 0.2s;
  position: relative;
}

.sortable:hover {
  color: #3b82f6 !important;
  background: rgba(59, 130, 246, 0.05);
}

.sort-icon {
  margin-left: 0.5rem;
  font-weight: 900;
  color: #3b82f6;
}

/* ============================================
   BADGES - ENHANCED VISIBILITY
   ============================================ */
.badge {
  padding: 0.5em 0.95em;
  font-weight: 700;
  font-size: 0.8rem;
  border-radius: 20px;
  letter-spacing: 0.025em;
  text-transform: uppercase;
}

.bg-success {
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%) !important;
  color: #065f46 !important;
  border: 2px solid #6ee7b7 !important;
}

.bg-info {
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%) !important;
  color: #1e40af !important;
  border: 2px solid #93c5fd !important;
}

.bg-warning {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%) !important;
  color: #92400e !important;
  border: 2px solid #fcd34d !important;
}

.bg-secondary {
  background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%) !important;
  color: #1f2937 !important;
  border: 2px solid #d1d5db !important;
}

/* ============================================
   BUTTONS - ENHANCED VISIBILITY
   ============================================ */
.btn {
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s;
  border: none;
  font-size: 0.95rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.btn-primary {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-primary:hover {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
}

.btn-outline {
  background: white;
  color: #3b82f6;
  border: 2px solid #3b82f6;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
}

.btn-outline:hover {
  background: #3b82f6;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.85rem;
}

.btn-light {
  background: white;
  color: #1a202c;
  border: 2px solid #d1d5db;
  font-weight: 700;
}

.btn-light:hover {
  background: #f9fafb;
  border-color: #3b82f6;
  color: #3b82f6;
}

.btn-outline-secondary {
  background: white;
  color: #6b7280;
  border: 2px solid #d1d5db;
  font-weight: 700;
}

.btn-outline-secondary:hover:not(:disabled) {
  background: #f9fafb;
  border-color: #3b82f6;
  color: #3b82f6;
}

.btn-outline-secondary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ============================================
   GRID CARDS
   ============================================ */
.employee-card {
  border: 2px solid #e5e7eb;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.06);
  transition: all 0.3s;
  background: white;
}

.employee-card:hover {
  transform: translateY(-6px);
  box-shadow: 0 12px 24px rgba(0,0,0,0.12);
  border-color: #3b82f6;
}

.employee-avatar-large {
  width: 72px;
  height: 72px;
  background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
  border-radius: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  font-weight: 900;
  color: #1a202c;
  border: 3px solid white;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.card-title {
  font-size: 1.25rem;
  font-weight: 800;
  color: #1a202c;
  margin: 0.75rem 0 0.25rem;
}

.card-subtitle {
  color: #6b7280;
  font-size: 0.95rem;
  font-weight: 600;
  margin: 0;
}

.info-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-top: 1.25rem;
}

.info-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: #4b5563;
  font-size: 0.95rem;
  font-weight: 600;
}

.info-item .icon {
  font-size: 1.25rem;
}

/* ============================================
   MODAL - ENHANCED VISIBILITY
   ============================================ */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  backdrop-filter: blur(4px);
}

.modal-container {
  background: white;
  width: 750px;
  max-width: 95%;
  border-radius: 20px;
  box-shadow: 0 25px 50px rgba(0,0,0,0.25);
  display: flex;
  flex-direction: column;
  max-height: 90vh;
  border: 2px solid #e5e7eb;
}

.modal-header {
  padding: 2rem;
  border-bottom: 2px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: linear-gradient(135deg, #f9fafb 0%, white 100%);
}

.modal-header h3 {
  font-size: 1.5rem;
  font-weight: 800;
  color: #1a202c;
}

.modal-close-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: 2px solid #e5e7eb;
  background: white;
  color: #6b7280;
  font-size: 1.75rem;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 300;
  line-height: 1;
}

.modal-close-btn:hover {
  background: #fee2e2;
  color: #dc2626;
  border-color: #fca5a5;
  transform: rotate(90deg);
}

.modal-content-scroll {
  padding: 2rem;
  overflow-y: auto;
  background: white;
}

.modal-footer {
  padding: 1.5rem 2rem;
  border-top: 2px solid #e5e7eb;
  background: #f9fafb;
  border-radius: 0 0 20px 20px;
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

.detail-box {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 12px;
  text-align: center;
  height: 100%;
  border: 2px solid #e5e7eb;
}

.detail-box small {
  font-size: 0.75rem;
  text-transform: uppercase;
  color: #6b7280;
  font-weight: 700;
  letter-spacing: 0.05em;
  display: block;
  margin-bottom: 0.5rem;
}

.detail-box .fw-bold {
  font-size: 1.1rem;
  color: #1a202c;
  font-weight: 800;
}

.info-section h5 {
  font-size: 1.1rem;
  font-weight: 800;
  color: #1a202c;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.info-section label {
  font-size: 0.75rem;
  text-transform: uppercase;
  color: #6b7280;
  font-weight: 700;
  letter-spacing: 0.05em;
  display: block;
  margin-bottom: 0.5rem;
}

.info-section div:not(.row) {
  font-size: 1rem;
  color: #1a202c;
  font-weight: 600;
}

/* ============================================
   ALERTS
   ============================================ */
.alert {
  padding: 1.75rem;
  border-radius: 16px;
  margin-bottom: 2rem;
  display: flex;
  align-items: flex-start;
  gap: 1.5rem;
  border: 2px solid;
  box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}

.alert-danger {
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
  color: #991b1b;
  border-color: #f87171;
}

.alert-icon {
  font-size: 2.5rem;
  flex-shrink: 0;
}

.alert-content h3 {
  font-size: 1.3rem;
  font-weight: 800;
  margin: 0 0 0.5rem;
  color: #7f1d1d;
}

.alert-content p {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: #991b1b;
}

/* ============================================
   PAGINATION - ENHANCED VISIBILITY
   ============================================ */
.pagination-container {
  background: white;
  padding: 1.25rem;
  border-radius: 12px;
  border: 2px solid #e5e7eb;
}

.pagination-container .text-muted {
  font-size: 0.95rem;
  color: #4b5563 !important;
  font-weight: 700 !important;
}

.pagination-container strong {
  color: #1a202c;
  font-weight: 900;
}

/* ============================================
   LOADING STATE
   ============================================ */
.loading {
  padding: 4rem 2rem;
  background: white;
  border-radius: 16px;
  border: 2px solid #e5e7eb;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 5px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1.5rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.loader-text {
  font-size: 1.1rem;
  color: #1a202c;
  font-weight: 700;
  margin-bottom: 1rem;
}

.loader-progress {
  width: 100%;
  max-width: 300px;
  height: 8px;
  background: #e5e7eb;
  border-radius: 10px;
  overflow: hidden;
  margin: 0 auto;
}

.progress-bar {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
  border-radius: 10px;
  transition: width 0.3s;
}

/* ============================================
   EMPTY STATE
   ============================================ */
.empty-state {
  background: white;
  padding: 4rem 2rem;
  border-radius: 16px;
  border: 2px dashed #d1d5db;
}

.empty-state h3 {
  font-size: 1.3rem;
  font-weight: 800;
  color: #1a202c;
  margin: 1rem 0 0.5rem;
}

.empty-state p {
  font-size: 1rem;
  color: #6b7280;
  font-weight: 500;
}

/* ============================================
   UTILITIES
   ============================================ */
.shadow-sm {
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.rounded {
  border-radius: 12px;
}

.rounded-pill {
  border-radius: 50rem;
}

.gap-1 { gap: 0.25rem; }
.gap-2 { gap: 0.5rem; }
.gap-3 { gap: 1rem; }

.d-flex { display: flex; }
.flex-1 { flex: 1; }
.flex-column { flex-direction: column; }
.align-items-center { align-items: center; }
.align-items-start { align-items: flex-start; }
.align-items-end { align-items: flex-end; }
.justify-content-between { justify-content: space-between; }
.justify-content-end { justify-content: flex-end; }
.text-center { text-align: center; }
.text-end { text-align: end; }
.text-nowrap { white-space: nowrap; }

.row {
  display: flex;
  flex-wrap: wrap;
  margin: 0 -0.5rem;
}

.col-3 { flex: 0 0 25%; max-width: 25%; padding: 0 0.5rem; }
.col-4 { flex: 0 0 33.333%; max-width: 33.333%; padding: 0 0.5rem; }
.col-6 { flex: 0 0 50%; max-width: 50%; padding: 0 0.5rem; }
.col-12 { flex: 0 0 100%; max-width: 100%; padding: 0 0.5rem; }

.g-3 > * { padding: 0.75rem; }

.m-0 { margin: 0; }
.mb-3 { margin-bottom: 1rem; }
.mb-4 { margin-bottom: 1.5rem; }
.mt-2 { margin-top: 0.5rem; }
.mt-4 { margin-top: 1.5rem; }
.me-2 { margin-right: 0.5rem; }
.pb-2 { padding-bottom: 0.5rem; }
.ps-4 { padding-left: 1.5rem; }
.pe-4 { padding-right: 1.5rem; }
.py-5 { padding-top: 3rem; padding-bottom: 3rem; }
.pt-3 { padding-top: 1rem; }

.border-top { border-top: 2px solid #e5e7eb; }
.border-bottom { border-bottom: 2px solid #e5e7eb; }
.border { border: 2px solid #e5e7eb; }

/* ============================================
   RESPONSIVE DESIGN
   ============================================ */
@media (max-width: 768px) {
  .employee-management {
    padding: 1rem;
  }
  
  .section-title {
    font-size: 1.75rem;
  }
  
  .stats-card {
    flex-direction: column;
    text-align: center;
  }
  
  .filter-actions {
    flex-direction: column;
    align-items: stretch !important;
  }
  
  .filters {
    flex-direction: column;
    width: 100%;
  }
  
  .form-group {
    width: 100%;
  }
  
  .view-options {
    width: 100%;
    justify-content: space-between;
  }
  
  .table thead th {
    font-size: 0.75rem;
    padding: 1rem 0.5rem;
  }
  
  .table tbody td {
    padding: 1rem 0.5rem;
    font-size: 0.85rem;
  }
  
  .col-4 {
    flex: 0 0 100%;
    max-width: 100%;
  }
  
  .modal-container {
    width: 100%;
    max-width: 100%;
    height: 100%;
    max-height: 100%;
    border-radius: 0;
  }
  
  .col-3, .col-6 {
    flex: 0 0 100%;
    max-width: 100%;
  }
}
</style>