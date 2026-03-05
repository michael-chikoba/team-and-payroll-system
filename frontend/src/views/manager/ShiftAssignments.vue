<template>
  <div class="shifts-page">

    <!-- ── Header Card ─────────────────────────────── -->
    <div class="dashboard-header-card">
      <div class="header-card-accent"></div>
      <div class="user-greeting">
        <div class="avatar-section">
          <div class="avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
              <line x1="16" y1="2" x2="16" y2="6"></line>
              <line x1="8" y1="2" x2="8" y2="6"></line>
              <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
          </div>
          <div class="user-info">
            <h1 class="greeting">Shift Assignments</h1>
            <p class="subtitle">Manage and view shift assignments across your team</p>
            <div class="role-meta">
              <span class="role-badge">Manager View</span>
            </div>
          </div>
        </div>

        <div class="header-actions">
          <button @click="loadAssignments()" class="btn-outline" :disabled="loading">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
            Refresh
          </button>
          <button @click="toggleShowAll" class="btn-outline" :class="{ active: showAll }">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
            {{ showAll ? 'Show Recent' : 'Show All' }}
          </button>
          <button @click="openAssignModal" class="btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Assign Shift
          </button>
        </div>
      </div>
    </div>

    <div class="dashboard-content">

      <!-- ── Metrics ──────────────────────────────── -->
      <div class="metrics-section" v-if="assignments.length > 0 && !loading">
        <h2>Summary</h2>
        <div class="metrics-grid">
          <div class="metric-card" style="--accent:#6366f1;">
            <div class="metric-icon-wrap" style="background:rgba(99,102,241,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </div>
            <div class="metric-value">{{ stats.total }}</div>
            <div class="metric-label">Total Shifts</div>
          </div>

          <div class="metric-card" style="--accent:#f59e0b;">
            <div class="metric-icon-wrap" style="background:rgba(245,158,11,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <div class="metric-value">{{ stats.pending }}</div>
            <div class="metric-label">Pending</div>
          </div>

          <div class="metric-card" style="--accent:#10b981;">
            <div class="metric-icon-wrap" style="background:rgba(16,185,129,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <div class="metric-value">{{ stats.accepted }}</div>
            <div class="metric-label">Accepted</div>
          </div>

          <div class="metric-card" style="--accent:#3b82f6;">
            <div class="metric-icon-wrap" style="background:rgba(59,130,246,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <div class="metric-value">{{ stats.uniqueEmployees }}</div>
            <div class="metric-label">Employees</div>
          </div>
        </div>
      </div>

      <!-- ── Filters + List ───────────────────────── -->
      <div class="table-section">

        <!-- Controls Bar -->
        <div class="controls-bar">
          <div class="filters-row">
            <div class="filter-group">
              <label>Status</label>
              <select v-model="filters.status" @change="loadAssignments()" class="filter-select">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="accepted">Accepted</option>
                <option value="rejected">Rejected</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div class="filter-group">
              <label>Shift Type</label>
              <select v-model="filters.shift_type" @change="loadAssignments()" class="filter-select">
                <option value="">All Types</option>
                <option value="morning">Morning</option>
                <option value="day">Day</option>
                <option value="evening">Evening</option>
                <option value="night">Night</option>
              </select>
            </div>
            <div class="filter-group">
              <label>Employee</label>
              <div class="search-wrapper">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="search-icon"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input
                  v-model="filters.employee_name"
                  type="text"
                  @input="debounceLoadAssignments"
                  class="filter-input"
                  placeholder="Search name..."
                />
              </div>
            </div>
            <div class="filter-group">
              <label>From</label>
              <input v-model="filters.from_date" type="date" @change="loadAssignments()" class="filter-select date-input"/>
            </div>
            <div class="filter-group">
              <label>To</label>
              <input v-model="filters.to_date" type="date" @change="loadAssignments()" class="filter-select date-input"/>
            </div>
          </div>
          <div class="controls-right">
            <span class="records-count" v-if="!loading">{{ pagination.total || assignments.length }} shift{{ (pagination.total || assignments.length) !== 1 ? 's' : '' }}</span>
            <button v-if="activeFiltersCount > 0" @click="clearFilters" class="btn-clear">
              Clear Filters
            </button>
          </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="empty-state">
          <div class="spinner"></div>
          <p>Loading schedule...</p>
        </div>

        <!-- Empty -->
        <div v-else-if="assignments.length === 0" class="empty-state">
          <div class="empty-icon-wrap">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
          </div>
          <h3>No Shifts Found</h3>
          <p>{{ activeFiltersCount > 0 ? 'Try adjusting your filters.' : 'Get started by creating a new shift assignment.' }}</p>
          <div class="empty-actions">
            <button @click="openAssignModal" class="btn-primary">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
              Assign Shift
            </button>
            <button v-if="activeFiltersCount > 0" @click="clearFilters" class="btn-secondary">Clear Filters</button>
          </div>
        </div>

        <!-- Assignment Cards -->
        <div v-else class="assignments-list">
          <div
            v-for="assignment in assignments"
            :key="assignment.id"
            class="assignment-card"
          >
            <div class="card-left">
              <!-- Employee Avatar + Info -->
              <div class="emp-section">
                <div class="emp-avatar">{{ getEmployeeInitial(assignment) }}</div>
                <div class="emp-details">
                  <div class="emp-name">{{ getEmployeeName(assignment) }}</div>
                  <div class="emp-meta" v-if="getEmployeeDetails(assignment)">
                    <span v-if="assignment.employee?.employee_id" class="meta-chip">
                      ID: {{ assignment.employee.employee_id }}
                    </span>
                    <span v-if="assignment.employee?.position" class="meta-chip">
                      {{ assignment.employee.position }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Shift Info Grid -->
              <div class="shift-info-grid">
                <div class="shift-info-item">
                  <span class="info-label">Date</span>
                  <span class="info-value">{{ formatDate(assignment.shift_date) }}</span>
                </div>
                <div class="shift-info-item">
                  <span class="info-label">Type</span>
                  <span class="info-value">
                    <span class="type-dot" :class="'type-' + assignment.shift_type"></span>
                    {{ capitalize(assignment.shift_type) }}
                  </span>
                </div>
                <div class="shift-info-item">
                  <span class="info-label">Time</span>
                  <span class="info-value">{{ formatTime(assignment.start_time) }} – {{ formatTime(assignment.end_time) }}</span>
                </div>
                <div class="shift-info-item">
                  <span class="info-label">Duration</span>
                  <span class="info-value">{{ calculateShiftDuration(assignment.start_time, assignment.end_time) }}</span>
                </div>
                <div class="shift-info-item">
                  <span class="info-label">Assigned By</span>
                  <span class="info-value">{{ getAssignedByName(assignment) || 'Unknown' }}</span>
                </div>
                <div class="shift-info-item" v-if="assignment.notes">
                  <span class="info-label">Notes</span>
                  <span class="info-value note-value">{{ assignment.notes }}</span>
                </div>
              </div>
            </div>

            <!-- Card Right: Status + Actions -->
            <div class="card-right">
              <span :class="['status-badge', getStatusClass(assignment.status)]">
                <span class="dot"></span>{{ capitalize(assignment.status) }}
              </span>
              <div class="card-actions">
                <button v-if="canEdit(assignment)" @click="editAssignment(assignment)" class="action-btn" title="Edit Shift">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button v-if="canDelete(assignment)" @click="deleteAssignment(assignment.id)" class="action-btn danger" title="Delete Shift">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.total > 0 && pagination.last_page > 1" class="pagination-bar">
          <span class="pagination-info">
            Showing <strong>{{ pagination.from }}</strong>–<strong>{{ pagination.to }}</strong> of <strong>{{ pagination.total }}</strong> shifts
          </span>
          <div class="pagination-controls">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="page-btn">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
              Prev
            </button>
            <template v-for="page in visiblePages" :key="page">
              <span v-if="page === '...'" class="page-ellipsis">…</span>
              <button
                v-else
                @click="changePage(page)"
                :class="['page-btn', { active: pagination.current_page === page }]">
                {{ page }}
              </button>
            </template>
            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="page-btn">
              Next
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <AssignShiftModal
      v-if="showAssignModal"
      :show="showAssignModal"
      :assignment="selectedAssignment"
      :employees="employees"
      :loading-employees="loadingEmployees"
      :auth-store="authStore"
      @close="closeAssignModal"
      @saved="handleAssignmentSaved"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue'
import { format } from 'date-fns'
import axios from 'axios'
import { useAuthStore } from '../../stores/auth'
import AssignShiftModal from './AssignShiftModal.vue'

const authStore = useAuthStore()
const assignments = ref([])
const employees = ref([])
const loading = ref(false)
const loadingEmployees = ref(false)
const showAssignModal = ref(false)
const selectedAssignment = ref(null)
const showAll = ref(false)
let debounceTimer = null

const stats = reactive({ total: 0, pending: 0, accepted: 0, rejected: 0, uniqueEmployees: 0 })

const pagination = reactive({
  total: 0, per_page: 20, current_page: 1, last_page: 1, from: 0, to: 0
})

const filters = reactive({
  status: '', shift_type: '', employee_name: '', from_date: '', to_date: ''
})

const visiblePages = computed(() => {
  const pages = []
  const current = pagination.current_page
  const last = pagination.last_page
  pages.push(1)
  let start = Math.max(2, current - 1)
  let end = Math.min(last - 1, current + 1)
  if (current <= 3) end = Math.min(last - 1, 4)
  if (current >= last - 2) start = Math.max(2, last - 3)
  if (start > 2) pages.push('...')
  for (let i = start; i <= end; i++) pages.push(i)
  if (end < last - 1) pages.push('...')
  if (last > 1 && !pages.includes(last)) pages.push(last)
  return pages
})

const activeFiltersCount = computed(() =>
  Object.values(filters).filter(v => v !== '').length
)

const toggleShowAll = () => { showAll.value = !showAll.value; loadAssignments(1) }
const clearFilters = () => {
  filters.status = ''; filters.shift_type = ''; filters.employee_name = ''
  filters.from_date = ''; filters.to_date = ''
  loadAssignments(1)
}

const loadAssignments = async (page = 1) => {
  loading.value = true
  try {
    const params = { page, ...filters, show_all: showAll.value ? 1 : 0 }
    Object.keys(params).forEach(k => (params[k] === '' || params[k] == null) && delete params[k])
    const response = await axios.get('/api/shift-assignments', { params })
    const data = response.data.assignments || response.data.data || (Array.isArray(response.data) ? response.data : [])
    assignments.value = data || []
    calculateStats()
    if (response.data.pagination) {
      Object.assign(pagination, response.data.pagination)
    } else {
      Object.assign(pagination, { total: data.length, from: 1, to: data.length, current_page: page, last_page: 1 })
    }
  } catch (error) {
    console.error('Failed to load assignments', error)
    assignments.value = []
  } finally {
    loading.value = false
  }
}

const calculateStats = () => {
  stats.total = assignments.value.length
  stats.pending = assignments.value.filter(a => a.status === 'pending').length
  stats.accepted = assignments.value.filter(a => a.status === 'accepted').length
  stats.rejected = assignments.value.filter(a => a.status === 'rejected').length
  stats.uniqueEmployees = new Set(assignments.value.map(a => a.employee_id).filter(Boolean)).size
}

const debounceLoadAssignments = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => loadAssignments(1), 500)
}

