<template>
  <div class="bg-white overflow-hidden shadow rounded-lg">
    <div class="p-5">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <component
            :is="iconComponent"
            class="h-6 w-6 text-gray-400"
            :class="iconColor"
            aria-hidden="true"
          />
        </div>
        <div class="ml-5 w-0 flex-1">
          <dl>
            <dt class="text-sm font-medium text-gray-500 truncate">
              {{ title }}
            </dt>
            <dd class="flex items-baseline">
              <div class="text-2xl font-semibold text-gray-900">
                {{ value }}{{ suffix }}
              </div>
            </dd>
          </dl>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue'
import {
  ClockIcon,
  CalendarIcon,
  ChartBarIcon,
  CurrencyDollarIcon,
  CheckCircleIcon,
  XCircleIcon,
  DocumentTextIcon
} from '@heroicons/vue/outline'

const icons = {
  ClockIcon,
  CalendarIcon,
  ChartBarIcon,
  CurrencyDollarIcon,
  CheckCircleIcon,
  XCircleIcon,
  DocumentTextIcon
}

const colorClasses = {
  blue: 'text-blue-500',
  green: 'text-green-500',
  red: 'text-red-500',
  yellow: 'text-yellow-500',
  purple: 'text-purple-500',
  pink: 'text-pink-500',
  indigo: 'text-indigo-500'
}

export default {
  name: 'StatCard',
  props: {
    title: {
      type: String,
      required: true
    },
    value: {
      type: [String, Number],
      required: true
    },
    suffix: {
      type: String,
      default: ''
    },
    icon: {
      type: String,
      required: true
    },
    color: {
      type: String,
      default: 'blue'
    }
  },
  setup(props) {
    const iconComponent = computed(() => icons[props.icon])
    const iconColor = computed(() => colorClasses[props.color] || colorClasses.blue)

    return {
      iconComponent,
      iconColor
    }
  }
}
</script>