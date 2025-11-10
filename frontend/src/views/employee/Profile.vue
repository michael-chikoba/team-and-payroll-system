<template>
  <div class="profile-view">
    <header class="header">
      <h1 class="title">{{ pageName }}</h1>
      <p class="subtitle">Manage your personal information and documents</p>
    </header>
    
    <div class="content">
      <!-- Profile Picture -->
      <div class="profile-pic-section">
        <div class="pic-container">
          <img 
            :src="profilePicUrl || '/default-avatar.png'" 
            alt="Profile Picture" 
            class="profile-pic"
            @error="handleImageError"
          />
          <label for="profile-pic-upload" class="pic-upload-btn">
            📷 Change Photo
            <input 
              id="profile-pic-upload" 
              type="file" 
              accept="image/*" 
              @change="handleProfilePicUpload"
              ref="profilePicInput"
            />
          </label>
        </div>
        <p class="pic-info">Upload a professional photo (Max 2MB)</p>
      </div>

      <!-- Profile Information Form -->
      <form @submit.prevent="updateProfile" class="profile-form">
        <div class="form-row">
          <div class="form-group">
            <label>First Name *</label>
            <input 
              v-model="form.firstName" 
              type="text" 
              required 
              placeholder="Enter first name"
            />
          </div>
          <div class="form-group">
            <label>Last Name *</label>
            <input 
              v-model="form.lastName" 
              type="text" 
              required 
              placeholder="Enter last name"
            />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Email *</label>
            <input 
              v-model="form.email" 
              type="email" 
              required 
              placeholder="employee@castleholding.co.zm"
            />
          </div>
          <div class="form-group">
            <label>Phone Number</label>
            <input 
              v-model="form.phone" 
              type="tel" 
              placeholder="+260 97 123 4567"
            />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Date of Birth</label>
            <input 
              v-model="form.dateOfBirth" 
              type="date" 
              :max="today"
            />
          </div>
          <div class="form-group">
            <label>National ID / Passport</label>
            <input 
              v-model="form.nationalId" 
              type="text" 
              placeholder="e.g., 234567/89/1"
            />
          </div>
        </div>

        <div class="form-group full-width">
          <label>Address</label>
          <textarea 
            v-model="form.address" 
            rows="3" 
            placeholder="Enter your full address in Lusaka, Zambia..."
          ></textarea>
        </div>

        <div class="form-group full-width">
          <label>Emergency Contact</label>
          <input 
            v-model="form.emergencyContact" 
            type="text" 
            placeholder="Name and phone number"
          />
        </div>

        <div v-if="formError" class="form-error">
          {{ formError }}
        </div>

        <div class="form-actions">
          <button type="button" @click="resetForm" class="btn-secondary">Cancel</button>
          <button type="submit" class="btn-primary" :disabled="updatingProfile">
            {{ updatingProfile ? 'Updating...' : 'Update Profile' }}
          </button>
        </div>
      </form>

      <!-- Documents Section -->
      <div class="documents-section">
        <h3 class="section-title">Official Documents</h3>
        <p class="section-subtitle">Upload and manage your employment documents</p>
        
        <div class="upload-area">
          <label for="document-upload" class="upload-btn">
            + Upload Document
            <input 
              id="document-upload" 
              type="file" 
              accept=".pdf,.doc,.docx,.jpg,.png" 
              multiple
              @change="handleDocumentUpload"
              ref="documentInput"
            />
          </label>
          <p class="upload-info">Supported: PDF, DOC, Images (Max 5MB each)</p>
        </div>

        <div v-if="documents.length === 0" class="empty-state">
          <p>No documents uploaded yet.</p>
          <button @click="$refs.documentInput.click()" class="btn-primary">Upload First Document</button>
        </div>

        <div v-else class="documents-grid">
          <div v-for="doc in documents" :key="doc.id" class="document-card">
            <div class="doc-icon">{{ getFileIcon(doc.fileName) }}</div>
            <h4 class="doc-name">{{ doc.fileName }}</h4>
            <p class="doc-type">{{ doc.type || 'General Document' }}</p>
            <p class="doc-date">{{ formatDate(doc.uploadDate) }}</p>
            <div class="doc-actions">
              <button @click="downloadDocument(doc.id)" class="action-btn download">Download</button>
              <button @click="deleteDocument(doc.id)" class="action-btn delete">Delete</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="loading">
        <div class="spinner"></div>
        <p>Loading profile...</p>
      </div>

      <!-- Error State -->
      <div v-if="error" class="error-message">
        {{ error }}
        <button @click="retryFetch" class="btn-primary">Retry</button>
      </div>
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
      pageName: 'My Profile',
      form: {
        firstName: '',
        lastName: '',
        email: '',
        phone: '',
        dateOfBirth: '',
        nationalId: '',
        address: '',
        emergencyContact: ''
      },
      profilePicUrl: '',
      documents: [],
      today: new Date().toISOString().split('T')[0],
      loading: false,
      updatingProfile: false,
      uploadingDoc: false,
      formError: null,
      error: null,
      retryCount: 0
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
        console.log('Fetching profile... (retry:', retry, ')')
        const [profileRes, docsRes] = await Promise.all([
          axios.get('/api/employee/profile'),
          axios.get('/api/employee/documents')
        ])
        console.log('Profile response:', profileRes.data)
        console.log('Documents response:', docsRes.data)
        
        const profile = profileRes.data.profile || profileRes.data
        this.form = {
          firstName: profile.first_name || '',
          lastName: profile.last_name || '',
          email: profile.email || '',
          phone: profile.phone || '',
          dateOfBirth: profile.date_of_birth?.split('T')[0] || '',
          nationalId: profile.national_id || '',
          address: profile.address || '',
          emergencyContact: profile.emergency_contact || ''
        }
        this.profilePicUrl = profile.profile_pic || ''
        
        this.documents = docsRes.data.data || docsRes.data || []
      } catch (err) {
        console.error('Profile fetch error:', err)
        this.handleApiError(err)
      } finally {
        this.loading = false
      }
    },
    async updateProfile() {
      this.updatingProfile = true
      this.formError = null
      try {
        const payload = { ...this.form }
        await axios.put('/api/employee/profile', payload)
        this.authStore.updateUser(this.form) // Update store if needed
        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Profile updated successfully!'
        })
      } catch (err) {
        this.handleApiError(err)
      } finally {
        this.updatingProfile = false
      }
    },
    async handleProfilePicUpload(event) {
      const file = event.target.files[0]
      if (!file) return
      if (file.size > 2 * 1024 * 1024) {
        this.formError = 'Profile picture must be less than 2MB.'
        return
      }
      const formData = new FormData()
      formData.append('profile_pic', file)
      try {
        const response = await axios.post('/api/employee/profile-pic', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        this.profilePicUrl = response.data.profile_pic_url || URL.createObjectURL(file)
        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Profile picture updated!'
        })
      } catch (err) {
        this.handleApiError(err)
      }
    },
    handleImageError() {
      this.profilePicUrl = '/default-avatar.png'
    },
    async handleDocumentUpload(event) {
      const files = Array.from(event.target.files)
      if (files.length === 0) return
      this.uploadingDoc = true
      try {
        const formData = new FormData()
        files.forEach(file => {
          if (file.size > 5 * 1024 * 1024) {
            this.formError = `File ${file.name} exceeds 5MB limit.`
            return
          }
          formData.append('documents', file)
          formData.append('type', file.name.includes('.pdf') ? 'contract' : 'general') // Auto-detect or let user choose
        })
        const response = await axios.post('/api/employee/documents', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        })
        this.documents = [...this.documents, ...(response.data.newDocuments || [])]
        this.$notify({
          type: 'success',
          title: 'Success',
          text: `${files.length} document(s) uploaded successfully!`
        })
      } catch (err) {
        this.handleApiError(err)
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
        link.setAttribute('download', `castle-document-${id}.pdf`)
        document.body.appendChild(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
      } catch (err) {
        this.handleApiError(err)
      }
    },
    async deleteDocument(id) {
      if (!confirm('Are you sure you want to delete this document?')) return
      try {
        await axios.delete(`/api/employee/documents/${id}`)
        this.documents = this.documents.filter(doc => doc.id !== id)
        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Document deleted successfully!'
        })
      } catch (err) {
        this.handleApiError(err)
      }
    },
    resetForm() {
      this.fetchProfile()
    },
    getFileIcon(fileName) {
      const ext = fileName.split('.').pop()?.toLowerCase()
      const icons = {
        pdf: '📄',
        doc: '📝',
        docx: '📝',
        jpg: '🖼️',
        jpeg: '🖼️',
        png: '🖼️'
      }
      return icons[ext] || '📎'
    },
    retryFetch() {
      this.retryCount++
      if (this.retryCount <= 3) {
        this.fetchProfile(true)
      } else {
        this.error = 'Max retries exceeded. Please refresh the page.'
      }
    },
    handleApiError(err) {
      let errorMsg = 'An unexpected error occurred.'
      if (err.code === 'ERR_NETWORK' || err.message.includes('Network Error')) {
        errorMsg = 'Network error. Check your connection.'
      } else if (err.response?.status === 401) {
        errorMsg = 'Session expired. Redirecting to login...'
        this.authStore.clearAuth()
        this.$router.push({ name: 'login' })
      } else if (err.response?.status === 403) {
        errorMsg = 'Access denied.'
      } else if (err.response?.status === 422) {
        errorMsg = err.response.data.message || 'Please check the form for errors.'
        this.formError = errorMsg
        return
      } else {
        errorMsg = err.response?.data?.message || errorMsg
      }
      this.formError = errorMsg
    },
    formatDate(date) {
      if (!date) return 'N/A'
      return new Date(date).toLocaleDateString('en-ZM', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      })
    }
  }
}
</script>

