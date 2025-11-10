<template>
  <div class="status-view">
    <div class="status-box">
      <p class="status-code">404?</p>
      
      <h1 class="main-title">{{ pageName }}</h1> 
      
      <div class="content-message">
        <p class="working-version-text">
          **This page is currently under maintenance.**
        </p>
        <p class="details-text">
          All complex functionality temporarily removed for successful build/deployment. We'll be back soon!
        </p>
        
        <router-link to="/" class="home-button">Go back to Dashboard</router-link>
      </div>
    </div>
  </div>
</template>
<script>
export default {
  name: 'NotFound',
  data() {
    return {
      pageName: 'NotFound'
    }
  }
}
</script>

<style scoped>
/* Modern Color Palette */
:root {
  --primary-color: #f7931a; /* A vibrant, modern orange/amber for warning */
  --text-color: #343a40;
  --text-light: #6c757d;
  --background-color: #f8f9fa; /* Light, clean background */
  --surface-color: #ffffff;
}

/* Full screen container & Centering */
.status-view {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: var(--background-color);
  font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  padding: 2rem;
}

/* The main container box */
.status-box {
  background: var(--surface-color);
  padding: 4rem;
  border-radius: 16px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08); /* Stronger, softer shadow */
  text-align: center;
  max-width: 500px;
  width: 100%;
  position: relative;
  overflow: hidden; /* Contains the large number */
}

/* Large Status Code/Placeholder Number */
.status-code {
  position: absolute;
  top: -20px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 10rem;
  font-weight: 800;
  color: rgba(247, 147, 26, 0.1); /* Very light, transparent primary color */
  margin: 0;
  pointer-events: none; /* Allows clicks to pass through */
  line-height: 1;
}

/* Main Title */
.main-title {
  margin-top: 0;
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--primary-color);
  margin-bottom: 1rem;
}

/* Content Area */
.content-message {
  margin-top: 2rem;
}

.working-version-text {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--text-color);
  margin-bottom: 0.5rem;
}

.details-text {
  font-size: 1rem;
  color: var(--text-light);
  margin-bottom: 2rem;
}

/* Call-to-Action Button */
.home-button {
  display: inline-block;
  background-color: var(--primary-color);
  color: white;
  text-decoration: none;
  padding: 0.8rem 1.5rem;
  border-radius: 8px;
  font-weight: 600;
  transition: background-color 0.3s ease, transform 0.1s ease;
  box-shadow: 0 4px 10px rgba(247, 147, 26, 0.3);
}

.home-button:hover {
  background-color: #e68612; /* Slightly darker on hover */
  transform: translateY(-1px);
}

/* Responsiveness */
@media (max-width: 600px) {
  .status-box {
    padding: 3rem 1.5rem;
  }
  
  .status-code {
    font-size: 7rem;
    top: 5px;
  }

  .main-title {
    font-size: 2rem;
  }
}
</style>
