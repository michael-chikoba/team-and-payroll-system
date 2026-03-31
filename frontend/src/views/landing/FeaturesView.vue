<template>
  <div class="page-wrapper">
    <LandingNavbar />
    <main>

      <!-- ── Page Hero ── -->
      <section class="page-hero">
        <div class="container">
          <!-- Heroicon: cpu-chip -->
          <div class="hero-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="52" height="52">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 0 0 2.25-2.25V6.75a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25Zm.75-12h9v9h-9v-9Z" />
            </svg>
          </div>
          <h1 class="page-title">Everything You Need to Manage Payroll</h1>
          <p class="page-subtitle">All features available on every plan. Choose the plan that fits your team size.</p>
        </div>
      </section>

      <!-- ── Features Grid ── -->
      <section class="features">
        <div class="container">
          <div class="features-grid">

            <!-- Automated Payroll -->
            <div class="feature-card">
              <div class="feature-icon-wrap feature-icon--blue">
                <!-- Heroicon: banknotes -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="26" height="26">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                </svg>
              </div>
              <h3>Automated Payroll Processing</h3>
              <p>Process payroll automatically with accurate calculations for salaries, bonuses, allowances, and deductions.</p>
              <ul class="feature-list">
                <li>Automated salary calculations</li>
                <li>Tax withholding &amp; compliance</li>
                <li>Direct deposit integration</li>
                <li>Payslip generation</li>
              </ul>
            </div>

            <!-- Employee Management -->
            <div class="feature-card">
              <div class="feature-icon-wrap feature-icon--orange">
                <!-- Heroicon: users -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="26" height="26">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                </svg>
              </div>
              <h3>Employee Management</h3>
              <p>Centralized employee database with comprehensive profiles, document management, and onboarding workflows.</p>
              <ul class="feature-list">
                <li>Employee profiles &amp; records</li>
                <li>Document storage</li>
                <li>Onboarding automation</li>
                <li>Role-based access control</li>
              </ul>
            </div>

            <!-- Time & Attendance -->
            <div class="feature-card">
              <div class="feature-icon-wrap feature-icon--green">
                <!-- Heroicon: clock -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="26" height="26">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
              </div>
              <h3>Time &amp; Attendance</h3>
              <p>Track employee attendance, working hours, overtime, and absences with integrated time-tracking tools.</p>
              <ul class="feature-list">
                <li>Clock-in/out tracking</li>
                <li>Overtime calculations</li>
                <li>Shift management</li>
                <li>GPS location verification</li>
              </ul>
            </div>

            <!-- Leave Management -->
            <div class="feature-card">
              <div class="feature-icon-wrap feature-icon--blue">
                <!-- Heroicon: calendar-days -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="26" height="26">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H18v-.008Zm0 2.25h.008v.008H18V15Z" />
                </svg>
              </div>
              <h3>Leave Management</h3>
              <p>Streamline leave requests, approvals, and balance tracking with automated workflows.</p>
              <ul class="feature-list">
                <li>Leave request &amp; approval</li>
                <li>Multiple leave types</li>
                <li>Balance tracking</li>
                <li>Calendar integration</li>
              </ul>
            </div>

            <!-- Team Management -->
            <div class="feature-card">
              <div class="feature-icon-wrap feature-icon--orange">
                <!-- Heroicon: rectangle-group -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="26" height="26">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 7.125C2.25 6.504 2.754 6 3.375 6h6c.621 0 1.125.504 1.125 1.125v3.75c0 .621-.504 1.125-1.125 1.125h-6a1.125 1.125 0 0 1-1.125-1.125v-3.75ZM14.25 8.625c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 0 1-1.125-1.125v-8.25ZM3.75 16.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 0 1-1.125-1.125v-2.25Z" />
                </svg>
              </div>
              <h3>Team Management &amp; Collaboration</h3>
              <p>Organize teams with Trello and Jira-style task management, ticket systems, and built-in collaboration tools.</p>
              <ul class="feature-list">
                <li>Kanban boards &amp; task tracking</li>
                <li>Ticket management system</li>
                <li>Slack integration available</li>
                <li>Built-in team chat</li>
              </ul>
            </div>

            <!-- Productivity Analytics -->
            <div class="feature-card">
              <div class="feature-icon-wrap feature-icon--green">
                <!-- Heroicon: chart-bar -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="26" height="26">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                </svg>
              </div>
              <h3>Productivity Analytics</h3>
              <p>Track team performance with detailed productivity reports and actionable insights.</p>
              <ul class="feature-list">
                <li>Productivity score tracking</li>
                <li>Team member reports</li>
                <li>Performance dashboards</li>
                <li>Goal tracking &amp; OKRs</li>
              </ul>
            </div>

            <!-- SLA Compliance -->
            <div class="feature-card">
              <div class="feature-icon-wrap feature-icon--blue">
                <!-- Heroicon: shield-check -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="26" height="26">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                </svg>
              </div>
              <h3>SLA Compliance Monitoring</h3>
              <p>Monitor and maintain service level agreements with real-time compliance tracking.</p>
              <ul class="feature-list">
                <li>SLA compliance rate tracking</li>
                <li>Percentage-based metrics</li>
                <li>Automated compliance alerts</li>
                <li>Detailed audit trails</li>
              </ul>
            </div>

            <!-- Multi-Country -->
            <div class="feature-card">
              <div class="feature-icon-wrap feature-icon--orange">
                <!-- Heroicon: globe-alt -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="26" height="26">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                </svg>
              </div>
              <h3>Multi-Country &amp; Currency</h3>
              <p>Manage payroll across multiple countries with different currencies, tax rules, and labor laws.</p>
              <ul class="feature-list">
                <li>50+ countries supported</li>
                <li>Multi-currency payments</li>
                <li>Local compliance</li>
                <li>Exchange rate updates</li>
              </ul>
            </div>

            <!-- Reports -->
            <div class="feature-card">
              <div class="feature-icon-wrap feature-icon--green">
                <!-- Heroicon: document-chart-bar -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="26" height="26">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25M9 16.5v.75m3-3v3M15 12v5.25m-4.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
              </div>
              <h3>Reports &amp; Analytics</h3>
              <p>Generate comprehensive reports with real-time insights into payroll costs and trends.</p>
              <ul class="feature-list">
                <li>Payroll reports</li>
                <li>Tax reports</li>
                <li>Custom dashboards</li>
                <li>Export to Excel/PDF</li>
              </ul>
            </div>

            <!-- Security -->
            <div class="feature-card">
              <div class="feature-icon-wrap feature-icon--blue">
                <!-- Heroicon: lock-closed -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="26" height="26">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
              </div>
              <h3>Enterprise Security</h3>
              <p>Bank-level security with encryption, regular backups, and compliance with international standards.</p>
              <ul class="feature-list">
                <li>256-bit encryption</li>
                <li>Two-factor authentication</li>
                <li>SOC 2 compliant</li>
                <li>Daily backups</li>
              </ul>
            </div>

            <!-- Mobile — featured card -->
            <div class="feature-card feature-card--featured">
              <div class="featured-card-badge">
                <!-- Heroicon: star -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="13" height="13">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                </svg>
                Featured
              </div>
              <div class="feature-icon-wrap feature-icon--orange">
                <!-- Heroicon: device-phone-mobile -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="26" height="26">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                </svg>
              </div>
              <h3>Mobile Access – Fully Responsive</h3>
              <p>Complete mobile experience with responsive design. Access all features on the go.</p>
              <ul class="feature-list">
                <li>iOS &amp; Android native apps</li>
                <li>Fully responsive web design</li>
                <li>Employee self-service portal</li>
                <li>Mobile time tracking</li>
                <li>Push notifications &amp; alerts</li>
                <li>Offline access mode</li>
              </ul>
            </div>

            <!-- Live Support Chat -->
            <div class="feature-card">
              <div class="feature-icon-wrap feature-icon--green">
                <!-- Heroicon: chat-bubble-left-right -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="26" height="26">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                </svg>
              </div>
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

          <!-- CTA banner -->
          <div class="features-cta">
            <div class="features-cta-content">
              <div class="features-cta-icon">
                <!-- Heroicon: sparkles -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="36" height="36">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456Z" />
                </svg>
              </div>
              <h3>And Many More Features...</h3>
              <p>Productivity reports, SLA compliance tracking, team collaboration tools, advanced analytics, workflow automation, and much more!</p>
              <div class="features-cta-actions">
                <router-link to="/contact" class="btn-primary">
                  <!-- Heroicon: envelope -->
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="17" height="17">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                  </svg>
                  Contact Us for Full Feature List
                </router-link>
                <router-link to="/contact" class="btn-outline--inverted">
                  <!-- Heroicon: play -->
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="17" height="17">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" />
                  </svg>
                  Book a Demo
                </router-link>
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
/* ── Page Hero ── */
.page-hero {
  padding-top: calc(clamp(2.5rem, 6vw, 4rem) + 72px);
  padding-bottom: clamp(2.5rem, 6vw, 4rem);
  background-color: #001a5e;
  background-image:
    radial-gradient(ellipse 65% 55% at 15% 40%, rgba(74, 144, 217, 0.32) 0%, transparent 65%),
    radial-gradient(ellipse 80% 70% at 85% 20%, rgba(0, 71, 171, 0.45) 0%, transparent 60%),
    linear-gradient(155deg, #001a5e 0%, #0047AB 45%, #003a8c 100%);
  color: white;
  text-align: center;
}

.hero-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 1.25rem;
  color: #F5A623;
}

