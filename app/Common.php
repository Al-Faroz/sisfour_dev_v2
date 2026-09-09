<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (! function_exists('sisfour_asset_url')) {
    /**
     * URL asset lokal dengan cache-busting berbasis file modification time.
     *
     * Tujuan utama: browser tidak mempertahankan JS/CSS versi lama setelah
     * deployment/replace file. Jika file tidak ditemukan, URL normal tetap
     * dikembalikan agar tidak mengubah perilaku bawaan aplikasi.
     */
    function sisfour_asset_url(string $path): string
    {
        $normalized = ltrim(str_replace('\\', '/', trim($path)), '/');

        if ($normalized === '') {
            return base_url();
        }

        $url = base_url($normalized);
        $fullPath = FCPATH . str_replace('/', DIRECTORY_SEPARATOR, $normalized);

        if (! is_file($fullPath)) {
            return $url;
        }

        $modifiedAt = filemtime($fullPath);

        if ($modifiedAt === false) {
            return $url;
        }

        return $url . '?v=' . $modifiedAt;
    }
}
