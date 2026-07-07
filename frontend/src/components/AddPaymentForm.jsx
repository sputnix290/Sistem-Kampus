import React, { useState, useEffect } from 'react';
import axios from 'axios';

const AddPaymentForm = () => {
    const [mahasiswaList, setMahasiswaList] = useState([]);
    const [formData, setFormData] = useState({
        mahasiswa_id: '',
        jenis_pembayaran: 'spp',
        jumlah_tagihan: '',
        jumlah_dibayar: '',
        tanggal_jatuh_tempo: '',
        metode_pembayaran: 'transfer',
        keterangan: '',
        tahun_akademik_id: 1, // Placeholder
        dibuat_oleh: 1 // Placeholder
    });

    useEffect(() => {
        const fetchMahasiswa = async () => {
            try {
                const response = await axios.get('/api/mahasiswa', {
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${getAuthToken()}`
                    }
                });
                setMahasiswaList(response.data.data);
            } catch (error) {
                console.error('Error fetching mahasiswa list:', error);
            }
        };
        fetchMahasiswa();
    }, []);

    const getAuthToken = () => {
        // Placeholder: In a real app, get this from localStorage, context, etc.
        return 'YOUR_AUTH_TOKEN'; 
    };

    const handleChange = (e) => {
        setFormData({ ...formData, [e.target.id]: e.target.value });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            const response = await axios.post('/api/pembayaran', formData, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${getAuthToken()}`
                }
            });
            alert(response.data.pesan);
            // Optionally reset form or refresh payment data
            setFormData({
                mahasiswa_id: '',
                jenis_pembayaran: 'spp',
                jumlah_tagihan: '',
                jumlah_dibayar: '',
                tanggal_jatuh_tempo: '',
                metode_pembayaran: 'transfer',
                keterangan: '',
                tahun_akademik_id: 1, 
                dibuat_oleh: 1 
            });
        } catch (error) {
            console.error('Error adding payment:', error);
            alert('Terjadi kesalahan saat menambahkan pembayaran.');
        }
    };

    return (
        <div className="bg-white dark:bg-slate-800 rounded-xl shadow-lg p-6">
            <h3 className="text-lg font-bold text-gray-800 dark:text-white mb-4">Tambah Pembayaran</h3>
            <form onSubmit={handleSubmit} className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label htmlFor="mahasiswa_id" className="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Mahasiswa</label>
                        <select id="mahasiswa_id" value={formData.mahasiswa_id} onChange={handleChange} className="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-yellow-500 bg-white dark:bg-slate-700 text-gray-800 dark:text-white">
                            <option value="">Pilih Mahasiswa</option>
                            {mahasiswaList.map(mahasiswa => (
                                <option key={mahasiswa.id} value={mahasiswa.id}>{mahasiswa.user.name} ({mahasiswa.nim})</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label htmlFor="jenis_pembayaran" className="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Jenis Pembayaran</label>
                        <select id="jenis_pembayaran" value={formData.jenis_pembayaran} onChange={handleChange} className="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-yellow-500 bg-white dark:bg-slate-700 text-gray-800 dark:text-white">
                            <option value="spp">SPP</option>
                            <option value="ukt">UKT</option>
                            <option value="daftar_ulang">Daftar Ulang</option>
                            <option value="praktikum">Praktikum</option>
                            <option value="skripsi">Skripsi</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label htmlFor="jumlah_tagihan" className="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Jumlah Tagihan</label>
                        <input type="number" id="jumlah_tagihan" value={formData.jumlah_tagihan} onChange={handleChange} className="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-yellow-500 bg-white dark:bg-slate-700 text-gray-800 dark:text-white" placeholder="0" />
                    </div>
                    <div>
                        <label htmlFor="jumlah_dibayar" className="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Jumlah Dibayar</label>
                        <input type="number" id="jumlah_dibayar" value={formData.jumlah_dibayar} onChange={handleChange} className="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-yellow-500 bg-white dark:bg-slate-700 text-gray-800 dark:text-white" placeholder="0" />
                    </div>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label htmlFor="tanggal_jatuh_tempo" className="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Tanggal Jatuh Tempo</label>
                        <input type="date" id="tanggal_jatuh_tempo" value={formData.tanggal_jatuh_tempo} onChange={handleChange} className="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-yellow-500 bg-white dark:bg-slate-700 text-gray-800 dark:text-white" />
                    </div>
                    <div>
                        <label htmlFor="metode_pembayaran" className="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Metode Pembayaran</label>
                        <select id="metode_pembayaran" value={formData.metode_pembayaran} onChange={handleChange} className="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-yellow-500 bg-white dark:bg-slate-700 text-gray-800 dark:text-white">
                            <option value="transfer">Transfer</option>
                            <option value="tunai">Tunai</option>
                            <option value="kartu_kredit">Kartu Kredit</option>
                            <option value="debit">Debit</option>
                            <option value="online">Online</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label htmlFor="keterangan" className="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Keterangan</label>
                    <textarea id="keterangan" value={formData.keterangan} onChange={handleChange} className="w-full px-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg focus:ring-2 focus:ring-yellow-500 bg-white dark:bg-slate-700 text-gray-800 dark:text-white" rows="3" placeholder="Keterangan tambahan..."></textarea>
                </div>
                <button type="submit" className="w-full bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-3 px-6 rounded-lg transition-colors">
                    Simpan Pembayaran
                </button>
            </form>
        </div>
    );
};

export default AddPaymentForm;
