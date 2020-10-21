<?php

namespace App\Controller;

use App\Classe\Cart;
use App\Entity\Order;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class StripeController extends AbstractController
{
    /**
     * @Route("/commande/create-session/{reference}", name="stripe_create_session", methods={"POST"})
     */
    public function index(Cart $cart, $reference, EntityManagerInterface $em)
    {
        $productsForStripe = [];
        $YOUR_DOMAIN = 'http://localhost:8000';

        $order = $em->getRepository(Order::class)->findOneByReference($reference);

        if(!$order){
            return new JsonResponse(['error' => 'order']);
        }

        foreach ($order->getOrderDetails()->getValues() as $product) {

            $productsForStripe[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $product->getPrice(),
                    'product_data' => [
                        'name' => $product->getProduct(),
                        'images' => [sprintf('%s/uploads/%s', $YOUR_DOMAIN, $em->getRepository(Product::class)->findOneByName($product->getProduct())->getIllustration())],
                    ],
                ],
                'quantity' => $product->getQuantity(),
            ];
        }

        $productsForStripe[] = [
            'price_data' => [
                'currency' => 'eur',
                'unit_amount' => $order->getCarrierPrice(),
                'product_data' => [
                    'name' => $order->getCarrierName()
                ],
            ],
            'quantity' => 1,
        ];

        Stripe::setApiKey('sk_test_51HeKf7AlQIBk1onfs1bHcuvaSyxGPpizpMs5ijqCzH4ZoQJzpSQBn95nWfAKcgMjMwxpsMjI7CyFgq6iXZlwvtNr00OguOYi1J');

        $checkout_session = Session::create([
            'customer_email' =>$this->getUser()->getEmail(),
            'payment_method_types' => ['card'],
            'line_items' => [$productsForStripe],
            'mode' => 'payment',
            'success_url' => $YOUR_DOMAIN . '/commande/merci/{CHECKOUT_SESSION_ID}',
            'cancel_url' => $YOUR_DOMAIN . '/commande/erreur/{CHECKOUT_SESSION_ID}',
        ]);

        $order->setStripeSessionID($checkout_session->id);
        $em->flush();

        return new JsonResponse(['id' => $checkout_session->id]);
    }
}
