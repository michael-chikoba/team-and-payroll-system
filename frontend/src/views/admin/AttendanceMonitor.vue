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
    
    <!-- Country & Business Filters Section -->
    <div class="filter-section">
      <div class="filter-group">
        <label>🌍 Country:</label>
        <select v-model="selectedCountry" @change="onCountryChange" class="filter-select" :disabled="loadingFilters">
          <option value="all">All Countries</option>
          <option v-for="country in availableCountries" :key="country.id" :value="country.id">
            {{ country.name }} ({{ country.employee_count || 0 }})
          </option>
        </select>
      </div>
      
      <div class="filter-group">
        <label>🏢 Business:</label>
        <select v-model="selectedBusiness" @change="onBusinessChange" class="filter-select" 
                :disabled="loadingFilters">
          <option value="all">All Businesses</option>
          <option v-for="business in availableBusinesses" :key="business.id" :value="business.id">
            {{ business.name }} ({{ business.employee_count || 0 }})
          </option>
        </select>
      </div>
      
      <div class="filter-group">
        <label>📅 Date:</label>
        <input
          type="date"
          v-model="selectedDate"
          @change="fetchAllData"
          :max="today"
          class="filter-select"
          :disabled="loading"
        />
      </div>
      
      <!-- Layout Toggle -->
      <div class="filter-group layout-toggle">
        <label>Layout:</label>
        <div class="layout-buttons">
          <button 
            @click="setLayout('grid')" 
            :class="{ active: layout === 'grid' }"
            class="layout-btn"
            title="Grid View"
          >
            <span class="layout-icon">🟩</span> Grid
          </button>
          <button 
            @click="setLayout('table')" 
            :class="{ active: layout === 'table' }"
            class="layout-btn"
            title="Table View"
          >
            <span class="layout-icon">📋</span> Table
          </button>
        </div>
      </div>
    </div>

    <div class="page-header">
      <div>
        <h1>Team Attendance Monitor</h1>
        <p class="subtitle">
          Monitoring team attendance for {{ safeFormatDate(selectedDate) }}
          <span v-if="selectedCountry !== 'all' || selectedBusiness !== 'all' || selectedDepartment !== 'all'">
            • 
            <span v-if="selectedCountry !== 'all'">🌍 {{ getCountryName(selectedCountry) }}</span>
            <span v-if="selectedBusiness !== 'all'"> • 🏢 {{ getBusinessName(selectedBusiness) }}</span>
            <span v-if="selectedDepartment !== 'all'"> • 📁 {{ selectedDepartment }}</span>
          </span>
        </p>
      </div>
      <div class="header-actions">
        <button @click="fetchAllData" class="btn-refresh" :disabled="loading">
          <span v-if="loading">Loading...</span>
          <span v-else>🔄 Refresh</span>
        </button>
      </div>
    </div>
    
    <div v-if="loadingFilters" class="loading">
      <div class="spinner"></div>
      <p>Loading filter options...</p>
    </div>
    
    <div v-else-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Loading attendance data...</p>
    </div>
    
    <div v-else-if="error" class="error-message">
      {{ error }}
      <button @click="retryFetch" class="btn-primary">Retry</button>
    </div>
    
    <div v-else>
      <!-- Country/Business Overview Section -->
      <div class="overview-section" v-if="summaryData">
        <div class="overview-card">
          <div class="overview-header">
            <h3>📊 Attendance Overview</h3>
            <div class="overview-filters">
              <span v-if="selectedCountry !== 'all'" class="filter-badge">
                🌍 {{ getCountryName(selectedCountry) }}
              </span>
              <span v-if="selectedBusiness !== 'all'" class="filter-badge">
                🏢 {{ getBusinessName(selectedBusiness) }}
              </span>
              <span class="layout-badge">
                <span v-if="layout === 'grid'">🟩 Grid View</span>
                <span v-else>📋 Table View</span>
              </span>
            </div>
          </div>
          <div class="overview-metrics">
            <div class="metric total">
              <span class="metric-value">{{ summaryData.total_employees }}</span>
              <span class="metric-label">Total Employees</span>
            </div>
            <div class="metric present">
              <span class="metric-value">{{ summaryData.present_count }}</span>
              <span class="metric-label">Present</span>
            </div>
            <div class="metric absent">
              <span class="metric-value">{{ summaryData.absent_count }}</span>
              <span class="metric-label">Absent</span>
            </div>
            <div class="metric late">
              <span class="metric-value">{{ summaryData.late_count }}</span>
              <span class="metric-label">Late</span>
            </div>
            <div class="metric rate">
              <span class="metric-value">{{ summaryData.attendance_rate }}%</span>
              <span class="metric-label">Attendance Rate</span>
            </div>
          </div>
        </div>
      </div>

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
      
      <!-- Employees Section -->
      <div class="employees-section">
        <div class="section-header">
          <div>
            <h2>Team Members ({{ allCount }})</h2>
            <p class="section-subtitle">
              Showing {{ filteredData.length }} {{ filterStatus === 'all' ? '' : formatStatus(filterStatus).toLowerCase() }} employees
              <span v-if="selectedDepartment !== 'all'">in {{ selectedDepartment }}</span>
            </p>
          </div>
          <div class="section-controls">
            <div class="filter-group">
              <select v-model="selectedDepartment" @change="onDepartmentChange" class="dept-select" :disabled="loading">
                <option value="all">All Departments</option>
                <option v-for="dept in departments" :key="dept" :value="dept">{{ dept }}</option>
              </select>
            </div>
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
        
        <!-- Grid Layout -->
        <div v-if="layout === 'grid' && attendanceData.length > 0" class="employees-grid">
          <div v-for="employee in filteredData" :key="employee.id" class="employee-card">
            <div class="employee-header">
              <div class="employee-avatar">
                {{ getInitials(employee.full_name) }}
              </div>
              <div class="employee-info">
                <h3>{{ employee.full_name }}</h3>
                <p class="employee-id">{{ employee.employee_id }} • {{ employee.department }}</p>
                <p class="employee-position">{{ employee.position }}</p>
                <div class="employee-location" v-if="employee.country_name || employee.business_name">
                  <span v-if="employee.country_name" class="location-badge country">🌍 {{ employee.country_name }}</span>
                  <span v-if="employee.business_name" class="location-badge business">🏢 {{ employee.business_name }}</span>
                </div>
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
        </div>
        
        <!-- Table Layout -->
        <div v-else-if="layout === 'table' && attendanceData.length > 0" class="employees-table-container">
          <div class="table-responsive">
            <table class="employees-table">
              <thead>
                <tr>
                  <th class="employee-col">Employee</th>
                  <th class="department-col">Department</th>
                  <th class="position-col">Position</th>
                  <th class="location-col">Location</th>
                  <th class="status-col">Status</th>
                  <th class="time-col">Clock In</th>
                  <th class="time-col">Clock Out</th>
                  <th class="hours-col">Hours</th>
                  <th class="actions-col">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="employee in filteredData" :key="employee.id">
                  <td class="employee-col">
                    <div class="table-employee-info">
                      <div class="table-avatar">
                        {{ getInitials(employee.full_name) }}
                      </div>
                      <div class="table-employee-details">
                        <div class="table-employee-name">{{ employee.full_name }}</div>
                        <div class="table-employee-id">ID: {{ employee.employee_id }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="department-col">
                    <span class="table-department">{{ employee.department }}</span>
                  </td>
                  <td class="position-col">
                    <span class="table-position">{{ employee.position }}</span>
                  </td>
                  <td class="location-col">
                    <div class="table-location">
                      <span v-if="employee.country_name" class="table-location-badge">
                        🌍 {{ employee.country_name }}
                      </span>
                      <span v-if="employee.business_name" class="table-location-badge">
                        🏢 {{ employee.business_name }}
                      </span>
                    </div>
                  </td>
                  <td class="status-col">
                    <span class="table-status-badge" :class="getStatusClass(employee.status)">
                      {{ formatStatus(employee.status) }}
                    </span>
                  </td>
                  <td class="time-col">
                    <span class="table-time">{{ safeFormatTime(employee.clock_in_time) }}</span>
                  </td>
                  <td class="time-col">
                    <span class="table-time">{{ safeFormatTime(employee.clock_out_time) }}</span>
                  </td>
                  <td class="hours-col">
                    <span class="table-hours">{{ formatHours(employee.hours_worked) }}</span>
                  </td>
                  <td class="actions-col">
                    <div class="table-actions">
                      <button @click="viewHistory(employee)" class="btn-table btn-view" title="View History">
                        📋
                      </button>
                      <button
                        v-if="employee.status === 'absent' && isToday"
                        @click="attemptMarkPresent(employee)"
                        class="btn-table btn-mark"
                        :disabled="markingPresentId === employee.id"
                        title="Mark Present"
                      >
                        <span v-if="markingPresentId === employee.id" class="loading-dots">...</span>
                        <span v-else>✓</span>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          
          <!-- Table Pagination -->
          <div v-if="filteredData.length > 0" class="table-pagination">
            <div class="pagination-info">
              Showing {{ filteredData.length }} of {{ allCount }} employees
            </div>
            <div class="pagination-controls">
              <button @click="exportToCSV" class="btn-export">
                📥 Export CSV
              </button>
            </div>
          </div>
        </div>
        
        <div v-if="filteredData.length === 0" class="empty-state">
          <div class="empty-icon">📭</div>
          <p>No {{ filterStatus === 'all' ? '' : formatStatus(filterStatus).toLowerCase() }} employees found</p>
          <p v-if="filterStatus !== 'all' || selectedDepartment !== 'all' || selectedCountry !== 'all' || selectedBusiness !== 'all'">
            Try changing the filters or date
          </p>
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
      loadingFilters: false,
      error: null,
      retryCount: 0,
      selectedDate: new Date().toISOString().split('T')[0],
      today: new Date().toISOString().split('T')[0],
      filterStatus: 'all',
      markingPresentId: null,
      
      // Layout toggle
      layout: 'grid', // 'grid' or 'table'
      
      // Country/Business filters
      selectedCountry: 'all',
      selectedBusiness: 'all',
      availableCountries: [],
      availableBusinesses: [],
      allBusinesses: [],
      
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
      
      // Summary data
      summaryData: null
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
    
    filteredData() {
      let filtered = this.departmentFilteredData;
      
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
    // Load layout preference from localStorage
    const savedLayout = localStorage.getItem('attendance-layout');
    if (savedLayout && ['grid', 'table'].includes(savedLayout)) {
      this.layout = savedLayout;
    }
    
    this.fetchFilterOptions();
    this.fetchAllData();
  },
 
  methods: {
    setLayout(type) {
      this.layout = type;
      localStorage.setItem('attendance-layout', type);
    },
    
    async fetchFilterOptions() {
      this.loadingFilters = true;
      try {
        const [countriesRes, businessesRes] = await Promise.all([
          axios.get('/api/admin/attendance/countries'),
          axios.get('/api/admin/attendance/businesses')
        ]);
        
        this.availableCountries = countriesRes.data.data || [];
        this.allBusinesses = businessesRes.data.data || [];
        this.availableBusinesses = [...this.allBusinesses];
        
      } catch (err) {
        console.error('Failed to load filter options:', err);
        this.showError('Failed to load filter options. Please refresh the page.', 'Filter Error');
      } finally {
        this.loadingFilters = false;
      }
    },
    
    async onCountryChange() {
      this.selectedBusiness = 'all';
      this.selectedDepartment = 'all';
      
      if (this.selectedCountry === 'all') {
        this.availableBusinesses = [...this.allBusinesses];
      } else {
        await this.fetchBusinessesByCountry();
      }
      
      this.fetchAllData();
    },
    
    async fetchBusinessesByCountry() {
      try {
        const response = await axios.get('/api/admin/attendance/businesses', {
          params: { country_id: this.selectedCountry }
        });
        this.availableBusinesses = response.data.data || [];
      } catch (err) {
        console.error('Failed to fetch businesses for country:', err);
      }
    },
    
    onBusinessChange() {
      this.selectedDepartment = 'all';
      this.fetchAllData();
    },
    
    onDepartmentChange() {
      this.filterStatus = 'all';
      this.fetchAllData();
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
    
    // --- Export to CSV ---
    exportToCSV() {
      if (this.filteredData.length === 0) {
        this.showAlert('No data to export', 'Export Error', 'error');
        return;
      }
      
      const headers = [
        'Employee Name',
        'Employee ID',
        'Department',
        'Position',
        'Country',
        'Business',
        'Status',
        'Clock In',
        'Clock Out',
        'Hours Worked',
        'Date'
      ];
      
      const csvData = this.filteredData.map(employee => [
        employee.full_name,
        employee.employee_id,
        employee.department,
        employee.position,
        employee.country_name || '',
        employee.business_name || '',
        this.formatStatus(employee.status),
        this.safeFormatTime(employee.clock_in_time),
        this.safeFormatTime(employee.clock_out_time),
        this.formatHours(employee.hours_worked),
        this.safeFormatDate(employee.date)
      ]);
      
      const csvContent = [
        headers.join(','),
        ...csvData.map(row => row.map(cell => `"${cell}"`).join(','))
      ].join('\n');
      
      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const url = URL.createObjectURL(blob);
      
      link.setAttribute('href', url);
      link.setAttribute('download', `attendance_${this.selectedDate}.csv`);
      link.style.visibility = 'hidden';
      
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      
      this.showSuccess(`Exported ${this.filteredData.length} records to CSV`, 'Export Successful');
    },
    
    // --- API Methods ---
    async fetchAllData() {
      this.loading = true;
      this.error = null;
      
      try {
        const params = {
          date: this.selectedDate,
          country_id: this.selectedCountry === 'all' ? null : this.selectedCountry,
          business_id: this.selectedBusiness === 'all' ? null : this.selectedBusiness,
          department: this.selectedDepartment === 'all' ? null : this.selectedDepartment
        };

        const response = await axios.get('/api/admin/attendance/status', { params });
        
        if (response.data.success) {
          await this.processAttendanceData(response.data.data, response.data.summary);
        } else {
          throw new Error(response.data.message || 'Failed to fetch attendance data');
        }
        
      } catch (err) {
        console.error('❌ Fetch all data error:', err);
        this.handleApiError(err);
      } finally {
        this.loading = false;
      }
    },
    
    async processAttendanceData(attendanceData, summary) {
      this.summaryData = summary;
      
      this.attendanceData = attendanceData.map(employee => {
        const fullName = `${employee.first_name} ${employee.last_name}`;
        const employeeId = employee.employee_id_number || `EMP${String(employee.employee_id).padStart(4, '0')}`;
        const department = employee.department || 'Unassigned';
        const position = employee.position || 'N/A';
        
        const country = this.availableCountries.find(c => c.id === employee.country_id);
        const business = this.availableBusinesses.find(b => b.id === employee.business_id) || 
                         this.allBusinesses.find(b => b.id === employee.business_id);
        
        return {
          id: employee.employee_id,
          full_name: fullName,
          employee_id: employeeId,
          department: department,
          position: position,
          status: employee.status,
          clock_in_time: employee.clock_in,
          clock_out_time: employee.clock_out,
          hours_worked: employee.total_hours,
          date: employee.date,
          country_id: employee.country_id,
          business_id: employee.business_id,
          country_name: country ? country.name : null,
          business_name: business ? business.name : null
        };
      });
      
      this.calculateDepartmentStats();
      this.departments = [...new Set(this.attendanceData.map(emp => emp.department))].sort();
    },
    
    calculateDepartmentStats() {
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
      
      this.attendanceData.forEach(emp => {
        const dept = emp.department || 'Unassigned';
        if (deptMap.has(dept)) {
          deptMap.get(dept).total++;
        } else {
          deptMap.set(dept, {
            name: dept,
            total: 1,
            present: 0,
            absent: 0,
            late: 0,
            rate: 0
          });
        }
      });
      
      this.attendanceData.forEach(emp => {
        const dept = emp.department || 'Unassigned';
        const deptStat = deptMap.get(dept);
        
        if (['present', 'completed'].includes(emp.status)) {
          deptStat.present++;
        } else if (emp.status === 'late') {
          deptStat.late++;
        } else if (['absent', 'on_leave'].includes(emp.status)) {
          deptStat.absent++;
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
          errorMsg = 'No attendance data found for this date/filters.';
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
      this.summaryData = null;
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
       
        await this.showSuccess(
          `${employee.full_name} has been successfully marked as present for ${this.safeFormatDate(this.selectedDate)}.`,
          'Success!'
        );
       
        await this.fetchAllData();
       
      } catch (err) {
        console.error('❌ Mark present error:', err);
        const errorMessage = err.response?.data?.message || 'Failed to mark employee as present. Please try again.';
       
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
    
    getCountryName(countryId) {
      const country = this.availableCountries.find(c => c.id == countryId);
      return country ? country.name : 'Unknown Country';
    },
    
    getBusinessName(businessId) {
      const business = this.availableBusinesses.find(b => b.id == businessId) ||
                       this.allBusinesses.find(b => b.id == businessId);
      return business ? business.name : 'Unknown Business';
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
.attendance-monitor {
  padding: 2rem;
  max-width: 1600px;
  margin: 0 auto;
  background: #f8f9fa;
  min-height: 100vh;
  position: relative;
}

/* Filter Section */
.filter-section {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 1.5rem;
  padding: 1.5rem;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  align-items: end;
}

.filter-group {
  display: flex;
  flex-direction: column;
}

.filter-group label {
  font-weight: 600;
  margin-bottom: 0.5rem;
  color: #2d3748;
  font-size: 0.9rem;
}

.filter-select {
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  background: white;
  font-size: 0.9rem;
  cursor: pointer;
  transition: border-color 0.2s;
}

.filter-select:focus {
  outline: none;
  border-color: #667eea;
}

.filter-select:disabled {
  background: #f7fafc;
  cursor: not-allowed;
  opacity: 0.7;
}

/* Layout Toggle */
.layout-toggle {
  margin-top: 0.5rem;
}

.layout-buttons {
  display: flex;
  gap: 0.5rem;
  margin-top: 0.25rem;
}

.layout-btn {
  flex: 1;
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  background: white;
  border-radius: 8px;
  font-size: 0.9rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.layout-btn:hover {
  border-color: #667eea;
  background: #f8fafc;
}

.layout-btn.active {
  background: #667eea;
  color: white;
  border-color: #667eea;
}

.layout-btn.active .layout-icon {
  filter: brightness(0) invert(1);
}

.layout-icon {
  font-size: 1rem;
}

.layout-badge {
  padding: 0.5rem 1rem;
  background: #f0f3ff;
  color: #4f46e5;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
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

.simple-modal-content h3 {
  color: #2d3748;
  margin-top: 0;
  margin-bottom: 1rem;
  font-size: 1.25rem;
}

.simple-modal-content p {
  color: #4a5568;
  margin-bottom: 1.5rem;
  line-height: 1.5;
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
  flex: 1;
}

.btn-cancel:hover {
  background: #cbd5e0;
}

.btn-success {
  background: linear-gradient(135deg, #52c41a 0%, #389e0d 100%) !important;
}

.btn-error {
  background: linear-gradient(135deg, #f5222d 0%, #cf1322 100%) !important;
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
  font-weight: 700;
}

.subtitle {
  color: #718096;
  margin: 0;
  font-size: 1rem;
  line-height: 1.5;
}

.header-actions {
  display: flex;
  gap: 0.75rem;
  align-items: center;
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
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
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
  margin-bottom: 1rem;
}

.loading {
  text-align: center;
  padding: 4rem;
  background: white;
  border-radius: 12px;
  margin: 1rem 0;
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

.overview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.overview-header h3 {
  margin: 0;
  color: #2d3748;
  font-size: 1.25rem;
  font-weight: 600;
}

.overview-filters {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.filter-badge {
  padding: 0.5rem 1rem;
  background: #e0e7ff;
  color: #4f46e5;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
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
  transition: transform 0.2s;
}

.metric:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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

/* Department Overview */
.department-overview {
  margin-bottom: 2rem;
}

.department-overview h2 {
  color: #2d3748;
  margin-bottom: 1rem;
  font-size: 1.5rem;
  font-weight: 600;
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
  transition: transform 0.2s;
}

.department-card:hover {
  transform: translateY(-2px);
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
  font-weight: 600;
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

/* Employees Section */
.employees-section {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  padding: 1.5rem;
  margin-top: 1rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.section-header h2 {
  color: #2d3748;
  margin: 0 0 0.25rem 0;
  font-size: 1.5rem;
  font-weight: 600;
}

.section-subtitle {
  color: #718096;
  margin: 0;
  font-size: 0.9rem;
}

.section-controls {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.dept-select {
  padding: 0.5rem 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  background: white;
  font-size: 0.9rem;
  cursor: pointer;
  min-width: 180px;
  transition: border-color 0.2s;
}

.dept-select:focus {
  outline: none;
  border-color: #667eea;
}

.dept-select:disabled {
  background: #f7fafc;
  cursor: not-allowed;
}

/* Filter Tabs */
.filter-tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  border-bottom: 2px solid #e2e8f0;
  padding-bottom: 0.5rem;
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
  font-size: 0.9rem;
}

.tab-btn:hover {
  color: #4a5568;
}

.tab-btn.active {
  color: #667eea;
  border-bottom-color: #667eea;
}

/* Grid Layout */
.employees-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
}

.employee-card {
  background: #f8fafc;
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
  border-bottom: 1px solid #e2e8f0;
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
  font-weight: 600;
}

.employee-id, .employee-position {
  color: #718096;
  font-size: 0.85rem;
  margin: 0;
}

.employee-location {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.5rem;
}

.location-badge {
  padding: 0.2rem 0.6rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  gap: 0.2rem;
}

.location-badge.country {
  background: #f0f9ff;
  color: #0c4a6e;
  border: 1px solid #bae6fd;
}

.location-badge.business {
  background: #f0f9ff;
  color: #0f766e;
  border: 1px solid #99f6e4;
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
  transform: translateY(-1px);
}

.btn-mark {
  background: #f6ffed;
  color: #52c41a;
  border-color: #d9f7be;
}

.btn-mark:hover:not(:disabled) {
  background: #e3ffe5;
  transform: translateY(-1px);
}

.btn-mark:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}

/* Table Layout */
.employees-table-container {
  margin-top: 1rem;
}

.table-responsive {
  overflow-x: auto;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
  background: white;
}

.employees-table {
  width: 100%;
  border-collapse: collapse;
  min-width: 1000px;
}

.employees-table thead {
  background: #f8fafc;
  border-bottom: 2px solid #e2e8f0;
}

.employees-table th {
  padding: 1rem;
  text-align: left;
  color: #4a5568;
  font-weight: 600;
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  white-space: nowrap;
}

.employees-table tbody tr {
  border-bottom: 1px solid #e2e8f0;
  transition: background-color 0.2s;
}

.employees-table tbody tr:hover {
  background-color: #f8fafc;
}

.employees-table td {
  padding: 1rem;
  vertical-align: middle;
}

.table-employee-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.table-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  font-weight: 700;
  flex-shrink: 0;
}

.table-employee-details {
  display: flex;
  flex-direction: column;
}

.table-employee-name {
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 0.1rem;
}

.table-employee-id {
  font-size: 0.75rem;
  color: #718096;
}

.table-department, .table-position {
  font-size: 0.9rem;
  color: #4a5568;
  font-weight: 500;
}

.table-location {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.table-location-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 8px;
  font-size: 0.75rem;
  background: #f0f9ff;
  color: #0c4a6e;
  border: 1px solid #bae6fd;
  display: inline-block;
  white-space: nowrap;
}

.table-status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 700;
  display: inline-block;
  text-transform: uppercase;
}

.table-time, .table-hours {
  font-size: 0.9rem;
  color: #4a5568;
  font-weight: 500;
  font-family: 'SF Mono', 'Monaco', 'Inconsolata', monospace;
}

.table-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-table {
  width: 36px;
  height: 36px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
  transition: all 0.2s;
}

.btn-table.btn-view {
  background: #f0f3ff;
  color: #667eea;
}

.btn-table.btn-view:hover {
  background: #e8ecff;
  transform: translateY(-1px);
}

.btn-table.btn-mark {
  background: #f6ffed;
  color: #52c41a;
  border: 1px solid #d9f7be;
}

.btn-table.btn-mark:hover:not(:disabled) {
  background: #e3ffe5;
  transform: translateY(-1px);
}

.btn-table.btn-mark:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none !important;
}

/* Table Pagination */
.table-pagination {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: white;
  border-top: 1px solid #e2e8f0;
  border-radius: 0 0 8px 8px;
}

.pagination-info {
  color: #718096;
  font-size: 0.9rem;
}

.btn-export {
  padding: 0.5rem 1rem;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-export:hover {
  background: #0da271;
  transform: translateY(-1px);
}

/* Loading dots */
.loading-dots:after {
  content: '...';
  animation: dots 1.5s steps(4, end) infinite;
}

@keyframes dots {
  0%, 20% { color: rgba(0,0,0,0); text-shadow: .25em 0 0 rgba(0,0,0,0), .5em 0 0 rgba(0,0,0,0); }
  40% { color: inherit; text-shadow: .25em 0 0 rgba(0,0,0,0), .5em 0 0 rgba(0,0,0,0); }
  60% { text-shadow: .25em 0 0 currentColor, .5em 0 0 rgba(0,0,0,0); }
  80%, 100% { text-shadow: .25em 0 0 currentColor, .5em 0 0 currentColor; }
}

/* Empty State */
.empty-state {
  grid-column: 1 / -1;
  text-align: center;
  padding: 4rem;
  color: #718096;
  background: #f8fafc;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.05);
  margin-top: 1rem;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-state p {
  margin: 0.5rem 0;
  font-size: 1rem;
}

/* Responsive Design */
@media (max-width: 1400px) {
  .employees-grid {
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  }
}

@media (max-width: 1024px) {
  .attendance-monitor {
    padding: 1rem;
  }
  
  .filter-section {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .department-cards {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  }
  
  .employees-grid {
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  }
}

@media (max-width: 768px) {
  .filter-section {
    grid-template-columns: 1fr;
  }
  
  .page-header {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
  
  .section-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .section-controls {
    width: 100%;
  }
  
  .dept-select {
    width: 100%;
  }
  
  .employees-grid {
    grid-template-columns: 1fr;
  }
  
  .overview-metrics {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .filter-tabs {
    flex-wrap: wrap;
  }
  
  .department-cards {
    grid-template-columns: 1fr;
  }
  
  .table-pagination {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }
}

@media (max-width: 480px) {
  .overview-metrics {
    grid-template-columns: 1fr;
  }
  
  .dept-metrics {
    grid-template-columns: repeat(4, 1fr);
  }
  
  .employee-header {
    flex-direction: column;
    text-align: center;
  }
  
  .attendance-status {
    margin-left: 0;
    margin-top: 0.5rem;
  }
  
  .attendance-actions {
    flex-direction: column;
  }
  
  .modal-actions {
    flex-direction: column;
  }
  
  .btn-cancel, .btn-primary {
    width: 100%;
  }
}
</style>