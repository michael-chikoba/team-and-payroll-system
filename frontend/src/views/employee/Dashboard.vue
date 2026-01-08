<template>
  <div class="dashboard-container">
    <!-- Header Section -->
    <div class="dashboard-header">
      <div class="header-top">
        <div class="user-greeting">
          <div class="avatar-section">
            <div class="avatar">
              <span>{{ getUserInitials() }}</span>
            </div>
            <div class="user-info">
              <h1 class="greeting">Welcome back, {{ authStore.user?.name || 'Employee' }}!</h1>
              <p class="subtitle">Track your attendance, leaves, and payroll in real-time</p>
            </div>
          </div>
          <div class="date-badge">
            <div class="date-content">
              <span class="day">{{ currentDay }}</span>
              <div class="date-details">
                <span class="date">{{ currentDateNum }}</span>
                <span class="month-year">{{ currentMonthYear }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="dashboard-main">
      <!-- Stats Cards Grid -->
      <div class="stats-grid" v-if="!loading">
        <div class="stat-card" style="--accent: #10b981;">
          <div class="card-header">
            <div class="icon-wrapper" style="background: rgba(16, 185, 129, 0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
              </svg>
            </div>
            <span class="trend-indicator positive">+2 this week</span>
          </div>
          <h3>Present Days</h3>
          <div class="stat-value">{{ stats.attendance_summary?.present_days || 0 }}</div>
          <div class="card-footer">
            <span>This month</span>
            <span class="compare">vs 18 last month</span>
          </div>
        </div>

        <div class="stat-card" style="--accent: #3b82f6;">
          <div class="card-header">
            <div class="icon-wrapper" style="background: rgba(59, 130, 246, 0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
              </svg>
            </div>
            <span class="trend-indicator positive">+5.2hrs</span>
          </div>
          <h3>Total Hours</h3>
          <div class="stat-value">{{ formatHours(stats.attendance_summary?.total_hours) || '0.00' }}</div>
          <div class="card-footer">
            <span>Worked hours</span>
            <span class="compare">Avg 8.2hrs/day</span>
          </div>
        </div>

        <div class="stat-card" style="--accent: #f59e0b;">
          <div class="card-header">
            <div class="icon-wrapper" style="background: rgba(245, 158, 11, 0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M5 12h14"></path>
                <path d="M12 5l7 7-7 7"></path>
              </svg>
            </div>
          </div>
          <h3>Leave Balance</h3>
          <div class="stat-value">{{ getTotalLeaveBalance() }}</div>
          <div class="card-footer">
            <span>Available days</span>
            <span class="compare">Across all types</span>
          </div>
        </div>

        <div class="stat-card" style="--accent: #ef4444;">
          <div class="card-header">
            <div class="icon-wrapper" style="background: rgba(239, 68, 68, 0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 10"></polyline>
              </svg>
            </div>
          </div>
          <h3>Overtime Hours</h3>
          <div class="stat-value">{{ formatHours(stats.attendance_summary?.overtime_hours) || '0.00' }}</div>
          <div class="card-footer">
            <span>Extra hours</span>
            <span class="compare">This month</span>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="content-grid" v-if="!loading">
        <!-- Left Column -->
        <div class="content-column">
          <!-- Attendance Chart -->
          <div class="content-card">
            <div class="card-title-bar">
              <h2>Attendance Summary</h2>
              <select class="period-select" v-model="selectedPeriod">
                <option value="month">This Month</option>
                <option value="week">This Week</option>
                <option value="quarter">This Quarter</option>
              </select>
            </div>
            <div class="attendance-breakdown" v-if="stats.attendance_summary">
              <div class="breakdown-item">
                <div class="metric">
                  <span class="dot present"></span>
                  <span class="label">Present</span>
                </div>
                <div class="values">
                  <span class="value">{{ stats.attendance_summary.present_days || 0 }} days</span>
                  <span class="percentage">{{ calculateAttendancePercentage('present') }}%</span>
                </div>
              </div>
              <div class="breakdown-item">
                <div class="metric">
                  <span class="dot absent"></span>
                  <span class="label">Absent</span>
                </div>
                <div class="values">
                  <span class="value">{{ stats.attendance_summary.absent_days || 0 }} days</span>
                  <span class="percentage">{{ calculateAttendancePercentage('absent') }}%</span>
                </div>
              </div>
              <div class="breakdown-item">
                <div class="metric">
                  <span class="dot late"></span>
                  <span class="label">Late Arrivals</span>
                </div>
                <div class="values">
                  <span class="value">{{ stats.attendance_summary.late_days || 0 }} days</span>
                  <span class="percentage">{{ calculateAttendancePercentage('late') }}%</span>
                </div>
              </div>
              <div class="breakdown-item">
                <div class="metric">
                  <span class="dot total"></span>
                  <span class="label">Total Hours</span>
                </div>
                <div class="values">
                  <span class="value">{{ formatHours(stats.attendance_summary.total_hours) || '0.00' }} hrs</span>
                  <span class="percentage">100%</span>
                </div>
              </div>
            </div>
            <div class="attendance-progress">
              <div class="progress-bar">
                <div class="progress-segment present" :style="{ width: calculateAttendancePercentage('present') + '%' }"></div>
                <div class="progress-segment absent" :style="{ width: calculateAttendancePercentage('absent') + '%' }"></div>
                <div class="progress-segment late" :style="{ width: calculateAttendancePercentage('late') + '%' }"></div>
              </div>
            </div>
          </div>

          <!-- Leave Balances -->
          <div class="content-card">
            <div class="card-title-bar">
              <h2>Leave Balances</h2>
              <span class="year-badge">{{ new Date().getFullYear() }}</span>
            </div>
            <div class="leave-balances-grid" v-if="stats.leave_balances && Object.keys(stats.leave_balances).length > 0">
              <div v-for="(balanceData, type) in stats.leave_balances" :key="type" class="leave-balance-item">
                <div class="leave-info">
                  <span class="leave-type">{{ formatLeaveType(type) }}</span>
                  <span class="leave-stats">{{ balanceData.available }} of {{ balanceData.total }} days</span>
                </div>
                <div class="progress-container">
                  <div class="progress-track">
                    <div class="progress-fill" :style="{ 
                      width: calculateProgress(balanceData) + '%',
                      background: getLeaveColor(type)
                    }"></div>
                  </div>
                  <span class="percentage">{{ calculateProgress(balanceData).toFixed(0) }}%</span>
                </div>
              </div>
            </div>
            <div v-else class="empty-state">
              <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"></path>
                <rect x="9" y="3" width="6" height="4" rx="2"></rect>
                <line x1="9" y1="12" x2="15" y2="12"></line>
                <line x1="9" y1="16" x2="15" y2="16"></line>
              </svg>
              <p>No leave balances available</p>
            </div>
          </div>
        </div>

        <!-- Right Column -->
        <div class="content-column">
          <!-- Recent Leaves -->
          <div class="content-card">
            <div class="card-title-bar">
              <h2>Recent Leave Requests</h2>
              <router-link to="/employee/leaves" class="action-link">
                View All
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M5 12h14"></path>
                  <path d="M12 5l7 7-7 7"></path>
                </svg>
              </router-link>
            </div>
            <div class="table-container" v-if="stats.recent_leaves && stats.recent_leaves.length > 0">
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Type</th>
                    <th>Dates</th>
                    <th>Days</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="leave in stats.recent_leaves.slice(0, 5)" :key="leave.id">
                    <td>
                      <span class="type-badge" :style="{ background: getLeaveColor(leave.type) + '20', color: getLeaveColor(leave.type) }">
                        {{ formatLeaveType(leave.type) }}
                      </span>
                    </td>
                    <td>
                      <div class="date-range">
                        <span>{{ formatDate(leave.start_date) }}</span>
                        <span class="separator">→</span>
                        <span>{{ formatDate(leave.end_date) }}</span>
                      </div>
                    </td>
                    <td class="days-cell">{{ leave.number_of_days || leave.total_days || 0 }}</td>
                    <td>
                      <span :class="['status-badge', leave.status.toLowerCase()]">
                        {{ leave.status }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-else class="empty-state">
              <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
                <line x1="16" y1="13" x2="8" y2="13"></line>
                <line x1="16" y1="17" x2="8" y2="17"></line>
              </svg>
              <p>No recent leave requests</p>
            </div>
          </div>

          <!-- Payslip Card -->
          <div class="content-card payslip-card" v-if="stats.upcoming_payslip">
            <div class="card-title-bar">
              <h2>Next Payslip</h2>
              <span class="status-badge upcoming">Upcoming</span>
            </div>
            <div class="payslip-content">
              <div class="payslip-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                  <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                  <path d="M6 8h12"></path>
                  <path d="M6 12h12"></path>
                  <path d="M6 16h6"></path>
                </svg>
              </div>
              <div class="payslip-details">
                <div class="payslip-detail">
                  <span class="label">Pay Period:</span>
                  <span class="value">{{ stats.upcoming_payslip.payroll_period }}</span>
                </div>
                <div class="payslip-detail">
                  <span class="label">Processing Date:</span>
                  <span class="value">{{ formatDate(stats.upcoming_payslip.processing_date) }}</span>
                </div>
                <div class="payslip-detail">
                  <span class="label">Estimated Days:</span>
                  <span class="value highlight">{{ stats.upcoming_payslip.estimated_days }} days</span>
                </div>
              </div>
            </div>
            <div class="payslip-actions">
              <button class="btn-outline">View Details</button>
              <button class="btn-primary">Download PDF</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading-container">
        <div class="spinner-container">
          <div class="spinner"></div>
          <p>Loading your dashboard...</p>
        </div>
      </div>

      <!-- Error State -->
      <div v-if="error" class="error-container">
        <div class="error-content">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
          <h3>Unable to Load Dashboard</h3>
          <p>{{ error }}</p>
          <button @click="retryFetch" class="btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M23 4v6h-6"></path>
              <path d="M1 20v-6h6"></path>
              <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10"></path>
              <path d="M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path>
            </svg>
            Try Again
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
  name: 'EmployeeDashboard',
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  data() {
    const now = new Date()
    return {
      pageName: 'Employee Dashboard',
      stats: {},
      loading: false,
      error: null,
      retryCount: 0,
      selectedPeriod: 'month',
      currentDay: now.toLocaleDateString('en-US', { weekday: 'long' }),
      currentDateNum: now.getDate(),
      currentMonthYear: now.toLocaleDateString('en-US', { month: 'short', year: 'numeric' })
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
     
      // Refresh data every 5 minutes
      this.refreshInterval = setInterval(() => {
        this.fetchDashboardData(true)
      }, 300000)
    },
    async fetchDashboardData(silent = false) {
      if (!silent) {
        this.loading = true
      }
      this.error = null
     
      try {
        const response = await axios.get('/api/dashboard')
       
        if (response.data.role === 'employee' && response.data.stats) {
          this.stats = response.data.stats
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
    handleApiError(err) {
      this.error = err.response?.data?.message || 'Failed to load dashboard data.'
    },
    retryFetch() {
        this.fetchDashboardData()
    },
    getTotalLeaveBalance() {
      if (!this.stats.leave_balances) return 0
      
      return Object.values(this.stats.leave_balances).reduce((sum, item) => {
        const val = typeof item === 'object' ? item.available : item;
        return sum + (Number(val) || 0);
      }, 0)
    },
    formatHours(hours) {
      if (!hours) return '0.00'
      return parseFloat(hours).toFixed(2)
    },
    formatLeaveType(type) {
      if (!type) return 'N/A'
      const typeMap = {
        'annual': 'Annual',
        'sick': 'Sick',
        'maternity': 'Maternity',
        'paternity': 'Paternity',
        'bereavement': 'Bereavement',
        'unpaid': 'Unpaid'
      }
      return typeMap[type.toLowerCase()] || type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
    },
    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric'
      })
    },
    calculateProgress(balanceData) {
      if (typeof balanceData !== 'object') {
        return 50
      }
      
      const total = balanceData.total || 1
      const available = balanceData.available || 0
      const percentage = (available / total) * 100
      return Math.min(percentage, 100)
    },
    calculateAttendancePercentage(type) {
      if (!this.stats.attendance_summary) return 0
      
      const present = this.stats.attendance_summary.present_days || 0
      const absent = this.stats.attendance_summary.absent_days || 0
      const late = this.stats.attendance_summary.late_days || 0
      const total = present + absent + late
      
      if (total === 0) return 0
      
      switch(type) {
        case 'present': return Math.round((present / total) * 100)
        case 'absent': return Math.round((absent / total) * 100)
        case 'late': return Math.round((late / total) * 100)
        default: return 0
      }
    },
    getLeaveColor(type) {
      const colors = {
        'annual': '#10b981',
        'sick': '#3b82f6',
        'maternity': '#8b5cf6',
        'paternity': '#f59e0b',
        'bereavement': '#64748b',
        'unpaid': '#ef4444'
      }
      return colors[type.toLowerCase()] || '#6366f1'
    },
    getUserInitials() {
      const name = this.authStore.user?.name || 'Employee'
      return name.split(' ').map(n => n[0]).join('').toUpperCase()
    }
  },
  beforeUnmount() {
    if (this.refreshInterval) {
      clearInterval(this.refreshInterval)
    }
  }
}
</script>

