<template>
  <span :class="badgeClasses" class="inline-flex items-center gap-1">
    <component v-if="statusIcon" :is="statusIcon" class="w-3 h-3" />
    {{ statusText }}
  </span>
</template>

<script setup>
import { computed } from 'vue'
import {
  ClockIcon,
  CheckCircleIcon,
  XCircleIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
  status: String
})

const statusConfig = {
  pending: {
    text: 'Pending',
    icon: ClockIcon,
    classes: 'bg-yellow-100 text-yellow-800 border border-yellow-200'
  },
  approved: {
    text: 'Approved',
    icon: CheckCircleIcon,
    classes: 'bg-green-100 text-green-800 border border-green-200'
  },
  rejected: {
    text: 'Rejected',
    icon: XCircleIcon,
    classes: 'bg-red-100 text-red-800 border border-red-200'
  },
  in_progress: {
    text: 'In Progress',
    icon: ArrowPathIcon,
    classes: 'bg-blue-100 text-blue-800 border border-blue-200'
  }
}

const statusText = computed(() => statusConfig[props.status]?.text || props.status)
const statusIcon = computed(() => statusConfig[props.status]?.icon)
const badgeClasses = computed(() => 
  `px-3 py-1 rounded-full text-xs font-semibold ${statusConfig[props.status]?.classes || 'bg-gray-100 text-gray-800 border border-gray-200'}`
)
</script>