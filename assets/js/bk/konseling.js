(() => {
  'use strict';

  const app = document.getElementById('bkKonselingApp');
  if (!app) return;

  const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
  const canManage = app.dataset.canManage === '1';
  const canExport = app.dataset.canExport === '1';

  const body = document.getElementById('konselingBody');
  const mobileList = document.getElementById('konselingMobileList');
  const totalLabel = document.getElementById('konselingTotal');
  const alertBox = document.getElementById('konselingAlert');

  if (!body || !mobileList) return;

  const search = document.getElementById('konselingSearch');
  const filterKelas = document.getElementById('konselingFilterKelas');
  const filterStatus = document.getElementById('konselingFilterStatus');
  const filterBidang = document.getElementById('konselingFilterBidang');
  const mulai = document.getElementById('konselingMulai');
  const selesai = document.getElementById('konselingSelesai');

  const createModalEl = document.getElementById('modalKonselingBaru');
  const detailModalEl = document.getElementById('modalKonselingDetail');
  const createForm = document.getElementById('formKonselingBaru');
  const updateForm = document.getElementById('formKonselingUpdate');
  const kelasSelect = document.getElementById('konselingKelas');
  const siswaSelect = document.getElementById('konselingSiswa');
  const bidangSelect = document.getElementById('konselingBidang');
  const topikSelect = document.getElementById('konselingTopik');
  const detailLoading = document.getElementById('konselingDetailLoading');
  const detailContent = document.getElementById('konselingDetailContent');

  const state = {
    limit: 25,
    offset: 0,
    total: 0,
  };

  let topikMap = {};
  try {
    topikMap = JSON.parse(document.getElementById('konselingTopikData')?.textContent || '{}');
  } catch (error) {
    topikMap = {};
  }

  const pager = window.SisfourPagination?.mount(body, {
    id: 'bkKonselingPager',
    label: 'catatan konseling',
    onChange: (next) => {
      state.limit = next.limit;
      state.offset = next.offset;
      load();
    },
  });

  const esc = (value) => {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
  };

  const today = () => {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
  };

  function show(message, type = 'danger') {
    if (!alertBox) return;
    alertBox.className = `alert alert-${type}`;
    alertBox.textContent = message;
  }

  function hideAlert() {
    alertBox?.classList.add('d-none');
  }

  function setBusy(button, busy, label = 'Memproses...') {
    if (!button) return;

    if (busy) {
      if (button.dataset.busy === '1') return;
      button.dataset.busy = '1';
      button.dataset.busyHtml = button.innerHTML;
      button.disabled = true;
      button.innerHTML = `<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>${label}`;
      return;
    }

    button.disabled = false;
    if (button.dataset.busyHtml !== undefined) {
      button.innerHTML = button.dataset.busyHtml;
    }
    delete button.dataset.busy;
    delete button.dataset.busyHtml;
  }

  function submitButton(form) {
    return form?.querySelector('button[type="submit"], input[type="submit"]') || null;
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
    try {
      payload = await response.json();
    } catch (error) {
      throw new Error('Response server tidak valid.');
    }

    if (!response.ok || payload.status !== 'success') {
      throw new Error(payload.message || 'Proses gagal.');
    }

    return payload;
  }

  function params(withPaging = true) {
    const p = new URLSearchParams({ format: 'json' });

    if (withPaging) {
      p.set('limit', String(state.limit));
      p.set('offset', String(state.offset));
    }

    if (search?.value) p.set('search', search.value);
    if (filterKelas?.value) p.set('id_kelas', filterKelas.value);
    if (filterStatus?.value) p.set('status', filterStatus.value);
    if (filterBidang?.value) p.set('bidang', filterBidang.value);
    if (mulai?.value) p.set('tanggal_mulai', mulai.value);
    if (selesai?.value) p.set('tanggal_selesai', selesai.value);

    return p;
  }

  function statusBadge(status) {
    return status === 'Selesai' ? 'success' : 'warning';
  }

  function render(rows) {
    body.innerHTML = rows.map((row) => `
      <tr>
        <td class="text-nowrap">${esc(row.tanggal)}</td>
        <td>
          <strong>${esc(row.nama_siswa)}</strong>
          <div class="small text-muted">NISN ${esc(row.nisn || '-')}</div>
        </td>
        <td>${esc(row.nama_kelas)}</td>
        <td>
          ${esc(row.bentuk_layanan)}
          <div class="small text-muted">Pertemuan ke-${Number(row.pertemuan_ke || 1)}</div>
        </td>
        <td>
          <span class="badge bg-label-secondary">${esc(row.bidang)}</span>
          <div class="small mt-1">${esc(row.topik)}</div>
        </td>
        <td><span class="badge bg-label-${statusBadge(row.status)}">${esc(row.status)}</span></td>
        <td class="text-end">
          <button type="button" class="btn btn-sm btn-outline-primary btn-detail-konseling" data-id="${Number(row.id)}">
            ${row.status === 'Selesai' ? 'Lihat' : 'Lengkapi'}
          </button>
        </td>
      </tr>
    `).join('') || `
      <tr><td colspan="7" class="text-center text-muted py-4">Belum ada catatan Konseling BK.</td></tr>
    `;

    mobileList.innerHTML = rows.map((row) => `
      <div class="list-group-item py-3">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
          <div class="sisfour-cell-primary">
            <span class="sisfour-cell-title">${esc(row.nama_siswa)}</span>
            <span class="sisfour-cell-meta">${esc(row.nama_kelas)} · ${esc(row.tanggal)}</span>
          </div>
          <span class="badge bg-label-${statusBadge(row.status)} flex-shrink-0">${esc(row.status)}</span>
        </div>
        <div class="small mb-1"><strong>${esc(row.bentuk_layanan)}</strong> · Pertemuan ke-${Number(row.pertemuan_ke || 1)}</div>
        <div class="small text-muted mb-3">${esc(row.bidang)} · ${esc(row.topik)}</div>
        <button type="button" class="btn btn-sm btn-outline-primary sisfour-touch-target--compact btn-detail-konseling" data-id="${Number(row.id)}">
          ${row.status === 'Selesai' ? 'Lihat Catatan' : 'Lengkapi Tahap 2'}
        </button>
      </div>
    `).join('') || '<div class="list-group-item text-center text-muted py-4">Belum ada catatan Konseling BK.</div>';

    document.querySelectorAll('.btn-detail-konseling').forEach((button) => {
      button.addEventListener('click', () => openDetail(button.dataset.id));
    });
  }

  async function load() {
    pager?.setDisabled(true);

    try {
      const payload = await requestJson(`${base}/bk/konseling?${params(true)}`);
      const data = payload.data || {};
      const rows = Array.isArray(data.rows) ? data.rows : [];

      state.total = Number(data.total || 0);
      state.limit = Number(data.limit || state.limit);
      state.offset = Number(data.offset ?? state.offset);

      if (rows.length === 0 && state.total > 0 && state.offset >= state.total) {
        state.offset = Math.floor((state.total - 1) / state.limit) * state.limit;
        await load();
        return;
      }

      hideAlert();
      render(rows);
      pager?.render(state);
      if (totalLabel) totalLabel.textContent = `${state.total} catatan`;
    } catch (error) {
      show(error.message || 'Data Konseling BK gagal dimuat.');
    } finally {
      pager?.setDisabled(false);
    }
  }

  function renderTopics() {
    if (!bidangSelect || !topikSelect) return;
    const values = Array.isArray(topikMap[bidangSelect.value]) ? topikMap[bidangSelect.value] : [];
    topikSelect.innerHTML = '<option value="">Pilih topik</option>';
    values.forEach((value) => topikSelect.add(new Option(value, value)));
  }

  async function loadStudents() {
    if (!siswaSelect || !kelasSelect) return;

    siswaSelect.disabled = true;
    siswaSelect.innerHTML = '<option value="">Memuat siswa...</option>';
    window.SisfourSearchableSelect?.sync(siswaSelect);

    const idKelas = String(kelasSelect.value || '');
    if (!idKelas) {
      siswaSelect.innerHTML = '<option value="">Pilih kelas terlebih dahulu</option>';
      window.SisfourSearchableSelect?.sync(siswaSelect);
      return;
    }

    try {
      const payload = await requestJson(`${base}/bk/konseling/siswa-kelas/${encodeURIComponent(idKelas)}`);
      const rows = payload.data?.rows || [];

      siswaSelect.innerHTML = '<option value="">Pilih siswa</option>';
      rows.forEach((row) => {
        siswaSelect.add(new Option(row.text || `${row.nisn} — ${row.nama}`, row.id));
      });
      siswaSelect.disabled = false;
      window.SisfourSearchableSelect?.sync(siswaSelect);
    } catch (error) {
      siswaSelect.innerHTML = '<option value="">Siswa gagal dimuat</option>';
      window.SisfourSearchableSelect?.sync(siswaSelect);
      show(error.message || 'Daftar siswa gagal dimuat.');
    }
  }

  function openCreate() {
    if (!createForm || !createModalEl) return;

    createForm.reset();
    createForm.dataset.busy = '0';
    setBusy(submitButton(createForm), false);
    if (document.getElementById('konselingTanggal')) {
      document.getElementById('konselingTanggal').value = today();
    }

    if (siswaSelect) {
      siswaSelect.innerHTML = '<option value="">Pilih kelas terlebih dahulu</option>';
      siswaSelect.disabled = true;
      window.SisfourSearchableSelect?.sync(siswaSelect);
    }

    renderTopics();
    bootstrap.Modal.getOrCreateInstance(createModalEl).show();
  }

  function fillDetail(row) {
    document.getElementById('detailKonselingSiswa').textContent = `${row.nisn || '-'} — ${row.nama_siswa || '-'}`;
    document.getElementById('detailKonselingKelas').textContent = row.nama_kelas || '-';
    document.getElementById('detailKonselingTanggal').textContent = `${row.tanggal || '-'} · Pertemuan ke-${Number(row.pertemuan_ke || 1)}`;
    document.getElementById('detailKonselingLayanan').textContent = row.bentuk_layanan || '-';
    document.getElementById('detailKonselingTopik').textContent = `${row.bidang || '-'} · ${row.topik || '-'}`;
    document.getElementById('detailKonselingCara').textContent = row.cara_hadir || '-';
    document.getElementById('detailKonselingGuru').textContent = row.nama_guru_bk || '-';

    const statusBadgeEl = document.getElementById('detailKonselingStatus');
    if (statusBadgeEl) {
      statusBadgeEl.textContent = row.status || 'Proses';
      statusBadgeEl.className = `badge bg-label-${statusBadge(row.status)}`;
    }

    if (updateForm) {
      updateForm.dataset.busy = '0';
      setBusy(submitButton(updateForm), false);
      updateForm.elements.id.value = row.id || '';
      updateForm.elements.uraian_masalah.value = row.uraian_masalah || '';
      updateForm.elements.hasil_kesepakatan.value = row.hasil_kesepakatan || '';
      updateForm.elements.rencana_berikutnya.value = row.rencana_berikutnya || '';
      updateForm.elements.tanggal_berikutnya.value = row.tanggal_berikutnya || '';
      updateForm.elements.status.value = row.status || 'Proses';
    }

    const uraianReadonly = document.getElementById('detailKonselingUraianReadonly');
    const hasilReadonly = document.getElementById('detailKonselingHasilReadonly');
    if (uraianReadonly) uraianReadonly.textContent = row.uraian_masalah || 'Belum diisi.';
    if (hasilReadonly) hasilReadonly.textContent = row.hasil_kesepakatan || 'Belum diisi.';
  }

  async function openDetail(id) {
    if (!detailModalEl) return;

    const modal = bootstrap.Modal.getOrCreateInstance(detailModalEl);
    modal.show();
    detailLoading?.classList.remove('d-none');
    detailContent?.classList.add('d-none');

    try {
      const payload = await requestJson(`${base}/bk/konseling/detail/${encodeURIComponent(id)}`);
      fillDetail(payload.data?.row || {});
      detailLoading?.classList.add('d-none');
      detailContent?.classList.remove('d-none');
    } catch (error) {
      if (detailLoading) detailLoading.textContent = error.message || 'Detail gagal dimuat.';
    }
  }

  document.getElementById('btnKonselingBaru')?.addEventListener('click', openCreate);
  kelasSelect?.addEventListener('change', loadStudents);
  bidangSelect?.addEventListener('change', renderTopics);

  document.getElementById('btnKonselingCari')?.addEventListener('click', () => {
    state.offset = 0;
    load();
  });

  search?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      state.offset = 0;
      load();
    }
  });

  if (canExport) {
    document.getElementById('btnKonselingExport')?.addEventListener('click', (event) => {
      event.preventDefault();
      const p = params(false);
      p.delete('format');
      window.location.href = `${base}/bk/konseling/export?${p.toString()}`;
    });
  }

  createForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (createForm.dataset.busy === '1') return;

    createForm.dataset.busy = '1';
    const button = submitButton(createForm);
    setBusy(button, true, 'Menyimpan...');

    try {
      const payload = await requestJson(`${base}/bk/konseling/create`, {
        method: 'POST',
        body: new FormData(createForm),
      });

      bootstrap.Modal.getInstance(createModalEl)?.hide();
      show(payload.message || 'Tahap 1 berhasil disimpan.', 'success');
      await load();

      if (payload.data?.id) {
        await openDetail(payload.data.id);
      }
    } catch (error) {
      show(error.message || 'Tahap 1 gagal disimpan.');
    } finally {
      createForm.dataset.busy = '0';
      setBusy(button, false);
    }
  });

  updateForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (updateForm.dataset.busy === '1') return;

    updateForm.dataset.busy = '1';
    const button = submitButton(updateForm);
    setBusy(button, true, 'Menyimpan...');

    const fd = new FormData(updateForm);
    const id = String(fd.get('id') || '');
    fd.delete('id');

    try {
      const payload = await requestJson(`${base}/bk/konseling/update/${encodeURIComponent(id)}`, {
        method: 'PUT',
        body: new URLSearchParams(fd),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      });

      show(payload.message || 'Konseling BK berhasil diperbarui.', 'success');
      await load();
      await openDetail(id);
    } catch (error) {
      show(error.message || 'Update Konseling BK gagal disimpan.');
    } finally {
      updateForm.dataset.busy = '0';
      setBusy(button, false);
    }
  });

  if (!canManage && createForm) {
    createForm.classList.add('d-none');
  }

  renderTopics();
  load();
})();