<style scoped>
.profile-view {
  padding: 2rem;
  max-width: 1000px;
  margin: 0 auto;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  min-height: 100vh;
}

.header {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  text-align: center;
  margin-bottom: 2rem;
}

.title {
  margin: 0 0 0.5rem;
  font-size: 2.5rem;
  font-weight: 300;
  color: #2c3e50;
}

.subtitle {
  margin: 0;
  color: #7f8c8d;
  font-size: 1.1rem;
}

.content {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  padding: 2rem;
  margin-bottom: 2rem;
}

.profile-pic-section {
  text-align: center;
  margin-bottom: 2rem;
}

.pic-container {
  position: relative;
  display: inline-block;
  margin-bottom: 1rem;
}

.profile-pic {
  width: 150px;
  height: 150px;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid #e2e8f0;
}

.pic-upload-btn {
  position: absolute;
  bottom: 10px;
  right: 10px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 500;
  transition: transform 0.2s ease;
}

.pic-upload-btn:hover {
  transform: scale(1.05);
}

.pic-upload-btn input {
  display: none;
}

.pic-info {
  color: #7f8c8d;
  font-size: 0.9rem;
}

.profile-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group.full-width {
  grid-column: 1 / -1;
}

.form-group label {
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #4a5568;
}

.form-group input,
.form-group textarea {
  padding: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 1rem;
}

