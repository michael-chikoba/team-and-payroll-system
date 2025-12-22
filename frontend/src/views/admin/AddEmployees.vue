<template>
  <div class="employee-management">
    <div class="page-header">
      <h1>Employee Management</h1>
      <button
        @click="showAddModal = true"
        class="btn-primary"
        v-if="authStore.isAdmin"
      >
        <span>➕</span> Add Employee
      </button>
    </div>
    <!-- Authentication & Permission Checks -->
    <div v-if="!authStore.isAuthenticated" class="error-message">
      Please log in to access employee management.
    </div>
    <div v-else-if="!authStore.isAdmin && !authStore.isManager" class="error-message">
      You don't have permission to access this page.
    </div>
    <!-- Loading / Error / Table -->
    <div v-else-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Loading employees...</p>
    </div>
    <div v-else-if="error" class="error-message">
      {{ error }}
      <button @click="retryFetch" class="btn-primary" style="margin-top: 1rem;">Retry</button>
    </div>
    <div v-else class="employees-table-wrapper">
      <!-- Business Filter -->
      <div class="business-filter" v-if="authStore.isAdmin && businesses.length > 1">
        <label for="businessFilter">Filter by Business:</label>
        <select
          id="businessFilter"
          v-model="selectedBusinessId"
          @change="onBusinessFilterChange"
          class="business-select"
        >
          <option value="">All Businesses</option>
          <option
            v-for="business in businesses"
            :key="business.id"
            :value="business.id"
          >
            {{ business.name }}
          </option>
        </select>
        <span class="business-badge" v-if="selectedBusinessId">
          Showing: {{ getBusinessName(selectedBusinessId) }}
        </span>
      </div>

      <!-- Search and Items Per Page Controls -->
      <div class="table-controls">
        <div class="search-box">
          <input
            type="text"
            v-model="searchQuery"
            placeholder="Search employees..."
            @input="onSearch"
            class="search-input"
          />
          <span class="search-icon">🔍</span>
        </div>
        <div class="pagination-controls">
          <label for="itemsPerPage">Items per page:</label>
          <select
            id="itemsPerPage"
            v-model="itemsPerPage"
            @change="onItemsPerPageChange"
            class="items-per-page-select"
          >
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
          <div class="table-info">
            Showing {{ showingStart }} to {{ showingEnd }} of {{ totalItems }} employees
          </div>
        </div>
      </div>

      <table class="employees-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>ID</th>
            <th>Email</th>
            <th>Position</th>
            <th>Department</th>
            <th>Country</th>
            <th>Business</th>
            <th>Salary</th>
            <th>Transport</th>
            <th>Lunch</th>
            <th>Hire Date</th>
            <th>Manager</th>
            <th>Type</th>
            <th>Role</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="employee in paginatedEmployees" :key="employee.id">
            <td>
              <div class="employee-name">
                <div class="employee-avatar">
                  {{ getInitials(getEmployeeName(employee)) }}
                </div>
                {{ getEmployeeName(employee) }}
              </div>
            </td>
            <td>{{ employee.employee_id || employee.id }}</td>
            <td>{{ employee.email || employee.user?.email || 'N/A' }}</td>
            <td>{{ employee.position || 'N/A' }}</td>
            <td>{{ employee.department || 'N/A' }}</td>
            <td>
              <div class="country-display" v-if="getCountry(employee)">
                <div class="country-flag-small">
                  <img :src="getCountry(employee).flag" class="flag-img" @error="(e) => e.target.style.display='none'" />
                  <span class="flag-fallback-small">{{ getCountry(employee).code }}</span>
                </div>
                {{ getCountry(employee).name }} ({{ getCountry(employee).code }})
              </div>
              <span v-else>N/A</span>
            </td>
            <td>
              <span class="business-tag" v-if="employee.business_id">
                {{ getBusinessName(employee.business_id) }}
              </span>
              <span v-else class="no-business">No Business</span>
            </td>
            <td>K{{ formatNumber(employee.base_salary) }}</td>
            <td>K{{ formatNumber(employee.transport_allowance || 0) }}</td>
            <td>K{{ formatNumber(employee.lunch_allowance || 0) }}</td>
            <td>{{ formatDate(employee.hire_date || employee.created_at) }}</td>
            <td>{{ getManagerName(employee.manager_id) }}</td>
            <td class="badge-cell">
              <span class="badge">{{ formatEmploymentType(employee.employment_type) }}</span>
            </td>
            <td class="badge-cell">
              <span class="badge" :class="getRoleBadgeClass(getEmployeeRole(employee))">
                {{ formatRole(getEmployeeRole(employee)) }}
              </span>
            </td>
            <td>
              <div class="employee-actions">
                <button
                  v-if="authStore.isAdmin"
                  @click="editEmployee(employee)"
                  class="btn-edit"
                  title="Edit"
                >
                  <span class="icon">✏️</span>
                </button>
                <button
                  v-if="authStore.isAdmin"
                  @click="deleteEmployee(employee)"
                  class="btn-delete"
                  title="Delete"
                >
                  <span class="icon">🗑️</span>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
      
      <!-- Empty State -->
      <div v-if="filteredEmployees.length === 0" class="empty-state">
        <p v-if="searchQuery">No employees found matching "{{ searchQuery }}"</p>
        <p v-else-if="selectedBusinessId">No employees found for {{ getBusinessName(selectedBusinessId) }}</p>
        <p v-else>No employees found</p>
        <button @click="showAddModal = true" class="btn-primary" v-if="authStore.isAdmin">
          Add First Employee
        </button>
      </div>

      <!-- Pagination Component -->
      <div v-if="filteredEmployees.length > 0" class="pagination">
        <button 
          @click="prevPage" 
          :disabled="currentPage === 1" 
          class="pagination-btn"
        >
          Previous
        </button>
        
        <div class="page-numbers">
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="goToPage(page)"
            :class="{
              'page-btn': true,
              'active': page === currentPage,
              'ellipsis': page === '...'
            }"
            :disabled="page === '...'"
          >
            {{ page }}
          </button>
        </div>
        
        <button 
          @click="nextPage" 
          :disabled="currentPage === totalPages" 
          class="pagination-btn"
        >
          Next
        </button>
      </div>
    </div>
    <!-- Add/Edit Modal -->
    <div v-if="(showAddModal || showEditModal) && authStore.isAdmin" class="modal-overlay" @click.self="closeModals">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ showEditModal ? 'Edit Employee' : 'Add New Employee' }}</h2>
          <button @click="closeModals" class="close-btn">✕</button>
        </div>
        <form @submit.prevent="submitForm" class="modal-body">
          <!-- Business Selection (Only for Admins with multiple businesses) -->
          <div class="form-group full-width" v-if="authStore.isAdmin && businesses.length > 1">
            <label>Business *</label>
            <select v-model="form.business_id" required>
              <option value="">Select Business</option>
              <option
                v-for="business in businesses"
                :key="business.id"
                :value="business.id"
              >
                {{ business.name }} ({{ business.industry || 'N/A' }})
              </option>
            </select>
            <small v-if="businesses.length === 0" class="warning-text">
              No businesses available. Please create a business first.
            </small>
          </div>
          <div v-else-if="authStore.isAdmin && businesses.length === 1" class="form-group full-width">
            <label>Business</label>
            <input
              :value="businesses[0].name"
              type="text"
              disabled
              class="disabled-input"
            />
            <input type="hidden" v-model="form.business_id" :value="businesses[0].id" />
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>First Name *</label>
              <input v-model="form.first_name" type="text" required placeholder="Enter first name" />
            </div>
            <div class="form-group">
              <label>Last Name *</label>
              <input v-model="form.last_name" type="text" required placeholder="Enter last name" />
            </div>
          </div>
          <div class="form-group" v-if="!showEditModal">
            <label>Email *</label>
            <input v-model="form.email" type="email" required placeholder="employee@example.com" />
          </div>
        
          <!-- Enhanced Country Selector -->
          <div class="form-group full-width">
            <label>Country *</label>
            <div class="country-select-wrapper">
              <div class="country-select-header" @click="toggleCountryDropdown">
                <div class="selected-country" v-if="selectedCountry">
                  <div class="country-flag-selector">
                    <img
                      :src="selectedCountry.flag"
                      class="flag-img-selector"
                      @error="(e) => e.target.style.display='none'"
                    />
                    <span class="flag-fallback-selector">{{ selectedCountry.code }}</span>
                  </div>
                  <div class="country-info">
                    <span class="country-name">{{ selectedCountry.name }}</span>
                    <span class="country-code">({{ selectedCountry.code }})</span>
                  </div>
                </div>
                <div class="country-placeholder" v-else>
                  Select a country...
                </div>
                <span class="dropdown-arrow">{{ showCountryDropdown ? '▲' : '▼' }}</span>
              </div>
              
              <div v-if="showCountryDropdown" class="country-dropdown">
                <div class="country-search">
                  <input
                    type="text"
                    v-model="countrySearch"
                    placeholder="Search countries..."
                    @click.stop
                    ref="countrySearchInput"
                  />
                  <span class="search-icon">🔍</span>
                </div>
                <div class="country-list">
                  <div
                    v-for="country in filteredCountries"
                    :key="country.id"
                    class="country-option"
                    :class="{
                      'selected': form.country_id === country.id,
                      'inactive': !country.is_active && form.country_id !== country.id
                    }"
                    @click="selectCountry(country)"
                  >
                    <div class="country-flag-selector">
                      <img
                        :src="country.flag"
                        class="flag-img-selector"
                        @error="(e) => e.target.style.display='none'"
                      />
                      <span class="flag-fallback-selector">{{ country.code }}</span>
                    </div>
                    <div class="country-info">
                      <span class="country-name">{{ country.name }}</span>
                      <span class="country-code">({{ country.code }})</span>
                    </div>
                    <span v-if="!country.is_active" class="inactive-badge">Inactive</span>
                    <span v-if="form.country_id === country.id" class="checkmark">✓</span>
                  </div>
                </div>
                <div v-if="filteredCountries.length === 0" class="no-countries">
                  No countries found matching "{{ countrySearch }}"
                </div>
              </div>
            </div>
            <input type="hidden" v-model="form.country_id" required />
            <small v-if="activeCountries.length === 0" class="warning-text">
              No active countries available. Please add a country first.
            </small>
            <small v-if="selectedCountry && !selectedCountry.is_active" class="warning-text">
              This country is currently inactive.
            </small>
          </div>
        
          <div class="form-group">
            <label>Role *</label>
            <select v-model="form.role" required @change="onRoleChange">
              <option value="employee">Employee</option>
              <option value="manager">Manager</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Position *</label>
              <input v-model="form.position" type="text" required placeholder="e.g., Software Developer" />
            </div>
            
            <!-- DYNAMIC DEPARTMENTS FROM BACKEND SETTINGS -->
            <div class="form-group">
              <label>Department *</label>
              <select v-model="form.department" required :disabled="loadingDepartments || !form.business_id">
                <option value="">{{ loadingDepartments ? 'Loading departments...' : 'Select Department' }}</option>
                <option 
                  v-for="(dept, index) in availableDepartments" 
                  :key="index" 
                  :value="dept.name"
                >
                  {{ dept.name }}
                </option>
              </select>
              <small v-if="!form.business_id" class="info-text">Please select a business first.</small>
              <small v-else-if="!loadingDepartments && availableDepartments.length === 0" class="warning-text">
                No departments found for this business. Please configure settings.
              </small>
            </div>
            
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Base Salary (K) *</label>
              <input v-model.number="form.base_salary" type="number" step="0.01" required placeholder="0.00" />
            </div>
            <div class="form-group">
              <label>Employment Type *</label>
              <select v-model="form.employment_type" required>
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
              <input v-model.number="form.transport_allowance" type="number" step="0.01" placeholder="300.00" />
            </div>
            <div class="form-group">
              <label>Lunch Allowance (K)</label>
              <input v-model.number="form.lunch_allowance" type="number" step="0.01" placeholder="240.00" />
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Hire Date *</label>
              <input v-model="form.hire_date" type="date" required />
            </div>
            <div class="form-group">
              <label>Manager <span v-if="form.role === 'employee'" style="color: red;">*</span></label>
              <select
                v-model="form.manager_id"
                :required="form.role === 'employee'"
                :disabled="form.role === 'manager' || form.role === 'admin'"
              >
                <option value="">No Manager</option>
                <option v-for="mgr in managers" :key="mgr.id" :value="mgr.id">
                  {{ mgr.first_name }} {{ mgr.last_name }} ({{ mgr.department }})
                </option>
              </select>
              <small v-if="form.role === 'employee' && managers.length === 0" class="warning-text">
                No managers available. Please create a manager first.
              </small>
              <small v-if="form.role === 'manager' || form.role === 'admin'" class="info-text">
                {{ form.role === 'admin' ? 'Admins' : 'Managers' }} don't have managers
              </small>
            </div>
          </div>
          <div v-if="formError" class="form-error">{{ formError }}</div>
          <div class="modal-footer">
            <button type="button" @click="closeModals" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="submitting">
              {{ submitting ? 'Saving...' : (showEditModal ? 'Update' : 'Add Employee') }}
            </button>
          </div>
        </form>
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
      
      // Department Logic
      availableDepartments: [],
      loadingDepartments: false,

      loading: false,
      error: null,
      showAddModal: false,
      showEditModal: false,
      submitting: false,
      formError: null,
      currentEmployee: null,
      selectedBusinessId: this.authStore.user?.business_id || '',
      showCountryDropdown: false,
      countrySearch: '',
      
      // Pagination data
      currentPage: 1,
      itemsPerPage: 10,
      searchQuery: '',
      
      form: {
        first_name: '',
        last_name: '',
        email: '',
        business_id: this.authStore.user?.business_id || '',
        country_id: '',
        role: 'employee',
        position: '',
        department: '',
        base_salary: '',
        transport_allowance: '',
        lunch_allowance: '',
        employment_type: '',
        hire_date: '',
        manager_id: ''
      },
      retryCount: 0
    }
  },
  computed: {
    activeCountries() {
      return this.countries.filter(c => c.is_active)
    },
    countriesSorted() {
      return [...this.countries].sort((a, b) => a.name.localeCompare(b.name))
    },
    // Filter managers based on selected business
    filteredManagers() {
      if (!this.selectedBusinessId) return this.managers
      return this.managers.filter(manager =>
        manager.business_id === this.selectedBusinessId || !manager.business_id
      )
    },
    // Filter countries for search
    filteredCountries() {
      if (!this.countrySearch.trim()) {
        return this.countriesSorted
      }
      const searchTerm = this.countrySearch.toLowerCase()
      return this.countriesSorted.filter(country =>
        country.name.toLowerCase().includes(searchTerm) ||
        country.code.toLowerCase().includes(searchTerm)
      )
    },
    // Get selected country object
    selectedCountry() {
      if (!this.form.country_id) return null
      return this.countries.find(c => c.id === this.form.country_id)
    },
    
    // Filter employees based on search query
    filteredEmployees() {
      if (!this.searchQuery.trim()) {
        return this.employees
      }
      
      const searchTerm = this.searchQuery.toLowerCase()
      return this.employees.filter(employee => {
        const name = this.getEmployeeName(employee).toLowerCase()
        const email = (employee.email || employee.user?.email || '').toLowerCase()
        const position = (employee.position || '').toLowerCase()
        const department = (employee.department || '').toLowerCase()
        const employeeId = (employee.employee_id || employee.id || '').toString().toLowerCase()
        
        return name.includes(searchTerm) ||
               email.includes(searchTerm) ||
               position.includes(searchTerm) ||
               department.includes(searchTerm) ||
               employeeId.includes(searchTerm)
      })
    },
    
    // Pagination calculations
    totalItems() {
      return this.filteredEmployees.length
    },
    
    totalPages() {
      return Math.ceil(this.totalItems / this.itemsPerPage)
    },
    
    showingStart() {
      return (this.currentPage - 1) * this.itemsPerPage + 1
    },
    
    showingEnd() {
      const end = this.currentPage * this.itemsPerPage
      return end > this.totalItems ? this.totalItems : end
    },
    
    paginatedEmployees() {
      const startIndex = (this.currentPage - 1) * this.itemsPerPage
      const endIndex = startIndex + this.itemsPerPage
      return this.filteredEmployees.slice(startIndex, endIndex)
    },
    
    visiblePages() {
      const pages = []
      const maxVisible = 5
      
      if (this.totalPages <= maxVisible) {
        for (let i = 1; i <= this.totalPages; i++) {
          pages.push(i)
        }
      } else {
        if (this.currentPage <= 3) {
          for (let i = 1; i <= 4; i++) {
            pages.push(i)
          }
          pages.push('...')
          pages.push(this.totalPages)
        } else if (this.currentPage >= this.totalPages - 2) {
          pages.push(1)
          pages.push('...')
          for (let i = this.totalPages - 3; i <= this.totalPages; i++) {
            pages.push(i)
          }
        } else {
          pages.push(1)
          pages.push('...')
          pages.push(this.currentPage - 1)
          pages.push(this.currentPage)
          pages.push(this.currentPage + 1)
          pages.push('...')
          pages.push(this.totalPages)
        }
      }
      
      return pages
    }
  },
  watch: {
    // Watch for changes in the form's business selection to fetch relevant departments
    'form.business_id': {
      handler(newVal) {
        if (newVal) {
          this.fetchSettingsForBusiness(newVal)
        } else {
          this.availableDepartments = []
        }
      },
      immediate: true
    }
  },
  mounted() {
    this.initializeComponent()
    // Close dropdown when clicking outside
    document.addEventListener('click', this.closeCountryDropdown)
  },
  beforeUnmount() {
    document.removeEventListener('click', this.closeCountryDropdown)
  },
  methods: {
    async initializeComponent() {
      if (!this.authStore.isAuthenticated) {
        this.error = 'Please log in to access employee management.'
        return
      }
      if (!this.authStore.isAdmin && !this.authStore.isManager) {
        this.error = 'You do not have permission to access this page.'
        return
      }
      
      try {
        // Load businesses first (for admin)
        if (this.authStore.isAdmin) {
          await this.fetchBusinesses()
        }
        
        // Set default business if user has only one business
        if (this.authStore.isAdmin && this.businesses.length === 1) {
          this.form.business_id = this.businesses[0].id
          this.selectedBusinessId = this.businesses[0].id
        }
        
        await Promise.all([
          this.fetchEmployees(),
          this.fetchManagers(),
          this.fetchCountries()
        ])
      } catch (err) {
        console.error('Initialization error:', err)
        this.error = 'Failed to initialize component. Please try again.'
      }
    },
    
    async fetchBusinesses() {
      try {
        const response = await axios.get('/api/admin/businesses')
        this.businesses = response.data.data || []
      } catch (error) {
        console.error('Failed to fetch businesses:', error)
        this.$notify({
          type: 'error',
          title: 'Error',
          text: 'Failed to load businesses'
        })
      }
    },
    
    // NEW: Fetch department settings for the specific business
    async fetchSettingsForBusiness(businessId) {
      this.loadingDepartments = true;
      try {
        const response = await axios.get('/api/admin/settings', {
          params: { business_id: businessId }
        });

        // Structure from AdminController::getSettings
        // Returns { settings: { departments: [{name: 'IT'}], ... }, ... }
        if (response.data && response.data.settings && response.data.settings.departments) {
          this.availableDepartments = response.data.settings.departments;
        } else {
          this.availableDepartments = [];
        }
      } catch (error) {
        console.error('Failed to load business settings:', error);
        this.availableDepartments = [];
      } finally {
        this.loadingDepartments = false;
      }
    },

    async fetchEmployees() {
      this.loading = true
      this.error = null
      try {
        let url = '/api/admin/employees'
        
        // Add business filter if selected
        if (this.selectedBusinessId) {
          url += `?business_id=${this.selectedBusinessId}`
        }
        
        const response = await axios.get(url)
        let employeesData = []
        if (response.data && response.data.employees) employeesData = response.data.employees
        else if (response.data && response.data.data) employeesData = response.data.data
        else employeesData = response.data || []
        
        this.employees = employeesData
        this.currentPage = 1 // Reset to first page when data changes
        
      } catch (err) {
        console.error('Fetch employees error:', err)
        this.handleApiError(err)
      } finally {
        this.loading = false
      }
    },
    
    async fetchManagers() {
      try {
        let url = '/api/admin/managers'
        
        // Add business filter if selected
        if (this.selectedBusinessId) {
          url += `?business_id=${this.selectedBusinessId}`
        }
        
        const response = await axios.get(url)
        this.managers = Array.isArray(response.data) ? response.data :
                     response.data?.data || response.data || []
        
      } catch (err) {
        console.error('Failed to fetch managers:', err)
        this.managers = []
      }
    },
    
    async fetchCountries() {
      try {
        const response = await axios.get('/api/admin/countries')
        this.countries = response.data?.data || response.data || []
      } catch (err) {
        console.error('Failed to fetch countries:', err)
        this.countries = []
      }
    },
    
    // Enhanced country selection methods
    toggleCountryDropdown() {
      this.showCountryDropdown = !this.showCountryDropdown
      if (this.showCountryDropdown) {
        this.$nextTick(() => {
          if (this.$refs.countrySearchInput) {
            this.$refs.countrySearchInput.focus()
          }
        })
      }
    },
    
    closeCountryDropdown(event) {
      const countrySelector = document.querySelector('.country-select-wrapper')
      if (countrySelector && !countrySelector.contains(event.target)) {
        this.showCountryDropdown = false
        this.countrySearch = ''
      }
    },
    
    selectCountry(country) {
      this.form.country_id = country.id
      this.showCountryDropdown = false
      this.countrySearch = ''
    },
    
    // Pagination methods
    prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--
      }
    },
    
    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++
      }
    },
    
    goToPage(page) {
      if (page !== '...' && page >= 1 && page <= this.totalPages) {
        this.currentPage = page
      }
    },
    
    onSearch() {
      this.currentPage = 1 // Reset to first page when searching
    },
    
    onItemsPerPageChange() {
      this.currentPage = 1 // Reset to first page when changing items per page
    },
    
    onBusinessFilterChange() {
      this.currentPage = 1 // Reset to first page when changing business filter
      this.fetchEmployees()
      this.fetchManagers()
      
      // Update form business_id if in add mode
      if (this.showAddModal && this.selectedBusinessId) {
        this.form.business_id = this.selectedBusinessId
      }
    },
    
    getBusinessName(businessId) {
      const business = this.businesses.find(b => b.id === businessId)
      return business ? business.name : 'Unknown Business'
    },
    
    getCountry(employee) {
      const countryId = employee.country_id || (employee.country && employee.country.id)
      return this.countries.find(c => c.id === countryId) || null
    },
    
    getEmployeeName(employee) {
      if (employee.first_name && employee.last_name) return `${employee.first_name} ${employee.last_name}`.trim()
      if (employee.user?.first_name) return `${employee.user.first_name} ${employee.user.last_name || ''}`.trim()
      if (employee.name) return employee.name
      if (employee.full_name) return employee.full_name
      return 'N/A'
    },
    
    getEmployeeRole(employee) {
      return employee.role || employee.user?.role || 'employee'
    },
    
    getManagerName(managerId) {
      if (!managerId) return 'No Manager'
      const manager = this.managers.find(m => m.id === managerId)
      return manager ? `${manager.first_name} ${manager.last_name}` : 'Unknown Manager'
    },
    
    formatRole(role) {
      const map = { admin: 'Admin', manager: 'Manager', employee: 'Employee' }
      return map[role] || 'Employee'
    },
    
    getRoleBadgeClass(role) {
      return {
        'role-employee': role === 'employee',
        'role-manager': role === 'manager',
        'role-admin': role === 'admin'
      }
    },
    
    onRoleChange() {
      if (this.form.role === 'manager' || this.form.role === 'admin') {
        this.form.manager_id = ''
      }
    },
    
    async submitForm() {
      this.submitting = true
      this.formError = null
      
      // Validations
      if (this.authStore.isAdmin && !this.form.business_id) {
        this.formError = 'Please select a business for the employee.'
        this.submitting = false
        return
      }
      
      if (!this.form.country_id) {
        this.formError = 'Please select a country for the employee.'
        this.submitting = false
        return
      }
      
      if (this.form.role === 'employee' && !this.form.manager_id) {
        this.formError = 'Please select a manager for the employee.'
        this.submitting = false
        return
      }
      
      const payload = {
        first_name: this.form.first_name,
        last_name: this.form.last_name,
        email: this.form.email,
        business_id: parseInt(this.form.business_id),
        country_id: parseInt(this.form.country_id),
        role: this.form.role,
        position: this.form.position,
        department: this.form.department,
        base_salary: parseFloat(this.form.base_salary),
        transport_allowance: parseFloat(this.form.transport_allowance || 0),
        lunch_allowance: parseFloat(this.form.lunch_allowance || 0),
        employment_type: this.form.employment_type,
        hire_date: this.form.hire_date,
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
        this.$notify({
          type: 'success',
          title: 'Success',
          text: this.showEditModal
            ? 'Employee updated successfully!'
            : 'Employee created successfully! Default password is: Password123!'
        })
      } catch (err) {
        console.error('Submit error:', err)
        this.handleApiError(err)
      } finally {
        this.submitting = false
      }
    },
    
    editEmployee(employee) {
      this.currentEmployee = employee
      const userRole = this.getEmployeeRole(employee)
      const countryId = employee.country_id || (employee.country && employee.country.id) || ''
      const businessId = employee.business_id || this.authStore.user?.business_id || ''

      // 1. Set the ID first so the watcher triggers the department fetch
      this.form.business_id = businessId;
      
      this.form = {
        first_name: employee.first_name || employee.user?.first_name || '',
        last_name: employee.last_name || employee.user?.last_name || '',
        email: employee.email || employee.user?.email || '',
        business_id: businessId,
        country_id: countryId,
        role: userRole,
        position: employee.position || '',
        department: employee.department || '',
        base_salary: employee.base_salary || '',
        transport_allowance: employee.transport_allowance || '',
        lunch_allowance: employee.lunch_allowance || '',
        employment_type: employee.employment_type || '',
        hire_date: employee.hire_date?.split('T')[0] || employee.created_at?.split('T')[0] || '',
        manager_id: userRole === 'employee' ? employee.manager_id : ''
      }
      
      // Ensure departments are fetched for the correct business in edit mode
      this.fetchSettingsForBusiness(businessId);

      this.showEditModal = true
    },
    
    resetForm() {
      this.form = {
        first_name: '',
        last_name: '',
        email: '',
        business_id: this.authStore.user?.business_id || '',
        country_id: '',
        role: 'employee',
        position: '',
        department: '',
        base_salary: '',
        transport_allowance: '',
        lunch_allowance: '',
        employment_type: '',
        hire_date: '',
        manager_id: ''
      }
      this.countrySearch = ''
      this.showCountryDropdown = false
      // Reset departments if no default business
      if(!this.authStore.user?.business_id) {
        this.availableDepartments = [];
      }
    },
    
    getInitials(name) {
      if (!name || name === 'N/A') return '??'
      return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2)
    },
    
    formatNumber(num) {
      return new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num || 0)
    },
    
    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
    },
    
    formatEmploymentType(type) {
      const types = { full_time: 'Full Time', part_time: 'Part Time', contract: 'Contract' }
      return types[type] || type || 'N/A'
    },
    
    retryFetch() {
      this.retryCount++
      if (this.retryCount <= 3) this.fetchEmployees()
      else this.error = 'Max retries exceeded. Check your network or server.'
    },
    
    handleApiError(err) {
      let errorMsg = 'An unexpected error occurred.'
      if (err.code === 'ERR_NETWORK' || err.message.includes('Network Error')) {
        errorMsg = 'Network error: Please check your connection and try again.'
      } else if (err.response?.status === 401) {
        errorMsg = 'Your session has expired. Please log in again.'
        this.authStore.clearAuth()
        this.$router.push({ name: 'login' })
      } else if (err.response?.status === 403) {
        errorMsg = 'You do not have permission to perform this action.'
      } else if (err.response?.status === 422) {
        this.formError = err.response.data.message || 'Please check the form for errors.'
        return
      } else {
        errorMsg = err.response?.data?.message || errorMsg
      }
      this.error = errorMsg
    },
    
    closeModals() {
      this.showAddModal = false
      this.showEditModal = false
      this.currentEmployee = null
      this.formError = null
      this.resetForm()
    },
    
    async deleteEmployee(employee) {
      const name = this.getEmployeeName(employee)
      if (!confirm(`Are you sure you want to delete ${name}? This action cannot be undone.`)) return
      
      try {
        await axios.delete(`/api/admin/employees/${employee.id}`)
        await this.fetchEmployees()
        this.$notify({ type: 'success', title: 'Success', text: 'Employee deleted successfully!' })
      } catch (err) {
        this.handleApiError(err)
      }
    }
  }
}
</script>

