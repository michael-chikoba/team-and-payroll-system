<template>
  <div class="landing-layout">
    <LandingNavbar />
    <main class="landing-main">
      <router-view />
    </main>
    <LandingFooter />
  </div>
</template>

<script setup>
import { onMounted, onUnmounted } from 'vue'
import LandingNavbar from '@/components/landing/LandingNavbar.vue'
import LandingFooter from '@/components/landing/LandingFooter.vue'

const WIDGET_ID  = '83d3a04a-5ecb-4e60-8382-c0ce520cb599'
const WIDGET_URL = 'https://archangelchat.it.com/widget/chat-widget.js'

function loadChatWidget() {
  if (document.getElementById('cs-widget-script')) {
    console.warn('[ChatWidget] Script tag already exists — skipping inject')
    return
  }

  console.log('[ChatWidget] Injecting widget script...')
  console.log('[ChatWidget] widgetId:', WIDGET_ID)
  console.log('[ChatWidget] src:', WIDGET_URL)

  window.ChatSystemConfig = { widgetId: WIDGET_ID }

  const script    = document.createElement('script')
  script.id       = 'cs-widget-script'
  script.src      = WIDGET_URL
  script.async    = true

  script.onload = () => console.log('[ChatWidget] ✅ Script loaded successfully')
  script.onerror = (e) => console.error('[ChatWidget] ❌ Script failed to load', e)

  document.body.appendChild(script)
  console.log('[ChatWidget] Script tag appended to body')
}

function removeChatWidget() {
  document.getElementById('cs-widget-script')?.remove()
  document.getElementById('cs-widget-root')?.remove()
  delete window.ChatSystemConfig
  console.log('[ChatWidget] Widget removed')
}

onMounted(loadChatWidget)
onUnmounted(removeChatWidget)
</script>

<style>
html {
  overflow-x: hidden !important;
  overflow-y: auto !important;
  scroll-behavior: smooth;
}

body {
  overflow-x: hidden !important;
  overflow-y: visible !important;
  min-height: 100vh !important;
  height: auto !important;
}

#app {
  min-height: 100vh;
  overflow-x: hidden;
  height: auto !important;
}
</style>

