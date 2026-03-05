<template>
  <div class="admin-dashboard">
    <div class="dashboard-main">

      <!-- ── Header Card ─────────────────────────────── -->
      <div class="dashboard-header-card">
        <div class="header-card-accent"></div>
        <div class="header-inner">

          <!-- Brand + Title -->
          <div class="user-greeting">
            <div class="avatar-section">
              <div class="avatar">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                  fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
              </div>
              <div class="user-info">
                <h1 class="greeting">Admin Dashboard</h1>
                <p class="subtitle">Overview of payroll, attendance &amp; employee metrics</p>
                <div class="role-meta">
                  <span class="role-badge">Admin View</span>
                  <span class="month-badge">{{ currentMonth }} {{ currentYear }}</span>
                  <!-- Shows the active business name in the header meta row -->
                  <span v-if="activeBusiness" class="business-badge">
                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24"
                      fill="none" stroke="currentColor" stroke-width="2.5">
                      <rect x="2" y="7" width="20" height="14" rx="2"/>
                      <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                    </svg>
                    {{ activeBusiness.name }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Controls -->
            <div class="header-controls">

              <!-- ══════════════════════════════════════════
                   Business Switcher
              ═══════════════════════════════════════════ -->
              <div class="biz-switcher" :class="{ open: switcherOpen }" ref="switcherWrap">

                <button class="biz-trigger" @click="toggleSwitcher">
                  <span v-if="activeBusiness" class="biz-trigger-avatar"
                    :style="{ background: bizColor(activeBusiness.id) }">
                    {{ activeBusiness.name.charAt(0).toUpperCase() }}
                  </span>
                  <span v-else class="biz-trigger-avatar globe">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"
                      fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="10"/>
                      <line x1="2" y1="12" x2="22" y2="12"/>
                      <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10
                               15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                    </svg>
                  </span>
                  <span class="biz-trigger-label">
                    {{ activeBusiness ? activeBusiness.name : 'All Businesses' }}
                  </span>
                  <svg class="biz-trigger-chevron" xmlns="http://www.w3.org/2000/svg"
                    width="13" height="13" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 12 15 18 9"/>
                  </svg>
                </button>

                <!-- Dropdown -->
                <Transition name="biz-drop">
                  <div v-if="switcherOpen" class="biz-dropdown">

                    <!-- Search -->
                    <div class="biz-search-row">
                      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                      </svg>
                      <input
                        ref="searchInput"
                        v-model="switcherSearch"
                        class="biz-search-input"
                        placeholder="Search businesses…"
                        @keydown.esc="closeSwitcher"
                      />
                      <span v-if="switcherSearch" class="biz-search-clear" @click="switcherSearch = ''">✕</span>
                    </div>

                    <!-- Loading state -->
                    <div v-if="loadingBusinesses" class="biz-list-state">
                      <div class="mini-spin"></div> Loading…
                    </div>

                    <!-- List -->
                    <ul v-else class="biz-list">

                      <!-- "All Businesses" option -->
                      <li>
                        <button class="biz-item" :class="{ active: !activeBusiness }"
                          @click="selectBusiness(null)">
                          <span class="biz-item-avatar globe">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13"
                              viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                              <circle cx="12" cy="12" r="10"/>
                              <line x1="2" y1="12" x2="22" y2="12"/>
                              <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10
                                       15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                            </svg>
                          </span>
                          <span class="biz-item-name">All Businesses</span>
                          <svg v-if="!activeBusiness" class="biz-item-check"
                            xmlns="http://www.w3.org/2000/svg" width="13" height="13"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"/>
                          </svg>
                        </button>
                      </li>

                      <!-- Fetched businesses -->
                      <li v-for="biz in filteredBusinesses" :key="biz.id">
                        <button class="biz-item" :class="{ active: activeBusiness?.id === biz.id }"
                          @click="selectBusiness(biz)">
                          <span class="biz-item-avatar" :style="{ background: bizColor(biz.id) }">
                            {{ biz.name.charAt(0).toUpperCase() }}
                          </span>
                          <span class="biz-item-info">
                            <span class="biz-item-name">{{ biz.name }}</span>
                            <span v-if="biz.legal_name && biz.legal_name !== biz.name"
                              class="biz-item-sub">{{ biz.legal_name }}</span>
                          </span>
                          <svg v-if="activeBusiness?.id === biz.id" class="biz-item-check"
                            xmlns="http://www.w3.org/2000/svg" width="13" height="13"
                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"/>
                          </svg>
                        </button>
                      </li>

                      <!-- Empty search result -->
                      <li v-if="!filteredBusinesses.length && switcherSearch" class="biz-list-state">
                        No match for "{{ switcherSearch }}"
                      </li>

                      <!-- No businesses at all -->
                      <li v-if="!businesses.length && !loadingBusinesses && !switcherSearch"
                        class="biz-list-state">
                        No businesses found
                      </li>
                    </ul>

                    <!-- Footer: go to Business Management -->
                    <div class="biz-dropdown-footer">
                      <button class="biz-manage-link" @click="goToBusinessManagement">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12"
                          viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                          <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                          <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        Manage Businesses
                      </button>
                    </div>

                  </div>
                </Transition>
              </div>
              <!-- ── End Business Switcher ─────────────── -->

              <!-- Refresh button — compact 28×28, icon 10×10 -->
              <button @click="refreshData" class="btn-icon-sm" title="Refresh Data">
                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24"
                  fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M23 4v6h-6"/><path d="M1 20v-6h6"/>
                  <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                </svg>
              </button>

              <button @click="processPayroll" class="btn-accent">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24"
                  fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="2" y="4" width="20" height="16" rx="2"/>
                  <path d="M6 8h12M6 12h12M6 16h6"/>
                </svg>
                Process Payroll
              </button>
            </div>
          </div>

          <!-- Date badge -->
          <div class="date-badge">
            <div class="date-content">
              <span class="day">{{ currentDay }}</span>
              <div class="date-details">
                <span class="date-num">{{ currentDateNum }}</span>
                <span class="month-year">{{ currentMonthYear }}</span>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- ── Switch confirmation toast ──────────────── -->
      <Transition name="toast-drop">
        <div v-if="switchToast" class="switch-toast">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24"
            fill="none" stroke="currentColor" stroke-width="2">
            <rect x="2" y="7" width="20" height="14" rx="2"/>
            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
          </svg>
          {{ switchToast }}
        </div>
      </Transition>

      <!-- ── Auth / Permission / Loading / Error guards ── -->
      <div v-if="!authStore.isAuthenticated" class="state-banner error">
        Please log in to access the dashboard.
      </div>
      <div v-else-if="!authStore.isAdmin" class="state-banner error">
        Access Denied. Admin privileges required.
      </div>
      <div v-else-if="loading" class="state-banner loading">
        <div class="spinner"></div>
        <span>Loading dashboard metrics...</span>
      </div>
      <div v-else-if="error" class="state-banner error">
        {{ error }}
        <button @click="retryFetch" class="btn-text">Retry</button>
      </div>

      <!-- ── Dashboard Content ───────────────────────── -->
      <div v-else class="dashboard-content">

        <!-- 1. Stats Grid -->
        <div class="stats-grid">

          <div class="stat-card" style="--accent:#3b82f6;">
            <div class="stat-icon-wrap" style="background:rgba(59,130,246,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
              </svg>
            </div>
            <div class="stat-info">
              <span class="stat-label">Total Employees</span>
              <div class="stat-number">{{ stats.totalEmployees }}</div>
              <div class="stat-trend" v-if="stats.newEmployees > 0">
                <span class="trend-up">↑ {{ stats.newEmployees }}</span> new this month
              </div>
            </div>
          </div>

          <div class="stat-card" style="--accent:#10b981;">
            <div class="stat-icon-wrap" style="background:rgba(16,185,129,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
            </div>
            <div class="stat-info">
              <span class="stat-label">Present Today</span>
              <div class="stat-number">{{ stats.activeToday }}</div>
              <div class="stat-trend">
                <span :class="stats.attendanceRate > 80 ? 'trend-up' : 'trend-down'">
                  {{ stats.attendanceRate }}%
                </span> attendance rate
              </div>
            </div>
          </div>

          <div class="stat-card" style="--accent:#f59e0b;">
            <div class="stat-icon-wrap" style="background:rgba(245,158,11,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
              </svg>
            </div>
            <div class="stat-info">
              <span class="stat-label">On Leave</span>
              <div class="stat-number">{{ stats.onLeave }}</div>
              <div class="stat-trend" v-if="stats.pendingLeaves > 0">
                <span class="trend-warn">{{ stats.pendingLeaves }}</span> pending requests
              </div>
            </div>
          </div>

          <div class="stat-card" style="--accent:#3b82f6;">
            <div class="stat-icon-wrap" style="background:rgba(59,130,246,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                fill="none" stroke="#3b82f6" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="1" x2="12" y2="23"/>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
              </svg>
            </div>
            <div class="stat-info">
              <span class="stat-label">Payroll ({{ currentMonth }})</span>
              <div class="stat-number small">K{{ formatNumber(stats.monthlyPayroll) }}</div>
              <div class="stat-trend">Total processed</div>
            </div>
          </div>

        </div>

        <!-- 2. Quick Actions -->
        <div class="section-container">
          <h2 class="section-title">Quick Actions</h2>
          <div class="quick-actions-grid">

            <div class="action-card" @click="navigateToName('EmployeeManagement')">
              <div class="ql-icon" style="background:#eff6ff;color:#3b82f6;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                  fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                  <circle cx="12" cy="7" r="4"/>
                </svg>
              </div>
              <span>Add Employee</span>
            </div>

            <div class="action-card" @click="navigateToName('PayslipGeneration')">
              <div class="ql-icon" style="background:#ecfdf5;color:#10b981;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                  fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                  <polyline points="14 2 14 8 20 8"/>
                  <line x1="16" y1="13" x2="8" y2="13"/>
                  <line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
              </div>
              <span>Generate Payslips</span>
            </div>

            <div class="action-card" @click="navigateToName('AdminReports')">
              <div class="ql-icon" style="background:#fffbeb;color:#f59e0b;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                  fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
              </div>
              <span>View Reports</span>
            </div>

            <div class="action-card" @click="navigateToName('TaxConfiguration')">
              <div class="ql-icon" style="background:#eff6ff;color:#3b82f6;">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                  fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="12" cy="12" r="3"/>
                  <path d="M19.07 4.93l-1.41 1.41M5.34 18.66l-1.41 1.41
                           M19.07 19.07l-1.41-1.41M5.34 5.34L3.93 3.93
                           M22 12h-2M4 12H2M12 22v-2M12 4V2"/>
                </svg>
              </div>
              <span>Tax Settings</span>
            </div>

          </div>
        </div>

        <!-- 3. Main Columns -->
        <div class="dashboard-columns">

          <!-- Left: Activity + Departments -->
          <div class="column-left">

            <div class="content-card">
              <div class="card-header">
                <h3>Recent Activity</h3>
                <button @click="navigateToName('AuditLogs')" class="btn-link">View All →</button>
              </div>
              <div class="activity-list">
                <div v-for="act in recentActivity" :key="act.id" class="activity-item">
                  <div class="act-dot" :style="{ backgroundColor: act.color }"></div>
                  <div class="act-details">
                    <p class="act-text">{{ act.text }}</p>
                    <span class="act-time">{{ act.time }}</span>
                  </div>
                </div>
                <div v-if="recentActivity.length === 0" class="empty-placeholder">No recent activity</div>
              </div>
            </div>

            <div class="content-card">
              <div class="card-header">
                <h3>Department Snapshot</h3>
              </div>
              <div class="dept-list">
                <div v-for="dept in departments" :key="dept.id" class="dept-row">
                  <div class="dept-info">
                    <span class="dept-name">{{ dept.name }}</span>
                    <span class="dept-count">{{ dept.employees }} staff</span>
                  </div>
                  <div class="dept-status">
                    <div class="progress-bar">
                      <div class="progress-fill" :style="{ width: dept.attendancePct + '%' }"></div>
                    </div>
                    <span class="dept-pct">{{ dept.attendancePct }}% Present</span>
                  </div>
                </div>
                <div v-if="departments.length === 0" class="empty-placeholder">No department data</div>
              </div>
            </div>

          </div>

          <!-- Right: Approvals -->
          <div class="column-right">
            <div class="content-card">
              <div class="card-header">
                <h3>Pending Approvals</h3>
                <span class="count-badge">{{ pendingApprovals.length }}</span>
              </div>
              <div class="approvals-list">
                <div v-for="app in pendingApprovals" :key="app.id" class="approval-card">
                  <div class="app-meta-row">
                    <span class="app-type">{{ app.type }}</span>
                    <span class="app-date">{{ app.date }}</span>
                  </div>
                  <p class="app-user">{{ app.name }}</p>
                  <div class="app-actions">
                    <button @click="approve(app.id)" class="btn-approve-sm">
                      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20 6 9 17 4 12"/>
                      </svg>
                      Approve
                    </button>
                    <button @click="reject(app.id)" class="btn-reject-sm">
                      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24"
                        fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                      </svg>
                      Reject
                    </button>
                  </div>
                </div>

                <div v-if="pendingApprovals.length === 0" class="empty-approvals">
                  <div class="empty-check">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24"
                      fill="none" stroke="#10b981" stroke-width="2.5">
                      <polyline points="20 6 9 17 4 12"/>
                    </svg>
                  </div>
                  <p>All caught up!</p>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>

    </div>
  </div>
</template>

<script>
import { useAuthStore }     from '@/stores/auth'
import { useBusinessStore } from '@/stores/business'
import axios from 'axios'
import { useDeviceDetection } from '@/utils/deviceDetection'

export default {
  name: 'AdminDashboard',

  setup() {
    const { isMobile, breakpoint } = useDeviceDetection()
    const authStore     = useAuthStore()
    const businessStore = useBusinessStore()
    return { authStore, businessStore }
  },

  data() {
    const now = new Date()
    return {
      // ── Businesses ────────────────────────────────────────────────────────
      businesses:        [],
      loadingBusinesses: false,
      activeBusiness: null,

      // ── Switcher UI ───────────────────────────────────────────────────────
      switcherOpen:   false,
      switcherSearch: '',
      switchToast:    null,
      _toastTimer:    null,

      // ── Dashboard data ────────────────────────────────────────────────────
      stats: {
        totalEmployees: 0, newEmployees:   0,
        activeToday:    0, attendanceRate: 0,
        onLeave:        0, pendingLeaves:  0,
        monthlyPayroll: 0, payrollChange:  0,
      },
      recentActivity:   [],
      pendingApprovals: [],
      departments:      [],
      loading:    false,
      error:      null,
      retryCount: 0,

      // ── Date display ──────────────────────────────────────────────────────
      currentDay:       now.toLocaleDateString('en-US', { weekday: 'long' }),
      currentDateNum:   now.getDate(),
      currentMonthYear: now.toLocaleDateString('en-US', { month: 'short', year: 'numeric' }),
      currentMonth:     now.toLocaleString('default', { month: 'long' }),
      currentYear:      now.getFullYear(),
    }
  },

  computed: {
    selectedBusinessId() {
      return this.activeBusiness?.id ?? ''
    },
    filteredBusinesses() {
      const q = this.switcherSearch.trim().toLowerCase()
      if (!q) return this.businesses
      return this.businesses.filter(b =>
        b.name.toLowerCase().includes(q) ||
        (b.legal_name || '').toLowerCase().includes(q)
      )
    },
  },

  mounted() {
    const stored = this.businessStore.getCurrentBusiness
    if (stored) this.activeBusiness = stored

    document.addEventListener('mousedown', this._onClickOutside)
    this.initializeComponent()
  },

  beforeUnmount() {
    document.removeEventListener('mousedown', this._onClickOutside)
    clearTimeout(this._toastTimer)
  },

  methods: {

    async initializeComponent() {
      if (!this.authStore.isAuthenticated || !this.authStore.isAdmin) return
      await this.fetchBusinesses()
      this.fetchDashboardData()
    },

    async fetchBusinesses() {
      this.loadingBusinesses = true
      try {
        const res       = await axios.get('/api/admin/businesses')
        this.businesses = res.data.data || res.data.businesses || []

        if (this.activeBusiness) {
          const fresh = this.businesses.find(b => b.id === this.activeBusiness.id)
          if (fresh) this.activeBusiness = fresh
        }
      } catch (e) {
        console.error('Failed to load businesses', e)
      } finally {
        this.loadingBusinesses = false
      }
    },

    toggleSwitcher() {
      this.switcherOpen = !this.switcherOpen
      if (this.switcherOpen) {
        this.switcherSearch = ''
        this.$nextTick(() => this.$refs.searchInput?.focus())
      }
    },

    closeSwitcher() {
      this.switcherOpen = false
    },

    _onClickOutside(e) {
      if (this.$refs.switcherWrap && !this.$refs.switcherWrap.contains(e.target)) {
        this.closeSwitcher()
      }
    },

    selectBusiness(biz) {
      this.closeSwitcher()

      if (biz?.id === this.activeBusiness?.id) return
      if (!biz && !this.activeBusiness)        return

      this.activeBusiness = biz

      try {
        if (typeof this.businessStore.switchBusiness === 'function') {
          this.businessStore.switchBusiness(biz?.id ?? null)
        } else if (typeof this.businessStore.setCurrentBusiness === 'function') {
          this.businessStore.setCurrentBusiness(biz)
        } else {
          this.businessStore.currentBusiness = biz
        }
      } catch (err) {
        console.warn('[AdminDashboard] Could not sync businessStore:', err)
      }

      this.showToast(biz ? `Switched to ${biz.name}` : 'Showing all businesses')

      this.retryCount = 0
      this.fetchDashboardData()
    },

    goToBusinessManagement() {
      this.closeSwitcher()
      this.$router.push({ name: 'BusinessManagement' })
    },

    showToast(msg) {
      this.switchToast = msg
      clearTimeout(this._toastTimer)
      this._toastTimer = setTimeout(() => { this.switchToast = null }, 3500)
    },

    bizColor(id) {
      const palette = ['#3b82f6','#10b981','#f59e0b','#2563eb','#ef4444','#06b6d4','#f97316','#84cc16']
      return palette[id % palette.length]
    },

    async fetchDashboardData() {
      this.loading = true
      this.error   = null
      try {
        const params = { business_id: this.selectedBusinessId || undefined }
        const today  = new Date().toISOString().split('T')[0]

        const [statsRes, attRes, leaveRes, auditRes] = await Promise.all([
          axios.get('/api/admin/stats',                { params }),
          axios.get('/api/admin/attendance/status',    { params: { ...params, date: today } }),
          axios.get('/api/admin/leaves/current-month', { params }),
          axios.get('/api/admin/audit-logs',           { params: { ...params, limit: 5 } }),
        ])

        const statsData           = statsRes.data.stats || statsRes.data || {}
        this.stats.totalEmployees = statsData.total_employees || 0
        this.stats.newEmployees   = 0

        const attSummary          = (attRes.data || {}).summary || {}
        this.stats.activeToday    = attSummary.present_count || 0
        this.stats.attendanceRate = this.stats.totalEmployees > 0
          ? Math.round((this.stats.activeToday / this.stats.totalEmployees) * 100)
          : 0

        const leaveData           = leaveRes.data || {}
        this.stats.onLeave        = leaveData.on_leave_count || 0
        this.stats.pendingLeaves  = leaveData.pending_count  || 0

        this.pendingApprovals = Array.isArray(leaveData.pending_leaves)
          ? leaveData.pending_leaves.slice(0, 5).map(l => ({
              id:   l.id,
              name: l.employee?.first_name
                ? `${l.employee.first_name} ${l.employee.last_name}`
                : `Employee #${l.employee_id}`,
              type: this.formatLeaveType(l.leave_type),
              date: this.formatDateRange(l.start_date, l.end_date),
            }))
          : []

        this.stats.monthlyPayroll = statsData.current_month_payroll_amount || 0

        const auditData     = auditRes.data.data || auditRes.data || []
        this.recentActivity = auditData.slice(0, 5).map(log => ({
          id:    log.id,
          text:  log.description || log.action || 'Activity',
          time:  this.formatRelativeTime(log.created_at),
          color: this.getActivityColor(log.action),
        }))

        const attList = (attRes.data || {}).data || []
        const deptMap = {}
        attList.forEach(att => {
          const d = att.department || 'Unassigned'
          if (!deptMap[d]) deptMap[d] = { id: d, name: d, employees: 0, present: 0 }
          deptMap[d].employees++
          if (['present', 'completed'].includes(att.status)) deptMap[d].present++
        })
        this.departments = Object.values(deptMap).map(d => ({
          ...d,
          attendancePct: d.employees > 0
            ? Math.round((d.present / d.employees) * 100) : 0,
        }))

      } catch (err) {
        console.error('Dashboard fetch error:', err)
        this.handleApiError(err)
      } finally {
        this.loading = false
      }
    },

    formatLeaveType(type) {
      return (type || 'Leave').replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())
    },
    formatDateRange(start, end) {
      const fmt = d => new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
      return `${fmt(start)} – ${fmt(end)}`
    },
    getActivityColor(action) {
      if (!action) return '#94a3b8'
      if (action.includes('delete') || action.includes('reject'))                           return '#ef4444'
      if (action.includes('create') || action.includes('add') || action.includes('approve')) return '#10b981'
      if (action.includes('update') || action.includes('edit'))                             return '#3b82f6'
      return '#2563eb'
    },
    formatRelativeTime(ts) {
      const diff = Date.now() - new Date(ts)
      const mins = Math.floor(diff / 60000)
      if (mins < 60)  return `${mins}m ago`
      const hrs = Math.floor(mins / 60)
      if (hrs  < 24)  return `${hrs}h ago`
      return `${Math.floor(hrs / 24)}d ago`
    },
    formatNumber(num) {
      return new Intl.NumberFormat('en-US').format(num || 0)
    },
    retryFetch() {
      this.retryCount++
      if (this.retryCount <= 3) this.fetchDashboardData()
      else this.error = 'Failed to load data after retries.'
    },
    refreshData() {
      this.retryCount = 0
      this.fetchDashboardData()
    },
    processPayroll()     { this.navigateToName('PayrollProcessing') },
    navigateToName(name) { this.$router.push({ name }) },

    async approve(id) {
      if (!confirm('Approve this request?')) return
      try {
        await axios.post(`/api/manager/leaves/${id}/approve`)
        this.pendingApprovals = this.pendingApprovals.filter(a => a.id !== id)
        this.stats.pendingLeaves--
      } catch { alert('Action failed') }
    },
    async reject(id) {
      if (!confirm('Reject this request?')) return
      try {
        await axios.post(`/api/manager/leaves/${id}/reject`)
        this.pendingApprovals = this.pendingApprovals.filter(a => a.id !== id)
        this.stats.pendingLeaves--
      } catch { alert('Action failed') }
    },
    handleApiError(err) {
      if (err.response?.status === 401) {
        this.authStore.clearAuth()
        this.$router.push({ name: 'login' })
      }
      this.error = err.response?.data?.message || 'Failed to load dashboard data.'
    },
  },
}
</script>

