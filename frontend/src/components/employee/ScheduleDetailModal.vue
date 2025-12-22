<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
      <div class="p-6 border-b border-gray-100 flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-800">{{ schedule.title }}</h2>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
      </div>
      
      <div class="p-6 space-y-6">
        <div>
          <label class="text-xs font-bold text-gray-400 uppercase tracking-wider">Description</label>
          <p class="mt-1 text-gray-700 leading-relaxed">{{ schedule.description || 'No description provided.' }}</p>
        </div>

        <div class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <label class="block text-gray-400 font-bold uppercase text-xs">Type</label>
            <p class="font-medium text-gray-800 uppercase">{{ schedule.schedule_type }}</p>
          </div>
          <div>
            <label class="block text-gray-400 font-bold uppercase text-xs">Due Date</label>
            <p class="font-medium text-gray-800">{{ new Date(schedule.due_date).toLocaleString() }}</p>
          </div>
        </div>

        <div class="pt-4 border-t border-gray-100">
          <label class="block text-sm font-semibold text-gray-700 mb-3">Update Status</label>
          <div class="flex flex-wrap gap-2">
            <button 
              v-for="status in ['pending', 'in_progress', 'completed']" 
              :key="status"
              @click="$emit('status-change', schedule.id, status)"
              :class="[
                'px-4 py-2 rounded-lg text-sm font-bold transition-all',
                schedule.status === status 
                  ? 'bg-indigo-600 text-white' 
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
              ]"
            >
              {{ status.replace('_', ' ').toUpperCase() }}
            </button>
          </div>
        </div>
      </div>

      <div class="p-6 bg-gray-50 text-right">
        <button 
          @click="$emit('close')"
          class="px-6 py-2 bg-white border border-gray-300 rounded-lg font-semibold text-gray-700 hover:bg-gray-100"
        >
          Close
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  schedule: Object,
  employeeView: Boolean
});
defineEmits(['close', 'status-change']);
</script>