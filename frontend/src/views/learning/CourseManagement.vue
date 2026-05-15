<template>
  <div class="course-mgmt">
    <!-- Header -->
    <div class="mgmt-header">
      <div>
        <div class="eyebrow">Administration</div>
        <h1>Course Management</h1>
        <p>Create, edit, and manage courses, modules, and assessments.</p>
      </div>
      <button @click="openCreateModal" class="btn-primary">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        New Course
      </button>
    </div>

    <!-- Search & Filter Bar -->
    <div class="search-filter-bar">
      <div class="search-wrapper">
        <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Search courses by title, category, or description..." 
          class="search-input"
          @input="handleSearch"
        />
        <button v-if="searchQuery" @click="clearSearch" class="clear-search">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>
      </div>
      <div class="filter-group">
        <select v-model="categoryFilter" class="filter-select" @change="handleFilter">
          <option value="">All Categories</option>
          <option value="General">General</option>
          <option value="Onboarding">Onboarding</option>
          <option value="Software">Software</option>
          <option value="Compliance">Compliance</option>
          <option value="Leadership">Leadership</option>
          <option value="Finance">Finance</option>
        </select>
        <select v-model="statusFilter" class="filter-select" @change="handleFilter">
          <option value="">All Status</option>
          <option value="published">Published</option>
          <option value="draft">Draft</option>
          <option value="archived">Archived</option>
        </select>
      </div>
      <div class="results-info" v-if="filteredCourses.length !== courses.length">
        Showing {{ filteredCourses.length }} of {{ courses.length }} courses
      </div>
    </div>

    <div v-if="loading" class="loading-overlay"><div class="spinner"></div></div>

    <!-- Courses Scrollable Grid -->
    <div class="courses-grid-container">
      <div class="courses-grid">
        <div v-for="course in paginatedCourses" :key="course.id" class="course-mgmt-card">
          <!-- Card Header -->
          <div class="card-top" :style="{ background: categoryGradient(course.category) }">
            <span class="card-cat">{{ course.category || 'General' }}</span>
            <span class="card-icon-sm">{{ categoryIcon(course.category) }}</span>
          </div>
          <div class="card-content">
            <div class="card-title-row">
              <h3>{{ course.title }}</h3>
              <div class="card-actions">
                <button @click="editCourse(course)" class="icon-btn" title="Edit">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                  </svg>
                </button>
                <button @click="deleteCourse(course)" class="icon-btn danger" title="Delete">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="3 6 5 6 21 6"/>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                  </svg>
                </button>
              </div>
            </div>
            <p class="card-desc">{{ truncate(course.description, 80) }}</p>

            <!-- Status Badge -->
            <div class="status-row">
              <span :class="['status-badge', course.status || 'published']">
                {{ course.status || 'Published' }}
              </span>
            </div>

            <!-- Modules Section -->
            <div class="sub-section">
              <div class="sub-header">
                <div class="sub-title">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                  </svg>
                  Modules ({{ course.modules?.length || 0 }})
                </div>
                <button @click="openModuleModal(course)" class="btn-sm">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                  </svg>
                  Add
                </button>
              </div>
              <div v-if="course.modules?.length" class="modules-mini-list">
                <div v-for="(mod, i) in course.modules.slice(0, 3)" :key="mod.id" class="mod-row">
                  <span class="mod-num">{{ i + 1 }}</span>
                  <span class="mod-title">{{ mod.title }}</span>
                  <span :class="['mod-type', mod.type]">{{ mod.type }}</span>
                  <div class="mod-actions">
                    <button @click="editModuleItem(mod, course)" class="icon-xs">
                      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                      </svg>
                    </button>
                    <button @click="deleteModuleItem(mod.id, course.id)" class="icon-xs danger">
                      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"/>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                      </svg>
                    </button>
                  </div>
                </div>
                <div v-if="course.modules.length > 3" class="more-modules">
                  +{{ course.modules.length - 3 }} more modules
                </div>
              </div>
              <div v-else class="empty-mini">No modules yet</div>
            </div>

            <!-- Assessment Section -->
            <div class="sub-section">
              <div class="sub-header">
                <div class="sub-title">
                  <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 11l3 3L22 4"/>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                  </svg>
                  Assessment
                </div>
                <button @click="openAssessmentModal(course)" class="btn-sm" :class="{ 'btn-sm-edit': course.assessment }">
                  {{ course.assessment ? 'Edit' : 'Add' }}
                </button>
              </div>
              <div v-if="course.assessment" class="assess-mini">
                <div class="assess-mini-info">
                  <span class="assess-mini-title">{{ course.assessment.title }}</span>
                  <div class="assess-mini-meta">
                    <span>Pass: <strong>{{ course.assessment.pass_mark }}%</strong></span>
                    <span>Attempts: <strong>{{ course.assessment.max_attempts }}</strong></span>
                    <span v-if="course.assessment.time_limit_minutes">Time: <strong>{{ course.assessment.time_limit_minutes }}m</strong></span>
                  </div>
                </div>
                <button @click="openQuestionsModal(course.assessment)" class="btn-questions">
                  <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                  </svg>
                  Manage Questions ({{ course.assessment.questions_count || 0 }})
                </button>
              </div>
              <div v-else class="empty-mini">No assessment configured</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="filteredCourses.length > 0" class="pagination">
      <button @click="prevPage" :disabled="currentPage === 1" class="page-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="15 18 9 12 15 6"/>
        </svg>
        Previous
      </button>
      <div class="page-numbers">
        <button 
          v-for="page in displayedPages" 
          :key="page"
          @click="goToPage(page)"
          :class="['page-num', { active: currentPage === page }]"
        >
          {{ page }}
        </button>
      </div>
      <button @click="nextPage" :disabled="currentPage === totalPages" class="page-btn">
        Next
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <polyline points="9 18 15 12 9 6"/>
        </svg>
      </button>
    </div>

    <div v-if="filteredCourses.length === 0 && !loading" class="empty-state">
      <div style="font-size:3rem">🔍</div>
      <h3>No courses found</h3>
      <p v-if="searchQuery">No results for "{{ searchQuery }}"</p>
      <p v-else>Create your first course to get started.</p>
      <button v-if="searchQuery || categoryFilter || statusFilter" @click="resetFilters" class="btn-secondary">Clear Filters</button>
      <button v-else @click="openCreateModal" class="btn-primary">Create Course</button>
    </div>

    <!-- Modals (same as before) -->
    <!-- ── COURSE MODAL ── -->
    <transition name="fade">
      <div v-if="showCourseModal" class="modal-overlay" @click.self="closeCourseModal">
        <div class="modal-box">
          <div class="modal-hd">
            <h2>{{ editingCourse ? 'Edit Course' : 'New Course' }}</h2>
            <button class="modal-close" @click="closeCourseModal">×</button>
          </div>
          <div class="modal-bd">
            <div class="fg"><label>Title <span class="req">*</span></label><input v-model="courseForm.title" class="fi" placeholder="Course title"/></div>
            <div class="fg-row">
              <div class="fg">
                <label>Category</label>
                <select v-model="courseForm.category" class="fi">
                  <option>General</option><option>Onboarding</option><option>Software</option><option>Compliance</option><option>Leadership</option><option>Finance</option>
                </select>
              </div>
              <div class="fg">
                <label>Duration (min)</label>
                <input v-model.number="courseForm.estimated_minutes" type="number" class="fi" placeholder="60"/>
              </div>
            </div>
            <div class="fg">
              <label>Status</label>
              <select v-model="courseForm.status" class="fi">
                <option value="published">Published</option>
                <option value="draft">Draft</option>
                <option value="archived">Archived</option>
              </select>
            </div>
            <div class="fg"><label>Description</label><textarea v-model="courseForm.description" rows="3" class="fi" placeholder="What will employees learn?"></textarea></div>
            <label class="toggle-row">
              <input type="checkbox" v-model="courseForm.allow_self_enroll"/>
              <span class="toggle-track"><span class="toggle-knob"></span></span>
              <span>Allow self-enrollment</span>
            </label>
          </div>
          <div class="modal-ft">
            <button @click="closeCourseModal" class="btn-ghost">Cancel</button>
            <button @click="saveCourse" class="btn-primary" :disabled="saving">
              <span v-if="saving" class="btn-spin"></span>
              {{ saving ? 'Saving…' : (editingCourse ? 'Update' : 'Create') }}
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- ── MODULE MODAL ── -->
    <transition name="fade">
      <div v-if="showModuleModalFlag" class="modal-overlay" @click.self="showModuleModalFlag = false">
        <div class="modal-box">
          <div class="modal-hd">
            <h2>{{ editingModule ? 'Edit Module' : 'Add Module' }}</h2>
            <button class="modal-close" @click="showModuleModalFlag = false">×</button>
          </div>
          <div class="modal-bd">
            <div class="fg"><label>Title <span class="req">*</span></label><input v-model="moduleForm.title" class="fi" placeholder="Module title"/></div>
            <div class="fg"><label>Description</label><input v-model="moduleForm.description" class="fi" placeholder="Brief description"/></div>
            <div class="fg-row">
              <div class="fg">
                <label>Type <span class="req">*</span></label>
                <select v-model="moduleForm.type" class="fi">
                  <option value="text">Text</option>
                  <option value="video">Video</option>
                  <option value="pdf">PDF</option>
                  <option value="link">Link</option>
                </select>
              </div>
              <div class="fg">
                <label>Duration (min)</label>
                <input v-model.number="moduleForm.duration_minutes" type="number" class="fi" placeholder="15"/>
              </div>
            </div>
            <div class="fg">
              <label>Content <span class="req">*</span></label>
              <textarea v-model="moduleForm.content" rows="4" class="fi" placeholder="Text content, video URL, or PDF URL"></textarea>
            </div>
            <div class="fg"><label>Order</label><input v-model.number="moduleForm.order" type="number" class="fi" placeholder="0"/></div>
          </div>
          <div class="modal-ft">
            <button @click="showModuleModalFlag = false" class="btn-ghost">Cancel</button>
            <button @click="saveModule" class="btn-primary" :disabled="saving">
              <span v-if="saving" class="btn-spin"></span>
              {{ saving ? 'Saving…' : (editingModule ? 'Update' : 'Add Module') }}
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- ── ASSESSMENT MODAL ── -->
    <transition name="fade">
      <div v-if="showAssessmentModalFlag" class="modal-overlay" @click.self="showAssessmentModalFlag = false">
        <div class="modal-box">
          <div class="modal-hd">
            <h2>{{ editingAssessment ? 'Edit Assessment' : 'Add Assessment' }}</h2>
            <button class="modal-close" @click="showAssessmentModalFlag = false">×</button>
          </div>
          <div class="modal-bd">
            <div class="fg"><label>Title <span class="req">*</span></label><input v-model="assessmentForm.title" class="fi" placeholder="Assessment title"/></div>
            <div class="fg-row">
              <div class="fg">
                <label>Pass Score (%) <span class="req">*</span></label>
                <input v-model.number="assessmentForm.pass_mark" type="number" class="fi" min="0" max="100" placeholder="70"/>
              </div>
              <div class="fg">
                <label>Max Attempts</label>
                <input v-model.number="assessmentForm.max_attempts" type="number" class="fi" min="1" placeholder="3"/>
              </div>
            </div>
            <div class="fg">
              <label>Time Limit (minutes, optional)</label>
              <input v-model.number="assessmentForm.time_limit_minutes" type="number" class="fi" min="1" placeholder="e.g. 30"/>
            </div>
            <div class="info-box">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              After saving, add questions by clicking "Manage Questions" on the assessment card.
            </div>
          </div>
          <div class="modal-ft">
            <button @click="showAssessmentModalFlag = false" class="btn-ghost">Cancel</button>
            <button @click="saveAssessment" class="btn-primary" :disabled="saving">
              <span v-if="saving" class="btn-spin"></span>
              {{ saving ? 'Saving…' : (editingAssessment ? 'Update' : 'Create Assessment') }}
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- ── QUESTIONS MODAL ── -->
    <transition name="fade">
      <div v-if="showQuestionsModalFlag" class="modal-overlay" @click.self="showQuestionsModalFlag = false">
        <div class="modal-box wide">
          <div class="modal-hd">
            <div>
              <h2>Manage Questions</h2>
              <p class="modal-sub">{{ currentAssessment?.title }}</p>
            </div>
            <button class="modal-close" @click="showQuestionsModalFlag = false">×</button>
          </div>
          <div class="modal-bd questions-bd">
            <div v-if="questionsLoading" class="q-loading"><div class="spinner"></div></div>
            <div v-else>
              <div v-for="(q, qi) in questions" :key="qi" class="q-editor">
                <div class="q-editor-hd">
                  <span class="q-num-badge">Q{{ qi + 1 }}</span>
                  <button @click="removeQuestion(qi)" class="icon-xs danger">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                  </button>
                </div>
                <div class="fg">
                  <label>Question text <span class="req">*</span></label>
                  <textarea v-model="q.question" rows="2" class="fi" placeholder="Enter question…"></textarea>
                </div>
                <div class="fg-row">
                  <div class="fg">
                    <label>Points</label>
                    <input v-model.number="q.points" type="number" class="fi" min="1" placeholder="1"/>
                  </div>
                  <div class="fg">
                    <label>Order</label>
                    <input v-model.number="q.order" type="number" class="fi" placeholder="0"/>
                  </div>
                </div>
                <div class="options-editor">
                  <label class="opts-label">Options <span class="hint">(select the correct answer)</span></label>
                  <div v-for="(opt, oi) in q.options" :key="oi" class="opt-row">
                    <label class="opt-radio" :class="{ correct: opt.is_correct }">
                      <input type="radio" :name="`correct_${qi}`" :checked="opt.is_correct" @change="setCorrect(q, oi)"/>
                      <span class="opt-radio-indicator">
                        <svg v-if="opt.is_correct" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                      </span>
                    </label>
                    <input v-model="opt.option_text" class="fi opt-input" :placeholder="`Option ${oi + 1}`"/>
                    <button @click="removeOpt(q, oi)" class="icon-xs danger" :disabled="q.options.length <= 2">
                      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                  </div>
                  <button @click="addOpt(q)" class="btn-add-opt">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Add Option
                  </button>
                </div>
              </div>

              <button @click="addQuestion" class="btn-add-q">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Question
              </button>
            </div>
          </div>
          <div class="modal-ft">
            <button @click="showQuestionsModalFlag = false" class="btn-ghost">Cancel</button>
            <button @click="saveQuestions" class="btn-primary" :disabled="saving">
              <span v-if="saving" class="btn-spin"></span>
              {{ saving ? 'Saving…' : 'Save All Questions' }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'CourseManagement',
  data() {
    return {
      courses: [], 
      loading: false, 
      saving: false, 
      questionsLoading: false,
      showCourseModal: false, 
      showModuleModalFlag: false,
      showAssessmentModalFlag: false, 
      showQuestionsModalFlag: false,
      editingCourse: null, 
      editingModule: null, 
      editingAssessment: null,
      currentCourse: null, 
      currentAssessment: null, 
      questions: [],
      
      // Search and filter
      searchQuery: '',
      categoryFilter: '',
      statusFilter: '',
      
      // Pagination
      currentPage: 1,
      itemsPerPage: 12,
      
      // Form data
      courseForm: { 
        title: '', 
        description: '', 
        category: 'General', 
        estimated_minutes: 60, 
        allow_self_enroll: true,
        status: 'published'
      },
      moduleForm: { 
        title: '', 
        description: '', 
        type: 'text', 
        content: '', 
        duration_minutes: null, 
        order: 0 
      },
      assessmentForm: { 
        title: '', 
        pass_mark: 70, 
        max_attempts: 3, 
        time_limit_minutes: null 
      }
    }
  },
  computed: {
    filteredCourses() {
      let result = [...this.courses]
      
      // Search filter
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        result = result.filter(course => 
          course.title?.toLowerCase().includes(query) ||
          course.description?.toLowerCase().includes(query) ||
          course.category?.toLowerCase().includes(query)
        )
      }
      
      // Category filter
      if (this.categoryFilter) {
        result = result.filter(course => course.category === this.categoryFilter)
      }
      
      // Status filter
      if (this.statusFilter) {
        result = result.filter(course => course.status === this.statusFilter)
      }
      
      return result
    },
    
    totalPages() {
      return Math.ceil(this.filteredCourses.length / this.itemsPerPage)
    },
    
    paginatedCourses() {
      const start = (this.currentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.filteredCourses.slice(start, end)
    },
    
    displayedPages() {
      const total = this.totalPages
      const current = this.currentPage
      const pages = []
      
      if (total <= 7) {
        for (let i = 1; i <= total; i++) pages.push(i)
      } else {
        if (current <= 3) {
          for (let i = 1; i <= 5; i++) pages.push(i)
          pages.push('...')
          pages.push(total)
        } else if (current >= total - 2) {
          pages.push(1)
          pages.push('...')
          for (let i = total - 4; i <= total; i++) pages.push(i)
        } else {
          pages.push(1)
          pages.push('...')
          for (let i = current - 1; i <= current + 1; i++) pages.push(i)
          pages.push('...')
          pages.push(total)
        }
      }
      return pages
    }
  },
  mounted() { 
    this.fetchCourses() 
  },
  methods: {
    async fetchCourses() {
      this.loading = true
      try {
        const r = await axios.get('/api/learning/courses')
        this.courses = r.data.data?.data || r.data.data || []
      } catch(e) { 
        console.error(e) 
      } finally { 
        this.loading = false 
      }
    },
    
    handleSearch() {
      this.currentPage = 1
    },
    
    handleFilter() {
      this.currentPage = 1
    },
    
    clearSearch() {
      this.searchQuery = ''
      this.handleSearch()
    },
    
    resetFilters() {
      this.searchQuery = ''
      this.categoryFilter = ''
      this.statusFilter = ''
      this.currentPage = 1
    },
    
    prevPage() {
      if (this.currentPage > 1) {
        this.currentPage--
      }
    },
    
    nextPage() {
      if (this.currentPage < this.totalPages) {
        this.currentPage++
      }
    },
    
    goToPage(page) {
      if (page !== '...') {
        this.currentPage = page
      }
    },
    
    openCreateModal() { 
      this.editingCourse = null
      this.courseForm = { 
        title: '', 
        description: '', 
        category: 'General', 
        estimated_minutes: 60, 
        allow_self_enroll: true,
        status: 'published'
      }
      this.showCourseModal = true 
    },
    
    closeCourseModal() { 
      this.showCourseModal = false
      this.editingCourse = null 
    },
    
    editCourse(c) { 
      this.editingCourse = c
      this.courseForm = { 
        title: c.title, 
        description: c.description, 
        category: c.category, 
        estimated_minutes: c.estimated_minutes, 
        allow_self_enroll: c.allow_self_enroll,
        status: c.status || 'published'
      }
      this.showCourseModal = true 
    },
    
    async saveCourse() {
      if (!this.courseForm.title) return
      this.saving = true
      try {
        if (this.editingCourse) {
          await axios.put(`/api/learning/courses/${this.editingCourse.id}`, this.courseForm)
        } else {
          await axios.post('/api/learning/courses', this.courseForm)
        }
        await this.fetchCourses()
        this.closeCourseModal()
      } catch(e) { 
        alert('Failed to save course') 
      } finally { 
        this.saving = false 
      }
    },
    
    async deleteCourse(c) {
      if (!confirm(`Delete "${c.title}"?`)) return
      try { 
        await axios.delete(`/api/learning/courses/${c.id}`)
        await this.fetchCourses()
      } catch(e) { 
        alert('Failed to delete') 
      }
    },
    
    openModuleModal(course) {
      this.currentCourse = course
      this.editingModule = null
      this.moduleForm = { 
        title: '', 
        description: '', 
        type: 'text', 
        content: '', 
        duration_minutes: null, 
        order: course.modules?.length || 0 
      }
      this.showModuleModalFlag = true
    },
    
    editModuleItem(mod, course) {
      this.currentCourse = course
      this.editingModule = mod
      this.moduleForm = { 
        title: mod.title, 
        description: mod.description, 
        type: mod.type, 
        content: mod.content, 
        duration_minutes: mod.duration_minutes, 
        order: mod.order 
      }
      this.showModuleModalFlag = true
    },
    
    async saveModule() {
      if (!this.moduleForm.title || !this.moduleForm.content) return
      this.saving = true
      try {
        if (this.editingModule) {
          await axios.put(`/api/learning/courses/${this.currentCourse.id}/modules/${this.editingModule.id}`, this.moduleForm)
        } else {
          await axios.post(`/api/learning/courses/${this.currentCourse.id}/modules`, this.moduleForm)
        }
        await this.fetchCourses()
        this.showModuleModalFlag = false
      } catch(e) { 
        alert('Failed to save module') 
      } finally { 
        this.saving = false 
      }
    },
    
    async deleteModuleItem(modId, courseId) {
      if (!confirm('Delete this module?')) return
      try { 
        await axios.delete(`/api/learning/courses/${courseId}/modules/${modId}`)
        await this.fetchCourses()
      } catch(e) { 
        alert('Failed to delete') 
      }
    },
    
    openAssessmentModal(course) {
      this.currentCourse = course
      this.editingAssessment = course.assessment
      if (course.assessment) {
        this.assessmentForm = { 
          title: course.assessment.title, 
          pass_mark: course.assessment.pass_mark, 
          max_attempts: course.assessment.max_attempts, 
          time_limit_minutes: course.assessment.time_limit_minutes 
        }
      } else {
        this.assessmentForm = { 
          title: `${course.title} Assessment`, 
          pass_mark: 70, 
          max_attempts: 3, 
          time_limit_minutes: null 
        }
      }
      this.showAssessmentModalFlag = true
    },
    
    async saveAssessment() {
      if (!this.assessmentForm.title || !this.assessmentForm.pass_mark) return
      this.saving = true
      try {
        await axios.post(`/api/learning/courses/${this.currentCourse.id}/assessment`, this.assessmentForm)
        await this.fetchCourses()
        this.showAssessmentModalFlag = false
      } catch(e) { 
        alert('Failed to save assessment') 
      } finally { 
        this.saving = false 
      }
    },
    
    async openQuestionsModal(assessment) {
      this.currentAssessment = assessment
      this.showQuestionsModalFlag = true
      this.questionsLoading = true
      try {
        const r = await axios.get(`/api/learning/assessments/${assessment.id}/questions`)
        this.questions = (r.data.data || []).map(q => ({
          id: q.id, 
          question: q.question, 
          points: q.points, 
          order: q.order,
          options: q.options?.map(o => ({ 
            id: o.id, 
            option_text: o.option_text, 
            is_correct: o.is_correct 
          })) || []
        }))
        if (this.questions.length === 0) this.addQuestion()
      } catch(e) { 
        this.questions = []
        this.addQuestion()
      } finally { 
        this.questionsLoading = false 
      }
    },
    
    addQuestion() {
      this.questions.push({ 
        id: null, 
        question: '', 
        points: 1, 
        order: this.questions.length,
        options: [
          { id: null, option_text: '', is_correct: true }, 
          { id: null, option_text: '', is_correct: false }, 
          { id: null, option_text: '', is_correct: false }, 
          { id: null, option_text: '', is_correct: false }
        ]
      })
    },
    
    removeQuestion(qi) { 
      if (this.questions.length > 1) this.questions.splice(qi, 1) 
    },
    
    addOpt(q) { 
      q.options.push({ id: null, option_text: '', is_correct: false }) 
    },
    
    removeOpt(q, oi) { 
      if (q.options.length > 2) q.options.splice(oi, 1) 
    },
    
    setCorrect(q, oi) { 
      q.options.forEach((o, i) => { o.is_correct = (i === oi) }) 
    },
    
    async saveQuestions() {
      this.saving = true
      try {
        for (const q of this.questions) {
          if (!q.question.trim()) continue
          const hasCorrect = q.options.some(o => o.is_correct)
          if (!hasCorrect) { q.options[0].is_correct = true }
          const payload = { 
            assessment_id: this.currentAssessment.id, 
            question: q.question, 
            points: q.points || 1, 
            order: q.order || 0, 
            options: q.options.filter(o => o.option_text.trim()) 
          }
          if (q.id) {
            await axios.put(`/api/learning/questions/${q.id}`, payload)
          } else {
            await axios.post('/api/learning/questions', payload)
          }
        }
        await this.fetchCourses()
        this.showQuestionsModalFlag = false
        alert('Questions saved successfully!')
      } catch(e) { 
        alert('Failed to save questions') 
      } finally { 
        this.saving = false 
      }
    },
    
    truncate(s, n) { 
      if (!s) return ''
      return s.length > n ? s.slice(0, n) + '…' : s 
    },
    
    categoryGradient(cat) { 
      return { 
        'Onboarding': 'linear-gradient(135deg,#6366f1,#a78bfa)', 
        'Software': 'linear-gradient(135deg,#0ea5e9,#6366f1)', 
        'Compliance': 'linear-gradient(135deg,#f59e0b,#ef4444)', 
        'Leadership': 'linear-gradient(135deg,#10b981,#0ea5e9)',
        'Finance': 'linear-gradient(135deg,#f59e0b,#10b981)'
      }[cat] || 'linear-gradient(135deg,#6366f1,#8b5cf6)' 
    },
    
    categoryIcon(cat) { 
      return { 
        'Onboarding': '🚀', 
        'Software': '💻', 
        'Compliance': '📋', 
        'Leadership': '🎯',
        'Finance': '💰'
      }[cat] || '📚' 
    }
  }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

.course-mgmt { 
  min-height: 100vh; 
  background: #f4f3ff; 
  padding: 2.5rem 3rem; 
  font-family: 'Outfit', sans-serif; 
  color: #1a1040; 
}

/* Header */
.mgmt-header { 
  display: flex; 
  justify-content: space-between; 
  align-items: flex-start; 
  margin-bottom: 2rem; 
  flex-wrap: wrap; 
  gap: 1rem; 
}
.eyebrow { 
  font-size: 0.68rem; 
  font-weight: 700; 
  letter-spacing: 0.12em; 
  text-transform: uppercase; 
  color: #7c3aed; 
  margin-bottom: 0.35rem; 
}
.mgmt-header h1 { 
  font-size: 2rem; 
  font-weight: 800; 
  color: #1a1040; 
  letter-spacing: -0.03em; 
  margin-bottom: 0.3rem; 
}
.mgmt-header p { 
  font-size: 0.875rem; 
  color: #6b7280; 
}

/* Search & Filter Bar */
.search-filter-bar {
  background: white;
  border-radius: 16px;
  padding: 1rem 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.04);
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  align-items: center;
}

