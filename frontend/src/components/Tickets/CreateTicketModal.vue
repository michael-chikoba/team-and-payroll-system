<template>
  <teleport to="body">
    <TransitionRoot appear :show="show" as="template">
      <Dialog as="div" class="relative z-[100]" @close="$emit('close')">
        <TransitionChild
          as="template"
          enter="ease-out duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="ease-in duration-200"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-gradient-to-br from-slate-900/80 via-slate-900/60 to-slate-900/80 backdrop-blur-xl transition-opacity" />
        </TransitionChild>

        <div class="fixed inset-0 z-10 overflow-y-auto">
          <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <TransitionChild
              as="template"
              enter="ease-out duration-300"
              enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
              enter-to="opacity-100 translate-y-0 sm:scale-100"
              leave="ease-in duration-200"
              leave-from="opacity-100 translate-y-0 sm:scale-100"
              leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
              <DialogPanel class="relative transform overflow-hidden rounded-2xl bg-gradient-to-b from-white to-slate-50 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border border-slate-200/60">
                <!-- Modern Header -->
                <div class="bg-gradient-to-r from-indigo-600 via-indigo-500 to-purple-500 px-8 py-6 relative overflow-hidden">
                  <div class="absolute inset-0 bg-gradient-to-r from-white/10 to-transparent"></div>
                  <div class="relative flex items-center justify-between">
                    <div class="flex items-center gap-4">
                      <div class="bg-white/20 p-3 rounded-xl backdrop-blur-sm shadow-lg">
                        <TicketIcon class="h-7 w-7 text-white" />
                      </div>
                      <div>
                        <DialogTitle class="text-2xl font-bold text-white tracking-tight">
                          Create New Ticket
                        </DialogTitle>
                        <p class="text-sm text-white/90 mt-1">Fill in the details to create a new support ticket</p>
                      </div>
                    </div>
                    <button
                      type="button"
                      @click="$emit('close')"
                      class="text-white/90 hover:text-white transition-all p-2 rounded-xl hover:bg-white/10 backdrop-blur-sm hover:scale-105"
                    >
                      <XMarkIcon class="h-6 w-6" />
                    </button>
                  </div>
                </div>

                <!-- Form Body -->
                <form @submit.prevent="handleSubmit" class="px-8 py-8 space-y-8">
                  <!-- Ticket Type Selection - Modern Cards -->
                  <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-4 flex items-center gap-2">
                      <div class="w-1 h-4 bg-indigo-500 rounded-full"></div>
                      Ticket Type <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-3 gap-4">
                      <button
                        type="button"
                        v-for="type in ticketTypes"
                        :key="type.slug"
                        @click="selectTicketType(type)"
                        :class="[
                          'group flex flex-col items-center justify-center p-5 rounded-xl border-2 transition-all duration-300 transform hover:scale-[1.02]',
                          form.type === type.slug
                            ? 'border-indigo-500 bg-gradient-to-br from-indigo-50 to-white shadow-lg shadow-indigo-100'
                            : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-lg'
                        ]"
                      >
                        <div :class="[
                          'p-3 rounded-xl mb-3 transition-all duration-300 group-hover:scale-110',
                          form.type === type.slug
                            ? 'bg-gradient-to-br from-indigo-500 to-purple-500 shadow-lg'
                            : 'bg-gradient-to-br from-slate-100 to-slate-50 group-hover:from-slate-200'
                        ]">
                          <component
                            :is="getTypeIcon(type.slug)"
                            :class="[
                              'h-6 w-6 transition-all duration-300',
                              form.type === type.slug ? 'text-white' : 'text-slate-600'
                            ]"
                          />
                        </div>
                        <span class="text-sm font-semibold text-slate-900 mb-1">{{ type.name }}</span>
                        <span class="text-xs text-slate-500 text-center leading-relaxed">{{ type.description }}</span>
                      </button>
                    </div>
                    <p v-if="errors.type" class="mt-3 text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ errors.type }}</p>
                  </div>

                  <!-- Title - Modern Input -->
                  <div>
                    <label for="title" class="block text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                      <div class="w-1 h-4 bg-indigo-500 rounded-full"></div>
                      Ticket Title <span class="text-red-500">*</span>
                    </label>
                    <input
                      id="title"
                      v-model="form.title"
                      type="text"
                      required
                      :placeholder="titlePlaceholder"
                      class="block w-full px-5 py-3.5 border border-slate-200 rounded-xl shadow-sm placeholder-slate-400 focus:outline-none focus:ring-3 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200 bg-white"
                      :class="{ 'border-red-300 focus:ring-red-500/20 focus:border-red-500': errors.title }"
                    />
                    <p v-if="errors.title" class="mt-2 text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ errors.title }}</p>
                  </div>

                  <!-- Description - Modern Textarea -->
                  <div>
                    <label for="description" class="block text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                      <div class="w-1 h-4 bg-indigo-500 rounded-full"></div>
                      Description <span class="text-red-500">*</span>
                    </label>
                    <textarea
                      id="description"
                      v-model="form.description"
                      required
                      rows="4"
                      :placeholder="descriptionPlaceholder"
                      class="block w-full px-5 py-3.5 border border-slate-200 rounded-xl shadow-sm placeholder-slate-400 focus:outline-none focus:ring-3 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200 resize-none bg-white"
                      :class="{ 'border-red-300 focus:ring-red-500/20 focus:border-red-500': errors.description }"
                    ></textarea>
                    <p v-if="errors.description" class="mt-2 text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ errors.description }}</p>
                  </div>

                  <!-- Category and Subcategory Row - Modern Cards -->
                  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Category -->
                    <div class="bg-gradient-to-br from-white to-slate-50 p-5 rounded-xl border border-slate-100 shadow-sm">
                      <label for="category" class="block text-sm font-semibold text-slate-700 mb-3">
                        Category <span class="text-red-500">*</span>
                      </label>
                      <select
                        id="category"
                        v-model="form.category"
                        required
                        class="block w-full px-4 py-3 border border-slate-200 rounded-lg shadow-sm focus:outline-none focus:ring-3 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200 bg-white"
                        :class="{ 'border-red-300 focus:ring-red-500/20 focus:border-red-500': errors.category }"
                      >
                        <option value="" class="text-slate-400">Select Category</option>
                        <option
                          v-for="category in selectedTypeCategories"
                          :key="category"
                          :value="category"
                          class="text-slate-700"
                        >
                          {{ category }}
                        </option>
                      </select>
                      <p v-if="errors.category" class="mt-2 text-sm text-red-600">{{ errors.category }}</p>
                    </div>

                    <!-- Subcategory -->
                    <div class="bg-gradient-to-br from-white to-slate-50 p-5 rounded-xl border border-slate-100 shadow-sm">
                      <label for="subcategory" class="block text-sm font-semibold text-slate-700 mb-3">
                        Subcategory
                      </label>
                      <select
                        id="subcategory"
                        v-model="form.subcategory"
                        class="block w-full px-4 py-3 border border-slate-200 rounded-lg shadow-sm focus:outline-none focus:ring-3 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200 bg-white"
                        :class="{ 'border-red-300 focus:ring-red-500/20 focus:border-red-500': errors.subcategory }"
                      >
                        <option value="" class="text-slate-400">Select Subcategory (Optional)</option>
                        <option
                          v-for="subcategory in selectedTypeSubcategories"
                          :key="subcategory"
                          :value="subcategory"
                          class="text-slate-700"
                        >
                          {{ subcategory }}
                        </option>
                      </select>
                      <p v-if="errors.subcategory" class="mt-2 text-sm text-red-600">{{ errors.subcategory }}</p>
                    </div>
                  </div>

                  <!-- Priority, Department, and Estimated Hours - Modern Cards -->
                  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Priority -->
                    <div class="bg-gradient-to-br from-white to-slate-50 p-5 rounded-xl border border-slate-100 shadow-sm">
                      <label for="priority" class="block text-sm font-semibold text-slate-700 mb-3">
                        Priority <span class="text-red-500">*</span>
                      </label>
                      <select
                        id="priority"
                        v-model="form.priority"
                        required
                        class="block w-full px-4 py-3 border border-slate-200 rounded-lg shadow-sm focus:outline-none focus:ring-3 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200 bg-white"
                        :class="{ 'border-red-300 focus:ring-red-500/20 focus:border-red-500': errors.priority }"
                      >
                        <option value="" class="text-slate-400">Select Priority</option>
                        <option value="low" class="text-emerald-600">Low</option>
                        <option value="medium" class="text-amber-600">Medium</option>
                        <option value="high" class="text-orange-600">High</option>
                        <option value="critical" class="text-red-600">Critical</option>
                      </select>
                      <p v-if="errors.priority" class="mt-2 text-sm text-red-600">{{ errors.priority }}</p>
                    </div>

                    <!-- Department -->
                    <div class="bg-gradient-to-br from-white to-slate-50 p-5 rounded-xl border border-slate-100 shadow-sm">
                      <label for="department_id" class="block text-sm font-semibold text-slate-700 mb-3">
                        Department
                      </label>
                      <select
                        id="department_id"
                        v-model="form.department_id"
                        class="block w-full px-4 py-3 border border-slate-200 rounded-lg shadow-sm focus:outline-none focus:ring-3 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200 bg-white"
                        :class="{ 'border-red-300 focus:ring-red-500/20 focus:border-red-500': errors.department_id }"
                      >
                        <option value="" class="text-slate-400">Select Department</option>
                        <option
                          v-for="dept in departments"
                          :key="dept.id"
                          :value="dept.id"
                          class="text-slate-700"
                        >
                          {{ dept.name }}
                        </option>
                      </select>
                      <p v-if="errors.department_id" class="mt-2 text-sm text-red-600">{{ errors.department_id }}</p>
                    </div>

                    <!-- Estimated Hours -->
                    <div class="bg-gradient-to-br from-white to-slate-50 p-5 rounded-xl border border-slate-100 shadow-sm">
                      <label for="estimated_hours" class="block text-sm font-semibold text-slate-700 mb-3">
                        Estimated Hours
                      </label>
                      <div class="relative">
                        <input
                          id="estimated_hours"
                          v-model.number="form.estimated_hours"
                          type="number"
                          min="0"
                          max="1000"
                          step="0.5"
                          placeholder="e.g., 2.5"
                          class="block w-full px-4 py-3 pl-10 border border-slate-200 rounded-lg shadow-sm placeholder-slate-400 focus:outline-none focus:ring-3 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200 bg-white"
                          :class="{ 'border-red-300 focus:ring-red-500/20 focus:border-red-500': errors.estimated_hours }"
                        />
                        <ClockIcon class="absolute left-3 top-3.5 h-5 w-5 text-slate-400" />
                      </div>
                      <p v-if="errors.estimated_hours" class="mt-2 text-sm text-red-600">{{ errors.estimated_hours }}</p>
                    </div>
                  </div>

                  <!-- Due Date and Assignees Row - Modern Cards -->
                  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Due Date -->
                    <div class="bg-gradient-to-br from-white to-slate-50 p-5 rounded-xl border border-slate-100 shadow-sm">
                      <label for="due_date" class="block text-sm font-semibold text-slate-700 mb-3">
                        Due Date <span class="text-red-500">*</span>
                      </label>
                      <div class="relative">
                        <CalendarDaysIcon class="absolute left-3 top-3.5 h-5 w-5 text-slate-400" />
                        <input
                          id="due_date"
                          v-model="form.due_date"
                          type="date"
                          required
                          :min="minDate"
                          class="block w-full px-4 py-3 pl-10 border border-slate-200 rounded-lg shadow-sm focus:outline-none focus:ring-3 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200 bg-white"
                          :class="{ 'border-red-300 focus:ring-red-500/20 focus:border-red-500': errors.due_date }"
                        />
                      </div>
                      <p v-if="errors.due_date" class="mt-2 text-sm text-red-500 bg-red-50 px-3 py-2 rounded-lg">{{ errors.due_date }}</p>
                    </div>

                    <!-- Quick Assign (Self) -->
                    <div class="bg-gradient-to-br from-white to-slate-50 p-5 rounded-xl border border-slate-100 shadow-sm">
                      <label class="block text-sm font-semibold text-slate-700 mb-3">
                        Quick Assign
                      </label>
                      <button
                        type="button"
                        @click="assignToSelf"
                        :disabled="isSelfAssigned"
                        :class="[
                          'w-full flex items-center justify-center px-4 py-3 rounded-lg font-medium text-sm transition-all',
                          isSelfAssigned
                            ? 'bg-green-100 text-green-800 border-2 border-green-300 cursor-not-allowed'
                            : 'bg-indigo-50 text-indigo-700 border-2 border-indigo-200 hover:bg-indigo-100 hover:border-indigo-300'
                        ]"
                      >
                        <UserCircleIcon class="h-5 w-5 mr-2" />
                        {{ isSelfAssigned ? 'Assigned to You' : 'Assign to Me' }}
                      </button>
                      <p class="mt-2 text-xs text-slate-500">
                        Quickly assign this ticket to yourself
                      </p>
                    </div>
                  </div>

                  <!-- 🔥 ENHANCED: Cross-Business Assignment Section -->
                  <div class="bg-gradient-to-br from-white to-slate-50 p-6 rounded-xl border border-slate-100 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                      <label class="block text-sm font-semibold text-slate-700 flex items-center gap-2">
                        <UserGroupIcon class="h-5 w-5 text-indigo-500" />
                        Assign Team Members
                      </label>

                      <!-- Cross-Business Indicator -->
                      <div v-if="canAssignCrossBusiness" class="flex items-center gap-2 px-3 py-1 bg-purple-100 rounded-full">
                        <div class="h-2 w-2 rounded-full bg-purple-500 animate-pulse"></div>
                        <span class="text-xs font-medium text-purple-700">Cross-business enabled</span>
                      </div>
                    </div>

                    <!-- Current Business Users -->
                    <div v-if="availableUsers.length > 0" class="mb-4">
                      <div class="flex items-center gap-2 mb-2">
                        <div class="h-1 w-8 bg-indigo-500 rounded-full"></div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Your Business Team</p>
                      </div>
                      <select
                        id="assigned_to"
                        v-model="form.assigned_to"
                        multiple
                        size="4"
                        class="block w-full px-4 py-3 border-2 border-indigo-200 rounded-lg shadow-sm focus:outline-none focus:ring-3 focus:ring-indigo-500/30 focus:border-indigo-400 transition-all duration-200 bg-white"
                        :disabled="loadingUsers"
                      >
                        <option
                          v-for="user in availableUsers"
                          :key="user.id"
                          :value="user.id"
                          class="py-2 hover:bg-indigo-50"
                        >
                          {{ user.name }} - {{ user.position || 'Employee' }} ({{ user.email }})
                        </option>
                      </select>
                    </div>

                    <!-- 🔥 NEW: Cross-Business Users (if available) -->
                    <div v-if="groupUsers.length > 0" class="mb-4">
                      <div class="flex items-center gap-2 mb-2">
                        <div class="h-1 w-8 bg-purple-500 rounded-full"></div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-purple-600 flex items-center gap-1">
                          <UserGroupIcon class="h-4 w-4" />
                          Business Group Members
                        </p>
                      </div>
                      <select
                        v-model="form.assigned_to"
                        multiple
                        size="4"
                        class="block w-full px-4 py-3 border-2 border-purple-200 rounded-lg shadow-sm focus:outline-none focus:ring-3 focus:ring-purple-500/30 focus:border-purple-400 transition-all duration-200 bg-gradient-to-br from-purple-50 to-white"
                        :disabled="loadingUsers"
                      >
                        <option
                          v-for="user in groupUsers"
                          :key="user.id"
                          :value="user.id"
                          class="py-2 hover:bg-purple-100"
                        >
                          {{ user.name }} - {{ user.position || 'Employee' }} ({{ user.business_name }})
                        </option>
                      </select>
                      <div class="mt-2 flex items-start gap-2 p-3 bg-purple-50 border border-purple-100 rounded-lg">
                        <InformationCircleIcon class="h-4 w-4 text-purple-500 flex-shrink-0 mt-0.5" />
                        <p class="text-xs text-purple-700">
                          <span class="font-semibold">Cross-business assignment:</span> These users are from other businesses in your business group. They will receive notifications when assigned.
                        </p>
                      </div>
                    </div>

                    <!-- Loading state -->
                    <div v-if="loadingUsers" class="flex items-center gap-3 px-4 py-3 bg-slate-50 rounded-lg border border-slate-200">
                      <div class="animate-spin rounded-full h-5 w-5 border-2 border-indigo-500 border-t-transparent"></div>
                      <span class="text-sm text-slate-600">Loading team members...</span>
                    </div>

                    <!-- 🔥 ENHANCED: Selected assignees as chips with business indicators -->
                    <div v-if="selectedAssignees.length > 0" class="mt-4">
                      <p class="text-sm font-medium text-slate-700 mb-3">Selected Assignees ({{ selectedAssignees.length }}):</p>
                      <div class="flex flex-wrap gap-2">
                        <div
                          v-for="user in selectedAssignees"
                          :key="user.id"
                          :class="[
                            'group inline-flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium border transition-all hover:shadow-md',
                            user.is_from_other_business
                              ? 'bg-gradient-to-r from-purple-50 to-purple-100 text-purple-800 border-purple-300 hover:from-purple-100 hover:to-purple-200'
                              : 'bg-gradient-to-r from-indigo-50 to-indigo-100 text-indigo-800 border-indigo-300 hover:from-indigo-100 hover:to-indigo-200'
                          ]"
                        >
                          <div :class="[
                            'h-6 w-6 rounded-full flex items-center justify-center text-xs font-bold',
                            user.is_from_other_business ? 'bg-purple-200 text-purple-700' : 'bg-indigo-200 text-indigo-700'
                          ]">
                            {{ getUserInitials(user) }}
                          </div>
                          <div class="flex flex-col items-start">
                            <span class="leading-none">{{ user.name }}</span>
                            <span v-if="user.is_from_other_business" class="text-xs mt-0.5 px-1.5 py-0.5 bg-purple-200 rounded leading-none">
                              {{ user.business_name }}
                            </span>
                          </div>
                          <button
                            type="button"
                            @click="removeAssignee(user.id)"
                            :class="[
                              'ml-1 hover:scale-110 transition-transform',
                              user.is_from_other_business ? 'text-purple-600 hover:text-purple-900' : 'text-indigo-600 hover:text-indigo-900'
                            ]"
                          >
                            <XMarkIcon class="h-4 w-4" />
                          </button>
                        </div>
                      </div>
                    </div>

                    <div class="mt-4 flex items-start gap-2 p-3 bg-slate-50 border border-slate-200 rounded-lg">
                      <InformationCircleIcon class="h-4 w-4 text-slate-500 flex-shrink-0 mt-0.5" />
                      <p class="text-xs text-slate-600">
                        Hold <kbd class="px-1.5 py-0.5 bg-white border border-slate-300 rounded text-xs">Ctrl</kbd> (or <kbd class="px-1.5 py-0.5 bg-white border border-slate-300 rounded text-xs">Cmd</kbd> on Mac) to select multiple team members
                      </p>
                    </div>

                    <p v-if="errors.assigned_to" class="mt-3 text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ errors.assigned_to }}</p>
                  </div>

                  <!-- APPROVER SECTION -->
                  <div v-if="showApproverField" class="bg-gradient-to-br from-white to-slate-50 p-5 rounded-xl border border-slate-100 shadow-sm">
                    <label for="approver_id" class="block text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                      <ShieldCheckIcon class="h-5 w-5 text-indigo-500" />
                      Assign to Approver <span class="text-red-500">*</span>
                    </label>

                    <!-- Loading State -->
                    <div v-if="loadingApprovers && internalApprovers.length === 0" class="flex items-center gap-3 px-4 py-3 border border-slate-200 rounded-lg bg-slate-50">
                      <div class="animate-spin rounded-full h-5 w-5 border-2 border-indigo-500 border-t-transparent"></div>
                      <span class="text-sm text-slate-600">Loading approvers...</span>
                    </div>

                    <!-- No Approvers -->
                    <div v-else-if="!loadingApprovers && internalApprovers.length === 0" class="px-4 py-3 border border-amber-200 rounded-lg bg-gradient-to-r from-amber-50 to-amber-100">
                      <p class="text-sm text-amber-800 flex items-center gap-2">
                        <ExclamationTriangleIcon class="h-4 w-4" />
                        No approvers available. Please contact your administrator.
                      </p>
                    </div>

                    <!-- Approvers List -->
                    <select
                      v-else
                      id="approver_id"
                      v-model="form.approver_id"
                      required
                      class="block w-full px-4 py-3 border border-slate-200 rounded-lg shadow-sm focus:outline-none focus:ring-3 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200 bg-white"
                      :class="{ 'border-red-300 focus:ring-red-500/20 focus:border-red-500': errors.approver_id }"
                    >
                      <option value="" class="text-slate-400">Select Approver</option>
                      <option
                        v-for="approver in internalApprovers"
                        :key="approver.id"
                        :value="approver.id"
                        class="text-slate-700"
                      >
                        {{ approver.name }} ({{ approver.email }})
                      </option>
                    </select>
                    <p v-if="errors.approver_id" class="mt-2 text-sm text-red-500 bg-red-50 px-3 py-2 rounded-lg">{{ errors.approver_id }}</p>
                  </div>

                  <!-- Attachments - Modern Upload Area -->
                  <div class="bg-gradient-to-br from-white to-slate-50 p-5 rounded-xl border border-slate-100 shadow-sm">
                    <label class="block text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
                      <PaperClipIcon class="h-5 w-5 text-slate-500" />
                      Attachments
                    </label>
                    <div class="mt-2 flex justify-center px-6 pt-8 pb-8 border-2 border-dashed rounded-xl transition-all duration-300 hover:border-indigo-400 hover:bg-indigo-50/50 group"
                         :class="errors.attachments ? 'border-red-300 bg-red-50/50' : 'border-slate-300'">
                      <div class="space-y-4 text-center">
                        <DocumentArrowUpIcon class="mx-auto h-12 w-12 text-slate-400 group-hover:text-indigo-400 transition-colors" />
                        <div class="flex flex-col sm:flex-row items-center justify-center gap-2 text-sm text-slate-600">
                          <label for="file-upload" class="relative cursor-pointer rounded-lg font-semibold text-indigo-600 hover:text-indigo-500 px-4 py-2 border border-indigo-200 hover:border-indigo-300 bg-white hover:bg-indigo-50 transition-all duration-200">
                            <span>Browse files</span>
                            <input
                              id="file-upload"
                              type="file"
                              multiple
                              @change="handleFileUpload"
                              class="sr-only"
                            />
                          </label>
                          <p class="text-slate-500">or drag and drop here</p>
                        </div>
                        <p class="text-xs text-slate-400">
                          PNG, JPG, PDF up to 10MB each
                        </p>
                      </div>
                    </div>

                    <!-- File list preview -->
                    <div v-if="form.attachments && form.attachments.length > 0" class="mt-6 space-y-3">
                      <p class="text-sm font-medium text-slate-700 mb-2">Selected files:</p>
                      <div v-for="(file, index) in form.attachments" :key="index"
                           class="flex items-center justify-between p-3 bg-white rounded-lg border border-slate-200 hover:border-slate-300 transition-colors group">
                        <div class="flex items-center gap-3">
                          <div class="p-2 bg-slate-100 rounded-lg">
                            <DocumentIcon class="h-5 w-5 text-slate-500" />
                          </div>
                          <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-slate-900 truncate">{{ file.name }}</p>
                            <p class="text-xs text-slate-500">{{ formatFileSize(file.size) }}</p>
                          </div>
                        </div>
                        <button
                          type="button"
                          @click="removeAttachment(index)"
                          class="text-slate-400 hover:text-red-500 p-1 rounded-lg hover:bg-red-50 transition-colors"
                        >
                          <TrashIcon class="h-5 w-5" />
                        </button>
                      </div>
                    </div>
                    <p v-if="errors.attachments" class="mt-3 text-sm text-red-600 bg-red-50 px-3 py-2 rounded-lg">{{ errors.attachments }}</p>
                  </div>

                  <!-- SLA Information - Modern Card -->
                  <div v-if="selectedTicketType" class="bg-gradient-to-r from-indigo-50 to-purple-50 p-5 rounded-xl border border-indigo-100">
                    <div class="flex items-center gap-3">
                      <div class="p-2 bg-gradient-to-br from-indigo-500 to-purple-500 rounded-lg">
                        <ClockIcon class="h-5 w-5 text-white" />
                      </div>
                      <span class="text-sm font-semibold text-indigo-900">Service Level Agreement</span>
                    </div>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                      <div class="bg-white/80 p-3 rounded-lg">
                        <p class="text-xs text-slate-500 mb-1">Response Time</p>
                        <p class="text-sm font-bold text-indigo-900">{{ selectedTicketType.sla_hours }} hours</p>
                      </div>
                      <div class="bg-white/80 p-3 rounded-lg">
                        <p class="text-xs text-slate-500 mb-1">Approval Required</p>
                        <p class="text-sm font-bold" :class="selectedTicketType.requires_approval ? 'text-amber-600' : 'text-emerald-600'">
                          {{ selectedTicketType.requires_approval ? 'Yes' : 'No' }}
                        </p>
                      </div>
                    </div>
                  </div>

                  <!-- General Error Message -->
                  <div v-if="errors.general" class="rounded-xl bg-gradient-to-r from-red-50 to-red-100 p-4 border border-red-200">
                    <div class="flex items-center gap-3">
                      <div class="flex-shrink-0">
                        <ExclamationTriangleIcon class="h-5 w-5 text-red-500" />
                      </div>
                      <div>
                        <p class="text-sm font-medium text-red-800">{{ errors.general }}</p>
                      </div>
                    </div>
                  </div>

                  <!-- Action Buttons - Modern -->
                  <div class="flex items-center justify-end gap-4 pt-6 border-t border-slate-200/60">
                    <button
                      type="button"
                      @click="$emit('close')"
                      class="px-6 py-3 border border-slate-300 rounded-xl text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-3 focus:ring-slate-500/20 transition-all duration-200 hover:scale-[1.02] disabled:opacity-50"
                      :disabled="submitting"
                    >
                      Cancel
                    </button>
                    <button
                      type="submit"
                      class="px-6 py-3 border border-transparent rounded-xl text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-3 focus:ring-indigo-500/30 shadow-lg hover:shadow-xl hover:scale-[1.02] transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                      :disabled="submitting || (loadingApprovers && internalApprovers.length === 0 && showApproverField)"
                    >
                      <span v-if="!submitting" class="flex items-center gap-2">
                        <PlusIcon class="h-4 w-4" />
                        Create Ticket
                      </span>
                      <span v-else class="flex items-center gap-2">
                        <div class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></div>
                        Creating...
                      </span>
                    </button>
                  </div>
                </form>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import {
  XMarkIcon,
  TicketIcon,
  ExclamationTriangleIcon,
  DocumentArrowUpIcon,
  DocumentIcon,
  TrashIcon,
  ClockIcon,
  ExclamationCircleIcon,
  DocumentTextIcon,
  AdjustmentsHorizontalIcon,
  UserCircleIcon,
  PlusIcon,
  PaperClipIcon,
  CalendarDaysIcon,
  InformationCircleIcon,
  ShieldCheckIcon,
  UserGroupIcon
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  show: {
    type: Boolean,
    required: true
  },
  assignableUsers: {
    type: Array,
    default: () => []
  },
  approvers: {
    type: Array,
    default: () => []
  }
})

