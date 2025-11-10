<template>
  <div class="dashboard-view">
    <header class="dashboard-header">
      <h1 class="welcome-title">Welcome back, {{ authStore.user?.name || 'Employee' }}!</h1>
      <p class="subtitle">Here's what's happening with your payroll and records.</p>
    </header>
    
    <!-- Summary Cards -->
    <div class="summary-cards" v-if="!loading">
      <div class="card">
        <div class="card-icon">📅</div>
        <h3>Present Days</h3>
        <p class="value">{{ stats.attendance_summary?.present_days || 0 }}</p>
      </div>
      <div class="card">
        <div class="card-icon">⏰</div>
        <h3>Total Hours</h3>
        <p class="value">{{ stats.attendance_summary?.total_hours || 0 }}</p>
      </div>
      <div class="card">
        <div class="card-icon">🌴</div>
        <h3>Leave Balance</h3>
        <p class="value">{{ getTotalLeaveBalance() }}</p>
      </div>
      <div class="card">
        <div class="card-icon">💼</div>
        <h3>Overtime Hours</h3>
        <p class="value">{{ stats.attendance_summary?.overtime_hours || 0 }}</p>
      </div>
    </div>

    <!-- Attendance Summary -->
    <div class="info-section" v-if="!loading && stats.attendance_summary">
      <div class="info-card">
        <h3>This Month's Attendance</h3>
        <div class="attendance-grid">
          <div class="attendance-item">
            <span class="label">Present:</span>
            <span class="value present">{{ stats.attendance_summary.present_days || 0 }} days</span>
          </div>
          <div class="attendance-item">
            <span class="label">Absent:</span>
            <span class="value absent">{{ stats.attendance_summary.absent_days || 0 }} days</span>
          </div>
          <div class="attendance-item">
            <span class="label">Late:</span>
            <span class="value late">{{ stats.attendance_summary.late_days || 0 }} days</span>
          </div>
          <div class="attendance-item">
            <span class="label">Total Hours:</span>
            <span class="value">{{ stats.attendance_summary.total_hours || 0 }} hrs</span>
          </div>
        </div>
      </div>

      <div class="info-card">
        <h3>Leave Balances ({{ new Date().getFullYear() }})</h3>
        <div class="leave-balances">
          <div v-for="(balance, type) in stats.leave_balances" :key="type" class="leave-item">
            <span class="leave-type">{{ formatLeaveType(type) }}</span>
            <span class="leave-balance">{{ balance }} days</span>
          </div>
          <div v-if="Object.keys(stats.leave_balances || {}).length === 0" class="empty-state">
            <p>No leave balances available</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Leaves -->
    <div class="records-section" v-if="!loading">
      <div class="section-header">
        <h3>Recent Leave Requests</h3>
        <router-link to="/employee/leaves" class="view-all">View All →</router-link>
      </div>
      <table class="records-table" v-if="stats.recent_leaves && stats.recent_leaves.length > 0">
        <thead>
          <tr>
            <th>Type</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Days</th>
            <th>Status</th>
            <th>Reason</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="leave in stats.recent_leaves" :key="leave.id">
            <td>{{ formatLeaveType(leave.type) }}</td>
            <td>{{ formatDate(leave.start_date) }}</td>
            <td>{{ formatDate(leave.end_date) }}</td>
            <td>{{ leave.number_of_days || 0 }}</td>
            <td>
              <span :class="['status', leave.status.toLowerCase()]">
                {{ leave.status }}
              </span>
            </td>
            <td>{{ leave.reason || 'N/A' }}</td>
          </tr>
        </tbody>
      </table>
      <div v-else class="empty-state">
        <p>No recent leave requests.</p>
      </div>

      <!-- Upcoming Payslip Info -->
      <div class="section-header" style="margin-top: 2rem;">
        <h3>Next Payslip</h3>
      </div>
      <div v-if="stats.upcoming_payslip" class="payslip-info">
        <div class="payslip-detail">
          <span class="label">Period:</span>
          <span class="value">{{ stats.upcoming_payslip.payroll_period }}</span>
        </div>
        <div class="payslip-detail">
          <span class="label">Processing Date:</span>
          <span class="value">{{ formatDate(stats.upcoming_payslip.processing_date) }}</span>
        </div>
        <div class="payslip-detail">
          <span class="label">Estimated Days:</span>
          <span class="value">{{ stats.upcoming_payslip.estimated_days }} days</span>
        </div>
      </div>
      <div v-else class="empty-state">
        <p>No upcoming payslip information available.</p>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Loading dashboard...</p>
    </div>

    <!-- Error State -->
    <div v-if="error" class="error-message">
      {{ error }}
      <button @click="retryFetch" class="btn-primary">Retry</button>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

