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
          :class="{ 'is-expanded': expandedGroups[item.key], 'is-active': item.isActive }"
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
                <span v-if="child.badge > 0" class="nav-badge">{{ child.badge }}</span>
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
          <span v-if="item.badge > 0" class="nav-badge">{{ item.badge }}</span>
        </router-link>
      </template>
    </nav>

    <div class="sidebar-footer">
      <slot name="footer" />
    </div>
  </aside>
</template>

<script>
import { ref, computed, watch, shallowRef, markRaw } from 'vue'
import { useRoute } from 'vue-router'
import AttendanceToggle from '@/components/common/Toggle.vue'
import { useAdminPermissions } from '@/composables/useAdminPermissions'
import { useAuthStore } from '@/stores/auth'
import {
  BriefcaseIcon, TicketIcon, ChevronDownIcon, HomeIcon, UsersIcon, UserPlusIcon,
  BanknotesIcon, DocumentTextIcon, ClockIcon, CalendarDaysIcon, ClipboardDocumentListIcon,
  CalculatorIcon, ChartBarIcon, ClipboardDocumentCheckIcon, GlobeAltIcon, Cog6ToothIcon,
  BuildingOfficeIcon, BuildingLibraryIcon, ChatBubbleLeftRightIcon, PresentationChartLineIcon,
  ListBulletIcon, RocketLaunchIcon, DocumentChartBarIcon, BoltIcon, CalendarIcon,
  FolderOpenIcon, ShieldCheckIcon, AcademicCapIcon, BookOpenIcon,
} from '@heroicons/vue/24/outline'

const icons = {
  BriefcaseIcon: markRaw(BriefcaseIcon), TicketIcon: markRaw(TicketIcon),
  ChevronDownIcon: markRaw(ChevronDownIcon), HomeIcon: markRaw(HomeIcon),
  UsersIcon: markRaw(UsersIcon), UserPlusIcon: markRaw(UserPlusIcon),
  BanknotesIcon: markRaw(BanknotesIcon), DocumentTextIcon: markRaw(DocumentTextIcon),
  ClockIcon: markRaw(ClockIcon), CalendarDaysIcon: markRaw(CalendarDaysIcon),
  ClipboardDocumentListIcon: markRaw(ClipboardDocumentListIcon), CalculatorIcon: markRaw(CalculatorIcon),
  ChartBarIcon: markRaw(ChartBarIcon), ClipboardDocumentCheckIcon: markRaw(ClipboardDocumentCheckIcon),
  GlobeAltIcon: markRaw(GlobeAltIcon), Cog6ToothIcon: markRaw(Cog6ToothIcon),
  BuildingOfficeIcon: markRaw(BuildingOfficeIcon), BuildingLibraryIcon: markRaw(BuildingLibraryIcon),
  ChatBubbleLeftRightIcon: markRaw(ChatBubbleLeftRightIcon),
  PresentationChartLineIcon: markRaw(PresentationChartLineIcon),
  ListBulletIcon: markRaw(ListBulletIcon), RocketLaunchIcon: markRaw(RocketLaunchIcon),
  DocumentChartBarIcon: markRaw(DocumentChartBarIcon), BoltIcon: markRaw(BoltIcon),
  CalendarIcon: markRaw(CalendarIcon), FolderOpenIcon: markRaw(FolderOpenIcon),
  ShieldCheckIcon: markRaw(ShieldCheckIcon), AcademicCapIcon: markRaw(AcademicCapIcon),
  BookOpenIcon: markRaw(BookOpenIcon),
}

