<template>
  <div v-if="isLoading" class="loading-overlay">
    <div class="loading-spinner"></div>
    <p>Loading...</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useLoadingStore } from '@/stores/loading'; // We'll create this store in Step 2

const loadingStore = useLoadingStore();
const isLoading = computed(() => loadingStore.isLoading); // Global state from store
</script>

<style scoped>
.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(255, 255, 255, 0.8); /* Semi-transparent overlay */
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  z-index: 9999; /* Ensure it's on top */
}

.loading-spinner {
  border: 4px solid #f3f3f3; /* Light grey */
  border-top: 4px solid #3498db; /* Blue */
  border-radius: 50%;
  width: 40px;
  height: 40px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
</style>