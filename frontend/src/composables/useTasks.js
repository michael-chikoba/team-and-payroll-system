// composables/useTasks.js
import { ref } from 'vue';
import axios from 'axios';

export function useTasks() {
  const tasks = ref([]);
  const employees = ref([]);
  const loading = ref(false);
  const error = ref(null);

  const fetchTasks = async () => {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.get('/api/tasks');
      tasks.value = response.data.tasks;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch tasks';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const createTask = async (taskData) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.post('/api/tasks', taskData);
      tasks.value.push(response.data.task);
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to create task';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const updateTask = async (taskId, taskData) => {
    loading.value = true;
    error.value = null;
    try {
      const response = await axios.put(`/api/tasks/${taskId}`, taskData);
      const index = tasks.value.findIndex(t => t.id === taskId);
      if (index !== -1) {
        tasks.value[index] = response.data.task;
      }
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to update task';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const updateTaskStatus = async (taskId, status) => {
    try {
      const response = await axios.patch(`/api/tasks/${taskId}/status`, { status });
      const index = tasks.value.findIndex(t => t.id === taskId);
      if (index !== -1) {
        tasks.value[index] = response.data.task;
      }
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to update status';
      throw err;
    }
  };

  const deleteTask = async (taskId) => {
    loading.value = true;
    error.value = null;
    try {
      await axios.delete(`/api/tasks/${taskId}`);
      tasks.value = tasks.value.filter(t => t.id !== taskId);
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to delete task';
      throw err;
    } finally {
      loading.value = false;
    }
  };

  const fetchEmployees = async () => {
    try {
      const response = await axios.get('/api/employees');
      employees.value = response.data.employees;
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to fetch employees';
      throw err;
    }
  };

  const addComment = async (taskId, comment) => {
    try {
      const response = await axios.post(`/api/tasks/${taskId}/comments`, { comment });
      const task = tasks.value.find(t => t.id === taskId);
      if (task) {
        task.comments = task.comments || [];
        task.comments.unshift(response.data.comment);
      }
      return response.data;
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to add comment';
      throw err;
    }
  };

  const deleteComment = async (commentId, taskId) => {
    try {
      await axios.delete(`/api/comments/${commentId}`);
      const task = tasks.value.find(t => t.id === taskId);
      if (task && task.comments) {
        task.comments = task.comments.filter(c => c.id !== commentId);
      }
    } catch (err) {
      error.value = err.response?.data?.message || 'Failed to delete comment';
      throw err;
    }
  };

  return {
    tasks,
    employees,
    loading,
    error,
    fetchTasks,
    createTask,
    updateTask,
    updateTaskStatus,
    deleteTask,
    fetchEmployees,
    addComment,
    deleteComment,
  };
}