(() => {
    'use strict';

    const app = document.getElementById('masterKelasApp');
    if (!app) return;

    const baseUrl = app.dataset.baseUrl.replace(/\/+$/, '');
    const table = document.getElementById('tableKelas');
    const tbody = table.querySelector('tbody');
    const filterForm = document.getElementById('formFilterKelas');

    let rows = [];
    let dataTable = null;
    let editingId = null;
    let activeAnggotaKelasId = null;
    let processData = null;

    const modalKelas = new bootstrap.Modal(
        document.getElementById('modalKelas')
    );

    const modalAnggota = new bootstrap.Modal(
        document.getElementById('modalAnggotaKelas')
    );

    const modalNaik = new bootstrap.Modal(
        document.getElementById('modalNaikKelas')
    );

    const modalLulus = new bootstrap.Modal(
        document.getElementById('modalLulusKelas')
    );

    const endpoint = (path) =>
        `${baseUrl}/${path.replace(/^\/+/, '')}`;

    const escapeHtml = (value) => {
        const div = document.createElement('div');
        div.textContent = value ?? '';
        return div.innerHTML;
    };

    const parseResponse = async (response) => {
        const data = await response.json().catch(() => ({}));

        if (!response.ok || data.status === 'error') {
            throw new Error(
                data.message || 'Permintaan tidak dapat diproses.'
            );
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
        timer: 1600,
        showConfirmButton: false,
    });

    const filterParams = () => {
        const params = new URLSearchParams(
            new FormData(filterForm)
        );

        for (const [key, value] of [...params.entries()]) {
            if (!String(value).trim()) {
                params.delete(key);
            }
        }

        return params;
    };

    const destroyDataTable = () => {
        if (dataTable && typeof dataTable.destroy === 'function') {
            dataTable.destroy();
            dataTable = null;
        }
    };

    const initDataTable = () => {
        if (typeof window.DataTable === 'function') {
            dataTable = new window.DataTable(table, {
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                order: [[4, 'desc'], [2, 'asc'], [3, 'asc']],
            });
        }
    };

    const renderRows = () => {
        destroyDataTable();

        tbody.innerHTML = rows.map((kelas, index) => {
            const tahun = `${escapeHtml(kelas.nama_tahun)} - ${escapeHtml(kelas.semester)}`;
            const badgeAktif = Number(kelas.tahun_aktif) === 1
                ? ' <span class="badge bg-label-success ms-1">Aktif</span>'
                : '';

            const btnLulus = String(kelas.tingkat) === '9'
                ? `
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger btn-lulus"
                        data-id="${kelas.id}"
                        title="Kelulusan"
                    >
                        <i class="bx bx-graduation"></i>
                    </button>
                `
                : '';

            return `
                <tr>
                    <td>${index + 1}</td>
                    <td>
                        <div class="fw-semibold">${escapeHtml(kelas.nama_kelas)}</div>
                    </td>
                    <td>${escapeHtml(kelas.tingkat)}</td>
                    <td>${escapeHtml(kelas.rombel)}</td>
                    <td>${tahun}${badgeAktif}</td>
                    <td>
                        <span class="badge bg-label-primary">
                            ${Number(kelas.jumlah_siswa || 0)} siswa
                        </span>
                    </td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary btn-anggota"
                                data-id="${kelas.id}"
                                title="Kelola anggota"
                            >
                                <i class="bx bx-group"></i>
                            </button>

                            <button
                                type="button"
                                class="btn btn-sm btn-outline-success btn-naik"
                                data-id="${kelas.id}"
                                title="Kenaikan kelas"
                            >
                                <i class="bx bx-up-arrow-alt"></i>
                            </button>

                            ${btnLulus}

                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary btn-edit"
                                data-id="${kelas.id}"
                                title="Edit kelas"
                            >
                                <i class="bx bx-edit"></i>
                            </button>

                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger btn-delete"
                                data-id="${kelas.id}"
                                title="Hapus kelas"
                            >
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        initDataTable();
    };

    const loadData = async () => {
        try {
            const params = filterParams();
            const suffix = params.toString()
                ? `?${params.toString()}`
                : '';

            const response = await fetch(
                endpoint(`master/kelas/json${suffix}`),
                {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }
            );

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

    document
        .getElementById('btnResetFilter')
        .addEventListener('click', () => {
            filterForm.reset();
            loadData();
        });

    const formKelas = document.getElementById('formKelas');
    const modalKelasTitle =
        document.getElementById('modalKelasTitle');

    const resetKelasForm = () => {
        editingId = null;
        formKelas.reset();
        document.getElementById('kelasId').value = '';
        modalKelasTitle.textContent = 'Tambah Kelas';
    };

    document
        .getElementById('btnTambahKelas')
        .addEventListener('click', () => {
            resetKelasForm();
            modalKelas.show();
        });

    formKelas.addEventListener('submit', async (event) => {
        event.preventDefault();

        const button =
            document.getElementById('btnSimpanKelas');

        const spinner =
            button.querySelector('.spinner-border');

        button.disabled = true;
        spinner.classList.remove('d-none');

        try {
            if (editingId === null) {
                const response = await fetch(
                    endpoint('master/kelas/create'),
                    {
                        method: 'POST',
                        body: new FormData(formKelas),
                        headers: {
                            'X-Requested-With':
                                'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    }
                );

                const result = await parseResponse(response);

                modalKelas.hide();
                await showSuccess(result.message);
                await loadData();
            } else {
                const payload = new URLSearchParams();
                const formData = new FormData(formKelas);

                for (const [key, value] of formData.entries()) {
                    if (key !== 'csrf_test_name') {
                        payload.append(key, value);
                    }
                }

                const response = await fetch(
                    endpoint(
                        `master/kelas/update/${editingId}`
                    ),
                    {
                        method: 'PUT',
                        body: payload,
                        headers: {
                            'Content-Type':
                                'application/x-www-form-urlencoded;charset=UTF-8',
                            'X-Requested-With':
                                'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    }
                );

                const result = await parseResponse(response);

                modalKelas.hide();
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

    const loadAnggota = async (idKelas) => {
        const response = await fetch(
            endpoint(`master/kelas/anggota/${idKelas}`),
            {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            }
        );

        const result = await parseResponse(response);
        const payload = result.data;

        activeAnggotaKelasId = idKelas;

        document.getElementById('anggotaKelasLabel').textContent =
            `${payload.kelas.nama_kelas} · Tahun ajaran ID ${payload.kelas.id_tahun}`;

        const tbodyAnggota =
            document.getElementById('tbodyAnggotaKelas');

        tbodyAnggota.innerHTML = payload.data.map((siswa) => {
            const isMember =
                Number(siswa.id_kelas || 0) === Number(idKelas);

            const status = isMember
                ? '<span class="badge bg-label-success">Anggota</span>'
                : '<span class="badge bg-label-secondary">Belum kelas</span>';

            const action = isMember
                ? `
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger btn-remove-anggota"
                        data-siswa-id="${siswa.id}"
                    >
                        Keluarkan
                    </button>
                `
                : `
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-primary btn-add-anggota"
                        data-siswa-id="${siswa.id}"
                    >
                        Masukkan
                    </button>
                `;

            return `
                <tr>
                    <td>${escapeHtml(siswa.nama)}</td>
                    <td class="font-monospace">${escapeHtml(siswa.nik)}</td>
                    <td class="font-monospace">${escapeHtml(siswa.nisn)}</td>
                    <td>${siswa.jenis_kelamin === 'L' ? 'L' : 'P'}</td>
                    <td>${status}</td>
                    <td>${action}</td>
                </tr>
            `;
        }).join('');
    };

    document
        .getElementById('tbodyAnggotaKelas')
        .addEventListener('click', async (event) => {
            const addButton =
                event.target.closest('.btn-add-anggota');

            const removeButton =
                event.target.closest('.btn-remove-anggota');

            if (addButton) {
                const idSiswa = Number(
                    addButton.dataset.siswaId
                );

                const formData = new FormData();
                formData.append('id_siswa', String(idSiswa));

                try {
                    const response = await fetch(
                        endpoint(
                            `master/kelas/anggota/add/${activeAnggotaKelasId}`
                        ),
                        {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-Requested-With':
                                    'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        }
                    );

                    const result =
                        await parseResponse(response);

                    await showSuccess(result.message);
                    await loadAnggota(activeAnggotaKelasId);
                    await loadData();
                } catch (error) {
                    showError(error);
                }

                return;
            }

            if (removeButton) {
                const idSiswa = Number(
                    removeButton.dataset.siswaId
                );

                const confirmation = await Swal.fire({
                    icon: 'warning',
                    title: 'Keluarkan dari kelas?',
                    text: 'Status siswa tetap Aktif, tetapi keanggotaan tahun ini akan dihapus.',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, keluarkan',
                    cancelButtonText: 'Batal',
                });

                if (!confirmation.isConfirmed) return;

                try {
                    const response = await fetch(
                        endpoint(
                            `master/kelas/anggota/remove/${activeAnggotaKelasId}/${idSiswa}`
                        ),
                        {
                            method: 'DELETE',
                            headers: {
                                'X-Requested-With':
                                    'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        }
                    );

                    const result =
                        await parseResponse(response);

                    await showSuccess(result.message);
                    await loadAnggota(activeAnggotaKelasId);
                    await loadData();
                } catch (error) {
                    showError(error);
                }
            }
        });

    const loadProcessData = async (idKelas) => {
        const response = await fetch(
            endpoint(`master/kelas/process-data/${idKelas}`),
            {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            }
        );

        const result = await parseResponse(response);
        processData = result.data;
        return processData;
    };

    const renderNaik = (payload) => {
        document.getElementById('naikKelasAsalId').value =
            String(payload.kelas.id);

        document.getElementById('naikKelasLabel').textContent =
            `Dari ${payload.kelas.nama_kelas}`;

        const target = document.getElementById('kelasTujuan');

        target.innerHTML = `
            <option value="">Pilih kelas tujuan</option>
            ${payload.target_kelas.map((kelas) => `
                <option
                    value="${kelas.id}"
                    data-tahun="${kelas.id_tahun}"
                >
                    ${escapeHtml(kelas.nama_tahun)} - ${escapeHtml(kelas.semester)}
                    · ${escapeHtml(kelas.nama_kelas)}
                </option>
            `).join('')}
        `;

        document.getElementById('tbodyNaikSiswa').innerHTML =
            payload.siswa.map((siswa) => `
                <tr>
                    <td>
                        <input
                            class="form-check-input check-naik"
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

        updateNaikCount();
    };

    const updateNaikCount = () => {
        const count = document.querySelectorAll(
            '.check-naik:checked'
        ).length;

        document.getElementById('jumlahNaikDipilih').textContent =
            `${count} siswa`;
    };

    document
        .getElementById('tbodyNaikSiswa')
        .addEventListener('change', updateNaikCount);

    document
        .getElementById('btnPilihSemuaNaik')
        .addEventListener('click', () => {
            document.querySelectorAll('.check-naik')
                .forEach((checkbox) => {
                    checkbox.checked = true;
                });

            updateNaikCount();
        });

    document
        .getElementById('btnKosongkanNaik')
        .addEventListener('click', () => {
            document.querySelectorAll('.check-naik')
                .forEach((checkbox) => {
                    checkbox.checked = false;
                });

            updateNaikCount();
        });

    document
        .getElementById('formNaikKelas')
        .addEventListener('submit', async (event) => {
            event.preventDefault();

            const idKelasAsal = Number(
                document.getElementById('naikKelasAsalId').value
            );

            const target =
                document.getElementById('kelasTujuan');

            const selectedOption =
                target.options[target.selectedIndex];

            const idTahunBaru = Number(
                selectedOption?.dataset?.tahun || 0
            );

            const formData = new FormData(event.currentTarget);
            formData.append(
                'id_tahun_baru',
                String(idTahunBaru)
            );

            const count = document.querySelectorAll(
                '.check-naik:checked'
            ).length;

            if (count === 0) {
                showError(
                    new Error('Pilih minimal satu siswa.')
                );
                return;
            }

            const confirmation = await Swal.fire({
                icon: 'question',
                title: 'Proses kenaikan kelas?',
                text: `${count} siswa akan dipindahkan ke kelas tujuan.`,
                showCancelButton: true,
                confirmButtonText: 'Proses',
                cancelButtonText: 'Batal',
            });

            if (!confirmation.isConfirmed) return;

            try {
                const response = await fetch(
                    endpoint(
                        `master/kelas/naik/${idKelasAsal}`
                    ),
                    {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With':
                                'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    }
                );

                const result =
                    await parseResponse(response);

                modalNaik.hide();
                await showSuccess(result.message);
                await loadData();
            } catch (error) {
                showError(error);
            }
        });

    const renderLulus = (payload) => {
        document.getElementById('lulusKelasId').value =
            String(payload.kelas.id);

        document.getElementById('lulusKelasLabel').textContent =
            payload.kelas.nama_kelas;

        document.getElementById('tbodyLulusSiswa').innerHTML =
            payload.siswa.map((siswa) => `
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

        updateLulusCount();
    };

    const updateLulusCount = () => {
        const count = document.querySelectorAll(
            '.check-lulus:checked'
        ).length;

        document.getElementById('jumlahLulusDipilih').textContent =
            `${count} siswa dipilih`;
    };

    document
        .getElementById('tbodyLulusSiswa')
        .addEventListener('change', updateLulusCount);

    document
        .getElementById('btnPilihSemuaLulus')
        .addEventListener('click', () => {
            document.querySelectorAll('.check-lulus')
                .forEach((checkbox) => {
                    checkbox.checked = true;
                });

            updateLulusCount();
        });

    document
        .getElementById('btnKosongkanLulus')
        .addEventListener('click', () => {
            document.querySelectorAll('.check-lulus')
                .forEach((checkbox) => {
                    checkbox.checked = false;
                });

            updateLulusCount();
        });

    document
        .getElementById('formLulusKelas')
        .addEventListener('submit', async (event) => {
            event.preventDefault();

            const idKelas = Number(
                document.getElementById('lulusKelasId').value
            );

            const count = document.querySelectorAll(
                '.check-lulus:checked'
            ).length;

            if (count === 0) {
                showError(
                    new Error('Pilih minimal satu siswa.')
                );
                return;
            }

            const confirmation = await Swal.fire({
                icon: 'warning',
                title: 'Luluskan siswa?',
                text: `${count} siswa akan berstatus Lulus dan kartu pelajar dinonaktifkan.`,
                showCancelButton: true,
                confirmButtonText: 'Ya, proses',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33',
            });

            if (!confirmation.isConfirmed) return;

            try {
                const response = await fetch(
                    endpoint(
                        `master/kelas/lulus/${idKelas}`
                    ),
                    {
                        method: 'POST',
                        body: new FormData(event.currentTarget),
                        headers: {
                            'X-Requested-With':
                                'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    }
                );

                const result =
                    await parseResponse(response);

                modalLulus.hide();
                await showSuccess(result.message);
                await loadData();
            } catch (error) {
                showError(error);
            }
        });

    tbody.addEventListener('click', async (event) => {
        const edit = event.target.closest('.btn-edit');
        const hapus = event.target.closest('.btn-delete');
        const anggota = event.target.closest('.btn-anggota');
        const naik = event.target.closest('.btn-naik');
        const lulus = event.target.closest('.btn-lulus');

        if (edit) {
            const id = Number(edit.dataset.id);
            const kelas = rows.find(
                (item) => Number(item.id) === id
            );

            if (!kelas) return;

            editingId = id;
            modalKelasTitle.textContent = 'Edit Kelas';
            document.getElementById('kelasId').value = String(id);
            document.getElementById('tingkat').value =
                String(kelas.tingkat);
            document.getElementById('rombel').value =
                kelas.rombel;
            document.getElementById('id_tahun').value =
                String(kelas.id_tahun);

            modalKelas.show();
            return;
        }

        if (hapus) {
            const id = Number(hapus.dataset.id);
            const kelas = rows.find(
                (item) => Number(item.id) === id
            );

            const confirmation = await Swal.fire({
                icon: 'warning',
                title: 'Pindahkan ke Recycle Bin?',
                text: kelas?.nama_kelas || 'Data Kelas',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
            });

            if (!confirmation.isConfirmed) return;

            try {
                const response = await fetch(
                    endpoint(`master/kelas/delete/${id}`),
                    {
                        method: 'DELETE',
                        headers: {
                            'X-Requested-With':
                                'XMLHttpRequest',
                        },
                        credentials: 'same-origin',
                    }
                );

                const result =
                    await parseResponse(response);

                await showSuccess(result.message);
                await loadData();
            } catch (error) {
                showError(error);
            }

            return;
        }

        if (anggota) {
            const id = Number(anggota.dataset.id);

            try {
                await loadAnggota(id);
                modalAnggota.show();
            } catch (error) {
                showError(error);
            }

            return;
        }

        if (naik) {
            const id = Number(naik.dataset.id);

            try {
                const payload = await loadProcessData(id);
                renderNaik(payload);
                modalNaik.show();
            } catch (error) {
                showError(error);
            }

            return;
        }

        if (lulus) {
            const id = Number(lulus.dataset.id);

            try {
                const payload = await loadProcessData(id);
                renderLulus(payload);
                modalLulus.show();
            } catch (error) {
                showError(error);
            }
        }
    });

    loadData();
})();
