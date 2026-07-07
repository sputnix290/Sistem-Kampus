import React, { useState, useCallback } from 'react';
import { Plus, Edit2, Trash2, Download, Users } from 'lucide-react';
import { DataTable } from '../components/DataTable';
import { Modal } from '../components/Modal';
import { mahasiswaApi, fakultasApi, programStudiApi } from '../utils/api';

export const MahasiswaPage = () => {
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingItem, setEditingItem] = useState(null);
  const [formData, setFormData] = useState({
    nim: '', full_name: '', email: '', password: '', faculty_id: '', study_program_id: '', angkatan: '', semester_aktif: 1, status: 'aktif'
  });
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
      setFakultasList([
        { id: 1, name: 'Fakultas Ilmu Komputer' },
        { id: 2, name: 'Fakultas Ekonomi' },
        { id: 3, name: 'Fakultas Hukum' },
      ]);
      setProgramStudiList([
        { id: 1, name: 'Teknik Informatika', fakultas_id: 1 },
        { id: 2, name: 'Sistem Informasi', fakultas_id: 1 },
        { id: 3, name: 'Manajemen', fakultas_id: 2 },
      ]);
    }
  };

  const openCreate = () => {
    setEditingItem(null);
    setFormData({ nim: '', full_name: '', email: '', password: '', faculty_id: '', study_program_id: '', angkatan: new Date().getFullYear(), semester_aktif: 1, status: 'aktif' });
    fetchOptions();
    setIsFormOpen(true);
  };

  const openEdit = (item) => {
    setEditingItem(item);
    setFormData({
      nim: item.nim || '',
      full_name: item.full_name || item.user?.name || '',
      email: item.email || item.user?.email || '',
      password: '',
      faculty_id: item.faculty_id || item.faculty?.id || '',
      study_program_id: item.study_program_id || item.studyProgram?.id || '',
      angkatan: item.angkatan || '',
      semester_aktif: item.semester_aktif || 1,
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
      if (editingItem) {
        await mahasiswaApi.update(editingItem.id, payload);
      } else {
        await mahasiswaApi.create(payload);
      }
      setIsFormOpen(false);
      setRefreshKey(k => k + 1);
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menyimpan data');
    } finally {
      setSubmitting(false);
    }
  };

  const handleDelete = async (id) => {
    if (!confirm('Yakin ingin menghapus mahasiswa ini?')) return;
    try {
      await mahasiswaApi.delete(id);
      setRefreshKey(k => k + 1);
    } catch (err) {
      alert(err.response?.data?.message || 'Gagal menghapus data');
    }
  };

  const fetchData = useCallback(async ({ page = 1, perPage = 10, search = '' }) => {
    const params = { page, per_page: perPage };
    if (search) params.search = search;
    return mahasiswaApi.getAll(params);
  }, []);

  const columns = [
    { key: 'nim', label: 'NIM' },
    { key: 'full_name', label: 'Nama Lengkap', render: (val, row) => row.full_name || row.user?.name || val || '—' },
    { key: 'faculty', label: 'Fakultas', render: (val, row) => row.faculty?.name || row.fakultas?.name || val || '—' },
    { key: 'study_program', label: 'Program Studi', render: (val, row) => row.studyProgram?.name || row.program_studi?.name || val || '—' },
    { key: 'angkatan', label: 'Angkatan' },
    { key: 'status', label: 'Status', render: (val) => (
      <span style={{ padding: '4px 10px', borderRadius: '999px', background: val === 'aktif' ? 'rgba(16,185,129,0.15)' : 'rgba(239,68,68,0.15)', color: val === 'aktif' ? '#34d399' : '#f87171', border: `1px solid ${val === 'aktif' ? 'rgba(16,185,129,0.3)' : 'rgba(239,68,68,0.3)'}`, fontSize: '0.78rem', fontWeight: 600 }}>
        {val || '—'}
      </span>
    )},
  ];

  return (
    <div className="dashboard-grid" style={{ gap: '18px' }}>
      {/* Header Card */}
      <div className="card section-card">
        <div className="kicker">Modul Akademik</div>
        <h1 className="page-title" style={{ fontSize: '2rem', marginTop: 8 }}>Kelola Mahasiswa</h1>
        <p className="page-subtitle">Tambah, edit, dan pantau data mahasiswa dengan tampilan yang rapi dan profesional.</p>
        <div className="actions-row" style={{ marginTop: 18 }}>
          <button className="btn btn-primary" type="button" onClick={openCreate}>
            <Plus size={16} style={{ marginRight: 6 }} /> Tambah Mahasiswa
          </button>
          <button className="btn btn-ghost" type="button">
            <Download size={16} style={{ marginRight: 6 }} /> Export
          </button>
        </div>
      </div>

      {/* Stats */}
      <div className="dashboard-grid stats">
        <div className="card stat-card">
          <div className="badge"><Users size={16} /> Total Mahasiswa</div>
          <div className="value" style={{ fontSize: '1.7rem' }}>2,418</div>
          <div className="foot">+8.2% dari bulan lalu</div>
        </div>
      </div>

      {/* Data Table */}
      <DataTable
        key={refreshKey}
        columns={columns}
        onFetchData={fetchData}
        emptyMessage="Belum ada data mahasiswa"
        actions={(row) => (
          <div style={{ display: 'flex', gap: '6px' }}>
            <button className="btn btn-ghost" onClick={() => openEdit(row)} style={{ padding: '6px 10px' }}>
              <Edit2 size={14} />
            </button>
            <button className="btn btn-ghost" onClick={() => handleDelete(row.id)} style={{ padding: '6px 10px', color: 'var(--danger)' }}>
              <Trash2 size={14} />
            </button>
          </div>
        )}
      />

      {/* Form Modal */}
      <Modal
        isOpen={isFormOpen}
        onClose={() => setIsFormOpen(false)}
        title={editingItem ? 'Edit Mahasiswa' : 'Tambah Mahasiswa'}
        size="lg"
        footer={
          <>
            <button className="btn btn-ghost" onClick={() => setIsFormOpen(false)}>Batal</button>
            <button className="btn btn-primary" onClick={handleSubmit} disabled={submitting}>
              {submitting ? 'Menyimpan...' : editingItem ? 'Update' : 'Simpan'}
            </button>
          </>
        }
      >
        <form onSubmit={handleSubmit} className="form-grid">
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
            <div className="form-field">
              <label>NIM</label>
              <input type="text" value={formData.nim} onChange={e => setFormData({...formData, nim: e.target.value})} placeholder="Contoh: 2024001" required />
            </div>
            <div className="form-field">
              <label>Nama Lengkap</label>
              <input type="text" value={formData.full_name} onChange={e => setFormData({...formData, full_name: e.target.value})} placeholder="Nama lengkap mahasiswa" required />
            </div>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
            <div className="form-field">
              <label>Email</label>
              <input type="email" value={formData.email} onChange={e => setFormData({...formData, email: e.target.value})} placeholder="nama@kampus.ac.id" required />
            </div>
            <div className="form-field">
              <label>{editingItem ? 'Password (opsional)' : 'Password'}</label>
              <input type="password" value={formData.password} onChange={e => setFormData({...formData, password: e.target.value})} placeholder={editingItem ? 'Kosongkan jika tidak diubah' : 'Min. 8 karakter'} required={!editingItem} />
            </div>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '14px' }}>
            <div className="form-field">
              <label>Fakultas</label>
              <select value={formData.faculty_id} onChange={e => setFormData({...formData, faculty_id: e.target.value})} required>
                <option value="">Pilih Fakultas</option>
                {fakultasList.map(f => <option key={f.id} value={f.id}>{f.name}</option>)}
              </select>
            </div>
            <div className="form-field">
              <label>Program Studi</label>
              <select value={formData.study_program_id} onChange={e => setFormData({...formData, study_program_id: e.target.value})} required>
                <option value="">Pilih Program Studi</option>
                {programStudiList.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
              </select>
            </div>
          </div>
          <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr 1fr', gap: '14px' }}>
            <div className="form-field">
              <label>Angkatan</label>
              <input type="number" value={formData.angkatan} onChange={e => setFormData({...formData, angkatan: e.target.value})} placeholder="Tahun angkatan" required />
            </div>
            <div className="form-field">
              <label>Semester Aktif</label>
              <select value={formData.semester_aktif} onChange={e => setFormData({...formData, semester_aktif: e.target.value})}>
                {[1,2,3,4,5,6,7,8].map(s => <option key={s} value={s}>Semester {s}</option>)}
              </select>
            </div>
            <div className="form-field">
              <label>Status</label>
              <select value={formData.status} onChange={e => setFormData({...formData, status: e.target.value})}>
                <option value="aktif">Aktif</option>
                <option value="cuti">Cuti</option>
                <option value="lulus">Lulus</option>
                <option value="keluar">Keluar</option>
              </select>
            </div>
          </div>
        </form>
      </Modal>
    </div>
  );
};

export default MahasiswaPage;