const changePage = (page) => {
  if (page >= 1 && page <= pagination.last_page) loadAssignments(page)
}

const fetchEmployees = async () => {
  if (employees.value.length > 0) return
  loadingEmployees.value = true
  try {
    const isAdmin = authStore.user?.roles?.includes('admin') || authStore.user?.roles?.includes('super-admin')
    const endpoint = isAdmin ? '/api/admin/employees' : '/api/manager/employees'
    const response = await axios.get(endpoint)
    employees.value = response.data.data || response.data.employees || response.data || []
  } catch (error) {
    console.error('Failed to fetch employees', error)
    employees.value = []
  } finally {
    loadingEmployees.value = false
  }
}

const openAssignModal = async () => {
  selectedAssignment.value = null
  showAssignModal.value = true
  await fetchEmployees()
}

const editAssignment = async (assignment) => {
  selectedAssignment.value = assignment
  showAssignModal.value = true
  await fetchEmployees()
}

const closeAssignModal = () => { showAssignModal.value = false; selectedAssignment.value = null }
const handleAssignmentSaved = () => { closeAssignModal(); loadAssignments(pagination.current_page) }

const deleteAssignment = async (id) => {
  if (!confirm('Are you sure you want to delete this shift assignment?')) return
  try {
    await axios.delete(`/api/shift-assignments/${id}`)
    loadAssignments(pagination.current_page)
  } catch (error) {
    alert('Failed to delete: ' + (error.response?.data?.message || error.message))
  }
}

