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
        <div class="form-actions">
          <button type="button" @click="resetForm" class="btn-secondary">
            Cancel
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
      retryCount: 0
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
       
        //console.log('Leave request submitted successfully:', response.data)
       
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
      if (this.$refs.fileInput) {
        this.$refs.fileInput.value = ''
      }
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
  }
}
</script>

<style scoped>
* {
  box-sizing: border-box;
}

.apply-leave-view {
  padding: 2rem;
  max-width: 800px;
  margin: 0 auto;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  background: #f8f9fc;
  min-height: 100vh;
}

/* Header Styles */
.header {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2.5rem;
  border-radius: 20px;
  box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
  margin-bottom: 2rem;
  color: white;
  text-align: center;
  position: relative;
  overflow: hidden;
}

.header::before {
  content: '';
  position: absolute;
  top: -50%;
  right: -50%;
  width: 200%;
  height: 200%;
  background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
  animation: pulse 15s ease-in-out infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

.title {
  margin: 0 0 0.5rem 0;
  font-size: 2.5rem;
  font-weight: 700;
  letter-spacing: -0.5px;
}

.subtitle {
  margin: 0;
  font-size: 1.1rem;
  opacity: 0.9;
  font-weight: 300;
}

/* Content */
.content {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}

.leave-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  padding: 2rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group label {
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
  display: flex;
  align-items: center;
  gap: 0.25rem;
}

.form-group input,
.form-group select,
.form-group textarea {
  padding: 0.875rem 1rem;
  border: 2px solid #e5e7eb;
  border-radius: 10px;
  font-size: 0.95rem;
  transition: all 0.2s;
  background: #f9fafb;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
  outline: none;
  border-color: #667eea;
  background: white;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-group textarea {
  resize: vertical;
  min-height: 100px;
}

.file-info {
  margin-top: 0.5rem;
  font-size: 0.875rem;
  color: #6b7280;
  padding: 0.75rem;
  background: #f0f9ff;
  border-radius: 8px;
  border-left: 3px solid #06b6d4;
  font-weight: 500;
}

.form-error {
  background: #fef2f2;
  color: #dc2626;
  padding: 1rem;
  border-radius: 10px;
  border-left: 4px solid #ef4444;
  font-weight: 500;
  font-size: 0.875rem;
}

/* Success Message */
.success-message {
  text-align: center;
  padding: 3rem 2rem;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border-radius: 16px;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  align-items: center;
  box-shadow: 0 4px 20px rgba(16, 185, 129, 0.3);
}

.success-message p {
  font-size: 1.125rem;
  margin: 0;
  opacity: 0.95;
}

.success-message .btn-secondary {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  backdrop-filter: blur(10px);
  border: 1px solid rgba(255, 255, 255, 0.3);
}

.success-message .btn-secondary:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-2px);
}

/* Form Actions */
.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 1rem;
  margin-top: 1rem;
  padding-top: 1.5rem;
  border-top: 1px solid #f0f0f0;
}

.btn-primary, .btn-secondary {
  padding: 0.875rem 2rem;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.btn-primary {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-3px);
  box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
}

.btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}

.btn-secondary {
  background: #f3f4f6;
  color: #374151;
}

.btn-secondary:hover {
  background: #e5e7eb;
  transform: translateY(-2px);
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

/* Responsive Design */
@media (max-width: 768px) {
  .apply-leave-view {
    padding: 1rem;
  }

  .header {
    padding: 2rem 1.5rem;
  }

  .title {
    font-size: 2rem;
  }

  .leave-form {
    padding: 1.5rem;
  }

  .form-row {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .form-actions {
    flex-direction: column;
  }

  .btn-primary, .btn-secondary {
    width: 100%;
  }

  .success-message {
    padding: 2rem 1.5rem;
  }
}
</style>