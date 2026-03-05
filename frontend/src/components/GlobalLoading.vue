<!-- src/components/GlobalLoading.vue -->
<template>
  <Transition name="fade">
    <div v-if="isLoading" class="loading-overlay" role="status" aria-label="Loading">
      <div class="loading-spinner"></div>
      <p class="loading-text">Loading...</p>
    </div>
  </Transition>
</template>

<script setup>
import { computed } from 'vue';
import { useLoadingStore } from '@/stores/loading';

const loadingStore = useLoadingStore();
const isLoading = computed(() => loadingStore.isLoading);
</script>

<style scoped>
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(255, 255, 255, 0.8);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  backdrop-filter: blur(2px);
}

.loading-spinner {
  border: 4px solid #f3f3f3;
  border-top: 4px solid #3498db;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
}

.loading-text {
  margin-top: 12px;
  font-size: 0.95rem;
  color: #555;
  font-family: system-ui, sans-serif;
}

@keyframes spin {
  0%   { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

/* Smooth fade transition */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>