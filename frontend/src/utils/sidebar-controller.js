/**
 * sidebar-controller.js
 * Handles sidebar collapse (desktop) and mobile drawer behaviour.
 * Works standalone or can be adapted for Vue/React.
 *
 * Usage:
 *   import { SidebarController } from './sidebar-controller.js';
 *   const ctrl = new SidebarController();
 *
 * OR drop the script at the bottom of your HTML and call:
 *   document.addEventListener('DOMContentLoaded', () => new SidebarController());
 */

export class SidebarController {
  constructor() {
    this.MOBILE_BREAKPOINT = 768;
    this.TABLET_BREAKPOINT = 992;

    this.sidebar = document.querySelector('.sidebar');
    this.adminLayout = document.querySelector('.admin-layout');
    this.mobileOverlay = document.querySelector('.mobile-sidebar-overlay');
    this.mobileToggle = document.querySelector('.mobile-menu-toggle');
    this.collapseBtn = document.querySelector('.sidebar-collapse-btn');

    // Restore persisted state
    this.isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    this.isMobileOpen = false;

    this._detectDevice();
    this._applyState();
    this._bindEvents();

    // Re-evaluate on resize
    window.addEventListener('resize', () => {
      this._detectDevice();
      this._applyState();
    });
  }

  /* ---- Device detection ---- */
  _detectDevice() {
    const w = window.innerWidth;
    this.isMobile = w <= this.MOBILE_BREAKPOINT;
    this.isTablet = w > this.MOBILE_BREAKPOINT && w <= this.TABLET_BREAKPOINT;
    this.isDesktop = w > this.TABLET_BREAKPOINT;
  }

  /* ---- Apply current state to DOM ---- */
  _applyState() {
    if (!this.sidebar) return;

    if (this.isMobile) {
      // Mobile: drawer mode — remove desktop collapse classes
      this.sidebar.classList.remove('collapsed');
      this.sidebar.classList.toggle('mobile-open', this.isMobileOpen);
      if (this.mobileOverlay) {
        this.mobileOverlay.classList.toggle('active', this.isMobileOpen);
      }
      if (this.adminLayout) {
        this.adminLayout.classList.remove('sidebar-collapsed');
      }
      // Show mobile toggle, hide desktop collapse btn
      if (this.mobileToggle) this.mobileToggle.style.display = 'flex';
      if (this.collapseBtn) this.collapseBtn.style.display = 'none';
    } else {
      // Desktop / Tablet: collapse mode — close mobile drawer
      this.isMobileOpen = false;
      this.sidebar.classList.remove('mobile-open');
      if (this.mobileOverlay) this.mobileOverlay.classList.remove('active');

      // On tablet, force-collapse
      const effectivelyCollapsed = this.isTablet ? true : this.isCollapsed;
      this.sidebar.classList.toggle('collapsed', effectivelyCollapsed);
      if (this.adminLayout) {
        this.adminLayout.classList.toggle('sidebar-collapsed', effectivelyCollapsed);
      }

      // Hide mobile toggle, show desktop collapse btn (desktop only)
      if (this.mobileToggle) this.mobileToggle.style.display = 'none';
      if (this.collapseBtn) {
        this.collapseBtn.style.display = this.isDesktop ? 'flex' : 'none';
        // Update icon direction
        this.collapseBtn.setAttribute(
          'title',
          effectivelyCollapsed ? 'Expand sidebar' : 'Collapse sidebar'
        );
        this.collapseBtn.innerHTML = effectivelyCollapsed
          ? '&#9654;' // ▶
          : '&#9664;'; // ◀
      }
    }
  }

  /* ---- Event bindings ---- */
  _bindEvents() {
    // Desktop collapse toggle
    if (this.collapseBtn) {
      this.collapseBtn.addEventListener('click', () => this.toggleCollapse());
    }

    // Mobile hamburger
    if (this.mobileToggle) {
      this.mobileToggle.addEventListener('click', () => this.toggleMobile());
    }

    // Close on overlay click
    if (this.mobileOverlay) {
      this.mobileOverlay.addEventListener('click', () => this.closeMobile());
    }

    // Close mobile drawer on nav link click
    document.querySelectorAll('.nav-link, .nav-button, .nav-item').forEach(link => {
      link.addEventListener('click', () => {
        if (this.isMobile) this.closeMobile();
      });
    });
  }

  /* ---- Public API ---- */
  toggleCollapse() {
    if (this.isTablet) return; // Tablet stays forced-collapsed
    this.isCollapsed = !this.isCollapsed;
    localStorage.setItem('sidebarCollapsed', this.isCollapsed);
    this._applyState();
  }

  toggleMobile() {
    this.isMobileOpen = !this.isMobileOpen;
    this._applyState();
  }

  closeMobile() {
    this.isMobileOpen = false;
    this._applyState();
  }

  openMobile() {
    this.isMobileOpen = true;
    this._applyState();
  }
}

/* Auto-init if used as plain script (non-module) */
if (typeof window !== 'undefined' && !window.__sidebarControllerInit) {
  window.__sidebarControllerInit = true;
  document.addEventListener('DOMContentLoaded', () => new SidebarController());
}