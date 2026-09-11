<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

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
}
