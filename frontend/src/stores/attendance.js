import { defineStore } from 'pinia'
import { ref } from 'vue'
import { attendanceAPI } from '@/api/attendance'

export const useAttendanceStore = defineStore('attendance', () => {
  const attendances = ref([])
  const summary = ref({})
  const isLoading = ref(false)
  const isClockedIn = ref(false)

  async function clockIn() {
    try {
      const response = await attendanceAPI.clockIn()
      isClockedIn.value = true
      return response
    } catch (error) {
      throw error
    }
  }

  async function clockOut() {
    try {
      const response = await attendanceAPI.clockOut()
      isClockedIn.value = false
      return response
    } catch (error) {
      throw error
    }
  }

  async function fetchAttendanceHistory(month, year) {
    try {
      isLoading.value = true
      const response = await attendanceAPI.getHistory({ month, year })
      attendances.value = response.data.data
    } catch (error) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  async function fetchSummary(month, year) {
    try {
      const response = await attendanceAPI.getSummary({ month, year })
      summary.value = response.data
    } catch (error) {
      throw error
    }
  }

  async function recordAttendance(attendanceData) {
    try {
      const response = await attendanceAPI.recordAttendance(attendanceData)
      attendances.value.unshift(response.data.attendance)
      return response
    } catch (error) {
      throw error
    }
  }

  async function updateAttendance(id, attendanceData) {
    try {
      const response = await attendanceAPI.updateAttendance(id, attendanceData)
      const index = attendances.value.findIndex(att => att.id === id)
      if (index !== -1) {
        attendances.value[index] = response.data.attendance
      }
      return response
    } catch (error) {
      throw error
    }
  }

  function checkClockStatus() {
    // This would typically check with the backend
    // For now, we'll assume false
    isClockedIn.value = false
  }

  return {
    attendances,
    summary,
    isLoading,
    isClockedIn,
    clockIn,
    clockOut,
    fetchAttendanceHistory,
    fetchSummary,
    recordAttendance,
    updateAttendance,
    checkClockStatus
  }
})