export default {
  name: 'EmployeeDashboard',
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  data() {
    return {
      pageName: 'Employee Dashboard',
      stats: {},
      loading: false,
      error: null,
      retryCount: 0
    }
  },
  mounted() {
    this.initializeComponent()
  },
 // In EmployeeDashboard.vue - update the methods section

methods: {
  initializeComponent() {
    if (!this.authStore.isAuthenticated) {
      this.error = 'Please log in to access the dashboard.'
      this.$router.push({ name: 'login' })
      return
    }
    
    this.fetchDashboardData()
    
    // Refresh data every 5 minutes to catch setting changes
    this.refreshInterval = setInterval(() => {
      this.fetchDashboardData(true)  // Silent refresh
    }, 300000) // 5 minutes
  },

  async fetchDashboardData(silent = false) {
    if (!silent) {
      this.loading = true
    }
    this.error = null
    
    try {
      // Fetch dashboard data
      const response = await axios.get('/api/dashboard')
      
      if (response.data.role === 'employee' && response.data.stats) {
        // Store old balances for comparison
        const oldBalances = this.stats.leave_balances || {}
        
        this.stats = response.data.stats
        
        // Check if leave balances have changed (admin updated settings)
        if (!silent && this.hasBalancesChanged(oldBalances, this.stats.leave_balances)) {
          this.showBalanceUpdateNotification()
        }
      } else if (response.data.role !== 'employee') {
        this.error = 'This dashboard is for employees only.'
      }
    } catch (err) {
      console.error('Dashboard fetch error:', err)
      if (!silent) {
        this.handleApiError(err)
      }
    } finally {
      if (!silent) {
        this.loading = false
      }
    }
  },

  hasBalancesChanged(oldBalances, newBalances) {
    if (!oldBalances || Object.keys(oldBalances).length === 0) {
      return false  // First load
    }
    
    for (const type in newBalances) {
      if (oldBalances[type] !== newBalances[type]) {
        return true
      }
    }
    return false
  },

  showBalanceUpdateNotification() {
    if (this.$notify) {
      this.$notify({
        type: 'info',
        title: 'Leave Balances Updated',
        text: 'Your leave balances have been updated based on new company policies.',
        duration: 5000
      })
    }
  },

  getTotalLeaveBalance() {
    if (!this.stats.leave_balances) return 0
    return Object.values(this.stats.leave_balances).reduce((sum, val) => sum + val, 0)
  },

  formatLeaveType(type) {
    if (!type) return 'N/A'
    
    // Map database types to display names
    const typeMap = {
      'annual': 'Annual Leave',
      'sick': 'Sick Leave',
      'maternity': 'Maternity Leave',
      'paternity': 'Paternity Leave',
      'bereavement': 'Bereavement Leave',
      'unpaid': 'Unpaid Leave'
    }
    
    return typeMap[type.toLowerCase()] || type.split('_')
      .map(word => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ')
  },

  formatDate(date) {
    if (!date) return 'N/A'
    return new Date(date).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    })
  }
},

