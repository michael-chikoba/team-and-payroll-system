import { defineStore } from 'pinia'
import { ref } from 'vue'
import { payrollAPI } from '@/api/payroll'

export const usePayrollStore = defineStore('payroll', () => {
  const payrolls = ref([])
  const currentPayroll = ref(null)
  const isLoading = ref(false)

  async function fetchPayrollHistory(params = {}) {
    try {
      isLoading.value = true
      const response = await payrollAPI.getPayrollHistory(params)
      payrolls.value = response.data.data
    } catch (error) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  async function fetchPayrollCycles() {
    try {
      const response = await payrollAPI.getPayrollCycles()
      return response.data
    } catch (error) {
      throw error
    }
  }

  async function processPayroll(payrollData) {
    try {
      isLoading.value = true
      const response = await payrollAPI.processPayroll(payrollData)
      payrolls.value.unshift(response.data.payroll)
      return response
    } catch (error) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  async function fetchPayrollDetails(id) {
    try {
      isLoading.value = true
      const response = await payrollAPI.getPayrollDetails(id)
      currentPayroll.value = response.data.data
    } catch (error) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  function getPayrollById(id) {
    return payrolls.value.find(payroll => payroll.id === id)
  }

  return {
    payrolls,
    currentPayroll,
    isLoading,
    fetchPayrollHistory,
    fetchPayrollCycles,
    processPayroll,
    fetchPayrollDetails,
    getPayrollById
  }
})