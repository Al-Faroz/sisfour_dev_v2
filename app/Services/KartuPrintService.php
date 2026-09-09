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

        $renderedCards = [];

        foreach ($cards as $card) {
            $renderedCards[] =
                $this->renderService->viewData($card);
        }

        $pages = array_chunk(
            $renderedCards,
            self::CARDS_PER_PAGE
        );

        $viewData = [
            'pages' => $pages,
            'side' => $side,
            'cards_per_page' =>
                self::CARDS_PER_PAGE,
            'background_back_data_uri' =>
                $renderedCards[0]
                    ['background_back_data_uri']
                    ?? null,
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
