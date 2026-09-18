(() => {
  'use strict';

  const app = document.getElementById('uksMasterApp');
  if (!app) return;

  const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
  const alertBox = document.getElementById('uksMasterAlert');
  const form = document.getElementById('formUksMaster');
  const modalEl = document.getElementById('modalUksMaster');

  if (!form || !modalEl) return;

  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  const esc = (value) => {
    const el = document.createElement('div');
    el.textContent = value ?? '';
    return el.innerHTML;
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
      credentials: 'same-origin',
    });
    let payload;
    try { payload = await response.json(); } catch { throw new Error('Response server tidak valid.'); }
    if (!response.ok || payload.status !== 'success') throw new Error(payload.message || 'Proses gagal.');
    return payload;
  }

  function rowData(button) {
    const holder = button.closest('[data-json]');
    return holder ? JSON.parse(decodeURIComponent(holder.dataset.json)) : null;
  }

  function openForm(type, row = null) {
    form.reset();
    form.elements.type.value = type;
    form.elements.id.value = row?.id || '';
    form.elements.nama.value = row?.nama || '';
    form.elements.urutan.value = row?.urutan ?? 0;
    form.elements.status_aktif.value = String(row?.status_aktif ?? 1);
    modal.show();
  }

  document.querySelectorAll('.btn-master-new').forEach((button) => {
    button.addEventListener('click', () => openForm(button.dataset.type));
  });

  document.querySelectorAll('.btn-master-edit').forEach((button) => {
    button.addEventListener('click', () => {
      const row = rowData(button);
      if (row) openForm(button.dataset.type, row);
    });
  });

  document.querySelectorAll('.btn-master-delete').forEach((button) => {
    button.addEventListener('click', async () => {
      const row = rowData(button);
      if (!row || !window.Swal?.fire) return;

      const result = await Swal.fire({
        icon: 'warning',
        title: 'Arsipkan master UKS?',
        html: `<strong>${esc(row.nama)}</strong><br><small>Histori lama tetap dapat menampilkan nilai tersimpan.</small>`,
        showCancelButton: true,
        confirmButtonText: 'Ya, arsipkan',
        cancelButtonText: 'Batal',
        reverseButtons: true,
      });
      if (!result.isConfirmed) return;

      try {
        const payload = await requestJson(
          `${base}/uks/master/${encodeURIComponent(button.dataset.type)}/delete/${row.id}`,
          { method: 'DELETE' }
        );
        show(payload.message || 'Master UKS diarsipkan.', 'success');
        location.reload();
      } catch (error) {
        show(error.message || 'Gagal mengarsipkan master UKS.');
      }
    });
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const fd = new FormData(form);
    const type = String(fd.get('type') || '');
    const id = String(fd.get('id') || '');
    fd.delete('type');
    fd.delete('id');

    let url = `${base}/uks/master/${encodeURIComponent(type)}/create`;
    let options = { method: 'POST', body: fd };
    if (id) {
      url = `${base}/uks/master/${encodeURIComponent(type)}/update/${id}`;
      options = {
        method: 'PUT',
        body: new URLSearchParams(fd),
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      };
    }

    try {
      const payload = await requestJson(url, options);
      modal.hide();
      show(payload.message || 'Master UKS tersimpan.', 'success');
      location.reload();
    } catch (error) {
      show(error.message || 'Gagal menyimpan master UKS.');
    }
  });
})();
