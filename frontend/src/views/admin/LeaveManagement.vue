<template>
  <div class="leave-management">
    <div class="management-main">

      <!-- ── Header Card ─────────────────────────────── -->
      <div class="dashboard-header-card">
        <div class="header-card-accent"></div>
        <div class="header-inner">

          <!-- Brand + Title -->
          <div class="user-greeting">
            <div class="avatar-section">
              <div class="avatar">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
              </div>
              <div class="user-info">
                <h1 class="greeting">Leave Management Dashboard</h1>
                <p class="subtitle">
                  {{ userRole === 'admin' ? 'All business leaves' : 'Your team leaves' }} for {{ currentMonthYear }}
                </p>
                <div class="role-meta">
                  <span class="role-badge">{{ userRole === 'admin' ? 'Admin View' : userRole === 'manager' ? 'Manager View' : 'Employee View' }}</span>
                  <span class="month-badge">{{ currentMonthYear }}</span>
                </div>
              </div>
            </div>

            <!-- Controls (Admin Only) -->
            <div v-if="userRole === 'admin'" class="header-controls">
              <div class="select-wrapper">
                <select v-model="selectedBusinessId" @change="refreshData" class="filter-select">
                  <option value="">All Businesses</option>
                  <option v-for="business in businesses" :key="business.id" :value="business.id">
                    {{ business.name }}
                  </option>
                </select>
                <span class="select-caret">▾</span>
              </div>

              <button @click="refreshData" class="btn-outline" :disabled="loading">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" :class="{ 'spinning': loading }">
                  <path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path>
                  <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                </svg>
                {{ loading ? 'Loading…' : 'Refresh' }}
              </button>
            </div>
          </div>

          <!-- Stats badge -->
          <div class="date-badge">
            <div class="date-content">
              <span class="stats-primary">{{ statistics.total_leaves }}</span>
              <div class="date-details">
                <span class="stats-label">Total Leaves</span>
                <span class="stats-total">{{ statistics.pending_count }} pending</span>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Statistics Cards (Dashboard style) -->
      <div class="stats-grid">
        <!-- Total Leaves -->
        <div class="stat-card" style="--accent:#8b5cf6;">
          <div class="stat-icon-wrap" style="background:rgba(139,92,246,0.1);">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
              <polyline points="14 2 14 8 20 8"></polyline>
              <line x1="16" y1="13" x2="8" y2="13"></line>
              <line x1="16" y1="17" x2="8" y2="17"></line>
              <polyline points="10 9 9 9 8 9"></polyline>
            </svg>
          </div>
          <div class="stat-info">
            <span class="stat-label">Total Leaves</span>
            <div class="stat-number">{{ statistics.total_leaves }}</div>
            <div class="stat-trend">This month</div>
          </div>
        </div>

        <!-- Pending Leaves -->
        <div class="stat-card" style="--accent:#f59e0b;">
          <div class="stat-icon-wrap" style="background:rgba(245,158,11,0.1);">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
          </div>
          <div class="stat-info">
            <span class="stat-label">Pending Approval</span>
            <div class="stat-number">{{ statistics.pending_count }}</div>
            <div class="stat-trend">
              <span class="trend-warn">Awaiting action</span>
            </div>
          </div>
        </div>

        <!-- Approved Leaves -->
        <div class="stat-card" style="--accent:#10b981;">
          <div class="stat-icon-wrap" style="background:rgba(16,185,129,0.1);">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
          </div>
          <div class="stat-info">
            <span class="stat-label">Approved</span>
            <div class="stat-number">{{ statistics.approved_count }}</div>
            <div class="stat-trend">
              <span class="trend-up">{{ ((statistics.approved_count / statistics.total_leaves) * 100 || 0).toFixed(0) }}%</span> approval rate
            </div>
          </div>
        </div>

        <!-- On Leave Today -->
        <div class="stat-card" style="--accent:#3b82f6;">
          <div class="stat-icon-wrap" style="background:rgba(59,130,246,0.1);">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
          </div>
          <div class="stat-info">
            <span class="stat-label">On Leave Today</span>
            <div class="stat-number">{{ statistics.on_leave_count }}</div>
            <div class="stat-trend">Currently absent</div>
          </div>
        </div>
      </div>

      <!-- Employees Currently On Leave -->
      <div v-if="onLeaveEmployees.length > 0" class="section-container">
        <div class="card-header">
          <h3>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
            Employees Currently On Leave
          </h3>
          <span class="count-badge">{{ onLeaveEmployees.length }}</span>
        </div>
        <div class="on-leave-grid">
          <div
            v-for="emp in onLeaveEmployees"
            :key="emp.id"
            class="employee-card"
          >
            <div class="emp-card-head">
              <div class="emp-avatar" style="background: #3b82f6;">{{ emp.name.charAt(0) }}</div>
              <div class="emp-card-info">
                <div class="emp-card-name">{{ emp.name }}</div>
                <div class="emp-card-meta">
                  <span class="emp-card-dept">{{ emp.department }}</span>
                </div>
              </div>
            </div>
            <div class="on-leave-details">
              <div class="detail-item">
                <span class="detail-label">Type:</span>
                <span class="detail-value">{{ formatLeaveType(emp.leave_type) }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Until:</span>
                <span class="detail-value">{{ formatDate(emp.end_date) }}</span>
              </div>
              <div class="detail-item highlight">
                <span class="detail-label">Remaining:</span>
                <span class="detail-value">{{ Math.round(emp.days_remaining) }} {{ Math.round(emp.days_remaining) === 1 ? 'day' : 'days' }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Search and Filters (Admin Only) -->
      <div v-if="userRole === 'admin'" class="controls-bar">
        <div class="filters-row">
          <div class="filter-group">
            <label>Search</label>
            <div class="search-wrapper">
              <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input
                v-model="search"
                type="text"
                placeholder="Search by employee name..."
                class="filter-input"
              />
            </div>
          </div>

          <div class="filter-group">
            <label>Status</label>
            <select v-model="statusFilter" class="filter-select">
              <option value="all">All Status</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
        </div>
        <span class="records-count" v-if="!loading">{{ filteredLeaves.length }} record{{ filteredLeaves.length !== 1 ? 's' : '' }}</span>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="empty-state">
        <div class="spinner"></div>
        <p>Loading leave records...</p>
      </div>

      <!-- Leave Records Table (Updated to match payslip style) -->
      <div v-else class="table-section">
        <!-- Table Header -->
        <div class="list-header">
          <div class="col-employee">Employee</div>
          <div class="col-business" v-if="userRole === 'admin'">Business</div>
          <div class="col-department">Department</div>
          <div class="col-type">Leave Type</div>
          <div class="col-dates">Dates</div>
          <div class="col-days">Days</div>
          <div class="col-reason">Reason</div>
          <div class="col-status">Status</div>
          <div class="col-actions">Actions</div>
        </div>

        <!-- Table Rows -->
        <div
          v-for="leave in paginatedLeaves"
          :key="leave.id"
          class="list-row"
          @click="viewLeaveDetails(leave)"
        >
          <div class="col-employee">
            <div class="emp-cell">
              <div class="emp-avatar-sm" :style="{ background: getAvatarColor(leave.employee?.id || leave.id) }">
                {{ getEmployeeName(leave).charAt(0) }}
              </div>
              <span class="emp-name">{{ getEmployeeName(leave) }}</span>
            </div>
          </div>

          <div class="col-business" v-if="userRole === 'admin'">
            {{ leave.employee?.business?.name || '—' }}
          </div>

          <div class="col-department">
            <span class="dept-tag">{{ leave.employee?.department || 'N/A' }}</span>
          </div>

          <div class="col-type">{{ formatLeaveType(leave.leave_type || leave.type) }}</div>

          <div class="col-dates">
            <div class="dates-wrap">
              <span class="date-start">{{ formatDate(leave.start_date) }}</span>
              <span class="date-arrow">→</span>
              <span class="date-end">{{ formatDate(leave.end_date) }}</span>
            </div>
          </div>

          <div class="col-days font-mono">{{ leave.total_days || calculateDays(leave.start_date, leave.end_date) }}</div>

          <div class="col-reason" :title="leave.reason || 'N/A'">
            {{ truncateReason(leave.reason) }}
          </div>

          <div class="col-status">
            <span :class="['status-badge', getStatusClass(leave.status)]">
              <span class="dot"></span>{{ formatStatus(leave.status) }}
            </span>
          </div>

          <div class="col-actions" @click.stop>
            <div v-if="leave.status === 'pending'" class="action-group">
              <button
                @click="approveLeave(leave.id)"
                :disabled="submitting"
                class="action-btn approve"
                title="Approve"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
              </button>
              <button
                @click="rejectLeave(leave.id)"
                :disabled="submitting"
                class="action-btn reject"
                title="Reject"
              >
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <line x1="18" y1="6" x2="6" y2="18"></line>
                  <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
              </button>
            </div>
            <span v-else class="text-muted">—</span>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="filteredLeaves.length === 0 && !loading" class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
            <polyline points="14 2 14 8 20 8"></polyline>
            <line x1="16" y1="13" x2="8" y2="13"></line>
            <line x1="16" y1="17" x2="8" y2="17"></line>
          </svg>
          <h3>No Leave Records Found</h3>
          <p>No leave records match your current filters.</p>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="pagination-bar">
          <span class="pagination-info">Page <strong>{{ currentPage }}</strong> of <strong>{{ totalPages }}</strong></span>
          <div class="pagination-controls">
            <button @click="prevPage" :disabled="currentPage === 1" class="page-btn">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
              Prev
            </button>
            <button @click="nextPage" :disabled="currentPage === totalPages" class="page-btn">
              Next
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

// Props (if needed for role detection)
const props = defineProps({
  userRole: {
    type: String,
    default: 'admin' // Can be 'admin', 'manager', or 'employee'
  }
})

const leaves = ref([])
const onLeaveEmployees = ref([])
const businesses = ref([])
const loading = ref(false)
const submitting = ref(false)
const search = ref('')
const statusFilter = ref('all')
const selectedBusinessId = ref('')
const currentPage = ref(1)
const perPage = ref(10)

const statistics = ref({
  total_leaves: 0,
  pending_count: 0,
  approved_count: 0,
  rejected_count: 0,
  on_leave_count: 0
})

const currentMonthYear = computed(() => {
  return new Date().toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long'
  })
})

