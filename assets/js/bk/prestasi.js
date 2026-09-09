(() => {
  'use strict';

  const app = document.getElementById('prestasiApp');
  if (!app) return;

  const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
  const body = document.getElementById('prestasiBody');
  const form = document.getElementById('formPrestasi');
  const alertBox = document.getElementById('prestasiAlert');
  const modalEl = document.getElementById('modalPrestasi');
  let offset = 0;
  let total = 0;
  const limit = 50;

  const esc = (value) => {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
  };

  const today = () => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
  };

  const params = () => {
    const p = new URLSearchParams({ format: 'json', limit: String(limit), offset: String(offset) });
    [
      ['search', 'prestasiSearch'],
      ['tingkat', 'prestasiTingkat'],
      ['tanggal_mulai', 'prestasiMulai'],
      ['tanggal_selesai', 'prestasiSelesai'],
    ].forEach(([key, id]) => {
      const value = document.getElementById(id)?.value;
      if (value) p.set(key, value);
    });
    return p;
  };

  function show(message, type = 'danger') {
    if (!alertBox) return;
    alertBox.className = `alert alert-${type}`;
    alertBox.textContent = message;
  }

  async function requestJson(url, options = {}) {
    const response = await fetch(url, {
      ...options,
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.headers || {}),
      },
    });
    const payload = await response.json();
    if (!response.ok || payload.status !== 'success') {
      throw new Error(payload.message || 'Proses gagal.');
    }
    return payload;
  }

  function setStudent(row = null) {
    if (!form) return;
    const select = form.elements.id_siswa;
    if (row) {
      window.SisfourSearchableSelect?.setValue(
        select,
        row.id_siswa,
        `${row.nisn} — ${row.nama_siswa}`
      );
    } else {
      window.SisfourSearchableSelect?.setValue(select, '', '');
    }
  }

  function openForm(row = null) {
    if (!form || !modalEl) return;
    form.reset();
    form.elements.id.value = row?.id || '';
    setStudent(row);
    form.elements.nama_prestasi.value = row?.nama_prestasi || '';
    form.elements.tingkat.value = row?.tingkat || 'Madrasah';
    form.elements.tanggal.value = row?.tanggal || today();
    form.elements.penyelenggara.value = row?.penyelenggara || '';
    form.elements.keterangan.value = row?.keterangan || '';
    document.getElementById('judulModalPrestasi').textContent = row ? 'Edit Prestasi Siswa' : 'Tambah Prestasi Siswa';
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  }

  async function load() {
    try {
      const payload = await requestJson(`${base}/bk/prestasi?${params()}`);
      const data = payload.data || {};
      total = Number(data.total || 0);
      const manage = Boolean(data.can_manage);

      body.innerHTML = (data.rows || []).map((row) => `
        <tr data-json="${encodeURIComponent(JSON.stringify(row))}">
          <td>${esc(row.tanggal)}</td>
          <td>${esc(row.nisn)}<br><strong>${esc(row.nama_siswa)}</strong></td>
          <td>${esc(row.nama_prestasi)}</td>
          <td>${esc(row.tingkat)}</td>
          <td>${esc(row.penyelenggara || '-')}</td>
          <td>${esc(row.keterangan || '-')}</td>
          ${manage ? `
            <td class="text-nowrap">
              <button type="button" class="btn btn-sm btn-outline-primary btn-edit-prestasi">Edit</button>
              <button type="button" class="btn btn-sm btn-outline-danger btn-delete-prestasi">Hapus</button>
            </td>
          ` : ''}
        </tr>
      `).join('') || `
        <tr><td colspan="${manage ? 7 : 6}" class="text-center text-muted py-4">Tidak ada data.</td></tr>
      `;

      document.getElementById('prestasiInfo').textContent = `${total ? offset + 1 : 0}-${Math.min(offset + limit, total)} dari ${total}`;
      document.getElementById('prestasiPrev').disabled = offset <= 0;
      document.getElementById('prestasiNext').disabled = offset + limit >= total;
      bindRows();
    } catch (error) {
      show(error.message || 'Gagal memuat data.');
    }
  }

  function bindRows() {
    document.querySelectorAll('.btn-edit-prestasi').forEach((button) => {
      button.addEventListener('click', () => {
        openForm(JSON.parse(decodeURIComponent(button.closest('tr').dataset.json)));
      });
    });

    document.querySelectorAll('.btn-delete-prestasi').forEach((button) => {
      button.addEventListener('click', async () => {
        const row = JSON.parse(decodeURIComponent(button.closest('tr').dataset.json));
        if (!confirm(`Hapus prestasi ${row.nama_siswa}?`)) return;
        try {
          const payload = await requestJson(`${base}/bk/prestasi/delete/${row.id}`, { method: 'DELETE' });
          show(payload.message || 'Prestasi berhasil dihapus.', 'success');
          load();
        } catch (error) {
          show(error.message || 'Gagal menghapus.');
        }
      });
    });
  }

  document.getElementById('btnPrestasiBaru')?.addEventListener('click', () => openForm());
  document.getElementById('btnPrestasiCari')?.addEventListener('click', () => {
    offset = 0;
    load();
  });
  document.getElementById('prestasiPrev')?.addEventListener('click', () => {
    offset = Math.max(0, offset - limit);
    load();
  });
  document.getElementById('prestasiNext')?.addEventListener('click', () => {
    if (offset + limit < total) {
      offset += limit;
      load();
    }
  });
  document.getElementById('btnPrestasiExport')?.addEventListener('click', (event) => {
    event.preventDefault();
    const p = params();
    p.delete('format');
    p.delete('limit');
    p.delete('offset');
    window.location.href = `${base}/bk/prestasi/export?${p}`;
  });

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const fd = new FormData(form);
    const id = String(fd.get('id') || '');
    fd.delete('id');

    let url = `${base}/bk/prestasi/create`;
    let options = { method: 'POST', body: fd };

    if (id) {
      url = `${base}/bk/prestasi/update/${id}`;
      options = {
        method: 'PUT',
        body: new URLSearchParams(fd),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      };
    }

    try {
      const payload = await requestJson(url, options);
      bootstrap.Modal.getInstance(modalEl)?.hide();
      show(payload.message || 'Prestasi berhasil disimpan.', 'success');
      load();
    } catch (error) {
      show(error.message || 'Gagal menyimpan.');
    }
  });

  load();
})();
