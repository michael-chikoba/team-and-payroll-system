<template>
  <div class="profile-view">
    <div class="profile-container">
      
      <!-- LEFT COLUMN: Identity Card -->
      <aside class="profile-sidebar">
        <div class="identity-card">
          <div class="profile-header-bg"></div>
          
          <div class="avatar-wrapper">
            <div class="avatar-container">
              <img
                :src="profilePicUrl || '/default-avatar.png'"
                alt="Profile"
                class="profile-pic"
                @error="handleImageError"
              />
              <!-- Upload Overlay -->
              <label for="profile-pic-upload" class="avatar-overlay">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hero-icon">
                  <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                  <circle cx="12" cy="13" r="4"/>
                </svg>
                <span class="upload-text">Change</span>
                <input
                  id="profile-pic-upload"
                  type="file"
                  accept="image/*"
                  @change="handleProfilePicUpload"
                  ref="profilePicInput"
                />
              </label>
            </div>
          </div>

          <div class="identity-info">
            <h2 class="user-fullname">{{ form.first_name }} {{ form.last_name }}</h2>
            <p class="user-email">{{ form.email }}</p>
            <div class="user-role-badge" v-if="employeeInfo">
              {{ employeeInfo.position }}
            </div>
          </div>

          <div class="identity-stats" v-if="employeeInfo">
            <div class="stat-item">
              <span class="stat-label">ID</span>
              <span class="stat-value">{{ employeeInfo.employee_id }}</span>
            </div>
            <div class="stat-item">
              <span class="stat-label">Dept</span>
              <span class="stat-value">{{ employeeInfo.department }}</span>
            </div>
          </div>
        </div>
      </aside>

      <!-- RIGHT COLUMN: Tabbed Content -->
      <main class="profile-content">
        <!-- Tab Navigation -->
        <nav class="profile-tabs">
          <button 
            v-for="tab in tabs" 
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="['tab-btn', { active: activeTab === tab.id }]"
          >
            <!-- Personal Details Icon -->
            <svg v-if="tab.id === 'personal'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tab-icon">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
              <circle cx="12" cy="7" r="4"/>
            </svg>
            <!-- Job Details Icon -->
            <svg v-if="tab.id === 'job'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tab-icon">
              <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
              <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
            </svg>
            <!-- Security Icon -->
            <svg v-if="tab.id === 'security'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tab-icon">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
              <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <!-- Documents Icon -->
            <svg v-if="tab.id === 'documents'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tab-icon">
              <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
            </svg>
            {{ tab.name }}
          </button>
        </nav>

        <div class="tab-panel">
          
          <!-- TAB 1: Edit Profile Form -->
          <div v-if="activeTab === 'personal'" class="fade-in">
            <div class="panel-header">
              <h3>Personal Details</h3>
              <p>Update your personal contact information.</p>
            </div>
            
            <form @submit.prevent="updateProfile" class="modern-form">
              <div class="form-grid">
                <div class="form-group">
                  <label>First Name</label>
                  <input v-model="form.first_name" type="text" required />
                </div>
                <div class="form-group">
                  <label>Last Name</label>
                  <input v-model="form.last_name" type="text" required />
                </div>
                <div class="form-group">
                  <label>Email Address</label>
                  <input v-model="form.email" type="email" required />
                </div>
                <div class="form-group">
                  <label>Phone Number</label>
                  <input v-model="form.phone" type="tel" />
                </div>
                <div class="form-group">
                  <label>Date of Birth</label>
                  <input v-model="form.date_of_birth" type="date" :max="today" />
                </div>
                <div class="form-group">
                  <label>National ID / Passport</label>
                  <input v-model="form.national_id" type="text" />
                </div>
                <div class="form-group full-width">
                  <label>Residential Address</label>
                  <textarea v-model="form.address" rows="2"></textarea>
                </div>
                <div class="form-group full-width">
                  <label>Emergency Contact</label>
                  <input v-model="form.emergency_contact" type="text" placeholder="Name and Phone Number" />
                </div>
              </div>

              <!-- Form Alerts -->
              <div v-if="formError" class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="alert-icon">
                  <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                {{ formError }}
              </div>
              <div v-if="successMessage" class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="alert-icon">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                {{ successMessage }}
              </div>

              <div class="form-actions">
                <button type="button" @click="resetForm" class="btn-ghost">Reset Changes</button>
                <button type="submit" class="btn-primary" :disabled="updatingProfile">
                  <svg v-if="!updatingProfile" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>
                  </svg>
                  {{ updatingProfile ? 'Saving...' : 'Save Changes' }}
                </button>
              </div>
            </form>
          </div>

          <!-- TAB 2: Job Details (Read Only) -->
          <div v-if="activeTab === 'job'" class="fade-in">
            <div class="panel-header">
              <h3>Employment Information</h3>
              <p>Your official employment records (Read-only).</p>
            </div>

            <div v-if="employeeInfo" class="info-grid">
              <div class="info-card">
                <span class="info-label">Employee ID</span>
                <span class="info-value">{{ employeeInfo.employee_id }}</span>
              </div>
              <div class="info-card">
                <span class="info-label">Position</span>
                <span class="info-value">{{ employeeInfo.position }}</span>
              </div>
              <div class="info-card">
                <span class="info-label">Department</span>
                <span class="info-value">{{ employeeInfo.department }}</span>
              </div>
              <div class="info-card">
                <span class="info-label">Employment Type</span>
                <span class="info-value">{{ formatEmploymentType(employeeInfo.employment_type) }}</span>
              </div>
              <div class="info-card">
                <span class="info-label">Hire Date</span>
                <span class="info-value">{{ formatDate(employeeInfo.hire_date) }}</span>
              </div>
              <div class="info-card">
                <span class="info-label">Direct Manager</span>
                <span class="info-value">{{ getManagerName(employeeInfo.manager) }}</span>
              </div>
            </div>
          </div>

          <!-- TAB 3: Security -->
          <div v-if="activeTab === 'security'" class="fade-in">
            <div class="panel-header">
              <h3>Security Settings</h3>
              <p>Manage your password and account security.</p>
            </div>

            <form @submit.prevent="updatePassword" class="modern-form password-form-wrapper">
              <div class="form-group">
                <label>Current Password</label>
                <input v-model="passwordForm.current_password" type="password" required />
              </div>
              
              <div class="password-split">
                <div class="form-group">
                  <label>New Password</label>
                  <input v-model="passwordForm.password" type="password" required minlength="8" />
                </div>
                <div class="form-group">
                  <label>Confirm Password</label>
                  <input 
                    v-model="passwordForm.password_confirmation" 
                    type="password" 
                    required 
                    :class="{ 'error-border': passwordForm.password && passwordForm.password_confirmation && passwordForm.password !== passwordForm.password_confirmation }"
                  />
                </div>
              </div>

              <div v-if="passwordError" class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="alert-icon">
                  <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                {{ passwordError }}
              </div>
              <div v-if="passwordSuccess" class="alert alert-success">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="alert-icon">
                  <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                </svg>
                {{ passwordSuccess }}
              </div>

              <div class="form-actions">
                <button type="submit" class="btn-primary" :disabled="updatingPassword || (passwordForm.password !== passwordForm.password_confirmation)">
                  <svg v-if="!updatingPassword" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="btn-icon">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                  </svg>
                  {{ updatingPassword ? 'Updating...' : 'Update Password' }}
                </button>
              </div>
            </form>
          </div>

          <!-- TAB 4: Documents -->
          <div v-if="activeTab === 'documents'" class="fade-in">
            <div class="panel-header">
              <h3>My Documents</h3>
              <p>Manage your official files and contracts.</p>
            </div>

            <div class="upload-zone">
              <label for="document-upload" class="upload-trigger">
                <div class="upload-content">
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="upload-hero-icon">
                    <polyline points="16 16 12 12 8 16"/><line x1="12" y1="12" x2="12" y2="21"/>
                    <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/>
                  </svg>
                  <span class="upload-text">Click to upload files</span>
                  <span class="upload-sub">PDF, DOC, JPG (Max 5MB)</span>
                </div>
                <input
                  id="document-upload"
                  type="file"
                  accept=".pdf,.doc,.docx,.jpg,.png"
                  multiple
                  @change="handleDocumentUpload"
                  ref="documentInput"
                />
              </label>
            </div>

            <div v-if="documents.length > 0" class="docs-list">
              <div v-for="doc in documents" :key="doc.id" class="doc-item">
                <div class="doc-file-icon">
                  <!-- PDF icon -->
                  <svg v-if="getFileType(doc.fileName) === 'pdf'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="file-type-icon pdf">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
                  </svg>
                  <!-- Word doc icon -->
                  <svg v-else-if="getFileType(doc.fileName) === 'doc'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="file-type-icon doc">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                  </svg>
                  <!-- Image icon -->
                  <svg v-else-if="getFileType(doc.fileName) === 'img'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="file-type-icon img">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
                    <polyline points="21 15 16 10 5 21"/>
                  </svg>
                  <!-- Generic file icon -->
                  <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="file-type-icon generic">
                    <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="13 2 13 9 20 9"/>
                  </svg>
                </div>
                <div class="doc-details">
                  <span class="doc-name">{{ doc.fileName }}</span>
                  <span class="doc-meta">{{ formatDate(doc.uploadDate) }} • {{ doc.type || 'General' }}</span>
                </div>
                <div class="doc-tools">
                  <button @click="downloadDocument(doc.id)" class="tool-btn download" title="Download">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tool-icon">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                  </button>
                  <button @click="deleteDocument(doc.id)" class="tool-btn delete" title="Delete">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="tool-icon">
                      <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                      <path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
            
            <div v-else class="empty-docs">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="empty-icon">
                <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
              </svg>
              <p>No documents uploaded yet.</p>
            </div>
          </div>

        </div>
      </main>
    </div>

    <!-- Global Loading Overlay -->
    <div v-if="loading" class="loading-overlay">
      <div class="spinner"></div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

