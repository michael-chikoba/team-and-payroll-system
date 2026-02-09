<template>
  <div class="productivity-monitor">
    <div class="header-card">
      <div class="card-header">
        <h1 class="card-title">Team Productivity Monitor</h1>
        <button 
          @click="exportReport" 
          class="btn-export" 
          :disabled="loading || productivityData.length === 0"
        >
          📊 Export Report
        </button>
      </div>
      
      <div class="header-subtitle">
        <span class="role-badge">{{ userRole }}</span>
        <span class="business-info" v-if="businessName">{{ businessName }}</span>
      </div>
      
      <div class="header-actions">
        <div class="period-selector">
          <select v-model="selectedPeriod" @change="onPeriodChange">
            <option value="last_7_days">Last 7 Days</option>
            <option value="last_30_days">Last 30 Days</option>
            <option value="this_month">This Month</option>
            <option value="last_month">Last Month</option>
            <option value="custom">Custom Range</option>
          </select>

          <div v-if="selectedPeriod === 'custom'" class="custom-date-range">
            <input type="date" v-model="customStartDate" @change="onCustomDateChange" :max="today">
            <span>to</span>
            <input type="date" v-model="customEndDate" @change="onCustomDateChange" :max="today" :min="customStartDate">
          </div>
        </div>
        
        <div v-if="periodInfo" class="period-info">
          <span class="period-label">Showing data from:</span>
          <strong>{{ periodInfo.start }}</strong> to <strong>{{ periodInfo.end }}</strong>
        </div>
      </div>
    </div>

    <!-- Authentication Check -->
    <div v-if="!authStore.isAuthenticated" class="error-message">
      <div class="error-content">
        <h3>Authentication Required</h3>
        <p>Please log in to access productivity monitor.</p>
        <button @click="$router.push('/login')" class="btn-primary">Go to Login</button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-else-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Loading productivity data for {{ formatPeriodText() }}...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-message">
      <div class="error-content">
        <h3>Unable to Load Productivity Data</h3>
        <p>{{ error }}</p>
        <div class="error-actions">
          <button @click="retryFetch" class="btn-primary">Retry</button>
          <button @click="fetchMockData" class="btn-secondary">
            Load Sample Data
          </button>
        </div>
      </div>
    </div>

    <!-- Demo Mode Warning -->
    <div v-if="isDemoMode && !loading && !error" class="demo-warning">
      <div class="warning-content">
        <span class="warning-icon">⚠️</span>
        <div class="warning-text">
          <strong>Demo Mode</strong>
          <p>Showing sample data. Real productivity data will be available when tasks and tickets are recorded in the system.</p>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div v-if="!loading && !error && authStore.isAuthenticated" class="productivity-content">
      <!-- Productivity Overview -->
      <div class="overview-section">
        <div class="overview-grid">
          <div class="overview-card main-overview">
            <h3>Team Overview</h3>
            <div class="overview-metrics">
              <div class="metric">
                <span class="metric-value">{{ teamOverview.avgProductivityScore }}%</span>
                <span class="metric-label">Average Score</span>
                <div class="metric-trend" :class="getTrendClass(teamOverview.trend)">
                  {{ getTrendIcon(teamOverview.trend) }} {{ Math.abs(teamOverview.trend_percentage) }}%
                </div>
              </div>
              <div class="metric">
                <span class="metric-value">{{ teamOverview.totalTasksCompleted }}</span>
                <span class="metric-label">Tasks Completed</span>
                <div class="metric-sub">
                  {{ teamOverview.tasksOnTime }} on time ({{ teamOverview.onTimeRate }}%)
                </div>
              </div>
              <div class="metric">
                <span class="metric-value">{{ teamOverview.slaCompliance }}%</span>
                <span class="metric-label">SLA Compliance</span>
                <div class="metric-sub">
                  {{ teamOverview.slaMet }} of {{ teamOverview.totalSLA }} met
                </div>
              </div>
              <div class="metric">
                <span class="metric-value">{{ formatHours(teamOverview.avgHoursPerTask) }}</span>
                <span class="metric-label">Avg Hours/Task</span>
                <div class="metric-sub">
                  {{ teamOverview.efficiencyRate }}% efficiency
                </div>
              </div>
            </div>
          </div>

          <!-- Performance Summary -->
          <div class="performance-summary">
            <h3>Performance Summary</h3>
            <div class="summary-grid">
              <div class="summary-item">
                <div class="summary-icon overdue">⏰</div>
                <div class="summary-content">
                  <div class="summary-value">{{ teamOverview.overdueTasks }}</div>
                  <div class="summary-label">Overdue Tasks</div>
                </div>
              </div>
              <div class="summary-item">
                <div class="summary-icon pending">⏳</div>
                <div class="summary-content">
                  <div class="summary-value">{{ teamOverview.pendingTasks }}</div>
                  <div class="summary-label">Pending Tasks</div>
                </div>
              </div>
              <div class="summary-item">
                <div class="summary-icon completed">✅</div>
                <div class="summary-content">
                  <div class="summary-value">{{ teamOverview.completedTasks }}</div>
                  <div class="summary-label">Completed Tasks</div>
                </div>
              </div>
              <div class="summary-item">
                <div class="summary-icon efficiency">📈</div>
                <div class="summary-content">
                  <div class="summary-value">{{ teamOverview.efficiencyRate }}%</div>
                  <div class="summary-label">Efficiency Rate</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="charts-section" v-if="showCharts && hasChartData">
        <div class="chart-card" v-if="slaChartData">
          <h3>SLA Compliance Trend</h3>
          <div class="chart-container">
            <LineChart :data="slaChartData" :options="slaChartOptions" />
          </div>
        </div>
        
        <div class="chart-card" v-if="taskDistributionData">
          <h3>Task Completion Distribution</h3>
          <div class="chart-container">
            <DoughnutChart :data="taskDistributionData" :options="taskDistributionOptions" />
          </div>
        </div>
        
        <div class="chart-card" v-if="weeklyPerformanceData">
          <h3>Weekly Performance</h3>
          <div class="chart-container">
            <BarChart :data="weeklyPerformanceData" :options="weeklyPerformanceOptions" />
          </div>
        </div>
        
        <div class="chart-card" v-if="timelineData">
          <h3>Performance Timeline</h3>
          <div class="chart-container">
            <LineChart :data="timelineData" :options="timelineOptions" />
          </div>
        </div>
      </div>

      <!-- Detailed Metrics -->
      <div class="detailed-metrics" v-if="showDetailedMetrics">
        <div class="metrics-card">
          <h3>SLA Performance Details</h3>
          <div class="sla-details">
            <div class="sla-metric">
              <div class="sla-header">
                <span class="sla-title">Response Time SLA</span>
                <span class="sla-rate">{{ slaDetails.responseTimeRate }}%</span>
              </div>
              <div class="sla-progress">
                <div class="progress-bar">
                  <div class="progress-fill" :style="{ width: slaDetails.responseTimeRate + '%' }"></div>
                </div>
                <div class="sla-stats">
                  <span>{{ slaDetails.responseTimeMet }} of {{ slaDetails.responseTimeTotal }} met</span>
                  <span class="avg-time">Avg: {{ formatHours(slaDetails.avgResponseTime) }}</span>
                </div>
              </div>
            </div>
            
            <div class="sla-metric">
              <div class="sla-header">
                <span class="sla-title">Resolution Time SLA</span>
                <span class="sla-rate">{{ slaDetails.resolutionRate }}%</span>
              </div>
              <div class="sla-progress">
                <div class="progress-bar">
                  <div class="progress-fill" :style="{ width: slaDetails.resolutionRate + '%' }"></div>
                </div>
                <div class="sla-stats">
                  <span>{{ slaDetails.resolutionMet }} of {{ slaDetails.resolutionTotal }} met</span>
                  <span class="avg-time">Avg: {{ formatHours(slaDetails.avgResolutionTime) }}</span>
                </div>
              </div>
            </div>
            
            <div class="sla-metric">
              <div class="sla-header">
                <span class="sla-title">Task Completion SLA</span>
                <span class="sla-rate">{{ slaDetails.completionRate }}%</span>
              </div>
              <div class="sla-progress">
                <div class="progress-bar">
                  <div class="progress-fill" :style="{ width: slaDetails.completionRate + '%' }"></div>
                </div>
                <div class="sla-stats">
                  <span>{{ slaDetails.completionMet }} of {{ slaDetails.completionTotal }} met</span>
                  <span class="avg-time">Avg delay: {{ formatHours(slaDetails.avgDelay) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="metrics-card">
          <h3>Task Completion Analysis</h3>
          <div class="completion-analysis">
            <div class="analysis-row">
              <span class="label">On Time Completion:</span>
              <span class="value">{{ taskAnalysis.onTimeRate }}%</span>
              <div class="trend" :class="getTrendClass(taskAnalysis.onTimeTrend)">
                {{ getTrendIcon(taskAnalysis.onTimeTrend) }} {{ taskAnalysis.onTimeChange }}%
              </div>
            </div>
            <div class="analysis-row">
              <span class="label">Average Completion Time:</span>
              <span class="value">{{ formatHours(taskAnalysis.avgCompletionTime) }}</span>
              <div class="trend" :class="getTrendClass(taskAnalysis.completionTimeTrend)">
                {{ getTrendIcon(taskAnalysis.completionTimeTrend) }} {{ taskAnalysis.completionTimeChange }}%
              </div>
            </div>
            <div class="analysis-row">
              <span class="label">Quality Score:</span>
              <span class="value">{{ taskAnalysis.qualityScore }}%</span>
              <div class="trend" :class="getTrendClass(taskAnalysis.qualityTrend)">
                {{ getTrendIcon(taskAnalysis.qualityTrend) }} {{ taskAnalysis.qualityChange }}%
              </div>
            </div>
            <div class="analysis-row">
              <span class="label">Reopen Rate:</span>
              <span class="value">{{ taskAnalysis.reopenRate }}%</span>
              <div class="trend" :class="getTrendClass(taskAnalysis.reopenTrend)">
                {{ getTrendIcon(taskAnalysis.reopenTrend) }} {{ taskAnalysis.reopenChange }}%
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Employees Productivity List -->
      <div class="employees-section" v-if="productivityData.length > 0">
        <div class="section-header">
          <h2>Employee Performance</h2>
          <div class="section-subtitle">
            <span>Showing {{ productivityData.length }} employee{{ productivityData.length !== 1 ? 's' : '' }}</span>
            <div class="sort-controls">
              <select v-model="sortBy" @change="sortEmployees">
                <option value="productivity_score">Productivity Score</option>
                <option value="tasks_completed">Tasks Completed</option>
                <option value="sla_compliance">SLA Compliance</option>
                <option value="on_time_rate">On-Time Rate</option>
              </select>
              <select v-model="sortOrder" @change="sortEmployees">
                <option value="desc">Highest First</option>
                <option value="asc">Lowest First</option>
              </select>
            </div>
          </div>
        </div>

        <div class="employees-grid">
          <div v-for="employee in sortedProductivityData" :key="employee.id" class="employee-card">
            <div class="employee-header">
              <div class="employee-avatar">
                {{ getInitials(employee.full_name) }}
                <div class="performance-badge" :class="getPerformanceClass(employee.performance_level)">
                  {{ formatPerformanceLevel(employee.performance_level) }}
                </div>
              </div>
              <div class="employee-info">
                <h3>{{ employee.full_name }}</h3>
                <p class="employee-id">{{ employee.employee_id }}</p>
                <div class="employee-meta">
                  <span class="meta-item">📍 {{ employee.department }}</span>
                  <span class="meta-item">👤 {{ employee.position }}</span>
                </div>
              </div>
              <div class="productivity-score">
                <div class="score-main">{{ employee.productivity_score }}%</div>
                <div class="score-trend" :class="getTrendClass(employee.trend)">
                  {{ getTrendIcon(employee.trend) }} {{ employee.trend_percentage }}%
                </div>
              </div>
            </div>
            
            <div class="employee-metrics">
              <div class="metric-row">
                <div class="metric-cell">
                  <span class="metric-label">Tasks</span>
                  <span class="metric-value">{{ employee.tasks_completed }}/{{ employee.total_tasks }}</span>
                  <div class="metric-progress">
                    <div class="progress-bar">
                      <div class="progress-fill" :style="{ width: calculateCompletionPercentage(employee.tasks_completed, employee.total_tasks) + '%' }"></div>
                    </div>
                  </div>
                </div>
                <div class="metric-cell">
                  <span class="metric-label">On Time</span>
                  <span class="metric-value">{{ employee.on_time_rate }}%</span>
                  <div class="metric-progress">
                    <div class="progress-bar">
                      <div class="progress-fill" :style="{ width: employee.on_time_rate + '%' }"></div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="metric-row">
                <div class="metric-cell">
                  <span class="metric-label">SLA Compliance</span>
                  <span class="metric-value">{{ employee.sla_compliance }}%</span>
                  <div class="metric-progress">
                    <div class="progress-bar">
                      <div class="progress-fill" :style="{ width: employee.sla_compliance + '%' }"></div>
                    </div>
                  </div>
                </div>
                <div class="metric-cell">
                  <span class="metric-label">Efficiency</span>
                  <span class="metric-value">{{ employee.efficiency_rate }}%</span>
                  <div class="metric-progress">
                    <div class="progress-bar">
                      <div class="progress-fill" :style="{ width: employee.efficiency_rate + '%' }"></div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="task-details">
                <div class="task-stats">
                  <div class="stat-item">
                    <span class="stat-icon overdue">⏰</span>
                    <span class="stat-count">{{ employee.overdue_tasks }}</span>
                    <span class="stat-label">Overdue</span>
                  </div>
                  <div class="stat-item">
                    <span class="stat-icon in-progress">🔄</span>
                    <span class="stat-count">{{ employee.in_progress_tasks }}</span>
                    <span class="stat-label">In Progress</span>
                  </div>
                  <div class="stat-item">
                    <span class="stat-icon completed">✅</span>
                    <span class="stat-count">{{ employee.completed_tasks }}</span>
                    <span class="stat-label">Completed</span>
                  </div>
                  <div class="stat-item">
                    <span class="stat-icon pending">⏳</span>
                    <span class="stat-count">{{ employee.pending_tasks }}</span>
                    <span class="stat-label">Pending</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="productivity-actions">
              <button @click="viewDetails(employee)" class="btn-view">View Details</button>
              <button @click="sendFeedback(employee)" class="btn-feedback">📝 Feedback</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="!isDemoMode" class="empty-state">
        <div class="empty-content">
          <span class="empty-icon">📊</span>
          <h3>No Productivity Data Available</h3>
          <p>No employee productivity data found for the selected period ({{ formatPeriodText() }}).</p>
          <p class="empty-hint">Try selecting a different date range or check back later when more data is available.</p>
          <div class="empty-actions">
            <button @click="fetchProductivity" class="btn-primary">Refresh</button>
            <button @click="fetchMockData" class="btn-secondary">Load Sample Data</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
import { Line as LineChart, Doughnut as DoughnutChart, Bar as BarChart } from 'vue-chartjs'
import { Chart as ChartJS, Title, Tooltip, Legend, LineElement, PointElement, LinearScale, CategoryScale, ArcElement, BarElement } from 'chart.js'

// Register ChartJS components
ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, LinearScale, CategoryScale, ArcElement, BarElement)

export default {
  name: 'ProductivityMonitor',
  
  components: {
    LineChart,
    DoughnutChart,
    BarChart
  },
  
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  
  data() {
    const today = new Date().toISOString().split('T')[0]
    return {
      productivityData: [],
      teamOverview: this.getDefaultOverview(),
      slaDetails: this.getDefaultSlaDetails(),
      taskAnalysis: this.getDefaultTaskAnalysis(),
      periodInfo: null,
      loading: false,
      error: null,
      selectedPeriod: 'last_30_days',
      customStartDate: null,
      customEndDate: today,
      sortBy: 'productivity_score',
      sortOrder: 'desc',
      slaChartData: null,
      taskDistributionData: null,
      weeklyPerformanceData: null,
      timelineData: null,
      isDemoMode: false,
      userRole: '',
      businessName: '',
      showCharts: false,
      showDetailedMetrics: false,
      today: today,
      slaChartOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: (context) => `${context.parsed.y}% SLA Met`
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            max: 100,
            ticks: {
              callback: (value) => value + '%'
            }
          }
        }
      },
      taskDistributionOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom' }
        }
      },
      weeklyPerformanceOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top' }
        }
      },
      timelineOptions: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top' }
        },
        scales: {
          y: {
            beginAtZero: true,
            max: 100
          }
        }
      }
    }
  },
  
  computed: {
    sortedProductivityData() {
      const data = [...this.productivityData]
      return data.sort((a, b) => {
        const aValue = a[this.sortBy] || 0
        const bValue = b[this.sortBy] || 0
        return this.sortOrder === 'desc' ? bValue - aValue : aValue - bValue
      })
    },

    hasChartData() {
      return (this.slaChartData || this.taskDistributionData || this.weeklyPerformanceData || this.timelineData)
    }
  },
  
  mounted() {
    this.initializeComponent()
  },
  
  methods: {
    getDefaultOverview() {
      return {
        avgProductivityScore: 0,
        totalTasksCompleted: 0,
        totalHoursWorked: 0,
        completionRate: 0,
        slaCompliance: 0,
        onTimeRate: 0,
        efficiencyRate: 0,
        tasksOnTime: 0,
        slaMet: 0,
        totalSLA: 0,
        overdueTasks: 0,
        pendingTasks: 0,
        completedTasks: 0,
        avgHoursPerTask: 0,
        trend: 'neutral',
        trend_percentage: 0
      }
    },
    
    getDefaultSlaDetails() {
      return {
        responseTimeRate: 0,
        responseTimeMet: 0,
        responseTimeTotal: 0,
        resolutionRate: 0,
        resolutionMet: 0,
        resolutionTotal: 0,
        completionRate: 0,
        completionMet: 0,
        completionTotal: 0,
        avgResponseTime: 0,
        avgResolutionTime: 0,
        avgDelay: 0
      }
    },
    
    getDefaultTaskAnalysis() {
      return {
        onTimeRate: 0,
        avgCompletionTime: 0,
        qualityScore: 0,
        reopenRate: 0,
        onTimeTrend: 'neutral',
        onTimeChange: 0,
        completionTimeTrend: 'neutral',
        completionTimeChange: 0,
        qualityTrend: 'neutral',
        qualityChange: 0,
        reopenTrend: 'neutral',
        reopenChange: 0
      }
    },
    
    initializeComponent() {
      if (!this.authStore.isAuthenticated) {
        this.error = 'Please log in to access productivity monitor.'
        return
      }
      
      // Set user role for display
      this.userRole = this.authStore.userRole || 'User'
      if (this.authStore.user?.employee?.business) {
        this.businessName = this.authStore.user.employee.business.name
      }
      
      this.fetchProductivity()
    },

    onPeriodChange() {
      if (this.selectedPeriod !== 'custom') {
        this.fetchProductivity()
      }
    },

    onCustomDateChange() {
      if (this.customStartDate && this.customEndDate) {
        this.fetchProductivity()
      }
    },

    formatPeriodText() {
      switch (this.selectedPeriod) {
        case 'last_7_days':
          return 'Last 7 Days'
        case 'last_30_days':
          return 'Last 30 Days'
        case 'this_month':
          return 'This Month'
        case 'last_month':
          return 'Last Month'
        case 'custom':
          if (this.customStartDate && this.customEndDate) {
            return `${this.formatDate(this.customStartDate)} to ${this.formatDate(this.customEndDate)}`
          }
          return 'Custom Range'
        default:
          return 'Selected Period'
      }
    },

    formatDate(dateStr) {
      const date = new Date(dateStr)
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
    },
    
    async fetchProductivity() {
      this.loading = true
      this.error = null
      this.isDemoMode = false
      
      try {
        const endpoints = [
          '/api/admin/reports/productivity',
          '/api/manager/reports/productivity',
          '/api/productivity'
        ]
        
        let response = null
        let lastError = null
        
        for (const endpoint of endpoints) {
          try {
            console.log(`Trying endpoint: ${endpoint}`)
            
            const params = {
              period: this.selectedPeriod,
              include_sla: true,
              include_tasks: true
            }
            
            if (this.selectedPeriod === 'custom' && this.customStartDate && this.customEndDate) {
              params.custom_start = this.customStartDate
              params.custom_end = this.customEndDate
            }
            
            response = await axios.get(endpoint, { 
              params,
              timeout: 15000,
              headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
              }
            })
            
            if (response && response.data) {
              console.log(`Success with endpoint: ${endpoint}`)
              break
            }
          } catch (err) {
            lastError = err
            console.log(`Failed with endpoint ${endpoint}:`, err.message)
            continue
          }
        }
        
        if (!response) {
          throw lastError || new Error('No API endpoint responded successfully')
        }
        
        this.processResponseData(response.data)
        
      } catch (err) {
        console.error('Productivity fetch error:', err)
        this.handleApiError(err)
      } finally {
        this.loading = false
      }
    },
    
    processResponseData(data) {
      console.log('Processing response data:', data)
      
      // Store period info
      if (data.period) {
        this.periodInfo = data.period
      }

      // Handle employee data
      if (data.employees && Array.isArray(data.employees)) {
        this.productivityData = data.employees
      } else {
        this.productivityData = []
      }

      // Handle overview
      if (data.overview) {
        this.teamOverview = { ...this.getDefaultOverview(), ...data.overview }
      } else {
        this.teamOverview = this.getDefaultOverview()
      }

      // Handle SLA details
      if (data.sla_details) {
        this.slaDetails = { ...this.getDefaultSlaDetails(), ...data.sla_details }
        this.showDetailedMetrics = true
      } else {
        this.slaDetails = this.getDefaultSlaDetails()
        this.showDetailedMetrics = false
      }

      // Handle task analysis
      if (data.task_analysis) {
        this.taskAnalysis = { ...this.getDefaultTaskAnalysis(), ...data.task_analysis }
      } else {
        this.taskAnalysis = this.getDefaultTaskAnalysis()
      }

      // Handle charts
      if (data.charts) {
        this.initializeCharts(data.charts)
        this.showCharts = true
      } else {
        this.showCharts = false
      }
    },
    
    initializeCharts(chartData) {
      // SLA Trend Chart
      if (chartData.sla_trend && chartData.sla_trend.labels && chartData.sla_trend.labels.length > 0) {
        this.slaChartData = {
          labels: chartData.sla_trend.labels,
          datasets: [{
            label: 'SLA Compliance Rate',
            data: chartData.sla_trend.data,
            borderColor: '#10B981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.4
          }]
        }
      } else {
        this.slaChartData = null
      }
      
      // Task Distribution Chart
      if (chartData.task_distribution && chartData.task_distribution.data && chartData.task_distribution.data.length > 0) {
        this.taskDistributionData = {
          labels: chartData.task_distribution.labels,
          datasets: [{
            data: chartData.task_distribution.data,
            backgroundColor: ['#10B981', '#F59E0B', '#3B82F6', '#EF4444']
          }]
        }
      } else {
        this.taskDistributionData = null
      }
      
      // Weekly Performance Chart
      if (chartData.weekly_performance && chartData.weekly_performance.labels && chartData.weekly_performance.labels.length > 0) {
        this.weeklyPerformanceData = {
          labels: chartData.weekly_performance.labels,
          datasets: [
            {
              label: 'Tasks Completed',
              data: chartData.weekly_performance.completed,
              backgroundColor: '#3B82F6'
            },
            {
              label: 'SLA Met',
              data: chartData.weekly_performance.sla_met,
              backgroundColor: '#10B981'
            }
          ]
        }
      } else {
        this.weeklyPerformanceData = null
      }
      
      // Timeline Chart
      if (chartData.timeline && chartData.timeline.labels && chartData.timeline.labels.length > 0) {
        this.timelineData = {
          labels: chartData.timeline.labels,
          datasets: [
            {
              label: 'Productivity Score',
              data: chartData.timeline.productivity,
              borderColor: '#8B5CF6',
              backgroundColor: 'rgba(139, 92, 246, 0.1)',
              fill: true,
              tension: 0.4
            },
            {
              label: 'SLA Compliance',
              data: chartData.timeline.sla,
              borderColor: '#10B981',
              backgroundColor: 'rgba(16, 185, 129, 0.1)',
              fill: true,
              tension: 0.4
            }
          ]
        }
      } else {
        this.timelineData = null
      }
    },
    
    fetchMockData() {
      this.isDemoMode = true
      this.loading = true
      
      setTimeout(() => {
        const mockEmployees = this.generateMockEmployees(8)
        this.productivityData = mockEmployees
        this.teamOverview = this.calculateMockOverview(mockEmployees)
        this.slaDetails = this.generateMockSlaDetails()
        this.taskAnalysis = this.generateMockTaskAnalysis()
        this.periodInfo = {
          start: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
          end: new Date().toISOString().split('T')[0]
        }
        
        this.initializeMockCharts()
        this.showCharts = true
        this.showDetailedMetrics = true
        this.loading = false
      }, 1000)
    },
    
    generateMockEmployees(count) {
      const departments = ['Engineering', 'Sales', 'Marketing', 'Support', 'HR', 'Finance']
      const positions = ['Developer', 'Manager', 'Analyst', 'Specialist', 'Coordinator']
      const performanceLevels = ['excellent', 'good', 'average', 'needs_improvement']
      const trends = ['up', 'down', 'neutral']
      
      return Array.from({ length: count }, (_, i) => ({
        id: i + 1,
        user_id: i + 100,
        employee_id: `EMP${String(i + 1000).padStart(4, '0')}`,
        full_name: `Employee ${i + 1}`,
        department: departments[i % departments.length],
        position: positions[i % positions.length],
        tasks_completed: Math.floor(Math.random() * 20) + 5,
        total_tasks: Math.floor(Math.random() * 25) + 10,
        completed_tasks: Math.floor(Math.random() * 15) + 3,
        in_progress_tasks: Math.floor(Math.random() * 5) + 1,
        pending_tasks: Math.floor(Math.random() * 5) + 1,
        overdue_tasks: Math.floor(Math.random() * 3),
        on_time_rate: Math.floor(Math.random() * 30) + 70,
        sla_compliance: Math.floor(Math.random() * 30) + 70,
        efficiency_rate: Math.floor(Math.random() * 30) + 70,
        productivity_score: Math.floor(Math.random() * 30) + 70,
        performance_level: performanceLevels[Math.floor(Math.random() * performanceLevels.length)],
        hours_worked: Math.floor(Math.random() * 40) + 20,
        trend: trends[Math.floor(Math.random() * trends.length)],
        trend_percentage: Math.floor(Math.random() * 10) + 1
      }))
    },
    
    calculateMockOverview(employees) {
      return {
        avgProductivityScore: Math.round(employees.reduce((sum, emp) => sum + emp.productivity_score, 0) / employees.length),
        totalTasksCompleted: employees.reduce((sum, emp) => sum + emp.tasks_completed, 0),
        slaCompliance: Math.round(employees.reduce((sum, emp) => sum + emp.sla_compliance, 0) / employees.length),
        avgHoursPerTask: 4.2,
        onTimeRate: Math.round(employees.reduce((sum, emp) => sum + emp.on_time_rate, 0) / employees.length),
        efficiencyRate: 78,
        tasksOnTime: Math.round(employees.reduce((sum, emp) => sum + emp.tasks_completed * (emp.on_time_rate / 100), 0)),
        slaMet: Math.round(employees.reduce((sum, emp) => sum + emp.sla_compliance, 0) / employees.length * 8),
        totalSLA: employees.length * 10,
        overdueTasks: employees.reduce((sum, emp) => sum + emp.overdue_tasks, 0),
        pendingTasks: employees.reduce((sum, emp) => sum + emp.pending_tasks, 0),
        completedTasks: employees.reduce((sum, emp) => sum + emp.completed_tasks, 0),
        totalHoursWorked: employees.reduce((sum, emp) => sum + emp.hours_worked, 0),
        trend: 'up',
        trend_percentage: 3.2
      }
    },
    
    generateMockSlaDetails() {
      return {
        responseTimeRate: 85,
        responseTimeMet: 34,
        responseTimeTotal: 40,
        resolutionRate: 78,
        resolutionMet: 31,
        resolutionTotal: 40,
        completionRate: 92,
        completionMet: 46,
        completionTotal: 50,
        avgResponseTime: 18.5,
        avgResolutionTime: 45.2,
        avgDelay: 2.3
      }
    },
    
    generateMockTaskAnalysis() {
      return {
        onTimeRate: 82,
        avgCompletionTime: 6.5,
        qualityScore: 88,
        reopenRate: 8,
        onTimeTrend: 'up',
        onTimeChange: 2.5,
        completionTimeTrend: 'down',
        completionTimeChange: 1.2,
        qualityTrend: 'up',
        qualityChange: 3.1,
        reopenTrend: 'down',
        reopenChange: 0.8
      }
    },
    
    initializeMockCharts() {
      this.slaChartData = {
        labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
        datasets: [{
          label: 'SLA Compliance Rate',
          data: [85, 88, 92, 90],
          borderColor: '#10B981',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          fill: true,
          tension: 0.4
        }]
      }
      
      this.taskDistributionData = {
        labels: ['On Time', 'Completed Late', 'In Progress', 'Overdue'],
        datasets: [{
          data: [70, 15, 10, 5],
          backgroundColor: ['#10B981', '#F59E0B', '#3B82F6', '#EF4444']
        }]
      }
      
      this.weeklyPerformanceData = {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
        datasets: [
          {
            label: 'Tasks Completed',
            data: [12, 19, 8, 15, 10],
            backgroundColor: '#3B82F6'
          },
          {
            label: 'SLA Met',
            data: [10, 15, 6, 12, 9],
            backgroundColor: '#10B981'
          }
        ]
      }
      
      this.timelineData = {
        labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
        datasets: [
          {
            label: 'Productivity Score',
            data: [78, 82, 85, 88],
            borderColor: '#8B5CF6',
            backgroundColor: 'rgba(139, 92, 246, 0.1)',
            fill: true,
            tension: 0.4
          },
          {
            label: 'SLA Compliance',
            data: [75, 80, 85, 88],
            borderColor: '#10B981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.4
          }
        ]
      }
    },
    
    retryFetch() {
      this.fetchProductivity()
    },
    
    handleApiError(err) {
      let errorMsg = 'An unexpected error occurred.'
      
      if (err.code === 'ERR_NETWORK' || err.message.includes('Network Error')) {
        errorMsg = 'Network error: Please check your connection.'
      } else if (err.response?.status === 401) {
        errorMsg = 'Your session has expired. Please log in again.'
        this.authStore.clearAuth()
        this.$router.push({ name: 'login' })
      } else if (err.response?.status === 403) {
        errorMsg = 'You do not have permission to access productivity data. Please contact your administrator.'
      } else if (err.response?.status === 404) {
        errorMsg = 'Productivity API endpoint not found. The feature might not be fully configured yet.'
      } else if (err.response?.status === 500) {
        errorMsg = 'Server error: ' + (err.response?.data?.message || 'Please try again later.')
      } else {
        errorMsg = err.response?.data?.message || err.message || errorMsg
      }
      
      this.error = errorMsg
      console.error('API Error:', err)
    },
    
    viewDetails(employee) {
      this.$router.push({ 
        name: 'employee-productivity-details', 
        params: { id: employee.id },
        query: { 
          period: this.selectedPeriod,
          start: this.customStartDate,
          end: this.customEndDate
        }
      })
    },
    
    sendFeedback(employee) {
      alert(`Feedback form for ${employee.full_name} would open here.`)
    },
    
    sortEmployees() {
      // Handled by computed property
    },
    
    getInitials(name) {
      if (!name || typeof name !== 'string') return '??'
      return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .substring(0, 2)
    },
    
    formatHours(hours) {
      if (!hours || hours === 0) return '0h'
      const wholeHours = Math.floor(hours)
      const minutes = Math.round((hours - wholeHours) * 60)
      return minutes > 0 ? `${wholeHours}h ${minutes}m` : `${wholeHours}h`
    },
    
    getTrendClass(trend) {
      return trend === 'up' ? 'trend-up' : trend === 'down' ? 'trend-down' : 'trend-neutral'
    },
    
    getTrendIcon(trend) {
      return trend === 'up' ? '📈' : trend === 'down' ? '📉' : '➡️'
    },
    
    getPerformanceClass(level) {
      const levels = {
        'excellent': 'performance-excellent',
        'good': 'performance-good',
        'average': 'performance-average',
        'needs_improvement': 'performance-needs-improvement',
        'poor': 'performance-poor'
      }
      return levels[level] || 'performance-average'
    },

    formatPerformanceLevel(level) {
      return level?.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) || 'Average'
    },

    calculateCompletionPercentage(completed, total) {
      if (!total || total === 0) return 0
      return Math.round((completed / total) * 100)
    },
    
    exportReport() {
      if (this.productivityData.length === 0) {
        alert('No data to export')
        return
      }
      
      alert('Export functionality would generate a PDF/Excel report here.')
    }
  }
}
</script>

