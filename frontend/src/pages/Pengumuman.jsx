import React, { useState, useCallback } from 'react';
import { Plus, Edit2, Trash2, Megaphone, Eye, Send } from 'lucide-react';
import { DataTable } from '../components/DataTable';
import { Modal } from '../components/Modal';
import { pengumumanApi } from '../utils/api';

export const PengumumanPage = () => {
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingItem, setEditingItem] = useState(null);
  const [formData, setFormData] = useState({ judul: '', isi: '', target_audience: 'all', tanggal_mulai: '', tanggal_selesai: '', status: 'draft', priority: 'normal' });
  const [submitting, setSubmitting] = useState(false);
  const [refreshKey, setRefreshKey] = useState(0);

  const openCreate = () => {
    setEditingItem(null);
    const today = new Date().toISOString().split('T')[0];
    setFormData({ judul: '', isi: '', target_audience: 'all', tanggal_mulai: today, tanggal_selesai: '', status: 'draft', priority: 'normal' });
    setIsFormOpen(true);
  };

  const openEdit = (item) => {
    setEditingItem(item);
    setFormData({
      judul: item.judul || item.title || '',
      isi: item.isi || item.content || '',
      target_audience: item.target_audience || item.targetAudience || 'all',
      tanggal_mulai: item.tanggal_mulai || item.tanggalMulai || '',
      tanggal_selesai: item.tanggal_selesai || item.tanggalSelesai || '',
      status: item.status || 'draft',
      priority: item.priority || 'normal',
    });/*  */
    setIsFormOpen(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      if (editingItem) await pengumumanApi.update(editingItem.id, formData);
      else await pengumumanApi.create(formData);
      setIsFormOpen(false);
      setRefreshKey(k => k + 1);
    } catch (err) { alert(err.response?.data?.message || 'Gagal menyimpan data'); }
    finally { setSubmitting(false); }
  };

  const handleDelete = async (id) => {
    if (!confirm('Yakin ingin menghapus pengumuman ini?')) return;
    try { await pengumumanApi.delete(id); setRefreshKey(k => k + 1); }
    catch (err) { alert(err.response?.data?.message || 'Gagal menghapus data'); }
  };

  const handlePublish = async (id) => {
    try {
      await pengumumanApi.update(id, { status: 'published' });
      setRefreshKey(k => k + 1);
    } catch (err) { alert(err.response?.data?.message || 'Gagal mempublikasikan pengumuman'); }
  };

  const fetchData = useCallback(async ({ page = 1, perPage = 10, search = '' }) => {
    const params = { page, per_page: perPage };
    if (search) params.search = search;
    return pengumumanApi.getAll(params);
  }, []);

  const targetLabels = { all: 'Semua', admin: 'Admin', dosen: 'Dosen', mahasiswa: 'Mahasiswa' };

  const columns = [
    {
      key: 'judul', label: 'Judul', render: (val, row) => (
        <div style={{ display: 'flex', flexDirection: 'column', gap: '4px' }}>
          <strong style={{ color: 'var(--text)' }}>{row.judul || row.title || val || '—'}</strong>
          <span style={{ fontSize: '0.82rem', color: 'var(--muted-2)' }}>
            {row.tanggal_mulai ? new Date(row.tanggal_mulai).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—'}
          </span>
        </div>
      )
    },
    { key: 'target_audience', label: 'Target', render: (val, row) => targetLabels[row.target_audience || row.targetAudience || val] || val || '—' },
    {
      key: 'priority', label: 'Prioritas', render: (val, row) => {
        const p = row.priority || val || 'normal';
        const colors = { tinggi: 'rgba(239,68,68,0.15)', normal: 'rgba(59,130,246,0.15)', rendah: 'rgba(148,163,184,0.15)' };
        const textColors = { tinggi: '#f87171', normal: '#60a5fa', rendah: '#94a3b8' };
        return <span style={{ padding: '4px 10px', borderRadius: '999px', background: colors[p] || colors.normal, color: textColors[p] || textColors.normal, fontSize: '0.78rem', fontWeight: 600, textTransform: 'capitalize' }}>{p}</span>;
      }
    },
    {
      key: 'status', label: 'Status', render: (val, row) => {
        const s = row.status || val || 'draft';
        return <span style={{ padding: '4px 10px', borderRadius: '999px', background: s === 'published' ? 'rgba(16,185,129,0.15)' : 'rgba(245,158,11,0.15)', color: s === 'published' ? '#34d399' : '#fbbf24', border: `1px solid ${s === 'published' ? 'rgba(16,185,129,0.3)' : 'rgba(245,158,11,0.3)'}`, fontSize: '0.78rem', fontWeight: 600 }}>{s === 'published' ? 'Published' : 'Draft'}</span>;
      }
    },
  ];

  return (
    <div className="dashboard-grid" style={{ gap: '18px' }}>
      <div className="card section-card">
        <div className="kicker">Modul Akademik</div>
        <h1 className="page-title" style={{ fontSize: '2rem', marginTop: 8 }}>Kelola Pengumuman</h1>
        <p className="page-subtitle">Buat dan publikasikan pengumuman akademik untuk dosen dan mahasiswa.</p>
        <div className="actions-row" style={{ marginTop: 18 }}>
          <button className="btn btn-primary" type="button" onClick={openCreate}><Plus size={16} style={{ marginRight: 6 }} /> Buat Pengumuman</button>
        </div>
      </div>

      <div className="dashboard-grid stats">
        <div className="card stat-card">
          <div className="badge"><Megaphone size={16} /> Pengumuman Aktif</div>
          <div className="value" style={{ fontSize: '1.7rem' }}>12</div>
          <div className="foot">Bulan ini</div>
        </div>
      </div>

      <DataTable key={refreshKey} columns={columns} onFetchData={fetchData} emptyMessage="Belum ada pengumuman"
        actions={(row) => (
          <div style={{ display: 'flex', gap: '6px' }}>
            {row.status !== 'published' && (
              <button className="btn btn-ghost" onClick={() => handlePublish(row.id)} style={{ padding: '6px 10px', color: 'var(--success)' }} title="Publish">
                <Send size={14} />
              </button>
            )}
            <button className="btn btn-ghost" onClick={() => openEdit(row)} style={{ padding: '6px 10px' }} title="Edit">
              <Edit2 size={14} />
            </button>
            <button className="btn btn-ghost" onClick={() => handleDelete(row.id)} style={{ padding: '6px 10px', color: 'var(--danger)' }} title="Hapus">
              <Trash2 size={14} />
            </button>
          </div>
        )} />

      <Modal isOpen={isFormOpen} onClose={() => setIsFormOpen(false)} title={editingItem ? 'Edit Pengumuman' : 'Buat Pengumuman'} size="lg"
        footer={<>
          <button className="btn btn-ghost" onClick={() => setIsFormOpen(false)}>Batal</button>
          <button className="btn btn-primary" onClick={handleSubmit} disabled={submitting}>{submitting ? 'Menyimpan...' : editingItem ? 'Update' : 'Simpan'}</button>
        </>}>
        <form onSubmit={handleSubmit} className="form-grid">
          <div className="form-field"><label>Judul Pengumuman</label><input type="text" value={formData.judul} onChange={e => setFormData({ ...formData, judul: e.target.value })} placeholder="Masukkan judul pengumuman" required /></div>
          <div className="form-field"><label>Isi Pengumuman</label><textarea value={formData.isi} onChange={e => setFormData({ ...formData, isi: e.target.value })} rows={6} placeholder="Tulis isi pengumuman di sini..." required style={{ borderRadius: '14px', border: '1px solid var(--line)', background: 'rgba(15, 23, 42, 0.8)', color: 'var(--text)', padding: '13px 14px', fontFamily: 'inherit', resize: 'vertical' }} /></div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
            <div className="form-field"><label>Target Audience</label><select value={formData.target_audience} onChange={e => setFormData({ ...formData, target_audience: e.target.value })}>
              <option value="all">Semua Pengguna</option>
              <option value="admin">Admin Saja</option>
              <option value="dosen">Dosen Saja</option>
              <option value="mahasiswa">Mahasiswa Saja</option>
            </select></div>
            <div className="form-field"><label>Prioritas</label><select value={formData.priority} onChange={e => setFormData({ ...formData, priority: e.target.value })}>
              <option value="normal">Normal</option>
              <option value="tinggi">Tinggi</option>
              <option value="rendah">Rendah</option>
            </select></div>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
            <div className="form-field"><label>Tanggal Mulai</label><input type="date" value={formData.tanggal_mulai} onChange={e => setFormData({ ...formData, tanggal_mulai: e.target.value })} required /></div>
            <div className="form-field"><label>Tanggal Selesai (opsional)</label><input type="date" value={formData.tanggal_selesai} onChange={e => setFormData({ ...formData, tanggal_selesai: e.target.value })} /></div>
          </div>
          <div className="form-field"><label>Status</label><select value={formData.status} onChange={e => setFormData({ ...formData, status: e.target.value })}>
            <option value="draft">Draft</option>
            <option value="published">Published</option>
          </select></div>
        </form>
      </Modal>
    </div>
  );
};

export default PengumumanPage;
