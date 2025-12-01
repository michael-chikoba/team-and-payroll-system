
<!-- AdminManagement.vue - New Component -->
<template>
  <div class="max-w-4xl mx-auto p-6">
    <div class="bg-white shadow-lg rounded-lg p-6">
      <div class="flex items-center justify-between mb-6">
        <div>
          <h2 class="text-2xl font-bold text-gray-800">Manage Administrators</h2>
          <p class="text-gray-600 mt-1">{{ business.name }}</p>
        </div>
        <button
          @click="$emit('cancel')"
          class="px-4 py-2 text-gray-600 hover:text-gray-800 focus:outline-none"
        >
          ← Back to List
        </button>
      </div>

      <!-- Current Administrators -->
      <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Current Administrators</h3>
        <div class="space-y-3">
          <div
            v-for="admin in admins"
            :key="admin.id"
            class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200"
          >
            <div class="flex-1">
              <div class="flex items-center space-x-3">
                <div>
                  <p class="font-medium text-gray-900">
                    {{ admin.first_name }} {{ admin.last_name }}
                  </p>
                  <p class="text-sm text-gray-600">{{ admin.email }}</p>
                </div>
                <span
                  :class="[
                    'px-2 py-1 text-xs rounded-full',
                    admin.pivot.is_primary ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'
                  ]"
                >
                  {{ admin.pivot.is_primary ? 'Primary Owner' : admin.pivot.role }}
                </span>
              </div>
            </div>
            
            <div class="flex items-center space-x-2">
              <button
                v-if="!admin.pivot.is_primary"
                @click="makeAdminPrimary(admin)"
                class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
              >
                Make Primary
              </button>
              <button
                v-if="!admin.pivot.is_primary"
                @click="confirmRemoveAdmin(admin)"
                class="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700"
              >
                Remove
              </button>
              <span v-else class="text-xs text-gray-500 italic">Cannot remove</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Add New Administrator -->
      <div class="border-t pt-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Add New Administrator</h3>
        <form @submit.prevent="addAdmin" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Search User by Email</label>
              <input
                v-model="newAdmin.email"
                type="email"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="user@example.com"
                required
              />
              <div v-if="errors.user_id" class="text-red-500 text-sm mt-1">{{ errors.user_id[0] }}</div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
              <select
                v-model="newAdmin.role"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
              >
                <option value="admin">Admin</option>
                <option value="manager">Manager</option>
                <option value="owner">Owner</option>
              </select>
            </div>
          </div>

          <div class="flex items-center">
            <input
              type="checkbox"
              v-model="newAdmin.is_primary"
              class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
            />
            <label class="ml-2 text-sm text-gray-700">Make this user the primary administrator</label>
          </div>

          <button
            type="submit"
            :disabled="loading"
            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50"
          >
            <span v-if="loading">Adding...</span>
            <span v-else>Add Administrator</span>
          </button>
        </form>
      </div>
    </div>

    <!-- Remove Confirmation Modal -->
    <div
      v-if="showRemoveModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    >
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Remove Administrator</h3>
        <p class="text-gray-600 mb-6">
          Are you sure you want to remove <strong>{{ adminToRemove?.first_name }} {{ adminToRemove?.last_name }}</strong> as an administrator?
        </p>
        <div class="flex justify-end space-x-3">
          <button
            @click="cancelRemove"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            @click="removeAdmin"
            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
          >
            Remove
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
        const response = await axios.get(`/api/admin/businesses/${this.business.id}`)
        this.admins = response.data.data.admins || []
      } catch (error) {
        console.error('Failed to fetch admins:', error)
      }
    },
    async addAdmin() {
      this.loading = true
      this.errors = {}

      try {
        // First, find user by email
        const userResponse = await axios.get(`/api/admin/users/search?email=${this.newAdmin.email}`)
        
        if (!userResponse.data.data) {
          this.errors.user_id = ['User not found with this email']
          return
        }

        const userId = userResponse.data.data.id

        const response = await axios.post(`/api/admin/businesses/${this.business.id}/admins`, {
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
