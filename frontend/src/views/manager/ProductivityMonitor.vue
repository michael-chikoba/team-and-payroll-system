<template>
  <div class="productivity-monitor">

    <!-- ── Header Card ─────────────────────────────── -->
    <div class="dashboard-header-card">
      <div class="user-greeting">
        <div class="avatar-section">
          <div class="avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
            </svg>
          </div>
          <div class="user-info">
            <h1 class="greeting">Team Productivity Monitor</h1>
            <p class="subtitle">Track performance, SLA compliance, and task completion across your team</p>
            <div class="role-meta">
              <span class="role-badge">Manager View</span>
            </div>
          </div>
        </div>

        <div class="header-actions">
          <div class="period-selector">
            <select v-model="selectedPeriod" @change="fetchProductivity" class="header-select">
              <option value="last_7_days">Last 7 Days</option>
              <option value="last_30_days">Last 30 Days</option>
              <option value="this_month">This Month</option>
              <option value="last_month">Last Month</option>
              <option value="custom">Custom Range</option>
            </select>
          </div>
          <div v-if="selectedPeriod === 'custom'" class="custom-date-range">
            <input type="date" v-model="customStartDate" @change="fetchProductivity" class="date-input" />
            <span class="date-sep">to</span>
            <input type="date" v-model="customEndDate" @change="fetchProductivity" class="date-input" />
          </div>
          <button @click="exportReport" class="btn-export">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Export Report
          </button>
        </div>
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
                <div class="metric-sub">{{ teamOverview.tasksOnTime }} on time ({{ teamOverview.onTimeRate }}%)</div>
              </div>
              <div class="metric">
                <span class="metric-value">{{ teamOverview.slaCompliance }}%</span>
                <span class="metric-label">SLA Compliance</span>
                <div class="metric-sub">{{ teamOverview.slaMet }} of {{ teamOverview.totalSLA }} met</div>
              </div>
              <div class="metric">
                <span class="metric-value">{{ formatHours(teamOverview.avgHoursPerTask) }}</span>
                <span class="metric-label">Avg Hours/Task</span>
                <div class="metric-sub">{{ teamOverview.efficiencyRate }}% efficiency</div>
              </div>
            </div>
          </div>

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
                <div class="progress-bar"><div class="progress-fill" :style="{ width: slaDetails.responseTimeRate + '%' }"></div></div>
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
                <div class="progress-bar"><div class="progress-fill" :style="{ width: slaDetails.resolutionRate + '%' }"></div></div>
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
                <div class="progress-bar"><div class="progress-fill" :style="{ width: slaDetails.completionRate + '%' }"></div></div>
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
              <div class="trend" :class="getTrendClass(taskAnalysis.onTimeTrend)">{{ getTrendIcon(taskAnalysis.onTimeTrend) }} {{ taskAnalysis.onTimeChange }}%</div>
            </div>
            <div class="analysis-row">
              <span class="label">Average Completion Time:</span>
              <span class="value">{{ formatHours(taskAnalysis.avgCompletionTime) }}</span>
              <div class="trend" :class="getTrendClass(taskAnalysis.completionTimeTrend)">{{ getTrendIcon(taskAnalysis.completionTimeTrend) }} {{ taskAnalysis.completionTimeChange }}%</div>
            </div>
            <div class="analysis-row">
              <span class="label">Quality Score:</span>
              <span class="value">{{ taskAnalysis.qualityScore }}%</span>
              <div class="trend" :class="getTrendClass(taskAnalysis.qualityTrend)">{{ getTrendIcon(taskAnalysis.qualityTrend) }} {{ taskAnalysis.qualityChange }}%</div>
            </div>
            <div class="analysis-row">
              <span class="label">Reopen Rate:</span>
              <span class="value">{{ taskAnalysis.reopenRate }}%</span>
              <div class="trend" :class="getTrendClass(taskAnalysis.reopenTrend)">{{ getTrendIcon(taskAnalysis.reopenTrend) }} {{ taskAnalysis.reopenChange }}%</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Employees Productivity List -->
      <div class="employees-section">
        <div class="section-header">
          <h2>Employee Performance</h2>
          <div class="sort-controls">
            <select v-model="sortBy" @change="sortEmployees" class="header-select">
              <option value="productivity_score">Productivity Score</option>
              <option value="tasks_completed">Tasks Completed</option>
              <option value="sla_compliance">SLA Compliance</option>
              <option value="on_time_rate">On-Time Rate</option>
            </select>
            <select v-model="sortOrder" @change="sortEmployees" class="header-select">
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
                  <div class="metric-progress"><div class="progress-bar"><div class="progress-fill" :style="{ width: (employee.tasks_completed / employee.total_tasks * 100) + '%' }"></div></div></div>
                </div>
                <div class="metric-cell">
                  <span class="metric-label">On Time</span>
                  <span class="metric-value">{{ employee.on_time_rate }}%</span>
                  <div class="metric-progress"><div class="progress-bar"><div class="progress-fill" :style="{ width: employee.on_time_rate + '%' }"></div></div></div>
                </div>
              </div>
              <div class="metric-row">
                <div class="metric-cell">
                  <span class="metric-label">SLA Compliance</span>
                  <span class="metric-value">{{ employee.sla_compliance }}%</span>
                  <div class="metric-progress"><div class="progress-bar"><div class="progress-fill" :style="{ width: employee.sla_compliance + '%' }"></div></div></div>
                </div>
                <div class="metric-cell">
                  <span class="metric-label">Efficiency</span>
                  <span class="metric-value">{{ employee.efficiency_rate }}%</span>
                  <div class="metric-progress"><div class="progress-bar"><div class="progress-fill" :style="{ width: employee.efficiency_rate + '%' }"></div></div></div>
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
            <button @click="exportPDF" class="btn-export-option">📄 Export as PDF</button>
            <button @click="exportExcel" class="btn-export-option">📊 Export as Excel</button>
            <button @click="exportCSV" class="btn-export-option">📈 Export as CSV</button>
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