export default {
  name: 'Profile',
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  data() {
    return {
      activeTab: 'personal',
      tabs: [
        { id: 'personal', name: 'Personal Details' },
        { id: 'job', name: 'Job Details' },
        { id: 'security', name: 'Security' },
        { id: 'documents', name: 'Documents' }
      ],
      form: {
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        date_of_birth: '',
        national_id: '',
        address: '',
        emergency_contact: ''
      },
      passwordForm: {
        current_password: '',
        password: '',
        password_confirmation: ''
      },
      employeeInfo: null,
      profilePicUrl: '',
      documents: [],
      today: new Date().toISOString().split('T')[0],
      loading: false,
      updatingProfile: false,
      updatingPassword: false,
      uploadingDoc: false,
      formError: null,
      passwordError: null,
      successMessage: null,
      passwordSuccess: null,
      error: null,
      originalFormData: null
    }
  },
  mounted() {
    this.fetchProfile()
  },
  methods: {
    async fetchProfile(retry = false) {
      this.loading = true
      this.error = null
      try {
        const [profileRes, docsRes] = await Promise.all([
          axios.get('/api/profile'),
          axios.get('/api/employee/documents').catch(() => ({ data: { data: [] } }))
        ])
        
        const user = profileRes.data.user || profileRes.data
        const employee = profileRes.data.employee?.data || profileRes.data.employee
        
        this.form = {
          first_name: user.first_name || '',
          last_name: user.last_name || '',
          email: user.email || '',
          phone: employee?.phone || '',
          date_of_birth: employee?.date_of_birth ? new Date(employee.date_of_birth).toISOString().split('T')[0] : '',
          national_id: employee?.national_id || '',
          address: employee?.address || '',
          emergency_contact: employee?.emergency_contact || ''
        }
        
        this.originalFormData = { ...this.form }
        
        if (employee) {
          this.employeeInfo = {
            employee_id: employee.employee_id,
            position: employee.position,
            department: employee.department,
            employment_type: employee.employment_type,
            hire_date: employee.hire_date,
            manager: employee.manager
          }
        }
        
        this.profilePicUrl = employee?.profile_pic ? `/storage/${employee.profile_pic}` : ''
        this.documents = docsRes.data.data || []
      } catch (err) {
        this.handleApiError(err, 'Failed to load profile')
      } finally {
        this.loading = false
      }
    },

    async updateProfile() {
      this.updatingProfile = true
      this.formError = null
      this.successMessage = null
     
      try {
        const payload = {}
        Object.keys(this.form).forEach(key => {
          if (this.form[key] !== this.originalFormData[key]) {
            payload[key] = this.form[key]
          }
        })
        
        if (Object.keys(payload).length === 0) {
          this.formError = 'No changes detected'
          this.updatingProfile = false
          return
        }
        
        await axios.put('/api/profile', payload)
        
        if (this.authStore.user) {
          this.authStore.user.first_name = this.form.first_name
          this.authStore.user.last_name = this.form.last_name
          this.authStore.user.email = this.form.email
        }
        
        this.originalFormData = { ...this.form }
        this.successMessage = 'Profile updated successfully!'
        setTimeout(() => this.successMessage = null, 3000)
        
      } catch (err) {
        this.handleApiError(err, 'Failed to update profile')
      } finally {
        this.updatingProfile = false
      }
    },

    async updatePassword() {
      if (this.passwordForm.password.length < 8) {
        this.passwordError = 'New password must be at least 8 characters long'
        return
      }
      if (this.passwordForm.password !== this.passwordForm.password_confirmation) {
        this.passwordError = 'Passwords do not match'
        return
      }

      this.updatingPassword = true
      this.passwordError = null
      
      try {
        await axios.post('/api/profile/password', this.passwordForm)
        this.resetPasswordForm()
        this.passwordSuccess = 'Password updated successfully!'
        setTimeout(() => this.passwordSuccess = null, 3000)
      } catch (err) {
        this.handleApiError(err, 'Failed to update password', 'password')
      } finally {
        this.updatingPassword = false
      }
    },

    resetPasswordForm() {
      this.passwordForm = { current_password: '', password: '', password_confirmation: '' }
    },
   
    async handleProfilePicUpload(event) {
      const file = event.target.files[0]
      if (!file) return
      
      const formData = new FormData()
      formData.append('profile_pic', file)
      
      try {
        const response = await axios.post('/api/employee/profile-pic', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        this.profilePicUrl = response.data.profile_pic_url || `/storage/${response.data.profile_pic}`
      } catch (err) {
        this.handleApiError(err, 'Failed to upload profile picture')
      }
    },
   
    handleImageError() {
      this.profilePicUrl = ''
    },
   
    async handleDocumentUpload(event) {
      const files = Array.from(event.target.files)
      if (files.length === 0) return
      this.uploadingDoc = true
      
      try {
        const formData = new FormData()
        for (const file of files) formData.append('documents', file)
        
        const response = await axios.post('/api/employee/documents', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        this.documents = [...this.documents, ...(response.data.newDocuments || [])]
      } catch (err) {
        this.handleApiError(err, 'Failed to upload documents')
      } finally {
        this.uploadingDoc = false
        event.target.value = ''
      }
    },
   
    async downloadDocument(id) {
      try {
        const response = await axios.get(`/api/employee/documents/${id}/download`, { responseType: 'blob' })
        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', `document-${id}.pdf`)
        document.body.appendChild(link)
        link.click()
        link.remove()
      } catch (err) {
        console.error(err)
      }
    },
   
    async deleteDocument(id) {
      if (!confirm('Delete this document?')) return
      try {
        await axios.delete(`/api/employee/documents/${id}`)
        this.documents = this.documents.filter(doc => doc.id !== id)
      } catch (err) {
        console.error(err)
      }
    },
   
    resetForm() {
      if (this.originalFormData) this.form = { ...this.originalFormData }
      this.formError = null
    },

    getFileType(fileName) {
      const ext = fileName?.split('.').pop()?.toLowerCase()
      if (ext === 'pdf') return 'pdf'
      if (['doc', 'docx'].includes(ext)) return 'doc'
      if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) return 'img'
      return 'generic'
    },
    
    handleApiError(err, defaultMsg, target = 'form') {
      let msg = defaultMsg
      if (err.response?.data?.message) msg = err.response.data.message
      
      if (target === 'password') this.passwordError = msg
      else this.formError = msg
    },
   
    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-ZM', { year: 'numeric', month: 'short', day: 'numeric' })
    },
   
    formatEmploymentType(type) {
      if (!type) return 'N/A'
      return type.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
    },
   
    getManagerName(manager) {
      if (!manager) return 'N/A'
      return `${manager.first_name} ${manager.last_name}`
    }
  }
}
</script>

