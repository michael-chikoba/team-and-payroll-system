import { defineStore } from 'pinia'
import { ref } from 'vue'
import { employeeAPI } from '@/api/employee'

export const useEmployeeStore = defineStore('employee', () => {
  const employees = ref([])
  const currentEmployee = ref(null)
  const isLoading = ref(false)

  async function fetchEmployees() {
    try {
      isLoading.value = true
      const response = await employeeAPI.getEmployees()
      employees.value = response.data.data
    } catch (error) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  async function fetchEmployee(id) {
    try {
      isLoading.value = true
      const response = await employeeAPI.getEmployee(id)
      currentEmployee.value = response.data.data
    } catch (error) {
      throw error
    } finally {
      isLoading.value = false
    }
  }

  async function createEmployee(employeeData) {
    try {
      const response = await employeeAPI.createEmployee(employeeData)
      employees.value.push(response.data.employee)
      return response
    } catch (error) {
      throw error
    }
  }

  async function updateEmployee(id, employeeData) {
    try {
      const response = await employeeAPI.updateEmployee(id, employeeData)
      const index = employees.value.findIndex(emp => emp.id === id)
      if (index !== -1) {
        employees.value[index] = response.data.employee
      }
      return response
    } catch (error) {
      throw error
    }
  }

  async function deleteEmployee(id) {
    try {
      await employeeAPI.deleteEmployee(id)
      employees.value = employees.value.filter(emp => emp.id !== id)
    } catch (error) {
      throw error
    }
  }

  return {
    employees,
    currentEmployee,
    isLoading,
    fetchEmployees,
    fetchEmployee,
    createEmployee,
    updateEmployee,
    deleteEmployee
  }
})