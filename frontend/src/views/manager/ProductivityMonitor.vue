<template>
  <div class="productivity-monitor">
    <div class="page-header">
      <h1>Team Productivity Monitor</h1>
    </div>

    <!-- Authentication Check -->
    <div v-if="!authStore.isAuthenticated" class="error-message">
      Please log in to access productivity monitor.
    </div>

    <!-- Permission Check -->
    <div v-else-if="!authStore.isManager" class="error-message">
      You don't have permission to access this page.
    </div>

    <!-- Loading State -->
    <div v-else-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Loading productivity data...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-message">
      {{ error }}
      <button @click="retryFetch" class="btn-primary" style="margin-top: 1rem;">Retry</button>
    </div>

    <!-- Productivity Overview -->
    <div v-else class="overview-section" v-if="teamOverview">
      <div class="overview-card">
        <h3>Team Overview</h3>
        <div class="overview-metrics">
          <div class="metric">
            <span class="metric-value">{{ teamOverview.avgProductivityScore }}%</span>
            <span class="metric-label">Average Score</span>
          </div>
          <div class="metric">
            <span class="metric-value">{{ teamOverview.totalTasksCompleted }}</span>
            <span class="metric-label">Tasks Completed</span>
          </div>
          <div class="metric">
            <span class="metric-value">{{ formatHours(teamOverview.totalHoursWorked) }}</span>
            <span class="metric-label">Total Hours</span>
          </div>
          <div class="metric">
            <span class="metric-value">{{ teamOverview.completionRate }}%</span>
            <span class="metric-label">Completion Rate</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Employees Productivity List -->
    <div class="employees-grid">
      <div v-for="employee in productivityData" :key="employee.id" class="employee-card">
        <div class="employee-header">
          <div class="employee-avatar">
            {{ getInitials(employee.full_name || employee.user?.name) }}
          </div>
          <div class="employee-info">
            <h3>{{ employee.full_name || employee.user?.name }}</h3>
            <p class="employee-id">{{ employee.employee_id }}</p>
          </div>
          <div class="productivity-score">
            {{ employee.productivity_score }}%
            <span class="score-trend" :class="getTrendClass(employee.trend)">
              {{ getTrendIcon(employee.trend) }}
            </span>
          </div>
        </div>
        
        <div class="productivity-details">
          <div class="detail-row">
            <span class="label">📋 Tasks:</span>
            <span>{{ employee.tasks_completed }} / {{ employee.total_tasks }}</span>
          </div>
          <div class="detail-row">
            <span class="label">⏱️ Hours:</span>
            <span>{{ formatHours(employee.hours_worked) }}</span>
          </div>
          <div class="detail-row">
            <span class="label">📊 Efficiency:</span>
            <span>{{ employee.efficiency_rate }}%</span>
          </div>
          <div class="detail-row">
            <span class="label">📈 Period:</span>
            <span>{{ formatDateRange(employee.period_start, employee.period_end) }}</span>
          </div>
        </div>

        <div class="productivity-actions">
          <button @click="viewDetails(employee)" class="btn-view">View Details</button>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="productivityData.length === 0" class="empty-state">
        <p>No productivity data available</p>
        <p>Productivity tracking may not be enabled for your team yet.</p>
      </div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

