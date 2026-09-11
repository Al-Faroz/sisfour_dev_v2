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
const alertBox = document.getElementById('logAlert');

if (!body || !alertBox) {
  return;
}

const state = {
  limit: 25,
  offset: 0,
  total: 0,
};

const pager = window.SisfourPagination?.mount(body, {
  id: 'logActivityPager',
  label: 'log',
  onChange: (next) => {
    state.limit = next.limit;
    state.offset = next.offset;
    load();
  },
});

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
    params.set('limit', String(state.limit));
    params.set('offset', String(state.offset));
  }

  const search = document
    .getElementById('logSearch')
    ?.value
    .trim();

  const module = document
    .getElementById('logModule')
    ?.value;

  const action = document
    .getElementById('logAction')
    ?.value;

  const start = document
    .getElementById('logStart')
    ?.value;

  const end = document
    .getElementById('logEnd')
    ?.value;

  if (search) params.set('search', search);
  if (module) params.set('modul', module);
  if (action) params.set('aksi', action);
  if (start) params.set('tanggal_mulai', start);
  if (end) params.set('tanggal_selesai', end);

  return params;
}

function syncUrl() {
  const params = buildParams(true);
  params.delete('format');
  const query = params.toString();

  window.history.replaceState(
    null,
    '',
    `${window.location.pathname}${query ? `?${query}` : ''}`
  );
}

function restoreState() {
  const params =
    new URLSearchParams(window.location.search);

  const limit = Number(
    params.get('limit') || 25
  );

  const offset = Number(
    params.get('offset') || 0
  );

  state.limit = [25, 50, 100].includes(limit)
    ? limit
    : 25;

  state.offset =
    Number.isFinite(offset) && offset >= 0
      ? offset
      : 0;

  const mapping = {
    search: 'logSearch',
    modul: 'logModule',
    aksi: 'logAction',
    tanggal_mulai: 'logStart',
    tanggal_selesai: 'logEnd',
  };

  Object.entries(mapping)
    .forEach(([key, id]) => {
      const value = params.get(key);
      const element = document.getElementById(id);

      if (value !== null && element) {
        element.value = value;
      }
    });
}

function actionBadge(action) {
  const normalized =
    String(action || '').toUpperCase();

  if (
    normalized.includes('DELETE')
    || normalized.includes('HAPUS')
  ) {
    return 'danger';
  }

  if (
    normalized.includes('CREATE')
    || normalized.includes('RESTORE')
    || normalized.includes('LOGIN')
  ) {
    return 'success';
  }

  if (
    normalized.includes('UPDATE')
    || normalized.includes('RESET')
  ) {
    return 'warning';
  }

  if (
    normalized.includes('EXPORT')
    || normalized.includes('DOWNLOAD')
  ) {
    return 'info';
  }

  return 'primary';
}

async function load() {
  pager?.setDisabled(true);

  body.innerHTML = `
    <tr>
      <td colspan="5" class="text-center py-4">
        <span class="spinner-border spinner-border-sm me-2"></span>
        Memuat log...
      </td>
    </tr>
  `;

  try {
    const response = await fetch(
      `${base}/log/activity?${buildParams(true)}`,
      {
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      }
    );

    const json = await response.json();

    if (
      !response.ok
      || json.status !== 'success'
    ) {
      show(
        json.message
          || 'Log Activity gagal dimuat.'
      );
      return;
    }

    hideAlert();

    const data = json.data || {};
    const rows = Array.isArray(data.rows)
      ? data.rows
      : [];

    state.total = Number(data.total || 0);
    state.limit = Number(data.limit || state.limit);
    state.offset = Number(data.offset ?? state.offset);

    if (
      rows.length === 0
      && state.total > 0
      && state.offset >= state.total
    ) {
      state.offset = Math.floor(
        (state.total - 1) / state.limit
      ) * state.limit;

      await load();
      return;
    }

    body.innerHTML = rows.length
      ? rows.map((row) => {
        const user = row.username
          || (
            row.id_user !== null
              ? `User #${row.id_user}`
              : 'System'
          );

        return `
          <tr>
            <td>${esc(row.waktu || '-')}</td>
            <td>${esc(user)}</td>
            <td>
              <span class="badge bg-label-${actionBadge(row.aksi)}">
                ${esc(row.aksi || '-')}
              </span>
            </td>
            <td>${esc(row.modul || '-')}</td>
            <td class="text-wrap">
              ${esc(row.keterangan || '-')}
            </td>
          </tr>
        `;
      }).join('')
      : `
        <tr>
          <td
            colspan="5"
            class="text-center text-muted py-4"
          >
            Tidak ada data.
          </td>
        </tr>
      `;

    pager?.render(state);
    syncUrl();
  } catch (error) {
    show('Terjadi kesalahan jaringan.');
  } finally {
    pager?.setDisabled(false);
  }
}

function resetFilters() {
  document.getElementById('logSearch').value = '';
  document.getElementById('logModule').value = '';
  document.getElementById('logAction').value = '';
  document.getElementById('logStart').value = '';
  document.getElementById('logEnd').value = '';

  state.offset = 0;
  load();
}

function exportCsv() {
  const params = buildParams(false);

  window.location.href =
    `${base}/log/activity/export?${params}`;
}

document
  .getElementById('btnFilterLog')
  ?.addEventListener(
    'click',
    () => {
      state.offset = 0;
      load();
    }
  );

document
  .getElementById('btnResetLog')
  ?.addEventListener(
    'click',
    resetFilters
  );

document
  .getElementById('btnExportLog')
  ?.addEventListener(
    'click',
    exportCsv
  );

document
  .getElementById('logSearch')
  ?.addEventListener(
    'keydown',
    (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        state.offset = 0;
        load();
      }
    }
  );

restoreState();
load();
})();
