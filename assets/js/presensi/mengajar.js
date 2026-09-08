(() => {
    'use strict';

    const app = document.getElementById('presensiMengajarApp');

    if (!app) {
        return;
    }

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

    let current = null;
    let selectedStatus = 'Hadir';

    function url(path) {
        return `${baseUrl}/${String(path).replace(/^\/+/, '')}`;
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
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

    function resetForm() {
        current = null;
        card.classList.add('d-none');
        materiInput.value = '';
        setStatus('Hadir');
    }

    function setStatus(status) {
        selectedStatus = status;

        statusButtons.forEach((button) => {
            const value = button.dataset.status;
            const active = value === status;

            button.className = 'btn jurnal-status';

            if (active) {
                if (value === 'Hadir') {
                    button.classList.add('btn-success');
                } else if (value === 'Izin') {
                    button.classList.add('btn-warning');
                } else {
                    button.classList.add('btn-danger');
                }
            } else {
                if (value === 'Hadir') {
                    button.classList.add('btn-outline-success');
                } else if (value === 'Izin') {
                    button.classList.add('btn-outline-warning');
                } else {
                    button.classList.add('btn-outline-danger');
                }
            }
        });

        if (status === 'Hadir') {
            geoNote.textContent = 'Status Hadir memerlukan geofence untuk Guru. Admin/Operator tidak dibatasi lokasi.';
        } else {
            geoNote.textContent = 'Status Izin/Sakit tidak memerlukan geofence, tetapi Guru tetap terikat time-window Jadwal.';
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
    }

    async function loadSchedulesForGuru() {
        resetForm();

        const idGuru = Number(guruSelect.value || 0);
        const tanggal = tanggalInput.value;

        jadwalSelect.innerHTML = '<option value="">Pilih Jadwal</option>';
        jadwalSelect.disabled = true;

        if (!idGuru || !tanggal) {
            return;
        }

        showInfo('Memuat Jadwal Guru...', 'info');

        try {
            const params = new URLSearchParams({
                format: 'json',
                tanggal,
                id_guru: String(idGuru)
            });

            const response = await fetch(
                url(`presensi/mengajar?${params.toString()}`),
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            const json = await response.json();

            if (!response.ok || json.status !== 'success') {
                showInfo(json.message || 'Gagal memuat Jadwal Guru.', 'danger');
                return;
            }

            populateJadwal(json.data?.jadwal || []);
        } catch (error) {
            showInfo('Terjadi kesalahan saat memuat Jadwal Guru.', 'danger');
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
        ].join(' | ');

        capabilityBadge.textContent = result.capability || '-';
        revisionBadge.classList.toggle('d-none', !result.submitted);

        materiInput.value = existing?.materi || '';
        setStatus(existing?.status || 'Hadir');

        btnSimpan.innerHTML = result.submitted
            ? '<i class="bx bx-save me-1"></i> Simpan Revisi'
            : '<i class="bx bx-save me-1"></i> Simpan Jurnal';

        card.classList.remove('d-none');
    }

    async function loadJournal() {
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

        btnMuat.disabled = true;
        card.classList.add('d-none');
        showInfo('Memuat Jurnal...', 'info');

        try {
            const response = await fetch(
                url(`presensi/mengajar/input/${idJadwal}?tanggal=${encodeURIComponent(tanggal)}&format=json`),
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            const json = await response.json();

            renderResult(json.data || {
                success: false,
                message: json.message || 'Gagal memuat Jurnal.'
            });
        } catch (error) {
            showInfo('Terjadi kesalahan saat memuat Jurnal.', 'danger');
        } finally {
            btnMuat.disabled = false;
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

        btnSimpan.disabled = true;

        try {
            let location = { latitude: null, longitude: null };

            if (selectedStatus === 'Hadir' && current.capability !== 'SEMUA') {
                showInfo('Memeriksa lokasi...', 'info');
                location = await getLocation();
            }

            const response = await fetch(url('presensi/mengajar/save'), {
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

            const json = await response.json();

            if (!response.ok || json.status !== 'success') {
                showInfo(json.message || 'Jurnal gagal disimpan.', 'danger');
                return;
            }

            showInfo(json.message || 'Jurnal berhasil disimpan.', 'success');

            setTimeout(() => {
                loadJournal();
            }, 350);
        } catch (error) {
            showInfo('Terjadi kesalahan saat menyimpan Jurnal.', 'danger');
        } finally {
            btnSimpan.disabled = false;
        }
    }

    guruSelect?.addEventListener('change', loadSchedulesForGuru);
    btnMuat?.addEventListener('click', loadJournal);
    btnSimpan?.addEventListener('click', saveJournal);

    statusButtons.forEach((button) => {
        button.addEventListener('click', () => {
            setStatus(button.dataset.status || 'Hadir');
        });
    });

    tanggalInput?.addEventListener('change', () => {
        const tanggal = tanggalInput.value;

        if (tanggal) {
            window.location.href = url(`presensi/mengajar?tanggal=${encodeURIComponent(tanggal)}`);
        }
    });

    const initialGuru = Number(app.dataset.selectedGuru || 0);
    const initialJadwal = Number(app.dataset.selectedJadwal || 0);

    if (initialGuru > 0 && initialJadwal === 0) {
        loadSchedulesForGuru();
    } else if (initialJadwal > 0) {
        loadJournal();
    } else {
        setStatus('Hadir');
    }
})();
