<template>
  <div class="page-wrapper">
    <LandingNavbar />
    <main>

      <section class="page-hero">
        <div class="container">
          <div class="hero-badge">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            Last Updated: March 1, 2026
          </div>
          <h1 class="page-title">Terms of Service</h1>
          <p class="page-subtitle">Please read these terms carefully before using Archangel Payroll. By using our services, you agree to these terms.</p>
        </div>
      </section>

      <section class="legal-section">
        <div class="container">
          <div class="legal-layout">

            <nav class="toc-nav">
              <div class="toc-title">Contents</div>
              <ul>
                <li v-for="section in sections" :key="section.id">
                  <a :href="`#${section.id}`" class="toc-link">{{ section.title }}</a>
                </li>
              </ul>
            </nav>

            <article class="legal-content">

              <div class="intro-callout">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="20" height="20">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                </svg>
                <p>These Terms of Service ("Terms") constitute a legally binding agreement between you ("Customer") and Archangel Technologies Inc. ("Archangel Payroll"). These Terms govern your access to and use of the Archangel Payroll platform and related services.</p>
              </div>

              <section id="acceptance">
                <h2>1. Acceptance of Terms</h2>
                <p>By creating an account, accessing, or using the Archangel Payroll platform, you confirm that you are at least 18 years old, have the legal authority to bind the business entity you represent, and agree to these Terms and our Privacy Policy.</p>
                <p>If you do not agree to these Terms, you may not use Archangel Payroll services.</p>
              </section>

              <section id="services">
                <h2>2. Description of Services</h2>
                <p>Archangel Payroll provides a cloud-based payroll management platform including payroll processing, tax calculation and filing, direct deposit services, employee self-service tools, compliance monitoring, and reporting and analytics.</p>
                <p>We reserve the right to modify, suspend, or discontinue any aspect of the service at any time with reasonable notice to active customers.</p>
              </section>

              <section id="accounts">
                <h2>3. Account Registration and Responsibilities</h2>
                <p>You are responsible for maintaining the confidentiality of your account credentials and for all activity that occurs under your account. You must immediately notify us of any unauthorized access or security breach.</p>
                <p>You agree to provide accurate, current, and complete information during registration and to keep this information updated. We reserve the right to suspend accounts with inaccurate information.</p>
              </section>

              <section id="payment">
                <h2>4. Payment Terms</h2>
                <p>Subscription fees are billed monthly or annually in advance. All fees are in US dollars unless otherwise stated. You authorize us to charge your designated payment method for all fees.</p>
                <h3>Late Payments</h3>
                <p>Invoices not paid within 30 days may result in service suspension. Overdue balances accrue interest at 1.5% per month or the maximum rate permitted by law, whichever is less.</p>
                <h3>Refunds</h3>
                <p>We do not offer refunds for partial months or unused subscription periods. However, if service outages caused by our failures prevent you from processing payroll, we will credit your account proportionally.</p>
              </section>

              <section id="data-responsibilities">
                <h2>5. Data and Customer Responsibilities</h2>
                <p>You are solely responsible for the accuracy of all data entered into the platform, including employee information, compensation data, and tax withholding elections. Archangel Payroll relies on your data to process payroll and file taxes; we are not liable for errors resulting from inaccurate data you provide.</p>
                <p>You are responsible for reviewing all payroll runs before approval and for ensuring the accuracy of your employees' information. You agree to maintain appropriate authorization from your employees for the collection and processing of their payroll data.</p>
              </section>

              <section id="acceptable-use">
                <h2>6. Acceptable Use</h2>
                <p>You agree not to:</p>
                <ul>
                  <li>Use the service for any unlawful purpose or in violation of any regulations</li>
                  <li>Attempt to gain unauthorized access to any part of the platform</li>
                  <li>Transmit malicious code, viruses, or disruptive data</li>
                  <li>Reverse engineer or attempt to extract the source code of our software</li>
                  <li>Resell, sublicense, or otherwise transfer access to the service without our written consent</li>
                  <li>Use the service to process payroll for entities you are not authorized to represent</li>
                </ul>
              </section>

              <section id="intellectual-property">
                <h2>7. Intellectual Property</h2>
                <p>Archangel Payroll retains all rights, title, and interest in and to the platform, including all software, algorithms, interfaces, and documentation. These Terms do not grant you any ownership rights to the platform.</p>
                <p>You retain ownership of your data. You grant Archangel Payroll a limited license to use your data solely to provide the services described herein.</p>
              </section>

              <section id="confidentiality">
                <h2>8. Confidentiality</h2>
                <p>Each party agrees to keep confidential any non-public information disclosed by the other party in connection with these Terms. This obligation does not apply to information that becomes publicly available through no fault of the receiving party, or that is required to be disclosed by law.</p>
              </section>

              <section id="warranties">
                <h2>9. Disclaimers and Warranties</h2>
                <p>THE SERVICE IS PROVIDED "AS IS" WITHOUT WARRANTIES OF ANY KIND. ARCHANGEL PAYROLL DISCLAIMS ALL WARRANTIES, EXPRESS OR IMPLIED, INCLUDING WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NON-INFRINGEMENT.</p>
                <p>We do not warrant that the service will be uninterrupted, error-free, or completely secure. Tax law interpretations provided by the platform should not be construed as legal advice.</p>
              </section>

              <section id="liability">
                <h2>10. Limitation of Liability</h2>
                <p>TO THE MAXIMUM EXTENT PERMITTED BY LAW, ARCHANGEL PAYROLL SHALL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, INCLUDING LOSS OF PROFITS OR DATA.</p>
                <p>Our total cumulative liability for any claims arising out of or related to these Terms shall not exceed the fees paid by you to Archangel Payroll in the three months preceding the claim.</p>
              </section>

              <section id="termination">
                <h2>11. Termination</h2>
                <p>Either party may terminate these Terms with 30 days written notice. We may suspend or terminate your account immediately if you materially breach these Terms, fail to pay fees, or engage in fraudulent activity.</p>
                <p>Upon termination, you may export your data for 90 days. After that period, we will delete your data in accordance with our Privacy Policy.</p>
              </section>

              <section id="governing-law">
                <h2>12. Governing Law</h2>
                <p>These Terms shall be governed by the laws of the State of Delaware, without regard to its conflict of law principles. Any disputes shall be resolved through binding arbitration in accordance with the American Arbitration Association rules, except that either party may seek injunctive relief in any court of competent jurisdiction.</p>
              </section>

              <section id="changes">
                <h2>13. Changes to Terms</h2>
                <p>We reserve the right to modify these Terms at any time. We will provide 30 days notice of material changes via email and in-app notification. Continued use of the service after the effective date constitutes acceptance of the revised Terms.</p>
              </section>

              <section id="contact">
                <h2>14. Contact</h2>
                <p>For questions about these Terms, contact us at:</p>
                <div class="contact-block">
                  <div><strong>Archangel Technologies Inc.</strong></div>
                  <div>Legal Department</div>
                  <div>Email: <a href="mailto:legal@archangelpayroll.com">legal@archangelpayroll.com</a></div>
                </div>
              </section>

            </article>
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

