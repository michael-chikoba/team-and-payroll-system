<template>
  <div class="admin-manager">

    <!-- ── Sticky Header ──────────────────────────────────────────── -->
    <div class="fixed-header">
      <div class="management-header-card">
        <div class="header-inner">
          <div class="user-greeting">
            <div class="avatar-section">
              <div class="avatar">
                <ShieldCheckIcon style="width:22px;height:22px;color:white;" />
              </div>
              <div class="user-info">
                <h1 class="greeting">Admin Manager</h1>
                <p class="subtitle">Manage administrator accounts, roles &amp; access restrictions</p>
                <div class="role-meta">
                  <span class="role-badge">Admin View</span>
                  <span class="month-badge">{{ admins.length }} Admin{{ admins.length !== 1 ? 's' : '' }}</span>
                </div>
              </div>
            </div>

            <div class="header-controls">
              <div class="view-toggle">
                <button type="button" :class="['toggle-btn', viewMode === 'grid' ? 'active' : '']" @click="viewMode = 'grid'" title="Grid View">
                  <Squares2X2Icon style="width:14px;height:14px;" />
                </button>
                <button type="button" :class="['toggle-btn', viewMode === 'table' ? 'active' : '']" @click="viewMode = 'table'" title="Table View">
                  <TableCellsIcon style="width:14px;height:14px;" />
                </button>
              </div>

              <button @click="fetchAdmins" class="btn-icon-hdr" title="Refresh">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M23 4v6h-6"/><path d="M1 20v-6h6"/>
                  <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                </svg>
              </button>
            </div>
          </div>

          <div class="stats-badge">
            <div class="stats-content">
              <span class="stats-primary">{{ admins.length }}</span>
              <div class="stats-details">
                <span class="stats-label">Total</span>
                <span class="stats-total">Admins</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ── Scrollable Content ─────────────────────────────────────── -->
    <div class="page-content">

      <div v-if="loading" class="state-banner loading">
        <div class="spinner" />
        <span>Loading administrators…</span>
      </div>

      <div v-else-if="error" class="state-banner error">
        {{ error }}
        <button @click="fetchAdmins" class="btn-text">Retry</button>
      </div>

      <div v-else class="management-content">

        <div v-if="admins.length === 0" class="empty-state">
          <div class="empty-icon">
            <UsersIcon style="width:48px;height:48px;color:#94a3b8;" />
          </div>
          <h3>No Administrators Found</h3>
          <p>Add admins via the Business Management page.</p>
        </div>

        <template v-else>

          <!-- ══ GRID VIEW ══ -->
          <div v-if="viewMode === 'grid'" class="admins-grid">
            <div
              v-for="admin in admins"
              :key="admin.user_id"
              class="admin-card"
              :class="{ 'is-suspended': admin.is_suspended }"
            >
              <div class="card-stripe" :class="`stripe-${admin.role}`" />

              <div class="card-header">
                <div class="admin-avatar-wrap">
                  <img v-if="admin.profile_photo_path" :src="admin.profile_photo_path" :alt="admin.name" />
                  <span v-else class="avatar-initials">{{ initials(admin.name) }}</span>
                </div>
                <div class="admin-info">
                  <div class="name-row">
                    <h3 class="admin-name">{{ admin.name }}</h3>
                    <span v-if="admin.is_suspended" class="tag tag-suspended">
                      <NoSymbolIcon style="width:0.65rem;height:0.65rem;" /> Suspended
                    </span>
                  </div>
                  <p class="admin-email">{{ admin.email }}</p>
                  <div class="admin-meta">
                    <span class="role-chip" :class="`chip-${admin.role}`">{{ admin.role }}</span>
                    <span v-if="admin.is_primary" class="role-chip chip-primary">Primary</span>
                    <span v-if="admin.position" class="meta-text">{{ admin.position }}</span>
                  </div>
                </div>
              </div>

              <!-- Restrictions -->
              <div class="card-body">
                <div class="section-head">
                  <LockClosedIcon style="width:0.75rem;height:0.75rem;" />
                  <span>Access Restrictions</span>
                  <span class="sh-hint">ON = restricted</span>
                </div>
                <div class="perm-grid">
                  <div
                    v-for="perm in RESTRICTION_FLAGS"
                    :key="perm.key"
                    class="perm-tile"
                    :class="{ restricted: admin[perm.key] }"
                  >
                    <div class="perm-tile-top">
                      <component :is="perm.icon" style="width:1rem;height:1rem;" />
                      <button
                        class="perm-toggle"
                        :class="{ active: admin[perm.key] }"
                        :disabled="saving[admin.user_id]"
                        @click="toggleFlag(admin, perm.key)"
                      >
                        <span class="perm-thumb" />
                      </button>
                    </div>
                    <span class="perm-tile-label">{{ perm.label }}</span>
                  </div>
                </div>
              </div>

              <!-- Role -->
              <div class="card-body card-body-row">
                <div class="section-head" style="margin-bottom:0;flex-shrink:0;">
                  <UserCircleIcon style="width:0.75rem;height:0.75rem;" />
                  <span>Role</span>
                </div>
                <div class="role-pills">
                  <button
                    v-for="r in ROLES"
                    :key="r.value"
                    class="role-pill"
                    :class="{ active: admin.role === r.value, [`pill-${r.value}`]: admin.role === r.value }"
                    :disabled="saving[admin.user_id] || admin.user_id === currentUserId"
                    @click="changeRole(admin, r.value)"
                  >{{ r.label }}</button>
                </div>
              </div>

              <!-- Actions -->
              <div class="card-actions">
                <button
                  v-if="!admin.is_suspended"
                  class="btn-action btn-suspend"
                  :disabled="saving[admin.user_id] || admin.user_id === currentUserId"
                  @click="openSuspendModal(admin)"
                >
                  <NoSymbolIcon style="width:15px;height:15px;" />
                  <span>Suspend</span>
                </button>
                <button
                  v-else
                  class="btn-action btn-reinstate"
                  :disabled="saving[admin.user_id]"
                  @click="unsuspend(admin)"
                >
                  <CheckCircleIcon style="width:15px;height:15px;" />
                  <span>Reinstate</span>
                </button>

                <p v-if="admin.is_suspended && admin.suspension_reason" class="suspension-note">
                  {{ admin.suspension_reason }}
                </p>

                <div v-if="saving[admin.user_id]" class="saving-pill">
                  <div class="mini-spin" /> Saving…
                </div>
              </div>
            </div>
          </div>

          <!-- ══ TABLE VIEW ══ -->
          <div v-else class="table-wrapper">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Administrator</th>
                  <th>Role</th>
                  <th class="th-center">Add Employees</th>
                  <th class="th-center">View Payroll</th>
                  <th class="th-center">View Payslips</th>
                  <th class="th-center">Manage Admins</th>
                  <th class="th-center">Status</th>
                  <th class="th-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="admin in admins"
                  :key="admin.user_id"
                  :class="{ 'row-suspended': admin.is_suspended }"
                >
                  <td class="td-admin">
                    <div class="table-avatar">
                      <img v-if="admin.profile_photo_path" :src="admin.profile_photo_path" :alt="admin.name" />
                      <span v-else>{{ initials(admin.name) }}</span>
                    </div>
                    <div class="td-info">
                      <span class="td-name">{{ admin.name }}</span>
                      <span class="td-email">{{ admin.email }}</span>
                      <span v-if="admin.position" class="td-pos">{{ admin.position }}</span>
                    </div>
                  </td>

                  <td>
                    <div class="role-pills role-pills-sm">
                      <button
                        v-for="r in ROLES"
                        :key="r.value"
                        class="role-pill"
                        :class="{ active: admin.role === r.value, [`pill-${r.value}`]: admin.role === r.value }"
                        :disabled="saving[admin.user_id] || admin.user_id === currentUserId"
                        @click="changeRole(admin, r.value)"
                      >{{ r.label }}</button>
                    </div>
                  </td>

                  <td v-for="perm in RESTRICTION_FLAGS" :key="perm.key" class="td-center">
                    <button
                      class="perm-toggle perm-toggle-sm"
                      :class="{ active: admin[perm.key] }"
                      :disabled="saving[admin.user_id]"
                      @click="toggleFlag(admin, perm.key)"
                      :title="admin[perm.key] ? 'Remove restriction' : 'Apply restriction'"
                    >
                      <span class="perm-thumb" />
                    </button>
                  </td>

                  <td class="td-center">
                    <span v-if="admin.is_suspended" class="status-badge status-suspended">Suspended</span>
                    <span v-else class="status-badge status-active">Active</span>
                  </td>

                  <td class="td-center">
                    <div class="table-actions">
                      <button
                        v-if="!admin.is_suspended"
                        class="tbl-btn tbl-suspend"
                        :disabled="saving[admin.user_id] || admin.user_id === currentUserId"
                        @click="openSuspendModal(admin)"
                        title="Suspend admin"
                      >
                        <NoSymbolIcon style="width:14px;height:14px;" />
                      </button>
                      <button
                        v-else
                        class="tbl-btn tbl-reinstate"
                        :disabled="saving[admin.user_id]"
                        @click="unsuspend(admin)"
                        title="Reinstate admin"
                      >
                        <CheckCircleIcon style="width:14px;height:14px;" />
                      </button>
                      <div v-if="saving[admin.user_id]" class="mini-spin" />
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

        </template>
      </div>
    </div>

    <!-- ── Suspend Modal ──────────────────────────────────────────── -->
    <Transition name="modal">
      <div v-if="suspendModal.open" class="modal-overlay" @click.self="closeSuspendModal">
        <div class="modal-content">
          <div class="modal-header">
            <div style="display:flex;align-items:center;gap:0.875rem;">
              <div class="modal-icon-wrap">
                <NoSymbolIcon style="width:1.25rem;height:1.25rem;color:#ef4444;" />
              </div>
              <div>
                <h2 class="modal-title">Suspend Administrator</h2>
                <p class="modal-sub">{{ suspendModal.admin?.name }}</p>
              </div>
            </div>
            <button @click="closeSuspendModal" class="close-btn">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
          </div>
          <div class="modal-body">
            <p class="modal-desc">
              This admin will lose access to all admin functions for this business until reinstated.
              Their account is <strong>not deleted</strong>.
            </p>
            <div class="form-group">
              <label class="form-label">Reason <span class="optional">(optional)</span></label>
              <textarea
                v-model="suspendModal.reason"
                class="form-textarea"
                rows="3"
                placeholder="e.g. Policy violation, temporary access revocation…"
              />
            </div>
          </div>
          <div class="modal-footer">
            <button class="btn-secondary" @click="closeSuspendModal">Cancel</button>
            <button class="btn-danger-solid" :disabled="suspendModal.loading" @click="confirmSuspend">
              <span v-if="suspendModal.loading" style="display:flex;align-items:center;gap:0.35rem;">
                <div class="mini-spin light" /> Suspending…
              </span>
              <span v-else>Confirm Suspend</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- ── Toast ─────────────────────────────────────────────────── -->
    <Transition name="toast">
      <div v-if="toast.show" class="toast" :class="`toast-${toast.type}`">
        <CheckCircleIcon v-if="toast.type === 'success'" style="width:1rem;height:1rem;flex-shrink:0;" />
        <ExclamationTriangleIcon v-else style="width:1rem;height:1rem;flex-shrink:0;" />
        {{ toast.message }}
      </div>
    </Transition>

  </div>
