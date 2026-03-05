<template>
  <div class="demo-requests-management">
    <!-- ── Main (header card lives inside here) ───────── -->
    <div class="management-main">

      <!-- ── Header Card ─────────────────────────────── -->
      <div class="management-header-card">
        <div class="header-card-accent"></div>
        <div class="header-inner">

          <!-- Brand + Title -->
          <div class="user-greeting">
            <div class="avatar-section">
              <div class="avatar">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                  <line x1="8" y1="21" x2="16" y2="21"></line>
                  <line x1="12" y1="17" x2="12" y2="21"></line>
                  <path d="M7 7l5 5 5-5"></path>
                </svg>
              </div>
              <div class="user-info">
                <h1 class="greeting">Demo Requests</h1>
                <p class="subtitle">Manage and respond to demo requests from potential customers</p>
                <div class="role-meta">
                  <span class="role-badge">Admin View</span>
                  <span class="month-badge">{{ stats.total }} Total Requests</span>
                </div>
              </div>
            </div>

            <!-- Controls -->
            <div class="header-controls">
              <button @click="exportToCSV" class="btn-icon" title="Export to CSV">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                  <polyline points="7 10 12 15 17 10"></polyline>
                  <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
              </button>

              <button @click="loadDemoRequests" class="btn-icon" title="Refresh Data" :disabled="loading">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :class="{ 'spinning': loading }">
                  <path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path>
                  <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
                </svg>
              </button>
            </div>
          </div>

          <!-- Stats badge -->
          <div class="view-badge">
            <div class="view-content">
              <span class="view-primary">{{ filteredRequests.length }}</span>
              <div class="view-details">
                <span class="view-label">Filtered</span>
                <span class="view-total">of {{ stats.total }}</span>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Stats Cards -->
      <div class="stats-grid">
        <div class="stat-card" style="--accent:#f59e0b;">
          <div class="stat-icon-wrap" style="background:rgba(245,158,11,0.1);">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
          </div>
          <div class="stat-info">
            <span class="stat-label">Pending</span>
            <div class="stat-number">{{ stats.pending }}</div>
            <div class="stat-trend">Awaiting response</div>
          </div>
        </div>

        <div class="stat-card" style="--accent:#3b82f6;">
          <div class="stat-icon-wrap" style="background:rgba(59,130,246,0.1);">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2">
              <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"></path>
            </svg>
          </div>
          <div class="stat-info">
            <span class="stat-label">Contacted</span>
            <div class="stat-number">{{ stats.contacted }}</div>
            <div class="stat-trend">In progress</div>
          </div>
        </div>

        <div class="stat-card" style="--accent:#10b981;">
          <div class="stat-icon-wrap" style="background:rgba(16,185,129,0.1);">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
          </div>
          <div class="stat-info">
            <span class="stat-label">Completed</span>
            <div class="stat-number">{{ stats.completed }}</div>
            <div class="stat-trend">Demo delivered</div>
          </div>
        </div>

        <div class="stat-card" style="--accent:#8b5cf6;">
          <div class="stat-icon-wrap" style="background:rgba(139,92,246,0.1);">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2">
              <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
              <line x1="8" y1="21" x2="16" y2="21"></line>
              <line x1="12" y1="17" x2="12" y2="21"></line>
            </svg>
          </div>
          <div class="stat-info">
            <span class="stat-label">Total Requests</span>
            <div class="stat-number">{{ stats.total }}</div>
            <div class="stat-trend">All time</div>
          </div>
        </div>
      </div>

      <!-- Filters Card -->
      <div class="filters-card">
        <div class="filters-header">
          <h3>Filter Demo Requests</h3>
          <span class="filter-count">{{ filteredRequests.length }} records</span>
        </div>
        <div class="filters-grid">
          <!-- Status Filter -->
          <div class="filter-item">
            <label class="filter-label">Status</label>
            <div class="select-wrapper">
              <select v-model="filters.status" @change="applyFilters" class="filter-select">
                <option value="">All Statuses</option>
                <option value="pending">Pending</option>
                <option value="contacted">Contacted</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
              <svg class="select-chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </div>
          </div>

          <!-- Company Size Filter -->
          <div class="filter-item">
            <label class="filter-label">Company Size</label>
            <div class="select-wrapper">
              <select v-model="filters.employeeCount" @change="applyFilters" class="filter-select">
                <option value="">All Sizes</option>
                <option value="1-10">1-10 employees</option>
                <option value="11-50">11-50 employees</option>
                <option value="51-200">51-200 employees</option>
                <option value="201-500">201-500 employees</option>
                <option value="500+">500+ employees</option>
              </select>
              <svg class="select-chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </div>
          </div>

          <!-- Search -->
          <div class="search-wrapper full-width">
            <svg class="search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input
              v-model="filters.search"
              @input="applyFilters"
              type="text"
              placeholder="Search by name, email, or company..."
              class="filter-search"
            />
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="state-banner loading">
        <div class="spinner"></div>
        <span>Loading demo requests...</span>
      </div>

      <!-- Demo Requests Table -->
      <div v-else-if="filteredRequests.length > 0" class="content-card">
        <div class="card-header">
          <h3>Demo Requests</h3>
          <span class="count-badge">{{ filteredRequests.length }}</span>
        </div>

        <div class="table-responsive">
          <table class="data-table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Contact</th>
                <th>Company</th>
                <th>Size</th>
                <th>Status</th>
                <th class="text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="request in paginatedRequests" :key="request.id">
                <td>
                  <div class="date-cell">
                    <span class="date">{{ formatDate(request.created_at) }}</span>
                    <span class="time">{{ formatTime(request.created_at) }}</span>
                  </div>
                </td>
                <td>
                  <div class="contact-cell">
                    <span class="contact-name">{{ request.first_name }} {{ request.last_name }}</span>
                    <span class="contact-email">{{ request.email }}</span>
                    <span class="contact-phone">{{ request.phone }}</span>
                  </div>
                </td>
                <td>
                  <span class="company-name">{{ request.company_name }}</span>
                </td>
                <td>
                  <span class="size-badge">{{ request.employee_count || 'Not specified' }}</span>
                </td>
                <td>
                  <span :class="['status-badge', request.status]">
                    {{ request.status }}
                  </span>
                </td>
                <td class="text-right">
                  <div class="action-buttons">
                    <button @click="viewDetails(request)" class="btn-icon-small" title="View Details">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="3"></circle>
                        <path d="M22 12c-2.667 4.667-6 7-10 7s-7.333-2.333-10-7c2.667-4.667 6-7 10-7s7.333 2.333 10 7z"></path>
                      </svg>
                    </button>
                    <button @click="updateStatus(request)" class="btn-icon-small" title="Update Status">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 14.66V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h5.34"></path>
                        <polygon points="18 2 22 6 12 16 8 16 8 12 18 2"></polygon>
                      </svg>
                    </button>
                    <button @click="sendEmail(request)" class="btn-icon-small" title="Send Email">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                      </svg>
                    </button>
                    <button @click="deleteRequest(request)" class="btn-icon-small btn-danger" title="Delete">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6"></polyline>
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="pagination-bar">
          <span class="pagination-info">
            Page {{ currentPage }} of {{ totalPages }} • {{ filteredRequests.length }} total records
          </span>
          <div class="pagination-controls">
            <button
              @click="currentPage--"
              :disabled="currentPage === 1"
              class="btn-icon-small"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="15 18 9 12 15 6"></polyline>
              </svg>
            </button>
            <span class="page-indicator">{{ currentPage }} / {{ totalPages }}</span>
            <button
              @click="currentPage++"
              :disabled="currentPage === totalPages"
              class="btn-icon-small"
            >
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="empty-state">
        <div class="empty-icon">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.5">
            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
            <line x1="8" y1="21" x2="16" y2="21"></line>
            <line x1="12" y1="17" x2="12" y2="21"></line>
          </svg>
        </div>
        <h3>No Demo Requests Found</h3>
        <p>{{ filters.search || filters.status || filters.employeeCount ? 'Try adjusting your filters' : 'Demo requests will appear here' }}</p>
      </div>
    </div>

    <!-- Details Modal -->
    <div v-if="selectedRequest" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content">
        <div class="modal-header">
          <h2>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
              <circle cx="12" cy="12" r="3"></circle>
              <path d="M22 12c-2.667 4.667-6 7-10 7s-7.333-2.333-10-7c2.667-4.667 6-7 10-7s7.333 2.333 10 7z"></path>
            </svg>
            Demo Request Details
          </h2>
          <button @click="closeModal" class="close-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        <div class="modal-body">
          <!-- Contact Information -->
          <div class="detail-section">
            <h3>Contact Information</h3>
            <div class="detail-grid">
              <div class="detail-item">
                <span class="detail-label">Name:</span>
                <span class="detail-value">{{ selectedRequest.first_name }} {{ selectedRequest.last_name }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Email:</span>
                <a :href="`mailto:${selectedRequest.email}`" class="detail-link">{{ selectedRequest.email }}</a>
              </div>
              <div class="detail-item">
                <span class="detail-label">Phone:</span>
                <a :href="`tel:${selectedRequest.phone}`" class="detail-link">{{ selectedRequest.phone }}</a>
              </div>
            </div>
          </div>

          <!-- Company Information -->
          <div class="detail-section">
            <h3>Company Information</h3>
            <div class="detail-grid">
              <div class="detail-item">
                <span class="detail-label">Company Name:</span>
                <span class="detail-value">{{ selectedRequest.company_name }}</span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Number of Employees:</span>
                <span class="detail-value">{{ selectedRequest.employee_count || 'Not specified' }}</span>
              </div>
            </div>
          </div>

          <!-- Additional Information -->
          <div v-if="selectedRequest.message" class="detail-section">
            <h3>Additional Information</h3>
            <div class="message-box">
              {{ selectedRequest.message }}
            </div>
          </div>

          <!-- Request Status -->
          <div class="detail-section">
            <h3>Request Status</h3>
            <div class="detail-grid">
              <div class="detail-item">
                <span class="detail-label">Current Status:</span>
                <span :class="['status-badge', selectedRequest.status]">
                  {{ selectedRequest.status }}
                </span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Requested On:</span>
                <span class="detail-value">{{ formatDateTime(selectedRequest.created_at) }}</span>
              </div>
              <div v-if="selectedRequest.contacted_at" class="detail-item">
                <span class="detail-label">Contacted On:</span>
                <span class="detail-value">{{ formatDateTime(selectedRequest.contacted_at) }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeModal" class="btn-secondary">Close</button>
          <button @click="updateStatus(selectedRequest)" class="btn-accent">Update Status</button>
          <button @click="sendEmail(selectedRequest)" class="btn-accent">Send Email</button>
        </div>
      </div>
    </div>

    <!-- Status Update Modal -->
    <div v-if="showStatusModal" class="modal-overlay" @click.self="closeStatusModal">
      <div class="modal-content small">
        <div class="modal-header">
          <h2>Update Status</h2>
          <button @click="closeStatusModal" class="close-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">Select New Status:</label>
            <div class="select-wrapper">
              <select v-model="statusUpdate.status" class="form-select">
                <option value="pending">Pending</option>
                <option value="contacted">Contacted</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
              </select>
              <svg class="select-chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeStatusModal" class="btn-secondary">Cancel</button>
          <button @click="saveStatusUpdate" class="btn-accent" :disabled="updating">
            <svg v-if="updating" class="spinner-small" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="32" stroke-dashoffset="32">
                <animate attributeName="stroke-dashoffset" dur="1.5s" values="32;0" repeatCount="indefinite"/>
              </circle>
            </svg>
            <span v-else>Update Status</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click.self="closeDeleteModal">
      <div class="modal-content small">
        <div class="modal-header">
          <h2>Confirm Delete</h2>
          <button @click="closeDeleteModal" class="close-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        <div class="modal-body">
          <div class="delete-warning">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <p>Are you sure you want to delete this demo request from <strong>{{ deleteTarget?.company_name }}</strong>?</p>
            <p class="warning-text">This action cannot be undone.</p>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeDeleteModal" class="btn-secondary">Cancel</button>
          <button @click="confirmDelete" class="btn-danger" :disabled="deleting">
            <svg v-if="deleting" class="spinner-small" viewBox="0 0 24 24">
              <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2" stroke-dasharray="32" stroke-dashoffset="32">
                <animate attributeName="stroke-dashoffset" dur="1.5s" values="32;0" repeatCount="indefinite"/>
              </circle>
            </svg>
            <span v-else>Delete</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

const demoRequests = ref([])
const loading = ref(false)
const selectedRequest = ref(null)
const showStatusModal = ref(false)
const showDeleteModal = ref(false)
const deleteTarget = ref(null)
const updating = ref(false)
const deleting = ref(false)
const currentPage = ref(1)
const itemsPerPage = ref(20)

const filters = ref({
  status: '',
  employeeCount: '',
  search: ''
})

const statusUpdate = ref({
  status: ''
})

const stats = computed(() => {
  return {
    pending: demoRequests.value.filter(r => r.status === 'pending').length,
    contacted: demoRequests.value.filter(r => r.status === 'contacted').length,
    completed: demoRequests.value.filter(r => r.status === 'completed').length,
    total: demoRequests.value.length
  }
})

const filteredRequests = computed(() => {
  let filtered = [...demoRequests.value]

  if (filters.value.status) {
    filtered = filtered.filter(r => r.status === filters.value.status)
  }

  if (filters.value.employeeCount) {
    filtered = filtered.filter(r => r.employee_count === filters.value.employeeCount)
  }

  if (filters.value.search) {
    const search = filters.value.search.toLowerCase()
    filtered = filtered.filter(r =>
      r.first_name.toLowerCase().includes(search) ||
      r.last_name.toLowerCase().includes(search) ||
      r.email.toLowerCase().includes(search) ||
      r.company_name.toLowerCase().includes(search) ||
      r.phone.includes(search)
    )
  }

  return filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
})

const totalPages = computed(() => {
  return Math.ceil(filteredRequests.value.length / itemsPerPage.value) || 1
})

const paginatedRequests = computed(() => {
  const start = (currentPage.value - 1) * itemsPerPage.value
  const end = start + itemsPerPage.value
  return filteredRequests.value.slice(start, end)
})

const loadDemoRequests = async () => {
  loading.value = true
  try {
    const response = await axios.get('/api/admin/demo-requests')
    demoRequests.value = response.data.data || response.data
    console.log('✅ Demo requests loaded:', demoRequests.value.length)
  } catch (error) {
    console.error('❌ Failed to load demo requests:', error)
    alert('Failed to load demo requests. Please try again.')
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  currentPage.value = 1
}

const viewDetails = (request) => {
  selectedRequest.value = request
}

const closeModal = () => {
  selectedRequest.value = null
}

const updateStatus = (request) => {
  selectedRequest.value = request
  statusUpdate.value.status = request.status
  showStatusModal.value = true
}

const closeStatusModal = () => {
  showStatusModal.value = false
  statusUpdate.value = { status: '' }
}

const saveStatusUpdate = async () => {
  updating.value = true
  try {
    await axios.put(`/api/admin/demo-requests/${selectedRequest.value.id}`, {
      status: statusUpdate.value.status
    })
    
    const index = demoRequests.value.findIndex(r => r.id === selectedRequest.value.id)
    if (index !== -1) {
      demoRequests.value[index].status = statusUpdate.value.status
      if (statusUpdate.value.status === 'contacted') {
        demoRequests.value[index].contacted_at = new Date().toISOString()
      }
    }
    
    closeStatusModal()
    closeModal()
    alert('Status updated successfully!')
  } catch (error) {
    console.error('❌ Failed to update status:', error)
    alert('Failed to update status. Please try again.')
  } finally {
    updating.value = false
  }
}

const deleteRequest = (request) => {
  deleteTarget.value = request
  showDeleteModal.value = true
}

const closeDeleteModal = () => {
  showDeleteModal.value = false
  deleteTarget.value = null
}

const confirmDelete = async () => {
  deleting.value = true
  try {
    await axios.delete(`/api/admin/demo-requests/${deleteTarget.value.id}`)
    
    const index = demoRequests.value.findIndex(r => r.id === deleteTarget.value.id)
    if (index !== -1) {
      demoRequests.value.splice(index, 1)
    }
    
    closeDeleteModal()
    alert('Demo request deleted successfully!')
  } catch (error) {
    console.error('❌ Failed to delete demo request:', error)
    alert('Failed to delete demo request. Please try again.')
  } finally {
    deleting.value = false
  }
}

const sendEmail = (request) => {
  const subject = `Demo Request - ${request.company_name}`
  const body = `Hi ${request.first_name},\n\nThank you for your interest in Archangel Payroll.\n\n`
  window.location.href = `mailto:${request.email}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`
}

const exportToCSV = () => {
  const headers = ['Date', 'First Name', 'Last Name', 'Email', 'Phone', 'Company', 'Employee Count', 'Status', 'Message']
  const rows = filteredRequests.value.map(r => [
    formatDate(r.created_at),
    r.first_name,
    r.last_name,
    r.email,
    r.phone,
    r.company_name,
    r.employee_count || '',
    r.status,
    r.message || ''
  ])

  let csvContent = headers.join(',') + '\n'
  rows.forEach(row => {
    csvContent += row.map(cell => `"${cell}"`).join(',') + '\n'
  })

  const blob = new Blob([csvContent], { type: 'text/csv' })
  const url = window.URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url
  a.download = `demo-requests-${new Date().toISOString().split('T')[0]}.csv`
  a.click()
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatTime = (date) => {
  return new Date(date).toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatDateTime = (date) => {
  return new Date(date).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(() => {
  loadDemoRequests()
})
</script>

<style scoped>
/* ── Base ─────────────────────────────────────────────── */
.demo-requests-management {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
}

/* ── Main wrapper ─────────────────────────────────────── */
.management-main {
  max-width: 1400px;
  margin: 0 auto;
  padding: 1.5rem 2rem 3rem;
}

/* ── Header Card ──────────────────────────────────────── */
.management-header-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem 1.75rem;
  box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.03);
  border: 1px solid #e2e8f0;
  margin-bottom: 1.25rem;
  position: relative;
  overflow: hidden;
}

.header-card-accent {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, #001f5b 0%, #002a7a 50%, #0040c1 100%);
}

.management-header-card::after {
  content: '';
  position: absolute;
  top: -20px;
  right: -20px;
  width: 160px;
  height: 160px;
  background: radial-gradient(circle, rgba(139, 92, 246, 0.05) 0%, transparent 70%);
  pointer-events: none;
}

.header-inner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.user-greeting {
  display: flex;
  align-items: center;
  gap: 2rem;
  flex: 1;
  flex-wrap: wrap;
}

/* Avatar */
.avatar-section {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.avatar {
  width: 52px;
  height: 52px;
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25);
  flex-shrink: 0;
}

.user-info {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.greeting {
  margin: 0;
  font-size: 1.375rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1.2;
}

.subtitle {
  margin: 0;
  color: #64748b;
  font-size: 0.875rem;
}

.role-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.125rem;
  flex-wrap: wrap;
}

.role-badge {
  background: #f5f3ff;
  border: 1px solid #ddd6fe;
  padding: 0.125rem 0.6rem;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 600;
  color: #6d28d9;
  display: inline-flex;
  align-items: center;
}

.month-badge {
  background: #ede9fe;
  border: 1px solid #ddd6fe;
  padding: 0.125rem 0.6rem;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 600;
  color: #7c3aed;
  display: inline-flex;
  align-items: center;
}

/* Controls */
.header-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

/* Buttons */
.btn-icon {
  width: 38px;
  height: 38px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  color: #475569;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
}

.btn-icon:hover:not(:disabled) {
  background: #f1f5f9;
  border-color: #cbd5e1;
  color: #1e293b;
}

.btn-icon:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-icon-small {
  width: 32px;
  height: 32px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  color: #475569;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon-small:hover:not(:disabled) {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.btn-icon-small.btn-danger:hover:not(:disabled) {
  background: #fef2f2;
  border-color: #ef4444;
  color: #ef4444;
}

.btn-icon-small:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-accent {
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
  color: white;
  border: none;
  padding: 0.55rem 1.1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
  white-space: nowrap;
}

.btn-accent:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(139, 92, 246, 0.4);
}

.btn-accent:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: white;
  border: 1px solid #e2e8f0;
  color: #475569;
  padding: 0.55rem 1.1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.btn-danger {
  background: #ef4444;
  color: white;
  border: none;
  padding: 0.55rem 1.1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-danger:hover:not(:disabled) {
  background: #dc2626;
  transform: translateY(-1px);
}

.btn-danger:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* View badge */
.view-badge {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0.75rem 1.125rem;
  min-width: 100px;
  flex-shrink: 0;
}

.view-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.view-primary {
  font-size: 1.5rem;
  font-weight: 700;
  color: #8b5cf6;
  line-height: 1;
}

.view-details {
  display: flex;
  flex-direction: column;
}

.view-label {
  font-size: 0.7rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.view-total {
  font-size: 0.7rem;
  color: #94a3b8;
}

/* ── Stats Grid ───────────────────────────────────────── */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.25rem;
  margin-bottom: 1.75rem;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 1.4rem 1.5rem;
  display: flex;
  align-items: center;
  gap: 1.1rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05);
  border: 1px solid #e2e8f0;
  position: relative;
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px -4px rgba(0,0,0,0.08);
  border-color: var(--accent);
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: var(--accent);
}

.stat-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-info {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.stat-label {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.stat-number {
  font-size: 2rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.1;
}

.stat-trend {
  font-size: 0.78rem;
  color: #64748b;
  margin-top: 0.2rem;
}

/* ── Filters Card ─────────────────────────────────────── */
.filters-card {
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05);
  padding: 1.25rem 1.5rem;
  margin-bottom: 1.75rem;
}

.filters-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.filters-header h3 {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: #334155;
}

.filter-count {
  font-size: 0.8rem;
  color: #64748b;
  background: #f1f5f9;
  padding: 0.2rem 0.6rem;
  border-radius: 12px;
}

.filters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.filter-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.filter-label {
  font-size: 0.8rem;
  font-weight: 600;
  color: #475569;
}

.select-wrapper {
  position: relative;
  width: 100%;
}

.filter-select {
  width: 100%;
  padding: 0.625rem 2rem 0.625rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
  background: white;
  cursor: pointer;
  appearance: none;
}

.filter-select:focus {
  outline: none;
  border-color: #8b5cf6;
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.select-chevron {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
}

/* Search */
.search-wrapper {
  position: relative;
}

.search-wrapper.full-width {
  grid-column: 1 / -1;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
}

.filter-search {
  width: 100%;
  padding: 0.625rem 1rem 0.625rem 2.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
  transition: all 0.2s;
}

.filter-search:focus {
  outline: none;
  border-color: #8b5cf6;
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

/* State Banners */
.state-banner {
  padding: 1.5rem;
  border-radius: 12px;
  text-align: center;
  margin-bottom: 1.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
}

.state-banner.loading {
  background: #f0f9ff;
  color: #0369a1;
  border: 1px solid #bae6fd;
}

.spinner {
  width: 28px;
  height: 28px;
  border: 3px solid #bae6fd;
  border-top-color: #0284c7;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.spinner-small {
  width: 16px;
  height: 16px;
  animation: spin 1s linear infinite;
}

/* ── Content Cards ────────────────────────────────────── */
.content-card {
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05);
  overflow: hidden;
  margin-bottom: 1.75rem;
}

.card-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fcfcfc;
}

.card-header h3 {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: #334155;
  display: flex;
  align-items: center;
}

.count-badge {
  background: #fef3c7;
  color: #92400e;
  padding: 0.2rem 0.65rem;
  border-radius: 20px;
  font-size: 0.72rem;
  font-weight: 700;
}

/* Table Styles */
.table-responsive {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;
}

.data-table thead th {
  background: #f8fafc;
  padding: 1rem 1rem;
  text-align: left;
  font-weight: 600;
  color: #475569;
  border-bottom: 2px solid #e2e8f0;
  white-space: nowrap;
}

.data-table tbody tr {
  border-bottom: 1px solid #f1f5f9;
  transition: background 0.15s;
}

.data-table tbody tr:hover {
  background: #f8fafc;
}

.data-table tbody td {
  padding: 1rem;
  color: #1e293b;
  vertical-align: middle;
}

/* Date Cell */
.date-cell {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.date {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.85rem;
}

.time {
  font-size: 0.7rem;
  color: #94a3b8;
}

/* Contact Cell */
.contact-cell {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.contact-name {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.9rem;
}

.contact-email {
  font-size: 0.75rem;
  color: #8b5cf6;
}

.contact-phone {
  font-size: 0.7rem;
  color: #64748b;
}

/* Company Name */
.company-name {
  font-weight: 600;
  color: #1e293b;
}

/* Size Badge */
.size-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: #f1f5f9;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 600;
  color: #475569;
}

/* Status Badges */
.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.status-badge.pending {
  background: #fef3c7;
  color: #f59e0b;
  border: 1px solid #fde68a;
}

.status-badge.contacted {
  background: #dbeafe;
  color: #3b82f6;
  border: 1px solid #bfdbfe;
}

.status-badge.completed {
  background: #d1fae5;
  color: #10b981;
  border: 1px solid #a7f3d0;
}

.status-badge.cancelled {
  background: #fee2e2;
  color: #ef4444;
  border: 1px solid #fecaca;
}

/* Action Buttons */
.action-buttons {
  display: flex;
  gap: 0.25rem;
  justify-content: flex-end;
}

.text-right {
  text-align: right;
}

/* Pagination */
.pagination-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.5rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
}

.pagination-info {
  font-size: 0.85rem;
  color: #64748b;
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.page-indicator {
  font-size: 0.85rem;
  font-weight: 600;
  color: #1e293b;
  min-width: 60px;
  text-align: center;
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
}

.empty-icon {
  width: 80px;
  height: 80px;
  margin: 0 auto 1.5rem;
  background: #f8fafc;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 2px dashed #e2e8f0;
}

.empty-state h3 {
  margin: 0 0 0.5rem;
  font-size: 1.1rem;
  color: #1e293b;
}

.empty-state p {
  margin: 0;
  color: #64748b;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-content {
  background: white;
  border-radius: 16px;
  width: 100%;
  max-width: 600px;
  max-height: 90vh;
  overflow: hidden;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  animation: modalSlideUp 0.2s ease;
}

.modal-content.small {
  max-width: 400px;
}

@keyframes modalSlideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 1.75rem;
  border-bottom: 1px solid #e2e8f0;
  background: #fcfcfc;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
  display: flex;
  align-items: center;
}

.close-btn {
  background: none;
  border: none;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #64748b;
  cursor: pointer;
  transition: all 0.2s;
}

.close-btn:hover {
  background: #fee2e2;
  color: #ef4444;
}

.modal-body {
  padding: 1.75rem;
  overflow-y: auto;
  max-height: calc(90vh - 140px);
}

/* Detail Sections */
.detail-section {
  margin-bottom: 1.5rem;
}

.detail-section:last-child {
  margin-bottom: 0;
}

.detail-section h3 {
  margin: 0 0 1rem;
  font-size: 1rem;
  font-weight: 700;
  color: #334155;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #f1f5f9;
}

.detail-grid {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.detail-item {
  display: flex;
  align-items: baseline;
  gap: 1rem;
}

.detail-label {
  min-width: 120px;
  font-weight: 600;
  color: #64748b;
  font-size: 0.85rem;
}

.detail-value {
  color: #1e293b;
  font-weight: 500;
  font-size: 0.9rem;
}

.detail-link {
  color: #8b5cf6;
  text-decoration: none;
  font-weight: 500;
  font-size: 0.9rem;
}

.detail-link:hover {
  text-decoration: underline;
}

.message-box {
  background: #f8fafc;
  padding: 1rem;
  border-radius: 8px;
  color: #1e293b;
  font-size: 0.9rem;
  line-height: 1.6;
  white-space: pre-wrap;
  border: 1px solid #e2e8f0;
}

/* Delete Warning */
.delete-warning {
  text-align: center;
  padding: 1rem;
}

.delete-warning svg {
  margin-bottom: 1rem;
}

.delete-warning p {
  margin: 0.5rem 0;
  color: #1e293b;
}

.warning-text {
  color: #ef4444;
  font-weight: 600;
  font-size: 0.9rem;
}

/* Modal Footer */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1.25rem 1.75rem;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
}

/* Form Elements */
.form-group {
  margin-bottom: 1rem;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  font-size: 0.9rem;
  color: #475569;
}

.form-select {
  width: 100%;
  padding: 0.625rem 2rem 0.625rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 0.9rem;
  background: white;
  cursor: pointer;
  appearance: none;
}

.form-select:focus {
  outline: none;
  border-color: #8b5cf6;
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

/* Responsive */
@media (max-width: 1024px) {
  .management-main {
    padding: 1.5rem;
  }
}

@media (max-width: 768px) {
  .management-main {
    padding: 1rem;
  }
  
  .header-inner {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .user-greeting {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .header-controls {
    width: 100%;
  }
  
  .greeting {
    font-size: 1.25rem;
  }
  
  .view-badge {
    align-self: flex-start;
  }
  
  .stats-grid {
    grid-template-columns: 1fr 1fr;
  }
  
  .filters-grid {
    grid-template-columns: 1fr;
  }
  
  .data-table thead th,
  .data-table tbody td {
    padding: 0.75rem;
    font-size: 0.85rem;
  }
  
  .pagination-bar {
    flex-direction: column;
    gap: 0.5rem;
    align-items: center;
  }
  
  .detail-item {
    flex-direction: column;
    gap: 0.25rem;
  }
  
  .detail-label {
    min-width: auto;
  }
}

@media (max-width: 480px) {
  .management-main {
    padding: 0.75rem;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .header-controls {
    flex-direction: column;
    align-items: stretch;
  }
  
  .btn-icon,
  .btn-accent {
    width: 100%;
    justify-content: center;
  }
  
  .role-meta {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .view-badge {
    width: 100%;
  }
  
  .view-content {
    justify-content: center;
  }
  
  .action-buttons {
    flex-wrap: wrap;
  }
  
  .pagination-controls {
    width: 100%;
    justify-content: center;
  }
}
</style>