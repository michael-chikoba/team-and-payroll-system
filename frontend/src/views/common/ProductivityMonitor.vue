<template>
  <div class="productivity-management">
    <!-- ── Main (header card lives inside here) ───────── -->
    <div class="management-main">

      <!-- ── Header Card ─────────────────────────────── -->
      <div class="management-header-card">
        <div class="header-card-accent"></div>
        <div class="header-inner">

          <!-- Brand + Title -->
          <div class="user-greeting">
            <div class="avatar-section">
              <div class="avatar">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                  <circle cx="12" cy="7" r="4"></circle>
                  <polyline points="17 11 19 13 23 9"></polyline>
                </svg>
              </div>
              <div class="user-info">
                <h1 class="greeting">Team Productivity Monitor</h1>
                <p class="subtitle">Track employee performance, tasks, and SLA compliance</p>
                <div class="role-meta">
                  <span class="role-badge">{{ userRole }}</span>
                  <span v-if="businessName" class="month-badge">{{ businessName }}</span>
                </div>
              </div>
            </div>

            <!-- Controls -->
            <div class="header-controls">
              <!-- Period Selector -->
              <div class="period-selector">
                <div class="select-wrapper">
                  <select v-model="selectedPeriod" @change="onPeriodChange" class="header-select">
                    <option value="last_7_days">Last 7 Days</option>
                    <option value="last_30_days">Last 30 Days</option>
                    <option value="this_month">This Month</option>
                    <option value="last_month">Last Month</option>
                    <option value="custom">Custom Range</option>
                  </select>
                  <svg class="select-chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                </div>

                <div v-if="selectedPeriod === 'custom'" class="custom-date-range">
                  <input 
                    type="date" 
                    v-model="customStartDate" 
                    @change="onCustomDateChange" 
                    :max="today"
                    class="date-input"
                  />
                  <span class="date-separator">to</span>
                  <input 
                    type="date" 
                    v-model="customEndDate" 
                    @change="onCustomDateChange" 
                    :max="today" 
                    :min="customStartDate"
                    class="date-input"
                  />
                </div>
              </div>

              <button @click="exportReport" class="btn-accent" :disabled="loading || productivityData.length === 0">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                  <polyline points="7 10 12 15 17 10"></polyline>
                  <line x1="12" y1="15" x2="12" y2="3"></line>
                </svg>
                Export Report
              </button>
            </div>
          </div>

          <!-- Period Info Badge -->
          <div v-if="periodInfo" class="view-badge">
            <div class="view-content">
              <span class="view-primary">{{ formatPeriodTextShort() }}</span>
              <div class="view-details">
                <span class="view-label">Date Range</span>
                <span class="view-total">{{ periodInfo.start }} - {{ periodInfo.end }}</span>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Authentication Check -->
      <div v-if="!authStore.isAuthenticated" class="state-banner error">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10"></circle>
          <line x1="12" y1="8" x2="12" y2="12"></line>
          <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>
        <span>Please log in to access productivity monitor.</span>
        <button @click="$router.push('/login')" class="btn-text">Go to Login</button>
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

      <!-- Loading State -->
      <div v-if="loading" class="state-banner loading">
        <div class="spinner"></div>
        <span>Loading productivity data for {{ formatPeriodText() }}...</span>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="state-banner error">
        {{ error }}
        <div class="error-actions">
          <button @click="retryFetch" class="btn-text">Retry</button>
          <button @click="fetchMockData" class="btn-text">Load Sample Data</button>
        </div>
      </div>

      <!-- Main Content -->
      <div v-if="!loading && !error && authStore.isAuthenticated" class="productivity-content">

        <!-- Productivity Overview -->
        <div class="overview-grid">
          <!-- Team Overview Card -->
          <div class="content-card">
            <div class="card-header">
              <h3>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                  <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                  <circle cx="9" cy="7" r="4"></circle>
                  <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                  <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                Team Overview
              </h3>
            </div>
            <div class="overview-metrics">
              <div class="metric-item">
                <div class="metric-icon" style="background:rgba(139,92,246,0.1); color:#8b5cf6;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                  </svg>
                </div>
                <div class="metric-content">
                  <span class="metric-label">Avg Score</span>
                  <span class="metric-value">{{ teamOverview.avgProductivityScore }}%</span>
                  <span :class="['metric-trend', getTrendClass(teamOverview.trend)]">
                    {{ getTrendIcon(teamOverview.trend) }} {{ Math.abs(teamOverview.trend_percentage) }}%
                  </span>
                </div>
              </div>

              <div class="metric-item">
                <div class="metric-icon" style="background:rgba(16,185,129,0.1); color:#10b981;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                  </svg>
                </div>
                <div class="metric-content">
                  <span class="metric-label">Tasks Completed</span>
                  <span class="metric-value">{{ teamOverview.totalTasksCompleted }}</span>
                  <span class="metric-sub">{{ teamOverview.tasksOnTime }} on time ({{ teamOverview.onTimeRate }}%)</span>
                </div>
              </div>

              <div class="metric-item">
                <div class="metric-icon" style="background:rgba(59,130,246,0.1); color:#3b82f6;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                  </svg>
                </div>
                <div class="metric-content">
                  <span class="metric-label">SLA Compliance</span>
                  <span class="metric-value">{{ teamOverview.slaCompliance }}%</span>
                  <span class="metric-sub">{{ teamOverview.slaMet }} of {{ teamOverview.totalSLA }} met</span>
                </div>
              </div>

              <div class="metric-item">
                <div class="metric-icon" style="background:rgba(245,158,11,0.1); color:#f59e0b;">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"></line>
                    <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                  </svg>
                </div>
                <div class="metric-content">
                  <span class="metric-label">Avg Hours/Task</span>
                  <span class="metric-value">{{ formatHours(teamOverview.avgHoursPerTask) }}</span>
                  <span class="metric-sub">{{ teamOverview.efficiencyRate }}% efficiency</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Performance Summary Card -->
          <div class="content-card">
            <div class="card-header">
              <h3>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 0.5rem;">
                  <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                  <line x1="3" y1="9" x2="21" y2="9"></line>
                  <line x1="9" y1="21" x2="9" y2="9"></line>
                </svg>
                Performance Summary
              </h3>
            </div>
            <div class="summary-grid">
              <div class="summary-item">
                <div class="summary-icon overdue">⏰</div>
                <div class="summary-content">
                  <span class="summary-value">{{ teamOverview.overdueTasks }}</span>
                  <span class="summary-label">Overdue Tasks</span>
                </div>
              </div>
              <div class="summary-item">
                <div class="summary-icon pending">⏳</div>
                <div class="summary-content">
                  <span class="summary-value">{{ teamOverview.pendingTasks }}</span>
                  <span class="summary-label">Pending Tasks</span>
                </div>
              </div>
              <div class="summary-item">
                <div class="summary-icon completed">✅</div>
                <div class="summary-content">
                  <span class="summary-value">{{ teamOverview.completedTasks }}</span>
                  <span class="summary-label">Completed Tasks</span>
                </div>
              </div>
              <div class="summary-item">
                <div class="summary-icon efficiency">📈</div>
                <div class="summary-content">
                  <span class="summary-value">{{ teamOverview.efficiencyRate }}%</span>
                  <span class="summary-label">Efficiency Rate</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Section -->
        <div v-if="showCharts && hasChartData" class="charts-grid">
          <!-- SLA Compliance Trend -->
          <div v-if="slaChartData" class="content-card chart-card">
            <div class="card-header">
              <h3>SLA Compliance Trend</h3>
            </div>
            <div class="chart-container">
              <LineChart :data="slaChartData" :options="slaChartOptions" />
            </div>
          </div>

          <!-- Task Distribution -->
          <div v-if="taskDistributionData" class="content-card chart-card">
            <div class="card-header">
              <h3>Task Completion Distribution</h3>
            </div>
            <div class="chart-container">
              <DoughnutChart :data="taskDistributionData" :options="taskDistributionOptions" />
            </div>
          </div>

          <!-- Weekly Performance -->
          <div v-if="weeklyPerformanceData" class="content-card chart-card">
            <div class="card-header">
              <h3>Weekly Performance</h3>
            </div>
            <div class="chart-container">
              <BarChart :data="weeklyPerformanceData" :options="weeklyPerformanceOptions" />
            </div>
          </div>

          <!-- Performance Timeline -->
          <div v-if="timelineData" class="content-card chart-card">
            <div class="card-header">
              <h3>Performance Timeline</h3>
            </div>
            <div class="chart-container">
              <LineChart :data="timelineData" :options="timelineOptions" />
            </div>
          </div>
        </div>

        <!-- Detailed Metrics -->
        <div v-if="showDetailedMetrics" class="metrics-grid">
          <!-- SLA Performance Details -->
          <div class="content-card">
            <div class="card-header">
              <h3>SLA Performance Details</h3>
            </div>
            <div class="sla-details">
              <div class="sla-metric">
                <div class="sla-header">
                  <span class="sla-title">Response Time SLA</span>
                  <span class="sla-rate">{{ slaDetails.responseTimeRate }}%</span>
                </div>
                <div class="progress-bar">
                  <div class="progress-fill" :style="{ width: slaDetails.responseTimeRate + '%' }"></div>
                </div>
                <div class="sla-stats">
                  <span>{{ slaDetails.responseTimeMet }} of {{ slaDetails.responseTimeTotal }} met</span>
                  <span class="avg-time">Avg: {{ formatHours(slaDetails.avgResponseTime) }}</span>
                </div>
              </div>

              <div class="sla-metric">
                <div class="sla-header">
                  <span class="sla-title">Resolution Time SLA</span>
                  <span class="sla-rate">{{ slaDetails.resolutionRate }}%</span>
                </div>
                <div class="progress-bar">
                  <div class="progress-fill" :style="{ width: slaDetails.resolutionRate + '%' }"></div>
                </div>
                <div class="sla-stats">
                  <span>{{ slaDetails.resolutionMet }} of {{ slaDetails.resolutionTotal }} met</span>
                  <span class="avg-time">Avg: {{ formatHours(slaDetails.avgResolutionTime) }}</span>
                </div>
              </div>

              <div class="sla-metric">
                <div class="sla-header">
                  <span class="sla-title">Task Completion SLA</span>
                  <span class="sla-rate">{{ slaDetails.completionRate }}%</span>
                </div>
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

          <!-- Task Completion Analysis -->
          <div class="content-card">
            <div class="card-header">
              <h3>Task Completion Analysis</h3>
            </div>
            <div class="completion-analysis">
              <div class="analysis-row">
                <span class="analysis-label">On Time Completion:</span>
                <span class="analysis-value">{{ taskAnalysis.onTimeRate }}%</span>
                <span :class="['analysis-trend', getTrendClass(taskAnalysis.onTimeTrend)]">
                  {{ getTrendIcon(taskAnalysis.onTimeTrend) }} {{ taskAnalysis.onTimeChange }}%
                </span>
              </div>
              <div class="analysis-row">
                <span class="analysis-label">Average Completion Time:</span>
                <span class="analysis-value">{{ formatHours(taskAnalysis.avgCompletionTime) }}</span>
                <span :class="['analysis-trend', getTrendClass(taskAnalysis.completionTimeTrend)]">
                  {{ getTrendIcon(taskAnalysis.completionTimeTrend) }} {{ taskAnalysis.completionTimeChange }}%
                </span>
              </div>
              <div class="analysis-row">
                <span class="analysis-label">Quality Score:</span>
                <span class="analysis-value">{{ taskAnalysis.qualityScore }}%</span>
                <span :class="['analysis-trend', getTrendClass(taskAnalysis.qualityTrend)]">
                  {{ getTrendIcon(taskAnalysis.qualityTrend) }} {{ taskAnalysis.qualityChange }}%
                </span>
              </div>
              <div class="analysis-row">
                <span class="analysis-label">Reopen Rate:</span>
                <span class="analysis-value">{{ taskAnalysis.reopenRate }}%</span>
                <span :class="['analysis-trend', getTrendClass(taskAnalysis.reopenTrend)]">
                  {{ getTrendIcon(taskAnalysis.reopenTrend) }} {{ taskAnalysis.reopenChange }}%
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Employees Productivity List -->
        <div v-if="productivityData.length > 0" class="content-card">
          <div class="card-header">
            <h3>Employee Performance</h3>
            <div class="header-controls">
              <div class="select-wrapper small">
                <select v-model="sortBy" @change="sortEmployees" class="header-select">
                  <option value="productivity_score">Productivity Score</option>
                  <option value="tasks_completed">Tasks Completed</option>
                  <option value="sla_compliance">SLA Compliance</option>
                  <option value="on_time_rate">On-Time Rate</option>
                </select>
                <svg class="select-chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
              </div>
              <div class="select-wrapper small">
                <select v-model="sortOrder" @change="sortEmployees" class="header-select">
                  <option value="desc">Highest First</option>
                  <option value="asc">Lowest First</option>
                </select>
                <svg class="select-chevron" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
              </div>
            </div>
          </div>

          <div class="employees-grid">
            <div v-for="employee in sortedProductivityData" :key="employee.id" class="employee-card">
              <div class="employee-header">
                <div class="employee-avatar">
                  {{ getInitials(employee.full_name) }}
                </div>
                <div class="employee-info">
                  <h4>{{ employee.full_name }}</h4>
                  <p class="employee-id">{{ employee.employee_id }}</p>
                  <div class="employee-meta">
                    <span class="meta-item">📍 {{ employee.department }}</span>
                    <span class="meta-item">👤 {{ employee.position }}</span>
                  </div>
                </div>
                <div class="employee-score">
                  <div class="score-main">{{ employee.productivity_score }}%</div>
                  <div :class="['score-trend', getTrendClass(employee.trend)]">
                    {{ getTrendIcon(employee.trend) }} {{ employee.trend_percentage }}%
                  </div>
                </div>
              </div>

              <div class="employee-metrics">
                <div class="metric-row">
                  <div class="metric-cell">
                    <span class="metric-label">Tasks</span>
                    <span class="metric-value">{{ employee.tasks_completed }}/{{ employee.total_tasks }}</span>
                    <div class="progress-bar">
                      <div class="progress-fill" :style="{ width: calculateCompletionPercentage(employee.tasks_completed, employee.total_tasks) + '%' }"></div>
                    </div>
                  </div>
                  <div class="metric-cell">
                    <span class="metric-label">On Time</span>
                    <span class="metric-value">{{ employee.on_time_rate }}%</span>
                    <div class="progress-bar">
                      <div class="progress-fill" :style="{ width: employee.on_time_rate + '%' }"></div>
                    </div>
                  </div>
                </div>

                <div class="metric-row">
                  <div class="metric-cell">
                    <span class="metric-label">SLA Compliance</span>
                    <span class="metric-value">{{ employee.sla_compliance }}%</span>
                    <div class="progress-bar">
                      <div class="progress-fill" :style="{ width: employee.sla_compliance + '%' }"></div>
                    </div>
                  </div>
                  <div class="metric-cell">
                    <span class="metric-label">Efficiency</span>
                    <span class="metric-value">{{ employee.efficiency_rate }}%</span>
                    <div class="progress-bar">
                      <div class="progress-fill" :style="{ width: employee.efficiency_rate + '%' }"></div>
                    </div>
                  </div>
                </div>

                <div class="performance-badge" :class="getPerformanceClass(employee.performance_level)">
                  {{ formatPerformanceLevel(employee.performance_level) }}
                </div>

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

              <div class="employee-actions">
                <button @click="viewDetails(employee)" class="btn-action btn-view">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"></circle>
                    <path d="M22 12c-2.667 4.667-6 7-10 7s-7.333-2.333-10-7c2.667-4.667 6-7 10-7s7.333 2.333 10 7z"></path>
                  </svg>
                  <span>View Details</span>
                </button>
                <button @click="sendFeedback(employee)" class="btn-action btn-feedback">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                  </svg>
                  <span>Feedback</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else-if="!isDemoMode" class="empty-state">
          <div class="empty-icon">📊</div>
          <h3>No Productivity Data Available</h3>
          <p>No employee productivity data found for the selected period ({{ formatPeriodText() }}).</p>
          <p class="empty-hint">Try selecting a different date range or check back later when more data is available.</p>
          <div class="empty-actions">
            <button @click="fetchProductivity" class="btn-accent">Refresh</button>
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
    const lastMonth = new Date()
    lastMonth.setMonth(lastMonth.getMonth() - 1)
    const lastMonthStart = new Date(lastMonth.getFullYear(), lastMonth.getMonth(), 1).toISOString().split('T')[0]
    const lastMonthEnd = new Date(lastMonth.getFullYear(), lastMonth.getMonth() + 1, 0).toISOString().split('T')[0]

    return {
      productivityData: [],
      teamOverview: this.getDefaultOverview(),
      slaDetails: this.getDefaultSlaDetails(),
      taskAnalysis: this.getDefaultTaskAnalysis(),
      periodInfo: {
        start: lastMonthStart,
        end: lastMonthEnd
      },
      loading: false,
      error: null,
      selectedPeriod: 'last_30_days',
      customStartDate: lastMonthStart,
      customEndDate: lastMonthEnd,
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

    formatPeriodTextShort() {
      switch (this.selectedPeriod) {
        case 'last_7_days': return '7D'
        case 'last_30_days': return '30D'
        case 'this_month': return 'MTD'
        case 'last_month': return 'L Mth'
        case 'custom': return 'Custom'
        default: return ''
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
      } else {
        // Set default period info based on selection
        const end = new Date()
        let start = new Date()
        if (this.selectedPeriod === 'last_7_days') {
          start.setDate(start.getDate() - 7)
        } else if (this.selectedPeriod === 'last_30_days') {
          start.setDate(start.getDate() - 30)
        } else if (this.selectedPeriod === 'this_month') {
          start = new Date(start.getFullYear(), start.getMonth(), 1)
        } else if (this.selectedPeriod === 'last_month') {
          start = new Date(start.getFullYear(), start.getMonth() - 1, 1)
          end.setDate(0) // Last day of previous month
        } else if (this.selectedPeriod === 'custom' && this.customStartDate && this.customEndDate) {
          start = new Date(this.customStartDate)
          end = new Date(this.customEndDate)
        }
        this.periodInfo = {
          start: start.toISOString().split('T')[0],
          end: end.toISOString().split('T')[0]
        }
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
/* ── Base ─────────────────────────────────────────────── */
.productivity-management {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
}

/* ── Main wrapper ─────────────────────────────────────── */
.management-main {
  max-width: 1400px;
  margin: 0 auto;
  padding: 1.5rem 2rem 3rem;
}

/* ── Header Card ──────────────────────────────────────── */
.management-header-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem 1.75rem;
  box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.03);
  border: 1px solid #e2e8f0;
  margin-bottom: 1.25rem;
  position: relative;
  overflow: hidden;
}

.header-card-accent {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  
}

.management-header-card::after {
  content: '';
  position: absolute;
  top: -20px;
  right: -20px;
  width: 160px;
  height: 160px;
  background: radial-gradient(circle, rgba(139, 92, 246, 0.05) 0%, transparent 70%);
  pointer-events: none;
}

.header-inner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.user-greeting {
  display: flex;
  align-items: center;
  gap: 2rem;
  flex: 1;
  flex-wrap: wrap;
}

/* Avatar */
.avatar-section {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.avatar {
  width: 52px;
  height: 52px;
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25);
  flex-shrink: 0;
}

.user-info {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.greeting {
  margin: 0;
  font-size: 1.375rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1.2;
}

.subtitle {
  margin: 0;
  color: #64748b;
  font-size: 0.875rem;
}

.role-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.125rem;
  flex-wrap: wrap;
}

.role-badge {
  background: #f5f3ff;
  border: 1px solid #ddd6fe;
  padding: 0.125rem 0.6rem;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 600;
  color: #6d28d9;
  display: inline-flex;
  align-items: center;
}

.month-badge {
  background: #ede9fe;
  border: 1px solid #ddd6fe;
  padding: 0.125rem 0.6rem;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 600;
  color: #7c3aed;
  display: inline-flex;
  align-items: center;
}

/* Controls */
.header-controls {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

/* Period Selector */
.period-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.select-wrapper {
  position: relative;
}

.select-wrapper.small {
  width: 140px;
}

.header-select {
  appearance: none;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  color: #475569;
  padding: 0.5rem 2rem 0.5rem 0.9rem;
  border-radius: 8px;
  font-family: 'Inter', system-ui, sans-serif;
  font-size: 0.875rem;
  cursor: pointer;
  min-width: 140px;
  transition: all 0.2s;
}

.header-select:focus {
  outline: none;
  border-color: #8b5cf6;
  box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
}

.select-chevron {
  position: absolute;
  right: 9px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
}

/* Custom Date Range */
.custom-date-range {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.25rem 0.5rem;
}

.date-input {
  padding: 0.4rem 0.5rem;
  border: none;
  background: transparent;
  font-size: 0.875rem;
  color: #1e293b;
  font-family: 'Inter', system-ui, sans-serif;
}

.date-input:focus {
  outline: none;
}

.date-separator {
  color: #94a3b8;
  font-size: 0.875rem;
}

/* Buttons */
.btn-icon {
  width: 38px;
  height: 38px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  color: #475569;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
  flex-shrink: 0;
}

.btn-icon:hover:not(:disabled) {
  background: #f1f5f9;
  border-color: #cbd5e1;
  color: #1e293b;
}

.btn-icon:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-accent {
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
  color: white;
  border: none;
  padding: 0.55rem 1.1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
  white-space: nowrap;
}

.btn-accent:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(139, 92, 246, 0.4);
}

.btn-accent:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  box-shadow: none;
}

