import React, { useState, useCallback } from 'react';
import { Plus, Edit2, Trash2, BookOpen } from 'lucide-react';
import { DataTable } from '../components/DataTable';
import { Modal } from '../components/Modal';
import { mataKuliahApi, dosenApi, programStudiApi } from '../utils/api';

export const MataKuliahPage = () => {
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingItem, setEditingItem] = useState(null);
  const [formData, setFormData] = useState({ kode: '', nama: '', sks: 3, semester: 1, program_studi_id: '', dosen_pengampu_id: '', deskripsi: '' });
  const [dosenList, setDosenList] = useState([]);
  const [programStudiList, setProgramStudiList] = useState([]);
  const [submitting, setSubmitting] = useState(false);
  const [refreshKey, setRefreshKey] = useState(0);

  const fetchOptions = async () => {
    try {
      const [dos, prodi] = await Promise.all([dosenApi.getAll(), programStudiApi.getAll()]);
      setDosenList(dosenList || dos.data || dos);
      setProgramStudiList(programStudiList || prodi.data || prodi);
    } catch (e) {
      setDosenList([{ id: 1, full_name: 'Dr. Ahmad Fauzi' }, { id: 2, full_name: 'Prof. Siti Aminah' }]);
      setProgramStudiList([{ id: 1, nama: 'Teknik Informatika' }, { id: 2, nama: 'Sistem Informasi' }]);
    }
  };

  const openCreate = () => {
    setEditingItem(null);
    setFormData({ kode: '', nama: '', sks: 3, semester: 1, program_studi_id: '', dosen_pengampu_id: '', deskripsi: '' });
    fetchOptions();
    setIsFormOpen(true);
  };

  const openEdit = (item) => {
    setEditingItem(item);
    setFormData({
      kode: item.kode || '',
      nama: item.nama || item.name || '',
      sks: item.sks || 3,
      semester: item.semester || 1,
      program_studi_id: item.program_studi_id || item.programStudi?.id || '',
      dosen_pengampu_id: item.dosen_pengampu_id || item.dosenPengampu?.id || '',
      deskripsi: item.deskripsi || '',
    });
    fetchOptions();
    setIsFormOpen(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      const payload = { ...formData, sks: parseInt(formData.sks), semester: parseInt(formData.semester) };
      if (editingItem) await mataKuliahApi.update(editingItem.id, payload);
      else await mataKuliahApi.create(payload);
      setIsFormOpen(false);
      setRefreshKey(k => k + 1);
    } catch (err) { alert(err.response?.data?.message || 'Gagal menyimpan data'); }
    finally { setSubmitting(false); }
  };

  const handleDelete = async (id) => {
    if (!confirm('Yakin ingin menghapus mata kuliah ini?')) return;
    try { await mataKuliahApi.delete(id); setRefreshKey(k => k + 1); }
    catch (err) { alert(err.response?.data?.message || 'Gagal menghapus data'); }
  };

  const fetchData = useCallback(async ({ page = 1, perPage = 10, search = '' }) => {
    const params = { page, per_page: perPage };
    if (search) params.search = search;
    return mataKuliahApi.getAll(params);
  }, []);

  const columns = [
    { key: 'kode', label: 'Kode MK' },
    { key: 'nama', label: 'Nama Mata Kuliah', render: (val, row) => row.nama || row.name || val || '—' },
    { key: 'sks', label: 'SKS' },
    { key: 'semester', label: 'Semester' },
    { key: 'program_studi', label: 'Program Studi', render: (val, row) => row.programStudi?.nama || row.program_studi?.name || val || '—' },
    { key: 'dosen_pengampu', label: 'Dosen Pengampu', render: (val, row) => {
      const dosen = row.dosenPengampu || row.dosen_pengampu;
      return dosen?.full_name || dosen?.nama || val || '—';
    }},
  ];

  return (
    <div className="dashboard-grid" style={{ gap: '18px' }}>
      <div className="card section-card">
        <div className="kicker">Modul Akademik</div>
        <h1 className="page-title" style={{ fontSize: '2rem', marginTop: 8 }}>Kelola Mata Kuliah</h1>
        <p className="page-subtitle">Kelola daftar mata kuliah, SKS, kelas, dan relasi pengajar.</p>
        <div className="actions-row" style={{ marginTop: 18 }}>
          <button className="btn btn-primary" type="button" onClick={openCreate}><Plus size={16} style={{ marginRight: 6 }} /> Tambah Mata Kuliah</button>
        </div>
      </div>

      <div className="dashboard-grid stats">
        <div className="card stat-card">
          <div className="badge"><BookOpen size={16} /> Total Mata Kuliah</div>
          <div className="value" style={{ fontSize: '1.7rem' }}>328</div>
          <div className="foot">Semester berjalan</div>
        </div>
      </div>

      <DataTable key={refreshKey} columns={columns} onFetchData={fetchData} emptyMessage="Belum ada data mata kuliah"
        actions={(row) => (
          <div style={{ display: 'flex', gap: '6px' }}>
            <button className="btn btn-ghost" onClick={() => openEdit(row)} style={{ padding: '6px 10px' }}><Edit2 size={14} /></button>
            <button className="btn btn-ghost" onClick={() => handleDelete(row.id)} style={{ padding: '6px 10px', color: 'var(--danger)' }}><Trash2 size={14} /></button>
          </div>
        )} />

      <Modal isOpen={isFormOpen} onClose={() => setIsFormOpen(false)} title={editingItem ? 'Edit Mata Kuliah' : 'Tambah Mata Kuliah'} size="lg"
        footer={<>
          <button className="btn btn-ghost" onClick={() => setIsFormOpen(false)}>Batal</button>
          <button className="btn btn-primary" onClick={handleSubmit} disabled={submitting}>{submitting ? 'Menyimpan...' : editingItem ? 'Update' : 'Simpan'}</button>
        </>}>
        <form onSubmit={handleSubmit} className="form-grid">
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
            <div className="form-field"><label>Kode Mata Kuliah</label><input type="text" value={formData.kode} onChange={e => setFormData({...formData, kode: e.target.value})} placeholder="Contoh: TIF101" required /></div>
            <div className="form-field"><label>Nama Mata Kuliah</label><input type="text" value={formData.nama} onChange={e => setFormData({...formData, nama: e.target.value})} placeholder="Contoh: Algoritma dan Pemrograman" required /></div>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
            <div className="form-field"><label>SKS</label><select value={formData.sks} onChange={e => setFormData({...formData, sks: e.target.value})}>
              {[1,2,3,4,6].map(s => <option key={s} value={s}>{s} SKS</option>)}
            </select></div>
            <div className="form-field"><label>Semester</label><select value={formData.semester} onChange={e => setFormData({...formData, semester: e.target.value})}>
              {[1,2,3,4,5,6,7,8].map(s => <option key={s} value={s}>Semester {s}</option>)}
            </select></div>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
            <div className="form-field"><label>Program Studi</label><select value={formData.program_studi_id} onChange={e => setFormData({...formData, program_studi_id: e.target.value})} required><option value="">Pilih Program Studi</option>{programStudiList.map(p => <option key={p.id} value={p.id}>{p.nama || p.name}</option>)}</select></div>
            <div className="form-field"><label>Dosen Pengampu</label><select value={formData.dosen_pengampu_id} onChange={e => setFormData({...formData, dosen_pengampu_id: e.target.value})}><option value="">Pilih Dosen</option>{dosenList.map(d => <option key={d.id} value={d.id}>{d.full_name || d.nama || d.name}</option>)}</select></div>
          </div>
          <div className="form-field"><label>Deskripsi</label><textarea value={formData.deskripsi} onChange={e => setFormData({...formData, deskripsi: e.target.value})} rows={4} placeholder="Deskripsi mata kuliah..." style={{ borderRadius: '14px', border: '1px solid var(--line)', background: 'rgba(15, 23, 42, 0.8)', color: 'var(--text)', padding: '13px 14px', fontFamily: 'inherit' }} /></div>
        </form>
      </Modal>
    </div>
  );
};

export default MataKuliahPage;
