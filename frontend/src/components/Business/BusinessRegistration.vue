<!-- resources/js/components/BusinessRegistration.vue -->
<template>
  <div class="max-w-4xl mx-auto p-6">
    <div class="bg-white shadow-lg rounded-lg p-6">
      <h2 class="text-2xl font-bold mb-6 text-gray-800">Register Your Business</h2>
      
      <form @submit.prevent="registerBusiness" class="space-y-6">
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

        <!-- Primary Administrator Information -->
        <div class="border-t pt-6">
          <h3 class="text-lg font-semibold text-gray-800 mb-4">Primary Administrator</h3>
          <p class="text-sm text-gray-600 mb-4">This person will have full administrative access to manage the business.</p>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
              <input
                v-model="form.admin_first_name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
              />
              <div v-if="errors.admin_first_name" class="text-red-500 text-sm mt-1">{{ errors.admin_first_name[0] }}</div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
              <input
                v-model="form.admin_last_name"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
              />
              <div v-if="errors.admin_last_name" class="text-red-500 text-sm mt-1">{{ errors.admin_last_name[0] }}</div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
              <input
                v-model="form.admin_email"
                type="email"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                required
              />
              <div v-if="errors.admin_email" class="text-red-500 text-sm mt-1">{{ errors.admin_email[0] }}</div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
              <input
                v-model="form.admin_phone"
                type="tel"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
              <div v-if="errors.admin_phone" class="text-red-500 text-sm mt-1">{{ errors.admin_phone[0] }}</div>
            </div>
          </div>

          <!-- Use Current User Checkbox -->
          <div class="mt-4">
            <label class="flex items-center">
              <input
                type="checkbox"
                v-model="useCurrentUserAsAdmin"
                @change="toggleCurrentUserAsAdmin"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span class="ml-2 text-sm text-gray-700">Use my information as the primary administrator</span>
            </label>
          </div>
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
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">State *</label>
                <input
                  v-model="form.state"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Postal Code *</label>
                <input
                  v-model="form.postal_code"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                  required
                />
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
            </div>
          </div>
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
            <span v-if="loading">Registering...</span>
            <span v-else>Register Business</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'BusinessRegistration',
  data() {
    return {
      form: {
        // Business fields
        name: '',
        legal_name: '',
        registration_number: '',
        tax_identification_number: '',
        business_type: '',
        industry: '',
        website: '',
        email: '',
        phone: '',
        address_line_1: '',
        address_line_2: '',
        city: '',
        state: '',
        postal_code: '',
        country_id: '',
        currency_code: 'USD',
        pay_period: 'monthly',
        // Admin fields
        admin_first_name: '',
        admin_last_name: '',
        admin_email: '',
        admin_phone: ''
      },
      countries: [],
      loading: false,
      errors: {},
      useCurrentUserAsAdmin: true
    }
  },
  async mounted() {
    await this.fetchCountries();
    this.prefillCurrentUserInfo();
  },
  methods: {
    async fetchCountries() {
      try {
        const response = await axios.get('/api/admin/countries');
        this.countries = response.data.data;
      } catch (error) {
        console.error('Failed to fetch countries:', error);
        this.$notify({
          type: 'error',
          title: 'Error',
          text: 'Failed to load countries list'
        });
      }
    },

    prefillCurrentUserInfo() {
      // Get current user from your auth system
      // This depends on how you store user data - adjust accordingly
      const currentUser = this.$store?.state?.auth?.user || 
                         JSON.parse(localStorage.getItem('user')) || 
                         { first_name: '', last_name: '', email: '', phone: '' };
      
      if (currentUser) {
        this.form.admin_first_name = currentUser.first_name || '';
        this.form.admin_last_name = currentUser.last_name || '';
        this.form.admin_email = currentUser.email || '';
        this.form.admin_phone = currentUser.phone || '';
      }
    },

    toggleCurrentUserAsAdmin() {
      if (this.useCurrentUserAsAdmin) {
        this.prefillCurrentUserInfo();
      } else {
        // Clear admin fields
        this.form.admin_first_name = '';
        this.form.admin_last_name = '';
        this.form.admin_email = '';
        this.form.admin_phone = '';
      }
    },

    async registerBusiness() {
      this.loading = true;
      this.errors = {};

      try {
        // Make sure we're using the correct API endpoint
        const response = await axios.post('/api/admin/businesses', this.form);
        
        this.$emit('success', response.data.data);
        
        this.$notify({
          type: 'success',
          title: 'Success',
          text: 'Business registered successfully!'
        });
      } catch (error) {
        console.error('Business registration error:', error);
        
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors;
          this.$notify({
            type: 'error',
            title: 'Validation Error',
            text: 'Please check the form for errors'
          });
        } else {
          this.$notify({
            type: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Failed to register business'
          });
        }
      } finally {
        this.loading = false;
      }
    }
  }
}
</script>