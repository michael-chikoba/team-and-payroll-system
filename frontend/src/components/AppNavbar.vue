<template>
  <header class="top-bar">
    <!-- ── Hamburger (mobile only) ── -->
    <button
      class="hamburger-menu"
      @click="toggleMobileMenu"
      :aria-expanded="mobileMenuOpen"
      aria-label="Toggle Menu"
    >
      <span :class="{ open: mobileMenuOpen }"></span>
      <span :class="{ open: mobileMenuOpen }"></span>
      <span :class="{ open: mobileMenuOpen }"></span>
    </button>

    <!-- ── Brand (mobile only) ── -->
    <div class="mobile-brand">
      <img src="@/assets/logo/PayLogo.png" alt="Payroll System Logo" class="mobile-logo" />
    </div>

    <!-- ── Right-side actions ── -->
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
            <div class="dropdown-header">
              <span class="dropdown-avatar">{{ initials }}</span>
              <div class="dropdown-user-info">
                <span class="dropdown-user-name">{{ fullName }}</span>
                <span class="dropdown-user-email">{{ userEmail }}</span>
              </div>
            </div>
            <div class="dropdown-divider" />

            <router-link :to="`/${role}/profile`" class="dropdown-item" @click="closeDropdown">
              <UserCircleIcon class="dropdown-icon icon" />
              My Profile
            </router-link>

            <router-link :to="ticketsRoute" class="dropdown-item" @click="closeDropdown">
              <TicketIcon class="dropdown-icon icon" />
              {{ role === 'employee' ? 'My Tickets' : 'Ticket Management' }}
              <span v-if="pendingTicketsCount > 0" class="dropdown-badge">{{ pendingTicketsCount }}</span>
            </router-link>

            <router-link v-if="role === 'employee'" to="/employee/charts" class="dropdown-item" @click="closeDropdown">
              <ChartBarIcon class="dropdown-icon icon" />
              Reports &amp; Charts
            </router-link>

            <router-link v-if="role !== 'employee'" :to="`/${role}/settings`" class="dropdown-item" @click="closeDropdown">
              <Cog6ToothIcon class="dropdown-icon icon" />
              Settings
            </router-link>

            <div class="dropdown-divider" />

            <button @click="handleLogout" class="dropdown-item logout-dropdown-item">
              <ArrowLeftOnRectangleIcon class="dropdown-icon icon" />
              Logout
            </button>
          </div>
        </transition>
      </div>
    </div>

    <!-- ── Mobile Nav Dropdown ── -->
    <div class="mobile-nav-menu" :class="{ open: mobileMenuOpen }">
      <div class="mobile-nav-container">

        <!-- Attendance toggle at the top of mobile menu -->
        <div class="mobile-attendance-section">
          <AttendanceToggle />
        </div>

        <div class="mobile-nav-divider" />

        <!-- Flat nav items for mobile (groups are expanded inline) -->
        <template v-for="item in flatNavItems" :key="item.to || item.key">
          <!-- Section label for groups -->
          <div v-if="item.type === 'section-label'" class="mobile-section-label">
            <component :is="item.icon" class="section-label-icon icon" />
            {{ item.label }}
          </div>

          <!-- Nav link -->
          <router-link
            v-else
            :to="item.to"
            class="mobile-nav-item"
            :class="{ 'is-child': item.isChild }"
            @click="closeMobileMenu"
          >
            <component :is="item.icon" class="mobile-nav-icon icon" />
            <span>{{ item.label }}</span>
            <span v-if="item.badge > 0" class="mobile-nav-badge">{{ item.badge }}</span>
          </router-link>
        </template>

        <div class="mobile-nav-divider" />

        <!-- Logout button at the bottom -->
        <button class="mobile-logout-btn" @click="handleLogout">
          <ArrowLeftOnRectangleIcon class="icon" />
          Logout
        </button>
      </div>
    </div>
  </header>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, shallowRef, markRaw, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useAdminPermissions } from '@/composables/useAdminPermissions'
import NotificationBell from '@/components/NotificationBell.vue'
import AttendanceToggle from '@/components/common/Toggle.vue'
import {
  TicketIcon, ChevronDownIcon, UserCircleIcon, ArrowLeftOnRectangleIcon,
  ChartBarIcon, Cog6ToothIcon, ChatBubbleLeftRightIcon, HomeIcon, UsersIcon,
  UserPlusIcon, BanknotesIcon, DocumentTextIcon, ClockIcon, CalendarDaysIcon,
  ClipboardDocumentListIcon, CalculatorIcon, ClipboardDocumentCheckIcon,
  GlobeAltIcon, BuildingOfficeIcon, BuildingLibraryIcon, PresentationChartLineIcon,
  BoltIcon, CalendarIcon, FolderOpenIcon, ShieldCheckIcon,
} from '@heroicons/vue/24/outline'

