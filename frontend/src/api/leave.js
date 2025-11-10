import api from './axios'

export const leaveAPI = {
  getLeaves() {
    return api.get('/leaves')
  },
  
  getPendingLeaves() {
    return api.get('/manager/leaves/pending')
  },
  
  getBalances() {
    return api.get('/employee/leaves/balance')
  },
  
  applyLeave(data) {
    return api.post('/employee/leaves', data)
  },
  
  approveLeave(id, data) {
    return api.post(`/manager/leaves/${id}/approve`, data)
  },
  
  rejectLeave(id, data) {
    return api.post(`/manager/leaves/${id}/reject`, data)
  }
}