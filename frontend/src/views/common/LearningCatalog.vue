<template>
  <div class="catalog-view">
    <!-- Header -->
    <div class="catalog-header">
      <div class="header-text">
        <h1>Learning &amp; Development</h1>
        <p>Grow your skills. Track your progress. Earn completions.</p>
      </div>
      <div class="header-stats" v-if="stats">
        <div class="stat-pill">
          <span class="stat-num">{{ stats.enrolled || stats.total_enrolled || 0 }}</span>
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
    <div v-if="userRole === 'admin' || userRole === 'manager'" class="admin-bar">
      <button @click="openCreateCourseModal" class="btn-primary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="12" y1="5" x2="12" y2="19"></line>
          <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        Create New Course
      </button>
      <button @click="toggleEmployeeProgress" :class="['btn-secondary', { active: showEmployeeProgress }]">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
          <circle cx="9" cy="7" r="4"></circle>
          <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
          <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
        </svg>
        {{ showEmployeeProgress ? 'Hide' : 'View' }} Employee Progress
      </button>
    </div>

    <!-- Employee Progress Dashboard (Admin/Manager Only) -->
    <div v-if="showEmployeeProgress && (userRole === 'admin' || userRole === 'manager')" class="progress-dashboard">
      <div class="dashboard-header">
        <h3>Employee Learning Progress</h3>
        <div class="dashboard-filters">
          <input v-model="employeeSearch" type="text" placeholder="Search employee..." class="search-input-sm" />
          <select v-model="selectedCourseFilter" class="filter-select-sm">
            <option value="">All Courses</option>
            <option v-for="course in courses" :key="course.id" :value="course.id">{{ course.title }}</option>
          </select>
          <button @click="exportReport" class="export-btn-sm">Export CSV</button>
        </div>
      </div>
      <div class="progress-table">
        <table>
          <thead>
            <tr>
              <th>Employee</th>
              <th>Department</th>
              <th>Course</th>
              <th>Progress</th>
              <th>Status</th>
              <th>Enrolled Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="progress in filteredEmployeeProgress" :key="progress.id">
              <td>
                <div class="employee-cell">
                  <div class="employee-avatar">{{ getInitials(progress.employee?.name || progress.employee_name) }}</div>
                  <span>{{ progress.employee?.name || progress.employee_name }}</span>
                </div>
              </td>
              <td>{{ progress.employee?.department || progress.department || '—' }}</td>
              <td>{{ progress.course?.title || progress.course_title }}</td>
              <td>
                <div class="table-progress">
                  <div class="progress-track">
                    <div class="progress-fill" :style="{ width: (progress.progress_percent || 0) + '%' }"></div>
                  </div>
                  <span class="progress-pct">{{ progress.progress_percent || 0 }}%</span>
                </div>
              </td>
              <td>
                <span :class="['status-chip', progress.status]">{{ formatStatus(progress.status) }}</span>
              </td>
              <td>{{ formatDate(progress.enrolled_at || progress.created_at) }}</td>
              <td>
                <button @click="viewEmployeeDetails(progress)" class="icon-action" title="View Details">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M22 12c0 5.52-4.48 10-10 10S2 17.52 2 12 6.48 2 12 2s10 4.48 10 10z"></path>
                  </svg>
                </button>
              </td>
            </tr>
            <tr v-if="filteredEmployeeProgress.length === 0">
              <td colspan="7" class="empty-table">No progress data found</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-bar">
      <div class="search-wrap">
        <svg xmlns="http://www.w3.org/2000/svg" class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
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
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        </button>
        <button @click="view = 'list'" :class="['toggle-btn', { active: view === 'list' }]">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        </button>
      </div>
    </div>

    <!-- My Courses Section -->
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
            <svg xmlns="http://www.w3.org/2000/svg" class="play-icon" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8" fill="white"/></svg>
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
              <span :class="['status-chip', course.enrollment?.status || 'enrolled']">{{ formatStatus(course.enrollment?.status || 'enrolled') }}</span>
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
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                {{ course.modules_count || course.total_modules || 0 }} modules
              </span>
              <span class="meta-item" v-if="course.estimated_minutes">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                {{ course.estimated_minutes }}m
              </span>
              <span v-if="course.assessment" class="meta-item quiz-flag">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                Quiz
              </span>
            </div>
          </div>
          <div class="card-footer">
            <div v-if="course.is_enrolled || course.enrollment" class="enrolled-state">
              <div class="mini-progress">
                <div class="mini-fill" :style="{ width: (course.enrollment?.progress_percent || 0) + '%' }"></div>
              </div>
              <span :class="['enroll-badge', course.enrollment?.status || 'enrolled']">{{ formatStatus(course.enrollment?.status || 'enrolled') }}</span>
            </div>
            <button v-else-if="course.allow_self_enroll !== false" @click.stop="enrollIn(course)" class="enroll-btn" :disabled="enrolling === course.id">
              {{ enrolling === course.id ? 'Enrolling…' : 'Enroll' }}
            </button>
            <div v-else-if="userRole === 'admin' || userRole === 'manager'" class="admin-actions">
              <button @click.stop="editCourse(course)" class="icon-action-sm" title="Edit Course">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M17 3l4 4-7 7H10v-4l7-7z"></path>
                  <path d="M4 20h16"></path>
                </svg>
              </button>
              <button @click.stop="deleteCourse(course)" class="icon-action-sm delete" title="Delete Course">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
              </button>
            </div>
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
              <span v-if="course.assessment" class="tag quiz">Quiz included</span>
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
              <span :class="['status-chip', course.enrollment?.status || 'enrolled']">{{ formatStatus(course.enrollment?.status || 'enrolled') }}</span>
            </div>
            <button v-else-if="course.allow_self_enroll !== false" @click.stop="enrollIn(course)" class="enroll-btn sm" :disabled="enrolling === course.id">
              {{ enrolling === course.id ? 'Enrolling…' : 'Enroll' }}
            </button>
            <div v-else-if="userRole === 'admin' || userRole === 'manager'" class="admin-actions-list">
              <button @click.stop="editCourse(course)" class="icon-action-sm" title="Edit Course">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M17 3l4 4-7 7H10v-4l7-7z"></path>
                  <path d="M4 20h16"></path>
                </svg>
              </button>
              <button @click.stop="deleteCourse(course)" class="icon-action-sm delete" title="Delete Course">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="3 6 5 6 21 6"></polyline>
                  <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="filteredCourses.length === 0 && !loading" class="empty-state">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="empty-icon"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        <p>No courses found{{ search ? ' for "' + search + '"' : '' }}</p>
        <button v-if="userRole === 'admin' || userRole === 'manager'" @click="openCreateCourseModal" class="btn-primary" style="margin-top: 1rem;">Create your first course</button>
      </div>
    </section>

    <!-- Create/Edit Course Modal -->
    <div v-if="showCourseModal" class="modal-overlay" @click="closeCourseModal">
      <div class="modal-content large" @click.stop>
        <div class="modal-header">
          <h2>{{ editingCourse ? 'Edit Course' : 'Create New Course' }}</h2>
          <button class="close-btn" @click="closeCourseModal">×</button>
        </div>
        <div class="modal-body">
          <form @submit.prevent="saveCourse" class="course-form">
            <div class="form-group">
              <label>Course Title *</label>
              <input v-model="courseForm.title" type="text" required />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Category</label>
                <select v-model="courseForm.category">
                  <option value="Onboarding">Onboarding</option>
                  <option value="Software">Software</option>
                  <option value="Compliance">Compliance</option>
                  <option value="Leadership">Leadership</option>
                  <option value="Finance">Finance</option>
                  <option value="General">General</option>
                </select>
              </div>
              <div class="form-group">
                <label>Estimated Minutes</label>
                <input v-model.number="courseForm.estimated_minutes" type="number" />
              </div>
              <div class="form-check" style="align-self: flex-end; padding-bottom: 0.5rem;">
                <input type="checkbox" v-model="courseForm.allow_self_enroll" id="allow_self_enroll" />
                <label for="allow_self_enroll">Self-enroll</label>
              </div>
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea v-model="courseForm.description" rows="3" placeholder="Brief description of the course"></textarea>
            </div>
            <div class="form-group">
              <label>Assigned Departments (optional)</label>
              <select v-model="courseForm.assigned_departments" multiple class="multi-select">
                <option v-for="dept in departments" :key="dept" :value="dept">{{ dept }}</option>
              </select>
              <small>Hold Ctrl/Cmd to select multiple</small>
            </div>
            <div class="form-group">
              <label>Assigned Roles (optional)</label>
              <select v-model="courseForm.assigned_roles" multiple class="multi-select">
                <option value="employee">Employee</option>
                <option value="manager">Manager</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div class="form-actions">
              <button type="button" @click="closeCourseModal" class="btn-secondary">Cancel</button>
              <button type="submit" class="btn-primary" :disabled="saving">{{ saving ? 'Saving...' : (editingCourse ? 'Update Course' : 'Create Course') }}</button>
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
      employeeProgress: [],
      search: '',
      activeCategory: 'All',
      view: 'grid',
      loading: false,
      enrolling: null,
      saving: false,
      showCourseModal: false,
      showEmployeeProgress: false,
      editingCourse: null,
      employeeSearch: '',
      selectedCourseFilter: '',
      userRole: 'employee',
      departments: ['HR', 'IT', 'Finance', 'Marketing', 'Sales', 'Operations'],
      courseForm: {
        title: '',
        category: 'General',
        estimated_minutes: 60,
        description: '',
        allow_self_enroll: true,
        assigned_departments: [],
        assigned_roles: []
      }
    }
  },
  computed: {
    categories() {
      const cats = ['All', ...new Set(this.courses.map(c => c.category).filter(Boolean))]
      return cats
    },
    filteredCourses() {
      return this.courses.filter(c => {
        const matchCat = this.activeCategory === 'All' || c.category === this.activeCategory
        const matchSearch = !this.search || c.title.toLowerCase().includes(this.search.toLowerCase()) || (c.description || '').toLowerCase().includes(this.search.toLowerCase())
        return matchCat && matchSearch
      })
    },
    filteredEmployeeProgress() {
      let filtered = [...this.employeeProgress]
      if (this.employeeSearch) {
        const search = this.employeeSearch.toLowerCase()
        filtered = filtered.filter(p => {
          const employeeName = p.employee?.name || p.employee_name || ''
          return employeeName.toLowerCase().includes(search)
        })
      }
      if (this.selectedCourseFilter) {
        filtered = filtered.filter(p => p.course_id === this.selectedCourseFilter)
      }
      return filtered
    }
  },
  mounted() {
    this.getUserRole()
    this.fetchData()
  },
  methods: {
    getUserRole() {
      const user = this.$store?.state?.auth?.user || JSON.parse(localStorage.getItem('user') || '{}')
      this.userRole = user.role || localStorage.getItem('user_role') || 'employee'
    },
    
    async fetchData() {
      this.loading = true
      try {
        // Fetch all courses
        const coursesRes = await axios.get('/api/learning/courses')
        this.courses = coursesRes.data.data?.data || coursesRes.data.data || []
        
        // Fetch my progress
        const progressRes = await axios.get('/api/learning/my-progress')
        const progressData = progressRes.data.data || []
        
        // Calculate stats from enrollments
        const enrollments = progressData.filter(p => p.enrollment)
        this.stats = {
          total_enrolled: enrollments.length,
          completed: enrollments.filter(e => e.enrollment?.status === 'completed').length,
          in_progress: enrollments.filter(e => e.enrollment?.status === 'in_progress').length,
          enrolled: enrollments.filter(e => e.enrollment?.status === 'enrolled').length
        }
        
        // Process my courses (in progress or enrolled)
        this.myCourses = progressData
          .filter(c => c.enrollment && c.enrollment.status !== 'completed')
          .map(c => ({
            ...c,
            enrollment: c.enrollment,
            completed_modules: c.completed_modules || 0,
            total_modules: c.total_modules || 0
          }))
        
        // Mark enrolled status on courses
        const enrolledCourseIds = new Set(progressData.map(p => p.id))
        this.courses = this.courses.map(course => ({
          ...course,
          is_enrolled: enrolledCourseIds.has(course.id),
          enrollment: progressData.find(p => p.id === course.id)?.enrollment
        }))
        
        // Fetch employee progress for admin/manager
        if (this.userRole === 'admin' || this.userRole === 'manager') {
          await this.fetchEmployeeProgress()
        }
      } catch (e) {
        console.error('Fetch error:', e)
        if (e.response?.status === 403) {
          console.log('Permission denied - check user role')
        }
      } finally {
        this.loading = false
      }
    },
    
    async fetchEmployeeProgress() {
      try {
        const res = await axios.get('/api/learning/report', {
          params: { per_page: 100 }
        })
        this.employeeProgress = res.data.data?.data || res.data.data || []
      } catch (e) {
        console.error('Failed to fetch employee progress:', e)
      }
    },
    
    async enrollIn(course) {
      this.enrolling = course.id
      try {
        await axios.post(`/api/learning/courses/${course.id}/enroll`)
        await this.fetchData()
        this.$router.push(`/learning/courses/${course.id}`)
      } catch (e) {
        console.error(e)
        const message = e.response?.data?.message || 'Failed to enroll. Please try again.'
        alert(message)
      } finally {
        this.enrolling = null
      }
    },
    
    goToCourse(id) {
      this.$router.push(`/learning/courses/${id}`)
    },
    
    openCreateCourseModal() {
      this.editingCourse = null
      this.courseForm = {
        title: '',
        category: 'General',
        estimated_minutes: 60,
        description: '',
        allow_self_enroll: true,
        assigned_departments: [],
        assigned_roles: []
      }
      this.showCourseModal = true
    },
    
    editCourse(course) {
      this.editingCourse = course
      this.courseForm = {
        title: course.title,
        category: course.category || 'General',
        estimated_minutes: course.estimated_minutes || 60,
        description: course.description || '',
        allow_self_enroll: course.allow_self_enroll !== false,
        assigned_departments: course.assigned_departments || [],
        assigned_roles: course.assigned_roles || []
      }
      this.showCourseModal = true
    },
    
    async saveCourse() {
      if (!this.courseForm.title.trim()) {
        alert('Course title is required')
        return
      }
      
      this.saving = true
      try {
        const payload = {
          title: this.courseForm.title,
          category: this.courseForm.category,
          estimated_minutes: this.courseForm.estimated_minutes,
          description: this.courseForm.description,
          allow_self_enroll: this.courseForm.allow_self_enroll,
          assigned_departments: this.courseForm.assigned_departments,
          assigned_roles: this.courseForm.assigned_roles
        }
        
        if (this.editingCourse) {
          await axios.put(`/api/learning/courses/${this.editingCourse.id}`, payload)
        } else {
          await axios.post('/api/learning/courses', payload)
        }
        await this.fetchData()
        this.closeCourseModal()
      } catch (e) {
        console.error('Failed to save course:', e)
        const message = e.response?.data?.message || 'Failed to save course. Please try again.'
        alert(message)
      } finally {
        this.saving = false
      }
    },
    
    async deleteCourse(course) {
      if (!confirm(`Delete "${course.title}"? This action cannot be undone.`)) return
      try {
        await axios.delete(`/api/learning/courses/${course.id}`)
        await this.fetchData()
      } catch (e) {
        console.error('Failed to delete course:', e)
        alert('Failed to delete course. Please try again.')
      }
    },
    
    toggleEmployeeProgress() {
      this.showEmployeeProgress = !this.showEmployeeProgress
      if (this.showEmployeeProgress && this.employeeProgress.length === 0) {
        this.fetchEmployeeProgress()
      }
    },
    
    async exportReport() {
      try {
        const res = await axios.get('/api/learning/report', {
          params: { export: 'csv' },
          responseType: 'blob'
        })
        const url = window.URL.createObjectURL(new Blob([res.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `learning_report_${new Date().toISOString().split('T')[0]}.csv`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
      } catch (e) {
        console.error('Failed to export report:', e)
        alert('Failed to export report')
      }
    },
    
    viewEmployeeDetails(progress) {
      const employeeId = progress.employee_id || progress.employee?.id
      if (employeeId) {
        this.$router.push(`/learning/employee/${employeeId}/progress`)
      }
    },
    
    closeCourseModal() {
      this.showCourseModal = false
      this.editingCourse = null
    },
    
    truncate(str, n) {
      if (!str) return ''
      return str.length > n ? str.slice(0, n) + '…' : str
    },
    
    formatStatus(s) {
      const statusMap = {
        enrolled: 'Enrolled',
        in_progress: 'In Progress',
        completed: 'Completed',
        failed: 'Failed'
      }
      return statusMap[s] || s || 'Enrolled'
    },
    
    formatDate(date) {
      if (!date) return '—'
      return new Date(date).toLocaleDateString()
    },
    
    getInitials(name) {
      if (!name) return '?'
      return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
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
      const map = {
        'Onboarding': '🚀',
        'Software': '💻',
        'Compliance': '📋',
        'Leadership': '🎯',
        'Finance': '📊'
      }
      return map[cat] || '📚'
    }
  }
}
</script>

<style scoped>
/* Keep all your existing styles - they work fine */
.catalog-view {
  min-height: 100vh;
  background: #f3f4f6;
  padding: 2rem;
  font-family: 'Inter', -apple-system, sans-serif;
  color: #1f2937;
}

/* Header */
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

/* Admin Bar */
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
  transition: all 0.15s;
  border: none;
}
.btn-primary {
  background: #4f46e5;
  color: white;
}
.btn-primary:hover { background: #4338ca; }
.btn-primary:disabled { background: #a5b4fc; cursor: not-allowed; }
.btn-secondary {
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #e5e7eb;
}
.btn-secondary:hover { background: #e5e7eb; }
.btn-secondary.active {
  background: #4f46e5;
  color: white;
  border-color: #4f46e5;
}

/* Progress Dashboard */
.progress-dashboard {
  background: white;
  border-radius: 12px;
  padding: 1.25rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  overflow-x: auto;
}
.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  flex-wrap: wrap;
  gap: 1rem;
}
.dashboard-header h3 {
  font-size: 1.1rem;
  font-weight: 600;
  color: #111827;
  margin: 0;
}
.dashboard-filters {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.search-input-sm, .filter-select-sm {
  padding: 0.4rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 0.8rem;
}
.export-btn-sm {
  padding: 0.4rem 0.75rem;
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 0.75rem;
  cursor: pointer;
}
.export-btn-sm:hover { background: #e5e7eb; }
.progress-table {
  overflow-x: auto;
}
.progress-table table {
  width: 100%;
  border-collapse: collapse;
}
.progress-table th,
.progress-table td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #f3f4f6;
}
.progress-table th {
  font-size: 0.75rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.employee-cell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.employee-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 0.7rem;
  font-weight: 600;
}
.table-progress {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-width: 100px;
}
.icon-action {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0.25rem;
  border-radius: 4px;
  color: #6b7280;
  transition: all 0.15s;
}
.icon-action:hover {
  background: #f3f4f6;
  color: #4f46e5;
}
.icon-action-sm {
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0.2rem;
  border-radius: 4px;
  color: #6b7280;
  transition: all 0.15s;
}
.icon-action-sm:hover {
  background: #f3f4f6;
  color: #4f46e5;
}
.icon-action-sm.delete:hover { color: #ef4444; }
.admin-actions, .admin-actions-list {
  display: flex;
  gap: 0.25rem;
}

/* Filters */
.filters-bar {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  background: white;
  border-radius: 12px;
  padding: 0.75rem 1rem;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}
.search-wrap { position: relative; flex: 1; min-width: 200px; }
.search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 16px; height: 16px; color: #9ca3af; }
.search-input { width: 100%; padding: 0.5rem 0.75rem 0.5rem 2.25rem; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.9rem; }
.search-input:focus { outline: none; border-color: #6366f1; }
.filter-pills { display: flex; gap: 0.4rem; flex-wrap: wrap; }
.pill-btn {
  padding: 0.35rem 0.85rem;
  border-radius: 20px;
  border: 1px solid #e5e7eb;
  background: transparent;
  font-size: 0.8rem;
  cursor: pointer;
  color: #6b7280;
  transition: all 0.15s;
}
.pill-btn.active { background: #4f46e5; color: white; border-color: #4f46e5; }
.view-toggle { display: flex; gap: 0.25rem; }
.toggle-btn {
  padding: 0.4rem 0.6rem;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
  background: transparent;
  cursor: pointer;
  color: #6b7280;
  display: flex; align-items: center;
}
.toggle-btn.active { background: #4f46e5; color: white; border-color: #4f46e5; }

/* Sections */
.section { margin-bottom: 2.5rem; }
.section-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #111827;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.count-badge {
  background: #f3f4f6;
  color: #6b7280;
  font-size: 0.75rem;
  padding: 0.15rem 0.5rem;
  border-radius: 20px;
}

/* In-Progress row */
.my-courses-row {
  display: flex;
  gap: 1rem;
  overflow-x: auto;
  padding-bottom: 0.5rem;
}
.progress-card {
  background: white;
  border-radius: 12px;
  min-width: 220px;
  max-width: 240px;
  cursor: pointer;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
  transition: transform 0.15s, box-shadow 0.15s;
  overflow: hidden;
  flex-shrink: 0;
}
.progress-card:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.1); }
.progress-card-top {
  height: 90px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem;
}
.course-category-badge {
  background: rgba(255,255,255,0.25);
  color: white;
  font-size: 0.65rem;
  font-weight: 600;
  padding: 0.2rem 0.5rem;
  border-radius: 10px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.play-icon { width: 32px; height: 32px; color: rgba(255,255,255,0.9); }
.progress-card-body { padding: 0.85rem; }
.progress-card-body h4 { font-size: 0.9rem; font-weight: 600; color: #111827; margin: 0 0 0.5rem; }
.progress-row { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; }
.progress-track { flex: 1; height: 6px; background: #f3f4f6; border-radius: 3px; overflow: hidden; }
.progress-fill { height: 100%; background: linear-gradient(90deg, #6366f1, #8b5cf6); border-radius: 3px; transition: width 0.4s; }
.progress-pct { font-size: 0.75rem; font-weight: 600; color: #4f46e5; min-width: 32px; text-align: right; }
.progress-meta { display: flex; align-items: center; justify-content: space-between; }
.modules-done { font-size: 0.7rem; color: #9ca3af; }

/* Course Grid */
.course-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
  gap: 1.25rem;
}
.course-card {
  background: white;
  border-radius: 14px;
  overflow: hidden;
  cursor: pointer;
  box-shadow: 0 1px 4px rgba(0,0,0,0.06);
  transition: transform 0.15s, box-shadow 0.15s;
  display: flex;
  flex-direction: column;
}
.course-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(79,70,229,0.12); }
.card-thumb {
  height: 120px;
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  padding: 0.75rem;
}
.thumb-category {
  background: rgba(255,255,255,0.2);
  color: white;
  font-size: 0.65rem;
  font-weight: 700;
  padding: 0.2rem 0.6rem;
  border-radius: 10px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  backdrop-filter: blur(4px);
}
.thumb-icon { font-size: 2rem; }
.card-body { padding: 1rem; flex: 1; }
.card-title { font-size: 1rem; font-weight: 600; color: #111827; margin: 0 0 0.4rem; }
.card-desc { font-size: 0.8rem; color: #6b7280; line-height: 1.5; margin: 0 0 0.75rem; }
.card-meta { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.meta-item {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 0.75rem;
  color: #6b7280;
}
.meta-item.quiz-flag { color: #7c3aed; }
.card-footer {
  padding: 0.75rem 1rem;
  border-top: 1px solid #f3f4f6;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.mini-progress { flex: 1; height: 4px; background: #f3f4f6; border-radius: 2px; overflow: hidden; margin-right: 0.5rem; }
.mini-fill { height: 100%; background: linear-gradient(90deg, #6366f1, #8b5cf6); border-radius: 2px; }
.enrolled-state { display: flex; align-items: center; width: 100%; gap: 0.5rem; }
.enroll-btn {
  background: #4f46e5;
  color: white;
  border: none;
  padding: 0.4rem 1rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.15s;
}
.enroll-btn:hover:not(:disabled) { background: #4338ca; }
.enroll-btn:disabled { background: #a5b4fc; cursor: not-allowed; }
.enroll-btn.sm { padding: 0.3rem 0.75rem; font-size: 0.75rem; }
.assigned-label { font-size: 0.75rem; color: #9ca3af; }

/* Status chips */
.status-chip {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.15rem 0.5rem;
  border-radius: 10px;
}
.status-chip.enrolled { background: #eff6ff; color: #1d4ed8; }
.status-chip.in_progress { background: #fef9c3; color: #854d0e; }
.status-chip.completed { background: #f0fdf4; color: #166534; }
.status-chip.failed { background: #fef2f2; color: #991b1b; }
.enroll-badge { font-size: 0.7rem; font-weight: 600; padding: 0.2rem 0.6rem; border-radius: 10px; }
.enroll-badge.enrolled { background: #eff6ff; color: #1d4ed8; }
.enroll-badge.in_progress { background: #fef9c3; color: #854d0e; }
.enroll-badge.completed { background: #f0fdf4; color: #166534; }
.enroll-badge.failed { background: #fef2f2; color: #991b1b; }

/* List view */
.course-list { display: flex; flex-direction: column; gap: 0.75rem; }
.list-row {
  background: white;
  border-radius: 12px;
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  cursor: pointer;
  box-shadow: 0 1px 3px rgba(0,0,0,0.05);
  transition: box-shadow 0.15s;
}
.list-row:hover { box-shadow: 0 4px 12px rgba(79,70,229,0.1); }
.list-thumb { width: 56px; height: 56px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.list-info { flex: 1; }
.list-title { font-size: 0.95rem; font-weight: 600; color: #111827; margin: 0 0 0.2rem; }
.list-desc { font-size: 0.8rem; color: #6b7280; margin: 0 0 0.4rem; }
.list-tags { display: flex; gap: 0.4rem; flex-wrap: wrap; }
.tag { background: #f3f4f6; color: #6b7280; font-size: 0.7rem; padding: 0.15rem 0.5rem; border-radius: 6px; }
.tag.quiz { background: #f5f3ff; color: #7c3aed; }
.list-action { display: flex; flex-direction: column; align-items: flex-end; gap: 0.35rem; min-width: 100px; }
.list-progress { display: flex; align-items: center; gap: 0.4rem; margin-bottom: 4px; }

/* Modal */
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
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}
.modal-content.large { max-width: 700px; }
.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}
.modal-header h2 {
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0;
}
.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #6b7280;
}
.modal-body {
  padding: 1.5rem;
}
.course-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.form-group label {
  font-size: 0.8rem;
  font-weight: 500;
  color: #374151;
}
.form-group input,
.form-group select,
.form-group textarea {
  padding: 0.5rem 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 0.85rem;
}
.multi-select {
  min-height: 80px;
}
.form-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 1rem;
}
.form-check {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  margin-top: 1rem;
}

/* Empty state */
.empty-state { text-align: center; padding: 3rem; color: #9ca3af; }
.empty-icon { width: 3rem; height: 3rem; margin: 0 auto 0.75rem; opacity: 0.4; }

/* Loading */
.loading-overlay {
  position: fixed; top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(255,255,255,0.8);
  display: flex; align-items: center; justify-content: center; z-index: 50;
}
.spinner { width: 36px; height: 36px; border: 3px solid #e5e7eb; border-top-color: #4f46e5; border-radius: 50%; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* Empty table */
.empty-table {
  text-align: center;
  padding: 2rem;
  color: #9ca3af;
}

@media (max-width: 768px) {
  .catalog-view { padding: 1rem; }
  .catalog-header { flex-direction: column; align-items: flex-start; }
  .filters-bar { flex-direction: column; align-items: stretch; }
  .course-grid { grid-template-columns: 1fr; }
  .form-row { grid-template-columns: 1fr; }
  .dashboard-header { flex-direction: column; align-items: stretch; }
  .dashboard-filters { flex-direction: column; }
}
</style>