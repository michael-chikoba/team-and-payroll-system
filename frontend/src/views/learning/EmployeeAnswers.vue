<!-- src/views/learning/EmployeeAnswers.vue -->
<template>
  <div class="employee-answers">
    <div class="header">
      <button @click="goBack" class="back-btn">← Back to Course</button>
      <h1>Employee Assessment Answers</h1>
      <p>Review and analyze employee assessment submissions</p>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <div class="filter-group">
        <select v-model="filters.status" @change="fetchAttempts" class="filter-select">
          <option value="">All Status</option>
          <option value="passed">Passed</option>
          <option value="failed">Failed</option>
        </select>
        <select v-model="filters.employeeId" @change="fetchAttempts" class="filter-select">
          <option value="">All Employees</option>
          <option v-for="emp in employees" :key="emp.id" :value="emp.id">
            {{ emp.name }}
          </option>
        </select>
        <button @click="exportCSV" class="export-btn">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
          </svg>
          Export CSV
        </button>
      </div>
    </div>

    <!-- Statistics Summary -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-value">{{ stats.total }}</div>
        <div class="stat-label">Total Attempts</div>
      </div>
      <div class="stat-card success">
        <div class="stat-value">{{ stats.passed }}</div>
        <div class="stat-label">Passed</div>
      </div>
      <div class="stat-card danger">
        <div class="stat-value">{{ stats.failed }}</div>
        <div class="stat-label">Failed</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ stats.averageScore }}%</div>
        <div class="stat-label">Average Score</div>
      </div>
    </div>

    <!-- Attempts List -->
    <div class="attempts-list">
      <div v-for="attempt in attempts" :key="attempt.id" class="attempt-card">
        <div class="attempt-header">
          <div class="employee-info">
            <div class="employee-avatar">
              {{ getInitials(attempt.enrollment.employee.user.name) }}
            </div>
            <div>
              <h4>{{ attempt.enrollment.employee.user.name }}</h4>
              <p class="employee-email">{{ attempt.enrollment.employee.user.email }}</p>
            </div>
          </div>
          <div :class="['score-badge', attempt.passed ? 'passed' : 'failed']">
            {{ attempt.score || 0 }}% - {{ attempt.passed ? 'Passed' : 'Failed' }}
          </div>
        </div>
        
        <div class="attempt-details">
          <div class="detail-item">
            <span class="label">Started:</span>
            <span>{{ formatDate(attempt.started_at) }}</span>
          </div>
          <div class="detail-item">
            <span class="label">Completed:</span>
            <span>{{ formatDate(attempt.completed_at) || 'In progress' }}</span>
          </div>
          <div class="detail-item">
            <span class="label">Passing Score:</span>
            <span>{{ attempt.assessment.pass_mark }}%</span>
          </div>
        </div>
        
        <button @click="viewAttemptDetails(attempt.id)" class="view-answers-btn">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
          View Detailed Answers
        </button>
      </div>
      
      <div v-if="attempts.length === 0 && !loading" class="empty-state">
        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="12" cy="12" r="10"/>
          <line x1="12" y1="8" x2="12" y2="12"/>
          <line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <h3>No attempts found</h3>
        <p>Employees haven't taken this assessment yet.</p>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.total > pagination.per_page" class="pagination">
      <button @click="prevPage" :disabled="pagination.current_page === 1" class="page-btn">
        Previous
      </button>
      <span class="page-info">
        Page {{ pagination.current_page }} of {{ pagination.last_page }}
      </span>
      <button @click="nextPage" :disabled="pagination.current_page === pagination.last_page" class="page-btn">
        Next
      </button>
    </div>

    <!-- Attempt Details Modal -->
    <div v-if="showDetailsModal" class="modal-overlay" @click.self="closeDetailsModal">
      <div class="modal-box wide">
        <div class="modal-hd">
          <h2>Detailed Answers - {{ selectedAttempt?.employee_name }}</h2>
          <button class="modal-close" @click="closeDetailsModal">×</button>
        </div>
        <div class="modal-bd answers-details">
          <div class="attempt-summary">
            <div class="summary-item">
              <strong>Course:</strong> {{ selectedAttempt?.course_title }}
            </div>
            <div class="summary-item">
              <strong>Assessment:</strong> {{ selectedAttempt?.assessment_title }}
            </div>
            <div class="summary-item">
              <strong>Score:</strong> 
              <span :class="selectedAttempt?.passed ? 'text-success' : 'text-danger'">
                {{ selectedAttempt?.score }}% ({{ selectedAttempt?.passed ? 'PASSED' : 'FAILED' }})
              </span>
            </div>
            <div class="summary-item">
              <strong>Completed:</strong> {{ formatDate(selectedAttempt?.completed_at) }}
            </div>
          </div>
          
          <div class="questions-review">
            <h3>Question by Question Review</h3>
            <div v-for="(answer, idx) in selectedAttempt?.answers" :key="idx" class="review-question">
              <div class="review-header">
                <span class="q-number">Question {{ idx + 1 }}</span>
                <span :class="['q-status', answer.is_correct ? 'correct' : 'incorrect']">
                  {{ answer.is_correct ? '✓ Correct' : '✗ Incorrect' }}
                </span>
                <span class="q-points">{{ answer.earned_points }}/{{ answer.points }} points</span>
              </div>
              <div class="question-text">{{ answer.question_text }}</div>
              
              <div class="options-review">
                <div v-for="option in answer.options" :key="option.id" 
                     :class="['option-item', 
                              { 'user-selected': option.id === answer.user_answer_id },
                              { 'correct-answer': option.is_correct }]">
                  <div class="option-indicator">
                    <svg v-if="option.is_correct" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                      <polyline points="20 6 9 17 4 12"/>
                    </svg>
                    <svg v-else-if="option.id === answer.user_answer_id && !answer.is_correct" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                    <span v-else class="bullet">•</span>
                  </div>
                  <span class="option-text">{{ option.text }}</span>
                  <span v-if="option.is_correct" class="correct-badge">Correct Answer</span>
                  <span v-if="option.id === answer.user_answer_id && !answer.is_correct" class="user-badge">Your Answer</span>
                </div>
              </div>
              
              <div v-if="!answer.is_correct && answer.user_answer_text" class="feedback">
                <strong>Your answer:</strong> {{ answer.user_answer_text }}
              </div>
            </div>
          </div>
        </div>
        <div class="modal-ft">
          <button @click="closeDetailsModal" class="btn-primary">Close</button>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'EmployeeAnswers',
  data() {
    return {
      courseId: null,
      course: null,
      attempts: [],
      employees: [],
      loading: false,
      showDetailsModal: false,
      selectedAttempt: null,
      filters: {
        status: '',
        employeeId: ''
      },
      stats: {
        total: 0,
        passed: 0,
        failed: 0,
        averageScore: 0
      },
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total: 0
      }
    }
  },
  mounted() {
    this.courseId = this.$route.params.id
    this.fetchCourse()
    this.fetchEmployees()
    this.fetchAttempts()
  },
  methods: {
    async fetchCourse() {
      try {
        const response = await axios.get(`/api/learning/courses/${this.courseId}`)
        this.course = response.data.data
      } catch (error) {
        console.error('Failed to fetch course:', error)
      }
    },
    
    async fetchEmployees() {
      try {
        const response = await axios.get('/api/employees?limit=100')
        this.employees = response.data.data || []
      } catch (error) {
        console.error('Failed to fetch employees:', error)
      }
    },
    
    async fetchAttempts() {
      this.loading = true
      try {
        const params = {
          page: this.pagination.current_page,
          status: this.filters.status,
          employee_id: this.filters.employeeId
        }
        const response = await axios.get(`/api/learning/courses/${this.courseId}/attempts`, { params })
        this.attempts = response.data.data.data || []
        this.pagination = {
          current_page: response.data.data.current_page,
          last_page: response.data.data.last_page,
          per_page: response.data.data.per_page,
          total: response.data.data.total
        }
        this.calculateStats()
      } catch (error) {
        console.error('Failed to fetch attempts:', error)
      } finally {
        this.loading = false
      }
    },
    
    calculateStats() {
      const total = this.attempts.length
      const passed = this.attempts.filter(a => a.passed).length
      const failed = total - passed
      const avgScore = total > 0 
        ? Math.round(this.attempts.reduce((sum, a) => sum + (a.score || 0), 0) / total)
        : 0
      
      this.stats = { total, passed, failed, averageScore: avgScore }
    },
    
    async viewAttemptDetails(attemptId) {
      this.loading = true
      try {
        const response = await axios.get(`/api/learning/attempts/${attemptId}/details`)
        this.selectedAttempt = response.data.data
        this.showDetailsModal = true
      } catch (error) {
        console.error('Failed to fetch attempt details:', error)
        alert('Failed to load attempt details')
      } finally {
        this.loading = false
      }
    },
    
    async exportCSV() {
      try {
        const response = await axios.get(`/api/learning/courses/${this.courseId}/attempts/export`, {
          responseType: 'blob'
        })
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `attempts_${this.course.title}_${new Date().toISOString().split('T')[0]}.csv`)
        document.body.appendChild(link)
        link.click()
        link.remove()
      } catch (error) {
        console.error('Failed to export:', error)
        alert('Failed to export data')
      }
    },
    
    closeDetailsModal() {
      this.showDetailsModal = false
      this.selectedAttempt = null
    },
    
    prevPage() {
      if (this.pagination.current_page > 1) {
        this.pagination.current_page--
        this.fetchAttempts()
      }
    },
    
    nextPage() {
      if (this.pagination.current_page < this.pagination.last_page) {
        this.pagination.current_page++
        this.fetchAttempts()
      }
    },
    
    goBack() {
      this.$router.push(`/learning/courses/${this.courseId}`)
    },
    
    getInitials(name) {
      if (!name) return '?'
      return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
    },
    
    formatDate(date) {
      if (!date) return '—'
      return new Date(date).toLocaleString()
    }
  }
}
</script>

