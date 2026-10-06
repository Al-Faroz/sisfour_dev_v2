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
            () => {
                // Android DownloadManager owns progress/completion UI.
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
