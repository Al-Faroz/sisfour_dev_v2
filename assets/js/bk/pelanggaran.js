(() => {
  'use strict';

  const app = document.getElementById('pelanggaranApp');
  if (!app) return;

  const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
  const form = document.getElementById('formPelanggaran');
  const modalEl = document.getElementById('modalPelanggaran');
  const modalTitle = document.getElementById('modalPelanggaranTitle');
  const tbody = document.getElementById('pelanggaranBody');
  const table = document.getElementById('tablePelanggaran');
  const mobileList = document.getElementById('pelanggaranMobileList');

  if (!form || !modalEl || !tbody || !table || !mobileList) return;

  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  const rows = Array.from(tbody.querySelectorAll('tr[data-id]'));
  const state = { limit: 25, offset: 0, total: rows.length };

  const pager = window.SisfourPagination?.mount(table, {
    id: 'pelanggaranPager',
    label: 'pelanggaran',
    onChange: (next) => {
      state.limit = next.limit;
      state.offset = next.offset;
      renderPage();
    },
  });

  const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

  const kategoriClass = (value) => ({
    Berat: 'danger',
    Sedang: 'warning',
    Ringan: 'secondary',
  }[String(value || '')] || 'secondary');

  function renderPage() {
    state.total = rows.length;
    const maxOffset = state.total > 0
      ? Math.floor((state.total - 1) / state.limit) * state.limit
      : 0;
    state.offset = Math.min(state.offset, maxOffset);

    const pageRows = rows.filter(
      (_, index) => index >= state.offset && index < state.offset + state.limit
    );

    rows.forEach((row, index) => {
      row.classList.toggle(
        'd-none',
        index < state.offset || index >= state.offset + state.limit
      );
    });

    mobileList.innerHTML = pageRows.length
      ? pageRows.map((row) => `
          <div class="list-group-item py-3" data-id="${escapeHtml(row.dataset.id || '')}">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
              <strong class="min-w-0 text-wrap flex-grow-1">${escapeHtml(row.dataset.nama || '-')}</strong>
              <span class="badge bg-label-${kategoriClass(row.dataset.kategori)} flex-shrink-0">
                ${escapeHtml(row.dataset.kategori || '-')}
              </span>
            </div>
            <div class="sisfour-mobile-actions">
              <button type="button" class="btn btn-sm btn-outline-primary sisfour-touch-target--compact btn-edit">
                <i class="bx bx-edit me-1"></i>Edit
              </button>
              <button type="button" class="btn btn-sm btn-outline-danger sisfour-touch-target--compact btn-delete">
                <i class="bx bx-trash me-1"></i>Hapus
              </button>
            </div>
          </div>
        `).join('')
      : '<div class="list-group-item sisfour-mobile-state text-muted">Belum ada Master Pelanggaran.</div>';

    pager?.render(state);
  }

  function setButtonBusy(button, busy, label = 'Memproses...') {
    if (!button) return;

    if (busy) {
      if (button.dataset.busy === '1') return;
      button.dataset.busy = '1';
      button.dataset.busyHtml = button.innerHTML;
      button.disabled = true;
      button.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${label}`;
      return;
    }

    button.disabled = false;
    if (button.dataset.busyHtml !== undefined) button.innerHTML = button.dataset.busyHtml;
    delete button.dataset.busy;
    delete button.dataset.busyHtml;
  }

  function formSubmitButton(targetForm) {
    return targetForm?.querySelector('button[type="submit"], input[type="submit"]') || null;
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

  function open(row = null) {
    form.reset();
    form.dataset.busy = '0';
    setButtonBusy(formSubmitButton(form), false);
    modalTitle.textContent = row ? 'Edit Pelanggaran' : 'Tambah Pelanggaran';
    form.elements.id.value = row?.dataset.id || '';
    form.elements.nama_pelanggaran.value = row?.dataset.nama || '';
    form.elements.kategori.value = row?.dataset.kategori || 'Ringan';
    modal.show();
  }

  document.getElementById('btnExportPelanggaran')?.addEventListener('click', () => {
    window.location.href = `${base}/bk/pelanggaran?export=1`;
  });

  document.getElementById('btnPelanggaranBaru')?.addEventListener('click', () => open());

  const sourceRowForButton = (button) => {
    const directRow = button.closest('tr[data-id]');
    if (directRow) return directRow;

    const id = button.closest('[data-id]')?.dataset.id;
    return id ? rows.find((row) => row.dataset.id === id) || null : null;
  };

  const handleListAction = async (event) => {
    const editButton = event.target.closest('.btn-edit');
    if (editButton) {
      const row = sourceRowForButton(editButton);
      if (row) open(row);
      return;
    }

    const deleteButton = event.target.closest('.btn-delete');
    if (!deleteButton || deleteButton.dataset.busy === '1') return;

    const row = sourceRowForButton(deleteButton);
    const id = row?.dataset.id;
    if (!id) return;

    const confirmation = await Swal.fire({
      icon: 'warning',
      title: 'Hapus pelanggaran?',
      html: `<strong>${escapeHtml(row.dataset.nama || '')}</strong><br><br>Data yang sudah digunakan pada Catatan Pelanggaran tidak dapat dihapus.`,
      showCancelButton: true,
      confirmButtonText: 'Ya, hapus',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#d33',
    });

    if (!confirmation.isConfirmed) return;

    setButtonBusy(deleteButton, true, 'Menghapus...');

    try {
      const payload = await requestJson(
        `${base}/bk/pelanggaran/delete/${id}`,
        { method: 'DELETE' }
      );
      await Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: payload.message || 'Master Pelanggaran berhasil dihapus.',
        timer: 1400,
        showConfirmButton: false,
      });
      window.location.reload();
    } catch (error) {
      await Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: error.message || 'Gagal menghapus.',
      });
      setButtonBusy(deleteButton, false);
    }
  };

  tbody.addEventListener('click', handleListAction);
  mobileList.addEventListener('click', handleListAction);

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (form.dataset.busy === '1') return;

    form.dataset.busy = '1';
    const submitButton = formSubmitButton(form);
    setButtonBusy(submitButton, true, 'Menyimpan...');

    const fd = new FormData(form);
    const id = String(fd.get('id') || '');
    fd.delete('id');

    let url = `${base}/bk/pelanggaran/create`;
    let options = { method: 'POST', body: fd };

    if (id) {
      url = `${base}/bk/pelanggaran/update/${id}`;
      options = {
        method: 'PUT',
        body: new URLSearchParams(fd),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      };
    }

    try {
      const payload = await requestJson(url, options);
      modal.hide();
      await Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: payload.message || 'Master Pelanggaran berhasil disimpan.',
        timer: 1400,
        showConfirmButton: false,
      });
      window.location.reload();
    } catch (error) {
      await Swal.fire({
        icon: 'error',
        title: 'Gagal',
        text: error.message || 'Gagal menyimpan.',
      });
      form.dataset.busy = '0';
      setButtonBusy(submitButton, false);
    }
  });

  renderPage();
})();
