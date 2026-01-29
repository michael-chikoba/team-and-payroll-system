<template>
  <div v-if="showDebug" style="position: fixed; bottom: 10px; right: 10px; background: #000; color: #0f0; padding: 10px; font-family: monospace; font-size: 11px; max-width: 400px; border-radius: 5px; z-index: 9999;">
    <button @click="showDebug = false" style="float: right; background: red; color: white; border: none; padding: 2px 5px; cursor: pointer;">X</button>
    <div><strong>Auth Debug Panel</strong></div>
    <div>Authenticated: {{ authStore.isAuthenticated }}</div>
    <div>User: {{ authStore.user?.email || 'None' }}</div>
    <div>Token: {{ authStore.token ? authStore.token.substring(0, 20) + '...' : 'None' }}</div>
    <div>Expiry: {{ expiryDate }}</div>
    <div>Expired: {{ authStore.isTokenExpired() }}</div>
    <button @click="checkStorage" style="margin-top: 5px; background: #0f0; color: #000; border: none; padding: 5px; cursor: pointer;">Check Storage</button>
    <button @click="testAPI" style="margin-top: 5px; background: #00f; color: #fff; border: none; padding: 5px; cursor: pointer;">Test API</button>
    <div v-if="apiResponse" style="margin-top: 5px; max-height: 200px; overflow-y: auto;">
      <pre>{{ JSON.stringify(apiResponse, null, 2) }}</pre>
    </div>
  </div>
  <button v-else @click="showDebug = true" style="position: fixed; bottom: 10px; right: 10px; background: #000; color: #0f0; padding: 5px 10px; border: none; cursor: pointer; border-radius: 5px; z-index: 9999;">
    🐛 Debug
  </button>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/api/axios'

const authStore = useAuthStore()
const showDebug = ref(false)
const apiResponse = ref(null)

const expiryDate = computed(() => {
  if (!authStore.tokenExpiry) return 'None'
  return new Date(parseInt(authStore.tokenExpiry)).toLocaleString()
})

function checkStorage() {
  console.log('=== STORAGE CHECK ===')
  console.log('Token:', localStorage.getItem('token')?.substring(0, 30) + '...')
  console.log('User:', localStorage.getItem('user'))
  console.log('Expiry:', localStorage.getItem('tokenExpiry'))
  console.log('Expiry Date:', new Date(parseInt(localStorage.getItem('tokenExpiry'))).toISOString())
  console.log('Is Expired:', Date.now() > parseInt(localStorage.getItem('tokenExpiry')))
  alert('Check console for storage details')
}

async function testAPI() {
  try {
    const response = await api.get('/debug-token')
    apiResponse.value = response.data
    console.log('API Response:', response.data)
  } catch (error) {
    apiResponse.value = {
      error: error.message,
      status: error.response?.status,
      data: error.response?.data
    }
    console.error('API Error:', error)
  }
}
</script>