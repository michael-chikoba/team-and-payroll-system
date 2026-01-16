<template>
  <div v-if="show" class="notification-toast">
    <div class="notification-icon" :class="type">
      <!-- Similar SVGs as panel -->
    </div>
    <div class="notification-content">
      <p>{{ message }}</p>
    </div>
    <button @click="close">Close</button>
  </div>
</template>
<script>
export default {
  name: 'Notification',
  props: {
    type: String,
    message: String,
    duration: Number
  },
  data() {
    return { show: true }
  },
  mounted() {
    setTimeout(() => this.show = false, this.duration)
  },
  methods: {
    close() {
      this.show = false
    }
  }
}
</script>
<style scoped>
.notification-toast {
  position: fixed;
  bottom: 20px;
  right: 20px;
  padding: 1rem;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  display: flex;
  gap: 1rem;
  align-items: center;
}
/* Add more styles */
</style>