<template>
  <div class="chat-interface">
    <div class="chat-container">
      <!-- Sidebar with groups list -->
      <div class="chat-sidebar" :class="{ 'mobile-hidden': selectedGroup }">
        <div class="sidebar-header">
          <h2>Messages</h2>
          <button @click="showNewGroupModal = true" class="btn-new-chat">
            ➕ <span class="desktop-only">New Chat</span>
          </button>
        </div>

        <div class="search-box">
          <input 
            v-model="searchQuery" 
            type="text" 
            placeholder="Search conversations..."
            class="search-input"
          />
        </div>

        <div class="groups-list">
          <div v-if="loadingGroups && groups.length === 0" class="loading-state">
            Loading chats...
          </div>
          <div v-else-if="filteredGroups.length === 0" class="empty-state">
            No conversations found.
          </div>
          <div 
            v-else
            v-for="group in filteredGroups" 
            :key="group.id"
            @click="selectGroup(group)"
            :class="['group-item', { active: selectedGroup?.id === group.id }]"
          >
            <div class="group-avatar">
              <img v-if="group.avatar" :src="group.avatar" :alt="group.name" />
              <div v-else class="avatar-placeholder">
                {{ group.name ? group.name.charAt(0).toUpperCase() : '#' }}
              </div>
            </div>
            <div class="group-info">
              <div class="group-name-row">
                <span class="group-name">{{ group.name }}</span>
                <span v-if="group.unread_count > 0" class="unread-badge">
                  {{ group.unread_count }}
                </span>
              </div>
              <p v-if="group.last_message" class="last-message">
                {{ truncateMessage(group.last_message.message || group.last_message.content) }}
              </p>
              <p v-else class="last-message">No messages yet</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Chat area -->
      <div class="chat-area" :class="{ 'mobile-visible': selectedGroup }">
        <!-- State 1: No Group Selected (Desktop Only) -->
        <div v-if="!selectedGroup" class="no-chat-selected">
          <div class="empty-chat-placeholder">
            <h3>Select a conversation</h3>
            <p>Choose a group from the sidebar to send a message.</p>
          </div>
        </div>

        <!-- State 2: Group Selected -->
        <div v-else class="chat-content">
          <!-- Chat header (Fixed Top) -->
          <div class="chat-header">
            <div class="chat-header-left">
              <!-- Back Button (Mobile Only) -->
              <button @click="deselectGroup" class="btn-back mobile-only">
                ←
              </button>
              <div class="chat-header-info">
                <h3>{{ selectedGroup.name }}</h3>
                <p>{{ selectedGroup.member_count || 0 }} member{{ selectedGroup.member_count !== 1 ? 's' : '' }}</p>
              </div>
            </div>
            <div class="chat-header-actions">
              <button @click="openAddMemberModal" class="btn-icon" title="Add Members">
                👥
              </button>
              <button @click="showGroupDetails = true" class="btn-icon" title="Group Details">
                ℹ️
              </button>
            </div>
          </div>

          <!-- Messages area (Scrollable Middle) -->
          <div ref="messagesContainer" class="messages-container">
            <div v-if="loadingMessages && messages.length === 0" class="loading">Loading messages...</div>
            <div v-else-if="messages.length === 0" class="empty-messages">
              <p>No messages here yet. Be the first to write!</p>
            </div>
            
            <div 
              v-else
              v-for="message in messages" 
              :key="message.id"
              :class="['message', { 'message-own': message.is_own }]"
            >
              <div v-if="!message.is_own" class="message-avatar">
                {{ message.user?.name?.charAt(0).toUpperCase() || 'U' }}
              </div>
              <div class="message-content">
                <div v-if="!message.is_own" class="message-sender">
                  {{ message.user?.name || 'Unknown' }}
                </div>
                
                <div v-if="message.reply_to" class="message-reply">
                  <strong>{{ message.reply_to.user_name }}:</strong>
                  {{ message.reply_to.message }}
                </div>
                
                <div class="message-bubble">
                  <!-- System messages -->
                  <p v-if="message.type === 'system'" style="font-style: italic; color: #666;">
                    {{ message.message }}
                  </p>
                  
                  <!-- Regular messages -->
                  <p v-else>{{ message.message }}</p>
                  
                  <!-- File attachments -->
                  <div v-if="message.attachment_url" class="message-attachment">
                    <a v-if="message.type === 'image'" :href="message.attachment_url" target="_blank">
                      <img :src="message.attachment_url" :alt="message.attachment_name" class="attachment-image" />
                    </a>
                    <a v-else :href="message.attachment_url" target="_blank" download>
                      📎 {{ message.attachment_name || 'Attachment' }}
                    </a>
                  </div>
                  
                  <span class="message-time">
                    {{ formatMessageTime(message.created_at) }}
                    <span v-if="message.is_edited"> (edited)</span>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Message input area (Fixed Bottom) -->
          <div class="message-input-area">
            <div v-if="selectedFile" class="selected-file-preview">
              <span class="file-name">📎 {{ selectedFile.name }}</span>
              <button @click="selectedFile = null" class="btn-remove-file" title="Remove file">✕</button>
            </div>

            <form @submit.prevent="sendMessage" class="message-form">
              <input 
                type="file" 
                ref="fileInput" 
                @change="handleFileSelect"
                style="display: none"
                accept="image/*,.pdf,.doc,.docx,.xls,.xlsx"
              />
              
              <button 
                type="button" 
                @click="$refs.fileInput.click()"
                class="btn-attach"
                title="Attach file"
              >
                📎
              </button>
              
              <input 
                v-model="newMessage" 
                type="text" 
                placeholder="Message..."
                class="message-input"
                :disabled="sending"
                autocomplete="off"
                @keydown.enter.exact.prevent="sendMessage"
              />
              
              <button 
                type="submit" 
                class="btn-send"
                :disabled="(!newMessage.trim() && !selectedFile) || sending"
                title="Send Message"
              >
                <span v-if="sending">...</span>
                <span v-else>➤</span>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals (Unchanged) -->
    <!-- New Group Modal -->
    <div v-if="showNewGroupModal" class="modal-overlay" @click.self="showNewGroupModal = false">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3>New Chat</h3>
          <button @click="showNewGroupModal = false" class="btn-close">✕</button>
        </div>
        
        <div class="modal-body">
          <div class="form-group">
            <label>Group Name <span class="required">*</span></label>
            <input v-model="newGroupName" type="text" placeholder="Enter group name" />
          </div>
          
          <div class="form-group">
            <label>Group Type <span class="required">*</span></label>
            <select v-model="newGroupType">
              <option value="" disabled selected>Select a type</option>
              <option value="custom">Custom Group</option>
              <option value="department">Department Group</option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Add Members <span class="required">*</span></label>
            <input 
              v-model="userSearch" 
              type="text" 
              placeholder="Search users..."
              @input="searchUsers"
            />
            <div class="user-list">
              <div v-if="filteredUsers.length === 0" class="no-users">
                No users found
              </div>
              <div 
                v-else
                v-for="user in filteredUsers" 
                :key="user.id"
                @click="toggleUserSelection(user)"
                :class="['user-item', { selected: selectedUserIds.includes(user.id) }]"
              >
                <div class="user-item-info">
                  <span class="user-name">{{ user.name }}</span>
                  <span class="user-email">{{ user.email }}</span>
                </div>
                <div class="user-checkbox">
                  <span v-if="selectedUserIds.includes(user.id)">☑️</span>
                  <span v-else>⬜</span>
                </div>
              </div>
            </div>
            <div class="selection-summary" v-if="selectedUserIds.length > 0">
              {{ selectedUserIds.length }} user(s) selected
            </div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button @click="showNewGroupModal = false" class="btn-cancel">Cancel</button>
          <button 
            @click="createGroup" 
            class="btn-primary" 
            :disabled="!isFormValid">
            Create
          </button>
        </div>
      </div>
    </div>

    <!-- Add Member Modal -->
    <div v-if="showAddMemberModal" class="modal-overlay" @click.self="showAddMemberModal = false">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3>Add Members to {{ selectedGroup?.name }}</h3>
          <button @click="showAddMemberModal = false" class="btn-close">✕</button>
        </div>
        
        <div class="modal-body">
          <div class="form-group">
            <label>Search Users</label>
            <input 
              v-model="addMemberSearch" 
              type="text" 
              placeholder="Search users to add..."
              @input="searchUsersForAdding"
            />
            <div class="user-list">
              <div v-if="addMemberFilteredUsers.length === 0" class="no-users">
                No users found
              </div>
              <div 
                v-else
                v-for="user in addMemberFilteredUsers" 
                :key="user.id"
                @click="toggleAddMemberSelection(user)"
                :class="['user-item', { selected: selectedAddMemberIds.includes(user.id), disabled: isExistingMember(user.id) }]"
              >
                <div class="user-item-info">
                  <span class="user-name">{{ user.name }}</span>
                  <span class="user-email">{{ user.email }}</span>
                  <span v-if="isExistingMember(user.id)" class="existing-member-badge">Already a member</span>
                </div>
                <div class="user-checkbox">
                  <span v-if="selectedAddMemberIds.includes(user.id)">☑️</span>
                  <span v-else-if="isExistingMember(user.id)">✓</span>
                  <span v-else>⬜</span>
                </div>
              </div>
            </div>
            <div class="selection-summary" v-if="selectedAddMemberIds.length > 0">
              {{ selectedAddMemberIds.length }} user(s) selected to add
            </div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button @click="showAddMemberModal = false" class="btn-cancel">Cancel</button>
          <button 
            @click="addMembersToGroup" 
            class="btn-primary" 
            :disabled="selectedAddMemberIds.length === 0 || addingMembers">
            <span v-if="addingMembers">Adding...</span>
            <span v-else>Add Members</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Group Details Modal -->
    <div v-if="showGroupDetails" class="modal-overlay" @click.self="showGroupDetails = false">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h3>Group Details</h3>
          <button @click="showGroupDetails = false" class="btn-close">✕</button>
        </div>
        
        <div class="modal-body">
          <div v-if="loadingGroupDetails" class="loading">Loading group details...</div>
          <div v-else-if="groupDetails">
            <div class="form-group">
              <label>Group Name</label>
              <p class="detail-value">{{ groupDetails.name }}</p>
            </div>
            
            <div class="form-group">
              <label>Group Type</label>
              <p class="detail-value">{{ groupDetails.type }}</p>
            </div>
            
            <div class="form-group">
              <label>Created</label>
              <p class="detail-value">{{ formatDate(groupDetails.created_at) }}</p>
            </div>
            
            <div class="form-group">
              <label>Members ({{ groupDetails.members?.length || 0 }})</label>
              <div class="members-list">
                <div v-if="!groupDetails.members || groupDetails.members.length === 0" class="no-members">
                  No members found
                </div>
                <div 
                  v-else
                  v-for="member in groupDetails.members" 
                  :key="member.id"
                  class="member-item"
                >
                  <div class="member-avatar">
                    {{ member.name?.charAt(0).toUpperCase() || 'U' }}
                  </div>
                  <div class="member-info">
                    <span class="member-name">{{ member.name }}</span>
                    <span class="member-email">{{ member.email }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="modal-footer">
          <button @click="showGroupDetails = false" class="btn-primary">Close</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import axios from 'axios'
import { format } from 'date-fns'

// Data
const groups = ref([])
const loadingGroups = ref(false)
const selectedGroup = ref(null)

const messages = ref([])
const loadingMessages = ref(false)
const sending = ref(false)

// Inputs
const newMessage = ref('')
const searchQuery = ref('')
const selectedFile = ref(null)
const messagesContainer = ref(null)
const refreshInterval = ref(null)

// New group modal data
const showNewGroupModal = ref(false)
const showGroupDetails = ref(false)
const newGroupName = ref('')
const newGroupType = ref('')
const userSearch = ref('')
const availableUsers = ref([])
const selectedUserIds = ref([])

// Add member modal data
const showAddMemberModal = ref(false)
const addMemberSearch = ref('')
const addMemberAvailableUsers = ref([])
const selectedAddMemberIds = ref([])
const addingMembers = ref(false)
const groupDetails = ref(null)
const loadingGroupDetails = ref(false)

// Computed
const filteredGroups = computed(() => {
  if (!searchQuery.value) return groups.value
  
  const query = searchQuery.value.toLowerCase()
  return groups.value.filter(g => 
    g.name.toLowerCase().includes(query) ||
    (g.last_message?.message?.toLowerCase().includes(query))
  )
})

const filteredUsers = computed(() => {
  if (!userSearch.value) return availableUsers.value
  
  const query = userSearch.value.toLowerCase()
  return availableUsers.value.filter(user => 
    user.name.toLowerCase().includes(query) ||
    user.email.toLowerCase().includes(query)
  )
})

const addMemberFilteredUsers = computed(() => {
  if (!addMemberSearch.value) return addMemberAvailableUsers.value
  
  const query = addMemberSearch.value.toLowerCase()
  return addMemberAvailableUsers.value.filter(user => 
    user.name.toLowerCase().includes(query) ||
    user.email.toLowerCase().includes(query)
  )
})

const isFormValid = computed(() => {
  return newGroupName.value.trim() !== '' && 
         newGroupType.value !== '' && 
         selectedUserIds.value.length > 0
})

// Helper methods
function truncateMessage(message, length = 35) {
  if (!message) return ''
  return message.length > length ? message.substring(0, length) + '...' : message
}

function formatMessageTime(timestamp) {
  if (!timestamp) return ''
  try {
    const date = new Date(timestamp)
    return format(date, 'hh:mm a')
  } catch (e) {
    return timestamp
  }
}

function formatDate(timestamp) {
  if (!timestamp) return ''
  try {
    const date = new Date(timestamp)
    return format(date, 'MMM dd, yyyy hh:mm a')
  } catch (e) {
    return timestamp
  }
}

function scrollToBottom() {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
}

function isExistingMember(userId) {
  if (!groupDetails.value || !groupDetails.value.members) return false
  return groupDetails.value.members.some(member => member.id === userId)
}

// API Methods
async function fetchGroups(isBackground = false) {
  if (!isBackground) loadingGroups.value = true
  
  try {
    const response = await axios.get('/api/chat/groups')
    if (response.data.success) {
      groups.value = response.data.groups || []
    }
  } catch (err) {
    console.error('Failed to fetch groups:', err)
  } finally {
    if (!isBackground) loadingGroups.value = false
  }
}

async function fetchMessages(isBackground = false) {
  if (!selectedGroup.value) return
  
  if (!isBackground) loadingMessages.value = true
  
  try {
    const response = await axios.get(`/api/chat/groups/${selectedGroup.value.id}/messages`)
    if (response.data.success) {
      const newMessages = response.data.messages || []
      
      const container = messagesContainer.value
      let isNearBottom = true
      
      if (container) {
        const distanceToBottom = container.scrollHeight - container.scrollTop - container.clientHeight
        isNearBottom = distanceToBottom < 100
      }

      messages.value = newMessages
      
      if (!isBackground || isNearBottom) {
        scrollToBottom()
      }
    }
  } catch (err) {
    console.error('Failed to fetch messages:', err)
  } finally {
    if (!isBackground) loadingMessages.value = false
  }
}

async function sendMessage() {
  if ((!newMessage.value.trim() && !selectedFile.value) || sending.value) return
  
  sending.value = true
  try {
    const formData = new FormData()
    
    if (newMessage.value.trim()) {
      formData.append('message', newMessage.value.trim())
    }
    
    if (selectedFile.value) {
      formData.append('attachment', selectedFile.value)
    }
    
    const response = await axios.post(
      `/api/chat/groups/${selectedGroup.value.id}/messages`,
      formData,
      {
        headers: { 
          'Content-Type': 'multipart/form-data',
          'Accept': 'application/json'
        }
      }
    )
    
    if (response.data.success) {
      newMessage.value = ''
      selectedFile.value = null
      await fetchMessages(false) 
    } else {
      alert('Failed to send message: ' + response.data.message)
    }
  } catch (err) {
    console.error('Failed to send message:', err)
    alert('Failed to send message.')
  } finally {
    sending.value = false
  }
}

async function selectGroup(group) {
  selectedGroup.value = group
  await fetchMessages(false)
  
  if (group.unread_count > 0) {
    try {
      await axios.post(`/api/chat/groups/${group.id}/read`)
      group.unread_count = 0
    } catch (err) {
      console.error('Failed to mark as read:', err)
    }
  }
  fetchGroups(true)
}

function deselectGroup() {
  selectedGroup.value = null
  fetchGroups(false)
}

function handleFileSelect(event) {
  const file = event.target.files[0]
  if (file) {
    if (file.size > 10 * 1024 * 1024) {
      alert('File size must be less than 10MB')
      return
    }
    selectedFile.value = file
  }
  event.target.value = null
}

async function fetchAvailableUsers() {
  try {
    const response = await axios.get('/api/chat/available-users')
    if (response.data.success) {
      availableUsers.value = response.data.users || []
    }
  } catch (err) {
    console.error('Failed to fetch users:', err)
  }
}

async function fetchUsersForAdding() {
  try {
    const response = await axios.get('/api/chat/available-users')
    if (response.data.success) {
      addMemberAvailableUsers.value = response.data.users || []
    }
  } catch (err) {
    console.error('Failed to fetch users for adding:', err)
  }
}

function searchUsers() {}
function searchUsersForAdding() {}

function toggleUserSelection(user) {
  const index = selectedUserIds.value.indexOf(user.id)
  if (index > -1) {
    selectedUserIds.value.splice(index, 1)
  } else {
    selectedUserIds.value.push(user.id)
  }
}

function toggleAddMemberSelection(user) {
  if (isExistingMember(user.id)) return
  
  const index = selectedAddMemberIds.value.indexOf(user.id)
  if (index > -1) {
    selectedAddMemberIds.value.splice(index, 1)
  } else {
    selectedAddMemberIds.value.push(user.id)
  }
}

async function createGroup() {
  if (!isFormValid.value) return
  
  try {
    const response = await axios.post('/api/chat/groups', {
      name: newGroupName.value.trim(),
      type: newGroupType.value,
      member_ids: selectedUserIds.value
    })
    
    if (response.data.success) {
      const newGroup = response.data.group
      groups.value.unshift(newGroup)
      showNewGroupModal.value = false
      newGroupName.value = ''
      newGroupType.value = ''
      selectedUserIds.value = []
      userSearch.value = ''
      await selectGroup(newGroup)
    }
  } catch (err) {
    console.error('Failed to create group:', err)
  }
}

async function openAddMemberModal() {
  if (!selectedGroup.value) return
  showAddMemberModal.value = true
  addMemberSearch.value = ''
  selectedAddMemberIds.value = []
  await Promise.all([fetchUsersForAdding(), fetchGroupDetails()])
}

async function fetchGroupDetails() {
  if (!selectedGroup.value) return
  loadingGroupDetails.value = true
  try {
    const response = await axios.get(`/api/chat/groups/${selectedGroup.value.id}/details`)
    if (response.data.success) {
      groupDetails.value = response.data.group
    }
  } catch (err) {
    console.error('Failed to fetch group details:', err)
  } finally {
    loadingGroupDetails.value = false
  }
}

async function addMembersToGroup() {
  if (!selectedGroup.value || selectedAddMemberIds.value.length === 0 || addingMembers.value) return
  addingMembers.value = true
  try {
    const response = await axios.post(`/api/chat/groups/${selectedGroup.value.id}/add-members`, {
      member_ids: selectedAddMemberIds.value
    })
    if (response.data.success) {
      await fetchGroupDetails()
      const groupIndex = groups.value.findIndex(g => g.id === selectedGroup.value.id)
      if (groupIndex !== -1) {
        groups.value[groupIndex].member_count = (groups.value[groupIndex].member_count || 0) + selectedAddMemberIds.value.length
        selectedGroup.value.member_count = groups.value[groupIndex].member_count
      }
      selectedAddMemberIds.value = []
      showAddMemberModal.value = false
      alert('Members added successfully!')
    }
  } catch (err) {
    console.error('Failed to add members:', err)
  } finally {
    addingMembers.value = false
  }
}

// Lifecycle
onMounted(async () => {
  await fetchGroups(false)
  await fetchAvailableUsers()
  
  refreshInterval.value = setInterval(async () => {
    if (selectedGroup.value) {
      await fetchMessages(true)
    }
    await fetchGroups(true)
  }, 5000)
})

onUnmounted(() => {
  if (refreshInterval.value) {
    clearInterval(refreshInterval.value)
  }
})

watch(showNewGroupModal, (newVal) => {
  if (newVal) fetchAvailableUsers()
})

watch(showGroupDetails, (newVal) => {
  if (newVal && selectedGroup.value) fetchGroupDetails()
})
</script>

<style scoped>
/* Main Layout - Fixed Height for Mobile/Desktop */
.chat-interface {
  height: calc(100vh - 60px); /* Adjust 60px if your top navbar is different */
  background: #f5f7fb;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  overflow: hidden;
  display: flex;
  flex-direction: column;
}

.chat-container {
  display: flex;
  flex: 1;
  width: 100%;
  height: 100%; /* Force full height */
  margin: 0;
  background: white;
  box-shadow: 0 0 15px rgba(0,0,0,0.05);
  position: relative;
  overflow: hidden;
}

/* Sidebar */
.chat-sidebar {
  width: 350px;
  border-right: 1px solid #e1e4e8;
  display: flex;
  flex-direction: column;
  background: #fff;
  z-index: 2;
  transition: transform 0.3s ease;
  height: 100%; /* Ensure full height */
}

.sidebar-header {
  padding: 1.25rem 1rem;
  border-bottom: 1px solid #e1e4e8;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8f9fa;
  flex-shrink: 0;
}

.sidebar-header h2 {
  margin: 0;
  font-size: 1.2rem;
  color: #333;
}

.btn-new-chat {
  background: #3b82f6;
  color: white;
  border: none;
  padding: 0.5rem 0.8rem;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.85rem;
  font-weight: 500;
  transition: background 0.2s;
  display: flex;
  align-items: center;
  gap: 5px;
}

.btn-new-chat:hover {
  background: #2563eb;
}

.search-box {
  padding: 1rem;
  border-bottom: 1px solid #f0f0f0;
  flex-shrink: 0;
}

.search-input {
  width: 100%;
  padding: 0.6rem 0.8rem;
  border: 1px solid #e1e4e8;
  border-radius: 8px;
  background: #f8f9fa;
  font-size: 0.9rem;
  box-sizing: border-box;
}

.search-input:focus {
  outline: none;
  border-color: #3b82f6;
  background: #fff;
}

.groups-list {
  flex: 1;
  overflow-y: auto;
}

.loading-state, .empty-state {
  padding: 2rem;
  text-align: center;
  color: #6b7280;
  font-size: 0.9rem;
}

.group-item {
  display: flex;
  padding: 1rem;
  cursor: pointer;
  border-bottom: 1px solid #f5f7fb;
  transition: all 0.2s;
}

.group-item:hover {
  background: #f8f9fa;
}

.group-item.active {
  background: #eff6ff;
  border-left: 4px solid #3b82f6;
}

.group-avatar {
  width: 45px;
  height: 45px;
  margin-right: 1rem;
  flex-shrink: 0;
}

.group-avatar img {
  width: 100%;
  height: 100%;
  border-radius: 50%;
  object-fit: cover;
}

.avatar-placeholder {
  width: 100%;
  height: 100%;
  background: linear-gradient(135deg, #60a5fa, #3b82f6);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 1.2rem;
}

.group-info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.group-name-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.3rem;
}

.group-name {
  font-weight: 600;
  color: #1f2937;
  font-size: 0.95rem;
}

.unread-badge {
  background: #ef4444;
  color: white;
  padding: 0.1rem 0.4rem;
  border-radius: 99px;
  font-size: 0.7rem;
  font-weight: 700;
  min-width: 18px;
  text-align: center;
}

.last-message {
  margin: 0;
  font-size: 0.85rem;
  color: #6b7280;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* Chat Area Logic */
.chat-area {
  flex: 1;
  display: flex;
  flex-direction: column;
  position: relative;
  overflow: hidden;
  background: white;
  height: 100%;
}

.no-chat-selected {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8f9fa;
  color: #6b7280;
}

.empty-chat-placeholder {
  text-align: center;
  padding: 1rem;
}

/* 
  VITAL FIX: Ensure chat-content is a column flex 
  and explicitly consumes 100% height to force children layout 
*/
.chat-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  height: 100%;
  min-height: 0;
  overflow: hidden;
}

.chat-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #e1e4e8;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: white;
  z-index: 10;
  flex-shrink: 0; /* Never shrink header */
}

