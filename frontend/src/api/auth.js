import api from './axios'

export const authAPI = {
  login(credentials) {
    return api.post('/login', credentials)
  },
  
  register(userData) {
    return api.post('/register', userData)
  },
  
  logout() {
    return api.post('/logout')
  },
  
  getUser() {
    return api.get('/user')
  },
  
  forgotPassword(data) {
    return api.post('/forgot-password', data)
  },
  
  resetPassword(data) {
    return api.post('/reset-password', data)
  }
}