// Add to lifecycle hooks
beforeUnmount() {
  // Clear interval when component is destroyed
  if (this.refreshInterval) {
    clearInterval(this.refreshInterval)
  }
}
}
</script>

<style scoped>
.dashboard-view {
  padding: 0;
  max-width: 1400px;
  margin: 0 auto;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.dashboard-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 2rem;
  text-align: center;
}

.welcome-title {
  margin: 0 0 0.5rem;
  font-size: 2.5rem;
  font-weight: 300;
}

.subtitle {
  margin: 0;
  font-size: 1.1rem;
  opacity: 0.9;
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  padding: 2rem;
}

.card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  text-align: center;
  transition: transform 0.2s;
}

.card:hover {
  transform: translateY(-4px);
}

.card-icon {
  font-size: 2.5rem;
  margin-bottom: 1rem;
}

.card h3 {
  margin: 0 0 0.5rem;
  font-size: 1rem;
  color: #7f8c8d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.card .value {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
  color: #2c3e50;
}

.info-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 2rem;
  padding: 0 2rem 2rem;
}

.info-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.info-card h3 {
  margin: 0 0 1.5rem;
  color: #2c3e50;
  font-size: 1.2rem;
}

.attendance-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.attendance-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.attendance-item .label {
  font-weight: 500;
  color: #6c757d;
}

.attendance-item .value {
  font-weight: 600;
  color: #2c3e50;
}

.attendance-item .value.present {
  color: #28a745;
}

.attendance-item .value.absent {
  color: #dc3545;
}

.attendance-item .value.late {
  color: #ffc107;
}

.leave-balances {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.leave-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.leave-type {
  font-weight: 500;
  color: #2c3e50;
}

.leave-balance {
  font-weight: 700;
  color: #667eea;
  font-size: 1.1rem;
}

.records-section {
  padding: 0 2rem 2rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.section-header h3 {
  margin: 0;
  color: #2c3e50;
}

.view-all {
  color: #667eea;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.2s;
}

.view-all:hover {
  color: #764ba2;
  text-decoration: underline;
}

.records-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.records-table th,
.records-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #ecf0f1;
}

.records-table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #2c3e50;
  text-transform: uppercase;
  font-size: 0.85rem;
}

.records-table tr:hover {
  background: #f8f9fa;
}

.status {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
  text-transform: uppercase;
}

.status.approved {
  background: #d4edda;
  color: #155724;
}

.status.pending {
  background: #fff3cd;
  color: #856404;
}

.status.rejected {
  background: #f8d7da;
  color: #721c24;
}

.payslip-info {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.payslip-detail {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.payslip-detail .label {
  font-size: 0.9rem;
  color: #6c757d;
  font-weight: 500;
}

.payslip-detail .value {
  font-size: 1.1rem;
  color: #2c3e50;
  font-weight: 600;
}

.empty-state {
  text-align: center;
  padding: 2rem;
  color: #7f8c8d;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.loading, .error-message {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
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
  border-radius: 8px;
  margin: 2rem;
}

.btn-primary {
  background: #667eea;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  cursor: pointer;
  margin-top: 1rem;
  font-weight: 600;
  transition: background 0.2s;
}

.btn-primary:hover {
  background: #764ba2;
}

@media (max-width: 768px) {
  .dashboard-header {
    padding: 1.5rem 1rem;
  }

  .welcome-title {
    font-size: 1.75rem;
  }

  .subtitle {
    font-size: 0.95rem;
  }

  .summary-cards {
    grid-template-columns: 1fr;
    padding: 1rem;
  }

  .info-section {
    grid-template-columns: 1fr;
    padding: 0 1rem 1rem;
  }

  .attendance-grid {
    grid-template-columns: 1fr;
  }

  .records-section {
    padding: 0 1rem 1rem;
  }

  .records-table {
    font-size: 0.85rem;
  }

  .records-table th,
  .records-table td {
    padding: 0.5rem;
  }

  .payslip-info {
    grid-template-columns: 1fr;
  }
}
</style>