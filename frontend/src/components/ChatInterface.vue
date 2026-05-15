<template>
  <div class="chat-wrapper">
    <!-- Sidebar -->
    <div class="chat-sidebar">
      <div class="sidebar-header">
        <div class="sidebar-header-inner">
          <h1 class="sidebar-title">Messages</h1>
          <button @click="showIntegrationsModal = true" class="icon-btn" title="Settings">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="3"></circle>
              <path d="M12 1v6m0 6v6m-9-9h6m6 0h6"></path>
            </svg>
          </button>
        </div>
        <div class="search-container">
          <input type="text" class="search-input" placeholder="Search messages..." v-model="searchQuery" />
        </div>
      </div>

      <div class="sidebar-body">
        <!-- Special Chats Section -->
        <div class="section">
          <div class="section-header">
            <div class="section-header-left">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
              </svg>
              <span>Special</span>
            </div>
          </div>
          
          <!-- AI Assistant with Provider Badge -->
          <div @click="selectAIChat" class="chat-item special-item" :class="{ 'is-active': activeChatType === 'ai' }">
            <div class="chat-item-icon">🤖</div>
            <span class="chat-item-name">AI Assistant</span>
            <span class="provider-badge">{{ aiProvider === 'groq' ? 'Groq' : 'Gemini' }}</span>
          </div>

          <!-- Supervisor Chat -->
          <div v-if="isEmployee && supervisorInfo" @click="selectSupervisorChat" class="chat-item special-item" :class="{ 'is-active': activeChatType === 'supervisor' }">
            <div class="chat-item-icon">👔</div>
            <span class="chat-item-name">{{ supervisorInfo.name }}</span>
            <span v-if="supervisorUnreadCount > 0" class="unread-badge">{{ supervisorUnreadCount }}</span>
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
          <div v-if="filteredChannels.length === 0" class="empty-state">No channels</div>
          <div v-else v-for="item in filteredChannels" :key="item.id" @click="selectChat(item)" class="chat-item" :class="{ 'is-active': selectedChat?.id === item.id && activeChatType === 'channel' }">
            <span class="chat-item-name" :class="{ 'is-unread': item.unread_count > 0 }"># {{ getChatDisplayName(item) }}</span>
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
          <div v-if="filteredDirectMessages.length === 0" class="empty-state">No direct messages</div>
          <div v-else v-for="item in filteredDirectMessages" :key="item.id" @click="selectChat(item)" class="chat-item" :class="{ 'is-active': selectedChat?.id === item.id && activeChatType === 'direct' }">
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
      <div v-if="!hasActiveChat" class="chat-empty">
        <div class="chat-empty-content">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity:0.3; margin-bottom: 12px;">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
          </svg>
          <h3>Select a conversation</h3>
          <p>Choose a channel, direct message, or chat with AI Assistant</p>
        </div>
      </div>

      <template v-else>
        <div class="chat-header">
          <div class="chat-header-left">
            <div class="chat-avatar">
              <img v-if="getChatAvatar()" :src="getChatAvatar()" alt="Avatar" />
              <div v-else class="avatar-placeholder">{{ getChatInitials() }}</div>
            </div>
            <div>
              <div class="chat-title">{{ getCurrentChatTitle }}</div>
              <div class="status" v-if="activeChatType !== 'ai'">
                <span class="status-dot"></span>
                {{ getChatStatus() }}
              </div>
              <div class="status" v-if="activeChatType === 'ai'">
                <span class="status-dot ai-status"></span>
                {{ aiProvider === 'groq' ? 'Groq AI - Fast & Intelligent' : 'Gemini AI' }}
              </div>
            </div>
          </div>
          <button v-if="activeChatType !== 'ai'" @click="toggleFavorite" class="icon-btn" :title="selectedChat?.is_favorite ? 'Unstar' : 'Star'">
            <svg width="18" height="18" viewBox="0 0 24 24" :fill="selectedChat?.is_favorite ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="2" :style="{ color: selectedChat?.is_favorite ? '#f59e0b' : 'inherit' }">
              <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
            </svg>
          </button>
          <button v-if="activeChatType === 'ai'" @click="clearAIChat" class="icon-btn" title="Clear conversation">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="3 6 5 6 21 6"></polyline>
              <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            </svg>
          </button>
        </div>

        <div ref="messagesContainer" class="messages-area">
          <div v-if="loadingMessages" class="messages-loading">
            <div class="spinner"></div>
            <span>Loading messages...</span>
          </div>
          <div v-else-if="currentMessages.length === 0" class="messages-empty">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
              <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
            <p>{{ getEmptyMessage }}</p>
            <div v-if="activeChatType === 'ai'" class="quick-actions">
              <button @click="quickAsk('How do I request time off?')" class="quick-btn">📅 Request Leave</button>
              <button @click="quickAsk('How do I view my payslip?')" class="quick-btn">💰 View Payslip</button>
              <button @click="quickAsk('How do I clock in?')" class="quick-btn">⏰ Clock In/Out</button>
              <button @click="quickAsk('How do I update my profile?')" class="quick-btn">👤 Update Profile</button>
              <button @click="quickAsk('How can I improve my productivity?')" class="quick-btn">⚡ Productivity Tips</button>
              <button @click="quickAsk('What are some stress management techniques?')" class="quick-btn">🧘 Wellness Tips</button>
            </div>
          </div>
          <div v-else class="messages-list">
            <div v-for="message in currentMessages" :key="message.id" :class="['message', message.sender_id === authStore.user?.id ? 'message-sent' : 'message-received']">
              <div class="message-content">
                <div class="message-text" v-html="formatMessageText(message.message)"></div>
                <div class="message-time">{{ formatTime(message.created_at || message.timestamp) }}</div>
              </div>
            </div>
            <div v-if="aiTyping" class="message message-received">
              <div class="message-content">
                <div class="typing-indicator">
                  <span></span><span></span><span></span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="message-input-area">
          <div class="input-wrapper">
            <button class="attach-btn" @click="triggerFileUpload">
              <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12" />
              </svg>
              <input type="file" ref="fileInput" style="display: none" @change="handleFileUpload" />
            </button>
            <textarea
              v-model="newMessage"
              class="message-input"
              :placeholder="getInputPlaceholder"
              @keydown.enter.prevent="sendMessage"
              @keydown.enter.shift="newMessage += '\n'"
              :disabled="sendingMessage"
              rows="1"
              ref="messageInput"
              @input="autoResizeTextarea"
            ></textarea>
            <button class="emoji-btn" @click="toggleEmojiPicker">😊</button>
          </div>
          <button class="send-btn" @click="sendMessage" :disabled="!canSendMessage || sendingMessage">
            <span v-if="!sendingMessage">Send →</span>
            <div v-else class="small-spinner"></div>
          </button>
        </div>
      </template>
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
          <input v-model="userSearchQuery" ref="userSearchInput" type="text" placeholder="Search by name or email..." class="modal-input" />
          <div v-if="filteredAvailableUsers.length > 0" class="modal-count">
            {{ filteredAvailableUsers.length }} user{{ filteredAvailableUsers.length !== 1 ? 's' : '' }} found
          </div>
        </div>
        <div class="modal-list">
          <div v-if="filteredAvailableUsers.length === 0" class="modal-empty"><p>No users found</p></div>
          <div v-else v-for="user in filteredAvailableUsers" :key="user.id" @click="startDM(user)" class="dm-user-item">
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

    <!-- Integrations Modal -->
    <div v-if="showIntegrationsModal" class="modal-backdrop" @click="showIntegrationsModal = false">
      <div class="modal-box" @click.stop>
        <div class="modal-header-row">
          <h2 class="modal-title">Integrations</h2>
          <button @click="showIntegrationsModal = false" class="icon-btn">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="18" y1="6" x2="6" y2="18"></line>
              <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
          </button>
        </div>
        <div class="integrations-list">
          <div class="integration-item">
            <div class="integration-icon">🤖</div>
            <div class="integration-info">
              <div class="integration-name">AI Assistant</div>
              <div class="integration-desc">Powered by Groq AI (Ultra-fast inference)</div>
            </div>
            <div class="integration-badge active">Active</div>
          </div>
          <div class="integration-item" v-if="supervisorInfo">
            <div class="integration-icon">👔</div>
            <div class="integration-info">
              <div class="integration-name">Supervisor Chat</div>
              <div class="integration-desc">Direct messaging with your supervisor</div>
            </div>
            <div class="integration-badge active">Connected</div>
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
  name: 'HRMessagingChat',
  setup() {
    const authStore = useAuthStore();
    return { authStore };
  },
  data() {
    return {
      searchQuery: '',
      activeChatType: null,
      selectedChat: null,
      messages: [],
      newMessage: '',
      selectedFile: null,
      loadingMessages: false,
      sendingMessage: false,
      
      channels: [],
      directMessages: [],
      
      // AI Chat
      aiMessages: [],
      aiTyping: false,
      aiProvider: 'groq', // 'groq' or 'gemini'
      
      // Supervisor Chat
      supervisorInfo: null,
      supervisorMessages: [],
      supervisorUnreadCount: 0,
      
      // Modals
      showNewDM: false,
      showIntegrationsModal: false,
      showCreateChannel: false,
      
      userSearchQuery: '',
      availableUsers: [],
      
      refreshInterval: null,
    };
  },
  computed: {
    isEmployee() {
      return this.authStore.user?.role === 'employee';
    },
    hasActiveChat() {
      return this.activeChatType !== null;
    },
    currentMessages() {
      if (this.activeChatType === 'ai') return this.aiMessages;
      if (this.activeChatType === 'supervisor') return this.supervisorMessages;
      return this.messages;
    },
    filteredChannels() {
      if (!this.searchQuery) return this.channels;
      const q = this.searchQuery.toLowerCase();
      return this.channels.filter(ch => ch.name?.toLowerCase().includes(q) || ch.display_name?.toLowerCase().includes(q));
    },
    filteredDirectMessages() {
      if (!this.searchQuery) return this.directMessages;
      const q = this.searchQuery.toLowerCase();
      return this.directMessages.filter(dm => this.getChatDisplayName(dm).toLowerCase().includes(q));
    },
    filteredAvailableUsers() {
      if (!this.userSearchQuery) return this.availableUsers;
      const q = this.userSearchQuery.toLowerCase();
      return this.availableUsers.filter(u => {
        const name = this.getUserDisplayName(u).toLowerCase();
        const email = (u.email || '').toLowerCase();
        return name.includes(q) || email.includes(q);
      });
    },
    canSendMessage() {
      if (this.activeChatType === 'ai') {
        return this.newMessage.trim().length > 0 && !this.sendingMessage;
      }
      return (this.newMessage.trim() || this.selectedFile) && !this.sendingMessage;
    },
    getUserFullName() {
      return this.authStore.user ? `${this.authStore.user.first_name || ''} ${this.authStore.user.last_name || ''}`.trim() : 'User';
    },
    getUserInitials() {
      const name = this.getUserFullName;
      const parts = name.split(' ');
      return parts.length >= 2 ? (parts[0][0] + parts[1][0]).toUpperCase() : name.substring(0, 2).toUpperCase();
    },
    getCurrentChatTitle() {
      if (this.activeChatType === 'ai') return 'AI Assistant';
      if (this.activeChatType === 'supervisor') return this.supervisorInfo?.name || 'Supervisor';
      return this.getChatDisplayName(this.selectedChat);
    },
    getInputPlaceholder() {
      if (this.activeChatType === 'ai') return 'Ask me anything... (HR questions, productivity tips, wellness advice, etc.)';
      return `Message ${this.getCurrentChatTitle}`;
    },
    getEmptyMessage() {
      if (this.activeChatType === 'ai') return 'Ask me anything about the HR system or general workplace topics!';
      if (this.activeChatType === 'supervisor') return 'No messages with your supervisor yet';
      return 'No messages yet. Say hello!';
    }
  },
  mounted() {
    this.initializeApp();
  },
  beforeUnmount() {
    if (this.refreshInterval) clearInterval(this.refreshInterval);
  },
  watch: {
    showNewDM(val) {
      if (val) {
        this.fetchAvailableUsers();
        this.userSearchQuery = '';
        this.$nextTick(() => { this.$refs.userSearchInput?.focus(); });
      }
    }
  },
  methods: {
    async initializeApp() {
      await this.fetchUserProfile();
      await this.fetchAvailableUsers();
      await this.fetchChats();
      await this.fetchSupervisorInfo();
      this.loadAIChatHistory();
      this.startAutoRefresh();
    },

    async fetchUserProfile() {
      try {
        const res = await axios.get('/api/profile');
        const user = res.data.user || res.data;
        this.userProfile = { first_name: user.first_name || '', last_name: user.last_name || '', email: user.email || '' };
      } catch (err) {
        console.error('Failed to fetch profile:', err);
      }
    },

    async fetchChats() {
      try {
        const res = await axios.get('/api/chat/groups');
        if (res.data.success) {
          this.channels = (res.data.channels || []).map(ch => ({ ...ch, type: 'channel' }));
          this.directMessages = (res.data.direct_messages || []).map(dm => ({ ...dm, type: 'direct' }));
          await this.enhanceDirectMessageNames();
        }
      } catch (err) {
        console.error('Failed to fetch chats:', err);
      }
    },

    async fetchSupervisorInfo() {
      try {
        const res = await axios.get('/api/profile');
        const employee = res.data.employee?.data || res.data.employee;
        if (employee && employee.manager) {
          this.supervisorInfo = {
            id: employee.manager.id,
            name: `${employee.manager.first_name} ${employee.manager.last_name}`,
            first_name: employee.manager.first_name,
            last_name: employee.manager.last_name
          };
          await this.fetchSupervisorMessages();
        }
      } catch (err) {
        console.error('Failed to fetch supervisor info:', err);
      }
    },

    async fetchSupervisorMessages() {
      try {
        const res = await axios.get('/api/supervisor-messages');
        this.supervisorMessages = res.data.data || [];
        this.updateSupervisorUnreadCount();
      } catch (err) {
        console.error('Failed to fetch supervisor messages:', err);
      }
    },

    async fetchMessages(chatId) {
      this.loadingMessages = true;
      try {
        const res = await axios.get(`/api/chat/groups/${chatId}/messages`);
        if (res.data.success) {
          this.messages = res.data.messages || [];
          this.$nextTick(() => this.scrollToBottom());
        }
      } catch (err) {
        console.error('Failed to fetch messages:', err);
      } finally {
        this.loadingMessages = false;
      }
    },

    async sendRegularMessage() {
      const formData = new FormData();
      if (this.newMessage.trim()) formData.append('message', this.newMessage.trim());
      if (this.selectedFile) formData.append('attachment', this.selectedFile);
      
      try {
        const res = await axios.post(`/api/chat/groups/${this.selectedChat.id}/messages`, formData);
        if (res.data.success) {
          this.messages.push(res.data.message);
          this.newMessage = '';
          this.selectedFile = null;
          this.autoResizeTextarea();
          this.$nextTick(() => this.scrollToBottom());
          await this.markAsRead(this.selectedChat.id);
        }
      } catch (err) {
        console.error('Failed to send message:', err);
        alert('Failed to send message. Please try again.');
      }
    },

    async sendSupervisorMessage() {
      try {
        const res = await axios.post('/api/supervisor-messages', {
          message: this.newMessage.trim(),
          category: 'general'
        });
        this.supervisorMessages.push(res.data.data);
        this.newMessage = '';
        this.autoResizeTextarea();
        this.$nextTick(() => this.scrollToBottom());
        return true;
      } catch (err) {
        console.error('Failed to send supervisor message:', err);
        alert('Failed to send message. Please try again.');
        return false;
      }
    },

    async sendAIMessage() {
      if (!this.newMessage.trim()) return;
      
      this.sendingMessage = true;
      this.aiTyping = true;
      
      const userMessage = {
        id: Date.now(),
        message: this.newMessage,
        sender_id: this.authStore.user?.id,
        created_at: new Date().toISOString()
      };
      this.aiMessages.push(userMessage);
      const question = this.newMessage;
      this.newMessage = '';
      this.autoResizeTextarea();
      await this.$nextTick(() => this.scrollToBottom());

      try {
        // Get conversation context for better responses
        const conversationContext = this.getConversationContext();
        
        const response = await axios.post('/api/ai/chat', { 
          message: question,
          context: conversationContext,
          use_system_knowledge: true
        });
        
        if (response.data && response.data.response) {
          const aiMessage = {
            id: Date.now() + 1,
            message: response.data.response,
            sender_id: null,
            created_at: new Date().toISOString()
          };
          this.aiMessages.push(aiMessage);
          this.saveAIChatHistory();
          
          // Show provider info if available
          if (response.data.provider) {
            this.aiProvider = response.data.provider;
          }
          
          await this.$nextTick(() => this.scrollToBottom());
        } else {
          throw new Error('Invalid response from AI');
        }
      } catch (err) {
        console.error('AI Error:', err);
        const errorMessage = {
          id: Date.now() + 1,
          message: this.getFallbackResponse(question),
          sender_id: null,
          created_at: new Date().toISOString()
        };
        this.aiMessages.push(errorMessage);
        await this.$nextTick(() => this.scrollToBottom());
      } finally {
        this.aiTyping = false;
        this.sendingMessage = false;
      }
    },

    getConversationContext() {
      // Get last 5 messages for context (excluding the current user message)
      const lastMessages = this.aiMessages.slice(-5).map(msg => ({
        role: msg.sender_id ? 'user' : 'assistant',
        content: msg.message
      }));
      return lastMessages;
    },

    getFallbackResponse(question) {
      const lowerQuestion = question.toLowerCase();
      
      // System-specific responses
      if (lowerQuestion.includes('clock in') || lowerQuestion.includes('clock out')) {
        return "**To clock in/out:**\n\n1. Go to **Attendance** section\n2. Click **Clock In** at shift start\n3. Click **Clock Out** at shift end\n4. For overtime, use **Clock In Overtime**\n\nYou can view your attendance history in the same section.";
      }
      if (lowerQuestion.includes('leave') || lowerQuestion.includes('time off')) {
        return "**To request leave:**\n\n1. Navigate to **Leaves → Request Leave**\n2. Select leave type (Annual, Sick, etc.)\n3. Choose start/end dates\n4. Add reason and submit\n\nCheck your leave balance in **Leaves → Balance**.";
      }
      if (lowerQuestion.includes('payslip') || lowerQuestion.includes('salary')) {
        return "**To view payslips:**\n\n1. Go to **Payslips** section\n2. Select pay period\n3. Click **View** for details\n4. Click **Download** for PDF\n\nPayslips show salary, allowances, deductions, and net pay.";
      }
      
      // General workplace advice
      if (lowerQuestion.includes('productivity') || lowerQuestion.includes('focus') || lowerQuestion.includes('efficient')) {
        return "**Productivity Best Practices:**\n\n• **Prioritize tasks** using the Eisenhower Matrix (urgent vs important)\n• **Time blocking** - Schedule specific times for specific tasks\n• **Pomodoro Technique** - 25 minutes focused work, 5-minute breaks\n• **Limit distractions** - Turn off notifications during deep work\n• **Use our Task Management system** to track and organize your work\n\nWould you like specific tips for using our task management features?";
      }
      
      if (lowerQuestion.includes('stress') || lowerQuestion.includes('wellness') || lowerQuestion.includes('burnout')) {
        return "**Workplace Wellness Strategies:**\n\n• **Take regular breaks** - Step away from your desk every 90 minutes\n• **Set boundaries** - Define clear work hours and stick to them\n• **Use your leave time** - Regular vacations prevent burnout\n• **Practice mindfulness** - Try 5-minute breathing exercises between tasks\n• **Stay hydrated** - Keep water at your desk\n\nCheck your leave balance in the system to plan your next break!";
      }
      
      if (lowerQuestion.includes('career') || lowerQuestion.includes('growth') || lowerQuestion.includes('promotion')) {
        return "**Career Development Tips:**\n\n• **Set SMART goals** (Specific, Measurable, Achievable, Relevant, Time-bound)\n• **Seek feedback regularly** - Use our performance review system\n• **Learn continuously** - Take advantage of training opportunities\n• **Build your network** - Connect with colleagues across departments\n• **Track achievements** - Document your wins in your profile\n\nAsk your manager about career development opportunities available in our organization.";
      }
      
      if (lowerQuestion.includes('communication') || lowerQuestion.includes('email') || lowerQuestion.includes('message')) {
        return "**Professional Communication Best Practices:**\n\n• **Be clear and concise** - Get to the point quickly\n• **Use proper subject lines** - Make emails easy to search/filter\n• **Consider your audience** - Adjust tone based on recipient\n• **Proofread** - Check for errors before sending\n• **Use our chat system** - For quick questions, use instant messaging\n\nNeed help with our messaging features? I can guide you through them!";
      }
      
      // Default - offer both system and general help
      return "I'm here to help! I can assist with:\n\n**🏢 System-Specific Questions:**\n• 📋 Attendance tracking (clock in/out, overtime)\n• 🏖️ Leave requests and balances\n• 💰 Payslips and salary information\n• ✅ Tasks and assignments\n• 👤 Profile updates and documents\n\n**💡 General Workplace Advice:**\n• ⚡ Productivity and time management\n• 🧘 Stress reduction and wellness\n• 📈 Career development tips\n• 💬 Professional communication\n\nWhat would you like to know?";
    },

    async sendMessage() {
      if (!this.canSendMessage) return;
      
      if (this.activeChatType === 'ai') {
        await this.sendAIMessage();
      } else if (this.activeChatType === 'supervisor') {
        await this.sendSupervisorMessage();
      } else if (this.selectedChat) {
        await this.sendRegularMessage();
      }
    },

    selectAIChat() {
      this.activeChatType = 'ai';
      this.selectedChat = null;
      this.loadAIChatHistory();
      this.$nextTick(() => {
        this.scrollToBottom();
        this.$refs.messageInput?.focus();
      });
    },

    selectSupervisorChat() {
      this.activeChatType = 'supervisor';
      this.selectedChat = null;
      this.$nextTick(() => {
        this.scrollToBottom();
        this.markSupervisorMessagesRead();
        this.$refs.messageInput?.focus();
      });
    },

    async selectChat(chat) {
      this.activeChatType = chat.type;
      this.selectedChat = chat;
      await this.fetchMessages(chat.id);
      if (chat.unread_count > 0) {
        await this.markAsRead(chat.id);
        chat.unread_count = 0;
      }
      this.$nextTick(() => this.$refs.messageInput?.focus());
    },

    async markAsRead(chatId) {
      try {
        await axios.post(`/api/chat/groups/${chatId}/read`);
      } catch (err) {
        console.error('Failed to mark as read:', err);
      }
    },

    markSupervisorMessagesRead() {
      const unreadMessages = this.supervisorMessages.filter(msg => msg.sender_id !== this.authStore.user?.id && !msg.read_at);
      unreadMessages.forEach(async (msg) => {
        try {
          await axios.post(`/api/supervisor-messages/${msg.id}/read`);
          msg.read_at = new Date().toISOString();
        } catch (err) { console.error('Failed to mark message as read:', err); }
      });
      this.updateSupervisorUnreadCount();
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

    quickAsk(question) {
      this.newMessage = question;
      this.sendAIMessage();
    },

    formatMessageText(text) {
      if (!text) return '';
      return text
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\n/g, '<br>')
        .replace(/•/g, '•')
        .replace(/\d+\./g, (match) => `<br>${match}`)
        .replace(/^(\d+\.)/gm, '<br>$1');
    },

    autoResizeTextarea() {
      this.$nextTick(() => {
        const textarea = this.$refs.messageInput;
        if (textarea) {
          textarea.style.height = 'auto';
          textarea.style.height = Math.min(textarea.scrollHeight, 100) + 'px';
        }
      });
    },

    async enhanceDirectMessageNames() {
      if (this.availableUsers.length === 0) await this.fetchAvailableUsers();
      const currentUserId = this.authStore.user?.id;
      for (const dm of this.directMessages) {
        if (!dm.members?.length) {
          try {
            const res = await axios.get(`/api/chat/groups/${dm.id}/members`);
            dm.members = res.data.members || res.data.data || [];
          } catch (err) {}
        }
        if (dm.members?.length) {
          const others = dm.members.filter(m => m.id !== currentUserId);
          if (others.length) {
            const names = others.map(m => this.getUserDisplayName(m)).filter(n => n && n !== 'Unknown');
            if (names.length) dm.display_name = names.join(', ');
          }
        }
      }
    },

    async fetchAvailableUsers() {
      try {
        const res = await axios.get('/api/chat/available-users');
        this.availableUsers = (res.data.users || res.data.data || []).map(user => ({ ...user, name: this.getUserDisplayName(user) }));
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
          const newDM = res.data.group || this.directMessages.find(dm => dm.members?.some(m => m.id === user.id));
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

    getUserDisplayName(user) {
      if (!user) return 'Unknown';
      const fullName = `${user.first_name || ''} ${user.last_name || ''}`.trim();
      if (fullName) return fullName;
      if (user.email) return user.email;
      return `User ${user.id}`;
    },

    getUserInitialsFromUser(user) {
      const name = this.getUserDisplayName(user);
      const parts = name.split(' ');
      return parts.length >= 2 ? (parts[0][0] + parts[1][0]).toUpperCase() : name.substring(0, 2).toUpperCase();
    },

    getChatDisplayName(chat) {
      if (!chat) return '';
      if (chat.type === 'direct') {
        if (chat.members?.length) {
          const others = chat.members.filter(m => m.id !== this.authStore.user?.id);
          if (others.length) return others.map(m => this.getUserDisplayName(m)).join(', ');
        }
        return chat.display_name || 'Direct Message';
      }
      return chat.display_name || chat.name || `Chat ${chat.id}`;
    },

    getChatAvatar() {
      return null;
    },

    getChatInitials() {
      if (this.activeChatType === 'ai') return 'AI';
      if (this.activeChatType === 'supervisor') {
        return this.supervisorInfo?.name?.substring(0, 2).toUpperCase() || 'SP';
      }
      return this.getChatDisplayName(this.selectedChat).substring(0, 2).toUpperCase();
    },

    getChatStatus() {
      return 'Active now';
    },

    updateSupervisorUnreadCount() {
      this.supervisorUnreadCount = this.supervisorMessages.filter(msg => msg.sender_id !== this.authStore.user?.id && !msg.read_at).length;
    },

    saveAIChatHistory() {
      localStorage.setItem('hr_ai_chat_history', JSON.stringify(this.aiMessages));
    },

    loadAIChatHistory() {
      const saved = localStorage.getItem('hr_ai_chat_history');
      if (saved) {
        try {
          this.aiMessages = JSON.parse(saved);
        } catch (err) {
          this.aiMessages = [];
        }
      }
    },
    
    clearAIChat() {
      if (confirm('Clear all AI chat messages?')) {
        this.aiMessages = [];
        localStorage.removeItem('hr_ai_chat_history');
        this.$nextTick(() => this.scrollToBottom());
      }
    },

    triggerFileUpload() {
      this.$refs.fileInput.click();
    },

    handleFileUpload(event) {
      this.selectedFile = event.target.files[0];
      if (this.selectedFile) {
        console.log('File selected:', this.selectedFile.name);
      }
    },

    toggleEmojiPicker() {},

    formatTime(timestamp) {
      if (!timestamp) return '';
      const date = new Date(timestamp);
      const now = new Date();
      const isToday = date.toDateString() === now.toDateString();
      if (isToday) return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
      return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    },

    scrollToBottom() {
      this.$nextTick(() => {
        const container = this.$refs.messagesContainer;
        if (container) container.scrollTop = container.scrollHeight;
      });
    },

    startAutoRefresh() {
      this.refreshInterval = setInterval(async () => {
        if (this.activeChatType === 'supervisor') {
          await this.fetchSupervisorMessages();
          if (this.activeChatType === 'supervisor') this.$nextTick(() => this.scrollToBottom());
        } else if (this.activeChatType !== 'ai' && this.selectedChat) {
          await this.fetchMessages(this.selectedChat.id);
        }
        await this.fetchChats();
      }, 10000);
    }
  }
};
</script>

<style scoped>
/* ... keep all existing styles from your original ... */

/* Add these additional styles */
.provider-badge {
  font-size: 10px;
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  padding: 2px 6px;
  border-radius: 10px;
  margin-left: 6px;
  font-weight: 600;
}

.ai-status {
  background: linear-gradient(135deg, #667eea, #764ba2);
}

.status-dot.ai-status {
  background: linear-gradient(135deg, #667eea, #764ba2);
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { opacity: 0.4; transform: scale(1); }
  50% { opacity: 1; transform: scale(1.2); }
  100% { opacity: 0.4; transform: scale(1); }
}

.quick-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: center;
  margin-top: 20px;
}

.quick-btn {
  padding: 8px 16px;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
  color: #334155;
}

.quick-btn:hover {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
  transform: translateY(-1px);
  border-color: transparent;
}

.small-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid white;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  display: inline-block;
}

/* Reset & Base */
* {
  box-sizing: border-box;
}

.chat-wrapper {
  display: flex;
  width: 100%;
  height: 100vh;
  background: #f8fafc;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
  overflow: hidden;
}

/* Sidebar */
.chat-sidebar {
  width: 320px;
  background: white;
  border-right: 1px solid #e2e8f0;
  display: flex;
  flex-direction: column;
}

.sidebar-header {
  padding: 20px;
  border-bottom: 1px solid #e2e8f0;
}

.sidebar-header-inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.sidebar-title {
  font-size: 18px;
  font-weight: 700;
  color: #1e2937;
  margin: 0;
}

.search-container {
  position: relative;
}

.search-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 13px;
  outline: none;
  transition: all 0.2s;
  color: #1e2937;
  background: white;
}

