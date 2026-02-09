<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-3xl font-extrabold text-gray-900">Shift Assignments</h1>
      <p class="text-gray-600 mt-1 font-medium">Manage and view shift assignments.</p>
    </div>

    <!-- Actions Bar -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6 flex flex-col md:flex-row items-center justify-between gap-4 border border-gray-200">
      <!-- Filters -->
      <div class="flex items-center gap-3 flex-1 flex-wrap w-full">
        <!-- Status Filter -->
        <select 
          v-model="filters.status" 
          @change="loadAssignments" 
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm bg-white text-gray-900 font-medium"
        >
          <option value="" class="text-gray-500">All Status</option>
          <option value="pending">Pending</option>
          <option value="accepted">Accepted</option>
          <option value="rejected">Rejected</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
        
        <!-- Type Filter -->
        <select 
          v-model="filters.shift_type" 
          @change="loadAssignments" 
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 text-sm bg-white text-gray-900 font-medium"
        >
          <option value="" class="text-gray-500">All Types</option>
          <option value="morning">Morning</option>
          <option value="day">Day</option>
          <option value="evening">Evening</option>
          <option value="night">Night</option>
        </select>
        
        <!-- Search Input -->
        <input
          v-model="filters.employee_name"
          type="text"
          @input="debounceLoadAssignments"
          class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 w-48 text-sm bg-white text-gray-900 placeholder-gray-500"
          placeholder="Search employee..."
        />
        
        <!-- Date Range -->
        <div class="flex items-center gap-2">
          <input 
            v-model="filters.from_date" 
            type="date" 
            @change="loadAssignments" 
            class="px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white text-gray-900 font-medium" 
          />
          <span class="text-gray-500 font-bold">-</span>
          <input 
            v-model="filters.to_date" 
            type="date" 
            @change="loadAssignments" 
            class="px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white text-gray-900 font-medium" 
          />
        </div>
        
        <!-- Clear Filters Button -->
        <button 
          @click="clearFilters" 
          class="px-3 py-2 text-sm text-gray-700 hover:text-gray-900 font-semibold hover:bg-gray-100 rounded-md transition-colors"
          :disabled="!activeFiltersCount"
        >
          Clear Filters
        </button>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center gap-3 w-full md:w-auto justify-end">
        <button
          @click="openAssignModal"
          class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors inline-flex items-center gap-2 font-bold shadow-sm"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Assign Shift
        </button>
        
        <button
          @click="loadAssignments"
          class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors inline-flex items-center gap-2 font-medium"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
          Refresh
        </button>
        
        <!-- Show All Toggle -->
        <button
          @click="toggleShowAll"
          class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm font-medium"
          :class="showAll ? 'bg-indigo-50 border-indigo-300 text-indigo-700' : ''"
        >
          {{ showAll ? 'Show Recent' : 'Show All' }}
        </button>
      </div>
    </div>

    <!-- Stats Summary -->
    <div v-if="assignments.length > 0" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">Total Shifts</p>
            <p class="text-2xl font-bold text-gray-900">{{ stats.total }}</p>
          </div>
          <div class="p-3 bg-indigo-50 rounded-full">
            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">Pending</p>
            <p class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</p>
          </div>
          <div class="p-3 bg-yellow-50 rounded-full">
            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">Accepted</p>
            <p class="text-2xl font-bold text-green-600">{{ stats.accepted }}</p>
          </div>
          <div class="p-3 bg-green-50 rounded-full">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
          </div>
        </div>
      </div>
      
      <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">Unique Employees</p>
            <p class="text-2xl font-bold text-blue-600">{{ stats.uniqueEmployees }}</p>
          </div>
          <div class="p-3 bg-blue-50 rounded-full">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Assignments List -->
    <div v-if="loading" class="text-center py-20">
      <div class="inline-block animate-spin rounded-full h-10 w-10 border-b-2 border-indigo-600"></div>
      <p class="text-gray-600 mt-3 text-sm font-medium">Loading schedule...</p>
    </div>

    <div v-else-if="assignments.length === 0" class="bg-white rounded-lg shadow-sm p-12 text-center border border-dashed border-gray-300">
      <div class="mx-auto h-12 w-12 text-gray-400">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      </div>
      <h3 class="mt-2 text-sm font-bold text-gray-900">No shifts found</h3>
      <p class="mt-1 text-sm text-gray-500">
        {{ activeFiltersCount > 0 ? 'Try adjusting your filters' : 'Get started by creating a new shift assignment' }}
      </p>
      <div class="mt-4 flex gap-3 justify-center">
        <button
          @click="openAssignModal"
          class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors inline-flex items-center gap-2 font-bold shadow-sm"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Assign Shift
        </button>
        <button
          v-if="activeFiltersCount > 0"
          @click="clearFilters"
          class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors font-medium"
        >
          Clear Filters
        </button>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 gap-4">
      <!-- Assignment Cards -->
      <div
        v-for="assignment in assignments"
        :key="assignment.id"
        class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:border-indigo-300 transition-all"
      >
        <!-- Card Content (Same as previous, styles inherited from globals or Tailwind defaults are mostly fine, but ensuring text-gray-900 on primary text) -->
        <div class="flex items-start justify-between">
          <div class="flex gap-4">
            <!-- Avatar -->
            <div class="w-12 h-12 bg-indigo-50 rounded-full flex items-center justify-center flex-shrink-0 text-indigo-700 font-bold text-lg border border-indigo-100">
              {{ getEmployeeInitial(assignment) }}
            </div>

            <!-- Details -->
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <h3 class="font-bold text-gray-900 text-lg">{{ getEmployeeName(assignment) }}</h3>
                <span :class="getStatusClass(assignment.status)" class="text-xs px-2 py-0.5 rounded-full font-bold capitalize">
                  {{ assignment.status }}
                </span>
              </div>
              
              <!-- Employee Info -->
              <div v-if="getEmployeeDetails(assignment)" class="mb-3">
                <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-700">
                  <span v-if="assignment.employee?.employee_id" class="flex items-center gap-1 font-medium">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/>
                    </svg>
                    ID: {{ assignment.employee.employee_id }}
                  </span>
                  <span v-if="assignment.employee?.position" class="flex items-center gap-1 font-medium">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    {{ assignment.employee.position }}
                  </span>
                </div>
              </div>
              
              <!-- Shift Details -->
              <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="text-sm text-gray-900 grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div class="space-y-2">
                    <div class="flex items-center gap-2">
                      <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                      <span class="font-bold text-gray-900">Date:</span>
                      <span>{{ formatDate(assignment.shift_date) }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="w-2 h-2 rounded-full ml-1" :class="getShiftTypeDot(assignment.shift_type)"></span>
                      <span class="font-bold text-gray-900">Type:</span>
                      <span>{{ capitalize(assignment.shift_type) }}</span>
                    </div>
                  </div>
                  
                  <div class="space-y-2">
                    <div class="flex items-center gap-2">
                      <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                      <span class="font-bold text-gray-900">Time:</span>
                      <span>{{ formatTime(assignment.start_time) }} - {{ formatTime(assignment.end_time) }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                      </svg>
                      <span class="font-bold text-gray-900">Duration:</span>
                      <span>{{ calculateShiftDuration(assignment.start_time, assignment.end_time) }}</span>
                    </div>
                  </div>
                  
                  <div class="space-y-2">
                    <div class="flex items-center gap-2">
                      <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                      </svg>
                      <span class="font-bold text-gray-900">Assigned By:</span>
                      <span>{{ getAssignedByName(assignment) || 'Unknown' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                      <svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                      </svg>
                      <span class="font-bold text-gray-900">Notes:</span>
                      <span>{{ assignment.notes || 'None' }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Actions Dropdown or Buttons -->
          <div class="flex flex-col gap-2">
            <div class="flex items-center gap-2">
              <button v-if="canEdit(assignment)" @click="editAssignment(assignment)" class="text-gray-500 hover:text-indigo-600 p-2 transition-colors" title="Edit Shift">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              </button>
              <button v-if="canDelete(assignment)" @click="deleteAssignment(assignment.id)" class="text-gray-500 hover:text-red-600 p-2 transition-colors" title="Delete Shift">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.total > 0 && pagination.last_page > 1" class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4">
       <div class="text-sm text-gray-700 font-medium">
         Showing {{ pagination.from }} to {{ pagination.to }} of {{ pagination.total }} shifts
       </div>
       <div class="flex gap-2">
         <button 
           @click="changePage(pagination.current_page - 1)" 
           :disabled="pagination.current_page === 1" 
           class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-1 bg-white text-gray-700 font-medium"
         >
           <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
           </svg>
           Prev
         </button>
         
         <div class="flex items-center gap-1">
           <button 
             v-for="page in visiblePages" 
             :key="page"
             @click="changePage(page)"
             :class="pagination.current_page === page ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'"
             class="px-3 py-1 border rounded-lg transition-colors min-w-[2.5rem] font-medium"
           >
             {{ page }}
           </button>
         </div>
         
         <button 
           @click="changePage(pagination.current_page + 1)" 
           :disabled="pagination.current_page === pagination.last_page" 
           class="px-3 py-1 border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-1 bg-white text-gray-700 font-medium"
         >
           Next
           <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
             <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
           </svg>
         </button>
       </div>
    </div>

    <!-- The Modal -->
    <AssignShiftModal
      v-if="showAssignModal"
      :show="showAssignModal"
      :assignment="selectedAssignment"
      :employees="employees"
      :loading-employees="loadingEmployees"
      :auth-store="authStore"
      @close="closeAssignModal"
      @saved="handleAssignmentSaved"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed, watch } from 'vue';
import { format } from 'date-fns';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';
import AssignShiftModal from './AssignShiftModal.vue';

const authStore = useAuthStore();
const assignments = ref([]);
const employees = ref([]);
const loading = ref(false);
const loadingEmployees = ref(false);
const showAssignModal = ref(false);
const selectedAssignment = ref(null);
const showDebug = ref(false);
const showAll = ref(false);
let debounceTimer = null;

const stats = reactive({
  total: 0,
  pending: 0,
  accepted: 0,
  rejected: 0,
  uniqueEmployees: 0
});

const pagination = reactive({
  total: 0,
  per_page: 20,
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0
});

const filters = reactive({
  status: '',
  shift_type: '',
  employee_name: '',
  from_date: '',
  to_date: ''
});

// Computed properties
const visiblePages = computed(() => {
  const pages = [];
  const current = pagination.current_page;
  const last = pagination.last_page;
  
  // Always show first page
  pages.push(1);
  
  // Calculate range around current page
  let start = Math.max(2, current - 1);
  let end = Math.min(last - 1, current + 1);
  
  // Adjust if at beginning
  if (current <= 3) {
    end = Math.min(last - 1, 4);
  }
  
  // Adjust if at end
  if (current >= last - 2) {
    start = Math.max(2, last - 3);
  }
  
  // Add ellipsis if needed
  if (start > 2) {
    pages.push('...');
  }
  
  // Add middle pages
  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  
  // Add ellipsis if needed
  if (end < last - 1) {
    pages.push('...');
  }
  
  // Always show last page if not already shown
  if (last > 1 && !pages.includes(last)) {
    pages.push(last);
  }
  
  return pages;
});

const activeFiltersCount = computed(() => {
  return Object.values(filters).filter(value => value !== '').length;
});

// Toggle show all
const toggleShowAll = () => {
  showAll.value = !showAll.value;
  loadAssignments(1);
};

// Clear all filters
const clearFilters = () => {
  filters.status = '';
  filters.shift_type = '';
  filters.employee_name = '';
  filters.from_date = '';
  filters.to_date = '';
  loadAssignments(1);
};

// Load Assignments
const loadAssignments = async (page = 1) => {
  loading.value = true;
  try {
    const params = { 
      page, 
      ...filters,
      show_all: showAll.value ? 1 : 0
    };
    
    // Clean empty filters
    Object.keys(params).forEach(key => (params[key] === '' || params[key] == null) && delete params[key]);

    const response = await axios.get('/api/shift-assignments', { params });
    
    let data = [];
    if (response.data.assignments) {
      data = response.data.assignments;
    } else if (response.data.data) {
      data = response.data.data;
    } else if (Array.isArray(response.data)) {
      data = response.data;
    }
    
    assignments.value = data || [];
    
    // Calculate statistics
    calculateStats();
    
    if (response.data.pagination) {
      const meta = response.data.pagination;
      Object.assign(pagination, {
        total: meta.total || 0,
        per_page: meta.per_page || 20,
        current_page: meta.current_page || 1,
        last_page: meta.last_page || 1,
        from: meta.from || 0,
        to: meta.to || 0
      });
    } else {
      pagination.total = data.length;
      pagination.from = 1;
      pagination.to = data.length;
      pagination.current_page = page;
      pagination.last_page = 1;
    }

  } catch (error) {
    console.error('Failed to load assignments', error);
    assignments.value = [];
    stats.total = 0;
    pagination.total = 0;
  } finally {
    loading.value = false;
  }
};

// Calculate statistics
const calculateStats = () => {
  stats.total = assignments.value.length;
  stats.pending = assignments.value.filter(a => a.status === 'pending').length;
  stats.accepted = assignments.value.filter(a => a.status === 'accepted').length;
  stats.rejected = assignments.value.filter(a => a.status === 'rejected').length;
  
  const employeeIds = new Set(assignments.value.map(a => a.employee_id).filter(id => id));
  stats.uniqueEmployees = employeeIds.size;
};

const debounceLoadAssignments = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => loadAssignments(1), 500);
};

const changePage = (page) => {
  if (page >= 1 && page <= pagination.last_page) {
    loadAssignments(page);
  }
};

// Employee Management
const fetchEmployees = async () => {
  if (employees.value.length > 0) return;
  loadingEmployees.value = true;
  try {
    let endpoint = '/api/manager/employees';
    if (authStore.user?.roles?.includes('admin')) {
      endpoint = '/api/admin/employees';
    } else if (authStore.user?.roles?.includes('super-admin')) {
      endpoint = '/api/admin/employees';
    }
    
    const response = await axios.get(endpoint);
    employees.value = response.data.data || response.data.employees || response.data || [];
  } catch (error) {
    console.error('Failed to fetch employees', error);
    employees.value = [];
  } finally {
    loadingEmployees.value = false;
  }
};

// Modal Logic
const openAssignModal = async () => {
  selectedAssignment.value = null;
  showAssignModal.value = true;
  await fetchEmployees();
};

const editAssignment = async (assignment) => {
  selectedAssignment.value = assignment;
  showAssignModal.value = true;
  await fetchEmployees();
};

const closeAssignModal = () => {
  showAssignModal.value = false;
  selectedAssignment.value = null;
};

const handleAssignmentSaved = () => {
  closeAssignModal();
  loadAssignments(pagination.current_page);
};

// Assignment Actions
const deleteAssignment = async (id) => {
  if (!confirm('Are you sure you want to delete this shift assignment?')) return;
  try {
    await axios.delete(`/api/shift-assignments/${id}`);
    loadAssignments(pagination.current_page);
  } catch (error) {
    alert('Failed to delete assignment: ' + (error.response?.data?.message || error.message));
  }
};

// Helper Functions
const canEdit = (assignment) => {
  if (!authStore.user?.roles) return false;
  const userRoles = authStore.user.roles;
  const isManagerOrAdmin = userRoles.includes('manager') || userRoles.includes('admin') || userRoles.includes('super-admin');
  if (!isManagerOrAdmin) return false;
  return ['pending', 'accepted'].includes(assignment.status);
};

const canDelete = (assignment) => {
  if (!authStore.user?.roles) return false;
  const userRoles = authStore.user.roles;
  const isManagerOrAdmin = userRoles.includes('manager') || userRoles.includes('admin') || userRoles.includes('super-admin');
  if (!isManagerOrAdmin) return false;
  return assignment.status !== 'completed';
};

const formatDate = (d) => {
  if (!d) return '-';
  try {
    return format(new Date(d), 'MMM dd, yyyy');
  } catch (e) {
    return d;
  }
};

const formatTime = (t) => {
  if (!t) return '';
  try {
    const [h, m] = t.split(':');
    const hour = parseInt(h);
    const minute = parseInt(m);
    const period = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minute.toString().padStart(2, '0')} ${period}`;
  } catch (e) {
    return t;
  }
};

const calculateShiftDuration = (startTime, endTime) => {
  if (!startTime || !endTime) return '';
  try {
    const [startHour, startMinute] = startTime.split(':').map(Number);
    const [endHour, endMinute] = endTime.split(':').map(Number);
    let startTotal = startHour * 60 + startMinute;
    let endTotal = endHour * 60 + endMinute;
    if (endTotal < startTotal) endTotal += 24 * 60;
    const durationMinutes = endTotal - startTotal;
    const hours = Math.floor(durationMinutes / 60);
    const minutes = durationMinutes % 60;
    return `${hours}h ${minutes}m`;
  } catch (e) {
    return '';
  }
};

const capitalize = (s) => s ? s.charAt(0).toUpperCase() + s.slice(1) : '';

const getEmployeeName = (assignment) => {
  if (assignment.employee?.full_name) return assignment.employee.full_name;
  if (assignment.employee?.user?.name) return assignment.employee.user.name;
  if (assignment.employee?.name) return assignment.employee.name;
  if (assignment.employee?.employee_id) return `Employee #${assignment.employee.employee_id}`;
  return 'Unknown Employee';
};

const getEmployeeInitial = (assignment) => {
  const name = getEmployeeName(assignment);
  return name.charAt(0).toUpperCase();
};

const getEmployeeDetails = (assignment) => {
  return assignment.employee && (
    assignment.employee.employee_id ||
    assignment.employee.position ||
    assignment.employee.department?.name
  );
};

const getAssignedByName = (assignment) => {
  if (assignment.assigned_by?.name) return assignment.assigned_by.name;
  if (assignment.assigned_by?.full_name) return assignment.assigned_by.full_name;
  if (assignment.assigned_by?.user?.name) return assignment.assigned_by.user.name;
  return null;
};

const getStatusClass = (s) => ({
  pending: 'bg-yellow-100 text-yellow-800 border border-yellow-200',
  accepted: 'bg-green-100 text-green-800 border border-green-200',
  rejected: 'bg-red-100 text-red-800 border border-red-200',
  completed: 'bg-blue-100 text-blue-800 border border-blue-200',
  cancelled: 'bg-gray-100 text-gray-800 border border-gray-200'
}[s] || 'bg-gray-100 text-gray-800 border border-gray-200');

const getShiftTypeDot = (t) => ({
  morning: 'bg-orange-400',
  day: 'bg-blue-400',
  evening: 'bg-indigo-400',
  night: 'bg-purple-400'
}[t] || 'bg-gray-400');

onMounted(() => {
  const today = new Date();
  filters.from_date = new Date(today.setDate(today.getDate() - 30)).toISOString().split('T')[0];
  filters.to_date = new Date().toISOString().split('T')[0];
  loadAssignments();
});

watch(() => authStore.user, () => {
  loadAssignments();
});
</script>