<template>
  <div class="landing-page">
    <!-- Navigation -->
    <nav class="navbar">
      <div class="container">
        <div class="nav-brand">
          <h1>Archangel Payroll</h1>
        </div>
        <div class="nav-links">
          <a href="#features" @click.prevent="scrollToSection('features')">Features</a>
          <a href="#solutions" @click.prevent="scrollToSection('solutions')">Solutions</a>
          <a href="#pricing" @click.prevent="scrollToSection('pricing')">Pricing</a>
          <a href="#testimonials" @click.prevent="scrollToSection('testimonials')">Testimonials</a>
          <a href="#contact" @click.prevent="scrollToSection('contact')">Contact</a>
          <button @click="scrollToDemo" class="btn-primary desktop-demo-btn">Request Demo</button>
        </div>
        <!-- Mobile Menu Button -->
        <button class="mobile-menu-btn" @click="toggleMobileMenu" :aria-expanded="mobileMenuOpen">
          <span></span>
          <span></span>
          <span></span>
        </button>
      </div>
      <!-- Mobile Menu -->
      <div class="mobile-menu" :class="{ open: mobileMenuOpen }">
        <div class="mobile-menu-container">
          <a href="#features" @click="closeMobileMenu">Features</a>
          <a href="#solutions" @click="closeMobileMenu">Solutions</a>
          <a href="#pricing" @click="closeMobileMenu">Pricing</a>
          <a href="#testimonials" @click="closeMobileMenu">Testimonials</a>
          <a href="#contact" @click="closeMobileMenu">Contact</a>
          <button @click="scrollToDemo" class="btn-primary mobile-demo-btn">Request Demo</button>
        </div>
      </div>
    </nav>

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
            <button @click="scrollToDemo" class="btn-primary btn-lg">
              Start Free Trial
            </button>
            <button @click="scrollToContact" class="btn-secondary btn-lg">
              Schedule a Demo
            </button>
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

    <!-- Features Section -->
    <section id="features" class="features">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Everything You Need to Manage Payroll</h2>
          <p class="section-subtitle">
            All features available on every plan. Choose the plan that fits your team size.
          </p>
        </div>
        <div class="features-grid">
          <div class="feature-card">
            <div class="feature-icon">💰</div>
            <h3>Automated Payroll Processing</h3>
            <p>Process payroll automatically with accurate calculations for salaries, bonuses, allowances, and deductions.</p>
            <ul class="feature-list">
              <li>✓ Automated salary calculations</li>
              <li>✓ Tax withholding & compliance</li>
              <li>✓ Direct deposit integration</li>
              <li>✓ Payslip generation</li>
            </ul>
          </div>
          <div class="feature-card">
            <div class="feature-icon">👥</div>
            <h3>Employee Management</h3>
            <p>Centralized employee database with comprehensive profiles, document management, and onboarding workflows.</p>
            <ul class="feature-list">
              <li>✓ Employee profiles & records</li>
              <li>✓ Document storage</li>
              <li>✓ Onboarding automation</li>
              <li>✓ Role-based access control</li>
            </ul>
          </div>
          <div class="feature-card">
            <div class="feature-icon">⏰</div>
            <h3>Time & Attendance</h3>
            <p>Track employee attendance, working hours, overtime, and absences with integrated time-tracking tools.</p>
            <ul class="feature-list">
              <li>✓ Clock-in/out tracking</li>
              <li>✓ Overtime calculations</li>
              <li>✓ Shift management</li>
              <li>✓ GPS location verification</li>
            </ul>
          </div>
          <div class="feature-card">
            <div class="feature-icon">🏖️</div>
            <h3>Leave Management</h3>
            <p>Streamline leave requests, approvals, and balance tracking with automated workflows.</p>
            <ul class="feature-list">
              <li>✓ Leave request & approval</li>
              <li>✓ Multiple leave types</li>
              <li>✓ Balance tracking</li>
              <li>✓ Calendar integration</li>
            </ul>
          </div>
          <div class="feature-card">
            <div class="feature-icon">🎯</div>
            <h3>Team Management & Collaboration</h3>
            <p>Organize teams with Trello and Jira-style task management, ticket systems, and built-in collaboration tools.</p>
            <ul class="feature-list">
              <li>✓ Kanban boards & task tracking</li>
              <li>✓ Ticket management system</li>
              <li>✓ Slack integration available</li>
              <li>✓ Built-in team chat</li>
            </ul>
          </div>
          <div class="feature-card">
            <div class="feature-icon">📈</div>
            <h3>Productivity Analytics</h3>
            <p>Track team performance with detailed productivity reports and actionable insights.</p>
            <ul class="feature-list">
              <li>✓ Productivity score tracking</li>
              <li>✓ Team member reports</li>
              <li>✓ Performance dashboards</li>
              <li>✓ Goal tracking & OKRs</li>
            </ul>
          </div>
          <div class="feature-card">
            <div class="feature-icon">✅</div>
            <h3>SLA Compliance Monitoring</h3>
            <p>Monitor and maintain service level agreements with real-time compliance tracking.</p>
            <ul class="feature-list">
              <li>✓ SLA compliance rate tracking</li>
              <li>✓ Percentage-based metrics</li>
              <li>✓ Automated compliance alerts</li>
              <li>✓ Detailed audit trails</li>
            </ul>
          </div>
          <div class="feature-card">
            <div class="feature-icon">🌍</div>
            <h3>Multi-Country & Currency</h3>
            <p>Manage payroll across multiple countries with different currencies, tax rules, and labor laws.</p>
            <ul class="feature-list">
              <li>✓ 50+ countries supported</li>
              <li>✓ Multi-currency payments</li>
              <li>✓ Local compliance</li>
              <li>✓ Exchange rate updates</li>
            </ul>
          </div>
          <div class="feature-card">
            <div class="feature-icon">📊</div>
            <h3>Reports & Analytics</h3>
            <p>Generate comprehensive reports with real-time insights into payroll costs and trends.</p>
            <ul class="feature-list">
              <li>✓ Payroll reports</li>
              <li>✓ Tax reports</li>
              <li>✓ Custom dashboards</li>
              <li>✓ Export to Excel/PDF</li>
            </ul>
          </div>
          <div class="feature-card">
            <div class="feature-icon">🔒</div>
            <h3>Enterprise Security</h3>
            <p>Bank-level security with encryption, regular backups, and compliance with international standards.</p>
            <ul class="feature-list">
              <li>✓ 256-bit encryption</li>
              <li>✓ Two-factor authentication</li>
              <li>✓ SOC 2 compliant</li>
              <li>✓ Daily backups</li>
            </ul>
          </div>
          <div class="feature-card featured-mobile">
            <div class="feature-icon">📱</div>
            <h3>Mobile Access - Fully Responsive</h3>
            <p>Complete mobile experience with responsive design. Access all features on the go.</p>
            <ul class="feature-list">
              <li>✓ iOS & Android native apps</li>
              <li>✓ Fully responsive web design</li>
              <li>✓ Employee self-service portal</li>
              <li>✓ Mobile time tracking</li>
              <li>✓ Push notifications & alerts</li>
              <li>✓ Offline access mode</li>
            </ul>
          </div>
          <div class="feature-card">
            <div class="feature-icon">💬</div>
            <h3>Live Support Chat</h3>
            <p>Real-time live chat support built directly into the platform so your team gets help instantly.</p>
            <ul class="feature-list">
              <li>✓ Real-time messaging</li>
              <li>✓ Visitor tracking</li>
              <li>✓ Agent dashboard</li>
              <li>✓ Chat history</li>
            </ul>
          </div>
        </div>

        <div class="features-cta">
          <div class="features-cta-content">
            <h3>🌟 And Many More Features...</h3>
            <p>Productivity reports, SLA compliance tracking, team collaboration tools, advanced analytics, workflow automation, and much more!</p>
            <div class="features-cta-actions">
              <button @click="scrollToContact" class="btn-primary">Contact Us for Full Feature List</button>
              <button @click="scrollToDemo" class="btn-outline">Book a Demo</button>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Solutions Section -->
    <section id="solutions" class="solutions">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Solutions for Every Business</h2>
          <p class="section-subtitle">Whether you're a startup or enterprise, we have the right solution</p>
        </div>
        <div class="solutions-grid">
          <div class="solution-card">
            <div class="solution-icon">🚀</div>
            <h3>For Startups</h3>
            <p>Simple, affordable payroll for growing teams</p>
            <ul>
              <li>1-50 employees</li>
              <li>Standard support</li>
              <li>All features included</li>
              <li>Email & chat support</li>
            </ul>
            <button class="btn-outline" @click="scrollToPricing">Learn More</button>
          </div>
          <div class="solution-card featured">
            <div class="featured-badge">Most Popular</div>
            <div class="solution-icon">🏢</div>
            <h3>For SMBs</h3>
            <p>Complete payroll and HR for medium businesses</p>
            <ul>
              <li>50-500 employees</li>
              <li>Priority support</li>
              <li>Multi-location support</li>
              <li>Dedicated account manager</li>
            </ul>
            <button class="btn-primary" @click="scrollToPricing">Learn More</button>
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
            <button class="btn-outline" @click="scrollToContact">Learn More</button>
          </div>
        </div>
      </div>
    </section>

    <!-- Benefits Section -->
    <section class="benefits">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Why Choose Archangel Payroll?</h2>
        </div>
        <div class="benefits-grid">
          <div class="benefit-item">
            <div class="benefit-number">01</div>
            <div class="benefit-content">
              <h3>All Features Included</h3>
              <p>Get access to every feature regardless of your plan. Only pay for the team size you need.</p>
            </div>
          </div>
          <div class="benefit-item">
            <div class="benefit-number">02</div>
            <div class="benefit-content">
              <h3>Priority Support</h3>
              <p>Higher plans include faster response times, dedicated account managers, and 24/7 premium support.</p>
            </div>
          </div>
          <div class="benefit-item">
            <div class="benefit-number">03</div>
            <div class="benefit-content">
              <h3>Scale Effortlessly</h3>
              <p>Upgrade your plan as you grow without losing features. All plans include our complete feature set.</p>
            </div>
          </div>
          <div class="benefit-item">
            <div class="benefit-number">04</div>
            <div class="benefit-content">
              <h3>Transparent Pricing</h3>
              <p>No hidden fees. Pay only for the number of employees. All features included in every plan.</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="pricing">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Simple, Transparent Pricing</h2>
          <p class="section-subtitle">All features included on every plan. Pay only for your team size.</p>
          <div class="pricing-toggle">
            <span :class="{ active: !annualBilling }" @click="annualBilling = false">Monthly</span>
            <button class="toggle-switch" @click="annualBilling = !annualBilling">
              <span class="toggle-slider" :class="{ annual: annualBilling }"></span>
            </button>
            <span :class="{ active: annualBilling }" @click="annualBilling = true">Annual (Save 20%)</span>
          </div>
        </div>
        <div class="pricing-grid">
          <div class="pricing-card">
            <div class="pricing-header">
              <h3>Starter</h3>
              <div class="pricing-amount">
                <span class="currency">$</span>
                <span class="amount">{{ annualBilling ? '39' : '49' }}</span>
                <span class="period">/month</span>
              </div>
              <p v-if="annualBilling" class="annual-savings">Billed annually ($468/year)</p>
              <p v-else>Perfect for small teams</p>
            </div>
            <div class="employee-limit"><span class="limit-label">Up to 10 employees</span></div>
            <ul class="pricing-features">
              <li>✓ All Features Included</li>
              <li>✓ Automated payroll</li>
              <li>✓ Employee self-service</li>
              <li>✓ Time & attendance</li>
              <li>✓ Leave management</li>
              <li>✓ Standard support</li>
            </ul>
            <button class="btn-outline btn-block" @click="scrollToDemo">Start Free Trial</button>
          </div>
          <div class="pricing-card featured">
            <div class="featured-badge">Most Popular</div>
            <div class="pricing-header">
              <h3>Professional</h3>
              <div class="pricing-amount">
                <span class="currency">$</span>
                <span class="amount">{{ annualBilling ? '89' : '109' }}</span>
                <span class="period">/month</span>
              </div>
              <p v-if="annualBilling" class="annual-savings">Billed annually ($1,068/year)</p>
              <p v-else>For growing businesses</p>
            </div>
            <div class="employee-limit"><span class="limit-label">Up to 100 employees</span></div>
            <ul class="pricing-features">
              <li>✓ All Features Included</li>
              <li>✓ Everything in Starter</li>
              <li>✓ Multi-country support</li>
              <li>✓ Advanced analytics</li>
              <li>✓ API access</li>
              <li>✓ Priority support</li>
            </ul>
            <button class="btn-primary btn-block" @click="scrollToDemo">Start Free Trial</button>
          </div>
          <div class="pricing-card">
            <div class="pricing-header">
              <h3>Enterprise</h3>
              <div class="pricing-amount"><span class="amount">Custom</span></div>
              <p>For large organizations</p>
            </div>
            <div class="employee-limit"><span class="limit-label">Unlimited employees</span></div>
            <ul class="pricing-features">
              <li>✓ All Features Included</li>
              <li>✓ Everything in Professional</li>
              <li>✓ Custom integrations</li>
              <li>✓ Dedicated account manager</li>
              <li>✓ Custom SLAs</li>
              <li>✓ 24/7 premium support</li>
            </ul>
            <button class="btn-outline btn-block" @click="scrollToContact">Contact Sales</button>
          </div>
        </div>
        <div class="pricing-note">
          <p>All plans include a 14-day free trial. No credit card required.</p>
        </div>
      </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonials">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Trusted by Leading Companies</h2>
          <p class="section-subtitle">See what our customers have to say</p>
        </div>
        <div class="testimonials-grid">
          <div class="testimonial-card">
            <div class="testimonial-rating">⭐⭐⭐⭐⭐</div>
            <p class="testimonial-text">"Archangel Payroll has completely transformed our HR operations. The multi-country support handles our Zambian, Kenyan, and South African payroll seamlessly."</p>
            <div class="testimonial-author">
              <div class="author-avatar">NN</div>
              <div class="author-info">
                <div class="author-name">Mr. Nawa Nawa</div>
                <div class="author-title">HR Director, EaglePay</div>
              </div>
            </div>
          </div>
          <div class="testimonial-card">
            <div class="testimonial-rating">⭐⭐⭐⭐⭐</div>
            <p class="testimonial-text">"As Managing Director of Castlebet, I need reliability and accuracy. Archangel Payroll delivers both. The compliance features ensure we're always up-to-date."</p>
            <div class="testimonial-author">
              <div class="author-avatar">MM</div>
              <div class="author-info">
                <div class="author-name">Margret Mwale</div>
                <div class="author-title">Managing Director, Castlebet</div>
              </div>
            </div>
          </div>
          <div class="testimonial-card">
            <div class="testimonial-rating">⭐⭐⭐⭐⭐</div>
            <p class="testimonial-text">"We switched to Archangel Payroll last year and haven't looked back. The platform scales perfectly with our growing team across Africa."</p>
            <div class="testimonial-author">
              <div class="author-avatar">AZ</div>
              <div class="author-info">
                <div class="author-name">Andrew Zulu</div>
                <div class="author-title">Founder, Zambezi Solutions</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Demo Request Section -->
    <section id="demo" class="demo-section">
      <div class="container">
        <div class="demo-content">
          <h2>See Archangel Payroll in Action</h2>
          <p>Request a personalized demo and discover how we can transform your payroll process</p>
        </div>
        <div class="demo-form-wrapper">
          <form @submit.prevent="submitDemoRequest" class="demo-form">
            <div class="form-group">
              <label for="company_name">Company Name *</label>
              <input id="company_name" v-model="demoForm.company_name" type="text" required placeholder="Your company name" />
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="first_name">First Name *</label>
                <input id="first_name" v-model="demoForm.first_name" type="text" required placeholder="John" />
              </div>
              <div class="form-group">
                <label for="last_name">Last Name *</label>
                <input id="last_name" v-model="demoForm.last_name" type="text" required placeholder="Doe" />
              </div>
            </div>
            <div class="form-group">
              <label for="email">Business Email *</label>
              <input id="email" v-model="demoForm.email" type="email" required placeholder="john@company.com" />
            </div>
            <div class="form-group">
              <label for="phone">Phone Number *</label>
              <input id="phone" v-model="demoForm.phone" type="tel" required placeholder="+1 (555) 000-0000" />
            </div>
            <div class="form-group">
              <label for="employee_count">Number of Employees</label>
              <select id="employee_count" v-model="demoForm.employee_count">
                <option value="">Select range</option>
                <option value="1-10">1-10</option>
                <option value="11-50">11-50</option>
                <option value="51-200">51-200</option>
                <option value="201-500">201-500</option>
                <option value="500+">500+</option>
              </select>
            </div>
            <div class="form-group">
              <label for="message">Tell us about your needs</label>
              <textarea id="message" v-model="demoForm.message" rows="4" placeholder="What are your payroll challenges?"></textarea>
            </div>
            <button type="submit" class="btn-primary btn-lg btn-block" :disabled="isSubmitting">
              {{ isSubmitting ? 'Submitting...' : 'Request Demo' }}
            </button>
            <p v-if="demoMessage" :class="['form-message', demoMessageType]">{{ demoMessage }}</p>
          </form>
        </div>
      </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact-section">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Get in Touch</h2>
          <p class="section-subtitle">We're here to help with any questions</p>
        </div>
        <div class="contact-grid">
          <div class="contact-info">
            <div class="contact-card">
              <div class="contact-card-icon">📧</div>
              <h3>Email Us</h3>
              <p>sales@archangelpayroll.com</p>
              <p>support@archangelpayroll.com</p>
            </div>
            <div class="contact-card">
              <div class="contact-card-icon">📞</div>
              <h3>Call Us</h3>
              <p>Sales: +260 211 123 4567</p>
              <p>Support: +260 211 123 4568</p>
            </div>
            <div class="contact-card">
              <div class="contact-card-icon">🕒</div>
              <h3>Business Hours</h3>
              <p>Monday - Friday</p>
              <p>8:00 AM - 6:00 PM CAT</p>
            </div>
            <div class="contact-card">
              <div class="contact-card-icon">💬</div>
              <h3>Live Chat</h3>
              <p>Available 24/7</p>
              <button class="btn-outline btn-sm" @click="startLiveChat">Start Chat</button>
            </div>
          </div>
          <div class="contact-form-wrapper">
            <form @submit.prevent="submitContactForm" class="contact-form">
              <div class="form-group">
                <label for="contact_name">Name *</label>
                <input id="contact_name" v-model="contactForm.name" type="text" required placeholder="Your name" />
              </div>
              <div class="form-group">
                <label for="contact_email">Email *</label>
                <input id="contact_email" v-model="contactForm.email" type="email" required placeholder="your@email.com" />
              </div>
              <div class="form-group">
                <label for="contact_subject">Subject *</label>
                <input id="contact_subject" v-model="contactForm.subject" type="text" required placeholder="How can we help?" />
              </div>
              <div class="form-group">
                <label for="contact_message">Message *</label>
                <textarea id="contact_message" v-model="contactForm.message" rows="5" required placeholder="Your message..."></textarea>
              </div>
              <button type="submit" class="btn-primary btn-lg btn-block" :disabled="isContactSubmitting">
                {{ isContactSubmitting ? 'Sending...' : 'Send Message' }}
              </button>
              <p v-if="contactMessage" :class="['form-message', contactMessageType]">{{ contactMessage }}</p>
            </form>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Frequently Asked Questions</h2>
        </div>
        <div class="faq-grid">
          <div class="faq-item" v-for="(faq, index) in faqs" :key="index">
            <div class="faq-question" @click="toggleFaq(index)">
              <h4>{{ faq.question }}</h4>
              <span class="faq-icon">{{ activeFaq === index ? '−' : '+' }}</span>
            </div>
            <div class="faq-answer" :class="{ active: activeFaq === index }">
              <p>{{ faq.answer }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="cta">
      <div class="container">
        <div class="cta-content">
          <h2>Ready to Transform Your Payroll?</h2>
          <p>Join thousands of companies that trust Archangel Payroll</p>
          <div class="cta-actions">
            <button @click="scrollToDemo" class="btn-primary btn-lg">Start Free Trial</button>
            <button @click="scrollToContact" class="btn-secondary btn-lg">Contact Sales</button>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
      <div class="container">
        <div class="footer-content">
          <div class="footer-section">
            <h3>Archangel Payroll</h3>
            <p>Modern payroll management for modern businesses.</p>
            <div class="social-links">
              <a href="#" aria-label="Twitter">𝕏</a>
              <a href="#" aria-label="LinkedIn">in</a>
              <a href="#" aria-label="Facebook">f</a>
            </div>
          </div>
          <div class="footer-section">
            <h4>Product</h4>
            <ul>
              <li><a href="#features">Features</a></li>
              <li><a href="#solutions">Solutions</a></li>
              <li><a href="#pricing">Pricing</a></li>
              <li><a href="#demo">Request Demo</a></li>
            </ul>
          </div>
          <div class="footer-section">
            <h4>Company</h4>
            <ul>
              <li><a href="#">About Us</a></li>
              <li><a href="#">Careers</a></li>
              <li><a href="#">Blog</a></li>
              <li><a href="#">Press</a></li>
            </ul>
          </div>
          <div class="footer-section">
            <h4>Legal</h4>
            <ul>
              <li><a href="#">Privacy Policy</a></li>
              <li><a href="#">Terms of Service</a></li>
              <li><a href="#">Security</a></li>
              <li><a href="#">Compliance</a></li>
            </ul>
          </div>
        </div>
        <div class="footer-bottom">
          <p>&copy; {{ new Date().getFullYear() }} Archangel Payroll. All rights reserved.</p>
          <div class="footer-badges">
            <span class="badge">🔒 SOC 2 Certified</span>
            <span class="badge">✓ GDPR Compliant</span>
            <span class="badge">⭐ 99.9% Uptime</span>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

// ─── Chat Widget Config ────────────────────────────────────────────────────────
const WIDGET_ID  = '3cfddf9e-c0b4-41e4-8af3-9aeaf58fe973'
const API_BASE   = 'https://preactively-martial-darcel.ngrok-free.dev/api'

// ─── Reactive State ───────────────────────────────────────────────────────────
const mobileMenuOpen   = ref(false)
const activeFaq        = ref(null)
const annualBilling    = ref(false)

const demoForm = ref({
  company_name: '', first_name: '', last_name: '',
  email: '', phone: '', employee_count: '', message: ''
})
const contactForm = ref({ name: '', email: '', subject: '', message: '' })

const isSubmitting        = ref(false)
const demoMessage         = ref('')
const demoMessageType     = ref('')
const isContactSubmitting = ref(false)
const contactMessage      = ref('')
const contactMessageType  = ref('')

// ─── Chat Widget Loader ───────────────────────────────────────────────────────
function loadChatWidget() {
  if (!WIDGET_ID || WIDGET_ID === '3cfddf9e-c0b4-41e4-8af3-9aeaf58fe973') return

  window.ChatSystemConfig = { widgetId: WIDGET_ID }

  const script    = document.createElement('script')
  script.src      = `${API_BASE.replace('/api', '')}/widget/chat-widget.js`
  script.async    = true
  script.id       = 'cs-widget-script'

  document.body.appendChild(script)
}

// ─── Start Live Chat ──────────────────────────────────────────────────────────
const startLiveChat = () => {
  if (window.ChatSystem && typeof window.ChatSystem.open === 'function') {
    window.ChatSystem.open()
  }
}

// ─── FAQs ─────────────────────────────────────────────────────────────────────
const faqs = [
  {
    question: 'Do all plans include all features?',
    answer: 'Yes! All plans include access to every feature. You only pay for the number of employees. Higher plans include priority support.'
  },
  {
    question: 'What support is included with each plan?',
    answer: 'Starter includes standard email and chat support. Professional includes priority support. Enterprise includes 24/7 premium support with dedicated account managers.'
  },
  {
    question: 'How does the team management feature work?',
    answer: 'Our team management system works like Trello and Jira, offering Kanban boards, ticket systems, task tracking, and collaboration tools.'
  },
  {
    question: 'How long does it take to set up?',
    answer: 'Most companies are up and running within 24-48 hours. Our onboarding team will guide you through the entire process.'
  },
  {
    question: 'Can I import my existing employee data?',
    answer: 'Yes! We support bulk import from Excel, CSV, and can integrate with most HR systems.'
  },
  {
    question: 'Is my data secure?',
    answer: 'Absolutely. We use bank-level 256-bit encryption, are SOC 2 compliant, and perform daily backups.'
  }
]

// ─── Navigation helpers ───────────────────────────────────────────────────────
const scrollToSection = (sectionId) => {
  document.getElementById(sectionId)?.scrollIntoView({ behavior: 'smooth' })
  closeMobileMenu()
}
const scrollToDemo    = () => scrollToSection('demo')
const scrollToContact = () => scrollToSection('contact')
const scrollToPricing = () => scrollToSection('pricing')
const toggleMobileMenu = () => { mobileMenuOpen.value = !mobileMenuOpen.value }
const closeMobileMenu  = () => { mobileMenuOpen.value = false }
const toggleFaq        = (i) => { activeFaq.value = activeFaq.value === i ? null : i }

// ─── Form handlers ────────────────────────────────────────────────────────────
const submitDemoRequest = async () => {
  isSubmitting.value = true
  demoMessage.value  = ''
  try {
    await new Promise(resolve => setTimeout(resolve, 1500))
    demoMessage.value     = "Thank you! We'll contact you shortly to schedule your demo."
    demoMessageType.value = 'success'
    demoForm.value = { company_name: '', first_name: '', last_name: '', email: '', phone: '', employee_count: '', message: '' }
  } catch {
    demoMessage.value     = 'Something went wrong. Please try again or contact us directly.'
    demoMessageType.value = 'error'
  } finally {
    isSubmitting.value = false
  }
}

const submitContactForm = async () => {
  isContactSubmitting.value = true
  contactMessage.value      = ''
  try {
    await new Promise(resolve => setTimeout(resolve, 1500))
    contactMessage.value     = "Message sent successfully! We'll get back to you soon."
    contactMessageType.value = 'success'
    contactForm.value = { name: '', email: '', subject: '', message: '' }
  } catch {
    contactMessage.value     = 'Failed to send message. Please try again or email us directly.'
    contactMessageType.value = 'error'
  } finally {
    isContactSubmitting.value = false
  }
}

// ─── Click outside to close mobile menu ──────────────────────────────────────
const handleClickOutside = (event) => {
  const nav = document.querySelector('.navbar')
  if (mobileMenuOpen.value && nav && !nav.contains(event.target)) {
    closeMobileMenu()
  }
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  loadChatWidget()
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  document.getElementById('cs-widget-script')?.remove()
  document.getElementById('cs-widget-root')?.remove()
})
</script>

