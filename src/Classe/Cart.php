<?php

namespace App\Classe;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Cart
{

    private $session;
    private $entityManager;

    public function __construct(SessionInterface $session, EntityManagerInterface $entityManager)
    {
        $this->session = $session;
        $this->entityManager = $entityManager;
    }


    public function add($id)
    {
        $cart = $this->session->get('cart', []);

        if (empty($cart[$id])) {
            $cart[$id] = 1;
        } else {
            $cart[$id]++;
        }

        $this->session->set('cart', $cart);
    }

    public function get()
    {
        return $this->session->get('cart');
    }

    public function remove()
    {
        $this->session->remove('cart');
    }

    public function delete($id)
    {
        $cart = $this->session->get('cart', []);
        unset($cart[$id]);
        $this->session->set('cart', $cart);
    }

    public function decrease($id)
    {
        $cart = $this->session->get('cart', []);

        if ($cart[$id] === 1) {
            unset($cart[$id]);
        } else {
            $cart[$id]--;
        }

        $this->session->set('cart', $cart);
    }

    public function getFull()
    {
        $finalCart = [];
        $cart = $this->session->get('cart', []);
        if (!empty($cart)) {
            $product = null;
            foreach ($cart as $id => $quantity) {
                $product = $this->entityManager->getRepository(Product::class)->findOneById($id);
                if(!$product){
                    $this->delete($id);
                    continue;
                }
                $finalCart[] = [
                    'product' => $product,
                    'quantity' => $quantity
                ];  
                            
            }
        }
        return $finalCart;
    }
}
