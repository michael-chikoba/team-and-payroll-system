<template>
  <div class="business-management">
    <!-- ── Main (header card lives inside here) ───────── -->
    <div class="management-main">

      <!-- ── Header Card ─────────────────────────────── -->
      <div class="management-header-card">
        <div class="header-card-accent"></div>
        <div class="header-inner">

          <!-- Brand + Title -->
          <div class="user-greeting">
            <div class="avatar-section">
              <div class="avatar">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                  <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
              </div>
              <div class="user-info">
                <h1 class="greeting">Business Management</h1>
                <p class="subtitle">Centralized hub for managing your organizations and business entities</p>
                <div class="role-meta">
                  <span class="role-badge">Admin View</span>
                  <span class="month-badge" v-if="currentBusiness">Active: {{ currentBusiness.name }}</span>
                  <span class="month-badge" v-else>No Business Selected</span>
                </div>
              </div>
            </div>

            <!-- Controls - Removed arrow button, kept Add Business button -->
            <div class="header-controls">
              <button @click="showRegistrationForm" class="btn-primary" v-if="currentView === 'list'">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <line x1="12" y1="5" x2="12" y2="19"></line>
                  <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Business
              </button>
            </div>
          </div>

          <!-- View badge -->
          <div class="view-badge">
            <div class="view-content">
              <span class="view-primary">{{ currentView === 'list' ? 'List' : currentView === 'create' ? 'Create' : currentView === 'edit' ? 'Edit' : 'Admins' }}</span>
              <div class="view-details">
                <span class="view-label">Current View</span>
                <span class="view-total">{{ currentView === 'list' ? 'All Businesses' : 'Business Details' }}</span>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- ── Main Content Area ───────────────────────── -->
      <Transition name="fade" mode="out-in">
        <div :key="currentView" class="management-content">
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
    </div>

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
/* ── Base ─────────────────────────────────────────────── */
.business-management {
  min-height: 100vh;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
}

/* ── Main wrapper ─────────────────────────────────────── */
.management-main {
  max-width: 1400px;
  margin: 0 auto;
  padding: 1.5rem 2rem 3rem;
}

/* ── Header Card ──────────────────────────────────────── */
.management-header-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem 1.75rem;
  box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.03);
  border: 1px solid #e2e8f0;
  margin-bottom: 1.25rem;
  position: relative;
  overflow: hidden;
}

.header-card-accent {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
}

.management-header-card::after {
  content: '';
  position: absolute;
  top: -20px;
  right: -20px;
  width: 160px;
  height: 160px;
  background: radial-gradient(circle, rgba(139, 92, 246, 0.05) 0%, transparent 70%);
  pointer-events: none;
}

.header-inner {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1.5rem;
  flex-wrap: wrap;
}

.user-greeting {
  display: flex;
  align-items: center;
  gap: 2rem;
  flex: 1;
  flex-wrap: wrap;
}

/* Avatar */
.avatar-section {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.avatar {
  width: 52px;
  height: 52px;
  background: linear-gradient(135deg, #305cc2, #305cc2);
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 4px 12px rgba(41, 48, 184, 0.25);
  flex-shrink: 0;
}

.user-info {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
}

.greeting {
  margin: 0;
  font-size: 1.375rem;
  font-weight: 700;
  color: #1e293b;
  line-height: 1.2;
}

.subtitle {
  margin: 0;
  color: #64748b;
  font-size: 0.875rem;
}

.role-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 0.125rem;
  flex-wrap: wrap;
}

.role-badge {
  background: #f5f3ff;
  border: 1px solid #ddd6fe;
  padding: 0.125rem 0.6rem;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 600;
  color: #305cc2;
  display: inline-flex;
  align-items: center;
}

.month-badge {
  background: #ede9fe;
  border: 1px solid #ddd6fe;
  padding: 0.125rem 0.6rem;
  border-radius: 8px;
  font-size: 0.7rem;
  font-weight: 600;
  color: #305cc2;
  display: inline-flex;
  align-items: center;
}

/* Controls */
.header-controls {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex-wrap: wrap;
}

/* Blue Add Business button (replacing btn-accent) */
.btn-primary {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white;
  border: none;
  padding: 0.55rem 1.1rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  transition: all 0.2s;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  white-space: nowrap;
}

.btn-primary:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
}

.btn-primary:active {
  transform: translateY(0);
}

/* View badge */
.view-badge {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 0.75rem 1.125rem;
  min-width: 130px;
  flex-shrink: 0;
}

.view-content {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.view-primary {
  font-size: 1.5rem;
  font-weight: 700;
  color: #3b82f6;
  line-height: 1;
  text-transform: capitalize;
}

.view-details {
  display: flex;
  flex-direction: column;
}

.view-label {
  font-size: 0.7rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.view-total {
  font-size: 0.7rem;
  color: #94a3b8;
  text-transform: capitalize;
}

/* ── Management Content ───────────────────────────────── */
.management-content {
  background: white;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.05);
  overflow: hidden;
  min-height: 400px;
  padding: 1.5rem;
}

/* ── Modern fade transition ───────────────────────────── */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* ── Responsive ───────────────────────────────────────── */
@media (max-width: 1024px) {
  .management-main {
    padding: 1.5rem;
  }
}

@media (max-width: 768px) {
  .management-main {
    padding: 1rem;
  }
  
  .header-inner {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .user-greeting {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .header-controls {
    width: 100%;
  }
  
  .btn-primary {
    width: 100%;
    justify-content: center;
  }
  
  .greeting {
    font-size: 1.25rem;
  }
  
  .view-badge {
    align-self: flex-start;
  }
  
  .management-content {
    padding: 1rem;
  }
}

@media (max-width: 480px) {
  .header-controls {
    flex-direction: column;
    align-items: stretch;
  }
  
  .btn-primary {
    width: 100%;
    justify-content: center;
  }
  
  .role-meta {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>