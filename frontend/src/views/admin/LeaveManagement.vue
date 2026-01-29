<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
      <!-- Page Header -->
      <div class="text-center mb-8">
        <h1 class="text-4xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mb-2">
          Leave Management Dashboard
        </h1>
        <p class="text-gray-600 text-lg">
          {{ userRole === 'admin' ? 'All business leaves' : 'Your team leaves' }} for {{ currentMonthYear }}
        </p>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Total Leaves -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-indigo-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm font-medium">Total Leaves</p>
              <p class="text-3xl font-bold text-gray-900 mt-1">{{ statistics.total_leaves }}</p>
            </div>
            <div class="bg-indigo-100 rounded-full p-3">
              <svg class="h-8 w-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Pending Leaves -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-yellow-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm font-medium">Pending Approval</p>
              <p class="text-3xl font-bold text-yellow-600 mt-1">{{ statistics.pending_count }}</p>
            </div>
            <div class="bg-yellow-100 rounded-full p-3">
              <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- Approved Leaves -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-green-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm font-medium">Approved</p>
              <p class="text-3xl font-bold text-green-600 mt-1">{{ statistics.approved_count }}</p>
            </div>
            <div class="bg-green-100 rounded-full p-3">
              <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
        </div>

        <!-- On Leave Today -->
        <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-gray-500 text-sm font-medium">On Leave Today</p>
              <p class="text-3xl font-bold text-blue-600 mt-1">{{ statistics.on_leave_count }}</p>
            </div>
            <div class="bg-blue-100 rounded-full p-3">
              <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Employees Currently On Leave -->
      <div v-if="onLeaveEmployees.length > 0" class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 mb-4 flex items-center gap-2">
          <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          Employees Currently On Leave
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="emp in onLeaveEmployees"
            :key="emp.id"
            class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1">
                <h3 class="font-semibold text-gray-900">{{ emp.name }}</h3>
                <p class="text-sm text-gray-500">{{ emp.department }}</p>
                <div class="mt-2 space-y-1">
                  <p class="text-xs text-gray-600">
                    <span class="font-medium">Type:</span> {{ formatLeaveType(emp.leave_type) }}
                  </p>
                  <p class="text-xs text-gray-600">
                    <span class="font-medium">Until:</span> {{ formatDate(emp.end_date) }}
                  </p>
                  <!-- Update this line in your template -->
<p class="text-xs text-blue-600 font-medium">
  {{ Math.round(emp.days_remaining) }} {{ Math.round(emp.days_remaining) === 1 ? 'day' : 'days' }} remaining