const icons = {
  TicketIcon: markRaw(TicketIcon), ChevronDownIcon: markRaw(ChevronDownIcon),
  UserCircleIcon: markRaw(UserCircleIcon), ArrowLeftOnRectangleIcon: markRaw(ArrowLeftOnRectangleIcon),
  ChartBarIcon: markRaw(ChartBarIcon), Cog6ToothIcon: markRaw(Cog6ToothIcon),
  ChatBubbleLeftRightIcon: markRaw(ChatBubbleLeftRightIcon), HomeIcon: markRaw(HomeIcon),
  UsersIcon: markRaw(UsersIcon), UserPlusIcon: markRaw(UserPlusIcon),
  BanknotesIcon: markRaw(BanknotesIcon), DocumentTextIcon: markRaw(DocumentTextIcon),
  ClockIcon: markRaw(ClockIcon), CalendarDaysIcon: markRaw(CalendarDaysIcon),
  ClipboardDocumentListIcon: markRaw(ClipboardDocumentListIcon), CalculatorIcon: markRaw(CalculatorIcon),
  ClipboardDocumentCheckIcon: markRaw(ClipboardDocumentCheckIcon), GlobeAltIcon: markRaw(GlobeAltIcon),
  BuildingOfficeIcon: markRaw(BuildingOfficeIcon), BuildingLibraryIcon: markRaw(BuildingLibraryIcon),
  PresentationChartLineIcon: markRaw(PresentationChartLineIcon), BoltIcon: markRaw(BoltIcon),
  CalendarIcon: markRaw(CalendarIcon), FolderOpenIcon: markRaw(FolderOpenIcon),
  ShieldCheckIcon: markRaw(ShieldCheckIcon),
}