</template>
<script>
import { ref, reactive, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useAdminPermissions } from '@/composables/useAdminPermissions'
import axios from 'axios'
import {
  ShieldCheckIcon,
  UsersIcon,
  NoSymbolIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  UserPlusIcon,
  BanknotesIcon,
  DocumentTextIcon,
  Cog6ToothIcon,
  LockClosedIcon,
  UserCircleIcon,
  Squares2X2Icon,
  TableCellsIcon,
} from '@heroicons/vue/24/outline'

const RESTRICTION_FLAGS = [
  { key: 'cannot_add_employee',  label: 'Add Employees', icon: UserPlusIcon     },
  { key: 'cannot_view_payroll',  label: 'View Payroll',  icon: BanknotesIcon    },
  { key: 'cannot_view_payslip',  label: 'View Payslips', icon: DocumentTextIcon },
  { key: 'cannot_manage_admins', label: 'Manage Admins', icon: Cog6ToothIcon    },
]

const ROLES = [
  { value: 'owner',   label: 'Owner'   },
  { value: 'admin',   label: 'Admin'   },
  { value: 'manager', label: 'Manager' },
]

export default {
  name: 'AdminManager',
  components: {
    ShieldCheckIcon, UsersIcon, NoSymbolIcon, CheckCircleIcon,
    ExclamationTriangleIcon, UserPlusIcon, BanknotesIcon,
    DocumentTextIcon, Cog6ToothIcon, LockClosedIcon, UserCircleIcon,
    Squares2X2Icon, TableCellsIcon,
  },

  setup() {
    const authStore = useAuthStore()
    
    // 🔍 DEBUG: Log current user
    console.log('🔍 Current user:', {
      id: authStore.user?.id,
      email: authStore.user?.email,
      businessId: authStore.currentBusinessId
    })

    const { refresh: refreshMyPermissions, flags: myPermissions } = useAdminPermissions(authStore.currentBusinessId)

    // 🔍 DEBUG: Watch my permissions
    watch(() => myPermissions.value, (newPerms) => {
      console.log('🔍 My permissions updated:', {
        userId: authStore.user?.id,
        email: authStore.user?.email,
        permissions: newPerms
      })
    }, { immediate: true, deep: true })

    const admins     = ref([])
    const loading    = ref(false)
    const error      = ref(null)
    const businessId = ref(null)
    const saving     = reactive({})
    const viewMode   = ref('grid')

    const toast        = reactive({ show: false, type: 'success', message: '' })
    const suspendModal = reactive({ open: false, admin: null, reason: '', loading: false })

    const currentUserId = computed(() => authStore.user?.id)

    const initials = (name) =>
      (name || '?').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase()

    let toastTimer = null
    const showToast = (message, type = 'success') => {
      clearTimeout(toastTimer)
      Object.assign(toast, { show: true, type, message })
      toastTimer = setTimeout(() => { toast.show = false }, 3500)
    }

    const patchLocal = (userId, patch) => {
      const idx = admins.value.findIndex(a => a.user_id === userId)
      if (idx !== -1) Object.assign(admins.value[idx], patch)
    }

    const fetchAdmins = async () => {
      loading.value = true
      error.value   = null
      try {
        const params = authStore.currentBusinessId ? { business_id: authStore.currentBusinessId } : {}
        console.log('🔍 Fetching admins with params:', params)
        
        const { data } = await axios.get('/api/admin/admin-permissions', { params })
        
        console.log('🔍 Admins response:', {
          business_id: data.business_id,
          adminCount: data.data.length,
          currentUser: data.data.find(a => a.email === authStore.user?.email)
        })
        
        admins.value     = data.data
        businessId.value = data.business_id
        
        // 🔍 DEBUG: Check if current user has restrictions
        const currentUserAdmin = admins.value.find(a => a.email === authStore.user?.email)
        if (currentUserAdmin) {
          console.log('🔍 Current user in admins list:', {
            id: currentUserAdmin.user_id,
            email: currentUserAdmin.email,
            restrictions: {
              cannot_add_employee: currentUserAdmin.cannot_add_employee,
              cannot_view_payroll: currentUserAdmin.cannot_view_payroll,
              cannot_view_payslip: currentUserAdmin.cannot_view_payslip,
              cannot_manage_admins: currentUserAdmin.cannot_manage_admins,
              is_suspended: currentUserAdmin.is_suspended
            }
          })
        }
      } catch (e) {
        console.error('🔍 Error fetching admins:', e)
        error.value = e.response?.data?.message || 'Failed to load administrators.'
      } finally {
        loading.value = false
      }
    }

    const toggleFlag = async (admin, key) => {
      console.log('🔍 Toggling flag:', { userId: admin.user_id, key, currentValue: admin[key] })
      
      const newValue = !admin[key]
      patchLocal(admin.user_id, { [key]: newValue })
      saving[admin.user_id] = true
      try {
        await axios.put(`/api/admin/admin-permissions/${admin.user_id}`, {
          business_id: businessId.value, [key]: newValue,
        })
        showToast(`${newValue ? '🔒 Restricted' : '🔓 Unrestricted'}: ${admin.name}`)
        
        // If this is the current user, refresh permissions
        if (admin.user_id === currentUserId.value) {
          console.log('🔍 Refreshing current user permissions after toggle')
          await refreshMyPermissions(businessId.value)
        }
      } catch (e) {
        console.error('🔍 Error toggling flag:', e)
        patchLocal(admin.user_id, { [key]: !newValue })
        showToast(e.response?.data?.message || 'Failed to update permission.', 'error')
      } finally {
        saving[admin.user_id] = false
      }
    }

    const changeRole = async (admin, role) => {
      if (admin.role === role) return
      const oldRole = admin.role
      patchLocal(admin.user_id, { role })
      saving[admin.user_id] = true
      try {
        await axios.patch(`/api/admin/admin-permissions/${admin.user_id}/role`, {
          business_id: businessId.value, role,
        })
        showToast(`Role updated to "${role}" for ${admin.name}.`)
      } catch (e) {
        patchLocal(admin.user_id, { role: oldRole })
        showToast(e.response?.data?.message || 'Failed to change role.', 'error')
      } finally {
        saving[admin.user_id] = false
      }
    }

    const openSuspendModal  = (admin) => { suspendModal.admin = admin; suspendModal.reason = ''; suspendModal.open = true }
    const closeSuspendModal = () => { suspendModal.open = false; suspendModal.admin = null }

    const confirmSuspend = async () => {
      suspendModal.loading = true
      const admin = suspendModal.admin
      try {
        await axios.post(`/api/admin/admin-permissions/${admin.user_id}/suspend`, {
          business_id: businessId.value, reason: suspendModal.reason || null,
        })
        patchLocal(admin.user_id, { is_suspended: true, suspension_reason: suspendModal.reason || null })
        showToast(`${admin.name} has been suspended.`)
        closeSuspendModal()
        if (admin.user_id === currentUserId.value) refreshMyPermissions(businessId.value)
      } catch (e) {
        showToast(e.response?.data?.message || 'Failed to suspend admin.', 'error')
      } finally {
        suspendModal.loading = false
      }
    }

    const unsuspend = async (admin) => {
      saving[admin.user_id] = true
      try {
        await axios.post(`/api/admin/admin-permissions/${admin.user_id}/unsuspend`, {
          business_id: businessId.value,
        })
        patchLocal(admin.user_id, { is_suspended: false, suspension_reason: null })
        showToast(`${admin.name} has been reinstated.`)
        if (admin.user_id === currentUserId.value) refreshMyPermissions(businessId.value)
      } catch (e) {
        showToast(e.response?.data?.message || 'Failed to reinstate admin.', 'error')
      } finally {
        saving[admin.user_id] = false
      }
    }

    onMounted(fetchAdmins)

    return {
      admins, loading, error, saving, businessId, viewMode,
      toast, suspendModal, currentUserId,
      RESTRICTION_FLAGS, ROLES,
      initials, fetchAdmins,
      toggleFlag, changeRole,
      openSuspendModal, closeSuspendModal, confirmSuspend, unsuspend,
    }
  },
}
</script>
<style scoped>
/* ── Base ─────────────────────────────────────────────────────────── */
.admin-manager {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  font-family: 'Inter', system-ui, sans-serif;
  color: #1e293b;
}

