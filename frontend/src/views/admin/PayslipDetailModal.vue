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
              <div class="calc-row">
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
            </div>
          </section>
        </div>

        <div class="modal-footer">
          <button @click="close" class="btn-secondary">Close</button>
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
    visible: Boolean,
    employeeId: Number,
    payrollPeriod: String
  },
  data() {
    return {
      payslip: null,
      loading: false,
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
      try {
        const response = await axios.get(`/api/admin/payroll/employee/${this.employeeId}/payslip`, {
          params: { payroll_period: this.payrollPeriod }
        })
        this.payslip = response.data.data
        this.isPreview = response.data.is_preview
      } catch (err) {
        console.error('Failed to load payslip:', err)
      } finally {
        this.loading = false
      }
    },
    close() { this.$emit('close') },
    formatCurrency(val) {
      return new Intl.NumberFormat('en-ZM', { style: 'currency', currency: 'ZMW' }).format(val || 0)
    }
  }
}
</script>

<style scoped>
/* (Keep existing styles) */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-container { background: white; border-radius: 12px; width: 90%; max-width: 600px; max-height: 90vh; overflow-y: auto; display: flex; flex-direction: column; }
.modal-header { padding: 1.5rem; background: #667eea; color: white; display: flex; justify-content: space-between; align-items: center; }
.modal-body { padding: 1.5rem; }
.section { margin-bottom: 1.5rem; }
.section h3 { font-size: 1rem; color: #4b5563; border-bottom: 1px solid #e5e7eb; padding-bottom: 0.5rem; margin-bottom: 1rem; }
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.calculation-table { background: #f9fafb; padding: 1rem; border-radius: 8px; }
.calc-row { display: flex; justify-content: space-between; padding: 0.5rem 0; font-size: 0.95rem; }
.calc-row.total { font-weight: 700; border-top: 1px solid #e5e7eb; margin-top: 0.5rem; padding-top: 1rem; }
.calc-row.indent { padding-left: 1rem; font-size: 0.9rem; color: #6b7280; }
.calc-value.negative { color: #dc2626; }
.net-pay-card { background: #10b981; color: white; padding: 1.5rem; border-radius: 12px; text-align: center; font-size: 1.5rem; font-weight: bold; }
.modal-footer { padding: 1rem; border-top: 1px solid #e5e7eb; text-align: right; }
.close-btn { background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; }
.btn-secondary { padding: 0.5rem 1rem; background: #e5e7eb; border: none; border-radius: 6px; cursor: pointer; }
.preview-badge { background: #fffbeb; color: #92400e; padding: 0.75rem; text-align: center; border-radius: 6px; margin-bottom: 1rem; border: 1px solid #fcd34d; }
</style>