<style scoped>
/* --- Layout & Variables --- */
.profile-view {
  min-height: 100vh;
  background-color: #f3f4f6;
  padding: 2rem;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
  color: #1f2937;
}

.profile-container {
  max-width: 1200px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: 320px 1fr;
  gap: 2rem;
  align-items: start;
}

/* --- Left Sidebar: Identity Card --- */
.identity-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
  overflow: hidden;
  position: sticky;
  top: 2rem;
}

.profile-header-bg {
  height: 100px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.avatar-wrapper {
  margin-top: -50px;
  display: flex;
  justify-content: center;
}

.avatar-container {
  position: relative;
  width: 100px;
  height: 100px;
  border-radius: 50%;
  border: 4px solid white;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  background: #e5e7eb;
}

.profile-pic {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}

/* Profile Hover Overlay */
.avatar-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6);
  border-radius: 50%;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: white;
  opacity: 0;
  transition: opacity 0.2s ease;
  cursor: pointer;
  font-size: 0.8rem;
  gap: 4px;
}

.avatar-container:hover .avatar-overlay {
  opacity: 1;
}

.avatar-overlay input {
  display: none;
}

.avatar-overlay .upload-text {
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.5px;
  color: white;
}

.hero-icon {
  width: 20px;
  height: 20px;
}

