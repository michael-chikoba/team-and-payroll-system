<template>
  <transition
    enter-active-class="ease-out duration-300"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="ease-in duration-200"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <!-- Backdrop -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

      <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
        <transition
          enter-active-class="ease-out duration-300"
          enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          enter-to-class="opacity-100 translate-y-0 sm:scale-100"
          leave-active-class="ease-in duration-200"
          leave-from-class="opacity-100 translate-y-0 sm:scale-100"
          leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
          <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
            <!-- Header -->
            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
              <div class="sm:flex sm:items-start">
                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10">
                  <span class="text-xl">⏱️</span>
                </div>
                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                  <h3 class="text-lg font-semibold leading-6 text-gray-900" id="modal-title">
                    {{ isEditing ? 'Edit Shift Assignment' : 'Assign New Shift' }}
                  </h3>

                  <!-- Loading State -->
                  <div v-if="loadingEmployees" class="mt-4 text-center py-4">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                    <p class="text-gray-600 mt-2">Loading team members...</p>
                  </div>

                  <!-- Form Content -->
                  <div v-else class="mt-4 space-y-4">
                    
                    <!-- Debug Info (Remove in production) -->
                    <div v-if="debugMode" class="p-2 bg-yellow-50 border border-yellow-200 rounded text-xs">
                      <p><strong>Debug:</strong> Found {{ employees.length }} employees</p>
                      <p v-if="employees.length > 0">First: {{ getEmployeeName(employees[0]) }}</p>
                      <p>Manager ID: {{ authStore.user?.id }}</p>
                    </div>

                    <!-- Employee Select -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Employee *</label>
                      <select 
                        v-model="form.employee_id" 
                        :disabled="employees.length === 0 || isEditing"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2 disabled:bg-gray-100 disabled:cursor-not-allowed"
                      >
                        <option value="" disabled>Select an employee</option>
                       
                        <option 
                          v-for="employee in employees" 
                          :key="employee.id" 
                          :value="employee.id"
                        >
                          {{ getEmployeeName(employee) }}
                        </option>
                      </select>
                      
                      <!-- Status Messages -->
                      <p v-if="employees.length === 0 && !loadingEmployees" class="text-sm text-yellow-600 mt-1">
                        No team members found. Contact HR to assign employees to your team.
                      </p>
                      <p v-else class="text-sm text-gray-500 mt-1">
                        {{ employees.length }} team member{{ employees.length !== 1 ? 's' : '' }} available
                      </p>
                    </div>

                    <!-- Shift Type -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Shift Type *</label>
                      <select v-model="form.shift_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2">
                        <option value="morning">Morning (06:00 - 14:00)</option>
                        <option value="evening">Evening (14:00 - 22:00)</option>
                        <option value="night">Night (22:00 - 06:00)</option>
                        <option value="day">Day Shift</option>
                      </select>
                    </div>

                    <!-- Date Range Selection -->
                    <div class="border-t border-gray-200 pt-4">
                      <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700">Date Range *</label>
                        <div class="flex items-center space-x-2">
                          <button 
                            type="button"
                            @click="toggleDateRange"
                            class="text-sm text-blue-600 hover:text-blue-800"
                          >
                            {{ useDateRange ? 'Single Date' : 'Date Range' }}
                          </button>
                          <span class="text-xs text-gray-500">
                            {{ useDateRange ? 'Range selected' : 'Single date' }}
                          </span>
                        </div>
                      </div>

                      <!-- Single Date (when not using range) -->
                      <div v-if="!useDateRange">
                        <input 
                          type="date" 
                          v-model="form.start_date" 
                          :min="minDate"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2" 
                        />
                        <p class="text-xs text-gray-500 mt-1">Shift date</p>
                      </div>

                      <!-- Date Range (when using range) -->
                      <div v-else class="grid grid-cols-2 gap-4">
                        <div>
                          <label class="block text-sm font-medium text-gray-700">Start Date *</label>
                          <input 
                            type="date" 
                            v-model="form.start_date" 
                            :min="minDate"
                            :max="form.end_date"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2" 
                          />
                        </div>
                        <div>
                          <label class="block text-sm font-medium text-gray-700">End Date *</label>
                          <input 
                            type="date" 
                            v-model="form.end_date" 
                            :min="form.start_date"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2" 
                          />
                        </div>
                        <div v-if="form.start_date && form.end_date" class="col-span-2">
                          <div class="bg-blue-50 p-3 rounded-lg">
                            <p class="text-sm text-blue-800">
                              <span class="font-medium">Duration:</span> 
                              {{ calculateDuration() }} day{{ calculateDuration() !== 1 ? 's' : '' }}
                            </p>
                            <p class="text-xs text-blue-600 mt-1">
                              Shift will be assigned for each day in this range
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Time Range -->
                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Start Time *</label>
                        <input type="time" v-model="form.start_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2" />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700">End Time *</label>
                        <input type="time" v-model="form.end_time" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2" />
                      </div>
                    </div>

                    <!-- Notes -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                      <textarea v-model="form.notes" rows="2" placeholder="Add any special instructions..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2"></textarea>
                    </div>

                    <!-- Form Errors -->
                    <div v-if="formErrors.length > 0" class="p-3 bg-red-50 border border-red-200 rounded-lg">
                      <ul class="text-sm text-red-600">
                        <li v-for="error in formErrors" :key="error" class="mb-1 last:mb-0">• {{ error }}</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
              <button 
                type="button" 
                @click="handleSubmit"
                :disabled="loadingSave || (employees.length === 0 && !isEditing)"
                class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="loadingSave">{{ isEditing ? 'Updating...' : 'Assigning...' }}</span>
                <span v-else>{{ isEditing ? 'Update Shift' : 'Assign Shift' }}</span>
              </button>
              <button 
                type="button" 
                @click="$emit('close')"
                :disabled="loadingSave"
                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
              >
                Cancel
              </button>
            </div>
          </div>
        </transition>
      </div>
    </div>
  </transition>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  show: Boolean,
  assignment: { type: Object, default: null },
  employees: { type: Array, default: () => [] },
  loadingEmployees: { type: Boolean, default: false },
  authStore: { type: Object, required: true }
});

