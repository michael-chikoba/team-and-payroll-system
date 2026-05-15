<!-- src/views/learning/LearningReports.vue -->
<template>
  <div class="learning-reports">
    <div class="header">
      <h1>Learning Reports</h1>
      <p>Track learning progress across your organization</p>
    </div>

    <div class="filters">
      <select v-model="filters.courseId" @change="fetchReport">
        <option value="">All Courses</option>
        <option v-for="course in courses" :key="course.id" :value="course.id">
          {{ course.title }}
        </option>
      </select>
      <select v-model="filters.status" @change="fetchReport">
        <option value="">All Status</option>
        <option value="enrolled">Enrolled</option>
        <option value="in_progress">In Progress</option>
        <option value="completed">Completed</option>
        <option value="failed">Failed</option>
      </select>
      <input type="date" v-model="filters.dateFrom" @change="fetchReport" placeholder="From Date" />
      <input type="date" v-model="filters.dateTo" @change="fetchReport" placeholder="To Date" />
      <button @click="exportCSV" class="export-btn">Export CSV</button>
    </div>

    <div class="stats-cards">
      <div class="stat-card">
        <div class="stat-value">{{ summary.total_enrollments }}</div>
        <div class="stat-label">Total Enrollments</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ summary.completion_rate }}%</div>
        <div class="stat-label">Completion Rate</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ summary.avg_progress }}%</div>
        <div class="stat-label">Average Progress</div>
      </div>
    </div>

    <div class="report-table">
      <table>
        <thead>
          <tr>
            <th>Employee</th>
            <th>Department</th>
            <th>Course</th>
            <th>Progress</th>
            <th>Status</th>
            <th>Enrolled Date</th>
            <th>Completed Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="record in reportData" :key="record.id">
            <td>{{ record.employee_name }}</td>
            <td>{{ record.department || '—' }}</td>
            <td>{{ record.course_title }}</td>
            <td>
              <div class="progress-cell">
                <div class="progress-track">
                  <div class="progress-fill" :style="{ width: record.progress_percent + '%' }"></div>
                </div>
                <span>{{ record.progress_percent }}%</span>
              </div>
            </td>
            <td>
              <span :class="['status-badge', record.status]">
                {{ formatStatus(record.status) }}
              </span>
            </td>
            <td>{{ formatDate(record.enrolled_at) }}</td>
            <td>{{ formatDate(record.completed_at) }}</td>
            <td>
              <button @click="viewEmployeeProgress(record.employee_id)" class="view-btn">
                View Details
              </button>
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="reportData.length === 0" class="empty-state">
        No data found
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'LearningReports',
  data() {
    return {
      courses: [],
      reportData: [],
      filters: {
        courseId: '',
        status: '',
        dateFrom: '',
        dateTo: ''
      },
      summary: {
        total_enrollments: 0,
        completion_rate: 0,
        avg_progress: 0
      }
    }
  },
  mounted() {
    this.fetchCourses()
    this.fetchReport()
  },
  methods: {
    async fetchCourses() {
      try {
        const response = await axios.get('/api/learning/courses')
        this.courses = response.data.data?.data || response.data.data || []
      } catch (error) {
        console.error('Failed to fetch courses:', error)
      }
    },
    
    async fetchReport() {
      try {
        const params = {}
        if (this.filters.courseId) params.course_id = this.filters.courseId
        if (this.filters.status) params.status = this.filters.status
        if (this.filters.dateFrom) params.date_from = this.filters.dateFrom
        if (this.filters.dateTo) params.date_to = this.filters.dateTo
        
        const response = await axios.get('/api/learning/report', { params })
        this.reportData = response.data.data || []
        this.calculateSummary()
      } catch (error) {
        console.error('Failed to fetch report:', error)
      }
    },
    
    calculateSummary() {
      const total = this.reportData.length
      const completed = this.reportData.filter(r => r.status === 'completed').length
      const totalProgress = this.reportData.reduce((sum, r) => sum + r.progress_percent, 0)
      
      this.summary = {
        total_enrollments: total,
        completion_rate: total > 0 ? Math.round((completed / total) * 100) : 0,
        avg_progress: total > 0 ? Math.round(totalProgress / total) : 0
      }
    },
    
    async exportCSV() {
      try {
        const params = {}
        if (this.filters.courseId) params.course_id = this.filters.courseId
        if (this.filters.status) params.status = this.filters.status
        if (this.filters.dateFrom) params.date_from = this.filters.dateFrom
        if (this.filters.dateTo) params.date_to = this.filters.dateTo
        params.export = 'csv'
        
        const response = await axios.get('/api/learning/report', { 
          params,
          responseType: 'blob' 
        })
        
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `learning_report_${new Date().toISOString().split('T')[0]}.csv`)
        document.body.appendChild(link)
        link.click()
        link.remove()
      } catch (error) {
        console.error('Failed to export report:', error)
        alert('Failed to export report')
      }
    },
    
    viewEmployeeProgress(employeeId) {
      this.$router.push(`/learning/employee/${employeeId}/progress`)
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
.learning-reports {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.header {
  margin-bottom: 2rem;
}

.header h1 {
  font-size: 1.75rem;
  color: #111827;
  margin: 0 0 0.5rem;
}

.header p {
  color: #6b7280;
  margin: 0;
}

.filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

.filters select, .filters input {
  padding: 0.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  min-width: 150px;
}

.export-btn {
  padding: 0.5rem 1rem;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.stats-cards {
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
}

.report-table {
  background: white;
  border-radius: 12px;
  overflow-x: auto;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #f3f4f6;
}

th {
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  background: #f9fafb;
}

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

.status-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 600;
}

.status-badge.enrolled { background: #eff6ff; color: #1d4ed8; }
.status-badge.in_progress { background: #fef9c3; color: #854d0e; }
.status-badge.completed { background: #f0fdf4; color: #166534; }
.status-badge.failed { background: #fef2f2; color: #991b1b; }

.view-btn {
  padding: 0.25rem 0.5rem;
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.75rem;
}

.view-btn:hover {
  background: #e5e7eb;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: #9ca3af;
}
</style>