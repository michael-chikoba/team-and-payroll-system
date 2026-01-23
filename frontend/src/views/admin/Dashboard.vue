<template>
  <div class="admin-dashboard">
    <!-- Header with Filters -->
    <header class="page-header">
      <div class="header-text">
        <h1 class="page-title">Admin Dashboard</h1>
        <p class="page-subtitle">Overview of payroll, attendance, and employee metrics.</p>
      </div>
      
      <div class="header-controls">
        <!-- Business/Country Filter -->
        <div class="filter-wrapper">
          <select v-model="selectedBusinessId" @change="refreshData" class="modern-select">
            <option value="">All Businesses</option>
            <option v-for="b in businesses" :key="b.id" :value="b.id">{{ b.name }}</option>
          </select>
        </div>

        <div class="actions-wrapper">
          <button @click="refreshData" class="btn-icon-only" title="Refresh Data">
            <span class="icon">🔄</span>
          </button>
          <button @click="processPayroll" class="btn-primary">
            <span class="icon">💰</span> Process Payroll
          </button>
        </div>
      </div>
    </header>

    <!-- State Handling -->
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

    <!-- Main Content -->
    <div v-else class="dashboard-content">
      
      <!-- Top Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card blue">
          <div class="stat-icon">👥</div>
          <div class="stat-info">
            <span class="stat-label">Total Employees</span>
            <div class="stat-number">{{ stats.totalEmployees }}</div>
            <div class="stat-trend" v-if="stats.newEmployees > 0">
              <span class="trend-up">↑ {{ stats.newEmployees }}</span> new this month
            </div>
          </div>
        </div>

        <div class="stat-card green">
          <div class="stat-icon">✅</div>
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

        <div class="stat-card orange">
          <div class="stat-icon">🏖️</div>
          <div class="stat-info">
            <span class="stat-label">On Leave</span>
            <div class="stat-number">{{ stats.onLeave }}</div>
            <div class="stat-trend" v-if="stats.pendingLeaves > 0">
              <span class="text-orange">{{ stats.pendingLeaves }}</span> pending requests
            </div>
          </div>
        </div>

        <div class="stat-card purple">
          <div class="stat-icon">💳</div>
          <div class="stat-info">
            <span class="stat-label">Payroll Run ({{ currentMonth }})</span>
            <div class="stat-number small">K{{ formatNumber(stats.monthlyPayroll) }}</div>
            <div class="stat-trend">
              Total processed
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="section-container">
        <h2 class="section-title">Quick Actions</h2>
        <div class="quick-actions-grid">
          <div class="action-card" @click="navigateToName('EmployeeManagement')">
            <div class="action-icon bg-blue">👤</div>
            <span>Add Employee</span>
          </div>
          <div class="action-card" @click="navigateToName('PayslipGeneration')">
            <div class="action-icon bg-green">📄</div>
            <span>Generate Payslips</span>
          </div>
          <div class="action-card" @click="navigateToName('AdminReports')">
            <div class="action-icon bg-orange">📊</div>
            <span>View Reports</span>
          </div>
          <div class="action-card" @click="navigateToName('TaxConfiguration')">
            <div class="action-icon bg-purple">⚙️</div>
            <span>Tax Settings</span>
          </div>
        </div>
      </div>

      <!-- Main Columns -->
      <div class="dashboard-columns">
        
        <!-- Left Column: Activity & Departments -->
        <div class="column-left">
          
          <!-- Recent Activity -->
          <div class="card">
            <div class="card-header">
              <h3>Recent Activity</h3>
              <button @click="navigateToName('AuditLogs')" class="btn-link">View All</button>
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

          <!-- Department Overview -->
          <div class="card mt-4">
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
                    <div class="fill" :style="{ width: dept.attendancePct + '%' }"></div>
                  </div>
                  <span class="dept-pct">{{ dept.attendancePct }}% Present</span>
                </div>
              </div>
              <div v-if="departments.length === 0" class="empty-placeholder">No department data</div>
            </div>
          </div>

        </div>

        <!-- Right Column: Approvals -->
        <div class="column-right">
          <div class="card h-full">
            <div class="card-header">
              <h3>Pending Approvals</h3>
              <span class="badge">{{ pendingApprovals.length }}</span>
            </div>
            <div class="approvals-list">
              <div v-for="app in pendingApprovals" :key="app.id" class="approval-card">
                <div class="app-header-row">
                  <span class="app-type">{{ app.type }}</span>
                  <span class="app-date">{{ app.date }}</span>
                </div>
                <p class="app-user">{{ app.name }}</p>
                <div class="app-actions">
                  <button @click="approve(app.id)" class="btn-tiny approve">Approve</button>
                  <button @click="reject(app.id)" class="btn-tiny reject">Reject</button>
                </div>
              </div>
              <div v-if="pendingApprovals.length === 0" class="empty-placeholder centered">
                <div class="check-icon">✓</div>
                <p>All caught up!</p>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

