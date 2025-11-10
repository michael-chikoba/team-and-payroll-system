<template>
  <div class="leaves-view">
    <header class="header">
      <h1 class="title">{{ pageName }}</h1>
      <p class="subtitle">Manage your leave requests and view approval history</p>
      <button @click="$router.push('/employee/apply-leave')" class="apply-btn">+ Apply for Leave</button>
    </header>
   
    <div class="tabs">
      <button
        @click="activeTab = 'pending'"
        :class="['tab-btn', { active: activeTab === 'pending' }]"
      >
        Pending Requests ({{ pendingLeaves.length }})
      </button>
      <button
        @click="activeTab = 'history'"
        :class="['tab-btn', { active: activeTab === 'history' }]"
      >
        Leave History ({{ leaveHistory.length }})
      </button>
    </div>
    <div class="content">
      <!-- Pending Leaves -->
      <div v-if="activeTab === 'pending'" class="section">
        <div v-if="pendingLeaves.length === 0" class="empty-state">
          <p>No pending leave requests.</p>
          <button @click="$router.push('/employee/apply-leave')" class="btn-primary">Apply for Leave</button>
        </div>
        <table v-else class="leaves-table">
          <thead>
            <tr>
              <th>Type</th>
              <th>Dates</th>
              <th>Duration</th>
              <th>Reason</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="leave in pendingLeaves" :key="leave.id">
              <td>{{ formatLeaveType(leave.leave_type) }}</td>
              <td>{{ formatDateRange(leave.start_date, leave.end_date) }}</td>
              <td>{{ leave.duration }} days</td>
              <td class="reason-cell">{{ leave.reason ? leave.reason.substring(0, 50) : '' }}{{ leave.reason && leave.reason.length > 50 ? '...' : '' }}</td>
              <td>
                <span :class="['status', leave.status.toLowerCase()]">
                  {{ formatStatus(leave.status) }}
                </span>
              </td>
              <td>
                <button @click="cancelLeave(leave.id)" class="action-btn cancel" v-if="leave.status === 'pending'">Cancel</button>
                <button @click="viewDetails(leave.id)" class="action-btn view">View</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <!-- Leave History -->
      <div v-if="activeTab === 'history'" class="section">
        <div v-if="leaveHistory.length === 0" class="empty-state">
          <p>No leave history available.</p>
        </div>
        <table v-else class="leaves-table">
          <thead>
            <tr>
              <th>Type</th>
              <th>Dates</th>
              <th>Duration</th>
              <th>Status</th>
              <th>Approved By</th>
              <th>Action Date</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="leave in leaveHistory" :key="leave.id">
              <td>{{ formatLeaveType(leave.leave_type) }}</td>
              <td>{{ formatDateRange(leave.start_date, leave.end_date) }}</td>
              <td>{{ leave.duration }} days</td>
              <td>
                <span :class="['status', leave.status.toLowerCase()]">
                  {{ formatStatus(leave.status) }}
                </span>
              </td>
              <td>{{ leave.approved_by || leave.approver?.name || 'N/A' }}</td>
              <td>{{ formatDate(leave.updated_at || leave.action_date) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <!-- Loading State -->
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Loading leave records...</p>
      </div>
      <!-- Error State -->
      <div v-if="error" class="error-message">
        {{ error }}
        <button @click="retryFetch" class="btn-primary">Retry</button>
      </div>
    </div>
  </div>
</template>
<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
export default {
  name: 'Leaves',
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  data() {
    return {
      pageName: 'My Leaves',
      activeTab: 'pending',
      pendingLeaves: [],
      leaveHistory: [],
      loading: false,
      error: null,
      retryCount: 0
    }
  },
  mounted() {
    this.fetchLeaves()
  },
  methods: {
    async fetchLeaves(retry = false) {
      this.loading = true
      this.error = null
      try {
        console.log('Fetching leaves... (retry:', retry, ')')
       
        // Use the correct endpoint: GET /api/employee/leaves
        const response = await axios.get('/api/employee/leaves')
        console.log('Leaves API response:', response.data)
       
        // Handle different response structures
        const leavesData = response.data.data || response.data || []
       
        // Filter leaves into pending and history
        this.pendingLeaves = leavesData.filter(leave =>
          leave.status === 'pending' || leave.status === 'under_review'
        )
       
        this.leaveHistory = leavesData.filter(leave =>
          leave.status !== 'pending' && leave.status !== 'under_review'
        )
       
        console.log('Pending leaves:', this.pendingLeaves)
        console.log('Leave history:', this.leaveHistory)
       
      } catch (err) {
        console.error('Leaves fetch error:', err)
        this.handleApiError(err)
      } finally {
        this.loading = false
      }
    },
   
    async cancelLeave(id) {
      if (!confirm('Are you sure you want to cancel this leave request?')) return
      try {
        // Use the dedicated cancel endpoint: POST /api/employee/leaves/{id}/cancel
        const response = await axios.post(`/api/employee/leaves/${id}/cancel`)
       
        // Refresh the full list to reflect changes (moves from pending to history)
        await this.fetchLeaves()
       
        this.$notify({
          type: 'success',
          title: 'Success',
          text: response.data.message || 'Leave request cancelled successfully!'
        })
      } catch (err) {
        console.error('Cancel leave error:', err)
        this.$notify({
          type: 'error',
          title: 'Cancel Failed',
          text: err.response?.data?.message || 'Failed to cancel leave request. Please try again.'
        })
      }
    },
   
    viewDetails(id) {
      this.$router.push({ name: 'leave-details', params: { id } })
    },
   
    retryFetch() {
      this.retryCount++
      if (this.retryCount <= 3) {
        this.fetchLeaves(true)
      } else {
        this.error = 'Max retries exceeded. Please refresh the page.'
      }
    },
   
    handleApiError(err) {
      let errorMsg = 'An unexpected error occurred.'
      if (err.code === 'ERR_NETWORK' || err.message.includes('Network Error')) {
        errorMsg = 'Network error. Check your connection.'
      } else if (err.response?.status === 401) {
        errorMsg = 'Session expired. Redirecting to login...'
        this.authStore.clearAuth()
        this.$router.push({ name: 'login' })
      } else if (err.response?.status === 403) {
        errorMsg = 'Access denied.'
      } else if (err.response?.status === 404) {
        errorMsg = 'Leave data not found. The endpoint may not be available.'
      } else {
        errorMsg = err.response?.data?.message || errorMsg
      }
      this.error = errorMsg
    },
   
    formatLeaveType(type) {
      const types = {
        annual: 'Annual',
        sick: 'Sick',
        maternity: 'Maternity',
        paternity: 'Paternity',
        bereavement: 'Bereavement',
        unpaid: 'Unpaid',
        casual: 'Casual',
        emergency: 'Emergency'
      }
      return types[type] || type
    },
   
    formatStatus(status) {
      const statuses = {
        pending: 'Pending',
        approved: 'Approved',
        rejected: 'Rejected',
        cancelled: 'Cancelled',
        under_review: 'Under Review'
      }
      return statuses[status] || status
    },
   
    formatDateRange(start, end) {
      return `${this.formatDate(start)} - ${this.formatDate(end)}`
    },
   
    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
  }
}
</script>
<style scoped>
.leaves-view {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  min-height: 100vh;
}
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: white;
  padding: 1.5rem 2rem;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  margin-bottom: 2rem;
}
.title {
  margin: 0;
  font-size: 2rem;
  font-weight: 300;
  color: #2c3e50;
}
.subtitle {
  margin: 0;
  color: #7f8c8d;
  font-size: 1rem;
}
.apply-btn {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: transform 0.2s ease;
}
.apply-btn:hover {
  transform: translateY(-2px);
}
.tabs {
  display: flex;
  background: white;
  border-radius: 12px 12px 0 0;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  margin-bottom: 0;
}
.tab-btn {
  flex: 1;
  padding: 1rem;
  border: none;
  background: none;
  font-weight: 500;
  color: #7f8c8d;
  cursor: pointer;
  transition: all 0.3s ease;
  border-bottom: 3px solid transparent;
}
.tab-btn.active {
  color: #2c3e50;
  background: #f8f9fa;
  border-bottom-color: #667eea;
}
.content {
  background: white;
  border-radius: 0 0 12px 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}
.section {
  padding: 2rem;
}
.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #7f8c8d;
}
.leaves-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.95rem;
}
.leaves-table th,
.leaves-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #ecf0f1;
}
.leaves-table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #2c3e50;
  text-transform: uppercase;
  font-size: 0.85rem;
}
.leaves-table tr:hover {
  background: #f8f9fa;
}
.reason-cell {
  max-width: 200px;
  word-wrap: break-word;
}
.status {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
  text-transform: uppercase;
}
.status.pending {
  background: #fff3cd;
  color: #856404;
}
.status.approved {
  background: #d4edda;
  color: #155724;
}
.status.rejected {
  background: #f8d7da;
  color: #721c24;
}
.status.cancelled {
  background: #e2e3e5;
  color: #383d41;
}
.status.under_review {
  background: #cce7ff;
  color: #004085;
}
.action-btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.85rem;
  cursor: pointer;
  margin-right: 0.5rem;
  transition: background 0.2s ease;
}
.action-btn.view {
  background: #007bff;
  color: white;
}
.action-btn.view:hover {
  background: #0056b3;
}
.action-btn.cancel {
  background: #dc3545;
  color: white;
}
.action-btn.cancel:hover {
  background: #c82333;
}
.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
}
.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
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
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}
.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
}
@media (max-width: 768px) {
  .header {
    flex-direction: column;
    gap: 1rem;
    padding: 1rem;
  }
  .apply-btn {
    width: 100%;
  }
  .tabs {
    flex-direction: column;
  }
  .section {
    padding: 1rem;
  }
  .leaves-table {
    font-size: 0.85rem;
  }
  .leaves-table th,
  .leaves-table td {
    padding: 0.5rem;
  }
  .form-row {
    grid-template-columns: 1fr;
  }
}
</style>