<template>
  <div class="attendance-monitor">
    <!-- Simple Message/Modal System -->
    <div v-if="modalVisible" :class="['simple-modal-overlay', { 'is-confirm': modalIsConfirm }]">
      <div class="simple-modal-content">
        <h3>{{ modalIsConfirm ? 'Confirmation' : 'Notification' }}</h3>
        <p>{{ modalMessage }}</p>
        <div class="modal-actions">
          <button v-if="modalIsConfirm" @click="cancelAction" class="btn-cancel">Cancel</button>
          <button @click="confirmAction" class="btn-primary">{{ modalIsConfirm ? 'Confirm' : 'Close' }}</button>
        </div>
      </div>
    </div>
    <!-- End Modal System -->

    <!-- History Modal -->
    <div v-if="historyVisible" class="simple-modal-overlay">
      <div class="history-modal-content">
        <div class="history-header">
          <h3>📋 Attendance History</h3>
          <button @click="closeHistory" class="btn-close">&times;</button>
        </div>
        <div class="history-body">
          <div class="history-summary">
            <h4>Summary for {{ currentEmployee?.full_name || 'Employee' }}</h4>
            <p><strong>Department:</strong> {{ employeeInfo.department || 'N/A' }}</p>
            <p><strong>Position:</strong> {{ employeeInfo.position || 'N/A' }}</p>
            <div class="summary-metrics">
              <span>Total Days: {{ summary.total_records || 0 }}</span>
              <span>Present: {{ summary.present_days || 0 }}</span>
              <span>Absent: {{ summary.absent_days || 0 }}</span>
              <span>Late: {{ summary.late_days || 0 }}</span>
            </div>
            <p><strong>Total Hours:</strong> {{ formatHours(summary.total_hours || 0) }}</p>
            <p><strong>Avg Hours/Day:</strong> {{ formatHours(summary.average_hours_per_day || 0) }}</p>
          </div>
          <div class="history-list">
            <h4>Recent Records (Last 10)</h4>
            <div v-if="loadingHistory" class="loading">Loading history...</div>
            <div v-else-if="formattedHistory.length === 0" class="empty-state">
              No attendance history found.
            </div>
            <div v-else v-for="(record, index) in formattedHistory" :key="index" class="history-item">
              <div class="history-date">{{ record.date }}</div>
              <div class="history-details">
                <span class="status-badge" :class="getStatusClass(record.status)">{{ formatStatus(record.status) }}</span>
                <span>Clock In: {{ record.clockIn }}</span>
                <span>Clock Out: {{ record.clockOut }}</span>
                <span>Hours: {{ record.hours }}</span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-actions">
          <button @click="closeHistory" class="btn-primary">Close</button>
        </div>
      </div>
    </div>
    <!-- End History Modal -->

    <div class="page-header">
      <div>
        <h1>Team Attendance Monitor</h1>
        <p class="subtitle">Monitoring team attendance for {{ formatDate(selectedDate) }}</p>
      </div>
      <div class="header-actions">
        <input
          type="date"
          v-model="selectedDate"
          @change="fetchAttendance"
          :max="today"
          class="date-picker"
        />
        <button @click="fetchAttendance" class="btn-refresh" :disabled="loading">
          <span v-if="loading">Loading...</span>
          <span v-else>🔄 Refresh</span>
        </button>
      </div>
    </div>
    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Loading attendance data...</p>
    </div>
    <div v-else-if="error" class="error-message">
      {{ error }}
      <button @click="retryFetch" class="btn-primary">Retry</button>
    </div>
    <div v-else>
      <!-- Overview Section -->
      <div class="overview-section" v-if="teamOverview">
        <div class="overview-card">
          <h3>📊 Team Attendance Overview</h3>
          <div class="overview-metrics">
            <div class="metric total">
              <span class="metric-value">{{ teamOverview.totalEmployees }}</span>
              <span class="metric-label">Total Team Members</span>
            </div>
            <div class="metric present">
              <span class="metric-value">{{ teamOverview.presentCount }}</span>
              <span class="metric-label">Present</span>
            </div>
            <div class="metric absent">
              <span class="metric-value">{{ teamOverview.absentCount }}</span>
              <span class="metric-label">Absent</span>
            </div>
            <div class="metric late">
              <span class="metric-value">{{ teamOverview.lateCount }}</span>
              <span class="metric-label">Late</span>
            </div>
            <div class="metric rate">
              <span class="metric-value">{{ teamOverview.attendanceRate }}%</span>
              <span class="metric-label">Attendance Rate</span>
            </div>
          </div>
        </div>
      </div>
      <!-- Employees Section -->
      <div class="employees-section">
        <h2>Team Members ({{ attendanceData.length }})</h2>
     
        <div class="filter-tabs" v-if="attendanceData.length > 0">
          <button
            @click="filterStatus = 'all'"
            :class="{ active: filterStatus === 'all' }"
            class="tab-btn"
          >
            All ({{ attendanceData.length }})
          </button>
          <button
            @click="filterStatus = 'present'"
            :class="{ active: filterStatus === 'present' }"
            class="tab-btn"
          >
            Present ({{ presentCount }})
          </button>
          <button
            @click="filterStatus = 'absent'"
            :class="{ active: filterStatus === 'absent' }"
            class="tab-btn"
          >
            Absent ({{ absentCount }})
          </button>
          <button
            @click="filterStatus = 'late'"
            :class="{ active: filterStatus === 'late' }"
            class="tab-btn"
          >
            Late ({{ lateCount }})
          </button>
        </div>
        <div class="employees-grid">
          <div v-for="employee in filteredData" :key="employee.id" class="employee-card">
            <div class="employee-header">
              <div class="employee-avatar">
                {{ getInitials(employee.full_name) }}
              </div>
              <div class="employee-info">
                <h3>{{ employee.full_name }}</h3>
                <p class="employee-id">{{ employee.employee_id }} • {{ employee.department }}</p>
                <p class="employee-position">{{ employee.position }}</p>
              </div>
              <div class="attendance-status">
                <span class="status-badge" :class="getStatusClass(employee.status)">
                  {{ formatStatus(employee.status) }}
                </span>
              </div>
            </div>
         
            <div class="attendance-details">
              <div class="detail-row">
                <span class="label">🕐 Clock In:</span>
                <span class="value">{{ formatTime(employee.clock_in_time) }}</span>
              </div>
              <div class="detail-row">
                <span class="label">🕐 Clock Out:</span>
                <span class="value">{{ formatTime(employee.clock_out_time) }}</span>
              </div>
              <div class="detail-row">
                <span class="label">⏱️ Hours Worked:</span>
                <span class="value">{{ formatHours(employee.hours_worked) }}</span>
              </div>
              <div class="detail-row">
                <span class="label">📅 Date:</span>
                <span class="value">{{ formatDate(employee.date) }}</span>
              </div>
            </div>
            <div class="attendance-actions">
              <button @click="viewHistory(employee)" class="btn-view">
                📋 View History
              </button>
              <button
                v-if="employee.status === 'absent' && isToday"
                @click="attemptMarkPresent(employee)"
                class="btn-mark"
              >
                ✓ Mark Present
              </button>
            </div>
          </div>
          <div v-if="filteredData.length === 0" class="empty-state">
              <div class="empty-icon">📭</div>
              <p>No {{ filterStatus === 'all' ? '' : formatStatus(filterStatus).toLowerCase() }} employees found</p>
              <p v-if="filterStatus !== 'all'">Try changing the filter or date</p>
            </div>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import axios from 'axios';
