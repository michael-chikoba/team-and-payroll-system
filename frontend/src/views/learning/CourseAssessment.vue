<!-- src/views/learning/CourseAssessment.vue -->
<template>
  <div class="assessment-view">
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>

    <div v-else-if="assessment && questions.length > 0" class="assessment-container">
      <div class="assessment-header">
        <button @click="goBack" class="back-btn">← Back to Course</button>
        <h1>{{ assessment.title || 'Course Assessment' }}</h1>
        <div class="assessment-info">
          <span>Passing Score: {{ assessment.pass_mark }}%</span>
          <span v-if="assessment.time_limit_minutes">Time Limit: {{ assessment.time_limit_minutes }} minutes</span>
          <span>Questions: {{ questions.length }}</span>
        </div>
      </div>

      <div v-if="assessment.time_limit_minutes && timeRemaining" class="timer-container">
        <div class="timer" :class="{ warning: timeRemaining < 60 }">
          ⏱️ Time Remaining: {{ formatTime(timeRemaining) }}
        </div>
      </div>

      <div class="questions-container">
        <form @submit.prevent="submitAssessment">
          <div v-for="(question, index) in questions" :key="question.id" class="question-card">
            <div class="question-header">
              <span class="question-number">Question {{ index + 1 }}</span>
              <span class="question-points">{{ question.points }} point{{ question.points > 1 ? 's' : '' }}</span>
            </div>
            <div class="question-text">{{ question.question }}</div>
            <div class="options-list">
              <label v-for="option in question.options" :key="option.id" class="option-label">
                <input
                  type="radio"
                  :name="`question_${question.id}`"
                  :value="option.id"
                  v-model="answers[question.id]"
                  :disabled="submitted"
                />
                <span class="option-text">{{ option.option_text }}</span>
              </label>
            </div>
          </div>

          <div class="assessment-actions">
            <button type="button" @click="goBack" class="cancel-btn">Cancel</button>
            <button type="submit" class="submit-btn" :disabled="submitted || submitting">
              {{ submitting ? 'Submitting...' : 'Submit Assessment' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <div v-else-if="result" class="result-container">
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
        <div class="result-score">
          <div class="score-circle">
            <span class="score-value">{{ result.score }}%</span>
          </div>
          <p>Passing Score: {{ assessment?.pass_mark }}%</p>
        </div>
        <div class="result-message">
          <p v-if="result.passed">
            You have successfully completed the assessment and earned your certificate!
          </p>
          <p v-else>
            You didn't meet the passing score. You have {{ remainingAttempts }} attempt(s) left.
          </p>
        </div>
        
        <div v-if="result.detailed_answers && result.detailed_answers.length" class="detailed-answers">
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

        <div class="result-actions">
          <button @click="goBackToCourse" class="back-to-course">Back to Course</button>
          <button v-if="!result.passed && remainingAttempts > 0" @click="retryAssessment" class="retry-btn">
            Try Again
          </button>
          <button v-if="result.passed" @click="viewCertificate" class="certificate-result-btn">
            View Certificate
          </button>
        </div>
      </div>
    </div>

    <div v-else class="error-state">
      <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="12" y1="8" x2="12" y2="12"></line>
        <line x1="12" y1="16" x2="12.01" y2="16"></line>
      </svg>
      <h2>Assessment Not Available</h2>
      <p>This course doesn't have an assessment yet or you haven't started it.</p>
      <button @click="goBackToCourse" class="btn-primary">Back to Course</button>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'CourseAssessment',
  data() {
    return {
      courseId: null,
      course: null,
      assessment: null,
      attempt: null,
      questions: [],
      answers: {},
      loading: false,
      submitting: false,
      submitted: false,
      result: null,
      timeRemaining: null,
      timerInterval: null,
      remainingAttempts: 3,
      userRole: 'employee'
    }
  },
  async mounted() {
    this.courseId = this.$route.params.id
    this.getUserRole()
    await this.checkOrStartAttempt()
  },
  beforeUnmount() {
    if (this.timerInterval) {
      clearInterval(this.timerInterval)
    }
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
    
    async checkOrStartAttempt() {
      this.loading = true
      
      try {
        const response = await axios.post(`/api/learning/courses/${this.courseId}/attempt`)
        this.attempt = response.data.data
        await this.fetchAssessmentAndQuestions()
        
        if (this.assessment?.time_limit_minutes && this.attempt && !this.attempt.completed_at) {
          this.startTimer()
        }
      } catch (error) {
        console.error('Failed to start attempt:', error)
        if (error.response?.status === 400) {
          const message = error.response?.data?.message || ''
          if (message.includes('already passed')) {
            alert('You have already passed this assessment!')
            this.goBackToCourse()
          } else if (message.includes('Maximum attempts')) {
            alert('You have reached the maximum number of attempts for this assessment.')
            this.goBackToCourse()
          }
        } else if (error.response?.status === 404) {
          alert('No assessment found for this course.')
          this.goBackToCourse()
        }
      } finally {
        this.loading = false
      }
    },
    
    async fetchAssessmentAndQuestions() {
      try {
        const response = await axios.get(`/api/learning/courses/${this.courseId}/assessment-questions`)
        this.questions = response.data.data || []
        this.assessment = response.data.assessment
        
        this.questions.forEach(question => {
          if (!this.answers[question.id]) {
            this.answers[question.id] = null
          }
        })
      } catch (error) {
        console.error('Failed to fetch questions:', error)
        this.questions = []
      }
    },
    
    startTimer() {
      if (!this.assessment?.time_limit_minutes) return
      
      const totalSeconds = this.assessment.time_limit_minutes * 60
      this.timeRemaining = totalSeconds
      
      this.timerInterval = setInterval(() => {
        if (this.timeRemaining > 0) {
          this.timeRemaining--
          if (this.timeRemaining === 0) {
            clearInterval(this.timerInterval)
            this.submitAssessment()
          }
        }
      }, 1000)
    },
    
    formatTime(seconds) {
      const minutes = Math.floor(seconds / 60)
      const remainingSeconds = seconds % 60
      return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
    },
    
    async submitAssessment() {
      if (this.submitted || this.submitting) return
      
      const unanswered = this.questions.filter(q => !this.answers[q.id])
      if (unanswered.length > 0) {
        if (!confirm(`You have ${unanswered.length} unanswered question(s). Submit anyway?`)) {
          return
        }
      }
      
      this.submitting = true
      try {
        const response = await axios.post(
          `/api/learning/courses/${this.courseId}/attempt/${this.attempt.id}/submit`,
          { answers: this.answers }
        )
        this.result = response.data.data
        this.submitted = true
        
        if (this.timerInterval) {
          clearInterval(this.timerInterval)
        }
        
        this.remainingAttempts = this.result.remaining_attempts || 0
      } catch (error) {
        console.error('Failed to submit assessment:', error)
        const message = error.response?.data?.message || 'Failed to submit assessment. Please try again.'
        alert(message)
      } finally {
        this.submitting = false
      }
    },
    
    retryAssessment() {
      this.result = null
      this.answers = {}
      this.submitted = false
      this.attempt = null
      this.checkOrStartAttempt()
    },
    
    goBackToCourse() {
      const rolePrefix = this.userRole === 'employee' ? '/employee' : this.userRole === 'manager' ? '/manager' : '/admin'
      this.$router.push(`${rolePrefix}/learning/courses/${this.courseId}`)
    },
    
    viewCertificate() {
      const rolePrefix = this.userRole === 'employee' ? '/employee' : this.userRole === 'manager' ? '/manager' : '/admin'
      this.$router.push(`${rolePrefix}/learning/certificate/${this.courseId}`)
    },
    
    goBack() {
      this.goBackToCourse()
    }
  }
}
</script>

<style scoped>
/* Styles same as previous CourseAssessment component */
.assessment-view {
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

.assessment-container {
  max-width: 800px;
  margin: 0 auto;
}

.assessment-header {
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

.assessment-header h1 {
  font-size: 1.75rem;
  color: #111827;
  margin: 0 0 0.5rem;
}

.assessment-info {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}

.assessment-info span {
  padding: 0.25rem 0.75rem;
  background: #e5e7eb;
  border-radius: 20px;
  font-size: 0.75rem;
  color: #374151;
}

.timer-container {
  background: white;
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 1.5rem;
  text-align: center;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.timer {
  font-size: 1.25rem;
  font-weight: 600;
  color: #4f46e5;
}

.timer.warning {
  color: #ef4444;
  animation: pulse 1s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.7; }
}

.questions-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.question-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.question-header {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.75rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.question-number {
  font-weight: 600;
  color: #4f46e5;
}

.question-points {
  font-size: 0.75rem;
  color: #6b7280;
}

.question-text {
  font-size: 1rem;
  color: #111827;
  margin-bottom: 1rem;
  line-height: 1.5;
}

.options-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.option-label {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.15s;
}

.option-label:hover {
  background: #f3f4f6;
}

.option-label input[type="radio"] {
  width: 18px;
  height: 18px;
  cursor: pointer;
}

.option-text {
  font-size: 0.9rem;
  color: #374151;
}

.assessment-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1rem;
}

.cancel-btn,
.submit-btn {
  padding: 0.625rem 1.5rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.15s;
}

.cancel-btn {
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  color: #374151;
}

.cancel-btn:hover {
  background: #e5e7eb;
}

.submit-btn {
  background: #4f46e5;
  border: none;
  color: white;
}

.submit-btn:hover:not(:disabled) {
  background: #4338ca;
}

.submit-btn:disabled {
  background: #a5b4fc;
  cursor: not-allowed;
}

.result-container {
  max-width: 800px;
  margin: 0 auto;
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

.result-score {
  margin-bottom: 1.5rem;
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

.result-message {
  color: #6b7280;
  margin-bottom: 1.5rem;
}

.detailed-answers {
  text-align: left;
  margin: 2rem 0;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
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

.result-actions {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-top: 1rem;
}

.back-to-course,
.retry-btn,
.certificate-result-btn {
  padding: 0.625rem 1.5rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
}

.back-to-course {
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  color: #374151;
}

.back-to-course:hover {
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

.certificate-result-btn {
  background: #10b981;
  border: none;
  color: white;
}

.certificate-result-btn:hover {
  background: #059669;
}

.error-state {
  text-align: center;
  padding: 4rem;
  max-width: 500px;
  margin: 0 auto;
}

.error-state svg {
  margin-bottom: 1rem;
  color: #9ca3af;
}

.error-state h2 {
  font-size: 1.5rem;
  color: #111827;
  margin-bottom: 0.5rem;
}

.error-state p {
  color: #6b7280;
  margin-bottom: 1.5rem;
}

.btn-primary {
  padding: 0.5rem 1rem;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

@media (max-width: 768px) {
  .assessment-view {
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
}
</style>