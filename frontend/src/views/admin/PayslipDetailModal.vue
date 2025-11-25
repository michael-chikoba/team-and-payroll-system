<template>

  <transition name="modal-fade">

    <div v-if="visible" class="modal-overlay" @click.self="close">

      <div class="modal-container">

        <div class="modal-header">

          <h2>{{ isPreview ? 'Payslip Preview' : 'Payslip Details' }}</h2>

          <button @click="close" class="close-btn">×</button>

        </div>

        <div v-if="loading" class="modal-loading">

          <div class="spinner"></div>

          <p>Loading payslip details...</p>

        </div>

        <div v-else-if="error" class="modal-error">

          <p>{{ error }}</p>

          <button @click="retry" class="retry-btn">Retry</button>

        </div>

        <div v-else-if="payslip" class="modal-body">

          <!-- Preview Badge -->

          <div v-if="isPreview" class="preview-badge">

            ⚠️ This is a preview. Process payroll to finalize.

          </div>

          <!-- Employee Info -->

          <section class="section employee-section">

            <h3>Employee Information</h3>

            <div class="info-grid">

              <div class="info-item">

                <span class="label">Name:</span>

                <span class="value">{{ payslip.employee?.name }}</span>

              </div>

              <div class="info-item">

                <span class="label">ID:</span>

                <span class="value">{{ payslip.employee?.employee_id }}</span>

              </div>

              <div class="info-item">

                <span class="label">Department:</span>

                <span class="value">{{ payslip.employee?.department }}</span>

              </div>

              <div class="info-item">

                <span class="label">Position:</span>

                <span class="value">{{ payslip.employee?.position }}</span>

              </div>

            </div>

          </section>

          <!-- Period Info -->

          <section class="section period-section">

            <h3>Pay Period</h3>

            <div class="info-grid">

              <div class="info-item">

                <span class="label">Period:</span>

                <span class="value">{{ formatPeriod(payslip.period?.payroll_period) }}</span>

              </div>

              <div class="info-item">

                <span class="label">Start Date:</span>

                <span class="value">{{ formatDate(payslip.period?.start_date) }}</span>

              </div>

              <div class="info-item">

                <span class="label">End Date:</span>

                <span class="value">{{ formatDate(payslip.period?.end_date) }}</span>

              </div>

              <div class="info-item">

                <span class="label">Payment Date:</span>

                <span class="value">{{ formatDate(payslip.period?.payment_date) }}</span>

              </div>

            </div>

          </section>

          <!-- Earnings Breakdown -->

          <section class="section earnings-section">

            <h3>Earnings Breakdown</h3>

            <div class="calculation-table">

              <div class="calc-row">

                <span class="calc-label">Basic Salary</span>

                <span class="calc-value">{{ formatCurrency(payslip.earnings?.basic_salary) }}</span>

              </div>

              <div class="calc-row subsection">

                <span class="calc-label">Allowances:</span>

                <span class="calc-value"></span>

              </div>

              <div class="calc-row indent">

                <span class="calc-label">Housing Allowance (25%)</span>

                <span class="calc-value">{{ formatCurrency(payslip.earnings?.house_allowance) }}</span>

              </div>

              <div class="calc-row indent">

                <span class="calc-label">Transport Allowance</span>

                <span class="calc-value">{{ formatCurrency(payslip.earnings?.transport_allowance) }}</span>

              </div>

              <div class="calc-row indent">

                <span class="calc-label">Lunch Allowance</span>

                <span class="calc-value">{{ formatCurrency(payslip.earnings?.lunch_allowance) }}</span>

              </div>

              <div v-if="payslip.earnings?.overtime_hours > 0" class="calc-row subsection">

                <span class="calc-label">Overtime:</span>

                <span class="calc-value"></span>

              </div>

              <div v-if="payslip.earnings?.overtime_hours > 0" class="calc-row indent">

                <span class="calc-label">{{ payslip.earnings.overtime_hours }}hrs @ {{ formatCurrency(payslip.earnings.overtime_rate) }}/hr</span>

                <span class="calc-value">{{ formatCurrency(payslip.earnings?.overtime_pay) }}</span>

              </div>

              <div v-if="payslip.earnings?.bonuses > 0" class="calc-row">

                <span class="calc-label">Bonuses</span>

                <span class="calc-value">{{ formatCurrency(payslip.earnings?.bonuses) }}</span>

              </div>

              <div class="calc-row total">

                <span class="calc-label">Gross Salary</span>

                <span class="calc-value">{{ formatCurrency(payslip.earnings?.gross_salary) }}</span>

              </div>

            </div>

          </section>

          <!-- Deductions Breakdown -->

          <section class="section deductions-section">

            <h3>Deductions Breakdown</h3>

            <div class="calculation-table">

              <div class="calc-row">

                <span class="calc-label">PAYE (on Basic Salary)</span>

                <span class="calc-value negative">-{{ formatCurrency(payslip.deductions?.paye) }}</span>

              </div>

              <div class="calc-row">

                <span class="calc-label">NAPSA (10% of Gross, capped)</span>

                <span class="calc-value negative">-{{ formatCurrency(payslip.deductions?.napsa) }}</span>

              </div>

              <div class="calc-row">

                <span class="calc-label">NHIMA (1% of Gross)</span>

                <span class="calc-value negative">-{{ formatCurrency(payslip.deductions?.nhima) }}</span>

              </div>

              <div v-if="payslip.deductions?.other_deductions > 0" class="calc-row">

                <span class="calc-label">Other Deductions</span>

                <span class="calc-value negative">-{{ formatCurrency(payslip.deductions?.other_deductions) }}</span>

              </div>

              <div class="calc-row total">

                <span class="calc-label">Total Deductions</span>

                <span class="calc-value negative">-{{ formatCurrency(payslip.deductions?.total_deductions) }}</span>

              </div>

            </div>

          </section>

          <!-- Net Pay Summary -->

          <section class="section summary-section">

            <div class="net-pay-card">

              <div class="summary-row">

                <span class="summary-label">Gross Pay:</span>

                <span class="summary-value">{{ formatCurrency(payslip.summary?.gross_pay) }}</span>

              </div>

              <div class="summary-row">

                <span class="summary-label">Total Deductions:</span>

                <span class="summary-value negative">-{{ formatCurrency(payslip.summary?.total_deductions) }}</span>

              </div>

              <div class="summary-divider"></div>

              <div class="summary-row net-pay-row">

                <span class="summary-label">Net Pay:</span>

                <span class="summary-value net-pay">{{ formatCurrency(payslip.summary?.net_pay) }}</span>

              </div>

            </div>

          </section>

          <!-- Status Badge -->

          <section class="section status-section">

            <div class="status-badge" :class="payslip.status">

              <span class="status-icon">{{ getStatusIcon(payslip.status) }}</span>

              <span class="status-text">{{ getStatusText(payslip.status) }}</span>

            </div>

          </section>

        </div>

        <div class="modal-footer">

          <button @click="close" class="btn-secondary">Close</button>

          <button v-if="!isPreview && payslip?.pdf_available" @click="downloadPdf" class="btn-primary">

            📄 Download PDF

          </button>

        </div>

      </div>

    </div>

  </transition>

