<!-- resources/js/pages/BusinessManagement.vue -->
<template>
  <div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center py-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Business Management</h1>
            <p class="text-gray-600">Manage your businesses and organizations</p>
          </div>
          <div class="flex items-center space-x-4">
            <button
              @click="goToDashboard"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200"
            >
              Back to Dashboard
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Show Business List by default -->
      <BusinessList
        v-if="currentView === 'list'"
        ref="businessList"
        @create-business="showRegistrationForm"
        @edit-business="editBusiness"
        @delete-business="confirmDeleteBusiness"
        @manage-admins="manageAdmins"
      />

      <!-- Show Registration Form when creating new business -->
      <BusinessRegistration
        v-else-if="currentView === 'create'"
        @cancel="showBusinessList"
        @success="handleBusinessCreated"
      />

      <!-- Show Edit Form when editing business -->
      <BusinessEdit
        v-else-if="currentView === 'edit'"
        :business="selectedBusiness"
        @cancel="showBusinessList"
        @success="handleBusinessUpdated"
      />

      <!-- Show Admin Management -->
      <AdminManagement
        v-else-if="currentView === 'admins'"
        :business="selectedBusiness"
        @cancel="showBusinessList"
        @success="handleAdminsUpdated"
      />
    </main>

    <!-- Delete Confirmation Modal -->
    <DeleteConfirmationModal
      v-if="showDeleteModal"
      :business="businessToDelete"
      @confirm="deleteBusiness"
      @cancel="cancelDelete"
    />
  </div>
</template>

<script>
import axios from 'axios';
import BusinessList from '@/components/Business/BusinessList.vue'
import BusinessRegistration from '@/components/Business/BusinessRegistration.vue'
import BusinessEdit from '@/components/Business/BusinessEdit.vue'
import AdminManagement from '@/components/Business/AdminManagement.vue'
import DeleteConfirmationModal from '@/components/Business/DeleteConfirmationModal.vue'

export default {
  name: 'BusinessManagement',
  components: {
    BusinessList,
    BusinessRegistration,
    BusinessEdit,
    AdminManagement,
    DeleteConfirmationModal
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
        
        this.showDeleteModal = false
        this.businessToDelete = null
        
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
      this.showBusinessList()
      if (this.$refs.businessList) {
        this.$refs.businessList.fetchBusinesses()
      }
    },
    handleBusinessUpdated(business) {
      this.showBusinessList()
      if (this.$refs.businessList) {
        this.$refs.businessList.fetchBusinesses()
      }
    },
    handleAdminsUpdated() {
      this.showBusinessList()
      if (this.$refs.businessList) {
        this.$refs.businessList.fetchBusinesses()
      }
    },
    goToDashboard() {
      this.$router.push('/admin/dashboard')
    }
  }
}
</script>

