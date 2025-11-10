import api from './axios'

export const attendanceAPI = {
  clockIn() {
    return api.post('/employee/attendance/clock-in')
  },
  
  clockOut() {
    return api.post('/employee/attendance/clock-out')
  },
  
  getHistory(params = {}) {
    return api.get('/employee/attendance/history', { params })
  },
  
  getSummary(params = {}) {
    return api.get('/employee/attendance/summary', { params })
  },
  
  recordAttendance(data) {
    return api.post('/employee/attendance', data)
  },
  
  updateAttendance(id, data) {
    return api.put(`/employee/attendance/${id}`, data)
  }
}