<style scoped>
/* Enhanced Country Selector Styles */
.country-select-wrapper {
  position: relative;
  width: 100%;
}
.country-select-header {
  padding: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: white;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  transition: border-color 0.2s;
}
.country-select-header:hover {
  border-color: #667eea;
}
.selected-country {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
}
.country-placeholder {
  color: #a0aec0;
  flex: 1;
}
.dropdown-arrow {
  color: #718096;
  font-size: 0.75rem;
}
.country-flag-selector {
  position: relative;
  width: 32px;
  height: 22px;
  border-radius: 4px;
  overflow: hidden;
  background: #f0f0f0;
  flex-shrink: 0;
}
.flag-img-selector {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.flag-fallback-selector {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.7rem;
  color: #555;
  background: rgba(255,255,255,0.8);
}
.country-info {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}
.country-name {
  font-weight: 500;
  color: #4a5568;
}
.country-code {
  font-size: 0.75rem;
  color: #718096;
}
.country-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  z-index: 100;
  margin-top: 0.25rem;
  max-height: 300px;
  overflow-y: auto;
}
.country-search {
  position: relative;
  padding: 0.75rem;
  border-bottom: 1px solid #e2e8f0;
}
.country-search input {
  width: 100%;
  padding: 0.5rem 2rem 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  font-size: 0.875rem;
}
.search-icon {
  position: absolute;
  right: 1.25rem;
  top: 50%;
  transform: translateY(-50%);
  color: #718096;
  font-size: 0.875rem;
}
.country-list {
  max-height: 250px;
  overflow-y: auto;
}
.country-option {
  padding: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
  transition: background-color 0.2s;
  border-bottom: 1px solid #f7fafc;
}
.country-option:last-child {
  border-bottom: none;
}
.country-option:hover {
  background-color: #f7fafc;
}
.country-option.selected {
  background-color: #e6f7ff;
}
.country-option.inactive {
  opacity: 0.6;
}
.inactive-badge {
  margin-left: auto;
  padding: 0.125rem 0.5rem;
  background: #fef3c7;
  color: #92400e;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}
.checkmark {
  color: #1890ff;
  font-weight: bold;
  margin-left: 0.5rem;
}
.no-countries {
  padding: 1rem;
  text-align: center;
  color: #718096;
  font-size: 0.875rem;
}

/* Table Controls */
.table-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 8px;
  flex-wrap: wrap;
  gap: 1rem;
}

