<template>
  <div class="payslips-view">
    <!-- Background Decorators -->
    <div class="bg-shape shape-1"></div>
    <div class="bg-shape shape-2"></div>

    <div class="container">
      <!-- Header Section -->
      <header class="header">
        <div class="header-content">
          <div class="brand-badge">CHZ</div>
          <div class="header-text">
            <h1 class="title">{{ pageName }}</h1>
            <p class="subtitle">Castle Holding Zambia &bull; Employee Portal</p>
          </div>
        </div>
        <div class="header-actions">
           <button class="btn-icon" @click="fetchPayslips" title="Refresh Data">
             <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
           </button>
        </div>
      </header>
      
      <!-- Filters Section -->
      <div class="controls-bar">
        <div class="filters-wrapper">
          <div class="filter-item">
            <label>Year</label>
            <div class="select-wrapper">
              <select v-model="filterYear" @change="fetchPayslips">
                <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
              </select>
              <svg class="chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </div>
          </div>
          
          <div class="filter-item">
            <label>Month</label>
            <div class="select-wrapper">
              <select v-model="filterMonth" @change="fetchPayslips">
                <option value="">All Months</option>
                <option v-for="month in months" :key="month.value" :value="month.value">{{ month.label }}</option>
              </select>
              <svg class="chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </div>
          </div>
          
          <div class="filter-item">
            <label>Status</label>
            <div class="select-wrapper">
              <select v-model="filterStatus" @change="fetchPayslips">
                <option value="">All Statuses</option>
                <option value="generated">Generated</option>
                <option value="paid">Paid</option>
              </select>
              <svg class="chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>
            </div>
          </div>
        </div>
        
        <button v-if="filterMonth || filterStatus" @click="resetFilters" class="btn-text">
          Clear Filters
        </button>
      </div>

      <!-- Main Content Area -->
      <div class="dashboard-grid">
        
        <!-- Summary Stats -->
        <div class="stats-row" v-if="!loading && payslips.length > 0">
          <div class="stat-card blue">
            <div class="stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
            </div>
            <div class="stat-info">
              <span class="stat-label">Total Payslips</span>
              <span class="stat-value">{{ payslips.length }}</span>
            </div>
          </div>
          
          <div class="stat-card green">
            <div class="stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            </div>
            <div class="stat-info">
              <span class="stat-label">Net Earnings</span>
              <span class="stat-value">{{ formatCurrency(totalEarnings) }}</span>
            </div>
          </div>
          
          <div class="stat-card purple">
            <div class="stat-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </div>
            <div class="stat-info">
              <span class="stat-label">Latest Period</span>
              <span class="stat-value">{{ latestPayslipPeriod }}</span>
            </div>
          </div>
        </div>

        <!-- Content Body -->
        <div class="content-panel">
          <!-- Loading State -->
          <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <p>Retrieving your records...</p>
          </div>

          <!-- Error State -->
          <div v-else-if="error" class="error-state">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <p>{{ error }}</p>
            <button @click="fetchPayslips" class="btn-primary">Try Again</button>
          </div>

          <!-- Empty State -->
          <div v-else-if="payslips.length === 0" class="empty-state">
            <div class="empty-icon">
              <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            </div>
            <h3>No Records Found</h3>
            <p>There are no payslips matching your selected filters.</p>
            <button @click="resetFilters" class="btn-secondary">Reset Filters</button>
          </div>

          <!-- Payslips Table -->
          <div v-else class="table-responsive">
            <table class="modern-table">
              <thead>
                <tr>
                  <th>Period</th>
                  <th class="text-right">Gross (ZMW)</th>
                  <th class="text-right">Deductions</th>
                  <th class="text-right">Net Pay</th>
                  <th>Status</th>
                  <th>Paid Date</th>
                  <th class="text-right">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="payslip in paginatedPayslips" :key="payslip.id">
                  <td class="cell-period">
                    <div class="period-wrapper">
                      <div class="period-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                      </div>
                      <span class="period-text">{{ safeFormatPeriod(payslip.period) }}</span>
                    </div>
                  </td>
                  <td class="text-right font-mono">{{ formatCurrency(payslip.grossPay) }}</td>
                  <td class="text-right font-mono text-danger">{{ formatCurrency(payslip.deductions) }}</td>
                  <td class="text-right font-mono text-success font-bold">{{ formatCurrency(payslip.netPay) }}</td>
                  <td>
                    <span :class="['status-pill', getStatusClass(payslip.status)]">
                      <span class="dot"></span>
                      {{ formatStatus(payslip.status) }}
                    </span>
                  </td>
                  <td class="text-muted">{{ formatDate(payslip.payment_date) }}</td>
                  <td class="text-right">
                    <div class="action-group">
                      <button @click="viewPayslip(payslip)" class="btn-icon-soft" title="View Details">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                      </button>
                      <button 
                        @click="downloadPayslip(payslip)" 
                        class="btn-icon-soft" 
                        :class="{ 'is-loading': downloading[payslip.id] }"
                        :disabled="downloading[payslip.id]"
                        title="Download PDF">
                        <svg v-if="!downloading[payslip.id]" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        <div v-else class="spinner-sm"></div>
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
            
            <div class="pagination-bar" v-if="totalPages > 1">
              <span class="page-info">Showing page {{ currentPage }} of {{ totalPages }}</span>
              <div class="page-controls">
                <button @click="prevPage" :disabled="currentPage === 1" class="btn-page">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                  Prev
                </button>
                <button @click="nextPage" :disabled="currentPage === totalPages" class="btn-page">
                  Next
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modern Payslip Detail Modal -->
    <transition name="modal-fade">
      <div v-if="selectedPayslip" class="modal-backdrop" @click.self="closeModal">
        <div class="modal-panel">
          <div class="modal-header">
            <div class="modal-title-group">
              <h2>Payslip Details</h2>
              <p class="modal-subtitle">{{ safeFormatPeriod(selectedPayslip.period) }}</p>
            </div>
            <button @click="closeModal" class="btn-close">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
          </div>
          
          <div class="modal-scroll-area">
            <!-- Header Info Grid -->
            <div class="info-grid">
              <div class="info-item">
                <span class="label">Payment Date</span>
                <span class="value">{{ formatDate(selectedPayslip.payment_date) }}</span>
              </div>
              <div class="info-item">
                <span class="label">Status</span>
                <span :class="['status-pill', getStatusClass(selectedPayslip.status)]">
                   <span class="dot"></span> {{ formatStatus(selectedPayslip.status) }}
                </span>
              </div>
              <div class="info-item">
                <span class="label">Payslip ID</span>
                <span class="value mono">#{{ selectedPayslip.id }}</span>
              </div>
            </div>

            <div class="split-view">
              <!-- Earnings Column -->
              <div class="detail-column earnings">
                <h3 class="column-header">
                  <span class="icon-circle green">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                  </span>
                  Earnings
                </h3>
                <div class="line-items">
                  <div class="line-item">
                    <span>Basic Salary</span>
                    <span>{{ formatCurrency(selectedPayslip.basic_salary || selectedPayslip.basicSalary || 0) }}</span>
                  </div>
                  <div class="line-item">
                    <span>House Allowance</span>
                    <span>{{ formatCurrency(selectedPayslip.house_allowance || selectedPayslip.houseAllowance || 0) }}</span>
                  </div>
                  <div class="line-item">
                    <span>Transport Allowance</span>
                    <span>{{ formatCurrency(selectedPayslip.transport_allowance || selectedPayslip.transportAllowance || 0) }}</span>
                  </div>
                  <div class="line-item">
                    <span>Other Allowances</span>
                    <span>{{ formatCurrency(selectedPayslip.other_allowances || selectedPayslip.otherAllowances || 0) }}</span>
                  </div>
                  <div class="line-item highlight">
                    <span>Overtime</span>
                    <span>{{ formatCurrency(selectedPayslip.overtime_pay || selectedPayslip.overtimePay || 0) }}</span>
                  </div>
                </div>
                <div class="column-total">
                  <span>Gross Pay</span>
                  <span>{{ formatCurrency(selectedPayslip.grossPay || selectedPayslip.gross_pay || 0) }}</span>
                </div>
              </div>

              <!-- Deductions Column -->
              <div class="detail-column deductions">
                <h3 class="column-header">
                  <span class="icon-circle red">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                  </span>
                  Deductions
                </h3>
                <div class="line-items">
                  <div class="line-item">
                    <span>NAPSA </span>
                    <span>{{ formatCurrency(selectedPayslip.napsa || selectedPayslip.napsa_deduction || 0) }}</span>
                  </div>
                  <div class="line-item">
                    <span>PAYE Tax</span>
                    <span>{{ formatCurrency(selectedPayslip.paye || selectedPayslip.paye_tax || 0) }}</span>
                  </div>
                  <div class="line-item">
                    <span>NHIMA </span>
                    <span>{{ formatCurrency(selectedPayslip.nhima || selectedPayslip.nhima_deduction || 0) }}</span>
                  </div>
                  <div class="line-item">
                    <span>Other Deductions</span>
                    <span>{{ formatCurrency(selectedPayslip.other_deductions || selectedPayslip.otherDeductions || 0) }}</span>
                  </div>
                </div>
                <div class="column-total text-danger">
                  <span>Total Deductions</span>
                  <span>- {{ formatCurrency(selectedPayslip.deductions || selectedPayslip.total_deductions || 0) }}</span>
                </div>
              </div>
            </div>

            <!-- Net Pay Result -->
            <div class="net-pay-card">
              <div class="net-pay-content">
                <span class="label">NET PAYABLE AMOUNT</span>
                <span class="amount">{{ formatCurrency(selectedPayslip.netPay || selectedPayslip.net_pay || 0) }}</span>
              </div>
              <div class="net-pay-bg-icon">ZMW</div>
            </div>
          </div>

          <div class="modal-footer">
            <button @click="closeModal" class="btn-secondary">Close</button>
            <button 
              @click="downloadPayslip(selectedPayslip)" 
              class="btn-primary" 
              :disabled="downloading[selectedPayslip.id]">
              <span v-if="downloading[selectedPayslip.id]" class="flex-center">
                <div class="spinner-sm white"></div> Processing...
              </span>
              <span v-else class="flex-center">
                <svg class="mr-2" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Download PDF
              </span>
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Toast Notification -->
    <transition name="toast-slide">
      <div v-if="toast.show" :class="['toast', toast.type]">
        <div class="toast-icon">
          <svg v-if="toast.type === 'success'" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
        </div>
        <span>{{ toast.message }}</span>
      </div>
    </transition>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