// Emits
const emit = defineEmits(['close', 'created'])

// Reactive State
const form = ref({
  type: 'issue',
  title: '',
  description: '',
  category: '',
  subcategory: '',
  department_id: '',
  approver_id: '',
  priority: 'medium',
  due_date: '',
  estimated_hours: null,
  assigned_to: [],
  attachments: []
})

const errors = ref({})
const submitting = ref(false)
const loadingTicketTypes = ref(false)
const loadingDepartments = ref(false)
const loadingUsers = ref(false)
const loadingApprovers = ref(false)

const ticketTypes = ref([])
const departments = ref([])
const users = ref([])
const groupUsers = ref([]) // 🔥 NEW: Users from other businesses in the group
const internalApprovers = ref([])
const canAssignCrossBusiness = ref(false) // 🔥 NEW: Flag for cross-business capability
const currentUserId = ref(null)

// Computed Properties
const minDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

const selectedTicketType = computed(() => {
  return ticketTypes.value.find(type => type.slug === form.value.type)
})

const showApproverField = computed(() => {
  return selectedTicketType.value?.requires_approval || false
})

const selectedTypeCategories = computed(() => {
  return selectedTicketType.value?.categories || []
})

const selectedTypeSubcategories = computed(() => {
  return selectedTicketType.value?.subcategories || []
})

