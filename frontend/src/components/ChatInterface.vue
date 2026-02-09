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
    
    <!-- Channel Browser Modal -->
    <div v-if="showChannelBrowser" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="showChannelBrowser = false">
      <div class="bg-white rounded-lg p-6 max-w-2xl w-full max-h-[80vh] flex flex-col" @click.stop>
        <h2 class="text-2xl font-bold mb-4">Browse Channels</h2>
        <input v-model="channelSearchQuery" placeholder="Search channels..." class="w-full p-2 border rounded mb-4" />
        <div class="overflow-y-auto flex-1 space-y-2">
          <div v-for="channel in filteredBrowsableChannels" :key="channel.id" class="flex justify-between items-center p-4 border rounded hover:bg-gray-50">
            <div>
              <div class="font-semibold">{{ channel.name }}</div>
              <div class="text-sm text-gray-500">{{ channel.description }}</div>
            </div>
            <button @click="joinChannel(channel)" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Join</button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- New Direct Message Modal - IMPROVED -->
    <div v-if="showNewDM" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="showNewDM = false">
      <div class="bg-white rounded-lg w-full max-w-2xl max-h-[80vh] flex flex-col shadow-xl" @click.stop>
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900">New Direct Message</h2>
            <button @click="showNewDM = false" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
              </svg>
            </button>
          </div>
        </div>
        
        <!-- Search Input -->
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="relative">
            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="11" cy="11" r="8"></circle>
              <path d="m21 21-4.35-4.35"></path>
            </svg>
            <input 
              v-model="userSearchQuery" 
              ref="userSearchInput"
              type="text"
              placeholder="Search by name or email..." 
              class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
          </div>
          <div v-if="filteredAvailableUsers.length > 0" class="mt-2 text-sm text-gray-500">
            {{ filteredAvailableUsers.length }} user{{ filteredAvailableUsers.length !== 1 ? 's' : '' }} found
          </div>
        </div>
        
        <!-- Users List - Scrollable -->
        <div class="flex-1 overflow-y-auto px-6 py-2">
          <div v-if="filteredAvailableUsers.length === 0" class="py-12 text-center text-gray-500">
            <svg class="mx-auto mb-3 text-gray-300" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <p class="font-medium">No users found</p>
            <p class="text-sm mt-1">Try a different search term</p>
          </div>
          
          <div v-else class="space-y-1">
            <div 
              v-for="user in filteredAvailableUsers" 
              :key="user.id" 
              @click="startDM(user)" 
              class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 cursor-pointer transition-colors border border-transparent hover:border-blue-200"
            >
              <!-- User Avatar -->
              <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center font-semibold text-white text-sm flex-shrink-0">
                {{ getUserInitialsFromUser(user) }}
              </div>
              
              <!-- User Info -->
              <div class="flex-1 min-w-0">
                <div class="font-semibold text-gray-900 truncate">{{ getUserDisplayName(user) }}</div>
                <div class="text-sm text-gray-500 truncate">{{ user.email }}</div>
              </div>
              
              <!-- Arrow Icon -->
              <svg class="text-gray-400 flex-shrink-0" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="9 18 15 12 9 6"></polyline>
              </svg>
            </div>
          </div>
        </div>
        
        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
          <p class="text-sm text-gray-600">
            Select a user to start a direct message conversation
          </p>
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
    showNewDM(val) { 
      if (val) {
        this.fetchAvailableUsers();
        this.userSearchQuery = '';
        this.$nextTick(() => {
          this.$refs.userSearchInput?.focus();
        });
      }
    }
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
      if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
      }
      return name.substring(0, 2).toUpperCase();
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
/* Existing styles remain the same... */
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
</style>