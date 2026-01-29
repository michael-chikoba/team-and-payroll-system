<template>
  <div class="landing-layout">
    <!-- Navigation (common to all pages) -->
    <LandingNavbar />
    
    <!-- Main content area -->
    <main class="landing-main">
      <router-view />
    </main>
    
    <!-- Footer (common to all pages) -->
    <LandingFooter />
  </div>
</template>

<script setup>
import LandingNavbar from '@/components/landing/LandingNavbar.vue'
import LandingFooter from '@/components/landing/LandingFooter.vue'
</script>

<style scoped>
.landing-layout {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
}

.landing-main {
  flex: 1;
}
</style>