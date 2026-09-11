(() => {
    'use strict';

    /**
     * SisisFour CSRF Fetch Wrapper
     *
     * Tujuan:
     * - seluruh request mutasi Fetch same-origin otomatis membawa token CSRF;
     * - tidak perlu menyalin token secara manual ke setiap modul;
     * - request GET/HEAD/OPTIONS tidak dimodifikasi header-nya;
     * - request cross-origin tidak disentuh;
     * - header yang sudah dipasang caller tidak ditimpa;
     * - fetch Web yang diarahkan AuthFilter ke halaman login memulihkan
     *   navigasi utama agar modul tidak menerima HTML login sebagai JSON.
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

    let redirectingToLogin = false;

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

    const isAuthLoginResponse = (response) => {
        if (
            !response
            || !response.redirected
            || !response.url
        ) {
            return false;
        }

        try {
            const url = new URL(
                response.url,
                window.location.href
            );

            if (url.origin !== window.location.origin) {
                return false;
            }

            return url.pathname
                .replace(/\/+$/, '')
                .endsWith('/auth/login');
        } catch (error) {
            return false;
        }
    };

    const recoverExpiredSession = (response) => {
        if (!isAuthLoginResponse(response)) {
            return response;
        }

        if (!redirectingToLogin) {
            redirectingToLogin = true;
            window.location.assign(response.url);
        }

        /*
         * Jangan teruskan HTML login ke caller yang mengharapkan JSON/PDF.
         * Navigasi sedang berpindah ke halaman login, sehingga promise ini
         * sengaja dibiarkan pending sampai document lama di-unload.
         */
        return new Promise(() => {});
    };

    const runFetch = (
        input,
        init,
        sameOrigin
    ) => {
        const promise = nativeFetch(input, init);

        if (!sameOrigin) {
            return promise;
        }

        return promise.then(recoverExpiredSession);
    };

    window.fetch = (input, init = {}) => {
        const sameOrigin = isSameOrigin(input);
        const method = resolveMethod(input, init);

        if (
            !sameOrigin
            || !mutationMethods.has(method)
        ) {
            return runFetch(
                input,
                init,
                sameOrigin
            );
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

            return runFetch(
                request,
                undefined,
                true
            );
        }

        return runFetch(
            input,
            {
                ...init,
                headers,
            },
            true
        );
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