</template>

<script>

import axios from 'axios'

export default {

  name: 'PayslipDetailModal',

  props: {

    visible: {

      type: Boolean,

      required: true

    },

    employeeId: {

      type: Number,

      required: false

    },

    payrollPeriod: {

      type: String,

      required: false

    }

  },

  data() {

    return {

      payslip: null,

      loading: false,

      error: null,

      isPreview: false

    }

  },

  watch: {

    visible(newVal) {

      if (newVal && this.employeeId && this.payrollPeriod) {

        this.fetchPayslipDetails()

      }

    }

  },

  methods: {

    async fetchPayslipDetails() {

      this.loading = true

      this.error = null

      

      try {

        const response = await axios.get(`/api/admin/payroll/employee/${this.employeeId}/payslip`, {

          params: {

            payroll_period: this.payrollPeriod

          }

        })

        

        this.payslip = response.data.data

        this.isPreview = response.data.is_preview || false

      } catch (err) {

        console.error('Failed to load payslip:', err)

        this.error = err.response?.data?.message || 'Failed to load payslip details'

      } finally {

        this.loading = false

      }

    },

    retry() {

      this.fetchPayslipDetails()

    },

    close() {

      this.$emit('close')

      // Reset state

      setTimeout(() => {

        this.payslip = null

        this.error = null

        this.isPreview = false

      }, 300)

    },

    downloadPdf() {

      if (this.payslip?.id) {

        window.open(`/api/payslips/${this.payslip.id}/download`, '_blank')

      }

    },

    formatCurrency(amount) {

      return new Intl.NumberFormat('en-ZM', {

        style: 'currency',

        currency: 'ZMW',

        minimumFractionDigits: 2

      }).format(amount || 0)

    },

    formatDate(dateString) {

      if (!dateString) return '—'

      return new Date(dateString).toLocaleDateString('en-ZM', {

        year: 'numeric',

        month: 'long',

        day: 'numeric'

      })

    },

    formatPeriod(period) {

      if (!period) return '—'

      const [year, month] = period.split('-')

      const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 

                          'July', 'August', 'September', 'October', 'November', 'December']

      return `${monthNames[parseInt(month) - 1]} ${year}`

    },

    getStatusIcon(status) {

      const icons = {

        'pending': '⏳',

        'paid': '✓',

        'draft': '📝',

        'generated': '📄'

      }

      return icons[status] || '•'

    },

    getStatusText(status) {

      return status ? status.charAt(0).toUpperCase() + status.slice(1) : 'Unknown'

    }

  }

}