.chat-header-left {
  display: flex;
  align-items: center;
  gap: 10px;
}

.btn-back {
  display: none;
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #3b82f6;
  cursor: pointer;
  padding: 0 0.5rem 0 0;
}

.chat-header-info h3 {
  margin: 0 0 0.2rem 0;
  font-size: 1.1rem;
  color: #1f2937;
}

.chat-header-info p {
  margin: 0;
  font-size: 0.85rem;
  color: #6b7280;
}

.btn-icon {
  background: transparent;
  border: none;
  font-size: 1.2rem;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 50%;
  margin-left: 0.5rem;
}

.btn-icon:hover {
  background: #f3f4f6;
}

/* 
  VITAL FIX: Messages Area 
  Must use min-height: 0 to allow scrolling inside flex container
*/
.messages-container {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  background: #fff;
  scroll-behavior: smooth;
  min-height: 0; 
}

.loading {
  text-align: center;
  color: #6b7280;
  padding: 1rem;
}

.empty-messages {
  text-align: center;
  margin-top: 2rem;
  color: #9ca3af;
  font-style: italic;
}

.message {
  display: flex;
  gap: 0.75rem;
  max-width: 80%;
}

.message-own {
  align-self: flex-end;
  flex-direction: row-reverse;
}

.message-avatar {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #e5e7eb;
  color: #4b5563;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 600;
  flex-shrink: 0;
}