<style scoped>
.dashboard-container {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}

.dashboard-header {
  background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
  color: white;
  padding: 1.5rem 2rem;
  position: relative;
  overflow: hidden;
}

.dashboard-header::after {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  width: 200px;
  height: 200px;
  background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.1) 0%, transparent 70%);
}

.header-top {
  max-width: 1200px;
  margin: 0 auto;
}

.user-greeting {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1.5rem;
}

.avatar-section {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.avatar {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 1.125rem;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.user-info {
  display: flex;
  flex-direction: column;
}

.greeting {
  margin: 0 0 0.25rem;
  font-size: 1.5rem;
  font-weight: 700;
  background: linear-gradient(135deg, #fff 0%, #e2e8f0 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.subtitle {
  margin: 0;
  color: #cbd5e1;
  font-size: 0.875rem;
}

.date-badge {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(8px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 12px;
  padding: 0.75rem 1rem;
  min-width: 120px;
}

.date-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.day {
  font-size: 1.125rem;
  font-weight: 700;
  color: #f1f5f9;
}

.date-details {
  display: flex;
  flex-direction: column;
}

.date {
  font-size: 1rem;
  font-weight: 700;
}

.month-year {
  font-size: 0.75rem;
  color: #cbd5e1;
}

.dashboard-main {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1.5rem 2rem 3rem;
  position: relative;
  z-index: 1;
}

/* Stats Grid - Compact Cards */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 1.25rem;
  box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.03);
  border: 1px solid #e2e8f0;
  transition: all 0.2s ease;
  position: relative;
  overflow: hidden;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.08);
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

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.icon-wrapper {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon-wrapper svg {
  color: var(--accent);
}

.trend-indicator {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.2rem 0.4rem;
  border-radius: 10px;
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.stat-card h3 {
  margin: 0 0 0.25rem;
  font-size: 0.75rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  font-weight: 600;
}

.stat-value {
  font-size: 1.75rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1;
  margin-bottom: 0.5rem;
}

.card-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.75rem;
  color: #94a3b8;
  padding-top: 0.5rem;
  border-top: 1px solid #f1f5f9;
}

.compare {
  font-weight: 500;
  color: #64748b;
  font-size: 0.7rem;
}

/* Content Grid - Compact Cards */
.content-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

@media (max-width: 1024px) {
  .content-grid {
    grid-template-columns: 1fr;
  }
}

.content-card {
  background: white;
  border-radius: 16px;
  padding: 1.25rem;
  box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.03);
  border: 1px solid #e2e8f0;
  margin-bottom: 1.25rem;
}

.card-title-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.card-title-bar h2 {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e293b;
}

.period-select {
  padding: 0.375rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: white;
  color: #475569;
  font-size: 0.75rem;
  cursor: pointer;
  height: 32px;
}

.year-badge {
  background: #f1f5f9;
  color: #475569;
  padding: 0.25rem 0.5rem;
  border-radius: 10px;
  font-size: 0.7rem;
  font-weight: 600;
}

.action-link {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  color: #6366f1;
  text-decoration: none;
  font-weight: 500;
  font-size: 0.75rem;
  transition: color 0.2s;
}

.action-link:hover {
  color: #4f46e5;
}

/* Attendance Breakdown - Compact */
.attendance-breakdown {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.breakdown-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.625rem;
  background: #f8fafc;
  border-radius: 10px;
}

.metric {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
}

.dot.present { background: #10b981; }
.dot.absent { background: #ef4444; }
.dot.late { background: #f59e0b; }
.dot.total { background: #6366f1; }

.label {
  font-weight: 500;
  color: #475569;
  font-size: 0.875rem;
}

.values {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.value {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.875rem;
}

.percentage {
  font-weight: 700;
  color: #64748b;
  min-width: 36px;
  text-align: right;
  font-size: 0.875rem;
}

.attendance-progress {
  width: 100%;
}

.progress-bar {
  height: 6px;
  background: #e2e8f0;
  border-radius: 3px;
  display: flex;
  overflow: hidden;
}

.progress-segment {
  height: 100%;
  transition: width 0.3s ease;
}

/* Leave Balances - Compact */
.leave-balances-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.leave-balance-item {
  display: flex;
  flex-direction: column;
  gap: 0.375rem;
}

.leave-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.leave-type {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.875rem;
}

.leave-stats {
  font-weight: 700;
  color: #6366f1;
  font-size: 0.75rem;
}

.progress-container {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.progress-track {
  flex: 1;
  height: 4px;
  background: #e2e8f0;
  border-radius: 2px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  border-radius: 2px;
  transition: width 0.5s ease;
}

.percentage {
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  min-width: 28px;
}

/* Table Styles - Compact */
.table-container {
  overflow-x: auto;
  margin: 0 -0.5rem;
  padding: 0 0.5rem;
}

.data-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  font-size: 0.875rem;
}

.data-table th {
  padding: 0.625rem 0.75rem;
  text-align: left;
  font-weight: 600;
  color: #64748b;
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #e2e8f0;
  background: #f8fafc;
  white-space: nowrap;
}

.data-table td {
  padding: 0.75rem;
  border-bottom: 1px solid #f1f5f9;
}

.data-table tr:hover {
  background: #f8fafc;
}

.type-badge {
  padding: 0.2rem 0.5rem;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: 600;
  white-space: nowrap;
}

.date-range {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.75rem;
}

.separator {
  color: #cbd5e1;
  font-size: 0.75rem;
}

.days-cell {
  font-weight: 700;
  color: #1e293b;
}

.status-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  white-space: nowrap;
}

/* Payslip Card - Compact */
.payslip-card {
  display: flex;
  flex-direction: column;
}

.payslip-content {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.payslip-icon {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6366f1;
  flex-shrink: 0;
}

.payslip-details {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.payslip-detail {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.875rem;
}

.payslip-detail .label {
  color: #64748b;
  font-size: 0.75rem;
}

.payslip-detail .value {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.875rem;
}

.payslip-detail .highlight {
  color: #10b981;
  font-weight: 700;
}

.payslip-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: auto;
}

.btn-outline, .btn-primary {
  padding: 0.5rem 1rem;
  border-radius: 10px;
  font-weight: 600;
  font-size: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.375rem;
  flex: 1;
  height: 36px;
}

.btn-outline {
  background: white;
  border: 1.5px solid #e2e8f0;
  color: #475569;
}

.btn-outline:hover {
  border-color: #cbd5e1;
  background: #f8fafc;
}

.btn-primary {
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  border: none;
  color: white;
  box-shadow: 0 1px 2px rgba(99, 102, 241, 0.2);
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(99, 102, 241, 0.3);
}

/* Empty States - Compact */
.empty-state {
  padding: 2rem 1.5rem;
  text-align: center;
  color: #94a3b8;
}

.empty-state svg {
  margin-bottom: 0.75rem;
  color: #e2e8f0;
}

.empty-state p {
  margin: 0;
  font-size: 0.875rem;
}

/* Loading State */
.loading-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 300px;
}

.spinner-container {
  text-align: center;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e2e8f0;
  border-top: 3px solid #6366f1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 0.75rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Error State */
.error-container {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 300px;
}

.error-content {
  text-align: center;
  max-width: 320px;
  padding: 1.5rem;
}

.error-content svg {
  margin-bottom: 1rem;
  color: #ef4444;
}

.error-content h3 {
  margin: 0 0 0.5rem;
  color: #dc2626;
  font-size: 1.25rem;
}

.error-content p {
  margin: 0 0 1rem;
  color: #7f1d1d;
  font-size: 0.875rem;
}

/* Responsive Design */
@media (max-width: 768px) {
  .dashboard-header {
    padding: 1rem;
  }
  
  .dashboard-main {
    padding: 1rem;
  }
  
  .user-greeting {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .greeting {
    font-size: 1.25rem;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }
  
  .content-grid {
    gap: 1rem;
  }
  
  .payslip-content {
    flex-direction: column;
    text-align: center;
    gap: 0.75rem;
  }
  
  .payslip-actions {
    flex-direction: column;
  }
  
  .data-table {
    font-size: 0.75rem;
  }
  
  .data-table th,
  .data-table td {
    padding: 0.5rem;
  }
}

@media (max-width: 480px) {
  .dashboard-header {
    padding: 0.75rem;
  }
  
  .dashboard-main {
    padding: 0.75rem;
  }
  
  .content-card {
    padding: 1rem;
  }
  
  .breakdown-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
  
  .values {
    width: 100%;
    justify-content: space-between;
  }
  
  .avatar {
    width: 40px;
    height: 40px;
    font-size: 1rem;
  }
  
  .stat-value {
    font-size: 1.5rem;
  }
}
</style>