</script>

<style scoped>

.modal-fade-enter-active, .modal-fade-leave-active {

  transition: opacity 0.3s ease;

}

.modal-fade-enter-from, .modal-fade-leave-to {

  opacity: 0;

}

.modal-overlay {

  position: fixed;

  top: 0;

  left: 0;

  width: 100%;

  height: 100%;

  background: rgba(0, 0, 0, 0.6);

  display: flex;

  align-items: center;

  justify-content: center;

  z-index: 1000;

  padding: 2rem;

  overflow-y: auto;

}

.modal-container {

  background: white;

  border-radius: 16px;

  max-width: 900px;

  width: 100%;

  max-height: 90vh;

  display: flex;

  flex-direction: column;

  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);

  animation: slideUp 0.3s ease;

}

@keyframes slideUp {

  from {

    transform: translateY(50px);

    opacity: 0;

  }

  to {

    transform: translateY(0);

    opacity: 1;

  }

}

.modal-header {

  display: flex;

  justify-content: space-between;

  align-items: center;

  padding: 1.5rem 2rem;

  border-bottom: 2px solid #e5e7eb;

  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

  color: white;

  border-radius: 16px 16px 0 0;

}

.modal-header h2 {

  margin: 0;

  font-size: 1.5rem;

  font-weight: 700;

}

.close-btn {

  background: none;

  border: none;

  font-size: 2rem;

  color: white;

  cursor: pointer;

  transition: transform 0.2s;

  padding: 0;

  width: 36px;

  height: 36px;

  display: flex;

  align-items: center;

  justify-content: center;

}

.close-btn:hover {

  transform: scale(1.2);

}

.modal-loading, .modal-error {

  padding: 4rem 2rem;

  text-align: center;

}

.spinner {

  width: 50px;

  height: 50px;

  border: 4px solid #f3f4f6;

  border-top: 4px solid #667eea;

  border-radius: 50%;

  animation: spin 1s linear infinite;

  margin: 0 auto 1rem;

}

@keyframes spin {

  0% { transform: rotate(0deg); }

  100% { transform: rotate(360deg); }

}

.retry-btn {

  margin-top: 1rem;

  padding: 0.75rem 1.5rem;

  background: #667eea;

  color: white;

  border: none;

  border-radius: 8px;

  cursor: pointer;

  font-weight: 600;

}

.modal-body {

  flex: 1;

  overflow-y: auto;

  padding: 2rem;

}

.preview-badge {

  background: #fef3c7;

  color: #92400e;

  padding: 1rem;

  border-radius: 8px;

  margin-bottom: 1.5rem;

  font-weight: 600;

  text-align: center;

  border: 2px solid #f59e0b;

}

