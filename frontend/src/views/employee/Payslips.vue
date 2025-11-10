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
          <div class="card-icon">📄</div>
          <h3>Total Payslips</h3>
          <p class="value">{{ payslips.length }}</p>
        </div>
        <div class="card">
          <div class="card-icon">💰</div>
          <h3>Total Earnings (ZMW)</h3>
          <p class="value">{{ formatCurrency(totalEarnings) }}</p>
        </div>
        <div class="card">
          <div class="card-icon">📅</div>
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
        <div class="empty-icon">📭</div>
        <p>No payslips found for the selected filters.</p>
        <button @click="resetFilters" class="btn-primary">Reset Filters</button>
      </div>

      <!-- Payslips Table -->
      <table v-else class="payslips-table">
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
              <span :class="['status', payslip.status.toLowerCase()]">
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
                  👁️ View
                </button>
                <button 
                  @click.prevent="downloadPayslip(payslip)" 
                  class="action-btn download" 
                  :class="{ 'downloading': downloading[payslip.id] }"
                  :disabled="downloading[payslip.id]"
                  title="Download PDF">
                  <span v-if="downloading[payslip.id]">⏳ Downloading...</span>
                  <span v-else>⬇️ Download</span>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
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
            <h3>Period Information</h3>
            <div class="detail-grid">
              <div><strong>Period:</strong> {{ selectedPayslip.period }}</div>
              <div><strong>Payment Date:</strong> {{ formatDate(selectedPayslip.payment_date) }}</div>
              <div><strong>Status:</strong> <span :class="['status', selectedPayslip.status]">{{ formatStatus(selectedPayslip.status) }}</span></div>
            </div>
          </div>

          <div class="detail-section">
            <h3>Earnings</h3>
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
            <h3>Deductions</h3>
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
        
        const response = await axios.get('/api/employee/payslips', { params })
        this.payslips = response.data.data || []
        
        // Extract available years from payslips
        if (this.payslips.length > 0) {
          const years = [...new Set(this.payslips.map(p => {
            const date = new Date(p.pay_period_start)
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
      // Vue 3: Direct property assignment works with reactivity
      this.downloading[payslip.id] = true
      try {
        const response = await axios.get(`/api/employee/payslips/${payslip.id}/download`, { 
          responseType: 'blob' 
        })
        
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `payslip-${payslip.period.replace(' ', '-')}.pdf`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
        
        this.showToast('Payslip downloaded successfully!', 'success')
      } catch (err) {
        console.error('Download error:', err)
        this.showToast('Failed to download payslip. Please try again.', 'error')
        this.handleApiError(err)
      } finally {
        this.downloading[payslip.id] = false
      }
    },
    
    viewPayslip(payslip) {
      this.selectedPayslip = payslip
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
        errorMsg = 'Employee record not found. Please contact HR.'
      } else {
        errorMsg = err.response?.data?.message || errorMsg
      }
      this.error = errorMsg
    },
    
    formatCurrency(amount) {
      return new Intl.NumberFormat('en-ZM', {
        style: 'currency',
        currency: 'ZMW',
        minimumFractionDigits: 2
      }).format(amount || 0)
    },
    
    formatStatus(status) {
      const statuses = {
        generated: 'Generated',
        paid: 'Paid',
        draft: 'Draft'
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
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  min-height: 100vh;
}

/* Toast Notification */
.toast {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  padding: 1rem 1.5rem;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 2000;
  animation: slideIn 0.3s ease-out;
}

.toast.success {
  background: #28a745;
  color: white;
}

.toast.error {
  background: #dc3545;
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
  background: white;
  padding: 1.5rem 2rem;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  margin-bottom: 2rem;
}

.title {
  margin: 0 0 0.5rem 0;
  font-size: 2rem;
  font-weight: 300;
  color: #2c3e50;
}

.subtitle {
  margin: 0;
  color: #7f8c8d;
  font-size: 1rem;
}

.filters {
  display: flex;
  gap: 1rem;
  background: white;
  padding: 1rem;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  min-width: 150px;
}

.filter-group label {
  font-weight: 600;
  color: #2c3e50;
  font-size: 0.9rem;
}

.filter-group select {
  padding: 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 0.95rem;
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  text-align: center;
}

.card-icon {
  font-size: 2.5rem;
  margin-bottom: 1rem;
}

.card h3 {
  margin: 0 0 0.5rem;
  font-size: 1rem;
  color: #7f8c8d;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.card .value {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
  color: #2c3e50;
}

.content {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #7f8c8d;
}

.empty-icon {
  font-size: 4rem;
  margin-bottom: 1rem;
}

.payslips-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.95rem;
}

.payslips-table th,
.payslips-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid #ecf0f1;
}

.payslips-table th {
  background: #f8f9fa;
  font-weight: 600;
  color: #2c3e50;
  text-transform: uppercase;
  font-size: 0.85rem;
}

.payslips-table tr:hover {
  background: #f8f9fa;
}

.deduction {
  color: #e74c3c;
}

.net-pay {
  color: #27ae60;
  font-size: 1.1rem;
}

.status {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 500;
  text-transform: uppercase;
}

.status.generated {
  background: #fff3cd;
  color: #856404;
}

.status.paid {
  background: #d4edda;
  color: #155724;
}

.status.draft {
  background: #f8d7da;
  color: #721c24;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.action-btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.2s ease;
  color: white;
}

.action-btn.download {
  background: #28a745;
}

.action-btn.download:hover:not(:disabled) {
  background: #218838;
}

.action-btn.download:disabled {
  background: #6c757d;
  cursor: not-allowed;
  opacity: 0.6;
}

.action-btn.view {
  background: #007bff;
}

.action-btn.view:hover {
  background: #0056b3;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.error-message {
  background: #fee;
  color: #c33;
  padding: 2rem;
  border-radius: 8px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.btn-primary, .btn-secondary {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: #f0f0f0;
  color: #333;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-card {
  background: white;
  border-radius: 12px;
  max-width: 800px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h2 {
  margin: 0;
  color: #2c3e50;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  color: #718096;
}

.modal-body {
  padding: 1.5rem;
}

.detail-section {
  margin-bottom: 2rem;
}

.detail-section h3 {
  margin: 0 0 1rem 0;
  color: #2c3e50;
  border-bottom: 2px solid #667eea;
  padding-bottom: 0.5rem;
}

.detail-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.amount-grid {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.amount-row {
  display: flex;
  justify-content: space-between;
  padding: 0.5rem 0;
  border-bottom: 1px solid #f0f0f0;
}

.amount-row.total {
  border-top: 2px solid #333;
  margin-top: 0.5rem;
  padding-top: 1rem;
  font-size: 1.1rem;
}

.net-pay-section {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 2rem;
  border-radius: 12px;
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
  font-size: 2.5rem;
  font-weight: 700;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  padding: 1.5rem;
  border-top: 1px solid #e2e8f0;
}

@media (max-width: 768px) {
  .payslips-view {
    padding: 0.5rem;
  }

  .filters {
    flex-direction: column;
  }

  .summary-cards {
    grid-template-columns: 1fr;
  }

  .payslips-table {
    font-size: 0.85rem;
  }

  .payslips-table th,
  .payslips-table td {
    padding: 0.5rem;
  }

  .action-buttons {
    flex-direction: column;
  }

  .modal-card {
    margin: 1rem;
    max-width: calc(100% - 2rem);
  }
}
</style>