// Fetch businesses for admin
const fetchBusinesses = async () => {
  if (props.userRole !== 'admin') return
  
  try {
    const response = await axios.get('/api/admin/businesses')
    businesses.value = response.data.data || response.data.businesses || []
  } catch (error) {
    console.error('❌ Error fetching businesses:', error)
  }
}

const fetchLeaves = async () => {
  loading.value = true
  try {
    // Based on role, use appropriate endpoint
    let endpoint
    const params = {}
    
    if (props.userRole === 'admin') {
      endpoint = '/api/admin/leaves/current-month'
      if (selectedBusinessId.value) {
        params.business_id = selectedBusinessId.value
      }
    } else if (props.userRole === 'manager') {
      endpoint = '/api/manager/leaves/current-month'
    } else {
      // For employees, you might want to use a different endpoint
      endpoint = '/api/employee/leaves'
    }

    console.log('Fetching leaves from:', endpoint, 'with params:', params)
    
    const response = await axios.get(endpoint, { params })
    const data = response.data

    console.log('Leave data received:', data)

    // Use all_leaves if available, otherwise fall back to pending_leaves
    if (data.all_leaves && Array.isArray(data.all_leaves)) {
      leaves.value = data.all_leaves
    } else if (data.pending_leaves && Array.isArray(data.pending_leaves)) {
      leaves.value = data.pending_leaves
    } else if (Array.isArray(data)) {
      leaves.value = data
    } else if (data.data && Array.isArray(data.data)) {
      leaves.value = data.data
    } else {
      leaves.value = []
    }

    console.log('Leaves loaded:', leaves.value.length, 'items')

    // Extract on-leave employees from dashboard data structure
    if (data.on_leave_employees && Array.isArray(data.on_leave_employees)) {
      onLeaveEmployees.value = data.on_leave_employees
    } else {
      // Fallback: extract from leaves data
      onLeaveEmployees.value = leaves.value
        .filter(leave => 
          leave.status === 'approved' && 
          isOnLeaveToday(leave.start_date, leave.end_date)
        )
        .map(leave => ({
          id: leave.employee?.id || leave.employee_id,
          name: getEmployeeName(leave),
          department: leave.employee?.department || 'N/A',
          leave_type: leave.leave_type || leave.type,
          end_date: leave.end_date,
          days_remaining: calculateDaysRemaining(leave.end_date)
        }))
    }

    // Update statistics based on dashboard structure
    if (data.status_counts || data.pending_count !== undefined) {
      // Use status_counts if available
      if (data.status_counts) {
        statistics.value = {
          total_leaves: data.total_leaves_this_month || 
                       (data.status_counts.pending + data.status_counts.approved + data.status_counts.rejected + data.status_counts.cancelled),
          pending_count: data.status_counts.pending || data.pending_count || 0,
          approved_count: data.status_counts.approved || data.approved_count || 0,
          rejected_count: data.status_counts.rejected || data.rejected_count || 0,
          on_leave_count: data.on_leave_count || 0
        }
      } else {
        statistics.value = {
          total_leaves: data.total_leaves_this_month || leaves.value.length,
          pending_count: data.pending_count || leaves.value.filter(l => l.status === 'pending').length,
          approved_count: data.approved_count || leaves.value.filter(l => l.status === 'approved').length,
          rejected_count: data.rejected_count || leaves.value.filter(l => l.status === 'rejected').length,
          on_leave_count: data.on_leave_count || onLeaveEmployees.value.length
        }
      }
    } else {
      // Calculate from leaves data
      statistics.value = {
        total_leaves: leaves.value.length,
        pending_count: leaves.value.filter(l => l.status === 'pending').length,
        approved_count: leaves.value.filter(l => l.status === 'approved').length,
        rejected_count: leaves.value.filter(l => l.status === 'rejected').length,
        on_leave_count: onLeaveEmployees.value.length
      }
    }

    console.log('Statistics updated:', statistics.value)

  } catch (error) {
    console.error('❌ Error fetching leave records:', error)
    if (error.response?.status === 404) {
      console.error('Endpoint not found. Check your route configuration.')
    }
    alert('Failed to fetch leaves. Please try again.')
  } finally {
    loading.value = false
  }
}

