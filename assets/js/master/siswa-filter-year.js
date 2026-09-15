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

    const activeYearId = String(app.dataset.activeYearId || '');
    const sourceOptions = Array.from(
        kelas.querySelectorAll('option[data-tahun]')
    ).map((option) => ({
        value: option.value,
        label: option.textContent || '',
        tahun: String(option.dataset.tahun || ''),
    }));

    const renderKelas = (preferredValue = '') => {
        const selectedYear = String(tahun.value || '');
        const current = String(preferredValue || '');

        kelas.innerHTML = '<option value="">Semua</option>';

        sourceOptions
            .filter((item) => item.tahun === selectedYear)
            .forEach((item) => {
                const option = document.createElement('option');
                option.value = item.value;
                option.textContent = item.label;
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

    if (requestedStatus !== null) {
        status.value = requestedStatus;
    } else {
        status.value = 'Aktif';
    }

    renderKelas(requestedClass || '');

    tahun.addEventListener('change', () => {
        renderKelas('');
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
            renderKelas('');
        }, 0);
    });
})();