export default {
  name: 'AppSidebar',
  components: { AttendanceToggle, ...icons },
  props: {
    role: { type: String, required: true, validator: v => ['admin', 'manager', 'employee'].includes(v) },
    sidebarClasses: { type: [String, Object, Array], default: '' },
    pendingTicketsCount: { type: Number, default: 0 },
    unreadChatCount: { type: Number, default: 0 },
    isChatOpen: { type: Boolean, default: false },
    canAccessRestrictedPages: { type: Boolean, default: false },
  },
  emits: ['nav-click', 'open-chat'],

  setup(props, { emit }) {
    const route = useRoute()
    const authStore = useAuthStore()
    const expandedGroups = ref({})

    const permissionsState = shallowRef({
      canAddEmployee: false, canViewPayroll: false, canViewPayslip: false,
      canManageAdmins: false, isSuspended: false, fetched: false
    })

    const currentBusinessId = computed(() => authStore.currentBusinessId)

    const loadPermissions = async (businessId) => {
      if (!businessId) return
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
      } catch (error) { console.error('Failed to load permissions:', error) }
    }

    watch(currentBusinessId, async (newId) => { if (newId) await loadPermissions(newId) }, { immediate: true })

    const toggleGroup = (key) => {
      expandedGroups.value = { ...expandedGroups.value, [key]: !expandedGroups.value[key] }
    }

    const handleNavClick = () => emit('nav-click')

    const baseAdminNav = [
      { to: '/admin/dashboard', icon: icons.HomeIcon, label: 'Dashboard' },
      { to: '/admin/audit-logs', icon: icons.ClipboardDocumentCheckIcon, label: 'Audit Logs' }
    ]

    const adminNav = computed(() => {
      const { canAddEmployee, canViewPayroll, canViewPayslip, canManageAdmins, isSuspended, fetched } = permissionsState.value
      if (!fetched) return baseAdminNav

      const items = [...baseAdminNav]
      const currentPath = route.path
      const isPathActive = (paths) => paths.some(p => currentPath.startsWith(p))

      if (canManageAdmins && !isSuspended) {
        items.push({ to: '/admin/admin-manager', icon: icons.ShieldCheckIcon, label: 'Admin Manager' })
      }

      const employeeChildren = [{ to: '/admin/employees', icon: icons.UsersIcon, label: 'Employees' }]
      if (canViewPayroll && !isSuspended) employeeChildren.push({ to: '/admin/payroll', icon: icons.BanknotesIcon, label: 'Payroll' })
      if (canViewPayslip && !isSuspended) employeeChildren.push({ to: '/admin/payslips', icon: icons.DocumentTextIcon, label: 'Payslips' })
      if (!isSuspended) {
        employeeChildren.push(
          { to: '/admin/attendance', icon: icons.ClockIcon, label: 'Attendance' },
          { to: '/admin/leaves', icon: icons.CalendarDaysIcon, label: 'Leave Management' }
        )
      }
      items.push({
        type: 'group', key: 'employee-management', icon: icons.UsersIcon, label: 'Employee Management',
        isActive: isPathActive(['/admin/employees', '/admin/payroll', '/admin/payslips', '/admin/leaves', '/admin/attendance']),
        children: employeeChildren,
      })

      if (!isSuspended) {
        items.push({
          type: 'group', key: 'service-management', icon: icons.ClipboardDocumentListIcon, label: 'Service Management',
          isActive: isPathActive(['/admin/Tasks', '/admin/tickets', '/admin/productivity', '/admin/reports']),
          children: [
            { to: '/admin/Tasks', icon: icons.ClipboardDocumentListIcon, label: 'Tasks Management' },
            { to: '/admin/tickets', icon: icons.TicketIcon, label: 'Ticket Management', badge: props.pendingTicketsCount },
            { to: '/admin/productivity', icon: icons.BoltIcon, label: 'Productivity Monitor' },
            { to: '/admin/reports', icon: icons.ChartBarIcon, label: 'Reports' },
          ],
        })

        items.push({
          type: 'group', key: 'business-settings', icon: icons.BuildingOfficeIcon, label: 'Business Settings',
          isActive: isPathActive(['/admin/settings', '/admin/business-groups', '/admin/businesses', '/admin/countries', '/admin/tax']),
          children: [
            { to: '/admin/settings', icon: icons.Cog6ToothIcon, label: 'Settings' },
            { to: '/admin/businesses', icon: icons.BuildingOfficeIcon, label: 'Business Management' },
            { to: '/admin/business-groups', icon: icons.BuildingLibraryIcon, label: 'Business Groups' },
            { to: '/admin/countries', icon: icons.GlobeAltIcon, label: 'Country Management' },
            { to: '/admin/tax', icon: icons.CalculatorIcon, label: 'Tax Configuration' },
          ],
        })

        // Learning Center Group for Admin with multiple sub-pages
        items.push({
          type: 'group', key: 'learning-center', icon: icons.AcademicCapIcon, label: 'Learning Center',
          isActive: isPathActive(['/admin/learning', '/admin/learning/manage', '/admin/learning/reports']),
          children: [
            { to: '/admin/learning', icon: icons.BookOpenIcon, label: 'Course Catalog' },
            { to: '/admin/learning/manage', icon: icons.AcademicCapIcon, label: 'Manage Courses' },
            { to: '/admin/learning/reports', icon: icons.ChartBarIcon, label: 'Learning Reports' },
            { to: '/admin/learning/my-progress', icon: icons.ClipboardDocumentListIcon, label: 'My Progress' },
          ],
        })
      }

      if (props.canAccessRestrictedPages && !isSuspended) {
        const superChildren = []
        if (canAddEmployee) superChildren.push({ to: '/admin/employ', icon: icons.UserPlusIcon, label: 'Add Employees' })
        superChildren.push(
          { to: '/admin/demo-requests', icon: icons.PresentationChartLineIcon, label: 'Demo Requests' },
          { to: '/admin/contact-requests', icon: icons.ChatBubbleLeftRightIcon, label: 'Contact Requests' },
        )
        if (superChildren.length > 0) {
          items.push({
            type: 'group', key: 'super-admin', icon: icons.ShieldCheckIcon, label: 'Super Admin',
            isActive: isPathActive(['/admin/employ', '/admin/demo-requests', '/admin/contact-requests']),
            children: superChildren,
          })
        }
      }

      return items
    })

    const managerNav = computed(() => {
      const currentPath = route.path
      const isPathActive = (paths) => paths.some(p => currentPath.startsWith(p))
      
      const items = [
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
        { to: '/manager/shifts', icon: icons.ClockIcon, label: 'Assign Shifts' },
      ]
      
      // Learning Center Group for Manager
      items.push({
        type: 'group', key: 'learning-center-manager', icon: icons.AcademicCapIcon, label: 'Learning Center',
        isActive: isPathActive(['/manager/learning', '/manager/learning/reports']),
        children: [
          { to: '/manager/learning', icon: icons.BookOpenIcon, label: 'Course Catalog' },
          { to: '/manager/learning/reports', icon: icons.ChartBarIcon, label: 'Team Reports' },
          { to: '/manager/learning/my-progress', icon: icons.ClipboardDocumentListIcon, label: 'My Progress' },
        ],
      })
      
      return items
    })

    const employeeNav = computed(() => {
      const currentPath = route.path
      const isPathActive = (paths) => paths.some(p => currentPath.startsWith(p))
      
      const items = [
        { to: '/employee/dashboard', icon: icons.HomeIcon, label: 'Dashboard' },
        { to: '/employee/attendance', icon: icons.ClockIcon, label: 'Attendance' },
        { to: '/employee/leaves', icon: icons.CalendarDaysIcon, label: 'Leaves' },
        { to: '/employee/apply-leave', icon: icons.DocumentTextIcon, label: 'Apply Leave' },
        { to: '/employee/payslips', icon: icons.BanknotesIcon, label: 'Payslips' },
        { to: { name: 'TaskBoard' }, icon: icons.FolderOpenIcon, label: 'Tasks' },
        { to: { name: 'EmployeeSchedules' }, icon: icons.CalendarIcon, label: 'Schedules' },
        { to: { name: 'myshifts' }, icon: icons.ClockIcon, label: 'My Shifts' },
        { to: { name: 'mytickets' }, icon: icons.TicketIcon, label: 'Tickets', badge: props.pendingTicketsCount },
        { to: { name: 'charts' }, icon: icons.ChartBarIcon, label: 'Reports' },
      ]
      
      // Learning Center Group for Employee
      items.push({
        type: 'group', key: 'learning-center-employee', icon: icons.AcademicCapIcon, label: 'Learning Center',
        isActive: isPathActive(['/employee/learning']),
        children: [
          { to: '/employee/learning', icon: icons.BookOpenIcon, label: 'Course Catalog' },
          { to: '/employee/learning/my-progress', icon: icons.ClipboardDocumentListIcon, label: 'My Progress' },
        ],
      })
      
      return items
    })

    const navItems = computed(() => {
      switch (props.role) {
        case 'admin': return adminNav.value
        case 'manager': return managerNav.value
        case 'employee': return employeeNav.value
        default: return []
      }
    })

    return { navItems, expandedGroups, toggleGroup, handleNavClick, ChevronDownIcon: icons.ChevronDownIcon }
  },
}
</script>