.section {

  margin-bottom: 2rem;

}

.section h3 {

  font-size: 1.125rem;

  font-weight: 700;

  color: #1a202c;

  margin: 0 0 1rem 0;

  padding-bottom: 0.5rem;

  border-bottom: 2px solid #e5e7eb;

}

.info-grid {

  display: grid;

  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));

  gap: 1rem;

}

.info-item {

  display: flex;

  flex-direction: column;

  gap: 0.25rem;

}

.info-item .label {

  font-size: 0.875rem;

  color: #6b7280;

  font-weight: 500;

}

.info-item .value {

  font-size: 1rem;

  color: #1a202c;

  font-weight: 600;

}

.calculation-table {

  background: #f9fafb;

  border-radius: 8px;

  padding: 1rem;

}

.calc-row {

  display: flex;

  justify-content: space-between;

  padding: 0.75rem 0;

  border-bottom: 1px solid #e5e7eb;

}

.calc-row:last-child {

  border-bottom: none;

}

.calc-row.subsection {

  font-weight: 600;

  color: #374151;

  margin-top: 0.5rem;

}

.calc-row.indent {

  padding-left: 1.5rem;

  font-size: 0.95rem;

}

.calc-row.total {

  font-weight: 700;

  font-size: 1.125rem;

  background: white;

  margin: 0.5rem -1rem -1rem -1rem;

  padding: 1rem 2rem;

  border-radius: 0 0 8px 8px;

}

.calc-label {

  color: #374151;

}

.calc-value {

  font-weight: 600;

  color: #059669;

}

.calc-value.negative {

  color: #dc2626;

}

.net-pay-card {

  background: linear-gradient(135deg, #10b981 0%, #059669 100%);

  color: white;

  padding: 2rem;

  border-radius: 12px;

  box-shadow: 0 10px 30px rgba(16, 185, 129, 0.3);

}

.summary-row {

  display: flex;

  justify-content: space-between;

  padding: 0.75rem 0;

  font-size: 1.125rem;

}

.summary-row.net-pay-row {

  font-size: 1.75rem;

  font-weight: 700;

}

.summary-divider {

  height: 2px;

  background: rgba(255, 255, 255, 0.3);

  margin: 1rem 0;

}

.summary-value.negative {

  opacity: 0.9;

}

.status-section {

  display: flex;

  justify-content: center;

}

.status-badge {

  display: inline-flex;

  align-items: center;

  gap: 0.5rem;

  padding: 0.75rem 1.5rem;

  border-radius: 24px;

  font-weight: 600;

  font-size: 1rem;

}

.status-badge.pending {

  background: #fef3c7;

  color: #92400e;

}

.status-badge.paid {

  background: #d1fae5;

  color: #065f46;

}

.status-badge.draft {

  background: #e0e7ff;

  color: #3730a3;

}

.modal-footer {

  display: flex;

  justify-content: flex-end;

  gap: 1rem;

  padding: 1.5rem 2rem;

  border-top: 2px solid #e5e7eb;

  background: #f9fafb;

  border-radius: 0 0 16px 16px;

}

.btn-primary, .btn-secondary {

  padding: 0.75rem 1.5rem;

  border: none;

  border-radius: 8px;

  font-weight: 600;

  cursor: pointer;

  transition: all 0.2s;

}

.btn-primary {

  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

  color: white;

}

.btn-primary:hover {

  transform: translateY(-2px);

  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);

}

.btn-secondary {

  background: #e5e7eb;

  color: #374151;

}

.btn-secondary:hover {

  background: #d1d5db;

}

@media (max-width: 768px) {

  .modal-overlay {

    padding: 1rem;

  }

  

  .modal-container {

    max-height: 95vh;

  }

  

  .modal-header, .modal-body, .modal-footer {

    padding: 1rem;

  }

  

  .info-grid {

    grid-template-columns: 1fr;

  }

  

  .calc-row {

    font-size: 0.9rem;

  }

  

  .net-pay-card {

    padding: 1.5rem;

  }

}

</style>