const emit = defineEmits(['close', 'saved', 'refresh-employees']);

const loadingSave = ref(false);
const formErrors = ref([]);
const debugMode = ref(false); // Set to true to see debug info
const useDateRange = ref(false); // Toggle for date range vs single date

const isEditing = computed(() => props.assignment !== null);

// Minimum date is today
const minDate = computed(() => new Date().toISOString().split('T')[0]);

const form = reactive({
  employee_id: '',
  shift_type: 'morning',
  start_date: new Date().toISOString().split('T')[0],
  end_date: new Date().toISOString().split('T')[0],
  start_time: '06:00',
  end_time: '14:00',
  notes: ''
});

// Matches the display logic in your EmployeeManagement table
function getEmployeeName(employee) {
  if (!employee) return 'Unknown';
  return employee.full_name || employee.user?.name || employee.email || `ID: ${employee.employee_id}`;
}

// Calculate duration between dates
function calculateDuration() {
  if (!form.start_date || !form.end_date) return 0;
  
  const start = new Date(form.start_date);
  const end = new Date(form.end_date);
  const diffTime = Math.abs(end - start);
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  return diffDays + 1; // +1 to include both start and end dates
}

// Toggle between date range and single date
function toggleDateRange() {
  useDateRange.value = !useDateRange.value;
  
  if (!useDateRange.value && form.start_date && form.end_date) {
    // When switching to single date, keep only the start date
    // Reset end_date to start_date
    form.end_date = form.start_date;
  } else if (useDateRange.value && form.start_date) {
    // When switching to range, set end_date to start_date if not already set
    if (!form.end_date || form.end_date < form.start_date) {
      form.end_date = form.start_date;
    }
  }
}

// Watch for assignment changes (Edit Mode)
watch(() => props.assignment, (newAssignment) => {
  if (newAssignment) {
    form.employee_id = newAssignment.employee_id || newAssignment.employee?.id || '';
    form.shift_type = newAssignment.shift_type || 'morning';
    
    // Handle both old single date and new date range format
    if (newAssignment.start_date && newAssignment.end_date) {
      form.start_date = newAssignment.start_date;
      form.end_date = newAssignment.end_date;
      useDateRange.value = newAssignment.start_date !== newAssignment.end_date;
    } else if (newAssignment.shift_date) {
      form.start_date = newAssignment.shift_date;
      form.end_date = newAssignment.shift_date;
      useDateRange.value = false;
    } else {
      form.start_date = new Date().toISOString().split('T')[0];
      form.end_date = new Date().toISOString().split('T')[0];
    }
    
    form.start_time = newAssignment.start_time || '06:00';
    form.end_time = newAssignment.end_time || '14:00';
    form.notes = newAssignment.notes || '';
  } else {
    // Reset form for new assignment
    resetForm();
  }
}, { immediate: true });

