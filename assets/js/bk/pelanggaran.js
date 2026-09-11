(() => {
  'use strict';

  const app = document.getElementById('pelanggaranApp');
  if (!app) return;

  const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
  const form = document.getElementById('formPelanggaran');
  const modalEl = document.getElementById('modalPelanggaran');

  if (!form || !modalEl) return;

  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

  function setButtonBusy(button, busy, label = 'Memproses...') {
    if (!button) return;

    if (busy) {
      if (button.dataset.busy === '1') return;

      button.dataset.busy = '1';
      button.dataset.busyHtml = button.innerHTML;
      button.disabled = true;
      button.innerHTML = `
        <span
          class="spinner-border spinner-border-sm me-2"
          role="status"
          aria-hidden="true"
        ></span>${label}
      `;
      return;
    }

    button.disabled = false;

    if (button.dataset.busyHtml !== undefined) {
      button.innerHTML = button.dataset.busyHtml;
    }

    delete button.dataset.busy;
    delete button.dataset.busyHtml;
  }

  function formSubmitButton(targetForm) {
    return targetForm?.querySelector(
      'button[type="submit"], input[type="submit"]'
    ) || null;
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
    form.elements.id.value = row?.dataset.id || '';
    form.elements.nama_pelanggaran.value = row?.dataset.nama || '';
    form.elements.kategori.value = row?.dataset.kategori || 'Ringan';
    form.elements.poin.value = row?.dataset.poin || 0;
    modal.show();
  }

  document.getElementById('btnPelanggaranBaru')
    ?.addEventListener('click', () => open());

  document.querySelectorAll('.btn-edit').forEach((button) => {
    button.addEventListener(
      'click',
      () => open(button.closest('tr'))
    );
  });

  document.querySelectorAll('.btn-delete').forEach((button) => {
    button.addEventListener('click', async () => {
      if (button.dataset.busy === '1') return;

      const id = button.closest('tr')?.dataset.id;

      if (!id || !confirm('Hapus pelanggaran ini?')) {
        return;
      }

      setButtonBusy(button, true, 'Menghapus...');

      try {
        await requestJson(
          `${base}/bk/pelanggaran/delete/${id}`,
          { method: 'DELETE' }
        );

        window.location.reload();
      } catch (error) {
        window.alert(error.message || 'Gagal menghapus.');
        setButtonBusy(button, false);
      }
    });
  });

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
    let options = {
      method: 'POST',
      body: fd,
    };

    if (id) {
      url = `${base}/bk/pelanggaran/update/${id}`;
      options = {
        method: 'PUT',
        body: new URLSearchParams(fd),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
      };
    }

    try {
      await requestJson(url, options);
      window.location.reload();
    } catch (error) {
      window.alert(error.message || 'Gagal menyimpan.');
      form.dataset.busy = '0';
      setButtonBusy(submitButton, false);
    }
  });
})();