<style scoped>
@import '@/assets/css/shared-layout-styles.css';

/* ── Sidebar: desktop only ─────────────────────────────────────── */
.sidebar {
  display: flex;
  flex-direction: column;
}

/* On mobile the navbar hamburger handles navigation — hide sidebar */
@media (max-width: 991px) {
  .sidebar {
    display: none !important;
  }
}

/* ── Logo ────────────────────────────────────────────────────── */
.logo-section {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 1rem 0.75rem;
}

.logo-image {
  width: auto;
  height: 48px;
  max-width: 85%;
  object-fit: contain;
  flex-shrink: 0;
  filter: drop-shadow(0px 2px 6px rgba(0, 0, 0, 0.12));
  transition: transform 0.3s ease;
}

.logo-image:hover { transform: scale(1.04); }

/* ── Icons ───────────────────────────────────────────────────── */
.icon { width: 1.1rem; height: 1.1rem; flex-shrink: 0; }

/* ── Nav wrapper ─────────────────────────────────────────────── */
.nav { padding: 0.75rem 0.5rem; }

/* ── Expand animation ────────────────────────────────────────── */
.expand-enter-active,
.expand-leave-active {
  transition: max-height 0.25s ease-in-out, opacity 0.25s ease-in-out;
  max-height: 400px;
  overflow: hidden;
}
.expand-enter-from, .expand-leave-to { max-height: 0; opacity: 0; }