const approveLeave = async (leaveId) => {
  if (!confirm('Are you sure you want to approve this leave request?')) return

  submitting.value = true
  try {
    let endpoint
    if (props.userRole === 'admin') {
      endpoint = `/api/admin/leaves/${leaveId}/approve`
    } else {
      endpoint = `/api/manager/leaves/${leaveId}/approve`
    }

    console.log('Approving leave at:', endpoint)
    
    // Send the required data for approval
    await axios.post(endpoint, {
      status: 'approved',
      manager_notes: 'Leave approved by admin'
    })
    
    alert('Leave approved successfully!')
    await refreshData()
  } catch (error) {
    console.error('❌ Error approving leave:', error)
    if (error.response?.status === 422) {
      alert('Validation error: ' + (error.response.data.message || 'Invalid data'))
    } else {
      alert('Failed to approve leave. Please try again.')
    }
  } finally {
    submitting.value = false
  }
}

const rejectLeave = async (leaveId) => {
  if (!confirm('Are you sure you want to reject this leave request?')) return

  submitting.value = true
  try {
    let endpoint
    if (props.userRole === 'admin') {
      endpoint = `/api/admin/leaves/${leaveId}/reject`
    } else {
      endpoint = `/api/manager/leaves/${leaveId}/reject`
    }

    console.log('Rejecting leave at:', endpoint)
    await axios.post(endpoint)
    alert('Leave rejected successfully!')
    await refreshData()
  } catch (error) {
    console.error('❌ Error rejecting leave:', error)
    alert('Failed to reject leave. Please try again.')
  } finally {
    submitting.value = false
  }
}

