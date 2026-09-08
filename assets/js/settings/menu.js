(() => {
'use strict';

const app = document.getElementById('settingsMenuApp');
if (!app) return;

const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
const alertBox = document.getElementById('menuAlert');

function show(message, type = 'danger') {
  alertBox.className = `alert alert-${type}`;
  alertBox.textContent = message;
}

document.querySelectorAll('.btn-save-menu').forEach(btn => {
  btn.addEventListener('click', async () => {
    const row = btn.closest('tr');
    const id = row.dataset.menuId;
    const roles = Array.from(row.querySelectorAll('.menu-role:checked')).map(x => x.value);

    const r = await fetch(`${base}/settings/menu/update/${id}`, {
      method: 'PUT',
      body: JSON.stringify({ roles }),
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });

    const j = await r.json();

    if (!r.ok || j.status !== 'success') {
      show(j.message || 'Menu gagal diperbarui.');
      return;
    }

    show(j.message || 'Menu berhasil diperbarui.', 'success');
  });
});
})();