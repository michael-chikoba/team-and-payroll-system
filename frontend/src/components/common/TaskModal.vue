<template>
  <div class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-content">
      <div class="modal-header">
        <h2>{{ task ? 'Edit Task' : 'Create New Task' }}</h2>
        <button @click="$emit('close')" class="close-btn">&times;</button>
      </div>

      <form @submit.prevent="handleSubmit" class="modal-body">
        <div class="form-group">
          <label for="title">Title *</label>
          <input
            id="title"
            v-model="formData.title"
            type="text"
            required
            placeholder="Enter task title"
            class="form-control"
            :disabled="loading"
          />
        </div>

        <div class="form-group">
          <label for="description">Description</label>
          <textarea
            id="description"
            v-model="formData.description"
            rows="4"
            placeholder="Enter task description"
            class="form-control"
            :disabled="loading"
          ></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="priority">Priority *</label>
            <select
              id="priority"
              v-model="formData.priority"
              required
              class="form-control"
              :disabled="loading"
            >
              <option value="low">Low</option>
              <option value="moderate">Moderate</option>
              <option value="high">High</option>
              <option value="critical">Critical</option>
            </select>
          </div>

          <div class="form-group">
            <label for="assigned_to">Assign To *</label>
            <select
              id="assigned_to"
              v-model="formData.assigned_to"
              required
              class="form-control"
              :disabled="loading || loadingEmployees"
            >
              <option value="">Select team member</option>
              <option
                v-for="employee in employees"
                :key="employee.id"
                :value="employee.id"
              >
                {{ getEmployeeDisplayName(employee) }}
                <span v-if="employee.position">- {{ employee.position }}</span>
                <span v-if="employee.department">({{ employee.department }})</span>
                <span v-if="employee.role !== 'employee'">[{{ employee.role }}]</span>
              </option>
            </select>
            <div v-if="loadingEmployees" class="loading-text">
              Loading team members...
            </div>
            <div v-if="employeeError" class="error-text">
              {{ employeeError }}
            </div>
            <div v-if="!loadingEmployees && employees.length === 0" class="info-text">
              No team members available in your business.
            </div>
          </div>
        </div>

        <div class="form-group">
          <label for="deadline">Deadline</label>
          <input
            id="deadline"
            v-model="formData.deadline"
            type="date"
            class="form-control"
            :disabled="loading"
          />
        </div>

        <div class="modal-footer">
          <button 
            type="button" 
            @click="$emit('close')" 
            class="btn-secondary"
            :disabled="loading"
          >
            Cancel
          </button>
          <button 
            type="submit" 
            class="btn-primary"
            :disabled="loading || loadingEmployees || !formData.assigned_to"
          >
            <span v-if="loading">Processing...</span>
            <span v-else>{{ task ? 'Update' : 'Create' }} Task</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';

const props = defineProps({
  task: Object,
});

const emit = defineEmits(['close', 'save']);

const authStore = useAuthStore();
const formData = ref({
  title: '',
  description: '',
  priority: 'moderate',
  assigned_to: '',
  deadline: '',
});

const loading = ref(false);
const employees = ref([]);
const loadingEmployees = ref(false);
const employeeError = ref('');

const currentUser = computed(() => {
  return {
    id: authStore.user?.id,
    name: authStore.user?.name || authStore.user?.first_name + ' ' + authStore.user?.last_name,
    first_name: authStore.user?.first_name,
    last_name: authStore.user?.last_name,
    email: authStore.user?.email,
    employee_id: authStore.user?.employee?.employee_id,
    position: authStore.user?.employee?.position,
    department: authStore.user?.employee?.department,
    is_self: true,
    role: authStore.user?.role
  };
});

onMounted(async () => {
  if (props.task) {
    formData.value = {
      title: props.task.title,
      description: props.task.description || '',
      priority: props.task.priority,
      assigned_to: props.task.assigned_to?.id || '',
      deadline: props.task.deadline ? formatDateLocal(props.task.deadline) : '',
    };
  }

  await fetchEmployees();
});