.identity-info {
  text-align: center;
  padding: 1rem 1.5rem;
}

.user-fullname {
  font-size: 1.25rem;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.user-email {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0.25rem 0 0.75rem;
}

.user-role-badge {
  display: inline-block;
  background: #eef2ff;
  color: #4f46e5;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  text-transform: uppercase;
}

.identity-stats {
  border-top: 1px solid #f3f4f6;
  display: flex;
  padding: 1rem 0;
}

.stat-item {
  flex: 1;
  text-align: center;
  border-right: 1px solid #f3f4f6;
}

.stat-item:last-child {
  border-right: none;
}

.stat-label {
  display: block;
  font-size: 0.7rem;
  color: #9ca3af;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-value {
  display: block;
  font-weight: 600;
  color: #374151;
  font-size: 0.9rem;
}

/* --- Right Content: Tabs & Panels --- */
.profile-content {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  min-height: 500px;
  display: flex;
  flex-direction: column;
}

.profile-tabs {
  display: flex;
  border-bottom: 1px solid #e5e7eb;
  padding: 0 1rem;
}

.tab-btn {
  background: none;
  border: none;
  padding: 1rem 1.5rem;
  font-size: 0.95rem;
  color: #6b7280;
  font-weight: 500;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.tab-btn:hover {
  color: #4f46e5;
}

.tab-btn.active {
  color: #4f46e5;
  border-bottom-color: #4f46e5;
}

.tab-icon {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
}

.tab-panel {
  padding: 2rem;
  flex: 1;
}

.panel-header {
  margin-bottom: 2rem;
}

.panel-header h3 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #111827;
  margin: 0 0 0.5rem;
}

.panel-header p {
  color: #6b7280;
  margin: 0;
}

/* --- Forms --- */
.modern-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

label {
  font-size: 0.875rem;
  font-weight: 500;
  color: #374151;
}

input, textarea {
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 0.95rem;
  transition: all 0.2s;
}

input:focus, textarea:focus {
  outline: none;
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.error-border {
  border-color: #ef4444 !important;
}

/* --- Info Cards (Job Details) --- */
.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1.5rem;
}

.info-card {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 8px;
  border: 1px solid #f3f4f6;
}

.info-label {
  display: block;
  font-size: 0.75rem;
  color: #6b7280;
  margin-bottom: 0.25rem;
}

.info-value {
  font-weight: 600;
  color: #1f2937;
  font-size: 1rem;
}

/* --- Actions & Buttons --- */
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1rem;
  padding-top: 1.5rem;
  border-top: 1px solid #f3f4f6;
}