const canEdit = (a) => {
  const roles = authStore.user?.roles || []
  return (roles.includes('manager') || roles.includes('admin') || roles.includes('super-admin'))
    && ['pending', 'accepted'].includes(a.status)
}

const canDelete = (a) => {
  const roles = authStore.user?.roles || []
  return (roles.includes('manager') || roles.includes('admin') || roles.includes('super-admin'))
    && a.status !== 'completed'
}

const formatDate = (d) => {
  if (!d) return '—'
  try { return format(new Date(d), 'MMM dd, yyyy') } catch { return d }
}

const formatTime = (t) => {
  if (!t) return ''
  try {
    const [h, m] = t.split(':')
    const hour = parseInt(h), minute = parseInt(m)
    return `${hour % 12 || 12}:${minute.toString().padStart(2, '0')} ${hour >= 12 ? 'PM' : 'AM'}`
  } catch { return t }
}

const calculateShiftDuration = (start, end) => {
  if (!start || !end) return ''
  try {
    const [sh, sm] = start.split(':').map(Number)
    const [eh, em] = end.split(':').map(Number)
    let diff = (eh * 60 + em) - (sh * 60 + sm)
    if (diff < 0) diff += 24 * 60
    return `${Math.floor(diff / 60)}h ${diff % 60}m`
  } catch { return '' }
}

