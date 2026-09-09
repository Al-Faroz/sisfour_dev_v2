(() => {
  'use strict';

  const app = document.getElementById('guruRecycleApp');
  if (!app) return;

  const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
  const body = document.getElementById('guruRecycleBody');

  const esc = (value) => {
    const node = document.createElement('div');
    node.textContent = value ?? '';
    return node.innerHTML;
  };

  const show = async (text, error = false) => {
    if (window.Swal) {
      await Swal.fire({ icon: error ? 'error' : 'success', text, confirmButtonText: 'OK' });
    } else {
      alert(text);
    }
  };

  async function load() {
    try {
      const response = await fetch(`${base}/master/guru/recycle?format=json`, {
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const payload = await response.json();
      if (!response.ok || payload.status !== 'success') {
        await show(payload.message || 'Gagal memuat Recycle Bin Guru.', true);
        return;
      }

      const rows = Array.isArray(payload.data) ? payload.data : [];
      body.innerHTML = rows.map((row, index) => `<tr data-id="${Number(row.id)}" data-name="${esc(row.nama)}">
        <td>${index + 1}</td>
        <td><strong>${esc(row.nama)}</strong></td>
        <td>${esc(row.nik || '-')}</td>
        <td>${esc(row.nip || '-')}</td>
        <td>${esc(row.status_kepegawaian || '-')}</td>
        <td>${esc(row.deleted_at || '-')}</td>
        <td class="text-nowrap">
          <button type="button" class="btn btn-sm btn-outline-success btn-restore"><i class="bx bx-undo me-1"></i>Restore</button>
          <button type="button" class="btn btn-sm btn-outline-danger btn-force"><i class="bx bx-trash me-1"></i>Hapus Permanen</button>
        </td>
      </tr>`).join('') || '<tr><td colspan="7" class="text-center text-muted py-4">Recycle Bin Guru kosong.</td></tr>';

      bind();
    } catch (error) {
      await show('Terjadi kesalahan jaringan.', true);
    }
  }

  function bind() {
    document.querySelectorAll('.btn-restore').forEach((button) => {
      button.addEventListener('click', () => mutate(button, 'restore', 'POST'));
    });
    document.querySelectorAll('.btn-force').forEach((button) => {
      button.addEventListener('click', () => mutate(button, 'force-delete', 'DELETE'));
    });
  }

  async function mutate(button, action, method) {
    const row = button.closest('tr');
    const id = row?.dataset.id;
    const name = row?.dataset.name || '';
    if (!id) return;

    let confirmed = confirm(action === 'restore' ? `Pulihkan ${name}?` : `Hapus permanen ${name}? Tindakan ini tidak dapat dibatalkan.`);
    if (window.Swal) {
      const result = await Swal.fire({
        icon: action === 'restore' ? 'question' : 'warning',
        title: action === 'restore' ? 'Pulihkan data?' : 'Hapus permanen?',
        text: name,
        showCancelButton: true,
        confirmButtonText: action === 'restore' ? 'Pulihkan' : 'Hapus Permanen',
        cancelButtonText: 'Batal',
      });
      confirmed = result.isConfirmed;
    }
    if (!confirmed) return;

    try {
      const response = await fetch(`${base}/master/guru/${action}/${id}`, {
        method,
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const payload = await response.json();
      if (!response.ok || payload.status !== 'success') {
        await show(payload.message || 'Operasi gagal.', true);
        return;
      }
      await show(payload.message || 'Operasi berhasil.');
      load();
    } catch (error) {
      await show('Terjadi kesalahan jaringan.', true);
    }
  }

  load();
})();
