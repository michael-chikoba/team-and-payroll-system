<template>
  <div class="leaves-view">
    <header class="header">
      <div class="header-content">
        <h1 class="title">{{ pageName }}</h1>
        <p class="subtitle">Manage your leave requests and view approval history</p>
      </div>
      <button @click="$router.push('/employee/apply-leave')" class="apply-btn">
        <i class="icon">+</i> Apply for Leave
      </button>
    </header>
   
    <div class="tabs-container">
      <div class="tabs">
        <button
          @click="activeTab = 'pending'"
          :class="['tab-btn', { active: activeTab === 'pending' }]"
        >
          <i class="icon-clock"></i>
          Pending Requests ({{ getPaginatedPending().length }})
        </button>
        <button
          @click="activeTab = 'history'"
          :class="['tab-btn', { active: activeTab === 'history' }]"
        >
          <i class="icon-history"></i>
          Leave History ({{ getPaginatedHistory().length }})
        </button>
      </div>
      <div class="tab-indicator"></div>
    </div>

    <div class="content">
      <!-- Pending Leaves -->
      <div v-if="activeTab === 'pending'" class="section">
        <div v-if="pendingLeaves.length === 0" class="empty-state">
          <i class="icon-empty"></i>
          <p>No pending leave requests.</p>
          <button @click="$router.push('/employee/apply-leave')" class="btn-primary">Apply for Leave</button>
        </div>
        <div v-else class="table-container">
          <table class="leaves-table">
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
              <tr v-for="leave in getPaginatedPending()" :key="leave.id">
                <td>{{ formatLeaveType(leave.leave_type) }}</td>
                <td>{{ formatDateRange(leave.start_date, leave.end_date) }}</td>
                <td>{{ leave.duration }} days</td>
                <td class="reason-cell">{{ truncateText(leave.reason) }}</td>
                <td>
                  <span :class="['status-badge', leave.status.toLowerCase()]">
                    {{ formatStatus(leave.status) }}
                  </span>
                </td>
                <td>
                  <button @click="cancelLeave(leave.id)" class="action-btn cancel" v-if="leave.status === 'pending'">
                    <i class="icon-cancel"></i> Cancel
                  </button>
                  <button @click="viewDetails(leave.id)" class="action-btn view">
                    <i class="icon-eye"></i> View
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
          <pagination
            :current-page="pendingCurrentPage"
            :total-items="pendingLeaves.length"
            :items-per-page="itemsPerPage"
            @page-change="pendingCurrentPage = $event"
          />
        </div>
      </div>

      <!-- Leave History -->
      <div v-if="activeTab === 'history'" class="section">
        <div v-if="leaveHistory.length === 0" class="empty-state">
          <i class="icon-empty"></i>
          <p>No leave history available.</p>
        </div>
        <div v-else class="table-container">
          <table class="leaves-table">
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
              <tr v-for="leave in getPaginatedHistory()" :key="leave.id">
                <td>{{ formatLeaveType(leave.leave_type) }}</td>
                <td>{{ formatDateRange(leave.start_date, leave.end_date) }}</td>
                <td>{{ leave.duration }} days</td>
                <td>
                  <span :class="['status-badge', leave.status.toLowerCase()]">
                    {{ formatStatus(leave.status) }}
                  </span>
                </td>
                <td>{{ leave.approved_by || leave.approver?.name || 'N/A' }}</td>
                <td>{{ formatDate(leave.updated_at || leave.action_date) }}</td>
              </tr>
            </tbody>
          </table>
          <pagination
            :current-page="historyCurrentPage"
            :total-items="leaveHistory.length"
            :items-per-page="itemsPerPage"
            @page-change="historyCurrentPage = $event"
          />
        </div>
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
  components: {
    // Assuming a Pagination component is imported or defined elsewhere
    // For now, we'll define a simple one inline or assume it's available
  },
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
      pendingCurrentPage: 1,
      historyCurrentPage: 1,
      itemsPerPage: 10,
      loading: false,
      error: null,
      retryCount: 0
    }
  },
  mounted() {
    this.fetchLeaves()
  },
  methods: {
    getPaginatedPending() {
      const start = (this.pendingCurrentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.pendingLeaves.slice(start, end)
    },
    getPaginatedHistory() {
      const start = (this.historyCurrentPage - 1) * this.itemsPerPage
      const end = start + this.itemsPerPage
      return this.leaveHistory.slice(start, end)
    },
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
    },
    truncateText(text) {
      if (!text) return ''
      return text.length > 50 ? text.substring(0, 50) + '...' : text
    }
  }
}
</script>

