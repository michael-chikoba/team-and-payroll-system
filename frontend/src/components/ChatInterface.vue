<template>
  <div class="flex h-screen bg-white">
    <!-- Sidebar -->
    <div class="w-64 bg-purple-900 text-white flex flex-col">
      <!-- Workspace Header -->
      <div class="p-4 border-b border-purple-800">
        <div class="flex items-center justify-between">
          <h1 class="text-lg font-bold truncate">My Chats</h1>
          <button 
            @click="showIntegrationsModal = true" 
            class="p-1 hover:bg-purple-800 rounded" 
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
      <div class="flex-1 overflow-y-auto">
        <div class="p-2">
          <button @click="showChannelBrowser = true" class="w-full flex items-center gap-2 px-3 py-2 text-sm hover:bg-purple-800 rounded">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="5" x2="12" y2="19"></line>
              <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            <span>Browse channels</span>
          </button>
        </div>
        
        <!-- Starred -->
        <div class="mb-4">
          <div class="flex items-center justify-between px-4 py-2 text-sm font-semibold text-purple-100">
            <div class="flex items-center gap-2">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
              </svg>
              <span>Starred</span>
            </div>
          </div>
          <div v-if="starredItems.length === 0" class="px-4 py-2 text-sm text-purple-300 italic">No starred items</div>
          <div v-else v-for="item in starredItems" :key="item.id" @click="selectChat(item)" class="flex items-center justify-between px-4 py-1.5 hover:bg-purple-800 cursor-pointer" :class="{'bg-blue-600 border-r-2 border-blue-300' : selectedChat?.id === item.id}">
            <div class="flex items-center gap-2 flex-1 min-w-0">
              <span class="text-sm truncate" :class="{'font-bold': item.unread_count > 0}">{{ getChatDisplayName(item) }}</span>
            </div>
            <span v-if="item.unread_count > 0" class="ml-2 bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full">{{ item.unread_count }}</span>
          </div>
        </div>
        
        <!-- Channels -->
        <div class="mb-4">
          <div class="flex items-center justify-between px-4 py-2 text-sm font-semibold text-purple-100">
            <div class="flex items-center gap-2">
              <span>Channels</span>
            </div>
            <button @click="showCreateChannel = true" class="p-1 hover:bg-purple-800 rounded">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
            </button>
          </div>
          <div v-if="channels.length === 0" class="px-4 py-2 text-sm text-purple-300 italic">No channels</div>
          <div v-else v-for="item in channels" :key="item.id" @click="selectChat(item)" class="flex items-center justify-between px-4 py-1.5 hover:bg-purple-800 cursor-pointer" :class="{'bg-blue-600 border-r-2 border-blue-300' : selectedChat?.id === item.id}">
            <span class="text-sm truncate" :class="{'font-bold': item.unread_count > 0}">{{ getChatDisplayName(item) }}</span>
            <span v-if="item.unread_count > 0" class="ml-2 bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full">{{ item.unread_count }}</span>
          </div>
        </div>
        
        <!-- Direct Messages -->
        <div class="mb-4">
          <div class="flex items-center justify-between px-4 py-2 text-sm font-semibold text-purple-100">
            <div class="flex items-center gap-2">
              <span>Direct Messages</span>
            </div>
            <button @click="showNewDM = true" class="p-1 hover:bg-purple-800 rounded">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
            </button>
          </div>
          <div v-if="directMessages.length === 0" class="px-4 py-2 text-sm text-purple-300 italic">No direct messages</div>
          <div v-else v-for="item in directMessages" :key="item.id" @click="selectChat(item)" class="flex items-center justify-between px-4 py-1.5 hover:bg-purple-800 cursor-pointer" :class="{'bg-blue-600 border-r-2 border-blue-300' : selectedChat?.id === item.id}">
            <span class="text-sm truncate" :class="{'font-bold': item.unread_count > 0}">{{ getChatDisplayName(item) }}</span>
            <span v-if="item.unread_count > 0" class="ml-2 bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full">{{ item.unread_count }}</span>
          </div>
        </div>
        
        <!-- Groups -->
        <div class="mb-4">
          <div class="flex items-center justify-between px-4 py-2 text-sm font-semibold text-purple-100">
            <span>Groups</span>
            <button @click="showCreateGroup = true" class="p-1 hover:bg-purple-800 rounded">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
              </svg>
            </button>
          </div>
          <div v-if="groups.length === 0" class="px-4 py-2 text-sm text-purple-300 italic">No groups</div>
          <div v-else v-for="item in groups" :key="item.id" @click="selectChat(item)" class="flex items-center justify-between px-4 py-1.5 hover:bg-purple-800 cursor-pointer" :class="{'bg-blue-600 border-r-2 border-blue-300' : selectedChat?.id === item.id}">
            <span class="text-sm truncate" :class="{'font-bold': item.unread_count > 0}">{{ getChatDisplayName(item) }}</span>
            <span v-if="item.unread_count > 0" class="ml-2 bg-red-500 text-white text-xs px-1.5 py-0.5 rounded-full">{{ item.unread_count }}</span>
          </div>
        </div>
      </div>
      
      <!-- User Profile -->
      <div class="p-4 border-t border-purple-800">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 bg-gradient-to-br from-green-400 to-blue-500 rounded flex items-center justify-center font-semibold text-sm">
            {{ getUserInitials }}
          </div>
          <div class="flex-1 min-w-0">
            <div class="text-sm font-semibold truncate">{{ getUserFullName }}</div>
            <div class="text-xs text-purple-300">🟢 Active</div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Main Chat Area -->
    <div class="flex-1 flex flex-col">
      <div v-if="!selectedChat" class="flex-1 flex items-center justify-center text-gray-500">
        <div class="text-center">
          <h3 class="text-xl font-semibold mb-2">Select a conversation</h3>
          <p>Choose a channel or direct message to start chatting</p>
        </div>
      </div>
      <template v-else>
        <!-- Chat Header -->
        <div class="h-14 border-b border-gray-200 px-6 flex items-center justify-between">
          <h2 class="text-lg font-bold">{{ getChatDisplayName(selectedChat) }}</h2>
          <button @click="toggleFavorite" class="p-2 hover:bg-gray-100 rounded">
            <svg width="18" height="18" viewBox="0 0 24 24" :fill="selectedChat.is_favorite ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" :class="selectedChat.is_favorite ? 'text-yellow-500' : 'text-gray-600'">
              <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
            </svg>
          </button>
        </div>
        
        <!-- Messages Area -->
        <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4">
          <div v-if="loadingMessages" class="text-center text-gray-500">Loading...</div>
          <div v-else-if="messages.length === 0" class="text-center text-gray-500">No messages yet</div>
          <div v-else>
            <div v-for="message in messages" :key="message.id" class="mb-4">
              <div class="flex gap-3">
                <div class="w-9 h-9 bg-blue-500 rounded text-white flex items-center justify-center font-semibold">
                  {{ getUserInitialsFromMessage(message) }}
                </div>
                <div class="flex-1">
                  <div class="flex items-baseline gap-2 mb-1">
                    <span class="font-semibold">{{ getUserNameFromMessage(message) }}</span>
                    <span class="text-xs text-gray-500">{{ formatTime(message.created_at) }}</span>
                  </div>
                  <div class="text-sm">{{ message.message }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Message Input -->
        <div class="border-t p-4">
          <div class="flex gap-2">
            <input 
              v-model="newMessage" 
              type="text" 
              :placeholder="`Message ${getChatDisplayName(selectedChat)}`"
              class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              @keydown.enter="sendMessage"
            />
            <button 
              @click="sendMessage" 
              :disabled="!canSendMessage"
              class="px-4 py-2 bg-green-600 text-white rounded-lg disabled:bg-gray-300"
            >
              Send
            </button>
          </div>
        </div>
      </template>
    </div>
    
    <!-- Modals (simplified) -->
    <div v-if="showChannelBrowser" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="showChannelBrowser = false">
      <div class="bg-white rounded-lg p-6 max-w-2xl w-full" @click.stop>
        <h2 class="text-2xl font-bold mb-4">Browse Channels</h2>
        <input v-model="channelSearchQuery" placeholder="Search..." class="w-full p-2 border rounded mb-4" />
        <div class="space-y-2">
          <div v-for="channel in filteredBrowsableChannels" :key="channel.id" class="flex justify-between items-center p-4 border rounded">
            <div>
              <div class="font-semibold">{{ channel.name }}</div>
              <div class="text-sm text-gray-500">{{ channel.description }}</div>
            </div>
            <button @click="joinChannel(channel)" class="px-4 py-2 bg-blue-600 text-white rounded">Join</button>
          </div>
        </div>
      </div>
    </div>
    
    <div v-if="showNewDM" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="showNewDM = false">
      <div class="bg-white rounded-lg p-6 max-w-2xl w-full" @click.stop>
        <h2 class="text-2xl font-bold mb-4">New Direct Message</h2>
        <input v-model="userSearchQuery" placeholder="Search users..." class="w-full p-2 border rounded mb-4" />
        <div class="space-y-2">
          <div v-for="user in filteredAvailableUsers" :key="user.id" @click="startDM(user)" class="p-4 border rounded hover:bg-gray-50 cursor-pointer">
            <div class="font-semibold">{{ getUserDisplayName(user) }}</div>
            <div class="text-sm text-gray-500">{{ user.email }}</div>
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
      return this.availableUsers.filter(u => this.getUserDisplayName(u).toLowerCase().includes(q));
    },
    filteredNonMembers() {
      return this.availableUsers.filter(u => !this.currentMembers.some(m => m.id === u.id));
    },
    getUserFullName() {
      if (this.userProfile.first_name && this.userProfile.last_name) {
        return `${this.userProfile.first_name} ${this.userProfile.last_name}`;
      }
      return this.authStore.user ? `${this.authStore.user.first_name || ''} ${this.authStore.user.last_name || ''}`.trim() : 'User';
    },
    getUserInitials() {
      const name = this.getUserFullName;
      const parts = name.split(' ');
      return parts.length >= 2 ? (parts[0][0] + parts[1][0]).toUpperCase() : name[0]?.toUpperCase() || 'U';
    }
  },
  mounted() {
    this.initializeApp();
  },
  beforeUnmount() {
    if (this.refreshInterval) clearInterval(this.refreshInterval);
  },
  watch: {
    showChannelBrowser(val) { if (val) this.fetchBrowsableChannels(); },
    showNewDM(val) { if (val) this.fetchAvailableUsers(); }
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
    
    extractUserIdsFromChat(chat) {
      const ids = [];
      if (chat.members?.length) chat.members.forEach(m => m.id && ids.push(m.id));
      return ids;
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
      return parts.length >= 2 ? (parts[0][0] + parts[1][0]).toUpperCase() : name[0]?.toUpperCase() || 'U';
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
          position: ''
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
          this.channels = (res.data.channels || []).map(ch => ({...ch, type: 'channel'}));
          this.directMessages = (res.data.direct_messages || []).map(dm => ({...dm, type: 'direct'}));
          this.groups = (res.data.groups || []).map(gr => ({...gr, type: 'group'}));
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
          name: this.getUserDisplayName(user)
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
  }
};
</script>

