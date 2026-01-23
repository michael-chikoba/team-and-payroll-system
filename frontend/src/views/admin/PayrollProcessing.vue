<template>
  <div class="payroll-view">
    <!-- Main Header -->
    <div class="page-header">
      <div class="header-content">
        <div class="header-left">
          <h1 class="page-title">
            <span class="title-icon">💰</span>
            {{ pageName }}
          </h1>
          <p class="page-subtitle">Manage and process employee payroll for the selected period</p>
        </div>
        <div class="header-right">
          <div class="action-buttons">
            <button @click="addEmployee" class="btn btn-outline">
              <i class="icon-add">+</i>
              Add Employee
            </button>
            <button
              @click="showPreview"
              class="btn btn-secondary"
              :disabled="processing || pendingCount === 0"
            >
              <i class="icon-preview">👁️</i>
              Preview & Adjust
            </button>
            <button
              @click="processPayroll"
              class="btn btn-primary"
              :disabled="processing || pendingCount === 0"
            >
              <i v-if="processing" class="spinner-small"></i>
              <i v-else class="icon-process">⚡</i>
              {{ processing ? 'Processing...' : 'Process Payroll' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Stats Bar -->
    <div class="quick-stats-bar">
      <div class="stats-container">
        <div class="stat-item" @click="filterStatus = 'all'">
          <div class="stat-value">{{ employees.length }}</div>
          <div class="stat-label">Total Employees</div>
        </div>
        <div class="stat-item" @click="filterStatus = 'pending'">
          <div class="stat-value pending">{{ pendingCount }}</div>
          <div class="stat-label">Pending</div>
        </div>
        <div class="stat-item" @click="filterStatus = 'paid'">
          <div class="stat-value success">{{ paidCount }}</div>
          <div class="stat-label">Paid</div>
        </div>
        <div class="stat-item">
          <div class="stat-value amount">{{ formatCurrency(totalPayroll) }}</div>
          <div class="stat-label">Total Amount</div>
        </div>
      </div>
    </div>

    <!-- Controls Section -->
    <div class="controls-section">
      <div class="controls-grid">
        <!-- Date Controls -->
        <div class="control-group">
          <label class="control-label">Payroll Period</label>
          <div class="date-controls">
            <input
              type="month"
              v-model="payrollPeriod"
              @change="updateDateRange"
              class="control-input"
            >
            <div class="date-range">
              <input
                type="date"
                v-model="startDate"
                class="control-input small"
              >
              <span class="date-separator">to</span>
              <input
                type="date"
                v-model="endDate"
                class="control-input small"
              >
            </div>
          </div>
        </div>

        <!-- Business Filter -->
        <div class="control-group" v-if="authStore.isAdmin">
          <label class="control-label">Business</label>
          <div class="select-wrapper">
            <select
              v-model="selectedBusinessId"
              @change="onBusinessFilterChange"
              class="control-select"
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
            <i class="select-arrow">▼</i>
          </div>
          <div v-if="selectedBusinessId" class="selected-business">
            <span class="business-indicator"></span>
            {{ getBusinessName(selectedBusinessId) }}
          </div>
        </div>

        <!-- Search -->
        <div class="control-group search-group">
          <label class="control-label">Search</label>
          <div class="search-wrapper">
            <i class="search-icon">🔍</i>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search employees..."
              class="search-input"
            />
            <button v-if="searchQuery" @click="clearSearch" class="clear-search">
              ×
            </button>
          </div>
        </div>

        <!-- Actions -->
        <div class="control-group actions-group">
          <label class="control-label">&nbsp;</label>
          <div class="action-buttons-small">
            <button @click="refreshPayrollData" class="btn btn-icon" title="Refresh">
              <i class="icon-refresh">🔄</i>
            </button>
            <div class="filter-buttons">
              <button
                @click="filterStatus = 'all'"
                :class="['filter-btn', { active: filterStatus === 'all' }]"
              >
                All
              </button>
              <button
                @click="filterStatus = 'pending'"
                :class="['filter-btn', { active: filterStatus === 'pending' }]"
              >
                Pending
              </button>
              <button
                @click="filterStatus = 'paid'"
                :class="['filter-btn', { active: filterStatus === 'paid' }]"
              >
                Paid
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Notifications -->
    <transition-group name="slide-down">
      <div v-if="taxConfigWarning" key="warn" class="notification warning">
        <div class="notification-content">
          <i class="notification-icon">⚠️</i>
          <span>{{ taxConfigWarning }}</span>
        </div>
        <button @click="taxConfigWarning = null" class="notification-close">×</button>
      </div>
      <div v-if="error" key="err" class="notification error">
        <div class="notification-content">
          <i class="notification-icon">❌</i>
          <span>{{ error }}</span>
        </div>
        <button @click="dismissError" class="notification-close">×</button>
      </div>
      <div v-if="successMessage" key="success" class="notification success">
        <div class="notification-content">
          <i class="notification-icon">✅</i>
          <span>{{ successMessage }}</span>
        </div>
        <button @click="successMessage = null" class="notification-close">×</button>
      </div>
    </transition-group>

    <!-- Main Content -->
    <div class="main-content">
      <div class="content-card">
        <!-- Table Header -->
        <div class="table-header">
          <div class="table-info">
            <span class="table-count">{{ filteredEmployees.length }} employees</span>
            <span class="table-subtext">Showing {{ pagination.startIndex + 1 }}-{{ pagination.endIndex }}</span>
          </div>
          <div class="table-actions">
            <div class="table-pagination">
              <button 
                @click="previousPage" 
                :disabled="pagination.currentPage === 1" 
                class="pagination-btn"
              >
                ←
              </button>
              <span class="pagination-info">
                Page {{ pagination.currentPage }} of {{ pagination.totalPages }}
              </span>
              <button 
                @click="nextPage" 
                :disabled="pagination.currentPage === pagination.totalPages" 
                class="pagination-btn"
              >
                →
              </button>
            </div>
            <div class="page-size">
              <select v-model="pagination.pageSize" @change="onPageSizeChange" class="page-size-select">
                <option value="10">10 rows</option>
                <option value="25">25 rows</option>
                <option value="50">50 rows</option>
                <option value="100">100 rows</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="loading-container">
          <div class="loading-spinner"></div>
          <p>Loading payroll data...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="filteredEmployees.length === 0" class="empty-container">
          <div class="empty-illustration">
            📊
          </div>
          <h3>No employees found</h3>
          <p>{{ searchQuery ? 'Try adjusting your search criteria' : 'Add employees to get started' }}</p>
          <button v-if="!searchQuery" @click="addEmployee" class="btn btn-primary">
            Add First Employee
          </button>
          <button v-else @click="clearSearch" class="btn btn-outline">
            Clear Search
          </button>
        </div>

        <!-- Employee Table -->
        <div v-else class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th class="checkbox-col">
                  <input
                    type="checkbox"
                    @change="toggleSelectAll"
                    :checked="allSelected"
                    class="checkbox"
                  />
                </th>
                <th class="employee-col">Employee</th>
                <th class="business-col">Business</th>
                <th class="position-col">Position</th>
                <th class="salary-col">Base Salary</th>
                <th class="salary-col">Gross</th>
                <th class="salary-col">Net Pay</th>
                <th class="status-col">Status</th>
                <th class="actions-col">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="employee in paginatedEmployees"
                :key="employee.id"
                :class="{ selected: employee.selected }"
              >
                <td class="checkbox-col">
                  <input
                    type="checkbox"
                    v-model="employee.selected"
                    class="checkbox"
                  />
                </td>
                <td class="employee-col">
                  <div class="employee-cell">
                    <div class="employee-avatar" :style="{ backgroundColor: getAvatarColor(employee.name) }">
                      {{ getInitials(employee.name) }}
                    </div>
                    <div class="employee-details">
                      <div class="employee-name">{{ employee.name }}</div>
                      <div class="employee-id">ID: #{{ employee.id }}</div>
                      <div class="employee-email">{{ employee.email || 'No email' }}</div>
                    </div>
                  </div>
                </td>
                <td class="business-col">
                  <div v-if="employee.business_name && employee.business_name !== 'No Business'" 
                       class="business-cell">
                    <span class="business-icon">🏢</span>
                    <span class="business-name">{{ employee.business_name }}</span>
                  </div>
                  <span v-else class="no-business">—</span>
                </td>
                <td class="position-col">
                  <span class="position-tag">{{ employee.position }}</span>
                </td>
                <td class="salary-col">
                  <div class="salary-amount">{{ formatCurrency(employee.base_salary) }}</div>
                </td>
                <td class="salary-col">
                  <div class="salary-amount gross">{{ formatCurrency(employee.gross_salary) }}</div>
                </td>
                <td class="salary-col">
                  <div class="salary-amount net">{{ formatCurrency(employee.net_pay) }}</div>
                </td>
                <td class="status-col">
                  <span :class="['status-badge', employee.payroll_status]">
                    <span class="status-dot"></span>
                    {{ formatStatus(employee.payroll_status) }}
                  </span>
                </td>
                <td class="actions-col">
                  <div class="table-actions-cell">
                    <button 
                      @click="viewDetails(employee.id)" 
                      class="action-icon view"
                      title="View Details"
                    >
                      👁️
                    </button>
                    <button 
                      @click="editAdjustments(employee)" 
                      class="action-icon adjust"
                      title="Adjustments"
                    >
                      ⚙️
                    </button>
                    <button 
                      @click="deleteEmployee(employee.id)" 
                      class="action-icon delete"
                      title="Delete"
                    >
                      🗑️
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Bulk Actions -->
          <div v-if="selectedCount > 0" class="bulk-actions-bar">
            <div class="bulk-info">
              <span class="selected-count">{{ selectedCount }} selected</span>
            </div>
            <div class="bulk-buttons">
              <button @click="showBulkAdjustments" class="btn btn-secondary btn-small">
                Bulk Adjustments
              </button>
              <button @click="bulkMarkPaid" class="btn btn-success btn-small">
                Mark as Paid
              </button>
              <button @click="clearSelection" class="btn btn-text btn-small">
                Clear Selection
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Preview & Adjustments Modal -->
    <div v-if="showPreviewModal" class="modal-overlay" @click.self="closePreview">
      <div class="modal-content large-modal">
        <div class="modal-header">
          <div class="modal-header-content">
            <h2>Payroll Preview & Adjustments</h2>
            <p>Review and modify payroll data before final processing</p>
          </div>
          <button @click="closePreview" class="modal-close-btn">×</button>
        </div>
        
        <div class="modal-body">
          <!-- Preview Stats -->
          <div class="preview-stats-grid">
            <div class="preview-stat">
              <div class="preview-stat-label">Employees</div>
              <div class="preview-stat-value">{{ previewEmployees.length }}</div>
            </div>
            <div class="preview-stat">
              <div class="preview-stat-label">Total Gross</div>
              <div class="preview-stat-value">{{ formatCurrency(previewTotalGross) }}</div>
            </div>
            <div class="preview-stat highlight">
              <div class="preview-stat-label">Net Payable</div>
              <div class="preview-stat-value">{{ formatCurrency(previewTotalNet) }}</div>
            </div>
            <div class="preview-stat">
              <div class="preview-stat-label">Adjustments</div>
              <div class="preview-stat-value" :class="previewTotalAdjustments >= 0 ? 'positive' : 'negative'">
                {{ formatCurrency(previewTotalAdjustments) }}
              </div>
            </div>
          </div>

          <!-- Employee Adjustments List -->
          <div class="adjustments-list">
            <div
              v-for="emp in previewEmployees"
              :key="emp.id"
              class="adjustment-item"
              :class="{ modified: calculateNetChange(emp) !== 0 }"
            >
              <div class="adjustment-item-header">
                <div class="employee-info">
                  <div class="employee-avatar-small">{{ getInitials(emp.name) }}</div>
                  <div>
                    <h4>{{ emp.name }}</h4>
                    <div class="employee-details">
                      <span class="position">{{ emp.position }}</span>
                      <span class="employee-id">#{{ emp.id }}</span>
                    </div>
                  </div>
                </div>
                <div class="salary-info">
                  <div class="salary-row">
                    <span>Base:</span>
                    <span>{{ formatCurrency(emp.base_salary) }}</span>
                  </div>
                  <div class="salary-row net">
                    <span>Net Pay:</span>
                    <strong>{{ formatCurrency(calculateAdjustedNet(emp)) }}</strong>
                  </div>
                </div>
              </div>

              <div class="adjustment-inputs">
                <div class="adjustment-col bonuses">
                  <div class="adjustment-col-title">
                    <span class="col-icon">➕</span>
                    Additions
                  </div>
                  <div class="input-group">
                    <label>Overtime</label>
                    <input
                      type="number"
                      v-model="emp.adjustments.overtime_bonus"
                      @input="updateAdjustments(emp)"
                      placeholder="0.00"
                      min="0"
                      step="0.01"
                    >
                  </div>
                  <div class="input-group">
                    <label>Bonus / Allowance</label>
                    <input
                      type="number"
                      v-model="emp.adjustments.other_bonuses"
                      @input="updateAdjustments(emp)"
                      placeholder="0.00"
                      min="0"
                      step="0.01"
                    >
                  </div>
                </div>

                <div class="adjustment-col deductions">
                  <div class="adjustment-col-title">
                    <span class="col-icon">➖</span>
                    Deductions
                  </div>
                  <div class="input-group">
                    <label>Loan Repayment</label>
                    <input
                      type="number"
                      v-model="emp.adjustments.loan_deductions"
                      @input="updateAdjustments(emp)"
                      placeholder="0.00"
                      min="0"
                      step="0.01"
                    >
                  </div>
                  <div class="input-group">
                    <label>Salary Advance</label>
                    <input
                      type="number"
                      v-model="emp.adjustments.advance_deductions"
                      @input="updateAdjustments(emp)"
                      placeholder="0.00"
                      min="0"
                      step="0.01"
                    >
                  </div>
                </div>
              </div>

              <div class="adjustment-summary">
                <span class="summary-label">Net Adjustment:</span>
                <span class="summary-value" :class="getNetChangeClass(emp)">
                  {{ calculateNetChange(emp) > 0 ? '+' : '' }}{{ formatCurrency(calculateNetChange(emp)) }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="closePreview" class="btn btn-secondary">Cancel</button>
          <div class="modal-footer-actions">
            <button @click="saveAdjustments" class="btn btn-outline">Save Only</button>
            <button @click="processWithAdjustments" class="btn btn-primary">
              <i v-if="processing" class="spinner-small"></i>
              Process Payroll
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Single Adjustment Modal -->
    <div v-if="showAdjustmentModal" class="modal-overlay" @click.self="closeAdjustmentModal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Adjustments for {{ adjustedEmployee?.name }}</h3>
          <button @click="closeAdjustmentModal" class="modal-close-btn">×</button>
        </div>
        
        <div class="modal-body">
          <div class="adjustment-form">
            <div class="form-group">
              <label>Overtime Bonus</label>
              <input 
                type="number" 
                v-model="currentAdjustments.overtime_bonus" 
                @input="updateAdjustments(currentAdjustments)"
                class="form-input"
                placeholder="0.00"
              >
            </div>
            <div class="form-group">
              <label>Other Bonuses</label>
              <input 
                type="number" 
                v-model="currentAdjustments.other_bonuses" 
                @input="updateAdjustments(currentAdjustments)"
                class="form-input"
                placeholder="0.00"
              >
            </div>
            <div class="form-group">
              <label>Loan Deductions</label>
              <input 
                type="number" 
                v-model="currentAdjustments.loan_deductions" 
                @input="updateAdjustments(currentAdjustments)"
                class="form-input"
                placeholder="0.00"
              >
            </div>
            <div class="form-group">
              <label>Advance Deductions</label>
              <input 
                type="number" 
                v-model="currentAdjustments.advance_deductions" 
                @input="updateAdjustments(currentAdjustments)"
                class="form-input"
                placeholder="0.00"
              >
            </div>
            
            <div class="net-change-summary">
              <span>Total Adjustments:</span>
              <span :class="getCurrentNetChangeClass()">
                {{ formatCurrency(currentAdjustments.bonuses - currentAdjustments.deductions) }}
              </span>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="closeAdjustmentModal" class="btn btn-secondary">Cancel</button>
          <button @click="saveEmployeeAdjustments" class="btn btn-primary">Save Changes</button>
        </div>
      </div>
    </div>
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
          String(emp.id).includes(query) ||
          (emp.business_name && emp.business_name.toLowerCase().includes(query))
        )
      }
      return filtered
    },
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
    searchQuery() { this.pagination.currentPage = 1 },
    filterStatus() { this.pagination.currentPage = 1 },
    filteredEmployees() { this.updatePagination() }
  },
  async mounted() {
    if (this.authStore.isAdmin) {
      await this.fetchBusinesses()
    }
    await this.initializePayrollPeriod()
    await this.fetchEmployees()
  },
  methods: {
    async fetchBusinesses() {
      try {
        const response = await axios.get('/api/admin/businesses')
        this.businesses = response.data.data || []
      } catch (error) {
        console.error('Failed to fetch businesses:', error)
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
        if (this.selectedBusinessId) params.business_id = this.selectedBusinessId
        
        const response = await axios.get('/api/admin/payroll/employees-summary', { params })
        
        this.employees = response.data.data.map(emp => ({
          id: emp.id,
          name: emp.name,
          email: emp.email,
          position: emp.position || emp.department || 'Unassigned',
          business_id: emp.business_id,
          business_name: emp.business_name || 'No Business',
          base_salary: emp.base_salary || 0,
          gross_salary: emp.gross_salary || 0,
          net_pay: emp.net_pay || 0,
          payroll_status: emp.payroll_status || 'pending',
          selected: false,
          adjustments: emp.adjustments || {
            overtime_bonus: 0,
            other_bonuses: 0,
            loan_deductions: 0,
            advance_deductions: 0,
            bonuses: 0,
            deductions: 0
          }
        }))
        this.updatePagination()
      } catch (err) {
        this.handleError(err)
      } finally {
        this.loading = false
      }
    },
    updatePagination() {
      const totalItems = this.filteredEmployees.length
      this.pagination.totalPages = Math.ceil(totalItems / this.pagination.pageSize) || 1
      if (this.pagination.currentPage > this.pagination.totalPages) {
        this.pagination.currentPage = this.pagination.totalPages
      }
      this.pagination.startIndex = (this.pagination.currentPage - 1) * this.pagination.pageSize
      this.pagination.endIndex = Math.min(this.pagination.startIndex + this.pagination.pageSize, totalItems)
    },
    nextPage() {
      if (this.pagination.currentPage < this.pagination.totalPages) this.pagination.currentPage++
    },
    previousPage() {
      if (this.pagination.currentPage > 1) this.pagination.currentPage--
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
      this.showSuccess('Data refreshed successfully!')
    },
    initializePayrollPeriod() {
      const now = new Date()
      this.payrollPeriod = `${now.getFullYear()}-${(now.getMonth() + 1).toString().padStart(2, '0')}`
      this.updateDateRange()
    },
    showPreview() {
      const pendingEmployees = this.employees.filter(emp =>
        emp.payroll_status === 'pending' && emp.selected
      )
      if (pendingEmployees.length === 0) {
        this.showError('Please select pending employees to preview.')
        return
      }
      this.previewEmployees = JSON.parse(JSON.stringify(pendingEmployees))
      this.showPreviewModal = true
    },
    closePreview() {
      this.showPreviewModal = false
      this.previewEmployees = []
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
    },
    updateAdjustments(employee) {
      const adj = employee.adjustments || {}
      adj.bonuses = (parseFloat(adj.overtime_bonus) || 0) + (parseFloat(adj.other_bonuses) || 0)
      adj.deductions = (parseFloat(adj.loan_deductions) || 0) + (parseFloat(adj.advance_deductions) || 0)
      if (employee === this.currentAdjustments) {
        this.currentAdjustments.bonuses = adj.bonuses
        this.currentAdjustments.deductions = adj.deductions
      }
    },
    calculateAdjustedNet(employee) {
      const baseNet = employee.net_pay || 0
      const adjustments = employee.adjustments || {}
      return baseNet + (adjustments.bonuses || 0) - (adjustments.deductions || 0)
    },
    calculateNetChange(employee) {
      const adjustments = employee.adjustments || {}
      return (adjustments.bonuses || 0) - (adjustments.deductions || 0)
    },
    getNetChangeClass(employee) {
      const change = this.calculateNetChange(employee)
      return change > 0 ? 'positive' : change < 0 ? 'negative' : ''
    },
    getCurrentNetChangeClass() {
      const change = this.currentAdjustments.bonuses - this.currentAdjustments.deductions
      return change > 0 ? 'positive' : change < 0 ? 'negative' : ''
    },
    saveEmployeeAdjustments() {
      if (this.adjustedEmployee) {
        if (!this.adjustedEmployee.adjustments) this.adjustedEmployee.adjustments = {}
        Object.assign(this.adjustedEmployee.adjustments, this.currentAdjustments)
        this.updateAdjustments({ adjustments: this.adjustedEmployee.adjustments })
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
      this.showSuccess('All adjustments saved locally!')
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
          payroll_period: this.payrollPeriod,
          start_date: this.startDate,
          end_date: this.endDate
        }
        await axios.post('/api/admin/payroll/process', payload)
        await this.fetchEmployees()
        this.showSuccess('Payroll processed successfully!')
        this.closePreview()
      } catch (err) {
        this.handlePayrollError(err)
      } finally {
        this.processing = false
      }
    },
    showBulkAdjustments() {
      const selectedEmployees = this.employees.filter(emp => emp.selected)
      if (selectedEmployees.length === 0) {
        this.showError('Select employees for bulk adjustments.')
        return
      }
      this.previewEmployees = JSON.parse(JSON.stringify(selectedEmployees))
      this.showPreviewModal = true
    },
    async processPayroll() {
      const pendingEmployees = this.employees.filter(e => e.payroll_status === 'pending')
      if (pendingEmployees.length === 0) return this.showError('No pending payroll.')
      if (!confirm(`Process ${pendingEmployees.length} pending employee(s)?`)) return
      
      this.processing = true
      try {
        const payload = {
          employee_ids: pendingEmployees.map(e => e.id),
          adjustments: pendingEmployees.reduce((acc, emp) => {
            if (emp.adjustments) acc[emp.id] = emp.adjustments
            return acc
          }, {}),
          payroll_period: this.payrollPeriod,
          start_date: this.startDate,
          end_date: this.endDate
        }
        await axios.post('/api/admin/payroll/process', payload)
        await this.fetchEmployees()
        this.showSuccess('Payroll processed successfully!')
      } catch (err) {
        this.handlePayrollError(err)
      } finally {
        this.processing = false
      }
    },
    async bulkMarkPaid() {
      const selectedEmployees = this.employees.filter(emp => emp.selected)
      if (selectedEmployees.length === 0) {
        this.showError('Please select employees to mark as paid.')
        return
      }
      
      try {
        await axios.post('/api/admin/payroll/mark-paid', {
          employee_ids: selectedEmployees.map(emp => emp.id),
          payroll_period: this.payrollPeriod
        })
        await this.fetchEmployees()
        this.showSuccess(`${selectedEmployees.length} employees marked as paid.`)
        this.clearSelection()
      } catch (err) {
        this.handleError(err)
      }
    },
    toggleSelectAll() {
      const newState = !this.allSelected
      this.filteredEmployees.forEach(emp => emp.selected = newState)
    },
    clearSelection() {
      this.employees.forEach(emp => emp.selected = false)
    },
    addEmployee() { this.$router.push({ name: 'admin.employees.create' }) },
    clearSearch() { this.searchQuery = '' },
    dismissError() { this.error = null },
    showSuccess(msg) { 
      this.successMessage = msg
      setTimeout(() => this.successMessage = null, 5000)
    },
    showError(msg) { 
      this.error = msg
      setTimeout(() => this.error = null, 5000)
    },
    handlePayrollError(err) {
      this.showError(err.response?.data?.message || 'Payroll processing failed')
    },
    handleError(err) { 
      this.showError(err.response?.data?.message || err.message || 'An error occurred')
    },
    formatCurrency(amount) {
      if (amount === null || amount === undefined) return '—'
      return new Intl.NumberFormat('en-ZM', { 
        style: 'currency', 
        currency: 'ZMW',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(amount)
    },
    getInitials(name) {
      if (!name) return '??'
      return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2)
    },
    getAvatarColor(name) {
      const colors = [
        '#667eea', '#764ba2', '#f093fb', '#f5576c',
        '#4facfe', '#00f2fe', '#43e97b', '#38f9d7',
        '#fa709a', '#fee140', '#a8edea', '#fed6e3'
      ]
      let hash = 0
      for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash)
      }
      return colors[Math.abs(hash) % colors.length]
    },
    formatStatus(status) {
      if (!status) return 'Unknown'
      return status.charAt(0).toUpperCase() + status.slice(1)
    },
    viewDetails(employeeId) {
      alert(`View details for employee #${employeeId}`)
      // this.selectedEmployeeId = employeeId
      // this.showModal = true
    },
    async deleteEmployee(id) {
      if (!confirm('Are you sure you want to delete this employee?')) return
      try {
        await axios.delete(`/api/admin/employees/${id}`)
        await this.fetchEmployees()
        this.showSuccess('Employee deleted successfully')
      } catch (err) {
        this.handleError(err)
      }
    }
  }
}
</script>

