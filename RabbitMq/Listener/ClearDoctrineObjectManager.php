<?php
namespace OldSound\RabbitMqBundle\RabbitMq\Listener;

use Doctrine\Common\Persistence\ManagerRegistry;
use OldSound\RabbitMqBundle\RabbitMq\Event\Event;
use OldSound\RabbitMqBundle\RabbitMq\Event\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Doctrine caches fetched objects for the entire process life.
 * Its good for web pages but not for long running processes.
 * This listener clears doctine`s identity map on every new message.
 */
class ClearDoctrineObjectManager implements EventSubscriberInterface
{
    /**
     * @var ManagerRegistry[]
     */
    protected $managerRegistries = [];

    /**
     * @param ManagerRegistry $manager
     */
    public function addManagerRegistry(ManagerRegistry $manager)
    {
        $this->managerRegistries[] = $manager;
    }

    /**
     * @param Event $event
     */
    protected function clearManagersIdentityMap(Event $event)
    {
        foreach ($this->managerRegistries as $registry) {
            foreach ($registry->getManagers() as $manager) {
                $manager->clear();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::MESSAGE => ['clearManagersIdentityMap', 50]
        ];
    }
}
