<!-- src/App.vue -->
<template>
  <div id="app">
    <!-- ── Global loading overlay ────────────────────────────────────────────
         Sits at the very top of the tree, above Suspense and router-view,
         so it renders during boot, route transitions, AND axios requests.  -->
    <GlobalLoading />

    <Suspense>
      <template #default>
        <div class="app-wrapper">
          <NotificationPermission v-if="showNotificationPermission" />

          <router-view v-slot="{ Component }">
            <component :is="Component" />
          </router-view>

          <!-- ── Idle warning overlay ──────────────────────────────────────
               Sits outside router-view so it survives all route changes.
               Only visible when an overtime session is active + user is idle -->
          <IdleWarningBanner />
        </div>
      </template>
    </Suspense>

    <!-- Notifications (kyvg/vue3-notification) -->
    <notifications position="top right" />
  </div>
</template>

<script>
import { ref, onMounted, defineAsyncComponent } from 'vue'
import IdleWarningBanner from '@/components/IdleWarningBanner.vue'
import GlobalLoading     from '@/components/GlobalLoading.vue'

export default {
  name: 'App',
  components: {
    GlobalLoading,
    IdleWarningBanner,

    NotificationPermission: defineAsyncComponent({
      loader: () => import('@/components/NotificationPermission.vue'),
      delay: 200,
      timeout: 10000,
      errorComponent:  { template: '<div></div>' },
      loadingComponent: { template: '<div></div>' },
    }),
  },

  setup() {
    const showNotificationPermission = ref(false)

    onMounted(() => {
      setTimeout(() => {
        showNotificationPermission.value = true
      }, 3000)

      console.log('✅ App component mounted')
    })

    return { showNotificationPermission }
  },
}
</script>