<style scoped>

/* ===== Base Styles ===== */
.landing-page {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
  color: #1a1a1a;
  line-height: 1.6;
  overflow-x: hidden;
  -webkit-font-smoothing: antialiased;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
.container { width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 16px; }
.section-header { text-align: center; margin-bottom: 3rem; }
.section-title { font-size: clamp(1.75rem, 5vw, 2.5rem); font-weight: 700; margin-bottom: 1rem; color: #1a1a1a; line-height: 1.2; }
.section-subtitle { font-size: clamp(1rem, 2.5vw, 1.25rem); color: #666; max-width: 600px; margin: 0 auto; line-height: 1.6; }

/* ===== Buttons ===== */
.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 15px rgba(102,126,234,0.3); white-space: nowrap; }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102,126,234,0.4); }
.btn-primary:disabled { background: #94a3b8; cursor: not-allowed; transform: none; }
.btn-secondary { background: white; color: #667eea; border: 2px solid #667eea; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
.btn-secondary:hover { background: #667eea; color: white; transform: translateY(-2px); }
.btn-outline { background: transparent; color: #667eea; border: 2px solid #667eea; padding: 0.75rem 1.5rem; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
.btn-outline:hover { background: #667eea; color: white; }
.btn-lg { padding: 1rem 2rem; font-size: clamp(1rem, 2vw, 1.1rem); }
.btn-sm { padding: 0.5rem 1rem; font-size: 0.9rem; }
.btn-block { width: 100%; }

/* ===== Navbar ===== */
.navbar {
  background: rgba(255,255,255,0.95);
  backdrop-filter: blur(10px);
  box-shadow: 0 2px 20px rgba(0,0,0,0.08);
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 1000;
  padding: 1rem 0;
}
.navbar .container { display: flex; justify-content: space-between; align-items: center; gap: 1rem; }
.nav-brand h1 { font-size: clamp(1.25rem, 3vw, 1.5rem); font-weight: 700; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin: 0; }
.nav-links { display: flex; gap: clamp(1rem, 2vw, 2rem); align-items: center; }
.nav-links a { text-decoration: none; color: #666; font-weight: 500; transition: color 0.3s; font-size: clamp(0.9rem, 1vw, 1rem); }
.nav-links a:hover { color: #667eea; }
.mobile-menu-btn { display: none; flex-direction: column; gap: 4px; background: none; border: none; cursor: pointer; padding: 0.5rem; }
.mobile-menu-btn span { width: 24px; height: 2px; background: #333; transition: all 0.3s; }
.mobile-menu { display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; box-shadow: 0 4px 20px rgba(0,0,0,0.1); overflow: hidden; max-height: 0; transition: max-height 0.4s ease-out; }
.mobile-menu.open { max-height: 500px; }
.mobile-menu-container { padding: 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
.mobile-menu a { text-decoration: none; color: #666; font-weight: 500; padding: 0.75rem 0; font-size: 1.1rem; border-bottom: 1px solid #f3f4f6; }
.mobile-demo-btn { margin-top: 1rem; width: 100%; }

/* ===== Navbar offset: push page content below the fixed navbar ===== */
.hero {
  padding: clamp(3rem, 8vw, 6rem) 0 clamp(2rem, 6vw, 4rem);
  /* Add top padding equal to navbar height so content isn't hidden behind it */
  padding-top: calc(clamp(3rem, 8vw, 6rem) + 72px);
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

/* ===== Hero ===== */
.hero .container { display: grid; grid-template-columns: 1fr; gap: 3rem; align-items: center; }
@media (min-width: 992px) { .hero .container { grid-template-columns: 1fr 1fr; gap: 4rem; } }
.hero-badge { display: inline-block; background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.9rem; margin-bottom: 1.5rem; }
.hero-title { font-size: clamp(2rem, 6vw, 3.5rem); font-weight: 800; margin-bottom: 1.5rem; line-height: 1.2; }
.hero-subtitle { font-size: clamp(1.1rem, 3vw, 1.35rem); margin-bottom: 2rem; opacity: 0.95; line-height: 1.6; }
.hero-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 2rem; padding: 1.5rem; background: rgba(255,255,255,0.1); border-radius: 12px; }
.stat-number { font-size: clamp(1.75rem, 4vw, 2.5rem); font-weight: 800; margin-bottom: 0.5rem; }
.stat-label { font-size: 0.9rem; opacity: 0.9; }
.hero-actions { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 2rem; }
@media (min-width: 768px) { .hero-actions { flex-direction: row; } }
.hero-trust { font-size: 0.9rem; opacity: 0.8; }
.dashboard-preview { background: white; border-radius: 16px; padding: 1.5rem; box-shadow: 0 20px 60px rgba(0,0,0,0.3); color: #333; max-width: 500px; margin: 0 auto; }
.preview-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb; }
.preview-dots { display: flex; gap: 6px; }
.preview-dots span { width: 12px; height: 12px; border-radius: 50%; background: #d1d5db; }
.preview-title { font-weight: 600; color: #1a1a1a; }
.preview-content { display: grid; gap: 1rem; }
.preview-card { display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f9fafb; border-radius: 8px; }
.card-icon { font-size: 2rem; }
.card-label { font-size: 0.85rem; color: #666; }
.card-value { font-size: 1.5rem; font-weight: 700; color: #1a1a1a; }

/* ===== Features ===== */
.features { padding: clamp(3rem, 8vw, 6rem) 0; background: #f9fafb; }
.features-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 350px), 1fr)); gap: clamp(1.5rem, 3vw, 2rem); }
.feature-card { background: white; padding: clamp(1.5rem, 3vw, 2rem); border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: all 0.3s; border: 1px solid #e5e7eb; }
.feature-card:hover { transform: translateY(-5px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); border-color: #667eea; }
.feature-card.featured-mobile { border: 2px solid #667eea; background: linear-gradient(135deg, rgba(102,126,234,0.05) 0%, rgba(118,75,162,0.05) 100%); }
.feature-icon { font-size: clamp(2.5rem, 4vw, 3rem); margin-bottom: 1rem; display: block; }
.feature-card h3 { font-size: clamp(1.25rem, 2.5vw, 1.5rem); margin-bottom: 1rem; color: #1a1a1a; }
.feature-card > p { color: #666; margin-bottom: 1.5rem; line-height: 1.7; }
.feature-list { list-style: none; padding: 0; }
.feature-list li { color: #555; margin-bottom: 0.75rem; padding-left: 1.5rem; position: relative; font-size: 0.95rem; }
.feature-list li::before { content: '✓'; position: absolute; left: 0; color: #10b981; font-weight: bold; }
.features-cta { margin-top: clamp(3rem, 6vw, 5rem); background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; padding: clamp(2rem, 5vw, 4rem); text-align: center; color: white; }
.features-cta-content h3 { font-size: clamp(1.75rem, 4vw, 2.25rem); margin-bottom: 1rem; }
.features-cta-content p { font-size: clamp(1rem, 2.5vw, 1.25rem); margin-bottom: 2rem; opacity: 0.95; max-width: 800px; margin-left: auto; margin-right: auto; }
.features-cta-actions { display: flex; flex-direction: column; gap: 1rem; justify-content: center; align-items: center; }
@media (min-width: 768px) { .features-cta-actions { flex-direction: row; } }
.features-cta-actions .btn-outline { background: white; color: #667eea; border-color: white; }

/* ===== Solutions ===== */
.solutions { padding: clamp(3rem, 8vw, 6rem) 0; background: white; }
.solutions-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr)); gap: clamp(1.5rem, 3vw, 2rem); max-width: 1000px; margin: 0 auto; }
.solution-card { background: white; padding: clamp(1.5rem, 3vw, 2.5rem); border-radius: 12px; border: 2px solid #e5e7eb; text-align: center; transition: all 0.3s; position: relative; display: flex; flex-direction: column; }
.solution-card:hover { transform: translateY(-5px); border-color: #667eea; box-shadow: 0 12px 24px rgba(0,0,0,0.1); }
.solution-card.featured { border-color: #667eea; box-shadow: 0 12px 24px rgba(102,126,234,0.2); }
.featured-badge { position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.85rem; font-weight: 600; white-space: nowrap; }
.solution-icon { font-size: 3.5rem; margin-bottom: 1rem; }
.solution-card h3 { font-size: 1.75rem; margin-bottom: 0.5rem; color: #1a1a1a; }
.solution-card > p { color: #666; margin-bottom: 2rem; }
.solution-card ul { list-style: none; padding: 0; margin-bottom: 2rem; text-align: left; flex-grow: 1; }
.solution-card li { padding: 0.75rem 0; border-bottom: 1px solid #e5e7eb; color: #555; font-size: 0.95rem; }
.solution-card button { margin-top: auto; }

/* ===== Benefits ===== */
.benefits { padding: clamp(3rem, 8vw, 6rem) 0; background: white; }
.benefits-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 280px), 1fr)); gap: clamp(2rem, 4vw, 3rem); }
.benefit-item { display: flex; gap: clamp(1rem, 2vw, 2rem); align-items: flex-start; }
.benefit-number { font-size: clamp(2.5rem, 5vw, 3.5rem); font-weight: 800; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; line-height: 1; flex-shrink: 0; }
.benefit-content h3 { font-size: 1.5rem; margin-bottom: 0.75rem; color: #1a1a1a; }
.benefit-content p { color: #666; line-height: 1.7; }

/* ===== Pricing ===== */
.pricing { padding: clamp(3rem, 8vw, 6rem) 0; background: #f9fafb; }
.pricing-toggle { display: flex; align-items: center; justify-content: center; gap: 1rem; margin-top: 2rem; flex-wrap: wrap; }
.pricing-toggle span { color: #666; font-weight: 500; cursor: pointer; transition: color 0.3s; }
.pricing-toggle span.active { color: #667eea; font-weight: 600; }
.toggle-switch { width: 60px; height: 32px; background: #e5e7eb; border-radius: 50px; border: none; position: relative; cursor: pointer; transition: background 0.3s; }
.toggle-slider { position: absolute; left: 4px; top: 4px; width: 24px; height: 24px; background: white; border-radius: 50%; transition: transform 0.3s; box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
.toggle-slider.annual { transform: translateX(28px); }
.pricing-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr)); gap: clamp(1.5rem, 3vw, 2rem); max-width: 1000px; margin: 3rem auto 0; }
.pricing-card { background: white; padding: clamp(1.5rem, 3vw, 2.5rem); border-radius: 12px; border: 2px solid #e5e7eb; transition: all 0.3s; position: relative; display: flex; flex-direction: column; }
.pricing-card:hover { transform: translateY(-5px); box-shadow: 0 12px 24px rgba(0,0,0,0.1); }
.pricing-card.featured { border-color: #667eea; box-shadow: 0 12px 24px rgba(102,126,234,0.2); }
.pricing-header { text-align: center; margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 1px solid #e5e7eb; }
.pricing-header h3 { font-size: 1.5rem; margin-bottom: 1rem; color: #1a1a1a; }
.pricing-amount { display: flex; align-items: baseline; justify-content: center; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.25rem; }
.currency { font-size: 1.5rem; font-weight: 600; color: #666; }
.amount { font-size: clamp(3rem, 6vw, 4rem); font-weight: 800; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; line-height: 1; }
.period { font-size: 1rem; color: #666; }
.annual-savings { color: #10b981; font-weight: 600; font-size: 0.9rem; }
.pricing-header p { color: #666; }
.employee-limit { text-align: center; margin: 1.5rem 0; padding: 0.75rem; background: linear-gradient(135deg, rgba(102,126,234,0.1) 0%, rgba(118,75,162,0.1) 100%); border-radius: 8px; }
.limit-label { font-weight: 600; color: #667eea; font-size: 1.1rem; }
.pricing-features { list-style: none; padding: 0; margin-bottom: 2rem; flex-grow: 1; }
.pricing-features li { padding: 0.75rem 0; color: #555; border-bottom: 1px solid #f3f4f6; font-size: 0.95rem; }
.pricing-note { text-align: center; margin-top: 3rem; color: #666; font-size: 0.95rem; }

/* ===== Testimonials ===== */
.testimonials { padding: clamp(3rem, 8vw, 6rem) 0; background: white; }
.testimonials-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 320px), 1fr)); gap: clamp(1.5rem, 3vw, 2rem); }
.testimonial-card { background: #f9fafb; padding: clamp(1.5rem, 3vw, 2rem); border-radius: 12px; border: 1px solid #e5e7eb; display: flex; flex-direction: column; }
.testimonial-rating { font-size: 1.25rem; margin-bottom: 1rem; }
.testimonial-text { color: #333; font-size: 1.1rem; line-height: 1.7; margin-bottom: 1.5rem; font-style: italic; flex-grow: 1; }
.testimonial-author { display: flex; align-items: center; gap: 1rem; }
.author-avatar { width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; flex-shrink: 0; }
.author-name { font-weight: 600; color: #1a1a1a; }
.author-title { font-size: 0.9rem; color: #666; }

/* ===== Demo Section ===== */
.demo-section { padding: clamp(3rem, 8vw, 6rem) 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.demo-content { text-align: center; margin-bottom: clamp(2rem, 5vw, 3rem); }
.demo-content h2 { font-size: clamp(1.75rem, 5vw, 2.5rem); margin-bottom: 1rem; }
.demo-content p { font-size: clamp(1rem, 2.5vw, 1.25rem); opacity: 0.95; }
.demo-form-wrapper { max-width: 700px; margin: 0 auto; }
.demo-form { background: white; padding: clamp(1.5rem, 4vw, 3rem); border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }

/* ===== Contact ===== */
.contact-section { padding: clamp(3rem, 8vw, 6rem) 0; background: #f9fafb; }
.contact-grid { display: grid; grid-template-columns: 1fr; gap: clamp(2rem, 4vw, 4rem); }
@media (min-width: 992px) { .contact-grid { grid-template-columns: 1fr 1.5fr; } }
.contact-info { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 250px), 1fr)); gap: clamp(1rem, 2vw, 1.5rem); }
.contact-card { background: white; padding: 1.5rem; border-radius: 12px; border: 1px solid #e5e7eb; text-align: center; display: flex; flex-direction: column; align-items: center; }
.contact-card-icon { font-size: 2.5rem; margin-bottom: 1rem; }
.contact-card h3 { font-size: 1.25rem; margin-bottom: 0.75rem; color: #1a1a1a; }
.contact-card p { color: #666; margin: 0.25rem 0; font-size: 0.95rem; }
.contact-form-wrapper { background: white; padding: clamp(1.5rem, 3vw, 2.5rem); border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; }

/* ===== Forms ===== */
.form-group { margin-bottom: 1.5rem; }
.form-row { display: grid; grid-template-columns: 1fr; gap: 1rem; }
@media (min-width: 640px) { .form-row { grid-template-columns: 1fr 1fr; } }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333; font-size: 0.95rem; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.875rem; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 1rem; transition: all 0.3s; font-family: inherit; color: #1a1a1a; background-color: white; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,0.1); }
.form-message { margin-top: 1rem; padding: 1rem; border-radius: 8px; text-align: center; font-weight: 500; }
.form-message.success { background: #d1fae5; color: #065f46; }
.form-message.error { background: #fee2e2; color: #991b1b; }

/* ===== FAQ ===== */
.faq { padding: clamp(3rem, 8vw, 6rem) 0; background: white; }
.faq-grid { max-width: 800px; margin: 0 auto; }
.faq-item { margin-bottom: 1rem; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
.faq-question { padding: 1.5rem; background: white; cursor: pointer; display: flex; justify-content: space-between; align-items: center; gap: 1rem; transition: background 0.3s; }
.faq-question:hover { background: #f9fafb; }
.faq-question h4 { margin: 0; font-size: 1.1rem; color: #1a1a1a; line-height: 1.4; }
.faq-icon { font-size: 1.5rem; color: #667eea; flex-shrink: 0; }
.faq-answer { max-height: 0; overflow: hidden; transition: max-height 0.3s ease, padding 0.3s ease; background: #f9fafb; }
.faq-answer.active { max-height: 500px; padding: 1.5rem; }
.faq-answer p { margin: 0; color: #666; line-height: 1.7; }

/* ===== CTA ===== */
.cta { padding: clamp(3rem, 8vw, 6rem) 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-align: center; }
.cta-content h2 { font-size: clamp(2rem, 6vw, 3rem); margin-bottom: 1rem; }
.cta-content p { font-size: clamp(1rem, 2.5vw, 1.25rem); margin-bottom: 2rem; opacity: 0.95; }
.cta-actions { display: flex; flex-direction: column; gap: 1rem; justify-content: center; align-items: center; }
@media (min-width: 768px) { .cta-actions { flex-direction: row; } }

/* ===== Footer ===== */
.footer { background: #1a1a1a; color: white; padding: clamp(2rem, 5vw, 4rem) 0 clamp(1.5rem, 3vw, 2rem); }
.footer-content { display: grid; grid-template-columns: repeat(auto-fill, minmax(min(100%, 200px), 1fr)); gap: clamp(1.5rem, 3vw, 2rem); margin-bottom: clamp(2rem, 4vw, 3rem); }
.footer-section h3 { font-size: 1.5rem; margin-bottom: 1rem; }
.footer-section h4 { font-size: 1.1rem; margin-bottom: 1rem; }
.footer-section p { color: #9ca3af; line-height: 1.7; margin-bottom: 1rem; font-size: 0.95rem; }
.footer-section ul { list-style: none; padding: 0; margin: 0; }
.footer-section ul li { margin-bottom: 0.75rem; }
.footer-section a { color: #9ca3af; text-decoration: none; transition: color 0.3s; font-size: 0.95rem; }
.footer-section a:hover { color: white; }
.social-links { display: flex; gap: 1rem; margin-top: 1rem; }
.social-links a { width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; transition: all 0.3s; text-decoration: none; }
.social-links a:hover { background: #667eea; transform: translateY(-3px); }
.footer-bottom { border-top: 1px solid #374151; padding-top: 2rem; display: flex; flex-direction: column; gap: 1rem; justify-content: space-between; align-items: center; color: #9ca3af; text-align: center; }
@media (min-width: 768px) { .footer-bottom { flex-direction: row; } }
.footer-badges { display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: center; }
.badge { background: rgba(255,255,255,0.1); padding: 0.5rem 1rem; border-radius: 50px; font-size: 0.85rem; }

/* ===== Responsive ===== */
@media (max-width: 991px) {
  .nav-links { display: none; }
  .mobile-menu-btn { display: flex; }
  .mobile-menu { display: block; }
}
@media (max-width: 575px) {
  .hero-actions { flex-direction: column; }
  .hero-actions button { width: 100%; }
  input, select, textarea { font-size: 16px; } /* prevents iOS zoom */
}
</style>