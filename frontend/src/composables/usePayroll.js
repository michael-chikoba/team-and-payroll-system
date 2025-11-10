import { ref, computed } from 'vue'
import { usePayrollStore } from '@/stores/payroll'

export function usePayroll() {
  const payrollStore = usePayrollStore()

  const processingPayroll = ref(false)

  const payrolls = computed(() => payrollStore.payrolls)
  const currentPayroll = computed(() => payrollStore.currentPayroll)
  const isLoading = computed(() => payrollStore.isLoading)

  async function processPayroll(payrollData) {
    processingPayroll.value = true
    try {
      const result = await payrollStore.processPayroll(payrollData)
      return result
    } catch (error) {
      throw error
    } finally {
      processingPayroll.value = false
    }
  }

  async function loadPayrollHistory(params = {}) {
    return await payrollStore.fetchPayrollHistory(params)
  }

  async function loadPayrollDetails(id) {
    return await payrollStore.fetchPayrollDetails(id)
  }

  function getPayrollStats(payroll) {
    if (!payroll) return {}

    return {
      totalEmployees: payroll.employee_count || 0,
      totalGross: payroll.total_gross || 0,
      totalNet: payroll.total_net || 0,
      totalDeductions: (payroll.total_gross || 0) - (payroll.total_net || 0),
      averageSalary: payroll.employee_count > 0 ? (payroll.total_gross / payroll.employee_count) : 0
    }
  }

  function formatPayrollPeriod(period) {
    return period.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())
  }

  return {
    // State
    payrolls,
    currentPayroll,
    isLoading,
    processingPayroll,

    // Actions
    processPayroll,
    loadPayrollHistory,
    loadPayrollDetails,

    // Computed
    getPayrollStats,
    formatPayrollPeriod
  }
}