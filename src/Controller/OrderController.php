<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OrderController extends AbstractController
{
    #[Route('/api/orders/create', name: 'create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, ProductRepository $productRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);
        $order = new Order();
        $order->setCustomer($user);


        $total = 0;
        foreach ($data['items'] as $item) {
            if (!isset($item['productId']) || !isset($item['quantity']) || $item['quantity'] <= 0) {
                return new JsonResponse(['error' => 'Invalid product data'], Response::HTTP_BAD_REQUEST);
            }

            $product = $productRepository->find($item['productId']);
            if (!$product) {
                return new JsonResponse(['error' => 'Product not found: ' . $item['productId']], Response::HTTP_BAD_REQUEST);
            }

            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity($item['quantity']);
            $orderItem->setCustomerOrder($order);
            $entityManager->persist($orderItem);
            $total += $product->getPrice() * $item['quantity'];
        }

        $order->setTotalAmount($total);
        $entityManager->persist($order);
        $entityManager->flush();

        return new JsonResponse([
            'message' => 'Order created successfully',
            'orderId' => $order->getId(),
            'total' => $order->getTotalAmount()
        ], Response::HTTP_CREATED);
    }


    #[Route('/admin/orders', name: 'admin_orders_list', priority: 3)]
    public function adminOrdersList(OrderRepository $orderRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $orders = $orderRepository->findAll();

        return $this->render('order/list.html.twig', [
            'orders' => $orders
        ]);
    }

    #[Route('/admin/orders/{id}', name: 'admin_orders_show')]
    public function adminOrderShow(Order $order): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('order/show.html.twig', [
            'order' => $order
        ]);
    }

    #[Route('/api/orders/my-orders', name: 'api_my_orders', methods: ['GET'])]
    public function getMyOrders(OrderRepository $orderRepository): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'No user connected'], Response::HTTP_UNAUTHORIZED);
        }
        $orders = $orderRepository->findBy(['customer' => $user], ['id' => 'DESC']);


        foreach ($orders as $order) {
            $items = [];
            foreach ($order->getOrderItems() as $item) {
                $items[] = [
                    'id' => $item->getId(),
                    'productId' => $item->getProduct()->getId(),
                    'productName' => $item->getProduct()->getName(),
                    'quantity' => $item->getQuantity(),
                    'unitPrice' => $item->getProduct()->getPrice(),
                    'subtotal' => $item->getProduct()->getPrice() * $item->getQuantity()
                ];
            }

            $myOrders[] = [
                'id' => $order->getId(),
                'totalAmount' => $order->getTotalAmount(),
                'items' => $items
            ];
        }

        return new JsonResponse(['orders' => $myOrders], Response::HTTP_OK);
    }

}
