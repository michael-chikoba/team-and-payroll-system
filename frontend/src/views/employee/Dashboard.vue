<template>
  <div class="dashboard-container">
    <!-- Header Section -->
    <div class="dashboard-header">
      <div class="header-top">
        <div class="user-greeting">
          <div class="avatar-section">
            <div class="avatar">
              <img 
                v-if="profilePicUrl && profilePicLoaded" 
                :src="profilePicUrl" 
                alt="Profile" 
                class="profile-avatar-img"
                @load="handleImageLoad"
                @error="handleImageError"
              />
              <span v-else>{{ getUserInitials() }}</span>
            </div>
            <div class="user-info">
              <h1 class="greeting">Welcome back, {{ getUserName() }}!</h1>
              <p class="subtitle">
                <span v-if="employeeInfo" class="employee-details">
                  {{ getEmployeePosition() }} • {{ getEmployeeDepartment() }}
                </span>
                <span v-else>Track your attendance, leaves, and payroll in real-time</span>
              </p>
              <div v-if="employeeInfo" class="employee-id">
                <span class="id-badge">ID: {{ getEmployeeId() }}</span>
                <span v-if="employeeInfo.employment_type" class="employment-type">
                  {{ formatEmploymentType(employeeInfo.employment_type) }}
                </span>
              </div>
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
      <div class="stats-grid" v-if="!loading && statsLoaded">
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
          <div class="stat-value">{{ getPresentDays() }}</div>
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
          <div class="stat-value">{{ formatHours(getTotalHours()) }}</div>
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
          <div class="stat-value">{{ formatHours(getOvertimeHours()) }}</div>
          <div class="card-footer">
            <span>Extra hours</span>
            <span class="compare">This month</span>
          </div>
        </div>
      </div>

      <!-- Main Content Grid -->
      <div class="content-grid" v-if="!loading && statsLoaded">
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
                  <span class="value">{{ getPresentDays() }} days</span>
                  <span class="percentage">{{ calculateAttendancePercentage('present') }}%</span>
                </div>
              </div>
              <div class="breakdown-item">
                <div class="metric">
                  <span class="dot absent"></span>
                  <span class="label">Absent</span>
                </div>
                <div class="values">
                  <span class="value">{{ getAbsentDays() }} days</span>
                  <span class="percentage">{{ calculateAttendancePercentage('absent') }}%</span>
                </div>
              </div>
              <div class="breakdown-item">
                <div class="metric">
                  <span class="dot late"></span>
                  <span class="label">Late Arrivals</span>
                </div>
                <div class="values">
                  <span class="value">{{ getLateDays() }} days</span>
                  <span class="percentage">{{ calculateAttendancePercentage('late') }}%</span>
                </div>
              </div>
              <div class="breakdown-item">
                <div class="metric">
                  <span class="dot total"></span>
                  <span class="label">Total Hours</span>
                </div>
                <div class="values">
                  <span class="value">{{ formatHours(getTotalHours()) }} hrs</span>
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
            <div class="leave-balances-grid" v-if="hasLeaveBalances()">
              <div v-for="(balanceData, type) in stats.leave_balances" :key="type" class="leave-balance-item">
                <div class="leave-info">
                  <span class="leave-type">{{ formatLeaveType(type) }}</span>
                  <span class="leave-stats">{{ getAvailableDays(balanceData) }} of {{ getTotalDays(balanceData) }} days</span>
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
            <div class="table-container" v-if="hasRecentLeaves()">
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
                  <tr v-for="leave in getRecentLeaves()" :key="leave.id">
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
                    <td class="days-cell">{{ getLeaveDays(leave) }}</td>
                    <td>
                      <span :class="['status-badge', getLeaveStatusClass(leave.status)]">
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

          <!-- Quick Profile Card -->
          <div class="content-card profile-summary-card">
            <div class="card-title-bar">
              <h2>My Profile</h2>
              <router-link to="/employee/profile" class="action-link">
                View Profile
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M5 12h14"></path>
                  <path d="M12 5l7 7-7 7"></path>
                </svg>
              </router-link>
            </div>
            <div class="profile-summary-content" v-if="employeeInfo">
              <div class="profile-detail">
                <span class="detail-label">Full Name:</span>
                <span class="detail-value">{{ getFullName() }}</span>
              </div>
              <div class="profile-detail">
                <span class="detail-label">Employee ID:</span>
                <span class="detail-value badge">{{ getEmployeeId() }}</span>
              </div>
              <div class="profile-detail">
                <span class="detail-label">Department:</span>
                <span class="detail-value">{{ getEmployeeDepartment() }}</span>
              </div>
              <div class="profile-detail">
                <span class="detail-label">Position:</span>
                <span class="detail-value highlight">{{ getEmployeePosition() }}</span>
              </div>
              <div class="profile-detail" v-if="employeeInfo.hire_date">
                <span class="detail-label">Since:</span>
                <span class="detail-value">{{ formatDate(employeeInfo.hire_date) }}</span>
              </div>
            </div>
            <div class="profile-actions">
              <router-link to="/employee/profile" class="btn-outline">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Edit Profile
              </router-link>
            </div>
          </div>

          <!-- Payslip Card -->
          <div class="content-card payslip-card" v-if="hasUpcomingPayslip()">
            <div class="card-title-bar">
              <h2>Next Payslip</h2>
              <span :class="['status-badge', getPaydayStatusClass()]">{{ getPaydayStatusText() }}</span>
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
                  <span class="value">{{ formatPayPeriod(stats.upcoming_payslip.payroll_period) }}</span>
                </div>
                <div class="payslip-detail">
                  <span class="label">Processing Date:</span>
                  <span class="value">{{ formatPayslipDate(stats.upcoming_payslip.processing_date) }}</span>
                </div>
                <div class="payslip-detail">
                  <span class="label">Days Until Payday:</span>
                  <span class="value highlight">{{ getDaysUntilPayday() }}</span>
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
      stats: {
        attendance_summary: null,
        leave_balances: null,
        recent_leaves: null,
        upcoming_payslip: null
      },
      employeeInfo: null,
      profilePicUrl: null,
      profilePicLoaded: false,
      loading: false,
      error: null,
      retryCount: 0,
      selectedPeriod: 'month',
      currentDay: now.toLocaleDateString('en-US', { weekday: 'long' }),
      currentDateNum: now.getDate(),
      currentMonthYear: now.toLocaleDateString('en-US', { month: 'short', year: 'numeric' }),
      statsLoaded: false,
      refreshInterval: null
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
      this.fetchEmployeeProfile()
     
      // Refresh data every 5 minutes
      this.refreshInterval = setInterval(() => {
        this.fetchDashboardData(true)
      }, 300000)
    },
    async fetchDashboardData(silent = false) {
      if (!silent) {
        this.loading = true
        this.statsLoaded = false
      }
      this.error = null
     
      try {
        const response = await axios.get('/api/dashboard')
       
        if (response.data.role === 'employee' && response.data.stats) {
          // Initialize stats with default values
          this.stats = {
            attendance_summary: response.data.stats.attendance_summary || {},
            leave_balances: response.data.stats.leave_balances || {},
            recent_leaves: response.data.stats.recent_leaves || [],
            upcoming_payslip: response.data.stats.upcoming_payslip || null
          }
          
          // Fix the payslip data if it has invalid dates
          if (this.stats.upcoming_payslip) {
            this.fixPayslipData()
          }
          
          this.statsLoaded = true
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
    
    // Fix payslip data with invalid dates
    fixPayslipData() {
      if (!this.stats.upcoming_payslip) return
      
      const payslip = this.stats.upcoming_payslip
      
      // If processing_date is invalid, try to parse it from payroll_period
      if (!this.isValidDate(payslip.processing_date) && payslip.payroll_period) {
        // Try to extract date from payroll_period format like "2012-01"
        const match = payslip.payroll_period.match(/^(\d{4})-(\d{2})$/)
        if (match) {
          const year = parseInt(match[1])
          const month = parseInt(match[2]) - 1 // JavaScript months are 0-indexed
          
          // Create a date for the end of the pay period (last day of month)
          const endOfMonth = new Date(year, month + 1, 0)
          
          // Set processing date to 3 days after end of pay period (typical payroll processing)
          const processingDate = new Date(endOfMonth)
          processingDate.setDate(endOfMonth.getDate() + 3)
          
          payslip.processing_date = processingDate.toISOString().split('T')[0]
        }
      }
      
      // Fix estimated_days if it's invalid
      if (typeof payslip.estimated_days === 'number' && (payslip.estimated_days < 0 || !Number.isInteger(payslip.estimated_days))) {
        // Use our frontend calculation instead
        payslip.estimated_days = this.getDaysUntilPaydayValue()
      }
    },
    
    // Check if a date string is valid
    isValidDate(dateString) {
      if (!dateString) return false
      
      try {
        const date = new Date(dateString)
        return !isNaN(date.getTime()) && date.toString() !== 'Invalid Date'
      } catch (error) {
        return false
      }
    },
    
    async fetchEmployeeProfile() {
      try {
        const response = await axios.get('/api/profile')
        const user = response.data.user || response.data
        const employee = response.data.employee?.data || response.data.employee
        
        if (employee) {
          this.employeeInfo = {
            first_name: user.first_name || '',
            last_name: user.last_name || '',
            employee_id: employee.employee_id || '',
            position: employee.position || '',
            department: employee.department || '',
            employment_type: employee.employment_type || '',
            hire_date: employee.hire_date || '',
            phone: employee.phone || '',
            email: user.email || ''
          }
          
          if (employee.profile_pic) {
            this.profilePicUrl = `/storage/${employee.profile_pic}`
          }
        }
      } catch (err) {
        console.error('Profile fetch error:', err)
      }
    },
    handleApiError(err) {
      this.error = err.response?.data?.message || 'Failed to load dashboard data.'
    },
    handleImageLoad() {
      this.profilePicLoaded = true
    },
    handleImageError() {
      this.profilePicLoaded = false
      this.profilePicUrl = null
    },
    retryFetch() {
      this.fetchDashboardData()
      this.fetchEmployeeProfile()
    },
    
    // Payslip date calculations - FIXED
    getDaysUntilPaydayValue() {
      if (!this.stats.upcoming_payslip?.processing_date) {
        return 0
      }
      
      try {
        const processingDate = new Date(this.stats.upcoming_payslip.processing_date)
        const today = new Date()
        
        // Reset both dates to midnight for accurate day calculation
        today.setHours(0, 0, 0, 0)
        processingDate.setHours(0, 0, 0, 0)
        
        // Calculate difference in days
        const diffTime = processingDate - today
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
        
        return Math.max(diffDays, 0)
      } catch (error) {
        console.error('Error calculating days until payday:', error)
        return 0
      }
    },
    
    getDaysUntilPayday() {
      const days = this.getDaysUntilPaydayValue()
      
      if (days === 0) {
        return 'Today'
      } else if (days === 1) {
        return '1 day'
      } else if (days < 0) {
        return 'Past due'
      } else {
        return `${days} days`
      }
    },
    
    getPaydayStatusClass() {
      const days = this.getDaysUntilPaydayValue()
      
      if (days === 0) {
        return 'today'
      } else if (days < 0) {
        return 'past-due'
      } else if (days <= 7) {
        return 'upcoming'
      } else {
        return 'upcoming'
      }
    },
    
    getPaydayStatusText() {
      const days = this.getDaysUntilPaydayValue()
      
      if (days === 0) {
        return 'Today'
      } else if (days < 0) {
        return 'Past Due'
      } else if (days <= 7) {
        return 'Upcoming'
      } else {
        return 'Upcoming'
      }
    },
    
    // Format pay period (e.g., "2012-01" -> "Jan 2012")
    formatPayPeriod(period) {
      if (!period) return 'N/A'
      
      try {
        // Handle format like "2012-01"
        const match = period.match(/^(\d{4})-(\d{2})$/)
        if (match) {
          const year = match[1]
          const month = parseInt(match[2]) - 1
          const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
          return `${monthNames[month]} ${year}`
        }
        
        // Handle other formats
        return period
      } catch (error) {
        console.error('Error formatting pay period:', error)
        return period || 'N/A'
      }
    },
    
    // Special date formatter for payslip dates
    formatPayslipDate(date) {
      if (!date) return 'N/A'
      
      try {
        const dateObj = new Date(date)
        
        if (isNaN(dateObj.getTime())) {
          // Try alternative parsing if standard parsing fails
          const parts = date.split('-')
          if (parts.length === 3) {
            const [year, month, day] = parts.map(Number)
            const altDate = new Date(year, month - 1, day)
            if (!isNaN(altDate.getTime())) {
              return altDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
              })
            }
          }
          return 'Invalid Date'
        }
        
        return dateObj.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        })
      } catch (e) {
        console.error('Date formatting error:', e)
        return 'Invalid Date'
      }
    },
    
    // Safe getter methods for stats
    getPresentDays() {
      return this.stats.attendance_summary?.present_days || 0
    },
    getAbsentDays() {
      return this.stats.attendance_summary?.absent_days || 0
    },
    getLateDays() {
      return this.stats.attendance_summary?.late_days || 0
    },
    getTotalHours() {
      return this.stats.attendance_summary?.total_hours || 0
    },
    getOvertimeHours() {
      return this.stats.attendance_summary?.overtime_hours || 0
    },
    
    getTotalLeaveBalance() {
      if (!this.stats.leave_balances) return 0
      
      return Object.values(this.stats.leave_balances).reduce((sum, item) => {
        const val = typeof item === 'object' ? item.available : item;
        return sum + (Number(val) || 0);
      }, 0)
    },
    
    // Safe getter methods for employee info
    getUserName() {
      return this.authStore.user?.name || this.employeeInfo?.first_name || 'Employee'
    },
    getFullName() {
      if (!this.employeeInfo) return 'N/A'
      return `${this.employeeInfo.first_name || ''} ${this.employeeInfo.last_name || ''}`.trim() || 'N/A'
    },
    getEmployeeId() {
      return this.employeeInfo?.employee_id || 'N/A'
    },
    getEmployeePosition() {
      return this.employeeInfo?.position || 'N/A'
    },
    getEmployeeDepartment() {
      return this.employeeInfo?.department || 'N/A'
    },
    
    // Safe getter methods for leave balances
    getAvailableDays(balanceData) {
      if (!balanceData) return 0
      return typeof balanceData === 'object' ? balanceData.available || 0 : Number(balanceData) || 0
    },
    getTotalDays(balanceData) {
      if (!balanceData) return 1
      return typeof balanceData === 'object' ? balanceData.total || 1 : Number(balanceData) || 1
    },
    
    // Check methods
    hasLeaveBalances() {
      return this.stats.leave_balances && Object.keys(this.stats.leave_balances).length > 0
    },
    hasRecentLeaves() {
      return this.stats.recent_leaves && this.stats.recent_leaves.length > 0
    },
    hasUpcomingPayslip() {
      if (!this.stats.upcoming_payslip) {
        return false
      }
      
      // Check if we have at least a pay period
      return !!this.stats.upcoming_payslip.payroll_period
    },
    
    // Getter methods with limits
    getRecentLeaves() {
      if (!this.stats.recent_leaves) return []
      return this.stats.recent_leaves.slice(0, 5)
    },
    getLeaveDays(leave) {
      return leave.number_of_days || leave.total_days || 0
    },
    getLeaveStatusClass(status) {
      if (!status) return ''
      return status.toLowerCase()
    },
    
    formatHours(hours) {
      if (!hours) return '0.00'
      const num = parseFloat(hours)
      return isNaN(num) ? '0.00' : num.toFixed(2)
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
      
      try {
        // Handle string dates that might have timezone issues
        const dateObj = new Date(date)
        
        // Check if date is valid
        if (isNaN(dateObj.getTime())) {
          // Try alternative parsing
          const parts = date.split('-')
          if (parts.length === 3) {
            const [year, month, day] = parts.map(Number)
            const altDate = new Date(year, month - 1, day)
            if (!isNaN(altDate.getTime())) {
              return altDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
              })
            }
          }
          return 'Invalid Date'
        }
        
        return dateObj.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric'
        })
      } catch (e) {
        console.error('Date formatting error:', e)
        return 'Invalid Date'
      }
    },
    formatEmploymentType(type) {
      if (!type) return 'N/A'
      return type.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
    },
    calculateProgress(balanceData) {
      if (!balanceData) return 0
      
      const total = this.getTotalDays(balanceData)
      const available = this.getAvailableDays(balanceData)
      
      if (total === 0) return 0
      
      const percentage = (available / total) * 100
      return Math.min(Math.max(percentage, 0), 100)
    },
    calculateAttendancePercentage(type) {
      if (!this.stats.attendance_summary) return 0
      
      const present = this.getPresentDays()
      const absent = this.getAbsentDays()
      const late = this.getLateDays()
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
      if (!type) return '#6366f1'
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
      if (this.employeeInfo) {
        const first = this.employeeInfo.first_name?.[0] || ''
        const last = this.employeeInfo.last_name?.[0] || ''
        return `${first}${last}`.toUpperCase() || 'E'
      }
      const name = this.authStore.user?.name || 'Employee'
      const initials = name.split(' ').map(n => n[0]).join('')
      return initials.toUpperCase() || 'E'
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
/* Add these new styles to the existing CSS */

/* Profile image in avatar */
.profile-avatar-img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}

