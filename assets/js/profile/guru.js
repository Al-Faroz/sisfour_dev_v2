(() => {
  'use strict';

  const app = document.getElementById('profileGuruApp');
  if (!app) return;

  const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
  const form = document.getElementById('formProfileGuru');
  const fotoForm = document.getElementById('formFotoProfileGuru');

  const addResponsiveTabs = () => {
    if (document.getElementById('profileGuruTabs')) return;

    const header = app.firstElementChild;
    if (!header) return;

    const nav = document.createElement('nav');
    nav.id = 'profileGuruTabs';
    nav.className = 'mb-4 overflow-auto';
    nav.setAttribute('aria-label', 'Navigasi Profile Guru');
    nav.innerHTML = `
      <div class="nav nav-pills flex-nowrap gap-2 text-nowrap pb-1">
        <a class="nav-link active" href="${base}/profile/guru" aria-current="page">
          <i class="bx bx-user me-1"></i>Biodata
        </a>
        <a class="nav-link" href="${base}/profile/guru/personalia">
          <i class="bx bx-history me-1"></i>Riwayat Personalia
        </a>
        <a class="nav-link" href="${base}/profile/guru/portofolio" target="_blank" rel="noopener">
          <i class="bx bx-file me-1"></i>Portofolio PDF
        </a>
      </div>
    `;
    header.insertAdjacentElement('afterend', nav);
  };

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
      const response = await fetch(`${base}/profile/guru/update`, {
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
      const response = await fetch(`${base}/profile/guru/upload-foto`, {
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

  addResponsiveTabs();
})();
