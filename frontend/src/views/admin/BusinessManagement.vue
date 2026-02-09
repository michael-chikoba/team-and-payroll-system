<template>
  <div class="min-h-screen bg-gray-50">
    <header class="bg-white shadow-md border-b border-gray-200 sticky top-0 z-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-4">
          <div>
            <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">
              <span class="text-indigo-700">Business</span> Management
            </h1>
            <p class="text-sm text-gray-600 mt-1 font-medium">
              Centralized hub for managing your organizations and business entities.
            </p>
            <!-- Show current business indicator -->
            <div v-if="currentBusiness" class="mt-2 flex items-center">
              <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                Active: {{ currentBusiness.name }}
              </span>
            </div>
          </div>
          <div class="flex items-center space-x-4">
            <button
              @click="goToDashboard"
              class="inline-flex items-center px-5 py-2 border border-transparent text-sm font-bold rounded-xl shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-500 focus:ring-opacity-50 transition transform hover:scale-[1.02] duration-200 ease-in-out"
            >
              <svg class="w-5 h-5 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
              </svg>
              Back to Dashboard
            </button>
          </div>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 text-gray-900">
      <Transition name="fade" mode="out-in">
        <div :key="currentView" class="bg-white rounded-lg shadow p-1 min-h-[400px]">
          <BusinessList
            v-if="currentView === 'list'"
            ref="businessList"
            @create-business="showRegistrationForm"
            @edit-business="editBusiness"
            @delete-business="confirmDeleteBusiness"
            @manage-admins="manageAdmins"
            @business-switched="handleBusinessSwitched"
          />

          <BusinessRegistration
            v-else-if="currentView === 'create'"
            @cancel="showBusinessList"
            @success="handleBusinessCreated"
          />

          <BusinessEdit
            v-else-if="currentView === 'edit'"
            :business="selectedBusiness"
            @cancel="showBusinessList"
            @success="handleBusinessUpdated"
          />

          <AdminManagement
            v-else-if="currentView === 'admins'"
            :business="selectedBusiness"
            @cancel="showBusinessList"
            @success="handleAdminsUpdated"
            @search-user="searchUser"
            @add-admin="addAdmin"
            @remove-admin="removeAdmin"
          />
        </div>
      </Transition>
    </main>

    <DeleteConfirmationModal
      v-if="showDeleteModal"
      :business="businessToDelete"
      @confirm="deleteBusiness"
      @cancel="cancelDelete"
    />
  </div>
</template>

<script>
import axios from 'axios'
import BusinessList from '@/components/Business/BusinessList.vue'
import BusinessRegistration from '@/components/Business/BusinessRegistration.vue'
import BusinessEdit from '@/components/Business/BusinessEdit.vue'
import AdminManagement from '@/components/Business/AdminManagement.vue'
import DeleteConfirmationModal from '@/components/Business/DeleteConfirmationModal.vue'
import { useBusinessStore } from '@/stores/business'
import { useAuthStore } from '@/stores/auth'
import { computed } from 'vue'

