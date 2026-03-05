<template>
  <div class="employee-layout">
    <!-- Mobile sidebar overlay -->
    <div
      v-if="isMobile && isSidebarOpen"
      class="sidebar-overlay active"
      @click="closeSidebar"
    />

    <!-- ── Shared Sidebar ── -->
    <AppSidebar
      role="employee"
      :sidebar-classes="sidebarClasses"
      :pending-tickets-count="pendingTicketsCount"
      :unread-chat-count="unreadChatCount"
      :is-chat-open="showChatModal"
      @nav-click="handleNavClick"
      @open-chat="openChatModal"
    />

    <div class="content-area">
      <!-- ── Shared Navbar ── -->
      <AppNavbar
        role="employee"
        :is-sidebar-open="isSidebarOpen"
        :pending-tickets-count="pendingTicketsCount"
        :unread-chat-count="unreadChatCount"
        :is-chat-open="showChatModal"
        @toggle-sidebar="toggleSidebar"
        @open-chat="openChatModal"
        @create-ticket="openQuickCreateModal"
      />

      <main class="main">
        <router-view />

        <!-- Chat Popup (same pattern as admin) -->
        <transition name="chat-popup">
          <div v-if="showChatModal" class="chat-popup-overlay" @click.self="closeChatModal">
            <div class="chat-popup-container">
              <div class="chat-popup-header">
                <h3>
                  <ChatBubbleLeftRightIcon class="inline-icon" />
                  Team Chat
                </h3>
                <button @click="closeChatModal" class="chat-close-btn">
                  <XMarkIcon class="inline-icon" />
                </button>
              </div>
              <div class="chat-popup-content">
                <ChatComponent
                  v-if="showChatModal"
                  :key="chatKey"
                  @unread-count="updateUnreadCount"
                />
              </div>
            </div>
          </div>
        </transition>

        <!-- Quick Create Ticket Modal -->
        <CreateTicketModal
          v-if="showQuickCreateModal"
          :show="showQuickCreateModal"
          @close="closeQuickCreateModal"
          @created="handleTicketCreated"
        />
      </main>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useSidebar } from '@/composables/useSidebar'
import { SidebarController } from '@/utils/sidebar-controller.js'
import AppSidebar from '@/components/AppSidebar.vue'
import AppNavbar from '@/components/AppNavbar.vue'
import ChatComponent from '@/components/ChatInterface.vue'
import CreateTicketModal from '@/components/Tickets/CreateTicketModal.vue'
import NotificationBell from '@/components/NotificationBell.vue'
import axios from 'axios'
import { ChatBubbleLeftRightIcon, XMarkIcon } from '@heroicons/vue/24/outline'

export default {
  name: 'EmployeeLayout',
  components: {
    AppSidebar, AppNavbar, ChatComponent, CreateTicketModal, NotificationBell,
    ChatBubbleLeftRightIcon, XMarkIcon,
  },
  setup() {
    const authStore = useAuthStore()
    const router = useRouter()
    const route = useRoute()
    const { isSidebarOpen, isMobile, toggleSidebar, closeSidebar, checkIfMobile, sidebarClasses } = useSidebar()

    const showChatModal = ref(false)
    const showQuickCreateModal = ref(false)
    const chatKey = ref(0)
    const pendingTicketsCount = ref(0)
    const unreadChatCount = ref(0)

    const handleNavClick = () => { if (isMobile.value) closeSidebar() }

    const openChatModal = () => {
      chatKey.value++
      showChatModal.value = true
      unreadChatCount.value = 0
      document.body.style.overflow = 'hidden'
    }
    const closeChatModal = () => {
      showChatModal.value = false
      document.body.style.overflow = 'auto'
    }
    const updateUnreadCount = (count) => { if (!showChatModal.value) unreadChatCount.value = count }

    const openQuickCreateModal = () => { showQuickCreateModal.value = true }
    const closeQuickCreateModal = () => { showQuickCreateModal.value = false }

    const handleTicketCreated = () => {
      closeQuickCreateModal()
      if (route.name === 'mytickets') window.dispatchEvent(new CustomEvent('refresh-tickets'))
      fetchPendingTicketsCount()
    }

    const fetchPendingTicketsCount = async () => {
      try {
        const res = await axios.get('/api/tickets/count', { params: { status: 'pending' } })
          .catch(() => ({ data: { total: 0 } }))
        pendingTicketsCount.value = res.data.total || 0
      } catch {}
    }

    const handleEscape = (e) => {
      if (e.key === 'Escape') {
        if (showChatModal.value) closeChatModal()
        if (showQuickCreateModal.value) closeQuickCreateModal()
        if (isMobile.value && isSidebarOpen.value) closeSidebar()
      }
    }
    const handleResize = () => checkIfMobile()

    watch(() => route.path, () => {
      if (route.name === 'mytickets') fetchPendingTicketsCount()
    })

    onMounted(() => {
      new SidebarController()
      checkIfMobile()
      window.addEventListener('resize', handleResize)
      document.addEventListener('keydown', handleEscape)
      fetchPendingTicketsCount()
      const interval = setInterval(fetchPendingTicketsCount, 300000)
      window.addEventListener('ticket-created', fetchPendingTicketsCount)
      window.addEventListener('ticket-updated', fetchPendingTicketsCount)
      onUnmounted(() => {
        window.removeEventListener('resize', handleResize)
        document.removeEventListener('keydown', handleEscape)
        clearInterval(interval)
        window.removeEventListener('ticket-created', fetchPendingTicketsCount)
        window.removeEventListener('ticket-updated', fetchPendingTicketsCount)
        document.body.style.overflow = 'auto'
      })
    })

    return {
      isSidebarOpen, isMobile, toggleSidebar, closeSidebar, sidebarClasses,
      showChatModal, showQuickCreateModal, chatKey, pendingTicketsCount, unreadChatCount,
      handleNavClick, openChatModal, closeChatModal, updateUnreadCount,
      openQuickCreateModal, closeQuickCreateModal, handleTicketCreated,
    }
  },
}
</script>

<style scoped>
@import '@/assets/css/shared-layout-styles.css';
.inline-icon { width: 1.25rem; height: 1.25rem; vertical-align: middle; }
</style>