<template>
  <div class="course-enrollments">
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>

    <div v-else class="container">
      <div class="header">
        <button @click="goBack" class="back-btn">← Back to Course</button>
        <h1>Manage Enrollments</h1>
        <p class="subtitle">{{ course?.title }}</p>
      </div>

      <!-- Bulk Enroll Section -->
      <div class="bulk-enroll-section">
        <h3>Bulk Enroll Employees</h3>
        <div class="bulk-form">
          <select v-model="selectedDepartment" class="department-select">
            <option value="">All Departments</option>
            <option v-for="dept in departments" :key="dept" :value="dept">{{ dept }}</option>
          </select>
          <button @click="bulkEnrollByDepartment" class="bulk-enroll-btn" :disabled="bulkEnrolling">
            {{ bulkEnrolling ? 'Enrolling...' : 'Enroll by Department' }}
          </button>
        </div>
      </div>

      <!-- Enroll Individual Employee -->
      <div class="individual-enroll">
        <h3>Enroll Individual Employee</h3>
        <div class="search-form">
          <input 
            v-model="employeeSearch" 
            type="text" 
            placeholder="Search employee by name..."
            @input="searchEmployees"
          />
          <button @click="enrollSelectedEmployee" :disabled="!selectedEmployeeId" class="enroll-btn">
            Enroll Selected
          </button>
        </div>
        <div v-if="searchResults.length > 0" class="search-results">
          <div 
            v-for="emp in searchResults" 
            :key="emp.id"
            class="search-result-item"
            @click="selectEmployee(emp)"
          >
            <input type="radio" :value="emp.id" v-model="selectedEmployeeId" />
            <span>{{ emp.name }} - {{ emp.department || 'No department' }}</span>
          </div>
        </div>
      </div>

      <!-- Enrollments List -->
      <div class="enrollments-list">
        <h3>Current Enrollments ({{ enrollments.length }})</h3>
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>Employee</th>
                <th>Department</th>
                <th>Status</th>
                <th>Progress</th>
                <th>Enrolled Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="enrollment in enrollments" :key="enrollment.id">
                <td>{{ enrollment.employee?.name || enrollment.employee_name }}</td>
                <td>{{ enrollment.employee?.department || enrollment.department || '—' }}</td>
                <td>
                  <span :class="['status-badge', enrollment.status]">
                    {{ formatStatus(enrollment.status) }}
                  </span>
                </td>
                <td>
                  <div class="progress-cell">
                    <div class="progress-track">
                      <div class="progress-fill" :style="{ width: enrollment.progress_percent + '%' }"></div>
                    </div>
                    <span>{{ enrollment.progress_percent }}%</span>
                  </div>
                </td>
                <td>{{ formatDate(enrollment.enrolled_at) }}</td>
                <td>
                  <button @click="unenrollEmployee(enrollment)" class="remove-btn" title="Remove from course">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="3 6 5 6 21 6"></polyline>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                  </button>
                </td>
              </tr>
              <tr v-if="enrollments.length === 0">
                <td colspan="6" class="empty-state">No enrollments yet</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'CourseEnrollments',
  data() {
    return {
      courseId: null,
      course: null,
      enrollments: [],
      departments: ['HR', 'IT', 'Finance', 'Marketing', 'Sales', 'Operations'],
      selectedDepartment: '',
      employeeSearch: '',
      searchResults: [],
      selectedEmployeeId: null,
      loading: false,
      bulkEnrolling: false
    }
  },
  mounted() {
    this.courseId = this.$route.params.id
    this.fetchCourseAndEnrollments()
  },
  methods: {
    async fetchCourseAndEnrollments() {
      this.loading = true
      try {
        const [courseRes, enrollmentsRes] = await Promise.all([
          axios.get(`/api/learning/courses/${this.courseId}`),
          axios.get(`/api/learning/report?course_id=${this.courseId}`)
        ])
        this.course = courseRes.data.data
        this.enrollments = enrollmentsRes.data.data?.data || enrollmentsRes.data.data || []
      } catch (error) {
        console.error('Failed to fetch data:', error)
      } finally {
        this.loading = false
      }
    },
    
    async searchEmployees() {
      if (this.employeeSearch.length < 2) {
        this.searchResults = []
        return
      }
      
      try {
        const response = await axios.get(`/api/employees/search?q=${this.employeeSearch}`)
        // Filter out already enrolled employees
        const enrolledIds = new Set(this.enrollments.map(e => e.employee_id))
        this.searchResults = response.data.data.filter(emp => !enrolledIds.has(emp.id))
      } catch (error) {
        console.error('Failed to search employees:', error)
      }
    },
    
    selectEmployee(employee) {
      this.selectedEmployeeId = employee.id
      this.employeeSearch = employee.name
      this.searchResults = []
    },
    
    async enrollSelectedEmployee() {
      if (!this.selectedEmployeeId) return
      
      try {
        await axios.post(`/api/learning/courses/${this.courseId}/bulk-enroll`, {
          employee_ids: [this.selectedEmployeeId]
        })
        await this.fetchCourseAndEnrollments()
        this.selectedEmployeeId = null
        this.employeeSearch = ''
      } catch (error) {
        console.error('Failed to enroll employee:', error)
        alert('Failed to enroll employee')
      }
    },
    
    async bulkEnrollByDepartment() {
      if (!this.selectedDepartment) {
        alert('Please select a department')
        return
      }
      
      if (!confirm(`Enroll all employees from ${this.selectedDepartment} department?`)) return
      
      this.bulkEnrolling = true
      try {
        // This would need a backend endpoint to get employees by department
        const response = await axios.get(`/api/employees?department=${this.selectedDepartment}`)
        const employeeIds = response.data.data.map(emp => emp.id)
        
        // Filter out already enrolled
        const enrolledIds = new Set(this.enrollments.map(e => e.employee_id))
        const newEmployeeIds = employeeIds.filter(id => !enrolledIds.has(id))
        
        if (newEmployeeIds.length === 0) {
          alert('All employees in this department are already enrolled')
          return
        }
        
        await axios.post(`/api/learning/courses/${this.courseId}/bulk-enroll`, {
          employee_ids: newEmployeeIds
        })
        await this.fetchCourseAndEnrollments()
        alert(`${newEmployeeIds.length} employees enrolled successfully`)
      } catch (error) {
        console.error('Failed to bulk enroll:', error)
        alert('Failed to bulk enroll employees')
      } finally {
        this.bulkEnrolling = false
      }
    },
    
    async unenrollEmployee(enrollment) {
      if (!confirm(`Remove ${enrollment.employee?.name || enrollment.employee_name} from this course?`)) return
      
      try {
        await axios.delete(`/api/learning/courses/${this.courseId}/enroll/${enrollment.employee_id}`)
        await this.fetchCourseAndEnrollments()
      } catch (error) {
        console.error('Failed to unenroll:', error)
        alert('Failed to remove employee from course')
      }
    },
    
    goBack() {
      this.$router.push(`/learning/courses/${this.courseId}`)
    },
    
    formatStatus(status) {
      const map = {
        enrolled: 'Enrolled',
        in_progress: 'In Progress',
        completed: 'Completed',
        failed: 'Failed'
      }
      return map[status] || status
    },
    
    formatDate(date) {
      if (!date) return '—'
      return new Date(date).toLocaleDateString()
    }
  }
}
</script>

