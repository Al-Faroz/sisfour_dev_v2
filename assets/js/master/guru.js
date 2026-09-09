(() => {
  'use strict';

  const app = document.getElementById('masterGuruApp');
  if (!app) return;

  const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
  const canManage = app.dataset.canManage === '1';
  const tbody = document.querySelector('#tableGuru tbody');
  const filterForm = document.getElementById('formFilterGuru');
  const form = document.getElementById('formGuru');
  const modalEl = document.getElementById('modalGuru');
  const importModalEl = document.getElementById('modalImportGuru');
  const importForm = document.getElementById('formImportGuru');
  let rows = [];

  const esc = (value) => {
    const node = document.createElement('div');
    node.textContent = value ?? '';
    return node.innerHTML;
  };

  const valueOrDash = (value) => {
    const text = String(value ?? '').trim();
    return text === '' ? '-' : esc(text);
  };

  const params = () => {
    const p = new URLSearchParams({ format: 'json' });
    ['nama', 'nik', 'nip', 'jenis_kelamin', 'status_kepegawaian'].forEach((name) => {
      const value = filterForm?.elements[name]?.value?.trim();
      if (value) p.set(name, value);
    });
    return p;
  };

  function message(text, type = 'success') {
    if (window.Swal) {
      return Swal.fire({
        icon: type === 'success' ? 'success' : 'error',
        text,
        confirmButtonText: 'OK',
      });
    }
    window.alert(text);
    return Promise.resolve();
  }

  async function load() {
    try {
      const response = await fetch(`${base}/master/guru?${params()}`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const payload = await response.json();
      if (!response.ok || payload.status !== 'success') {
        await message(payload.message || 'Gagal memuat data Guru.', 'error');
        return;
      }
      rows = Array.isArray(payload.data) ? payload.data : [];
      render();
    } catch (error) {
      await message('Terjadi kesalahan jaringan saat memuat data Guru.', 'error');
    }
  }

  function render() {
    if (!tbody) return;

    tbody.innerHTML = rows.map((row, index) => {
      const complete = Boolean(row.identity_complete);
      const identityBadge = complete
        ? ''
        : '<span class="badge bg-label-warning mt-1">NIK belum lengkap</span>';
      const account = row.username
        ? `${esc(row.username)}<br><span class="badge ${Number(row.status_user) === 1 ? 'bg-label-success' : 'bg-label-secondary'}">${Number(row.status_user) === 1 ? 'Aktif' : 'Nonaktif'}</span>`
        : '<span class="text-warning">Akun belum terhubung</span>';
      const contact = [row.no_telepon, row.email].filter(Boolean).map(esc).join('<br>') || '-';
      const data = encodeURIComponent(JSON.stringify(row));
      const manageActions = canManage
        ? ` <button type="button" class="btn btn-sm btn-outline-primary btn-edit" title="Edit"><i class="bx bx-edit"></i></button>
            <button type="button" class="btn btn-sm btn-outline-secondary btn-foto" title="Ganti Foto"><i class="bx bx-image"></i></button>
            <button type="button" class="btn btn-sm btn-outline-danger btn-delete" title="Hapus"><i class="bx bx-trash"></i></button>`
        : '';

      return `<tr data-row="${data}">
        <td>${index + 1}</td>
        <td><strong>${esc(row.nama)}</strong><br><small>${row.jenis_kelamin === 'P' ? 'Perempuan' : 'Laki-laki'}</small></td>
        <td>${valueOrDash(row.nik)}<br>${identityBadge}</td>
        <td>${valueOrDash(row.nip)}</td>
        <td>${valueOrDash(row.status_kepegawaian)}</td>
        <td>${account}</td>
        <td>${contact}</td>
        <td class="text-nowrap">
          <button type="button" class="btn btn-sm btn-outline-info btn-detail" title="Detail"><i class="bx bx-show"></i></button>${manageActions}
        </td>
      </tr>`;
    }).join('') || '<tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data Guru.</td></tr>';

    bindRows();
  }

  function rowData(button) {
    const raw = button.closest('tr')?.dataset.row;
    return raw ? JSON.parse(decodeURIComponent(raw)) : null;
  }

  function bindRows() {
    document.querySelectorAll('.btn-detail').forEach((button) => {
      button.addEventListener('click', () => showDetail(rowData(button)));
    });

    if (!canManage) return;

    document.querySelectorAll('.btn-edit').forEach((button) => {
      button.addEventListener('click', () => openForm(rowData(button)));
    });

    document.querySelectorAll('.btn-foto').forEach((button) => {
      button.addEventListener('click', () => uploadPhotoPrompt(rowData(button)));
    });

    document.querySelectorAll('.btn-delete').forEach((button) => {
      button.addEventListener('click', () => remove(rowData(button)));
    });
  }

  function showDetail(row) {
    if (!row) return;
    const body = document.getElementById('detailGuruBody');
    if (!body) return;

    body.innerHTML = `<div class="row g-3">
      <div class="col-md-6"><small class="text-muted">Nama</small><div class="fw-semibold">${esc(row.nama)}</div></div>
      <div class="col-md-3"><small class="text-muted">NIK</small><div>${valueOrDash(row.nik)}</div></div>
      <div class="col-md-3"><small class="text-muted">NIP</small><div>${valueOrDash(row.nip)}</div></div>
      <div class="col-md-4"><small class="text-muted">Jenis Kelamin</small><div>${row.jenis_kelamin === 'P' ? 'Perempuan' : 'Laki-laki'}</div></div>
      <div class="col-md-4"><small class="text-muted">Tempat, Tanggal Lahir</small><div>${valueOrDash(row.tempat_lahir)}${row.tanggal_lahir ? `, ${esc(row.tanggal_lahir)}` : ''}</div></div>
      <div class="col-md-4"><small class="text-muted">Agama</small><div>${valueOrDash(row.agama)}</div></div>
      <div class="col-md-4"><small class="text-muted">Status Kepegawaian</small><div>${valueOrDash(row.status_kepegawaian)}</div></div>
      <div class="col-md-4"><small class="text-muted">NUPTK</small><div>${valueOrDash(row.nuptk)}</div></div>
      <div class="col-md-4"><small class="text-muted">Identitas Login</small><div>${valueOrDash(row.login_identifier)}</div></div>
      <div class="col-md-6"><small class="text-muted">No. Telepon</small><div>${valueOrDash(row.no_telepon)}</div></div>
      <div class="col-md-6"><small class="text-muted">Email</small><div>${valueOrDash(row.email)}</div></div>
      <div class="col-12"><small class="text-muted">Alamat</small><div>${valueOrDash(row.alamat)}</div></div>
    </div>`;

    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalDetailGuru')).show();
  }

  function openForm(row = null) {
    if (!canManage || !form || !modalEl) return;
    form.reset();
    document.getElementById('guruId').value = row?.id || '';
    document.getElementById('modalGuruTitle').textContent = row ? 'Edit Guru' : 'Tambah Guru';

    const fields = ['nik', 'nip', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'agama', 'status_kepegawaian', 'nuptk', 'no_telepon', 'email', 'alamat'];
    fields.forEach((name) => {
      if (form.elements[name]) form.elements[name].value = row?.[name] ?? '';
    });
    if (form.elements.foto) form.elements.foto.value = '';

    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  }

  async function uploadPhotoPrompt(row) {
    if (!row) return;
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/png';
    input.addEventListener('change', async () => {
      if (!input.files?.[0]) return;
      const fd = new FormData();
      fd.append('foto', input.files[0]);
      try {
        const response = await fetch(`${base}/master/guru/upload-foto/${row.id}`, {
          method: 'POST',
          body: fd,
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const payload = await response.json();
        if (!response.ok || payload.status !== 'success') {
          await message(payload.message || 'Gagal memperbarui foto.', 'error');
          return;
        }
        await message(payload.message || 'Foto berhasil diperbarui.');
        load();
      } catch (error) {
        await message('Terjadi kesalahan jaringan.', 'error');
      }
    });
    input.click();
  }

  async function remove(row) {
    if (!row) return;
    let confirmed = window.confirm(`Pindahkan ${row.nama} ke Recycle Bin?`);
    if (window.Swal) {
      const result = await Swal.fire({
        icon: 'warning',
        title: 'Pindahkan ke Recycle Bin?',
        text: row.nama,
        showCancelButton: true,
        confirmButtonText: 'Ya, pindahkan',
        cancelButtonText: 'Batal',
      });
      confirmed = result.isConfirmed;
    }
    if (!confirmed) return;

    try {
      const response = await fetch(`${base}/master/guru/delete/${row.id}`, {
        method: 'DELETE',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const payload = await response.json();
      if (!response.ok || payload.status !== 'success') {
        await message(payload.message || 'Gagal menghapus data Guru.', 'error');
        return;
      }
      await message(payload.message || 'Data dipindahkan ke Recycle Bin.');
      load();
    } catch (error) {
      await message('Terjadi kesalahan jaringan.', 'error');
    }
  }

  document.getElementById('btnTambahGuru')?.addEventListener('click', () => openForm());
  document.getElementById('btnImportGuru')?.addEventListener('click', () => {
    bootstrap.Modal.getOrCreateInstance(importModalEl).show();
  });

  filterForm?.addEventListener('submit', (event) => {
    event.preventDefault();
    load();
  });

  document.getElementById('btnResetFilter')?.addEventListener('click', () => {
    filterForm?.reset();
    load();
  });

  document.getElementById('btnExportGuru')?.addEventListener('click', (event) => {
    event.preventDefault();
    const p = params();
    p.delete('format');
    window.location.href = `${base}/master/guru/export?${p}`;
  });

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const id = String(document.getElementById('guruId')?.value || '');
    const fd = new FormData(form);
    const photo = fd.get('foto');

    try {
      let response;
      if (!id) {
        response = await fetch(`${base}/master/guru/create`, {
          method: 'POST',
          body: fd,
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
      } else {
        fd.delete('foto');
        response = await fetch(`${base}/master/guru/update/${id}`, {
          method: 'PUT',
          body: new URLSearchParams(fd),
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest',
          },
        });
      }

      const payload = await response.json();
      if (!response.ok || payload.status !== 'success') {
        await message(payload.message || 'Gagal menyimpan data Guru.', 'error');
        return;
      }

      if (id && photo instanceof File && photo.size > 0) {
        const photoData = new FormData();
        photoData.append('foto', photo);
        const photoResponse = await fetch(`${base}/master/guru/upload-foto/${id}`, {
          method: 'POST',
          body: photoData,
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const photoPayload = await photoResponse.json();
        if (!photoResponse.ok || photoPayload.status !== 'success') {
          await message(`${payload.message} Namun foto gagal diperbarui: ${photoPayload.message || 'unknown error'}`, 'error');
          return;
        }
      }

      bootstrap.Modal.getInstance(modalEl)?.hide();
      await message(payload.message || 'Data Guru berhasil disimpan.');
      load();
    } catch (error) {
      await message('Terjadi kesalahan jaringan saat menyimpan data.', 'error');
    }
  });

  importForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const fd = new FormData(importForm);
    try {
      const response = await fetch(`${base}/master/guru/import`, {
        method: 'POST',
        body: fd,
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const payload = await response.json();
      if (!response.ok || payload.status !== 'success') {
        await message(payload.message || 'Import gagal.', 'error');
        return;
      }
      bootstrap.Modal.getInstance(importModalEl)?.hide();
      importForm.reset();
      await message(payload.message || 'Import berhasil.');
      load();
    } catch (error) {
      await message('Terjadi kesalahan jaringan saat import.', 'error');
    }
  });

  load();
})();
