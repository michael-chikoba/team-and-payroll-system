<template>
  <div class="attendance-page">
    
    <!-- History Modal -->
    <transition name="fade">
      <div v-if="history.visible" class="modal-backdrop" @click.self="closeHistory">
        <div class="modal-content wide">
          <div class="modal-header">
            <h3>Attendance History: {{ history.employeeName }}</h3>
            <button @click="closeHistory" class="btn-close">&times;</button>
          </div>
          <div class="modal-body">
            <!-- Summary Stats -->
            <div class="stats-row">
              <div class="stat-card">
                <span class="label">Total Days</span>
                <span class="value">{{ history.summary.total_records || 0 }}</span>
              </div>
              <div class="stat-card success">
                <span class="label">Present</span>
                <span class="value">{{ history.summary.present_days || 0 }}</span>
              </div>
              <div class="stat-card danger">
                <span class="label">Absent</span>
                <span class="value">{{ history.summary.absent_days || 0 }}</span>
              </div>
              <div class="stat-card warning">
                <span class="label">Late</span>
                <span class="value">{{ history.summary.late_days || 0 }}</span>
              </div>
              <div class="stat-card info">
                <span class="label">Overtime Hrs</span>
                <span class="value">{{ (history.summary.total_overtime_hours || 0).toFixed(1) }}</span>
              </div>
            </div>

            <!-- History Table -->
            <div class="table-container">
              <table v-if="history.records.length > 0" class="clean-table">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Clock In</th>
                    <th>Clock Out</th>
                    <th>Regular</th>
                    <th>Overtime</th>
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(record, index) in history.records" :key="index">
                    <td>{{ formatDate(record.date) }}</td>
                    <td>
                      <span class="badge" :class="getStatusClass(record.status)">
                        {{ formatStatus(record.status) }}
                      </span>
                    </td>
                    <td>{{ formatTime(record.clock_in) }}</td>
                    <td>{{ formatTime(record.clock_out) }}</td>
                    <td>{{ formatHours(record.regular_hours || 0) }}</td>
                    <td class="overtime-cell">{{ formatHours(record.overtime_hours || 0) }}</td>
                    <td><strong>{{ formatHours(record.total_hours) }}</strong></td>
                  </tr>
                </tbody>
              </table>
              <div v-else class="empty-state small">No history records found.</div>
            </div>
          </div>
          <div class="modal-footer">
            <button @click="closeHistory" class="btn btn-primary">Close</button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Main Content -->
    <div class="page-header">
      <div class="header-text">
        <h1>Team Monitor</h1>
        <p>Attendance for {{ formatDate(selectedDate) }}</p>
      </div>
      <div class="header-controls">
        <input 
          type="date" 
          v-model="selectedDate" 
          :max="today" 
          @change="fetchData" 
          class="date-input"
        />
        <button @click="fetchData" class="btn btn-primary" :disabled="loading">
          {{ loading ? 'Loading...' : 'Refresh Data' }}
        </button>
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="alert error">
      {{ error }}
      <button @click="retryFetch" class="btn-link">Try Again</button>
    </div>

    <!-- Loading State -->
    <div v-else-if="loading && !attendanceData.length" class="loading-state">
      <div class="spinner"></div>
      <p>Syncing attendance records...</p>
    </div>

    <!-- Data View -->
    <div v-else>
      <!-- Overview Cards -->
      <div v-if="teamOverview" class="overview-grid">
        <div class="card metric">
          <span class="metric-val">{{ teamOverview.totalEmployees }}</span>
          <span class="metric-label">Total Staff</span>
        </div>
        <div class="card metric success">
          <span class="metric-val">{{ teamOverview.presentCount }}</span>
          <span class="metric-label">Present</span>
        </div>
        <div class="card metric danger">
          <span class="metric-val">{{ teamOverview.absentCount }}</span>
          <span class="metric-label">Absent</span>
        </div>
        <div class="card metric warning">
          <span class="metric-val">{{ teamOverview.lateCount }}</span>
          <span class="metric-label">Late</span>
        </div>
        <div class="card metric info">
          <span class="metric-val">{{ teamOverview.totalOvertimeHours }}</span>
          <span class="metric-label">Overtime Hrs</span>
        </div>
      </div>

      <!-- Employee List -->
      <div class="section-header">
        <h2>Team Members ({{ filteredData.length }})</h2>
        <div class="tabs">
          <button v-for="tab in tabs" :key="tab.key" 
            @click="activeFilter = tab.key"
            :class="['tab', { active: activeFilter === tab.key }]">
            {{ tab.label }}
          </button>
        </div>
      </div>

      <div class="employee-grid">
        <div v-for="emp in filteredData" :key="emp.id" class="employee-card">
          <div class="card-top">
            <div class="avatar">{{ getInitials(emp.full_name) }}</div>
            <div class="info">
              <h3>{{ emp.full_name }}</h3>
              <p>{{ emp.position }}</p>
              <p v-if="emp.has_shift" class="shift-info">
                🕐 Shift: {{ emp.shift.start_time }} - {{ emp.shift.end_time }}
              </p>
            </div>
            <span class="badge" :class="getStatusClass(emp.status)">
              {{ formatStatus(emp.status) }}
            </span>
          </div>

          <div class="card-details">
            <div class="detail">
              <span>In</span>
              <strong>{{ formatTime(emp.clock_in_time) }}</strong>
            </div>
            <div class="detail">
              <span>Out</span>
              <strong>{{ formatTime(emp.clock_out_time) }}</strong>
            </div>
            <div class="detail">
              <span>Total</span>
              <strong>{{ formatHours(emp.hours_worked) }}</strong>
            </div>
          </div>

          <!-- Overtime Info -->
          <div v-if="emp.overtime_hours > 0" class="overtime-info">
            <div class="overtime-badge">
              ⚡ Overtime
            </div>
            <div class="overtime-details">
              <div class="overtime-item">
                <span>Regular:</span>
                <strong>{{ formatHours(emp.regular_hours) }}</strong>
              </div>
              <div class="overtime-item">
                <span>Overtime:</span>
                <strong class="overtime-value">{{ formatHours(emp.overtime_hours) }}</strong>
              </div>
            </div>
          </div>

          <!-- Late Info -->
          <div v-if="emp.is_late && emp.minutes_late > 0" class="late-info">
            ⚠️ Late by {{ emp.minutes_late }} minutes
          </div>

          <div class="card-actions">
            <button @click="viewHistory(emp)" class="btn btn-secondary small">History</button>
          </div>
        </div>

        <div v-if="filteredData.length === 0" class="empty-state">
          No employees found for this filter.
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

