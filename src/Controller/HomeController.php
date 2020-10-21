<?php

namespace App\Controller;

use App\Entity\Header;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{

    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/", name="home")
     */
    public function index()
    {
        $bestProducts = $this->entityManager->getRepository(Product::class)->findByIsBest(true);
        $headers = $this->entityManager->getRepository(Header::class)->findAll();
        
        return $this->render('home/index.html.twig',[
            'bestProducts' => $bestProducts,
            'headers' => $headers
        ]);
    }
}
