<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">
              <span class="text-indigo-600">User</span> Management
            </h1>
            <p class="text-sm text-gray-600 mt-1">
              Manage all users across the system
            </p>
          </div>
          
          <button
            @click="openCreateModal"
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Create User
          </button>
        </div>

        <!-- Stats -->
        <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-5">
          <div class="bg-white overflow-hidden shadow rounded-lg border">
            <div class="px-4 py-5 sm:p-6">
              <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
              <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ stats.total_users || 0 }}</dd>
            </div>
          </div>
          <div class="bg-white overflow-hidden shadow rounded-lg border">
            <div class="px-4 py-5 sm:p-6">
              <dt class="text-sm font-medium text-gray-500 truncate">SuperAdmins</dt>
              <dd class="mt-1 text-3xl font-semibold text-purple-600">{{ stats.superadmins || 0 }}</dd>
            </div>
          </div>
          <div class="bg-white overflow-hidden shadow rounded-lg border">
            <div class="px-4 py-5 sm:p-6">
              <dt class="text-sm font-medium text-gray-500 truncate">Admins</dt>
              <dd class="mt-1 text-3xl font-semibold text-blue-600">{{ stats.admins || 0 }}</dd>
            </div>
          </div>
          <div class="bg-white overflow-hidden shadow rounded-lg border">
            <div class="px-4 py-5 sm:p-6">
              <dt class="text-sm font-medium text-gray-500 truncate">Managers</dt>
              <dd class="mt-1 text-3xl font-semibold text-green-600">{{ stats.managers || 0 }}</dd>
            </div>
          </div>
          <div class="bg-white overflow-hidden shadow rounded-lg border">
            <div class="px-4 py-5 sm:p-6">
              <dt class="text-sm font-medium text-gray-500 truncate">Employees</dt>
              <dd class="mt-1 text-3xl font-semibold text-gray-600">{{ stats.employees || 0 }}</dd>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Filters -->
      <div class="mb-6 bg-white rounded-lg shadow p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <!-- Search -->
          <div class="md:col-span-2">
            <input
              v-model="searchQuery"
              @input="debouncedSearch"
              type="search"
              placeholder="Search users..."
              class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            />
          </div>

          <!-- Role Filter -->
          <select
            v-model="filters.role"
            @change="fetchUsers"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          >
            <option value="">All Roles</option>
            <option value="admin">Admin</option>
            <option value="manager">Manager</option>
            <option value="employee">Employee</option>
          </select>

          <!-- SuperAdmin Filter -->
          <select
            v-model="filters.is_superadmin"
            @change="fetchUsers"
            class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
          >
            <option value="">All Users</option>
            <option value="1">SuperAdmins Only</option>
            <option value="0">Non-SuperAdmins</option>
          </select>
        </div>
      </div>

      <!-- Users Table -->
      <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <div v-if="loading" class="p-12 text-center">
          <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-indigo-200 border-t-indigo-600"></div>
        </div>

        <div v-else-if="users.length === 0" class="p-12 text-center">
          <p class="text-gray-500">No users found</p>
        </div>

        <table v-else class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="relative px-6 py-3">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                    <span class="text-indigo-600 font-medium">{{ getInitials(user) }}</span>
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">
                      {{ user.first_name }} {{ user.last_name }}
                    </div>
                    <div class="text-sm text-gray-500">{{ user.email }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="getRoleBadgeClass(user.role)">
                  {{ formatRole(user.role) }}
                </span>
                <span v-if="user.is_superadmin" class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                  <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  SuperAdmin
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ user.current_business?.name || 'No business' }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ formatDate(user.created_at) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span v-if="user.email_verified_at" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                  Verified
                </span>
                <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                  Pending
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex items-center justify-end space-x-2">
                  <button @click="viewUser(user)" class="text-indigo-600 hover:text-indigo-900" title="View">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                  </button>
                  <button @click="editUser(user)" class="text-blue-600 hover:text-blue-900" title="Edit">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </button>
                  <button 
                    @click="toggleSuperAdmin(user)" 
                    :class="user.is_superadmin ? 'text-red-600 hover:text-red-900' : 'text-purple-600 hover:text-purple-900'"
                    :title="user.is_superadmin ? 'Remove SuperAdmin' : 'Make SuperAdmin'"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                  </button>
                  <button @click="deleteUser(user)" class="text-red-600 hover:text-red-900" title="Delete">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div v-if="meta.last_page > 1" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
          <div class="flex-1 flex justify-between sm:hidden">
            <button @click="previousPage" :disabled="meta.current_page === 1" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50">
              Previous
            </button>
            <button @click="nextPage" :disabled="meta.current_page === meta.last_page" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50">
              Next
            </button>
          </div>
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-gray-700">
                Showing <span class="font-medium">{{ meta.from }}</span> to <span class="font-medium">{{ meta.to }}</span> of <span class="font-medium">{{ meta.total }}</span> results
              </p>
            </div>
            <div>
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                <button @click="previousPage" :disabled="meta.current_page === 1" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                  <span class="sr-only">Previous</span>
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                  </svg>
                </button>
                <button @click="nextPage" :disabled="meta.current_page === meta.last_page" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50">
                  <span class="sr-only">Next</span>
                  <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                  </svg>
                </button>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Toast Notifications -->
    <div class="fixed bottom-4 right-4 z-50 space-y-2">
      <div v-for="toast in toasts" :key="toast.id" :class="['flex items-center p-4 rounded-lg shadow-lg', getToastClass(toast.type)]">
        <div class="ml-3">
          <p :class="['text-sm font-medium', getToastTextClass(toast.type)]">{{ toast.message }}</p>
        </div>
        <button @click="removeToast(toast.id)" class="ml-auto">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { debounce } from 'lodash'

