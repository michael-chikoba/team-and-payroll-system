<template>
  <div class="page-wrapper">
    <LandingNavbar />
    <main>

      <section class="page-hero">
        <div class="container">
          <div class="hero-badge">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
            </svg>
            Stay Audit-Ready
          </div>
          <h1 class="page-title">Built-In Compliance for Every Jurisdiction</h1>
          <p class="page-subtitle">Archangel Payroll continuously monitors regulatory changes and automatically keeps your payroll compliant — so you don't have to.</p>
        </div>
      </section>

      <!-- Compliance Areas -->
      <section class="compliance-section">
        <div class="container">
          <div class="section-header">
            <h2 class="section-title">Compliance Coverage</h2>
            <p class="section-sub">From federal tax law to state-specific wage requirements, we've got it covered.</p>
          </div>
          <div class="compliance-grid">
            <div class="compliance-card" v-for="area in complianceAreas" :key="area.id">
              <div class="compliance-icon" :class="`compliance-icon--${area.color}`">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="26" height="26" v-html="area.iconPath" />
              </div>
              <div class="compliance-body">
                <h3>{{ area.title }}</h3>
                <p>{{ area.desc }}</p>
                <ul>
                  <li v-for="item in area.items" :key="item">{{ item }}</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- How It Works -->
      <section class="how-section">
        <div class="container">
          <div class="section-header">
            <h2 class="section-title">Automated Compliance Workflow</h2>
            <p class="section-sub">Our compliance engine works silently in the background so your team can focus on running the business.</p>
          </div>
          <div class="steps-row">
            <div class="step-card" v-for="(step, i) in steps" :key="step.id">
              <div class="step-number">{{ String(i + 1).padStart(2, '0') }}</div>
              <div class="step-icon" :class="`step-icon--${step.color}`">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="22" height="22" v-html="step.iconPath" />
              </div>
              <h3>{{ step.title }}</h3>
              <p>{{ step.desc }}</p>
            </div>
          </div>
        </div>
      </section>

      <!-- Compliance by State -->
      <section class="states-section">
        <div class="container">
          <div class="states-layout">
            <div class="states-text">
              <h2>All 50 States. One Platform.</h2>
              <p>Managing employees across multiple states is a compliance nightmare. Different wage laws, tax rates, and filing deadlines — Archangel tracks them all automatically.</p>
              <ul class="states-features">
                <li>
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                  Automatic state tax registration support
                </li>
                <li>
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                  Real-time minimum wage monitoring
                </li>
                <li>
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                  Paid leave law compliance tracking
                </li>
                <li>
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                  Multi-state employee apportionment
                </li>
                <li>
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                  New hire reporting in every state
                </li>
              </ul>
            </div>
            <div class="states-visual">
              <div class="states-card">
                <div class="states-card-header">Active Compliance Monitoring</div>
                <div class="states-metric-row" v-for="m in stateMetrics" :key="m.label">
                  <div class="states-metric-label">{{ m.label }}</div>
                  <div class="states-metric-value" :style="{ color: m.color }">{{ m.value }}</div>
                </div>
                <div class="states-badge">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                  All checks passing
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- CTA -->
      <section class="cta">
        <div class="container">
          <div class="cta-content">
            <div class="cta-icon-wrap">
              <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="36" height="36">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
              </svg>
            </div>
            <h2>Stop Worrying About Compliance</h2>
            <p>Let Archangel handle the complexity. Start your free 14-day trial and see compliance automation in action.</p>
            <div class="cta-actions">
              <router-link to="/contact" class="btn-primary btn-lg">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                Start Free Trial
              </router-link>
              <router-link to="/contact" class="btn-secondary btn-lg">Schedule a Demo</router-link>
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

const complianceAreas = [
  {
    id: 1, title: 'Federal Tax Compliance', color: 'blue',
    iconPath: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" />',
    desc: 'Automated federal payroll tax calculations, withholding, and filing.',
    items: ['IRS Form 941 filing', 'FICA (Social Security & Medicare)', 'FUTA tax management', 'W-2 and 1099 generation', 'ACA reporting (1094/1095)'],
  },
  {
    id: 2, title: 'State & Local Taxes', color: 'green',
    iconPath: '<path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z" />',
    desc: 'Coverage across all 50 states plus local jurisdictions.',
    items: ['State income tax withholding', 'State unemployment (SUI/SUTA)', 'Local and city tax handling', 'State new hire reporting', 'State-specific deductions'],
  },
  {
    id: 3, title: 'Wage & Hour Laws', color: 'orange',
    iconPath: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
    desc: 'Real-time monitoring of minimum wage and overtime requirements.',
    items: ['Federal & state minimum wage alerts', 'FLSA overtime calculations', 'Exempt/non-exempt classification', 'Tip credit management', 'Child labor law compliance'],
  },
  {
    id: 4, title: 'Benefits Compliance', color: 'blue',
    iconPath: '<path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />',
    desc: 'End-to-end benefits administration with compliance built in.',
    items: ['ACA employer mandate tracking', 'FSA/HSA contribution limits', 'COBRA administration', '401(k) contribution limits', 'ERISA compliance support'],
  },
  {
    id: 5, title: 'Leave Management', color: 'green',
    iconPath: '<path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />',
    desc: 'Compliant paid leave programs for every jurisdiction.',
    items: ['FMLA tracking and compliance', 'State paid family leave (PFL)', 'Paid sick leave laws', 'Military leave (USERRA)', 'Bereavement leave tracking'],
  },
  {
    id: 6, title: 'Record Keeping', color: 'orange',
    iconPath: '<path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 8.485-8.25 15.375-8.25 15.375S3.75 14.86 3.75 6.375a8.25 8.25 0 0 1 16.5 0Z" />',
    desc: 'Automated record retention meeting all regulatory requirements.',
    items: ['IRS 3–7 year retention rules', 'FLSA payroll record requirements', 'I-9 employment verification', 'Audit trail for all changes', 'Secure document storage'],
  },
]

