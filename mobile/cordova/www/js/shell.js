(() => {
    'use strict';

    const APP_URL =
        'https://sisfour.mtsn4jombang.sch.id/';

    const APP_ORIGIN =
        new URL(APP_URL).origin;

    const status =
        document.getElementById('appStatus');

    const retryButton =
        document.getElementById('retryButton');

    let browser = null;
    let opening = false;
    let closingAfterError = false;

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

    const releaseBrowser = () => {
        browser = null;
        opening = false;
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
            'loadstart',
            () => {
                setStatus(
                    'Memuat SisFour…'
                );
            }
        );

        browser.addEventListener(
            'loadstop',
            () => {
                if (!browser) {
                    return;
                }

                opening = false;

                setStatus(
                    'SisFour siap.'
                );

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
