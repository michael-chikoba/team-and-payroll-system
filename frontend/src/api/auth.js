import api from './axios'

export const authAPI = {
  async login(credentials) {
    const response = await api.post('/login', credentials)
    return response
  },

  async register(userData) {
    const response = await api.post('/register', userData)
    return response
  },

  async logout() {
    const response = await api.post('/logout')
    return response
  },

  async getUser() {
    const response = await api.get('/user')
    return response
  },

  async refreshToken() {
    const response = await api.post('/refresh-token')
    return response
  },

  async forgotPassword(data) {
    const response = await api.post('/forgot-password', data)
    return response
  },

  async resetPassword(data) {
    const response = await api.post('/reset-password', data)
    return response
  },

  async getLoginHistory(limit = 20) {
    const response = await api.get('/auth/login-history', {
      params: { limit }
    })
    return response
  }
}