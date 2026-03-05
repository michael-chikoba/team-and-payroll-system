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
            <label for="status">Status *</label>
            <select
              id="status"
              v-model="formData.status"
              required
              class="form-control"
              :disabled="loading"
            >
              <option value="todo">To Do</option>
              <option value="in_progress">In Progress</option>
              <option value="under_review">Under Review</option>
              <option value="completed">Completed</option>
            </select>
            <div class="hint-text">
              💡 Select current status (useful for retroactive tasks)
            </div>
          </div>
        </div>

        <div class="form-group">
          <label for="assigned_to">Assign To *</label>
          <select
            id="assigned_to"
            v-model="formData.assigned_to"
            required
            class="form-control assignee-select"
            :disabled="loading || loadingEmployees"
          >
            <option value="">Select team member</option>
            
            <!-- Current Business Employees -->
            <optgroup v-if="currentBusinessEmployees.length > 0" label="Your Business">
              <option
                v-for="employee in currentBusinessEmployees"
                :key="employee.id"
                :value="employee.id"
                class="assignee-option"
              >
                {{ getEmployeeDisplayName(employee) }}
                <span v-if="employee.position"> - {{ employee.position }}</span>
                <span v-if="employee.department"> ({{ employee.department }})</span>
              </option>
            </optgroup>
            
            <!-- Partner Business Employees -->
            <optgroup v-if="externalBusinessEmployees.length > 0" label="Partner Businesses">
              <option
                v-for="employee in externalBusinessEmployees"
                :key="employee.id"
                :value="employee.id"
                class="external-option assignee-option"
              >
                {{ getEmployeeDisplayName(employee) }}
                <span v-if="employee.business_name"> [{{ employee.business_name }}]</span>
                <span v-if="employee.position"> - {{ employee.position }}</span>
              </option>
            </optgroup>
          </select>
          
          <!-- Info message about external employees -->
          <div v-if="externalBusinessEmployees.length > 0 && !loadingEmployees" class="info-text">
            ℹ️ You can assign tasks to {{ externalBusinessEmployees.length }} employee(s) from partner businesses
          </div>
          
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

        <!-- NEW: Date Range Section -->
        <div class="date-section">
          <h3 class="section-title">📅 Timeline</h3>
          
          <div class="form-row">
            <div class="form-group">
              <label for="planned_start_date">
                Start Date
                <span class="optional-badge">Optional</span>
              </label>
              <input
                id="planned_start_date"
                v-model="formData.planned_start_date"
                type="date"
                class="form-control"
                :disabled="loading"
                :max="formData.deadline || undefined"
              />
              <div class="hint-text">
                📌 When did/will work start? 
              </div>
            </div>

            <div class="form-group">
              <label for="deadline">
                Deadline
              </label>
              <input
                id="deadline"
                v-model="formData.deadline"
                type="date"
                class="form-control"
                :disabled="loading"
                :min="formData.planned_start_date || minDate"
              />
              <div v-if="deadlineError" class="error-text">
                {{ deadlineError }}
              </div>
              
            </div>
          </div>

          <!-- Date validation messages -->
          <div v-if="showDateRangeWarning" class="warning-box">
            ⚠️ Start date is after deadline. Please adjust the dates.
          </div>
          
          <div v-if="showRetroactiveInfo" class="info-box">
            ℹ️ Creating a retroactive task (start date in the past)
          </div>
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
            :disabled="loading || loadingEmployees || !formData.assigned_to || showDateRangeWarning"
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
import { ref, onMounted, computed, watch } from 'vue';
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
  status: 'todo',
  assigned_to: '',
  planned_start_date: '',
  deadline: '',
});

const loading = ref(false);
const employees = ref([]);
const loadingEmployees = ref(false);
const employeeError = ref('');
const deadlineError = ref('');

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

// Separate current business and external employees
const currentBusinessEmployees = computed(() => {
  return employees.value.filter(emp => !emp.is_from_other_business);
});

const externalBusinessEmployees = computed(() => {
  return employees.value.filter(emp => emp.is_from_other_business);
});

// Get today's date in YYYY-MM-DD format
const minDate = computed(() => {
  const today = new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
});

