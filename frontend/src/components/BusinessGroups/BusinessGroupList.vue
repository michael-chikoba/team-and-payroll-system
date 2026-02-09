<template>
  <div class="business-groups-container">
    <!-- Header -->
    <div class="header-section">
      <h1 class="page-title">Business Groups</h1>
      <button @click="showCreateModal = true" class="btn-primary">
        <i class="fas fa-plus"></i> Create Group
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state">
      <i class="fas fa-spinner fa-spin"></i> Loading groups...
    </div>

    <!-- Empty State -->
    <div v-else-if="groups.length === 0" class="empty-state">
      <i class="fas fa-users fa-3x"></i>
      <h3>No Business Groups</h3>
      <p>Create your first business group to start collaborating</p>
      <button @click="showCreateModal = true" class="btn-primary">
        Create First Group
      </button>
    </div>

    <!-- Groups Grid -->
    <div v-else class="groups-grid">
      <div
        v-for="group in groups"
        :key="group.id"
        class="group-card"
      >
        <div class="group-header">
          <h3>{{ group.name }}</h3>
          <span class="group-type-badge" :class="`badge-${group.group_type}`">
            {{ formatGroupType(group.group_type) }}
          </span>
        </div>
        <p class="group-description">{{ group.description || 'No description' }}</p>
        <div class="group-stats">
          <div class="stat">
            <i class="fas fa-building"></i>
            <span>{{ group.businesses_count || 0 }} Businesses</span>
          </div>
          <div class="stat">
            <i class="fas fa-ticket-alt"></i>
            <span>{{ group.tickets_count || 0 }} Tickets</span>
          </div>
          <div class="stat">
            <i class="fas fa-tasks"></i>
            <span>{{ group.tasks_count || 0 }} Tasks</span>
          </div>
        </div>
        <div class="group-footer">
          <span class="group-role">{{ getRole(group) }}</span>
          <span class="group-date">
            Joined {{ formatDate(group.pivot?.joined_at) }}
          </span>
        </div>
        <!-- Action Buttons -->
        <div class="group-actions">
          <button @click.stop="viewGroupDetails(group.id)" class="btn-view-details">
            <i class="fas fa-eye"></i> View Details
          </button>
          <button @click.stop="viewGroupDashboard(group.id)" class="btn-view-dashboard">
            <i class="fas fa-chart-line"></i> Dashboard
          </button>
        </div>
      </div>
    </div>

    <!-- Create Group Modal -->
    <CreateBusinessGroupModal
      v-if="showCreateModal"
      @close="showCreateModal = false"
      @created="onGroupCreated"
    />
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import CreateBusinessGroupModal from './CreateBusinessGroupModal.vue';