.search-input:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.search-input::placeholder {
  color: #94a3b8;
}

.sidebar-body {
  flex: 1;
  overflow-y: auto;
  padding: 12px 0;
}

.section {
  margin-bottom: 16px;
}

.section-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 4px 16px;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.section-header-left {
  display: flex;
  align-items: center;
  gap: 6px;
}

.empty-state {
  padding: 8px 20px;
  font-size: 12px;
  color: #94a3b8;
  font-style: italic;
}

.chat-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 8px 16px;
  cursor: pointer;
  transition: all 0.2s;
  border-left: 3px solid transparent;
}

.chat-item:hover {
  background: #f1f5f9;
}

.chat-item.is-active {
  background: #f1f5f9;
  border-left-color: #6366f1;
}

.chat-item-icon {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
}

.chat-item-name {
  flex: 1;
  font-size: 14px;
  color: #334155;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.chat-item-name.is-unread {
  font-weight: 700;
  color: #1e2937;
}

.unread-badge {
  background: #e01e5a;
  color: white;
  font-size: 11px;
  font-weight: 700;
  padding: 2px 6px;
  border-radius: 10px;
}

/* Sidebar Footer */
.sidebar-footer {
  padding: 16px 20px;
  border-top: 1px solid #e2e8f0;
  background: white;
}