export default {
  name: 'ManagerAttendanceMonitor',
  
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },

  data() {
    return {
      attendanceData: [],
      teamOverview: null,
      loading: false,
      error: null,
      retryCount: 0,
      selectedDate: new Date().toISOString().split('T')[0],
      today: new Date().toISOString().split('T')[0],
      activeFilter: 'all',
      tabs: [
        { key: 'all', label: 'All' },
        { key: 'present', label: 'Present' },
        { key: 'absent', label: 'Absent' },
        { key: 'late', label: 'Late' }
      ],
      history: {
        visible: false,
        loading: false,
        employeeName: '',
        records: [],
        summary: {}
      }
    }
  },

  computed: {
    isToday() {
      return this.selectedDate === this.today
    },

    filteredData() {
      if (this.activeFilter === 'all') return this.attendanceData
      
      return this.attendanceData.filter(emp => {
        const status = emp.status
        if (this.activeFilter === 'present') return ['present', 'completed'].includes(status)
        if (this.activeFilter === 'absent') return ['absent', 'on_leave'].includes(status)
        return status === this.activeFilter
      })
    }
  },

  mounted() {
    this.fetchData()
  },

  methods: {
    async fetchData() {
      this.loading = true
      this.error = null
      
      try {
        const response = await axios.get('/api/manager/attendance/team-status', {
          params: { date: this.selectedDate }
        })

        if (response.data.success) {
          this.processData(response.data)
        } else {
          throw new Error(response.data.message || 'Failed to fetch data')
        }
        
        this.retryCount = 0
      } catch (err) {
        this.handleError(err)
      } finally {
        this.loading = false
      }
    },

    processData(data) {
      const attendances = data.attendances || []
      const summary = data.summary || {}
      
      this.attendanceData = attendances.map(att => ({
        id: att.id || att.employee_id,
        employee_id: att.employee_id,
        full_name: att.full_name || att.employee_name || 'Unknown',
        position: att.position || 'Staff',
        department: att.department || 'General',
        status: att.status,
        clock_in_time: att.clock_in,
        clock_out_time: att.clock_out,
        hours_worked: att.total_hours || 0,
        regular_hours: att.regular_hours || 0,
        overtime_hours: att.overtime_hours || 0,
        has_shift: att.has_shift || false,
        shift: att.shift || null,
        is_late: att.is_late || false,
        minutes_late: att.minutes_late || 0,
        date: att.date
      }))

      // Calculate overview with overtime
      const totalOvertime = this.attendanceData.reduce((sum, emp) => 
        sum + (emp.overtime_hours || 0), 0
      )

      this.teamOverview = {
        totalEmployees: summary.total_employees || attendances.length,
        presentCount: summary.present || 0,
        absentCount: summary.absent || 0,
        lateCount: summary.late || 0,
        totalOvertimeHours: totalOvertime.toFixed(1)
      }
    },

    async viewHistory(employee) {
      this.history.visible = true
      this.history.loading = true
      this.history.employeeName = employee.full_name
      
      try {
        const date = new Date(this.selectedDate)
        const res = await axios.get(`/api/manager/attendance/${employee.employee_id}/history`, {
          params: {
            month: date.getMonth() + 1,
            year: date.getFullYear()
          }
        })
        
        this.history.records = res.data.data || []
        
        // Calculate summary with overtime
        const records = this.history.records
        const totalOvertimeHours = records.reduce((sum, r) => 
          sum + (r.overtime_hours || 0), 0
        )
        
        this.history.summary = {
          ...res.data.summary,
          total_overtime_hours: totalOvertimeHours
        }
      } catch (e) {
        this.error = 'Could not load history.'
        this.closeHistory()
      } finally {
        this.history.loading = false
      }
    },

    closeHistory() {
      this.history.visible = false
      this.history.records = []
      this.history.summary = {}
    },

    retryFetch() {
      if (this.retryCount < 3) {
        this.retryCount++
        this.fetchData()
      } else {
        this.error = 'Unable to connect to server. Please refresh the page.'
      }
    },

    handleError(err) {
      console.error(err)
      this.error = err.response?.data?.message || 'Failed to load team attendance.'
    },

    getInitials(name) {
      return name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '??'
    },
    
    formatDate(d) {
      if (!d) return '-'
      return new Date(d).toLocaleDateString('en-US', { 
        weekday: 'short', 
        month: 'short', 
        day: 'numeric' 
      })
    },
    
    formatTime(t) {
      if (!t) return '--:--'
      if (t.includes('M')) return t
      try {
        const date = t.includes('T') ? new Date(t) : new Date(`2000-01-01T${t}`)
        return isNaN(date) ? t : date.toLocaleTimeString('en-US', { 
          hour: 'numeric', 
          minute: '2-digit' 
        })
      } catch (e) { 
        return t 
      }
    },
    
    formatHours(h) {
      const num = parseFloat(h) || 0
      const hrs = Math.floor(num)
      const mins = Math.round((num - hrs) * 60)
      return `${hrs}h ${mins}m`
    },
    
    formatStatus(s) {
      const map = { 
        present: 'Present', 
        completed: 'Completed', 
        absent: 'Absent', 
        late: 'Late', 
        on_leave: 'On Leave' 
      }
      return map[s] || s
    },
    
    getStatusClass(s) {
      if (['present', 'completed'].includes(s)) return 'badge-success'
      if (['absent', 'on_leave'].includes(s)) return 'badge-danger'
      if (s === 'late') return 'badge-warning'
      return 'badge-neutral'
    }
  }
}
</script>

