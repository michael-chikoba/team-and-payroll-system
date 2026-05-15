<!-- src/views/learning/MyProgress.vue -->
<template>
  <div class="my-progress">
    <div class="header">
      <h1>My Learning Progress</h1>
      <p>Track your course completions and achievements</p>
    </div>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-value">{{ stats.enrolled }}</div>
        <div class="stat-label">Enrolled</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ stats.in_progress }}</div>
        <div class="stat-label">In Progress</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ stats.completed }}</div>
        <div class="stat-label">Completed</div>
      </div>
      <div class="stat-card">
        <div class="stat-value">{{ averageProgress }}%</div>
        <div class="stat-label">Avg Progress</div>
      </div>
    </div>

    <div class="courses-list">
      <h2>My Courses</h2>
      <div v-for="course in myCourses" :key="course.id" class="course-progress-card">
        <div class="course-header">
          <h3>{{ course.title }}</h3>
          <span :class="['status-badge', course.enrollment.status]">
            {{ formatStatus(course.enrollment.status) }}
          </span>
        </div>
        <div class="progress-section">
          <div class="progress-track">
            <div class="progress-fill" :style="{ width: course.enrollment.progress_percent + '%' }"></div>
          </div>
          <span class="progress-percent">{{ course.enrollment.progress_percent }}%</span>
        </div>
        <div class="course-meta">
          <span>{{ course.completed_modules }}/{{ course.total_modules }} modules completed</span>
          <button @click="continueCourse(course.id)" class="continue-btn">
            {{ course.enrollment.progress_percent === 100 ? 'Review' : 'Continue' }}
          </button>
        </div>
      </div>
      <div v-if="myCourses.length === 0" class="empty-state">
        <p>You haven't enrolled in any courses yet.</p>
        <button @click="browseCourses" class="browse-btn">Browse Courses</button>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'MyProgress',
  data() {
    return {
      myCourses: [],
      stats: {
        enrolled: 0,
        in_progress: 0,
        completed: 0
      },
      userRole: 'employee'
    }
  },
  computed: {
    averageProgress() {
      if (this.myCourses.length === 0) return 0
      const total = this.myCourses.reduce((sum, c) => sum + (c.enrollment?.progress_percent || 0), 0)
      return Math.round(total / this.myCourses.length)
    }
  },
  mounted() {
    this.getUserRole()
    this.fetchMyProgress()
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
    
    async fetchMyProgress() {
      try {
        const response = await axios.get('/api/learning/my-progress')
        this.myCourses = response.data.data || []
        this.stats = response.data.stats || { enrolled: 0, in_progress: 0, completed: 0 }
      } catch (error) {
        console.error('Failed to fetch progress:', error)
      }
    },
    
    continueCourse(courseId) {
      const rolePrefix = this.userRole === 'employee' ? '/employee' : this.userRole === 'manager' ? '/manager' : '/admin'
      this.$router.push(`${rolePrefix}/learning/courses/${courseId}`)
    },
    
    browseCourses() {
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
      return map[status] || status
    }
  }
}
</script>

<style scoped>
.my-progress {
  padding: 2rem;
  max-width: 1200px;
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

.courses-list h2 {
  font-size: 1.25rem;
  margin-bottom: 1rem;
  color: #111827;
}

.course-progress-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.course-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.course-header h3 {
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

.course-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.course-meta span {
  font-size: 0.75rem;
  color: #6b7280;
}

.continue-btn {
  padding: 0.5rem 1rem;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.875rem;
}

.continue-btn:hover {
  background: #4338ca;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  background: white;
  border-radius: 12px;
}

.empty-state p {
  color: #6b7280;
  margin-bottom: 1rem;
}

.browse-btn {
  padding: 0.5rem 1rem;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}
</style>