.btn-secondary {
  background: white;
  border: 1px solid #e2e8f0;
  color: #475569;
  padding: 0.55rem 1.1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

/* View badge */
.view-badge {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0.75rem 1.125rem;
  min-width: 130px;
  flex-shrink: 0;
}

.view-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.view-primary {
  font-size: 1.5rem;
  font-weight: 700;
  color: #8b5cf6;
  line-height: 1;
}

.view-details {
  display: flex;
  flex-direction: column;
}

.view-label {
  font-size: 0.7rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.view-total {
  font-size: 0.7rem;
  color: #94a3b8;
}

/* Demo Warning */
.demo-warning {
  background: #fffbeb;
  border: 1px solid #f59e0b;
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 1.75rem;
}

.warning-content {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
}

.warning-icon {
  font-size: 1.25rem;
}

.warning-text {
  flex: 1;
}

.warning-text strong {
  display: block;
  color: #92400e;
  margin-bottom: 0.25rem;
  font-size: 0.9rem;
}

.warning-text p {
  margin: 0;
  color: #b45309;
  font-size: 0.85rem;
}

/* State Banners */
.state-banner {
  padding: 1.5rem;
  border-radius: 12px;
  text-align: center;
  margin-bottom: 1.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.state-banner.error {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fecaca;
}

.state-banner.loading {
  background: #f0f9ff;
  color: #0369a1;
  border: 1px solid #bae6fd;
}

.spinner {
  width: 28px;
  height: 28px;
  border: 3px solid #bae6fd;
  border-top-color: #0284c7;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.btn-text {
  background: none;
  border: none;
  color: #8b5cf6;
  font-weight: 600;
  cursor: pointer;
  margin-left: 0.75rem;
}

.btn-text:hover {
  text-decoration: underline;
}

.error-actions {
  display: flex;
  gap: 0.5rem;
}

/* ── Content Cards ────────────────────────────────────── */
.content-card {
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05);
  overflow: hidden;
  margin-bottom: 1.75rem;
}

.card-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #fcfcfc;
}

.card-header h3 {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: #334155;
  display: flex;
  align-items: center;
}

/* Overview Grid */
.overview-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 1.5rem;
  margin-bottom: 1.75rem;
}

