import React from 'react';

const PaymentTable = ({ payments }) => {
    return (
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
                        {payments && payments.length > 0 ? (
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
};

export default PaymentTable;
