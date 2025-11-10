import { defineStore } from 'pinia'
import { ref } from 'vue'
import { payslipAPI } from '@/api/payslip'

export const usePayslipStore = defineStore('payslip', () => {
  const payslips = ref([])
  const currentPayslip = ref(null)
  const isLoading = ref(false)

  async function fetchPayslips(params = {}) {
    try {
      isLoading.value = true
      const response = await payslipAPI.getPayslips(params)
      payslips.value = response.data.data
    } catch (error) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  async function fetchPayslip(id) {
    try {
      isLoading.value = true
      const response = await payslipAPI.getPayslip(id)
      currentPayslip.value = response.data.data
    } catch (error) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  async function downloadPayslip(id) {
    try {
      const response = await payslipAPI.downloadPayslip(id)
      
      // Create blob and download
      const blob = new Blob([response.data], { type: 'application/pdf' })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `payslip-${id}.pdf`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
      
    } catch (error) {
      throw error
    }
  }

  async function generatePayslips(data) {
    try {
      isLoading.value = true
      const response = await payslipAPI.generatePayslips(data)
      return response
    } catch (error) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  async function bulkDownloadPayslips(data) {
    try {
      const response = await payslipAPI.bulkDownloadPayslips(data)
      
      // Create blob and download
      const blob = new Blob([response.data], { type: 'application/zip' })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `payslips-${new Date().toISOString().split('T')[0]}.zip`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
      
    } catch (error) {
      throw error
    }
  }

  function getPayslipById(id) {
    return payslips.value.find(payslip => payslip.id === id)
  }

  return {
    payslips,
    currentPayslip,
    isLoading,
    fetchPayslips,
    fetchPayslip,
    downloadPayslip,
    generatePayslips,
    bulkDownloadPayslips,
    getPayslipById
  }
})