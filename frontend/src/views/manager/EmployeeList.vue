<template>
  <div class="employee-management">
    <div class="page-header">
      <div class="header-content">
        <h1>👥 Team Management</h1>
        <p class="subtitle">Manage your team members and their details</p>
      </div>
      <div class="header-stats">
        <div class="stat-card">
          <div class="stat-icon">👥</div>
          <div class="stat-info">
            <span class="stat-value">{{ employees.length }}</span>
            <span class="stat-label">Total Staff</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">👨‍💼</div>
          <div class="stat-info">
            <span class="stat-value">{{ fullTimeCount }}</span>
            <span class="stat-label">Full Time</span>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">📅</div>
          <div class="stat-info">
            <span class="stat-value">{{ newHiresCount }}</span>
            <span class="stat-label">New Hires (30 days)</span>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Authentication Check -->
    <div v-if="!authStore.isAuthenticated" class="error-message">
      <div class="error-icon">🔒</div>
      <div>
        <h3>Authentication Required</h3>
        <p>Please log in to access employee management.</p>
      </div>
    </div>
    
    <!-- Permission Check -->
    <div v-else-if="!authStore.isManager" class="error-message">
      <div class="error-icon">🚫</div>
      <div>
        <h3>Access Denied</h3>
        <p>Manager privileges required to view this page.</p>
      </div>
    </div>
    
    <!-- Loading State -->
    <div v-else-if="loading" class="loading-container">
      <div class="loader">
        <div class="loader-spinner"></div>
        <div class="loader-text">Loading team members...</div>
        <div class="loader-progress">
          <div class="progress-bar" :style="{ width: loadingProgress + '%' }"></div>
        </div>
      </div>
    </div>
    
    <!-- Error State -->
    <div v-else-if="error" class="error-state">
      <div class="error-content">
        <div class="error-icon">⚠️</div>
        <div class="error-details">
          <h3>Unable to Load Data</h3>
          <p>{{ error }}</p>
          <button @click="retryFetch" class="retry-btn">
            <span class="btn-icon">🔄</span>
            Try Again
          </button>
        </div>
      </div>
    </div>
    
    <!-- Main Content -->
    <div v-else class="main-content">
      <!-- Search and Filters -->
      <div class="controls-section">
        <div class="search-bar">
          <span class="search-icon">🔍</span>
          <input 
            v-model="searchQuery" 
            placeholder="Search employees by name, ID, or department..." 
            class="search-input"
          />
          <span class="search-count">{{ filteredEmployees.length }} results</span>
        </div>
        
        <div class="filter-actions">
          <div class="filters">
            <select v-model="selectedDepartment" class="filter-select">
              <option value="">All Departments</option>
              <option v-for="dept in departments" :key="dept" :value="dept">{{ dept }}</option>
            </select>
            
            <select v-model="selectedType" class="filter-select">
              <option value="">All Types</option>
              <option value="full_time">Full Time</option>
              <option value="part_time">Part Time</option>
              <option value="contract">Contract</option>
            </select>
          </div>
          
          <div class="view-options">
            <button 
              @click="toggleView" 
              class="view-toggle"
              :title="gridView ? 'Switch to Table View' : 'Switch to Grid View'"
            >
              {{ gridView ? '📊 Table' : '🟦 Grid' }}
            </button>
            <button @click="exportData" class="export-btn">
              <span class="btn-icon">📥</span>
              Export
            </button>
          </div>
        </div>
      </div>
      
      <!-- Grid View -->
      <div v-if="gridView" class="employee-grid">
        <div v-for="employee in paginatedEmployees" :key="employee.id" class="employee-card">
          <div class="card-header">
            <div class="employee-avatar-large">
              {{ getInitials(employee.full_name || employee.user?.name) }}
            </div>
            <div class="employee-status" :class="getStatusClass(employee)">
              {{ getStatusText(employee) }}
            </div>
          </div>
          
          <div class="card-body">
            <h3 class="employee-name">{{ employee.full_name || employee.user?.name }}</h3>
            <p class="employee-title">{{ employee.position }}</p>
            <p class="employee-dept">{{ employee.department }}</p>
            
            <div class="employee-details">
              <div class="detail-item">
                <span class="detail-label">ID</span>
                <span class="detail-value">{{ employee.employee_id }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Type</span>
                <span class="detail-value badge">{{ formatEmploymentType(employee.employment_type) }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Location</span>
                <span class="detail-value">{{ employee.location || 'N/A' }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Hired</span>
                <span class="detail-value">{{ formatDateShort(employee.hire_date) }}</span>
              </div>
            </div>
          </div>
          
          <div class="card-footer">
            <button @click="viewEmployeeDetails(employee)" class="card-action view-btn">
              <span class="action-icon">👁️</span>
              View Details
            </button>
            <button class="card-action more-btn">
              <span class="action-icon">⋯</span>
            </button>
          </div>
        </div>
      </div>
      
      <!-- Table View -->
      <div v-else class="table-container">
        <div class="table-wrapper">
          <table class="employees-table">
            <thead>
              <tr>
                <th class="sortable" @click="sortBy('full_name')">
                  <span>Employee</span>
                  <span class="sort-icon" v-if="sortField === 'full_name'">
                    {{ sortDirection === 'asc' ? '↑' : '↓' }}
                  </span>
                </th>
                <th>ID</th>
                <th class="sortable" @click="sortBy('position')">
                  <span>Position</span>
                  <span class="sort-icon" v-if="sortField === 'position'">
                    {{ sortDirection === 'asc' ? '↑' : '↓' }}
                  </span>
                </th>
                <th>Department</th>
                <th>Location</th>
                <th>Type</th>
                <th>Hire Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="employee in paginatedEmployees" :key="employee.id">
                <td>
                  <div class="table-employee">
                    <div class="table-avatar">
                      {{ getInitials(employee.full_name || employee.user?.name) }}
                    </div>
                    <div class="table-employee-info">
                      <div class="employee-name">{{ employee.full_name || employee.user?.name }}</div>
                      <div class="employee-email">{{ employee.email || employee.user?.email }}</div>
                    </div>
                  </div>
                </td>
                <td><span class="employee-id">{{ employee.employee_id }}</span></td>
                <td>{{ employee.position }}</td>
                <td>
                  <span class="department-tag">{{ employee.department }}</span>
                </td>
                <td>
                  <span class="location-tag">{{ employee.location || 'N/A' }}</span>
                </td>
                <td>
                  <span class="employment-badge" :class="employee.employment_type">
                    {{ formatEmploymentType(employee.employment_type) }}
                  </span>
                </td>
                <td>{{ formatDate(employee.hire_date) }}</td>
                <td>
                  <div class="action-buttons">
                    <button @click="viewEmployeeDetails(employee)" class="action-btn view-btn" title="View Details">
                      <span class="btn-icon">👁️</span>
                    </button>
                    <button class="action-btn more-btn" title="More Actions">
                      <span class="btn-icon">⋯</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Empty State -->
      <div v-if="filteredEmployees.length === 0" class="empty-state">
        <div class="empty-illustration">👥</div>
        <h3>No Employees Found</h3>
        <p>No team members match your search criteria.</p>
        <button @click="clearFilters" class="clear-filters-btn">
          Clear Filters
        </button>
      </div>
      
      <!-- Pagination -->
      <div v-if="filteredEmployees.length > itemsPerPage" class="pagination-container">
        <div class="pagination-info">
          Showing {{ startIndex + 1 }}-{{ Math.min(endIndex, filteredEmployees.length) }} of {{ filteredEmployees.length }}
        </div>
        <div class="pagination-controls">
          <button 
            @click="prevPage" 
            :disabled="currentPage === 1" 
            class="pagination-btn prev-btn"
          >
            <span class="btn-icon">←</span>
            Previous
          </button>
          
          <div class="page-numbers">
            <button 
              v-for="page in visiblePages" 
              :key="page" 
              @click="goToPage(page)"
              :class="['page-btn', { active: currentPage === page }]"
            >
              {{ page }}
            </button>
          </div>
          
          <button 
            @click="nextPage" 
            :disabled="currentPage === totalPages" 
            class="pagination-btn next-btn"
          >
            Next
            <span class="btn-icon">→</span>
          </button>
        </div>
      </div>
    </div>
    
    <!-- Employee Details Modal -->
    <div v-if="selectedEmployee" class="modal-overlay" @click.self="selectedEmployee = null">
      <div class="modal-card">
        <div class="modal-header">
          <div class="modal-title">
            <div class="modal-avatar">
              {{ getInitials(selectedEmployee.full_name || selectedEmployee.user?.name) }}
            </div>
            <div>
              <h2>{{ selectedEmployee.full_name || selectedEmployee.user?.name }}</h2>
              <p class="modal-subtitle">{{ selectedEmployee.position }} • {{ selectedEmployee.department }}</p>
            </div>
          </div>
          <button @click="selectedEmployee = null" class="close-btn">
            <span class="close-icon">×</span>
          </button>
        </div>
        
        <div class="modal-body">
          <!-- Quick Stats -->
          <div class="modal-stats">
            <div class="modal-stat">
              <span class="stat-label">Employee ID</span>
              <span class="stat-value">{{ selectedEmployee.employee_id }}</span>
            </div>
            <div class="modal-stat">
              <span class="stat-label">Type</span>
              <span class="stat-value badge">{{ formatEmploymentType(selectedEmployee.employment_type) }}</span>
            </div>
            <div class="modal-stat">
              <span class="stat-label">Location</span>
              <span class="stat-value">{{ selectedEmployee.location || 'N/A' }}</span>
            </div>
            <div class="modal-stat">
              <span class="stat-label">Hire Date</span>
              <span class="stat-value">{{ formatDate(selectedEmployee.hire_date) }}</span>
            </div>
          </div>
          
          <div class="modal-sections">
            <!-- Personal Information -->
            <div class="modal-section">
              <h3 class="section-title">
                <span class="section-icon">👤</span>
                Personal Information
              </h3>
              <div class="info-grid">
                <div class="info-item">
                  <label>Email</label>
                  <p>{{ selectedEmployee.email || selectedEmployee.user?.email || 'N/A' }}</p>
                </div>
                <div class="info-item">
                  <label>Phone</label>
                  <p>{{ selectedEmployee.phone || 'N/A' }}</p>
                </div>
                <div class="info-item">
                  <label>Date of Birth</label>
                  <p>{{ formatDate(selectedEmployee.date_of_birth) || 'N/A' }}</p>
                </div>
                <div class="info-item">
                  <label>National ID</label>
                  <p>{{ selectedEmployee.national_id || 'N/A' }}</p>
                </div>
              </div>
            </div>
            
            <!-- Address & Contact -->
            <div class="modal-section">
              <h3 class="section-title">
                <span class="section-icon">🏠</span>
                Address & Contact
              </h3>
              <div class="info-grid">
                <div class="info-item full-width">
                  <label>Address</label>
                  <p>{{ selectedEmployee.address || 'N/A' }}</p>
                </div>
                <div class="info-item">
                  <label>Emergency Contact</label>
                  <p>{{ selectedEmployee.emergency_contact || 'N/A' }}</p>
                </div>
              </div>
            </div>
            
            <!-- Work Information -->
            <div class="modal-section">
              <h3 class="section-title">
                <span class="section-icon">💼</span>
                Work Information
              </h3>
              <div class="info-grid">
                <div class="info-item">
                  <label>Reports To</label>
                  <p>{{ selectedEmployee.reports_to || 'N/A' }}</p>
                </div>
                <div class="info-item">
                  <label>Work Schedule</label>
                  <p>{{ selectedEmployee.work_schedule || 'Standard Business Hours' }}</p>
                </div>
                <div class="info-item">
                  <label>Work Location</label>
                  <p>{{ selectedEmployee.work_location || selectedEmployee.location || 'N/A' }}</p>
                </div>
                <div class="info-item">
                  <label>Work Phone</label>
                  <p>{{ selectedEmployee.work_phone || 'N/A' }}</p>
                </div>
              </div>
            </div>
            
            <!-- Additional Information -->
            <div class="modal-section">
              <h3 class="section-title">
                <span class="section-icon">📝</span>
                Additional Information
              </h3>
              <div class="notes-section">
                <p>{{ selectedEmployee.notes || 'No additional notes available' }}</p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button @click="selectedEmployee = null" class="btn-secondary">
            Close
          </button>
          <button class="btn-primary">
            <span class="btn-icon">📧</span>
            Contact Employee
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
      itemsPerPage: 12,
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
      
      // Apply search filter
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(emp => {
          const name = (emp.full_name || emp.user?.name || '').toLowerCase()
          const id = (emp.employee_id || '').toLowerCase()
          const dept = (emp.department || '').toLowerCase()
          const email = (emp.email || emp.user?.email || '').toLowerCase()
          const position = (emp.position || '').toLowerCase()
          const location = (emp.location || '').toLowerCase()
          
          return name.includes(query) || 
                 id.includes(query) || 
                 dept.includes(query) ||
                 email.includes(query) ||
                 position.includes(query) ||
                 location.includes(query)
        })
      }
      
      // Apply department filter
      if (this.selectedDepartment) {
        filtered = filtered.filter(emp => emp.department === this.selectedDepartment)
      }
      
      // Apply employment type filter
      if (this.selectedType) {
        filtered = filtered.filter(emp => emp.employment_type === this.selectedType)
      }
      
      // Apply sorting
      filtered.sort((a, b) => {
        let aVal = a[this.sortField] || ''
        let bVal = b[this.sortField] || ''
        
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
      return Math.ceil(this.filteredEmployees.length / this.itemsPerPage)
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
    searchQuery() {
      this.currentPage = 1
    },
    selectedDepartment() {
      this.currentPage = 1
    },
    selectedType() {
      this.currentPage = 1
    }
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
     
      // Simulate loading progress
      const progressInterval = setInterval(() => {
        if (this.loadingProgress < 90) {
          this.loadingProgress += 10
        }
      }, 100)
     
      try {
        const response = await axios.get('/api/manager/employees', {
          params: { manager_id: this.authStore.user?.id }
        })
       
        this.employees = response.data.data || response.data || []
        this.loadingProgress = 100
      } catch (err) {
        console.error('Fetch error:', err)
        this.handleApiError(err)
      } finally {
        clearInterval(progressInterval)
        setTimeout(() => {
          this.loading = false
        }, 300)
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
      if (err.code === 'ERR_NETWORK' || err.message.includes('Network Error')) {
        errorMsg = 'Network error. Please check your connection.'
      } else if (err.response?.status === 401) {
        errorMsg = 'Session expired. Please log in again.'
        this.authStore.clearAuth()
        this.$router.push({ name: 'login' })
      } else if (err.response?.status === 403) {
        errorMsg = 'Insufficient permissions.'
      } else {
        errorMsg = err.response?.data?.message || errorMsg
      }
      this.error = errorMsg
    },
   
    viewEmployeeDetails(employee) {
      this.selectedEmployee = employee
    },
   
    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++
      }
    },
   
    prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--
      }
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
      // Implement export functionality
      alert('Export feature coming soon!')
    },
   
    getInitials(name) {
      if (!name) return '??'
      return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2)
    },
   
    getStatusClass(employee) {
      return employee.employment_type || 'unknown'
    },
   
    getStatusText(employee) {
      const statusMap = {
        full_time: 'Full Time',
        part_time: 'Part Time',
        contract: 'Contract'
      }
      return statusMap[employee.employment_type] || 'Unknown'
    },
   
    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    },
   
    formatDateShort(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: '2-digit'
      })
    },
   
    formatEmploymentType(type) {
      const types = {
        full_time: 'Full Time',
        part_time: 'Part Time',
        contract: 'Contract'
      }
      return types[type] || type || 'N/A'
    }
  }
}
</script>