<style scoped>
.course-enrollments {
  min-height: 100vh;
  background: #f9fafb;
  padding: 2rem;
}

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255,255,255,0.9);
  display: flex;
  align-items: center;
  justify-content: center;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e5e7eb;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}

.header {
  margin-bottom: 2rem;
}

.back-btn {
  background: none;
  border: none;
  color: #4f46e5;
  cursor: pointer;
  font-size: 0.875rem;
  margin-bottom: 1rem;
  padding: 0;
}

.back-btn:hover {
  text-decoration: underline;
}

.header h1 {
  font-size: 1.75rem;
  color: #111827;
  margin: 0;
}

.subtitle {
  color: #6b7280;
  margin-top: 0.25rem;
}

.bulk-enroll-section,
.individual-enroll,
.enrollments-list {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.bulk-enroll-section h3,
.individual-enroll h3,
.enrollments-list h3 {
  margin: 0 0 1rem;
  font-size: 1rem;
  color: #111827;
}

.bulk-form {
  display: flex;
  gap: 1rem;
}

.department-select {
  flex: 1;
  padding: 0.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
}

.bulk-enroll-btn {
  padding: 0.5rem 1rem;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.bulk-enroll-btn:hover:not(:disabled) {
  background: #4338ca;
}

.bulk-enroll-btn:disabled {
  background: #a5b4fc;
  cursor: not-allowed;
}

.search-form {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}

.search-form input {
  flex: 1;
  padding: 0.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
}

.enroll-btn {
  padding: 0.5rem 1rem;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.enroll-btn:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

.search-results {
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  max-height: 200px;
  overflow-y: auto;
}

.search-result-item {
  padding: 0.5rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  border-bottom: 1px solid #f3f4f6;
}

.search-result-item:hover {
  background: #f9fafb;
}

.table-container {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #f3f4f6;
}

th {
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.status-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 600;
}

.status-badge.enrolled { background: #eff6ff; color: #1d4ed8; }
.status-badge.in_progress { background: #fef9c3; color: #854d0e; }
.status-badge.completed { background: #f0fdf4; color: #166534; }

.progress-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-width: 100px;
}

.progress-track {
  flex: 1;
  height: 6px;
  background: #f3f4f6;
  border-radius: 3px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #6366f1, #8b5cf6);
  border-radius: 3px;
}

.remove-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: #ef4444;
  padding: 0.25rem;
  border-radius: 4px;
}

.remove-btn:hover {
  background: #fef2f2;
}

.empty-state {
  text-align: center;
  padding: 2rem;
  color: #9ca3af;
}

@media (max-width: 768px) {
  .course-enrollments {
    padding: 1rem;
  }
  
  .bulk-form,
  .search-form {
    flex-direction: column;
  }
  
  .table-container {
    font-size: 0.8rem;
  }
  
  th, td {
    padding: 0.5rem;
  }
}
</style>