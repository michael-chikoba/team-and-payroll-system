<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-3xl font-bold text-gray-900">Business Group Invitations</h1>
      <p class="mt-2 text-sm text-gray-600">
        Manage invitations to join business groups
      </p>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200 mb-6">
      <nav class="-mb-px flex space-x-8">
        <button
          @click="activeTab = 'received'"
          :class="[
            activeTab === 'received'
              ? 'border-purple-500 text-purple-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          Received
          <span
            v-if="receivedInvitations.length > 0"
            class="ml-2 py-0.5 px-2 rounded-full text-xs font-medium bg-purple-100 text-purple-600"
          >
            {{ receivedInvitations.length }}
          </span>
        </button>
        <button
          @click="activeTab = 'sent'"
          :class="[
            activeTab === 'sent'
              ? 'border-purple-500 text-purple-600'
              : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
            'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm'
          ]"
        >
          Sent
          <span
            v-if="sentInvitations.length > 0"
            class="ml-2 py-0.5 px-2 rounded-full text-xs font-medium bg-gray-100 text-gray-600"
          >
            {{ sentInvitations.length }}
          </span>
        </button>
      </nav>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-purple-200 border-t-purple-600"></div>
      <p class="mt-4 text-sm text-gray-500">Loading invitations...</p>
    </div>

    <!-- Received Invitations Tab -->
    <div v-else-if="activeTab === 'received'">
      <div v-if="receivedInvitations.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No pending invitations</h3>
        <p class="mt-1 text-sm text-gray-500">
          You don't have any business group invitations at this time.
        </p>
      </div>

      <div v-else class="space-y-4">
        <InvitationCard
          v-for="invitation in receivedInvitations"
          :key="invitation.id"
          :invitation="invitation"
          type="received"
          @accept="handleAccept"
          @reject="handleReject"
        />
      </div>
    </div>

    <!-- Sent Invitations Tab -->
    <div v-else-if="activeTab === 'sent'">
      <div v-if="sentInvitations.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">No sent invitations</h3>
        <p class="mt-1 text-sm text-gray-500">
          You haven't sent any business group invitations yet.
        </p>
      </div>

      <div v-else class="space-y-4">
        <InvitationCard
          v-for="invitation in sentInvitations"
          :key="invitation.id"
          :invitation="invitation"
          type="sent"
          @cancel="handleCancel"
        />
      </div>
    </div>

    <!-- Accept Modal -->
    <AcceptInvitationModal
      v-if="acceptingInvitation"
      :invitation="acceptingInvitation"
      @close="acceptingInvitation = null"
      @accepted="handleAccepted"
    />

    <!-- Reject Modal -->
    <RejectInvitationModal
      v-if="rejectingInvitation"
      :invitation="rejectingInvitation"
      @close="rejectingInvitation = null"
      @rejected="handleRejected"
    />
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'
import InvitationCard from './InvitationCard.vue'
import AcceptInvitationModal from './AcceptInvitationModal.vue'
import RejectInvitationModal from './RejectInvitationModal.vue'

export default {
  name: 'InvitationsDashboard',
  
  components: {
    InvitationCard,
    AcceptInvitationModal,
    RejectInvitationModal,
  },
  
  setup() {
    const loading = ref(false)
    const activeTab = ref('received')
    const receivedInvitations = ref([])
    const sentInvitations = ref([])
    const acceptingInvitation = ref(null)
    const rejectingInvitation = ref(null)

    const fetchInvitations = async () => {
      loading.value = true
      try {
        const response = await axios.get('/api/business-group-invitations')
        
        if (response.data.success) {
          receivedInvitations.value = response.data.data.received
          sentInvitations.value = response.data.data.sent
        }
      } catch (error) {
        console.error('Error fetching invitations:', error)
      } finally {
        loading.value = false
      }
    }

    const handleAccept = (invitation) => {
      acceptingInvitation.value = invitation
    }

    const handleReject = (invitation) => {
      rejectingInvitation.value = invitation
    }

    const handleAccepted = () => {
      acceptingInvitation.value = null
      fetchInvitations()
    }

    const handleRejected = () => {
      rejectingInvitation.value = null
      fetchInvitations()
    }

    const handleCancel = async (invitation) => {
      if (!confirm('Are you sure you want to cancel this invitation?')) {
        return
      }

      try {
        const response = await axios.delete(`/api/business-group-invitations/${invitation.id}`)
        
        if (response.data.success) {
          fetchInvitations()
        }
      } catch (error) {
        console.error('Error cancelling invitation:', error)
      }
    }

    onMounted(() => {
      fetchInvitations()
    })

    return {
      loading,
      activeTab,
      receivedInvitations,
      sentInvitations,
      acceptingInvitation,
      rejectingInvitation,
      handleAccept,
      handleReject,
      handleAccepted,
      handleRejected,
      handleCancel,
    }
  }
}
</script>