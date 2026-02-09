<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-container">
      <div class="modal-header">
        <h2>Create Business Group</h2>
        <button @click="$emit('close')" class="close-btn">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="modal-body">
        <!-- Group Name -->
        <div class="form-group">
          <label>Group Name *</label>
          <input
            v-model="form.name"
            type="text"
            placeholder="e.g., TechCorp Global"
            required
            class="form-input"
          />
        </div>

        <!-- Description -->
        <div class="form-group">
          <label>Description</label>
          <textarea
            v-model="form.description"
            placeholder="Brief description of the business group"
            rows="3"
            class="form-input"
          ></textarea>
        </div>

        <!-- Group Type -->
        <div class="form-group">
          <label>Group Type *</label>
          <select v-model="form.group_type" required class="form-input">
            <option value="">Select type...</option>
            <option value="holding">Holding Company</option>
            <option value="franchise">Franchise</option>
            <option value="subsidiary">Subsidiary</option>
            <option value="partnership">Partnership</option>
          </select>
        </div>

        <!-- Feature Toggles -->
        <div class="features-section">
          <h3>Group Features</h3>
          
          <div class="toggle-group">
            <label class="toggle-label">
              <input
                v-model="form.allow_cross_business_tickets"
                type="checkbox"
                class="toggle-input"
              />
              <span class="toggle-text">
                <strong>Cross-Business Tickets</strong>
                <small>Allow ticket creation across businesses</small>
              </span>
            </label>
          </div>

          <div class="toggle-group">
            <label class="toggle-label">
              <input
                v-model="form.allow_cross_business_tasks"
                type="checkbox"
                class="toggle-input"
              />
              <span class="toggle-text">
                <strong>Cross-Business Tasks</strong>
                <small>Allow task assignment across businesses</small>
              </span>
            </label>
          </div>

          <div class="toggle-group">
            <label class="toggle-label">
              <input
                v-model="form.allow_employee_visibility"
                type="checkbox"
                class="toggle-input"
              />
              <span class="toggle-text">
                <strong>Employee Visibility</strong>
                <small>Show employees from all businesses</small>
              </span>
            </label>
          </div>

          <div class="toggle-group">
            <label class="toggle-label">
              <input
                v-model="form.allow_resource_sharing"
                type="checkbox"
                class="toggle-input"
              />
              <span class="toggle-text">
                <strong>Resource Sharing</strong>
                <small>Enable sharing of resources</small>
              </span>
            </label>
          </div>
        </div>

        <!-- Error Message -->
        <div v-if="errorMessage" class="error-message">
          <i class="fas fa-exclamation-circle"></i>
          {{ errorMessage }}
        </div>

        <!-- Buttons -->
        <div class="modal-footer">
          <button type="button" @click="$emit('close')" class="btn-secondary">
            Cancel
          </button>
          <button type="submit" :disabled="submitting" class="btn-primary">
            <i v-if="submitting" class="fas fa-spinner fa-spin"></i>
            {{ submitting ? 'Creating...' : 'Create Group' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue';
import axios from 'axios';

export default {
  name: 'CreateBusinessGroupModal',
  emits: ['close', 'created'],
  setup(props, { emit }) {
    const form = ref({
      name: '',
      description: '',
      group_type: '',
      allow_cross_business_tickets: true,
      allow_cross_business_tasks: true,
      allow_cross_business_projects: false,
      allow_employee_visibility: false,
      allow_resource_sharing: false,
    });

    const submitting = ref(false);
    const errorMessage = ref('');

    const handleSubmit = async () => {
      try {
        submitting.value = true;
        errorMessage.value = '';

        const response = await axios.post('/api/business-groups', form.value);

        if (response.data.success) {
          emit('created', response.data.data);
          emit('close');
        }
      } catch (error) {
        console.error('Error creating group:', error);
        errorMessage.value = error.response?.data?.message || 'Failed to create business group';
      } finally {
        submitting.value = false;
      }
    };

    return {
      form,
      submitting,
      errorMessage,
      handleSubmit
    };
  }
};
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
  max-width: 600px;
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
  font-size: 24px;
  font-weight: 700;
  color: #1a202c;
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  font-size: 20px;
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

textarea.form-input {
  resize: vertical;
  font-family: inherit;
}

.features-section {
  margin-top: 28px;
  padding-top: 24px;
  border-top: 1px solid #e5e7eb;
}

.features-section h3 {
  font-size: 16px;
  font-weight: 700;
  color: #374151;
  margin-bottom: 16px;
}

.toggle-group {
  margin-bottom: 16px;
}

.toggle-label {
  display: flex;
  align-items: start;
  cursor: pointer;
  user-select: none;
}

.toggle-input {
  width: 20px;
  height: 20px;
  margin-right: 12px;
  cursor: pointer;
  flex-shrink: 0;
  margin-top: 2px;
}

.toggle-text {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.toggle-text strong {
  color: #374151;
  font-size: 14px;
}

.toggle-text small {
  color: #6b7280;
  font-size: 13px;
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
}

.btn-primary:hover:not(:disabled) {
  background-color: #4338ca;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>