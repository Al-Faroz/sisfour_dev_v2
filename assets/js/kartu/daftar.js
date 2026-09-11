(() => {
    'use strict';

    const app = document.getElementById('kartuApp');
    if (!app) return;

    const base = String(
        app.dataset.baseUrl || ''
    ).replace(/\/+$/, '');

    const maxPrint = Number(
        app.dataset.maxPrint || 200
    );

    const canManage =
        app.dataset.canManage === '1';

    const body =
        document.getElementById('kartuBody');

    const checkAll =
        document.getElementById('checkAllKartu');

    if (!body) {
        return;
    }

    const state = {
        limit: 25,
        offset: 0,
        total: 0,
    };

    const printButtonIds = [
        'btnCetakDepanSelected',
        'btnCetakBelakangSelected',
        'btnCetakDepanKelas',
        'btnCetakBelakangKelas',
    ];

    const pager = window.SisfourPagination?.mount(
        body,
        {
            id: 'kartuDaftarPager',
            label: 'kartu',
            onChange: (next) => {
                state.limit = next.limit;
                state.offset = next.offset;

                if (checkAll) {
                    checkAll.checked = false;
                }

                load();
            },
        }
    );

    const esc = (value) => {
        const div =
            document.createElement('div');

        div.textContent = value ?? '';

        return div.innerHTML;
    };

    const showAlert = (
        message,
        type = 'info'
    ) => {
        const box =
            document.getElementById('kartuAlert');

        if (!box) {
            alert(message);
            return;
        }

        box.className =
            `alert alert-${type}`;

        box.textContent = message;
    };

    const hideAlert = () => {
        const box =
            document.getElementById('kartuAlert');

        if (box) {
            box.className = 'alert d-none';
            box.textContent = '';
        }
    };

    const params = (withPaging = true) => {
        const query =
            new URLSearchParams();

        if (withPaging) {
            query.set('format', 'json');
            query.set(
                'limit',
                String(state.limit)
            );
            query.set(
                'offset',
                String(state.offset)
            );
        }

        const search =
            document.getElementById(
                'kartuSearch'
            )?.value.trim();

        const status =
            document.getElementById(
                'kartuStatus'
            )?.value;

        const kelas =
            document.getElementById(
                'kartuKelas'
            )?.value;

        if (search) {
            query.set('search', search);
        }

        if (status) {
            query.set('status', status);
        }

        if (kelas) {
            query.set('id_kelas', kelas);
        }

        return query;
    };

    const syncUrl = () => {
        const query = params(true);

        query.delete('format');

        const encoded =
            query.toString();

        window.history.replaceState(
            null,
            '',
            `${window.location.pathname}${encoded ? `?${encoded}` : ''}`
        );
    };

    const restoreState = () => {
        const query =
            new URLSearchParams(
                window.location.search
            );

        const limit = Number(
            query.get('limit') || 25
        );

        const offset = Number(
            query.get('offset') || 0
        );

        state.limit =
            [25, 50, 100].includes(limit)
                ? limit
                : 25;

        state.offset =
            Number.isFinite(offset)
            && offset >= 0
                ? offset
                : 0;

        const mapping = {
            search: 'kartuSearch',
            status: 'kartuStatus',
            id_kelas: 'kartuKelas',
        };

        Object.entries(mapping)
            .forEach(([key, id]) => {
                const value =
                    query.get(key);

                const element =
                    document.getElementById(id);

                if (
                    value !== null
                    && element
                ) {
                    element.value = value;
                }
            });
    };

    async function parseJson(response) {
        const data =
            await response.json()
                .catch(() => ({}));

        if (
            !response.ok
            || data.status !== 'success'
        ) {
            throw new Error(
                data.message
                || 'Permintaan gagal diproses.'
            );
        }

        return data;
    }

    const bindReissue = () => {
        document
            .querySelectorAll('.btn-reissue')
            .forEach((button) => {
                button.addEventListener(
                    'click',
                    async () => {
                        button.disabled = true;

                        try {
                            const response =
                                await fetch(
                                    `${base}/kartu/reissue/${button.dataset.id}`,
                                    {
                                        method: 'POST',
                                        headers: {
                                            Accept:
                                                'application/json',
                                            'X-Requested-With':
                                                'XMLHttpRequest',
                                        },
                                        credentials:
                                            'same-origin',
                                    }
                                );

                            const json =
                                await parseJson(
                                    response
                                );

                            showAlert(
                                json.message
                                || 'Reissue selesai.',
                                'success'
                            );

                            await load();
                        } catch (error) {
                            showAlert(
                                error.message,
                                'danger'
                            );
                        } finally {
                            button.disabled = false;
                        }
                    }
                );
            });
    };

    const renderRows = (
        rows,
        serverCanManage
    ) => {
        body.innerHTML =
            rows.map((card) => `
                <tr>
                    ${
                        canManage
                            ? `
                                <td>
                                    <input
                                        class="form-check-input check-kartu"
                                        type="checkbox"
                                        value="${Number(card.id)}"
                                        ${card.status_aktif === 'Aktif' ? '' : 'disabled'}
                                    >
                                </td>
                            `
                            : ''
                    }
                    <td>
                        <div class="font-monospace small">
                            ${esc(card.nisn)}
                        </div>
                        <strong>${esc(card.nama)}</strong>
                    </td>
                    <td>${esc(card.nama_kelas || '-')}</td>
                    <td>${esc(card.nomor_kartu)}</td>
                    <td>${esc(card.tanggal_terbit)}</td>
                    <td>
                        <span class="badge ${
                            card.status_aktif === 'Aktif'
                                ? 'bg-label-success'
                                : 'bg-label-secondary'
                        }">
                            ${esc(card.status_aktif)}
                        </span>
                    </td>
                    <td class="text-end text-nowrap">
                        <a
                            class="btn btn-sm btn-outline-primary"
                            href="${base}/kartu/preview/${Number(card.id)}"
                        >
                            Preview
                        </a>
                        <a
                            class="btn btn-sm btn-outline-secondary"
                            href="${base}/kartu/download/${Number(card.id)}"
                        >
                            PDF
                        </a>
                        ${
                            serverCanManage
                                ? `
                                    <button
                                        class="btn btn-sm btn-outline-warning btn-reissue"
                                        data-id="${Number(card.id)}"
                                        type="button"
                                    >
                                        Reissue
                                    </button>
                                `
                                : ''
                        }
                    </td>
                </tr>
            `).join('')
            || `
                <tr>
                    <td
                        colspan="${canManage ? 7 : 6}"
                        class="text-center text-muted py-4"
                    >
                        Belum ada kartu.
                    </td>
                </tr>
            `;

        bindReissue();
    };

    async function load() {
        hideAlert();
        pager?.setDisabled(true);

        body.innerHTML = `
            <tr>
                <td
                    colspan="${canManage ? 7 : 6}"
                    class="text-center py-4"
                >
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Memuat kartu...
                </td>
            </tr>
        `;

        try {
            const response = await fetch(
                `${base}/kartu/daftar?${params(true)}`,
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With':
                            'XMLHttpRequest',
                    },
                    credentials:
                        'same-origin',
                }
            );

            const json =
                await parseJson(response);

            const data = json.data || {};
            const rows =
                Array.isArray(data.rows)
                    ? data.rows
                    : [];

            state.total =
                Number(data.total || 0);

            state.limit =
                Number(
                    data.limit
                    || state.limit
                );

            state.offset =
                Number(
                    data.offset
                    ?? state.offset
                );

            if (
                rows.length === 0
                && state.total > 0
                && state.offset >= state.total
            ) {
                state.offset =
                    Math.floor(
                        (state.total - 1)
                        / state.limit
                    ) * state.limit;

                await load();
                return;
            }

            renderRows(
                rows,
                Boolean(data.can_manage)
            );

            pager?.render(state);
            syncUrl();

            if (checkAll) {
                checkAll.checked = false;
            }
        } catch (error) {
            showAlert(
                error.message,
                'danger'
            );
        } finally {
            pager?.setDisabled(false);
        }
    }

    document
        .getElementById('btnKartuCari')
        ?.addEventListener(
            'click',
            () => {
                state.offset = 0;
                load();
            }
        );

    document
        .getElementById('kartuSearch')
        ?.addEventListener(
            'keydown',
            (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    state.offset = 0;
                    load();
                }
            }
        );

    document
        .getElementById('formGenerateKartu')
        ?.addEventListener(
            'submit',
            async (event) => {
                event.preventDefault();

                const button =
                    event.target.querySelector(
                        'button[type="submit"]'
                    );

                if (button) {
                    button.disabled = true;
                }

                try {
                    const response = await fetch(
                        `${base}/kartu/generate`,
                        {
                            method: 'POST',
                            body: new FormData(
                                event.target
                            ),
                            headers: {
                                Accept:
                                    'application/json',
                                'X-Requested-With':
                                    'XMLHttpRequest',
                            },
                            credentials:
                                'same-origin',
                        }
                    );

                    const json =
                        await parseJson(
                            response
                        );

                    showAlert(
                        json.message
                        || 'Generate selesai.',
                        'success'
                    );

                    window.location.reload();
                } catch (error) {
                    showAlert(
                        error.message,
                        'danger'
                    );
                } finally {
                    if (button) {
                        button.disabled = false;
                    }
                }
            }
        );

    document
        .getElementById('btnGenerateSemua')
        ?.addEventListener(
            'click',
            async (event) => {
                const button =
                    event.currentTarget;

                const initialCount = Number(
                    document.getElementById(
                        'eligibleCount'
                    )?.textContent
                    || 0
                );

                if (initialCount <= 0) {
                    showAlert(
                        'Tidak ada kartu baru yang perlu digenerate.',
                        'info'
                    );
                    return;
                }

                const ok = window.confirm(
                    `Generate ${initialCount} kartu yang belum terbit? `
                    + 'Proses dilakukan bertahap maksimal 200 siswa per request.'
                );

                if (!ok) return;

                button.disabled = true;

                let totalGenerated = 0;
                let totalExisting = 0;
                let remaining = initialCount;
                let batchNo = 0;

                try {
                    while (remaining > 0) {
                        batchNo += 1;

                        const processed =
                            initialCount
                            - remaining;

                        button.textContent =
                            `Memproses ${processed}/${initialCount}...`;

                        showAlert(
                            `Bulk generate batch ${batchNo}. `
                            + `${processed} dari ${initialCount} selesai.`,
                            'info'
                        );

                        const formData =
                            new FormData();

                        const selectedClass =
                            document.getElementById(
                                'kartuKelas'
                            )?.value;

                        if (selectedClass) {
                            formData.append(
                                'id_kelas',
                                selectedClass
                            );
                        }

                        const response =
                            await fetch(
                                `${base}/kartu/generate-bulk`,
                                {
                                    method: 'POST',
                                    body: formData,
                                    headers: {
                                        Accept:
                                            'application/json',
                                        'X-Requested-With':
                                            'XMLHttpRequest',
                                    },
                                    credentials:
                                        'same-origin',
                                }
                            );

                        const json =
                            await parseJson(
                                response
                            );

                        const data =
                            json.data || {};

                        totalGenerated += Number(
                            data.generated_count
                            || 0
                        );

                        totalExisting += Number(
                            data.existing_count
                            || 0
                        );

                        const nextRemaining =
                            Number(
                                data.remaining_count
                                || 0
                            );

                        if (
                            nextRemaining
                                >= remaining
                            && !data.done
                        ) {
                            throw new Error(
                                'Bulk generate tidak mengalami kemajuan. '
                                + 'Proses dihentikan untuk mencegah loop.'
                            );
                        }

                        remaining =
                            nextRemaining;

                        const doneCount =
                            initialCount
                            - remaining;

                        const eligibleCount =
                            document.getElementById(
                                'eligibleCount'
                            );

                        if (eligibleCount) {
                            eligibleCount.textContent =
                                String(remaining);
                        }

                        button.textContent =
                            remaining > 0
                                ? `Memproses ${doneCount}/${initialCount}...`
                                : 'Selesai';

                        if (data.done) {
                            break;
                        }
                    }

                    showAlert(
                        `Bulk generate selesai. `
                        + `${totalGenerated} kartu baru diterbitkan`
                        + (
                            totalExisting > 0
                                ? `, ${totalExisting} existing dilewati.`
                                : '.'
                        ),
                        'success'
                    );

                    window.setTimeout(
                        () => window.location.reload(),
                        1000
                    );
                } catch (error) {
                    showAlert(
                        error.message,
                        'danger'
                    );

                    button.disabled = false;
                    button.textContent =
                        'Generate Semua Belum Terbit';
                }
            }
        );

    checkAll?.addEventListener(
        'change',
        () => {
            document
                .querySelectorAll(
                    '.check-kartu:not(:disabled)'
                )
                .forEach((checkbox) => {
                    checkbox.checked =
                        checkAll.checked;
                });
        }
    );

    const selectedIds = () =>
        Array.from(
            document.querySelectorAll(
                '.check-kartu:checked'
            )
        ).map(
            (checkbox) =>
                Number(checkbox.value)
        );

    const setPrintButtonsDisabled = (disabled) => {
        printButtonIds.forEach((id) => {
            const button =
                document.getElementById(id);

            if (button) {
                button.disabled = disabled;
            }
        });
    };

    const filenameFromResponse = (
        response,
        side
    ) => {
        const disposition =
            response.headers.get(
                'Content-Disposition'
            ) || '';

        const utf8Match =
            disposition.match(
                /filename\*=UTF-8''([^;]+)/i
            );

        if (utf8Match?.[1]) {
            try {
                return decodeURIComponent(
                    utf8Match[1]
                        .trim()
                        .replace(/^"|"$/g, '')
                );
            } catch (error) {
                // Abaikan dan lanjut ke filename biasa.
            }
        }

        const plainMatch =
            disposition.match(
                /filename="?([^";]+)"?/i
            );

        if (plainMatch?.[1]) {
            return plainMatch[1].trim();
        }

        const label =
            side === 'back'
                ? 'BELAKANG'
                : 'DEPAN';

        return `kartu_pelajar_A4_${label}.pdf`;
    };

    const responseErrorMessage = async (
        response
    ) => {
        const contentType = String(
            response.headers.get('Content-Type')
            || ''
        ).toLowerCase();

        if (contentType.includes('application/json')) {
            const data =
                await response.json()
                    .catch(() => ({}));

            return data.message
                || data.data?.message
                || `Gagal membuat PDF (HTTP ${response.status}).`;
        }

        const text =
            await response.text()
                .catch(() => '');

        const cleanText = text
            .replace(/<[^>]+>/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();

        if (cleanText) {
            return cleanText.slice(0, 240);
        }

        return `Gagal membuat PDF (HTTP ${response.status}).`;
    };

    const downloadPdfBlob = (
        blob,
        filename
    ) => {
        const objectUrl =
            URL.createObjectURL(blob);

        const link =
            document.createElement('a');

        link.href = objectUrl;
        link.download = filename;
        link.style.display = 'none';

        document.body.appendChild(link);
        link.click();
        link.remove();

        window.setTimeout(
            () => URL.revokeObjectURL(objectUrl),
            1000
        );
    };

    const submitPrint = async (
        side,
        mode
    ) => {
        const formData =
            new FormData();

        formData.append('side', side);
        formData.append('mode', mode);

        if (mode === 'selected') {
            const ids = selectedIds();

            if (!ids.length) {
                showAlert(
                    'Pilih minimal satu kartu aktif.',
                    'warning'
                );
                return;
            }

            if (ids.length > maxPrint) {
                showAlert(
                    `Maksimum ${maxPrint} kartu per file PDF.`,
                    'warning'
                );
                return;
            }

            ids.forEach((id) => {
                formData.append(
                    'id_kartu[]',
                    String(id)
                );
            });
        } else {
            const idKelas =
                document.getElementById(
                    'kartuKelas'
                )?.value;

            if (!idKelas) {
                showAlert(
                    'Pilih kelas terlebih dahulu untuk cetak per kelas.',
                    'warning'
                );
                return;
            }

            formData.append(
                'id_kelas',
                String(idKelas)
            );
        }

        setPrintButtonsDisabled(true);

        showAlert(
            'Menyiapkan file PDF. Jangan menutup halaman ini.',
            'info'
        );

        try {
            const response = await fetch(
                `${base}/kartu/cetak-massal`,
                {
                    method: 'POST',
                    body: formData,
                    headers: {
                        Accept:
                            'application/pdf, application/json',
                        'X-Requested-With':
                            'XMLHttpRequest',
                    },
                    credentials:
                        'same-origin',
                }
            );

            if (!response.ok) {
                throw new Error(
                    await responseErrorMessage(
                        response
                    )
                );
            }

            const contentType = String(
                response.headers.get(
                    'Content-Type'
                ) || ''
            ).toLowerCase();

            if (!contentType.includes('application/pdf')) {
                throw new Error(
                    'Server tidak mengembalikan file PDF yang valid.'
                );
            }

            const blob =
                await response.blob();

            if (blob.size <= 0) {
                throw new Error(
                    'File PDF kosong dan tidak dapat diunduh.'
                );
            }

            const filename =
                filenameFromResponse(
                    response,
                    side
                );

            downloadPdfBlob(
                blob,
                filename
            );

            showAlert(
                `PDF berhasil dibuat: ${filename}`,
                'success'
            );
        } catch (error) {
            showAlert(
                error.message
                || 'Gagal membuat PDF Kartu Pelajar.',
                'danger'
            );
        } finally {
            setPrintButtonsDisabled(false);
        }
    };

    document
        .getElementById(
            'btnCetakDepanSelected'
        )
        ?.addEventListener(
            'click',
            () => {
                submitPrint(
                    'front',
                    'selected'
                );
            }
        );

    document
        .getElementById(
            'btnCetakBelakangSelected'
        )
        ?.addEventListener(
            'click',
            () => {
                submitPrint(
                    'back',
                    'selected'
                );
            }
        );

    document
        .getElementById(
            'btnCetakDepanKelas'
        )
        ?.addEventListener(
            'click',
            () => {
                submitPrint(
                    'front',
                    'kelas'
                );
            }
        );

    document
        .getElementById(
            'btnCetakBelakangKelas'
        )
        ?.addEventListener(
            'click',
            () => {
                submitPrint(
                    'back',
                    'kelas'
                );
            }
        );

    restoreState();
    load();
})();