export default {
  name: 'BusinessGroupList',
  components: {
    CreateBusinessGroupModal
  },
  setup() {
    const router = useRouter();
    const groups = ref([]);
    const loading = ref(true);
    const showCreateModal = ref(false);

    // FIX: This line had a syntax error - removed incorrect assignment
    const apiClient = axios.create({
      baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000',
      withCredentials: true,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    // Add request interceptor
    apiClient.interceptors.request.use(
      async (config) => {
        // Get token from localStorage
        const authData = localStorage.getItem('archangel_auth');
        if (authData) {
          try {
            const parsed = JSON.parse(authData);
            if (parsed.token) {
              config.headers.Authorization = `Bearer ${parsed.token}`;
            }
          } catch (e) {
            console.error('Failed to parse auth data:', e);
          }
        }
        
        // Add CSRF token for non-GET requests
        if (config.method !== 'get') {
          const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
          if (token) {
            config.headers['X-CSRF-TOKEN'] = token;
          }
        }
        
        return config;
      },
      (error) => Promise.reject(error)
    );

    // Add response interceptor
    apiClient.interceptors.response.use(
      (response) => response,
      (error) => {
        console.error('API Error:', error.response?.status, error.message);
        
        if (error.response?.status === 401) {
          // Clear invalid auth data
          localStorage.removeItem('archangel_auth');
          router.push('/login');
        }
        
        return Promise.reject(error);
      }
    );

    const fetchGroups = async () => {
      try {
        loading.value = true;
        
        console.log('Fetching business groups...');
        
        // First ensure CSRF cookie is set
        await axios.get('/sanctum/csrf-cookie', {
          baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000',
          withCredentials: true
        });
        
        const response = await apiClient.get('/api/business-groups');
        console.log('Groups response:', response.data);
        
        // Handle different response formats
        if (response.data && Array.isArray(response.data.data)) {
          groups.value = response.data.data;
        } else if (Array.isArray(response.data)) {
          groups.value = response.data;
        } else if (response.data && response.data.groups) {
          groups.value = response.data.groups;
        } else {
          console.warn('Unexpected response format:', response.data);
          groups.value = [];
        }
      } catch (error) {
        console.error('Error fetching groups:', error);
        
        // Show user-friendly error message
        let errorMessage = 'Failed to load business groups';
        if (error.response?.data?.message) {
          errorMessage = error.response.data.message;
        } else if (error.response?.data?.error) {
          errorMessage = error.response.data.error;
        } else if (error.message) {
          errorMessage = error.message;
        }
        
        alert(`Error: ${errorMessage}. Please check your connection and try again.`);
        
        groups.value = [];
      } finally {
        loading.value = false;
      }
    };

    const viewGroupDashboard = (groupId) => {
      console.log('Navigating to group dashboard:', groupId);
      
      // FIX: Ensure we're passing the ID correctly, not the colon prefix
      if (router.hasRoute('business-group-dashboard')) {
        router.push({ 
          name: 'business-group-dashboard', 
          params: { id: groupId }
        });
      } else {
        console.warn('Dashboard route not found, redirecting to details');
        viewGroupDetails(groupId);
      }
    };

    const viewGroupDetails = (groupId) => {
      console.log('Navigating to group details:', groupId);
      
      // FIX: Important - pass just the ID, not the colon prefix
      if (router.hasRoute('BusinessGroupDetails')) {
        router.push({ 
          name: 'BusinessGroupDetails', 
          params: { id: groupId.toString() } // Ensure it's a string
        });
      } else {
        // Fallback: show alert with group info
        const group = groups.value.find(g => g.id === groupId);
        if (group) {
          alert(`Group Details:\nName: ${group.name}\nType: ${group.group_type}\nDescription: ${group.description}`);
        }
      }
    };

    const formatGroupType = (type) => {
      if (!type) return 'Unknown';
      return type.charAt(0).toUpperCase() + type.slice(1).replace('_', ' ');
    };

    const getRole = (group) => {
      return group.pivot?.role?.toUpperCase() || 'MEMBER';
    };

    const formatDate = (date) => {
      if (!date) return 'N/A';
      try {
        const d = new Date(date);
        return d.toLocaleDateString('en-US', { 
          month: 'short', 
          day: 'numeric', 
          year: 'numeric' 
        });
      } catch (error) {
        console.warn('Invalid date:', date);
        return 'Invalid date';
      }
    };

    const onGroupCreated = (newGroup) => {
      groups.value.unshift(newGroup);
      showCreateModal.value = false;
      
      // Show success message
      alert(`Business group "${newGroup.name}" created successfully!`);
    };

    onMounted(() => {
      fetchGroups();
    });

    return {
      groups,
      loading,
      showCreateModal,
      viewGroupDashboard,
      viewGroupDetails,
      formatGroupType,
      getRole,
      formatDate,
      onGroupCreated
    };
  }
};
</script>

<style scoped>
.business-groups-container {
  padding: 24px;
  max-width: 1400px;
  margin: 0 auto;
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
  flex-wrap: wrap;
  gap: 16px;
}

.page-title {
  font-size: 28px;
  font-weight: 700;
  color: #1a202c;
  margin: 0;
}

.btn-primary {
  background-color: #4f46e5;
  color: white;
  padding: 12px 24px;
  border-radius: 8px;
  border: none;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
  display: flex;
  align-items: center;
  gap: 8px;
  white-space: nowrap;
}

.btn-primary:hover {
  background-color: #4338ca;
}

.btn-primary:active {
  transform: translateY(1px);
}

.loading-state {
  text-align: center;
  padding: 60px 20px;
  color: #6b7280;
  font-size: 18px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 16px;
}

.loading-state i {
  font-size: 32px;
}

.empty-state {
  text-align: center;
  padding: 80px 20px;
  color: #6b7280;
  background: #f9fafb;
  border-radius: 12px;
  border: 2px dashed #e5e7eb;
}

.empty-state i {
  color: #d1d5db;
  margin-bottom: 20px;
}

.empty-state h3 {
  font-size: 24px;
  color: #374151;
  margin-bottom: 8px;
}

.empty-state p {
  margin-bottom: 24px;
  color: #6b7280;
}

.groups-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 24px;
}

@media (max-width: 768px) {
  .groups-grid {
    grid-template-columns: 1fr;
  }
  
  .header-section {
    flex-direction: column;
    align-items: stretch;
  }
  
  .btn-primary {
    justify-content: center;
  }
}

.group-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 24px;
  transition: all 0.2s;
  position: relative;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.group-card:hover {
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.group-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.group-header h3 {
  font-size: 20px;
  font-weight: 700;
  color: #1a202c;
  margin: 0;
  flex: 1;
  word-break: break-word;
  margin-right: 12px;
}

.group-type-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  text-transform: uppercase;
  white-space: nowrap;
}

.badge-holding { background-color: #dbeafe; color: #1e40af; }
.badge-franchise { background-color: #fef3c7; color: #92400e; }
.badge-subsidiary { background-color: #d1fae5; color: #065f46; }
.badge-partnership { background-color: #e0e7ff; color: #3730a3; }
.badge-unknown { background-color: #f3f4f6; color: #6b7280; }

.group-description {
  color: #6b7280;
  font-size: 14px;
  margin-bottom: 20px;
  line-height: 1.5;
  flex: 1;
}

.group-stats {
  display: flex;
  gap: 16px;
  margin-bottom: 16px;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
  flex-wrap: wrap;
}

.stat {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #6b7280;
  font-size: 13px;
  white-space: nowrap;
}

.stat i {
  color: #9ca3af;
  width: 16px;
  text-align: center;
}

.group-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 12px;
  border-top: 1px solid #e5e7eb;
  font-size: 12px;
  margin-bottom: 16px;
  flex-wrap: wrap;
  gap: 8px;
}

.group-role {
  background-color: #f3f4f6;
  padding: 4px 10px;
  border-radius: 6px;
  font-weight: 600;
  color: #374151;
  white-space: nowrap;
}

.group-date {
  color: #9ca3af;
  white-space: nowrap;
}

.group-actions {
  display: flex;
  gap: 12px;
  margin-top: auto;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}

.btn-view-details {
  flex: 1;
  background-color: #4f46e5;
  color: white;
  padding: 10px 16px;
  border-radius: 6px;
  border: none;
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: background-color 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  white-space: nowrap;
}

.btn-view-details:hover {
  background-color: #4338ca;
}

.btn-view-dashboard {
  flex: 1;
  background-color: white;
  color: #4f46e5;
  padding: 10px 16px;
  border-radius: 6px;
  border: 1px solid #4f46e5;
  font-weight: 500;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  white-space: nowrap;
}

.btn-view-dashboard:hover {
  background-color: #f5f5ff;
}

.btn-view-details:active,
.btn-view-dashboard:active {
  transform: translateY(1px);
}
</style>