export default {
  name: 'EmployeePayslips',
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  data() {
    return {
      pageName: 'My Payslips',
      payslips: [],
      filterYear: new Date().getFullYear(),
      filterMonth: '',
      filterStatus: '',
      availableYears: [],
      months: [
        { label: 'January', value: '01' }, { label: 'February', value: '02' },
        { label: 'March', value: '03' }, { label: 'April', value: '04' },
        { label: 'May', value: '05' }, { label: 'June', value: '06' },
        { label: 'July', value: '07' }, { label: 'August', value: '08' },
        { label: 'September', value: '09' }, { label: 'October', value: '10' },
        { label: 'November', value: '11' }, { label: 'December', value: '12' }
      ],
      loading: false,
      downloading: {},
      error: null,
      selectedPayslip: null,
      toast: { show: false, message: '', type: 'success' },
      currentPage: 1,
      perPage: 10
    }
  },
  computed: {
    totalEarnings() {
      return this.payslips.reduce((sum, payslip) => {
        const netPay = payslip.netPay || payslip.net_pay || 0
        return sum + netPay
      }, 0)
    },
    latestPayslipPeriod() {
      if (this.payslips.length === 0) return '—'
      return this.safeFormatPeriod(this.payslips[0].period) || '—'
    },
    paginatedPayslips() {
      const start = (this.currentPage - 1) * this.perPage
      const end = start + this.perPage
      return this.payslips.slice(start, end)
    },
    totalPages() {
      return Math.ceil(this.payslips.length / this.perPage)
    }
  },
  mounted() {
    this.fetchPayslips()
  },
  methods: {
    async fetchPayslips() {
      this.loading = true
      this.error = null
      try {
        const params = {
          year: this.filterYear,
          ...(this.filterMonth && { month: this.filterMonth }),
          ...(this.filterStatus && { status: this.filterStatus })
        }
        
        const response = await axios.get('/api/employee/payslips', { params })
        this.payslips = response.data.data || response.data || []
        this.currentPage = 1
       
        if (this.payslips.length > 0) {
          const years = [...new Set(this.payslips.map(p => {
            const period = this.safeFormatPeriod(p.period)
            if (period) {
              const yearMatch = period.match(/\d{4}/)
              if (yearMatch) return parseInt(yearMatch[0])
            }
            if (p.payment_date) return new Date(p.payment_date).getFullYear()
            if (p.created_at) return new Date(p.created_at).getFullYear()
            return new Date().getFullYear()
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
    
    async downloadPayslip(payslip) {
      const payslipId = payslip.id || payslip.payslip_id
      if (!payslipId) return
      
      this.downloading = { ...this.downloading, [payslipId]: true }
     
      try {
        let response
        try {
          response = await axios.get(`/api/employee/payslips/${payslipId}/download`, {
            responseType: 'blob', headers: { 'Accept': 'application/pdf' }
          })
        } catch (firstError) {
          response = await axios.get(`/api/payslips/${payslipId}/download`, {
            responseType: 'blob', headers: { 'Accept': 'application/pdf' }
          })
        }
       
        const contentType = response.headers['content-type']
        const isPDF = contentType && contentType.includes('pdf')
        
        if (isPDF || response.data.size > 0) {
          const blob = new Blob([response.data], { type: 'application/pdf' })
          const url = window.URL.createObjectURL(blob)
          const link = document.createElement('a')
          link.href = url
          const period = this.safeFormatPeriod(payslip.period) || payslipId
          const fileName = `Castle-Payslip-${String(period).replace(/\s+/g, '-').toLowerCase()}.pdf`
          link.setAttribute('download', fileName)
          document.body.appendChild(link)
          link.click()
          link.remove()
          window.URL.revokeObjectURL(url)
          this.showToast('Payslip downloaded successfully!', 'success')
        } else {
            throw new Error('Invalid file received')
        }
      } catch (err) {
        this.showToast('Failed to download payslip.', 'error')
      } finally {
        this.downloading = { ...this.downloading, [payslipId]: false }
      }
    },
    
    async viewPayslip(payslip) {
      try {
        const payslipId = payslip.id || payslip.payslip_id
        const response = await axios.get(`/api/employee/payslips/${payslipId}`)
        const detailedPayslip = response.data.data || response.data
        this.selectedPayslip = { ...payslip, ...detailedPayslip, id: payslipId }
      } catch (err) {
        this.selectedPayslip = { ...payslip, id: payslip.id || payslip.payslip_id }
        this.showToast('Showing preview. Some details may be missing.', 'warning')
      }
    },
    
    closeModal() {
      this.selectedPayslip = null
    },
    
    resetFilters() {
      this.filterYear = new Date().getFullYear()
      this.filterMonth = ''
      this.filterStatus = ''
      this.fetchPayslips()
    },
    
    showToast(message, type = 'success') {
      this.toast = { show: true, message, type }
      setTimeout(() => { this.toast.show = false }, 3000)
    },
    
    handleApiError(err) {
      this.error = 'Unable to load data. Please check your connection.'
    },
    
    formatCurrency(amount) {
      if (amount === null || amount === undefined) return 'ZMW 0.00'
      const numAmount = typeof amount === 'string' ? parseFloat(amount) : amount
      return new Intl.NumberFormat('en-ZM', {
        style: 'currency', currency: 'ZMW', minimumFractionDigits: 2
      }).format(numAmount)
    },
    
    formatStatus(status) {
      const statuses = { generated: 'Generated', paid: 'Paid', draft: 'Draft', pending: 'Pending' }
      return statuses[status?.toLowerCase()] || status || 'Unknown'
    },
    
    getStatusClass(status) {
      const statusClassMap = {
        generated: 'warning', paid: 'success', draft: 'danger', pending: 'danger', completed: 'success'
      }
      return statusClassMap[status?.toLowerCase()] || 'neutral'
    },
    
    formatDate(date) {
      if (!date) return '—'
      try {
        return new Date(date).toLocaleDateString('en-ZM', {
          year: 'numeric', month: 'short', day: 'numeric'
        })
      } catch (error) { return '—' }
    },
    
    safeFormatPeriod(period) {
      if (!period) return ''
      const periodStr = String(period)
      if (/^\d{4}-\d{2}$/.test(periodStr)) {
        const [year, month] = periodStr.split('-')
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
        return `${monthNames[parseInt(month) - 1]} ${year}`
      }
      return periodStr
    },
    
    prevPage() { if (this.currentPage > 1) this.currentPage-- },
    nextPage() { if (this.currentPage < this.totalPages) this.currentPage++ }
  }
}
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@500&display=swap');

.payslips-view {
  /* Modern Theme Colors - Corporate & Clean */
  --color-bg: #f3f4f6;
  --color-surface: #ffffff;
  --color-surface-trans: rgba(255, 255, 255, 0.85);
  --color-primary: #1e3a8a; /* Castle Blue */
  --color-primary-hover: #1e40af;
  --color-accent: #0d9488; /* Teal */
  --color-text-main: #111827;
  --color-text-sub: #6b7280;
  --color-border: #e5e7eb;
  --color-success: #10b981;
  --color-warning: #f59e0b;
  --color-danger: #ef4444;
  --radius-lg: 16px;
  --radius-md: 10px;
  --radius-sm: 6px;
  --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
  --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
  --shadow-lg: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
  
  font-family: 'Inter', sans-serif;
  background-color: var(--color-bg);
  min-height: 100vh;
  position: relative;
  overflow-x: hidden;
  color: var(--color-text-main);
}

/* Background Decorators */
.bg-shape {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  z-index: 0;
  opacity: 0.5;
}
.shape-1 { top: -100px; right: -100px; width: 400px; height: 400px; background: rgba(30, 58, 138, 0.15); }
.shape-2 { bottom: -100px; left: -100px; width: 300px; height: 300px; background: rgba(13, 148, 136, 0.15); }

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
  position: relative;
  z-index: 1;
}

/* Header */
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2.5rem;
}
.header-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}
.brand-badge {
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, var(--color-primary), var(--color-accent));
  color: white;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 1.2rem;
  box-shadow: 0 10px 15px -3px rgba(30, 58, 138, 0.3);
}
.title {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--color-text-main);
  letter-spacing: -0.025em;
  margin: 0;
}
.subtitle {
  color: var(--color-text-sub);
  margin: 0;
  font-size: 0.9rem;
}

/* Filters & Controls */
.controls-bar {
  background: var(--color-surface-trans);
  backdrop-filter: blur(12px);
  padding: 1rem;
  border-radius: var(--radius-lg);
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  border: 1px solid white;
  box-shadow: var(--shadow-sm);
  flex-wrap: wrap;
  gap: 1rem;
}
.filters-wrapper {
  display: flex;
  gap: 1.5rem;
  flex-wrap: wrap;
}
.filter-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}
.filter-item label {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--color-text-sub);
}
.select-wrapper {
  position: relative;
  min-width: 140px;
}
select {
  width: 100%;
  appearance: none;
  background: var(--color-bg);
  border: 1px solid var(--color-border);
  padding: 0.5rem 2rem 0.5rem 1rem;
  border-radius: var(--radius-sm);
  font-family: inherit;
  font-size: 0.9rem;
  color: var(--color-text-main);
  cursor: pointer;
  transition: all 0.2s;
}
select:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
}
.chevron {
  position: absolute;
  right: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--color-text-sub);
  pointer-events: none;
}