.user-profile {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: linear-gradient(135deg, #4ade80, #3b82f6);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 14px;
  font-weight: 700;
}

.user-name {
  font-weight: 600;
  color: #1e2937;
  font-size: 14px;
}

.user-status {
  font-size: 11px;
  color: #10b981;
}

/* Chat Main Area */
.chat-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  background: #f8fafc;
}

.chat-empty {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.chat-empty-content {
  text-align: center;
  color: #94a3b8;
}

.chat-empty-content h3 {
  color: #334155;
  margin-bottom: 8px;
}

/* Chat Header */
.chat-header {
  height: 72px;
  background: white;
  border-bottom: 1px solid #e2e8f0;
  padding: 0 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.chat-header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}

.chat-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  overflow: hidden;
  background: linear-gradient(135deg, #667eea, #764ba2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
  font-weight: 700;
  color: white;
}

.chat-title {
  font-size: 17px;
  font-weight: 700;
  color: #1e2937;
}

.status {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  color: #10b981;
  margin-top: 2px;
}

.status-dot {
  width: 8px;
  height: 8px;
  background: #10b981;
  border-radius: 50%;
}

/* Messages Area */
.messages-area {
  flex: 1;
  overflow-y: auto;
  padding: 24px;
}

.messages-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  height: 100%;
  color: #94a3b8;
}

