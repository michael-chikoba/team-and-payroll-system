<template>
  <div class="max-w-4xl mx-auto py-4">
    <div class="bg-white shadow-2xl rounded-2xl p-8 border border-gray-100">
      <div class="flex items-center justify-between mb-8 pb-4 border-b border-gray-100">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 tracking-tight">
            Admin Access for <span class="text-indigo-600">{{ business.name }}</span>
          </h2>
          <p class="text-base text-gray-500 mt-1">
            Manage roles, assign owners, and control administrative access.
          </p>
        </div>
        <button
          @click="$emit('cancel')"
          class="inline-flex items-center text-sm font-medium text-indigo-600 hover:text-indigo-800 transition duration-150"
        >
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"></path>
          </svg>
          Back to Businesses
        </button>
      </div>

      <div class="mb-10">
        <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
          <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m14-4a2 2 0 01-2 2H7a2 2 0 01-2-2m14-4H5m13 0a2 2 0 002-2V9a2 2 0 00-2-2h-3L8 3v10h4z"></path></svg>
          Current Team ({{ admins.length }})
        </h3>
        <div class="space-y-4">
          <div
            v-for="admin in admins"
            :key="admin.id"
            class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200 hover:shadow-md transition-shadow duration-200"
          >
            <div class="flex items-center space-x-4 flex-1 min-w-0">
              <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 font-bold text-sm flex-shrink-0">
                {{ admin.first_name ? admin.first_name.charAt(0) : '?' }}
              </div>
              <div class="min-w-0">
                <p class="font-medium text-gray-900 truncate">
                  {{ admin.first_name }} {{ admin.last_name }}
                </p>
                <p class="text-sm text-gray-500 truncate">{{ admin.email }}</p>
              </div>
              
              <span
                :class="[
                  'px-3 py-1 text-xs font-semibold uppercase tracking-wider rounded-full flex-shrink-0 whitespace-nowrap',
                  admin.pivot.is_primary ? 'bg-indigo-100 text-indigo-700 ring-1 ring-indigo-600/20' : 'bg-gray-100 text-gray-600 ring-1 ring-gray-600/20'
                ]"
              >
                {{ admin.pivot.is_primary ? 'Primary Owner' : admin.pivot.role }}
              </span>
            </div>
            
            <div class="flex items-center space-x-2 flex-shrink-0">
              <button
                v-if="!admin.pivot.is_primary"
                @click="makeAdminPrimary(admin)"
                class="px-3 py-1 text-xs font-medium border border-indigo-300 text-indigo-700 rounded-lg hover:bg-indigo-50 transition duration-150 focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                title="Make Primary Owner"
              >
                Primary
              </button>
              <button
                v-if="!admin.pivot.is_primary"
                @click="confirmRemoveAdmin(admin)"
                class="px-3 py-1 text-xs font-medium bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition duration-150 focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                title="Remove Administrator"
              >
                Remove
              </button>
              <span v-else class="text-xs text-gray-500 italic px-3 py-1">Owner cannot be removed</span>
            </div>
          </div>
        </div>
      </div>

      <div class="border-t border-gray-200 pt-8">
        <h3 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
          <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
          Invite/Add New Administrator
        </h3>
        <form @submit.prevent="addAdmin" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 mb-1">User Email</label>
              <input
                id="email"
                v-model="newAdmin.email"
                type="email"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                placeholder="user@example.com"
                required
              />
              <div v-if="errors.user_id" class="text-red-500 text-sm mt-1">{{ errors.user_id[0] }}</div>
            </div>

            <div>
              <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Assigned Role</label>
              <select
                id="role"
                v-model="newAdmin.role"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 bg-white"
                required
              >
                <option value="admin">Admin</option>
                <option value="manager">Manager</option>
                <option value="owner">Owner</option>
              </select>
            </div>

            <div class="flex items-center mt-6">
              <input
                id="is_primary"
                type="checkbox"
                v-model="newAdmin.is_primary"
                class="h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
              />
              <label for="is_primary" class="ml-3 text-sm font-medium text-gray-700 select-none">
                Set as Primary
              </label>
            </div>
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="px-6 py-2 bg-indigo-600 text-white rounded-xl shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-500 focus:ring-opacity-50 transition transform hover:scale-[1.01] duration-200 disabled:opacity-50 disabled:shadow-none"
          >
            <span v-if="loading" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
              Adding...
            </span>
            <span v-else>Add Administrator</span>
          </button>
        </form>
      </div>
    </div>

    <div
      v-if="showRemoveModal"
      class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50 transition-opacity"
      aria-modal="true"
      role="dialog"
    >
      <div class="bg-white rounded-xl shadow-2xl p-6 max-w-lg w-full mx-4 transform transition-all">
        <h3 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
          <svg class="w-6 h-6 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
          Confirm Removal
        </h3>
        <p class="text-gray-600 mb-6">
          You are about to remove **{{ adminToRemove?.first_name }} {{ adminToRemove?.last_name }}** from the administrator list of **{{ business.name }}**. This action cannot be undone.
        </p>
        <div class="flex justify-end space-x-3">
          <button
            @click="cancelRemove"
            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition duration-150 font-medium"
          >
            Cancel
          </button>
          <button
            @click="removeAdmin"
            class="px-4 py-2 bg-red-600 text-white rounded-lg shadow-md hover:bg-red-700 transition duration-150 font-medium focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
          >
            Remove Administrator
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'AdminManagement',
  props: {
    business: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      admins: [],
      newAdmin: {
        email: '',
        role: 'admin',
        is_primary: false
      },
      loading: false,
      errors: {},
      showRemoveModal: false,
      adminToRemove: null
    }
  },
  async mounted() {
    await this.fetchAdmins()
  },
  methods: {
    async fetchAdmins() {
      try {
        // Fetching the business details which should include the updated admins array
        const response = await axios.get(`/api/admin/businesses/${this.business.id}`)
        // Assuming the admins data is nested under the business object
        this.admins = response.data.data.admins || []
      } catch (error) {
        console.error('Failed to fetch admins:', error)
        this.$notify({
          type: 'error',
          title: 'Error',
          text: 'Failed to load administrator list.'
        })
      }
    },
    async addAdmin() {
      this.loading = true
      this.errors = {}

      try {
        // 1. Find user by email (as per original logic)
        const userResponse = await axios.get(`/api/admin/users/search?email=${this.newAdmin.email}`)
        
        if (!userResponse.data.data) {
          this.errors.user_id = ['User not found with this email.']
          this.loading = false
          return
        }

        const userId = userResponse.data.data.id

        // 2. Add the user as admin
        await axios.post(`/api/admin/businesses/${this.business.id}/admins`, {
          user_id: userId,
          role: this.newAdmin.role,
          is_primary: this.newAdmin.is_primary
        })

        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Administrator added successfully'
        })

        this.newAdmin = { email: '', role: 'admin', is_primary: false }
        await this.fetchAdmins()
        this.$emit('success') // Signal parent component (BusinessManagement) that data was updated
      } catch (error) {
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors
        }
        this.$notify({
          type: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to add administrator'
        })
      } finally {
        this.loading = false
      }
    },
    async makeAdminPrimary(admin) {
      try {
        await axios.put(`/api/admin/businesses/${this.business.id}/admins/${admin.id}/primary`)
        
        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Primary administrator updated'
        })
        
        await this.fetchAdmins()
        this.$emit('success')
      } catch (error) {
        this.$notify({
          type: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to update primary administrator'
        })
      }
    },
    confirmRemoveAdmin(admin) {
      this.adminToRemove = admin
      this.showRemoveModal = true
    },
    async removeAdmin() {
      try {
        await axios.delete(`/api/admin/businesses/${this.business.id}/admins/${this.adminToRemove.id}`)
        
        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Administrator removed successfully'
        })
        
        this.showRemoveModal = false
        this.adminToRemove = null
        await this.fetchAdmins()
        this.$emit('success')
      } catch (error) {
        this.$notify({
          type: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to remove administrator'
        })
      }
    },
    cancelRemove() {
      this.showRemoveModal = false
      this.adminToRemove = null
    }
  }
}
</script>