<style scoped>
.employee-answers {
  min-height: 100vh;
  background: #f9fafb;
  padding: 2rem;
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
  margin: 0 0 0.5rem;
}

.header p {
  color: #6b7280;
  margin: 0;
}

.filters-bar {
  background: white;
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 2rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.filter-group {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
  align-items: center;
}

.filter-select {
  padding: 0.5rem 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-family: inherit;
  min-width: 150px;
}

.export-btn {
  padding: 0.5rem 1rem;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
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

.stat-card.success .stat-value { color: #10b981; }
.stat-card.danger .stat-value { color: #ef4444; }

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
}

.attempts-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.attempt-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.attempt-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.employee-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.employee-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 600;
  font-size: 1.2rem;
}

.employee-info h4 {
  margin: 0 0 0.25rem;
  font-size: 1rem;
}

.employee-email {
  font-size: 0.75rem;
  color: #6b7280;
  margin: 0;
}

.score-badge {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.875rem;
}

.score-badge.passed {
  background: #f0fdf4;
  color: #166534;
}

.score-badge.failed {
  background: #fef2f2;
  color: #991b1b;
}

.attempt-details {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
  padding: 0.75rem;
  background: #f9fafb;
  border-radius: 8px;
  flex-wrap: wrap;
}

.detail-item {
  font-size: 0.8rem;
  color: #6b7280;
}

.detail-item .label {
  font-weight: 600;
  color: #374151;
  margin-right: 0.5rem;
}

.view-answers-btn {
  padding: 0.5rem 1rem;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.view-answers-btn:hover {
  background: #4338ca;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 2rem;
}

.page-btn {
  padding: 0.5rem 1rem;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  cursor: pointer;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-info {
  color: #6b7280;
}

.empty-state {
  text-align: center;
  padding: 4rem;
  background: white;
  border-radius: 12px;
}

.empty-state svg {
  color: #9ca3af;
  margin-bottom: 1rem;
}

.empty-state h3 {
  font-size: 1.25rem;
  color: #111827;
  margin-bottom: 0.5rem;
}

.empty-state p {
  color: #6b7280;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-box {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-hd {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-close {
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #f3f4f6;
  border: none;
  cursor: pointer;
  font-size: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-bd {
  padding: 1.5rem;
  max-height: 60vh;
  overflow-y: auto;
}

.modal-ft {
  padding: 1rem 1.5rem;
  border-top: 1px solid #e5e7eb;
  display: flex;
  justify-content: flex-end;
}

.answers-details {
  font-size: 0.9rem;
}

.attempt-summary {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 0.5rem;
}

.summary-item {
  font-size: 0.85rem;
}

.text-success { color: #10b981; font-weight: 600; }
.text-danger { color: #ef4444; font-weight: 600; }

.questions-review h3 {
  font-size: 1rem;
  margin-bottom: 1rem;
}

.review-question {
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.review-header {
  display: flex;
  gap: 1rem;
  align-items: center;
  margin-bottom: 0.75rem;
  flex-wrap: wrap;
}

.q-number {
  font-weight: 600;
  color: #4f46e5;
}

.q-status {
  padding: 0.25rem 0.5rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 600;
}

.q-status.correct {
  background: #f0fdf4;
  color: #166534;
}

.q-status.incorrect {
  background: #fef2f2;
  color: #991b1b;
}

.q-points {
  font-size: 0.75rem;
  color: #6b7280;
}

.question-text {
  font-weight: 500;
  margin-bottom: 1rem;
  color: #111827;
}

.options-review {
  margin-top: 0.5rem;
}

.option-item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem;
  border-radius: 8px;
  margin-bottom: 0.25rem;
}

.option-item.user-selected {
  background: #fef3c7;
}

.option-item.correct-answer {
  background: #f0fdf4;
}

.option-indicator {
  width: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.bullet {
  color: #9ca3af;
}

.option-text {
  flex: 1;
  font-size: 0.85rem;
}

.correct-badge, .user-badge {
  font-size: 0.65rem;
  padding: 0.15rem 0.4rem;
  border-radius: 4px;
}

.correct-badge {
  background: #10b981;
  color: white;
}

.user-badge {
  background: #f59e0b;
  color: white;
}

.feedback {
  margin-top: 0.5rem;
  padding: 0.5rem;
  background: #fef3c7;
  border-radius: 8px;
  font-size: 0.8rem;
}

.loading-overlay {
  position: fixed;
  inset: 0;
  background: rgba(255,255,255,0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
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

@media (max-width: 768px) {
  .employee-answers {
    padding: 1rem;
  }
  
  .attempt-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .filter-group {
    flex-direction: column;
    align-items: stretch;
  }
  
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>