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
