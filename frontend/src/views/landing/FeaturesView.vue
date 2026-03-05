<template>
  <div class="page-wrapper">
    <LandingNavbar />
    <main>
      <!-- Page Hero -->
      <section class="page-hero">
        <div class="container">
          <h1 class="page-title">Everything You Need to Manage Payroll</h1>
          <p class="page-subtitle">All features available on every plan. Choose the plan that fits your team size.</p>
        </div>
      </section>

      <!-- Features Section -->
      <section class="features">
        <div class="container">
          <div class="features-grid">
            <div class="feature-card">
              <div class="feature-icon">💰</div>
              <h3>Automated Payroll Processing</h3>
              <p>Process payroll automatically with accurate calculations for salaries, bonuses, allowances, and deductions.</p>
              <ul class="feature-list">
                <li>Automated salary calculations</li>
                <li>Tax withholding & compliance</li>
                <li>Direct deposit integration</li>
                <li>Payslip generation</li>
              </ul>
            </div>
            <div class="feature-card">
              <div class="feature-icon">👥</div>
              <h3>Employee Management</h3>
              <p>Centralized employee database with comprehensive profiles, document management, and onboarding workflows.</p>
              <ul class="feature-list">
                <li>Employee profiles & records</li>
                <li>Document storage</li>
                <li>Onboarding automation</li>
                <li>Role-based access control</li>
              </ul>
            </div>
            <div class="feature-card">
              <div class="feature-icon">⏰</div>
              <h3>Time & Attendance</h3>
              <p>Track employee attendance, working hours, overtime, and absences with integrated time-tracking tools.</p>
              <ul class="feature-list">
                <li>Clock-in/out tracking</li>
                <li>Overtime calculations</li>
                <li>Shift management</li>
                <li>GPS location verification</li>
              </ul>
            </div>
            <div class="feature-card">
              <div class="feature-icon">🏖️</div>
              <h3>Leave Management</h3>
              <p>Streamline leave requests, approvals, and balance tracking with automated workflows.</p>
              <ul class="feature-list">
                <li>Leave request & approval</li>
                <li>Multiple leave types</li>
                <li>Balance tracking</li>
                <li>Calendar integration</li>
              </ul>
            </div>
            <div class="feature-card">
              <div class="feature-icon">🎯</div>
              <h3>Team Management & Collaboration</h3>
              <p>Organize teams with Trello and Jira-style task management, ticket systems, and built-in collaboration tools.</p>
              <ul class="feature-list">
                <li>Kanban boards & task tracking</li>
                <li>Ticket management system</li>
                <li>Slack integration available</li>
                <li>Built-in team chat</li>
              </ul>
            </div>
            <div class="feature-card">
              <div class="feature-icon">📈</div>
              <h3>Productivity Analytics</h3>
              <p>Track team performance with detailed productivity reports and actionable insights.</p>
              <ul class="feature-list">
                <li>Productivity score tracking</li>
                <li>Team member reports</li>
                <li>Performance dashboards</li>
                <li>Goal tracking & OKRs</li>
              </ul>
            </div>
            <div class="feature-card">
              <div class="feature-icon">✅</div>
              <h3>SLA Compliance Monitoring</h3>
              <p>Monitor and maintain service level agreements with real-time compliance tracking.</p>
              <ul class="feature-list">
                <li>SLA compliance rate tracking</li>
                <li>Percentage-based metrics</li>
                <li>Automated compliance alerts</li>
                <li>Detailed audit trails</li>
              </ul>
            </div>
            <div class="feature-card">
              <div class="feature-icon">🌍</div>
              <h3>Multi-Country & Currency</h3>
              <p>Manage payroll across multiple countries with different currencies, tax rules, and labor laws.</p>
              <ul class="feature-list">
                <li>50+ countries supported</li>
                <li>Multi-currency payments</li>
                <li>Local compliance</li>
                <li>Exchange rate updates</li>
              </ul>
            </div>
            <div class="feature-card">
              <div class="feature-icon">📊</div>
              <h3>Reports & Analytics</h3>
              <p>Generate comprehensive reports with real-time insights into payroll costs and trends.</p>
              <ul class="feature-list">
                <li>Payroll reports</li>
                <li>Tax reports</li>
                <li>Custom dashboards</li>
                <li>Export to Excel/PDF</li>
              </ul>
            </div>
            <div class="feature-card">
              <div class="feature-icon">🔒</div>
              <h3>Enterprise Security</h3>
              <p>Bank-level security with encryption, regular backups, and compliance with international standards.</p>
              <ul class="feature-list">
                <li>256-bit encryption</li>
                <li>Two-factor authentication</li>
                <li>SOC 2 compliant</li>
                <li>Daily backups</li>
              </ul>
            </div>
            <div class="feature-card featured-mobile">
              <div class="feature-icon">📱</div>
              <h3>Mobile Access – Fully Responsive</h3>
              <p>Complete mobile experience with responsive design. Access all features on the go.</p>
              <ul class="feature-list">
                <li>iOS & Android native apps</li>
                <li>Fully responsive web design</li>
                <li>Employee self-service portal</li>
                <li>Mobile time tracking</li>
                <li>Push notifications & alerts</li>
                <li>Offline access mode</li>
              </ul>
            </div>
            <div class="feature-card">
              <div class="feature-icon">💬</div>
              <h3>Live Support Chat</h3>
              <p>Real-time live chat support built directly into the platform so your team gets help instantly.</p>
              <ul class="feature-list">
                <li>Real-time messaging</li>
                <li>Visitor tracking</li>
                <li>Agent dashboard</li>
                <li>Chat history</li>
              </ul>
            </div>
          </div>

          <div class="features-cta">
            <div class="features-cta-content">
              <h3>🌟 And Many More Features...</h3>
              <p>Productivity reports, SLA compliance tracking, team collaboration tools, advanced analytics, workflow automation, and much more!</p>
              <div class="features-cta-actions">
                <router-link to="/contact" class="btn-primary">Contact Us for Full Feature List</router-link>
                <router-link to="/contact" class="btn-outline">Book a Demo</router-link>
              </div>
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
.page-title  { font-size: clamp(2rem, 5vw, 3rem); font-weight: 800; margin-bottom: 1rem; line-height: 1.2; }
.page-subtitle { font-size: clamp(1rem, 2.5vw, 1.25rem); opacity: 0.95; max-width: 600px; margin: 0 auto; }

