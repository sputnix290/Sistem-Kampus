import React from 'react';
import { Navigate, Route, Routes } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import Login from './pages/Login';
import { AppLayout } from './layouts/AppLayout';
import { ProtectedRoute } from './components/ProtectedRoute';
import { RoleRoute } from './components/RoleRoute';
import { DashboardAdmin } from './pages/DashboardAdmin';
import { DashboardDosen } from './pages/DashboardDosen';
import { DashboardMahasiswa } from './pages/DashboardMahasiswa';
import { MahasiswaPage } from './pages/Mahasiswa';
import { DosenPage } from './pages/Dosen';
import { ProgramStudiPage } from './pages/ProgramStudi';
import { MataKuliahPage } from './pages/MataKuliah';
import { PengumumanPage } from './pages/Pengumuman';
import { PengaturanPage } from './pages/Pengaturan';
import Dashboard from './pages/Dashboard';

const App = () => {
  return (
    <AuthProvider>
      <Routes>
        <Route path="/login" element={<Login />} />
        <Route element={<ProtectedRoute />}>
          <Route element={<AppLayout />}>
            <Route index element={<Navigate to="/login" replace />} />
            <Route path="/dashboard" element={<RoleRoute allowedRoles={["admin"]} fallbackTo="/dashboard/dosen" />}>
              <Route index element={<DashboardAdmin />} />
            </Route>
            <Route path="/dashboard/dosen" element={<RoleRoute allowedRoles={["dosen"]} fallbackTo="/dashboard/mahasiswa" />}>
              <Route index element={<DashboardDosen />} />
            </Route>
            <Route path="/dashboard/mahasiswa" element={<RoleRoute allowedRoles={["mahasiswa"]} fallbackTo="/dashboard" />}>
              <Route index element={<DashboardMahasiswa />} />
            </Route>
            <Route path="/mahasiswa" element={<RoleRoute allowedRoles={["admin"]} fallbackTo="/dashboard" />}>
              <Route index element={<MahasiswaPage />} />
            </Route>
            <Route path="/dosen" element={<RoleRoute allowedRoles={["admin"]} fallbackTo="/dashboard" />}>
              <Route index element={<DosenPage />} />
            </Route>
            <Route path="/program-studi" element={<RoleRoute allowedRoles={["admin"]} fallbackTo="/dashboard" />}>
              <Route index element={<ProgramStudiPage />} />
            </Route>
            <Route path="/mata-kuliah" element={<RoleRoute allowedRoles={["admin"]} fallbackTo="/dashboard" />}>
              <Route index element={<MataKuliahPage />} />
            </Route>
            <Route path="/pengumuman" element={<RoleRoute allowedRoles={["admin", "dosen"]} fallbackTo="/dashboard" />}>
              <Route index element={<PengumumanPage />} />
            </Route>
            <Route path="/pengaturan" element={<RoleRoute allowedRoles={["admin"]} fallbackTo="/dashboard" />}>
              <Route index element={<PengaturanPage />} />
            </Route>
            <Route path="/pembayaran-dashboard" element={<RoleRoute allowedRoles={["admin"]} fallbackTo="/dashboard" />}>
              <Route index element={<Dashboard />} />
            </Route>
          </Route>
        </Route>
        <Route path="*" element={<Navigate to="/login" replace />} />
      </Routes>
    </AuthProvider>
  );
};

export default App;
