<template>
  <div class="page-wrapper">
    <LandingNavbar />
    <main>
      <!-- Page Hero -->
      <section class="page-hero">
        <div class="container">
          <h1 class="page-title">Solutions for Every Business</h1>
          <p class="page-subtitle">Whether you're a startup or enterprise, we have the right solution for your team</p>
        </div>
      </section>

      <!-- Solutions Section -->
      <section class="solutions">
        <div class="container">
          <div class="solutions-grid">
            <div class="solution-card">
              <div class="solution-icon">🚀</div>
              <h3>For Startups</h3>
              <p>Simple, affordable payroll for growing teams</p>
              <ul>
                <li>1–50 employees</li>
                <li>Standard support</li>
                <li>All features included</li>
                <li>Email & chat support</li>
              </ul>
              <router-link to="/pricing" class="btn-outline">View Pricing</router-link>
            </div>
            <div class="solution-card featured">
              <div class="featured-badge">Most Popular</div>
              <div class="solution-icon">🏢</div>
              <h3>For SMBs</h3>
              <p>Complete payroll and HR for medium businesses</p>
              <ul>
                <li>50–500 employees</li>
                <li>Priority support</li>
                <li>Multi-location support</li>
                <li>Dedicated account manager</li>
              </ul>
              <router-link to="/pricing" class="btn-primary">View Pricing</router-link>
            </div>
            <div class="solution-card">
              <div class="solution-icon">🌟</div>
              <h3>For Enterprise</h3>
              <p>Scalable payroll for large organizations</p>
              <ul>
                <li>500+ employees</li>
                <li>Custom workflows</li>
                <li>24/7 premium support</li>
                <li>Custom SLAs</li>
              </ul>
              <router-link to="/contact" class="btn-outline">Contact Sales</router-link>
            </div>
          </div>
        </div>
      </section>

      <!-- Industry Solutions -->
      <section class="industry">
        <div class="container">
          <div class="section-header">
            <h2 class="section-title">Built for Every Industry</h2>
            <p class="section-subtitle">Archangel Payroll adapts to your industry's unique compliance and workflow needs</p>
          </div>
          <div class="industry-grid">
            <div class="industry-card">
              <div class="industry-icon">🏦</div>
              <h3>Finance & Banking</h3>
              <p>Full compliance with financial sector regulations, robust audit trails, and secure data handling.</p>
            </div>
            <div class="industry-card">
              <div class="industry-icon">🏥</div>
              <h3>Healthcare</h3>
              <p>Manage complex shift patterns, licensing requirements, and specialized pay structures for medical staff.</p>
            </div>
            <div class="industry-card">
              <div class="industry-icon">🛒</div>
              <h3>Retail & Hospitality</h3>
              <p>Handle high-volume hourly workers, seasonal staff, and tip management seamlessly.</p>
            </div>
            <div class="industry-card">
              <div class="industry-icon">🏗️</div>
              <h3>Construction</h3>
              <p>Project-based payroll, contractor management, and multi-site workforce tracking.</p>
            </div>
            <div class="industry-card">
              <div class="industry-icon">💻</div>
              <h3>Technology</h3>
              <p>Equity compensation, remote team payroll across jurisdictions, and flexible benefit management.</p>
            </div>
            <div class="industry-card">
              <div class="industry-icon">🎓</div>
              <h3>Education</h3>
              <p>Term-time pay schedules, grant management, and support for both teaching and administrative staff.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- CTA -->
      <section class="cta">
        <div class="container">
          <div class="cta-content">
            <h2>Not Sure Which Solution Fits?</h2>
            <p>Talk to our team and we'll help you find the perfect plan for your business</p>
            <div class="cta-actions">
              <router-link to="/contact" class="btn-primary btn-lg">Talk to Sales</router-link>
              <router-link to="/pricing" class="btn-secondary btn-lg">View Pricing</router-link>
            </div>
          </div>
        </div>
      </section>
    </main>
    <LandingFooter />
  </div>
</template>

<script setup>
import LandingNavbar from '@/components/landing/LandingNavbar.vue'
import LandingFooter from '@/components/landing/LandingFooter.vue'
</script>

<style scoped>
/* Page-specific styles only — globals live in landing.css */

.page-hero {
  padding-top: calc(clamp(2.5rem, 6vw, 4rem) + 72px);
  padding-bottom: clamp(2.5rem, 6vw, 4rem);
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  text-align: center;
}
.page-title    { font-size: clamp(2rem, 5vw, 3rem); font-weight: 800; margin-bottom: 1rem; line-height: 1.2; }
.page-subtitle { font-size: clamp(1rem, 2.5vw, 1.25rem); opacity: 0.95; max-width: 600px; margin: 0 auto; }

/* ── Solution Cards ── */
.solutions { padding: clamp(3rem, 8vw, 6rem) 0; background: white; }

.solutions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr));
  gap: clamp(1.5rem, 3vw, 2rem);
  max-width: 1000px;
  margin: 0 auto;
}

.solution-card {
  background: white;
  padding: clamp(1.5rem, 3vw, 2.5rem);
  border-radius: 12px;
  border: 2px solid #e5e7eb;
  text-align: center;
  transition: all 0.3s;
  position: relative;
  display: flex;
  flex-direction: column;
}

.solution-card:hover { transform: translateY(-5px); border-color: #667eea; box-shadow: 0 12px 24px rgba(0,0,0,0.1); }
.solution-card.featured { border-color: #667eea; box-shadow: 0 12px 24px rgba(102,126,234,0.2); }

.solution-icon { font-size: 3.5rem; margin-bottom: 1rem; }
.solution-card h3 { font-size: 1.75rem; margin-bottom: 0.5rem; color: #1a1a1a; }
.solution-card > p { color: #666; margin-bottom: 2rem; }
.solution-card ul { list-style: none; padding: 0; margin-bottom: 2rem; text-align: left; flex-grow: 1; }
.solution-card li { padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb; color: #555; font-size: 0.95rem; }
.solution-card a { margin-top: auto; }

/* ── Industry ── */
.industry { padding: clamp(3rem, 8vw, 6rem) 0; background: #f9fafb; }

.industry-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr));
  gap: clamp(1.5rem, 3vw, 2rem);
}

.industry-card {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  transition: all 0.3s;
}

.industry-card:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); border-color: #667eea; }
.industry-icon { font-size: 2.5rem; margin-bottom: 1rem; display: block; }
.industry-card h3 { font-size: 1.25rem; margin-bottom: 0.75rem; color: #1a1a1a; }
.industry-card p  { color: #666; line-height: 1.7; font-size: 0.95rem; }

/* ── CTA ── */
.cta {
  padding: clamp(3rem, 8vw, 6rem) 0;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  text-align: center;
}
.cta-content h2 { font-size: clamp(2rem, 6vw, 3rem); margin-bottom: 1rem; }
.cta-content p  { font-size: clamp(1rem, 2.5vw, 1.25rem); margin-bottom: 2rem; opacity: 0.95; }
</style>