/* Stats Cards */
.stats-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}
.stat-card {
  background: var(--color-surface);
  padding: 1.5rem;
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  gap: 1.25rem;
  box-shadow: var(--shadow-sm);
  border: 1px solid white;
  transition: transform 0.2s, box-shadow 0.2s;
}
.stat-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-md);
}
.stat-icon {
  width: 50px;
  height: 50px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.stat-card.blue .stat-icon { background: #eff6ff; color: var(--color-primary); }
.stat-card.green .stat-icon { background: #ecfdf5; color: var(--color-success); }
.stat-card.purple .stat-icon { background: #f5f3ff; color: #7c3aed; }

.stat-info { display: flex; flex-direction: column; }
.stat-label { font-size: 0.85rem; color: var(--color-text-sub); font-weight: 500; }
.stat-value { font-size: 1.5rem; font-weight: 700; color: var(--color-text-main); letter-spacing: -0.02em; }

/* Table Section */
.content-panel {
  background: var(--color-surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-md);
  padding: 1.5rem;
  border: 1px solid rgba(255,255,255,0.6);
  min-height: 400px;
}
.table-responsive {
  width: 100%;
  overflow-x: auto;
}
.modern-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0 0.5rem;
  font-size: 0.95rem;
}
.modern-table th {
  padding: 0 1rem 0.5rem 1rem;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.75rem;
  color: var(--color-text-sub);
  letter-spacing: 0.05em;
  text-align: left;
}
.modern-table td {
  padding: 1rem;
  background: white;
  border-top: 1px solid var(--color-border);
  border-bottom: 1px solid var(--color-border);
}
.modern-table tr td:first-child {
  border-left: 1px solid var(--color-border);
  border-top-left-radius: var(--radius-md);
  border-bottom-left-radius: var(--radius-md);
}
.modern-table tr td:last-child {
  border-right: 1px solid var(--color-border);
  border-top-right-radius: var(--radius-md);
  border-bottom-right-radius: var(--radius-md);
}
.modern-table tr {
  transition: transform 0.2s;
}
.modern-table tr:hover td {
  background: #f9fafb;
}

/* Table Content Styling */
.period-wrapper { display: flex; align-items: center; gap: 0.75rem; }
.period-icon { color: var(--color-primary); }
.period-text { font-weight: 600; color: var(--color-text-main); }
.font-mono { font-family: 'JetBrains Mono', monospace; font-size: 0.9rem; }
.text-right { text-align: right; }
.text-muted { color: var(--color-text-sub); font-size: 0.85rem; }
.text-danger { color: var(--color-danger); }
.text-success { color: var(--color-success); }
.font-bold { font-weight: 700; }

/* Status Pills */
.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
}
.dot { width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.status-pill.success { background: #ecfdf5; color: #059669; }
.status-pill.warning { background: #fffbeb; color: #d97706; }
.status-pill.danger { background: #fef2f2; color: #dc2626; }
.status-pill.neutral { background: #f3f4f6; color: #6b7280; }

/* Buttons & Actions */
.action-group {
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}
.btn-primary {
  background: var(--color-primary);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  box-shadow: 0 4px 6px -1px rgba(30, 58, 138, 0.4);
}
.btn-primary:hover { background: var(--color-primary-hover); transform: translateY(-1px); }
.btn-primary:disabled { opacity: 0.7; cursor: not-allowed; }

.btn-secondary {
  background: white;
  border: 1px solid var(--color-border);
  color: var(--color-text-main);
  padding: 0.75rem 1.5rem;
  border-radius: var(--radius-md);
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-secondary:hover { background: #f9fafb; border-color: var(--color-text-sub); }

.btn-icon, .btn-icon-soft {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-icon { background: white; color: var(--color-text-main); box-shadow: var(--shadow-sm); }
.btn-icon:hover { color: var(--color-primary); }
.btn-icon-soft { background: transparent; color: var(--color-text-sub); }
.btn-icon-soft:hover { background: #f3f4f6; color: var(--color-primary); }
.btn-text { background: none; border: none; color: var(--color-primary); font-weight: 600; cursor: pointer; font-size: 0.9rem; }
.btn-text:hover { text-decoration: underline; }

/* Pagination */
.pagination-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1.5rem;
  border-top: 1px solid var(--color-border);
  margin-top: 1rem;
}
.page-info { color: var(--color-text-sub); font-size: 0.85rem; }
.page-controls { display: flex; gap: 0.5rem; }
.btn-page {
  display: flex; align-items: center; gap: 0.25rem;
  padding: 0.5rem 1rem; border: 1px solid var(--color-border);
  background: white; border-radius: var(--radius-sm);
  cursor: pointer; font-size: 0.85rem; color: var(--color-text-main);
}
.btn-page:hover:not(:disabled) { background: #f9fafb; }
.btn-page:disabled { opacity: 0.5; cursor: not-allowed; }

/* Empty & Loading States */
.empty-state, .loading-state, .error-state {
  text-align: center;
  padding: 4rem 1rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}
.empty-icon { color: var(--color-border); margin-bottom: 0.5rem; }
.empty-state h3 { margin: 0; font-size: 1.1rem; }
.empty-state p { color: var(--color-text-sub); max-width: 300px; margin: 0; }
.spinner {
  width: 40px; height: 40px; border: 3px solid #f3f4f6;
  border-top: 3px solid var(--color-primary); border-radius: 50%;
  animation: spin 1s linear infinite;
}
.spinner-sm {
  width: 18px; height: 18px; border: 2px solid rgba(0,0,0,0.1);
  border-top: 2px solid currentColor; border-radius: 50%;
  animation: spin 1s linear infinite;
}
.spinner-sm.white { border-color: rgba(255,255,255,0.2); border-top-color: white; }

/* Modal Styles */
.modal-backdrop {
  position: fixed; inset: 0;
  background: rgba(17, 24, 39, 0.7);
  backdrop-filter: blur(4px);
  z-index: 100;
  display: flex; justify-content: center; align-items: center;
  padding: 1rem;
}
.modal-panel {
  background: white;
  width: 100%; max-width: 800px;
  max-height: 90vh;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  display: flex; flex-direction: column;
  overflow: hidden;
}
.modal-header {
  padding: 1.5rem 2rem;
  border-bottom: 1px solid var(--color-border);
  display: flex; justify-content: space-between; align-items: flex-start;
  background: #f8fafc;
}
.modal-title-group h2 { margin: 0; font-size: 1.25rem; font-weight: 700; color: var(--color-primary); }
.modal-subtitle { margin: 0; color: var(--color-text-sub); font-size: 0.9rem; }
.btn-close { background: none; border: none; cursor: pointer; color: var(--color-text-sub); }
.btn-close:hover { color: var(--color-danger); }

.modal-scroll-area { overflow-y: auto; padding: 2rem; }
.info-grid {
  display: grid; grid-template-columns: repeat(3, 1fr);
  background: #f8fafc; padding: 1rem; border-radius: var(--radius-md);
  margin-bottom: 2rem;
}
.info-item { display: flex; flex-direction: column; gap: 0.25rem; }
.info-item .label { text-transform: uppercase; font-size: 0.7rem; color: var(--color-text-sub); letter-spacing: 0.05em; font-weight: 600; }
.info-item .value { font-weight: 600; font-size: 0.95rem; }
.info-item .mono { font-family: 'JetBrains Mono', monospace; font-size: 0.85rem; }

.split-view { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 2rem; }
.column-header {
  display: flex; align-items: center; gap: 0.5rem;
  font-size: 1rem; border-bottom: 2px solid #f3f4f6;
  padding-bottom: 0.75rem; margin-bottom: 1rem;
}
.icon-circle { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
.icon-circle.green { background: #ecfdf5; color: #059669; }
.icon-circle.red { background: #fef2f2; color: #dc2626; }

.line-items { display: flex; flex-direction: column; gap: 0.5rem; }
.line-item { display: flex; justify-content: space-between; font-size: 0.9rem; padding: 0.5rem 0.75rem; border-radius: 6px; }
.line-item:hover { background: #f9fafb; }
.line-item.highlight { background: #fffbeb; font-weight: 500; }
.column-total { display: flex; justify-content: space-between; font-weight: 700; border-top: 1px solid var(--color-border); padding-top: 1rem; margin-top: 0.5rem; padding-left: 0.75rem; padding-right: 0.75rem; }

.net-pay-card {
  background: linear-gradient(135deg, var(--color-primary), #3b82f6);
  color: white; padding: 1.5rem 2rem;
  border-radius: var(--radius-lg);
  display: flex; justify-content: space-between; align-items: center;
  position: relative; overflow: hidden;
}
.net-pay-content { display: flex; flex-direction: column; position: relative; z-index: 1; }
.net-pay-content .label { font-size: 0.75rem; opacity: 0.9; letter-spacing: 0.1em; margin-bottom: 0.25rem; font-weight: 600; }
.net-pay-content .amount { font-size: 2rem; font-weight: 800; }
.net-pay-bg-icon { position: absolute; right: -10px; bottom: -20px; font-size: 5rem; font-weight: 900; opacity: 0.1; transform: rotate(-10deg); }

.modal-footer { padding: 1.5rem 2rem; border-top: 1px solid var(--color-border); display: flex; justify-content: flex-end; gap: 1rem; background: #f9fafb; }

/* Toast */
.toast {
  position: fixed; bottom: 2rem; right: 2rem;
  background: white; padding: 1rem 1.5rem;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-lg);
  display: flex; align-items: center; gap: 0.75rem;
  z-index: 200; font-weight: 500;
  border-left: 4px solid var(--color-success);
}
.toast.error { border-left-color: var(--color-danger); }
.toast.warning { border-left-color: var(--color-warning); }
.toast-slide-enter-active, .toast-slide-leave-active { transition: all 0.3s ease; }
.toast-slide-enter-from, .toast-slide-leave-to { opacity: 0; transform: translateY(20px); }

/* Utility */
.mr-2 { margin-right: 0.5rem; }
.flex-center { display: flex; align-items: center; }

/* Animations */
@keyframes spin { to { transform: rotate(360deg); } }
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.2s; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }

/* Mobile Responsive */
@media (max-width: 768px) {
  .header { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .header-actions { position: absolute; top: 2rem; right: 2rem; }
  .filters-wrapper { flex-direction: column; width: 100%; }
  .filter-item, .select-wrapper, select { width: 100%; }
  .split-view { grid-template-columns: 1fr; }
  .info-grid { grid-template-columns: 1fr; }
  
  /* Mobile Cards for Table */
  .modern-table thead { display: none; }
  .modern-table, .modern-table tbody, .modern-table tr, .modern-table td { display: block; width: 100%; }
  .modern-table tr { margin-bottom: 1.5rem; border: 1px solid var(--color-border); border-radius: var(--radius-md); overflow: hidden; }
  .modern-table td { padding: 0.75rem 1rem; border: none; border-bottom: 1px solid #f3f4f6; display: flex; justify-content: space-between; align-items: center; }
  .modern-table td:last-child { border-bottom: none; background: #f9fafb; padding: 1rem; justify-content: flex-end; }
  .modern-table td::before { content: attr(data-label); font-weight: 600; font-size: 0.8rem; color: var(--color-text-sub); }
  
  /* Specifically hide cell borders defined in desktop view */
  .modern-table tr td:first-child, .modern-table tr td:last-child { border: none; border-bottom: 1px solid #f3f4f6; border-radius: 0; }
  .modern-table tr td:last-child { border-bottom: none; }
}
</style>