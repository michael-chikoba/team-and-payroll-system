export const apiHandler = {
  async handleResponse(response) {
    if (response.status >= 200 && response.status < 300) {
      return response.data
    } else {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
  },

  handleError(error) {
    if (error.response) {
      // Server responded with error status
      const message = error.response.data?.message || 'An error occurred'
      const errors = error.response.data?.errors || {}
      
      return {
        message,
        errors,
        status: error.response.status,
        data: error.response.data
      }
    } else if (error.request) {
      // Request made but no response received
      return {
        message: 'Network error. Please check your connection.',
        errors: {},
        status: 0
      }
    } else {
      // Something else happened
      return {
        message: error.message || 'An unexpected error occurred',
        errors: {},
        status: 0
      }
    }
  },

  async withErrorHandling(apiCall, errorCallback = null) {
    try {
      const response = await apiCall()
      return this.handleResponse(response)
    } catch (error) {
      const handledError = this.handleError(error)
      
      if (errorCallback) {
        errorCallback(handledError)
      } else {
        console.error('API Error:', handledError)
      }
      
      throw handledError
    }
  }
}