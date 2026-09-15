(() => {
  'use strict';

  const app = document.getElementById('kenaikanSiswaApp');
  if (!app) return;

  const baseUrl = app.dataset.baseUrl.replace(/\/+$/, '');
  const modalElement = document.getElementById('modalKenaikan');
  const modal = new bootstrap.Modal(modalElement);
  const form = document.getElementById('formKenaikan');
  const tbody = document.getElementById('tbodyNaik');
  const target = document.getElementById('kelasTujuanNaik');
  const tingkatFilter = document.getElementById('filterTingkatKenaikan');
  const sourceTable = document.getElementById('tableKenaikanKelas');
  const sourceBody = sourceTable?.querySelector('tbody');
  const sourceRows = sourceBody
    ? Array.from(sourceBody.querySelectorAll('tr')).filter((row) => row.querySelector('.btn-proses-naik'))
    : [];

  const state = { limit: 25, offset: 0, total: sourceRows.length };
  let tingkat = '';

  const endpoint = (path) => `${baseUrl}/${path.replace(/^\/+/, '')}`;
  const esc = (value) => {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
  };
  const parse = async (response) => {
    const data = await response.json().catch(() => ({}));
    if (!response.ok || data.status === 'error') {
      throw new Error(data.message || 'Permintaan gagal.');
    }
    return data;
  };
  const showError = (message) => Swal.fire({
    icon: 'error',
    title: 'Gagal',
    text: message,
  });
  const count = () => {
    document.getElementById('jumlahNaikDipilih').textContent =
      `${tbody.querySelectorAll('.check-naik:checked:not(:disabled)').length} siswa`;
  };

  const pager = sourceTable && window.SisfourPagination
    ? window.SisfourPagination.mount(sourceTable, {
        id: 'kenaikanKelasPager',
        label: 'kelas',
        onChange: (next) => {
          state.limit = next.limit;
          state.offset = next.offset;
          renderSource();
        },
      })
    : null;

  const tingkatRow = (row) => String(row.children[1]?.textContent || '').trim();

  const renderSource = () => {
    const filtered = sourceRows.filter((row) => !tingkat || tingkatRow(row) === tingkat);
    state.total = filtered.length;

    const maxOffset = state.total
      ? Math.floor((state.total - 1) / state.limit) * state.limit
      : 0;
    state.offset = Math.min(state.offset, maxOffset);

    sourceRows.forEach((row) => row.classList.add('d-none'));
    filtered
      .slice(state.offset, state.offset + state.limit)
      .forEach((row) => row.classList.remove('d-none'));

    let empty = sourceBody?.querySelector('.js-kenaikan-empty');
    if (!empty && sourceBody && sourceRows.length > 0) {
      empty = document.createElement('tr');
      empty.className = 'js-kenaikan-empty sisfour-empty-row d-none';
      empty.innerHTML = '<td colspan="5" class="text-muted">Tidak ada kelas untuk tingkat yang dipilih.</td>';
      sourceBody.appendChild(empty);
    }

    empty?.classList.toggle('d-none', filtered.length > 0);
    pager?.render(state);
  };

  tingkatFilter?.addEventListener('change', (event) => {
    tingkat = String(event.target.value || '');
    state.offset = 0;
    renderSource();
  });

  document.addEventListener('click', async (event) => {
    const button = event.target.closest('.btn-proses-naik');
    if (!button || button.disabled) return;

    const id = Number(button.dataset.id);

    try {
      const response = await fetch(
        endpoint(`manajemen-siswa/process-data/${id}`),
        {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin',
        }
      );
      const data = await parse(response);
      const payload = data.data;

      document.getElementById('idKelasAsal').value = id;
      document.getElementById('labelKelasAsal').textContent =
        `Dari ${button.dataset.nama || payload.kelas?.nama_kelas || ''}`;

      target.innerHTML = '<option value="">Pilih kelas tujuan</option>'
        + (payload.target_kelas || []).map((kelas) => `
          <option value="${kelas.id}" data-tahun="${kelas.id_tahun}">
            ${esc(kelas.nama_tahun)} - ${esc(kelas.semester)} · ${esc(kelas.nama_kelas)}
          </option>
        `).join('');

      tbody.innerHTML = (payload.siswa || []).map((siswa) => {
        const done = Boolean(siswa.sudah_dinaikkan);
        return `
          <tr class="${done ? 'table-light text-muted' : ''}">
            <td>
              <input
                class="form-check-input check-naik"
                type="checkbox"
                name="id_siswa[]"
                value="${siswa.id_siswa}"
                ${done ? 'disabled' : 'checked'}
              >
            </td>
            <td>${esc(siswa.nama)}</td>
            <td class="font-monospace">${esc(siswa.nisn)}</td>
            <td>${siswa.jenis_kelamin === 'L' ? 'L' : 'P'}</td>
            <td>
              ${done
                ? '<span class="badge bg-label-success">Sudah Dinaikkan</span>'
                : '<span class="badge bg-label-secondary">Belum Diproses</span>'}
            </td>
          </tr>
        `;
      }).join('');

      if (!(payload.target_kelas || []).length) {
        target.innerHTML = '<option value="">Kelas tujuan valid belum tersedia</option>';
      }

      document.getElementById('idTahunBaru').value = '';
      window.SisfourSearchableSelect?.enhance(modalElement);
      window.SisfourSearchableSelect?.sync(target);
      count();
      modal.show();
    } catch (error) {
      showError(error.message || 'Data kenaikan gagal dimuat.');
    }
  });

  target.addEventListener('change', () => {
    const option = target.options[target.selectedIndex];
    document.getElementById('idTahunBaru').value = option?.dataset?.tahun || '';
  });

  tbody.addEventListener('change', count);

  document.getElementById('btnPilihSemuaNaik').addEventListener('click', () => {
    tbody.querySelectorAll('.check-naik:not(:disabled)').forEach((item) => {
      item.checked = true;
    });
    count();
  });

  document.getElementById('btnKosongkanNaik').addEventListener('click', () => {
    tbody.querySelectorAll('.check-naik:not(:disabled)').forEach((item) => {
      item.checked = false;
    });
    count();
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();

    const id = Number(document.getElementById('idKelasAsal').value);
    if (!target.value) return showError('Kelas tujuan wajib dipilih.');
    if (!document.getElementById('idTahunBaru').value) return showError('Tahun ajaran tujuan tidak valid.');
    if (!tbody.querySelectorAll('.check-naik:checked:not(:disabled)').length) {
      return showError('Pilih minimal satu siswa yang belum dinaikkan.');
    }

    try {
      const response = await fetch(
        endpoint(`manajemen-siswa/kenaikan/proses/${id}`),
        {
          method: 'POST',
          body: new FormData(form),
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          credentials: 'same-origin',
        }
      );
      const data = await parse(response);
      modal.hide();
      await Swal.fire({ icon: 'success', title: 'Berhasil', text: data.message });
      window.location.reload();
    } catch (error) {
      showError(error.message || 'Kenaikan kelas gagal.');
    }
  });

  renderSource();
})();
