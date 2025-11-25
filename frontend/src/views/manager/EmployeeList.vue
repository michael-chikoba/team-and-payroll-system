<template>
  <div class="employee-management">
    <div class="page-header">
      <h1>My Team - Employee Management</h1>
    </div>
    <!-- Authentication Check -->
    <div v-if="!authStore.isAuthenticated" class="error-message">
      Please log in to access employee management.
    </div>
    <!-- Permission Check -->
    <div v-else-if="!authStore.isManager" class="error-message">
      You don't have permission to access this page.
    </div>
    <!-- Loading State -->
    <div v-else-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Loading team members...</p>
    </div>
    <!-- Error State -->
    <div v-else-if="error" class="error-message">
      {{ error }}
      <button @click="retryFetch" class="btn-primary">Retry</button>
    </div>
    <!-- Employees Table -->
    <div v-else class="table-container">
      <div class="table-header">
        <h2>Team Members ({{ employees.length }})</h2>
      </div>
      <table class="employees-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Employee ID</th>
            <th>Email</th>
            <th>Position</th>
            <th>Department</th>
            <th>Salary (K)</th>
            <th>Hire Date</th>
            <th>Type</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="employee in paginatedEmployees" :key="employee.id">
            <td>
              <div class="employee-name">
                <div class="employee-avatar">
                  {{ getInitials(employee.full_name || employee.user?.name) }}
                </div>
                {{ employee.full_name || employee.user?.name }}
              </div>
            </td>
            <td>{{ employee.employee_id }}</td>
            <td>{{ employee.email || employee.user?.email }}</td>
            <td>{{ employee.position }}</td>
            <td>{{ employee.department }}</td>
            <td class="salary">{{ formatNumber(employee.base_salary) }}</td>
            <td>{{ formatDate(employee.hire_date) }}</td>
            <td>
              <span class="badge">{{ formatEmploymentType(employee.employment_type) }}</span>
            </td>
            <td>
              <button @click="viewEmployeeDetails(employee)" class="action-btn view">
                <i class="icon icon-eye"></i> View
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <!-- Pagination -->
      <div class="pagination" v-if="employees.length > itemsPerPage">
        <button @click="prevPage" :disabled="currentPage === 1" class="pagination-btn">Previous</button>
        <span v-for="page in totalPages" :key="page" class="page-number"
              :class="{ active: currentPage === page }"
              @click="goToPage(page)">
          {{ page }}
        </span>
        <button @click="nextPage" :disabled="currentPage === totalPages" class="pagination-btn">Next</button>
      </div>
      <!-- Empty State -->
      <div v-if="employees.length === 0" class="empty-state">
        <i class="icon icon-empty"></i>
        <p>No team members found</p>
        <p>Contact HR to assign employees to your team.</p>
      </div>
    </div>

    <!-- Employee Details Modal -->
    <div v-if="selectedEmployee" class="modal-overlay" @click.self="selectedEmployee = null">
      <div class="modal-card">
        <div class="modal-header">
          <h2>{{ selectedEmployee.full_name || selectedEmployee.user?.name }}'s Details</h2>
          <button @click="selectedEmployee = null" class="close-btn">✕</button>
        </div>
        <div class="modal-body">
          <div class="detail-section">
            <div class="card-header">
              <h3>Basic Information</h3>
              <i class="icon icon-user"></i>
            </div>
            <div class="detail-grid">
              <div class="detail-row">
                <span class="label">Employee ID:</span>
                <span>{{ selectedEmployee.employee_id }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Position:</span>
                <span>{{ selectedEmployee.position }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Department:</span>
                <span>{{ selectedEmployee.department }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Employment Type:</span>
                <span class="badge">{{ formatEmploymentType(selectedEmployee.employment_type) }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Salary:</span>
                <span>K{{ formatNumber(selectedEmployee.base_salary) }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Hire Date:</span>
                <span>{{ formatDate(selectedEmployee.hire_date) }}</span>
              </div>
            </div>
          </div>
          <div class="detail-section">
            <div class="card-header">
              <h3>Contact Information</h3>
              <i class="icon icon-phone"></i>
            </div>
            <div class="detail-grid">
              <div class="detail-row">
                <span class="label">Email:</span>
                <span>{{ selectedEmployee.email || selectedEmployee.user?.email }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Phone:</span>
                <span>{{ selectedEmployee.phone || 'N/A' }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Address:</span>
                <span>{{ selectedEmployee.address || 'N/A' }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Emergency Contact:</span>
                <span>{{ selectedEmployee.emergency_contact || 'N/A' }}</span>
              </div>
            </div>
          </div>
          <div class="detail-section">
            <div class="card-header">
              <h3>Performance & Notes</h3>
              <i class="icon icon-star"></i>
            </div>
            <div class="detail-grid">
              <div class="detail-row">
                <span class="label">Performance Rating:</span>
                <span class="badge rating-badge">{{ selectedEmployee.performance_rating || 'N/A' }}</span>
              </div>
              <div class="detail-row">
                <span class="label">Last Review Date:</span>
                <span>{{ formatDate(selectedEmployee.last_review_date) }}</span>
              </div>
              <div class="detail-row full-width">
                <span class="label">Notes:</span>
                <span class="notes">{{ selectedEmployee.notes || 'No notes available' }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="selectedEmployee = null" class="btn-secondary">Close</button>
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
      error: null,
      retryCount: 0,
      currentPage: 1,
      itemsPerPage: 10,
      selectedEmployee: null
    }
  },
  computed: {
    paginatedEmployees() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.employees.slice(start, end)
    },
    totalPages() {
      return Math.ceil(this.employees.length / this.itemsPerPage)
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
      this.error = null
     
      try {
        const response = await axios.get('/api/manager/employees', {
          params: { manager_id: this.authStore.user?.id }
        })
       
        this.employees = response.data.data || response.data
      } catch (err) {
        console.error('Fetch error:', err)
        this.handleApiError(err)
      } finally {
        this.loading = false
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
   
    handleApiError(err) {
      let errorMsg = 'An unexpected error occurred. (Likely CORS - check console)'
      if (err.code === 'ERR_NETWORK' || err.message.includes('Network Error')) {
        errorMsg = 'Network/CORS error: Ensure backend allows requests from localhost:3000'
      } else if (err.response?.status === 401) {
        errorMsg = 'Your session has expired. Please log in again.'
        this.authStore.clearAuth()
        this.$router.push({ name: 'login' })
      } else if (err.response?.status === 403) {
        errorMsg = 'You do not have permission to perform this action.'
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
   
    getInitials(name) {
      if (!name) return '??'
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
      return types[type] || type
    }
  }
}
</script>

<style scoped>
.employee-management {
  --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
  --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  --light: #f8fafc;
  --dark: #1f2937;
  --border: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  --radius: 16px;
  --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);

  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background: var(--primary-gradient);
  min-height: 100vh;
  color: var(--text-primary);
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  padding: 2rem;
  border-radius: 20px;
  box-shadow: var(--shadow-lg);
  margin-bottom: 2rem;
}

.page-header h1 {
  margin: 0;
  font-size: 2.5rem;
  font-weight: 700;
  background: var(--primary-gradient);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.btn-primary {
  background: var(--primary-gradient);
  color: white;
  border: none;
  padding: 1rem 2rem;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: var(--transition);
  box-shadow: 0 4px 14px rgba(102, 126, 234, 0.3);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 6rem 2rem;
  color: var(--text-secondary);
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  margin: 2rem auto;
  max-width: 400px;
}

.spinner {
  width: 60px;
  height: 60px;
  border: 4px solid rgba(226, 232, 240, 0.3);
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1.5rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-message {
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
  color: #991b1b;
  padding: 2rem;
  border-radius: var(--radius);
  text-align: center;
  box-shadow: var(--shadow-lg);
  border: 1px solid rgba(239, 68, 68, 0.2);
  margin: 2rem auto;
  max-width: 500px;
}

.table-container {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: var(--radius);
  box-shadow: var(--shadow-lg);
  overflow: hidden;
  padding: 2rem;
}

.table-header {
  margin-bottom: 1.5rem;
}

.table-header h2 {
  margin: 0;
  color: var(--text-primary);
  font-size: 1.5rem;
  font-weight: 600;
}

.employees-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.95rem;
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.employees-table th,
.employees-table td {
  padding: 1.25rem 1rem;
  text-align: left;
  border-bottom: 1px solid #f3f4f6;
}

.employees-table th {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  font-weight: 600;
  color: var(--text-secondary);
  text-transform: uppercase;
  font-size: 0.8rem;
  letter-spacing: 0.05em;
}

.employees-table tr:hover {
  background: #f8fafc;
  transform: scale(1.01);
  transition: var(--transition);
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
  background: var(--primary-gradient);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  font-weight: 700;
}

.salary {
  font-weight: 600;
  color: #059669;
}

.badge {
  background: #eef2ff;
  color: #667eea;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
}

.rating-badge {
  background: #fef3c7;
  color: #d97706;
}

.action-btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 8px;
  font-size: 0.85rem;
  cursor: pointer;
  transition: var(--transition);
  color: white;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-weight: 500;
}

.action-btn.view {
  background: var(--primary-gradient);
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.action-btn.view:hover {
  background: linear-gradient(135deg, #5a67d8 0%, #4c51bf 100%);
  transform: translateY(-1px);
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  margin-top: 2rem;
  padding: 1rem;
}

.pagination-btn,
.page-number {
  padding: 0.5rem 1rem;
  border: 1px solid var(--border);
  background: white;
  color: var(--text-primary);
  border-radius: 6px;
  cursor: pointer;
  transition: var(--transition);
  font-weight: 500;
}

.pagination-btn:hover:not(:disabled),
.page-number:hover:not(.active) {
  background: #f3f4f6;
  border-color: #9ca3af;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-number.active {
  background: var(--primary-gradient);
  color: white;
  border-color: transparent;
}

.empty-state {
  text-align: center;
  padding: 6rem 2rem;
  color: var(--text-secondary);
}

.empty-state .icon {
  font-size: 4rem;
  color: var(--text-secondary);
  margin-bottom: 1rem;
  display: block;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: var(--radius);
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: var(--shadow-lg);
  border: 1px solid rgba(255, 255, 255, 0.2);
  margin: 1rem;
  width: calc(100% - 2rem);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem;
  border-bottom: 1px solid var(--border);
}

.modal-header h2 {
  margin: 0;
  color: var(--text-primary);
  font-size: 1.5rem;
  font-weight: 600;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: var(--text-secondary);
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: var(--transition);
}

.close-btn:hover {
  background: rgba(226, 232, 240, 0.5);
}

.modal-body {
  padding: 2rem;
}

.detail-section {
  margin-bottom: 2.5rem;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.card-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 1.25rem;
  font-weight: 600;
  letter-spacing: -0.01em;
}

.card-header .icon {
  font-size: 1.5rem;
  color: var(--text-secondary);
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: rgba(248, 250, 252, 0.5);
  border-radius: 8px;
  transition: var(--transition);
}

.detail-row:hover {
  background: rgba(241, 245, 249, 0.8);
}

.detail-row.full-width {
  grid-column: 1 / -1;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.5rem;
}

.detail-row .label {
  font-weight: 500;
  color: var(--text-secondary);
}

.detail-row .notes {
  color: var(--text-primary);
  font-style: italic;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 2rem;
  border-top: 1px solid var(--border);
  background: rgba(248, 250, 252, 0.5);
}

.btn-secondary {
  background: rgba(248, 250, 252, 0.5);
  color: var(--text-primary);
  border: 1px solid var(--border);
  padding: 1rem 2rem;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 600;
  transition: var(--transition);
}

.icon {
  font-size: 1.2rem;
  font-weight: bold;
}

@media (max-width: 768px) {
  .employee-management {
    padding: 1rem;
  }

  .page-header {
    flex-direction: column;
    gap: 1.5rem;
    padding: 1.5rem;
    text-align: center;
  }

  .table-container {
    padding: 1.5rem;
  }

  .employees-table {
    font-size: 0.85rem;
  }

  .employees-table th,
  .employees-table td {
    padding: 0.75rem 0.5rem;
  }

  .employee-name {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }

  .modal-footer {
    flex-direction: column-reverse;
    align-items: stretch;
  }

  .btn-secondary {
    justify-content: center;
  }

  .pagination {
    flex-wrap: wrap;
  }
}
</style>