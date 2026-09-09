(() => {
  'use strict';

  const app = document.getElementById('bkKasusApp');
  if (!app) return;

  const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
  const pageCanManage = app.dataset.canManage === '1';
  const body = document.getElementById('kasusBody');
  const info = document.getElementById('kasusInfo');
  const alertBox = document.getElementById('kasusAlert');
  const search = document.getElementById('kasusSearch');
  const kategori = document.getElementById('kasusKategori');
  const mulai = document.getElementById('kasusMulai');
  const selesai = document.getElementById('kasusSelesai');
  const form = document.getElementById('formKasus');
  const modalEl = document.getElementById('modalKasus');
  const detailModalEl = document.getElementById('modalDetailKasus');
  const detailLoading = document.getElementById('detailKasusLoading');
  const detailContent = document.getElementById('detailKasusContent');
  const formTindak = document.getElementById('formTindakLanjut');
  const timeline = document.getElementById('timelineTindakLanjut');

  let offset = 0;
  let total = 0;
  let currentDetail = null;
  const limit = 50;

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

  const params = () => {
    const p = new URLSearchParams({ format: 'json', limit: String(limit), offset: String(offset) });
    if (search?.value) p.set('search', search.value);
    if (kategori?.value) p.set('kategori', kategori.value);
    if (mulai?.value) p.set('tanggal_mulai', mulai.value);
    if (selesai?.value) p.set('tanggal_selesai', selesai.value);
    return p;
  };

  function show(message, type = 'danger') {
    if (!alertBox) return;
    alertBox.className = `alert alert-${type}`;
    alertBox.textContent = message;
  }

  function hideAlert() {
    alertBox?.classList.add('d-none');
  }

  function setRemoteSelectValue(select, value, text) {
    if (!select) return;
    window.SisfourSearchableSelect?.setValue(select, value, text);

    if (!window.SisfourSearchableSelect) {
      let option = Array.from(select.options).find((item) => item.value === String(value));
      if (!option) {
        option = new Option(text, value, true, true);
        select.add(option);
      }
      select.value = String(value);
    }
  }

  function openForm(row = null) {
    if (!form || !modalEl) return;

    form.reset();
    form.elements.id.value = row?.id || '';

    if (row) {
      setRemoteSelectValue(
        form.elements.id_siswa,
        row.id_siswa,
        `${row.nisn} — ${row.nama_siswa}`
      );
    } else {
      setRemoteSelectValue(form.elements.id_siswa, '', '');
    }

    form.elements.id_pelanggaran.value = row?.id_pelanggaran || '';
    window.SisfourSearchableSelect?.sync(form.elements.id_pelanggaran);
    form.elements.tanggal.value = row?.tanggal || today();
    form.elements.keterangan.value = row?.keterangan || '';

    document.getElementById('judulModalKasus').textContent = row
      ? 'Edit Catatan Kasus'
      : 'Tambah Catatan Kasus';

    bootstrap.Modal.getOrCreateInstance(modalEl).show();
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

  async function load() {
    try {
      const payload = await requestJson(`${base}/bk/kasus?${params()}`);
      hideAlert();

      const data = payload.data || {};
      total = Number(data.total || 0);
      const canManage = Boolean(data.can_manage);
      const rows = data.rows || [];

      body.innerHTML = rows.map((row) => `
        <tr data-json="${encodeURIComponent(JSON.stringify(row))}">
          <td>${esc(row.tanggal)}</td>
          <td>${esc(row.nisn)}<br><strong>${esc(row.nama_siswa)}</strong></td>
          <td>${esc(row.nama_pelanggaran)}</td>
          <td>${esc(row.kategori)}</td>
          <td>${Number(row.poin || 0)}</td>
          <td>${esc(row.keterangan || '-')}</td>
          <td class="text-end text-nowrap">
            <button type="button" class="btn btn-sm btn-outline-secondary btn-detail-kasus">
              Tindak Lanjut
            </button>
            ${canManage ? `
              <button type="button" class="btn btn-sm btn-outline-primary btn-edit-kasus">Edit</button>
              <button type="button" class="btn btn-sm btn-outline-danger btn-delete-kasus">Hapus</button>
            ` : ''}
          </td>
        </tr>
      `).join('') || `
        <tr>
          <td colspan="7" class="text-center text-muted py-4">Tidak ada data.</td>
        </tr>
      `;

      info.textContent = `${total ? offset + 1 : 0}-${Math.min(offset + limit, total)} dari ${total}`;
      document.getElementById('kasusPrev').disabled = offset <= 0;
      document.getElementById('kasusNext').disabled = offset + limit >= total;
      bindRows();
    } catch (error) {
      show(error.message || 'Terjadi kesalahan jaringan.');
    }
  }

  function rowData(button) {
    return JSON.parse(decodeURIComponent(button.closest('tr').dataset.json));
  }

  function bindRows() {
    document.querySelectorAll('.btn-detail-kasus').forEach((button) => {
      button.addEventListener('click', () => openDetail(rowData(button).id));
    });

    document.querySelectorAll('.btn-edit-kasus').forEach((button) => {
      button.addEventListener('click', () => openForm(rowData(button)));
    });

    document.querySelectorAll('.btn-delete-kasus').forEach((button) => {
      button.addEventListener('click', async () => {
        const row = rowData(button);
        if (!confirm(`Hapus Catatan Kasus ${row.nama_siswa} beserta seluruh tindak lanjutnya?`)) return;

        try {
          const payload = await requestJson(`${base}/bk/kasus/delete/${row.id}`, { method: 'DELETE' });
          show(payload.message || 'Berhasil.', 'success');
          load();
        } catch (error) {
          show(error.message || 'Gagal menghapus.');
        }
      });
    });
  }

  async function openDetail(idKasus) {
    if (!detailModalEl) return;

    const modal = bootstrap.Modal.getOrCreateInstance(detailModalEl);
    modal.show();
    detailLoading?.classList.remove('d-none');
    detailContent?.classList.add('d-none');

    try {
      const payload = await requestJson(`${base}/bk/kasus/detail/${idKasus}`);
      currentDetail = payload.data || {};
      renderDetail(currentDetail);
      detailLoading?.classList.add('d-none');
      detailContent?.classList.remove('d-none');
    } catch (error) {
      if (detailLoading) {
        detailLoading.textContent = error.message || 'Detail gagal dimuat.';
      }
    }
  }

  function renderDetail(data) {
    const kasus = data.kasus || {};
    document.getElementById('detailKasusSiswa').textContent = `${kasus.nisn || '-'} — ${kasus.nama_siswa || '-'}`;
    document.getElementById('detailKasusTanggal').textContent = kasus.tanggal || '-';
    document.getElementById('detailKasusKategori').textContent = `${kasus.kategori || '-'} / ${Number(kasus.poin || 0)} poin`;
    document.getElementById('detailKasusPelanggaran').textContent = kasus.nama_pelanggaran || '-';
    document.getElementById('detailKasusKeterangan').textContent = kasus.keterangan || '-';

    if (formTindak) {
      resetTindakForm();
      formTindak.elements.id_kasus.value = kasus.id || '';

      const jenisSelect = formTindak.elements.tindak_lanjut;
      jenisSelect.innerHTML = '<option value="">Pilih tindak lanjut</option>';
      (data.tindak_lanjut_options || []).forEach((label) => {
        jenisSelect.add(new Option(label, label));
      });
    }

    const rows = data.tindak_lanjut || [];
    document.getElementById('jumlahTindakLanjut').textContent = String(rows.length);

    if (!timeline) return;

    timeline.innerHTML = rows.map((row) => `
      <div class="border rounded p-3" data-tindak="${encodeURIComponent(JSON.stringify(row))}">
        <div class="d-flex flex-wrap justify-content-between gap-2 mb-2">
          <div>
            <strong>${esc(row.tindak_lanjut)}</strong>
            <div class="small text-muted">${esc(row.tanggal)}</div>
          </div>
          ${data.can_manage ? `
            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-tindak">Edit</button>
          ` : ''}
        </div>
        <div>${esc(row.keterangan || '-')}</div>
        <div class="small text-muted mt-2">
          Dicatat oleh: ${esc(row.nama_input || row.username_input || '-')}
        </div>
      </div>
    `).join('') || '<div class="text-center text-muted py-3">Belum ada tindak lanjut.</div>';

    timeline.querySelectorAll('.btn-edit-tindak').forEach((button) => {
      button.addEventListener('click', () => {
        const row = JSON.parse(decodeURIComponent(button.closest('[data-tindak]').dataset.tindak));
        editTindak(row);
      });
    });
  }

  function resetTindakForm() {
    if (!formTindak) return;
    const idKasus = formTindak.elements.id_kasus.value;
    formTindak.reset();
    formTindak.elements.id.value = '';
    formTindak.elements.id_kasus.value = idKasus;
    formTindak.elements.tanggal.value = today();
    document.getElementById('judulTindakLanjut').textContent = 'Tambah Tindak Lanjut';
    document.getElementById('btnBatalEditTindak')?.classList.add('d-none');
  }

  function editTindak(row) {
    if (!formTindak) return;
    formTindak.elements.id.value = row.id;
    formTindak.elements.tanggal.value = row.tanggal || '';
    formTindak.elements.tindak_lanjut.value = row.tindak_lanjut || '';
    formTindak.elements.keterangan.value = row.keterangan || '';
    document.getElementById('judulTindakLanjut').textContent = 'Edit Tindak Lanjut';
    document.getElementById('btnBatalEditTindak')?.classList.remove('d-none');
    formTindak.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  document.getElementById('btnKasusBaru')?.addEventListener('click', () => openForm());
  document.getElementById('btnKasusCari')?.addEventListener('click', () => {
    offset = 0;
    load();
  });
  search?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      offset = 0;
      load();
    }
  });
  document.getElementById('kasusPrev')?.addEventListener('click', () => {
    offset = Math.max(0, offset - limit);
    load();
  });
  document.getElementById('kasusNext')?.addEventListener('click', () => {
    if (offset + limit < total) {
      offset += limit;
      load();
    }
  });
  document.getElementById('btnKasusExport')?.addEventListener('click', (event) => {
    event.preventDefault();
    const p = params();
    p.delete('format');
    p.delete('limit');
    p.delete('offset');
    window.location.href = `${base}/bk/kasus/export?${p}`;
  });

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const fd = new FormData(form);
    const id = String(fd.get('id') || '');
    fd.delete('id');

    let url = `${base}/bk/kasus/create`;
    let options = { method: 'POST', body: fd };

    if (id) {
      url = `${base}/bk/kasus/update/${id}`;
      options = {
        method: 'PUT',
        body: new URLSearchParams(fd),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      };
    }

    try {
      const payload = await requestJson(url, options);
      bootstrap.Modal.getInstance(modalEl)?.hide();
      show(payload.message || 'Berhasil.', 'success');
      await load();

      if (!id && payload.data?.id) {
        await openDetail(payload.data.id);
      }
    } catch (error) {
      show(error.message || 'Gagal menyimpan.');
    }
  });

  formTindak?.addEventListener('submit', async (event) => {
    event.preventDefault();

    const fd = new FormData(formTindak);
    const id = String(fd.get('id') || '');
    const idKasus = String(fd.get('id_kasus') || '');
    fd.delete('id');
    fd.delete('id_kasus');

    let url = `${base}/bk/kasus/${idKasus}/tindak-lanjut`;
    let options = { method: 'POST', body: fd };

    if (id) {
      url = `${base}/bk/kasus/tindak-lanjut/${id}`;
      options = {
        method: 'PUT',
        body: new URLSearchParams(fd),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      };
    }

    try {
      const payload = await requestJson(url, options);
      show(payload.message || 'Tindak lanjut berhasil disimpan.', 'success');
      await openDetail(idKasus);
    } catch (error) {
      show(error.message || 'Tindak lanjut gagal disimpan.');
    }
  });

  document.getElementById('btnBatalEditTindak')?.addEventListener('click', resetTindakForm);

  if (!pageCanManage && formTindak) {
    formTindak.classList.add('d-none');
  }

  load();
})();
