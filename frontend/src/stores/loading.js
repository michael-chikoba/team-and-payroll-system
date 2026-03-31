// src/plugins/loadingInterceptor.js
// Global loading overlay is navigation-only (see App.vue router guards).
// This interceptor is kept for any future per-request needs but does NOT
// touch the loading overlay.

export function setupLoadingInterceptor() {
  return (axiosInstance) => {
    // ── Request interceptor ─────────────────────────────────────────────
    // Reserved for auth headers, request logging, etc.
    axiosInstance.interceptors.request.use(
      (config) => config,
      (error) => Promise.reject(error)
    )

    // ── Response interceptor ────────────────────────────────────────────
    // Reserved for global error handling, token refresh, etc.
    axiosInstance.interceptors.response.use(
      (response) => response,
      (error) => Promise.reject(error)
    )

    return axiosInstance
  }
}