<!-- src/views/learning/LearningCatalog.vue -->
<template>
  <div class="catalog-view">
    <!-- Header -->
    <div class="catalog-header">
      <div class="header-text">
        <h1>Learning &amp; Development</h1>
        <p>Grow your skills. Track your progress. Earn certifications.</p>
      </div>
      <div class="header-stats" v-if="stats">
        <div class="stat-pill">
          <span class="stat-num">{{ stats.enrolled || 0 }}</span>
          <span class="stat-lbl">Enrolled</span>
        </div>
        <div class="stat-pill">
          <span class="stat-num">{{ stats.completed || 0 }}</span>
          <span class="stat-lbl">Completed</span>
        </div>
        <div class="stat-pill accent">
          <span class="stat-num">{{ stats.in_progress || 0 }}</span>
          <span class="stat-lbl">In Progress</span>
        </div>
      </div>
    </div>

    <!-- Admin/Manager Actions Bar -->
    <div v-if="isAdminOrManager" class="admin-bar">
      <button @click="openCreateCourseModal" class="btn-primary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Create New Course
      </button>
      <button @click="goToCourseManagement" class="btn-secondary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M12 4v16M4 12h16"></path>
        </svg>
        Manage Courses
      </button>
      <button @click="goToReports" class="btn-secondary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 12v3a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4v-3"></path>
          <path d="M12 2v8m0 0-3-3m3 3 3-3"></path>
        </svg>
        Reports
      </button>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <div class="search-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/>
          <line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input v-model="search" type="text" placeholder="Search courses…" class="search-input" />
      </div>
      <div class="filter-pills">
        <button
          v-for="cat in categories"
          :key="cat"
          @click="activeCategory = cat"
          :class="['pill-btn', { active: activeCategory === cat }]"
        >{{ cat }}</button>
      </div>
      <div class="view-toggle">
        <button @click="view = 'grid'" :class="['toggle-btn', { active: view === 'grid' }]">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
            <rect x="3" y="3" width="7" height="7"/>
            <rect x="14" y="3" width="7" height="7"/>
            <rect x="3" y="14" width="7" height="7"/>
            <rect x="14" y="14" width="7" height="7"/>
          </svg>
        </button>
        <button @click="view = 'list'" :class="['toggle-btn', { active: view === 'list' }]">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
            <line x1="8" y1="6" x2="21" y2="6"/>
            <line x1="8" y1="12" x2="21" y2="12"/>
            <line x1="8" y1="18" x2="21" y2="18"/>
            <line x1="3" y1="6" x2="3.01" y2="6"/>
            <line x1="3" y1="12" x2="3.01" y2="12"/>
            <line x1="3" y1="18" x2="3.01" y2="18"/>
          </svg>
        </button>
      </div>
    </div>

    <!-- My Courses Section (Continue Learning) -->
    <section v-if="myCourses.length > 0" class="section">
      <h2 class="section-title">Continue Learning</h2>
      <div class="my-courses-row">
        <div
          v-for="course in myCourses"
          :key="'my-' + course.id"
          class="progress-card"
          @click="goToCourse(course.id)"
        >
          <div class="progress-card-top" :style="{ background: categoryColor(course.category) }">
            <span class="course-category-badge">{{ course.category || 'General' }}</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="play-icon" viewBox="0 0 24 24" fill="currentColor">
              <circle cx="12" cy="12" r="10"/>
              <polygon points="10 8 16 12 10 16 10 8" fill="white"/>
            </svg>
          </div>
          <div class="progress-card-body">
            <h4>{{ course.title }}</h4>
            <div class="progress-row">
              <div class="progress-track">
                <div class="progress-fill" :style="{ width: (course.enrollment?.progress_percent || 0) + '%' }"></div>
              </div>
              <span class="progress-pct">{{ course.enrollment?.progress_percent || 0 }}%</span>
            </div>
            <div class="progress-meta">
              <span :class="['status-chip', course.enrollment?.status || 'enrolled']">
                {{ formatStatus(course.enrollment?.status || 'enrolled') }}
              </span>
              <span class="modules-done">{{ course.completed_modules || 0 }}/{{ course.total_modules || 0 }} modules</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Course Catalog -->
    <section class="section">
      <h2 class="section-title">
        {{ activeCategory === 'All' ? 'All Courses' : activeCategory }}
        <span class="count-badge">{{ filteredCourses.length }}</span>
      </h2>

      <!-- Grid View -->
      <div v-if="view === 'grid'" class="course-grid">
        <div
          v-for="course in filteredCourses"
          :key="course.id"
          class="course-card"
          @click="goToCourse(course.id)"
        >
          <div class="card-thumb" :style="{ background: categoryColor(course.category) }">
            <span class="thumb-category">{{ course.category || 'General' }}</span>
            <div class="thumb-icon">{{ categoryIcon(course.category) }}</div>
          </div>
          <div class="card-body">
            <h3 class="card-title">{{ course.title }}</h3>
            <p class="card-desc">{{ truncate(course.description, 80) }}</p>
            <div class="card-meta">
              <span class="meta-item">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                  <rect x="3" y="4" width="18" height="18" rx="2"/>
                  <line x1="16" y1="2" x2="16" y2="6"/>
                  <line x1="8" y1="2" x2="8" y2="6"/>
                  <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
                {{ course.modules_count || course.total_modules || 0 }} modules
              </span>
              <span class="meta-item" v-if="course.estimated_minutes">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                  <circle cx="12" cy="12" r="10"/>
                  <polyline points="12 6 12 12 16 14"/>
                </svg>
                {{ course.estimated_minutes }}m
              </span>
            </div>
          </div>
          <div class="card-footer">
            <div v-if="course.is_enrolled || course.enrollment" class="enrolled-state">
              <div class="mini-progress">
                <div class="mini-fill" :style="{ width: (course.enrollment?.progress_percent || 0) + '%' }"></div>
              </div>
              <span :class="['enroll-badge', course.enrollment?.status || 'enrolled']">
                {{ formatStatus(course.enrollment?.status || 'enrolled') }}
              </span>
            </div>
            <button v-else-if="course.allow_self_enroll !== false" @click.stop="enrollInCourse(course)" class="enroll-btn" :disabled="enrolling === course.id">
              {{ enrolling === course.id ? 'Enrolling…' : 'Enroll' }}
            </button>
            <span v-else class="assigned-label">Assigned by admin</span>
          </div>
        </div>
      </div>

      <!-- List View -->
      <div v-else class="course-list">
        <div
          v-for="course in filteredCourses"
          :key="course.id"
          class="list-row"
          @click="goToCourse(course.id)"
        >
          <div class="list-thumb" :style="{ background: categoryColor(course.category) }">
            <span style="font-size:1.25rem">{{ categoryIcon(course.category) }}</span>
          </div>
          <div class="list-info">
            <h4 class="list-title">{{ course.title }}</h4>
            <p class="list-desc">{{ truncate(course.description, 100) }}</p>
            <div class="list-tags">
              <span class="tag">{{ course.category || 'General' }}</span>
              <span class="tag">{{ course.modules_count || course.total_modules || 0 }} modules</span>
            </div>
          </div>
          <div class="list-action">
            <div v-if="course.is_enrolled || course.enrollment">
              <div class="list-progress">
                <div class="progress-track">
                  <div class="progress-fill" :style="{ width: (course.enrollment?.progress_percent || 0) + '%' }"></div>
                </div>
                <span class="progress-pct">{{ course.enrollment?.progress_percent || 0 }}%</span>
              </div>
              <span :class="['status-chip', course.enrollment?.status || 'enrolled']">
                {{ formatStatus(course.enrollment?.status || 'enrolled') }}
              </span>
            </div>
            <button v-else-if="course.allow_self_enroll !== false" @click.stop="enrollInCourse(course)" class="enroll-btn sm" :disabled="enrolling === course.id">
              {{ enrolling === course.id ? 'Enrolling…' : 'Enroll' }}
            </button>
          </div>
        </div>
      </div>

      <div v-if="filteredCourses.length === 0 && !loading" class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="empty-icon">
          <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
          <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
        </svg>
        <p>No courses found{{ search ? ' for "' + search + '"' : '' }}</p>
      </div>
    </section>

    <!-- Create Course Modal -->
    <div v-if="showCourseModal" class="modal-overlay" @click="closeCourseModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>Create New Course</h2>
          <button class="close-btn" @click="closeCourseModal">×</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveCourse">
            <div class="form-group">
              <label>Course Title *</label>
              <input v-model="courseForm.title" type="text" required />
            </div>
            <div class="form-group">
              <label>Category</label>
              <select v-model="courseForm.category">
                <option value="General">General</option>
                <option value="Onboarding">Onboarding</option>
                <option value="Software">Software</option>
                <option value="Compliance">Compliance</option>
                <option value="Leadership">Leadership</option>
                <option value="Finance">Finance</option>
              </select>
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea v-model="courseForm.description" rows="3"></textarea>
            </div>
            <div class="form-group">
              <label>Estimated Minutes</label>
              <input v-model.number="courseForm.estimated_minutes" type="number" />
            </div>
            <div class="form-check">
              <input type="checkbox" v-model="courseForm.allow_self_enroll" id="allow_self_enroll" />
              <label for="allow_self_enroll">Allow self-enrollment</label>
            </div>
            <div class="form-actions">
              <button type="button" @click="closeCourseModal" class="btn-secondary">Cancel</button>
              <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Create Course' }}</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'LearningCatalog',
  data() {
    return {
      courses: [],
      myCourses: [],
      stats: null,
      search: '',
      activeCategory: 'All',
      view: 'grid',
      loading: false,
      enrolling: null,
      saving: false,
      showCourseModal: false,
      userRole: 'employee',
      courseForm: {
        title: '',
        category: 'General',
        description: '',
        estimated_minutes: 60,
        allow_self_enroll: true
      }
    }
  },
  computed: {
    isAdminOrManager() {
      return this.userRole === 'admin' || this.userRole === 'manager'
    },
    categories() {
      const cats = ['All', ...new Set(this.courses.map(c => c.category).filter(Boolean))]
      return cats
    },
    filteredCourses() {
      return this.courses.filter(c => {
        const matchCat = this.activeCategory === 'All' || c.category === this.activeCategory
        const matchSearch = !this.search || c.title.toLowerCase().includes(this.search.toLowerCase())
        return matchCat && matchSearch
      })
    }
  },
  mounted() {
    this.getUserRole()
    this.fetchData()
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
    async fetchData() {
      this.loading = true
      try {
        const [coursesRes, progressRes] = await Promise.all([
          axios.get('/api/learning/courses'),
          axios.get('/api/learning/my-progress')
        ])
        
        this.courses = coursesRes.data.data?.data || coursesRes.data.data || []
        this.myCourses = progressRes.data.data || []
        this.stats = progressRes.data.stats || { enrolled: 0, completed: 0, in_progress: 0 }
      } catch (error) {
        console.error('Fetch error:', error)
      } finally {
        this.loading = false
      }
    },
    async enrollInCourse(course) {
      this.enrolling = course.id
      try {
        await axios.post(`/api/learning/courses/${course.id}/enroll`)
        this.$router.push(`/learning/courses/${course.id}`)
      } catch (error) {
        console.error(error)
        const message = error.response?.data?.message || 'Failed to enroll'
        alert(message)
      } finally {
        this.enrolling = null
      }
    },
    goToCourse(id) {
      const rolePrefix = this.userRole === 'admin' ? '/admin' : this.userRole === 'manager' ? '/manager' : '/employee'
      this.$router.push(`${rolePrefix}/learning/courses/${id}`)
    },
    goToCourseManagement() {
      const rolePrefix = this.userRole === 'admin' ? '/admin' : '/manager'
      this.$router.push(`${rolePrefix}/learning/manage`)
    },
    goToReports() {
      const rolePrefix = this.userRole === 'admin' ? '/admin' : '/manager'
      this.$router.push(`${rolePrefix}/learning/reports`)
    },
    openCreateCourseModal() {
      this.showCourseModal = true
    },
    async saveCourse() {
      this.saving = true
      try {
        await axios.post('/api/learning/courses', this.courseForm)
        await this.fetchData()
        this.closeCourseModal()
      } catch (error) {
        console.error('Failed to save course:', error)
        alert('Failed to save course')
      } finally {
        this.saving = false
      }
    },
    closeCourseModal() {
      this.showCourseModal = false
      this.courseForm = {
        title: '',
        category: 'General',
        description: '',
        estimated_minutes: 60,
        allow_self_enroll: true
      }
    },
    truncate(str, n) {
      if (!str) return ''
      return str.length > n ? str.slice(0, n) + '…' : str
    },
    formatStatus(s) {
      const map = { enrolled: 'Enrolled', in_progress: 'In Progress', completed: 'Completed', failed: 'Failed' }
      return map[s] || s || 'Enrolled'
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
    },
    categoryIcon(cat) {
      const map = { 'Onboarding': '🚀', 'Software': '💻', 'Compliance': '📋', 'Leadership': '🎯', 'Finance': '💰' }
      return map[cat] || '📚'
    }
  }
}
</script>

