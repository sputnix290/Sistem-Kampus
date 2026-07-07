import React from 'react';

const createModulePage = (title, description) => () => (
  <div className="dashboard-grid" style={{ gap: '18px' }}>
    <div className="card section-card">
      <div className="kicker">Modul Akademik</div>
      <h1 className="page-title" style={{ fontSize: '2rem', marginTop: 8 }}>{title}</h1>
      <p className="page-subtitle">{description}</p>
    </div>

    <div className="dashboard-grid two-col">
      <div className="card section-card">
        <div className="kicker">Daftar Data</div>
        <div className="list-stack" style={{ marginTop: 16 }}>
          <div className="list-row"><span>Data utama belum dihubungkan</span><strong>Coming soon</strong></div>
          <div className="list-row"><span>Integrasi CRUD backend</span><strong>Siap disambungkan</strong></div>
          <div className="list-row"><span>Filter & pencarian</span><strong>Siap</strong></div>
        </div>
      </div>
      <div className="card section-card">
        <div className="kicker">Aksi Cepat</div>
        <div className="actions-row" style={{ marginTop: 16 }}>
          <button className="btn btn-primary" type="button">Tambah Data</button>
          <button className="btn btn-ghost" type="button">Impor</button>
          <button className="btn btn-ghost" type="button">Ekspor</button>
        </div>
      </div>
    </div>
  </div>
);

export const MahasiswaPage = createModulePage('Mahasiswa', 'Kelola data mahasiswa, status akademik, dan informasi administrasi dengan tampilan yang rapi.');
export const DosenPage = createModulePage('Dosen', 'Kelola data dosen, kepegawaian, dan pengampu mata kuliah dalam satu modul.');
export const ProgramStudiPage = createModulePage('Program Studi', 'Atur struktur program studi per fakultas dan pantau data akademik secara cepat.');
export const MataKuliahPage = createModulePage('Mata Kuliah', 'Kelola daftar mata kuliah, SKS, kelas, dan relasi pengajar.');
export const PengumumanPage = createModulePage('Pengumuman', 'Buat dan publikasikan pengumuman akademik untuk dosen dan mahasiswa.');
