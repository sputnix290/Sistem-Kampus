import axios from 'axios';

const API_BASE_URL = '/api'; // Adjust this if your API is on a different origin

const getAuthToken = () => {
    // Placeholder: In a real app, get this from localStorage, context, etc.
    return 'YOUR_AUTH_TOKEN'; 
};

const fetcher = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${getAuthToken()}`
    }
});

const paymentService = {
    getDashboardStats: async () => {
        const response = await fetcher.get('/dashboard/stats');
        return response.data;
    },

    getMahasiswaList: async () => {
        const response = await fetcher.get('/mahasiswa');
        return response.data.data; // Assuming API returns { data: [...] }
    },

    getMahasiswaDetail: async (mahasiswaId) => {
        const response = await fetcher.get(`/dashboard/mahasiswa/${mahasiswaId}`);
        return response.data;
    },

    getPayments: async (mahasiswaId = null) => {
        const url = mahasiswaId ? `/pembayaran/mahasiswa/${mahasiswaId}` : '/pembayaran';
        const response = await fetcher.get(url);
        return response.data.pembayaran.data; // Adjust based on actual API response structure
    },

    addPayment: async (paymentData) => {
        const response = await fetcher.post('/pembayaran', paymentData);
        return response.data;
    },

    updatePaymentStatus: async (paymentId, status) => {
        const response = await fetcher.put(`/pembayaran/${paymentId}/status`, { status });
        return response.data;
    }
};

export default paymentService;
