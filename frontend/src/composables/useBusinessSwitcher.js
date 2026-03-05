import axios from 'axios'
import { useAuthStore }              from '@/stores/auth'
import { useBusinessStore }          from '@/stores/business'
import { useSuspendedBusinessStore } from '@/stores/suspendedBusiness'
import { resetAdminPermissions }     from '@/composables/useAdminPermissions'

export function useBusinessSwitcher() {
  const authStore      = useAuthStore()
  const businessStore  = useBusinessStore()
  const suspendedStore = useSuspendedBusinessStore()

  /**
   * Switch the current user to a different business.
   * Automatically shows the suspension modal if the user is suspended there.
   *
   * @param {string|number} targetBusinessId
   * @param {object}        [options]
   * @param {boolean}       [options.silent=false]  - Skip suspension check (used for the "go back" action)
   */
  async function switchBusiness(targetBusinessId, { silent = false } = {}) {
    const previousBusinessId = authStore.currentBusinessId

    // 1. Call your existing switch-business API endpoint
    const { data } = await axios.post('/api/admin/switch-business', {
      business_id: targetBusinessId,
    })

    // 2. Update auth store with the new business context
    authStore.updateCurrentBusiness(targetBusinessId)

    // 3. If the server returns updated user data, persist it
    if (data?.user) {
      authStore.user = data.user
      localStorage.setItem('user', JSON.stringify(data.user))
    }

    // 4. Bust the cached permissions so fresh flags are fetched for the new business
    resetAdminPermissions()

    // 5. Fetch fresh permissions for the target business
    const permResponse = await axios.get('/api/admin/my-permissions', {
      params: { business_id: targetBusinessId },
    })

    const flags = permResponse.data

    // 6. Suspension check — skip when switching back (to avoid an infinite loop)
    if (!silent && flags.is_suspended) {
      // Find the business name for the modal message
      const targetBusiness = businessStore.businesses?.find(
        b => String(b.id) === String(targetBusinessId)
      )
      const businessName = targetBusiness?.name || 'this business'

      suspendedStore.show(businessName, previousBusinessId)
      return { suspended: true }
    }

    return { suspended: false }
  }

  return { switchBusiness }
}