.search-box {
  position: relative;
  flex: 1;
  min-width: 250px;
}

.search-input {
  width: 100%;
  padding: 0.75rem 2.5rem 0.75rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.95rem;
  transition: border-color 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-box .search-icon {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #718096;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}

.pagination-controls label {
  font-weight: 600;
  color: #4a5568;
  white-space: nowrap;
}

.items-per-page-select {
  padding: 0.5rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: white;
  color: #4a5568;
  min-width: 80px;
}

.table-info {
  color: #718096;
  font-size: 0.9rem;
  white-space: nowrap;
}

/* Pagination Styles */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid #e2e8f0;
  margin-top: 1rem;
}

.pagination-btn {
  padding: 0.75rem 1.5rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  color: #4a5568;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  min-width: 100px;
}

.pagination-btn:hover:not(:disabled) {
  background: #f7fafc;
  border-color: #667eea;
  color: #667eea;
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
  padding: 0.5rem 1rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  color: #4a5568;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  min-width: 40px;
}

.page-btn:hover:not(:disabled) {
  background: #f7fafc;
  border-color: #667eea;
}

.page-btn.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-color: #667eea;
}

.page-btn.ellipsis {
  background: transparent;
  border: none;
  cursor: default;
  min-width: auto;
}

.page-btn.ellipsis:hover {
  background: transparent;
  border: none;
}

