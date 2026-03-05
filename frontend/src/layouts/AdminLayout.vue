<template>
  <div class="admin-layout" :class="{ 'sidebar-collapsed': !isSidebarOpen }">
    <!-- Mobile sidebar overlay -->
    <div
      v-if="isMobile && isSidebarOpen"
      class="sidebar-overlay active"
      @click="closeSidebar"
    />

    <!-- ── Shared Sidebar ── -->
    <AppSidebar
      role="admin"
      :sidebar-classes="sidebarClasses"
      :pending-tickets-count="pendingTicketsCount"
      :unread-chat-count="unreadChatCount"
      :is-chat-open="showChat"
      :can-access-restricted-pages="canAccessRestrictedPages"
      @nav-click="handleNavClick"
      @open-chat="openChat"
    />

    <!-- Content wrapper: sits to the right of the sidebar -->
    <div class="content-area">
      <!-- ── Shared Navbar ── -->
      <AppNavbar
        role="admin"
        :is-sidebar-open="isSidebarOpen"
        :pending-tickets-count="pendingTicketsCount"
        :unread-chat-count="unreadChatCount"
        :is-chat-open="showChat"
        @toggle-sidebar="toggleSidebar"
        @open-chat="openChat"
        @go-to-tickets="goToTickets"
      />

      <!-- Main content: fills the space below the navbar and beside the sidebar -->
      <main class="main-content">
        <router-view />
      </main>

      <!-- Chat Modal -->
      <transition name="chat-popup">
        <div v-if="showChat" class="chat-popup-overlay" @click.self="closeChat">
          <div class="chat-popup-container">
            <div class="chat-popup-header">
              <h3>
                <ChatBubbleLeftRightIcon class="inline-icon" />
                Chat Assistant
              </h3>
              <button @click="closeChat" class="chat-close-btn">
                <XMarkIcon class="inline-icon" />
              </button>
            </div>
            <div class="chat-popup-content">
              <ChatInterface
                v-if="showChat"
                :key="chatKey"
                @close="closeChat"
                @unread-count="updateUnreadCount"
              />
            </div>
          </div>
        </div>
      </transition>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useSidebar } from '@/composables/useSidebar'
import { SidebarController } from '@/utils/sidebar-controller.js'
import AppSidebar from '@/components/AppSidebar.vue'
import AppNavbar from '@/components/AppNavbar.vue'
import ChatInterface from '@/components/ChatInterface.vue'
import NotificationBell from '@/components/NotificationBell.vue'
import { ChatBubbleLeftRightIcon, XMarkIcon } from '@heroicons/vue/24/outline'

export default {
  name: 'AdminLayout',
  components: { AppSidebar, AppNavbar, ChatInterface, NotificationBell, ChatBubbleLeftRightIcon, XMarkIcon },
  setup() {
    const authStore = useAuthStore()
    const router = useRouter()
    const route = useRoute()
    const { isSidebarOpen, isMobile, toggleSidebar, closeSidebar, checkIfMobile, sidebarClasses } = useSidebar()

    const showChat = ref(false)
    const chatKey = ref(0)
    const pendingTicketsCount = ref(0)
    const unreadChatCount = ref(0)

    const canAccessRestrictedPages = computed(() => {
      const allowed = ['michaelchikoba0@gmail.com']
      return allowed.includes(authStore.user?.email)
    })

    const handleNavClick = () => { if (isMobile.value) closeSidebar() }

    const openChat = () => {
      chatKey.value++
      showChat.value = true
      unreadChatCount.value = 0
      document.body.style.overflow = 'hidden'
    }
    const closeChat = () => {
      showChat.value = false
      document.body.style.overflow = 'auto'
    }
    const updateUnreadCount = (count) => { if (!showChat.value) unreadChatCount.value = count }

    const goToTickets = () => router.push('/admin/tickets')

    const fetchPendingTicketsCount = async () => {
      try {
        const res = await fetch('/api/tickets/count?status=pending', {
          headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
        }).catch(() => ({ ok: false }))
        if (res.ok) { const d = await res.json(); pendingTicketsCount.value = d.count || 0 }
      } catch {}
    }

    let interval

    const handleEscape = (e) => {
      if (e.key === 'Escape') {
        if (showChat.value) closeChat()
        if (isMobile.value && isSidebarOpen.value) closeSidebar()
      }
    }
    const handleResize = () => { checkIfMobile() }

    watch(() => route.path, () => { if (isMobile.value) closeSidebar() })

    onMounted(() => {
      new SidebarController()
      checkIfMobile()
      window.addEventListener('resize', handleResize)
      document.addEventListener('keydown', handleEscape)
      fetchPendingTicketsCount()
      interval = setInterval(fetchPendingTicketsCount, 300000)
      window.addEventListener('ticket-created', fetchPendingTicketsCount)
      window.addEventListener('ticket-updated', fetchPendingTicketsCount)
    })

    onUnmounted(() => {
      window.removeEventListener('resize', handleResize)
      document.removeEventListener('keydown', handleEscape)
      clearInterval(interval)
      window.removeEventListener('ticket-created', fetchPendingTicketsCount)
      window.removeEventListener('ticket-updated', fetchPendingTicketsCount)
      document.body.style.overflow = 'auto'
    })

    return {
      isSidebarOpen, isMobile, toggleSidebar, closeSidebar, sidebarClasses,
      showChat, chatKey, pendingTicketsCount, unreadChatCount,
      canAccessRestrictedPages,
      handleNavClick, openChat, closeChat, updateUnreadCount, goToTickets,
    }
  },
}
</script>

