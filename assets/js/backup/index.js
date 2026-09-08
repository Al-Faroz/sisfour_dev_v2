(() => {
'use strict';

const app = document.getElementById('backupApp');

if (!app) {
  return;
}

const base = String(
  app.dataset.baseUrl || ''
).replace(/\/+$/, '');

const body = document.getElementById('backupBody');
const alertBox = document.getElementById('backupAlert');
const createButton = document.getElementById('btnCreateBackup');
const reloadButton = document.getElementById('btnReloadBackup');

function esc(value) {
  const el = document.createElement('div');
  el.textContent = value ?? '';
  return el.innerHTML;
}

function formatBytes(bytes) {
  const size = Number(bytes || 0);

  if (size < 1024) {
    return `${size} B`;
  }

  if (size < 1024 * 1024) {
    return `${(size / 1024).toFixed(2)} KB`;
  }

  return `${(size / 1024 / 1024).toFixed(2)} MB`;
}

function show(message, type = 'danger') {
  alertBox.className = `alert alert-${type}`;
  alertBox.textContent = message;
}

function hideAlert() {
  alertBox.classList.add('d-none');
}

async function load() {
  try {
    const response = await fetch(
      `${base}/backup?format=json`,
      {
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      }
    );

    const json = await response.json();

    if (!response.ok || json.status !== 'success') {
      show(
        json.message
          || 'Daftar backup gagal dimuat.'
      );
      return;
    }

    hideAlert();

    const rows = json.data?.rows || [];

    body.innerHTML = rows.length
      ? rows.map(
        row => `<tr>
          <td><code>${esc(row.filename)}</code></td>
          <td>${esc(formatBytes(row.size))}</td>
          <td>${esc(row.created_at || '-')}</td>
          <td class="text-end">
            <a
              class="btn btn-sm btn-outline-primary"
              href="${base}/backup/download/${encodeURIComponent(row.filename)}"
            >Download</a>
            <button
              type="button"
              class="btn btn-sm btn-outline-danger btn-delete-backup"
              data-filename="${esc(row.filename)}"
            >Hapus</button>
          </td>
        </tr>`
      ).join('')
      : `<tr>
          <td
            colspan="4"
            class="text-center text-muted py-4"
          >
            Belum ada file backup.
          </td>
        </tr>`;

    bindDeleteButtons();
  } catch (error) {
    show('Terjadi kesalahan jaringan.');
  }
}

async function createBackup() {
  if (!confirm(
    'Buat backup database sekarang? '
    + 'Proses dapat membutuhkan waktu bila database besar.'
  )) {
    return;
  }

  createButton.disabled = true;
  reloadButton.disabled = true;

  try {
    const response = await fetch(
      `${base}/backup/create`,
      {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      }
    );

    const json = await response.json();

    if (!response.ok || json.status !== 'success') {
      show(
        json.message
          || 'Backup gagal dibuat.'
      );
      return;
    }

    show(
      json.message
        || 'Backup berhasil dibuat.',
      'success'
    );

    await load();
  } catch (error) {
    show('Terjadi kesalahan jaringan.');
  } finally {
    createButton.disabled = false;
    reloadButton.disabled = false;
  }
}

async function deleteBackup(filename) {
  if (!confirm(
    `Hapus file backup ${filename}?`
  )) {
    return;
  }

  try {
    const response = await fetch(
      `${base}/backup/delete/${encodeURIComponent(filename)}`,
      {
        method: 'DELETE',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      }
    );

    const json = await response.json();

    if (!response.ok || json.status !== 'success') {
      show(
        json.message
          || 'Backup gagal dihapus.'
      );
      return;
    }

    show(
      json.message
        || 'Backup berhasil dihapus.',
      'success'
    );

    await load();
  } catch (error) {
    show('Terjadi kesalahan jaringan.');
  }
}

function bindDeleteButtons() {
  document
    .querySelectorAll('.btn-delete-backup')
    .forEach(button => {
      button.addEventListener(
        'click',
        () => deleteBackup(
          button.dataset.filename
        )
      );
    });
}

createButton.addEventListener(
  'click',
  createBackup
);

reloadButton.addEventListener(
  'click',
  load
);

bindDeleteButtons();
})();
