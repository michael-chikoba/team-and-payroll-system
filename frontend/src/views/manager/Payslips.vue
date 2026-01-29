<template>
  <div class="payslips-view">
    <header class="header">
      <div class="header-left">
        <h1 class="title">{{ pageName }}</h1>
        <p class="subtitle">Castle Holding Zambia - Manage and download your payslips</p>
      </div>
      <!-- Role indicator for manager/admin -->
      <div v-if="isManagerOrAdmin" class="role-badge">
        <i class="icon icon-shield"></i>
        <span>{{ userRoleLabel }} View</span>
      </div>
    </header>
    
    <div class="filters">
      <div class="filter-group">
        <label>Year:</label>
        <select v-model="filterYear" @change="fetchPayslips">
          <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Month:</label>
        <select v-model="filterMonth" @change="fetchPayslips">
          <option value="">All Months</option>
          <option v-for="month in months" :key="month.value" :value="month.value">{{ month.label }}</option>
        </select>
      </div>
      <div class="filter-group">
        <label>Status:</label>
        <select v-model="filterStatus" @change="fetchPayslips">
          <option value="">All</option>
          <option value="generated">Generated</option>
          <option value="paid">Paid</option>
          <option value="draft">Draft</option>
        </select>
      </div>
      <!-- Employee filter for manager/admin -->
      <div v-if="isManagerOrAdmin && employees.length > 0" class="filter-group">
      
      </div>
    </div>
    
    <div class="content">
      <!-- Summary Cards -->
      <div class="summary-cards" v-if="!loading && payslips.length > 0">
        <div class="card">
          <div class="card-icon">
            <i class="icon icon-file"></i>
          </div>
          <h3>Total Payslips</h3>
          <p class="value">{{ payslips.length }}</p>
        </div>
        <div class="card">
          <div class="card-icon">
            <i class="icon icon-money"></i>
          </div>
          <h3>Total Earnings (ZMW)</h3>
          <p class="value">{{ formatCurrency(totalEarnings) }}</p>
        </div>
        <div class="card">
          <div class="card-icon">
            <i class="icon icon-calendar"></i>
          </div>
          <h3>Latest Payslip</h3>
          <p class="value">{{ latestPayslipPeriod }}</p>
        </div>
       
      </div>
      
      <!-- Loading State -->
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Loading payslips...</p>
      </div>
      
      <!-- Error State -->
      <div v-else-if="error" class="error-message">
        {{ error }}
        <button @click="fetchPayslips" class="btn-primary">Retry</button>
      </div>
      
      <!-- Empty State -->
      <div v-else-if="payslips.length === 0" class="empty-state">
        <i class="icon icon-empty"></i>
        <p>No payslips found for the selected filters.</p>
        <button @click="resetFilters" class="btn-primary">Reset Filters</button>
      </div>
      
      <!-- Payslips Table -->
      <div v-else class="table-container">
        <table class="payslips-table">
          <thead>
            <tr>
              <th v-if="isManagerOrAdmin">Employee</th>
              <th>Period</th>
              <th>Gross Pay (ZMW)</th>
              <th>Deductions (ZMW)</th>
              <th>Net Pay (ZMW)</th>
              <th>Status</th>
              <th>Payment Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="payslip in paginatedPayslips" :key="payslip.id">
              <td v-if="isManagerOrAdmin">
                <div class="employee-info">
                  <div class="employee-name">{{ getEmployeeName(payslip) }}</div>
                  <div class="employee-id" v-if="getEmployeeId(payslip)">ID: {{ getEmployeeId(payslip) }}</div>
                </div>
              </td>
              <td><strong>{{ formatPeriodShort(payslip) }}</strong></td>
              <td>{{ formatCurrency(payslip.grossPay || payslip.gross_pay) }}</td>
              <td class="deduction">{{ formatCurrency(payslip.deductions || payslip.total_deductions) }}</td>
              <td class="net-pay"><strong>{{ formatCurrency(payslip.netPay || payslip.net_pay) }}</strong></td>
              <td>
                <span :class="['status-badge', getStatusClass(payslip.status)]">
                  {{ formatStatus(payslip.status) }}
                </span>
              </td>
              <td>{{ formatDate(payslip.payment_date || payslip.paymentDate) }}</td>
              <td>
                <div class="action-buttons">
                  <button
                    @click="viewPayslip(payslip)"
                    class="action-btn view"
                    title="View Details">
                    <i class="icon icon-eye"></i> View
                  </button>
                  <button
                    @click="downloadPayslip(payslip)"
                    class="action-btn download"
                    :class="{ 'downloading': downloading[payslip.id] }"
                    :disabled="downloading[payslip.id]"
                    title="Download PDF">
                    <span v-if="downloading[payslip.id]">
                      <i class="icon icon-spinner"></i>
                    </span>
                    <span v-else>
                      <i class="icon icon-download"></i>
                    </span>
                  </button>
                  <!-- Additional actions for manager/admin -->
                  <button
                    v-if="isManagerOrAdmin && payslip.status === 'draft'"
                    @click="approvePayslip(payslip)"
                    class="action-btn approve"
                    title="Approve Payslip">
                    <i class="icon icon-check"></i>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="pagination" v-if="totalPages > 1">
          <button @click="prevPage" :disabled="currentPage === 1">Previous</button>
          <span>Page {{ currentPage }} of {{ totalPages }}</span>
          <button @click="nextPage" :disabled="currentPage === totalPages">Next</button>
        </div>
      </div>
    </div>
    
    <!-- DESIGNED PAYSLIP DETAIL MODAL -->
    <div v-if="selectedPayslip" class="modal-overlay" @click.self="closeModal">
      <div class="modal-card">
        <div class="modal-header">
          <div class="company-brand">
            <div class="brand-icon"><i class="icon icon-building"></i></div>
            <div>
              <h2>Payslip Details</h2>
              <span class="company-name">Castle Holding Zambia</span>
            </div>
          </div>
          <button @click="closeModal" class="close-btn" title="Close">✕</button>
        </div>
        
        <div class="modal-body payslip-paper">
          <!-- Top Info Grid -->
          <div class="payslip-top-grid">
            <div class="info-block">
              <h4 class="info-label">Employee Details</h4>
              <div class="info-content">
                <p class="emp-name">{{ getEmployeeName(selectedPayslip) }}</p>
                <div class="emp-meta-row">
                   <span>ID: <strong>{{ getEmployeeId(selectedPayslip) }}</strong></span>
                </div>
                <div class="emp-meta-row" v-if="getEmployeePosition(selectedPayslip) !== 'N/A'">
                   <span>{{ getEmployeePosition(selectedPayslip) }}</span>
                </div>
                <div class="emp-meta-row" v-if="getEmployeeDepartment(selectedPayslip) !== 'N/A'">
                   <span>{{ getEmployeeDepartment(selectedPayslip) }}</span>
                </div>
              </div>
            </div>
            
            <div class="info-block right-align">
              <h4 class="info-label">Payslip Summary</h4>
              <div class="summary-list">
                <div class="summary-row">
                  <span class="label">Period:</span>
                  <span class="value period-value">{{ formatPeriod(selectedPayslip) }}</span>
                </div>
                <div class="summary-row">
                  <span class="label">Pay Date:</span>
                  <span class="value">{{ getPaymentDate(selectedPayslip) }}</span>
                </div>
                <div class="summary-row status-row">
                  <span class="label">Status:</span>
                  <span :class="['status-pill', getStatusClass(selectedPayslip.status)]">
                    {{ formatStatus(selectedPayslip.status) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          
          <hr class="divider">
          
          <!-- Financial Split -->
          <div class="financial-split">
            <!-- Earnings Column -->
            <div class="financial-col earnings-col">
              <div class="col-header">
                <i class="icon icon-plus-circle"></i> Earnings
              </div>
              <ul class="line-items">
                <li>
                  <span>Basic Salary</span>
                  <span class="amount">{{ formatCurrency(selectedPayslip.basic_salary || selectedPayslip.basicSalary) }}</span>
                </li>
                <li>
                  <span>House Allowance</span>
                  <span class="amount">{{ formatCurrency(selectedPayslip.house_allowance || selectedPayslip.houseAllowance) }}</span>
                </li>
                <li>
                  <span>Transport Allowance</span>
                  <span class="amount">{{ formatCurrency(selectedPayslip.transport_allowance || selectedPayslip.transportAllowance) }}</span>
                </li>
                <li v-if="(selectedPayslip.overtime_pay || selectedPayslip.overtimePay) > 0">
                  <span>Overtime Pay</span>
                  <span class="amount">{{ formatCurrency(selectedPayslip.overtime_pay || selectedPayslip.overtimePay) }}</span>
                </li>
                <li v-if="(selectedPayslip.other_allowances || selectedPayslip.otherAllowances) > 0">
                  <span>Other Allowances</span>
                  <span class="amount">{{ formatCurrency(selectedPayslip.other_allowances || selectedPayslip.otherAllowances) }}</span>
                </li>
              </ul>
              <div class="col-total">
                <span>Total Earnings</span>
                <span>{{ formatCurrency(selectedPayslip.grossPay || selectedPayslip.gross_pay) }}</span>
              </div>
            </div>
            
            <!-- Deductions Column -->
            <div class="financial-col deductions-col">
              <div class="col-header">
                <i class="icon icon-minus-circle"></i> Deductions
              </div>
              <ul class="line-items">
                <li>
                  <span>NAPSA </span>
                  <span class="amount deduction-text">{{ formatCurrency(selectedPayslip.napsa || selectedPayslip.napsa_deduction) }}</span>
                </li>
                <li>
                  <span>PAYE Tax</span>
                  <span class="amount deduction-text">{{ formatCurrency(selectedPayslip.paye || selectedPayslip.paye_tax) }}</span>
                </li>
                <li>
                  <span>NHIMA </span>
                  <span class="amount deduction-text">{{ formatCurrency(selectedPayslip.nhima || selectedPayslip.nhima_deduction) }}</span>
                </li>
                <li v-if="(selectedPayslip.other_deductions || selectedPayslip.otherDeductions) > 0">
                  <span>Other Deductions</span>
                  <span class="amount deduction-text">{{ formatCurrency(selectedPayslip.other_deductions || selectedPayslip.otherDeductions) }}</span>
                </li>
              </ul>
              <div class="col-total">
                <span>Total Deductions</span>
                <span class="deduction-text">{{ formatCurrency(selectedPayslip.deductions || selectedPayslip.total_deductions) }}</span>
              </div>
            </div>
          </div>
          
          <!-- Net Pay Block -->
          <div class="net-pay-block">
            <div class="net-pay-label">NET PAYABLE</div>
            <div class="net-pay-value">{{ formatCurrency(selectedPayslip.netPay || selectedPayslip.net_pay) }}</div>
          </div>
        </div>
        
        <div class="modal-footer">
          <div class="footer-left">
             <span class="generated-date">Generated on {{ formatDate(selectedPayslip.created_at) }}</span>
          </div>
          <div class="footer-actions">
            <button @click="closeModal" class="btn-secondary">Close</button>
            <button v-if="isManagerOrAdmin && selectedPayslip.status === 'draft'" @click="approvePayslip(selectedPayslip)" class="btn-success">
              <i class="icon icon-check"></i> Approve
            </button>
            <button
              @click="downloadPayslip(selectedPayslip)"
              class="btn-primary"
              :disabled="isDownloading(selectedPayslip.id)">
              <span v-if="isDownloading(selectedPayslip.id)">
                <i class="icon icon-spinner"></i> Downloading...
              </span>
              <span v-else>
                <i class="icon icon-download"></i> Download PDF
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Toast Notification -->
    <div v-if="toast.show" :class="['toast', toast.type]">
      {{ toast.message }}
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

export default {
  name: 'PayslipsView',
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  data() {
    return {
      pageName: 'My Payslips',
      payslips: [],
      employees: [],
      filterYear: new Date().getFullYear(),
      filterMonth: '',
      filterStatus: '',
      filterEmployee: '',
      availableYears: [],
      months: [
        { label: 'January', value: '01' },
        { label: 'February', value: '02' },
        { label: 'March', value: '03' },
        { label: 'April', value: '04' },
        { label: 'May', value: '05' },
        { label: 'June', value: '06' },
        { label: 'July', value: '07' },
        { label: 'August', value: '08' },
        { label: 'September', value: '09' },
        { label: 'October', value: '10' },
        { label: 'November', value: '11' },
        { label: 'December', value: '12' }
      ],
      loading: false,
      downloading: {},
      error: null,
      selectedPayslip: null,
      toast: {
        show: false,
        message: '',
        type: 'success'
      },
      currentPage: 1,
      perPage: 10
    }
  },
  computed: {
    userRole() {
      return this.authStore.user?.role?.toLowerCase() || 'employee'
    },
    
    isManagerOrAdmin() {
      return this.userRole === 'manager' || this.userRole === 'admin'
    },
    
    userRoleLabel() {
      const labels = {
        'admin': 'Admin',
        'manager': 'Manager',
        'employee': 'Employee'
      }
      return labels[this.userRole] || 'Employee'
    },
    
    totalEarnings() {
      return this.payslips.reduce((sum, payslip) => {
        const netPay = payslip.netPay || payslip.net_pay || 0
        return sum + netPay
      }, 0)
    },
    
    latestPayslipPeriod() {
      if (this.payslips.length === 0) return 'N/A'
      return this.formatPeriodShort(this.payslips[0])
    },
    
    totalEmployees() {
      return this.employees.length
    },
    
    paginatedPayslips() {
      const start = (this.currentPage - 1) * this.perPage
      const end = start + this.perPage
      return this.payslips.slice(start, end)
    },
    
    totalPages() {
      return Math.ceil(this.payslips.length / this.perPage)
    },
    
    // Dynamic API base path based on role
    apiBasePath() {
      if (this.userRole === 'manager') {
        return '/api/manager/payslips'
      } else if (this.userRole === 'admin') {
        return '/api/admin/payslips'
      } else {
        return '/api/employee/payslips'
      }
    },
    
    // Employee endpoint for manager/admin
    employeesApiPath() {
      if (this.userRole === 'manager') {
        return '/api/manager/employees'
      } else if (this.userRole === 'admin') {
        return '/api/admin/employees'
      }
      return null
    }
  },
  mounted() {
    this.initializePage()
  },
  methods: {
    initializePage() {
      // Update page name based on role
      if (this.userRole === 'manager') {
        this.pageName = 'My Payslips'
      } else if (this.userRole === 'admin') {
        this.pageName = 'All Payslips'
      }
      
      this.fetchPayslips()
      if (this.isManagerOrAdmin) {
        this.fetchEmployees()
      }
    },
    
    async fetchPayslips() {
      this.loading = true
      this.error = null
      try {
        const params = {
          year: this.filterYear,
          ...(this.filterMonth && { month: this.filterMonth }),
          ...(this.filterStatus && { status: this.filterStatus }),
          ...(this.isManagerOrAdmin && this.filterEmployee && { employee_id: this.filterEmployee })
        }
       
        const response = await axios.get(this.apiBasePath, { params })
        this.payslips = response.data.data || response.data || []
        this.currentPage = 1
       
        // Extract available years from payslips
        if (this.payslips.length > 0) {
          const years = [...new Set(this.payslips.map(p => {
            const dateStr = p.pay_period_start || p.period_start || p.created_at || p.payment_date
            const date = new Date(dateStr)
            return isNaN(date.getTime()) ? new Date().getFullYear() : date.getFullYear()
          }))].sort((a, b) => b - a)
          this.availableYears = years.length > 0 ? years : [new Date().getFullYear()]
        } else {
          this.availableYears = [new Date().getFullYear()]
        }
      } catch (err) {
        console.error('Payslips fetch error:', err)
        this.handleApiError(err)
      } finally {
        this.loading = false
      }
    },
    
    async fetchEmployees() {
      if (!this.employeesApiPath) return
      
      try {
        const response = await axios.get(this.employeesApiPath)
        this.employees = response.data.data || response.data.employees || response.data || []
      } catch (err) {
        console.error('Employees fetch error:', err)
      }
    },
    
    async downloadPayslip(payslip) {
      const payslipId = payslip.id || payslip.payslip_id
      if (!payslipId) {
        this.showToast('Invalid payslip ID', 'error')
        return
      }
      
      this.downloading = { ...this.downloading, [payslipId]: true }
     
      try {
        let response
        try {
          response = await axios.get(`${this.apiBasePath}/${payslipId}/download`, {
            responseType: 'blob',
            headers: { 'Accept': 'application/pdf' }
          })
        } catch (firstError) {
          console.log('Primary endpoint failed, trying alternatives...')
          const endpoints = [
            `/api/payslips/${payslipId}/download`,
            `/api/download-payslip/${payslipId}`,
            `/api/employee/payslips/${payslipId}/download`
          ]
          
          for (const endpoint of endpoints) {
            try {
              response = await axios.get(endpoint, {
                responseType: 'blob',
                headers: { 'Accept': 'application/pdf' }
              })
              break
            } catch (e) { continue }
          }
          if (!response) throw new Error('All download endpoints failed')
        }
       
        const contentType = response.headers['content-type']
        const isPDF = contentType && contentType.includes('pdf')
        const isFile = response.data && response.data.size > 0
        
        if (isPDF || isFile) {
          const blob = new Blob([response.data], { type: 'application/pdf' })
          const url = window.URL.createObjectURL(blob)
          const link = document.createElement('a')
          link.href = url
          
          const period = String(payslip.period || payslipId).replace(/\s+/g, '-')
          const employeeName = this.isManagerOrAdmin ? `-${this.getEmployeeName(payslip).replace(/\s+/g, '-')}` : ''
          const fileName = `payslip-${period}${employeeName}.pdf`.toLowerCase()
          
          link.setAttribute('download', fileName)
          document.body.appendChild(link)
          link.click()
          link.remove()
          window.URL.revokeObjectURL(url)
         
          this.showToast('Payslip downloaded successfully!', 'success')
        } else {
          throw new Error('Invalid file type received')
        }
      } catch (err) {
        console.error('Download error:', err)
        this.showToast('Failed to download payslip. Please try again.', 'error')
      } finally {
        this.downloading = { ...this.downloading, [payslipId]: false }
      }
    },
    
    isDownloading(payslipId) {
      return this.downloading[payslipId] || false
    },
    
    async viewPayslip(payslip) {
      try {
        const payslipId = payslip.id || payslip.payslip_id
        const response = await axios.get(`${this.apiBasePath}/${payslipId}`)
        const detailedPayslip = response.data.data || response.data
        
        this.selectedPayslip = {
          ...payslip,
          ...detailedPayslip,
          id: payslipId 
        }
      } catch (err) {
        console.error('Error fetching payslip details:', err)
        this.selectedPayslip = { ...payslip, id: payslip.id || payslip.payslip_id }
        this.showToast('Showing basic payslip information.', 'warning')
      }
    },
    
    async approvePayslip(payslip) {
      if (!confirm('Are you sure you want to approve this payslip?')) return
      
      try {
        const payslipId = payslip.id || payslip.payslip_id
        await axios.post(`${this.apiBasePath}/${payslipId}/approve`)
        this.showToast('Payslip approved successfully!', 'success')
        this.fetchPayslips()
        if (this.selectedPayslip && this.selectedPayslip.id === payslipId) {
          this.selectedPayslip.status = 'generated'
        }
      } catch (err) {
        this.showToast('Failed to approve payslip.', 'error')
      }
    },
    
    getEmployeeName(payslip) {
      if (payslip.employee?.name) return payslip.employee.name
      if (payslip.employee?.full_name) return payslip.employee.full_name
      if (payslip.employee?.first_name) {
        return `${payslip.employee.first_name} ${payslip.employee.last_name || ''}`.trim()
      }
      return payslip.employee_name || 'Unknown Employee'
    },
    
    getEmployeeId(payslip) {
      return payslip.employee?.employee_id || payslip.employee?.id || 'N/A'
    },
    
    getEmployeeDepartment(payslip) {
      return payslip.employee?.department || 'N/A'
    },
    
    getEmployeePosition(payslip) {
      return payslip.employee?.position || 'N/A'
    },
    
    closeModal() {
      this.selectedPayslip = null
    },
    
    resetFilters() {
      this.filterYear = new Date().getFullYear()
      this.filterMonth = ''
      this.filterStatus = ''
      this.filterEmployee = ''
      this.fetchPayslips()
    },
    
    showToast(message, type = 'success') {
      this.toast = { show: true, message, type }
      setTimeout(() => { this.toast.show = false }, 3000)
    },
    
    handleApiError(err) {
      let errorMsg = 'An unexpected error occurred.'
      if (err.response?.status === 401) {
        this.authStore.clearAuth()
        this.$router.push({ name: 'login' })
      } else {
        errorMsg = err.response?.data?.message || err.message
      }
      this.error = errorMsg
    },
    
    formatCurrency(amount) {
      if (amount === null || amount === undefined) return 'ZMW 0.00'
      const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount
      return new Intl.NumberFormat('en-ZM', {
        style: 'currency',
        currency: 'ZMW',
        minimumFractionDigits: 2
      }).format(numAmount)
    },
    
    formatStatus(status) {
      const statuses = { generated: 'Generated', paid: 'Paid', draft: 'Draft', processing: 'Processing' }
      return statuses[status?.toLowerCase()] || status || 'Unknown'
    },
    
    getStatusClass(status) {
      const map = { generated: 'generated', paid: 'paid', draft: 'draft', processing: 'generated' }
      return map[status?.toLowerCase()] || 'draft'
    },
    
    formatDate(date) {
      if (!date) return 'N/A'
      try {
        const d = new Date(date)
        if (isNaN(d.getTime())) return String(date) 
        return d.toLocaleDateString('en-ZM', { year: 'numeric', month: 'short', day: 'numeric' })
      } catch (e) {
        return 'N/A'
      }
    },
    
    // NEW: Handles parsing JSON period string into a readable range
    formatPeriod(payslip) {
      if (!payslip || !payslip.period) return 'N/A'
      
      let periodData = payslip.period
      
      // 1. Try parsing JSON string
      if (typeof periodData === 'string' && periodData.trim().startsWith('{')) {
        try {
          periodData = JSON.parse(periodData)
        } catch (e) {
          // If parse fails, return the string as is or cleanup
          return periodData
        }
      }
      
      // 2. Format object with start/end
      if (typeof periodData === 'object' && periodData !== null) {
        const start = periodData.start ? this.formatDate(periodData.start) : ''
        const end = periodData.end ? this.formatDate(periodData.end) : ''
        
        if (start && end) return `${start} – ${end}`
        if (start) return start
      }
      
      // 3. Fallback
      return periodData
    },

    // Short version for table display
    formatPeriodShort(payslip) {
       const period = this.formatPeriod(payslip);
       // If it's very long, truncate or logic here, otherwise return formatted
       return period;
    },
    
    getPaymentDate(payslip) {
      // 1. Check root property
      if (payslip.payment_date) return this.formatDate(payslip.payment_date)
      
      // 2. Check inside JSON period string if applicable
      if (typeof payslip.period === 'string' && payslip.period.startsWith('{')) {
        try {
          const parsed = JSON.parse(payslip.period)
          if (parsed.payment_date) return this.formatDate(parsed.payment_date)
        } catch (e) { /* ignore */ }
      }
      
      return 'N/A'
    },
    
    prevPage() { if (this.currentPage > 1) this.currentPage-- },
    nextPage() { if (this.currentPage < this.totalPages) this.currentPage++ }
  }
}
</script>

<style scoped>
.payslips-view {
  --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
  --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  --info-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
  --light: #f8fafc;
  --dark: #1f2937;
  --border: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  --radius: 16px;
  --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background: var(--primary-gradient);
  min-height: 100vh;
  color: var(--text-primary);
}

/* --- Modal & Pop-up Styles --- */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(15, 23, 42, 0.65);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 1rem;
  animation: fadeIn 0.3s ease-out;
}

@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

.modal-card {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 800px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
  animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  overflow: hidden;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.25rem 2rem;
  border-bottom: 1px solid var(--border);
  background: #f8fafc;
}

.company-brand {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.brand-icon {
  width: 40px;
  height: 40px;
  background: var(--primary-gradient);
  color: white;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 700;
  color: var(--text-primary);
}

.company-name {
  font-size: 0.85rem;
  color: var(--text-secondary);
}

.close-btn {
  background: transparent;
  border: none;
  font-size: 1.25rem;
  color: var(--text-secondary);
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 50%;
  transition: var(--transition);
  line-height: 1;
}

.close-btn:hover {
  background: #e2e8f0;
  color: var(--text-primary);
}

.modal-body {
  padding: 0;
  overflow-y: auto;
  flex: 1;
}

.payslip-paper {
  padding: 2rem;
  background: #fff;
}

/* Payslip Layout Header (Updated) */
.payslip-top-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 2rem;
  margin-bottom: 1.5rem;
}

.info-label {
  text-transform: uppercase;
  font-size: 0.75rem;
  color: var(--text-secondary);
  letter-spacing: 0.05em;
  margin-bottom: 0.75rem;
  font-weight: 600;
}

/* Left side: Employee details */
.emp-name {
  font-size: 1.15rem;
  font-weight: 700;
  color: var(--text-primary);
  margin: 0 0 0.25rem 0;
}
.emp-meta-row {
  font-size: 0.9rem;
  color: var(--text-secondary);
  margin-bottom: 0.2rem;
}

/* Right side: Summary Table Layout */
.right-align {
  text-align: right;
}

.summary-list {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  margin-top: 0.25rem;
}

.summary-row {
  display: flex;
  justify-content: flex-end; /* Aligns key-value pairs to the right */
  align-items: center;
  gap: 1rem;
  font-size: 0.95rem;
}

.summary-row .label {
  color: var(--text-secondary);
  font-size: 0.85rem;
}

.summary-row .value {
  font-weight: 600;
  color: var(--text-primary);
}

.period-value {
  white-space: nowrap; /* Prevents date range from wrapping awkwardly */
}

.status-pill {
  padding: 0.2rem 0.6rem;
  border-radius: 4px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
}
.status-pill.generated { background: #dbeafe; color: #1e40af; }
.status-pill.paid { background: #d1fae5; color: #065f46; }
.status-pill.draft { background: #fee2e2; color: #991b1b; }

.divider {
  border: 0;
  border-top: 1px dashed var(--border);
  margin: 1.5rem 0;
}

/* Financial Split Section */
.financial-split {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

.financial-col {
  background: #f8fafc;
  border-radius: 8px;
  padding: 1.25rem;
  border: 1px solid var(--border);
}

.col-header {
  font-weight: 600;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--text-primary);
}

.col-header .icon {
  color: var(--text-secondary);
}

.line-items {
  list-style: none;
  padding: 0;
  margin: 0;
}

.line-items li {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.75rem;
  font-size: 0.9rem;
}

.line-items li span:first-child {
  color: var(--text-secondary);
}

.amount {
  font-family: 'Courier New', monospace; /* Monospace for numbers aligns decimals better */
  font-weight: 600;
  color: var(--text-primary);
}

.deduction-text {
  color: #dc2626;
}

.col-total {
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  font-weight: 700;
}

.net-pay-block {
  margin-top: 2rem;
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  padding: 1.5rem;
  border-radius: 8px;
  text-align: center;
  color: #064e3b;
}

.net-pay-label {
  font-size: 0.85rem;
  text-transform: uppercase;
  letter-spacing: 0.1em;
  margin-bottom: 0.5rem;
  opacity: 0.8;
}

.net-pay-value {
  font-size: 2rem;
  font-weight: 800;
  font-family: 'Inter', sans-serif;
}

/* Modal Footer */
.modal-footer {
  padding: 1.25rem 2rem;
  border-top: 1px solid var(--border);
  background: #fff;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.generated-date {
  font-size: 0.8rem;
  color: var(--text-secondary);
  font-style: italic;
}

.footer-actions {
  display: flex;
  gap: 0.75rem;
}

/* Existing Page Styles (Preserved & Cleaned) */
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  padding: 2rem;
  border-radius: 20px;
  box-shadow: var(--shadow-lg);
  margin-bottom: 2rem;
  position: relative;
}

.header-left { flex: 1; }
.role-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: var(--info-gradient);
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  box-shadow: var(--shadow);
}

.title {
  margin: 0 0 0.5rem 0;
  font-size: 2.5rem;
  font-weight: 700;
  background: var(--primary-gradient);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}
.subtitle { margin: 0; color: var(--text-secondary); font-size: 1.1rem; }

.filters {
  display: flex;
  gap: 1rem;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  padding: 1.5rem;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  margin-bottom: 2rem;
  flex-wrap: wrap;
}
.filter-group { display: flex; flex-direction: column; gap: 0.5rem; min-width: 150px; }
.filter-group select { padding: 0.75rem; border: 1px solid var(--border); border-radius: 8px; }

.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}
.card {
  background: rgba(255, 255, 255, 0.95);
  padding: 2rem;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  text-align: center;
  transition: var(--transition);
  position: relative;
  overflow: hidden;
}
.card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }
.card-icon { font-size: 3rem; margin-bottom: 1rem; color: var(--text-secondary); }
.card h3 { margin: 0 0 0.5rem; font-size: 0.875rem; color: var(--text-secondary); text-transform: uppercase; }
.card .value { margin: 0; font-size: 2.5rem; font-weight: 700; color: var(--text-primary); }