<style scoped>
.productivity-monitor {
  padding: 2rem;
  max-width: 1600px;
  margin: 0 auto;
}

/* Updated Header Card Styles */
.header-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  padding: 24px;
  margin-bottom: 24px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  margin-bottom: 16px;
}

.card-title {
  margin: 0;
  font-size: 1.75rem;
  font-weight: 700;
  color: #2d3748;
  flex: 1;
}

.btn-export {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 10px 24px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  white-space: nowrap;
  font-size: 0.95rem;
  box-shadow: 0 2px 4px rgba(102, 126, 234, 0.2);
}

.btn-export:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.btn-export:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background: #a0aec0;
  box-shadow: none;
}

.header-subtitle {
  display: flex;
  gap: 1rem;
  align-items: center;
  margin-bottom: 1.5rem;
  color: #6b7280;
}

.role-badge {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  padding: 6px 16px;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
  box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
}

.business-info {
  background: #f3f4f6;
  padding: 6px 16px;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
}

.header-actions {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  margin-top: 1rem;
}

.period-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.period-selector select {
  padding: 8px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: white;
  color: #4a5568;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: border-color 0.2s;
}

.period-selector select:hover {
  border-color: #cbd5e1;
}

.period-selector select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.custom-date-range {
  display: flex;
  align-items: center;
  gap: 8px;
}

