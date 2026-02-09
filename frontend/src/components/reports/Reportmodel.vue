<template>
  <transition
    enter-active-class="transition ease-out duration-200"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition ease-in duration-150"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div 
      v-if="show"
      class="fixed inset-0 bg-gray-900 bg-opacity-50 z-50 flex items-center justify-center p-4"
      @click.self="closeModal"
    >
      <transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
      >
        <div 
          v-if="show"
          class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
          @click.stop
        >
          <!-- Header -->
          <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10">
            <div>
              <h2 class="text-2xl font-bold text-gray-900">Submit Report</h2>
              <p class="text-sm text-gray-600 mt-1">{{ schedule?.title }}</p>
            </div>
            <button
              type="button"
              @click="closeModal"
              class="text-gray-400 hover:text-gray-600 focus:outline-none"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

          <!-- Form -->
          <form @submit.prevent="submitReport" class="px-6 py-5 space-y-5">
            
            <!-- Report Type Selection -->
            <div>
              <label class="block text-sm font-bold text-gray-900 mb-2">
                Report Type <span class="text-red-600">*</span>
              </label>
              <div class="flex gap-4">
                <label class="flex items-center">
                  <input
                    type="radio"
                    v-model="formData.report_type"
                    value="text"
                    class="mr-2"
                  />
                  <span class="text-sm">Text Report</span>
                </label>
                <label class="flex items-center">
                  <input
                    type="radio"
                    v-model="formData.report_type"
                    value="file"
                    class="mr-2"
                  />
                  <span class="text-sm">File Upload</span>
                </label>
                <label class="flex items-center">
                  <input
                    type="radio"
                    v-model="formData.report_type"
                    value="both"
                    class="mr-2"
                  />
                  <span class="text-sm">Both</span>
                </label>
              </div>
            </div>

            <!-- Text Report Content -->
            <div v-if="formData.report_type === 'text' || formData.report_type === 'both'">
              <label for="report_content" class="block text-sm font-bold text-gray-900 mb-2">
                Report Summary <span class="text-red-600">*</span>
              </label>
              <textarea
                id="report_content"
                v-model="formData.report_content"
                rows="6"
                :required="formData.report_type === 'text' || formData.report_type === 'both'"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Describe what was accomplished, challenges faced, and any important notes..."
              ></textarea>
            </div>

            <!-- File Upload -->
            <div v-if="formData.report_type === 'file' || formData.report_type === 'both'">
              <label for="report_file" class="block text-sm font-bold text-gray-900 mb-2">
                Upload Report File <span class="text-red-600">*</span>
              </label>
              <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-indigo-400 transition-colors">
                <div class="space-y-1 text-center">
                  <svg
                    class="mx-auto h-12 w-12 text-gray-400"
                    stroke="currentColor"
                    fill="none"
                    viewBox="0 0 48 48"
                    aria-hidden="true"
                  >
                    <path
                      d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                    />
                  </svg>
                  <div class="flex text-sm text-gray-600">
                    <label
                      for="report_file"
                      class="relative cursor-pointer bg-white rounded-md font-medium text-indigo-600 hover:text-indigo-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-indigo-500"
                    >
                      <span>Upload a file</span>
                      <input
                        id="report_file"
                        ref="fileInput"
                        type="file"
                        accept=".pdf,.doc,.docx,.txt"
                        @change="handleFileChange"
                        class="sr-only"
                        :required="formData.report_type === 'file' || formData.report_type === 'both'"
                      />
                    </label>
                    <p class="pl-1">or drag and drop</p>
                  </div>
                  <p class="text-xs text-gray-500">PDF, DOC, DOCX, TXT up to 10MB</p>
                  
                  <!-- Selected File Display -->
                  <div v-if="selectedFile" class="mt-4 p-3 bg-indigo-50 rounded-lg">
                    <div class="flex items-center justify-between">
                      <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                          <p class="text-sm font-medium text-gray-900">{{ selectedFile.name }}</p>
                          <p class="text-xs text-gray-500">{{ formatFileSize(selectedFile.size) }}</p>
                        </div>
                      </div>
                      <button
                        type="button"
                        @click="removeFile"
                        class="text-red-600 hover:text-red-700"
                      >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Metadata Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="hours_worked" class="block text-sm font-bold text-gray-900 mb-2">
                  Hours Worked
                </label>
                <input
                  id="hours_worked"
                  v-model.number="formData.metadata.hours_worked"
                  type="number"
                  step="0.5"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="8.0"
                />
              </div>

              <div>
                <label for="tasks_completed" class="block text-sm font-bold text-gray-900 mb-2">
                  Tasks Completed
                </label>
                <input
                  id="tasks_completed"
                  v-model.number="formData.metadata.tasks_completed"
                  type="number"
                  min="0"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                  placeholder="5"
                />
              </div>
            </div>

            <!-- Challenges -->
            <div>
              <label for="challenges" class="block text-sm font-bold text-gray-900 mb-2">
                Challenges Faced
              </label>
              <textarea
                id="challenges"
                v-model="formData.metadata.challenges"
                rows="3"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Any obstacles or difficulties encountered..."
              ></textarea>
            </div>

            <!-- Achievements -->
            <div>
              <label for="achievements" class="block text-sm font-bold text-gray-900 mb-2">
                Key Achievements
              </label>
              <textarea
                id="achievements"
                v-model="formData.metadata.achievements"
                rows="3"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Notable accomplishments or milestones..."
              ></textarea>
            </div>

            <!-- Footer Actions -->
            <div class="sticky bottom-0 bg-white border-t border-gray-200 -mx-6 px-6 py-4 flex items-center justify-end gap-3 mt-6">
              <button
                type="button"
                @click="closeModal"
                :disabled="submitting"
                class="px-4 py-2 text-sm font-bold text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 disabled:opacity-50"
              >
                Cancel
              </button>
              <button
                type="submit"
                :disabled="submitting || !isFormValid"
                class="px-4 py-2 text-sm font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 inline-flex items-center"
              >
                <svg v-if="submitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ submitting ? 'Submitting...' : 'Submit Report' }}
              </button>
            </div>
          </form>
        </div>
      </transition>
    </div>
  </transition>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
  show: { type: Boolean, default: false },
  schedule: { type: Object, default: null }
});

