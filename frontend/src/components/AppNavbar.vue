<template>
  <header class="top-bar">
    <!-- Hamburger (mobile) -->
    <button
      class="hamburger-menu"
      :class="{ active: isSidebarOpen }"
      @click="$emit('toggle-sidebar')"
      aria-label="Toggle Menu"
    >
      <div class="hamburger-icon">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </button>

   
    <!-- Right-side actions -->
    <div class="right-actions">

      <!-- New Ticket button (employee tickets page only) -->
      <button
        v-if="showNewTicketButton"
        @click="$emit('create-ticket')"
        class="quick-action-button"
        title="Create New Ticket"
      >
        <TicketIcon class="quick-action-icon icon" />
        <span class="quick-action-text">New Ticket</span>
      </button>

      <!-- Pending tickets badge (admin / manager) -->
      <button
        v-if="pendingTicketsCount > 0 && showTicketsBadge"
        @click="$emit('go-to-tickets')"
        class="quick-action-button ticket-notification-badge"
      >
        <TicketIcon class="quick-action-icon icon" />
        <span class="quick-action-text">Tickets ({{ pendingTicketsCount }})</span>
      </button>

      <!-- Chat button -->
      <button
        @click="$emit('open-chat')"
        class="quick-action-button"
        :title="isChatOpen ? 'Close Chat' : 'Open Chat'"
      >
        <ChatBubbleLeftRightIcon class="quick-action-icon icon" />
        <span class="quick-action-text">Chat</span>
        <span v-if="unreadChatCount > 0 && !isChatOpen" class="notification-badge">
          {{ unreadChatCount }}
        </span>
      </button>

      <!-- Notification Bell -->
      <NotificationBell />

      <!-- Profile Dropdown -->
      <div class="profile-dropdown" ref="dropdownRef">
        <button class="profile-trigger" @click="toggleDropdown">
          <span class="user-avatar">{{ initials }}</span>
          <span class="user-name">{{ greeting }}, {{ displayName }}!</span>
          <ChevronDownIcon class="dropdown-arrow icon" :class="{ rotated: isDropdownOpen }" />
        </button>

        <transition name="dropdown">
          <div v-if="isDropdownOpen" class="profile-dropdown-menu">
            <!-- User info header -->
            <div class="dropdown-header">
              <span class="dropdown-avatar">{{ initials }}</span>
              <div class="dropdown-user-info">
                <span class="dropdown-user-name">{{ fullName }}</span>
                <span class="dropdown-user-email">{{ userEmail }}</span>
              </div>
            </div>
            <div class="dropdown-divider" />

            <!-- Profile link -->
            <router-link
              :to="`/${role}/profile`"
              class="dropdown-item"
              @click="closeDropdown"
            >
              <UserCircleIcon class="dropdown-icon icon" />
              My Profile
            </router-link>

            <!-- Tickets link -->
            <router-link
              :to="ticketsRoute"
              class="dropdown-item"
              @click="closeDropdown"
            >
              <TicketIcon class="dropdown-icon icon" />
              {{ role === 'employee' ? 'My Tickets' : 'Ticket Management' }}
              <span v-if="pendingTicketsCount > 0" class="dropdown-badge">
                {{ pendingTicketsCount }}
              </span>
            </router-link>

            <!-- Reports (employee only) -->
            <router-link
              v-if="role === 'employee'"
              to="/employee/charts"
              class="dropdown-item"
              @click="closeDropdown"
            >
              <ChartBarIcon class="dropdown-icon icon" />
              Reports &amp; Charts
            </router-link>

            <!-- Settings (admin / manager) -->
            <router-link
              v-if="role !== 'employee'"
              :to="`/${role}/settings`"
              class="dropdown-item"
              @click="closeDropdown"
            >
              <Cog6ToothIcon class="dropdown-icon icon" />
              Settings
            </router-link>

            <div class="dropdown-divider" />

            <button
              @click="handleLogout"
              class="dropdown-item logout-dropdown-item"
            >
              <ArrowLeftOnRectangleIcon class="dropdown-icon icon" />
              Logout
            </button>
          </div>
        </transition>
      </div>
    </div>
  </header>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import NotificationBell from '@/components/NotificationBell.vue'
import {
  TicketIcon,
  ChevronDownIcon,
  UserCircleIcon,
  ArrowLeftOnRectangleIcon,
  ChartBarIcon,
  Cog6ToothIcon,
  ChatBubbleLeftRightIcon,
} from '@heroicons/vue/24/outline'

