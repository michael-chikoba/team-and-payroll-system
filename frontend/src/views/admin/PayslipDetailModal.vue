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

        <div v-else-if="payslip" class="modal-body">
          <!-- Currency Info Badge -->
          <div class="currency-info" v-if="currentCurrency">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="1" x2="12" y2="23"/>
              <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
            Currency: {{ getCurrencySymbol(currentCurrency) }} {{ currentCurrency }}
          </div>

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
                <span class="label">Position:</span>
                <span class="value">{{ payslip.employee?.position }}</span>
              </div>
              <div class="info-item">
                <span class="label">Department:</span>
                <span class="value">{{ payslip.employee?.department }}</span>
              </div>
              <div class="info-item" v-if="payslip.business_name">
                <span class="label">Business:</span>
                <span class="value">{{ payslip.business_name }}</span>
              </div>
            </div>
          </section>

          <!-- Earnings -->
          <section class="section">
            <h3>Earnings</h3>
            <div class="calculation-table">
              <div class="calc-row">
                <span class="calc-label">Basic Salary</span>
                <span class="calc-value">{{ formatCurrency(payslip.earnings?.basic_salary) }}</span>
              </div>
              
              <!-- Allowances -->
              <div v-if="payslip.earnings?.house_allowance > 0" class="calc-row indent">
                <span class="calc-label">Housing Allowance</span>
                <span class="calc-value">{{ formatCurrency(payslip.earnings?.house_allowance) }}</span>
              </div>
              <div v-if="payslip.earnings?.transport_allowance > 0" class="calc-row indent">
                <span class="calc-label">Transport Allowance</span>
                <span class="calc-value">{{ formatCurrency(payslip.earnings?.transport_allowance) }}</span>
              </div>
              <div v-if="payslip.earnings?.lunch_allowance > 0" class="calc-row indent">
                <span class="calc-label">Lunch/Other Allowance</span>
                <span class="calc-value">{{ formatCurrency(payslip.earnings?.lunch_allowance) }}</span>
              </div>
              
              <div v-if="payslip.earnings?.overtime_pay > 0" class="calc-row">
                <span class="calc-label">Overtime Pay</span>
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

          <!-- DYNAMIC DEDUCTIONS SECTION -->
          <section class="section">
            <h3>Deductions</h3>
            <div class="calculation-table">
              <!-- PAYE -->
              <div class="calc-row" v-if="payslip.deductions?.paye > 0">
                <span class="calc-label">PAYE (Tax)</span>
                <span class="calc-value negative">-{{ formatCurrency(payslip.deductions?.paye) }}</span>
              </div>

              <!-- Statutory Deductions Loop -->
              <template v-if="payslip.deductions?.statutory && payslip.deductions.statutory.length">
                 <div 
                    v-for="(deduction, idx) in payslip.deductions.statutory" 
                    :key="idx" 
                    class="calc-row"
                 >
                    <span class="calc-label">{{ deduction.name }}</span>
                    <span class="calc-value negative">-{{ formatCurrency(deduction.amount) }}</span>
                 </div>
              </template>

              <!-- Other Deductions -->
              <div v-if="payslip.deductions?.loan_deductions > 0" class="calc-row">
                <span class="calc-label">Loan Repayment</span>
                <span class="calc-value negative">-{{ formatCurrency(payslip.deductions?.loan_deductions) }}</span>
              </div>
              <div v-if="payslip.deductions?.advance_deductions > 0" class="calc-row">
                <span class="calc-label">Salary Advance</span>
                <span class="calc-value negative">-{{ formatCurrency(payslip.deductions?.advance_deductions) }}</span>
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

          <!-- Net Pay -->
          <section class="section summary-section">
            <div class="net-pay-card">
              <div class="summary-row net-pay-row">
                <span class="summary-label">Net Pay:</span>
                <span class="summary-value net-pay">{{ formatCurrency(payslip.summary?.net_pay) }}</span>
              </div>
              <div class="payment-info" v-if="payslip.payment_date">
                Payment Date: {{ formatDate(payslip.payment_date) }}
              </div>
            </div>
          </section>
        </div>

        <div class="modal-footer">
          <button @click="close" class="btn-secondary">Close</button>
          <button v-if="isPreview" @click="processPayroll" class="btn-primary">Process Payroll</button>
        </div>
      </div>
    </div>
  </transition>
