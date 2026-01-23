import { format, parseISO, differenceInDays, addDays, isWeekend } from 'date-fns'

export const dateUtils = {
  formatDate(date, formatStr = 'MMM dd, yyyy') {
    if (!date) return ''
    return format(parseISO(date), formatStr)
  },

  formatDateTime(date, formatStr = 'MMM dd, yyyy HH:mm') {
    if (!date) return ''
    return format(parseISO(date), formatStr)
  },

  calculateWorkingDays(startDate, endDate) {
    if (!startDate || !endDate) return 0
    
    const start = parseISO(startDate)
    const end = parseISO(endDate)
    let workingDays = 0
    let current = start

    while (current <= end) {
      if (!isWeekend(current)) {
        workingDays++
      }
      current = addDays(current, 1)
    }

    return workingDays
  },

  isFutureDate(date) {
    return parseISO(date) > new Date()
  },

  isPastDate(date) {
    return parseISO(date) < new Date()
  },

  getCurrentPayrollPeriod() {
    const now = new Date()
    const month = now.toLocaleString('default', { month: 'long' })
    const year = now.getFullYear()
    return `${month} ${year}`
  }
}