<template>
  <div class="payslips-view">
    <header class="header">
      <div class="header-left">
        <h1 class="title">{{ pageName }}</h1>
        <p class="subtitle">Castle Holding Zambia - Manage and download your payslips</p>
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
        </select>
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
            <tr v-for="payslip in payslips" :key="payslip.id">
              <td><strong>{{ payslip.period }}</strong></td>
              <td>{{ formatCurrency(payslip.grossPay) }}</td>
              <td class="deduction">{{ formatCurrency(payslip.deductions) }}</td>
              <td class="net-pay"><strong>{{ formatCurrency(payslip.netPay) }}</strong></td>
              <td>
                <span :class="['status-badge', payslip.status.toLowerCase()]">
                  {{ formatStatus(payslip.status) }}
                </span>
              </td>
              <td>{{ formatDate(payslip.payment_date) }}</td>
              <td>
                <div class="action-buttons">
                  <button
                    @click="viewPayslip(payslip)"
                    class="action-btn view"
                    title="View Details">
                    <i class="icon icon-eye"></i> View
                  </button>
                  <button
                    @click.prevent="downloadPayslip(payslip)"
                    class="action-btn download"
                    :class="{ 'downloading': downloading[payslip.id] }"
                    :disabled="downloading[payslip.id]"
                    title="Download PDF">
                    <span v-if="downloading[payslip.id]">
                      <i class="icon icon-spinner"></i> Downloading...
                    </span>
                    <span v-else>
                      <i class="icon icon-download"></i> Download
                    </span>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    <!-- Payslip Detail Modal -->
    <div v-if="selectedPayslip" class="modal-overlay" @click.self="selectedPayslip = null">
      <div class="modal-card">
        <div class="modal-header">
          <h2>Payslip Details - {{ selectedPayslip.period }}</h2>
          <button @click="selectedPayslip = null" class="close-btn">✕</button>
        </div>
        <div class="modal-body">
          <div class="detail-section">
            <div class="card-header">
              <h3>Period Information</h3>
              <i class="icon icon-info"></i>
            </div>
            <div class="detail-grid">
              <div><strong>Period:</strong> {{ selectedPayslip.period }}</div>
              <div><strong>Payment Date:</strong> {{ formatDate(selectedPayslip.payment_date) }}</div>
              <div><strong>Status:</strong> <span :class="['status-badge', selectedPayslip.status]">{{ formatStatus(selectedPayslip.status) }}</span></div>
            </div>
          </div>
          <div class="detail-section">
            <div class="card-header">
              <h3>Earnings</h3>
              <i class="icon icon-money"></i>
            </div>
            <div class="amount-grid">
              <div class="amount-row">
                <span>Basic Salary:</span>
                <span>{{ formatCurrency(selectedPayslip.basic_salary) }}</span>
              </div>
              <div class="amount-row">
                <span>House Allowance:</span>
                <span>{{ formatCurrency(selectedPayslip.house_allowance) }}</span>
              </div>
              <div class="amount-row">
                <span>Transport Allowance:</span>
                <span>{{ formatCurrency(selectedPayslip.transport_allowance) }}</span>
              </div>
              <div class="amount-row">
                <span>Other Allowances:</span>
                <span>{{ formatCurrency(selectedPayslip.other_allowances) }}</span>
              </div>
              <div class="amount-row">
                <span>Overtime Pay ({{ selectedPayslip.overtime_hours }}h):</span>
                <span>{{ formatCurrency(selectedPayslip.overtime_pay) }}</span>
              </div>
              <div class="amount-row total">
                <span><strong>Gross Pay:</strong></span>
                <span><strong>{{ formatCurrency(selectedPayslip.grossPay) }}</strong></span>
              </div>
            </div>
          </div>
          <div class="detail-section">
            <div class="card-header">
              <h3>Deductions</h3>
              <i class="icon icon-minus"></i>
            </div>
            <div class="amount-grid">
              <div class="amount-row">
                <span>NAPSA (5%):</span>
                <span>{{ formatCurrency(selectedPayslip.napsa) }}</span>
              </div>
              <div class="amount-row">
                <span>PAYE Tax:</span>
                <span>{{ formatCurrency(selectedPayslip.paye) }}</span>
              </div>
              <div class="amount-row">
                <span>NHIMA (1%):</span>
                <span>{{ formatCurrency(selectedPayslip.nhima) }}</span>
              </div>
              <div class="amount-row">
                <span>Other Deductions:</span>
                <span>{{ formatCurrency(selectedPayslip.other_deductions) }}</span>
              </div>
              <div class="amount-row total deduction">
                <span><strong>Total Deductions:</strong></span>
                <span><strong>{{ formatCurrency(selectedPayslip.deductions) }}</strong></span>
              </div>
            </div>
          </div>
          <div class="net-pay-section">
            <div class="net-pay-label">NET PAY</div>
            <div class="net-pay-amount">{{ formatCurrency(selectedPayslip.netPay) }}</div>
          </div>
        </div>
        <div class="modal-footer">
          <button
            @click="downloadPayslip(selectedPayslip)"
            class="btn-primary"
            :disabled="downloading[selectedPayslip.id]">
            {{ downloading[selectedPayslip.id] ? '⏳ Downloading...' : '⬇️ Download PDF' }}
          </button>
          <button @click="selectedPayslip = null" class="btn-secondary">Close</button>
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
      }
    }
  },
  computed: {
    totalEarnings() {
      return this.payslips.reduce((sum, payslip) => sum + (payslip.netPay || 0), 0)
    },
    latestPayslipPeriod() {
      if (this.payslips.length === 0) return 'N/A'
      return this.payslips[0].period || 'N/A'
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
        
        // Use the correct endpoint for employee payslips
        const response = await axios.get('/api/employee/payslips', { params })
        this.payslips = response.data.data || response.data || []
        
        // Extract available years from payslips
        if (this.payslips.length > 0) {
          const years = [...new Set(this.payslips.map(p => {
            // Try different date field names that might be in the response
            const dateStr = p.pay_period_start || p.period_start || p.created_at
            const date = new Date(dateStr)
            return date.getFullYear()
          }))].sort((a, b) => b - a)
          this.availableYears = years
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
      this.$set(this.downloading, payslip.id, true)
      
      try {
        // Use the correct endpoint structure
        const response = await axios.get(`/api/employee/payslips/${payslip.id}/download`, {
          responseType: 'blob',
          headers: {
            'Accept': 'application/pdf'
          }
        })
        
        // Check if we got a PDF
        if (response.data.type === 'application/pdf') {
          const url = window.URL.createObjectURL(new Blob([response.data], { type: 'application/pdf' }))
          const link = document.createElement('a')
          link.href = url
          link.setAttribute('download', `payslip-${payslip.period?.replace(/ /g, '-') || payslip.id}.pdf`)
          document.body.appendChild(link)
          link.click()
          link.remove()
          window.URL.revokeObjectURL(url)
          
          this.showToast('Payslip downloaded successfully!', 'success')
        } else {
          // Handle case where PDF generation might have failed
          throw new Error('Invalid file type received')
        }
      } catch (err) {
        console.error('Download error:', err)
        
        // More specific error handling
        if (err.response?.status === 404) {
          this.showToast('Payslip PDF not found. Please contact HR.', 'error')
        } else if (err.response?.status === 403) {
          this.showToast('You do not have permission to download this payslip.', 'error')
        } else {
          this.showToast('Failed to download payslip. Please try again.', 'error')
        }
        
        this.handleApiError(err)
      } finally {
        this.$set(this.downloading, payslip.id, false)
      }
    },
    
    async viewPayslip(payslip) {
      try {
        // Fetch detailed payslip information
        const response = await axios.get(`/api/employee/payslips/${payslip.id}`)
        this.selectedPayslip = response.data.data || response.data
      } catch (err) {
        console.error('Error fetching payslip details:', err)
        this.showToast('Failed to load payslip details.', 'error')
        // Fallback to basic payslip data
        this.selectedPayslip = payslip
      }
    },
    
    resetFilters() {
      this.filterYear = new Date().getFullYear()
      this.filterMonth = ''
      this.filterStatus = ''
      this.fetchPayslips()
    },
    
    showToast(message, type = 'success') {
      this.toast = { show: true, message, type }
      setTimeout(() => {
        this.toast.show = false
      }, 3000)
    },
    
    handleApiError(err) {
      let errorMsg = 'An unexpected error occurred.'
      
      if (err.code === 'ERR_NETWORK' || err.message.includes('Network Error')) {
        errorMsg = 'Network error. Please check your connection.'
      } else if (err.response?.status === 401) {
        errorMsg = 'Session expired. Redirecting to login...'
        this.authStore.clearAuth()
        this.$router.push({ name: 'login' })
      } else if (err.response?.status === 403) {
        errorMsg = 'Access denied.'
      } else if (err.response?.status === 404) {
        errorMsg = 'Payslip not found. Please contact HR.'
      } else if (err.response?.status === 500) {
        errorMsg = 'Server error. Please try again later.'
      } else {
        errorMsg = err.response?.data?.message || err.response?.data?.error || errorMsg
      }
      
      this.error = errorMsg
    },
    
    formatCurrency(amount) {
      if (amount === null || amount === undefined) return 'ZMW 0.00'
      return new Intl.NumberFormat('en-ZM', {
        style: 'currency',
        currency: 'ZMW',
        minimumFractionDigits: 2
      }).format(amount)
    },
    
    formatStatus(status) {
      const statuses = {
        generated: 'Generated',
        paid: 'Paid',
        draft: 'Draft',
        processing: 'Processing'
      }
      return statuses[status] || status
    },
    
    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-ZM', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
  }
}
</script>

