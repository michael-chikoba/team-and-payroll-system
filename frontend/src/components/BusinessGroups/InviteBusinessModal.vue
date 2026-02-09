<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-container">
      <div class="modal-header">
        <h2>Invite Business</h2>
        <button @click="$emit('close')" class="close-btn">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="modal-body">
        <!-- Business Owner Email -->
        <div class="form-group">
          <label>Business Owner Email *</label>
          <input
            v-model="form.email"
            type="email"
            required
            class="form-input"
            placeholder="owner@business.com"
          />
          <p class="input-help">
            The email address of the business owner
          </p>
        </div>

        <!-- Role -->
        <div class="form-group">
          <label>Role *</label>
          <select v-model="form.proposed_role" class="form-input">
  <option value="member">Member</option>
  <option value="partner">Partner</option>
  <option value="owner">Owner</option>
</select>

        </div>

        <!-- Success -->
        <div v-if="successMessage" class="success-message">
          <i class="fas fa-check-circle"></i>
          {{ successMessage }}
        </div>

        <!-- Error -->
        <div v-if="errorMessage" class="error-message">
          <i class="fas fa-exclamation-circle"></i>
          {{ errorMessage }}
        </div>

        <!-- Footer -->
        <div class="modal-footer">
          <button type="button" class="btn-secondary" @click="$emit('close')">
            Cancel
          </button>

          <button type="submit" class="btn-primary" :disabled="submitting">
            <i v-if="submitting" class="fas fa-spinner fa-spin"></i>
            {{ submitting ? 'Sending...' : 'Send Invitation' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import axios from 'axios'

export default {
  name: 'InviteBusinessModal',
  props: {
    groupId: {
      type: [Number, String],
      required: true
    }
  },
  emits: ['close', 'invited'],
  setup(props, { emit }) {
    const form = ref({
  email: '',
  proposed_role: 'member',
  business_group_id: props.groupId
})


    const submitting = ref(false)
    const errorMessage = ref('')
    const successMessage = ref('')

    const handleSubmit = async () => {
      submitting.value = true
      errorMessage.value = ''
      successMessage.value = ''

      try {
        const { data } = await axios.post(
          '/api/business-group-invitations',
          form.value
        )

        successMessage.value = data.message || 'Invitation sent successfully'
        emit('invited', data.data)

        setTimeout(() => emit('close'), 1200)
      } catch (error) {
        if (error.response?.status === 422) {
          errorMessage.value = Object.values(
            error.response.data.errors
          ).flat().join(' ')
        } else {
          errorMessage.value =
            error.response?.data?.message ||
            'Failed to send invitation'
        }
      } finally {
        submitting.value = false
      }
    }

    return {
      form,
      submitting,
      errorMessage,
      successMessage,
      handleSubmit
    }
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal-container {
  background: white;
  border-radius: 12px;
  max-width: 500px;
  width: 100%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  font-size: 20px;
  font-weight: 600;
  color: #1a202c;
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  font-size: 18px;
  color: #6b7280;
  cursor: pointer;
  padding: 8px;
  border-radius: 6px;
  transition: background-color 0.2s;
}

.close-btn:hover {
  background-color: #f3f4f6;
}

.modal-body {
  padding: 24px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  font-weight: 600;
  color: #374151;
  margin-bottom: 8px;
  font-size: 14px;
}

.input-help {
  font-size: 12px;
  color: #6b7280;
  margin-top: 4px;
  margin-bottom: 0;
}

.form-input {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.form-input:focus {
  outline: none;
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.success-message {
  background-color: #d1fae5;
  border: 1px solid #a7f3d0;
  color: #065f46;
  padding: 12px 16px;
  border-radius: 8px;
  margin-top: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}

.error-message {
  background-color: #fee2e2;
  border: 1px solid #fca5a5;
  color: #991b1b;
  padding: 12px 16px;
  border-radius: 8px;
  margin-top: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 24px;
  padding-top: 20px;
  border-top: 1px solid #e5e7eb;
}

.btn-secondary {
  background-color: white;
  color: #374151;
  border: 1px solid #d1d5db;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-secondary:hover {
  background-color: #f9fafb;
}

.btn-primary {
  background-color: #4f46e5;
  color: white;
  border: none;
  padding: 10px 24px;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 140px;
  justify-content: center;
}

.btn-primary:hover:not(:disabled) {
  background-color: #4338ca;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.fa-spinner {
  margin-right: 8px;
}
</style>