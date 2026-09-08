(() => {
'use strict';

const app = document.getElementById('logActivityApp');

if (!app) {
  return;
}

const base = String(
  app.dataset.baseUrl || ''
).replace(/\/+$/, '');

const body = document.getElementById('logBody');
const info = document.getElementById('logInfo');
const alertBox = document.getElementById('logAlert');

let offset = 0;
let total = 0;
const limit = 50;

function esc(value) {
  const el = document.createElement('div');
  el.textContent = value ?? '';
  return el.innerHTML;
}

function show(message, type = 'danger') {
  alertBox.className = `alert alert-${type}`;
  alertBox.textContent = message;
}

function hideAlert() {
  alertBox.classList.add('d-none');
}

function buildParams(includePaging = true) {
  const params = new URLSearchParams();

  if (includePaging) {
    params.set('format', 'json');
    params.set('limit', String(limit));
    params.set('offset', String(offset));
  }

  const search = document
    .getElementById('logSearch')
    .value
    .trim();

  const module = document
    .getElementById('logModule')
    .value;

  const action = document
    .getElementById('logAction')
    .value;

  const start = document
    .getElementById('logStart')
    .value;

  const end = document
    .getElementById('logEnd')
    .value;

  if (search) params.set('search', search);
  if (module) params.set('modul', module);
  if (action) params.set('aksi', action);
  if (start) params.set('tanggal_mulai', start);
  if (end) params.set('tanggal_selesai', end);

  return params;
}

async function load() {
  try {
    const response = await fetch(
      `${base}/log/activity?${buildParams(true)}`,
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
          || 'Log Activity gagal dimuat.'
      );
      return;
    }

    hideAlert();

    const data = json.data || {};
    const rows = data.rows || [];

    total = Number(data.total || 0);

    body.innerHTML = rows.length
      ? rows.map(row => {
        const user = row.username
          || (
            row.id_user !== null
              ? `User #${row.id_user}`
              : 'System'
          );

        return `<tr>
          <td>${esc(row.waktu || '-')}</td>
          <td>${esc(user)}</td>
          <td>
            <span class="badge bg-label-primary">
              ${esc(row.aksi || '-')}
            </span>
          </td>
          <td>${esc(row.modul || '-')}</td>
          <td class="text-wrap">${esc(row.keterangan || '-')}</td>
        </tr>`;
      }).join('')
      : `<tr>
          <td
            colspan="5"
            class="text-center text-muted py-4"
          >
            Tidak ada data.
          </td>
        </tr>`;

    info.textContent =
      `${total ? offset + 1 : 0}`
      + `-${Math.min(offset + limit, total)}`
      + ` dari ${total}`;

    document.getElementById('logPrev').disabled =
      offset <= 0;

    document.getElementById('logNext').disabled =
      offset + limit >= total;
  } catch (error) {
    show('Terjadi kesalahan jaringan.');
  }
}

function resetFilters() {
  document.getElementById('logSearch').value = '';
  document.getElementById('logModule').value = '';
  document.getElementById('logAction').value = '';
  document.getElementById('logStart').value = '';
  document.getElementById('logEnd').value = '';
  offset = 0;
  load();
}

function exportCsv() {
  const params = buildParams(false);
  window.location.href =
    `${base}/log/activity/export?${params}`;
}

document
  .getElementById('btnFilterLog')
  .addEventListener(
    'click',
    () => {
      offset = 0;
      load();
    }
  );

document
  .getElementById('btnResetLog')
  .addEventListener(
    'click',
    resetFilters
  );

document
  .getElementById('btnExportLog')
  .addEventListener(
    'click',
    exportCsv
  );

document
  .getElementById('logPrev')
  .addEventListener(
    'click',
    () => {
      offset = Math.max(
        0,
        offset - limit
      );
      load();
    }
  );

document
  .getElementById('logNext')
  .addEventListener(
    'click',
    () => {
      if (offset + limit < total) {
        offset += limit;
        load();
      }
    }
  );

document
  .getElementById('logSearch')
  .addEventListener(
    'keydown',
    event => {
      if (event.key === 'Enter') {
        event.preventDefault();
        offset = 0;
        load();
      }
    }
  );

load();
})();
