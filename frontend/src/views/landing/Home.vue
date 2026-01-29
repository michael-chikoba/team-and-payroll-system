<template>
  <div class="home-page">
    <!-- Hero Section -->
    <section class="hero">
      <div class="container">
        <div class="hero-content">
          <div class="hero-badge">🚀 Trusted by 500+ Companies</div>
          <h1 class="hero-title">Complete Payroll Management for Modern Businesses</h1>
          <p class="hero-subtitle">
            Streamline payroll, manage employees, track attendance, and ensure compliance—all in one powerful, cloud-based platform.
          </p>
          <div class="hero-stats">
            <div class="stat-item">
              <div class="stat-number">70%</div>
              <div class="stat-label">Time Saved</div>
            </div>
            <div class="stat-item">
              <div class="stat-number">99.9%</div>
              <div class="stat-label">Accuracy</div>
            </div>
            <div class="stat-item">
              <div class="stat-number">24/7</div>
              <div class="stat-label">Support</div>
            </div>
          </div>
          <div class="hero-actions">
            <router-link to="/demo" class="btn-primary btn-lg">
              Start Free Trial
            </router-link>
            <router-link to="/contact" class="btn-secondary btn-lg">
              Schedule a Demo
            </router-link>
          </div>
          <div class="hero-trust">
            <p>No credit card required • 14-day free trial • Cancel anytime</p>
          </div>
        </div>
        <div class="hero-image">
          <div class="dashboard-preview">
            <div class="preview-header">
              <div class="preview-dots">
                <span></span>
                <span></span>
                <span></span>
              </div>
              <div class="preview-title">Payroll Dashboard</div>
            </div>
            <div class="preview-content">
              <div class="preview-card">
                <div class="card-icon">💰</div>
                <div class="card-info">
                  <div class="card-label">Total Payroll</div>
                  <div class="card-value">$245,890</div>
                </div>
              </div>
              <div class="preview-card">
                <div class="card-icon">👥</div>
                <div class="card-info">
                  <div class="card-label">Active Employees</div>
                  <div class="card-value">143</div>
                </div>
              </div>
              <div class="preview-card">
                <div class="card-icon">📊</div>
                <div class="card-info">
                  <div class="card-label">Processing</div>
                  <div class="card-value">On Time</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Quick Feature Highlights -->
    <section class="feature-highlights">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">All-in-One Payroll Solution</h2>
          <p class="section-subtitle">
            Everything you need to manage payroll efficiently
          </p>
        </div>
        <div class="highlights-grid">
          <div class="highlight-card">
            <div class="highlight-icon">⚡</div>
            <h3>Fast Processing</h3>
            <p>Process payroll in minutes instead of days with automated calculations.</p>
          </div>
          <div class="highlight-card">
            <div class="highlight-icon">🛡️</div>
            <h3>Compliance Guaranteed</h3>
            <p>Stay up-to-date with tax laws and regulations automatically.</p>
          </div>
          <div class="highlight-card">
            <div class="highlight-icon">💎</div>
            <h3>Cost Effective</h3>
            <p>Reduce payroll processing costs by up to 50% with our platform.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Quick Links to Other Pages -->
    <section class="quick-links">
      <div class="container">
        <h2 class="section-title">Explore Our Platform</h2>
        <div class="links-grid">
          <router-link to="/features" class="link-card">
            <div class="link-icon">🌟</div>
            <h3>Features</h3>
            <p>Discover all our powerful features</p>
            <span class="link-arrow">→</span>
          </router-link>
          <router-link to="/pricing" class="link-card">
            <div class="link-icon">💰</div>
            <h3>Pricing</h3>
            <p>Simple, transparent pricing plans</p>
            <span class="link-arrow">→</span>
          </router-link>
          <router-link to="/solutions" class="link-card">
            <div class="link-icon">🏢</div>
            <h3>Solutions</h3>
            <p>Tailored for your business size</p>
            <span class="link-arrow">→</span>
          </router-link>
          <router-link to="/demo" class="link-card featured">
            <div class="link-icon">🚀</div>
            <h3>Try Free</h3>
            <p>Start your 14-day free trial</p>
            <span class="link-arrow">→</span>
          </router-link>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { onMounted } from 'vue'

