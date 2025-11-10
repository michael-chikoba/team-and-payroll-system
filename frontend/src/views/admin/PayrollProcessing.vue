<template>
  <div class="payroll-view">
    <header class="header">
      <h1 class="title">{{ pageName }}</h1>
      <div class="header-actions">
        <button @click="addEmployee" class="add-btn">
          <span class="btn-icon">+</span> Add Employee
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
              <th>Position</th>
              <th>Salary</th>
              <th>Status</th>
              <th>Pay Period</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="employee in filteredEmployees"
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
                  </div>
                </div>
              </td>
              <td>
                <span class="position-badge">{{ employee.position }}</span>
              </td>
              <td class="salary">{{ formatCurrency(employee.salary) }}</td>
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
        <!-- Bulk Actions -->
        <div v-if="selectedCount > 0" class="bulk-actions">
          <span class="selected-count">{{ selectedCount }} employee(s) selected</span>
          <button @click="bulkMarkPaid" class="bulk-btn paid">Mark Selected as Paid</button>
          <button @click="bulkMarkPending" class="bulk-btn pending">Mark Selected as Pending</button>
          <button @click="clearSelection" class="bulk-btn clear">Clear Selection</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'PayrollProcessing',
  data() {
    return {
      pageName: 'Payroll Processing Dashboard',
      employees: [],
      processing: false,
      loading: true,
      error: null,
      successMessage: null,
      searchQuery: '',
      filterStatus: 'all',
      payrollPeriod: '',
      startDate: '',
      endDate: '',
      debugMode: true, // Set to false in production
      currentPayload: null
    }
  },
  computed: {
    pendingCount() {
      // Only count employees with explicit 'pending' status from backend
      return this.employees.filter(emp => emp.payroll_status === 'pending').length
    },
    paidCount() {
      // Only count employees with explicit 'paid' status from backend
      return this.employees.filter(emp => emp.payroll_status === 'paid').length
    },
    totalPayroll() {
      return this.employees
        .filter(emp => emp.payroll_status === 'pending')
        .reduce((sum, emp) => sum + (emp.salary || 0), 0)
    },
    filteredEmployees() {
      let filtered = this.employees
      // Apply status filter
      if (this.filterStatus !== 'all') {
        filtered = filtered.filter(emp => emp.payroll_status === this.filterStatus)
      }
      // Apply search filter
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(emp =>
          emp.name.toLowerCase().includes(query) ||
          emp.position.toLowerCase().includes(query) ||
          emp.id.toString().includes(query) ||
          (emp.email && emp.email.toLowerCase().includes(query))
        )
      }
      return filtered
    },
    selectedCount() {
      return this.employees.filter(emp => emp.selected).length
    },
    allSelected() {
      return this.filteredEmployees.length > 0 &&
             this.filteredEmployees.every(emp => emp.selected)
    }
  },
  async mounted() {
    await this.initializePayrollPeriod()
    await this.fetchEmployees()
  },
  methods: {
    getPayrollDates() {
      return {
        payroll_period: this.payrollPeriod,
        start_date: this.startDate,
        end_date: this.endDate
      }
    },
    preparePayrollPayload(employeeIds) {
      // Ensure employee_ids is always an array of integers
      const processedEmployeeIds = employeeIds.map(id => parseInt(id)).filter(id => !isNaN(id))
     
      // Prepare payload according to backend expectations
      const payload = {
        employee_ids: processedEmployeeIds,
        ...this.getPayrollDates()
      }
      return payload
    },
    initializePayrollPeriod() {
      // Compute current payroll period - ensure proper date format
      const now = new Date()
      const year = now.getFullYear()
      const monthNum = now.getMonth() + 1
      const month = monthNum.toString().padStart(2, '0')
     
      // Format: YYYY-MM
      this.payrollPeriod = `${year}-${month}`
     
      // Format: YYYY-MM-DD
      this.startDate = `${year}-${month}-01`
     
      // Calculate last day of month
      const lastDate = new Date(year, monthNum, 0)
      this.endDate = `${year}-${month}-${lastDate.getDate().toString().padStart(2, '0')}`
    },
    async fetchEmployees() {
      this.loading = true
      this.error = null
      try {
        const params = { payroll_period: this.payrollPeriod, per_page: 1000 }
        const [employeesRes, payrollRes] = await Promise.all([
          axios.get('/api/admin/employees'),
          axios.get('/api/admin/payroll/history', { params })
        ])
        
        const payrollMap = {}
        if (payrollRes.data?.data) {
          payrollRes.data.data.forEach(record => {
            payrollMap[record.employee_id] = {
              status: record.status,
              pay_period: record.pay_period,
              amount: record.amount
            }
          })
        }

        let employeesData = []
        if (employeesRes.data?.employees) {
          employeesData = employeesRes.data.employees
        } else if (employeesRes.data?.data) {
          employeesData = employeesRes.data.data
        } else {
          employeesData = employeesRes.data || []
        }

        this.employees = employeesData.map(emp => {
          const payrollData = payrollMap[emp.id]
         
          const firstName = emp.first_name || emp.user?.first_name || ''
          const lastName = emp.last_name || emp.user?.last_name || ''
          const fullName = [firstName, lastName].filter(Boolean).join(' ') || 'Unknown Employee'
          const position = emp.position || emp.department || 'Unassigned'
          const salary = emp.base_salary || emp.salary || 0
          const email = emp.email || emp.user?.email || null
          
          // CRITICAL: Only use status from backend, never set a default
          // If no payroll data exists, payroll_status will be undefined/null
          const payrollStatus = payrollData?.status

          return {
            id: emp.id,
            name: fullName,
            email: email,
            position: position,
            salary: salary,
            payroll_status: payrollStatus, // undefined if not set by backend
            payPeriod: payrollData?.pay_period || this.payrollPeriod,
            employee_record: emp,
            selected: false
          }
        })

        // Log employees without status for debugging
        const employeesWithoutStatus = this.employees.filter(emp => !emp.payroll_status)
        if (employeesWithoutStatus.length > 0) {
          console.log(`ℹ️ ${employeesWithoutStatus.length} employees without payroll status from backend`)
        }
        
        // Log employees with status for verification
        const employeesWithStatus = this.employees.filter(emp => emp.payroll_status)
        console.log(`✅ ${employeesWithStatus.length} employees have payroll status:`, 
          employeesWithStatus.map(e => ({id: e.id, name: e.name, status: e.payroll_status}))
        )

      } catch (err) {
        console.error('❌ Failed to load payroll data:', err)
        this.handleError(err)
      } finally {
        this.loading = false
      }
    },
    // Enhanced payroll processing with better validation
    async processPayroll() {
      // Only process employees that have pending status
      const pendingEmployees = this.employees.filter(e => e.payroll_status === 'pending')
      
      if (pendingEmployees.length === 0) {
        this.showError('ℹ️ No pending payroll to process.')
        return
      }

      if (!confirm(`Process payroll for ${pendingEmployees.length} pending employee(s)?`)) {
        return
      }

      this.processing = true
      try {
        const pendingIds = pendingEmployees.map(e => e.id)
        
        // Prepare payload according to backend expectations
        const payload = this.preparePayrollPayload(pendingIds)
        this.currentPayload = payload
        
        const response = await axios.post('/api/admin/payroll/process', payload)
        
        // Update employee statuses only for processed employees
        this.employees.forEach(emp => {
          if (pendingIds.includes(emp.id)) {
            emp.payroll_status = 'paid'
          }
        })
        
        this.showSuccess('✅ Payroll processed successfully!')
       
      } catch (err) {
        console.error('💥 Payroll processing error:', err)
        this.handlePayrollError(err)
      } finally {
        this.processing = false
      }
    },
    async bulkMarkPaid() {
      // Only process employees that have a status set AND are selected
      const selectedEmployees = this.employees.filter(e => e.selected && e.payroll_status)
      const selectedIds = selectedEmployees.map(e => e.id)
      
      if (selectedIds.length === 0) {
        this.showError('No employees with payroll status selected. Set status first before marking as paid.')
        return
      }

      if (!confirm(`Mark ${selectedIds.length} employee(s) as paid?`)) return
      
      try {
        const payload = this.preparePayrollPayload(selectedIds)
        this.currentPayload = payload
        
        await axios.post('/api/admin/payroll/process', payload)
        
        this.employees.forEach(emp => {
          if (emp.selected && emp.payroll_status) {
            emp.payroll_status = 'paid'
            emp.selected = false
          }
        })
        
        this.showSuccess(`✅ ${selectedIds.length} employee(s) marked as paid.`)
      } catch (err) {
        console.error('💥 Bulk mark paid error:', err)
        this.handlePayrollError(err)
      }
    },
    async bulkMarkPending() {
      // Only process employees that have a status set AND are selected
      const selectedEmployees = this.employees.filter(e => e.selected && e.payroll_status)
      const selectedIds = selectedEmployees.map(e => e.id)
      
      if (selectedIds.length === 0) {
        this.showError('No employees with payroll status selected. Set status first before marking as pending.')
        return
      }

      if (!confirm(`Mark ${selectedIds.length} employee(s) as pending?`)) return
      
      try {
        const payload = {
          employee_ids: selectedIds,
          status: 'pending',
          ...this.getPayrollDates()
        }
        
        await axios.post('/api/admin/payroll/update-status', payload)
        
        this.employees.forEach(emp => {
          if (emp.selected && emp.payroll_status) {
            emp.payroll_status = 'pending'
            emp.selected = false
          }
        })
        
        this.showSuccess(`✅ ${selectedIds.length} employee(s) marked as pending.`)
      } catch (err) {
        console.error('💥 Bulk mark pending error:', err)
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
      
      // Optimistically update UI
      employee.payroll_status = newStatus
      
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
       
        // Refresh data from backend to ensure consistency
        await this.fetchEmployees()
        this.showSuccess(`✅ Employee marked as ${newStatus}.`)
      } catch (err) {
        console.error('💥 Toggle status error:', err)
        this.handlePayrollError(err)
        // Revert on error
        employee.payroll_status = originalStatus
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
       
        employee.payroll_status = status
        this.showSuccess(`✅ Employee status set to ${status}.`)
      } catch (err) {
        console.error('💥 Set initial status error:', err)
        this.handlePayrollError(err)
      }
    },
    // Centralized error handling
    handlePayrollError(err) {
      // Enhanced error handling for 422 errors
      if (err.response?.status === 422) {
        const responseData = err.response.data
       
        console.error('🔍 422 Validation Error Details:', {
          status: err.response.status,
          data: responseData,
          requestData: err.config?.data ? JSON.parse(err.config.data) : null
        })
        
        let errorMessage = 'Validation failed: '
       
        if (responseData.errors) {
          // Laravel validation errors format
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
      this.$router.push({ name: 'admin.employee.details', params: { id } })
    },
    async deleteEmployee(id) {
      const employee = this.employees.find(e => e.id === id)
      if (!confirm(`Delete ${employee?.name || 'this employee'}? This cannot be undone.`)) return
      try {
        await axios.delete(`/api/admin/employees/${id}`)
        this.employees = this.employees.filter(e => e.id !== id)
        this.showSuccess('✅ Employee removed successfully.')
      } catch (err) {
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
      return new Intl.NumberFormat('en-ZM', {
        style: 'currency',
        currency: 'ZMW',
        minimumFractionDigits: 2
      }).format(amount)
    },
    formatDate(dateString) {
      if (!dateString) return '—'
      // Handle YYYY-MM format for period
      if (dateString.match(/^\d{4}-\d{2}$/)) {
        const [year, month] = dateString.split('-')
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
        return `${monthNames[parseInt(month) - 1]} ${year}`
      }
      // Otherwise, treat as date
      const date = new Date(dateString)
      if (isNaN(date.getTime())) return '—'
      return date.toLocaleDateString('en-ZM', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
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
        // This is now handled in handlePayrollError
        return
      } else if (err.response?.data?.message) {
        message = err.response.data.message
      } else if (err.message) {
        message = err.message
      } else if (err.code === 'ERR_NETWORK') {
        message = 'Network error. Please check your connection.'
      }
     
      this.error = message
      console.error('API Error:', err)
    }
  }
}
</script>

<style scoped>
/* Debug Panel Styles */
.debug-panel {
  background: #1a202c;
  color: #e2e8f0;
  border-radius: 8px;
  padding: 1rem;
  margin-bottom: 1rem;
  font-family: 'Monaco', 'Consolas', monospace;
  font-size: 0.875rem;
  border-left: 4px solid #667eea;
}
.debug-panel h4 {
  margin: 0 0 0.75rem 0;
  color: #90cdf4;
  font-size: 1rem;
}
.debug-content {
  background: #2d3748;
  padding: 0.75rem;
  border-radius: 4px;
  margin-bottom: 0.75rem;
}
.debug-content pre {
  margin: 0.5rem 0 0 0;
  background: #4a5568;
  padding: 0.5rem;
  border-radius: 4px;
  overflow-x: auto;
  font-size: 0.75rem;
}
.debug-close, .debug-toggle {
  background: #667eea;
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  font-size: 0.75rem;
  cursor: pointer;
  transition: background 0.2s;
}
.debug-close:hover, .debug-toggle:hover {
  background: #5a67d8;
}
.debug-toggle {
  background: #718096;
  margin-left: auto;
}
.debug-toggle:hover {
  background: #4a5568;
}
* {
  box-sizing: border-box;
}
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
.add-btn, .process-btn {
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
.process-btn {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}
.process-btn:disabled {
  background: #9ca3af;
  cursor: not-allowed;
  transform: none;
}
.add-btn:hover:not(:disabled),
.process-btn:hover:not(:disabled) {
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
.fade-enter-active, .fade-leave-active {
  transition: all 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
.error-banner, .success-banner {
  padding: 1rem 1.5rem;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-weight: 500;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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
.salary {
  font-weight: 600;
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
  flex-wrap: wrap;
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
  .add-btn, .process-btn {
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
  /* Hide less important columns on mobile */
  .payroll-table th:nth-child(2),
  .payroll-table td:nth-child(2),
  .payroll-table th:nth-child(7),
  .payroll-table td:nth-child(7) {
    display: none;
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
    flex-direction: column;
  }
  .action-btn {
    width: 100%;
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
  /* Stack table on very small screens */
  .table-container {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  .payroll-table {
    min-width: 600px;
  }
}
/* Print Styles */
@media print {
  .payroll-view {
    background: white;
    padding: 0;
  }
  .header-actions,
  .filters-section,
  .action-buttons,
  .bulk-actions,
  .error-banner,
  .success-banner {
    display: none !important;
  }
  .header {
    box-shadow: none;
    border-bottom: 2px solid #000;
  }
  .payroll-table {
    page-break-inside: avoid;
  }
  .card {
    box-shadow: none;
    border: 1px solid #e5e7eb;
  }
}
</style>