const fetchEmployees = async () => {
  loadingEmployees.value = true;
  employeeError.value = '';
  
  try {
    console.log('Fetching employees for task assignment...');
    const response = await axios.get('/api/tasks/employees/simple');
    const employeesData = response.data.employees || [];
    
    console.log('Employees fetched:', employeesData);
    
    // If no employees returned, include current user
    if (employeesData.length === 0) {
      console.log('No employees found, adding current user');
      employees.value = [currentUser.value];
    } else {
      employees.value = employeesData;
    }
    
    // For new tasks, default to current user if not already selected
    if (!props.task && !formData.value.assigned_to && employees.value.length > 0) {
      const currentUserInList = employees.value.find(emp => emp.id === currentUser.value.id);
      if (currentUserInList) {
        formData.value.assigned_to = currentUser.value.id;
      } else if (employees.value[0]) {
        formData.value.assigned_to = employees.value[0].id;
      }
    }
    
  } catch (err) {
    console.error('Failed to fetch employees:', err);
    employeeError.value = 'Failed to load team members.';
    
    // Fallback: show current user only
    employees.value = [currentUser.value];
    if (!props.task) {
      formData.value.assigned_to = currentUser.value.id;
    }
  } finally {
    loadingEmployees.value = false;
  }
};

const formatDateLocal = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

const getEmployeeDisplayName = (employee) => {
  let name = employee.full_name || `${employee.first_name} ${employee.last_name}`;
  
  // Add "You" indicator
  if (employee.is_self) {
    name += ' (You)';
  }
  
  return name;
};

const handleSubmit = async () => {
  if (!formData.value.title.trim()) {
    alert('Please enter a task title.');
    return;
  }

  if (!formData.value.assigned_to) {
    alert('Please select a team member to assign this task to.');
    return;
  }

  loading.value = true;
  
  try {
    // Prepare the data for API
    const submitData = {
      title: formData.value.title.trim(),
      description: formData.value.description.trim(),
      priority: formData.value.priority,
      assigned_to: formData.value.assigned_to,
      ...(formData.value.deadline && { deadline: new Date(formData.value.deadline).toISOString() })
    };

    console.log('Submitting task data:', submitData);
    
    emit('save', submitData);
  } catch (err) {
    console.error('Error preparing task data:', err);
    alert('Error preparing task data. Please try again.');
  } finally {
    loading.value = false;
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
}

.modal-content {
  background-color: white;
  border-radius: 12px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 24px;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h2 {
  font-size: 24px;
  font-weight: 600;
  color: #2d3748;
  margin: 0;
}

.close-btn {
  background: none;
  border: none;
  font-size: 32px;
  color: #a0aec0;
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: color 0.2s;
}

.close-btn:hover {
  color: #2d3748;
}

.modal-body {
  padding: 24px;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-size: 14px;
  font-weight: 600;
  color: #2d3748;
}

.form-control {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #cbd5e0;
  border-radius: 6px;
  font-size: 14px;
  transition: border-color 0.2s;
}

.form-control:focus {
  outline: none;
  border-color: #4299e1;
  box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
}

.form-control:disabled {
  background-color: #f7fafc;
  cursor: not-allowed;
  opacity: 0.6;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  padding-top: 20px;
  border-top: 1px solid #e2e8f0;
}

.btn-primary, .btn-secondary {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
  min-width: 100px;
}

.btn-primary {
  background-color: #4299e1;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background-color: #3182ce;
}

.btn-primary:disabled {
  background-color: #a0aec0;
  cursor: not-allowed;
}

.btn-secondary {
  background-color: #e2e8f0;
  color: #2d3748;
}

.btn-secondary:hover:not(:disabled) {
  background-color: #cbd5e0;
}

.btn-secondary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.loading-text {
  font-size: 12px;
  color: #4299e1;
  margin-top: 4px;
}

.error-text {
  font-size: 12px;
  color: #e53e3e;
  margin-top: 4px;
}

.info-text {
  font-size: 12px;
  color: #718096;
  margin-top: 4px;
  font-style: italic;
}

@media (max-width: 640px) {
  .form-row {
    grid-template-columns: 1fr;
  }
  
  .modal-footer {
    flex-direction: column;
  }
  
  .btn-primary, .btn-secondary {
    width: 100%;
  }
}
</style>