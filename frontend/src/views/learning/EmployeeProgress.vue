<template>
  <div class="employee-progress">
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>

    <div v-else class="container">
      <div class="header">
        <button @click="goBack" class="back-btn">← Back</button>
        <h1>Employee Learning Progress</h1>
      </div>

      <div v-if="employee" class="employee-info">
        <div class="avatar">{{ getInitials(employee.name) }}</div>
        <div class="details">
          <h2>{{ employee.name }}</h2>
          <p>{{ employee.department || 'No department' }} • {{ employee.role || 'Employee' }}</p>
        </div>
      </div>

      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-value">{{ stats.total }}</div>
          <div class="stat-label">Total Enrolled</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ stats.completed }}</div>
          <div class="stat-label">Completed</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ stats.inProgress }}</div>
          <div class="stat-label">In Progress</div>
        </div>
        <div class="stat-card">
          <div class="stat-value">{{ stats.averageProgress }}%</div>
          <div class="stat-label">Avg Progress</div>
        </div>
      </div>

      <div class="courses-section">
        <h3>Enrolled Courses</h3>
        <div class="courses-list">
          <div v-for="enrollment in enrollments" :key="enrollment.id" class="course-progress-card">
            <div class="course-info">
              <h4>{{ enrollment.course?.title || enrollment.course_title }}</h4>
              <span :class="['status-badge', enrollment.status]">
                {{ formatStatus(enrollment.status) }}
              </span>
            </div>
            <div class="progress-section">
              <div class="progress-track">
                <div class="progress-fill" :style="{ width: enrollment.progress_percent + '%' }"></div>
              </div>
              <span class="progress-percent">{{ enrollment.progress_percent }}%</span>
            </div>
            <div class="course-dates">
              <span>Enrolled: {{ formatDate(enrollment.enrolled_at) }}</span>
              <span v-if="enrollment.completed_at">Completed: {{ formatDate(enrollment.completed_at) }}</span>
            </div>
            <button @click="viewCourse(enrollment.course_id)" class="view-course-btn">
              View Course
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'EmployeeProgress',
  data() {
    return {
      employeeId: null,
      employee: null,
      enrollments: [],
      stats: {
        total: 0,
        completed: 0,
        inProgress: 0,
        averageProgress: 0
      },
      loading: false
    }
  },
  mounted() {
    this.employeeId = this.$route.params.employeeId
    this.fetchEmployeeProgress()
  },
  methods: {
    async fetchEmployeeProgress() {
      this.loading = true
      try {
        const response = await axios.get(`/api/learning/employee/${this.employeeId}/progress`)
        this.employee = response.data.employee
        this.enrollments = response.data.enrollments
        
        // Calculate stats
        this.stats.total = this.enrollments.length
        this.stats.completed = this.enrollments.filter(e => e.status === 'completed').length
        this.stats.inProgress = this.enrollments.filter(e => e.status === 'in_progress').length
        const totalProgress = this.enrollments.reduce((sum, e) => sum + e.progress_percent, 0)
        this.stats.averageProgress = this.enrollments.length ? Math.round(totalProgress / this.enrollments.length) : 0
      } catch (error) {
        console.error('Failed to fetch employee progress:', error)
      } finally {
        this.loading = false
      }
    },
    viewCourse(courseId) {
      this.$router.push(`/learning/courses/${courseId}`)
    },
    goBack() {
      this.$router.push('/learning')
    },
    getInitials(name) {
      if (!name) return '?'
      return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
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
.employee-progress {
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

.employee-info {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 2rem;
  display: flex;
  align-items: center;
  gap: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.avatar {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  font-weight: 600;
}

.details h2 {
  margin: 0 0 0.25rem;
  font-size: 1.25rem;
  color: #111827;
}

.details p {
  margin: 0;
  color: #6b7280;
  font-size: 0.875rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  text-align: center;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: #4f46e5;
}

.stat-label {
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.25rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.courses-section h3 {
  font-size: 1.25rem;
  color: #111827;
  margin-bottom: 1rem;
}

.courses-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.course-progress-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.course-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.course-info h4 {
  margin: 0;
  font-size: 1rem;
  color: #111827;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 600;
}

.status-badge.enrolled { background: #eff6ff; color: #1d4ed8; }
.status-badge.in_progress { background: #fef9c3; color: #854d0e; }
.status-badge.completed { background: #f0fdf4; color: #166534; }
.status-badge.failed { background: #fef2f2; color: #991b1b; }

.progress-section {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.progress-track {
  flex: 1;
  height: 8px;
  background: #f3f4f6;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #6366f1, #8b5cf6);
  border-radius: 4px;
  transition: width 0.3s;
}

.progress-percent {
  font-size: 0.875rem;
  font-weight: 600;
  color: #4f46e5;
  min-width: 45px;
}

.course-dates {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
  font-size: 0.75rem;
  color: #6b7280;
}

.view-course-btn {
  padding: 0.5rem 1rem;
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 0.875rem;
  cursor: pointer;
}

.view-course-btn:hover {
  background: #e5e7eb;
}

@media (max-width: 768px) {
  .employee-progress {
    padding: 1rem;
  }
  
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .employee-info {
    flex-direction: column;
    text-align: center;
  }
  
  .course-info {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
}
</style>