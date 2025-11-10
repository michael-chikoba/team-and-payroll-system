<template>
  <div class="apply-leave-view">
    <header class="header">
      <h1 class="title">{{ pageName }}</h1>
      <p class="subtitle">Submit your leave request for approval</p>
    </header>
    
    <div class="content">
      <div v-if="successMessage" class="success-message">
        {{ successMessage }}
        <button @click="resetForm" class="btn-secondary">Apply Another</button>
      </div>
      
      <form v-else @submit.prevent="submitLeave" class="leave-form">
        <div class="form-row">
          <div class="form-group">
            <label>Leave Type *</label>
            <select v-model="form.leaveType" required @change="updateLeaveType">
              <option value="">Select Leave Type</option>
              <option value="annual">Annual Leave</option>
              <option value="sick">Sick Leave</option>
              <option value="maternity">Maternity Leave</option>
              <option value="paternity">Paternity Leave</option>
              <option value="bereavement">Bereavement Leave</option>
              <option value="unpaid">Unpaid Leave</option>
            </select>
            <small class="help-text">Selected value: {{ form.leaveType || 'None' }}</small>
          </div>
          <div class="form-group">
            <label>Duration (Days) *</label>
            <input 
              v-model.number="form.duration" 
              type="number" 
              min="1" 
              max="30"
              required 
              placeholder="e.g., 5"
            />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Start Date *</label>
            <input 
              v-model="form.startDate" 
              type="date" 
              required 
              :min="today"
            />
          </div>
          <div class="form-group">
            <label>End Date *</label>
            <input 
              v-model="form.endDate" 
              type="date" 
              required 
              :min="form.startDate || today"
            />
          </div>
        </div>

        <div class="form-group">
          <label>Reason/Description *</label>
          <textarea 
            v-model="form.reason" 
            rows="4" 
            required 
            placeholder="Provide a detailed reason for your leave request..."
          ></textarea>
        </div>

        <div class="form-group">
          <label>Attach Supporting Document (Optional)</label>
          <input 
            ref="fileInput"
            type="file" 
            accept=".pdf,.doc,.docx,.jpg,.png"
            @change="handleFileUpload"
          />
          <p v-if="form.attachment" class="file-info">Selected: {{ form.attachment.name }}</p>
        </div>

        <div v-if="formError" class="form-error">
          {{ formError }}
        </div>

        <div class="debug-info" v-if="debugMode">
          <h4>Debug Information:</h4>
          <pre>{{ debugData }}</pre>
        </div>

        <div class="form-actions">
          <button type="button" @click="resetForm" class="btn-secondary">
            Cancel
          </button>
          <button type="button" @click="toggleDebug" class="btn-debug">
            {{ debugMode ? 'Hide Debug' : 'Show Debug' }}
          </button>
          <button type="submit" class="btn-primary" :disabled="submitting">
            {{ submitting ? 'Submitting...' : 'Submit Leave Request' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth'
import axios from 'axios'

export default {
  name: 'ApplyLeave',
  setup() {
    const authStore = useAuthStore()
    return { authStore }
  },
  data() {
    return {
      pageName: 'Apply for Leave',
      form: {
        leaveType: '',
        duration: 1,
        startDate: '',
        endDate: '',
        reason: '',
        attachment: null
      },
      today: new Date().toISOString().split('T')[0],
      submitting: false,
      successMessage: null,
      formError: null,
      retryCount: 0,
      debugMode: false
    }
  },
  computed: {
    debugData() {
      return {
        formData: {
          type: this.form.leaveType,
          duration: this.form.duration,
          start_date: this.form.startDate,
          end_date: this.form.endDate,
          reason: this.form.reason,
          attachment: this.form.attachment ? this.form.attachment.name : null
        },
        validation: {
          validLeaveTypes: ['Annual', 'sick', 'maternity', 'paternity', 'bereavement', 'unpaid'],
          currentType: this.form.leaveType,
          isValidType: ['annual', 'sick', 'maternity', 'paternity', 'bereavement', 'unpaid'].includes(this.form.leaveType)
        }
      }
    }
  },
  watch: {
    'form.startDate'() {
      if (this.form.startDate && this.form.endDate < this.form.startDate) {
        this.form.endDate = ''
      }
    },
    'form.endDate'() {
      if (this.form.startDate && this.form.endDate) {
        this.calculateDuration()
      }
    }
  },
  methods: {
    updateLeaveType() {
      console.log('Leave type selected:', this.form.leaveType)
      this.formError = null
    },
    calculateDuration() {
      if (this.form.startDate && this.form.endDate) {
        const start = new Date(this.form.startDate)
        const end = new Date(this.form.endDate)
        const diffTime = end - start
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1
        this.form.duration = diffDays
      }
    },
    validateForm() {
      // Reset previous errors
      this.formError = null

      // Check leave type
      const validLeaveTypes = ['annual', 'sick', 'maternity', 'paternity', 'bereavement', 'unpaid']
      if (!this.form.leaveType) {
        this.formError = 'Please select a leave type.'
        return false
      }
      if (!validLeaveTypes.includes(this.form.leaveType)) {
        this.formError = `Invalid leave type selected. Please choose from: ${validLeaveTypes.join(', ')}`
        return false
      }

      // Check dates
      if (!this.form.startDate || !this.form.endDate) {
        this.formError = 'Please select both start and end dates.'
        return false
      }

      const start = new Date(this.form.startDate)
      const end = new Date(this.form.endDate)
      if (end < start) {
        this.formError = 'End date must be after start date.'
        return false
      }

      // Check reason
      if (!this.form.reason.trim()) {
        this.formError = 'Please provide a reason for your leave request.'
        return false
      }

      return true
    },
    async submitLeave() {
      if (!this.validateForm()) {
        return
      }

      this.submitting = true
      this.formError = null
      
      try {
        const payload = new FormData()
        
        // Use the exact field names expected by the backend
        payload.append('type', this.form.leaveType) // Must be one of: annual, sick, maternity, paternity, bereavement, unpaid
        payload.append('duration', this.form.duration)
        payload.append('start_date', this.form.startDate)
        payload.append('end_date', this.form.endDate)
        payload.append('reason', this.form.reason)
        
        if (this.form.attachment) {
          payload.append('attachment', this.form.attachment)
        }

        // Log the payload for debugging
        console.log('Submitting leave request with payload:', {
          type: this.form.leaveType,
          duration: this.form.duration,
          start_date: this.form.startDate,
          end_date: this.form.endDate,
          reason: this.form.reason
        })

        // Log FormData contents
        for (let [key, value] of payload.entries()) {
          console.log(`FormData: ${key} =`, value)
        }

        const response = await axios.post('/api/employee/leaves', payload, {
          headers: { 
            'Content-Type': 'multipart/form-data',
            'Accept': 'application/json'
          },
          timeout: 10000
        })
        
        console.log('Leave request submitted successfully:', response.data)
        
        this.successMessage = 'Leave request submitted successfully! It will be reviewed by your manager.'
        
        if (this.$notify) {
          this.$notify({
            type: 'success',
            title: 'Success',
            text: 'Your leave request has been submitted.'
          })
        }
        
        // Clear form after short delay to show success message
        setTimeout(() => {
          this.resetForm()
        }, 3000)
        
      } catch (err) {
        console.error('Leave submission error:', err)
        this.handleApiError(err)
      } finally {
        this.submitting = false
      }
    },
    handleFileUpload(event) {
      const file = event.target.files[0]
      if (file) {
        if (file.size > 5 * 1024 * 1024) { // 5MB limit
          this.formError = 'File size must be less than 5MB.'
          event.target.value = ''
          return
        }
        
        const allowedTypes = [
          'application/pdf', 
          'application/msword', 
          'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 
          'image/jpeg', 
          'image/png'
        ]
        if (!allowedTypes.includes(file.type)) {
          this.formError = 'File type not allowed. Please upload PDF, DOC, DOCX, JPG, or PNG files only.'
          event.target.value = ''
          return
        }
      }
      this.form.attachment = file
      this.formError = null
    },
    resetForm() {
      this.form = {
        leaveType: '',
        duration: 1,
        startDate: '',
        endDate: '',
        reason: '',
        attachment: null
      }
      this.formError = null
      this.successMessage = null
      this.debugMode = false
      if (this.$refs.fileInput) {
        this.$refs.fileInput.value = ''
      }
    },
    toggleDebug() {
      this.debugMode = !this.debugMode
    },
    handleApiError(err) {
      let errorMsg = 'An unexpected error occurred.'
      
      if (err.code === 'ERR_NETWORK') {
        errorMsg = 'Network error. Please check your connection.'
      } else if (err.response?.status === 401) {
        errorMsg = 'Session expired. Redirecting to login...'
        this.authStore.clearAuth()
        setTimeout(() => {
          this.$router.push({ name: 'login' })
        }, 2000)
      } else if (err.response?.status === 422) {
        // Handle validation errors
        const responseData = err.response.data
        console.log('Validation errors:', responseData)
        
        if (responseData.errors) {
          const errors = Object.values(responseData.errors).flat()
          errorMsg = errors.join('. ')
        } else if (responseData.message) {
          errorMsg = responseData.message
        } else {
          errorMsg = 'Please check the form for errors.'
        }
        
        // Specific handling for type validation
        if (errorMsg.includes('type') && errorMsg.includes('invalid')) {
          errorMsg = `Invalid leave type. Please select one of: Annual, Sick, Maternity, Paternity, Bereavement, or Unpaid Leave. (You selected: ${this.form.leaveType})`
        }
      } else if (err.response?.status === 403) {
        errorMsg = 'You do not have permission to apply for leave.'
      } else if (err.response?.status === 500) {
        errorMsg = 'Server error. Please try again later or contact support.'
      } else {
        errorMsg = err.response?.data?.message || errorMsg
      }
      
      this.formError = errorMsg
      
      // Log detailed error for debugging
      console.error('API Error Details:', {
        status: err.response?.status,
        data: err.response?.data,
        message: err.message
      })
    }
  },
  mounted() {
    // Set minimum date to today
    this.today = new Date().toISOString().split('T')[0]
    console.log('Available leave types:', ['Annual', 'sick', 'maternity', 'paternity', 'bereavement', 'unpaid'])
  }
}
</script>

<style scoped>
.apply-leave-view {
  padding: 2rem;
  max-width: 800px;
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
}

.leave-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
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

.form-group label {
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #4a5568;
}

.help-text {
  margin-top: 0.25rem;
  font-size: 0.8rem;
  color: #718096;
  font-style: italic;
}

.form-group input,
.form-group select,
.form-group textarea {
  padding: 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.file-info {
  margin-top: 0.5rem;
  font-size: 0.9rem;
  color: #718096;
  padding: 0.5rem;
  background: #f7fafc;
  border-radius: 4px;
  border-left: 3px solid #667eea;
}

.form-error {
  background: #fed7d7;
  color: #c53030;
  padding: 1rem;
  border-radius: 6px;
  border-left: 4px solid #e53e3e;
  font-weight: 500;
}

.debug-info {
  background: #f7fafc;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 1rem;
  margin-top: 1rem;
}

.debug-info h4 {
  margin: 0 0 0.5rem 0;
  color: #4a5568;
}

.debug-info pre {
  background: #edf2f7;
  padding: 0.75rem;
  border-radius: 4px;
  overflow-x: auto;
  font-size: 0.8rem;
  color: #2d3748;
}

.success-message {
  text-align: center;
  padding: 2rem;
  background: #c6f6d5;
  color: #22543d;
  border-radius: 8px;
  border-left: 4px solid #38a169;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  align-items: center;
}

.success-message .btn-secondary {
  background: #38a169;
  color: white;
}

.success-message .btn-secondary:hover {
  background: #2f855a;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

.btn-primary, .btn-secondary, .btn-debug {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 1rem;
}

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-secondary {
  background: #e2e8f0;
  color: #4a5568;
}

.btn-secondary:hover {
  background: #cbd5e0;
  transform: translateY(-1px);
}

.btn-debug {
  background: #f6e05e;
  color: #744210;
}

.btn-debug:hover {
  background: #ecc94b;
  transform: translateY(-1px);
}

@media (max-width: 768px) {
  .apply-leave-view {
    padding: 1rem;
  }
  
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .form-actions {
    flex-direction: column;
  }
  
  .header {
    padding: 1.5rem;
  }
  
  .content {
    padding: 1.5rem;
  }
  
  .title {
    font-size: 2rem;
  }
  
  .btn-primary, .btn-secondary, .btn-debug {
    width: 100%;
    text-align: center;
  }
}
</style>