const viewLeaveDetails = (leave) => {
  // You can implement a modal or navigation to view leave details
  console.log('View leave details:', leave)
  // For now, just show an alert with basic info
  alert(`
Leave Details:
Employee: ${getEmployeeName(leave)}
Type: ${formatLeaveType(leave.leave_type || leave.type)}
Dates: ${formatDate(leave.start_date)} to ${formatDate(leave.end_date)}
Days: ${leave.total_days || calculateDays(leave.start_date, leave.end_date)}
Reason: ${leave.reason || 'N/A'}
Status: ${formatStatus(leave.status)}
  `)
}

const refreshData = async () => {
  await fetchLeaves()
  currentPage.value = 1
}

// Helper functions
const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatLeaveType = (type) => {
  if (!type) return 'N/A'
  return type.charAt(0).toUpperCase() + type.slice(1).replace(/_/g, ' ')
}

const formatStatus = (status) => {
  if (!status) return 'N/A'
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const getEmployeeName = (leave) => {
  // Try multiple possible data structures
  if (leave.employee?.user?.name) {
    return leave.employee.user.name
  } else if (leave.employee?.name) {
    return leave.employee.name
  } else if (leave.employee?.full_name && leave.employee.full_name !== 'N/A') {
    return leave.employee.full_name
  } else if (leave.employee?.user?.first_name) {
    return `${leave.employee.user.first_name} ${leave.employee.user.last_name || ''}`.trim()
  } else if (leave.employee?.first_name) {
    return `${leave.employee.first_name} ${leave.employee.last_name || ''}`.trim()
  } else if (leave.employee_name) {
    return leave.employee_name
  }
  return 'Unknown Employee'
}

const calculateDays = (startDate, endDate) => {
  if (!startDate || !endDate) return 'N/A'
  const start = new Date(startDate)
  const end = new Date(endDate)
  const diffTime = Math.abs(end - start)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1
  return diffDays
}

const calculateDaysRemaining = (endDate) => {
  if (!endDate) return 0
  
  const today = new Date()
  const end = new Date(endDate)
  
  // If end date is in the past, return 0
  if (end < today) return 0
  
  // Calculate whole days remaining including today
  const todayStart = new Date(today.getFullYear(), today.getMonth(), today.getDate())
  const endStart = new Date(end.getFullYear(), end.getMonth(), end.getDate())
  
  // Add 1 to include today as a day
  const diffDays = Math.floor((endStart - todayStart) / (1000 * 60 * 60 * 24)) + 1
  
  return Math.max(diffDays, 0)
}

const isOnLeaveToday = (startDate, endDate) => {
  if (!startDate || !endDate) return false
  
  const today = new Date()
  const todayStart = new Date(today.getFullYear(), today.getMonth(), today.getDate())
  
  const start = new Date(startDate)
  const startStart = new Date(start.getFullYear(), start.getMonth(), start.getDate())
  
  const end = new Date(endDate)
  const endStart = new Date(end.getFullYear(), end.getMonth(), end.getDate())
  
  return todayStart >= startStart && todayStart <= endStart
}

const truncateReason = (reason) => {
  if (!reason) return '—'
  return reason.length > 40 ? reason.substring(0, 40) + '...' : reason
}

const filteredLeaves = computed(() => {
  console.log('Filtering leaves:', {
    total: leaves.value.length,
    statusFilter: statusFilter.value,
    search: search.value
  })

  const results = leaves.value.filter((leave) => {
    const employeeName = getEmployeeName(leave).toLowerCase()
    const matchesSearch = search.value === '' || employeeName.includes(search.value.toLowerCase())
    const matchesStatus = statusFilter.value === 'all' || 
                         leave.status?.toLowerCase() === statusFilter.value.toLowerCase()
    
    return matchesSearch && matchesStatus
  })

  console.log('Filtered results:', results.length, 'items')
  return results
})

const paginatedLeaves = computed(() => {
  const start = (currentPage.value - 1) * perPage.value
  return filteredLeaves.value.slice(start, start + perPage.value)
})

const totalPages = computed(() => {
  return Math.ceil(filteredLeaves.value.length / perPage.value)
})

const getStatusClass = (status) => {
  if (!status) return 'neutral'
  const statusMap = {
    pending: 'warning',
    approved: 'success',
    rejected: 'danger',
    cancelled: 'neutral'
  }
  return statusMap[status.toLowerCase()] || 'neutral'
}

const getAvatarColor = (id) => {
  // Generate consistent colors based on ID
  const colors = ['#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#6366f1']
  const index = (typeof id === 'number' ? id : String(id).length) % colors.length
  return colors[index]
}

const prevPage = () => {
  if (currentPage.value > 1) currentPage.value--
}

const nextPage = () => {
  if (currentPage.value < totalPages.value) currentPage.value++
}

onMounted(() => {
  if (props.userRole === 'admin') {
    fetchBusinesses()
  }
  fetchLeaves()
})
</script>

<style scoped>
/* ── Base ──────────────────────────────────────────── */
.leave-management {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
}

.management-main {
  max-width: 1400px;
  margin: 0 auto;
  padding: 1.5rem 2rem 3rem;
}

/* ── Header Card ─────────────────────────────────── */
.dashboard-header-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem 1.75rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0;
  margin-bottom: 1.25rem;
  position: relative;
  overflow: hidden;
}

