// src/composables/useAdminPermissions.js
import { ref, computed, watch } from 'vue'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth' // Add this import!

const _fetched = ref(false)
const _fetching = ref(false)
const _fetchError = ref(null)
const _businessId = ref(null)
const _userId = ref(null) // Track which user the permissions are for

const _flags = ref({
  cannot_add_employee: false,
  cannot_view_payroll: false,
  cannot_view_payslip: false,
  cannot_manage_admins: false,
  is_suspended: false,
})

let _inflightPromise = null

async function _fetchPermissions(businessId = null, userId = null) {
  // If already fetched for THIS business AND user, skip
  if (_fetched.value && _businessId.value === businessId && _userId.value === userId) {
    console.log('📦 Using cached permissions for user:', userId, 'business:', businessId)
    return
  }

  // If a request for the same business/user is already in flight, reuse it
  if (_inflightPromise && _businessId.value === businessId && _userId.value === userId) {
    console.log('🔄 Reusing in-flight request for user:', userId)
    return _inflightPromise
  }

  _fetching.value = true
  _fetchError.value = null
  _businessId.value = businessId
  _userId.value = userId

  const params = businessId ? { business_id: businessId } : {}

  console.log('🌐 Fetching permissions for user:', userId, 'business:', businessId)

  _inflightPromise = axios
    .get('/api/admin/my-permissions', { params })
    .then(({ data }) => {
      console.log('✅ Permissions API response:', data)
      
      _flags.value = {
        cannot_add_employee: !!data.cannot_add_employee,
        cannot_view_payroll: !!data.cannot_view_payroll,
        cannot_view_payslip: !!data.cannot_view_payslip,
        cannot_manage_admins: !!data.cannot_manage_admins,
        is_suspended: !!data.is_suspended,
      }
      
      _fetched.value = true
      console.log('✅ Permissions updated:', _flags.value)
    })
    .catch((err) => {
      _fetchError.value = err?.response?.data?.message || err.message
      console.warn('❌ Failed to load permissions:', err)
      
      // Reset to defaults on error
      _flags.value = {
        cannot_add_employee: false,
        cannot_view_payroll: false,
        cannot_view_payslip: false,
        cannot_manage_admins: false,
        is_suspended: false,
      }
    })
    .finally(() => {
      _fetching.value = false
      _inflightPromise = null
    })

  return _inflightPromise
}

export function resetAdminPermissions() {
  _fetched.value = false
  _fetching.value = false
  _fetchError.value = null
  _businessId.value = null
  _userId.value = null
  _inflightPromise = null
  _flags.value = {
    cannot_add_employee: false,
    cannot_view_payroll: false,
    cannot_view_payslip: false,
    cannot_manage_admins: false,
    is_suspended: false,
  }
}

export function useAdminPermissions(businessId = null) {
  // Get current user from auth store
  let authStore
  try {
    authStore = useAuthStore()
  } catch (e) {
    console.warn('Auth store not available yet:', e)
    authStore = { user: null }
  }
  
  const userId = computed(() => authStore.user?.id)

  // Create computed refs that update when the singleton changes
  const localFlags = computed(() => _flags.value)
  const localFetching = computed(() => _fetching.value)
  const localFetchError = computed(() => _fetchError.value)
  const localFetched = computed(() => _fetched.value)

  // Watch for changes in businessId or userId and fetch accordingly
  watch([() => businessId, userId], ([newBusinessId, newUserId]) => {
    if (newBusinessId && newUserId) {
      console.log('👤 User changed/loaded, fetching permissions for user:', newUserId)
      _fetchPermissions(newBusinessId, newUserId)
    }
  }, { immediate: true })

  // Positive "CAN do" computed booleans
  const canAddEmployee = computed(() => !_flags.value.cannot_add_employee)
  const canViewPayroll = computed(() => !_flags.value.cannot_view_payroll)
  const canViewPayslip = computed(() => !_flags.value.cannot_view_payslip)
  const canManageAdmins = computed(() => !_flags.value.cannot_manage_admins)
  const isSuspended = computed(() => _flags.value.is_suspended)

  const refresh = (newBusinessId = null) => {
    _fetched.value = false
    _businessId.value = null
    _userId.value = null
    return _fetchPermissions(newBusinessId ?? businessId, userId.value)
  }

  const waitForFetch = () => {
    if (_fetched.value) return Promise.resolve()
    if (_inflightPromise) return _inflightPromise
    return _fetchPermissions(businessId, userId.value)
  }

  return {
    flags: localFlags,
    fetching: localFetching,
    fetchError: localFetchError,
    fetched: localFetched,
    canAddEmployee,
    canViewPayroll,
    canViewPayslip,
    canManageAdmins,
    isSuspended,
    restrictions: localFlags,
    refresh,
    waitForFetch,
  }
}