<template>
  <div class="payslips-view">

    <!-- ── Header Card ─────────────────────────────── -->
    <div class="dashboard-header-card">
      <div class="header-card-accent"></div>
      <div class="user-greeting">
        <div class="avatar-section">
          <div class="avatar">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <line x1="12" y1="1" x2="12" y2="23"></line>
              <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
          </div>
          <div class="user-info">
            <h1 class="greeting">{{ pageName }}</h1>
            <p class="subtitle">Employee Payslips</p>
            <div class="role-meta">
              <span class="role-badge">Employee View</span>
            </div>
          </div>
        </div>

        <div class="header-actions">
          <button @click="fetchPayslips" class="btn-outline">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6"></path><path d="M1 20v-6h6"></path><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
            Refresh
          </button>
          <button v-if="filterMonth || filterStatus" @click="resetFilters" class="btn-outline warning">
            Clear Filters
          </button>
        </div>
      </div>
    </div>

    <div class="dashboard-content">

      <!-- ── Metrics ──────────────────────────────── -->
      <div class="metrics-section" v-if="!loading && payslips.length > 0">
        <h2>Summary</h2>
        <div class="metrics-grid">
          <div class="metric-card" style="--accent:#6366f1;">
            <div class="metric-icon-wrap" style="background:rgba(99,102,241,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            </div>
            <div class="metric-value">{{ payslips.length }}</div>
            <div class="metric-label">Total Payslips</div>
          </div>

          <div class="metric-card" style="--accent:#10b981;">
            <div class="metric-icon-wrap" style="background:rgba(16,185,129,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
            </div>
            <div class="metric-value">{{ formatCurrencyShort(totalEarnings) }}</div>
            <div class="metric-label">Net Earnings</div>
          </div>

          <div class="metric-card" style="--accent:#f59e0b;">
            <div class="metric-icon-wrap" style="background:rgba(245,158,11,0.1);">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </div>
            <div class="metric-value">{{ latestPayslipPeriod }}</div>
            <div class="metric-label">Latest Period</div>
          </div>
        </div>
      </div>

      <!-- ── Filters + Table ──────────────────────── -->
      <div class="table-section">
        <!-- Controls Bar -->
        <div class="controls-bar">
          <div class="filters-row">
            <div class="filter-group">
              <label>Year</label>
              <select v-model="filterYear" @change="fetchPayslips" class="filter-select">
                <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
              </select>
            </div>
            <div class="filter-group">
              <label>Month</label>
              <select v-model="filterMonth" @change="fetchPayslips" class="filter-select">
                <option value="">All Months</option>
                <option v-for="month in months" :key="month.value" :value="month.value">{{ month.label }}</option>
              </select>
            </div>
            <div class="filter-group">
              <label>Status</label>
              <select v-model="filterStatus" @change="fetchPayslips" class="filter-select">
                <option value="">All Statuses</option>
                <option value="generated">Generated</option>
                <option value="paid">Paid</option>
              </select>
            </div>
          </div>
          <span class="records-count" v-if="!loading">{{ payslips.length }} record{{ payslips.length !== 1 ? 's' : '' }}</span>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="empty-state">
          <div class="spinner"></div>
          <p>Retrieving your records...</p>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="1.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
          <p>{{ error }}</p>
          <button @click="fetchPayslips" class="btn-primary">Try Again</button>
        </div>

        <!-- Empty -->
        <div v-else-if="payslips.length === 0" class="empty-state">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
          <p>No payslips found matching your selected filters.</p>
          <button @click="resetFilters" class="btn-secondary">Reset Filters</button>
        </div>

        <!-- Table -->
        <div v-else class="table-container">
          <div class="list-header">
            <div class="col-period">Period</div>
            <div class="col-amount text-right">Gross</div>
            <div class="col-amount text-right">Deductions</div>
            <div class="col-amount text-right">Net Pay</div>
            <div class="col-status">Status</div>
            <div class="col-date">Paid Date</div>
            <div class="col-actions text-right">Actions</div>
          </div>

          <div v-for="payslip in paginatedPayslips" :key="payslip.id" class="list-row" @click="viewPayslip(payslip)">
            <div class="col-period">
              <span class="period-text">{{ safeFormatPeriod(payslip.period) }}</span>
            </div>
            <div class="col-amount text-right font-mono">{{ formatCurrency(payslip.grossPay) }}</div>
            <div class="col-amount text-right font-mono text-danger">{{ formatCurrency(payslip.deductions) }}</div>
            <div class="col-amount text-right font-mono text-success font-bold">{{ formatCurrency(payslip.netPay) }}</div>
            <div class="col-status">
              <span :class="['status-badge', getStatusClass(payslip.status)]">
                <span class="dot"></span>{{ formatStatus(payslip.status) }}
              </span>
            </div>
            <div class="col-date text-muted">{{ formatDate(payslip.payment_date) }}</div>
            <div class="col-actions" @click.stop>
              <div class="action-group">
                <button @click="viewPayslip(payslip)" class="action-btn" title="View Details">
                  <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
                <button
                  @click="downloadPayslip(payslip)"
                  class="action-btn"
                  :class="{ loading: downloading[payslip.id] }"
                  :disabled="downloading[payslip.id]"
                  title="Download PDF">
                  <div v-if="downloading[payslip.id]" class="spinner-sm"></div>
                  <svg v-else xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="pagination-bar">
          <span class="pagination-info">Page <strong>{{ currentPage }}</strong> of <strong>{{ totalPages }}</strong></span>
          <div class="pagination-controls">
            <button @click="prevPage" :disabled="currentPage === 1" class="page-btn">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
              Prev
            </button>
            <button @click="nextPage" :disabled="currentPage === totalPages" class="page-btn">
              Next
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Payslip Detail Modal ─────────────────────── -->
    <transition name="modal-fade">
      <div v-if="selectedPayslip" class="modal-overlay" @click.self="closeModal">
        <div class="modal-container">
          <div class="modal-header">
            <div class="modal-title-wrap">
              <div class="modal-avatar">ZMW</div>
              <div>
                <h3 class="modal-name">Payslip Details</h3>
                <p class="modal-position">Castle Holding Zambia · {{ safeFormatPeriod(selectedPayslip.period) }}</p>
              </div>
            </div>
            <button @click="closeModal" class="modal-close">
              <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
          </div>

          <div class="modal-body">
            <!-- Quick Info Row -->
            <div class="modal-stats">
              <div class="modal-stat">
                <small>Payment Date</small>
                <strong>{{ formatDate(selectedPayslip.payment_date) }}</strong>
              </div>
              <div class="modal-stat">
                <small>Status</small>
                <span :class="['status-badge', getStatusClass(selectedPayslip.status)]">
                  <span class="dot"></span>{{ formatStatus(selectedPayslip.status) }}
                </span>
              </div>
              <div class="modal-stat">
                <small>Payslip ID</small>
                <strong class="mono">#{{ selectedPayslip.id }}</strong>
              </div>
            </div>

            <!-- Earnings / Deductions Split -->
            <div class="split-view">
              <div class="detail-column">
                <h5 class="modal-section-title">
                  <span class="col-dot green"></span> Earnings
                </h5>
                <div class="line-items">
                  <div class="line-item"><span>Basic Salary</span><span>{{ formatCurrency(selectedPayslip.basic_salary || selectedPayslip.basicSalary || 0) }}</span></div>
                  <div class="line-item"><span>House Allowance</span><span>{{ formatCurrency(selectedPayslip.house_allowance || selectedPayslip.houseAllowance || 0) }}</span></div>
                  <div class="line-item"><span>Transport Allowance</span><span>{{ formatCurrency(selectedPayslip.transport_allowance || selectedPayslip.transportAllowance || 0) }}</span></div>
                  <div class="line-item"><span>Other Allowances</span><span>{{ formatCurrency(selectedPayslip.other_allowances || selectedPayslip.otherAllowances || 0) }}</span></div>
                  <div class="line-item"><span>Overtime</span><span>{{ formatCurrency(selectedPayslip.overtime_pay || selectedPayslip.overtimePay || 0) }}</span></div>
                </div>
                <div class="col-total">
                  <span>Gross Pay</span>
                  <span class="text-success">{{ formatCurrency(selectedPayslip.grossPay || selectedPayslip.gross_pay || 0) }}</span>
                </div>
              </div>

              <div class="detail-column">
                <h5 class="modal-section-title">
                  <span class="col-dot red"></span> Deductions
                </h5>
                <div class="line-items">
                  <div class="line-item"><span>NAPSA</span><span class="text-danger">{{ formatCurrency(selectedPayslip.napsa || selectedPayslip.napsa_deduction || 0) }}</span></div>
                  <div class="line-item"><span>PAYE Tax</span><span class="text-danger">{{ formatCurrency(selectedPayslip.paye || selectedPayslip.paye_tax || 0) }}</span></div>
                  <div class="line-item"><span>NHIMA</span><span class="text-danger">{{ formatCurrency(selectedPayslip.nhima || selectedPayslip.nhima_deduction || 0) }}</span></div>
                  <div class="line-item"><span>Other Deductions</span><span class="text-danger">{{ formatCurrency(selectedPayslip.other_deductions || selectedPayslip.otherDeductions || 0) }}</span></div>
                </div>
                <div class="col-total">
                  <span>Total Deductions</span>
                  <span class="text-danger">- {{ formatCurrency(selectedPayslip.deductions || selectedPayslip.total_deductions || 0) }}</span>
                </div>
              </div>
            </div>

            <!-- Net Pay -->
            <div class="net-pay-card">
              <div>
                <div class="net-label">NET PAYABLE AMOUNT</div>
                <div class="net-amount">{{ formatCurrency(selectedPayslip.netPay || selectedPayslip.net_pay || 0) }}</div>
              </div>
              <div class="net-bg">ZMW</div>
            </div>
          </div>

          <div class="modal-footer">
            <button @click="closeModal" class="btn-secondary">Close</button>
            <button @click="downloadPayslip(selectedPayslip)" class="btn-primary" :disabled="downloading[selectedPayslip.id]">
              <div v-if="downloading[selectedPayslip.id]" class="spinner-sm white" style="margin-right:0.4rem"></div>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:0.4rem"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
              {{ downloading[selectedPayslip.id] ? 'Processing...' : 'Download PDF' }}
            </button>
          </div>
        </div>
      </div>
    </transition>

    <!-- ── Toast ─────────────────────────────────────── -->
    <transition name="toast-slide">
      <div v-if="toast.show" :class="['toast', toast.type]">
        <svg v-if="toast.type === 'success'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
        <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
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
      availableYears: [new Date().getFullYear()],
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
      return this.payslips.reduce((sum, p) => sum + (p.netPay || p.net_pay || 0), 0)
    },
    latestPayslipPeriod() {
      return this.payslips.length ? this.safeFormatPeriod(this.payslips[0].period) || '—' : '—'
    },
    paginatedPayslips() {
      const start = (this.currentPage - 1) * this.perPage
      return this.payslips.slice(start, start + this.perPage)
    },
    totalPages() {
      return Math.ceil(this.payslips.length / this.perPage)
    }
  },
  mounted() { this.fetchPayslips() },
  methods: {
    async fetchPayslips() {
      this.loading = true; this.error = null
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
            if (period) { const m = period.match(/\d{4}/); if (m) return parseInt(m[0]) }
            if (p.payment_date) return new Date(p.payment_date).getFullYear()
            return new Date().getFullYear()
          }))].sort((a, b) => b - a)
          this.availableYears = years.length ? years : [new Date().getFullYear()]
        }
      } catch (err) { this.handleApiError(err) } finally { this.loading = false }
    },

    async downloadPayslip(payslip) {
      const id = payslip.id || payslip.payslip_id
      if (!id) return
      this.downloading = { ...this.downloading, [id]: true }
      try {
        let response
        try {
          response = await axios.get(`/api/employee/payslips/${id}/download`, { responseType: 'blob', headers: { Accept: 'application/pdf' } })
        } catch {
          response = await axios.get(`/api/payslips/${id}/download`, { responseType: 'blob', headers: { Accept: 'application/pdf' } })
        }
        const isPDF = response.headers['content-type']?.includes('pdf') || response.data.size > 0
        if (isPDF) {
          const blob = new Blob([response.data], { type: 'application/pdf' })
          const url = window.URL.createObjectURL(blob)
          const link = document.createElement('a')
          link.href = url
          link.setAttribute('download', `Castle-Payslip-${String(this.safeFormatPeriod(payslip.period) || id).replace(/\s+/g, '-').toLowerCase()}.pdf`)
          document.body.appendChild(link); link.click(); link.remove()
          window.URL.revokeObjectURL(url)
          this.showToast('Payslip downloaded successfully!', 'success')
        } else throw new Error('Invalid file received')
      } catch { this.showToast('Failed to download payslip.', 'error') }
      finally { this.downloading = { ...this.downloading, [id]: false } }
    },

    async viewPayslip(payslip) {
      try {
        const id = payslip.id || payslip.payslip_id
        const r = await axios.get(`/api/employee/payslips/${id}`)
        this.selectedPayslip = { ...payslip, ...(r.data.data || r.data), id }
      } catch {
        this.selectedPayslip = { ...payslip, id: payslip.id || payslip.payslip_id }
        this.showToast('Showing preview. Some details may be missing.', 'warning')
      }
    },

    closeModal() { this.selectedPayslip = null },
    resetFilters() { this.filterYear = new Date().getFullYear(); this.filterMonth = ''; this.filterStatus = ''; this.fetchPayslips() },
    showToast(message, type = 'success') { this.toast = { show: true, message, type }; setTimeout(() => { this.toast.show = false }, 3000) },
    handleApiError() { this.error = 'Unable to load data. Please check your connection.' },

    formatCurrency(amount) {
      if (amount === null || amount === undefined) return 'ZMW 0.00'
      return new Intl.NumberFormat('en-ZM', { style: 'currency', currency: 'ZMW', minimumFractionDigits: 2 }).format(parseFloat(amount) || 0)
    },
    formatCurrencyShort(amount) {
      const n = parseFloat(amount) || 0
      if (n >= 1000000) return `ZMW ${(n/1000000).toFixed(1)}M`
      if (n >= 1000) return `ZMW ${(n/1000).toFixed(1)}K`
      return `ZMW ${n.toFixed(0)}`
    },
    formatStatus(s) { return { generated: 'Generated', paid: 'Paid', draft: 'Draft', pending: 'Pending' }[s?.toLowerCase()] || s || 'Unknown' },
    getStatusClass(s) { return { generated: 'warning', paid: 'success', draft: 'danger', pending: 'danger', completed: 'success' }[s?.toLowerCase()] || 'neutral' },
    formatDate(date) {
      if (!date) return '—'
      try { return new Date(date).toLocaleDateString('en-ZM', { year: 'numeric', month: 'short', day: 'numeric' }) } catch { return '—' }
    },
    safeFormatPeriod(period) {
      if (!period) return ''
      const s = String(period)
      if (/^\d{4}-\d{2}$/.test(s)) {
        const [y, m] = s.split('-')
        return `${ ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][parseInt(m)-1] } ${y}`
      }
      return s
    },
    prevPage() { if (this.currentPage > 1) this.currentPage-- },
    nextPage() { if (this.currentPage < this.totalPages) this.currentPage++ }
  }
}
</script>

