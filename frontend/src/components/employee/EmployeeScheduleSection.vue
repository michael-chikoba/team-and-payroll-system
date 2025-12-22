<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-6">
    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
      <h3 class="text-lg font-bold text-gray-800 uppercase tracking-wider">
        {{ formatType(type) }}
      </h3>
    </div>
    
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="schedule in schedules" :key="schedule.id" class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4">
              <div class="text-sm font-semibold text-gray-900">{{ schedule.title }}</div>
              <div class="text-xs text-gray-500 truncate max-w-xs">{{ schedule.description }}</div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600">
              {{ formatDate(schedule.due_date) }}
            </td>
            <td class="px-6 py-4">
              <span :class="getStatusClass(schedule.status)" class="px-2 py-1 text-xs font-medium rounded-full">
                {{ schedule.status.replace('_', ' ') }}
              </span>
            </td>
            <td class="px-6 py-4 text-right">
              <button 
                @click="$emit('view-details', schedule)"
                class="text-indigo-600 hover:text-indigo-900 font-medium text-sm"
              >
                Details
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup>
defineProps({
  type: String,
  schedules: Array
});

defineEmits(['view-details', 'status-change']);

const formatType = (type) => type ? type.replace(/_/g, ' ') : 'General';

const formatDate = (date) => {
  if (!date) return 'No date';
  return new Date(date).toLocaleDateString('en-US', { 
    month: 'short', 
    day: 'numeric', 
    year: 'numeric' 
  });
};

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    overdue: 'bg-red-100 text-red-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};
</script>