// Check if start date is after deadline
const showDateRangeWarning = computed(() => {
  if (!formData.value.planned_start_date || !formData.value.deadline) {
    return false;
  }
  
  const startDate = new Date(formData.value.planned_start_date);
  const deadlineDate = new Date(formData.value.deadline);
  
  return startDate > deadlineDate;
});

// Check if creating a retroactive task
const showRetroactiveInfo = computed(() => {
  if (!formData.value.planned_start_date) {
    return false;
  }
  
  const startDate = new Date(formData.value.planned_start_date);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  startDate.setHours(0, 0, 0, 0);
  
  return startDate < today;
});

// Watch for status changes and suggest appropriate start date
watch(() => formData.value.status, (newStatus) => {
  if (!formData.value.planned_start_date) {
    if (newStatus === 'completed' || newStatus === 'in_progress' || newStatus === 'under_review') {
      // Suggest setting a start date for tasks that are already in progress or completed
      console.log('Task is already started/completed - consider setting a start date');
    }
  }
});

onMounted(async () => {
  if (props.task) {
    formData.value = {
      title: props.task.title,
      description: props.task.description || '',
      priority: props.task.priority,
      status: props.task.status || 'todo',
      assigned_to: props.task.assigned_to?.id || '',
      planned_start_date: props.task.planned_start_date ? formatDateLocal(props.task.planned_start_date) : '',
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
    console.log('Current business employees:', employeesData.filter(e => !e.is_from_other_business).length);
    console.log('External business employees:', employeesData.filter(e => e.is_from_other_business).length);
    
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

const validateDeadline = () => {
  deadlineError.value = '';
  
  if (!formData.value.deadline) {
    // Deadline is optional, so empty is valid
    return true;
  }
  
  // If start date exists, deadline must be after it (handled by showDateRangeWarning)
  if (showDateRangeWarning.value) {
    deadlineError.value = 'Deadline must be after start date.';
    return false;
  }
  
  return true;
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

  // Validate dates
  if (!validateDeadline()) {
    document.getElementById('deadline')?.focus();
    return;
  }

  if (showDateRangeWarning.value) {
    alert('Start date cannot be after the deadline. Please adjust your dates.');
    return;
  }

  loading.value = true;
  
  try {
    // Prepare the data for API
    const submitData = {
      title: formData.value.title.trim(),
      description: formData.value.description.trim(),
      priority: formData.value.priority,
      status: formData.value.status,
      assigned_to: formData.value.assigned_to,
    };

    // Add optional dates if provided
    if (formData.value.planned_start_date) {
      submitData.planned_start_date = new Date(formData.value.planned_start_date).toISOString();
    }
    
    if (formData.value.deadline) {
      submitData.deadline = new Date(formData.value.deadline).toISOString();
    }

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
/* Font definitions */
:root {
  --font-size-xs: 0.75rem;
  --font-size-sm: 0.875rem;
  --font-size-base: 1rem;
  --font-size-lg: 1.125rem;
  --font-size-xl: 1.25rem;
  --font-size-2xl: 1.5rem;
}

* {
  font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 
               "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", 
               sans-serif, "Apple Color Emoji", "Segoe UI Emoji", 
               "Segoe UI Symbol", "Noto Color Emoji";
}

/* Typography */
.modal-header h2 {
  font-size: var(--font-size-2xl);
  font-weight: 600;
  line-height: 1.2;
  letter-spacing: -0.02em;
  color: #0f172a;
  margin: 0;
}

.section-title {
  font-size: var(--font-size-lg);
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 1rem;
  padding-bottom: 0.5rem;
  border-bottom: 2px solid #e2e8f0;
}

.form-group label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  font-size: var(--font-size-sm);
  font-weight: 600;
  letter-spacing: 0.025em;
  color: #1e293b;
  text-transform: uppercase;
}

.optional-badge {
  font-size: var(--font-size-xs);
  font-weight: 500;
  text-transform: lowercase;
  background: linear-gradient(135deg, #e0e7ff, #ddd6fe);
  color: #4c1d95;
  padding: 0.125rem 0.5rem;
  border-radius: 0.25rem;
}

.form-control {
  width: 100%;
  padding: 0.625rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  font-size: var(--font-size-sm);
  line-height: 1.5;
  transition: all 0.15s ease;
  background-color: white;
  color: #0f172a;
}

.form-control:focus {
  outline: none;
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.form-control::placeholder {
  color: #94a3b8;
  font-size: var(--font-size-sm);
}

/* Buttons */
.btn-primary, .btn-secondary {
  padding: 0.625rem 1.25rem;
  border: none;
  border-radius: 0.5rem;
  font-size: var(--font-size-sm);
  font-weight: 500;
  line-height: 1.5;
  cursor: pointer;
  transition: all 0.15s ease;
  min-width: 100px;
}

.btn-primary {
  background: linear-gradient(to right, #4f46e5, #7c3aed);
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: linear-gradient(to right, #4338ca, #6d28d9);
  transform: scale(1.02);
}

.btn-primary:disabled {
  background: #cbd5e1;
  cursor: not-allowed;
  opacity: 0.6;
}

.btn-secondary {
  background-color: #f1f5f9;
  color: #1e293b;
  border: 1px solid #e2e8f0;
}

.btn-secondary:hover:not(:disabled) {
  background-color: #e2e8f0;
  border-color: #cbd5e1;
}

/* Helper text styles */
.loading-text,
.error-text,
.info-text,
.hint-text {
  font-size: var(--font-size-xs);
  margin-top: 0.375rem;
  line-height: 1.5;
}

.loading-text {
  color: #4f46e5;
}

.error-text {
  color: #dc2626;
}

.info-text {
  color: #2563eb;
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-style: italic;
}

.hint-text {
  color: #059669;
  font-style: italic;
}

/* Info/Warning boxes */
.date-section {
  background: linear-gradient(135deg, #fefce8, #fef3c7);
  padding: 1.25rem;
  border-radius: 0.75rem;
  border: 1px solid #fde047;
  margin-bottom: 1.25rem;
}

.warning-box {
  background: linear-gradient(135deg, #fef2f2, #fee2e2);
  border: 1px solid #fca5a5;
  color: #991b1b;
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  font-size: var(--font-size-sm);
  margin-top: 1rem;
  font-weight: 500;
}

.info-box {
  background: linear-gradient(135deg, #eff6ff, #dbeafe);
  border: 1px solid #93c5fd;
  color: #1e40af;
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  font-size: var(--font-size-sm);
  margin-top: 1rem;
  font-weight: 500;
}

/* Select styling */
.assignee-select,
.assignee-option,
optgroup,
select.form-control,
select.form-control option {
  font-family: inherit;
  font-size: var(--font-size-sm);
  line-height: 1.5;
}

.assignee-option {
  padding: 0.5rem 0.75rem;
}

optgroup {
  font-weight: 600;
  font-size: var(--font-size-xs);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #1e293b;
  background-color: #f8fafc;
  padding: 0.5rem 0;
}

optgroup[label="Your Business"] {
  background: linear-gradient(to right, #f0fdf4, #f8fafc);
}

optgroup[label="Partner Businesses"] {
  background: linear-gradient(to right, #eff6ff, #f8fafc);
}

.external-option {
  font-style: italic;
  color: #334155;
}

/* Modal layout */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background-color: white;
  border-radius: 1rem;
  width: 90%;
  max-width: 700px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e2e8f0;
}

.close-btn {
  background: none;
  border: none;
  font-size: 2rem;
  color: #94a3b8;
  cursor: pointer;
  padding: 0;
  width: 2rem;
  height: 2rem;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
  border-radius: 0.375rem;
}

.close-btn:hover {
  color: #0f172a;
  background-color: #f1f5f9;
}

.modal-body {
  padding: 1.5rem;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding-top: 1.25rem;
  border-top: 1px solid #e2e8f0;
  margin-top: 0.5rem;
}

/* Responsive */
@media (max-width: 640px) {
  .form-row {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .modal-footer {
    flex-direction: column;
  }
  
  .btn-primary, .btn-secondary {
    width: 100%;
  }
  
  .modal-header h2 {
    font-size: 1.25rem;
  }
}
</style>