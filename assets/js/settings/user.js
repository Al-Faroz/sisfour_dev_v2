(() => {
'use strict';

const app = document.getElementById('settingsUserApp');

if (!app) {
  return;
}

const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
const body = document.getElementById('userBody');
const alertBox = document.getElementById('userAlert');
const form = document.getElementById('formUser');
const resetForm = document.getElementById('formResetPassword');
const modal = bootstrap.Modal.getOrCreateInstance(
  document.getElementById('modalUser')
);
const resetModal = bootstrap.Modal.getOrCreateInstance(
  document.getElementById('modalResetPassword')
);

const state = {
  limit: 25,
  offset: 0,
  total: 0,
};

const managedById = new Map();

const footer = body?.closest('.card')?.querySelector('.card-footer');
let pagerContainer = document.getElementById('userPager');

if (!pagerContainer && footer) {
  footer.innerHTML = '';
  pagerContainer = document.createElement('div');
  pagerContainer.id = 'userPager';
  pagerContainer.className = 'w-100';
  footer.appendChild(pagerContainer);
}

const pager = pagerContainer && window.SisfourPagination
  ? window.SisfourPagination.create(pagerContainer, {
      label: 'user',
      onChange: (next) => {
        state.limit = next.limit;
        state.offset = next.offset;
        load();
      },
    })
  : null;

const esc = (value) => {
  const element = document.createElement('div');
  element.textContent = value ?? '';
  return element.innerHTML;
};

function show(message, type = 'danger') {
  alertBox.className = `alert alert-${type}`;
  alertBox.textContent = message;
}

function hide() {
  alertBox.classList.add('d-none');
}

function params(withPaging = true) {
  const p = new URLSearchParams({ format: 'json' });
  const search = document.getElementById('userSearch').value.trim();
  const role = document.getElementById('userRoleFilter').value;
  const status = document.getElementById('userStatusFilter').value;

  if (search) p.set('search', search);
  if (role) p.set('role', role);
  if (status) p.set('status', status);

  if (withPaging) {
    p.set('limit', String(state.limit));
    p.set('offset', String(state.offset));
  }

  return p;
}

function syncUrl() {
  const p = params(true);
  p.delete('format');
  const query = p.toString();

  window.history.replaceState(
    null,
    '',
    `${window.location.pathname}${query ? `?${query}` : ''}`
  );
}

function restoreState() {
  const p = new URLSearchParams(window.location.search);
  const limit = Number(p.get('limit') || 25);
  const offset = Number(p.get('offset') || 0);

  state.limit = [25, 50, 100].includes(limit) ? limit : 25;
  state.offset = Number.isFinite(offset) && offset >= 0 ? offset : 0;

  const mapping = {
    search: 'userSearch',
    role: 'userRoleFilter',
    status: 'userStatusFilter',
  };

  Object.entries(mapping).forEach(([key, id]) => {
    const value = p.get(key);
    const element = document.getElementById(id);

    if (value !== null && element) {
      element.value = value;
    }
  });
}

async function load() {
  pager?.setDisabled(true);

  body.innerHTML = `
    <tr>
      <td colspan="7" class="text-center py-4">
        <span class="spinner-border spinner-border-sm me-2"></span>
        Memuat user...
      </td>
    </tr>
  `;

  try {
    const response = await fetch(
      `${base}/settings/user?${params(true)}`,
      {
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      }
    );

    const json = await response.json();

    if (!response.ok || json.status !== 'success') {
      show(json.message || 'Gagal memuat user.');
      return;
    }

    hide();

    const data = json.data || {};
    state.total = Number(data.total || 0);
    state.limit = Number(data.limit || state.limit);
    state.offset = Number(data.offset || 0);

    managedById.clear();

    body.innerHTML = (data.rows || []).map((item) => {
      managedById.set(
        String(item.id),
        Boolean(item.credential_managed)
      );

      const secondary = (item.secondary_roles || [])
        .map(
          (role) =>
            `<span class="badge bg-label-secondary me-1">${esc(role)}</span>`
        )
        .join('') || '-';

      return `
        <tr>
          <td>
            <strong>${esc(item.username)}</strong>
            ${
              item.credential_managed
                ? '<br><small class="text-muted">dikelola Master</small>'
                : ''
            }
          </td>
          <td>${esc(item.identity_label || 'Tanpa relasi')}</td>
          <td>${esc(item.role || 'NULL')}</td>
          <td>${secondary}</td>
          <td>
            ${
              Number(item.status_aktif) === 1
                ? '<span class="badge bg-success">Aktif</span>'
                : '<span class="badge bg-secondary">Nonaktif</span>'
            }
          </td>
          <td>${Number(item.auth_version || 0)}</td>
          <td class="text-end">
            <button
              class="btn btn-sm btn-outline-primary btn-edit-user"
              data-id="${item.id}"
            >Edit</button>
            <button
              class="btn btn-sm btn-outline-warning btn-reset-user"
              data-id="${item.id}"
            >Reset</button>
            ${
              item.credential_managed
                ? ''
                : ` <button
                    class="btn btn-sm btn-outline-danger btn-delete-user"
                    data-id="${item.id}"
                  >Hapus</button>`
            }
          </td>
        </tr>
      `;
    }).join('') || `
      <tr>
        <td colspan="7" class="text-center text-muted py-4">
          Tidak ada data.
        </td>
      </tr>
    `;

    bindRows();
    pager?.render(state);
    syncUrl();
  } catch (error) {
    show('Terjadi kesalahan jaringan.');
  } finally {
    pager?.setDisabled(false);
  }
}

function clearIdentity() {
  ['id_guru', 'id_pegawai', 'id_siswa'].forEach((name) => {
    form.elements[name].value = '';
  });
}

function resetIdentity() {
  clearIdentity();
  document.getElementById('identityResult').innerHTML =
    '<option value="">Pilih identitas</option>';
  document.getElementById('identityHelp').textContent = '';
}

function setManagedUI(managed) {
  form.elements.username.readOnly = managed;

  document.getElementById('usernameHelp').textContent = managed
    ? 'Username otomatis mengikuti NIP/NIK dari Master.'
    : 'Username dapat diatur untuk akun non-Guru/Pegawai.';

  if (!form.elements.id.value) {
    document
      .getElementById('passwordCreateWrap')
      .classList.toggle('d-none', managed);

    form.elements.password.required = !managed;
  }
}

function openCreate() {
  form.reset();
  form.elements.id.value = '';
  resetIdentity();

  document.getElementById('identityType').disabled = false;
  document.getElementById('identitySearch').disabled = false;
  document.getElementById('identityResult').disabled = false;
  document.getElementById('btnIdentitySearch').disabled = false;
  document.getElementById('passwordCreateWrap').classList.remove('d-none');
  form.elements.password.required = true;

  setManagedUI(false);
  modal.show();
}

async function openEdit(id) {
  const response = await fetch(
    `${base}/settings/user?format=json&id=${encodeURIComponent(id)}`,
    {
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    }
  );

  const json = await response.json();

  if (!response.ok || json.status !== 'success') {
    show(json.message || 'User tidak dapat dibuka.');
    return;
  }

  const user = json.data.user;

  form.reset();
  form.elements.id.value = user.id;
  form.elements.username.value = user.username || '';
  form.elements.primary_role.value = user.role || '';
  form.elements.status_aktif.value = String(user.status_aktif ?? 1);

  document.getElementById('passwordCreateWrap').classList.add('d-none');
  form.elements.password.required = false;

  document.querySelectorAll('.secondary-role').forEach((checkbox) => {
    checkbox.checked = (user.secondary_roles || []).includes(
      checkbox.value
    );
  });

  resetIdentity();

  let type = '';
  let identityId = '';

  if (user.id_guru) {
    type = 'guru';
    identityId = user.id_guru;
  } else if (user.id_pegawai) {
    type = 'pegawai';
    identityId = user.id_pegawai;
  } else if (user.id_siswa) {
    type = 'siswa';
    identityId = user.id_siswa;
  }

  document.getElementById('identityType').value = type;

  if (type && identityId) {
    document.getElementById('identityResult').innerHTML =
      `<option value="${esc(identityId)}" selected>${esc(user.identity_label || 'Identitas')}</option>`;

    form.elements[`id_${type}`].value = identityId;
  }

  const managed = Boolean(user.credential_managed);

  setManagedUI(managed);

  document.getElementById('identityType').disabled = managed;
  document.getElementById('identitySearch').disabled = managed;
  document.getElementById('identityResult').disabled = managed;
  document.getElementById('btnIdentitySearch').disabled = managed;
  document.getElementById('identityHelp').textContent = managed
    ? 'Relasi Guru/Pegawai dikelola dari Master dan tidak dapat dipindah di halaman ini.'
    : '';

  modal.show();
}

async function searchIdentity() {
  const type = document.getElementById('identityType').value;
  const search = document.getElementById('identitySearch').value.trim();
  const select = document.getElementById('identityResult');

  resetIdentity();

  if (!type) {
    setManagedUI(false);
    return;
  }

  const query = new URLSearchParams({
    format: 'json',
    mode: 'options',
    type,
    search,
  });

  const response = await fetch(
    `${base}/settings/user?${query}`,
    {
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    }
  );

  const json = await response.json();

  if (!response.ok || json.status !== 'success') {
    show(json.message || 'Identitas gagal dicari.');
    return;
  }

  select.innerHTML =
    '<option value="">Pilih identitas</option>'
    + (json.data.rows || []).map((item) => `
        <option value="${item.id}" data-used="${item.id_user || ''}">
          ${esc(item.kode || '-')} — ${esc(item.nama)}
          ${item.id_user ? ' [sudah terhubung]' : ''}
        </option>
      `).join('');

  setManagedUI(type === 'guru' || type === 'pegawai');
}

function applyIdentity() {
  const type = document.getElementById('identityType').value;
  const value = document.getElementById('identityResult').value;

  clearIdentity();

  if (type && value) {
    form.elements[`id_${type}`].value = value;
  }

  setManagedUI(type === 'guru' || type === 'pegawai');
}

function bindRows() {
  document.querySelectorAll('.btn-edit-user').forEach((button) => {
    button.addEventListener(
      'click',
      () => openEdit(button.dataset.id)
    );
  });

  document.querySelectorAll('.btn-reset-user').forEach((button) => {
    button.addEventListener('click', () => {
      resetForm.reset();
      resetForm.elements.id.value = button.dataset.id;

      const managed = managedById.get(
        String(button.dataset.id)
      ) === true;

      document
        .getElementById('managedResetInfo')
        .classList.toggle('d-none', !managed);

      document
        .getElementById('manualResetFields')
        .classList.toggle('d-none', managed);

      resetForm.elements.password.required = !managed;
      resetForm.elements.password_confirmation.required = !managed;
      resetModal.show();
    });
  });

  document.querySelectorAll('.btn-delete-user').forEach((button) => {
    button.addEventListener('click', async () => {
      if (!confirm('Hapus user ini?')) return;

      const response = await fetch(
        `${base}/settings/user/delete/${button.dataset.id}`,
        {
          method: 'DELETE',
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        }
      );

      const json = await response.json();

      if (!response.ok || json.status !== 'success') {
        show(json.message || 'User gagal dihapus.');
        return;
      }

      show(json.message || 'User dihapus.', 'success');
      await load();
    });
  });
}

document.getElementById('btnUserBaru').addEventListener(
  'click',
  openCreate
);

document.getElementById('btnUserCari').addEventListener(
  'click',
  () => {
    state.offset = 0;
    load();
  }
);

document.getElementById('userSearch').addEventListener(
  'keydown',
  (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      state.offset = 0;
      load();
    }
  }
);

document.getElementById('btnIdentitySearch').addEventListener(
  'click',
  searchIdentity
);

document.getElementById('identityType').addEventListener(
  'change',
  () => {
    document.getElementById('identitySearch').value = '';
    resetIdentity();

    setManagedUI(
      ['guru', 'pegawai'].includes(
        document.getElementById('identityType').value
      )
    );
  }
);

document.getElementById('identityResult').addEventListener(
  'change',
  applyIdentity
);

form.addEventListener('submit', async (event) => {
  event.preventDefault();

  if (!document.getElementById('identityType').disabled) {
    applyIdentity();
  }

  const formData = new FormData(form);
  const id = String(formData.get('id') || '');
  formData.delete('id');

  let response;

  if (!id) {
    response = await fetch(
      `${base}/settings/user/create`,
      {
        method: 'POST',
        body: formData,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      }
    );
  } else {
    formData.delete('password');

    const payload = {};

    for (const [key, value] of formData.entries()) {
      if (key === 'secondary_roles[]') {
        payload.secondary_roles ??= [];
        payload.secondary_roles.push(value);
      } else {
        payload[key] = value;
      }
    }

    response = await fetch(
      `${base}/settings/user/update/${id}`,
      {
        method: 'PUT',
        body: JSON.stringify(payload),
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      }
    );
  }

  const json = await response.json();

  if (!response.ok || json.status !== 'success') {
    show(json.message || 'User gagal disimpan.');
    return;
  }

  modal.hide();
  show(json.message || 'User berhasil disimpan.', 'success');
  await load();
});

resetForm.addEventListener('submit', async (event) => {
  event.preventDefault();

  const id = resetForm.elements.id.value;

  const response = await fetch(
    `${base}/settings/user/reset/${id}`,
    {
      method: 'POST',
      body: new FormData(resetForm),
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    }
  );

  const json = await response.json();

  if (!response.ok || json.status !== 'success') {
    show(json.message || 'Reset password gagal.');
    return;
  }

  resetModal.hide();
  show(json.message || 'Password berhasil direset.', 'success');
  await load();
});

restoreState();
load();
})();