@media (max-width: 1024px) {
  .overview-grid {
    grid-template-columns: 1fr;
  }
}

.overview-metrics {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  padding: 1.5rem;
}

@media (max-width: 640px) {
  .overview-metrics {
    grid-template-columns: 1fr;
  }
}

.metric-item {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 12px;
}

.metric-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.metric-content {
  flex: 1;
  min-width: 0;
}

.metric-label {
  display: block;
  font-size: 0.7rem;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.02em;
  margin-bottom: 0.25rem;
}

.metric-value {
  display: block;
  font-size: 1.5rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.1;
}

.metric-trend {
  font-size: 0.7rem;
  font-weight: 600;
  margin-top: 0.25rem;
}

.metric-sub {
  font-size: 0.7rem;
  color: #64748b;
  margin-top: 0.25rem;
}

/* Summary Grid */
.summary-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  padding: 1.5rem;
}

.summary-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f8fafc;
  border-radius: 12px;
}

.summary-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}

.summary-icon.overdue {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

.summary-icon.pending {
  background: rgba(245, 158, 11, 0.1);
  color: #f59e0b;
}

.summary-icon.completed {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
}

.summary-icon.efficiency {
  background: rgba(59, 130, 246, 0.1);
  color: #3b82f6;
}

.summary-content {
  flex: 1;
}

.summary-value {
  display: block;
  font-size: 1.25rem;
  font-weight: 700;
  color: #0f172a;
  line-height: 1.2;
}

.summary-label {
  font-size: 0.7rem;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

/* Charts Grid */
.charts-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
  margin-bottom: 1.75rem;
}

@media (max-width: 1024px) {
  .charts-grid {
    grid-template-columns: 1fr;
  }
}

.chart-card {
  padding: 1rem;
}

.chart-container {
  height: 250px;
  padding: 1rem;
  position: relative;
}

/* Metrics Grid */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
  margin-bottom: 1.75rem;
}

