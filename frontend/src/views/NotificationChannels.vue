<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
              <BellIcon class="w-7 h-7 text-indigo-600" />
              Notification Channels
            </h1>
            <p class="text-sm text-gray-600 mt-1">
              Configure where ticket notifications are sent
            </p>
          </div>
          <button
            @click="handleCreate"
            class="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
          >
            <PlusIcon class="w-5 h-5" />
            Add Channel
          </button>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600 mx-auto"></div>
        <p class="mt-4 text-gray-600">Loading notification channels...</p>
      </div>

      <!-- Channels Grid -->
      <div v-else-if="channels.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="channel in channels"
          :key="channel.id"
          class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-all"
        >
          <div class="flex items-start justify-between mb-4">
            <div :class="['p-3 rounded-lg', getChannelTypeColor(channel.channel_type)]">
              <component :is="getChannelIcon(channel.channel_type)" class="w-5 h-5" />
            </div>
            <div class="flex items-center gap-2">
              <button
                @click="handleTest(channel)"
                :disabled="testingChannel === channel.id"
                class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors disabled:opacity-50"
                title="Test notification"
              >
                <BeakerIcon class="w-4 h-4" />
              </button>
              <button
                @click="handleEdit(channel)"
                class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
              >
                <PencilIcon class="w-4 h-4" />
              </button>
              <button
                @click="handleDelete(channel.id)"
                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
              >
                <TrashIcon class="w-4 h-4" />
              </button>
            </div>
          </div>

          <h3 class="font-semibold text-gray-900 mb-1">{{ channel.name }}</h3>
          <p class="text-sm text-gray-600 mb-3">{{ channel.channel_type_display }}</p>
          
          <div class="flex items-center gap-2 text-xs text-gray-500 mb-4">
            <ChatBubbleLeftIcon class="w-4 h-4" />
            {{ channel.destination }}
          </div>

          <div class="border-t border-gray-100 pt-4 mt-4">
            <div class="flex items-center justify-between text-xs">
              <span class="text-gray-500">Events:</span>
              <span class="font-medium text-gray-700">
                {{ channel.subscribed_events?.length || 0 }}
              </span>
            </div>
            <div class="flex items-center justify-between text-xs mt-2">
              <span class="text-gray-500">Status:</span>
              <span :class="[
                'px-2 py-1 rounded-full',
                channel.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'
              ]">
                {{ channel.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
        <BellIcon class="w-16 h-16 text-gray-300 mx-auto mb-4" />
        <h3 class="text-lg font-semibold text-gray-900 mb-2">
          No notification channels configured
        </h3>
        <p class="text-gray-600 mb-6">
          Get started by creating your first notification channel
        </p>
        <button
          @click="handleCreate"
          class="inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
        >
          <PlusIcon class="w-5 h-5" />
          Create Channel
        </button>
      </div>

      <!-- Create/Edit Modal -->
      <teleport to="body">
        <div v-if="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="showCreateModal = false">
          <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto" @click.stop>
            <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 z-10">
              <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-900">
                  {{ editingChannel ? 'Edit Channel' : 'Create Channel' }}
                </h2>
                <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600">
                  <XMarkIcon class="w-6 h-6" />
                </button>
              </div>
            </div>

            <div class="p-6 space-y-6">
              <!-- Channel Name -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Channel Name
                </label>
                <input
                  v-model="formData.name"
                  type="text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  placeholder="e.g., Urgent Tickets Notifications"
                  required
                />
              </div>

              <!-- Channel Type -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Channel Type
                </label>
                <select
                  v-model="formData.channel_type"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  :disabled="editingChannel !== null"
                >
                  <option value="chat_group">Chat Group</option>
                  <option value="email">Email</option>
                  <option value="slack">Slack</option>
                  <option value="webhook">Webhook</option>
                </select>
              </div>

              <!-- Chat Group Selection -->
              <div v-if="formData.channel_type === 'chat_group'">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Select Chat Group
                </label>
                <select
                  v-model="formData.chat_group_id"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  required
                >
                  <option value="">Select a group...</option>
                  <option v-for="group in chatGroups" :key="group.id" :value="group.id">
                    {{ group.name }} ({{ group.member_count }} members)
                  </option>
                </select>
              </div>

              <!-- Email Address -->
              <div v-if="formData.channel_type === 'email'">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Email Address
                </label>
                <input
                  v-model="formData.email_address"
                  type="email"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                  placeholder="notifications@example.com"
                  required
                />
              </div>

              <!-- Events -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">
                  Subscribe to Events
                </label>
                <div class="space-y-2">
                  <label
                    v-for="(label, key) in availableEvents"
                    :key="key"
                    class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer"
                  >
                    <input
                      type="checkbox"
                      :value="key"
                      v-model="formData.subscribed_events"
                      class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                    />
                    <span class="text-sm text-gray-700">{{ label }}</span>
                  </label>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200">
                <button
                  @click="showCreateModal = false"
                  type="button"
                  class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors"
                >
                  Cancel
                </button>
                <button
                  @click="handleSubmit"
                  type="button"
                  class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors"
                >
                  {{ editingChannel ? 'Update' : 'Create' }} Channel
                </button>
              </div>
            </div>
          </div>
        </div>
      </teleport>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import {
  BellIcon,
  PlusIcon,
  TrashIcon,
  PencilIcon,
  BeakerIcon,
  ChatBubbleLeftIcon,
  EnvelopeIcon,
  PaperAirplaneIcon,
  GlobeAltIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline'

const channels = ref([])
const chatGroups = ref([])
const availableEvents = ref({})
const loading = ref(true)
const showCreateModal = ref(false)
const editingChannel = ref(null)
const testingChannel = ref(null)

const formData = ref({
  name: '',
  channel_type: 'chat_group',
  chat_group_id: '',
  email_address: '',
  slack_webhook_url: '',
  webhook_url: '',
  subscribed_events: [],
  filters: {}
})

onMounted(() => {
  fetchData()
})

const fetchData = async () => {
  try {
    loading.value = true
    const [channelsRes, groupsRes, eventsRes] = await Promise.all([
      axios.get('/api/notification-channels'),
      axios.get('/api/notification-channels/available-chat-groups'),
      axios.get('/api/notification-channels/available-events')
    ])

    if (channelsRes.data.success) channels.value = channelsRes.data.channels
    if (groupsRes.data.success) chatGroups.value = groupsRes.data.chat_groups
    if (eventsRes.data.success) availableEvents.value = eventsRes.data.events
  } catch (error) {
    console.error('Failed to fetch data:', error)
  } finally {
    loading.value = false
  }
}

const handleCreate = () => {
  editingChannel.value = null
  formData.value = {
    name: '',
    channel_type: 'chat_group',
    chat_group_id: '',
    email_address: '',
    slack_webhook_url: '',
    webhook_url: '',
    subscribed_events: [],
    filters: {}
  }
  showCreateModal.value = true
}

const handleEdit = (channel) => {
  editingChannel.value = channel
  formData.value = {
    name: channel.name,
    channel_type: channel.channel_type,
    chat_group_id: channel.chat_group?.id || '',
    email_address: '',
    slack_webhook_url: '',
    webhook_url: '',
    subscribed_events: channel.subscribed_events || [],
    filters: channel.filters || {}
  }
  showCreateModal.value = true
}

const handleSubmit = async () => {
  try {
    const url = editingChannel.value 
      ? `/api/notification-channels/${editingChannel.value.id}`
      : '/api/notification-channels'
    
    const method = editingChannel.value ? 'put' : 'post'
    
    const response = await axios[method](url, formData.value)

    if (response.data.success) {
      showCreateModal.value = false
      fetchData()
      alert(editingChannel.value ? 'Channel updated!' : 'Channel created!')
    }
  } catch (error) {
    console.error('Failed to save channel:', error)
    alert('Failed to save channel')
  }
}

const handleDelete = async (channelId) => {
  if (!confirm('Delete this notification channel?')) return

  try {
    const response = await axios.delete(`/api/notification-channels/${channelId}`)

    if (response.data.success) {
      fetchData()
      alert('Channel deleted successfully')
    }
  } catch (error) {
    console.error('Failed to delete channel:', error)
    alert('Failed to delete channel')
  }
}

const handleTest = async (channel) => {
  testingChannel.value = channel.id
  try {
    const response = await axios.post(`/api/notification-channels/${channel.id}/test`)

    if (response.data.success) {
      alert('✅ Test notification sent successfully!')
    }
  } catch (error) {
    console.error('Test failed:', error)
    alert('❌ Test failed')
  } finally {
    testingChannel.value = null
  }
}

const getChannelIcon = (type) => {
  switch (type) {
    case 'chat_group': return ChatBubbleLeftIcon
    case 'email': return EnvelopeIcon
    case 'slack': return PaperAirplaneIcon
    case 'webhook': return GlobeAltIcon
    default: return BellIcon
  }
}

const getChannelTypeColor = (type) => {
  switch (type) {
    case 'chat_group': return 'bg-blue-100 text-blue-700'
    case 'email': return 'bg-green-100 text-green-700'
    case 'slack': return 'bg-purple-100 text-purple-700'
    case 'webhook': return 'bg-orange-100 text-orange-700'
    default: return 'bg-gray-100 text-gray-700'
  }
}
</script>