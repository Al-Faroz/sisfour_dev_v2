(() => {
    'use strict';

    const APP_URL =
        'https://sisfour.mtsn4jombang.sch.id/';

    const APP_ORIGIN =
        new URL(APP_URL).origin;

    const DASHBOARD_URL =
        new URL('dashboard', APP_URL).href;

    const POST_DOWNLOAD_PATHS =
        new Set([
            '/statistik/export/pdf',
            '/kartu/cetak-massal',
            '/kartu/export-jpg-zip',
        ]);

    const status =
        document.getElementById('appStatus');

    const retryButton =
        document.getElementById('retryButton');

    let browser = null;
    let opening = false;
    let closingAfterError = false;
    let currentUrl = APP_URL;

    const setStatus = (
        message,
        canRetry = false
    ) => {
        if (status) {
            status.textContent = message;
        }

        if (retryButton) {
            retryButton.hidden = !canRetry;
        }
    };

    const parseUrl = (value) => {
        try {
            return new URL(value);
        } catch {
            return null;
        }
    };

    const isInternalUrl = (value) => {
        const url = parseUrl(value);

        return Boolean(
            url
            && url.protocol === 'https:'
            && url.origin === APP_ORIGIN
        );
    };

    const openExternalHttps = (value) => {
        const url = parseUrl(value);

        if (!url || url.protocol !== 'https:') {
            return false;
        }

        cordova.InAppBrowser.open(
            url.href,
            '_system'
        );

        return true;
    };

    const showBrowserAlert = (message) => {
        if (!browser) {
            return;
        }

        browser.executeScript({
            code: `window.alert(${JSON.stringify(message)});`,
        });
    };

    const handleDownload = (event) => {
        if (!event || !isInternalUrl(event.url)) {
            showBrowserAlert(
                'Download diblokir karena sumber file tidak diizinkan.'
            );
            return;
        }

        if (!window.SisFourNative?.download) {
            showBrowserAlert(
                'Handler download Android belum tersedia.'
            );
            return;
        }

        window.SisFourNative.download(
            {
                url: event.url,
                userAgent: event.userAgent || '',
                contentDisposition:
                    event.contentDisposition || '',
                mimetype: event.mimetype || '',
                contentLength:
                    Number(event.contentLength) || 0,
            },
            (result) => {
                const fileName =
                    result?.fileName
                    || 'file';

                showBrowserAlert(
                    'File tersimpan di Downloads: '
                    + fileName
                );
            },
            (error) => {
                const detail =
                    typeof error === 'string'
                        ? error
                        : 'Download gagal dimulai.';

                showBrowserAlert(detail);
            }
        );
    };

    const clampNumber = (
        value,
        min,
        max,
        fallback
    ) => {
        const number = Number(value);

        if (!Number.isFinite(number)) {
            return fallback;
        }

        return Math.min(
            max,
            Math.max(min, number)
        );
    };

    const sendGeoResult = (payload) => {
        if (!browser) {
            return;
        }

        browser.executeScript({
            code:
                `window.__sisfourNativeGeoResolve`
                + ` && window.__sisfourNativeGeoResolve(`
                + `${JSON.stringify(payload)});`,
        });
    };

    const sendFileResult = (payload) => {
        if (!browser) {
            return;
        }

        browser.executeScript({
            code:
                `window.__sisfourNativeFileResolve`
                + ` && window.__sisfourNativeFileResolve(`
                + `${JSON.stringify(payload)});`,
        });
    };

    const isAllowedPostDownloadUrl = (value) => {
        const url = parseUrl(value);

        return Boolean(
            url
            && url.protocol === 'https:'
            && url.origin === APP_ORIGIN
            && POST_DOWNLOAD_PATHS.has(url.pathname)
        );
    };

    const handleLocationRequest = (data) => {
        if (!isInternalUrl(currentUrl)) {
            return;
        }

        const requestId =
            typeof data?.requestId === 'string'
                ? data.requestId
                : '';

        if (
            !/^[A-Za-z0-9._:-]{1,96}$/
                .test(requestId)
        ) {
            return;
        }

        if (!navigator.geolocation) {
            sendGeoResult({
                requestId,
                ok: false,
                code: 2,
                message:
                    'Layanan lokasi Android tidak tersedia.',
            });
            return;
        }

        const rawOptions =
            data.options
            && typeof data.options === 'object'
                ? data.options
                : {};

        const options = {
            enableHighAccuracy:
                rawOptions.enableHighAccuracy !== false,
            timeout: clampNumber(
                rawOptions.timeout,
                1000,
                30000,
                15000
            ),
            maximumAge: clampNumber(
                rawOptions.maximumAge,
                0,
                60000,
                0
            ),
        };

        navigator.geolocation.getCurrentPosition(
            (position) => {
                sendGeoResult({
                    requestId,
                    ok: true,
                    position: {
                        coords: {
                            latitude:
                                position.coords.latitude,
                            longitude:
                                position.coords.longitude,
                            accuracy:
                                position.coords.accuracy,
                            altitude:
                                position.coords.altitude,
                            altitudeAccuracy:
                                position.coords.altitudeAccuracy,
                            heading:
                                position.coords.heading,
                            speed:
                                position.coords.speed,
                        },
                        timestamp:
                            position.timestamp
                            || Date.now(),
                    },
                });
            },
            (error) => {
                sendGeoResult({
                    requestId,
                    ok: false,
                    code:
                        Number(error?.code) || 2,
                    message:
                        error?.message
                        || 'Lokasi tidak dapat diperoleh.',
                });
            },
            options
        );
    };

    const handlePostDownloadRequest = (data) => {
        if (
            !isInternalUrl(currentUrl)
            || !window.SisFourNative?.downloadPost
        ) {
            return;
        }

        const requestId =
            typeof data?.requestId === 'string'
                ? data.requestId
                : '';

        if (
            !/^[A-Za-z0-9._:-]{1,96}$/
                .test(requestId)
            || !isAllowedPostDownloadUrl(data.url)
        ) {
            sendFileResult({
                requestId,
                ok: false,
                message:
                    'POST download diblokir oleh policy APK.',
            });
            return;
        }

        const fields =
            Array.isArray(data.fields)
                ? data.fields.slice(0, 600)
                : [];

        const headers =
            data.headers
            && typeof data.headers === 'object'
                ? data.headers
                : {};

        window.SisFourNative.downloadPost(
            {
                url: data.url,
                fields,
                headers,
                accept:
                    typeof data.accept === 'string'
                        ? data.accept
                        : 'application/octet-stream',
                userAgent:
                    typeof data.userAgent === 'string'
                        ? data.userAgent
                        : '',
            },
            (result) => {
                sendFileResult({
                    requestId,
                    ok: true,
                    result: result || {},
                });
            },
            (error) => {
                sendFileResult({
                    requestId,
                    ok: false,
                    message:
                        typeof error === 'string'
                            ? error
                            : 'POST download gagal.',
                });
            }
        );
    };

    const handleAppExitRequest = () => {
        const url = parseUrl(currentUrl);
        const path =
            url?.pathname?.replace(/\/+$/, '')
            || '/';

        if (
            path !== '/'
            && path !== '/dashboard'
        ) {
            return;
        }

        if (browser) {
            browser.close();
        }

        window.setTimeout(
            () => navigator.app?.exitApp?.(),
            120
        );
    };

    const handleBridgeMessage = (event) => {
        const data = event?.data;

        if (
            !data
            || typeof data !== 'object'
        ) {
            return;
        }

        if (data.type === 'location.request') {
            handleLocationRequest(data);
            return;
        }

        if (data.type === 'file.downloadPost') {
            handlePostDownloadRequest(data);
            return;
        }

        if (data.type === 'app.exit') {
            handleAppExitRequest();
        }
    };

    const injectDashboardButton = () => {
        if (!browser || !isInternalUrl(currentUrl)) {
            return;
        }

        const code = `
(() => {
    const dashboardUrl = ${JSON.stringify(DASHBOARD_URL)};

    const path =
        window.location.pathname
            .replace(/\\/+$/, '')
        || '/';

    const shouldShow =
        path !== '/'
        && path !== '/dashboard'
        && !path.startsWith('/auth');

    const existing =
        document.getElementById(
            'sisfourCordovaDashboard'
        );

    if (!shouldShow) {
        existing?.remove();
        return false;
    }

    if (existing) {
        existing.hidden = false;
        return true;
    }

    if (!document.body) {
        return false;
    }

    const button =
        document.createElement('button');

    button.id =
        'sisfourCordovaDashboard';

    button.type = 'button';

    button.setAttribute(
        'aria-label',
        'Kembali ke Dashboard'
    );

    button.setAttribute(
        'title',
        'Dashboard'
    );

    button.innerHTML =
        '<span aria-hidden="true">⌂</span>';

    button.style.cssText = [
        'position:fixed',
        'right:104px',
        'bottom:calc(env(safe-area-inset-bottom, 0px) + 18px)',
        'z-index:2147483647',
        'width:48px',
        'height:48px',
        'border:0',
        'border-radius:50%',
        'display:flex',
        'align-items:center',
        'justify-content:center',
        'padding:0',
        'font:700 26px/1 system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif',
        'color:#fff',
        'background:#009846',
        'box-shadow:0 4px 14px rgba(0,0,0,.30)',
        'opacity:.96',
        'touch-action:manipulation'
    ].join(';');

    button.addEventListener(
        'click',
        () => {
            window.location.assign(
                dashboardUrl
            );
        }
    );

    document.body.appendChild(button);
    return true;
})();
`;

        browser.executeScript(
            { code },
            () => {
                // Independent helper: no dependency on other injected bridges.
            }
        );

        window.setTimeout(
            () => {
                if (!browser) {
                    return;
                }

                browser.executeScript({
                    code,
                });
            },
            700
        );
    };

    const injectLoginAutofillHints = () => {
        if (!browser || !isInternalUrl(currentUrl)) {
            return;
        }

        const url = parseUrl(currentUrl);

        if (
            !url
            || !url.pathname
                .replace(/\/+$/, '')
                .endsWith('/auth/login')
        ) {
            return;
        }

        const code = `
(() => {
    const username =
        document.getElementById('username');

    const password =
        document.getElementById('password');

    if (!username || !password) {
        return false;
    }

    username.setAttribute(
        'autocomplete',
        'username'
    );

    username.setAttribute(
        'autocapitalize',
        'none'
    );

    username.setAttribute(
        'spellcheck',
        'false'
    );

    password.setAttribute(
        'autocomplete',
        'current-password'
    );

    window.setTimeout(
        () => {
            if (
                document.visibilityState
                === 'visible'
            ) {
                username.focus({
                    preventScroll: true
                });
            }
        },
        350
    );

    return true;
})();
`;

        browser.executeScript({
            code,
        });
    };

    const injectRemoteHelpers = () => {
        if (!browser || !isInternalUrl(currentUrl)) {
            return;
        }

        const code = `
(() => {
    const dashboardUrl = ${JSON.stringify(DASHBOARD_URL)};

    const currentPath =
        window.location.pathname.replace(/\\/+$/, '')
        || '/';

    const shouldShowDashboard =
        currentPath !== '/dashboard'
        && currentPath !== '/'
        && !currentPath.startsWith('/auth');

    if (
        shouldShowDashboard
        && !document.getElementById('sisfourCordovaDashboard')
    ) {
        const button = document.createElement('button');

        button.id = 'sisfourCordovaDashboard';
        button.type = 'button';
        button.setAttribute(
            'aria-label',
            'Kembali ke Dashboard'
        );
        button.setAttribute(
            'title',
            'Kembali ke Dashboard'
        );
        button.textContent = '⌂';

        button.style.cssText = [
            'position:fixed',
            'right:14px',
            'bottom:calc(env(safe-area-inset-bottom, 0px) + 70px)',
            'z-index:2147483646',
            'width:46px',
            'height:46px',
            'border:0',
            'border-radius:50%',
            'display:flex',
            'align-items:center',
            'justify-content:center',
            'padding:0',
            'font:700 25px/1 system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif',
            'color:#fff',
            'background:#696cff',
            'box-shadow:0 4px 14px rgba(0,0,0,.24)',
            'opacity:.92',
            'touch-action:manipulation'
        ].join(';');

        button.addEventListener('click', () => {
            window.location.assign(dashboardUrl);
        });

        document.body.appendChild(button);
    }

    if (
        window.cordova_iab
        && !window.__sisfourNativeGeoInstalled
    ) {
        window.__sisfourNativeGeoInstalled = true;
        window.__sisfourNativeGeoPending =
            Object.create(null);
        window.__sisfourLastUserGesture = 0;

        const markUserGesture = () => {
            window.__sisfourLastUserGesture =
                Date.now();
        };

        [
            'pointerdown',
            'touchstart',
            'click',
            'keydown'
        ].forEach((eventName) => {
            document.addEventListener(
                eventName,
                markUserGesture,
                {
                    capture: true,
                    passive: true
                }
            );
        });

        window.__sisfourNativeGeoResolve =
            (payload) => {
                const requestId =
                    payload
                    && typeof payload.requestId === 'string'
                        ? payload.requestId
                        : '';

                const pending =
                    window.__sisfourNativeGeoPending[
                        requestId
                    ];

                if (!pending) {
                    return;
                }

                delete window.__sisfourNativeGeoPending[
                    requestId
                ];

                if (payload.ok) {
                    pending.success(
                        payload.position
                    );
                    return;
                }

                if (typeof pending.error === 'function') {
                    pending.error({
                        code:
                            Number(payload.code) || 2,
                        message:
                            payload.message
                            || 'Lokasi tidak dapat diperoleh.'
                    });
                }
            };

        const nativeGetCurrentPosition =
            (success, error, options) => {
                if (typeof success !== 'function') {
                    throw new TypeError(
                        'Callback geolocation wajib berupa fungsi.'
                    );
                }

                const sinceGesture =
                    Date.now()
                    - Number(
                        window.__sisfourLastUserGesture
                        || 0
                    );

                if (
                    sinceGesture < 0
                    || sinceGesture > 15000
                ) {
                    if (typeof error === 'function') {
                        error({
                            code: 1,
                            message:
                                'Lokasi hanya dapat diminta setelah tindakan pengguna.'
                        });
                    }
                    return;
                }

                const requestId =
                    'geo-'
                    + Date.now().toString(36)
                    + '-'
                    + Math.random()
                        .toString(36)
                        .slice(2, 10);

                window.__sisfourNativeGeoPending[
                    requestId
                ] = {
                    success,
                    error
                };

                const safeOptions = {
                    enableHighAccuracy:
                        options?.enableHighAccuracy !== false,
                    timeout:
                        Number(options?.timeout) || 15000,
                    maximumAge:
                        Number(options?.maximumAge) || 0
                };

                window.cordova_iab.postMessage(
                    JSON.stringify({
                        type: 'location.request',
                        requestId,
                        options: safeOptions
                    })
                );
            };

        try {
            if (!navigator.geolocation) {
                Object.defineProperty(
                    navigator,
                    'geolocation',
                    {
                        configurable: true,
                        value: {}
                    }
                );
            }

            Object.defineProperty(
                navigator.geolocation,
                'getCurrentPosition',
                {
                    configurable: true,
                    writable: true,
                    value: nativeGetCurrentPosition
                }
            );
        } catch (error) {
            try {
                navigator.geolocation.getCurrentPosition =
                    nativeGetCurrentPosition;
            } catch (ignored) {
                // The page keeps its browser implementation.
            }
        }
    }

    if (
        window.cordova_iab
        && !window.__sisfourNativeFileInstalled
    ) {
        window.__sisfourNativeFileInstalled = true;
        window.__sisfourNativeFilePending =
            Object.create(null);

        window.__sisfourNativeFileResolve =
            (payload) => {
                const requestId =
                    payload
                    && typeof payload.requestId === 'string'
                        ? payload.requestId
                        : '';

                const pending =
                    window.__sisfourNativeFilePending[
                        requestId
                    ];

                if (!pending) {
                    return;
                }

                delete window.__sisfourNativeFilePending[
                    requestId
                ];

                if (payload.ok) {
                    pending.resolve(
                        payload.result || {}
                    );
                    return;
                }

                pending.reject(
                    new Error(
                        payload.message
                        || 'Download gagal diproses.'
                    )
                );
            };

        const nativeCsrfHeaders = () => {
            const headers = {};

            try {
                const token =
                    window.SisisFourCsrf?.getToken?.();

                const headerName =
                    window.SisisFourCsrf?.headerName
                    || 'X-CSRF-TOKEN';

                if (token) {
                    headers[headerName] = token;
                }
            } catch (ignored) {
                // Form token may still be present.
            }

            return headers;
        };

        const nativeFormFields = (formData) => {
            const fields = [];

            for (
                const [name, value]
                of formData.entries()
            ) {
                if (typeof value !== 'string') {
                    continue;
                }

                fields.push({
                    name,
                    value
                });
            }

            return fields;
        };

        const requestNativePostDownload = (
            url,
            fields,
            accept
        ) => new Promise((resolve, reject) => {
            const requestId =
                'file-'
                + Date.now().toString(36)
                + '-'
                + Math.random()
                    .toString(36)
                    .slice(2, 10);

            window.__sisfourNativeFilePending[
                requestId
            ] = {
                resolve,
                reject
            };

            window.cordova_iab.postMessage(
                JSON.stringify({
                    type: 'file.downloadPost',
                    requestId,
                    url,
                    fields,
                    accept,
                    headers:
                        nativeCsrfHeaders(),
                    userAgent:
                        navigator.userAgent || ''
                })
            );
        });

        const statistikExportForm =
            document.getElementById(
                'statistikExportForm'
            );

        if (statistikExportForm) {
            const nativeStatistikSubmit = () => {
                const status =
                    document.getElementById(
                        'statistikStatus'
                    );

                if (status) {
                    status.textContent =
                        'Mengunduh PDF melalui Android...';
                }

                const fields =
                    nativeFormFields(
                        new FormData(
                            statistikExportForm
                        )
                    );

                requestNativePostDownload(
                    statistikExportForm.action,
                    fields,
                    'application/pdf'
                )
                    .then((result) => {
                        if (status) {
                            status.textContent =
                                'PDF tersimpan di Downloads: '
                                + (
                                    result.fileName
                                    || 'statistik.pdf'
                                );
                        }
                    })
                    .catch((error) => {
                        if (status) {
                            status.textContent =
                                error.message
                                || 'Export PDF gagal.';
                        }
                    });
            };

            try {
                Object.defineProperty(
                    statistikExportForm,
                    'submit',
                    {
                        configurable: true,
                        writable: true,
                        value: nativeStatistikSubmit
                    }
                );
            } catch (ignored) {
                statistikExportForm.submit =
                    nativeStatistikSubmit;
            }
        }

        const kartuButtonIds = [
            'btnCetakDepanSelected',
            'btnCetakBelakangSelected',
            'btnCetakDepanKelas',
            'btnCetakBelakangKelas',
            'btnExportJpgKelas'
        ];

        const showKartuAlert = (
            message,
            type = 'info'
        ) => {
            const alert =
                document.getElementById(
                    'kartuAlert'
                );

            if (!alert) {
                return;
            }

            alert.className =
                'alert alert-' + type;

            alert.textContent = message;
        };

        const selectedKartuIds = () => {
            const values = new Set();

            document
                .querySelectorAll(
                    '.check-kartu:checked:not(:disabled)'
                )
                .forEach((input) => {
                    const value =
                        String(input.value || '')
                            .trim();

                    if (value) {
                        values.add(value);
                    }
                });

            return Array.from(values);
        };

        const captureKartuButtonState = () =>
            kartuButtonIds
                .map((id) =>
                    document.getElementById(id)
                )
                .filter(Boolean)
                .map((button) => ({
                    button,
                    disabled:
                        Boolean(button.disabled)
                }));

        const setCapturedButtonsBusy = (
            states,
            busy
        ) => {
            states.forEach((state) => {
                state.button.disabled =
                    busy
                        ? true
                        : state.disabled;
            });
        };

        document.addEventListener(
            'click',
            (event) => {
                const button =
                    event.target?.closest?.(
                        '#btnCetakDepanSelected,'
                        + '#btnCetakBelakangSelected,'
                        + '#btnCetakDepanKelas,'
                        + '#btnCetakBelakangKelas,'
                        + '#btnExportJpgKelas'
                    );

                if (!button) {
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();

                const idKelas =
                    String(
                        document
                            .getElementById(
                                'kartuKelas'
                            )
                            ?.value
                        || ''
                    ).trim();

                const fields = [];
                let url = '';
                let accept = '';
                let fallbackName = '';

                if (
                    button.id
                    === 'btnExportJpgKelas'
                ) {
                    if (!idKelas) {
                        showKartuAlert(
                            'Pilih kelas terlebih dahulu untuk export JPG ZIP.',
                            'warning'
                        );
                        return;
                    }

                    fields.push({
                        name: 'id_kelas',
                        value: idKelas
                    });

                    url =
                        window.location.origin
                        + '/kartu/export-jpg-zip';

                    accept =
                        'application/zip';

                    fallbackName =
                        'kartu_pelajar_JPG_DEPAN.zip';
                } else {
                    const isSelected =
                        button.id.endsWith(
                            'Selected'
                        );

                    const side =
                        button.id.includes(
                            'Belakang'
                        )
                            ? 'back'
                            : 'front';

                    const mode =
                        isSelected
                            ? 'selected'
                            : 'class';

                    fields.push({
                        name: 'side',
                        value: side
                    });

                    fields.push({
                        name: 'mode',
                        value: mode
                    });

                    if (isSelected) {
                        const ids =
                            selectedKartuIds();

                        if (!ids.length) {
                            showKartuAlert(
                                'Pilih minimal satu kartu aktif.',
                                'warning'
                            );
                            return;
                        }

                        ids.forEach((id) => {
                            fields.push({
                                name: 'id_kartu[]',
                                value: id
                            });
                        });
                    } else {
                        if (!idKelas) {
                            showKartuAlert(
                                'Pilih kelas terlebih dahulu.',
                                'warning'
                            );
                            return;
                        }

                        fields.push({
                            name: 'id_kelas',
                            value: idKelas
                        });
                    }

                    url =
                        window.location.origin
                        + '/kartu/cetak-massal';

                    accept =
                        'application/pdf';

                    fallbackName =
                        'kartu_pelajar_A4.pdf';
                }

                const buttonStates =
                    captureKartuButtonState();

                setCapturedButtonsBusy(
                    buttonStates,
                    true
                );

                showKartuAlert(
                    'Menyiapkan file melalui Android. Jangan menutup halaman ini.',
                    'info'
                );

                requestNativePostDownload(
                    url,
                    fields,
                    accept
                )
                    .then((result) => {
                        showKartuAlert(
                            'File tersimpan di Downloads: '
                            + (
                                result.fileName
                                || fallbackName
                            ),
                            'success'
                        );
                    })
                    .catch((error) => {
                        showKartuAlert(
                            error.message
                            || 'Download gagal.',
                            'danger'
                        );
                    })
                    .finally(() => {
                        setCapturedButtonsBusy(
                            buttonStates,
                            false
                        );
                    });
            },
            true
        );
    }

    if (
        window.cordova_iab
        && !window.__sisfourCordovaBackInstalled
    ) {
        window.__sisfourCordovaBackInstalled =
            true;

        let allowRealBack = false;
        let exitArmedAt = 0;

        const rearmBackSentinel = () => {
            window.history.pushState(
                {
                    sisfourCordovaBack:
                        true
                },
                '',
                window.location.href
            );
        };

        const hideBootstrapLayer = (
            selector,
            apiName,
            dismissSelector
        ) => {
            const element =
                document.querySelector(
                    selector
                );

            if (!element) {
                return false;
            }

            try {
                const api =
                    window.bootstrap?.[apiName];

                const instance =
                    api?.getInstance?.(element)
                    || api?.getOrCreateInstance?.(
                        element
                    );

                if (instance?.hide) {
                    instance.hide();
                    return true;
                }
            } catch (ignored) {
                // Fallback below.
            }

            const dismiss =
                element.querySelector(
                    dismissSelector
                );

            if (dismiss) {
                dismiss.click();
                return true;
            }

            element.classList.remove('show');
            return true;
        };

        const closeTopLayer = () => {
            if (
                hideBootstrapLayer(
                    '.modal.show',
                    'Modal',
                    '[data-bs-dismiss="modal"]'
                )
            ) {
                return true;
            }

            if (
                hideBootstrapLayer(
                    '.offcanvas.show',
                    'Offcanvas',
                    '[data-bs-dismiss="offcanvas"]'
                )
            ) {
                return true;
            }

            const dropdownMenu =
                document.querySelector(
                    '.dropdown-menu.show'
                );

            if (dropdownMenu) {
                const dropdown =
                    dropdownMenu.closest(
                        '.dropdown'
                    );

                const toggle =
                    dropdown?.querySelector(
                        '[data-bs-toggle="dropdown"]'
                    );

                try {
                    const instance =
                        window.bootstrap
                            ?.Dropdown
                            ?.getInstance?.(
                                toggle
                            );

                    if (instance?.hide) {
                        instance.hide();
                        return true;
                    }
                } catch (ignored) {
                    // Fallback below.
                }

                toggle?.click?.();
                return true;
            }

            if (
                document.documentElement
                    .classList
                    .contains(
                        'layout-menu-expanded'
                    )
            ) {
                try {
                    window.Helpers
                        ?.setCollapsed?.(
                            true
                        );
                } catch (ignored) {
                    document
                        .documentElement
                        .classList
                        .remove(
                            'layout-menu-expanded'
                        );
                }

                document
                    .documentElement
                    .classList
                    .remove(
                        'layout-menu-expanded'
                    );

                return true;
            }

            const appSidebar =
                document.querySelector(
                    '[data-bs-toggle="sidebar"]'
                )
                ?.getAttribute(
                    'data-target'
                );

            if (appSidebar) {
                const openSidebar =
                    document.querySelector(
                        appSidebar + '.show'
                    );

                if (openSidebar) {
                    openSidebar
                        .classList
                        .remove('show');

                    document
                        .querySelector(
                            '.app-overlay.show'
                        )
                        ?.classList
                        .remove('show');

                    return true;
                }
            }

            return false;
        };

        const pageLooksDirty = () => {
            try {
                const probe =
                    new Event(
                        'beforeunload',
                        {
                            cancelable: true
                        }
                    );

                window.dispatchEvent(probe);

                return probe.defaultPrevented;
            } catch (ignored) {
                return false;
            }
        };

        const showExitHint = () => {
            let hint =
                document.getElementById(
                    'sisfourCordovaExitHint'
                );

            if (!hint) {
                hint =
                    document.createElement(
                        'div'
                    );

                hint.id =
                    'sisfourCordovaExitHint';

                hint.style.cssText = [
                    'position:fixed',
                    'left:50%',
                    'bottom:calc(env(safe-area-inset-bottom, 0px) + 24px)',
                    'transform:translateX(-50%)',
                    'z-index:2147483647',
                    'max-width:calc(100vw - 32px)',
                    'padding:10px 14px',
                    'border-radius:999px',
                    'background:rgba(32,33,36,.92)',
                    'color:#fff',
                    'font:500 14px/1.3 system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif',
                    'box-shadow:0 4px 16px rgba(0,0,0,.28)',
                    'pointer-events:none'
                ].join(';');

                document.body.appendChild(
                    hint
                );
            }

            hint.textContent =
                'Tekan Kembali sekali lagi untuk keluar.';

            hint.hidden = false;

            window.clearTimeout(
                window.__sisfourExitHintTimer
            );

            window.__sisfourExitHintTimer =
                window.setTimeout(
                    () => {
                        hint.hidden = true;
                    },
                    1800
                );
        };

        rearmBackSentinel();

        window.addEventListener(
            'popstate',
            () => {
                if (allowRealBack) {
                    allowRealBack = false;
                    return;
                }

                if (closeTopLayer()) {
                    rearmBackSentinel();
                    return;
                }

                const path =
                    window.location.pathname
                        .replace(/\/+$/, '')
                    || '/';

                if (
                    path === '/'
                    || path === '/dashboard'
                ) {
                    const now = Date.now();

                    if (
                        now - exitArmedAt
                        <= 1800
                    ) {
                        window.cordova_iab
                            .postMessage(
                                JSON.stringify({
                                    type: 'app.exit'
                                })
                            );
                        return;
                    }

                    exitArmedAt = now;
                    rearmBackSentinel();
                    showExitHint();
                    return;
                }

                if (
                    pageLooksDirty()
                    && !window.confirm(
                        'Perubahan belum disimpan. Tinggalkan halaman ini?'
                    )
                ) {
                    rearmBackSentinel();
                    return;
                }

                allowRealBack = true;
                window.history.back();
            }
        );
    }

})();
`;

        browser.executeScript({
            code,
        });
    };

    const releaseBrowser = () => {
        browser = null;
        opening = false;
        currentUrl = APP_URL;
    };

    const openSisFour = () => {
        if (browser || opening) {
            return;
        }

        opening = true;

        setStatus(
            'Menghubungkan ke SisFour…'
        );

        const options = [
            'location=no',
            'hidden=yes',
            'beforeload=get',
            'hardwareback=yes',
            'fullscreen=no',
        ].join(',');

        browser =
            cordova.InAppBrowser.open(
                APP_URL,
                '_blank',
                options
            );

        browser.addEventListener(
            'beforeload',
            (event, callback) => {
                if (isInternalUrl(event.url)) {
                    currentUrl = event.url;
                    callback(event.url);
                    return;
                }

                if (openExternalHttps(event.url)) {
                    return;
                }

                setStatus(
                    'Navigasi non-HTTPS atau tidak dikenal diblokir.'
                );
            }
        );

        browser.addEventListener(
            'message',
            handleBridgeMessage
        );

        browser.addEventListener(
            'download',
            handleDownload
        );

        browser.addEventListener(
            'loadstart',
            (event) => {
                if (isInternalUrl(event?.url)) {
                    currentUrl = event.url;
                }

                setStatus(
                    'Memuat SisFour…'
                );
            }
        );

        browser.addEventListener(
            'loadstop',
            (event) => {
                if (!browser) {
                    return;
                }

                if (isInternalUrl(event?.url)) {
                    currentUrl = event.url;
                }

                opening = false;

                setStatus(
                    'SisFour siap.'
                );

                injectDashboardButton();
                injectLoginAutofillHints();
                injectRemoteHelpers();
                browser.show();
            }
        );

        browser.addEventListener(
            'loaderror',
            () => {
                closingAfterError = true;

                const failedBrowser =
                    browser;

                releaseBrowser();

                if (failedBrowser) {
                    failedBrowser.close();
                }

                setStatus(
                    'SisFour tidak dapat dibuka. Periksa koneksi internet lalu coba lagi.',
                    true
                );
            }
        );

        browser.addEventListener(
            'exit',
            () => {
                releaseBrowser();

                if (closingAfterError) {
                    closingAfterError = false;
                    return;
                }

                setStatus(
                    'SisFour ditutup.',
                    true
                );
            }
        );
    };

    retryButton?.addEventListener(
        'click',
        openSisFour
    );

    document.addEventListener(
        'deviceready',
        () => {
            document.documentElement
                .classList.add(
                    'sisfour-cordova'
                );

            openSisFour();
        },
        false
    );
})();
