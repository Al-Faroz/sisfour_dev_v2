(() => {
'use strict';

const app = document.getElementById('settingsMenuApp');
if (!app) return;

const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
const alertBox = document.getElementById('menuAlert');

function show(message, type = 'danger') {
  if (!alertBox) return;
  alertBox.className = `alert alert-${type}`;
  alertBox.textContent = message;
}

function setBusy(button, busy) {
  if (!button) return;

  if (busy) {
    button.dataset.busy = '1';
    button.dataset.originalHtml = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>Menyimpan...';
    return;
  }

  button.disabled = false;
  if (button.dataset.originalHtml !== undefined) {
    button.innerHTML = button.dataset.originalHtml;
  }
  delete button.dataset.busy;
  delete button.dataset.originalHtml;
}

async function parseResponse(response) {
  const payload = await response.json().catch(() => ({}));

  if (!response.ok || payload.status !== 'success') {
    throw new Error(payload.message || 'Menu gagal diperbarui.');
  }

  return payload;
}

document.querySelectorAll('.btn-save-menu').forEach(btn => {
  btn.addEventListener('click', async () => {
    if (btn.dataset.busy === '1') return;

    const row = btn.closest('tr');
    if (!row) return;

    const id = row.dataset.menuId;
    const roles = Array.from(row.querySelectorAll('.menu-role:checked')).map(x => x.value);

    setBusy(btn, true);

    try {
      const response = await fetch(`${base}/settings/menu/update/${id}`, {
        method: 'PUT',
        body: JSON.stringify({ roles }),
        headers: {
          Accept: 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      const payload = await parseResponse(response);
      show(payload.message || 'Menu berhasil diperbarui.', 'success');
    } catch (error) {
      show(error?.message || 'Menu gagal diperbarui.');
    } finally {
      setBusy(btn, false);
    }
  });
});
})();