export default {
  name: 'AdminDashboard',
  
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  
  data() {
    return {
      selectedBusinessId: '',
      businesses: [],
      
      stats: {
        totalEmployees: 0,
        newEmployees: 0,
        activeToday: 0,
        attendanceRate: 0,
        onLeave: 0,
        pendingLeaves: 0,
        monthlyPayroll: 0,
        payrollChange: 0
      },
      recentActivity: [],
      pendingApprovals: [],
      departments: [],
      loading: false,
      error: null,
      retryCount: 0
    }
  },
  
  computed: {
    currentMonth() {
      return new Date().toLocaleString('default', { month: 'long' });
    }
  },

  mounted() {
    this.initializeComponent();
  },
  
  methods: {
    async initializeComponent() {
      if (!this.authStore.isAuthenticated) return;
      if (!this.authStore.isAdmin) return;
      
      await this.fetchBusinesses();
      this.fetchDashboardData();
    },

    async fetchBusinesses() {
      try {
        const res = await axios.get('/api/admin/businesses');
        this.businesses = res.data.data || res.data.businesses || [];
      } catch (e) { console.error('Failed to load businesses', e); }
    },
    
  async fetchDashboardData(retry = false) {
      this.loading = true;
      this.error = null;
      
      try {
        const params = { business_id: this.selectedBusinessId || undefined };
        
        // Parallel Fetch - Use the systemStats endpoint and get today's date
        const today = new Date().toISOString().split('T')[0];
        
        const [statsRes, attRes, leaveRes, auditRes] = await Promise.all([
          axios.get('/api/admin/stats', { params }),
          axios.get('/api/admin/attendance/status', { params: { ...params, date: today } }),
          axios.get('/api/admin/leaves/current-month', { params }),
          axios.get('/api/admin/audit-logs', { params: { ...params, limit: 5 } })
        ]);
        
        // 1. System Stats
        const statsData = statsRes.data.stats || statsRes.data || {};
        this.stats.totalEmployees = statsData.total_employees || 0;
        
        // Calculate new employees this month from employees list
        const now = new Date();
        this.stats.newEmployees = 0; // Will calculate if we get employee details

        // 2. Attendance Stats
        const attData = attRes.data || {};
        const attSummary = attData.summary || {};
        this.stats.activeToday = attSummary.present_count || 0;
        this.stats.attendanceRate = this.stats.totalEmployees > 0 
          ? Math.round((this.stats.activeToday / this.stats.totalEmployees) * 100) 
          : 0;

        // 3. Leave Stats
        const leaveData = leaveRes.data || {};
        this.stats.onLeave = leaveData.on_leave_count || 0;
        this.stats.pendingLeaves = leaveData.pending_count || 0;
        
        // Approvals List
        if (leaveData.pending_leaves && Array.isArray(leaveData.pending_leaves)) {
          this.pendingApprovals = leaveData.pending_leaves.slice(0, 5).map(l => ({
            id: l.id,
            name: l.employee?.first_name && l.employee?.last_name 
              ? `${l.employee.first_name} ${l.employee.last_name}`
              : l.employee?.name || `Employee #${l.employee_id}`,
            type: this.formatLeaveType(l.leave_type),
            date: this.formatDateRange(l.start_date, l.end_date)
          }));
        } else {
          this.pendingApprovals = [];
        }

        // 4. Payroll Stats from system stats (amount in local currency)
        this.stats.monthlyPayroll = statsData.current_month_payroll_amount || 0;

        // 5. Activity Log
        const auditData = auditRes.data.data || auditRes.data || [];
        this.recentActivity = auditData.slice(0, 5).map(log => ({
          id: log.id,
          text: log.description || log.action || 'Activity',
          time: this.formatRelativeTime(log.created_at),
          color: this.getActivityColor(log.action)
        }));

        // 6. Department Breakdown from attendance data
        const attDataList = attData.data || [];
        const deptMap = {};
        
        attDataList.forEach(att => {
          const d = att.department || 'Unassigned';
          if (!deptMap[d]) {
            deptMap[d] = { 
              id: d,
              name: d, 
              employees: 0, 
              present: 0 
            };
          }
          deptMap[d].employees++;
          if (att.status === 'present' || att.status === 'completed') {
            deptMap[d].present++;
          }
        });
        
        this.departments = Object.values(deptMap).map(d => ({
          ...d,
          attendancePct: d.employees > 0 
            ? Math.round((d.present / d.employees) * 100) 
            : 0
        }));

      } catch (err) {
        console.error('Dashboard fetch error:', err);
        this.handleApiError(err);
      } finally {
        this.loading = false;
      }
    },
    // --- Helpers ---
    formatLeaveType(type) {
      return (type || 'Leave').replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase());
    },
    
    formatDateRange(start, end) {
      const s = new Date(start);
      const e = new Date(end);
      return `${s.toLocaleDateString('en-US', {month:'short', day:'numeric'})} - ${e.toLocaleDateString('en-US', {month:'short', day:'numeric'})}`;
    },
    
    getActivityColor(action) {
      if (!action) return '#94a3b8'; // gray
      if (action.includes('delete') || action.includes('reject')) return '#ef4444'; // red
      if (action.includes('create') || action.includes('add') || action.includes('approve')) return '#10b981'; // green
      if (action.includes('update') || action.includes('edit')) return '#3b82f6'; // blue
      return '#8b5cf6'; // purple default
    },

    formatRelativeTime(ts) {
      const diff = new Date() - new Date(ts);
      const mins = Math.floor(diff / 60000);
      if (mins < 60) return `${mins}m ago`;
      const hours = Math.floor(mins / 60);
      if (hours < 24) return `${hours}h ago`;
      return `${Math.floor(hours / 24)}d ago`;
    },

    formatNumber(num) {
      return new Intl.NumberFormat('en-US').format(num || 0);
    },

    retryFetch() {
      this.retryCount++;
      if (this.retryCount <= 3) this.fetchDashboardData(true);
      else this.error = "Failed to load data after retries.";
    },

    refreshData() {
      this.retryCount = 0;
      this.fetchDashboardData();
    },

    // --- Actions ---
    processPayroll() { this.navigateToName('PayrollProcessing'); },
    navigateToName(name) { this.$router.push({ name: name }); },

    async approve(id) {
      if(!confirm('Approve this request?')) return;
      try {
        await axios.post(`/api/manager/leaves/${id}/approve`);
        this.pendingApprovals = this.pendingApprovals.filter(a => a.id !== id);
        this.stats.pendingLeaves--;
      } catch (e) { alert('Action failed'); }
    },

    async reject(id) {
      if(!confirm('Reject this request?')) return;
      try {
        await axios.post(`/api/manager/leaves/${id}/reject`);
        this.pendingApprovals = this.pendingApprovals.filter(a => a.id !== id);
        this.stats.pendingLeaves--;
      } catch (e) { alert('Action failed'); }
    },

    handleApiError(err) {
      if (err.response?.status === 401) {
        this.authStore.clearAuth();
        this.$router.push({ name: 'login' });
      }
      this.error = err.response?.data?.message || 'Failed to load dashboard data.';
    }
  }
}
</script>

