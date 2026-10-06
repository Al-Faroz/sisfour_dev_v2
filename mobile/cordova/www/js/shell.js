(() => {
    'use strict';

    const APP_URL =
        'https://sisfour.mtsn4jombang.sch.id/';

    const APP_ORIGIN =
        new URL(APP_URL).origin;

    const DASHBOARD_URL =
        new URL('dashboard', APP_URL).href;

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

    const showRemoteAlert = (message) => {
        if (!browser) {
            return;
        }

        browser.executeScript({
            code:
                `window.alert(${JSON.stringify(message)});`,
        });
    };

    const handleDownload = (event) => {
        if (!event || !isInternalUrl(event.url)) {
            showRemoteAlert(
                'Download diblokir karena sumber file tidak diizinkan.'
            );
            return;
        }

        if (!window.SisFourNative?.download) {
            showRemoteAlert(
                'Handler download Android belum tersedia.'
            );
            return;
        }

        window.SisFourNative.download(
            {
                url: event.url,
                userAgent:
                    event.userAgent || '',
                contentDisposition:
                    event.contentDisposition || '',
                mimetype:
                    event.mimetype || '',
                contentLength:
                    Number(event.contentLength) || 0,
            },
            (result) => {
                const fileName =
                    result?.fileName
                    || 'file';

                showRemoteAlert(
                    'File tersimpan di Downloads: '
                    + fileName
                );
            },
            (error) => {
                showRemoteAlert(
                    typeof error === 'string'
                        ? error
                        : 'Download gagal diproses.'
                );
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
            {
                enableHighAccuracy:
                    rawOptions.enableHighAccuracy
                    !== false,
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
    const path =
        window.location.pathname
            .replace(/\\/+$/, '')
        || '/';

    const existing =
        document.getElementById(
            'sisfourCordovaDashboard'
        );

    const shouldShow =
        path !== '/'
        && path !== '/dashboard'
        && !path.startsWith('/auth');

    if (!shouldShow) {
        existing?.remove();
        return;
    }

    if (existing || !document.body) {
        return;
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
        'Kembali ke Dashboard'
    );

    button.textContent = '⌂';

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
        'background:#696cff',
        'box-shadow:0 4px 14px rgba(0,0,0,.28)',
        'opacity:.96',
        'touch-action:manipulation'
    ].join(';');

    button.addEventListener(
        'click',
        () => {
            window.location.assign(
                ${JSON.stringify(DASHBOARD_URL)}
            );
        }
    );

    document.body.appendChild(button);
})();
`;

        browser.executeScript({
            code,
        });
    };

    const injectGeolocationBridge = () => {
        if (!browser || !isInternalUrl(currentUrl)) {
            return;
        }

        const code = `
(() => {
    if (
        !window.cordova_iab
        || window.__sisfourNativeGeoInstalled
    ) {
        return;
    }

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
                && typeof payload.requestId
                    === 'string'
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

            window.cordova_iab.postMessage(
                JSON.stringify({
                    type: 'location.request',
                    requestId,
                    options: {
                        enableHighAccuracy:
                            options?.enableHighAccuracy
                            !== false,
                        timeout:
                            Number(options?.timeout)
                            || 15000,
                        maximumAge:
                            Number(options?.maximumAge)
                            || 0
                    }
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
            navigator.geolocation
                .getCurrentPosition =
                    nativeGetCurrentPosition;
        } catch (ignored) {
            // Keep the WebView implementation if it is immutable.
        }
    }
})();
`;

        browser.executeScript({
            code,
        });
    };

    const injectBackAdapter = () => {
        if (!browser || !isInternalUrl(currentUrl)) {
            return;
        }

        const code = `
(() => {
    if (
        !window.cordova_iab
        || window.__sisfourCordovaBackInstalled
    ) {
        return;
    }

    window.__sisfourCordovaBackInstalled =
        true;

    let allowRealBack = false;
    let exitArmedAt = 0;

    const rearm = () => {
        window.history.pushState(
            {
                sisfourCordovaBack: true
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
            document.querySelector(selector);

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

        const dropdown =
            document.querySelector(
                '.dropdown-menu.show'
            );

        if (dropdown) {
            const toggle =
                dropdown
                    .closest('.dropdown')
                    ?.querySelector(
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
                    ?.setCollapsed?.(true);
            } catch (ignored) {
                // Class fallback below.
            }

            document.documentElement
                .classList
                .remove(
                    'layout-menu-expanded'
                );

            return true;
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
                document.createElement('div');

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

            document.body.appendChild(hint);
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

    rearm();

    window.addEventListener(
        'popstate',
        () => {
            if (allowRealBack) {
                allowRealBack = false;
                return;
            }

            if (closeTopLayer()) {
                rearm();
                return;
            }

            const path =
                window.location.pathname
                    .replace(/\\/+$/, '')
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
                rearm();
                showExitHint();
                return;
            }

            if (
                pageLooksDirty()
                && !window.confirm(
                    'Perubahan belum disimpan. Tinggalkan halaman ini?'
                )
            ) {
                rearm();
                return;
            }

            allowRealBack = true;
            window.history.back();
        }
    );
})();
`;

        browser.executeScript({
            code,
        });
    };

    const injectRemoteAdapters = () => {
        injectDashboardButton();
        injectGeolocationBridge();
        injectBackAdapter();
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

                injectRemoteAdapters();
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

                failedBrowser?.close();

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