// 🔥 UPDATED: Filter to show only current business users
const availableUsers = computed(() => {
  return users.value.filter(u => !u.is_from_other_business)
})

// 🔥 UPDATED: Combine users from both sources for selection display
const selectedAssignees = computed(() => {
  if (!form.value.assigned_to || form.value.assigned_to.length === 0) return []

  const allUsers = [...users.value, ...groupUsers.value]
  return allUsers.filter(user => form.value.assigned_to.includes(user.id))
})

const isSelfAssigned = computed(() => {
  return currentUserId.value && form.value.assigned_to.includes(currentUserId.value)
})

const titlePlaceholder = computed(() => {
  switch(form.value.type) {
    case 'issue':
      return 'Brief description of the issue or problem'
    case 'request':
      return 'What are you requesting?'
    case 'change_request':
      return 'Describe the change you want to make'
    default:
      return 'Brief description'
  }
})

const descriptionPlaceholder = computed(() => {
  switch(form.value.type) {
    case 'issue':
      return 'Describe the issue in detail, including steps to reproduce, error messages, and impact...'
    case 'request':
      return 'Provide details about your request, why it\'s needed, and any specific requirements...'
    case 'change_request':
      return 'Describe the change, business justification, impact analysis, and implementation plan...'
    default:
      return 'Provide detailed information'
  }
})