<style scoped>
/* ── Base ──────────────────────────────────────────── */
.payslips-view {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
  padding: 1.5rem 2rem 3rem;
  max-width: 1200px;
  margin: 0 auto;
}

/* ── Header Card ─────────────────────────────────── */
.dashboard-header-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem 1.75rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0;
  margin-bottom: 1.25rem;
  position: relative;
  overflow: hidden;
}

.header-card-accent {
  position: absolute; top: 0; left: 0; right: 0; height: 3px;
 
}

.user-greeting {
  display: flex; justify-content: space-between; align-items: center; gap: 1.5rem;
}

.avatar-section { display: flex; align-items: center; gap: 1rem; }

.avatar {
  width: 52px; height: 52px;
  background: linear-gradient(135deg, #3b82f6, #6366f1);
  border-radius: 14px; display: flex; align-items: center; justify-content: center;
  color: white; box-shadow: 0 4px 12px rgba(59,130,246,0.25); flex-shrink: 0;
}

.user-info { display: flex; flex-direction: column; gap: 0.2rem; }
.greeting { margin: 0; font-size: 1.375rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
.subtitle { margin: 0; color: #64748b; font-size: 0.875rem; }
.role-meta { margin-top: 0.125rem; }

.role-badge {
  background: #f0fdf4; border: 1px solid #bbf7d0;
  padding: 0.125rem 0.6rem; border-radius: 8px;
  font-size: 0.7rem; font-weight: 600; color: #166534;
}

.header-actions { display: flex; gap: 0.5rem; flex-shrink: 0; }

/* ── Buttons ─────────────────────────────────────── */
.btn-primary {
  display: flex; align-items: center;
  background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;
  border: none; padding: 0.5rem 1.25rem; border-radius: 8px;
  font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,0.35); }
.btn-primary:disabled { opacity: 0.7; cursor: not-allowed; transform: none; }

.btn-outline {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.45rem 0.9rem; background: white; border: 1px solid #e2e8f0;
  color: #475569; border-radius: 8px; font-size: 0.82rem; font-weight: 600;
  cursor: pointer; transition: all 0.2s;
}
.btn-outline:hover { background: #f8fafc; border-color: #cbd5e1; color: #1e293b; }
.btn-outline.warning { color: #92400e; background: #fef3c7; border-color: #fde68a; }
.btn-outline.warning:hover { background: #fde68a; }

.btn-secondary {
  padding: 0.5rem 1.25rem; background: #f1f5f9; color: #475569;
  border: 1px solid #e2e8f0; border-radius: 8px;
  font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.btn-secondary:hover { background: #e2e8f0; }

/* ── Dashboard Content ───────────────────────────── */
.dashboard-content { display: flex; flex-direction: column; gap: 1.5rem; }

/* ── Section Cards ───────────────────────────────── */
.metrics-section,
.table-section {
  background: white;
  border-radius: 16px;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0;
  padding: 1.5rem;
}

h2 { font-size: 1.1rem; font-weight: 600; margin: 0 0 1.25rem 0; color: #334155; }

/* ── Metrics ─────────────────────────────────────── */
.metrics-grid {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem;
}

.metric-card {
  padding: 1.25rem; background: #f8fafc; border-radius: 12px;
  display: flex; flex-direction: column; align-items: center; text-align: center;
  border: 1px solid #e2e8f0; position: relative; overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}
.metric-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px -4px rgba(0,0,0,0.08); border-color: var(--accent); }
.metric-card::before { display: none; }

.metric-icon-wrap {
  width: 40px; height: 40px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center; margin-bottom: 0.75rem;
}
.metric-value { font-size: 1.8rem; font-weight: 800; color: #0f172a; line-height: 1.1; margin-bottom: 0.25rem; }
.metric-label { font-size: 0.78rem; color: #64748b; font-weight: 500; text-transform: uppercase; letter-spacing: 0.04em; }

/* ── Controls ────────────────────────────────────── */
.controls-bar {
  display: flex; justify-content: space-between; align-items: flex-end;
  margin-bottom: 1.25rem; gap: 1rem; flex-wrap: wrap;
}
.filters-row { display: flex; gap: 0.875rem; flex-wrap: wrap; }
.filter-group { display: flex; flex-direction: column; gap: 0.3rem; }
.filter-group label { font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }

.filter-select {
  padding: 0.45rem 2rem 0.45rem 0.875rem; border: 1px solid #e2e8f0;
  border-radius: 8px; background: #f8fafc; color: #334155;
  font-size: 0.875rem; font-weight: 500; cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat: no-repeat; background-position: right 0.75rem center;
  transition: all 0.2s;
}
.filter-select:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }

.records-count {
  font-size: 0.78rem; font-weight: 700; color: #64748b;
  background: #f1f5f9; padding: 0.2rem 0.7rem; border-radius: 9999px; white-space: nowrap;
}

/* ── Table ───────────────────────────────────────── */
.table-container { border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0; }

.list-header,
.list-row {
  display: grid;
  grid-template-columns: 1.5fr 1fr 1fr 1fr 0.9fr 1fr 0.7fr;
  padding: 0.75rem 1rem; align-items: center; gap: 0.75rem;
}

.list-header {
  background: #f8fafc; border-bottom: 1px solid #e2e8f0;
  font-size: 0.7rem; font-weight: 700; color: #64748b;
  text-transform: uppercase; letter-spacing: 0.05em;
}

.list-row {
  border-bottom: 1px solid #f1f5f9; background: white;
  cursor: pointer; transition: background 0.15s;
}
.list-row:last-child { border-bottom: none; }
.list-row:hover { background: #f8fafc; }

.period-text { font-weight: 600; color: #334155; font-size: 0.875rem; }

.font-mono { font-family: 'SFMono-Regular', Consolas, monospace; font-size: 0.82rem; }
.font-bold { font-weight: 700; }
.text-right { text-align: right; }
.text-muted { color: #64748b; font-size: 0.82rem; }
.text-danger { color: #ef4444; }
.text-success { color: #10b981; }

/* Status Badges */
.status-badge {
  display: inline-flex; align-items: center; gap: 5px;
  padding: 0.25rem 0.65rem; border-radius: 9999px;
  font-size: 0.7rem; font-weight: 700; white-space: nowrap;
}
.dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
.status-badge.success { background: #d1fae5; color: #065f46; }
.status-badge.warning { background: #fef3c7; color: #92400e; }
.status-badge.danger  { background: #fee2e2; color: #991b1b; }
.status-badge.neutral { background: #f1f5f9; color: #64748b; }

/* Action Buttons */
.action-group { display: flex; justify-content: flex-end; gap: 0.35rem; }

.action-btn {
  width: 30px; height: 30px; border-radius: 6px;
  border: 1px solid #e2e8f0; background: white; color: #64748b;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.15s;
}
.action-btn:hover { background: #eff6ff; color: #4f46e5; border-color: #a5b4fc; }
.action-btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* ── States ──────────────────────────────────────── */
.spinner {
  width: 40px; height: 40px; border: 3px solid #e2e8f0; border-top-color: #6366f1;
  border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto;
}
.spinner-sm {
  width: 14px; height: 14px; border: 2px solid rgba(0,0,0,0.15); border-top-color: currentColor;
  border-radius: 50%; animation: spin 1s linear infinite; display: inline-block;
}
.spinner-sm.white { border-color: rgba(255,255,255,0.3); border-top-color: white; }
@keyframes spin { to { transform: rotate(360deg); } }

.empty-state {
  text-align: center; padding: 4rem 2rem;
  display: flex; flex-direction: column; align-items: center; gap: 0.875rem; color: #94a3b8;
}
.empty-state p { margin: 0; font-size: 0.875rem; color: #64748b; }

/* ── Pagination ──────────────────────────────────── */
.pagination-bar {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1rem 0 0; border-top: 1px solid #f1f5f9; margin-top: 0.5rem;
}
.pagination-info { font-size: 0.82rem; color: #64748b; }
.pagination-info strong { color: #1e293b; font-weight: 700; }
.pagination-controls { display: flex; gap: 0.5rem; }

.page-btn {
  display: flex; align-items: center; gap: 0.3rem;
  padding: 0.35rem 0.875rem; background: white; border: 1px solid #e2e8f0;
  border-radius: 6px; font-size: 0.78rem; font-weight: 600; color: #475569;
  cursor: pointer; transition: all 0.15s;
}
.page-btn:hover:not(:disabled) { border-color: #a5b4fc; color: #4f46e5; background: #eff6ff; }
.page-btn:disabled { opacity: 0.4; cursor: not-allowed; }

/* ── Modal ───────────────────────────────────────── */
.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,31,91,0.5);
  backdrop-filter: blur(4px); z-index: 100;
  display: flex; justify-content: center; align-items: center; padding: 1rem;
}

.modal-container {
  background: white; width: 100%; max-width: 720px; max-height: 90vh;
  border-radius: 16px; box-shadow: 0 25px 60px rgba(0,31,91,0.2);
  display: flex; flex-direction: column; overflow: hidden;
  border: 1px solid #e2e8f0; animation: slideUp 0.25s ease-out;
}
@keyframes slideUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }

.modal-header {
  padding: 1.5rem 1.75rem;
  background: linear-gradient(135deg, #001f5b 0%, #002a7a 100%);
  display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;
}
.modal-title-wrap { display: flex; align-items: center; gap: 1rem; }

.modal-avatar {
  width: 48px; height: 48px; background: rgba(255,255,255,0.15); border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-weight: 900; font-size: 0.75rem; color: white; letter-spacing: 0.05em;
  border: 1.5px solid rgba(255,255,255,0.25);
}

.modal-name { font-size: 1.2rem; font-weight: 700; color: white; margin: 0; }
.modal-position { font-size: 0.82rem; color: rgba(255,255,255,0.7); margin: 0.2rem 0 0; }

.modal-close {
  width: 36px; height: 36px; border-radius: 50%;
  border: 1.5px solid rgba(255,255,255,0.25); background: rgba(255,255,255,0.1);
  color: white; display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all 0.2s;
}
.modal-close:hover { background: rgba(239,68,68,0.6); border-color: transparent; }

.modal-body { padding: 1.5rem 1.75rem; overflow-y: auto; flex: 1; }

/* Modal Quick Stats */
.modal-stats {
  display: grid; grid-template-columns: repeat(3, 1fr);
  gap: 0.875rem; margin-bottom: 1.5rem;
}
.modal-stat {
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
  padding: 0.875rem; text-align: center;
  display: flex; flex-direction: column; gap: 0.3rem; align-items: center;
}
.modal-stat small { font-size: 0.68rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; }
.modal-stat strong { font-size: 0.9rem; font-weight: 700; color: #1e293b; }
.modal-stat .mono { font-family: 'SFMono-Regular', Consolas, monospace; font-size: 0.82rem; }

/* Split View */
.split-view { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; margin-bottom: 1.25rem; }

.detail-column {
  background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1.125rem;
}

.modal-section-title {
  display: flex; align-items: center; gap: 0.5rem;
  font-size: 0.82rem; font-weight: 700; color: #334155;
  margin: 0 0 0.875rem; padding-bottom: 0.6rem; border-bottom: 1px solid #e2e8f0;
}

.col-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.col-dot.green { background: #10b981; }
.col-dot.red   { background: #ef4444; }

.line-items { display: flex; flex-direction: column; }
.line-item {
  display: flex; justify-content: space-between; align-items: center;
  padding: 0.45rem 0.5rem; border-radius: 6px; font-size: 0.82rem;
}
.line-item:hover { background: white; }
.line-item span:first-child { color: #64748b; }
.line-item span:last-child { font-family: 'SFMono-Regular', Consolas, monospace; font-size: 0.78rem; font-weight: 600; }

.col-total {
  display: flex; justify-content: space-between; font-weight: 700;
  border-top: 1px solid #e2e8f0; padding-top: 0.75rem; margin-top: 0.5rem; font-size: 0.875rem;
}

/* Net Pay Card */
.net-pay-card {
  background: linear-gradient(135deg, #001f5b 0%, #0040c1 100%);
  color: white; padding: 1.5rem; border-radius: 12px;
  display: flex; justify-content: space-between; align-items: center;
  position: relative; overflow: hidden; margin-bottom: 0;
}
.net-label { font-size: 0.68rem; opacity: 0.8; letter-spacing: 0.12em; font-weight: 600; margin-bottom: 0.4rem; }
.net-amount { font-size: 1.8rem; font-weight: 800; letter-spacing: -0.02em; }
.net-bg { position: absolute; right: -10px; bottom: -22px; font-size: 4.5rem; font-weight: 900; opacity: 0.07; letter-spacing: -0.05em; }

.modal-footer {
  padding: 1.125rem 1.75rem; border-top: 1px solid #f1f5f9;
  background: #f8fafc; display: flex; justify-content: flex-end; gap: 0.75rem; flex-shrink: 0;
}

/* ── Toast ───────────────────────────────────────── */
.toast {
  position: fixed; bottom: 2rem; right: 2rem;
  background: white; padding: 0.875rem 1.25rem; border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  display: flex; align-items: center; gap: 0.65rem;
  z-index: 200; font-size: 0.875rem; font-weight: 500;
  border-left: 4px solid #10b981;
}
.toast.error { border-left-color: #ef4444; }
.toast.warning { border-left-color: #f59e0b; }
.toast.success svg { color: #10b981; }
.toast.error svg { color: #ef4444; }

/* Transitions */
.modal-fade-enter-active, .modal-fade-leave-active { transition: opacity 0.25s ease; }
.modal-fade-enter-from, .modal-fade-leave-to { opacity: 0; }
.toast-slide-enter-active, .toast-slide-leave-active { transition: all 0.3s ease; }
.toast-slide-enter-from, .toast-slide-leave-to { opacity: 0; transform: translateY(12px); }

/* ── Responsive ──────────────────────────────────── */
@media (max-width: 768px) {
  .payslips-view { padding: 1rem; }
  .user-greeting { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .header-actions { width: 100%; }
  .metrics-grid { grid-template-columns: 1fr 1fr; }
  .list-header { display: none; }
  .list-row {
    grid-template-columns: 1fr auto;
    grid-template-areas: "period status" "amounts actions";
    padding: 0.875rem 1rem; gap: 0.5rem;
  }
  .col-period { grid-area: period; }
  .col-status { grid-area: status; }
  .col-amount, .col-date { display: none; }
  .col-actions { grid-area: actions; }
  .split-view { grid-template-columns: 1fr; gap: 1rem; }
  .modal-stats { grid-template-columns: 1fr 1fr; }
  .modal-container { max-height: 100vh; border-radius: 16px 16px 0 0; position: fixed; bottom: 0; }
}

@media (max-width: 480px) {
  .metrics-grid { grid-template-columns: 1fr; }
}
</style>