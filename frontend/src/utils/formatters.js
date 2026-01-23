export const formatters = {
  formatCurrency(amount, currency = 'ZMW') {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency
    }).format(amount)
  },

  formatPercentage(value, decimals = 1) {
    return `${parseFloat(value).toFixed(decimals)}%`
  },

  formatNumber(value, decimals = 0) {
    return new Intl.NumberFormat('en-US', {
      minimumFractionDigits: decimals,
      maximumFractionDigits: decimals
    }).format(value)
  },

  truncateText(text, maxLength = 50) {
    if (!text) return ''
    if (text.length <= maxLength) return text
    return text.substring(0, maxLength) + '...'
  },

  capitalizeFirst(string) {
    if (!string) return ''
    return string.charAt(0).toUpperCase() + string.slice(1)
  },

  formatPhoneNumber(phoneNumber) {
    const cleaned = phoneNumber.replace(/\D/g, '')
    const match = cleaned.match(/^(\d{3})(\d{3})(\d{4})$/)
    if (match) {
      return '(' + match[1] + ') ' + match[2] + '-' + match[3]
    }
    return phoneNumber
  }
}