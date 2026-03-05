<template>
  <aside class="sidebar" :class="sidebarClasses">
    <!-- Logo -->
    <div class="logo-section">
      <img src="@/assets/logo/PayLogo.png" alt="Payroll System Logo" class="logo-image" />
    </div>

    <!-- Attendance Toggle -->
    <div class="attendance-toggle-section">
      <AttendanceToggle />
    </div>

    <!-- Navigation -->
    <nav class="nav scrollable-nav">
      <template v-for="item in navItems" :key="item.to || item.key">
        <!-- Group item (dropdown) -->
        <div
          v-if="item.type === 'group'"
          class="nav-group"
          :class="{ 'is-expanded': expandedGroups[item.key], 'is-active': item.isActive?.() }"
        >
          <button
            class="nav-group-button"
            @click="toggleGroup(item.key)"
            aria-haspopup="true"
            :aria-expanded="expandedGroups[item.key]"
          >
            <div class="nav-link-content">
              <component :is="item.icon" class="nav-icon icon" />
              <span class="nav-text">{{ item.label }}</span>
            </div>
            <ChevronDownIcon
              class="dropdown-chevron icon"
              :class="{ 'rotate-180': expandedGroups[item.key] }"
            />
          </button>
          <transition name="expand">
            <div v-show="expandedGroups[item.key]" class="nav-group-menu">
              <router-link
                v-for="child in item.children"
                :key="child.to"
                :to="child.to"
                class="nav-group-item"
                @click="handleNavClick"
              >
                <component :is="child.icon" class="sub-icon icon" />
                <span class="sub-text">{{ child.label }}</span>
                <span v-if="child.badge?.() > 0" class="nav-badge">{{ child.badge() }}</span>
              </router-link>
            </div>
          </transition>
        </div>

        <!-- Standard router-link -->
        <router-link
          v-else
          :to="item.to"
          class="nav-item"
          active-class="active"
          @click="handleNavClick"
        >
          <component :is="item.icon" class="nav-icon icon" />
          <span class="nav-text">{{ item.label }}</span>
          <span v-if="item.superAdmin" class="super-admin-badge">Super Admin</span>
          <span v-if="item.badge?.() > 0" class="nav-badge">{{ item.badge() }}</span>
        </router-link>
      </template>
    </nav>

    <div class="sidebar-footer">
      <slot name="footer" />
    </div>
  </aside>
</template>

