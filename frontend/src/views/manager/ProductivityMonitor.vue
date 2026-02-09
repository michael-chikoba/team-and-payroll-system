<template>
  <div class="productivity-monitor">
    <div class="page-header">
      <h1>Team Productivity Monitor</h1>
      <div class="header-actions">
        <div class="period-selector">
          <select v-model="selectedPeriod" @change="fetchProductivity">
            <option value="last_7_days">Last 7 Days</option>
            <option value="last_30_days">Last 30 Days</option>
            <option value="this_month">This Month</option>
            <option value="last_month">Last Month</option>
            <option value="custom">Custom Range</option>
          </select>
          <div v-if="selectedPeriod === 'custom'" class="custom-date-range">
            <input type="date" v-model="customStartDate" @change="fetchProductivity">
            <span>to</span>
            <input type="date" v-model="customEndDate" @change="fetchProductivity">
          </div>
        </div>
        <button @click="exportReport" class="btn-export">
          📊 Export Report
        </button>
      </div>
    </div>

    <!-- Authentication Check -->
    <div v-if="!authStore.isAuthenticated" class="error-message">
      Please log in to access productivity monitor.
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

    <!-- Main Content -->
    <div v-else class="productivity-content">
      
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
                  {{ getTrendIcon(teamOverview.trend) }} {{ teamOverview.trend_percentage }}%
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
      <div class="charts-section">
        <div class="chart-card">
          <h3>SLA Compliance Trend</h3>
          <div class="chart-container">
            <LineChart :data="slaChartData" :options="slaChartOptions" v-if="slaChartData" />
          </div>
        </div>
        
        <div class="chart-card">
          <h3>Task Completion Distribution</h3>
          <div class="chart-container">
            <DoughnutChart :data="taskDistributionData" :options="taskDistributionOptions" v-if="taskDistributionData" />
          </div>
        </div>
        
        <div class="chart-card">
          <h3>Weekly Performance</h3>
          <div class="chart-container">
            <BarChart :data="weeklyPerformanceData" :options="weeklyPerformanceOptions" v-if="weeklyPerformanceData" />
          </div>
        </div>
        
        <div class="chart-card">
          <h3>Task Status Timeline</h3>
          <div class="chart-container">
            <LineChart :data="timelineData" :options="timelineOptions" v-if="timelineData" />
          </div>
        </div>
      </div>

      <!-- Detailed Metrics -->
      <div class="detailed-metrics">
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
      <div class="employees-section">
        <div class="section-header">
          <h2>Employee Performance</h2>
          <div class="sort-controls">
            <select v-model="sortBy" @change="sortEmployees">
              <option value="productivity_score">Productivity Score</option>
              <option value="tasks_completed">Tasks Completed</option>
              <option value="sla_compliance">SLA Compliance</option>
              <option value="on_time_rate">On-Time Rate</option>
            </select>
            <select v-model="sortOrder" @change="sortEmployees">
              <option value="desc">Descending</option>
              <option value="asc">Ascending</option>
            </select>
          </div>
        </div>

        <div class="employees-grid">
          <div v-for="employee in sortedProductivityData" :key="employee.id" class="employee-card">
            <div class="employee-header">
              <div class="employee-avatar">
                {{ getInitials(employee.full_name || employee.user?.name) }}
                <div class="performance-badge" :class="getPerformanceClass(employee.performance_level)">
                  {{ employee.performance_level }}
                </div>
              </div>
              <div class="employee-info">
                <h3>{{ employee.full_name || employee.user?.name }}</h3>
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
                      <div class="progress-fill" :style="{ width: (employee.tasks_completed / employee.total_tasks * 100) + '%' }"></div>
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
              <button @click="downloadReport(employee)" class="btn-download">📥 Report</button>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="productivityData.length === 0" class="empty-state">
            <p>No productivity data available</p>
            <p>Productivity tracking may not be enabled for your team yet.</p>
          </div>
        </div>
      </div>

      <!-- Export Options -->
      <div class="export-section">
        <div class="export-options">
          <h3>Export Reports</h3>
          <div class="export-buttons">
            <button @click="exportPDF" class="btn-export-option">
              📄 Export as PDF
            </button>
            <button @click="exportExcel" class="btn-export-option">
              📊 Export as Excel
            </button>
            <button @click="exportCSV" class="btn-export-option">
              📈 Export as CSV
            </button>
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
    return {
      productivityData: [],
      teamOverview: null,
      slaDetails: {},
      taskAnalysis: {},
      loading: false,
      error: null,
      retryCount: 0,
      selectedPeriod: 'last_30_days',
      customStartDate: null,
      customEndDate: null,
      sortBy: 'productivity_score',
      sortOrder: 'desc',
      slaChartData: null,
      taskDistributionData: null,
      weeklyPerformanceData: null,
      timelineData: null
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
    }
  },
  
  mounted() {
    this.initializeComponent()
  },
  
  methods: {
    initializeComponent() {
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
    
    async fetchProductivity() {
      this.loading = true
      this.error = null
      
      try {
        const params = {
          manager_id: this.authStore.user?.id,
          period: this.selectedPeriod,
          include_sla: true,
          include_tasks: true,
          include_charts: true
        }
        
        if (this.selectedPeriod === 'custom' && this.customStartDate && this.customEndDate) {
          params.custom_start = this.customStartDate
          params.custom_end = this.customEndDate
        }
        
        const response = await axios.get('/api/manager/reports/productivity-enhanced', { params })
        
        this.productivityData = response.data.employees || []
        this.teamOverview = response.data.overview || null
        this.slaDetails = response.data.sla_details || {}
        this.taskAnalysis = response.data.task_analysis || {}
        
        // Initialize charts
        this.initializeCharts(response.data.charts || {})
      } catch (err) {
        this.handleApiError(err)
      } finally {
        this.loading = false
      }
    },
    
    initializeCharts(chartData) {
      // SLA Compliance Trend Chart
      if (chartData.sla_trend) {
        this.slaChartData = {
          labels: chartData.sla_trend.labels || ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
          datasets: [
            {
              label: 'SLA Compliance Rate',
              data: chartData.sla_trend.data || [85, 88, 92, 90],
              borderColor: '#10B981',
              backgroundColor: 'rgba(16, 185, 129, 0.1)',
              fill: true,
              tension: 0.4
            }
          ]
        }
        
        this.slaChartOptions = {
          responsive: true,
          plugins: {
            legend: { display: false }
          },
          scales: {
            y: {
              beginAtZero: true,
              max: 100,
              ticks: {
                callback: function(value) {
                  return value + '%'
                }
              }
            }
          }
        }
      }
      
      // Task Distribution Chart
      if (chartData.task_distribution) {
        this.taskDistributionData = {
          labels: chartData.task_distribution.labels || ['On Time', 'Completed Late', 'In Progress', 'Overdue'],
          datasets: [{
            data: chartData.task_distribution.data || [70, 15, 10, 5],
            backgroundColor: ['#10B981', '#F59E0B', '#3B82F6', '#EF4444']
          }]
        }
        
        this.taskDistributionOptions = {
          responsive: true,
          plugins: {
            legend: { position: 'bottom' }
          }
        }
      }
      
      // Weekly Performance Chart
      if (chartData.weekly_performance) {
        this.weeklyPerformanceData = {
          labels: chartData.weekly_performance.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
          datasets: [
            {
              label: 'Tasks Completed',
              data: chartData.weekly_performance.completed || [12, 19, 8, 15, 10],
              backgroundColor: '#3B82F6'
            },
            {
              label: 'SLA Met',
              data: chartData.weekly_performance.sla_met || [10, 15, 6, 12, 9],
              backgroundColor: '#10B981'
            }
          ]
        }
        
        this.weeklyPerformanceOptions = {
          responsive: true,
          plugins: {
            legend: { position: 'top' }
          },
          scales: {
            x: { stacked: true },
            y: { stacked: true }
          }
        }
      }
      
      // Timeline Chart
      if (chartData.timeline) {
        this.timelineData = {
          labels: chartData.timeline.labels || ['Jan', 'Feb', 'Mar', 'Apr', 'May'],
          datasets: [
            {
              label: 'Productivity Score',
              data: chartData.timeline.productivity || [78, 82, 85, 88, 90],
              borderColor: '#8B5CF6',
              backgroundColor: 'rgba(139, 92, 246, 0.1)',
              fill: true
            },
            {
              label: 'SLA Compliance',
              data: chartData.timeline.sla || [75, 80, 85, 88, 92],
              borderColor: '#10B981',
              backgroundColor: 'rgba(16, 185, 129, 0.1)',
              fill: true
            }
          ]
        }
        
        this.timelineOptions = {
          responsive: true,
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
    
    retryFetch() {
      this.retryCount++
      if (this.retryCount <= 3) {
        this.fetchProductivity()
      } else {
        this.error = 'Max retries exceeded. Check your network or server.'
      }
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
        errorMsg = 'You do not have permission to perform this action.'
      } else {
        errorMsg = err.response?.data?.message || errorMsg
      }
      this.error = errorMsg
    },
    
    viewDetails(employee) {
      this.$router.push({ 
        name: 'employee-productivity-details', 
        params: { id: employee.id },
        query: { period: this.selectedPeriod }
      })
    },
    
    sortEmployees() {
      // Sorting is handled by computed property
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
    
    async downloadReport(employee) {
      try {
        const response = await axios.get(`/api/manager/reports/employee/${employee.id}/download`, {
          responseType: 'blob',
          params: { period: this.selectedPeriod }
        })
        
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `Productivity-Report-${employee.employee_id}.pdf`)
        document.body.appendChild(link)
        link.click()
        link.remove()
      } catch (error) {
        console.error('Error downloading report:', error)
      }
    },
    
    exportReport() {
      // Implementation for full report export
      console.log('Exporting full report...')
    },
    
    exportPDF() {
      // PDF export implementation
    },
    
    exportExcel() {
      // Excel export implementation
    },
    
    exportCSV() {
      // CSV export implementation
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

.page-header {
  margin-bottom: 2rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.page-header h1 {
  color: #2d3748;
  font-size: 2rem;
  margin: 0;
}

.header-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.period-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.period-selector select {
  padding: 0.5rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: white;
  color: #4a5568;
}

.custom-date-range {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.custom-date-range input {
  padding: 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
}

.btn-export {
  background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s;
}

.btn-export:hover {
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
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.section-header h2 {
  color: #2d3748;
  font-size: 1.5rem;
  margin: 0;
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

.btn-download {
  background: #f0f9ff;
  color: #3b82f6;
}

.btn-download:hover {
  background: #dbeafe;
  transform: scale(1.05);
}

.empty-state {
  grid-column: 1 / -1;
  text-align: center;
  padding: 4rem;
  color: #718096;
}

.export-section {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  padding: 1.5rem;
  margin-top: 2rem;
}

.export-options h3 {
  margin: 0 0 1rem 0;
  color: #2d3748;
  font-size: 1.1rem;
}

.export-buttons {
  display: flex;
  gap: 1rem;
}

.btn-export-option {
  flex: 1;
  padding: 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: white;
  color: #4a5568;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-export-option:hover {
  background: #f8f9fa;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
</style>