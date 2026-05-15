<!-- src/views/learning/AssessmentResults.vue -->
<template>
  <div class="assessment-results">
    <div v-if="loading" class="loading">
      <div class="spinner"></div>
    </div>

    <div v-else-if="result" class="results-container">
      <div class="result-card" :class="{ passed: result.passed, failed: !result.passed }">
        <div class="result-icon">
          <svg v-if="result.passed" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
          </svg>
          <svg v-else width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
        </div>
        <h2>{{ result.passed ? 'Congratulations!' : 'Assessment Failed' }}</h2>
        <div class="score-display">
          <div class="score-circle">
            <span class="score-value">{{ result.score }}%</span>
          </div>
          <p>Passing Score: {{ result.passing_score }}%</p>
        </div>
        
        <div class="detailed-answers" v-if="result.detailed_answers">
          <h3>Review Your Answers</h3>
          <div v-for="(answer, idx) in result.detailed_answers" :key="idx" class="answer-review">
            <div class="question-review">
              <span class="question-num">Q{{ idx + 1 }}</span>
              <span class="question-text">{{ answer.question }}</span>
              <span :class="['result-badge', answer.is_correct ? 'correct' : 'incorrect']">
                {{ answer.is_correct ? '✓ Correct' : '✗ Incorrect' }}
              </span>
            </div>
            <div class="answer-details">
              <div class="your-answer">
                <strong>Your answer:</strong> {{ answer.user_answer_text || 'Not answered' }}
              </div>
              <div class="correct-answer" v-if="!answer.is_correct">
                <strong>Correct answer:</strong> {{ answer.correct_answer_text }}
              </div>
              <div class="points-earned">
                Points: {{ answer.earned_points }}/{{ answer.points }}
              </div>
            </div>
          </div>
        </div>

        <div class="actions">
          <button @click="goBackToCourse" class="back-btn">Back to Course</button>
          <button v-if="!result.passed" @click="retryAssessment" class="retry-btn">Try Again</button>
          <button v-if="result.passed" @click="downloadCertificate" class="certificate-btn">Download Certificate</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'AssessmentResults',
  data() {
    return {
      result: null,
      loading: false,
      courseId: null,
      attemptId: null,
      userRole: 'employee'
    }
  },
  mounted() {
    this.courseId = this.$route.params.id
    this.attemptId = this.$route.params.attemptId
    this.getUserRole()
    this.fetchResults()
  },
  methods: {
    getUserRole() {
      try {
        const user = JSON.parse(localStorage.getItem('user') || '{}')
        this.userRole = user.role || localStorage.getItem('user_role') || 'employee'
      } catch (error) {
        this.userRole = 'employee'
      }
    },
    
    async fetchResults() {
      this.loading = true
      try {
        const response = await axios.get(`/api/learning/attempts/${this.attemptId}/answers`)
        this.result = response.data.data
      } catch (error) {
        console.error('Failed to fetch results:', error)
      } finally {
        this.loading = false
      }
    },
    
    goBackToCourse() {
      const rolePrefix = this.userRole === 'employee' ? '/employee' : this.userRole === 'manager' ? '/manager' : '/admin'
      this.$router.push(`${rolePrefix}/learning/courses/${this.courseId}`)
    },
    
    retryAssessment() {
      const rolePrefix = this.userRole === 'employee' ? '/employee' : this.userRole === 'manager' ? '/manager' : '/admin'
      this.$router.push(`${rolePrefix}/learning/courses/${this.courseId}/assessment`)
    },
    
    downloadCertificate() {
      window.open(`/api/learning/certificate/${this.courseId}/download`, '_blank')
    }
  }
}
</script>

<style scoped>
.assessment-results {
  min-height: 100vh;
  background: #f9fafb;
  padding: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.loading {
  text-align: center;
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

.results-container {
  max-width: 800px;
  width: 100%;
}

.result-card {
  background: white;
  border-radius: 16px;
  padding: 2rem;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.result-card.passed {
  border-top: 4px solid #10b981;
}

.result-card.failed {
  border-top: 4px solid #ef4444;
}

.result-icon {
  margin-bottom: 1rem;
}

.result-card.passed .result-icon {
  color: #10b981;
}

.result-card.failed .result-icon {
  color: #ef4444;
}

.result-card h2 {
  font-size: 1.5rem;
  margin: 0 0 1rem;
  color: #111827;
}

.score-display {
  margin-bottom: 2rem;
}

.score-circle {
  width: 120px;
  height: 120px;
  margin: 0 auto 1rem;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  display: flex;
  align-items: center;
  justify-content: center;
}

.result-card.passed .score-circle {
  background: linear-gradient(135deg, #10b981, #34d399);
}

.result-card.failed .score-circle {
  background: linear-gradient(135deg, #ef4444, #f87171);
}

.score-value {
  font-size: 2rem;
  font-weight: 700;
  color: white;
}

.detailed-answers {
  text-align: left;
  margin: 2rem 0;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
  max-height: 400px;
  overflow-y: auto;
}

.detailed-answers h3 {
  font-size: 1rem;
  margin-bottom: 1rem;
  color: #111827;
}

.answer-review {
  padding: 1rem;
  margin-bottom: 1rem;
  background: #f9fafb;
  border-radius: 8px;
}

.question-review {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  flex-wrap: wrap;
}

.question-num {
  font-weight: 600;
  color: #4f46e5;
}

.question-text {
  flex: 1;
  font-size: 0.9rem;
}

.result-badge {
  font-size: 0.7rem;
  padding: 0.15rem 0.5rem;
  border-radius: 20px;
}

.result-badge.correct {
  background: #f0fdf4;
  color: #166534;
}

.result-badge.incorrect {
  background: #fef2f2;
  color: #991b1b;
}

.answer-details {
  padding-left: 1.5rem;
  font-size: 0.8rem;
  color: #6b7280;
}

.answer-details div {
  margin: 0.25rem 0;
}

.points-earned {
  margin-top: 0.5rem;
  font-weight: 500;
  color: #4f46e5;
}

.actions {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-top: 1rem;
  flex-wrap: wrap;
}

.back-btn, .retry-btn, .certificate-btn {
  padding: 0.625rem 1.5rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s;
}

.back-btn {
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  color: #374151;
}

.back-btn:hover {
  background: #e5e7eb;
}

.retry-btn {
  background: #4f46e5;
  border: none;
  color: white;
}

.retry-btn:hover {
  background: #4338ca;
}

.certificate-btn {
  background: #10b981;
  border: none;
  color: white;
}

.certificate-btn:hover {
  background: #059669;
}

@media (max-width: 768px) {
  .assessment-results {
    padding: 1rem;
  }
  
  .result-card {
    padding: 1.5rem;
  }
  
  .score-circle {
    width: 100px;
    height: 100px;
  }
  
  .score-value {
    font-size: 1.5rem;
  }
  
  .actions {
    flex-direction: column;
  }
}
</style>