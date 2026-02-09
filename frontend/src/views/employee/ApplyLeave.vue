<template>
  <div class="apply-leave-view">
    <header class="header sticky-header">
      <div class="header-content">
        <h1 class="title">{{ pageName }}</h1>
        <p class="subtitle">Submit your leave request for approval</p>
      </div>
    </header>
   
    <div class="content">
      <div v-if="successMessage" class="alert alert-success">
        {{ successMessage }}
        <button @click="resetForm" class="btn-secondary">Apply Another</button>
      </div>
     
      <form v-else @submit.prevent="submitLeave" class="leave-form">
        <div class="form-row d-flex gap-3">
          <div class="form-group">
            <label class="form-label">Leave Type *</label>
            <select v-model="form.leaveType" class="form-select" required @change="updateLeaveType">
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
            <label class="form-label">Duration (Days) *</label>
            <input
              v-model.number="form.duration"
              type="number"
              min="1"
              max="30"
              required
              placeholder="e.g., 5"
              class="form-control"
            />
          </div>
        </div>
        <div class="form-row d-flex gap-3">
          <div class="form-group">
            <label class="form-label">Start Date *</label>
            <input
              v-model="form.startDate"
              type="date"
              required
              :min="today"
              class="form-control"
            />
          </div>
          <div class="form-group">
            <label class="form-label">End Date *</label>
            <input
              v-model="form.endDate"
              type="date"
              required
              :min="form.startDate || today"
              class="form-control"
            />
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Reason/Description *</label>
          <textarea
            v-model="form.reason"
            rows="4"
            required
            placeholder="Provide a detailed reason for your leave request..."
            class="form-textarea"
          ></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Attach Supporting Document (Optional)</label>
          <input
            ref="fileInput"
            type="file"
            accept=".pdf,.doc,.docx,.jpg,.png"
            @change="handleFileUpload"
            class="form-control"
          />
          <p v-if="form.attachment" class="file-info">Selected: {{ form.attachment.name }}</p>
        </div>
        <div v-if="formError" class="alert alert-danger">
          {{ formError }}
        </div>
        <div class="form-actions d-flex justify-content-end gap-2">
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
// Script remains unchanged
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
        if (!allowedTypes.includes(file.type) ) {
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
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        setTimeout(() => {
          this.$router.push('/auth/login')
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
/* Use shared styles - minimal overrides */
.apply-leave-view {
  padding: var(--spacing-xl);
  max-width: 800px;
  margin: 0 auto;
  background: var(--background-color);
  min-height: 100vh;
}

/* Sticky header that scrolls with the page */
.header {
  position: sticky;
  top: 0;
  z-index: 100;
  padding: var(--spacing-lg) var(--spacing-xl);
  border-radius: var(--radius-2xl);
  box-shadow: var(--shadow-xl);
  margin-bottom: var(--spacing-xl);
  text-align: center;
  background: var(--surface-color);
  backdrop-filter: blur(10px);
  transition: all 0.3s ease;
}

/* Optional: Add a subtle effect when scrolling */
.header.scrolled {
  box-shadow: var(--shadow-lg);
  padding: var(--spacing-md) var(--spacing-xl);
}

.header-content {
  max-width: 100%;
  margin: 0 auto;
}

.title {
  font-size: 2rem;
  font-weight: 700;
  color: #000000;
  margin-bottom: var(--spacing-xs);
  transition: font-size 0.3s ease;
}

/* Optional: Make title smaller when header is sticky and scrolled */
.header.scrolled .title {
  font-size: 1.5rem;
}

.subtitle {
  font-size: 1rem;
  color: var(--sidebar-text-muted);
  margin-bottom: 0;
  transition: font-size 0.3s ease;
}

/* Optional: Make subtitle smaller when header is sticky and scrolled */
.header.scrolled .subtitle {
  font-size: 0.875rem;
}

/* Content using shared .content-section */
.content {
  background: var(--surface-color);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-md);
  padding: var(--spacing-xl);
  margin-top: var(--spacing-lg);
}

.leave-form {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

/* Form row using shared flex utils */
.form-row {
  flex-wrap: wrap;
}

.file-info {
  margin-top: var(--spacing-sm);
  font-size: 0.875rem;
  color: var(--text-light);
  padding: var(--spacing-sm);
  background: var(--background-alt);
  border-radius: var(--radius-md);
  border-left: 3px solid var(--info-color);
}

/* Form actions using shared */
.form-actions {
  margin-top: var(--spacing-md);
  padding-top: var(--spacing-md);
  border-top: 1px solid var(--border-color);
}

/* Responsive using shared media queries */
@media (max-width: 768px) {
  .apply-leave-view {
    padding: var(--spacing-md);
  }
  
  .header {
    padding: var(--spacing-md) var(--spacing-lg);
    margin-bottom: var(--spacing-lg);
  }
  
  .title {
    font-size: 1.75rem;
  }
  
  .subtitle {
    font-size: 0.875rem;
  }
  
  .content {
    padding: var(--spacing-lg);
  }
  
  .form-row {
    flex-direction: column;
  }
}

@media (max-width: 480px) {
  .header {
    padding: var(--spacing-sm) var(--spacing-md);
    margin-bottom: var(--spacing-md);
  }
  
  .title {
    font-size: 1.5rem;
  }
  
  .subtitle {
    font-size: 0.75rem;
  }
  
  .content {
    padding: var(--spacing-md);
  }
}
</style>