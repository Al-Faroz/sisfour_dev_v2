# SisFour Android Release Engineering

## 1. Release identity

Current first-release baseline:

~~~text
applicationId / widget id = id.sch.mtsn4jombang.sisfour
versionName               = 1.0.0
versionCode               = 10000
~~~

`versionCode` must increase for every release that must update an installed older APK. The same release keystore/private key must be retained for the lifetime of the Android application identity.

## 2. Secret boundary

Never commit any of the following:

- release keystore;
- keystore/private-key password;
- populated `build-release.json`;
- exported private key or backup archive containing it.

The repository intentionally ignores:

~~~text
build-release.json
release-artifacts/
*.jks
*.keystore
*.p12
*.pfx
~~~

Keep the primary keystore outside the repository and maintain at least one independent encrypted backup under institutional control.

## 3. Create the release keystore once

Example command from a trusted Windows terminal:

~~~powershell
keytool -genkeypair -v `
  -keystore "C:\SisFour-Secrets\android\SisFour-release.jks" `
  -alias "sisfour-release" `
  -keyalg RSA `
  -keysize 4096 `
  -validity 10000
~~~

Record securely:

- keystore location;
- alias;
- keystore type;
- store password;
- key password;
- SHA-256 certificate fingerprint;
- responsible custodian and backup location.

Do not regenerate a different key for routine SisFour updates.

## 4. Local signing config

Copy:

~~~text
build-release.example.json
        ->
build-release.json
~~~

Then replace the local placeholder path/password values. `build-release.json` is ignored by Git.

## 5. Build gates before release

From `mobile/cordova`:

~~~powershell
npm ci
npx cordova prepare android
npm run requirements:android
npm run build:android
~~~

The debug build is the feature/UAT gate. Do not promote to signed release if the current exact source head has not passed real-device UAT.

## 6. Signed APK

After `build-release.json` is populated locally:

~~~powershell
npm run build:android:release:apk
~~~

Cordova Android 15.1.0 accepts `apk` and `bundle` as package types; release defaults to bundle, so the SisFour scripts explicitly select the required artifact.

## 7. Signed AAB

When an AAB is required for a distribution channel:

~~~powershell
npm run build:android:release:aab
~~~

APK remains the direct-install/UAT distribution artifact unless the chosen channel requires another format.

## 8. Signature verification

For APK:

~~~powershell
apksigner verify --verbose --print-certs "<path-to-release.apk>"
~~~

Compare the printed certificate SHA-256 digest with the institutional release-key record.

For AAB:

~~~powershell
jarsigner -verify -verbose -certs "<path-to-release.aab>"
~~~

## 9. Install tests

Clean install:

~~~powershell
adb install "<release.apk>"
~~~

Signed update test:

~~~powershell
adb install -r "<newer-release.apk>"
~~~

The update test is valid only when:

- package id is unchanged;
- signing certificate is unchanged;
- new `versionCode` is greater than the installed release;
- user data/session behavior is checked after update.

A debug-signed APK is not a substitute for a signed-release update test.

## 10. Distribution artifact

Final retained release set should include:

~~~text
SisFour-<versionName>-<versionCode>-release.apk
optional SisFour-<versionName>-<versionCode>-release.aab
SHA-256 checksum file
certificate SHA-256 fingerprint record
release/UAT checklist reference
~~~

Never include the keystore or passwords in the distribution package.