ChartJS.register(Title, Tooltip, Legend, LineElement, PointElement, LinearScale, CategoryScale, ArcElement, BarElement)

export default {
  name: 'ProductivityMonitor',
  components: { LineChart, DoughnutChart, BarChart },
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
  mounted() { this.initializeComponent() },
  methods: {
    initializeComponent() {
      if (!this.authStore.isAuthenticated) { this.error = 'Please log in to access productivity monitor.'; return }
      if (!this.authStore.isManager) { this.error = 'You do not have permission to access this page.'; return }
      this.fetchProductivity()
    },
    async fetchProductivity() {
      this.loading = true; this.error = null
      try {
        const params = {
          manager_id: this.authStore.user?.id, period: this.selectedPeriod,
          include_sla: true, include_tasks: true, include_charts: true
        }
        if (this.selectedPeriod === 'custom' && this.customStartDate && this.customEndDate) {
          params.custom_start = this.customStartDate; params.custom_end = this.customEndDate
        }
        const response = await axios.get('/api/manager/reports/productivity-enhanced', { params })
        this.productivityData = response.data.employees || []
        this.teamOverview = response.data.overview || null
        this.slaDetails = response.data.sla_details || {}
        this.taskAnalysis = response.data.task_analysis || {}
        this.initializeCharts(response.data.charts || {})
      } catch (err) {
        this.handleApiError(err)
      } finally { this.loading = false }
    },
    initializeCharts(chartData) {
      if (chartData.sla_trend) {
        this.slaChartData = { labels: chartData.sla_trend.labels || ['Week 1', 'Week 2', 'Week 3', 'Week 4'], datasets: [{ label: 'SLA Compliance Rate', data: chartData.sla_trend.data || [85, 88, 92, 90], borderColor: '#10B981', backgroundColor: 'rgba(16, 185, 129, 0.1)', fill: true, tension: 0.4 }] }
        this.slaChartOptions = { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, max: 100, ticks: { callback: v => v + '%' } } } }
      }
      if (chartData.task_distribution) {
        this.taskDistributionData = { labels: chartData.task_distribution.labels || ['On Time', 'Completed Late', 'In Progress', 'Overdue'], datasets: [{ data: chartData.task_distribution.data || [70, 15, 10, 5], backgroundColor: ['#10B981', '#F59E0B', '#3B82F6', '#EF4444'] }] }
        this.taskDistributionOptions = { responsive: true, plugins: { legend: { position: 'bottom' } } }
      }
      if (chartData.weekly_performance) {
        this.weeklyPerformanceData = { labels: chartData.weekly_performance.labels || ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'], datasets: [{ label: 'Tasks Completed', data: chartData.weekly_performance.completed || [12, 19, 8, 15, 10], backgroundColor: '#3B82F6' }, { label: 'SLA Met', data: chartData.weekly_performance.sla_met || [10, 15, 6, 12, 9], backgroundColor: '#10B981' }] }
        this.weeklyPerformanceOptions = { responsive: true, plugins: { legend: { position: 'top' } }, scales: { x: { stacked: true }, y: { stacked: true } } }
      }
      if (chartData.timeline) {
        this.timelineData = { labels: chartData.timeline.labels || ['Jan', 'Feb', 'Mar', 'Apr', 'May'], datasets: [{ label: 'Productivity Score', data: chartData.timeline.productivity || [78, 82, 85, 88, 90], borderColor: '#8B5CF6', backgroundColor: 'rgba(139, 92, 246, 0.1)', fill: true }, { label: 'SLA Compliance', data: chartData.timeline.sla || [75, 80, 85, 88, 92], borderColor: '#10B981', backgroundColor: 'rgba(16, 185, 129, 0.1)', fill: true }] }
        this.timelineOptions = { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true, max: 100 } } }
      }
    },
    retryFetch() {
      this.retryCount++
      if (this.retryCount <= 3) this.fetchProductivity()
      else this.error = 'Max retries exceeded. Check your network or server.'
    },
    handleApiError(err) {
      let errorMsg = 'An unexpected error occurred.'
      if (err.code === 'ERR_NETWORK' || err.message.includes('Network Error')) errorMsg = 'Network error: Please check your connection.'
      else if (err.response?.status === 401) { errorMsg = 'Your session has expired. Please log in again.'; this.authStore.clearAuth(); this.$router.push({ name: 'login' }) }
      else if (err.response?.status === 403) errorMsg = 'You do not have permission to perform this action.'
      else errorMsg = err.response?.data?.message || errorMsg
      this.error = errorMsg
    },
    viewDetails(employee) { this.$router.push({ name: 'employee-productivity-details', params: { id: employee.id }, query: { period: this.selectedPeriod } }) },
    sortEmployees() {},
    getInitials(name) { if (!name) return '??'; return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) },
    formatHours(hours) { if (!hours) return '0h'; const h = Math.floor(hours); const m = Math.round((hours - h) * 60); return m > 0 ? `${h}h ${m}m` : `${h}h` },
    getTrendClass(trend) { return trend === 'up' ? 'trend-up' : trend === 'down' ? 'trend-down' : 'trend-neutral' },
    getTrendIcon(trend) { return trend === 'up' ? '📈' : trend === 'down' ? '📉' : '➡️' },
    getPerformanceClass(level) { return { excellent: 'performance-excellent', good: 'performance-good', average: 'performance-average', needs_improvement: 'performance-needs-improvement', poor: 'performance-poor' }[level] || 'performance-average' },
    async downloadReport(employee) {
      try {
        const response = await axios.get(`/api/manager/reports/employee/${employee.id}/download`, { responseType: 'blob', params: { period: this.selectedPeriod } })
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a'); link.href = url
        link.setAttribute('download', `Productivity-Report-${employee.employee_id}.pdf`)
        document.body.appendChild(link); link.click(); link.remove()
      } catch (error) { console.error('Error downloading report:', error) }
    },
    exportReport() { console.log('Exporting full report...') },
    exportPDF() {},
    exportExcel() {},
    exportCSV() {}
  }
}
</script>

