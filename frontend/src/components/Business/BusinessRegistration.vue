<template>
  <div class="max-w-4xl mx-auto py-4">
    <div class="bg-white shadow-2xl rounded-2xl p-8 border border-gray-100">
      <h2 class="text-3xl font-bold mb-8 text-gray-900 tracking-tight">
        🚀 Register Your New Business
      </h2>
      
      <form @submit.prevent="registerBusiness" class="space-y-10">
        
        <!-- 1. Business Details -->
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

        <!-- 2. Location & Finance -->
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
                  
                  <!-- Popular Group -->
                  <optgroup label="Popular Currencies" v-if="popularCurrencies.length > 0">
                    <option v-for="currency in popularCurrencies" :key="currency.code" :value="currency.code">
                      {{ currency.code }} - {{ currency.name }} ({{ currency.symbol }})
                    </option>
                  </optgroup>
                  
                  <!-- All Others Group -->
                  <optgroup label="All Currencies" v-if="allCurrencies.length > 0">
                    <option v-for="currency in allCurrencies" :key="currency.code" :value="currency.code">
                      {{ currency.code }} - {{ currency.name }}
                    </option>
                  </optgroup>
                </select>
                
                <!-- Loading Indicator -->
                <div v-if="loadingCurrencies" class="text-xs text-gray-500 mt-1 flex items-center">
                   <svg class="animate-spin h-3 w-3 mr-1 text-indigo-500" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Syncing rates...
                </div>
                
                <p v-if="selectedCurrency" class="text-xs text-gray-500 mt-1">
                  <span class="font-medium">{{ selectedCurrency.name }}</span> 
                  <span v-if="selectedCurrency.symbol"> (Symbol: {{ selectedCurrency.symbol }})</span>
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. Registration & Tax -->
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
        
        <!-- 4. Primary Administrator -->
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

