(() => {
    'use strict';

    const app = document.getElementById('profileGuruApp');
    if (!app) return;

    const baseUrl = app.dataset.baseUrl.replace(/\/+$/, '');
    const endpoint = (path) =>
        `${baseUrl}/${String(path).replace(/^\/+/, '')}`;

    const parseResponse = async (response) => {
        const data = await response.json().catch(() => ({}));

        if (!response.ok || data.success === false) {
            throw new Error(
                data.message || 'Permintaan tidak dapat diproses.'
            );
        }

        return data;
    };

    const showError = (error) => {
        Swal.fire({
            icon: 'error',
            title: 'Gagal',
            text: error?.message || 'Terjadi kesalahan.',
        });
    };

    const showSuccess = (message) =>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: message,
            timer: 1600,
            showConfirmButton: false,
        });

    const profileForm = document.getElementById('formProfileGuru');

    profileForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const button = document.getElementById(
            'btnSimpanProfileGuru'
        );
        const spinner = button?.querySelector('.spinner-border');

        if (button) button.disabled = true;
        spinner?.classList.remove('d-none');

        try {
            const formData = new FormData(profileForm);
            const payload = new URLSearchParams();

            for (const [key, value] of formData.entries()) {
                if (key !== 'csrf_test_name') {
                    payload.append(key, value);
                }
            }

            const response = await fetch(
                endpoint('profile/guru/update'),
                {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type':
                            'application/x-www-form-urlencoded;charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: payload,
                }
            );

            const result = await parseResponse(response);
            await showSuccess(result.message);
        } catch (error) {
            showError(error);
        } finally {
            if (button) button.disabled = false;
            spinner?.classList.add('d-none');
        }
    });

    const fotoForm = document.getElementById(
        'formFotoProfileGuru'
    );

    fotoForm?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const input = document.getElementById(
            'fotoProfileGuru'
        );
        const file = input?.files?.[0];

        if (!file) {
            showError(new Error('Pilih file foto terlebih dahulu.'));
            return;
        }

        const button = fotoForm.querySelector(
            'button[type="submit"]'
        );
        const spinner = button?.querySelector('.spinner-border');

        if (button) button.disabled = true;
        spinner?.classList.remove('d-none');

        try {
            const body = new FormData();
            body.append('foto', file);

            const response = await fetch(
                endpoint('profile/guru/upload-foto'),
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body,
                }
            );

            const result = await parseResponse(response);
            const filename = result.data?.foto;

            if (filename) {
                const image = document.getElementById(
                    'profileGuruFoto'
                );

                if (image) {
                    image.src = endpoint(
                        `uploads/foto_guru/${encodeURIComponent(filename)}`
                    );
                    image.alt = 'Foto Profile Guru';
                    image.classList.remove('d-none');
                }

                document
                    .getElementById('profileGuruFotoFallback')
                    ?.classList.add('d-none');
            }

            fotoForm.reset();
            await showSuccess(result.message);
        } catch (error) {
            showError(error);
        } finally {
            if (button) button.disabled = false;
            spinner?.classList.add('d-none');
        }
    });
})();
