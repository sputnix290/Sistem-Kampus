import React from 'react';
import { Link, Outlet, useLocation } from 'react-router-dom';
import { BookOpen, Building2, ClipboardList, GraduationCap, LayoutDashboard, LogOut, Megaphone, School2, Settings, Users } from 'lucide-react';
import { useAuth } from '../context/AuthContext';

const NAV_ITEMS = [
  { label: 'Dashboard', to: '/dashboard', icon: LayoutDashboard, roles: ['admin'] },
  { label: 'Dashboard Dosen', to: '/dashboard/dosen', icon: GraduationCap, roles: ['dosen'] },
  { label: 'Dashboard Mahasiswa', to: '/dashboard/mahasiswa', icon: School2, roles: ['mahasiswa'] },
  { label: 'Mahasiswa', to: '/mahasiswa', icon: Users, roles: ['admin'] },
  { label: 'Dosen', to: '/dosen', icon: ClipboardList, roles: ['admin'] },
  { label: 'Program Studi', to: '/program-studi', icon: Building2, roles: ['admin'] },
  { label: 'Mata Kuliah', to: '/mata-kuliah', icon: BookOpen, roles: ['admin'] },
  { label: 'Pengumuman', to: '/pengumuman', icon: Megaphone, roles: ['admin', 'dosen'] },
  { label: 'Pengaturan', to: '/pengaturan', icon: Settings, roles: ['admin'] },
];

const roleLabel = { admin: 'Administrator', dosen: 'Dosen', mahasiswa: 'Mahasiswa' };

export const AppLayout = () => {
  const { user, logout } = useAuth();
  const location = useLocation();
  const currentRole = user?.role || 'admin';
  const filteredNav = NAV_ITEMS.filter((item) => item.roles.includes(currentRole));

  return (
    <div className="app-shell">
      <aside className="sidebar">
        <div className="sidebar-brand">
          <div className="brand-mark">SK</div>
          <div className="brand-title"><strong>Sistem Kampus</strong><span>{roleLabel[currentRole] || 'Pengguna'}</span></div>
        </div>
        <div className="nav-section">
          <p className="nav-label">Menu Utama</p>
          <nav className="nav-list">
            {filteredNav.map((item) => {
              const Icon = item.icon;
              const active = location.pathname === item.to;
              return <Link key={item.to} to={item.to} className={`nav-item ${active ? 'active' : ''}`}><Icon size={18} /><span>{item.label}</span></Link>;
            })}
          </nav>
        </div>
        <div className="sidebar-footer">
          <button className="nav-item" onClick={logout} type="button" style={{ width: '100%', border: 0, background: 'transparent' }}>
            <LogOut size={18} /><span>Keluar</span>
          </button>
        </div>
      </aside>
      <main className="main-area">
        <header className="topbar">
          <div><div className="kicker">Portal Akademik</div><h1 className="page-title" style={{ fontSize: '1.35rem', margin: '4px 0 0' }}>{user?.name || 'Pengguna'}</h1></div>
          <div className="badge">{roleLabel[currentRole] || 'Pengguna'}</div>
        </header>
        <div className="page-content"><Outlet /></div>
      </main>
    </div>
  );
};
