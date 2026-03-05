<template>
  <div class="chat-wrapper">
    <!-- Sidebar -->
    <div class="chat-sidebar">
      <!-- Workspace Header -->
      <div class="sidebar-header">
        <div class="sidebar-header-inner">
          <h1 class="sidebar-title">My Chats</h1>
          <button
            @click="showIntegrationsModal = true"
            class="icon-btn"
            title="Settings"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="3"></circle>
              <path d="M12 1v6m0 6v6m-9-9h6m6 0h6"></path>
            </svg>
          </button>
        </div>
      </div>

      <!-- Channels Container -->
      <div class="sidebar-body">
        <div class="browse-btn-wrap">
          <button @click="showChannelBrowser = true" class="browse-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Browse channels</span>
          </button>
        </div>

        <!-- Starred -->
        <div class="section">
          <div class="section-header">
            <div class="section-header-left">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
              </svg>
              <span>Starred</span>
            </div>
          </div>
          <div v-if="starredItems.length === 0" class="empty-state">No starred items</div>
          <div
            v-else
            v-for="item in starredItems"
            :key="item.id"
            @click="selectChat(item)"
            class="chat-item"
            :class="{ 'is-active': selectedChat?.id === item.id }"
          >
            <span class="chat-item-name" :class="{ 'is-unread': item.unread_count > 0 }">{{ getChatDisplayName(item) }}</span>
            <span v-if="item.unread_count > 0" class="unread-badge">{{ item.unread_count }}</span>
          </div>
        </div>

        <!-- Channels -->
        <div class="section">
          <div class="section-header">
            <span>Channels</span>
            <button @click="showCreateChannel = true" class="icon-btn-sm">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
            </button>
          </div>
          <div v-if="channels.length === 0" class="empty-state">No channels</div>
          <div
            v-else
            v-for="item in channels"
            :key="item.id"
            @click="selectChat(item)"
            class="chat-item"
            :class="{ 'is-active': selectedChat?.id === item.id }"
          >
            <span class="chat-item-name" :class="{ 'is-unread': item.unread_count > 0 }">{{ getChatDisplayName(item) }}</span>
            <span v-if="item.unread_count > 0" class="unread-badge">{{ item.unread_count }}</span>
          </div>
        </div>

        <!-- Direct Messages -->
        <div class="section">
          <div class="section-header">
            <span>Direct Messages</span>
            <button @click="showNewDM = true" class="icon-btn-sm">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
            </button>
          </div>
          <div v-if="directMessages.length === 0" class="empty-state">No direct messages</div>
          <div
            v-else
            v-for="item in directMessages"
            :key="item.id"
            @click="selectChat(item)"
            class="chat-item"
            :class="{ 'is-active': selectedChat?.id === item.id }"
          >
            <span class="chat-item-name" :class="{ 'is-unread': item.unread_count > 0 }">{{ getChatDisplayName(item) }}</span>
            <span v-if="item.unread_count > 0" class="unread-badge">{{ item.unread_count }}</span>
          </div>
        </div>

        <!-- Groups -->
        <div class="section">
          <div class="section-header">
            <span>Groups</span>
            <button @click="showCreateGroup = true" class="icon-btn-sm">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
            </button>
          </div>
          <div v-if="groups.length === 0" class="empty-state">No groups</div>
          <div
            v-else
            v-for="item in groups"
            :key="item.id"
            @click="selectChat(item)"
            class="chat-item"
            :class="{ 'is-active': selectedChat?.id === item.id }"
          >
            <span class="chat-item-name" :class="{ 'is-unread': item.unread_count > 0 }">{{ getChatDisplayName(item) }}</span>
            <span v-if="item.unread_count > 0" class="unread-badge">{{ item.unread_count }}</span>
          </div>
        </div>
      </div>

      <!-- User Profile -->
      <div class="sidebar-footer">
        <div class="user-profile">
          <div class="user-avatar">{{ getUserInitials }}</div>
          <div class="user-info">
            <div class="user-name">{{ getUserFullName }}</div>
            <div class="user-status">🟢 Active</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Chat Area -->
    <div class="chat-main">
      <div v-if="!selectedChat" class="chat-empty">
        <div class="chat-empty-content">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:0.3; margin-bottom: 12px;">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
          </svg>
          <h3>Select a conversation</h3>
          <p>Choose a channel or direct message to start chatting</p>
        </div>
      </div>

      <template v-else>
        <!-- Chat Header -->
        <div class="chat-header">
          <h2 class="chat-header-title">{{ getChatDisplayName(selectedChat) }}</h2>
          <button @click="toggleFavorite" class="icon-btn" :title="selectedChat.is_favorite ? 'Unstar' : 'Star'">
            <svg width="18" height="18" viewBox="0 0 24 24"
              :fill="selectedChat.is_favorite ? 'currentColor' : 'none'"
              stroke="currentColor" stroke-width="2"
              :style="{ color: selectedChat.is_favorite ? '#f59e0b' : 'inherit' }"
            >
              <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
            </svg>
          </button>
        </div>

        <!-- Messages Area -->
        <div ref="messagesContainer" class="messages-area">
          <div v-if="loadingMessages" class="messages-loading">Loading...</div>
          <div v-else-if="messages.length === 0" class="messages-empty">No messages yet. Say hello!</div>
          <div v-else class="messages-list">
            <div v-for="message in messages" :key="message.id" class="message">
              <div class="message-avatar">{{ getUserInitialsFromMessage(message) }}</div>
              <div class="message-body">
                <div class="message-meta">
                  <span class="message-author">{{ getUserNameFromMessage(message) }}</span>
                  <span class="message-time">{{ formatTime(message.created_at) }}</span>
                </div>
                <div class="message-text">{{ message.message }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Message Input -->
        <div class="message-input-area">
          <input
            v-model="newMessage"
            type="text"
            :placeholder="`Message ${getChatDisplayName(selectedChat)}`"
            class="message-input"
            @keydown.enter="sendMessage"
          />
          <button
            @click="sendMessage"
            :disabled="!canSendMessage"
            class="send-btn"
          >
            Send
          </button>
        </div>
      </template>
    </div>

    <!-- Channel Browser Modal -->
    <div v-if="showChannelBrowser" class="modal-backdrop" @click="showChannelBrowser = false">
      <div class="modal-box" @click.stop>
        <h2 class="modal-title">Browse Channels</h2>
        <input v-model="channelSearchQuery" placeholder="Search channels..." class="modal-input" />
        <div class="modal-list">
          <div v-for="channel in filteredBrowsableChannels" :key="channel.id" class="modal-list-item">
            <div>
              <div class="modal-item-name">{{ channel.name }}</div>
              <div class="modal-item-desc">{{ channel.description }}</div>
            </div>
            <button @click="joinChannel(channel)" class="join-btn">Join</button>
          </div>
        </div>
      </div>
    </div>

    <!-- New DM Modal -->
    <div v-if="showNewDM" class="modal-backdrop" @click="showNewDM = false">
      <div class="modal-box" @click.stop>
        <div class="modal-header-row">
          <h2 class="modal-title">New Direct Message</h2>
          <button @click="showNewDM = false" class="icon-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        <div class="modal-search-wrap">
          <input
            v-model="userSearchQuery"
            ref="userSearchInput"
            type="text"
            placeholder="Search by name or email..."
            class="modal-input"
          />
          <div v-if="filteredAvailableUsers.length > 0" class="modal-count">
            {{ filteredAvailableUsers.length }} user{{ filteredAvailableUsers.length !== 1 ? 's' : '' }} found
          </div>
        </div>
        <div class="modal-list">
          <div v-if="filteredAvailableUsers.length === 0" class="modal-empty">
            <p>No users found</p>
          </div>
          <div
            v-else
            v-for="user in filteredAvailableUsers"
            :key="user.id"
            @click="startDM(user)"
            class="dm-user-item"
          >
            <div class="dm-user-avatar">{{ getUserInitialsFromUser(user) }}</div>
            <div class="dm-user-info">
              <div class="dm-user-name">{{ getUserDisplayName(user) }}</div>
              <div class="dm-user-email">{{ user.email }}</div>
            </div>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="opacity:0.4">
              <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { useAuthStore } from '@/stores/auth';

export default {
  name: 'SlackChat',
  setup() {
    const authStore = useAuthStore();
    return { authStore };
  },
  data() {
    return {
      userProfile: { first_name: '', last_name: '', email: '', position: '' },
      showIntegrationsModal: false,
      channels: [],
      directMessages: [],
      groups: [],
      selectedChat: null,
      messages: [],
      newMessage: '',
      selectedFile: null,
      replyingTo: null,
      loadingMessages: false,
      showChannelBrowser: false,
      browsableChannels: [],
      channelSearchQuery: '',
      refreshInterval: null,
      availableUsers: [],
      showCreateChannel: false,
      newChannelName: '',
      newChannelDesc: '',
      showCreateGroup: false,
      newGroupName: '',
      selectedMembers: [],
      userSearchQuery: '',
      showNewDM: false,
      showMembersModal: false,
      currentMembers: [],
      memberSearchQuery: '',
      selectedNewMembers: [],
      userIdToNameMap: new Map(),
    };
  },
  computed: {
    starredItems() {
      return [...this.channels, ...this.directMessages, ...this.groups].filter(item => item.is_favorite);
    },
    canSendMessage() {
      return this.newMessage.trim() || this.selectedFile;
    },
    filteredBrowsableChannels() {
      if (!this.channelSearchQuery) return this.browsableChannels;
      const q = this.channelSearchQuery.toLowerCase();
      return this.browsableChannels.filter(ch => ch.name.toLowerCase().includes(q));
    },
    filteredAvailableUsers() {
      if (!this.userSearchQuery) return this.availableUsers;
      const q = this.userSearchQuery.toLowerCase();
      return this.availableUsers.filter(u => {
        const displayName = this.getUserDisplayName(u).toLowerCase();
        const email = (u.email || '').toLowerCase();
        return displayName.includes(q) || email.includes(q);
      });
    },
    getUserFullName() {
      if (this.userProfile.first_name && this.userProfile.last_name) {
        return `${this.userProfile.first_name} ${this.userProfile.last_name}`;
      }
      return this.authStore.user
        ? `${this.authStore.user.first_name || ''} ${this.authStore.user.last_name || ''}`.trim()
        : 'User';
    },
    getUserInitials() {
      const name = this.getUserFullName;
      const parts = name.split(' ');
      return parts.length >= 2
        ? (parts[0][0] + parts[1][0]).toUpperCase()
        : name[0]?.toUpperCase() || 'U';
    },
  },
  mounted() {
    this.initializeApp();
  },
  beforeUnmount() {
    if (this.refreshInterval) clearInterval(this.refreshInterval);
  },
  watch: {
    showChannelBrowser(val) { if (val) this.fetchBrowsableChannels(); },
    showNewDM(val) {
      if (val) {
        this.fetchAvailableUsers();
        this.userSearchQuery = '';
        this.$nextTick(() => { this.$refs.userSearchInput?.focus(); });
      }
    },
  },
  methods: {
    async initializeApp() {
      await this.fetchUserProfile();
      await this.fetchAvailableUsers();
      await this.fetchChats();
      this.startAutoRefresh();
    },
    getUserDisplayName(user) {
      if (!user) return 'Unknown';
      if (user.name?.trim()) return user.name;
      if (user.full_name?.trim()) return user.full_name;
      if (user.username?.trim()) return user.username;
      const fullName = `${user.first_name || ''} ${user.last_name || ''}`.trim();
      if (fullName) return fullName;
      if (user.email) return user.email;
      return `User ${user.id}`;
    },
    getUserInitialsFromUser(user) {
      const name = this.getUserDisplayName(user);
      const parts = name.split(' ');
      return parts.length >= 2
        ? (parts[0][0] + parts[1][0]).toUpperCase()
        : name.substring(0, 2).toUpperCase();
    },
    getChatDisplayName(chat) {
      if (!chat) return '';
      if (chat.type === 'direct') {
        if (chat.members?.length > 0) {
          const others = chat.members.filter(m => m.id !== this.authStore.user?.id);
          if (others.length > 0) return others.map(m => this.getUserDisplayName(m)).join(', ');
        }
        if (chat.display_name && chat.display_name !== 'Direct message') return chat.display_name;
        return 'Direct Message';
      }
      return chat.display_name || chat.name || `Chat ${chat.id}`;
    },
    getUserNameFromMessage(message) {
      if (!message?.user) return 'Unknown';
      if (message.user.id && !message.user.name) {
        const user = this.availableUsers.find(u => u.id === message.user.id);
        if (user) return this.getUserDisplayName(user);
      }
      return this.getUserDisplayName(message.user);
    },
    getUserInitialsFromMessage(message) {
      const name = this.getUserNameFromMessage(message);
      const parts = name.split(' ');
      return parts.length >= 2
        ? (parts[0][0] + parts[1][0]).toUpperCase()
        : name[0]?.toUpperCase() || 'U';
    },
    buildUserIdToNameMap() {
      this.userIdToNameMap.clear();
      this.availableUsers.forEach(user => {
        if (user.id) this.userIdToNameMap.set(user.id, this.getUserDisplayName(user));
      });
    },
    async fetchUserProfile() {
      try {
        const res = await axios.get('/api/profile');
        const user = res.data.user || res.data;
        this.userProfile = {
          first_name: user.first_name || '',
          last_name: user.last_name || '',
          email: user.email || '',
          position: '',
        };
      } catch (err) {
        console.error('Failed to fetch profile:', err);
      }
    },
    formatTime(timestamp) {
      return new Date(timestamp).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    },
    async fetchChats() {
      try {
        const res = await axios.get('/api/chat/groups');
        if (res.data.success) {
          this.channels = (res.data.channels || []).map(ch => ({ ...ch, type: 'channel' }));
          this.directMessages = (res.data.direct_messages || []).map(dm => ({ ...dm, type: 'direct' }));
          this.groups = (res.data.groups || []).map(gr => ({ ...gr, type: 'group' }));
          await this.enhanceDirectMessageNames();
        }
      } catch (err) {
        console.error('Failed to fetch chats:', err);
      }
    },
    async enhanceDirectMessageNames() {
      if (this.availableUsers.length === 0) await this.fetchAvailableUsers();
      const currentUserId = this.authStore.user?.id;
      for (const dm of this.directMessages) {
        if (dm.type === 'direct') {
          if (!dm.members?.length) {
            try {
              const res = await axios.get(`/api/chat/groups/${dm.id}/members`);
              dm.members = res.data.members || res.data.data || [];
            } catch (err) {
              console.error(`Failed to fetch DM members:`, err);
            }
          }
          if (dm.members?.length) {
            const others = dm.members.filter(m => m.id !== currentUserId);
            if (others.length) {
              const names = others.map(m => this.getUserDisplayName(m)).filter(n => n && n !== 'Unknown');
              if (names.length) dm.display_name = names.join(', ');
            }
          }
        }
      }
    },
    async selectChat(chat) {
      this.selectedChat = chat;
      await this.fetchMessages(false);
      if (chat.unread_count > 0) {
        await this.markAsRead(chat.id);
        chat.unread_count = 0;
      }
    },
    async fetchMessages(silent = true) {
      if (!this.selectedChat) return;
      if (!silent) this.loadingMessages = true;
      try {
        const res = await axios.get(`/api/chat/groups/${this.selectedChat.id}/messages`);
        if (res.data.success) {
          this.messages = res.data.messages || [];
          this.$nextTick(() => this.scrollToBottom());
        }
      } catch (err) {
        console.error('Failed to fetch messages:', err);
      } finally {
        if (!silent) this.loadingMessages = false;
      }
    },
    async sendMessage() {
      if (!this.canSendMessage || !this.selectedChat) return;
      const formData = new FormData();
      if (this.newMessage.trim()) formData.append('message', this.newMessage.trim());
      if (this.selectedFile) formData.append('attachment', this.selectedFile);
      try {
        const res = await axios.post(`/api/chat/groups/${this.selectedChat.id}/messages`, formData);
        if (res.data.success) {
          this.messages.push(res.data.message);
          this.newMessage = '';
          this.selectedFile = null;
          this.$nextTick(() => this.scrollToBottom());
        }
      } catch (err) {
        console.error('Failed to send message:', err);
      }
    },
    async toggleFavorite() {
      if (!this.selectedChat) return;
      try {
        const res = await axios.post(`/api/chat/groups/${this.selectedChat.id}/toggle-favorite`);
        if (res.data.success) {
          this.selectedChat.is_favorite = res.data.is_favorite;
          await this.fetchChats();
        }
      } catch (err) {
        console.error('Failed to toggle favorite:', err);
      }
    },
    async markAsRead(chatId) {
      try {
        await axios.post(`/api/chat/groups/${chatId}/read`);
      } catch (err) {
        console.error('Failed to mark as read:', err);
      }
    },
    async fetchBrowsableChannels() {
      try {
        const res = await axios.get('/api/chat/channels/browse');
        if (res.data.success) this.browsableChannels = res.data.channels || [];
      } catch (err) {
        console.error('Failed to fetch browsable channels:', err);
      }
    },
    async joinChannel(channel) {
      try {
        const res = await axios.post(`/api/chat/channels/${channel.id}/join`);
        if (res.data.success) {
          this.showChannelBrowser = false;
          await this.fetchChats();
        }
      } catch (err) {
        console.error('Failed to join channel:', err);
      }
    },
    scrollToBottom() {
      const container = this.$refs.messagesContainer;
      if (container) container.scrollTop = container.scrollHeight;
    },
    startAutoRefresh() {
      this.refreshInterval = setInterval(async () => {
        if (this.selectedChat) await this.fetchMessages(true);
        await this.fetchChats();
      }, 5000);
    },
    async fetchAvailableUsers() {
      try {
        const res = await axios.get('/api/chat/available-users');
        this.availableUsers = (res.data.users || res.data.data || []).map(user => ({
          ...user,
          name: this.getUserDisplayName(user),
        }));
        this.buildUserIdToNameMap();
        this.enhanceDirectMessageNames();
      } catch (err) {
        console.error('Failed to fetch users:', err);
        this.availableUsers = [];
      }
    },
    async startDM(user) {
      try {
        const res = await axios.post('/api/chat/direct-message', { user_id: user.id });
        if (res.data.success) {
          this.showNewDM = false;
          await this.fetchChats();
          const newDM = res.data.group || this.directMessages.find(dm =>
            dm.members?.some(m => m.id === user.id)
          );
          if (newDM) {
            if (!newDM.display_name || newDM.display_name === 'Direct message') {
              newDM.display_name = this.getUserDisplayName(user);
            }
            this.selectChat(newDM);
          }
        }
      } catch (err) {
        console.error('Failed to start DM:', err);
      }
    },
  },
};
</script>

<style scoped>
/* ── Root: fills 100% of whatever container holds it ── */
.chat-wrapper {
  display: flex;
  width: 100%;
  height: 100%;
  min-height: 0;
  overflow: hidden;
  background: #fff;
}

/* ── Sidebar ── */
.chat-sidebar {
  width: 240px;
  min-width: 240px;
  background: #3F0E40;
  color: #fff;
  display: flex;
  flex-direction: column;
  height: 100%;
  overflow: hidden;
}

.sidebar-header {
  padding: 14px 12px;
  border-bottom: 1px solid rgba(255,255,255,0.1);
  flex-shrink: 0;
}

.sidebar-header-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.sidebar-title {
  font-size: 15px;
  font-weight: 700;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.sidebar-body {
  flex: 1;
  overflow-y: auto;
  padding: 8px 0;
}

.sidebar-body::-webkit-scrollbar { width: 4px; }
.sidebar-body::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 2px; }

.sidebar-footer {
  border-top: 1px solid rgba(255,255,255,0.1);
  padding: 10px 12px;
  flex-shrink: 0;
}

.browse-btn-wrap {
  padding: 4px 8px 8px;
}

.browse-btn {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 10px;
  font-size: 13px;
  color: rgba(255,255,255,0.8);
  background: transparent;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  text-align: left;
  transition: background 0.15s;
}
.browse-btn:hover { background: rgba(255,255,255,0.1); }

/* Section */
.section { margin-bottom: 8px; }

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 4px 12px 4px;
  font-size: 12px;
  font-weight: 700;
  color: rgba(255,255,255,0.7);
  letter-spacing: 0.03em;
}
.section-header-left {
  display: flex;
  align-items: center;
  gap: 5px;
}

