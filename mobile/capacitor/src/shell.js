import {
    InAppBrowser,
    DefaultWebViewOptions
} from '@capacitor/inappbrowser';

const SISFOUR_URL = 'https://sisfour.mtsn4jombang.sch.id/';

const options = {
    ...DefaultWebViewOptions,
    clearCache: false,
    clearSessionCache: false,
    showToolbar: false,
    showURL: false,
    showNavigationButtons: false,
    android: {
        ...DefaultWebViewOptions.android,
        hardwareBack: true,
        isIsolated: true
    }
};

let opening = false;

async function openSisFour() {
    if (opening) return;
    opening = true;

    try {
        await InAppBrowser.openInWebView({
            url: SISFOUR_URL,
            options
        });
    } catch (error) {
        console.error('Gagal membuka SisFour:', error);
        const status = document.getElementById('status');
        const retry = document.getElementById('retryButton');
        if (status) status.textContent = 'SisFour tidak dapat dibuka. Periksa koneksi.';
        if (retry) retry.hidden = false;
    } finally {
        opening = false;
    }
}

document.getElementById('retryButton')?.addEventListener('click', openSisFour);

InAppBrowser.addListener('browserClosed', () => {
    openSisFour();
});

openSisFour();
