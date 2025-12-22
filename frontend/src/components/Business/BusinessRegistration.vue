<template>
  <div class="max-w-4xl mx-auto py-4">
    <div class="bg-white shadow-2xl rounded-2xl p-8 border border-gray-100">
      <h2 class="text-3xl font-bold mb-8 text-gray-900 tracking-tight">
        🚀 Register Your New Business
      </h2>
      
      <form @submit.prevent="registerBusiness" class="space-y-10">
        
        <div class="space-y-6 pb-6 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-indigo-600 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5M12 7h.01"></path></svg>
            1. Business Details
          </h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Business Name *</label>
              <input
                id="name"
                v-model="form.name"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                required
              />
              <div v-if="errors.name" class="text-red-500 text-sm mt-1">{{ errors.name[0] }}</div>
            </div>
            <div>
              <label for="legal_name" class="block text-sm font-medium text-gray-700 mb-1">Legal Name *</label>
              <input
                id="legal_name"
                v-model="form.legal_name"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                required
              />
              <div v-if="errors.legal_name" class="text-red-500 text-sm mt-1">{{ errors.legal_name[0] }}</div>
            </div>
            <div>
              <label for="business_type" class="block text-sm font-medium text-gray-700 mb-1">Business Type *</label>
              <select
                id="business_type"
                v-model="form.business_type"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 bg-white"
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
              <label for="industry" class="block text-sm font-medium text-gray-700 mb-1">Industry (e.g., Software, Retail)</label>
              <input
                id="industry"
                v-model="form.industry"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
              />
            </div>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Business Email *</label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                required
              />
            </div>
            <div>
              <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Business Phone</label>
              <input
                id="phone"
                v-model="form.phone"
                type="tel"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
              />
            </div>
          </div>
        </div>
        <div class="space-y-6 pb-6 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-indigo-600 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            2. Location & Finance
          </h3>
          
          <div class="space-y-4">
            <div>
              <label for="address_line_1" class="block text-sm font-medium text-gray-700 mb-1">Address Line 1 *</label>
              <input
                id="address_line_1"
                v-model="form.address_line_1"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                required
              />
            </div>
            <div>
              <label for="address_line_2" class="block text-sm font-medium text-gray-700 mb-1">Address Line 2 (Optional)</label>
              <input
                id="address_line_2"
                v-model="form.address_line_2"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
              />
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City *</label>
                <input
                  id="city"
                  v-model="form.city"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                  required
                />
              </div>
              <div>
                <label for="state" class="block text-sm font-medium text-gray-700 mb-1">State/Region *</label>
                <input
                  id="state"
                  v-model="form.state"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                  required
                />
              </div>
              <div>
                <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Postal Code *</label>
                <input
                  id="postal_code"
                  v-model="form.postal_code"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                  required
                />
              </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="country_id" class="block text-sm font-medium text-gray-700 mb-1">Country *</label>
                <select
                  id="country_id"
                  v-model="form.country_id"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 bg-white"
                  required
                >
                  <option value="">Select Country</option>
                  <option v-for="country in countries" :key="country.id" :value="country.id">
                    {{ country.name }}
                  </option>
                </select>
              </div>
              <div>
                <label for="currency_code" class="block text-sm font-medium text-gray-700 mb-1">Currency *</label>
                <select
                  id="currency_code"
                  v-model="form.currency_code"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 bg-white"
                  required
                >
                  <option value="">Select Currency</option>
                  <optgroup label="Global Currencies">
                    <option value="USD">USD - US Dollar</option>
                    <option value="EUR">EUR - Euro</option>
                    <option value="GBP">GBP - British Pound</option>
                    <option value="JPY">JPY - Japanese Yen</option>
                    <option value="CNY">CNY - Chinese Yuan</option>
                    <option value="CAD">CAD - Canadian Dollar</option>
                    <option value="AUD">AUD - Australian Dollar</option>
                    <option value="CHF">CHF - Swiss Franc</option>
                  </optgroup>
                  <optgroup label="African Currencies">
                    <option value="ZMW">ZMW - Zambian Kwacha</option>
                    <option value="ZAR">ZAR - South African Rand</option>
                    <option value="NGN">NGN - Nigerian Naira</option>
                    <option value="KES">KES - Kenyan Shilling</option>
                    <option value="GHS">GHS - Ghanaian Cedi</option>
                    <option value="EGP">EGP - Egyptian Pound</option>
                    <option value="ETB">ETB - Ethiopian Birr</option>
                    <option value="DZD">DZD - Algerian Dinar</option>
                    <option value="MAD">MAD - Moroccan Dirham</option>
                    <option value="TZS">TZS - Tanzanian Shilling</option>
                    <option value="UGX">UGX - Ugandan Shilling</option>
                    <option value="XOF">XOF - West African CFA Franc</option>
                    <option value="XAF">XAF - Central African CFA Franc</option>
                    <option value="MZN">MZN - Mozambican Metical</option>
                    <option value="MWK">MWK - Malawian Kwacha</option>
                    <option value="RWF">RWF - Rwandan Franc</option>
                    <option value="BWP">BWP - Botswana Pula</option>
                    <option value="NAD">NAD - Namibian Dollar</option>
                    <option value="LSL">LSL - Lesotho Loti</option>
                    <option value="SZL">SZL - Swazi Lilangeni</option>
                    <option value="SDG">SDG - Sudanese Pound</option>
                    <option value="TND">TND - Tunisian Dinar</option>
                    <option value="LYD">LYD - Libyan Dinar</option>
                    <option value="DJF">DJF - Djiboutian Franc</option>
                    <option value="MUR">MUR - Mauritian Rupee</option>
                    <option value="SCR">SCR - Seychellois Rupee</option>
                    <option value="BIF">BIF - Burundian Franc</option>
                    <option value="CDF">CDF - Congolese Franc</option>
                    <option value="GMD">GMD - Gambian Dalasi</option>
                    <option value="SLL">SLL - Sierra Leonean Leone</option>
                    <option value="LRD">LRD - Liberian Dollar</option>
                    <option value="GNF">GNF - Guinean Franc</option>
                    <option value="SSP">SSP - South Sudanese Pound</option>
                    <option value="MRU">MRU - Mauritanian Ouguiya</option>
                    <option value="STN">STN - São Tomé and Príncipe Dobra</option>
                    <option value="AOA">AOA - Angolan Kwanza</option>
                    <option value="ZWL">ZWL - Zimbabwean Dollar</option>
                    <option value="SOS">SOS - Somali Shilling</option>
                    <option value="KMF">KMF - Comorian Franc</option>
                    <option value="ERN">ERN - Eritrean Nakfa</option>
                    <option value="MGA">MGA - Malagasy Ariary</option>
                  </optgroup>
                  <optgroup label="Other Currencies">
                    <option value="INR">INR - Indian Rupee</option>
                    <option value="PKR">PKR - Pakistani Rupee</option>
                    <option value="BDT">BDT - Bangladeshi Taka</option>
                    <option value="THB">THB - Thai Baht</option>
                    <option value="IDR">IDR - Indonesian Rupiah</option>
                    <option value="PHP">PHP - Philippine Peso</option>
                    <option value="VND">VND - Vietnamese Dong</option>
                    <option value="KRW">KRW - South Korean Won</option>
                    <option value="SGD">SGD - Singapore Dollar</option>
                    <option value="MYR">MYR - Malaysian Ringgit</option>
                    <option value="NZD">NZD - New Zealand Dollar</option>
                    <option value="BRL">BRL - Brazilian Real</option>
                    <option value="MXN">MXN - Mexican Peso</option>
                    <option value="ARS">ARS - Argentine Peso</option>
                    <option value="CLP">CLP - Chilean Peso</option>
                    <option value="COP">COP - Colombian Peso</option>
                    <option value="PEN">PEN - Peruvian Sol</option>
                    <option value="UYU">UYU - Uruguayan Peso</option>
                    <option value="BOB">BOB - Bolivian Boliviano</option>
                  </optgroup>
                </select>
                <p v-if="selectedCurrency" class="text-xs text-gray-500 mt-1">
                  <span class="font-medium">{{ selectedCurrency.name }}</span> 
                  <span v-if="selectedCurrency.symbol">(Symbol: {{ selectedCurrency.symbol }})</span>
                </p>
              </div>
            </div>
          </div>
        </div>
        <div class="space-y-6 pb-6 border-b border-gray-200">
          <h3 class="text-xl font-semibold text-indigo-600 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            3. Registration & Tax
          </h3>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="registration_number" class="block text-sm font-medium text-gray-700 mb-1">Registration Number (Company/EIN)</label>
              <input
                id="registration_number"
                v-model="form.registration_number"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
              />
            </div>
            <div>
              <label for="tax_identification_number" class="block text-sm font-medium text-gray-700 mb-1">Tax ID Number (VAT/TIN)</label>
              <input
                id="tax_identification_number"
                v-model="form.tax_identification_number"
                type="text"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
              />
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
            <div>
              <h4 class="text-base font-semibold text-gray-800 mb-2">Payroll Settings</h4>
              <label for="pay_period" class="block text-sm font-medium text-gray-700 mb-1">Default Pay Period *</label>
              <select
                id="pay_period"
                v-model="form.pay_period"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 bg-white"
                required
              >
                <option value="monthly">Monthly</option>
                <option value="semi-monthly">Semi-Monthly (Twice a month)</option>
                <option value="bi-weekly">Bi-Weekly (Every two weeks)</option>
                <option value="weekly">Weekly</option>
              </select>
            </div>
          </div>
        </div>
        
        <div class="space-y-6">
          <h3 class="text-xl font-semibold text-indigo-600 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.935 13.935 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            4. Primary Administrator
          </h3>
          <p class="text-sm text-gray-600">This person will be the **initial owner** with full access rights.</p>
          
          <div class="mt-4">
            <label class="flex items-center p-3 bg-indigo-50 border border-indigo-200 rounded-lg cursor-pointer hover:bg-indigo-100 transition duration-150">
              <input
                type="checkbox"
                v-model="useCurrentUserAsAdmin"
                @change="toggleCurrentUserAsAdmin"
                class="h-5 w-5 rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
              />
              <span class="ml-3 text-sm font-medium text-indigo-800">Use my information as the primary administrator (Recommended)</span>
            </label>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="admin_first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
              <input
                id="admin_first_name"
                v-model="form.admin_first_name"
                type="text"
                :disabled="useCurrentUserAsAdmin"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 disabled:bg-gray-100 disabled:text-gray-500"
                required
              />
              <div v-if="errors.admin_first_name" class="text-red-500 text-sm mt-1">{{ errors.admin_first_name[0] }}</div>
            </div>
            <div>
              <label for="admin_last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
              <input
                id="admin_last_name"
                v-model="form.admin_last_name"
                type="text"
                :disabled="useCurrentUserAsAdmin"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 disabled:bg-gray-100 disabled:text-gray-500"
                required
              />
              <div v-if="errors.admin_last_name" class="text-red-500 text-sm mt-1">{{ errors.admin_last_name[0] }}</div>
            </div>
            <div>
              <label for="admin_email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
              <input
                id="admin_email"
                v-model="form.admin_email"
                type="email"
                :disabled="useCurrentUserAsAdmin"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 disabled:bg-gray-100 disabled:text-gray-500"
                required
              />
              <div v-if="errors.admin_email" class="text-red-500 text-sm mt-1">{{ errors.admin_email[0] }}</div>
            </div>
            <div>
              <label for="admin_phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
              <input
                id="admin_phone"
                v-model="form.admin_phone"
                type="tel"
                :disabled="useCurrentUserAsAdmin"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 disabled:bg-gray-100 disabled:text-gray-500"
              />
              <div v-if="errors.admin_phone" class="text-red-500 text-sm mt-1">{{ errors.admin_phone[0] }}</div>
            </div>
          </div>
        </div>
        <div class="flex justify-end space-x-4 pt-6">
          <button
            type="button"
            @click="$emit('cancel')"
            class="px-6 py-2 border border-gray-300 rounded-xl text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 transition duration-200 font-medium"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="loading"
            class="px-6 py-2 bg-indigo-600 text-white rounded-xl shadow-lg hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-500 focus:ring-opacity-50 transition transform hover:scale-[1.01] duration-200 disabled:opacity-50 disabled:shadow-none font-semibold"
          >
            <span v-if="loading" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
              Registering...
            </span>
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
  emits: ['cancel', 'success'],
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
        currency_code: '',
        pay_period: 'monthly',
        // Admin fields
        admin_first_name: '',
        admin_last_name: '',
        admin_email: '',
        admin_phone: ''
      },
      countries: [],
      currencies: [
        // Global Currencies
        { code: 'USD', name: 'US Dollar', symbol: '$', group: 'global' },
        { code: 'EUR', name: 'Euro', symbol: '€', group: 'global' },
        { code: 'GBP', name: 'British Pound', symbol: '£', group: 'global' },
        { code: 'JPY', name: 'Japanese Yen', symbol: '¥', group: 'global' },
        { code: 'CNY', name: 'Chinese Yuan', symbol: '¥', group: 'global' },
        { code: 'CAD', name: 'Canadian Dollar', symbol: '$', group: 'global' },
        { code: 'AUD', name: 'Australian Dollar', symbol: '$', group: 'global' },
        { code: 'CHF', name: 'Swiss Franc', symbol: 'CHF', group: 'global' },
        
        // African Currencies
        { code: 'ZMW', name: 'Zambian Kwacha', symbol: 'K', group: 'africa' },
        { code: 'ZAR', name: 'South African Rand', symbol: 'R', group: 'africa' },
        { code: 'NGN', name: 'Nigerian Naira', symbol: '₦', group: 'africa' },
        { code: 'KES', name: 'Kenyan Shilling', symbol: 'KSh', group: 'africa' },
        { code: 'GHS', name: 'Ghanaian Cedi', symbol: 'GH₵', group: 'africa' },
        { code: 'EGP', name: 'Egyptian Pound', symbol: 'E£', group: 'africa' },
        { code: 'ETB', name: 'Ethiopian Birr', symbol: 'Br', group: 'africa' },
        { code: 'DZD', name: 'Algerian Dinar', symbol: 'د.ج', group: 'africa' },
        { code: 'MAD', name: 'Moroccan Dirham', symbol: 'د.م.', group: 'africa' },
        { code: 'TZS', name: 'Tanzanian Shilling', symbol: 'TSh', group: 'africa' },
        { code: 'UGX', name: 'Ugandan Shilling', symbol: 'USh', group: 'africa' },
        { code: 'XOF', name: 'West African CFA Franc', symbol: 'CFA', group: 'africa' },
        { code: 'XAF', name: 'Central African CFA Franc', symbol: 'FCFA', group: 'africa' },
        { code: 'MZN', name: 'Mozambican Metical', symbol: 'MT', group: 'africa' },
        { code: 'MWK', name: 'Malawian Kwacha', symbol: 'MK', group: 'africa' },
        { code: 'RWF', name: 'Rwandan Franc', symbol: 'RF', group: 'africa' },
        { code: 'BWP', name: 'Botswana Pula', symbol: 'P', group: 'africa' },
        { code: 'NAD', name: 'Namibian Dollar', symbol: '$', group: 'africa' },
        { code: 'LSL', name: 'Lesotho Loti', symbol: 'L', group: 'africa' },
        { code: 'SZL', name: 'Swazi Lilangeni', symbol: 'E', group: 'africa' },
        { code: 'SDG', name: 'Sudanese Pound', symbol: 'SDG', group: 'africa' },
        { code: 'TND', name: 'Tunisian Dinar', symbol: 'د.ت', group: 'africa' },
        { code: 'LYD', name: 'Libyan Dinar', symbol: 'ل.د', group: 'africa' },
        { code: 'DJF', name: 'Djiboutian Franc', symbol: 'Fdj', group: 'africa' },
        { code: 'MUR', name: 'Mauritian Rupee', symbol: '₨', group: 'africa' },
        { code: 'SCR', name: 'Seychellois Rupee', symbol: '₨', group: 'africa' },
        { code: 'BIF', name: 'Burundian Franc', symbol: 'FBu', group: 'africa' },
        { code: 'CDF', name: 'Congolese Franc', symbol: 'FC', group: 'africa' },
        { code: 'GMD', name: 'Gambian Dalasi', symbol: 'D', group: 'africa' },
        { code: 'SLL', name: 'Sierra Leonean Leone', symbol: 'Le', group: 'africa' },
        { code: 'LRD', name: 'Liberian Dollar', symbol: '$', group: 'africa' },
        { code: 'GNF', name: 'Guinean Franc', symbol: 'FG', group: 'africa' },
        { code: 'SSP', name: 'South Sudanese Pound', symbol: '£', group: 'africa' },
        { code: 'MRU', name: 'Mauritanian Ouguiya', symbol: 'UM', group: 'africa' },
        { code: 'STN', name: 'São Tomé and Príncipe Dobra', symbol: 'Db', group: 'africa' },
        { code: 'AOA', name: 'Angolan Kwanza', symbol: 'Kz', group: 'africa' },
        { code: 'ZWL', name: 'Zimbabwean Dollar', symbol: '$', group: 'africa' },
        { code: 'SOS', name: 'Somali Shilling', symbol: 'Sh.So.', group: 'africa' },
        { code: 'KMF', name: 'Comorian Franc', symbol: 'CF', group: 'africa' },
        { code: 'ERN', name: 'Eritrean Nakfa', symbol: 'Nfk', group: 'africa' },
        { code: 'MGA', name: 'Malagasy Ariary', symbol: 'Ar', group: 'africa' },
        
        // Other Currencies
        { code: 'INR', name: 'Indian Rupee', symbol: '₹', group: 'other' },
        { code: 'PKR', name: 'Pakistani Rupee', symbol: '₨', group: 'other' },
        { code: 'BDT', name: 'Bangladeshi Taka', symbol: '৳', group: 'other' },
        { code: 'THB', name: 'Thai Baht', symbol: '฿', group: 'other' },
        { code: 'IDR', name: 'Indonesian Rupiah', symbol: 'Rp', group: 'other' },
        { code: 'PHP', name: 'Philippine Peso', symbol: '₱', group: 'other' },
        { code: 'VND', name: 'Vietnamese Dong', symbol: '₫', group: 'other' },
        { code: 'KRW', name: 'South Korean Won', symbol: '₩', group: 'other' },
        { code: 'SGD', name: 'Singapore Dollar', symbol: '$', group: 'other' },
        { code: 'MYR', name: 'Malaysian Ringgit', symbol: 'RM', group: 'other' },
        { code: 'NZD', name: 'New Zealand Dollar', symbol: '$', group: 'other' },
        { code: 'BRL', name: 'Brazilian Real', symbol: 'R$', group: 'other' },
        { code: 'MXN', name: 'Mexican Peso', symbol: '$', group: 'other' },
        { code: 'ARS', name: 'Argentine Peso', symbol: '$', group: 'other' },
        { code: 'CLP', name: 'Chilean Peso', symbol: '$', group: 'other' },
        { code: 'COP', name: 'Colombian Peso', symbol: '$', group: 'other' },
        { code: 'PEN', name: 'Peruvian Sol', symbol: 'S/', group: 'other' },
        { code: 'UYU', name: 'Uruguayan Peso', symbol: '$', group: 'other' },
        { code: 'BOB', name: 'Bolivian Boliviano', symbol: 'Bs', group: 'other' }
      ],
      loading: false,
      errors: {},
      useCurrentUserAsAdmin: true
    }
  },
  computed: {
    selectedCurrency() {
      return this.currencies.find(c => c.code === this.form.currency_code);
    }
  },
  async mounted() {
    await this.fetchCountries();
    this.prefillCurrentUserInfo();
    
    // Set default currency based on user's location or default to USD
    this.setDefaultCurrency();
  },
  watch: {
    // Watch country_id to suggest a default currency
    'form.country_id'(newCountryId) {
      this.suggestCurrencyForCountry(newCountryId);
    }
  },
  methods: {
    setDefaultCurrency() {
      // Try to get user's preferred currency from localStorage or user profile
      const userCurrency = localStorage.getItem('preferred_currency') || 
                          this.$store?.state?.auth?.user?.currency_code;
      
      if (userCurrency && this.currencies.find(c => c.code === userCurrency)) {
        this.form.currency_code = userCurrency;
      } else {
        this.form.currency_code = 'USD'; // Default fallback
      }
    },
    
    suggestCurrencyForCountry(countryId) {
      if (!countryId) return;
      
      const selectedCountry = this.countries.find(c => c.id === countryId);
      if (selectedCountry && selectedCountry.currency_code) {
        // Find if the country's currency exists in our list
        const suggestedCurrency = this.currencies.find(c => c.code === selectedCountry.currency_code);
        if (suggestedCurrency) {
          // Only suggest if the current currency is USD (default) or empty
          if (this.form.currency_code === 'USD' || !this.form.currency_code) {
            this.form.currency_code = suggestedCurrency.code;
            this.$notify({
              type: 'info',
              title: 'Currency Suggested',
              text: `Suggested currency for ${selectedCountry.name}: ${suggestedCurrency.code} - ${suggestedCurrency.name}`,
              duration: 3000
            });
          }
        }
      }
    },
    
    async fetchCountries() {
      try {
        const response = await axios.get('/api/admin/countries');
        this.countries = response.data.data;
        
        // Enrich countries with currency codes if not provided by API
        this.enrichCountriesWithCurrencies();
        
      } catch (error) {
        console.error('Failed to fetch countries:', error);
        this.$notify({
          type: 'error',
          title: 'Error',
          text: 'Failed to load countries list'
        });
      }
    },
    
    enrichCountriesWithCurrencies() {
      // Common country-currency mappings
      const countryCurrencyMap = {
        'US': 'USD',
        'GB': 'GBP',
        'EU': 'EUR',
        'ZA': 'ZAR',
        'NG': 'NGN',
        'KE': 'KES',
        'GH': 'GHS',
        'EG': 'EGP',
        'ZM': 'ZMW',
        'TZ': 'TZS',
        'UG': 'UGX',
        'ET': 'ETB',
        'MW': 'MWK',
        'RW': 'RWF',
        'BW': 'BWP',
        'NA': 'NAD',
        'LS': 'LSL',
        'SZ': 'SZL',
        'SD': 'SDG',
        'TN': 'TND',
        'LY': 'LYD',
        'DJ': 'DJF',
        'MU': 'MUR',
        'SC': 'SCR',
        'BI': 'BIF',
        'CD': 'CDF',
        'GM': 'GMD',
        'SL': 'SLL',
        'LR': 'LRD',
        'GN': 'GNF',
        'SS': 'SSP',
        'MR': 'MRU',
        'ST': 'STN',
        'AO': 'AOA',
        'ZW': 'ZWL',
        'SO': 'SOS',
        'KM': 'KMF',
        'ER': 'ERN',
        'MG': 'MGA',
        'DZ': 'DZD',
        'MA': 'MAD',
        'MZ': 'MZN'
      };
      
      // Add currency_code to countries if not present
      this.countries = this.countries.map(country => {
        if (!country.currency_code && country.code) {
          const currencyCode = countryCurrencyMap[country.code] || null;
          if (currencyCode) {
            return { ...country, currency_code: currencyCode };
          }
        }
        return country;
      });
    },
    
    prefillCurrentUserInfo() {
      // Get current user from your auth system
      const currentUser = this.$store?.state?.auth?.user ||
                         JSON.parse(localStorage.getItem('user')) ||
                         { first_name: 'Current', last_name: 'User', email: 'current.user@example.com', phone: '123-456-7890' };
      
      if (currentUser && this.useCurrentUserAsAdmin) {
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