.btn-primary {
  background: #4f46e5;
  color: white;
  border: none;
  padding: 0.6rem 1.5rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.btn-primary:hover:not(:disabled) {
  background: #4338ca;
}

.btn-primary:disabled {
  background: #a5b4fc;
  cursor: not-allowed;
}

.btn-icon {
  width: 16px;
  height: 16px;
  flex-shrink: 0;
}

.btn-ghost {
  background: transparent;
  color: #6b7280;
  border: none;
  padding: 0.6rem 1rem;
  cursor: pointer;
}

.btn-ghost:hover {
  color: #1f2937;
  background: #f3f4f6;
  border-radius: 8px;
}

/* --- Alerts --- */
.alert {
  padding: 1rem;
  border-radius: 8px;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.alert-icon {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
}

.alert-error {
  background: #fef2f2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.alert-success {
  background: #f0fdf4;
  color: #166534;
  border: 1px solid #bbf7d0;
}

/* --- Documents Section --- */
.upload-zone {
  margin-bottom: 2rem;
}

.upload-trigger {
  display: block;
  border: 2px dashed #d1d5db;
  border-radius: 12px;
  padding: 2rem;
  text-align: center;
  cursor: pointer;
  transition: all 0.2s;
}

.upload-trigger:hover {
  border-color: #4f46e5;
  background: #f5f3ff;
}

.upload-trigger input {
  display: none;
}

.upload-hero-icon {
  width: 2.5rem;
  height: 2.5rem;
  color: #9ca3af;
  display: block;
  margin: 0 auto 0.5rem;
  transition: color 0.2s;
}

.upload-trigger:hover .upload-hero-icon {
  color: #4f46e5;
}

.upload-text {
  display: block;
  font-weight: 600;
  color: #4f46e5;
}

.upload-sub {
  font-size: 0.8rem;
  color: #9ca3af;
  display: block;
  margin-top: 0.25rem;
}

.docs-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.doc-item {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  transition: box-shadow 0.2s;
}

.doc-item:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
  border-color: #d1d5db;
}

.doc-file-icon {
  margin-right: 1rem;
  display: flex;
  align-items: center;
}

.file-type-icon {
  width: 28px;
  height: 28px;
}

.file-type-icon.pdf {
  color: #dc2626;
}

.file-type-icon.doc {
  color: #2563eb;
}

.file-type-icon.img {
  color: #7c3aed;
}

.file-type-icon.generic {
  color: #6b7280;
}

.doc-details {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.doc-name {
  font-weight: 500;
  font-size: 0.95rem;
  color: #374151;
}

.doc-meta {
  font-size: 0.75rem;
  color: #9ca3af;
}

.doc-tools {
  display: flex;
  gap: 0.5rem;
}

.tool-btn {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.tool-icon {
  width: 16px;
  height: 16px;
}

.download {
  background: #f0fdf4;
  color: #166534;
}
.download:hover { background: #dcfce7; }

.delete {
  background: #fef2f2;
  color: #991b1b;
}
.delete:hover { background: #fee2e2; }

/* --- Empty State --- */
.empty-docs {
  text-align: center;
  padding: 3rem 0;
  color: #9ca3af;
}

.empty-icon {
  width: 3rem;
  height: 3rem;
  margin: 0 auto 0.75rem;
  opacity: 0.5;
}

/* --- Password Form --- */
.password-split {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

/* --- Mobile Responsiveness --- */
@media (max-width: 768px) {
  .profile-container {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }
  
  .identity-card {
    position: static;
  }
  
  .form-grid, .info-grid, .password-split {
    grid-template-columns: 1fr;
  }
  
  .profile-tabs {
    overflow-x: auto;
    white-space: nowrap;
  }
}

/* --- Utilities --- */
.fade-in {
  animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
}

.loading-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(255,255,255,0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-top-color: #4f46e5;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>