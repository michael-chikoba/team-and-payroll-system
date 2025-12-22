<template>
  <div
    class="fixed inset-0 bg-gray-900 bg-opacity-75 backdrop-blur-sm flex items-center justify-center z-50 transition-opacity"
    aria-modal="true"
    role="dialog"
  >
    <div class="bg-white rounded-xl shadow-2xl p-8 max-w-lg w-full mx-4 transform transition-all border border-gray-200">
      
      <div class="flex items-start mb-6">
        <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center flex-shrink-0 mr-4">
          <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
        </div>
        <div>
          <h3 class="text-xl font-bold text-gray-900 leading-6">Permanently Delete Business</h3>
          <p class="text-sm text-red-600 mt-1">This action is irreversible.</p>
        </div>
      </div>
      
      <div class="mb-8 space-y-4">
        <p class="text-gray-700 text-base">
          Are you **absolutely sure** you want to delete the business:
          <strong class="font-extrabold text-red-700 block mt-1 text-lg">{{ business.name }}</strong>?
        </p>
        
        <p class="text-sm text-gray-600 font-medium">
          Deleting this business will permanently remove all associated data, including:
        </p>
        <ul class="list-disc list-inside text-sm text-gray-700 ml-4 space-y-1 p-3 bg-red-50 border border-red-200 rounded-lg">
          <li>**All** business information and settings</li>
          <li>**All** employee records and sensitive details</li>
          <li>**All** payroll data, history, and financial logs</li>
          <li>**All** administrator and user access</li>
        </ul>
      </div>

      <div class="flex justify-end space-x-3">
        <button
          @click="$emit('cancel')"
          class="px-6 py-2 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-100 transition duration-150 font-medium focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
        >
          Cancel
        </button>
        <button
          @click="$emit('confirm')"
          class="px-6 py-2 bg-red-600 text-white rounded-xl shadow-lg hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-500 focus:ring-opacity-50 transition transform hover:scale-[1.01] duration-200 font-semibold"
        >
          Yes, Delete Permanently
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
  },
  emits: ['cancel', 'confirm']
}
</script>