<style scoped>
.payroll-view {
  min-height: 100vh;
  background: #f8fafc;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
}

/* Header Styles */
.page-header {
  background: white;
  border-bottom: 1px solid #e2e8f0;
  padding: 1.5rem 2rem;
}

.header-content {
  max-width: 1400px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 2rem;
}

.header-left {
  flex: 1;
}

.page-title {
  font-size: 1.875rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.title-icon {
  font-size: 1.5rem;
}

.page-subtitle {
  color: #64748b;
  font-size: 0.875rem;
  margin: 0;
}

.header-right {
  flex-shrink: 0;
}

.action-buttons {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

/* Button Styles */
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  border: 1px solid transparent;
  white-space: nowrap;
}

.btn-primary {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

.btn-primary:hover:not(:disabled) {
  background: #2563eb;
  border-color: #2563eb;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-secondary {
  background: #f1f5f9;
  color: #475569;
  border-color: #e2e8f0;
}

.btn-secondary:hover:not(:disabled) {
  background: #e2e8f0;
  border-color: #cbd5e1;
}

.btn-outline {
  background: transparent;
  color: #475569;
  border-color: #cbd5e1;
}

.btn-outline:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #94a3b8;
}

.btn-success {
  background: #10b981;
  color: white;
  border-color: #10b981;
}

.btn-success:hover:not(:disabled) {
  background: #059669;
  border-color: #059669;
}

.btn-text {
  background: transparent;
  color: #64748b;
  border: none;
}

.btn-text:hover:not(:disabled) {
  color: #475569;
  background: #f1f5f9;
}

.btn-small {
  padding: 0.375rem 0.75rem;
  font-size: 0.75rem;
}

.btn-icon {
  padding: 0.5rem;
  width: 2.5rem;
  height: 2.5rem;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none !important;
}

.spinner-small {
  display: inline-block;
  width: 14px;
  height: 14px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  border-top-color: white;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* Quick Stats Bar */
.quick-stats-bar {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 1rem 2rem;
}

.stats-container {
  max-width: 1400px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.stat-item {
  cursor: pointer;
  transition: transform 0.2s;
}

.stat-item:hover {
  transform: translateY(-2px);
}

.stat-value {
  font-size: 1.875rem;
  font-weight: 700;
  margin-bottom: 0.25rem;
}

.stat-value.pending {
  color: #fbbf24;
}

.stat-value.success {
  color: #34d399;
}

.stat-value.amount {
  font-size: 1.5rem;
}

.stat-label {
  font-size: 0.875rem;
  opacity: 0.9;
}

/* Controls Section */
.controls-section {
  background: white;
  padding: 1.5rem 2rem;
  border-bottom: 1px solid #e2e8f0;
}

.controls-grid {
  max-width: 1400px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.control-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.control-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.control-input {
  padding: 0.625rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.875rem;
  transition: border-color 0.2s;
}

.control-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.control-input.small {
  width: 120px;
}

.date-controls {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.date-range {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.date-separator {
  color: #94a3b8;
  font-size: 0.875rem;
}

.select-wrapper {
  position: relative;
}

.control-select {
  width: 100%;
  padding: 0.625rem 2.5rem 0.625rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.875rem;
  background: white;
  appearance: none;
  cursor: pointer;
}

.select-arrow {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  color: #64748b;
}

.search-wrapper {
  position: relative;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}

.search-input {
  width: 100%;
  padding: 0.625rem 2.5rem 0.625rem 2.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.875rem;
}

.clear-search {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  font-size: 1.25rem;
  line-height: 1;
  padding: 0;
}

.action-buttons-small {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.filter-buttons {
  display: flex;
  background: #f1f5f9;
  border-radius: 6px;
  padding: 2px;
}

.filter-btn {
  padding: 0.375rem 0.75rem;
  border: none;
  background: transparent;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
}

.filter-btn.active {
  background: white;
  color: #3b82f6;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.selected-business {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.75rem;
  background: #f0f9ff;
  border: 1px solid #bae6fd;
  border-radius: 4px;
  font-size: 0.75rem;
  color: #0369a1;
  margin-top: 0.25rem;
}

.business-indicator {
  width: 6px;
  height: 6px;
  background: #0ea5e9;
  border-radius: 50%;
}

/* Notifications */
.notification {
  max-width: 1400px;
  margin: 1rem auto;
  padding: 1rem 1.25rem;
  border-radius: 8px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  animation: slideDown 0.3s ease;
}

.notification.warning {
  background: #fffbeb;
  border: 1px solid #fde68a;
  color: #92400e;
}

.notification.error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
}

.notification.success {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #166534;
}

.notification-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.notification-icon {
  font-size: 1.25rem;
}

.notification-close {
  background: none;
  border: none;
  color: inherit;
  font-size: 1.25rem;
  cursor: pointer;
  opacity: 0.7;
  padding: 0;
  line-height: 1;
}

.notification-close:hover {
  opacity: 1;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
}

.slide-down-enter-from,
.slide-down-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Main Content */
.main-content {
  max-width: 1400px;
  margin: 2rem auto;
  padding: 0 2rem;
}

.content-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.table-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8fafc;
}

.table-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.table-count {
  font-weight: 600;
  color: #1e293b;
}

.table-subtext {
  font-size: 0.875rem;
  color: #64748b;
}

.table-actions {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.table-pagination {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.pagination-btn {
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #e2e8f0;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  color: #475569;
  transition: all 0.2s;
}

.pagination-btn:hover:not(:disabled) {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-info {
  font-size: 0.875rem;
  color: #64748b;
}

.page-size {
  position: relative;
}

.page-size-select {
  padding: 0.375rem 1.75rem 0.375rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  font-size: 0.75rem;
  background: white;
  appearance: none;
  cursor: pointer;
}

/* Loading State */
.loading-container {
  padding: 4rem 2rem;
  text-align: center;
  color: #64748b;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #f1f5f9;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

/* Empty State */
.empty-container {
  padding: 4rem 2rem;
  text-align: center;
}

.empty-illustration {
  font-size: 4rem;
  margin-bottom: 1.5rem;
  opacity: 0.5;
}

.empty-container h3 {
  font-size: 1.25rem;
  color: #1e293b;
  margin: 0 0 0.5rem;
}

.empty-container p {
  color: #64748b;
  margin: 0 0 1.5rem;
}

/* Data Table */
.table-responsive {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: #f8fafc;
}

.data-table th {
  padding: 1rem 1rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
}

.data-table tbody tr {
  border-bottom: 1px solid #f1f5f9;
  transition: background-color 0.2s;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.data-table tbody tr.selected {
  background: #f0f9ff;
}

.data-table td {
  padding: 1rem 1rem;
  vertical-align: middle;
}

.checkbox-col {
  width: 50px;
}

.checkbox {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #3b82f6;
}

.employee-col {
  min-width: 280px;
}

.employee-cell {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.employee-avatar {
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 0.875rem;
  flex-shrink: 0;
}

.employee-details {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.employee-name {
  font-weight: 600;
  color: #1e293b;
}

.employee-id {
  font-size: 0.75rem;
  color: #64748b;
}

.employee-email {
  font-size: 0.75rem;
  color: #94a3b8;
}

.business-col {
  min-width: 180px;
}

.business-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.business-icon {
  font-size: 1rem;
}

.business-name {
  font-size: 0.875rem;
  color: #475569;
}

.no-business {
  color: #94a3b8;
  font-style: italic;
  font-size: 0.875rem;
}

.position-col {
  min-width: 150px;
}

.position-tag {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: #f1f5f9;
  color: #475569;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.salary-col {
  min-width: 120px;
}

.salary-amount {
  font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Fira Code', monospace;
  font-size: 0.875rem;
  font-weight: 500;
}

.salary-amount.gross {
  color: #3b82f6;
}

.salary-amount.net {
  color: #10b981;
  font-weight: 600;
}

.status-col {
  min-width: 100px;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.25rem 0.75rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 500;
}

.status-badge.pending {
  background: #fffbeb;
  color: #92400e;
}

.status-badge.paid {
  background: #f0fdf4;
  color: #166534;
}

.status-badge.unknown {
  background: #f1f5f9;
  color: #64748b;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.status-badge.pending .status-dot {
  background: #f59e0b;
}

.status-badge.paid .status-dot {
  background: #10b981;
}

.status-badge.unknown .status-dot {
  background: #94a3b8;
}

.actions-col {
  min-width: 120px;
}

.table-actions-cell {
  display: flex;
  gap: 0.5rem;
}

.action-icon {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 4px;
  background: transparent;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 1rem;
}

.action-icon.view {
  color: #3b82f6;
}

.action-icon.view:hover {
  background: #dbeafe;
}

.action-icon.adjust {
  color: #8b5cf6;
}

.action-icon.adjust:hover {
  background: #f3f0ff;
}

.action-icon.delete {
  color: #ef4444;
}

.action-icon.delete:hover {
  background: #fee2e2;
}

/* Bulk Actions */
.bulk-actions-bar {
  padding: 1rem 1.5rem;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.bulk-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.selected-count {
  font-weight: 600;
  color: #1e293b;
}

.bulk-buttons {
  display: flex;
  gap: 0.5rem;
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
  padding: 1rem;
  animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

.modal-content {
  background: white;
  border-radius: 12px;
  max-width: 500px;
  width: 100%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
  animation: slideUp 0.3s ease;
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

.modal-content.large-modal {
  max-width: 900px;
  max-height: 85vh;
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.modal-header-content h2 {
  margin: 0 0 0.25rem;
  font-size: 1.5rem;
  color: #1e293b;
}

.modal-header-content p {
  margin: 0;
  color: #64748b;
  font-size: 0.875rem;
}

.modal-close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #94a3b8;
  cursor: pointer;
  line-height: 1;
  padding: 0;
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}

.modal-close-btn:hover {
  background: #f1f5f9;
  color: #475569;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
  flex: 1;
}

.modal-footer {
  padding: 1.25rem 1.5rem;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-footer-actions {
  display: flex;
  gap: 0.75rem;
}

/* Preview Stats Grid */
.preview-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
  background: #f8fafc;
  padding: 1rem;
  border-radius: 8px;
}

.preview-stat {
  text-align: center;
}

.preview-stat.highlight {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 8px;
  padding: 1rem;
  margin: -0.5rem;
}

.preview-stat-label {
  font-size: 0.75rem;
  color: #64748b;
  margin-bottom: 0.25rem;
  font-weight: 500;
}

.preview-stat.highlight .preview-stat-label {
  color: rgba(255, 255, 255, 0.9);
}

.preview-stat-value {
  font-size: 1.5rem;
  font-weight: 600;
  color: #1e293b;
}

.preview-stat.highlight .preview-stat-value {
  color: white;
}

.preview-stat-value.positive {
  color: #10b981;
}

.preview-stat-value.negative {
  color: #ef4444;
}

/* Adjustments List */
.adjustments-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.adjustment-item {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
  transition: border-color 0.2s;
}

.adjustment-item.modified {
  border-color: #3b82f6;
  box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.1);
}

.adjustment-item-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #f1f5f9;
}

.employee-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.employee-avatar-small {
  width: 32px;
  height: 32px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 0.75rem;
  flex-shrink: 0;
}

.employee-info h4 {
  margin: 0 0 0.25rem;
  font-size: 0.875rem;
  color: #1e293b;
}

.employee-details {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.75rem;
  color: #64748b;
}

.position {
  background: #f1f5f9;
  padding: 0.125rem 0.5rem;
  border-radius: 4px;
}

.salary-info {
  text-align: right;
  font-size: 0.875rem;
}

.salary-row {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.25rem;
}

.salary-row.net {
  font-weight: 600;
  color: #10b981;
  margin-top: 0.25rem;
}

/* Adjustment Inputs */
.adjustment-inputs {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1rem;
}

.adjustment-col {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.adjustment-col-title {
  font-size: 0.75rem;
  font-weight: 600;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.col-icon {
  font-size: 1rem;
}

.adjustment-col.bonuses .adjustment-col-title {
  color: #10b981;
}

.adjustment-col.deductions .adjustment-col-title {
  color: #ef4444;
}

.input-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.input-group label {
  font-size: 0.75rem;
  color: #64748b;
}

.input-group input {
  padding: 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  font-size: 0.875rem;
  transition: border-color 0.2s;
}

.input-group input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

/* Adjustment Summary */
.adjustment-summary {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px dashed #e2e8f0;
}

.summary-label {
  font-size: 0.875rem;
  color: #64748b;
}

.summary-value {
  font-weight: 600;
  font-size: 1rem;
}

.summary-value.positive {
  color: #10b981;
}

.summary-value.negative {
  color: #ef4444;
}

/* Adjustment Form */
.adjustment-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.form-group label {
  font-size: 0.75rem;
  font-weight: 500;
  color: #475569;
}

.form-input {
  padding: 0.625rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.875rem;
  transition: border-color 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

.net-change-summary {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 6px;
  margin-top: 0.5rem;
  font-weight: 500;
}

.net-change-summary .positive {
  color: #10b981;
}

.net-change-summary .negative {
  color: #ef4444;
}

/* Responsive Design */
@media (max-width: 1200px) {
  .header-content {
    flex-direction: column;
    gap: 1rem;
  }
  
  .action-buttons {
    width: 100%;
    justify-content: flex-start;
  }
  
  .controls-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .page-header,
  .controls-section,
  .main-content {
    padding-left: 1rem;
    padding-right: 1rem;
  }
  
  .stats-container {
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
  }
  
  .controls-grid {
    grid-template-columns: 1fr;
  }
  
  .table-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .table-actions {
    flex-direction: column;
    gap: 0.75rem;
  }
  
  .data-table {
    font-size: 0.875rem;
  }
  
  .data-table th,
  .data-table td {
    padding: 0.75rem 0.5rem;
  }
  
  .modal-content.large-modal {
    max-height: 90vh;
  }
  
  .preview-stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .adjustment-inputs {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .modal-footer {
    flex-direction: column;
    gap: 1rem;
  }
  
  .modal-footer-actions {
    width: 100%;
    justify-content: stretch;
  }
  
  .modal-footer-actions .btn {
    flex: 1;
  }
}

@media (max-width: 480px) {
  .stats-container {
    grid-template-columns: 1fr;
  }
  
  .action-buttons {
    flex-direction: column;
  }
  
  .btn {
    width: 100%;
  }
  
  .bulk-actions-bar {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .bulk-buttons {
    flex-direction: column;
  }
  
  .date-range {
    flex-direction: column;
    align-items: stretch;
  }
  
  .date-range .control-input.small {
    width: 100%;
  }
  
  .preview-stats-grid {
    grid-template-columns: 1fr;
  }
}
</style>