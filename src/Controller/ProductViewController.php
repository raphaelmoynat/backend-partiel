<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Repository\ProductRepository;
use App\Service\QrCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/admin/products')]
#[IsGranted('ROLE_ADMIN')]
class ProductViewController extends AbstractController
{
    private QrCodeService $qrCodeService;

    public function __construct(QrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    #[Route('/', name: 'product_list')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findAll();

        return $this->render('product_view/index.html.twig', [
            'products' => $products,
        ]);
    }


    #[Route('/create', name: 'product_create', methods: ['GET', 'POST'], priority: 4)]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $product = new Product();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

            return $this->redirectToRoute('product_list');
        }

        return $this->render('product_view/create.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/edit/{id}', name: 'product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $qrCodeData = json_encode([
                'id' => $product->getId(),
                'name' => $product->getName(),
                'price' => $product->getPrice()
            ]);

            $qrCode = $this->qrCodeService->generateQrCode($qrCodeData);
            $product->setQrCode($qrCode);

            $entityManager->flush();

            return $this->redirectToRoute('product_list');
        }

        return $this->render('product_view/edit.html.twig', [
            'product' => $product,
            'form' => $form,
        ]);
    }

    #[Route('/delete/{id}', name: 'product_delete', priority: 5)]
    public function delete( Product $product, EntityManagerInterface $entityManager): Response
    {

        $entityManager->remove($product);
        $entityManager->flush();


        return $this->redirectToRoute('product_list');
    }

    #[Route('/{id}', name: 'product_show', priority: 3)]
    public function show(Product $product): Response
    {
        return $this->render('product_view/show.html.twig', [
            'product' => $product,
        ]);
    }





}
