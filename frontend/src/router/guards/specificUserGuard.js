// src/router/guards/specificUserGuard.js
import { useAuthStore } from '@/stores/auth';

export function specificUserGuard(to, from, next) {
  const authStore = useAuthStore();
  
  // Check if user is authenticated
  if (!authStore.isAuthenticated) {
    console.log('User not authenticated, redirecting to login');
    next('/auth/login');
    return;
  }

  // Check if user has admin role
  if (authStore.user?.role !== 'admin') {
    console.log('User is not admin, redirecting to unauthorized');
    next('/unauthorized');
    return;
  }

  // Check for specific user restriction in route meta
  const specificUserEmail = to.meta.specificUser;
  
  if (specificUserEmail) {
    // This route requires a specific user
    if (authStore.user?.email !== specificUserEmail) {
      console.log(`User ${authStore.user?.email} is not authorized for this route. Required: ${specificUserEmail}`);
      next('/unauthorized');
      return;
    }
  }

  console.log('User authorized for route:', to.path);
  next();
}