<style scoped>
:root {
  --primary: #4f46e5;
  --secondary: #64748b;
  --success: #10b981;
  --danger: #ef4444;
  --warning: #f59e0b;
  --info: #06b6d4;
  --bg-page: #f8fafc;
  --bg-card: #ffffff;
  --text-main: #1e293b;
  --text-light: #64748b;
  --border: #e2e8f0;
  --radius: 12px;
}

.attendance-page {
  padding: 2rem;
  background: var(--bg-page);
  min-height: 100vh;
  font-family: 'Inter', system-ui, sans-serif;
  color: var(--text-main);
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.page-header h1 {
  margin: 0;
  font-size: 1.8rem;
  font-weight: 700;
}

.header-controls {
  display: flex;
  gap: 1rem;
}

.date-input {
  padding: 0.6rem;
  border: 1px solid var(--border);
  border-radius: 8px;
}

.overview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.card {
  background: var(--bg-card);
  padding: 1.5rem;
  border-radius: var(--radius);
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
  border: 1px solid var(--border);
}

.metric {
  text-align: center;
}

.metric-val {
  display: block;
  font-size: 2rem;
  font-weight: 800;
}

.metric.success { color: var(--success); }
.metric.danger { color: var(--danger); }
.metric.warning { color: var(--warning); }
.metric.info { color: var(--info); }

.metric-label {
  font-size: 0.85rem;
  color: var(--text-light);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}

.tabs {
  background: #e2e8f0;
  padding: 0.25rem;
  border-radius: 8px;
  display: flex;
}

.tab {
  background: none;
  border: none;
  padding: 0.5rem 1rem;
  cursor: pointer;
  border-radius: 6px;
  color: var(--text-light);
  font-weight: 600;
}

.tab.active {
  background: white;
  color: var(--primary);
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.employee-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
}

.employee-card {
  background: white;
  border-radius: var(--radius);
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
  overflow: hidden;
  transition: transform 0.2s;
}

.employee-card:hover {
  transform: translateY(-4px);
}

.card-top {
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  background: #f8fafc;
  border-bottom: 1px solid var(--border);
}

.avatar {
  width: 45px;
  height: 45px;
  background: linear-gradient(135deg, #6366f1, #a855f7);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  flex-shrink: 0;
}

.info {
  flex: 1;
  min-width: 0;
}

.info h3 {
  margin: 0;
  font-size: 1.1rem;
}

.info p {
  margin: 0;
  font-size: 0.85rem;
  color: var(--text-light);
}

.shift-info {
  color: var(--info);
  font-weight: 500;
  margin-top: 0.25rem;
}

.badge {
  margin-left: auto;
  padding: 0.25rem 0.75rem;
  border-radius: 99px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  flex-shrink: 0;
}

.badge-success { background: #d1fae5; color: #065f46; }
.badge-danger { background: #fee2e2; color: #991b1b; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-neutral { background: #f3f4f6; color: #4b5563; }

.card-details {
  padding: 1.25rem;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.5rem;
}

.detail {
  display: flex;
  flex-direction: column;
  text-align: center;
}

.detail span {
  font-size: 0.75rem;
  color: var(--text-light);
  text-transform: uppercase;
}

.overtime-info {
  padding: 1rem 1.25rem;
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  border-top: 1px solid #fbbf24;
  border-bottom: 1px solid #fbbf24;
}

.overtime-badge {
  font-weight: 700;
  color: #92400e;
  font-size: 0.85rem;
  margin-bottom: 0.5rem;
}

.overtime-details {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
}

.overtime-item {
  display: flex;
  flex-direction: column;
  text-align: center;
}

.overtime-item span {
  font-size: 0.75rem;
  color: #92400e;
  text-transform: uppercase;
}

.overtime-value {
  color: #b45309;
}

.late-info {
  padding: 0.75rem 1.25rem;
  background: #fff7ed;
  color: #c2410c;
  font-size: 0.85rem;
  font-weight: 600;
  border-top: 1px solid #fed7aa;
  text-align: center;
}

.card-actions {
  padding: 1rem;
  display: flex;
  gap: 0.5rem;
  border-top: 1px solid var(--border);
}

.btn {
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary { 
  background: var(--primary); 
  color: white; 
}

.btn-primary:hover { 
  opacity: 0.9; 
}

.btn-secondary { 
  background: white; 
  border: 1px solid var(--border); 
  color: var(--text-main); 
}

.small { 
  flex: 1; 
  padding: 0.4rem; 
  font-size: 0.9rem; 
}

/* Modal Styles */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
  backdrop-filter: blur(2px);
}

.modal-content {
  background: white;
  width: 90%;
  max-width: 400px;
  border-radius: 12px;
  box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
  overflow: hidden;
}

.modal-content.wide { 
  max-width: 750px; 
}

.modal-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-body { 
  padding: 1.5rem; 
  max-height: 70vh;
  overflow-y: auto;
}

.modal-footer { 
  padding: 1rem 1.5rem; 
  background: #f8fafc; 
  text-align: right; 
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: var(--text-light);
}

.stats-row { 
  display: grid; 
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); 
  gap: 0.75rem; 
  margin-bottom: 1.5rem; 
}

.stat-card { 
  padding: 0.75rem; 
  border-radius: 8px; 
  text-align: center; 
  border: 1px solid var(--border); 
  background: #f8fafc; 
}

.stat-card .label {
  display: block;
  font-size: 0.75rem;
  color: var(--text-light);
  margin-bottom: 0.25rem;
  text-transform: uppercase;
}

.stat-card .value {
  display: block;
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-main);
}

.stat-card.success { 
  background: #ecfdf5; 
  border-color: #a7f3d0; 
}

.stat-card.success .value { 
  color: var(--success); 
}

.stat-card.danger { 
  background: #fef2f2; 
  border-color: #fecaca; 
}

.stat-card.danger .value { 
  color: var(--danger); 
}

.stat-card.warning { 
  background: #fef3c7; 
  border-color: #fde68a; 
}

.stat-card.warning .value { 
  color: var(--warning); 
}

.stat-card.info { 
  background: #f0f9ff; 
  border-color: #bae6fd; 
}

.stat-card.info .value { 
  color: var(--info); 
}

.clean-table { 
  width: 100%; 
  border-collapse: collapse; 
}

.clean-table th { 
  text-align: left; 
  padding: 0.75rem; 
  color: var(--text-light); 
  border-bottom: 1px solid var(--border);
  background: #f8fafc;
  font-size: 0.85rem;
  text-transform: uppercase;
}

.clean-table td { 
  padding: 0.75rem; 
  border-bottom: 1px solid #f1f5f9; 
}

.overtime-cell {
  color: var(--warning);
  font-weight: 600;
}

.alert.error { 
  background: #fef2f2; 
  color: #991b1b; 
  border: 1px solid #fecaca; 
  padding: 1rem; 
  border-radius: 8px; 
  margin-bottom: 1rem; 
}

.loading-state, .empty-state { 
  text-align: center; 
  padding: 3rem; 
  color: var(--text-light); 
}

.spinner { 
  width: 30px; 
  height: 30px; 
  border: 3px solid #e2e8f0; 
  border-top-color: var(--primary); 
  border-radius: 50%; 
  animation: spin 1s linear infinite; 
  margin: 0 auto 1rem; 
}

@keyframes spin { 
  to { transform: rotate(360deg); } 
}

.fade-enter-active, .fade-leave-active { 
  transition: opacity 0.2s; 
}

.fade-enter-from, .fade-leave-to { 
  opacity: 0; 
}

@media (max-width: 768px) {
  .page-header { 
    flex-direction: column; 
    text-align: center; 
  }
  
  .header-controls { 
    width: 100%; 
    justify-content: center; 
  }
  
  .employee-grid { 
    grid-template-columns: 1fr; 
  }
  
  .stats-row { 
    grid-template-columns: repeat(2, 1fr); 
  }
  
  .overtime-details {
    grid-template-columns: 1fr;
  }
}
</style>