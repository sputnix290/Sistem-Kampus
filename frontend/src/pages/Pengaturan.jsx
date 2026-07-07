import React from 'react';
import { Settings, Moon, Sun, Bell, Globe, Shield, Database, RefreshCw } from 'lucide-react';
import { useAuth } from '../context/AuthContext';

export const PengaturanPage = () => {
  const { user } = useAuth();
  const isAdmin = user?.role === 'admin';

  return (
    <div className="dashboard-grid" style={{ gap: '18px' }}>
      <div className="card section-card">
        <div className="kicker">Pengaturan Sistem</div>
        <h1 className="page-title" style={{ fontSize: '2rem', marginTop: 8 }}>Kelola Pengaturan</h1>
        <p className="page-subtitle">Atur preferensi tampilan, notifikasi, dan konfigurasi sistem kampus.</p>
      </div>

      <div className="dashboard-grid two-col">
        <div className="card section-card">
          <div className="kicker">Tampilan</div>
          <div className="list-stack" style={{ marginTop: 16 }}>
            <div className="list-row">
              <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                <Moon size={18} color="#94a3b8" />
                <div>
                  <strong style={{ color: 'var(--text)', fontSize: '0.95rem' }}>Mode Gelap</strong>
                  <p style={{ margin: 0, color: 'var(--muted)', fontSize: '0.85rem' }}>Tema tampilan gelap aktif secara default</p>
                </div>
              </div>
              <label style={{ position: 'relative', width: '44px', height: '24px', cursor: 'pointer' }}>
                <input type="checkbox" defaultChecked style={{ opacity: 0, width: 0, height: 0 }} />
                <span style={{ position: 'absolute', inset: 0, background: 'var(--primary)', borderRadius: '12px', transition: '0.2s' }}>
                  <span style={{ position: 'absolute', top: '2px', left: '22px', width: '20px', height: '20px', background: 'white', borderRadius: '50%', transition: '0.2s' }} />
                </span>
              </label>
            </div>
            <div className="list-row">
              <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                <Globe size={18} color="#94a3b8" />
                <div>
                  <strong style={{ color: 'var(--text)', fontSize: '0.95rem' }}>Bahasa</strong>
                  <p style={{ margin: 0, color: 'var(--muted)', fontSize: '0.85rem' }}>Bahasa antarmuka</p>
                </div>
              </div>
              <select style={{ padding: '6px 12px', borderRadius: '10px', border: '1px solid var(--line)', background: 'var(--surface-strong)', color: 'var(--text)' }}>
                <option value="id">Bahasa Indonesia</option>
                <option value="en">English</option>
              </select>
            </div>
          </div>
        </div>

        <div className="card section-card">
          <div className="kicker">Notifikasi</div>
          <div className="list-stack" style={{ marginTop: 16 }}>
            <div className="list-row">
              <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                <Bell size={18} color="#94a3b8" />
                <div>
                  <strong style={{ color: 'var(--text)', fontSize: '0.95rem' }}>Notifikasi Email</strong>
                  <p style={{ margin: 0, color: 'var(--muted)', fontSize: '0.85rem' }}>Terima pemberitahuan via email</p>
                </div>
              </div>
              <label style={{ position: 'relative', width: '44px', height: '24px', cursor: 'pointer' }}>
                <input type="checkbox" defaultChecked style={{ opacity: 0, width: 0, height: 0 }} />
                <span style={{ position: 'absolute', inset: 0, background: 'var(--primary)', borderRadius: '12px', transition: '0.2s' }}>
                  <span style={{ position: 'absolute', top: '2px', left: '22px', width: '20px', height: '20px', background: 'white', borderRadius: '50%', transition: '0.2s' }} />
                </span>
              </label>
            </div>
            <div className="list-row">
              <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                <Shield size={18} color="#94a3b8" />
                <div>
                  <strong style={{ color: 'var(--text)', fontSize: '0.95rem' }}>Keamanan Login</strong>
                  <p style={{ margin: 0, color: 'var(--muted)', fontSize: '0.85rem' }}>Autentikasi dua faktor</p>
                </div>
              </div>
              <label style={{ position: 'relative', width: '44px', height: '24px', cursor: 'pointer' }}>
                <input type="checkbox" style={{ opacity: 0, width: 0, height: 0 }} />
                <span style={{ position: 'absolute', inset: 0, background: 'rgba(148, 163, 184, 0.3)', borderRadius: '12px', transition: '0.2s' }}>
                  <span style={{ position: 'absolute', top: '2px', left: '2px', width: '20px', height: '20px', background: 'white', borderRadius: '50%', transition: '0.2s' }} />
                </span>
              </label>
            </div>
          </div>
        </div>
      </div>

      {isAdmin && (
        <div className="dashboard-grid two-col">
          <div className="card section-card">
            <div className="kicker">Administrasi Sistem</div>
            <div className="list-stack" style={{ marginTop: 16 }}>
              <div className="list-row">
                <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                  <Database size={18} color="#94a3b8" />
                  <div>
                    <strong style={{ color: 'var(--text)', fontSize: '0.95rem' }}>Backup Database</strong>
                    <p style={{ margin: 0, color: 'var(--muted)', fontSize: '0.85rem' }}>Terakhir backup: 2 jam lalu</p>
                  </div>
                </div>
                <button className="btn btn-ghost" style={{ padding: '8px 14px' }}>Backup</button>
              </div>
              <div className="list-row">
                <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
                  <RefreshCw size={18} color="#94a3b8" />
                  <div>
                    <strong style={{ color: 'var(--text)', fontSize: '0.95rem' }}>Sinkronisasi Data</strong>
                    <p style={{ margin: 0, color: 'var(--muted)', fontSize: '0.85rem' }}>Sinkronkan dengan sistem eksternal</p>
                  </div>
                </div>
                <button className="btn btn-ghost" style={{ padding: '8px 14px' }}>Sync</button>
              </div>
            </div>
          </div>

          <div className="card section-card">
            <div className="kicker">Informasi Sistem</div>
            <div className="list-stack" style={{ marginTop: 16 }}>
              <div className="list-row"><span>Versi Aplikasi</span><strong>1.0.0</strong></div>
              <div className="list-row"><span>Backend Framework</span><strong>Laravel 11</strong></div>
              <div className="list-row"><span>Frontend Framework</span><strong>React + Vite</strong></div>
              <div className="list-row"><span>Database</span><strong>MySQL</strong></div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default PengaturanPage;