/* ── Footer ──────────────────────────────────────────────────── */
.sidebar-footer {
  margin-top: auto;
  padding: 0.875rem 0.75rem 1.125rem;
  border-top: 1px solid var(--sidebar-border);
  flex-shrink: 0;
}

/* ── Scrollable nav ──────────────────────────────────────────── */
.scrollable-nav {
  flex: 1;
  overflow-y: auto;
  scrollbar-width: thin;
  scrollbar-color: rgba(0, 0, 0, 0.1) transparent;
}
.scrollable-nav::-webkit-scrollbar { width: 3px; }
.scrollable-nav::-webkit-scrollbar-track { background: transparent; }
.scrollable-nav::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.1); border-radius: 3px; }
.scrollable-nav::-webkit-scrollbar-thumb:hover { background: rgba(0, 0, 0, 0.2); }

/* ── Learning Center specific styles ─────────────────────────── */
.nav-item .nav-icon,
.nav-group-item .sub-icon {
  transition: all 0.2s ease;
}

.nav-item:hover .nav-icon,
.nav-group-item:hover .sub-icon {
  transform: scale(1.05);
}

/* Active state for Learning Center items */
.nav-item.router-link-active .nav-icon,
.nav-group-item.router-link-active .sub-icon {
  color: var(--primary-color, #4f46e5);
}

.nav-item.router-link-active .nav-text,
.nav-group-item.router-link-active .sub-text {
  font-weight: 600;
  color: var(--primary-color, #4f46e5);
}

/* Group active styling */
.nav-group.is-active .nav-group-button .nav-text {
  color: var(--primary-color, #4f46e5);
  font-weight: 600;
}

/* Learning Center group styling */
.nav-group[data-key="learning-center"] .nav-group-button .nav-icon,
.nav-group[data-key="learning-center-manager"] .nav-group-button .nav-icon,
.nav-group[data-key="learning-center-employee"] .nav-group-button .nav-icon {
  color: #4f46e5;
}

/* Badge styling */
.nav-badge {
  background: #ef4444;
  color: white;
  font-size: 0.65rem;
  font-weight: 600;
  padding: 0.125rem 0.375rem;
  border-radius: 10px;
  margin-left: auto;
  min-width: 18px;
  text-align: center;
}

/* Super admin badge */
.super-admin-badge {
  background: linear-gradient(135deg, #f59e0b, #ef4444);
  color: white;
  font-size: 0.6rem;
  font-weight: 600;
  padding: 0.125rem 0.375rem;
  border-radius: 4px;
  margin-left: 0.5rem;
  white-space: nowrap;
}
</style>