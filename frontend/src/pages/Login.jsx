import React, { useContext, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import AuthContext from '../context/AuthContext';
import './Login.css';

const Login = () => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  const { login } = useContext(AuthContext);
  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();
    setIsLoading(true);
    setError('');

    try {
      const data = await login(email, password);
      const role = data?.user?.role || data?.role;

      if (role === 'admin') navigate('/dashboard', { replace: true });
      else if (role === 'dosen') navigate('/dashboard/dosen', { replace: true });
      else navigate('/dashboard/mahasiswa', { replace: true });
    } catch (err) {
      setError('Login gagal. Periksa kembali email dan kata sandi Anda.');
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="login-screen">
      <div className="card login-card">
        <section className="login-hero">
          <div className="badge">Portal Akademik</div>
          <h1 className="login-title">Selamat datang di portal kampus <span className="brand-highlight">Wiwok Detok University</span></h1>
          <p className="login-desc">
            Kampus WDU (Wiwok Detok University) adalah kampus yang berintegritas yang memiliki semangat Hidup Jokowi!!!
          </p>
          <div className="dashboard-grid stats" style={{ marginTop: '28px', gridTemplateColumns: 'repeat(2, minmax(0, 1fr))' }}>
            <div className="card stat-card">
              <div className="label">National Ranking</div>
              <div className="value" style={{ fontSize: '1.7rem' }}>1st</div>
            </div>
            <div className="card stat-card">
              <div className="label">World Ranking</div>
              <div className="value" style={{ fontSize: '1.7rem' }}>146th</div>
            </div>
          </div>
        </section>

        <section className="login-panel">
          <div className="kicker">Masuk ke akun Anda</div>
          <h2 className="page-title" style={{ fontSize: '2rem', marginTop: 8 }}>Selamat datang kembali</h2>
          <p className="page-subtitle">Silakan masuk menggunakan akun kampus Anda untuk melanjutkan.</p>

          {error && <div className="card" style={{ padding: '14px 16px', marginTop: '18px', borderColor: 'rgba(239, 68, 68, 0.35)', color: '#fecaca' }}>{error}</div>}

          <form onSubmit={handleSubmit} className="login-form">
            <div className="form-field">
              <label htmlFor="email">Email / Username</label>
              <input
                type="email"
                id="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="nama@kampus.ac.id"
                autoComplete="email"
                required
              />
            </div>

            <div className="form-field">
              <label htmlFor="password">Kata Sandi</label>
              <input
                type="password"
                id="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="Masukkan kata sandi"
                autoComplete="current-password"
                required
              />
            </div>

            <div className="actions-row" style={{ justifyContent: 'space-between', alignItems: 'center' }}>
              <label className="badge" style={{ cursor: 'pointer' }}>
                <input type="checkbox" style={{ width: 'auto' }} />
                Ingat saya
              </label>
              <a href="#" style={{ color: '#23262dff', fontWeight: 600 }}>Lupa sandi?</a>
            </div>

            <button type="submit" className="btn btn-primary" disabled={isLoading} style={{ width: '100%' }}>
              {isLoading ? 'Memproses...' : 'Masuk ke Portal'}
            </button>
          </form>
        </section>
      </div>
    </div>
  );
};

export default Login;