const steps = [
  { id: 1, title: 'Monitor', desc: 'Our system continuously monitors federal, state, and local regulatory changes in real time.', color: 'blue', iconPath: '<path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />' },
  { id: 2, title: 'Analyze', desc: 'Changes are analyzed to determine their impact on your specific payroll configuration.', color: 'orange', iconPath: '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />' },
  { id: 3, title: 'Update', desc: 'Tax tables, rates, and rules are automatically updated before changes take effect.', color: 'green', iconPath: '<path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />' },
  { id: 4, title: 'Notify', desc: 'You receive clear, actionable notifications when any action is required on your end.', color: 'blue', iconPath: '<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />' },
]

const stateMetrics = [
  { label: 'States Monitored', value: '50 / 50', color: '#2DB34A' },
  { label: 'Regulations Tracked', value: '2,847', color: '#0047AB' },
  { label: 'Last Updated', value: 'Today', color: '#2DB34A' },
  { label: 'Compliance Score', value: '100%', color: '#F5A623' },
]
</script>

<style scoped>
.container { width: 100%; max-width: 1280px; margin: 0 auto; padding: 0 clamp(1rem, 4vw, 2rem); }

.page-hero {
  padding-top: calc(clamp(3rem, 8vw, 5rem) + 72px);
  padding-bottom: clamp(2rem, 5vw, 4rem);
  background-color: #001a5e;
  background-image:
    radial-gradient(ellipse 65% 55% at 15% 40%, rgba(74, 144, 217, 0.32) 0%, transparent 65%),
    linear-gradient(155deg, #001a5e 0%, #0047AB 45%, #003a8c 100%);
  color: white; text-align: center;
}
.hero-badge {
  display: inline-flex; align-items: center; gap: 0.45rem;
  background: rgba(245, 166, 35, 0.18); border: 1px solid rgba(245, 166, 35, 0.4);
  color: #F5A623; padding: 0.45rem 1rem; border-radius: 9999px;
  font-size: 0.85rem; font-weight: 600; margin-bottom: 1.5rem;
}
.page-title { font-size: clamp(2rem, 6vw, 3.2rem); font-weight: 800; margin-bottom: 1.25rem; line-height: 1.15; letter-spacing: -0.02em; }
.page-subtitle { font-size: clamp(1rem, 2.5vw, 1.2rem); opacity: 0.88; line-height: 1.7; font-weight: 300; max-width: 620px; margin: 0 auto; }

.section-header { text-align: center; margin-bottom: 3rem; }
.section-title { font-size: clamp(1.75rem, 4vw, 2.4rem); font-weight: 800; color: #001a5e; letter-spacing: -0.02em; margin-bottom: 0.75rem; }
.section-sub { color: #4b5563; font-size: 1rem; line-height: 1.7; max-width: 560px; margin: 0 auto; }

/* Compliance Cards */
.compliance-section { padding: clamp(3rem, 7vw, 5rem) 0; background: #f0f4f8; }
.compliance-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 360px), 1fr)); gap: 1.5rem; }
.compliance-card { background: white; border-radius: 14px; padding: 1.75rem; border: 1px solid #dde3ee; display: flex; gap: 1.25rem; transition: all 0.25s; }
.compliance-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,71,171,0.1); }
.compliance-icon { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.compliance-icon--blue   { background: rgba(0, 71, 171, 0.1); color: #0047AB; }
.compliance-icon--green  { background: rgba(45, 179, 74, 0.1); color: #2DB34A; }
.compliance-icon--orange { background: rgba(245, 166, 35, 0.12); color: #d18b00; }
.compliance-body h3 { font-size: 1rem; font-weight: 700; color: #001a5e; margin-bottom: 0.4rem; }
.compliance-body p  { font-size: 0.88rem; color: #6b7280; margin-bottom: 0.75rem; line-height: 1.5; }
.compliance-body ul { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.3rem; }
.compliance-body ul li { font-size: 0.85rem; color: #374151; display: flex; gap: 0.4rem; align-items: flex-start; }
.compliance-body ul li::before { content: '✓'; color: #2DB34A; font-weight: 700; flex-shrink: 0; }

/* Steps */
.how-section { padding: clamp(3rem, 7vw, 5rem) 0; background: white; }
.steps-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; }
.step-card { text-align: center; padding: 2rem 1.5rem; background: #f8fafc; border-radius: 14px; border: 1px solid #dde3ee; position: relative; }
.step-number { position: absolute; top: -14px; left: 50%; transform: translateX(-50%); background: #001a5e; color: white; font-size: 0.72rem; font-weight: 800; padding: 0.25rem 0.65rem; border-radius: 9999px; letter-spacing: 0.05em; }
.step-icon { width: 52px; height: 52px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
.step-icon--blue   { background: rgba(0, 71, 171, 0.1); color: #0047AB; }
.step-icon--green  { background: rgba(45, 179, 74, 0.1); color: #2DB34A; }
.step-icon--orange { background: rgba(245, 166, 35, 0.12); color: #d18b00; }
.step-card h3 { font-size: 1rem; font-weight: 700; color: #001a5e; margin-bottom: 0.5rem; }
.step-card p  { font-size: 0.88rem; color: #4b5563; line-height: 1.65; }

/* States */
.states-section { padding: clamp(3rem, 7vw, 5rem) 0; background: #f0f4f8; }
.states-layout { display: grid; grid-template-columns: 1fr; gap: 3rem; align-items: center; }
@media (min-width: 992px) { .states-layout { grid-template-columns: 1fr 1fr; } }
.states-text h2 { font-size: clamp(1.75rem, 4vw, 2.4rem); font-weight: 800; color: #001a5e; margin-bottom: 1.25rem; letter-spacing: -0.02em; }
.states-text p { color: #4b5563; line-height: 1.75; margin-bottom: 1.5rem; font-size: 0.97rem; }
.states-features { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.65rem; }
.states-features li { display: flex; align-items: center; gap: 0.6rem; font-size: 0.93rem; color: #374151; font-weight: 500; }
.states-features li svg { color: #2DB34A; flex-shrink: 0; }

.states-card { background: linear-gradient(135deg, #001a5e, #0047AB); border-radius: 16px; padding: 2rem; color: white; }
.states-card-header { font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; opacity: 0.7; margin-bottom: 1.5rem; }
.states-metric-row { display: flex; justify-content: space-between; align-items: center; padding: 0.85rem 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
.states-metric-row:last-of-type { border-bottom: none; }
.states-metric-label { font-size: 0.88rem; opacity: 0.8; }
.states-metric-value { font-size: 1rem; font-weight: 800; }
.states-badge { display: flex; align-items: center; gap: 0.5rem; margin-top: 1.5rem; font-size: 0.85rem; font-weight: 600; color: #2DB34A; background: rgba(45,179,74,0.12); border: 1px solid rgba(45,179,74,0.3); padding: 0.5rem 1rem; border-radius: 9999px; justify-content: center; }

/* CTA */
.cta {
  padding: clamp(3rem, 8vw, 6rem) 0;
  background-color: #001a5e;
  background-image: linear-gradient(148deg, #001a5e 0%, #0047AB 55%, #001240 100%);
  color: white; text-align: center;
}
.cta-icon-wrap { display: flex; justify-content: center; margin-bottom: 1.25rem; color: #F5A623; }
.cta-content h2 { font-size: clamp(1.75rem, 5vw, 2.8rem); margin-bottom: 1rem; font-weight: 800; letter-spacing: -0.02em; }
.cta-content p  { font-size: clamp(1rem, 2vw, 1.15rem); margin-bottom: 2.5rem; color: rgba(255,255,255,0.75); font-weight: 300; }
.cta-actions { display: flex; flex-direction: column; gap: 1rem; justify-content: center; align-items: center; }
@media (min-width: 600px) { .cta-actions { flex-direction: row; } }
.btn-primary { display: inline-flex; align-items: center; gap: 0.45rem; background: linear-gradient(135deg, #0047AB 0%, #4A90D9 100%); color: white !important; border: none; padding: 0.82rem 1.75rem; border-radius: 9999px; font-weight: 700; font-size: 0.95rem; cursor: pointer; transition: all 0.24s; box-shadow: 0 4px 18px rgba(0, 71, 171, 0.4); text-decoration: none; white-space: nowrap; }
.btn-primary:hover { background: linear-gradient(135deg, #003a8c 0%, #3a7bc8 100%); transform: translateY(-2px); }
.btn-secondary { display: inline-flex; align-items: center; gap: 0.45rem; background: transparent; color: white !important; border: 2px solid rgba(255, 255, 255, 0.55); padding: 0.82rem 1.75rem; border-radius: 9999px; font-weight: 600; font-size: 0.95rem; cursor: pointer; transition: all 0.24s; text-decoration: none; white-space: nowrap; }
.btn-secondary:hover { background: rgba(255, 255, 255, 0.12); border-color: rgba(255,255,255,0.9); transform: translateY(-2px); }
.btn-lg { padding: 1rem 2.2rem; font-size: 1rem; }
</style>