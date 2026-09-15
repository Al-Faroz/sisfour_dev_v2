(() => {
    'use strict';

    const isYearSelect = (select) => {
        if (!(select instanceof HTMLSelectElement)) return false;

        const name = String(select.name || '').toLowerCase();
        const id = String(select.id || '').toLowerCase();

        return name === 'id_tahun' || id.includes('tahun');
    };

    const findActiveOption = (select) => Array.from(select.options).find(
        (option) => /\(aktif\)/i.test(String(option.textContent || ''))
    );

    const applyDefault = (select) => {
        if (!isYearSelect(select)) return;

        const activeOption = findActiveOption(select);
        if (!activeOption) return;

        // Hormati pilihan eksplisit dari server/URL/user. Hanya isi ketika
        // selector masih kosong / placeholder.
        if (String(select.value || '').trim() === '') {
            select.value = activeOption.value;
        }

        // Jadikan periode aktif sebagai reset-default untuk form HTML.
        Array.from(select.options).forEach((option) => {
            option.defaultSelected = option === activeOption;
        });

        window.SisfourSearchableSelect?.sync(select);
    };

    const apply = (root = document) => {
        root.querySelectorAll('select').forEach(applyDefault);
    };

    // Script dimuat setelah HTML selesai diparse dan sebelum JS page-specific,
    // sehingga request pertama dari halaman sudah membawa Tahun Ajaran aktif.
    apply();

    document.addEventListener('DOMContentLoaded', () => apply());

    window.SisfourActiveYearDefault = { apply, applyDefault };
})();
