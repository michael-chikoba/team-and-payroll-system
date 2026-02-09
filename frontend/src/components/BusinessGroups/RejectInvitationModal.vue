<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <form @submit.prevent="handleReject">
          <!-- Header -->
          <div class="bg-red-600 px-6 py-4">
            <h3 class="text-lg font-medium text-white">Reject Invitation</h3>
          </div>

          <!-- Content -->
          <div class="bg-white px-6 py-6">
            <div class="space-y-4">
              <!-- Warning -->
              <div class="bg-yellow-50 p-4 rounded-lg">
                <p class="text-sm text-yellow-800">
                  You are about to reject the invitation to join "{{ invitation.business_group.name }}".
                  This action cannot be undone.
                </p>
              </div>

              <!-- Optional Message -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Reason (Optional)
                </label>
                <textarea
                  v-model="message"
                  rows="3"
                  placeholder="Provide a reason for rejecting..."
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm"
                ></textarea>
              </div>

              <!-- Error Display -->
              <div v-if="error" class="rounded-md bg-red-50 p-4">
                <p class="text-sm text-red-800">{{ error }}</p>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="bg-gray-50 px-6 py-4 flex flex-row-reverse gap-3">
            <button
              type="submit"
              :disabled="rejecting"
              class="inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-sm font-medium text-white hover:bg-red-700 focus:outline-none disabled:opacity-50"
            >
              {{ rejecting ? 'Rejecting...' : 'Reject Invitation' }}
            </button>
            <button
              type="button"
              @click="$emit('close')"
              :disabled="rejecting"
              class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none disabled:opacity-50"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import axios from 'axios'

export default {
  name: 'RejectInvitationModal',
  
  props: {
    invitation: {
      type: Object,
      required: true
    }
  },
  
  emits: ['close', 'rejected'],
  
  setup(props, { emit }) {
    const rejecting = ref(false)
    const message = ref('')
    const error = ref(null)

    const handleReject = async () => {
      rejecting.value = true
      error.value = null

      try {
        const response = await axios.post(
          `/api/business-group-invitations/${props.invitation.id}/reject`,
          { message: message.value }
        )

        if (response.data.success) {
          emit('rejected')
        }
      } catch (err) {
        error.value = err.response?.data?.message || 'Failed to reject invitation'
      } finally {
        rejecting.value = false
      }
    }

    return {
      rejecting,
      message,
      error,
      handleReject
    }
  }
}
</script>