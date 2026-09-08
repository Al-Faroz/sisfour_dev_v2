(() => {
'use strict';

const app = document.getElementById('settingsUserApp');
if (!app) return;

const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
const body = document.getElementById('userBody');
const info = document.getElementById('userInfo');
const alertBox = document.getElementById('userAlert');
const form = document.getElementById('formUser');
const resetForm = document.getElementById('formResetPassword');
const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalUser'));
const resetModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalResetPassword'));

let offset = 0;
let total = 0;
const limit = 30;

const esc = value => {
  const el = document.createElement('div');
  el.textContent = value ?? '';
  return el.innerHTML;
};

function show(message, type = 'danger') {
  alertBox.className = `alert alert-${type}`;
  alertBox.textContent = message;
}

function hide() {
  alertBox.classList.add('d-none');
}

function queryParams() {
  const p = new URLSearchParams({ format: 'json', limit, offset });
  const search = document.getElementById('userSearch').value.trim();
  const role = document.getElementById('userRoleFilter').value;
  const status = document.getElementById('userStatusFilter').value;
  if (search) p.set('search', search);
  if (role) p.set('role', role);
  if (status) p.set('status', status);
  return p;
}

async function load() {
  try {
    const r = await fetch(`${base}/settings/user?${queryParams()}`, {
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    });
    const j = await r.json();
    if (!r.ok || j.status !== 'success') {
      show(j.message || 'Gagal memuat user.');
      return;
    }

    hide();
    const d = j.data || {};
    total = Number(d.total || 0);

    body.innerHTML = (d.rows || []).map(row => {
      const secondary = (row.secondary_roles || []).map(x => `<span class="badge bg-label-secondary me-1">${esc(x)}</span>`).join('') || '-';
      return `<tr>
        <td><strong>${esc(row.username)}</strong></td>
        <td>${esc(row.identity_label || 'Tanpa relasi')}</td>
        <td>${esc(row.role || 'NULL')}</td>
        <td>${secondary}</td>
        <td>${Number(row.status_aktif) === 1 ? '<span class="badge bg-success">Aktif</span>' : '<span class="badge bg-secondary">Nonaktif</span>'}</td>
        <td>${Number(row.auth_version || 0)}</td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-primary btn-edit-user" data-id="${row.id}">Edit</button>
          <button class="btn btn-sm btn-outline-warning btn-reset-user" data-id="${row.id}">Reset</button>
          <button class="btn btn-sm btn-outline-danger btn-delete-user" data-id="${row.id}">Hapus</button>
        </td>
      </tr>`;
    }).join('') || '<tr><td colspan="7" class="text-center text-muted">Tidak ada data.</td></tr>';

    info.textContent = `${total ? offset + 1 : 0}-${Math.min(offset + limit, total)} dari ${total}`;
    document.getElementById('userPrev').disabled = offset <= 0;
    document.getElementById('userNext').disabled = offset + limit >= total;
    bindRows();
  } catch (e) {
    show('Terjadi kesalahan jaringan.');
  }
}

function resetIdentity() {
  ['id_guru', 'id_pegawai', 'id_siswa'].forEach(name => form.elements[name].value = '');
  document.getElementById('identityResult').innerHTML = '<option value="">Pilih identitas</option>';
}

function openCreate() {
  form.reset();
  form.elements.id.value = '';
  resetIdentity();
  document.getElementById('passwordCreateWrap').classList.remove('d-none');
  form.elements.password.required = true;
  modal.show();
}

async function openEdit(id) {
  const r = await fetch(`${base}/settings/user?format=json&id=${encodeURIComponent(id)}`, {
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
  });
  const j = await r.json();

  if (!r.ok || j.status !== 'success') {
    show(j.message || 'User tidak dapat dibuka.');
    return;
  }

  const u = j.data.user;
  form.reset();
  form.elements.id.value = u.id;
  form.elements.username.value = u.username || '';
  form.elements.primary_role.value = u.role || '';
  form.elements.status_aktif.value = String(u.status_aktif ?? 1);
  document.getElementById('passwordCreateWrap').classList.add('d-none');
  form.elements.password.required = false;
  form.elements.password.value = '';

  document.querySelectorAll('.secondary-role').forEach(cb => {
    cb.checked = (u.secondary_roles || []).includes(cb.value);
  });

  resetIdentity();

  let type = '';
  let identityId = '';
  let label = u.identity_label || '';

  if (u.id_guru) {
    type = 'guru';
    identityId = u.id_guru;
    form.elements.id_guru.value = identityId;
  } else if (u.id_pegawai) {
    type = 'pegawai';
    identityId = u.id_pegawai;
    form.elements.id_pegawai.value = identityId;
  } else if (u.id_siswa) {
    type = 'siswa';
    identityId = u.id_siswa;
    form.elements.id_siswa.value = identityId;
  }

  document.getElementById('identityType').value = type;
  if (type && identityId) {
    document.getElementById('identityResult').innerHTML =
      `<option value="${esc(identityId)}" selected>${esc(label)}</option>`;
  }

  modal.show();
}

async function searchIdentity() {
  const type = document.getElementById('identityType').value;
  const search = document.getElementById('identitySearch').value.trim();
  const select = document.getElementById('identityResult');

  resetIdentity();

  if (!type) return;

  const p = new URLSearchParams({ format: 'json', mode: 'options', type, search });
  const r = await fetch(`${base}/settings/user?${p}`, {
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
  });
  const j = await r.json();

  if (!r.ok || j.status !== 'success') {
    show(j.message || 'Identitas gagal dicari.');
    return;
  }

  select.innerHTML = '<option value="">Pilih identitas</option>' +
    (j.data.rows || []).map(row =>
      `<option value="${row.id}" data-used="${row.id_user || ''}">${esc(row.kode || '-')} — ${esc(row.nama)}${row.id_user ? ' [sudah terhubung]' : ''}</option>`
    ).join('');
}

function applyIdentity() {
  resetIdentity();
  const type = document.getElementById('identityType').value;
  const value = document.getElementById('identityResult').value;
  if (type && value) form.elements[`id_${type}`].value = value;
}

function bindRows() {
  document.querySelectorAll('.btn-edit-user').forEach(btn =>
    btn.addEventListener('click', () => openEdit(btn.dataset.id)));

  document.querySelectorAll('.btn-reset-user').forEach(btn =>
    btn.addEventListener('click', () => {
      resetForm.reset();
      resetForm.elements.id.value = btn.dataset.id;
      resetModal.show();
    }));

  document.querySelectorAll('.btn-delete-user').forEach(btn =>
    btn.addEventListener('click', async () => {
      if (!confirm('Hapus user ini? Untuk akun yang masih memiliki relasi data, gunakan Nonaktif bila penghapusan ditolak.')) return;
      const r = await fetch(`${base}/settings/user/delete/${btn.dataset.id}`, {
        method: 'DELETE',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
      });
      const j = await r.json();
      if (!r.ok || j.status !== 'success') {
        show(j.message || 'User gagal dihapus.');
        return;
      }
      show(j.message || 'User dihapus.', 'success');
      load();
    }));
}

document.getElementById('btnUserBaru').addEventListener('click', openCreate);
document.getElementById('btnUserCari').addEventListener('click', () => { offset = 0; load(); });
document.getElementById('userPrev').addEventListener('click', () => { offset = Math.max(0, offset - limit); load(); });
document.getElementById('userNext').addEventListener('click', () => { if (offset + limit < total) { offset += limit; load(); } });
document.getElementById('btnIdentitySearch').addEventListener('click', searchIdentity);
document.getElementById('identityType').addEventListener('change', () => {
  document.getElementById('identitySearch').value = '';
  resetIdentity();
});
document.getElementById('identityResult').addEventListener('change', applyIdentity);

form.addEventListener('submit', async e => {
  e.preventDefault();
  applyIdentity();

  const fd = new FormData(form);
  const id = String(fd.get('id') || '');
  fd.delete('id');

  let r;

  if (!id) {
    r = await fetch(`${base}/settings/user/create`, {
      method: 'POST',
      body: fd,
      headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    });
  } else {
    fd.delete('password');
    const payload = {};
    for (const [key, value] of fd.entries()) {
      if (key === 'secondary_roles[]') {
        payload.secondary_roles ??= [];
        payload.secondary_roles.push(value);
      } else {
        payload[key] = value;
      }
    }

    r = await fetch(`${base}/settings/user/update/${id}`, {
      method: 'PUT',
      body: JSON.stringify(payload),
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
  }

  const j = await r.json();

  if (!r.ok || j.status !== 'success') {
    show(j.message || 'User gagal disimpan.');
    return;
  }

  modal.hide();
  show(j.message || 'User berhasil disimpan.', 'success');
  load();
});

resetForm.addEventListener('submit', async e => {
  e.preventDefault();
  const id = resetForm.elements.id.value;
  const r = await fetch(`${base}/settings/user/reset/${id}`, {
    method: 'POST',
    body: new FormData(resetForm),
    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
  });
  const j = await r.json();

  if (!r.ok || j.status !== 'success') {
    show(j.message || 'Reset password gagal.');
    return;
  }

  resetModal.hide();
  show(j.message || 'Password berhasil direset.', 'success');
  load();
});

load();
})();