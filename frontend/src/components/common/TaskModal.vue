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
              <option value="">Select employee</option>
              <option
                v-for="employee in employees"
                :key="employee.user_id || employee.id"
                :value="employee.user_id"
              >
                {{ getEmployeeDisplayName(employee) }} - {{ employee.position }} ({{ employee.department }})
              </option>
            </select>
            <div v-if="loadingEmployees" class="loading-text">
              Loading team members...
            </div>
            <div v-if="employeeError" class="error-text">
              {{ employeeError }}
            </div>
            <div v-if="!loadingEmployees && employees.length === 0" class="error-text">
              No team members available for assignment.
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
            :disabled="loading || loadingEmployees"
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
import { ref, onMounted, watch, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
  task: Object,
  employees: {
    type: Array,
    default: () => []
  },
  loadingEmployees: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'save']);

const formData = ref({
  title: '',
  description: '',
  priority: 'moderate',
  assigned_to: '',
  deadline: '',
});

const loading = ref(false);
const employeeError = ref('');

// Local employees state in case we need to fetch directly
const localEmployees = ref([]);
const localLoadingEmployees = ref(false);

// Get the final employees list (prefer props, fallback to local)
const employees = computed(() => {
  const employeeList = props.employees && props.employees.length > 0 ? props.employees : localEmployees.value;
  
  // Filter out employees without user_id
  const validEmployees = employeeList.filter(emp => emp.user_id);
  
  console.log('Valid employees with user_id:', validEmployees);
  
  return validEmployees;
});

// Get the final loading state
const isLoadingEmployees = computed(() => {
  return props.loadingEmployees || localLoadingEmployees.value;
});

// Alias for template use
const loadingEmployees = isLoadingEmployees;

onMounted(() => {
  if (props.task) {
    formData.value = {
      title: props.task.title,
      description: props.task.description || '',
      priority: props.task.priority,
      assigned_to: props.task.assigned_to?.id || props.task.assigned_to?.user_id || '',
      deadline: props.task.deadline ? formatDateLocal(props.task.deadline) : '',
    };
  }

  // Always try to fetch employees using the simple endpoint
  fetchEmployeesDirectly();
});

// Watch for employees prop changes
watch(() => props.employees, (newEmployees) => {
  if (newEmployees && newEmployees.length > 0) {
    console.log('Received employees from props:', newEmployees);
    // Transform to ensure user_id is present
    localEmployees.value = newEmployees.map(emp => ({
      ...emp,
      user_id: emp.user_id || emp.id // Fallback to id if user_id not present
    }));
  }
}, { immediate: true });

// Fetch employees using the simple endpoint that returns user_id
const fetchEmployeesDirectly = async () => {
  localLoadingEmployees.value = true;
  employeeError.value = '';
  
  try {
    // Use the SIMPLE employees endpoint that returns user_id correctly
    const response = await axios.get('/api/tasks/employees/simple');
    const employeesData = response.data.employees || [];
    
    console.log('Fetched employees from simple endpoint:', employeesData);
    
    // The simple endpoint should already return user_id as 'id'
    localEmployees.value = employeesData.map(employee => ({
      id: employee.id, // This is actually user_id from backend
      user_id: employee.id, // Explicitly set user_id
      name: employee.full_name || `${employee.first_name} ${employee.last_name}`,
      first_name: employee.first_name,
      last_name: employee.last_name,
      email: employee.email,
      position: employee.position,
      department: employee.department,
      employee_id: employee.employee_id
    }));
    
    console.log('Transformed employees:', localEmployees.value);
    
    if (localEmployees.value.length === 0) {
      employeeError.value = 'No team members available for task assignment.';
    }
    
  } catch (err) {
    console.error('Failed to fetch employees:', err);
    employeeError.value = 'Failed to load team members. Please try again.';
    
    // Provide more specific error message
    if (err.response?.status === 401) {
      employeeError.value = 'Your session has expired. Please log in again.';
    } else if (err.response?.status === 403) {
      employeeError.value = 'You do not have permission to access team members.';
    } else if (err.code === 'ERR_NETWORK') {
      employeeError.value = 'Network error. Please check your connection.';
    }
  } finally {
    localLoadingEmployees.value = false;
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
  // Use the formatted name like in the employee management page
  if (employee.name) return employee.name;
  if (employee.first_name && employee.last_name) {
    return `${employee.first_name} ${employee.last_name}`;
  }
  if (employee.email) return employee.email;
  return 'Unknown Employee';
};

const handleSubmit = async () => {
  if (isLoadingEmployees.value) {
    alert('Please wait while team members are loading...');
    return;
  }

  if (!formData.value.assigned_to) {
    alert('Please select a team member to assign this task to.');
    return;
  }

  // Basic validation
  if (!formData.value.title.trim()) {
    alert('Please enter a task title.');
    return;
  }

  // Verify the assigned_to value is a valid user_id
  const selectedEmployee = employees.value.find(emp => emp.user_id === formData.value.assigned_to);
  if (!selectedEmployee) {
    alert('Invalid employee selection. Please select again.');
    return;
  }

  loading.value = true;
  
  try {
    // Prepare the data for API
    const submitData = {
      title: formData.value.title.trim(),
      description: formData.value.description.trim(),
      priority: formData.value.priority,
      assigned_to: formData.value.assigned_to, // This should be user_id
      ...(formData.value.deadline && { deadline: new Date(formData.value.deadline).toISOString() })
    };

    console.log('Submitting task data:', submitData);
    console.log('Selected employee:', selectedEmployee);
    
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