<style>
/* CSS Reset & Variables */
:root {
  --slack-aubergine: #3F0E40;
  --slack-aubergine-dark: #350d36;
  --slack-blue: #1164A3;
  --slack-green: #2BAC76;
  --slack-text-light: #bcb9be;
  --text-main: #1d1c1d;
  --text-secondary: #616061;
  --border-light: #e6e6e6;
  --border-dark: #d1d5db;
  --bg-hover: #f8f8f8;
  --message-highlight: #f2c74466;
  --focus-ring: 0 0 0 4px rgba(29, 155, 209, 0.3);
}
* {
  box-sizing: border-box;
}
body {
  margin: 0;
  font-family: 'Lato', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
}
/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(0,0,0,0.1);
  border-radius: 4px;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
  background: rgba(0,0,0,0.3);
}
.slack-chat {
  display: flex;
  height: 100vh;
  width: 100vw;
  overflow: hidden;
  background: white;
}
/* ========================================= */
/* SIDEBAR STYLES */
/* ========================================= */
.sidebar {
  width: 260px;
  background: var(--slack-aubergine);
  color: #cfc3cf;
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  border-right: 1px solid rgba(255,255,255,0.1);
  transition: width 0.2s;
}
.workspace-header {
  height: 49px;
  padding: 0 16px;
  display: flex;
  align-items: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.workspace-name {
  font-size: 18px;
  font-weight: 900;
  color: white;
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.workspace-info {
  display: flex;
  width: 100%;
  justify-content: space-between;
  align-items: center;
}
.settings-btn {
  background: white;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--slack-aubergine);
  border: none;
  cursor: pointer;
  opacity: 0.8;
}
.sidebar-actions {
  padding: 8px 16px;
}
.browse-channels-btn {
  background: rgba(0,0,0,0.2);
  border: none;
  color: #cfc3cf;
  width: 100%;
  text-align: left;
  padding: 6px 8px;
  border-radius: 4px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}
.browse-channels-btn:hover {
  background: rgba(255,255,255,0.1);
  color: white;
}
.channels-container {
  flex: 1;
  overflow-y: auto;
  padding-bottom: 20px;
}
/* Chat Section Component Styles */
.chat-section {
  margin-top: 16px;
}
.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 4px 16px;
  color: #cfc3cf;
  cursor: pointer;
}
.section-title {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  opacity: 0.8;
}
.section-title:hover {
  opacity: 1;
}
.dropdown-arrow {
  font-size: 8px;
}
.add-btn {
  background: none;
  border: none;
  color: #cfc3cf;
  font-size: 18px;
  cursor: pointer;
  padding: 0 4px;
}
.add-btn:hover {
  background: rgba(255,255,255,0.1);
  border-radius: 4px;
}
.chat-list-item {
  padding: 4px 16px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 28px;
  margin: 1px 0;
}
.chat-list-item:hover {
  background: var(--slack-aubergine-dark);
}
.chat-list-item.active {
  background: var(--slack-blue);
  color: white;
}
.chat-list-item.active .item-icon {
  opacity: 1;
}
.chat-list-item.unread .item-name {
  font-weight: 700;
  color: white;
}
.item-content {
  display: flex;
  align-items: center;
  gap: 8px;
  overflow: hidden;
}
.item-icon {
  opacity: 0.6;
  font-size: 14px;
  min-width: 14px;
}
.item-icon.dot {
  font-size: 10px;
  color: var(--slack-green);
  opacity: 1;
}
.item-name {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-size: 15px;
}
.unread-badge {
  background: #cd2553;
  color: white;
  font-size: 11px;
  font-weight: 700;
  padding: 2px 8px;
  border-radius: 10px;
  min-width: 20px;
  text-align: center;
}
/* User Profile */
.user-profile {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  background: rgba(0,0,0,0.15);
  cursor: pointer;
}
.user-profile:hover {
  background: rgba(0,0,0,0.25);
}
.user-avatar-small {
  width: 36px;
  height: 36px;
  background: #e0e0e0;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #333;
  font-weight: bold;
  margin-right: 10px;
}
.user-details {
  overflow: hidden;
}
.user-name {
  color: white;
  font-weight: 700;
  font-size: 14px;
}
.user-status {
  font-size: 12px;
  color: #cfc3cf;
  display: flex;
  align-items: center;
  gap: 4px;
}
.status-dot {
  width: 6px;
  height: 6px;
  background: var(--slack-green);
  border-radius: 50%;
  display: inline-block;
}
/* ========================================= */
/* MAIN CHAT AREA */
/* ========================================= */
.chat-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-width: 0;
  background: white;
}
/* Header */
.chat-header {
  height: 49px;
  border-bottom: 1px solid var(--border-light);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px 0 20px;
  flex-shrink: 0;
}
.chat-header-left {
  display: flex;
  align-items: center;
  gap: 8px;
}
.chat-title {
  margin: 0;
  font-size: 18px;
  font-weight: 900;
  color: var(--text-main);
  display: flex;
  align-items: center;
}
.chat-title .hash-icon {
  color: var(--text-secondary);
  font-size: 16px;
  margin-right: 2px;
}
.favorite-toggle {
  background: none;
  border: none;
  color: #d1d5db;
  cursor: pointer;
  padding: 4px;
}
.favorite-toggle.is-active {
  color: #F2C744;
}
.favorite-toggle:hover {
  color: #F2C744;
}
.chat-header-right {
  display: flex;
  align-items: center;
}
.members-pill {
  display: flex;
  align-items: center;
  cursor: pointer;
  padding: 2px 6px;
  border-radius: 4px;
}
.members-pill:hover {
  background: var(--bg-hover);
}
.avatars-stack {
  display: flex;
  margin-right: 6px;
}
.stack-avatar {
  width: 20px;
  height: 20px;
  border-radius: 4px;
  background: #ddd;
  border: 2px solid white;
  margin-left: -6px;
}
.stack-avatar:first-child {
  margin-left: 0;
}
.members-pill .count {
  font-size: 13px;
  color: var(--text-secondary);
  font-weight: 600;
}
.header-divider {
  width: 1px;
  height: 24px;
  background: var(--border-light);
  margin: 0 12px;
}
.header-icon-btn {
  background: none;
  border: none;
  color: var(--text-secondary);
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
}
.header-icon-btn:hover {
  background: var(--bg-hover);
}
/* Messages Area */
.messages-area {
  flex: 1;
  overflow-y: auto;
  padding: 20px 0;
  display: flex;
  flex-direction: column;
}
.loading-messages {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 20px;
  color: var(--text-secondary);
}
.empty-messages {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: flex-end;
  padding: 0 20px 20px 20px;
}
.empty-channel-intro {
  margin-bottom: 20px;
}
.intro-icon {
  width: 70px;
  height: 70px;
  background: #f8f8f8;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 40px;
  color: var(--text-main);
  margin-bottom: 12px;
}
.empty-channel-intro h1 {
  margin: 0 0 8px 0;
  font-size: 28px;
}
.empty-channel-intro p {
  color: var(--text-secondary);
  font-size: 16px;
}
.highlight {
  background: rgba(29, 155, 209, 0.1);
  color: var(--slack-blue);
  padding: 0 4px;
  border-radius: 2px;
}
/* Individual Message Row */
.messages-list {
  display: flex;
  flex-direction: column;
  width: 100%;
}
.message-row {
  display: flex;
  padding: 8px 20px;
  position: relative;
  gap: 12px;
}
.message-row:hover {
  background: var(--bg-hover);
}
.message-row:hover .message-actions-hover {
  opacity: 1;
  pointer-events: auto;
}
.message-avatar {
  width: 36px;
  height: 36px;
  background-color: var(--slack-blue); /* Default user color */
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  font-size: 14px;
}
.message-content {
  flex: 1;
  min-width: 0;
}
.message-header {
  display: flex;
  align-items: baseline;
  gap: 8px;
  margin-bottom: 2px;
}
.message-user {
  font-weight: 900;
  color: var(--text-main);
  cursor: pointer;
}
.message-user:hover {
  text-decoration: underline;
}
.message-time {
  font-size: 12px;
  color: var(--text-secondary);
}
.message-text {
  color: var(--text-main);
  font-size: 15px;
  line-height: 1.46668;
  white-space: pre-wrap;
  word-break: break-word;
}
/* Reply Quote */
.message-reply-quote {
  display: flex;
  align-items: center;
  margin-bottom: 4px;
  font-size: 13px;
  gap: 6px;
}
.quote-bar {
  width: 3px;
  height: 14px;
  background: #ddd;
  border-radius: 2px;
}
.quote-user {
  font-weight: bold;
  color: var(--text-secondary);
}
.quote-text {
  color: #888;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 300px;
}
/* Reactions */
.message-reactions {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  margin-top: 4px;
}
.reaction-pill {
  display: flex;
  align-items: center;
  gap: 4px;
  background: rgba(29, 28, 29, 0.04);
  border: 1px solid transparent;
  border-radius: 12px;
  padding: 2px 6px;
  cursor: pointer;
  font-size: 12px;
}
.reaction-pill:hover {
  background: white;
  border-color: #bab8b9;
}
.reaction-pill.reacted {
  background: rgba(29, 155, 209, 0.1);
  border-color: rgba(29, 155, 209, 0.3);
}
.reaction-pill .count {
  font-weight: 600;
  color: var(--slack-blue);
}
/* Hover Actions Menu */
.message-actions-hover {
  position: absolute;
  top: -12px;
  right: 20px;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.1s;
}
.actions-group {
  background: white;
  border: 1px solid var(--border-light);
  border-radius: 8px;
  display: flex;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  padding: 2px;
}
.action-icon {
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: none;
  border: none;
  color: #555;
  cursor: pointer;
  border-radius: 4px;
}
.action-icon:hover {
  background: #f0f0f0;
}
/* Input Area */
.message-input-container {
  padding: 0 20px 20px 20px;
  flex-shrink: 0;
}
.input-box-wrapper {
  border: 1px solid #868686;
  border-radius: 8px;
  display: flex;
  flex-direction: column;
  transition: border-color 0.2s;
  background: white;
}
.input-box-wrapper:focus-within {
  box-shadow: var(--focus-ring);
  border-color: transparent;
}
/* Replying Banner */
.replying-banner {
  display: flex;
  align-items: center;
  padding: 8px 12px;
  background: #f8f8f8;
  border-top-left-radius: 7px;
  border-top-right-radius: 7px;
  border-bottom: 1px solid var(--border-light);
  gap: 8px;
}
.reply-line {
  width: 2px;
  height: 20px;
  background: var(--text-secondary);
}
.replying-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  font-size: 13px;
}
.replying-label {
  color: var(--text-secondary);
}
.replying-text {
  color: #888;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 400px;
}
.close-reply {
  background: none;
  border: none;
  cursor: pointer;
  color: #666;
}
/* File Preview */
.file-preview-banner {
  padding: 8px;
  border-bottom: 1px solid var(--border-light);
}
.file-chip {
  display: inline-flex;
  align-items: center;
  background: white;
  border: 1px solid #ddd;
  border-radius: 6px;
  padding: 4px;
  gap: 8px;
  max-width: 300px;
}
.file-icon {
  width: 32px;
  height: 32px;
  background: #eee;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #666;
}
.file-details {
  display: flex;
  flex-direction: column;
  flex: 1;
  overflow: hidden;
}
.file-name {
  font-size: 13px;
  font-weight: 600;
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
}
.file-size {
  font-size: 11px;
  color: #888;
}
.remove-file-btn {
  background: none;
  border: none;
  cursor: pointer;
  color: #888;
}
/* Main Text Input */
.input-editor {
  padding: 8px 10px;
  min-height: 44px;
}
.bare-input {
  width: 100%;
  border: none;
  outline: none;
  font-size: 15px;
  resize: none;
  font-family: inherit;
}
/* Toolbar */
.input-toolbar {
  display: flex;
  justify-content: space-between;
  padding: 4px 8px 8px 8px;
  align-items: center;
}
.toolbar-left {
  display: flex;
  gap: 2px;
  align-items: center;
}
.tool-btn {
  background: none;
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 4px;
  color: #616061;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}
.tool-btn:hover {
  background: #f2f2f2;
}
.divider {
  width: 1px;
  height: 20px;
  background: #ddd;
  margin: 0 4px;
}
.send-btn-primary {
  background: white;
  color: #ddd;
  border: none;
  width: 32px;
  height: 32px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  cursor: default;
}
.send-btn-primary.ready {
  background: #007a5a;
  color: white;
  cursor: pointer;
}
.send-btn-primary.ready:hover {
  background: #148567;
}
.typing-hint {
  font-size: 11px;
  color: #888;
  margin-top: 4px;
  text-align: right;
  padding-right: 4px;
}
/* Empty/No Chat Selected */
.no-chat-selected {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  color: var(--text-main);
}
.empty-state-content {
  text-align: center;
  max-width: 400px;
}
.empty-icon {
  background: #f8f8f8;
  width: 100px;
  height: 100px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 20px;
  color: var(--text-secondary);
}
/* Modal Styles */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}
.modal {
  background: white;
  border-radius: 8px;
  width: 600px;
  max-width: 90%;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 0 0 1px rgba(29, 28, 29, 0.13), 0 18px 48px 0 rgba(0, 0, 0, 0.35);
}
.modal-header {
  padding: 20px 24px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.modal-header h2 {
  margin: 0;
  font-size: 22px;
  font-weight: 900;
}
.close-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 8px;
  border-radius: 4px;
}
.close-btn:hover {
  background: #f2f2f2;
}
.modal-body {
  padding: 0 24px 24px;
  display: flex;
  flex-direction: column;
  gap: 16px;
  overflow: hidden;
}
.search-container {
  position: relative;
  width: 100%;
}
.search-icon {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: #666;
}
.search-input {
  width: 100%;
  padding: 10px 10px 10px 36px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 15px;
}
.search-input:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(29, 155, 209, 0.3);
  border-color: var(--slack-blue);
}
.browsable-channels {
  overflow-y: auto;
  max-height: 400px;
  border: 1px solid #eee;
  border-radius: 6px;
}
.channel-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px;
  border-bottom: 1px solid #f2f2f2;
}
.channel-row:hover {
  background: #f8f8f8;
}
.channel-row-name {
  font-weight: 700;
  font-size: 15px;
  margin-bottom: 4px;
}
.channel-row-meta {
  font-size: 13px;
  color: #616061;
}
.join-btn {
  padding: 6px 16px;
  border: 1px solid #ddd;
  background: white;
  border-radius: 4px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}
.join-btn:hover {
  background: #f8f8f8;
  border-color: #999;
}
</style>