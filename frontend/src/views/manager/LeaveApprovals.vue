<template>
  <div class="leave-approvals">
    <div class="page-header">
      <h1>Leave Approvals</h1>
    </div>

    <!-- Authentication Check -->
    <div v-if="!authStore.isAuthenticated" class="error-message">
      Please log in to access leave approvals.
    </div>

    <!-- Permission Check -->
    <div v-else-if="!authStore.isManager" class="error-message">
      You don't have permission to access this page.
    </div>

    <!-- Loading State -->
    <div v-else-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Loading leave requests...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-message">
      {{ error }}
      <button @click="retryFetch" class="btn-primary" style="margin-top: 1rem;">Retry</button>
    </div>

    <!-- Leave Requests List -->
    <div v-else class="requests-grid">
      <div v-for="request in leaveRequests" :key="request.id" class="request-card">
        <div class="request-header">
          <div class="employee-avatar">
            {{ getInitials(request.employee?.full_name || request.employee?.user?.name) }}
          </div>
          <div class="request-info">
            <h3>{{ request.employee?.full_name || request.employee?.user?.name }}</h3>
            <p class="employee-id">{{ request.employee?.employee_id }}</p>
            <span class="status-badge" :class="getStatusClass(request.status)">
              {{ formatStatus(request.status) }}
            </span>
          </div>
        </div>
        
        <div class="request-details">
          <div class="detail-row">
            <span class="label">📅 Leave Type:</span>
            <span>{{ formatLeaveType(request.type) }}</span>
          </div>
          <div class="detail-row">
            <span class="label">📆 Start Date:</span>
            <span>{{ formatDate(request.start_date) }}</span>
          </div>
          <div class="detail-row">
            <span class="label">📆 End Date:</span>
            <span>{{ formatDate(request.end_date) }}</span>
          </div>
          <div class="detail-row">
            <span class="label">📊 Days:</span>
            <span>{{ request.total_days }}</span>
          </div>
          <div class="detail-row reason-row">
            <span class="label">📝 Reason:</span>
            <span>{{ request.reason || 'N/A' }}</span>
          </div>
        </div>

        <div class="request-actions" v-if="request.status === 'pending'">
          <button @click="approveRequest(request)" class="btn-approve" :disabled="processing">
            Approve
          </button>
          <button @click="showRejectModal(request)" class="btn-reject" :disabled="processing">
            Reject
          </button>
        </div>
        
        <div v-else class="request-status">
          <p>This request has been {{ formatStatus(request.status) }}.</p>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="leaveRequests.length === 0" class="empty-state">
        <p>No pending leave requests</p>
        <p>Your team has no leave requests to review at the moment.</p>
      </div>
    </div>

    <!-- Reject Modal -->
    <div v-if="showRejectDialog" class="modal-overlay" @click.self="closeRejectModal">
      <div class="modal">
        <div class="modal-header">
          <h2>Reject Leave Request</h2>
          <button @click="closeRejectModal" class="close-btn">✕</button>
        </div>
        <div class="modal-body">
          <p>Rejecting leave request for: <strong>{{ selectedRequest?.employee?.full_name }}</strong></p>
          <div class="form-group">
            <label>Manager Notes (Optional)</label>
            <textarea 
              v-model="rejectNotes" 
              rows="4"
              placeholder="Provide a reason for rejection..."
            ></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button @click="closeRejectModal" class="btn-secondary">Cancel</button>
          <button @click="confirmReject" class="btn-reject" :disabled="processing">
            {{ processing ? 'Rejecting...' : 'Confirm Reject' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

export default {
  name: 'LeaveApprovals',
  
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  
  data() {
    return {
      leaveRequests: [],
      loading: false,
      error: null,
      retryCount: 0,
      processing: false,
      showRejectDialog: false,
      selectedRequest: null,
      rejectNotes: ''
    }
  },
  
  mounted() {
    this.initializeComponent()
  },
  
  methods: {
    initializeComponent() {
      if (!this.authStore.isAuthenticated) {
        this.error = 'Please log in to access leave approvals.'
        return
      }
      
      if (!this.authStore.isManager) {
        this.error = 'You do not have permission to access this page.'
        return
      }
      
      this.fetchLeaveRequests()
    },
    
    async fetchLeaveRequests(retry = false) {
      this.loading = true
      this.error = null
      
      try {
        console.log('Fetching pending leave requests... (retry:', retry, ')')
        
        // Use the correct endpoint: /api/manager/leaves/pending
        const response = await axios.get('/api/manager/leaves/pending')
        
        console.log('Leave requests response:', response.data)
        this.leaveRequests = response.data.data || response.data
      } catch (err) {
        console.error('Fetch error:', err)
        this.handleApiError(err)
      } finally {
        this.loading = false
      }
    },
    
    retryFetch() {
      this.retryCount++
      if (this.retryCount <= 3) {
        this.fetchLeaveRequests(true)
      } else {
        this.error = 'Max retries exceeded. Check your network or server.'
      }
    },
    
    async approveRequest(request) {
      if (!confirm(`Approve leave request for ${request.employee?.full_name || request.employee?.user?.name}?`)) {
        return
      }
      
      this.processing = true
      
      try {
        // Use the correct endpoint: /api/manager/leaves/{leave}/approve
        await axios.post(`/api/manager/leaves/${request.id}/approve`, {
          status: 'approved'
        })
        
        if (this.$notify) {
          this.$notify({
            type: 'success',
            title: 'Success',
            text: 'Leave request approved successfully!'
          })
        }
        
        await this.fetchLeaveRequests()
      } catch (err) {
        console.error('Approve error:', err)
        this.handleApiError(err)
      } finally {
        this.processing = false
      }
    },
    
    showRejectModal(request) {
      this.selectedRequest = request
      this.rejectNotes = ''
      this.showRejectDialog = true
    },
    
    closeRejectModal() {
      this.showRejectDialog = false
      this.selectedRequest = null
      this.rejectNotes = ''
    },
    
    async confirmReject() {
      if (!this.selectedRequest) return
      
      this.processing = true
      
      try {
        // Use the correct endpoint: /api/manager/leaves/{leave}/reject
        await axios.post(`/api/manager/leaves/${this.selectedRequest.id}/reject`, {
          status: 'rejected',
          manager_notes: this.rejectNotes
        })
        
        if (this.$notify) {
          this.$notify({
            type: 'success',
            title: 'Success',
            text: 'Leave request rejected successfully!'
          })
        }
        
        this.closeRejectModal()
        await this.fetchLeaveRequests()
      } catch (err) {
        console.error('Reject error:', err)
        this.handleApiError(err)
      } finally {
        this.processing = false
      }
    },
    
    handleApiError(err) {
      let errorMsg = 'An unexpected error occurred.'
      if (err.code === 'ERR_NETWORK') {
        errorMsg = 'Network error. Please check your connection.'
      } else if (err.response?.status === 401) {
        errorMsg = 'Session expired. Redirecting to login...'
        this.authStore.clearAuth()
        setTimeout(() => this.$router.push({ name: 'login' }), 2000)
      } else if (err.response?.status === 403) {
        errorMsg = 'You do not have permission to perform this action.'
      } else {
        errorMsg = err.response?.data?.message || errorMsg
      }
      this.error = errorMsg
    },
    
    getInitials(name) {
      if (!name) return '??'
      return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2)
    },
    
    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    },
    
    formatStatus(status) {
      const statuses = {
        pending: 'Pending',
        approved: 'Approved',
        rejected: 'Rejected'
      }
      return statuses[status] || status
    },
    
    getStatusClass(status) {
      return `status-${status}`
    },
    
    formatLeaveType(type) {
      const types = {
        annual: 'Annual Leave',
        sick: 'Sick Leave',
        maternity: 'Maternity Leave',
        paternity: 'Paternity Leave',
        bereavement: 'Bereavement Leave',
        unpaid: 'Unpaid Leave'
      }
      return types[type] || type
    }
  }
}
</script>

