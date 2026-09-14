(() => {
'use strict';

const app = document.getElementById('settingsSistemApp');
if (!app) return;

const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
const alertBox = document.getElementById('sistemAlert');

function show(message, type = 'danger') {
  if (!alertBox) return;

  alertBox.className = `alert alert-${type}`;
  alertBox.textContent = message;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function setBusy(form, busy, label = 'Memproses...') {
  const button = form?.querySelector('button[type="submit"]');
  if (!button) return;

  if (busy) {
    if (button.dataset.busy === '1') return;
    button.dataset.busy = '1';
    button.dataset.originalHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = `<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>${label}`;
    return;
  }

  button.disabled = false;
  if (button.dataset.originalHtml !== undefined) {
    button.innerHTML = button.dataset.originalHtml;
  }
  delete button.dataset.busy;
  delete button.dataset.originalHtml;
}

async function parseResponse(response) {
  const payload = await response.json().catch(() => ({}));

  if (!response.ok || payload.status !== 'success') {
    throw new Error(payload.message || 'Permintaan tidak dapat diproses.');
  }

  return payload;
}

const formSistem = document.getElementById('formSistem');
formSistem?.addEventListener('submit', async event => {
  event.preventDefault();
  if (event.currentTarget.dataset.busy === '1') return;

  const form = event.currentTarget;
  form.dataset.busy = '1';
  setBusy(form, true, 'Menyimpan...');

  try {
    const fd = new FormData(form);
    const payload = Object.fromEntries(fd.entries());
    payload.geofencing_aktif = form.elements.geofencing_aktif.checked ? '1' : '0';

    const response = await fetch(`${base}/settings/sistem/update`, {
      method: 'PUT',
      body: JSON.stringify(payload),
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    const result = await parseResponse(response);
    show(result.message || 'Setting berhasil disimpan.', 'success');
  } catch (error) {
    show(error.message || 'Setting gagal disimpan.');
  } finally {
    form.dataset.busy = '0';
    setBusy(form, false);
  }
});

document.querySelectorAll('.branding-form').forEach(form => {
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (form.dataset.busy === '1') return;

    form.dataset.busy = '1';
    setBusy(form, true, 'Mengunggah...');

    try {
      const fd = new FormData(form);
      fd.append('asset_type', form.dataset.type);

      const response = await fetch(`${base}/settings/sistem/upload-branding`, {
        method: 'POST',
        body: fd,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const result = await parseResponse(response);
      show(result.message || 'Branding berhasil di-upload.', 'success');
      form.reset();

      // Reload memastikan logo/icon yang baru disimpan di setting_sistem langsung
      // dipakai shell. Favicon sendiri juga memakai filemtime sebagai cache-buster.
      window.setTimeout(() => window.location.reload(), 450);
    } catch (error) {
      show(error.message || 'Upload branding gagal.');
      form.dataset.busy = '0';
      setBusy(form, false);
    }
  });
});

document.querySelectorAll('.kta-form').forEach(form => {
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (form.dataset.busy === '1') return;

    form.dataset.busy = '1';
    setBusy(form, true, 'Mengunggah...');

    try {
      const fd = new FormData(form);
      fd.append('side', form.dataset.side);

      const response = await fetch(`${base}/settings/sistem/upload-background-kta`, {
        method: 'POST',
        body: fd,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const result = await parseResponse(response);
      show(result.message || 'Template kartu berhasil di-upload.', 'success');
      form.reset();
    } catch (error) {
      show(error.message || 'Upload template kartu gagal.');
    } finally {
      form.dataset.busy = '0';
      setBusy(form, false);
    }
  });
});

const formMaintenance = document.getElementById('formMaintenance');
formMaintenance?.addEventListener('submit', async event => {
  event.preventDefault();
  if (event.currentTarget.dataset.busy === '1') return;

  const form = event.currentTarget;
  const maintenanceEnabled = form.elements.maintenance_mode.checked;

  if (maintenanceEnabled) {
    const confirmation = window.Swal
      ? await Swal.fire({
          icon: 'warning',
          title: 'Aktifkan Maintenance Mode?',
          text: 'Pengguna non-Admin Web/API akan langsung dibatasi dengan HTTP 503. Admin efektif tetap dapat mengakses sistem.',
          showCancelButton: true,
          confirmButtonText: 'Ya, aktifkan',
          cancelButtonText: 'Batal',
          confirmButtonColor: '#ff3e1d'
        })
      : { isConfirmed: false };

    if (!confirmation.isConfirmed) return;
  }

  form.dataset.busy = '1';
  setBusy(form, true, 'Menerapkan...');

  try {
    const fd = new FormData(form);
    fd.set('maintenance_mode', maintenanceEnabled ? '1' : '0');

    const message = document.querySelector('#formSistem [name="maintenance_message"]')?.value || '';
    fd.set('maintenance_message', message);

    const response = await fetch(`${base}/settings/sistem/maintenance`, {
      method: 'POST',
      body: fd,
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    const result = await parseResponse(response);
    show(result.message || 'Maintenance berhasil diperbarui.', 'success');
  } catch (error) {
    show(error.message || 'Maintenance gagal diperbarui.');
  } finally {
    form.dataset.busy = '0';
    setBusy(form, false);
  }
});
})();
