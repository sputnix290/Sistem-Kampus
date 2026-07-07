import React from 'react';
import { BookOpen, CalendarDays, ClipboardList, Clock3, GraduationCap, LineChart, Users } from 'lucide-react';

const stats = [
  { label: 'Kelas Diampu', value: '8', foot: 'Semester ganjil', icon: BookOpen },
  { label: 'Mahasiswa Bimbingan', value: '42', foot: 'Aktif', icon: Users },
  { label: 'Pertemuan Hari Ini', value: '3', foot: 'Jadwal terdekat', icon: CalendarDays },
  { label: 'Presensi Masuk', value: '96%', foot: 'Rata-rata bulan ini', icon: LineChart },
];

const schedule = ['08:00 - Basis Data Lanjut', '10:00 - Sistem Informasi Akademik', '13:00 - Seminar Proposal'];
const alerts = ['Batas unggah nilai tengah semester: Jumat, 16:00', 'Form presensi kelas reguler tersedia', 'Ada 5 mahasiswa yang belum konsultasi minggu ini'];

const StatCard = ({ item }) => { const Icon = item.icon; return <div className="card stat-card"><div className="badge"><Icon size={16} /> {item.label}</div><div className="value">{item.value}</div><div className="foot">{item.foot}</div></div>; };

export const DashboardDosen = () => (
  <div className="dashboard-grid">
    <div className="dashboard-grid stats">{stats.map((item) => <StatCard key={item.label} item={item} />)}</div>
    <div className="dashboard-grid two-col">
      <div className="card section-card">
        <div className="kicker">Jadwal Hari Ini</div>
        <h2 className="page-title" style={{ fontSize: '1.9rem', marginTop: 8 }}>Aktivitas mengajar</h2>
        <div className="list-stack" style={{ marginTop: 16 }}>{schedule.map((item) => <div key={item} className="list-row"><span>{item}</span><Clock3 size={18} color="#8ba3ff" /></div>)}</div>
      </div>
      <div className="card section-card">
        <div className="kicker">Notifikasi Akademik</div>
        <div className="list-stack" style={{ marginTop: 16 }}>{alerts.map((item) => <div key={item} className="list-row"><span>{item}</span><ClipboardList size={18} color="#8ba3ff" /></div>)}</div>
      </div>
    </div>
    <div className="dashboard-grid two-col">
        <div className="card section-card">
         <div className="kicker">Tugas Cepat</div>
         <div className="actions-row" style={{ marginTop: 16 }}>
           <button className="btn btn-blue" type="button" onClick={() => console.log('Input Nilai clicked')}>Input Nilai</button>
           <button className="btn btn-blue" type="button" onClick={() => console.log('Absensi clicked')}>Absensi Kelas</button>
           <button className="btn btn-blue" type="button" onClick={() => console.log('Bimbingan clicked')}>Bimbingan</button>
         </div>
       </div>
      <div className="card section-card">
        <div className="kicker">Ringkasan</div>
        <p className="page-subtitle" style={{ marginTop: 12 }}>Dashboard dosen menampilkan beban mengajar, jadwal, dan pengingat penting agar aktivitas akademik lebih terarah.</p>
      </div>
    </div>
  </div>
);