.form-group input:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-error {
  background: #fee;
  color: #c33;
  padding: 0.75rem;
  border-radius: 6px;
  border-left: 4px solid #dc3545;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
}

.documents-section {
  border-top: 1px solid #e2e8f0;
  padding-top: 2rem;
}

.section-title {
  margin: 0 0 0.5rem;
  color: #2c3e50;
  font-size: 1.5rem;
}

.section-subtitle {
  margin: 0 0 1.5rem;
  color: #7f8c8d;
}

.upload-area {
  text-align: center;
  padding: 2rem;
  border: 2px dashed #e2e8f0;
  border-radius: 12px;
  margin-bottom: 2rem;
  transition: border-color 0.2s ease;
}

.upload-area:hover {
  border-color: #667eea;
}

.upload-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 1rem 2rem;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  font-size: 1.1rem;
  transition: transform 0.2s ease;
}

.upload-btn:hover {
  transform: translateY(-2px);
}

.upload-btn input {
  display: none;
}

.upload-info {
  color: #7f8c8d;
  font-size: 0.9rem;
  margin-top: 0.5rem;
}

.documents-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1rem;
}

.document-card {
  background: #f8f9fa;
  padding: 1.5rem;
  border-radius: 8px;
  text-align: center;
  border: 1px solid #e2e8f0;
  transition: box-shadow 0.2s ease;
}

.document-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.doc-icon {
  font-size: 3rem;
  margin-bottom: 0.5rem;
}

.doc-name {
  margin: 0 0 0.25rem;
  font-weight: 600;
  color: #2c3e50;
  font-size: 0.95rem;
  word-break: break-all;
}

.doc-type {
  margin: 0 0 0.5rem;
  color: #7f8c8d;
  font-size: 0.85rem;
}

.doc-date {
  margin: 0 0 1rem;
  color: #718096;
  font-size: 0.8rem;
}

.doc-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
}

.action-btn {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.8rem;
  cursor: pointer;
  transition: background 0.2s ease;
}

.action-btn.download {
  background: #28a745;
  color: white;
}

.action-btn.download:hover {
  background: #218838;
}

.action-btn.delete {
  background: #dc3545;
  color: white;
}

.action-btn.delete:hover {
  background: #c82333;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem 2rem;
  text-align: center;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #667eea;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
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
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.btn-primary, .btn-secondary {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: transform 0.2s ease;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: #e2e8f0;
  color: #4a5568;
}

@media (max-width: 768px) {
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .profile-pic {
    width: 120px;
    height: 120px;
  }
  
  .documents-grid {
    grid-template-columns: 1fr;
  }
  
  .doc-actions {
    flex-direction: column;
  }
  
  .content {
    padding: 1.5rem;
  }
}
</style>