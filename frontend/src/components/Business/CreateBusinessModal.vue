<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>

      <!-- Modal -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
          <!-- Header -->
          <div class="flex items-center justify-between mb-6">
            <div>
              <h3 class="text-lg leading-6 font-medium text-gray-900">
                Business Details
              </h3>
              <p class="mt-1 text-sm text-gray-500">
                ID: {{ business.id }}
              </p>
            </div>
            <button
              @click="$emit('close')"
              class="text-gray-400 hover:text-gray-500 focus:outline-none"
            >
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Content -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Basic Information -->
            <div class="space-y-6">
              <div>
                <h4 class="text-md font-medium text-gray-900 mb-3">Basic Information</h4>
                <dl class="space-y-3">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Business Name</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ business.name }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ business.email }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Type</dt>
                    <dd class="mt-1 text-sm text-gray-900 capitalize">{{ business.type?.replace('_', ' ') }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1">
                      <span :class="[
                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                        business.status === 'active' ? 'bg-green-100 text-green-800' :
                        business.status === 'suspended' ? 'bg-red-100 text-red-800' :
                        'bg-yellow-100 text-yellow-800'
                      ]">
                        {{ business.status?.charAt(0).toUpperCase() + business.status?.slice(1) }}
                      </span>
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Verification</dt>
                    <dd class="mt-1">
                      <span v-if="business.is_verified" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        <CheckCircleIcon class="w-3 h-3 mr-1" />
                        Verified
                      </span>
                      <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        Pending Verification
                      </span>
                    </dd>
                  </div>
                </dl>
              </div>

              <!-- Contact Information -->
              <div>
                <h4 class="text-md font-medium text-gray-900 mb-3">Contact Information</h4>
                <dl class="space-y-3">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Website</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                      <a v-if="business.website" :href="business.website" target="_blank" class="text-blue-600 hover:text-blue-900">
                        {{ business.website }}
                      </a>
                      <span v-else class="text-gray-500">Not provided</span>
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Phone</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ business.phone || 'Not provided' }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Address</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                      <template v-if="business.address">
                        {{ business.address }}, {{ business.city }}, {{ business.state }} {{ business.zip_code }}
                      </template>
                      <span v-else class="text-gray-500">Not provided</span>
                    </dd>
                  </div>
                </dl>
              </div>
            </div>

            <!-- Owner Information & Additional Details -->
            <div class="space-y-6">
              <!-- Owner Information -->
              <div>
                <h4 class="text-md font-medium text-gray-900 mb-3">Owner Information</h4>
                <div class="bg-gray-50 rounded-lg p-4">
                  <div class="flex items-center">
                    <div class="flex-shrink-0">
                      <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                        <UserIcon class="h-6 w-6 text-blue-600" />
                      </div>
                    </div>
                    <div class="ml-3">
                      <div class="text-sm font-medium text-gray-900">
                        {{ business.owner?.first_name }} {{ business.owner?.last_name }}
                      </div>
                      <div class="text-sm text-gray-500">{{ business.owner?.email }}</div>
                      <div class="text-sm text-gray-500">{{ business.owner?.phone }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Business Description -->
              <div>
                <h4 class="text-md font-medium text-gray-900 mb-3">Description</h4>
                <p class="text-sm text-gray-700 bg-gray-50 rounded-lg p-4">
                  {{ business.description || 'No description provided.' }}
                </p>
              </div>

              <!-- System Information -->
              <div>
                <h4 class="text-md font-medium text-gray-900 mb-3">System Information</h4>
                <dl class="space-y-3">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Created At</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ formatDate(business.created_at) }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Updated At</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ formatDate(business.updated_at) }}</dd>
                  </div>
                  <div v-if="business.verified_at">
                    <dt class="text-sm font-medium text-gray-500">Verified At</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ formatDate(business.verified_at) }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Admin Count</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ business.admin_count || 0 }} administrators</dd>
                  </div>
                </dl>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end space-x-3">
            <button
              @click="$emit('close')"
              class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Close
            </button>
            <button
              @click="$emit('edit')"
              class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <PencilSquareIcon class="w-4 h-4 mr-2" />
              Edit Business
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { CheckCircleIcon, UserIcon, PencilSquareIcon } from '@heroicons/vue/24/outline'

export default {
  name: 'BusinessDetailModal',
  props: {
    business: {
      type: Object,
      required: true
    }
  },
  emits: ['close', 'edit'],
  
  setup() {
    const formatDate = (dateString) => {
      return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    return {
      CheckCircleIcon,
      UserIcon,
      PencilSquareIcon,
      formatDate
    }
  }
}
</script>