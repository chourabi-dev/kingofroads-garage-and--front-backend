<?php

namespace App\Service;

use App\Entity\Maintenance;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

/**
 * Renders a maintenance as a PDF invoice (dompdf).
 *
 * Line prices are treated as prices excluding tax (HT). The invoice shows:
 *   Total HT + TVA (INVOICE_TVA_RATE %) + Timbre fiscal (INVOICE_TIMBRE_FISCAL) = Net à payer (TTC)
 * The timbre fiscal is a fixed stamp duty and is not subject to TVA.
 */
class InvoicePdfGenerator
{
    /**
     * @param array<string, string> $company company details, configured through .env (COMPANY_*)
     */
    public function __construct(
        private readonly Environment $twig,
        private readonly FrenchAmountInWords $amountInWords,
        private readonly array $company,
        private readonly float $tvaRate,
        private readonly float $timbreFiscal,
    ) {
    }

    /**
     * @return array{totalHt: float, tvaRate: float, tvaAmount: float, timbreFiscal: float, totalTtc: float}
     */
    public function computeTotals(Maintenance $maintenance): array
    {
        $totalHt = round($maintenance->getTotal(), 3);
        $tvaAmount = round($totalHt * $this->tvaRate / 100, 3);
        $timbre = round($this->timbreFiscal, 3);

        return [
            'totalHt' => $totalHt,
            'tvaRate' => $this->tvaRate,
            'tvaAmount' => $tvaAmount,
            'timbreFiscal' => $timbre,
            'totalTtc' => round($totalHt + $tvaAmount + $timbre, 3),
        ];
    }

    public function generate(Maintenance $maintenance): string
    {
        $totals = $this->computeTotals($maintenance);

        $html = $this->twig->render('admin/maintenance/invoice.html.twig', [
            'maintenance' => $maintenance,
            'bike' => $maintenance->getBike(),
            'client' => $maintenance->getBike()?->getClient(),
            'company' => $this->company,
            'totals' => $totals,
            'amountInWords' => $this->amountInWords->amount($totals['totalTtc']),
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
