<?php

namespace App\Controller;

use App\Entity\Maintenance;
use App\Service\InvoicePdfGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class MaintenanceInvoiceController extends AbstractController
{
    #[Route('/admin/maintenance/{id}/invoice.pdf', name: 'admin_maintenance_invoice', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function download(Maintenance $maintenance, InvoicePdfGenerator $generator): Response
    {
        $pdf = $generator->generate($maintenance);

        $response = new Response($pdf);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set(
            'Content-Disposition',
            $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $maintenance->getInvoiceNumber().'.pdf')
        );

        return $response;
    }
}
