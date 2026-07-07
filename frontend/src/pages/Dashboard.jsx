import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { LayoutDashboard, Users, CreditCard, FileText } from 'lucide-react'; // Icons

// Placeholder components (will be created in separate files later)
const StudentProfileCard = ({ student }) => (
    <div className="bg-yellow-100 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-700 rounded-xl p-6 mb-8">
        <h3 className="text-lg font-bold text-gray-800 dark:text-white mb-4">Profil Mahasiswa</h3>
        {student ? (
            <div className="flex items-center gap-6">
                <div className="w-20 h-20 rounded-full bg-yellow-300 dark:bg-yellow-700 flex items-center justify-center">
                    <span className="text-3xl font-bold text-gray-800 dark:text-white">{student.user.name.split(' ').map(n => n[0]).join('').toUpperCase()}</span>
                </div>
                <div>
                    <p className="text-xl font-semibold text-gray-800 dark:text-white">{student.user.name}</p>
                    <p className="text-gray-600 dark:text-yellow-300">{student.nim}</p>
                    <p className="text-gray-500 dark:text-yellow-400">{student.program_studi.name}</p>
                </div>
            </div>
        ) : (
            <p className="text-gray-600 dark:text-yellow-300">Pilih Mahasiswa untuk melihat profil.</p>
        )}
    </div>
);

const PaymentTable = ({ payments }) => (
    <div className="bg-white dark:bg-slate-800 rounded-xl shadow-lg overflow-hidden">
        <div className="p-6 border-b border-gray-200 dark:border-slate-700">
            <h3 className="text-lg font-bold text-gray-800 dark:text-white">Daftar Pembayaran</h3>
        </div>
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead className="bg-gray-50 dark:bg-slate-700">
                    <tr>
                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-300 uppercase">No</th>
                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-300 uppercase">Mahasiswa</th>
                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-300 uppercase">Jenis</th>
                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-300 uppercase">Jumlah</th>
                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-300 uppercase">Status</th>
                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-300 uppercase">Tanggal</th>
                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-300 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    {payments.length > 0 ? (
                        payments.map((pembayaran, index) => (
                            <tr key={pembayaran.id}>
                                <td className="px-6 py-4 whitespace-nowrap">{index + 1}</td>
                                <td className="px-6 py-4 whitespace-nowrap">{pembayaran.mahasiswa.user.name}</td>
                                <td className="px-6 py-4 whitespace-nowrap">{pembayaran.jenis_pembayaran}</td>
                                <td className="px-6 py-4 whitespace-nowrap">{pembayaran.jumlah}</td>
                                <td className="px-6 py-4 whitespace-nowrap">{pembayaran.status}</td>
                                <td className="px-6 py-4 whitespace-nowrap">{pembayaran.tanggal_bayar ? new Date(pembayaran.tanggal_bayar).toLocaleDateString() : 'N/A'}</td>
                                <td className="px-6 py-4 whitespace-nowrap">
                                    <button className="text-blue-600 hover:underline mr-2">Detail</button>
                                    <button className="text-green-600 hover:underline">Update Status</button>
                                </td>
                            </tr>
                        ))
                    ) : (
                        <tr>
                            <td colSpan="7" className="px-6 py-8 text-center text-gray-500">Tidak ada data pembayaran.</td>
                        </tr>
                    )}
                </tbody>
            </table>
        </div>
    </div>
);

const AddPaymentForm = () => (
    <div className="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
        <h3 className="text-lg font-bold text-gray-800 dark:text-white mb-4">Tambah Pembayaran</h3>
        <form className="space-y-4">
            {/* Form fields */}
            <button type="submit" className="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                Simpan Pembayaran
            </button>
        </form>
    </div>
);