.search-wrapper {
  flex: 2;
  min-width: 250px;
  position: relative;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #9ca3af;
}

.search-input {
  width: 100%;
  padding: 0.7rem 1rem 0.7rem 2.5rem;
  border: 1.5px solid #e5e7eb;
  border-radius: 12px;
  font-family: inherit;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.search-input:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

.clear-search {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: #9ca3af;
  display: flex;
  align-items: center;
  padding: 2px;
}

.clear-search:hover {
  color: #ef4444;
}

.filter-group {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.filter-select {
  padding: 0.65rem 1rem;
  border: 1.5px solid #e5e7eb;
  border-radius: 10px;
  font-family: inherit;
  font-size: 0.85rem;
  background: white;
  cursor: pointer;
  transition: all 0.2s;
}

.filter-select:focus {
  outline: none;
  border-color: #6366f1;
}

.results-info {
  font-size: 0.8rem;
  color: #6b7280;
  padding: 0.5rem 0;
  margin-left: auto;
}

/* Scrollable Grid Container */
.courses-grid-container {
  max-height: calc(100vh - 280px);
  overflow-y: auto;
  padding-right: 0.5rem;
}

/* Custom scrollbar */
.courses-grid-container::-webkit-scrollbar {
  width: 8px;
}

.courses-grid-container::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 10px;
}

.courses-grid-container::-webkit-scrollbar-thumb {
  background: #c7d2fe;
  border-radius: 10px;
}

.courses-grid-container::-webkit-scrollbar-thumb:hover {
  background: #6366f1;
}

.courses-grid { 
  display: grid; 
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); 
  gap: 1.5rem; 
}

