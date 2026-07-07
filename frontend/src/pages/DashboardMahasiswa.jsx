import React from 'react';
import { BookOpen, CalendarDays, CheckCircle2, CreditCard, NotebookTabs, TimerReset } from 'lucide-react';

const stats = [
  { label: 'SKS Diambil', value: '21', foot: 'Batas normal 24 SKS', icon: NotebookTabs },
  { label: 'Kehadiran', value: '93%', foot: 'Semester ini', icon: CheckCircle2 },
  { label: 'Jadwal Hari Ini', value: '4', foot: 'Kelas aktif', icon: CalendarDays },
  { label: 'Status Pembayaran', value: 'Lunas', foot: 'Semester berjalan', icon: CreditCard },
];

const schedule = ['07:30 - Algoritma dan Pemrograman', '10:00 - Basis Data', '13:00 - Analisis Sistem', '15:30 - Praktikum Jaringan'];
const reminders = ['Deadline pengisian KRS tinggal 2 hari', 'Nilai tugas 2 sudah tersedia', 'Jadwal konsultasi PA hari Kamis'];

const StatCard = ({ item }) => { const Icon = item.icon; return <div className="card stat-card"><div className="badge"><Icon size={16} /> {item.label}</div><div className="value">{item.value}</div><div className="foot">{item.foot}</div></div>; };

export const DashboardMahasiswa = () => (
  <div className="dashboard-grid">
    <div className="dashboard-grid stats">{stats.map((item) => <StatCard key={item.label} item={item} />)}</div>
    <div className="dashboard-grid two-col">
      <div className="card section-card">
        <div className="kicker">Jadwal Kuliah</div>
        <h2 className="page-title" style={{ fontSize: '1.9rem', marginTop: 8 }}>Agenda perkuliahan hari ini</h2>
        <div className="list-stack" style={{ marginTop: 16 }}>{schedule.map((item) => <div key={item} className="list-row"><span>{item}</span><TimerReset size={18} color="#8ba3ff" /></div>)}</div>
      </div>
      <div className="card section-card">
        <div className="kicker">Pengingat Akademik</div>
        <div className="list-stack" style={{ marginTop: 16 }}>{reminders.map((item) => <div key={item} className="list-row"><span>{item}</span><BookOpen size={18} color="#8ba3ff" /></div>)}</div>
      </div>
    </div>
    <div className="dashboard-grid two-col">
      <div className="card section-card">
        <div className="kicker">Progres Studi</div>
        <div className="list-stack" style={{ marginTop: 16 }}>
          <div className="list-row"><span>IP Semester</span><strong>3.62</strong></div>
          <div className="list-row"><span>Total SKS Lulus</span><strong>88</strong></div>
          <div className="list-row"><span>Status Akademik</span><strong>Aktif</strong></div>
        </div>
      </div>
        <div className="card section-card">
         <div className="kicker">Aksi Cepat</div>
         <div className="actions-row" style={{ marginTop: 16 }}>
           <button className="btn btn-blue" type="button" onClick={() => console.log('Lihat KRS clicked')}>Lihat KRS</button>
           <button className="btn btn-blue" type="button" onClick={() => console.log('Transkrip clicked')}>Transkrip</button>
           <button className="btn btn-blue" type="button" onClick={() => console.log('Pembayaran clicked')}>Pembayaran</button>
         </div>
       </div>
    </div>
  </div>
);

