<?php

namespace App\Controller\Admin;

use App\Entity\Order;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Router\CrudUrlGenerator;

class OrderCrudController extends AbstractCrudController
{

    private $entityManager;
    private $crudUrlGenerator;


    public function __construct(EntityManagerInterface $entityManager, CrudUrlGenerator $crudUrlGenerator)
    {
        $this->entityManager = $entityManager;
        $this->crudUrlGenerator = $crudUrlGenerator;
    }

    public static function getEntityFqcn(): string
    {
        return Order::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $updatePreparation = Action::new('updatePreparation', 'Prépartion en cours', 'fas fa-box-open')
            ->setCssClass('update-state')
            ->linkToCrudAction('updatePreparation');

        $updateDelivery = Action::new('updateDelivery', 'Livraison en cours', 'fas fa-truck')
            ->setCssClass('update-state')
            ->linkToCrudAction('updateDelivery');

        return $actions->add('index', 'detail')
            ->add('detail', $updatePreparation)
            ->add('detail', $updateDelivery)
            ->remove(Crud::PAGE_DETAIL, Action::EDIT)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::NEW)
            ->remove(Crud::PAGE_INDEX, Action::EDIT)
            ->remove(Crud::PAGE_INDEX, Action::DELETE);
    }

    public function updatePreparation(AdminContext $adminContext)
    {
        $order = $adminContext->getEntity()->getInstance();
        if ($order->getState() === 1) {

            $order->setState(2);
            $this->entityManager->flush();

            $this->addFlash('notice', sprintf(
                "<span style='background-color:#32ff7e; color:#000'>La commande <strong>%s</strong> est passée au status \"Préparation en cours\"<span>",
                $order->getReference()
            ));

            // Notification email ?
        } else if ($order->getState() !== 2) {
            $this->addFlash('notice', sprintf(
                "<span style='background-color:#ff3838; color:#000'>La commande <strong>%s</strong> devra avoir le status \"Payée\" afin de pouvoir la passer au status \"Préparation en cours\"<span>",
                $order->getReference()
            ));
        } else {
            $this->addFlash('notice', sprintf(
                "<span style='background-color:#ff3838; color:#000'>La commande <strong>%s</strong> est déjà au status \"Préparation en cours\"<span>",
                $order->getReference()
            ));
        }
        $url = $this->crudUrlGenerator
            ->build()
            ->setController(OrderCrudController::class)
            ->setAction('index')
            ->generateUrl();

        return $this->redirect($url);
    }

    public function updateDelivery(AdminContext $adminContext)
    {
        $order = $adminContext->getEntity()->getInstance();
        if ($order->getState() === 2) {

            $order->setState(3);
            $this->entityManager->flush();

            $this->addFlash('notice', sprintf(
                "<span style='background-color:#32ff7e; color:#000'>La commande <strong>%s</strong> est passée au status \"Livraison en cours\"<span>",
                $order->getReference()
            ));
            // Notification email ?
        } else if ($order->getState() !== 3) {
            $this->addFlash('notice', sprintf(
                "<span style='background-color:#ff3838; color:#000'>La commande <strong>%s</strong> devra avoir le status \"Préparation en cours\" afin de pouvoir la passer au status \"Livraison en cours\"<span>",
                $order->getReference()
            ));
        } else {
            $this->addFlash('notice', sprintf(
                "<span style='background-color:#ff3838; color:#000'>La commande <strong>%s</strong> est au status \"Livraison en cours\"<span>",
                $order->getReference()
            ));
        }
        $url = $this->crudUrlGenerator
            ->build()
            ->setController(OrderCrudController::class)
            ->setAction('index')
            ->generateUrl();

        return $this->redirect($url);
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setDefaultSort(['id' => 'DESC']);
    }


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            DateTimeField::new('createdAt', 'créée le'),
            TextField::new('user.fullname', 'Utilisateur'),
            TextEditorField::new('delivery', 'Adresse de livraison')->onlyOnDetail(),
            MoneyField::new('total')->setCurrency('EUR'),
            TextField::new('carrierName', 'Transporteur'),
            MoneyField::new('carrierPrice', 'Frais de port')->setCurrency('EUR'),
            ChoiceField::new('state', 'Etat commande')->setChoices([
                'Non payée' => '0',
                'Payée' => '1',
                'Préparation en cours' => '2',
                'Livraison en cours' => '3',
            ]),
            ArrayField::new('orderDetails', 'Produits achetés')->hideOnIndex()

        ];
    }
}
