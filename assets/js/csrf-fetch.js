(() => {
    'use strict';

    /**
     * SisisFour CSRF Fetch Wrapper
     *
     * Tujuan:
     * - seluruh request mutasi Fetch same-origin otomatis membawa token CSRF;
     * - tidak perlu menyalin token secara manual ke setiap modul;
     * - request GET/HEAD/OPTIONS tidak dimodifikasi;
     * - request cross-origin tidak disentuh;
     * - header yang sudah dipasang caller tidak ditimpa.
     *
     * Security.php menggunakan regenerate=false sehingga token tetap valid
     * untuk seluruh request AJAX selama lifetime halaman/session cookie.
     */

    if (typeof window.fetch !== 'function') {
        return;
    }

    const tokenMeta = document.querySelector(
        'meta[name="csrf-token"]'
    );

    const headerMeta = document.querySelector(
        'meta[name="csrf-header-name"]'
    );

    if (!tokenMeta || !tokenMeta.content) {
        return;
    }

    const csrfHeaderName =
        headerMeta?.content?.trim() || 'X-CSRF-TOKEN';

    const nativeFetch = window.fetch.bind(window);

    const mutationMethods = new Set([
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
    ]);

    const isSameOrigin = (input) => {
        try {
            const rawUrl = input instanceof Request
                ? input.url
                : String(input);

            const url = new URL(
                rawUrl,
                window.location.href
            );

            return url.origin === window.location.origin;
        } catch (error) {
            return false;
        }
    };

    const resolveMethod = (input, init) => {
        const initMethod = init?.method;

        if (initMethod) {
            return String(initMethod).toUpperCase();
        }

        if (input instanceof Request) {
            return String(input.method || 'GET').toUpperCase();
        }

        return 'GET';
    };

    const mergeHeaders = (input, init) => {
        const headers = new Headers();

        if (input instanceof Request) {
            input.headers.forEach((value, key) => {
                headers.set(key, value);
            });
        }

        if (init?.headers) {
            new Headers(init.headers).forEach((value, key) => {
                headers.set(key, value);
            });
        }

        return headers;
    };

    window.fetch = (input, init = {}) => {
        const method = resolveMethod(input, init);

        if (
            !mutationMethods.has(method)
            || !isSameOrigin(input)
        ) {
            return nativeFetch(input, init);
        }

        const headers = mergeHeaders(input, init);

        if (!headers.has(csrfHeaderName)) {
            headers.set(
                csrfHeaderName,
                tokenMeta.content
            );
        }

        if (input instanceof Request) {
            const request = new Request(input, {
                ...init,
                headers,
            });

            return nativeFetch(request);
        }

        return nativeFetch(input, {
            ...init,
            headers,
        });
    };

    window.SisisFourCsrf = Object.freeze({
        headerName: csrfHeaderName,

        getToken() {
            return tokenMeta.content;
        },

        getTokenName() {
            return document
                .querySelector(
                    'meta[name="csrf-token-name"]'
                )
                ?.content
                ?.trim() || 'csrf_test_name';
        },
    });
})();
