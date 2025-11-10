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

    <!-- Authentication Check -->
    <div v-if="!authStore.isAuthenticated" class="error-message">
      Please log in to access employee management.
    </div>

    <!-- Permission Check -->
    <div v-else-if="!authStore.isAdmin && !authStore.isManager" class="error-message">
      You don't have permission to access this page.
    </div>

    <!-- Loading State -->
    <div v-else-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Loading employees...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-message">
      {{ error }}
      <button @click="retryFetch" class="btn-primary" style="margin-top: 1rem;">Retry</button>
    </div>

    <!-- Employees Table -->
    <div v-else class="employees-table-wrapper">
      <table class="employees-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>ID</th>
            <th>Email</th>
            <th>Position</th>
            <th>Department</th>
            <th>Salary</th>
            <th>Hire Date</th>
            <th>Manager</th>
            <th>Type</th>
            <th>Role</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="employee in employees" :key="employee.id">
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
            <td>K{{ formatNumber(employee.base_salary) }}</td>
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
                <button @click="viewEmployee(employee)" class="btn-view">View</button>
                <button 
                  v-if="authStore.isAdmin" 
                  @click="editEmployee(employee)" 
                  class="btn-edit"
                >Edit</button>
                <button 
                  v-if="authStore.isAdmin" 
                  @click="deleteEmployee(employee)" 
                  class="btn-delete"
                >Delete</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Empty State -->
      <div v-if="employees.length === 0" class="empty-state">
        <p>No employees found</p>
        <button 
          @click="showAddModal = true" 
          class="btn-primary"
          v-if="authStore.isAdmin"
        >
          Add First Employee
        </button>
      </div>
    </div>

    <!-- Add/Edit Employee Modal -->
    <div v-if="(showAddModal || showEditModal) && authStore.isAdmin" class="modal-overlay" @click.self="closeModals">
      <div class="modal">
        <div class="modal-header">
          <h2>{{ showEditModal ? 'Edit Employee' : 'Add New Employee' }}</h2>
          <button @click="closeModals" class="close-btn">✕</button>
        </div>

        <form @submit.prevent="submitForm" class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label>First Name *</label>
              <input 
                v-model="form.first_name" 
                type="text" 
                required 
                placeholder="Enter first name"
              />
            </div>
            <div class="form-group">
              <label>Last Name *</label>
              <input 
                v-model="form.last_name" 
                type="text" 
                required 
                placeholder="Enter last name"
              />
            </div>
          </div>

          <div class="form-group" v-if="!showEditModal">
            <label>Email *</label>
            <input 
              v-model="form.email" 
              type="email" 
              required 
              placeholder="employee@example.com"
            />
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
              <input 
                v-model="form.position" 
                type="text" 
                required 
                placeholder="e.g., Software Developer"
              />
            </div>
            <div class="form-group">
              <label>Department *</label>
              <select v-model="form.department" required>
                <option value="">Select Department</option>
                <option value="IT">IT</option>
                <option value="HR">HR</option>
                <option value="Finance">Finance</option>
                <option value="Sales">Sales</option>
                <option value="Marketing">Marketing</option>
                <option value="Operations">Operations</option>
                <option value="Administration">Administration</option>
                <option value="Management">Management</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Base Salary (K) *</label>
              <input 
                v-model.number="form.base_salary" 
                type="number" 
                step="0.01"
                required 
                placeholder="0.00"
              />
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
              <label>Hire Date *</label>
              <input 
                v-model="form.hire_date" 
                type="date" 
                required 
              />
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

          <div v-if="formError" class="form-error">
            {{ formError }}
          </div>

          <div class="modal-footer">
            <button type="button" @click="closeModals" class="btn-secondary">
              Cancel
            </button>
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
      loading: false,
      error: null,
      showAddModal: false,
      showEditModal: false,
      submitting: false,
      formError: null,
      currentEmployee: null,
      form: {
        first_name: '',
        last_name: '',
        email: '',
        role: 'employee',
        position: '',
        department: '',
        base_salary: '',
        employment_type: '',
        hire_date: '',
        manager_id: ''
      },
      retryCount: 0
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
      
      if (!this.authStore.isAdmin && !this.authStore.isManager) {
        this.error = 'You do not have permission to access this page.'
        return
      }
      
      this.fetchEmployees()
      this.fetchManagers()
    },
    
    async fetchEmployees(retry = false) {
      this.loading = true
      this.error = null
      
      try {
        console.log('Fetching employees... (retry:', retry, ')')
        const response = await axios.get('/api/admin/employees')
        
        console.log('Employees response:', response.data)
        
        // Handle different response structures
        let employeesData = []
        if (response.data && response.data.employees) {
          employeesData = response.data.employees
        } else if (response.data && response.data.data) {
          employeesData = response.data.data
        } else {
          employeesData = response.data || []
        }
        
        this.employees = employeesData
        console.log('Processed employees:', this.employees)
      } catch (err) {
        console.error('Fetch error:', err)
        this.handleApiError(err)
      } finally {
        this.loading = false
      }
    },
    
    async fetchManagers() {
      try {
        console.log('Fetching managers...')
        const response = await axios.get('/api/admin/managers')
        
        console.log('Managers API response:', response.data)
        
        // Handle different response structures for managers
        if (Array.isArray(response.data)) {
          this.managers = response.data
        } else if (response.data && response.data.data) {
          this.managers = response.data.data
        } else {
          this.managers = response.data || []
        }
        
        if (this.managers.length === 0) {
          console.warn('No managers found in the system')
        } else {
          console.log('Managers loaded successfully:', this.managers)
        }
      } catch (err) {
        console.error('Failed to fetch managers:', err)
        this.managers = []
        
        // Only show error if it's not a 404 (endpoint might not exist yet)
        if (err.response?.status !== 404) {
          this.$notify({
            type: 'error',
            title: 'Error',
            text: 'Failed to load managers list'
          })
        }
      }
    },
    
    getEmployeeName(employee) {
      // Handle both direct and nested user structure
      if (employee.first_name && employee.last_name) {
        return `${employee.first_name} ${employee.last_name}`.trim()
      } else if (employee.user && employee.user.first_name) {
        return `${employee.user.first_name} ${employee.user.last_name || ''}`.trim()
      } else if (employee.name) {
        return employee.name
      } else if (employee.full_name) {
        return employee.full_name
      }
      return 'N/A'
    },
    
    getEmployeeRole(employee) {
      // Handle both direct and nested user structure
      return employee.role || employee.user?.role || 'employee'
    },
    
    getManagerName(managerId) {
      if (!managerId) return 'No Manager'
      const manager = this.managers.find(m => m.id === managerId)
      return manager ? `${manager.first_name} ${manager.last_name}` : 'Unknown Manager'
    },
    
    formatRole(role) {
      const roleMap = {
        'admin': 'Admin',
        'manager': 'Manager',
        'employee': 'Employee'
      }
      return roleMap[role] || 'Employee'
    },
    
    getRoleBadgeClass(role) {
      return {
        'role-employee': role === 'employee',
        'role-manager': role === 'manager',
        'role-admin': role === 'admin'
      }
    },
    
    onRoleChange() {
      // Clear manager_id when role is changed to manager or admin
      if (this.form.role === 'manager' || this.form.role === 'admin') {
        this.form.manager_id = ''
      }
    },
    
    retryFetch() {
      this.retryCount++
      if (this.retryCount <= 3) {
        this.fetchEmployees(true)
      } else {
        this.error = 'Max retries exceeded. Check your network or server.'
      }
    },
    
    async submitForm() {
      this.submitting = true
      this.formError = null
      
      try {
        // Validate manager selection for employees
        if (this.form.role === 'employee' && !this.form.manager_id) {
          this.formError = 'Please select a manager for the employee.'
          this.submitting = false
          return
        }

        const payload = {
          first_name: this.form.first_name,
          last_name: this.form.last_name,
          email: this.form.email,
          role: this.form.role,
          position: this.form.position,
          department: this.form.department,
          base_salary: parseFloat(this.form.base_salary),
          employment_type: this.form.employment_type,
          hire_date: this.form.hire_date,
          manager_id: this.form.role === 'employee' ? this.form.manager_id : null
        }
        
        console.log('Submitting employee data:', payload)
        
        if (this.showEditModal) {
          await axios.put(`/api/admin/employees/${this.currentEmployee.id}`, payload)
        } else {
          await axios.post('/api/admin/employees', payload)
        }
        
        await this.fetchEmployees()
        await this.fetchManagers() // Refresh managers list after update
        this.closeModals()
        
        const successMessage = this.showEditModal 
          ? `Employee updated successfully!`
          : `Employee created successfully! Default password is: Password123!`;
        
        this.$notify({
          type: 'success',
          title: 'Success',
          text: successMessage
        })
      } catch (err) {
        console.error('Submit error:', err.response)
        this.handleApiError(err)
      } finally {
        this.submitting = false
      }
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
      } else if (err.response?.status === 404) {
        errorMsg = 'The requested resource was not found.'
      } else if (err.response?.status === 500) {
        errorMsg = err.response?.data?.message || 'Server error occurred. Please try again.'
        // Show more details if available
        if (err.response?.data?.details && this.authStore.isAdmin) {
          this.formError = err.response.data.details
        }
        return
      } else {
        errorMsg = err.response?.data?.message || errorMsg
      }
      this.error = errorMsg
    },
    
    editEmployee(employee) {
      this.currentEmployee = employee
      
      const userRole = this.getEmployeeRole(employee)
      
      this.form = {
        first_name: employee.first_name || employee.user?.first_name || '',
        last_name: employee.last_name || employee.user?.last_name || '',
        email: employee.email || employee.user?.email || '',
        role: userRole,
        position: employee.position || '',
        department: employee.department || '',
        base_salary: employee.base_salary || '',
        employment_type: employee.employment_type || '',
        hire_date: employee.hire_date?.split('T')[0] || employee.created_at?.split('T')[0] || '',
        manager_id: userRole === 'employee' ? employee.manager_id : ''
      }
      
      this.showEditModal = true
    },
    
    async deleteEmployee(employee) {
      const employeeName = this.getEmployeeName(employee)
      if (!confirm(`Are you sure you want to delete ${employeeName}? This action cannot be undone.`)) {
        return
      }
      
      try {
        await axios.delete(`/api/admin/employees/${employee.id}`)
        await this.fetchEmployees()
        await this.fetchManagers() // Refresh managers list after deletion
        
        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Employee deleted successfully!'
        })
      } catch (err) {
        this.handleApiError(err)
      }
    },
    
    viewEmployee(employee) {
      this.$router.push({ 
        name: 'employee-details', 
        params: { id: employee.id } 
      })
    },
    
    closeModals() {
      this.showAddModal = false
      this.showEditModal = false
      this.currentEmployee = null
      this.formError = null
      this.resetForm()
    },
    
    resetForm() {
      this.form = {
        first_name: '',
        last_name: '',
        email: '',
        role: 'employee',
        position: '',
        department: '',
        base_salary: '',
        employment_type: '',
        hire_date: '',
        manager_id: ''
      }
    },
    
    getInitials(name) {
      if (!name || name === 'N/A') return '??'
      return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2)
    },
    
    formatNumber(num) {
      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      }).format(num || 0)
    },
    
    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
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
/* Add this new style for info text */
.info-text {
  color: #667eea;
  font-size: 0.875rem;
  margin-top: 0.25rem;
  display: block;
}

/* Add admin role badge style */
.role-admin {
  background: #fee2e2;
  color: #991b1b;
}

/* Your existing CSS styles remain the same */
.employee-management {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
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

.employee-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.employee-actions button {
  padding: 0.5rem 0.75rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  font-size: 0.875rem;
  transition: all 0.2s;
  white-space: nowrap;
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
  transform: scale(1.05);
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
  .employees-table-wrapper {
    font-size: 0.875rem;
  }
  
  .employees-table th,
  .employees-table td {
    padding: 0.75rem 0.5rem;
  }
  
  .employee-actions {
    flex-direction: column;
  }
  
  .employee-actions button {
    width: 100%;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .page-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
}
</style>