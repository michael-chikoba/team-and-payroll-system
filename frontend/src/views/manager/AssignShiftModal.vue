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
                    <p class="text-gray-600 mt-2">Loading employees...</p>
                  </div>

                  <!-- Error State -->
                  <div v-else-if="error" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-red-600 text-sm">{{ error }}</p>
                    <button 
                      @click="retryFetch"
                      class="mt-2 text-sm text-red-700 hover:text-red-800 underline"
                    >
                      Retry
                    </button>
                  </div>

                  <!-- Form Content -->
                  <div v-else class="mt-4 space-y-4">
                    
                    <!-- Employee Select -->
                    <div>
                      <label class="block text-sm font-medium text-gray-700">Employee *</label>
                      <select 
                        v-model="form.employee_id" 
                        :disabled="employees.length === 0 || isEditing"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2 disabled:bg-gray-100 disabled:cursor-not-allowed"
                      >
                        <option value="" disabled>Select an employee</option>
                        <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                          {{ getEmployeeName(employee) }}
                        </option>
                      </select>
                      
                      <!-- Feedback Messages -->
                      <p v-if="employees.length === 0 && !loadingEmployees && !error" class="text-sm text-yellow-600 mt-1">
                        No employees found.
                      </p>
                      <p v-else-if="employees.length > 0" class="text-sm text-gray-500 mt-1">
                        {{ employees.length }} employee{{ employees.length !== 1 ? 's' : '' }} available
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

                    <!-- Dates -->
                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <label class="block text-sm font-medium text-gray-700">Start Date *</label>
                        <input type="date" v-model="form.start_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2" />
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-gray-700">End Date *</label>
                        <input type="date" v-model="form.end_date" :min="form.start_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2" />
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
                      <textarea v-model="form.notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm border p-2"></textarea>
                    </div>

                    <!-- Form Errors -->
                    <div v-if="formErrors.length > 0" class="p-3 bg-red-50 border border-red-200 rounded-lg">
                      <ul class="text-sm text-red-600">
                        <li v-for="error in formErrors" :key="error">• {{ error }}</li>
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
  authStore: { type: Object, required: true }
});

const emit = defineEmits(['close', 'saved']);

const employees = ref([]);
const loadingEmployees = ref(false);
const loadingSave = ref(false);
const error = ref(null);
const formErrors = ref([]);
const retryCount = ref(0);

const isEditing = computed(() => props.assignment !== null);

const form = reactive({
  employee_id: '',
  shift_type: 'morning',
  start_date: new Date().toISOString().split('T')[0],
  end_date: new Date().toISOString().split('T')[0],
  start_time: '06:00',
  end_time: '14:00',
  notes: ''
});

// ROBUST NAME GETTER: Handles all possible API structures
function getEmployeeName(employee) {
  if (!employee) return 'Unknown';
  
  // 1. Check top-level full name fields
  if (employee.full_name) return employee.full_name;
  if (employee.name) return employee.name;
  
  // 2. Check nested user object
  if (employee.user) {
    if (employee.user.full_name) return employee.user.full_name;
    if (employee.user.name) return employee.user.name;
    if (employee.user.first_name || employee.user.last_name) {
      return `${employee.user.first_name || ''} ${employee.user.last_name || ''}`.trim();
    }
  }
  
  // 3. Check combined name fields
  if (employee.first_name || employee.last_name) {
    return `${employee.first_name || ''} ${employee.last_name || ''}`.trim();
  }
  
  // 4. Fallbacks
  return employee.email || employee.employee_id || `Employee #${employee.id}`;
}

watch(() => props.assignment, (newAssignment) => {
  if (newAssignment) {
    form.employee_id = newAssignment.employee_id;
    form.shift_type = newAssignment.shift_type || 'morning';
    form.start_date = newAssignment.start_date || newAssignment.shift_date;
    form.end_date = newAssignment.end_date || newAssignment.shift_date;
    form.start_time = newAssignment.start_time;
    form.end_time = newAssignment.end_time;
    form.notes = newAssignment.notes || '';
  } else {
    resetForm();
  }
}, { immediate: true });

function resetForm() {
  form.employee_id = '';
  form.shift_type = 'morning';
  const today = new Date().toISOString().split('T')[0];
  form.start_date = today;
  form.end_date = today;
  form.start_time = '06:00';
  form.end_time = '14:00';
  form.notes = '';
}

watch(() => form.shift_type, (newType) => {
  if (isEditing.value) return;
  switch(newType) {
    case 'morning': form.start_time = '06:00'; form.end_time = '14:00'; break;
    case 'evening': form.start_time = '14:00'; form.end_time = '22:00'; break;
    case 'night': form.start_time = '22:00'; form.end_time = '06:00'; break;
    default: form.start_time = '08:00'; form.end_time = '17:00';
  }
});

watch(() => props.show, async (show) => {
  if (show) {
    retryCount.value = 0;
    // Reload employees every time modal opens to ensure up-to-date list
    await fetchEmployees();
  }
});

async function fetchEmployees(isRetry = false) {
  loadingEmployees.value = true;
  error.value = null;

  try {
    // 1. Attempt Primary Endpoint (Manager)
    let response = await axios.get('/api/manager/employees', {
      params: { manager_id: props.authStore.user?.id }
    });

    let extractedData = extractArrayFromResponse(response.data);

    // 2. Fallback: If manager list is empty, try generic employees endpoint (often needed for Admins)
    if (extractedData.length === 0) {
      console.log('Manager endpoint returned empty, trying generic endpoint...');
      response = await axios.get('/api/employees');
      extractedData = extractArrayFromResponse(response.data);
    }

    employees.value = extractedData;

    if (employees.value.length === 0) {
      error.value = 'No employees found in the system.';
    }

  } catch (err) {
    console.error('Fetch error:', err);
    error.value = 'Failed to load employees. Please check connection.';
  } finally {
    loadingEmployees.value = false;
  }
}

// Helper to find the array regardless of nesting depth (data.data vs data)
function extractArrayFromResponse(responseData) {
  if (!responseData) return [];

  // Priority 1: Laravel Paginated Resource { data: [...] }
  if (Array.isArray(responseData.data)) {
    return responseData.data;
  }
  
  // Priority 2: Direct Array [...]
  if (Array.isArray(responseData)) {
    return responseData;
  }

  // Priority 3: Deep nested { data: { data: [...] } }
  if (responseData.data && Array.isArray(responseData.data.data)) {
    return responseData.data.data;
  }

  return [];
}

function retryFetch() {
  retryCount.value++;
  if (retryCount.value <= 3) fetchEmployees(true);
}

function validateForm() {
  formErrors.value = [];
  if (!form.employee_id) formErrors.value.push('Please select an employee');
  if (!form.start_date || !form.end_date) formErrors.value.push('Please select dates');
  if (form.end_date < form.start_date) formErrors.value.push('End date cannot be before start date');
  return formErrors.value.length === 0;
}

async function handleSubmit() {
  if (!validateForm()) return;
  
  loadingSave.value = true;
  try {
    const url = isEditing.value ? `/api/shift-assignments/${props.assignment.id}` : '/api/shift-assignments';
    const method = isEditing.value ? 'PUT' : 'POST';
    
    await axios({ method, url, data: form });
    
    emit('saved');
    emit('close');
  } catch (err) {
    console.error(err);
    formErrors.value = [err.response?.data?.message || 'Failed to save shift.'];
  } finally {
    loadingSave.value = false;
  }
}
</script>