<!-- In App.vue, ensure router-view is properly wrapped -->
<template>
  <div id="app">
    <Suspense>
      <template #default>
        <div class="app-wrapper">
          <NotificationPermission v-if="showNotificationPermission" />
          <router-view v-slot="{ Component }">
            <component :is="Component" />
          </router-view>
        </div>
      </template>
      <!-- ... -->
    </Suspense>
  </div>
</template>

<script>
import { ref, onMounted, defineAsyncComponent } from 'vue'

export default {
  name: 'App',
  components: {
    // Use async component with error handling
    NotificationPermission: defineAsyncComponent({
      loader: () => import('@/components/NotificationPermission.vue'),
      delay: 200,
      timeout: 10000,
      errorComponent: {
        template: '<div></div>' // Silent fail for notification permission
      },
      loadingComponent: {
        template: '<div></div>'
      }
    })
  },
  setup() {
    const showNotificationPermission = ref(false)

    onMounted(() => {
      // Show notification permission after everything is loaded
      setTimeout(() => {
        showNotificationPermission.value = true
      }, 3000) // 3 second delay to ensure everything is ready

      console.log('✅ App component mounted and router ready')
    })

    return {
      showNotificationPermission
    }
  }
}
</script>

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

#app {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  color: #2c3e50;
  min-height: 100vh;
  background-color: #f8f9fa;
}

.app-wrapper {
  min-height: 100vh;
}

.app-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.loader {
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top: 4px solid white;
  border-radius: 50%;
  width: 50px;
  height: 50px;
  animation: spin 1s linear infinite;
  margin-bottom: 20px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.app-loading p {
  font-size: 1.2rem;
  font-weight: 500;
}
</style>