/* Employee details in subtitle */
.employee-details {
  font-weight: 500;
  color: #e2e8f0;
}

.employee-id {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-top: 0.25rem;
}

.id-badge {
  background: rgba(255, 255, 255, 0.15);
  padding: 0.125rem 0.5rem;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 600;
  color: #f1f5f9;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.employment-type {
  font-size: 0.7rem;
  color: #cbd5e1;
  font-weight: 500;
}

/* Profile Summary Card */
.profile-summary-card {
  display: flex;
  flex-direction: column;
}

.profile-summary-content {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1rem;
}

.profile-detail {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.875rem;
}

.profile-detail .detail-label {
  color: #64748b;
  font-size: 0.75rem;
  font-weight: 500;
}

.profile-detail .detail-value {
  font-weight: 600;
  color: #1e293b;
  font-size: 0.875rem;
}

.profile-detail .detail-value.badge {
  background: #f1f5f9;
  padding: 0.125rem 0.5rem;
  border-radius: 6px;
  font-size: 0.7rem;
  color: #475569;
}

.profile-detail .detail-value.highlight {
  color: #6366f1;
  font-weight: 700;
}

.profile-actions {
  margin-top: auto;
  padding-top: 1rem;
  border-top: 1px solid #f1f5f9;
}

/* Payday status badges */
.status-badge.upcoming {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
  border: 1px solid rgba(16, 185, 129, 0.2);
}

.status-badge.today {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
  border: 1px solid rgba(245, 158, 11, 0.2);
}

.status-badge.past-due {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  border: 1px solid rgba(239, 68, 68, 0.2);
}

/* Update existing styles for better spacing */
.user-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
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

/* Responsive adjustments */
@media (max-width: 768px) {
  .employee-id {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
  }
  
  .profile-detail {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.125rem;
  }
}

@media (max-width: 480px) {
  .employee-details {
    font-size: 0.75rem;
  }
  
  .profile-detail .detail-label,
  .profile-detail .detail-value {
    font-size: 0.75rem;
  }
}

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