.empty-state {
  padding: 4px 16px 6px;
  font-size: 12px;
  color: rgba(255,255,255,0.4);
  font-style: italic;
}

.chat-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 5px 16px;
  cursor: pointer;
  transition: background 0.15s;
  border-right: 2px solid transparent;
}
.chat-item:hover { background: rgba(255,255,255,0.08); }
.chat-item.is-active {
  background: #1164A3;
  border-right-color: #7bc4f0;
}

.chat-item-name {
  font-size: 13px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  color: rgba(255,255,255,0.85);
}
.chat-item-name.is-unread {
  font-weight: 700;
  color: #fff;
}

.unread-badge {
  margin-left: 6px;
  flex-shrink: 0;
  background: #e01e5a;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  padding: 1px 6px;
  border-radius: 10px;
  line-height: 1.4;
}

/* User profile */
.user-profile {
  display: flex;
  align-items: center;
  gap: 8px;
}
.user-avatar {
  width: 32px;
  height: 32px;
  border-radius: 6px;
  background: linear-gradient(135deg, #4ade80, #3b82f6);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 700;
  flex-shrink: 0;
}
.user-info { min-width: 0; }
.user-name {
  font-size: 13px;
  font-weight: 600;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.user-status { font-size: 11px; color: rgba(255,255,255,0.55); }

/* ── Icon buttons ── */
.icon-btn {
  background: transparent;
  border: none;
  cursor: pointer;
  color: rgba(255,255,255,0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 4px;
  border-radius: 4px;
  transition: background 0.15s, color 0.15s;
}
.icon-btn:hover { background: rgba(255,255,255,0.1); color: #fff; }

.icon-btn-sm {
  background: transparent;
  border: none;
  cursor: pointer;
  color: rgba(255,255,255,0.55);
  display: flex;
  align-items: center;
  padding: 2px;
  border-radius: 3px;
  transition: background 0.15s;
}
.icon-btn-sm:hover { background: rgba(255,255,255,0.1); color: #fff; }

/* ── Main chat area ── */
.chat-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  height: 100%;
  min-width: 0;
  overflow: hidden;
  background: #fff;
}

.chat-empty {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #9ca3af;
}
.chat-empty-content {
  text-align: center;
}
.chat-empty-content h3 {
  font-size: 18px;
  font-weight: 600;
  margin: 0 0 6px;
  color: #374151;
}
.chat-empty-content p { margin: 0; font-size: 14px; }

.chat-header {
  height: 52px;
  border-bottom: 1px solid #e5e7eb;
  padding: 0 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-shrink: 0;
}
.chat-header-title {
  font-size: 15px;
  font-weight: 700;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.chat-header .icon-btn { color: #6b7280; }
.chat-header .icon-btn:hover { background: #f3f4f6; color: #374151; }

/* Messages */
.messages-area {
  flex: 1;
  overflow-y: auto;
  padding: 16px;
  min-height: 0;
}
.messages-area::-webkit-scrollbar { width: 6px; }
.messages-area::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }

.messages-loading,
.messages-empty {
  text-align: center;
  color: #9ca3af;
  font-size: 14px;
  margin-top: 40px;
}

.messages-list { display: flex; flex-direction: column; gap: 16px; }

.message { display: flex; gap: 10px; }

.message-avatar {
  width: 36px;
  height: 36px;
  background: #3b82f6;
  border-radius: 6px;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  font-weight: 700;
  flex-shrink: 0;
}

.message-body { flex: 1; min-width: 0; }

.message-meta {
  display: flex;
  align-items: baseline;
  gap: 8px;
  margin-bottom: 3px;
}
.message-author { font-size: 14px; font-weight: 600; color: #111827; }
.message-time { font-size: 11px; color: #9ca3af; }
.message-text { font-size: 14px; color: #374151; line-height: 1.5; word-break: break-word; }

/* Input */
.message-input-area {
  padding: 12px 16px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  gap: 8px;
  flex-shrink: 0;
}
.message-input {
  flex: 1;
  padding: 8px 14px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.message-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59,130,246,0.15);
}
.send-btn {
  padding: 8px 18px;
  background: #16a34a;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.15s;
  white-space: nowrap;
}
.send-btn:hover { background: #15803d; }
.send-btn:disabled { background: #d1d5db; cursor: not-allowed; }

/* ── Modals ── */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}
.modal-box {
  background: #fff;
  border-radius: 12px;
  padding: 24px;
  width: 100%;
  max-width: 560px;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  gap: 14px;
  box-shadow: 0 20px 60px rgba(0,0,0,0.25);
}
.modal-header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.modal-header-row .icon-btn { color: #6b7280; }
.modal-header-row .icon-btn:hover { background: #f3f4f6; color: #111; }
.modal-title { font-size: 20px; font-weight: 700; margin: 0; color: #111827; }
.modal-input {
  width: 100%;
  padding: 9px 14px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  outline: none;
  box-sizing: border-box;
}
.modal-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.15); }
.modal-count { font-size: 12px; color: #9ca3af; margin-top: 4px; }
.modal-search-wrap { display: flex; flex-direction: column; }
.modal-list { overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 8px; }
.modal-list-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}
.modal-item-name { font-weight: 600; font-size: 14px; }
.modal-item-desc { font-size: 12px; color: #6b7280; margin-top: 2px; }
.modal-empty { text-align: center; color: #9ca3af; padding: 32px 0; }
.join-btn {
  padding: 6px 16px;
  background: #2563eb;
  color: #fff;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
}
.join-btn:hover { background: #1d4ed8; }

/* DM user items */
.dm-user-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.15s;
  border: 1px solid transparent;
}
.dm-user-item:hover { background: #eff6ff; border-color: #bfdbfe; }
.dm-user-avatar {
  width: 38px;
  height: 38px;
  background: linear-gradient(135deg, #3b82f6, #8b5cf6);
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 700;
  color: #fff;
  flex-shrink: 0;
}
.dm-user-info { flex: 1; min-width: 0; }
.dm-user-name { font-size: 14px; font-weight: 600; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dm-user-email { font-size: 12px; color: #6b7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>