// Methods
const getTypeIcon = (type) => {
  switch(type) {
    case 'issue':
      return ExclamationCircleIcon
    case 'request':
      return DocumentTextIcon
    case 'change_request':
      return AdjustmentsHorizontalIcon
    default:
      return TicketIcon
  }
}

const selectTicketType = (type) => {
  form.value.type = type.slug
  form.value.category = ''
  form.value.subcategory = ''
  form.value.approver_id = ''

  if (type.slug) {
    fetchCategories(type.slug)
  }
}

const assignToSelf = () => {
  if (currentUserId.value && !form.value.assigned_to.includes(currentUserId.value)) {
    form.value.assigned_to.push(currentUserId.value)
  }
}

const removeAssignee = (userId) => {
  form.value.assigned_to = form.value.assigned_to.filter(id => id !== userId)
}

const getUserInitials = (user) => {
  if (!user || !user.name) return '??'
  return user.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

// 🔥 UPDATED: Fetch both current business and group users
const fetchUsers = async () => {
  loadingUsers.value = true
  try {
    const response = await axios.get('/api/tickets/assignable-users')

    console.log('Assignable users response:', response.data)

    if (response.data) {
      users.value = response.data.assignable_users || []
      groupUsers.value = response.data.group_users || [] // 🔥 NEW
      canAssignCrossBusiness.value = response.data.can_assign_cross_business || false // 🔥 NEW

      // Also update approvers if included
      if (response.data.approvers) {
        internalApprovers.value = response.data.approvers
      }

      console.log('Loaded users:', {
        currentBusiness: users.value.length,
        groupUsers: groupUsers.value.length,
        canAssignCrossBusiness: canAssignCrossBusiness.value
      })
    }
  } catch (error) {
    console.error('Error fetching users:', error)
    users.value = []
    groupUsers.value = []
  } finally {
    loadingUsers.value = false
  }
}

const fetchApprovers = async () => {
  loadingApprovers.value = true
  try {
    const response = await axios.get('/api/tickets/approvers')

    let rawList = []

    if (Array.isArray(response.data)) {
      rawList = response.data
    } else if (response.data && Array.isArray(response.data.approvers)) {
      rawList = response.data.approvers
    } else if (response.data && Array.isArray(response.data.data)) {
      rawList = response.data.data
    }

    const processedList = rawList
      .filter(item => item && typeof item === 'object')
      .map(approver => {
        const name = approver.name ||
                     `${approver.first_name || ''} ${approver.last_name || ''}`.trim() ||
                     approver.email ||
                     'Unknown User'

        return {
          id: approver.id,
          name: name,
          email: approver.email || '',
          first_name: approver.first_name || '',
          last_name: approver.last_name || '',
          business_id: approver.business_id,
          position: approver.position || 'Admin'
        }
      })
      .filter(approver => approver.id)

    internalApprovers.value = processedList

  } catch (error) {
    console.error('Error fetching approvers:', error)
    internalApprovers.value = []
  } finally {
    loadingApprovers.value = false
  }
}

const fetchTicketTypes = async () => {
  loadingTicketTypes.value = true
  try {
    const response = await axios.get('/api/tickets/types')
    ticketTypes.value = response.data || []

    if (ticketTypes.value.length > 0 && !form.value.type) {
      form.value.type = ticketTypes.value[0].slug
      fetchCategories(ticketTypes.value[0].slug)
    }
  } catch (error) {
    console.error('Error fetching ticket types:', error)
    ticketTypes.value = []
  } finally {
    loadingTicketTypes.value = false
  }
}

const fetchCategories = async (typeSlug) => {
  try {
    const response = await axios.get(`/api/tickets/types/${typeSlug}/categories`)
    if (response.data) {
      const typeIndex = ticketTypes.value.findIndex(t => t.slug === typeSlug)
      if (typeIndex !== -1) {
        ticketTypes.value[typeIndex].categories = response.data.categories || []
        ticketTypes.value[typeIndex].subcategories = response.data.subcategories || []
      }
    }
  } catch (error) {
    console.error('Error fetching categories:', error)
  }
}

const fetchDepartments = async () => {
  loadingDepartments.value = true
  try {
    const response = await axios.get('/api/tickets/departments')
    departments.value = response.data || []
  } catch (error) {
    console.error('Error fetching departments:', error)
    departments.value = []
  } finally {
    loadingDepartments.value = false
  }
}

const getCurrentUser = async () => {
  try {
    const response = await axios.get('/api/user')
    const userData = response.data.user || response.data
    currentUserId.value = userData.id
  } catch (error) {
    console.error('Error fetching current user:', error)
  }
}

const resetForm = () => {
  form.value = {
    type: 'issue',
    title: '',
    description: '',
    category: '',
    subcategory: '',
    department_id: '',
    approver_id: '',
    priority: 'medium',
    due_date: '',
    estimated_hours: null,
    assigned_to: [],
    attachments: []
  }
  errors.value = {}
}

const handleFileUpload = (event) => {
  const files = Array.from(event.target.files)
  const maxSize = 10 * 1024 * 1024 // 10MB
  const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf']

  files.forEach(file => {
    if (file.size > maxSize) {
      errors.value.attachments = `File "${file.name}" exceeds 10MB limit`
      return
    }

    if (!allowedTypes.includes(file.type)) {
      errors.value.attachments = `File "${file.name}" has unsupported format`
      return
    }

    form.value.attachments.push(file)
  })

  event.target.value = ''
}

const removeAttachment = (index) => {
  form.value.attachments.splice(index, 1)
}

const prepareAssignedToArray = (assignedTo) => {
  let assignedToArray = assignedTo;

  if (!assignedToArray) {
    return [];
  }

  if (!Array.isArray(assignedToArray)) {
    assignedToArray = [assignedToArray];
  }

  return assignedToArray
    .map(item => {
      if (typeof item === 'object' && item !== null) {
        return item.id;
      }
      return Number(item);
    })
    .filter(id => !isNaN(id) && id > 0);
}

const prepareFormData = () => {
  const assignedToArray = prepareAssignedToArray(form.value.assigned_to);

  const data = {
    type: form.value.type,
    title: form.value.title,
    description: form.value.description,
    category: form.value.category,
    subcategory: form.value.subcategory || null,
    department_id: form.value.department_id || null,
    approver_id: form.value.approver_id || null,
    priority: form.value.priority,
    due_date: form.value.due_date,
    estimated_hours: form.value.estimated_hours || null,
    assigned_to: assignedToArray
  }

  return data
}

const validateForm = () => {
  errors.value = {}
  let isValid = true

  if (!form.value.type) {
    errors.value.type = 'Ticket type is required'
    isValid = false
  }

  if (!form.value.title.trim()) {
    errors.value.title = 'Title is required'
    isValid = false
  }

  if (!form.value.description.trim()) {
    errors.value.description = 'Description is required'
    isValid = false
  }

  if (!form.value.category) {
    errors.value.category = 'Category is required'
    isValid = false
  }

  if (!form.value.priority) {
    errors.value.priority = 'Priority is required'
    isValid = false
  }

  if (!form.value.due_date) {
    errors.value.due_date = 'Due date is required'
    isValid = false
  }

  if (form.value.due_date) {
    const today = new Date().toISOString().split('T')[0]
    if (form.value.due_date < today) {
      errors.value.due_date = 'Due date cannot be in the past'
      isValid = false
    }
  }

  if (showApproverField.value && !form.value.approver_id) {
    errors.value.approver_id = 'Approver is required for this ticket type'
    isValid = false
  }

  return isValid
}

const handleSubmit = async () => {
  if (!validateForm()) {
    return
  }

  submitting.value = true
  errors.value = {}

  try {
    const data = prepareFormData()

    console.log('Submitting ticket with data:', data)

    if (form.value.attachments.length > 0) {
      const formData = new FormData()

      Object.keys(data).forEach(key => {
        const value = data[key]
        if (key === 'assigned_to' && Array.isArray(value)) {
          if (value.length > 0) {
            value.forEach(item => {
              formData.append('assigned_to[]', item)
            })
          }
        } else {
          formData.append(key, value !== null && value !== undefined ? value : '')
        }
      })

      form.value.attachments.forEach(file => {
        formData.append('attachments[]', file)
      })

      const response = await axios.post('/api/tickets', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })

      if (response.data) {
        resetForm()
        emit('created', response.data)
      }
    } else {
      const response = await axios.post('/api/tickets', data)

      if (response.data) {
        resetForm()
        emit('created', response.data)
      }
    }
  } catch (error) {
    console.error('Error creating ticket:', error)

    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors
    } else if (error.response?.data?.message) {
      errors.value.general = error.response.data.message
    } else {
      errors.value.general = 'Failed to create ticket. Please try again.'
    }
  } finally {
    submitting.value = false
  }
}

