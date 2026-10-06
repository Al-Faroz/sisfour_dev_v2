'use strict';

const exec = require('cordova/exec');

exports.download = (
    options,
    success,
    error
) => {
    exec(
        success,
        error,
        'SisFourNative',
        'download',
        [options]
    );
};


exports.downloadRequest = (
    options,
    success,
    error
) => {
    exec(
        success,
        error,
        'SisFourNative',
        'downloadRequest',
        [options]
    );
};
