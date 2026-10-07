'use strict';

const fs = require('node:fs');
const path = require('node:path');

module.exports = function patchSisFourInAppBrowserAutofill(context) {
    const projectRoot =
        context?.opts?.projectRoot
        || process.cwd();

    const candidates = [
        path.join(
            projectRoot,
            'platforms',
            'android',
            'app',
            'src',
            'main',
            'java',
            'org',
            'apache',
            'cordova',
            'inappbrowser',
            'InAppBrowser.java'
        ),
        path.join(
            projectRoot,
            'plugins',
            'cordova-plugin-inappbrowser',
            'src',
            'android',
            'InAppBrowser.java'
        ),
    ];

    const anchor =
        '                settings.setJavaScriptEnabled(true);';

    const marker =
        '                // SisFour: enable standard Android WebView autofill.';

    const patch = [
        anchor,
        marker,
        '                settings.setSaveFormData(true);',
        '                if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {',
        '                    inAppWebView.setImportantForAutofill(',
        '                            View.IMPORTANT_FOR_AUTOFILL_YES',
        '                    );',
        '                }',
    ].join('\n');

    let patched = false;

    candidates.forEach((target) => {
        if (!fs.existsSync(target)) {
            return;
        }

        const source =
            fs.readFileSync(target, 'utf8');

        if (source.includes(marker)) {
            patched = true;
            return;
        }

        if (!source.includes(anchor)) {
            throw new Error(
                'SisFour autofill patch anchor not found in '
                + target
            );
        }

        fs.writeFileSync(
            target,
            source.replace(anchor, patch),
            'utf8'
        );

        patched = true;
    });

    if (!patched) {
        throw new Error(
            'SisFour could not locate generated cordova-plugin-inappbrowser Android source for autofill patch.'
        );
    }
};
