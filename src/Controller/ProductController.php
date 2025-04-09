<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Service\QrCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products')]
class ProductController extends AbstractController

{

    private QrCodeService $qrCodeService;

    public function __construct(QrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    #[Route('/', name: 'api_product_list', methods: ['GET'])]
    public function index(ProductRepository $productRepository): JsonResponse
    {
        $products = $productRepository->findAll();
        $data = [];

        foreach ($products as $product) {
            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice(),
                'qrCode' => $product->getQrCode(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/create', name: 'api_product_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || !isset($data['price'])) {
            return $this->json(['error' => 'fields missing'], Response::HTTP_BAD_REQUEST);
        }

        $product = new Product();
        $product->setName($data['name']);
        $product->setPrice((float) $data['price']);

        $entityManager->persist($product);
        $entityManager->flush();

        $qrCodeData = json_encode([
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice()
        ]);
        $qrCode = $this->qrCodeService->generateQrCode($qrCodeData);
        $product->setQrCode($qrCode);


        $entityManager->flush();

        return $this->json([
            'id' => $product->getId(),
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'qrCode' => $product->getQrCode(),
        ], Response::HTTP_CREATED);
    }


    #[Route('/products/check/{qrCode}', name: 'check_product', methods: ['GET'])]
    public function checkProduct(string $qrCode, ProductRepository $productRepository): JsonResponse
    {
        $product = $productRepository->findOneBy(['qrCode' => $qrCode, 'isDeleted' => false]);
        if ($product) {
            return $this->json([
                'exists' => true,
                'product' => [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'price' => $product->getPrice(),
                    'qrCode' => $product->getQrCode(),
                ]
            ]);
        }

        return $this->json(['exists' => false, 'message' => 'Produit non trouvé'], 404);
    }


}