.spinner {
  width: 32px;
  height: 32px;
  border: 3px solid #e2e8f0;
  border-top-color: #6366f1;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.messages-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
  height: 100%;
  color: #94a3b8;
}

.messages-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.message {
  display: flex;
  max-width: 70%;
}

.message-sent {
  align-self: flex-end;
}

.message-received {
  align-self: flex-start;
}

.message-content {
  padding: 10px 14px;
  border-radius: 16px;
  max-width: 100%;
}

.message-sent .message-content {
  background: #4f46e5;
  color: white;
  border-bottom-right-radius: 4px;
}

.message-received .message-content {
  background: white;
  color: #1e2937;
  border: 1px solid #e2e8f0;
  border-bottom-left-radius: 4px;
}

.message-text {
  font-size: 14px;
  line-height: 1.5;
  word-break: break-word;
}

.message-text strong {
  font-weight: 700;
}

.message-time {
  font-size: 11px;
  opacity: 0.7;
  margin-top: 4px;
  text-align: right;
}

.typing-indicator {
  display: flex;
  gap: 4px;
  padding: 8px 12px;
}

.typing-indicator span {
  width: 8px;
  height: 8px;
  background: #94a3b8;
  border-radius: 50%;
  animation: typing 1.4s infinite;
}

.typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
.typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