@media (max-width: 1024px) {
  .metrics-grid {
    grid-template-columns: 1fr;
  }
}

/* SLA Details */
.sla-details {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.sla-metric {
  background: #f8fafc;
  border-radius: 12px;
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
  color: #1e293b;
  font-size: 0.9rem;
}

.sla-rate {
  font-weight: 700;
  color: #10b981;
  font-size: 1rem;
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
  color: #64748b;
}

.avg-time {
  font-weight: 600;
  color: #475569;
}

/* Completion Analysis */
.completion-analysis {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.analysis-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: #f8fafc;
  border-radius: 8px;
}

.analysis-label {
  font-weight: 500;
  color: #475569;
  font-size: 0.85rem;
}

.analysis-value {
  font-weight: 700;
  color: #0f172a;
}

.analysis-trend {
  font-size: 0.7rem;
  font-weight: 600;
  padding: 0.2rem 0.5rem;
  border-radius: 12px;
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

/* Employees Grid */
.employees-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 1.5rem;
  padding: 1.5rem;
}

@media (max-width: 640px) {
  .employees-grid {
    grid-template-columns: 1fr;
  }
}

.employee-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.25rem;
  transition: all 0.2s;
}

.employee-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  border-color: #8b5cf6;
}

.employee-header {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e2e8f0;
}

