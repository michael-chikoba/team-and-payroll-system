<template>
  <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal integrations-modal" @click.stop>
      <!-- Header -->
      <div class="modal-header">
        <h2>Channel Integrations</h2>
        <button @click="$emit('close')" class="close-btn">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <!-- Tabs -->
      <div class="tabs">
        <button
          class="tab"
          :class="{ active: activeTab === 'list' }"
          @click="activeTab = 'list'"
        >
          All Integrations ({{ integrations.length }})
        </button>
        <button
          class="tab"
          :class="{ active: activeTab === 'create' }"
          @click="activeTab = 'create'"
        >
          Create New
        </button>
      </div>

      <!-- Tab Content -->
      <div class="modal-body">
        <!-- List Tab -->
        <div v-if="activeTab === 'list'">
          <div class="integration-info-section">
            <h3>Current Channel: {{ currentChannelName }}</h3>
            <p class="channel-description">{{ currentChannelDescription || 'No description' }}</p>
          </div>

          <div v-if="loading" class="loading-state">
            <p>Loading integrations...</p>
          </div>

          <div v-else-if="integrations.length === 0" class="empty-state">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path>
            </svg>
            <h3>No integrations yet</h3>
            <p>Create an integration to connect external applications to channels</p>
            <button @click="activeTab = 'create'" class="btn-primary">Create Integration</button>
          </div>

          <div v-else class="integrations-list">
            <div v-for="integration in integrations" :key="integration.id" class="integration-card">
              <div class="integration-header">
                <div class="integration-icon">
                  <img v-if="integration.icon_url" :src="integration.icon_url" alt="" />
                  <svg v-else width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path>
                  </svg>
                </div>
                <div class="integration-info">
                  <h3>{{ integration.name }}</h3>
                  <p>{{ integration.description || 'No description' }}</p>
                </div>
                <div class="integration-status">
                  <span class="status-badge" :class="{ active: integration.is_active }">
                    {{ integration.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </div>
              </div>

              <div class="integration-meta">
                <div class="meta-item">
                  <span class="meta-label">Channel:</span>
                  <span class="meta-value">{{ integration.channel_name || currentChannelName }}</span>
                </div>
                <div class="meta-item">
                  <span class="meta-label">API Key:</span>
                  <span class="meta-value api-key" @click="showApiKey(integration)" title="Click to view">
                    {{ formatApiKey(integration.api_key) }}
                  </span>
                </div>
                <div v-if="integration.webhook_secret" class="meta-item">
                  <span class="meta-label">Webhook Secret:</span>
                  <span class="meta-value secret" @click="showWebhookSecret(integration)" title="Click to view">
                    {{ integration.webhook_secret ? '********' : 'Not set' }}
                  </span>
                </div>
                <div class="meta-item">
                  <span class="meta-label">Created:</span>
                  <span class="meta-value">{{ formatDate(integration.created_at) }}</span>
                </div>
              </div>

              <div class="integration-stats">
                <div class="stat">
                  <span class="stat-label">Messages Sent</span>
                  <span class="stat-value">{{ integration.message_count || 0 }}</span>
                </div>
                <div class="stat">
                  <span class="stat-label">Last Used</span>
                  <span class="stat-value">{{ formatDate(integration.last_used_at) }}</span>
                </div>
              </div>

              <div class="integration-actions">
                <button @click="copyIntegrationDetails(integration)" class="btn-secondary">
                  Copy Details
                </button>
                <button @click="viewLogs(integration)" class="btn-secondary">
                  View Logs
                </button>
                <button @click="toggleIntegration(integration)" class="btn-secondary">
                  {{ integration.is_active ? 'Disable' : 'Enable' }}
                </button>
                <button @click="regenerateApiKey(integration)" class="btn-secondary">
                  Regenerate Key
                </button>
                <button @click="deleteIntegration(integration)" class="btn-danger">
                  Delete
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Create Tab -->
        <div v-if="activeTab === 'create'" class="create-form">
          <div class="form-section">
            <h3>Select Channel</h3>
            <div class="form-group">
              <label>Channel *</label>
              <select v-model="newIntegration.group_id" class="form-input">
                <option value="">Select a channel or group</option>
                <option v-for="ch in channels" :key="ch.id" :value="ch.id">
                  {{ getChatName(ch) }}
                </option>
              </select>
              <span class="help-text">Select the channel this integration will send messages to</span>
            </div>
          </div>

          <div class="form-section">
            <h3>Integration Details</h3>
            <div class="form-group">
              <label>Integration Name *</label>
              <input
                v-model="newIntegration.name"
                type="text"
                placeholder="e.g., GitHub Notifications, CI/CD Bot"
                class="form-input"
                @input="generateDefaultDescription"
              />
              <span class="help-text">Give your integration a descriptive name</span>
            </div>

            <div class="form-group">
              <label>Description</label>
              <textarea
                v-model="newIntegration.description"
                placeholder="What does this integration do?"
                class="form-input"
                rows="3"
              ></textarea>
              <span class="help-text">Describe what this integration will be used for</span>
            </div>

            <div class="form-group">
              <label>Icon URL (Optional)</label>
              <input
                v-model="newIntegration.icon_url"
                type="url"
                placeholder="https://example.com/icon.png"
                class="form-input"
              />
              <span class="help-text">URL to an icon that will represent this integration</span>
            </div>
          </div>

          <div class="form-section">
            <h3>Security Settings</h3>
            <div class="form-group">
              <label class="checkbox-label">
                <input type="checkbox" v-model="newIntegration.generate_webhook_secret" />
                Generate webhook secret for signature verification
              </label>
              <span class="help-text">Recommended for secure integrations (optional)</span>
            </div>
          </div>

          <div class="form-actions">
            <button
              @click="createIntegration"
              :disabled="!canCreate || creating"
              class="btn-primary"
            >
              <span v-if="creating">Creating...</span>
              <span v-else>Create Integration</span>
            </button>
            <button @click="resetForm" class="btn-secondary">
              Cancel
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- API Key / Details Modal -->
    <div v-if="showApiKeyModal" class="modal-overlay" @click.self="closeApiKeyModal">
      <div class="modal api-key-modal" @click.stop>
        <div class="modal-header">
          <h2>Integration Details</h2>
          <button @click="closeApiKeyModal" class="close-btn">×</button>
        </div>
        <div class="modal-body">
          <div v-if="createdIntegration || currentIntegration" class="success-message">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <p>Integration {{ createdIntegration ? 'created' : 'details' }} successfully!</p>
          </div>

          <div class="integration-details">
            <div class="detail-item">
              <span class="detail-label">Name:</span>
              <span class="detail-value">{{ currentIntegration?.name || createdIntegration?.name }}</span>
            </div>
            <div class="detail-item">
              <span class="detail-label">Channel:</span>
              <span class="detail-value">{{ currentChannelName }}</span>
            </div>
          </div>

          <div class="api-key-section">
            <h3>API Key</h3>
            <p class="warning-text">⚠️ Save this key now! You won't be able to see it again.</p>
            <div class="key-display">
              <code>{{ currentIntegration?.api_key || createdIntegration?.api_key }}</code>
              <button
                @click="copyToClipboard(currentIntegration?.api_key || createdIntegration?.api_key)"
                class="copy-btn"
              >
                Copy
              </button>
            </div>
          </div>

          <div
            v-if="currentIntegration?.webhook_secret || createdIntegration?.webhook_secret"
            class="api-key-section"
          >
            <h3>Webhook Secret</h3>
            <p class="help-text">Use this to verify webhook signatures</p>
            <div class="key-display">
              <code>{{ currentIntegration?.webhook_secret || createdIntegration?.webhook_secret }}</code>
              <button
                @click="copyToClipboard(currentIntegration?.webhook_secret || createdIntegration?.webhook_secret)"
                class="copy-btn"
              >
                Copy
              </button>
            </div>
          </div>

          <div class="usage-instructions">
            <h3>How to Use</h3>
            <p class="help-text">Send messages to the channel using the API key:</p>
            <div class="code-block">
              <pre>{{ getUsageExample }}</pre>
              <button @click="copyToClipboard(getUsageExample)" class="copy-btn">Copy</button>
            </div>

            <p class="help-text">Test with curl:</p>
            <div class="code-block">
              <pre>{{ getCurlExample }}</pre>
              <button @click="copyToClipboard(getCurlExample)" class="copy-btn">Copy</button>
            </div>
          </div>

          <div class="api-actions">
            <button @click="closeApiKeyModal" class="btn-primary full-width">
              I've Saved the Key
            </button>
            <button @click="downloadIntegrationDetails" class="btn-secondary full-width">
              Download Details as JSON
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Logs Modal -->
    <div v-if="showLogsModal" class="modal-overlay" @click.self="showLogsModal = false">
      <div class="modal logs-modal" @click.stop>
        <div class="modal-header">
          <h2>Integration Logs - {{ selectedIntegration?.name }}</h2>
          <button @click="showLogsModal = false" class="close-btn">×</button>
        </div>
        <div class="modal-body">
          <div class="logs-filter">
            <div class="filter-group">
              <label>Date Range:</label>
              <select v-model="logFilter.days" class="form-input small">
                <option value="7">Last 7 days</option>
                <option value="30">Last 30 days</option>
                <option value="90">Last 90 days</option>
                <option value="365">Last year</option>
              </select>
            </div>
            <div class="filter-group">
              <label>Status:</label>
              <select v-model="logFilter.status" class="form-input small">
                <option value="">All</option>
                <option value="success">Success</option>
                <option value="failed">Failed</option>
              </select>
            </div>
            <button @click="refreshLogs" class="btn-secondary">Refresh</button>
          </div>

          <div v-if="loadingLogs" class="loading-state">
            <p>Loading logs...</p>
          </div>
          <div v-else-if="logs.length === 0" class="empty-state">
            <p>No logs found for the selected filters</p>
          </div>
          <div v-else class="logs-list">
            <div v-for="log in logs" :key="log.id" class="log-entry" :class="log.status">
              <div class="log-header">
                <span class="log-action">{{ log.action }}</span>
                <span class="log-status">{{ log.status }}</span>
                <span class="log-time">{{ formatDate(log.created_at) }}</span>
              </div>
              <div v-if="log.message" class="log-message">{{ log.message }}</div>
              <div v-if="log.error_message" class="log-error">
                <strong>Error:</strong> {{ log.error_message }}
              </div>
              <div class="log-meta">
                <span v-if="log.ip_address">IP: {{ log.ip_address }}</span>
                <span v-if="log.user_agent">Agent: {{ log.user_agent }}</span>
              </div>
            </div>
          </div>

          <div v-if="logs.length > 0 && hasMoreLogs" class="logs-pagination">
            <button @click="loadMoreLogs" :disabled="loadingLogs" class="btn-secondary">
              {{ loadingLogs ? 'Loading...' : 'Load More' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'IntegrationsModal',
  props: {
    show: Boolean,
    channelId: Number,
  },
  data() {
    return {
      activeTab: 'list',
      loading: false,
      integrations: [],
      channels: [],
      newIntegration: {
        group_id: '',
        name: '',
        description: '',
        icon_url: '',
        generate_webhook_secret: false,
      },
      creating: false,
      showApiKeyModal: false,
      createdIntegration: null,
      currentIntegration: null,
      showLogsModal: false,
      selectedIntegration: null,
      logs: [],
      loadingLogs: false,
      logFilter: {
        days: '7',
        status: '',
        page: 1,
      },
      hasMoreLogs: true,
      currentChannelInfo: null,
    };
  },
  computed: {
    canCreate() {
      return (
        this.newIntegration.name.trim().length > 0 &&
        this.newIntegration.group_id
      );
    },
    currentChannelName() {
      return (
        this.currentChannelInfo?.display_name ||
        this.currentChannelInfo?.name ||
        'Unknown Channel'
      );
    },
    currentChannelDescription() {
      return this.currentChannelInfo?.description || '';
    },
    getUsageExample() {
      const baseUrl = window.location.origin;
      const apiKey = this.currentIntegration?.api_key || this.createdIntegration?.api_key;
      return `POST ${baseUrl}/api/webhooks/chat/message
Content-Type: application/json
X-Chat-Api-Key: ${apiKey}

{
  "message": "Hello from external application!",
  "attachments": [
    {
      "type": "link",
      "url": "https://example.com",
      "title": "Example Link"
    }
  ]
}`;
    },
    getCurlExample() {
      const baseUrl = window.location.origin;
      const apiKey = this.currentIntegration?.api_key || this.createdIntegration?.api_key;
      return `curl -X POST "${baseUrl}/api/webhooks/chat/message" \\
  -H "Content-Type: application/json" \\
  -H "X-Chat-Api-Key: ${apiKey}" \\
  -d '{
    "message": "Test message from curl"
  }'`;
    },
  },
  watch: {
    show(val) {
      if (val) {
        if (this.channelId) {
          this.fetchIntegrations();
          this.fetchChannelInfo();
        }
        this.fetchChannels();
      }
    },
    activeTab(val) {
      if (val === 'create' && this.channels.length === 0) {
        this.fetchChannels();
      }
    },
  },
  methods: {
    // ── Channel & Integration Data ──────────────────────────────────────────────
    async fetchChannelInfo() {
      if (!this.channelId) return;
      try {
        const response = await axios.get(`/api/chat/groups/${this.channelId}/details`);
        if (response.data.success) {
          this.currentChannelInfo = response.data.group;
        }
      } catch (error) {
        console.error('Failed to fetch channel info:', error);
      }
    },

    async fetchChannels() {
      try {
        const response = await axios.get('/api/chat/groups');
        if (response.data.success) {
          this.channels = [
            ...(response.data.channels || []).map((ch) => ({ ...ch, type: 'channel' })),
            ...(response.data.groups || []).map((gr) => ({ ...gr, type: 'group' })),
          ];
          this.channels = this.channels.filter((ch) => ch.type !== 'direct');
        }
      } catch (error) {
        console.error('Failed to fetch channels:', error);
      }
    },

    getChatName(ch) {
      return ch.name || ch.display_name || `Unnamed ${ch.type}`;
    },

    async fetchIntegrations() {
      this.loading = true;
      try {
        const response = await axios.get(`/api/chat/groups/${this.channelId}/integrations`);
        if (response.data.success) {
          this.integrations = response.data.integrations || [];
        }
      } catch (error) {
        console.error('Failed to fetch integrations:', error);
        this.showError('Failed to load integrations');
      } finally {
        this.loading = false;
      }
    },

    // ── Create Integration ──────────────────────────────────────────────────────
    async createIntegration() {
      if (!this.canCreate) return;
      this.creating = true;

      try {
        const payload = {
          name: this.newIntegration.name.trim(),
          description: this.newIntegration.description.trim() || null,
          icon_url: this.newIntegration.icon_url.trim() || null,
          generate_webhook_secret: this.newIntegration.generate_webhook_secret,
        };

        const response = await axios.post(
          `/api/chat/groups/${this.newIntegration.group_id}/integrations`,
          payload
        );

        if (response.data.success) {
          this.createdIntegration = response.data.integration;
          this.showApiKeyModal = true;
          this.resetForm();
          await this.fetchIntegrations();
          this.showSuccess('Integration created successfully!');
        } else {
          throw new Error(response.data.message || 'Failed to create integration');
        }
      } catch (error) {
        console.error('Failed to create integration:', error);
        this.showError(error.response?.data?.message || 'Failed to create integration');
      } finally {
        this.creating = false;
      }
    },

    // ── Other Actions ───────────────────────────────────────────────────────────
    async toggleIntegration(integration) {
      try {
        const response = await axios.patch(
          `/api/chat/groups/${this.channelId}/integrations/${integration.id}`,
          { is_active: !integration.is_active }
        );
        if (response.data.success) {
          integration.is_active = !integration.is_active;
          this.showSuccess(`Integration ${integration.is_active ? 'enabled' : 'disabled'}`);
        }
      } catch (error) {
        console.error('Failed to toggle integration:', error);
        this.showError('Failed to update integration');
      }
    },

    async regenerateApiKey(integration) {
      if (!confirm('Regenerate API key? Old key will stop working.')) return;
      try {
        const response = await axios.post(
          `/api/chat/groups/${this.channelId}/integrations/${integration.id}/regenerate-key`
        );
        if (response.data.success) {
          this.currentIntegration = response.data.integration;
          this.showApiKeyModal = true;
          await this.fetchIntegrations();
          this.showSuccess('API key regenerated!');
        }
      } catch (error) {
        console.error('Failed to regenerate API key:', error);
        this.showError('Failed to regenerate API key');
      }
    },

    async deleteIntegration(integration) {
      if (!confirm(`Delete "${integration.name}"? This cannot be undone.`)) return;
      try {
        const response = await axios.delete(
          `/api/chat/groups/${this.channelId}/integrations/${integration.id}`
        );
        if (response.data.success) {
          await this.fetchIntegrations();
          this.showSuccess('Integration deleted');
        }
      } catch (error) {
        console.error('Failed to delete integration:', error);
        this.showError('Failed to delete integration');
      }
    },

    // ── Logs ────────────────────────────────────────────────────────────────────
    async viewLogs(integration) {
      this.selectedIntegration = integration;
      this.showLogsModal = true;
      this.logs = [];
      this.logFilter.page = 1;
      this.hasMoreLogs = true;
      await this.loadLogs();
    },

    async loadLogs() {
      if (!this.selectedIntegration) return;
      this.loadingLogs = true;
      try {
        const params = {
          days: this.logFilter.days,
          status: this.logFilter.status,
          page: this.logFilter.page,
        };
        const response = await axios.get(
          `/api/chat/groups/${this.channelId}/integrations/${this.selectedIntegration.id}/logs`,
          { params }
        );
        if (response.data.success) {
          const newLogs = response.data.logs || [];
          if (this.logFilter.page === 1) {
            this.logs = newLogs;
          } else {
            this.logs = [...this.logs, ...newLogs];
          }
          this.hasMoreLogs = newLogs.length > 0;
        }
      } catch (error) {
        console.error('Failed to fetch logs:', error);
        this.showError('Failed to load logs');
      } finally {
        this.loadingLogs = false;
      }
    },

    async loadMoreLogs() {
      this.logFilter.page++;
      await this.loadLogs();
    },

    async refreshLogs() {
      this.logFilter.page = 1;
      await this.loadLogs();
    },

    // ── API Key Display & Utils ────────────────────────────────────────────────
    showApiKey(integration) {
      this.currentIntegration = integration;
      this.showApiKeyModal = true;
    },

    showWebhookSecret(integration) {
      this.currentIntegration = integration;
      this.showApiKeyModal = true;
    },

    copyIntegrationDetails(integration) {
      const details = {
        name: integration.name,
        channel: this.currentChannelName,
        api_key: integration.api_key,
        webhook_secret: integration.webhook_secret,
        created_at: integration.created_at,
        usage_example: this.getUsageExample,
        curl_example: this.getCurlExample,
      };
      this.copyToClipboard(JSON.stringify(details, null, 2));
      this.showSuccess('Integration details copied to clipboard');
    },

    copyToClipboard(text) {
      if (!text) return;
      navigator.clipboard.writeText(text)
        .then(() => this.showSuccess('Copied to clipboard!'))
        .catch(() => this.showError('Failed to copy to clipboard'));
    },

    closeApiKeyModal() {
      this.showApiKeyModal = false;
      this.createdIntegration = null;
      this.currentIntegration = null;
    },

    resetForm() {
      this.newIntegration = {
        group_id: this.channelId || '',
        name: '',
        description: '',
        icon_url: '',
        generate_webhook_secret: false,
      };
      this.activeTab = 'list';
    },

    generateDefaultDescription() {
      if (!this.newIntegration.description && this.newIntegration.name) {
        const name = this.newIntegration.name.toLowerCase();
        if (name.includes('github')) {
          this.newIntegration.description = 'Sends GitHub notifications to this channel';
        } else if (name.includes('ci') || name.includes('cd') || name.includes('jenkins')) {
          this.newIntegration.description = 'Sends CI/CD pipeline notifications';
        } else if (name.includes('monitor') || name.includes('alert')) {
          this.newIntegration.description = 'Sends system monitoring alerts';
        }
      }
    },

    formatDate(date) {
      if (!date) return 'Never';
      return new Date(date).toLocaleString();
    },

    formatApiKey(apiKey) {
      if (!apiKey) return 'Not available';
      return apiKey.substring(0, 8) + '...' + apiKey.substring(apiKey.length - 8);
    },

    showSuccess(message) {
      alert(message);
    },

    showError(message) {
      alert('Error: ' + message);
    },

    downloadIntegrationDetails() {
      const integration = this.currentIntegration || this.createdIntegration;
      if (!integration) return;

      const data = {
        integration: {
          name: integration.name,
          api_key: integration.api_key,
          webhook_secret: integration.webhook_secret,
          created_at: integration.created_at,
          is_active: integration.is_active,
        },
        channel: {
          name: this.currentChannelName,
          id: this.channelId,
        },
        usage: {
          endpoint: `${window.location.origin}/api/webhooks/chat/message`,
          headers: {
            'Content-Type': 'application/json',
            'X-Chat-Api-Key': integration.api_key,
          },
          example: { message: "Hello from integration!", attachments: [] },
        },
      };

      const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `integration-${integration.name.toLowerCase().replace(/\s+/g, '-')}.json`;
      a.click();
      URL.revokeObjectURL(url);
      this.showSuccess('Integration details downloaded');
    },
  },
};
</script>
<style scoped>
.integrations-modal {
  max-width: 800px;
  max-height: 90vh;
}

.tabs {
  display: flex;
  border-bottom: 1px solid #e5e7eb;
  padding: 0 24px;
}

.tab {
  padding: 12px 16px;
  border: none;
  background: none;
  cursor: pointer;
  font-weight: 500;
  color: #6b7280;
  border-bottom: 2px solid transparent;
  transition: all 0.2s;
}

.tab:hover {
  color: #111827;
}

.tab.active {
  color: #2563eb;
  border-bottom-color: #2563eb;
}

.integration-info-section {
  background: #f8fafc;
  border-radius: 8px;
  padding: 16px;
  margin-bottom: 16px;
  border: 1px solid #e5e7eb;
}

.integration-info-section h3 {
  margin: 0 0 8px 0;
  font-size: 16px;
  color: #111827;
}

.channel-description {
  margin: 0;
  color: #6b7280;
  font-size: 14px;
}

.integrations-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.integration-card {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
  transition: all 0.2s;
}

.integration-card:hover {
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.integration-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.integration-icon {
  width: 48px;
  height: 48px;
  border-radius: 8px;
  background: #f3f4f6;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.integration-icon img {
  width: 32px;
  height: 32px;
  border-radius: 4px;
}

.integration-info {
  flex: 1;
  min-width: 0;
}

.integration-info h3 {
  margin: 0 0 4px 0;
  font-size: 16px;
  font-weight: 600;
}

.integration-info p {
  margin: 0;
  font-size: 14px;
  color: #6b7280;
}

.integration-status .status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  background: #f3f4f6;
  color: #6b7280;
}

.integration-status .status-badge.active {
  background: #dcfce7;
  color: #166534;
}

.integration-meta {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
  padding: 12px;
  background: #f9fafb;
  border-radius: 6px;
}

.meta-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.meta-label {
  font-size: 12px;
  color: #6b7280;
}

.meta-value {
  font-size: 14px;
  font-weight: 500;
  word-break: break-all;
}

.meta-value.api-key,
.meta-value.secret {
  cursor: pointer;
  color: #2563eb;
  text-decoration: underline;
}

.meta-value.api-key:hover,
.meta-value.secret:hover {
  color: #1d4ed8;
}

.integration-stats {
  display: flex;
  gap: 24px;
  margin-bottom: 16px;
  padding: 12px;
  background: #f9fafb;
  border-radius: 6px;
}

.stat {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.stat-label {
  font-size: 12px;
  color: #6b7280;
}

.stat-value {
  font-size: 14px;
  font-weight: 600;
}

.integration-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

/* Create Form Styles */
.create-form {
  max-width: 600px;
  margin: 0 auto;
}

.form-section {
  margin-bottom: 24px;
  padding: 16px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #f8fafc;
}

.form-section h3 {
  margin: 0 0 16px 0;
  font-size: 16px;
  color: #111827;
}

.current-channel-info {
  margin-bottom: 16px;
}

.channel-display {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}

.channel-display svg {
  color: #6b7280;
}

.channel-name {
  font-weight: 600;
  color: #111827;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  font-size: 14px;
  color: #374151;
}

.form-input {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  transition: all 0.2s;
}

.form-input.small {
  padding: 6px 8px;
  font-size: 13px;
}

.form-input:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

textarea.form-input {
  min-height: 80px;
  resize: vertical;
}

.help-text {
  display: block;
  margin-top: 4px;
  font-size: 12px;
  color: #6b7280;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  font-size: 14px;
}

.checkbox-label input {
  cursor: pointer;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 24px;
}

.btn-primary {
  padding: 10px 20px;
  background: #2563eb;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
}

.btn-primary:hover:not(:disabled) {
  background: #1d4ed8;
}

.btn-primary:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

.btn-secondary {
  padding: 8px 16px;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-secondary:hover {
  background: #f9fafb;
  border-color: #9ca3af;
}

.btn-danger {
  padding: 8px 16px;
  background: white;
  color: #dc2626;
  border: 1px solid #dc2626;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-danger:hover {
  background: #fef2f2;
}

/* API Key Modal Styles */
.api-key-modal {
  max-width: 600px;
}

.success-message {
  text-align: center;
  margin-bottom: 24px;
  color: #059669;
}

.success-message svg {
  margin: 0 auto 12px;
  color: #059669;
}

.integration-details {
  margin-bottom: 24px;
  padding: 16px;
  background: #f8fafc;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
}

.detail-item {
  display: flex;
  margin-bottom: 8px;
}

.detail-item:last-child {
  margin-bottom: 0;
}

.detail-label {
  min-width: 100px;
  font-weight: 600;
  color: #374151;
}

.detail-value {
  flex: 1;
  color: #6b7280;
}

.api-key-section {
  margin-bottom: 24px;
}

.api-key-section h3 {
  margin: 0 0 8px 0;
  font-size: 16px;
}

.warning-text {
  color: #dc2626;
  font-weight: 500;
  margin-bottom: 12px;
}

.key-display {
  display: flex;
  gap: 8px;
  align-items: center;
  background: #1f2937;
  padding: 12px;
  border-radius: 6px;
  border: 1px solid #374151;
}

.key-display code {
  flex: 1;
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
  font-size: 12px;
  word-break: break-all;
  color: #f3f4f6;
}

.copy-btn {
  padding: 6px 12px;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  cursor: pointer;
  font-size: 12px;
  white-space: nowrap;
}

.copy-btn:hover {
  background: #f9fafb;
}

.usage-instructions {
  margin: 24px 0;
}

.usage-instructions h3 {
  margin: 0 0 12px 0;
  font-size: 16px;
}

.code-block {
  position: relative;
  background: #1f2937;
  color: #f3f4f6;
  padding: 16px;
  border-radius: 6px;
  overflow-x: auto;
  margin: 12px 0;
}

.code-block pre {
  margin: 0;
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
  font-size: 12px;
  white-space: pre-wrap;
}

.code-block .copy-btn {
  position: absolute;
  top: 8px;
  right: 8px;
}

.full-width {
  width: 100%;
  margin-top: 8px;
}

.api-actions {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 24px;
}

/* Logs Modal Styles */
.logs-modal {
  max-width: 800px;
}

.logs-filter {
  display: flex;
  gap: 12px;
  align-items: flex-end;
  margin-bottom: 16px;
  padding: 16px;
  background: #f8fafc;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
}

.filter-group {
  flex: 1;
}

.filter-group label {
  display: block;
  margin-bottom: 4px;
  font-size: 12px;
  color: #6b7280;
}

.logs-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  max-height: 400px;
  overflow-y: auto;
}

.log-entry {
  padding: 12px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
}

.log-entry.success {
  border-left: 4px solid #059669;
}

.log-entry.failed {
  border-left: 4px solid #dc2626;
}

.log-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.log-action {
  font-weight: 600;
}

.log-status {
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 12px;
  background: #f3f4f6;
}

.log-time {
  font-size: 12px;
  color: #6b7280;
}

.log-message {
  margin-bottom: 8px;
  font-size: 14px;
  color: #374151;
}

.log-error {
  color: #dc2626;
  font-size: 14px;
  margin-bottom: 8px;
  padding: 8px;
  background: #fef2f2;
  border-radius: 4px;
}

.log-meta {
  display: flex;
  gap: 12px;
  font-size: 12px;
  color: #6b7280;
}

.logs-pagination {
  text-align: center;
  margin-top: 16px;
  padding-top: 16px;
  border-top: 1px solid #e5e7eb;
}

.empty-state,
.loading-state {
  text-align: center;
  padding: 40px 20px;
  color: #6b7280;
}

.empty-state svg {
  margin: 0 auto 16px;
  color: #d1d5db;
}

.empty-state h3 {
  margin: 0 0 8px 0;
  color: #111827;
}

/* Modal overlay */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: 20px;
}

.modal {
  background: white;
  border-radius: 8px;
  width: 100%;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal-header {
  padding: 20px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.close-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  color: #6b7280;
  font-size: 24px;
  line-height: 1;
}

.close-btn:hover {
  color: #111827;
}

.modal-body {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
}
</style>