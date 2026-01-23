<template>
  <span :class="badgeClasses" class="inline-flex items-center gap-1">
    <component :is="priorityIcon" class="w-3 h-3" />
    {{ priorityText }}
  </span>
</template>

<script setup>
import { computed } from 'vue'
import {
  InformationCircleIcon,
  FlagIcon,
  ExclamationTriangleIcon,
  ExclamationCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  priority: String
})

const priorityConfig = {
  low: {
    text: 'Low',
    icon: InformationCircleIcon,
    classes: 'bg-green-100 text-green-800 border border-green-200'
  },
  medium: {
    text: 'Medium',
    icon: FlagIcon,
    classes: 'bg-blue-100 text-blue-800 border border-blue-200'
  },
  high: {
    text: 'High',
    icon: ExclamationTriangleIcon,
    classes: 'bg-yellow-100 text-yellow-800 border border-yellow-200'
  },
  critical: {
    text: 'Critical',
    icon: ExclamationCircleIcon,
    classes: 'bg-red-100 text-red-800 border border-red-200'
  }
}

const priorityText = computed(() => priorityConfig[props.priority]?.text || props.priority)
const priorityIcon = computed(() => priorityConfig[props.priority]?.icon || InformationCircleIcon)
const badgeClasses = computed(() => 
  `px-3 py-1 rounded-full text-xs font-semibold ${priorityConfig[props.priority]?.classes || 'bg-gray-100 text-gray-800 border border-gray-200'}`
)
</script>