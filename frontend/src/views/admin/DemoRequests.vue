<template>
  <div class="demo-requests-page">
    <div class="page-header">
      <div>
        <h1>Demo Requests</h1>
        <p class="subtitle">Manage and respond to demo requests from potential customers</p>
      </div>
      <div class="header-actions">
        <button @click="exportToCSV" class="btn-secondary">
          <span class="icon">📥</span> Export CSV
        </button>
        <button @click="loadDemoRequests" class="btn-primary">
          <span class="icon">🔄</span> Refresh
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-icon pending">📋</div>
        <div class="stat-info">
          <div class="stat-label">Pending</div>
          <div class="stat-value">{{ stats.pending }}</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon contacted">✅</div>
        <div class="stat-info">
          <div class="stat-label">Contacted</div>
          <div class="stat-value">{{ stats.contacted }}</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon completed">🎉</div>
        <div class="stat-info">
          <div class="stat-label">Completed</div>
          <div class="stat-value">{{ stats.completed }}</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon total">📊</div>
        <div class="stat-info">
          <div class="stat-label">Total Requests</div>
          <div class="stat-value">{{ stats.total }}</div>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
      <div class="filter-group">
        <label>Status:</label>
        <select v-model="filters.status" @change="applyFilters">
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="contacted">Contacted</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Company Size:</label>
        <select v-model="filters.employeeCount" @change="applyFilters">
          <option value="">All Sizes</option>
          <option value="1-10">1-10</option>
          <option value="11-50">11-50</option>
          <option value="51-200">51-200</option>
          <option value="201-500">201-500</option>
          <option value="500+">500+</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Search:</label>
        <input
          v-model="filters.search"
          @input="applyFilters"
          type="text"
          placeholder="Search by name, email, or company..."
        />
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading demo requests...</p>
    </div>

    <!-- Demo Requests Table -->
    <div v-else-if="filteredRequests.length > 0" class="table-container">
      <table class="demo-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Contact</th>
            <th>Company</th>
            <th>Size</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="request in paginatedRequests" :key="request.id">
            <td>
              <div class="date-cell">
                {{ formatDate(request.created_at) }}
                <span class="time">{{ formatTime(request.created_at) }}</span>
              </div>
            </td>
            <td>
              <div class="contact-cell">
                <div class="contact-name">{{ request.first_name }} {{ request.last_name }}</div>
                <div class="contact-email">{{ request.email }}</div>
                <div class="contact-phone">{{ request.phone }}</div>
              </div>
            </td>
            <td>
              <strong>{{ request.company_name }}</strong>
            </td>
            <td>
              <span class="badge size-badge">{{ request.employee_count || 'Not specified' }}</span>
            </td>
            <td>
              <span :class="['status-badge', request.status]">
                {{ request.status }}
              </span>
            </td>
            <td>
              <div class="action-buttons">
                <button @click="viewDetails(request)" class="btn-icon" title="View Details">
                  👁️
                </button>
                <button @click="updateStatus(request)" class="btn-icon" title="Update Status">
                  ✏️
                </button>
                <button @click="sendEmail(request)" class="btn-icon" title="Send Email">
                  ✉️
                </button>
                <button @click="deleteRequest(request)" class="btn-icon btn-danger" title="Delete">
                  🗑️
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="pagination">
        <button
          @click="currentPage--"
          :disabled="currentPage === 1"
          class="btn-secondary btn-sm"
        >
          ← Previous
        </button>
        <span class="pagination-info">
          Page {{ currentPage }} of {{ totalPages }} ({{ filteredRequests.length }} total)
        </span>
        <button
          @click="currentPage++"
          :disabled="currentPage === totalPages"
          class="btn-secondary btn-sm"
        >
          Next →
        </button>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="empty-state">
      <div class="empty-icon">📭</div>
      <h3>No demo requests found</h3>
      <p>{{ filters.search || filters.status ? 'Try adjusting your filters' : 'Demo requests will appear here' }}</p>
    </div>

    <!-- Details Modal -->
    <div v-if="selectedRequest" class="modal-overlay" @click="closeModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>Demo Request Details</h2>
          <button @click="closeModal" class="btn-close">✕</button>
        </div>
        <div class="modal-body">
          <div class="detail-section">
            <h3>Contact Information</h3>
            <div class="detail-grid">
              <div class="detail-item">
                <label>Name:</label>
                <span>{{ selectedRequest.first_name }} {{ selectedRequest.last_name }}</span>
              </div>
              <div class="detail-item">
                <label>Email:</label>
                <span>{{ selectedRequest.email }}</span>
              </div>
              <div class="detail-item">
                <label>Phone:</label>
                <span>{{ selectedRequest.phone }}</span>
              </div>
            </div>
          </div>

          <div class="detail-section">
            <h3>Company Information</h3>
            <div class="detail-grid">
              <div class="detail-item">
                <label>Company Name:</label>
                <span>{{ selectedRequest.company_name }}</span>
              </div>
              <div class="detail-item">
                <label>Number of Employees:</label>
                <span>{{ selectedRequest.employee_count || 'Not specified' }}</span>
              </div>
            </div>
          </div>

          <div class="detail-section" v-if="selectedRequest.message">
            <h3>Additional Information</h3>
            <p class="message-text">{{ selectedRequest.message }}</p>
          </div>

          <div class="detail-section">
            <h3>Request Status</h3>
            <div class="detail-grid">
              <div class="detail-item">
                <label>Current Status:</label>
                <span :class="['status-badge', selectedRequest.status]">
                  {{ selectedRequest.status }}
                </span>
              </div>
              <div class="detail-item">
                <label>Requested On:</label>
                <span>{{ formatDateTime(selectedRequest.created_at) }}</span>
              </div>
              <div class="detail-item" v-if="selectedRequest.contacted_at">
                <label>Contacted On:</label>
                <span>{{ formatDateTime(selectedRequest.contacted_at) }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeModal" class="btn-secondary">Close</button>
          <button @click="updateStatus(selectedRequest)" class="btn-primary">Update Status</button>
          <button @click="sendEmail(selectedRequest)" class="btn-primary">Send Email</button>
        </div>
      </div>
    </div>

    <!-- Status Update Modal -->
    <div v-if="showStatusModal" class="modal-overlay" @click="closeStatusModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>Update Status</h2>
          <button @click="closeStatusModal" class="btn-close">✕</button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Select New Status:</label>
            <select v-model="statusUpdate.status" class="form-control">
              <option value="pending">Pending</option>
              <option value="contacted">Contacted</option>
              <option value="completed">Completed</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeStatusModal" class="btn-secondary">Cancel</button>
          <button @click="saveStatusUpdate" class="btn-primary" :disabled="updating">
            {{ updating ? 'Updating...' : 'Update Status' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="modal-overlay" @click="closeDeleteModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h2>Confirm Delete</h2>
          <button @click="closeDeleteModal" class="btn-close">✕</button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this demo request from <strong>{{ deleteTarget?.company_name }}</strong>?</p>
          <p class="warning-text">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
          <button @click="closeDeleteModal" class="btn-secondary">Cancel</button>
          <button @click="confirmDelete" class="btn-danger" :disabled="deleting">
            {{ deleting ? 'Deleting...' : 'Delete' }}
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
.demo-requests-page {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
}

.page-header h1 {
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
  color: #1a1a1a;
}

.subtitle {
  color: #666;
  font-size: 1rem;
}

.header-actions {
  display: flex;
  gap: 1rem;
}

.icon {
  margin-right: 0.5rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.stat-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  font-size: 2.5rem;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 12px;
}

.stat-icon.pending { background: #fef3c7; }
.stat-icon.contacted { background: #d1fae5; }
.stat-icon.completed { background: #dbeafe; }
.stat-icon.total { background: #e0e7ff; }

.stat-label {
  font-size: 0.875rem;
  color: #666;
  margin-bottom: 0.25rem;
}

.stat-value {
  font-size: 2rem;
  font-weight: 700;
  color: #1a1a1a;
}

.filters-section {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin-bottom: 2rem;
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.filter-group {
  flex: 1;
  min-width: 200px;
}

.filter-group label {
  display: block;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #333;
}

.filter-group select,
.filter-group input {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.3s;
}

.filter-group select:focus,
.filter-group input:focus {
  outline: none;
  border-color: #667eea;
}

.table-container {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.demo-table {
  width: 100%;
  border-collapse: collapse;
}

.demo-table thead {
  background: #f9fafb;
  border-bottom: 2px solid #e5e7eb;
}

.demo-table th {
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.demo-table td {
  padding: 1rem;
  border-bottom: 1px solid #f3f4f6;
}

.demo-table tbody tr:hover {
  background: #f9fafb;
}

.date-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.time {
  font-size: 0.875rem;
  color: #666;
}

.contact-cell {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.contact-name {
  font-weight: 600;
  color: #1a1a1a;
}

.contact-email {
  font-size: 0.875rem;
  color: #667eea;
}

.contact-phone {
  font-size: 0.875rem;
  color: #666;
}

.status-badge {
  display: inline-block;
  padding: 0.375rem 0.75rem;
  border-radius: 50px;
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: capitalize;
}

.status-badge.pending {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.contacted {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.completed {
  background: #dbeafe;
  color: #1e40af;
}

.status-badge.cancelled {
  background: #fee2e2;
  color: #991b1b;
}

.size-badge {
  background: #f3f4f6;
  color: #374151;
  padding: 0.375rem 0.75rem;
  border-radius: 50px;
  font-size: 0.875rem;
  font-weight: 600;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  padding: 0.5rem;
  border: none;
  background: #f3f4f6;
  border-radius: 6px;
  cursor: pointer;
  font-size: 1.25rem;
  transition: all 0.3s;
}

.btn-icon:hover {
  background: #e5e7eb;
  transform: scale(1.1);
}

.btn-icon.btn-danger:hover {
  background: #fee2e2;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
  background: #94a3b8;
  cursor: not-allowed;
  transform: none;
}

.btn-secondary {
  background: white;
  color: #667eea;
  border: 2px solid #667eea;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-secondary:hover {
  background: #667eea;
  color: white;
}

.btn-secondary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-danger {
  background: #dc2626;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-danger:hover {
  background: #b91c1c;
}

.btn-danger:disabled {
  background: #94a3b8;
  cursor: not-allowed;
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
}

.pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.pagination-info {
  color: #666;
  font-size: 0.875rem;
}

.loading-state {
  text-align: center;
  padding: 4rem 2rem;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f4f6;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: 12px;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.empty-state h3 {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
  color: #1a1a1a;
}

.empty-state p {
  color: #666;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
}

.modal-content {
  background: white;
  border-radius: 12px;
  max-width: 600px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  font-size: 1.5rem;
  margin: 0;
  color: #1a1a1a;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #666;
  padding: 0.5rem;
}

.btn-close:hover {
  color: #1a1a1a;
}

.modal-body {
  padding: 1.5rem;
}

.detail-section {
  margin-bottom: 2rem;
}

.detail-section:last-child {
  margin-bottom: 0;
}

.detail-section h3 {
  font-size: 1.25rem;
  margin-bottom: 1rem;
  color: #1a1a1a;
}

.detail-grid {
  display: grid;
  gap: 1rem;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-item label {
  font-size: 0.875rem;
  color: #666;
  font-weight: 600;
}

.detail-item span {
  color: #1a1a1a;
}

.message-text {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 8px;
  color: #374151;
  line-height: 1.6;
  white-space: pre-wrap;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.form-control {
  width: 100%;
  padding: 0.75rem;
  border: 2px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  font-family: inherit;
}

.form-control:focus {
  outline: none;
  border-color: #667eea;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group:last-child {
  margin-bottom: 0;
}

.form-group label {
  display: block;
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #333;
}

.warning-text {
  color: #dc2626;
  font-weight: 600;
  margin-top: 0.5rem;
}

@media (max-width: 768px) {
  .demo-requests-page {
    padding: 1rem;
  }

  .page-header {
    flex-direction: column;
    gap: 1rem;
  }

  .header-actions {
    width: 100%;
    flex-direction: column;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .filters-section {
    flex-direction: column;
  }

  .table-container {
    overflow-x: auto;
  }

  .demo-table {
    min-width: 800px;
  }

  .pagination {
    flex-direction: column;
    gap: 1rem;
  }
}
</style>