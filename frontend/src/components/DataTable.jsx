import React, { useState, useEffect } from 'react';
import { Search, ChevronLeft, ChevronRight, Loader2, AlertCircle } from 'lucide-react';

/**
 * Generic DataTable component with pagination and loading states
 * 
 * @param {Object} props
 * @param {Array} props.data - Array of data items to display
 * @param {Array} props.columns - Column definitions: [{ key, label, render }]
 * @param {Function} props.onFetchData - Async function to fetch data with params { page, perPage, search }
 * @param {React.ReactNode} props.emptyMessage - Message to show when no data
 * @param {Function} props.onRowClick - Optional click handler for rows
 */
export const DataTable = ({ columns, onFetchData, emptyMessage = 'Tidak ada data', onRowClick, actions }) => {
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [perPage] = useState(10);
  const [totalPages, setTotalPages] = useState(1);

  useEffect(() => {
    const timer = setTimeout(() => fetchData(), 300);
    return () => clearTimeout(timer);
  }, [page, search]);

  const fetchData = async () => {
    setLoading(true);
    setError(null);
    try {
      const result = await onFetchData({ page, perPage, search });
      // Handle different response structures
      const items = result.mahasiswa?.data || result.dosen?.data || result.data || result;
      const meta = result.mahasiswa?.meta || result.dosen?.meta || result.meta;

      if (Array.isArray(items)) {
        setData(items);
        if (meta) {
          setTotalPages(Math.ceil(meta.total / meta.per_page));
        } else if (result.total_pages) {
          setTotalPages(result.total_pages);
        }
      } else {
        setData([]);
      }
    } catch (err) {
      setError(err.response?.data?.message || 'Gagal memuat data');
      setData([]);
    } finally {
      setLoading(false);
    }
  };

  const handleSearch = (e) => {
    setSearch(e.target.value);
    setPage(1);
  };

  const getStatusBadge = (status) => {
    const styles = {
      aktif: { bg: 'rgba(16, 185, 129, 0.15)', color: '#34d399', border: 'rgba(16, 185, 129, 0.3)' },
      published: { bg: 'rgba(59, 130, 246, 0.15)', color: '#60a5fa', border: 'rgba(59, 130, 246, 0.3)' },
      draft: { bg: 'rgba(245, 158, 11, 0.15)', color: '#fbbf24', border: 'rgba(245, 158, 11, 0.3)' },
      default: { bg: 'rgba(148, 163, 184, 0.15)', color: '#94a3b8', border: 'rgba(148, 163, 184, 0.3)' }
    };
    const s = styles[status?.toLowerCase()] || styles.default;
    return (
      <span style={{ padding: '4px 10px', borderRadius: '999px', background: s.bg, color: s.color, border: `1px solid ${s.border}`, fontSize: '0.78rem', fontWeight: 600 }}>
        {status || '—'}
      </span>
    );
  };

  return (
    <div style={{ display: 'grid', gap: '16px' }}>
      {/* Search Bar */}
      <div className="card" style={{ padding: '16px', display: 'flex', alignItems: 'center', gap: '12px' }}>
        <Search size={18} color="#94a3b8" />
        <input
          type="text"
          placeholder="Cari data..."
          value={search}
          onChange={handleSearch}
          style={{ flex: 1, border: 'none', background: 'transparent', color: 'var(--text)', fontSize: '0.9rem', outline: 'none' }}
        />
      </div>

      {/* Table */}
      <div className="card" style={{ overflow: 'hidden' }}>
        {error && (
          <div style={{ padding: '24px', display: 'flex', alignItems: 'center', gap: '12px', color: 'var(--danger)' }}>
            <AlertCircle size={20} />
            <span>{error}</span>
            <button className="btn btn-ghost" onClick={fetchData} style={{ marginLeft: 'auto' }}>Coba Lagi</button>
          </div>
        )}

        {loading ? (
          <div style={{ padding: '48px', display: 'flex', justifyContent: 'center' }}>
            <Loader2 size={24} style={{ animation: 'spin 1s linear infinite', color: 'var(--primary)' }} />
          </div>
        ) : (
          <>
            <div style={{ overflowX: 'auto' }}>
              <table style={{ width: '100%', borderCollapse: 'collapse' }}>
                <thead>
                  <tr style={{ borderBottom: '1px solid var(--line)' }}>
                    {columns.map((col) => (
                      <th key={col.key} style={{ padding: '14px 16px', textAlign: 'left', fontSize: '0.78rem', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.08em', color: 'var(--muted-2)' }}>
                        {col.label}
                      </th>
                    ))}
                    {actions && (
                      <th style={{ padding: '14px 16px', textAlign: 'left', fontSize: '0.78rem', fontWeight: 700, textTransform: 'uppercase', letterSpacing: '0.08em', color: 'var(--muted-2)' }}>Aksi</th>
                    )}
                  </tr>
                </thead>
                <tbody>
                  {data.length === 0 ? (
                    <tr><td colSpan={columns.length + (actions ? 1 : 0)} style={{ padding: '48px', textAlign: 'center', color: 'var(--muted)' }}>{emptyMessage}</td></tr>
                  ) : (
                    data.map((row, idx) => (
                      <tr
                        key={row.id || idx}
                        onClick={() => onRowClick?.(row)}
                        style={{ borderBottom: '1px solid var(--line)', cursor: onRowClick ? 'pointer' : 'default', transition: 'background 0.15s ease' }}
                        onMouseEnter={(e) => e.currentTarget.style.background = 'rgba(91, 124, 250, 0.06)'}
                        onMouseLeave={(e) => e.currentTarget.style.background = 'transparent'}
                      >
                        {columns.map((col) => (
                          <td key={col.key} style={{ padding: '14px 16px', color: 'var(--text)', fontSize: '0.9rem' }}>
                            {col.render ? col.render(row[col.key], row) : row[col.key] || '—'}
                          </td>
                        ))}
                        {actions && (
                          <td style={{ padding: '14px 16px' }}>
                            {actions(row)}
                          </td>
                        )}
                      </tr>
                    ))
                  )}
                </tbody>
              </table>
            </div>

            {/* Pagination */}
            {totalPages > 1 && (
              <div style={{ padding: '14px 16px', display: 'flex', justifyContent: 'center', gap: '8px', borderTop: '1px solid var(--line)' }}>
                <button className="btn btn-ghost" disabled={page === 1} onClick={() => setPage(p => p - 1)} style={{ padding: '8px 12px' }}>
                  <ChevronLeft size={16} />
                </button>
                {Array.from({ length: Math.min(5, totalPages) }, (_, i) => {
                  let pageNum;
                  if (totalPages <= 5) {
                    pageNum = i + 1;
                  } else if (page <= 3) {
                    pageNum = i + 1;
                  } else if (page >= totalPages - 2) {
                    pageNum = totalPages - 4 + i;
                  } else {
                    pageNum = page - 2 + i;
                  }
                  return (
                    <button
                      key={pageNum}
                      className={`btn ${page === pageNum ? 'btn-primary' : 'btn-ghost'}`}
                      onClick={() => setPage(pageNum)}
                      style={{ padding: '8px 12px', minWidth: '36px' }}
                    >
                      {pageNum}
                    </button>
                  );
                })}
                <button className="btn btn-ghost" disabled={page === totalPages} onClick={() => setPage(p => p + 1)} style={{ padding: '8px 12px' }}>
                  <ChevronRight size={16} />
                </button>
              </div>
            )}
          </>
        )}
      </div>

      <style>{`
        @keyframes spin {
          from { transform: rotate(0deg); }
          to { transform: rotate(360deg); }
        }
      `}</style>
    </div>
  );
};

export default DataTable;
