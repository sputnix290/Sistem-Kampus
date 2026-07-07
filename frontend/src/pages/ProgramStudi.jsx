import React, { useState, useCallback } from 'react';
import { Plus, Edit2, Trash2, Building2 } from 'lucide-react';
import { DataTable } from '../components/DataTable';
import { Modal } from '../components/Modal';
import { programStudiApi, fakultasApi } from '../utils/api';

export const ProgramStudiPage = () => {
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingItem, setEditingItem] = useState(null);
  const [formData, setFormData] = useState({ kode: '', nama: '', fakultas_id: '', akreditasi: '', deskripsi: '' });
  const [fakultasList, setFakultasList] = useState([]);
  const [submitting, setSubmitting] = useState(false);
  const [refreshKey, setRefreshKey] = useState(0);

  const fetchOptions = async () => {
    try {
      const fak = await fakultasApi.getAll();
      setFakultasList(fak.data || fak);
    } catch (e) {
      setFakultasList([{ id: 1, name: 'Fakultas Ilmu Komputer' }, { id: 2, name: 'Fakultas Ekonomi' }, { id: 3, name: 'Fakultas Hukum' }]);
    }
  };

  const openCreate = () => {
    setEditingItem(null);
    setFormData({ kode: '', nama: '', fakultas_id: '', akreditasi: 'Belum Terakreditasi', deskripsi: '' });
    fetchOptions();
    setIsFormOpen(true);
  };

  const openEdit = (item) => {
    setEditingItem(item);
    setFormData({
      kode: item.kode || '',
      nama: item.nama || item.name || '',
      fakultas_id: item.fakultas_id || item.fakultas?.id || '',
      akreditasi: item.akreditasi || 'Belum Terakreditasi',
      deskripsi: item.deskripsi || '',
    });
    fetchOptions();
    setIsFormOpen(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      if (editingItem) await programStudiApi.update(editingItem.id, formData);
      else await programStudiApi.create(formData);
      setIsFormOpen(false);
      setRefreshKey(k => k + 1);
    } catch (err) { alert(err.response?.data?.message || 'Gagal menyimpan data'); }
    finally { setSubmitting(false); }
  };

  const handleDelete = async (id) => {
    if (!confirm('Yakin ingin menghapus program studi ini?')) return;
    try { await programStudiApi.delete(id); setRefreshKey(k => k + 1); }
    catch (err) { alert(err.response?.data?.message || 'Gagal menghapus data'); }
  };

  const fetchData = useCallback(async ({ page = 1, perPage = 10, search = '' }) => {
    const params = { page, per_page: perPage };
    if (search) params.search = search;
    return programStudiApi.getAll(params);
  }, []);

  const columns = [
    { key: 'kode', label: 'Kode Prodi' },
    { key: 'nama', label: 'Nama Program Studi', render: (val, row) => row.nama || row.name || val || '—' },
    { key: 'fakultas', label: 'Fakultas', render: (val, row) => row.fakultas?.name || row.fakultas?.nama || val || '—' },
    { key: 'akreditasi', label: 'Akreditasi', render: (val) => {
      const colors = { 'A': 'rgba(16,185,129,0.15)', 'B': 'rgba(59,130,246,0.15)', 'C': 'rgba(245,158,11,0.15)' };
      const textColors = { 'A': '#34d399', 'B': '#60a5fa', 'C': '#fbbf24' };
      const borders = { 'A': 'rgba(16,185,129,0.3)', 'B': 'rgba(59,130,246,0.3)', 'C': 'rgba(245,158,11,0.3)' };
      const c = val?.toUpperCase();
      return <span style={{ padding: '4px 10px', borderRadius: '999px', background: colors[c] || 'rgba(148,163,184,0.15)', color: textColors[c] || '#94a3b8', border: `1px solid ${borders[c] || 'rgba(148,163,184,0.3)'}`, fontSize: '0.78rem', fontWeight: 600 }}>{val || '—'}</span>;
    }},
    { key: 'jumlah_mahasiswa', label: 'Jumlah Mahasiswa', render: (val, row) => row.jumlah_mahasiswa || row.mahasiswa_count || val || '0' },
  ];

  const akreditasiOptions = ['Unggul', 'A', 'B', 'C', 'Belum Terakreditasi'];

  return (
    <div className="dashboard-grid" style={{ gap: '18px' }}>
      <div className="card section-card">
        <div className="kicker">Modul Akademik</div>
        <h1 className="page-title" style={{ fontSize: '2rem', marginTop: 8 }}>Kelola Program Studi</h1>
        <p className="page-subtitle">Atur struktur program studi per fakultas dan pantau data akademik secara cepat.</p>
        <div className="actions-row" style={{ marginTop: 18 }}>
          <button className="btn btn-primary" type="button" onClick={openCreate}><Plus size={16} style={{ marginRight: 6 }} /> Tambah Program Studi</button>
        </div>
      </div>

      <div className="dashboard-grid stats">
        <div className="card stat-card">
          <div className="badge"><Building2 size={16} /> Total Program Studi</div>
          <div className="value" style={{ fontSize: '1.7rem' }}>24</div>
          <div className="foot">Seluruh fakultas</div>
        </div>
      </div>

      <DataTable key={refreshKey} columns={columns} onFetchData={fetchData} emptyMessage="Belum ada data program studi"
        actions={(row) => (
          <div style={{ display: 'flex', gap: '6px' }}>
            <button className="btn btn-ghost" onClick={() => openEdit(row)} style={{ padding: '6px 10px' }}><Edit2 size={14} /></button>
            <button className="btn btn-ghost" onClick={() => handleDelete(row.id)} style={{ padding: '6px 10px', color: 'var(--danger)' }}><Trash2 size={14} /></button>
          </div>
        )} />

      <Modal isOpen={isFormOpen} onClose={() => setIsFormOpen(false)} title={editingItem ? 'Edit Program Studi' : 'Tambah Program Studi'} size="md"
        footer={<>
          <button className="btn btn-ghost" onClick={() => setIsFormOpen(false)}>Batal</button>
          <button className="btn btn-primary" onClick={handleSubmit} disabled={submitting}>{submitting ? 'Menyimpan...' : editingItem ? 'Update' : 'Simpan'}</button>
        </>}>
        <form onSubmit={handleSubmit} className="form-grid">
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
            <div className="form-field"><label>Kode Program Studi</label><input type="text" value={formData.kode} onChange={e => setFormData({...formData, kode: e.target.value})} placeholder="Contoh: TIF" required /></div>
            <div className="form-field"><label>Nama Program Studi</label><input type="text" value={formData.nama} onChange={e => setFormData({...formData, nama: e.target.value})} placeholder="Contoh: Teknik Informatika" required /></div>
          </div>
          <div className="form-field"><label>Fakultas</label><select value={formData.fakultas_id} onChange={e => setFormData({...formData, fakultas_id: e.target.value})} required><option value="">Pilih Fakultas</option>{fakultasList.map(f => <option key={f.id} value={f.id}>{f.name}</option>)}</select></div>
          <div className="form-field"><label>Akreditasi</label><select value={formData.akreditasi} onChange={e => setFormData({...formData, akreditasi: e.target.value})}>{akreditasiOptions.map(a => <option key={a} value={a}>{a}</option>)}</select></div>
          <div className="form-field"><label>Deskripsi</label><textarea value={formData.deskripsi} onChange={e => setFormData({...formData, deskripsi: e.target.value})} rows={4} placeholder="Deskripsi singkat program studi..." style={{ borderRadius: '14px', border: '1px solid var(--line)', background: 'rgba(15, 23, 42, 0.8)', color: 'var(--text)', padding: '13px 14px', fontFamily: 'inherit' }} /></div>
        </form>
      </Modal>
    </div>
  );
};

export default ProgramStudiPage;