export default {
  name: 'AttendanceMonitor',
  data() {
    return {
      attendanceData: [],
      teamOverview: null,
      loading: false,
      error: null,
      retryCount: 0,
      selectedDate: new Date().toISOString().split('T')[0],
      today: new Date().toISOString().split('T')[0],
      filterStatus: 'all',
      // Modal state
      modalVisible: false,
      modalMessage: '',
      modalIsConfirm: false,
      modalResolve: null,
      modalReject: null,
      currentEmployeeToMark: null,
      // History state
      historyVisible: false,
      loadingHistory: false,
      formattedHistory: [],
      summary: {},
      employeeInfo: {},
      currentEmployee: null,
    };
  },
  computed: {
    isToday() {
      return this.selectedDate === this.today;
    },
   
    presentCount() {
      return this.attendanceData.filter(e =>
        ['present', 'completed'].includes(e.status)
      ).length;
    },
    absentCount() {
      return this.attendanceData.filter(e =>
        ['absent', 'on_leave'].includes(e.status)
      ).length;
    },
    lateCount() {
      return this.attendanceData.filter(e => e.status === 'late').length;
    },
    filteredData() {
      if (this.filterStatus === 'all') {
        return this.attendanceData;
      }
     
      return this.attendanceData.filter(emp => {
        if (this.filterStatus === 'present') {
          return ['present', 'completed'].includes(emp.status);
        }
        if (this.filterStatus === 'absent') {
          return ['absent', 'on_leave'].includes(emp.status);
        }
        if (this.filterStatus === 'late') {
          return emp.status === 'late';
        }
        return emp.status === this.filterStatus;
      });
    }
  },
  mounted() {
    this.fetchAttendance();
  },
  methods: {
    // --- Modal Methods ---
    showAlert(message) {
      this.modalMessage = message;
      this.modalIsConfirm = false;
      this.modalVisible = true;
      return new Promise((resolve) => {
        this.modalResolve = resolve;
      });
    },
    showConfirm(message) {
      this.modalMessage = message;
      this.modalIsConfirm = true;
      this.modalVisible = true;
      return new Promise((resolve, reject) => {
        this.modalResolve = resolve;
        this.modalReject = reject;
      });
    },
    confirmAction() {
      this.modalVisible = false;
      if (this.modalResolve) this.modalResolve();
      this.resetModal();
    },
    cancelAction() {
      this.modalVisible = false;
      if (this.modalReject) this.modalReject();
      this.resetModal();
    },
    resetModal() {
      this.modalResolve = null;
      this.modalReject = null;
      this.modalMessage = '';
      this.modalIsConfirm = false;
      this.currentEmployeeToMark = null;
    },
    // --- History Modal Methods ---
    openHistory(employee, historyData, summaryData, employeeData) {
      this.currentEmployee = employee;
      this.formattedHistory = historyData.map(record => ({
        date: this.formatDate(record.date),
        status: record.status,
        clockIn: this.formatTime(record.clock_in),
        clockOut: this.formatTime(record.clock_out),
        hours: this.formatHours(record.total_hours || 0)
      }));
      this.summary = summaryData;
      this.employeeInfo = employeeData;
      this.historyVisible = true;
    },
    closeHistory() {
      this.historyVisible = false;
      this.formattedHistory = [];
      this.summary = {};
      this.employeeInfo = {};
      this.currentEmployee = null;
    },
    // --- API Methods ---
    async fetchAttendance(retry = false) {
      this.loading = true;
      this.error = null;
      try {
        const response = await axios.get('/api/manager/attendance', {
          params: { date: this.selectedDate }
        });
        const attendances = response.data.attendances || [];
        const attendancesForDate = attendances.filter(a => a.date === this.selectedDate);
        // Transform attendances to expected format
        this.attendanceData = attendancesForDate.map(att => ({
          id: att.employee.id,
          full_name: `${att.employee.first_name} ${att.employee.last_name}`.trim(),
          employee_id: att.employee.id,
          department: att.employee.department || 'N/A',
          position: att.employee.position || 'N/A',
          status: att.status,
          clock_in_time: att.checkIn,
          clock_out_time: att.checkOut,
          hours_worked: att.hoursWorked || 0,
          date: att.date
        }));
        const summary = response.data.summary || {};
        const totalEmployees = summary.total_employees || 0;
     
        // Calculate counts from actual attendance data
        const presentOnTime = this.attendanceData.filter(e =>
          ['present', 'completed'].includes(e.status)
        ).length;
        const late = this.attendanceData.filter(e => e.status === 'late').length;
        const absent = this.attendanceData.filter(e =>
          ['absent', 'on_leave'].includes(e.status)
        ).length;
        // FIXED: Calculate attendance rate correctly
        const totalPresentAndLate = presentOnTime + late;
        const attendanceRate = totalEmployees > 0
          ? Math.round((totalPresentAndLate / totalEmployees) * 100)
          : 0;
        // Assign to data
        this.teamOverview = {
          totalEmployees,
          presentCount: presentOnTime,
          absentCount: absent,
          lateCount: late,
          attendanceRate: attendanceRate
        };
      } catch (err) {
        this.handleApiError(err);
      } finally {
        this.loading = false;
      }
    },
    retryFetch() {
      this.retryCount++;
      if (this.retryCount <= 3) {
        this.fetchAttendance(true);
      } else {
        this.error = 'Max retries exceeded. Please refresh the page.';
        this.retryCount = 0;
      }
    },
    handleApiError(err) {
      let errorMsg = 'Failed to load attendance data.';
      if (err.response) {
        if (err.response.status === 401) {
          errorMsg = 'Session expired. Please log in again.';
        } else if (err.response.status === 403) {
          errorMsg = 'Access denied. You must be a manager to view this data.';
        } else if (err.response.status === 404) {
          errorMsg = 'No attendance data found for this date.';
          this.attendanceData = [];
          this.teamOverview = { totalEmployees: 0, presentCount: 0, absentCount: 0, lateCount: 0, attendanceRate: 0 };
          return;
        } else if (err.response.data?.message) {
          errorMsg = err.response.data.message;
        }
      } else if (err.request) {
        errorMsg = 'Network error. Please check your connection.';
      }
      this.error = errorMsg;
    },
    async attemptMarkPresent(employee) {
      this.currentEmployeeToMark = employee;
      const confirmed = await this.showConfirm(
        `Mark ${employee.full_name} as present for ${this.formatDate(this.selectedDate)}? This will register a manual clock-in.`
      ).catch(() => false);
      if (confirmed) {
        await this.markPresent(employee);
      }
    },
    async markPresent(employee) {
      try {
        await axios.post(`/api/manager/attendance/${employee.id}/mark-present`, {
          date: this.selectedDate
        });
        await this.showAlert('Employee successfully marked as present!');
        await this.fetchAttendance();
      } catch (err) {
        const msg = err.response?.data?.message || 'Failed to mark employee as present.';
        await this.showAlert(msg);
      }
    },
   async viewHistory(employee) {
     this.loadingHistory = true;
     try {
      // CORRECTED: Use the proper manager employee history route
      const response = await axios.get(`/api/manager/attendance/${employee.id}/history`, {
        params: {
          month: new Date(this.selectedDate).getMonth() + 1,
          year: new Date(this.selectedDate).getFullYear(),
          per_page: 10 // Show last 10 records
        }
      });
     
      const historyData = response.data.data || [];
      const summaryData = response.data.summary || {};
      const employeeData = response.data.employee || {};
     
      if (historyData.length === 0) {
        await this.showAlert(`No attendance history found for ${employee.full_name}.`);
        return;
      }
     
      this.openHistory(employee, historyData, summaryData, employeeData);
     
    } catch (err) {
     
      let errorMsg = 'Failed to fetch attendance history.';
     
      if (err.response) {
        if (err.response.status === 404) {
          errorMsg = `No attendance history found for ${employee.full_name}.`;
        } else if (err.response.status === 403) {
          errorMsg = 'You do not have permission to view this employee\'s history.';
        } else if (err.response.data?.message) {
          errorMsg = err.response.data.message;
        }
      } else if (err.request) {
        errorMsg = 'Network error. Please check your connection.';
      }
     
      await this.showAlert(errorMsg);
    } finally {
      this.loadingHistory = false;
    }
  },
    // --- Utility Methods ---
    getInitials(name) {
      if (!name) return '??';
      const parts = name.split(' ');
      const first = parts[0]?.[0] || '';
      const last = parts.length > 1 ? parts[parts.length - 1]?.[0] : '';
      return (first + last).toUpperCase().substring(0, 2);
    },
   
    formatDate(date) {
      if (!date) return 'N/A';
      try {
        const d = new Date(date.includes('T') ? date : date + 'T00:00:00');
        return d.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          weekday: 'short'
        });
      } catch (e) {
        return date;
      }
    },
   
    formatTime(time) {
      if (!time) return 'N/A';
      // If already in 12h format with AM/PM, return as is
      if (time.match(/AM|PM/i)) {
        return time;
      }
      try {
        let date;
        if (time.includes('T') || time.includes('Z')) {
          date = new Date(time);
        } else {
          // Assume 24h format like "09:12"
          date = new Date(`2000-01-01T${time}:00`);
        }
        if (isNaN(date)) return time;
        return date.toLocaleTimeString('en-US', {
          hour: 'numeric',
          minute: '2-digit',
          hour12: true
        });
      } catch (e) {
        return time;
      }
    },
   
    formatHours(hours) {
      if (hours == null || hours === undefined) return '0h 0m';
      const h = Math.floor(hours);
      const m = Math.round((hours - h) * 60);
      return `${h}h ${m}m`;
    },
   
    formatStatus(status) {
      const statuses = {
        present: 'Present',
        completed: 'Completed',
        absent: 'Absent',
        late: 'Late',
        on_leave: 'On Leave'
      };
      return statuses[status] || status;
    },
   
    getStatusClass(status) {
      if (status === 'completed') {
        return 'status-present';
      }
      return `status-${status}`;
    }
  }
};
</script>
<style scoped>
/* Your existing CSS styles remain exactly the same */
.attendance-monitor {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  background: #f8f9fa;
  min-height: 100vh;
  position: relative;
}
/* --- Modal Styles --- */
.simple-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(4px);
}
.simple-modal-content {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
  width: 90%;
  max-width: 400px;
  text-align: center;
  animation: modal-fade-in 0.3s ease-out;
}
.simple-modal-content h3 {
  color: #2d3748;
  margin-top: 0;
  margin-bottom: 1rem;
}
.simple-modal-content p {
  color: #4a5568;
  margin-bottom: 1.5rem;
}
.modal-actions {
  display: flex;
  justify-content: space-around;
  gap: 1rem;
}
.btn-cancel {
  padding: 0.75rem 1.5rem;
  background: #e2e8f0;
  color: #4a5568;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s;
}
.btn-cancel:hover {
  background: #cbd5e0;
}
/* --- History Modal Styles --- */
.history-modal-content {
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
  width: 90%;
  max-width: 600px;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  animation: modal-fade-in 0.3s ease-out;
}
.history-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}
.history-header h3 {
  margin: 0;
  color: #2d3748;
}
.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #718096;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.btn-close:hover {
  color: #f5222d;
}
.history-body {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
}
.history-summary {
  background: #f8f9fa;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
}
.history-summary h4 {
  margin: 0 0 0.5rem 0;
  color: #2d3748;
}
.history-summary p {
  margin: 0.25rem 0;
  color: #4a5568;
}
.summary-metrics {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  margin: 0.5rem 0;
}
.summary-metrics span {
  background: white;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.85rem;
  color: #718096;
}
.history-list h4 {
  margin: 0 0 1rem 0;
  color: #2d3748;
}
.history-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  margin-bottom: 0.5rem;
}
.history-date {
  font-weight: 600;
  color: #2d3748;
  min-width: 120px;
}
.history-details {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.25rem;
  flex: 1;
}
.history-details span {
  font-size: 0.85rem;
  color: #718096;
}
/* --- Rest of your original styles below --- */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}
.page-header h1 {
  color: #2d3748;
  font-size: 2rem;
  margin: 0 0 0.5rem 0;
}
.subtitle {
  color: #718096;
  margin: 0;
  font-size: 1rem;
}
.header-actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}
.date-picker {
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  cursor: pointer;
}
.btn-refresh, .btn-primary {
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s, opacity 0.2s;
}
.btn-primary {
  flex: 1;
}
.btn-refresh:hover:not(:disabled), .btn-primary:hover {
  transform: translateY(-2px);
}
.btn-refresh:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.error-message {
  background: #fff1f0;
  color: #f5222d;
  padding: 2rem;
  border-radius: 12px;
  text-align: center;
  border: 1px solid #ffccc7;
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
@keyframes modal-fade-in {
  from { opacity: 0; transform: translateY(-20px) scale(0.95); }
  to { opacity: 1; transform: translateY(0) scale(1); }
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
  margin: 0 0 1.5rem 0;
  color: #2d3748;
  font-size: 1.25rem;
  border-bottom: 1px solid #f0f0f0;
  padding-bottom: 0.75rem;
}
.overview-metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1.5rem;
}
.metric {
  text-align: center;
  padding: 1rem;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}
