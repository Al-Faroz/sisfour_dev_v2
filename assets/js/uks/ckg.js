(() => {
  'use strict';

  const app = document.getElementById('uksCkgApp');
  if (!app) return;

  const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
  const canManage = app.dataset.manage === '1';
  const body = document.getElementById('ckgBody');
  const mobile = document.getElementById('ckgMobileList');
  const alertBox = document.getElementById('ckgAlert');
  const form = document.getElementById('formCkg');
  const importForm = document.getElementById('formCkgImport');
  const modalEl = document.getElementById('modalCkg');
  const importModalEl = document.getElementById('modalCkgImport');
  const state = { limit: 25, offset: 0, total: 0 };

  if (!body || !mobile) return;

  const pager = window.SisfourPagination?.mount(body, {
    id: 'uksCkgPager',
    label: 'CKG',
    onChange: (next) => {
      state.limit = next.limit;
      state.offset = next.offset;
      load();
    },
  });

  const esc = (value) => {
    const el = document.createElement('div');
    el.textContent = value ?? '';
    return el.innerHTML;
  };

  const today = () => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
  };

  function show(message, type = 'danger') {
    if (!alertBox) return;
    alertBox.className = `alert alert-${type}`;
    alertBox.textContent = message;
  }

  function setBusy(button, busy, label = 'Memproses...') {
    if (!button) return;
    if (busy) {
      if (button.dataset.busy === '1') return;
      button.dataset.busy = '1';
      button.dataset.html = button.innerHTML;
      button.disabled = true;
      button.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>${label}`;
      return;
    }
    button.disabled = false;
    if (button.dataset.html !== undefined) button.innerHTML = button.dataset.html;
    delete button.dataset.busy;
    delete button.dataset.html;
  }

  async function requestJson(url, options = {}) {
    const response = await fetch(url, {
      ...options,
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.headers || {}),
      },
      credentials: 'same-origin',
    });

    let payload;
    try { payload = await response.json(); } catch { throw new Error('Response server tidak valid.'); }
    if (!response.ok || payload.status !== 'success') {
      throw new Error(payload.message || 'Proses gagal.');
    }
    return payload;
  }

  function params(withPaging = true) {
    const p = new URLSearchParams({ format: 'json' });
    const map = {
      id_tahun: 'ckgTahun',
      id_kelas: 'ckgKelas',
      search: 'ckgSearch',
      tanggal_mulai: 'ckgMulai',
      tanggal_selesai: 'ckgSelesai',
    };
    Object.entries(map).forEach(([key, id]) => {
      const value = document.getElementById(id)?.value;
      if (value) p.set(key, value);
    });
    if (withPaging) {
      p.set('limit', String(state.limit));
      p.set('offset', String(state.offset));
    }
    return p;
  }

  function syncUrl() {
    const p = params(true);
    p.delete('format');
    const q = p.toString();
    history.replaceState(null, '', `${location.pathname}${q ? `?${q}` : ''}`);
  }

  function renderClassOptions(rows) {
    const select = document.getElementById('ckgKelas');
    if (!select) return;
    const current = select.value;
    select.innerHTML = '<option value="">Semua kelas</option>' + (rows || [])
      .map((row) => `<option value="${Number(row.id)}">${esc(row.nama_kelas)}</option>`)
      .join('');
    if ([...select.options].some((opt) => opt.value === current)) select.value = current;
  }

  function render(rows, manage) {
    const empty = `<tr><td colspan="${manage ? 7 : 6}" class="text-center text-muted py-4">Tidak ada data.</td></tr>`;
    body.innerHTML = (rows || []).map((row) => {
      const status = [row.status_gizi, row.status_tinggi].filter(Boolean).join(' · ') || '-';
      const pressure = [row.tekanan_sistol, row.tekanan_diastol].filter((v) => v !== null && v !== '').join('/');
      return `<tr data-json="${encodeURIComponent(JSON.stringify(row))}">
        <td>${esc(row.tanggal)}</td>
        <td><strong>${esc(row.nama_siswa)}</strong><div class="small text-muted">${esc(row.nisn)}</div></td>
        <td>${esc(row.nama_kelas || '-')}</td>
        <td>${esc(row.berat_badan ?? '-')} / ${esc(row.tinggi_badan ?? '-')}</td>
        <td>${esc(status)}</td>
        <td>${esc(pressure || '-')}<div class="small text-muted">Gula: ${esc(row.gula_darah ?? '-')}</div></td>
        ${manage ? '<td class="text-nowrap"><button class="btn btn-sm btn-outline-primary sisfour-touch-target--compact btn-ckg-edit" type="button">Edit</button> <button class="btn btn-sm btn-outline-danger sisfour-touch-target--compact btn-ckg-delete" type="button">Hapus</button></td>' : ''}
      </tr>`;
    }).join('') || empty;

    mobile.innerHTML = (rows || []).map((row) => `<div class="list-group-item py-3" data-json="${encodeURIComponent(JSON.stringify(row))}">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
        <div class="min-w-0 flex-grow-1">
          <div class="fw-semibold text-wrap">${esc(row.nama_siswa)}</div>
          <div class="small text-muted text-wrap">${esc(row.nisn)} · ${esc(row.nama_kelas || '-')}</div>
        </div>
        <span class="badge bg-label-primary flex-shrink-0">${esc(row.tanggal)}</span>
      </div>
      <div class="small mt-2 text-wrap">BB/TB: ${esc(row.berat_badan ?? '-')} / ${esc(row.tinggi_badan ?? '-')}</div>
      <div class="small text-muted text-wrap">${esc([row.status_gizi, row.status_tinggi].filter(Boolean).join(' · ') || '-')}</div>
      ${manage ? '<div class="sisfour-mobile-actions mt-3"><button class="btn btn-sm btn-outline-primary sisfour-touch-target--compact btn-ckg-edit" type="button">Edit</button><button class="btn btn-sm btn-outline-danger sisfour-touch-target--compact btn-ckg-delete" type="button">Hapus</button></div>' : ''}
    </div>`).join('') || '<div class="list-group-item sisfour-mobile-state text-muted">Tidak ada data.</div>';

    bindRows();
  }

  function rowData(button) {
    const holder = button.closest('[data-json]');
    return holder ? JSON.parse(decodeURIComponent(holder.dataset.json)) : null;
  }

  function setStudent(row) {
    if (!form) return;
    window.SisfourSearchableSelect?.setValue(
      form.elements.id_siswa,
      row?.id_siswa || '',
      row ? `${row.nama_siswa} — ${row.nisn}` : ''
    );
  }

  function openForm(row = null) {
    if (!form || !modalEl) return;
    form.reset();
    form.elements.id.value = row?.id || '';
    setStudent(row);
    form.elements.tanggal.value = row?.tanggal || today();

    [
      'berat_badan','tinggi_badan','status_gizi','status_tinggi','lingkar_perut',
      'tekanan_sistol','tekanan_diastol','gula_darah','kondisi_gigi_mulut',
      'visus_kanan','visus_kiri','buta_warna','hasil_pendengaran',
      'skrining_talasemia','skrining_tuberkulosis'
    ].forEach((name) => {
      if (form.elements[name]) form.elements[name].value = row?.[name] ?? '';
    });

    document.getElementById('judulModalCkg').textContent = row ? 'Edit Data CKG' : 'Tambah Data CKG';
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  }

  async function load() {
    pager?.setDisabled(true);
    try {
      const payload = await requestJson(`${base}/uks/ckg?${params(true)}`);
      const data = payload.data || {};
      const rows = Array.isArray(data.rows) ? data.rows : [];
      state.total = Number(data.total || 0);
      state.limit = Number(data.limit || state.limit);
      state.offset = Number(data.offset ?? state.offset);
      renderClassOptions(data.kelas_options || []);
      if (!rows.length && state.total > 0 && state.offset >= state.total) {
        state.offset = Math.floor((state.total - 1) / state.limit) * state.limit;
        await load();
        return;
      }
      render(rows, Boolean(data.can_manage));
      pager?.render(state);
      syncUrl();
    } catch (error) {
      show(error.message || 'Gagal memuat Data CKG.');
    } finally {
      pager?.setDisabled(false);
    }
  }

  function bindRows() {
    document.querySelectorAll('.btn-ckg-edit').forEach((button) => {
      button.addEventListener('click', () => {
        const row = rowData(button);
        if (row) openForm(row);
      });
    });

    document.querySelectorAll('.btn-ckg-delete').forEach((button) => {
      button.addEventListener('click', async () => {
        const row = rowData(button);
        if (!row || button.dataset.busy === '1') return;
        if (!window.Swal?.fire) {
          show('Dialog konfirmasi tidak tersedia.');
          return;
        }
        const result = await Swal.fire({
          icon: 'warning',
          title: 'Hapus Data CKG?',
          html: `<strong>${esc(row.nama_siswa)}</strong><br>${esc(row.tanggal)}`,
          showCancelButton: true,
          confirmButtonText: 'Ya, hapus',
          cancelButtonText: 'Batal',
          reverseButtons: true,
        });
        if (!result.isConfirmed) return;

        setBusy(button, true, 'Menghapus...');
        try {
          const payload = await requestJson(`${base}/uks/ckg/delete/${row.id}`, { method: 'DELETE' });
          show(payload.message || 'Data CKG dihapus.', 'success');
          await load();
        } catch (error) {
          show(error.message || 'Gagal menghapus Data CKG.');
        } finally {
          setBusy(button, false);
        }
      });
    });
  }

  document.getElementById('btnCkgBaru')?.addEventListener('click', () => openForm());
  document.getElementById('btnCkgImport')?.addEventListener('click', () => {
    if (!importModalEl) return;
    importForm?.reset();
    bootstrap.Modal.getOrCreateInstance(importModalEl).show();
  });
  document.getElementById('btnCkgCari')?.addEventListener('click', () => { state.offset = 0; load(); });
  document.getElementById('btnCkgReset')?.addEventListener('click', () => {
    const tahun = document.getElementById('ckgTahun');
    if (tahun) {
      tahun.value = '';
      window.SisfourActiveYearDefault?.applyDefault(tahun);
    }
    ['ckgKelas','ckgSearch','ckgMulai','ckgSelesai'].forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.value = '';
    });
    state.offset = 0;
    load();
  });
  document.getElementById('ckgTahun')?.addEventListener('change', () => { state.offset = 0; load(); });
  document.getElementById('btnCkgExport')?.addEventListener('click', (event) => {
    event.preventDefault();
    const p = params(false);
    p.delete('format');
    location.href = `${base}/uks/ckg/export?${p}`;
  });

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (form.dataset.busy === '1') return;
    form.dataset.busy = '1';
    const button = form.querySelector('button[type="submit"]');
    setBusy(button, true, 'Menyimpan...');

    const fd = new FormData(form);
    const id = String(fd.get('id') || '');
    fd.delete('id');
    let url = `${base}/uks/ckg/create`;
    let options = { method: 'POST', body: fd };

    if (id) {
      url = `${base}/uks/ckg/update/${id}`;
      options = {
        method: 'PUT',
        body: new URLSearchParams(fd),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      };
    }

    try {
      const payload = await requestJson(url, options);
      bootstrap.Modal.getInstance(modalEl)?.hide();
      show(payload.message || 'Data CKG tersimpan.', 'success');
      await load();
    } catch (error) {
      show(error.message || 'Gagal menyimpan Data CKG.');
    } finally {
      form.dataset.busy = '0';
      setBusy(button, false);
    }
  });

  importForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (importForm.dataset.busy === '1') return;
    importForm.dataset.busy = '1';
    const button = importForm.querySelector('button[type="submit"]');
    setBusy(button, true, 'Mengimport...');

    try {
      const payload = await requestJson(`${base}/uks/ckg/import`, {
        method: 'POST',
        body: new FormData(importForm),
      });
      bootstrap.Modal.getInstance(importModalEl)?.hide();
      show(payload.message || 'Import selesai.', 'success');
      await load();
    } catch (error) {
      show(error.message || 'Import CKG gagal.');
    } finally {
      importForm.dataset.busy = '0';
      setBusy(button, false);
    }
  });

  const query = new URLSearchParams(location.search);
  const limit = Number(query.get('limit') || 25);
  const offset = Number(query.get('offset') || 0);
  state.limit = [25, 50, 100].includes(limit) ? limit : 25;
  state.offset = Number.isFinite(offset) && offset >= 0 ? offset : 0;
  [['id_tahun','ckgTahun'],['id_kelas','ckgKelas'],['search','ckgSearch'],['tanggal_mulai','ckgMulai'],['tanggal_selesai','ckgSelesai']].forEach(([key,id]) => {
    const el = document.getElementById(id);
    const value = query.get(key);
    if (el && value !== null) el.value = value;
  });

  load();
})();