const sections = [
  { id: 'acceptance', title: '1. Acceptance of Terms' },
  { id: 'services', title: '2. Description of Services' },
  { id: 'accounts', title: '3. Account Registration' },
  { id: 'payment', title: '4. Payment Terms' },
  { id: 'data-responsibilities', title: '5. Data & Responsibilities' },
  { id: 'acceptable-use', title: '6. Acceptable Use' },
  { id: 'intellectual-property', title: '7. Intellectual Property' },
  { id: 'confidentiality', title: '8. Confidentiality' },
  { id: 'warranties', title: '9. Disclaimers & Warranties' },
  { id: 'liability', title: '10. Limitation of Liability' },
  { id: 'termination', title: '11. Termination' },
  { id: 'governing-law', title: '12. Governing Law' },
  { id: 'changes', title: '13. Changes to Terms' },
  { id: 'contact', title: '14. Contact' },
]
</script>

<style scoped>
.container { width: 100%; max-width: 1280px; margin: 0 auto; padding: 0 clamp(1rem, 4vw, 2rem); }

.page-hero {
  padding-top: calc(clamp(3rem, 8vw, 5rem) + 72px);
  padding-bottom: clamp(2rem, 5vw, 4rem);
  background-color: #001a5e;
  background-image: linear-gradient(155deg, #001a5e 0%, #0047AB 45%, #003a8c 100%);
  color: white; text-align: center;
}
.hero-badge {
  display: inline-flex; align-items: center; gap: 0.45rem;
  background: rgba(245, 166, 35, 0.18); border: 1px solid rgba(245, 166, 35, 0.4);
  color: #F5A623; padding: 0.45rem 1rem; border-radius: 9999px;
  font-size: 0.85rem; font-weight: 600; margin-bottom: 1.5rem;
}
.page-title { font-size: clamp(2rem, 6vw, 3rem); font-weight: 800; margin-bottom: 1.25rem; line-height: 1.15; letter-spacing: -0.02em; }
.page-subtitle { font-size: clamp(1rem, 2.5vw, 1.15rem); opacity: 0.88; line-height: 1.7; font-weight: 300; max-width: 600px; margin: 0 auto; }

.legal-section { padding: clamp(3rem, 7vw, 5rem) 0; background: #f0f4f8; }
.legal-layout { display: grid; grid-template-columns: 1fr; gap: 2.5rem; }
@media (min-width: 1024px) { .legal-layout { grid-template-columns: 260px 1fr; } }

.toc-nav { background: white; border-radius: 14px; padding: 1.5rem; border: 1px solid #dde3ee; height: fit-content; position: sticky; top: 100px; }
.toc-title { font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: #6b7280; margin-bottom: 1rem; }
.toc-nav ul { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.25rem; }
.toc-link { font-size: 0.85rem; color: #4b5563; text-decoration: none; padding: 0.35rem 0.5rem; border-radius: 6px; display: block; transition: all 0.2s; }
.toc-link:hover { background: rgba(0,71,171,0.06); color: #0047AB; }

.legal-content { background: white; border-radius: 14px; padding: clamp(1.5rem, 4vw, 3rem); border: 1px solid #dde3ee; }
.intro-callout { display: flex; gap: 0.75rem; background: rgba(0,71,171,0.05); border: 1px solid rgba(0,71,171,0.12); border-left: 4px solid #0047AB; border-radius: 8px; padding: 1rem 1.25rem; margin-bottom: 2rem; color: #1e40af; align-items: flex-start; }
.intro-callout p { margin: 0; font-size: 0.93rem; line-height: 1.65; }
.intro-callout svg { flex-shrink: 0; margin-top: 2px; }

.legal-content section { margin-bottom: 2.5rem; padding-bottom: 2.5rem; border-bottom: 1px solid #f0f4f8; }
.legal-content section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
.legal-content h2 { font-size: 1.35rem; font-weight: 800; color: #001a5e; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 2px solid #f0f4f8; }
.legal-content h3 { font-size: 1rem; font-weight: 700; color: #1f2937; margin: 1.25rem 0 0.5rem; }
.legal-content p { font-size: 0.95rem; color: #374151; line-height: 1.75; margin-bottom: 1rem; }
.legal-content p:last-child { margin-bottom: 0; }
.legal-content ul { list-style: none; padding: 0; margin: 0.75rem 0 1rem; display: flex; flex-direction: column; gap: 0.5rem; }
.legal-content ul li { display: flex; gap: 0.5rem; font-size: 0.93rem; color: #374151; line-height: 1.65; }
.legal-content ul li::before { content: '→'; color: #0047AB; flex-shrink: 0; font-weight: 700; }
.legal-content a { color: #0047AB; text-decoration: none; font-weight: 500; }
.legal-content a:hover { text-decoration: underline; }
.contact-block { background: #f8fafc; border: 1px solid #dde3ee; border-radius: 10px; padding: 1.25rem; margin-top: 1rem; font-size: 0.93rem; line-height: 2; color: #374151; }
</style>