.page-title {
  font-size: clamp(2rem, 5vw, 3rem);
  font-weight: 800;
  margin-bottom: 1rem;
  line-height: 1.15;
  letter-spacing: -0.02em;
  color: #ffffff;
}

.page-subtitle {
  font-size: clamp(1rem, 2.5vw, 1.2rem);
  color: rgba(255, 255, 255, 0.72);
  max-width: 560px;
  margin: 0 auto;
  font-weight: 300;
  line-height: 1.7;
}

/* ── Feature Cards ── */
.features { padding: clamp(3rem, 8vw, 6rem) 0; background: #f0f4f8; }

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 340px), 1fr));
  gap: clamp(1.25rem, 2.5vw, 1.75rem);
}

.feature-card {
  background: white;
  padding: clamp(1.5rem, 3vw, 2rem);
  border-radius: 14px;
  border: 1px solid #dde3ee;
  border-left: 4px solid transparent;
  box-shadow: 0 2px 10px rgba(0, 71, 171, 0.05);
  transition: all 0.28s;
  position: relative;
}

/* Cycle left-border colors through logo palette */
.feature-card:nth-child(3n+1) { border-left-color: #0047AB; }
.feature-card:nth-child(3n+2) { border-left-color: #F5A623; }
.feature-card:nth-child(3n+3) { border-left-color: #2DB34A; }

.feature-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 12px 32px rgba(0, 71, 171, 0.13);
  border-color: #4A90D9;
  border-left-color: inherit;
}

/* Featured mobile card */
.feature-card--featured {
  border: 2px solid #0047AB !important;
  background: linear-gradient(135deg, rgba(0, 71, 171, 0.04) 0%, rgba(74, 144, 217, 0.06) 100%);
  box-shadow: 0 8px 28px rgba(0, 71, 171, 0.14);
}

.featured-card-badge {
  position: absolute;
  top: -13px;
  left: 50%;
  transform: translateX(-50%);
  background: linear-gradient(135deg, #0047AB 0%, #4A90D9 100%);
  color: white;
  padding: 0.35rem 1rem;
  border-radius: 9999px;
  font-size: 0.73rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  white-space: nowrap;
  display: flex;
  align-items: center;
  gap: 0.35rem;
  box-shadow: 0 4px 14px rgba(0, 71, 171, 0.3);
}

/* Icon wrappers — logo colors */
.feature-icon-wrap {
  width: 52px; height: 52px;
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  margin-bottom: 1.1rem;
}

.feature-icon--blue   { background: rgba(0, 71, 171, 0.1); color: #0047AB; }
.feature-icon--orange { background: rgba(245, 166, 35, 0.12); color: #c47f00; }
.feature-icon--green  { background: rgba(45, 179, 74, 0.1); color: #2DB34A; }

.feature-card h3 {
  font-size: clamp(1rem, 2.5vw, 1.15rem);
  font-weight: 700;
  margin-bottom: 0.65rem;
  color: #001a5e;
  line-height: 1.35;
}

.feature-card > p {
  color: #4b5563;
  margin-bottom: 1.25rem;
  line-height: 1.72;
  font-size: 0.92rem;
}

/* Check list — green ticks */
.feature-list { list-style: none; padding: 0; margin: 0; }

.feature-list li {
  color: #374151;
  margin-bottom: 0.55rem;
  padding-left: 1.5rem;
  position: relative;
  font-size: 0.88rem;
  line-height: 1.5;
}

.feature-list li::before {
  content: '';
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 16px; height: 16px;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2.5' stroke='%232DB34A'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m4.5 12.75 6 6 9-13.5'/%3E%3C/svg%3E");
  background-size: contain;
  background-repeat: no-repeat;
}

/* ── Features CTA banner ── */
.features-cta {
  margin-top: clamp(3rem, 6vw, 5rem);
  background-color: #001a5e;
  background-image:
    radial-gradient(ellipse 70% 60% at 90% 100%, rgba(245, 166, 35, 0.18) 0%, transparent 55%),
    radial-gradient(ellipse 50% 50% at 5% 5%, rgba(45, 179, 74, 0.1) 0%, transparent 60%),
    linear-gradient(148deg, #001a5e 0%, #0047AB 55%, #001240 100%);
  border-radius: 16px;
  padding: clamp(2rem, 5vw, 4rem);
  text-align: center;
  color: white;
  overflow: hidden;
}

.features-cta-icon {
  display: flex;
  justify-content: center;
  margin-bottom: 1rem;
  color: #F5A623;
}

.features-cta-content h3 {
  font-size: clamp(1.6rem, 4vw, 2.2rem);
  font-weight: 800;
  margin-bottom: 1rem;
  letter-spacing: -0.015em;
}

.features-cta-content p {
  font-size: clamp(0.95rem, 2.5vw, 1.1rem);
  margin-bottom: 2rem;
  color: rgba(255, 255, 255, 0.72);
  max-width: 720px;
  margin-left: auto;
  margin-right: auto;
  font-weight: 300;
  line-height: 1.75;
}

.features-cta-actions {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  justify-content: center;
  align-items: center;
}

@media (min-width: 600px) { .features-cta-actions { flex-direction: row; } }

/* Primary button */
.btn-primary {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: linear-gradient(135deg, #0047AB 0%, #4A90D9 100%);
  color: white !important;
  border: none;
  padding: 0.82rem 1.75rem;
  border-radius: 9999px;
  font-weight: 700;
  font-size: 0.93rem;
  cursor: pointer;
  transition: all 0.24s;
  box-shadow: 0 4px 18px rgba(0, 71, 171, 0.4);
  text-decoration: none;
  white-space: nowrap;
}

.btn-primary:hover {
  background: linear-gradient(135deg, #003a8c 0%, #3a7bc8 100%);
  transform: translateY(-2px);
  box-shadow: 0 8px 26px rgba(0, 71, 171, 0.5);
}

/* Inverted outline for dark backgrounds */
.btn-outline--inverted {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  background: transparent;
  color: white !important;
  border: 2px solid rgba(255, 255, 255, 0.5);
  padding: 0.82rem 1.75rem;
  border-radius: 9999px;
  font-weight: 600;
  font-size: 0.93rem;
  cursor: pointer;
  transition: all 0.24s;
  text-decoration: none;
  white-space: nowrap;
}

.btn-outline--inverted:hover {
  background: rgba(255, 255, 255, 0.12);
  border-color: rgba(255, 255, 255, 0.9);
  transform: translateY(-2px);
}
</style>