.employee-avatar {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #8b5cf6, #7c3aed);
  border-radius: 12px;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 1rem;
  flex-shrink: 0;
}

.employee-info {
  flex: 1;
  min-width: 0;
}

.employee-info h4 {
  margin: 0 0 0.25rem 0;
  font-size: 1rem;
  font-weight: 700;
  color: #1e293b;
}

.employee-id {
  margin: 0 0 0.5rem 0;
  font-size: 0.7rem;
  color: #64748b;
  font-family: 'Inter', monospace;
}

.employee-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.meta-item {
  font-size: 0.65rem;
  color: #64748b;
  background: white;
  padding: 0.2rem 0.5rem;
  border-radius: 4px;
  border: 1px solid #e2e8f0;
}

.employee-score {
  text-align: right;
}

.score-main {
  font-size: 1.25rem;
  font-weight: 800;
  color: #0f172a;
}

.score-trend {
  font-size: 0.65rem;
  font-weight: 600;
  margin-top: 0.1rem;
}

/* Employee Metrics */
.employee-metrics {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  margin-bottom: 1rem;
}

.metric-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
}

.metric-cell {
  background: white;
  padding: 0.75rem;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.metric-label {
  display: block;
  font-size: 0.6rem;
  color: #64748b;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.02em;
  margin-bottom: 0.25rem;
}

.metric-value {
  display: block;
  font-size: 0.9rem;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 0.5rem;
}

/* Performance Badge */
.performance-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.65rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.02em;
  width: fit-content;
  margin-bottom: 0.75rem;
}