// Auto-adjust time based on shift type
watch(() => form.shift_type, (newType) => {
  if (isEditing.value) return; 
  switch(newType) {
    case 'morning': 
      form.start_time = '06:00'; 
      form.end_time = '14:00'; 
      break;
    case 'evening': 
      form.start_time = '14:00'; 
      form.end_time = '22:00'; 
      break;
    case 'night': 
      form.start_time = '22:00'; 
      form.end_time = '06:00'; 
      break;
    default: 
      form.start_time = '08:00'; 
      form.end_time = '17:00';
  }
});

// Watch start_date to update end_date if needed
watch(() => form.start_date, (newStartDate) => {
  if (useDateRange.value && newStartDate && form.end_date < newStartDate) {
    form.end_date = newStartDate;
  }
}, { immediate: true });

// Log employees when they change
watch(() => props.employees, (newEmployees) => {
  console.log('=== Employees Updated in Modal ===');
  console.log('Total employees:', newEmployees.length);
  console.log('Employees:', newEmployees);
}, { immediate: true, deep: true });

function resetForm() {
  form.employee_id = '';
  form.shift_type = 'morning';
  form.start_date = new Date().toISOString().split('T')[0];
  form.end_date = new Date().toISOString().split('T')[0];
  form.start_time = '06:00';
  form.end_time = '14:00';
  form.notes = '';
  useDateRange.value = false;
  formErrors.value = [];
}

function validateForm() {
  formErrors.value = [];
  
  if (!form.employee_id) {
    formErrors.value.push('Please select an employee');
  }
  if (!form.start_date) {
    formErrors.value.push('Please select a start date');
  }
  if (useDateRange.value && !form.end_date) {
    formErrors.value.push('Please select an end date');
  }
  if (form.start_date && form.start_date < minDate.value) {
    formErrors.value.push('Start date cannot be in the past');
  }
  if (useDateRange.value && form.end_date < form.start_date) {
    formErrors.value.push('End date must be on or after start date');
  }
  if (!form.start_time || !form.end_time) {
    formErrors.value.push('Please select start and end times');
  }
  if (form.start_time && form.end_time && form.start_time >= form.end_time) {
    // Allow night shifts (22:00 to 06:00) where end time appears "before" start time
    if (!(form.shift_type === 'night' && form.start_time > form.end_time)) {
      formErrors.value.push('End time must be after start time');
    }
  }
  
  // Validate date range length
  if (useDateRange.value && form.start_date && form.end_date) {
    const duration = calculateDuration();
    if (duration > 30) {
      formErrors.value.push('Date range cannot exceed 30 days');
    }
  }
  
  return formErrors.value.length === 0;
}

async function handleSubmit() {
  if (!validateForm()) return;

  loadingSave.value = true;
  formErrors.value = [];
  
  try {
    const url = isEditing.value 
      ? `/api/shift-assignments/${props.assignment.id}`
      : '/api/shift-assignments';
    
    const method = isEditing.value ? 'PUT' : 'POST';
    
    // Prepare payload
    const payload = {
      employee_id: parseInt(form.employee_id),
      shift_type: form.shift_type,
      start_date: form.start_date,
      end_date: form.end_date,
      use_date_range: useDateRange.value,
      start_time: form.start_time,
      end_time: form.end_time,
      notes: form.notes || null
    };

    console.log('=== Submitting Shift Assignment ===');
    console.log('Method:', method);
    console.log('URL:', url);
    console.log('Payload:', payload);
    
    const response = await axios({
      method,
      url,
      data: payload,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    });
    
    console.log('=== Assignment Saved Successfully ===');
    console.log('Response:', response.data);
    
    emit('saved', response.data);
    emit('close');
    resetForm();
  } catch (err) {
    console.error('=== Save Error ===');
    console.error('Error:', err);
    console.error('Response:', err.response);
    console.error('Response Data:', err.response?.data);
    console.error('Validation Errors:', err.response?.data?.errors);
    
    if (err.response?.data?.errors) {
      // Flatten Laravel validation errors object
      formErrors.value = Object.values(err.response.data.errors).flat();
    } else if (err.response?.data?.error) {
      formErrors.value = [err.response.data.error];
    } else if (err.response?.data?.message) {
      formErrors.value = [err.response.data.message];
    } else {
      formErrors.value = ['Failed to save assignment. Please try again.'];
    }
  } finally {
    loadingSave.value = false;
  }
}
</script>