.custom-date-range input {
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.875rem;
  background: white;
  color: #4a5568;
  cursor: pointer;
}

.custom-date-range input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
}

.custom-date-range span {
  color: #6b7280;
  font-size: 0.875rem;
}

.period-info {
  background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
  border: 1px solid #bae6fd;
  padding: 12px 16px;
  border-radius: 8px;
  font-size: 0.875rem;
  color: #0369a1;
  font-weight: 500;
}

.period-label {
  margin-right: 8px;
  color: #0c4a6e;
}

/* Responsive design for smaller screens */
@media (max-width: 768px) {
  .card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }
  
  .btn-export {
    align-self: stretch;
    width: 100%;
    justify-content: center;
    display: flex;
  }
  
  .header-actions {
    flex-direction: column;
    align-items: stretch;
  }
  
  .period-selector {
    flex-direction: column;
    align-items: stretch;
  }
  
  .custom-date-range {
    flex-direction: column;
    align-items: stretch;
  }
}

/* Rest of the existing styles remain unchanged */
.page-header {
  margin-bottom: 2rem;
}

.page-header h1 {
  color: #2d3748;
  font-size: 2rem;
  margin: 0 0 0.5rem 0;
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
  padding: 2rem;
  border-radius: 8px;
  text-align: center;
  margin: 2rem 0;
}