export default {
  name: 'AppNavbar',
  components: { NotificationBell, AttendanceToggle, ...icons },

  props: {
    role: { type: String, required: true, validator: v => ['admin', 'manager', 'employee'].includes(v) },
    isSidebarOpen: { type: Boolean, default: false },
    pendingTicketsCount: { type: Number, default: 0 },
    unreadChatCount: { type: Number, default: 0 },
    isChatOpen: { type: Boolean, default: false },
    canAccessRestrictedPages: { type: Boolean, default: false },
  },
  emits: ['toggle-sidebar', 'open-chat', 'go-to-tickets', 'create-ticket', 'logout'],

  setup(props, { emit }) {
    const router = useRouter()
    const route = useRoute()
    const authStore = useAuthStore()
    const isDropdownOpen = ref(false)
    const mobileMenuOpen = ref(false)
    const dropdownRef = ref(null)

    // ── Permissions (for admin mobile nav) ────────────────────────
    const permissionsState = shallowRef({
      canAddEmployee: false, canViewPayroll: false, canViewPayslip: false,
      canManageAdmins: false, isSuspended: false, fetched: false
    })

    const currentBusinessId = computed(() => authStore.currentBusinessId)

    const loadPermissions = async (businessId) => {
      if (!businessId || props.role !== 'admin') return
      try {
        const permissions = useAdminPermissions(businessId)
        await permissions.waitForFetch()
        permissionsState.value = {
          canAddEmployee: permissions.canAddEmployee?.value ?? false,
          canViewPayroll: permissions.canViewPayroll?.value ?? false,
          canViewPayslip: permissions.canViewPayslip?.value ?? false,
          canManageAdmins: permissions.canManageAdmins?.value ?? false,
          isSuspended: permissions.isSuspended?.value ?? false,
          fetched: true
        }
      } catch (e) { console.error('Failed to load permissions:', e) }
    }

    watch(currentBusinessId, async (newId) => { if (newId) await loadPermissions(newId) }, { immediate: true })

    // ── User display ──────────────────────────────────────────────
    const fullName = computed(() =>
      authStore.user?.fullName || authStore.user?.name || { admin: 'Admin User', manager: 'Manager', employee: 'Employee' }[props.role]
    )
    const displayName = computed(() => fullName.value.split(' ')[0])
    const greeting = computed(() => ({ admin: 'Hello', manager: 'Hello', employee: 'Hi' }[props.role]))
    const userEmail = computed(() => authStore.user?.email || '')
    const initials = computed(() => fullName.value.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2))

    // ── Role flags ────────────────────────────────────────────────
    const showNewTicketButton = computed(() => props.role === 'employee' && route.name === 'mytickets')
    const showTicketsBadge = computed(() => props.role !== 'employee')
    const ticketsRoute = computed(() => ({
      admin: '/admin/tickets', manager: '/manager/tickets', employee: { name: 'mytickets' }
    }[props.role]))

    // ── Flat nav items for mobile menu ────────────────────────────
    // Groups become section labels + indented children
    const flatNavItems = computed(() => {
      const { canAddEmployee, canViewPayroll, canViewPayslip, canManageAdmins, isSuspended } = permissionsState.value
      const items = []

      if (props.role === 'admin') {
        items.push(
          { to: '/admin/dashboard', icon: icons.HomeIcon, label: 'Dashboard' },
          { to: '/admin/audit-logs', icon: icons.ClipboardDocumentCheckIcon, label: 'Audit Logs' }
        )
        if (canManageAdmins && !isSuspended) {
          items.push({ to: '/admin/admin-manager', icon: icons.ShieldCheckIcon, label: 'Admin Manager' })
        }

        // Employee Management section
        items.push({ type: 'section-label', icon: icons.UsersIcon, label: 'Employee Management' })
        items.push({ to: '/admin/employees', icon: icons.UsersIcon, label: 'Employees', isChild: true })
        if (canViewPayroll && !isSuspended) items.push({ to: '/admin/payroll', icon: icons.BanknotesIcon, label: 'Payroll', isChild: true })
        if (canViewPayslip && !isSuspended) items.push({ to: '/admin/payslips', icon: icons.DocumentTextIcon, label: 'Payslips', isChild: true })
        if (!isSuspended) {
          items.push(
            { to: '/admin/attendance', icon: icons.ClockIcon, label: 'Attendance', isChild: true },
            { to: '/admin/leaves', icon: icons.CalendarDaysIcon, label: 'Leave Management', isChild: true }
          )
        }

        if (!isSuspended) {
          // Service Management section
          items.push({ type: 'section-label', icon: icons.ClipboardDocumentListIcon, label: 'Service Management' })
          items.push(
            { to: '/admin/Tasks', icon: icons.ClipboardDocumentListIcon, label: 'Tasks Management', isChild: true },
            { to: '/admin/tickets', icon: icons.TicketIcon, label: 'Ticket Management', badge: props.pendingTicketsCount, isChild: true },
            { to: '/admin/productivity', icon: icons.BoltIcon, label: 'Productivity Monitor', isChild: true },
            { to: '/admin/reports', icon: icons.ChartBarIcon, label: 'Reports', isChild: true }
          )

          // Business Settings section
          items.push({ type: 'section-label', icon: icons.BuildingOfficeIcon, label: 'Business Settings' })
          items.push(
            { to: '/admin/settings', icon: icons.Cog6ToothIcon, label: 'Settings', isChild: true },
            { to: '/admin/businesses', icon: icons.BuildingOfficeIcon, label: 'Business Management', isChild: true },
            { to: '/admin/business-groups', icon: icons.BuildingLibraryIcon, label: 'Business Groups', isChild: true },
            { to: '/admin/countries', icon: icons.GlobeAltIcon, label: 'Country Management', isChild: true },
            { to: '/admin/tax', icon: icons.CalculatorIcon, label: 'Tax Configuration', isChild: true }
          )
        }

        if (props.canAccessRestrictedPages && !isSuspended) {
          items.push({ type: 'section-label', icon: icons.ShieldCheckIcon, label: 'Super Admin' })
          if (canAddEmployee) items.push({ to: '/admin/employ', icon: icons.UserPlusIcon, label: 'Add Employees', isChild: true })
          items.push(
            { to: '/admin/demo-requests', icon: icons.PresentationChartLineIcon, label: 'Demo Requests', isChild: true },
            { to: '/admin/contact-requests', icon: icons.ChatBubbleLeftRightIcon, label: 'Contact Requests', isChild: true }
          )
        }
      }

      if (props.role === 'manager') {
        items.push(
          { to: '/manager/dashboard', icon: icons.HomeIcon, label: 'Dashboard' },
          { to: '/manager/schedule', icon: icons.CalendarDaysIcon, label: 'Team Schedule' },
          { to: '/manager/employees', icon: icons.UsersIcon, label: 'Employees' },
          { to: '/manager/attendance', icon: icons.ClockIcon, label: 'Attendance' },
          { to: '/manager/leave-approvals', icon: icons.ClipboardDocumentCheckIcon, label: 'Approvals' },
          { to: '/manager/tickets', icon: icons.TicketIcon, label: 'Tickets', badge: props.pendingTicketsCount },
          { to: '/manager/reports', icon: icons.PresentationChartLineIcon, label: 'Reports' },
          { to: '/manager/productivity', icon: icons.BoltIcon, label: 'Productivity' },
          { to: '/manager/payslips', icon: icons.BanknotesIcon, label: 'Payslips' },
          { to: '/manager/tasks', icon: icons.ClipboardDocumentListIcon, label: 'Assign Tasks' },
          { to: '/manager/shifts', icon: icons.ClockIcon, label: 'Assign Shifts' }
        )
      }

      if (props.role === 'employee') {
        items.push(
          { to: '/employee/dashboard', icon: icons.HomeIcon, label: 'Dashboard' },
          { to: '/employee/attendance', icon: icons.ClockIcon, label: 'Attendance' },
          { to: '/employee/leaves', icon: icons.CalendarDaysIcon, label: 'Leaves' },
          { to: '/employee/apply-leave', icon: icons.DocumentTextIcon, label: 'Apply Leave' },
          { to: '/employee/payslips', icon: icons.BanknotesIcon, label: 'Payslips' },
          { to: { name: 'TaskBoard' }, icon: icons.FolderOpenIcon, label: 'Tasks' },
          { to: { name: 'EmployeeSchedules' }, icon: icons.CalendarIcon, label: 'Schedules' },
          { to: { name: 'myshifts' }, icon: icons.ClockIcon, label: 'My Shifts' },
          { to: { name: 'mytickets' }, icon: icons.TicketIcon, label: 'Tickets', badge: props.pendingTicketsCount },
          { to: { name: 'charts' }, icon: icons.ChartBarIcon, label: 'Reports' }
        )
      }

      return items
    })

    // ── Mobile menu ────────────────────────────────────────────────
    const toggleMobileMenu = () => { mobileMenuOpen.value = !mobileMenuOpen.value }
    const closeMobileMenu = () => { mobileMenuOpen.value = false }

    // ── Profile dropdown ──────────────────────────────────────────
    const toggleDropdown = () => { isDropdownOpen.value = !isDropdownOpen.value }
    const closeDropdown = () => { isDropdownOpen.value = false }

    const handleLogout = async () => {
      closeDropdown()
      closeMobileMenu()
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
      if (dropdownRef.value && !dropdownRef.value.contains(e.target)) closeDropdown()
      // Close mobile menu if clicking outside the header
      const header = document.querySelector('.top-bar')
      if (mobileMenuOpen.value && header && !header.contains(e.target)) closeMobileMenu()
    }

    const handleEscape = (e) => {
      if (e.key === 'Escape') { closeDropdown(); closeMobileMenu() }
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
      fullName, displayName, greeting, userEmail, initials,
      showNewTicketButton, showTicketsBadge, ticketsRoute,
      isDropdownOpen, dropdownRef, toggleDropdown, closeDropdown, handleLogout,
      mobileMenuOpen, toggleMobileMenu, closeMobileMenu,
      flatNavItems,
      ChevronDownIcon: icons.ChevronDownIcon,
    }
  },
}
</script>

