<template>
  <div class="attendance-monitor">
    <!-- Simple Message/Modal System -->
    <div v-if="modalVisible" :class="['simple-modal-overlay', { 'is-confirm': modalIsConfirm, 'is-success': modalIsSuccess, 'is-error': modalIsError }]">
      <div class="simple-modal-content">
        <div class="modal-icon" v-if="modalIsSuccess || modalIsError">
          <span v-if="modalIsSuccess">✅</span>
          <span v-else-if="modalIsError">❌</span>
        </div>
        <h3>{{ modalTitle }}</h3>
        <p>{{ modalMessage }}</p>
        <div class="modal-actions">
          <button v-if="modalIsConfirm" @click="cancelAction" class="btn-cancel">Cancel</button>
          <button @click="confirmAction" class="btn-primary" :class="{ 'btn-success': modalIsSuccess, 'btn-error': modalIsError }">
            {{ modalButtonText }}
          </button>
        </div>
      </div>
    </div>
    <!-- End Modal System -->
    <div class="page-header">
      <div>
        <h1>Team Attendance Monitor</h1>
        <p class="subtitle">Monitoring team attendance for {{ safeFormatDate(selectedDate) }}</p>
      </div>
      <div class="header-actions">
        <input
          type="date"
          v-model="selectedDate"
          @change="fetchAllData"
          :max="today"
          class="date-picker"
        />
        <button @click="fetchAllData" class="btn-refresh" :disabled="loading">
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
      <!-- Department Overview Section -->
      <div class="department-overview" v-if="departmentStats.length > 0">
        <h2>📊 Department Overview</h2>
        <div class="department-cards">
          <div v-for="dept in departmentStats" :key="dept.name" class="department-card">
            <div class="department-header">
              <h3>{{ dept.name }}</h3>
              <span class="dept-count">{{ dept.total }} employees</span>
            </div>
            <div class="dept-metrics">
              <div class="dept-metric present">
                <span class="dept-value">{{ dept.present }}</span>
                <span class="dept-label">Present</span>
              </div>
              <div class="dept-metric absent">
                <span class="dept-value">{{ dept.absent }}</span>
                <span class="dept-label">Absent</span>
              </div>
              <div class="dept-metric late">
                <span class="dept-value">{{ dept.late }}</span>
                <span class="dept-label">Late</span>
              </div>
              <div class="dept-metric rate">
                <span class="dept-value">{{ dept.rate }}%</span>
                <span class="dept-label">Rate</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Overview Section -->
      <div class="overview-section" v-if="currentOverview">
        <div class="overview-card">
          <h3>📊 {{ selectedDepartment === 'all' ? 'Team' : selectedDepartment }} Attendance Overview</h3>
          <div class="overview-metrics">
            <div class="metric total">
              <span class="metric-value">{{ currentOverview.totalEmployees }}</span>
              <span class="metric-label">Total Team Members</span>
            </div>
            <div class="metric present">
              <span class="metric-value">{{ currentOverview.presentCount }}</span>
              <span class="metric-label">Present</span>
            </div>
            <div class="metric absent">
              <span class="metric-value">{{ currentOverview.absentCount }}</span>
              <span class="metric-label">Absent</span>
            </div>
            <div class="metric late">
              <span class="metric-value">{{ currentOverview.lateCount }}</span>
              <span class="metric-label">Late</span>
            </div>
            <div class="metric rate">
              <span class="metric-value">{{ currentOverview.attendanceRate }}%</span>
              <span class="metric-label">Attendance Rate</span>
            </div>
          </div>
        </div>
      </div>
      <!-- Employees Section -->
      <div class="employees-section">
        <div class="section-header">
          <h2>Team Members ({{ allCount }})</h2>
          <div class="department-filter" v-if="departments.length > 0">
            <select v-model="selectedDepartment" @change="onDepartmentChange" class="dept-select">
              <option value="all">All Departments</option>
              <option v-for="dept in departments" :key="dept" :value="dept">{{ dept }}</option>
            </select>
          </div>
        </div>
       
        <div class="filter-tabs" v-if="attendanceData.length > 0">
          <button
            @click="filterStatus = 'all'"
            :class="{ active: filterStatus === 'all' }"
            class="tab-btn"
          >
            All ({{ allCount }})
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
                <span class="value">{{ safeFormatTime(employee.clock_in_time) }}</span>
              </div>
              <div class="detail-row">
                <span class="label">🕐 Clock Out:</span>
                <span class="value">{{ safeFormatTime(employee.clock_out_time) }}</span>
              </div>
              <div class="detail-row">
                <span class="label">⏱️ Hours Worked:</span>
                <span class="value">{{ formatHours(employee.hours_worked) }}</span>
              </div>
              <div class="detail-row">
                <span class="label">📅 Date:</span>
                <span class="value">{{ safeFormatDate(employee.date) }}</span>
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
                :disabled="markingPresentId === employee.id"
              >
                <span v-if="markingPresentId === employee.id" class="loading-dots">Marking</span>
                <span v-else>✓ Mark Present</span>
              </button>
            </div>
          </div>
          <div v-if="filteredData.length === 0" class="empty-state">
            <div class="empty-icon">📭</div>
            <p>No {{ filterStatus === 'all' ? '' : formatStatus(filterStatus).toLowerCase() }} employees found</p>
            <p v-if="filterStatus !== 'all' || selectedDepartment !== 'all'">Try changing the filter or date</p>
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
      departmentStats: [],
      departments: [],
      selectedDepartment: 'all',
      loading: false,
      error: null,
      retryCount: 0,
      selectedDate: new Date().toISOString().split('T')[0],
      today: new Date().toISOString().split('T')[0],
      filterStatus: 'all',
      markingPresentId: null, // Track which employee is being marked present
      // Enhanced Modal state
      modalVisible: false,
      modalTitle: '',
      modalMessage: '',
      modalIsConfirm: false,
      modalIsSuccess: false,
      modalIsError: false,
      modalButtonText: 'Close',
      modalResolve: null,
      modalReject: null,
      currentEmployeeToMark: null,
    };
  },
  computed: {
    isToday() {
      return this.selectedDate === this.today;
    },
   
    departmentFilteredData() {
      if (this.selectedDepartment === 'all') {
        return this.attendanceData;
      }
      return this.attendanceData.filter(emp => emp.department === this.selectedDepartment);
    },
    allCount() {
      return this.departmentFilteredData.length;
    },
    presentCount() {
      return this.departmentFilteredData.filter(e =>
        ['present', 'completed'].includes(e.status)
      ).length;
    },
    absentCount() {
      return this.departmentFilteredData.filter(e =>
        ['absent', 'on_leave'].includes(e.status)
      ).length;
    },
    lateCount() {
      return this.departmentFilteredData.filter(e => e.status === 'late').length;
    },
    currentOverview() {
      const data = this.departmentFilteredData;
      const total = data.length;
      const presentOnTime = data.filter(e =>
        ['present', 'completed'].includes(e.status)
      ).length;
      const late = data.filter(e => e.status === 'late').length;
      const absent = total - (presentOnTime + late);
      return {
        totalEmployees: total,
        presentCount: presentOnTime,
        absentCount: absent,
        lateCount: late,
        attendanceRate: total > 0 ?
          Math.round(((presentOnTime + late) / total) * 100) : 0
      };
    },
    filteredData() {
      let filtered = this.departmentFilteredData;
      // Filter by status
      if (this.filterStatus === 'all') {
        return filtered;
      }
     
      return filtered.filter(emp => {
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
    this.fetchAllData();
  },
 
  methods: {
    onDepartmentChange() {
      this.filterStatus = 'all'; // Reset status filter when department changes
    },
    // --- Enhanced Modal Methods ---
    showAlert(message, title = 'Notification', type = 'info') {
      this.modalTitle = title;
      this.modalMessage = message;
      this.modalIsConfirm = false;
      this.modalIsSuccess = type === 'success';
      this.modalIsError = type === 'error';
      this.modalButtonText = 'Close';
      this.modalVisible = true;
     
      return new Promise((resolve) => {
        this.modalResolve = resolve;
      });
    },
    showConfirm(message, title = 'Confirmation') {
      this.modalTitle = title;
      this.modalMessage = message;
      this.modalIsConfirm = true;
      this.modalIsSuccess = false;
      this.modalIsError = false;
      this.modalButtonText = 'Confirm';
      this.modalVisible = true;
     
      return new Promise((resolve, reject) => {
        this.modalResolve = resolve;
        this.modalReject = reject;
      });
    },
    showSuccess(message, title = 'Success!') {
      return this.showAlert(message, title, 'success');
    },
    showError(message, title = 'Error') {
      return this.showAlert(message, title, 'error');
    },
    confirmAction() {
      this.modalVisible = false;
      if (this.modalResolve) this.modalResolve(true);
      this.resetModal();
    },
    cancelAction() {
      this.modalVisible = false;
      if (this.modalReject) this.modalReject(false);
      this.resetModal();
    },
    resetModal() {
      this.modalResolve = null;
      this.modalReject = null;
      this.modalTitle = '';
      this.modalMessage = '';
      this.modalIsConfirm = false;
      this.modalIsSuccess = false;
      this.modalIsError = false;
      this.modalButtonText = 'Close';
      this.currentEmployeeToMark = null;
    },
    // --- API Methods ---
    async fetchAllData() {
      this.loading = true;
      this.error = null;
      try {
        const [employeesRes, attendanceRes] = await Promise.all([
          axios.get('/api/admin/employees'),
          axios.get('/api/admin/reports/attendance', {
            params: {
              date: this.selectedDate,
              detailed: true
            }
          })
        ]);
        await this.processAttendanceData(attendanceRes.data, employeesRes.data);
      } catch (err) {
        console.error('❌ Fetch all data error:', err);
        this.handleApiError(err);
      } finally {
        this.loading = false;
      }
    },
    async processAttendanceData(attendanceData, employeesData) {
      const employees = employeesData.data || employeesData.employees || employeesData || [];
      const attendances = attendanceData.data || attendanceData.attendances || attendanceData || [];
     
      this.departments = [...new Set(employees.map(emp => emp.department || 'Unassigned'))].sort();
      // Filter attendances for selected date
      const selectedDateFormatted = new Date(this.selectedDate).toISOString().split('T')[0];
      const attendancesForDate = attendances.filter(a => {
        if (!a.date) return false;
        const attendanceDate = new Date(a.date).toISOString().split('T')[0];
        return attendanceDate === selectedDateFormatted;
      });
      // Create map of attendance records by employee ID
      const attendanceMap = new Map();
      attendancesForDate.forEach(att => {
        const employeeId = att.employee_id || att.employee?.id;
        if (employeeId) {
          attendanceMap.set(employeeId, att);
        }
      });
      // Create attendance records for ALL employees
      this.attendanceData = employees.map(emp => {
        const att = attendanceMap.get(emp.id) || null;
       
        const employeeDetails = att?.employee || emp;
        const fullName = this.getEmployeeFullName(employeeDetails);
        const employeeId = employeeDetails.employee_id || `EMP${String(emp.id).padStart(4, '0')}`;
        const department = employeeDetails.department || emp.department || 'Unassigned';
        const position = employeeDetails.position || emp.position || 'N/A';
       
        const hoursWorked = att ? this.calculateHoursWorked(att.clock_in, att.clock_out) : 0;
       
        let status = 'absent';
        if (att) {
          status = att.status || 'absent';
        }
        return {
          id: emp.id,
          full_name: fullName,
          employee_id: employeeId,
          department: department,
          position: position,
          status: status,
          clock_in_time: att ? att.clock_in : null,
          clock_out_time: att ? att.clock_out : null,
          hours_worked: hoursWorked,
          date: this.selectedDate
        };
      });
      this.calculateDepartmentStats(employees);
    },
    calculateHoursWorked(clockIn, clockOut) {
      if (!clockIn || !clockOut) return 0;
     
      try {
        let clockInTime, clockOutTime;
       
        if (clockIn.includes('T') || clockIn.includes('Z')) {
          clockInTime = new Date(clockIn);
          clockOutTime = new Date(clockOut);
        } else {
          clockInTime = new Date(`2000-01-01T${clockIn}`);
          clockOutTime = new Date(`2000-01-01T${clockOut}`);
        }
       
        if (isNaN(clockInTime.getTime()) || isNaN(clockOutTime.getTime())) {
          return 0;
        }
       
        const diffMs = clockOutTime - clockInTime;
        const hours = diffMs / (1000 * 60 * 60);
       
        return Math.max(0, Math.round(hours * 100) / 100);
      } catch (e) {
        console.warn('Error calculating hours worked:', e);
        return 0;
      }
    },
    getEmployeeFullName(employee) {
      if (!employee) return 'Unknown Employee';
     
      if (employee.first_name && employee.last_name) {
        return `${employee.first_name} ${employee.last_name}`.trim();
      }
     
      if (employee.full_name) {
        return employee.full_name;
      }
     
      if (employee.name) {
        return employee.name;
      }
     
      return 'Unknown Employee';
    },
    calculateDepartmentStats(employees) {
      const deptMap = new Map();
     
      this.departments.forEach(dept => {
        deptMap.set(dept, {
          name: dept,
          total: 0,
          present: 0,
          absent: 0,
          late: 0,
          rate: 0
        });
      });
      employees.forEach(emp => {
        const dept = emp.department || 'Unassigned';
        if (deptMap.has(dept)) {
          deptMap.get(dept).total++;
        }
      });
      this.attendanceData.forEach(att => {
        const dept = att.department;
        if (deptMap.has(dept)) {
          const deptStat = deptMap.get(dept);
          if (['present', 'completed'].includes(att.status)) deptStat.present++;
          else if (att.status === 'late') deptStat.late++;
          else if (['absent', 'on_leave'].includes(att.status)) deptStat.absent++;
        }
      });
      deptMap.forEach(deptStat => {
        if (deptStat.total > 0) {
          const attended = deptStat.present + deptStat.late;
          deptStat.rate = Math.round((attended / deptStat.total) * 100);
        }
      });
      this.departmentStats = Array.from(deptMap.values());
    },
    retryFetch() {
      this.retryCount++;
      if (this.retryCount <= 3) {
        this.fetchAllData();
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
          errorMsg = 'Access denied. You must be an admin to view this data.';
        } else if (err.response.status === 404) {
          errorMsg = 'No attendance data found for this date.';
          this.resetData();
          return;
        } else if (err.response.data?.message) {
          errorMsg = err.response.data.message;
        }
      } else if (err.request) {
        errorMsg = 'Network error. Please check your connection.';
      }
     
      this.error = errorMsg;
    },
    resetData() {
      this.attendanceData = [];
      this.departmentStats = [];
    },
    async attemptMarkPresent(employee) {
      this.currentEmployeeToMark = employee;
      const confirmed = await this.showConfirm(
        `Mark ${employee.full_name} as present for ${this.safeFormatDate(this.selectedDate)}? This will register a manual clock-in.`,
        'Mark Employee Present'
      ).catch(() => false);
      if (confirmed) {
        await this.markPresent(employee);
      }
    },
    async markPresent(employee) {
      this.markingPresentId = employee.id;
     
      try {
        const response = await axios.post(`/api/admin/attendance/${employee.id}/mark-present`, {
          date: this.selectedDate,
          force: true
        });
       
        console.log('✅ Mark present response:', response.data);
       
        // Show success message
        await this.showSuccess(
          `${employee.full_name} has been successfully marked as present for ${this.safeFormatDate(this.selectedDate)}.`,
          'Success!'
        );
       
        // Refresh the data to reflect the change immediately
        await this.fetchAllData();
       
      } catch (err) {
        console.error('❌ Mark present error:', err);
        const errorMessage = err.response?.data?.message || 'Failed to mark employee as present. Please try again.';
       
        // Show error message
        await this.showError(
          errorMessage,
          'Failed to Mark Present'
        );
      } finally {
        this.markingPresentId = null;
      }
    },
    viewHistory(employee) {
      this.showAlert(`Attendance history for ${employee.full_name} would open in a real app.`, 'View History');
    },
    // --- Utility Methods ---
    getInitials(name) {
      if (!name || name === 'Unknown Employee') return '??';
      const parts = name.split(' ').filter(part => part.length > 0);
      const first = parts[0]?.[0] || '';
      const last = parts.length > 1 ? parts[parts.length - 1]?.[0] : '';
      return (first + last).toUpperCase().substring(0, 2);
    },
   
    safeFormatDate(date) {
      if (!date) return 'N/A';
      try {
        const d = new Date(date);
        if (isNaN(d.getTime())) return 'Invalid Date';
        return d.toLocaleDateString('en-US', {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
          weekday: 'short'
        });
      } catch (e) {
        return 'Invalid Date';
      }
    },
   
    safeFormatTime(time) {
      if (!time) return 'N/A';
     
      if (time.match(/AM|PM/i)) {
        return time;
      }
     
      try {
        let date;
        if (time.includes('T') || time.includes('Z')) {
          date = new Date(time);
        } else {
          date = new Date(`2000-01-01T${time}`);
        }
       
        if (isNaN(date.getTime())) return time;
       
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
      if (hours == null || hours === undefined || isNaN(hours)) return '0h 0m';
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
      return statuses[status] || (status || 'Unknown');
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
/* Enhanced Modal Styles */
.simple-modal-overlay.is-success .simple-modal-content {
  border-top: 4px solid #52c41a;
}
.simple-modal-overlay.is-error .simple-modal-content {
  border-top: 4px solid #f5222d;
}
.modal-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}
.btn-success {
  background: linear-gradient(135deg, #52c41a 0%, #389e0d 100%) !important;
}
.btn-error {
  background: linear-gradient(135deg, #f5222d 0%, #cf1322 100%) !important;
}
/* Loading dots for button */
.loading-dots:after {
  content: '...';
  animation: dots 1.5s steps(4, end) infinite;
}
@keyframes dots {
  0%, 20% { color: rgba(0,0,0,0); text-shadow: .25em 0 0 rgba(0,0,0,0), .5em 0 0 rgba(0,0,0,0); }
  40% { color: white; text-shadow: .25em 0 0 rgba(0,0,0,0), .5em 0 0 rgba(0,0,0,0); }
  60% { text-shadow: .25em 0 0 white, .5em 0 0 rgba(0,0,0,0); }
  80%, 100% { text-shadow: .25em 0 0 white, .5em 0 0 white; }
}
/* Disabled button state */
.btn-mark:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}
.btn-mark:disabled:hover {
  background: #f6ffed !important;
  transform: none !important;
}
.department-overview {
  margin-bottom: 2rem;
}
.department-overview h2 {
  color: #2d3748;
  margin-bottom: 1rem;
  font-size: 1.5rem;
}
.department-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}
.department-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  padding: 1.5rem;
  border-left: 4px solid #667eea;
}
.department-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #f0f0f0;
}
.department-header h3 {
  margin: 0;
  color: #2d3748;
  font-size: 1.1rem;
}
.dept-count {
  background: #f0f3ff;
  color: #667eea;
  padding: 0.3rem 0.8rem;
  border-radius: 16px;
  font-size: 0.8rem;
  font-weight: 600;
}
.dept-metrics {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0.75rem;
}
.dept-metric {
  text-align: center;
  padding: 0.75rem;
  background: #f8f9fa;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}
.dept-value {
  display: block;
  font-size: 1.3rem;
  font-weight: 700;
  color: #4a5568;
}
.dept-metric.present .dept-value { color: #52c41a; }
.dept-metric.absent .dept-value { color: #f5222d; }
.dept-metric.late .dept-value { color: #fa8c16; }
.dept-metric.rate .dept-value { color: #667eea; }
.dept-label {
  display: block;
  font-size: 0.75rem;
  color: #718096;
  margin-top: 0.25rem;
  font-weight: 500;
}
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  flex-wrap: wrap;
  gap: 1rem;
}
.department-filter {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}
.dept-select {
  padding: 0.5rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  background: white;
  font-size: 0.9rem;
  cursor: pointer;
  min-width: 180px;
}
/* Rest of your existing styles remain the same */
.attendance-monitor {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  background: #f8f9fa;
  min-height: 100vh;
  position: relative;
}
/* Modal Styles */
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
/* Page Header */
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
/* Loading and Error States */
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
/* Overview Section */
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
/* Employees Section */
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
  .department-cards {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  }
 
  .section-header {
    flex-direction: column;
    align-items: stretch;
  }
 
  .dept-select {
    width: 100%;
  }
 
  .employees-grid {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  }
}
@media (max-width: 600px) {
  .department-cards {
    grid-template-columns: 1fr;
  }
 
  .dept-metrics {
    grid-template-columns: repeat(4, 1fr);
  }
 
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
 
  .filter-tabs {
    flex-wrap:  wrap;
  }
}
</style>