/* ── Feature Cards ── */
.features { padding: clamp(3rem, 8vw, 6rem) 0; background: #f9fafb; }

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 350px), 1fr));
  gap: clamp(1.5rem, 3vw, 2rem);
}

.feature-card {
  background: white;
  padding: clamp(1.5rem, 3vw, 2rem);
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  transition: all 0.3s;
  border: 1px solid #e5e7eb;
}

.feature-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
  border-color: #667eea;
}

.feature-card.featured-mobile {
  border: 2px solid #667eea;
  background: linear-gradient(135deg, rgba(102,126,234,0.05) 0%, rgba(118,75,162,0.05) 100%);
}

.feature-icon  { font-size: clamp(2.5rem, 4vw, 3rem); margin-bottom: 1rem; display: block; }
.feature-card h3 { font-size: clamp(1.1rem, 2.5vw, 1.35rem); margin-bottom: 0.75rem; color: #1a1a1a; }
.feature-card > p { color: #666; margin-bottom: 1.25rem; line-height: 1.7; font-size: 0.95rem; }

.feature-list { list-style: none; padding: 0; }
.feature-list li {
  color: #555;
  margin-bottom: 0.6rem;
  padding-left: 1.5rem;
  position: relative;
  font-size: 0.93rem;
}
.feature-list li::before { content: '✓'; position: absolute; left: 0; color: #10b981; font-weight: bold; }

/* ── Features CTA block ── */
.features-cta {
  margin-top: clamp(3rem, 6vw, 5rem);
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 16px;
  padding: clamp(2rem, 5vw, 4rem);
  text-align: center;
  color: white;
}

.features-cta-content h3 { font-size: clamp(1.75rem, 4vw, 2.25rem); margin-bottom: 1rem; }
.features-cta-content p {
  font-size: clamp(1rem, 2.5vw, 1.2rem);
  margin-bottom: 2rem;
  opacity: 0.95;
  max-width: 800px;
  margin-left: auto;
  margin-right: auto;
}

.features-cta-actions {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  justify-content: center;
  align-items: center;
}
@media (min-width: 768px) { .features-cta-actions { flex-direction: row; } }
</style>