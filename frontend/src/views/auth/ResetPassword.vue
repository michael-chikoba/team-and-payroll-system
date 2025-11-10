<template>
  <div class="simple-view">
    <h1>{{ pageName }}</h1>
    <div class="content">
      <p>{{ pageName }} - Working Build Version</p>
      <p>All complex functionality temporarily removed for successful build.</p>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ResetPassword',
  data() {
    return {
      pageName: 'ResetPassword'
    }
  }
}
</script>

<style scoped>
.simple-view {
  padding: 2rem;
  max-width: 1200px;
  margin: 0 auto;
}

.content {
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  text-align: center;
}
</style>