<style scoped>
.payslips-view {
  --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  --success-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
  --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
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

/* Toast Notification */
.toast {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  padding: 1rem 1.5rem;
  border-radius: var(--radius);
  box-shadow: var(--shadow-lg);
  z-index: 2000;
  animation: slideIn 0.3s ease-out;
  font-weight: 500;
}

.toast.success {
  background: var(--success-gradient);
  color: white;
}

.toast.error {
  background: var(--danger-gradient);
  color: white;
}

@keyframes slideIn {
  from {
    transform: translateX(400px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

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

.subtitle {
  margin: 0;
  color: var(--text-secondary);
  font-size: 1.1rem;
  font-weight: 400;
}

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
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  min-width: 150px;
}

.filter-group label {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.9rem;
}

.filter-group select {
  padding: 0.75rem;
  border: 1px solid var(--border);
  border-radius: 8px;
  font-size: 0.95rem;
  background: white;
  transition: var(--transition);
}

.filter-group select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
  padding: 0 0 2rem;
  max-width: 1200px;
  margin-left: auto;
  margin-right: auto;
}

.card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  padding: 2rem;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  text-align: center;
  transition: var(--transition);
  border: 1px solid rgba(255, 255, 255, 0.2);
  position: relative;
  overflow: hidden;
}

.card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: var(--primary-gradient);
  opacity: 0;
  transition: var(--transition);
}