.header-card-accent {
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
}

.header-inner {
  display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; flex-wrap: wrap;
}

.user-greeting {
  display: flex; align-items: center; gap: 2rem; flex: 1; flex-wrap: wrap;
}

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
.role-meta { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.125rem; flex-wrap: wrap; }

.role-badge {
  background: #f0fdf4; border: 1px solid #bbf7d0;
  padding: 0.125rem 0.6rem; border-radius: 8px;
  font-size: 0.7rem; font-weight: 600; color: #166534;
}

.month-badge {
  background: #f1f5f9; border: 1px solid #e2e8f0;
  padding: 0.125rem 0.6rem; border-radius: 8px;
  font-size: 0.7rem; font-weight: 600; color: #475569;
}

/* Controls */
.header-controls {
  display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;
}

.select-wrapper { position: relative; }

.filter-select {
  padding: 0.45rem 2rem 0.45rem 0.875rem; border: 1px solid #e2e8f0;
  border-radius: 8px; background: #f8fafc; color: #334155;
  font-size: 0.875rem; font-weight: 500; cursor: pointer;
  appearance: none; transition: all 0.2s; font-family: inherit;
  min-width: 180px;
}
.filter-select:focus {
  outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

.select-caret {
  position: absolute; right: 0.6rem; top: 50%;
  transform: translateY(-50%); pointer-events: none;
  color: #94a3b8; font-size: 0.7rem;
}

.btn-outline {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.45rem 0.9rem; background: white; border: 1px solid #e2e8f0;
  color: #475569; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s;
}
.btn-outline:hover:not(:disabled) {
  background: #f8fafc; border-color: #cbd5e1; color: #1e293b;
}
.btn-outline:disabled { opacity: 0.5; cursor: not-allowed; }

.spinning { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* Date badge */
.date-badge {
  background: #f8fafc; border: 1px solid #e2e8f0;
  border-radius: 12px; padding: 0.75rem 1.125rem; min-width: 130px; flex-shrink: 0;
}

.date-content {
  display: flex; align-items: center; gap: 0.75rem;
}

.stats-primary { font-size: 1.5rem; font-weight: 700; color: #334155; line-height: 1; }

.date-details { display: flex; flex-direction: column; }
.stats-label { font-size: 0.7rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.02em; }
.stats-total { font-size: 0.7rem; color: #94a3b8; }

/* ── Stats Grid ───────────────────────────────────────── */
.stats-grid {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.25rem; margin-bottom: 1.75rem;
}

.stat-card {
  background: white; border-radius: 16px; padding: 1.4rem 1.5rem;
  display: flex; align-items: center; gap: 1.1rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;
  transition: transform 0.2s, box-shadow 0.2s;
}
.stat-card:hover {
  transform: translateY(-2px); box-shadow: 0 8px 20px -4px rgba(0,0,0,0.08);
}

.stat-icon-wrap { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.stat-info { display: flex; flex-direction: column; min-width: 0; }
.stat-label { font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; }
.stat-number { font-size: 2rem; font-weight: 800; color: #0f172a; line-height: 1.1; }
.stat-trend { font-size: 0.78rem; color: #64748b; margin-top: 0.2rem; }

.trend-up   { color: #10b981; font-weight: 700; }
.trend-down { color: #ef4444; font-weight: 700; }
.trend-warn { color: #f59e0b; font-weight: 700; }

/* ── Section Containers ───────────────────────────────── */
.section-container {
  background: white; border-radius: 16px; border: 1px solid #e2e8f0;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 1.75rem;
}

.card-header {
  padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9;
  display: flex; justify-content: space-between; align-items: center; background: #fcfcfc;
}
.card-header h3 { margin: 0; font-size: 1rem; font-weight: 700; color: #334155; display: flex; align-items: center; }

.count-badge {
  background: #f1f5f9; color: #475569; padding: 0.2rem 0.65rem;
  border-radius: 20px; font-size: 0.72rem; font-weight: 700;
}

/* On Leave Grid */
.on-leave-grid {
  display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; padding: 1.5rem;
}

.employee-card {
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.125rem;
  transition: transform 0.18s, box-shadow 0.18s;
}
.employee-card:hover {
  transform: translateY(-2px); box-shadow: 0 6px 20px -4px rgba(0,0,0,0.1); border-color: #cbd5e1;
}

.emp-card-head {
  display: flex; align-items: flex-start; gap: 0.75rem;
  padding-bottom: 0.875rem; margin-bottom: 0.875rem; border-bottom: 1px solid #e2e8f0;
}

.emp-avatar {
  width: 42px; height: 42px; border-radius: 10px;
  background: #3b82f6; display: flex; align-items: center; justify-content: center;
  color: white; font-weight: 700; font-size: 0.85rem; flex-shrink: 0;
}

.emp-card-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 0.15rem; }
.emp-card-name { font-size: 0.9rem; font-weight: 700; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.emp-card-meta { display: flex; gap: 0.5rem; }
.emp-card-dept { font-size: 0.7rem; color: #64748b; background: #f1f5f9; padding: 0.05rem 0.4rem; border-radius: 4px; }

.on-leave-details { display: flex; flex-direction: column; gap: 0.35rem; }
.detail-item {
  display: flex; justify-content: space-between; font-size: 0.8rem;
  padding: 0.2rem 0; border-bottom: 1px dashed #e2e8f0;
}
.detail-item:last-child { border-bottom: none; }
.detail-item.highlight { color: #334155; font-weight: 600; }
.detail-label { color: #64748b; }
.detail-value { color: #1e293b; font-weight: 500; }

/* ── Controls Bar (Filters) ──────────────────────────── */
.controls-bar {
  display: flex; justify-content: space-between; align-items: flex-end;
  background: white; border-radius: 16px; border: 1px solid #e2e8f0;
  padding: 1.5rem; margin-bottom: 1.25rem; gap: 1rem; flex-wrap: wrap;
}

.filters-row { display: flex; gap: 0.875rem; flex-wrap: wrap; }

.filter-group { display: flex; flex-direction: column; gap: 0.3rem; }
.filter-group label { font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }

.search-wrapper { position: relative; }
.search-icon {
  position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%);
  color: #94a3b8; pointer-events: none;
}
.filter-input {
  padding: 0.45rem 0.75rem 0.45rem 2.2rem; border: 1px solid #e2e8f0;
  border-radius: 8px; background: #f8fafc; color: #334155;
  font-size: 0.82rem; font-weight: 500; width: 240px; font-family: inherit;
  transition: all 0.2s;
}
.filter-input:focus {
  outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

.filter-select {
  padding: 0.45rem 2rem 0.45rem 0.875rem; border: 1px solid #e2e8f0;
  border-radius: 8px; background: #f8fafc; color: #334155;
  font-size: 0.82rem; font-weight: 500; cursor: pointer;
  appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 0.6rem center;
}
.filter-select:focus {
  outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

.records-count {
  font-size: 0.78rem; font-weight: 700; color: #64748b;
  background: #f1f5f9; padding: 0.2rem 0.7rem; border-radius: 9999px; white-space: nowrap;
}

/* ── Table Section ──────────────────────────────────── */
.table-section {
  background: white; border-radius: 16px; border: 1px solid #e2e8f0;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05); overflow: hidden;
}

/* Table Header */
.list-header {
  display: grid;
  grid-template-columns: 2fr 1.5fr 1fr 1.2fr 1.8fr 0.7fr 1.5fr 1fr 1fr;
  padding: 1rem 1.5rem;
  background: #f8fafc; border-bottom: 1px solid #e2e8f0;
  font-size: 0.7rem; font-weight: 700; color: #64748b;
  text-transform: uppercase; letter-spacing: 0.05em;
  gap: 1rem;
}

/* Table Rows */
.list-row {
  display: grid;
  grid-template-columns: 2fr 1.5fr 1fr 1.2fr 1.8fr 0.7fr 1.5fr 1fr 1fr;
  padding: 0.875rem 1.5rem;
  align-items: center; gap: 1rem;
  border-bottom: 1px solid #f1f5f9;
  background: white;
  cursor: pointer;
  transition: background 0.15s;
}
.list-row:last-child { border-bottom: none; }
.list-row:hover { background: #f8fafc; }

/* Column Styles */
.col-employee .emp-cell {
  display: flex; align-items: center; gap: 0.65rem;
}

.emp-avatar-sm {
  width: 36px; height: 36px; border-radius: 9px;
  display: flex; align-items: center; justify-content: center;
  color: white; font-weight: 700; font-size: 0.78rem; flex-shrink: 0;
}

.emp-name { font-weight: 600; color: #1e293b; font-size: 0.9rem; }

.dept-tag {
  display: inline-block; padding: 0.2rem 0.55rem;
  background: #f1f5f9; color: #475569; border-radius: 5px;
  font-size: 0.72rem; font-weight: 600; border: 1px solid #e2e8f0;
}

.dates-wrap {
  display: flex; align-items: center; gap: 0.4rem;
  font-size: 0.85rem;
}
.date-arrow { color: #94a3b8; font-size: 0.8rem; }
.date-start, .date-end { color: #334155; }

.font-mono {
  font-family: 'SFMono-Regular', Consolas, monospace;
  font-size: 0.82rem;
}

.col-reason {
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  color: #64748b; font-size: 0.85rem;
}

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
.status-badge.neutral { background: #f1f5f9; color: #64748b; }

/* Action Buttons */
.action-group {
  display: flex; gap: 0.3rem;
}

.action-btn {
  width: 32px; height: 32px; border-radius: 6px;
  border: 1px solid #e2e8f0; background: white; color: #64748b;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.15s;
}
.action-btn.approve:hover:not(:disabled) {
  background: #ecfdf5; color: #10b981; border-color: #bbf7d0;
}
.action-btn.reject:hover:not(:disabled) {
  background: #fee2e2; color: #ef4444; border-color: #fecaca;
}
.action-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.text-muted { color: #94a3b8; font-size: 0.8rem; }

/* ── Pagination ──────────────────────────────────────── */
.pagination-bar {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; background: #fcfcfc;
}

.pagination-info { font-size: 0.82rem; color: #64748b; }
.pagination-info strong { color: #1e293b; font-weight: 700; }
.pagination-controls { display: flex; gap: 0.5rem; }

.page-btn {
  display: flex; align-items: center; gap: 0.3rem;
  padding: 0.35rem 0.875rem; background: white; border: 1px solid #e2e8f0;
  border-radius: 6px; font-size: 0.78rem; font-weight: 600; color: #475569;
  cursor: pointer; transition: all 0.15s;
}
.page-btn:hover:not(:disabled) { border-color: #a5b4fc; color: #4f46e5; background: #eff6ff; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }

/* ── States ──────────────────────────────────────────── */
.spinner {
  width: 40px; height: 40px; border: 3px solid #e2e8f0; border-top-color: #6366f1;
  border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;
}

.empty-state {
  text-align: center; padding: 4rem 2rem;
  display: flex; flex-direction: column; align-items: center; gap: 0.875rem; color: #94a3b8;
}
.empty-state p { margin: 0; font-size: 0.875rem; color: #64748b; }
.empty-state h3 { margin: 0; font-size: 1.1rem; color: #1e293b; }

/* ── Responsive ──────────────────────────────────────── */
@media (max-width: 1200px) {
  .list-header, .list-row {
    grid-template-columns: 2fr 1.2fr 1fr 1fr 1.8fr 0.7fr 1.5fr 1fr 0.8fr;
    padding: 0.875rem 1rem;
  }
}

@media (max-width: 1024px) {
  .management-main { padding: 1.5rem; }
  .list-header { display: none; }
  .list-row {
    display: grid;
    grid-template-columns: 1fr auto;
    grid-template-areas: 
      "employee status"
      "business business"
      "type dates"
      "department days"
      "reason reason"
      "actions actions";
    padding: 1rem;
    gap: 0.5rem;
  }
  .col-employee { grid-area: employee; }
  .col-business { grid-area: business; }
  .col-department { grid-area: department; }
  .col-type { grid-area: type; }
  .col-dates { grid-area: dates; }
  .col-days { grid-area: days; text-align: right; }
  .col-reason { grid-area: reason; }
  .col-status { grid-area: status; }
  .col-actions { grid-area: actions; }
  .action-group { justify-content: flex-end; }
}

@media (max-width: 768px) {
  .management-main { padding: 1rem; }
  .header-inner { flex-direction: column; align-items: flex-start; }
  .user-greeting { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .header-controls { width: 100%; }
  .filter-select { min-width: 0; width: 100%; }
  .greeting { font-size: 1.25rem; }
  .date-badge { align-self: flex-start; }
  .stats-grid { grid-template-columns: 1fr 1fr; }
  .controls-bar { flex-direction: column; align-items: stretch; }
  .filters-row { width: 100%; }
  .search-wrapper { width: 100%; }
  .filter-input { width: 100%; }
  .filter-select { width: 100%; }
  .records-count { align-self: flex-end; }
  .pagination-bar { flex-direction: column; gap: 0.75rem; align-items: flex-start; }
}

@media (max-width: 480px) {
  .stats-grid { grid-template-columns: 1fr; }
  .on-leave-grid { grid-template-columns: 1fr; }
  .pagination-controls { width: 100%; }
  .page-btn { flex: 1; justify-content: center; }
}
</style>