(() => {
    'use strict';

    const app = document.getElementById('dokumenSiswaApp');
    if (!app) {
        return;
    }

    const base = String(app.dataset.baseUrl || '').replace(/\/+$/, '');
    const canManage = app.dataset.canManage === '1';
    const canHardDelete = app.dataset.canHardDelete === '1';
    const focusImport = app.dataset.focusImport === '1';
    const hasActivePeriod = app.dataset.hasActivePeriod === '1';

    let classes = [];
    try {
        classes = JSON.parse(
            document.getElementById('dokumenClassesData')?.textContent || '[]'
        );
    } catch {
        classes = [];
    }

    const alertBox = document.getElementById('dokumenAlert');
    const modalEl = document.getElementById('modalDokumen');
    const bulkModalEl = document.getElementById('modalBulkDokumen');
    const form = document.getElementById('formDokumen');
    const target = document.getElementById('dokumenTarget');
    const studentField = document.getElementById('dokumenStudentField');
    const levelField = document.getElementById('dokumenLevelField');
    const studentQuery = document.getElementById('dokumenStudentQuery');
    const studentSelect = document.getElementById('dokumenIdSiswa');
    const bulkForm = document.getElementById('formBulkDokumen');
    const selectedIds = new Set();

    let previewToken = '';

    const escapeHtml = (value) => {
        const node = document.createElement('div');
        node.textContent = value ?? '';
        return node.innerHTML;
    };

    function show(message, type = 'danger') {
        if (!alertBox) {
            return;
        }

        alertBox.className = 'alert alert-' + type;
        alertBox.textContent = message;
        window.scrollTo({
            top: 0,
            behavior: 'smooth',
        });
    }

    function rowData(element) {
        const holder = element.closest('[data-json]');
        try {
            return JSON.parse(
                decodeURIComponent(holder?.dataset.json || '')
            );
        } catch {
            return null;
        }
    }

    async function requestJson(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.headers || {}),
            },
        });

        let payload;
        try {
            payload = await response.json();
        } catch {
            throw new Error('Response server tidak valid.');
        }

        if (!response.ok || payload.status !== 'success') {
            throw new Error(
                payload.message || 'Proses gagal.'
            );
        }

        return payload;
    }

    function busy(button, on, label = 'Memproses...') {
        if (!button) {
            return;
        }

        if (on) {
            button.dataset.oldHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2"></span>'
                + label;
            return;
        }

        button.disabled = false;

        if (button.dataset.oldHtml) {
            button.innerHTML = button.dataset.oldHtml;
        }

        delete button.dataset.oldHtml;
    }

    function toggleTarget() {
        const individual = target?.value !== 'TINGKAT';

        studentField?.classList.toggle(
            'd-none',
            !individual
        );
        levelField?.classList.toggle(
            'd-none',
            individual
        );

        if (studentSelect) {
            studentSelect.required = individual;
        }

        const level = form?.elements.tingkat;
        if (level) {
            level.required = !individual;
        }
    }

    function syncSelectionUi() {
        document
            .querySelectorAll('.doc-select')
            .forEach((checkbox) => {
                checkbox.checked = selectedIds.has(
                    String(checkbox.value)
                );
            });

        const count = selectedIds.size;
        const countNode = document.getElementById(
            'hardDeleteCount'
        );
        const deleteButton = document.getElementById(
            'btnHardDeleteDokumen'
        );

        if (countNode) {
            countNode.textContent = '(' + count + ')';
        }

        if (deleteButton) {
            deleteButton.disabled = count === 0;
        }

        const pageIds = [
            ...new Set(
                Array.from(
                    document.querySelectorAll('.doc-select')
                ).map((item) => String(item.value))
            ),
        ];
        const selectAll = document.getElementById(
            'selectAllDokumen'
        );

        if (selectAll) {
            const selectedOnPage = pageIds.filter(
                (id) => selectedIds.has(id)
            ).length;

            selectAll.checked =
                pageIds.length > 0
                && selectedOnPage === pageIds.length;

            selectAll.indeterminate =
                selectedOnPage > 0
                && selectedOnPage < pageIds.length;
        }
    }

    target?.addEventListener(
        'change',
        toggleTarget
    );

    document
        .querySelectorAll('.doc-select')
        .forEach((checkbox) => {
            checkbox.addEventListener(
                'change',
                () => {
                    const id = String(
                        checkbox.value
                    );

                    if (checkbox.checked) {
                        selectedIds.add(id);
                    } else {
                        selectedIds.delete(id);
                    }

                    syncSelectionUi();
                }
            );
        });

    document
        .getElementById('selectAllDokumen')
        ?.addEventListener(
            'change',
            (event) => {
                const checked =
                    event.currentTarget.checked;

                [
                    ...new Set(
                        Array.from(
                            document.querySelectorAll(
                                '.doc-select'
                            )
                        ).map(
                            (item) =>
                                String(item.value)
                        )
                    ),
                ].forEach((id) => {
                    if (checked) {
                        selectedIds.add(id);
                    } else {
                        selectedIds.delete(id);
                    }
                });

                syncSelectionUi();
            }
        );

    document
        .getElementById('btnHardDeleteDokumen')
        ?.addEventListener(
            'click',
            async () => {
                if (!canHardDelete
                    || selectedIds.size === 0
                ) {
                    return;
                }

                const total = selectedIds.size;
                const confirmed = confirm(
                    'Hapus permanen '
                    + total
                    + ' Dokumen dari SisFour?\n\n'
                    + 'Tindakan ini tidak dapat dibatalkan. '
                    + 'File Google Drive TIDAK akan dihapus.'
                );

                if (!confirmed) {
                    return;
                }

                const button =
                    document.getElementById(
                        'btnHardDeleteDokumen'
                    );
                busy(
                    button,
                    true,
                    'Menghapus...'
                );

                try {
                    const body = new FormData();
                    selectedIds.forEach((id) => {
                        body.append(
                            'id_dokumen[]',
                            id
                        );
                    });

                    const payload =
                        await requestJson(
                            base
                            + '/dokumen-siswa/hard-delete',
                            {
                                method: 'POST',
                                body,
                            }
                        );

                    show(
                        payload.message
                        || 'Dokumen berhasil dihapus.',
                        'success'
                    );

                    setTimeout(
                        () => location.reload(),
                        400
                    );
                } catch (error) {
                    show(error.message);
                    busy(button, false);
                }
            }
        );

    document
        .getElementById('btnDokumenBaru')
        ?.addEventListener(
            'click',
            () => {
                form?.reset();

                if (form) {
                    form.elements.id.value = '';
                }

                const title =
                    document.getElementById(
                        'dokumenModalTitle'
                    );
                if (title) {
                    title.textContent =
                        'Tambah Dokumen';
                }

                if (studentSelect) {
                    studentSelect.innerHTML =
                        '<option value="">'
                        + 'Pilih hasil pencarian'
                        + '</option>';
                }

                toggleTarget();

                bootstrap.Modal
                    .getOrCreateInstance(modalEl)
                    .show();
            }
        );

    document
        .getElementById('btnCariDokumenSiswa')
        ?.addEventListener(
            'click',
            async () => {
                const query = String(
                    studentQuery?.value || ''
                ).trim();

                if (query.length < 2) {
                    show(
                        'Ketik minimal 2 karakter '
                        + 'untuk mencari siswa.'
                    );
                    return;
                }

                const button =
                    document.getElementById(
                        'btnCariDokumenSiswa'
                    );

                busy(
                    button,
                    true,
                    'Mencari...'
                );

                try {
                    const payload =
                        await requestJson(
                            base
                            + '/dokumen-siswa/cari-siswa?'
                            + new URLSearchParams({
                                q: query,
                            })
                        );

                    const rows =
                        payload.data?.rows || [];

                    studentSelect.innerHTML =
                        '<option value="">'
                        + 'Pilih hasil pencarian'
                        + '</option>'
                        + rows.map(
                            (row) =>
                                '<option value="'
                                + row.id
                                + '">'
                                + escapeHtml(
                                    row.text
                                )
                                + '</option>'
                        ).join('');

                    if (!rows.length) {
                        show(
                            'Siswa tidak ditemukan.',
                            'warning'
                        );
                    }
                } catch (error) {
                    show(error.message);
                } finally {
                    busy(button, false);
                }
            }
        );

    document
        .querySelectorAll('.btn-edit-dokumen')
        .forEach((button) => {
            button.addEventListener(
                'click',
                () => {
                    const row = rowData(button);
                    if (!row || !form) {
                        return;
                    }

                    form.reset();
                    form.elements.id.value =
                        String(row.id || '');
                    form.elements.target_type.value =
                        row.target_type
                        || 'INDIVIDU';
                    form.elements.format_file.value =
                        row.format_file
                        || 'PDF';
                    form.elements.judul.value =
                        row.judul || '';
                    form.elements.link_gdrive.value =
                        row.link_gdrive || '';
                    form.elements.status.value =
                        row.status
                        || 'PUBLISHED';
                    form.elements.tingkat.value =
                        row.tingkat || '';

                    studentSelect.innerHTML =
                        '<option value="">'
                        + 'Pilih hasil pencarian'
                        + '</option>';

                    if (row.id_siswa) {
                        const suffix =
                            row.nama_kelas_current
                                ? ' · '
                                    + row.nama_kelas_current
                                : '';
                        const label =
                            (row.nisn || '')
                            + ' — '
                            + (row.nama_siswa || '')
                            + suffix;

                        studentSelect.add(
                            new Option(
                                label,
                                String(
                                    row.id_siswa
                                ),
                                true,
                                true
                            )
                        );
                    }

                    const title =
                        document.getElementById(
                            'dokumenModalTitle'
                        );
                    if (title) {
                        title.textContent =
                            'Edit Dokumen';
                    }

                    toggleTarget();

                    bootstrap.Modal
                        .getOrCreateInstance(
                            modalEl
                        )
                        .show();
                }
            );
        });

    form?.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            const body = new FormData(form);
            const id = String(
                body.get('id') || ''
            );
            body.delete('id');

            const button = form.querySelector(
                '[type=submit]'
            );
            busy(
                button,
                true,
                'Menyimpan...'
            );

            try {
                const url = id
                    ? base
                        + '/dokumen-siswa/update/'
                        + encodeURIComponent(id)
                    : base
                        + '/dokumen-siswa/create';

                const options = id
                    ? {
                        method: 'PUT',
                        body: new URLSearchParams(
                            body
                        ),
                        headers: {
                            'Content-Type':
                                'application/x-www-form-urlencoded',
                        },
                    }
                    : {
                        method: 'POST',
                        body,
                    };

                const payload =
                    await requestJson(
                        url,
                        options
                    );

                bootstrap.Modal
                    .getInstance(modalEl)
                    ?.hide();

                show(
                    payload.message
                    || 'Berhasil.',
                    'success'
                );

                setTimeout(
                    () => location.reload(),
                    350
                );
            } catch (error) {
                show(error.message);
            } finally {
                busy(button, false);
            }
        }
    );

    document
        .querySelectorAll('.btn-archive-dokumen')
        .forEach((button) => {
            button.addEventListener(
                'click',
                async () => {
                    if (!confirm(
                        'Arsipkan Dokumen ini? '
                        + 'Link Google Drive tidak dihapus.'
                    )) {
                        return;
                    }

                    busy(
                        button,
                        true,
                        '...'
                    );

                    try {
                        const payload =
                            await requestJson(
                                base
                                + '/dokumen-siswa/archive/'
                                + encodeURIComponent(
                                    button.dataset.id
                                ),
                                {
                                    method: 'PUT',
                                }
                            );

                        show(
                            payload.message
                            || 'Berhasil.',
                            'success'
                        );

                        setTimeout(
                            () => location.reload(),
                            300
                        );
                    } catch (error) {
                        show(error.message);
                    } finally {
                        busy(button, false);
                    }
                }
            );
        });

    document
        .querySelectorAll('.btn-rollback-batch')
        .forEach((button) => {
            button.addEventListener(
                'click',
                async () => {
                    if (!confirm(
                        'Rollback seluruh metadata dari batch ini? '
                        + 'File Google Drive tidak akan dihapus.'
                    )) {
                        return;
                    }

                    busy(
                        button,
                        true,
                        'Rollback...'
                    );

                    try {
                        const payload =
                            await requestJson(
                                base
                                + '/dokumen-siswa/import/rollback/'
                                + encodeURIComponent(
                                    button.dataset.id
                                ),
                                {
                                    method: 'POST',
                                }
                            );

                        show(
                            payload.message
                            || 'Batch berhasil di-rollback.',
                            'success'
                        );

                        setTimeout(
                            () => location.reload(),
                            350
                        );
                    } catch (error) {
                        show(error.message);
                    } finally {
                        busy(button, false);
                    }
                }
            );
        });

    const templateFilter =
        document.getElementById(
            'templateFilter'
        );
    const templateLevelWrap =
        document.getElementById(
            'templateLevelWrap'
        );
    const templateClassWrap =
        document.getElementById(
            'templateClassWrap'
        );
    const templateLevel =
        document.getElementById(
            'templateTingkat'
        );
    const templateClass =
        document.getElementById(
            'templateKelas'
        );

    function renderTemplateControls() {
        const mode =
            templateFilter?.value
            || 'all';

        templateLevelWrap?.classList.toggle(
            'd-none',
            mode !== 'tingkat'
        );
        templateClassWrap?.classList.toggle(
            'd-none',
            mode !== 'kelas'
        );

        if (templateClass) {
            templateClass.innerHTML =
                classes.map(
                    (row) =>
                        '<option value="'
                        + row.id
                        + '">'
                        + escapeHtml(
                            row.nama_kelas
                        )
                        + '</option>'
                ).join('');
        }
    }

    templateFilter?.addEventListener(
        'change',
        renderTemplateControls
    );

    document
        .getElementById(
            'btnDownloadTemplateDokumen'
        )
        ?.addEventListener(
            'click',
            () => {
                const query =
                    new URLSearchParams();

                if (
                    templateFilter?.value
                    === 'tingkat'
                ) {
                    if (!hasActivePeriod) {
                        show(
                            'Filter Tingkat membutuhkan '
                            + 'Tahun Ajaran aktif.'
                        );
                        return;
                    }

                    query.set(
                        'tingkat',
                        String(
                            templateLevel?.value
                            || ''
                        )
                    );
                }

                if (
                    templateFilter?.value
                    === 'kelas'
                ) {
                    if (!hasActivePeriod) {
                        show(
                            'Filter Kelas membutuhkan '
                            + 'Tahun Ajaran aktif.'
                        );
                        return;
                    }

                    query.set(
                        'id_kelas',
                        String(
                            templateClass?.value
                            || ''
                        )
                    );
                }

                window.location.href =
                    base
                    + '/dokumen-siswa/template'
                    + (
                        query.toString()
                            ? '?'
                                + query.toString()
                            : ''
                    );
            }
        );

    document
        .getElementById('btnBulkDokumen')
        ?.addEventListener(
            'click',
            () => {
                previewToken = '';

                document
                    .getElementById(
                        'bulkDokumenPreview'
                    )
                    ?.classList.add('d-none');

                renderTemplateControls();

                bootstrap.Modal
                    .getOrCreateInstance(
                        bulkModalEl
                    )
                    .show();
            }
        );

    bulkForm?.addEventListener(
        'submit',
        async (event) => {
            event.preventDefault();

            const button =
                bulkForm.querySelector(
                    '[type=submit]'
                );

            busy(
                button,
                true,
                'Membaca XLSX...'
            );

            try {
                const payload =
                    await requestJson(
                        base
                        + '/dokumen-siswa/import/preview',
                        {
                            method: 'POST',
                            body: new FormData(
                                bulkForm
                            ),
                        }
                    );

                const data =
                    payload.data || {};

                previewToken = String(
                    data.token || ''
                );

                document
                    .getElementById(
                        'bulkDokumenPreview'
                    )
                    .classList.remove(
                        'd-none'
                    );

                document
                    .getElementById(
                        'bulkTotal'
                    )
                    .textContent =
                        (data.total_row || 0)
                        + ' total';

                document
                    .getElementById(
                        'bulkValid'
                    )
                    .textContent =
                        (data.total_valid || 0)
                        + ' valid';

                document
                    .getElementById(
                        'bulkError'
                    )
                    .textContent =
                        (data.total_error || 0)
                        + ' error';

                const errors =
                    document.getElementById(
                        'bulkErrors'
                    );
                const warnings =
                    document.getElementById(
                        'bulkWarnings'
                    );

                errors.classList.toggle(
                    'd-none',
                    !(data.errors || []).length
                );
                errors.innerHTML =
                    (data.errors || [])
                        .map(
                            (item) =>
                                '<div>'
                                + escapeHtml(item)
                                + '</div>'
                        )
                        .join('');

                warnings.classList.toggle(
                    'd-none',
                    !(data.warnings || []).length
                );
                warnings.innerHTML =
                    (data.warnings || [])
                        .map(
                            (item) =>
                                '<div>'
                                + escapeHtml(item)
                                + '</div>'
                        )
                        .join('');

                document
                    .getElementById(
                        'bulkPreviewBody'
                    )
                    .innerHTML =
                        (data.rows || [])
                            .map(
                                (row) =>
                                    '<tr>'
                                    + '<td>'
                                    + row.excel_row
                                    + '</td>'
                                    + '<td>'
                                    + escapeHtml(
                                        row.nisn
                                    )
                                    + '</td>'
                                    + '<td>'
                                    + escapeHtml(
                                        row.nama_siswa
                                    )
                                    + '</td>'
                                    + '<td>'
                                    + escapeHtml(
                                        row.nama_kelas
                                    )
                                    + '</td>'
                                    + '<td class="text-truncate" '
                                    + 'style="max-width:260px">'
                                    + escapeHtml(
                                        row.link_gdrive
                                    )
                                    + '</td>'
                                    + '</tr>'
                            )
                            .join('');

                document
                    .getElementById(
                        'btnCommitBulkDokumen'
                    )
                    .classList.toggle(
                        'd-none',
                        !data.can_commit
                    );

                show(
                    payload.message
                    || 'Preview selesai.',
                    data.can_commit
                        ? 'success'
                        : 'warning'
                );
            } catch (error) {
                show(error.message);
            } finally {
                busy(button, false);
            }
        }
    );

    document
        .getElementById(
            'btnCommitBulkDokumen'
        )
        ?.addEventListener(
            'click',
            async () => {
                if (!previewToken) {
                    return;
                }

                const button =
                    document.getElementById(
                        'btnCommitBulkDokumen'
                    );

                busy(
                    button,
                    true,
                    'Commit...'
                );

                try {
                    const body = new FormData();
                    body.append(
                        'token',
                        previewToken
                    );

                    const payload =
                        await requestJson(
                            base
                            + '/dokumen-siswa/import/commit',
                            {
                                method: 'POST',
                                body,
                            }
                        );

                    show(
                        payload.message
                        || 'Import berhasil.',
                        'success'
                    );

                    bootstrap.Modal
                        .getInstance(
                            bulkModalEl
                        )
                        ?.hide();

                    setTimeout(
                        () => location.reload(),
                        400
                    );
                } catch (error) {
                    show(error.message);
                } finally {
                    busy(button, false);
                }
            }
        );

    renderTemplateControls();
    toggleTarget();
    syncSelectionUi();

    if (canManage && focusImport) {
        setTimeout(
            () => {
                document
                    .getElementById(
                        'btnBulkDokumen'
                    )
                    ?.click();
            },
            150
        );
    }
})();
