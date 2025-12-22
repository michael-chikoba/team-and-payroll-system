<template>
  <div class="admin-settings-container">
    <!-- Loading Overlay -->
    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading configuration...</p>
    </div>

    <!-- Main Content -->
    <template v-else>
      <!-- Header Section -->
      <header class="page-header">
        <div class="header-content">
          <h1>⚙️ Business Settings</h1>
          <p class="subtitle">Manage configuration for your business locations and branches.</p>
        </div>
        
        <div class="header-actions">
          <div class="business-selector-wrapper">
            <label for="business-select">Active Business</label>
            <div class="select-container">
              <span class="select-icon">🏢</span>
              <select 
                id="business-select" 
                v-model="selectedBusinessId" 
                @change="loadBusinessSettings"
              >
                <option :value="null">-- Select a business --</option>
                <option 
                  v-for="business in businesses" 
                  :key="business.id" 
                  :value="business.id"
                >
                  {{ business.name }} ({{ business.country_name }})
                </option>
              </select>
            </div>
          </div>
          
          <button 
            v-if="!selectedBusinessId && isSuperAdmin"
            class="btn btn-primary" 
            @click="$router.push({ name: 'create-business' })"
          >
            <span class="icon">➕</span> Create Business
          </button>
        </div>
      </header>

      <!-- Feedback Messages -->
      <transition name="fade">
        <div v-if="successMessage" class="alert alert-success">
          <span class="icon">✅</span> {{ successMessage }}
        </div>
      </transition>

      <transition name="fade">
        <div v-if="error" class="alert alert-error">
          <div class="alert-content">
            <span class="icon">⚠️</span> {{ error }}
          </div>
          <!-- Initialize Button (Only if settings missing) -->
          <button 
            v-if="isSettingsMissing && selectedBusinessId" 
            @click="initializeSelectedBusiness"
            class="btn btn-sm btn-white"
          >
            Initialize Now
          </button>
        </div>
      </transition>

      <!-- Empty State -->
      <div v-if="!selectedBusinessId" class="empty-state">
        <div class="empty-icon">🏢</div>
        <h3>No Business Selected</h3>
        <p>Please select a business from the dropdown above to manage its settings.</p>
      </div>

      <!-- Settings Form -->
      <div v-else-if="selectedBusinessId && (!error || isSettingsMissing)" class="settings-wrapper">
        
        <!-- Info Banner -->
        <div v-if="currentBusiness" class="info-banner">
          <div class="banner-icon">{{ currentBusiness.flag_emoji || '🏢' }}</div>
          <div class="banner-details">
            <h2>{{ currentBusiness.name }}</h2>
            <div class="meta-tags">
              <span class="tag">🌍 {{ currentBusiness.country_name }}</span>
              <span v-if="countryInfo" class="tag">💱 {{ countryInfo.currency }}</span>
              <span v-if="countryInfo" class="tag">🕐 {{ countryInfo.timezone }}</span>
            </div>
          </div>
        </div>

        <div class="cards-grid">
          <!-- 1. Company Information -->
          <div class="card">
            <div class="card-header">
              <h3>🏢 Business Information</h3>
            </div>
            <div class="card-body">
              <div class="form-grid">
                <div class="form-group">
                  <label>Business Name <span class="required">*</span></label>
                  <input 
                    v-model="settings.company_name" 
                    type="text" 
                    class="form-control"
                    placeholder="e.g. Lusaka HQ"
                    @input="onSettingChange"
                  />
                </div>
                
                <div class="form-group">
                  <label>Tax / Registration ID</label>
                  <input 
                    v-model="settings.tax_id" 
                    type="text" 
                    class="form-control"
                    placeholder="e.g. TPIN-123456"
                    @input="onSettingChange"
                  />
                </div>
                
                <div class="form-group">
                  <label>Currency <span class="required">*</span></label>
                  <select v-model="settings.currency" class="form-control" @change="onSettingChange">
                    <option disabled value="">Select Currency</option>
                    <optgroup label="Common">
                      <option value="ZMW">ZMW - Zambian Kwacha</option>
                      <option value="USD">USD - US Dollar</option>
                      <option value="GBP">GBP - British Pound</option>
                      <option value="EUR">EUR - Euro</option>
                      <option value="ZAR">ZAR - South African Rand</option>
                    </optgroup>
                    <optgroup label="African Currencies">
                      <option v-for="curr in africanCurrencies" :key="curr.code" :value="curr.code">
                        {{ curr.code }} - {{ curr.name }}
                      </option>
                    </optgroup>
                  </select>
                </div>
                
                <div class="form-group">
                  <label>Date Format</label>
                  <select v-model="settings.date_format" class="form-control" @change="onSettingChange">
                    <option value="d/m/Y">DD/MM/YYYY </option>
                    <option value="m/d/Y">MM/DD/YYYY </option>
                    <option value="Y-m-d">YYYY-MM-DD </option>
                    <option value="d M Y">DD MMM YYYY </option>
                  </select>
                </div>

                <div class="form-group full-width">
                  <label>Physical Address <span class="required">*</span></label>
                  <textarea 
                    v-model="settings.company_address" 
                    rows="2" 
                    class="form-control"
                    placeholder="Street, City, Province"
                    @input="onSettingChange"
                  ></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- 2. Departments -->
          <div class="card">
            <div class="card-header">
              <h3>🏛️ Departments</h3>
              <button class="btn-text" @click="addDepartment">
                <span>+ Add New</span>
              </button>
            </div>
            <div class="card-body">
              <p class="helper-text">Define the operational departments for this branch.</p>
              <div class="departments-list">
                <div 
                  v-for="(dept, index) in settings.departments" 
                  :key="index" 
                  class="department-row"
                >
                  <input 
                    v-model="dept.name" 
                    type="text" 
                    class="form-control"
                    placeholder="Department Name"
                    @input="onSettingChange"
                  />
                  <button 
                    class="btn-icon delete" 
                    @click="removeDepartment(index)"
                    :disabled="settings.departments.length <= 1"
                    title="Remove Department"
                  >
                    🗑️
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- 3. Leave Policies -->
          <div class="card">
            <div class="card-header">
              <h3>🏖️ Leave Policies</h3>
            </div>
            <div class="card-body">
              <div class="form-grid">
                <div class="form-group">
                  <label>Annual Leave (Days)</label>
                  <div class="input-suffix">
                    <input 
                      v-model.number="settings.annual_leave_days" 
                      type="number" 
                      class="form-control"
                      min="0"
                      @input="onSettingChange"
                    />
                    <span>/ yr</span>
                  </div>
                </div>
                
                <div class="form-group">
                  <label>Sick Leave (Days)</label>
                  <div class="input-suffix">
                    <input 
                      v-model.number="settings.sick_leave_days" 
                      type="number" 
                      class="form-control"
                      min="0"
                      @input="onSettingChange"
                    />
                    <span>/ yr</span>
                  </div>
                </div>
                
                <div class="form-group">
                  <label>Maternity Leave</label>
                  <div class="input-suffix">
                    <input 
                      v-model.number="settings.maternity_leave_days" 
                      type="number" 
                      class="form-control"
                      min="0"
                      @input="onSettingChange"
                    />
                    <span>Days</span>
                  </div>
                </div>
                
                <div class="form-group">
                  <label>Paternity Leave</label>
                  <div class="input-suffix">
                    <input 
                      v-model.number="settings.paternity_leave_days" 
                      type="number" 
                      class="form-control"
                      min="0"
                      @input="onSettingChange"
                    />
                    <span>Days</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 4. Security -->
          <div class="card">
            <div class="card-header">
              <h3>🔒 Security</h3>
            </div>
            <div class="card-body">
              <div class="form-grid">
                <div class="form-group">
                  <label>Default Password</label>
                  <input 
                    v-model="settings.default_password" 
                    type="text" 
                    class="form-control"
                    @input="onSettingChange"
                  />
                  <small class="hint">For new employee accounts</small>
                </div>
                
                <div class="form-group">
                  <label>Max Login Attempts</label>
                  <input 
                    v-model.number="settings.max_login_attempts" 
                    type="number" 
                    class="form-control"
                    min="1" max="10"
                    @input="onSettingChange"
                  />
                </div>
                
                <div class="form-group">
                  <label>Session Timeout</label>
                  <div class="input-suffix">
                    <input 
                      v-model.number="settings.session_timeout" 
                      type="number" 
                      class="form-control"
                      min="1"
                      @input="onSettingChange"
                    />
                    <span>min</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sticky Footer Action Bar -->
        <div class="action-bar">
          <div class="action-bar-content">
            <span class="change-indicator" :class="{ visible: hasChanges }">
              ⚠️ You have unsaved changes
            </span>
            <div class="action-buttons">
              <button 
                class="btn btn-secondary" 
                @click="loadBusinessSettings" 
                :disabled="(!hasChanges && !isSettingsMissing) || submitting"
              >
                Reset
              </button>
              
              <button 
                v-if="!isSettingsMissing"
                class="btn btn-primary" 
                @click="saveSettings" 
                :disabled="!hasChanges || submitting"
              >
                <span v-if="submitting" class="spinner-small"></span>
                {{ submitting ? 'Saving...' : 'Save Settings' }}
              </button>
              
              <button 
                v-else
                class="btn btn-primary" 
                @click="initializeSelectedBusiness" 
                :disabled="submitting"
              >
                <span v-if="submitting" class="spinner-small"></span>
                {{ submitting ? 'Initializing...' : 'Initialize Settings' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </template>
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
      selectedBusinessId: null,
      businesses: [],
      currentBusiness: null,
      countryInfo: null,
      
      // Default Settings Structure
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
        date_format: 'd/m/Y',
        departments: []
      },
      
      originalSettings: null,
      loading: false,
      error: null,
      isSettingsMissing: false,
      submitting: false,
      successMessage: null,

      // Expanded African Currency List
      africanCurrencies: [
        { code: 'NGN', name: 'Nigerian Naira' },
        { code: 'KES', name: 'Kenyan Shilling' },
        { code: 'GHS', name: 'Ghanaian Cedi' },
        { code: 'EGP', name: 'Egyptian Pound' },
        { code: 'RWF', name: 'Rwandan Franc' },
        { code: 'TZS', name: 'Tanzanian Shilling' },
        { code: 'UGX', name: 'Ugandan Shilling' },
        { code: 'BWP', name: 'Botswana Pula' },
        { code: 'MAD', name: 'Moroccan Dirham' },
        { code: 'XOF', name: 'West African CFA' },
        { code: 'XAF', name: 'Central African CFA' },
        { code: 'MUR', name: 'Mauritian Rupee' },
        { code: 'NAD', name: 'Namibian Dollar' },
        { code: 'ETB', name: 'Ethiopian Birr' },
        { code: 'MWK', name: 'Malawian Kwacha' },
        { code: 'MZN', name: 'Mozambican Metical' },
        { code: 'AOA', name: 'Angolan Kwanza' }
      ]
    }
  },

  computed: {
    hasChanges() {
      if (!this.originalSettings) return false
      return JSON.stringify(this.settings) !== JSON.stringify(this.originalSettings)
    },
    
    selectedBusinessName() {
      const business = this.businesses.find(b => b.id == this.selectedBusinessId)
      return business ? business.name : ''
    },
    
    isSuperAdmin() {
      return this.authStore.user?.role === 'super_admin'
    }
  },

  mounted() {
    this.initializeComponent()
  },

  methods: {
    async initializeComponent() {
      if (!this.authStore.isAuthenticated) {
        this.error = 'Please log in to access settings.'
        return
      }
      
      await this.loadBusinesses()
      
      // Auto-select business if stored in user profile or only one exists
      if (this.authStore.user?.current_business_id && !this.selectedBusinessId) {
        this.selectedBusinessId = this.authStore.user.current_business_id
      }
      
      if (this.selectedBusinessId) {
        await this.loadBusinessSettings()
      }
    },
   
    async loadBusinesses() {
      try {
        // Keeping original API endpoint
        const response = await axios.get('/api/admin/businesses-with-countries')
        this.businesses = response.data.businesses || []
      } catch (err) {
        console.error('Failed to load businesses:', err)
        this.error = 'Failed to load businesses list.'
      }
    },
   
    async loadBusinessSettings() {
      this.error = null
      this.successMessage = null
      this.isSettingsMissing = false
      this.countryInfo = null
      
      if (!this.selectedBusinessId) return

      this.loading = true
      this.currentBusiness = this.businesses.find(b => b.id == this.selectedBusinessId)

      try {
        // Keeping original API endpoint
        const response = await axios.get('/api/admin/settings', {
          params: { business_id: this.selectedBusinessId }
        })
       
        if (response.data && response.data.settings) {
          this.settings = { 
            ...this.settings,
            ...response.data.settings,
            business_id: this.selectedBusinessId
          }
          
          // Parsing logic to handle if backend returns stringified JSON or Array
          if (typeof this.settings.departments === 'string') {
             try {
               this.settings.departments = JSON.parse(this.settings.departments)
             } catch(e) {
               this.settings.departments = []
             }
          } else if (!Array.isArray(this.settings.departments)) {
             this.settings.departments = []
          }

          // Ensure at least one department row exists or default
          if (this.settings.departments.length === 0) {
             this.settings.departments = [{ name: 'General' }]
          }

          this.countryInfo = response.data.country_info
          this.originalSettings = JSON.parse(JSON.stringify(this.settings))
        }
      } catch (err) {
        // 404 handling for missing settings
        if (err.response?.status === 404) {
          this.error = `Settings not yet configured for ${this.selectedBusinessName}.`
          this.isSettingsMissing = true
          this.prepareDefaultSettings()
        } else {
          this.handleApiError(err)
        }
      } finally {
        this.loading = false
      }
    },
    
    prepareDefaultSettings() {
      const business = this.businesses.find(b => b.id == this.selectedBusinessId)
      
      this.settings = {
        business_id: this.selectedBusinessId,
        company_name: business?.name || '',
        company_address: '',
        tax_id: business?.tax_identification_number || '',
        currency: business?.currency_code || 'ZMW',
        annual_leave_days: 21,
        sick_leave_days: 7,
        maternity_leave_days: 90,
        paternity_leave_days: 14,
        default_password: 'Password123!',
        max_login_attempts: 5,
        session_timeout: 60,
        date_format: 'd/m/Y',
        // Default departments as requested by the system settings logic
        departments: [
          { name: 'Human Resources' },
          { name: 'Finance' },
          { name: 'Operations' },
          { name: 'IT' }
        ]
      }
    },
    
    async initializeSelectedBusiness() {
      this.submitting = true
      this.error = null
      
      try {
        // Keeping original API endpoint
        await axios.post('/api/admin/business-settings/initialize', this.settings)
        this.successMessage = `Settings initialized successfully for ${this.selectedBusinessName}!`
        this.isSettingsMissing = false
        await this.loadBusinessSettings()
      } catch (err) {
        this.handleApiError(err)
      } finally {
        this.submitting = false
      }
    },
   
    async saveSettings() {
      this.submitting = true
      this.error = null
      this.successMessage = null
     
      try {
        // Keeping original API endpoint
        const response = await axios.put('/api/admin/settings', {
          ...this.settings,
          business_id: this.selectedBusinessId
        })
       
        this.successMessage = response.data.message || 'Settings updated successfully!'
        this.originalSettings = JSON.parse(JSON.stringify(this.settings))
        
        if (this.settings.company_name !== this.currentBusiness.name) {
          await this.loadBusinesses()
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
      if (this.settings.departments.length > 0) {
        this.settings.departments.splice(index, 1)
      }
    },
   
    onSettingChange() {
      // Triggers computed properties
    },
   
    handleApiError(err) {
      let errorMsg = 'An unexpected error occurred.'
     
      if (err.code === 'ERR_NETWORK') {
        errorMsg = 'Network error: Unable to connect to server.'
      } else if (err.response?.status === 401) {
        errorMsg = 'Session expired.'
        this.authStore.clearAuth()
        this.$router.push({ name: 'login' })
      } else if (err.response?.status === 422) {
        const errors = err.response.data.errors
        errorMsg = errors ? Object.values(errors).flat().join(', ') : err.response.data.message
      } else {
        errorMsg = err.response?.data?.message || errorMsg
      }
     
      this.error = errorMsg
    }
  }
}
</script>

<style scoped>
/* Variables & Reset */
.admin-settings-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 2rem;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  color: #1e293b;
  min-height: 100vh;
  padding-bottom: 5rem; /* Space for sticky footer */
}

/* Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 2rem;
  margin-bottom: 2.5rem;
}

.page-header h1 {
  font-size: 1.875rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.subtitle {
  color: #64748b;
  margin-top: 0.5rem;
}

.business-selector-wrapper {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.business-selector-wrapper label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #475569;
}

.select-container {
  position: relative;
  background: white;
  border: 1px solid #cbd5e1;
  border-radius: 0.5rem;
  display: flex;
  align-items: center;
  transition: all 0.2s;
}

.select-container:focus-within {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.select-icon {
  padding-left: 0.75rem;
  font-size: 1.25rem;
}

select {
  appearance: none;
  background: transparent;
  border: none;
  padding: 0.75rem 2.5rem 0.75rem 0.75rem;
  font-size: 0.95rem;
  color: #1e293b;
  width: 100%;
  min-width: 280px;
  cursor: pointer;
}

select:focus {
  outline: none;
}

/* Cards & Layout */
.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
  gap: 1.5rem;
}

.card {
  background: white;
  border-radius: 1rem;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
  border: 1px solid #f1f5f9;
  display: flex;
  flex-direction: column;
}

.card-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid #f1f5f9;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h3 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #334155;
}

.card-body {
  padding: 1.5rem;
  flex: 1;
}

/* Info Banner */
.info-banner {
  background: linear-gradient(to right, #eff6ff, #ffffff);
  border: 1px solid #bfdbfe;
  border-radius: 0.75rem;
  padding: 1.5rem;
  margin-bottom: 2rem;
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.banner-icon {
  font-size: 2.5rem;
  background: white;
  width: 60px;
  height: 60px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.banner-details h2 {
  margin: 0 0 0.5rem 0;
  font-size: 1.25rem;
  color: #1e40af;
}

.meta-tags {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.tag {
  background: white;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.8rem;
  font-weight: 500;
  color: #1e40af;
  border: 1px solid #dbeafe;
}

/* Forms */
.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.25rem;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-group label {
  display: block;
  font-size: 0.875rem;
  font-weight: 500;
  color: #64748b;
  margin-bottom: 0.5rem;
}

.required {
  color: #ef4444;
}

.form-control {
  width: 100%;
  padding: 0.65rem 0.75rem;
  border: 1px solid #cbd5e1;
  border-radius: 0.5rem;
  font-size: 0.95rem;
  transition: all 0.2s;
  background: #fff;
  color: #1e293b;
  box-sizing: border-box; /* Crucial for padding */
}

.form-control:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.input-suffix {
  position: relative;
  display: flex;
  align-items: center;
}

.input-suffix input {
  padding-right: 3.5rem;
}

.input-suffix span {
  position: absolute;
  right: 1rem;
  color: #94a3b8;
  font-size: 0.875rem;
  pointer-events: none;
}

textarea.form-control {
  resize: vertical;
}

.hint {
  font-size: 0.75rem;
  color: #94a3b8;
  margin-top: 0.25rem;
  display: block;
}

/* Departments List */
.departments-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.department-row {
  display: flex;
  gap: 0.5rem;
  align-items: center;
}

.btn-icon {
  background: transparent;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-icon:hover:not(:disabled) {
  background: #fee2e2;
  border-color: #fca5a5;
}

.btn-icon:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  background: #f1f5f9;
}

.helper-text {
  font-size: 0.875rem;
  color: #64748b;
  margin-bottom: 1rem;
}

/* Buttons */
.btn {
  padding: 0.65rem 1.25rem;
  border-radius: 0.5rem;
  font-weight: 500;
  font-size: 0.95rem;
  border: none;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.2s;
}

.btn-primary {
  background: #4f46e5;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #4338ca;
  transform: translateY(-1px);
}

.btn-secondary {
  background: white;
  border: 1px solid #cbd5e1;
  color: #475569;
}

.btn-secondary:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #94a3b8;
}

.btn-text {
  background: none;
  border: none;
  color: #4f46e5;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
}

.btn-text:hover {
  text-decoration: underline;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Alerts */
.alert {
  padding: 1rem;
  border-radius: 0.5rem;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.alert-error {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
}

.alert-success {
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  color: #166534;
}

.btn-sm {
  padding: 0.25rem 0.75rem;
  font-size: 0.8rem;
}

.btn-white {
  background: white;
  border: 1px solid #e5e7eb;
}

/* Sticky Action Bar */
.action-bar {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: white;
  border-top: 1px solid #e2e8f0;
  padding: 1rem 2rem;
  box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.05);
  z-index: 50;
}

.action-bar-content {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.change-indicator {
  color: #d97706;
  font-weight: 500;
  opacity: 0;
  transition: opacity 0.3s;
}

.change-indicator.visible {
  opacity: 1;
}

.action-buttons {
  display: flex;
  gap: 1rem;
}

/* States */
.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 400px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #e2e8f0;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

.spinner-small {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: #f8fafc;
  border-radius: 1rem;
  border: 2px dashed #cbd5e1;
}

.empty-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

@keyframes fade {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Responsive */
@media (max-width: 768px) {
  .admin-settings-container {
    padding: 1rem;
  }
  
  .cards-grid {
    grid-template-columns: 1fr;
  }
  
  .form-grid {
    grid-template-columns: 1fr;
  }
  
  .page-header {
    flex-direction: column;
    align-items: stretch;
    gap: 1.5rem;
  }
  
  .action-bar {
    padding: 1rem;
  }
  
  .action-buttons {
    width: 100%;
  }
  
  .action-buttons .btn {
    flex: 1;
    justify-content: center;
  }
  
  .change-indicator {
    display: none;
  }
}
</style>