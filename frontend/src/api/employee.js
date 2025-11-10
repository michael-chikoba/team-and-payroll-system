import api from './axios'

export const employeeAPI = {
  getEmployees() {
    return api.get('/admin/employees')
  },
  
  getEmployee(id) {
    return api.get(`/admin/employees/${id}`)
  },
  
  createEmployee(data) {
    return api.post('/admin/employees', data)
  },
  
  updateEmployee(id, data) {
    return api.put(`/admin/employees/${id}`, data)
  },
  
  deleteEmployee(id) {
    return api.delete(`/admin/employees/${id}`)
  },
  
  getProfile() {
    return api.get('/profile')
  },
  
  updateProfile(data) {
    return api.put('/profile', data)
  }
}