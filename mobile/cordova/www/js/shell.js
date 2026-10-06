(() => {
    'use strict';

    const status = document.getElementById('appStatus');

    const setStatus = (message) => {
        if (status) {
            status.textContent = message;
        }
    };

    document.addEventListener(
        'deviceready',
        () => {
            document.documentElement.classList.add(
                'sisfour-cordova'
            );

            setStatus(
                'Perangkat siap. SisFour akan dibuka pada tahap integrasi berikutnya.'
            );
        },
        false
    );
})();
