<!-- resources/js/components/Business/BusinessEdit.vue -->
<template>
  <div class="max-w-4xl mx-auto p-6">
    <div class="bg-white shadow-lg rounded-lg p-6">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Edit Business</h2>
        <button
          @click="$emit('cancel')"
          class="px-4 py-2 text-gray-600 hover:text-gray-800 focus:outline-none transition-colors duration-200"
        >
          ← Back to List
        </button>
      </div>
      
      <form @submit.prevent="updateBusiness" class="space-y-6">
        <!-- Business Basic Information -->
        <div>
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Business Information</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Business Name *</label>
              <input
                v-model="form.name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
              />
              <div v-if="errors.name" class="text-red-500 text-sm mt-1">{{ errors.name[0] }}</div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Legal Name *</label>
              <input
                v-model="form.legal_name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
              />
              <div v-if="errors.legal_name" class="text-red-500 text-sm mt-1">{{ errors.legal_name[0] }}</div>
            </div>
          </div>
        </div>

        <!-- Registration Numbers -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Registration Number</label>
            <input
              v-model="form.registration_number"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Tax ID Number</label>
            <input
              v-model="form.tax_identification_number"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>

        <!-- Business Type & Industry -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Business Type *</label>
            <select
              v-model="form.business_type"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            >
              <option value="">Select Business Type</option>
              <option value="sole_proprietorship">Sole Proprietorship</option>
              <option value="partnership">Partnership</option>
              <option value="corporation">Corporation</option>
              <option value="llc">LLC</option>
            </select>
            <div v-if="errors.business_type" class="text-red-500 text-sm mt-1">{{ errors.business_type[0] }}</div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Industry</label>
            <input
              v-model="form.industry"
              type="text"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
        </div>

        <!-- Contact Information -->
        <div>
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Business Contact Information</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Business Email *</label>
              <input
                v-model="form.email"
                type="email"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
              />
              <div v-if="errors.email" class="text-red-500 text-sm mt-1">{{ errors.email[0] }}</div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Business Phone</label>
              <input
                v-model="form.phone"
                type="tel"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>
        </div>

        <!-- Website -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Website</label>
          <input
            v-model="form.website"
            type="url"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
            placeholder="https://example.com"
          />
          <div v-if="errors.website" class="text-red-500 text-sm mt-1">{{ errors.website[0] }}</div>
        </div>

        <!-- Address -->
        <div class="border-t pt-6">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Business Address</h3>
          
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 1 *</label>
              <input
                v-model="form.address_line_1"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
              />
              <div v-if="errors.address_line_1" class="text-red-500 text-sm mt-1">{{ errors.address_line_1[0] }}</div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Address Line 2</label>
              <input
                v-model="form.address_line_2"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">City *</label>
                <input
                  v-model="form.city"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                />
                <div v-if="errors.city" class="text-red-500 text-sm mt-1">{{ errors.city[0] }}</div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">State *</label>
                <input
                  v-model="form.state"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                />
                <div v-if="errors.state" class="text-red-500 text-sm mt-1">{{ errors.state[0] }}</div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code *</label>
                <input
                  v-model="form.postal_code"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                />
                <div v-if="errors.postal_code" class="text-red-500 text-sm mt-1">{{ errors.postal_code[0] }}</div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Country *</label>
                <select
                  v-model="form.country_id"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                >
                  <option value="">Select Country</option>
                  <option v-for="country in countries" :key="country.id" :value="country.id">
                    {{ country.name }} ({{ country.currency_code }})
                  </option>
                </select>
                <div v-if="errors.country_id" class="text-red-500 text-sm mt-1">{{ errors.country_id[0] }}</div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Currency *</label>
                <select
                  v-model="form.currency_code"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                >
                  <option value="USD">USD - US Dollar</option>
                  <option value="EUR">EUR - Euro</option>
                  <option value="GBP">GBP - British Pound</option>
                  <option value="ZMW">ZMW - Zambian Kwacha</option>
                  <!-- Add more currencies as needed -->
                </select>
                <div v-if="errors.currency_code" class="text-red-500 text-sm mt-1">{{ errors.currency_code[0] }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Payroll Settings -->
        <div class="border-t pt-6">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Payroll Settings</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Pay Period *</label>
              <select
                v-model="form.pay_period"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
              >
                <option value="monthly">Monthly</option>
                <option value="semi-monthly">Semi-Monthly</option>
                <option value="bi-weekly">Bi-Weekly</option>
                <option value="weekly">Weekly</option>
              </select>
              <div v-if="errors.pay_period" class="text-red-500 text-sm mt-1">{{ errors.pay_period[0] }}</div>
            </div>
          </div>
        </div>

        <!-- Current Administrators Display (Read-only) -->
        <div class="border-t pt-6" v-if="business.admins && business.admins.length > 0">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Current Administrators</h3>
          <div class="bg-gray-50 rounded-lg p-4">
            <div v-for="admin in business.admins" :key="admin.id" class="flex items-center justify-between py-2 border-b border-gray-200 last:border-b-0">
              <div>
                <p class="font-medium text-gray-900">
                  {{ admin.user?.first_name }} {{ admin.user?.last_name }}
                </p>
                <p class="text-sm text-gray-600">{{ admin.user?.email }}</p>
                <span class="inline-block px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full mt-1">
                  {{ admin.is_primary ? 'Primary Admin' : 'Admin' }}
                </span>
              </div>
            </div>
          </div>
          <p class="text-sm text-gray-500 mt-2">
            Note: Administrator information cannot be edited here. Please contact system administrator to manage user access.
          </p>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end space-x-4 pt-6 border-t">
          <button
            type="button"
            @click="$emit('cancel')"
            class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors duration-200"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="loading"
            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 transition-colors duration-200"
          >
            <span v-if="loading">Updating...</span>
            <span v-else>Update Business</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'BusinessEdit',
  props: {
    business: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      form: { 
        // Business fields
        name: this.business.name || '',
        legal_name: this.business.legal_name || '',
        registration_number: this.business.registration_number || '',
        tax_identification_number: this.business.tax_identification_number || '',
        business_type: this.business.business_type || '',
        industry: this.business.industry || '',
        website: this.business.website || '',
        email: this.business.email || '',
        phone: this.business.phone || '',
        address_line_1: this.business.address_line_1 || '',
        address_line_2: this.business.address_line_2 || '',
        city: this.business.city || '',
        state: this.business.state || '',
        postal_code: this.business.postal_code || '',
        country_id: this.business.country_id || '',
        currency_code: this.business.currency_code || 'USD',
        pay_period: this.business.pay_period || 'monthly'
      },
      countries: [],
      loading: false,
      errors: {}
    }
  },
  async mounted() {
    await this.fetchCountries();
  },
  methods: {
    async fetchCountries() {
      try {
        const response = await axios.get('/api/admin/countries');
        this.countries = response.data?.data || response.data || [];
      } catch (error) {
        console.error('Failed to fetch countries:', error);
        this.countries = [];
        // Don't show error for managers who might not have access
      }
    },

    async updateBusiness() {
      this.loading = true;
      this.errors = {};

      try {
        // Use the correct API endpoint - adjust based on your routes
        const response = await axios.put(`/api/admin/businesses/${this.business.id}`, this.form);
        
        this.$emit('success', response.data.data);
        
        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Business updated successfully!'
        });
      } catch (error) {
        console.error('Business update error:', error);
        
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors;
          this.$notify({
            type: 'error',
            title: 'Validation Error',
            text: 'Please check the form for errors'
          });
        } else if (error.response?.status === 403) {
          this.$notify({
            type: 'error',
            title: 'Permission Denied',
            text: 'You are not authorized to update this business'
          });
        } else {
          this.$notify({
            type: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to update business'
          });
        }
      } finally {
        this.loading = false;
      }
    }
  }
}
</script>