.content {
  background: rgba(255, 255, 255, 0.95);
  padding: 2rem;
  border-radius: var(--radius);
  box-shadow: var(--shadow-lg);
}

.table-container { overflow-x: auto; margin-top: 1rem; }
.payslips-table { width: 100%; border-collapse: collapse; font-size: 0.95rem; background: white; border-radius: 12px; }
.payslips-table th, .payslips-table td { padding: 1.25rem 1rem; text-align: left; border-bottom: 1px solid #f3f4f6; }
.payslips-table th { background: #f8fafc; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; font-size: 0.8rem; }
.payslips-table tr:hover { background: #f8fafc; }

.status-badge { padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
.status-badge.generated { background: var(--warning-gradient); color: white; }
.status-badge.paid { background: var(--success-gradient); color: white; }
.status-badge.draft { background: var(--danger-gradient); color: white; }

.action-buttons { display: flex; gap: 0.5rem; }
.action-btn { padding: 0.5rem 0.8rem; border: none; border-radius: 8px; cursor: pointer; color: white; display: flex; align-items: center; gap: 0.25rem; font-size: 0.8rem; }
.action-btn.view { background: var(--primary-gradient); }
.action-btn.download { background: var(--success-gradient); }
.action-btn.approve { background: var(--info-gradient); }

.pagination { display: flex; justify-content: center; gap: 1rem; margin-top: 2rem; align-items: center; }
.pagination button { padding: 0.5rem 1rem; border-radius: 6px; border: 1px solid var(--border); background: white; cursor: pointer; }
.pagination button:disabled { opacity: 0.5; cursor: not-allowed; }

.toast { position: fixed; bottom: 2rem; right: 2rem; padding: 1rem 1.5rem; border-radius: 8px; color: white; z-index: 2000; animation: slideIn 0.3s; }
.toast.success { background: var(--success-gradient); }
.toast.error { background: var(--danger-gradient); }
.toast.warning { background: var(--warning-gradient); }
@keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }

/* Buttons Generic */
.btn-primary { background: var(--primary-gradient); color: white; padding: 0.6rem 1.2rem; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; }
.btn-success { background: var(--success-gradient); color: white; padding: 0.6rem 1.2rem; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; }
.btn-secondary { background: white; color: var(--text-primary); border: 1px solid var(--border); padding: 0.6rem 1.2rem; border-radius: 8px; cursor: pointer; }

/* Responsive */
@media (max-width: 768px) {
  .modal-card { height: 100vh; max-height: 100vh; border-radius: 0; width: 100%; }
  .payslip-top-grid, .financial-split { grid-template-columns: 1fr; gap: 1rem; }
  .right-align { text-align: left; }
  .summary-row { justify-content: flex-start; }
  .modal-footer { flex-direction: column; gap: 1rem; align-items: stretch; }
  .footer-actions { justify-content: space-between; }
  .header { flex-direction: column; text-align: center; }
  .filters { flex-direction: column; }
}
</style>