export default {
  name: 'AppNavbar',
  components: {
    NotificationBell,
    TicketIcon,
    ChevronDownIcon,
    UserCircleIcon,
    ArrowLeftOnRectangleIcon,
    ChartBarIcon,
    Cog6ToothIcon,
    ChatBubbleLeftRightIcon,
  },
  props: {
    // 'admin' | 'manager' | 'employee'
    role: {
      type: String,
      required: true,
      validator: v => ['admin', 'manager', 'employee'].includes(v),
    },
    isSidebarOpen: {
      type: Boolean,
      default: false,
    },
    pendingTicketsCount: {
      type: Number,
      default: 0,
    },
    unreadChatCount: {
      type: Number,
      default: 0,
    },
    isChatOpen: {
      type: Boolean,
      default: false,
    },
  },
  emits: [
    'toggle-sidebar',
    'open-chat',
    'go-to-tickets',
    'create-ticket',
    'logout',
  ],
  setup(props, { emit }) {
    const router = useRouter()
    const route = useRoute()
    const authStore = useAuthStore()
    const isDropdownOpen = ref(false)
    const dropdownRef = ref(null)

    // ── Computed display values ───────────────────────────────────
    const pageTitle = computed(() => route.meta?.title || {
      admin: 'Admin Dashboard',
      manager: 'Manager Dashboard',
      employee: 'Welcome Back!',
    }[props.role])

    const fullName = computed(() =>
      authStore.user?.fullName || authStore.user?.name || {
        admin: 'Admin User',
        manager: 'Manager',
        employee: 'Employee',
      }[props.role]
    )

    const displayName = computed(() => fullName.value.split(' ')[0])

    const greeting = computed(() => ({
      admin: 'Hello',
      manager: 'Hello',
      employee: 'Hi',
    }[props.role]))

    const userEmail = computed(() => authStore.user?.email || '')

    const initials = computed(() =>
      fullName.value
        .split(' ')
        .map(w => w[0])
        .join('')
        .toUpperCase()
        .slice(0, 2)
    )

    // ── Role-specific flags ───────────────────────────────────────
    // Show "New Ticket" button only on the employee tickets page
    const showNewTicketButton = computed(() =>
      props.role === 'employee' && route.name === 'mytickets'
    )

    // Show ticket count badge in the header only for admin / manager
    const showTicketsBadge = computed(() =>
      props.role !== 'employee'
    )

    // Tickets route per role
    const ticketsRoute = computed(() => ({
      admin: '/admin/tickets',
      manager: '/manager/tickets',
      employee: { name: 'mytickets' },
    }[props.role]))

    // ── Dropdown ──────────────────────────────────────────────────
    const toggleDropdown = () => { isDropdownOpen.value = !isDropdownOpen.value }
    const closeDropdown = () => { isDropdownOpen.value = false }

    const handleLogout = async () => {
      closeDropdown()
      try {
        await authStore.logout()
      } catch {
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        localStorage.removeItem('selectedBusiness')
      }
      router.push('/auth/login')
    }

    // ── Click-outside ─────────────────────────────────────────────
    const handleClickOutside = (e) => {
      if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        closeDropdown()
      }
    }

    const handleEscape = (e) => {
      if (e.key === 'Escape') closeDropdown()
    }

    onMounted(() => {
      document.addEventListener('click', handleClickOutside)
      document.addEventListener('keydown', handleEscape)
    })
    onUnmounted(() => {
      document.removeEventListener('click', handleClickOutside)
      document.removeEventListener('keydown', handleEscape)
    })

    return {
      pageTitle,
      fullName,
      displayName,
      greeting,
      userEmail,
      initials,
      showNewTicketButton,
      showTicketsBadge,
      ticketsRoute,
      isDropdownOpen,
      dropdownRef,
      toggleDropdown,
      closeDropdown,
      handleLogout,
    }
  },
}
</script>

<style scoped>
@import '@/assets/css/shared-layout-styles.css';

/* ── Uniform icon sizing ──────────────────────────────────────── */
.icon {
  width: 1.25rem;
  height: 1.25rem;
  flex-shrink: 0;
}

/* ── Right-side actions row ───────────────────────────────────── */
.right-actions {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm, 0.5rem);
  margin-left: auto;
}

/* ── Dropdown arrow rotation ──────────────────────────────────── */
.dropdown-arrow.rotated {
  transform: rotate(180deg);
  transition: transform 0.2s ease;
}

/* ── Dropdown header block ────────────────────────────────────── */
.dropdown-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
}
.dropdown-avatar {
  width: 2rem;
  height: 2rem;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 0.8rem;
  background: var(--accent-color, #4f46e5);
  color: #fff;
  flex-shrink: 0;
}
.dropdown-user-info {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}
.dropdown-user-name {
  font-size: 0.875rem;
  font-weight: 600;
}
.dropdown-user-email {
  font-size: 0.75rem;
  opacity: 0.65;
}

/* ── Dropdown badge ───────────────────────────────────────────── */
.dropdown-badge {
  margin-left: auto;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.1rem 0.45rem;
  border-radius: 999px;
  background: var(--danger-color, #ef4444);
  color: #fff;
}
</style>