.card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
}

.card:hover::before {
  opacity: 1;
}

.card-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  display: block;
  transition: var(--transition);
  color: var(--text-secondary);
}

.card h3 {
  margin: 0 0 0.5rem;
  font-size: 0.875rem;
  color: var(--text-secondary);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 500;
}

.card .value {
  margin: 0;
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.2;
}

.content {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: var(--radius);
  box-shadow: var(--shadow-lg);
  overflow: hidden;
  padding: 2rem;
}

.empty-state {
  text-align: center;
  padding: 6rem 2rem;
  color: var(--text-secondary);
}

.empty-state .icon {
  font-size: 4rem;
  color: var(--text-secondary);
  margin-bottom: 1rem;
  display: block;
}

.table-container {
  overflow-x: auto;
  margin-top: 1rem;
}

.payslips-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.95rem;
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.payslips-table th,
.payslips-table td {
  padding: 1.25rem 1rem;
  text-align: left;
  border-bottom: 1px solid #f3f4f6;
}

.payslips-table th {
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  font-weight: 600;
  color: var(--text-secondary);
  text-transform: uppercase;
  font-size: 0.8rem;
  letter-spacing: 0.05em;
}

.payslips-table tr:hover {
  background: #f8fafc;
  transform: scale(1.01);
  transition: var(--transition);
}

.deduction {
  color: #dc2626;
  font-weight: 500;
}

.net-pay {
  color: #059669;
  font-size: 1.1rem;
  font-weight: 700;
}

.status-badge {
  padding: 0.5rem 1rem;
  border-radius: 50px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  display: inline-block;
}

.status-badge.generated {
  background: var(--warning-gradient);
  color: white;
}

.status-badge.paid {
  background: var(--success-gradient);
  color: white;
}

