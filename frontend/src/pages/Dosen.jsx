import React, { useState, useCallback } from 'react';
import { Plus, Edit2, Trash2, Download, ClipboardList } from 'lucide-react';
import { DataTable } from '../components/DataTable';
import { Modal } from '../components/Modal';
import { dosenApi, fakultasApi, programStudiApi } from '../utils/api';

export const DosenPage = () => {
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingItem, setEditingItem] = useState(null);
  const [formData, setFormData] = useState({ nip: '', full_name: '', email: '', password: '', faculty_id: '', study_program_id: '', jabatan: '', status: 'aktif' });
  const [fakultasList, setFakultasList] = useState([]);
  const [programStudiList, setProgramStudiList] = useState([]);
  const [submitting, setSubmitting] = useState(false);
  const [refreshKey, setRefreshKey] = useState(0);

  const fetchOptions = async () => {
    try {
      const [fak, prodi] = await Promise.all([fakultasApi.getAll(), programStudiApi.getAll()]);
      setFakultasList(fakultasList || fak.data || fak);
      setProgramStudiList(programStudiList || prodi.data || prodi);
    } catch (e) {
      setFakultasList([{ id: 1, name: 'Fakultas Ilmu Komputer' }, { id: 2, name: 'Fakultas Ekonomi' }, { id: 3, name: 'Fakultas Hukum' }]);
      setProgramStudiList([{ id: 1, name: 'Teknik Informatika' }, { id: 2, name: 'Sistem Informasi' }, { id: 3, name: 'Manajemen' }]);
    }
  };

  const openCreate = () => {
    setEditingItem(null);
    setFormData({ nip: '', full_name: '', email: '', password: '', faculty_id: '', study_program_id: '', jabatan: '', status: 'aktif' });
    fetchOptions();
    setIsFormOpen(true);
  };

  const openEdit = (item) => {
    setEditingItem(item);
    setFormData({
      nip: item.nip || '',
      full_name: item.full_name || item.user?.name || '',
      email: item.email || item.user?.email || '',
      password: '',
      faculty_id: item.faculty_id || item.faculty?.id || '',
      study_program_id: item.study_program_id || item.studyProgram?.id || '',
      jabatan: item.jabatan || '',
      status: item.status || 'aktif',
    });
    fetchOptions();
    setIsFormOpen(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);
    try {
      const payload = { ...formData };
      if (!payload.password && editingItem) delete payload.password;
      if (editingItem) await dosenApi.update(editingItem.id, payload);
      else await dosenApi.create(payload);
      setIsFormOpen(false);
      setRefreshKey(k => k + 1);
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menyimpan data');
    } finally { setSubmitting(false); }
  };

  const handleDelete = async (id) => {
    if (!confirm('Yakin ingin menghapus dosen ini?')) return;
    try { await dosenApi.delete(id); setRefreshKey(k => k + 1); }
    catch (err) { alert(err.response?.data?.message || 'Gagal menghapus data'); }
  };

  const fetchData = useCallback(async ({ page = 1, perPage = 10, search = '' }) => {
    const params = { page, per_page: perPage };
    if (search) params.search = search;
    return dosenApi.getAll(params);
  }, []);

  const columns = [
    { key: 'nip', label: 'NIP' },
    { key: 'full_name', label: 'Nama Lengkap', render: (val, row) => row.full_name || row.user?.name || val || '—' },
    { key: 'faculty', label: 'Fakultas', render: (val, row) => row.faculty?.name || row.fakultas?.name || val || '—' },
    { key: 'jabatan', label: 'Jabatan' },
    { key: 'status', label: 'Status', render: (val) => (
      <span style={{ padding: '4px 10px', borderRadius: '999px', background: val === 'aktif' ? 'rgba(16,185,129,0.15)' : 'rgba(239,68,68,0.15)', color: val === 'aktif' ? '#34d399' : '#f87171', border: `1px solid ${val === 'aktif' ? 'rgba(16,185,129,0.3)' : 'rgba(239,68,68,0.3)'}`, fontSize: '0.78rem', fontWeight: 600 }}>{val || '—'}</span>
    )},
  ];

  return (
    <div className="dashboard-grid" style={{ gap: '18px' }}>
      <div className="card section-card">
        <div className="kicker">Modul Akademik</div>
        <h1 className="page-title" style={{ fontSize: '2rem', marginTop: 8 }}>Kelola Dosen</h1>
        <p className="page-subtitle">Kelola data dosen, kepegawaian, dan pengampu mata kuliah dalam satu modul.</p>
        <div className="actions-row" style={{ marginTop: 18 }}>
          <button className="btn btn-primary" type="button" onClick={openCreate}><Plus size={16} style={{ marginRight: 6 }} /> Tambah Dosen</button>
          <button className="btn btn-ghost" type="button"><Download size={16} style={{ marginRight: 6 }} /> Export</button>
        </div>
      </div>

      <div className="dashboard-grid stats">
        <div className="card stat-card">
          <div className="badge"><ClipboardList size={16} /> Total Dosen</div>
          <div className="value" style={{ fontSize: '1.7rem' }}>146</div>
          <div className="foot">12 fakultas</div>
        </div>
      </div>

      <DataTable key={refreshKey} columns={columns} onFetchData={fetchData} emptyMessage="Belum ada data dosen"
        actions={(row) => (
          <div style={{ display: 'flex', gap: '6px' }}>
            <button className="btn btn-ghost" onClick={() => openEdit(row)} style={{ padding: '6px 10px' }}><Edit2 size={14} /></button>
            <button className="btn btn-ghost" onClick={() => handleDelete(row.id)} style={{ padding: '6px 10px', color: 'var(--danger)' }}><Trash2 size={14} /></button>
          </div>
        )} />

      <Modal isOpen={isFormOpen} onClose={() => setIsFormOpen(false)} title={editingItem ? 'Edit Dosen' : 'Tambah Dosen'} size="lg"
        footer={<>
          <button className="btn btn-ghost" onClick={() => setIsFormOpen(false)}>Batal</button>
          <button className="btn btn-primary" onClick={handleSubmit} disabled={submitting}>{submitting ? 'Menyimpan...' : editingItem ? 'Update' : 'Simpan'}</button>
        </>}>
        <form onSubmit={handleSubmit} className="form-grid">
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
            <div className="form-field"><label>NIP</label><input type="text" value={formData.nip} onChange={e => setFormData({...formData, nip: e.target.value})} placeholder="Contoh: 198501012010011001" required /></div>
            <div className="form-field"><label>Nama Lengkap</label><input type="text" value={formData.full_name} onChange={e => setFormData({...formData, full_name: e.target.value})} placeholder="Nama lengkap dosen" required /></div>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
            <div className="form-field"><label>Email</label><input type="email" value={formData.email} onChange={e => setFormData({...formData, email: e.target.value})} placeholder="dosen@kampus.ac.id" required /></div>
            <div className="form-field"><label>{editingItem ? 'Password (opsional)' : 'Password'}</label><input type="password" value={formData.password} onChange={e => setFormData({...formData, password: e.target.value})} placeholder={editingItem ? 'Kosongkan jika tidak diubah' : 'Min. 8 karakter'} required={!editingItem} /></div>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
            <div className="form-field"><label>Fakultas</label><select value={formData.faculty_id} onChange={e => setFormData({...formData, faculty_id: e.target.value})} required><option value="">Pilih Fakultas</option>{fakultasList.map(f => <option key={f.id} value={f.id}>{f.name}</option>)}</select></div>
            <div className="form-field"><label>Program Studi</label><select value={formData.study_program_id} onChange={e => setFormData({...formData, study_program_id: e.target.value})} required><option value="">Pilih Program Studi</option>{programStudiList.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}</select></div>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
            <div className="form-field"><label>Jabatan</label><input type="text" value={formData.jabatan} onChange={e => setFormData({...formData, jabatan: e.target.value})} placeholder="Contoh: Lektor Kepala" /></div>
            <div className="form-field"><label>Status</label><select value={formData.status} onChange={e => setFormData({...formData, status: e.target.value})}><option value="aktif">Aktif</option><option value="tidak aktif">Tidak Aktif</option><option value="pensiun">Pensiun</option></select></div>
          </div>
        </form>
      </Modal>
    </div>
  );
};

export default DosenPage;
