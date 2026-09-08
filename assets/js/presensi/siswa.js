(() => {
    'use strict';

    const app = document.getElementById('presensiSiswaApp');

    if (!app) {
        return;
    }

    const baseUrl = app.dataset.baseUrl || '/';
    const elTanggal = document.getElementById('presensiTanggal');
    const elKelas = document.getElementById('presensiKelas');
    const elSesi = document.getElementById('presensiSesi');
    const btnMuat = document.getElementById('btnMuatPresensi');
    const btnSimpan = document.getElementById('btnSimpanPresensi');
    const info = document.getElementById('presensiInfo');
    const card = document.getElementById('presensiCard');
    const cardTitle = document.getElementById('presensiCardTitle');
    const cardMeta = document.getElementById('presensiCardMeta');
    const capabilityBadge = document.getElementById('presensiCapability');
    const revisionBadge = document.getElementById('presensiRevisionBadge');
    const geoNote = document.getElementById('presensiGeoNote');
    const tbody = document.getElementById('presensiTableBody');

    const state = {
        items: [],
        capability: '',
        geofenceRequired: false,
        submitted: false,
        canRevise: false,
    };

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const endpoint = (path) => new URL(path, baseUrl).toString();

    const showInfo = (message, type = 'info') => {
        info.className = `alert alert-${type}`;
        info.textContent = message;
        info.classList.remove('d-none');
    };

    const hideInfo = () => {
        info.classList.add('d-none');
        info.textContent = '';
    };

    const setLoading = (loading) => {
        btnMuat.disabled = loading;
        btnSimpan.disabled = loading;

        if (loading) {
            btnMuat.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Memuat';
        } else {
            btnMuat.innerHTML = '<i class="bx bx-search-alt me-1"></i> Muat';
        }
    };

    const requestJson = async (url, options = {}) => {
        const response = await fetch(url, options);
        let payload = null;

        try {
            payload = await response.json();
        } catch (error) {
            payload = null;
        }

        if (!response.ok || payload?.status === 'error') {
            const err = new Error(payload?.message || `Request gagal (${response.status}).`);
            err.status = response.status;
            err.payload = payload;
            throw err;
        }

        return payload;
    };

    const refreshKelas = async () => {
        const tanggal = elTanggal.value;

        if (!tanggal) {
            return;
        }

        const current = elKelas.value;
        const url = new URL(endpoint('presensi/siswa'));
        url.searchParams.set('format', 'json');
        url.searchParams.set('tanggal', tanggal);

        try {
            const payload = await requestJson(url.toString());
            const rows = payload?.data?.kelas || [];

            elKelas.innerHTML = '<option value="">Pilih kelas</option>';

            rows.forEach((kelas) => {
                const option = document.createElement('option');
                option.value = String(kelas.id);
                option.textContent = kelas.nama_kelas;

                if (String(kelas.id) === current) {
                    option.selected = true;
                }

                elKelas.appendChild(option);
            });
        } catch (error) {
            showInfo(error.message, 'danger');
        }
    };

    const statusButtons = (item) => {
        const statuses = ['Hadir', 'Sakit', 'Izin', 'Alpha'];

        return statuses.map((status) => {
            const active = item.status === status;
            const klass = active ? 'btn-primary' : 'btn-outline-secondary';

            return `
                <button
                    type="button"
                    class="btn btn-sm ${klass} presensi-status-btn"
                    data-id-siswa="${item.id_siswa}"
                    data-status="${status}"
                >${status}</button>
            `;
        }).join('');
    };

    const renderItems = () => {
        if (state.items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-4">Tidak ada siswa.</td></tr>';
            return;
        }

        tbody.innerHTML = state.items.map((item, index) => `
            <tr data-id-siswa="${item.id_siswa}">
                <td>${index + 1}</td>
                <td class="fw-semibold">${escapeHtml(item.nama)}</td>
                <td>${escapeHtml(item.nisn)}</td>
                <td>
                    <div class="btn-group flex-wrap" role="group" aria-label="Status ${escapeHtml(item.nama)}">
                        ${statusButtons(item)}
                    </div>
                </td>
            </tr>
        `).join('');
    };

    const renderContext = (data) => {
        state.items = Array.isArray(data.items) ? data.items : [];
        state.capability = data.capability || '';
        state.geofenceRequired = Boolean(data.geofence_required);
        state.submitted = Boolean(data.submitted);
        state.canRevise = Boolean(data.can_revise);

        cardTitle.textContent = `Kelas ${data?.kelas?.nama_kelas || ''}`;
        cardMeta.textContent = `${data.tanggal || ''} · ${data.sesi || ''} · ${state.items.length} siswa`;
        capabilityBadge.textContent = state.capability === 'GURU_TERJADWAL'
            ? 'Guru Terjadwal'
            : state.capability === 'WALI'
                ? 'Wali Kelas'
                : 'Administratif';

        revisionBadge.classList.toggle('d-none', !state.submitted);

        if (state.geofenceRequired) {
            geoNote.textContent = 'Geofencing wajib. Browser akan meminta lokasi saat Presensi disimpan.';
        } else {
            geoNote.textContent = 'Geofencing tidak diwajibkan untuk capability ini.';
        }

        btnSimpan.classList.toggle('d-none', state.submitted && !state.canRevise);
        btnSimpan.innerHTML = state.submitted && state.canRevise
            ? '<i class="bx bx-save me-1"></i> Simpan Revisi'
            : '<i class="bx bx-save me-1"></i> Simpan Presensi';

        renderItems();
        card.classList.remove('d-none');
    };

    const loadPresensi = async () => {
        hideInfo();

        const idKelas = elKelas.value;
        const tanggal = elTanggal.value;
        const sesi = elSesi.value;

        if (!tanggal || !idKelas || !sesi) {
            showInfo('Tanggal, kelas, dan sesi wajib dipilih.', 'warning');
            return;
        }

        setLoading(true);

        const url = new URL(endpoint(`presensi/siswa/input/${encodeURIComponent(idKelas)}/json`));
        url.searchParams.set('tanggal', tanggal);
        url.searchParams.set('sesi', sesi);

        try {
            const payload = await requestJson(url.toString());
            renderContext(payload.data);
            showInfo(payload.message || 'Data Presensi berhasil dimuat.', 'success');
        } catch (error) {
            card.classList.add('d-none');

            if (error.status === 409 && error.payload?.data?.code === 'ALREADY_SUBMITTED') {
                showInfo(error.message, 'warning');
            } else {
                showInfo(error.message, 'danger');
            }
        } finally {
            setLoading(false);
        }
    };

    const getLocation = () => new Promise((resolve, reject) => {
        if (!navigator.geolocation) {
            reject(new Error('Browser tidak mendukung geolocation.'));
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => resolve({
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
            }),
            () => reject(new Error('Lokasi tidak dapat diperoleh. Izinkan akses lokasi lalu coba lagi.')),
            {
                enableHighAccuracy: true,
                timeout: 15000,
                maximumAge: 0,
            }
        );
    });

    const savePresensi = async () => {
        if (state.items.length === 0) {
            showInfo('Tidak ada data siswa untuk disimpan.', 'warning');
            return;
        }

        const payload = {
            id_kelas: Number(elKelas.value),
            tanggal: elTanggal.value,
            sesi: elSesi.value,
            items: state.items.map((item) => ({
                id_siswa: Number(item.id_siswa),
                status: item.status,
            })),
        };

        if (state.geofenceRequired) {
            try {
                const location = await getLocation();
                payload.latitude = location.latitude;
                payload.longitude = location.longitude;
            } catch (error) {
                showInfo(error.message, 'danger');
                return;
            }
        }

        const confirmed = typeof Swal === 'undefined'
            ? window.confirm(state.submitted ? 'Simpan revisi Presensi?' : 'Simpan Presensi kelas ini?')
            : (await Swal.fire({
                title: state.submitted ? 'Simpan revisi?' : 'Simpan Presensi?',
                text: `${state.items.length} siswa akan diproses secara atomic.`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal',
            })).isConfirmed;

        if (!confirmed) {
            return;
        }

        btnSimpan.disabled = true;
        const original = btnSimpan.innerHTML;
        btnSimpan.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan';

        try {
            const response = await requestJson(endpoint('presensi/siswa/save'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            if (typeof Swal !== 'undefined') {
                await Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: response.message || 'Presensi berhasil disimpan.',
                });
            } else {
                window.alert(response.message || 'Presensi berhasil disimpan.');
            }

            if (state.capability === 'GURU_TERJADWAL' && !state.submitted) {
                state.items = [];
                card.classList.add('d-none');
                showInfo('Presensi sudah tersimpan. Guru biasa tidak dapat membuka kembali data saved.', 'success');
            } else {
                await loadPresensi();
            }
        } catch (error) {
            showInfo(error.message, 'danger');

            if (typeof Swal !== 'undefined') {
                await Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: error.message,
                });
            }
        } finally {
            btnSimpan.disabled = false;
            btnSimpan.innerHTML = original;
        }
    };

    tbody.addEventListener('click', (event) => {
        const button = event.target.closest('.presensi-status-btn');

        if (!button) {
            return;
        }

        const idSiswa = Number(button.dataset.idSiswa);
        const status = button.dataset.status;
        const item = state.items.find((row) => Number(row.id_siswa) === idSiswa);

        if (!item) {
            return;
        }

        item.status = status;
        renderItems();
    });

    elTanggal.addEventListener('change', async () => {
        card.classList.add('d-none');
        hideInfo();
        await refreshKelas();
    });

    elKelas.addEventListener('change', () => card.classList.add('d-none'));
    elSesi.addEventListener('change', () => card.classList.add('d-none'));
    btnMuat.addEventListener('click', loadPresensi);
    btnSimpan.addEventListener('click', savePresensi);

    const selectedKelas = app.dataset.selectedKelas || '';

    if (selectedKelas && elKelas.querySelector(`option[value="${CSS.escape(selectedKelas)}"]`)) {
        elKelas.value = selectedKelas;
    }

    if (app.dataset.selectedSesi) {
        elSesi.value = app.dataset.selectedSesi;
    }
})();
