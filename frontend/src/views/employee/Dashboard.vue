<template>
  <div class="dashboard-view">
    <header class="dashboard-header">
      <div class="header-content">
        <div class="user-info">
          <h1 class="welcome-title">Welcome back, {{ authStore.user?.name || 'Employee' }}!</h1>
          <p class="subtitle">Here's what's happening with your payroll and records.</p>
        </div>
        <div class="date-display">
          <span class="current-date">{{ currentDate }}</span>
        </div>
      </div>
    </header>
   
    <!-- Summary Cards -->
    <div class="summary-cards" v-if="!loading">
      <div class="card">
        <div class="card-icon present">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
        </div>
        <h3>Present Days</h3>
        <p class="value">{{ stats.attendance_summary?.present_days || 0 }}</p>
        <span class="card-subtitle">This month</span>
      </div>
      <div class="card">
        <div class="card-icon hours">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
        </div>
        <h3>Total Hours</h3>
        <p class="value">{{ formatHours(stats.attendance_summary?.total_hours) || '0.00' }}</p>
        <span class="card-subtitle">Worked hours</span>
      </div>
      <div class="card">
        <div class="card-icon leave">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14"></path>
            <path d="M12 5l7 7-7 7"></path>
          </svg>
        </div>
        <h3>Leave Balance</h3>
        <p class="value">{{ getTotalLeaveBalance() }}</p>
        <span class="card-subtitle">Available days</span>
      </div>
      <div class="card">
        <div class="card-icon overtime">
          <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 10"></polyline>
          </svg>
        </div>
        <h3>Overtime Hours</h3>
        <p class="value">{{ formatHours(stats.attendance_summary?.overtime_hours) || '0.00' }}</p>
        <span class="card-subtitle">Extra hours</span>
      </div>
    </div>

    <!-- Dashboard Content -->
    <div class="dashboard-content" v-if="!loading">
      <!-- Attendance and Leave Section -->
      <div class="content-section">
        <div class="section-column">
          <!-- Attendance Summary -->
          <div class="info-card" v-if="stats.attendance_summary">
            <div class="card-header">
              <h3>This Month's Attendance</h3>
              <div class="card-actions">
                <button class="btn-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                  </svg>
                </button>
              </div>
            </div>
            <div class="attendance-grid">
              <div class="attendance-item">
                <div class="attendance-indicator present"></div>
                <div class="attendance-details">
                  <span class="label">Present</span>
                  <span class="value">{{ stats.attendance_summary.present_days || 0 }} days</span>
                </div>
              </div>
              <div class="attendance-item">
                <div class="attendance-indicator absent"></div>
                <div class="attendance-details">
                  <span class="label">Absent</span>
                  <span class="value">{{ stats.attendance_summary.absent_days || 0 }} days</span>
                </div>
              </div>
              <div class="attendance-item">
                <div class="attendance-indicator late"></div>
                <div class="attendance-details">
                  <span class="label">Late</span>
                  <span class="value">{{ stats.attendance_summary.late_days || 0 }} days</span>
                </div>
              </div>
              <div class="attendance-item">
                <div class="attendance-indicator total"></div>
                <div class="attendance-details">
                  <span class="label">Total Hours</span>
                  <span class="value">{{ formatHours(stats.attendance_summary.total_hours) || '0.00' }} hrs</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="section-column">
          <!-- Leave Balances -->
          <div class="info-card">
            <div class="card-header">
              <h3>Leave Balances ({{ new Date().getFullYear() }})</h3>
              <div class="card-actions">
                <button class="btn-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                  </svg>
                </button>
              </div>
            </div>
            <div class="leave-balances">
              <div v-for="(balance, type) in stats.leave_balances" :key="type" class="leave-item">
                <div class="leave-type-info">
                  <span class="leave-type">{{ formatLeaveType(type) }}</span>
                  <span class="leave-days">{{ balance }} days</span>
                </div>
                <div class="leave-progress">
                  <div class="progress-bar">
                    <div class="progress-fill" :style="{ width: calculateProgress(balance) + '%' }"></div>
                  </div>
                </div>
              </div>
              <div v-if="Object.keys(stats.leave_balances || {}).length === 0" class="empty-state">
                <p>No leave balances available</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Leaves and Payslip Section -->
      <div class="content-section">
        <div class="section-column wide">
          <!-- Recent Leaves -->
          <div class="info-card">
            <div class="card-header">
              <h3>Recent Leave Requests</h3>
              <div class="card-actions">
                <router-link to="/employee/leaves" class="view-all">View All</router-link>
              </div>
            </div>
            <div class="table-container" v-if="stats.recent_leaves && stats.recent_leaves.length > 0">
              <table class="records-table">
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
                    <td>
                      <div class="leave-type-cell">
                        <span class="leave-type-badge">{{ formatLeaveType(leave.type) }}</span>
                      </div>
                    </td>
                    <td>{{ formatDate(leave.start_date) }}</td>
                    <td>{{ formatDate(leave.end_date) }}</td>
                    <td>{{ leave.number_of_days || 0 }}</td>
                    <td>
                      <span :class="['status', leave.status.toLowerCase()]">
                        {{ leave.status }}
                      </span>
                    </td>
                    <td class="reason-cell">{{ leave.reason || 'N/A' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="empty-state">
              <div class="empty-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                  <polyline points="14 2 14 8 20 8"></polyline>
                  <line x1="16" y1="13" x2="8" y2="13"></line>
                  <line x1="16" y1="17" x2="8" y2="17"></line>
                  <polyline points="10 9 9 9 8 9"></polyline>
                </svg>
              </div>
              <p>No recent leave requests.</p>
            </div>
          </div>
        </div>

        <div class="section-column">
          <!-- Upcoming Payslip Info -->
          <div class="info-card">
            <div class="card-header">
              <h3>Next Payslip</h3>
              <div class="card-actions">
                <button class="btn-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                  </svg>
                </button>
              </div>
            </div>
            <div v-if="stats.upcoming_payslip" class="payslip-info">
              <div class="payslip-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                  <path d="M6 8h12"></path>
                  <path d="M6 12h12"></path>
                  <path d="M6 16h6"></path>
                </svg>
              </div>
              <div class="payslip-details">
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
            </div>
            <div v-else class="empty-state">
              <div class="empty-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                  <path d="M6 8h12"></path>
                  <path d="M6 12h12"></path>
                  <path d="M6 16h6"></path>
                </svg>
              </div>
              <p>No upcoming payslip information available.</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Loading dashboard...</p>
    </div>

    <!-- Error State -->
    <div v-if="error" class="error-message">
      <div class="error-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
      </div>
      <h3>Something went wrong</h3>
      <p>{{ error }}</p>
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
      retryCount: 0,
      currentDate: new Date().toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
      })
    }
  },
  mounted() {
    this.initializeComponent()
  },
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
    formatHours(hours) {
      if (!hours) return '0.00'
      return parseFloat(hours).toFixed(2)
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
    },
    calculateProgress(balance) {
      // Simple progress calculation for leave balances
      // You can customize this based on your business logic
      const maxBalance = 30; // Assuming 30 days as maximum
      return Math.min((balance / maxBalance) * 100, 100);
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
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
  background-color: #f8fafc;
  min-height: 100vh;
}