/* ── Sticky Header ────────────────────────────────────────────────── */
.fixed-header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(248, 250, 252, 0.9);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  padding: 1.5rem 2rem 0;
  box-sizing: border-box;
  box-shadow: 0 1px 0 rgba(0,0,0,0.04);
}

.management-header-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem 1.75rem;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05), 0 1px 2px -1px rgba(0,0,0,0.03);
  border: 1px solid #e2e8f0;
  margin-bottom: 1.25rem;
  position: relative;
  overflow: hidden;
}
.management-header-card::after {
  content: '';
  position: absolute;
  top: -20px; right: -20px;
  width: 160px; height: 160px;
  background: radial-gradient(circle, rgba(79,70,229,0.06) 0%, transparent 70%);
  pointer-events: none;
}

.header-inner   { display: flex; justify-content: space-between; align-items: center; gap: 1.5rem; flex-wrap: wrap; }
.user-greeting  { display: flex; align-items: center; gap: 2rem; flex: 1; flex-wrap: wrap; }
.avatar-section { display: flex; align-items: center; gap: 1rem; }

.avatar {
  width: 52px; height: 52px;
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  border-radius: 14px;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 12px rgba(79,70,229,0.25);
  flex-shrink: 0;
}

.user-info  { display: flex; flex-direction: column; gap: 0.2rem; }
.greeting   { margin: 0; font-size: 1.375rem; font-weight: 700; color: #1e293b; line-height: 1.2; }
.subtitle   { margin: 0; color: #64748b; font-size: 0.875rem; }
.role-meta  { display: flex; align-items: center; gap: 0.5rem; margin-top: 0.125rem; }
.role-badge  { background: #eef2ff; border: 1px solid #c7d2fe; padding: 0.125rem 0.6rem; border-radius: 8px; font-size: 0.7rem; font-weight: 600; color: #4338ca; }
.month-badge { background: #ede9fe; border: 1px solid #ddd6fe; padding: 0.125rem 0.6rem; border-radius: 8px; font-size: 0.7rem; font-weight: 600; color: #6d28d9; }

.header-controls { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; }

.view-toggle { display: flex; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; flex-shrink: 0; }
.toggle-btn { width: 36px; height: 36px; border: none; background: white; color: #64748b; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s; }
.toggle-btn:first-child { border-right: 1px solid #e2e8f0; }
.toggle-btn.active { background: #4f46e5; color: white; }
.toggle-btn:not(.active):hover { background: #f8fafc; color: #1e293b; }

.btn-icon-hdr { width: 38px; height: 38px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; color: #475569; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; flex-shrink: 0; }
.btn-icon-hdr:hover { background: #f1f5f9; border-color: #cbd5e1; color: #1e293b; }

.stats-badge   { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 0.75rem 1.125rem; min-width: 100px; flex-shrink: 0; }
.stats-content { display: flex; align-items: center; gap: 0.75rem; }
.stats-primary { font-size: 1.5rem; font-weight: 700; color: #4f46e5; line-height: 1; }
.stats-details { display: flex; flex-direction: column; }
.stats-label   { font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.02em; }
.stats-total   { font-size: 0.7rem; color: #94a3b8; }

/* ── Page Content ─────────────────────────────────────────────────── */
.page-content {
  flex: 1;
  padding: 0 2rem 3rem;
  max-width: 1400px;
  width: 100%;
  margin: 0 auto;
  box-sizing: border-box;
}

/* ── State Banners ────────────────────────────────────────────────── */
.state-banner { padding: 1.5rem; border-radius: 12px; text-align: center; margin-bottom: 1.75rem; display: flex; align-items: center; justify-content: center; gap: 0.75rem; }
.state-banner.error   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.state-banner.loading { background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe; }
.spinner { width: 28px; height: 28px; border: 3px solid #c7d2fe; border-top-color: #4f46e5; border-radius: 50%; animation: spin 1s linear infinite; }
.btn-text { background: none; border: none; color: #4f46e5; font-weight: 600; cursor: pointer; margin-left: 0.75rem; }
.btn-text:hover { text-decoration: underline; }

/* ── Management Content ───────────────────────────────────────────── */
.management-content { display: flex; flex-direction: column; gap: 1.75rem; }

/* ── Empty State ──────────────────────────────────────────────────── */
.empty-state { text-align: center; padding: 4rem 2rem; background: white; border-radius: 16px; border: 1px solid #e2e8f0; }
.empty-icon  { width: 80px; height: 80px; margin: 0 auto 1.5rem; background: #f8fafc; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px dashed #e2e8f0; }
.empty-state h3 { margin: 0 0 0.5rem; font-size: 1.25rem; color: #1e293b; }
.empty-state p  { margin: 0; color: #64748b; }

/* ══════════════════════════════════════════════════════════════════
   GRID VIEW
══════════════════════════════════════════════════════════════════ */
.admins-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
  gap: 1.5rem;
}

.admin-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  box-shadow: 0 2px 4px -1px rgba(0,0,0,0.05);
  transition: transform 0.2s, box-shadow 0.2s;
}
.admin-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px -8px rgba(0,0,0,0.12); }
.admin-card.is-suspended { border-color: #fecaca; background: #fff8f8; }

.card-header {
  display: flex; align-items: flex-start; gap: 0.875rem;
  padding: 1.25rem 1.5rem 1rem;
  background: #fcfcfc;
  border-bottom: 1px solid #f1f5f9;
}

.admin-avatar-wrap {
  width: 2.875rem; height: 2.875rem;
  border-radius: 10px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.95rem; font-weight: 800; color: #fff;
  overflow: hidden;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}
.admin-avatar-wrap img { width: 100%; height: 100%; object-fit: cover; }

.admin-info { flex: 1; min-width: 0; }
.name-row   { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }
.admin-name { font-size: 0.925rem; font-weight: 700; color: #111827; margin: 0; }
.admin-email { font-size: 0.75rem; color: #9ca3af; margin: 2px 0 0; }
.admin-meta  { display: flex; align-items: center; gap: 0.35rem; margin-top: 0.45rem; flex-wrap: wrap; }
.meta-text   { font-size: 0.7rem; color: #9ca3af; }

.tag          { display: inline-flex; align-items: center; gap: 2px; font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 0.15rem 0.45rem; border-radius: 4px; }
.tag-suspended { background: #fee2e2; color: #dc2626; }

.role-chip    { font-size: 0.66rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; padding: 0.15rem 0.5rem; border-radius: 5px; }
.chip-owner   { background: #fef3c7; color: #b45309; }
.chip-admin   { background: #ede9fe; color: #5b21b6; }
.chip-manager { background: #d1fae5; color: #065f46; }
.chip-primary { background: #f0f9ff; color: #0369a1; }

.card-body     { padding: 1rem 1.5rem; border-bottom: 1px solid #f1f5f9; }
.card-body-row { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; }

.section-head {
  display: flex; align-items: center; gap: 0.35rem;
  font-size: 0.68rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.08em;
  color: #9ca3af; margin-bottom: 0.65rem;
}
.sh-hint { margin-left: auto; font-size: 0.62rem; font-weight: 400; text-transform: none; letter-spacing: 0; color: #d1d5db; }

.perm-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; }
.perm-tile {
  background: #f9fafb; border: 1px solid #f1f3f8;
  border-radius: 8px; padding: 0.6rem 0.7rem;
  display: flex; flex-direction: column; gap: 0.4rem;
  transition: background 0.15s, border-color 0.15s;
}
.perm-tile.restricted         { background: #fff1f2; border-color: #fecaca; }
.perm-tile-top                { display: flex; align-items: center; justify-content: space-between; }
.perm-tile-label              { font-size: 0.7rem; font-weight: 600; color: #6b7280; }
.perm-tile.restricted .perm-tile-label { color: #dc2626; }

.perm-toggle          { width: 2.2rem; height: 1.2rem; border-radius: 9999px; background: #e5e7eb; border: none; cursor: pointer; position: relative; flex-shrink: 0; transition: background 0.18s; }
.perm-toggle.active   { background: #ef4444; }
.perm-toggle:disabled { opacity: 0.5; cursor: not-allowed; }
.perm-thumb {
  position: absolute; top: 2px; left: 2px;
  width: calc(1.2rem - 4px); height: calc(1.2rem - 4px);
  background: #fff; border-radius: 50%; display: block;
  box-shadow: 0 1px 3px rgba(0,0,0,0.15);
  transition: transform 0.18s cubic-bezier(0.34,1.56,0.64,1);
}
.perm-toggle.active .perm-thumb { transform: translateX(1rem); }
.perm-toggle-sm       { width: 2.4rem; height: 1.3rem; }
.perm-toggle-sm .perm-thumb { width: calc(1.3rem - 4px); height: calc(1.3rem - 4px); }
.perm-toggle-sm.active .perm-thumb { transform: translateX(1.1rem); }

.role-pills    { display: flex; gap: 0.3rem; flex-wrap: wrap; }
.role-pills-sm .role-pill { padding: 0.2rem 0.5rem; font-size: 0.7rem; }
.role-pill     { padding: 0.25rem 0.65rem; border: 1px solid #e5e7eb; border-radius: 9999px; background: #f9fafb; color: #9ca3af; font-size: 0.74rem; font-weight: 600; cursor: pointer; transition: all 0.15s; white-space: nowrap; }
.role-pill:hover:not(:disabled)   { border-color: #c7d2fe; color: #4f46e5; background: #eef2ff; }
.role-pill:disabled                { opacity: 0.4; cursor: not-allowed; }
.role-pill.active                  { border-color: transparent; color: #fff; }
.role-pill.pill-owner.active   { background: #f59e0b; }
.role-pill.pill-admin.active   { background: #4f46e5; }
.role-pill.pill-manager.active { background: #10b981; }

.card-actions {
  display: flex; align-items: center; flex-wrap: wrap; gap: 0.5rem;
  padding: 1rem 1.5rem;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
}

.btn-action          { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.45rem 0.9rem; border-radius: 8px; font-size: 0.78rem; font-weight: 600; cursor: pointer; border: 1px solid; transition: all 0.2s; white-space: nowrap; }
.btn-action:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,0.07); }
.btn-action:disabled { opacity: 0.45; cursor: not-allowed; }
.btn-suspend   { background: white; border-color: #fca5a5; color: #ef4444; }
.btn-suspend:hover:not(:disabled)   { background: #fef2f2; border-color: #ef4444; }
.btn-reinstate { background: white; border-color: #6ee7b7; color: #10b981; }
.btn-reinstate:hover:not(:disabled) { background: #f0fdf9; border-color: #10b981; }

.suspension-note { font-size: 0.72rem; color: #9ca3af; font-style: italic; flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin: 0; }
.saving-pill     { display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.72rem; color: #6b7280; margin-left: auto; }

/* ══════════════════════════════════════════════════════════════════
   TABLE VIEW
══════════════════════════════════════════════════════════════════ */
.table-wrapper { overflow-x: auto; border-radius: 12px; border: 1px solid #e2e8f0; background: white; }
.admin-table   { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
.admin-table thead tr { background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.admin-table th { padding: 0.75rem 1rem; text-align: left; font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; white-space: nowrap; }
.th-center { text-align: center; }
.admin-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background 0.12s; }
.admin-table tbody tr:last-child { border-bottom: none; }
.admin-table tbody tr:hover { background: #fafbff; }
.admin-table tbody tr.row-suspended { background: #fff8f8; }
.admin-table td { padding: 0.875rem 1rem; vertical-align: middle; }
.td-center { text-align: center; }

.td-admin { display: flex; align-items: center; gap: 0.75rem; }

.table-avatar {
  width: 2.25rem; height: 2.25rem;
  border-radius: 8px; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.78rem; font-weight: 800; color: #fff;
  overflow: hidden;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
}
.table-avatar img { width: 100%; height: 100%; object-fit: cover; }

.td-info { display: flex; flex-direction: column; gap: 1px; }
.td-name  { font-weight: 600; color: #111827; font-size: 0.83rem; }
.td-email { font-size: 0.72rem; color: #9ca3af; }
.td-pos   { font-size: 0.7rem; color: #c3c8d8; }

.status-badge     { display: inline-block; font-size: 0.68rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; padding: 0.2rem 0.55rem; border-radius: 5px; }
.status-active    { background: #dcfce7; color: #166534; }
.status-suspended { background: #fee2e2; color: #991b1b; }

.table-actions { display: flex; align-items: center; justify-content: center; gap: 0.4rem; }

.tbl-btn           { width: 30px; height: 30px; border-radius: 6px; border: 1px solid #e2e8f0; background: white; color: #64748b; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.15s; flex-shrink: 0; }
.tbl-btn:disabled  { opacity: 0.4; cursor: not-allowed; }
.tbl-suspend:hover:not(:disabled)   { background: #fef2f2; color: #dc2626; border-color: #fca5a5; }
.tbl-reinstate:hover:not(:disabled) { background: #ecfdf5; color: #059669; border-color: #a7f3d0; }

/* ── Spinners ─────────────────────────────────────────────────────── */
.mini-spin       { width: 0.9rem; height: 0.9rem; border: 2px solid #c7d2fe; border-top-color: #4f46e5; border-radius: 50%; animation: spin 0.7s linear infinite; flex-shrink: 0; }
.mini-spin.light { border-color: rgba(255,255,255,0.3); border-top-color: #fff; }
@keyframes spin  { to { transform: rotate(360deg); } }

/* ── Modal ────────────────────────────────────────────────────────── */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 1rem; }
.modal-content { background: #fff; border-radius: 16px; width: min(480px, 92vw); display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden; animation: modalSlideUp 0.2s ease; }
@keyframes modalSlideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

.modal-header    { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.75rem; border-bottom: 1px solid #e2e8f0; background: #fcfcfc; }
.modal-icon-wrap { width: 2.5rem; height: 2.5rem; flex-shrink: 0; background: #fee2e2; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.modal-title     { font-size: 1.05rem; font-weight: 700; color: #111827; margin: 0; }
.modal-sub       { font-size: 0.8rem; color: #9ca3af; margin: 2px 0 0; }
.close-btn       { background: none; border: none; width: 36px; height: 36px; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #64748b; cursor: pointer; transition: all 0.2s; flex-shrink: 0; }
.close-btn:hover { background: #fee2e2; color: #ef4444; }

.modal-body   { padding: 1.5rem 1.75rem; }
.modal-desc   { font-size: 0.875rem; color: #6b7280; line-height: 1.6; margin: 0 0 1rem; }
.form-group   { display: flex; flex-direction: column; gap: 0.35rem; }
.form-label   { font-size: 0.78rem; font-weight: 600; color: #374151; }
.optional     { font-weight: 400; color: #d1d5db; }
.form-textarea { background: #f9fafb; border: 2px solid #e5e7eb; border-radius: 8px; color: #111827; padding: 0.6rem 0.75rem; font-size: 0.85rem; resize: vertical; font-family: inherit; outline: none; transition: border-color 0.15s; }
.form-textarea:focus { border-color: #a5b4fc; box-shadow: 0 0 0 3px rgba(165,180,252,0.2); }

.modal-footer      { display: flex; justify-content: flex-end; gap: 0.6rem; padding: 1.25rem 1.75rem; border-top: 1px solid #e2e8f0; background: #f8fafc; }
.btn-secondary     { padding: 0.55rem 1.25rem; background: white; border: 1px solid #e2e8f0; border-radius: 8px; font-weight: 600; font-size: 0.85rem; color: #475569; cursor: pointer; transition: all 0.2s; }
.btn-secondary:hover { background: #f1f5f9; }
.btn-danger-solid  { padding: 0.55rem 1.25rem; background: #ef4444; color: #fff; border: none; border-radius: 8px; font-weight: 600; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 0.35rem; transition: all 0.2s; }
.btn-danger-solid:hover:not(:disabled) { background: #dc2626; }
.btn-danger-solid:disabled { opacity: 0.45; cursor: not-allowed; }

/* ── Toast ────────────────────────────────────────────────────────── */
.toast         { position: fixed; bottom: 1.75rem; right: 1.75rem; z-index: 2000; display: flex; align-items: center; gap: 0.6rem; padding: 0.75rem 1.1rem; border-radius: 10px; font-size: 0.84rem; font-weight: 600; max-width: 360px; box-shadow: 0 10px 40px rgba(0,0,0,0.12); }
.toast-success { background: #fff; border: 1px solid #6ee7b7; color: #065f46; }
.toast-error   { background: #fff; border: 1px solid #fca5a5; color: #b91c1c; }

/* ── Transitions ─────────────────────────────────────────────────── */
.modal-enter-active, .modal-leave-active { transition: all 0.2s ease; }
.modal-enter-from, .modal-leave-to       { opacity: 0; }
.toast-enter-active, .toast-leave-active { transition: all 0.25s ease; }
.toast-enter-from { opacity: 0; transform: translateY(0.75rem); }
.toast-leave-to   { opacity: 0; transform: translateX(0.75rem); }

/* ── Responsive ───────────────────────────────────────────────────── */
@media (max-width: 1024px) {
  .fixed-header { padding: 1.5rem 1.5rem 0; }
  .page-content { padding: 0 1.5rem 2rem; }
  .admins-grid  { grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); }
}
@media (max-width: 768px) {
  .fixed-header  { padding: 1rem 1rem 0; }
  .page-content  { padding: 0 1rem 2rem; }
  .header-inner  { flex-direction: column; align-items: flex-start; }
  .user-greeting { flex-direction: column; align-items: flex-start; gap: 1rem; }
  .header-controls { width: 100%; }
  .stats-badge   { align-self: flex-start; }
  .admins-grid   { grid-template-columns: 1fr; }
}
@media (max-width: 480px) {
  .card-actions { flex-direction: column; align-items: stretch; }
  .btn-action   { justify-content: center; }
}
</style>