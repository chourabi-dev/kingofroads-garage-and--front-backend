<?php

namespace App\Service;

use App\Entity\Maintenance;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

/**
 * Renders a maintenance as a PDF invoice (dompdf).
 */
class InvoicePdfGenerator
{
    public function __construct(private readonly Environment $twig)
    {
    }

    public function generate(Maintenance $maintenance): string
    {
        $html = $this->twig->render('admin/maintenance/invoice.html.twig', [
            'maintenance' => $maintenance,
            'bike' => $maintenance->getBike(),
            'client' => $maintenance->getBike()?->getClient(),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