<style scoped>
/* ── Base ─────────────────────────────────────────── */
.productivity-monitor {
  padding: 1.5rem 2rem 3rem;
  max-width: 1600px;
  margin: 0 auto;
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

/* ── Header Card ──────────────────────────────────── */
.dashboard-header-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem 1.75rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0;
  flex-shrink: 0;
}

.user-greeting { display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
.avatar-section { display: flex; align-items: center; gap: 1rem; }

.avatar {
  width: 52px; height: 52px;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  border-radius: 14px; display: flex; align-items: center; justify-content: center;
  color: white; box-shadow: 0 4px 12px rgba(59,130,246,0.25); flex-shrink: 0;
}

.user-info { display: flex; flex-direction: column; gap: 0.2rem; }
.greeting { margin: 0; font-size: 1.375rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
.subtitle { margin: 0; color: #64748b; font-size: 0.875rem; }
.role-meta { margin-top: 0.125rem; }
.role-badge { background: #eff6ff; border: 1px solid #bfdbfe; padding: 0.125rem 0.6rem; border-radius: 8px; font-size: 0.7rem; font-weight: 600; color: #1d4ed8; display: inline-block; }

.header-actions { display: flex; gap: 0.625rem; align-items: center; flex-wrap: wrap; flex-shrink: 0; }

.header-select {
  appearance: none;
  padding: 0.45rem 2rem 0.45rem 0.75rem; border: 1px solid #e2e8f0;
  border-radius: 8px; background: #f8fafc; color: #334155;
  font-size: 0.82rem; font-weight: 500; cursor: pointer;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 0.6rem center;
  font-family: inherit; transition: all 0.2s;
}
.header-select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

.custom-date-range { display: flex; align-items: center; gap: 0.5rem; }
.date-sep { font-size: 0.8rem; color: #64748b; }
.date-input {
  padding: 0.45rem 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px;
  background: #f8fafc; color: #334155; font-size: 0.82rem; font-family: inherit; transition: all 0.2s;
}
.date-input:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

.btn-export {
  display: inline-flex; align-items: center; gap: 0.4rem;
  background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;
  border: none; padding: 0.5rem 1.1rem; border-radius: 8px;
  font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: all 0.2s; font-family: inherit;
}
.btn-export:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,0.35); }

/* ── States ───────────────────────────────────────── */
.loading { text-align: center; padding: 4rem; background: white; border-radius: 16px; border: 1px solid #e2e8f0; }
.spinner { width: 40px; height: 40px; border: 3px solid #e2e8f0; border-top-color: #6366f1; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem; }
@keyframes spin { to { transform: rotate(360deg); } }
.error-message { background: #fef2f2; color: #dc2626; padding: 1.25rem; border-radius: 12px; text-align: center; border: 1px solid #fecaca; }
.btn-primary { background: #6366f1; color: white; border: none; padding: 0.5rem 1.25rem; border-radius: 8px; font-weight: 600; cursor: pointer; font-family: inherit; }

/* ── Content ──────────────────────────────────────── */
.productivity-content { display: flex; flex-direction: column; gap: 1.5rem; }

/* ── Overview ─────────────────────────────────────── */
.overview-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; }
@media (max-width: 1024px) { .overview-grid { grid-template-columns: 1fr; } }

.overview-card, .performance-summary, .chart-card, .metrics-card, .export-section {
  background: white; border-radius: 16px;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0; padding: 1.5rem;
}

.overview-card h3, .performance-summary h3, .chart-card h3, .metrics-card h3, .export-options h3 {
  margin: 0 0 1.25rem 0; color: #334155; font-size: 1rem; font-weight: 700;
}

.overview-metrics { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
@media (max-width: 640px) { .overview-metrics { grid-template-columns: 1fr; } }

.metric { text-align: center; padding: 1rem; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0; }
.metric-value { display: block; font-size: 1.75rem; font-weight: 800; color: #0f172a; }
.metric-label { display: block; font-size: 0.78rem; color: #64748b; margin-top: 0.2rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.03em; }
.metric-trend { font-size: 0.75rem; margin-top: 0.4rem; font-weight: 600; }
.metric-sub { font-size: 0.75rem; color: #94a3b8; margin-top: 0.2rem; }

.summary-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.875rem; }
.summary-item { display: flex; align-items: center; gap: 0.875rem; padding: 0.875rem; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0; }
.summary-icon { font-size: 1.5rem; }
.summary-value { font-size: 1.25rem; font-weight: 700; color: #0f172a; }
.summary-label { font-size: 0.72rem; color: #64748b; font-weight: 500; margin-top: 0.1rem; }

/* ── Charts ───────────────────────────────────────── */
.charts-section { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
@media (max-width: 1024px) { .charts-section { grid-template-columns: 1fr; } }
.chart-container { height: 250px; position: relative; }

/* ── Detailed Metrics ─────────────────────────────── */
.detailed-metrics { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
@media (max-width: 1024px) { .detailed-metrics { grid-template-columns: 1fr; } }

.sla-details { display: flex; flex-direction: column; gap: 1.25rem; }
.sla-metric { background: #f8fafc; border-radius: 10px; padding: 1rem; border: 1px solid #e2e8f0; }
.sla-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
.sla-title { font-weight: 600; color: #334155; font-size: 0.875rem; }
.sla-rate { font-weight: 700; color: #10b981; }
.progress-bar { height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; margin-bottom: 0.5rem; }
.progress-fill { height: 100%; background: linear-gradient(90deg, #10b981, #34d399); border-radius: 3px; transition: width 0.3s ease; }
.sla-stats { display: flex; justify-content: space-between; font-size: 0.75rem; color: #64748b; }
.avg-time { font-weight: 600; }

.completion-analysis { display: flex; flex-direction: column; gap: 0.75rem; }
.analysis-row { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem; background: #f8fafc; border-radius: 10px; border: 1px solid #e2e8f0; gap: 0.5rem; }
.analysis-row .label { font-weight: 500; color: #475569; font-size: 0.875rem; flex: 1; }
.analysis-row .value { font-weight: 700; color: #0f172a; }
.trend { font-size: 0.75rem; font-weight: 600; padding: 0.2rem 0.5rem; border-radius: 6px; white-space: nowrap; }
.trend-up { color: #10b981; background: rgba(16,185,129,0.1); }
.trend-down { color: #ef4444; background: rgba(239,68,68,0.1); }
.trend-neutral { color: #f59e0b; background: rgba(245,158,11,0.1); }

/* ── Employees ────────────────────────────────────── */
.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 1rem; }
.section-header h2 { color: #1e293b; font-size: 1.25rem; margin: 0; font-weight: 700; }
.sort-controls { display: flex; gap: 0.5rem; }

.employees-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 1.5rem; }
@media (max-width: 640px) { .employees-grid { grid-template-columns: 1fr; } }

.employee-card {
  background: white; border-radius: 16px;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0; padding: 1.5rem;
  transition: transform 0.2s, box-shadow 0.2s;
}
.employee-card:hover { transform: translateY(-3px); box-shadow: 0 8px 20px -6px rgba(0,0,0,0.1); border-color: #c7d2fe; }

.employee-header { display: flex; align-items: flex-start; gap: 1rem; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 1px solid #f1f5f9; position: relative; }
.employee-avatar { width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; font-weight: 700; position: relative; flex-shrink: 0; }
.performance-badge { position: absolute; bottom: -4px; right: -6px; font-size: 0.55rem; padding: 0.2rem 0.45rem; border-radius: 10px; color: white; font-weight: 700; text-transform: uppercase; letter-spacing: 0.02em; white-space: nowrap; }
.performance-excellent { background: #10b981; }
.performance-good { background: #3b82f6; }
.performance-average { background: #f59e0b; }
.performance-needs-improvement { background: #ef4444; }
.performance-poor { background: #6b7280; }

.employee-info h3 { margin: 0 0 0.2rem 0; color: #1e293b; font-size: 1rem; font-weight: 700; }
.employee-id { color: #94a3b8; font-size: 0.8rem; margin: 0 0 0.4rem 0; }
.employee-meta { display: flex; flex-wrap: wrap; gap: 0.5rem; }
.meta-item { font-size: 0.72rem; color: #64748b; background: #f1f5f9; padding: 0.15rem 0.5rem; border-radius: 6px; border: 1px solid #e2e8f0; }

.productivity-score { position: absolute; right: 0; top: 0; text-align: right; }
.score-main { font-size: 1.5rem; font-weight: 800; color: #0f172a; }
.score-trend { font-size: 0.72rem; font-weight: 600; }

.employee-metrics { margin-bottom: 1.25rem; }
.metric-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; margin-bottom: 0.75rem; }
.metric-cell { padding: 0.75rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; }
.metric-label { display: block; font-size: 0.7rem; color: #94a3b8; margin-bottom: 0.2rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.03em; }
.metric-value { display: block; font-size: 0.95rem; font-weight: 700; color: #1e293b; margin-bottom: 0.4rem; }
.metric-progress .progress-bar { height: 4px; }

.task-details { margin-top: 0.75rem; }
.task-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem; }
.stat-item { text-align: center; padding: 0.5rem 0.25rem; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; }
.stat-icon { display: block; font-size: 0.9rem; margin-bottom: 0.2rem; }
.stat-count { display: block; font-weight: 700; color: #0f172a; font-size: 0.875rem; }
.stat-label { display: block; font-size: 0.62rem; color: #94a3b8; font-weight: 500; }

.productivity-actions { display: flex; gap: 0.5rem; }
.productivity-actions button { flex: 1; padding: 0.625rem; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.2s; font-size: 0.82rem; font-family: inherit; }
.btn-view { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.btn-view:hover { background: #dbeafe; }
.btn-download { background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
.btn-download:hover { background: #dcfce7; }

.empty-state { grid-column: 1 / -1; text-align: center; padding: 4rem; color: #94a3b8; background: white; border-radius: 16px; border: 1px solid #e2e8f0; }

/* ── Export ───────────────────────────────────────── */
.export-buttons { display: flex; gap: 0.875rem; flex-wrap: wrap; }
.btn-export-option {
  flex: 1; min-width: 140px; padding: 0.875rem; border: 1px solid #e2e8f0;
  border-radius: 10px; background: #f8fafc; color: #475569; font-weight: 600;
  cursor: pointer; transition: all 0.2s; font-family: inherit; font-size: 0.875rem;
}
.btn-export-option:hover { background: white; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.07); border-color: #c7d2fe; color: #4f46e5; }

/* ── Responsive ───────────────────────────────────── */
@media (max-width: 768px) {
  .productivity-monitor { padding: 1rem 1rem 2rem; }
  .user-greeting { flex-direction: column; align-items: flex-start; }
  .header-actions { width: 100%; flex-wrap: wrap; }
  .charts-section, .detailed-metrics { grid-template-columns: 1fr; }
}
</style>