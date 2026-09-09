(() => {
  'use strict';

  const app = document.getElementById('personaliaApp');
  if (!app) return;

  const endpointBase = String(app.dataset.endpointBase || '').replace(/\/+$/, '');
  const canEdit = app.dataset.canEdit === '1';

  const config = {
    pendidikan: { modal: 'modalPendidikan', tab: '#tabPendidikan' },
    penugasan: { modal: 'modalPenugasan', tab: '#tabPenugasan' },
    pangkat: { modal: 'modalPangkat', tab: '#tabPangkat' },
    dokumen: { modal: 'modalDokumen', tab: '#tabDokumen' },
  };

  const notify = async (message, error = false) => {
    if (window.Swal) {
      await Swal.fire({
        icon: error ? 'error' : 'success',
        text: message,
        confirmButtonText: 'OK',
      });
      return;
    }
    window.alert(message);
  };

  const confirmDelete = async () => {
    if (window.Swal) {
      const result = await Swal.fire({
        icon: 'warning',
        title: 'Hapus record?',
        text: 'Dokumen yang melekat pada record ini juga akan dihapus.',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
      });
      return result.isConfirmed;
    }
    return window.confirm('Hapus record ini beserta dokumen yang melekat?');
  };

  const modalFor = (category) => {
    const id = config[category]?.modal;
    const element = id ? document.getElementById(id) : null;
    return element ? bootstrap.Modal.getOrCreateInstance(element) : null;
  };

  const formFor = (category) => document.querySelector(`.personalia-form[data-category="${category}"]`);

  const setBusy = (form, busy) => {
    const button = form?.querySelector('button[type="submit"]');
    const spinner = button?.querySelector('.spinner-border');
    if (button) button.disabled = busy;
    spinner?.classList.toggle('d-none', !busy);
  };

  const resetForm = (category) => {
    const form = formFor(category);
    if (!form) return;
    form.reset();
    if (form.elements.id) form.elements.id.value = '';

    if (category === 'dokumen' && form.elements.file_dokumen) {
      form.elements.file_dokumen.required = true;
      form.querySelector('.new-file-required')?.classList.remove('d-none');
    }
  };

  const fillForm = (category, row) => {
    const form = formFor(category);
    if (!form || !row) return;
    form.reset();

    Object.entries(row).forEach(([key, value]) => {
      const input = form.elements[key];
      if (!input || input.type === 'file') return;
      input.value = value ?? '';
    });

    if (form.elements.id) form.elements.id.value = row.id ?? '';

    if (category === 'dokumen' && form.elements.file_dokumen) {
      form.elements.file_dokumen.required = false;
      form.querySelector('.new-file-required')?.classList.add('d-none');
    }
  };

  const rememberTab = (category) => {
    const target = config[category]?.tab;
    if (target) window.location.hash = target;
  };

  const activateHashTab = () => {
    const hash = window.location.hash;
    if (!hash) return;
    const trigger = Array.from(document.querySelectorAll('[data-bs-toggle="tab"]'))
      .find((item) => item.getAttribute('data-bs-target') === hash);
    if (trigger) bootstrap.Tab.getOrCreateInstance(trigger).show();
  };

  if (canEdit) {
    document.querySelectorAll('.btn-add').forEach((button) => {
      button.addEventListener('click', () => {
        const category = button.dataset.category;
        if (!config[category]) return;
        resetForm(category);
        modalFor(category)?.show();
      });
    });

    document.querySelectorAll('.btn-edit-record').forEach((button) => {
      button.addEventListener('click', () => {
        const category = button.dataset.category;
        if (!config[category]) return;

        try {
          const row = JSON.parse(decodeURIComponent(button.dataset.record || ''));
          fillForm(category, row);
          modalFor(category)?.show();
        } catch (error) {
          notify('Data record tidak dapat dibaca.', true);
        }
      });
    });

    document.querySelectorAll('.personalia-form').forEach((form) => {
      form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const category = form.dataset.category;
        if (!config[category]) return;

        setBusy(form, true);
        try {
          const response = await fetch(`${endpointBase}/save/${category}`, {
            method: 'POST',
            body: new FormData(form),
            headers: {
              Accept: 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
          });
          const payload = await response.json();

          if (!response.ok || payload.status !== 'success') {
            await notify(payload.message || 'Data personalia gagal disimpan.', true);
            return;
          }

          await notify(payload.message || 'Data personalia berhasil disimpan.');
          rememberTab(category);
          window.location.reload();
        } catch (error) {
          await notify('Terjadi kesalahan jaringan saat menyimpan data.', true);
        } finally {
          setBusy(form, false);
        }
      });
    });

    document.querySelectorAll('.btn-delete-record').forEach((button) => {
      button.addEventListener('click', async () => {
        const category = button.dataset.category;
        const id = Number(button.dataset.id || 0);
        if (!config[category] || id <= 0 || !(await confirmDelete())) return;

        try {
          const response = await fetch(`${endpointBase}/delete/${category}/${id}`, {
            method: 'DELETE',
            headers: {
              Accept: 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
          });
          const payload = await response.json();

          if (!response.ok || payload.status !== 'success') {
            await notify(payload.message || 'Data personalia gagal dihapus.', true);
            return;
          }

          await notify(payload.message || 'Data personalia berhasil dihapus.');
          rememberTab(category);
          window.location.reload();
        } catch (error) {
          await notify('Terjadi kesalahan jaringan saat menghapus data.', true);
        }
      });
    });
  }

  document.querySelectorAll('[data-bs-toggle="tab"]').forEach((trigger) => {
    trigger.addEventListener('shown.bs.tab', () => {
      const target = trigger.getAttribute('data-bs-target');
      if (target) history.replaceState(null, '', target);
    });
  });

  activateHashTab();
})();
