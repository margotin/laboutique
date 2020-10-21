<?php

namespace App\EventSubscriber;

use App\Entity\Header;
use App\Entity\Product;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use ReflectionClass;

class EasyAdminSubscriber implements EventSubscriberInterface
{

    private $appKernel;

    public function __construct(KernelInterface $appKernel)
    {
        $this->appKernel = $appKernel;
    }

    public static function getSubscribedEvents()
    {
        return [
            BeforeEntityPersistedEvent::class => ['setIllustration'],
            BeforeEntityUpdatedEvent::class => ['updateIllustration']
        ];
    }



    public function setIllustration(BeforeEntityPersistedEvent $event)
    {
        $this->uploadIllustration($event);
    }

    public function updateIllustration(BeforeEntityUpdatedEvent $event)
    {
        $this->uploadIllustration($event);
    }

    private function uploadIllustration($event)
    {
        $entity = $event->getEntityInstance();

        if (!$this->isProductInstance($entity) && !$this->isHeaderInstance($entity)) {
            unset($entity);
            return;
        }

        $entityName = (new ReflectionClass($entity))->getShortName();

        if (!empty($_FILES[$entityName]['tmp_name']['illustration'])) {
            $name = time();
            $fileName = sprintf(
                "%s.%s",
                $name,
                pathinfo($_FILES[$entityName]['name']['illustration'], PATHINFO_EXTENSION)
            );
            move_uploaded_file($_FILES[$entityName]['tmp_name']['illustration'], sprintf("%s/public/uploads/%s", $this->appKernel->getProjectDir(), $fileName));

            $entity->setIllustration($fileName);
        }
    }

    private function isProductInstance($event)
    {
        return $event instanceof Product;
    }

    private function isHeaderInstance($event)
    {
        return $event instanceof Header;
    }
}