.dashboard-header {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  color: white;
  padding: 2rem 2rem 2.5rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.header-content {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  max-width: 1200px;
  margin: 0 auto;
}

.user-info {
  flex: 1;
}

.welcome-title {
  margin: 0 0 0.5rem;
  font-size: 2.25rem;
  font-weight: 700;
  letter-spacing: -0.025em;
}

.subtitle {
  margin: 0;
  font-size: 1.125rem;
  opacity: 0.9;
  font-weight: 400;
}

.date-display {
  text-align: right;
}

.current-date {
  font-size: 1rem;
  opacity: 0.9;
  font-weight: 500;
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
  padding: 0 2rem;
  margin-top: -1.5rem;
  margin-bottom: 2rem;
  position: relative;
  z-index: 10;
}

.card {
  background: white;
  padding: 1.75rem;
  border-radius: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  text-align: center;
  transition: all 0.3s ease;
  border: 1px solid #e2e8f0;
  position: relative;
  overflow: hidden;
}

.card:hover {
  transform: translateY(-5px);
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #6366f1, #8b5cf6);
}

.card-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 1rem;
  background: rgba(99, 102, 241, 0.1);
  color: #6366f1;
}

.card-icon.present {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.card-icon.hours {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.card-icon.leave {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.card-icon.overtime {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.card h3 {
  margin: 0 0 0.5rem;
  font-size: 0.875rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 600;
}

.card .value {
  margin: 0;
  font-size: 2.25rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
}

.card-subtitle {
  font-size: 0.75rem;
  color: #94a3b8;
  margin-top: 0.5rem;
  display: block;
}

.dashboard-content {
  padding: 0 2rem 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.content-section {
  display: flex;
  gap: 2rem;
  margin-bottom: 2rem;
}

.section-column {
  flex: 1;
}

.section-column.wide {
  flex: 2;
}

.info-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #e2e8f0;
  overflow: hidden;
  height: 100%;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem 1.5rem 1rem;
  border-bottom: 1px solid #f1f5f9;
}

.card-header h3 {
  margin: 0;
  color: #1e293b;
  font-size: 1.25rem;
  font-weight: 600;
}

.card-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-icon {
  background: none;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.btn-icon:hover {
  background: #f1f5f9;
  color: #475569;
}

.view-all {
  color: #6366f1;
  text-decoration: none;
  font-weight: 500;
  font-size: 0.875rem;
  transition: color 0.2s;
  padding: 0.5rem 0.75rem;
  border-radius: 8px;
}

.view-all:hover {
  color: #4f46e5;
  background: #f8fafc;
}

.attendance-grid {
  padding: 1.5rem;
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.attendance-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 12px;
  transition: all 0.2s;
}

.attendance-item:hover {
  background: #f1f5f9;
}

.attendance-indicator {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  flex-shrink: 0;
}

.attendance-indicator.present {
  background: #10b981;
}

.attendance-indicator.absent {
  background: #ef4444;
}

.attendance-indicator.late {
  background: #f59e0b;
}

.attendance-indicator.total {
  background: #6366f1;
}

.attendance-details {
  display: flex;
  flex-direction: column;
}

.attendance-details .label {
  font-size: 0.875rem;
  color: #64748b;
  font-weight: 500;
}

.attendance-details .value {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
}

.leave-balances {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.leave-item {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.leave-type-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.leave-type {
  font-weight: 500;
  color: #1e293b;
  font-size: 0.95rem;
}

.leave-days {
  font-weight: 700;
  color: #6366f1;
  font-size: 1rem;
}

.leave-progress {
  width: 100%;
}

.progress-bar {
  height: 6px;
  background: #e2e8f0;
  border-radius: 3px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #6366f1, #8b5cf6);
  border-radius: 3px;
  transition: width 0.5s ease;
}

.table-container {
  overflow-x: auto;
}

.records-table {
  width: 100%;
  border-collapse: collapse;
}

.records-table th,
.records-table td {
  padding: 1rem 1.5rem;
  text-align: left;
  border-bottom: 1px solid #f1f5f9;
}

.records-table th {
  background: #f8fafc;
  font-weight: 600;
  color: #475569;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.05em;
}

.records-table tr:hover {
  background: #f8fafc;
}

.leave-type-cell {
  display: flex;
  align-items: center;
}

.leave-type-badge {
  background: #f1f5f9;
  color: #475569;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 500;
}

.status {
  padding: 0.375rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.status.approved {
  background: #d1fae5;
  color: #065f46;
}

.status.pending {
  background: #fef3c7;
  color: #92400e;
}

.status.rejected {
  background: #fee2e2;
  color: #991b1b;
}

.reason-cell {
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.payslip-info {
  padding: 1.5rem;
  display: flex;
  gap: 1.5rem;
  align-items: center;
}

.payslip-icon {
  width: 64px;
  height: 64px;
  border-radius: 12px;
  background: rgba(99, 102, 241, 0.1);
  color: #6366f1;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.payslip-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.payslip-detail {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #f1f5f9;
}

.payslip-detail:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.payslip-detail .label {
  font-size: 0.875rem;
  color: #64748b;
  font-weight: 500;
}

.payslip-detail .value {
  font-size: 0.95rem;
  color: #1e293b;
  font-weight: 600;
}

.empty-state {
  padding: 3rem 1.5rem;
  text-align: center;
  color: #64748b;
}

.empty-icon {
  margin-bottom: 1rem;
  color: #cbd5e1;
}

.empty-state p {
  margin: 0;
  font-size: 1rem;
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
  border: 4px solid #f1f5f9;
  border-top: 4px solid #6366f1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-message {
  background: white;
  border-radius: 16px;
  padding: 3rem;
  margin: 2rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  border: 1px solid #fecaca;
}

.error-icon {
  margin-bottom: 1rem;
  color: #ef4444;
}

.error-message h3 {
  margin: 0 0 0.5rem;
  color: #dc2626;
  font-size: 1.5rem;
}

.error-message p {
  margin: 0 0 1.5rem;
  color: #7f1d1d;
}

.btn-primary {
  background: #6366f1;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: background 0.2s;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
}

.btn-primary:hover {
  background: #4f46e5;
}

@media (max-width: 1024px) {
  .content-section {
    flex-direction: column;
  }
  
  .section-column.wide {
    flex: 1;
  }
}

@media (max-width: 768px) {
  .dashboard-header {
    padding: 1.5rem 1rem 2rem;
  }
  
  .header-content {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .date-display {
    text-align: left;
  }
  
  .welcome-title {
    font-size: 1.75rem;
  }
  
  .subtitle {
    font-size: 1rem;
  }
  
  .summary-cards {
    grid-template-columns: repeat(2, 1fr);
    padding: 0 1rem;
    margin-top: -1rem;
  }
  
  .dashboard-content {
    padding: 0 1rem 1rem;
  }
  
  .content-section {
    gap: 1.5rem;
  }
  
  .attendance-grid {
    grid-template-columns: 1fr;
    padding: 1rem;
  }
  
  .records-table th,
  .records-table td {
    padding: 0.75rem 1rem;
  }
  
  .payslip-info {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }
  
  .payslip-detail {
    flex-direction: column;
    gap: 0.25rem;
  }
}

@media (max-width: 640px) {
  .summary-cards {
    grid-template-columns: 1fr;
  }
  
  .card {
    padding: 1.5rem;
  }
}
</style>