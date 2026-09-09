(() => {
    'use strict';

    const app = document.getElementById('masterSiswaApp');
    if (!app) return;

    const baseUrl = app.dataset.baseUrl.replace(/\/+$/, '');
    const canEdit = app.dataset.canEdit === '1';
    const canEditNisn = app.dataset.canEditNisn === '1';
    const canManage = app.dataset.canManage === '1';
    const canImportExport = app.dataset.canImportExport === '1';

    const table = document.getElementById('tableSiswa');
    const tbody = table.querySelector('tbody');
    const filterForm = document.getElementById('formFilterSiswa');

    let rows = [];
    let dataTable = null;
    let editingId = null;

    const modalSiswa = document.getElementById('modalSiswa')
        ? new bootstrap.Modal(document.getElementById('modalSiswa'))
        : null;

    const modalKelas = document.getElementById('modalKelasSiswa')
        ? new bootstrap.Modal(document.getElementById('modalKelasSiswa'))
        : null;

    const modalMutasi = document.getElementById('modalMutasiSiswa')
        ? new bootstrap.Modal(document.getElementById('modalMutasiSiswa'))
        : null;

    const modalImport = document.getElementById('modalImportSiswa')
        ? new bootstrap.Modal(document.getElementById('modalImportSiswa'))
        : null;

    const endpoint = (path) => `${baseUrl}/${path.replace(/^\/+/, '')}`;

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    };

    const parseResponse = async (response) => {
        const data = await response.json().catch(() => ({}));

        if (!response.ok || data.status === 'error') {
            throw new Error(data.message || 'Permintaan tidak dapat diproses.');
        }

        return data;
    };

    const showError = (error) => {
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: error?.message || 'Terjadi kesalahan.',
        });
    };

    const showSuccess = (message) => Swal.fire({
        icon: 'success',
        title: 'Berhasil',
        text: message,
        timer: 1700,
        showConfirmButton: false,
    });

    const filterParams = () => {
        const params = new URLSearchParams(new FormData(filterForm));

        for (const [key, value] of [...params.entries()]) {
            if (!String(value).trim()) {
                params.delete(key);
            }
        }

        return params;
    };

    const destroyTable = () => {
        if (dataTable && typeof dataTable.destroy === 'function') {
            dataTable.destroy();
            dataTable = null;
        }
    };

    const initTable = () => {
        if (typeof window.DataTable === 'function') {
            dataTable = new window.DataTable(table, {
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                order: [[1, 'asc']],
            });
        }
    };

    const statusBadge = (status) => {
        const map = {
            Aktif: 'success',
            Lulus: 'primary',
            Pindah: 'warning',
            Keluar: 'danger',
        };

        return `<span class="badge bg-label-${map[status] || 'secondary'}">${escapeHtml(status)}</span>`;
    };

    const renderRows = () => {
        destroyTable();

        tbody.innerHTML = rows.map((siswa, index) => {
            const foto = siswa.foto
                ? `<img src="${endpoint(`uploads/foto_siswa/${encodeURIComponent(siswa.foto)}`)}"
                        alt="" class="rounded object-fit-cover me-2" width="38" height="50">`
                : `<div class="avatar avatar-sm me-2">
                        <span class="avatar-initial rounded bg-label-secondary">
                            ${escapeHtml((siswa.nama || '?').charAt(0))}
                        </span>
                   </div>`;

            const editButton = canEdit
                ? `<button type="button" class="btn btn-sm btn-outline-primary btn-edit"
                           data-id="${siswa.id}" title="Edit biodata">
                        <i class="bx bx-edit"></i>
                   </button>`
                : '';

            const kelasButton = canManage && siswa.status_aktif === 'Aktif'
                ? `<button type="button" class="btn btn-sm btn-outline-info btn-kelas"
                           data-id="${siswa.id}" title="${siswa.id_kelas_aktif ? 'Pindah kelas' : 'Set kelas'}">
                        <i class="bx bx-building-house"></i>
                   </button>`
                : '';

            const mutasiButton = canManage && siswa.status_aktif === 'Aktif'
                ? `<button type="button" class="btn btn-sm btn-outline-warning btn-mutasi"
                           data-id="${siswa.id}" title="Mutasi keluar">
                        <i class="bx bx-transfer"></i>
                   </button>`
                : '';

            const deleteButton = canManage
                ? `<button type="button" class="btn btn-sm btn-outline-danger btn-delete"
                           data-id="${siswa.id}" title="Hapus">
                        <i class="bx bx-trash"></i>
                   </button>`
                : '';

            return `
                <tr>
                    <td>${index + 1}</td>
                    <td>
                        <div class="d-flex align-items-center">
                            ${foto}
                            <div>
                                <div class="fw-semibold">${escapeHtml(siswa.nama)}</div>
                                <small class="text-muted">
                                    ${siswa.tempat_lahir || siswa.tanggal_lahir
                                        ? `${escapeHtml(siswa.tempat_lahir || '')}${siswa.tempat_lahir && siswa.tanggal_lahir ? ', ' : ''}${escapeHtml(siswa.tanggal_lahir || '')}`
                                        : '-'}
                                </small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="font-monospace">NIK: ${escapeHtml(siswa.nik)}</div>
                        <div class="font-monospace text-muted">NISN: ${escapeHtml(siswa.nisn)}</div>
                    </td>
                    <td>${escapeHtml(siswa.nama_kelas_aktif || '-')}</td>
                    <td>${siswa.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan'}</td>
                    <td>${statusBadge(siswa.status_aktif)}</td>
                    <td>${siswa.no_telepon ? escapeHtml(siswa.no_telepon) : '<span class="text-muted">-</span>'}</td>
                    ${(canEdit || canManage)
                        ? `<td><div class="d-flex gap-1 flex-wrap">${editButton}${kelasButton}${mutasiButton}${deleteButton}</div></td>`
                        : ''}
                </tr>
            `;
        }).join('');

        initTable();
    };

    const loadData = async () => {
        try {
            const params = filterParams();
            const suffix = params.toString() ? `?${params.toString()}` : '';

            const response = await fetch(endpoint(`master/siswa/json${suffix}`), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });

            const result = await parseResponse(response);
            rows = Array.isArray(result.data) ? result.data : [];
            renderRows();
        } catch (error) {
            showError(error);
        }
    };

    filterForm.addEventListener('submit', (event) => {
        event.preventDefault();
        loadData();
    });

    document.getElementById('btnResetFilter')?.addEventListener('click', () => {
        filterForm.reset();
        loadData();
    });

    document.getElementById('btnExportSiswa')?.addEventListener('click', (event) => {
        event.preventDefault();
        const params = filterParams();
        const suffix = params.toString() ? `?${params.toString()}` : '';
        window.location.href = endpoint(`master/siswa/export${suffix}`);
    });

    if (canEdit || canManage) {
        const form = document.getElementById('formSiswa');
        const title = document.getElementById('modalSiswaTitle');
        const nisnInput = document.getElementById('nisn');
        const fotoInput = document.getElementById('foto');

        const resetForm = () => {
            editingId = null;
            form.reset();
            document.getElementById('siswaId').value = '';
            title.textContent = 'Tambah Siswa';
            nisnInput.readOnly = false;
        };

        document.getElementById('btnTambahSiswa')?.addEventListener('click', () => {
            resetForm();
            modalSiswa.show();
        });

        tbody.addEventListener('click', async (event) => {
            const edit = event.target.closest('.btn-edit');
            const kelas = event.target.closest('.btn-kelas');
            const mutasi = event.target.closest('.btn-mutasi');
            const hapus = event.target.closest('.btn-delete');

            if (edit) {
                const id = Number(edit.dataset.id);
                const siswa = rows.find((item) => Number(item.id) === id);
                if (!siswa) return;

                editingId = id;
                title.textContent = 'Edit Biodata Siswa';

                [
                    'nik','nisn','nama','jenis_kelamin','tempat_lahir','tanggal_lahir',
                    'alamat','no_telepon','kebutuhan_khusus','disabilitas','nomor_kip_pip',
                    'nama_ayah_kandung','nama_ibu_kandung','nama_wali',
                ].forEach((field) => {
                    const input = document.getElementById(field);
                    if (input) input.value = siswa[field] ?? '';
                });

                document.getElementById('siswaId').value = String(id);
                nisnInput.readOnly = !canEditNisn;
                fotoInput.value = '';
                modalSiswa.show();
                return;
            }

            if (kelas) {
                const id = Number(kelas.dataset.id);
                const siswa = rows.find((item) => Number(item.id) === id);
                if (!siswa) return;

                document.getElementById('kelasSiswaId').value = String(id);
                document.getElementById('kelasNamaSiswa').value = siswa.nama;
                document.getElementById('kelasSaatIni').value =
                    siswa.nama_kelas_aktif || 'Belum memiliki kelas';
                document.getElementById('idKelasTujuan').value =
                    siswa.id_kelas_aktif ? String(siswa.id_kelas_aktif) : '';

                modalKelas.show();
                return;
            }

            if (mutasi) {
                const id = Number(mutasi.dataset.id);
                const siswa = rows.find((item) => Number(item.id) === id);
                if (!siswa) return;

                document.getElementById('mutasiSiswaId').value = String(id);
                document.getElementById('mutasiNamaSiswa').value = siswa.nama;
                document.getElementById('mutasiStatus').value = '';
                document.getElementById('mutasiKeterangan').value = '';
                modalMutasi.show();
                return;
            }

            if (hapus) {
                const id = Number(hapus.dataset.id);
                const siswa = rows.find((item) => Number(item.id) === id);

                const confirm = await Swal.fire({
                    icon: 'warning',
                    title: 'Pindahkan ke Recycle Bin?',
                    text: siswa?.nama || 'Data Siswa',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, hapus',
                    cancelButtonText: 'Batal',
                });

                if (!confirm.isConfirmed) return;

                try {
                    const response = await fetch(endpoint(`master/siswa/delete/${id}`), {
                        method: 'DELETE',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });

                    const result = await parseResponse(response);
                    await showSuccess(result.message);
                    await loadData();
                } catch (error) {
                    showError(error);
                }
            }
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const button = document.getElementById('btnSimpanSiswa');
            const spinner = button.querySelector('.spinner-border');
            button.disabled = true;
            spinner.classList.remove('d-none');

            try {
                if (editingId === null) {
                    if (!canManage) {
                        throw new Error('Anda tidak memiliki hak untuk menambah siswa.');
                    }

                    const response = await fetch(endpoint('master/siswa/create'), {
                        method: 'POST',
                        body: new FormData(form),
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                    });

                    const result = await parseResponse(response);
                    modalSiswa.hide();
                    await showSuccess(result.message);
                    await loadData();
                } else {
                    const payload = new URLSearchParams();
                    const formData = new FormData(form);

                    for (const [key, value] of formData.entries()) {
                        if (key !== 'foto' && key !== 'csrf_test_name') {
                            payload.append(key, value);
                        }
                    }

                    const response = await fetch(endpoint(`master/siswa/update/${editingId}`), {
                        method: 'PUT',
                        body: payload,
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    });

                    const result = await parseResponse(response);

                    const foto = fotoInput.files?.[0];
                    if (foto) {
                        const fotoForm = new FormData();
                        fotoForm.append('foto', foto);

                        const fotoResponse = await fetch(
                            endpoint(`master/siswa/upload-foto/${editingId}`),
                            {
                                method: 'POST',
                                body: fotoForm,
                                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                                credentials: 'same-origin',
                            }
                        );

                        await parseResponse(fotoResponse);
                    }

                    modalSiswa.hide();
                    await showSuccess(result.message);
                    await loadData();
                }
            } catch (error) {
                showError(error);
            } finally {
                button.disabled = false;
                spinner.classList.add('d-none');
            }
        });
    }

    if (canManage) {
        const kelasForm = document.getElementById('formKelasSiswa');

        kelasForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const id = Number(document.getElementById('kelasSiswaId').value);
            const target = document.getElementById('idKelasTujuan').value;

            if (!id || !target) {
                showError(new Error('Kelas tujuan wajib dipilih.'));
                return;
            }

            const button = kelasForm.querySelector('button[type="submit"]');
            const spinner = button.querySelector('.spinner-border');
            button.disabled = true;
            spinner.classList.remove('d-none');

            try {
                const response = await fetch(endpoint(`master/siswa/mutasi/${id}`), {
                    method: 'POST',
                    body: new FormData(kelasForm),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });

                const result = await parseResponse(response);
                modalKelas.hide();
                await showSuccess(result.message);
                await loadData();
            } catch (error) {
                showError(error);
            } finally {
                button.disabled = false;
                spinner.classList.add('d-none');
            }
        });

        const mutasiForm = document.getElementById('formMutasiSiswa');

        mutasiForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const id = Number(document.getElementById('mutasiSiswaId').value);

            try {
                const response = await fetch(endpoint(`master/siswa/mutasi/${id}`), {
                    method: 'POST',
                    body: new FormData(mutasiForm),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });

                const result = await parseResponse(response);
                modalMutasi.hide();
                await showSuccess(result.message);
                await loadData();
            } catch (error) {
                showError(error);
            }
        });
    }

    if (canImportExport) {
        const importForm = document.getElementById('formImportSiswa');

        document.getElementById('btnImportSiswa')?.addEventListener('click', () => {
            importForm.reset();
            modalImport.show();
        });

        importForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const button = importForm.querySelector('button[type="submit"]');
            const spinner = button.querySelector('.spinner-border');
            button.disabled = true;
            spinner.classList.remove('d-none');

            try {
                const response = await fetch(endpoint('master/siswa/import'), {
                    method: 'POST',
                    body: new FormData(importForm),
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });

                const result = await parseResponse(response);
                modalImport.hide();
                await showSuccess(result.message);
                await loadData();
            } catch (error) {
                showError(error);
            } finally {
                button.disabled = false;
                spinner.classList.add('d-none');
            }
        });
    }

    loadData();
})();