.error-content {
  max-width: 500px;
  margin: 0 auto;
}

.error-content h3 {
  margin: 0 0 1rem 0;
}

.error-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-top: 1.5rem;
}

.btn-primary {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}

.btn-secondary {
  background: #e5e7eb;
  color: #4b5563;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
}

.demo-warning {
  background: #fffbeb;
  border: 1px solid #f59e0b;
  color: #92400e;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 2rem;
}

.warning-content {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.warning-icon {
  font-size: 1.5rem;
}

.warning-text {
  flex: 1;
}

.warning-text strong {
  display: block;
  margin-bottom: 0.25rem;
}

.warning-text p {
  margin: 0;
  font-size: 0.875rem;
}

.overview-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

@media (max-width: 1024px) {
  .overview-grid {
    grid-template-columns: 1fr;
  }
}

.overview-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  padding: 1.5rem;
}

.overview-card h3 {
  margin: 0 0 1.5rem 0;
  color: #2d3748;
  font-size: 1.25rem;
}

.overview-metrics {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

@media (max-width: 640px) {
  .overview-metrics {
    grid-template-columns: 1fr;
  }
}

.metric {
  text-align: center;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.metric-value {
  display: block;
  font-size: 1.75rem;
  font-weight: 700;
  color: #2d3748;
}

.metric-label {
  display: block;
  font-size: 0.875rem;
  color: #718096;
  margin-top: 0.25rem;
}

.metric-trend {
  font-size: 0.75rem;
  margin-top: 0.5rem;
  font-weight: 600;
}

.metric-sub {
  font-size: 0.75rem;
  color: #718096;
  margin-top: 0.25rem;
}

.performance-summary {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  padding: 1.5rem;
}

.performance-summary h3 {
  margin: 0 0 1.5rem 0;
  color: #2d3748;
  font-size: 1.25rem;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.summary-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.75rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.summary-icon {
  font-size: 1.5rem;
}

.summary-icon.overdue { color: #ef4444; }
.summary-icon.pending { color: #f59e0b; }
.summary-icon.completed { color: #10b981; }
.summary-icon.efficiency { color: #3b82f6; }

.summary-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: #2d3748;
}

.summary-label {
  font-size: 0.75rem;
  color: #718096;
}

.charts-section {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
  margin-bottom: 2rem;
}

@media (max-width: 1024px) {
  .charts-section {
    grid-template-columns: 1fr;
  }
}

.chart-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  padding: 1.5rem;
}

.chart-card h3 {
  margin: 0 0 1rem 0;
  color: #2d3748;
  font-size: 1.1rem;
}

.chart-container {
  height: 250px;
  position: relative;
}

.detailed-metrics {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
  margin-bottom: 2rem;
}

@media (max-width: 1024px) {
  .detailed-metrics {
    grid-template-columns: 1fr;
  }
}

.metrics-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  padding: 1.5rem;
}

.metrics-card h3 {
  margin: 0 0 1.5rem 0;
  color: #2d3748;
  font-size: 1.1rem;
}

.sla-details {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.sla-metric {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 1rem;
}

.sla-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.sla-title {
  font-weight: 600;
  color: #2d3748;
}

.sla-rate {
  font-weight: 700;
  color: #10b981;
}

.progress-bar {
  height: 6px;
  background: #e2e8f0;
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #10b981, #34d399);
  border-radius: 3px;
  transition: width 0.3s ease;
}

.sla-stats {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
  color: #718096;
}

.avg-time {
  font-weight: 600;
}

.completion-analysis {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.analysis-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.analysis-row .label {
  font-weight: 500;
  color: #4a5568;
}

.analysis-row .value {
  font-weight: 700;
  color: #2d3748;
}

.trend {
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.trend-up {
  color: #10b981;
  background: rgba(16, 185, 129, 0.1);
}

.trend-down {
  color: #ef4444;
  background: rgba(239, 68, 68, 0.1);
}

.trend-neutral {
  color: #f59e0b;
  background: rgba(245, 158, 11, 0.1);
}

.employees-section {
  margin-bottom: 2rem;
}

.section-header {
  margin-bottom: 1.5rem;
}

.section-header h2 {
  color: #2d3748;
  font-size: 1.5rem;
  margin: 0 0 0.5rem 0;
}

.section-subtitle {
  display: flex;
  justify-content: space-between;
  align-items: center;
  color: #6b7280;
  font-size: 0.875rem;
}

.sort-controls {
  display: flex;
  gap: 0.5rem;
}

.sort-controls select {
  padding: 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: white;
  color: #4a5568;
  font-size: 0.875rem;
}

.employees-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
}

@media (max-width: 640px) {
  .employees-grid {
    grid-template-columns: 1fr;
  }
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
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f0f0f0;
  position: relative;
}

.employee-avatar {
  width: 70px;
  height: 70px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  font-weight: 700;
  position: relative;
  flex-shrink: 0;
}

.performance-badge {
  position: absolute;
  bottom: -5px;
  right: -5px;
  font-size: 0.6rem;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  color: white;
  font-weight: 600;
  text-transform: capitalize;
  white-space: nowrap;
}

.performance-excellent {
  background: linear-gradient(135deg, #10b981, #059669);
}

.performance-good {
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}

.performance-average {
  background: linear-gradient(135deg, #f59e0b, #d97706);
}

.performance-needs-improvement {
  background: linear-gradient(135deg, #ef4444, #dc2626);
}

.performance-poor {
  background: linear-gradient(135deg, #6b7280, #4b5563);
}

.employee-info {
  flex: 1;
  min-width: 0;
}

.employee-info h3 {
  margin: 0 0 0.25rem 0;
  color: #2d3748;
  font-size: 1.1rem;
}

.employee-id {
  color: #718096;
  font-size: 0.875rem;
  margin: 0 0 0.5rem 0;
}

.employee-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.meta-item {
  font-size: 0.75rem;
  color: #718096;
  background: #f8f9fa;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.productivity-score {
  position: absolute;
  right: 0;
  top: 0;
  text-align: right;
}

.score-main {
  font-size: 1.5rem;
  font-weight: 700;
  color: #2d3748;
}

.score-trend {
  font-size: 0.75rem;
}

.employee-metrics {
  margin-bottom: 1.5rem;
}

.metric-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  margin-bottom: 1rem;
}

.metric-cell {
  padding: 0.75rem;
  background: #f8f9fa;
  border-radius: 8px;
}

.metric-label {
  display: block;
  font-size: 0.75rem;
  color: #718096;
  margin-bottom: 0.25rem;
}

.metric-value {
  display: block;
  font-size: 1rem;
  font-weight: 700;
  color: #2d3748;
  margin-bottom: 0.5rem;
}

.metric-progress {
  margin-top: 0.5rem;
}

.task-details {
  margin-top: 1rem;
}

.task-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.5rem;
}

.stat-item {
  text-align: center;
  padding: 0.5rem;
  background: #f8f9fa;
  border-radius: 6px;
}

.stat-icon {
  display: block;
  font-size: 1rem;
  margin-bottom: 0.25rem;
}

.stat-icon.overdue { color: #ef4444; }
.stat-icon.in-progress { color: #3b82f6; }
.stat-icon.completed { color: #10b981; }
.stat-icon.pending { color: #f59e0b; }

.stat-count {
  display: block;
  font-weight: 700;
  color: #2d3748;
  font-size: 0.875rem;
}

.stat-label {
  display: block;
  font-size: 0.65rem;
  color: #718096;
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
}

.btn-view {
  background: #e6f7ff;
  color: #1890ff;
}

.btn-view:hover {
  background: #bae7ff;
  transform: scale(1.05);
}

.btn-feedback {
  background: #f0f9ff;
  color: #3b82f6;
}

.btn-feedback:hover {
  background: #dbeafe;
  transform: scale(1.05);
}

.empty-state {
  text-align: center;
  padding: 4rem;
  color: #718096;
}

.empty-content {
  max-width: 500px;
  margin: 0 auto;
}

.empty-icon {
  font-size: 4rem;
  display: block;
  margin-bottom: 1.5rem;
}

.empty-content h3 {
  margin: 0 0 1rem 0;
  color: #4b5563;
}

.empty-hint {
  color: #6b7280;
  font-size: 0.875rem;
  margin-top: 0.5rem;
}

.empty-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-top: 1.5rem;
}
</style>