.metric-value {
  display: block;
  font-size: 1.8rem;
  font-weight: 700;
  color: #4a5568;
}
.metric.present .metric-value { color: #52c41a; }
.metric.absent .metric-value { color: #f5222d; }
.metric.late .metric-value { color: #fa8c16; }
.metric.rate .metric-value { color: #667eea; }
.metric-label {
  display: block;
  font-size: 0.9rem;
  color: #718096;
  margin-top: 0.25rem;
  font-weight: 500;
}
.employees-section h2 {
  color: #2d3748;
  margin-bottom: 1rem;
}
.filter-tabs {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
  border-bottom: 2px solid #e2e8f0;
}
.tab-btn {
  padding: 0.5rem 1rem;
  background: none;
  border: none;
  border-bottom: 3px solid transparent;
  color: #718096;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.tab-btn:hover {
  color: #4a5568;
}
.tab-btn.active {
  color: #667eea;
  border-bottom-color: #667eea;
}
.employees-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}
.employee-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  padding: 1.5rem;
  transition: transform 0.2s, box-shadow 0.2s;
  border: 1px solid #e2e8f0;
}
.employee-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 6px 25px rgba(0,0,0,0.1);
}
.employee-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #f0f0f0;
}
.employee-avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  font-weight: 700;
  flex-shrink: 0;
}
.employee-info h3 {
  margin: 0 0 0.2rem 0;
  color: #2d3748;
  font-size: 1.15rem;
}
.employee-id, .employee-position {
  color: #718096;
  font-size: 0.85rem;
  margin: 0;
}
.attendance-status {
  margin-left: auto;
  flex-shrink: 0;
}
.status-badge {
  padding: 0.3rem 0.8rem;
  border-radius: 16px;
  font-size: 0.8rem;
  font-weight: 700;
  display: inline-block;
  text-transform: uppercase;
}
.status-present, .status-completed {
  background: #f6ffed;
  color: #52c41a;
}
.status-absent {
  background: #fff1f0;
  color: #f5222d;
}
.status-late {
  background: #fff7e6;
  color: #fa8c16;
}
.status-on_leave {
  background: #e6f7ff;
  color: #1890ff;
}
.attendance-details {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
  padding-top: 0.5rem;
}
.detail-row {
  display: flex;
  justify-content: space-between;
  font-size: 0.95rem;
}
.label {
  color: #718096;
  font-weight: 500;
}
.value {
  color: #2d3748;
  font-weight: 600;
}
.attendance-actions {
  display: flex;
  gap: 0.75rem;
}
.attendance-actions button {
  flex: 1;
  padding: 0.8rem;
  border: 1px solid transparent;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.2s;
}
.btn-view {
  background: #f0f3ff;
  color: #667eea;
}
.btn-view:hover {
  background: #e8ecff;
}
.btn-mark {
  background: #f6ffed;
  color: #52c41a;
  border-color: #d9f7be;
}
.btn-mark:hover {
  background: #e3ffe5;
}
.empty-state {
  grid-column: 1 / -1;
  text-align: center;
  padding: 4rem;
  color: #718096;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  margin-top: 1rem;
}
.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}
@media (max-width: 900px) {
  .employees-grid {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  }
}
@media (max-width: 600px) {
  .page-header {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }
 
  .header-actions {
    width: 100%;
    justify-content: center;
  }
 
  .date-picker {
    flex-grow: 1;
  }
  .employees-grid {
    grid-template-columns: 1fr;
  }
 
  .overview-metrics {
    grid-template-columns: 1fr;
  }
  .history-item {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }
  .history-details {
    align-items: flex-start;
  }
}
</style>