<style scoped>
@import '@/assets/css/shared-layout-styles.css';

.inline-icon {
  width: 1.25rem;
  height: 1.25rem;
  vertical-align: middle;
}

/* ============================================
   ADMIN LAYOUT — DEBUG BOUNDARIES ONLY
   All scroll / sticky / offset logic lives in
   shared-layout-styles.css. Scoped styles here
   are purely the coloured debug borders/labels.
   ============================================ */
.admin-layout { border: 3px solid red; }
.content-area { border: 3px solid orange; min-width: 0; }
.main-content { border: 3px solid green; position: relative; }

:deep(.header),
:deep(.top-header),
:deep(.top-bar) {
  border: 3px solid yellow !important;
}

.main-content > :deep(*) {
  border: 3px solid blue;
  min-height: 100%;
  width: 100%;
}

/* ============================================
   DEBUG LABELS
   ============================================ */
.admin-layout::before {
  content: "Layout Start";
  position: fixed;
  top: 0;
  left: 0;
  background: red;
  color: white;
  padding: 2px 5px;
  font-size: 12px;
  z-index: 10000;
  pointer-events: none;
}

.content-area::before {
  content: "Content Area";
  position: fixed;
  top: 0;
  left: 100px;
  background: orange;
  color: white;
  padding: 2px 5px;
  font-size: 12px;
  z-index: 10000;
  pointer-events: none;
}

:deep(.header)::before,
:deep(.top-header)::before,
:deep(.top-bar)::before {
  content: "Navbar Start";
  position: absolute;
  top: 0;
  right: 0;
  background: yellow;
  color: black;
  padding: 2px 5px;
  font-size: 12px;
  z-index: 10000;
  pointer-events: none;
}

.main-content::before {
  content: "Main Content";
  position: absolute;
  top: 10px;
  left: 10px;
  background: green;
  color: white;
  padding: 2px 5px;
  font-size: 12px;
  z-index: 10000;
  pointer-events: none;
}

.main-content > :deep(*)::before {
  content: "Page Slot";
  position: absolute;
  top: 10px;
  right: 10px;
  background: blue;
  color: white;
  padding: 2px 5px;
  font-size: 12px;
  z-index: 10000;
  pointer-events: none;
}

/* ── Uncomment to hide all debug visuals before deploying ── */

.admin-layout          { border: none !important; }
.content-area          { border: none !important; }
.main-content          { border: none !important; }
.main-content > :deep(*) { border: none !important; }
:deep(.header),
:deep(.top-header),
:deep(.top-bar)        { border: none !important; }

.admin-layout::before,
.content-area::before,
:deep(.header)::before,
:deep(.top-header)::before,
:deep(.top-bar)::before,
.main-content::before,
.main-content > :deep(*)::before { display: none; }

</style>