<style scoped>
.employee-management {
  --primary-color: #4f46e5;
  --primary-light: #818cf8;
  --primary-dark: #3730a3;
  --success-color: #10b981;
  --warning-color: #f59e0b;
  --danger-color: #ef4444;
  --info-color: #3b82f6;
  
  --bg-primary: #ffffff;
  --bg-secondary: #f8fafc;
  --bg-tertiary: #f1f5f9;
  
  --text-primary: #0f172a;
  --text-secondary: #475569;
  --text-tertiary: #64748b;
  --text-light: #94a3b8;
  
  --border-color: #e2e8f0;
  --border-light: #f1f5f9;
  
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  
  --radius-sm: 8px;
  --radius: 12px;
  --radius-lg: 16px;
  --radius-xl: 20px;
  
  --transition: all 0.2s ease;
  
  min-height: 100vh;
  background: linear-gradient(135deg, var(--bg-secondary) 0%, #f1f5f9 100%);
  padding: 2rem;
}

/* Page Header */
.page-header {
  margin-bottom: 2rem;
  animation: fadeIn 0.5s ease;
}

.header-content {
  margin-bottom: 2rem;
}

.header-content h1 {
  font-size: 2.5rem;
  font-weight: 800;
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.subtitle {
  color: var(--text-secondary);
  font-size: 1.1rem;
  margin: 0;
}

.header-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
  margin-top: 2rem;
}

.stat-card {
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: var(--shadow);
  transition: var(--transition);
  border: 1px solid transparent;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
  border-color: var(--primary-light);
}

.stat-icon {
  font-size: 2.5rem;
  width: 64px;
  height: 64px;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
  color: white;
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
}

.stat-info {
  display: flex;
  flex-direction: column;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1;
}

.stat-label {
  font-size: 0.9rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
}

/* Error States */
.error-message,
.error-state {
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  padding: 2rem;
  box-shadow: var(--shadow);
  margin: 2rem auto;
  max-width: 600px;
  text-align: center;
  animation: fadeIn 0.5s ease;
}

.error-message {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  text-align: left;
}

.error-icon {
  font-size: 3rem;
  color: var(--danger-color);
}

.error-message h3,
.error-state h3 {
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
  font-size: 1.5rem;
}

.error-message p,
.error-state p {
  color: var(--text-secondary);
  margin: 0 0 1.5rem 0;
}

.retry-btn {
  background: var(--primary-color);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: var(--radius);
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.retry-btn:hover {
  background: var(--primary-dark);
  transform: translateY(-1px);
}

/* Loading State */
.loading-container {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 400px;
}

.loader {
  text-align: center;
  max-width: 400px;
  width: 100%;
}

.loader-spinner {
  width: 60px;
  height: 60px;
  border: 3px solid var(--border-color);
  border-top-color: var(--primary-color);
  border-radius: 50%;
  margin: 0 auto 1.5rem;
  animation: spin 1s linear infinite;
}

.loader-text {
  color: var(--text-secondary);
  font-size: 1.1rem;
  margin-bottom: 1rem;
}

.loader-progress {
  height: 6px;
  background: var(--border-color);
  border-radius: 3px;
  overflow: hidden;
}

.progress-bar {
  height: 100%;
  background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
  border-radius: 3px;
  transition: width 0.3s ease;
}

/* Controls Section */
.controls-section {
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: var(--shadow);
}

.search-bar {
  display: flex;
  align-items: center;
  background: var(--bg-tertiary);
  border-radius: var(--radius);
  padding: 0.5rem 1rem;
  margin-bottom: 1.5rem;
}

.search-icon {
  color: var(--text-tertiary);
  font-size: 1.2rem;
  margin-right: 0.75rem;
}

.search-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  font-size: 1rem;
  color: var(--text-primary);
  padding: 0.75rem 0;
}

.search-input::placeholder {
  color: var(--text-light);
}

.search-count {
  color: var(--text-secondary);
  font-size: 0.9rem;
  white-space: nowrap;
  margin-left: 1rem;
}

.filter-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.filters {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.filter-select {
  background: var(--bg-tertiary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  padding: 0.75rem 1rem;
  color: var(--text-primary);
  font-size: 0.95rem;
  min-width: 160px;
  cursor: pointer;
  transition: var(--transition);
}

.filter-select:hover {
  border-color: var(--primary-light);
}

.view-options {
  display: flex;
  gap: 0.75rem;
}

.view-toggle,
.export-btn {
  background: var(--bg-tertiary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  padding: 0.75rem 1rem;
  color: var(--text-primary);
  font-size: 0.95rem;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.view-toggle:hover,
.export-btn:hover {
  background: var(--border-color);
  border-color: var(--text-light);
}

/* Employee Grid */
.employee-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.employee-card {
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow);
  transition: var(--transition);
  border: 1px solid transparent;
}

.employee-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
  border-color: var(--primary-light);
}

.card-header {
  background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
  padding: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.employee-avatar-large {
  width: 60px;
  height: 60px;
  background: rgba(255, 255, 255, 0.9);
  color: var(--primary-color);
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  font-weight: 700;
}

.employee-status {
  background: rgba(255, 255, 255, 0.9);
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
}

.employee-status.full_time {
  color: var(--success-color);
}

.employee-status.part_time {
  color: var(--warning-color);
}

.employee-status.contract {
  color: var(--info-color);
}

.card-body {
  padding: 1.5rem;
}

.employee-name {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 0.25rem 0;
}

.employee-title {
  color: var(--text-secondary);
  margin: 0 0 0.5rem 0;
  font-size: 0.95rem;
}

.employee-dept {
  color: var(--primary-color);
  font-weight: 600;
  font-size: 0.9rem;
  margin: 0 0 1rem 0;
}

.employee-details {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-label {
  font-size: 0.75rem;
  color: var(--text-light);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.detail-value {
  font-size: 0.9rem;
  color: var(--text-primary);
  font-weight: 500;
}

.badge {
  background: var(--bg-tertiary);
  color: var(--text-secondary);
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
}

.card-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--bg-secondary);
}

.card-action {
  background: none;
  border: none;
  color: var(--text-secondary);
  font-size: 0.9rem;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 6px;
  transition: var(--transition);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.card-action.view-btn {
  color: var(--primary-color);
}

.card-action:hover {
  background: var(--bg-tertiary);
  color: var(--text-primary);
}

/* Table View */
.table-container {
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow);
  margin-bottom: 2rem;
}

.table-wrapper {
  overflow-x: auto;
}

.employees-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 1000px;
}

.employees-table th {
  background: var(--bg-tertiary);
  padding: 1rem 1.5rem;
  text-align: left;
  color: var(--text-secondary);
  font-weight: 600;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid var(--border-color);
}

.employees-table th.sortable {
  cursor: pointer;
  user-select: none;
  transition: var(--transition);
}

.employees-table th.sortable:hover {
  background: var(--border-color);
}

.employees-table th.sortable span {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.sort-icon {
  font-size: 0.75rem;
}

.employees-table td {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--border-light);
  color: var(--text-primary);
}

.employees-table tr:hover td {
  background: var(--bg-tertiary);
}

.employees-table tr:last-child td {
  border-bottom: none;
}

.table-employee {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.table-avatar {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
  color: white;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  font-weight: 700;
}

.employee-name {
  font-weight: 600;
  color: var(--text-primary);
  margin-bottom: 0.25rem;
}

.employee-email {
  font-size: 0.85rem;
  color: var(--text-light);
}

.employee-id {
  background: var(--bg-tertiary);
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-family: monospace;
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.department-tag {
  background: var(--bg-tertiary);
  color: var(--text-secondary);
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 500;
}

.location-tag {
  background: #f0f9ff;
  color: #0369a1;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 500;
  border: 1px solid #bae6fd;
}

.employment-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
}

.employment-badge.full_time {
  background: #d1fae5;
  color: #065f46;
}

.employment-badge.part_time {
  background: #fef3c7;
  color: #92400e;
}

.employment-badge.contract {
  background: #dbeafe;
  color: #1e40af;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  border: 1px solid var(--border-color);
  background: var(--bg-primary);
  color: var(--text-secondary);
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  justify-content: center;
}

.action-btn:hover {
  background: var(--bg-tertiary);
  color: var(--text-primary);
}

.action-btn.view-btn:hover {
  border-color: var(--primary-light);
  color: var(--primary-color);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
}

.empty-illustration {
  font-size: 4rem;
  margin-bottom: 1.5rem;
  opacity: 0.5;
}

.empty-state h3 {
  color: var(--text-primary);
  margin: 0 0 0.5rem 0;
  font-size: 1.5rem;
}

.empty-state p {
  color: var(--text-secondary);
  margin: 0 0 1.5rem 0;
}

.clear-filters-btn {
  background: var(--primary-color);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: var(--radius);
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
}

.clear-filters-btn:hover {
  background: var(--primary-dark);
  transform: translateY(-1px);
}

/* Pagination */
.pagination-container {
  background: var(--bg-primary);
  border-radius: var(--radius-lg);
  padding: 1.5rem;
  box-shadow: var(--shadow);
}

.pagination-info {
  color: var(--text-secondary);
  font-size: 0.9rem;
  margin-bottom: 1rem;
  text-align: center;
}

.pagination-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.pagination-btn {
  background: var(--bg-tertiary);
  border: 1px solid var(--border-color);
  border-radius: var(--radius);
  padding: 0.75rem 1.25rem;
  color: var(--text-primary);
  font-size: 0.95rem;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-width: 120px;
}

.pagination-btn:hover:not(:disabled) {
  background: var(--border-color);
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-numbers {
  display: flex;
  gap: 0.5rem;
}

.page-btn {
  width: 40px;
  height: 40px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border-color);
  background: var(--bg-primary);
  color: var(--text-primary);
  cursor: pointer;
  transition: var(--transition);
  font-size: 0.95rem;
}

.page-btn:hover:not(.active) {
  background: var(--bg-tertiary);
}

.page-btn.active {
  background: var(--primary-color);
  color: white;
  border-color: var(--primary-color);
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 2rem;
  animation: fadeIn 0.3s ease;
}

.modal-card {
  background: var(--bg-primary);
  border-radius: var(--radius-xl);
  max-width: 800px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: var(--shadow-lg);
  animation: slideUp 0.3s ease;
}

.modal-header {
  padding: 2rem;
  border-bottom: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.modal-title {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.modal-avatar {
  width: 64px;
  height: 64px;
  background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
  color: white;
  border-radius: var(--radius);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: 700;
}

.modal-header h2 {
  margin: 0;
  color: var(--text-primary);
  font-size: 1.75rem;
  font-weight: 700;
}

.modal-subtitle {
  color: var(--text-secondary);
  margin: 0.25rem 0 0 0;
  font-size: 1rem;
}

.close-btn {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  border: none;
  background: var(--bg-tertiary);
  color: var(--text-secondary);
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-btn:hover {
  background: var(--border-color);
  color: var(--text-primary);
}

.close-icon {
  font-size: 1.5rem;
  line-height: 1;
}

.modal-body {
  padding: 2rem;
}

.modal-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
  background: var(--bg-secondary);
  padding: 1.5rem;
  border-radius: var(--radius-lg);
}

.modal-stat {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.stat-label {
  color: var(--text-light);
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.stat-value {
  color: var(--text-primary);
  font-size: 1.1rem;
  font-weight: 600;
}

.modal-sections {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.modal-section {
  background: var(--bg-secondary);
  border-radius: var(--radius-lg);
  padding: 1.5rem;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: var(--text-primary);
  margin: 0 0 1.5rem 0;
  font-size: 1.25rem;
  font-weight: 600;
}

.section-icon {
  font-size: 1.5rem;
}

.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.info-item.full-width {
  grid-column: 1 / -1;
}

.info-item label {
  color: var(--text-secondary);
  font-size: 0.9rem;
  font-weight: 500;
}

.info-item p {
  color: var(--text-primary);
  margin: 0;
  font-size: 1rem;
}

.notes-section {
  background: rgba(255, 255, 255, 0.7);
  padding: 1.5rem;
  border-radius: var(--radius);
}

.modal-footer {
  padding: 1.5rem 2rem;
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

.btn-secondary {
  background: var(--bg-tertiary);
  color: var(--text-primary);
  border: 1px solid var(--border-color);
  padding: 0.75rem 1.5rem;
  border-radius: var(--radius);
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
}

.btn-secondary:hover {
  background: var(--border-color);
}

.btn-primary {
  background: var(--primary-color);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: var(--radius);
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-primary:hover {
  background: var(--primary-dark);
  transform: translateY(-1px);
}

/* Animations */
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .employee-management {
    padding: 1rem;
  }
  
  .header-content h1 {
    font-size: 2rem;
  }
  
  .header-stats {
    grid-template-columns: 1fr;
  }
  
  .filter-actions {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filters {
    flex-direction: column;
  }
  
  .filter-select {
    width: 100%;
  }
  
  .view-options {
    justify-content: center;
  }
  
  .employee-grid {
    grid-template-columns: 1fr;
  }
  
  .modal-card {
    margin: 1rem;
    max-height: calc(100vh - 2rem);
  }
  
  .modal-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .close-btn {
    align-self: flex-end;
  }
  
  .modal-footer {
    flex-direction: column;
  }
  
  .pagination-controls {
    flex-direction: column;
    gap: 1rem;
  }
  
  .page-numbers {
    order: 2;
  }
  
  .prev-btn {
    order: 1;
  }
  
  .next-btn {
    order: 3;
  }
}
</style>