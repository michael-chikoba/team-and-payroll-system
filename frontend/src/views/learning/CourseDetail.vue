<!-- src/views/learning/CourseDetail.vue -->
<template>
  <div class="course-detail">
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>
    
    <div v-else-if="course" class="course-container">
      <!-- Header -->
      <div class="course-header" :style="{ background: categoryColor(course.category) }">
        <div class="header-content">
          <button @click="goBack" class="back-btn">
            ← Back to Catalog
          </button>
          <h1>{{ course.title }}</h1>
          <p class="course-description">{{ course.description }}</p>
          <div class="course-meta">
            <span class="meta-badge">{{ course.category || 'General' }}</span>
            <span class="meta-badge">{{ course.modules?.length || 0 }} modules</span>
            <span v-if="course.estimated_minutes" class="meta-badge">
              {{ course.estimated_minutes }} min
            </span>
          </div>
        </div>
      </div>

      <!-- Content -->
      <div class="course-content">
        <!-- Sidebar -->
        <div class="course-sidebar">
          <div class="enrollment-card">
            <div v-if="isEnrolled" class="progress-section">
              <h4>Your Progress</h4>
              <div class="progress-track large">
                <div class="progress-fill" :style="{ width: (enrollment?.progress_percent || 0) + '%' }"></div>
              </div>
              <div class="progress-stats">
                <span>{{ enrollment?.progress_percent || 0 }}% Complete</span>
                <span>{{ completedModulesCount }}/{{ totalModules }} modules</span>
              </div>
              <span :class="['status-badge', enrollment?.status]">
                {{ formatStatus(enrollment?.status) }}
              </span>
              <div v-if="enrollment?.status === 'completed'" class="certificate-link">
                <button @click="viewCertificate" class="certificate-btn">
                  🎓 View Certificate
                </button>
              </div>
            </div>
            
            <div v-else class="enroll-section">
              <h4>Ready to start?</h4>
              <button 
                @click="enrollInCourse" 
                class="enroll-btn-large"
                :disabled="enrolling"
              >
                {{ enrolling ? 'Enrolling...' : 'Enroll Now' }}
              </button>
              <p class="enroll-note" v-if="course.allow_self_enroll">
                Self-enrollment is enabled for this course
              </p>
            </div>
          </div>
        </div>

        <!-- Main Content - Modules -->
        <div class="course-main">
          <div class="modules-section">
            <h2>Course Modules</h2>
            <div class="modules-list">
              <div 
                v-for="(module, index) in course.modules" 
                :key="module.id"
                class="module-item"
                :class="{ completed: isModuleCompleted(module.id) }"
              >
                <div class="module-header" @click="toggleModule(index)">
                  <div class="module-icon">
                    <svg v-if="isModuleCompleted(module.id)" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="20 6 9 17 4 12"></polyline>
                    </svg>
                    <span v-else class="module-number">{{ index + 1 }}</span>
                  </div>
                  <div class="module-info">
                    <h3>{{ module.title }}</h3>
                    <p v-if="module.description" class="module-desc">{{ module.description }}</p>
                  </div>
                  <div class="module-actions">
                    <span v-if="module.duration_minutes" class="duration">
                      {{ module.duration_minutes }} min
                    </span>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline :points="openModules[index] ? '18 15 12 9 6 15' : '6 9 12 15 18 9'"></polyline>
                    </svg>
                  </div>
                </div>
                <div v-show="openModules[index]" class="module-content">
                  <div v-if="module.type === 'video'" class="video-player">
                    <video controls class="video-element">
                      <source :src="module.content" type="video/mp4">
                      Your browser does not support the video tag.
                    </video>
                  </div>
                  <div v-else-if="module.type === 'pdf'" class="pdf-viewer">
                    <iframe :src="module.content" class="pdf-iframe"></iframe>
                  </div>
                  <div v-else class="text-content" v-html="module.content">
                  </div>
                  
                  <div v-if="isEnrolled && !isModuleCompleted(module.id)" class="module-footer">
                    <button @click="markModuleComplete(module.id)" class="complete-btn">
                      Mark as Complete
                    </button>
                  </div>
                  <div v-else-if="isEnrolled && isModuleCompleted(module.id)" class="module-footer completed">
                    <span class="completed-badge">✓ Completed</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Assessment Section -->
          <div v-if="course.assessment" class="assessment-section">
            <h2>Final Assessment</h2>
            <div class="assessment-card">
              <div class="assessment-info">
                <h3>{{ course.assessment.title || 'Course Assessment' }}</h3>
                <p>Passing score: {{ course.assessment.pass_mark || 70 }}%</p>
                <p>Max attempts: {{ course.assessment.max_attempts || 3 }}</p>
                <p v-if="remainingAttempts !== null">Remaining attempts: {{ remainingAttempts }}</p>
              </div>
              <button 
                @click="startAssessment" 
                class="assessment-btn"
                :disabled="!isEnrolled || !allModulesCompleted || assessmentPassed"
              >
                <span v-if="assessmentPassed">✓ Assessment Passed</span>
                <span v-else-if="!allModulesCompleted">Complete all modules first</span>
                <span v-else-if="remainingAttempts === 0">No attempts remaining</span>
                <span v-else>Take Assessment</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="error-state">
      <h2>Course not found</h2>
      <p>The course you're looking for doesn't exist or you don't have access.</p>
      <button @click="goBack" class="btn-primary">Back to Catalog</button>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'CourseDetail',
  data() {
    return {
      course: null,
      enrollment: null,
      isEnrolled: false,
      completedModulesArray: [],
      completedModulesCount: 0,
      openModules: {},
      loading: false,
      enrolling: false,
      userRole: 'employee',
      remainingAttempts: null,
      assessmentPassed: false
    }
  },
  computed: {
    courseId() {
      return this.$route.params.id
    },
    totalModules() {
      return this.course?.modules?.length || 0
    },
    allModulesCompleted() {
      return this.completedModulesCount === this.totalModules && this.totalModules > 0
    }
  },
  mounted() {
    this.getUserRole()
    this.fetchCourseDetail()
  },
  methods: {
    getUserRole() {
      try {
        const user = JSON.parse(localStorage.getItem('user') || '{}')
        this.userRole = user.role || localStorage.getItem('user_role') || 'employee'
      } catch (error) {
        console.error('Error getting user role:', error)
        this.userRole = 'employee'
      }
    },
    
    async fetchCourseDetail() {
      this.loading = true
      try {
        const response = await axios.get(`/api/learning/courses/${this.courseId}`)
        this.course = response.data.data
        this.isEnrolled = response.data.is_enrolled || false
        this.enrollment = response.data.enrollment
        
        // Handle completed modules
        const completedModules = response.data.completed_modules
        if (Array.isArray(completedModules)) {
          this.completedModulesArray = completedModules
          this.completedModulesCount = completedModules.length
        } else {
          this.completedModulesArray = []
          this.completedModulesCount = 0
        }
        
        // Check assessment status if enrolled
        if (this.isEnrolled && this.course.assessment) {
          await this.checkAssessmentStatus()
        }
        
        // Auto-open first uncompleted module
        if (this.isEnrolled && this.course.modules && this.course.modules.length > 0) {
          const firstIncomplete = this.course.modules.findIndex(
            m => !this.isModuleCompleted(m.id)
          )
          if (firstIncomplete !== -1) {
            this.openModules[firstIncomplete] = true
          } else if (this.course.modules.length > 0) {
            this.openModules[0] = true
          }
        }
      } catch (error) {
        console.error('Failed to fetch course:', error)
        if (error.response?.status === 404) {
          this.course = null
        }
      } finally {
        this.loading = false
      }
    },
    
    async checkAssessmentStatus() {
      try {
        const response = await axios.post(`/api/learning/courses/${this.courseId}/attempt`)
        this.remainingAttempts = response.data.data?.remaining_attempts || this.course.assessment.max_attempts
        this.assessmentPassed = false
      } catch (error) {
        if (error.response?.status === 400) {
          const message = error.response?.data?.message || ''
          if (message.includes('already passed')) {
            this.assessmentPassed = true
            this.remainingAttempts = 0
          } else if (message.includes('Maximum attempts')) {
            this.remainingAttempts = 0
          }
        }
      }
    },
    
    isModuleCompleted(moduleId) {
      return this.completedModulesArray.includes(moduleId)
    },
    
    async markModuleComplete(moduleId) {
      try {
        await axios.post(`/api/learning/courses/${this.courseId}/modules/${moduleId}/complete`)
        
        if (!this.completedModulesArray.includes(moduleId)) {
          this.completedModulesArray.push(moduleId)
          this.completedModulesCount = this.completedModulesArray.length
        }
        
        // Refresh enrollment data
        const response = await axios.get(`/api/learning/courses/${this.courseId}`)
        this.enrollment = response.data.enrollment
        
        if (this.completedModulesCount === this.totalModules) {
          alert('Congratulations! You have completed all modules! You can now take the final assessment.')
        }
      } catch (error) {
        console.error('Failed to mark module complete:', error)
        alert('Failed to mark module as complete. Please try again.')
      }
    },
    
    async enrollInCourse() {
      this.enrolling = true
      try {
        await axios.post(`/api/learning/courses/${this.courseId}/enroll`)
        await this.fetchCourseDetail()
        alert('Successfully enrolled in the course!')
      } catch (error) {
        console.error('Failed to enroll:', error)
        const message = error.response?.data?.message || 'Failed to enroll in course'
        alert(message)
      } finally {
        this.enrolling = false
      }
    },
    
    async startAssessment() {
      try {
        await axios.post(`/api/learning/courses/${this.courseId}/attempt`)
        const rolePrefix = this.userRole === 'employee' ? '/employee' : this.userRole === 'manager' ? '/manager' : '/admin'
        this.$router.push(`${rolePrefix}/learning/courses/${this.courseId}/assessment`)
      } catch (error) {
        if (error.response?.status === 400 && error.response?.data?.message?.includes('already passed')) {
          alert('You have already passed this assessment!')
        } else {
          alert('Unable to start assessment. Please try again.')
        }
      }
    },
    
    viewCertificate() {
      const rolePrefix = this.userRole === 'employee' ? '/employee' : this.userRole === 'manager' ? '/manager' : '/admin'
      this.$router.push(`${rolePrefix}/learning/certificate/${this.courseId}`)
    },
    
    toggleModule(index) {
      this.openModules[index] = !this.openModules[index]
    },
    
    goBack() {
      const rolePrefix = this.userRole === 'employee' ? '/employee' : this.userRole === 'manager' ? '/manager' : '/admin'
      this.$router.push(`${rolePrefix}/learning`)
    },
    
    formatStatus(status) {
      const map = {
        enrolled: 'Enrolled',
        in_progress: 'In Progress',
        completed: 'Completed',
        failed: 'Failed'
      }
      return map[status] || status || 'Enrolled'
    },
    
    categoryColor(cat) {
      const map = {
        'Onboarding': 'linear-gradient(135deg,#6366f1,#8b5cf6)',
        'Software': 'linear-gradient(135deg,#0ea5e9,#6366f1)',
        'Compliance': 'linear-gradient(135deg,#f59e0b,#ef4444)',
        'Leadership': 'linear-gradient(135deg,#10b981,#0ea5e9)',
        'Finance': 'linear-gradient(135deg,#f59e0b,#10b981)',
      }
      return map[cat] || 'linear-gradient(135deg,#6366f1,#8b5cf6)'
    }
  }
}
</script>