const emit = defineEmits(['close', 'submitted']);

const submitting = ref(false);
const selectedFile = ref(null);
const fileInput = ref(null);

const formData = ref({
  schedule_id: null,
  report_type: 'text',
  report_content: '',
  metadata: {
    hours_worked: null,
    tasks_completed: null,
    challenges: '',
    achievements: ''
  }
});

const isFormValid = computed(() => {
  if (formData.value.report_type === 'text') {
    return !!formData.value.report_content;
  } else if (formData.value.report_type === 'file') {
    return !!selectedFile.value;
  } else if (formData.value.report_type === 'both') {
    return !!formData.value.report_content && !!selectedFile.value;
  }
  return false;
});

watch(() => props.schedule, (newSchedule) => {
  if (newSchedule) {
    formData.value.schedule_id = newSchedule.id;
  }
}, { immediate: true });

const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    // Validate file size (10MB max)
    if (file.size > 10 * 1024 * 1024) {
      alert('File size must be less than 10MB');
      event.target.value = '';
      return;
    }
    
    // Validate file type
    const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
    if (!allowedTypes.includes(file.type)) {
      alert('Only PDF, DOC, DOCX, and TXT files are allowed');
      event.target.value = '';
      return;
    }
    
    selectedFile.value = file;
  }
};

const removeFile = () => {
  selectedFile.value = null;
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const submitReport = async () => {
  submitting.value = true;
  
  try {
    const formDataToSend = new FormData();
    formDataToSend.append('schedule_id', formData.value.schedule_id);
    formDataToSend.append('report_type', formData.value.report_type);
    
    if (formData.value.report_content) {
      formDataToSend.append('report_content', formData.value.report_content);
    }
    
    if (selectedFile.value) {
      formDataToSend.append('report_file', selectedFile.value);
    }
    
    // Append metadata
    if (formData.value.metadata.hours_worked !== null) {
      formDataToSend.append('metadata[hours_worked]', formData.value.metadata.hours_worked);
    }
    if (formData.value.metadata.tasks_completed !== null) {
      formDataToSend.append('metadata[tasks_completed]', formData.value.metadata.tasks_completed);
    }
    if (formData.value.metadata.challenges) {
      formDataToSend.append('metadata[challenges]', formData.value.metadata.challenges);
    }
    if (formData.value.metadata.achievements) {
      formDataToSend.append('metadata[achievements]', formData.value.metadata.achievements);
    }

    const response = await axios.post('/api/schedule-reports', formDataToSend, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    alert('Report submitted successfully!');
    emit('submitted', response.data.report);
    closeModal();
    
  } catch (error) {
    console.error('Failed to submit report:', error);
    
    if (error.response?.data?.message) {
      alert(error.response.data.message);
    } else {
      alert('Failed to submit report. Please try again.');
    }
  } finally {
    submitting.value = false;
  }
};

const closeModal = () => {
  // Reset form
  formData.value = {
    schedule_id: null,
    report_type: 'text',
    report_content: '',
    metadata: {
      hours_worked: null,
      tasks_completed: null,
      challenges: '',
      achievements: ''
    }
  };
  selectedFile.value = null;
  if (fileInput.value) {
    fileInput.value.value = '';
  }
  emit('close');
};
</script>