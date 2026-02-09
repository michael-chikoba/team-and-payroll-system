<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="close"></div>

      <!-- Modal -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <div class="sm:flex sm:items-start">
            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
              <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                Edit Business
              </h3>
              
              <form @submit.prevent="handleSubmit">
                <div class="space-y-4">
                  <!-- Business Name -->
                  <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                      Business Name *
                    </label>
                    <input
                      type="text"
                      id="name"
                      v-model="form.name"
                      required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    />
                  </div>

                  <!-- Business Email -->
                  <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">
                      Business Email *
                    </label>
                    <input
                      type="email"
                      id="email"
                      v-model="form.email"
                      required
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    />
                  </div>

                  <!-- Business Type -->
                  <div>
                    <label for="type" class="block text-sm font-medium text-gray-700">
                      Business Type
                    </label>
                    <select
                      id="type"
                      v-model="form.type"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    >
                      <option value="corporate">Corporate</option>
                      <option value="small_business">Small Business</option>
                      <option value="startup">Startup</option>
                      <option value="individual">Individual</option>
                    </select>
                  </div>

                  <!-- Business Status -->
                  <div>
                    <label for="status" class="block text-sm font-medium text-gray-700">
                      Status
                    </label>
                    <select
                      id="status"
                      v-model="form.status"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    >
                      <option value="active">Active</option>
                      <option value="suspended">Suspended</option>
                      <option value="pending">Pending</option>
                    </select>
                  </div>

                  <!-- Website -->
                  <div>
                    <label for="website" class="block text-sm font-medium text-gray-700">
                      Website
                    </label>
                    <input
                      type="url"
                      id="website"
                      v-model="form.website"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    />
                  </div>

                  <!-- Phone -->
                  <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700">
                      Phone
                    </label>
                    <input
                      type="tel"
                      id="phone"
                      v-model="form.phone"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    />
                  </div>

                  <!-- Address -->
                  <div>
                    <label for="address" class="block text-sm font-medium text-gray-700">
                      Address
                    </label>
                    <input
                      type="text"
                      id="address"
                      v-model="form.address"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    />
                  </div>

                  <!-- City -->
                  <div>
                    <label for="city" class="block text-sm font-medium text-gray-700">
                      City
                    </label>
                    <input
                      type="text"
                      id="city"
                      v-model="form.city"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    />
                  </div>

                  <!-- State -->
                  <div>
                    <label for="state" class="block text-sm font-medium text-gray-700">
                      State
                    </label>
                    <input
                      type="text"
                      id="state"
                      v-model="form.state"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    />
                  </div>

                  <!-- Zip Code -->
                  <div>
                    <label for="zip_code" class="block text-sm font-medium text-gray-700">
                      Zip Code
                    </label>
                    <input
                      type="text"
                      id="zip_code"
                      v-model="form.zip_code"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    />
                  </div>

                  <!-- Description -->
                  <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">
                      Description
                    </label>
                    <textarea
                      id="description"
                      v-model="form.description"
                      rows="3"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    ></textarea>
                  </div>

                  <!-- Verification Status -->
                  <div class="flex items-center justify-between pt-4 border-t">
                    <label for="is_verified" class="text-sm font-medium text-gray-700">
                      Verified Status
                    </label>
                    <input
                      type="checkbox"
                      id="is_verified"
                      v-model="form.is_verified"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                    />
                  </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                  <button
                    type="button"
                    @click="close"
                    class="inline-flex justify-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    :disabled="loading"
                    class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                  >
                    <span v-if="loading">Updating...</span>
                    <span v-else>Update Business</span>
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'BusinessEditModal',
  props: {
    show: {
      type: Boolean,
      default: false
    },
    business: {
      type: Object,
      required: true
    }
  },
  emits: ['close', 'updated'],
  
  data() {
    return {
      loading: false,
      form: {
        name: '',
        email: '',
        type: 'small_business',
        status: 'active',
        website: '',
        phone: '',
        address: '',
        city: '',
        state: '',
        zip_code: '',
        description: '',
        is_verified: false
      }
    }
  },
  
  watch: {
    business: {
      immediate: true,
      handler(newBusiness) {
        if (newBusiness) {
          this.form = {
            name: newBusiness.name || '',
            email: newBusiness.email || '',
            type: newBusiness.type || 'small_business',
            status: newBusiness.status || 'active',
            website: newBusiness.website || '',
            phone: newBusiness.phone || '',
            address: newBusiness.address || '',
            city: newBusiness.city || '',
            state: newBusiness.state || '',
            zip_code: newBusiness.zip_code || '',
            description: newBusiness.description || '',
            is_verified: newBusiness.is_verified || false
          }
        }
      }
    }
  },
  
  methods: {
    close() {
      this.$emit('close')
    },
    
    async handleSubmit() {
      this.loading = true
      try {
        const response = await axios.put(`/api/admin/businesses/${this.business.id}`, this.form)
        
        if (response.data.success) {
          this.$emit('updated', response.data.data)
          this.close()
        }
      } catch (error) {
        console.error('Error updating business:', error)
        alert(error.response?.data?.message || 'Failed to update business')
      } finally {
        this.loading = false
      }
    }
  }
}
</script>