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

    const mobileList =
        document.getElementById('kartuMobileList');

    const checkAll =
        document.getElementById('checkAllKartu');

    const checkAllMobile =
        document.getElementById('checkAllKartuMobile');

    if (!body || !mobileList) {
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
        'btnExportJpgKelas',
    ];

    const pager = window.SisfourPagination?.mount(
        body,
        {
            id: 'kartuDaftarPager',
            label: 'kartu',
            onChange: (next) => {
                state.limit = next.limit;
                state.offset = next.offset;

                [checkAll, checkAllMobile]
                    .filter(Boolean)
                    .forEach((control) => {
                        control.checked = false;
                    });

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

    const updateCheckAllState = () => {
        const activeIds = new Set(
            Array.from(
                document.querySelectorAll(
                    '.check-kartu:not(:disabled)'
                )
            ).map((checkbox) => checkbox.value)
        );

        const checkedIds = new Set(
            Array.from(
                document.querySelectorAll(
                    '.check-kartu:checked'
                )
            ).map((checkbox) => checkbox.value)
        );

        const allChecked =
            activeIds.size > 0
            && Array.from(activeIds)
                .every((id) => checkedIds.has(id));

        [checkAll, checkAllMobile]
            .filter(Boolean)
            .forEach((control) => {
                control.checked = allChecked;
            });
    };

    const bindSelection = () => {
        document
            .querySelectorAll('.check-kartu')
            .forEach((checkbox) => {
                checkbox.addEventListener(
                    'change',
                    () => {
                        document
                            .querySelectorAll('.check-kartu')
                            .forEach((peer) => {
                                if (
                                    peer !== checkbox
                                    && peer.value === checkbox.value
                                ) {
                                    peer.checked = checkbox.checked;
                                }
                            });

                        updateCheckAllState();
                    }
                );
            });

        updateCheckAllState();
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
                                        aria-label="Pilih kartu ${esc(card.nama)}"
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
                            class="btn btn-sm btn-outline-primary sisfour-touch-target--compact"
                            href="${base}/kartu/preview/${Number(card.id)}"
                        >
                            Preview
                        </a>
                        <a
                            class="btn btn-sm btn-outline-secondary sisfour-touch-target--compact"
                            href="${base}/kartu/download/${Number(card.id)}"
                        >
                            PDF
                        </a>
                        ${
                            serverCanManage
                                ? `
                                    <button
                                        class="btn btn-sm btn-outline-warning sisfour-touch-target--compact btn-reissue"
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

        mobileList.innerHTML =
            rows.map((card) => `
                <div class="list-group-item py-3 kartu-mobile-card">
                    <div class="kartu-mobile-card__head">
                        <div class="kartu-mobile-card__identity">
                            <div class="font-monospace small text-muted">
                                ${esc(card.nisn)}
                            </div>
                            <strong class="d-block text-wrap">
                                ${esc(card.nama)}
                            </strong>
                            <small class="d-block text-muted text-wrap">
                                ${esc(card.nama_kelas || '-')}
                            </small>
                        </div>
                        <span class="badge ${
                            card.status_aktif === 'Aktif'
                                ? 'bg-label-success'
                                : 'bg-label-secondary'
                        } flex-shrink-0">
                            ${esc(card.status_aktif)}
                        </span>
                    </div>

                    <div class="kartu-mobile-card__meta small mt-2">
                        <div><span class="text-muted">Nomor:</span> ${esc(card.nomor_kartu)}</div>
                        <div><span class="text-muted">Terbit:</span> ${esc(card.tanggal_terbit)}</div>
                    </div>

                    ${
                        canManage
                            ? `
                                <label class="kartu-mobile-card__select mt-2">
                                    <input
                                        class="form-check-input check-kartu mt-0"
                                        type="checkbox"
                                        value="${Number(card.id)}"
                                        ${card.status_aktif === 'Aktif' ? '' : 'disabled'}
                                    >
                                    <span>
                                        ${card.status_aktif === 'Aktif'
                                            ? 'Pilih untuk cetak massal'
                                            : 'Kartu nonaktif'}
                                    </span>
                                </label>
                            `
                            : ''
                    }

                    <div class="kartu-mobile-card__actions">
                        <a
                            class="btn btn-sm btn-outline-primary sisfour-touch-target--compact"
                            href="${base}/kartu/preview/${Number(card.id)}"
                        >
                            Preview
                        </a>
                        <a
                            class="btn btn-sm btn-outline-secondary sisfour-touch-target--compact"
                            href="${base}/kartu/download/${Number(card.id)}"
                        >
                            PDF
                        </a>
                        ${
                            serverCanManage
                                ? `
                                    <button
                                        class="btn btn-sm btn-outline-warning sisfour-touch-target--compact btn-reissue"
                                        data-id="${Number(card.id)}"
                                        type="button"
                                    >
                                        Reissue
                                    </button>
                                `
                                : ''
                        }
                    </div>
                </div>
            `).join('')
            || '<div class="list-group-item sisfour-mobile-state text-muted">Belum ada kartu.</div>';

        bindSelection();
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
        mobileList.innerHTML =
            '<div class="list-group-item sisfour-mobile-state text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Memuat kartu...</div>';

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

            [checkAll, checkAllMobile]
                .filter(Boolean)
                .forEach((control) => {
                    control.checked = false;
                });
        } catch (error) {
            showAlert(
                error.message,
                'danger'
            );
            mobileList.innerHTML =
                `<div class="list-group-item sisfour-mobile-state text-danger">${esc(error.message)}</div>`;
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

    const setPageSelection = (checked) => {
        document
            .querySelectorAll(
                '.check-kartu:not(:disabled)'
            )
            .forEach((checkbox) => {
                checkbox.checked = checked;
            });

        [checkAll, checkAllMobile]
            .filter(Boolean)
            .forEach((control) => {
                control.checked = checked;
            });
    };

    checkAll?.addEventListener(
        'change',
        () => setPageSelection(checkAll.checked)
    );

    checkAllMobile?.addEventListener(
        'change',
        () => setPageSelection(checkAllMobile.checked)
    );

    const selectedIds = () =>
        Array.from(
            new Set(
                Array.from(
                    document.querySelectorAll(
                        '.check-kartu:checked'
                    )
                ).map(
                    (checkbox) =>
                        Number(checkbox.value)
                ).filter(Number.isFinite)
            )
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
        fallbackFilename
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

        return fallbackFilename;
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
                || `Gagal membuat file (HTTP ${response.status}).`;
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

        return `Gagal membuat file (HTTP ${response.status}).`;
    };

    const downloadBlob = (
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

            const label =
                side === 'back'
                    ? 'BELAKANG'
                    : 'DEPAN';

            const filename =
                filenameFromResponse(
                    response,
                    `kartu_pelajar_A4_${label}.pdf`
                );

            downloadBlob(
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

    const submitJpgZip = async () => {
        const idKelas =
            document.getElementById(
                'kartuKelas'
            )?.value;

        if (!idKelas) {
            showAlert(
                'Pilih kelas terlebih dahulu untuk export JPG ZIP.',
                'warning'
            );
            return;
        }

        const formData =
            new FormData();

        formData.append(
            'id_kelas',
            String(idKelas)
        );

        setPrintButtonsDisabled(true);

        showAlert(
            'Menyiapkan JPG depan per siswa dan arsip ZIP. Jangan menutup halaman ini.',
            'info'
        );

        try {
            const response = await fetch(
                `${base}/kartu/export-jpg-zip`,
                {
                    method: 'POST',
                    body: formData,
                    headers: {
                        Accept:
                            'application/zip, application/json',
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

            if (
                !contentType.includes('application/zip')
                && !contentType.includes('application/x-zip-compressed')
            ) {
                throw new Error(
                    'Server tidak mengembalikan file ZIP yang valid.'
                );
            }

            const blob =
                await response.blob();

            if (blob.size <= 0) {
                throw new Error(
                    'File ZIP kosong dan tidak dapat diunduh.'
                );
            }

            const filename =
                filenameFromResponse(
                    response,
                    'kartu_pelajar_JPG_DEPAN.zip'
                );

            downloadBlob(
                blob,
                filename
            );

            showAlert(
                `JPG ZIP berhasil dibuat: ${filename}`,
                'success'
            );
        } catch (error) {
            showAlert(
                error.message
                || 'Gagal membuat JPG ZIP Kartu Pelajar.',
                'danger'
            );
        } finally {
            setPrintButtonsDisabled(false);
        }
    };

    document
        .getElementById(
            'btnExportJpgKelas'
        )
        ?.addEventListener(
            'click',
            submitJpgZip
        );

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