// Watchers
watch(() => props.show, (newVal) => {
  if (newVal) {
    getCurrentUser()

    if (props.approvers && props.approvers.length > 0) {
      internalApprovers.value = props.approvers
      loadingApprovers.value = false
    } else {
      fetchApprovers()
    }

    fetchTicketTypes()
    fetchDepartments()
    fetchUsers() // 🔥 This now fetches both current business and group users

    resetForm()
  } else {
    resetForm()
    internalApprovers.value = []
    loadingApprovers.value = false
  }
}, { immediate: true })

watch(() => props.approvers, (newApprovers) => {
  if (newApprovers && newApprovers.length > 0 && props.show) {
    internalApprovers.value = newApprovers
  }
})

onMounted(() => {
  // Initial setup if needed
})
</script>

<style scoped>
/* Custom styles for multiple select */
select[multiple] option:checked {
  background: linear-gradient(to right, #e0e7ff, #f3f4f6);
  color: #4f46e5;
  font-weight: 500;
}

select[multiple] option:hover {
  background: linear-gradient(to right, #f1f5f9, #f8fafc);
}

select[multiple]:focus {
  border-color: #4f46e5;
  box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

/* Smooth transitions */
.transition-all {
  transition-property: all;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
}

/* Custom scrollbar */
select[multiple]::-webkit-scrollbar {
  width: 6px;
}

select[multiple]::-webkit-scrollbar-track {
  background: #f1f5f9;
  border-radius: 4px;
}

select[multiple]::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

select[multiple]::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Keyboard shortcut styling */
kbd {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
}
</style>