.status-badge.draft {
  background: var(--danger-gradient);
  color: white;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 8px;
  font-size: 0.85rem;
  cursor: pointer;
  transition: var(--transition);
  color: white;
  display: flex;
  align-items: center;
  gap: 0.25rem;
  font-weight: 500;
}

.action-btn.download {
  background: var(--success-gradient);
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.action-btn.download:hover:not(:disabled) {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  transform: translateY(-1px);
}

.action-btn.download:disabled {
  background: #6c757d;
  cursor: not-allowed;
  opacity: 0.6;
}

.action-btn.view {
  background: var(--primary-gradient);
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.action-btn.view:hover {
  background: linear-gradient(135deg, #5a67d8 0%, #4c51bf 100%);
  transform: translateY(-1px);
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 6rem 2rem;
  color: var(--text-secondary);
}

.spinner {
  width: 60px;
  height: 60px;
  border: 4px solid #f3f4f6;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1.5rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-message {
  background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
  color: #991b1b;
  padding: 2rem;
  border-radius: var(--radius);
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  box-shadow: var(--shadow-lg);
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.btn-primary, .btn-secondary {
  padding: 1rem 2rem;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  font-weight: 600;
  transition: var(--transition);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-primary {
  background: var(--primary-gradient);
  color: white;
  box-shadow: 0 4px 14px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: rgba(248, 250, 252, 0.5);
  color: var(--text-primary);
  border: 1px solid var(--border);
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(10px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  border-radius: var(--radius);
  max-width: 900px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: var(--shadow-lg);
  border: 1px solid rgba(255, 255, 255, 0.2);
  margin: 1rem;
  width: calc(100% - 2rem);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem;
  border-bottom: 1px solid var(--border);
}

.modal-header h2 {
  margin: 0;
  color: var(--text-primary);
  font-size: 1.5rem;
  font-weight: 600;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: var(--text-secondary);
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: var(--transition);
}

.close-btn:hover {
  background: rgba(226, 232, 240, 0.5);
}

.modal-body {
  padding: 2rem;
}

.detail-section {
  margin-bottom: 2.5rem;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.card-header h3 {
  margin: 0;
  color: var(--text-primary);
  font-size: 1.25rem;
  font-weight: 600;
  letter-spacing: -0.01em;
}

.card-header .icon {
  font-size: 1.5rem;
  color: var(--text-secondary);
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  padding: 1rem;
  background: rgba(248, 250, 252, 0.5);
  border-radius: 12px;
}

.amount-grid {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.amount-row {
  display: flex;
  justify-content: space-between;
  padding: 1rem;
  background: rgba(248, 250, 252, 0.5);
  border-radius: 8px;
  transition: var(--transition);
}

.amount-row:hover {
  background: rgba(241, 245, 249, 0.8);
}

.amount-row.total {
  border-top: 2px solid #333;
  margin-top: 0.5rem;
  padding-top: 1rem;
  font-size: 1.1rem;
  background: white;
  border-radius: 8px;
}

.net-pay-section {
  background: var(--primary-gradient);
  color: white;
  padding: 2.5rem;
  border-radius: var(--radius);
  text-align: center;
  margin-top: 2rem;
}

.net-pay-label {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
  opacity: 0.9;
}

.net-pay-amount {
  font-size: 3rem;
  font-weight: 700;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 2rem;
  border-top: 1px solid var(--border);
  background: rgba(248, 250, 252, 0.5);
}

.icon {
  font-size: 1.2rem;
  font-weight: bold;
}

@media (max-width: 768px) {
  .payslips-view {
    padding: 1rem;
  }

  .header {
    flex-direction: column;
    gap: 1.5rem;
    padding: 1.5rem;
    text-align: center;
  }

  .filters {
    flex-direction: column;
    gap: 1rem;
  }

  .summary-cards {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .payslips-table {
    font-size: 0.85rem;
  }

  .payslips-table th,
  .payslips-table td {
    padding: 0.75rem 0.5rem;
  }

  .action-buttons {
    flex-direction: column;
  }

  .modal-card {
    margin: 1rem;
    max-width: calc(100% - 2rem);
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.5rem;
  }

  .detail-grid {
    grid-template-columns: 1fr;
  }

  .modal-footer {
    flex-direction: column-reverse;
    align-items: stretch;
  }

  .btn-primary, .btn-secondary {
    justify-content: center;
  }
}
</style>