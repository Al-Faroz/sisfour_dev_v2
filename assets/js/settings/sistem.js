(() => {
'use strict';

const app = document.getElementById('settingsSistemApp');
if (!app) return;

const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
const alertBox = document.getElementById('sistemAlert');

function show(message, type = 'danger') {
  alertBox.className = `alert alert-${type}`;
  alertBox.textContent = message;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

document.getElementById('formSistem').addEventListener('submit', async e => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const payload = Object.fromEntries(fd.entries());
  payload.geofencing_aktif = e.target.elements.geofencing_aktif.checked ? '1' : '0';

  const r = await fetch(`${base}/settings/sistem/update`, {
    method: 'PUT',
    body: JSON.stringify(payload),
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    }
  });
  const j = await r.json();

  if (!r.ok || j.status !== 'success') {
    show(j.message || 'Setting gagal disimpan.');
    return;
  }

  show(j.message || 'Setting berhasil disimpan.', 'success');
});

document.querySelectorAll('.branding-form').forEach(form => {
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(form);
    fd.append('asset_type', form.dataset.type);

    const r = await fetch(`${base}/settings/sistem/upload-branding`, {
      method: 'POST',
      body: fd,
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    });
    const j = await r.json();

    if (!r.ok || j.status !== 'success') {
      show(j.message || 'Upload branding gagal.');
      return;
    }

    show(j.message || 'Branding berhasil di-upload.', 'success');
    form.reset();
  });
});

document.querySelectorAll('.kta-form').forEach(form => {
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData(form);
    fd.append('side', form.dataset.side);

    const r = await fetch(`${base}/settings/sistem/upload-background-kta`, {
      method: 'POST',
      body: fd,
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    });
    const j = await r.json();

    if (!r.ok || j.status !== 'success') {
      show(j.message || 'Upload template kartu gagal.');
      return;
    }

    show(j.message || 'Template kartu berhasil di-upload.', 'success');
    form.reset();
  });
});

document.getElementById('formMaintenance').addEventListener('submit', async e => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const maintenanceEnabled = e.target.elements.maintenance_mode.checked;

  fd.set('maintenance_mode', maintenanceEnabled ? '1' : '0');

  const message = document.querySelector('#formSistem [name="maintenance_message"]').value;
  fd.set('maintenance_message', message);

  if (maintenanceEnabled &&
      !confirm(
        'Aktifkan Maintenance Mode? '
        + 'Pengguna non-Admin Web/API akan langsung dibatasi dengan HTTP 503. '
        + 'Admin efektif tetap dapat mengakses sistem.'
      )) {
    return;
  }

  const r = await fetch(`${base}/settings/sistem/maintenance`, {
    method: 'POST',
    body: fd,
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
  });
  const j = await r.json();

  if (!r.ok || j.status !== 'success') {
    show(j.message || 'Maintenance gagal diperbarui.');
    return;
  }

  show(j.message || 'Maintenance berhasil diperbarui.', 'success');
});
})();
