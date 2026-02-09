<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="close"></div>

      <!-- Modal -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
          <!-- Header -->
          <div class="flex items-center justify-between mb-6">
            <div>
              <h3 class="text-lg leading-6 font-medium text-gray-900">
                Manage Administrators - {{ business.name }}
              </h3>
              <p class="mt-1 text-sm text-gray-500">
                Add or remove administrators for this business
              </p>
            </div>
            <button
              @click="close"
              class="text-gray-400 hover:text-gray-500 focus:outline-none"
            >
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Current Admins -->
          <div class="mb-8">
            <h4 class="text-md font-medium text-gray-900 mb-4">Current Administrators</h4>
            
            <div v-if="loading" class="text-center py-4">
              <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>
            
            <div v-else-if="admins.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
              <p class="text-gray-500">No administrators found</p>
            </div>
            
            <div v-else class="space-y-3">
              <div
                v-for="admin in admins"
                :key="admin.user_id"
                class="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
              >
                <div class="flex items-center">
                  <div class="flex-shrink-0">
                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                      <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                      </svg>
                    </div>
                  </div>
                  <div class="ml-3">
                    <div class="text-sm font-medium text-gray-900">
                      {{ admin.user?.first_name }} {{ admin.user?.last_name }}
                    </div>
                    <div class="text-sm text-gray-500">{{ admin.user?.email }}</div>
                    <div class="flex items-center mt-1">
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-2">
                        {{ formatRole(admin.role) }}
                      </span>
                      <span v-if="admin.is_primary" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Primary
                      </span>
                    </div>
                  </div>
                </div>
                
                <div class="flex items-center space-x-2">
                  <button
                    v-if="!admin.is_primary"
                    @click="removeAdmin(admin.user_id)"
                    class="text-red-600 hover:text-red-900 text-sm font-medium"
                  >
                    Remove
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Add New Admin -->
          <div class="border-t pt-6">
            <h4 class="text-md font-medium text-gray-900 mb-4">Add New Administrator</h4>
            
            <div class="space-y-4">
              <!-- Search User -->
              <div>
                <label for="searchEmail" class="block text-sm font-medium text-gray-700">
                  Search User by Email
                </label>
                <div class="mt-1 flex rounded-md shadow-sm">
                  <input
                    type="email"
                    id="searchEmail"
                    v-model="searchEmail"
                    placeholder="Enter user email"
                    class="flex-1 min-w-0 block w-full rounded-none rounded-l-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  />
                  <button
                    @click="searchUser"
                    type="button"
                    :disabled="searching"
                    class="inline-flex items-center px-4 py-2 border border-l-0 border-gray-300 rounded-r-md bg-gray-50 text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 disabled:opacity-50"
                  >
                    <span v-if="searching">Searching...</span>
                    <span v-else>Search</span>
                  </button>
                </div>
                
                <!-- Search Results -->
                <div v-if="searchResult" class="mt-3 p-3 bg-blue-50 rounded-lg">
                  <div class="flex items-center justify-between">
                    <div>
                      <div class="text-sm font-medium text-gray-900">
                        {{ searchResult.first_name }} {{ searchResult.last_name }}
                      </div>
                      <div class="text-sm text-gray-600">{{ searchResult.email }}</div>
                    </div>
                    <button
                      @click="addSearchedUser"
                      class="text-sm text-blue-600 hover:text-blue-900 font-medium"
                    >
                      Add as Admin
                    </button>
                  </div>
                </div>
                
                <!-- Search Error -->
                <div v-if="searchError" class="mt-2 text-sm text-red-600">
                  {{ searchError }}
                </div>
              </div>

              <!-- Or Create New User -->
              <div class="pt-4 border-t">
                <h5 class="text-sm font-medium text-gray-700 mb-3">Or create a new user</h5>
                
                <div class="grid grid-cols-2 gap-4">
                  <div>
                    <label for="newFirstName" class="block text-sm font-medium text-gray-700">
                      First Name
                    </label>
                    <input
                      type="text"
                      id="newFirstName"
                      v-model="newUser.first_name"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    />
                  </div>
                  
                  <div>
                    <label for="newLastName" class="block text-sm font-medium text-gray-700">
                      Last Name
                    </label>
                    <input
                      type="text"
                      id="newLastName"
                      v-model="newUser.last_name"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    />
                  </div>
                </div>
                
                <div class="mt-4">
                  <label for="newEmail" class="block text-sm font-medium text-gray-700">
                    Email *
                  </label>
                  <input
                    type="email"
                    id="newEmail"
                    v-model="newUser.email"
                    required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  />
                </div>
                
                <div class="mt-4">
                  <label for="role" class="block text-sm font-medium text-gray-700">
                    Role
                  </label>
                  <select
                    id="role"
                    v-model="newUser.role"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                  >
                    <option value="admin">Administrator</option>
                    <option value="manager">Manager</option>
                    <option value="owner">Owner</option>
                  </select>
                </div>
                
                <div class="mt-4 flex items-center justify-between">
                  <div>
                    <button
                      @click="createAndAddUser"
                      :disabled="addingUser"
                      class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                    >
                      <span v-if="addingUser">Adding...</span>
                      <span v-else>Create User & Add as Admin</span>
                    </button>
                  </div>
                  
                  <div class="text-sm text-gray-500">
                    A welcome email will be sent to the new user
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end">
            <button
              @click="close"
              class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Done
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'AdminManagementModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    business: {
      type: Object,
      required: true
    }
  },
  emits: ['close', 'updated'],
  
  data() {
    return {
      loading: false,
      admins: [],
      searchEmail: '',
      searching: false,
      searchResult: null,
      searchError: '',
      addingUser: false,
      newUser: {
        first_name: '',
        last_name: '',
        email: '',
        role: 'admin'
      }
    }
  },
  
  watch: {
    show: {
      immediate: true,
      handler(newVal) {
        if (newVal && this.business) {
          this.fetchAdmins()
        }
      }
    }
  },
  
  methods: {
    close() {
      this.$emit('close')
    },
    
    async fetchAdmins() {
      this.loading = true
      try {
        const response = await axios.get(`/api/admin/businesses/${this.business.id}/admins`)
        if (response.data.success) {
          this.admins = response.data.data
        }
      } catch (error) {
        console.error('Error fetching admins:', error)
        alert('Failed to load administrators')
      } finally {
        this.loading = false
      }
    },
    
    async searchUser() {
      if (!this.searchEmail) {
        this.searchError = 'Please enter an email address'
        return
      }
      
      this.searching = true
      this.searchError = ''
      this.searchResult = null
      
      try {
        // Assuming you have a user search endpoint
        const response = await axios.get('/api/admin/users/search', {
          params: { email: this.searchEmail }
        })
        
        if (response.data.success) {
          this.searchResult = response.data.data
        } else {
          this.searchError = response.data.message || 'User not found'
        }
      } catch (error) {
        console.error('Error searching user:', error)
        this.searchError = error.response?.data?.message || 'User not found with this email'
      } finally {
        this.searching = false
      }
    },
    
    async addSearchedUser() {
      if (!this.searchResult) return
      
      try {
        await axios.post(`/api/admin/businesses/${this.business.id}/admins`, {
          user_id: this.searchResult.id,
          role: 'admin',
          is_primary: false
        })
        
        this.$emit('updated')
        this.fetchAdmins()
        this.searchResult = null
        this.searchEmail = ''
      } catch (error) {
        console.error('Error adding admin:', error)
        alert(error.response?.data?.message || 'Failed to add administrator')
      }
    },
    
    async removeAdmin(userId) {
      if (!confirm('Are you sure you want to remove this administrator?')) {
        return
      }
      
      try {
        await axios.delete(`/api/admin/businesses/${this.business.id}/admins/${userId}`)
        this.$emit('updated')
        this.fetchAdmins()
      } catch (error) {
        console.error('Error removing admin:', error)
        alert(error.response?.data?.message || 'Failed to remove administrator')
      }
    },
    
    async createAndAddUser() {
      if (!this.newUser.email) {
        alert('Email is required')
        return
      }
      
      this.addingUser = true
      try {
        // First create the user
        const userResponse = await axios.post('/api/admin/users', {
          first_name: this.newUser.first_name,
          last_name: this.newUser.last_name,
          email: this.newUser.email,
          role: 'user' // Default role
        })
        
        if (userResponse.data.success) {
          // Then add as admin
          await axios.post(`/api/admin/businesses/${this.business.id}/admins`, {
            user_id: userResponse.data.data.id,
            role: this.newUser.role,
            is_primary: false
          })
          
          this.$emit('updated')
          this.fetchAdmins()
          
          // Reset form
          this.newUser = {
            first_name: '',
            last_name:'',
                        email: '',
            role: 'admin'
          }
        } else {
          alert('Failed to create user')
        }
      } catch (error) {
        console.error('Error creating user:', error)
        alert(error.response?.data?.message || 'Failed to create user')
      } finally {
        this.addingUser = false
      }
    },
    
    formatRole(role) {
      return role ? role.charAt(0).toUpperCase() + role.slice(1) : 'Unknown'
    }
  }
}
</script>