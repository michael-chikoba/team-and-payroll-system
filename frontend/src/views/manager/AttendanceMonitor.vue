<template>
  <div class="attendance-page">
    
    <!-- Unified Modal System -->
    <transition name="fade">
      <div v-if="modal.visible" class="modal-backdrop" @click.self="closeModal">
        <div class="modal-content">
          <div class="modal-header">
            <h3>{{ modal.title }}</h3>
            <button @click="closeModal" class="btn-close">&times;</button>
          </div>
          <div class="modal-body">
            <p>{{ modal.message }}</p>
          </div>
          <div class="modal-footer">
            <button v-if="modal.type === 'confirm'" @click="handleModalCancel" class="btn btn-secondary">Cancel</button>
            <button @click="handleModalConfirm" class="btn btn-primary">{{ modal.confirmText || 'OK' }}</button>
          </div>
        </div>
      </div>
    </transition>

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
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(record, index) in history.records" :key="index">
                    <td>{{ formatDate(record.date) }}</td>
                    <td>
                      <span class="badge" :class="getStatusClass(record.status)">{{ formatStatus(record.status) }}</span>
                    </td>
                    <td>{{ formatTime(record.clock_in) }}</td>
                    <td>{{ formatTime(record.clock_out) }}</td>
                    <td>{{ formatHours(record.total_hours) }}</td>
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
              <span>Hrs</span>
              <strong>{{ formatHours(emp.hours_worked) }}</strong>
            </div>
          </div>

          <div class="card-actions">
            <button @click="viewHistory(emp)" class="btn btn-secondary small">History</button>
            <button 
              v-if="emp.status === 'absent' && isToday" 
              @click="confirmMarkPresent(emp)" 
              class="btn btn-outline-success small"
            >
              Mark Present
            </button>
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
  name: 'AttendanceMonitor',
  
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },

  data() {
    return {
      // Data
      attendanceData: [],
      employees: [],
      teamOverview: null,
      
      // State
      loading: false,
      error: null,
      retryCount: 0,
      selectedDate: new Date().toISOString().split('T')[0],
      today: new Date().toISOString().split('T')[0],
      
      // Filters
      activeFilter: 'all',
      tabs: [
        { key: 'all', label: 'All' },
        { key: 'present', label: 'Present' },
        { key: 'absent', label: 'Absent' },
        { key: 'late', label: 'Late' }
      ],

      // Modals
      modal: {
        visible: false,
        type: 'alert', // 'alert' | 'confirm'
        title: '',
        message: '',
        confirmText: 'OK',
        resolve: null,
        reject: null
      },
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
    // --- Data Logic ---
    async fetchData() {
      this.loading = true
      this.error = null
      
      try {
        console.log('Fetching Data for:', this.selectedDate)
        
        const [empRes, attRes] = await Promise.all([
          axios.get('/api/manager/employees', { params: { manager_id: this.authStore.user?.id } }),
          // FIX: Updated endpoint to fetch team status instead of personal history
          axios.get('/api/manager/attendance/team-status', { params: { date: this.selectedDate } })
        ])

        this.processData(empRes.data, attRes.data)
        this.retryCount = 0
      } catch (err) {
        this.handleError(err)
      } finally {
        this.loading = false
      }
    },

    processData(employeeData, attendanceData) {
      // 1. Normalize Employees
      const rawEmployees = Array.isArray(employeeData) ? employeeData : (employeeData.data || [])
      this.employees = rawEmployees

      // 2. Normalize Attendance
      // Adapts to various API response structures
      let attendances = []
      if (Array.isArray(attendanceData)) {
        attendances = attendanceData
      } else if (attendanceData.data) {
        attendances = attendanceData.data
      } else if (attendanceData.attendances) {
        attendances = attendanceData.attendances
      }

      // 3. Map Attendance by ID
      const attMap = new Map()
      attendances.forEach(rec => {
        const id = rec.employee?.id || rec.employee_id
        if (id) attMap.set(String(id), rec)
      })

      // 4. Merge
      this.attendanceData = this.employees.map(emp => {
        const record = attMap.get(String(emp.id))
        
        // Name Resolution
        let firstName = emp.first_name || emp.user?.first_name || ''
        let lastName = emp.last_name || emp.user?.last_name || ''
        const fullName = (firstName && lastName) ? `${firstName} ${lastName}` : (emp.name || 'Unknown')

        return {
          id: emp.id,
          full_name: fullName.trim(),
          employee_id: emp.employee_id || emp.id,
          department: emp.department || 'General',
          position: emp.position || 'Staff',
          
          // Data Resolution (Handle casing differences)
          status: record ? record.status : 'absent',
          clock_in_time: record ? (record.checkIn || record.clock_in || record.clock_in_time) : null,
          clock_out_time: record ? (record.checkOut || record.clock_out || record.clock_out_time) : null,
          hours_worked: record ? (record.hoursWorked || record.total_hours || 0) : 0,
          date: this.selectedDate
        }
      })

      this.calculateOverview()
    },

    calculateOverview() {
      const total = this.attendanceData.length
      const present = this.attendanceData.filter(e => ['present', 'completed'].includes(e.status)).length
      const late = this.attendanceData.filter(e => e.status === 'late').length
      const absent = this.attendanceData.filter(e => ['absent', 'on_leave'].includes(e.status)).length
      
      this.teamOverview = {
        totalEmployees: total,
        presentCount: present,
        absentCount: absent,
        lateCount: late,
        attendanceRate: total ? Math.round(((present + late) / total) * 100) : 0
      }
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

    // --- Actions ---
    async confirmMarkPresent(employee) {
      const confirmed = await this.showModal({
        type: 'confirm',
        title: 'Mark Present?',
        message: `Are you sure you want to mark ${employee.full_name} as present for today?`,
        confirmText: 'Yes, Mark Present'
      })

      if (confirmed) {
        try {
          await axios.post(`/api/manager/attendance/${employee.id}/mark-present`, { date: this.selectedDate })
          this.fetchData()
          this.showModal({ title: 'Success', message: 'Employee marked as present.' })
        } catch (e) {
          this.showModal({ title: 'Error', message: 'Failed to update attendance.' })
        }
      }
    },

    async viewHistory(employee) {
      this.history.visible = true
      this.history.loading = true
      this.history.employeeName = employee.full_name
      
      try {
        const res = await axios.get(`/api/manager/attendance/${employee.id}/history`, {
          params: {
            month: new Date(this.selectedDate).getMonth() + 1,
            year: new Date(this.selectedDate).getFullYear()
          }
        })
        this.history.records = res.data.data || []
        this.history.summary = res.data.summary || {}
      } catch (e) {
        this.showModal({ title: 'Error', message: 'Could not load history.' })
        this.closeHistory()
      } finally {
        this.history.loading = false
      }
    },

    // --- Modal Logic ---
    showModal({ type = 'alert', title, message, confirmText }) {
      this.modal.visible = true
      this.modal.type = type
      this.modal.title = title
      this.modal.message = message
      this.modal.confirmText = confirmText || 'OK'
      return new Promise((resolve, reject) => {
        this.modal.resolve = resolve
        this.modal.reject = reject
      })
    },
    handleModalConfirm() {
      if (this.modal.resolve) this.modal.resolve(true)
      this.modal.visible = false
    },
    handleModalCancel() {
      if (this.modal.resolve) this.modal.resolve(false)
      this.modal.visible = false
    },
    closeModal() {
      if (this.modal.type === 'confirm') this.handleModalCancel()
      else this.handleModalConfirm()
    },
    closeHistory() {
      this.history.visible = false
      this.history.records = []
    },

    // --- Formatting ---
    getInitials(name) {
      return name ? name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : '??'
    },
    formatDate(d) {
      if (!d) return '-'
      return new Date(d).toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' })
    },
    formatTime(t) {
      if (!t) return '--:--'
      if (t.includes('M')) return t // Already formatted
      try {
        const date = t.includes('T') ? new Date(t) : new Date(`2000-01-01T${t}`)
        return isNaN(date) ? t : date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })
      } catch (e) { return t }
    },
    formatHours(h) {
      const num = parseFloat(h) || 0
      const hrs = Math.floor(num)
      const mins = Math.round((num - hrs) * 60)
      return `${hrs}h ${mins}m`
    },
    formatStatus(s) {
      const map = { present: 'Present', completed: 'Completed', absent: 'Absent', late: 'Late', on_leave: 'On Leave' }
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
/* Clean CSS Variables */
:root {
  --primary: #4f46e5;
  --secondary: #64748b;
  --success: #10b981;
  --danger: #ef4444;
  --warning: #f59e0b;
  --bg-page: #f8fafc;
  --bg-card: #ffffff;
  --text-main: #1e293b;
  --text-light: #64748b;
  --border: #e2e8f0;
  --radius: 12px;
}

.attendance-page {
  padding: 2rem;
  background: var(--bg-page, #f8fafc);
  min-height: 100vh;
  font-family: 'Inter', system-ui, sans-serif;
  color: var(--text-main, #1e293b);
}

/* Header */
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
  border: 1px solid var(--border, #e2e8f0);
  border-radius: 8px;
}

/* Overview */
.overview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}
.card {
  background: var(--bg-card, white);
  padding: 1.5rem;
  border-radius: var(--radius, 12px);
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
  border: 1px solid var(--border, #e2e8f0);
}
.metric {
  text-align: center;
}
.metric-val {
  display: block;
  font-size: 2rem;
  font-weight: 800;
}
.metric.success { color: var(--success, #10b981); }
.metric.danger { color: var(--danger, #ef4444); }
.metric.warning { color: var(--warning, #f59e0b); }
.metric-label {
  font-size: 0.85rem;
  color: var(--text-light, #64748b);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* Filter Tabs */
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
  color: var(--text-light, #64748b);
  font-weight: 600;
}
.tab.active {
  background: white;
  color: var(--primary, #4f46e5);
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

/* Employee Grid */
.employee-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}
.employee-card {
  background: white;
  border-radius: var(--radius, 12px);
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
  border-bottom: 1px solid var(--border, #e2e8f0);
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
}
.info h3 {
  margin: 0;
  font-size: 1.1rem;
}
.info p {
  margin: 0;
  font-size: 0.85rem;
  color: var(--text-light, #64748b);
}
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
  color: var(--text-light, #64748b);
  text-transform: uppercase;
}
.card-actions {
  padding: 1rem;
  display: flex;
  gap: 0.5rem;
  border-top: 1px solid var(--border, #e2e8f0);
}

/* Buttons & Badges */
.btn {
  padding: 0.6rem 1.2rem;
  border-radius: 8px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-primary { background: var(--primary, #4f46e5); color: white; }
.btn-primary:hover { opacity: 0.9; }
.btn-secondary { background: white; border: 1px solid var(--border, #e2e8f0); color: var(--text-main, #1e293b); }
.btn-outline-success { background: white; border: 1px solid var(--success, #10b981); color: var(--success, #10b981); }
.btn-outline-success:hover { background: #d1fae5; }
.small { flex: 1; padding: 0.4rem; font-size: 0.9rem; }

.badge {
  margin-left: auto;
  padding: 0.25rem 0.75rem;
  border-radius: 99px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
}
.badge-success { background: #d1fae5; color: #065f46; }
.badge-danger { background: #fee2e2; color: #991b1b; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-neutral { background: #f3f4f6; color: #4b5563; }

/* Modals */
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
.modal-content.wide { max-width: 650px; }
.modal-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--border, #e2e8f0);
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.modal-body { padding: 1.5rem; }
.modal-footer { padding: 1rem 1.5rem; background: #f8fafc; text-align: right; }
.stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 0.5rem; margin-bottom: 1.5rem; }
.stat-card { padding: 0.75rem; border-radius: 8px; text-align: center; border: 1px solid var(--border); background: #f8fafc; }
.stat-card.success { background: #ecfdf5; border-color: #a7f3d0; }
.stat-card.danger { background: #fef2f2; border-color: #fecaca; }

/* Table */
.clean-table { width: 100%; border-collapse: collapse; }
.clean-table th { text-align: left; padding: 0.75rem; color: var(--text-light); border-bottom: 1px solid var(--border); }
.clean-table td { padding: 0.75rem; border-bottom: 1px solid #f1f5f9; }

/* Utils */
.alert.error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
.loading-state, .empty-state { text-align: center; padding: 3rem; color: var(--text-light); }
.spinner { width: 30px; height: 30px; border: 3px solid #e2e8f0; border-top-color: var(--primary); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 1rem; }
@keyframes spin { to { transform: rotate(360deg); } }
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

@media (max-width: 600px) {
  .page-header { flex-direction: column; text-align: center; }
  .header-controls { width: 100%; justify-content: center; }
  .employee-grid { grid-template-columns: 1fr; }
  .stats-row { grid-template-columns: repeat(2, 1fr); }
}
</style>