<script>
import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import AttendanceToggle from '@/components/common/Toggle.vue'
import { useAdminPermissions } from '@/composables/useAdminPermissions'
import { useAuthStore } from '@/stores/auth'
import {
  BriefcaseIcon,
  TicketIcon,
  ChevronDownIcon,
  HomeIcon,
  UsersIcon,
  UserPlusIcon,
  BanknotesIcon,
  DocumentTextIcon,
  ClockIcon,
  CalendarDaysIcon,
  ClipboardDocumentListIcon,
  CalculatorIcon,
  ChartBarIcon,
  ClipboardDocumentCheckIcon,
  GlobeAltIcon,
  Cog6ToothIcon,
  BuildingOfficeIcon,
  BuildingLibraryIcon,
  ChatBubbleLeftRightIcon,
  PresentationChartLineIcon,
  ListBulletIcon,
  RocketLaunchIcon,
  DocumentChartBarIcon,
  BoltIcon,
  CalendarIcon,
  FolderOpenIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline'

export default {
  name: 'AppSidebar',

  components: {
    AttendanceToggle,
    BriefcaseIcon, TicketIcon, ChevronDownIcon, HomeIcon, UsersIcon, UserPlusIcon,
    BanknotesIcon, DocumentTextIcon, ClockIcon, CalendarDaysIcon, ClipboardDocumentListIcon,
    CalculatorIcon, ChartBarIcon, ClipboardDocumentCheckIcon, GlobeAltIcon, Cog6ToothIcon,
    BuildingOfficeIcon, BuildingLibraryIcon, ChatBubbleLeftRightIcon, PresentationChartLineIcon,
    ListBulletIcon, RocketLaunchIcon, DocumentChartBarIcon, BoltIcon, CalendarIcon, FolderOpenIcon,
    ShieldCheckIcon,
  },

  props: {
    role: {
      type: String,
      required: true,
      validator: v => ['admin', 'manager', 'employee'].includes(v),
    },
    sidebarClasses: {
      type: [String, Object, Array],
      default: '',
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
    canAccessRestrictedPages: {
      type: Boolean,
      default: false,
    },
  },

  emits: ['nav-click', 'open-chat', 'toggle-sidebar'],

  setup(props, { emit }) {
    const route = useRoute()
    const authStore = useAuthStore()
    const expandedGroups = ref({})
    const recomputeKey = ref(0)

    const user = computed(() => authStore.user)
    const currentBusinessId = computed(() => authStore.currentBusinessId)

    const permissions = useAdminPermissions(currentBusinessId.value)

    const {
      flags,
      canAddEmployee,
      canViewPayroll,
      canViewPayslip,
      canManageAdmins,
      isSuspended,
      refresh,
      fetched,
      fetching,
      fetchError,
    } = permissions

    watch([flags, canAddEmployee, canViewPayroll, canViewPayslip, canManageAdmins, isSuspended, fetchError],
      ([newFlags, add, payroll, payslip, manage, suspended, error]) => {
        console.log('🔄 Sidebar permissions updated:', {
          flags: newFlags,
          canAddEmployee: add,
          canViewPayroll: payroll,
          canViewPayslip: payslip,
          canManageAdmins: manage,
          isSuspended: suspended,
          error: error,
          businessId: currentBusinessId.value,
          fetched: fetched.value,
        })
    }, { immediate: true, deep: true })

    watch(currentBusinessId, (newId, oldId) => {
      if (newId && newId !== oldId) {
        refresh(newId)
      }
    })

    watch(user, (newUser, oldUser) => {
      if (newUser?.id !== oldUser?.id) {
        refresh(currentBusinessId.value)
      }
    }, { deep: true })

    const toggleGroup = (key) => {
      expandedGroups.value[key] = !expandedGroups.value[key]
    }

    const handleNavClick = () => emit('nav-click')

    const logoTitle = computed(() => ({
      admin:    'Payroll System',
      manager:  'Manager Hub',
      employee: 'Portal',
    }[props.role]))

    const adminNav = computed(() => {
      recomputeKey.value

      const hasManageAdmins = canManageAdmins?.value ?? false
      const hasViewPayroll = canViewPayroll?.value ?? false
      const hasViewPayslip = canViewPayslip?.value ?? false
      const hasAddEmployee = canAddEmployee?.value ?? false
      const isUserSuspended = isSuspended?.value ?? false

      const items = []

      items.push(
        { to: '/admin/dashboard', icon: HomeIcon, label: 'Dashboard' },
        { to: '/admin/audit-logs', icon: ClipboardDocumentCheckIcon, label: 'Audit Logs' }
      )

      if (hasManageAdmins && !isUserSuspended) {
        items.push({
          to: '/admin/admin-manager',
          icon: ShieldCheckIcon,
          label: 'Admin Manager',
        })
      }

      const employeeChildren = []

      employeeChildren.push({ to: '/admin/employees', icon: UsersIcon, label: 'Employees' })

      if (hasViewPayroll && !isUserSuspended) {
        employeeChildren.push({ to: '/admin/payroll', icon: BanknotesIcon, label: 'Payroll' })
      }

      if (hasViewPayslip && !isUserSuspended) {
        employeeChildren.push({ to: '/admin/payslips', icon: DocumentTextIcon, label: 'Payslips' })
      }

      if (!isUserSuspended) {
        employeeChildren.push(
          { to: '/admin/attendance', icon: ClockIcon, label: 'Attendance' },
          { to: '/admin/leaves', icon: CalendarDaysIcon, label: 'Leave Management' }
        )
      }

      if (employeeChildren.length > 0) {
        items.push({
          type: 'group',
          key: 'employee-management',
          icon: UsersIcon,
          label: 'Employee Management',
          isActive: () => [
            '/admin/employees', '/admin/payroll', '/admin/payslips',
            '/admin/leaves', '/admin/attendance',
          ].some(p => route.path.startsWith(p)),
          children: employeeChildren,
        })
      }

      if (!isUserSuspended) {
        items.push({
          type: 'group',
          key: 'service-management',
          icon: ClipboardDocumentListIcon,
          label: 'Service Management',
          isActive: () => [
            '/admin/Tasks', '/admin/tickets', '/admin/productivity', '/admin/reports',
          ].some(p => route.path.startsWith(p)),
          children: [
            { to: '/admin/Tasks', icon: ClipboardDocumentListIcon, label: 'Tasks Management' },
            {
              to: '/admin/tickets',
              icon: TicketIcon,
              label: 'Ticket Management',
              badge: () => props.pendingTicketsCount
            },
            { to: '/admin/productivity', icon: BoltIcon, label: 'Productivity Monitor' },
            { to: '/admin/reports', icon: ChartBarIcon, label: 'Reports' },
          ],
        })
      }

      if (!isUserSuspended) {
        items.push({
          type: 'group',
          key: 'business-settings',
          icon: BuildingOfficeIcon,
          label: 'Business Settings',
          isActive: () => [
            '/admin/settings', '/admin/business-groups', '/admin/businesses',
            '/admin/countries', '/admin/tax',
          ].some(p => route.path.startsWith(p)),
          children: [
            { to: '/admin/settings', icon: Cog6ToothIcon, label: 'Settings' },
            { to: '/admin/businesses', icon: BuildingOfficeIcon, label: 'Business Management' },
            { to: '/admin/business-groups', icon: BuildingLibraryIcon, label: 'Business Groups' },
            { to: '/admin/countries', icon: GlobeAltIcon, label: 'Country Management' },
            { to: '/admin/tax', icon: CalculatorIcon, label: 'Tax Configuration' },
          ],
        })
      }

      if (props.canAccessRestrictedPages && !isUserSuspended) {
        const superChildren = []

        if (hasAddEmployee) {
          superChildren.push({ to: '/admin/employ', icon: UserPlusIcon, label: 'Add Employees' })
        }

        superChildren.push(
          { to: '/admin/demo-requests', icon: PresentationChartLineIcon, label: 'Demo Requests' },
          { to: '/admin/contact-requests', icon: ChatBubbleLeftRightIcon, label: 'Contact Requests' },
        )

        if (superChildren.length > 0) {
          items.push({
            type: 'group',
            key: 'super-admin',
            icon: ShieldCheckIcon,
            label: 'Super Admin',
            isActive: () => [
              '/admin/employ', '/admin/demo-requests', '/admin/contact-requests',
            ].some(p => route.path.startsWith(p)),
            children: superChildren,
          })
        }
      }

      return items
    })

    const managerNav = computed(() => [
      { to: '/manager/dashboard', icon: HomeIcon, label: 'Dashboard' },
      { to: '/manager/schedule', icon: CalendarDaysIcon, label: 'Team Schedule' },
      { to: '/manager/employees', icon: UsersIcon, label: 'Employees' },
      { to: '/manager/attendance', icon: ClockIcon, label: 'Attendance' },
      { to: '/manager/leave-approvals', icon: ClipboardDocumentCheckIcon, label: 'Approvals' },
      {
        to: '/manager/tickets',
        icon: TicketIcon,
        label: 'Tickets',
        badge: () => props.pendingTicketsCount
      },
      { to: '/manager/reports', icon: PresentationChartLineIcon, label: 'Reports' },
      { to: '/manager/productivity', icon: BoltIcon, label: 'Productivity' },
      { to: '/manager/payslips', icon: BanknotesIcon, label: 'Payslips' },
      { to: '/manager/tasks', icon: ClipboardDocumentListIcon, label: 'Assign Tasks' },
      { to: '/manager/shifts', icon: ClockIcon, label: 'Assign Shifts' },
    ])

    const employeeNav = computed(() => [
      { to: '/employee/dashboard', icon: HomeIcon, label: 'Dashboard' },
      { to: '/employee/attendance', icon: ClockIcon, label: 'Attendance' },
      { to: '/employee/leaves', icon: CalendarDaysIcon, label: 'Leaves' },
      { to: '/employee/apply-leave', icon: DocumentTextIcon, label: 'Apply Leave' },
      { to: '/employee/payslips', icon: BanknotesIcon, label: 'Payslips' },
      { to: { name: 'TaskBoard' }, icon: FolderOpenIcon, label: 'Tasks' },
      { to: { name: 'EmployeeSchedules' }, icon: CalendarIcon, label: 'Schedules' },
      { to: { name: 'myshifts' }, icon: ClockIcon, label: 'My Shifts' },
      {
        to: { name: 'mytickets' },
        icon: TicketIcon,
        label: 'Tickets',
        badge: () => props.pendingTicketsCount
      },
      { to: { name: 'charts' }, icon: ChartBarIcon, label: 'Reports' },
    ])

    const navItems = computed(() => ({
      admin: adminNav.value,
      manager: managerNav.value,
      employee: employeeNav.value,
    }[props.role]))

    return {
      props,
      navItems,
      logoTitle,
      expandedGroups,
      toggleGroup,
      handleNavClick,
      ChevronDownIcon,
    }
  },
}
</script>

<style scoped>
@import '@/assets/css/shared-layout-styles.css';

/* ─────────────────────────────────────────────────────────────────
   LOGO SECTION
   Only layout/sizing overrides — no color overrides that fight
   the shared white-sidebar theme.
───────────────────────────────────────────────────────────────── */
.logo-section {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 1rem 0.75rem;
  position: relative;
  /* Border is handled by shared CSS */
}

.logo-image {
  width: auto;
  height: 48px;
  max-width: 85%;
  object-fit: contain;
  flex-shrink: 0;
  filter: drop-shadow(0px 2px 6px rgba(0, 0, 0, 0.12));
  image-rendering: auto;
  transition: transform 0.3s ease;
}

.logo-image:hover {
  transform: scale(1.04);
}

/* ─────────────────────────────────────────────────────────────────
   ICON SIZING
   Keep the .icon class for size only — color comes from shared CSS
───────────────────────────────────────────────────────────────── */
.icon {
  width: 1.1rem;
  height: 1.1rem;
  flex-shrink: 0;
}

/* ─────────────────────────────────────────────────────────────────
   NAV WRAPPER
   Padding/gap only — everything else from shared CSS
───────────────────────────────────────────────────────────────── */
.nav {
  padding: 0.75rem 0.5rem;
}

/* ─────────────────────────────────────────────────────────────────
   EXPAND ANIMATION
───────────────────────────────────────────────────────────────── */
.expand-enter-active,
.expand-leave-active {
  transition: max-height 0.25s ease-in-out, opacity 0.25s ease-in-out;
  max-height: 400px;
  overflow: hidden;
}

.expand-enter-from,
.expand-leave-to {
  max-height: 0;
  opacity: 0;
}

/* ─────────────────────────────────────────────────────────────────
   SIDEBAR FOOTER
───────────────────────────────────────────────────────────────── */
.sidebar-footer {
  margin-top: auto;
  padding: 0.875rem 0.75rem 1.125rem;
  border-top: 1px solid var(--sidebar-border);
  flex-shrink: 0;
}

/* ─────────────────────────────────────────────────────────────────
   SCROLLABLE NAV
───────────────────────────────────────────────────────────────── */
.scrollable-nav {
  flex: 1;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: rgba(0, 0, 0, 0.1) transparent;
}

.scrollable-nav::-webkit-scrollbar {
  width: 3px;
}

.scrollable-nav::-webkit-scrollbar-track {
  background: transparent;
}

.scrollable-nav::-webkit-scrollbar-thumb {
  background: rgba(0, 0, 0, 0.1);
  border-radius: 3px;
}

.scrollable-nav::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.2);
}
</style>