<style scoped>
.leaves-view {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
  color: #333;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  padding: 2rem;
  border-radius: 20px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  margin-bottom: 2rem;
}

.header-content {
  flex: 1;
}

.title {
  margin: 0 0 0.5rem 0;
  font-size: 2.5rem;
  font-weight: 700;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.subtitle {
  margin: 0;
  color: #6b7280;
  font-size: 1.1rem;
  font-weight: 400;
}

.apply-btn {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border: none;
  padding: 1rem 2rem;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
}

.apply-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
}

.icon {
  font-size: 1.2rem;
  font-weight: bold;
}

.tabs-container {
  position: relative;
  margin-bottom: 1rem;
}

.tabs {
  display: flex;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.tab-btn {
  flex: 1;
  padding: 1.25rem;
  border: none;
  background: none;
  font-weight: 600;
  color: #9ca3af;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1rem;
  position: relative;
}

.tab-btn.active {
  color: #1f2937;
  background: rgba(102, 126, 234, 0.1);
}

.tab-btn .icon {
  font-size: 1.1rem;
}

.tab-indicator {
  position: absolute;
  bottom: -4px;
  left: 0;
  height: 3px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  transition: transform 0.3s ease;
  transform: translateX(0);
  width: 50%;
}

.tab-btn:first-child.active ~ .tab-indicator {
  transform: translateX(100%);
}

.content {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.section {
  padding: 2.5rem;
}

.empty-state {
  text-align: center;
  padding: 6rem 2rem;
  color: #6b7280;
}

.empty-state .icon {
  font-size: 4rem;
  color: #d1d5db;
  margin-bottom: 1rem;
  display: block;
}

.table-container {
  overflow-x: auto;
}

.leaves-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.95rem;
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.leaves-table th,
.leaves-table td {
  padding: 1.25rem 1rem;
  text-align: left;
  border-bottom: 1px solid #f3f4f6;
}

.leaves-table th {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  font-weight: 600;
  color: #374151;
  text-transform: uppercase;
  font-size: 0.8rem;
  letter-spacing: 0.05em;
}

.leaves-table tr:hover {
  background: #f8fafc;
  transform: scale(1.01);
  transition: all 0.2s ease;
}

.reason-cell {
  max-width: 200px;
  word-wrap: break-word;
  color: #6b7280;
}

.status-badge {
  padding: 0.5rem 1rem;
  border-radius: 50px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  display: inline-block;
}

.status-badge.pending {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  color: #92400e;
}

.status-badge.approved {
  background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
  color: #065f46;
}

.status-badge.rejected {
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
  color: #991b1b;
}

.status-badge.cancelled {
  background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
  color: #4b5563;
}

.status-badge.under_review {
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
  color: #1e40af;
}

.action-btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 8px;
  font-size: 0.85rem;
  cursor: pointer;
  margin-right: 0.5rem;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-weight: 500;
}

.action-btn.view {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}

.action-btn.view:hover {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  transform: translateY(-1px);
}

.action-btn.cancel {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
  box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
}

.action-btn.cancel:hover {
  background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
  transform: translateY(-1px);
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 6rem 2rem;
  color: #6b7280;
}

.spinner {
  width: 60px;
  height: 60px;
  border: 4px solid #f3f4f6;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1.5rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-message {
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
  color: #991b1b;
  padding: 2rem;
  border-radius: 12px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 4px 14px rgba(239, 68, 68, 0.2);
}

.btn-primary {
  background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
  color: white;
  border: none;
  padding: 1rem 2rem;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
  box-shadow: 0 4px 14px rgba(139, 92, 246, 0.3);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
}

/* Simple Pagination Component Styles (assuming inline) */
.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
  margin-top: 2rem;
  padding: 1rem;
}

.pagination button {
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  background: white;
  color: #374151;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.pagination button:hover:not(:disabled) {
  background: #f3f4f6;
  border-color: #9ca3af;
}

.pagination button.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-color: transparent;
}

.pagination button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

@media (max-width: 768px) {
  .leaves-view {
    padding: 1rem;
  }

  .header {
    flex-direction: column;
    gap: 1.5rem;
    padding: 1.5rem;
    text-align: center;
  }

  .apply-btn {
    width: 100%;
    justify-content: center;
  }

  .tabs {
    flex-direction: column;
  }

  .tab-indicator {
    display: none;
  }

  .section {
    padding: 1.5rem;
  }

  .leaves-table {
    font-size: 0.85rem;
  }

  .leaves-table th,
  .leaves-table td {
    padding: 0.75rem 0.5rem;
  }

  .action-btn {
    padding: 0.4rem 0.8rem;
    font-size: 0.8rem;
  }
}
</style>