.message-content {
  display: flex;
  flex-direction: column;
}

.message-sender {
  font-size: 0.75rem;
  color: #6b7280;
  margin-bottom: 0.2rem;
  margin-left: 0.5rem;
}

.message-bubble {
  background: #f3f4f6;
  padding: 0.75rem 1rem;
  border-radius: 12px;
  border-top-left-radius: 2px;
  color: #1f2937;
  position: relative;
  line-height: 1.4;
  word-wrap: break-word;
}

.message-own .message-bubble {
  background: #3b82f6;
  color: white;
  border-top-left-radius: 12px;
  border-top-right-radius: 2px;
}

.message-bubble p {
  margin: 0;
}

.attachment-image {
  max-width: 100%;
  border-radius: 8px;
  margin-bottom: 0.5rem;
}

.message-attachment {
  margin-top: 0.5rem;
  padding: 0.5rem;
  background: rgba(255,255,255,0.2);
  border-radius: 4px;
}

.message-attachment a {
  color: inherit;
  text-decoration: none;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.message-time {
  display: block;
  font-size: 0.65rem;
  margin-top: 0.4rem;
  opacity: 0.7;
  text-align: right;
}

/* 
  VITAL FIX: Input Area 
  Remove sticky positioning. Let it sit naturally at the bottom 
  of the flex column.
*/
.message-input-area {
  padding: 1rem 1.5rem;
  background: white;
  border-top: 1px solid #e1e4e8;
  z-index: 20;
  flex-shrink: 0; /* Prevent collapsing */
  width: 100%;
  box-sizing: border-box;
}

.selected-file-preview {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f0f9ff;
  padding: 0.5rem 1rem;
  border-radius: 6px;
  margin-bottom: 0.75rem;
  border: 1px solid #bae6fd;
}

.btn-remove-file {
  background: none;
  border: none;
  color: #ef4444;
  cursor: pointer;
  font-weight: bold;
}

.message-form {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.btn-attach {
  background: #f3f4f6;
  border: 1px solid #e5e7eb;
  color: #6b7280;
  width: 40px;
  height: 40px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 1.2rem;
  transition: all 0.2s;
  flex-shrink: 0;
}

.btn-attach:hover {
  background: #e5e7eb;
}

.message-input {
  flex: 1;
  height: 40px;
  padding: 0 1rem;
  border: 1px solid #d1d5db;
  border-radius: 20px;
  font-size: 0.95rem;
  transition: border-color 0.2s;
}

.message-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

.btn-send {
  background: #3b82f6;
  color: white;
  border: none;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 1rem;
  box-shadow: 0 2px 5px rgba(59, 130, 246, 0.3);
  transition: all 0.2s;
  flex-shrink: 0;
}

.btn-send:hover:not(:disabled) {
  background: #2563eb;
  transform: translateY(-1px);
}

.btn-send:disabled {
  background: #e5e7eb;
  color: #9ca3af;
  cursor: not-allowed;
  box-shadow: none;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  backdrop-filter: blur(2px);
}

.modal {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  max-height: 85vh;
  display: flex;
  flex-direction: column;
  box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

.modal-header {
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #f0f0f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.2rem;
  cursor: pointer;
  color: #9ca3af;
}

.modal-body {
  padding: 1.5rem;
  overflow-y: auto;
}

.form-group {
  margin-bottom: 1.25rem;
}

.form-group label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  font-size: 0.9rem;
  color: #374151;
}

.required {
  color: #ef4444;
}

.form-group input, .form-group select {
  width: 100%;
  padding: 0.6rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 0.95rem;
  box-sizing: border-box;
}

.detail-value {
  padding: 0.6rem;
  background: #f9fafb;
  border-radius: 6px;
  margin: 0;
  font-size: 0.95rem;
  color: #374151;
}

.user-list {
  margin-top: 0.5rem;
  max-height: 180px;
  overflow-y: auto;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
}

.user-item {
  padding: 0.75rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  border-bottom: 1px solid #f9fafb;
}

.user-item:hover:not(.disabled) {
  background: #f9fafb;
}

.user-item.selected {
  background: #eff6ff;
}

.user-item.disabled {
  opacity: 0.6;
  cursor: not-allowed;
  background: #f9fafb;
}

.user-item-info {
  display: flex;
  flex-direction: column;
  flex: 1;
}

.user-name {
  font-weight: 500;
  font-size: 0.9rem;
}

.user-email {
  font-size: 0.8rem;
  color: #6b7280;
}

.existing-member-badge {
  font-size: 0.7rem;
  color: #059669;
  background: #d1fae5;
  padding: 0.1rem 0.4rem;
  border-radius: 4px;
  margin-top: 0.2rem;
  display: inline-block;
}

.selection-summary {
  margin-top: 0.5rem;
  padding: 0.5rem;
  background: #f0f9ff;
  border-radius: 4px;
  font-size: 0.85rem;
  color: #0369a1;
  text-align: center;
}

.members-list {
  margin-top: 0.5rem;
  max-height: 200px;
  overflow-y: auto;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
}

.member-item {
  padding: 0.75rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  border-bottom: 1px solid #f9fafb;
}

.member-item:last-child {
  border-bottom: none;
}

.member-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #e5e7eb;
  color: #4b5563;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  font-weight: 600;
  flex-shrink: 0;
}

.member-info {
  display: flex;
  flex-direction: column;
}

.member-name {
  font-weight: 500;
  font-size: 0.9rem;
}

.member-email {
  font-size: 0.8rem;
  color: #6b7280;
}

.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #f0f0f0;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.btn-cancel, .btn-primary {
  padding: 0.6rem 1.2rem;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.9rem;
  font-weight: 500;
}

