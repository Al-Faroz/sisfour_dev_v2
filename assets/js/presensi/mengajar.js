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
    const catatanInput = document.getElementById('jurnalCatatan');
    const btnSimpan = document.getElementById('btnSimpanJurnal');
    const geoNote = document.getElementById('jurnalGeoNote');
    const statusButtons = Array.from(document.querySelectorAll('.jurnal-status'));
    const studentSection = document.getElementById('jurnalStudentSection');
    const studentSearch = document.getElementById('jurnalStudentSearch');
    const studentSuggestions = document.getElementById('jurnalStudentSuggestions');
    const studentSelected = document.getElementById('jurnalStudentSelected');
    const studentEmpty = document.getElementById('jurnalStudentEmpty');
    const sakitCount = document.getElementById('jurnalSakitCount');
    const izinCount = document.getElementById('jurnalIzinCount');
    const alphaCount = document.getElementById('jurnalAlphaCount');
    const isOperationalGuru = document.body.classList.contains('sisfour-role-guru');

    let current = null;
    let selectedStatus = 'Hadir';
    let saveBusy = false;
    let loadBusy = false;
    let dirty = false;
    let roster = [];
    const selectedStudents = new Map();

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

    function closeStudentSuggestions() {
        studentSuggestions.classList.add('d-none');
        studentSuggestions.innerHTML = '';
    }

    function updateStudentSummary() {
        const summary = { Sakit: 0, Izin: 0, Alpha: 0 };

        selectedStudents.forEach((student) => {
            if (Object.hasOwn(summary, student.status)) {
                summary[student.status]++;
            }
        });

        sakitCount.textContent = String(summary.Sakit);
        izinCount.textContent = String(summary.Izin);
        alphaCount.textContent = String(summary.Alpha);
    }

    function setStudentSectionState() {
        const enabled = selectedStatus === 'Hadir' && !saveBusy;
        studentSection.classList.toggle('is-disabled', !enabled);
        studentSearch.disabled = !enabled;

        studentSelected.querySelectorAll('button').forEach((button) => {
            button.disabled = !enabled;
        });

        if (!enabled) {
            closeStudentSuggestions();
        }
    }

    function setSaveBusy(busy) {
        saveBusy = busy;
        btnSimpan.disabled = busy;
        btnMuat.disabled = busy || loadBusy;
        tanggalInput.disabled = busy;
        guruSelect.disabled = busy;
        jadwalSelect.disabled = busy || !hasScheduleOptions();
        materiInput.disabled = busy;
        catatanInput.disabled = busy;
        statusButtons.forEach((button) => {
            button.disabled = busy;
        });
        setStudentSectionState();

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

    function resetStudents() {
        roster = [];
        selectedStudents.clear();
        studentSearch.value = '';
        closeStudentSuggestions();
        renderSelectedStudents();
    }

    function resetForm() {
        current = null;
        dirty = false;
        card.classList.add('d-none');
        materiInput.value = '';
        catatanInput.value = '';
        resetStudents();
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
            : 'Status Izin/Sakit tidak memerlukan lokasi. Daftar siswa S/I/A tidak digunakan ketika Guru tidak hadir.';

        setStudentSectionState();

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

    function renderStudentSuggestions() {
        if (selectedStatus !== 'Hadir') {
            closeStudentSuggestions();
            return;
        }

        const query = studentSearch.value.trim().toLowerCase();
        if (!query) {
            closeStudentSuggestions();
            return;
        }

        const matches = roster
            .filter((student) => !selectedStudents.has(Number(student.id)))
            .filter((student) => {
                const nama = String(student.nama || '').toLowerCase();
                const nisn = String(student.nisn || '').toLowerCase();
                return nama.includes(query) || nisn.includes(query);
            })
            .slice(0, 8);

        if (!matches.length) {
            studentSuggestions.innerHTML = '<div class="p-3 text-muted small">Siswa tidak ditemukan pada roster kelas Jurnal.</div>';
            studentSuggestions.classList.remove('d-none');
            return;
        }

        studentSuggestions.innerHTML = matches.map((student) => `
            <button type="button" class="jurnal-student-suggestion" data-student-id="${Number(student.id)}">
                <div class="fw-semibold">${escapeHtml(student.nama || '-')}</div>
                <small class="text-muted">${student.nisn ? `NISN ${escapeHtml(student.nisn)}` : 'NISN tidak tersedia'}</small>
            </button>
        `).join('');
        studentSuggestions.classList.remove('d-none');
    }

    function addStudent(idStudent) {
        const id = Number(idStudent || 0);
        if (!id || selectedStudents.has(id)) return;

        const student = roster.find((item) => Number(item.id) === id);
        if (!student) return;

        selectedStudents.set(id, {
            id_siswa: id,
            nama: student.nama || '-',
            nisn: student.nisn || '',
            status: '',
        });

        studentSearch.value = '';
        closeStudentSuggestions();
        dirty = true;
        renderSelectedStudents();
    }

    function statusButton(id, status, currentStatus, css, label) {
        const active = currentStatus === status;
        return `
            <button
                type="button"
                class="btn jurnal-student-status sisfour-touch-target ${active ? `btn-${css}` : `btn-outline-${css}`}"
                data-action="status"
                data-student-id="${id}"
                data-status="${status}"
                aria-pressed="${active ? 'true' : 'false'}"
                aria-label="${status} untuk siswa"
                title="${status}"
            >${label}</button>
        `;
    }

    function renderSelectedStudents() {
        const rows = Array.from(selectedStudents.values());
        studentEmpty.classList.toggle('d-none', rows.length > 0);

        studentSelected.innerHTML = rows.map((student) => {
            const id = Number(student.id_siswa);
            const incomplete = !student.status;

            return `
                <div class="jurnal-student-row" data-student-row="${id}">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <div class="min-w-0">
                            <div class="fw-semibold text-break">${escapeHtml(student.nama || '-')}</div>
                            <small class="text-muted">${student.nisn ? `NISN ${escapeHtml(student.nisn)}` : 'NISN tidak tersedia'}</small>
                        </div>
                        <button
                            type="button"
                            class="btn btn-sm btn-icon btn-outline-secondary sisfour-touch-target"
                            data-action="remove"
                            data-student-id="${id}"
                            aria-label="Hapus ${escapeHtml(student.nama || 'siswa')} dari Jurnal"
                            title="Hapus"
                        ><i class="bx bx-x"></i></button>
                    </div>
                    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2">
                        <div class="jurnal-student-status-grid" role="group" aria-label="Status ${escapeHtml(student.nama || 'siswa')}">
                            ${statusButton(id, 'Sakit', student.status, 'warning', 'S')}
                            ${statusButton(id, 'Izin', student.status, 'info', 'I')}
                            ${statusButton(id, 'Alpha', student.status, 'danger', 'A')}
                        </div>
                        <small class="${incomplete ? 'text-danger' : 'text-muted'}">
                            ${incomplete ? 'Pilih status S/I/A' : escapeHtml(student.status)}
                        </small>
                    </div>
                </div>
            `;
        }).join('');

        updateStudentSummary();
        setStudentSectionState();
    }

    function hydrateStudents(result) {
        roster = Array.isArray(result.siswa_options) ? result.siswa_options : [];
        selectedStudents.clear();

        const existingRows = Array.isArray(result.siswa_exceptions)
            ? result.siswa_exceptions
            : [];

        existingRows.forEach((row) => {
            const id = Number(row.id_siswa || 0);
            if (!id) return;

            const rosterStudent = roster.find((student) => Number(student.id) === id);
            selectedStudents.set(id, {
                id_siswa: id,
                nama: row.nama_siswa_snapshot || rosterStudent?.nama || '-',
                nisn: row.nisn_snapshot || rosterStudent?.nisn || '',
                status: row.status || '',
            });
        });

        studentSearch.value = '';
        closeStudentSuggestions();
        renderSelectedStudents();
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
        catatanInput.value = existing?.catatan || '';
        setStatus(existing?.status || 'Hadir', false);
        hydrateStudents(result);
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

        const incompleteStudent = Array.from(selectedStudents.values())
            .find((student) => !student.status);

        if (incompleteStudent) {
            showInfo(`Pilih status S/I/A untuk ${incompleteStudent.nama}.`, 'warning');
            studentSelected.querySelector(`[data-student-row="${Number(incompleteStudent.id_siswa)}"]`)?.scrollIntoView({
                block: 'center',
                behavior: 'smooth'
            });
            return;
        }

        if (selectedStatus !== 'Hadir' && selectedStudents.size > 0) {
            showInfo('Daftar siswa S/I/A hanya dapat disimpan ketika status Guru Hadir.', 'warning');
            return;
        }

        const wasSubmitted = Boolean(current.submitted);
        const currentCapability = current.capability || '';
        setSaveBusy(true);

        try {
            let location = { latitude: null, longitude: null };
            if (selectedStatus === 'Hadir' && currentCapability !== 'SEMUA') {
                showInfo('Memeriksa lokasi...', 'info');
                location = await getLocation();
            }

            const siswa = Array.from(selectedStudents.values()).map((student) => ({
                id_siswa: Number(student.id_siswa),
                status: student.status,
            }));

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
                    catatan: catatanInput.value.trim(),
                    siswa,
                    latitude: location.latitude,
                    longitude: location.longitude
                })
            });

            dirty = false;
            const total = Number(json.data?.siswa_exception_total || siswa.length || 0);
            showInfo(
                `${json.message || 'Jurnal berhasil disimpan.'} ${total} siswa S/I/A tercatat pada Jurnal.`,
                'success'
            );
            setSaveBusy(false);

            if (currentCapability !== 'SEMUA' && !wasSubmitted) {
                card.classList.add('d-none');
                return;
            }

            window.setTimeout(loadJournal, 250);
            return;
        } catch (error) {
            const message = error.network
                ? `${error.message} Materi, catatan, dan daftar siswa tetap dipertahankan.`
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

    [materiInput, catatanInput].forEach((input) => {
        input?.addEventListener('input', () => {
            if (current?.success) {
                dirty = true;
            }
        });
    });

    statusButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (saveBusy) return;

            const nextStatus = button.dataset.status || 'Hadir';
            if (
                nextStatus !== 'Hadir'
                && selectedStatus === 'Hadir'
                && selectedStudents.size > 0
            ) {
                const confirmed = window.confirm(
                    `Status Guru akan diubah menjadi ${nextStatus}. Daftar siswa S/I/A akan dikosongkan. Lanjutkan?`
                );

                if (!confirmed) return;
                selectedStudents.clear();
                renderSelectedStudents();
            }

            setStatus(nextStatus);
        });
    });

    studentSearch?.addEventListener('input', renderStudentSuggestions);
    studentSearch?.addEventListener('focus', renderStudentSuggestions);

    studentSuggestions?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-student-id]');
        if (!button) return;
        addStudent(button.dataset.studentId);
    });

    studentSelected?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action][data-student-id]');
        if (!button || selectedStatus !== 'Hadir' || saveBusy) return;

        const id = Number(button.dataset.studentId || 0);
        const action = button.dataset.action;
        const student = selectedStudents.get(id);
        if (!student) return;

        if (action === 'remove') {
            selectedStudents.delete(id);
        } else if (action === 'status') {
            student.status = button.dataset.status || '';
            selectedStudents.set(id, student);
        }

        dirty = true;
        renderSelectedStudents();
    });

    document.addEventListener('click', (event) => {
        if (!studentSection.contains(event.target)) {
            closeStudentSuggestions();
        }
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
        renderSelectedStudents();
    }
})();