<style scoped>
/* Same styles as previous CourseDetail component */
.course-detail {
  min-height: 100vh;
  background: #f9fafb;
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

.course-header {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  padding: 2rem;
}

.header-content {
  max-width: 1200px;
  margin: 0 auto;
}

.back-btn {
  background: rgba(255,255,255,0.2);
  border: none;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.875rem;
  margin-bottom: 1rem;
}

.back-btn:hover {
  background: rgba(255,255,255,0.3);
}

.course-header h1 {
  font-size: 2rem;
  margin: 0 0 0.5rem;
}

.course-description {
  font-size: 1rem;
  opacity: 0.9;
  margin-bottom: 1rem;
}

.course-meta {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.meta-badge {
  background: rgba(255,255,255,0.2);
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
}

.course-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 2rem;
}

.course-sidebar {
  position: sticky;
  top: 2rem;
  height: fit-content;
}

.enrollment-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.progress-section h4,
.enroll-section h4 {
  margin: 0 0 1rem;
  font-size: 1rem;
  color: #111827;
}

.progress-track {
  background: #f3f4f6;
  border-radius: 10px;
  height: 8px;
  overflow: hidden;
}

.progress-track.large {
  height: 12px;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #6366f1, #8b5cf6);
  border-radius: 10px;
  transition: width 0.3s;
}

.progress-stats {
  display: flex;
  justify-content: space-between;
  margin: 0.5rem 0;
  font-size: 0.8rem;
  color: #6b7280;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 600;
  margin-top: 0.5rem;
}

.status-badge.enrolled { background: #eff6ff; color: #1d4ed8; }
.status-badge.in_progress { background: #fef9c3; color: #854d0e; }
.status-badge.completed { background: #f0fdf4; color: #166534; }

.certificate-link {
  margin-top: 1rem;
}

.certificate-btn {
  width: 100%;
  padding: 0.5rem;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

.enroll-btn-large {
  width: 100%;
  padding: 0.75rem;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
}

.enroll-btn-large:hover:not(:disabled) {
  background: #4338ca;
}

.enroll-btn-large:disabled {
  background: #a5b4fc;
  cursor: not-allowed;
}

.course-main {
  min-width: 0;
}

.modules-section h2,
.assessment-section h2 {
  font-size: 1.25rem;
  margin: 0 0 1rem;
  color: #111827;
}

.modules-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.module-item {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #e5e7eb;
  transition: box-shadow 0.2s;
}

.module-item:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.module-item.completed {
  border-left: 4px solid #10b981;
}

.module-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  cursor: pointer;
  background: white;
}

.module-header:hover {
  background: #f9fafb;
}

.module-icon {
  flex-shrink: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: #f3f4f6;
  color: #4f46e5;
  font-weight: 600;
}

.module-item.completed .module-icon {
  background: #10b981;
  color: white;
}

.module-info {
  flex: 1;
}

.module-info h3 {
  margin: 0;
  font-size: 1rem;
  color: #111827;
}

.module-desc {
  margin: 0.25rem 0 0;
  font-size: 0.8rem;
  color: #6b7280;
}

.module-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #9ca3af;
}

.duration {
  font-size: 0.7rem;
}

.module-content {
  padding: 1rem;
  border-top: 1px solid #e5e7eb;
  background: #f9fafb;
}

.video-player,
.pdf-viewer {
  margin-bottom: 1rem;
}

.video-element {
  width: 100%;
  border-radius: 8px;
}

.pdf-iframe {
  width: 100%;
  height: 400px;
  border: none;
  border-radius: 8px;
}

.text-content {
  line-height: 1.6;
  color: #374151;
  margin-bottom: 1rem;
}

.module-footer {
  margin-top: 1rem;
  text-align: right;
}

.complete-btn {
  padding: 0.5rem 1rem;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

.complete-btn:hover {
  background: #4338ca;
}

.completed-badge {
  color: #10b981;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.assessment-section {
  margin-top: 2rem;
}

.assessment-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.assessment-info h3 {
  margin: 0 0 0.5rem;
  font-size: 1.1rem;
}

.assessment-info p {
  margin: 0.25rem 0;
  font-size: 0.8rem;
  color: #6b7280;
}

.assessment-btn {
  padding: 0.5rem 1.5rem;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
}

.assessment-btn:disabled {
  background: #e5e7eb;
  color: #9ca3af;
  cursor: not-allowed;
}

.error-state {
  text-align: center;
  padding: 4rem;
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
  .course-content {
    grid-template-columns: 1fr;
    padding: 1rem;
  }
  
  .course-sidebar {
    position: static;
  }
  
  .course-header h1 {
    font-size: 1.5rem;
  }
  
  .assessment-card {
    flex-direction: column;
    text-align: center;
  }
}
</style>