.btn-cancel {
  border: 1px solid #d1d5db;
  background: white;
  color: #374151;
}

.btn-primary {
  background: #3b82f6;
  color: white;
  border: none;
}

.btn-primary:disabled {
  background: #93c5fd;
  cursor: not-allowed;
}

/* ========================================= */
/* MOBILE RESPONSIVE STYLES                  */
/* ========================================= */
@media (max-width: 768px) {
  .chat-container {
    flex-direction: column;
    overflow: hidden;
  }

  .chat-sidebar, .chat-area {
    width: 100%;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
    background: white;
  }

  .chat-sidebar {
    z-index: 10;
    display: flex;
    transform: translateX(0);
  }
  
  .chat-sidebar.mobile-hidden {
    transform: translateX(-100%);
    pointer-events: none;
  }

  .chat-area {
    z-index: 20;
    transform: translateX(100%);
    transition: transform 0.3s ease;
  }
  
  .chat-area.mobile-visible {
    transform: translateX(0);
  }
  
  .btn-back.mobile-only {
    display: block;
  }

  .no-chat-selected {
    display: none;
  }

  .chat-header {
    padding: 0.75rem 1rem;
  }

  .sidebar-header {
    padding: 1rem;
  }

  .desktop-only {
    display: none;
  }
  
  .message-input-area {
    padding: 0.75rem;
  }

  .modal {
    width: 95%;
    max-height: 90vh;
  }
  
  .message {
    max-width: 90%;
  }
}
</style>