<style scoped>
@import '@/assets/css/shared-layout-styles.css';

/* ── Top bar shell ────────────────────────────────────────────── */
.top-bar {
  position: relative; /* needed for absolute mobile dropdown */
}

/* ── Icons ───────────────────────────────────────────────────── */
.icon { width: 1.25rem; height: 1.25rem; flex-shrink: 0; }

/* ── Right-side actions ───────────────────────────────────────── */
.right-actions {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm, 0.5rem);
  margin-left: auto;
}

/* ── Hamburger — mobile only ──────────────────────────────────── */
.hamburger-menu {
  display: none;
  flex-direction: column;
  justify-content: center;
  gap: 5px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.4rem;
  border-radius: 6px;
  flex-shrink: 0;
}

.hamburger-menu span {
  width: 22px;
  height: 2px;
  background: #4b5563;
  display: block;
  border-radius: 2px;
  transition: all 0.24s cubic-bezier(0.4, 0, 0.2, 1);
}

.hamburger-menu span.open:nth-child(1) { transform: translateY(7px) rotate(45deg);  background: #0047AB; }
.hamburger-menu span.open:nth-child(2) { opacity: 0; transform: scaleX(0); }
.hamburger-menu span.open:nth-child(3) { transform: translateY(-7px) rotate(-45deg); background: #0047AB; }

/* ── Mobile brand logo — mobile only ─────────────────────────── */
.mobile-brand {
  display: none;
  align-items: center;
}

.mobile-logo {
  height: 32px;
  width: auto;
  object-fit: contain;
}

/* ── Mobile nav dropdown ──────────────────────────────────────── */
.mobile-nav-menu {
  display: none;
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  overflow: hidden;
  max-height: 0;
  transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  background: rgba(255, 255, 255, 0.99);
  border-top: 1px solid #dde3ee;
  box-shadow: 0 8px 32px rgba(0, 31, 91, 0.1);
  z-index: 999;
}

.mobile-nav-menu.open { max-height: 85vh; overflow-y: auto; }

.mobile-nav-container {
  padding: 0.75rem 1.25rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0;
}

/* Attendance section at top of mobile nav */
.mobile-attendance-section {
  padding: 0.75rem 0.5rem;
}

.mobile-nav-divider {
  height: 1px;
  background: #f0f4f8;
  margin: 0.5rem 0;
}

/* Section labels (for group headers) */
.mobile-section-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.85rem 0.5rem 0.4rem;
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: #0047AB;
  margin-top: 0.25rem;
}

.mobile-section-label .section-label-icon {
  width: 0.95rem;
  height: 0.95rem;
  color: #0047AB;
}

/* Nav items */
.mobile-nav-item {
  text-decoration: none;
  color: #4b5563;
  font-weight: 500;
  padding: 0.8rem 0.5rem;
  font-size: 0.97rem;
  border-bottom: 1px solid #f0f4f8;
  transition: color 0.2s, background 0.2s;
  display: flex;
  align-items: center;
  gap: 0.65rem;
  border-radius: 6px;
}

.mobile-nav-item svg { color: #0047AB; flex-shrink: 0; }

.mobile-nav-item:last-of-type { border-bottom: none; }

.mobile-nav-item:hover,
.mobile-nav-item.router-link-active {
  color: #0047AB;
  background: rgba(0, 71, 171, 0.04);
}

/* Child items are indented */
.mobile-nav-item.is-child {
  padding-left: 1.75rem;
  font-size: 0.92rem;
}

.mobile-nav-badge {
  margin-left: auto;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.1rem 0.45rem;
  border-radius: 999px;
  background: #ef4444;
  color: #fff;
}

/* Logout button */
.mobile-logout-btn {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  width: 100%;
  padding: 0.85rem 0.5rem;
  background: none;
  border: none;
  cursor: pointer;
  color: #ef4444;
  font-weight: 600;
  font-size: 0.97rem;
  border-radius: 6px;
  transition: background 0.2s;
  margin-top: 0.25rem;
}

.mobile-logout-btn:hover { background: rgba(239, 68, 68, 0.06); }
.mobile-logout-btn svg { color: #ef4444; }

/* ── Profile dropdown ────────────────────────────────────────── */
.dropdown-arrow.rotated {
  transform: rotate(180deg);
  transition: transform 0.2s ease;
}

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

.dropdown-user-info { display: flex; flex-direction: column; gap: 0.1rem; }
.dropdown-user-name { font-size: 0.875rem; font-weight: 600; }
.dropdown-user-email { font-size: 0.75rem; opacity: 0.65; }

.dropdown-badge {
  margin-left: auto;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.1rem 0.45rem;
  border-radius: 999px;
  background: var(--danger-color, #ef4444);
  color: #fff;
}

/* ── Responsive breakpoint ────────────────────────────────────── */
@media (max-width: 991px) {
  /* Show hamburger and mobile brand */
  .hamburger-menu { display: flex; }
  .mobile-brand   { display: flex; }

  /* Show mobile dropdown */
  .mobile-nav-menu { display: block; }

  /* Hide desktop-only user name in profile trigger on very small screens */
  .user-name { display: none; }
}

@media (min-width: 992px) {
  /* Hide hamburger and mobile menu on desktop — sidebar handles nav */
  .hamburger-menu  { display: none; }
  .mobile-brand    { display: none; }
  .mobile-nav-menu { display: none !important; }
}
</style>