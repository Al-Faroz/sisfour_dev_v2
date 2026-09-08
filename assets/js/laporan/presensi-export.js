(() => {
    'use strict';

    const app = document.getElementById('laporanExportApp');
    if (!app) return;

    const baseUrl = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
    const tahun = document.getElementById('exportTahun');
    const kelas = document.getElementById('exportKelas');
    const bulan = document.getElementById('exportBulan');
    const btnBulanan = document.getElementById('btnExportBulanan');
    const btnSemester = document.getElementById('btnExportSemester');

    const endpoint = (path) => `${baseUrl}/${path.replace(/^\/+/, '')}`;

    async function refreshKelas() {
        const params = new URLSearchParams({
            format: 'json',
            id_tahun: tahun.value
        });

        try {
            const res = await fetch(endpoint(`laporan/presensi/matrix?${params.toString()}`), {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json();
            const data = json.data || {};

            if (!res.ok || json.status !== 'success') return;

            kelas.innerHTML = '<option value="">Pilih Kelas</option>';
            (data.kelas || []).forEach((item) => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.nama_kelas;
                kelas.appendChild(option);
            });
        } catch (_) {
            // Server tetap akan memvalidasi scope saat export.
        }
    }

    function validate() {
        if (!tahun.value || !kelas.value) {
            window.alert('Tahun Ajaran dan Kelas wajib dipilih.');
            return false;
        }
        return true;
    }

    btnBulanan?.addEventListener('click', () => {
        if (!validate() || !bulan.value) {
            if (!bulan.value) window.alert('Bulan wajib dipilih.');
            return;
        }

        const params = new URLSearchParams({
            id_tahun: tahun.value,
            id_kelas: kelas.value,
            bulan: bulan.value
        });

        window.location.href = endpoint(`laporan/presensi/export/bulan?${params.toString()}`);
    });

    btnSemester?.addEventListener('click', () => {
        if (!validate()) return;

        const params = new URLSearchParams({
            id_tahun: tahun.value,
            id_kelas: kelas.value
        });

        window.location.href = endpoint(`laporan/presensi/export/semester?${params.toString()}`);
    });

    tahun?.addEventListener('change', refreshKelas);
})();