const capitalize = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1) : ''

const getEmployeeName = (a) =>
  a.employee?.full_name || a.employee?.user?.name || a.employee?.name ||
  (a.employee?.employee_id ? `Employee #${a.employee.employee_id}` : 'Unknown Employee')

const getEmployeeInitial = (a) => getEmployeeName(a).charAt(0).toUpperCase()

const getEmployeeDetails = (a) =>
  a.employee && (a.employee.employee_id || a.employee.position || a.employee.department?.name)

const getAssignedByName = (a) =>
  a.assigned_by?.name || a.assigned_by?.full_name || a.assigned_by?.user?.name || null

const getStatusClass = (s) => ({
  pending: 'warning', accepted: 'success', rejected: 'danger',
  completed: 'info', cancelled: 'neutral'
}[s] || 'neutral')

onMounted(() => {
  const today = new Date()
  const from = new Date(); from.setDate(today.getDate() - 30)
  filters.from_date = from.toISOString().split('T')[0]
  filters.to_date = new Date().toISOString().split('T')[0]
  loadAssignments()
})

watch(() => authStore.user, () => loadAssignments())
</script>

<style scoped>
/* ── Base ──────────────────────────────────────────── */
.shifts-page {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
  padding: 1.5rem 2rem 3rem;
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* ── Header Card ─────────────────────────────────── */
.dashboard-header-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem 1.75rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0;
  position: relative;
  overflow: hidden;
  flex-shrink: 0;
}

.header-card-accent {
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
}

.user-greeting { display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; }
.avatar-section { display: flex; align-items: center; gap: 1rem; }

.avatar {
  width: 52px; height: 52px;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  border-radius: 14px; display: flex; align-items: center; justify-content: center;
  color: white; box-shadow: 0 4px 12px rgba(59,130,246,0.25); flex-shrink: 0;
}

.user-info { display: flex; flex-direction: column; gap: 0.2rem; }
.greeting { margin: 0; font-size: 1.375rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
.subtitle { margin: 0; color: #64748b; font-size: 0.875rem; }
.role-meta { margin-top: 0.125rem; }

.role-badge {
  background: #eff6ff; border: 1px solid #bfdbfe;
  padding: 0.125rem 0.6rem; border-radius: 8px;
  font-size: 0.7rem; font-weight: 600; color: #1d4ed8; display: inline-block;
}

.header-actions { display: flex; gap: 0.5rem; flex-shrink: 0; align-items: center; }

/* ── Buttons ─────────────────────────────────────── */
.btn-primary {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;
  border: none; padding: 0.5rem 1.1rem; border-radius: 8px;
  font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
  font-family: inherit;
}
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,0.35); }

