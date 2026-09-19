<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;
use ZipArchive;

class KartuPrintService
{
    private const CARDS_PER_PAGE = 10;

    protected KartuRenderService $renderService;

    public function __construct()
    {
        $this->renderService = new KartuRenderService();
    }

    public function pdf(
        array $cards,
        string $side = 'front'
    ): string {
        $side = strtolower(trim($side));

        if (!in_array($side, ['front', 'back'], true)) {
            $side = 'front';
        }

        $background = $this->renderService->backgroundDataUri($side);

        if ($side === 'back') {
            // Sisi belakang identik untuk setiap kartu. Tidak perlu membentuk
            // QR, foto, atau data individual yang tidak pernah dirender.
            $renderedCards = array_fill(0, count($cards), []);
        } else {
            $renderedCards = [];

            foreach ($cards as $card) {
                $renderedCards[] = $this->renderService->viewData(
                    $card,
                    false
                );
            }
        }

        $pages = array_chunk(
            $renderedCards,
            self::CARDS_PER_PAGE
        );

        $viewData = [
            'pages' => $pages,
            'side' => $side,
            'cards_per_page' => self::CARDS_PER_PAGE,
            'background_front_data_uri' => $side === 'front'
                ? $background
                : null,
            'background_back_data_uri' => $side === 'back'
                ? $background
                : null,
        ];

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(
            view('kartu/cetak_massal', $viewData)
        );
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function frontJpgZip(array $cards): string
    {
        if ($cards === []) {
            throw new RuntimeException(
                'Tidak ada kartu untuk diexport.'
            );
        }

        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException(
                'Ekstensi ZIP PHP tidak tersedia.'
            );
        }

        $cacheDir = rtrim(
            WRITEPATH,
            '/\\'
        ) . DIRECTORY_SEPARATOR . 'cache';

        if (
            !is_dir($cacheDir)
            && !mkdir($cacheDir, 0775, true)
            && !is_dir($cacheDir)
        ) {
            throw new RuntimeException(
                'Folder cache sementara tidak dapat dibuat.'
            );
        }

        $path = tempnam(
            $cacheDir,
            'kartu_jpg_'
        );

        if ($path === false) {
            throw new RuntimeException(
                'File ZIP sementara tidak dapat dibuat.'
            );
        }

        $zip = new ZipArchive();
        $opened = false;

        try {
            $status = $zip->open(
                $path,
                ZipArchive::CREATE | ZipArchive::OVERWRITE
            );

            if ($status !== true) {
                throw new RuntimeException(
                    'Arsip ZIP tidak dapat dibuka.'
                );
            }

            $opened = true;

            foreach ($cards as $index => $card) {
                $jpeg = $this->renderService->frontJpeg(
                    $card
                );

                $nisn = $this->safeFilenamePart(
                    (string) ($card['nisn'] ?? 'NISN')
                );

                $nama = $this->safeFilenamePart(
                    (string) ($card['nama'] ?? 'SISWA')
                );

                $filename = sprintf(
                    '%03d_%s_%s.jpg',
                    $index + 1,
                    $nisn !== '' ? $nisn : 'NISN',
                    $nama !== '' ? $nama : 'SISWA'
                );

                if (!$zip->addFromString(
                    $filename,
                    $jpeg
                )) {
                    throw new RuntimeException(
                        'JPG gagal ditambahkan ke ZIP.'
                    );
                }
            }

            if (!$zip->close()) {
                throw new RuntimeException(
                    'Arsip ZIP gagal diselesaikan.'
                );
            }

            $opened = false;

            $binary = file_get_contents($path);

            if ($binary === false || $binary === '') {
                throw new RuntimeException(
                    'Arsip ZIP kosong atau tidak dapat dibaca.'
                );
            }

            return $binary;
        } finally {
            if ($opened) {
                $zip->close();
            }

            if (is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function safeFilenamePart(string $value): string
    {
        $value = trim($value);

        if (function_exists('iconv')) {
            $ascii = @iconv(
                'UTF-8',
                'ASCII//TRANSLIT//IGNORE',
                $value
            );

            if (is_string($ascii) && $ascii !== '') {
                $value = $ascii;
            }
        }

        $value = preg_replace(
            '/[^A-Za-z0-9_-]+/',
            '_',
            $value
        ) ?? '';

        return trim($value, '_-');
    }
}
