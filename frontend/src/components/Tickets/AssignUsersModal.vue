<template>
  <span :class="badgeClasses">
    {{ priorityLabel }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  priority: {
    type: String,
    required: true
  },
  size: {
    type: String,
    default: 'normal' // 'small', 'normal', 'large'
  }
})

const priorityLabels = {
  low: 'Low',
  medium: 'Medium',
  high: 'High',
  critical: 'Critical'
}

const priorityColors = {
  low: 'bg-green-100 text-green-800',
  medium: 'bg-blue-100 text-blue-800',
  high: 'bg-orange-100 text-orange-800',
  critical: 'bg-red-100 text-red-800'
}

const sizeClasses = {
  small: 'px-2 py-0.5 text-xs',
  normal: 'px-3 py-1 text-sm',
  large: 'px-4 py-2 text-base'
}

const priorityLabel = computed(() => {
  return priorityLabels[props.priority] || props.priority
})

const badgeClasses = computed(() => {
  const baseClasses = 'inline-flex items-center rounded-full font-medium'
  const colorClass = priorityColors[props.priority] || 'bg-gray-100 text-gray-800'
  const sizeClass = sizeClasses[props.size]
  
  return `${baseClasses} ${colorClass} ${sizeClass}`
})
</script>