.btn-outline {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.45rem 0.9rem; background: white; border: 1px solid #e2e8f0;
  color: #475569; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-outline:hover:not(:disabled) { background: #f8fafc; border-color: #cbd5e1; color: #1e293b; }
.btn-outline.active { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
.btn-outline:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-secondary {
  padding: 0.5rem 1.1rem; background: #f1f5f9; color: #475569;
  border: 1px solid #e2e8f0; border-radius: 8px;
  font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-secondary:hover { background: #e2e8f0; }

.btn-clear {
  padding: 0.25rem 0.75rem; background: none; border: none;
  color: #6366f1; font-size: 0.78rem; font-weight: 600;
  cursor: pointer; font-family: inherit; transition: color 0.15s;
}
.btn-clear:hover { color: #4f46e5; text-decoration: underline; }

/* ── Dashboard Content ───────────────────────────── */
.dashboard-content { display: flex; flex-direction: column; gap: 1.5rem; }

/* ── Section Cards ───────────────────────────────── */
.metrics-section,
.table-section {
  background: white; border-radius: 16px;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0; padding: 1.5rem;
}
h2 { font-size: 1.1rem; font-weight: 600; margin: 0 0 1.25rem 0; color: #334155; }

/* ── Metrics ─────────────────────────────────────── */
.metrics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; }

.metric-card {
  padding: 1.25rem; background: #f8fafc; border-radius: 12px;
  display: flex; flex-direction: column; align-items: center; text-align: center;
  border: 1px solid #e2e8f0; position: relative; overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}
.metric-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px -4px rgba(0,0,0,0.08); border-color: var(--accent); }
.metric-card::before { display: none; }
.metric-icon-wrap { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem; }
.metric-value { font-size: 1.8rem; font-weight: 800; color: #0f172a; line-height: 1.1; margin-bottom: 0.25rem; }
.metric-label { font-size: 0.78rem; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.04em; }

/* ── Controls ────────────────────────────────────── */
.controls-bar {
  display: flex; justify-content: space-between; align-items: flex-end;
  margin-bottom: 1.25rem; gap: 1rem; flex-wrap: wrap;
}
.filters-row { display: flex; gap: 0.75rem; flex-wrap: wrap; align-items: flex-end; }
.filter-group { display: flex; flex-direction: column; gap: 0.3rem; }
.filter-group label { font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }

.filter-select {
  padding: 0.45rem 2rem 0.45rem 0.75rem; border: 1px solid #e2e8f0;
  border-radius: 8px; background: #f8fafc; color: #334155;
  font-size: 0.82rem; font-weight: 500; cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 0.6rem center;
  transition: all 0.2s; font-family: inherit;
}
.filter-select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.date-input { background-image: none; padding-right: 0.75rem; cursor: default; }

.search-wrapper { position: relative; }
.search-icon { position: absolute; left: 0.6rem; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
.filter-input {
  padding: 0.45rem 0.75rem 0.45rem 1.9rem; border: 1px solid #e2e8f0;
  border-radius: 8px; background: #f8fafc; color: #334155;
  font-size: 0.82rem; font-weight: 500; width: 160px;
  transition: all 0.2s; font-family: inherit;
}
.filter-input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
.filter-input::placeholder { color: #94a3b8; }

.controls-right { display: flex; align-items: center; gap: 0.5rem; flex-shrink: 0; }
.records-count {
  font-size: 0.78rem; font-weight: 700; color: #64748b;
  background: #f1f5f9; padding: 0.2rem 0.7rem; border-radius: 9999px; white-space: nowrap;
}

/* ── States ──────────────────────────────────────── */
.spinner {
  width: 40px; height: 40px; border: 3px solid #e2e8f0; border-top-color: #6366f1;
  border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;
}
@keyframes spin { to { transform: rotate(360deg); } }

.empty-state {
  text-align: center; padding: 4rem 2rem;
  display: flex; flex-direction: column; align-items: center; gap: 0.875rem;
}
.empty-icon-wrap {
  width: 64px; height: 64px; border-radius: 16px; background: #f1f5f9;
  border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: #94a3b8;
}
.empty-state h3 { margin: 0; font-size: 1.1rem; font-weight: 700; color: #1e293b; }
.empty-state p { margin: 0; font-size: 0.875rem; color: #64748b; max-width: 320px; }
.empty-actions { display: flex; gap: 0.75rem; margin-top: 0.25rem; }

/* ── Assignment Cards ────────────────────────────── */
.assignments-list { display: flex; flex-direction: column; gap: 0.875rem; }

.assignment-card {
  display: flex; justify-content: space-between; align-items: flex-start;
  gap: 1.25rem; padding: 1.25rem;
  border: 1px solid #e2e8f0; border-radius: 12px; background: #fdfdfe;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.assignment-card:hover { border-color: #c7d2fe; box-shadow: 0 4px 12px -4px rgba(0,0,0,0.07); }

.card-left { display: flex; gap: 1.125rem; flex: 1; min-width: 0; }

.emp-section { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; flex-shrink: 0; }

.emp-avatar {
  width: 42px; height: 42px; border-radius: 50%;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  color: white; display: flex; align-items: center; justify-content: center;
  font-size: 1rem; font-weight: 700; flex-shrink: 0;
}

.emp-details { min-width: 0; }
.emp-name { font-size: 0.9rem; font-weight: 700; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.emp-meta { display: flex; flex-wrap: wrap; gap: 0.35rem; margin-top: 0.3rem; }
.meta-chip {
  font-size: 0.68rem; font-weight: 600; color: #475569;
  background: #f1f5f9; border: 1px solid #e2e8f0;
  padding: 0.1rem 0.5rem; border-radius: 9999px;
}

/* Shift Info Grid */
.shift-info-grid {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.5rem 1.5rem;
  flex: 1; min-width: 0; align-content: start;
}
.shift-info-item { display: flex; flex-direction: column; gap: 0.1rem; }
.info-label { font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.04em; }
.info-value { font-size: 0.82rem; font-weight: 600; color: #334155; display: flex; align-items: center; gap: 0.35rem; }
.note-value { font-weight: 400; color: #64748b; font-size: 0.8rem; }

/* Shift type dots inside info */
.type-dot {
  display: inline-block; width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0;
}
.type-morning { background: #f97316; }
.type-day     { background: #3b82f6; }
.type-evening { background: #8b5cf6; }
.type-night   { background: #475569; }

/* Card Right */
.card-right {
  display: flex; flex-direction: column; align-items: flex-end;
  gap: 0.75rem; flex-shrink: 0;
}

.card-actions { display: flex; gap: 0.35rem; }

.action-btn {
  width: 30px; height: 30px; border-radius: 6px;
  border: 1px solid #e2e8f0; background: white; color: #64748b;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.15s;
}
.action-btn:hover { background: #eff6ff; color: #4f46e5; border-color: #a5b4fc; }
.action-btn.danger:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }

/* Status Badges */
.status-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 0.25rem 0.65rem; border-radius: 9999px;
  font-size: 0.7rem; font-weight: 700; white-space: nowrap;
}
.dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
.status-badge.success { background: #d1fae5; color: #065f46; }
.status-badge.warning { background: #fef3c7; color: #92400e; }
.status-badge.danger  { background: #fee2e2; color: #991b1b; }
.status-badge.info    { background: #dbeafe; color: #1e40af; }
.status-badge.neutral { background: #f1f5f9; color: #64748b; }

/* ── Pagination ──────────────────────────────────── */
.pagination-bar {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1rem 0 0; border-top: 1px solid #f1f5f9; margin-top: 0.875rem;
}
.pagination-info { font-size: 0.82rem; color: #64748b; }
.pagination-info strong { color: #1e293b; font-weight: 700; }
.pagination-controls { display: flex; gap: 0.35rem; align-items: center; }

.page-btn {
  display: inline-flex; align-items: center; gap: 0.3rem;
  padding: 0.35rem 0.75rem; background: white; border: 1px solid #e2e8f0;
  border-radius: 6px; font-size: 0.78rem; font-weight: 600; color: #475569;
  cursor: pointer; transition: all 0.15s; font-family: inherit; min-width: 2rem; justify-content: center;
}
.page-btn:hover:not(:disabled) { border-color: #a5b4fc; color: #4f46e5; background: #eff6ff; }
.page-btn.active { background: #6366f1; color: white; border-color: #6366f1; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
.page-ellipsis { padding: 0 0.25rem; color: #94a3b8; font-size: 0.82rem; }

/* ── Responsive ──────────────────────────────────── */
@media (max-width: 1000px) {
  .shift-info-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 768px) {
  .shifts-page { padding: 1rem 1rem 2rem; }
  .user-greeting { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .header-actions { width: 100%; flex-wrap: wrap; }
  .metrics-grid { grid-template-columns: repeat(2, 1fr); }
  .controls-bar { flex-direction: column; align-items: flex-start; }
  .filters-row { width: 100%; }
  .filter-input { width: 100%; }
  .assignment-card { flex-direction: column; gap: 1rem; }
  .card-left { flex-direction: column; gap: 0.875rem; }
  .shift-info-grid { grid-template-columns: repeat(2, 1fr); }
  .card-right { flex-direction: row; align-items: center; justify-content: space-between; width: 100%; }
  .pagination-bar { flex-direction: column; gap: 0.75rem; align-items: flex-start; }
}

@media (max-width: 480px) {
  .metrics-grid { grid-template-columns: 1fr 1fr; }
  .shift-info-grid { grid-template-columns: 1fr 1fr; }
}
</style>