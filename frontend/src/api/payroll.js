import api from './axios'

export const payrollAPI = {
  processPayroll(data) {
    return api.post('/admin/payroll/process', data)
  },
  
  getPayrollHistory(params = {}) {
    return api.get('/admin/payroll/history', { params })
  },
  
  getPayrollCycles() {
    return api.get('/admin/payroll/cycles')
  },
  
  getPayrollDetails(id) {
    return api.get(`/admin/payroll/${id}`)
  }
}