.performance-excellent {
  background: #ecfdf5;
  color: #10b981;
  border: 1px solid #d1fae5;
}

.performance-good {
  background: #eff6ff;
  color: #3b82f6;
  border: 1px solid #dbeafe;
}

.performance-average {
  background: #fffbeb;
  color: #f59e0b;
  border: 1px solid #fef3c7;
}

.performance-needs-improvement {
  background: #fef2f2;
  color: #ef4444;
  border: 1px solid #fee2e2;
}

.performance-poor {
  background: #f1f5f9;
  color: #64748b;
  border: 1px solid #e2e8f0;
}

/* Task Stats */
.task-stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.5rem;
  margin-top: 0.75rem;
}

.stat-item {
  text-align: center;
  padding: 0.5rem;
  background: white;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.stat-icon {
  display: block;
  font-size: 0.9rem;
  margin-bottom: 0.25rem;
}

.stat-icon.overdue {
  color: #ef4444;
}

.stat-icon.in-progress {
  color: #3b82f6;
}

.stat-icon.completed {
  color: #10b981;
}

.stat-icon.pending {
  color: #f59e0b;
}

.stat-count {
  display: block;
  font-weight: 700;
  color: #0f172a;
  font-size: 0.85rem;
}

.stat-label {
  display: block;
  font-size: 0.55rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

/* Employee Actions */
.employee-actions {
  display: flex;
  gap: 0.5rem;
  margin-top: 1rem;
}

.btn-action {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.25rem;
  padding: 0.5rem;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid #e2e8f0;
  background: white;
}

.btn-view {
  color: #8b5cf6;
  border-color: #8b5cf6;
}

.btn-view:hover {
  background: #f5f3ff;
  transform: translateY(-1px);
}

.btn-feedback {
  color: #3b82f6;
  border-color: #3b82f6;
}

.btn-feedback:hover {
  background: #eff6ff;
  transform: translateY(-1px);
}

/* Empty State */
.empty-state {
  text-align: center;
  padding: 3rem 1.5rem;
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-state h3 {
  margin: 0 0 0.5rem;
  font-size: 1.1rem;
  color: #1e293b;
}

.empty-state p {
  margin: 0.25rem 0;
  color: #64748b;
  font-size: 0.9rem;
}

.empty-hint {
  color: #94a3b8;
  font-size: 0.8rem;
  margin-top: 0.5rem;
}

.empty-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-top: 1.5rem;
}

/* Responsive */
@media (max-width: 1024px) {
  .management-main {
    padding: 1.5rem;
  }
}

@media (max-width: 768px) {
  .management-main {
    padding: 1rem;
  }
  
  .header-inner {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .user-greeting {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .header-controls {
    width: 100%;
    flex-wrap: wrap;
  }
  
  .greeting {
    font-size: 1.25rem;
  }
  
  .view-badge {
    align-self: flex-start;
  }
  
  .period-selector {
    width: 100%;
  }
  
  .select-wrapper {
    width: 100%;
  }
  
  .header-select {
    width: 100%;
    min-width: 0;
  }
  
  .custom-date-range {
    width: 100%;
    flex-direction: column;
  }
  
  .date-input {
    width: 100%;
  }
  
  .overview-metrics {
    padding: 1rem;
  }
  
  .summary-grid {
    padding: 1rem;
  }
  
  .employees-grid {
    padding: 1rem;
  }
  
  .employee-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .employee-score {
    text-align: left;
    width: 100%;
  }
  
  .task-stats {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .header-controls {
    flex-direction: column;
  }
  
  .btn-accent {
    width: 100%;
    justify-content: center;
  }
  
  .role-meta {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .metric-row {
    grid-template-columns: 1fr;
  }
  
  .employee-actions {
    flex-direction: column;
  }
  
  .btn-action {
    width: 100%;
  }
  
  .empty-actions {
    flex-direction: column;
  }
}
</style>