/* Card Styles */
.course-mgmt-card { 
  background: white; 
  border-radius: 20px; 
  overflow: hidden; 
  border: 1.5px solid rgba(0,0,0,0.06); 
  box-shadow: 0 2px 12px rgba(99,102,241,0.08); 
  transition: transform 0.2s, box-shadow 0.2s;
}

.course-mgmt-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(99,102,241,0.12);
}

.card-top { 
  height: 80px; 
  display: flex; 
  align-items: center; 
  justify-content: space-between; 
  padding: 1rem 1.25rem; 
}

.card-cat { 
  background: rgba(255,255,255,0.25); 
  backdrop-filter: blur(4px); 
  color: white; 
  font-size: 0.7rem; 
  font-weight: 700; 
  padding: 0.25rem 0.65rem; 
  border-radius: 20px; 
  border: 1px solid rgba(255,255,255,0.3); 
  text-transform: uppercase; 
  letter-spacing: 0.05em; 
}

.card-icon-sm { 
  font-size: 2rem; 
  filter: drop-shadow(0 2px 4px rgba(0,0,0,0.2)); 
}

.card-content { 
  padding: 1.25rem; 
}

.card-title-row { 
  display: flex; 
  justify-content: space-between; 
  align-items: flex-start; 
  margin-bottom: 0.4rem; 
}