@keyframes typing {
  0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
  30% { transform: translateY(-6px); opacity: 1; }
}

/* Message Input Area - FIXED for text visibility */
.message-input-area {
  padding: 16px 24px;
  background: white;
  border-top: 1px solid #e2e8f0;
  display: flex;
  align-items: center;
  gap: 12px;
}

.input-wrapper {
  flex: 1;
  display: flex;
  align-items: center;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 4px 12px;
  gap: 8px;
  transition: all 0.2s;
}

.input-wrapper:focus-within {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.attach-btn, .emoji-btn {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: transparent;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  color: #64748b;
  transition: all 0.2s;
  flex-shrink: 0;
}

.attach-btn:hover, .emoji-btn:hover {
  background: #f1f5f9;
  color: #4f46e5;
}

textarea.message-input {
  flex: 1;
  border: none;
  outline: none;
  background: transparent;
  font-size: 14px;
  color: #1e2937;
  padding: 10px 0;
  font-family: inherit;
  resize: none;
  min-height: 44px;
  max-height: 120px;
  line-height: 1.5;
  width: 100%;
}

textarea.message-input::placeholder {
  color: #94a3b8;
}

textarea.message-input:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.send-btn {
  padding: 10px 24px;
  background: #4f46e5;
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
  flex-shrink: 0;
}

.send-btn:hover:not(:disabled) {
  background: #4338ca;
  transform: translateY(-1px);
}

.send-btn:disabled {
  background: #cbd5e1;
  cursor: not-allowed;
}

.send-arrow {
  font-size: 16px;
}

/* Quick Actions */
.quick-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: center;
  margin-top: 20px;
}

