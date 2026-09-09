(() => {
  'use strict';

  const app = document.getElementById('profilePegawaiApp');
  if (!app) return;

  const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
  const form = document.getElementById('formProfilePegawai');
  const fotoForm = document.getElementById('formFotoProfilePegawai');

  const notify = async (text, error = false) => {
    if (window.Swal) {
      await Swal.fire({ icon: error ? 'error' : 'success', text, confirmButtonText: 'OK' });
    } else {
      alert(text);
    }
  };

  const setBusy = (formElement, busy) => {
    const button = formElement?.querySelector('button[type="submit"]');
    const spinner = button?.querySelector('.spinner-border');
    if (button) button.disabled = busy;
    spinner?.classList.toggle('d-none', !busy);
  };

  form?.addEventListener('submit', async (event) => {
    event.preventDefault();
    setBusy(form, true);

    try {
      const fd = new FormData(form);
      const response = await fetch(`${base}/profile/pegawai/update`, {
        method: 'PUT',
        body: new URLSearchParams(fd),
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });
      const payload = await response.json();
      if (!response.ok || payload.success !== true) {
        await notify(payload.message || 'Profile gagal diperbarui.', true);
        return;
      }
      await notify(payload.message || 'Profile berhasil diperbarui.');
      window.location.reload();
    } catch (error) {
      await notify('Terjadi kesalahan jaringan.', true);
    } finally {
      setBusy(form, false);
    }
  });

  fotoForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    setBusy(fotoForm, true);

    try {
      const response = await fetch(`${base}/profile/pegawai/upload-foto`, {
        method: 'POST',
        body: new FormData(fotoForm),
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      const payload = await response.json();
      if (!response.ok || payload.success !== true) {
        await notify(payload.message || 'Foto gagal diperbarui.', true);
        return;
      }
      await notify(payload.message || 'Foto berhasil diperbarui.');
      window.location.reload();
    } catch (error) {
      await notify('Terjadi kesalahan jaringan.', true);
    } finally {
      setBusy(fotoForm, false);
    }
  });
})();
