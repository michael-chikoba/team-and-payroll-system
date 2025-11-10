import api from './axios'

export const payslipAPI = {
  getPayslips(params = {}) {
    return api.get('/employee/payslips', { params })
  },
  
  getPayslip(id) {
    return api.get(`/employee/payslips/${id}`)
  },
  
  downloadPayslip(id) {
    return api.get(`/employee/payslips/${id}/download`, {
      responseType: 'blob'
    })
  },
  
  generatePayslips(data) {
    return api.post('/admin/payslips/generate', data)
  },
  
  bulkDownloadPayslips(data) {
    return api.post('/admin/payslips/bulk-download', data, {
      responseType: 'blob'
    })
  }
}