/* Existing CSS styles */
.business-filter {
  background: #f8fafc;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
}
.business-filter label {
  font-weight: 600;
  color: #4a5568;
}
.business-select {
  padding: 0.5rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: white;
  color: #4a5568;
  min-width: 200px;
}
.business-badge {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
}
.business-tag {
  background: #eef2ff;
  color: #667eea;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
  display: inline-block;
}
.no-business {
  color: #a0aec0;
  font-style: italic;
  font-size: 0.9rem;
}
.disabled-input {
  background: #f7fafc;
  color: #a0aec0;
  cursor: not-allowed;
  border: 1px solid #e2e8f0;
}
.form-group.full-width {
  grid-column: 1 / -1;
}
.country-display {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.95rem;
}
.country-flag-small {
  position: relative;
  width: 32px;
  height: 22px;
  border-radius: 4px;
  overflow: hidden;
  background: #f0f0f0;
  flex-shrink: 0;
}
.flag-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.flag-fallback-small {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.7rem;
  color: #555;
  background: rgba(255,255,255,0.8);
}
.info-text {
  color: #667eea;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  display: block;
}
.role-admin {
  background: #fee2e2;
  color: #991b1b;
}
/* Updated action buttons with icons */
.employee-actions {
  display: flex;
  gap: 0.25rem;
  flex-wrap: nowrap;
  justify-content: center;
}
.employee-actions button {
  padding: 0.5rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.875rem;
  transition: all 0.2s;
  white-space: nowrap;
  min-width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.btn-view {
  background: #e6f7ff;
  color: #1890ff;
}
.btn-edit {
  background: #fff7e6;
  color: #fa8c16;
}
.btn-delete {
  background: #fff1f0;
  color: #f5222d;
}
.employee-actions button:hover {
  transform: scale(1.1);
}
.employee-actions .icon {
  font-size: 1rem;
  line-height: 1;
}
/* Existing CSS styles */
.employee-management {
  padding: 2rem;
  max-width: 1600px;
  margin: 0 auto;
  background: #f9fafb;
  min-height: 100vh;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}
.page-header h1 {
  color: #2d3748;
  font-size: 2rem;
  margin: 0;
}
.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: transform 0.2s;
}
.btn-primary:hover {
  transform: translateY(-2px);
}
.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.loading {
  text-align: center;
  padding: 4rem;
}
.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
.error-message {
  background: #fee;
  color: #c33;
  padding: 1rem;
  border-radius: 8px;
  text-align: center;
}
.employees-table-wrapper {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  overflow-x: auto;
}
.employees-table {
  width: 100%;
  border-collapse: collapse;
}
.employees-table th,
.employees-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}
.employees-table th {
  background-color: #f7fafc;
  font-weight: 600;
  color: #4a5568;
  position: sticky;
  top: 0;
  z-index: 10;
}
.employees-table tr:hover {
  background-color: #f7fafc;
}
.employee-name {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.employee-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  font-weight: 700;
  flex-shrink: 0;
}
.badge-cell {
  padding: 0.5rem;
}
.badge {
  background: #eef2ff;
  color: #667eea;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
  display: inline-block;
}
.role-employee {
  background: #d1fae5;
  color: #065f46;
}
.role-manager {
  background: #fef3c7;
  color: #92400e;
}
.empty-state {
  text-align: center;
  padding: 4rem;
  color: #718096;
}
.empty-state p {
  margin-bottom: 1rem;
}
/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.modal {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}
.modal-header h2 {
  margin: 0;
  color: #2d3748;
}
.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #718096;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}
.close-btn:hover {
  background: #f0f0f0;
}
.modal-body {
  padding: 1.5rem;
}
.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}
.form-group {
  margin-bottom: 1rem;
}
.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #4a5568;
  font-weight: 600;
}
.form-group input,
.form-group select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 1rem;
}
.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #667eea;
}
.form-error {
  background: #fee;
  color: #c33;
  padding: 0.75rem;
  border-radius: 6px;
  margin-bottom: 1rem;
}
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}
.btn-secondary {
  background: #e2e8f0;
  color: #4a5568;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}
.warning-text {
  color: #f59e0b;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  display: block;
}

@media (max-width: 768px) {
  .table-controls {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-box {
    width: 100%;
    min-width: unset;
  }
  
  .pagination-controls {
    width: 100%;
    justify-content: space-between;
  }
  
  .pagination {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .page-numbers {
    order: -1;
    width: 100%;
    justify-content: center;
  }
  
  .employees-table-wrapper {
    font-size: 0.875rem;
  }

  .employees-table th,
  .employees-table td {
    padding: 0.75rem 0.5rem;
  }

  .employee-actions {
    flex-direction: row;
    gap: 0.25rem;
  }

  .employee-actions button {
    min-width: 35px;
    height: 35px;
    padding: 0.25rem;
  }

  .employee-actions .icon {
    font-size: 0.875rem;
  }

  .form-row {
    grid-template-columns: 1fr;
  }

  .page-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }

  .business-filter {
    flex-direction: column;
    align-items: stretch;
  }

  .business-select {
    min-width: 100%;
  }

  .country-info {
    flex-direction: row;
    align-items: center;
    gap: 0.5rem;
  }

  .country-code {
    font-size: 0.7rem;
  }
}
</style>