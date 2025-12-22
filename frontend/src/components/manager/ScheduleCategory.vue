<template>
  <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden h-full flex flex-col">
    <!-- Header -->
    <div :class="`px-5 py-4 border-b border-gray-100 bg-${color}-50 flex items-center justify-between`">
      <div class="flex items-center gap-3">
        <div :class="`p-2 rounded-lg bg-${color}-100 text-${color}-600`">
          <slot name="icon">📌</slot>
        </div>
        <h3 class="font-bold text-gray-800 text-sm uppercase tracking-wide">{{ title }}</h3>
      </div>
      <span :class="`text-xs font-bold text-${color}-700 bg-white px-2 py-1 rounded-full border border-${color}-100`">
        {{ tasks.length }}
      </span>
    </div>

    <!-- List -->
    <div class="divide-y divide-gray-100 flex-1 overflow-y-auto max-h-[300px]">
      <div v-for="task in tasks" :key="task.id" class="p-4 hover:bg-gray-50 transition group">
        <div class="flex items-start justify-between">
          <div>
            <p class="text-sm font-semibold text-gray-800">{{ task.title }}</p>
            <div class="flex items-center mt-1 gap-2">
              <span class="text-xs text-gray-500 bg-gray-100 px-1.5 py-0.5 rounded">
                👤 {{ task.assigned_to.name }}
              </span>
              <span v-if="task.deadline" class="text-xs text-red-500 font-medium">
                📅 {{ new Date(task.deadline).toLocaleDateString() }}
              </span>
            </div>
          </div>
          
          <button 
            @click="$emit('complete', task.id)"
            class="opacity-0 group-hover:opacity-100 text-green-600 hover:text-green-700 bg-green-50 hover:bg-green-100 p-1.5 rounded-full transition-all"
            title="Mark Done">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>
      </div>
      
      <div v-if="tasks.length === 0" class="p-8 text-center text-gray-400 text-sm italic">
        No active tasks.
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps(['title', 'color', 'tasks']);
defineEmits(['complete']);
</script>