import { defineStore } from 'pinia'
import { ref } from 'vue'
import { leaveAPI } from '@/api/leave'

export const useLeaveStore = defineStore('leave', () => {
  const leaves = ref([])
  const pendingLeaves = ref([])
  const leaveBalances = ref({})
  const isLoading = ref(false)

  async function fetchLeaves() {
    try {
      isLoading.value = true
      const response = await leaveAPI.getLeaves()
      leaves.value = response.data.data
    } catch (error) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  async function fetchPendingLeaves() {
    try {
      isLoading.value = true
      const response = await leaveAPI.getPendingLeaves()
      pendingLeaves.value = response.data.data
    } catch (error) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  async function fetchLeaveBalances() {
    try {
      const response = await leaveAPI.getBalances()
      leaveBalances.value = response.data.balances
    } catch (error) {
      throw error
    }
  }

  async function applyLeave(leaveData) {
    try {
      const response = await leaveAPI.applyLeave(leaveData)
      leaves.value.unshift(response.data.leave)
      return response
    } catch (error) {
      throw error
    }
  }

  async function approveLeave(id, data) {
    try {
      const response = await leaveAPI.approveLeave(id, data)
      
      // Update in leaves list
      const index = leaves.value.findIndex(leave => leave.id === id)
      if (index !== -1) {
        leaves.value[index] = response.data.leave
      }
      
      // Remove from pending leaves
      pendingLeaves.value = pendingLeaves.value.filter(leave => leave.id !== id)
      
      return response
    } catch (error) {
      throw error
    }
  }

  async function rejectLeave(id, data) {
    try {
      const response = await leaveAPI.rejectLeave(id, data)
      
      // Update in leaves list
      const index = leaves.value.findIndex(leave => leave.id === id)
      if (index !== -1) {
        leaves.value[index] = response.data.leave
      }
      
      // Remove from pending leaves
      pendingLeaves.value = pendingLeaves.value.filter(leave => leave.id !== id)
      
      return response
    } catch (error) {
      throw error
    }
  }

  function getBalanceByType(type) {
    return leaveBalances.value[type]?.balance || 0
  }

  return {
    leaves,
    pendingLeaves,
    leaveBalances,
    isLoading,
    fetchLeaves,
    fetchPendingLeaves,
    fetchLeaveBalances,
    applyLeave,
    approveLeave,
    rejectLeave,
    getBalanceByType
  }
})