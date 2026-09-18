(() => {
  'use strict';

  const app = document.getElementById('uksHarianApp');
  if (!app) return;

  const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
  const body = document.getElementById('harianBody');
  const mobile = document.getElementById('harianMobileList');
  const alertBox = document.getElementById('harianAlert');
  const form = document.getElementById('formHarian');
  const modalEl = document.getElementById('modalHarian');
  const state = { limit: 25, offset: 0, total: 0 };

  if (!body || !mobile) return;

  const pager = window.SisfourPagination?.mount(body, {
    id: 'uksHarianPager',
    label: 'kunjungan UKS',
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
  const nowTime = () => {
    const d = new Date();
    return `${String(d.getHours()).padStart(2, '0')}:${String(d.getMinutes()).padStart(2, '0')}`;
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
      id_tahun: 'harianTahun',
      id_kelas: 'harianKelas',
      search: 'harianSearch',
      tanggal_mulai: 'harianMulai',
      tanggal_selesai: 'harianSelesai',
      id_keluhan: 'harianKeluhan',
      id_hasil: 'harianHasil',
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
    const select = document.getElementById('harianKelas');
    if (!select) return;
    const current = select.value;
    select.innerHTML = '<option value="">Semua kelas</option>' + (rows || [])
      .map((row) => `<option value="${Number(row.id)}">${esc(row.nama_kelas)}</option>`)
      .join('');
    if ([...select.options].some((opt) => opt.value === current)) select.value = current;
  }

  function actionLabel(row) {
    return (row.tindakan || []).map((item) => item.nama).join(', ') || '-';
  }

  function render(rows, manage) {
    body.innerHTML = (rows || []).map((row) => `<tr data-json="${encodeURIComponent(JSON.stringify(row))}">
      <td>${esc(row.tanggal)}<div class="small text-muted">${esc(String(row.jam_masuk || '').slice(0, 5))}</div></td>
      <td><strong>${esc(row.nama_siswa)}</strong><div class="small text-muted">${esc(row.nisn)} · ${esc(row.nama_kelas || '-')}</div></td>
      <td>${esc(row.keluhan || '-')}<div class="small text-muted">${esc(row.catatan_keluhan || '')}</div></td>
      <td>${esc(actionLabel(row))}</td>
      <td>${esc(row.hasil || '-')}</td>
      <td>${esc(row.petugas_nama || '-')}</td>
      ${manage ? '<td class="text-nowrap"><button class="btn btn-sm btn-outline-primary btn-harian-edit" type="button">Edit</button> <button class="btn btn-sm btn-outline-danger btn-harian-delete" type="button">Hapus</button></td>' : ''}
    </tr>`).join('') || `<tr><td colspan="${manage ? 7 : 6}" class="text-center text-muted py-4">Tidak ada data.</td></tr>`;

    mobile.innerHTML = (rows || []).map((row) => `<div class="list-group-item py-3" data-json="${encodeURIComponent(JSON.stringify(row))}">
      <div class="d-flex justify-content-between gap-2"><div><div class="fw-semibold">${esc(row.nama_siswa)}</div><div class="small text-muted">${esc(row.nisn)} · ${esc(row.nama_kelas || '-')}</div></div><span class="badge bg-label-primary">${esc(row.tanggal)} ${esc(String(row.jam_masuk || '').slice(0,5))}</span></div>
      <div class="mt-2">${esc(row.keluhan || '-')}</div>
      <div class="small text-muted">${esc(actionLabel(row))} · ${esc(row.hasil || '-')}</div>
      ${manage ? '<div class="sisfour-mobile-actions mt-3"><button class="btn btn-sm btn-outline-primary btn-harian-edit" type="button">Edit</button><button class="btn btn-sm btn-outline-danger btn-harian-delete" type="button">Hapus</button></div>' : ''}
    </div>`).join('') || '<div class="list-group-item text-center text-muted py-4">Tidak ada data.</div>';

    bindRows();
  }

  function rowData(button) {
    const holder = button.closest('[data-json]');
    return holder ? JSON.parse(decodeURIComponent(holder.dataset.json)) : null;
  }

  function ensureSelectOption(select, value, label) {
    if (!select || !value) return;
    const exists = [...select.options].some((option) => option.value === String(value));
    if (!exists) {
      const option = new Option(`${label} (tersimpan)`, String(value), false, false);
      option.dataset.historical = '1';
      select.add(option);
    }
  }

  function clearHistoricalFormRefs() {
    if (!form) return;
    form.querySelectorAll('option[data-historical="1"]').forEach((option) => option.remove());
    form.querySelectorAll('.historical-action').forEach((node) => node.remove());
  }

  function ensureHistoricalAction(item) {
    if (!form || !item?.id) return;
    const selector = `.harian-action[value="${CSS.escape(String(item.id))}"]`;
    if (form.querySelector(selector)) return;

    const host = form.querySelector('.harian-action')?.closest('.row');
    if (!host) return;

    const wrap = document.createElement('div');
    wrap.className = 'col-12 col-md-4 historical-action';
    wrap.innerHTML = `<div class="form-check"><input class="form-check-input harian-action" type="checkbox" name="tindakan[]" value="${esc(item.id)}" id="historical_action_${esc(item.id)}"><label class="form-check-label" for="historical_action_${esc(item.id)}">${esc(item.nama)} (tersimpan)</label></div>`;
    host.appendChild(wrap);
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
    clearHistoricalFormRefs();
    form.elements.id.value = row?.id || '';
    setStudent(row);
    form.elements.tanggal.value = row?.tanggal || today();
    form.elements.jam_masuk.value = row ? String(row.jam_masuk || '').slice(0, 5) : nowTime();
    form.elements.catatan_keluhan.value = row?.catatan_keluhan || '';
    form.elements.suhu_tubuh.value = row?.suhu_tubuh ?? '';
    form.elements.tekanan_darah.value = row?.tekanan_darah || '';
    form.elements.obat_diberikan.value = row?.obat_diberikan || '';
    form.elements.jam_keluar.value = row ? String(row.jam_keluar || '').slice(0, 5) : '';
    form.elements.orang_tua_dihubungi.value = row?.orang_tua_dihubungi || 'Tidak';

    if (row) {
      ensureSelectOption(form.elements.id_keluhan, row.id_keluhan, row.keluhan || 'Keluhan');
      ensureSelectOption(form.elements.id_hasil, row.id_hasil, row.hasil || 'Hasil');
    }
    form.elements.id_keluhan.value = row?.id_keluhan || form.elements.id_keluhan.value;
    form.elements.id_hasil.value = row?.id_hasil || form.elements.id_hasil.value;

    (row?.tindakan || []).forEach(ensureHistoricalAction);
    const selected = new Set((row?.tindakan || []).map((item) => String(item.id)));
    form.querySelectorAll('.harian-action').forEach((checkbox) => {
      checkbox.checked = selected.has(checkbox.value);
    });

    document.getElementById('judulModalHarian').textContent = row ? 'Edit Catatan UKS' : 'Tambah Kunjungan UKS';
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  }

  async function load() {
    pager?.setDisabled(true);
    try {
      const payload = await requestJson(`${base}/uks/harian?${params(true)}`);
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
      show(error.message || 'Gagal memuat Catatan UKS.');
    } finally {
      pager?.setDisabled(false);
    }
  }

  function bindRows() {
    document.querySelectorAll('.btn-harian-edit').forEach((button) => {
      button.addEventListener('click', () => {
        const row = rowData(button);
        if (row) openForm(row);
      });
    });

    document.querySelectorAll('.btn-harian-delete').forEach((button) => {
      button.addEventListener('click', async () => {
        const row = rowData(button);
        if (!row || button.dataset.busy === '1') return;
        if (!window.Swal?.fire) {
          show('Dialog konfirmasi tidak tersedia.');
          return;
        }
        const result = await Swal.fire({
          icon: 'warning',
          title: 'Hapus Catatan UKS?',
          html: `<strong>${esc(row.nama_siswa)}</strong><br>${esc(row.tanggal)}`,
          showCancelButton: true,
          confirmButtonText: 'Ya, hapus',
          cancelButtonText: 'Batal',
          reverseButtons: true,
        });
        if (!result.isConfirmed) return;

        setBusy(button, true, 'Menghapus...');
        try {
          const payload = await requestJson(`${base}/uks/harian/delete/${row.id}`, { method: 'DELETE' });
          show(payload.message || 'Catatan UKS dihapus.', 'success');
          await load();
        } catch (error) {
          show(error.message || 'Gagal menghapus Catatan UKS.');
        } finally {
          setBusy(button, false);
        }
      });
    });
  }

  document.getElementById('btnHarianBaru')?.addEventListener('click', () => openForm());
  document.getElementById('btnHarianCari')?.addEventListener('click', () => { state.offset = 0; load(); });
  document.getElementById('btnHarianReset')?.addEventListener('click', () => {
    const tahun = document.getElementById('harianTahun');
    if (tahun) {
      tahun.value = '';
      window.SisfourActiveYearDefault?.applyDefault(tahun);
    }
    ['harianKelas','harianSearch','harianMulai','harianSelesai','harianKeluhan','harianHasil'].forEach((id) => {
      const el = document.getElementById(id);
      if (el) el.value = '';
    });
    state.offset = 0;
    load();
  });
  document.getElementById('harianTahun')?.addEventListener('change', () => { state.offset = 0; load(); });
  document.getElementById('btnHarianExport')?.addEventListener('click', (event) => {
    event.preventDefault();
    const p = params(false);
    p.delete('format');
    location.href = `${base}/uks/harian/export?${p}`;
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

    let url = `${base}/uks/harian/create`;
    let options = { method: 'POST', body: fd };

    if (id) {
      url = `${base}/uks/harian/update/${id}`;
      options = {
        method: 'PUT',
        body: new URLSearchParams(fd),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      };
    }

    try {
      const payload = await requestJson(url, options);
      bootstrap.Modal.getInstance(modalEl)?.hide();
      show(payload.message || 'Catatan UKS tersimpan.', 'success');
      await load();
    } catch (error) {
      show(error.message || 'Gagal menyimpan Catatan UKS.');
    } finally {
      form.dataset.busy = '0';
      setBusy(button, false);
    }
  });

  const query = new URLSearchParams(location.search);
  const limit = Number(query.get('limit') || 25);
  const offset = Number(query.get('offset') || 0);
  state.limit = [25, 50, 100].includes(limit) ? limit : 25;
  state.offset = Number.isFinite(offset) && offset >= 0 ? offset : 0;
  [['id_tahun','harianTahun'],['id_kelas','harianKelas'],['search','harianSearch'],['tanggal_mulai','harianMulai'],['tanggal_selesai','harianSelesai'],['id_keluhan','harianKeluhan'],['id_hasil','harianHasil']].forEach(([key,id]) => {
    const el = document.getElementById(id);
    const value = query.get(key);
    if (el && value !== null) el.value = value;
  });

  const openCreateFromHash = location.hash === '#tambah';
  load().then(() => {
    if (openCreateFromHash && document.getElementById('btnHarianBaru')) {
      openForm();
    }
  });
})();