// Static comprehensive list to ensure dropdown is never empty
const WORLD_CURRENCIES = [
  // Majors
  { code: 'USD', name: 'US Dollar', symbol: '$' },
  { code: 'EUR', name: 'Euro', symbol: '€' },
  { code: 'GBP', name: 'British Pound', symbol: '£' },
  { code: 'JPY', name: 'Japanese Yen', symbol: '¥' },
  { code: 'CNY', name: 'Chinese Yuan', symbol: '¥' },
  { code: 'CAD', name: 'Canadian Dollar', symbol: '$' },
  { code: 'AUD', name: 'Australian Dollar', symbol: '$' },
  { code: 'CHF', name: 'Swiss Franc', symbol: 'Fr' },
  
  // Africa
  { code: 'ZMW', name: 'Zambian Kwacha', symbol: 'K' },
  { code: 'ZAR', name: 'South African Rand', symbol: 'R' },
  { code: 'NGN', name: 'Nigerian Naira', symbol: '₦' },
  { code: 'KES', name: 'Kenyan Shilling', symbol: 'KSh' },
  { code: 'GHS', name: 'Ghanaian Cedi', symbol: 'GH₵' },
  { code: 'EGP', name: 'Egyptian Pound', symbol: 'E£' },
  { code: 'ETB', name: 'Ethiopian Birr', symbol: 'Br' },
  { code: 'DZD', name: 'Algerian Dinar', symbol: 'د.ج' },
  { code: 'MAD', name: 'Moroccan Dirham', symbol: 'د.م.' },
  { code: 'TZS', name: 'Tanzanian Shilling', symbol: 'TSh' },
  { code: 'UGX', name: 'Ugandan Shilling', symbol: 'USh' },
  { code: 'XOF', name: 'West African CFA Franc', symbol: 'CFA' },
  { code: 'XAF', name: 'Central African CFA Franc', symbol: 'FCFA' },
  { code: 'MZN', name: 'Mozambican Metical', symbol: 'MT' },
  { code: 'MWK', name: 'Malawian Kwacha', symbol: 'MK' },
  { code: 'RWF', name: 'Rwandan Franc', symbol: 'RF' },
  { code: 'BWP', name: 'Botswana Pula', symbol: 'P' },
  { code: 'NAD', name: 'Namibian Dollar', symbol: '$' },
  { code: 'LSL', name: 'Lesotho Loti', symbol: 'L' },
  { code: 'SZL', name: 'Swazi Lilangeni', symbol: 'E' },
  { code: 'SDG', name: 'Sudanese Pound', symbol: 'SDG' },
  { code: 'TND', name: 'Tunisian Dinar', symbol: 'د.ت' },
  { code: 'LYD', name: 'Libyan Dinar', symbol: 'ل.د' },
  { code: 'DJF', name: 'Djiboutian Franc', symbol: 'Fdj' },
  { code: 'MUR', name: 'Mauritian Rupee', symbol: '₨' },
  { code: 'SCR', name: 'Seychellois Rupee', symbol: '₨' },
  { code: 'BIF', name: 'Burundian Franc', symbol: 'FBu' },
  { code: 'CDF', name: 'Congolese Franc', symbol: 'FC' },
  { code: 'GMD', name: 'Gambian Dalasi', symbol: 'D' },
  { code: 'SLL', name: 'Sierra Leonean Leone', symbol: 'Le' },
  { code: 'LRD', name: 'Liberian Dollar', symbol: '$' },
  { code: 'GNF', name: 'Guinean Franc', symbol: 'FG' },
  { code: 'SSP', name: 'South Sudanese Pound', symbol: '£' },
  { code: 'MRU', name: 'Mauritanian Ouguiya', symbol: 'UM' },
  { code: 'STN', name: 'São Tomé and Príncipe Dobra', symbol: 'Db' },
  { code: 'AOA', name: 'Angolan Kwanza', symbol: 'Kz' },
  { code: 'ZWL', name: 'Zimbabwean Dollar', symbol: '$' },
  { code: 'SOS', name: 'Somali Shilling', symbol: 'Sh.So.' },
  { code: 'KMF', name: 'Comorian Franc', symbol: 'CF' },
  { code: 'ERN', name: 'Eritrean Nakfa', symbol: 'Nfk' },
  { code: 'MGA', name: 'Malagasy Ariary', symbol: 'Ar' },
  { code: 'CVE', name: 'Cape Verdean Escudo', symbol: '$' },
  
  // Asia & Pacific
  { code: 'INR', name: 'Indian Rupee', symbol: '₹' },
  { code: 'PKR', name: 'Pakistani Rupee', symbol: '₨' },
  { code: 'BDT', name: 'Bangladeshi Taka', symbol: '৳' },
  { code: 'THB', name: 'Thai Baht', symbol: '฿' },
  { code: 'IDR', name: 'Indonesian Rupiah', symbol: 'Rp' },
  { code: 'PHP', name: 'Philippine Peso', symbol: '₱' },
  { code: 'VND', name: 'Vietnamese Dong', symbol: '₫' },
  { code: 'KRW', name: 'South Korean Won', symbol: '₩' },
  { code: 'SGD', name: 'Singapore Dollar', symbol: '$' },
  { code: 'MYR', name: 'Malaysian Ringgit', symbol: 'RM' },
  { code: 'NZD', name: 'New Zealand Dollar', symbol: '$' },
  { code: 'HKD', name: 'Hong Kong Dollar', symbol: '$' },
  { code: 'TWD', name: 'New Taiwan Dollar', symbol: 'NT$' },
  { code: 'AFN', name: 'Afghan Afghani', symbol: '؋' },
  { code: 'LKR', name: 'Sri Lankan Rupee', symbol: 'Rs' },
  { code: 'MMK', name: 'Burmese Kyat', symbol: 'K' },
  { code: 'NPR', name: 'Nepalese Rupee', symbol: 'Rs' },
  { code: 'KHR', name: 'Cambodian Riel', symbol: '៛' },
  { code: 'LAK', name: 'Lao Kip', symbol: '₭' },
  { code: 'MNT', name: 'Mongolian Tögrög', symbol: '₮' },
  { code: 'FJD', name: 'Fijian Dollar', symbol: '$' },
  { code: 'PGK', name: 'Papua New Guinean Kina', symbol: 'K' },
  { code: 'WST', name: 'Samoan Tālā', symbol: 'T' },
  { code: 'VUV', name: 'Vanuatu Vatu', symbol: 'VT' },
  { code: 'TOP', name: 'Tongan Paʻanga', symbol: 'T$' },
  { code: 'SBD', name: 'Solomon Islands Dollar', symbol: '$' },
  
  // Middle East & Central Asia
  { code: 'AED', name: 'UAE Dirham', symbol: 'د.إ' },
  { code: 'SAR', name: 'Saudi Riyal', symbol: '﷼' },
  { code: 'QAR', name: 'Qatari Riyal', symbol: '﷼' },
  { code: 'KWD', name: 'Kuwaiti Dinar', symbol: 'د.ك' },
  { code: 'BHD', name: 'Bahraini Dinar', symbol: '.د.ب' },
  { code: 'OMR', name: 'Omani Rial', symbol: '﷼' },
  { code: 'JOD', name: 'Jordanian Dinar', symbol: 'د.ا' },
  { code: 'ILS', name: 'Israeli New Shekel', symbol: '₪' },
  { code: 'TRY', name: 'Turkish Lira', symbol: '₺' },
  { code: 'IRR', name: 'Iranian Rial', symbol: '﷼' },
  { code: 'IQD', name: 'Iraqi Dinar', symbol: 'ع.د' },
  { code: 'LBP', name: 'Lebanese Pound', symbol: 'ل.ل' },
  { code: 'YER', name: 'Yemeni Rial', symbol: '﷼' },
  { code: 'SYP', name: 'Syrian Pound', symbol: '£' },
  { code: 'AZN', name: 'Azerbaijani Manat', symbol: '₼' },
  { code: 'KZT', name: 'Kazakhstani Tenge', symbol: '₸' },
  { code: 'UZS', name: 'Uzbekistani Soʻm', symbol: 'лв' },
  { code: 'TMT', name: 'Turkmenistan Manat', symbol: 'm' },
  { code: 'KGS', name: 'Kyrgyzstani Som', symbol: 'с' },
  { code: 'TJS', name: 'Tajikistani Somoni', symbol: 'SM' },
  { code: 'GEL', name: 'Georgian Lari', symbol: '₾' },
  { code: 'AMD', name: 'Armenian Dram', symbol: '֏' },
  
  // Americas
  { code: 'BRL', name: 'Brazilian Real', symbol: 'R$' },
  { code: 'MXN', name: 'Mexican Peso', symbol: '$' },
  { code: 'ARS', name: 'Argentine Peso', symbol: '$' },
  { code: 'CLP', name: 'Chilean Peso', symbol: '$' },
  { code: 'COP', name: 'Colombian Peso', symbol: '$' },
  { code: 'PEN', name: 'Peruvian Sol', symbol: 'S/' },
  { code: 'UYU', name: 'Uruguayan Peso', symbol: '$' },
  { code: 'BOB', name: 'Bolivian Boliviano', symbol: 'Bs' },
  { code: 'VES', name: 'Venezuelan Bolívar', symbol: 'Bs' },
  { code: 'PYG', name: 'Paraguayan Guaraní', symbol: '₲' },
  { code: 'CRC', name: 'Costa Rican Colón', symbol: '₡' },
  { code: 'DOP', name: 'Dominican Peso', symbol: 'RD$' },
  { code: 'GTQ', name: 'Guatemalan Quetzal', symbol: 'Q' },
  { code: 'HNL', name: 'Honduran Lempira', symbol: 'L' },
  { code: 'NIO', name: 'Nicaraguan Córdoba', symbol: 'C$' },
  { code: 'PAB', name: 'Panamanian Balboa', symbol: 'B/.' },
  { code: 'CUP', name: 'Cuban Peso', symbol: '$' },
  { code: 'JMD', name: 'Jamaican Dollar', symbol: 'J$' },
  { code: 'TTD', name: 'Trinidad and Tobago Dollar', symbol: 'TT$' },
  { code: 'BSD', name: 'Bahamian Dollar', symbol: '$' },
  { code: 'BBD', name: 'Barbadian Dollar', symbol: '$' },
  { code: 'HTG', name: 'Haitian Gourde', symbol: 'G' },
  { code: 'XCD', name: 'East Caribbean Dollar', symbol: '$' },
  { code: 'GYD', name: 'Guyanese Dollar', symbol: '$' },
  { code: 'SRD', name: 'Surinamese Dollar', symbol: '$' },
  { code: 'BZD', name: 'Belize Dollar', symbol: 'BZ$' },
  
  // Europe Non-Euro
  { code: 'RUB', name: 'Russian Ruble', symbol: '₽' },
  { code: 'SEK', name: 'Swedish Krona', symbol: 'kr' },
  { code: 'NOK', name: 'Norwegian Krone', symbol: 'kr' },
  { code: 'DKK', name: 'Danish Krone', symbol: 'kr' },
  { code: 'PLN', name: 'Polish Złoty', symbol: 'zł' },
  { code: 'CZK', name: 'Czech Koruna', symbol: 'Kč' },
  { code: 'HUF', name: 'Hungarian Forint', symbol: 'Ft' },
  { code: 'ISK', name: 'Icelandic Króna', symbol: 'kr' },
  { code: 'RON', name: 'Romanian Leu', symbol: 'lei' },
  { code: 'BGN', name: 'Bulgarian Lev', symbol: 'лв' },
  { code: 'RSD', name: 'Serbian Dinar', symbol: 'дин.' },
  { code: 'UAH', name: 'Ukrainian Hryvnia', symbol: '₴' },
  { code: 'BYN', name: 'Belarusian Ruble', symbol: 'Br' },
  { code: 'MDL', name: 'Moldovan Leu', symbol: 'L' },
  { code: 'ALL', name: 'Albanian Lek', symbol: 'L' },
  { code: 'BAM', name: 'Bosnia-Herzegovina Mark', symbol: 'KM' },
  { code: 'MKD', name: 'Macedonian Denar', symbol: 'ден' },
];

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
      // Initialize with full list immediately so dropdown is never empty
      currencies: [...WORLD_CURRENCIES], 
      loading: false,
      loadingCurrencies: false,
      errors: {},
      useCurrentUserAsAdmin: true
    }
  },
  computed: {
    selectedCurrency() {
      return this.currencies.find(c => c.code === this.form.currency_code);
    },
    popularCurrencies() {
      // Define popular currency codes
      const popularCodes = ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY', 'ZMW', 'ZAR', 'NGN', 'KES', 'GHS'];
      return this.currencies.filter(currency => 
        currency.code && popularCodes.includes(currency.code.toUpperCase())
      );
    },
    allCurrencies() {
      const popularCodes = ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY', 'ZMW', 'ZAR', 'NGN', 'KES', 'GHS'];
      return this.currencies.filter(currency => 
        currency.code && !popularCodes.includes(currency.code.toUpperCase())
      );
    }
  },
  async mounted() {
    // Parallel fetch
    await Promise.all([
      this.fetchCountries(),
      this.fetchCurrencies() // This now only UPDATES/MERGES, never deletes
    ]);
    
    this.prefillCurrentUserInfo();
    this.setDefaultCurrency();
  },
  watch: {
    'form.country_id'(newCountryId) {
      this.suggestCurrencyForCountry(newCountryId);
    }
  },
  methods: {
    async fetchCurrencies() {
      this.loadingCurrencies = true;
      try {
        // Fetch known currencies from API (e.g., active ones or ones with specific settings)
        const response = await axios.get('/api/admin/currencies');
        
        if (response.data && response.data.data && Array.isArray(response.data.data)) {
          const apiData = response.data.data;
          
          // Create a map of existing currencies for O(1) lookup
          // We map strictly by UpperCase Code to avoid duplicates
          const currencyMap = new Map(this.currencies.map(c => [c.code.toUpperCase(), c]));
          
          apiData.forEach(apiCurrency => {
            if (!apiCurrency.code) return;
            const code = apiCurrency.code.toUpperCase();
            
            if (currencyMap.has(code)) {
              // MERGE: Update existing currency with API data (e.g. latest rates, ID)
              const existing = currencyMap.get(code);
              currencyMap.set(code, { ...existing, ...apiCurrency });
            } else {
              // ADD: If API has a currency we somehow missed, add it
              currencyMap.set(code, {
                code: code,
                name: apiCurrency.name || code,
                symbol: apiCurrency.symbol || code,
                decimal_places: apiCurrency.decimal_places || 2
              });
            }
          });
          
          // Convert back to array
          this.currencies = Array.from(currencyMap.values());
        }
      } catch (error) {
        console.warn('Currency sync failed, using built-in global list:', error);
        // Do nothing else - the hardcoded list is already there and safe.
      } finally {
        this.loadingCurrencies = false;
      }
    },
    
    setDefaultCurrency() {
      const userCurrency = localStorage.getItem('preferred_currency') || 
                          this.$store?.state?.auth?.user?.currency_code;
      
      // Only set if it exists in our list
      if (userCurrency && this.currencies.some(c => c.code === userCurrency)) {
        this.form.currency_code = userCurrency;
      } else {
        this.form.currency_code = 'USD';
      }
    },
    
    suggestCurrencyForCountry(countryId) {
      if (!countryId) return;
      
      const selectedCountry = this.countries.find(c => c.id === countryId);
      if (selectedCountry && selectedCountry.currency_code) {
        const suggestedCurrency = this.currencies.find(c => c.code === selectedCountry.currency_code);
        
        if (suggestedCurrency) {
          // Suggest if currently unset or default
          if (this.form.currency_code === 'USD' || !this.form.currency_code) {
            this.form.currency_code = suggestedCurrency.code;
            this.$notify({
              type: 'info',
              title: 'Currency Suggested',
              text: `Currency set to ${suggestedCurrency.code} for ${selectedCountry.name}`,
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
        this.enrichCountriesWithCurrencies();
      } catch (error) {
        console.error('Failed to fetch countries:', error);
      }
    },
    
    enrichCountriesWithCurrencies() {
      // Map country codes (ISO 3166-1 alpha-2) to currency codes
      // This helps auto-select currency when user picks a country
      const countryCurrencyMap = {
        'US': 'USD', 'GB': 'GBP', 'EU': 'EUR', 'DE': 'EUR', 'FR': 'EUR', 'IT': 'EUR', 'ES': 'EUR',
        'ZA': 'ZAR', 'NG': 'NGN', 'KE': 'KES', 'GH': 'GHS', 'ZM': 'ZMW', 'TZ': 'TZS', 'UG': 'UGX',
        'IN': 'INR', 'CN': 'CNY', 'JP': 'JPY', 'AU': 'AUD', 'CA': 'CAD', 'CH': 'CHF', 'AE': 'AED',
        'RW': 'RWF', 'MW': 'MWK', 'BW': 'BWP', 'NA': 'NAD', 'ZW': 'ZWL', 'MZ': 'MZN', 'AO': 'AOA',
        'ET': 'ETB', 'EG': 'EGP', 'DZ': 'DZD', 'MA': 'MAD', 'SD': 'SDG', 'TN': 'TND', 'LY': 'LYD',
        'SA': 'SAR', 'QA': 'QAR', 'KW': 'KWD', 'BH': 'BHD', 'OM': 'OMR', 'JO': 'JOD', 'LB': 'LBP',
        'TR': 'TRY', 'RU': 'RUB', 'KR': 'KRW', 'TH': 'THB', 'ID': 'IDR', 'VN': 'VND', 'PH': 'PHP',
        'MY': 'MYR', 'SG': 'SGD', 'HK': 'HKD', 'TW': 'TWD', 'NZ': 'NZD', 'BR': 'BRL', 'MX': 'MXN',
        'AR': 'ARS', 'CL': 'CLP', 'CO': 'COP', 'PE': 'PEN', 'SE': 'SEK', 'NO': 'NOK', 'DK': 'DKK'
      };
      
      this.countries = this.countries.map(country => {
        if (!country.currency_code && country.code) {
          const code = countryCurrencyMap[country.code];
          if (code) return { ...country, currency_code: code };
        }
        return country;
      });
    },
    
    prefillCurrentUserInfo() {
      const currentUser = this.$store?.state?.auth?.user ||
                         JSON.parse(localStorage.getItem('user'));
      
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
        this.$notify({ type: 'success', title: 'Success', text: 'Business registered successfully!' });
      } catch (error) {
        console.error('Registration error:', error);
        if (error.response?.status === 422) {
          this.errors = error.response.data.errors;
          this.$notify({ type: 'error', title: 'Validation Error', text: 'Please check the form for errors' });
        } else {
          this.$notify({ type: 'error', title: 'Error', text: error.response?.data?.message || 'Failed to register business' });
        }
      } finally {
        this.loading = false;
      }
    }
  }
}
</script>