
<!-- DeleteConfirmationModal.vue - New Component -->
<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
      <div class="flex items-center mb-4">
        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
          <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <div>
          <h3 class="text-lg font-semibold text-gray-900">Delete Business</h3>
          <p class="text-sm text-gray-600">This action cannot be undone</p>
        </div>
      </div>
      
      <div class="mb-6">
        <p class="text-gray-700 mb-2">
          Are you sure you want to delete <strong>{{ business.name }}</strong>?
        </p>
        <p class="text-sm text-gray-600">
          This will permanently remove:
        </p>
        <ul class="list-disc list-inside text-sm text-gray-600 mt-2 space-y-1">
          <li>All business information</li>
          <li>Employee records</li>
          <li>Payroll data</li>
          <li>Admin access</li>
        </ul>
      </div>

      <div class="flex justify-end space-x-3">
        <button
          @click="$emit('cancel')"
          class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500"
        >
          Cancel
        </button>
        <button
          @click="$emit('confirm')"
          class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
        >
          Delete Business
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'DeleteConfirmationModal',
  props: {
    business: {
      type: Object,
      required: true
    }
  }
}
</script>