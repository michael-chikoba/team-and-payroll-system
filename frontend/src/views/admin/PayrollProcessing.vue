<template>
  <div class="payroll-view">
    <header class="header">
      <h1 class="title">{{ pageName }}</h1>
      <div class="header-actions">
        <button @click="addEmployee" class="add-btn">
          <span class="btn-icon">+</span> Add Employee
        </button>
        <button
          @click="showPreview"
          class="preview-btn"
          :disabled="processing || pendingCount === 0"
        >
          📊 Preview & Adjust
        </button>
        <button
          @click="processPayroll"
          class="process-btn"
          :disabled="processing || pendingCount === 0"
        >
          <span v-if="processing" class="spinner-small"></span>
          {{ processing ? 'Processing...' : 'Process Payroll' }}
        </button>
      </div>
    </header>
    <!-- Date Selection Section -->
    <div class="date-selection-section">
      <div class="date-inputs">
        <div class="form-group">
          <label class="form-label">Payroll Period (YYYY-MM)</label>
          <input
            type="month"
            v-model="payrollPeriod"
            @change="updateDateRange"
            class="date-input"
          >
        </div>
        <div class="form-group">
          <label class="form-label">Start Date</label>
          <input
            type="date"
            v-model="startDate"
            class="date-input"
          >
        </div>
        <div class="form-group">
          <label class="form-label">End Date</label>
          <input
            type="date"
            v-model="endDate"
            class="date-input"
          >
        </div>
        <!-- Business Filter Section -->
        <div class="business-filter-section" v-if="authStore.isAdmin">
          <div class="form-group">
            <label class="form-label">Filter by Business:</label>
            <select
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
        </div>
        <button @click="refreshPayrollData" class="refresh-btn">
          🔄 Refresh
        </button>
      </div>
    </div>
    <!-- Warning Banner for Tax Configuration -->
    <transition name="fade">
      <div v-if="taxConfigWarning" class="warning-banner">
        <span>⚠️ {{ taxConfigWarning }}</span>
        <button @click="taxConfigWarning = null" class="dismiss-btn">×</button>
      </div>
    </transition>
    <!-- Error Banner -->
    <transition name="fade">
      <div v-if="error" class="error-banner">
        <span>{{ error }}</span>
        <button @click="dismissError" class="dismiss-btn">×</button>
      </div>
    </transition>
    <!-- Success Banner -->
    <transition name="fade">
      <div v-if="successMessage" class="success-banner">
        <span>{{ successMessage }}</span>
        <button @click="successMessage = null" class="dismiss-btn">×</button>
      </div>
    </transition>
    <!-- Summary Cards -->
    <div class="summary-cards">
      <div class="card">
        <div class="card-icon employees">👥</div>
        <h3>Total Employees</h3>
        <p class="value">{{ employees.length }}</p>
      </div>
      <div class="card">
        <div class="card-icon pending">⏳</div>
        <h3>Pending Payroll</h3>
        <p class="value pending">{{ pendingCount }}</p>
      </div>
      <div class="card">
        <div class="card-icon amount">💰</div>
        <h3>Total Payroll Amount</h3>
        <p class="value">{{ formatCurrency(totalPayroll) }}</p>
      </div>
      <div class="card">
        <div class="card-icon paid">✓</div>
        <h3>Paid This Period</h3>
        <p class="value success">{{ paidCount }}</p>
      </div>
    </div>
    <!-- Filters & Search -->
    <div class="filters-section">
      <div class="search-box">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search by name, position, or ID..."
          class="search-input"
        />
      </div>
      <div class="filter-buttons">
        <button
          @click="filterStatus = 'all'"
          :class="['filter-btn', { active: filterStatus === 'all' }]"
        >
          All ({{ employees.length }})
        </button>
        <button
          @click="filterStatus = 'pending'"
          :class="['filter-btn', { active: filterStatus === 'pending' }]"
        >
          Pending ({{ pendingCount }})
        </button>
        <button
          @click="filterStatus = 'paid'"
          :class="['filter-btn', { active: filterStatus === 'paid' }]"
        >
          Paid ({{ paidCount }})
        </button>
      </div>
    </div>
    <!-- Content Area -->
    <div class="content">
      <div v-if="loading" class="loading-state">
        <div class="spinner"></div>
        <p>Loading payroll data...</p>
      </div>
      <div v-else-if="filteredEmployees.length === 0 && !searchQuery" class="empty-state">
        <div class="empty-icon">📋</div>
        <h2>No Employees Found</h2>
        <p>Add employees to start processing payroll.</p>
        <button @click="addEmployee" class="btn-primary">Add Your First Employee</button>
      </div>
      <div v-else-if="filteredEmployees.length === 0 && searchQuery" class="empty-state">
        <div class="empty-icon">🔍</div>
        <h2>No Results Found</h2>
        <p>Try adjusting your search criteria.</p>
        <button @click="clearSearch" class="btn-secondary">Clear Search</button>
      </div>
      <div v-else class="table-container">
        <!-- Pagination Info -->
        <div class="pagination-info">
          Showing {{ pagination.startIndex + 1 }}-{{ pagination.endIndex }} of {{ filteredEmployees.length }} employees
        </div>
        
        <table class="payroll-table">
          <thead>
            <tr>
              <th>
                <input
                  type="checkbox"
                  @change="toggleSelectAll"
                  :checked="allSelected"
                  class="checkbox"
                />
              </th>
              <th>ID</th>
              <th>Employee</th>
              <th>Business</th>
              <th>Position</th>
              <th>Base Salary</th>
              <th>Gross Salary</th>
              <th>Net Pay</th>
              <th>Status</th>
              <th>Pay Period</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="employee in paginatedEmployees"
              :key="employee.id"
              :class="{ selected: employee.selected }"
            >
              <td>
                <input
                  type="checkbox"
                  v-model="employee.selected"
                  class="checkbox"
                />
              </td>
              <td><strong>#{{ employee.id }}</strong></td>
              <td>
                <div class="employee-name">
                  <div class="employee-avatar" :style="{ background: getAvatarColor(employee.name) }">
                    {{ getInitials(employee.name) }}
                  </div>
                  <div class="employee-info">
                    <div class="name">{{ employee.name }}</div>
                    <div class="email">{{ employee.email || '—' }}</div>
                    <div class="business-tag-small" v-if="employee.business_name && employee.business_name !== 'No Business'">
                      {{ employee.business_name }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="business-column">
                <span v-if="employee.business_name && employee.business_name !== 'No Business'" class="business-tag">
                  {{ employee.business_name }}
                </span>
                <span v-else class="no-business">—</span>
              </td>
              <td>
                <span class="position-badge">{{ employee.position }}</span>
              </td>
              <td class="salary">{{ formatCurrency(employee.base_salary) }}</td>
              <td class="gross-salary">{{ formatCurrency(employee.gross_salary) }}</td>
              <td class="net-pay">{{ formatCurrency(employee.net_pay) }}</td>
              <td>
                <span v-if="employee.payroll_status" :class="['status', employee.payroll_status.toLowerCase()]">
                  <span class="status-dot"></span>
                  {{ employee.payroll_status.charAt(0).toUpperCase() + employee.payroll_status.slice(1) }}
                </span>
                <span v-else class="status unknown">
                  <span class="status-dot"></span>
                  Not Set
                </span>
              </td>
              <td>{{ formatDate(employee.payPeriod) }}</td>
              <td>
                <div class="action-buttons">
                  <button
                    @click="viewDetails(employee.id)"
                    class="action-btn view"
                    title="View Details"
                  >
                    👁️
                  </button>
                  <button
                    @click="editAdjustments(employee)"
                    class="action-btn adjust"
                    title="Add Adjustments"
                  >
                    ⚙️
                  </button>
                  <button
                    v-if="employee.payroll_status"
                    @click="toggleStatus(employee.id)"
                    :class="['action-btn', employee.payroll_status.toLowerCase()]"
                    :title="employee.payroll_status === 'pending' ? 'Mark as Paid' : 'Mark as Pending'"
                  >
                    {{ employee.payroll_status === 'pending' ? '✓' : '↻' }}
                  </button>
                  <button
                    v-else
                    @click="setInitialStatus(employee.id)"
                    class="action-btn unknown"
                    title="Set Status"
                  >
                    ⚙️
                  </button>
                  <button
                    @click="deleteEmployee(employee.id)"
                    class="action-btn delete"
                    title="Delete Employee"
                  >
                    🗑️
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        
        <!-- Pagination Controls -->
        <div v-if="filteredEmployees.length > 0" class="pagination-controls">
          <div class="pagination-buttons">
            <button 
              @click="previousPage" 
              :disabled="pagination.currentPage === 1"
              class="pagination-btn"
            >
              Previous
            </button>
            <span class="page-numbers">
              Page {{ pagination.currentPage }} of {{ pagination.totalPages }}
            </span>
            <button 
              @click="nextPage" 
              :disabled="pagination.currentPage === pagination.totalPages"
              class="pagination-btn"
            >
              Next
            </button>
          </div>
          
          <div class="page-size-selector">
            <label for="pageSize">Show:</label>
            <select 
              id="pageSize" 
              v-model="pagination.pageSize" 
              @change="onPageSizeChange"
              class="page-size-select"
            >
              <option value="10">10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
            <span>per page</span>
          </div>
        </div>
        
        <!-- Bulk Actions -->
        <div v-if="selectedCount > 0" class="bulk-actions">
          <span class="selected-count">{{ selectedCount }} employee(s) selected</span>
          <button @click="showBulkAdjustments" class="bulk-btn adjust">Bulk Adjustments</button>
          <button @click="bulkMarkPaid" class="bulk-btn paid">Mark Selected as Paid</button>
          <button @click="bulkMarkPending" class="bulk-btn pending">Mark Selected as Pending</button>
          <button @click="clearSelection" class="bulk-btn clear">Clear Selection</button>
        </div>
      </div>
    </div>
    <!-- Payslip Detail Modal -->
    <PayslipDetailModal
      :visible="showModal"
      :employee-id="selectedEmployeeId"
      :payroll-period="payrollPeriod"
      @close="showModal = false"
    />
    <!-- Preview & Adjustments Modal -->
    <div v-if="showPreviewModal" class="modal-overlay">
      <div class="modal-content large-modal">
        <div class="modal-header">
          <h2>Payroll Preview & Adjustments</h2>
          <button @click="closePreview" class="close-btn">×</button>
        </div>
       
        <div class="preview-content">
          <!-- Summary Section -->
          <div class="preview-summary">
            <h3>Payroll Summary</h3>
            <div class="summary-grid">
              <div class="summary-item">
                <span class="label">Total Employees:</span>
                <span class="value">{{ previewEmployees.length }}</span>
              </div>
              <div class="summary-item">
                <span class="label">Total Gross:</span>
                <span class="value">{{ formatCurrency(previewTotalGross) }}</span>
              </div>
              <div class="summary-item">
                <span class="label">Total Net Pay:</span>
                <span class="value">{{ formatCurrency(previewTotalNet) }}</span>
              </div>
              <div class="summary-item">
                <span class="label">Total Adjustments:</span>
                <span class="value">{{ formatCurrency(previewTotalAdjustments) }}</span>
              </div>
            </div>
          </div>
          <!-- Employee Adjustments -->
          <div class="adjustments-section">
            <h3>Employee Adjustments</h3>
            <div class="adjustments-list">
              <div
                v-for="emp in previewEmployees"
                :key="emp.id"
                class="employee-adjustment"
                :class="{ 'has-adjustments': emp.adjustments && (emp.adjustments.bonuses > 0 || emp.adjustments.deductions > 0) }"
              >
                <div class="employee-header">
                  <div class="employee-info">
                    <strong>{{ emp.name }}</strong>
                    <span class="position">{{ emp.position }}</span>
                    <span class="business-info" v-if="emp.business_name && emp.business_name !== 'No Business'">
                      {{ emp.business_name }}
                    </span>
                  </div>
                  <div class="salary-info">
                    <span>Base: {{ formatCurrency(emp.base_salary) }}</span>
                    <span>Net: {{ formatCurrency(calculateAdjustedNet(emp)) }}</span>
                  </div>
                </div>
               
                <div class="adjustment-controls">
                  <div class="adjustment-group">
                    <label>Overtime Bonus:</label>
                    <input
                      type="number"
                      v-model="emp.adjustments.overtime_bonus"
                      @input="updateAdjustments(emp)"
                      placeholder="0.00"
                      min="0"
                      step="0.01"
                    >
                  </div>
                 
                  <div class="adjustment-group">
                    <label>Other Bonuses:</label>
                    <input
                      type="number"
                      v-model="emp.adjustments.other_bonuses"
                      @input="updateAdjustments(emp)"
                      placeholder="0.00"
                      min="0"
                      step="0.01"
                    >
                  </div>
                 
                  <div class="adjustment-group">
                    <label>Loan Deductions:</label>
                    <input
                      type="number"
                      v-model="emp.adjustments.loan_deductions"
                      @input="updateAdjustments(emp)"
                      placeholder="0.00"
                      min="0"
                      step="0.01"
                    >
                  </div>
                 
                  <div class="adjustment-group">
                    <label>Advance Deductions:</label>
                    <input
                      type="number"
                      v-model="emp.adjustments.advance_deductions"
                      @input="updateAdjustments(emp)"
                      placeholder="0.00"
                      min="0"
                      step="0.01"
                    >
                  </div>
                 
                  <div class="adjustment-totals">
                    <div class="total-item">
                      <span>Total Bonuses:</span>
                      <span class="positive">{{ formatCurrency(emp.adjustments.bonuses) }}</span>
                    </div>
                    <div class="total-item">
                      <span>Total Deductions:</span>
                      <span class="negative">{{ formatCurrency(emp.adjustments.deductions) }}</span>
                    </div>
                    <div class="total-item net-change">
                      <span>Net Change:</span>
                      <span :class="getNetChangeClass(emp)">{{ formatCurrency(calculateNetChange(emp)) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-actions">
          <button @click="closePreview" class="btn-secondary">Cancel</button>
          <button @click="saveAdjustments" class="btn-primary">Save Adjustments</button>
          <button @click="processWithAdjustments" class="btn-success">
            <span v-if="processing" class="spinner-small"></span>
            Process Payroll with Adjustments
          </button>
        </div>
      </div>
    </div>
    <!-- Individual Adjustment Modal -->
    <div v-if="showAdjustmentModal" class="modal-overlay">
      <div class="modal-content">
        <div class="modal-header">
          <h2>Adjustments for {{ adjustedEmployee?.name }}</h2>
          <button @click="closeAdjustmentModal" class="close-btn">×</button>
        </div>
       
        <div class="adjustment-form">
          <div class="form-group">
            <label>Overtime Bonus:</label>
            <input
              type="number"
              v-model="currentAdjustments.overtime_bonus"
              placeholder="0.00"
              min="0"
              step="0.01"
            >
          </div>
         
          <div class="form-group">
            <label>Other Bonuses:</label>
            <input
              type="number"
              v-model="currentAdjustments.other_bonuses"
              placeholder="0.00"
              min="0"
              step="0.01"
            >
          </div>
         
          <div class="form-group">
            <label>Loan Deductions:</label>
            <input
              type="number"
              v-model="currentAdjustments.loan_deductions"
              placeholder="0.00"
              min="0"
              step="0.01"
            >
          </div>
         
          <div class="form-group">
            <label>Advance Deductions:</label>
            <input
              type="number"
              v-model="currentAdjustments.advance_deductions"
              placeholder="0.00"
              min="0"
              step="0.01"
            >
          </div>
         
          <div class="adjustment-summary">
            <div class="summary-row">
              <span>Total Bonuses:</span>
              <span class="positive">{{ formatCurrency(currentAdjustments.bonuses) }}</span>
            </div>
            <div class="summary-row">
              <span>Total Deductions:</span>
              <span class="negative">{{ formatCurrency(currentAdjustments.deductions) }}</span>
            </div>
            <div class="summary-row net-change">
              <span>Net Change:</span>
              <span :class="getCurrentNetChangeClass()">
                {{ formatCurrency(currentAdjustments.bonuses - currentAdjustments.deductions) }}
              </span>
            </div>
          </div>
        </div>
        <div class="modal-actions">
          <button @click="closeAdjustmentModal" class="btn-secondary">Cancel</button>
          <button @click="saveEmployeeAdjustments" class="btn-primary">Save Adjustments</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import { useAuthStore } from '@/stores/auth'
import PayslipDetailModal from './PayslipDetailModal.vue'

export default {
  name: 'PayrollProcessing',
  components: {
    PayslipDetailModal
  },
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  data() {
    return {
      pageName: 'Payroll Processing Dashboard',
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
      showModal: false,
      selectedEmployeeId: null,
      showPreviewModal: false,
      showAdjustmentModal: false,
      previewEmployees: [],
      adjustedEmployee: null,
      currentAdjustments: {
        overtime_bonus: 0,
        other_bonuses: 0,
        loan_deductions: 0,
        advance_deductions: 0,
        bonuses: 0,
        deductions: 0
      },
      // Pagination data
      pagination: {
        currentPage: 1,
        pageSize: 10,
        totalPages: 0,
        startIndex: 0,
        endIndex: 0
      }
    }
  },
  computed: {
    pendingCount() {
      return this.employees.filter(emp => emp.payroll_status === 'pending').length
    },
    paidCount() {
      return this.employees.filter(emp => emp.payroll_status === 'paid').length
    },
    totalPayroll() {
      return this.employees
        .filter(emp => emp.payroll_status === 'pending')
        .reduce((sum, emp) => sum + (emp.net_pay || 0), 0)
    },
    filteredEmployees() {
      let filtered = this.employees
      if (this.filterStatus !== 'all') {
        filtered = filtered.filter(emp => emp.payroll_status === this.filterStatus)
      }
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(emp =>
          emp.name.toLowerCase().includes(query) ||
          emp.position.toLowerCase().includes(query) ||
          emp.id.toString().includes(query) ||
          (emp.email && emp.email.toLowerCase().includes(query)) ||
          (emp.business_name && emp.business_name.toLowerCase().includes(query))
        )
      }
      return filtered
    },
    // Paginated employees for current page
    paginatedEmployees() {
      this.updatePagination()
      return this.filteredEmployees.slice(
        this.pagination.startIndex,
        this.pagination.endIndex
      )
    },
    selectedCount() {
      return this.employees.filter(emp => emp.selected).length
    },
    allSelected() {
      return this.filteredEmployees.length > 0 &&
             this.filteredEmployees.every(emp => emp.selected)
    },
    previewTotalGross() {
      return this.previewEmployees.reduce((sum, emp) => sum + (emp.gross_salary || 0), 0)
    },
    previewTotalNet() {
      return this.previewEmployees.reduce((sum, emp) => sum + this.calculateAdjustedNet(emp), 0)
    },
    previewTotalAdjustments() {
      return this.previewEmployees.reduce((sum, emp) => {
        const adjustments = emp.adjustments || {}
        return sum + (adjustments.bonuses || 0) - (adjustments.deductions || 0)
      }, 0)
    }
  },
  watch: {
    // Reset to first page when search query changes
    searchQuery() {
      this.pagination.currentPage = 1
    },
    
    // Reset to first page when filter changes
    filterStatus() {
      this.pagination.currentPage = 1
    },
    
    // Reset to first page when filtered employees change
    filteredEmployees() {
      this.pagination.currentPage = 1
      this.updatePagination()
    }
  },
  async mounted() {
    // Initialize businesses first for admin users
    if (this.authStore.isAdmin) {
      await this.fetchBusinesses()
    }
    await this.initializePayrollPeriod()
    await this.fetchEmployees()
  },
  methods: {
    // Fetch businesses method
    async fetchBusinesses() {
      try {
        const response = await axios.get('/api/admin/businesses')
        this.businesses = response.data.data || []
        console.log('Businesses fetched:', this.businesses)
      } catch (error) {
        console.error('Failed to fetch businesses:', error)
        this.$notify({
          type: 'error',
          title: 'Error',
          text: 'Failed to load businesses'
        })
      }
    },
    
    getBusinessName(businessId) {
      const business = this.businesses.find(b => b.id === businessId)
      return business ? business.name : 'Unknown Business'
    },
    
    onBusinessFilterChange() {
      this.pagination.currentPage = 1
      this.fetchEmployees()
    },
    
    // Update the fetchEmployees method to include business filter
    async fetchEmployees() {
      this.loading = true
      this.error = null
      try {
        const params = {
          payroll_period: this.payrollPeriod,
          start_date: this.startDate,
          end_date: this.endDate,
          per_page: 1000
        }
        
        // Add business_id filter if selected
        if (this.selectedBusinessId) {
          params.business_id = this.selectedBusinessId
        }
        
        console.log('Fetching payroll data with params:', params)
        
        const response = await axios.get('/api/admin/payroll/employees-summary', { params })
        
        this.employees = response.data.data.map(emp => ({
          id: emp.id,
          name: emp.name,
          email: emp.email,
          position: emp.position || emp.department || 'Unassigned',
          department: emp.department || 'N/A',
          business_id: emp.business_id,
          business_name: emp.business_name || 'No Business',
          base_salary: emp.base_salary || 0,
          gross_salary: emp.gross_salary || 0,
          net_pay: emp.net_pay || 0,
          payroll_status: emp.payroll_status || 'pending',
          payPeriod: emp.pay_period || this.payrollPeriod,
          employee_record: emp,
          selected: false,
          payslip_data: emp.payslip_data,
          adjustments: emp.adjustments || {
            overtime_bonus: 0,
            other_bonuses: 0,
            loan_deductions: 0,
            advance_deductions: 0,
            bonuses: 0,
            deductions: 0
          }
        }))
        
        console.log('Employees loaded:', this.employees.length)
        console.log('Sample employee:', this.employees[0])
        
        // Initialize pagination after data is loaded
        this.updatePagination()
        
      } catch (err) {
        console.error('❌ Failed to load payroll data:', err)
        this.handleError(err)
      } finally {
        this.loading = false
      }
    },
    
    // Pagination methods
    updatePagination() {
      const totalItems = this.filteredEmployees.length
      this.pagination.totalPages = Math.ceil(totalItems / this.pagination.pageSize)
      
      // Ensure current page is valid
      if (this.pagination.currentPage > this.pagination.totalPages) {
        this.pagination.currentPage = Math.max(1, this.pagination.totalPages)
      }
      
      this.pagination.startIndex = (this.pagination.currentPage - 1) * this.pagination.pageSize
      this.pagination.endIndex = Math.min(
        this.pagination.startIndex + this.pagination.pageSize,
        totalItems
      )
    },
    
    nextPage() {
      if (this.pagination.currentPage < this.pagination.totalPages) {
        this.pagination.currentPage++
      }
    },
    
    previousPage() {
      if (this.pagination.currentPage > 1) {
        this.pagination.currentPage--
      }
    },
    
    onPageSizeChange() {
      this.pagination.currentPage = 1
      this.updatePagination()
    },
    
    updateDateRange() {
      if (this.payrollPeriod) {
        const [year, month] = this.payrollPeriod.split('-')
        this.startDate = `${year}-${month}-01`
     
        const lastDate = new Date(year, parseInt(month), 0)
        this.endDate = `${year}-${month}-${lastDate.getDate().toString().padStart(2, '0')}`
      }
    },
   
    async refreshPayrollData() {
      await this.fetchEmployees()
    },
   
    initializePayrollPeriod() {
      const now = new Date()
      const year = now.getFullYear()
      const monthNum = now.getMonth() + 1
      const month = monthNum.toString().padStart(2, '0')
   
      this.payrollPeriod = `${year}-${month}`
      this.updateDateRange()
    },
   
    getPayrollDates() {
      const dates = {
        payroll_period: this.payrollPeriod,
        start_date: this.startDate,
        end_date: this.endDate
      }
      return dates
    },
   
    preparePayrollPayload(employeeIds) {
      const processedEmployeeIds = employeeIds.map(id => parseInt(id)).filter(id => !isNaN(id))
      const payload = {
        employee_ids: processedEmployeeIds,
        ...this.getPayrollDates()
      }
      return payload
    },
    
    // ... rest of your existing methods remain the same
    showPreview() {
      const pendingEmployees = this.employees.filter(emp =>
        emp.payroll_status === 'pending' && emp.selected
      )
     
      if (pendingEmployees.length === 0) {
        this.showError('Please select pending employees to preview.')
        return
      }
      this.previewEmployees = pendingEmployees.map(emp => ({
        ...emp,
        adjustments: {
          overtime_bonus: 0,
          other_bonuses: 0,
          loan_deductions: 0,
          advance_deductions: 0,
          bonuses: 0,
          deductions: 0,
          ...(emp.adjustments || {})
        }
      }))
     
      this.showPreviewModal = true
    },
    
    editAdjustments(employee) {
      this.adjustedEmployee = employee
      this.currentAdjustments = {
        overtime_bonus: employee.adjustments?.overtime_bonus || 0,
        other_bonuses: employee.adjustments?.other_bonuses || 0,
        loan_deductions: employee.adjustments?.loan_deductions || 0,
        advance_deductions: employee.adjustments?.advance_deductions || 0,
        bonuses: employee.adjustments?.bonuses || 0,
        deductions: employee.adjustments?.deductions || 0
      }
      this.showAdjustmentModal = true
    },
    
    closeAdjustmentModal() {
      this.showAdjustmentModal = false
      this.adjustedEmployee = null
      this.currentAdjustments = {
        overtime_bonus: 0,
        other_bonuses: 0,
        loan_deductions: 0,
        advance_deductions: 0,
        bonuses: 0,
        deductions: 0
      }
    },
    
    updateAdjustments(employee) {
      employee.adjustments.bonuses =
        (parseFloat(employee.adjustments.overtime_bonus) || 0) +
        (parseFloat(employee.adjustments.other_bonuses) || 0)
     
      employee.adjustments.deductions =
        (parseFloat(employee.adjustments.loan_deductions) || 0) +
        (parseFloat(employee.adjustments.advance_deductions) || 0)
    },
    
    calculateAdjustedNet(employee) {
      const baseNet = employee.net_pay || 0
      const adjustments = employee.adjustments || {}
      const netChange = (adjustments.bonuses || 0) - (adjustments.deductions || 0)
      return baseNet + netChange
    },
    
    calculateNetChange(employee) {
      const adjustments = employee.adjustments || {}
      return (adjustments.bonuses || 0) - (adjustments.deductions || 0)
    },
    
    getNetChangeClass(employee) {
      const netChange = this.calculateNetChange(employee)
      return netChange >= 0 ? 'positive' : 'negative'
    },
    
    getCurrentNetChangeClass() {
      const netChange = this.currentAdjustments.bonuses - this.currentAdjustments.deductions
      return netChange >= 0 ? 'positive' : 'negative'
    },
    
    saveEmployeeAdjustments() {
      if (this.adjustedEmployee) {
        if (!this.adjustedEmployee.adjustments) {
          this.adjustedEmployee.adjustments = {}
        }
       
        Object.assign(this.adjustedEmployee.adjustments, this.currentAdjustments)
        this.updateAdjustments(this.adjustedEmployee)
       
        this.showSuccess('Adjustments saved successfully!')
        this.closeAdjustmentModal()
      }
    },
    
    saveAdjustments() {
      this.previewEmployees.forEach(previewEmp => {
        const mainEmp = this.employees.find(emp => emp.id === previewEmp.id)
        if (mainEmp) {
          mainEmp.adjustments = { ...previewEmp.adjustments }
        }
      })
     
      this.showSuccess('All adjustments saved successfully!')
      this.closePreview()
    },
    
    async processWithAdjustments() {
      this.processing = true
      try {
        const payload = {
          employee_ids: this.previewEmployees.map(emp => emp.id),
          adjustments: this.previewEmployees.reduce((acc, emp) => {
            acc[emp.id] = emp.adjustments
            return acc
          }, {}),
          ...this.getPayrollDates()
        }
        const response = await axios.post('/api/admin/payroll/process', payload)
        await this.fetchEmployees()
        this.showSuccess('Payroll processed successfully with adjustments!')
        this.closePreview()
      } catch (err) {
        console.error('❌ Payroll processing with adjustments failed:', err)
        this.handlePayrollError(err)
      } finally {
        this.processing = false
      }
    },
    
    showBulkAdjustments() {
      const selectedEmployees = this.employees.filter(emp => emp.selected)
      if (selectedEmployees.length === 0) {
        this.showError('Please select employees for bulk adjustments.')
        return
      }
     
      this.previewEmployees = selectedEmployees.map(emp => ({
        ...emp,
        adjustments: {
          overtime_bonus: 0,
          other_bonuses: 0,
          loan_deductions: 0,
          advance_deductions: 0,
          bonuses: 0,
          deductions: 0,
          ...(emp.adjustments || {})
        }
      }))
     
      this.showPreviewModal = true
    },
    
    async processPayroll() {
      const pendingEmployees = this.employees.filter(e => e.payroll_status === 'pending')
      if (pendingEmployees.length === 0) {
        this.showError('No pending payroll to process.')
        return
      }
      if (!confirm(`Process payroll for ${pendingEmployees.length} pending employee(s)?`)) {
        return
      }
      this.processing = true
      try {
        const pendingIds = pendingEmployees.map(e => e.id)
        const payload = {
          ...this.preparePayrollPayload(pendingIds),
          adjustments: pendingEmployees.reduce((acc, emp) => {
            if (emp.adjustments) {
              acc[emp.id] = emp.adjustments
            }
            return acc
          }, {})
        }
        const response = await axios.post('/api/admin/payroll/process', payload)
        await this.fetchEmployees()
        this.showSuccess('Payroll processed successfully!')
      } catch (err) {
        console.error('❌ Payroll processing error:', err)
        this.handlePayrollError(err)
      } finally {
        this.processing = false
      }
    },
    
    async bulkMarkPaid() {
      const selectedEmployees = this.employees.filter(e => e.selected && e.payroll_status)
      const selectedIds = selectedEmployees.map(e => e.id)
      if (selectedIds.length === 0) {
        this.showError('No employees with payroll status selected.')
        return
      }
      if (!confirm(`Mark ${selectedIds.length} employee(s) as paid?`)) {
        return
      }
      try {
        const payload = this.preparePayrollPayload(selectedIds)
        const response = await axios.post('/api/admin/payroll/process', payload)
        await this.fetchEmployees()
        this.showSuccess(`${selectedIds.length} employee(s) marked as paid.`)
      } catch (err) {
        console.error('❌ Bulk mark paid error:', err)
        this.handlePayrollError(err)
      }
    },
    
    async bulkMarkPending() {
      const selectedEmployees = this.employees.filter(e => e.selected && e.payroll_status)
      const selectedIds = selectedEmployees.map(e => e.id)
      if (selectedIds.length === 0) {
        this.showError('No employees with payroll status selected.')
        return
      }
      if (!confirm(`Mark ${selectedIds.length} employee(s) as pending?`)) {
        return
      }
      try {
        const payload = {
          employee_ids: selectedIds,
          status: 'pending',
          ...this.getPayrollDates()
        }
        const response = await axios.post('/api/admin/payroll/update-status', payload)
        await this.fetchEmployees()
        this.showSuccess(`${selectedIds.length} employee(s) marked as pending.`)
      } catch (err) {
        console.error('❌ Bulk mark pending error:', err)
        this.handlePayrollError(err)
      }
    },
    
    async toggleStatus(employeeId) {
      const employee = this.employees.find(e => e.id === employeeId)
      if (!employee || !employee.payroll_status) {
        this.showError('Cannot toggle status for employee without payroll status')
        return
      }
      const originalStatus = employee.payroll_status
      const newStatus = originalStatus === 'pending' ? 'paid' : 'pending'
     
      try {
        let payload
        if (newStatus === 'paid') {
          payload = this.preparePayrollPayload([employeeId])
          await axios.post('/api/admin/payroll/process', payload)
        } else {
          payload = {
            employee_ids: [employeeId],
            status: newStatus,
            ...this.getPayrollDates()
          }
          await axios.post('/api/admin/payroll/update-status', payload)
        }
     
        await this.fetchEmployees()
        this.showSuccess(`Employee marked as ${newStatus}.`)
      } catch (err) {
        console.error('❌ Toggle status error:', err)
        this.handlePayrollError(err)
      }
    },
    
    async setInitialStatus(employeeId) {
      const employee = this.employees.find(e => e.id === employeeId)
      if (!employee) return
      const status = confirm('Set initial status as Pending?\n\nClick OK for Pending, Cancel for Paid')
        ? 'pending'
        : 'paid'
     
      try {
        let payload
        if (status === 'paid') {
          payload = this.preparePayrollPayload([employeeId])
          await axios.post('/api/admin/payroll/process', payload)
        } else {
          payload = {
            employee_ids: [employeeId],
            status: status,
            ...this.getPayrollDates()
          }
          await axios.post('/api/admin/payroll/update-status', payload)
        }
     
        await this.fetchEmployees()
        this.showSuccess(`Employee status set to ${status}.`)
      } catch (err) {
        console.error('❌ Set initial status error:', err)
        this.handlePayrollError(err)
      }
    },
    
    getInitials(name) {
      if (!name || name === '—' || name === 'Unknown Employee') return '??'
      return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2)
    },
    
    getAvatarColor(name) {
      const colors = [
        'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
        'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
        'linear-gradient(135deg, #30cfd0 0%, #330867 100%)'
      ]
      const index = name.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0) % colors.length
      return colors[index]
    },
    
    addEmployee() {
      this.$router.push({ name: 'admin.employees.create' })
    },
    
    toggleSelectAll() {
      const newState = !this.allSelected
      this.filteredEmployees.forEach(emp => {
        emp.selected = newState
      })
    },
    
    clearSelection() {
      this.employees.forEach(emp => {
        emp.selected = false
      })
    },
    
    viewDetails(id) {
      const employee = this.employees.find(e => e.id === id)
      this.selectedEmployeeId = id
      this.showModal = true
    },
    
    async deleteEmployee(id) {
      const employee = this.employees.find(e => e.id === id)
      if (!confirm(`Delete ${employee?.name || 'this employee'}? This cannot be undone.`)) {
        return
      }
      try {
        await axios.delete(`/api/admin/employees/${id}`)
        this.employees = this.employees.filter(e => e.id !== id)
        this.showSuccess('Employee removed successfully.')
      } catch (err) {
        console.error('❌ Delete employee error:', err)
        this.handleError(err)
      }
    },
    
    clearSearch() {
      this.searchQuery = ''
    },
    
    dismissError() {
      this.error = null
    },
    
    showSuccess(message) {
      this.successMessage = message
      setTimeout(() => {
        this.successMessage = null
      }, 5000)
    },
    
    showError(message) {
      this.error = message
      setTimeout(() => {
        this.error = null
      }, 5000)
    },
    
    formatCurrency(amount) {
      if (amount === null || amount === undefined) return '—'
      return new Intl.NumberFormat('en-ZM', {
        style: 'currency',
        currency: 'ZMW',
        minimumFractionDigits: 2
      }).format(amount)
    },
    
    formatDate(dateString) {
      if (!dateString) return '—'
      if (dateString.match(/^\d{4}-\d{2}$/)) {
        const [year, month] = dateString.split('-')
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
        return `${monthNames[parseInt(month) - 1]} ${year}`
      }
      const date = new Date(dateString)
      if (isNaN(date.getTime())) return '—'
      return date.toLocaleDateString('en-ZM', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },
    
    handlePayrollError(err) {
      if (err.response?.status === 422) {
        const responseData = err.response.data
        let errorMessage = 'Validation failed: '
     
        if (responseData.errors) {
          const errors = responseData.errors
          const errorList = []
          for (const [field, messages] of Object.entries(errors)) {
            errorList.push(`${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}`)
          }
          errorMessage += errorList.join('; ')
        } else if (responseData.message) {
          errorMessage = responseData.message
        } else {
          errorMessage += 'Please check the data and try again.'
        }
     
        this.showError(errorMessage)
      } else {
        this.handleError(err)
      }
    },
    
    handleError(err) {
      let message = 'An unexpected error occurred.'
      if (err.response?.status === 401) {
        message = 'Your session has expired. Please log in again.'
        if (this.$store?.auth?.clearAuth) {
          this.$store.auth.clearAuth()
        }
        this.$router.push({ name: 'login' })
        return
      } else if (err.response?.status === 403) {
        message = 'You do not have permission to perform this action.'
      } else if (err.response?.status === 422) {
        return
      } else if (err.response?.data?.message) {
        message = err.response.data.message
      } else if (err.message) {
        message = err.message
      } else if (err.code === 'ERR_NETWORK') {
        message = 'Network error. Please check your connection.'
      }
      this.error = message
    }
  }
}
</script>

<style scoped>
/* Add business-specific styles */
.business-filter-section {
  background: white;
  padding: 1.5rem;
  border-radius: 16px;
  margin-bottom: 1.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.business-select {
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.95rem;
  width: 300px;
  max-width: 100%;
  transition: border-color 0.3s;
}

.business-select:focus {
  outline: none;
  border-color: #667eea;
}

.business-badge {
  margin-left: 1rem;
  padding: 0.375rem 0.875rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
}

.business-tag {
  display: inline-block;
  padding: 0.375rem 0.875rem;
  background: #eef2ff;
  color: #667eea;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 180px;
}

.business-tag-small {
  display: inline-block;
  padding: 0.125rem 0.5rem;
  background: #f5f3ff;
  color: #8b5cf6;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 500;
  margin-top: 0.25rem;
}

.business-info {
  display: inline-block;
  padding: 0.125rem 0.5rem;
  background: #f0f9ff;
  color: #0369a1;
  border-radius: 4px;
  font-size: 0.8rem;
  font-weight: 500;
  margin-left: 0.5rem;
}

.no-business {
  color: #9ca3af;
  font-style: italic;
  font-size: 0.85rem;
}

/* Update table column widths */
@media (min-width: 1200px) {
  .payroll-table th:nth-child(4),
  .payroll-table td:nth-child(4) {
    width: 180px;
  }
}

/* Pagination styles */
.pagination-info {
  padding: 1rem 1.5rem;
  background: #f8fafc;
  border-bottom: 1px solid #e5e7eb;
  color: #6b7280;
  font-size: 0.9rem;
  font-weight: 500;
}

.pagination-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  background: #f9fafb;
  border-top: 2px solid #e5e7eb;
  flex-wrap: wrap;
  gap: 1rem;
}

.pagination-buttons {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.pagination-btn {
  background: white;
  border: 1px solid #e2e8f0;
  color: #4a5568;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.pagination-btn:hover:not(:disabled) {
  background: #667eea;
  color: white;
  border-color: #667eea;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-numbers {
  color: #4a5568;
  font-weight: 500;
}

.page-size-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #718096;
  font-size: 0.9rem;
}

.page-size-select {
  padding: 0.25rem 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  background: white;
}

/* Responsive design for pagination */
@media (max-width: 768px) {
  .pagination-controls {
    flex-direction: column;
    gap: 1rem;
  }
  
  .pagination-buttons {
    justify-content: center;
  }
  
  .page-size-selector {
    justify-content: center;
  }
  
  .business-select {
    width: 100%;
  }
  
  .business-badge {
    margin-left: 0;
    margin-top: 0.5rem;
    display: inline-block;
  }
}

/* Your existing styles remain the same below */
.payroll-view {
  padding: 2rem;
  max-width: 1600px;
  margin: 0 auto;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  background: white;
  padding: 2rem;
  border-radius: 16px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
}

.title {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
  color: #1a202c;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  background-clip: text;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

.add-btn, .process-btn, .preview-btn {
  padding: 0.875rem 1.75rem;
  border: none;
  border-radius: 10px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-icon {
  font-size: 1.25rem;
  font-weight: bold;
}

.add-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.preview-btn {
  background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
  color: white;
}

.process-btn {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.process-btn:disabled, .preview-btn:disabled {
  background: #9ca3af;
  cursor: not-allowed;
  transform: none;
}

.add-btn:hover:not(:disabled),
.process-btn:hover:not(:disabled),
.preview-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
}

.spinner-small {
  width: 16px;
  height: 16px;
  border: 2px solid #ffffff40;
  border-top: 2px solid white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

.date-selection-section {
  background: white;
  padding: 1.5rem;
  border-radius: 16px;
  margin-bottom: 1.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.date-inputs {
  display: flex;
  gap: 1rem;
  align-items: end;
  flex-wrap: wrap;
}

.form-group {
  display: flex;
  flex-direction: column;
  min-width: 150px;
}

.form-label {
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.5rem;
  font-size: 0.875rem;
}

.date-input {
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: border-color 0.3s;
}

.date-input:focus {
  outline: none;
  border-color: #667eea;
}

.refresh-btn {
  padding: 0.75rem 1.5rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.3s;
  height: fit-content;
}

.refresh-btn:hover {
  background: #2563eb;
}

.warning-banner, .error-banner, .success-banner {
  padding: 1rem 1.5rem;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 500;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.warning-banner {
  background: #fffbeb;
  color: #92400e;
  border-left: 4px solid #f59e0b;
}

.error-banner {
  background: #fee2e2;
  color: #991b1b;
  border-left: 4px solid #dc2626;
}

.success-banner {
  background: #d1fae5;
  color: #065f46;
  border-left: 4px solid #10b981;
}

.dismiss-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: inherit;
  opacity: 0.7;
  transition: opacity 0.2s;
}

.dismiss-btn:hover {
  opacity: 1;
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.card {
  background: white;
  padding: 1.75rem;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  position: relative;
  overflow: hidden;
}

.card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.card-icon {
  font-size: 2.5rem;
  margin-bottom: 0.5rem;
}

.card h3 {
  margin: 0 0 0.75rem;
  font-size: 0.9rem;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 600;
}

.card .value {
  margin: 0;
  font-size: 2.25rem;
  font-weight: 700;
  color: #1a202c;
}

.card .value.pending {
  color: #f59e0b;
}

.card .value.success {
  color: #10b981;
}

.filters-section {
  background: white;
  padding: 1.5rem;
  border-radius: 16px;
  margin-bottom: 1.5rem;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  align-items: center;
}

.search-box {
  flex: 1;
  min-width: 250px;
}

.search-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-size: 0.95rem;
  transition: border-color 0.3s;
}

.search-input:focus {
  outline: none;
  border-color: #667eea;
}

.filter-buttons {
  display: flex;
  gap: 0.5rem;
}

.filter-btn {
  padding: 0.625rem 1.25rem;
  border: 2px solid #e5e7eb;
  background: white;
  border-radius: 10px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s;
  color: #6b7280;
}

.filter-btn.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-color: transparent;
}

.filter-btn:hover:not(.active) {
  border-color: #667eea;
  color: #667eea;
}

.content {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.empty-state {
  text-align: center;
  padding: 5rem 2rem;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.empty-state h2 {
  color: #1a202c;
  margin: 0 0 0.5rem;
  font-size: 1.75rem;
}

.empty-state p {
  color: #6b7280;
  font-size: 1.1rem;
  margin-bottom: 2rem;
}

.btn-primary, .btn-secondary {
  padding: 0.875rem 2rem;
  border: none;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-secondary {
  background: #e5e7eb;
  color: #374151;
}

.btn-primary:hover, .btn-secondary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.table-container {
  overflow-x: auto;
}

.payroll-table {
  width: 100%;
  border-collapse: collapse;
}

.payroll-table th,
.payroll-table td {
  padding: 1.25rem 1rem;
  text-align: left;
  border-bottom: 1px solid #f3f4f6;
}

.payroll-table th {
  background: #f9fafb;
  font-weight: 600;
  color: #374151;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-size: 0.8rem;
}

.payroll-table tbody tr {
  transition: background 0.2s;
}

.payroll-table tbody tr:hover {
  background: #f9fafb;
}

.payroll-table tbody tr.selected {
  background: #ede9fe;
}

.checkbox {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #667eea;
}

.employee-name {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.employee-avatar {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  font-weight: 700;
  flex-shrink: 0;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.employee-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.employee-info .name {
  font-weight: 600;
  color: #1a202c;
}

.employee-info .email {
  font-size: 0.85rem;
  color: #6b7280;
}

.position-badge {
  display: inline-block;
  padding: 0.375rem 0.875rem;
  background: #f3f4f6;
  border-radius: 8px;
  font-size: 0.85rem;
  font-weight: 500;
  color: #374151;
}

.salary, .gross-salary, .net-pay {
  font-weight: 600;
}

.salary {
  color: #059669;
}

.gross-salary {
  color: #3b82f6;
}

.net-pay {
  color: #059669;
}

.status {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.375rem 0.875rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 600;
}

.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  animation: pulse 2s infinite;
}

.status.paid {
  background: #d1fae5;
  color: #065f46;
}

.status.paid .status-dot {
  background: #10b981;
}

.status.pending {
  background: #fef3c7;
  color: #92400e;
}

.status.pending .status-dot {
  background: #f59e0b;
}

.status.unknown {
  background: #f3f4f6;
  color: #6b7280;
}

.status.unknown .status-dot {
  background: #9ca3af;
}

@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
  flex-wrap: nowrap;
}

.action-btn {
  padding: 0.5rem 0.875rem;
  border: none;
  border-radius: 8px;
  font-size: 1.1rem;
  cursor: pointer;
  transition: all 0.2s ease;
  color: white;
  min-width: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.action-btn.view {
  background: #3b82f6;
}

.action-btn.view:hover {
  background: #2563eb;
  transform: scale(1.05);
}

.action-btn.adjust {
  background: #8b5cf6;
}

.action-btn.adjust:hover {
  background: #7c3aed;
  transform: scale(1.05);
}

.action-btn.pending {
  background: #10b981;
}

.action-btn.pending:hover {
  background: #059669;
  transform: scale(1.05);
}

.action-btn.paid {
  background: #f59e0b;
}

.action-btn.paid:hover {
  background: #d97706;
  transform: scale(1.05);
}

.action-btn.delete {
  background: #ef4444;
}

.action-btn.delete:hover {
  background: #dc2626;
  transform: scale(1.05);
}

.action-btn.unknown {
  background: #6b7280;
}

.action-btn.unknown:hover {
  background: #4b5563;
  transform: scale(1.05);
}

.bulk-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem 1.5rem;
  background: #f9fafb;
  border-top: 2px solid #e5e7eb;
  flex-wrap: wrap;
}

.selected-count {
  font-weight: 600;
  color: #374151;
  margin-right: auto;
}

.bulk-btn {
  padding: 0.625rem 1.25rem;
  border: none;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  color: white;
}

.bulk-btn.adjust {
  background: #8b5cf6;
}

.bulk-btn.adjust:hover {
  background: #7c3aed;
}

.bulk-btn.paid {
  background: #10b981;
}

.bulk-btn.paid:hover {
  background: #059669;
}

.bulk-btn.pending {
  background: #f59e0b;
}

.bulk-btn.pending:hover {
  background: #d97706;
}

.bulk-btn.clear {
  background: #6b7280;
}

.bulk-btn.clear:hover {
  background: #4b5563;
}

.loading-state {
  padding: 4rem 2rem;
  text-align: center;
  color: #6b7280;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f4f6;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1.5rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Modal Styles - rest of your existing styles remain the same */
/* ... existing modal styles ... */

.fade-enter-active, .fade-leave-active {
  transition: all 0.3s ease;
}

.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Responsive Design */
@media (max-width: 1200px) {
  .summary-cards {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .payroll-view {
    padding: 1rem;
  }
 
  .header {
    flex-direction: column;
    gap: 1rem;
    padding: 1.5rem;
  }
 
  .title {
    font-size: 1.5rem;
    text-align: center;
  }
 
  .header-actions {
    flex-direction: column;
    width: 100%;
  }
 
  .add-btn, .process-btn, .preview-btn {
    width: 100%;
    justify-content: center;
  }
 
  .summary-cards {
    grid-template-columns: 1fr;
  }
 
  .filters-section {
    flex-direction: column;
    align-items: stretch;
  }
 
  .search-box {
    width: 100%;
  }
 
  .filter-buttons {
    flex-direction: column;
  }
 
  .filter-btn {
    width: 100%;
  }
 
  .payroll-table {
    font-size: 0.85rem;
  }
 
  .payroll-table th,
  .payroll-table td {
    padding: 0.75rem 0.5rem;
  }
 
  .employee-name {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
 
  .employee-avatar {
    width: 36px;
    height: 36px;
    font-size: 0.75rem;
  }
 
  .action-buttons {
    flex-direction: row;
    flex-wrap: nowrap;
  }
 
  .action-btn {
    padding: 0.5rem;
    min-width: auto;
    font-size: 1rem;
  }
 
  .bulk-actions {
    flex-direction: column;
    align-items: stretch;
  }
 
  .selected-count {
    margin-right: 0;
    text-align: center;
    margin-bottom: 0.5rem;
  }
 
  .bulk-btn {
    width: 100%;
  }
 
  .modal-content {
    margin: 1rem;
    max-height: calc(100vh - 2rem);
  }
 
  .adjustment-controls {
    grid-template-columns: 1fr;
  }
 
  .employee-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
 
  .salary-info {
    align-items: flex-start;
  }
 
  .modal-actions {
    flex-direction: column;
  }
}

@media (max-width: 480px) {
  .card {
    padding: 1.25rem;
  }
 
  .card .value {
    font-size: 1.75rem;
  }
 
  .card-icon {
    font-size: 2rem;
  }
 
  .table-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
 
  .payroll-table {
    min-width: 600px;
  }
}
</style>