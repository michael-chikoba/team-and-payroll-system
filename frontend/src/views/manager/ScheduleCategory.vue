<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
      <div class="flex items-center">
        <!-- Icon rendering based on props -->
        <div :class="`p-2 rounded-lg mr-3 bg-${color}-100 text-${color}-600`">
          <!-- Simple mapping of icons can be done here or passed via slots. Using generic placeholder for brevity -->
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>
        </div>
        <h3 class="font-bold text-gray-800">{{ title }}</h3>
      </div>
      <span class="text-xs font-semibold text-gray-500 bg-gray-200 px-2 py-1 rounded-full">{{ tasks.length }}</span>
    </div>

    <div class="divide-y divide-gray-100">
      <div v-for="task in tasks" :key="task.id" class="p-4 hover:bg-gray-50 transition flex items-center justify-between group">
        <div class="flex-1 min-w-0 mr-4">
          <p class="text-sm font-medium text-gray-900 truncate">{{ task.title }}</p>
          <div class="flex items-center mt-1">
             <span class="text-xs text-gray-500 mr-2">Assigned: {{ task.assigned_to.name }}</span>
             <span v-if="task.deadline" class="text-xs text-red-500">Due: {{ new Date(task.deadline).toLocaleDateString() }}</span>
          </div>
        </div>
        <button 
          @click="$emit('complete', task.id)"
          class="opacity-0 group-hover:opacity-100 p-2 text-green-600 hover:bg-green-50 rounded-full transition focus:opacity-100"
          title="Mark Complete">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
        </button>
      </div>

      <div v-if="tasks.length === 0" class="p-6 text-center text-sm text-gray-400">
        No active tasks in this queue.
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: String,
  color: {
    type: String,
    default: 'indigo'
  },
  icon: String,
  tasks: Array
});

defineEmits(['complete']);
</script>