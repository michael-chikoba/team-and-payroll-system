<template>
  <div class="admin-settings">
    <div class="page-header">
      <h1>Admin Settings</h1>
      <button
        @click="saveSettings"
        class="btn-primary"
        :disabled="submitting || !hasChanges"
      >
        <span v-if="submitting">💾 Saving...</span>
        <span v-else>💾 Save Changes</span>
      </button>
    </div>
    <!-- Authentication Check -->
    <div v-if="!authStore.isAuthenticated" class="error-message">
      Please log in to access admin settings.
    </div>
    <!-- Permission Check -->
    <div v-else-if="!authStore.isAdmin" class="error-message">
      You don't have permission to access this page.
    </div>
    <!-- Loading State -->
    <div v-else-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Loading settings...</p>
    </div>
    <!-- Error State -->
    <div v-else-if="error" class="error-message">
      {{ error }}
      <button @click="retryFetch" class="btn-primary" style="margin-top: 1rem;">Retry</button>
    </div>
    <!-- Settings Content -->
    <div v-else class="settings-content">
      <!-- Company Information -->
      <div class="settings-section">
        <h2>Company Information</h2>
        <div class="settings-grid">
          <div class="setting-field">
            <label>Company Name *</label>
            <input
              v-model="settings.company_name"
              type="text"
              placeholder="Enter company name"
              required
              @input="onSettingChange"
            />
          </div>
          <div class="setting-field">
            <label>Company Address</label>
            <textarea
              v-model="settings.company_address"
              rows="3"
              placeholder="Enter company address"
              @input="onSettingChange"
            ></textarea>
          </div>
          <div class="setting-field">
            <label>Tax ID</label>
            <input
              v-model="settings.tax_id"
              type="text"
              placeholder="Enter tax ID"
              @input="onSettingChange"
            />
          </div>
          <div class="setting-field">
            <label>Currency</label>
            <select v-model="settings.currency" @change="onSettingChange">
              <option value="USD">USD</option>
              <option value="ZMW">ZMW</option>
              <option value="EUR">EUR</option>
            </select>
          </div>
        </div>
      </div>
      <!-- Leave Policies -->
      <div class="settings-section">
        <h2>Leave Policies</h2>
        <div class="settings-grid">
          <div class="setting-field">
            <label>Annual Leave Days</label>
            <input
              v-model.number="settings.annual_leave_days"
              type="number"
              min="0"
              placeholder="e.g., 21"
              @input="onSettingChange"
            />
          </div>
          <div class="setting-field">
            <label>Sick Leave Days</label>
            <input
              v-model.number="settings.sick_leave_days"
              type="number"
              min="0"
              placeholder="e.g., 7"
              @input="onSettingChange"
            />
          </div>
          <div class="setting-field">
            <label>Maternity Leave Days</label>
            <input
              v-model.number="settings.maternity_leave_days"
              type="number"
              min="0"
              placeholder="e.g., 90"
              @input="onSettingChange"
            />
          </div>
          <div class="setting-field">
            <label>Paternity Leave Days</label>
            <input
              v-model.number="settings.paternity_leave_days"
              type="number"
              min="0"
              placeholder="e.g., 14"
              @input="onSettingChange"
            />
          </div>
        </div>
      </div>
      <!-- System Settings -->
      <div class="settings-section">
        <h2>System Settings</h2>
        <div class="settings-grid">
          <div class="setting-field">
            <label>Default Password</label>
            <input
              v-model="settings.default_password"
              type="text"
              placeholder="e.g., Password123!"
              @input="onSettingChange"
            />
            <small>Used for new employee accounts</small>
          </div>
          <div class="setting-field">
            <label>Max Login Attempts</label>
            <input
              v-model.number="settings.max_login_attempts"
              type="number"
              min="1"
              max="10"
              placeholder="e.g., 5"
              @input="onSettingChange"
            />
          </div>
          <div class="setting-field">
            <label>Session Timeout (minutes)</label>
            <input
              v-model.number="settings.session_timeout"
              type="number"
              min="1"
              max="480"
              placeholder="e.g., 60"
              @input="onSettingChange"
            />
          </div>
        </div>
      </div>
      <!-- Departments Management -->
      <div class="settings-section">
        <h2>Departments</h2>
        <div class="departments-list">
          <div v-for="(dept, index) in settings.departments" :key="index" class="department-item">
            <input
              v-model="dept.name"
              type="text"
              placeholder="Department name"
              @input="onSettingChange"
            />
            <button
              @click="removeDepartment(index)"
              class="btn-delete"
              :disabled="settings.departments.length <= 1"
            >
              Remove
            </button>
          </div>
          <button @click="addDepartment" class="btn-add-dept">+ Add Department</button>
        </div>
      </div>
      <div v-if="successMessage" class="success-message">
        {{ successMessage }}
      </div>
    </div>
  </div>
