<template>
  <div class="bg-white border border-gray-200 rounded-lg shadow-sm hover:shadow-md transition-shadow p-6">
    <div class="flex items-start justify-between">
      <div class="flex-1">
        <!-- Group Info -->
        <div class="flex items-center mb-4">
          <div class="h-12 w-12 rounded-lg bg-gradient-to-br from-purple-400 to-indigo-500 flex items-center justify-center">
            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </div>
          <div class="ml-4">
            <h3 class="text-lg font-semibold text-gray-900">
              {{ invitation.business_group.name }}
            </h3>
            <p class="text-sm text-gray-500">
              {{ formatGroupType(invitation.business_group.group_type) }}
            </p>
          </div>
        </div>

        <!-- Invitation Details -->
        <div class="space-y-2 mb-4">
          <div v-if="type === 'received'" class="flex items-center text-sm text-gray-600">
            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            <span>Invited by: <strong>{{ invitation.invited_by.first_name }} {{ invitation.invited_by.last_name }}</strong></span>
          </div>

          <div v-else class="flex items-center text-sm text-gray-600">
            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <span>Invited business: <strong>{{ invitation.invited_business.name }}</strong></span>
          </div>

          <div class="flex items-center text-sm text-gray-600">
            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <span>Proposed role: <strong class="capitalize">{{ invitation.proposed_role }}</strong></span>
          </div>

          <div class="flex items-center text-sm text-gray-600">
            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <span>Expires: <strong>{{ formatDate(invitation.expires_at) }}</strong></span>
          </div>
        </div>

        <!-- Status Badge -->
        <div>
          <span :class="getStatusClass(invitation.status)">
            {{ formatStatus(invitation.status) }}
          </span>
        </div>
      </div>

      <!-- Actions -->
      <div v-if="type === 'received' && invitation.status === 'pending'" class="flex flex-col gap-2 ml-4">
        <button
          @click="$emit('accept', invitation)"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
          Accept
        </button>
        <button
          @click="$emit('reject', invitation)"
          class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
          Reject
        </button>
      </div>

      <div v-else-if="type === 'sent' && invitation.status === 'pending'" class="ml-4">
        <button
          @click="$emit('cancel', invitation)"
          class="inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
          Cancel
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'InvitationCard',
  
  props: {
    invitation: {
      type: Object,
      required: true
    },
    type: {
      type: String,
      required: true,
      validator: (value) => ['received', 'sent'].includes(value)
    }
  },
  
  emits: ['accept', 'reject', 'cancel'],
  
  methods: {
    formatGroupType(type) {
      const types = {
        holding: 'Holding Company',
        franchise: 'Franchise',
        subsidiary: 'Subsidiary',
        partnership: 'Partnership'
      }
      return types[type] || type
    },
    
    formatStatus(status) {
      return status.charAt(0).toUpperCase() + status.slice(1)
    },
    
    getStatusClass(status) {
      const classes = {
        pending: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800',
        accepted: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800',
        rejected: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800',
        cancelled: 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800'
      }
      return classes[status] || classes.pending
    },
    
    formatDate(dateString) {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      })
    }
  }
}
</script>