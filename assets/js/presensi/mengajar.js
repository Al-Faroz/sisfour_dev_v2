(() => {
    'use strict';

    const app = document.getElementById('presensiMengajarApp');
    if (!app) return;

    const baseUrl = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
    const tanggalInput = document.getElementById('jurnalTanggal');
    const guruSelect = document.getElementById('jurnalGuru');
    const jadwalSelect = document.getElementById('jurnalJadwal');
    const btnMuat = document.getElementById('btnMuatJurnal');
    const info = document.getElementById('jurnalInfo');
    const card = document.getElementById('jurnalCard');
    const cardTitle = document.getElementById('jurnalCardTitle');
    const cardMeta = document.getElementById('jurnalCardMeta');
    const capabilityBadge = document.getElementById('jurnalCapability');
    const revisionBadge = document.getElementById('jurnalRevisionBadge');
    const materiInput = document.getElementById('jurnalMateri');
    const btnSimpan = document.getElementById('btnSimpanJurnal');
    const geoNote = document.getElementById('jurnalGeoNote');
    const statusButtons = Array.from(document.querySelectorAll('.jurnal-status'));
    const isOperationalGuru = document.body.classList.contains('sisfour-role-guru');

    let current = null;
    let selectedStatus = 'Hadir';
    let saveBusy = false;
    let loadBusy = false;
    let dirty = false;

    function url(path) {
        return `${baseUrl}/${String(path).replace(/^\/+/, '')}`;
    }

    function showInfo(message, type = 'info') {
        info.className = `alert alert-${type}`;
        info.textContent = message;
        info.classList.remove('d-none');
    }

    function hideInfo() {
        info.classList.add('d-none');
        info.textContent = '';
    }

    async function requestJson(requestUrl, options = {}) {
        let response;

        try {
            response = await fetch(requestUrl, options);
        } catch (error) {
            const networkError = new Error('Koneksi ke server gagal. Periksa jaringan lalu coba lagi.');
            networkError.network = true;
            throw networkError;
        }

        let json = null;
        try {
            json = await response.json();
        } catch (error) {
            json = null;
        }

        if (!response.ok || json?.status === 'error') {
            const message = response.status === 401
                ? 'Sesi Anda telah berakhir. Silakan login kembali.'
                : json?.message || `Request gagal (${response.status}).`;
            const requestError = new Error(message);
            requestError.status = response.status;
            requestError.payload = json;
            throw requestError;
        }

        if (!json) {
            throw new Error('Respons server tidak valid. Silakan muat ulang halaman.');
        }

        return json;
    }

    function normalSaveLabel() {
        return current?.submitted
            ? '<i class="bx bx-save me-1"></i> Simpan Revisi'
            : '<i class="bx bx-save me-1"></i> Simpan Jurnal';
    }

    function hasScheduleOptions() {
        return Array.from(jadwalSelect.options).some((option) => option.value !== '');
    }

    function setSaveBusy(busy) {
        saveBusy = busy;
        btnSimpan.disabled = busy;
        btnMuat.disabled = busy || loadBusy;
        tanggalInput.disabled = busy;
        guruSelect.disabled = busy;
        jadwalSelect.disabled = busy || !hasScheduleOptions();
        materiInput.disabled = busy;
        statusButtons.forEach((button) => {
            button.disabled = busy;
        });

        btnSimpan.innerHTML = busy
            ? '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...'
            : normalSaveLabel();
    }

    function setLoadBusy(busy) {
        loadBusy = busy;
        btnMuat.disabled = busy || saveBusy;
        btnMuat.innerHTML = busy
            ? '<span class="spinner-border spinner-border-sm me-1"></span> Memuat...'
            : '<i class="bx bx-search-alt me-1"></i> Muat Jurnal';
    }

    function resetForm() {
        current = null;
        dirty = false;
        card.classList.add('d-none');
        materiInput.value = '';
        setStatus('Hadir', false);
    }

    function setStatus(status, markDirty = true) {
        const previousStatus = selectedStatus;
        selectedStatus = status;

        statusButtons.forEach((button) => {
            const value = button.dataset.status;
            const active = value === status;
            button.className = 'btn jurnal-status sisfour-touch-target';
            button.setAttribute('aria-pressed', active ? 'true' : 'false');

            if (active) {
                if (value === 'Hadir') button.classList.add('btn-success');
                else if (value === 'Izin') button.classList.add('btn-warning');
                else button.classList.add('btn-danger');
            } else {
                if (value === 'Hadir') button.classList.add('btn-outline-success');
                else if (value === 'Izin') button.classList.add('btn-outline-warning');
                else button.classList.add('btn-outline-danger');
            }
        });

        geoNote.textContent = status === 'Hadir'
            ? 'Status Hadir mengikuti validasi geofence server untuk Guru. Admin/Operator tidak dibatasi lokasi.'
            : 'Status Izin/Sakit tidak memerlukan lokasi, tetapi Guru tetap terikat time-window Jadwal.';

        if (markDirty && previousStatus !== status && current?.success) {
            dirty = true;
        }
    }

    function populateJadwal(rows) {
        jadwalSelect.innerHTML = '<option value="">Pilih Jadwal</option>';

        if (!Array.isArray(rows) || rows.length === 0) {
            jadwalSelect.disabled = true;
            showInfo('Guru tersebut tidak memiliki Jadwal Aktif pada tanggal yang dipilih.', 'warning');
            return;
        }

        rows.forEach((row) => {
            const option = document.createElement('option');
            option.value = String(row.id);
            option.textContent = [
                `${row.jam_mulai || ''} - ${row.jam_selesai || ''}`,
                row.nama_kelas || '-',
                row.nama_mapel || '-',
                row.sesi || '-'
            ].join(' | ');
            jadwalSelect.appendChild(option);
        });

        jadwalSelect.disabled = false;
        hideInfo();

        if (isOperationalGuru && rows.length === 1) {
            jadwalSelect.value = String(rows[0].id);
            window.setTimeout(loadJournal, 0);
        }
    }

    async function loadSchedulesForGuru() {
        if (saveBusy) return;

        resetForm();

        const idGuru = Number(guruSelect.value || 0);
        const tanggal = tanggalInput.value;

        jadwalSelect.innerHTML = '<option value="">Pilih Jadwal</option>';
        jadwalSelect.disabled = true;

        if (!idGuru || !tanggal) return;

        showInfo('Memuat Jadwal Guru...', 'info');

        try {
            const params = new URLSearchParams({
                format: 'json',
                tanggal,
                id_guru: String(idGuru)
            });

            const json = await requestJson(
                url(`presensi/mengajar?${params.toString()}`),
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            populateJadwal(json.data?.jadwal || []);
        } catch (error) {
            showInfo(error.message, 'danger');
        }
    }

    function renderResult(result) {
        current = result;

        if (!result || !result.success) {
            card.classList.add('d-none');
            showInfo(result?.message || 'Jurnal tidak dapat dimuat.', 'warning');
            return;
        }

        hideInfo();
        const jadwal = result.jadwal || {};
        const existing = result.existing || null;

        cardTitle.textContent = `${jadwal.nama_kelas || '-'} — ${jadwal.nama_mapel || '-'}`;
        cardMeta.textContent = [
            jadwal.nama_guru || '-',
            `${jadwal.jam_mulai || ''} - ${jadwal.jam_selesai || ''}`,
            jadwal.sesi || '-',
            result.tanggal || ''
        ].join(' · ');

        capabilityBadge.textContent = result.capability || '-';
        revisionBadge.classList.toggle('d-none', !result.submitted);
        materiInput.value = existing?.materi || '';
        setStatus(existing?.status || 'Hadir', false);
        dirty = false;
        btnSimpan.innerHTML = normalSaveLabel();
        card.classList.remove('d-none');
    }

    async function loadJournal() {
        if (loadBusy || saveBusy) return;

        const idJadwal = Number(jadwalSelect.value || 0);
        const tanggal = tanggalInput.value;

        if (!Number(guruSelect.value || 0)) {
            showInfo('Pilih Nama Guru terlebih dahulu.', 'warning');
            return;
        }

        if (!idJadwal || !tanggal) {
            showInfo('Pilih Jadwal Guru terlebih dahulu.', 'warning');
            return;
        }

        setLoadBusy(true);
        card.classList.add('d-none');
        showInfo('Memuat Jurnal...', 'info');

        try {
            const json = await requestJson(
                url(`presensi/mengajar/input/${idJadwal}?tanggal=${encodeURIComponent(tanggal)}&format=json`),
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            renderResult(json.data || {
                success: false,
                message: json.message || 'Gagal memuat Jurnal.'
            });
        } catch (error) {
            showInfo(error.message, 'danger');
        } finally {
            setLoadBusy(false);
        }
    }

    function getLocation() {
        return new Promise((resolve) => {
            if (!navigator.geolocation) {
                resolve({ latitude: null, longitude: null });
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => resolve({
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude
                }),
                () => resolve({ latitude: null, longitude: null }),
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        });
    }

    async function saveJournal() {
        if (saveBusy || loadBusy) return;

        if (!current?.success) {
            showInfo('Muat Jurnal terlebih dahulu.', 'warning');
            return;
        }

        const materi = materiInput.value.trim();
        if (!materi) {
            showInfo('Materi/keterangan wajib diisi.', 'warning');
            materiInput.focus();
            return;
        }

        setSaveBusy(true);

        try {
            let location = { latitude: null, longitude: null };
            if (selectedStatus === 'Hadir' && current.capability !== 'SEMUA') {
                showInfo('Memeriksa lokasi...', 'info');
                location = await getLocation();
            }

            const json = await requestJson(url('presensi/mengajar/save'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    id_jadwal: Number(jadwalSelect.value),
                    tanggal: tanggalInput.value,
                    status: selectedStatus,
                    materi,
                    latitude: location.latitude,
                    longitude: location.longitude
                })
            });

            dirty = false;
            showInfo(json.message || 'Jurnal berhasil disimpan.', 'success');
            setSaveBusy(false);
            window.setTimeout(loadJournal, 250);
            return;
        } catch (error) {
            const message = error.network
                ? `${error.message} Materi dan status Jurnal tetap dipertahankan.`
                : `${error.message} Input Jurnal belum dihapus dan dapat dicoba kembali.`;
            showInfo(message, 'danger');
        } finally {
            if (saveBusy) {
                setSaveBusy(false);
            }
        }
    }

    function autoSelectOperationalGuru() {
        if (!isOperationalGuru || guruSelect.value) {
            return false;
        }

        const options = Array.from(guruSelect.options).filter((option) => option.value !== '');
        if (options.length !== 1) {
            return false;
        }

        guruSelect.value = options[0].value;
        window.SisfourSearchableSelect?.sync(guruSelect);
        loadSchedulesForGuru();
        return true;
    }

    guruSelect?.addEventListener('change', loadSchedulesForGuru);
    btnMuat?.addEventListener('click', loadJournal);
    btnSimpan?.addEventListener('click', saveJournal);

    materiInput?.addEventListener('input', () => {
        if (current?.success) {
            dirty = true;
        }
    });

    statusButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (!saveBusy) {
                setStatus(button.dataset.status || 'Hadir');
            }
        });
    });

    tanggalInput?.addEventListener('change', () => {
        if (dirty && !window.confirm('Perubahan Jurnal belum disimpan. Ganti tanggal dan abaikan perubahan?')) {
            tanggalInput.value = current?.tanggal || app.dataset.tanggal || '';
            return;
        }

        const tanggal = tanggalInput.value;
        if (tanggal) {
            dirty = false;
            window.location.href = url(`presensi/mengajar?tanggal=${encodeURIComponent(tanggal)}`);
        }
    });

    window.addEventListener('beforeunload', (event) => {
        if (!dirty) return;
        event.preventDefault();
        event.returnValue = '';
    });

    const initialGuru = Number(app.dataset.selectedGuru || 0);
    const initialJadwal = Number(app.dataset.selectedJadwal || 0);

    if (initialGuru > 0 && initialJadwal === 0) {
        loadSchedulesForGuru();
    } else if (initialJadwal > 0) {
        loadJournal();
    } else if (!autoSelectOperationalGuru()) {
        setStatus('Hadir', false);
    }
})();