</template>

<script>
import axios from 'axios'
import { useBusinessStore } from '@/stores/business'

export default {
  name: 'PayslipDetailModal',
  props: {
    visible: Boolean,
    employeeId: Number,
    payrollPeriod: String,
    businessId: Number
  },
  setup() {
    const businessStore = useBusinessStore()
    return { businessStore }
  },
  data() {
    return {
      payslip: null,
      loading: false,
      isPreview: false,
      // Currency symbols for African currencies
      currencySymbols: {
        // West African
        'NGN': '₦', 'GHS': '₵', 'XOF': 'CFA', 'GMD': 'D', 'LRD': 'L$', 
        'SLL': 'Le', 'MRU': 'UM', 'CVE': '$',
        // East African
        'KES': 'KSh', 'UGX': 'USh', 'TZS': 'TSh', 'RWF': 'FRw', 
        'BIF': 'FBu', 'ETB': 'Br', 'SOS': 'S', 'DJF': 'Fdj', 
        'ERN': 'Nfk', 'SCR': '₨', 'COM': 'CF', 'MGA': 'Ar', 'MUR': '₨',
        // Southern African
        'ZAR': 'R', 'ZMW': 'K', 'BWP': 'P', 'NAD': 'N$', 
        'SZL': 'L', 'LSL': 'L', 'MWK': 'MK', 'MZN': 'MT', 
        'AOA': 'Kz', 'ZWL': 'ZiG',
        // Central African
        'XAF': 'FCFA', 'CDF': 'FC', 'STN': 'Db',
        // North African
        'EGP': 'E£', 'MAD': 'DH', 'DZD': 'DA', 'TND': 'DT', 
        'LYD': 'LD', 'SDG': '£S'
      }
    }
  },
  computed: {
    currentCurrency() {
      // Try to get currency from payslip data first
      if (this.payslip?.currency_code) {
        return this.payslip.currency_code
      }
      // Then from business store
      if (this.businessStore.currentBusiness?.currency_code) {
        return this.businessStore.currentBusiness.currency_code
      }
      // Fallback to ZMW
      return 'ZMW'
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
    getCurrencySymbol(currencyCode) {
      return this.currencySymbols[currencyCode] || currencyCode || '$'
    },
    async fetchPayslipDetails() {
      this.loading = true
      try {
        const params = { 
          payroll_period: this.payrollPeriod,
          business_id: this.businessId || this.businessStore.currentBusiness?.id
        }
        const response = await axios.get(`/api/admin/payroll/employee/${this.employeeId}/payslip`, {
          params: params
        })
        this.payslip = response.data.data
        this.isPreview = response.data.is_preview
      } catch (err) {
        console.error('Failed to load payslip:', err)
      } finally {
        this.loading = false
      }
    },
    close() { 
      this.$emit('close') 
    },
    async processPayroll() {
      this.$emit('process-payroll', this.employeeId)
      this.close()
    },
    formatCurrency(val) {
      if (val === null || val === undefined) return '—'
      return new Intl.NumberFormat('en-ZM', { 
        style: 'currency', 
        currency: this.currentCurrency, 
        minimumFractionDigits: 2 
      }).format(val || 0).replace(/[A-Z]{3}/g, this.getCurrencySymbol(this.currentCurrency))
    },
    formatDate(date) {
      if (!date) return '—'
      return new Date(date).toLocaleDateString('en-GB')
    }
  }
}
</script>

<style scoped>
.modal-overlay { 
  position: fixed; 
  inset: 0; 
  background: rgba(0,0,0,0.5); 
  display: flex; 
  align-items: center; 
  justify-content: center; 
  z-index: 1000; 
}
.modal-container { 
  background: white; 
  border-radius: 12px; 
  width: 90%; 
  max-width: 600px; 
  max-height: 90vh; 
  overflow-y: auto; 
  display: flex; 
  flex-direction: column; 
}
.modal-header { 
  padding: 1.5rem; 
  background: #667eea; 
  color: white; 
  display: flex; 
  justify-content: space-between; 
  align-items: center; 
  border-radius: 12px 12px 0 0;
}
.modal-header h2 { 
  margin: 0; 
  font-size: 1.25rem; 
}
.modal-loading {
  padding: 3rem;
  text-align: center;
}
.modal-body { 
  padding: 1.5rem; 
}
.currency-info {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: #fef3c7;
  color: #92400e;
  padding: 0.4rem 0.8rem;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 600;
  margin-bottom: 1rem;
}
.section { 
  margin-bottom: 1.5rem; 
}
.section h3 { 
  font-size: 1rem; 
  color: #4b5563; 
  border-bottom: 2px solid #e5e7eb; 
  padding-bottom: 0.5rem; 
  margin-bottom: 1rem; 
}
.info-grid { 
  display: grid; 
  grid-template-columns: 1fr 1fr; 
  gap: 1rem; 
}
.info-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.info-item .label {
  font-size: 0.7rem;
  font-weight: 600;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.info-item .value {
  font-size: 0.9rem;
  font-weight: 500;
  color: #1f2937;
}
.calculation-table { 
  background: #f9fafb; 
  padding: 1rem; 
  border-radius: 8px; 
}
.calc-row { 
  display: flex; 
  justify-content: space-between; 
  padding: 0.5rem 0; 
  font-size: 0.95rem; 
}
.calc-row.total { 
  font-weight: 700; 
  border-top: 2px solid #e5e7eb; 
  margin-top: 0.5rem; 
  padding-top: 1rem; 
}
.calc-row.indent { 
  padding-left: 1rem; 
  font-size: 0.9rem; 
  color: #6b7280; 
}
.calc-value.negative { 
  color: #dc2626; 
}
.net-pay-card { 
  background: linear-gradient(135deg, #10b981, #059669);
  color: white; 
  padding: 1.5rem; 
  border-radius: 12px; 
  text-align: center; 
}
.summary-row {
  display: flex;
  justify-content: center;
  align-items: baseline;
  gap: 0.5rem;
}
.summary-label {
  font-size: 1rem;
  font-weight: 600;
  opacity: 0.9;
}
.summary-value.net-pay {
  font-size: 2rem;
  font-weight: 800;
}
.payment-info {
  margin-top: 0.75rem;
  font-size: 0.8rem;
  opacity: 0.8;
  border-top: 1px solid rgba(255,255,255,0.2);
  padding-top: 0.75rem;
}
.modal-footer { 
  padding: 1rem 1.5rem; 
  border-top: 1px solid #e5e7eb; 
  text-align: right; 
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}
.close-btn { 
  background: none; 
  border: none; 
  color: white; 
  font-size: 1.5rem; 
  cursor: pointer; 
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  transition: background 0.2s;
}
.close-btn:hover {
  background: rgba(255,255,255,0.1);
}
.btn-secondary { 
  padding: 0.5rem 1rem; 
  background: #e5e7eb; 
  border: none; 
  border-radius: 6px; 
  cursor: pointer;
  font-weight: 600;
  transition: background 0.2s;
}
.btn-secondary:hover {
  background: #d1d5db;
}
.btn-primary {
  padding: 0.5rem 1rem;
  background: #667eea;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 600;
  transition: background 0.2s;
}
.btn-primary:hover {
  background: #5a67d8;
}
.preview-badge { 
  background: #fffbeb; 
  color: #92400e; 
  padding: 0.75rem; 
  text-align: center; 
  border-radius: 6px; 
  margin-bottom: 1rem; 
  border: 1px solid #fcd34d; 
  font-size: 0.875rem;
}
.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e5e7eb;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
.modal-fade-enter-active, .modal-fade-leave-active {
  transition: opacity 0.25s ease;
}
.modal-fade-enter-from, .modal-fade-leave-to {
  opacity: 0;
}

/* Responsive */
@media (max-width: 640px) {
  .modal-container {
    width: 95%;
    max-height: 95vh;
  }
  .info-grid {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }
  .summary-value.net-pay {
    font-size: 1.5rem;
  }
  .modal-footer {
    flex-direction: column;
  }
  .modal-footer button {
    width: 100%;
  }
}
</style>