.quick-btn {
  padding: 8px 16px;
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  font-size: 13px;
  cursor: pointer;
  transition: all 0.2s;
  color: #334155;
}

.quick-btn:hover {
  background: #e2e8f0;
  transform: translateY(-1px);
  color: #1e2937;
}

.small-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid white;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  display: inline-block;
}

/* Icon Button */
.icon-btn {
  background: transparent;
  border: none;
  cursor: pointer;
  color: #64748b;
  padding: 6px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.icon-btn:hover {
  background: #f1f5f9;
  color: #1e2937;
}

.icon-btn-sm {
  background: transparent;
  border: none;
  cursor: pointer;
  color: #64748b;
  padding: 4px;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.icon-btn-sm:hover {
  background: #f1f5f9;
}

/* Modals */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-box {
  background: white;
  border-radius: 16px;
  padding: 24px;
  width: 100%;
  max-width: 500px;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  gap: 16px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.modal-title {
  font-size: 20px;
  font-weight: 700;
  color: #1e2937;
  margin: 0;
}

.modal-input {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-size: 14px;
  outline: none;
  color: #1e2937;
  background: white;
}

.modal-input:focus {
  border-color: #6366f1;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.modal-list {
  overflow-y: auto;
  max-height: 400px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.modal-count {
  font-size: 12px;
  color: #64748b;
  margin-top: 8px;
}

.modal-empty {
  text-align: center;
  color: #94a3b8;
  padding: 40px 0;
}

.dm-user-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.dm-user-item:hover {
  background: #eff6ff;
  border-color: #bfdbfe;
}

.dm-user-avatar {
  width: 40px;
  height: 40px;
  background: linear-gradient(135deg, #3b82f6, #8b5cf6);
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 14px;
  font-weight: 700;
  flex-shrink: 0;
}

.dm-user-info {
  flex: 1;
}

.dm-user-name {
  font-weight: 600;
  color: #1e2937;
  margin-bottom: 2px;
}

.dm-user-email {
  font-size: 12px;
  color: #64748b;
}

/* Integrations */
.integrations-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.integration-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px;
  background: #f8fafc;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.integration-icon {
  width: 44px;
  height: 44px;
  background: white;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
}

.integration-info {
  flex: 1;
}

.integration-name {
  font-weight: 600;
  color: #1e2937;
}

.integration-desc {
  font-size: 12px;
  color: #64748b;
}

.integration-badge {
  padding: 4px 10px;
  background: #e2e8f0;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  color: #475569;
}

.integration-badge.active {
  background: #dcfce7;
  color: #166534;
}

/* Scrollbar */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Responsive */
@media (max-width: 768px) {
  .chat-sidebar {
    width: 280px;
  }
  
  .message {
    max-width: 85%;
  }
  
  .message-input-area {
    padding: 12px 16px;
  }
  
  .send-btn {
    padding: 8px 16px;
  }
}

@media (max-width: 640px) {
  .chat-sidebar {
    width: 260px;
  }
  
  .chat-header {
    padding: 0 16px;
  }
  
  .messages-area {
    padding: 16px;
  }
  
  .quick-actions {
    gap: 8px;
  }
  
  .quick-btn {
    padding: 6px 12px;
    font-size: 12px;
  }
}
</style>