<style scoped>
/* --- Layout & Reset --- */
.admin-dashboard { max-width: 1400px; margin: 0 auto; padding: 2rem; font-family: 'Inter', sans-serif; color: #1e293b; background-color: #f8fafc; min-height: 100vh; }
* { box-sizing: border-box; }

/* --- Header --- */
.page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
.page-title { font-size: 1.8rem; font-weight: 700; margin: 0; color: #0f172a; }
.page-subtitle { color: #64748b; margin: 0.25rem 0 0; }
.header-controls { display: flex; gap: 1rem; align-items: center; }
.modern-select { padding: 0.6rem 1rem; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem; background: white; cursor: pointer; min-width: 200px; }
.actions-wrapper { display: flex; gap: 0.5rem; }

/* --- Buttons --- */
.btn-primary { background: #4f46e5; color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: 0.2s; }
.btn-primary:hover { background: #4338ca; transform: translateY(-1px); }
.btn-icon-only { background: white; border: 1px solid #e2e8f0; width: 40px; height: 40px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; transition: 0.2s; }
.btn-icon-only:hover { background: #f1f5f9; border-color: #cbd5e1; }
.btn-text { background: none; border: none; color: #4f46e5; font-weight: 600; cursor: pointer; }
.btn-link { background: none; border: none; color: #64748b; font-size: 0.85rem; cursor: pointer; }
.btn-link:hover { color: #4f46e5; }

/* --- State Banners --- */
.state-banner { padding: 1.5rem; border-radius: 12px; text-align: center; margin-bottom: 2rem; }
.state-banner.error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.state-banner.loading { background: #f0f9ff; color: #0369a1; }
.spinner { width: 30px; height: 30px; border: 3px solid #bae6fd; border-top: 3px solid #0284c7; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 0.5rem; }
@keyframes spin { to { transform: rotate(360deg); } }

/* --- Stats Grid --- */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
.stat-card { background: white; border-radius: 16px; padding: 1.5rem; display: flex; align-items: center; gap: 1rem; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: transform 0.2s; border-left: 4px solid transparent; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
.stat-card.blue { border-left-color: #3b82f6; } .stat-card.blue .stat-icon { background: #eff6ff; color: #3b82f6; }
.stat-card.green { border-left-color: #10b981; } .stat-card.green .stat-icon { background: #ecfdf5; color: #10b981; }
.stat-card.orange { border-left-color: #f59e0b; } .stat-card.orange .stat-icon { background: #fffbeb; color: #f59e0b; }
.stat-card.purple { border-left-color: #8b5cf6; } .stat-card.purple .stat-icon { background: #f5f3ff; color: #8b5cf6; }

.stat-icon { width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }
.stat-info { display: flex; flex-direction: column; }
.stat-label { font-size: 0.85rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
.stat-number { font-size: 2rem; font-weight: 800; color: #0f172a; line-height: 1.2; }
.stat-number.small { font-size: 1.6rem; }
.stat-trend { font-size: 0.85rem; color: #64748b; margin-top: 0.25rem; }
.trend-up { color: #10b981; font-weight: 600; }
.trend-down { color: #ef4444; font-weight: 600; }
.text-orange { color: #f59e0b; font-weight: 600; }

/* --- Quick Actions --- */
.section-container { margin-bottom: 2rem; }
.section-title { font-size: 1.2rem; font-weight: 700; color: #334155; margin-bottom: 1rem; }
.quick-actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; }
.action-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; padding: 1.25rem; text-align: center; cursor: pointer; transition: 0.2s; display: flex; flex-direction: column; align-items: center; gap: 0.75rem; }
.action-card:hover { border-color: #4f46e5; background: #f8fafc; }
.action-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; color: white; }
.bg-blue { background: #3b82f6; } .bg-green { background: #10b981; } .bg-orange { background: #f59e0b; } .bg-purple { background: #8b5cf6; }
.action-card span { font-weight: 600; color: #475569; font-size: 0.9rem; }

/* --- Columns Layout --- */
.dashboard-columns { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; }
.card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; height: 100%; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
.card-header { padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #fcfcfc; }
.card-header h3 { margin: 0; font-size: 1rem; font-weight: 700; color: #334155; }
.badge { background: #fef3c7; color: #92400e; padding: 0.2rem 0.6rem; border-radius: 99px; font-size: 0.75rem; font-weight: 700; }

/* Activity List */
.activity-list { padding: 1rem; }
.activity-item { display: flex; gap: 1rem; margin-bottom: 1rem; align-items: flex-start; }
.activity-item:last-child { margin-bottom: 0; }
.act-dot { width: 10px; height: 10px; border-radius: 50%; margin-top: 6px; flex-shrink: 0; }
.act-text { margin: 0; font-size: 0.9rem; color: #1e293b; }
.act-time { font-size: 0.75rem; color: #94a3b8; }

/* Departments */
.dept-list { padding: 1rem; }
.dept-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.dept-info { display: flex; flex-direction: column; }
.dept-name { font-weight: 600; font-size: 0.95rem; }
.dept-count { font-size: 0.8rem; color: #64748b; }
.dept-status { text-align: right; width: 120px; }
.progress-bar { background: #e2e8f0; height: 6px; border-radius: 3px; overflow: hidden; margin-bottom: 0.25rem; }
.fill { background: #10b981; height: 100%; }
.dept-pct { font-size: 0.75rem; color: #10b981; font-weight: 600; }

/* Approvals */
.approvals-list { padding: 1rem; display: flex; flex-direction: column; gap: 0.75rem; }
.approval-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; }
.app-header-row { display: flex; justify-content: space-between; font-size: 0.75rem; color: #64748b; margin-bottom: 0.25rem; }
.app-user { margin: 0 0 0.75rem 0; font-weight: 600; color: #0f172a; }
.app-actions { display: flex; gap: 0.5rem; }
.btn-tiny { flex: 1; padding: 0.4rem; border-radius: 6px; border: none; cursor: pointer; font-size: 0.8rem; font-weight: 600; }
.approve { background: #dcfce7; color: #166534; } .approve:hover { background: #bbf7d0; }
.reject { background: #fee2e2; color: #991b1b; } .reject:hover { background: #fecaca; }

.empty-placeholder { color: #94a3b8; font-style: italic; text-align: center; padding: 1rem; font-size: 0.9rem; }
.centered { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 200px; }
.check-icon { font-size: 3rem; color: #e2e8f0; margin-bottom: 0.5rem; }

/* Responsive */
@media (max-width: 1024px) {
  .dashboard-columns { grid-template-columns: 1fr; }
}
@media (max-width: 640px) {
  .page-header { flex-direction: column; align-items: flex-start; }
  .header-controls { width: 100%; flex-direction: column; align-items: stretch; }
  .stats-grid { grid-template-columns: 1fr; }
  .quick-actions-grid { grid-template-columns: 1fr 1fr; }
}
</style>