<!-- src/App.vue -->
<template>
  <div id="app">
    <GlobalLoading :loading="isNavigating" />
    <Suspense>
      <template #default>
        <div class="app-wrapper">
          <NotificationPermission v-if="showNotificationPermission" />
          <router-view v-slot="{ Component }">
            <component :is="Component" />
          </router-view>
          <IdleWarningBanner />
        </div>
      </template>
    </Suspense>
    <notifications position="top right" />
  </div>
</template>

<script>
import { ref, onMounted, defineAsyncComponent } from 'vue'
import { useRouter } from 'vue-router'
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
    const isNavigating = ref(false)
    const router = useRouter()

    router.beforeEach(() => { isNavigating.value = true })
    router.afterEach(() => { isNavigating.value = false })
    router.onError(() => { isNavigating.value = false })

    onMounted(() => {
      setTimeout(() => { showNotificationPermission.value = true }, 3000)
      console.log('✅ App component mounted')
    })

    return { showNotificationPermission, isNavigating }
  },
}
</script>

<style>
/*
 * App-level global styles.
 * NOTE: body/html base rules live in base.css (imported in main.js).
 * These rules only handle the #app shell — they do NOT set
 * overflow: hidden or height: 100vh on html/body.
 */
#app {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  overflow-x: hidden;
}

.app-wrapper {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  overflow-x: hidden;
}
</style>