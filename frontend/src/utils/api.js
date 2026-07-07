import api from './axios';

/**
 * API helper functions for all modules
 */

// Generic CRUD operations
export const apiService = {
  getAll: async (endpoint, params = {}) => {
    const response = await api.get(endpoint, { params });
    return response.data;
  },

  getById: async (endpoint, id) => {
    const response = await api.get(`${endpoint}/${id}`);
    return response.data;
  },

  create: async (endpoint, data) => {
    const response = await api.post(endpoint, data);
    return response.data;
  },

  update: async (endpoint, id, data) => {
    const response = await api.put(`${endpoint}/${id}`, data);
    return response.data;
  },

  delete: async (endpoint, id) => {
    const response = await api.delete(`${endpoint}/${id}`);
    return response.data;
  },
};

// Module-specific API services
export const mahasiswaApi = {
  getAll: (params) => apiService.getAll('/mahasiswa', params),
  getById: (id) => apiService.getById('/mahasiswa', id),
  create: (data) => apiService.create('/mahasiswa', data),
  update: (id, data) => apiService.update('/mahasiswa', id, data),
  delete: (id) => apiService.delete('/mahasiswa', id),
};

export const dosenApi = {
  getAll: (params) => apiService.getAll('/dosen', params),
  getById: (id) => apiService.getById('/dosen', id),
  create: (data) => apiService.create('/dosen', data),
  update: (id, data) => apiService.update('/dosen', id, data),
  delete: (id) => apiService.delete('/dosen', id),
};

export const programStudiApi = {
  getAll: (params) => apiService.getAll('/program-studi', params),
  getById: (id) => apiService.getById('/program-studi', id),
  create: (data) => apiService.create('/program-studi', data),
  update: (id, data) => apiService.update('/program-studi', id, data),
  delete: (id) => apiService.delete('/program-studi', id),
};

export const mataKuliahApi = {
  getAll: (params) => apiService.getAll('/mata-kuliah', params),
  getById: (id) => apiService.getById('/mata-kuliah', id),
  create: (data) => apiService.create('/mata-kuliah', data),
  update: (id, data) => apiService.update('/mata-kuliah', id, data),
  delete: (id) => apiService.delete('/mata-kuliah', id),
};

export const pengumumanApi = {
  getAll: (params) => apiService.getAll('/pengumuman', params),
  getById: (id) => apiService.getById('/pengumuman', id),
  create: (data) => apiService.create('/pengumuman', data),
  update: (id, data) => apiService.update('/pengumuman', id, data),
  delete: (id) => apiService.delete('/pengumuman', id),
};

export const fakultasApi = {
  getAll: () => apiService.getAll('/fakultas'),
  getById: (id) => apiService.getById('/fakultas', id),
  create: (data) => apiService.create('/fakultas', data),
  update: (id, data) => apiService.update('/fakultas', id, data),
  delete: (id) => apiService.delete('/fakultas', id),
};
