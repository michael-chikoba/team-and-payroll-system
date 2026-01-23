import axios from 'axios'

// Create axios instance with base configuration
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'https://archangel.darth.cloud/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  withCredentials: true, // Important for cookies/sessions
})

// Request interceptor - logs all outgoing requests
api.interceptors.request.use(
  (config) => {
   
    
    return config
  },
  (error) => {
    console.error('❌ Request interceptor error:', error)
    return Promise.reject(error)
  }
)

// Response interceptor - logs all responses
api.interceptors.response.use(
  (response) => {
    
   
    
    return response
  },
  (error) => {
    console.log('=== AXIOS ERROR RESPONSE ===')
    
    if (error.response) {
      // Server responded with error status
      console.error('Error Response:')
      console.error('  URL:', error.config?.url)
      console.error('  Status:', error.response.status)
      console.error('  Status Text:', error.response.statusText)
      console.error('  Headers:', error.response.headers)
      console.error('  Data:', error.response.data)
      console.error('  Message:', error.response.data?.message)
      console.error('  Errors:', error.response.data?.errors)
    } else if (error.request) {
      // Request made but no response received
      console.error('No Response Received:')
      console.error('  Request:', error.request)
      console.error('  Message:', error.message)
      console.error('  Code:', error.code)
    } else {
      // Error in request setup
      console.error('Request Setup Error:')
      console.error('  Message:', error.message)
    }
    
   
    
    return Promise.reject(error)
  }
)

// Helper function to ensure routes start with /api/
const ensureApiRoute = (route) => {
  if (!route) return '/api/';
  if (route.startsWith('/api/')) return route;
  if (route.startsWith('api/')) return `/${route}`;
  if (route.startsWith('/')) return `/api${route}`;
  return `/api/${route}`;
}

export const authAPI = {
  async login(credentials) {
   
    
    try {
      const response = await api.post(ensureApiRoute('/login'), {
        email: credentials.email,
        password: credentials.password,
      })
      
    //  console.log('✅ Login API call successful')
      return response
    } catch (error) {
      console.error('❌ Login API call failed:', error.message)
      throw error
    }
  },

  async register(userData) {
    
    
    try {
      const response = await api.post(ensureApiRoute('/register'), userData)
    //  console.log('✅ Register API call successful')
      return response
    } catch (error) {
      console.error('❌ Register API call failed:', error.message)
      throw error
    }
  },

  async logout() {
 //   console.log('👋 authAPI.logout() called')
    
    try {
      const response = await api.post(ensureApiRoute('/logout'))
     // console.log('✅ Logout API call successful')
      return response
    } catch (error) {
      console.error('❌ Logout API call failed:', error.message)
      throw error
    }
  },

  async getUser() {
    console.log('👤 authAPI.getUser() called')
    
    try {
      const response = await api.get(ensureApiRoute('/user'))
  //    console.log('✅ Get user API call successful')
      return response
    } catch (error) {
      console.error('❌ Get user API call failed:', error.message)
      throw error
    }
  },

  async forgotPassword(data) {
//    console.log('🔑 authAPI.forgotPassword() called')
    
    try {
      const response = await api.post(ensureApiRoute('/forgot-password'), data)
     // console.log('✅ Forgot password API call successful')
      return response
    } catch (error) {
      console.error('❌ Forgot password API call failed:', error.message)
      throw error
    }
  },

  async resetPassword(data) {
    console.log('🔐 authAPI.resetPassword() called')
    
    try {
      const response = await api.post(ensureApiRoute('/reset-password'), data)
     // console.log('✅ Reset password API call successful')
      return response
    } catch (error) {
      console.error('❌ Reset password API call failed:', error.message)
      throw error
    }
  },

  async verifyEmail(data) {
   // console.log('✉️ authAPI.verifyEmail() called')
    
    try {
      const response = await api.post(ensureApiRoute('/email/verify'), data)
    //  console.log('✅ Verify email API call successful')
      return response
    } catch (error) {
      console.error('❌ Verify email API call failed:', error.message)
      throw error
    }
  },

  async resendVerification() {
  //  console.log('📧 authAPI.resendVerification() called')
    
    try {
      const response = await api.post(ensureApiRoute('/email/resend'))
   //   console.log('✅ Resend verification API call successful')
      return response
    } catch (error) {
      console.error('❌ Resend verification API call failed:', error.message)
      throw error
    }
  },
}

// Export other API modules with the same pattern
export const userAPI = {
  async updateProfile(data) {
    try {
      return await api.put(ensureApiRoute('/user/profile'), data)
    } catch (error) {
      throw error
    }
  },

  async updatePassword(data) {
    try {
      return await api.put(ensureApiRoute('/user/password'), data)
    } catch (error) {
      throw error
    }
  },

  async updateAvatar(data) {
    try {
      const formData = new FormData()
      formData.append('avatar', data)
      return await api.post(ensureApiRoute('/user/avatar'), formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })
    } catch (error) {
      throw error
    }
  }
}

export const csrfAPI = {
  async getCsrfCookie() {
    try {
      const response = await api.get(ensureApiRoute('/sanctum/csrf-cookie'))
     // console.log('✅ CSRF cookie fetched successfully')
      return response
    } catch (error) {
      console.error('❌ CSRF cookie fetch failed:', error.message)
      throw error
    }
  }
}

export default api