</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Business Filter (Admin Only) -->
      <div v-if="userRole === 'admin'" class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
          <div class="flex flex-col sm:flex-row gap-4 w-full lg:w-auto flex-1">
            <!-- Business Filter -->
            <select
              v-model="selectedBusinessId"
              @change="refreshData"
              class="block w-full sm:w-48 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 bg-white"
            >
              <option value="">All Businesses</option>
              <option v-for="business in businesses" :key="business.id" :value="business.id">
                {{ business.name }}
              </option>
            </select>

            <!-- Search Input -->
            <div class="relative flex-1">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
              <input
                v-model="search"
                type="text"
                placeholder="Search by employee name..."
                class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200"
              />
            </div>

            <!-- Status Filter -->
            <select
              v-model="statusFilter"
              class="block w-full sm:w-48 px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 bg-white"
            >
              <option value="all">All Status</option>
              <option value="pending">Pending</option>
              <option value="approved">Approved</option>
              <option value="rejected">Rejected</option>
              <option value="cancelled">Cancelled</option>
            </select>
          </div>

          <!-- Refresh Button -->
          <button
            @click="refreshData"
            :disabled="loading"
            class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-medium rounded-lg hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-sm hover:shadow-md"
          >
            <svg
              class="h-5 w-5"
              :class="{ 'animate-spin': loading }"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>Refresh</span>
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="bg-white rounded-xl shadow-sm p-12 text-center">
        <div class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent mb-4"></div>
        <p class="text-gray-600 text-lg">Loading leave records...</p>
      </div>

      <!-- Table -->
      <div v-else class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gradient-to-r from-indigo-600 to-purple-600">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                  Employee
                </th>
                <th v-if="userRole === 'admin'" class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                  Business
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                  Department
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                  Leave Type
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                  Start Date
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                  End Date
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                  Days
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                  Reason
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                  Status
                </th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr
                v-for="leave in filteredLeaves"
                :key="leave.id"
                class="hover:bg-indigo-50 transition-colors duration-150"
              >
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">
                    {{ getEmployeeName(leave) }}
                  </div>
                </td>
                <td v-if="userRole === 'admin'" class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-600">
                    {{ leave.employee?.business?.name || 'N/A' }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-600">
                    {{ leave.employee?.department || 'N/A' }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-600">
                    {{ formatLeaveType(leave.leave_type || leave.type) }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-600">
                    {{ formatDate(leave.start_date) }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-600">
                    {{ formatDate(leave.end_date) }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">
                    {{ leave.total_days || calculateDays(leave.start_date, leave.end_date) }}
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="text-sm text-gray-600 max-w-xs truncate" :title="leave.reason || 'N/A'">
                    {{ leave.reason || 'N/A' }}
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="statusClass(leave.status)" class="inline-flex px-3 py-1 text-xs font-semibold rounded-full">
                    {{ formatStatus(leave.status) }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                  <div v-if="leave.status === 'pending'" class="flex gap-2">
                    <button
                      @click="approveLeave(leave.id)"
                      :disabled="submitting"
                      class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                    >
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                      </svg>
                      <span>Approve</span>
                    </button>
                    <button
                      @click="rejectLeave(leave.id)"
                      :disabled="submitting"
                      class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                    >
                      <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                      </svg>
                      <span>Reject</span>
                    </button>
                  </div>
                  <span v-else class="text-gray-400 italic">No actions</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Empty State -->
        <div v-if="filteredLeaves.length === 0 && !loading" class="text-center py-12">
          <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          <p class="text-gray-600 text-lg">No leave records found for the current filters.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

// Props (if needed for role detection)
const props = defineProps({
  userRole: {
    type: String,
    default: 'admin' // Can be 'admin', 'manager', or 'employee'
  }
})

const leaves = ref([])
const onLeaveEmployees = ref([])
const businesses = ref([])
const loading = ref(false)
const submitting = ref(false)
const search = ref('')
const statusFilter = ref('all')
const selectedBusinessId = ref('')

const statistics = ref({
  total_leaves: 0,
  pending_count: 0,
  approved_count: 0,
  rejected_count: 0,
  on_leave_count: 0
})

const currentMonthYear = computed(() => {
  return new Date().toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long'
  })
})

// Fetch businesses for admin
const fetchBusinesses = async () => {
  if (props.userRole !== 'admin') return
  
  try {
    const response = await axios.get('/api/admin/businesses')
    businesses.value = response.data.data || response.data.businesses || []
  } catch (error) {
    console.error('❌ Error fetching businesses:', error)
  }
}

const fetchLeaves = async () => {
  loading.value = true
  try {
    // Based on role, use appropriate endpoint
    let endpoint
    const params = {}
    
    if (props.userRole === 'admin') {
      endpoint = '/api/admin/leaves/current-month'
      if (selectedBusinessId.value) {
        params.business_id = selectedBusinessId.value
      }
    } else if (props.userRole === 'manager') {
      endpoint = '/api/manager/leaves/current-month'
    } else {
      // For employees, you might want to use a different endpoint
      endpoint = '/api/employee/leaves'
    }

    console.log('Fetching leaves from:', endpoint, 'with params:', params)
    
    const response = await axios.get(endpoint, { params })
    const data = response.data

    console.log('Leave data received:', data)

    // Use all_leaves if available, otherwise fall back to pending_leaves
    if (data.all_leaves && Array.isArray(data.all_leaves)) {
      leaves.value = data.all_leaves
    } else if (data.pending_leaves && Array.isArray(data.pending_leaves)) {
      leaves.value = data.pending_leaves
    } else if (Array.isArray(data)) {
      leaves.value = data
    } else if (data.data && Array.isArray(data.data)) {
      leaves.value = data.data
    } else {
      leaves.value = []
    }

    console.log('Leaves loaded:', leaves.value.length, 'items')

    // Extract on-leave employees from dashboard data structure
    if (data.on_leave_employees && Array.isArray(data.on_leave_employees)) {
      onLeaveEmployees.value = data.on_leave_employees
    } else {
      // Fallback: extract from leaves data
      onLeaveEmployees.value = leaves.value
        .filter(leave => 
          leave.status === 'approved' && 
          isOnLeaveToday(leave.start_date, leave.end_date)
        )
        .map(leave => ({
          id: leave.employee?.id || leave.employee_id,
          name: getEmployeeName(leave),
          department: leave.employee?.department || 'N/A',
          leave_type: leave.leave_type || leave.type,
          end_date: leave.end_date,
          days_remaining: calculateDaysRemaining(leave.end_date)
        }))
    }

    // Update statistics based on dashboard structure
    if (data.status_counts || data.pending_count !== undefined) {
      // Use status_counts if available
      if (data.status_counts) {
        statistics.value = {
          total_leaves: data.total_leaves_this_month || 
                       (data.status_counts.pending + data.status_counts.approved + data.status_counts.rejected + data.status_counts.cancelled),
          pending_count: data.status_counts.pending || data.pending_count || 0,
          approved_count: data.status_counts.approved || data.approved_count || 0,
          rejected_count: data.status_counts.rejected || data.rejected_count || 0,
          on_leave_count: data.on_leave_count || 0
        }
      } else {
        statistics.value = {
          total_leaves: data.total_leaves_this_month || leaves.value.length,
          pending_count: data.pending_count || leaves.value.filter(l => l.status === 'pending').length,
          approved_count: data.approved_count || leaves.value.filter(l => l.status === 'approved').length,
          rejected_count: data.rejected_count || leaves.value.filter(l => l.status === 'rejected').length,
          on_leave_count: data.on_leave_count || onLeaveEmployees.value.length
        }
      }
    } else {
      // Calculate from leaves data
      statistics.value = {
        total_leaves: leaves.value.length,
        pending_count: leaves.value.filter(l => l.status === 'pending').length,
        approved_count: leaves.value.filter(l => l.status === 'approved').length,
        rejected_count: leaves.value.filter(l => l.status === 'rejected').length,
        on_leave_count: onLeaveEmployees.value.length
      }
    }

    console.log('Statistics updated:', statistics.value)

  } catch (error) {
    console.error('❌ Error fetching leave records:', error)
    if (error.response?.status === 404) {
      console.error('Endpoint not found. Check your route configuration.')
    }
    alert('Failed to fetch leaves. Please try again.')
  } finally {
    loading.value = false
  }
}

const approveLeave = async (leaveId) => {
  if (!confirm('Are you sure you want to approve this leave request?')) return

  submitting.value = true
  try {
    let endpoint
    if (props.userRole === 'admin') {
      endpoint = `/api/admin/leaves/${leaveId}/approve`
    } else {
      endpoint = `/api/manager/leaves/${leaveId}/approve`
    }

    console.log('Approving leave at:', endpoint)
    
    // Send the required data for approval
    await axios.post(endpoint, {
      status: 'approved',
      manager_notes: 'Leave approved by admin'
    })
    
    alert('Leave approved successfully!')
    await refreshData()
  } catch (error) {
    console.error('❌ Error approving leave:', error)
    if (error.response?.status === 422) {
      alert('Validation error: ' + (error.response.data.message || 'Invalid data'))
    } else {
      alert('Failed to approve leave. Please try again.')
    }
  } finally {
    submitting.value = false
  }
}
const rejectLeave = async (leaveId) => {
  if (!confirm('Are you sure you want to reject this leave request?')) return

  submitting.value = true
  try {
    let endpoint
    if (props.userRole === 'admin') {
      endpoint = `/api/admin/leaves/${leaveId}/reject`
    } else {
      endpoint = `/api/manager/leaves/${leaveId}/reject`
    }

    console.log('Rejecting leave at:', endpoint)
    await axios.post(endpoint)
    alert('Leave rejected successfully!')
    await refreshData()
  } catch (error) {
    console.error('❌ Error rejecting leave:', error)
    alert('Failed to reject leave. Please try again.')
  } finally {
    submitting.value = false
  }
}

const refreshData = async () => {
  await fetchLeaves()
}

// Helper functions
const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

const formatLeaveType = (type) => {
  if (!type) return 'N/A'
  return type.charAt(0).toUpperCase() + type.slice(1).replace(/_/g, ' ')
}

const formatStatus = (status) => {
  if (!status) return 'N/A'
  return status.charAt(0).toUpperCase() + status.slice(1)
}

const getEmployeeName = (leave) => {
  // Try multiple possible data structures
  if (leave.employee?.user?.name) {
    return leave.employee.user.name
  } else if (leave.employee?.name) {
    return leave.employee.name
  } else if (leave.employee?.full_name && leave.employee.full_name !== 'N/A') {
    return leave.employee.full_name
  } else if (leave.employee?.user?.first_name) {
    return `${leave.employee.user.first_name} ${leave.employee.user.last_name || ''}`.trim()
  } else if (leave.employee?.first_name) {
    return `${leave.employee.first_name} ${leave.employee.last_name || ''}`.trim()
  } else if (leave.employee_name) {
    return leave.employee_name
  }
  return 'Unknown Employee'
}

const calculateDays = (startDate, endDate) => {
  if (!startDate || !endDate) return 'N/A'
  const start = new Date(startDate)
  const end = new Date(endDate)
  const diffTime = Math.abs(end - start)
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1
  return diffDays
}

const calculateDaysRemaining = (endDate) => {
  if (!endDate) return 0
  
  const today = new Date()
  const end = new Date(endDate)
  
  // If end date is in the past, return 0
  if (end < today) return 0
  
  // Calculate whole days remaining including today
  const todayStart = new Date(today.getFullYear(), today.getMonth(), today.getDate())
  const endStart = new Date(end.getFullYear(), end.getMonth(), end.getDate())
  
  // Add 1 to include today as a day
  const diffDays = Math.floor((endStart - todayStart) / (1000 * 60 * 60 * 24)) + 1
  
  return Math.max(diffDays, 0)
}

const isOnLeaveToday = (startDate, endDate) => {
  if (!startDate || !endDate) return false
  
  const today = new Date()
  const todayStart = new Date(today.getFullYear(), today.getMonth(), today.getDate())
  
  const start = new Date(startDate)
  const startStart = new Date(start.getFullYear(), start.getMonth(), start.getDate())
  
  const end = new Date(endDate)
  const endStart = new Date(end.getFullYear(), end.getMonth(), end.getDate())
  
  return todayStart >= startStart && todayStart <= endStart
}

const filteredLeaves = computed(() => {
  console.log('Filtering leaves:', {
    total: leaves.value.length,
    statusFilter: statusFilter.value,
    search: search.value
  })

  const results = leaves.value.filter((leave) => {
    const employeeName = getEmployeeName(leave).toLowerCase()
    const matchesSearch = employeeName.includes(search.value.toLowerCase())
    const matchesStatus = statusFilter.value === 'all' || 
                         leave.status?.toLowerCase() === statusFilter.value
    
    console.log('Leave check:', {
      name: getEmployeeName(leave),
      status: leave.status,
      matchesSearch,
      matchesStatus
    })
    
    return matchesSearch && matchesStatus
  })

  console.log('Filtered results:', results.length, 'items')
  return results
})

const statusClass = (status) => {
  switch (status?.toLowerCase()) {
    case 'approved':
      return 'bg-green-100 text-green-800'
    case 'rejected':
      return 'bg-red-100 text-red-800'
    case 'pending':
      return 'bg-yellow-100 text-yellow-800'
    case 'cancelled':
      return 'bg-gray-100 text-gray-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

onMounted(() => {
  if (props.userRole === 'admin') {
    fetchBusinesses()
  }
  fetchLeaves()
})
</script>

<style scoped>
:root {
  --primary-color: #4f46e5;
  --primary-hover: #4338ca;
  --success-color: #10b981;
  --danger-color: #ef4444;
  --warning-color: #f59e0b;
  --text-primary: #1f2937;
  --text-secondary: #6b7280;
  --bg-primary: #ffffff;
  --bg-secondary: #f9fafb;
  --border-color: #e5e7eb;
  --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
  --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
  --radius: 12px;
  --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.leave-management-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
  background: var(--bg-secondary);
  min-height: 100vh;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

.page-header {
  text-align: center;
  margin-bottom: 2rem;
}

.page-header h1 {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 0.5rem;
  background: linear-gradient(135deg, var(--primary-color), #7c3aed);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.page-subtitle {
  color: var(--text-secondary);
  margin-top: -0.5rem;
  margin-bottom: 1.5rem;
  font-size: 1.1rem;
  font-weight: 400;
}

.controls-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  gap: 1rem;
  background: var(--bg-primary);
  padding: 1.5rem;
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
}

.filters {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex: 1;
}

.search-wrapper {
  position: relative;
  flex: 1;
}

.search-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 2.5rem;
  border: 2px solid var(--border-color);
  border-radius: var(--radius);
  font-size: 1rem;
  transition: var(--transition);
  background: var(--bg-primary);
}

.search-input:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-secondary);
}

.status-filter {
  border: 2px solid var(--border-color);
  border-radius: var(--radius);
  padding: 0.75rem 1rem;
  background: var(--bg-primary);
  font-size: 1rem;
  transition: var(--transition);
  cursor: pointer;
}

.status-filter:focus {
  outline: none;
  border-color: var(--primary-color);
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.btn-refresh {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, var(--primary-color), #7c3aed);
  color: white;
  border: none;
  border-radius: var(--radius);
  font-weight: 500;
  cursor: pointer;
  transition: var(--transition);
  box-shadow: var(--shadow-sm);
}

.btn-refresh:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
  background: linear-gradient(135deg, var(--primary-hover), #6d28d9);
}

.btn-refresh:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.refresh-icon.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  background: var(--bg-primary);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  text-align: center;
  color: var(--text-secondary);
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid var(--border-color);
  border-top: 4px solid var(--primary-color);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

.table-wrapper {
  background: var(--bg-primary);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.table-container {
  overflow-x: auto;
}

.leave-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.95rem;
}

.leave-table thead th {
  background: linear-gradient(135deg, var(--primary-color), #7c3aed);
  color: white;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  letter-spacing: 0.025em;
  position: sticky;
  top: 0;
  z-index: 10;
}

.leave-table tbody tr {
  transition: var(--transition);
  border-bottom: 1px solid var(--border-color);
}

.leave-table tbody tr:hover {
  background: rgba(79, 70, 229, 0.05);
  transform: scale(1.002);
}

.leave-table tbody td {
  padding: 1rem;
  vertical-align: middle;
}

.employee-cell {
  font-weight: 500;
  color: var(--text-primary);
}

.status-badge {
  display: inline-block;
  padding: 0.375rem 0.75rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.025em;
  transition: var(--transition);
}

.status-approved {
  background: rgba(16, 185, 129, 0.1);
  color: var(--success-color);
  border: 1px solid rgba(16, 185, 129, 0.2);
}

.status-rejected {
  background: rgba(239, 68, 68, 0.1);
  color: var(--danger-color);
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.status-pending {
  background: rgba(245, 158, 11, 0.1);
  color: var(--warning-color);
  border: 1px solid rgba(245, 158, 11, 0.2);
}

.actions-cell {
  white-space: nowrap;
}

.btn-approve,
.btn-reject {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: var(--transition);
  margin-right: 0.5rem;
  margin-bottom: 0.25rem;
}

.btn-approve {
  background: rgba(16, 185, 129, 0.1);
  color: var(--success-color);
  border: 1px solid rgba(16, 185, 129, 0.2);
}

.btn-approve:hover:not(:disabled) {
  background: rgba(16, 185, 129, 0.2);
  transform: translateY(-1px);
}

.btn-reject {
  background: rgba(239, 68, 68, 0.1);
  color: var(--danger-color);
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.btn-reject:hover:not(:disabled) {
  background: rgba(239, 68, 68, 0.2);
  transform: translateY(-1px);
}

.btn-approve:disabled,
.btn-reject:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.no-actions {
  color: var(--text-secondary);
  font-style: italic;
  font-size: 0.875rem;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  color: var(--text-secondary);
  text-align: center;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

@media (max-width: 768px) {
  .leave-management-container {
    padding: 1rem;
  }

  .page-header h1 {
    font-size: 2rem;
  }

  .controls-bar {
    flex-direction: column;
    gap: 1rem;
    padding: 1rem;
  }

  .filters {
    flex-direction: column;
    width: 100%;
  }

  .search-input,
  .status-filter {
    width: 100%;
  }

  .leave-table {
    font-size: 0.85rem;
  }

  .leave-table thead th,
  .leave-table tbody td {
    padding: 0.75rem 0.5rem;
  }

  .btn-approve,
  .btn-reject {
    margin-right: 0;
    width: 100%;
    justify-content: center;
    margin-bottom: 0.5rem;
  }
}
</style>