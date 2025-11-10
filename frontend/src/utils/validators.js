export const validators = {
  required(value) {
    return !!value || 'This field is required'
  },

  email(value) {
    const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return pattern.test(value) || 'Please enter a valid email address'
  },

  minLength(value, length) {
    return value.length >= length || `Must be at least ${length} characters`
  },

  maxLength(value, length) {
    return value.length <= length || `Must be less than ${length} characters`
  },

  password(value) {
    const hasUpperCase = /[A-Z]/.test(value)
    const hasLowerCase = /[a-z]/.test(value)
    const hasNumbers = /\d/.test(value)
    const hasSpecialChar = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(value)
    
    if (value.length < 8) return 'Password must be at least 8 characters'
    if (!hasUpperCase) return 'Password must contain at least one uppercase letter'
    if (!hasLowerCase) return
        if (!hasNumbers) return 'Password must contain at least one number'
    if (!hasSpecialChar) return 'Password must contain at least one special character'
    return true
  },

  phone(value) {
    const pattern = /^[\+]?[1-9][\d]{0,15}$/
    return pattern.test(value.replace(/\D/g, '')) || 'Please enter a valid phone number'
  },

  positiveNumber(value) {
    return value > 0 || 'Must be a positive number'
  },

  dateAfter(startDate, endDate) {
    return new Date(endDate) >= new Date(startDate) || 'End date must be after start date'
  },

  fileSize(file, maxSizeInMB = 5) {
    const maxSize = maxSizeInMB * 1024 * 1024
    return file.size <= maxSize || `File size must be less than ${maxSizeInMB}MB`
  },

  fileType(file, allowedTypes = ['image/jpeg', 'image/png', 'application/pdf']) {
    return allowedTypes.includes(file.type) || `File type must be one of: ${allowedTypes.join(', ')}`
  }
}