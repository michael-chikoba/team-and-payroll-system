import api from './axios'

export const reportAPI = {
  getPayrollReport(params = {}) {
    return api.get('/admin/reports/payroll', { params })
  },
  
  getAttendanceReport(params = {}) {
    return api.get('/admin/reports/attendance', { params })
  },
  
  getLeaveReport(params = {}) {
    return api.get('/admin/reports/leave', { params })
  },
  
  getTeamReport(params = {}) {
    return api.get('/manager/reports/team', { params })
  },
  
  getProductivityReport(params = {}) {
    return api.get('/manager/reports/productivity', { params })
  },
  
  exportReport(type, format = 'pdf', params = {}) {
    return api.get(`/reports/export/${type}`, {
      params: { ...params, format },
      responseType: 'blob'
    })
  }
}