</template>
<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'
export default {
  name: 'AdminSettings',
 
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
 
  data() {
    return {
      settings: {
        company_name: '',
        company_address: '',
        tax_id: '',
        currency: 'ZMW',
        annual_leave_days: 21,
        sick_leave_days: 7,
        maternity_leave_days: 90,
        paternity_leave_days: 14,
        default_password: 'Password123!',
        max_login_attempts: 5,
        session_timeout: 60,
        departments: []
      },
      originalSettings: null,
      loading: false,
      error: null,
      submitting: false,
      successMessage: null,
      retryCount: 0
    }
  },
 
  computed: {
    hasChanges() {
      if (!this.originalSettings) return false
      return JSON.stringify(this.settings) !== JSON.stringify(this.originalSettings)
    }
  },
 
  mounted() {
    this.initializeComponent()
  },
 
  methods: {
    initializeComponent() {
      if (!this.authStore.isAuthenticated) {
        this.error = 'Please log in to access admin settings.'
        return
      }
     
      if (!this.authStore.isAdmin) {
        this.error = 'You do not have permission to access this page.'
        return
      }
     
      this.fetchSettings()
    },
   
    async fetchSettings(retry = false) {
      this.loading = true
      this.error = null
     
      try {
        const response = await axios.get('/api/admin/settings')
       
        this.settings = { ...this.settings, ...response.data }
        this.originalSettings = JSON.parse(JSON.stringify(this.settings))
      } catch (err) {
        this.handleApiError(err)
      } finally {
        this.loading = false
      }
    },
   
    retryFetch() {
      this.retryCount++
      if (this.retryCount <= 3) {
        this.fetchSettings(true)
      } else {
        this.error = 'Max retries exceeded. Check your network or server.'
      }
    },
   
    async saveSettings() {
      this.submitting = true
      this.error = null
      this.successMessage = null
     
      try {
        await axios.put('/api/admin/settings', this.settings)
       
        this.successMessage = 'Settings updated successfully! Changes will reflect across the system.'
        this.originalSettings = JSON.parse(JSON.stringify(this.settings))
       
        // Show notification if available
        if (this.$notify) {
          this.$notify({
            type: 'success',
            title: 'Success',
            text: 'Admin settings updated successfully!'
          })
        }
      } catch (err) {
        this.handleApiError(err)
      } finally {
        this.submitting = false
      }
    },
   
    addDepartment() {
      this.settings.departments.push({ name: '' })
    },
   
    removeDepartment(index) {
      if (this.settings.departments.length > 1) {
        this.settings.departments.splice(index, 1)
      }
    },
   
    onSettingChange() {
      // This method is called whenever any setting changes
      // You can add debouncing or validation here if needed
    },
   
    handleApiError(err) {
      let errorMsg = 'An unexpected error occurred.'
     
      if (err.code === 'ERR_NETWORK' || err.message.includes('Network Error')) {
        errorMsg = 'Network error: Unable to connect to server.'
      } else if (err.response?.status === 401) {
        errorMsg = 'Your session has expired. Please log in again.'
        this.authStore.clearAuth()
        this.$router.push({ name: 'login' })
      } else if (err.response?.status === 403) {
        errorMsg = 'You do not have permission to perform this action.'
      } else if (err.response?.status === 422) {
        errorMsg = 'Validation error: ' + Object.values(err.response.data.errors || {}).flat().join(', ')
      } else {
        errorMsg = err.response?.data?.message || errorMsg
      }
     
      this.error = errorMsg
    }
  }
}
</script>
<style scoped>
/* Keep the same styles as before, they're perfect */
.admin-settings {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}
.page-header h1 {
  color: #2d3748;
  font-size: 2rem;
  margin: 0;
}
.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.75rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: transform 0.2s;
}
.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
}
.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.btn-delete {
  background: #fff1f0;
  color: #f5222d;
  border: 1px solid #f5222d;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.8rem;
  cursor: pointer;
}
.btn-delete:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
.btn-add-dept {
  background: #f6ffed;
  color: #52c41a;
  border: 1px solid #52c41a;
  padding: 0.5rem 1rem;
  border-radius: 4px;
  cursor: pointer;
  margin-top: 0.5rem;
}
.loading {
  text-align: center;
  padding: 4rem;
}
.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
.error-message {
  background: #fee;
  color: #c33;
  padding: 1rem;
  border-radius: 8px;
  text-align: center;
}
.success-message {
  background: #f6ffed;
  color: #52c41a;
  padding: 1rem;
  border-radius: 8px;
  text-align: center;
  margin-top: 1rem;
}
.settings-content {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}
.settings-section {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  padding: 1.5rem;
}
.settings-section h2 {
  margin: 0 0 1rem 0;
  color: #2d3748;
  font-size: 1.25rem;
  border-bottom: 2px solid #f0f0f0;
  padding-bottom: 0.5rem;
}
.settings-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1rem;
}
.setting-field {
  display: flex;
  flex-direction: column;
}
.setting-field label {
  margin-bottom: 0.5rem;
  color: #4a5568;
  font-weight: 600;
}
.setting-field input,
.setting-field select,
.setting-field textarea {
  padding: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 1rem;
}
.setting-field input:focus,
.setting-field select:focus,
.setting-field textarea:focus {
  outline: none;
  border-color: #667eea;
}
.setting-field small {
  color: #718096;
  font-size: 0.8rem;
  margin-top: 0.25rem;
}
.departments-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.department-item {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}
.department-item input {
  flex: 1;
  padding: 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
}
@media (max-width: 768px) {
  .settings-grid {
    grid-template-columns: 1fr;
  }
 
  .page-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
 
  .department-item {
    flex-direction: column;
    align-items: stretch;
  }
 
  .department-item input {
    margin-bottom: 0.5rem;
  }
}
</style>