const Dashboard = () => {
    const [activeSection, setActiveSection] = useState('dashboard');
    const [dashboardStats, setDashboardStats] = useState({});
    const [mahasiswaData, setMahasiswaData] = useState([]);
    const [pembayaranData, setPembayaranData] = useState([]);
    const [selectedStudent, setSelectedStudent] = useState(null);
    const [isDarkMode, setIsDarkMode] = useState(false);

    useEffect(() => {
        // Check for dark mode preference
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
            setIsDarkMode(true);
        } else {
            document.documentElement.classList.remove('dark');
            setIsDarkMode(false);
        }

        loadDashboardStats();
    }, []);

    const getAuthToken = () => {
        // Placeholder: In a real app, get this from localStorage, context, etc.
        return 'YOUR_AUTH_TOKEN'; 
    };

    const fetcher = axios.create({
        baseURL: '/api', // Adjust if your API is on a different origin
        headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${getAuthToken()}`
        }
    });

    const loadDashboardStats = async () => {
        try {
            const response = await fetcher.get('/dashboard/stats');
            setDashboardStats(response.data);
        } catch (error) {
            console.error('Error loading dashboard stats:', error);
        }
    };

    const loadMahasiswaData = async () => {
        try {
            const response = await fetcher.get('/mahasiswa');
            setMahasiswaData(response.data.data); // Assuming API returns { data: [...] }
        } catch (error) {
            console.error('Error loading mahasiswa data:', error);
        }
    };

    const loadPembayaranData = async (mahasiswaId = null) => {
        try {
            const url = mahasiswaId ? `/pembayaran/mahasiswa/${mahasiswaId}` : '/pembayaran';
            const response = await fetcher.get(url);
            setPembayaranData(response.data.pembayaran.data); // Adjust based on actual API response structure
        } catch (error) {
            console.error('Error loading payment data:', error);
        }
    };

    const viewMahasiswaDetail = async (mahasiswaId) => {
        try {
            const response = await fetcher.get(`/dashboard/mahasiswa/${mahasiswaId}`);
            setSelectedStudent(response.data.mahasiswa);
            setPembayaranData(response.data.pembayaran); // Assuming API returns payment data with student
            setActiveSection('dashboard'); // Switch to dashboard view to show student profile
        } catch (error) {
            console.error('Error loading mahasiswa detail:', error);
        }
    };

    const toggleDarkMode = () => {
        if (isDarkMode) {
            document.documentElement.classList.remove('dark');
            localStorage.theme = 'light';
            setIsDarkMode(false);
        } else {
            document.documentElement.classList.add('dark');
            localStorage.theme = 'dark';
            setIsDarkMode(true);
        }
    };

    return (
        <div className="flex min-h-screen">
            {/* Sidebar */}
            <aside className="w-64 bg-white dark:bg-slate-800 shadow-lg">
                <div className="p-6">
                    <h1 className="text-xl font-bold text-gray-800 dark:text-white">Sistem Kampus</h1>
                    <p className="text-sm text-gray-500 dark:text-slate-400 mt-1">Dashboard Pembayaran</p>
                </div>
                <nav className="mt-6">
                    <div className="px-4 py-2 text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider">
                        Menu Utama
                    </div>
                    <a href="#" onClick={() => setActiveSection('dashboard')} className={`sidebar-item ${activeSection === 'dashboard' ? 'active' : ''}`}>
                        <LayoutDashboard size={20} /><span>Dashboard</span>
                    </a>
                    <a href="#" onClick={() => { setActiveSection('mahasiswa'); loadMahasiswaData(); }} className={`sidebar-item ${activeSection === 'mahasiswa' ? 'active' : ''}`}>
                        <Users size={20} /><span>Data Mahasiswa</span>
                    </a>
                    <a href="#" onClick={() => { setActiveSection('pembayaran'); loadPembayaranData(); }} className={`sidebar-item ${activeSection === 'pembayaran' ? 'active' : ''}`}>
                        <CreditCard size={20} /><span>Pembayaran</span>
                    </a>
                    <a href="#" onClick={() => setActiveSection('tambah-pembayaran')} className={`sidebar-item ${activeSection === 'tambah-pembayaran' ? 'active' : ''}`}>
                        <FileText size={20} /><span>Tambah Pembayaran</span>
                    </a>
                    <a href="#" onClick={() => setActiveSection('laporan')} className={`sidebar-item ${activeSection === 'laporan' ? 'active' : ''}`}>
                        <FileText size={20} /><span>Laporan</span>
                    </a>
                </nav>
            </aside>
            
            {/* Main Content */}
            <div className="flex-1 p-8">
                {/* Header */}
                <header className="flex justify-between items-center mb-8">
                    <h2 className="text-2xl font-bold text-gray-800 dark:text-white">Dashboard</h2>
                    <div className="flex items-center gap-4">
                        <button onClick={toggleDarkMode} className="p-2 rounded-lg bg-gray-200 dark:bg-slate-700">
                            <span>{isDarkMode ? '☀️' : '🌙'}</span>
                        </button>
                        <div className="text-right">
                            <p className="font-semibold text-gray-800 dark:text-white">Admin User</p>
                            <p className="text-sm text-gray-500 dark:text-slate-400">Administrator</p>
                        </div>
                    </div>
                </header>
                
                {/* Dashboard Content */}
                {activeSection === 'dashboard' && (
                    <div>
                        {/* Stats Cards */}
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                            <div className="stat-card yellow-card">
                                <div className="stat-icon">🎓</div>
                                <div className="stat-content">
                                    <p className="stat-label">Total Mahasiswa</p>
                                    <p className="stat-value">{dashboardStats.total_mahasiswa || 0}</p>
                                </div>
                            </div>
                            <div className="stat-card red-card">
                                <div className="stat-icon">💰</div>
                                <div className="stat-content">
                                    <p className="stat-label">Belum Lunas</p>
                                    <p className="stat-value">{dashboardStats.statistik?.belum_lunas || 0}</p>
                                </div>
                            </div>
                            <div className="stat-card green-card">
                                <div className="stat-icon">✅</div>
                                <div className="stat-content">
                                    <p className="stat-label">Lunas</p>
                                    <p className="stat-value">{dashboardStats.statistik?.lunas || 0}</p>
                                </div>
                            </div>
                            <div className="stat-card orange-card">
                                <div className="stat-icon">⏳</div>
                                <div className="stat-content">
                                    <p className="stat-label">Terlambat</p>
                                    <p className="stat-value">{dashboardStats.statistik?.terlambat || 0}</p>
                                </div>
                            </div>
                        </div>
                        
                        <StudentProfileCard student={selectedStudent} />
                        <PaymentTable payments={pembayaranData} />
                    </div>
                )}
                
                {/* Mahasiswa Section */}
                {activeSection === 'mahasiswa' && (
                    <div className="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
                        <h3 className="text-lg font-bold text-gray-800 dark:text-white mb-4">Data Mahasiswa</h3>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                                <thead className="bg-gray-50 dark:bg-slate-700">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-300 uppercase">NIM</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-300 uppercase">Nama</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-300 uppercase">Program Studi</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-300 uppercase">Status</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-slate-300 uppercase">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {mahasiswaData.length > 0 ? (
                                        mahasiswaData.map(mhs => (
                                            <tr key={mhs.id}>
                                                <td className="px-6 py-4 whitespace-nowrap">{mhs.nim}</td>
                                                <td className="px-6 py-4 whitespace-nowrap">{mhs.user.name}</td>
                                                <td className="px-6 py-4 whitespace-nowrap">{mhs.program_studi.nama}</td>
                                                <td className="px-6 py-4 whitespace-nowrap">{mhs.status}</td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <button onClick={() => viewMahasiswaDetail(mhs.id)} className="text-blue-600 hover:underline mr-2">View</button>
                                                    <button className="text-yellow-600 hover:underline">Edit</button>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan="5" className="px-6 py-8 text-center text-gray-500">Tidak ada data mahasiswa.</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}
                
                {/* Pembayaran Section */}
                {activeSection === 'pembayaran' && (
                    <div>
                        <PaymentTable payments={pembayaranData} />
                    </div>
                )}
                
                {/* Tambah Pembayaran Section */}
                {activeSection === 'tambah-pembayaran' && (
                    <AddPaymentForm />
                )}
                
                {/* Laporan Section */}
                {activeSection === 'laporan' && (
                    <div className="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
                        <h3 className="text-lg font-bold text-gray-800 dark:text-white mb-4">Laporan Pembayaran</h3>
                        <div className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <button className="bg-yellow-500 hover:bg-yellow-600 text-white p-4 rounded-lg text-left">
                                    <p className="font-bold">Laporan Bulanan</p>
                                    <p className="text-sm opacity-80">Export data bulanan</p>
                                </button>
                                <button className="bg-red-500 hover:bg-red-600 text-white p-4 rounded-lg text-left">
                                    <p className="font-bold">Laporan Tunggakan</p>
                                    <p className="text-sm opacity-80">Cetak daftar tunggakan</p>
                                </button>
                                <button className="bg-green-500 hover:bg-green-600 text-white p-4 rounded-lg text-left">
                                    <p className="font-bold">Laporan Arus Kas</p>
                                    <p className="text-sm opacity-80">Export arus kas</p>
                                </button>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
};

export default Dashboard;
