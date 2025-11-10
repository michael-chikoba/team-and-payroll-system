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
      <button @click="retryFetch" class="btn-primary" style="margin-top: 1rem;">Retry</button>
    </div>

    <!-- Employees List -->
    <div v-else class="employees-grid">
      <div v-for="employee in employees" :key="employee.id" class="employee-card">
        <div class="employee-header">
          <div class="employee-avatar">
            {{ getInitials(employee.full_name || employee.user?.name) }}
          </div>
          <div class="employee-info">
            <h3>{{ employee.full_name || employee.user?.name }}</h3>
            <p class="employee-id">{{ employee.employee_id }}</p>
          </div>
        </div>
        
        <div class="employee-details">
          <div class="detail-row">
            <span class="label">📧 Email:</span>
            <span>{{ employee.email || employee.user?.email }}</span>
          </div>
          <div class="detail-row">
            <span class="label">💼 Position:</span>
            <span>{{ employee.position }}</span>
          </div>
          <div class="detail-row">
            <span class="label">🏢 Department:</span>
            <span>{{ employee.department }}</span>
          </div>
          <div class="detail-row">
            <span class="label">💰 Salary:</span>
            <span>K{{ formatNumber(employee.base_salary) }}</span>
          </div>
          <div class="detail-row">
            <span class="label">📅 Hire Date:</span>
            <span>{{ formatDate(employee.hire_date) }}</span>
          </div>
          <div class="detail-row">
            <span class="label">📋 Type:</span>
            <span class="badge">{{ formatEmploymentType(employee.employment_type) }}</span>
          </div>
        </div>

        <!-- Expanded Details -->
        <div v-if="expandedEmployees.has(employee.id)" class="expanded-details">
          <div class="detail-row">
            <span class="label">📞 Phone:</span>
            <span>{{ employee.phone || 'N/A' }}</span>
          </div>
          <div class="detail-row">
            <span class="label">🏠 Address:</span>
            <span>{{ employee.address || 'N/A' }}</span>
          </div>
          <div class="detail-row">
            <span class="label">🚨 Emergency Contact:</span>
            <span>{{ employee.emergency_contact || 'N/A' }}</span>
          </div>
          <div class="detail-row">
            <span class="label">⭐ Performance Rating:</span>
            <span class="badge rating-badge">{{ employee.performance_rating || 'N/A' }}</span>
          </div>
          <div class="detail-row">
            <span class="label">📊 Last Review Date:</span>
            <span>{{ formatDate(employee.last_review_date) }}</span>
          </div>
          <div class="detail-row">
            <span class="label">📝 Notes:</span>
            <span>{{ employee.notes || 'No notes available' }}</span>
          </div>
        </div>

        <div class="employee-actions">
          <button @click="toggleExpanded(employee.id)" class="btn-view">
            {{ expandedEmployees.has(employee.id) ? 'View Less' : 'View More' }}
          </button>
          <button @click="viewEmployee(employee)" class="btn-details">Full Details</button>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="employees.length === 0" class="empty-state">
        <p>No team members found</p>
        <p>Contact HR to assign employees to your team.</p>
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
      retryCount: 0,  // For retry logic
      expandedEmployees: new Set()
    }
  },
  
  mounted() {
    this.initializeComponent()
  },
  
  methods: {
    initializeComponent() {
      // Check authentication and permissions
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
        // Use manager-specific endpoint to fetch only managed employees
        const response = await axios.get('/api/manager/employees', {
          params: { manager_id: this.authStore.user?.id }
        })
        
        this.employees = response.data.data || response.data
      } catch (err) {
        console.error('Fetch error:', err)
        console.error('Error response:', err.response)
        console.error('Full error:', err.toJSON ? err.toJSON() : err)
        
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
    
    toggleExpanded(employeeId) {
      if (this.expandedEmployees.has(employeeId)) {
        this.expandedEmployees.delete(employeeId)
      } else {
        this.expandedEmployees.add(employeeId)
      }
    },
    
    viewEmployee(employee) {
      // Navigate to employee detail page for full details
      this.$router.push({ 
        name: 'employee-details', 
        params: { id: employee.id } 
      })
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

.employees-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.employee-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  padding: 1.5rem;
  transition: transform 0.2s, box-shadow 0.2s;
}

.employee-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.employee-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f0f0f0;
}

.employee-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  font-weight: 700;
}

.employee-info h3 {
  margin: 0 0 0.25rem 0;
  color: #2d3748;
  font-size: 1.1rem;
}

.employee-id {
  color: #718096;
  font-size: 0.875rem;
  margin: 0;
}

.employee-details,
.expanded-details {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.expanded-details {
  border-top: 1px solid #f0f0f0;
  padding-top: 1rem;
  background-color: #f8f9ff;
  border-radius: 8px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.9rem;
}

.label {
  color: #718096;
  font-weight: 500;
}

.badge {
  background: #eef2ff;
  color: #667eea;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
}

.rating-badge {
  background: #fef3c7;
  color: #d97706;
}

.employee-actions {
  display: flex;
  gap: 0.5rem;
}

.employee-actions button {
  flex: 1;
  padding: 0.5rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s;
}

.btn-view {
  background: #e6f7ff;
  color: #1890ff;
}

.btn-details {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.employee-actions button:hover {
  transform: scale(1.05);
}

.empty-state {
  grid-column: 1 / -1;
  text-align: center;
  padding: 4rem;
  color: #718096;
}

@media (max-width: 768px) {
  .employees-grid {
    grid-template-columns: 1fr;
  }
  
  .page-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .employee-actions {
    flex-direction: column;
  }
}
</style>