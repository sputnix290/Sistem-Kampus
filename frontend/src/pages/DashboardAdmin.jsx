import React from 'react';
import { BarChart3, BookOpen, CalendarDays, ClipboardList, GraduationCap, ShieldCheck, Users } from 'lucide-react';

const stats = [
  { label: 'Mahasiswa Aktif', value: '2.418', foot: '+8.2% dari bulan lalu', icon: Users },
  { label: 'Dosen Aktif', value: '146', foot: '12 fakultas', icon: GraduationCap },
  { label: 'Mata Kuliah', value: '328', foot: 'Semester berjalan', icon: BookOpen },
  { label: 'Kelas Berjalan', value: '84', foot: 'Update real-time', icon: ClipboardList },
];

const activities = [
  'Pendaftaran KRS gelombang 2 dibuka',
  'Pengumuman jadwal ujian tengah semester',
  'Rekap presensi dosen telah diperbarui',
  'Tagihan pembayaran semester ditarik otomatis',
];

const shortcuts = [
  { title: 'Kelola Mahasiswa', desc: 'Tambah, edit, dan pantau data mahasiswa.' },
  { title: 'Kelola Program Studi', desc: 'Atur struktur akademik per fakultas.' },
  { title: 'Lihat Statistik', desc: 'Pantau performa akademik secara cepat.' },
];

const StatCard = ({ item }) => {
  const Icon = item.icon;
  return (
    <div className="card stat-card">
      <div className="badge"><Icon size={16} /> {item.label}</div>
      <div className="value">{item.value}</div>
      <div className="foot">{item.foot}</div>
    </div>
  );
};

const OverviewCard = () => (
  <div className="card section-card">
    <div className="kicker">Ringkasan Akademik</div>
    <h2 className="page-title" style={{ fontSize: '2rem', marginTop: 8 }}>Selamat datang di dashboard kampus</h2>
    <p className="page-subtitle">Pantau aktivitas akademik, status administrasi, dan data utama dalam satu tampilan yang bersih dan mudah dipakai.</p>
    <div className="actions-row" style={{ marginTop: 18 }}>
      <button className="btn btn-primary" type="button">Buka Laporan</button>
      <button className="btn btn-ghost" type="button">Lihat Kalender</button>
    </div>
  </div>
);

const ActivityCard = () => (
  <div className="card section-card">
    <div className="kicker">Aktivitas Terkini</div>
    <div className="list-stack" style={{ marginTop: 16 }}>
      {activities.map((item) => (
        <div className="list-row" key={item}>
          <span>{item}</span>
          <BarChart3 size={18} color="#8ba3ff" />
        </div>
      ))}
    </div>
  </div>
);

const ShortcutCard = () => (
  <div className="card section-card">
    <div className="kicker">Akses Cepat</div>
    <div className="list-stack" style={{ marginTop: 16 }}>
      {shortcuts.map((item) => (
        <div key={item.title} className="list-row" style={{ flexDirection: 'column', alignItems: 'flex-start' }}>
          <strong>{item.title}</strong>
          <span style={{ color: 'var(--muted)' }}>{item.desc}</span>
        </div>
      ))}
       <div className="list-row" style={{ flexDirection: 'column', alignItems: 'flex-start' }}>
          <button className="btn btn-blue" onClick={() => console.log('Lihat KRS clicked')}>Lihat KRS</button>
        </div>
        <div className="list-row" style={{ flexDirection: 'column', alignItems: 'flex-start' }}>
          <button className="btn btn-blue" onClick={() => console.log('Transkrip clicked')}>Transkrip</button>
        </div>
        <div className="list-row" style={{ flexDirection: 'column', alignItems: 'flex-start' }}>
          <button className="btn btn-blue" onClick={() => console.log('Pembayaran clicked')}>Pembayaran</button>
        </div>
    </div>
  </div>
);

export const DashboardAdmin = () => {
  return (
    <div className="dashboard-grid">
      <div className="dashboard-grid stats">
        {stats.map((item) => <StatCard key={item.label} item={item} />)}
      </div>
      <div className="dashboard-grid two-col">
        <OverviewCard />
        <ActivityCard />
      </div>
      <div className="dashboard-grid two-col">
        <ShortcutCard />
        <div className="card section-card">
          <div className="kicker">Status Sistem</div>
          <div className="list-stack" style={{ marginTop: 16 }}>
            <div className="list-row"><span>API Kampus</span><span className="badge">Aktif</span></div>
            <div className="list-row"><span>Sinkronisasi Data</span><span className="badge">Normal</span></div>
            <div className="list-row"><span>Keamanan Login</span><span className="badge">Terlindungi</span></div>
          </div>
        </div>
      </div>
    </div>
  );
};
