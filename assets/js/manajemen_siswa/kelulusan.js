(() => {
    'use strict';

    const app = document.getElementById('kelulusanSiswaApp');
    if (!app) return;

    const baseUrl = app.dataset.baseUrl.replace(/\/+$/, '');
    const modalElement = document.getElementById('modalKelulusan');
    const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
    const form = document.getElementById('formKelulusan');
    const tbody = document.getElementById('tbodyLulus');
    const alumniBody = document.getElementById('tbodyAlumni');
    const alumniMobileList = document.getElementById('alumniMobileList');

    const endpoint = (path) =>
        `${baseUrl}/${path.replace(/^\/+/, '')}`;

    const escapeHtml = (value) => {
        const node = document.createElement('div');
        node.textContent = value ?? '';
        return node.innerHTML;
    };

    const parseResponse = async (response) => {
        const payload = await response.json().catch(() => ({}));

        if (!response.ok || payload.status === 'error') {
            throw new Error(payload.message || 'Permintaan gagal.');
        }

        return payload;
    };

    const showError = (message) => {
        if (typeof Swal !== 'undefined') {
            return Swal.fire({
                icon: 'error',
                title: 'Gagal',
                text: message,
            });
        }

        window.alert(message);
        return Promise.resolve();
    };

    const showSuccess = (message) => {
        if (typeof Swal !== 'undefined') {
            return Swal.fire({
                icon: 'success',
                title: 'Berhasil',
                text: message,
            });
        }

        window.alert(message || 'Berhasil.');
        return Promise.resolve();
    };

    const countSelected = () => {
        document.getElementById('jumlahLulusDipilih').textContent =
            `${tbody.querySelectorAll('.check-lulus:checked').length} siswa dipilih`;
    };

    const restoreFormData = () => {
        const data = new FormData();
        data.append('action', 'restore');

        const csrf = form.querySelector('input[type="hidden"][name]');
        if (csrf) {
            data.append(csrf.name, csrf.value);
        }

        return data;
    };

    document.addEventListener('click', async (event) => {
        const button = event.target.closest('.btn-proses-lulus');
        if (!button) return;

        const id = Number(button.dataset.id);

        try {
            const response = await fetch(
                endpoint(`manajemen-siswa/process-data/${id}`),
                {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }
            );

            const payload = await parseResponse(response);
            const data = payload.data;

            document.getElementById('idKelasLulus').value = id;
            document.getElementById('labelKelasLulus').textContent =
                button.dataset.nama || data.kelas?.nama_kelas || '';

            tbody.innerHTML = (data.siswa || []).map((siswa) => `
                <tr>
                    <td>
                        <input
                            class="form-check-input check-lulus"
                            type="checkbox"
                            name="id_siswa[]"
                            value="${siswa.id_siswa}"
                            checked
                        >
                    </td>
                    <td>${escapeHtml(siswa.nama)}</td>
                    <td class="font-monospace">${escapeHtml(siswa.nisn)}</td>
                    <td>${siswa.jenis_kelamin === 'L' ? 'L' : 'P'}</td>
                </tr>
            `).join('');

            countSelected();
            modal.show();
        } catch (error) {
            await showError(
                error.message || 'Data kelulusan gagal dimuat.'
            );
        }
    });

    [alumniBody, alumniMobileList]
        .filter(Boolean)
        .forEach((container) => container.addEventListener('click', async (event) => {
        const button = event.target.closest('.btn-restore-lulus');
        if (!button) return;

        const historyId = Number(button.dataset.historyId);
        const nama = button.dataset.nama || 'siswa';

        const confirmed = typeof Swal === 'undefined'
            ? window.confirm(`Restore ${nama} menjadi siswa Aktif?`)
            : (await Swal.fire({
                icon: 'question',
                title: 'Restore siswa Lulus?',
                html: `<strong>${escapeHtml(nama)}</strong><br>Siswa akan dikembalikan menjadi Aktif pada kelas terakhirnya.`,
                showCancelButton: true,
                confirmButtonText: 'Ya, restore',
                cancelButtonText: 'Batal',
            })).isConfirmed;

        if (!confirmed) return;

        button.disabled = true;

        try {
            const response = await fetch(
                endpoint(`manajemen-siswa/kelulusan/proses/${historyId}`),
                {
                    method: 'POST',
                    body: restoreFormData(),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }
            );

            const payload = await parseResponse(response);
            await showSuccess(payload.message || 'Siswa berhasil direstore.');
            window.location.reload();
        } catch (error) {
            button.disabled = false;
            await showError(
                error.message || 'Restore siswa Lulus gagal.'
            );
        }
    }));

    tbody.addEventListener('change', countSelected);

    document
        .getElementById('btnPilihSemuaLulus')
        .addEventListener('click', () => {
            tbody.querySelectorAll('.check-lulus').forEach((item) => {
                item.checked = true;
            });
            countSelected();
        });

    document
        .getElementById('btnKosongkanLulus')
        .addEventListener('click', () => {
            tbody.querySelectorAll('.check-lulus').forEach((item) => {
                item.checked = false;
            });
            countSelected();
        });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const id = Number(
            document.getElementById('idKelasLulus').value
        );

        if (!tbody.querySelectorAll('.check-lulus:checked').length) {
            await showError('Pilih minimal satu siswa.');
            return;
        }

        const confirmed = typeof Swal === 'undefined'
            ? window.confirm('Luluskan siswa terpilih?')
            : (await Swal.fire({
                icon: 'warning',
                title: 'Luluskan siswa terpilih?',
                text: 'Proses ini mengubah status siswa menjadi Lulus.',
                showCancelButton: true,
                confirmButtonText: 'Ya, luluskan',
                cancelButtonText: 'Batal',
            })).isConfirmed;

        if (!confirmed) return;

        try {
            const response = await fetch(
                endpoint(`manajemen-siswa/kelulusan/proses/${id}`),
                {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }
            );

            const payload = await parseResponse(response);
            modal.hide();
            await showSuccess(payload.message || 'Kelulusan berhasil.');
            window.location.reload();
        } catch (error) {
            await showError(
                error.message || 'Kelulusan siswa gagal.'
            );
        }
    });
})();