<style scoped>
.catalog-view {
  min-height: 100vh;
  background: #f3f4f6;
  padding: 2rem;
  font-family: 'Inter', -apple-system, sans-serif;
  color: #1f2937;
}

.catalog-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.header-text h1 {
  font-size: 1.75rem;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.header-text p { color: #6b7280; margin: 0.25rem 0 0; }

.header-stats { display: flex; gap: 0.75rem; }

.stat-pill {
  background: white;
  border-radius: 12px;
  padding: 0.75rem 1.25rem;
  text-align: center;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  min-width: 80px;
}

.stat-pill.accent { background: #4f46e5; }
.stat-pill.accent .stat-num,
.stat-pill.accent .stat-lbl { color: white; }

.stat-num { display: block; font-size: 1.4rem; font-weight: 700; color: #111827; }
.stat-lbl { display: block; font-size: 0.7rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }

.admin-bar {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  flex-wrap: wrap;
}

.btn-primary, .btn-secondary {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  border: none;
}

.btn-primary { background: #4f46e5; color: white; }
.btn-primary:hover { background: #4338ca; }
.btn-secondary { background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; }
.btn-secondary:hover { background: #e5e7eb; }

.filters-bar {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  background: white;
  border-radius: 12px;
  padding: 0.75rem 1rem;
}

.search-wrap { position: relative; flex: 1; min-width: 200px; }
.search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #9ca3af; }
.search-input { width: 100%; padding: 0.5rem 0.75rem 0.5rem 2.25rem; border: 1px solid #e5e7eb; border-radius: 8px; }

.filter-pills { display: flex; gap: 0.4rem; flex-wrap: wrap; }
.pill-btn {
  padding: 0.35rem 0.85rem;
  border-radius: 20px;
  border: 1px solid #e5e7eb;
  background: transparent;
  font-size: 0.8rem;
  cursor: pointer;
}
.pill-btn.active { background: #4f46e5; color: white; border-color: #4f46e5; }

.view-toggle { display: flex; gap: 0.25rem; }
.toggle-btn {
  padding: 0.4rem 0.6rem;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  background: transparent;
  cursor: pointer;
}
.toggle-btn.active { background: #4f46e5; color: white; }

.section { margin-bottom: 2.5rem; }
.section-title { font-size: 1.1rem; font-weight: 600; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; }

.my-courses-row { display: flex; gap: 1rem; overflow-x: auto; padding-bottom: 0.5rem; }
.progress-card {
  background: white;
  border-radius: 12px;
  min-width: 220px;
  cursor: pointer;
  overflow: hidden;
}
.progress-card-top { height: 90px; display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; }
.progress-card-body { padding: 0.85rem; }
.progress-row { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
.progress-track { flex: 1; height: 6px; background: #f3f4f6; border-radius: 3px; overflow: hidden; }
.progress-fill { height: 100%; background: linear-gradient(90deg, #6366f1, #8b5cf6); }

.course-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 1.25rem; }
.course-card { background: white; border-radius: 14px; overflow: hidden; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
.course-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
.card-thumb { height: 120px; display: flex; align-items: flex-end; justify-content: space-between; padding: 0.75rem; }
.card-body { padding: 1rem; }
.card-title { font-size: 1rem; font-weight: 600; margin: 0 0 0.4rem; }
.card-desc { font-size: 0.8rem; color: #6b7280; }
.card-meta { display: flex; gap: 0.5rem; margin-top: 0.5rem; }
.meta-item { display: flex; align-items: center; gap: 4px; font-size: 0.75rem; color: #6b7280; }
.card-footer { padding: 0.75rem 1rem; border-top: 1px solid #f3f4f6; }

.enroll-btn {
  background: #4f46e5;
  color: white;
  border: none;
  padding: 0.4rem 1rem;
  border-radius: 20px;
  cursor: pointer;
}
.enroll-btn:disabled { background: #a5b4fc; cursor: not-allowed; }

.status-chip, .enroll-badge {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: 10px;
}
.status-chip.enrolled, .enroll-badge.enrolled { background: #eff6ff; color: #1d4ed8; }
.status-chip.in_progress, .enroll-badge.in_progress { background: #fef9c3; color: #854d0e; }
.status-chip.completed, .enroll-badge.completed { background: #f0fdf4; color: #166534; }

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.modal-content {
  background: white;
  border-radius: 16px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}
.modal-body { padding: 1.5rem; }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; font-size: 0.8rem; margin-bottom: 0.25rem; }
.form-group input, .form-group select, .form-group textarea {
  width: 100%;
  padding: 0.5rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
}
.form-check { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; }
.form-actions { display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1rem; }

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255,255,255,0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
}
.spinner {
  width: 36px;
  height: 36px;
  border: 3px solid #e5e7eb;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.empty-state { text-align: center; padding: 3rem; color: #9ca3af; }
</style>