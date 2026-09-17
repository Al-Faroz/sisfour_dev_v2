(() => {
  'use strict';

  const app = document.getElementById('bkKonselingSettingsApp');
  const form = document.getElementById('formKonselingSettings');
  const alertBox = document.getElementById('konselingSettingsAlert');
  const resetButton = document.getElementById('btnResetKonselingSettings');

  if (!app || !form) return;

  const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');

  function show(message, type = 'danger') {
    if (!alertBox) return;
    alertBox.className = `alert alert-${type}`;
    alertBox.textContent = message;
    alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
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

  async function askResetConfirmation() {
    if (!window.Swal?.fire) {
      show('Dialog konfirmasi tidak tersedia. Muat ulang halaman lalu coba lagi.');
      return false;
    }

    const result = await window.Swal.fire({
      title: 'Kembalikan pengaturan?',
      text: 'Seluruh pilihan Form Konseling akan dikembalikan ke default.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, kembalikan',
      cancelButtonText: 'Batal',
      reverseButtons: true,
    });

    return Boolean(result.isConfirmed);
  }

  function fill(options = {}) {
    const topik = options.topik || {};
    const setLines = (name, values) => {
      const element = form.elements[name];
      if (element) element.value = Array.isArray(values) ? values.join('\n') : '';
    };

    setLines('bentuk_layanan', options.bentuk_layanan || []);
    setLines('cara_hadir', options.cara_hadir || []);
    setLines('topik_pribadi', topik.Pribadi || []);
    setLines('topik_sosial', topik.Sosial || []);
    setLines('topik_belajar', topik.Belajar || []);
    setLines('topik_karier', topik.Karier || []);
    setLines('rencana', options.rencana || []);
  }

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const button = form.querySelector('button[type="submit"]');
    if (button?.dataset.busy === '1') return;

    setBusy(button, true, 'Menyimpan...');

    try {
      const payload = await requestJson(`${base}/bk/konseling/settings`, {
        method: 'POST',
        body: new FormData(form),
      });
      fill(payload.data?.options || {});
      show(payload.message || 'Pengaturan berhasil disimpan.', 'success');
    } catch (error) {
      show(error.message || 'Pengaturan gagal disimpan.');
    } finally {
      setBusy(button, false);
    }
  });

  resetButton?.addEventListener('click', async () => {
    if (resetButton.dataset.busy === '1') return;
    if (!await askResetConfirmation()) return;

    setBusy(resetButton, true, 'Memulihkan...');

    try {
      const payload = await requestJson(`${base}/bk/konseling/settings/reset`, {
        method: 'POST',
      });
      fill(payload.data?.options || {});
      show(payload.message || 'Pengaturan default berhasil dipulihkan.', 'success');
    } catch (error) {
      show(error.message || 'Pengaturan default gagal dipulihkan.');
    } finally {
      setBusy(resetButton, false);
    }
  });
})();