onMounted(() => {
  // Page-specific initialization if needed
})
</script>

<style scoped>
.home-page {
  padding-bottom: 4rem;
}

/* Hero Section */
.hero {
  padding: 6rem 0 4rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  position: relative;
  overflow: hidden;
}

.hero::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="2" fill="white" opacity="0.1"/></svg>');
  opacity: 0.3;
}

.hero .container {
  position: relative;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 4rem;
  align-items: center;
}

.hero-badge {
  display: inline-block;
  background: rgba(255, 255, 255, 0.2);
  padding: 0.5rem 1rem;
  border-radius: 50px;
  font-size: 0.9rem;
  margin-bottom: 1.5rem;
  backdrop-filter: blur(10px);
}

.hero-title {
  font-size: 3.5rem;
  font-weight: 800;
  margin-bottom: 1.5rem;
  line-height: 1.2;
}

.hero-subtitle {
  font-size: 1.35rem;
  margin-bottom: 2rem;
  opacity: 0.95;
  line-height: 1.6;
}

.hero-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 2rem;
  margin-bottom: 2rem;
  padding: 2rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  backdrop-filter: blur(10px);
}

.stat-item {
  text-align: center;
}

.stat-number {
  font-size: 2.5rem;
  font-weight: 800;
  margin-bottom: 0.5rem;
}

.stat-label {
  font-size: 0.9rem;
  opacity: 0.9;
}

.hero-actions {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
}

.hero-trust {
  font-size: 0.9rem;
  opacity: 0.8;
}

.dashboard-preview {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
  color: #333;
}

.preview-header {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.preview-dots {
  display: flex;
  gap: 6px;
}

.preview-dots span {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: #d1d5db;
}

.preview-title {
  font-weight: 600;
  color: #1a1a1a;
}

.preview-content {
  display: grid;
  gap: 1rem;
}

.preview-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 8px;
}

.card-icon {
  font-size: 2rem;
}

.card-label {
  font-size: 0.85rem;
  color: #666;
}

.card-value {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a1a1a;
}

/* Feature Highlights */
.feature-highlights {
  padding: 6rem 0;
  background: white;
}

.highlights-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
  max-width: 1000px;
  margin: 0 auto;
}

.highlight-card {
  text-align: center;
  padding: 2rem;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  transition: all 0.3s;
}

.highlight-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
  border-color: #667eea;
}

.highlight-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.highlight-card h3 {
  font-size: 1.5rem;
  margin-bottom: 1rem;
  color: #1a1a1a;
}

.highlight-card p {
  color: #666;
  line-height: 1.6;
}

/* Quick Links */
.quick-links {
  padding: 4rem 0;
  background: #f9fafb;
}

.links-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
  max-width: 1000px;
  margin: 0 auto;
}

.link-card {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  border: 2px solid #e5e7eb;
  text-decoration: none;
  color: inherit;
  transition: all 0.3s;
  position: relative;
  overflow: hidden;
}

.link-card:hover {
  transform: translateY(-5px);
  border-color: #667eea;
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
}

.link-card.featured {
  border-color: #667eea;
  background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
}

.link-icon {
  font-size: 2.5rem;
  margin-bottom: 1rem;
}

.link-card h3 {
  font-size: 1.5rem;
  margin-bottom: 0.5rem;
  color: #1a1a1a;
}

.link-card p {
  color: #666;
  margin-bottom: 1rem;
}

.link-arrow {
  font-size: 1.5rem;
  color: #667eea;
  transition: transform 0.3s;
}

.link-card:hover .link-arrow {
  transform: translateX(5px);
}

@media (max-width: 768px) {
  .hero .container {
    grid-template-columns: 1fr;
  }
  
  .hero-title {
    font-size: 2.5rem;
  }
  
  .hero-stats {
    grid-template-columns: 1fr;
  }
  
  .hero-actions {
    flex-direction: column;
  }
}
</style>