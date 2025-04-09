<?php

namespace App\Controller;

use App\Entity\Product;
use Dompdf\Dompdf;
use Endroid\QrCode\Builder\BuilderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class PdfGeneratorController extends AbstractController
{
    #[Route('/pdf/generator/{id}', name: 'app_pdf_generator')]
    public function index(Product $product): Response
    {
        $data = [
            "id"=>$product->getId(),
            "name" => $product->getName(),
            "price" => $product->getPrice(),
            "qrCode" => $product->getQrCode(),
        ];

        $html = $this->renderView('pdf_generator/index.html.twig', $data);
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="ficheproduit-'.$product->getId().'.pdf"',
        ]);
    }

}