export default {
  name: 'ProductivityMonitor',
  
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  
  data() {
    return {
      productivityData: [],
      teamOverview: null,
      loading: false,
      error: null,
      retryCount: 0  // For retry logic
    }
  },
  
  mounted() {
    this.initializeComponent()
  },
  
  methods: {
    initializeComponent() {
      // Check authentication and permissions
      if (!this.authStore.isAuthenticated) {
        this.error = 'Please log in to access productivity monitor.'
        return
      }
      
      if (!this.authStore.isManager) {
        this.error = 'You do not have permission to access this page.'
        return
      }
      
      this.fetchProductivity()
    },
    
    async fetchProductivity(retry = false) {
      this.loading = true
      this.error = null
      
      try {
        console.log('Fetching productivity data... (retry:', retry, ')')
        console.log('Auth token:', this.authStore.token ? 'Present' : 'Missing')
        console.log('User role:', this.authStore.userRole)
        console.log('Manager ID:', this.authStore.user?.id)
        
        // Use manager-specific endpoint to fetch team productivity
        const response = await axios.get('/api/manager/reports/productivity', {
          params: { 
            manager_id: this.authStore.user?.id,
            period: 'last_30_days'  // Example period, can be dynamic
          }
        })
        
        console.log('Productivity response:', response.data)
        this.productivityData = response.data.employees || []
        this.teamOverview = response.data.overview || null
      } catch (err) {
        console.error('Fetch error:', err)
        console.error('Error response:', err.response)
        console.error('Full error:', err.toJSON ? err.toJSON() : err)
        
        this.handleApiError(err)
      } finally {
        this.loading = false
      }
    },
    
    retryFetch() {
      this.retryCount++
      if (this.retryCount <= 3) {
        this.fetchProductivity(true)
      } else {
        this.error = 'Max retries exceeded. Check your network or server.'
      }
    },
    
    handleApiError(err) {
      let errorMsg = 'An unexpected error occurred. (Likely CORS - check console)'
      if (err.code === 'ERR_NETWORK' || err.message.includes('Network Error')) {
        errorMsg = 'Network/CORS error: Ensure backend allows requests from localhost:3000'
      } else if (err.response?.status === 401) {
        errorMsg = 'Your session has expired. Please log in again.'
        this.authStore.clearAuth()
        this.$router.push({ name: 'login' })
      } else if (err.response?.status === 403) {
        errorMsg = 'You do not have permission to perform this action.'
      } else {
        errorMsg = err.response?.data?.message || errorMsg
      }
      this.error = errorMsg
    },
    
    viewDetails(employee) {
      // Navigate to employee productivity details page (if exists)
      this.$router.push({ 
        name: 'employee-productivity-details', 
        params: { id: employee.id } 
      })
    },
    
    getInitials(name) {
      if (!name) return '??'
      return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2)
    },
    
    formatHours(hours) {
      if (!hours) return '0h'
      const wholeHours = Math.floor(hours)
      const minutes = Math.round((hours - wholeHours) * 60)
      return `${wholeHours}h ${minutes}m`
    },
    
    formatDateRange(start, end) {
      if (!start || !end) return 'N/A'
      const startDate = new Date(start).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
      const endDate = new Date(end).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
      return `${startDate} - ${endDate}`
    },
    
    getTrendClass(trend) {
      return trend === 'up' ? 'trend-up' : trend === 'down' ? 'trend-down' : 'trend-neutral'
    },
    
    getTrendIcon(trend) {
      return trend === 'up' ? '📈' : trend === 'down' ? '📉' : '➡️'
    }
  }
}
</script>

<style scoped>
.productivity-monitor {
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

.overview-section {
  margin-bottom: 2rem;
}

.overview-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  padding: 1.5rem;
}

.overview-card h3 {
  margin: 0 0 1rem 0;
  color: #2d3748;
  font-size: 1.25rem;
}

.overview-metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
}

.metric {
  text-align: center;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.metric-value {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
}

.metric-label {
  display: block;
  font-size: 0.875rem;
  color: #718096;
  margin-top: 0.25rem;
}

.employees-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.employee-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  padding: 1.5rem;
  transition: transform 0.2s, box-shadow 0.2s;
}

.employee-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 20px rgba(0,0,0,0.12);
}

.employee-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f0f0f0;
  position: relative;
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

.employee-info h3 {
  margin: 0 0 0.25rem 0;
  color: #2d3748;
  font-size: 1.1rem;
}

.employee-id {
  color: #718096;
  font-size: 0.875rem;
  margin: 0;
}

.productivity-score {
  position: absolute;
  right: 0;
  top: 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 0 0 0 8px;
  font-weight: 600;
  font-size: 1rem;
}

.score-trend {
  font-size: 0.875rem;
  margin-left: 0.25rem;
}

.trend-up {
  color: #52c41a;
}

.trend-down {
  color: #f5222d;
}

.trend-neutral {
  color: #fa8c16;
}

.productivity-details {
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

.productivity-actions {
  display: flex;
  gap: 0.5rem;
}

.productivity-actions button {
  flex: 1;
  padding: 0.75rem;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s;
  background: #e6f7ff;
  color: #1890ff;
}

.productivity-actions button:hover {
  transform: scale(1.05);
  background: #bae7ff;
}

.empty-state {
  grid-column: 1 / -1;
  text-align: center;
  padding: 4rem;
  color: #718096;
}

@media (max-width: 768px) {
  .employees-grid {
    grid-template-columns: 1fr;
  }
  
  .overview-metrics {
    grid-template-columns: 1fr;
  }
  
  .page-header {
    text-align: center;
  }
}
</style>