<style scoped>
.leave-approvals {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 2rem;
}

.page-header h1 {
  color: #2d3748;
  font-size: 2rem;
  margin: 0;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s;
}

.btn-primary:hover {
  transform: translateY(-2px);
}

.loading {
  text-align: center;
  padding: 4rem;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-message {
  background: #fee;
  color: #c33;
  padding: 1rem;
  border-radius: 8px;
  text-align: center;
}

.requests-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
}

.request-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  padding: 1.5rem;
  transition: transform 0.2s, box-shadow 0.2s;
}

.request-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.request-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f0f0f0;
}

.employee-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  font-weight: 700;
}

.request-info h3 {
  margin: 0 0 0.25rem 0;
  color: #2d3748;
  font-size: 1.1rem;
}

.employee-id {
  color: #718096;
  font-size: 0.875rem;
  margin: 0 0 0.5rem 0;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
}

.status-pending {
  background: #fff7e6;
  color: #fa8c16;
}

.status-approved {
  background: #f6ffed;
  color: #52c41a;
}

.status-rejected {
  background: #fff1f0;
  color: #f5222d;
}

.request-details {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.9rem;
}

.label {
  color: #718096;
  font-weight: 500;
}

.request-actions {
  display: flex;
  gap: 0.5rem;
}

.request-actions button {
  flex: 1;
  padding: 0.75rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s;
}

.btn-approve {
  background: #f6ffed;
  color: #52c41a;
}

.btn-reject {
  background: #fff1f0;
  color: #f5222d;
}

.request-actions button:hover {
  transform: scale(1.05);
}

.request-status {
  text-align: center;
  padding: 1rem;
  color: #718096;
  font-style: italic;
}

.empty-state {
  grid-column: 1 / -1;
  text-align: center;
  padding: 4rem;
  color: #718096;
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
}

.modal {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h2 {
  margin: 0;
  color: #2d3748;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #718096;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
}

.close-btn:hover {
  background: #f0f0f0;
}

.modal-body {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  color: #4a5568;
  font-weight: 600;
}

.form-group textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 1rem;
  resize: vertical;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1rem 1.5rem;
  border-top: 1px solid #e2e8f0;
}

.btn-secondary {
  background: #e2e8f0;
  color: #4a5568;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
}

@media (max-width: 768px) {
  .requests-grid {
    grid-template-columns: 1fr;
  }
}
</style>