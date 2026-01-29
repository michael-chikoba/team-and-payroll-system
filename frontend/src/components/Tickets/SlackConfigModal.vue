<template>
  <TransitionRoot :show="show" as="template">
    <Dialog as="div" class="relative z-50" @close="$emit('close')">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="relative w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all">
              <!-- Header -->
              <div class="border-b border-gray-200 bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-6">
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center">
                      <svg class="h-7 w-7 text-white" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M5.042 15.165a2.528 2.528 0 0 1-2.52 2.523A2.528 2.528 0 0 1 0 15.165a2.527 2.527 0 0 1 2.522-2.52h2.52v2.52zM6.313 15.165a2.527 2.527 0 0 1 2.521-2.52 2.527 2.527 0 0 1 2.521 2.52v6.313A2.528 2.528 0 0 1 8.834 24a2.528 2.528 0 0 1-2.521-2.522v-6.313zM8.834 5.042a2.528 2.528 0 0 1-2.521-2.52A2.528 2.528 0 0 1 8.834 0a2.528 2.528 0 0 1 2.521 2.522v2.52H8.834zM8.834 6.313a2.528 2.528 0 0 1 2.521 2.521 2.528 2.528 0 0 1-2.521 2.521H2.522A2.528 2.528 0 0 1 0 8.834a2.528 2.528 0 0 1 2.522-2.521h6.312zM18.956 8.834a2.528 2.528 0 0 1 2.522-2.521A2.528 2.528 0 0 1 24 8.834a2.528 2.528 0 0 1-2.522 2.521h-2.522V8.834zM17.688 8.834a2.528 2.528 0 0 1-2.523 2.521 2.527 2.527 0 0 1-2.52-2.521V2.522A2.527 2.527 0 0 1 15.165 0a2.528 2.528 0 0 1 2.523 2.522v6.312zM15.165 18.956a2.528 2.528 0 0 1 2.523 2.522A2.528 2.528 0 0 1 15.165 24a2.527 2.527 0 0 1-2.52-2.522v-2.522h2.52zM15.165 17.688a2.527 2.527 0 0 1-2.52-2.523 2.526 2.526 0 0 1 2.52-2.52h6.313A2.527 2.527 0 0 1 24 15.165a2.528 2.528 0 0 1-2.522 2.523h-6.313z"/>
                      </svg>
                    </div>
                    <div>
                      <DialogTitle class="text-2xl font-bold text-white">
                        Slack Integration
                      </DialogTitle>
                      <p class="text-sm text-purple-100 mt-1">
                        {{ integration ? 'Manage your Slack workspace connection' : 'Connect your Slack workspace' }}
                      </p>
                    </div>
                  </div>
                  <button
                    @click="$emit('close')"
                    class="rounded-lg p-2 text-white/80 hover:text-white hover:bg-white/10 transition-colors"
                  >
                    <XMarkIcon class="h-6 w-6" />
                  </button>
                </div>
              </div>

              <!-- Content -->
              <div class="px-6 py-6 max-h-[70vh] overflow-y-auto">
                <!-- Existing Integration Info -->
                <div v-if="integration" class="mb-6 rounded-xl bg-gradient-to-br from-purple-50 to-indigo-50 border border-purple-200 p-5">
                  <div class="flex items-start justify-between">
                    <div class="space-y-3">
                      <div class="flex items-center gap-2">
                        <div class="h-2 w-2 rounded-full" :class="integration.is_active ? 'bg-green-500 animate-pulse' : 'bg-gray-400'"></div>
                        <span class="text-sm font-semibold text-gray-700">
                          {{ integration.is_active ? 'Connected' : 'Disconnected' }}
                        </span>
                      </div>
                      
                      <div class="space-y-2">
                        <div v-if="integration.workspace_name" class="flex items-center gap-2 text-sm">
                          <span class="font-medium text-gray-600">Workspace:</span>
                          <span class="text-gray-900 font-semibold">{{ integration.workspace_name }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm">
                          <span class="font-medium text-gray-600">Channel:</span>
                          <span class="text-gray-900 font-semibold">#{{ integration.channel_name }}</span>
                        </div>
                        <div v-if="integration.connected_by" class="flex items-center gap-2 text-sm">
                          <span class="font-medium text-gray-600">Connected by:</span>
                          <span class="text-gray-700">{{ integration.connected_by.name }}</span>
                        </div>
                        <div v-if="integration.connected_at" class="flex items-center gap-2 text-sm">
                          <span class="font-medium text-gray-600">Connected:</span>
                          <span class="text-gray-700">{{ formatDate(integration.connected_at) }}</span>
                        </div>
                      </div>
                    </div>
                    
                    <div class="flex gap-2">
                      <button
                        @click="testConnection"
                        :disabled="isTestingConnection"
                        class="px-3 py-2 text-sm font-medium text-purple-700 bg-white border border-purple-300 rounded-lg hover:bg-purple-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        <span v-if="!isTestingConnection">Test</span>
                        <span v-else class="flex items-center gap-2">
                          <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                          </svg>
                          Testing...
                        </span>
                      </button>
                      <button
                        @click="confirmDisconnect"
                        class="px-3 py-2 text-sm font-medium text-red-700 bg-white border border-red-300 rounded-lg hover:bg-red-50 transition-colors"
                      >
                        Disconnect
                      </button>
                    </div>
                  </div>
                </div>

                <!-- Setup Instructions (when not connected) -->
                <div v-if="!integration" class="mb-6 rounded-xl bg-blue-50 border border-blue-200 p-5">
                  <h3 class="text-lg font-semibold text-blue-900 mb-3 flex items-center gap-2">
                    <InformationCircleIcon class="h-6 w-6" />
                    Setup Instructions
                  </h3>
                  <ol class="space-y-3 text-sm text-blue-800">
                    <li class="flex gap-3">
                      <span class="flex-shrink-0 h-6 w-6 rounded-full bg-blue-600 text-white flex items-center justify-center font-semibold text-xs">1</span>
                      <div>
                        <p class="font-medium">Create a Slack Incoming Webhook</p>
                        <p class="text-blue-700 mt-1">Go to <a href="https://api.slack.com/messaging/webhooks" target="_blank" class="underline hover:text-blue-900">Slack API</a> and create a new incoming webhook for your workspace.</p>
                      </div>
                    </li>
                    <li class="flex gap-3">
                      <span class="flex-shrink-0 h-6 w-6 rounded-full bg-blue-600 text-white flex items-center justify-center font-semibold text-xs">2</span>
                      <div>
                        <p class="font-medium">Select a Channel</p>
                        <p class="text-blue-700 mt-1">Choose the channel where ticket notifications will be sent.</p>
                      </div>
                    </li>
                    <li class="flex gap-3">
                      <span class="flex-shrink-0 h-6 w-6 rounded-full bg-blue-600 text-white flex items-center justify-center font-semibold text-xs">3</span>
                      <div>
                        <p class="font-medium">Copy the Webhook URL</p>
                        <p class="text-blue-700 mt-1">Copy your webhook URL and paste it below.</p>
                      </div>
                    </li>
                  </ol>
                </div>

                <!-- Configuration Form -->
                <form @submit.prevent="saveConfiguration" class="space-y-5">
                  <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                      Webhook URL <span class="text-red-500">*</span>
                    </label>
                    <input
                      v-model="form.webhook_url"
                      type="url"
                      required
                      placeholder="https://hooks.slack.com/services/..."
                      class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                      :class="{ 'border-red-300': errors.webhook_url }"
                    />
                    <p v-if="errors.webhook_url" class="mt-1 text-sm text-red-600">{{ errors.webhook_url[0] }}</p>
                  </div>

                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Channel Name <span class="text-red-500">*</span>
                      </label>
                      <input
                        v-model="form.channel_name"
                        type="text"
                        required
                        placeholder="general"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                        :class="{ 'border-red-300': errors.channel_name }"
                      />
                      <p v-if="errors.channel_name" class="mt-1 text-sm text-red-600">{{ errors.channel_name[0] }}</p>
                    </div>

                    <div>
                      <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Channel ID <span class="text-red-500">*</span>
                      </label>
                      <input
                        v-model="form.channel_id"
                        type="text"
                        required
                        placeholder="C01234567"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                        :class="{ 'border-red-300': errors.channel_id }"
                      />
                      <p v-if="errors.channel_id" class="mt-1 text-sm text-red-600">{{ errors.channel_id[0] }}</p>
                    </div>
                  </div>

                  <div class="grid grid-cols-2 gap-4">
                    <div>
                      <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Workspace Name
                      </label>
                      <input
                        v-model="form.workspace_name"
                        type="text"
                        placeholder="My Company Workspace"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                      />
                    </div>

                    <div>
                      <label class="block text-sm font-semibold text-gray-700 mb-2">
                        Workspace ID
                      </label>
                      <input
                        v-model="form.workspace_id"
                        type="text"
                        placeholder="T01234567"
                        class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm"
                      />
                    </div>
                  </div>

                  <!-- Notification Settings -->
                  <div class="border-t border-gray-200 pt-5">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Notification Settings</h4>
                    <p class="text-sm text-gray-600 mb-4">Choose which events trigger Slack notifications</p>
                    
                    <div class="space-y-3">
                      <label v-for="(label, key) in notificationTypes" :key="key" class="flex items-center justify-between p-3 rounded-lg border border-gray-200 hover:bg-gray-50 cursor-pointer transition-colors">
                        <span class="text-sm font-medium text-gray-700">{{ label }}</span>
                        <input
                          v-model="form.notification_settings[key]"
                          type="checkbox"
                          class="rounded border-gray-300 text-purple-600 focus:ring-purple-500"
                        />
                      </label>
                    </div>
                  </div>
                </form>
              </div>

              <!-- Footer -->
              <div class="border-t border-gray-200 bg-gray-50 px-6 py-4 flex items-center justify-end gap-3">
                <button
                  type="button"
                  @click="$emit('close')"
                  class="px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                >
                  Cancel
                </button>
                <button
                  @click="saveConfiguration"
                  :disabled="isSaving"
                  class="px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg hover:from-purple-700 hover:to-indigo-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg"
                >
                  <span v-if="!isSaving">{{ integration ? 'Update Configuration' : 'Connect Slack' }}</span>
                  <span v-else class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Saving...
                  </span>
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue'
import { XMarkIcon, InformationCircleIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'

const props = defineProps({
  show: {
    type: Boolean,
    required: true
  }
})

const emit = defineEmits(['close', 'saved'])

const integration = ref(null)
const isSaving = ref(false)
const isTestingConnection = ref(false)
const errors = ref({})

const form = ref({
  webhook_url: '',
  channel_name: '',
  channel_id: '',
  workspace_name: '',
  workspace_id: '',
  notification_settings: {
    created: true,
    approved: true,
    rejected: true,
    status_changed: true,
    assigned: true,
    comment_added: false,
    attachment_uploaded: false
  }
})

const notificationTypes = {
  created: 'New Ticket Created',
  approved: 'Ticket Approved',
  rejected: 'Ticket Rejected',
  status_changed: 'Status Changed',
  assigned: 'Ticket Assigned',
  comment_added: 'Comment Added',
  attachment_uploaded: 'Attachment Uploaded'
}

const fetchIntegration = async () => {
  try {
    const response = await axios.get('/api/slack-integration')
    if (response.data.is_configured) {
      integration.value = response.data.integration
      Object.assign(form.value, {
        webhook_url: '••••••••••••••••', // Masked for security
        channel_name: response.data.integration.channel_name,
        channel_id: response.data.integration.channel_id,
        workspace_name: response.data.integration.workspace_name || '',
        workspace_id: response.data.integration.workspace_id || '',
        notification_settings: response.data.integration.notification_settings
      })
    }
  } catch (error) {
       console.error('Full error object:', error)
    console.error('Response data:', error.response?.data)
    console.error('Status code:', error.response?.status)
    console.error('Request config:', error.config)
    console.error('Failed to fetch integration:', error)
    console.error('Error details:', {
      status: error.response?.status,
      data: error.response?.data,
      message: error.message
    })
    if (error.response?.status === 401) {
      alert('Authentication required. Please log in again.')
    } else if (error.response?.status === 403) {
      alert('You do not have permission to access this resource.')
    } else if (error.response?.status === 400) {
      alert('Bad request: ' + (error.response?.data?.message || 'Please check your request'))
    }
  }
}

const saveConfiguration = async () => {
  isSaving.value = true
  errors.value = {}

  try {
    const payload = { ...form.value }
    
    // Don't send masked webhook URL
    if (integration.value && payload.webhook_url === '••••••••••••••••') {
      delete payload.webhook_url
    }

    const response = integration.value
      ? await axios.put(`/api/slack-integration/${integration.value.id}`, payload)
      : await axios.post('/api/slack-integration', payload)

    integration.value = response.data.integration
    emit('saved')
    
    alert(response.data.message)
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else {
      alert(error.response?.data?.message || 'Failed to save configuration')
    }
  } finally {
    isSaving.value = false
  }
}

const testConnection = async () => {
  if (!integration.value) return

  isTestingConnection.value = true
  try {
    const response = await axios.post(`/api/slack-integration/${integration.value.id}/test`)
    
    if (response.data.success) {
      alert('✅ Test message sent successfully! Check your Slack channel.')
    } else {
      alert(`❌ Test failed: ${response.data.error}`)
    }
  } catch (error) {
    alert(`❌ Test failed: ${error.response?.data?.message || error.message}`)
  } finally {
    isTestingConnection.value = false
  }
}

const confirmDisconnect = async () => {
  if (!confirm('Are you sure you want to disconnect Slack? This will stop all notifications.')) {
    return
  }

  try {
    await axios.delete(`/api/slack-integration/${integration.value.id}`)
    integration.value = null
    form.value = {
      webhook_url: '',
      channel_name: '',
      channel_id: '',
      workspace_name: '',
      workspace_id: '',
      notification_settings: {
        created: true,
        approved: true,
        rejected: true,
        status_changed: true,
        assigned: true,
        comment_added: false,
        attachment_uploaded: false
      }
    }
    emit('saved')
    alert('Slack integration disconnected successfully')
  } catch (error) {
    alert('Failed to disconnect: ' + (error.response?.data?.message || error.message))
  }
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

watch(() => props.show, (newVal) => {
  if (newVal) {
    fetchIntegration()
  }
})
</script>