export default {
  name: 'SuperAdminUserManagement',
  
  setup() {
    const users = ref([])
    const loading = ref(false)
    const searchQuery = ref('')
    const toasts = ref([])

    const filters = ref({
      role: '',
      is_superadmin: '',
    })

    const stats = ref({
      total_users: 0,
      superadmins: 0,
      admins: 0,
      managers: 0,
      employees: 0,
    })

    const meta = ref({
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 0,
      from: 0,
      to: 0,
    })

    const fetchUsers = async () => {
      loading.value = true
      try {
        const params = {
          page: meta.value.current_page,
          per_page: meta.value.per_page,
          search: searchQuery.value,
          ...filters.value
        }

        const response = await axios.get('/api/superadmin/users', { params })
        
        if (response.data.success) {
          users.value = response.data.data
          meta.value = response.data.meta
          stats.value = response.data.stats
        }
      } catch (error) {
        console.error('Error fetching users:', error)
        showToast('Failed to fetch users', 'error')
      } finally {
        loading.value = false
      }
    }

    const debouncedSearch = debounce(() => {
      meta.value.current_page = 1
      fetchUsers()
    }, 500)

    const openCreateModal = () => {
      // Implement create modal
    }

    const viewUser = (user) => {
      // Implement view modal
    }

    const editUser = (user) => {
      // Implement edit modal
    }

    const toggleSuperAdmin = async (user) => {
      if (!confirm(`Are you sure you want to ${user.is_superadmin ? 'remove' : 'grant'} superadmin privileges for ${user.first_name} ${user.last_name}?`)) {
        return
      }

      try {
        const response = await axios.post(`/api/superadmin/users/${user.id}/toggle-superadmin`)
        
        if (response.data.success) {
          showToast(response.data.message, 'success')
          fetchUsers()
        }
      } catch (error) {
        console.error('Error toggling superadmin:', error)
        showToast(error.response?.data?.message || 'Failed to update user', 'error')
      }
    }

    const deleteUser = async (user) => {
      if (!confirm(`Are you sure you want to delete ${user.first_name} ${user.last_name}? Type DELETE to confirm.`)) {
        return
      }

      const confirmation = prompt('Type DELETE to confirm:')
      if (confirmation !== 'DELETE') {
        return
      }

      try {
        const response = await axios.delete(`/api/superadmin/users/${user.id}`, {
          data: { confirmation: 'DELETE' }
        })
        
        if (response.data.success) {
          showToast(response.data.message, 'success')
          fetchUsers()
        }
      } catch (error) {
        console.error('Error deleting user:', error)
        showToast(error.response?.data?.message || 'Failed to delete user', 'error')
      }
    }

    const previousPage = () => {
      if (meta.value.current_page > 1) {
        meta.value.current_page--
        fetchUsers()
      }
    }

    const nextPage = () => {
      if (meta.value.current_page < meta.value.last_page) {
        meta.value.current_page++
        fetchUsers()
      }
    }

    // Utility functions
    const getInitials = (user) => {
      return `${user.first_name[0]}${user.last_name[0]}`.toUpperCase()
    }

    const getRoleBadgeClass = (role) => {
      const classes = {
        admin: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800',
        manager: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800',
        employee: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800'
      }
      return classes[role] || classes.employee
    }

    const formatRole = (role) => {
      return role ? role.charAt(0).toUpperCase() + role.slice(1) : 'Employee'
    }

    const formatDate = (dateString) => {
      if (!dateString) return 'N/A'
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }

    const getToastClass = (type) => {
      const classes = {
        success: 'bg-green-50 border border-green-200',
        error: 'bg-red-50 border border-red-200',
        warning: 'bg-yellow-50 border border-yellow-200',
        info: 'bg-blue-50 border border-blue-200'
      }
      return classes[type] || classes.info
    }

    const getToastTextClass = (type) => {
      const classes = {
        success: 'text-green-800',
        error: 'text-red-800',
        warning: 'text-yellow-800',
        info: 'text-blue-800'
      }
      return classes[type] || classes.info
    }

    let toastId = 0
    const showToast = (message, type = 'info') => {
      const id = ++toastId
      toasts.value.push({ id, message, type })
      setTimeout(() => removeToast(id), 5000)
    }

    const removeToast = (id) => {
      toasts.value = toasts.value.filter(toast => toast.id !== id)
    }

    onMounted(() => {
      fetchUsers()
    })

    return {
      users,
      loading,
      searchQuery,
      toasts,
      filters,
      stats,
      meta,
      fetchUsers,
      debouncedSearch,
      openCreateModal,
      viewUser,
      editUser,
      toggleSuperAdmin,
      deleteUser,
      previousPage,
      nextPage,
      getInitials,
      getRoleBadgeClass,
      formatRole,
      formatDate,
      getToastClass,
      getToastTextClass,
      removeToast,
    }
  }
}
</script>