.card-title-row h3 { 
  font-size: 1.05rem; 
  font-weight: 800; 
  color: #1a1040; 
  flex: 1; 
  margin-right: 0.5rem;
  line-height: 1.4;
}

.card-actions { 
  display: flex; 
  gap: 0.35rem; 
  flex-shrink: 0; 
}

.card-desc { 
  font-size: 0.8rem; 
  color: #6b7280; 
  margin-bottom: 1rem; 
  line-height: 1.5; 
}

.status-row {
  margin-bottom: 1rem;
}

.status-badge {
  font-size: 0.65rem;
  font-weight: 700;
  padding: 0.2rem 0.6rem;
  border-radius: 20px;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.status-badge.published {
  background: #f0fdf4;
  color: #166534;
}

.status-badge.draft {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.archived {
  background: #fee2e2;
  color: #991b1b;
}

/* Button Styles */
.btn-primary {
  display: inline-flex; 
  align-items: center; 
  gap: 0.45rem;
  padding: 0.65rem 1.35rem; 
  background: #6366f1; 
  color: white;
  border: none; 
  border-radius: 12px; 
  font-family: inherit;
  font-size: 0.875rem; 
  font-weight: 700; 
  cursor: pointer; 
  transition: all 0.2s;
  box-shadow: 0 4px 14px rgba(99,102,241,0.35);
}

.btn-primary:hover:not(:disabled) { 
  background: #4f46e5; 
  transform: translateY(-1px); 
}

.btn-primary:disabled { 
  opacity: 0.65; 
  cursor: not-allowed; 
}

.btn-secondary {
  display: inline-flex; 
  align-items: center; 
  gap: 0.45rem;
  padding: 0.65rem 1.35rem; 
  background: white; 
  color: #6366f1;
  border: 1.5px solid #6366f1; 
  border-radius: 12px; 
  font-family: inherit;
  font-size: 0.875rem; 
  font-weight: 700; 
  cursor: pointer; 
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #6366f1;
  color: white;
}

.btn-ghost {
  padding: 0.65rem 1.2rem; 
  background: transparent; 
  color: #6b7280;
  border: 1.5px solid #e5e7eb; 
  border-radius: 10px; 
  font-family: inherit;
  font-size: 0.85rem; 
  font-weight: 500; 
  cursor: pointer; 
  transition: all 0.15s;
}

.btn-ghost:hover { 
  background: #f3f4f6; 
}

.icon-btn, .icon-xs {
  width: 28px; 
  height: 28px; 
  border-radius: 8px; 
  border: 1.5px solid #e5e7eb;
  background: #f9fafb; 
  color: #6b7280; 
  cursor: pointer; 
  display: flex;
  align-items: center; 
  justify-content: center; 
  transition: all 0.15s;
}

.icon-btn { 
  width: 30px; 
  height: 30px; 
}

.icon-btn:hover, .icon-xs:hover { 
  background: #e5e7eb; 
}

.icon-btn.danger:hover, .icon-xs.danger:hover { 
  background: #fee2e2; 
  color: #dc2626; 
  border-color: #fca5a5; 
}

/* Pagination */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  margin-top: 2rem;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

.page-btn {
  padding: 0.5rem 1rem;
  background: white;
  border: 1.5px solid #e5e7eb;
  border-radius: 10px;
  font-family: inherit;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  transition: all 0.2s;
}

.page-btn:hover:not(:disabled) {
  border-color: #6366f1;
  color: #6366f1;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-numbers {
  display: flex;
  gap: 0.35rem;
}

.page-num {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  border: 1.5px solid #e5e7eb;
  background: white;
  font-family: inherit;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.page-num:hover {
  border-color: #6366f1;
  color: #6366f1;
}

.page-num.active {
  background: #6366f1;
  border-color: #6366f1;
  color: white;
}

/* Sub-sections */
.sub-section { 
  border-top: 1px solid #f3f4f6; 
  padding-top: 1rem; 
  margin-top: 1rem; 
}

.sub-header { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  margin-bottom: 0.75rem; 
}

.sub-title { 
  display: flex; 
  align-items: center; 
  gap: 0.4rem; 
  font-size: 0.82rem; 
  font-weight: 700; 
  color: #374151; 
}

.btn-sm {
  padding: 0.28rem 0.65rem; 
  background: #f3f4f6; 
  border: 1.5px solid #e5e7eb;
  border-radius: 8px; 
  font-family: inherit; 
  font-size: 0.72rem; 
  font-weight: 600;
  color: #374151; 
  cursor: pointer; 
  transition: all 0.15s;
  display: inline-flex; 
  align-items: center; 
  gap: 0.3rem;
}

.btn-sm:hover { 
  border-color: #6366f1; 
  color: #6366f1; 
}

.btn-sm.btn-sm-edit { 
  background: #ede9fe; 
  border-color: #ddd6fe; 
  color: #7c3aed; 
}

.btn-sm.btn-sm-edit:hover { 
  background: #ddd6fe; 
}

.modules-mini-list { 
  display: flex; 
  flex-direction: column; 
  gap: 0.4rem; 
}

.mod-row {
  display: flex; 
  align-items: center; 
  gap: 0.5rem;
  padding: 0.5rem 0.75rem; 
  background: #f9fafb; 
  border-radius: 8px;
}

.mod-num { 
  width: 22px; 
  height: 22px; 
  border-radius: 50%; 
  background: #6366f1; 
  color: white; 
  font-size: 0.7rem; 
  font-weight: 700; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  flex-shrink: 0; 
}

.mod-title { 
  flex: 1; 
  font-size: 0.82rem; 
  font-weight: 500; 
  color: #1a1040; 
  min-width: 0; 
  white-space: nowrap; 
  overflow: hidden; 
  text-overflow: ellipsis; 
}

.mod-type { 
  font-size: 0.62rem; 
  font-weight: 700; 
  padding: 0.15rem 0.45rem; 
  border-radius: 5px; 
  text-transform: uppercase; 
  flex-shrink: 0; 
}

.mod-type.video { background: #fef3c7; color: #d97706; }
.mod-type.pdf { background: #fee2e2; color: #dc2626; }
.mod-type.text { background: #e0e7ff; color: #4f46e5; }
.mod-type.link { background: #d1fae5; color: #059669; }

.more-modules {
  font-size: 0.7rem;
  color: #9ca3af;
  padding: 0.25rem 0.75rem;
  font-style: italic;
}

.empty-mini { 
  font-size: 0.78rem; 
  color: #9ca3af; 
  font-style: italic; 
  padding: 0.5rem 0; 
}

.assess-mini { 
  background: linear-gradient(135deg, #f5f3ff, #ede9fe); 
  border: 1px solid #ddd6fe; 
  border-radius: 12px; 
  padding: 0.85rem; 
}

.assess-mini-title { 
  font-size: 0.85rem; 
  font-weight: 700; 
  color: #4c1d95; 
  display: block; 
  margin-bottom: 0.35rem; 
}

.assess-mini-meta { 
  display: flex; 
  gap: 1rem; 
  font-size: 0.75rem; 
  color: #6b7280; 
  margin-bottom: 0.75rem; 
  flex-wrap: wrap; 
}

.btn-questions {
  display: inline-flex; 
  align-items: center; 
  gap: 0.4rem;
  padding: 0.4rem 0.85rem; 
  background: #6366f1; 
  color: white;
  border: none; 
  border-radius: 8px; 
  font-family: inherit; 
  font-size: 0.75rem; 
  font-weight: 700; 
  cursor: pointer; 
  transition: all 0.2s;
  box-shadow: 0 2px 8px rgba(99,102,241,0.3);
}

.btn-questions:hover { 
  background: #4f46e5; 
}

.empty-state { 
  text-align: center; 
  padding: 5rem 2rem; 
}

.empty-state h3 { 
  font-size: 1.25rem; 
  font-weight: 700; 
  margin: 1rem 0 0.4rem; 
}

.empty-state p { 
  color: #6b7280; 
  margin-bottom: 1.5rem; 
}

/* Modal Styles */
.modal-overlay { 
  position: fixed; 
  inset: 0; 
  background: rgba(15,14,23,0.55); 
  backdrop-filter: blur(6px); 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  z-index: 9999; 
}

.modal-box {
  background: white; 
  border-radius: 22px; 
  width: 90%; 
  max-width: 500px;
  max-height: 90vh; 
  overflow-y: auto; 
  box-shadow: 0 24px 64px rgba(0,0,0,0.2); 
  animation: slideUp 0.25s ease;
}

.modal-box.wide { 
  max-width: 680px; 
}

@keyframes slideUp { 
  from { opacity:0; transform:translateY(20px); } 
  to { opacity:1; transform:translateY(0); } 
}

.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.modal-hd { 
  display: flex; 
  justify-content: space-between; 
  align-items: flex-start; 
  padding: 1.5rem 1.75rem 0; 
}

.modal-hd h2 { 
  font-size: 1.2rem; 
  font-weight: 800; 
  color: #1a1040; 
}

.modal-sub { 
  font-size: 0.78rem; 
  color: #9ca3af; 
  margin-top: 0.2rem; 
}

.modal-close { 
  font-size: 1.5rem; 
  line-height: 1; 
  background: #f3f4f6; 
  border: none; 
  border-radius: 50%; 
  width: 32px; 
  height: 32px; 
  cursor: pointer; 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  color: #6b7280; 
  transition: all 0.15s; 
}

.modal-close:hover { 
  background: #fee2e2; 
  color: #dc2626; 
}

.modal-bd { 
  padding: 1.5rem 1.75rem; 
}

.modal-ft { 
  padding: 0 1.75rem 1.75rem; 
  display: flex; 
  justify-content: flex-end; 
  gap: 0.75rem; 
  border-top: 1px solid #f3f4f6; 
  padding-top: 1rem; 
}

.fg { 
  margin-bottom: 1rem; 
}

.fg label { 
  display: block; 
  font-size: 0.78rem; 
  font-weight: 600; 
  color: #374151; 
  margin-bottom: 0.35rem; 
}

.fg-row { 
  display: grid; 
  grid-template-columns: 1fr 1fr; 
  gap: 0.75rem; 
}

.req { 
  color: #ef4444; 
}

.hint { 
  font-weight: 400; 
  color: #9ca3af; 
  font-style: italic; 
}

.fi {
  width: 100%; 
  padding: 0.6rem 0.85rem; 
  border: 1.5px solid #e5e7eb; 
  border-radius: 10px;
  font-family: inherit; 
  font-size: 0.875rem; 
  outline: none; 
  transition: all 0.2s; 
  background: #fafafa; 
  color: #1a1040;
}

.fi:focus { 
  border-color: #6366f1; 
  background: white; 
  box-shadow: 0 0 0 3px rgba(99,102,241,0.1); 
}

.info-box {
  display: flex; 
  align-items: flex-start; 
  gap: 0.5rem;
  background: #fffbeb; 
  border: 1px solid #fde68a; 
  border-radius: 10px;
  padding: 0.75rem; 
  font-size: 0.8rem; 
  color: #92400e;
}

.toggle-row { 
  display: flex; 
  align-items: center; 
  gap: 0.75rem; 
  cursor: pointer; 
  font-size: 0.875rem; 
  color: #374151; 
  font-weight: 500; 
  margin-bottom: 0.5rem; 
}

.toggle-row input { 
  display: none; 
}

.toggle-track { 
  width: 42px; 
  height: 24px; 
  background: #e5e7eb; 
  border-radius: 12px; 
  position: relative; 
  transition: background 0.2s; 
  flex-shrink: 0; 
}

.toggle-knob { 
  position: absolute; 
  top: 3px; 
  left: 3px; 
  width: 18px; 
  height: 18px; 
  background: white; 
  border-radius: 50%; 
  transition: transform 0.2s; 
  box-shadow: 0 1px 3px rgba(0,0,0,0.2); 
}

.toggle-row input:checked + .toggle-track { 
  background: #6366f1; 
}

.toggle-row input:checked + .toggle-track .toggle-knob { 
  transform: translateX(18px); 
}

/* Questions editor */
.questions-bd { 
  max-height: 55vh; 
  overflow-y: auto; 
}

.q-loading { 
  display: flex; 
  justify-content: center; 
  padding: 2rem; 
}

.q-editor { 
  background: #f9fafb; 
  border-radius: 14px; 
  padding: 1.25rem; 
  margin-bottom: 1rem; 
  border: 1.5px solid #e5e7eb; 
}

.q-editor-hd { 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  margin-bottom: 1rem; 
}

.q-num-badge { 
  font-size: 0.7rem; 
  font-weight: 800; 
  color: #6366f1; 
  background: #e0e7ff; 
  padding: 0.22rem 0.65rem; 
  border-radius: 20px; 
  text-transform: uppercase; 
  letter-spacing: 0.05em; 
}

.options-editor { 
  background: white; 
  border-radius: 10px; 
  padding: 0.85rem; 
  border: 1px solid #e5e7eb; 
}

.opts-label { 
  display: block; 
  font-size: 0.75rem; 
  font-weight: 600; 
  color: #374151; 
  margin-bottom: 0.65rem; 
}

.opt-row { 
  display: flex; 
  align-items: center; 
  gap: 0.5rem; 
  margin-bottom: 0.5rem; 
}

.opt-radio { 
  flex-shrink: 0; 
}

.opt-radio input { 
  display: none; 
}

.opt-radio-indicator {
  width: 22px; 
  height: 22px; 
  border-radius: 50%; 
  border: 2px solid #d1d5db; 
  cursor: pointer;
  display: flex; 
  align-items: center; 
  justify-content: center; 
  transition: all 0.15s;
}

.opt-radio.correct .opt-radio-indicator { 
  border-color: #10b981; 
  background: #10b981; 
  color: white; 
}

.opt-input { 
  flex: 1; 
  margin: 0; 
}

.btn-add-opt {
  display: inline-flex; 
  align-items: center; 
  gap: 0.35rem;
  padding: 0.35rem 0.75rem; 
  background: transparent; 
  border: 1.5px dashed #d1d5db;
  border-radius: 8px; 
  font-family: inherit; 
  font-size: 0.75rem; 
  color: #9ca3af; 
  cursor: pointer; 
  transition: all 0.15s; 
  margin-top: 0.25rem;
}

.btn-add-opt:hover { 
  border-color: #6366f1; 
  color: #6366f1; 
}

.btn-add-q {
  display: inline-flex; 
  align-items: center; 
  gap: 0.45rem;
  width: 100%; 
  padding: 0.75rem; 
  background: transparent; 
  border: 2px dashed #d1d5db;
  border-radius: 14px; 
  font-family: inherit; 
  font-size: 0.875rem; 
  font-weight: 600;
  color: #9ca3af; 
  cursor: pointer; 
  transition: all 0.15s; 
  justify-content: center; 
  margin-top: 0.5rem;
}

.btn-add-q:hover { 
  border-color: #6366f1; 
  color: #6366f1; 
  background: #f5f3ff; 
}

.btn-spin { 
  width: 14px; 
  height: 14px; 
  border: 2px solid rgba(255,255,255,0.4); 
  border-top-color: white; 
  border-radius: 50%; 
  animation: spin 0.7s linear infinite; 
  display: inline-block; 
}

.spinner { 
  width: 36px; 
  height: 36px; 
  border: 3px solid #e5e7eb; 
  border-top-color: #6366f1; 
  border-radius: 50%; 
  animation: spin 0.8s linear infinite; 
  margin: auto; 
}

@keyframes spin { 
  to { transform: rotate(360deg); } 
}

.loading-overlay { 
  position: fixed; 
  inset: 0; 
  background: rgba(255,255,255,0.88); 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  z-index: 50; 
}

/* Responsive */
@media (max-width: 768px) {
  .course-mgmt { 
    padding: 1.25rem; 
  }
  
  .courses-grid { 
    grid-template-columns: 1fr; 
  }
  
  .fg-row { 
    grid-template-columns: 1fr; 
  }
  
  .mgmt-header { 
    flex-direction: column; 
    align-items: flex-start; 
  }
  
  .search-filter-bar {
    flex-direction: column;
    align-items: stretch;
  }
  
  .filter-group {
    justify-content: stretch;
  }
  
  .results-info {
    text-align: center;
  }
  
  .pagination {
    flex-wrap: wrap;
  }
}
</style>