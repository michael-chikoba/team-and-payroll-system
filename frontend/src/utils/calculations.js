export const calculations = {
  calculateNetPay(grossPay, deductions) {
    const totalDeductions = Object.values(deductions).reduce((sum, amount) => sum + amount, 0)
    return grossPay - totalDeductions
  },

  calculateOvertimePay(regularHours, overtimeHours, hourlyRate, overtimeMultiplier = 1.5) {
    const regularPay = regularHours * hourlyRate
    const overtimePay = overtimeHours * hourlyRate * overtimeMultiplier
    return regularPay + overtimePay
  },

  calculateTax(income, taxBrackets) {
    let tax = 0
    let remainingIncome = income

    for (const bracket of taxBrackets) {
      if (remainingIncome <= 0) break

      const bracketMax = bracket.max || Infinity
      const bracketMin = bracket.min || 0
      const bracketRange = bracketMax - bracketMin
      const taxableInBracket = Math.min(remainingIncome, bracketRange)

      tax += taxableInBracket * (bracket.rate / 100)
      remainingIncome -= taxableInBracket
    }

    return tax
  },

  calculateLeaveDays(startDate, endDate, excludeWeekends = true) {
    const start = new Date(startDate)
    const end = new Date(endDate)
    let days = 0
    let current = new Date(start)

    while (current <= end) {
      if (!excludeWeekends || (current.getDay() !== 0 && current.getDay() !== 6)) {
        days++
      }
      current.setDate(current.getDate() + 1)
    }

    return days
  },

  calculateAttendanceRate(presentDays, workingDays) {
    if (workingDays === 0) return 0
    return (presentDays / workingDays) * 100
  },

  roundToDecimal(value, decimals = 2) {
    return Math.round(value * Math.pow(10, decimals)) / Math.pow(10, decimals)
  }
}