export default {
  name: 'BusinessManagement',
  components: {
    BusinessList,
    BusinessRegistration,
    BusinessEdit,
    AdminManagement,
    DeleteConfirmationModal
  },
  setup() {
    const businessStore = useBusinessStore()
    const authStore = useAuthStore()
    
    return {
      businessStore,
      authStore,
      currentBusiness: computed(() => businessStore.getCurrentBusiness)
    }
  },
  data() {
    return {
      currentView: 'list',
      selectedBusiness: null,
      showDeleteModal: false,
      businessToDelete: null
    }
  },
  methods: {
    showBusinessList() {
      this.currentView = 'list'
      this.selectedBusiness = null
    },
    
    showRegistrationForm() {
      this.currentView = 'create'
    },
    
    editBusiness(business) {
      this.selectedBusiness = business
      this.currentView = 'edit'
    },
    
    manageAdmins(business) {
      this.selectedBusiness = business
      this.currentView = 'admins'
    },
    
    confirmDeleteBusiness(business) {
      this.businessToDelete = business
      this.showDeleteModal = true
    },
    
    async deleteBusiness() {
      try {
        await axios.delete(`/api/admin/businesses/${this.businessToDelete.id}`)
        
        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Business deleted successfully'
        })
        
        // Update the store
        this.businessStore.removeBusiness(this.businessToDelete.id)
        
        this.showDeleteModal = false
        this.businessToDelete = null
        
        // Refresh the list
        if (this.$refs.businessList) {
          this.$refs.businessList.fetchBusinesses()
        }
      } catch (error) {
        this.$notify({
          type: 'error',
          title: 'Error',
          text: error.response?.data?.message || 'Failed to delete business'
        })
      }
    },
    
    cancelDelete() {
      this.showDeleteModal = false
      this.businessToDelete = null
    },
    
    handleBusinessCreated(business) {
      // Add to store
      this.businessStore.addBusiness(business)
      
      this.showBusinessList()
      this.$nextTick(() => {
        if (this.$refs.businessList) {
          this.$refs.businessList.fetchBusinesses()
        }
      })
    },
    
    handleBusinessUpdated(business) {
      // Update in store
      this.businessStore.updateBusiness(business.id, business)
      
      this.showBusinessList()
      this.$nextTick(() => {
        if (this.$refs.businessList) {
          this.$refs.businessList.fetchBusinesses()
        }
      })
    },
    
    handleAdminsUpdated() {
      this.showBusinessList()
      this.$nextTick(() => {
        if (this.$refs.businessList) {
          this.$refs.businessList.fetchBusinesses()
        }
      })
    },
    
    /**
     * Handle business switch event from child component
     */
    handleBusinessSwitched(business) {
      console.log('Business switched to:', business.name)
      
      // Optionally navigate to a different page or refresh certain data
      // For example, you might want to refresh the dashboard
      this.$notify({
        type: 'info',
        title: 'Context Updated',
        text: `Now working in ${business.name}`
      })
    },
    
    goToDashboard() {
      this.$router.push('/admin/dashboard')
    },
    
    async searchUser(email) {
      try {
        const response = await axios.get('/api/admin/users/search', {
          params: { email }
        })
        
        if (response.data.success) {
          this.$notify({
            type: 'success',
            title: 'User Found',
            text: `Found user: ${response.data.data.first_name} ${response.data.data.last_name}`
          })
          return response.data.data
        } else {
          throw new Error(response.data.message || 'User not found')
        }
      } catch (error) {
        console.error('Error searching for user:', error)
        this.$notify({
          type: 'error',
          title: 'Search Failed',
          text: error.response?.data?.message || 'User not found with this email'
        })
        throw error
      }
    },
    
    async addAdmin(businessId, userId, role) {
      try {
        const response = await axios.post(`/api/admin/businesses/${businessId}/admins`, {
          user_id: userId,
          role: role,
          is_primary: false
        })
        
        if (response.data.success) {
          this.$notify({
            type: 'success',
            title: 'Admin Added',
            text: 'Administrator added successfully to the business'
          })
          return response.data
        } else {
          throw new Error(response.data.message || 'Failed to add admin')
        }
      } catch (error) {
        console.error('Error adding admin:', error)
        this.$notify({
          type: 'error',
          title: 'Failed to Add Admin',
          text: error.response?.data?.message || 'Could not add administrator to the business'
        })
        throw error
      }
    },
    
    async removeAdmin(businessId, adminUserId) {
      try {
        const response = await axios.delete(`/api/admin/businesses/${businessId}/admins/${adminUserId}`)
        
        if (response.data.success) {
          this.$notify({
            type: 'success',
            title: 'Admin Removed',
            text: 'Administrator removed successfully from the business'
          })
          return response.data
        } else {
          throw new Error(response.data.message || 'Failed to remove admin')
        }
      } catch (error) {
        console.error('Error removing admin:', error)
        this.$notify({
          type: 'error',
          title: 'Failed to Remove Admin',
          text: error.response?.data?.message || 'Could not remove administrator from the business'
        })
        throw error
      }
    }
  }
}
</script>

<style scoped>
/* Modern fade transition for view switching */
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>