<style scoped>
/* ── Base ─────────────────────────────────────────────── */
.admin-dashboard {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
}

.dashboard-main {
  max-width: 1400px;
  margin: 0 auto;
  padding: 1.5rem 2rem 3rem;
}

/* ── Header Card ──────────────────────────────────────── */
.dashboard-header-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem 1.75rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0;
  margin-bottom: 1.25rem;
  position: relative;
  overflow: visible;
}

.header-card-accent {
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
  border-radius: 16px 16px 0 0;
}

.dashboard-header-card::after {
  content: ''; position: absolute; top: -20px; right: -20px;
  width: 160px; height: 160px;
  background: radial-gradient(circle, rgba(59,130,246,0.05) 0%, transparent 70%);
  pointer-events: none;
}

.header-inner {
  display: flex; justify-content: space-between;
  align-items: center; gap: 1.5rem; flex-wrap: wrap;
}

.user-greeting { display: flex; align-items: center; gap: 2rem; flex: 1; flex-wrap: wrap; }
.avatar-section { display: flex; align-items: center; gap: 1rem; }

.avatar {
  width: 52px; height: 52px;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  border-radius: 14px; display: flex; align-items: center;
  justify-content: center; color: white;
  box-shadow: 0 4px 12px rgba(59,130,246,0.25); flex-shrink: 0;
}

