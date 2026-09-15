(() => {
    'use strict';

    const app = document.getElementById('masterSiswaApp');
    const form = document.getElementById('formFilterSiswa');
    const tahun = document.getElementById('filterTahun');
    const kelas = document.getElementById('filterKelas');
    const status = document.getElementById('filterStatus');

    if (!app || !form || !tahun || !kelas || !status) {
        return;
    }

    const baseUrl = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
    const activeYearId = String(app.dataset.activeYearId || '');
    const sourceOptions = Array.from(
        kelas.querySelectorAll('option[data-tahun]')
    ).map((option) => ({
        value: option.value,
        label: option.textContent || '',
        tahun: String(option.dataset.tahun || ''),
    }));

    const endpoint = (path) =>
        `${baseUrl}/${String(path).replace(/^\/+/, '')}`;

    const replaceKelasOptions = (items, preferredValue = '') => {
        const current = String(preferredValue || '');
        kelas.innerHTML = '<option value="">Semua</option>';

        items.forEach((item) => {
            const option = document.createElement('option');
            option.value = String(item.value ?? item.id ?? '');
            option.textContent = String(item.label ?? item.nama_kelas ?? '');
            kelas.appendChild(option);
        });

        if (
            current
            && Array.from(kelas.options).some(
                (option) => option.value === current
            )
        ) {
            kelas.value = current;
        } else {
            kelas.value = '';
        }

        window.SisfourSearchableSelect?.sync(kelas);
    };

    const fallbackItems = (idTahun) => sourceOptions
        .filter((item) => item.tahun === String(idTahun || ''));

    const renderFallback = (idTahun, preferredValue = '') => {
        replaceKelasOptions(fallbackItems(idTahun), preferredValue);
    };

    const loadKelas = async (idTahun, preferredValue = '') => {
        const yearId = String(idTahun || '');
        const fallback = fallbackItems(yearId);

        renderFallback(yearId, preferredValue);

        if (!yearId) {
            return;
        }

        try {
            const params = new URLSearchParams({
                id_tahun: yearId,
                format: 'json',
            });

            const response = await fetch(
                endpoint(`master/kelas/json?${params.toString()}`),
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }
            );

            const payload = await response.json().catch(() => ({}));

            if (!response.ok || payload.status !== 'success') {
                return;
            }

            const rows = Array.isArray(payload.data) ? payload.data : [];

            // Jika endpoint kelas dibatasi oleh scope role, pertahankan fallback
            // Master Siswa yang sudah disaring oleh permission-nya sendiri.
            if (!rows.length && fallback.length) {
                return;
            }

            replaceKelasOptions(
                rows.map((row) => ({
                    value: row.id,
                    label: row.nama_kelas,
                })),
                preferredValue
            );
        } catch (error) {
            // Fallback server-rendered tetap dipakai bila refresh live gagal.
            console.warn('Refresh kelas Master Siswa gagal.', error);
        }
    };

    const params = new URLSearchParams(window.location.search);
    const requestedYear = params.get('id_tahun');
    const requestedClass = params.get('id_kelas');
    const requestedStatus = params.get('status_aktif');

    const initialYear = requestedYear || activeYearId;

    if (
        initialYear
        && Array.from(tahun.options).some(
            (option) => option.value === initialYear
        )
    ) {
        tahun.value = initialYear;
    }

    status.value = requestedStatus !== null
        ? requestedStatus
        : 'Aktif';

    loadKelas(tahun.value, requestedClass || '');

    tahun.addEventListener('change', () => {
        loadKelas(tahun.value, '');
    });

    form.addEventListener('reset', () => {
        window.setTimeout(() => {
            if (
                activeYearId
                && Array.from(tahun.options).some(
                    (option) => option.value === activeYearId
                )
            ) {
                tahun.value = activeYearId;
            }

            status.value = 'Aktif';
            loadKelas(tahun.value, '');
        }, 0);
    });
})();
