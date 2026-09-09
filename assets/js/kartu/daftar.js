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

    let offset = 0;
    let total = 0;
    const limit = 50;

    const body = document.getElementById('kartuBody');
    const checkAll =
        document.getElementById('checkAllKartu');

    const esc = (value) => {
        const div = document.createElement('div');
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

    const params = () => {
        const params = new URLSearchParams({
            format: 'json',
            limit: String(limit),
            offset: String(offset),
        });

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
            params.set('search', search);
        }

        if (status) {
            params.set('status', status);
        }

        if (kelas) {
            params.set('id_kelas', kelas);
        }

        return params;
    };

    async function parseJson(response) {
        const data =
            await response.json().catch(() => ({}));

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

    async function load() {
        hideAlert();

        try {
            const response = await fetch(
                `${base}/kartu/daftar?${params()}`,
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With':
                            'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }
            );

            const json =
                await parseJson(response);

            const data = json.data || {};
            total = Number(data.total || 0);

            body.innerHTML =
                (data.rows || []).map((card) => `
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
                        <td class="text-end">
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
                                data.can_manage
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
                            class="text-center text-muted"
                        >
                            Belum ada kartu.
                        </td>
                    </tr>
                `;

            const info =
                document.getElementById(
                    'kartuInfo'
                );

            if (info) {
                info.textContent =
                    `${total ? offset + 1 : 0}`
                    + `-${Math.min(offset + limit, total)}`
                    + ` dari ${total}`;
            }

            const prev =
                document.getElementById(
                    'kartuPrev'
                );

            const next =
                document.getElementById(
                    'kartuNext'
                );

            if (prev) {
                prev.disabled = offset <= 0;
            }

            if (next) {
                next.disabled =
                    offset + limit >= total;
            }

            if (checkAll) {
                checkAll.checked = false;
            }

            document
                .querySelectorAll('.btn-reissue')
                .forEach((button) => {
                    button.addEventListener(
                        'click',
                        async () => {
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
                            } catch (error) {
                                showAlert(
                                    error.message,
                                    'danger'
                                );
                            }
                        }
                    );
                });
        } catch (error) {
            showAlert(
                error.message,
                'danger'
            );
        }
    }

    document
        .getElementById('btnKartuCari')
        ?.addEventListener('click', () => {
            offset = 0;
            load();
        });

    document
        .getElementById('kartuPrev')
        ?.addEventListener('click', () => {
            offset = Math.max(
                0,
                offset - limit
            );
            load();
        });

    document
        .getElementById('kartuNext')
        ?.addEventListener('click', () => {
            if (offset + limit < total) {
                offset += limit;
                load();
            }
        });

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
                        await parseJson(response);

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
                            initialCount - remaining;

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

                        /*
                         * Tombol "Generate Semua Belum Terbit"
                         * default-nya seluruh scope.
                         * Jika operator memilih kelas pada filter,
                         * bulk dibatasi ke kelas tersebut.
                         */
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
                            data.generated_count || 0
                        );

                        totalExisting += Number(
                            data.existing_count || 0
                        );

                        const nextRemaining =
                            Number(
                                data.remaining_count || 0
                            );

                        /*
                         * Guard untuk mencegah infinite loop
                         * bila server melaporkan sukses tetapi
                         * jumlah eligible tidak berkurang.
                         */
                        if (
                            nextRemaining >= remaining
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
                            initialCount - remaining;

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

    const submitPrint = (
        side,
        mode
    ) => {
        const form =
            document.createElement('form');

        form.method = 'POST';
        form.action =
            `${base}/kartu/cetak-massal`;
        form.target = '_blank';
        form.style.display = 'none';

        const add = (name, value) => {
            const input =
                document.createElement('input');

            input.type = 'hidden';
            input.name = name;
            input.value = String(value);
            form.appendChild(input);
        };

        add('side', side);
        add('mode', mode);

        /*
         * submitPrint menggunakan form.submit(), bukan fetch().
         * csrf-fetch.js hanya menginjeksi token ke request Fetch,
         * sehingga form POST biasa wajib membawa field CSRF sendiri.
         */
        if (
            window.SisisFourCsrf
            && typeof window.SisisFourCsrf.getToken === 'function'
            && typeof window.SisisFourCsrf.getTokenName === 'function'
        ) {
            add(
                window.SisisFourCsrf.getTokenName(),
                window.SisisFourCsrf.getToken()
            );
        } else {
            const tokenMeta = document.querySelector(
                'meta[name="csrf-token"]'
            );

            const tokenNameMeta = document.querySelector(
                'meta[name="csrf-token-name"]'
            );

            if (tokenMeta?.content) {
                add(
                    tokenNameMeta?.content?.trim()
                        || 'csrf_test_name',
                    tokenMeta.content
                );
            }
        }

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
                add('id_kartu[]', id);
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

            add('id_kelas', idKelas);
        }

        document.body.appendChild(form);
        form.submit();
        form.remove();
    };

    document
        .getElementById(
            'btnCetakDepanSelected'
        )
        ?.addEventListener(
            'click',
            () =>
                submitPrint(
                    'front',
                    'selected'
                )
        );

    document
        .getElementById(
            'btnCetakBelakangSelected'
        )
        ?.addEventListener(
            'click',
            () =>
                submitPrint(
                    'back',
                    'selected'
                )
        );

    document
        .getElementById(
            'btnCetakDepanKelas'
        )
        ?.addEventListener(
            'click',
            () =>
                submitPrint(
                    'front',
                    'kelas'
                )
        );

    document
        .getElementById(
            'btnCetakBelakangKelas'
        )
        ?.addEventListener(
            'click',
            () =>
                submitPrint(
                    'back',
                    'kelas'
                )
        );

    load();
})();