.user-info  { display: flex; flex-direction: column; gap: 0.2rem; }
.greeting   { margin: 0; font-size: 1.375rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
.subtitle   { margin: 0; color: #64748b; font-size: 0.875rem; }
.role-meta  { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.125rem; flex-wrap: wrap; }

.role-badge {
  background: #eff6ff; border: 1px solid #bfdbfe;
  padding: 0.125rem 0.6rem; border-radius: 8px;
  font-size: 0.7rem; font-weight: 600; color: #1d4ed8;
  display: inline-flex; align-items: center;
}
.month-badge {
  background: #dbeafe; border: 1px solid #bfdbfe;
  padding: 0.125rem 0.6rem; border-radius: 8px;
  font-size: 0.7rem; font-weight: 600; color: #2563eb;
  display: inline-flex; align-items: center;
}
.business-badge {
  background: #f0fdf4; border: 1px solid #bbf7d0;
  padding: 0.125rem 0.6rem; border-radius: 8px;
  font-size: 0.7rem; font-weight: 600; color: #166534;
  display: inline-flex; align-items: center; gap: 0.3rem;
}

/* ── Header Controls ──────────────────────────────────── */
.header-controls { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }

/* ══════════════════════════════════════════════════════
   Business Switcher
════════════════════════════════════════════════════════ */
.biz-switcher { position: relative; }

.biz-trigger {
  display: flex; align-items: center; gap: 0.5rem;
  background: #f8fafc; border: 1px solid #e2e8f0;
  border-radius: 8px; padding: 0.45rem 0.75rem;
  font-size: 0.875rem; font-weight: 600; color: #374151;
  cursor: pointer; transition: all 0.2s;
  min-width: 160px; max-width: 230px;
  font-family: inherit;
}
.biz-trigger:hover,
.biz-switcher.open .biz-trigger {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
  color: #1d4ed8;
}

.biz-trigger-avatar {
  width: 22px; height: 22px; border-radius: 5px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.7rem; font-weight: 700; color: white;
}
.biz-trigger-avatar.globe { background: #e2e8f0; color: #64748b; }

.biz-trigger-label {
  flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  text-align: left;
}

.biz-trigger-chevron {
  flex-shrink: 0; color: #9ca3af; transition: transform 0.2s;
}
.biz-switcher.open .biz-trigger-chevron { transform: rotate(180deg); }

/* Dropdown panel */
.biz-dropdown {
  position: absolute; top: calc(100% + 8px); left: 0;
  background: white; border: 1px solid #e2e8f0;
  border-radius: 12px; box-shadow: 0 16px 48px rgba(0,0,0,0.13);
  width: 290px; z-index: 9999; overflow: hidden;
}

/* Search row */
.biz-search-row {
  display: flex; align-items: center; gap: 0.5rem;
  padding: 0.7rem 1rem; border-bottom: 1px solid #f1f5f9; color: #9ca3af;
}
.biz-search-input {
  flex: 1; border: none; outline: none;
  font-size: 0.875rem; color: #1e293b; background: transparent; font-family: inherit;
}
.biz-search-input::placeholder { color: #cbd5e1; }
.biz-search-clear {
  font-size: 0.7rem; color: #9ca3af; cursor: pointer;
  line-height: 1; padding: 2px 4px; border-radius: 4px;
}
.biz-search-clear:hover { background: #f1f5f9; color: #475569; }

/* List */
.biz-list {
  list-style: none; margin: 0; padding: 0.375rem;
  max-height: 268px; overflow-y: auto;
}
.biz-list::-webkit-scrollbar       { width: 4px; }
.biz-list::-webkit-scrollbar-track { background: transparent; }
.biz-list::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }

.biz-item {
  display: flex; align-items: center; gap: 0.65rem;
  width: 100%; padding: 0.6rem 0.75rem;
  border: none; border-radius: 8px; background: transparent;
  cursor: pointer; text-align: left; transition: background 0.15s;
  font-family: inherit;
}
.biz-item:hover  { background: #f8fafc; }
.biz-item.active { background: #eff6ff; }

.biz-item-avatar {
  width: 28px; height: 28px; border-radius: 7px;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.75rem; font-weight: 700; color: white; flex-shrink: 0;
}
.biz-item-avatar.globe { background: #f1f5f9 !important; color: #64748b !important; }

.biz-item-info { display: flex; flex-direction: column; flex: 1; min-width: 0; }
.biz-item-name {
  font-size: 0.875rem; font-weight: 600; color: #1e293b;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.biz-item-sub {
  font-size: 0.7rem; color: #94a3b8;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.biz-item-check { color: #3b82f6; flex-shrink: 0; margin-left: auto; }

/* Empty / loading inside list */
.biz-list-state {
  display: flex; align-items: center; justify-content: center; gap: 0.5rem;
  padding: 1.25rem; font-size: 0.8rem; color: #94a3b8;
}
.mini-spin {
  width: 14px; height: 14px;
  border: 2px solid #e2e8f0; border-top-color: #3b82f6;
  border-radius: 50%; animation: spin 0.8s linear infinite;
}

/* Footer */
.biz-dropdown-footer { border-top: 1px solid #f1f5f9; padding: 0.5rem; }
.biz-manage-link {
  display: flex; align-items: center; gap: 0.5rem;
  width: 100%; padding: 0.6rem 0.75rem;
  border: none; border-radius: 8px; background: transparent;
  font-size: 0.8rem; font-weight: 600; color: #1d4ed8;
  cursor: pointer; transition: background 0.15s; font-family: inherit;
}
.biz-manage-link:hover { background: #eff6ff; }

/* Dropdown animation */
.biz-drop-enter-active, .biz-drop-leave-active {
  transition: opacity 0.18s ease, transform 0.18s ease;
}
.biz-drop-enter-from, .biz-drop-leave-to {
  opacity: 0; transform: translateY(-6px) scale(0.98);
}

/* ── Switch toast ─────────────────────────────────────── */
.switch-toast {
  display: flex; align-items: center; gap: 0.5rem;
  background: #f0fdf4; border: 1px solid #bbf7d0;
  color: #166534; padding: 0.625rem 1rem;
  border-radius: 10px; font-size: 0.875rem; font-weight: 600;
  margin-bottom: 1rem;
  box-shadow: 0 4px 12px rgba(16,185,129,0.12);
}
.toast-drop-enter-active, .toast-drop-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}
.toast-drop-enter-from, .toast-drop-leave-to {
  opacity: 0; transform: translateY(-8px);
}

/* ── Buttons ──────────────────────────────────────────── */
/* Compact refresh button — 28×28px, icon 10×10 */
.btn-icon-sm {
  width: 28px; height: 28px; background: #f8fafc;
  border: 1px solid #e2e8f0; border-radius: 7px; color: #64748b;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.2s; flex-shrink: 0; padding: 0;
}
.btn-icon-sm:hover { background: #eff6ff; border-color: #bfdbfe; color: #2563eb; }

.btn-accent {
  background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;
  border: none; padding: 0.55rem 1.1rem; border-radius: 8px;
  font-size: 0.875rem; font-weight: 600; cursor: pointer;
  display: flex; align-items: center; gap: 0.4rem;
  transition: all 0.2s; box-shadow: 0 4px 12px rgba(59,130,246,0.3); white-space: nowrap;
}
.btn-accent:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(59,130,246,0.4); }

/* ── Date badge ───────────────────────────────────────── */
.date-badge {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0.75rem 1.125rem;
  min-width: 130px;
  flex-shrink: 0;
}

.date-content {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  white-space: nowrap;
}

.day {
  font-size: 1rem;
  font-weight: 700;
  color: #3b82f6;
  padding-right: 0.25rem;
}

.date-details {
  display: flex;
  align-items: baseline;
  gap: 0.25rem;
}

.date-num {
  font-size: 1rem;
  font-weight: 700;
  color: #1e293b;
}

.month-year {
  font-size: 0.8rem;
  color: #94a3b8;
  font-weight: 500;
}

.date-num::after {
  content: '';
  display: inline-block;
  width: 1px;
  height: 12px;
  background: #e2e8f0;
  margin: 0 0.5rem;
  vertical-align: middle;
}

/* ── Dashboard Content ────────────────────────────────── */
.dashboard-content { display: flex; flex-direction: column; gap: 1.75rem; }

/* ── Stats Grid ───────────────────────────────────────── */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px,1fr)); gap: 1.25rem; }

.stat-card {
  background: white; border-radius: 16px; padding: 1.4rem 1.5rem;
  display: flex; align-items: center; gap: 1.1rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05);
  border: 1px solid #e2e8f0; position: relative; overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px -4px rgba(0,0,0,0.08); border-color: #e2e8f0; }

.stat-icon-wrap { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.stat-info      { display: flex; flex-direction: column; min-width: 0; }
.stat-label     { font-size: 0.75rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; }
.stat-number    { font-size: 2rem; font-weight: 800; color: #0f172a; line-height: 1.1; }
.stat-number.small { font-size: 1.55rem; }
.stat-trend     { font-size: 0.78rem; color: #64748b; margin-top: 0.2rem; }
.trend-up   { color: #10b981; font-weight: 700; }
.trend-down { color: #ef4444; font-weight: 700; }
.trend-warn { color: #f59e0b; font-weight: 700; }

/* ── Quick Actions ────────────────────────────────────── */
.section-title { font-size: 1.05rem; font-weight: 700; color: #334155; margin: 0 0 1rem; }
.quick-actions-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(150px,1fr)); gap: 1rem; }
.action-card {
  background: white; border: 1px solid #e2e8f0; border-radius: 12px;
  padding: 1.4rem 1rem; text-align: center; cursor: pointer; transition: all 0.2s;
  display: flex; flex-direction: column; align-items: center; gap: 0.75rem;
}
.action-card:hover { border-color: #3b82f6; background: #fafafa; transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.07); }
.ql-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; transition: transform 0.2s; }
.action-card:hover .ql-icon { transform: scale(1.08); }
.action-card span { font-weight: 600; color: #475569; font-size: 0.875rem; }

/* ── Columns ──────────────────────────────────────────── */
.dashboard-columns { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; align-items: start; }
.column-left { display: flex; flex-direction: column; gap: 1.5rem; }

/* ── Content Cards ────────────────────────────────────── */
.content-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05); overflow: hidden; }
.card-header  { padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #fcfcfc; }
.card-header h3 { margin: 0; font-size: 1rem; font-weight: 700; color: #334155; }
.count-badge { background: #fef3c7; color: #92400e; padding: 0.2rem 0.65rem; border-radius: 20px; font-size: 0.72rem; font-weight: 700; }
.btn-link { background: none; border: none; color: #3b82f6; font-size: 0.82rem; font-weight: 600; cursor: pointer; transition: color 0.2s; }
.btn-link:hover { color: #2563eb; }

.activity-list { padding: 1rem 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
.activity-item { display: flex; gap: 1rem; align-items: flex-start; }
.act-dot   { width: 10px; height: 10px; border-radius: 50%; margin-top: 5px; flex-shrink: 0; }
.act-details { display: flex; flex-direction: column; gap: 0.1rem; }
.act-text  { margin: 0; font-size: 0.875rem; color: #1e293b; line-height: 1.4; }
.act-time  { font-size: 0.75rem; color: #94a3b8; }

.dept-list    { padding: 1rem 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
.dept-row     { display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
.dept-info    { display: flex; flex-direction: column; min-width: 0; }
.dept-name    { font-weight: 600; font-size: 0.9rem; color: #1e293b; }
.dept-count   { font-size: 0.75rem; color: #64748b; }
.dept-status  { text-align: right; min-width: 120px; }
.progress-bar { background: #e2e8f0; height: 5px; border-radius: 3px; overflow: hidden; margin-bottom: 0.25rem; }
.progress-fill{ background: linear-gradient(90deg,#3b82f6,#93c5fd); height: 100%; border-radius: 3px; transition: width 0.5s ease; }
.dept-pct     { font-size: 0.72rem; color: #3b82f6; font-weight: 700; }

.approvals-list { padding: 1rem 1.5rem; display: flex; flex-direction: column; gap: 0.75rem; }
.approval-card  { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1rem; transition: border-color 0.2s; }
.approval-card:hover { border-color: #cbd5e1; }
.app-meta-row { display: flex; justify-content: space-between; font-size: 0.72rem; color: #64748b; margin-bottom: 0.25rem; }
.app-type { font-weight: 700; color: #1d4ed8; background: #dbeafe; padding: 1px 6px; border-radius: 4px; }
.app-date { color: #94a3b8; }
.app-user { margin: 0 0 0.75rem; font-weight: 700; color: #0f172a; font-size: 0.9rem; }
.app-actions { display: flex; gap: 0.5rem; }

.btn-approve-sm, .btn-reject-sm {
  flex: 1; padding: 0.4rem 0.6rem; border-radius: 7px; border: none;
  cursor: pointer; font-size: 0.78rem; font-weight: 700;
  display: flex; align-items: center; justify-content: center; gap: 0.3rem; transition: all 0.15s;
}
.btn-approve-sm { background: #dcfce7; color: #166534; }
.btn-approve-sm:hover { background: #bbf7d0; }
.btn-reject-sm  { background: #fee2e2; color: #991b1b; }
.btn-reject-sm:hover { background: #fecaca; }

.empty-approvals { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 3rem 1rem; color: #94a3b8; gap: 0.5rem; }
.empty-check { width: 56px; height: 56px; border-radius: 50%; background: #ecfdf5; display: flex; align-items: center; justify-content: center; margin-bottom: 0.25rem; }
.empty-approvals p { margin: 0; font-size: 0.9rem; font-weight: 500; }
.empty-placeholder { color: #94a3b8; font-style: italic; font-size: 0.875rem; text-align: center; padding: 1.5rem 0; }

/* ── Utilities ────────────────────────────────────────── */
.btn-text { background: none; border: none; color: #3b82f6; font-weight: 600; cursor: pointer; margin-left: 0.75rem; }

.state-banner { padding: 1.5rem; border-radius: 12px; text-align: center; margin-bottom: 1.75rem; display: flex; align-items: center; justify-content: center; gap: 0.75rem; }
.state-banner.error   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.state-banner.loading { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }

.spinner { width: 28px; height: 28px; border: 3px solid #bfdbfe; border-top-color: #2563eb; border-radius: 50%; animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Responsive ───────────────────────────────────────── */
@media (max-width: 1024px) { .dashboard-columns { grid-template-columns: 1fr; } }

@media (max-width: 768px) {
  .dashboard-main   { padding: 1rem; }
  .header-inner     { flex-direction: column; align-items: flex-start; }
  .user-greeting    { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .header-controls  { width: 100%; }
  .greeting         { font-size: 1.25rem; }
  .stats-grid       { grid-template-columns: 1fr 1fr; }
  .date-badge       { align-self: flex-start; }
  .biz-trigger      { min-width: 0; width: 100%; max-width: 100%; }
  .biz-dropdown     { width: 100%; }
}
@media (max-width: 480px) {
  .stats-grid         { grid-template-columns: 1fr; }
  .quick-actions-grid { grid-template-columns: 1fr 1fr; }
  .header-controls    { flex-direction: column; }
  .btn-accent         { width: 100%; justify-content: center; }
}
</style>