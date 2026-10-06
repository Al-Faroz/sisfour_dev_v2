# SisFour Android Branding Source

Source of truth awal G4.1G:

- `mtsn4jombang-logo-master.png`
- copied verbatim from `uploads/settings/branding/logo_20260908_181247_ce029c58.png`
- source blob SHA: `bcecd043b6230f6d8350b7641cb5439422e2a34b`
- existing runtime `logo_...` dan `icon_...` pada baseline mempunyai blob yang identik
- source PNG sekitar 2319 × 2299 px

Rules:

- jangan memakai asset/default branding Apache Cordova;
- master resmi tidak diedit in-place;
- launcher/splash turunan kelak harus dibuat dari master ini;
- adaptive foreground boleh diberi safe-padding khusus setelah real-device visual UAT;
- icon dan splash final boleh dipisahkan bila kebutuhan visual Android berbeda;
- release keystore/signing material tidak boleh ditempatkan di folder ini atau repository biasa.

Current recovery candidate keeps the official master untouched and uses Android-specific safe-zone XML drawables for adaptive foreground and splash. The local post-splash shell is intentionally text-only to avoid the double-logo transition observed on device. Final launcher/splash acceptance remains a clean-build real-device gate.
